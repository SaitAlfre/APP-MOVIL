<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria as AuditoriaDominio;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Produccion\EstadoLoteProduccion;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion as LoteProduccionDominio;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion as LoteProduccionEloquent;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto as MovimientoProductoEloquent;
use App\Infrastructure\Persistence\Eloquent\Producto as ProductoEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentLoteProduccionRepository implements LoteProduccionRepositoryInterface
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function buscarPorId(int $id): ?LoteProduccionDominio
    {
        $registro = LoteProduccionEloquent::query()->find($id);

        return $registro !== null ? $this->aDominio($registro) : null;
    }

    public function paginar(int $porPagina = 20): LengthAwarePaginator
    {
        return LoteProduccionEloquent::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->through(fn (LoteProduccionEloquent $l) => $this->aDominio($l));
    }

    public function litrosAsignadosEnFecha(DateTimeImmutable $fecha): float
    {
        return (float) LoteProduccionEloquent::query()
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->where('estado', '!=', EstadoLoteProduccion::Cancelado->value)
            ->sum('litros_asignados');
    }

    public function existeAsignacionEnFecha(DateTimeImmutable $fecha): bool
    {
        return $this->litrosAsignadosEnFecha($fecha) > 0.0;
    }

    public function crear(LoteProduccionDominio $lote): LoteProduccionDominio
    {
        return DB::transaction(function () use ($lote) {
            // Mismo mutex usado en el resto de Producción (bloquear vehiculos en orden fijo) para
            // serializar la lectura del saldo del día y evitar que dos lotes concurrentes asignen
            // litros que ya no están disponibles.
            DB::table('vehiculos')->orderBy('id')->lockForUpdate()->get(['id']);

            $poolTotal = array_sum(array_column($this->entregas->recepcionPorVehiculoEnFecha($lote->fecha), 'litros'));
            $yaAsignado = $this->litrosAsignadosEnFecha($lote->fecha);
            $disponible = round($poolTotal - $yaAsignado, 3);

            if (round($lote->litrosAsignados, 3) > $disponible + 0.001) {
                throw LoteProduccionInvalidoException::superaSaldoDisponible(max(0.0, $disponible));
            }

            $codigo = $this->generarCodigo($lote->fecha);

            $registro = LoteProduccionEloquent::query()->create([
                'codigo' => $codigo,
                'producto_id' => $lote->productoId,
                'fecha' => $lote->fecha->format('Y-m-d'),
                'litros_por_unidad_snapshot' => $lote->litrosPorUnidadSnapshot,
                'litros_asignados' => $lote->litrosAsignados,
                'unidades_estimadas' => $lote->unidadesEstimadas,
                'estado' => EstadoLoteProduccion::Borrador->value,
                'origen_acopio' => $lote->origenAcopio,
                'responsable_id' => $lote->responsableId,
            ]);

            $this->auditorias->registrar(new AuditoriaDominio(
                id: null,
                entidad: 'lote_produccion',
                entidadId: $registro->id,
                accion: AccionAuditoria::Crear,
                valorAntes: null,
                valorDespues: "codigo={$codigo};litros_asignados={$lote->litrosAsignados}",
                motivo: null,
                usuarioId: $lote->responsableId,
                ocurridoEn: new DateTimeImmutable,
            ));

            return $this->aDominio($registro);
        });
    }

    public function iniciar(int $id, DateTimeImmutable $ahora, int $usuarioId): LoteProduccionDominio
    {
        return DB::transaction(function () use ($id, $ahora, $usuarioId) {
            $registro = LoteProduccionEloquent::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $estadoActual = EstadoLoteProduccion::from($registro->estado);

            if ($estadoActual !== EstadoLoteProduccion::Borrador) {
                throw LoteProduccionInvalidoException::transicionInvalida($estadoActual, 'iniciar');
            }

            $registro->update(['estado' => EstadoLoteProduccion::EnProceso->value, 'iniciado_en' => $ahora]);

            $this->auditorias->registrar(new AuditoriaDominio(
                id: null,
                entidad: 'lote_produccion',
                entidadId: $registro->id,
                accion: AccionAuditoria::Actualizar,
                valorAntes: 'estado=borrador',
                valorDespues: 'estado=en_proceso',
                motivo: null,
                usuarioId: $usuarioId,
                ocurridoEn: $ahora,
            ));

            return $this->aDominio($registro);
        });
    }

    public function finalizar(int $id, float $litrosUsados, float $litrosMermaProceso, DateTimeImmutable $ahora, int $usuarioId): LoteProduccionDominio
    {
        return DB::transaction(function () use ($id, $litrosUsados, $litrosMermaProceso, $ahora, $usuarioId) {
            $registro = LoteProduccionEloquent::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $estadoActual = EstadoLoteProduccion::from($registro->estado);

            if ($estadoActual !== EstadoLoteProduccion::EnProceso) {
                throw LoteProduccionInvalidoException::transicionInvalida($estadoActual, 'finalizar');
            }

            LoteProduccionDominio::validarFinalizacion((float) $registro->litros_asignados, $litrosUsados, $litrosMermaProceso);

            $unidadesProducidas = (int) floor($litrosUsados / (float) $registro->litros_por_unidad_snapshot);
            $litrosSobrantes = round((float) $registro->litros_asignados - $litrosUsados - $litrosMermaProceso, 3);

            $registro->update([
                'estado' => EstadoLoteProduccion::Finalizado->value,
                'litros_usados' => $litrosUsados,
                'litros_merma_proceso' => $litrosMermaProceso,
                'litros_sobrantes' => $litrosSobrantes,
                'unidades_producidas' => $unidadesProducidas,
                'finalizado_en' => $ahora,
            ]);

            $producto = ProductoEloquent::query()->whereKey($registro->producto_id)->lockForUpdate()->firstOrFail();
            $producto->update(['existencia' => (float) $producto->existencia + $unidadesProducidas]);

            MovimientoProductoEloquent::query()->create([
                'producto_id' => $registro->producto_id,
                'tipo' => 'produccion',
                'cantidad' => $unidadesProducidas,
                'unidad' => $producto->unidad_produccion,
                'motivo' => "Lote {$registro->codigo}",
                'usuario_id' => $usuarioId,
                'fecha' => $ahora,
            ]);

            $this->auditorias->registrar(new AuditoriaDominio(
                id: null,
                entidad: 'lote_produccion',
                entidadId: $registro->id,
                accion: AccionAuditoria::Actualizar,
                valorAntes: 'estado=en_proceso',
                valorDespues: json_encode(['estado' => 'finalizado', 'litros_usados' => $litrosUsados, 'litros_merma_proceso' => $litrosMermaProceso, 'litros_sobrantes' => $litrosSobrantes, 'unidades_producidas' => $unidadesProducidas]),
                motivo: null,
                usuarioId: $usuarioId,
                ocurridoEn: $ahora,
            ));

            return $this->aDominio($registro);
        });
    }

    public function cancelar(int $id, string $motivo, DateTimeImmutable $ahora, int $usuarioId): LoteProduccionDominio
    {
        return DB::transaction(function () use ($id, $motivo, $ahora, $usuarioId) {
            $registro = LoteProduccionEloquent::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $estadoActual = EstadoLoteProduccion::from($registro->estado);

            if (! in_array($estadoActual, [EstadoLoteProduccion::Borrador, EstadoLoteProduccion::EnProceso], true)) {
                throw LoteProduccionInvalidoException::transicionInvalida($estadoActual, 'cancelar');
            }

            if (trim($motivo) === '') {
                throw LoteProduccionInvalidoException::motivoCancelacionObligatorio();
            }

            $registro->update([
                'estado' => EstadoLoteProduccion::Cancelado->value,
                'cancelado_en' => $ahora,
                'motivo_cancelacion' => $motivo,
            ]);

            $this->auditorias->registrar(new AuditoriaDominio(
                id: null,
                entidad: 'lote_produccion',
                entidadId: $registro->id,
                accion: AccionAuditoria::Anular,
                valorAntes: "estado={$estadoActual->value}",
                valorDespues: 'estado=cancelado',
                motivo: $motivo,
                usuarioId: $usuarioId,
                ocurridoEn: $ahora,
            ));

            return $this->aDominio($registro);
        });
    }

    public function sumLitrosUsados(): float
    {
        return (float) LoteProduccionEloquent::query()->sum('litros_usados');
    }

    public function sumUnidadesProducidas(): int
    {
        return (int) LoteProduccionEloquent::query()->sum('unidades_producidas');
    }

    public function contarPorEstadoEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $conteos = LoteProduccionEloquent::query()
            ->whereDate('fecha', '>=', $desde->format('Y-m-d'))
            ->whereDate('fecha', '<=', $hasta->format('Y-m-d'))
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'borrador' => (int) ($conteos['borrador'] ?? 0),
            'en_proceso' => (int) ($conteos['en_proceso'] ?? 0),
            'finalizado' => (int) ($conteos['finalizado'] ?? 0),
            'cancelado' => (int) ($conteos['cancelado'] ?? 0),
        ];
    }

    public function sumLitrosUsadosEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): float
    {
        return (float) LoteProduccionEloquent::query()
            ->whereDate('fecha', '>=', $desde->format('Y-m-d'))
            ->whereDate('fecha', '<=', $hasta->format('Y-m-d'))
            ->sum('litros_usados');
    }

    public function litrosAsignadosPorDiaEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        // Se agrupa en PHP (no con GROUP BY fecha) porque en sqlite la columna DATE puede volver
        // con hora incluida ("2026-09-20 00:00:00"), mientras que en MySQL vuelve solo la fecha;
        // normalizar acá evita que ambos motores agrupen distinto.
        return LoteProduccionEloquent::query()
            ->whereDate('fecha', '>=', $desde->format('Y-m-d'))
            ->whereDate('fecha', '<=', $hasta->format('Y-m-d'))
            ->where('estado', '!=', EstadoLoteProduccion::Cancelado->value)
            ->get(['fecha', 'litros_asignados'])
            ->groupBy(fn ($fila) => DateTimeImmutable::createFromInterface($fila->fecha)->format('Y-m-d'))
            ->map(fn ($grupo, $fecha) => ['fecha' => $fecha, 'litros' => (float) $grupo->sum('litros_asignados')])
            ->values()
            ->all();
    }

    /** Se llama con el mutex de vehiculos ya bloqueado por crear(), así que el conteo es estable. */
    private function generarCodigo(DateTimeImmutable $fecha): string
    {
        $prefijo = 'L-'.$fecha->format('Ymd').'-';
        $consecutivo = LoteProduccionEloquent::query()->whereDate('fecha', $fecha->format('Y-m-d'))->count() + 1;

        return $prefijo.str_pad((string) $consecutivo, 2, '0', STR_PAD_LEFT);
    }

    private function aDominio(LoteProduccionEloquent $registro): LoteProduccionDominio
    {
        return LoteProduccionDominio::reconstruir(
            id: $registro->id,
            codigo: $registro->codigo,
            productoId: $registro->producto_id,
            fecha: DateTimeImmutable::createFromInterface($registro->fecha),
            litrosPorUnidadSnapshot: (float) $registro->litros_por_unidad_snapshot,
            litrosAsignados: (float) $registro->litros_asignados,
            litrosUsados: $registro->litros_usados !== null ? (float) $registro->litros_usados : null,
            litrosMermaProceso: $registro->litros_merma_proceso !== null ? (float) $registro->litros_merma_proceso : null,
            litrosSobrantes: $registro->litros_sobrantes !== null ? (float) $registro->litros_sobrantes : null,
            unidadesEstimadas: $registro->unidades_estimadas,
            unidadesProducidas: $registro->unidades_producidas,
            estado: EstadoLoteProduccion::from($registro->estado),
            origenAcopio: $registro->origen_acopio ?? [],
            responsableId: $registro->responsable_id,
            iniciadoEn: $registro->iniciado_en !== null ? DateTimeImmutable::createFromInterface($registro->iniciado_en) : null,
            finalizadoEn: $registro->finalizado_en !== null ? DateTimeImmutable::createFromInterface($registro->finalizado_en) : null,
            canceladoEn: $registro->cancelado_en !== null ? DateTimeImmutable::createFromInterface($registro->cancelado_en) : null,
            motivoCancelacion: $registro->motivo_cancelacion,
        );
    }
}

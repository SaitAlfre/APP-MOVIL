<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Auditoria\Auditoria as AuditoriaDominio;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Entregas\Entrega as EntregaDominio;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Entrega as EntregaEloquent;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentEntregaRepository implements EntregaRepositoryInterface
{
    public function __construct(
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function buscarPorId(int $id): ?EntregaDominio
    {
        $entrega = EntregaEloquent::query()->find($id);

        return $entrega !== null ? $this->aDominio($entrega) : null;
    }

    public function ultimasDelProveedor(int $proveedorId, int $limite): array
    {
        return EntregaEloquent::query()
            ->where('proveedor_id', $proveedorId)
            ->where('anulada', false)
            ->orderByDesc('registrado_en')
            ->limit($limite)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function deJornadaYProveedor(int $jornadaId, int $proveedorId): array
    {
        return EntregaEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->where('proveedor_id', $proveedorId)
            ->where('anulada', false)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function recientesPorJornada(int $jornadaId, int $limite): array
    {
        return EntregaEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->orderByDesc('registrado_en')
            ->limit($limite)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function resumenDelDia(int $jornadaId): array
    {
        $fila = EntregaEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->where('anulada', false)
            ->selectRaw('COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas')
            ->first();

        return [
            'litros' => (float) $fila->litros,
            'entregas' => (int) $fila->entregas,
        ];
    }

    public function sinControlCalidad(int $limite = 30): array
    {
        return EntregaEloquent::query()
            ->where('anulada', false)
            ->whereNotIn('id', function ($query) {
                $query->select('entrega_id')->from('controles_calidad');
            })
            ->orderByDesc('registrado_en')
            ->limit($limite)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function litrosPorProveedorEnRango(int $proveedorId, DateTimeImmutable $desde, DateTimeImmutable $hasta): float
    {
        return (float) EntregaEloquent::query()
            ->where('proveedor_id', $proveedorId)
            ->where('anulada', false)
            ->whereBetween('registrado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->sum('litros');
    }

    public function litrosPorDiaDelProveedor(int $proveedorId, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return EntregaEloquent::query()
            ->where('proveedor_id', $proveedorId)
            ->where('anulada', false)
            ->whereBetween('registrado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->selectRaw('DATE(registrado_en) as fecha, COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas')
            ->groupBy(DB::raw('DATE(registrado_en)'))
            ->orderBy('fecha')
            ->get()
            ->map(fn ($fila) => [
                'fecha' => (string) $fila->fecha,
                'litros' => (float) $fila->litros,
                'entregas' => (int) $fila->entregas,
            ])
            ->all();
    }

    public function resumenPorProveedores(array $proveedorIds, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        if ($proveedorIds === []) {
            return [];
        }

        return EntregaEloquent::query()
            ->where('anulada', false)
            ->whereIn('proveedor_id', $proveedorIds)
            ->whereBetween('registrado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->selectRaw('proveedor_id, COALESCE(SUM(litros), 0) as litros, MAX(registrado_en) as ultima')
            ->groupBy('proveedor_id')
            ->get()
            ->mapWithKeys(fn ($fila) => [
                (int) $fila->proveedor_id => [
                    'litros' => (float) $fila->litros,
                    'ultima' => $fila->ultima !== null ? (string) $fila->ultima : null,
                ],
            ])
            ->all();
    }

    public function resumenPorRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $fila = EntregaEloquent::query()
            ->where('anulada', false)
            ->whereBetween('registrado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->selectRaw('COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas')
            ->first();

        return [
            'litros' => (float) $fila->litros,
            'entregas' => (int) $fila->entregas,
        ];
    }

    public function litrosPorZonaEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return EntregaEloquent::query()
            ->where('anulada', false)
            ->whereBetween('registrado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->selectRaw('zona_id, COALESCE(SUM(litros), 0) as litros')
            ->groupBy('zona_id')
            ->orderByDesc('litros')
            ->get()
            ->map(fn ($fila) => ['zona_id' => (int) $fila->zona_id, 'litros' => (float) $fila->litros])
            ->all();
    }

    public function resumenParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        $fila = $this->consultaReporte($desde, $hasta, $zonaId)
            ->selectRaw('COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas, COALESCE(AVG(litros), 0) as promedio_litros, COUNT(DISTINCT proveedor_id) as proveedores, COALESCE(SUM(tachos), 0) as tachos')
            ->first();

        return [
            'litros' => (float) $fila->litros,
            'entregas' => (int) $fila->entregas,
            'promedio_litros' => (float) $fila->promedio_litros,
            'proveedores' => (int) $fila->proveedores,
            'tachos' => (int) $fila->tachos,
        ];
    }

    public function tendenciaParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        return $this->consultaReporte($desde, $hasta, $zonaId)
            ->selectRaw('DATE(registrado_en) as fecha, COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas')
            ->groupBy(DB::raw('DATE(registrado_en)'))
            ->orderBy('fecha')
            ->get()
            ->map(fn ($fila) => [
                'fecha' => (string) $fila->fecha,
                'litros' => (float) $fila->litros,
                'entregas' => (int) $fila->entregas,
            ])
            ->all();
    }

    public function zonasParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        return $this->consultaReporte($desde, $hasta, $zonaId)
            ->join('zonas', 'zonas.id', '=', 'entregas.zona_id')
            ->selectRaw('zonas.nombre as zona, COALESCE(SUM(entregas.litros), 0) as litros, COUNT(*) as entregas')
            ->groupBy('zonas.id', 'zonas.nombre')
            ->orderByDesc('litros')
            ->get()
            ->map(fn ($fila) => [
                'zona' => (string) $fila->zona,
                'litros' => (float) $fila->litros,
                'entregas' => (int) $fila->entregas,
            ])
            ->all();
    }

    public function acopiadoresParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        return $this->consultaReporte($desde, $hasta, $zonaId)
            ->join('usuarios', 'usuarios.id', '=', 'entregas.usuario_id')
            ->selectRaw('usuarios.nombres as acopiador, COALESCE(SUM(entregas.litros), 0) as litros, COUNT(*) as entregas')
            ->groupBy('usuarios.id', 'usuarios.nombres')
            ->orderByDesc('litros')
            ->get()
            ->map(fn ($fila) => [
                'acopiador' => (string) $fila->acopiador,
                'litros' => (float) $fila->litros,
                'entregas' => (int) $fila->entregas,
            ])
            ->all();
    }

    public function pendientesCalidadParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        $fila = $this->consultaReporte($desde, $hasta, $zonaId)
            ->whereNotIn('entregas.id', function ($query) {
                $query->select('entrega_id')->from('controles_calidad');
            })
            ->selectRaw('COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas')
            ->first();

        return [
            'litros' => (float) $fila->litros,
            'entregas' => (int) $fila->entregas,
        ];
    }

    public function entregasParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null, ?int $limite = 200): array
    {
        $consulta = $this->consultaReporte($desde, $hasta, $zonaId)
            ->join('proveedores', 'proveedores.id', '=', 'entregas.proveedor_id')
            ->join('zonas', 'zonas.id', '=', 'entregas.zona_id')
            ->join('vehiculos', 'vehiculos.id', '=', 'entregas.vehiculo_id')
            ->select([
                'entregas.registrado_en',
                'proveedores.codigo as proveedor_codigo',
                'proveedores.nombres as proveedor',
                'zonas.nombre as zona',
                'vehiculos.nombre as vehiculo',
                'vehiculos.placa',
                'entregas.litros',
                'entregas.tachos',
            ])
            ->orderByDesc('entregas.registrado_en');

        if ($limite !== null) {
            $consulta->limit($limite);
        }

        return $consulta->get()
            ->map(fn (EntregaEloquent $entrega) => [
                'fecha' => $entrega->registrado_en->format('Y-m-d H:i:s'),
                'proveedor_codigo' => (string) $entrega->proveedor_codigo,
                'proveedor' => (string) $entrega->proveedor,
                'zona' => (string) $entrega->zona,
                'vehiculo' => (string) $entrega->vehiculo,
                'placa' => (string) $entrega->placa,
                'litros' => (float) $entrega->litros,
                'tachos' => (int) $entrega->tachos,
            ])
            ->all();
    }

    public function litrosAprobadosPorVehiculoEnFecha(DateTimeImmutable $fecha): array
    {
        return EntregaEloquent::query()
            ->where('anulada', false)
            ->whereDate('registrado_en', $fecha->format('Y-m-d'))
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('controles_calidad')
                    ->whereColumn('controles_calidad.entrega_id', 'entregas.id')
                    ->whereIn('controles_calidad.resultado', ['aprobado', 'observado']);
            })
            ->selectRaw('vehiculo_id, COALESCE(SUM(litros), 0) as litros')
            ->groupBy('vehiculo_id')
            ->get()
            ->map(fn ($fila) => ['vehiculo_id' => (int) $fila->vehiculo_id, 'litros' => (float) $fila->litros])
            ->all();
    }

    public function recepcionPorVehiculoEnFecha(DateTimeImmutable $fecha): array
    {
        $aprobados = collect($this->litrosAprobadosPorVehiculoEnFecha($fecha))->keyBy('vehiculo_id');

        // Agregado en PHP (en vez de GROUP_CONCAT) para que la consulta funcione igual en MySQL
        // y en el sqlite en memoria de las pruebas, cuya sintaxis de concatenación difiere.
        $mermasPorVehiculo = DB::table('recepciones_acopio')
            ->join('jornadas', 'jornadas.id', '=', 'recepciones_acopio.jornada_id')
            ->whereDate('jornadas.fecha', $fecha->format('Y-m-d'))
            ->select(['jornadas.vehiculo_id', 'recepciones_acopio.litros_recolectados', 'recepciones_acopio.litros_medidos', 'recepciones_acopio.motivo_diferencia'])
            ->get()
            ->groupBy('vehiculo_id')
            ->map(fn ($grupo) => [
                'merma' => $grupo->sum(fn ($fila) => (float) $fila->litros_recolectados - (float) $fila->litros_medidos),
                'motivos' => $grupo->pluck('motivo_diferencia')->filter(fn ($motivo) => $motivo !== null && $motivo !== '')->unique()->implode('; '),
            ]);

        $filas = DB::table('entregas')
            ->join('vehiculos', 'vehiculos.id', '=', 'entregas.vehiculo_id')
            ->join('jornadas', 'jornadas.id', '=', 'entregas.jornada_id')
            ->join('usuarios', 'usuarios.id', '=', 'jornadas.usuario_id')
            ->where('entregas.anulada', false)
            ->whereDate('entregas.registrado_en', $fecha->format('Y-m-d'))
            ->selectRaw('vehiculos.id as vehiculo_id, vehiculos.nombre, vehiculos.placa, usuarios.id as usuario_id, usuarios.nombres, SUM(entregas.litros) as litros')
            ->groupBy('vehiculos.id', 'vehiculos.nombre', 'vehiculos.placa', 'usuarios.id', 'usuarios.nombres')
            ->orderBy('vehiculos.nombre')->orderBy('usuarios.nombres')->get();

        return $filas->groupBy('vehiculo_id')->map(function ($grupo, $id) use ($aprobados, $mermasPorVehiculo) {
            $vehiculo = $grupo->first();
            $litros = (float) ($aprobados->get($id)['litros'] ?? 0);
            $mermaFila = $mermasPorVehiculo->get($id);
            // Un excedente medido en planta (diferencia negativa aquí) nunca incrementa el saldo disponible.
            $merma = max(0.0, round((float) ($mermaFila['merma'] ?? 0), 2));

            return [
                'vehiculoId' => (int) $id,
                'vehiculoNombre' => $vehiculo->nombre,
                'placa' => $vehiculo->placa,
                'acopiadores' => $grupo->map(fn ($fila) => $fila->nombres.' ('.number_format((float) $fila->litros, 1).' L)')->implode(', '),
                'litrosRecolectados' => (float) $grupo->sum('litros'),
                'litrosAprobados' => $litros,
                'merma' => $merma,
                'motivo' => $mermaFila['motivos'] ?? '',
                'litros' => max(0, round($litros - $merma, 2)),
            ];
        })->values()->all();
    }

    public function litrosAprobadosPorDiaEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return EntregaEloquent::query()
            ->where('anulada', false)
            ->whereBetween('registrado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('controles_calidad')
                    ->whereColumn('controles_calidad.entrega_id', 'entregas.id')
                    ->whereIn('controles_calidad.resultado', ['aprobado', 'observado']);
            })
            ->selectRaw('DATE(registrado_en) as fecha, COALESCE(SUM(litros), 0) as litros')
            ->groupBy(DB::raw('DATE(registrado_en)'))
            ->orderByDesc('fecha')
            ->get()
            ->map(fn ($fila) => ['fecha' => (string) $fila->fecha, 'litros' => (float) $fila->litros])
            ->all();
    }

    public function litrosAprobadosTotal(): float
    {
        return (float) EntregaEloquent::query()
            ->where('anulada', false)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('controles_calidad')
                    ->whereColumn('controles_calidad.entrega_id', 'entregas.id')
                    ->whereIn('controles_calidad.resultado', ['aprobado', 'observado']);
            })
            ->sum('litros');
    }

    private function consultaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId): Builder
    {
        return EntregaEloquent::query()
            ->where('entregas.anulada', false)
            ->whereBetween('entregas.registrado_en', [
                $desde->format('Y-m-d 00:00:00'),
                $hasta->format('Y-m-d 23:59:59'),
            ])
            ->when($zonaId !== null, fn (Builder $consulta) => $consulta->where('entregas.zona_id', $zonaId));
    }

    public function registrar(EntregaDominio $entrega, AuditoriaDominio $auditoria): EntregaDominio
    {
        return DB::transaction(function () use ($entrega, $auditoria) {
            $registro = $this->crearRegistro($entrega);

            $this->auditorias->registrar(new AuditoriaDominio(
                id: null,
                entidad: $auditoria->entidad,
                entidadId: $registro->id,
                accion: $auditoria->accion,
                valorAntes: $auditoria->valorAntes,
                valorDespues: $auditoria->valorDespues,
                motivo: $auditoria->motivo,
                usuarioId: $auditoria->usuarioId,
                ocurridoEn: $auditoria->ocurridoEn,
            ));

            return $this->aDominio($registro);
        });
    }

    public function corregir(int $id, float $litros, int $tachos, ?string $observaciones, DateTimeImmutable $ahora, AuditoriaDominio $auditoria): void
    {
        DB::transaction(function () use ($id, $litros, $tachos, $observaciones, $auditoria) {
            EntregaEloquent::query()->whereKey($id)->update([
                'litros' => $litros,
                'tachos' => $tachos,
                'observaciones' => $observaciones,
            ]);

            $this->auditorias->registrar($auditoria);
        });
    }

    public function anular(int $id, DateTimeImmutable $ahora, AuditoriaDominio $auditoria): void
    {
        DB::transaction(function () use ($id, $auditoria) {
            EntregaEloquent::query()->whereKey($id)->update(['anulada' => true]);

            $this->auditorias->registrar($auditoria);
        });
    }

    private function crearRegistro(EntregaDominio $entrega): EntregaEloquent
    {
        return EntregaEloquent::query()->create([
            'jornada_id' => $entrega->jornadaId,
            'proveedor_id' => $entrega->proveedorId,
            'usuario_id' => $entrega->usuarioId,
            'zona_id' => $entrega->zonaId,
            'vehiculo_id' => $entrega->vehiculoId,
            'litros' => $entrega->litros,
            'tachos' => $entrega->tachos,
            'observaciones' => $entrega->observaciones,
            'registrado_en' => $entrega->registradoEn,
            'lote_id' => $entrega->loteId,
            'anulada' => false,
        ]);
    }

    private function aDominio(EntregaEloquent $entrega): EntregaDominio
    {
        return EntregaDominio::reconstruir(
            id: $entrega->id,
            jornadaId: $entrega->jornada_id,
            proveedorId: $entrega->proveedor_id,
            usuarioId: $entrega->usuario_id,
            zonaId: $entrega->zona_id,
            vehiculoId: $entrega->vehiculo_id,
            litros: (float) $entrega->litros,
            tachos: $entrega->tachos,
            observaciones: $entrega->observaciones,
            registradoEn: DateTimeImmutable::createFromInterface($entrega->registrado_en),
            loteId: $entrega->lote_id,
            anulada: $entrega->anulada,
        );
    }
}

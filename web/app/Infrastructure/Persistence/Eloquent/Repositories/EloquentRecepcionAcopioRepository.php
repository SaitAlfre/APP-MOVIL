<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Auditoria\Auditoria as AuditoriaDominio;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Recepcion\Exceptions\RecepcionInvalidaException;
use App\Domain\Recepcion\RecepcionAcopio as RecepcionAcopioDominio;
use App\Domain\Recepcion\RecepcionAcopioRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Jornada as JornadaEloquent;
use App\Infrastructure\Persistence\Eloquent\RecepcionAcopio as RecepcionAcopioEloquent;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentRecepcionAcopioRepository implements RecepcionAcopioRepositoryInterface
{
    public function __construct(
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function porJornada(int $jornadaId): ?RecepcionAcopioDominio
    {
        $registro = RecepcionAcopioEloquent::query()->where('jornada_id', $jornadaId)->first();

        return $registro !== null ? $this->aDominio($registro) : null;
    }

    public function porJornadas(array $jornadaIds): array
    {
        if ($jornadaIds === []) {
            return [];
        }

        return RecepcionAcopioEloquent::query()
            ->whereIn('jornada_id', $jornadaIds)
            ->get()
            ->mapWithKeys(fn (RecepcionAcopioEloquent $registro) => [(int) $registro->jornada_id => $this->aDominio($registro)])
            ->all();
    }

    public function listarJornadas(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?string $estado = null): array
    {
        $jornadas = DB::table('jornadas')
            ->join('usuarios', 'usuarios.id', '=', 'jornadas.usuario_id')
            ->join('vehiculos', 'vehiculos.id', '=', 'jornadas.vehiculo_id')
            ->join('zonas', 'zonas.id', '=', 'jornadas.zona_id')
            ->whereDate('jornadas.fecha', '>=', $desde->format('Y-m-d'))
            ->whereDate('jornadas.fecha', '<=', $hasta->format('Y-m-d'))
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('entregas')
                    ->whereColumn('entregas.jornada_id', 'jornadas.id')
                    ->where('entregas.anulada', false);
            })
            ->select([
                'jornadas.id as jornada_id', 'jornadas.fecha',
                'usuarios.nombres as acopiador',
                'vehiculos.nombre as vehiculo_nombre', 'vehiculos.placa',
                'zonas.nombre as zona',
            ])
            ->orderByDesc('jornadas.fecha')
            ->orderBy('vehiculos.nombre')
            ->get();

        if ($jornadas->isEmpty()) {
            return [];
        }

        $jornadaIds = $jornadas->pluck('jornada_id');

        $litrosPorJornada = DB::table('entregas')
            ->whereIn('jornada_id', $jornadaIds)
            ->where('anulada', false)
            ->selectRaw('jornada_id, SUM(litros) as litros')
            ->groupBy('jornada_id')
            ->pluck('litros', 'jornada_id');

        $calidadPorJornada = DB::table('entregas')
            ->leftJoin('controles_calidad', 'controles_calidad.entrega_id', '=', 'entregas.id')
            ->whereIn('entregas.jornada_id', $jornadaIds)
            ->where('entregas.anulada', false)
            ->selectRaw(<<<'SQL'
                entregas.jornada_id,
                SUM(CASE WHEN controles_calidad.id IS NULL THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN controles_calidad.resultado = 'aprobado' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN controles_calidad.resultado = 'observado' THEN 1 ELSE 0 END) as observadas,
                SUM(CASE WHEN controles_calidad.resultado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
                SQL)
            ->groupBy('entregas.jornada_id')
            ->get()
            ->keyBy('jornada_id');

        $recepciones = RecepcionAcopioEloquent::query()->whereIn('jornada_id', $jornadaIds)->get()->keyBy('jornada_id');

        $filas = $jornadas->map(function ($jornada) use ($litrosPorJornada, $calidadPorJornada, $recepciones) {
            $recepcion = $recepciones->get($jornada->jornada_id);
            $calidad = $calidadPorJornada->get($jornada->jornada_id);

            return [
                'jornadaId' => (int) $jornada->jornada_id,
                'fecha' => new DateTimeImmutable($jornada->fecha),
                'acopiador' => $jornada->acopiador,
                'vehiculoNombre' => $jornada->vehiculo_nombre,
                'placa' => $jornada->placa,
                'zona' => $jornada->zona,
                'litrosRecolectados' => (float) ($litrosPorJornada->get($jornada->jornada_id) ?? 0),
                'recepcion' => $recepcion !== null ? $this->aDominio($recepcion) : null,
                'calidad' => [
                    'pendientes' => (int) ($calidad->pendientes ?? 0),
                    'aprobadas' => (int) ($calidad->aprobadas ?? 0),
                    'observadas' => (int) ($calidad->observadas ?? 0),
                    'rechazadas' => (int) ($calidad->rechazadas ?? 0),
                ],
            ];
        });

        if ($estado === 'pendiente') {
            $filas = $filas->filter(fn (array $fila) => $fila['recepcion'] === null);
        } elseif ($estado === 'registrada') {
            $filas = $filas->filter(fn (array $fila) => $fila['recepcion'] !== null);
        }

        return $filas->values()->all();
    }

    public function resumenEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        $fila = DB::table('recepciones_acopio')
            ->join('jornadas', 'jornadas.id', '=', 'recepciones_acopio.jornada_id')
            ->whereDate('jornadas.fecha', '>=', $desde->format('Y-m-d'))
            ->whereDate('jornadas.fecha', '<=', $hasta->format('Y-m-d'))
            ->when($zonaId !== null, fn ($query) => $query->where('jornadas.zona_id', $zonaId))
            ->selectRaw('COALESCE(SUM(recepciones_acopio.litros_recolectados), 0) as recolectados, COALESCE(SUM(recepciones_acopio.litros_medidos), 0) as medidos')
            ->first();

        $recolectados = (float) $fila->recolectados;
        $medidos = (float) $fila->medidos;

        return [
            'litrosRecolectados' => $recolectados,
            'litrosMedidos' => $medidos,
            'merma' => max(0.0, round($recolectados - $medidos, 2)),
        ];
    }

    public function mermasPorVehiculoEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array
    {
        return DB::table('recepciones_acopio')
            ->join('jornadas', 'jornadas.id', '=', 'recepciones_acopio.jornada_id')
            ->join('vehiculos', 'vehiculos.id', '=', 'jornadas.vehiculo_id')
            ->whereDate('jornadas.fecha', '>=', $desde->format('Y-m-d'))
            ->whereDate('jornadas.fecha', '<=', $hasta->format('Y-m-d'))
            ->when($zonaId !== null, fn ($query) => $query->where('jornadas.zona_id', $zonaId))
            ->selectRaw('vehiculos.nombre as vehiculo, vehiculos.placa, COALESCE(SUM(recepciones_acopio.litros_recolectados), 0) as recolectados, COALESCE(SUM(recepciones_acopio.litros_recolectados - recepciones_acopio.litros_medidos), 0) as merma')
            ->groupBy('vehiculos.id', 'vehiculos.nombre', 'vehiculos.placa')
            ->orderByDesc('merma')
            ->get()
            ->map(function ($fila) {
                $recolectados = (float) $fila->recolectados;
                $merma = max(0.0, round((float) $fila->merma, 2));

                return [
                    'vehiculo' => (string) $fila->vehiculo,
                    'placa' => (string) $fila->placa,
                    'litrosRecolectados' => $recolectados,
                    'merma' => $merma,
                    'mermaPorcentaje' => $recolectados > 0.0 ? round(($merma / $recolectados) * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    public function registrarLlegada(RecepcionAcopioDominio $recepcion, AuditoriaDominio $auditoria): RecepcionAcopioDominio
    {
        return DB::transaction(function () use ($recepcion, $auditoria) {
            $jornada = JornadaEloquent::query()->whereKey($recepcion->jornadaId)->lockForUpdate()->firstOrFail();

            $tieneAsignacion = DB::table('lotes_produccion')
                ->whereDate('fecha', $jornada->fecha)
                ->where('estado', '!=', 'cancelado')
                ->exists();

            if ($tieneAsignacion) {
                throw RecepcionInvalidaException::diaYaProcesado();
            }

            $anterior = RecepcionAcopioEloquent::query()->where('jornada_id', $recepcion->jornadaId)->first();

            $datos = [
                'jornada_id' => $recepcion->jornadaId,
                'llegada_en' => $recepcion->llegadaEn,
                'litros_recolectados' => $recepcion->litrosRecolectados,
                'litros_medidos' => $recepcion->litrosMedidos,
                'motivo_diferencia' => $recepcion->motivoDiferencia,
                'observaciones' => $recepcion->observaciones,
                'usuario_id' => $recepcion->usuarioId,
            ];

            if ($anterior !== null) {
                $anterior->update($datos);
                $registro = $anterior;
            } else {
                $registro = RecepcionAcopioEloquent::query()->create($datos);
            }

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

    private function aDominio(RecepcionAcopioEloquent $registro): RecepcionAcopioDominio
    {
        return RecepcionAcopioDominio::reconstruir(
            id: $registro->id,
            jornadaId: $registro->jornada_id,
            llegadaEn: DateTimeImmutable::createFromInterface($registro->llegada_en),
            litrosRecolectados: (float) $registro->litros_recolectados,
            litrosMedidos: (float) $registro->litros_medidos,
            motivoDiferencia: $registro->motivo_diferencia,
            observaciones: $registro->observaciones,
            usuarioId: $registro->usuario_id,
        );
    }
}

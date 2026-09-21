<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Jornadas\Jornada as JornadaDominio;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Jornada as JornadaEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentJornadaRepository implements JornadaRepositoryInterface
{
    public function paginarTodas(int $porPagina = 20): LengthAwarePaginator
    {
        return JornadaEloquent::query()
            ->orderByDesc('abierta_en')
            ->paginate($porPagina)
            ->through(fn (JornadaEloquent $jornada) => $this->aDominio($jornada));
    }

    public function buscarPorId(int $id): ?JornadaDominio
    {
        $jornada = JornadaEloquent::query()->find($id);

        return $jornada !== null ? $this->aDominio($jornada) : null;
    }

    public function abiertas(int $limite = 10): array
    {
        return JornadaEloquent::query()
            ->whereNull('cerrada_en')
            ->orderByDesc('abierta_en')
            ->limit($limite)
            ->get()
            ->map(fn (JornadaEloquent $jornada) => $this->aDominio($jornada))
            ->all();
    }

    public function obtenerAbiertaPorUsuarioYFecha(int $usuarioId, DateTimeImmutable $fecha): ?JornadaDominio
    {
        $jornada = JornadaEloquent::query()
            ->where('usuario_id', $usuarioId)
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->whereNull('cerrada_en')
            ->first();

        return $jornada !== null ? $this->aDominio($jornada) : null;
    }

    public function obtenerAbiertaPorZona(int $zonaId): ?JornadaDominio
    {
        $jornada = JornadaEloquent::query()
            ->where('zona_id', $zonaId)
            ->whereNull('cerrada_en')
            ->first();

        return $jornada !== null ? $this->aDominio($jornada) : null;
    }

    public function insertar(JornadaDominio $jornada): JornadaDominio
    {
        $registro = JornadaEloquent::query()->create([
            'usuario_id' => $jornada->usuarioId,
            'zona_id' => $jornada->zonaId,
            'vehiculo_id' => $jornada->vehiculoId,
            'fecha' => $jornada->fecha->format('Y-m-d'),
            'abierta_en' => $jornada->abiertaEn,
            'cerrada_en' => null,
            'seguimiento_activo' => false,
        ]);

        return $this->aDominio($registro);
    }

    public function cerrar(int $id, DateTimeImmutable $cerradaEn): void
    {
        JornadaEloquent::query()->findOrFail($id)->update(['cerrada_en' => $cerradaEn]);
    }

    public function actualizarSeguimientoActivo(int $id, bool $activo): void
    {
        JornadaEloquent::query()->whereKey($id)->update(['seguimiento_activo' => $activo]);
    }

    private function aDominio(JornadaEloquent $jornada): JornadaDominio
    {
        return new JornadaDominio(
            id: $jornada->id,
            usuarioId: $jornada->usuario_id,
            zonaId: $jornada->zona_id,
            vehiculoId: $jornada->vehiculo_id,
            fecha: DateTimeImmutable::createFromInterface($jornada->fecha),
            abiertaEn: DateTimeImmutable::createFromInterface($jornada->abierta_en),
            cerradaEn: $jornada->cerrada_en !== null ? DateTimeImmutable::createFromInterface($jornada->cerrada_en) : null,
            seguimientoActivo: $jornada->seguimiento_activo,
        );
    }
}

<?php

namespace App\Domain\Jornadas;

use Illuminate\Pagination\LengthAwarePaginator;

interface JornadaRepositoryInterface
{
    public function paginarTodas(int $porPagina = 20): LengthAwarePaginator;

    public function buscarPorId(int $id): ?Jornada;

    public function obtenerAbiertaPorUsuarioYFecha(int $usuarioId, \DateTimeImmutable $fecha): ?Jornada;

    public function obtenerAbiertaPorZona(int $zonaId): ?Jornada;

    public function insertar(Jornada $jornada): Jornada;

    public function cerrar(int $id, \DateTimeImmutable $cerradaEn): void;

    public function actualizarSeguimientoActivo(int $id, bool $activo): void;
}

<?php

namespace App\Domain\Jornadas;

interface JornadaRepositoryInterface
{
    public function buscarPorId(int $id): ?Jornada;

    public function obtenerAbiertaPorUsuarioYFecha(int $usuarioId, \DateTimeImmutable $fecha): ?Jornada;

    public function obtenerAbiertaPorZona(int $zonaId): ?Jornada;

    public function insertar(Jornada $jornada): Jornada;

    public function cerrar(int $id, \DateTimeImmutable $cerradaEn): void;

    public function actualizarSeguimientoActivo(int $id, bool $activo): void;
}

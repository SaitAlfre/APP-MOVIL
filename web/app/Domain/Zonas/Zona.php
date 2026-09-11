<?php

namespace App\Domain\Zonas;

use App\Domain\Zonas\Exceptions\ZonaInvalidaException;

final class Zona
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly bool $activo,
    ) {}

    public static function crear(string $nombre, bool $activo = true): self
    {
        self::validar($nombre);

        return new self(id: null, nombre: trim($nombre), activo: $activo);
    }

    public static function reconstruir(int $id, string $nombre, bool $activo): self
    {
        return new self($id, $nombre, $activo);
    }

    public function actualizar(string $nombre): self
    {
        self::validar($nombre);

        return new self($this->id, trim($nombre), $this->activo);
    }

    public function conEstado(bool $activo): self
    {
        return new self($this->id, $this->nombre, $activo);
    }

    private static function validar(string $nombre): void
    {
        if (trim($nombre) === '') {
            throw ZonaInvalidaException::nombreVacio();
        }
    }
}

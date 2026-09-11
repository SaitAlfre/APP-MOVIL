<?php

namespace App\Domain\Vehiculos;

use App\Domain\Vehiculos\Exceptions\VehiculoInvalidoException;

final class Vehiculo
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $placa,
        public readonly bool $activo,
    ) {}

    public static function crear(string $nombre, string $placa, bool $activo = true): self
    {
        self::validar($nombre, $placa);

        return new self(id: null, nombre: trim($nombre), placa: strtoupper(trim($placa)), activo: $activo);
    }

    public static function reconstruir(int $id, string $nombre, string $placa, bool $activo): self
    {
        return new self($id, $nombre, $placa, $activo);
    }

    public function actualizar(string $nombre, string $placa): self
    {
        self::validar($nombre, $placa);

        return new self($this->id, trim($nombre), strtoupper(trim($placa)), $this->activo);
    }

    public function conEstado(bool $activo): self
    {
        return new self($this->id, $this->nombre, $this->placa, $activo);
    }

    private static function validar(string $nombre, string $placa): void
    {
        if (trim($nombre) === '') {
            throw VehiculoInvalidoException::nombreVacio();
        }

        if (trim($placa) === '') {
            throw VehiculoInvalidoException::placaVacia();
        }
    }
}

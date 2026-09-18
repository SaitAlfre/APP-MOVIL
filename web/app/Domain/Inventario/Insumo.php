<?php

namespace App\Domain\Inventario;

use App\Domain\Inventario\Exceptions\InsumoInvalidoException;

final class Insumo
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $unidad,
        public readonly float $stockMinimo,
        public readonly float $existencia,
        public readonly float $reservado,
        public readonly bool $activo,
    ) {}

    public static function crear(string $nombre, string $unidad, float $stockMinimo): self
    {
        self::validar($nombre, $unidad, $stockMinimo);

        return new self(
            id: null,
            nombre: trim($nombre),
            unidad: trim($unidad),
            stockMinimo: $stockMinimo,
            existencia: 0.0,
            reservado: 0.0,
            activo: true,
        );
    }

    public static function reconstruir(
        int $id,
        string $nombre,
        string $unidad,
        float $stockMinimo,
        float $existencia,
        float $reservado,
        bool $activo,
    ): self {
        return new self($id, $nombre, $unidad, $stockMinimo, $existencia, $reservado, $activo);
    }

    public function disponible(): float
    {
        return round($this->existencia - $this->reservado, 3);
    }

    public function bajoMinimo(): bool
    {
        return $this->disponible() < $this->stockMinimo;
    }

    private static function validar(string $nombre, string $unidad, float $stockMinimo): void
    {
        if (trim($nombre) === '') {
            throw InsumoInvalidoException::nombreVacio();
        }

        if (trim($unidad) === '') {
            throw InsumoInvalidoException::unidadVacia();
        }

        if ($stockMinimo < 0) {
            throw InsumoInvalidoException::stockMinimoInvalido();
        }
    }
}

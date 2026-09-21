<?php

namespace App\Domain\Productos;

use App\Domain\Productos\Exceptions\ProductoInvalidoException;

final class Producto
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $presentacion,
        public readonly string $unidadProduccion,
        public readonly ?float $contenidoPorUnidad,
        public readonly ?string $unidadContenido,
        public readonly float $litrosPorUnidad,
        public readonly ?string $otrosInsumos,
        public readonly float $existencia,
        public readonly bool $activo,
    ) {}

    public static function crear(
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
        float $litrosPorUnidad,
        ?string $otrosInsumos,
    ): self {
        self::validar($nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad, $litrosPorUnidad);

        return new self(
            id: null,
            nombre: trim($nombre),
            presentacion: trim($presentacion),
            unidadProduccion: trim($unidadProduccion),
            contenidoPorUnidad: $contenidoPorUnidad,
            unidadContenido: $unidadContenido !== null && trim($unidadContenido) !== '' ? trim($unidadContenido) : null,
            litrosPorUnidad: $litrosPorUnidad,
            otrosInsumos: $otrosInsumos !== null && trim($otrosInsumos) !== '' ? trim($otrosInsumos) : null,
            existencia: 0.0,
            activo: true,
        );
    }

    public static function reconstruir(
        int $id,
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
        float $litrosPorUnidad,
        ?string $otrosInsumos,
        float $existencia,
        bool $activo,
    ): self {
        return new self($id, $nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad, $unidadContenido, $litrosPorUnidad, $otrosInsumos, $existencia, $activo);
    }

    public function conDatosActualizados(
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
        float $litrosPorUnidad,
        ?string $otrosInsumos,
    ): self {
        self::validar($nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad, $litrosPorUnidad);

        return new self(
            id: $this->id,
            nombre: trim($nombre),
            presentacion: trim($presentacion),
            unidadProduccion: trim($unidadProduccion),
            contenidoPorUnidad: $contenidoPorUnidad,
            unidadContenido: $unidadContenido !== null && trim($unidadContenido) !== '' ? trim($unidadContenido) : null,
            litrosPorUnidad: $litrosPorUnidad,
            otrosInsumos: $otrosInsumos !== null && trim($otrosInsumos) !== '' ? trim($otrosInsumos) : null,
            existencia: $this->existencia,
            activo: $this->activo,
        );
    }

    private static function validar(string $nombre, string $presentacion, string $unidadProduccion, ?float $contenidoPorUnidad, float $litrosPorUnidad): void
    {
        if (trim($nombre) === '') {
            throw ProductoInvalidoException::nombreVacio();
        }

        if (trim($presentacion) === '') {
            throw ProductoInvalidoException::presentacionVacia();
        }

        if (trim($unidadProduccion) === '') {
            throw ProductoInvalidoException::unidadProduccionVacia();
        }

        if ($contenidoPorUnidad !== null && $contenidoPorUnidad <= 0.0) {
            throw ProductoInvalidoException::contenidoPorUnidadInvalido();
        }

        if ($litrosPorUnidad <= 0.0) {
            throw ProductoInvalidoException::litrosPorUnidadInvalido();
        }
    }
}

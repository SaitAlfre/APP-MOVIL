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
        public readonly float $existencia,
        public readonly bool $activo,
        public readonly ?int $recetaActivaId,
    ) {}

    public static function crear(
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
    ): self {
        self::validar($nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad);

        return new self(
            id: null,
            nombre: trim($nombre),
            presentacion: trim($presentacion),
            unidadProduccion: trim($unidadProduccion),
            contenidoPorUnidad: $contenidoPorUnidad,
            unidadContenido: $unidadContenido !== null && trim($unidadContenido) !== '' ? trim($unidadContenido) : null,
            existencia: 0.0,
            activo: true,
            recetaActivaId: null,
        );
    }

    public static function reconstruir(
        int $id,
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
        float $existencia,
        bool $activo,
        ?int $recetaActivaId,
    ): self {
        return new self($id, $nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad, $unidadContenido, $existencia, $activo, $recetaActivaId);
    }

    public function conDatosActualizados(
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
    ): self {
        self::validar($nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad);

        return new self(
            id: $this->id,
            nombre: trim($nombre),
            presentacion: trim($presentacion),
            unidadProduccion: trim($unidadProduccion),
            contenidoPorUnidad: $contenidoPorUnidad,
            unidadContenido: $unidadContenido !== null && trim($unidadContenido) !== '' ? trim($unidadContenido) : null,
            existencia: $this->existencia,
            activo: $this->activo,
            recetaActivaId: $this->recetaActivaId,
        );
    }

    public function tieneRecetaActiva(): bool
    {
        return $this->recetaActivaId !== null;
    }

    private static function validar(string $nombre, string $presentacion, string $unidadProduccion, ?float $contenidoPorUnidad): void
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
    }
}

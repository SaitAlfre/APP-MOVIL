<?php

namespace App\Domain\Recetas;

use App\Domain\Recetas\Exceptions\RecetaInvalidaException;

final class Receta
{
    /** @param list<RecetaIngrediente> $ingredientes */
    private function __construct(
        public readonly ?int $id,
        public readonly int $productoId,
        public readonly string $nombre,
        public readonly int $version,
        public readonly float $rendimientoBase,
        public readonly string $rendimientoUnidad,
        public readonly ?string $observaciones,
        public readonly EstadoReceta $estado,
        public readonly int $creadoPorUsuarioId,
        public readonly array $ingredientes,
    ) {}

    /** @param list<RecetaIngrediente> $ingredientes */
    public static function crear(
        int $productoId,
        string $nombre,
        int $version,
        float $rendimientoBase,
        string $rendimientoUnidad,
        ?string $observaciones,
        int $creadoPorUsuarioId,
        array $ingredientes,
    ): self {
        self::validar($nombre, $rendimientoBase, $rendimientoUnidad, $ingredientes);

        return new self(
            id: null,
            productoId: $productoId,
            nombre: trim($nombre),
            version: $version,
            rendimientoBase: $rendimientoBase,
            rendimientoUnidad: trim($rendimientoUnidad),
            observaciones: $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
            estado: EstadoReceta::Borrador,
            creadoPorUsuarioId: $creadoPorUsuarioId,
            ingredientes: $ingredientes,
        );
    }

    /** @param list<RecetaIngrediente> $ingredientes */
    public static function reconstruir(
        int $id,
        int $productoId,
        string $nombre,
        int $version,
        float $rendimientoBase,
        string $rendimientoUnidad,
        ?string $observaciones,
        EstadoReceta $estado,
        int $creadoPorUsuarioId,
        array $ingredientes,
    ): self {
        return new self($id, $productoId, $nombre, $version, $rendimientoBase, $rendimientoUnidad, $observaciones, $estado, $creadoPorUsuarioId, $ingredientes);
    }

    /**
     * Genera la siguiente versión (borrador) de esta receta, partiendo de los mismos
     * datos, para editar sin alterar la versión ya utilizada o activa.
     *
     * @param  list<RecetaIngrediente>  $ingredientes
     */
    public function nuevaVersion(
        string $nombre,
        float $rendimientoBase,
        string $rendimientoUnidad,
        ?string $observaciones,
        array $ingredientes,
        int $creadoPorUsuarioId,
    ): self {
        return self::crear(
            productoId: $this->productoId,
            nombre: $nombre,
            version: $this->version + 1,
            rendimientoBase: $rendimientoBase,
            rendimientoUnidad: $rendimientoUnidad,
            observaciones: $observaciones,
            creadoPorUsuarioId: $creadoPorUsuarioId,
            ingredientes: $ingredientes,
        );
    }

    /**
     * Actualiza esta misma versión en sitio. Solo válido mientras esté en borrador y no
     * se haya usado en ningún lote; de lo contrario debe crearse una nueva versión.
     *
     * @param  list<RecetaIngrediente>  $ingredientes
     */
    public function conDatosActualizados(
        string $nombre,
        float $rendimientoBase,
        string $rendimientoUnidad,
        ?string $observaciones,
        array $ingredientes,
    ): self {
        if (! $this->editableEnSitio()) {
            throw RecetaInvalidaException::noEditable();
        }

        self::validar($nombre, $rendimientoBase, $rendimientoUnidad, $ingredientes);

        return new self(
            $this->id,
            $this->productoId,
            trim($nombre),
            $this->version,
            $rendimientoBase,
            trim($rendimientoUnidad),
            $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
            $this->estado,
            $this->creadoPorUsuarioId,
            $ingredientes,
        );
    }

    public function activar(): self
    {
        if ($this->estado === EstadoReceta::Archivada) {
            throw RecetaInvalidaException::noPuedeActivarseArchivada();
        }

        return new self($this->id, $this->productoId, $this->nombre, $this->version, $this->rendimientoBase, $this->rendimientoUnidad, $this->observaciones, EstadoReceta::Activa, $this->creadoPorUsuarioId, $this->ingredientes);
    }

    public function archivar(): self
    {
        return new self($this->id, $this->productoId, $this->nombre, $this->version, $this->rendimientoBase, $this->rendimientoUnidad, $this->observaciones, EstadoReceta::Archivada, $this->creadoPorUsuarioId, $this->ingredientes);
    }

    public function editableEnSitio(): bool
    {
        return $this->estado === EstadoReceta::Borrador;
    }

    /** @param list<RecetaIngrediente> $ingredientes */
    private static function validar(string $nombre, float $rendimientoBase, string $rendimientoUnidad, array $ingredientes): void
    {
        if (trim($nombre) === '') {
            throw RecetaInvalidaException::nombreVacio();
        }

        if ($rendimientoBase <= 0.0) {
            throw RecetaInvalidaException::rendimientoInvalido();
        }

        if (trim($rendimientoUnidad) === '') {
            throw RecetaInvalidaException::rendimientoUnidadVacia();
        }

        if ($ingredientes === []) {
            throw RecetaInvalidaException::sinIngredientes();
        }

        $insumoIds = array_map(fn (RecetaIngrediente $i) => $i->insumoId, $ingredientes);

        if (count($insumoIds) !== count(array_unique($insumoIds))) {
            throw RecetaInvalidaException::ingredienteDuplicado();
        }
    }
}

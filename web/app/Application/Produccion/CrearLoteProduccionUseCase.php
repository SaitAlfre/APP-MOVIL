<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Domain\Recetas\RecetaRepositoryInterface;
use DateTimeImmutable;

final class CrearLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
        private readonly ProductoRepositoryInterface $productos,
        private readonly RecetaRepositoryInterface $recetas,
        private readonly CalculaNecesidadesReceta $calculaNecesidades,
    ) {}

    public function ejecutar(
        string $codigo,
        int $productoId,
        float $cantidadPlanificada,
        DateTimeImmutable $fechaPlanificada,
        ?string $observaciones,
        int $responsableUsuarioId,
    ): LoteProduccion {
        $producto = $this->productos->buscarPorId($productoId);

        if ($producto === null) {
            throw ProductoInvalidoException::noExiste();
        }

        if (! $producto->activo) {
            throw LoteProduccionInvalidoException::productoInactivo();
        }

        if ($producto->recetaActivaId === null) {
            throw LoteProduccionInvalidoException::sinRecetaActiva();
        }

        $receta = $this->recetas->buscarPorId($producto->recetaActivaId);

        if ($receta === null) {
            throw LoteProduccionInvalidoException::sinRecetaActiva();
        }

        if ($this->lotes->buscarPorCodigo(trim($codigo)) !== null) {
            throw LoteProduccionInvalidoException::codigoDuplicado();
        }

        $necesidades = $this->calculaNecesidades->ejecutar($receta, $cantidadPlanificada);

        $lote = LoteProduccion::crear(
            codigo: $codigo,
            productoId: $producto->id,
            recetaId: $receta->id,
            cantidadPlanificada: $cantidadPlanificada,
            unidad: $producto->unidadProduccion,
            responsableUsuarioId: $responsableUsuarioId,
            fechaPlanificada: $fechaPlanificada,
            observaciones: $observaciones,
        );

        return $this->lotes->crear($lote, $necesidades);
    }
}

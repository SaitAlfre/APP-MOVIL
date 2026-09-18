<?php

namespace App\Application\Produccion;

use App\Domain\Inventario\ConversionUnidades;
use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Produccion\NecesidadInsumo;
use App\Domain\Recetas\Receta;
use App\Domain\Recetas\RecetaIngrediente;

/**
 * cantidad necesaria = cantidad de la receta × cantidad planificada / rendimiento base,
 * convertida a la unidad propia de cada insumo para poder compararla contra su existencia.
 */
final class CalculaNecesidadesReceta
{
    public function __construct(
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    /** @return list<NecesidadInsumo> */
    public function ejecutar(Receta $receta, float $cantidadPlanificada): array
    {
        return array_map(
            fn (RecetaIngrediente $ingrediente) => $this->calcularUno($receta, $ingrediente, $cantidadPlanificada),
            $receta->ingredientes,
        );
    }

    private function calcularUno(Receta $receta, RecetaIngrediente $ingrediente, float $cantidadPlanificada): NecesidadInsumo
    {
        $insumo = $this->insumos->buscarPorId($ingrediente->insumoId);

        if ($insumo === null || $insumo->id === null) {
            throw InsumoInvalidoException::noExiste();
        }

        $cantidadEnUnidadReceta = $ingrediente->cantidad * $cantidadPlanificada / $receta->rendimientoBase;
        $cantidadEnUnidadInsumo = ConversionUnidades::convertir($cantidadEnUnidadReceta, $ingrediente->unidad, $insumo->unidad);

        return new NecesidadInsumo($insumo->id, $insumo->unidad, round($cantidadEnUnidadInsumo, 3));
    }
}

<?php

namespace App\Application\Recetas;

use App\Domain\Inventario\ConversionUnidades;
use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Recetas\RecetaIngrediente;

/**
 * Valida y construye la lista de RecetaIngrediente a partir de la entrada del formulario,
 * comprobando que cada insumo exista y que su unidad sea compatible (kg/g, L/ml, o exacta).
 */
final class ConstruyeIngredientesReceta
{
    public function __construct(
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    /**
     * @param  list<array{insumo_id: int, cantidad: float, unidad: string}>  $ingredientesInput
     * @return list<RecetaIngrediente>
     */
    public function ejecutar(array $ingredientesInput): array
    {
        return array_map(function (array $fila) {
            $insumo = $this->insumos->buscarPorId((int) $fila['insumo_id']);

            if ($insumo === null) {
                throw InsumoInvalidoException::noExiste();
            }

            $unidad = trim((string) $fila['unidad']) !== '' ? (string) $fila['unidad'] : $insumo->unidad;

            ConversionUnidades::convertir(1.0, $unidad, $insumo->unidad);

            return RecetaIngrediente::crear($insumo->id, (float) $fila['cantidad'], $unidad);
        }, $ingredientesInput);
    }
}

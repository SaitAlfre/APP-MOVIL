<?php

namespace App\Application\Recetas;

use App\Domain\Recetas\Exceptions\RecetaInvalidaException;
use App\Domain\Recetas\Receta;
use App\Domain\Recetas\RecetaRepositoryInterface;

/**
 * Edita una receta. Si aún está en borrador y nunca se usó en un lote, se actualiza en
 * sitio; en cualquier otro caso (activa, archivada, o ya usada) se crea una nueva versión
 * en borrador, para no alterar retroactivamente la fórmula con la que se fabricó algo.
 */
final class EditarRecetaUseCase
{
    public function __construct(
        private readonly RecetaRepositoryInterface $recetas,
        private readonly ConstruyeIngredientesReceta $construyeIngredientes,
    ) {}

    /** @param list<array{insumo_id: int, cantidad: float, unidad: string}> $ingredientesInput */
    public function ejecutar(
        int $recetaId,
        string $nombre,
        float $rendimientoBase,
        string $rendimientoUnidad,
        ?string $observaciones,
        array $ingredientesInput,
        int $usuarioId,
    ): Receta {
        $receta = $this->recetas->buscarPorId($recetaId);

        if ($receta === null) {
            throw RecetaInvalidaException::noExiste();
        }

        $ingredientes = $this->construyeIngredientes->ejecutar($ingredientesInput);

        if ($receta->editableEnSitio() && ! $this->recetas->fueUtilizadaEnLotes($recetaId)) {
            return $this->recetas->actualizar($receta->conDatosActualizados($nombre, $rendimientoBase, $rendimientoUnidad, $observaciones, $ingredientes));
        }

        $nuevaVersion = $receta->nuevaVersion($nombre, $rendimientoBase, $rendimientoUnidad, $observaciones, $ingredientes, $usuarioId);

        return $this->recetas->guardar($nuevaVersion);
    }
}

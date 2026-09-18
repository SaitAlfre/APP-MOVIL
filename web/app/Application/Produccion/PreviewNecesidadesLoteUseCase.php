<?php

namespace App\Application\Produccion;

use App\Domain\Inventario\Insumo;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Domain\Recetas\RecetaRepositoryInterface;

final class PreviewNecesidadesLoteUseCase
{
    public function __construct(
        private readonly ProductoRepositoryInterface $productos,
        private readonly RecetaRepositoryInterface $recetas,
        private readonly InsumoRepositoryInterface $insumos,
        private readonly CalculaNecesidadesReceta $calculaNecesidades,
    ) {}

    /** @return list<array{insumo: Insumo, necesaria: float, disponible: float, faltante: float, suficiente: bool}> */
    public function ejecutar(int $productoId, float $cantidadPlanificada): array
    {
        $producto = $this->productos->buscarPorId($productoId);

        if ($producto === null) {
            throw ProductoInvalidoException::noExiste();
        }

        if ($producto->recetaActivaId === null) {
            throw LoteProduccionInvalidoException::sinRecetaActiva();
        }

        $receta = $this->recetas->buscarPorId($producto->recetaActivaId);

        if ($receta === null) {
            throw LoteProduccionInvalidoException::sinRecetaActiva();
        }

        $necesidades = $this->calculaNecesidades->ejecutar($receta, $cantidadPlanificada);

        return array_map(function ($necesidad) {
            $insumo = $this->insumos->buscarPorId($necesidad->insumoId);
            $disponible = $insumo?->disponible() ?? 0.0;
            $faltante = max(0.0, round($necesidad->cantidad - $disponible, 3));

            return [
                'insumo' => $insumo,
                'necesaria' => $necesidad->cantidad,
                'disponible' => $disponible,
                'faltante' => $faltante,
                'suficiente' => $faltante <= 0.0,
            ];
        }, $necesidades);
    }
}

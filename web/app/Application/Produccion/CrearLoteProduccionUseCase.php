<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Productos\ProductoRepositoryInterface;
use DateTimeImmutable;

final class CrearLoteProduccionUseCase
{
    public function __construct(
        private readonly ObtenerAcopioDelDiaUseCase $obtenerAcopio,
        private readonly ProductoRepositoryInterface $productos,
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(DateTimeImmutable $fecha, int $productoId, float $litrosAsignados, int $responsableId): LoteProduccion
    {
        $producto = $this->productos->buscarPorId($productoId);

        if ($producto === null) {
            throw LoteProduccionInvalidoException::productoNoExiste();
        }

        LoteProduccion::validarAsignacion($litrosAsignados);

        $acopio = $this->obtenerAcopio->ejecutar($fecha);

        $lote = LoteProduccion::crear(
            productoId: $producto->id,
            fecha: $fecha,
            litrosPorUnidadSnapshot: $producto->litrosPorUnidad,
            litrosAsignados: $litrosAsignados,
            origenAcopio: $acopio->porVehiculo,
            responsableId: $responsableId,
        );

        return $this->lotes->crear($lote);
    }
}

<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Productos\ProductoRepositoryInterface;

final class IniciarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function ejecutar(int $loteId): LoteProduccion
    {
        $lote = $this->lotes->buscarPorId($loteId);

        if ($lote === null) {
            throw LoteProduccionInvalidoException::noExiste();
        }

        $producto = $this->productos->buscarPorId($lote->productoId);

        if ($producto !== null && ! $producto->activo) {
            throw LoteProduccionInvalidoException::productoInactivo();
        }

        return $this->lotes->iniciar($loteId);
    }
}

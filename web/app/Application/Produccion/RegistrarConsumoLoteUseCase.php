<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;

final class RegistrarConsumoLoteUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    /** @param array<int, float> $consumos insumoId => cantidad total consumida informada */
    public function ejecutar(int $loteId, array $consumos, int $usuarioId): LoteProduccion
    {
        if ($this->lotes->buscarPorId($loteId) === null) {
            throw LoteProduccionInvalidoException::noExiste();
        }

        return $this->lotes->registrarConsumo($loteId, $consumos, $usuarioId);
    }
}

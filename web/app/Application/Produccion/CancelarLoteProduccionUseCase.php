<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;

final class CancelarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $loteId, string $motivo, int $usuarioId): LoteProduccion
    {
        if ($this->lotes->buscarPorId($loteId) === null) {
            throw LoteProduccionInvalidoException::noExiste();
        }

        return $this->lotes->cancelar($loteId, $motivo, $usuarioId);
    }
}

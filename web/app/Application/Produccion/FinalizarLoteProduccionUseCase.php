<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;

final class FinalizarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    /** @param array<int, float>|null $consumosFinales insumoId => cantidad total consumida informada */
    public function ejecutar(int $loteId, float $cantidadObtenida, ?array $consumosFinales, int $usuarioId): LoteProduccion
    {
        if ($this->lotes->buscarPorId($loteId) === null) {
            throw LoteProduccionInvalidoException::noExiste();
        }

        return $this->lotes->finalizar($loteId, $cantidadObtenida, $consumosFinales, $usuarioId);
    }
}

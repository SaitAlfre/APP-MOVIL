<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class IniciarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $loteId, int $usuarioId): LoteProduccion
    {
        $lote = $this->lotes->buscarPorId($loteId);

        if ($lote === null) {
            throw new RuntimeException('Lote no encontrado.');
        }

        if (! $lote->puedeIniciar()) {
            throw LoteProduccionInvalidoException::transicionInvalida($lote->estado, 'iniciar');
        }

        return $this->lotes->iniciar($loteId, new DateTimeImmutable, $usuarioId);
    }
}

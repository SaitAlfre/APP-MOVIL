<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class CancelarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $loteId, string $motivo, int $usuarioId): LoteProduccion
    {
        $lote = $this->lotes->buscarPorId($loteId);

        if ($lote === null) {
            throw new RuntimeException('Lote no encontrado.');
        }

        if (! $lote->puedeCancelar()) {
            throw LoteProduccionInvalidoException::transicionInvalida($lote->estado, 'cancelar');
        }

        if (trim($motivo) === '') {
            throw LoteProduccionInvalidoException::motivoCancelacionObligatorio();
        }

        return $this->lotes->cancelar($loteId, $motivo, new DateTimeImmutable, $usuarioId);
    }
}

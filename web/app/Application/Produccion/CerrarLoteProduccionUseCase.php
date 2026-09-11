<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class CerrarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $loteId): LoteProduccion
    {
        $lote = $this->lotes->buscarPorId($loteId);

        if ($lote === null) {
            throw new RuntimeException('El lote no existe.');
        }

        return $this->lotes->guardar($lote->cerrar(new DateTimeImmutable));
    }
}

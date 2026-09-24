<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class FinalizarLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $loteId, float $litrosUsados, float $litrosMermaProceso, int $usuarioId, ?int $unidadesReales = null): LoteProduccion
    {
        $lote = $this->lotes->buscarPorId($loteId);

        if ($lote === null) {
            throw new RuntimeException('Lote no encontrado.');
        }

        if (! $lote->puedeFinalizar()) {
            throw LoteProduccionInvalidoException::transicionInvalida($lote->estado, 'finalizar');
        }

        LoteProduccion::validarFinalizacion($lote->litrosAsignados, $litrosUsados, $litrosMermaProceso);

        return $this->lotes->finalizar($loteId, $litrosUsados, $litrosMermaProceso, new DateTimeImmutable, $usuarioId, $unidadesReales);
    }
}

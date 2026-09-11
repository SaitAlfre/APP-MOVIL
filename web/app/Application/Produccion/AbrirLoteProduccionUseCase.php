<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;

final class AbrirLoteProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(string $codigo, string $producto, float $litrosUtilizados, int $responsableUsuarioId): LoteProduccion
    {
        if ($this->lotes->buscarPorCodigo(trim($codigo)) !== null) {
            throw LoteProduccionInvalidoException::codigoDuplicado();
        }

        $lote = LoteProduccion::abrir($codigo, $producto, $litrosUtilizados, $responsableUsuarioId, new DateTimeImmutable);

        return $this->lotes->guardar($lote);
    }
}

<?php

namespace App\Application\Recepcion;

use App\Domain\Recepcion\RecepcionAcopioRepositoryInterface;
use DateTimeImmutable;

final class ListarJornadasParaRecepcionUseCase
{
    public function __construct(
        private readonly RecepcionAcopioRepositoryInterface $recepciones,
    ) {}

    public function ejecutar(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?string $estado = null): array
    {
        return $this->recepciones->listarJornadas($desde, $hasta, $estado);
    }
}

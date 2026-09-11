<?php

namespace App\Application\Jornadas;

use App\Domain\Jornadas\JornadaRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarJornadasUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $porPagina = 20): LengthAwarePaginator
    {
        return $this->jornadas->paginarTodas($porPagina);
    }
}

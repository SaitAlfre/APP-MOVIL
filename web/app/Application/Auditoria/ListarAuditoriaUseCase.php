<?php

namespace App\Application\Auditoria;

use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarAuditoriaUseCase
{
    public function __construct(
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function ejecutar(int $porPagina = 25, array $filtros = []): LengthAwarePaginator
    {
        return $this->auditorias->paginar($porPagina, $filtros);
    }
}

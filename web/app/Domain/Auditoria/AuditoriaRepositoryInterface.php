<?php

namespace App\Domain\Auditoria;

use Illuminate\Pagination\LengthAwarePaginator;

interface AuditoriaRepositoryInterface
{
    public function registrar(Auditoria $auditoria): void;

    public function paginar(int $porPagina = 25): LengthAwarePaginator;
}

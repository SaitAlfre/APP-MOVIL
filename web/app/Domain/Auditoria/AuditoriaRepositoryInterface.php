<?php

namespace App\Domain\Auditoria;

interface AuditoriaRepositoryInterface
{
    public function registrar(Auditoria $auditoria): void;
}

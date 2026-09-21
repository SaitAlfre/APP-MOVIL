<?php

namespace App\Application\Usuarios;

use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarUsuariosUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {}

    public function ejecutar(?string $busqueda, ?Rol $rol, ?bool $activo, int $porPagina = 20): LengthAwarePaginator
    {
        return $this->usuarios->paginar($busqueda, $rol, $activo, $porPagina);
    }
}

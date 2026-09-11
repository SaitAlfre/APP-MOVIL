<?php

namespace App\Domain\Usuarios;

interface UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorUsername(string $username): ?Usuario;

    /** @return list<Usuario> */
    public function conRol(Rol $rol): array;

    public function guardar(Usuario $usuario): Usuario;
}

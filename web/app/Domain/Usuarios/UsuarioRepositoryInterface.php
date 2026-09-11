<?php

namespace App\Domain\Usuarios;

interface UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorUsername(string $username): ?Usuario;

    public function guardar(Usuario $usuario): Usuario;
}

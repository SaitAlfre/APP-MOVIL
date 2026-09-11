<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Usuario as UsuarioEloquent;
use DateTimeImmutable;

final class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?UsuarioDominio
    {
        $usuario = UsuarioEloquent::query()->find($id);

        return $usuario !== null ? $this->aDominio($usuario) : null;
    }

    public function buscarPorUsername(string $username): ?UsuarioDominio
    {
        $usuario = UsuarioEloquent::query()->where('username', $username)->first();

        return $usuario !== null ? $this->aDominio($usuario) : null;
    }

    public function guardar(UsuarioDominio $usuario): UsuarioDominio
    {
        $registro = $usuario->id !== null
            ? UsuarioEloquent::query()->findOrFail($usuario->id)
            : new UsuarioEloquent;

        $registro->username = $usuario->username;
        $registro->nombres = $usuario->nombres;
        $registro->dni = $usuario->dni;
        $registro->activo = $usuario->activo;
        $registro->roles = array_map(fn (Rol $r) => $r->value, $usuario->roles);
        $registro->intentos_fallidos = $usuario->intentosFallidos;
        $registro->bloqueado_hasta = $usuario->bloqueadoHasta;

        if ($registro->pin_hash === null || $registro->pin_hash === '') {
            $registro->pin_hash = $usuario->pinHash;
        }

        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    private function aDominio(UsuarioEloquent $usuario): UsuarioDominio
    {
        $bloqueadoHasta = $usuario->bloqueado_hasta;

        return UsuarioDominio::reconstruir(
            id: $usuario->id,
            username: $usuario->username,
            nombres: $usuario->nombres,
            dni: $usuario->dni,
            pinHash: $usuario->pin_hash,
            activo: $usuario->activo,
            roles: array_map(fn (string $r) => Rol::from($r), $usuario->roles ?? []),
            intentosFallidos: $usuario->intentos_fallidos,
            bloqueadoHasta: $bloqueadoHasta !== null ? DateTimeImmutable::createFromInterface($bloqueadoHasta) : null,
        );
    }
}

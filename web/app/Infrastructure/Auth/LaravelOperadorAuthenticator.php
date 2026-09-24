<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\OperadorAuthenticatorInterface;
use App\Domain\Auth\OperadorSesion;
use App\Domain\Usuarios\Rol;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

final class LaravelOperadorAuthenticator implements OperadorAuthenticatorInterface
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {}

    public function intentar(string $username, string $pin): bool
    {
        return $this->auth->guard('operador')->attempt(['username' => $username, 'password' => $pin]);
    }

    public function validar(string $username, string $pin): bool
    {
        return $this->auth->guard('operador')->validate(['username' => $username, 'password' => $pin]);
    }

    public function usuarioActual(): ?OperadorSesion
    {
        /** @var Usuario|null $usuario */
        $usuario = $this->auth->guard('operador')->user();

        if ($usuario === null) {
            return null;
        }

        return new OperadorSesion(
            id: $usuario->id,
            username: $usuario->username,
            nombres: $usuario->nombres,
            roles: array_map(fn (string $r) => Rol::from($r), $usuario->roles ?? []),
        );
    }

    public function cerrarSesion(): void
    {
        $this->auth->guard('operador')->logout();
    }
}

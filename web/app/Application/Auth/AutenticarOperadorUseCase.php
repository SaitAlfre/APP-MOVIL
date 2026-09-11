<?php

namespace App\Application\Auth;

use App\Domain\Auth\Exceptions\CuentaBloqueadaException;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Domain\Auth\OperadorAuthenticatorInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;

final class AutenticarOperadorUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly OperadorAuthenticatorInterface $authenticator,
    ) {}

    public function ejecutar(string $username, string $pin): bool
    {
        $usuario = $this->usuarios->buscarPorUsername($username);

        if ($usuario === null) {
            return false;
        }

        if (! $usuario->activo) {
            throw new CuentaInactivaException;
        }

        $ahora = new DateTimeImmutable;

        if ($usuario->estaBloqueado($ahora)) {
            throw new CuentaBloqueadaException;
        }

        if (! $this->authenticator->intentar($username, $pin)) {
            $this->usuarios->guardar($usuario->conIntentoFallido($ahora));

            return false;
        }

        $this->usuarios->guardar($usuario->sinIntentosFallidos());

        return true;
    }
}

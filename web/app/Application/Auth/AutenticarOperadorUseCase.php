<?php

namespace App\Application\Auth;

use App\Domain\Auth\Exceptions\CuentaBloqueadaException;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Domain\Auth\OperadorAuthenticatorInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
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

        if ($usuario->bloqueadoManualmente) {
            throw new CuentaBloqueadaException("Cuenta bloqueada por un administrador: {$usuario->motivoBloqueo}");
        }

        if ($usuario->estaBloqueado($ahora)) {
            throw new CuentaBloqueadaException;
        }

        if (! $this->authenticator->intentar($username, $pin)) {
            $maxIntentos = (int) (Configuracion::find('login_intentos_maximos')?->valor ?: 5);
            $minutosBloqueo = (int) (Configuracion::find('login_bloqueo_minutos')?->valor ?: 15);
            $this->usuarios->guardar($usuario->conIntentoFallido($ahora, $maxIntentos, $minutosBloqueo));

            return false;
        }

        $this->usuarios->guardar($usuario->sinIntentosFallidos());

        return true;
    }
}

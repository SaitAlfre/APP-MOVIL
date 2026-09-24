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
        return $this->comprobar($username, fn () => $this->authenticator->intentar($username, $pin));
    }

    /**
     * Mismas reglas que el login web (cuenta activa, bloqueo manual y por intentos fallidos) pero sin
     * abrir una sesión: lo usa la app móvil para obtener su token de sincronización.
     */
    public function verificarSinSesion(string $username, string $pin): bool
    {
        return $this->comprobar($username, fn () => $this->authenticator->validar($username, $pin));
    }

    /** @param  callable(): bool  $credencialesValidas */
    private function comprobar(string $username, callable $credencialesValidas): bool
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

        if (! $credencialesValidas()) {
            $maxIntentos = (int) (Configuracion::find('login_intentos_maximos')?->valor ?: 5);
            $minutosBloqueo = (int) (Configuracion::find('login_bloqueo_minutos')?->valor ?: 15);
            $this->usuarios->guardar($usuario->conIntentoFallido($ahora, $maxIntentos, $minutosBloqueo));

            return false;
        }

        $this->usuarios->guardar($usuario->sinIntentosFallidos());

        return true;
    }
}

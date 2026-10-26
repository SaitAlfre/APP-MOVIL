<?php

namespace App\Application\Usuarios;

use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario;

/** Validaciones compartidas por los casos de uso de gestión de usuarios. */
trait ValidaDatosUsuario
{
    private function validarZonaAsignada(?int $zonaId, array $roles): void
    {
        if ($zonaId === null) {
            if (in_array(Rol::Calidad, $roles, true)) {
                throw new UsuarioInvalidoException('Asigna la zona del técnico de calidad.');
            }

            return;
        }
        if (! in_array(Rol::Acopiador, $roles, true) && ! in_array(Rol::Calidad, $roles, true)) {
            throw new UsuarioInvalidoException('La zona de trabajo corresponde a Acopiador o Calidad.');
        }
        if (! $this->zonas->buscarPorId($zonaId)?->activo) {
            throw new UsuarioInvalidoException('Selecciona una zona de trabajo activa.');
        }
    }

    /** @param list<Rol> $roles @param list<Rol> $anteriores */
    private function validarRolesAsignables(array $roles, array $anteriores = []): void
    {
        $valores = fn (array $lista) => collect($lista)->map(fn (Rol $rol) => $rol->value)->sort()->values()->all();
        if (count($roles) > 1 && $valores($roles) !== $valores($anteriores)) {
            throw new UsuarioInvalidoException('Cada cuenta nueva tiene un solo rol. Las combinaciones heredadas solo se conservan sin cambios.');
        }
    }

    private function validarIdentidad(string $username, string $dni, ?Usuario $anterior = null): void
    {
        if ($anterior === null && ! preg_match('/\A[a-z0-9._]{3,30}\z/', $username)) {
            throw new UsuarioInvalidoException('El usuario debe tener de 3 a 30 caracteres: minúsculas, números, punto o guion bajo.');
        }
        if (($anterior === null || $dni !== $anterior->dni) && ! preg_match('/\A[0-9]{8}\z/', $dni)) {
            throw new UsuarioInvalidoException('El DNI debe tener 8 dígitos.');
        }
    }

    private function validarPin(string $pin): void
    {
        if (! preg_match('/\A[0-9]{4}\z/', $pin)) {
            throw UsuarioInvalidoException::pinInvalido();
        }
    }

    /**
     * Solo un administrador puede otorgar o quitar el rol de Administrador: evita que alguien
     * con acceso a este caso de uso (hoy solo admins, pero el chequeo queda como salvaguarda)
     * se autoasigne o asigne un permiso que no puede administrar.
     *
     * @param  list<Rol>  $roles
     */
    private function validarAsignacionDeAdmin(array $roles, bool $actorEsAdmin): void
    {
        if (in_array(Rol::Admin, $roles, true) && ! $actorEsAdmin) {
            throw UsuarioInvalidoException::noPuedeAsignarRolQueNoTiene();
        }
    }
}

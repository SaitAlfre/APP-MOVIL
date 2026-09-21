<?php

namespace App\Application\Usuarios;

use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;

/** Validaciones compartidas por los casos de uso de gestión de usuarios. */
trait ValidaDatosUsuario
{
    private function validarPin(string $pin): void
    {
        if (! preg_match('/^\d{4,8}$/', $pin)) {
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

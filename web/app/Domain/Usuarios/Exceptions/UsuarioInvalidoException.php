<?php

namespace App\Domain\Usuarios\Exceptions;

use DomainException;

final class UsuarioInvalidoException extends DomainException
{
    public static function usernameVacio(): self
    {
        return new self('El nombre de usuario es obligatorio.');
    }

    public static function nombresVacios(): self
    {
        return new self('Los nombres son obligatorios.');
    }

    public static function dniVacio(): self
    {
        return new self('El DNI es obligatorio.');
    }

    public static function sinRoles(): self
    {
        return new self('El usuario debe tener al menos un rol.');
    }

    public static function usernameDuplicado(): self
    {
        return new self('Ya existe un usuario con ese nombre de usuario.');
    }

    public static function motivoBloqueoObligatorio(): self
    {
        return new self('Debes indicar el motivo del bloqueo.');
    }

    public static function sinAdministradoresActivos(): self
    {
        return new self('Esta acción dejaría el sistema sin ningún administrador activo y sin bloquear.');
    }

    public static function noPuedeAsignarRolQueNoTiene(): self
    {
        return new self('No puedes asignar o quitar el rol de Administrador si tú mismo no lo tienes.');
    }

    public static function pinInvalido(): self
    {
        return new self('El PIN debe tener entre 4 y 8 dígitos numéricos.');
    }
}

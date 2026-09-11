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
}

<?php

namespace App\Domain\Proveedores\Exceptions;

use DomainException;

/** Rechazo del registro unificado por la situación del DNI o por permisos; el mensaje es apto para mostrar al administrador. */
final class RegistroProveedorException extends DomainException
{
    public static function situacion(string $mensaje): self
    {
        return new self($mensaje);
    }

    public static function confirmacionRequerida(string $mensaje): self
    {
        return new self($mensaje.' Confirma la acción sugerida para continuar.');
    }

    public static function sinPermisoCuentas(): self
    {
        return new self('Solo un administrador puede crear cuentas de acceso para proveedores.');
    }

    public static function sinPermisoVincular(): self
    {
        return new self('No tienes permiso para vincular cuentas a fichas de proveedores.');
    }

    public static function credencialesRequeridas(): self
    {
        return new self('Indica el usuario y el PIN de la cuenta móvil, o desactiva la creación de cuenta.');
    }

    public static function nadaQueRegistrar(): self
    {
        return new self('Este DNI ya tiene una ficha productiva. Activa la creación de cuenta para darle acceso móvil o revisa la ficha existente.');
    }
}

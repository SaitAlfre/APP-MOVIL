<?php

namespace App\Domain\Entregas\Exceptions;

use DomainException;

/** Motivo por el que el servidor no acepta una entrega enviada desde la app móvil; el celular lo muestra tal cual. */
final class EntregaMovilRechazadaException extends DomainException
{
    private function __construct(string $mensaje, public readonly string $codigo, public readonly int $estadoHttp = 422)
    {
        parent::__construct($mensaje);
    }

    public static function noExiste(string $que, string $valor): self
    {
        return new self("{$que} «{$valor}» no está registrado en el servidor. Pide al administrador que lo registre igual que en la app.", 'dato_no_registrado');
    }

    public static function autorDistinto(string $username): self
    {
        return new self("Esta entrega la registró «{$username}»: debe enviarla su propia cuenta.", 'autor_distinto', 403);
    }

    public static function cambioDeProveedor(): self
    {
        return new self('Una entrega ya enviada no puede cambiar de proveedor.', 'cambio_de_proveedor');
    }

    public static function reactivacion(): self
    {
        return new self('La entrega ya está anulada en el servidor y no se puede reactivar desde el celular.', 'reactivacion');
    }

    public static function versionObsoleta(): self
    {
        return new self('El servidor ya tiene una versión más reciente de esta entrega.', 'version_obsoleta', 409);
    }
}

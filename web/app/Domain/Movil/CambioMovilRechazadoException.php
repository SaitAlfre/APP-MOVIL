<?php

namespace App\Domain\Movil;

use DomainException;

/**
 * El panel no acepta un cambio hecho en la app móvil. [definitivo] = reintentar igual no sirve (dato
 * inválido); si no, el celular vuelve a intentarlo (p. ej. aún no llega la entrega que evalúa calidad).
 */
final class CambioMovilRechazadoException extends DomainException
{
    public function __construct(string $mensaje, public readonly string $codigo = 'invalido', public readonly int $estadoHttp = 422, public readonly bool $definitivo = true)
    {
        parent::__construct($mensaje);
    }

    public static function sinPermiso(): self
    {
        return new self('Tu cuenta no tiene permiso para enviar este cambio al panel.', 'sin_permiso', 403);
    }

    public static function noExiste(string $que, string $valor): self
    {
        return new self("{$que} «{$valor}» no está registrado en el panel web.", 'dato_no_registrado');
    }

    public static function esperando(string $mensaje): self
    {
        return new self($mensaje, 'esperando', 409, false);
    }
}

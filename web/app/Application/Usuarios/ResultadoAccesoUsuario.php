<?php

namespace App\Application\Usuarios;

use App\Domain\Usuarios\Usuario;

/** Resultado de suspender o restaurar un acceso, con lo ocurrido en la ficha productiva vinculada. */
final readonly class ResultadoAccesoUsuario
{
    public const SIN_FICHA = 'sin_ficha';

    public const FICHA_SUSPENDIDA = 'ficha_suspendida';

    public const FICHA_RESTAURADA = 'ficha_restaurada';

    public const FICHA_YA_SUSPENDIDA = 'ficha_ya_suspendida';

    public const FICHA_RETIRADA = 'ficha_retirada';

    public const FICHA_SUSPENSION_INDEPENDIENTE = 'ficha_suspension_independiente';

    public const FICHA_ACTIVA = 'ficha_activa';

    /** @param  self::*|null  $ficha  null cuando la cuenta no tiene el rol Proveedor. */
    public function __construct(
        public Usuario $usuario,
        public ?string $ficha,
    ) {}
}

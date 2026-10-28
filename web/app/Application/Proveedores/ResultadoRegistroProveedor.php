<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Proveedor;
use App\Domain\Usuarios\Usuario;

final class ResultadoRegistroProveedor
{
    public function __construct(
        public readonly Proveedor $proveedor,
        public readonly ?Usuario $cuenta,
        public readonly SituacionRegistroProveedor $situacion,
        public readonly bool $cuentaCreada,
    ) {}

    public function mensaje(): string
    {
        $usuario = $this->cuenta?->username;

        return match (true) {
            $this->situacion === SituacionRegistroProveedor::FichaSinCuenta => "Se creó la cuenta móvil «{$usuario}» y se vinculó a la ficha existente {$this->proveedor->codigo}.",
            $this->situacion === SituacionRegistroProveedor::CuentaSinFicha => "Se registró la ficha {$this->proveedor->codigo} y se asoció a la cuenta existente «{$usuario}».",
            $this->situacion === SituacionRegistroProveedor::FichaYCuentaSinVincular => "La ficha {$this->proveedor->codigo} quedó vinculada a la cuenta «{$usuario}».",
            $this->cuentaCreada => "Proveedor {$this->proveedor->nombres} registrado con cuenta móvil «{$usuario}». Ya aparece en Proveedores y en Usuarios y accesos.",
            default => "Proveedor {$this->proveedor->nombres} registrado sin cuenta móvil. Código {$this->proveedor->codigo}.",
        };
    }
}

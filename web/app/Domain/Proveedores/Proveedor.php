<?php

namespace App\Domain\Proveedores;

use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;

final class Proveedor
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $codigo,
        public readonly string $nombres,
        public readonly string $dni,
        public readonly ?string $telefono,
        public readonly ?string $direccion,
        public readonly int $zonaId,
        public readonly int $tachos,
        public readonly float $capacidadTachoL,
        public readonly EstadoProveedor $estado,
        public readonly ?int $creadoPorAdminId,
    ) {}

    public static function crear(
        string $codigo,
        string $nombres,
        string $dni,
        ?string $telefono,
        ?string $direccion,
        int $zonaId,
        int $tachos,
        float $capacidadTachoL,
        ?int $creadoPorAdminId = null,
        EstadoProveedor $estado = EstadoProveedor::Activo,
    ): self {
        self::validar($codigo, $nombres, $dni, $tachos, $capacidadTachoL);

        return new self(
            id: null,
            codigo: trim($codigo),
            nombres: trim($nombres),
            dni: trim($dni),
            telefono: self::limpiar($telefono),
            direccion: self::limpiar($direccion),
            zonaId: $zonaId,
            tachos: $tachos,
            capacidadTachoL: $capacidadTachoL,
            estado: $estado,
            creadoPorAdminId: $creadoPorAdminId,
        );
    }

    public static function reconstruir(
        int $id,
        string $codigo,
        string $nombres,
        string $dni,
        ?string $telefono,
        ?string $direccion,
        int $zonaId,
        int $tachos,
        float $capacidadTachoL,
        EstadoProveedor $estado,
        ?int $creadoPorAdminId,
    ): self {
        return new self(
            id: $id,
            codigo: $codigo,
            nombres: $nombres,
            dni: $dni,
            telefono: $telefono,
            direccion: $direccion,
            zonaId: $zonaId,
            tachos: $tachos,
            capacidadTachoL: $capacidadTachoL,
            estado: $estado,
            creadoPorAdminId: $creadoPorAdminId,
        );
    }

    public function actualizar(
        string $codigo,
        string $nombres,
        string $dni,
        ?string $telefono,
        ?string $direccion,
        int $zonaId,
        int $tachos,
        float $capacidadTachoL,
    ): self {
        self::validar($codigo, $nombres, $dni, $tachos, $capacidadTachoL);

        return new self(
            id: $this->id,
            codigo: trim($codigo),
            nombres: trim($nombres),
            dni: trim($dni),
            telefono: self::limpiar($telefono),
            direccion: self::limpiar($direccion),
            zonaId: $zonaId,
            tachos: $tachos,
            capacidadTachoL: $capacidadTachoL,
            estado: $this->estado,
            creadoPorAdminId: $this->creadoPorAdminId,
        );
    }

    public function conEstado(EstadoProveedor $estado): self
    {
        return new self(
            id: $this->id,
            codigo: $this->codigo,
            nombres: $this->nombres,
            dni: $this->dni,
            telefono: $this->telefono,
            direccion: $this->direccion,
            zonaId: $this->zonaId,
            tachos: $this->tachos,
            capacidadTachoL: $this->capacidadTachoL,
            estado: $estado,
            creadoPorAdminId: $this->creadoPorAdminId,
        );
    }

    public function capacidadTotalL(): float
    {
        return $this->tachos * $this->capacidadTachoL;
    }

    private static function validar(string $codigo, string $nombres, string $dni, int $tachos, float $capacidadTachoL): void
    {
        if (trim($codigo) === '') {
            throw ProveedorInvalidoException::codigoVacio();
        }

        if (trim($nombres) === '') {
            throw ProveedorInvalidoException::nombresVacios();
        }

        if (trim($dni) === '') {
            throw ProveedorInvalidoException::dniVacio();
        }

        if ($tachos <= 0) {
            throw ProveedorInvalidoException::tachosInvalidos();
        }

        if ($capacidadTachoL <= 0.0) {
            throw ProveedorInvalidoException::capacidadInvalida();
        }
    }

    private static function limpiar(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : null;

        return $valor === '' ? null : $valor;
    }
}

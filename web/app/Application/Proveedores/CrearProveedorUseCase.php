<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;

final class CrearProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly ZonaRepositoryInterface $zonas,
    ) {}

    public function ejecutar(DatosProveedor $datos, ?int $creadoPorAdminId): Proveedor
    {
        if ($this->zonas->buscarPorId($datos->zonaId) === null) {
            throw ProveedorInvalidoException::zonaInexistente();
        }

        if ($this->proveedores->buscarPorCodigo($datos->codigo) !== null) {
            throw ProveedorInvalidoException::codigoDuplicado();
        }

        if ($this->proveedores->buscarPorDni($datos->dni) !== null) {
            throw ProveedorInvalidoException::dniDuplicado();
        }

        $proveedor = Proveedor::crear(
            codigo: $datos->codigo,
            nombres: $datos->nombres,
            dni: $datos->dni,
            telefono: $datos->telefono,
            direccion: $datos->direccion,
            zonaId: $datos->zonaId,
            tachos: $datos->tachos,
            capacidadTachoL: $datos->capacidadTachoL,
            creadoPorAdminId: $creadoPorAdminId,
        );

        return $this->proveedores->guardar($proveedor);
    }
}

<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;

final class ActualizarProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly ZonaRepositoryInterface $zonas,
    ) {}

    public function ejecutar(int $proveedorId, DatosProveedor $datos): Proveedor
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor === null) {
            throw new \RuntimeException('El proveedor no existe.');
        }

        if ($this->zonas->buscarPorId($datos->zonaId) === null) {
            throw ProveedorInvalidoException::zonaInexistente();
        }

        $porCodigo = $this->proveedores->buscarPorCodigo($datos->codigo);
        if ($porCodigo !== null && $porCodigo->id !== $proveedor->id) {
            throw ProveedorInvalidoException::codigoDuplicado();
        }

        $porDni = $this->proveedores->buscarPorDni($datos->dni);
        if ($porDni !== null && $porDni->id !== $proveedor->id) {
            throw ProveedorInvalidoException::dniDuplicado();
        }

        $actualizado = $proveedor->actualizar(
            codigo: $datos->codigo,
            nombres: $datos->nombres,
            dni: $datos->dni,
            telefono: $datos->telefono,
            direccion: $datos->direccion,
            zonaId: $datos->zonaId,
            tachos: $datos->tachos,
            capacidadTachoL: $datos->capacidadTachoL,
        );

        return $this->proveedores->guardar($actualizado);
    }
}

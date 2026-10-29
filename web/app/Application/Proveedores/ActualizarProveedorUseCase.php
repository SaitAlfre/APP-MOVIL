<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ActualizarProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly ZonaRepositoryInterface $zonas,
        private readonly CambiarEstadoProveedorUseCase $cambiarEstado,
    ) {}

    public function ejecutar(int $proveedorId, DatosProveedor $datos, ?EstadoProveedor $estado = null, ?string $motivoEstado = null, ?int $actorId = null): Proveedor
    {
        return DB::transaction(function () use ($proveedorId, $datos, $estado, $motivoEstado, $actorId): Proveedor {
            DB::table('proveedores')->where('id', $proveedorId)->lockForUpdate()->first();
            $proveedor = $this->actualizarDatos($proveedorId, $datos);

            return $estado === null ? $proveedor : $this->cambiarEstado->ejecutar($proveedorId, $estado, $motivoEstado, $actorId);
        });
    }

    private function actualizarDatos(int $proveedorId, DatosProveedor $datos): Proveedor
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

        if ($proveedor->usuarioId !== null && trim($datos->dni) !== trim($proveedor->dni)) {
            throw ProveedorInvalidoException::dniBloqueadoPorCuentaVinculada();
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

        if ($datos->responsableIncluido) {
            $actualizado = $actualizado->conResponsable($datos->dueno);
        }

        return $this->proveedores->guardar($actualizado);
    }
}

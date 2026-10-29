<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use Illuminate\Support\Facades\DB;

/** Cambia el estado productivo de una ficha y, en la misma transacción, el de su cuenta vinculada. */
final class CambiarEstadoProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly SincronizarCuentaConFichaUseCase $sincronizarCuenta,
    ) {}

    public function ejecutar(int $proveedorId, EstadoProveedor $estado, ?string $motivo = null, ?int $actorId = null): Proveedor
    {
        return DB::transaction(function () use ($proveedorId, $estado, $motivo, $actorId): Proveedor {
            $proveedor = $this->proveedores->buscarPorId($proveedorId);

            if ($proveedor === null) {
                throw new \RuntimeException('El proveedor no existe.');
            }
            $origenAnterior = $this->proveedores->origenSuspension($proveedorId);
            $guardado = $this->proveedores->guardar($proveedor->conEstado($estado));
            if ($proveedor->estado !== $estado) {
                $this->sincronizarCuenta->ejecutar($guardado, $estado, $origenAnterior, $motivo, $actorId);
            }

            return $guardado;
        });
    }
}

<?php

namespace App\Domain\Proveedores;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProveedorRepositoryInterface
{
    /** La ficha se suspendió al suspender el acceso de su cuenta vinculada; puede restaurarse con ella. */
    public const ORIGEN_ACCESO_CUENTA = 'acceso_cuenta';

    public function paginar(int $porPagina = 15): LengthAwarePaginator;

    public function buscarPorId(int $id): ?Proveedor;

    public function buscarPorCodigo(string $codigo): ?Proveedor;

    public function buscarPorDni(string $dni): ?Proveedor;

    /** @return list<Proveedor> */
    public function activosPorZona(int $zonaId): array;

    public function contarActivos(): int;

    public function contarTodos(): int;

    /** Listado paginado con los filtros de la pantalla de proveedores (búsqueda libre, zona y estado). */
    public function paginarFiltrado(?string $busqueda, ?int $zonaId, ?EstadoProveedor $estado, int $porPagina = 15): LengthAwarePaginator;

    public function buscarPorUsuarioId(int $usuarioId): ?Proveedor;

    /** Fichas sin cuenta móvil vinculada, para la conciliación supervisada. @return list<Proveedor> */
    public function sinCuentaVinculada(): array;

    /** Al cambiar el estado se borra el origen de una suspensión anterior: la nueva decisión es independiente. */
    public function guardar(Proveedor $proveedor): Proveedor;

    /** Origen de la suspensión productiva vigente; null si es independiente o histórica sin origen conocido. */
    public function origenSuspension(int $proveedorId): ?string;

    /** Cambia el estado por una suspensión o restauración de la cuenta vinculada; la auditoría la registra quien llama. */
    public function cambiarEstadoPorAccesoDeCuenta(int $proveedorId, EstadoProveedor $estado): void;
}

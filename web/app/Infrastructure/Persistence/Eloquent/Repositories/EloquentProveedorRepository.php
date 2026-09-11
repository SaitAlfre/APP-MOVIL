<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Proveedores\Proveedor as ProveedorDominio;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Proveedor as ProveedorEloquent;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentProveedorRepository implements ProveedorRepositoryInterface
{
    public function paginar(int $porPagina = 15): LengthAwarePaginator
    {
        return ProveedorEloquent::query()
            ->with('zona')
            ->orderBy('nombres')
            ->paginate($porPagina)
            ->through(fn (ProveedorEloquent $proveedor) => $this->aDominio($proveedor));
    }

    public function buscarPorId(int $id): ?ProveedorDominio
    {
        $proveedor = ProveedorEloquent::query()->find($id);

        return $proveedor !== null ? $this->aDominio($proveedor) : null;
    }

    public function buscarPorCodigo(string $codigo): ?ProveedorDominio
    {
        $proveedor = ProveedorEloquent::query()->where('codigo', $codigo)->first();

        return $proveedor !== null ? $this->aDominio($proveedor) : null;
    }

    public function buscarPorDni(string $dni): ?ProveedorDominio
    {
        $proveedor = ProveedorEloquent::query()->where('dni', $dni)->first();

        return $proveedor !== null ? $this->aDominio($proveedor) : null;
    }

    public function activosPorZona(int $zonaId): array
    {
        return ProveedorEloquent::query()
            ->where('zona_id', $zonaId)
            ->where('estado', 'activo')
            ->orderBy('nombres')
            ->get()
            ->map(fn (ProveedorEloquent $p) => $this->aDominio($p))
            ->all();
    }

    public function buscarPorUsuarioId(int $usuarioId): ?ProveedorDominio
    {
        $proveedor = ProveedorEloquent::query()->where('usuario_id', $usuarioId)->first();

        return $proveedor !== null ? $this->aDominio($proveedor) : null;
    }

    public function guardar(ProveedorDominio $proveedor): ProveedorDominio
    {
        $registro = $proveedor->id !== null
            ? ProveedorEloquent::query()->findOrFail($proveedor->id)
            : new ProveedorEloquent;

        $registro->fill([
            'codigo' => $proveedor->codigo,
            'nombres' => $proveedor->nombres,
            'dni' => $proveedor->dni,
            'telefono' => $proveedor->telefono,
            'direccion' => $proveedor->direccion,
            'zona_id' => $proveedor->zonaId,
            'tachos' => $proveedor->tachos,
            'capacidad_tacho_l' => $proveedor->capacidadTachoL,
            'estado' => $proveedor->estado,
            'creado_por_usuario_id' => $proveedor->creadoPorUsuarioId,
            'usuario_id' => $proveedor->usuarioId,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    private function aDominio(ProveedorEloquent $proveedor): ProveedorDominio
    {
        return ProveedorDominio::reconstruir(
            id: $proveedor->id,
            codigo: $proveedor->codigo,
            nombres: $proveedor->nombres,
            dni: $proveedor->dni,
            telefono: $proveedor->telefono,
            direccion: $proveedor->direccion,
            zonaId: $proveedor->zona_id,
            tachos: $proveedor->tachos,
            capacidadTachoL: (float) $proveedor->capacidad_tacho_l,
            estado: $proveedor->estado,
            creadoPorUsuarioId: $proveedor->creado_por_usuario_id,
            usuarioId: $proveedor->usuario_id,
        );
    }
}

<?php

namespace App\Domain\Inventario;

use Illuminate\Pagination\LengthAwarePaginator;

interface InsumoRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator;

    /** @return list<Insumo> */
    public function todosActivos(): array;

    /** @return list<Insumo> */
    public function conStockBajo(): array;

    public function buscarPorId(int $id): ?Insumo;

    public function buscarPorNombre(string $nombre): ?Insumo;

    public function guardar(Insumo $insumo): Insumo;

    public function ajustar(int $insumoId, float $delta, string $motivo, int $usuarioId, ?string $observaciones): Insumo;
}

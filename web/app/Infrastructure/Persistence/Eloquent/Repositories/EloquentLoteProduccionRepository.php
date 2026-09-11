<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Produccion\EstadoLoteProduccion;
use App\Domain\Produccion\LoteProduccion as LoteDominio;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion as LoteEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentLoteProduccionRepository implements LoteProduccionRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator
    {
        return LoteEloquent::query()
            ->orderByDesc('abierto_en')
            ->paginate($porPagina)
            ->through(fn (LoteEloquent $l) => $this->aDominio($l));
    }

    public function buscarPorId(int $id): ?LoteDominio
    {
        $lote = LoteEloquent::query()->find($id);

        return $lote !== null ? $this->aDominio($lote) : null;
    }

    public function buscarPorCodigo(string $codigo): ?LoteDominio
    {
        $lote = LoteEloquent::query()->where('codigo', $codigo)->first();

        return $lote !== null ? $this->aDominio($lote) : null;
    }

    public function guardar(LoteDominio $lote): LoteDominio
    {
        $registro = $lote->id !== null
            ? LoteEloquent::query()->findOrFail($lote->id)
            : new LoteEloquent;

        $registro->fill([
            'codigo' => $lote->codigo,
            'producto' => $lote->producto,
            'litros_utilizados' => $lote->litrosUtilizados,
            'estado' => $lote->estado,
            'responsable_usuario_id' => $lote->responsableUsuarioId,
            'abierto_en' => $lote->abiertoEn,
            'cerrado_en' => $lote->cerradoEn,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    private function aDominio(LoteEloquent $lote): LoteDominio
    {
        return LoteDominio::reconstruir(
            id: $lote->id,
            codigo: $lote->codigo,
            producto: $lote->producto,
            litrosUtilizados: (float) $lote->litros_utilizados,
            estado: $lote->estado instanceof EstadoLoteProduccion ? $lote->estado : EstadoLoteProduccion::from($lote->estado),
            responsableUsuarioId: $lote->responsable_usuario_id,
            abiertoEn: DateTimeImmutable::createFromInterface($lote->abierto_en),
            cerradoEn: $lote->cerrado_en !== null ? DateTimeImmutable::createFromInterface($lote->cerrado_en) : null,
        );
    }
}

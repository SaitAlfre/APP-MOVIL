<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Auditoria\DatosSegurosAuditoria;
use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria as AuditoriaDominio;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Auditoria as AuditoriaEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentAuditoriaRepository implements AuditoriaRepositoryInterface
{
    public function registrar(AuditoriaDominio $auditoria): void
    {
        AuditoriaEloquent::query()->create([
            'entidad' => $auditoria->entidad,
            'entidad_id' => $auditoria->entidadId,
            'accion' => $auditoria->accion->value,
            'valor_antes' => $auditoria->valorAntes,
            'valor_despues' => $auditoria->valorDespues,
            'motivo' => $auditoria->motivo,
            'usuario_id' => $auditoria->usuarioId,
            'ocurrido_en' => $auditoria->ocurridoEn,
        ]);
    }

    public function paginar(int $porPagina = 25, array $filtros = []): LengthAwarePaginator
    {
        return AuditoriaEloquent::query()
            ->when($filtros['usuario_id'] ?? null, fn ($q, $valor) => $q->where('usuario_id', $valor))
            ->when($filtros['entidad'] ?? null, fn ($q, $valor) => $q->where('entidad', $valor))
            ->when($filtros['entidad_id'] ?? null, fn ($q, $valor) => $q->where('entidad_id', $valor))
            ->when($filtros['accion'] ?? null, fn ($q, $valor) => $q->where('accion', $valor))
            ->when($filtros['desde'] ?? null, fn ($q, $valor) => $q->where('ocurrido_en', '>=', $valor.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $valor) => $q->where('ocurrido_en', '<=', $valor.' 23:59:59'))
            ->orderByDesc('ocurrido_en')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->appends($filtros)
            ->through(fn (AuditoriaEloquent $a) => $this->aDominio($a));
    }

    public function buscarPorId(int $id): ?AuditoriaDominio
    {
        $registro = AuditoriaEloquent::query()->find($id);

        return $registro ? $this->aDominio($registro) : null;
    }

    private function aDominio(AuditoriaEloquent $a): AuditoriaDominio
    {
        return new AuditoriaDominio(
            id: $a->id,
            entidad: $a->entidad,
            entidadId: $a->entidad_id,
            accion: AccionAuditoria::from($a->accion),
            valorAntes: DatosSegurosAuditoria::limpiar($a->valor_antes),
            valorDespues: DatosSegurosAuditoria::limpiar($a->valor_despues),
            motivo: DatosSegurosAuditoria::limpiar($a->motivo),
            usuarioId: $a->usuario_id,
            ocurridoEn: DateTimeImmutable::createFromInterface($a->ocurrido_en),
        );
    }
}

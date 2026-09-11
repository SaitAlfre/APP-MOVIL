<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

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

    public function paginar(int $porPagina = 25): LengthAwarePaginator
    {
        return AuditoriaEloquent::query()
            ->orderByDesc('ocurrido_en')
            ->paginate($porPagina)
            ->through(fn (AuditoriaEloquent $a) => new AuditoriaDominio(
                id: $a->id,
                entidad: $a->entidad,
                entidadId: $a->entidad_id,
                accion: AccionAuditoria::from($a->accion),
                valorAntes: $a->valor_antes,
                valorDespues: $a->valor_despues,
                motivo: $a->motivo,
                usuarioId: $a->usuario_id,
                ocurridoEn: DateTimeImmutable::createFromInterface($a->ocurrido_en),
            ));
    }
}

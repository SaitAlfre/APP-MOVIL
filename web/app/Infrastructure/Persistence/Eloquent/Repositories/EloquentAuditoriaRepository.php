<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Auditoria\Auditoria as AuditoriaDominio;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Auditoria as AuditoriaEloquent;

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
}

<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Auditoria\DatosSegurosAuditoria;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $registro) {
            foreach (['valor_antes', 'valor_despues', 'motivo'] as $campo) {
                $registro->{$campo} = DatosSegurosAuditoria::limpiar($registro->{$campo});
            }
        });
    }

    protected $table = 'auditorias';

    protected $fillable = [
        'entidad', 'entidad_id', 'accion', 'valor_antes', 'valor_despues', 'motivo', 'usuario_id', 'ocurrido_en',
    ];

    protected function casts(): array
    {
        return ['ocurrido_en' => 'datetime'];
    }
}

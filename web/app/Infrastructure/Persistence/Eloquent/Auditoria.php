<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $fillable = [
        'entidad', 'entidad_id', 'accion', 'valor_antes', 'valor_despues', 'motivo', 'usuario_id', 'ocurrido_en',
    ];

    protected function casts(): array
    {
        return ['ocurrido_en' => 'datetime'];
    }
}

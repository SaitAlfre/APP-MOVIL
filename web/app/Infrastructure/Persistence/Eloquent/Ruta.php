<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruta extends Model
{
    protected $table = 'rutas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'paradas' => 'array',
            'trazado' => 'array',
            'mapa_version' => 'integer',
        ];
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function acopiador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'acopiador_id');
    }
}

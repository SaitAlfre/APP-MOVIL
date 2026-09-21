<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecepcionAcopio extends Model
{
    protected $table = 'recepciones_acopio';

    protected $fillable = [
        'jornada_id', 'llegada_en', 'litros_recolectados', 'litros_medidos',
        'motivo_diferencia', 'observaciones', 'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'llegada_en' => 'datetime',
            'litros_recolectados' => 'decimal:2',
            'litros_medidos' => 'decimal:2',
        ];
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class, 'jornada_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}

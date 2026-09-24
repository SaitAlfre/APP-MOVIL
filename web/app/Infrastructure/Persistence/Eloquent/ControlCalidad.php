<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Calidad\EstadoCalidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlCalidad extends Model
{
    protected $table = 'controles_calidad';

    protected $fillable = [
        'entrega_id', 'usuario_id', 'resultado', 'temperatura_c', 'acidez', 'observaciones', 'evaluado_en', 'uuid_movil',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => EstadoCalidad::class,
            'temperatura_c' => 'decimal:2',
            'acidez' => 'decimal:2',
            'evaluado_en' => 'datetime',
        ];
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class, 'entrega_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}

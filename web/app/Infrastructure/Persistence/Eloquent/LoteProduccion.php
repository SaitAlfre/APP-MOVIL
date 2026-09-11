<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Produccion\EstadoLoteProduccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteProduccion extends Model
{
    protected $table = 'lotes_produccion';

    protected $fillable = [
        'codigo', 'producto', 'litros_utilizados', 'estado', 'responsable_usuario_id', 'abierto_en', 'cerrado_en',
    ];

    protected function casts(): array
    {
        return [
            'litros_utilizados' => 'decimal:2',
            'estado' => EstadoLoteProduccion::class,
            'abierto_en' => 'datetime',
            'cerrado_en' => 'datetime',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_usuario_id');
    }
}

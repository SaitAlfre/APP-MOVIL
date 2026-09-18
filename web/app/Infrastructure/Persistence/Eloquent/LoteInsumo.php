<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteInsumo extends Model
{
    protected $table = 'lote_insumos';

    protected $fillable = [
        'lote_produccion_id', 'insumo_id', 'unidad', 'cantidad_necesaria', 'cantidad_reservada', 'cantidad_consumida',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_necesaria' => 'decimal:3',
            'cantidad_reservada' => 'decimal:3',
            'cantidad_consumida' => 'decimal:3',
        ];
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteProduccion::class, 'lote_produccion_id');
    }
}

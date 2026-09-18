<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntradaInsumo extends Model
{
    protected $table = 'entradas_insumo';

    protected $fillable = [
        'insumo_id', 'proveedor_id', 'entrega_id', 'cantidad', 'unidad', 'fecha',
        'costo_unitario', 'costo_total', 'documento_referencia', 'lote_origen',
        'vencimiento', 'observaciones', 'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'fecha' => 'date',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:2',
            'vencimiento' => 'date',
        ];
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}

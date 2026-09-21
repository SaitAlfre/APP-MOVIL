<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoProducto extends Model
{
    protected $table = 'movimientos_producto';

    protected $fillable = [
        'producto_id', 'tipo', 'cantidad', 'unidad',
        'motivo', 'usuario_id', 'observaciones', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'fecha' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

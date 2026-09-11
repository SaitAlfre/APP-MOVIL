<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Liquidaciones\EstadoLiquidacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    protected $fillable = [
        'proveedor_id', 'periodo_inicio', 'periodo_fin', 'litros_totales', 'precio_litro',
        'monto_total', 'estado', 'generada_en', 'pagada_en',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'litros_totales' => 'decimal:2',
            'precio_litro' => 'decimal:3',
            'monto_total' => 'decimal:2',
            'estado' => EstadoLiquidacion::class,
            'generada_en' => 'datetime',
            'pagada_en' => 'datetime',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}

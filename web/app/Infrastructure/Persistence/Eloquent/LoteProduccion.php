<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteProduccion extends Model
{
    protected $table = 'lotes_produccion';

    protected $fillable = [
        'codigo', 'producto_id', 'fecha', 'litros_por_unidad_snapshot', 'litros_asignados',
        'litros_usados', 'litros_merma_proceso', 'litros_sobrantes', 'unidades_estimadas',
        'unidades_producidas', 'estado', 'origen_acopio', 'responsable_id',
        'iniciado_en', 'finalizado_en', 'cancelado_en', 'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'litros_por_unidad_snapshot' => 'decimal:3',
            'litros_asignados' => 'decimal:3',
            'litros_usados' => 'decimal:3',
            'litros_merma_proceso' => 'decimal:3',
            'litros_sobrantes' => 'decimal:3',
            'origen_acopio' => 'array',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }
}

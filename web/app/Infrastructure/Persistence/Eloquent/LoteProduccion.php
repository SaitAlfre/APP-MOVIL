<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Produccion\EstadoLoteProduccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteProduccion extends Model
{
    protected $table = 'lotes_produccion';

    protected $fillable = [
        'codigo', 'producto_id', 'receta_id', 'cantidad_planificada', 'cantidad_obtenida', 'unidad',
        'estado', 'responsable_usuario_id', 'fecha_planificada', 'observaciones',
        'abierto_en', 'iniciado_en', 'finalizado_en', 'cancelado_en', 'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_planificada' => 'decimal:3',
            'cantidad_obtenida' => 'decimal:3',
            'estado' => EstadoLoteProduccion::class,
            'fecha_planificada' => 'date',
            'abierto_en' => 'datetime',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_usuario_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'lote_produccion_id');
    }
}

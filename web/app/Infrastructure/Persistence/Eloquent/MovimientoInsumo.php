<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Inventario\TipoMovimientoInsumo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInsumo extends Model
{
    protected $table = 'movimientos_insumo';

    protected $fillable = [
        'insumo_id', 'tipo', 'cantidad', 'unidad', 'lote_produccion_id', 'entrada_insumo_id',
        'motivo', 'usuario_id', 'observaciones', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimientoInsumo::class,
            'cantidad' => 'decimal:3',
            'fecha' => 'datetime',
        ];
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}

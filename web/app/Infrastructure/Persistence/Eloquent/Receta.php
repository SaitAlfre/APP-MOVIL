<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Recetas\EstadoReceta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    protected $table = 'recetas';

    protected $fillable = [
        'producto_id', 'nombre', 'version', 'rendimiento_base', 'rendimiento_unidad',
        'observaciones', 'estado', 'creado_por_usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'rendimiento_base' => 'decimal:3',
            'estado' => EstadoReceta::class,
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(RecetaIngrediente::class);
    }
}

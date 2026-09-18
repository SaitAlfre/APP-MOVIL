<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected static function newFactory(): ProductoFactory
    {
        return ProductoFactory::new();
    }

    protected $table = 'productos';

    protected $fillable = [
        'nombre', 'presentacion', 'unidad_produccion', 'contenido_por_unidad',
        'unidad_contenido', 'existencia', 'activo', 'receta_activa_id',
    ];

    protected function casts(): array
    {
        return [
            'contenido_por_unidad' => 'decimal:3',
            'existencia' => 'decimal:3',
            'activo' => 'boolean',
        ];
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class);
    }

    public function recetaActiva(): BelongsTo
    {
        return $this->belongsTo(Receta::class, 'receta_activa_id');
    }
}

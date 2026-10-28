<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    use HasFactory;

    protected static function newFactory(): ProductoFactory
    {
        return ProductoFactory::new();
    }

    protected $table = 'productos';

    protected $fillable = [
        'receta_id', 'cantidad_receta', 'nombre', 'presentacion', 'unidad_produccion', 'contenido_por_unidad',
        'precio_proveedor', 'precio_cliente', 'precio_mayorista', 'receta_activa',
        'unidad_contenido', 'litros_por_unidad', 'otros_insumos', 'existencia', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'contenido_por_unidad' => 'decimal:3',
            'litros_por_unidad' => 'decimal:3',
            'existencia' => 'decimal:3', 'cantidad_receta' => 'decimal:3',
            'activo' => 'boolean', 'receta_activa' => 'boolean',
            'precio_proveedor' => 'decimal:2', 'precio_cliente' => 'decimal:2', 'precio_mayorista' => 'decimal:2',
        ];
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }
}

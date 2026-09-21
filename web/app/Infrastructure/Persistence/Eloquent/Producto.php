<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'unidad_contenido', 'litros_por_unidad', 'otros_insumos', 'existencia', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'contenido_por_unidad' => 'decimal:3',
            'litros_por_unidad' => 'decimal:3',
            'existencia' => 'decimal:3',
            'activo' => 'boolean',
        ];
    }
}

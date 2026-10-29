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
        'receta_id', 'codigo', 'descripcion', 'imagen', 'stock_minimo', 'nombre', 'presentacion', 'unidad_produccion', 'contenido_por_unidad',
        'precio_proveedor', 'precio_cliente', 'precio_mayorista', 'receta_activa',
        'unidad_contenido', 'litros_por_unidad', 'otros_insumos', 'existencia', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'contenido_por_unidad' => 'decimal:3',
            'litros_por_unidad' => 'decimal:6', 'stock_minimo' => 'decimal:3',
            'existencia' => 'decimal:3',
            'activo' => 'boolean', 'receta_activa' => 'boolean',
            'precio_proveedor' => 'decimal:2', 'precio_cliente' => 'decimal:2', 'precio_mayorista' => 'decimal:2',
        ];
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    /** URL de la imagen servida por la aplicación (no depende del enlace public/storage). */
    public function urlImagen(): ?string
    {
        return $this->imagen !== null ? route('admin.produccion.productos.imagen', ['producto' => $this->id, 'v' => $this->updated_at?->timestamp]) : null;
    }

    /** Disponible, bajo (en o bajo el mínimo configurado) o agotado. */
    public function estadoStock(): string
    {
        return match (true) {
            (float) $this->existencia <= 0 => 'agotado',
            $this->stock_minimo !== null && (float) $this->existencia <= (float) $this->stock_minimo => 'bajo',
            default => 'disponible',
        };
    }
}

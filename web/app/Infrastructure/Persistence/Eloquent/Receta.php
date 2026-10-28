<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\Infrastructure\Persistence\Eloquent\RecetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'recetas';

    protected $fillable = ['nombre', 'unidad_base', 'litros_por_unidad', 'otros_insumos', 'activo'];

    protected function casts(): array
    {
        return ['litros_por_unidad' => 'decimal:3', 'activo' => 'boolean'];
    }

    protected static function newFactory(): RecetaFactory
    {
        return RecetaFactory::new();
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}

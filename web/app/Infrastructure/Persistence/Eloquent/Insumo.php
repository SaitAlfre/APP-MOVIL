<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\InsumoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected static function newFactory(): InsumoFactory
    {
        return InsumoFactory::new();
    }

    protected $table = 'insumos';

    protected $fillable = [
        'nombre', 'unidad', 'stock_minimo', 'existencia', 'reservado', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'stock_minimo' => 'decimal:3',
            'existencia' => 'decimal:3',
            'reservado' => 'decimal:3',
            'activo' => 'boolean',
        ];
    }
}

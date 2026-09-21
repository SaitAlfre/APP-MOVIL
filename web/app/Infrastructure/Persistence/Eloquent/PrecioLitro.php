<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PrecioLitro extends Model
{
    protected $table = 'precios_litro';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['vigente_desde' => 'date', 'precio' => 'decimal:4'];
    }
}

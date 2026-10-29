<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materiales';

    protected $fillable = ['nombre', 'unidad', 'existencia', 'stock_minimo'];

    protected function casts(): array
    {
        return ['existencia' => 'decimal:3', 'stock_minimo' => 'decimal:3'];
    }
}

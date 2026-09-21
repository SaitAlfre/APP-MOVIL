<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['vista_previa' => 'array', 'errores' => 'array', 'procesada_en' => 'datetime'];
    }
}

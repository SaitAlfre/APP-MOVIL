<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Comunicado extends Model
{
    protected $table = 'comunicados';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['publicar_en' => 'datetime', 'publicado_en' => 'datetime'];
    }
}

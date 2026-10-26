<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class SinRecojo extends Model
{
    protected $table = 'sin_recojos';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'registrada_en' => 'datetime', 'deshecha' => 'boolean',
            'deshecha_en' => 'datetime', 'actualizado_en' => 'integer'];
    }
}

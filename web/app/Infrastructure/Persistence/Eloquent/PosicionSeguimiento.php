<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class PosicionSeguimiento extends Model
{
    protected $table = 'posiciones_seguimiento';

    protected $fillable = ['jornada_id', 'lat', 'lng', 'precision_m', 'capturada_en'];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'precision_m' => 'decimal:2',
            'capturada_en' => 'datetime',
        ];
    }
}

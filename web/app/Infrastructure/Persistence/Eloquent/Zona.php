<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\ZonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected static function newFactory(): ZonaFactory
    {
        return ZonaFactory::new();
    }

    protected $table = 'zonas';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}

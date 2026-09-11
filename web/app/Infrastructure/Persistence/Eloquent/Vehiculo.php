<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\VehiculoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected static function newFactory(): VehiculoFactory
    {
        return VehiculoFactory::new();
    }

    protected $table = 'vehiculos';

    protected $fillable = ['nombre', 'placa', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}

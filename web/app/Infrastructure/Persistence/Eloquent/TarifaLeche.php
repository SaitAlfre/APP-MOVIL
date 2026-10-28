<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\Infrastructure\Persistence\Eloquent\TarifaLecheFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifaLeche extends Model
{
    use HasFactory;

    protected $table = 'tarifas_leche';

    protected $fillable = ['precio', 'desde', 'hasta'];

    protected function casts(): array
    {
        return ['precio' => 'decimal:3', 'desde' => 'date', 'hasta' => 'date'];
    }

    protected static function newFactory(): TarifaLecheFactory
    {
        return TarifaLecheFactory::new();
    }

    public static function vigenteEn(?string $fecha = null): ?self
    {
        $fecha ??= today('America/Lima')->toDateString();

        return self::whereDate('desde', '<=', $fecha)->whereDate('hasta', '>=', $fecha)->orderByDesc('desde')->first();
    }
}

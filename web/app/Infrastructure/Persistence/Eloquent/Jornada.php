<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\JornadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jornada extends Model
{
    use HasFactory;

    protected static function newFactory(): JornadaFactory
    {
        return JornadaFactory::new();
    }

    protected $table = 'jornadas';

    protected $fillable = [
        'usuario_id', 'zona_id', 'vehiculo_id', 'fecha', 'abierta_en', 'cerrada_en', 'seguimiento_activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'abierta_en' => 'datetime',
            'cerrada_en' => 'datetime',
            'seguimiento_activo' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}

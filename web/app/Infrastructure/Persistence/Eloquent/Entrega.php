<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Calidad\CalificarEntregasConAnalisis;
use Database\Factories\EntregaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrega extends Model
{
    use HasFactory;

    protected static function newFactory(): EntregaFactory
    {
        return EntregaFactory::new();
    }

    protected $table = 'entregas';

    protected $fillable = [
        'jornada_id', 'proveedor_id', 'usuario_id', 'zona_id', 'vehiculo_id',
        'litros', 'tachos', 'observaciones', 'registrado_en', 'lote_id', 'anulada', 'uuid_movil', 'version_movil',
    ];

    /** Una entrega que llega después del análisis de calidad del día se califica con él (ver CalificarEntregasConAnalisis). */
    protected static function booted(): void
    {
        static::created(fn (Entrega $entrega) => app(CalificarEntregasConAnalisis::class)->porEntrega($entrega));
    }

    public function revisionMovil(): string
    {
        return hash('sha256', json_encode([
            (int) $this->id, (float) $this->litros, (int) $this->tachos,
            (bool) $this->anulada, $this->observaciones,
        ], JSON_THROW_ON_ERROR));
    }

    protected function casts(): array
    {
        return [
            'litros' => 'decimal:2',
            'registrado_en' => 'datetime',
            'anulada' => 'boolean',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class, 'jornada_id');
    }
}

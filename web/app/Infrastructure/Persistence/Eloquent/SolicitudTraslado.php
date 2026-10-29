<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Database\Factories\SolicitudTrasladoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTraslado extends Model
{
    use HasFactory;

    protected static function newFactory(): SolicitudTrasladoFactory
    {
        return SolicitudTrasladoFactory::new();
    }

    protected $table = 'solicitudes_traslado';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['resuelto_en' => 'datetime', 'fecha_efectiva' => 'date', 'aplicado_en' => 'datetime'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_destino_id');
    }
}

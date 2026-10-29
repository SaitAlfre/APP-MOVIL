<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfraccionCalidad extends Model
{
    protected $table = 'infracciones_calidad';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['semana_inicio' => 'date', 'semana_fin' => 'date', 'confirmado_en' => 'datetime',
            'descuento_litro' => 'decimal:2', 'numero_infraccion' => 'integer'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function analisis(): BelongsTo
    {
        return $this->belongsTo(AnalisisCalidad::class, 'analisis_calidad_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'confirmado_por');
    }
}

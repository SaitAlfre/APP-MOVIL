<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Calidad\ParametrosCalidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Análisis LactoScan de un proveedor (mismo registro que `control_calidad` en la app móvil). */
class AnalisisCalidad extends Model
{
    protected $table = 'analisis_calidad';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoAnalisis::class,
            'alertas' => 'array',
            'visita' => 'array',
            'registrado_en' => 'datetime',
            'volumen_l' => 'float',
            'temperatura' => 'float', 'grasa' => 'float', 'sng' => 'float', 'densidad' => 'float', 'proteina' => 'float',
            'lactosa' => 'float', 'sales' => 'float', 'solidos_totales' => 'float', 'agua_anadida' => 'float',
            'punto_congelacion' => 'float', 'ph' => 'float',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** Controles de las entregas que este análisis calificó. */
    public function controles(): HasMany
    {
        return $this->hasMany(ControlCalidad::class, 'analisis_calidad_id');
    }

    /** @return array<string, float|null> valores por clave de parámetro */
    public function valores(): array
    {
        return collect(ParametrosCalidad::PARAMETROS)->mapWithKeys(fn (array $p, string $clave) => [$clave => $this->{$p[5]}])->all();
    }

    public function unidadCongelacion(): string
    {
        return $this->visita['unidadCongelacion'] ?? '°C';
    }
}

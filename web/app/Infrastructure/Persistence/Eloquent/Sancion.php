<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sancion extends Model
{
    protected $table = 'sanciones';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['descuento' => 'decimal:2', 'resuelto_en' => 'datetime'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function controlCalidad(): BelongsTo
    {
        return $this->belongsTo(ControlCalidad::class);
    }
}

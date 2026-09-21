<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamoProveedor extends Model
{
    protected $table = 'reclamos_proveedor';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['litros_originales' => 'decimal:2', 'litros_solicitados' => 'decimal:2', 'resuelto_en' => 'datetime'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class);
    }
}

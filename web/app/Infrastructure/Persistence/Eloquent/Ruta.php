<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruta extends Model
{
    protected $table = 'rutas';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }
}

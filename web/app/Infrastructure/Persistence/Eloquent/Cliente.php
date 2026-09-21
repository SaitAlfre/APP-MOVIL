<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}

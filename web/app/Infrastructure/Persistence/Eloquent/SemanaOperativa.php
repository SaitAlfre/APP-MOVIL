<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class SemanaOperativa extends Model
{
    protected $table = 'semanas_operativas';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['inicio' => 'date', 'fin' => 'date'];
    }
}

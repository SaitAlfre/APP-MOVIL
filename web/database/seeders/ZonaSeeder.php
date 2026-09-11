<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Seeder;

class ZonaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Zona Norte', 'Zona Sur', 'Zona Centro'] as $nombre) {
            Zona::query()->firstOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}

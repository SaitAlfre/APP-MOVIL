<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Database\Seeder;

class OperadorSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $username = env('ACOPIADOR_SEED_USERNAME', 'acopiador1');
        $pin = env('ACOPIADOR_SEED_PIN', '1234');

        Usuario::query()->firstOrCreate(
            ['username' => $username],
            ['nombres' => 'Acopiador de Prueba', 'dni' => '87654321', 'pin_hash' => $pin, 'activo' => true, 'roles' => ['acopiador']],
        );

        $this->command?->info("Acopiador de desarrollo: {$username} / PIN {$pin}");
    }
}

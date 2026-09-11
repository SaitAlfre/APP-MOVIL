<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = [
            'username' => env('ADMIN_SEED_USERNAME', 'admin'),
            'pin' => env('ADMIN_SEED_PIN', '0000'),
        ];
        $acopiador = [
            'username' => env('ACOPIADOR_SEED_USERNAME', 'acopiador1'),
            'pin' => env('ACOPIADOR_SEED_PIN', '1234'),
        ];
        $proveedor = [
            'username' => env('PROVEEDOR_SEED_USERNAME', 'proveedor1'),
            'pin' => env('PROVEEDOR_SEED_PIN', '1234'),
        ];

        Usuario::query()->firstOrCreate(
            ['username' => $admin['username']],
            ['nombres' => 'Administrador Ecolecta', 'dni' => '00000000', 'pin_hash' => $admin['pin'], 'activo' => true, 'roles' => ['admin']],
        );

        Usuario::query()->firstOrCreate(
            ['username' => $acopiador['username']],
            ['nombres' => 'Acopiador de Prueba', 'dni' => '87654321', 'pin_hash' => $acopiador['pin'], 'activo' => true, 'roles' => ['acopiador']],
        );

        Usuario::query()->firstOrCreate(
            ['username' => $proveedor['username']],
            ['nombres' => 'Proveedor de Prueba', 'dni' => '11223344', 'pin_hash' => $proveedor['pin'], 'activo' => true, 'roles' => ['proveedor']],
        );

        $this->command?->info("Admin de desarrollo: {$admin['username']} / PIN {$admin['pin']}");
        $this->command?->info("Acopiador de desarrollo: {$acopiador['username']} / PIN {$acopiador['pin']}");
        $this->command?->info("Proveedor de desarrollo: {$proveedor['username']} / PIN {$proveedor['pin']}");
    }
}

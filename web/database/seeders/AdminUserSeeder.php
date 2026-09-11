<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $email = env('ADMIN_SEED_EMAIL', 'admin@ecolecta.test');
        $password = env('ADMIN_SEED_PASSWORD', 'password');

        AdminUser::query()->firstOrCreate(
            ['email' => $email],
            ['nombres' => 'Administrador Ecolecta', 'password' => $password, 'activo' => true],
        );

        $this->command?->info("Admin de desarrollo: {$email} / {$password}");
    }
}

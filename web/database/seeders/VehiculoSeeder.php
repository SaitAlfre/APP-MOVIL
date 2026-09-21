<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use Illuminate\Database\Seeder;

class VehiculoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['Camioneta 1', 'ABC-123'], ['Camioneta 2', 'XYZ-789'], ['Camioneta 3', 'LMN-456'], ['Camioneta 4', 'QRS-321']] as [$nombre, $placa]) {
            Vehiculo::query()->firstOrCreate(['placa' => $placa], ['nombre' => $nombre, 'activo' => true]);
        }
    }
}

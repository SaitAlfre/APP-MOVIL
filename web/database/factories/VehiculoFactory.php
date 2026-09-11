<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Camioneta '.fake()->unique()->numberBetween(1, 999),
            'placa' => strtoupper(fake()->unique()->bothify('???-###')),
            'activo' => true,
        ];
    }
}

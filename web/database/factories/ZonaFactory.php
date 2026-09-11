<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Zona>
 */
class ZonaFactory extends Factory
{
    protected $model = Zona::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->city(),
            'activo' => true,
        ];
    }
}

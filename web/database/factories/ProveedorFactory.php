<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'codigo' => 'PRV-'.fake()->unique()->numerify('###'),
            'nombres' => fake()->name(),
            'dni' => fake()->unique()->numerify('########'),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'zona_id' => Zona::factory(),
            'tachos' => fake()->numberBetween(1, 5),
            'capacidad_tacho_l' => 40,
            'estado' => 'activo',
        ];
    }
}

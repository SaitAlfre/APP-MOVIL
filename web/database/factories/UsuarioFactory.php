<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'nombres' => fake()->name(),
            'dni' => fake()->unique()->numerify('########'),
            'pin_hash' => '1234',
            'activo' => true,
            'roles' => ['acopiador'],
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}

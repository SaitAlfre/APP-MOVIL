<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}

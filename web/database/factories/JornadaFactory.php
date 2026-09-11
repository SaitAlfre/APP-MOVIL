<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jornada>
 */
class JornadaFactory extends Factory
{
    protected $model = Jornada::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'zona_id' => Zona::factory(),
            'vehiculo_id' => Vehiculo::factory(),
            'fecha' => now()->toDateString(),
            'abierta_en' => now(),
            'cerrada_en' => null,
            'seguimiento_activo' => false,
        ];
    }

    public function cerrada(): static
    {
        return $this->state(fn () => ['cerrada_en' => now()]);
    }
}

<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entrega>
 */
class EntregaFactory extends Factory
{
    protected $model = Entrega::class;

    public function definition(): array
    {
        return [
            'jornada_id' => Jornada::factory(),
            'proveedor_id' => Proveedor::factory(),
            'usuario_id' => Usuario::factory(),
            'zona_id' => Zona::factory(),
            'vehiculo_id' => Vehiculo::factory(),
            'litros' => fake()->randomFloat(2, 5, 40),
            'tachos' => fake()->numberBetween(1, 3),
            'observaciones' => null,
            'registrado_en' => now(),
            'lote_id' => null,
            'anulada' => false,
        ];
    }
}

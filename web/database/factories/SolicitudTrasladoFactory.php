<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolicitudTrasladoFactory extends Factory
{
    protected $model = SolicitudTraslado::class;

    public function definition(): array
    {
        return ['uuid_movil' => fake()->uuid(), 'proveedor_id' => Proveedor::factory(),
            'zona_destino_id' => Zona::factory(), 'motivo' => 'Cambio de ruta de prueba', 'estado' => 'PENDIENTE'];
    }
}

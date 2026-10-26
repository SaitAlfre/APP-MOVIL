<?php

namespace Database\Factories\Infrastructure\Persistence\Eloquent;

use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use Illuminate\Database\Eloquent\Factories\Factory;

class TarifaLecheFactory extends Factory
{
    protected $model = TarifaLeche::class;

    public function definition(): array
    {
        return ['precio' => '1.800', 'desde' => '2026-01-01', 'hasta' => '2026-12-31'];
    }
}

<?php

namespace App\Application\Liquidaciones;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use DateTimeImmutable;
use RuntimeException;

final class CalcularImporteTemporadas
{
    public function __construct(private readonly EntregaRepositoryInterface $entregas) {}

    public function tarifas(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        return TarifaLeche::whereDate('desde', '<=', $hasta->format('Y-m-d'))->whereDate('hasta', '>=', $desde->format('Y-m-d'))
            ->orderBy('desde')->get()->map(fn ($t) => ['id' => $t->id, 'precio' => (float) $t->precio, 'desde' => $t->desde->toDateString(), 'hasta' => $t->hasta->toDateString()])->all();
    }

    public function calcular(int $proveedorId, DateTimeImmutable $desde, DateTimeImmutable $hasta, array $tarifas): array
    {
        $detalle = $this->entregas->litrosPorDiaDelProveedor($proveedorId, $desde, $hasta);
        $unidades = 0;
        foreach ($detalle as &$dia) {
            $coinciden = array_values(array_filter($tarifas, fn ($t) => $t['desde'] <= $dia['fecha'] && $t['hasta'] >= $dia['fecha']));
            if (count($coinciden) !== 1) {
                throw new RuntimeException('Configura un único precio por litro para el día '.$dia['fecha'].' antes de liquidar.');
            }
            $dia['precio'] = (float) $coinciden[0]['precio'];
            $dia['tarifaId'] = $coinciden[0]['id'] ?? null;
            $importe = (int) round($dia['litros'] * 100) * (int) round($dia['precio'] * 1000);
            $unidades += $importe;
            $dia['subtotal'] = round($importe / 100000, 2);
        }
        unset($dia);
        $litros = array_sum(array_column($detalle, 'litros'));

        return ['detalle' => $detalle, 'litros' => $litros, 'bruto' => round(array_sum(array_column($detalle, 'subtotal')), 2),
            'precio' => $litros > 0 ? $unidades / 100000 / $litros : 0];
    }
}

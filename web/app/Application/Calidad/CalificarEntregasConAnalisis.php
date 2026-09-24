<?php

namespace App\Application\Calidad;

use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use Carbon\CarbonInterface;

/**
 * Puente entre el análisis por proveedor (como en la app) y el control por entrega que usan producción,
 * recepción y sanciones: un análisis califica las entregas de ese proveedor del mismo día (hora de Perú) que
 * aún no tienen control. Funciona en ambos órdenes: si la entrega llega después del análisis, se califica al
 * registrarse. Un control hecho a mano sobre una entrega nunca se pisa.
 */
class CalificarEntregasConAnalisis
{
    public function porAnalisis(AnalisisCalidad $analisis): void
    {
        [$desde, $hasta] = $this->dia($analisis->registrado_en);
        $analisis->controles()->each(fn (ControlCalidad $control) => $control->update($this->valores($analisis)));

        Entrega::query()->where('proveedor_id', $analisis->proveedor_id)->where('anulada', false)
            ->whereBetween('registrado_en', [$desde, $hasta])
            ->whereNotIn('id', ControlCalidad::query()->select('entrega_id'))
            ->each(fn (Entrega $entrega) => ControlCalidad::query()->create([...$this->valores($analisis), 'entrega_id' => $entrega->id]));
    }

    public function porEntrega(Entrega $entrega): void
    {
        if ($entrega->anulada || ControlCalidad::query()->where('entrega_id', $entrega->id)->exists()) {
            return;
        }
        [$desde, $hasta] = $this->dia($entrega->registrado_en);
        $analisis = AnalisisCalidad::query()->where('proveedor_id', $entrega->proveedor_id)
            ->whereBetween('registrado_en', [$desde, $hasta])->latest('registrado_en')->first();
        if ($analisis !== null) {
            ControlCalidad::query()->create([...$this->valores($analisis), 'entrega_id' => $entrega->id]);
        }
    }

    /** @return array<string, mixed> */
    private function valores(AnalisisCalidad $analisis): array
    {
        return [
            'analisis_calidad_id' => $analisis->id,
            'usuario_id' => $analisis->usuario_id,
            'resultado' => $analisis->estado->resultadoEntrega(),
            'temperatura_c' => $analisis->temperatura !== null ? max(-5, min(60, $analisis->temperatura)) : null,
            'acidez' => null,
            'observaciones' => mb_strimwidth('Análisis '.$analisis->codigo_muestra.' · '.$analisis->estado->etiqueta()
                .($analisis->alertas ? ' · '.implode(' · ', $analisis->alertas) : ''), 0, 255, '…'),
            'evaluado_en' => $analisis->registrado_en,
        ];
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} inicio y fin del día en Perú, en UTC */
    private function dia(CarbonInterface $instante): array
    {
        $local = $instante->copy()->setTimezone('America/Lima');

        return [$local->copy()->startOfDay()->utc(), $local->copy()->endOfDay()->utc()];
    }
}

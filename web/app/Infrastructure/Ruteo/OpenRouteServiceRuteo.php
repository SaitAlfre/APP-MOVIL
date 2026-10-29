<?php

namespace App\Infrastructure\Ruteo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Traza el camino por carretera entre paradas en el orden dado, usando OpenRouteService desde el
 * servidor. No reordena paradas: el acopiador conserva su orden de siempre.
 */
final class OpenRouteServiceRuteo
{
    /** Límite de puntos por consulta del plan público; rutas más largas se piden por tramos. */
    private const int PARADAS_POR_TRAMO = 50;

    private const int PUNTOS_MAXIMOS = 2000;

    public function disponible(): bool
    {
        return filled(config('mapas.ors.clave'));
    }

    /**
     * Devuelve el camino completo y cada tramo entre puntos consecutivos, para poder reemplazar a
     * mano solo el tramo de una trocha que no está en el mapa.
     *
     * @param  list<array{latitud: float, longitud: float}>  $paradas
     * @return array{trazado: list<array{latitud: float, longitud: float}>, tramos: list<array{puntos: list<array{latitud: float, longitud: float}>, distancia_m: float, duracion_s: float}>, distancia_m: float, duracion_s: float}
     */
    public function trazar(array $paradas, string $tipoVehiculo): array
    {
        if (! $this->disponible()) {
            throw new RuntimeException('Falta configurar la clave de OpenRouteService. Puedes dibujar el camino manualmente.');
        }
        if (count($paradas) < 2) {
            throw new RuntimeException('Agrega al menos dos paradas para calcular el camino.');
        }
        $perfil = config('mapas.ors.perfiles.'.$tipoVehiculo) ?? config('mapas.ors.perfiles.camion');
        $tramos = [];

        for ($inicio = 0; $inicio < count($paradas) - 1; $inicio += self::PARADAS_POR_TRAMO - 1) {
            $tramos = [...$tramos, ...$this->consultar($perfil, array_slice($paradas, $inicio, self::PARADAS_POR_TRAMO))];
        }
        $limite = max(20, intdiv(self::PUNTOS_MAXIMOS, count($tramos)));
        $tramos = array_map(fn (array $tramo) => [
            'puntos' => array_map(fn (array $punto) => ['latitud' => round($punto[1], 6), 'longitud' => round($punto[0], 6)], $this->reducir($tramo['coordenadas'], $limite)),
            'distancia_m' => round($tramo['distancia'], 1),
            'duracion_s' => round($tramo['duracion'], 1),
        ], $tramos);
        $trazado = [];
        foreach ($tramos as $tramo) {
            $trazado = [...$trazado, ...($trazado === [] ? $tramo['puntos'] : array_slice($tramo['puntos'], 1))];
        }

        return [
            'trazado' => $trazado,
            'tramos' => $tramos,
            'distancia_m' => round(array_sum(array_column($tramos, 'distancia_m')), 1),
            'duracion_s' => round(array_sum(array_column($tramos, 'duracion_s')), 1),
        ];
    }

    /**
     * @param  list<array{latitud: float, longitud: float}>  $paradas
     * @return list<array{coordenadas: list<array{float, float}>, distancia: float, duracion: float}>
     */
    private function consultar(string $perfil, array $paradas): array
    {
        try {
            $respuesta = Http::withHeaders(['Authorization' => config('mapas.ors.clave'), 'Accept' => 'application/geo+json, application/json'])
                ->timeout(20)->retry(1, 500, throw: false)
                ->post(rtrim(config('mapas.ors.url'), '/').'/v2/directions/'.$perfil.'/geojson', [
                    'coordinates' => array_map(fn (array $parada) => [(float) $parada['longitud'], (float) $parada['latitud']], $paradas),
                    // Viviendas rurales lejos de la vía: se acepta buscar el camino hasta 1 km de cada parada.
                    'radiuses' => array_fill(0, count($paradas), 1000),
                    'instructions' => false,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con OpenRouteService. Conservamos tus paradas; intenta de nuevo o dibuja el camino.');
        }

        if ($respuesta->status() === 429 || $respuesta->status() === 403) {
            throw new RuntimeException('OpenRouteService no aceptó la consulta (límite o clave). Intenta más tarde o dibuja el camino manualmente.');
        }
        $ruta = $respuesta->json('features.0');
        $coordenadas = $ruta['geometry']['coordinates'] ?? null;
        if (! $respuesta->successful() || ! is_array($coordenadas) || count($coordenadas) < 2) {
            throw new RuntimeException('No se encontró un camino por carretera entre las paradas. Revisa sus ubicaciones o dibuja a mano el tramo rural.');
        }
        $cortes = $ruta['properties']['way_points'] ?? [0, count($coordenadas) - 1];
        $segmentos = $ruta['properties']['segments'] ?? [];
        $tramos = [];
        for ($i = 0; $i < count($cortes) - 1; $i++) {
            $tramos[] = [
                'coordenadas' => array_slice($coordenadas, $cortes[$i], $cortes[$i + 1] - $cortes[$i] + 1),
                'distancia' => (float) ($segmentos[$i]['distance'] ?? 0),
                'duracion' => (float) ($segmentos[$i]['duration'] ?? 0),
            ];
        }

        return $tramos;
    }

    /**
     * @param  list<array{float, float}>  $puntos
     * @return list<array{float, float}>
     */
    private function reducir(array $puntos, int $limite): array
    {
        if (count($puntos) <= $limite) {
            return $puntos;
        }
        $paso = (count($puntos) - 1) / ($limite - 1);

        return array_map(fn (int $i) => $puntos[(int) round($i * $paso)], range(0, $limite - 1));
    }
}

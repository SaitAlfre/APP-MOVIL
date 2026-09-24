<?php

namespace App\Application\Calidad;

use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Calidad\ParametrosCalidad;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Guarda un análisis LactoScan hecho en el panel o recibido de la app. La clave es `uuid` (el mismo id en el
 * celular), así que reenviarlo actualiza el mismo registro. El estado y las alertas se calculan con las
 * reglas compartidas (ParametrosCalidad); un análisis de la app conserva el estado que se le dio allí.
 */
class GuardarAnalisisCalidadUseCase
{
    public function __construct(private readonly CalificarEntregasConAnalisis $calificar) {}

    /**
     * @param  array{uuid?: string|null, proveedor_id: int, registrado_en: CarbonImmutable, valores: array<string, float|null>,
     *     unidad_congelacion?: string, serial?: string|null, modo?: string|null, observaciones?: string|null,
     *     origen?: string, texto_comprobante?: string|null, codigo_muestra?: string|null, lote_recipiente?: string|null,
     *     volumen_l?: float|null, apariencia?: string|null, estado?: EstadoAnalisis|null, alertas?: list<string>|null,
     *     visita?: array<string, mixed>}  $datos
     */
    public function ejecutar(array $datos, Usuario $tecnico): AnalisisCalidad
    {
        $valores = collect(array_keys(ParametrosCalidad::PARAMETROS))->mapWithKeys(fn (string $clave) => [$clave => $datos['valores'][$clave] ?? null])->all();
        if (collect($valores)->every(fn ($valor) => $valor === null)) {
            throw new InvalidArgumentException('Ingresa al menos un resultado del análisis antes de guardar.');
        }
        foreach ($valores as $clave => $valor) {
            if ($valor !== null && (! is_finite($valor) || (! ParametrosCalidad::permiteNegativo($clave) && $valor < 0))) {
                throw new InvalidArgumentException('Revisa '.ParametrosCalidad::nombre($clave).': usa solo números positivos.');
            }
        }

        $unidad = ($datos['unidad_congelacion'] ?? '°C') === '°H' ? '°H' : '°C';
        $evaluacion = ParametrosCalidad::evaluar($valores, $unidad);
        $proveedor = Proveedor::query()->with('zona')->findOrFail($datos['proveedor_id']);
        $uuid = $datos['uuid'] ?? (string) Str::uuid();
        $existente = AnalisisCalidad::query()->where('uuid', $uuid)->first();
        $ahora = now();

        $columnas = collect(ParametrosCalidad::PARAMETROS)->mapWithKeys(fn (array $p, string $clave) => [$p[5] => $valores[$clave]])->all();
        $visita = [
            ...($existente?->visita ?? []),
            ...($datos['visita'] ?? []),
            'proveedorNombre' => $proveedor->nombres, 'proveedorCodigo' => $proveedor->codigo,
            'zonaNombre' => $proveedor->zona?->nombre ?? '', 'unidadCongelacion' => $unidad,
            'referencias' => $evaluacion['referencias'], 'parametrosAlertados' => $evaluacion['alertados'],
            'parametrosCorrectos' => $evaluacion['correctos'],
        ];
        $visita['tecnicoNombre'] = ($visita['tecnicoNombre'] ?? '') ?: $tecnico->nombres;
        $visita['creadaEn'] ??= $datos['registrado_en']->getTimestampMs();
        $visita['confirmadaEn'] ??= $ahora->getTimestampMs();
        $visita['confirmadoNombre'] = ($visita['confirmadoNombre'] ?? '') ?: $tecnico->nombres;

        return DB::transaction(function () use ($datos, $existente, $uuid, $proveedor, $tecnico, $columnas, $evaluacion, $visita): AnalisisCalidad {
            $analisis = $existente ?? new AnalisisCalidad(['uuid' => $uuid, 'usuario_id' => $tecnico->id]);
            $analisis->fill([
                ...$columnas,
                'proveedor_id' => $proveedor->id,
                'codigo_muestra' => $existente?->codigo_muestra ?? ($datos['codigo_muestra'] ?? null) ?: 'AN-'.strtoupper(substr($uuid, 0, 10)),
                'lote_recipiente' => $datos['lote_recipiente'] ?? null,
                'volumen_l' => $datos['volumen_l'] ?? null,
                'origen_captura' => ($datos['origen'] ?? 'MANUAL') === 'ESCANER' ? 'ESCANER' : 'MANUAL',
                'serial_analizador' => ($datos['serial'] ?? null) ?: null,
                'modo_analizador' => ($datos['modo'] ?? null) ?: null,
                'apariencia' => $datos['apariencia'] ?? null,
                'observaciones' => trim((string) ($datos['observaciones'] ?? '')) ?: null,
                'estado' => $datos['estado'] ?? $evaluacion['estado'],
                'alertas' => $datos['alertas'] ?? $evaluacion['alertas'],
                'texto_comprobante' => $datos['texto_comprobante'] ?? null,
                'visita' => $visita,
                'registrado_en' => $datos['registrado_en'],
            ])->save();
            $this->calificar->porAnalisis($analisis);

            return $analisis;
        });
    }
}

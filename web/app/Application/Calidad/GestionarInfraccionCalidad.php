<?php

namespace App\Application\Calidad;

use App\Application\Liquidaciones\CalcularCastigoCalidadSemanal;
use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Proveedores\EstadoProveedor;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GestionarInfraccionCalidad
{
    public function __construct(
        private readonly CambiarEstadoProveedorUseCase $cambiarEstado,
        private readonly CalcularCastigoCalidadSemanal $castigos,
    ) {}

    /** @return array{inicio: string, fin: string} */
    public static function semana(AnalisisCalidad $analisis): array
    {
        $dias = ['lunes' => 1, 'martes' => 2, 'miércoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sábado' => 6, 'domingo' => 0];
        $configurado = Configuracion::whereKey('inicio_semana')->value('valor') ?? 'jueves';
        $fecha = CarbonImmutable::instance($analisis->registrado_en)->setTimezone('America/Lima');
        $inicio = $fecha->startOfWeek($dias[$configurado] ?? 4);

        return ['inicio' => $inicio->toDateString(), 'fin' => $inicio->addDays(6)->toDateString()];
    }

    public function registrar(AnalisisCalidad $analisis, Usuario $actor, string $motivo): InfraccionCalidad
    {
        abort_unless($actor->puede('calidad', 'crear'), 403);

        return DB::transaction(function () use ($analisis, $actor, $motivo): InfraccionCalidad {
            Proveedor::whereKey($analisis->proveedor_id)->lockForUpdate()->firstOrFail();
            $analisis = AnalisisCalidad::whereKey($analisis->id)->lockForUpdate()->firstOrFail();
            $existente = InfraccionCalidad::where('analisis_calidad_id', $analisis->id)->first();
            if ($existente !== null) {
                return $existente;
            }
            if ($analisis->estado === EstadoAnalisis::Aprobado) {
                throw ValidationException::withMessages(['motivo' => 'Un análisis aprobado no genera una incidencia de adulteración. Registra la prueba que sustenta el hallazgo.']);
            }
            $incidencia = InfraccionCalidad::create([
                'analisis_calidad_id' => $analisis->id, 'proveedor_id' => $analisis->proveedor_id,
                'zona_id' => $analisis->proveedor->zona_id, 'registrado_por' => $actor->id,
                'motivo' => trim($motivo), 'estado' => 'pendiente',
                'semana_inicio' => self::semana($analisis)['inicio'], 'semana_fin' => self::semana($analisis)['fin'],
            ]);
            $this->auditar($incidencia, $actor, null);

            return $incidencia;
        });
    }

    public function resolver(InfraccionCalidad $incidencia, Usuario $actor, string $decision, string $respuesta, ?float $descuento): InfraccionCalidad
    {
        abort_unless($actor->tieneRol('admin'), 403);
        if (! in_array($decision, ['confirmar', 'descartar'], true) || mb_strlen(trim($respuesta)) < 10 || mb_strlen($respuesta) > 1000) {
            throw ValidationException::withMessages(['respuesta' => 'Selecciona la decisión y explica su fundamento (10 a 1000 caracteres).']);
        }
        if ($decision === 'confirmar' && ($descuento === null || ! is_finite($descuento) || $descuento < 0.20 || $descuento > 0.50 || abs($descuento * 100 - round($descuento * 100)) > 0.00001)) {
            throw ValidationException::withMessages(['descuento_litro' => 'El descuento debe ser de S/ 0,20 a S/ 0,50 por litro, con hasta dos decimales.']);
        }

        return DB::transaction(function () use ($incidencia, $actor, $decision, $respuesta, $descuento): InfraccionCalidad {
            $proveedor = Proveedor::whereKey($incidencia->proveedor_id)->lockForUpdate()->firstOrFail();
            $incidencia = InfraccionCalidad::whereKey($incidencia->id)->lockForUpdate()->firstOrFail();
            $destino = $decision === 'confirmar' ? 'confirmada' : 'descartada';
            if ($incidencia->estado === $destino) {
                return $incidencia;
            }
            if ($incidencia->estado !== 'pendiente') {
                throw ValidationException::withMessages(['decision' => 'Este caso ya tiene una decisión registrada.']);
            }
            $antes = $incidencia->getAttributes();
            $datos = ['estado' => $destino, 'respuesta' => trim($respuesta), 'confirmado_por' => $actor->id, 'confirmado_en' => now()];
            if ($decision === 'confirmar') {
                if ($incidencia->zona_id === null) {
                    throw ValidationException::withMessages(['decision' => 'El control no tiene una zona identificada. Revisa su origen antes de confirmar y notificar.']);
                }
                if ($incidencia->analisis->registrado_en->isFuture()) {
                    throw ValidationException::withMessages(['decision' => 'No se puede confirmar una prueba con fecha futura.']);
                }
                $semana = $incidencia->semana_inicio !== null && $incidencia->semana_fin !== null
                    ? ['inicio' => $incidencia->semana_inicio->toDateString(), 'fin' => $incidencia->semana_fin->toDateString()]
                    : self::semana($incidencia->analisis);
                $pagadas = Liquidacion::where('proveedor_id', $proveedor->id)->where('estado', 'pagada')
                    ->whereDate('periodo_inicio', '<=', $semana['fin'])->whereDate('periodo_fin', '>=', $semana['inicio'])->lockForUpdate()->exists();
                if ($pagadas) {
                    throw ValidationException::withMessages(['decision' => 'La semana incluye una liquidación ya pagada. Se requiere resolver el ajuste por separado antes de confirmar el castigo.']);
                }
                $numero = InfraccionCalidad::where('proveedor_id', $proveedor->id)->where('estado', 'confirmada')->count() + 1;
                $datos += ['descuento_litro' => $descuento, 'semana_inicio' => $semana['inicio'], 'semana_fin' => $semana['fin'], 'numero_infraccion' => $numero];
            }
            $incidencia->update($datos);
            if ($decision === 'confirmar') {
                if ($incidencia->numero_infraccion >= 3) {
                    $this->cambiarEstado->ejecutar($proveedor->id, EstadoProveedor::Retirado, 'Tercera infracción de adulteración confirmada. Caso #'.$incidencia->id.': '.$respuesta, $actor->id);
                }
                $this->castigos->recalcularPendientes($proveedor->id, $incidencia->semana_inicio->toDateString(), $incidencia->semana_fin->toDateString());
                $this->avisar($incidencia, $proveedor, $actor);
            }
            $this->auditar($incidencia, $actor, $antes);

            return $incidencia;
        });
    }

    private function avisar(InfraccionCalidad $incidencia, Proveedor $proveedor, Usuario $actor): void
    {
        $periodo = $incidencia->semana_inicio->format('d/m/Y').' al '.$incidencia->semana_fin->format('d/m/Y');
        $texto = 'Se confirmó adulteración en el control sorpresa de '.$proveedor->nombres
            .' del '.$incidencia->analisis->registrado_en->setTimezone('America/Lima')->format('d/m/Y')
            .'. Infracción acumulada: '.$incidencia->numero_infraccion.'. Descuento: S/ '
            .number_format((float) $incidencia->descuento_litro, 2).' por litro en la semana del '.$periodo
            .'. Si hay varios casos en la semana se aplica el descuento mayor, sin sumarlos.'
            .($incidencia->numero_infraccion >= 3 ? ' El proveedor fue retirado al alcanzar la tercera infracción.' : '');
        $comun = ['infraccion_calidad_id' => $incidencia->id, 'audiencia' => 'proveedores', 'estado' => 'publicado', 'publicado_en' => now(), 'autor_id' => $actor->id];
        Comunicado::create($comun + ['codigo' => 'CAL-'.$incidencia->id.'-IND', 'titulo' => 'Resultado de tu control de calidad',
            'contenido' => $texto, 'proveedor_id' => $proveedor->id]);
        Comunicado::create($comun + ['codigo' => 'CAL-'.$incidencia->id.'-ZONA', 'titulo' => 'Control de calidad: '.$proveedor->nombres,
            'contenido' => 'Se confirmó adulteración en el control sorpresa de '.$proveedor->nombres
                .' del '.$incidencia->analisis->registrado_en->setTimezone('America/Lima')->format('d/m/Y')
                .'. Este aviso se dirige a los proveedores de su zona para informar el resultado del control de calidad.',
            'zona_id' => $incidencia->zona_id,
            'destinatarios_proveedor' => Proveedor::where('zona_id', $incidencia->zona_id)->pluck('id')->map(fn ($id) => (int) $id)->all()]);
    }

    private function auditar(InfraccionCalidad $incidencia, Usuario $actor, ?array $antes): void
    {
        Auditoria::create(['entidad' => 'infraccion_calidad', 'entidad_id' => $incidencia->id, 'usuario_id' => $actor->id,
            'accion' => $antes === null ? 'crear' : 'actualizar', 'valor_antes' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
            'valor_despues' => json_encode($incidencia->getAttributes(), JSON_UNESCAPED_UNICODE),
            'motivo' => $incidencia->respuesta ?: $incidencia->motivo, 'ocurrido_en' => now()]);
    }
}

<?php

namespace App\Application\Proveedores;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Traslado de zona con aviso: el proveedor lo pide con 3 días de anticipación, administración lo
 * aprueba y el cambio se aplica en la fecha efectiva. Hasta ese día sigue en su ruta actual, así no
 * se desordenan los recojos ni la liquidación de la semana.
 */
final class TrasladoZonaProveedor
{
    public const int DIAS_AVISO = 3;

    public function __construct(private readonly ActualizarProveedorUseCase $actualizarProveedor) {}

    public static function hoy(): CarbonImmutable
    {
        return CarbonImmutable::now('America/Lima')->startOfDay();
    }

    /** Primera fecha en la que puede aplicarse una solicitud según el aviso mínimo. */
    public static function fechaMinima(SolicitudTraslado $solicitud): CarbonImmutable
    {
        $pedida = CarbonImmutable::parse($solicitud->created_at)->setTimezone('America/Lima')->startOfDay();

        return $pedida->addDays(self::DIAS_AVISO);
    }

    /** Aplica ahora si la fecha efectiva ya llegó; si no, el traslado queda programado. */
    public function aplicarSiCorresponde(SolicitudTraslado $solicitud, int $autorId): bool
    {
        if ($solicitud->estado !== 'APROBADA' || $solicitud->aplicado_en !== null
            || $solicitud->fecha_efectiva === null || CarbonImmutable::parse($solicitud->fecha_efectiva->toDateString(), 'America/Lima')->greaterThan(self::hoy())) {
            return false;
        }

        DB::transaction(function () use ($solicitud, $autorId): void {
            $proveedor = Proveedor::query()->lockForUpdate()->findOrFail($solicitud->proveedor_id);
            $anterior = $proveedor->zona_id;
            $this->actualizarProveedor->ejecutar($proveedor->id, new DatosProveedor(
                codigo: $proveedor->codigo, nombres: $proveedor->nombres, dni: $proveedor->dni,
                telefono: $proveedor->telefono, direccion: $proveedor->direccion, zonaId: $solicitud->zona_destino_id,
                tachos: $proveedor->tachos, capacidadTachoL: (float) $proveedor->capacidad_tacho_l,
            ));
            $this->moverEntreRutas($proveedor->fresh(), $anterior, $solicitud->zona_destino_id);
            Auditoria::query()->create([
                'entidad' => 'proveedor', 'entidad_id' => $proveedor->id, 'usuario_id' => $autorId,
                'accion' => 'autorizar', 'ocurrido_en' => now(), 'motivo' => $solicitud->motivo,
                'valor_antes' => "zona={$anterior}", 'valor_despues' => "zona={$solicitud->zona_destino_id}",
            ]);
            $solicitud->update(['aplicado_en' => now()]);
        });

        return true;
    }

    /** Aplica los traslados aprobados cuya fecha efectiva ya llegó. Lo ejecuta la tarea diaria. */
    public function aplicarProgramados(): int
    {
        return SolicitudTraslado::query()->where('estado', 'APROBADA')->whereNull('aplicado_en')
            ->whereDate('fecha_efectiva', '<=', self::hoy()->toDateString())->get()
            ->filter(fn (SolicitudTraslado $solicitud) => $this->aplicarSiCorresponde($solicitud, (int) ($solicitud->resuelto_por ?? 0) ?: $this->autorSistema()))
            ->count();
    }

    /** Sale de las rutas de su zona anterior y entra al final de la primera ruta activa de la nueva zona. */
    private function moverEntreRutas(Proveedor $proveedor, int $zonaAnterior, int $zonaNueva): void
    {
        Ruta::query()->where('zona_id', $zonaAnterior)->lockForUpdate()->get()->each(function (Ruta $ruta) use ($proveedor): void {
            $paradas = array_values(array_filter($ruta->paradas ?? [], fn (array $parada) => ($parada['proveedor_id'] ?? null) !== $proveedor->id));
            if (count($paradas) !== count($ruta->paradas ?? [])) {
                $ruta->update(['paradas' => $paradas, 'mapa_version' => $ruta->mapa_version + 1]);
            }
        });
        $destino = Ruta::query()->where('zona_id', $zonaNueva)->where('activo', true)->orderBy('id')->lockForUpdate()->first();
        if ($destino === null || $proveedor->latitud === null || count($destino->paradas ?? []) >= 100) {
            return;
        }
        $destino->update(['mapa_version' => $destino->mapa_version + 1, 'paradas' => [...($destino->paradas ?? []), [
            'latitud' => (float) $proveedor->latitud, 'longitud' => (float) $proveedor->longitud, 'nombre' => mb_substr($proveedor->nombres, 0, 120),
            'proveedor_id' => $proveedor->id, 'referencia' => mb_substr((string) $proveedor->direccion, 0, 500),
        ]]]);
    }

    private function autorSistema(): int
    {
        return (int) DB::table('usuarios')->whereJsonContains('roles', 'admin')->orderBy('id')->value('id');
    }
}

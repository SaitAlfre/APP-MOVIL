<?php

namespace App\Application\Movil;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\EstadoLiquidacion;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class ResolverReclamoProveedorUseCase
{
    public function __construct(private readonly EntregaRepositoryInterface $entregas) {}

    public function ejecutar(int $id, Usuario $autor, string $estado, string $respuesta, ?int $entregaId = null): ReclamoProveedor
    {
        if (! $autor->puede('sanciones', 'gestionar')) {
            throw CambioMovilRechazadoException::sinPermiso();
        }
        $respuesta = trim($respuesta);
        if (! in_array($estado, ['resuelto', 'rechazado'], true) || $respuesta === '' || mb_strlen($respuesta) > 500) {
            throw new CambioMovilRechazadoException('Selecciona una decisión y escribe una respuesta de hasta 500 caracteres.');
        }

        return DB::transaction(function () use ($id, $autor, $estado, $respuesta, $entregaId): ReclamoProveedor {
            $reclamo = ReclamoProveedor::query()->lockForUpdate()->findOrFail($id);
            if ($reclamo->estado !== 'pendiente') {
                if ($reclamo->estado === $estado) {
                    return $reclamo;
                }
                throw new CambioMovilRechazadoException('El reclamo ya tiene una decisión distinta. Sincroniza para ver el resultado.', 'solicitud_resuelta', 409);
            }
            if ($estado === 'resuelto') {
                Proveedor::query()->whereKey($reclamo->proveedor_id)->lockForUpdate()->first();
                $entrega = Entrega::query()->with('proveedor')->lockForUpdate()->find($reclamo->entrega_id ?? $entregaId);
                if ($entrega === null || $entrega->proveedor_id !== $reclamo->proveedor_id) {
                    throw new CambioMovilRechazadoException('Para aprobar este reclamo, registra o selecciona una entrega del mismo proveedor en la web.', 'entrega_requerida');
                }
                if ($entrega->anulada) {
                    throw new CambioMovilRechazadoException('No se pueden ajustar litros de una entrega anulada.');
                }
                $litros = (float) $reclamo->litros_solicitados;
                if (! is_finite($litros) || $litros <= 0 || $litros > 18500) {
                    throw new CambioMovilRechazadoException('Ingresa una cantidad válida de hasta 18 500 litros.');
                }
                $fecha = $entrega->registrado_en->copy()->setTimezone('America/Lima')->toDateString();
                $liquidaciones = Liquidacion::query()->where('proveedor_id', $reclamo->proveedor_id)
                    ->whereDate('periodo_inicio', '<=', $fecha)->whereDate('periodo_fin', '>=', $fecha)
                    ->orderBy('id')->lockForUpdate()->get();
                if ($liquidaciones->contains(fn (Liquidacion $l) => $l->estado === EstadoLiquidacion::Pagada)) {
                    throw new CambioMovilRechazadoException('Esta entrega ya tiene una liquidación pagada. No se pueden modificar sus litros.', 'liquidacion_pagada');
                }
                $anteriores = (float) $entrega->litros;
                $entrega->update(['litros' => $litros]);
                $reclamo->entrega_id = $entrega->id;
                $reclamo->litros_originales = $anteriores;
                Auditoria::query()->create([
                    'entidad' => 'entrega', 'entidad_id' => $entrega->id, 'usuario_id' => $autor->id,
                    'accion' => 'corregir', 'valor_antes' => "litros={$anteriores}", 'valor_despues' => "litros={$litros}",
                    'motivo' => "Reclamo #{$reclamo->id}: {$respuesta}", 'ocurrido_en' => now(),
                ]);
                foreach ($liquidaciones as $liquidacion) {
                    $totalLitros = $this->entregas->litrosPorProveedorEnRango(
                        $reclamo->proveedor_id, DateTimeImmutable::createFromInterface($liquidacion->periodo_inicio),
                        DateTimeImmutable::createFromInterface($liquidacion->periodo_fin),
                    );
                    $bruto = round($totalLitros * (float) $liquidacion->precio_litro, 2);
                    $sanciones = (float) Sancion::query()->where('aplicada_liquidacion_id', $liquidacion->id)->sum('descuento');
                    $descuento = min($sanciones > 0 ? $sanciones : (float) $liquidacion->descuento_sanciones, $bruto);
                    $liquidacion->update([
                        'litros_totales' => $totalLitros, 'descuento_sanciones' => $descuento,
                        'monto_total' => round($bruto - $descuento, 2),
                    ]);
                }
            }
            $reclamo->fill(['estado' => $estado, 'respuesta' => $respuesta, 'resuelto_por' => $autor->id, 'resuelto_en' => now()])->save();

            return $reclamo;
        });
    }
}

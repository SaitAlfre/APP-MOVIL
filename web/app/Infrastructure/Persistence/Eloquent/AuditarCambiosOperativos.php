<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria as Registro;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

class AuditarCambiosOperativos
{
    /** Solo campos operativos: nunca se serializa el modelo completo ni el request. */
    private const CAMPOS = [
        ControlCalidad::class => ['control_calidad', ['entrega_id', 'resultado', 'temperatura_c', 'acidez', 'observaciones', 'evaluado_en']],
        Liquidacion::class => ['liquidacion', ['proveedor_id', 'periodo_inicio', 'periodo_fin', 'litros_totales', 'precio_litro', 'monto_total', 'estado', 'pagada_en']],
        Producto::class => ['producto', ['nombre', 'presentacion', 'unidad_produccion', 'litros_por_unidad', 'otros_insumos', 'existencia', 'activo']],
        MovimientoProducto::class => ['movimiento_producto', ['producto_id', 'tipo', 'cantidad', 'unidad', 'motivo', 'fecha']],
        Sancion::class => ['sancion', ['control_calidad_id', 'proveedor_id', 'tipo', 'severidad', 'descuento', 'motivo', 'estado', 'resuelto_en']],
        ReclamoProveedor::class => ['reclamo_proveedor', ['proveedor_id', 'entrega_id', 'litros_originales', 'litros_solicitados', 'motivo', 'estado', 'respuesta', 'resuelto_en']],
        Cliente::class => ['cliente', ['codigo', 'tipo', 'nombre', 'documento', 'ciudad', 'activo']],
        Venta::class => ['venta', ['codigo', 'cliente_id', 'subtotal', 'descuento', 'total', 'estado', 'vendida_en']],
        Comunicado::class => ['comunicado', ['codigo', 'titulo', 'audiencia', 'estado', 'publicar_en', 'publicado_en']],
        Importacion::class => ['importacion', ['tipo', 'archivo_original', 'estado', 'filas_total', 'filas_procesadas', 'filas_error', 'procesada_en']],
        Ruta::class => ['ruta', ['codigo', 'nombre', 'zona_id', 'activo']],
        Zona::class => ['zona', ['nombre', 'activo']],
        Vehiculo::class => ['vehiculo', ['nombre', 'placa', 'activo']],
        Proveedor::class => ['proveedor', ['codigo', 'nombres', 'zona_id', 'estado', 'tachos', 'capacidad_tacho_l', 'usuario_id']],
        Jornada::class => ['jornada', ['usuario_id', 'vehiculo_id', 'zona_id', 'fecha', 'abierta_en', 'cerrada_en']],
    ];

    public function created(Model $modelo): void
    {
        $this->registrar($modelo, true);
    }

    public function updated(Model $modelo): void
    {
        $this->registrar($modelo, false);
    }

    private function registrar(Model $modelo, bool $creado): void
    {
        $actorId = auth('operador')->id();
        if ($actorId === null) {
            return;
        }
        [$entidad, $campos] = self::CAMPOS[$modelo::class];
        $campos = $creado ? $campos : array_values(array_intersect($campos, array_keys($modelo->getChanges())));
        if ($campos === []) {
            return;
        }
        $antes = $creado ? null : array_intersect_key($modelo->getRawOriginal(), array_flip($campos));
        $despues = array_intersect_key($modelo->getAttributes(), array_flip($campos));
        app(AuditoriaRepositoryInterface::class)->registrar(new Registro(
            id: null, entidad: $entidad, entidadId: (int) $modelo->getKey(),
            accion: $creado ? AccionAuditoria::Crear : AccionAuditoria::Actualizar,
            valorAntes: $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
            valorDespues: json_encode($despues, JSON_UNESCAPED_UNICODE),
            motivo: $modelo->getAttribute('motivo') ?? $modelo->getAttribute('observaciones'),
            usuarioId: (int) $actorId, ocurridoEn: new DateTimeImmutable,
        ));
    }
}

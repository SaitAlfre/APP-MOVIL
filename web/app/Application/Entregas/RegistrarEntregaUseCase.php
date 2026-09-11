<?php

namespace App\Application\Entregas;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Entregas\Entrega;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

/**
 * Guardado nunca bloqueante por red: el web escribe directo a MySQL. Bloquea si supera la
 * capacidad física del tacho, y marca una advertencia no bloqueante si la cantidad se desvía
 * más de 40% del promedio de las últimas 7 entregas del proveedor. Misma regla que el móvil
 * (ver RegistrarEntregaUseCase.kt).
 */
final class RegistrarEntregaUseCase
{
    private const float UMBRAL_DESVIACION = 0.4;

    private const int ENTREGAS_PARA_PROMEDIO = 7;

    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(
        int $jornadaId,
        int $proveedorId,
        int $usuarioId,
        int $zonaId,
        int $vehiculoId,
        float $litros,
        int $tachos,
        ?string $observaciones,
        ?string $loteId = null,
    ): ResultadoRegistroEntrega {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor === null) {
            throw new RuntimeException('Proveedor no encontrado.');
        }

        if ($proveedor->estado !== EstadoProveedor::Activo) {
            throw EntregaInvalidaException::proveedorNoActivo($proveedor->estado);
        }

        if ($litros > $proveedor->capacidadTotalL()) {
            throw EntregaInvalidaException::superaCapacidad($proveedor->capacidadTotalL());
        }

        $ahora = new DateTimeImmutable;

        $entrega = Entrega::crear(
            jornadaId: $jornadaId,
            proveedorId: $proveedorId,
            usuarioId: $usuarioId,
            zonaId: $zonaId,
            vehiculoId: $vehiculoId,
            litros: $litros,
            tachos: $tachos,
            observaciones: $observaciones,
            registradoEn: $ahora,
            loteId: $loteId,
        );

        $anteriores = $this->entregas->ultimasDelProveedor($proveedorId, self::ENTREGAS_PARA_PROMEDIO);
        $promedio = $anteriores !== [] ? array_sum(array_map(fn (Entrega $e) => $e->litros, $anteriores)) / count($anteriores) : 0.0;
        $advertencia = $promedio > 0.0 && abs($litros - $promedio) / $promedio > self::UMBRAL_DESVIACION;

        $auditoria = new Auditoria(
            id: null,
            entidad: 'entrega',
            entidadId: 0,
            accion: AccionAuditoria::Crear,
            valorAntes: null,
            valorDespues: "litros={$litros};tachos={$tachos}",
            motivo: null,
            usuarioId: $usuarioId,
            ocurridoEn: $ahora,
        );

        $guardada = $this->entregas->registrar($entrega, $auditoria);

        return new ResultadoRegistroEntrega($guardada, $advertencia);
    }
}

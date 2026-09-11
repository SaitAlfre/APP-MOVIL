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
use Illuminate\Support\Str;
use RuntimeException;

/** Todas las entregas del lote comparten un lote_id y se guardan en una única transacción: si una falla, ninguna queda. */
final class RegistrarLoteUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    /**
     * @param  list<ItemLote>  $items
     * @return list<Entrega>
     */
    public function ejecutar(int $jornadaId, int $usuarioId, int $zonaId, int $vehiculoId, array $items): array
    {
        if ($items === []) {
            throw EntregaInvalidaException::loteVacio();
        }

        $loteId = (string) Str::uuid();
        $ahora = new DateTimeImmutable;

        $entregas = [];
        $auditorias = [];

        foreach ($items as $item) {
            $proveedor = $this->proveedores->buscarPorId($item->proveedorId);

            if ($proveedor === null) {
                throw new RuntimeException('Proveedor no encontrado.');
            }

            if ($proveedor->estado !== EstadoProveedor::Activo) {
                throw EntregaInvalidaException::proveedorNoActivo($proveedor->estado);
            }

            if ($item->litros > $proveedor->capacidadTotalL()) {
                throw EntregaInvalidaException::superaCapacidad($proveedor->capacidadTotalL());
            }

            $entregas[] = Entrega::crear(
                jornadaId: $jornadaId,
                proveedorId: $item->proveedorId,
                usuarioId: $usuarioId,
                zonaId: $zonaId,
                vehiculoId: $vehiculoId,
                litros: $item->litros,
                tachos: $item->tachos,
                observaciones: $item->observaciones,
                registradoEn: $ahora,
                loteId: $loteId,
            );

            $auditorias[] = new Auditoria(
                id: null,
                entidad: 'entrega',
                entidadId: 0,
                accion: AccionAuditoria::Crear,
                valorAntes: null,
                valorDespues: "litros={$item->litros};tachos={$item->tachos}",
                motivo: null,
                usuarioId: $usuarioId,
                ocurridoEn: $ahora,
            );
        }

        return $this->entregas->registrarLote($entregas, $auditorias);
    }
}

<?php

namespace App\Domain\Entregas;

use App\Domain\Auditoria\Auditoria;
use DateTimeImmutable;

interface EntregaRepositoryInterface
{
    public function buscarPorId(int $id): ?Entrega;

    /** @return list<Entrega> */
    public function ultimasDelProveedor(int $proveedorId, int $limite): array;

    /** @return list<Entrega> */
    public function deJornadaYProveedor(int $jornadaId, int $proveedorId): array;

    /** @return list<Entrega> */
    public function recientesPorJornada(int $jornadaId, int $limite): array;

    /** @return array{litros: float, entregas: int} */
    public function resumenDelDia(int $jornadaId): array;

    /** @return list<Entrega> */
    public function sinControlCalidad(int $limite = 30): array;

    public function litrosPorProveedorEnRango(int $proveedorId, DateTimeImmutable $desde, DateTimeImmutable $hasta): float;

    /**
     * Litros acumulados en el rango y fecha de la última entrega, por proveedor.
     * Resuelve en una sola consulta los datos que la lista de proveedores muestra por fila.
     *
     * @param  list<int>  $proveedorIds
     * @return array<int, array{litros: float, ultima: ?string}>
     */
    public function resumenPorProveedores(array $proveedorIds, DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    /** @return array{litros: float, entregas: int} */
    /**
     * Litros y entregas por día de un proveedor, para el detalle de su liquidación.
     *
     * @return list<array{fecha: string, litros: float, entregas: int}>
     */
    public function litrosPorDiaDelProveedor(int $proveedorId, DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    public function resumenPorRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    /** @return list<array{zona_id: int, litros: float}> */
    public function litrosPorZonaEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    /** @return array{litros: float, entregas: int, promedio_litros: float, proveedores: int, tachos: int} */
    public function resumenParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;

    /** @return list<array{fecha: string, litros: float, entregas: int}> */
    public function tendenciaParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;

    /** @return list<array{zona: string, litros: float, entregas: int}> */
    public function zonasParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;

    /** @return list<array{acopiador: string, litros: float, entregas: int}> */
    public function acopiadoresParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;

    /** @return array{litros: float, entregas: int} Entregas sin control de calidad todavía, no anuladas, en el rango. */
    public function pendientesCalidadParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;

    /** @return list<array{fecha: string, proveedor_codigo: string, proveedor: string, zona: string, vehiculo: string, placa: string, litros: float, tachos: int}> */
    public function entregasParaReporte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null, ?int $limite = 200): array;

    /** @return list<array{vehiculo_id: int, litros: float}> Solo entregas aprobadas/observadas por Calidad, no anuladas. */
    public function litrosAprobadosPorVehiculoEnFecha(DateTimeImmutable $fecha): array;

    /**
     * Litros habilitados por Calidad menos las mermas de transporte registradas en Recepción de
     * leche (ver App\Domain\Recepcion), agregados por vehículo para el día. Un excedente medido
     * en planta (litros medidos > recolectados) no incrementa este saldo: solo se descuentan
     * mermas, nunca se suman diferencias positivas.
     *
     * @return list<array{vehiculoId: int, vehiculoNombre: string, placa: string, acopiadores: string, litrosRecolectados: float, litrosAprobados: float, merma: float, motivo: string, litros: float}>
     */
    public function recepcionPorVehiculoEnFecha(DateTimeImmutable $fecha): array;

    /** @return list<array{fecha: string, litros: float}> Solo entregas aprobadas/observadas por Calidad, no anuladas. */
    public function litrosAprobadosPorDiaEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    public function litrosAprobadosTotal(): float;

    public function registrar(Entrega $entrega, Auditoria $auditoria): Entrega;

    public function corregir(int $id, float $litros, int $tachos, ?string $observaciones, DateTimeImmutable $ahora, Auditoria $auditoria): void;

    public function anular(int $id, DateTimeImmutable $ahora, Auditoria $auditoria): void;
}

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

    public function registrar(Entrega $entrega, Auditoria $auditoria): Entrega;

    /**
     * @param  list<Entrega>  $entregas
     * @param  list<Auditoria>  $auditorias
     * @return list<Entrega>
     */
    public function registrarLote(array $entregas, array $auditorias): array;

    public function corregir(int $id, float $litros, int $tachos, ?string $observaciones, DateTimeImmutable $ahora, Auditoria $auditoria): void;

    public function anular(int $id, DateTimeImmutable $ahora, Auditoria $auditoria): void;
}

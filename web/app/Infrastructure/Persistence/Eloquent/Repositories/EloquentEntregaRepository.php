<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Auditoria\Auditoria as AuditoriaDominio;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Entregas\Entrega as EntregaDominio;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Entrega as EntregaEloquent;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentEntregaRepository implements EntregaRepositoryInterface
{
    public function __construct(
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function buscarPorId(int $id): ?EntregaDominio
    {
        $entrega = EntregaEloquent::query()->find($id);

        return $entrega !== null ? $this->aDominio($entrega) : null;
    }

    public function ultimasDelProveedor(int $proveedorId, int $limite): array
    {
        return EntregaEloquent::query()
            ->where('proveedor_id', $proveedorId)
            ->where('anulada', false)
            ->orderByDesc('registrado_en')
            ->limit($limite)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function deJornadaYProveedor(int $jornadaId, int $proveedorId): array
    {
        return EntregaEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->where('proveedor_id', $proveedorId)
            ->where('anulada', false)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function recientesPorJornada(int $jornadaId, int $limite): array
    {
        return EntregaEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->orderByDesc('registrado_en')
            ->limit($limite)
            ->get()
            ->map(fn (EntregaEloquent $e) => $this->aDominio($e))
            ->all();
    }

    public function resumenDelDia(int $jornadaId): array
    {
        $fila = EntregaEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->where('anulada', false)
            ->selectRaw('COALESCE(SUM(litros), 0) as litros, COUNT(*) as entregas')
            ->first();

        return [
            'litros' => (float) $fila->litros,
            'entregas' => (int) $fila->entregas,
        ];
    }

    public function registrar(EntregaDominio $entrega, AuditoriaDominio $auditoria): EntregaDominio
    {
        return DB::transaction(function () use ($entrega, $auditoria) {
            $registro = $this->crearRegistro($entrega);

            $this->auditorias->registrar(new AuditoriaDominio(
                id: null,
                entidad: $auditoria->entidad,
                entidadId: $registro->id,
                accion: $auditoria->accion,
                valorAntes: $auditoria->valorAntes,
                valorDespues: $auditoria->valorDespues,
                motivo: $auditoria->motivo,
                usuarioId: $auditoria->usuarioId,
                ocurridoEn: $auditoria->ocurridoEn,
            ));

            return $this->aDominio($registro);
        });
    }

    public function registrarLote(array $entregas, array $auditorias): array
    {
        return DB::transaction(function () use ($entregas, $auditorias) {
            $resultado = [];

            foreach ($entregas as $indice => $entrega) {
                $registro = $this->crearRegistro($entrega);
                $auditoria = $auditorias[$indice];

                $this->auditorias->registrar(new AuditoriaDominio(
                    id: null,
                    entidad: $auditoria->entidad,
                    entidadId: $registro->id,
                    accion: $auditoria->accion,
                    valorAntes: $auditoria->valorAntes,
                    valorDespues: $auditoria->valorDespues,
                    motivo: $auditoria->motivo,
                    usuarioId: $auditoria->usuarioId,
                    ocurridoEn: $auditoria->ocurridoEn,
                ));

                $resultado[] = $this->aDominio($registro);
            }

            return $resultado;
        });
    }

    public function corregir(int $id, float $litros, int $tachos, ?string $observaciones, DateTimeImmutable $ahora, AuditoriaDominio $auditoria): void
    {
        DB::transaction(function () use ($id, $litros, $tachos, $observaciones, $auditoria) {
            EntregaEloquent::query()->whereKey($id)->update([
                'litros' => $litros,
                'tachos' => $tachos,
                'observaciones' => $observaciones,
            ]);

            $this->auditorias->registrar($auditoria);
        });
    }

    public function anular(int $id, DateTimeImmutable $ahora, AuditoriaDominio $auditoria): void
    {
        DB::transaction(function () use ($id, $auditoria) {
            EntregaEloquent::query()->whereKey($id)->update(['anulada' => true]);

            $this->auditorias->registrar($auditoria);
        });
    }

    private function crearRegistro(EntregaDominio $entrega): EntregaEloquent
    {
        return EntregaEloquent::query()->create([
            'jornada_id' => $entrega->jornadaId,
            'proveedor_id' => $entrega->proveedorId,
            'usuario_id' => $entrega->usuarioId,
            'zona_id' => $entrega->zonaId,
            'vehiculo_id' => $entrega->vehiculoId,
            'litros' => $entrega->litros,
            'tachos' => $entrega->tachos,
            'observaciones' => $entrega->observaciones,
            'registrado_en' => $entrega->registradoEn,
            'lote_id' => $entrega->loteId,
            'anulada' => false,
        ]);
    }

    private function aDominio(EntregaEloquent $entrega): EntregaDominio
    {
        return EntregaDominio::reconstruir(
            id: $entrega->id,
            jornadaId: $entrega->jornada_id,
            proveedorId: $entrega->proveedor_id,
            usuarioId: $entrega->usuario_id,
            zonaId: $entrega->zona_id,
            vehiculoId: $entrega->vehiculo_id,
            litros: (float) $entrega->litros,
            tachos: $entrega->tachos,
            observaciones: $entrega->observaciones,
            registradoEn: DateTimeImmutable::createFromInterface($entrega->registrado_en),
            loteId: $entrega->lote_id,
            anulada: $entrega->anulada,
        );
    }
}

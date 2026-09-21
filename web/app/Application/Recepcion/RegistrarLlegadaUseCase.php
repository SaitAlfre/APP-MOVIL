<?php

namespace App\Application\Recepcion;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Domain\Recepcion\Exceptions\RecepcionInvalidaException;
use App\Domain\Recepcion\RecepcionAcopio;
use App\Domain\Recepcion\RecepcionAcopioRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

/**
 * Registra la llegada física a planta de una jornada (viaje), sin depender de que Calidad ya
 * haya evaluado las entregas: la evaluación y la medición de la recepción son procesos
 * independientes que solo confluyen cuando Producción calcula el saldo disponible.
 */
final class RegistrarLlegadaUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
        private readonly EntregaRepositoryInterface $entregas,
        private readonly RecepcionAcopioRepositoryInterface $recepciones,
    ) {}

    public function ejecutar(
        int $jornadaId,
        DateTimeImmutable $llegadaEn,
        float $litrosMedidos,
        ?string $motivoDiferencia,
        ?string $observaciones,
        int $usuarioId,
    ): RecepcionAcopio {
        $jornada = $this->jornadas->buscarPorId($jornadaId);

        if ($jornada === null) {
            throw new RuntimeException('Jornada no encontrada.');
        }

        $resumen = $this->entregas->resumenDelDia($jornadaId);

        if ($resumen['litros'] <= 0.0) {
            throw RecepcionInvalidaException::jornadaSinEntregas();
        }

        $anterior = $this->recepciones->porJornada($jornadaId);

        $recepcion = RecepcionAcopio::crear(
            jornadaId: $jornadaId,
            llegadaEn: $llegadaEn,
            litrosRecolectados: $resumen['litros'],
            litrosMedidos: $litrosMedidos,
            motivoDiferencia: $motivoDiferencia,
            observaciones: $observaciones,
            usuarioId: $usuarioId,
        );

        $auditoria = new Auditoria(
            id: null,
            entidad: 'recepcion_acopio',
            entidadId: 0,
            accion: $anterior !== null ? AccionAuditoria::Corregir : AccionAuditoria::Crear,
            valorAntes: $anterior !== null ? "litros_medidos={$anterior->litrosMedidos};diferencia={$anterior->diferencia()}" : null,
            valorDespues: "litros_medidos={$litrosMedidos};diferencia={$recepcion->diferencia()}",
            motivo: $motivoDiferencia,
            usuarioId: $usuarioId,
            ocurridoEn: new DateTimeImmutable,
        );

        return $this->recepciones->registrarLlegada($recepcion, $auditoria);
    }
}

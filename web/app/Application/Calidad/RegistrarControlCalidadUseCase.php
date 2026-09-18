<?php

namespace App\Application\Calidad;

use App\Application\Inventario\RegistrarEntradaDesdeEntregaUseCase;
use App\Domain\Calidad\ControlCalidad;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Calidad\EstadoCalidad;
use App\Domain\Calidad\Exceptions\ControlCalidadInvalidoException;
use App\Domain\Entregas\EntregaRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class RegistrarControlCalidadUseCase
{
    public function __construct(
        private readonly ControlCalidadRepositoryInterface $controles,
        private readonly EntregaRepositoryInterface $entregas,
        private readonly RegistrarEntradaDesdeEntregaUseCase $registrarEntradaDesdeEntrega,
    ) {}

    public function ejecutar(
        int $entregaId,
        int $usuarioId,
        EstadoCalidad $resultado,
        ?float $temperaturaC,
        ?float $acidez,
        ?string $observaciones,
    ): ControlCalidad {
        if ($this->entregas->buscarPorId($entregaId) === null) {
            throw new RuntimeException('La entrega no existe.');
        }

        if ($this->controles->buscarPorEntregaId($entregaId) !== null) {
            throw ControlCalidadInvalidoException::entregaYaEvaluada();
        }

        $evaluadoEn = new DateTimeImmutable;

        $control = ControlCalidad::crear(
            entregaId: $entregaId,
            usuarioId: $usuarioId,
            resultado: $resultado,
            temperaturaC: $temperaturaC,
            acidez: $acidez,
            observaciones: $observaciones,
            evaluadoEn: $evaluadoEn,
        );

        $control = $this->controles->guardar($control);

        // La leche pendiente o rechazada nunca queda disponible para fabricar: solo
        // aprobada/observada generan una entrada de inventario.
        if ($resultado !== EstadoCalidad::Rechazado) {
            $this->registrarEntradaDesdeEntrega->ejecutar($entregaId, $usuarioId, $evaluadoEn);
        }

        return $control;
    }
}

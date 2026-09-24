package pe.ecolecta.domain

import pe.ecolecta.domain.model.EstadoProveedor

sealed class EntregaInvalidaException(mensaje: String) : Exception(mensaje) {
    data object LitrosNoPositivos : EntregaInvalidaException("La cantidad debe ser mayor a 0.")
    data object TachosInvalidos : EntregaInvalidaException("La cantidad de tachos debe ser mayor a 0.")
    data object MotivoObligatorio : EntregaInvalidaException("Debe indicar un motivo para esta acción.")
    data object NoEsConflicto : EntregaInvalidaException("La entrega no tiene un conflicto pendiente de resolución.")
    data object YaAnulada : EntregaInvalidaException("La entrega está anulada: la anulación es definitiva y ya no admite correcciones.")
    data object ConflictoPendiente :
        EntregaInvalidaException("La entrega tiene un conflicto de sincronización. Resuélvelo primero en Conflictos.")
    data object SinCambios : EntregaInvalidaException("Los litros y tachos son iguales a los registrados; no hay nada que corregir.")
    data class SemanaLiquidada(val semana: String, val pagada: Boolean) : EntregaInvalidaException(
        "La semana del $semana ya tiene una liquidación ${if (pagada) "pagada" else "aprobada"}. " +
            "Cambiar esta entrega dejaría el pago desactualizado, por eso está bloqueada.",
    )
    data class SuperaCapacidad(val capacidadL: Double) :
        EntregaInvalidaException("Supera la capacidad física del tacho: $capacidadL L")
    data object LoteVacio : EntregaInvalidaException("El lote debe tener al menos una entrega.")
    data class ProveedorNoActivo(val estado: EstadoProveedor) :
        EntregaInvalidaException("No se puede registrar una entrega: el proveedor está ${estado.name}.")
}

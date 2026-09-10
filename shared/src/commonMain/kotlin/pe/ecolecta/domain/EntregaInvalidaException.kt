package pe.ecolecta.domain

import pe.ecolecta.domain.model.EstadoProveedor

sealed class EntregaInvalidaException(mensaje: String) : Exception(mensaje) {
    data object LitrosNoPositivos : EntregaInvalidaException("La cantidad debe ser mayor a 0.")
    data object TachosInvalidos : EntregaInvalidaException("La cantidad de tachos debe ser mayor a 0.")
    data object MotivoObligatorio : EntregaInvalidaException("Debe indicar un motivo para esta acción.")
    data object NoEsConflicto : EntregaInvalidaException("La entrega no tiene un conflicto pendiente de resolución.")
    data class SuperaCapacidad(val capacidadL: Double) :
        EntregaInvalidaException("Supera la capacidad física del tacho: $capacidadL L")
    data object LoteVacio : EntregaInvalidaException("El lote debe tener al menos una entrega.")
    data class ProveedorNoActivo(val estado: EstadoProveedor) :
        EntregaInvalidaException("No se puede registrar una entrega: el proveedor está ${estado.name}.")
}

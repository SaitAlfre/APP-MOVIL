package pe.ecolecta.presentation.proveedor.home

import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Entrega

data class ProveedorHomeUiState(
    val cargando: Boolean = true,
    val nombreProveedor: String = "",
    val codigoProveedor: String = "",
    val estadoProveedor: EstadoProveedor = EstadoProveedor.ACTIVO,
    val litrosHoy: Double = 0.0,
    val entregasHoy: Int = 0,
    val litrosSemana: Double = 0.0,
    val entregasSemana: Int = 0,
    val ultimasEntregas: List<Entrega> = emptyList(),
    val sinEntregas: Boolean = false,
    val error: String? = null,
)

package pe.ecolecta.domain.model

data class ResumenEntregas(
    val numeroEntregas: Int,
    val litrosTotales: Double,
    val promedioPorEntrega: Double,
    val mayorEntrega: Double,
    val menorEntrega: Double,
)

data class ResumenSyncProveedor(
    val pendientes: Int,
    val sincronizadas: Int,
    val errores: Int,
    val conflictos: Int,
)

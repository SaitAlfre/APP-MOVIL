package pe.ecolecta.presentation.navegacion

sealed interface Pantalla {
    data object Splash : Pantalla
    data object Login : Pantalla
    data class SeleccionRol(val usuarioId: String) : Pantalla
    data object RolNoDisponible : Pantalla

    data object AcopiadorSeleccionZonaVehiculo : Pantalla
    data object AcopiadorHome : Pantalla
    data object AcopiadorRegistroEntrega : Pantalla
    data object AcopiadorEscanearQr : Pantalla
    data object AcopiadorLote : Pantalla
    data object AcopiadorSincronizacion : Pantalla
    data object AcopiadorPerfil : Pantalla

    data object ProveedorHome : Pantalla
    data object ProveedorEntregas : Pantalla
    data class ProveedorEntregaDetalle(val id: String) : Pantalla
    data object ProveedorMiRuta : Pantalla
    data object ProveedorMiQr : Pantalla
    data object ProveedorPerfil : Pantalla

    data object AdminDashboard : Pantalla
    data object AdminUsuarios : Pantalla
    data class AdminUsuarioForm(val id: String? = null) : Pantalla
    data object AdminZonas : Pantalla
    data class AdminZonaForm(val id: String? = null) : Pantalla
    data object AdminVehiculos : Pantalla
    data class AdminVehiculoForm(val id: String? = null) : Pantalla
    data object AdminProveedores : Pantalla
    data class AdminProveedorForm(val id: String? = null) : Pantalla
    data object AdminTraslados : Pantalla
    data object AdminJornadas : Pantalla
    data class AdminJornadaDetalle(val id: String) : Pantalla
    data object AdminEntregas : Pantalla
    data class AdminEntregaDetalle(val id: String) : Pantalla
    data object AdminConflictos : Pantalla
    data object AdminAuditoria : Pantalla
}

enum class SeccionAdmin(val pantalla: Pantalla, val etiqueta: String) {
    DASHBOARD(Pantalla.AdminDashboard, "Dashboard"),
    USUARIOS(Pantalla.AdminUsuarios, "Usuarios"),
    ZONAS(Pantalla.AdminZonas, "Zonas"),
    VEHICULOS(Pantalla.AdminVehiculos, "Vehículos"),
    PROVEEDORES(Pantalla.AdminProveedores, "Proveedores"),
    TRASLADOS(Pantalla.AdminTraslados, "Traslados"),
    JORNADAS(Pantalla.AdminJornadas, "Jornadas"),
    ENTREGAS(Pantalla.AdminEntregas, "Entregas"),
    CONFLICTOS(Pantalla.AdminConflictos, "Conflictos"),
    AUDITORIA(Pantalla.AdminAuditoria, "Auditoría"),
}

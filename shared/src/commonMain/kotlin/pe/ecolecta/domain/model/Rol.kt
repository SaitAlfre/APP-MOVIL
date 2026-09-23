package pe.ecolecta.domain.model

/** Los cuatro perfiles de la aplicación móvil; cada uno tiene su propio módulo. */
enum class Rol(val etiqueta: String, val descripcion: String) {
    ADMIN("Administrador", "Gestiona usuarios, catálogos, liquidaciones y supervisa toda la operación."),
    ACOPIADOR("Acopiador", "Abre jornadas en su ruta y registra las entregas de leche de los proveedores."),
    CALIDAD("Técnico de calidad", "Registra y confirma los análisis de calidad en la zona asignada."),
    PROVEEDOR("Proveedor", "Consulta sus entregas, calidad y pagos; envía reclamos y solicitudes."),
    ;

    /** Acopiador y técnico de calidad trabajan sobre una zona asignada. */
    val requiereZona: Boolean get() = this == ACOPIADOR || this == CALIDAD

    companion object {
        /** Lee un rol guardado; los perfiles retirados de versiones anteriores se ignoran. */
        fun desde(valor: String): Rol? = entries.firstOrNull { it.name == valor }
    }
}

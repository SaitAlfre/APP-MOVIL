package pe.ecolecta.domain.model

enum class EstadoSeguimiento {
    INACTIVO,
    /** El seguimiento ya arrancó pero todavía no llegó ninguna coordenada real (ni la inicial ni tras recuperar señal). */
    BUSCANDO,
    ACTIVO,
    SIN_CONEXION,
    SIN_SENAL,
    PERMISO_DENEGADO,
    ERROR_ALMACENAMIENTO,
    ERROR_CAPTURA,
    NO_DISPONIBLE_PLATAFORMA,
}

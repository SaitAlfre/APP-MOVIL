package pe.ecolecta.domain.security

/** Protección en reposo. El contexto vincula cada valor a una cuenta y finalidad. */
interface ProtectorSecretos {
    fun proteger(valor: String, contexto: String): String
    fun abrir(valor: String, contexto: String): String

    object NoDisponible : ProtectorSecretos {
        override fun proteger(valor: String, contexto: String): String = throw SecretoLocalNoDisponibleException()
        override fun abrir(valor: String, contexto: String): String = throw SecretoLocalNoDisponibleException()
    }
}

class SecretoLocalNoDisponibleException : IllegalStateException(
    "No se pudo abrir la credencial protegida de este dispositivo. Se conserva el registro; vuelve a autenticar o restablece su PIN con el administrador.",
)

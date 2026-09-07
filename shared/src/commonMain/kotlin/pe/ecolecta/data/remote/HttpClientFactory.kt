package pe.ecolecta.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation
import io.ktor.serialization.kotlinx.json.json
import kotlinx.serialization.json.Json

/**
 * Cliente Ktor de la infraestructura base (§2, §44 Fase 1). No hay backend todavía: ningún
 * repositorio de este módulo lo usa aún; queda listo para el SyncEngine de la Fase 4.
 */
expect fun crearHttpClientEngine(): HttpClient

fun crearHttpClient(): HttpClient = crearHttpClientEngine().config {
    install(ContentNegotiation) {
        json(Json { ignoreUnknownKeys = true })
    }
}

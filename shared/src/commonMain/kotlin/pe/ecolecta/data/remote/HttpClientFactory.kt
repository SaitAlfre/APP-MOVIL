package pe.ecolecta.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation
import io.ktor.serialization.kotlinx.json.json
import kotlinx.serialization.json.Json

/**
 * Cliente Ktor compartido para la API del panel web. Cada plataforma aporta su motor HTTP;
 * las respuestas JSON admiten campos adicionales para mantener compatibilidad con el servidor.
 */
expect fun crearHttpClientEngine(): HttpClient

fun crearHttpClient(): HttpClient = crearHttpClientEngine().config {
    install(ContentNegotiation) {
        json(Json { ignoreUnknownKeys = true })
    }
}

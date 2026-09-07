package pe.ecolecta.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.engine.darwin.Darwin

actual fun crearHttpClientEngine(): HttpClient = HttpClient(Darwin)

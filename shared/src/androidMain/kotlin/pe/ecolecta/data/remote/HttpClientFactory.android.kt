package pe.ecolecta.data.remote

import io.ktor.client.HttpClient
import io.ktor.client.engine.okhttp.OkHttp

actual fun crearHttpClientEngine(): HttpClient = HttpClient(OkHttp)

package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import kotlinx.serialization.encodeToString
import pe.ecolecta.domain.model.DatosVisitaCalidad
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.db.Control_calidad
import pe.ecolecta.domain.model.ControlCalidad
import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.OrigenCaptura
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.data.local.EntidadCambio
import pe.ecolecta.data.local.marcarCambio

class SqlDelightControlCalidadRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : ControlCalidadRepository {
    override fun observarTodos(): Flow<List<ControlCalidad>> =
        db.controlCalidadQueries.selectTodos().asFlow().mapToList(dispatcher).map { it.map(Control_calidad::aDominio) }

    override fun observarPorUsuario(usuarioId: String): Flow<List<ControlCalidad>> =
        db.controlCalidadQueries.selectPorUsuario(usuarioId).asFlow().mapToList(dispatcher).map { it.map(Control_calidad::aDominio) }

    override suspend fun obtenerPorId(id: String): ControlCalidad? = withContext(dispatcher) {
        db.controlCalidadQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun existeCodigoMuestra(codigo: String): Boolean = withContext(dispatcher) {
        db.controlCalidadQueries.contarPorCodigo(codigo).executeAsOne() > 0
    }

    override suspend fun insertar(control: ControlCalidad) = withContext(dispatcher) {
        db.transaction {
        db.controlCalidadQueries.insertar(
            id = control.id, proveedor_id = control.proveedorId, usuario_id = control.usuarioId,
            codigo_muestra = control.codigoMuestra, lote_recipiente = control.loteRecipiente,
            volumen_l = control.volumenL, origen_captura = control.origenCaptura.name,
            serial_analizador = control.serialAnalizador, modo_analizador = control.modoAnalizador,
            temperatura = control.temperatura, grasa = control.grasa, sng = control.sng,
            densidad = control.densidad, proteina = control.proteina, lactosa = control.lactosa,
            sales = control.sales, solidos_totales = control.solidosTotales,
            agua_anadida = control.aguaAnadida, punto_congelacion = control.puntoCongelacion,
            ph = control.ph, apariencia = control.apariencia, observaciones = control.observaciones,
            estado = control.estado.name, alertas = control.alertas.joinToString("\n"),
            texto_comprobante = control.textoComprobante, registrado_en = control.registradoEn,
            updated_at = control.updatedAt, sync_state = control.syncState.name,
        )
        db.controlCalidadQueries.guardarVisita(Json.encodeToString(control.visita), control.id)
        db.marcarCambio(EntidadCambio.CALIDAD, control.id)
        }
        Unit
    }

    override suspend fun obtenerZonaAsignada(usuarioId: String): String? = withContext(dispatcher) {
        db.usuarioZonaQueries.selectZonaId(usuarioId).executeAsOneOrNull()
    }
}

private val visitaJson = Json { ignoreUnknownKeys = true }

private fun Control_calidad.aDominio() = ControlCalidad(
    id = id, proveedorId = proveedor_id, usuarioId = usuario_id, codigoMuestra = codigo_muestra,
    loteRecipiente = lote_recipiente, volumenL = volumen_l, origenCaptura = OrigenCaptura.valueOf(origen_captura),
    serialAnalizador = serial_analizador, modoAnalizador = modo_analizador, temperatura = temperatura,
    grasa = grasa, sng = sng, densidad = densidad, proteina = proteina, lactosa = lactosa,
    sales = sales, solidosTotales = solidos_totales, aguaAnadida = agua_anadida,
    puntoCongelacion = punto_congelacion, ph = ph, apariencia = apariencia, observaciones = observaciones,
    estado = EstadoControlCalidad.valueOf(estado), alertas = alertas.lines().filter { it.isNotBlank() },
    textoComprobante = texto_comprobante, registradoEn = registrado_en, updatedAt = updated_at,
    syncState = SyncState.valueOf(sync_state),
    // Una visita ilegible (p. ej. llegada de otra versión) no debe impedir ver el análisis.
    visita = runCatching { visitaJson.decodeFromString<DatosVisitaCalidad>(visita_json) }.getOrDefault(DatosVisitaCalidad()),
)

package pe.ecolecta.data.local

import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.security.PinHasher

/**
 * Siembra datos de demostración en el primer arranque (las 4 zonas ya vienen sembradas por la
 * migración de zona.sq). Incluye un traslado PENDIENTE y una entrega en CONFLICT para que las
 * pantallas de Traslados/Conflictos de ADMIN tengan algo que resolver sin depender del módulo
 * Acopiador (Fase 3) ni de un backend real (Fase 4), que todavía no existen.
 */
class DatabaseSeeder(
    private val db: EcolectaDatabase,
    private val reloj: Reloj,
    private val pinHasher: PinHasher,
) {
    fun sembrarSiEsNecesario() {
        val yaHayDatos = db.usuarioQueries.selectTodos().executeAsList().isNotEmpty()
        if (yaHayDatos) return

        val ahora = reloj.ahora().toEpochMilliseconds()
        val hoy = reloj.hoy()

        val zonas = db.zonaQueries.selectTodas().executeAsList()
        val zonaFaon = zonas.first { it.nombre == "FAON-MARKAPAJO" }
        val zonaMoro = zonas.first { it.nombre == "MORO VIEJO-PANCHA" }
        val zonaCollana = zonas.first { it.nombre == "COLLANA I-YASIN-HUAN" }
        val zonaPlanta = zonas.first { it.nombre == "PLANTA-COLLANA II" }

        val adminId = nuevoId()
        val hashAdmin = pinHasher.crearHash("1234")
        db.usuarioQueries.insertar(
            id = adminId, username = "admin", nombres = "Administrador General", dni = "00000001",
            pin_hash = hashAdmin.hash, pin_salt = hashAdmin.salt, activo = 1, updated_at = ahora,
        )
        db.usuarioRolQueries.insertar(usuario_id = adminId, rol = Rol.ADMIN.name)

        val acopiadorId = nuevoId()
        val hashAcopiador = pinHasher.crearHash("1234")
        db.usuarioQueries.insertar(
            id = acopiadorId, username = "jperez", nombres = "Juan Pérez", dni = "00000002",
            pin_hash = hashAcopiador.hash, pin_salt = hashAcopiador.salt, activo = 1, updated_at = ahora,
        )
        db.usuarioRolQueries.insertar(usuario_id = acopiadorId, rol = Rol.ACOPIADOR.name)

        val proveedorUsuarioId = nuevoId()
        val hashProveedor = pinHasher.crearHash("1234")
        db.usuarioQueries.insertar(
            id = proveedorUsuarioId, username = "mquispe", nombres = "Mario Quispe", dni = "10000001",
            pin_hash = hashProveedor.hash, pin_salt = hashProveedor.salt, activo = 1, updated_at = ahora,
        )
        db.usuarioRolQueries.insertar(usuario_id = proveedorUsuarioId, rol = Rol.PROVEEDOR.name)

        val vehiculo1 = nuevoId()
        db.vehiculoQueries.insertar(id = vehiculo1, nombre = "Camión 1", placa = "V1A-123", activo = 1)
        db.vehiculoQueries.insertar(id = nuevoId(), nombre = "Camión 2", placa = "V2B-456", activo = 1)

        fun sembrarProveedor(codigo: String, nombres: String, dni: String, zonaId: String): String {
            val id = nuevoId()
            db.proveedorQueries.insertar(
                id = id, codigo = codigo, nombres = nombres, dni = dni, telefono = null, direccion = null,
                zona_id = zonaId, tachos = 2, capacidad_tacho_l = 40.0, estado = EstadoProveedor.ACTIVO.name,
                updated_at = ahora, sync_state = SyncState.SYNCED.name,
            )
            return id
        }

        val prov1 = sembrarProveedor("PRV-001", "Mario Quispe", "10000001", zonaFaon.id)
        db.proveedorQueries.vincularUsuario(usuario_id = proveedorUsuarioId, id = prov1)
        val prov2 = sembrarProveedor("PRV-002", "Elena Huamán", "10000002", zonaFaon.id)
        val prov3 = sembrarProveedor("PRV-003", "Rosa Ccapa", "10000003", zonaMoro.id)
        val prov4 = sembrarProveedor("PRV-004", "Luis Apaza", "10000004", zonaMoro.id)
        val prov5 = sembrarProveedor("PRV-005", "Pedro Mamani", "10000005", zonaCollana.id)
        sembrarProveedor("PRV-006", "Vilma Condori", "10000006", zonaPlanta.id)

        db.trasladoQueries.insertar(
            id = nuevoId(), proveedor_id = prov5, zona_origen_id = zonaCollana.id, zona_destino_id = zonaPlanta.id,
            motivo = "Cambio de ruta de recojo solicitado por el proveedor.", creado_en = ahora, sync_state = "PENDING",
        )

        val jornadaId = nuevoId()
        db.jornadaQueries.insertar(
            id = jornadaId, usuario_id = acopiadorId, zona_id = zonaFaon.id, vehiculo_id = vehiculo1,
            fecha = hoy.toString(), abierta_en = ahora, sync_state = SyncState.SYNCED.name,
        )

        fun sembrarEntrega(proveedorId: String, litros: Double, syncState: SyncState, offsetMs: Long): String {
            val id = nuevoId()
            db.entregaQueries.insertar(
                id = id, jornada_id = jornadaId, proveedor_id = proveedorId, usuario_id = acopiadorId,
                zona_id = zonaFaon.id, vehiculo_id = vehiculo1, litros = litros, tachos = 1, observaciones = null,
                registrado_en = ahora - offsetMs, device_id = "seed-device", lote_id = null,
                sync_state = syncState.name, updated_at = ahora - offsetMs,
            )
            return id
        }

        sembrarEntrega(prov1, 20.0, SyncState.SYNCED, offsetMs = 3_600_000L)
        sembrarEntrega(prov2, 18.5, SyncState.PENDING, offsetMs = 1_800_000L)
        sembrarEntrega(prov3, 22.0, SyncState.ERROR, offsetMs = 900_000L)

        // Historial adicional del proveedor con cuenta propia (mquispe), para que su dashboard/historial tengan datos reales.
        val diaMs = 86_400_000L
        sembrarEntrega(prov1, 19.0, SyncState.SYNCED, offsetMs = diaMs)
        sembrarEntrega(prov1, 21.5, SyncState.SYNCED, offsetMs = 2 * diaMs)
        sembrarEntrega(prov1, 17.0, SyncState.SYNCED, offsetMs = 9 * diaMs)
        val entregaAnuladaProv1 = sembrarEntrega(prov1, 15.0, SyncState.SYNCED, offsetMs = 3 * diaMs)
        db.entregaQueries.anular(updated_at = ahora, id = entregaAnuladaProv1)

        val entregaConflicto = sembrarEntrega(prov4, 24.0, SyncState.PENDING, offsetMs = 300_000L)
        db.entregaQueries.marcarConflicto(
            litros_servidor = 21.0,
            tachos_servidor = 1.0,
            motivo_conflicto = "El servidor recibió un valor distinto para esta entrega.",
            updated_at = ahora,
            id = entregaConflicto,
        )
    }
}

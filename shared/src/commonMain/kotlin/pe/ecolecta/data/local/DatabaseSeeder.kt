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
        if (!yaHayDatos) {
            // Todo en una única transacción: si el proceso se interrumpe a mitad de la siembra, no debe
            // quedar un subconjunto de datos a medio sembrar que el guard de arriba ya no complete.
            db.transaction { sembrar() }
        }
        // Es idempotente y se ejecuta también al actualizar una instalación existente.
        db.transaction { sembrarDatosMovilesDePrueba() }
    }

    private fun sembrar() {
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

    /**
     * Datos controlados para probar la operación móvil completa: 3 proveedores con cuenta por
     * zona, cuatro acopiadores, un responsable de calidad por zona y cuatro camiones en total.
     */
    private fun sembrarDatosMovilesDePrueba() {
        val ahora = reloj.ahora().toEpochMilliseconds()
        val todasLasZonas = db.zonaQueries.selectTodas().executeAsList()
        val zonas = listOf(
            "FAON-MARKAPAJO" to "faon",
            "MORO VIEJO-PANCHA" to "moro",
            "COLLANA I-YASIN-HUAN" to "collana",
            "PLANTA-COLLANA II" to "planta",
        ).mapNotNull { (nombre, clave) -> todasLasZonas.firstOrNull { it.nombre == nombre }?.let { it to clave } }
        val pinProveedor = pinHasher.crearHash("1234")
        val pinAcopiador = pinHasher.crearHash("2468")
        val pinCalidad = pinHasher.crearHash("8642")

        fun usuarioSiFalta(
            username: String,
            nombres: String,
            dni: String,
            rol: Rol,
            hash: String,
            salt: String,
        ): String {
            val existente = db.usuarioQueries.selectPorUsername(username).executeAsOneOrNull()
            if (existente != null) return existente.id
            val id = nuevoId()
            db.usuarioQueries.insertar(
                id = id, username = username, nombres = nombres, dni = dni,
                pin_hash = hash, pin_salt = salt, activo = 1, updated_at = ahora,
            )
            db.usuarioRolQueries.insertar(usuario_id = id, rol = rol.name)
            return id
        }

        zonas.forEachIndexed { zonaIndex, (zona, clave) ->
            val numeroZona = zonaIndex + 1

            val calidadId = usuarioSiFalta(
                username = "calidad_$clave",
                nombres = "Control de Calidad ${zona.nombre}",
                dni = "40${numeroZona.toString().padStart(6, '0')}",
                rol = Rol.CALIDAD,
                hash = pinCalidad.hash,
                salt = pinCalidad.salt,
            )
            db.usuarioZonaQueries.asignar(usuario_id = calidadId, zona_id = zona.id)
            val tecnico = db.usuarioQueries.selectPorId(calidadId).executeAsOne()
            if (tecnico.nombres == "Control de Calidad ${zona.nombre}") {
                val nombresTecnicos = listOf("Miguel Vargas", "Lucía Ramos", "Carlos Huamán", "Elena Quispe")
                db.usuarioQueries.actualizar(nombres = nombresTecnicos[zonaIndex], dni = tecnico.dni,
                    activo = tecnico.activo, updated_at = ahora, id = calidadId)
            }

            val acopiadorId = usuarioSiFalta(
                username = "acop_$clave",
                nombres = "Acopiador ${zona.nombre}",
                dni = "30${numeroZona.toString().padStart(6, '0')}",
                rol = Rol.ACOPIADOR,
                hash = pinAcopiador.hash,
                salt = pinAcopiador.salt,
            )
            db.usuarioZonaQueries.asignar(usuario_id = acopiadorId, zona_id = zona.id)

            (1..3).forEach { numero ->
                val sufijo = numero.toString().padStart(2, '0')
                val username = "prov_${clave}_$sufijo"
                val usuarioId = usuarioSiFalta(
                    username = username,
                    nombres = "Proveedor ${zona.nombre} $sufijo",
                    dni = "${numeroZona}${numero.toString().padStart(7, '0')}",
                    rol = Rol.PROVEEDOR,
                    hash = pinProveedor.hash,
                    salt = pinProveedor.salt,
                )
                val codigo = "PRV-${clave.uppercase()}-$sufijo"
                val proveedorExistente = db.proveedorQueries.selectTodos().executeAsList().firstOrNull { it.codigo == codigo }
                val proveedorId = proveedorExistente?.id ?: nuevoId().also { id ->
                    db.proveedorQueries.insertar(
                        id = id, codigo = codigo, nombres = "Proveedor ${zona.nombre} $sufijo",
                        dni = "${numeroZona}${numero.toString().padStart(7, '0')}", telefono = null,
                        direccion = zona.nombre, zona_id = zona.id, tachos = 2,
                        capacidad_tacho_l = 40.0, estado = EstadoProveedor.ACTIVO.name,
                        updated_at = ahora, sync_state = SyncState.SYNCED.name,
                    )
                }
                db.proveedorQueries.vincularUsuario(usuario_id = usuarioId, id = proveedorId)
                val fincas = listOf("Finca El Rosal", "Hacienda Los Pinos", "Granja Bella Vista")
                val duenos = listOf("Rosa Mamani", "Jorge Quispe", "Ana Condori")
                db.proveedorQueries.actualizarEjemplo(nombres = fincas[numero - 1], dueno = duenos[numero - 1],
                    id = proveedorId, nombreAnterior = "Proveedor ${zona.nombre} $sufijo")
            }
        }

        val vehiculos = listOf(
            "Camión 1" to "V1A-123",
            "Camión 2" to "V2B-456",
            "Camión 3" to "V3C-789",
            "Camión 4" to "V4D-012",
        )
        val placasExistentes = db.vehiculoQueries.selectTodos().executeAsList().map { it.placa }.toSet()
        vehiculos.filterNot { it.second in placasExistentes }.forEach { (nombre, placa) ->
            db.vehiculoQueries.insertar(id = nuevoId(), nombre = nombre, placa = placa, activo = 1)
        }
    }
}

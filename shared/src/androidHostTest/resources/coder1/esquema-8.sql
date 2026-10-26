-- Fixture histórico de esquema 8 (máxima migración original: 7). Fuente Git: 5af8d7d92fa0ed82ee9dc5b09a91ce0addda5d3c. Solo DDL, sin datos reales.

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/auditoria.sq
CREATE TABLE auditoria (
    id TEXT NOT NULL PRIMARY KEY,
    entidad TEXT NOT NULL,
    entidad_id TEXT NOT NULL,
    accion TEXT NOT NULL,
    valor_antes TEXT,
    valor_despues TEXT,
    motivo TEXT,
    usuario_id TEXT NOT NULL,
    ocurrido_en INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    sync_state TEXT NOT NULL DEFAULT 'PENDING'
);
CREATE INDEX auditoria_entidad_idx ON auditoria(entidad, entidad_id);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/avisoRemotoPendiente.sq
CREATE TABLE aviso_remoto_pendiente (
    usuario_id TEXT NOT NULL PRIMARY KEY,
    zona_id TEXT NOT NULL,
    jornada_id TEXT NOT NULL,
    jornada_abierta_en INTEGER NOT NULL,
    secuencia_en INTEGER NOT NULL,
    jornada_abierta INTEGER,
    creado_en INTEGER NOT NULL
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/controlCalidad.sq
CREATE TABLE control_calidad (
    id TEXT NOT NULL PRIMARY KEY,
    proveedor_id TEXT NOT NULL,
    usuario_id TEXT NOT NULL,
    codigo_muestra TEXT NOT NULL UNIQUE,
    lote_recipiente TEXT,
    volumen_l REAL,
    origen_captura TEXT NOT NULL,
    serial_analizador TEXT,
    modo_analizador TEXT,
    temperatura REAL,
    grasa REAL,
    sng REAL,
    densidad REAL,
    proteina REAL,
    lactosa REAL,
    sales REAL,
    solidos_totales REAL,
    agua_anadida REAL,
    punto_congelacion REAL,
    ph REAL,
    apariencia TEXT,
    observaciones TEXT,
    estado TEXT NOT NULL,
    alertas TEXT NOT NULL,
    texto_comprobante TEXT,
    registrado_en INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    sync_state TEXT NOT NULL DEFAULT 'PENDING',
    visita_json TEXT NOT NULL DEFAULT '{}'
);
CREATE INDEX control_calidad_proveedor_idx ON control_calidad(proveedor_id);
CREATE INDEX control_calidad_usuario_idx ON control_calidad(usuario_id);
CREATE INDEX control_calidad_fecha_idx ON control_calidad(registrado_en);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/entrega.sq
CREATE TABLE entrega (
    id TEXT NOT NULL PRIMARY KEY,
    jornada_id TEXT NOT NULL,
    proveedor_id TEXT NOT NULL,
    usuario_id TEXT NOT NULL,
    zona_id TEXT NOT NULL,
    vehiculo_id TEXT NOT NULL,
    litros REAL NOT NULL,
    tachos INTEGER NOT NULL DEFAULT 1,
    observaciones TEXT,
    registrado_en INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    lote_id TEXT,
    anulada INTEGER NOT NULL DEFAULT 0,
    sync_state TEXT NOT NULL DEFAULT 'PENDING',
    sync_error TEXT,
    intentos INTEGER NOT NULL DEFAULT 0,
    updated_at INTEGER NOT NULL,
    litros_servidor REAL,
    tachos_servidor REAL,
    motivo_conflicto TEXT
);
CREATE INDEX entrega_proveedor_id_idx ON entrega(proveedor_id);
CREATE INDEX entrega_jornada_id_idx ON entrega(jornada_id);
CREATE INDEX entrega_zona_id_idx ON entrega(zona_id);
CREATE INDEX entrega_sync_state_idx ON entrega(sync_state);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/jornada.sq
CREATE TABLE jornada (
    id TEXT NOT NULL PRIMARY KEY,
    usuario_id TEXT NOT NULL,
    zona_id TEXT NOT NULL,
    vehiculo_id TEXT NOT NULL,
    fecha TEXT NOT NULL,
    abierta_en INTEGER NOT NULL,
    cerrada_en INTEGER,
    sync_state TEXT NOT NULL DEFAULT 'PENDING'
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/outbox.sq
CREATE TABLE outbox (
    id TEXT NOT NULL PRIMARY KEY,
    entidad TEXT NOT NULL,
    entidad_id TEXT NOT NULL,
    operacion TEXT NOT NULL,
    payload TEXT NOT NULL,
    creado_en INTEGER NOT NULL,
    intentos INTEGER NOT NULL DEFAULT 0,
    ultimo_error TEXT,
    estado TEXT NOT NULL DEFAULT 'PENDING'
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/portalProveedor.sq
CREATE TABLE portal_proveedor (
    id TEXT NOT NULL PRIMARY KEY,
    proveedor_id TEXT NOT NULL REFERENCES proveedor(id),
    tipo TEXT NOT NULL,
    contenido TEXT NOT NULL,
    creado_en INTEGER NOT NULL
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/proveedor.sq
CREATE TABLE proveedor (
    id TEXT NOT NULL PRIMARY KEY,
    codigo TEXT NOT NULL UNIQUE,
    nombres TEXT NOT NULL,
    dni TEXT NOT NULL,
    telefono TEXT,
    direccion TEXT,
    zona_id TEXT NOT NULL,
    tachos INTEGER NOT NULL DEFAULT 1,
    capacidad_tacho_l REAL NOT NULL DEFAULT 40.0,
    estado TEXT NOT NULL DEFAULT 'ACTIVO',
    updated_at INTEGER NOT NULL,
    sync_state TEXT NOT NULL DEFAULT 'SYNCED',
    -- Vincula la cuenta con rol PROVEEDOR que puede autenticarse y consultar este registro (§5).
    usuario_id TEXT,
    dueno TEXT
);
CREATE INDEX proveedor_zona_id_idx ON proveedor(zona_id);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/rutaProveedorCache.sq
CREATE TABLE ruta_proveedor_cache (
    usuario_id TEXT NOT NULL PRIMARY KEY,
    zona_id TEXT NOT NULL,
    zona_nombre TEXT NOT NULL,
    acopiador_id TEXT NOT NULL,
    acopiador_nombre TEXT NOT NULL,
    vehiculo_id TEXT NOT NULL,
    vehiculo_nombre TEXT NOT NULL,
    jornada_id TEXT NOT NULL,
    jornada_abierta_en INTEGER NOT NULL DEFAULT 0,
    fecha TEXT NOT NULL,
    lat REAL NOT NULL,
    lng REAL NOT NULL,
    precision_m REAL NOT NULL,
    capturada_en INTEGER NOT NULL,
    secuencia_en INTEGER NOT NULL DEFAULT 0,
    seguimiento_activo INTEGER NOT NULL DEFAULT 1,
    jornada_abierta INTEGER NOT NULL
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/sesion.sq
CREATE TABLE sesion_activa (
    id INTEGER NOT NULL PRIMARY KEY,
    usuario_id TEXT NOT NULL,
    rol_activo TEXT NOT NULL
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/traslado.sq
CREATE TABLE traslado_zona (
    id TEXT NOT NULL PRIMARY KEY,
    proveedor_id TEXT NOT NULL,
    zona_origen_id TEXT NOT NULL,
    zona_destino_id TEXT NOT NULL,
    motivo TEXT,
    autorizado_por TEXT,
    estado TEXT NOT NULL DEFAULT 'PENDIENTE',
    creado_en INTEGER NOT NULL,
    sync_state TEXT NOT NULL DEFAULT 'SYNCED'
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/ubicacionAcopiadorLocal.sq
CREATE TABLE ubicacion_acopiador_local (
    usuario_id TEXT NOT NULL PRIMARY KEY,
    jornada_id TEXT NOT NULL,
    zona_id TEXT NOT NULL,
    lat REAL NOT NULL,
    lng REAL NOT NULL,
    precision_m REAL NOT NULL,
    capturada_en INTEGER NOT NULL,
    publicada INTEGER NOT NULL DEFAULT 0
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/usuario.sq
CREATE TABLE usuario (
    id TEXT NOT NULL PRIMARY KEY,
    username TEXT NOT NULL UNIQUE,
    nombres TEXT NOT NULL,
    dni TEXT NOT NULL,
    pin_hash TEXT NOT NULL,
    pin_salt TEXT NOT NULL,
    activo INTEGER NOT NULL DEFAULT 1,
    updated_at INTEGER NOT NULL,
    intentos_fallidos INTEGER NOT NULL DEFAULT 0,
    bloqueado_hasta INTEGER
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/usuarioRol.sq
CREATE TABLE usuario_rol (
    usuario_id TEXT NOT NULL,
    rol TEXT NOT NULL,
    PRIMARY KEY (usuario_id, rol)
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/usuarioZona.sq
CREATE TABLE usuario_zona (
    usuario_id TEXT NOT NULL PRIMARY KEY,
    zona_id TEXT NOT NULL
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/vehiculo.sq
CREATE TABLE vehiculo (
    id TEXT NOT NULL PRIMARY KEY,
    nombre TEXT NOT NULL,
    placa TEXT NOT NULL,
    activo INTEGER NOT NULL DEFAULT 1
);

-- shared/src/commonMain/sqldelight/pe/ecolecta/db/zona.sq
CREATE TABLE zona (
    id TEXT NOT NULL PRIMARY KEY,
    nombre TEXT NOT NULL,
    activo INTEGER NOT NULL DEFAULT 1
);

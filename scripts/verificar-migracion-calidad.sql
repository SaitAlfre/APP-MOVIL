.bail on
-- Ejecutar desde la raíz: sqlite3 :memory: ".read scripts/verificar-migracion-calidad.sql"
-- Simula el esquema previo y comprueba que los nuevos campos conservan una visita existente.
.read shared/src/commonMain/sqldelight/pe/ecolecta/db/4.sqm
CREATE TABLE proveedor (id TEXT PRIMARY KEY, nombres TEXT NOT NULL);
INSERT INTO proveedor VALUES ('proveedor-previo', 'Proveedor existente');
INSERT INTO control_calidad (id, proveedor_id, usuario_id, codigo_muestra, origen_captura,
    estado, alertas, registrado_en, updated_at)
VALUES ('visita-previa', 'proveedor-previo', 'tecnico-previo', 'MUE-ANTERIOR', 'MANUAL',
    'APROBADO', '', 1000, 1000);
.read shared/src/commonMain/sqldelight/pe/ecolecta/db/5.sqm
.read shared/src/commonMain/sqldelight/pe/ecolecta/db/6.sqm
CREATE TEMP TABLE comprobacion (correcto INTEGER NOT NULL CHECK(correcto = 1));
INSERT INTO comprobacion SELECT COUNT(*) = 1 FROM control_calidad
    WHERE id = 'visita-previa' AND codigo_muestra = 'MUE-ANTERIOR' AND visita_json = '{}';
INSERT INTO comprobacion SELECT COUNT(*) = 1 FROM proveedor
    WHERE id = 'proveedor-previo' AND nombres = 'Proveedor existente' AND dueno IS NULL;
UPDATE control_calidad SET visita_json = '{"finca":"El Rosal","accionTomada":"Segunda revisión"}'
    WHERE id = 'visita-previa';
INSERT INTO comprobacion SELECT COUNT(*) = 1 FROM control_calidad
    WHERE visita_json LIKE '%Segunda revisión%' AND registrado_en = 1000;
SELECT 'OK: visitas anteriores conservadas; metadatos y dueño disponibles.';

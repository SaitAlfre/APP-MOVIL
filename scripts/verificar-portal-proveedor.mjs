// Verifica el SQL de migración y consultas reales sin tocar la base del usuario.
// Node 24: node scripts/verificar-portal-proveedor.mjs
import { DatabaseSync } from 'node:sqlite';
import { readFileSync, mkdtempSync, rmSync, rmdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import assert from 'node:assert/strict';

const folder = mkdtempSync(join(tmpdir(), 'ecolactea-portal-test-'));
const file = join(folder, 'test.db');
const base = new URL('../shared/src/commonMain/sqldelight/pe/ecolecta/db/', import.meta.url);
let db = new DatabaseSync(file);
try {
  const providerSchema = readFileSync(new URL('proveedor.sq', base), 'utf8').split(');')[0] + ');';
  db.exec(providerSchema);
  db.exec("INSERT INTO proveedor(id,codigo,nombres,dni,zona_id,updated_at) VALUES ('a','A','Proveedor A','11111111','z',0),('b','B','Proveedor B','22222222','z',0)");
  db.exec(readFileSync(new URL('7.sqm', base), 'utf8'));
  assert.equal(db.prepare('SELECT COUNT(*) AS n FROM proveedor').get().n, 2);
  const queries = readFileSync(new URL('portalProveedor.sq', base), 'utf8');
  const insert = queries.split('guardar:')[1].trim();
  const select = queries.split('porProveedor:')[1].split('guardar:')[0].trim();
  const evidence = 'data:image/jpeg;base64,' + 'A'.repeat(900_000);
  db.prepare(insert).run('s1', 'a', 'SOLICITUD', JSON.stringify({ proveedorId: 'a', evidencia: evidence, estado: 'PENDIENTE_ENVIO' }), 1);
  db.prepare(insert).run('s2', 'b', 'SOLICITUD', '{}', 2);
  assert.throws(() => db.prepare(insert).run('s1', 'a', 'SOLICITUD', '{}', 1));
  assert.equal(db.prepare(select).all('a').length, 1);
  db.close();
  db = new DatabaseSync(file);
  const saved = JSON.parse(db.prepare(select).get('a').contenido);
  assert.equal(saved.evidencia, evidence);
  assert.equal(saved.estado, 'PENDIENTE_ENVIO');
  assert.equal(db.prepare('SELECT zona_id FROM proveedor WHERE id=?').get('a').zona_id, 'z');
  console.log('OK: migración conserva proveedores, aislamiento por proveedor, idempotencia por ID, persistencia tras reapertura y zona inalterada.');
} finally {
  db.close();
  // Solo el directorio efímero exacto creado por esta prueba.
  rmSync(file);
  rmdirSync(folder);
}

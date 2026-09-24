// Pruebas de firestore.rules para `registros_acopio` (lista de acopio compartida entre el celular del
// acopiador y el del proveedor) con el Firebase Emulator Suite. Ejecutar con: npm install && npm test
// (dentro de firebase-tests/). Reemplaza a las pruebas de `rutas_activas` (seguimiento GPS retirado).
//
// Casos centrales:
// - PRIVACIDAD: un proveedor solo puede leer/consultar documentos de SU código (fijado a mano en
//   proveedor_links/{uid}.proveedorCodigo); nunca los de otro proveedor, ni listar la colección.
// - AUTORIZACIÓN: solo un acopiador vinculado a la zona del registro puede escribirlo, con su propia
//   identidad al crearlo.
// - SIN DUPLICADOS / SIN RETROCESOS: reenviar el mismo registro sobrescribe el mismo documento; una
//   copia atrasada (actualizadoEn menor) no pisa una corrección más nueva; nada se borra.

import { test, before, beforeEach, after } from "node:test";
import { readFileSync } from "node:fs";
import {
  initializeTestEnvironment,
  assertSucceeds,
  assertFails,
} from "@firebase/rules-unit-testing";
import { doc, setDoc, getDoc, getDocs, deleteDoc, collection, query, where } from "firebase/firestore";

const ACOPIADOR_UID = "uid-acopiador";
const ACOPIADOR_USUARIO_ID = "u-acopiador-1";
const RELEVO_UID = "uid-acopiador-relevo";
const OTRA_ZONA_UID = "uid-acopiador-otra-zona";
const PROVEEDOR_UID = "uid-proveedor-01";
const PROVEEDOR_02_UID = "uid-proveedor-02";
const IMPOSTOR_UID = "uid-sin-vinculo";
const ZONA = "zona-faon-markapajo";
const OTRA_ZONA = "zona-moro-viejo-pancha";

let testEnv;

before(async () => {
  testEnv = await initializeTestEnvironment({
    projectId: "demo-no-project",
    firestore: { rules: readFileSync("../firestore.rules", "utf8"), host: "127.0.0.1", port: 8080 },
  });
});

after(async () => {
  await testEnv.cleanup();
});

beforeEach(async () => {
  await testEnv.clearFirestore();
  await testEnv.withSecurityRulesDisabled(async (context) => {
    const db = context.firestore();
    await setDoc(doc(db, "acopiador_links", ACOPIADOR_UID), { usuarioId: ACOPIADOR_USUARIO_ID, zonaId: ZONA });
    await setDoc(doc(db, "acopiador_links", RELEVO_UID), { usuarioId: "u-relevo", zonaId: ZONA });
    await setDoc(doc(db, "acopiador_links", OTRA_ZONA_UID), { usuarioId: "u-otra-zona", zonaId: OTRA_ZONA });
    await setDoc(doc(db, "proveedor_links", PROVEEDOR_UID), { usuarioId: "u-prov-1", zonaId: ZONA, proveedorCodigo: "PRV-FAON-01" });
    await setDoc(doc(db, "proveedor_links", PROVEEDOR_02_UID), { usuarioId: "u-prov-2", zonaId: ZONA, proveedorCodigo: "PRV-FAON-02" });
  });
});

function entrega(id, overrides = {}) {
  return {
    id,
    tipo: "ENTREGA",
    proveedorCodigo: "PRV-FAON-01",
    zonaId: ZONA,
    jornadaId: "j1",
    acopiadorId: ACOPIADOR_USUARIO_ID,
    acopiadorNombre: "Juan Pérez",
    fecha: "2026-09-24",
    registradoEn: 1_790_000_000_000,
    litros: 40.5,
    tachos: 1,
    anulada: false,
    motivo: null,
    detalle: null,
    deshecha: false,
    actualizadoEn: 1_790_000_000_000,
    ...overrides,
  };
}

function sinRecojo(id, overrides = {}) {
  return entrega(id, { tipo: "SIN_RECOJO", litros: null, tachos: null, motivo: "SIN_LECHE", ...overrides });
}

const db = (uid) => testEnv.authenticatedContext(uid).firestore();

async function sembrar(id, datos) {
  await testEnv.withSecurityRulesDisabled(async (context) => {
    await setDoc(doc(context.firestore(), "registros_acopio", id), datos);
  });
}

// ------------------------------------------------------------------ escritura del acopiador

test("el acopiador vinculado a la zona registra entregas y sin recojo", async () => {
  await assertSucceeds(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1")));
  await assertSucceeds(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "m1"), sinRecojo("m1")));
});

test("reenviar el mismo registro sobrescribe el mismo documento (reintento sin duplicar)", async () => {
  await assertSucceeds(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1")));
  await assertSucceeds(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1")));
  const lectura = await getDocs(query(collection(db(PROVEEDOR_UID), "registros_acopio"), where("proveedorCodigo", "==", "PRV-FAON-01")));
  if (lectura.size !== 1) throw new Error(`se esperaba 1 documento y hay ${lectura.size}`);
});

test("sin vínculo, con otra zona o suplantando a otro acopiador no se puede escribir", async () => {
  await assertFails(setDoc(doc(db(IMPOSTOR_UID), "registros_acopio", "e1"), entrega("e1")));
  await assertFails(setDoc(doc(db(OTRA_ZONA_UID), "registros_acopio", "e1"), entrega("e1")));
  await assertFails(setDoc(doc(db(RELEVO_UID), "registros_acopio", "e1"), entrega("e1"))); // crea a nombre de otro
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1", { zonaId: OTRA_ZONA })));
  await assertFails(setDoc(doc(db(PROVEEDOR_UID), "registros_acopio", "e1"), entrega("e1"))); // el proveedor no escribe
});

test("datos inválidos se rechazan", async () => {
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("otro-id")));
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1", { litros: 0 })));
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "m1"), sinRecojo("m1", { motivo: "" })));
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1", { tipo: "UBICACION" })));
});

test("corrección, anulación y deshacer: mismo documento, nunca con datos atrasados", async () => {
  await sembrar("e1", entrega("e1", { actualizadoEn: 100 }));
  await assertSucceeds(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1", { litros: 38, actualizadoEn: 200 })));
  await assertSucceeds(setDoc(doc(db(RELEVO_UID), "registros_acopio", "e1"), entrega("e1", { anulada: true, actualizadoEn: 300 })));
  // Una copia vieja que llega tarde no revierte la anulación.
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1", { litros: 40.5, actualizadoEn: 150 })));
  // No se puede mover el registro a otro proveedor ni cambiar su tipo.
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), entrega("e1", { proveedorCodigo: "PRV-FAON-02", actualizadoEn: 400 })));
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1"), sinRecojo("e1", { actualizadoEn: 400 })));
  // Un acopiador de otra zona no corrige.
  await assertFails(setDoc(doc(db(OTRA_ZONA_UID), "registros_acopio", "e1"), entrega("e1", { actualizadoEn: 500 })));
  // Nada se borra.
  await assertFails(deleteDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1")));
});

// ------------------------------------------------------------------ lectura del proveedor (privacidad)

test("el proveedor lee sus propios registros", async () => {
  await sembrar("e1", entrega("e1"));
  await assertSucceeds(getDoc(doc(db(PROVEEDOR_UID), "registros_acopio", "e1")));
  await assertSucceeds(getDocs(query(collection(db(PROVEEDOR_UID), "registros_acopio"), where("proveedorCodigo", "==", "PRV-FAON-01"))));
});

test("el proveedor nunca lee registros de otro proveedor ni lista la colección", async () => {
  await sembrar("e2", entrega("e2", { proveedorCodigo: "PRV-FAON-02" }));
  await assertFails(getDoc(doc(db(PROVEEDOR_UID), "registros_acopio", "e2")));
  await assertFails(getDocs(query(collection(db(PROVEEDOR_UID), "registros_acopio"), where("proveedorCodigo", "==", "PRV-FAON-02"))));
  await assertFails(getDocs(collection(db(PROVEEDOR_UID), "registros_acopio")));
  await assertSucceeds(getDoc(doc(db(PROVEEDOR_02_UID), "registros_acopio", "e2")));
});

test("sin vínculo ni el acopiador pueden leer registros", async () => {
  await sembrar("e1", entrega("e1"));
  await assertFails(getDoc(doc(db(IMPOSTOR_UID), "registros_acopio", "e1")));
  await assertFails(getDoc(doc(testEnv.unauthenticatedContext().firestore(), "registros_acopio", "e1")));
  await assertFails(getDoc(doc(db(ACOPIADOR_UID), "registros_acopio", "e1")));
});

test("la colección antigua del GPS ya no es accesible", async () => {
  await assertFails(setDoc(doc(db(ACOPIADOR_UID), "rutas_activas", ZONA), { zonaId: ZONA, acopiadorId: ACOPIADOR_USUARIO_ID }));
  await assertFails(getDoc(doc(db(PROVEEDOR_UID), "rutas_activas", ZONA)));
});

// Pruebas de firestore.rules con el Firebase Emulator Suite (Firestore local, sin tocar el proyecto
// real ni gastar cuota). Ejecutar con: npm install && npm test (dentro de firebase-tests/).
//
// Caso central pedido en el Grupo 5: una publicación atrasada (en cola desde antes de "detener
// seguimiento" o de cerrar/reabrir una jornada) NUNCA debe poder reactivar el seguimiento ni
// sobrescribir los datos de una jornada más nueva — sin importar cuándo llegue al servidor. Eso se
// prueba comparando escrituras con jornadaAbiertaEn/secuenciaEn más viejos contra el documento ya
// guardado (ver "protección contra escrituras atrasadas" más abajo).
//
// Segundo caso central: acopiador_links/{uid} guarda identidad (usuarioId) Y autorización de zona
// (zonaId) — ambos administrados a mano, nunca editables por el cliente. create/update exigen las
// dos cosas: ni una identidad válida sin autorización de zona, ni una jornadaAbiertaEn mayor sin
// autorización de zona, alcanzan para escribir en una zona ajena (ver "autorización de zona" abajo).

import { test, before, beforeEach, after } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import {
  initializeTestEnvironment,
  assertSucceeds,
  assertFails,
} from "@firebase/rules-unit-testing";
import { doc, setDoc, getDoc, updateDoc } from "firebase/firestore";

const ACOPIADOR_UID = "uid-acopiador-demo";
const ACOPIADOR_USUARIO_ID = "u-acopiador-1";
const RELEVO_UID = "uid-acopiador-relevo";
const RELEVO_USUARIO_ID = "u-acopiador-relevo";
const ACOPIADOR_OTRA_ZONA_UID = "uid-acopiador-otra-zona";
const ACOPIADOR_OTRA_ZONA_USUARIO_ID = "u-acopiador-otra-zona";
const PROVEEDOR_UID = "uid-proveedor-demo";
const PROVEEDOR_USUARIO_ID = "u-proveedor-1";
const ZONA_ID = "zona-faon-markapajo";
const OTRA_ZONA_ID = "zona-moro-viejo-pancha";
const IMPOSTOR_UID = "uid-sin-vinculo";

let testEnv;

before(async () => {
  testEnv = await initializeTestEnvironment({
    projectId: "ecolecta-huata-demo-reglas-test",
    firestore: {
      rules: readFileSync("../firestore.rules", "utf8"),
      host: "127.0.0.1",
      port: 8080,
    },
  });
});

after(async () => {
  await testEnv.cleanup();
});

beforeEach(async () => {
  await testEnv.clearFirestore();
  // Vínculos creados "a mano desde Firebase Console" — la app nunca los escribe, así que se siembran
  // aquí saltándose las reglas (equivalente a lo que hace un administrador en la consola). Cada
  // acopiador_links lleva usuarioId (identidad) Y zonaId (autorización) — ambos administrados.
  await testEnv.withSecurityRulesDisabled(async (context) => {
    const db = context.firestore();
    await setDoc(doc(db, "acopiador_links", ACOPIADOR_UID), { usuarioId: ACOPIADOR_USUARIO_ID, zonaId: ZONA_ID });
    await setDoc(doc(db, "acopiador_links", RELEVO_UID), { usuarioId: RELEVO_USUARIO_ID, zonaId: ZONA_ID });
    await setDoc(doc(db, "acopiador_links", ACOPIADOR_OTRA_ZONA_UID), {
      usuarioId: ACOPIADOR_OTRA_ZONA_USUARIO_ID,
      zonaId: OTRA_ZONA_ID,
    });
    await setDoc(doc(db, "proveedor_links", PROVEEDOR_UID), {
      usuarioId: PROVEEDOR_USUARIO_ID,
      zonaId: ZONA_ID,
    });
  });
});

async function leerSinReglas(zonaId) {
  let snapshot;
  await testEnv.withSecurityRulesDisabled(async (context) => {
    snapshot = await getDoc(doc(context.firestore(), "rutas_activas", zonaId));
  });
  return snapshot.data();
}

function posicion({
  jornadaId = "j1",
  jornadaAbiertaEn,
  secuenciaEn,
  seguimientoActivo = true,
  jornadaAbierta = true,
  acopiadorId = ACOPIADOR_USUARIO_ID,
  zonaId = ZONA_ID,
}) {
  return {
    zonaId,
    zonaNombre: "Zona de prueba",
    acopiadorId,
    acopiadorNombre: "Acopiador de prueba",
    vehiculoId: "veh-1",
    vehiculoNombre: "Vehículo de prueba",
    jornadaId,
    jornadaAbiertaEn,
    fecha: "2026-01-15",
    lat: -12.0,
    lng: -77.0,
    precisionM: 8,
    capturadaEn: secuenciaEn,
    secuenciaEn,
    seguimientoActivo,
    jornadaAbierta,
  };
}

test("la app nunca puede leer ni escribir acopiador_links/proveedor_links, aunque esté autenticada", async () => {
  const acopiadorDb = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await assertFails(getDoc(doc(acopiadorDb, "acopiador_links", ACOPIADOR_UID)));
  await assertFails(setDoc(doc(acopiadorDb, "acopiador_links", ACOPIADOR_UID), { usuarioId: "otro", zonaId: ZONA_ID }));
  const proveedorDb = testEnv.authenticatedContext(PROVEEDOR_UID).firestore();
  await assertFails(getDoc(doc(proveedorDb, "proveedor_links", PROVEEDOR_UID)));
});

test("el acopiador dueño puede crear el documento de su propia zona", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await assertSucceeds(
    setDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 })),
  );
});

test("un usuario sin acopiador_links (impostor) no puede crear ni actualizar rutas_activas", async () => {
  const db = testEnv.authenticatedContext(IMPOSTOR_UID).firestore();
  await assertFails(
    setDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 })),
  );
});

test("un acopiador no puede publicar en una zona que no es la suya (acopiadorId no coincide con su vínculo)", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await assertFails(
    setDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500, acopiadorId: "otro-usuario" })),
  );
});

test("autorización de zona: un acopiador con identidad válida pero vinculado a OTRA zona no puede CREAR esta ruta", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_OTRA_ZONA_UID).firestore();
  await assertFails(
    setDoc(
      doc(db, "rutas_activas", ZONA_ID),
      posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500, acopiadorId: ACOPIADOR_OTRA_ZONA_USUARIO_ID, zonaId: ZONA_ID }),
    ),
  );
});

test("autorización de zona: un acopiador con identidad válida pero vinculado a OTRA zona no puede ACTUALIZAR esta ruta, aunque publique una jornada más nueva", async () => {
  const dueño = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(doc(dueño, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));

  const intruso = testEnv.authenticatedContext(ACOPIADOR_OTRA_ZONA_UID).firestore();
  await assertFails(
    updateDoc(
      doc(intruso, "rutas_activas", ZONA_ID),
      posicion({
        jornadaId: "jornada-intento",
        jornadaAbiertaEn: 999_999, // muy posterior a 1000 — una fecha mayor no debe alcanzar
        secuenciaEn: 1,
        acopiadorId: ACOPIADOR_OTRA_ZONA_USUARIO_ID,
        zonaId: ZONA_ID,
      }),
    ),
  );

  const actual = await leerSinReglas(ZONA_ID);
  assert.equal(actual.acopiadorId, ACOPIADOR_USUARIO_ID, "la zona no debe cambiar de dueño ante una escritura sin autorización de zona");
});

test("el proveedor vinculado a la zona puede leerla; uno vinculado a otra zona no puede", async () => {
  await testEnv.withSecurityRulesDisabled(async (context) => {
    await setDoc(doc(context.firestore(), "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));
    await setDoc(doc(context.firestore(), "proveedor_links", "uid-otra-zona"), {
      usuarioId: "u-proveedor-2",
      zonaId: OTRA_ZONA_ID,
    });
  });

  const proveedorDeLaZona = testEnv.authenticatedContext(PROVEEDOR_UID).firestore();
  await assertSucceeds(getDoc(doc(proveedorDeLaZona, "rutas_activas", ZONA_ID)));

  const proveedorDeOtraZona = testEnv.authenticatedContext("uid-otra-zona").firestore();
  await assertFails(getDoc(doc(proveedorDeOtraZona, "rutas_activas", ZONA_ID)));

  const sinVinculo = testEnv.authenticatedContext(IMPOSTOR_UID).firestore();
  await assertFails(getDoc(doc(sinVinculo, "rutas_activas", ZONA_ID)));
});

test("protección contra escrituras atrasadas: una posición más vieja (secuenciaEn menor) en la misma jornada se rechaza", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await assertSucceeds(setDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 })));

  // Llega tarde una posición capturada ANTES de "detener seguimiento" (secuenciaEn=200 < 500 ya guardado).
  await assertFails(
    updateDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 200 })),
  );

  const actual = await leerSinReglas(ZONA_ID);
  assert.equal(actual.secuenciaEn, 500, "el documento no debe cambiar tras la escritura atrasada rechazada");
});

test("protección contra escrituras atrasadas: una posición más nueva (secuenciaEn mayor) en la misma jornada se acepta", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));

  await assertSucceeds(
    updateDoc(doc(db, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 600 })),
  );
});

test("un 'detener seguimiento' atrasado no puede reactivar una posición ya marcada como detenida", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  // Estado actual: ya se aplicó "detener" (secuenciaEn=900, seguimientoActivo=false).
  await setDoc(
    doc(db, "rutas_activas", ZONA_ID),
    posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 900, seguimientoActivo: false }),
  );

  // Llega tarde una posición capturada ANTES de detener (secuenciaEn=700 < 900), con seguimientoActivo=true.
  await assertFails(
    updateDoc(
      doc(db, "rutas_activas", ZONA_ID),
      posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 700, seguimientoActivo: true }),
    ),
  );

  const actual = await leerSinReglas(ZONA_ID);
  assert.equal(actual.seguimientoActivo, false, "el seguimiento no debe reactivarse por una escritura atrasada");
});

test("protección contra escrituras atrasadas: una jornada anterior (jornadaAbiertaEn menor) nunca sobrescribe, aunque secuenciaEn sea alto", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  // La jornada nueva (jornadaAbiertaEn=2000) ya está guardada.
  await setDoc(
    doc(db, "rutas_activas", ZONA_ID),
    posicion({ jornadaId: "jornada-nueva", jornadaAbiertaEn: 2000, secuenciaEn: 100 }),
  );

  // Llega tarde una escritura de la jornada VIEJA (jornadaAbiertaEn=1000), con un secuenciaEn enorme:
  // el reloj lógico de jornada manda primero, así que igual debe rechazarse.
  await assertFails(
    updateDoc(
      doc(db, "rutas_activas", ZONA_ID),
      posicion({ jornadaId: "jornada-vieja", jornadaAbiertaEn: 1000, secuenciaEn: 999_999_999 }),
    ),
  );

  const actual = await leerSinReglas(ZONA_ID);
  assert.equal(actual.jornadaId, "jornada-nueva", "la jornada más nueva nunca debe perderse ante una escritura atrasada de una jornada anterior");
});

test("una jornada genuinamente nueva (jornadaAbiertaEn mayor) del mismo acopiador siempre se acepta, aunque secuenciaEn sea bajo", async () => {
  const db = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(
    doc(db, "rutas_activas", ZONA_ID),
    posicion({ jornadaId: "jornada-vieja", jornadaAbiertaEn: 1000, secuenciaEn: 999_999_999, seguimientoActivo: false, jornadaAbierta: false }),
  );

  await assertSucceeds(
    updateDoc(
      doc(db, "rutas_activas", ZONA_ID),
      posicion({ jornadaId: "jornada-nueva", jornadaAbiertaEn: 1100, secuenciaEn: 1 }),
    ),
  );
});

test("relevo legítimo: otro acopiador vinculado a LA MISMA zona toma la ruta con una jornada más nueva", async () => {
  const anterior = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(doc(anterior, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));

  const relevo = testEnv.authenticatedContext(RELEVO_UID).firestore();
  await assertSucceeds(
    updateDoc(
      doc(relevo, "rutas_activas", ZONA_ID),
      posicion({ jornadaId: "jornada-relevo", jornadaAbiertaEn: 2000, secuenciaEn: 1, acopiadorId: RELEVO_USUARIO_ID }),
    ),
  );

  const actual = await leerSinReglas(ZONA_ID);
  assert.equal(actual.acopiadorId, RELEVO_USUARIO_ID, "la zona debe quedar a nombre del acopiador de relevo");
});

test("un impostor sin vínculo no puede tomar la zona aunque publique una jornada mayor", async () => {
  const anterior = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(doc(anterior, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));

  const impostor = testEnv.authenticatedContext(IMPOSTOR_UID).firestore();
  await assertFails(
    updateDoc(
      doc(impostor, "rutas_activas", ZONA_ID),
      posicion({ jornadaId: "jornada-impostor", jornadaAbiertaEn: 2000, secuenciaEn: 1, acopiadorId: "cualquiera" }),
    ),
  );
});

test("un acopiador vinculado no puede reclamar la zona publicando el usuarioId de otro (suplantación)", async () => {
  const anterior = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(doc(anterior, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));

  // RELEVO_UID está vinculado a esta misma zona, pero intenta publicar reclamando ser ACOPIADOR_USUARIO_ID.
  const suplantador = testEnv.authenticatedContext(RELEVO_UID).firestore();
  await assertFails(
    updateDoc(
      doc(suplantador, "rutas_activas", ZONA_ID),
      posicion({ jornadaId: "jornada-suplantada", jornadaAbiertaEn: 2000, secuenciaEn: 1, acopiadorId: ACOPIADOR_USUARIO_ID }),
    ),
  );
});

test("tras un relevo legítimo, una escritura atrasada del acopiador anterior (con su jornadaAbiertaEn vieja) se rechaza", async () => {
  const anterior = testEnv.authenticatedContext(ACOPIADOR_UID).firestore();
  await setDoc(doc(anterior, "rutas_activas", ZONA_ID), posicion({ jornadaAbiertaEn: 1000, secuenciaEn: 500 }));

  const relevo = testEnv.authenticatedContext(RELEVO_UID).firestore();
  await updateDoc(
    doc(relevo, "rutas_activas", ZONA_ID),
    posicion({ jornadaId: "jornada-relevo", jornadaAbiertaEn: 2000, secuenciaEn: 1, acopiadorId: RELEVO_USUARIO_ID }),
  );

  // Al acopiador anterior le llega tarde una publicación en cola de SU jornada vieja (jornadaAbiertaEn=1000).
  await assertFails(
    updateDoc(
      doc(anterior, "rutas_activas", ZONA_ID),
      posicion({ jornadaId: "jornada-vieja", jornadaAbiertaEn: 1000, secuenciaEn: 999_999_999 }),
    ),
  );

  const actual = await leerSinReglas(ZONA_ID);
  assert.equal(actual.acopiadorId, RELEVO_USUARIO_ID, "el relevo no debe perderse ante una escritura atrasada del acopiador anterior");
});

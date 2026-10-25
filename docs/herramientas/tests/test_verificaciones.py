"""Regresión del ejecutor; todos los datos son ficticios y temporales."""
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import time
import unittest
from unittest.mock import patch

sys.dont_write_bytecode = True
BASE = Path(__file__).resolve().parents[1]
def module(name):
    spec = importlib.util.spec_from_file_location(name, BASE / (name+'.py'))
    result = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(result)
    return result
qa = module('ejecutar_verificaciones')
check = module('verificar_documentacion')


class VerificacionesTest(unittest.TestCase):
    def test_log_sanea_ruta_correo_token_y_pin_sin_cambiar_conteos(self):
        address = 'prueba'+'@'+'ejemplo'+'.invalid'
        token = 'ghp_' + 'X'*30
        text = '/privado/proyecto '+address+' '+token+' PIN 9876; tests 289 passed 289'
        result = qa.sanitize(text, [('/privado/proyecto','<REPO>')])
        self.assertNotIn(address, result)
        self.assertNotIn(token, result)
        self.assertNotIn('9876', result)
        self.assertIn('tests 289 passed 289', result)
        self.assertIn('<REPO>', result)

    def test_junit_distingue_passed_failed_skipped_y_error(self):
        with tempfile.TemporaryDirectory() as d:
            p = Path(d)/'tests.xml'
            p.write_text('<testsuite><testcase name="ok"/><testcase name="bad"><failure/></testcase><testcase name="skip"><skipped/></testcase><testcase name="error"><error/></testcase></testsuite>')
            counts = qa.junit_counts([p], time.time()-1)
            self.assertEqual((4,1,2,1), tuple(counts[k] for k in ['total','passed','failed','skipped']))

    def test_junit_anterior_no_se_atribuye_a_ejecucion_actual(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d)/'old.xml';p.write_text('<testsuite><testcase name="old"/></testsuite>')
            os.utime(p,(1,1))
            with self.assertRaises(ValueError):qa.junit_counts([p],time.time())

    def test_copia_fuera_de_temporal_se_rechaza(self):
        with self.assertRaises(ValueError):qa.isolated_copy(qa.ROOT/'web','web')

    def test_copia_con_env_privado_se_rechaza(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d);(p/'.env').write_text('DATO_FICTICIO=ejemplo')
            with self.assertRaises(ValueError):qa.isolated_copy(p,'web')

    def test_copia_con_lock_distinto_se_rechaza(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d);(p/'composer.lock').write_text('{}')
            with self.assertRaises(ValueError):qa.isolated_copy(p,'web')

    def test_tiempo_limite_no_se_convierte_en_passed(self):
        with self.assertRaises(subprocess.TimeoutExpired):
            qa.execute([sys.executable,'-c','import time;time.sleep(5)'],qa.ROOT,os.environ,0.1)

    def test_comando_fallido_conserva_salida_y_codigo(self):
        p=qa.execute([sys.executable,'-c','print("fallo ficticio");raise SystemExit(3)'],qa.ROOT,os.environ,2)
        self.assertEqual(3,p.returncode)
        self.assertIn('fallo ficticio',p.stdout)

    def test_copia_excluye_datos_privados_y_conserva_vistas_vendor(self):
        with tempfile.TemporaryDirectory() as d:
            root=Path(d);source=root/'source';source.mkdir()
            for name,text in [('composer.lock','{}'),('.env','DATO_FICTICIO=privado'),('database/datos.sqlite','no copiar'),('storage/logs/prueba.log','no copiar'),('vendor/autoload.php','no copiar'),('resources/views/vendor/pagination.blade.php','vista de la aplicación')]:
                p=source/name;p.parent.mkdir(parents=True,exist_ok=True);p.write_text(text)
            target=root/'copia';qa.copy_project(source,target)
            self.assertFalse((target/'.env').exists())
            self.assertFalse((target/'database/datos.sqlite').exists())
            self.assertFalse((target/'vendor').exists())
            self.assertFalse((target/'storage/logs/prueba.log').exists())
            self.assertTrue((target/'resources/views/vendor/pagination.blade.php').is_file())

    def test_respaldo_sqlite_ficticio_se_restaura_sin_perder_registros(self):
        import sqlite3
        with tempfile.TemporaryDirectory() as d:
            folder=Path(d)
            with sqlite3.connect(folder/'origen.sqlite') as original:
                original.execute('CREATE TABLE entrega(id TEXT PRIMARY KEY, litros REAL NOT NULL)')
                original.execute('INSERT INTO entrega VALUES (?,?)',('UUID_FICTICIO',20.0));original.commit()
                with sqlite3.connect(folder/'respaldo.sqlite') as backup:original.backup(backup)
            with sqlite3.connect(folder/'respaldo.sqlite') as backup:
                with sqlite3.connect(folder/'restaurada.sqlite') as restored:backup.backup(restored)
            with sqlite3.connect(folder/'restaurada.sqlite') as restored:
                self.assertEqual('ok',restored.execute('PRAGMA integrity_check').fetchone()[0])
                self.assertEqual([('UUID_FICTICIO',20.0)],restored.execute('SELECT id,litros FROM entrega').fetchall())

    def test_node_reporter_tap(self):
        self.assertEqual({'total':9,'passed':9,'failed':0,'skipped':0},qa.node_counts('# tests 9\n# pass 9\n# fail 0\n# skipped 0\n'))

    def test_node_reporter_spec(self):
        self.assertEqual(9,qa.node_counts('ℹ tests 9\nℹ pass 9\nℹ fail 0\nℹ skipped 0\n')['passed'])

    def test_node_sin_resumen_no_se_presenta_como_passed(self):
        with self.assertRaises(ValueError):qa.node_counts('Script exited successfully')

    def test_node_cancelado_no_se_presenta_como_suite_completa(self):
        with self.assertRaises(ValueError):qa.node_counts('# tests 9\n# pass 8\n# fail 0\n# skipped 0\n')

    def test_anclas_incluyen_acentos_id_y_titulos_repetidos(self):
        found=check.anchors('<a id="estable"></a>\n# Pruebas mínimas\n# Pruebas mínimas\n')
        self.assertTrue({'estable','pruebas-mínimas','pruebas-mínimas-1'}<=found)

    def test_manifiesto_incluye_readme_y_bajas_sin_respaldos(self):
        names={str(p.relative_to(check.ROOT)) for p in check.publication_files()}
        self.assertIn('README.md',names)
        for old in ['ADMIN_MOVIL.md','CREDENCIALES_PRUEBA_MOVIL.md','MODULO_CALIDAD_MOVIL.md','PROMPT_WEB_ECOLECTA.md','PROVEEDOR_MOVIL_FIGMA.md']:
            if not (check.ROOT/old).exists() and subprocess.run(['git','cat-file','-e','HEAD:'+old],cwd=check.ROOT,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0:
                self.assertIn(old,names)
        self.assertFalse(any(p.endswith(('.zip','.sqlite','.env','.pyc')) for p in names))

    def test_directorio_home_relativo_no_es_ruta_personal(self):
        import re
        pattern=re.compile(r"(?<![\w])/"+"home"+"/")
        self.assertIsNone(pattern.search("shared/src/acopiador/home/Vista.kt"))
        self.assertIsNotNone(pattern.search("file:///"+"home"+"/persona/proyecto"))

    def test_huella_de_captura_detecta_modificacion(self):
        with tempfile.TemporaryDirectory() as d:
            folder=Path(d);p=folder/'captura.png';p.write_bytes(b'fixture no imagen real')
            expected=qa.digest(p.read_bytes())
            self.assertTrue(check.artifact_matches(folder,p.name,expected))
            p.write_bytes(b'alteracion ficticia')
            self.assertFalse(check.artifact_matches(folder,p.name,expected))

    def test_artefacto_fuera_del_directorio_se_rechaza(self):
        with tempfile.TemporaryDirectory() as d:
            root=Path(d);folder=root/'recibo';folder.mkdir();p=root/'externo.log';p.write_text('fixture')
            self.assertFalse(check.artifact_matches(folder,'../externo.log',qa.digest(p.read_bytes())))

    def test_url_http_con_espacios_no_pasa_silenciosamente(self):
        self.assertIsNotNone(check.http_url_error('https://github.com/SaitAlfre/APP-MOVIL/commit/abcd 1234'))

    def test_url_commit_requiere_sha_completo(self):
        self.assertIsNotNone(check.http_url_error('https://github.com/SaitAlfre/APP-MOVIL/commit/abcd123'))
        self.assertIsNone(check.http_url_error('https://github.com/SaitAlfre/APP-MOVIL/commit/'+'a'*40))

    def test_url_con_espacio_codificado_es_valida(self):
        self.assertIsNone(check.http_url_error('https://example.invalid/documento%20publico'))

    def test_hu_y_tareas_se_conservan_unicas(self):
        stories,tasks=check.parse_catalog()
        self.assertGreaterEqual(len(stories),41)
        self.assertEqual(len(tasks),len(set(tasks)))
        self.assertTrue(all(len(v['criteria'])>=2 for v in stories.values()))


if __name__=='__main__':unittest.main()

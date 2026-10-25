#!/usr/bin/env python3
"""Ejecuta verificaciones permitidas y conserva recibos/logs sin tocar datos reales."""
from __future__ import annotations

import argparse
import base64
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import signal
import shutil
import subprocess
import sys
import tempfile
import time
from urllib.parse import urlparse
import uuid
import xml.etree.ElementTree as ET
from zoneinfo import ZoneInfo

ROOT = Path(__file__).resolve().parents[2]
SUITES = ('documentacion', 'jvm', 'php', 'firestore', 'web', 'android', 'utilidades')


def digest(data):
    return hashlib.sha256(data).hexdigest()


def now():
    return datetime.now(timezone.utc).isoformat()


def execute(command, cwd, env, timeout):
    """El tiempo límite termina solo el grupo de procesos creado por esta ejecución."""
    process = subprocess.Popen(command, cwd=cwd, env=env, stdout=subprocess.PIPE,
                               stderr=subprocess.STDOUT, text=True, errors='replace', start_new_session=True)
    try:
        output, _ = process.communicate(timeout=timeout)
    except subprocess.TimeoutExpired:
        os.killpg(process.pid, signal.SIGTERM)
        try:
            output, _ = process.communicate(timeout=5)
        except subprocess.TimeoutExpired:
            os.killpg(process.pid, signal.SIGKILL)
            output, _ = process.communicate()
        raise subprocess.TimeoutExpired(command, timeout, output=output)
    return subprocess.CompletedProcess(command, process.returncode, output)


def source_version():
    head = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
    names = subprocess.check_output(['git', 'ls-files', '-z'], cwd=ROOT).decode().split('\0')
    names += subprocess.check_output(['git', 'ls-files', '--others', '--exclude-standard', '-z'], cwd=ROOT).decode().split('\0')
    hashes = {}
    for name in sorted(set(names)):
        p = ROOT / name
        if not name or not p.is_file() or name.startswith(('docs/', '.github/')) or p.suffix == '.md':
            continue
        if p.name.startswith('.env') or p.name == 'google-services.json' or p.suffix in {'.sqlite', '.db', '.log', '.zip', '.pyc'}:
            continue
        hashes[name] = digest(p.read_bytes())
    return {'base_commit': head, 'source_sha256': digest(json.dumps(hashes, sort_keys=True).encode()), 'files_sha256': hashes}


def sanitize(text, replacements=(), secrets=()):
    for value in sorted((s for s in secrets if len(s) >= 8), key=len, reverse=True):
        text = text.replace(value, '<secreto omitido>')
    for old, new in sorted(replacements, key=lambda pair: len(pair[0]), reverse=True):
        if old:
            text = text.replace(old, new)
    text = re.sub(r'\b[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}\b', '<correo omitido>', text)
    text = re.sub(r'(?i)(\bPIN\s*[:=]?\s+)\d{4,}', r'\1<omitido>', text)
    text = re.sub(r'(?i)(authorization\s*[:=]\s*(?:bearer|basic)\s+)\S+', r'\1<omitido>', text)
    text = re.sub(r'\b(?:gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{25,})', '<token omitido>', text)
    return text


def junit_counts(files, since):
    cases = []
    for path in files:
        if not path.is_file() or path.stat().st_mtime < since - 2:
            continue
        tree = ET.parse(path)
        for case in tree.iter('testcase'):
            state = 'Failed' if case.find('failure') is not None or case.find('error') is not None else 'Skipped' if case.find('skipped') is not None else 'Passed'
            cases.append({'class': case.get('classname', ''), 'name': case.get('name', ''), 'state': state})
    if not cases:
        raise ValueError('El ejecutor no produjo casos JUnit nuevos; no se acepta un reporte anterior.')
    return {'total': len(cases), 'passed': sum(c['state'] == 'Passed' for c in cases),
            'failed': sum(c['state'] == 'Failed' for c in cases), 'skipped': sum(c['state'] == 'Skipped' for c in cases), 'cases': cases}


def node_counts(output):
    """Node puede usar los reporters TAP o spec según la versión/terminal."""
    values = {}
    for name, key in [('tests','total'), ('pass','passed'), ('fail','failed'), ('skipped','skipped')]:
        matches = re.findall(r'^(?:#|ℹ)\s+' + name + r'\s+(\d+)\s*$', output, re.M)
        if not matches:
            raise ValueError('Falta resumen Node; no se infieren resultados de un exit code.')
        values[key] = int(matches[-1])
    if values['total'] != sum(values[k] for k in ['passed','failed','skipped']):
        raise ValueError('Resumen Node con casos cancelados o totales incompatibles.')
    return values


def isolated_copy(path, project):
    path = path.resolve()
    temp = Path(tempfile.gettempdir()).resolve()
    if path == (ROOT / project).resolve() or temp not in path.parents:
        raise ValueError('La suite requiere una copia aislada en el directorio temporal del sistema.')
    if (path / '.env').exists():
        raise ValueError('La copia no puede contener .env; se usan variables ficticias del ejecutor.')
    lock = 'composer.lock' if project == 'web' else 'package-lock.json'
    if digest((path / lock).read_bytes()) != digest((ROOT / project / lock).read_bytes()):
        raise ValueError('El lockfile de la copia no coincide con el proyecto.')
    return path


def make_command(args, folder, env):
    php = args.php or shutil.which('php')
    node = args.node or shutil.which('node')
    if args.suite == 'documentacion':
        return [sys.executable, str(ROOT / 'docs/herramientas/verificar_documentacion.py'), '--export-kanban'], ROOT
    if args.suite in {'jvm', 'android'}:
        cmd = ['bash', './gradlew', '--offline', '--no-daemon', '--console=plain', '--no-configuration-cache']
        if args.suite == 'android':
            cmd += ['-PlocalPreview=true', '-Pecolecta.servidorUrl=http://127.0.0.1:8765', ':androidApp:assembleDebug']
        else:
            cmd += ['--rerun-tasks', ':shared:testAndroidHostTest']
            if args.filtro:
                cmd += ['--tests', args.filtro]
            if args.servidor:
                parsed = urlparse(args.servidor)
                if parsed.scheme != 'http' or parsed.hostname not in {'127.0.0.1', 'localhost'}:
                    raise ValueError('La integración solo admite un servidor HTTP de loopback.')
                if not args.bd_servidor or Path(tempfile.gettempdir()).resolve() not in Path(args.bd_servidor).resolve().parents:
                    raise ValueError('La integración requiere SQLite ficticia en una carpeta temporal.')
                env['ECOLECTA_SERVIDOR_PRUEBA'] = args.servidor
                env['ECOLECTA_SERVIDOR_BD'] = args.bd_servidor
            else:
                env.pop('ECOLECTA_SERVIDOR_PRUEBA', None)
                env.pop('ECOLECTA_SERVIDOR_BD', None)
        return cmd, ROOT
    if args.suite == 'php':
        if not php or not args.web_dir:
            raise ValueError('Faltan PHP o --web-dir con una copia temporal preparada.')
        cwd = isolated_copy(Path(args.web_dir), 'web')
        if not (cwd / 'public/build/manifest.json').is_file():
            raise ValueError('Compilar los assets de la copia antes de PHPUnit; falta el manifest Vite.')
        env['APP_KEY'] = 'base64:' + base64.b64encode(os.urandom(32)).decode()
        env.update(APP_ENV='testing', DB_CONNECTION='sqlite', DB_DATABASE=':memory:', DB_URL='',
                   CACHE_STORE='array', SESSION_DRIVER='array', MAIL_MAILER='array', QUEUE_CONNECTION='sync')
        return [php, 'vendor/bin/phpunit', '--colors=never', '--log-junit', str(folder / 'junit.xml')], cwd
    if args.suite in {'firestore', 'web'}:
        if not node or not args.npm:
            raise ValueError('Faltan Node o --npm apuntando a npm-cli.js.')
        project = 'web' if args.suite == 'web' else 'firebase-tests'
        value = args.web_dir if project == 'web' else args.firebase_dir
        if not value:
            raise ValueError('Falta la copia temporal de ' + project)
        cwd = isolated_copy(Path(value), project)
        env['PATH'] = str(Path(node).parent) + os.pathsep + env.get('PATH', '')
        return [node, args.npm] + (['run', 'build'] if project == 'web' else ['test']), cwd
    if args.suite == 'utilidades':
        return [sys.executable, '-m', 'unittest', 'discover', '-s', 'docs/herramientas/tests', '-v'], ROOT
    raise ValueError('Suite no admitida')


def run(args):
    stamp = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')
    ident = 'EXE-' + stamp + '-' + args.suite + '-' + uuid.uuid4().hex[:6]
    folder = Path(args.salida).resolve() if args.salida else Path(tempfile.mkdtemp(prefix='ecolactea-qa-'))
    if args.salida:
        if Path(tempfile.gettempdir()).resolve() not in folder.parents:
            raise ValueError('--salida debe ser una carpeta temporal nueva.')
        folder.mkdir(exist_ok=False, parents=True)
    folder.chmod(0o700)
    started = now()
    version = source_version()
    record = {'schema': 1, 'id': ident, 'actor': 'Agente técnico autorizado; no atribuido al equipo',
              'suite': args.suite, 'start_utc': started, 'timezone_display': 'America/Lima',
              'expected': 'Verificación sin fallos y, en suites de tests, sin omisiones', 'version': version,
              'runner_sha256': digest(Path(__file__).read_bytes()), 'state': 'Blocked', 'exit_code': None}
    record['start_local'] = datetime.fromisoformat(started).astimezone(ZoneInfo('America/Lima')).isoformat()
    env = dict(os.environ)
    for key in ['GH_TOKEN', 'GITHUB_TOKEN', 'GOOGLE_APPLICATION_CREDENTIALS', 'FIREBASE_TOKEN', 'DATABASE_URL']:
        env.pop(key, None)
    replacements = [(str(folder), '<EJECUCION>'), (str(ROOT), '<REPO>'), (str(Path.home()), '<HOME>')]
    for value in [args.web_dir, args.firebase_dir, args.npm, args.php, args.node, args.bd_servidor]:
        if value:
            replacements.append((str(Path(value).resolve()), '<RECURSO_AISLADO>'))
    replacements.append((tempfile.gettempdir() + '/', '<TMP>/'))
    secrets = [v for k, v in env.items() if re.search(r'(?i)(token|secret|password|app_key)', k)]
    raw = ''
    beginning = time.time()
    try:
        command, cwd = make_command(args, folder, env)
        record['command'] = [sanitize(str(c), replacements, secrets) for c in command]
        record['cwd'] = sanitize(str(cwd), replacements, secrets)
        secrets = [v for k, v in env.items() if re.search(r'(?i)(token|secret|password|app_key)', k)]
        completed = execute(command, cwd, env, args.timeout)
        raw = completed.stdout
        record['exit_code'] = completed.returncode
        record['state'] = 'Passed' if completed.returncode == 0 else 'Failed'
        if args.suite in {'jvm', 'php'}:
            xml = sorted((ROOT / 'shared/build/test-results/testAndroidHostTest').glob('TEST-*.xml')) if args.suite == 'jvm' else [folder / 'junit.xml']
            record['tests'] = junit_counts(xml, beginning)
            if record['tests']['failed']:
                record['state'] = 'Failed'
            elif record['tests']['skipped']:
                record['state'] = 'Blocked'
                record['limitation'] = 'Existen tests omitidos: no se presenta la suite completa como Passed.'
        if args.suite == 'firestore':
            record['tests'] = node_counts(raw)
            if record['tests']['failed']:
                record['state'] = 'Failed'
            elif record['tests']['skipped']:
                record['state'] = 'Blocked'
        if args.suite == 'utilidades':
            match = re.search(r'Ran (\d+) tests?', raw)
            if not match:
                raise ValueError('Falta el resumen real de unittest.')
            total = int(match[1])
            failures = re.search(r'failures=(\d+)', raw)
            errors = re.search(r'errors=(\d+)', raw)
            skips = re.search(r'skipped=(\d+)', raw)
            failed = (int(failures[1]) if failures else 0) + (int(errors[1]) if errors else 0)
            skipped = int(skips[1]) if skips else 0
            record['tests'] = {'total':total,'passed':total-failed-skipped,'failed':failed,'skipped':skipped}
            if skipped and record['state'] == 'Passed':
                record['state'] = 'Blocked'
        if args.suite == 'documentacion' and completed.returncode == 0:
            record['documentary'] = json.loads(raw)
        if args.suite == 'android' and completed.returncode == 0:
            apk = ROOT / 'androidApp/build/outputs/apk/debug/androidApp-debug.apk'
            if apk.is_file():
                record['apk_sha256'] = digest(apk.read_bytes())
            record['limitation'] = 'Build localPreview; no prueba Firebase estándar, iOS ni dispositivo físico.'
    except subprocess.TimeoutExpired as e:
        raw = (e.stdout or b'').decode(errors='replace') if isinstance(e.stdout, bytes) else (e.stdout or '')
        record['state'] = 'Blocked'
        record['limitation'] = 'Tiempo límite alcanzado; no se declara Passed.'
    except (ValueError, OSError, ET.ParseError) as e:
        record['state'] = 'Failed' if record.get('exit_code') not in (None, 0) else 'Blocked'
        record['limitation'] = sanitize(str(e), replacements, secrets)
    record['end_utc'] = now()
    record['end_local'] = datetime.fromisoformat(record['end_utc']).astimezone(ZoneInfo('America/Lima')).isoformat()
    clean = sanitize(raw, replacements, secrets)
    if re.search(r'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----', clean):
        clean = '[Evidencia retenida: se detectó una clave privada; no publicable.]\n'
        record['state'] = 'Blocked'
    record['raw_log_sha256'] = digest(raw.encode())
    record['published_log_sha256'] = digest(clean.encode())
    record['log'] = 'salida.log'
    record['obtained'] = ('Código ' + str(record['exit_code']) + '; estado ' + record['state'])
    (folder / 'salida-original.log').write_text(raw)
    (folder / 'salida-original.log').chmod(0o600)
    (folder / 'salida.log').write_text(clean)
    (folder / 'resultado.json').write_text(json.dumps(record, ensure_ascii=False, indent=2) + '\n')
    if args.evidencia:
        publish(record, clean)
        report(verbose=False)
    print(json.dumps({'id': ident, 'state': record['state'], 'exit_code': record['exit_code'],
                      'tests': {k:v for k,v in record.get('tests', {}).items() if k != 'cases'},
                      'limitation': record.get('limitation'), 'receipt': str(folder / 'resultado.json')}, ensure_ascii=False))
    return 0 if record['state'] == 'Passed' else 1


def publish(record, log):
    path = ROOT / 'docs/pruebas/ejecuciones/automaticas' / record['id']
    path.mkdir(parents=True, exist_ok=False)
    (path / 'salida.log').write_text(log)
    (path / 'resultado.json').write_text(json.dumps(record, ensure_ascii=False, indent=2) + '\n')
    text = '# ' + record['id'] + '\n\n[Recibo y casos](resultado.json) · [Log saneado](salida.log)\n\n'
    text += '**Ejecutante:** agente técnico autorizado. **Suite:** ' + record['suite'] + '.\n\n'
    text += '**Inicio/fin UTC:** ' + record['start_utc'] + ' → ' + record['end_utc'] + '.\n\n'
    text += '**Base:** `' + record['version']['base_commit'] + '` más árbol identificado por SHA-256 en el recibo.\n\n'
    text += '**Esperado:** ' + record['expected'] + '. **Obtenido:** ' + record['obtained'] + '.\n\n'
    if record.get('tests'):
        t = record['tests']
        text += '**Tests:** {total}; Passed {passed}; Failed {failed}; Skipped {skipped}.\n\n'.format(**t)
    if record.get('limitation'):
        text += '**Límite:** ' + record['limitation'] + '\n\n'
    text += 'No acredita aprobación de PR, reuniones, Planning Poker o aceptación del beneficiario.\n'
    (path / 'README.md').write_text(text)


def report(verbose=True):
    base = ROOT / 'docs/pruebas/ejecuciones/automaticas'
    records = []
    for p in sorted(base.glob('*/resultado.json')):
        d = json.loads(p.read_text())
        log = (p.parent / d['log']).resolve()
        if log.parent != p.parent.resolve() or digest(log.read_bytes()) != d['published_log_sha256']:
            raise ValueError('Log alterado: ' + d['id'])
        records.append(d)
    lines = ['# Verificaciones automáticas reales', '', '[Guía de pruebas](../../README.md) · [Ejecuciones originales del 22-10](../resultados-2026-10-22.md)', '',
             'Cada fila procede de un recibo ejecutado. Reintentos no se suman como tests distintos ni velocidad Scrum. Las omisiones conservan su bloqueo.', '',
             '| ID | Suite | Estado | Tests Passed/Total | Omitidos |', '|---|---|---|---|---|']
    for d in records:
        t = d.get('tests', {})
        lines.append('| [{0}]({0}/README.md) | {1} | {2} | {3} | {4} |'.format(d['id'], d['suite'], d['state'], str(t['passed'])+'/'+str(t['total']) if t else 'No aplica', t.get('skipped', 'No aplica')))
    base.mkdir(exist_ok=True, parents=True)
    (base / 'README.md').write_text('\n'.join(lines) + '\n')
    (base / 'resumen.json').write_text(json.dumps({'source': 'Recibos y logs verificados por SHA-256', 'runs': [{k:v for k,v in d.items() if k not in {'version','tests'}} | {'version_source_sha256': d['version']['source_sha256'], 'tests': {k:v for k,v in d.get('tests',{}).items() if k != 'cases'}} for d in records]}, ensure_ascii=False, indent=2) + '\n')
    if verbose:
        print(json.dumps({'runs':len(records),'report':str(base.relative_to(ROOT) / 'README.md')}))
    return 0


def copy_project(source, target):
    """Excluye datos/configuración privados y conserva las vistas vendor del proyecto."""
    def ignored(parent, names):
        rel = Path(parent).relative_to(source)
        return {name for name in names if name.startswith('.env') or name in {'node_modules','.git','build','__pycache__'}
                or (name == 'vendor' and rel == Path('.')) or (rel == Path('bootstrap') and name == 'cache')
                or (rel == Path('.') and name == 'storage') or Path(name).suffix in {'.sqlite','.db','.log','.zip'}}
    shutil.copytree(source, target, ignore=ignored)
    for folder in ['bootstrap/cache','storage/framework/cache/data','storage/framework/views','storage/framework/sessions','storage/logs']:
        (target/folder).mkdir(parents=True, exist_ok=True)


def prepare(args):
    php = args.php or shutil.which('php')
    composer = args.composer or shutil.which('composer')
    node = args.node or shutil.which('node')
    npm = args.npm or shutil.which('npm')
    if not all([php, composer, node, npm]):
        raise ValueError('La preparación requiere PHP/Composer/Node/npm; se pueden indicar sus rutas, sin cambiar dependencias del sistema.')
    folder = Path(tempfile.mkdtemp(prefix='ecolactea-entorno-'))
    folder.chmod(0o700)
    web = folder/'web'
    firebase = folder/'firebase-tests'
    copy_project(ROOT/'web', web)
    copy_project(ROOT/'firebase-tests', firebase)
    shutil.copyfile(ROOT/'firestore.rules', folder/'firestore.rules')
    env = dict(os.environ)
    env['PATH'] = str(Path(php).parent)+os.pathsep+str(Path(node).parent)+os.pathsep+env.get('PATH','')
    env['COMPOSER_HOME'] = str(folder/'composer-home')
    env['COMPOSER_CACHE_DIR'] = str(Path.home()/'.cache/composer')
    for k in ['GH_TOKEN','GITHUB_TOKEN','NPM_TOKEN','NODE_AUTH_TOKEN','FIREBASE_TOKEN','GOOGLE_APPLICATION_CREDENTIALS','DATABASE_URL']:
        env.pop(k, None)
    for name in ['npm-user.conf','npm-global.conf']:
        (folder/name).touch()
    env['NPM_CONFIG_USERCONFIG'] = str(folder/'npm-user.conf')
    env['NPM_CONFIG_GLOBALCONFIG'] = str(folder/'npm-global.conf')
    env['npm_config_audit'] = 'false'
    env['npm_config_fund'] = 'false'
    composer_cmd = [php, composer] if Path(composer).suffix == '.phar' else [composer]
    commands = [(composer_cmd+['install','--no-interaction','--prefer-dist','--no-scripts','--no-plugins'],web),
                ([node,npm,'ci','--ignore-scripts'],web), ([node,npm,'ci','--ignore-scripts'],firebase),
                ([node,npm,'run','build'],web)]
    raw = ''
    for command,cwd in commands:
        result = execute(command,cwd,env,args.timeout)
        raw += result.stdout
        if result.returncode:
            (folder/'preparacion.log').write_text(raw)
            raise ValueError('Falló una dependencia/build de la copia; el log privado está en '+str(folder/'preparacion.log'))
    db = folder/'web-prueba.sqlite'
    db.touch(exist_ok=False)
    pin = str(int.from_bytes(os.urandom(4),'big') % 9000 + 1000)
    env.update(APP_ENV='local', APP_KEY='base64:'+base64.b64encode(os.urandom(32)).decode(),
               APP_DEBUG='false', APP_URL='http://127.0.0.1:8765', DB_CONNECTION='sqlite',DB_DATABASE=str(db),DB_URL='',
               SESSION_DRIVER='file',CACHE_STORE='array',MAIL_MAILER='array',QUEUE_CONNECTION='sync',ADMIN_SEED_PIN=pin)
    for command in [[php,'artisan','migrate','--no-interaction','--force'],
                    [php,'artisan','db:seed','--class=UsuarioSeeder','--no-interaction','--force'],
                    [php,'artisan','db:seed','--class=MovilDemoSeeder','--no-interaction','--force']]:
        result=execute(command,web,env,args.timeout)
        raw+=result.stdout
        if result.returncode:
            (folder/'preparacion.log').write_text(raw)
            raise ValueError('No se completó el backend ficticio; no se inicia servidor.')
    fixture=folder/'fixture-lectura.php'
    fixture.write_text("<?php\nrequire getcwd().'/vendor/autoload.php';\n$app = require getcwd().'/bootstrap/app.php';\n$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();\n$p=App\\Infrastructure\\Persistence\\Eloquent\\Proveedor::where('codigo','PRV-FAON-01')->firstOrFail();\nApp\\Infrastructure\\Persistence\\Eloquent\\Liquidacion::create(['proveedor_id'=>$p->id,'periodo_inicio'=>'2026-10-15','periodo_fin'=>'2026-10-21','litros_totales'=>100,'precio_litro'=>1.70,'monto_total'=>170,'estado'=>'pendiente','generada_en'=>now()]);\necho 'Fixture ficticia de lectura: no prueba cálculo ni pago.';\n")
    result=execute([php,str(fixture)],web,env,args.timeout)
    if result.returncode:
        (folder/'preparacion.log').write_text(raw+result.stdout)
        raise ValueError('No se completó el fixture de integración.')
    (folder/'preparacion.log').write_text(raw+result.stdout)
    (folder/'preparacion.log').chmod(0o600)
    import socket
    with socket.socket() as sock:
        if sock.connect_ex(('127.0.0.1',8765))==0:
            raise ValueError('Puerto 8765 ocupado; no se toca el servicio existente.')
    env['APP_ENV']='testing'
    log=(folder/'servidor.log').open('w')
    server=subprocess.Popen([php,'-S','127.0.0.1:8765','-t','public'],cwd=web,env=env,stdout=log,stderr=subprocess.STDOUT,start_new_session=True)
    log.close()
    import urllib.request
    ready=False
    for _ in range(50):
        if server.poll() is not None:break
        try:
            with urllib.request.urlopen('http://127.0.0.1:8765/login',timeout=1) as response:
                ready=response.status==200
            if ready:break
        except OSError:time.sleep(0.1)
    if not ready:
        server.terminate();server.wait(timeout=5)
        raise ValueError('El backend temporal no respondió; proceso propio detenido.')
    args.php,args.node,args.npm = php,node,npm
    args.web_dir,args.firebase_dir = str(web),str(firebase)
    args.servidor,args.bd_servidor = 'http://127.0.0.1:8765',str(db)
    os.environ['ECOLECTA_PIN_ADMIN']=pin
    return server,folder


def run_all(args):
    server=None
    folder=None
    previous_pin=os.environ.get('ECOLECTA_PIN_ADMIN')
    results=[]
    try:
        if args.preparar:
            server,folder=prepare(args)
        for suite in ['web','php','firestore','jvm','android','utilidades','documentacion']:
            args.suite=suite
            args.salida=None
            results.append(run(args))
    except (ValueError,OSError) as error:
        print(json.dumps({'state':'Blocked','reason':sanitize(str(error),[(str(Path.home()),'<HOME>')]),'no_production_changes':True},ensure_ascii=False))
        return 1
    finally:
        if server is not None:
            server.terminate()
            try:server.wait(timeout=10)
            except subprocess.TimeoutExpired:server.kill();server.wait()
            shutdown={'backend_stopped':server.poll() is not None,'utc':now(),'environment':'SQLite nueva/datos ficticios/loopback'}
            if folder:(folder/'cierre.json').write_text(json.dumps(shutdown,indent=2)+'\n')
        if previous_pin is None:os.environ.pop('ECOLECTA_PIN_ADMIN',None)
        else:os.environ['ECOLECTA_PIN_ADMIN']=previous_pin
    return int(any(results))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--informe', action='store_true')
    parser.add_argument('--todas', action='store_true')
    parser.add_argument('--preparar', action='store_true', help='Crea copias/SQLite ficticia y un backend temporal; solo con --todas')
    parser.add_argument('--composer')
    parser.add_argument('--suite', choices=SUITES)
    parser.add_argument('--salida')
    parser.add_argument('--php')
    parser.add_argument('--node')
    parser.add_argument('--npm')
    parser.add_argument('--web-dir')
    parser.add_argument('--firebase-dir')
    parser.add_argument('--filtro')
    parser.add_argument('--servidor')
    parser.add_argument('--bd-servidor')
    parser.add_argument('--timeout', type=int, default=900)
    parser.add_argument('--evidencia', action='store_true')
    args = parser.parse_args()
    if args.informe:
        return report()
    if args.preparar and not args.todas:
        parser.error('--preparar requiere --todas para garantizar el cierre del servidor')
    if args.todas:
        return run_all(args)
    if not args.suite:
        parser.error('Indicar --suite o --informe')
    return run(args)


if __name__ == '__main__':
    raise SystemExit(main())

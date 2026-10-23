#!/usr/bin/env python3
"""OCR ML Kit y persistencia nativa, solo preview en emulador desechable sin app instalada."""
import argparse
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import time
import uuid
import xml.etree.ElementTree as ET

from ejecutar_verificaciones import ROOT, digest, execute, now, publish, report, sanitize, source_version


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--serial', required=True)
    parser.add_argument('--adb', default=str(Path.home()/'Android/Sdk/platform-tools/adb'))
    parser.add_argument('--emulador-desechable', action='store_true', required=True)
    args = parser.parse_args()
    if not re.fullmatch(r'emulator-\d+', args.serial):
        parser.error('Solo se permiten emuladores; nunca un teléfono físico.')
    env = dict(os.environ)
    pin = env.get('ECOLECTA_PIN_ACOPIADOR')
    if not pin or not pin.isdigit():
        parser.error('Definir ECOLECTA_PIN_ACOPIADOR con la cuenta ficticia del seeder preview.')
    folder = Path(tempfile.mkdtemp(prefix='ecolactea-native-'))
    folder.chmod(0o700)
    ident = 'EXE-'+datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')+'-android-ui-'+uuid.uuid4().hex[:6]
    record = {'schema':1,'id':ident,'actor':'Agente técnico autorizado; no atribuido al equipo',
              'suite':'android-ui','start_utc':now(),'version':source_version(),
              'expected':'Arranque UI, OCR real de dos imágenes sintéticas y entrega20L PENDING tras reapertura offline',
              'command':['python3','docs/herramientas/ejecutar_android_instrumentada.py','--serial','<EMULADOR_NUEVO>','--emulador-desechable'],
              'environment':'Android API36 x86_64 emulado, localPreview, sin ADB reverse; WiFi/datos apagados',
              'runner_sha256':digest(Path(__file__).read_bytes()),'state':'Blocked','tests':{'total':5,'passed':0,'failed':0,'skipped':5,'cases':[]},
              'log':'salida.log','exit_code':None}
    raw = ''
    def command(cmd, timeout=240):
        nonlocal raw
        completed = execute(cmd, ROOT, env, timeout)
        raw += completed.stdout+'\n'
        if completed.returncode:
            raise ValueError('Comando fallido; consultar log saneado')
        return completed.stdout
    def adb(*parts, timeout=240):
        return command([args.adb,'-s',args.serial,*parts],timeout)
    try:
        if 'pe.ecolecta.preview' in adb('shell','pm','list','packages','pe.ecolecta.preview'):
            raise ValueError('La preview ya está instalada; usar un emulador NUEVO. No se borran sus datos.')
        if adb('reverse','--list').strip():
            raise ValueError('Hay túneles ADB; no se considera una prueba offline aislada.')
        record['api'] = adb('shell','getprop','ro.build.version.sdk').strip()
        record['abi'] = adb('shell','getprop','ro.product.cpu.abi').strip()
        command(['bash','./gradlew','--offline','--no-daemon','--console=plain','--no-configuration-cache',
                 '-PlocalPreview=true','-Pecolecta.servidorUrl=http://127.0.0.1:8765',
                 ':androidApp:assembleDebug',':androidApp:assembleDebugAndroidTest'],900)
        apk=ROOT/'androidApp/build/outputs/apk/debug/androidApp-debug.apk'
        test_apk=ROOT/'androidApp/build/outputs/apk/androidTest/debug/androidApp-debug-androidTest.apk'
        record['apk_sha256']=digest(apk.read_bytes())
        record['test_apk_sha256']=digest(test_apk.read_bytes())
        adb('install',str(apk)); adb('install',str(test_apk))
        adb('shell','input','keyevent','KEYCODE_WAKEUP'); adb('shell','wm','dismiss-keyguard')
        adb('shell','svc','wifi','disable'); adb('shell','svc','data','disable')
        record['network_state']=adb('shell','settings','get','global','wifi_on').strip()
        if record['network_state'] != '0': raise ValueError('No se confirmó WiFi apagado.')
        # UI observada fuera del proceso de Instrumentation, que finaliza su Activity al terminar.
        adb('shell','am','start','-W','-n','pe.ecolecta.preview/pe.ecolecta.MainActivity')
        ui_deadline=time.monotonic()+120
        visible=False
        while time.monotonic()<ui_deadline:
            adb('shell','uiautomator','dump','/sdcard/ecolactea-qa-ui.xml')
            xml=adb('shell','cat','/sdcard/ecolactea-qa-ui.xml')
            try:
                tree=ET.fromstring(xml)
                labels={n.get('text') for n in tree.iter('node')}
                if {'Usuario','PIN (4 dígitos)','Ingresar'}<=labels:
                    visible=True
                    (folder/'ui.xml').write_text(xml)
                    break
            except ET.ParseError:pass
        record['tests']['cases'].append({'class':'UIAutomation XML','name':'NAT-01-arranque-ui','state':'Passed' if visible else 'Failed'})
        if not visible:raise ValueError('No se observó formulario de acceso completo en el árbol UI.')
        image=subprocess.run([args.adb,'-s',args.serial,'exec-out','screencap','-p'],stdout=subprocess.PIPE,check=True).stdout
        if not image.startswith(b'\x89PNG'):raise ValueError('Captura UI no válida')
        (folder/'arranque.png').write_bytes(image)
        adb('shell','am','force-stop','pe.ecolecta.preview')
        def instrument(extra):
            output=adb('shell','am','instrument','-w','-e','entorno','ficticio-nuevo',*extra,
                       'pe.ecolecta.preview.test/pe.ecolecta.qa.VerificacionInstrumentada',timeout=300)
            match=re.search(r'INSTRUMENTATION_RESULT: qa_json=(\{[^\n]+\})', output)
            if not match: raise ValueError('Instrumentación sin recibo qa_json.')
            value=json.loads(match[1])
            record['tests']['cases'].extend({'class':'VerificacionInstrumentada','name':c['id'],'state':c['state'],**({'reason':c['reason']} if 'reason' in c else {})} for c in value['checks'])
            if not value['ok']:raise ValueError(value.get('reason','Falló instrumentación'))
            return value
        first=instrument(['-e','pinPrueba',pin])
        record['delivery_uuid']=first['delivery_uuid']
        adb('shell','am','force-stop','pe.ecolecta.preview')
        instrument(['-e','modo','reabrir','-e','uuid',first['delivery_uuid']])
        record.update(state='Passed',exit_code=0)
    except (ValueError,OSError,subprocess.SubprocessError) as error:
        record['state']='Failed'
        record['limitation']=str(error)
    finally:
        cases=record['tests']['cases']
        record['tests'].update(passed=sum(c['state']=='Passed' for c in cases),failed=sum(c['state']=='Failed' for c in cases),skipped=5-len(cases))
        record['end_utc']=now()
        record['obtained']=f"{record['tests']['passed']}/5 controles Passed; {record['tests']['skipped']} no ejecutados."
        clean=sanitize(raw,[(str(ROOT),'<REPO>'),(str(Path.home()),'<HOME>')], [pin])
        record['raw_log_sha256']=digest(raw.encode()); record['published_log_sha256']=digest(clean.encode())
        record['limitation']=record.get('limitation','Verifica motor OCR e imagen sintética, no cámara física ni lectura sanitaria. Registro mediante casos de uso nativos, no digitación GUI. Sin aceptación empresarial ni revisión independiente.')
        (folder/'original.log').write_text(raw);(folder/'original.log').chmod(0o600)
        publish(record,clean)
        target=ROOT/'docs/pruebas/ejecuciones/automaticas'/ident
        if (folder/'arranque.png').exists():
            image=(folder/'arranque.png').read_bytes();(target/'arranque.png').write_bytes(image)
            record['screenshot_sha256']=digest(image)
            if (folder/'ui.xml').exists():
                xml=(folder/'ui.xml').read_bytes();(target/'ui.xml').write_bytes(xml);record['ui_xml_sha256']=digest(xml)
            (target/'resultado.json').write_text(json.dumps(record,ensure_ascii=False,indent=2)+'\n')
            with (target/'README.md').open('a') as out:out.write('\n![Arranque real de la preview](arranque.png)\n')
        report(verbose=False)
        print(json.dumps({'id':ident,'state':record['state'],'tests':{k:v for k,v in record['tests'].items() if k!='cases'},'limitation':record['limitation']},ensure_ascii=False))
    return 0 if record['state']=='Passed' else 1


if __name__=='__main__':raise SystemExit(main())

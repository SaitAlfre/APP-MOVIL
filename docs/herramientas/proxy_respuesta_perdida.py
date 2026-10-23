#!/usr/bin/env python3
"""Fallo de red reproducible para una entrega ficticia. Solo escucha/reenvía en loopback.

El backend debe ser una copia con datos de prueba. No registra cuerpos ni credenciales.
La primera PUT correcta de ese UUID se persiste y pierde su respuesta. Después devuelve
503 sin reenviar hasta crear el archivo `permitir` en el directorio de salida temporal.
"""
import argparse
from datetime import datetime, timezone
from http.client import HTTPConnection
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
import json
from pathlib import Path
import socket
import tempfile
import threading
import uuid


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--uuid', required=True, type=uuid.UUID)
    parser.add_argument('--salida', required=True, type=Path)
    parser.add_argument('--port', type=int, default=8766)
    parser.add_argument('--backend-port', type=int, default=8765)
    args = parser.parse_args()
    folder = args.salida.resolve()
    if Path(tempfile.gettempdir()).resolve() not in folder.parents:
        parser.error('Usar un directorio nuevo dentro de la carpeta temporal del sistema')
    folder.mkdir(exist_ok=False)
    lock = threading.Lock()
    state = {'dropped': False, 'in_flight': False}

    def event(**data):
        with (folder / 'eventos.jsonl').open('a') as f:
            f.write(json.dumps({'utc': datetime.now(timezone.utc).isoformat(), **data})+'\n')

    class Handler(BaseHTTPRequestHandler):
        def log_message(self, *unused):
            pass

        def handle_request(self):
            path = self.path.split('?', 1)[0]
            target = self.command == 'PUT' and path.endswith('/entregas/'+str(args.uuid))
            drop = False
            if target and not (folder / 'permitir').exists():
                with lock:
                    if state['dropped'] or state['in_flight']:
                        payload = b'{"message":"Fallo de red controlado: entorno de pruebas"}'
                        self.send_response(503)
                        self.send_header('Content-Type', 'application/json')
                        self.send_header('Content-Length', str(len(payload)))
                        self.end_headers()
                        self.wfile.write(payload)
                        event(method=self.command, path=path, status=503, forwarded=False)
                        return
                    state['in_flight'] = True
                    drop = True
            data = self.rfile.read(int(self.headers.get('Content-Length', '0')))
            headers = {k: v for k,v in self.headers.items() if k.lower() not in {'host','connection','transfer-encoding'}}
            upstream = HTTPConnection('127.0.0.1', args.backend_port, timeout=30)
            try:
                upstream.request(self.command, self.path, body=data, headers=headers)
                response = upstream.getresponse()
                body = response.read()
                if drop and 200 <= response.status < 300:
                    with lock:
                        state['dropped'] = True
                        state['in_flight'] = False
                    event(method=self.command, path=path, status=response.status, forwarded=True, reply_dropped=True)
                    self.close_connection = True
                    self.connection.shutdown(socket.SHUT_RDWR)
                    self.connection.close()
                    return
                if drop:
                    with lock:
                        state['in_flight'] = False
                self.send_response(response.status)
                for name,value in response.getheaders():
                    if name.lower() not in {'content-length','connection','transfer-encoding'}:
                        self.send_header(name,value)
                self.send_header('Content-Length',str(len(body)))
                self.end_headers()
                self.wfile.write(body)
                event(method=self.command,path=path,status=response.status,forwarded=True)
            finally:
                upstream.close()

        do_GET = do_POST = do_PUT = do_PATCH = do_DELETE = handle_request

    server = ThreadingHTTPServer(('127.0.0.1',args.port),Handler)
    print('Proxy de prueba loopback listo; no usar datos productivos',flush=True)
    server.serve_forever()


if __name__ == '__main__':
    main()

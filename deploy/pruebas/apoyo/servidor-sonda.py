#!/usr/bin/env python3
"""Servidor HTTP mínimo para probar deploy/sonda-sitio.sh.

    servidor-sonda.py PUERTO CONFIG.json

CONFIG: {"/ruta?consulta": {"estado": 200, "cabeceras": {"X-Sede-Respaldo": "5"},
                            "demora": 0.0, "fallar_primeras": 0}}
`fallar_primeras` = cuántas peticiones a esa ruta responden 503 antes de responder bien
(un fallo transitorio, como el de un despliegue). Una ruta sin configurar responde 404.
Imprime `listo` cuando ya escucha.
"""
import json, sys, time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

puerto, config = int(sys.argv[1]), json.load(open(sys.argv[2]))
cuenta: dict[str, int] = {}


class H(BaseHTTPRequestHandler):
    def do_GET(self):
        c = config.get(self.path)
        if c is None:
            self.send_response(404); self.end_headers(); return
        for k, v in c.get("exigir", {}).items():
            if self.headers.get(k) != v:
                self.send_response(403); self.end_headers(); return
        cuenta[self.path] = cuenta.get(self.path, 0) + 1
        time.sleep(c.get("demora", 0))
        estado = 503 if cuenta[self.path] <= c.get("fallar_primeras", 0) else c.get("estado", 200)
        self.send_response(estado)
        for k, v in c.get("cabeceras", {}).items():
            self.send_header(k, v)
        self.send_header("Content-Length", "2")
        self.end_headers()
        self.wfile.write(b"ok")

    def log_message(self, *a):
        pass


s = ThreadingHTTPServer(("127.0.0.1", puerto), H)
print("listo", flush=True)
s.serve_forever()

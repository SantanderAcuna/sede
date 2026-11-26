#!/usr/bin/env python3
"""Origen FastCGI simulado para probar la caché de nginx (T9.7). NO es PHP-FPM.

Habla FastCGI de verdad (lo que nginx espera en `fastcgi_pass`) y responde con las cabeceras
que la aplicación emite en staging (medidas con GET de solo lectura el 2026-11-26):

  imagen pública      200  Cache-Control: max-age=300, public   ETag
  imagen del editor   200  Cache-Control: no-store, private     Set-Cookie (XSRF-TOKEN y sesión)
  no existe           404  Cache-Control: no-cache, private     (JSON)
  documento de política 200 Cache-Control: no-cache, private    ETag, Last-Modified, Content-Disposition

El comportamiento de cada ruta se cambia escribiendo un archivo en el directorio de estado:
  <ruta sin la barra inicial y con «/» → «_»>  con el texto  404 | 503 | lento | ok
  sin_set_cookie                                con el texto  si   (el editor no recibe Set-Cookie)

Uso: origen-fastcgi.py <directorio-de-estado> [puerto]     (escucha en 127.0.0.1)
"""
import os
import socketserver
import struct
import sys
import time

ESTADO = sys.argv[1]
PUERTO = int(sys.argv[2]) if len(sys.argv) > 2 else 9000
PDF = bytes(range(256)) * (3 * 1024 * 1024 // 256)  # 3 MiB


def registro(tipo, rid, datos):
    relleno = (-len(datos)) % 8
    return struct.pack('!BBHHBx', 1, tipo, rid, len(datos), relleno) + datos + b'\0' * relleno


def parametros(b):
    d, i = {}, 0

    def largo():
        nonlocal i
        n = b[i]
        if n >> 7:
            n = struct.unpack('!I', b[i:i + 4])[0] & 0x7fffffff
            i += 4
        else:
            i += 1
        return n

    while i < len(b):
        a = largo()
        c = largo()
        d[b[i:i + a].decode()] = b[i + a:i + a + c].decode('latin1')
        i += a + c
    return d


def modo(nombre):
    try:
        return open(os.path.join(ESTADO, nombre)).read().strip()
    except FileNotFoundError:
        return 'ok'


class Manejador(socketserver.StreamRequestHandler):
    def handle(self):
        p, rid = b'', 1
        while True:
            h = self.rfile.read(8)
            if len(h) < 8:
                return
            _, t, rid, cl, pl, _ = struct.unpack('!BBHHBB', h)
            cuerpo = self.rfile.read(cl)
            self.rfile.read(pl)
            if t == 4:
                p += cuerpo
            if t == 5 and cl == 0:
                break
        e = parametros(p)
        uri = e.get('REQUEST_URI', '')
        editor = 'sede-session=' in e.get('HTTP_COOKIE', '')
        m = modo(uri.strip('/').replace('/', '_'))
        sys.stderr.write('ORIGEN %s %s modo=%s\n' % (uri, 'editor' if editor else 'anon', m))
        sys.stderr.flush()
        if m == 'lento':
            time.sleep(8)
        if m in ('404', '503'):
            cab = ['Status: ' + m, 'Content-Type: application/json', 'Cache-Control: no-cache, private']
            cuerpo = b'{"success":false,"message":"El recurso no fue encontrado.","data":null,"errors":null}'
        elif uri.startswith('/politicas/'):
            cab = ['Status: 200', 'Content-Type: application/pdf',
                   'Content-Disposition: attachment; filename="tratamiento-datos-v3.pdf"',
                   'Cache-Control: no-cache, private', 'ETag: "p1"',
                   'Last-Modified: Wed, 25 Nov 2026 10:00:00 GMT', 'Content-Length: %d' % len(PDF)]
            cuerpo = PDF
        elif editor:
            cab = ['Status: 200', 'Content-Type: image/png', 'Cache-Control: no-store, private', 'ETag: "e1"']
            if modo('sin_set_cookie') != 'si':
                cab += ['Set-Cookie: XSRF-TOKEN=abc; path=/; secure; samesite=lax',
                        'Set-Cookie: sede-session=abc; path=/; secure; httponly; samesite=lax']
            cuerpo = b'BORRADOR'
        else:
            cab = ['Status: 200', 'Content-Type: image/png', 'Cache-Control: max-age=300, public', 'ETag: "e1"']
            cuerpo = b'PUBLICA'
        salida = ('\r\n'.join(cab) + '\r\n\r\n').encode() + cuerpo
        for k in range(0, len(salida), 32768):
            self.wfile.write(registro(6, rid, salida[k:k + 32768]))
        self.wfile.write(registro(6, rid, b'') + registro(3, rid, struct.pack('!IBxxx', 0, 0)))


socketserver.ThreadingTCPServer.allow_reuse_address = True
socketserver.ThreadingTCPServer(('127.0.0.1', PUERTO), Manejador).serve_forever()

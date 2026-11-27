#!/usr/bin/env python3
"""Origen que NO acepta conexiones (T9.7). Reproduce el contenedor `app` parado o inalcanzable.

Escucha con la cola de conexiones pendientes en su mínimo y no llama nunca a `accept()`. Se llenan
los huecos de esa cola con dos conexiones propias; a partir de ahí el núcleo descarta los SYN
siguientes y el `connect()` de nginx se queda esperando hasta su `fastcgi_connect_timeout`, que es
lo que se mide en staging con `app` detenido («upstream timed out (110) while connecting»). Un
origen que rechaza la conexión (RST) no sirve para esto: falla al instante.

Uso: origen-colgado.py [puerto]     (escucha en 127.0.0.1 hasta que lo matan)
"""
import socket
import sys
import time

puerto = int(sys.argv[1]) if len(sys.argv) > 1 else 9000
servidor = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
servidor.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
servidor.bind(("127.0.0.1", puerto))
servidor.listen(0)

relleno = []
for _ in range(2):
    c = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    c.settimeout(1)
    try:
        c.connect(("127.0.0.1", puerto))
    except OSError:
        pass
    relleno.append(c)

print("listo", flush=True)
while True:
    time.sleep(3600)

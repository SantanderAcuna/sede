#!/usr/bin/env python3
"""Comprueba si una contraseña coincide con la registrada en /etc/shadow.

Se ejecuta como root. La contraseña llega por la entrada estándar, nunca como
argumento, para que no aparezca en el listado de procesos del servidor.

    printf '%s\\n' "$CONTRASENA" | python3 verificar-contrasena.py ops
"""
import crypt
import sys

usuario = sys.argv[1]
contrasena = sys.stdin.readline().rstrip("\n")

with open("/etc/shadow", encoding="utf-8") as archivo:
    for linea in archivo:
        campos = linea.rstrip("\n").split(":")
        if campos[0] != usuario:
            continue

        almacenado = campos[1]
        if almacenado in ("", "!", "*", "!!") or almacenado.startswith("!"):
            print(f"NO_UTILIZABLE: la cuenta {usuario} no tiene contraseña utilizable")
            sys.exit(2)

        print("COINCIDE" if crypt.crypt(contrasena, almacenado) == almacenado else "NO_COINCIDE")
        sys.exit(0)

print(f"NO_EXISTE: no hay entrada para {usuario} en /etc/shadow")
sys.exit(3)

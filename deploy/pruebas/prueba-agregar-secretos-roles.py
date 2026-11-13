#!/usr/bin/env python3
"""Prueba `agregar-secretos-roles.py` sobre archivos TEMPORALES (no toca el registro real).

    python3 deploy/pruebas/prueba-agregar-secretos-roles.py
"""
from __future__ import annotations

import importlib.util
import pathlib
import sys
import tempfile

RAIZ = pathlib.Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location("agregar", RAIZ / "deploy" / "agregar-secretos-roles.py")
agregar = importlib.util.module_from_spec(spec)
spec.loader.exec_module(agregar)

fallos: list[str] = []


def comprobar(ok: bool, texto: str) -> None:
    print(("  \033[32m✓\033[0m " if ok else "  \033[31m✗\033[0m ") + texto)
    if not ok:
        fallos.append(texto)


d = pathlib.Path(tempfile.mkdtemp())
env, pas = d / ".accesos.local.env", d / "pass.md"
env.write_text("DB_PASSWORD_STAGING=aaa\nDB_PASSWORD_PRODUCTION=bbb\nDB_APP_PASSWORD_STAGING=ya-existe\n", encoding="utf-8")
pas.write_text("| clave | staging | produccion |\n|---|---|---|\n| DB_PASSWORD | `aaa` | `bbb` |\n| REDIS_PASSWORD | `r` | `r` |\n", encoding="utf-8")

hechas = agregar.agregar(env, pas)
e = dict(l.split("=", 1) for l in env.read_text().splitlines() if "=" in l)
p = pas.read_text()

comprobar(e["DB_APP_PASSWORD_STAGING"] == "ya-existe", "no sobrescribe lo que ya existía")
comprobar(all(k in e for k in ["DB_APP_PASSWORD_PRODUCTION", "DB_LECTURA_PASSWORD_STAGING", "DB_LECTURA_PASSWORD_PRODUCTION"]), "añade las claves que faltaban")
comprobar(len(e["DB_LECTURA_PASSWORD_STAGING"]) == 48, "genera 24 bytes en hexadecimal")
comprobar("| DB_APP_PASSWORD | `ya-existe` |" in p, "la fila de pass.md lleva el mismo valor que el registro")
comprobar(p.index("DB_PASSWORD") < p.index("DB_APP_PASSWORD") < p.index("REDIS_PASSWORD"), "las filas van tras DB_PASSWORD")
comprobar(oct(env.stat().st_mode)[-3:] == "600" and oct(pas.stat().st_mode)[-3:] == "600", "los archivos quedan 0600")
antes = (env.read_text(), pas.read_text())
comprobar(agregar.agregar(env, pas) == [] and (env.read_text(), pas.read_text()) == antes, "idempotente: la segunda vez no cambia nada")
sys.exit(1 if fallos else 0)

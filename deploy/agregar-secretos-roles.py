#!/usr/bin/env python3
"""Añade al registro local las claves de los roles de la base (Fase 0A, ADR-0004).

    python3 deploy/agregar-secretos-roles.py

Añade, SI FALTAN, `DB_APP_PASSWORD_*` y `DB_LECTURA_PASSWORD_*` (staging y producción) a
`.accesos.local.env` y sus filas a `pass.md`. **No sobrescribe nada que ya exista** y no
imprime ningún valor: sólo los nombres. Es idempotente: una segunda ejecución no cambia nada.

Para ROTARLAS después se usa `deploy/rotar-secretos.py --aplicacion` como siempre.
"""
from __future__ import annotations

import importlib.util
import pathlib
import re
import sys

RAIZ = pathlib.Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("rotar", RAIZ / "deploy" / "rotar-secretos.py")
rotar = importlib.util.module_from_spec(spec)
spec.loader.exec_module(rotar)

CLAVES = ["DB_APP_PASSWORD", "DB_LECTURA_PASSWORD"]


def agregar(env_path: pathlib.Path, pass_path: pathlib.Path) -> list[str]:
    rotar.ENV, rotar.PASS = env_path, pass_path
    env = env_path.read_text(encoding="utf-8").splitlines()
    pas = pass_path.read_text(encoding="utf-8").splitlines()
    nuevas: list[str] = []

    for clave in CLAVES:
        st, pr = f"{clave}_STAGING", f"{clave}_PRODUCTION"
        existentes = {m.group(1) for l in env if (m := re.match(r"^([A-Z0-9_]+)=", l))}
        if st not in existentes:
            env.append(f"{st}={rotar.generar_hex()}")
            nuevas.append(st)
        if pr not in existentes:
            env.append(f"{pr}={rotar.generar_hex()}")
            nuevas.append(pr)

        # La fila de pass.md debe llevar el MISMO valor que el registro.
        valores = {k: v for l in env if "=" in l for k, v in [l.split("=", 1)]}
        tiene_fila = any(
            l.startswith("|") and l.strip().strip("|").split("|")[0].strip().strip("`") == clave for l in pas
        )
        if not tiene_fila:
            fila = f"| {clave} | `{valores[st]}` | `{valores[pr]}` |"
            destino = next(
                (i for i, l in enumerate(pas) if l.startswith("|") and l.strip().strip("|").split("|")[0].strip().strip("`") == "DB_PASSWORD"),
                None,
            )
            if destino is None:
                sys.exit("ABORTADO: no encontré la fila «DB_PASSWORD» en pass.md; no se escribió nada.")
            pas.insert(destino + 1, fila)
            nuevas.append(f"fila {clave} en pass.md")

    if nuevas:
        rotar.escribir_env(env)
        rotar.escribir_pass(pas)
    return nuevas


if __name__ == "__main__":
    for p in (rotar.ENV, rotar.PASS):
        if not p.exists():
            sys.exit(f"Falta {p}. Corre este guion desde el repositorio donde está tu registro local.")
    rotar.exigir_no_rastreado()
    hechas = agregar(rotar.ENV, rotar.PASS)
    print("Añadido:" if hechas else "Nada que añadir: el registro ya tenía todo.")
    for h in hechas:
        print(f"  + {h}")

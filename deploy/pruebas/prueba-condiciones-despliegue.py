#!/usr/bin/env python3
"""Qué entorno despliega cada evento (.github/workflows/despliegue.yml).

    python3 deploy/pruebas/prueba-condiciones-despliegue.py

Regla que se comprueba: cada entorno se despliega SOLO desde su rama —staging desde
`staging`, producción desde `main`—, tanto por empuje como a mano. Así lo que llega a un
servidor siempre recorrió develop → staging → main, y el grupo de concurrencia (que se
calcula por rama) es el mismo para todo lo que toca un mismo servidor.

Por qué existe (2026-11-24). Con las condiciones anteriores:
  - lanzar a mano «staging» desde `main` ejecutaba TAMBIÉN el trabajo de producción
    (`github.ref_name == 'main'` bastaba), y desplegaba en staging el commit de main;
  - lanzar a mano «produccion» desde `staging` desplegaba en producción el commit de
    staging, y además en staging;
  - el grupo de concurrencia es `despliegue-<rama>`: un despliegue a mano desde otra rama
    caía en otro grupo y podía correr A LA VEZ que el de la rama sobre el mismo servidor.

Se evalúan las expresiones `if:` reales del archivo con un intérprete mínimo de la sintaxis
que usan (==, !=, &&, ||, !, paréntesis, cadenas, `github.*`, `inputs.*`, `vars.*`).
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

import yaml

RAIZ = Path(__file__).resolve().parents[2]
FLUJO = RAIZ / ".github/workflows/despliegue.yml"

TOKEN = re.compile(r"\s*(?:(\|\||&&|==|!=|!|\(|\))|'([^']*)'|([A-Za-z_][A-Za-z0-9_.]*))")


def evaluar(expr: str, ctx: dict[str, str]) -> bool:
    expr = expr.strip()
    if expr.startswith("${{") and expr.endswith("}}"):
        expr = expr[3:-2]
    toks: list[tuple[str, str]] = []
    pos = 0
    while pos < len(expr):
        if expr[pos:].strip() == "":
            break
        m = TOKEN.match(expr, pos)
        if not m:
            raise ValueError(f"sintaxis no soportada en: {expr[pos:]!r}")
        op, cad, ident = m.groups()
        toks.append(("op", op) if op else ("cad", cad) if cad is not None else ("id", ident))
        pos = m.end()
    i = 0

    def ver() -> tuple[str, str] | None:
        return toks[i] if i < len(toks) else None

    def tomar() -> tuple[str, str]:
        nonlocal i
        t = toks[i]
        i += 1
        return t

    def primario() -> object:
        t = tomar()
        if t == ("op", "!"):
            return not verdad(primario())
        if t == ("op", "("):
            v = o()
            if tomar() != ("op", ")"):
                raise ValueError("falta ')'")
            return v
        if t[0] == "cad":
            return t[1]
        if t[0] == "id":
            if t[1] not in ctx:
                raise ValueError(f"contexto desconocido: {t[1]}")
            return ctx[t[1]]
        raise ValueError(f"token inesperado {t}")

    def comparacion() -> object:
        izq = primario()
        while ver() in (("op", "=="), ("op", "!=")):
            op = tomar()[1]
            der = primario()
            izq = (str(izq).lower() == str(der).lower()) if op == "==" else (str(izq).lower() != str(der).lower())
        return izq

    def verdad(v: object) -> bool:
        return bool(v) and v != ""

    def y() -> object:
        v = comparacion()
        while ver() == ("op", "&&"):
            tomar()
            d = comparacion()
            v = d if verdad(v) else v
        return v

    def o() -> object:
        v = y()
        while ver() == ("op", "||"):
            tomar()
            d = y()
            v = v if verdad(v) else d
        return v

    r = o()
    if i != len(toks):
        raise ValueError("sobran tokens")
    return verdad(r)


def main() -> int:
    flujo = yaml.safe_load(FLUJO.read_text(encoding="utf-8"))
    trabajos = flujo["jobs"]
    cond = {n: trabajos[n]["if"] for n in ("staging", "produccion")}

    # (evento, rama, entrada «entorno») -> trabajos que deben ejecutarse
    casos = [
        ("push", "staging", "", {"staging"}),
        ("push", "main", "", {"produccion"}),
        ("workflow_dispatch", "staging", "staging", {"staging"}),
        ("workflow_dispatch", "main", "produccion", {"produccion"}),
        ("workflow_dispatch", "main", "staging", set()),
        ("workflow_dispatch", "staging", "produccion", set()),
        ("workflow_dispatch", "develop", "staging", set()),
        ("workflow_dispatch", "develop", "produccion", set()),
    ]
    fallos = 0
    for evento, rama, entorno, esperado in casos:
        ctx = {
            "github.event_name": evento,
            "github.ref_name": rama,
            "inputs.entorno": entorno,
            "vars.SEDE_DESPLIEGUE_STAGING": "true",
            "vars.SEDE_DESPLIEGUE_PRODUCCION": "true",
        }
        obtenido = {n for n, e in cond.items() if evaluar(e, ctx)}
        desc = f"{evento} en '{rama}'" + (f" con entorno={entorno}" if entorno else "")
        if obtenido == esperado:
            print(f"  ✓ {desc} → {sorted(obtenido) or 'ninguno'}")
        else:
            fallos += 1
            print(f"  ✗ {desc} → {sorted(obtenido) or 'ninguno'} (se esperaba {sorted(esperado) or 'ninguno'})", file=sys.stderr)

    # Con las variables apagadas no se despliega nada, ni a mano.
    for evento, rama, entorno, _ in casos:
        ctx = {"github.event_name": evento, "github.ref_name": rama, "inputs.entorno": entorno,
               "vars.SEDE_DESPLIEGUE_STAGING": "", "vars.SEDE_DESPLIEGUE_PRODUCCION": ""}
        if any(evaluar(e, ctx) for e in cond.values()):
            fallos += 1
            print(f"  ✗ con las variables apagadas, {evento} en '{rama}' despliega", file=sys.stderr)
    if fallos == 0:
        print("  ✓ con las variables del repositorio apagadas no se despliega nada")

    grupo = str(flujo.get("concurrency", {}).get("group", ""))
    if "github.ref_name" in grupo:
        print("  ✓ el grupo de concurrencia es por rama, y cada entorno sale de una sola rama")
    else:
        fallos += 1
        print(f"  ✗ el grupo de concurrencia ya no es por rama: {grupo!r}", file=sys.stderr)

    if fallos:
        print(f"\n{fallos} caso(s) fallaron.", file=sys.stderr)
        return 1
    print("\nCada entorno se despliega solo desde su rama.")
    return 0


if __name__ == "__main__":
    sys.exit(main())

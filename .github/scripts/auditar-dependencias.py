#!/usr/bin/env python3
"""
Auditoría de dependencias: distingue lo corregible de lo que no lo es.

Por qué existe este archivo
---------------------------
La puerta de dependencias falla ante vulnerabilidades altas o críticas
**corregibles**, que es lo que su propio mensaje declara. La distinción importa:
hay advisories que ninguna actualización del bloqueo puede resolver porque no
existe versión corregida aguas arriba, y una puerta que falla también en esos
casos no protege nada — se acaba desactivando.

`npm audit` sólo devuelve un código de salida y no separa ambos casos, así que
aquí se lee su informe JSON. Vivía embebido en el flujo con un heredoc, y eso
rompió el YAML: el terminador del heredoc tiene que ir en la columna cero, y una
línea sin indentar cierra el bloque escalar de `run: |`. Sacarlo a un archivo
propio elimina ese campo de minas y además permite probarlo sin ejecutar el flujo.

Uso
---
    python3 auditar-dependencias.py <informe.json> [advisory-tolerado ...]

Imprime `corregibles toleradas injustificadas` y sale con 1 si hay algo que
romper la construcción.
"""

from __future__ import annotations

import json
import sys


def advisories(vulnerabilidad: dict) -> set[str]:
    """Los identificadores de advisory que afectan **directamente** a este paquete."""
    return {
        entrada.get("url", "").rstrip("/").split("/")[-1]
        for entrada in (vulnerabilidad.get("via") or [])
        if isinstance(entrada, dict)
    }


def padres(vulnerabilidad: dict) -> set[str]:
    """Los paquetes que arrastran a éste: en `via` van como texto, no como objeto."""
    return {entrada for entrada in (vulnerabilidad.get("via") or []) if isinstance(entrada, str)}


def auditar(informe: dict, toleradas: set[str]) -> tuple[int, int, int]:
    """Devuelve (corregibles, toleradas, injustificadas) y explica cada fallo."""
    vulnerabilidades = informe.get("vulnerabilities") or {}

    # Un paquete está justificado si el advisory tolerado lo alcanza y no tiene
    # ningún advisory propio sin justificar.
    #
    # La propagación tiene que ser «alguno de sus padres», no «todos»: `nuxt` y
    # `@nuxt/nitro-server` se referencian mutuamente, y con «todos» el ciclo no se
    # cierra nunca y la puerta fallaría para siempre. Con «alguno» el ciclo se
    # resuelve, y la segunda condición evita el abuso: un paquete que además tenga
    # su propio advisory no tolerado sigue contando como fallo.
    alcanzados = {
        nombre for nombre, v in vulnerabilidades.items() if advisories(v) & toleradas
    }
    cambio = True
    while cambio:
        cambio = False
        for nombre, v in vulnerabilidades.items():
            if nombre in alcanzados:
                continue
            if padres(v) & alcanzados:
                alcanzados.add(nombre)
                cambio = True

    corregibles = 0
    toleradas_n = 0
    injustificadas = 0

    for nombre, v in vulnerabilidades.items():
        if v.get("severity") not in ("high", "critical"):
            continue

        propias = advisories(v)
        if nombre in alcanzados and not (propias - toleradas):
            toleradas_n += 1
            continue

        if v.get("fixAvailable"):
            corregibles += 1
            print(
                f"::error::{nombre}: {v.get('severity')}, con arreglo disponible",
                file=sys.stderr,
            )
        else:
            injustificadas += 1
            print(
                f"::error::{nombre}: alta o crítica SIN arreglo y SIN justificar",
                file=sys.stderr,
            )

    return corregibles, toleradas_n, injustificadas


def main() -> int:
    if len(sys.argv) < 2:
        print("falta la ruta del informe de npm audit", file=sys.stderr)
        return 2

    ruta, *permitidas = sys.argv[1:]

    try:
        with open(ruta, encoding="utf-8") as f:
            informe = json.load(f)
    except Exception:
        # Sin informe no se puede afirmar que no haya vulnerabilidades. Se falla.
        print("::error::no se pudo leer el informe de npm audit", file=sys.stderr)
        print("1 0 1")
        return 1

    corregibles, toleradas, injustificadas = auditar(informe, set(permitidas))
    print(f"{corregibles} {toleradas} {injustificadas}")
    return 1 if (corregibles or injustificadas) else 0


if __name__ == "__main__":
    sys.exit(main())

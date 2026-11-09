#!/usr/bin/env python3
"""Prueba que `rotar-secretos.py` no rota la frase de las copias sin que se pida.

    python3 deploy/pruebas/prueba-rotar-secretos.py

Por qué existe: `BACKUP_PASSPHRASE` cifra cada copia de seguridad. Rotarla junto con
las demás contraseñas (que era lo que hacía `--aplicacion`) dejaba sin poder abrirse,
sin aviso, todo lo copiado hasta ese día.

Trabaja sobre archivos TEMPORALES: no toca `.accesos.local.env` ni `pass.md` reales y
no llama a GitHub ni al servidor.
"""
from __future__ import annotations

import contextlib
import importlib.util
import io
import pathlib
import sys
import tempfile

RAIZ = pathlib.Path(__file__).resolve().parents[2]
CLAVES = ["APP_KEY", "DB_PASSWORD", "REDIS_PASSWORD", "S3_ACCESS_KEY", "S3_SECRET_KEY", "BACKUP_PASSPHRASE"]

spec = importlib.util.spec_from_file_location("rotar", RAIZ / "deploy" / "rotar-secretos.py")
rotar = importlib.util.module_from_spec(spec)
spec.loader.exec_module(rotar)

fallos: list[str] = []


def comprobar(condicion: bool, texto: str) -> None:
    print(("  \033[32m✓\033[0m " if condicion else "  \033[31m✗\033[0m ") + texto)
    if not condicion:
        fallos.append(texto)


def registro_nuevo() -> tuple[pathlib.Path, pathlib.Path]:
    carpeta = pathlib.Path(tempfile.mkdtemp())
    env = carpeta / ".accesos.local.env"
    pas = carpeta / "pass.md"
    env.write_text(
        "\n".join(f"{k}_{e}=viejo_{k}_{e}" for k in CLAVES for e in ("STAGING", "PRODUCTION")) + "\n",
        encoding="utf-8",
    )
    pas.write_text(
        "| clave | staging | produccion |\n|---|---|---|\n"
        + "\n".join(f"| {k} | viejo | viejo |" for k in CLAVES)
        + "\n",
        encoding="utf-8",
    )
    return env, pas


def rotar_con(env: pathlib.Path, pas: pathlib.Path, frase: bool) -> dict[str, str]:
    rotar.ENV, rotar.PASS = env, pas
    rotar.exigir_no_rastreado = lambda: None
    with contextlib.redirect_stdout(io.StringIO()):
        rotar.rotar_aplicacion(False, frase)
    return dict(l.split("=", 1) for l in env.read_text(encoding="utf-8").splitlines() if "=" in l)


env, pas = registro_nuevo()
a = rotar_con(env, pas, frase=False)
comprobar(a["BACKUP_PASSPHRASE_STAGING"] == "viejo_BACKUP_PASSPHRASE_STAGING", "sin --frase-copias la frase de staging NO cambia")
comprobar(a["BACKUP_PASSPHRASE_PRODUCTION"] == "viejo_BACKUP_PASSPHRASE_PRODUCTION", "sin --frase-copias la frase de producción NO cambia")
comprobar(a["APP_KEY_STAGING"] != "viejo_APP_KEY_STAGING", "el resto de secretos sí se rota")
comprobar(not any("ANTERIOR" in k for k in a), "sin --frase-copias no se crean entradas *_ANTERIOR_*")

env, pas = registro_nuevo()
b = rotar_con(env, pas, frase=True)
comprobar(b["BACKUP_PASSPHRASE_STAGING"] != "viejo_BACKUP_PASSPHRASE_STAGING", "con --frase-copias la frase de staging se rota")
anteriores = {k: v for k, v in b.items() if "ANTERIOR" in k}
comprobar(len(anteriores) == 2, "con --frase-copias se guardan las dos frases anteriores (staging y producción)")
comprobar(
    "viejo_BACKUP_PASSPHRASE_STAGING" in anteriores.values() and "viejo_BACKUP_PASSPHRASE_PRODUCTION" in anteriores.values(),
    "las frases anteriores conservan el valor que tenían antes de rotar",
)

sys.exit(1 if fallos else 0)

#!/bin/bash
#
# Guarda de los montajes del compose.yaml (Fase 0A, corrección del arranque de PostgreSQL).
#
#   bash deploy/pruebas/guarda-montajes-compose.sh
#
# El problema que evita: al servidor sólo llegan TRES archivos de configuración —`compose.yaml`,
# `plantilla.env` y `valores-<entorno>.env`, que `desplegar.sh` extrae de la imagen— más lo que
# `publicar-entorno.sh` instala una vez. Un montaje del anfitrión con ruta relativa
# (`./docker/...`) apunta a un archivo que en el servidor NO existe: Docker crea un DIRECTORIO
# con ese nombre y el contenedor muere con «is a directory: permission denied». Pasó con el
# punto de entrada de PostgreSQL y no lo vio ninguna prueba, porque todas corren en un
# repositorio donde el archivo sí existe.
#
# Comprueba:
#   1. Todo montaje relativo del anfitrión es un DIRECTORIO que el servidor sí tiene o que
#      Docker puede crear vacío sin romper nada (lista blanca explícita, abajo).
#   2. Ningún montaje relativo apunta a un archivo (`.sh`, `.conf`, `.env`, `.sql`…).
#   3. El punto de entrada de PostgreSQL viaja DENTRO de `compose.yaml` (`configs.content`) y es
#      idéntico, byte a byte, a `docker/postgres/entrypoint-tls.sh`, que sigue siendo la fuente
#      legible y revisable. Una copia que se desincroniza en silencio es peor que ninguna.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="$RAIZ/compose.yaml"
FUENTE="$RAIZ/docker/postgres/entrypoint-tls.sh"

[ -f "$COMPOSE" ] || { printf '\033[31mFalta %s\033[0m\n' "$COMPOSE" >&2; exit 1; }
[ -f "$FUENTE" ]  || { printf '\033[31mFalta %s\033[0m\n' "$FUENTE" >&2; exit 1; }

python3 - "$COMPOSE" "$FUENTE" <<'PY'
import re
import sys

import yaml

compose_path, fuente_path = sys.argv[1], sys.argv[2]
with open(compose_path, encoding="utf-8") as f:
    compose = yaml.safe_load(f)

# Directorios relativos que se pueden montar desde el anfitrión: los crea `certificado.sh`
# (nginx) o Docker, vacíos (certificados de la base, que sólo se leen con POSTGRES_SSL=on).
PERMITIDOS = {"./docker/nginx/certs", "./docker/postgres/certs"}
EXTENSIONES_DE_ARCHIVO = (".sh", ".conf", ".env", ".sql", ".yaml", ".yml", ".json", ".ini", ".crt", ".key", ".pem")

fallos = 0


def ok(msg):
    print(f"  \033[32m✓\033[0m {msg}")


def mal(msg):
    global fallos
    fallos += 1
    print(f"  \033[31m✗ {msg}\033[0m", file=sys.stderr)


def origen_relativo(fuente):
    """Devuelve la ruta relativa de un montaje (resolviendo `${VAR:-./ruta}`), o None."""
    m = re.fullmatch(r"\$\{[A-Z_]+:-(\./[^}]*)\}", fuente)
    if m:
        return m.group(1)
    if fuente.startswith("./") or fuente.startswith("../"):
        return fuente
    return None


# --- 1 y 2. Montajes del anfitrión --------------------------------------------
relativos = []
for nombre, servicio in (compose.get("services") or {}).items():
    for v in servicio.get("volumes") or []:
        if isinstance(v, str):
            # El origen puede ser `${VAR:-./ruta}`, que lleva un `:` propio: no se parte por él.
            fuente = re.match(r"(\$\{[^}]*\}|[^:]*)", v).group(1)
        else:
            fuente = v.get("source", "")
        ruta = origen_relativo(fuente)
        if ruta is not None:
            relativos.append((nombre, ruta))

print("Montajes relativos del anfitrión")
for nombre, ruta in relativos:
    if ruta.endswith(EXTENSIONES_DE_ARCHIVO):
        mal(f"{nombre}: monta el ARCHIVO {ruta} desde el anfitrión; en el servidor no existe y Docker "
            "crearía un directorio. Llévalo dentro de compose.yaml (configs.content).")
    elif ruta.rstrip("/") not in PERMITIDOS:
        mal(f"{nombre}: monta {ruta}, que no está en la lista de directorios que el servidor tiene.")
    else:
        ok(f"{nombre}: {ruta}")
if not relativos:
    ok("ninguno")

# --- 3. El punto de entrada de PostgreSQL viaja dentro del compose ------------
print("Punto de entrada de PostgreSQL")
with open(fuente_path, encoding="utf-8") as f:
    fuente = f.read()

cfg = (compose.get("configs") or {}).get("entrypoint_tls")
if not cfg or "content" not in cfg:
    mal("compose.yaml no define configs.entrypoint_tls.content")
else:
    # En Compose, `$$` es un `$` literal; la comparación se hace con el contenido ya resuelto.
    contenido = cfg["content"].replace("$$", "$")
    if contenido == fuente:
        ok("configs.entrypoint_tls.content == docker/postgres/entrypoint-tls.sh")
    else:
        mal("configs.entrypoint_tls.content difiere de docker/postgres/entrypoint-tls.sh "
            "(editar el archivo y regenerar el bloque con deploy/pruebas/guarda-montajes-compose.sh --regenerar).")

pg = (compose.get("services") or {}).get("postgres", {})
usa = [c for c in (pg.get("configs") or []) if (c.get("source") if isinstance(c, dict) else c) == "entrypoint_tls"]
if not usa:
    mal("el servicio postgres no consume configs.entrypoint_tls")
else:
    c = usa[0]
    if not isinstance(c, dict) or c.get("target") != "/usr/local/bin/entrypoint-tls.sh":
        mal("postgres debe montar entrypoint_tls en /usr/local/bin/entrypoint-tls.sh")
    elif pg.get("entrypoint") != ["/bin/sh", "/usr/local/bin/entrypoint-tls.sh"]:
        mal("postgres debe arrancar con `sh` explícito (los configs no garantizan el bit de ejecución); "
            f"trae {pg.get('entrypoint')!r}")
    else:
        ok("postgres lo monta en /usr/local/bin/entrypoint-tls.sh y lo arranca con sh explícito")

sys.exit(1 if fallos else 0)
PY
estado=$?

if [ "$estado" -ne 0 ]; then
  printf '\n\033[31mLa guarda de montajes falló.\033[0m\n' >&2
  exit 1
fi
printf '\n\033[32mMontajes del compose coherentes con lo que llega al servidor.\033[0m\n'

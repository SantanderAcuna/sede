#!/bin/bash
#
# Guarda del acceso del sitio a Redis (Fase 0C, T9.5).
#
#   bash deploy/pruebas/guarda-cache-sitio.sh
#
# El respaldo de la última respuesta válida (T9.4) guarda copias en Redis desde el servidor del
# sitio. Eso abre un camino nuevo: el sitio es el contenedor que renderiza y reenvía contenido
# de terceros, el más expuesto de la pila, y pasa a poder hablar con la caché que también guarda
# las sesiones y las colas de la aplicación. Lo que acota el daño de un sitio comprometido son
# TRES cosas a la vez, y las tres tienen que sostenerse (si falla una, las otras no bastan):
#
#   1. Red: el sitio NO entra en la red `data`; entra en una red interna propia (`cache-sitio`)
#      donde sólo están él y Redis. No llega a PostgreSQL.
#   2. Credencial: el sitio usa un usuario ACL `sitio` que sólo puede GET/SET/DEL/PING/AUTH
#      sobre claves `sitio:*`. Su contraseña NO es la de Redis (`REDIS_PASSWORD`).
#   3. Entorno: el contenedor del sitio no lleva ningún otro secreto. Antes de T9.5 recibía el
#      `.env` ENTERO (clave de la aplicación, clave del dueño de la base, frase de las copias,
#      `REDIS_PASSWORD`...): con la red y el usuario ACL puestos, el ataque más corto habría sido
#      leer `REDIS_PASSWORD` de su propio entorno y entrar como `default`.
#
# Esta guarda lee la pila YA RESUELTA (`docker compose config`), no el texto del YAML, para que
# también la atrape una variable o una anulación. Usa valores de prueba DISTINTOS para cada
# secreto: así puede comprobar que ninguno se cuela donde no debe.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$RAIZ" || exit 1

fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

command -v docker >/dev/null || { printf '\033[31mFalta docker (sólo se usa `docker compose config`).\033[0m\n' >&2; exit 1; }
command -v python3 >/dev/null || { printf '\033[31mFalta python3.\033[0m\n' >&2; exit 1; }

# Un valor distinto y reconocible por secreto. Ninguno parece real. Van en un `.env` de una
# carpeta temporal, junto a una copia del compose: es lo que ocurre en el servidor, y sin ese
# `.env` el `env_file` del sitio (si lo hubiera) se resolvería VACÍO y la comprobación de
# «ningún otro secreto llega al sitio» pasaría sin haber comprobado nada.
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
cp compose.yaml "$tmp/compose.yaml"
cat > "$tmp/.env" <<'ENV'
APP_KEY=base64:VALOR-APP-KEY
DB_PASSWORD=VALOR-DB-DUENO
DB_APP_PASSWORD=VALOR-DB-APP
DB_LECTURA_PASSWORD=VALOR-DB-LECTURA
REDIS_PASSWORD=VALOR-REDIS-PRINCIPAL
REDIS_SITIO_PASSWORD=VALOR-REDIS-SITIO
BACKUP_PASSPHRASE=VALOR-FRASE-COPIAS
SEDE_DOMINIO=dominio.prueba.local
ENV
pila="$(cd "$tmp" && env -i PATH="$PATH" HOME="${HOME:-/tmp}" DOCKER_HOST="${DOCKER_HOST:-}" docker compose -f compose.yaml config --format json 2>&1)" \
  || { printf '\033[31mLa pila de producción no se resuelve:\n%s\033[0m\n' "$pila" >&2; exit 1; }

# Sin la contraseña del usuario `sitio` la pila NO se resuelve: un despliegue sin ella se detiene
# con un mensaje que dice cuál falta, en vez de arrancar Redis sin el usuario o el sitio sin clave.
grep -v '^REDIS_SITIO_PASSWORD=' "$tmp/.env" > "$tmp/.env.sin" && mv "$tmp/.env.sin" "$tmp/.env"
if (cd "$tmp" && env -i PATH="$PATH" HOME="${HOME:-/tmp}" DOCKER_HOST="${DOCKER_HOST:-}" docker compose -f compose.yaml config --quiet >/dev/null 2>&1); then
  mal "la pila se resolvió SIN REDIS_SITIO_PASSWORD: la exigencia (:?) no funciona"
else
  ok "sin REDIS_SITIO_PASSWORD la pila no se resuelve, como debe"
fi
echo 'REDIS_SITIO_PASSWORD=VALOR-REDIS-SITIO' >> "$tmp/.env"

PILA="$pila" python3 - <<'PY'
import json
import os
import sys

import yaml

pila = json.loads(os.environ["PILA"])
with open("compose.yaml", encoding="utf-8") as f:
    compose_crudo = yaml.safe_load(f)
servicios = pila["services"]
redes = pila.get("networks", {})
fallos = 0


def ok(m):
    print(f"  \033[32m✓\033[0m {m}")


def mal(m):
    global fallos
    fallos += 1
    print(f"  \033[31m✗ {m}\033[0m", file=sys.stderr)


def comprobar(cond, bien, mal_):
    ok(bien) if cond else mal(mal_)


sitio, redis = servicios["sitio"], servicios["redis"]
nombres_red = lambda s: sorted((s.get("networks") or {}).keys())

# --- 1. Red --------------------------------------------------------------------------------
comprobar(nombres_red(sitio) == ["backend", "cache-sitio"],
          "el sitio está en `backend` y `cache-sitio`, y en ninguna más",
          f"redes del sitio: {nombres_red(sitio)} (deben ser backend y cache-sitio; `data` le daría PostgreSQL)")
comprobar(nombres_red(redis) == ["cache-sitio", "data"],
          "Redis está en `data` y `cache-sitio`",
          f"redes de Redis: {nombres_red(redis)} (deben ser cache-sitio y data)")
comprobar(redes.get("cache-sitio", {}).get("internal") is True,
          "`cache-sitio` es interna: no sale ni entra tráfico de fuera de la pila",
          "`cache-sitio` no está marcada `internal: true`")
miembros = sorted(n for n, s in servicios.items() if "cache-sitio" in (s.get("networks") or {}))
comprobar(miembros == ["redis", "sitio"],
          "en `cache-sitio` sólo están `redis` y `sitio`",
          f"miembros de `cache-sitio`: {miembros}")
comprobar(not redis.get("ports"),
          "Redis no publica ningún puerto",
          f"Redis publica puertos: {redis.get('ports')}")

# --- 2. Credencial: usuario ACL `sitio` ---------------------------------------------------
cmd = [str(x) for x in redis["command"]]
REGLAS = ["on", ">VALOR-REDIS-SITIO", "~sitio:*", "-@all", "+get", "+set", "+del", "+ping", "+auth"]
if "--user" in cmd:
    i = cmd.index("--user")
    # Los argumentos del `--user` llegan hasta el siguiente que empieza por `--`.
    fin = next((j for j in range(i + 1, len(cmd)) if cmd[j].startswith("--")), len(cmd))
    acl = cmd[i + 1:fin]
    comprobar(acl[:1] == ["sitio"] and acl[1:] == REGLAS,
              "el usuario ACL `sitio` puede sólo GET, SET, DEL, PING y AUTH sobre `sitio:*`",
              f"regla ACL distinta de la esperada: {acl}")
    comprobar(cmd.count("--user") == 1,
              "hay un solo `--user` (otro usuario ACL ampliaría lo que se vigila aquí)",
              f"hay {cmd.count('--user')} `--user` en el comando de Redis")
else:
    mal("Redis no declara el usuario ACL `sitio` (`--user sitio on >… ~sitio:* -@all +get +set +del +ping +auth`)")
comprobar("--requirepass" in cmd and cmd[cmd.index("--requirepass") + 1] == "VALOR-REDIS-PRINCIPAL",
          "el usuario `default` sigue protegido con la contraseña principal",
          "Redis no exige la contraseña principal al usuario `default`")

# --- 3. Entorno del sitio -----------------------------------------------------------------
env = sitio.get("environment") or {}
ESPERADAS = {
    "NUXT_API_INTERNA", "NUXT_PUBLIC_API_URL", "NUXT_PUBLIC_DOMINIO",
    "NUXT_REDIS_HOST", "NUXT_REDIS_PORT", "NUXT_REDIS_USUARIO", "NUXT_REDIS_PASSWORD",
}
sobran, faltan = sorted(set(env) - ESPERADAS), sorted(ESPERADAS - set(env))
comprobar(not sobran and not faltan,
          "el entorno del sitio es EXACTAMENTE la lista esperada (ni un secreto de más)",
          f"entorno del sitio: sobran {sobran}, faltan {faltan}")
comprobar("env_file" not in compose_crudo["services"]["sitio"],
          "el sitio no carga el `.env` entero (sin `env_file`)",
          "el sitio declara `env_file`: recibiría todos los secretos")

valores = {k: str(v) for k, v in env.items()}
comprobar(valores.get("NUXT_REDIS_USUARIO") == "sitio",
          "el sitio entra como el usuario `sitio`",
          f"NUXT_REDIS_USUARIO = {valores.get('NUXT_REDIS_USUARIO')!r}")
comprobar(valores.get("NUXT_REDIS_PASSWORD") == "VALOR-REDIS-SITIO",
          "el sitio lleva la contraseña del usuario `sitio`",
          "NUXT_REDIS_PASSWORD no es REDIS_SITIO_PASSWORD")
comprobar(valores.get("NUXT_REDIS_HOST") == "redis" and valores.get("NUXT_REDIS_PORT") == "6379",
          "el sitio llega a Redis por `redis:6379`, dentro de `cache-sitio`",
          f"destino de Redis: {valores.get('NUXT_REDIS_HOST')}:{valores.get('NUXT_REDIS_PORT')}")
comprobar(valores.get("NUXT_PUBLIC_DOMINIO") == "dominio.prueba.local",
          "el dominio de las copias sale del dominio del despliegue (si no, no se guardaría nada)",
          f"NUXT_PUBLIC_DOMINIO = {valores.get('NUXT_PUBLIC_DOMINIO')!r}")

PROHIBIDOS = ["VALOR-APP-KEY", "VALOR-DB-DUENO", "VALOR-DB-APP", "VALOR-DB-LECTURA",
              "VALOR-REDIS-PRINCIPAL", "VALOR-FRASE-COPIAS"]
colados = sorted(k for k, v in valores.items() for p in PROHIBIDOS if p in v)
comprobar(not colados,
          "ningún otro secreto llega al entorno del sitio",
          f"secretos que llegan al sitio por: {colados}")

sys.exit(1 if fallos else 0)
PY
[ $? -eq 0 ] || fallos=$((fallos + 1))

# --- 4. La contraseña del sitio es un secreto del servidor, no un valor de la imagen --------
if grep -qE '^REDIS_SITIO_PASSWORD obligatorio$' deploy/secretos-requeridos.txt; then
  ok "REDIS_SITIO_PASSWORD es un secreto obligatorio (sin ella el despliegue se detiene antes de tocar nada)"
else
  mal "REDIS_SITIO_PASSWORD no figura como obligatoria en deploy/secretos-requeridos.txt"
fi
if grep -qE '^REDIS_SITIO_PASSWORD=\$\{REDIS_SITIO_PASSWORD\}$' deploy/plantilla.env; then
  ok "la plantilla entrega REDIS_SITIO_PASSWORD al .env (compose la lee de ahí)"
else
  mal "deploy/plantilla.env no declara REDIS_SITIO_PASSWORD=\${REDIS_SITIO_PASSWORD}"
fi
if grep -qE '^SEDE_DOMINIO=\$\{DOMINIO\}$' deploy/plantilla.env; then
  ok "la plantilla entrega SEDE_DOMINIO al .env (de ahí sale NUXT_PUBLIC_DOMINIO del sitio)"
else
  mal "deploy/plantilla.env no declara SEDE_DOMINIO=\${DOMINIO}: el sitio no sabría para qué dominio guardar copias"
fi
if grep -qE '^NUXT_REDIS_' deploy/plantilla.env deploy/valores-*.env; then
  mal "NUXT_REDIS_* no deben estar en la plantilla ni en los valores: las pone compose.yaml, y estar en el .env las llevaría a todos los servicios"
else
  ok "la plantilla no lleva NUXT_REDIS_* (las pone compose.yaml sólo al sitio)"
fi

if [ "$fallos" -gt 0 ]; then
  printf '\n\033[31m%d comprobación(es) fallaron.\033[0m\n' "$fallos" >&2
  exit 1
fi
printf '\n\033[32mEl acceso del sitio a Redis está acotado por red, credencial y entorno.\033[0m\n'

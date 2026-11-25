#!/bin/bash
#
# Prueba, contra un Redis REAL, que el usuario ACL `sitio` hace lo que el respaldo necesita y
# nada más (Fase 0C, T9.5).
#
#   bash deploy/pruebas/prueba-acl-redis-sitio.sh
#
# `guarda-cache-sitio.sh` comprueba que la regla ACL está escrita como debe; esta prueba
# comprueba que esa regla, tal como Redis la interpreta, funciona: que el sitio puede lo que usa
# (`GET`, `SET` con `PX`, `DEL`, `PING` sobre `sitio:*`), que NO puede lo demás (otras claves,
# `FLUSHALL`, `KEYS`, `CONFIG`, `EVAL`, `ACL`) y que las dos contraseñas no se intercambian.
# Una regla bien escrita que Redis interpreta distinto —o una versión nueva de Redis que cambia
# una sintaxis— no la detecta ninguna lectura del YAML.
#
# El comando y la imagen salen de la pila RESUELTA de `compose.yaml`: se prueba lo que se despliega,
# no una copia. Sin red (`--network none`): se entra con `docker exec`.
#
# `REDIS_IMAGEN` permite sustituir la imagen (por ejemplo, un espejo del mismo digest) cuando el
# registro por defecto no es alcanzable; el comando sigue siendo el de compose.yaml.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$RAIZ" || exit 1

fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

for c in docker python3; do
  command -v "$c" >/dev/null || { printf '\033[31mFalta %s.\033[0m\n' "$c" >&2; exit 1; }
done
docker info >/dev/null 2>&1 || { printf '\033[31mNo hay demonio de Docker al que hablarle.\033[0m\n' >&2; exit 1; }

PRINCIPAL="principal-$(head -c 12 /dev/urandom | od -An -tx1 | tr -d ' \n')"
SITIO="sitio-$(head -c 12 /dev/urandom | od -An -tx1 | tr -d ' \n')"

tmp="$(mktemp -d)"
cid=""
trap '[ -n "$cid" ] && docker rm -f "$cid" >/dev/null 2>&1; rm -rf "$tmp"' EXIT
cp compose.yaml "$tmp/compose.yaml"
cat > "$tmp/.env" <<ENV
APP_KEY=base64:x
DB_PASSWORD=x
DB_APP_PASSWORD=x
DB_LECTURA_PASSWORD=x
BACKUP_PASSPHRASE=x
REDIS_PASSWORD=$PRINCIPAL
REDIS_SITIO_PASSWORD=$SITIO
ENV

pila="$(cd "$tmp" && env -i PATH="$PATH" HOME="${HOME:-/tmp}" DOCKER_HOST="${DOCKER_HOST:-}" docker compose -f compose.yaml config --format json)" \
  || { printf '\033[31mLa pila no se resuelve.\033[0m\n' >&2; exit 1; }

# Los argumentos del comando, uno por línea (pueden llevar espacios o `>`), y la imagen.
PILA="$pila" python3 - "$tmp" <<'PY'
import json, os, sys
r = json.loads(os.environ["PILA"])["services"]["redis"]
open(os.path.join(sys.argv[1], "imagen"), "w").write(r["image"])
open(os.path.join(sys.argv[1], "comando"), "w").write("\n".join(str(x) for x in r["command"]) + "\n")
PY
imagen="${REDIS_IMAGEN:-$(cat "$tmp/imagen")}"
mapfile -t comando < "$tmp/comando"

cid="$(docker run -d --network none "$imagen" "${comando[@]}")" \
  || { printf '\033[31mNo se pudo arrancar Redis con el comando de compose.yaml.\033[0m\n' >&2; exit 1; }

# Espera a que atienda (con la contraseña principal, la única que `default` acepta).
listo=0
for _ in $(seq 1 40); do
  if docker exec -e REDISCLI_AUTH="$PRINCIPAL" "$cid" redis-cli ping 2>/dev/null | grep -q PONG; then listo=1; break; fi
  sleep 0.25
done
[ "$listo" = 1 ] || { docker logs "$cid" >&2; printf '\033[31mRedis no arrancó.\033[0m\n' >&2; exit 1; }

# `como_sitio` / `como_principal`: ejecutan UN comando y devuelven su salida (incluidos los errores).
como_sitio()     { docker exec "$cid" redis-cli --user sitio --pass "$SITIO" --no-auth-warning "$@" 2>&1; }
como_principal() { docker exec -e REDISCLI_AUTH="$PRINCIPAL" "$cid" redis-cli "$@" 2>&1; }

espera() {  # espera <descripción> <esperado (regex)> <salida>
  if printf '%s' "$3" | grep -qE "$2"; then ok "$1"; else mal "$1 — salida: $(printf '%s' "$3" | head -c 200)"; fi
}

echo "Lo que el sitio SÍ necesita"
espera "PING"                                         '^PONG$'  "$(como_sitio ping)"
espera "SET con PX sobre sitio:*"                      '^OK$'    "$(como_sitio set sitio:respaldo:/tramites '{"a":1}' PX 60000)"
espera "SET con EX y NX (la forma que más se usa)"     '^OK$'    "$(como_sitio set sitio:respaldo:/noticias x EX 60 NX)"
espera "GET devuelve lo guardado"                      '^\{"a":1\}$' "$(como_sitio get sitio:respaldo:/tramites)"
espera "DEL sobre sitio:*"                             '^1$'     "$(como_sitio del sitio:respaldo:/noticias)"

echo "Lo que el sitio NO puede"
espera "no lee claves de la aplicación"                 'NOPERM'  "$(como_sitio get laravel_database_sesion)"
espera "no escribe fuera de sitio:*"                    'NOPERM'  "$(como_sitio set sede_cache:x 1)"
espera "no borra fuera de sitio:*"                      'NOPERM'  "$(como_sitio del sede_cache:x)"
espera "no escribe una clave que sólo EMPIEZA parecido" 'NOPERM'  "$(como_sitio set sitiox:y 1)"
espera "no hace FLUSHALL"                               'NOPERM'  "$(como_sitio flushall)"
espera "no hace FLUSHDB"                                'NOPERM'  "$(como_sitio flushdb)"
espera "no lista claves (KEYS)"                         'NOPERM'  "$(como_sitio keys '*')"
espera "no recorre claves (SCAN)"                       'NOPERM'  "$(como_sitio scan 0)"
espera "no lee la configuración (CONFIG GET)"           'NOPERM'  "$(como_sitio config get requirepass)"
espera "no cambia la configuración (CONFIG SET)"        'NOPERM'  "$(como_sitio config set maxmemory 1mb)"
espera "no ejecuta scripts (EVAL)"                      'NOPERM'  "$(como_sitio eval 'return redis.call("get","x")' 0)"
espera "no ve ni cambia usuarios (ACL)"                 'NOPERM'  "$(como_sitio acl list)"
espera "no apaga ni vuelca (SHUTDOWN / DEBUG)"          'NOPERM'  "$(como_sitio shutdown nosave)"
espera "no escribe con EXPIRE sobre claves ajenas"      'NOPERM'  "$(como_sitio expire sede_cache:x 1)"

echo "Las contraseñas no se intercambian"
espera "el usuario sitio NO entra con la contraseña principal" 'WRONGPASS|invalid username-password' "$(docker exec "$cid" redis-cli --user sitio --pass "$PRINCIPAL" --no-auth-warning ping 2>&1)"
espera "el usuario default NO entra con la contraseña del sitio" 'WRONGPASS|invalid username-password|NOAUTH|Authentication required' \
  "$(docker exec "$cid" redis-cli --pass "$SITIO" --no-auth-warning ping 2>&1)"
espera "sin credenciales no se hace nada"               'NOAUTH|Authentication required' "$(docker exec "$cid" redis-cli get sitio:respaldo:/tramites 2>&1)"

echo "La contraseña principal sigue mandando"
espera "default (principal) sí puede ver las claves del sitio" 'sitio:respaldo:/tramites' "$(como_principal keys 'sitio:*')"
espera "default (principal) sí puede escribir fuera de sitio:*" '^OK$' "$(como_principal set sede_cache:x 1)"
espera "el usuario sitio no sobrescribió nada ajeno"     '^1$' "$(como_principal get sede_cache:x)"

echo "La regla, tal como Redis la guardó"
regla="$(como_principal acl getuser sitio | tr '\n' ' ')"
espera "el usuario existe, activo y limitado a sitio:*" 'on.*~sitio:\*' "$regla"
espera "no tiene permisos de más (sólo get, set, del, ping, auth)" '\+get.*\+set.*\+del.*\+ping.*\+auth|-@all' "$regla"
if printf '%s' "$regla" | grep -qE '\+@all|allkeys|&\*|\*~'; then mal "la regla guardada tiene comodines de más: $regla"; else ok "la regla guardada no tiene +@all ni claves *"; fi

if [ "$fallos" -gt 0 ]; then
  printf '\n\033[31m%d comprobación(es) fallaron.\033[0m\n' "$fallos" >&2
  exit 1
fi
printf '\n\033[32mEl usuario `sitio` hace lo que el respaldo necesita y nada más.\033[0m\n'

#!/bin/bash
#
# Verificación AC-12.5 del Módulo 01 (auditoría): un archivo subido por la API se
# sirve con `200`, el `Content-Type` correcto y `X-Content-Type-Options: nosniff`.
# SOLO para staging.
#
#   verificar-archivo-publico.sh
#
# De punta a punta, por el mismo camino que un usuario real (Cloudflare → nginx →
# app), sin tocar la base de datos más que para el alta y la baja del usuario:
#
#   1. Crea el usuario de prueba con `archivos.ver` y `archivos.crear`
#      (`usuario-prueba.sh crear archivos`).
#   2. Inicia sesión con cookie + CSRF, como lo hace el panel.
#   3. Sube un PNG de 1×1 a la entidad por `POST /api/v1/panel/entidad/{uuid}/archivos`.
#   4. Hace `curl -sI <url devuelta>` SIN cookies (lo que vería cualquiera con el enlace).
#   5. Comprueba tres cosas y dice APRUEBA o FALLA para cada una.
#   6. Borra el archivo (disco y registro) y el usuario de prueba, pase lo que pase.
#
# Se ejecuta en el servidor con una cuenta que pueda usar Docker (administración,
# NO la cuenta de despliegue). La contraseña del usuario de prueba no se escribe en
# ningún archivo, ni en la línea de comandos de ningún proceso (no sale en `ps`), ni
# en la salida de este script.
#
# Dependencias del servidor: bash, curl, python3 y docker.
#
set -euo pipefail

rojo()  { printf '\033[31m%s\033[0m\n' "$*" >&2; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
URL="${URL_STAGING:-https://staging.santamarta.gov.co}"
URL="${URL%/}"
BASE="/opt/sede/staging"
CORREO="prueba.auditoria@example.com"
COLECCION="prueba-auditoria"

# Modo de ensayo en local (las pruebas de este mismo script): no toca Docker ni el
# usuario; recibe el correo y la contraseña de un usuario que ya existe. En el
# servidor NO se usa.
SOLO_HTTP="${AC125_SOLO_HTTP:-}"

for dep in curl python3; do
  command -v "$dep" >/dev/null || { rojo "Falta «$dep» en este equipo."; exit 1; }
done

if [ -z "$SOLO_HTTP" ]; then
  command -v docker >/dev/null || { rojo "Falta «docker» en este equipo."; exit 1; }
  [ -f "$BASE/.env" ] && [ -f "$BASE/compose.yaml" ] \
    || { rojo "Faltan $BASE/.env o $BASE/compose.yaml. ¿Es el servidor de staging?"; exit 1; }
  COMPOSE="docker compose -f $BASE/compose.yaml --env-file $BASE/.env"
fi

umask 077
TMP="$(mktemp -d)"
JAR="$TMP/cookies.txt"
CLAVE=""
UUID_ARCHIVO=""

limpiar() {
  local codigo=$?
  set +e
  if [ -z "$SOLO_HTTP" ]; then
    if [ -n "$UUID_ARCHIVO" ]; then
      # Sin permiso `archivos.eliminar` el usuario de prueba no puede borrarlo por la
      # API (a propósito), así que se borra con el modelo: disco y registro.
      $COMPOSE run --rm -T -e "PRUEBA_UUID=$UUID_ARCHIVO" app php >/dev/null <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\FileMedia::where('uuid', getenv('PRUEBA_UUID'))->first()?->deleteFully();
PHP
      echo "Archivo de prueba borrado ($UUID_ARCHIVO)."
    fi
    "$DIR/usuario-prueba.sh" borrar || rojo "No se pudo borrar el usuario de prueba: ejecuta «usuario-prueba.sh borrar»."
  fi
  rm -rf "$TMP"
  exit "$codigo"
}
trap limpiar EXIT

if [ -z "$SOLO_HTTP" ]; then
  SALIDA="$("$DIR/usuario-prueba.sh" crear archivos)"
  CLAVE="$(printf '%s\n' "$SALIDA" | sed -n 's/^Contraseña (se muestra solo ahora): //p')"
  [ -n "$CLAVE" ] || { rojo "No se pudo leer la contraseña del usuario de prueba."; exit 1; }
else
  CORREO="${PRUEBA_CORREO:?con AC125_SOLO_HTTP hace falta PRUEBA_CORREO}"
  CLAVE="${PRUEBA_CLAVE:?con AC125_SOLO_HTTP hace falta PRUEBA_CLAVE}"
fi

# Valor de una cookie del tarro de cookies de curl (formato Netscape), decodificado.
cookie() {
  python3 - "$JAR" "$1" <<'PY'
import sys, urllib.parse
jar, nombre = sys.argv[1], sys.argv[2]
for linea in open(jar, encoding="utf-8"):
    linea = linea.removeprefix("#HttpOnly_").rstrip("\n")
    campos = linea.split("\t")
    if len(campos) == 7 and campos[5] == nombre:
        print(urllib.parse.unquote(campos[6]))
        break
PY
}

# Valor de un campo `data.<ruta>` de un JSON recibido por la entrada estándar.
campo() {
  python3 -c '
import json, sys
valor = json.load(sys.stdin)["data"]
for parte in sys.argv[1].split("."):
    valor = valor[parte]
print(valor)
' "$1"
}

CABECERAS=(-H "Accept: application/json" -H "Origin: $URL" -H "Referer: $URL/")

echo "== 1. Sesión (cookie + CSRF) en $URL"
CODIGO="$(curl -sS -o /dev/null -w '%{http_code}' -c "$JAR" -b "$JAR" "${CABECERAS[@]}" "$URL/sanctum/csrf-cookie")"
[ "$CODIGO" = "204" ] || { rojo "csrf-cookie respondió $CODIGO (se esperaba 204)."; exit 1; }
XSRF="$(cookie XSRF-TOKEN)"
[ -n "$XSRF" ] || { rojo "El servidor no entregó la cookie XSRF-TOKEN."; exit 1; }

# La contraseña viaja por la entrada estándar de curl, no por su línea de comandos.
CODIGO="$(printf '{"email":"%s","password":"%s"}' "$CORREO" "$CLAVE" \
  | curl -sS -o "$TMP/login.json" -w '%{http_code}' -c "$JAR" -b "$JAR" "${CABECERAS[@]}" \
      -H "Content-Type: application/json" -H "X-XSRF-TOKEN: $XSRF" \
      --data-binary @- "$URL/api/v1/panel/login")"
[ "$CODIGO" = "200" ] || { rojo "El inicio de sesión respondió $CODIGO (se esperaba 200)."; cat "$TMP/login.json" >&2; exit 1; }
XSRF="$(cookie XSRF-TOKEN)"   # la sesión se regenera al iniciar: el token cambia

echo "== 2. Entidad a la que se adjunta"
UUID_ENTIDAD="$(curl -sS "${CABECERAS[@]}" "$URL/api/v1/entidad" | campo uuid)"
[ -n "$UUID_ENTIDAD" ] || { rojo "No se pudo leer el uuid de la entidad."; exit 1; }

echo "== 3. Subida de un PNG de 1x1"
printf '%s' 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' \
  | base64 -d > "$TMP/prueba.png"
CODIGO="$(curl -sS -o "$TMP/subida.json" -w '%{http_code}' -b "$JAR" "${CABECERAS[@]}" \
  -H "X-XSRF-TOKEN: $XSRF" \
  -F "archivo=@$TMP/prueba.png;type=image/png" -F "collection=$COLECCION" \
  "$URL/api/v1/panel/entidad/$UUID_ENTIDAD/archivos")"
if [ "$CODIGO" != "200" ]; then
  rojo "La subida respondió $CODIGO (se esperaba 200)."; cat "$TMP/subida.json" >&2; exit 1
fi
UUID_ARCHIVO="$(campo uuid < "$TMP/subida.json")"
URL_ARCHIVO="$(campo url < "$TMP/subida.json")"
DISCO="$(campo disk < "$TMP/subida.json")"
echo "   uuid:  $UUID_ARCHIVO"
echo "   disco: $DISCO"
echo "   url:   ${URL_ARCHIVO%%\?*}  (con firma; se omite la parte firmada)"

echo "== 4. curl -sI de la URL devuelta, sin cookies"
curl -sSI --max-time 30 "$URL_ARCHIVO" > "$TMP/cabeceras.txt"
sed 's/\r$//' "$TMP/cabeceras.txt" | sed 's/^/   /'

ESTADO="$(sed -n '1s/^HTTP\/[0-9.]* \([0-9]*\).*/\1/p' "$TMP/cabeceras.txt")"
TIPO="$(sed -n 's/^[Cc]ontent-[Tt]ype:[[:space:]]*\([^;[:space:]]*\).*/\1/p' "$TMP/cabeceras.txt" | tr -d '\r' | head -1)"
NOSNIFF="$(sed -n 's/^[Xx]-[Cc]ontent-[Tt]ype-[Oo]ptions:[[:space:]]*//p' "$TMP/cabeceras.txt" | tr -d '\r' | head -1)"

echo "== 5. Resultado"
FALLOS=0
comprobar() { # etiqueta, obtenido, esperado
  if [ "$2" = "$3" ]; then verde "   APRUEBA  $1: $2"; else rojo "   FALLA    $1: obtenido «$2», esperado «$3»"; FALLOS=$((FALLOS + 1)); fi
}
comprobar "estado HTTP" "$ESTADO" "200"
comprobar "Content-Type" "$TIPO" "image/png"
comprobar "X-Content-Type-Options" "${NOSNIFF,,}" "nosniff"

if [ "$FALLOS" -eq 0 ]; then
  verde "AC-12.5: APRUEBA (3 de 3)."
else
  rojo "AC-12.5: FALLA ($FALLOS de 3)."
  exit 1
fi

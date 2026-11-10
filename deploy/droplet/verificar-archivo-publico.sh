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
#   6. AC-12.4: sube un archivo de 21 MiB (debe dar 422 con mensaje, no un 413 opaco)
#      y otro de 26 MiB (lo corta nginx con 413 en JSON).
#   7. Borra los archivos de prueba (disco y registro) y el usuario, pase lo que pase.
#
# Se ejecuta en el servidor con `sudo`, desde la cuenta de administración (NO la de
# despliegue), junto a `usuario-prueba.sh` en la misma carpeta:
#
#   ssh -t ops@<servidor> 'sudo bash ~/verificar-archivo-publico.sh'
#
# La contraseña del usuario de prueba no se escribe en
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
  if ! { [ -f "$BASE/.env" ] && [ -f "$BASE/compose.yaml" ]; }; then
    if [ "$(id -u)" -ne 0 ]; then
      rojo "Con esta cuenta no veo $BASE/.env ni compose.yaml (en el servidor sólo root entra a esa carpeta). Ejecuta con sudo."
    else
      rojo "Faltan $BASE/.env o $BASE/compose.yaml. ¿Es el servidor de staging?"
    fi
    exit 1
  fi
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
    # Se borra por colección (solo la usan estas pruebas) y no por uuid: si una subida
    # que debía rechazarse llegara a aceptarse, también se limpia. Sin permiso
    # `archivos.eliminar` el usuario de prueba no puede borrarlos por la API (a
    # propósito), así que se borran con el modelo: disco y registro.
    $COMPOSE run --rm -T -e "PRUEBA_COLECCION=$COLECCION" app php >"$TMP/limpieza.out" 2>&1 <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $n = 0;
    foreach (App\Models\FileMedia::where('collection', getenv('PRUEBA_COLECCION'))->get() as $f) {
        $f->deleteFully();
        $n++;
    }
    echo $n, "\n";
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage()."\n");
    exit(1);
}
PHP
    estado=$?
    if [ "$estado" -eq 0 ]; then
      echo "Archivos de prueba borrados: $(tail -n 1 "$TMP/limpieza.out")."
    else
      rojo "No se pudieron borrar los archivos de la colección «$COLECCION»: bórralos a mano."
      cat "$TMP/limpieza.out" >&2
    fi
    "$DIR/usuario-prueba.sh" borrar || rojo "No se pudo borrar el usuario de prueba: ejecuta «usuario-prueba.sh borrar»."
  fi
  rm -rf "$TMP"
  exit "$codigo"
}
trap limpiar EXIT

if [ -z "$SOLO_HTTP" ]; then
  # La salida se guarda en archivos, pero si el alta falla se muestra la causa: antes
  # un fallo aquí terminaba el guion sin decir nada.
  if ! SALIDA="$("$DIR/usuario-prueba.sh" crear archivos 2>"$TMP/crear.err")"; then
    rojo "No se pudo crear el usuario de prueba:"
    cat "$TMP/crear.err" >&2
    printf '%s\n' "$SALIDA" | sed 's/^Contraseña.*/(contraseña omitida)/' >&2
    exit 1
  fi
  CLAVE="$(printf '%s\n' "$SALIDA" | sed -n 's/^.*Contraseña (se muestra solo ahora): \([A-Za-z0-9]*\).*$/\1/p')"
  [ "${#CLAVE}" -eq 24 ] || { rojo "No se pudo leer la contraseña del usuario de prueba."; exit 1; }
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
if valor is None:   # un null no es un valor: sin esto se enviaba la cadena «None»
    sys.exit(3)
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
if ! UUID_ENTIDAD="$(curl -sS "${CABECERAS[@]}" "$URL/api/v1/entidad" | campo uuid)" || [ -z "$UUID_ENTIDAD" ]; then
  rojo "GET /api/v1/entidad no entregó un uuid (¿la fila de la entidad tiene uuid nulo?)."
  exit 1
fi

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
  FALLOS_125=0
else
  rojo "AC-12.5: FALLA ($FALLOS de 3)."
  FALLOS_125=$FALLOS
fi

echo "== 6. Límites de tamaño (AC-12.4)"
FALLOS=0
sobredimensionado() { # MiB, destino: un PNG válido seguido de ceros
  { cat "$TMP/prueba.png"; head -c "$(( $1 * 1024 * 1024 ))" /dev/zero; } > "$2"
}
subir_grande() { # archivo, nombre de la respuesta
  curl -sS --max-time 180 -o "$TMP/$2.json" -w '%{http_code}' -b "$JAR" "${CABECERAS[@]}" \
    -H "X-XSRF-TOKEN: $XSRF" -F "archivo=@$1;type=image/png" -F "collection=$COLECCION" \
    "$URL/api/v1/panel/entidad/$UUID_ENTIDAD/archivos" || echo "000"
}

sobredimensionado 21 "$TMP/21m.png"
CODIGO="$(subir_grande "$TMP/21m.png" r21)"
comprobar "21 MiB: estado HTTP" "$CODIGO" "422"
comprobar "21 MiB: el mensaje nombra el límite de 20 MB" "$(grep -c '20 MB' "$TMP/r21.json" || true)" "1"
rm -f "$TMP/21m.png"

if [ -n "$SOLO_HTTP" ]; then
  echo "   (26 MiB omitido en el ensayo local: no hay nginx delante que lo corte con 413)"
else
  sobredimensionado 26 "$TMP/26m.png"
  CODIGO="$(subir_grande "$TMP/26m.png" r26)"
  comprobar "26 MiB: estado HTTP" "$CODIGO" "413"
  comprobar "26 MiB: respuesta JSON de la API" "$(grep -c '"success":false' "$TMP/r26.json" || true)" "1"
  rm -f "$TMP/26m.png"
fi

if [ "$FALLOS" -eq 0 ]; then
  verde "AC-12.4: APRUEBA."
else
  rojo "AC-12.4: FALLA ($FALLOS comprobaciones)."
fi
[ "$FALLOS_125" -eq 0 ] && [ "$FALLOS" -eq 0 ] || exit 1

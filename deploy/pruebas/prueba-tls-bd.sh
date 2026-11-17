#!/bin/bash
#
# Prueba del TLS a la base de datos (Fase 0A, T5). Se ejecuta con Docker disponible:
#
#   bash deploy/pruebas/prueba-tls-bd.sh
#
# Qué demuestra, contra el servicio `postgres` REAL de `compose.yaml` (misma imagen, mismas
# capacidades, mismo punto de entrada que viaja en `configs.entrypoint_tls`), y no contra una
# réplica que pudiera divergir:
#
#   A. Con `POSTGRES_SSL=on` y certificado, el servidor tiene `ssl=on`.
#   B. Una conexión de red SIN cifrar (`sslmode=disable`) es RECHAZADA («no encryption»).
#   C. Una conexión con `sslmode=require` entra y `pg_stat_ssl` la muestra cifrada (TLS >= 1.2),
#      también con un rol sin privilegios (el caso de `sede_app`).
#   D. Con `POSTGRES_SSL=on` y SIN certificado el contenedor NO arranca y lo dice.
#   E. Con `POSTGRES_SSL=off` (staging, riesgo aceptado) el servidor responde `ssl=off`.
#   F. Producción exige TLS en sus valores (`POSTGRES_SSL=on`, `DB_SSLMODE=require`).
#   G. `certificado-bd.sh` emite el certificado con los permisos correctos, es idempotente,
#      renueva cuando va a caducar y reemplaza uno cuya llave no le corresponde.
#   H. La unidad de systemd que renueva el certificado es válida (`systemd-analyze verify`).
#
# Para probar que la prueba muerde: `COMPOSE_ARCHIVO=<copia mutilada de compose.yaml>`.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="${COMPOSE_ARCHIVO:-$RAIZ/compose.yaml}"
SCRIPT_CERT="$RAIZ/deploy/droplet/certificado-bd.sh"
PROYECTO="sedetls$$"
TMP="$(mktemp -d)"
FALLOS=0

verde() { printf '  \033[32m✓\033[0m %s\n' "$*"; }
rojo()  { printf '  \033[31m✗ %s\033[0m\n' "$*" >&2; FALLOS=$((FALLOS + 1)); }
paso()  { printf '\n\033[1m%s\033[0m\n' "$*"; }
raiz()  { if [ "$(id -u)" -eq 0 ]; then "$@"; else sudo -n "$@"; fi; }

# Valores de mentira, como los del CI: sólo hacen falta para que `compose` resuelva el archivo.
export APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="
export DB_PASSWORD="prueba-tls-clave"
export DB_APP_PASSWORD="prueba" DB_LECTURA_PASSWORD="prueba" REDIS_PASSWORD="prueba"
export BACKUP_PASSPHRASE="prueba" S3_ACCESS_KEY="prueba" S3_SECRET_KEY="prueba"

dc() { docker compose -f "$COMPOSE" --project-directory "$RAIZ" -p "$PROYECTO" "$@"; }

limpiar() {
  dc down -v --remove-orphans >/dev/null 2>&1 || true
  raiz rm -rf "$TMP" 2>/dev/null || rm -rf "$TMP" 2>/dev/null || true
}
trap limpiar EXIT

command -v docker >/dev/null 2>&1 || { echo "Hace falta Docker." >&2; exit 1; }
[ -f "$SCRIPT_CERT" ] || { rojo "Falta $SCRIPT_CERT"; exit 1; }

# psql DENTRO del contenedor, por TCP (127.0.0.1 pasa por las reglas hostssl/hostnossl; el socket
# local no). $1 = sslmode, $2 = usuario, $3 = clave, $4 = SQL.
psql_tcp() {
  dc exec -T -e PGSSLMODE="$1" -e PGPASSWORD="$3" postgres \
    psql -h 127.0.0.1 -U "$2" -d sede -v ON_ERROR_STOP=1 -Atc "$4" 2>&1
}

levantar() { # $1 = POSTGRES_SSL, $2 = directorio de certificados
  POSTGRES_SSL="$1" SEDE_DB_CERTS_DIR="$2" dc up -d --wait --wait-timeout 90 postgres >/dev/null 2>&1
}

# --- G (primero: produce los certificados que usan las demás) -----------------
paso "G. certificado-bd.sh"
export SEDE_BASE="$TMP/opt" SEDE_UNIT_DIR="$TMP/units" SEDE_BIN_DIR="$TMP/bin" SEDE_SIN_SYSTEMD=1
mkdir -p "$SEDE_BASE" "$SEDE_UNIT_DIR" "$SEDE_BIN_DIR"
DIR_CERTS="$SEDE_BASE/produccion/db-certs"

if raiz bash "$SCRIPT_CERT" produccion >"$TMP/g1.txt" 2>&1; then verde "emite el certificado"; else rojo "no pudo emitir: $(cat "$TMP/g1.txt")"; fi
if raiz test -s "$DIR_CERTS/server.crt" && raiz test -s "$DIR_CERTS/server.key"; then verde "crea server.crt y server.key"; else rojo "faltan server.crt/server.key"; fi
perm_llave="$(raiz stat -c '%a %U' "$DIR_CERTS/server.key" 2>/dev/null)"
[ "$perm_llave" = "600 root" ] && verde "la llave queda 0600 y de root" || rojo "la llave es '$perm_llave' (esperado '600 root')"
perm_dir="$(raiz stat -c '%a' "$DIR_CERTS" 2>/dev/null)"
[ "$perm_dir" = "750" ] && verde "el directorio queda 0750" || rojo "el directorio es '$perm_dir' (esperado 750)"
raiz openssl x509 -in "$DIR_CERTS/server.crt" -noout -ext subjectAltName 2>/dev/null | grep -q "DNS:postgres" \
  && verde "el certificado nombra a «postgres» (el anfitrión de la pila)" || rojo "el certificado no incluye DNS:postgres"
raiz openssl x509 -in "$DIR_CERTS/server.crt" -noout -checkend $((700 * 86400)) >/dev/null 2>&1 \
  && verde "vigencia larga (> 700 días)" || rojo "vigencia demasiado corta"

huella1="$(raiz sha256sum "$DIR_CERTS/server.crt" | cut -c1-16)"
raiz bash "$SCRIPT_CERT" produccion >"$TMP/g2.txt" 2>&1
huella2="$(raiz sha256sum "$DIR_CERTS/server.crt" | cut -c1-16)"
if [ "$huella1" = "$huella2" ] && grep -q "VIGENTE" "$TMP/g2.txt"; then verde "idempotente: la segunda corrida no cambia nada"; else rojo "la segunda corrida cambió el certificado o no dijo VIGENTE"; fi

# Renovación: con el umbral por encima de la vigencia restante, debe emitir otro.
SEDE_DB_CERT_UMBRAL_DIAS=900 raiz bash "$SCRIPT_CERT" produccion >"$TMP/g3.txt" 2>&1
huella3="$(raiz sha256sum "$DIR_CERTS/server.crt" | cut -c1-16)"
if [ "$huella3" != "$huella1" ] && grep -q "EMITIDO" "$TMP/g3.txt"; then verde "renueva cuando le quedan menos días que el umbral"; else rojo "no renovó con el umbral alto"; fi

# Llave que no corresponde al certificado: se reemplaza el par completo.
raiz sh -c "openssl genpkey -algorithm EC -pkeyopt ec_paramgen_curve:prime256v1 -out '$DIR_CERTS/server.key' 2>/dev/null && chmod 600 '$DIR_CERTS/server.key'"
raiz bash "$SCRIPT_CERT" produccion >"$TMP/g4.txt" 2>&1
pk_crt="$(raiz openssl x509 -in "$DIR_CERTS/server.crt" -noout -pubkey 2>/dev/null | sha256sum)"
pk_key="$(raiz openssl pkey -in "$DIR_CERTS/server.key" -pubout 2>/dev/null | sha256sum)"
[ "$pk_crt" = "$pk_key" ] && verde "una llave que no corresponde se detecta y se reemplaza el par" || rojo "el par quedó incoherente"

if raiz bash "$SCRIPT_CERT" desarrollo >/dev/null 2>&1; then rojo "aceptó un entorno que no existe"; else verde "rechaza un entorno desconocido"; fi

# --- H ------------------------------------------------------------------------
paso "H. Temporizador de renovación"
if raiz bash "$SCRIPT_CERT" produccion --programar >"$TMP/h.txt" 2>&1; then verde "--programar escribe las unidades"; else rojo "--programar falló: $(cat "$TMP/h.txt")"; fi
if [ -f "$SEDE_UNIT_DIR/sede-certificado-bd-produccion.timer" ] && [ -f "$SEDE_UNIT_DIR/sede-certificado-bd-produccion.service" ]; then
  if command -v systemd-analyze >/dev/null 2>&1; then
    if systemd-analyze verify "$SEDE_UNIT_DIR/sede-certificado-bd-produccion.service" "$SEDE_UNIT_DIR/sede-certificado-bd-produccion.timer" >"$TMP/v.txt" 2>&1; then
      verde "systemd-analyze verify: sin errores"
    else
      rojo "systemd-analyze verify: $(cat "$TMP/v.txt")"
    fi
  else
    echo "  (systemd-analyze no está: la validez de las unidades no se comprobó)"
  fi
else
  rojo "faltan las unidades .service/.timer"
fi

# --- F ------------------------------------------------------------------------
paso "F. Valores de los entornos"
grep -q '^POSTGRES_SSL=on$' "$RAIZ/deploy/valores-produccion.env" && verde "producción: POSTGRES_SSL=on" || rojo "producción debe tener POSTGRES_SSL=on"
grep -q '^DB_SSLMODE=require$' "$RAIZ/deploy/valores-produccion.env" && verde "producción: DB_SSLMODE=require" || rojo "producción debe tener DB_SSLMODE=require"
grep -q '^POSTGRES_SSL=off$' "$RAIZ/deploy/valores-staging.env" && echo "  (staging sin TLS: riesgo aceptado, ADR-0004)" || rojo "staging cambió de postura sin ADR"

# --- A, B, C ------------------------------------------------------------------
paso "A–C. Servidor con TLS (POSTGRES_SSL=on, certificado presente)"
if levantar on "$DIR_CERTS"; then
  verde "el contenedor arranca sano con certificado"
  estado="$(psql_tcp require sede "$DB_PASSWORD" 'show ssl')"
  [ "$estado" = "on" ] && verde "A. show ssl = on" || rojo "A. show ssl = '$estado'"

  salida="$(psql_tcp disable sede "$DB_PASSWORD" 'select 1')"; codigo=$?
  if [ "$codigo" -ne 0 ] && grep -qi "no encryption" <<<"$salida"; then
    verde "B. sslmode=disable rechazado («no encryption»)"
  else
    rojo "B. una conexión SIN cifrar no fue rechazada (código $codigo): $salida"
  fi

  salida="$(psql_tcp require sede "$DB_PASSWORD" "select ssl::text||' '||version from pg_stat_ssl where pid = pg_backend_pid()")"
  if grep -Eq '^true TLSv1\.[23]$' <<<"$salida"; then verde "C. propietario: conexión cifrada ($salida)"; else rojo "C. propietario sin cifrar o sin TLS >= 1.2: '$salida'"; fi

  # Un rol sin privilegios, como `sede_app`: la regla de hostssl vale para todos.
  psql_tcp require sede "$DB_PASSWORD" "create role prueba_tls login password 'clave-tls-prueba' nosuperuser nocreatedb nocreaterole" >/dev/null
  salida="$(psql_tcp require prueba_tls clave-tls-prueba "select ssl::text||' '||version from pg_stat_ssl where pid = pg_backend_pid()")"
  if grep -Eq '^true TLSv1\.[23]$' <<<"$salida"; then verde "C. rol sin privilegios: conexión cifrada ($salida)"; else rojo "C. rol sin privilegios sin cifrar: '$salida'"; fi
  salida="$(psql_tcp disable prueba_tls clave-tls-prueba 'select 1')"; codigo=$?
  if [ "$codigo" -ne 0 ] && grep -qi "no encryption" <<<"$salida"; then verde "B. el rol sin privilegios tampoco entra sin cifrar"; else rojo "B. el rol sin privilegios entró sin cifrar: $salida"; fi
else
  rojo "el contenedor no arrancó con certificado: $(dc logs --tail 20 postgres 2>&1 | tail -8)"
fi
dc down -v --remove-orphans >/dev/null 2>&1

# --- D ------------------------------------------------------------------------
paso "D. POSTGRES_SSL=on SIN certificado"
vacio="$TMP/sin-certs"; mkdir -p "$vacio"
if levantar on "$vacio"; then
  rojo "D. el contenedor arrancó SIN certificado: una base que se cree cifrada y no lo esté"
else
  registro="$(dc logs postgres 2>&1)"
  if grep -q "faltan /tls/server.crt" <<<"$registro"; then verde "D. no arranca y dice por qué"; else rojo "D. no arrancó pero sin el mensaje esperado: $(tail -3 <<<"$registro")"; fi
fi
dc down -v --remove-orphans >/dev/null 2>&1

# --- E ------------------------------------------------------------------------
paso "E. POSTGRES_SSL=off (staging)"
if levantar off "$vacio"; then
  estado="$(psql_tcp disable sede "$DB_PASSWORD" 'show ssl')"
  [ "$estado" = "off" ] && verde "E. show ssl = off y la conexión sin cifrar entra, como se decidió para staging" || rojo "E. show ssl = '$estado'"
else
  rojo "E. el contenedor no arrancó con POSTGRES_SSL=off: $(dc logs --tail 8 postgres 2>&1)"
fi

echo
if [ "$FALLOS" -ne 0 ]; then
  printf '\033[31m%s comprobación(es) fallaron.\033[0m\n' "$FALLOS" >&2
  exit 1
fi
printf '\033[32mEl TLS a la base se exige donde se decidió, y se renueva.\033[0m\n'

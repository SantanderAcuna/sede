#!/bin/bash
#
# Emite y renueva el certificado TLS de la BASE DE DATOS (Fase 0A, T5).
#
#   sudo bash certificado-bd.sh produccion              emite si falta, no sirve o va a caducar
#   sudo bash certificado-bd.sh produccion --programar  además deja el temporizador que lo renueva
#   sudo bash certificado-bd.sh produccion --forzar     emite uno nuevo aunque el actual sirva
#   sudo bash certificado-bd.sh produccion --reiniciar  si emitió, reinicia PostgreSQL (lo usa el temporizador)
#
# Es OTRO certificado que el de `certificado.sh`: aquel lo emite Let's Encrypt para nginx y se
# valida por DNS contra Cloudflare. Éste cifra el tramo aplicación → PostgreSQL, que va por la red
# interna de Docker y NO pasa por Cloudflare, así que no depende de él ni de ninguna autoridad
# externa: es autofirmado, con `DB_SSLMODE=require` (cifra, no verifica al servidor). Pasar a
# `verify-full` exige una autoridad certificadora propia (ver docs/adr/0004).
#
# Dónde queda: `/opt/sede/<entorno>/db-certs/{server.crt,server.key}`, el directorio que
# `compose.yaml` monta en `/tls` (variable `SEDE_DB_CERTS_DIR`, de `deploy/plantilla.env`). El
# punto de entrada de PostgreSQL copia el par a un sitio propio con el dueño que el servidor
# exige; aquí la llave queda de root, 0600, y el directorio 0750.
#
# Cuándo correrlo: ANTES del primer despliegue de un entorno con `POSTGRES_SSL=on`. Sin el par el
# contenedor de la base no arranca (a propósito: una base que se cree cifrada y no lo esté es
# peor que una que no arranca).
#
# Idempotente: si el certificado sirve (existe, su llave le corresponde y no caduca antes de
# SEDE_DB_CERT_UMBRAL_DIAS días) no toca nada.
#
# Variables (sólo para pruebas): SEDE_BASE, SEDE_DB_CERT_DIAS, SEDE_DB_CERT_UMBRAL_DIAS,
# SEDE_UNIT_DIR, SEDE_BIN_DIR, SEDE_SIN_SYSTEMD.
set -euo pipefail

rojo()  { printf '\033[31m%s\033[0m\n' "$*" >&2; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

ENTORNO="${1:-}"
case "$ENTORNO" in
  staging|produccion) shift ;;
  *) rojo "Uso: certificado-bd.sh <staging|produccion> [--forzar] [--programar] [--reiniciar]"; exit 2 ;;
esac

FORZAR=0; PROGRAMAR=0; REINICIAR=0
for a in "$@"; do
  case "$a" in
    --forzar) FORZAR=1 ;;
    --programar) PROGRAMAR=1 ;;
    --reiniciar) REINICIAR=1 ;;
    *) rojo "Opción desconocida: $a"; exit 2 ;;
  esac
done

[ "$(id -u)" -eq 0 ] || { rojo "Se ejecuta como root (la llave queda de root, 0600)."; exit 1; }
command -v openssl >/dev/null 2>&1 || { rojo "Falta openssl."; exit 1; }

BASE="${SEDE_BASE:-/opt/sede}"
DIAS="${SEDE_DB_CERT_DIAS:-825}"
UMBRAL="${SEDE_DB_CERT_UMBRAL_DIAS:-60}"
UNIT_DIR="${SEDE_UNIT_DIR:-/etc/systemd/system}"
BIN_DIR="${SEDE_BIN_DIR:-/usr/local/bin}"
DIR="$BASE/$ENTORNO/db-certs"
CRT="$DIR/server.crt"
KEY="$DIR/server.key"

install -d -m 750 -o root -g root "$BASE/$ENTORNO" "$DIR" 2>/dev/null || install -d -m 750 "$DIR"
# `install -d` sólo fija el modo de los directorios que crea: se asegura el del directorio de certificados.
chmod 750 "$DIR"

# Un par sirve si existen los dos, la llave corresponde al certificado y no caduca pronto.
par_sirve() {
  [ -s "$CRT" ] && [ -s "$KEY" ] || return 1
  openssl x509 -in "$CRT" -noout -checkend $((UMBRAL * 86400)) >/dev/null 2>&1 || return 1
  local de_crt de_key
  de_crt="$(openssl x509 -in "$CRT" -noout -pubkey 2>/dev/null | sha256sum)"
  de_key="$(openssl pkey -in "$KEY" -pubout 2>/dev/null | sha256sum)"
  [ -n "$de_crt" ] && [ "$de_crt" = "$de_key" ]
}

EMITIDO=0
if [ "$FORZAR" -eq 0 ] && par_sirve; then
  verde "VIGENTE: $(openssl x509 -in "$CRT" -noout -enddate | sed 's/notAfter=/hasta /')"
else
  # Se genera en un directorio temporal DEL MISMO sistema de archivos y se mueve: el contenedor
  # nunca ve un certificado a medias ni una llave sin su certificado.
  TMP="$(mktemp -d "$DIR/.nuevo.XXXXXX")"
  trap 'rm -rf "$TMP"' EXIT
  openssl req -x509 -newkey ec -pkeyopt ec_paramgen_curve:prime256v1 -nodes -days "$DIAS" \
    -keyout "$TMP/server.key" -out "$TMP/server.crt" \
    -subj "/CN=postgres" \
    -addext "subjectAltName=DNS:postgres,DNS:localhost,IP:127.0.0.1" \
    -addext "keyUsage=critical,digitalSignature,keyEncipherment" \
    -addext "extendedKeyUsage=serverAuth" 2>/dev/null
  chmod 600 "$TMP/server.key"
  chmod 644 "$TMP/server.crt"
  chown root:root "$TMP/server.key" "$TMP/server.crt"
  # La llave primero: si algo se corta entre los dos movimientos queda un par incoherente que
  # `par_sirve` detecta en la corrida siguiente (y el contenedor, al arrancar, lo rechaza).
  mv -f "$TMP/server.key" "$KEY"
  mv -f "$TMP/server.crt" "$CRT"
  EMITIDO=1
  verde "EMITIDO: $(openssl x509 -in "$CRT" -noout -enddate | sed 's/notAfter=/hasta /')"
fi

if [ "$EMITIDO" -eq 1 ] && [ "$REINICIAR" -eq 1 ]; then
  COMPOSE="$BASE/$ENTORNO/compose.yaml"
  if [ -f "$COMPOSE" ] && docker compose -f "$COMPOSE" --env-file "$BASE/$ENTORNO/.env" ps --status running --services 2>/dev/null | grep -qx postgres; then
    # PostgreSQL copia el par al arrancar: sin reinicio seguiría sirviendo el certificado viejo.
    docker compose -f "$COMPOSE" --env-file "$BASE/$ENTORNO/.env" restart postgres
    verde "PostgreSQL reiniciado con el certificado nuevo."
  else
    echo "PostgreSQL no está corriendo en $ENTORNO: tomará el certificado en su próximo arranque."
  fi
fi

if [ "$PROGRAMAR" -eq 1 ]; then
  install -d -m 755 "$BIN_DIR" "$UNIT_DIR"
  # El temporizador llama a una copia FIJA del guion, no a la del directorio desde donde se corrió.
  if [ "$(readlink -f "$0")" != "$(readlink -f "$BIN_DIR/certificado-bd.sh")" ]; then
    install -m 755 -o root -g root "$0" "$BIN_DIR/certificado-bd.sh"
  fi
  cat > "$UNIT_DIR/sede-certificado-bd-$ENTORNO.service" <<UNIDAD
[Unit]
Description=Renueva el certificado TLS de la base de datos de la sede ($ENTORNO)

[Service]
Type=oneshot
ExecStart=$BIN_DIR/certificado-bd.sh $ENTORNO --reiniciar
UNIDAD
  cat > "$UNIT_DIR/sede-certificado-bd-$ENTORNO.timer" <<UNIDAD
[Unit]
Description=Revisa cada semana si el certificado de la base ($ENTORNO) está por caducar

[Timer]
OnCalendar=weekly
RandomizedDelaySec=1h
Persistent=true

[Install]
WantedBy=timers.target
UNIDAD
  chmod 644 "$UNIT_DIR/sede-certificado-bd-$ENTORNO".{service,timer}
  if [ -z "${SEDE_SIN_SYSTEMD:-}" ]; then
    systemctl daemon-reload
    systemctl enable --now "sede-certificado-bd-$ENTORNO.timer" >/dev/null
    verde "Temporizador activo: $(systemctl list-timers "sede-certificado-bd-$ENTORNO.timer" --no-pager 2>/dev/null | sed -n 2p | xargs)"
  else
    echo "Unidades escritas en $UNIT_DIR (sin activar: SEDE_SIN_SYSTEMD)."
  fi
fi

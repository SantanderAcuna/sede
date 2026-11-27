#!/usr/bin/env bash
#
# `make local` con un `.env` que YA existe (el de una máquina creada antes de que el
# entorno exigiera `REDIS_SITIO_PASSWORD`, T9.5). Corre el guion real (`preparar`, sin
# Docker) sobre una copia temporal y comprueba que:
#   - agrega la variable que falta, aleatoria y sin tocar el resto del archivo;
#   - completa la variable cuando existe pero vacía (Compose también la rechaza vacía);
#   - no cambia un `.env` que ya está completo;
#   - no pisa los permisos del archivo;
#   - una segunda ejecución no cambia nada.
set -uo pipefail
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
fallos=0
ok()  { echo "  ok:   $1"; }
mal() { echo "  FALLA: $1"; fallos=$((fallos+1)); }

nuevo_entorno() {  # $1 = carpeta; guion real y un .env viejo (sin la variable)
  mkdir -p "$1/scripts"; cp "$RAIZ/scripts/local.sh" "$1/scripts/local.sh"
  ( umask 077; printf 'APP_NAME=sede\nREDIS_PASSWORD=%s\nBACKUP_PASSPHRASE=%s' viejaA viejaB > "$1/.env" )
}
preparar() { ( cd "$1" && bash scripts/local.sh preparar >/dev/null 2>&1 ); }
valor()    { grep -m1 '^REDIS_SITIO_PASSWORD=' "$1/.env" | cut -d= -f2-; }

echo "1. .env viejo sin la variable"
A="$T/a"; nuevo_entorno "$A"; preparar "$A"
v="$(valor "$A")"
[ "${#v}" -ge 32 ] && ok "se agregó con ${#v} caracteres" || mal "no se agregó (valor='$v')"
[ "$(grep -c '^REDIS_SITIO_PASSWORD=' "$A/.env")" = 1 ] && ok "una sola línea" || mal "línea repetida o ausente"
grep -qx 'REDIS_PASSWORD=viejaA' "$A/.env" && grep -qx 'BACKUP_PASSPHRASE=viejaB' "$A/.env" \
  && ok "el resto del archivo sigue igual (también la última línea sin salto final)" || mal "se alteró el resto del archivo"
[ "$(stat -c %a "$A/.env")" = 600 ] && ok "permisos 600 intactos" || mal "permisos: $(stat -c %a "$A/.env")"

echo "2. segunda ejecución"
antes="$(sha256sum "$A/.env")"; preparar "$A"
[ "$antes" = "$(sha256sum "$A/.env")" ] && ok "sin cambios" || mal "la segunda ejecución cambió el archivo"

echo "3. variable presente pero vacía"
B="$T/b"; nuevo_entorno "$B"; printf '\nREDIS_SITIO_PASSWORD=\n' >> "$B/.env"; preparar "$B"
v="$(valor "$B")"
[ "${#v}" -ge 32 ] && ok "se completó" || mal "sigue vacía (valor='$v')"
[ "$(grep -c '^REDIS_SITIO_PASSWORD=' "$B/.env")" = 1 ] && ok "una sola línea" || mal "línea repetida"

echo "4. .env completo no se toca"
C="$T/c"; nuevo_entorno "$C"; printf '\nREDIS_SITIO_PASSWORD=conservar\n' >> "$C/.env"
antes="$(sha256sum "$C/.env")"; preparar "$C"
[ "$antes" = "$(sha256sum "$C/.env")" ] && ok "intacto" || mal "se modificó un .env completo"

echo "5. .env nuevo (sin archivo previo)"
D="$T/d"; mkdir -p "$D/scripts"; cp "$RAIZ/scripts/local.sh" "$D/scripts/"; preparar "$D"
v="$(valor "$D")"; [ "${#v}" -ge 32 ] && ok "se crea con la variable" || mal "falta en el .env nuevo"

echo
[ "$fallos" = 0 ] && { echo "TODO EN ORDEN"; exit 0; } || { echo "$fallos FALLA(S)"; exit 1; }

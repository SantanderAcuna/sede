#!/bin/bash
#
# Guarda del guion instalado en el servidor (deriva repositorio ↔ servidor).
#
#   bash deploy/pruebas/guarda-guion-instalado.sh
#
# La canalización ejecuta `/usr/local/bin/desplegar.sh`, una COPIA de
# `deploy/droplet/despliegue-remoto.sh` instalada a mano. Si el guion del repositorio
# cambia y nadie reinstala la copia, el despliegue corre el viejo y termina en verde sin
# hacer lo nuevo. Pasó con el cálculo de huellas de la Fase 0B (T8): migró, sembró y
# levantó, pero no calculó huellas, y nada lo dijo.
#
# Remedio: la huella (SHA-256) del guion viaja en la imagen junto al resto del entorno
# y el propio guion del servidor la compara con la suya antes de tocar nada. Esta guarda
# comprueba, sin desplegar, que las tres piezas encajan:
#   1. `deploy/despliegue-remoto.sha256` es la huella del guion actual (si cambias el
#      guion y no la regeneras, falla aquí y no en el servidor).
#   2. El Dockerfile la copia a la imagen.
#   3. El guion define y usa `guion_al_dia`, y esa función distingue los tres casos:
#      igual, distinto (rechaza) y ausente (imagen anterior a la comprobación: avisa).
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
GUION="$RAIZ/deploy/droplet/despliegue-remoto.sh"
HUELLA="$RAIZ/deploy/despliegue-remoto.sha256"
DOCKERFILE="$RAIZ/docker/app/Dockerfile"

fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

[ -f "$GUION" ] || { printf '\033[31mFalta %s\033[0m\n' "$GUION" >&2; exit 1; }
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT

echo "guion instalado en el servidor"

# --- 1. La huella del repositorio es la del guion actual -----------------------
if [ -f "$HUELLA" ]; then
  esperada="$(sha256sum "$GUION" | cut -d' ' -f1)"
  [ "$(tr -d '[:space:]' < "$HUELLA")" = "$esperada" ] \
    && ok "deploy/despliegue-remoto.sha256 es la huella del guion actual" \
    || mal "deploy/despliegue-remoto.sha256 NO coincide con el guion. Regenera: sha256sum deploy/droplet/despliegue-remoto.sh | cut -d' ' -f1 > deploy/despliegue-remoto.sha256"
else
  mal "falta deploy/despliegue-remoto.sha256"
fi

# --- 2. El Dockerfile la lleva a la imagen --------------------------------------
grep -Eq 'COPY[^#]*deploy/despliegue-remoto\.sha256[^#]*/opt/sede-entorno/' "$DOCKERFILE" \
  && ok "el Dockerfile copia la huella a /opt/sede-entorno/" \
  || mal "el Dockerfile no copia deploy/despliegue-remoto.sha256 a /opt/sede-entorno/"

# --- 3. El guion la comprueba ----------------------------------------------------
grep -q '^guion_al_dia() {' "$GUION" \
  && ok "el guion define guion_al_dia" || mal "el guion no define guion_al_dia"
awk '/^preparar_entorno\(\) \{/ {d=1} d && /guion_al_dia/ {f=1} /^}/ {d=0} END {exit f?0:1}' "$GUION" \
  && ok "preparar_entorno llama a guion_al_dia" || mal "preparar_entorno no llama a guion_al_dia"

# Comportamiento: se extrae la función tal cual está en el guion y se ejecuta.
awk '/^guion_al_dia\(\) \{/ {d=1} d {print} d && /^}/ {exit}' "$GUION" > "$T/funcion.sh"
rojo()  { printf '%s\n' "$*" >&2; }
aviso() { printf '%s\n' "$*" >&2; }
verde() { printf '%s\n' "$*"; }
if [ -s "$T/funcion.sh" ]; then
  # shellcheck disable=SC1091
  . "$T/funcion.sh"
  mkdir -p "$T/imagen"
  printf 'guion de prueba\n' > "$T/guion"
  h="$(sha256sum "$T/guion" | cut -d' ' -f1)"

  printf '%s\n' "$h" > "$T/imagen/despliegue-remoto.sha256"
  guion_al_dia "$T/imagen" "$T/guion" >/dev/null 2>&1 \
    && ok "huella igual: sigue" || mal "huella igual: debió seguir"

  printf '%064d\n' 0 > "$T/imagen/despliegue-remoto.sha256"
  salida="$(guion_al_dia "$T/imagen" "$T/guion" 2>&1)"; codigo=$?
  [ "$codigo" -ne 0 ] && ok "huella distinta: se rechaza" || mal "huella distinta: debió rechazar"
  printf '%s' "$salida" | grep -q 'desplegar.sh' \
    && ok "el rechazo dice qué reinstalar (desplegar.sh)" || mal "el rechazo no nombra desplegar.sh"

  rm -f "$T/imagen/despliegue-remoto.sha256"
  salida="$(guion_al_dia "$T/imagen" "$T/guion" 2>&1)"; codigo=$?
  [ "$codigo" -eq 0 ] && ok "imagen sin huella (anterior a la comprobación): sigue" || mal "imagen sin huella: no debía rechazar"
  printf '%s' "$salida" | grep -qi 'aviso\|no trae' \
    && ok "...pero lo avisa" || mal "imagen sin huella: debió avisar"
fi

if [ "$fallos" -eq 0 ]; then echo "guion instalado: todo en orden."; else echo "guion instalado: $fallos fallo(s)." >&2; exit 1; fi

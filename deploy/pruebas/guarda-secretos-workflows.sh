#!/usr/bin/env bash
#
# Guarda: ningún flujo de .github/workflows lee un secreto de la APLICACIÓN.
#
# Los secretos de la aplicación (APP_KEY, contraseñas de la base y de Redis, claves de S3, la frase de
# las copias…) viven en UN sitio: `/etc/sede/<entorno>.secretos.env` en el servidor (ADR-0019). Los
# flujos sólo necesitan DEPLOY_*, VERIFICADOR_VINCULOS_TOKEN y GITHUB_TOKEN. Un flujo que empiece a
# leer `secrets.DB_PASSWORD` convierte a GitHub en un segundo custodio sin que nadie lo haya decidido:
# esta guarda lo hace visible en la revisión del PR.
#
# Los nombres salen de `deploy/secretos-requeridos.txt` (la lista de verdad de los secretos de la
# aplicación). Si un flujo debe leer uno, se decide a propósito: se quita de la lista de la guarda aquí.
#
# Autoprueba: antes de mirar los flujos reales comprueba que la guarda falla con un flujo que lee un
# secreto de la aplicación y pasa con uno que sólo lee DEPLOY_*; una guarda que no puede fallar no sirve.
set -uo pipefail
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LISTA="$RAIZ/deploy/secretos-requeridos.txt"
[ -f "$LISTA" ] || { echo "Falta $LISTA" >&2; exit 2; }

nombres_de_la_aplicacion() { grep -vE '^\s*(#|$)' "$LISTA" | awk '{print $1}'; }

# revisar <carpeta de flujos>: imprime «archivo: NOMBRE» por cada lectura prohibida; devuelve 1 si hay alguna.
revisar() {
  local carpeta="$1" malo=0 f n
  local nombres; nombres="$(nombres_de_la_aplicacion)"
  for f in "$carpeta"/*.yml "$carpeta"/*.yaml; do
    [ -f "$f" ] || continue
    while read -r n; do
      [ -n "$n" ] || continue
      if printf '%s\n' "$nombres" | grep -qx "$n"; then echo "  $(basename "$f"): secrets.$n"; malo=1; fi
    done < <(grep -oE 'secrets\.[A-Za-z0-9_]+' "$f" | sed 's/^secrets\.//' | sort -u)
  done
  return $malo
}

T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
fallos=0

# --- Autoprueba -----------------------------------------------------------------------------
mkdir "$T/malo" "$T/bueno"
printf 'env:\n  X: ${{ secrets.DB_PASSWORD }}\n' > "$T/malo/a.yml"
printf 'env:\n  X: ${{ secrets.DEPLOY_SSH_KEY }}\n  Y: ${{ secrets.GITHUB_TOKEN }}\n' > "$T/bueno/a.yml"
if revisar "$T/malo" >/dev/null; then echo "  ✗ autoprueba: la guarda NO detectó secrets.DB_PASSWORD" >&2; fallos=$((fallos+1)); else echo "  ✓ autoprueba: detecta la lectura de un secreto de la aplicación"; fi
if revisar "$T/bueno" >/dev/null; then echo "  ✓ autoprueba: acepta DEPLOY_* y GITHUB_TOKEN"; else echo "  ✗ autoprueba: rechazó un flujo legítimo" >&2; fallos=$((fallos+1)); fi

# --- Flujos reales ---------------------------------------------------------------------------
if salida="$(revisar "$RAIZ/.github/workflows")"; then
  echo "  ✓ ningún flujo de .github/workflows lee un secreto de la aplicación"
else
  echo "  ✗ estos flujos leen secretos de la APLICACIÓN (ADR-0019: viven sólo en el servidor):" >&2
  printf '%s\n' "$salida" >&2; fallos=$((fallos+1))
fi

[ "$fallos" = 0 ] && exit 0
echo "$fallos fallo(s)." >&2; exit 1

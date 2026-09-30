#!/bin/bash
#
# Despliega una versión y vuelve atrás solo si algo falla.
#
#   desplegar.sh <entorno> <version> [--volver]
#
# Entornos: staging | produccion
# Versión:  el identificador del commit; es también la etiqueta de la imagen.
#
# La vuelta atrás NO es un adorno: la etiqueta de cada versión es el commit, así
# que volver atrás es desplegar otra vez la etiqueta anterior, que sigue en el
# registro. Eso es lo que convierte un despliegue fallido en un incidente de un
# minuto en lugar de una tarde.
#
# Este es el ÚNICO comando que la llave de despliegue puede ejecutar.
#
# Se instala como `/usr/local/bin/desplegar.sh` y se invoca desde la canalización
# así:
#
#   ssh -i <llave> deploy@<servidor> "desplegar.sh staging <confirmación>"
#
# Ojo con ese detalle: la restricción de la llave lo declara como comando forzado
# **sin argumentos**, de modo que sshd ejecuta el guion vacío y entrega lo que
# escribió el cliente en `SSH_ORIGINAL_COMMAND`. Por eso el guion lo lee más
# abajo. Sin eso, se ejecutaría sin entorno ni versión y fallaría siempre.
#
set -euo pipefail

rojo()  { printf '\033[31m%s\033[0m\n' "$*" >&2; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

# ---------------------------------------------------------------------------
# De dónde salen los argumentos
#
# La llave de despliegue entra con un comando FORZADO y SIN argumentos
# (`command="/usr/local/bin/desplegar.sh"` en `authorized_keys`), así que sshd no
# le pasa ninguno. Los entrega en `SSH_ORIGINAL_COMMAND`, tal cual los escribió
# quien llama. Sin leerlos de ahí, este guion arrancaría sin entorno ni versión y
# fallaría SIEMPRE, por mucho que el resto estuviera bien.
# ---------------------------------------------------------------------------
if [ "$#" -eq 0 ] && [ -n "${SSH_ORIGINAL_COMMAND:-}" ]; then
  # División por espacios a propósito: así se reconstruyen los argumentos.
  # shellcheck disable=SC2086
  set -- $SSH_ORIGINAL_COMMAND

  # Quien llama escribe el nombre del comando delante —«desplegar.sh staging …»—
  # porque así se lee mejor en la canalización, así que hay que quitarlo. Se
  # aceptan las dos formas para no depender de cómo lo escriba el cliente.
  case "${1:-}" in
    desplegar.sh|*/desplegar.sh) shift ;;
  esac
fi

ENTORNO="${1:-}"
VERSION="${2:-}"
VOLVER="${3:-}"

# Se validan como si vinieran de fuera, porque vienen de fuera: las escribe el
# cliente y viajan por la red hasta aquí.
case "$ENTORNO" in
  staging|produccion) ;;
  "") rojo "Falta el entorno: staging o produccion"; exit 1 ;;
  *)  rojo "Entorno no válido: '$ENTORNO'"; exit 1 ;;
esac

# La versión se usa como etiqueta de imagen y se escribe en un archivo, así que
# sólo puede ser una confirmación en hexadecimal. Se rechaza cualquier otra cosa
# —espacios, barras, puntos suspensivos— antes de que llegue a una orden.
case "$VERSION" in
  "") rojo "Falta la versión (identificador del commit)"; exit 1 ;;
  *[!0-9a-f]*) rojo "La versión debe ser hexadecimal en minúsculas: '$VERSION'"; exit 1 ;;
esac
if [ "${#VERSION}" -lt 7 ] || [ "${#VERSION}" -gt 40 ]; then
  rojo "La versión debe tener entre 7 y 40 caracteres, tiene ${#VERSION}"
  exit 1
fi

case "$VOLVER" in
  ""|--volver) ;;
  *) rojo "Tercer argumento no válido: '$VOLVER'"; exit 1 ;;
esac

BASE="/opt/sede/$ENTORNO"
COMPOSE="docker compose -f $BASE/compose.yaml --env-file $BASE/.env"
ESPERA="${SEDE_ESPERA_SEGUNDOS:-180}"

[ -f "$BASE/.env" ]     || { rojo "Falta $BASE/.env. ¿Se publicó el entorno?"; exit 1; }
[ -f "$BASE/compose.yaml" ] || { rojo "Falta $BASE/compose.yaml"; exit 1; }

cd "$BASE"

# --- Versión anterior ---------------------------------------------------------
if [ -f "$BASE/version" ]; then
  ANTERIOR="$(cat "$BASE/version")"
else
  ANTERIOR=""
fi

# Se apunta la versión nueva ANTES de intentarlo: si el despliegue muere a mitad,
# queda registrado qué se estaba intentando.
if [ "$VOLVER" != "--volver" ]; then
  printf '%s' "$VERSION" > "$BASE/version"
  [ -n "$ANTERIOR" ] && printf '%s' "$ANTERIOR" > "$BASE/version.anterior"
fi

export APP_VERSION="$VERSION"

volver_atras() {
  if [ -z "$ANTERIOR" ] || [ "$ANTERIOR" = "$VERSION" ]; then
    rojo "No hay versión anterior a la que volver."
    return 1
  fi
  rojo "Volviendo a la versión $ANTERIOR…"
  printf '%s' "$ANTERIOR" > "$BASE/version"
  export APP_VERSION="$ANTERIOR"
  $COMPOSE pull -q || true
  $COMPOSE up -d --remove-orphans
  $COMPOSE up -d --force-recreate nginx
}

# --- Despliegue ---------------------------------------------------------------
echo "Desplegando $VERSION en $ENTORNO"

if ! $COMPOSE pull -q; then
  rojo "No se pudieron traer las imágenes."
  volver_atras || true
  exit 1
fi
verde "  imágenes traídas"

# Las migraciones van ANTES de servir: una aplicación nueva contra un esquema
# viejo falla en la primera petición del ciudadano.
if ! $COMPOSE run --rm app php artisan migrate --force; then
  rojo "Fallaron las migraciones."
  volver_atras || true
  exit 1
fi
verde "  esquema al día"

$COMPOSE rm -f >/dev/null 2>&1 || true
$COMPOSE up -d --remove-orphans

# nginx resuelve la dirección del contenedor al arrancar y la conserva: sin
# recrearlo, seguiría hablándole al contenedor anterior, que ya no existe.
$COMPOSE up -d --force-recreate nginx
verde "  contenedores en marcha"

echo -n "Esperando a que la sede responda…"
ESPERADO=$((ESPERA / 5))
for i in $(seq 1 "$ESPERADO"); do
  if curl -fsSk "https://127.0.0.1:${NGINX_HTTPS_PORT:-443}/ready" >/dev/null 2>&1; then
    echo
    verde "  la sede responde"
    echo
    $COMPOSE ps --format 'table {{.Service}}\t{{.Status}}' | sed 's/^/  /'
    exit 0
  fi
  echo -n "."
  sleep 5
done

echo
rojo "La sede no respondió en ${ESPERA}s. Últimos registros:"
$COMPOSE logs --tail=40 app nginx 2>&1 | sed 's/^/  /' >&2

volver_atras || true
exit 1

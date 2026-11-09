#!/bin/bash
#
# Guarda estática de las copias de seguridad (docs/copias-de-seguridad.md).
#
#   bash deploy/pruebas/guarda-copias.sh
#
# Lo que esta guarda protege son tres desencajes que NO fallan donde se introducen y
# sólo se descubren cuando alguien intenta RESTAURAR, que es el peor momento:
#
#   1. La versión de `pg_dump` de la imagen frente a la del servidor. `pg_dump` se niega
#      a volcar un servidor MÁS NUEVO que él. Si alguien sube `postgres:18` a `19` en
#      compose.yaml y no toca el Dockerfile, las pruebas pasan, la imagen se construye y
#      la primera copia nocturna falla. El Dockerfile ya lo comprueba al construir, pero
#      sólo contra el 18 que lleva escrito; esto comprueba que ese 18 sigue siendo el
#      del servidor, en la propia revisión del cambio.
#   2. Quién puede tocar las copias. `/copias` lo montan sólo `scheduler` (que las hace)
#      y `copia` (la vía manual). Si otro servicio lo monta, el volumen donde están las
#      copias queda al alcance de un proceso que atiende peticiones.
#   3. Que la rotación de secretos no rote la frase de las copias sin que se pida: hacerlo
#      deja sin poder abrirse todo lo copiado hasta ese día.
#
# Es estática a propósito: no construye la imagen (tarda minutos). La prueba real de
# que una copia se hace y se restaura es la del servidor.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

# --- 1. pg_dump y servidor en la misma serie ---------------------------------------
cliente="$(grep -oE 'postgresql[0-9]+-client' "$RAIZ/docker/app/Dockerfile" | head -1 | grep -oE '[0-9]+')"
servidor="$(grep -oE 'image: postgres:[0-9]+' "$RAIZ/compose.yaml" | head -1 | grep -oE '[0-9]+$')"
ci="$(grep -oE 'image: postgres:[0-9]+' "$RAIZ/.github/workflows/ci.yml" | head -1 | grep -oE '[0-9]+$')"

if [ -z "$cliente" ] || [ -z "$servidor" ]; then
  mal "no pude leer la serie del cliente ('${cliente:-?}') o del servidor ('${servidor:-?}')"
elif [ "$cliente" = "$servidor" ]; then
  ok "pg_dump de la imagen (serie $cliente) coincide con el servidor de la pila (serie $servidor)"
else
  mal "pg_dump de la imagen es de la serie $cliente y el servidor de la pila de la $servidor: pg_dump se negaría a volcarlo"
fi

if [ -n "$ci" ] && [ "$ci" = "$servidor" ]; then
  ok "la base de las pruebas de CI es de la misma serie ($ci)"
else
  mal "la base de las pruebas de CI (serie '${ci:-?}') no coincide con la de la pila ($servidor)"
fi

if grep -q "pg_dump (PostgreSQL) $cliente" "$RAIZ/docker/app/Dockerfile"; then
  ok "el Dockerfile comprueba al construir que pg_dump es de la serie $cliente"
else
  mal "el Dockerfile ya no comprueba la versión de pg_dump al construir"
fi

# --- 2. quién monta /copias -------------------------------------------------------
if command -v docker >/dev/null 2>&1 && command -v jq >/dev/null 2>&1; then
  pila="$(APP_KEY=x DB_PASSWORD=x REDIS_PASSWORD=x BACKUP_PASSPHRASE=x \
          docker compose -f "$RAIZ/compose.yaml" --profile copias config --format json 2>/dev/null)"
  if [ -z "$pila" ]; then
    mal "la pila no se resolvió (¿falta docker compose?): no pude comprobar quién monta /copias"
  else
    montan="$(printf '%s' "$pila" | jq -r '[.services | to_entries[] | select((.value.volumes // []) | any(.target == "/copias")) | .key] | sort | join(" ")')"
    if [ "$montan" = "copia scheduler" ]; then
      ok "sólo 'scheduler' y 'copia' montan /copias"
    else
      mal "/copias lo montan: '${montan:-nadie}' (deben ser exactamente 'copia scheduler')"
    fi
  fi
else
  mal "faltan docker o jq: no pude comprobar quién monta /copias"
fi

# --- 2b. /copias no se declara como VOLUME en el Dockerfile --------------------------
# Un `VOLUME /copias` crea un volumen anónimo, vacío y escribible, en CADA contenedor de la
# imagen que no lo monte (app, horizon), y deja uno huérfano en cada despliegue.
if grep -E '^VOLUME' "$RAIZ/docker/app/Dockerfile" | grep -q '/copias'; then
  mal "el Dockerfile declara /copias en VOLUME: app y horizon recibirían un volumen anónimo escribible"
else
  ok "el Dockerfile no declara /copias como VOLUME (no hay volúmenes anónimos de copias)"
fi

# --- 3. la rotación no toca la frase de las copias por defecto ---------------------
if python3 "$RAIZ/deploy/pruebas/prueba-rotar-secretos.py" >/dev/null 2>&1; then
  ok "rotar-secretos.py no rota la frase de las copias salvo con --frase-copias, y conserva la anterior"
else
  mal "rotar-secretos.py cambió su trato de la frase de las copias (ejecuta deploy/pruebas/prueba-rotar-secretos.py para ver el detalle)"
fi

echo
if [ "$fallos" -eq 0 ]; then
  printf '\033[32mLas copias de seguridad encajan con la pila.\033[0m\n'
else
  printf '\033[31m%d fallo(s).\033[0m\n' "$fallos" >&2
  exit 1
fi

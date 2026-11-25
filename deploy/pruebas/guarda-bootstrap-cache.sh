#!/bin/bash
#
# Guarda: `/app/bootstrap/cache` viaja con la IMAGEN y no sobrevive a un despliegue.
#
#   bash deploy/pruebas/guarda-bootstrap-cache.sh
#
# Qué protege. Esa carpeta guarda `packages.php` (qué paquetes de Composer registran
# proveedores de servicio) y `services.php`. Los genera `php artisan package:discover`
# AL CONSTRUIR la imagen, a partir del `vendor/` de ESA versión.
#
# Si un volumen CON NOMBRE se monta encima, Docker copia el contenido de la imagen al
# volumen SÓLO la primera vez (cuando está vacío). Desde entonces cada versión nueva lee
# el `packages.php` de la versión que creó el volumen. Medido el 2026-11-24 con la imagen
# de staging (a171a26): una imagen sin `barryvdh/laravel-dompdf` con el volumen creado por
# la anterior no arranca —`Class "Barryvdh\DomPDF\ServiceProvider" not found`—; con un
# volumen nuevo, sí. Quitar (o añadir) un paquete con proveedor rompía (o ignoraba) el
# arranque sólo en los servidores donde el volumen ya existía, nunca en la CI.
#
# Comprobado también ese día, con la carpeta montada en SÓLO LECTURA y el contenido de la
# imagen: `app`, `horizon` y `scheduler` sanos; portada, catálogo, PDF/ODT, sitemap,
# búsqueda, inicio de sesión del panel y las seis tareas programadas (incluida
# `backup:run`) terminan bien. Las cachés de configuración, rutas y eventos ya van al
# `/tmp` de cada contenedor (docker/app/entrypoint.sh), así que nada escribe ahí.
#
# Es estática: no construye la imagen.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DOCKERFILE="$RAIZ/docker/app/Dockerfile"
fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

# --- 1. El Dockerfile no declara /app/bootstrap/cache como VOLUME ------------------------
# Un VOLUME en ese camino crea un volumen anónimo por contenedor, y Compose lo CONSERVA al
# recrear el contenedor (sin `--renew-anon-volumes`): el mismo efecto que uno con nombre.
if grep -E '^[[:space:]]*VOLUME' "$DOCKERFILE" | grep -q 'bootstrap/cache'; then
  mal "docker/app/Dockerfile declara /app/bootstrap/cache en VOLUME"
else
  ok "el Dockerfile no declara /app/bootstrap/cache como VOLUME"
fi

# --- 2. Los manifiestos se generan al construir ----------------------------------------
if grep -q 'php artisan package:discover' "$DOCKERFILE"; then
  ok "el Dockerfile genera packages.php y services.php al construir (package:discover)"
else
  mal "el Dockerfile ya no ejecuta package:discover: la imagen saldría sin manifiestos"
fi

# --- 3. Ningún servicio monta algo en /app/bootstrap/cache -----------------------------
if command -v docker > /dev/null 2>&1 && command -v jq > /dev/null 2>&1; then
  pila="$(APP_KEY=x DB_PASSWORD=x DB_APP_PASSWORD=x DB_LECTURA_PASSWORD=x REDIS_PASSWORD=x BACKUP_PASSPHRASE=x \
    docker compose -f "$RAIZ/compose.yaml" --profile copias --profile migrar config --format json 2> /dev/null)"
  if [ -z "$pila" ]; then
    mal "la pila no se resolvió: no pude comprobar los montajes"
  else
    montan="$(printf '%s' "$pila" | jq -r '[.services | to_entries[]
      | select((.value.volumes // []) | any(.target == "/app/bootstrap/cache")) | .key] | sort | join(" ")')"
    if [ -z "$montan" ]; then
      ok "ningún servicio monta un volumen en /app/bootstrap/cache"
    else
      mal "montan un volumen en /app/bootstrap/cache: $montan"
    fi
    if printf '%s' "$pila" | jq -e '.volumes // {} | has("cache-app")' > /dev/null; then
      mal "compose.yaml aún declara el volumen 'cache-app'"
    else
      ok "compose.yaml no declara el volumen 'cache-app'"
    fi
  fi
else
  mal "faltan docker o jq: no pude comprobar los montajes"
fi

if [ "$fallos" -gt 0 ]; then
  printf '\n\033[31m%d comprobación(es) fallaron.\033[0m\n' "$fallos" >&2
  exit 1
fi
printf '\n\033[32mbootstrap/cache viaja con la imagen.\033[0m\n'

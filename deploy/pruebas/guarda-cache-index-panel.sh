#!/usr/bin/env bash
# El documento del panel (index.html, que lo sirve `location /admin`) debe llevar
# `Cache-Control: no-cache`: así el navegador lo revalida en cada visita y toma los
# activos del despliegue vigente. Los activos llevan hash y se borran al desplegar; con un
# index.html sin Cache-Control el navegador aplica caché heurística (solo hay
# `Last-Modified`) y puede seguir pidiendo activos que ya no existen (404).
# Evidencia: staging, 2026-11-19, tras el despliegue 44568098316.
#
# Guarda estática, como las de ci.yml: lee el bloque `location /admin { … }` real.
# Uso: bash deploy/pruebas/guarda-cache-index-panel.sh [ruta/a/default.conf]
set -u
RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
f="${1:-$RAIZ/docker/nginx/conf.d/default.conf}"
bloque="$(awk '/^[[:space:]]*location \/admin[[:space:]]*\{/{d=1} d{print} d&&/^    \}/{exit}' "$f")"
[ -n "$bloque" ] || { echo "✗ no se encontró 'location /admin' en $f"; exit 1; }
echo "$bloque" | grep -Eq '^\s*add_header\s+Cache-Control\s+"no-cache"\s+always;' \
  || { echo "✗ 'location /admin' no emite Cache-Control: no-cache: el index del panel puede quedar en caché tras un despliegue."; exit 1; }
echo "✓ el index del panel sale con Cache-Control: no-cache"

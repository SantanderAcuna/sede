#!/usr/bin/env bash
# Modo de la CSP (informe ↔ aplicación) con un nginx REAL y los archivos reales del repo.
#
# Comprueba que:
#   · sin variable, o con valores raros, la cabecera sigue siendo Report-Only (staging intacto);
#   · SEDE_CSP_MODO=aplicar emite Content-Security-Policy con LA MISMA política;
#   · el script es idempotente y nginx acepta la configuración en ambos modos;
#   · el panel conserva `blob:` en img-src y el sitio no, en ambos modos.
# Uso: bash deploy/pruebas/prueba-csp-modo.sh   (requiere nginx y curl)
set -u
RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
SCRIPT="$RAIZ/docker/nginx/entrypoint/40-csp-modo.sh"
MAPA="$RAIZ/docker/nginx/snippets/mapa-csp-panel.conf"
ORIGINAL="$RAIZ/docker/nginx/snippets/csp-informe.conf"
T="$(mktemp -d)"
PUERTO="${PUERTO_CSP_MODO:-18084}"
fallos=0
limpiar() { [ -f "$T/nginx.pid" ] && kill "$(cat "$T/nginx.pid")" 2>/dev/null; rm -rf "$T"; }
trap limpiar EXIT

command -v nginx >/dev/null || { echo "Falta nginx."; exit 2; }
[ -x "$SCRIPT" ] || { echo "  ✗ $SCRIPT no existe o no es ejecutable"; exit 1; }

comprobar() { if [ "$3" = "$2" ]; then echo "  ✓ $1"; else echo "  ✗ $1 (esperado: «$2», real: «$3»)"; fallos=$((fallos+1)); fi; }

politica() { grep -E '^add_header Content-Security-Policy(-Report-Only)? ' "$1" | sed -E 's/^add_header Content-Security-Policy(-Report-Only)? //'; }
nombre()   { grep -oE '^add_header Content-Security-Policy(-Report-Only)?' "$1" | sed 's/add_header //'; }

# Copia de trabajo de los snippets para cada escenario.
# El script lee ORIGEN (inmutable, como en la imagen) y genera DESTINO.
preparar() { rm -rf "$T/s"; mkdir -p "$T/s"; rm -f "$GEN"; }
GEN="$T/s/sede-csp.conf"
ejecutar() { SEDE_CSP_ORIGEN="$ORIGINAL" SEDE_CSP_DESTINO="$GEN" sh "$SCRIPT" >/dev/null 2>&1; }

echo "Sin variable:"
preparar; env -u SEDE_CSP_MODO SEDE_CSP_ORIGEN="$ORIGINAL" SEDE_CSP_DESTINO="$GEN" sh "$SCRIPT" >/dev/null 2>&1
comprobar "sigue en Report-Only" "Content-Security-Policy-Report-Only" "$(nombre "$GEN")"
comprobar "lo generado es idéntico al original" "" "$(diff "$ORIGINAL" "$GEN")"
comprobar "el original no se toca" "$(sha256sum < "$ORIGINAL")" "$(git -C "$RAIZ" show HEAD:docker/nginx/snippets/csp-informe.conf 2>/dev/null | sha256sum || cat "$ORIGINAL" | sha256sum)"

echo "Valores no reconocidos:"
for v in "" "informe" "APLICAR" "si" "1" "true" "aplicar " ; do
  preparar; SEDE_CSP_MODO="$v" ejecutar
  comprobar "«$v» deja Report-Only" "Content-Security-Policy-Report-Only" "$(nombre "$GEN")"
done

echo "SEDE_CSP_MODO=aplicar:"
preparar; SEDE_CSP_MODO=aplicar ejecutar
comprobar "emite Content-Security-Policy" "Content-Security-Policy" "$(nombre "$GEN")"
comprobar "la política es idéntica" "$(politica "$ORIGINAL")" "$(politica "$GEN")"
SEDE_CSP_MODO=aplicar ejecutar
comprobar "es idempotente" "Content-Security-Policy" "$(nombre "$GEN")"
comprobar "queda una sola cabecera CSP" "1" "$(grep -c '^add_header Content-Security-Policy' "$GEN")"

# nginx real, en ambos modos.
servir() { # modo
  preparar
  SEDE_CSP_MODO="$1" ejecutar
  mkdir -p "$T/tmp"; chmod 755 "$T"
  cat > "$T/nginx.conf" <<CONF
pid $T/nginx.pid;
error_log $T/error.log;
daemon on;
events {}
http {
    access_log off;
    client_body_temp_path $T/tmp/a; proxy_temp_path $T/tmp/b; fastcgi_temp_path $T/tmp/c; uwsgi_temp_path $T/tmp/d; scgi_temp_path $T/tmp/e;
    include $MAPA;
    server {
        listen 127.0.0.1:$PUERTO;
        location / { include $GEN; default_type text/html; return 200 'sitio'; }
        location /admin { include $GEN; default_type text/html; return 200 'panel'; }
    }
}
CONF
  nginx -t -c "$T/nginx.conf" >/dev/null 2>&1 || { echo "  ✗ nginx rechaza la configuración en modo $1"; fallos=$((fallos+1)); return; }
  nginx -c "$T/nginx.conf"; sleep 0.5
}
parar() { [ -f "$T/nginx.pid" ] && kill "$(cat "$T/nginx.pid")" 2>/dev/null; sleep 0.5; rm -f "$T/nginx.pid"; }
cabeceras() { curl -s -D - -o /dev/null "http://127.0.0.1:$PUERTO$1"; }

for modo in informe aplicar; do
  echo "nginx real en modo $modo:"
  servir "$modo"
  sitio="$(cabeceras /)"; panel="$(cabeceras /admin/)"
  if [ "$modo" = informe ]; then
    comprobar "sitio: Report-Only" "1" "$(echo "$sitio" | grep -ci '^content-security-policy-report-only:')"
    comprobar "sitio: sin cabecera que bloquee" "0" "$(echo "$sitio" | grep -ci '^content-security-policy:')"
  else
    comprobar "sitio: Content-Security-Policy" "1" "$(echo "$sitio" | grep -ci '^content-security-policy:')"
    comprobar "sitio: sin Report-Only" "0" "$(echo "$sitio" | grep -ci '^content-security-policy-report-only:')"
  fi
  comprobar "sitio: img-src sin blob:" "0" "$(echo "$sitio" | grep -i '^content-security-policy' | grep -c 'blob:')"
  comprobar "panel: img-src con blob:" "1" "$(echo "$panel" | grep -i '^content-security-policy' | grep -c 'img-src [^;]*blob:')"
  parar
done

[ "$fallos" -eq 0 ] && { echo "csp-modo: todo en orden"; exit 0; }
echo "csp-modo: $fallos fallo(s)"; exit 1

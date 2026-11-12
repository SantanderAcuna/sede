#!/usr/bin/env bash
# CSP del panel: la vista previa de la imagen que se elige antes de subirla usa una dirección
# `blob:` (URL.createObjectURL). La política en modo informe solo permitía `'self' data:`, así que
# el navegador avisaba en la consola; al pasar a modo de aplicación la vista previa dejaría de verse.
#
# Regla: `blob:` en `img-src` SOLO en el panel (/admin). El sitio público conserva `'self' data:`.
# La política es una sola (csp-informe.conf); lo único que cambia es una variable que fija un mapa.
#
# Esta prueba levanta un nginx REAL con los archivos reales del repo (el mapa y el snippet) y mide
# qué cabecera sale en cada ruta. Uso: bash deploy/pruebas/prueba-csp-panel.sh  (requiere nginx y curl)
set -u
RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
MAPA="$RAIZ/docker/nginx/snippets/mapa-csp-panel.conf"
SNIPPET="$RAIZ/docker/nginx/snippets/csp-informe.conf"
NGINX_CONF="$RAIZ/docker/nginx/nginx.conf"
T="$(mktemp -d)"
PUERTO="${PUERTO_CSP:-18083}"
fallos=0
limpiar() { [ -f "$T/nginx.pid" ] && kill "$(cat "$T/nginx.pid")" 2>/dev/null; rm -rf "$T"; }
trap limpiar EXIT

command -v nginx >/dev/null || { echo "Falta nginx."; exit 2; }
[ -f "$MAPA" ] || { echo "  ✗ no existe $MAPA"; fallos=$((fallos+1)); }
[ -f "$SNIPPET" ] || { echo "  ✗ no existe $SNIPPET"; fallos=$((fallos+1)); }
[ "$fallos" -eq 0 ] || { echo "csp-panel: $fallos fallo(s)"; exit 1; }

comprobar() { # descripción, esperado, real
  if [ "$3" = "$2" ]; then echo "  ✓ $1"; else echo "  ✗ $1 (esperado: «$2», real: «$3»)"; fallos=$((fallos+1)); fi
}

# nginx.conf debe incluir el mapa en el contexto http: sin esto la variable no existe y nginx no arranca.
if grep -q 'include[[:space:]]\+/etc/nginx/snippets/mapa-csp-panel.conf;' "$NGINX_CONF"; then
  echo "  ✓ nginx.conf incluye el mapa de la CSP del panel"
else
  echo "  ✗ nginx.conf no incluye snippets/mapa-csp-panel.conf"; fallos=$((fallos+1))
fi

mkdir -p "$T/panel/admin" "$T/tmp"
chmod 755 "$T"
echo '<html><body>panel</body></html>' > "$T/panel/admin/index.html"
chmod -R a+rX "$T/panel"
cat > "$T/nginx.conf" <<CONF
pid $T/nginx.pid;
error_log $T/error.log;
daemon on;
events {}
http {
    include /etc/nginx/mime.types;
    access_log off;
    client_body_temp_path $T/tmp/a; proxy_temp_path $T/tmp/b; fastcgi_temp_path $T/tmp/c; uwsgi_temp_path $T/tmp/d; scgi_temp_path $T/tmp/e;
    include $MAPA;
    server {
        listen 127.0.0.1:$PUERTO;
        location / {
            include $SNIPPET;
            default_type text/html;
            return 200 'sitio';
        }
        location /admin {
            include $SNIPPET;
            root $T/panel;
            try_files \$uri \$uri/ /admin/index.html;
        }
    }
}
CONF
nginx -t -c "$T/nginx.conf" >"$T/t.log" 2>&1 || { cat "$T/t.log"; echo "  ✗ nginx rechaza la configuración"; exit 1; }
nginx -c "$T/nginx.conf" || { echo "  ✗ no arrancó nginx"; exit 1; }
for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$PUERTO/" && break; sleep 0.2; done

csp() { # ruta → "estado|política"
  curl -s -D - -o /dev/null "http://127.0.0.1:$PUERTO$1" | tr -d '\r' \
    | awk -F': ' 'NR==1{split($0,a," ");e=a[2]} tolower($1)=="content-security-policy-report-only"{v=$0; sub(/^[^:]*: /,"",v)} END{print e"|"v}'
}
img() { csp "$1" | grep -o "img-src [^;]*"; }
estado() { csp "$1" | cut -d'|' -f1; }

comprobar "el sitio público conserva img-src 'self' data:"           "img-src 'self' data:"       "$(img /)"
comprobar "otra ruta del sitio público tampoco admite blob:"          "img-src 'self' data:"       "$(img /tramites/x)"
comprobar "el panel admite blob: en img-src"                          "img-src 'self' data: blob:" "$(img /admin/)"
comprobar "una ruta profunda del panel (respaldo a index.html) también" "img-src 'self' data: blob:" "$(img /admin/contenidos/noticias)"
comprobar "las respuestas medidas son 200 (no una página de error)"   "200 200 200 200" "$(estado /) $(estado /tramites/x) $(estado /admin/) $(estado /admin/contenidos/noticias)"

sitio="$(csp / | cut -d'|' -f2)"
panel="$(csp /admin/ | cut -d'|' -f2)"
comprobar "el panel y el sitio emiten la MISMA política salvo ' blob:' en img-src" "$sitio" "${panel/ blob:;/;}"
comprobar "blob: aparece una sola vez en la política del panel" "1" "$(echo "$panel" | grep -o 'blob:' | wc -l)"
comprobar "blob: no aparece nunca en la del sitio"              "0" "$(echo "$sitio" | grep -o 'blob:' | wc -l)"
comprobar "script-src del panel sigue siendo solo 'self'"       "script-src 'self'" "$(echo "$panel" | grep -o "script-src [^;]*")"

if [ "$fallos" -eq 0 ]; then echo "csp-panel: todo en orden."; else echo "csp-panel: $fallos fallo(s)."; exit 1; fi

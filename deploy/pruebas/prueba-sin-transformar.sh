#!/usr/bin/env bash
# M01-4 (RF-01-017, CA-01-017): el borde de Cloudflare no debe poder inyectar su baliza de
# Web Analytics antes del consentimiento. Según la documentación de Cloudflare, una respuesta con
# `Cache-Control: no-transform` no se modifica (y por eso no se le añade la baliza).
#
# Esta prueba levanta un nginx REAL con los archivos reales del repo (el mapa y el snippet) y un
# origen simulado, y comprueba qué cabeceras salen. Lo que NO prueba —y no puede— es que Cloudflare
# respete la cabecera: eso se comprueba en staging con `npm run test:cookies` (ver deploy/README.md).
#
# Uso: bash deploy/pruebas/prueba-sin-transformar.sh        (requiere nginx, python3 y curl)
set -u
RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
MAPA="$RAIZ/docker/nginx/snippets/mapa-sin-transformar.conf"
SNIPPET="$RAIZ/docker/nginx/snippets/sin-transformar.conf"
COMPRESION="$RAIZ/docker/nginx/snippets/compresion.conf"
T="$(mktemp -d)"
PUERTO_NGINX="${PUERTO_NGINX:-18081}"
PUERTO_ORIGEN="${PUERTO_ORIGEN:-18082}"
fallos=0
limpiar() { [ -f "$T/nginx.pid" ] && kill "$(cat "$T/nginx.pid")" 2>/dev/null; [ -n "${ORIGEN:-}" ] && kill "$ORIGEN" 2>/dev/null; rm -rf "$T"; }
trap limpiar EXIT

command -v nginx >/dev/null || { echo "Falta nginx."; exit 2; }
[ -f "$MAPA" ] || { echo "  ✗ no existe $MAPA"; fallos=$((fallos+1)); }
[ -f "$SNIPPET" ] || { echo "  ✗ no existe $SNIPPET"; fallos=$((fallos+1)); }
[ -f "$COMPRESION" ] || { echo "  ✗ no existe $COMPRESION"; fallos=$((fallos+1)); }
[ "$fallos" -eq 0 ] || { echo "sin-transformar: $fallos fallo(s)"; exit 1; }

# Origen simulado: HTML (como Nitro, sin Cache-Control), JSON y un activo con su propio Cache-Control.
cat > "$T/origen.py" <<PY
import http.server
class H(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path.startswith('/api'):
            tipo, cuerpo, extra = 'application/json', b'{"ok":true}', {}
        elif self.path.startswith('/_nuxt'):
            tipo, cuerpo, extra = 'application/javascript', b'1', {'Cache-Control': 'public, max-age=31536000, immutable'}
        elif self.path.startswith('/grande'):
            import random
            r = random.Random(7)
            pal = ['tramite','servicio','ciudadano','alcaldia','distrito','normativa','peticion','queja','radicado','secretaria','vigencia','formulario','requisito','atencion','horario','consulta']
            cuerpo = ('<html><body>' + ''.join('<p class="c%d"><a href="/t/%d">%s %s %s</a></p>' % (r.randint(0,40), r.randint(0,99999), r.choice(pal), r.choice(pal), r.choice(pal)) for _ in range(1800)) + '</body></html>').encode()
            tipo, extra = 'text/html;charset=utf-8', {}
        else:
            tipo, cuerpo, extra = 'text/html;charset=utf-8', b'<html><body>x</body></html>', {}
        self.send_response(200); self.send_header('Content-Type', tipo)
        for k, v in extra.items(): self.send_header(k, v)
        self.send_header('Content-Length', str(len(cuerpo))); self.end_headers(); self.wfile.write(cuerpo)
    def log_message(self, *a): pass
http.server.HTTPServer(('127.0.0.1', $PUERTO_ORIGEN), H).serve_forever()
PY
python3 "$T/origen.py" 2>"$T/origen.err" & ORIGEN=$!

mkdir -p "$T/panel/admin" "$T/tmp"
chmod 755 "$T"
echo '<html><body>panel</body></html>' > "$T/panel/admin/index.html"
echo 'console.log(1)' > "$T/panel/admin/app.js"
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
    include $COMPRESION;
    server {
        listen 127.0.0.1:$PUERTO_NGINX;
        location / {
            include $SNIPPET;
            proxy_pass http://127.0.0.1:$PUERTO_ORIGEN;
        }
        location /admin {
            include $SNIPPET;
            root $T/panel;
            try_files \$uri \$uri/ /admin/index.html;
        }
    }
}
CONF
sleep 0.5; [ -s "$T/origen.err" ] && { cat "$T/origen.err"; echo "  ✗ el origen simulado no arrancó"; exit 1; }
nginx -t -c "$T/nginx.conf" >"$T/t.log" 2>&1 || { cat "$T/t.log"; echo "  ✗ nginx rechaza la configuración"; exit 1; }
nginx -c "$T/nginx.conf" || { echo "  ✗ no arrancó nginx"; exit 1; }
for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$PUERTO_NGINX/api" && break; sleep 0.2; done

# Estado HTTP y valores de Cache-Control de una URL. Una respuesta de ERROR de nginx también es
# `text/html` y recibiría la cabecera: sin comprobar el 200, la prueba daría por buena una página 500.
cc() { # ruta → "estado|valor1;valor2"
  curl -s -D - -o /dev/null "http://127.0.0.1:$PUERTO_NGINX$1" | tr -d '\r' \
    | awk -F': ' 'NR==1{split($0,a," ");e=a[2]} tolower($1)=="cache-control"{v=(v==""?$2:v";"$2)} END{print e"|"v}'
}
comprobar() { # descripción, esperado (texto exacto de las líneas), real
  if [ "$3" = "$2" ]; then echo "  ✓ $1"; else echo "  ✗ $1 (esperado: «$2», real: «$3»)"; fallos=$((fallos+1)); fi
}

comprobar "el HTML del sitio sale con Cache-Control: no-transform"        "200|no-transform" "$(cc /)"
comprobar "una ruta cualquiera del sitio (HTML) también"                  "200|no-transform" "$(cc /tramites)"
comprobar "el HTML del panel sale con Cache-Control: no-transform"        "200|no-transform" "$(cc /admin/)"
comprobar "el JSON de la API no lleva Cache-Control añadido"              "200|" "$(cc /api/v1/x)"
comprobar "el activo conserva SU Cache-Control, sin duplicados"           "200|public, max-age=31536000, immutable" "$(cc /_nuxt/a.js)"
comprobar "un activo estático del panel no lleva Cache-Control añadido"   "200|" "$(cc /admin/app.js)"

# Compresión: con no-transform el borde ya no recomprime, así que manda la del origen. El nivel por
# defecto de nginx (1) deja el HTML ~15 % más grande que el nivel 5 (medido con la portada real:
# 15,1 KB frente a 12,9 KB; zstd -3 del borde: 14,0 KB). El cuerpo de prueba es sintético (semilla fija).
curl -s -H 'Accept-Encoding: gzip' -o "$T/grande.gz" -w '%{http_code}|%{size_download}' "http://127.0.0.1:$PUERTO_NGINX/grande" > "$T/grande.txt"
curl -s -H 'Accept-Encoding: identity' -o "$T/grande.html" "http://127.0.0.1:$PUERTO_NGINX/grande"
read -r nivel1 nivel5 < <(python3 -c "
import zlib,sys
d=open('$T/grande.html','rb').read()
print(len(zlib.compress(d,1)), len(zlib.compress(d,5)))")
real="$(cut -d'|' -f2 "$T/grande.txt")"
if [ "$(cut -d'|' -f1 "$T/grande.txt")" = 200 ] && [ "$real" -le $(( nivel5 * 102 / 100 )) ]; then
  echo "  ✓ el HTML grande sale comprimido a nivel ≥ 5 ($real B; nivel 1: $nivel1 B, nivel 5: $nivel5 B)"
else
  echo "  ✗ el HTML grande no sale comprimido a nivel ≥ 5 (real: $real B; nivel 1: $nivel1 B, nivel 5: $nivel5 B)"; fallos=$((fallos+1))
fi

if [ "$fallos" -eq 0 ]; then echo "sin-transformar: todo en orden."; else echo "sin-transformar: $fallos fallo(s)."; exit 1; fi

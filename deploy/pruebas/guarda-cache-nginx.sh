#!/usr/bin/env bash
# T9.7: guarda estática de la caché de nginx. Es la parte de la prueba que no necesita arrancar nada
# (la que sí lo hace es prueba-cache-nginx.sh). Falla si se afloja cualquiera de estas cosas:
#
#  1. El nombre de la cookie de sesión del `map` de nginx coincide con el que emite Laravel
#     (`Str::slug(APP_NAME).'-session'`, salvo SESSION_COOKIE). Si no coincidiera, el editor dejaría
#     de saltarse la caché. Laravel lo hace imposible de filtrar mientras emita `Set-Cookie` al
#     editor, pero esa es una protección de otra capa, no una razón para dejar dos nombres sueltos.
#  2. `fastcgi_read_timeout` se declara UNA vez, en `nginx.conf` (contexto http). Repetido dentro de
#     un `location` que incluye `snippets/fastcgi.conf` es `emerg` y nginx no arranca (B10).
#  3. Las ubicaciones con caché no llevan `add_header`: un `add_header` en un `location` descarta los
#     de seguridad heredados del servidor.
#  4. La ubicación de imágenes va ANTES de `~ /\.` y de los bloques por extensión: si va después, un
#     slug como `.oculto` llega a PHP donde hoy recibe 403.
#  5. El registro lleva `cache=$upstream_cache_status` (sin él no se puede ver MISS/HIT/STALE).
#  6. En compose, el barrido es un contenedor mínimo: mismo volumen y misma imagen que nginx, sin
#     red, solo lectura, sin capacidades, sin puertos y con el chequeo de salud heredado desactivado.
#  7. El script del barrido borra por `-mmin +1429` (busybox borra desde N+1 minutos: 1429 deja
#     la edad máxima por debajo de 24 h) y atiende SIGQUIT (la señal de parada de la imagen de nginx).
#
# Uso: bash deploy/pruebas/guarda-cache-nginx.sh      (requiere python3 con PyYAML y docker compose)
set -uo pipefail
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$RAIZ" || exit 1

export APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' DB_PASSWORD=prueba DB_APP_PASSWORD=prueba \
  DB_LECTURA_PASSWORD=prueba REDIS_PASSWORD=prueba REDIS_SITIO_PASSWORD=prueba-sitio BACKUP_PASSPHRASE=prueba \
  S3_ACCESS_KEY=prueba S3_SECRET_KEY=prueba
docker compose -f compose.yaml config --format json > "${TMPDIR:-/tmp}/pila-cache-nginx.json" 2>/dev/null \
  || { echo "docker compose config falló: no se puede comprobar el compose."; exit 1; }

python3 - "${TMPDIR:-/tmp}/pila-cache-nginx.json" <<'PY'
import json
import re
import sys
from pathlib import Path

pila = json.load(open(sys.argv[1], encoding="utf-8"))
fallos = 0


def ok(m):
    print(f"  \033[32m✓\033[0m {m}")


def mal(m):
    global fallos
    fallos += 1
    print(f"  \033[31m✗ {m}\033[0m", file=sys.stderr)


def comprueba(cond, bien, roto):
    ok(bien) if cond else mal(roto)


def sin_comentarios(t):
    return "\n".join(re.sub(r"(^|\s)#.*$", "", l) for l in t.splitlines())


nginx_conf = Path("docker/nginx/nginx.conf").read_text(encoding="utf-8")
default_conf = Path("docker/nginx/conf.d/default.conf").read_text(encoding="utf-8")
fastcgi_conf = Path("docker/nginx/snippets/fastcgi.conf").read_text(encoding="utf-8")
nginx_s, default_s, fastcgi_s = map(sin_comentarios, (nginx_conf, default_conf, fastcgi_conf))

# 1. Nombre de la cookie de sesión.
def slug(s):
    return re.sub(r"[^a-z0-9]+", "-", s.lower()).strip("-")

mapa = re.search(r'map\s+\$http_cookie\s+\$sesion_del_panel\s*\{[^}]*"~\(\^\|;\\s\*\)([A-Za-z0-9_-]+)=" +1;', nginx_s)
comprueba(mapa is not None, "nginx.conf declara el mapa $sesion_del_panel", "nginx.conf no declara el mapa $sesion_del_panel con el formato esperado")
nombre_nginx = mapa.group(1) if mapa else None
esperados = {}
for f in sorted(Path("deploy").glob("*.env")):
    texto = f.read_text(encoding="utf-8")
    cookie = re.search(r"^SESSION_COOKIE=(\S+)", texto, re.M)
    app = re.search(r"^APP_NAME=(\S+)", texto, re.M)
    if cookie:
        esperados[f.name] = cookie.group(1)
    elif app:
        esperados[f.name] = slug(app.group(1)) + "-session"
comprueba(len(esperados) > 0, "se leyó APP_NAME/SESSION_COOKIE de deploy/*.env", "no se encontró APP_NAME ni SESSION_COOKIE en deploy/*.env")
for archivo, nombre in esperados.items():
    comprueba(nombre == nombre_nginx,
              f"{archivo}: la cookie de sesión es «{nombre}», igual que la del mapa de nginx",
              f"{archivo}: la cookie de sesión de Laravel es «{nombre}» y el mapa de nginx usa «{nombre_nginx}»")

# 2. fastcgi_read_timeout una sola vez, en http.
comprueba(len(re.findall(r"^\s*fastcgi_read_timeout\b", nginx_s, re.M)) == 1,
          "nginx.conf declara fastcgi_read_timeout una vez (contexto http)",
          "nginx.conf debe declarar fastcgi_read_timeout exactamente una vez en el contexto http")
comprueba("fastcgi_read_timeout" not in fastcgi_s,
          "snippets/fastcgi.conf ya no fija fastcgi_read_timeout",
          "snippets/fastcgi.conf vuelve a fijar fastcgi_read_timeout: repetido en un location que lo redefine, nginx no arranca")


def bloque(texto, inicio):
    i = texto.index(inicio)
    j = texto.index("{", i)
    n, k = 1, j + 1
    while n:
        n += {"{": 1, "}": -1}.get(texto[k], 0)
        k += 1
    return texto[i:k]


IMAGENES = "location ~ ^/api/v1/(noticias/[^/.][^/]*/imagen|banners/[0-9a-f-]+/imagen|contenido/imagenes/[0-9a-f-]+)$"
POLITICAS = "location ~ ^/politicas/[a-z0-9-]+(\\.(odt|pdf)|/documento)$"
try:
    # Se compara sin la alineación de espacios de las directivas.
    b_img = re.sub(r"[ \t]+", " ", bloque(default_s, IMAGENES))
    b_pol = re.sub(r"[ \t]+", " ", bloque(default_s, POLITICAS))
except ValueError:
    mal("default.conf no declara las ubicaciones de imágenes y de documentos de políticas con la expresión esperada")
    sys.exit(1)

# 3. Sin add_header en las ubicaciones con caché.
for nombre, b in (("imágenes", b_img), ("documentos de políticas", b_pol)):
    comprueba("add_header" not in b, f"la ubicación de {nombre} no usa add_header",
              f"la ubicación de {nombre} usa add_header: descartaría las cabeceras de seguridad del servidor")

# 4. Orden.
pos_img = default_s.index(IMAGENES)
for marca in ("location ~ /\\.", "location ~* \\.(env|ini|log"):
    comprueba(pos_img < default_s.index(marca), f"la ubicación de imágenes va antes de «{marca}»",
              f"la ubicación de imágenes va DESPUÉS de «{marca}»: `/noticias/.oculto/imagen` llegaría a PHP")

# Directivas imprescindibles.
for nombre, b, cache in (("imágenes", b_img, "imagenes"), ("documentos de políticas", b_pol, "documentos")):
    for d in (f"fastcgi_cache {cache};", "fastcgi_cache_key $request_method$uri;", "fastcgi_cache_valid 200 404 1s;",
              "fastcgi_ignore_headers Cache-Control Expires;", "fastcgi_cache_bypass $sesion_del_panel;",
              "fastcgi_cache_use_stale error timeout http_500 http_503 updating;", "fastcgi_cache_lock on;"):
        comprueba(d in b, f"{nombre}: {d}", f"{nombre}: falta «{d}»")
comprueba(re.search(r"fastcgi_no_cache\s+\$sesion_del_panel\s+\$no_guardar_imagen;", b_img) is not None,
          "imágenes: fastcgi_no_cache $sesion_del_panel $no_guardar_imagen", "imágenes: falta fastcgi_no_cache con $sesion_del_panel y $no_guardar_imagen")
comprueba(re.search(r"fastcgi_no_cache\s+\$sesion_del_panel\s+\$no_guardar_404;", b_pol) is not None,
          "documentos: fastcgi_no_cache $sesion_del_panel $no_guardar_404", "documentos: falta fastcgi_no_cache con $sesion_del_panel y $no_guardar_404")
comprueba("fastcgi_read_timeout 5s;" in b_img and "fastcgi_connect_timeout 2s;" in b_img,
          "imágenes: espera de 5 s al origen y 2 s de conexión", "imágenes: faltan fastcgi_read_timeout 5s / fastcgi_connect_timeout 2s")
comprueba("fastcgi_read_timeout" not in b_pol, "documentos: conservan los 60 s globales (el PDF/A se genera al vuelo)",
          "documentos: no deben bajar fastcgi_read_timeout")
comprueba("fastcgi_cache_lock_timeout 60s;" in b_pol and "fastcgi_cache_lock_age 60s;" in b_pol,
          "documentos: el bloqueo espera hasta 60 s (varias descargas en frío = una sola generación)",
          "documentos: faltan fastcgi_cache_lock_timeout 60s y fastcgi_cache_lock_age 60s")
for nombre, zona, tam, inact in (("imagenes", "imagenes", "1g", "1h"), ("documentos", "documentos", "512m", "1h")):
    m = re.search(rf"fastcgi_cache_path\s+/var/cache/sede/{nombre}\s+levels=1:2\s+keys_zone={zona}:\d+m\s+max_size={tam}\s+inactive={inact}\s+use_temp_path=off;", nginx_s)
    comprueba(m is not None, f"zona {zona}: /var/cache/sede/{nombre}, max_size={tam}, inactive={inact}",
              f"zona {zona}: fastcgi_cache_path no coincide con el diseño (/var/cache/sede/{nombre}, max_size={tam}, inactive={inact}, use_temp_path=off)")

# 5. Registro.
fmt = re.search(r"log_format\s+principal\s+(.*?);", nginx_s, re.S)
comprueba(fmt is not None and "cache=$upstream_cache_status" in fmt.group(1),
          "log_format principal registra cache=$upstream_cache_status", "log_format principal no registra cache=$upstream_cache_status")

# 6. Compose.
srv = pila["services"]
nginx, barrido = srv.get("nginx"), srv.get("cache-barrido")
comprueba(barrido is not None, "compose: existe el servicio cache-barrido", "compose: falta el servicio cache-barrido")
if barrido and nginx:
    montaje = lambda s: [(v["source"], v["target"]) for v in s.get("volumes", []) if v.get("type") == "volume"]
    m_n = [m for m in montaje(nginx) if m[1] == "/var/cache/sede"]
    m_b = [m for m in montaje(barrido) if m[1] == "/var/cache/sede"]
    comprueba(len(m_n) == 1 and m_n == m_b, "nginx y cache-barrido montan el MISMO volumen en /var/cache/sede",
              "nginx y cache-barrido no montan el mismo volumen con nombre en /var/cache/sede")
    comprueba(barrido.get("image") == nginx.get("image"), "cache-barrido usa la imagen de nginx", "cache-barrido no usa la misma imagen que nginx")
    comprueba(str(barrido.get("user")) == "101:101", "cache-barrido corre como 101:101 (el usuario de nginx)", f"cache-barrido corre como {barrido.get('user')!r}, no 101:101")
    comprueba(barrido.get("network_mode") == "none", "cache-barrido sin red", "cache-barrido debe tener network_mode: none")
    comprueba(barrido.get("read_only") is True, "cache-barrido en solo lectura", "cache-barrido debe ser read_only")
    comprueba(barrido.get("cap_drop") == ["ALL"] and not barrido.get("cap_add"), "cache-barrido sin capacidades", "cache-barrido debe tener cap_drop ALL y ningún cap_add")
    comprueba("no-new-privileges:true" in (barrido.get("security_opt") or []), "cache-barrido con no-new-privileges", "cache-barrido sin no-new-privileges")
    comprueba(not barrido.get("ports"), "cache-barrido no publica puertos", "cache-barrido no debe publicar puertos")
    comprueba((barrido.get("healthcheck") or {}).get("disable") is True, "cache-barrido sin el chequeo de salud heredado de nginx",
              "cache-barrido hereda el HEALTHCHECK de nginx (pide https://127.0.0.1/health sin red) y quedaría unhealthy: healthcheck: disable: true")
    comprueba(barrido.get("restart") == "unless-stopped", "cache-barrido se reinicia solo", "cache-barrido debe tener restart: unless-stopped")
    comprueba(barrido.get("entrypoint") == ["/usr/local/bin/barrido-cache.sh"], "cache-barrido ejecuta barrido-cache.sh", "cache-barrido debe usar entrypoint /usr/local/bin/barrido-cache.sh")
    comprueba(any(v.get("target") == "/var/cache/sede" and v.get("read_only") is not True for v in nginx.get("volumes", [])),
              "nginx escribe en /var/cache/sede", "nginx no puede escribir en /var/cache/sede")

# 7. Script del barrido y Dockerfile.
dockerfile = Path("docker/nginx/Dockerfile").read_text(encoding="utf-8")
comprueba(re.search(r"COPY\s+--chmod=0755\s+docker/nginx/barrido-cache\.sh\s+/usr/local/bin/barrido-cache\.sh", dockerfile) is not None,
          "el Dockerfile copia barrido-cache.sh con permiso de ejecución", "el Dockerfile no copia docker/nginx/barrido-cache.sh a /usr/local/bin/")
script = Path("docker/nginx/barrido-cache.sh").read_text(encoding="utf-8")
comprueba('SEDE_CACHE_EDAD_MIN:-1429' in script and "-mmin +" in script, "el barrido borra por -mmin +1429", "el barrido debe usar -mmin +${SEDE_CACHE_EDAD_MIN:-1429}")
comprueba(re.search(r"trap\s+'exit 0'\s+TERM\s+INT\s+QUIT", script) is not None, "el barrido atiende TERM, INT y QUIT (parada rápida)",
          "el barrido debe atender SIGQUIT (la imagen de nginx lo usa para parar): sin él docker stop tarda 10 s")

print()
if fallos:
    print(f"\033[31mguarda-cache-nginx: {fallos} fallo(s).\033[0m", file=sys.stderr)
    sys.exit(1)
print("\033[32mguarda-cache-nginx: todo en orden.\033[0m")
PY

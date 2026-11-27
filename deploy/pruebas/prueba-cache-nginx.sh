#!/usr/bin/env bash
# T9.7: la caché de nginx para imágenes públicas y documentos de políticas, probada con la
# CONFIGURACIÓN REAL del repo (nginx.conf, conf.d/, snippets/) en la imagen REAL de
# docker/nginx/Dockerfile, con las restricciones de compose.yaml (solo lectura, tmpfs, sin
# capacidades salvo las cuatro del servicio) y un origen FastCGI simulado.
#
# Qué NO prueba: PHP-FPM. Las cabeceras que el origen simula son las medidas en staging; que la
# aplicación las siga emitiendo lo fijan las pruebas de backend (LecturasPublicasSinCookiesTest,
# ImagenContenidoConSesionRealTest). La prueba real completa es la de staging tras desplegar.
#
# Uso:   bash deploy/pruebas/prueba-cache-nginx.sh
# Requiere docker, python3, curl y openssl; usa los puertos 80, 443, 3000 y 9000 del anfitrión.
# SEDE_IMAGEN_NGINX=<imagen> cambia la imagen (por omisión, la línea FROM del Dockerfile).
set -u
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
APOYO="$RAIZ/deploy/pruebas/apoyo"
IMAGEN="${SEDE_IMAGEN_NGINX:-$(awk '/^FROM /{print $2; exit}' "$RAIZ/docker/nginx/Dockerfile")}"
T="$(mktemp -d)"
NOMBRE="sede-prueba-cache-$$"
VOL="$NOMBRE-vol"
U="https://127.0.0.1"
ok=0; mal=0
PIDS=()

limpiar() {
  docker rm -f "$NOMBRE" >/dev/null 2>&1
  docker volume rm -f "$VOL" >/dev/null 2>&1
  for p in "${PIDS[@]:-}"; do [ -n "$p" ] && kill "$p" 2>/dev/null; done
  rm -rf "$T"
}
trap limpiar EXIT

v() { # descripción, obtenido, esperado
  if [ "$2" = "$3" ]; then ok=$((ok + 1)); printf '  \033[32m✓\033[0m %s\n' "$1"
  else mal=$((mal + 1)); printf '  \033[31m✗ %s (esperado «%s», salió «%s»)\033[0m\n' "$1" "$3" "$2" >&2; fi
}

for h in docker python3 curl openssl; do
  command -v "$h" >/dev/null || { echo "Falta $h."; exit 2; }
done

mkdir -p "$T/estado" "$T/certs" "$T/sitio" "$T/conf.d"
# Es la configuración real salvo por una cosa: se quitan las líneas `listen [::]:…`. Un ejecutor sin IPv6
# (el sandbox de desarrollo, algunos runners) rechaza el socket y nginx no arranca; no tiene que ver con la caché.
cp "$RAIZ"/docker/nginx/conf.d/*.conf "$T/conf.d/"
sed -i '/^\s*listen \[::\]/d' "$T/conf.d/"*.conf
openssl req -x509 -newkey rsa:2048 -nodes -days 1 -subj "/CN=prueba" \
  -keyout "$T/certs/sede.key" -out "$T/certs/sede.crt" >/dev/null 2>&1 || { echo "No se pudo crear el certificado."; exit 2; }
chmod 644 "$T/certs/"*

# El origen FastCGI (puerto 9000) y un «sitio» vacío (puerto 3000, solo para que el upstream exista).
python3 "$APOYO/origen-fastcgi.py" "$T/estado" 9000 >/dev/null 2>"$T/origen.log" & ORIGEN_PID=$!; PIDS+=("$ORIGEN_PID")
( cd "$T/sitio" && exec python3 -m http.server 3000 --bind 127.0.0.1 >/dev/null 2>&1 ) & PIDS+=("$!")
sleep 0.5

docker volume create "$VOL" >/dev/null

arrancar() { # $1 = nombre de la cookie que el origen... (no cambia nada: la config es la real)
  docker rm -f "$NOMBRE" >/dev/null 2>&1
  docker run -d --name "$NOMBRE" --network host \
    --add-host app:127.0.0.1 --add-host sitio:127.0.0.1 \
    --read-only --tmpfs /var/cache/nginx:size=64m --tmpfs /var/run:size=8m --tmpfs /tmp:size=16m \
    --security-opt no-new-privileges:true --cap-drop ALL \
    --cap-add NET_BIND_SERVICE --cap-add CHOWN --cap-add SETGID --cap-add SETUID \
    --memory 256m --pids-limit 256 \
    -e SEDE_CSP_MODO=informe \
    -v "$VOL:/var/cache/sede" \
    -v "$T/certs:/etc/nginx/certs:ro" \
    -v "$RAIZ/docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro" \
    -v "$T/conf.d:/etc/nginx/conf.d:ro" \
    -v "$RAIZ/docker/nginx/snippets:/etc/nginx/snippets:ro" \
    -v "$RAIZ/docker/nginx/entrypoint/40-csp-modo.sh:/docker-entrypoint.d/40-csp-modo.sh:ro" \
    "$IMAGEN" >/dev/null 2>&1 || return 1
  for _ in $(seq 1 40); do
    curl -sk -o /dev/null "$U/health" 2>/dev/null && return 0
    sleep 0.3
  done
  return 1
}

# Cada petición espera un poco: el límite de la zona `api` es de 10/s con ráfaga de 20 y esto
# no es una prueba de carga.
pausa() { sleep 0.12; }
codigo() { pausa; curl -sk -o /dev/null -w '%{http_code}' "$@"; }
cuerpo() { pausa; curl -sk "$@" | head -c 8; }
# HTTP/2 entrega los nombres de cabecera en minúsculas.
cabecera() { pausa; curl -sk -D- -o /dev/null "${@:2}" | tr -d '\r' | grep -i "^$1:" | head -1; }
estado_cache() { docker logs "$NOMBRE" 2>/dev/null | grep -F "\"GET $1 " | tail -1 | sed -n 's/.*cache=\([A-Z-]*\).*/\1/p'; }
sec() { printf '%s\n' "$1"; }
fija() { printf '%s' "$2" > "$T/estado/$1"; }
archivos() { docker run --rm --user 101:101 --read-only --network none --cap-drop ALL -v "$VOL:/var/cache/sede" "$IMAGEN" \
  sh -c 'find /var/cache/sede -type f 2>/dev/null | wc -l' 2>/dev/null; }

arrancar || { docker logs "$NOMBRE" 2>&1 | tail -20; echo "nginx no arrancó con la configuración del repo."; exit 1; }
v "nginx arranca con la configuración real y las restricciones de compose" "0" "0"

B=/api/v1/banners/11111111-2222-3333-4444-555555555555/imagen
N=/api/v1/noticias/fiesta-del-mar/imagen
C=/api/v1/contenido/imagenes/aaaaaaaa-2222-3333-4444-555555555555
KB=api_v1_banners_11111111-2222-3333-4444-555555555555_imagen
KN=api_v1_noticias_fiesta-del-mar_imagen

sec "1. Anónimo: guarda y reutiliza"
codigo "$U$B" >/dev/null; v "primera petición MISS" "$(estado_cache $B)" MISS
codigo "$U$B" >/dev/null; v "segunda petición HIT" "$(estado_cache $B)" HIT
v "304 con If-None-Match sobre una entrada guardada" "$(codigo -H 'If-None-Match: "e1"' $U$B)" 304
v "cabeceras de seguridad heredadas siguen en la respuesta cacheada" \
  "$(cabecera strict-transport-security $U$B | grep -ci '^strict-transport-security:')" 1
v "la caché no cambia el Cache-Control que ve el navegador" "$(cabecera cache-control $U$B)" "cache-control: max-age=300, public"

sec "2. Editor (cookie de sesión del panel): nunca se guarda ni se sirve de la caché"
v "el editor ve su borrador" "$(cuerpo -H 'Cookie: sede_consentimiento=1; sede-session=abc' $U$C)" BORRADOR
v "estado BYPASS" "$(estado_cache $C)" BYPASS
v "el anónimo que pide lo mismo después ve la pública" "$(cuerpo $U$C)" PUBLICA

sec "3. Defensa por cabecera del origen (aunque el nombre de la cookie no coincidiera)"
fija sin_set_cookie si
C3=/api/v1/contenido/imagenes/cccccccc-2222-3333-4444-555555555555
# Una cookie con OTRO nombre: la caché no la reconoce como sesión del panel.
cuerpo -H 'Cookie: otra-session=abc' "$U$C3" >/dev/null
v "una respuesta 200 private/no-store del origen no se guarda" "$(cuerpo $U$C3)" PUBLICA
rm -f "$T/estado/sin_set_cookie"

sec "4. Despublicar y luego caer el origen: lo despublicado no vuelve"
codigo "$U$N" >/dev/null; sleep 2
fija "$KN" 404
v "despublicada: 404" "$(codigo $U$N)" 404
v "el 404 reemplaza la entrada guardada" "$(estado_cache $N)" EXPIRED
fija "$KN" 503; sleep 2
v "origen en 503: sigue 404, no resucita" "$(codigo $U$N)" 404
fija "$KN" lento; sleep 2
v "origen colgado: sigue 404, no resucita" "$(codigo $U$N)" 404
rm -f "$T/estado/$KN"; sleep 2
v "republicada: 200" "$(codigo $U$N)" 200

sec "5. Imagen pública guardada y origen caído: sirve la copia"
codigo "$U$B" >/dev/null; sleep 2
fija "$KB" 503
v "origen en 503: 200 con la copia" "$(codigo $U$B)" 200
v "estado STALE" "$(estado_cache $B)" STALE
rm -f "$T/estado/$KB"

sec "6. Direcciones inventadas con 404 no llenan la caché"
a0="$(archivos)"
for i in $(seq 1 20); do
  k="$(printf 'api_v1_banners_%08x-0000-0000-0000-000000000000_imagen' "$i")"
  fija "$k" 404
  codigo "$U/${k//_//}" >/dev/null
done
v "20 UUID inexistentes no añaden archivos" "$(( $(archivos) - a0 ))" 0
rm -f "$T"/estado/api_v1_banners_0*

sec "7. Lo que ya estaba bloqueado sigue bloqueado y lo que no es imagen no se guarda"
v "/api/v1/noticias/.oculto/imagen sigue en 403" "$(codigo $U/api/v1/noticias/.oculto/imagen)" 403
v "/api/v1/noticias/imagen.log sigue en 403" "$(codigo $U/api/v1/noticias/imagen.log)" 403
codigo "$U/api/v1/noticias" >/dev/null
v "/api/v1/noticias (JSON) no pasa por la caché" "$(estado_cache /api/v1/noticias)" "-"

sec "8. Documentos de políticas (B9)"
P=/politicas/tratamiento-datos.pdf; KP=politicas_tratamiento-datos.pdf
codigo "$U$P" >/dev/null; v "primera descarga MISS" "$(estado_cache $P)" MISS
codigo "$U$P" >/dev/null; v "segunda descarga HIT" "$(estado_cache $P)" HIT
v "el nombre del archivo no cambia" "$(cabecera content-disposition $U$P)" 'content-disposition: attachment; filename="tratamiento-datos-v3.pdf"'
v "el Cache-Control hacia el borde no cambia" "$(cabecera cache-control $U$P)" "cache-control: no-cache, private"
v "304 con If-None-Match" "$(codigo -H 'If-None-Match: "p1"' $U$P)" 304
sleep 2; fija "$KP" 404
v "despublicada: 404" "$(codigo $U$P)" 404
fija "$KP" 503; sleep 2
v "origen en 503: sigue 404, no resucita" "$(codigo $U$P)" 404
rm -f "$T/estado/$KP"
fija politicas_pesada.pdf lento
: > "$T/origen.log"
pids=()
for _ in 1 2 3 4; do ( curl -sk -o /dev/null "$U/politicas/pesada.pdf" ) & pids+=("$!"); sleep 0.25; done
wait "${pids[@]}"
v "4 descargas simultáneas en frío generan el documento una sola vez" "$(grep -c pesada "$T/origen.log")" 1
rm -f "$T/estado/politicas_pesada.pdf"

sec "8b. Origen que no acepta conexiones (app parado): la copia sale en pocos segundos"
# En staging, con `app` detenido, el documento tardó 60,0 s en salir como copia vieja: el bloque de
# políticas no fijaba `fastcgi_connect_timeout` y nginx usa 60 s por omisión (medido 2026-11-26).
# Aquí el origen deja de aceptar conexiones (no las rechaza: las deja colgadas, como el contenedor parado).
ms() { echo $(( $(date +%s%N) / 1000000 )); }
codigo "$U$P" >/dev/null; codigo "$U$B" >/dev/null; sleep 2
kill "$ORIGEN_PID" 2>/dev/null; wait "$ORIGEN_PID" 2>/dev/null
python3 "$APOYO/origen-colgado.py" 9000 >"$T/colgado.out" 2>&1 & COLGADO_PID=$!; PIDS+=("$COLGADO_PID")
for _ in $(seq 1 20); do grep -q listo "$T/colgado.out" 2>/dev/null && break; sleep 0.1; done
for recurso in "$P" "$B"; do
  t0="$(ms)"; c="$(curl -sk --max-time 20 -o /dev/null -w '%{http_code}' "$U$recurso")"; t1="$(ms)"
  v "$recurso con el origen colgado: 200 con la copia" "$c" 200
  v "$recurso: la copia sale en menos de 10 s (salió en $((t1 - t0)) ms)" "$([ $((t1 - t0)) -lt 10000 ] && echo si || echo no)" si
  v "$recurso: estado STALE" "$(estado_cache $recurso)" STALE
  sleep 1.2
done

sec "8c. Rutas dinámicas sin copia con el origen colgado: error en pocos segundos, no en 60"
# Con `app` parado, cada petición a estas rutas retenía un worker de nginx 60 s (el valor por omisión de
# `fastcgi_connect_timeout`). Sin copia que servir lo correcto es fallar rápido (504).
for ruta in /health /sanctum/csrf-cookie "/api/v1/noticias?per_page=1" /storage/no-existe; do
  t0="$(ms)"; c="$(curl -sk --max-time 20 -o /dev/null -w '%{http_code}' "$U$ruta")"; t1="$(ms)"
  v "$ruta con el origen colgado: 504 (salió $c)" "$c" 504
  v "$ruta: falla en menos de 10 s (tardó $((t1 - t0)) ms)" "$([ $((t1 - t0)) -lt 10000 ] && echo si || echo no)" si
done
kill "$COLGADO_PID" 2>/dev/null; wait "$COLGADO_PID" 2>/dev/null
python3 "$APOYO/origen-fastcgi.py" "$T/estado" 9000 >/dev/null 2>"$T/origen.log" & ORIGEN_PID=$!; PIDS+=("$ORIGEN_PID")
sleep 0.5

sec "9. Barrido (contenedor aparte: uid 101, sin red, sin capacidades, solo lectura)"
codigo "$U$B" >/dev/null; sleep 2
docker run --rm --user 101:101 --network none --cap-drop ALL -v "$VOL:/var/cache/sede" "$IMAGEN" \
  sh -c 'for f in $(find /var/cache/sede/imagenes -type f); do touch -d "@$(( $(date +%s) - 3600 ))" "$f"; done' >/dev/null 2>&1
fija "$KB" 503
v "antes del barrido, con el origen caído: copia" "$(codigo $U$B)" 200
docker run --rm --user 101:101 --read-only --network none --cap-drop ALL \
  --security-opt no-new-privileges:true --memory 32m --pids-limit 8 \
  -e SEDE_CACHE_EDAD_MIN=30 -e SEDE_CACHE_INTERVALO=1 \
  -v "$VOL:/var/cache/sede" \
  -v "$RAIZ/docker/nginx/barrido-cache.sh:/barrido.sh:ro" \
  --entrypoint timeout "$IMAGEN" 3 /bin/sh /barrido.sh >/dev/null 2>&1
v "tras el barrido, con el origen caído: ya no hay copia vieja (503)" "$(codigo $U$B)" 503
rm -f "$T/estado/$KB"
v "con el origen sano se rellena sola" "$(codigo $U$B)" 200
v "el barrido terminó sin que nginx registrara errores graves" \
  "$(docker logs "$NOMBRE" 2>&1 | grep -cE '\[(crit|alert|emerg)\]')" 0

echo
if [ "$mal" -eq 0 ]; then printf '\033[32mcache-nginx: %d comprobaciones, todas bien.\033[0m\n' "$ok"; exit 0; fi
printf '\033[31mcache-nginx: %d bien, %d mal.\033[0m\n' "$ok" "$mal" >&2
docker logs "$NOMBRE" 2>&1 | grep -E '\[(crit|alert|emerg|error)\]' | grep -v 'index.html' | tail -5 >&2
exit 1

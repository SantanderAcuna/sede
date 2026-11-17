#!/bin/bash
#
# Prueba de `scripts/actualizar-rangos-cloudflare.sh`. No necesita red ni Docker:
#
#   bash deploy/pruebas/prueba-rangos-cloudflare.sh
#
# Los rangos de Cloudflare son una lista de CONFIANZA: nginx toma de `CF-Connecting-IP` la dirección
# del ciudadano sólo si la petición llega desde uno de ellos (`set_real_ip_from`). Una lista
# envenenada —un `0.0.0.0/0`, una página de error de HTML, un archivo truncado— haría que cualquier
# cliente pudiera falsear su dirección y saltarse el límite de tasa, o que nginx dejara de arrancar.
# Por eso el actualizador valida antes de escribir, y esta prueba demuestra cada rechazo.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$RAIZ/scripts/actualizar-rangos-cloudflare.sh"
SNIPPET_REAL="$RAIZ/docker/nginx/snippets/cloudflare-realip.conf"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
FALLOS=0

verde() { printf '  \033[32m✓\033[0m %s\n' "$*"; }
rojo()  { printf '  \033[31m✗ %s\033[0m\n' "$*" >&2; FALLOS=$((FALLOS + 1)); }

[ -f "$SCRIPT" ] || { rojo "Falta $SCRIPT"; exit 1; }

V4_BUENO=$'173.245.48.0/20\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18'
V6_BUENO=$'2400:cb00::/32\n2606:4700::/32\n2803:f800::/32\n2405:b500::/32'
# Como las publica Cloudflare: sin salto de línea final.
printf '%s' "$V4_BUENO" > "$TMP/v4.txt"
printf '%s' "$V6_BUENO" > "$TMP/v6.txt"

plantilla() {
  cat > "$1" <<'CONF'
# cabecera de la prueba
# >>> rangos de Cloudflare (generado por scripts/actualizar-rangos-cloudflare.sh; no editar a mano)
set_real_ip_from 1.2.3.0/24;
# <<< fin de los rangos
real_ip_header CF-Connecting-IP;
real_ip_recursive on;
CONF
}

# Ejecuta el actualizador con archivos locales. $1 v4, $2 v6, $3 snippet, resto = opciones.
correr() {
  local v4="$1" v6="$2" sn="$3"; shift 3
  SEDE_CF_PRUEBA=1 SEDE_CF_V4="file://$v4" SEDE_CF_V6="file://$v6" SEDE_CF_SNIPPET="$sn" bash "$SCRIPT" "$@" 2>&1
}

echo "Actualización con listas válidas"
plantilla "$TMP/a.conf"
salida="$(correr "$TMP/v4.txt" "$TMP/v6.txt" "$TMP/a.conf")"; codigo=$?
[ "$codigo" -eq 0 ] && verde "sale con 0 y actualiza" || rojo "código $codigo: $salida"
n="$(grep -c '^set_real_ip_from ' "$TMP/a.conf")"
[ "$n" -eq 10 ] && verde "escribe los 10 rangos (6 IPv4 + 4 IPv6)" || rojo "escribió $n rangos (esperado 10)"
grep -q '^set_real_ip_from 1\.2\.3\.0/24;' "$TMP/a.conf" && rojo "dejó el rango viejo" || verde "reemplaza lo que había entre las marcas"
grep -q '^real_ip_header CF-Connecting-IP;' "$TMP/a.conf" && grep -q '^# cabecera de la prueba' "$TMP/a.conf" \
  && verde "no toca nada fuera de las marcas" || rojo "tocó lo que está fuera de las marcas"
grep -E '^set_real_ip_from ' "$TMP/a.conf" | grep -vqE '^set_real_ip_from [0-9a-f:./]+;$' && rojo "una línea no tiene la forma esperada" || verde "todas las líneas tienen la forma «set_real_ip_from <rango>;»"

echo "Idempotencia y --comprobar"
antes="$(sha256sum "$TMP/a.conf" | cut -c1-16)"
correr "$TMP/v4.txt" "$TMP/v6.txt" "$TMP/a.conf" >/dev/null
despues="$(sha256sum "$TMP/a.conf" | cut -c1-16)"
[ "$antes" = "$despues" ] && verde "una segunda corrida no cambia el archivo" || rojo "la segunda corrida cambió el archivo"
correr "$TMP/v4.txt" "$TMP/v6.txt" "$TMP/a.conf" --comprobar >/dev/null; codigo=$?
[ "$codigo" -eq 0 ] && verde "--comprobar con lo vigente sale con 0" || rojo "--comprobar con lo vigente salió con $codigo"
plantilla "$TMP/b.conf"
copia="$(sha256sum "$TMP/b.conf" | cut -c1-16)"
correr "$TMP/v4.txt" "$TMP/v6.txt" "$TMP/b.conf" --comprobar >/dev/null; codigo=$?
[ "$codigo" -eq 1 ] && verde "--comprobar con rangos distintos sale con 1" || rojo "--comprobar con rangos distintos salió con $codigo (esperado 1)"
[ "$copia" = "$(sha256sum "$TMP/b.conf" | cut -c1-16)" ] && verde "--comprobar NO modifica el archivo" || rojo "--comprobar modificó el archivo"

echo "Rechazos (el archivo no debe cambiar y el código debe ser 2)"
rechazo() { # $1 = descripción, $2 = contenido v4, $3 = contenido v6
  printf '%s' "$2" > "$TMP/r4.txt"; printf '%s' "$3" > "$TMP/r6.txt"
  plantilla "$TMP/r.conf"; local h; h="$(sha256sum "$TMP/r.conf" | cut -c1-16)"
  correr "$TMP/r4.txt" "$TMP/r6.txt" "$TMP/r.conf" >/dev/null; local c=$?
  if [ "$c" -eq 2 ] && [ "$h" = "$(sha256sum "$TMP/r.conf" | cut -c1-16)" ]; then verde "$1"; else rojo "$1 (código $c, archivo cambiado: $([ "$h" = "$(sha256sum "$TMP/r.conf" | cut -c1-16)" ] && echo no || echo SÍ))"; fi
}
rechazo "rechaza 0.0.0.0/0 (confiar en todo el mundo)" $'0.0.0.0/0\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18' "$V6_BUENO"
rechazo "rechaza ::/0" "$V4_BUENO" $'::/0\n2606:4700::/32\n2803:f800::/32\n2405:b500::/32'
rechazo "rechaza un rango demasiado ancho (10.0.0.0/8 y 8.0.0.0/8)" $'8.0.0.0/8\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18' "$V6_BUENO"
rechazo "rechaza rangos privados (10.0.0.0/16)" $'10.0.0.0/16\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18' "$V6_BUENO"
rechazo "rechaza una página HTML de error" $'<html><body>403 Forbidden</body></html>' "$V6_BUENO"
rechazo "rechaza una lista vacía" "" "$V6_BUENO"
rechazo "rechaza una lista truncada (menos de 5 rangos IPv4)" $'173.245.48.0/20\n103.21.244.0/22' "$V6_BUENO"
rechazo "rechaza IPv6 donde se esperaba IPv4" "$V6_BUENO" "$V6_BUENO"
rechazo "rechaza un rango mal formado (300.1.1.0/24)" $'300.1.1.0/24\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18' "$V6_BUENO"
rechazo "rechaza bits de host (173.245.48.1/20)" $'173.245.48.1/20\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18' "$V6_BUENO"
rechazo "rechaza una inyección de directiva (; en la línea)" $'1.1.1.0/24; real_ip_header X-Forwarded-For\n103.21.244.0/22\n103.22.200.0/22\n103.31.4.0/22\n141.101.64.0/18\n108.162.192.0/18' "$V6_BUENO"

echo "Fuente inaccesible y marcas ausentes"
plantilla "$TMP/c.conf"
SEDE_CF_PRUEBA=1 SEDE_CF_V4="file://$TMP/no-existe.txt" SEDE_CF_V6="file://$TMP/v6.txt" SEDE_CF_SNIPPET="$TMP/c.conf" bash "$SCRIPT" >/dev/null 2>&1; codigo=$?
[ "$codigo" -eq 2 ] && verde "si no se puede leer la fuente sale con 2 (no con «sin cambios»)" || rojo "fuente inaccesible: código $codigo (esperado 2)"
printf 'sin marcas\n' > "$TMP/d.conf"
correr "$TMP/v4.txt" "$TMP/v6.txt" "$TMP/d.conf" >/dev/null; codigo=$?
[ "$codigo" -eq 2 ] && verde "sin las marcas no escribe y sale con 2" || rojo "sin marcas: código $codigo (esperado 2)"
SEDE_CF_V4="file://$TMP/v4.txt" SEDE_CF_V6="file://$TMP/v6.txt" SEDE_CF_SNIPPET="$TMP/c.conf" bash "$SCRIPT" >/dev/null 2>&1; codigo=$?
[ "$codigo" -eq 2 ] && verde "sin SEDE_CF_PRUEBA=1 no acepta file:// (sólo https)" || rojo "aceptó file:// en producción (código $codigo)"

echo "El archivo real del repositorio"
grep -q '^# >>> rangos de Cloudflare' "$SNIPPET_REAL" && grep -q '^# <<< fin de los rangos' "$SNIPPET_REAL" \
  && verde "cloudflare-realip.conf tiene las marcas que el actualizador necesita" || rojo "cloudflare-realip.conf no tiene las marcas"
grep -q 'actualizar-rangos-cloudflare.sh' "$SNIPPET_REAL" && [ -f "$RAIZ/deploy/droplet/actualizar-rangos-cloudflare.sh" ] \
  && rojo "el comentario aún apunta a un guion del servidor que no existe" || verde "el comentario no apunta a nada que no exista"
cp "$SNIPPET_REAL" "$TMP/real.conf"
n="$(grep -c '^set_real_ip_from ' "$SNIPPET_REAL")"
[ "$n" -ge 20 ] && verde "el archivo real declara $n rangos" || rojo "el archivo real declara sólo $n rangos"
grep -E '^set_real_ip_from ' "$SNIPPET_REAL" | grep -qE ' (0\.0\.0\.0|::)/0;' && rojo "el archivo real confía en todo" || verde "el archivo real no confía en todo"

echo
if [ "$FALLOS" -ne 0 ]; then printf '\033[31m%s comprobación(es) fallaron.\033[0m\n' "$FALLOS" >&2; exit 1; fi
printf '\033[32mEl actualizador de rangos valida antes de escribir.\033[0m\n'

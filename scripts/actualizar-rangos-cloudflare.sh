#!/bin/bash
#
# Actualiza los rangos de Cloudflare que nginx toma como CONFIABLES para restaurar la dirección
# real del ciudadano (`docker/nginx/snippets/cloudflare-realip.conf`).
#
#   bash scripts/actualizar-rangos-cloudflare.sh              reescribe el bloque si cambió
#   bash scripts/actualizar-rangos-cloudflare.sh --comprobar  no escribe: sale con 1 si hay diferencias
#
# Salida: 0 = al día (o actualizado) · 1 = (--comprobar) hay diferencias · 2 = no se pudo obtener o
# NO VALIDA la lista, y entonces no se escribe nada. Un 2 nunca debe leerse como «sin cambios».
#
# Por qué existe. Con Cloudflare delante, todas las peticiones llegan desde direcciones de
# Cloudflare y la dirección del ciudadano viene en `CF-Connecting-IP`. nginx sólo debe creerle a esa
# cabecera cuando la petición viene de un rango de Cloudflare (`set_real_ip_from`): con un rango de
# más, cualquier cliente puede falsear su dirección y saltarse el límite de tasa; con uno de menos,
# el límite trata a todos los ciudadanos como uno solo. Cloudflare publica la lista oficial y
# advierte que puede cambiar: https://developers.cloudflare.com/fundamentals/concepts/cloudflare-ip-addresses/
#
# Por qué NO se actualiza en el servidor. Todo lo que nginx lee viaja DENTRO de la imagen (ADR-0019:
# al servidor sólo llegan compose.yaml, la plantilla y los valores). Una lista que se reescribiera
# en el servidor sería estado fuera del repositorio y de la revisión. Aquí el cambio es un cambio de
# código: este guion regenera el archivo, el flujo semanal `rangos-cloudflare.yml` avisa cuando la
# lista oficial difiere, se revisa en un PR y llega con el siguiente despliegue.
#
# Variables (sólo para pruebas): SEDE_CF_V4, SEDE_CF_V6, SEDE_CF_SNIPPET, SEDE_CF_PRUEBA=1 (acepta
# file://; sin ella sólo se admite https).
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
URL_V4="${SEDE_CF_V4:-https://www.cloudflare.com/ips-v4}"
URL_V6="${SEDE_CF_V6:-https://www.cloudflare.com/ips-v6}"
SNIPPET="${SEDE_CF_SNIPPET:-$RAIZ/docker/nginx/snippets/cloudflare-realip.conf}"
COMPROBAR=0
case "${1:-}" in
  "") ;;
  --comprobar) COMPROBAR=1 ;;
  *) echo "Uso: $0 [--comprobar]" >&2; exit 2 ;;
esac

PROTOCOLOS="=https"
[ "${SEDE_CF_PRUEBA:-}" = "1" ] && PROTOCOLOS="=https,file"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

for par in "v4|$URL_V4" "v6|$URL_V6"; do
  nombre="${par%%|*}"; url="${par#*|}"
  # --proto fija el protocolo también tras una redirección; sin --location no se sigue ninguna.
  if ! curl -fsS --max-time 30 --proto "$PROTOCOLOS" -o "$TMP/$nombre.txt" "$url" 2>"$TMP/$nombre.err"; then
    echo "No se pudo obtener $url: $(head -c 200 "$TMP/$nombre.err")" >&2
    exit 2
  fi
done

# Valida y deja el bloque ya formateado. Todo lo que no sea un rango global, bien formado, con
# prefijo razonable y en la cantidad esperada se rechaza: la lista decide en quién confía nginx.
python3 -I - "$TMP/v4.txt" "$TMP/v6.txt" "$TMP/bloque.txt" <<'PY'
import ipaddress
import sys

ruta4, ruta6, salida = sys.argv[1:4]
# Prefijos mínimos: el mayor rango publicado hoy es /13 (IPv4) y /29 (IPv6). Más ancho que eso no
# es Cloudflare, es un error o un envenenamiento.
PREFIJO_MIN = {4: 12, 6: 29}
CANTIDAD = {4: (5, 60), 6: (3, 60)}


def leer(ruta, version):
    with open(ruta, encoding="utf-8", errors="replace") as f:
        lineas = [l.strip() for l in f.read().replace("\r", "").split("\n") if l.strip()]
    rangos = []
    for linea in lineas:
        try:
            red = ipaddress.ip_network(linea, strict=True)
        except ValueError as e:
            sys.exit(f"Línea que no es un rango válido en la lista IPv{version}: {linea[:80]!r} ({e})")
        if red.version != version:
            sys.exit(f"Rango IPv{red.version} en la lista IPv{version}: {linea}")
        if red.prefixlen < PREFIJO_MIN[version]:
            sys.exit(f"Rango demasiado ancho para ser de Cloudflare: {linea}")
        if not red.is_global:
            sys.exit(f"Rango no global (privado, reservado o de bucle): {linea}")
        rangos.append(str(red))
    minimo, maximo = CANTIDAD[version]
    if not minimo <= len(rangos) <= maximo:
        sys.exit(f"La lista IPv{version} trae {len(rangos)} rangos (se esperaban entre {minimo} y {maximo}): "
                 "¿archivo truncado o página de error?")
    if len(set(rangos)) != len(rangos):
        sys.exit(f"La lista IPv{version} repite rangos")
    return rangos


v4 = leer(ruta4, 4)
v6 = leer(ruta6, 6)
with open(salida, "w", encoding="utf-8") as f:
    f.write("# >>> rangos de Cloudflare (generado por scripts/actualizar-rangos-cloudflare.sh; no editar a mano)\n")
    f.write("# Fuente: https://www.cloudflare.com/ips-v4 y https://www.cloudflare.com/ips-v6\n")
    for r in v4 + v6:
        f.write(f"set_real_ip_from {r};\n")
    f.write("# <<< fin de los rangos\n")
PY
if [ $? -ne 0 ]; then
  echo "La lista de Cloudflare no pasó la validación; no se escribe nada." >&2
  exit 2
fi

if [ ! -f "$SNIPPET" ] || ! grep -q '^# >>> rangos de Cloudflare' "$SNIPPET" || ! grep -q '^# <<< fin de los rangos' "$SNIPPET"; then
  echo "$SNIPPET no tiene las marcas «# >>> rangos de Cloudflare» y «# <<< fin de los rangos»." >&2
  exit 2
fi

python3 -I - "$SNIPPET" "$TMP/bloque.txt" "$TMP/nuevo.conf" <<'PY'
import sys

snippet, bloque, salida = sys.argv[1:4]
with open(snippet, encoding="utf-8") as f:
    original = f.read()
with open(bloque, encoding="utf-8") as f:
    nuevo = f.read()
inicio = original.index("# >>> rangos de Cloudflare")
fin = original.index("# <<< fin de los rangos") + len("# <<< fin de los rangos\n")
with open(salida, "w", encoding="utf-8") as f:
    f.write(original[:inicio] + nuevo + original[fin:])
PY

if cmp -s "$SNIPPET" "$TMP/nuevo.conf"; then
  echo "Los rangos de Cloudflare están al día ($(grep -c '^set_real_ip_from ' "$SNIPPET") rangos)."
  exit 0
fi

if [ "$COMPROBAR" -eq 1 ]; then
  echo "Los rangos de Cloudflare CAMBIARON. Diferencias (archivo actual → lista oficial):"
  diff <(grep '^set_real_ip_from ' "$SNIPPET") <(grep '^set_real_ip_from ' "$TMP/nuevo.conf") | grep '^[<>]' | sed 's/^</  - /; s/^>/  + /'
  echo "Para aplicarlo: bash scripts/actualizar-rangos-cloudflare.sh   (y revisar el PR)"
  exit 1
fi

# Se escribe sobre una copia en el mismo directorio y se mueve: nginx nunca lee un archivo a medias.
cp "$TMP/nuevo.conf" "$SNIPPET.nuevo" && mv -f "$SNIPPET.nuevo" "$SNIPPET"
echo "Rangos de Cloudflare actualizados ($(grep -c '^set_real_ip_from ' "$SNIPPET") rangos)."
exit 0

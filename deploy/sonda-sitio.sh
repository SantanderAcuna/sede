#!/usr/bin/env bash
#
# Sonda de disponibilidad de la sede desde FUERA (DISP-01). La lanza
# `.github/workflows/sonda.yml`; también sirve a mano:
#
#   bash deploy/sonda-sitio.sh https://staging.santamarta.gov.co
#
# Qué comprueba, y qué NO:
#   /                          el sitio responde 200
#   /health                    la API responde 200 a través de nginx
#   /api/v1/tramites?per_page=1  200 y SIN la cabecera `X-Sede-Respaldo`. Esa cabecera la pone
#                              el sitio cuando sirve una copia porque la API falló (ADR 0009):
#                              el sitio sigue «arriba» pero degradado, y desde fuera es la
#                              única señal de que la API no está sana.
#   NO ve la memoria de Redis, `evicted_keys` ni `ACL LOG` (viven dentro del servidor): esas
#   tres señales del ADR 0009 siguen siendo una comprobación manual.
#
# Un fallo aislado no es una alarma: un despliegue recrea los contenedores y deja la sede
# inalcanzable unos segundos. Cada ruta se reintenta INTENTOS veces con ESPERA entre intentos y
# solo cuenta el último resultado.
#
# Variables (todas opcionales): INTENTOS (3), ESPERA en segundos (30), TOPE de cada petición en
# segundos (15), INFORME (ruta de un archivo Markdown), VERIFICADOR_TOKEN (si el borde desafía a
# los ejecutores de GitHub; viaja como cabecera X-Verificador-Vinculos, igual que la
# verificación de vínculos, y nunca se imprime).
#
# Salida: 0 todo sano; 1 alguna ruta caída o degradada.
set -uo pipefail

BASE="${1:?Falta la dirección base, p. ej. https://staging.santamarta.gov.co}"
BASE="${BASE%/}"
INTENTOS="${INTENTOS:-3}"
ESPERA="${ESPERA:-30}"
TOPE="${TOPE:-15}"
INFORME="${INFORME:-}"

RUTAS=("/health" "/" "/api/v1/tramites?per_page=1")
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT

# Una petición. Escribe «codigo segundos degradado» en $T/res. El token entra por la
# configuración de curl (-K -), no como argumento: un argumento aparece en la lista de procesos.
pedir() {
  local ruta="$1" codigo seg
  local conf='silent
output = "/dev/null"'
  [ -n "${VERIFICADOR_TOKEN:-}" ] && conf+=$'\n'"header = \"X-Verificador-Vinculos: ${VERIFICADOR_TOKEN}\""
  read -r codigo seg < <(printf '%s\n' "$conf" | curl -K - --max-time "$TOPE" -D "$T/cab" \
      -w '%{http_code} %{time_total}\n' "${BASE}${ruta}" 2>/dev/null || true)
  [ -n "${codigo:-}" ] || codigo=000
  local degradado=0
  grep -qi '^x-sede-respaldo:' "$T/cab" 2>/dev/null && degradado=1
  rm -f "$T/cab"
  printf '%s %s %s' "$codigo" "${seg:-0}" "$degradado"
}

malas=0
filas=""
for ruta in "${RUTAS[@]}"; do
  estado="FALLA"; detalle=""
  for ((i=1; i<=INTENTOS; i++)); do
    read -r codigo seg degradado <<<"$(pedir "$ruta")"
    if [ "$codigo" = "200" ] && [ "$degradado" = "0" ]; then estado="OK"; detalle="${seg} s"; break; fi
    if [ "$codigo" = "200" ]; then
      estado="DEGRADADO"; detalle="200 con X-Sede-Respaldo: la API falló y el sitio sirve una copia"
    elif [ "$codigo" = "000" ]; then
      estado="FALLA"; detalle="000, sin respuesta en ${TOPE} s o sin conexión"
    else
      estado="FALLA"; detalle="HTTP ${codigo}"
    fi
    [ "$i" -lt "$INTENTOS" ] && sleep "$ESPERA"
  done
  [ "$estado" = "OK" ] || malas=$((malas+1))
  echo "${estado}  ${ruta}  ${detalle}"
  filas+="| ${ruta} | ${detalle} | ${estado} |"$'\n'
done

if [ -n "$INFORME" ]; then
  {
    echo "## Sonda de ${BASE}"
    echo
    echo "Intentos por ruta: ${INTENTOS}, con ${ESPERA} s entre uno y otro; tope de cada petición: ${TOPE} s."
    echo
    echo "| Ruta | Resultado | Estado |"
    echo "|---|---|---|"
    printf '%s' "$filas"
  } > "$INFORME"
fi

[ "$malas" = 0 ] && exit 0
exit 1

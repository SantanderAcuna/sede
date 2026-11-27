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
#   /api/v1/tramites?per_page=1  200: la API llega a PHP y a la base por la ruta pública
#   NO ve si el sitio está sirviendo COPIAS por una API caída. La cabecera `X-Sede-Respaldo`
#   (ADR 0009) la pone la ruta interna del sitio al renderizar; las peticiones de un visitante a
#   `/api/v1` no pasan por ella (nginx las manda directo a PHP), así que desde fuera no puede
#   verse. Si la API cae, esta sonda lo ve por el 5xx de `/api/v1` y `/health`, no por una cabecera.
#   NO ve la memoria de Redis, `evicted_keys` ni `ACL LOG` (viven dentro del servidor): las vigila
#   `php artisan monitoreo:redis` en el servidor.
#
# Desafío del borde. Cloudflare puede responder 403 con `cf-mitigated: challenge` a los ejecutores de
# GitHub. Eso NO prueba que la sede esté caída ni que esté sana: es «no verificable» y sale con 2,
# sin abrir incidencia. Un 403 sin esa cabecera sí es un fallo.
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
# Salida: 0 todo sano; 1 alguna ruta caída (manda sobre el 2); 2 sin caídas, pero el borde desafió
# a la sonda en alguna ruta (no verificable).
set -uo pipefail

BASE="${1:?Falta la dirección base, p. ej. https://staging.santamarta.gov.co}"
BASE="${BASE%/}"
INTENTOS="${INTENTOS:-3}"
ESPERA="${ESPERA:-30}"
TOPE="${TOPE:-15}"
INFORME="${INFORME:-}"

RUTAS=("/health" "/" "/api/v1/tramites?per_page=1")
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT

# Una petición. Devuelve «codigo segundos desafio» (desafio=1 si el borde la desafió). El token entra por la
# configuración de curl (-K -), no como argumento: un argumento aparece en la lista de procesos.
pedir() {
  local ruta="$1" codigo seg
  local conf='silent
output = "/dev/null"'
  [ -n "${VERIFICADOR_TOKEN:-}" ] && conf+=$'\n'"header = \"X-Verificador-Vinculos: ${VERIFICADOR_TOKEN}\""
  read -r codigo seg < <(printf '%s\n' "$conf" | curl -K - --max-time "$TOPE" -D "$T/cab" \
      -w '%{http_code} %{time_total}\n' "${BASE}${ruta}" 2>/dev/null || true)
  [ -n "${codigo:-}" ] || codigo=000
  local desafio=0
  grep -qiE '^cf-mitigated:[[:space:]]*challenge' "$T/cab" 2>/dev/null && desafio=1
  rm -f "$T/cab"
  printf '%s %s %s' "$codigo" "${seg:-0}" "$desafio"
}

malas=0; sinverificar=0
filas=""
for ruta in "${RUTAS[@]}"; do
  estado="FALLA"; detalle=""
  for ((i=1; i<=INTENTOS; i++)); do
    read -r codigo seg desafio <<<"$(pedir "$ruta")"
    if [ "$codigo" = "200" ]; then estado="OK"; detalle="${seg} s"; break; fi
    if [ "$desafio" = "1" ]; then
      # Reintentar no cambia nada: el borde volverá a desafiar. No es una caída.
      estado="NO VERIFICABLE"; detalle="HTTP ${codigo} con desafío del borde (cf-mitigated): no prueba que la sede esté caída"
      break
    elif [ "$codigo" = "000" ]; then
      estado="FALLA"; detalle="000, sin respuesta en ${TOPE} s o sin conexión"
    else
      estado="FALLA"; detalle="HTTP ${codigo}"
    fi
    [ "$i" -lt "$INTENTOS" ] && sleep "$ESPERA"
  done
  case "$estado" in
    OK) ;;
    "NO VERIFICABLE") sinverificar=$((sinverificar+1)) ;;
    *) malas=$((malas+1)) ;;
  esac
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

[ "$malas" -gt 0 ] && exit 1
[ "$sinverificar" -gt 0 ] && exit 2
exit 0

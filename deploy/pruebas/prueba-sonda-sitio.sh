#!/usr/bin/env bash
#
# deploy/sonda-sitio.sh contra un servidor simulado (DISP-01). Casos:
#   1. todo sano                                   -> 0
#   2. una ruta responde 503 siempre               -> 1 y nombra la ruta
#   3. fallo transitorio que se recupera           -> 0 (un despliegue no despierta a nadie)
#   4. el borde desafía a la sonda (403 + cf-mitigated) -> 2 («no verificable»): NO es una caída
#   4b. desafío en una ruta y caída real en otra   -> 1 (la caída manda)
#   4c. 403 SIN la cabecera del desafío            -> 1 (un 403 cualquiera sí es un fallo)
#   4d. X-Sede-Respaldo en /api/v1 no cambia el resultado: esa cabecera sólo existe en la ruta
#       interna del sitio; nginx manda /api/v1 directo a PHP, así que desde fuera no puede verse
#   5. respuesta más lenta que el tope             -> 1
#   6. nada escucha en el puerto                   -> 1
#   7. el informe nombra cada ruta y su resultado
#   8. el token opcional viaja como cabecera y no se imprime
set -uo pipefail
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
T="$(mktemp -d)"; PID=""
trap '[ -n "$PID" ] && kill "$PID" 2>/dev/null; rm -rf "$T"' EXIT
fallos=0
ok()  { echo "  ok:   $1"; }
mal() { echo "  FALLA: $1"; fallos=$((fallos+1)); }

SANO='{"/health":{},"/":{},"/api/v1/tramites?per_page=1":{}}'
PUERTO=$((20000 + RANDOM % 20000))

arrancar() {  # $1 = JSON de configuración
  [ -n "$PID" ] && { kill "$PID" 2>/dev/null; wait "$PID" 2>/dev/null; PID=""; }
  printf '%s' "$1" > "$T/c.json"
  python3 "$RAIZ/deploy/pruebas/apoyo/servidor-sonda.py" "$PUERTO" "$T/c.json" > "$T/s.out" &
  PID=$!
  for _ in $(seq 50); do grep -q listo "$T/s.out" 2>/dev/null && return; sleep 0.1; done
  echo "el servidor simulado no arrancó"; exit 2
}
sonda() {  # variables ajustadas para que la prueba sea rápida
  INTENTOS="${INTENTOS:-3}" ESPERA="${ESPERA:-0.2}" TOPE="${TOPE:-2}" INFORME="$T/informe.md" \
    bash "$RAIZ/deploy/sonda-sitio.sh" "http://127.0.0.1:$PUERTO" > "$T/salida.txt" 2>&1
}

echo "1. todo sano"
arrancar "$SANO"; sonda; [ $? = 0 ] && ok "sale con 0" || { mal "salió distinto de 0"; cat "$T/salida.txt"; }

echo "2. una ruta caída"
arrancar '{"/health":{},"/":{"estado":503},"/api/v1/tramites?per_page=1":{}}'; sonda; c=$?
[ $c = 1 ] && ok "sale con 1" || mal "salió con $c"
grep -q '^| / |.*503' "$T/informe.md" && ok "el informe nombra la ruta y el 503" || mal "el informe no nombra la ruta caída"

echo "3. fallo transitorio (2 de 3 intentos fallan, el tercero responde)"
arrancar '{"/health":{"fallar_primeras":2},"/":{},"/api/v1/tramites?per_page=1":{}}'; sonda; [ $? = 0 ] && ok "se recupera: sale con 0" || mal "un fallo transitorio disparó la alarma"

echo "4. el borde desafía a la sonda"
DESAF='{"estado":403,"cabeceras":{"cf-mitigated":"challenge"}}'
arrancar "{\"/health\":$DESAF,\"/\":$DESAF,\"/api/v1/tramites?per_page=1\":$DESAF}"; sonda; c=$?
[ $c = 2 ] && ok "sale con 2 (no verificable)" || mal "salió con $c"
grep -qi "no verificable" "$T/informe.md" && ok "el informe dice «no verificable»" || mal "el informe no dice «no verificable»"
grep -q "| FALLA |" "$T/informe.md" && mal "el desafío se contó como FALLA" || ok "no se cuenta como FALLA"

echo "4b. desafío en una ruta y caída real en otra"
arrancar "{\"/health\":{\"estado\":503},\"/\":$DESAF,\"/api/v1/tramites?per_page=1\":{}}"; sonda; c=$?
[ $c = 1 ] && ok "sale con 1" || mal "salió con $c"

echo "4c. 403 sin la cabecera del desafío"
arrancar '{"/health":{},"/":{"estado":403},"/api/v1/tramites?per_page=1":{}}'; sonda; c=$?
[ $c = 1 ] && ok "sale con 1" || mal "salió con $c"

echo "4d. X-Sede-Respaldo en /api/v1 no cambia el resultado"
arrancar '{"/health":{},"/":{},"/api/v1/tramites?per_page=1":{"cabeceras":{"X-Sede-Respaldo":"412"}}}'; sonda; c=$?
[ $c = 0 ] && ok "sale con 0: la sonda no finge ver lo que no puede ver" || mal "salió con $c"

echo "5. respuesta más lenta que el tope"
arrancar '{"/health":{"demora":3},"/":{},"/api/v1/tramites?per_page=1":{}}'; TOPE=1 INTENTOS=2 sonda; [ $? = 1 ] && ok "sale con 1" || mal "no detectó la lentitud"

echo "6. nada escucha"
[ -n "$PID" ] && { kill "$PID" 2>/dev/null; wait "$PID" 2>/dev/null; PID=""; }
sonda; [ $? = 1 ] && ok "sale con 1" || mal "no detectó el puerto cerrado"

echo "7. informe con todas las rutas"
arrancar "$SANO"; sonda
for r in "/health" "/api/v1/tramites?per_page=1"; do grep -qF "$r" "$T/informe.md" && ok "informe incluye $r" || mal "informe sin $r"; done

echo "8. token opcional"
TOKEN_PRUEBA="t-$RANDOM-$RANDOM-$RANDOM"   # se genera en cada corrida: no hay literal que un escáner tome por credencial
arrancar "{\"/health\":{\"exigir\":{\"X-Verificador-Vinculos\":\"$TOKEN_PRUEBA\"}},\"/\":{},\"/api/v1/tramites?per_page=1\":{}}"
sonda; [ $? = 1 ] && ok "sin token, la ruta que lo exige falla" || mal "sin token no debía pasar"
VERIFICADOR_TOKEN="$TOKEN_PRUEBA" sonda; [ $? = 0 ] && ok "con token, la cabecera viaja y pasa" || mal "con token no pasó"
grep -q "$TOKEN_PRUEBA" "$T/salida.txt" "$T/informe.md" && mal "el token apareció en la salida" || ok "el token no se imprime"

echo
[ "$fallos" = 0 ] && { echo "TODO EN ORDEN"; exit 0; } || { echo "$fallos FALLA(S)"; exit 1; }

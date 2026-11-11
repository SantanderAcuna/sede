#!/usr/bin/env bash
# Prueba de scripts/reintentar.sh: reintenta lo que falla, se detiene en lo que
# funciona y no esconde un fallo permanente. Sin red ni sudo: usa comandos falsos.
#
# Origen: el CI de #532 falló 4 veces seguidas con
# «Temporary failure resolving 'azure.archive.ubuntu.com'» en los pasos con `apt`.
set -u
R="$(cd "$(dirname "$0")/../.." && pwd)/scripts/reintentar.sh"
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
fallos=0
ok() { echo "  ✓ $1"; }
mal() { echo "  ✗ $1"; fallos=$((fallos + 1)); }

[ -f "$R" ] || { echo "  ✗ no existe $R"; exit 1; }

# Falla N veces y luego funciona; cuenta las llamadas en un archivo.
cat > "$T/falla-n.sh" <<'SH'
#!/usr/bin/env bash
c="$(cat "$1" 2>/dev/null || echo 0)"; c=$((c + 1)); echo "$c" > "$1"
[ "$c" -gt "$2" ]
SH
chmod +x "$T/falla-n.sh"

echo "reintentar.sh"

echo 0 > "$T/c1"; PAUSA=0 bash "$R" 3 "$T/falla-n.sh" "$T/c1" 0 >/dev/null 2>&1
[ $? -eq 0 ] && [ "$(cat "$T/c1")" = 1 ] && ok "si funciona a la primera, no repite (1 llamada)" || mal "primera: llamadas=$(cat "$T/c1")"

echo 0 > "$T/c2"; PAUSA=0 bash "$R" 4 "$T/falla-n.sh" "$T/c2" 2 >/dev/null 2>&1
[ $? -eq 0 ] && [ "$(cat "$T/c2")" = 3 ] && ok "falla 2 veces y funciona a la tercera (3 llamadas, éxito)" || mal "tercera: llamadas=$(cat "$T/c2")"

echo 0 > "$T/c3"; PAUSA=0 bash "$R" 3 "$T/falla-n.sh" "$T/c3" 99 >/dev/null 2>&1
codigo=$?
[ "$codigo" -ne 0 ] && [ "$(cat "$T/c3")" = 3 ] && ok "un fallo permanente se intenta 3 veces y SALE con error (no se esconde)" || mal "permanente: código=$codigo llamadas=$(cat "$T/c3")"

PAUSA=0 bash "$R" 2 bash -c 'exit 7' >/dev/null 2>&1
[ $? -eq 7 ] && ok "conserva el código de salida del último intento (7)" || mal "código de salida distinto de 7"

PAUSA=0 bash "$R" 2 bash -c 'echo "con espacios y \"comillas\""' 2>&1 | grep -q 'con espacios y "comillas"' \
  && ok "pasa los argumentos intactos" || mal "los argumentos se alteraron"

bash "$R" >/dev/null 2>&1; [ $? -eq 2 ] && ok "sin argumentos: error de uso (2)" || mal "sin argumentos no dio 2"

[ "$fallos" -eq 0 ] && echo "reintentar.sh: todo en orden." || { echo "reintentar.sh: $fallos fallo(s)."; exit 1; }

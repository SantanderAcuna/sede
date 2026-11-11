#!/usr/bin/env bash
# Uso: reintentar.sh <intentos> <comando> [argumentos…]
#
# Ejecuta el comando hasta `intentos` veces y sale con el código del último. Para
# fallos TRANSITORIOS del ejecutor (DNS del espejo de apt, descargas cortadas):
# un fallo permanente se intenta todas las veces y termina en error, no se esconde.
# `PAUSA` (segundos entre intentos, por defecto 20) se puede poner a 0 en pruebas.
set -u
if [ "$#" -lt 2 ]; then
  echo "Uso: $0 <intentos> <comando> [argumentos…]" >&2
  exit 2
fi
intentos="$1"; shift
pausa="${PAUSA:-20}"
codigo=1
for ((i = 1; i <= intentos; i++)); do
  "$@"; codigo=$?
  [ "$codigo" -eq 0 ] && exit 0
  echo "::warning::intento $i/$intentos falló (código $codigo): $*" >&2
  [ "$i" -lt "$intentos" ] && sleep "$pausa"
done
exit "$codigo"

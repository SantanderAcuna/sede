#!/bin/sh
# Barrido de la caché de nginx (T9.7, ADR-0010). Corre en el servicio `cache-barrido` de compose.yaml:
# mismo volumen que nginx, usuario 101 (el de nginx), sin red, sin capacidades y en solo lectura.
#
# Por qué existe: `inactive` de nginx NO es un tope de edad (mide el tiempo sin uso: una entrada
# que se sigue pidiendo mientras PHP está caído nunca caduca). Este barrido pone el tope: nada con
# más de 24 h sin renovarse se conserva, así que la copia vieja que nginx sirve ante una caída de
# PHP no pasa de 24 h (D-0C.4). Con PHP sano cada renovación reescribe el archivo y su fecha.
#
# `-mmin +N` borra desde N+1 minutos (medido en busybox): 1429 deja la edad máxima por debajo de 24 h.
# Va dentro de un contenedor aparte porque nginx corre sin DAC_OVERRIDE y sus carpetas son 0700: root
# no entra, el usuario 101 sí.
#
# Variables (solo las usa la prueba): SEDE_CACHE_DIR, SEDE_CACHE_EDAD_MIN, SEDE_CACHE_INTERVALO.
set -u
DIR="${SEDE_CACHE_DIR:-/var/cache/sede}"
EDAD_MIN="${SEDE_CACHE_EDAD_MIN:-1429}"
INTERVALO="${SEDE_CACHE_INTERVALO:-600}"

# La imagen de nginx para con SIGQUIT (STOPSIGNAL). Sin atenderlo, `docker stop` espera 10 s y mata con 137.
trap 'exit 0' TERM INT QUIT

echo "barrido: $DIR, más de $EDAD_MIN min, cada $INTERVALO s"
while :; do
  n=$(find "$DIR" -type f -mmin +"$EDAD_MIN" -print -delete 2>/dev/null | wc -l)
  [ "$n" -gt 0 ] && echo "barrido: $n archivo(s) con más de $EDAD_MIN min"
  sleep "$INTERVALO" &
  wait $!
done

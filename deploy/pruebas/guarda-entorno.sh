#!/bin/bash
#
# Guarda del entorno del despliegue (ADR-0019).
#
#   bash deploy/pruebas/guarda-entorno.sh
#
# Comprueba, SIN desplegar nada, que las cuatro piezas del entorno siguen encajando:
# la plantilla, los valores por entorno, la lista de nombres de secretos y el guion del
# servidor. Son cuatro archivos que viven en sitios distintos y que sólo se usan juntos
# en el servidor, una vez cada despliegue: sin esta guarda, un desencaje entre ellos se
# descubre allí, con el despliegue a medias. Ya pasó una vez, con el `compose.yaml`.
#
# Lo que NO comprueba: que los VALORES sean los correctos. Eso no es comprobable aquí;
# lo que sí es comprobable es que cada variable tenga un proveedor declarado y que
# ningún secreto pueda venir de un archivo que viaja dentro de una imagen.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
GUION="$RAIZ/deploy/droplet/despliegue-remoto.sh"
PLANTILLA="$RAIZ/deploy/plantilla.env"
LISTA="$RAIZ/deploy/secretos-requeridos.txt"
DOCKERFILE="$RAIZ/docker/app/Dockerfile"

fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

for f in "$GUION" "$PLANTILLA" "$LISTA" "$DOCKERFILE"; do
  [ -f "$f" ] || { printf '\033[31mFalta %s\033[0m\n' "$f" >&2; exit 1; }
done

# --- 1. Las dos listas de nombres de secretos coinciden -----------------------
# El guion del servidor lleva la lista escrita porque es el ancla de confianza del
# anfitrión y no puede leerla de una imagen. Una lista escrita dos veces se desincroniza
# sola, así que se comprueba.
# El patrón del nombre es parte de la comprobación: sin él, la propia explicación del
# formato que el archivo lleva en sus comentarios («obligatorio  tiene que estar…»)
# entraba en la lista como si fuera un secreto llamado «#».
lista_archivo_oblig="$(awk '$1 ~ /^[A-Z][A-Z0-9_]*$/ && $2 == "obligatorio" {print $1}' "$LISTA" | sort -u | tr '\n' ' ')"
lista_archivo_opc="$(awk '$1 ~ /^[A-Z][A-Z0-9_]*$/ && $2 == "opcional" {print $1}' "$LISTA" | sort -u | tr '\n' ' ')"
lista_guion_oblig="$(sed -n 's/^SECRETOS_OBLIGATORIOS="\(.*\)"$/\1/p' "$GUION" | tr ' ' '\n' | sort -u | tr '\n' ' ')"
lista_guion_opc="$(awk '/^SECRETOS_RECONOCIDOS="/{d=1} d{print} d && /"$/ && !/^SECRETOS_RECONOCIDOS="$/{exit}' "$GUION" \
  | tr -d '"' | sed 's/^SECRETOS_RECONOCIDOS=//' | tr ' \n' '\n\n' | grep -v '^$' | sort -u | tr '\n' ' ')"

if [ "$lista_archivo_oblig" = "$lista_guion_oblig" ]; then
  ok "los secretos obligatorios coinciden entre la lista y el guion del servidor"
else
  mal "los secretos OBLIGATORIOS no coinciden:"
  printf '      secretos-requeridos.txt: %s\n      despliegue-remoto.sh:    %s\n' "$lista_archivo_oblig" "$lista_guion_oblig" >&2
fi
if [ "$lista_archivo_opc" = "$lista_guion_opc" ]; then
  ok "los secretos opcionales coinciden entre la lista y el guion del servidor"
else
  mal "los secretos OPCIONALES no coinciden:"
  printf '      secretos-requeridos.txt: %s\n      despliegue-remoto.sh:    %s\n' "$lista_archivo_opc" "$lista_guion_opc" >&2
fi

TODOS_SECRETOS="$lista_archivo_oblig $lista_archivo_opc"

# --- 2. Cada variable de la plantilla tiene un proveedor ----------------------
# `ENTORNO` y `VERSION` las pone el guion del servidor: son lo único que ningún archivo
# puede saber.
variables="$(grep -oE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$PLANTILLA" | tr -d '${}' | sort -u)"

for entorno in staging produccion; do
  valores="$RAIZ/deploy/valores-$entorno.env"
  [ -f "$valores" ] || { mal "falta deploy/valores-$entorno.env"; continue; }

  claves="$(grep -oE '^[A-Z][A-Z0-9_]*=' "$valores" | tr -d '=' | sort -u) ENTORNO VERSION $TODOS_SECRETOS"
  faltan=""
  for v in $variables; do
    printf '%s\n' $claves | grep -qxF "$v" || faltan="$faltan $v"
  done

  # El dominio de producción sigue sin decidirse (§21.A). Es una ausencia DECLARADA: el
  # despliegue a producción se detiene por ella, y así tiene que ser hasta que se
  # decida. Lo que esta guarda no acepta es que falte cualquier OTRA cosa.
  if [ "$entorno" = "produccion" ]; then
    faltan="$(printf '%s\n' $faltan | grep -vxE 'DOMINIO|ORIGENES_PERMITIDOS' | tr '\n' ' ')"
    pendientes="$(grep -cE '^(DOMINIO|ORIGENES_PERMITIDOS)=' "$valores")"
    [ "$pendientes" = "2" ] && ok "producción ya declara su dominio" \
      || ok "producción sigue sin dominio decidido (§21.A): su despliegue se detendrá por eso"
  fi

  if [ -z "${faltan// /}" ]; then
    ok "todas las variables de la plantilla tienen proveedor en $entorno"
  else
    mal "en $entorno no hay quien provea:$faltan"
    printf '      Un secreto va en deploy/secretos-requeridos.txt + el servidor;\n' >&2
    printf '      cualquier otra variable, en deploy/valores-%s.env.\n' "$entorno" >&2
  fi

  # --- 3. Ningún secreto en un archivo que viaja en la imagen -----------------
  intrusos=""
  for s in $TODOS_SECRETOS; do
    grep -qE "^$s=" "$valores" && intrusos="$intrusos $s"
  done
  if [ -z "${intrusos// /}" ]; then
    ok "deploy/valores-$entorno.env no declara ningún secreto"
  else
    mal "deploy/valores-$entorno.env declara secretos:$intrusos"
    printf '      Ese archivo acaba DENTRO de una imagen publicada.\n' >&2
  fi

  # --- 4. Contrato e identidad de la pila -------------------------------------
  contrato_valores="$(sed -n 's/^CONTRATO_ENTORNO=//p' "$valores")"
  contrato_guion="$(sed -n 's/^CONTRATO_ENTORNO_SOPORTADO=//p' "$GUION")"
  [ -n "$contrato_guion" ] && [ "$contrato_valores" = "$contrato_guion" ] \
    && ok "el contrato del entorno coincide en $entorno (versión $contrato_guion)" \
    || mal "contrato distinto en $entorno: valores='$contrato_valores' guion='$contrato_guion'"

  registro_valores="$(sed -n 's/^REGISTRY=//p' "$valores")"
  registro_guion="$(sed -n 's/^REGISTRO_CONFIABLE="\(.*\)"$/\1/p' "$GUION")"
  [ -n "$registro_guion" ] && [ "$registro_valores" = "$registro_guion" ] \
    && ok "el registro de imágenes coincide en $entorno" \
    || mal "registro distinto en $entorno: valores='$registro_valores' guion='$registro_guion'"
done

# --- 5. El entorno viaja de verdad dentro de la imagen ------------------------
# Sin este COPY, el guion del servidor no encuentra la configuración, cae al modo
# «imagen sin entorno» y despliega con la configuración vieja: exactamente el fallo
# que este mecanismo viene a cerrar, y en silencio.
ruta_guion="$(sed -n 's/^RUTA_ENTORNO_EN_IMAGEN="\(.*\)"$/\1/p' "$GUION")"
if [ -z "$ruta_guion" ]; then
  mal "no se pudo leer RUTA_ENTORNO_EN_IMAGEN del guion del servidor"
else
  faltan_copias=""
  for archivo in compose.yaml plantilla.env valores-staging.env valores-produccion.env; do
    grep -qE "(^|[ /])$archivo( |\\\\|$)" "$DOCKERFILE" || faltan_copias="$faltan_copias $archivo"
  done
  grep -q "$ruta_guion" "$DOCKERFILE" || faltan_copias="$faltan_copias (la ruta $ruta_guion)"
  if [ -z "${faltan_copias// /}" ]; then
    ok "la imagen de la aplicación copia el entorno a $ruta_guion"
  else
    mal "docker/app/Dockerfile no copia:$faltan_copias"
  fi
fi

echo
if [ "$fallos" -eq 0 ]; then
  printf '\033[32mEl entorno del despliegue encaja.\033[0m\n'
  exit 0
fi
printf '\033[31m%s comprobaciones del entorno fallan.\033[0m\n' "$fallos" >&2
exit 1

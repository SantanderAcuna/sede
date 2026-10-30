#!/bin/bash
#
# Despliega una versión y vuelve atrás solo si algo falla.
#
#   desplegar.sh <entorno> <version> [--volver]
#
# Entornos: staging | produccion
# Versión:  el identificador del commit; es también la etiqueta de la imagen.
#
# La vuelta atrás NO es un adorno: la etiqueta de cada versión es el commit, así
# que volver atrás es desplegar otra vez la etiqueta anterior, que sigue en el
# registro. Eso es lo que convierte un despliegue fallido en un incidente de un
# minuto en lugar de una tarde.
#
# Este es el ÚNICO comando que la llave de despliegue puede ejecutar.
#
# Desde el ADR-0019 este guion TAMBIÉN resuelve el entorno: extrae `compose.yaml`,
# `plantilla.env` y los valores de la imagen de la versión que se despliega y los
# renderiza con los secretos que viven en este servidor. Ver el bloque «DE DÓNDE SALE
# EL ENTORNO», más abajo, para el reparto de confianza y por qué este guion no se
# autoactualiza.
#
# Se instala como `/usr/local/bin/desplegar.sh` y se invoca desde la canalización
# así:
#
#   ssh -i <llave> deploy@<servidor> "desplegar.sh staging <confirmación>"
#
# Ojo con ese detalle: la restricción de la llave lo declara como comando forzado
# **sin argumentos**, de modo que sshd ejecuta el guion vacío y entrega lo que
# escribió el cliente en `SSH_ORIGINAL_COMMAND`. Por eso el guion lo lee más
# abajo. Sin eso, se ejecutaría sin entorno ni versión y fallaría siempre.
#
set -euo pipefail

rojo()  { printf '\033[31m%s\033[0m\n' "$*" >&2; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

# ---------------------------------------------------------------------------
# De dónde salen los argumentos
#
# La llave de despliegue entra con un comando FORZADO y SIN argumentos
# (`command="/usr/local/bin/desplegar.sh"` en `authorized_keys`), así que sshd no
# le pasa ninguno. Los entrega en `SSH_ORIGINAL_COMMAND`, tal cual los escribió
# quien llama. Sin leerlos de ahí, este guion arrancaría sin entorno ni versión y
# fallaría SIEMPRE, por mucho que el resto estuviera bien.
# ---------------------------------------------------------------------------
if [ "$#" -eq 0 ] && [ -n "${SSH_ORIGINAL_COMMAND:-}" ]; then
  # División por espacios a propósito: así se reconstruyen los argumentos.
  # shellcheck disable=SC2086
  set -- $SSH_ORIGINAL_COMMAND

  # Quien llama escribe el nombre del comando delante —«desplegar.sh staging …»—
  # porque así se lee mejor en la canalización, así que hay que quitarlo. Se
  # aceptan las dos formas para no depender de cómo lo escriba el cliente.
  case "${1:-}" in
    desplegar.sh|*/desplegar.sh) shift ;;
  esac
fi

ENTORNO="${1:-}"
VERSION="${2:-}"
VOLVER="${3:-}"

# Se validan como si vinieran de fuera, porque vienen de fuera: las escribe el
# cliente y viajan por la red hasta aquí.
case "$ENTORNO" in
  staging|produccion) ;;
  "") rojo "Falta el entorno: staging o produccion"; exit 1 ;;
  *)  rojo "Entorno no válido: '$ENTORNO'"; exit 1 ;;
esac

# La versión se usa como etiqueta de imagen y se escribe en un archivo, así que
# sólo puede ser una confirmación en hexadecimal. Se rechaza cualquier otra cosa
# —espacios, barras, puntos suspensivos— antes de que llegue a una orden.
case "$VERSION" in
  "") rojo "Falta la versión (identificador del commit)"; exit 1 ;;
  *[!0-9a-f]*) rojo "La versión debe ser hexadecimal en minúsculas: '$VERSION'"; exit 1 ;;
esac
if [ "${#VERSION}" -lt 7 ] || [ "${#VERSION}" -gt 40 ]; then
  rojo "La versión debe tener entre 7 y 40 caracteres, tiene ${#VERSION}"
  exit 1
fi

case "$VOLVER" in
  ""|--volver) ;;
  *) rojo "Tercer argumento no válido: '$VOLVER'"; exit 1 ;;
esac

BASE="/opt/sede/$ENTORNO"

# El ESTADO del despliegue (qué versión corre y cuál había antes) vive en
# /var/lib, NO junto a la configuración. No es un detalle de orden: la carpeta de
# configuración la escribe la administración y la cuenta de despliegue no debe
# poder tocarla. Escribiendo ahí, una llave de despliegue comprometida podría
# reescribir el entorno que ejecuta; con el estado aparte, sólo puede anotar qué
# versión intentó.
ESTADO="/var/lib/sede/$ENTORNO"
COMPOSE="docker compose -f $BASE/compose.yaml --env-file $BASE/.env"
ESPERA="${SEDE_ESPERA_SEGUNDOS:-180}"

[ -d "$BASE" ] || { rojo "Falta $BASE. Lo crea deploy/publicar-entorno.sh."; exit 1; }
# El estado se exige explícitamente y con un mensaje que dice DE DÓNDE sale, en
# lugar de dejar que falle al escribir con «Permission denied», que apunta a los
# permisos y no a que falta el directorio.
[ -d "$ESTADO" ] || { rojo "Falta $ESTADO. Lo crea deploy/publicar-entorno.sh."; exit 1; }
[ -w "$ESTADO" ] || { rojo "$ESTADO no se puede escribir."; exit 1; }

# ---------------------------------------------------------------------------
# DE DÓNDE SALE EL ENTORNO (ADR-0019)
#
# Hasta ahora `.env` y `compose.yaml` los escribía en el servidor un guion que se
# ejecutaba A MANO desde la máquina de quien despliega. Dos consecuencias, las dos
# vistas: (1) un cambio de configuración no tenía efecto hasta acordarse de publicar
# el entorno, y el orden importaba —la canalización desplegó la imagen nueva con la
# configuración vieja y dejó el dominio entero en 521—; (2) volver a una versión
# anterior devolvía su código pero NO su configuración.
#
# Ahora la configuración viaja con la versión, dentro de la imagen de la aplicación
# (`/opt/sede-entorno/`), y este guion la renderiza aquí con los secretos del
# servidor. La configuración pasa a ser inmutable y direccionable por versión, igual
# que el código.
#
# EL REPARTO DE CONFIANZA, que es lo que hace esto seguro:
#
#   · Los SECRETOS nunca se mueven. Viven en `/etc/sede/<entorno>.secretos.env`, de
#     `root` y con permisos 600. No están en el repositorio —que es público—, no están
#     en GitHub Actions y no entran en ninguna imagen. Que un secreto no tenga que
#     viajar es la propiedad más fuerte de este diseño.
#   · La PLANTILLA y los VALORES llegan en la imagen y se tratan como DATOS: se
#     interpretan con un analizador estricto y NUNCA con `source`. Ejecutarlos daría a
#     quien pueda empujar al registro la capacidad de ejecutar órdenes como `root` en
#     este servidor.
#   · Este guion es el ANCLA DE CONFIANZA y NO se autoactualiza. Podría extraerse de
#     la imagen como el resto, y sería más cómodo; sería también una escalada de
#     privilegio: quien empujara una imagen sustituiría el código `root` del
#     anfitrión. Se instala a mano, deliberadamente, y por eso lleva escritos aquí el
#     registro de confianza y la lista de nombres de secretos.
#   · El contrato se comprueba: si la imagen trae un entorno que este guion no
#     entiende, el despliegue se detiene ANTES de tocar un contenedor.
#
# FALLO CERRADO. Nada se cambia hasta que todo valida: se renderiza en una carpeta
# temporal que sólo `root` puede leer, se comprueban marcadores, secretos y que la
# pila resuelva, y sólo entonces se intercambian los archivos con `mv` (un cambio de
# nombre, atómico) guardando los anteriores.
#
# RIESGO RESIDUAL DECLARADO: la imagen se identifica por su etiqueta, que es la
# confirmación. Quien tuviera permiso de escritura en el registro podría reemplazar
# una etiqueta por otro contenido. Fijar por digestión exigiría que la canalización se
# lo pasara a este guion, y hoy la llave de despliegue sólo puede ejecutar un comando
# con dos argumentos. Queda anotado en el ADR-0019.
# ---------------------------------------------------------------------------

# Constantes del ANFITRIÓN. No salen de la imagen a propósito (ver arriba).
REGISTRO_CONFIABLE="ghcr.io/santanderacuna"
NOMBRE_APP="sede"
CONTRATO_ENTORNO_SOPORTADO=1
RUTA_ENTORNO_EN_IMAGEN="/opt/sede-entorno"
SECRETOS="/etc/sede/$ENTORNO.secretos.env"

# Nombres de secretos. Tiene que coincidir con `deploy/secretos-requeridos.txt`, y una
# guarda de la canalización lo comprueba. Obligatorio = tiene que estar y no puede
# estar vacío; reconocido = puede faltar, y entonces se renderiza vacío (así declara la
# plantilla una integración apagada).
SECRETOS_OBLIGATORIOS="APP_KEY DB_PASSWORD REDIS_PASSWORD BACKUP_PASSPHRASE"
SECRETOS_RECONOCIDOS="SUPER_ADMIN_EMAIL SUPER_ADMIN_NAME SUPER_ADMIN_PASSWORD
S3_ACCESS_KEY S3_SECRET_KEY MAIL_USERNAME MAIL_PASSWORD IDENTIDAD_CLIENTE_ID
IDENTIDAD_CLIENTE_SECRETO SEDE_PAGOS_SECRETO SEDE_CORREO_CERTIFICADO_SECRETO
CAPTCHA_SECRETO"

declare -A VALOR=()

# Lee un archivo `CLAVE=valor` como DATOS. Rechaza cualquier otra cosa, con el número
# de línea: un archivo que no se entiende se rechaza, no se interpreta a medias.
#
#   $1 archivo   $2 cómo llamarlo en los mensajes   $3 nombres PROHIBIDOS (opcional)
cargar_declarativo() {
  local archivo="$1" clase="$2" prohibidos="${3:-}"
  local n=0 linea clave valor
  [ -f "$archivo" ] || { rojo "Falta $archivo ($clase)."; return 1; }
  while IFS= read -r linea || [ -n "$linea" ]; do
    n=$((n + 1))
    # Se tolera el retorno de carro por si el archivo se editó en otro sistema: un
    # `\r` invisible al final del valor acabaría dentro de una clave de cifrado.
    linea="${linea%$'\r'}"
    case "$linea" in '' | '#'*) continue ;; esac
    if [[ ! "$linea" =~ ^([A-Z][A-Z0-9_]*)=(.*)$ ]]; then
      rojo "$archivo línea $n: no es CLAVE=valor ($clase). No se interpreta a medias."
      return 1
    fi
    clave="${BASH_REMATCH[1]}"
    valor="${BASH_REMATCH[2]}"
    if [ -n "$prohibidos" ] && printf '%s\n' $prohibidos | grep -qxF "$clave"; then
      rojo "$archivo línea $n declara '$clave', que SÓLO puede venir de $SECRETOS."
      rojo "  Un archivo que viaja en una imagen no puede fijar un secreto."
      return 1
    fi
    # Los dos caracteres de control que usa el renderizador como marca interna. Un
    # valor que los contuviera podría alterar la sustitución.
    case "$valor" in *$'\001'* | *$'\002'*) rojo "$archivo línea $n: valor con caracteres de control."; return 1 ;; esac
    VALOR["$clave"]="$valor"
  done < "$archivo"
  return 0
}

# Sustituye `${CLAVE}` por su valor, en UNA sola pasada y sin ejecutar nada.
#
# Se hace en dos fases con una marca interna (`\001<índice>\002`) en lugar de
# sustituir directamente, y el motivo es de corrección, no de estilo: si un valor
# contiene algo con la forma `${OTRA}`, una sustitución directa lo volvería a mirar en
# la siguiente vuelta del bucle y lo reemplazaría. Con la marca, lo ya sustituido no se
# vuelve a examinar.
#
# No se usa `envsubst`: no está instalado en el servidor (habría que añadir `gettext`
# como dependencia del despliegue) y, sobre todo, expandiría CUALQUIER variable del
# entorno del proceso que apareciera en la plantilla. Aquí sólo existe lo que se ha
# cargado explícitamente. La sustitución de bash no da significado especial a `&` ni a
# `\` en el reemplazo, a diferencia de `sed`.
renderizar() {
  local plantilla="$1" salida="$2" linea i
  local -a claves=() valores=()
  for i in "${!VALOR[@]}"; do
    claves+=("$i")
    valores+=("${VALOR[$i]}")
  done
  : > "$salida"
  while IFS= read -r linea || [ -n "$linea" ]; do
    for i in "${!claves[@]}"; do
      linea="${linea//'${'"${claves[i]}"'}'/$'\001'"$i"$'\002'}"
    done
    for i in "${!claves[@]}"; do
      linea="${linea//$'\001'"$i"$'\002'/"${valores[i]}"}"
    done
    printf '%s\n' "$linea" >> "$salida"
  done < "$plantilla"
}

# Deja en $BASE el `.env` y el `compose.yaml` de ESTA versión.
#   0 = listo    1 = error (no se toca nada)    2 = la imagen no trae entorno
preparar_entorno() {
  local imagen tmp cid modo dueno clave faltan n
  imagen="$REGISTRO_CONFIABLE/$NOMBRE_APP-app:$VERSION"

  # 1. El archivo de secretos, antes que nada. Se comprueban dueño y permisos: si lo
  #    pudiera escribir otra cuenta, dejaría de ser el ancla que se supone.
  [ -f "$SECRETOS" ] || {
    rojo "Falta $SECRETOS."
    rojo "  Lo instala './deploy/publicar-entorno.sh $ENTORNO' desde la máquina de operación."
    return 1
  }
  modo="$(stat -c %a "$SECRETOS")"
  dueno="$(stat -c %U:%G "$SECRETOS")"
  case "$modo" in 600 | 400) ;; *)
    rojo "$SECRETOS tiene permisos $modo; deben ser 600. No se usa."
    return 1
    ;;
  esac
  [ "$dueno" = "root:root" ] || {
    rojo "$SECRETOS pertenece a $dueno; debe ser root:root. No se usa."
    return 1
  }

  # 2. La imagen que trae la configuración. Sólo se trae si no está: así el guion es
  #    idempotente y se puede reintentar un despliegue sin volver a depender de la red.
  if ! docker image inspect "$imagen" > /dev/null 2>&1; then
    if ! docker pull -q "$imagen" > /dev/null; then
      rojo "No se pudo traer $imagen."
      return 1
    fi
  fi

  # 3. Extracción. `docker create` NO ejecuta la imagen: crea el contenedor y se copia
  #    de su sistema de archivos. Nada del contenido de la imagen corre en el servidor.
  tmp="$(mktemp -d)" || return 1
  chmod 700 "$tmp"
  cid="$(docker create "$imagen" true 2> /dev/null)" || {
    rojo "No se pudo preparar la extracción del entorno."
    rm -rf "$tmp"
    return 1
  }
  if ! docker cp "$cid:$RUTA_ENTORNO_EN_IMAGEN/." "$tmp/" > /dev/null 2>&1; then
    docker rm -f "$cid" > /dev/null 2>&1 || true
    rm -rf "$tmp"
    return 2
  fi
  docker rm -f "$cid" > /dev/null 2>&1 || true

  for n in compose.yaml plantilla.env "valores-$ENTORNO.env"; do
    [ -f "$tmp/$n" ] || {
      rojo "La imagen de $VERSION no trae $n."
      rm -rf "$tmp"
      return 2
    }
  done

  # 4. Valores de la imagen (datos, nunca ejecutados) con los nombres de secretos
  #    PROHIBIDOS, y después los secretos del servidor, que así ganan siempre.
  VALOR=()
  if ! cargar_declarativo "$tmp/valores-$ENTORNO.env" "valores del entorno" \
    "$SECRETOS_OBLIGATORIOS $SECRETOS_RECONOCIDOS"; then
    rm -rf "$tmp"
    return 1
  fi

  if [ "${VALOR[CONTRATO_ENTORNO]:-}" != "$CONTRATO_ENTORNO_SOPORTADO" ]; then
    rojo "La imagen trae un entorno de contrato '${VALOR[CONTRATO_ENTORNO]:-ninguno}' y este"
    rojo "  servidor entiende el '$CONTRATO_ENTORNO_SOPORTADO'. Actualiza /usr/local/bin/desplegar.sh"
    rojo "  (deploy/droplet/despliegue-remoto.sh) antes de desplegar esta versión."
    rm -rf "$tmp"
    return 1
  fi
  if [ "${VALOR[REGISTRY]:-}" != "$REGISTRO_CONFIABLE" ] || [ "${VALOR[APP_NAME]:-}" != "$NOMBRE_APP" ]; then
    rojo "El entorno de la imagen apunta a otro registro o aplicación."
    rojo "  De dónde se traen las imágenes lo decide este servidor, no la imagen."
    rm -rf "$tmp"
    return 1
  fi

  # Los reconocidos se inicializan vacíos: una integración apagada no es un error.
  for clave in $SECRETOS_RECONOCIDOS; do VALOR["$clave"]=""; done
  if ! cargar_declarativo "$SECRETOS" "secretos del servidor"; then
    rm -rf "$tmp"
    return 1
  fi

  # Lo que sabe el anfitrión y no puede saber ningún archivo.
  VALOR[ENTORNO]="$ENTORNO"
  VALOR[VERSION]="$VERSION"

  # 5. Un secreto obligatorio vacío pasa una comprobación de presencia y falla al
  #    usarse, a mitad del despliegue. Se comprueba uno a uno, por su nombre.
  for clave in $SECRETOS_OBLIGATORIOS; do
    if [ -z "${VALOR[$clave]:-}" ]; then
      rojo "$SECRETOS no define $clave (o lo define vacío)."
      rm -rf "$tmp"
      return 1
    fi
  done

  # 6. Cobertura ANTES de renderizar, para que el mensaje diga qué falta y de dónde
  #    tiene que salir, en lugar de dejar un `.env` a medias.
  faltan=""
  for clave in $(grep -oE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$tmp/plantilla.env" | tr -d '${}' | sort -u); do
    [ -n "${VALOR[$clave]+x}" ] || faltan="$faltan $clave"
  done
  if [ -n "$faltan" ]; then
    rojo "La plantilla usa variables que nadie provee:$faltan"
    rojo "  Un secreto va en $SECRETOS (en este servidor);"
    rojo "  cualquier otra, en deploy/valores-$ENTORNO.env (en el repositorio)."
    rm -rf "$tmp"
    return 1
  fi

  # 7. Renderizado con la máscara cerrada: el archivo nace sin permisos para nadie más.
  (
    umask 077
    renderizar "$tmp/plantilla.env" "$tmp/env.nuevo"
  ) || {
    rojo "Falló el renderizado del entorno."
    rm -rf "$tmp"
    return 1
  }

  if grep -qE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$tmp/env.nuevo"; then
    rojo "Quedaron marcadores sin sustituir:"
    grep -oE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$tmp/env.nuevo" | sort -u | sed 's/^/    /' >&2
    rm -rf "$tmp"
    return 1
  fi

  # 8. La pila tiene que resolver con ESTE par de archivos antes de sustituir nada.
  if ! docker compose -f "$tmp/compose.yaml" --env-file "$tmp/env.nuevo" config --quiet; then
    rojo "La pila NO resuelve con el entorno de $VERSION. No se sustituye nada."
    rm -rf "$tmp"
    return 1
  fi

  # 9. Intercambio atómico, guardando el anterior. `mv` dentro del mismo sistema de
  #    archivos es un cambio de nombre: no existe un instante con medio archivo.
  install -m 640 -o root -g deploy "$tmp/env.nuevo" "$BASE/.env.entrante" || { rm -rf "$tmp"; return 1; }
  install -m 644 -o root -g deploy "$tmp/compose.yaml" "$BASE/compose.yaml.entrante" || { rm -rf "$tmp"; return 1; }
  [ -f "$BASE/.env" ] && cp -p "$BASE/.env" "$BASE/.env.anterior"
  [ -f "$BASE/compose.yaml" ] && cp -p "$BASE/compose.yaml" "$BASE/compose.yaml.anterior"
  mv -f "$BASE/.env.entrante" "$BASE/.env"
  mv -f "$BASE/compose.yaml.entrante" "$BASE/compose.yaml"
  rm -rf "$tmp"
  verde "  entorno de $VERSION renderizado en el servidor"
  return 0
}

# En `--volver` no se renderiza: se despliega lo que ya hay, que es justamente la
# situación en la que se recurre a este modo.
if [ "$VOLVER" != "--volver" ]; then
  preparar_entorno
  CODIGO_ENTORNO=$?
  case "$CODIGO_ENTORNO" in
    0) ;;
    2)
      # Una versión anterior a este mecanismo no lleva el entorno dentro. Se sigue con
      # el que haya en el servidor, que es exactamente el comportamiento de antes, y se
      # dice en voz alta para que no parezca que se aplicó una configuración nueva.
      printf '\033[33m%s\033[0m\n' "  La imagen de $VERSION no trae entorno: se usa el que ya está en $BASE." >&2
      ;;
    *) exit 1 ;;
  esac
fi

# Con el entorno ya resuelto, estos dos archivos TIENEN que estar. Se comprueba
# después y no antes: ahora los puede haber escrito este mismo guion.
[ -f "$BASE/.env" ]         || { rojo "Falta $BASE/.env."; exit 1; }
[ -f "$BASE/compose.yaml" ] || { rojo "Falta $BASE/compose.yaml"; exit 1; }

cd "$BASE"

# --- Versión anterior ---------------------------------------------------------
if [ -f "$ESTADO/version" ]; then
  ANTERIOR="$(cat "$ESTADO/version")"
else
  ANTERIOR=""
fi

# Se apunta la versión nueva ANTES de intentarlo: si el despliegue muere a mitad,
# queda registrado qué se estaba intentando.
if [ "$VOLVER" != "--volver" ]; then
  printf '%s' "$VERSION" > "$ESTADO/version"
  [ -n "$ANTERIOR" ] && printf '%s' "$ANTERIOR" > "$ESTADO/version.anterior"
fi

export APP_VERSION="$VERSION"

volver_atras() {
  if [ -z "$ANTERIOR" ] || [ "$ANTERIOR" = "$VERSION" ]; then
    rojo "No hay versión anterior a la que volver."
    return 1
  fi
  rojo "Volviendo a la versión $ANTERIOR…"
  printf '%s' "$ANTERIOR" > "$ESTADO/version"

  # El entorno también vuelve atrás (ADR-0019). Desde que la configuración viaja
  # con la versión, volver al código anterior sin volver a SU configuración deja la
  # pila en un estado que nadie ha probado: código viejo con variables nuevas.
  if [ -f "$BASE/.env.anterior" ]; then
    cp -p "$BASE/.env.anterior" "$BASE/.env"
    [ -f "$BASE/compose.yaml.anterior" ] && cp -p "$BASE/compose.yaml.anterior" "$BASE/compose.yaml"
    rojo "  entorno anterior restaurado"
  fi

  # `APP_VERSION` del proceso gana sobre el archivo de entorno en Compose, así que
  # esto manda aunque el `.env` restaurado diga otra cosa.
  export APP_VERSION="$ANTERIOR"
  $COMPOSE pull -q || true
  $COMPOSE up -d --remove-orphans
  $COMPOSE up -d --force-recreate nginx
}

# --- Despliegue ---------------------------------------------------------------
echo "Desplegando $VERSION en $ENTORNO"

if ! $COMPOSE pull -q; then
  rojo "No se pudieron traer las imágenes."
  volver_atras || true
  exit 1
fi
verde "  imágenes traídas"

# Las migraciones van ANTES de servir: una aplicación nueva contra un esquema
# viejo falla en la primera petición del ciudadano.
if ! $COMPOSE run --rm app php artisan migrate --force; then
  rojo "Fallaron las migraciones."
  volver_atras || true
  exit 1
fi
verde "  esquema al día"

# --- Siembra ------------------------------------------------------------------
#
# **Sin esto la sede se sirve vacía, y así se sirvió.** El despliegue migraba y
# no sembraba: la base de pruebas quedó con `tramites: 0`, así que la API
# devolvía `total: 0`, la portada decía «el catálogo no está disponible» y
# **cada ficha respondía 404**. El código viajaba y los datos no, y nada lo
# decía: el despliegue terminaba en verde.
#
# Se nombra el sembrador por su clase y no se ejecuta el de por defecto. Un
# `db:seed` a secas correría `DatabaseSeeder`, que es el punto de entrada de
# Laravel y el que usan las pruebas y `make preparar`: cualquier dato que
# mañana se añada ahí para desarrollar viajaría a producción sin que nadie lo
# note. `SedeSeeder` es la lista de lo institucional, y los cuatro sembradores
# que llama son idempotentes, así que correrlo en cada despliegue no duplica
# nada ni pisa lo que haya corregido una persona.
#
# Va después de migrar —se siembra sobre un esquema al día— y antes de levantar:
# una sede que responde con el catálogo vacío es peor que una que no responde,
# porque la primera parece funcionar.
if ! $COMPOSE run --rm app php artisan db:seed --class=SedeSeeder --force; then
  rojo "Falló la siembra. La sede se serviría sin catálogo, así que no se levanta."
  volver_atras || true
  exit 1
fi
verde "  datos institucionales sembrados"

$COMPOSE rm -f >/dev/null 2>&1 || true
$COMPOSE up -d --remove-orphans

# nginx resuelve la dirección del contenedor al arrancar y la conserva: sin
# recrearlo, seguiría hablándole al contenedor anterior, que ya no existe.
$COMPOSE up -d --force-recreate nginx
verde "  contenedores en marcha"

echo -n "Esperando a que la sede responda…"
ESPERADO=$((ESPERA / 5))
for _ in $(seq 1 "$ESPERADO"); do
  if curl -fsSk "https://127.0.0.1:${NGINX_HTTPS_PORT:-443}/ready" >/dev/null 2>&1; then
    echo
    verde "  la sede responde"
    echo
    $COMPOSE ps --format 'table {{.Service}}\t{{.Status}}' | sed 's/^/  /'
    exit 0
  fi
  # Si el punto de entrada está en bucle de reinicio, esperar los 180 segundos no
  # sirve de nada: no va a responder. Se detecta y se informa YA, con su motivo.
  #
  # El despliegue tardaba cuatro minutos en fallar por un certificado que no
  # existe, cuando la causa se sabe en veinte segundos. Un fallo lento no es sólo
  # tiempo perdido: es tiempo durante el cual quien mira no sabe si el despliegue
  # avanza o está atascado.
  if $COMPOSE ps --status restarting --services 2>/dev/null | grep -qx nginx \
     || $COMPOSE ps --status exited --services 2>/dev/null | grep -qx nginx; then
    echo
    rojo "El punto de entrada no arranca. Últimos registros:"
    $COMPOSE logs --tail=15 nginx 2>&1 | sed 's/^/  /' >&2
    volver_atras || true
    exit 1
  fi
  echo -n "."
  sleep 5
done

echo
rojo "La sede no respondió en ${ESPERA}s. Últimos registros:"
$COMPOSE logs --tail=40 app nginx 2>&1 | sed 's/^/  /' >&2

volver_atras || true
exit 1

#!/usr/bin/env bash
#
# Puerta de respaldo y restauración (D-06, RNF-B1-026).
#
# **Qué comprueba, y por qué así.** Un respaldo que nunca se ha restaurado no es
# un respaldo: es un fichero del que se supone algo. Esta puerta hace el ciclo
# entero contra la base de datos de verdad —volcado, restauración en una base
# temporal y comparación fila por fila— y falla si algo no cuadra.
#
# **No toca los datos.** El volcado es de sólo lectura y la restauración va a una
# base temporal que se crea y se destruye en la misma ejecución, con `trap` para
# que no quede ni siquiera si la puerta falla a mitad.
#
# **De qué depende.** De la pila levantada (`make arriba`) y de `docker`. No usa
# el servicio `copia` del compose ni la frase de paso: así la puerta se puede
# ejecutar sin secretos y comprueba lo que de verdad importa —que la base se pueda
# volcar y volver a levantar con los mismos datos—.
#
# Uso: `make respaldo` · `bash scripts/verificar-respaldo.sh`
#      SEDE_CONTENEDOR_BD=<nombre> para forzar el contenedor de la base.
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

PROYECTO="${APP_NAME:-sede}"
BD_TEMPORAL="respaldo_verificacion_$$"
VOLCADO=""

fallos=0
aviso() { printf '  %s\n' "$*"; }

limpiar() {
  if [[ -n "${CONTENEDOR_BD:-}" ]]; then
    docker exec "$CONTENEDOR_BD" psql -U "$BD_USUARIO" -d postgres \
      -c "DROP DATABASE IF EXISTS \"$BD_TEMPORAL\"" >/dev/null 2>&1 || true
  fi
  [[ -n "$VOLCADO" && -f "$VOLCADO" ]] && rm -f "$VOLCADO"
  return 0
}
trap limpiar EXIT

echo "Puerta de respaldo y restauración"
echo "================================================================================"

# ——— 1. ¿Está docker y está la pila en marcha? ———
if ! command -v docker >/dev/null 2>&1; then
  echo "Falla: no hay docker en el PATH. Esta puerta necesita la pila levantada"
  echo "(`make arriba`); sin ella no puede comprobar que una copia se restaura."
  exit 1
fi

if [[ -z "${SEDE_CONTENEDOR_BD:-}" ]]; then
  CONTENEDOR_BD="$(docker ps \
    --filter "label=com.docker.compose.project=$PROYECTO" \
    --format '{{.Names}} {{.Label "com.docker.compose.service"}}' \
    | awk '$2 == "db" || $2 == "postgres" { print $1 }' | head -1)"
else
  CONTENEDOR_BD="$SEDE_CONTENEDOR_BD"
fi

if [[ -z "${CONTENEDOR_BD:-}" ]]; then
  echo "Falla: no se encontró el contenedor de la base de datos del proyecto «$PROYECTO»."
  echo "Levántela con \`make arriba\`, o indique el contenedor con SEDE_CONTENEDOR_BD."
  exit 1
fi

BD_NOMBRE="$(docker exec "$CONTENEDOR_BD" printenv POSTGRES_DB 2>/dev/null || true)"
BD_USUARIO="$(docker exec "$CONTENEDOR_BD" printenv POSTGRES_USER 2>/dev/null || true)"
BD_NOMBRE="${BD_NOMBRE:-sede_electronica}"
BD_USUARIO="${BD_USUARIO:-sede}"

aviso "contenedor: $CONTENEDOR_BD"
aviso "base de datos: $BD_NOMBRE (usuario $BD_USUARIO)"

# ——— 2. Volcado ———
VOLCADO="$(mktemp -t respaldo-XXXXXX.dump)"
if ! docker exec "$CONTENEDOR_BD" pg_dump -U "$BD_USUARIO" -d "$BD_NOMBRE" -Fc > "$VOLCADO" 2>/dev/null; then
  echo "Falla: no se pudo volcar la base $BD_NOMBRE."
  exit 1
fi

TAMANO="$(stat -c%s "$VOLCADO" 2>/dev/null || echo 0)"
if (( TAMANO < 1024 )); then
  echo "Falla: el volcado mide $TAMANO bytes. Una base con datos no cabe ahí: la copia está vacía."
  exit 1
fi
aviso "volcado: $TAMANO bytes"

# ——— 3. Restauración en una base temporal ———
docker exec "$CONTENEDOR_BD" psql -U "$BD_USUARIO" -d postgres \
  -c "DROP DATABASE IF EXISTS \"$BD_TEMPORAL\"" >/dev/null 2>&1 || true
docker exec "$CONTENEDOR_BD" psql -U "$BD_USUARIO" -d postgres \
  -c "CREATE DATABASE \"$BD_TEMPORAL\"" >/dev/null

if ! docker exec -i "$CONTENEDOR_BD" pg_restore -U "$BD_USUARIO" -d "$BD_TEMPORAL" \
  --no-owner --no-privileges < "$VOLCADO" 2>/dev/null; then
  echo "Falla: la restauración devolvió un error. Un volcado que no se restaura no es una copia."
  exit 1
fi
aviso "restauración: sin errores"

# ——— 4. Comparación, tabla por tabla y con conteos exactos ———
# `query_to_xml` da el `count(*)` real de cada tabla en una sola consulta: contar
# con `pg_stat_user_tables` daría estimaciones, y una copia se comprueba con
# números exactos.
CONSULTA="SELECT relname || '=' || (xpath('/row/c/text()', query_to_xml(
  format('SELECT count(*) AS c FROM %I.%I', schemaname, relname), false, true, ''
)))[1]::text FROM pg_stat_user_tables ORDER BY relname"

docker exec "$CONTENEDOR_BD" psql -U "$BD_USUARIO" -d "$BD_NOMBRE" -tAc "$CONSULTA" \
  | sed '/^$/d' > "$VOLCADO.original"
docker exec "$CONTENEDOR_BD" psql -U "$BD_USUARIO" -d "$BD_TEMPORAL" -tAc "$CONSULTA" \
  | sed '/^$/d' > "$VOLCADO.restaurada"

TABLAS="$(wc -l < "$VOLCADO.original" | tr -d ' ')"
FILAS="$(awk -F= '{ suma += $2 } END { print suma + 0 }' "$VOLCADO.original")"
aviso "origen: $TABLAS tablas · $FILAS filas"

if ! diff -q "$VOLCADO.original" "$VOLCADO.restaurada" >/dev/null; then
  fallos=1
  echo
  echo "Falla: la base restaurada no coincide con la original."
  diff "$VOLCADO.original" "$VOLCADO.restaurada" | head -10
fi

if (( TABLAS == 0 || FILAS == 0 )); then
  fallos=1
  echo
  echo "Falla: la comparación no encontró tablas con contenido; la puerta no estaría comprobando nada."
fi

rm -f "$VOLCADO.original" "$VOLCADO.restaurada"

echo
echo "================================================================================"
if (( fallos == 0 )); then
  echo "La puerta pasa: la base se vuelca y se restaura con las mismas $TABLAS tablas y $FILAS filas."
  exit 0
fi

echo "La puerta falla: la copia de seguridad no restaura lo que debería."
exit 1

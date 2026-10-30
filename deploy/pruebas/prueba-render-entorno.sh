#!/bin/bash
#
# Pruebas del renderizador del entorno del servidor (ADR-0019).
#
#   bash deploy/pruebas/prueba-render-entorno.sh
#
# QUÉ SE PROTEGE AQUÍ, y por qué merece una prueba propia. Este renderizador escribe
# el `.env` con el que arranca la sede: la clave de la aplicación, la de la base y la
# del super-admin pasan por él. Sus dos fallos posibles son silenciosos:
#
#   · que un valor se sustituya mal (una clave truncada o alterada no se nota al
#     renderizar, se nota cuando la aplicación no arranca o, peor, arranca mal);
#   · que el contenido de un archivo que llega DENTRO de una imagen acabe
#     ejecutándose o pisando un secreto.
#
# Se prueban las funciones REALES: se extraen del propio `despliegue-remoto.sh` en vez
# de copiarlas aquí, porque una copia se queda vieja y entonces la prueba deja de
# hablar del código que corre en el servidor.
#
# No se prueba aquí `preparar_entorno` completa: necesita `root`, el demonio de Docker
# y una imagen publicada. Eso se verifica a mano antes de desplegar.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
GUION="$RAIZ/deploy/droplet/despliegue-remoto.sh"
PLANTILLA="$RAIZ/deploy/plantilla.env"

fallos=0
pasadas=0
ok()    { pasadas=$((pasadas + 1)); printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal()   { fallos=$((fallos + 1));   printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }
igual() { if [ "$2" = "$3" ]; then ok "$1"; else mal "$1"; printf '      esperado: %q\n      obtenido: %q\n' "$3" "$2" >&2; fi; }

# --- Las funciones reales, extraídas del guion del servidor -------------------
FUNCIONES="$(mktemp)"
trap 'rm -rf "$FUNCIONES" "$TMP"' EXIT
awk '/^# Constantes del ANFITRIÓN/{dentro=1} /^# En `--volver` no se renderiza/{dentro=0} dentro' \
  "$GUION" > "$FUNCIONES"

for f in cargar_declarativo renderizar; do
  grep -q "^$f()" "$FUNCIONES" || { printf '\033[31mNo se pudo extraer %s() de %s\033[0m\n' "$f" "$GUION" >&2; exit 1; }
done
ENTORNO=staging
# shellcheck source=/dev/null
. "$FUNCIONES"

TMP="$(mktemp -d)"

echo
echo "1. El analizador acepta datos y RECHAZA todo lo demás"

VALOR=()
printf 'A=1\n# comentario\n\nB=con espacios y = signos\nC=\n' > "$TMP/bien.env"
if cargar_declarativo "$TMP/bien.env" "prueba" 2>/dev/null; then ok "acepta un archivo CLAVE=valor"; else mal "rechazó un archivo válido"; fi
igual "el valor se toma literal hasta el final de la línea" "${VALOR[B]:-}" "con espacios y = signos"
# `${x+definida}` y no `${x:-…}`: lo segundo no distingue «vacía» de «ausente», y esa
# diferencia es precisamente la que decide si un secreto obligatorio pasa o no.
igual "una clave sin valor queda DEFINIDA y vacía" "${VALOR[C]+definida}:${VALOR[C]-ausente}" "definida:"

for caso in 'minuscula=1' 'CON-GUION=1' '1NUMERO=1' 'sin igual' 'export A=1'; do
  VALOR=()
  printf '%s\n' "$caso" > "$TMP/mal.env"
  if cargar_declarativo "$TMP/mal.env" "prueba" 2>/dev/null; then
    mal "aceptó una línea que no es CLAVE=valor: $caso"
  else
    ok "rechaza: $caso"
  fi
done

VALOR=()
printf 'A=1\nB=2\n' > "$TMP/mal2.env"
if cargar_declarativo "$TMP/mal2.env" "prueba" "B C" 2>/dev/null; then
  mal "un archivo de valores pudo declarar un nombre de secreto"
else
  ok "rechaza que los valores de la imagen declaren un secreto"
fi

VALOR=()
printf 'A=con\001control\n' > "$TMP/mal3.env"
if cargar_declarativo "$TMP/mal3.env" "prueba" 2>/dev/null; then
  mal "aceptó un valor con los caracteres de control del renderizador"
else
  ok "rechaza valores con caracteres de control"
fi

VALOR=()
if cargar_declarativo "$TMP/no-existe.env" "prueba" 2>/dev/null; then
  mal "no avisó de un archivo ausente"
else
  ok "avisa cuando el archivo no existe"
fi

echo
echo "2. La sustitución es literal y de una sola pasada"

VALOR=()
VALOR[UNO]='valor-1'
VALOR[DOS]='$(id)`whoami`&\/'
VALOR[TRES]='${UNO}'
VALOR[VACIA]=''
printf 'a=${UNO}\nb=${DOS}\nc=${TRES}\nd=${VACIA}fin\ne=${UNO}-${UNO}\nf=${NO_DEFINIDA}\ng=sin marcadores\n' > "$TMP/p.env"
renderizar "$TMP/p.env" "$TMP/r.env"

igual "sustituye un marcador"                       "$(sed -n 1p "$TMP/r.env")" 'a=valor-1'
igual "un valor con \$(), \`\`, & y \\ queda LITERAL"  "$(sed -n 2p "$TMP/r.env")" 'b=$(id)`whoami`&\/'
igual "un valor con forma de marcador NO se re-sustituye" "$(sed -n 3p "$TMP/r.env")" 'c=${UNO}'
igual "un valor vacío no se come el resto de la línea" "$(sed -n 4p "$TMP/r.env")" 'd=fin'
igual "sustituye todas las apariciones de la línea"  "$(sed -n 5p "$TMP/r.env")" 'e=valor-1-valor-1'
igual "una variable no definida se deja intacta"     "$(sed -n 6p "$TMP/r.env")" 'f=${NO_DEFINIDA}'
igual "una línea sin marcadores no se toca"          "$(sed -n 7p "$TMP/r.env")" 'g=sin marcadores'
igual "no añade ni pierde líneas"                    "$(wc -l < "$TMP/r.env")" "7"

# Una plantilla sin salto de línea final no debe perder su última línea.
printf 'x=${UNO}' > "$TMP/p2.env"
renderizar "$TMP/p2.env" "$TMP/r2.env"
igual "conserva la última línea sin salto final" "$(cat "$TMP/r2.env")" 'x=valor-1'

echo
echo "3. La plantilla real queda completa, y con los valores reales"

VALOR=()
# Secretos de juguete, con los caracteres que de verdad aparecen en una clave.
declare -A SECRETO=(
  [APP_KEY]='base64:AbC+dEf/gHi=jKl'
  [DB_PASSWORD]='clave con espacio & $ignora `esto`'
  [REDIS_PASSWORD]='r3d1s'
  [BACKUP_PASSPHRASE]='frase-de-respaldo'
  [SUPER_ADMIN_EMAIL]='admin@example.test'
  [SUPER_ADMIN_NAME]='Nombre Apellido'
  [SUPER_ADMIN_PASSWORD]='Sup3r!'
)
: > "$TMP/secretos.env"
for k in "${!SECRETO[@]}"; do printf '%s=%s\n' "$k" "${SECRETO[$k]}" >> "$TMP/secretos.env"; done

cargar_declarativo "$RAIZ/deploy/valores-staging.env" "valores" "" || mal "no se pudo cargar valores-staging.env"
for k in $SECRETOS_RECONOCIDOS; do VALOR["$k"]=""; done
cargar_declarativo "$TMP/secretos.env" "secretos" || mal "no se pudo cargar los secretos de prueba"
VALOR[ENTORNO]='staging'
VALOR[VERSION]='0123456789abcdef0123456789abcdef01234567'

renderizar "$PLANTILLA" "$TMP/real.env"

if grep -qE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$TMP/real.env"; then
  mal "quedaron marcadores sin sustituir en la plantilla real:"
  grep -oE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$TMP/real.env" | sort -u | sed 's/^/      /' >&2
else
  ok "la plantilla real no deja ningún marcador"
fi

igual "APP_KEY llega íntegra, con + / y ="       "$(grep -m1 '^APP_KEY=' "$TMP/real.env")"     "APP_KEY=${SECRETO[APP_KEY]}"
igual "DB_PASSWORD llega íntegra y literal"      "$(grep -m1 '^DB_PASSWORD=' "$TMP/real.env")" "DB_PASSWORD=${SECRETO[DB_PASSWORD]}"
igual "APP_VERSION es la versión desplegada"     "$(grep -m1 '^APP_VERSION=' "$TMP/real.env")" "APP_VERSION=${VALOR[VERSION]}"
igual "el dominio sale de los valores del entorno" "$(grep -m1 '^SEDE_DOMINIO=' "$TMP/real.env")" "SEDE_DOMINIO=staging.santamarta.gov.co"
igual "la ruta de certificados usa el entorno"   "$(grep -m1 '^SEDE_CERTS_DIR=' "$TMP/real.env")" "SEDE_CERTS_DIR=/opt/sede/staging/certs"

# Equivalencia con el mecanismo anterior: sustituir `${VAR}` por su valor es
# exactamente lo que hacía `envsubst` en la máquina de operación. Si estas dos salidas
# se separaran, el servidor arrancaría con un entorno distinto del que se revisó.
python3 - "$PLANTILLA" "$TMP/real.env" "$TMP/secretos.env" "$RAIZ/deploy/valores-staging.env" "${VALOR[VERSION]}" <<'PY'
import re, sys
plantilla, obtenido, secretos, valores, version = sys.argv[1:6]
v = {}
for archivo in (valores, secretos):
    for linea in open(archivo, encoding='utf-8'):
        linea = linea.rstrip('\n')
        if not linea or linea.startswith('#') or '=' not in linea:
            continue
        k, _, val = linea.partition('=')
        if re.fullmatch(r'[A-Z][A-Z0-9_]*', k):
            v[k] = val
v.setdefault('ENTORNO', 'staging')
v['VERSION'] = version
esperado = re.sub(r'\$\{([A-Za-z_][A-Za-z0-9_]*)\}',
                  lambda m: v.get(m.group(1), m.group(0)),
                  open(plantilla, encoding='utf-8').read())
real = open(obtenido, encoding='utf-8').read()
# Las claves opcionales ausentes se renderizan vacías; el equivalente hace lo mismo
# tras declararlas, así que se comparan sin ellas.
esperado = re.sub(r'\$\{[A-Za-z_][A-Za-z0-9_]*\}', '', esperado)
if esperado.rstrip('\n') == real.rstrip('\n'):
    print('  \033[32m✓\033[0m coincide con la sustitución de referencia (equivale a `envsubst`)')
else:
    import difflib
    print('  \033[31m✗ NO coincide con la sustitución de referencia\033[0m', file=sys.stderr)
    for l in list(difflib.unified_diff(esperado.splitlines(), real.splitlines(),
                                       'referencia', 'renderizador', lineterm=''))[:20]:
        print('      ' + l, file=sys.stderr)
    sys.exit(1)
PY
if [ $? -eq 0 ]; then pasadas=$((pasadas + 1)); else fallos=$((fallos + 1)); fi

echo
if [ "$fallos" -eq 0 ]; then
  printf '\033[32m%s comprobaciones, todas pasan.\033[0m\n' "$pasadas"
  exit 0
fi
printf '\033[31m%s comprobaciones fallan de %s.\033[0m\n' "$fallos" "$((fallos + pasadas))" >&2
exit 1

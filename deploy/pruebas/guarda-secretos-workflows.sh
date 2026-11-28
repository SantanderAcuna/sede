#!/usr/bin/env bash
#
# Guarda: ningún flujo de .github/workflows lee un secreto de la APLICACIÓN.
#
# Los secretos de la aplicación (APP_KEY, contraseñas de la base y de Redis, claves de S3, la frase de
# las copias…) viven en UN sitio: `/etc/sede/<entorno>.secretos.env` en el servidor (ADR-0019). Los
# flujos sólo necesitan DEPLOY_*, VERIFICADOR_VINCULOS_TOKEN y GITHUB_TOKEN. Un flujo que empiece a
# leer `secrets.DB_PASSWORD` convierte a GitHub en un segundo custodio sin que nadie lo haya decidido:
# esta guarda lo hace visible en la revisión del PR.
#
# Los nombres salen de `deploy/secretos-requeridos.txt` (la lista de verdad de los secretos de la
# aplicación). Si un flujo debe leer uno, se decide a propósito: se quita de la lista de la guarda aquí.
#
# Qué cuenta como lectura (documentación de GitHub Actions, consultada el 2026-11-27; páginas «contexts»,
# «secrets», «reuse-workflows» y «use-secrets» de docs.github.com/en/actions):
#  - `secrets.NOMBRE` y `secrets['NOMBRE']`: las dos sintaxis de acceso a un contexto («contexts»).
#    Los nombres se comparan SIN distinguir mayúsculas: «Are case insensitive when referenced» («secrets»),
#    así que `secrets.db_password` lee `DB_PASSWORD`.
#  - Cualquier otro uso de `secrets` dentro de `${{ }}` —`toJSON(secrets)`, `secrets[matrix.x]`,
#    `secrets[format(…)]`— no deja ver qué nombre se lee. Se rechaza siempre: la guarda no puede
#    garantizar que no sea un secreto de la aplicación.
#  - `secrets: inherit` pasa al flujo reutilizable TODOS los secretos del que lo llama («reuse-workflows»).
#    Se rechaza por la misma razón.
# Sólo se mira dentro de `${{ }}`: fuera de una expresión GitHub no sustituye nada, y `secrets` no está
# disponible en `if:` («Secrets cannot be directly referenced in if: conditionals»). Tampoco cuentan los
# textos entre comillas simples de una expresión: `'secrets.X'` es una cadena, no una lectura.
#
# Autoprueba: antes de mirar los flujos reales comprueba que la guarda falla con cada forma de lectura de
# un secreto de la aplicación y pasa con las legítimas; una guarda que no puede fallar no sirve.
set -uo pipefail
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LISTA="$RAIZ/deploy/secretos-requeridos.txt"
[ -f "$LISTA" ] || { echo "Falta $LISTA" >&2; exit 2; }

# revisar <carpeta de flujos>: imprime «archivo: lectura» por cada lectura prohibida; devuelve 1 si hay alguna.
revisar() {
  python3 - "$LISTA" "$1" <<'PY'
import pathlib
import re
import sys

lista, carpeta = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
prohibidos = {
    linea.split()[0].upper()
    for linea in lista.read_text(encoding="utf-8").splitlines()
    if linea.strip() and not linea.lstrip().startswith("#")
}

TOKEN = re.compile(r"\s*(?:(?P<cadena>'(?:[^']|'')*')|(?P<nombre>[A-Za-z_][A-Za-z0-9_-]*)|(?P<otro>.))", re.S)
HEREDA = re.compile(r"(?:^|[\s{,])secrets\s*:\s*(['\"]?)inherit\1\s*(?:$|[,}#])", re.I | re.M)


def expresiones(texto: str) -> list[str]:
    """Contenido de cada `${{ … }}`; un `}}` dentro de una cadena entre comillas simples no la cierra."""
    encontradas, inicio = [], texto.find("${{")
    while inicio != -1:
        i, en_cadena = inicio + 3, False
        while i < len(texto):
            if texto[i] == "'":
                en_cadena = not en_cadena
            elif not en_cadena and texto.startswith("}}", i):
                break
            i += 1
        encontradas.append(texto[inicio + 3:i])
        inicio = texto.find("${{", i)
    return encontradas


def lecturas(expresion: str) -> list[str]:
    """Lecturas prohibidas de una expresión: un nombre de la lista o un uso de `secrets` sin nombre fijo."""
    tokens = [(m.lastgroup, m.group(m.lastgroup)) for m in TOKEN.finditer(expresion) if m.lastgroup]
    malas = []
    for i, (tipo, valor) in enumerate(tokens):
        if tipo != "nombre" or valor.lower() != "secrets" or (i > 0 and tokens[i - 1][1] == "."):
            continue
        siguiente = tokens[i + 1:i + 4]
        if len(siguiente) >= 2 and siguiente[0][1] == "." and siguiente[1][0] == "nombre":
            nombre = siguiente[1][1]
        elif len(siguiente) == 3 and siguiente[0][1] == "[" and siguiente[1][0] == "cadena" and siguiente[2][1] == "]":
            nombre = siguiente[1][1][1:-1].replace("''", "'")
        else:
            malas.append("secrets sin nombre fijo: ${{" + expresion.strip() + "}}")
            continue
        if nombre.upper() in prohibidos:
            malas.append("secrets." + nombre.upper())
    return malas


malo = False
for flujo in sorted([*carpeta.glob("*.yml"), *carpeta.glob("*.yaml")]):
    texto = flujo.read_text(encoding="utf-8")
    encontradas = [lectura for e in expresiones(texto) for lectura in lecturas(e)]
    encontradas += ["secrets: inherit" for _ in HEREDA.finditer(texto)]
    for lectura in encontradas:
        print(f"  {flujo.name}: {lectura}")
        malo = True
sys.exit(1 if malo else 0)
PY
}

T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
fallos=0
n=0

# caso <se espera: detecta|acepta> <contenido del flujo>
caso() {
  n=$((n+1)); mkdir "$T/$n"; printf '%s\n' "$2" > "$T/$n/a.yml"
  if revisar "$T/$n" >/dev/null; then resultado=acepta; else resultado=detecta; fi
  if [ "$resultado" = "$1" ]; then
    echo "  ✓ autoprueba: $1: $2"
  else
    echo "  ✗ autoprueba: debía $1 y no lo hizo: $2" >&2; fallos=$((fallos+1))
  fi
}

# --- Autoprueba -----------------------------------------------------------------------------
caso detecta 'X: ${{ secrets.DB_PASSWORD }}'
caso detecta "X: \${{ secrets['DB_PASSWORD'] }}"
caso detecta "X: \${{ secrets[ 'db_password' ] }}"
caso detecta 'X: ${{ secrets.db_password }}'
caso detecta 'X: ${{ SECRETS.DB_PASSWORD }}'
caso detecta 'X: ${{ secrets . DB_PASSWORD }}'
caso detecta 'X: ${{ toJSON(secrets) }}'
caso detecta 'X: ${{ secrets[matrix.nombre] }}'
caso detecta "X: \${{ secrets[format('DB_{0}', 'PASSWORD')] }}"
caso detecta 'X: ${{ secrets }}'
caso detecta "$(printf 'run: |\n  echo ${{\n    secrets.DB_PASSWORD\n  }}')"
caso detecta "X: \${{ format('}}', secrets.DB_PASSWORD) }}"
caso detecta "$(printf 'jobs:\n  a:\n    uses: ./.github/workflows/b.yml\n    secrets: inherit')"
caso detecta "$(printf 'jobs:\n  a:\n    uses: ./.github/workflows/b.yml\n    secrets: \x27inherit\x27')"
caso detecta 'jobs: { a: { uses: ./.github/workflows/b.yml, secrets: inherit } }'
caso acepta 'X: ${{ secrets.DEPLOY_SSH_KEY }}'
caso acepta 'X: ${{ secrets.GITHUB_TOKEN }}'
caso acepta "X: \${{ secrets['DEPLOY_HOST'] }}"
caso acepta "$(printf 'jobs:\n  a:\n    uses: ./.github/workflows/b.yml\n    secrets:\n      %s' \
  'CLAVE: ${{ secrets.DEPLOY_SSH_KEY }}')"
caso acepta "$(printf 'on:\n  workflow_call:\n    secrets:\n      CLAVE:\n        required: true')"
caso acepta "X: \${{ format('{0}', 'secrets.DB_PASSWORD') }}"
caso acepta 'X: ${{ github.secrets.DB_PASSWORD }}'
caso acepta 'run: echo "secrets.DB_PASSWORD no se lee aquí: no es una expresión"'

# --- Flujos reales ---------------------------------------------------------------------------
if salida="$(revisar "$RAIZ/.github/workflows")"; then
  echo "  ✓ ningún flujo de .github/workflows lee un secreto de la aplicación"
else
  echo "  ✗ estos flujos leen secretos de la APLICACIÓN (ADR-0019: viven sólo en el servidor):" >&2
  printf '%s\n' "$salida" >&2; fallos=$((fallos+1))
fi

[ "$fallos" = 0 ] && exit 0
echo "$fallos fallo(s)." >&2; exit 1

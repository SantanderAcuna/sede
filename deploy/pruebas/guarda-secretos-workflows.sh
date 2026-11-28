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
# Se lee el YAML YA INTERPRETADO (PyYAML, el mismo que usa `prueba-condiciones-despliegue.py` en la CI),
# no el texto del archivo: así los comentarios no cuentan y un escape de comillas dobles (`"\x42"`, una
# línea partida con `\`) se ve como lo que GitHub recibe. Se recorren todos los valores y claves del
# documento, también los repetidos.
#
# Salida: 0 sin lecturas prohibidas, 1 con alguna (una línea «archivo: lectura» por cada una), 3 si la
# revisión no se pudo completar: un flujo ilegible (YAML inválido, no UTF-8), PyYAML ausente o cualquier
# fallo inesperado del revisor. Un error nunca sale con 1, y un flujo ilegible no impide revisar los demás:
# las lecturas que se hallen se informan igual.
#
# Autoprueba: antes de mirar los flujos reales comprueba que la guarda falla con cada forma de lectura de
# un secreto de la aplicación y pasa con las legítimas; una guarda que no puede fallar no sirve.
set -uo pipefail
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LISTA="$RAIZ/deploy/secretos-requeridos.txt"
[ -f "$LISTA" ] || { echo "Falta $LISTA" >&2; exit 2; }

# revisar <carpeta de flujos>: imprime «archivo: lectura» por cada lectura prohibida. Salida 0, 1 o 3 (arriba).
revisar() {
  python3 - "$LISTA" "$1" <<'PY'
import pathlib
import re
import sys

SIN_LECTURAS, CON_LECTURAS, ERROR = 0, 1, 3

try:
    import yaml
except ImportError as error:
    print(f"  no se puede revisar: falta PyYAML ({error})", file=sys.stderr)
    sys.exit(ERROR)

lista, carpeta = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
# Nombres de la lista, en mayúsculas; `principal()` los carga dentro de la frontera de errores.
prohibidos: set[str] = set()


def cargar_prohibidos() -> None:
    """Lee los nombres de `secretos-requeridos.txt` (primera palabra de cada línea que no es comentario)."""
    prohibidos.update(
        linea.split()[0].upper()
        for linea in lista.read_text(encoding="utf-8").splitlines()
        if linea.strip() and not linea.lstrip().startswith("#")
    )

# Un token de una expresión: una cadena entre comillas simples ('' es una comilla dentro), un nombre
# (letras, cifras, `_` y `-`, como los nombres de propiedad de GitHub) o cualquier otro carácter suelto.
TOKEN = re.compile(r"\s*(?:(?P<cadena>'(?:[^']|'')*')|(?P<nombre>[A-Za-z_][A-Za-z0-9_-]*)|(?P<otro>\S))", re.S)


def nodos(raiz: yaml.Node) -> list[yaml.Node]:
    """Cada nodo del documento una sola vez; un alias de YAML apunta a un nodo ya visto y no se repite."""
    vistos, pendientes, encontrados = set(), [raiz], []
    while pendientes:
        actual = pendientes.pop()
        if id(actual) in vistos:
            continue
        vistos.add(id(actual))
        encontrados.append(actual)
        if isinstance(actual, yaml.SequenceNode):
            pendientes.extend(actual.value)
        elif isinstance(actual, yaml.MappingNode):
            pendientes.extend(hijo for par in actual.value for hijo in par)
    return encontrados


def hereda_secretos(nodo: yaml.Node) -> bool:
    """¿Es este nodo una correspondencia con un par `secrets: inherit`, con o sin comillas?"""
    if not isinstance(nodo, yaml.MappingNode):
        return False
    return any(
        isinstance(clave, yaml.ScalarNode)
        and isinstance(valor, yaml.ScalarNode)
        and clave.value.lower() == "secrets"
        and valor.value.lower() == "inherit"
        for clave, valor in nodo.value
    )


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


def nombre_leido(siguientes: list[tuple[str, str]]) -> str:
    """Nombre fijo que se lee tras `secrets`, o '' si el uso no deja verlo.

    Sólo hay dos formas con nombre fijo: `.NOMBRE` (desreferencia) y `['NOMBRE']` (índice con cadena).
    """
    desreferencia = len(siguientes) >= 2 and siguientes[0][1] == "." and siguientes[1][0] == "nombre"
    if desreferencia:
        return siguientes[1][1]
    indice_con_cadena = (
        len(siguientes) >= 3
        and siguientes[0][1] == "["
        and siguientes[1][0] == "cadena"
        and siguientes[2][1] == "]"
    )
    if indice_con_cadena:
        return siguientes[1][1][1:-1].replace("''", "'")
    return ""


def lecturas(expresion: str) -> list[str]:
    """Lecturas prohibidas de una expresión: un nombre de la lista o un uso de `secrets` sin nombre fijo."""
    tokens = [(m.lastgroup, m.group(m.lastgroup)) for m in TOKEN.finditer(expresion)]
    malas = []
    for i, (tipo, valor) in enumerate(tokens):
        es_contexto = tipo == "nombre" and valor.lower() == "secrets" and (i == 0 or tokens[i - 1][1] != ".")
        if not es_contexto:
            continue
        nombre = nombre_leido(tokens[i + 1:i + 4])
        if nombre == "":
            malas.append("secrets sin nombre fijo: ${{" + expresion.strip() + "}}")
        elif nombre.upper() in prohibidos:
            malas.append("secrets." + nombre.upper())
    return malas


def revisar_flujo(flujo: pathlib.Path) -> list[str]:
    """Lecturas prohibidas de un archivo de flujo, en todos sus documentos YAML."""
    malas = []
    for documento in yaml.compose_all(flujo.read_text(encoding="utf-8")):
        if documento is None:
            continue
        for nodo in nodos(documento):
            if isinstance(nodo, yaml.ScalarNode):
                malas += [lectura for e in expresiones(nodo.value) for lectura in lecturas(e)]
            elif hereda_secretos(nodo):
                malas.append("secrets: inherit")
    return malas


def principal() -> int:
    """Revisa cada flujo de la carpeta; un flujo ilegible se informa y no detiene la revisión de los demás."""
    cargar_prohibidos()
    hay_lecturas, hay_errores = False, False
    for flujo in sorted([*carpeta.glob("*.yml"), *carpeta.glob("*.yaml")]):
        if not flujo.is_file():
            continue
        try:
            malas = revisar_flujo(flujo)
        except (OSError, UnicodeDecodeError, RecursionError, yaml.YAMLError) as error:
            print(f"  {flujo.name}: no se pudo leer ({type(error).__name__}: {error})", file=sys.stderr)
            hay_errores = True
            continue
        for lectura in malas:
            print(f"  {flujo.name}: {lectura}")
            hay_lecturas = True
    if hay_errores:
        return ERROR
    return CON_LECTURAS if hay_lecturas else SIN_LECTURAS


try:
    sys.exit(principal())
except Exception as error:  # Frontera del proceso: un fallo del revisor no puede salir con 1 (detección).
    print(f"  fallo inesperado del revisor ({type(error).__name__}: {error})", file=sys.stderr)
    sys.exit(ERROR)
PY
}

T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
fallos=0
casos=0

# caso <salida esperada: 0 acepta, 1 detecta, 3 error> <contenido del flujo>
caso() {
  local esperada="$1" contenido="$2" obtenida
  casos=$((casos+1)); mkdir "$T/$casos"; printf '%s\n' "$contenido" > "$T/$casos/a.yml"
  revisar "$T/$casos" >/dev/null 2>&1; obtenida=$?
  if [ "$obtenida" = "$esperada" ]; then
    echo "  ✓ autoprueba (salida $obtenida): ${contenido:0:100}"
  else
    echo "  ✗ autoprueba: se esperaba salida $esperada y fue $obtenida: ${contenido:0:100}" >&2; fallos=$((fallos+1))
  fi
}

# --- Autoprueba: lecturas prohibidas (salida 1) -----------------------------------------------
caso 1 'X: ${{ secrets.DB_PASSWORD }}'
caso 1 "X: \${{ secrets['DB_PASSWORD'] }}"
caso 1 "X: \${{ secrets[ 'db_password' ] }}"
caso 1 'X: ${{ secrets.db_password }}'
caso 1 'X: ${{ SECRETS.DB_PASSWORD }}'
caso 1 'X: ${{ secrets . DB_PASSWORD }}'
caso 1 'X: ${{ toJSON(secrets) }}'
caso 1 'X: ${{ secrets[matrix.nombre] }}'
caso 1 "X: \${{ secrets[format('DB_{0}', 'PASSWORD')] }}"
caso 1 'X: ${{ secrets }}'
caso 1 "$(printf 'run: |\n  echo ${{\n    secrets.DB_PASSWORD\n  }}')"
caso 1 "X: \${{ format('}}', secrets.DB_PASSWORD) }}"
caso 1 "$(printf "# ver \${{ it's }}\nX: \${{ secrets.DB_PASSWORD }}\n# don't")"
caso 1 'X: "${{ secrets.D\x42_PASSWORD }}"'
caso 1 'X: "\x24{{ s\x65crets.DB_PASSWORD }}"'
caso 1 "$(printf 'X: "${{ secrets.DB_\\\n  PASSWORD }}"')"
caso 1 "$(printf 'X: ${{ secrets.DEPLOY_HOST }}\nX: ${{ secrets.DB_PASSWORD }}')"
caso 1 "$(printf 'jobs:\n  a:\n    uses: ./.github/workflows/b.yml\n    secrets: inherit')"
caso 1 "$(printf 'jobs:\n  a:\n    uses: ./.github/workflows/b.yml\n    "secrets": \x27inherit\x27')"
caso 1 'jobs: { a: { uses: ./.github/workflows/b.yml, secrets: inherit } }'
# --- Autoprueba: lecturas legítimas (salida 0) ------------------------------------------------
caso 0 'X: ${{ secrets.DEPLOY_SSH_KEY }}'
caso 0 'X: ${{ secrets.GITHUB_TOKEN }}'
caso 0 "X: \${{ secrets['DEPLOY_HOST'] }}"
caso 0 "$(printf 'jobs:\n  a:\n    uses: ./.github/workflows/b.yml\n    secrets:\n      %s' \
  'CLAVE: ${{ secrets.DEPLOY_SSH_KEY }}')"
caso 0 "$(printf 'on:\n  workflow_call:\n    secrets:\n      CLAVE:\n        required: true')"
caso 0 "X: \${{ format('{0}', 'secrets.DB_PASSWORD') }}"
caso 0 'X: ${{ github.secrets.DB_PASSWORD }}'
caso 0 'run: echo "secrets.DB_PASSWORD no se lee aquí, no es una expresión"'
caso 0 '# X: ${{ secrets.DB_PASSWORD }} (comentario: GitHub no lo evalúa)'
# --- Autoprueba: flujos ilegibles (salida 3, nunca 1) -----------------------------------------
caso 3 'a: ['
caso 3 "$(printf 'X: \xff\xfe')"
caso 3 "$(python3 -c "print('X: ' + '[' * 3000 + ']' * 3000)")"
# Sin PyYAML (simulado con un `yaml` que no importa) la salida es 3, no la de una detección.
mkdir "$T/sin-yaml"; echo 'raise ImportError("PyYAML ausente (simulado)")' > "$T/sin-yaml/yaml.py"
mkdir "$T/flujo-sin-yaml"; printf 'X: ${{ secrets.DEPLOY_HOST }}\n' > "$T/flujo-sin-yaml/a.yml"
PYTHONPATH="$T/sin-yaml" revisar "$T/flujo-sin-yaml" >/dev/null 2>&1; obtenida=$?
if [ "$obtenida" = 3 ]; then
  echo "  ✓ autoprueba (salida 3): sin PyYAML"
else
  echo "  ✗ autoprueba: sin PyYAML se esperaba salida 3 y fue $obtenida" >&2; fallos=$((fallos+1))
fi
# Un flujo ilegible no esconde la lectura de otro: se revisan todos y se informan las dos cosas.
mkdir "$T/mixto"; printf 'a: [\n' > "$T/mixto/a.yml"; printf 'X: ${{ secrets.DB_PASSWORD }}\n' > "$T/mixto/b.yml"
mixto="$(revisar "$T/mixto" 2>/dev/null)"; obtenida=$?
if [ "$obtenida" = 3 ] && printf '%s' "$mixto" | grep -q 'b.yml: secrets.DB_PASSWORD'; then
  echo "  ✓ autoprueba (salida 3): un flujo ilegible no esconde la lectura de otro"
else
  echo "  ✗ autoprueba: con un flujo ilegible se perdió la lectura del otro (salida $obtenida)" >&2
  fallos=$((fallos+1))
fi
# Un directorio con nombre de flujo no es un flujo: se salta, no rompe la guarda.
mkdir -p "$T/dir/b.yml"; printf 'X: ${{ secrets.DEPLOY_HOST }}\n' > "$T/dir/a.yml"
if revisar "$T/dir" >/dev/null 2>&1; then
  echo "  ✓ autoprueba (salida 0): un directorio llamado b.yml se salta"
else
  echo "  ✗ autoprueba: un directorio llamado b.yml rompió la guarda" >&2; fallos=$((fallos+1))
fi

# --- Flujos reales ---------------------------------------------------------------------------
salida="$(revisar "$RAIZ/.github/workflows")"; estado=$?
if [ "$estado" = 0 ]; then
  echo "  ✓ ningún flujo de .github/workflows lee un secreto de la aplicación"
elif [ "$estado" = 1 ]; then
  echo "  ✗ estos flujos leen secretos de la APLICACIÓN (ADR-0019: viven sólo en el servidor):" >&2
  printf '%s\n' "$salida" >&2; fallos=$((fallos+1))
else
  echo "  ✗ no se pudieron revisar todos los flujos (salida $estado): el error está arriba" >&2; fallos=$((fallos+1))
  if [ -n "$salida" ]; then
    echo "  ✗ y en los que sí se revisaron hay lecturas de secretos de la APLICACIÓN:" >&2
    printf '%s\n' "$salida" >&2
  fi
fi

[ "$fallos" = 0 ] && exit 0
echo "$fallos fallo(s)." >&2; exit 1

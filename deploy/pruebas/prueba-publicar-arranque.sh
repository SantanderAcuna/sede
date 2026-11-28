#!/usr/bin/env bash
#
# Prueba del tramo REMOTO de `deploy/publicar-entorno.sh` que instala `.env` y `compose.yaml`
# (el «arranque»). No toca ningún servidor: extrae el guion que viajaría por `ssh`, lo ejecuta en
# un directorio temporal y sustituye `sudo`, `chown` e `install -o/-g` por equivalentes sin
# privilegios (la prueba no corre como root).
#
# Qué fija:
#   1. Servidor limpio (sin versión desplegada, sin `docker/`): instala `.env` y `compose.yaml`
#      y NO falla. Un `chown -R … docker/` incondicional abortaba aquí con `set -eu`.
#   2. Con versión desplegada: CONSERVA el `.env` y el `compose.yaml` que corren. Publicarlos desde
#      otra rama y que el despliegue siguiente fallara dejaba código A con configuración B, porque
#      `despliegue-remoto.sh` copia a `.anterior` justo lo que encuentra.
#   3. `FORZAR_ARRANQUE=1` sí los reescribe (arranque deliberado de un entorno).
#
# Salida: 0 si todo se cumple; 1 si algo falla (dice qué).
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPT="$RAIZ/deploy/publicar-entorno.sh"
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
fallos=0
ok()   { printf '  ok     %s\n' "$*"; }
fallo(){ printf '  FALLA  %s\n' "$*" >&2; fallos=$((fallos+1)); }

# --- El guion remoto tal como viajaría -------------------------------------------------------
GUION="$(sed -n "/^GUION=\$(cat <<'EOS'\$/,/^EOS\$/p" "$SCRIPT" | sed '1d;$d')"
[ -n "$GUION" ] || { echo "No encontré el guion remoto en $SCRIPT" >&2; exit 1; }

# --- Utilidades sin privilegios ---------------------------------------------------------------
mkdir "$T/bin"
cat > "$T/bin/sudo" <<'S'
#!/usr/bin/env bash
while [ $# -gt 0 ]; do case "$1" in -S|-k) shift ;; -p) shift 2 ;; *) break ;; esac; done
cat >/dev/null
exec "$@"
S
printf '#!/usr/bin/env bash\nexit 0\n' > "$T/bin/chown"
cat > "$T/bin/install" <<'S'
#!/usr/bin/env bash
args=(); while [ $# -gt 0 ]; do case "$1" in -o|-g) shift 2 ;; *) args+=("$1"); shift ;; esac; done
exec /usr/bin/install "${args[@]}"
S
chmod +x "$T/bin/"*

# --- Un paquete como el que arma publicar-entorno.sh -------------------------------------------
mkdir "$T/pkg"; printf 'CLAVE=NUEVA\n' > "$T/pkg/sede.env"; printf 'servicios: nuevo\n' > "$T/pkg/compose.yaml"
tar -czf "$T/entorno.tar.gz" -C "$T/pkg" sede.env compose.yaml
B64_ENV="$(base64 -w0 "$T/entorno.tar.gz")"; B64_PW="$(printf x | base64 -w0)"

correr() {   # correr <caso> [FORZAR]; deja DESTINO y ESTADO propios del caso
  local caso="$1" forzar="${2:-}" g
  DEST="$T/$caso/opt"; EST="$T/$caso/var"; TMPE="$T/$caso/tmp"
  g="${GUION//@@PW@@/$B64_PW}"; g="${g//@@DESTINO@@/$DEST}"; g="${g//@@ENTORNO@@/e}"
  g="${g//@@ENV@@/$B64_ENV}"; g="${g//@@FORZAR@@/$forzar}"
  g="${g//\/var\/lib\/sede\/e/$EST}"; g="${g//\/tmp\/sede-entorno/$TMPE}"
  mkdir -p "$EST"
  PATH="$T/bin:$PATH" bash -c "$g" >"$T/$caso.salida" 2>&1
}
contenido() { [ -f "$1" ] && cat "$1" || echo "(no existe)"; }

echo "1. servidor limpio"
if correr limpio; then ok "no falla sin docker/ ni versión"; else fallo "falló en servidor limpio: $(tail -3 "$T/limpio.salida" | tr '\n' ' ')"; fi
[ "$(contenido "$T/limpio/opt/.env")" = "CLAVE=NUEVA" ] && ok ".env instalado" || fallo ".env no quedó instalado"
[ "$(contenido "$T/limpio/opt/compose.yaml")" = "servicios: nuevo" ] && ok "compose.yaml instalado" || fallo "compose.yaml no quedó instalado"

echo "2. con versión desplegada"
mkdir -p "$T/vivo/opt" "$T/vivo/var"; printf 'CLAVE=VIVA\n' > "$T/vivo/opt/.env"; printf 'servicios: vivo\n' > "$T/vivo/opt/compose.yaml"
printf 'abc123' > "$T/vivo/var/version"
if correr vivo; then ok "no falla"; else fallo "falló con versión desplegada: $(tail -3 "$T/vivo.salida" | tr '\n' ' ')"; fi
[ "$(contenido "$T/vivo/opt/.env")" = "CLAVE=VIVA" ] && ok ".env en marcha conservado" || fallo ".env en marcha fue reescrito: $(contenido "$T/vivo/opt/.env")"
[ "$(contenido "$T/vivo/opt/compose.yaml")" = "servicios: vivo" ] && ok "compose.yaml en marcha conservado" || fallo "compose.yaml en marcha fue reescrito"

echo "3. FORZAR_ARRANQUE=1"
mkdir -p "$T/forzado/opt" "$T/forzado/var"; printf 'CLAVE=VIVA\n' > "$T/forzado/opt/.env"; printf 'abc123' > "$T/forzado/var/version"
if correr forzado 1; then ok "no falla"; else fallo "falló al forzar: $(tail -3 "$T/forzado.salida" | tr '\n' ' ')"; fi
[ "$(contenido "$T/forzado/opt/.env")" = "CLAVE=NUEVA" ] && ok "el arranque se reescribe cuando se fuerza" || fallo "forzar no reescribió el .env"

[ "$fallos" = 0 ] && { echo "OK: el arranque no pisa un entorno desplegado."; exit 0; }
echo "$fallos comprobación(es) fallaron." >&2; exit 1

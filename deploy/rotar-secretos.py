#!/usr/bin/env python3
"""Rota los secretos de la sede y los registra en los dos archivos locales.

Existe por una razón concreta: rotar a mano es generar un valor, aplicarlo en un
sitio y olvidar registrarlo en otro. Así fue como la contraseña de `ops` del
droplet nuevo quedó asignada y sin registrar, y como un registro acabó apuntando
a un servidor que ya no existía. Aquí la generación, la aplicación y el registro
ocurren en la misma ejecución, o no ocurren.

Nunca imprime un valor. Sólo longitudes y comprobaciones.

    ./rotar-secretos.py --comprobar            # no cambia nada
    ./rotar-secretos.py --aplicacion           # secretos de la aplicación
    ./rotar-secretos.py --aplicacion --github  # y los empuja a GitHub
    ./rotar-secretos.py --ops                  # contraseña de ops en el servidor
    ./rotar-secretos.py --servidor             # datos del servidor en el registro

Necesita los dos archivos de registro:
    .accesos.local.env   forma clave=valor, para la máquina
    pass.md              forma legible, para las personas
"""
from __future__ import annotations

import argparse
import base64
import re
import secrets
import string
import subprocess
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[1]
ENV = RAIZ / ".accesos.local.env"
PASS = RAIZ / "pass.md"
VERIFICADOR = RAIZ / "deploy/droplet/verificar-contrasena.py"
USUARIO_ADMIN = "ops"

# Sólo se rellena con --ip, para corregir un registro que apunta a un servidor
# que ya no existe. En el uso normal el destino sale siempre del registro.
IP_FORZADA: str | None = None

# clave en pass.md -> (clave de staging en .env, clave de producción en .env)
APLICACION = {
    "APP_KEY": ("APP_KEY_STAGING", "APP_KEY_PRODUCTION"),
    "DB_PASSWORD": ("DB_PASSWORD_STAGING", "DB_PASSWORD_PRODUCTION"),
    "REDIS_PASSWORD": ("REDIS_PASSWORD_STAGING", "REDIS_PASSWORD_PRODUCTION"),
    "S3_ACCESS_KEY": ("S3_ACCESS_KEY_STAGING", "S3_ACCESS_KEY_PRODUCTION"),
    "S3_SECRET_KEY": ("S3_SECRET_KEY_STAGING", "S3_SECRET_KEY_PRODUCTION"),
    "BACKUP_PASSPHRASE": ("BACKUP_PASSPHRASE_STAGING", "BACKUP_PASSPHRASE_PRODUCTION"),
}

# Alfabeto sin caracteres que rompan una línea de órdenes, un archivo de entorno
# ni el formato usuario:contraseña que espera chpasswd.
ALFABETO = string.ascii_letters + string.digits + "-_.+@%"


def generar_hex(bytes_: int = 24) -> str:
    return secrets.token_hex(bytes_)


def generar_app_key() -> str:
    """Una clave de Laravel: base64: seguido de 32 bytes en base64."""
    return "base64:" + base64.b64encode(secrets.token_bytes(32)).decode()


def generar_contrasena(largo: int = 24) -> str:
    return "".join(secrets.choice(ALFABETO) for _ in range(largo))


def exigir_no_rastreado() -> None:
    """Interlock: si pass.md vuelve a estar rastreado, escribir secretos en él
    los publica en el próximo push. Es exactamente lo que ya pasó una vez."""
    r = subprocess.run(
        ["git", "ls-files", "--error-unmatch", "pass.md"],
        cwd=RAIZ, capture_output=True, text=True,
    )
    if r.returncode == 0:
        sys.exit(
            "ABORTADO: pass.md está rastreado por git.\n"
            "Escribir secretos en él los publicaría. Retíralo primero con:\n"
            "    git rm --cached pass.md  &&  git commit"
        )


def leer_env() -> list[str]:
    return ENV.read_text(encoding="utf-8").splitlines()


def escribir_env(lineas: list[str]) -> None:
    ENV.write_text("\n".join(lineas) + "\n", encoding="utf-8")
    ENV.chmod(0o600)


def fijar_env(lineas: list[str], clave: str, valor: str) -> None:
    patron = re.compile(rf"^{re.escape(clave)}=")
    for i, linea in enumerate(lineas):
        if patron.match(linea):
            lineas[i] = f"{clave}={valor}"
            return
    lineas.append(f"{clave}={valor}")


def leer_pass() -> list[str]:
    return PASS.read_text(encoding="utf-8").splitlines()


def escribir_pass(lineas: list[str]) -> None:
    PASS.write_text("\n".join(lineas) + "\n", encoding="utf-8")
    PASS.chmod(0o600)


def fijar_fila_pass(lineas: list[str], clave: str, *valores: str) -> bool:
    """Sustituye las columnas de valor de una fila de tabla de pass.md."""
    for i, linea in enumerate(lineas):
        if not linea.startswith("|"):
            continue
        celdas = [c.strip() for c in linea.strip().strip("|").split("|")]
        if not celdas or celdas[0].strip("`") != clave:
            continue
        nuevas = [celdas[0]] + [f"`{v}`" for v in valores] + celdas[1 + len(valores):]
        lineas[i] = "| " + " | ".join(nuevas) + " |"
        return True
    return False



def fijar_contrasena_ops(lineas: list[str], valor: str) -> bool:
    """La contraseña de ops no está en una tabla con columnas por entorno, sino en
    un bloque de texto, así que no sirve `fijar_fila_pass`. Se sustituye la PRIMERA
    línea «Contraseña : …», que es la de la sección de cuentas.

    Que esto falte fue un defecto real: `--ops` actualizaba el archivo para la
    máquina y dejaba el archivo para las personas con la contraseña ANTERIOR, es
    decir, el registro legible decía algo que ya no era cierto. Es exactamente el
    fallo que esta herramienta existe para evitar.
    """
    patron = re.compile(r"^Contraseña\s*:\s*\S+\s*$")
    for i, linea in enumerate(lineas):
        if patron.match(linea):
            lineas[i] = f"Contraseña       : {valor}"
            return True
    return False

def gh(*args: str, entrada: str | None = None, intentos: int = 4) -> None:
    """gh con reintentos: la API de GitHub devuelve 500 de forma transitoria, y un
    fallo a mitad deja GitHub y el registro desalineados."""
    import time

    for intento in range(1, intentos + 1):
        r = subprocess.run(["gh", *args], input=entrada, capture_output=True, text=True, cwd=RAIZ)
        if r.returncode == 0:
            return
        transitorio = "HTTP 5" in r.stderr or "timeout" in r.stderr.lower()
        if not transitorio or intento == intentos:
            sys.exit(f"gh {' '.join(args)} falló tras {intento} intento(s): {r.stderr.strip()}")
        espera = 2 ** intento
        print(f"    (intento {intento} falló con un error transitorio; reintento en {espera}s)")
        time.sleep(espera)


def empujar_github() -> None:
    """Empuja a GitHub los valores YA registrados, sin generar ninguno nuevo.

    Es lo que hace falta cuando el empuje falla a mitad: regenerar cambiaría
    valores que quizá ya se aplicaron, y dejaría el registro y GitHub diciendo
    cosas distintas."""
    exigir_no_rastreado()
    lineas = leer_env()
    valores = {}
    for linea in lineas:
        if "=" in linea and not linea.startswith("#"):
            k, v = linea.split("=", 1)
            valores[k.strip()] = v.strip()

    print("Empujando a los secretos de entorno de GitHub (sin regenerar)")
    for clave, (k_staging, k_produccion) in APLICACION.items():
        for entorno, k_env in (("staging", k_staging), ("production", k_produccion)):
            valor = valores.get(k_env)
            if not valor:
                sys.exit(f"ABORTADO: falta {k_env} en .accesos.local.env.")
            gh("secret", "set", clave, "--env", entorno, entrada=valor)
            print(f"  {entorno}/{clave} actualizado")


def resumen(clave: str, valor: str) -> str:
    return f"  {clave:<26} {len(valor):>3} caracteres, empieza por «{valor[:6]}…»"



def valor_de(clave: str) -> str:
    for linea in leer_env():
        if linea.startswith(f"{clave}="):
            return linea.split("=", 1)[1].strip()
    sys.exit(f"ABORTADO: falta {clave} en .accesos.local.env.")

def servidor_ip() -> str:
    """El destino sale del registro, no de una constante escrita aquí: una IP
    fija es exactamente cómo el registro acabó apuntando a un servidor muerto.

    Si el registro apunta a un servidor que ya no existe, hay un problema de
    arranque: para arreglarlo hace falta hablar con el servidor nuevo antes de
    que el registro lo mencione. Para eso está --ip."""
    if IP_FORZADA:
        return IP_FORZADA
    for linea in leer_env():
        if linea.startswith("SERVIDOR_IP="):
            return linea.split("=", 1)[1].strip()
    sys.exit("ABORTADO: no hay SERVIDOR_IP en .accesos.local.env. Usa --servidor --ip <dirección>.")


def en_el_servidor(guion: str, privilegiado: bool = False) -> subprocess.CompletedProcess[str]:
    """Ejecuta un guion en el servidor, como la cuenta de administración.

    NUNCA como `root`: el acceso de `root` por SSH está cerrado, y darlo por
    supuesto fue un defecto real de esta herramienta —seguía conectándose como
    `root@`, la conexión fallaba y el fallo se veía como «no se pudo verificar»,
    que apunta al sitio equivocado.

    Si el guion necesita privilegios, se pasa por `sudo` con la contraseña de
    administración. El guion viaja codificado y se ejecuta desde un archivo
    temporal con permisos 600, así que ni el guion ni la contraseña aparecen en
    el listado de procesos del servidor.
    """
    destino = servidor_ip()
    print(f"  servidor de destino: {destino} (como {USUARIO_ADMIN})")

    if privilegiado:
        import base64 as _b64
        b64_guion = _b64.b64encode(guion.encode()).decode()
        b64_pw = _b64.b64encode(valor_de("OPS_PASSWORD_INICIAL").encode()).decode()
        guion = "\n".join([
            "umask 077",
            f"printf %s '{b64_guion}' | base64 -d > /tmp/sede-rotar.sh",
            "chmod 600 /tmp/sede-rotar.sh",
            f"PWSEC=$(printf %s '{b64_pw}' | base64 -d)",
            "printf '%s\\n' \"$PWSEC\" | sudo -S -k -p '' bash /tmp/sede-rotar.sh",
            "CODIGO=$?",
            "rm -f /tmp/sede-rotar.sh",
            "unset PWSEC",
            "exit $CODIGO",
        ])

    return subprocess.run(
        ["ssh", "-F", "/dev/null", "-i", str(Path.home() / ".ssh/id_ed25519"),
         "-o", "BatchMode=yes", "-o", "StrictHostKeyChecking=no", "-o", "ConnectTimeout=25",
         f"{USUARIO_ADMIN}@{destino}", "bash -s"],
        input=guion, capture_output=True, text=True,
    )


def comprobar() -> None:
    exigir_no_rastreado()
    print("Comprobaciones")
    faltantes = [p for p in (PASS, ENV, VERIFICADOR) if not p.exists()]
    for p in (PASS, ENV, VERIFICADOR):
        rel = p.relative_to(RAIZ)
        print(f"  {str(rel):<30} {'presente' if p.exists() else 'FALTA'}")
    print(f"  pass.md rastreado por git     : no (correcto)")
    if faltantes:
        sys.exit(f"\nABORTADO: faltan {len(faltantes)} archivo(s) del registro.")
    lineas = leer_env()
    presentes = [
        c for par in APLICACION.values() for c in par
        if any(l.startswith(f"{c}=") for l in lineas)
    ]
    total = len(APLICACION) * 2
    print(f"  claves de .env presentes      : {len(presentes)}/{total}")
    ausentes = [c for par in APLICACION.values() for c in par if c not in presentes]
    if ausentes:
        print(f"  ausentes                      : {', '.join(ausentes)}")
    ip = next((l.split("=", 1)[1] for l in lineas if l.startswith("SERVIDOR_IP=")), "—")
    print(f"  servidor registrado           : {ip}")
    print("  Comprueba que es el servidor correcto; si no, corrige con --servidor.")


def rotar_aplicacion(hacia_github: bool) -> None:
    exigir_no_rastreado()
    env_lineas = leer_env()
    pass_lineas = leer_pass()
    generados: list[tuple[str, str]] = []

    for clave, (k_staging, k_produccion) in APLICACION.items():
        valor_staging = generar_app_key() if clave == "APP_KEY" else generar_hex()
        valor_produccion = generar_app_key() if clave == "APP_KEY" else generar_hex()

        fijar_env(env_lineas, k_staging, valor_staging)
        fijar_env(env_lineas, k_produccion, valor_produccion)

        if not fijar_fila_pass(pass_lineas, clave, valor_staging, valor_produccion):
            sys.exit(f"ABORTADO: no encontré la fila «{clave}» en pass.md. No se escribió nada.")

        generados += [(k_staging, valor_staging), (k_produccion, valor_produccion)]

    # Los dos archivos se escriben sólo después de que TODAS las filas cuadren,
    # para no dejar un registro a medias si algo falla a mitad de camino.
    escribir_env(env_lineas)
    escribir_pass(pass_lineas)

    print("Secretos de la aplicación rotados y registrados")
    for clave, valor in generados:
        print(resumen(clave, valor))

    if not hacia_github:
        print("\n  (no se tocó GitHub: repite con --github para empujarlos)")
        return

    print("\nEmpujando a los secretos de entorno de GitHub")
    for clave, valor in generados:
        entorno = "staging" if clave.endswith("_STAGING") else "production"
        nombre = clave[: -len("_STAGING")] if entorno == "staging" else clave[: -len("_PRODUCTION")]
        # El valor va por la entrada estándar: nunca aparece en la línea de órdenes.
        gh("secret", "set", nombre, "--env", entorno, entrada=valor)
        print(f"  {entorno}/{nombre} actualizado")


def rotar_ops() -> None:
    exigir_no_rastreado()
    nueva = generar_contrasena()
    b64 = base64.b64encode(nueva.encode()).decode()
    verificador = VERIFICADOR.read_text(encoding="utf-8")
    b64_verificador = base64.b64encode(verificador.encode()).decode()

    guion = "\n".join([
        "set -e",
        f"PW=$(printf %s '{b64}' | base64 -d)",
        'printf "ops:%s\\n" "$PW" | chpasswd',
        "chage -M 90 -m 1 -W 14 ops",
        f"printf %s '{b64_verificador}' | base64 -d > /tmp/vc.py",
        'printf "%s\\n" "$PW" | python3 /tmp/vc.py ops 2>/dev/null | tail -1',
        "rm -f /tmp/vc.py",
    ])

    r = en_el_servidor(guion, privilegiado=True)
    salida = [l for l in r.stdout.splitlines() if l.strip() and not l.startswith("**")]
    print("Contraseña de ops en el servidor")
    for linea in salida:
        print(f"  {linea}")

    if "COINCIDE" not in r.stdout:
        # Se muestra la salida de error de la conexión: sin ella, «no se pudo
        # verificar» apunta al sitio equivocado y se busca el fallo en la
        # comprobación cuando el problema estaba en la conexión.
        detalle = (r.stderr or "").strip().splitlines()
        if detalle:
            print("  la conexión o el cambio fallaron:")
            for linea in detalle[-4:]:
                print(f"    {linea}")
        sys.exit("ABORTADO: la comprobación contra /etc/shadow no confirmó el cambio. NO se registra.")

    # Los dos registros se actualizan antes de escribir ninguno: si uno falla, no
    # se deja el otro a medias. Un registro a medias es peor que ninguno, porque
    # se consulta y se cree.
    lineas_env = leer_env()
    fijar_env(lineas_env, "OPS_PASSWORD_INICIAL", nueva)

    lineas_pass = leer_pass()
    if not fijar_contrasena_ops(lineas_pass, nueva):
        sys.exit("ABORTADO: no encontré la contraseña de ops en pass.md. NO se registra nada.")

    escribir_env(lineas_env)
    escribir_pass(lineas_pass)

    print(resumen("OPS_PASSWORD_INICIAL", nueva))
    print("  pass.md                       actualizado")
    print("\n  La comprobación confirmó que el servidor y los dos registros coinciden.")


def actualizar_servidor() -> None:
    import json
    import urllib.request

    def metadato(campo: str) -> str:
        url = f"http://169.254.169.254/metadata/v1/{campo}"
        with urllib.request.urlopen(url, timeout=5) as r:
            return r.read().decode().strip()

    # Se leen desde el propio servidor: son los únicos datos que no dependen de
    # que yo recuerde bien una cifra.
    guion = "for c in id region hostname; do echo \"$c=$(curl -s --max-time 6 http://169.254.169.254/metadata/v1/$c)\"; done; ip -6 addr show scope global | awk '/inet6/{print \"ipv6=\" $2}' | cut -d/ -f1; echo \"ipv4=$(hostname -I | awk '{print $1}')\"; . /etc/os-release; echo \"so=$PRETTY_NAME\""
    r = en_el_servidor(guion)
    datos = dict(
        linea.split("=", 1) for linea in r.stdout.splitlines() if "=" in linea
    )

    lineas = leer_env()
    fijar_env(lineas, "SERVIDOR_IP", datos["ipv4"])
    fijar_env(lineas, "SERVIDOR_ID", datos["id"])
    fijar_env(lineas, "SERVIDOR_HOSTNAME", datos["hostname"])
    fijar_env(lineas, "SERVIDOR_REGION", datos["region"])
    escribir_env(lineas)

    pass_lineas = leer_pass()
    fijar_fila_pass(pass_lineas, "Dirección IPv4", datos["ipv4"])
    fijar_fila_pass(pass_lineas, "Dirección IPv6", datos["ipv6"])
    fijar_fila_pass(pass_lineas, "Identificador", datos["id"])
    fijar_fila_pass(pass_lineas, "Nombre", datos["hostname"])
    fijar_fila_pass(pass_lineas, "Región", datos["region"])
    fijar_fila_pass(pass_lineas, "Sistema", datos["so"])
    fijar_fila_pass(pass_lineas, "DEPLOY_HOST", datos["ipv4"])
    escribir_pass(pass_lineas)

    print("Registro del servidor actualizado")
    for k, v in datos.items():
        print(f"  {k:<10} {v}")

    print("\n  Falta DEPLOY_KNOWN_HOSTS: las claves de host cambiaron con el servidor.")
    print("  Se obtienen del propio droplet, no por ssh-keyscan (que el cortafuegos frena):")
    print("    ssh ops@<servidor> 'sudo cat /etc/ssh/ssh_host_*_key.pub'")


def main() -> None:
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--comprobar", action="store_true", help="no cambia nada")
    p.add_argument("--aplicacion", action="store_true", help="rota APP_KEY, contraseñas y frase de copias")
    p.add_argument("--github", action="store_true", help="empuja también a los secretos de GitHub")
    p.add_argument("--empujar", action="store_true", help="empuja los valores ya registrados, sin regenerar")
    p.add_argument("--ops", action="store_true", help="rota la contraseña de ops en el servidor")
    p.add_argument("--servidor", action="store_true", help="actualiza los datos del servidor")
    p.add_argument("--ip", help="dirección del servidor, sólo para corregir un registro obsoleto")
    a = p.parse_args()

    global IP_FORZADA
    IP_FORZADA = a.ip

    if a.comprobar:
        comprobar()
    if a.aplicacion:
        rotar_aplicacion(a.github)
    if a.empujar:
        empujar_github()
    if a.ops:
        rotar_ops()
    if a.servidor:
        actualizar_servidor()
    if not any([a.comprobar, a.aplicacion, a.ops, a.servidor, a.empujar]):
        p.print_help()


if __name__ == "__main__":
    main()

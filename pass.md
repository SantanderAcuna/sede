# Credenciales de la Sede Electrónica

> ## ADVERTENCIA
>
> **Este archivo contiene valores en claro.** Está excluido del control de versiones
> por `.gitignore` (regla `/pass.md`), y el repositorio es **público**: si se empuja
> una sola vez, la contraseña queda en el historial de forma permanente aunque
> después se borre.
>
> No lo copies, no lo pegues en un chat y no lo muevas dentro del repositorio.
> Su destino final es el **gestor de secretos de la entidad**. Este archivo es un
> puente, no un destino.
>
> **Generado:** 2026-09-30 · **Permisos:** `600` · **Ubicación:** fuera del control de versiones

---

## 1. El servidor

| Dato | Valor |
|---|---|
| Dirección IPv4 | `198.199.89.119` |
| Dirección IPv6 | `2604:a880:0400:d1::5:125a:a001` |
| Identificador | `604812963` |
| Nombre | `sede-electronica` |
| Región | `nyc1` (Nueva York 1) |
| Sistema | Ubuntu 26.04.1 LTS |
| Dominio de pruebas | `staging.santamarta.gov.co` |
| Cortafuegos del host | Activo · 22 con límite de tasa, 80 y 443 |
| Fail2Ban | Activo · jails `sshd` y `recidive` |

---

## 2. Cuentas

### `ops` — administración (tuya)

```
Usuario          : ops
Contraseña       : D/BqyMms1GmKx2UNPQUfJS9F
```

**Esta contraseña es inicial y el sistema ya obliga a cambiarla:** al entrar por primera
vez, el servidor pedirá una nueva antes de dejarte hacer nada. La política es de 90 días
de vigencia, 1 día mínimo entre cambios y 14 días de aviso previo.

**Cómo entrar:**

```bash
ssh -F /dev/null -i ~/.ssh/id_ed25519 ops@198.199.89.119
```

El `-F /dev/null` es necesario porque en esta máquina el archivo
`/etc/ssh/ssh_config.d/20-systemd-ssh-proxy.conf` figura con propietario incorrecto y
`ssh` se niega a arrancar sin él. Compruébalo con:

```bash
stat -c '%U:%G %a' /etc/ssh/ssh_config.d/*.conf
```

Si aparecen como `nobody`, se corrige con
`sudo chown root:root /etc/ssh/ssh_config.d/*.conf`.

**Qué puede hacer:** todo, elevando con `sudo` y contraseña. Sustituye al acceso de `root`,
que sigue habilitado con llave y se cerrará al terminar el aprovisionamiento.

Verificado al crear la cuenta:

```
dentro como:     ops@sede-electronica
grupos:          ops adm sudo sede-ssh systemd-journal
home:            /home/ops
sudo:            pide contraseña (correcto)
```

### `deploy` — canalización

```
Usuario          : deploy
Contraseña       : ninguna utilizable (bloqueada a propósito)
```

No entra con contraseña ni con shell libre: su llave está **restringida a un único
comando**, el script de despliegue. Sin túneles, sin reenvío de agente, sin terminal.

---

## 3. Llaves SSH

| Llave | Huella | Dónde | Estado |
|---|---|---|---|
| **Personal (tuya)** | `SHA256:v5E0mAsz851iLBKnn1uTPf78f9EqJeCh50Z7U6rsFxE` | `~/.ssh/id_ed25519`, con frase de paso | Autorizada para `ops` y para `root` |
| **Despliegue** | `SHA256:/T+kJDhzAUsICDCf0dxi3yCwqlDL+tY2jdP809KezOI` | `.claves/deploy_ed25519` | Autorizada para `deploy`, sin frase de paso |

La llave privada de despliegue **no se copia aquí**: vive en `.claves/deploy_ed25519`
(también ignorado por el control de versiones) y su valor está publicado en el secreto
`DEPLOY_SSH_KEY` de GitHub. Duplicarla en dos archivos multiplicaría los sitios por donde
puede filtrarse.

---

## 4. Secretos de la aplicación

Un juego **distinto por entorno**. Compartirlos haría que un incidente en pruebas alcanzara
producción.

| Secreto | `staging` | `production` |
|---|---|---|
| `APP_KEY` | `base64:I/FJb3+QaTkn19v+Ah9LLnPdG4AfA/3AzzrzKsZsQyc=` | `base64:b3yIcj9irnWuia3OFypdo3ePEh5znFSD/FulMbOUcZg=` |
| `DB_PASSWORD` | `9b2cebbe6812d98de13fca27fa4079eeeedce9a86db8f381` | `9a8f2c0a0198e8a4f881cf1129698aed446e990cbd2afd8d` |
| `REDIS_PASSWORD` | `df2b57da117ef2169a453228e1e0d505f7d9986ab86887a5` | `ab35a0f872bbe8fec403cf4e826f2e33dba9d36f775c5356` |
| `S3_ACCESS_KEY` | `63f83a8643b75d7839e7063eb2fe0c5cd6a3872ccfa43e42` | `73f6f03e568cdd23d8dafed7fafafc90895f524928a82012` |
| `S3_SECRET_KEY` | `8f8bc5e6028fc0806e0fe419705a1da8936ee9ad53427155` | `2c2fe819ffbbd86485fa7a793099ae28f991ff4372d34052` |
| `BACKUP_PASSPHRASE` | `9b2116bfc0c310cf795f2f200423fdef4df6e98f8c70d443` | `fad2bb1b29853b4933317b7b9006b7e30c10662b3f632dce` |

### Sobre `APP_KEY`

**Si se pierde, los datos cifrados de la base quedan irrecuperables** aunque la copia de
seguridad esté intacta. Debe custodiarse en tres sitios: el secreto del entorno, el gestor
de la entidad y un sobre cerrado en custodia física.

### Sobre `BACKUP_PASSPHRASE`

Es la que descifra las copias de seguridad. **La copia operativa vive en el servidor**,
porque el servidor debe poder cifrar sin intervención humana; la contingencia real es la
custodia externa. Si se pierde, las copias son inservibles.

---

## 5. Secretos de GitHub

En `github.com/SantanderAcuna/sede` → Settings → Secrets and variables → Actions.

### De repositorio (compartidos por todos los despliegues)

| Secreto | Valor | Contenido |
|---|---|---|
| `DEPLOY_HOST` | `198.199.89.119` | Destino del despliegue |
| `DEPLOY_PORT` | `22` | Puerto SSH |
| `DEPLOY_USER` | `deploy` | Cuenta de despliegue |
| `DEPLOY_SSH_KEY` | *(en `.claves/deploy_ed25519`)* | Llave privada de despliegue |
| `DEPLOY_KNOWN_HOSTS` | *(claves de host del servidor)* | Evita aceptar cualquier anfitrión |

### Por entorno

`staging` y `production` contienen los seis secretos de la sección 4, cada uno con su valor.

### Conservados sin borrar

| Secreto | Estado |
|---|---|
| `SEDE_PAGOS_SECRETO` | **Sin tocar.** Lo entrega la pasarela |
| `SEDE_CORREO_CERTIFICADO_SECRETO` | **Sin tocar.** Lo entrega el operador postal |

Estos dos existían del proyecto anterior, cuando la documentación decía que **no debían
crearse**. GitHub no permite leer un secreto, así que **sólo tú puedes saber si contienen un
valor de relleno o uno real**: si son de relleno, se borran; si son reales, se migran a la
custodia de la entidad.

---

## 6. Qué falta, y de quién depende

| Falta | Depende de |
|---|---|
| Ampliar el servidor a 8 GB | Titular |
| Decidir el dominio de producción | Titular |
| Token de API de DigitalOcean (cortafuegos del proveedor) | Titular |
| Confirmar quién administra la zona de Cloudflare | Titular |
| Revisar los dos secretos de terceros | Titular |
| Cerrar el acceso de `root` por SSH | Se hace al final del aprovisionamiento |
| Credenciales de identidad digital, pasarela y correo certificado | Terceros, por convenio |

---

## 7. Qué cambiar y cuándo

| Qué | Cuándo | Quién |
|---|---|---|
| Contraseña de `ops` | **En el primer ingreso** — el sistema ya lo exige | Titular |
| Contraseñas de base y caché | Trimestral | Canalización |
| `APP_KEY` | Trimestral, con procedimiento y conservando la anterior | Entidad |
| Llave de despliegue | Trimestral | Titular |
| Llave personal | Anual, o ante cualquier sospecha | Titular |
| `BACKUP_PASSPHRASE` | Anual | Entidad |

---

## 8. Cómo quitarle este archivo de encima

Cuando los valores estén en el gestor de secretos de la entidad:

```bash
shred -u pass.md .accesos.local.env
```

`shred` sobrescribe antes de borrar, que es lo que corresponde a un archivo con
contraseñas. Un `rm` deja el contenido recuperable en el disco.

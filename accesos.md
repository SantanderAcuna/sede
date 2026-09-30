# Registro de accesos de la Sede Electrónica

> **Este archivo no contiene valores. Nunca.** Sólo nombres, para qué sirve cada uno, dónde
> vive y quién lo custodia. El repositorio es **público**: un valor escrito aquí sería un
> valor filtrado. Las huellas de llaves públicas sí se incluyen, porque son información
> pública por definición.
>
> **Versión:** 1.0 · **Fecha:** 2026-09-30 · **Estado:** vivo — se actualiza con cada alta,
> baja o rotación.

---

## 1. Cómo se lee

| Columna | Significado |
|---|---|
| **Nombre** | El identificador exacto con el que se referencia |
| **Para qué** | Qué se rompe si falta |
| **Dónde vive** | El lugar donde está el valor |
| **Custodia** | Quién responde por él |
| **Estado** | Existe · hay que generarlo · pendiente de un tercero · obsoleto |

---

## 2. Accesos que ya existen y funcionan

| Nombre | Para qué | Dónde vive | Custodia | Estado |
|---|---|---|---|---|
| Llave SSH personal | Administrar el droplet | `~/.ssh/id_ed25519` y agente del llavero | Titular | **Existe** — huella `SHA256:v5E0mAsz851iLBKnn1uTPf78f9EqJeCh50Z7U6rsFxE`, comentario `santanderjose19@gmail.com`, **con frase de paso** |
| Acceso al droplet | Entrar como administrador | `/root/.ssh/authorized_keys` de `198.199.89.119` | Titular | **Existe** — es la única llave autorizada |
| Cuenta de GitHub | Repositorio y canalizaciones | Sesión de `gh` en esta máquina | Titular | **Existe** — cuenta `SantanderAcuna`, permisos de **administración** sobre el repositorio |
| Repositorio | Código y automatización | `github.com/SantanderAcuna/sede` | Titular | **Existe** — **público**, rama por defecto `master` |
| Llave SSH de despliegue | Que la canalización despliegue | `.claves/deploy_ed25519` (ignorada) y secreto de GitHub | Canalización | **Existe** — huella `SHA256:/T+kJDhzAUsICDCf0dxi3yCwqlDL+tY2jdP809KezOI`, sin frase de paso, **restringida a un solo comando** |
| Cuenta `ops` | Administración del servidor | Servidor, con `sudo` | Titular | **Existe** — contraseña inicial en el archivo local; **cámbiala en el primer ingreso** |
| Cuenta `deploy` | Canalización | Servidor, sin contraseña utilizable | Canalización | **Existe** — sólo puede ejecutar `/usr/local/bin/desplegar.sh` |

### Sobre la llave personal

Entra como `root` hoy. Cuando se cierre el acceso de `root` (§14.1 del plan), **la misma
llave entrará como `ops`**: la llave no cambia, cambia la cuenta. El `root` por SSH queda
deshabilitado y sólo se usa la consola del proveedor para emergencias.

Tiene frase de paso y la desbloquea el agente del llavero. Eso **es correcto para una
persona** y **no sirve para una máquina**: ningún ejecutor de GitHub puede teclear esa
frase.

---

## 3. Accesos que hay que generar

Todos los genera la canalización, salvo los dos primeros.

| Nombre | Para qué | Dónde vivirá | Estado |
|---|---|---|---|
| Llave SSH de despliegue | Que la canalización entre al servidor | Secreto de repositorio | **Hecho** — restringida a un único comando |
| `DEPLOY_KNOWN_HOSTS` | Que el despliegue no acepte cualquier anfitrión | Secreto de repositorio | **Hecho** — las dos claves de host del servidor actual |
| `DEPLOY_HOST` | Destino del despliegue | Secreto de repositorio | **Hecho** — `198.199.89.119` |
| `DEPLOY_USER` | Cuenta de despliegue | Secreto de repositorio | **Hecho** — `deploy` |
| `DEPLOY_PORT` | Puerto SSH | Secreto de repositorio | **Hecho** — se eliminó la variable duplicada |
| `APP_KEY` | Cifrado de la aplicación | Secreto por entorno | **Hecho** — uno distinto por entorno |
| `DB_PASSWORD` | Base de datos | Secreto por entorno | **Hecho** — uno distinto por entorno |
| `REDIS_PASSWORD` | Caché y colas | Secreto por entorno | **Hecho** — uno distinto por entorno |
| `S3_ACCESS_KEY` / `S3_SECRET_KEY` | Almacenamiento de objetos | Secreto por entorno | **Hecho** |
| `BACKUP_PASSPHRASE` | Cifrado de las copias | Secreto por entorno | **Hecho** — falta la copia en custodia externa |
| `CLOUDFLARE_API_TOKEN` | Acción de Fail2Ban y purga de caché | Secreto por entorno | **Por obtener** — requiere acceso a Cloudflare |

### Regla de la `APP_KEY`

**Si se pierde, los datos cifrados de la base quedan irrecuperables** aunque la copia de
seguridad esté intacta. Por eso vive en tres sitios: el secreto del entorno, el gestor de
secretos de la entidad y un sobre cerrado en custodia física. Nunca en el repositorio, nunca
en un `.env.example`, nunca en un registro de conversación.

---

## 4. Accesos que dependen de ti o de un tercero

| Nombre | Para qué | Quién lo entrega | Estado |
|---|---|---|---|
| Token de API de DigitalOcean | Crear y versionar el cortafuegos del proveedor (§13.2) | Titular | **Pendiente por decisión** — acordaste dejarlo para después |
| Token de API de Cloudflare | Bloquear atacantes en el borde y purgar caché | Titular | **No existe** — hace falta conocer quién administra la zona |
| Acceso al DNS de `santamarta.gov.co` | Apuntar el dominio y el certificado | Entidad | **Por confirmar** |
| Credenciales de identidad digital | Ingreso federado del ciudadano | Proveedor de identidad | **Pendiente** — requiere convenio |
| Firma de la pasarela de pagos | Autenticar las notificaciones de pago | Pasarela | **Pendiente** — la entrega el proveedor |
| Firma del correo certificado | Constancia de notificación de actos administrativos | Operador postal | **Pendiente** — la entrega el proveedor |
| Token del captcha | Protección de formularios | Proveedor | **Opcional** — hay implementación propia disponible |
| Credenciales de correo saliente | Acuse de recibo y notificaciones | Proveedor | **Por decidir el proveedor** |

> **Ninguno de estos se inventa.** Un valor de relleno pasa la comprobación de presencia y
> falla únicamente al verificar la firma, ya en caliente, con el ciudadano esperando. La
> ausencia se declara y la integración queda apagada con un mensaje útil.

---

## 5. Lo que existe del proyecto anterior y hay que limpiar

Estos accesos se crearon para el **servidor viejo**. Reutilizarlos apuntaría el despliegue a
una máquina que ya no existe.

### Secretos de repositorio (5) — todos obsoletos

| Nombre | Problema |
|---|---|
| `DEPLOY_HOST` | Apunta al servidor anterior |
| `DEPLOY_KNOWN_HOSTS` | Huella del servidor anterior |
| `DEPLOY_SSH_KEY` | Llave del servidor anterior |
| `DEPLOY_PORT` | **Duplicado**: existe también como variable con el mismo nombre |
| `DEPLOY_USER` | **Duplicado** como variable, y con el valor `sede`, que ya no se usará |

### Secretos por entorno (7 en cada uno) — obsoletos o sin verificar

| Nombre | Problema |
|---|---|
| `APP_KEY` | Se generó para el despliegue anterior; se regenera con el nuevo |
| `DB_PASSWORD`, `REDIS_PASSWORD` | Ídem |
| `S3_ACCESS_KEY`, `S3_SECRET_KEY` | Ídem |
| `SEDE_PAGOS_SECRETO` | **Debe eliminarse o verificarse**: según la documentación del proyecto anterior, este secreto **no debía crearse** porque su valor lo entrega la pasarela. Si contiene un valor inventado, es peor que su ausencia |
| `SEDE_CORREO_CERTIFICADO_SECRETO` | Mismo caso con el operador postal |

### Variables — obsoletas

| Nombre | Ámbito | Problema |
|---|---|---|
| `SEDE_DESPLIEGUE_STAGING` | Repositorio | Interruptor del proyecto anterior |
| `DEPLOY_PORT`, `DEPLOY_USER` | Ambos entornos | Duplican secretos con el mismo nombre |
| `SEDE_DOMINIO`, `SEDE_URL` | `staging` | Apuntan a la IP desnuda `137.184.21.210` |
| `SEDE_DOMINIO`, `SEDE_URL` | `production` | Apuntan a `www.santamarta.gov.co`, que **no** es el dominio decidido |

### Acciones de limpieza — estado

- [x] Eliminados los 5 secretos de repositorio del servidor anterior.
- [x] Eliminada la variable `SEDE_DESPLIEGUE_STAGING` del repositorio.
- [x] Eliminados los 5 secretos obsoletos de cada entorno (`APP_KEY`, base, caché y S3).
- [x] Eliminadas las 4 variables obsoletas de cada entorno (puerto, usuario, dominio y URL).
- [ ] **Pendiente:** los dos secretos de terceros. **No se han borrado.** GitHub nunca
  permite leer un secreto, así que sólo el titular puede saber si contienen un valor de
  relleno o uno real. Si son de relleno, se borran; si son reales, se migran a la custodia
  de la entidad.
- [ ] Renombrar la rama principal y borrar las tres antiguas (tareas F0.2–F0.4).

**Total limpiado: 17 de 19.**

---

## 6. Dónde vive cada cosa

| Destino | Qué se guarda ahí | Quién puede leerlo |
|---|---|---|
| Secretos de repositorio de GitHub | Lo que comparte todo el despliegue: destino, llave, huella | Trabajos de la canalización |
| Secretos por entorno de GitHub | Credenciales propias de `staging` y de `production`, **distintas entre sí** | Sólo trabajos que declaran ese entorno |
| Gestor de secretos de la entidad | `APP_KEY`, credenciales de terceros, frase de paso de las copias | La entidad |
| Custodia física | Sobre cerrado con la frase de paso de las copias, dos responsables | La entidad |
| Archivo local ignorado | Puente temporal para trasladar valores generados a su destino | El titular |

**Cada entorno tiene su propio juego de credenciales.** Compartir la contraseña de la base
entre `staging` y `production` haría que un incidente en pruebas alcanzara producción.

### Archivos locales con valores

Este documento **no contiene valores**: es el registro versionado, y el repositorio es
público. Los valores viven en dos archivos locales, ambos ignorados por el control de
versiones y con permisos `600`:

| Archivo | Para qué | Formato |
|---|---|---|
| `pass.md` | Consulta humana: credenciales organizadas y explicadas | Markdown |
| `.accesos.local.env` | Consumo por programas y traslado al gestor de secretos | `CLAVE=valor` |

**Son un puente, no un destino.** Su contenido se traslada al gestor de la entidad y
después se destruyen con `shred -u`, que sobrescribe antes de borrar: un `rm` deja el
contenido recuperable en el disco.

---

## 7. Rotación

| Qué | Cada cuánto | Quién |
|---|---|---|
| Contraseñas de base de datos y caché | Trimestral | Canalización |
| `APP_KEY` | Trimestral, con procedimiento y conservación de la clave anterior | Entidad |
| Llave SSH de despliegue | Trimestral | Titular |
| Llave SSH personal | Anual, o ante cualquier sospecha | Titular |
| Credenciales de terceros | Según el proveedor | Entidad, coordinado |
| Frase de paso de las copias | Anual | Entidad |

La rotación de la `APP_KEY` **no es un cambio de variable**: exige volver a cifrar los datos
que dependían de la clave anterior y conservarla hasta terminar. Es un procedimiento, no un
trámite.

---

## 8. Estado técnico de los accesos al servidor

| Aspecto | Estado |
|---|---|
| Servidor | `198.199.89.119` (IPv4) · `2604:a880:0400:d1::5:125a:a001` (IPv6) |
| Identificador | `604812963` · región `nyc1` · Ubuntu 26.04.1 LTS |
| Cuentas | `ops` (administración, con `sudo`) y `deploy` (canalización) — **creadas** |
| Llaves autorizadas | La personal para `ops`; la de despliegue para `deploy`, restringida a un comando |
| Acceso de `root` por SSH | **Todavía habilitado** (`prohibit-password`); se cierra al final del aprovisionamiento |
| Autenticación por contraseña | Deshabilitada |
| Cortafuegos del host | **Activo** — entrada denegada por defecto; 22 con límite de tasa, 80 y 443. Con guarda contra el bypass de Docker |
| Fail2Ban | **Activo** — jails `sshd` y `recidive`; ya ha bloqueado un atacante |
| Docker | **29.8.1** con Compose 5.5.1, demonio endurecido |
| Intercambio | 2 GB, con `swappiness` en 10 |
| Cortafuegos del proveedor | **No configurado** — pendiente por decisión |

> **El límite de tasa del puerto 22 es real y se nota.** Abrir más de seis conexiones SSH en
> treinta segundos desde la misma dirección bloquea temporalmente esa dirección. No es un
> bloqueo permanente —se levanta solo— pero conviene saberlo antes de culpar a la red.

---

## 9. Qué falta para poder desplegar

- [ ] Ampliar el servidor a 8 GB de memoria — **depende del titular**
- [x] Crear las cuentas `ops` y `deploy` — hecho y verificado
- [ ] Cerrar el acceso de `root` — se hace al final del aprovisionamiento, a propósito
- [x] Generar la llave de despliegue y restringirla — hecha, con un único comando permitido
- [x] Regenerar `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT` y `DEPLOY_KNOWN_HOSTS` — hechos
- [ ] Limpiar los 19 accesos obsoletos — **17 hechos**; faltan los dos de terceros
- [x] Generar los secretos de aplicación, uno por entorno — hechos
- [ ] Decidir el dominio de producción — **depende del titular**
- [ ] Obtener el token de DigitalOcean para el cortafuegos — **depende del titular**
- [ ] Confirmar quién administra la zona de Cloudflare — **depende del titular**

---

## Anexo — Huellas de llaves conocidas

| Llave | Huella | Dónde está autorizada | Estado |
|---|---|---|---|
| Personal del titular | `SHA256:v5E0mAsz851iLBKnn1uTPf78f9EqJeCh50Z7U6rsFxE` | Droplet actual, como `root` | **En uso** |
| Despliegue de producción (anterior) | `SHA256:hxWqlBB8X41JETe+fyobsr18EmDCjs+eYZyz5xHyk7I` | Servidor anterior | **Obsoleta** |
| Despliegue de pruebas (anterior) | `SHA256:llj4xeFCfK5w8Jsc+J5t82wEcEGIheDbYdeQ9gwvn7s` | Servidor anterior | **Obsoleta** |

Las dos llaves de despliegue anteriores **fueron rechazadas por el servidor actual**: no
tienen acceso a esta máquina. La única vigente es la personal.

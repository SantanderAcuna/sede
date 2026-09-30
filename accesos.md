# Registro de accesos de la Sede Electrónica

> **Este archivo no contiene valores. Nunca.** Sólo nombres, para qué sirve cada uno, dónde
> vive y quién lo custodia. El repositorio es **público**: un valor escrito aquí sería un
> valor filtrado. Las huellas de llaves públicas sí se incluyen, porque son información
> pública por definición.
>
> **Versión:** 1.1 · **Fecha:** 2026-09-30 · **Estado:** vivo — se actualiza con cada alta,
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
| Acceso al droplet | Entrar como administrador | `/home/ops/.ssh/authorized_keys` de `165.22.46.11` | Titular | **Existe** — la administración entra como `ops`; **`root` por SSH está cerrado** |
| Cuenta de GitHub | Repositorio y canalizaciones | Sesión de `gh` en esta máquina | Titular | **Existe** — cuenta `SantanderAcuna`, con permisos de administración |
| Repositorio | Código y automatización | `github.com/SantanderAcuna/sede` | Titular | **Existe** — **público**, rama por defecto `main`; `main`, `staging` y `develop` protegidas |
| Llave SSH de despliegue | Que la canalización despliegue | `.claves/deploy_ed25519` (ignorada) y secreto de GitHub | Canalización | **Existe** — huella `SHA256:/T+kJDhzAUsICDCf0dxi3yCwqlDL+tY2jdP809KezOI`, sin frase de paso, **restringida a un solo comando** |
| Cuenta `ops` | Administración del servidor | Servidor, con `sudo` | Titular | **Existe** — contraseña **verificada contra `/etc/shadow`**; el servidor la pide en cada elevación |
| Cuenta `deploy` | Canalización | Servidor, sin contraseña utilizable | Canalización | **Existe** — sólo ejecuta `/usr/local/bin/desplegar.sh` |
| Guion de despliegue | Lo único que `deploy` puede ejecutar | `/usr/local/bin/desplegar.sh` en el servidor | Canalización | **Existe**, instalado a mano |

### Sobre la llave personal

Entra como `ops`. El acceso de `root` por SSH **ya está cerrado** (`PermitRootLogin no`,
confirmado tras comprobar que `ops` entra y eleva), así que `root` sólo se alcanza elevando
con `sudo` y la contraseña de `ops`, o por la consola del proveedor para emergencias.

Tiene frase de paso y la desbloquea el agente del llavero. Eso **es correcto para una
persona** y **no sirve para una máquina**: ningún ejecutor de GitHub puede teclear esa frase.

---

## 3. Accesos que ya están generados

| Nombre | Para qué | Dónde vive | Estado |
|---|---|---|---|
| `DEPLOY_SSH_KEY` | Que la canalización entre al servidor | Secreto de repositorio | **Hecho** — restringido a un único comando |
| `DEPLOY_KNOWN_HOSTS` | Que el despliegue no acepte cualquier anfitrión | Secreto de repositorio | **Hecho** — las **tres** claves de host del servidor actual (`ed25519`, `rsa` y `ecdsa`), contrastadas por dos vías independientes |
| `DEPLOY_HOST` | Destino del despliegue | Secreto de repositorio | **Hecho** — `165.22.46.11` |
| `DEPLOY_USER` | Cuenta de despliegue | Secreto de repositorio | **Hecho** — `deploy` |
| `DEPLOY_PORT` | Puerto SSH | Secreto de repositorio | **Hecho** — `22` |
| `APP_KEY` | Cifrado de la aplicación | Secreto por entorno | **Hecho y ROTADO** — uno distinto por entorno |
| `DB_PASSWORD` | Base de datos | Secreto por entorno | **Hecho y ROTADO** — uno distinto por entorno |
| `REDIS_PASSWORD` | Caché y colas | Secreto por entorno | **Hecho y ROTADO** — uno distinto por entorno |
| `S3_ACCESS_KEY` / `S3_SECRET_KEY` | Almacenamiento de objetos | Secreto por entorno | **Hecho y ROTADO** |
| `BACKUP_PASSPHRASE` | Cifrado de las copias | Secreto por entorno | **Hecho y ROTADO** — falta la copia en custodia externa |
| `CLOUDFLARE_API_TOKEN` | Purga de caché en el borde | Secreto por entorno | **Por obtener** — requiere acceso a Cloudflare |

> **Por qué dice ROTADO.** Los seis secretos de cada entorno quedaron publicados en el
> repositorio el 2026-09-30 (§5) y se rotaron el mismo día. Los valores que pudieran haber
> recogido terceros ya no sirven para nada. La lista de arriba describe el juego vigente.

### Regla de la `APP_KEY`

**Si se pierde, los datos cifrados de la base quedan irrecuperables** aunque la copia de
seguridad esté intacta. Por eso vive en tres sitios: el secreto del entorno, el gestor de
secretos de la entidad y un sobre cerrado en custodia física. Nunca en el repositorio, nunca
en un `.env.example`, nunca en un registro de conversación.

La rotación tiene una ventana cómoda ahora mismo: **no hay datos cifrados todavía**, así que
cambiar la clave es gratis. Después de la puesta en servicio dejará de serlo.

---

## 4. Accesos que dependen de ti o de un tercero

| Nombre | Para qué | Quién lo entrega | Estado |
|---|---|---|---|
| Token de API de DigitalOcean | Crear y versionar el cortafuegos del proveedor (§13.2) | Titular | **Pendiente por decisión** — acordaste dejarlo para después |
| Token de API de Cloudflare | Emitir el certificado por DNS y purgar caché | Titular | **No existe** — hace falta conocer quién administra la zona |
| Acceso al DNS de `santamarta.gov.co` | Apuntar el dominio y emitir el certificado | Entidad | **Por confirmar** |
| Credenciales de identidad digital | Ingreso federado del ciudadano | Proveedor de identidad | **Pendiente** — requiere convenio |
| Firma de la pasarela de pagos | Autenticar las notificaciones de pago | Pasarela | **Pendiente** — la entrega el proveedor |
| Firma del correo certificado | Constancia de notificación de actos administrativos | Operador postal | **Pendiente** — la entrega el proveedor |
| Token del captcha | Protección de formularios | Proveedor | **Opcional** — hay implementación propia disponible |
| Credenciales de correo saliente | Acuse de recibo y notificaciones | Proveedor | **Por decidir el proveedor** |

> **Ninguno de estos se inventa.** Un valor de relleno pasa la comprobación de presencia y
> falla únicamente al verificar la firma, ya en caliente, con el ciudadano esperando. La
> ausencia se declara y la integración queda apagada con un mensaje útil.

---

## 5. Incidente del 2026-09-30: credenciales publicadas

`pass.md`, el archivo local con valores en claro, **estaba rastreado por git** y llegó al
repositorio **público**. Las reglas `/pass.md` de `.gitignore` no lo protegían porque
**.gitignore no se aplica a los archivos ya rastreados**: una vez añadido, viaja en cada
empuje aunque la regla exista.

**Qué quedó expuesto:** la contraseña de `ops` y los seis secretos de aplicación de cada
entorno (`APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD`, `S3_ACCESS_KEY`, `S3_SECRET_KEY` y
`BACKUP_PASSPHRASE`). La **llave privada de despliegue NO se expuso**: el archivo sólo citaba
su ruta, y no contiene ningún bloque de llave privada.

**Qué se hizo, en este orden:**

1. Retirar `pass.md` del índice, conservándolo en disco e ignorado.
2. **Rotar los doce secretos** y empujarlos a los entornos de GitHub.
3. Rotar la contraseña de `ops`, verificándola contra `/etc/shadow` antes de registrarla.
4. Reescribir el historial de las ramas afectadas y empujarlas.
5. Activar el **análisis de secretos y la protección de empuje** del repositorio.
6. Añadir a la canalización un aviso por credenciales en el **historial**, no sólo en el árbol.

**Lo que aún no está cerrado.** Las solicitudes de fusión **#7, #8, #9 y #10** conservan las
confirmaciones antiguas en sus referencias (`refs/pull/*`), y una reescritura de ramas no las
toca: GitHub no permite borrar solicitudes. Como los secretos están rotados, esos valores ya
no sirven, pero las páginas siguen mostrándolos. **Requiere una petición a Soporte de
GitHub** para purgar los objetos inalcanzables y las vistas en caché.

**Lección, y por qué las barreras ahora son tres.** La canalización corre **después** del
empuje, así que no puede impedir una fuga: sólo avisar. Lo que la impide es la barrera local
antes de confirmar, y la protección de empuje del repositorio. Las tres están puestas.

---

## 6. Lo que existe del proyecto anterior y hay que limpiar

Estos accesos se crearon para el **servidor viejo**, que ya no existe.

### Secretos por entorno — los dos de terceros

| Nombre | Problema |
|---|---|
| `SEDE_PAGOS_SECRETO` | **Debe eliminarse o verificarse**: según la documentación del proyecto anterior, este secreto **no debía crearse** porque su valor lo entrega la pasarela. Si contiene un valor inventado, es peor que su ausencia |
| `SEDE_CORREO_CERTIFICADO_SECRETO` | Mismo caso con el operador postal |

### Acciones de limpieza — estado

- [x] Eliminados los 5 secretos de repositorio del servidor anterior, y generados los nuevos.
- [x] Eliminadas las variables obsoletas del proyecto anterior.
- [x] Eliminados los secretos de aplicación obsoletos de cada entorno, y generados los nuevos.
- [x] Eliminados los **17** accesos obsoletos.
- [ ] **Pendiente:** los dos secretos de terceros. **No se han borrado.** GitHub nunca permite
  leer un secreto, así que sólo el titular puede saber si contienen un valor de relleno o uno
  real. Si son de relleno, se borran; si son reales, se migran a la custodia de la entidad.
- [ ] Destruir el droplet anterior (`198.199.89.119`) — **lo gestiona el titular**.

**Total limpiado: 17 de 19.**

---

## 7. Dónde vive cada cosa

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
público. Los valores viven en dos archivos locales, ambos **ignorados** por el control de
versiones y con permisos `600`:

| Archivo | Para qué | Formato |
|---|---|---|
| `pass.md` | Consulta humana: credenciales organizadas y explicadas | Markdown |
| `.accesos.local.env` | Consumo por programas y traslado al gestor de secretos | `CLAVE=valor` |

**Son un puente, no un destino.** Su contenido se traslada al gestor de la entidad y
después se destruyen con `shred -u`, que sobrescribe antes de borrar: un `rm` deja el
contenido recuperable en el disco.

**Herramienta de rotación.** `deploy/rotar-secretos.py` genera los valores, los aplica y los
registra **en la misma ejecución**, y **se niega a escribir si `pass.md` vuelve a estar
rastreado**. Esa negativa es deliberada: registrar un secreto en un archivo rastreado es
exactamente cómo se produjo el incidente de §5.

---

## 8. Rotación

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

**Rotación hecha el 2026-09-30** — fuera de calendario, por el incidente de §5: los doce
secretos de aplicación, la contraseña de `ops`, y el destino del despliegue.

---

## 9. Estado técnico de los accesos al servidor

| Aspecto | Estado |
|---|---|
| Servidor | `165.22.46.11` (IPv4) · `2604:a880:800:14:0:3:96ab:d000` (IPv6) |
| Identificador | `604962462` · región `nyc3` · Ubuntu 24.04.5 LTS · 4 vCPU / 7,8 GB / 154 GB |
| Cuentas | `ops` (administración, con `sudo`) y `deploy` (canalización, sin contraseña utilizable) |
| Llaves autorizadas | Una para `ops`; la de despliegue para `deploy`, restringida a un comando |
| Acceso de `root` por SSH | **CERRADO** (`no`), confirmado tras verificar que `ops` entra y eleva |
| Autenticación por contraseña | Deshabilitada; sólo llave pública |
| Intercambio de claves | Prefiere el híbrido **pos-cuántico** `sntrup761x25519-sha512@openssh.com` |
| Cortafuegos del host | **Activo** — entrada denegada por defecto; 22 con límite de tasa, 80 y 443. Con guarda contra el bypass de Docker |
| Docker | **29.8.1** con Compose 5.5.1, demonio endurecido |
| Intercambio | 2 GB |
| Cortafuegos del proveedor | **No configurado** — pendiente por decisión |

### Fail2Ban

| Cárcel | Umbral | Bloqueo |
|---|---|---|
| `sshd` | 5 fallos en 10 minutos | 1 hora, que se duplica con cada reincidencia |
| `recidive` | 3 bloqueos en 1 día | 4 semanas, en todos los puertos |

La lista de exclusión incluye la propia máquina y la dirección del administrador actual.

> **Esta configuración se añadió el 2026-09-30, y no antes por un error.** El guion de
> aprovisionamiento instalaba Fail2Ban y **no lo configuraba**: quedaba con los valores por
> defecto del paquete. Se descubrió de la peor manera — dos intentos de entrar como `root`,
> que por estar cerrado **siempre** fallan, activaron la cárcel y dejaron la administración
> **sin acceso por SSH durante diez minutos**. La propia protección se convirtió en el
> incidente. Recuperarse desde fuera del servidor exige la consola del proveedor.
>
> **Si vuelve a pasar**, la orden es:
>
> ```bash
> fail2ban-client set sshd unbanip <dirección>
> ```
>
> Y `deploy/droplet/preparar.sh` la imprime al terminar.
>
> **El límite de tasa del puerto 22 es otra cosa.** Abrir más de seis conexiones SSH en
> treinta segundos desde la misma dirección bloquea esa dirección unos segundos. Se levanta
> solo, pero conviene saberlo antes de culpar a la red.

### Lo que hay y lo que no hay en el servidor

| Elemento | Estado |
|---|---|
| `/usr/local/bin/desplegar.sh` | **Instalado** — es el único comando que `deploy` puede ejecutar |
| `/opt/sede` | **Vacío** — el entorno aún no se ha publicado (§10) |
| Certificado del origen | **No existe** — bloqueado por Cloudflare (§10) |
| Imágenes en el registro | **No publicadas** — la construcción fallaba antes de construir (§10) |

---

## 10. Qué falta para poder desplegar

**Depende de ti:**

- [ ] Emitir el certificado del origen — **bloqueado**: `staging.santamarta.gov.co` está tras
  Cloudflare, que redirige HTTP a HTTPS en el borde, así que el desafío ACME recibe un 522.
  Hace falta un **token de API** para validar por DNS, un certificado de origen, o permiso
  para poner el registro en «solo DNS» mientras se emite.
- [ ] Decidir el dominio de producción.
- [ ] Obtener el token de DigitalOcean para el cortafuegos del proveedor.
- [ ] Verificar o borrar los dos secretos de terceros (§6).
- [ ] Enviar la petición a Soporte de GitHub por las solicitudes #7–#10 (§5).
- [ ] Destruir el droplet anterior.
- [ ] Decidir el almacenamiento de objetos (hoy hay un apaño: disco local).

**Hecho desde la versión anterior de este registro:**

- [x] **Droplet con más capacidad** — 4 vCPU / 7,8 GB sobre Ubuntu 24.04.5, la versión que la
  guía usa como referencia.
- [x] Cuentas `ops` y `deploy` creadas y verificadas.
- [x] **Acceso de `root` por SSH cerrado**, con temporizador de seguridad y confirmación.
- [x] Llave de despliegue generada y restringida a un único comando.
- [x] `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT` y `DEPLOY_KNOWN_HOSTS` regenerados.
- [x] Secretos de aplicación generados, uno por entorno, y rotados tras el incidente.
- [x] Fail2Ban configurado (§9).
- [x] Guion de despliegue instalado y capaz de recibir sus argumentos (§10, nota).

**Falta por hacer, y no depende de ti:**

- [ ] **Publicar el entorno** en `/opt/sede/staging`: `.env` renderizado desde
  `deploy/plantilla.env` y `compose.yaml`. El plan dice que la canalización lo renderiza en
  cada despliegue, pero **ese mecanismo no está implementado**, y la llave de despliegue
  sólo puede ejecutar un comando, así que no puede copiar archivos. **Pendiente de decisión:
  por dónde viaja el entorno.**
- [ ] Decidir y aplicar cómo recibe `desplegar.sh` el entorno, si por entrada estándar o por
  un archivo colocado una vez por el administrador.

> **Nota sobre el guion de despliegue.** La restricción de la llave lo declara como comando
> forzado **sin argumentos**, así que sshd los entrega en `SSH_ORIGINAL_COMMAND`. El guion no
> los leía: habría fallado siempre con «Falta el entorno» aunque todo lo demás estuviera bien.
> Ya los lee y los valida, porque vienen de fuera.

---

## Anexo — Huellas de llaves conocidas

| Llave | Huella | Dónde está autorizada | Estado |
|---|---|---|---|
| Personal del titular | `SHA256:v5E0mAsz851iLBKnn1uTPf78f9EqJeCh50Z7U6rsFxE` | `165.22.46.11`, como `ops` | **En uso** |
| Despliegue de la sede | `SHA256:/T+kJDhzAUsICDCf0dxi3yCwqlDL+tY2jdP809KezOI` | `165.22.46.11`, como `deploy`, restringida a un comando | **En uso** |
| Despliegue de producción (anterior) | `SHA256:hxWqlBB8X41JETe+fyobsr18EmDCjs+eYZyz5xHyk7I` | Servidor anterior | **Obsoleta** |
| Despliegue de pruebas (anterior) | `SHA256:llj4xeFCfK5w8Jsc+J5t82wEcEGIheDbYdeQ9gwvn7s` | Servidor anterior | **Obsoleta** |
| Host del servidor actual (`ed25519`) | `SHA256:2c5d1e2e65734cf24a38d038db1680945a3fc3670ed0b6c62dc3659ca70c300e` | — | **Vigente**, en `DEPLOY_KNOWN_HOSTS` |

Las dos llaves de despliegue anteriores **fueron rechazadas por el servidor actual**: no
tienen acceso a esta máquina. La única personal vigente es la del titular.

# Auditoría del Módulo 01 — Estructura e Identidad GOV.CO
## Cumplimiento contra el expediente y la ley, hallazgos, plan de remediación y criterios de aceptación

| | |
|---|---|
| **Proyecto** | Sede Electrónica — Alcaldía Distrital de Santa Marta |
| **Módulo auditado** | 01 · Estructura e Identidad GOV.CO (`estructura-identidad.md`, 181 líneas, idéntico a `sede.md`) |
| **Fecha de la auditoría** | 2026-10-28 |
| **Preparado para** | José — equipo TIC, Alcaldía Distrital de Santa Marta |
| **Preparado por** | Claudio (asistente de IA). La auditoría (2026-10-28) fue de solo lectura; **la ejecución de las fases 0 y 1 empezó el 2026-10-28 y se documenta en §8.7** (PR #285 a #324 del repositorio; el servidor de staging se tocó con tu aprobación) |
| **Estado del documento** | **EN EJECUCIÓN** (corte 2026-10-29). Fases 0 y 1: lo que podía hacer la sesión está hecho y comprobado; lo que queda es tuyo (§8.7). Fases 2 a 4: sin empezar. Nada se ejecuta sin tu aprobación explícita, fase por fase |

> ## ⚠️ Antes de nada: este archivo ya está en un repositorio PÚBLICO
>
> **ACTUALIZACIÓN 2026-10-29 (HECHO):** el archivo **ya está versionado en `main`, `staging` y `develop`** y el repo sigue siendo público por decisión D-1. Las dos frases siguientes describen el riesgo original, que **se materializó**: este documento lista debilidades que siguen abiertas (kernel sin actualizar, puerto 22 sin confirmar, MFA sin implementar, CSP en modo informe). **Decisión que te corresponde:** o sacas este archivo del repo (a un lugar privado) y lo dejas en `.git/info/exclude`, o aceptas que ese inventario sea público hasta cerrar las fases 0 y 1. Yo recomiendo lo primero. No reescribí el historial.
>
> **HECHO:** el repositorio `SantanderAcuna/sede` es **público** (la API de GitHub responde `200` sin credenciales) y la carpeta `sede-electronica-doc/` está **versionada** (54 archivos rastreados). Este archivo **no está ignorado** por `.gitignore`: un `git add .` lo publicaría.
>
> Este documento describe debilidades de seguridad reales y todavía abiertas de un sistema en construcción de una entidad pública. **Publicarlo antes de corregirlas sería entregar el mapa del ataque.** Por eso:
> 1. No lo incluyas en un commit ni en un PR mientras el repo sea público (o mientras queden hallazgos de las fases 0 y 1 abiertos).
> 2. Está redactado **sin** la IP del servidor, sin nombres de usuario del servidor, sin valores de secretos y sin credenciales. Aun así, el solo inventario de fallas es sensible.
> 3. Si quieres una protección local que no toque el repo: añade la ruta a `.git/info/exclude` (archivo local, no versionado). Te lo dejo como acción opcional **a tu aprobación** (tarea T-00).

---

## 0. Cómo leer este documento

**Convención de evidencia** (la misma del expediente y de tus preferencias):

| Etiqueta | Significa |
|---|---|
| **HECHO** | Lo observé directamente: una respuesta HTTP, una línea de código con su ruta, la salida de un comando. Se cita dónde. |
| **INFERENCIA** | Lo deduzco de hechos, pero no lo ejecuté ni lo medí. Dice de qué hechos sale. |
| **HIPÓTESIS** | Algo plausible que no pude comprobar. Va acompañado de **cómo comprobarlo**. |
| **NO VERIFICABLE** | Requiere acceso que no tengo (servidor, permisos de administrador en GitHub, la base de datos). Va a la lista de verificaciones a cargo del dueño (§9). |

**Estados de cumplimiento** (matriz de §5):

| Símbolo | Estado | Criterio |
|---|---|---|
| ✅ | Cumple | Hay evidencia de que lo exigido se cumple en el alcance actual. |
| 🟡 | Parcial | Cumple una parte o cumple solo por configuración estática; falta una parte concreta (se nombra). |
| ❌ | No cumple | La evidencia muestra que no está hecho, o está hecho como marcador de posición. |
| ⏸ | Diferido | El propio expediente lo difiere por decisión documentada. |
| ❔ | No verificable | No hay evidencia suficiente a favor ni en contra con lo que pude observar. **No cuenta como cumplido.** |

**Lo que NO es esta auditoría.** No es una auditoría legal: las normas se citan **tal como las cita el expediente** (no verifiqué su texto vigente). La validación jurídica corresponde a la Oficina Jurídica y al Oficial de Protección de Datos de la Alcaldía.

---

## 1. Veredicto

**El Módulo 01 no se cumple al 100 %.** Ni en el código, ni en lo desplegado, ni en lo administrativo.

**Resultado de la matriz (§5, 59 requisitos del Módulo 01 que pude contrastar):**

| Estado | Filas | % de las 52 evaluables |
|---|---|---|
| ✅ Cumple | 14 | 27 % |
| 🟡 Cumple parcialmente | 23 | 44 % |
| ❌ No cumple | 15 | 29 % |
| ❔ No verificable con lo que tengo (producción, trámites administrativos, mediciones) | 6 | — |
| ⏸ Diferido por decisión del propio expediente | 1 | — |

*Las 59 filas no son los 137 criterios de la matriz del proyecto ni todos los requisitos del módulo: son los que pude agrupar y contrastar con evidencia. «% evaluables» excluye ❔ y ⏸. Los porcentajes son un resumen, no una métrica de calidad: no todas las filas pesan igual.*

**Hallazgos (§6): 44** — 11 de severidad Alta, 20 Media y 13 Baja (criterio de severidad declarado en §6). **Plan (§8): 32 tareas, 161 criterios de aceptación** y 6 decisiones tuyas (§8.6). Esfuerzo orientativo ≈ 46 días-persona **±50 %** (**estimación mía**, sin velocidad histórica del equipo).

**Los problemas que más pesan** (el resto está en §6):

1. **Las cinco políticas obligatorias son marcadores de posición** (≈80 palabras cada una, todas dicen «Este documento está en preparación»). No hay términos y condiciones, ni política de tratamiento de datos (Ley 1581/2012), ni de cookies, ni de derechos de autor con contenido. Es el incumplimiento **legal** más claro del módulo y el más barato de cerrar. → H-21, T-20.
2. **El repositorio público contiene `accesos.md`** con el inventario operativo de la infraestructura. Es independiente del módulo 01, pero es el riesgo más alto del proyecto. → H-01, T-01.
3. **La protección de ramas deja pasar todo:** las reglas de `main`, `staging` y `develop` exigen un PR pero con **0 aprobaciones** y **sin comprobaciones de estado obligatorias**; el CI está en rojo hoy y se puede fusionar igual. → H-02, T-04.
4. **La integración con GOV.CO (Decreto 2106/2019 arts. 14-15) no tiene evidencia de haberse iniciado administrativamente** (formulario a MinTIC, Plan de Integración en el PETI). Todo lo técnico del módulo se justifica por este objetivo. → H-30, T-31.
5. **Funciones centrales ausentes:** buscador sin autocompletado ni tolerancia ortográfica (H-23), módulo de noticias sin datos (H-24), sin CMS para menú/noticias/carrusel (H-24/H-26), derecho de petición que no envía nada (H-32).

6. **La cuenta de super-administrador de staging no tiene MFA y su clave es de 8 dígitos** (comprobado con un login real el 2026-10-28, §3.3.1). → H-38, T-14, T-10.

**Lo que sí está bien** (para que el balance sea honesto): identidad visual Kit UI v9.2 autoalojada y servida (tipografías, Bootstrap 5.0.2, Cobalt, barra de 56 px), página 404 personalizada con 6 enlaces, aviso de salida con lista blanca y pruebas, menú de 7 ítems y 2 niveles en el orden exigido, teléfonos con +57, mapa del sitio y `sitemap.xml`, consentimiento de cookies versionado con caducidad de 12 meses, doble pila IPv4/IPv6 en el borde, 249/249 pruebas del panel en verde (ejecutadas por mí el 2026-10-28, al comienzo de esta revisión).

---

## 2. Alcance, método y límites

### 2.1 Qué se auditó y contra qué

| Fuente de exigencias | Cómo se usó |
|---|---|
| `estructura-identidad.md` (este módulo): RF-B1/B2/B3, RF-01-D01..D03, RNF, RN, UC, HU, §7-§9 | Cada ID se contrastó con evidencia (§5). |
| Normas citadas por el módulo (Decreto 2106/2019, Decreto 1078/2015, Ley 1437/2011, Directiva Presidencial 03/2019, Res. 2893/2020, Res. 1519/2020, Ley 1581/2012, Ley 1712/2014) | Tabla de §7. Citadas como las cita el expediente. |
| `docs/trazabilidad.md` (generada por `sitio/scripts/trazabilidad.mjs`), `auditoria-sede.md`, `contract/openapi.yaml`, `PRODUCT.md` | Contraste de lo declarado vs lo observado. |

### 2.2 Qué se examinó (evidencia primaria)

| Frente | Qué hice | Versión / momento |
|---|---|---|
| **Sitio desplegado** | 88 URLs sondeadas, HTML capturado y analizado, cabeceras, DNS, políticas, 404, API pública de identidad | `staging.santamarta.gov.co`, 2026-10-28. **Es staging (`noindex`), no producción** |
| **Código del sitio / panel** | Lectura de componentes, composables, pruebas, configuración | Checkout local de José (rama `release/staging-test-fixes`) |
| **Backend** | Lectura de controladores, servicios, rutas, seeders, middlewares; `composer audit --locked` ejecutado; PHPUnit: ver §3 | Clon de `origin/staging` @ `40496ad` (2026-10-07) |
| **Infraestructura** | Lectura de `docker/`, `compose*.yaml`, `deploy/`, `.github/workflows/` | Checkout local |
| **GitHub** | API pública (visibilidad, ramas protegidas, reglas efectivas) | 2026-10-28 |
| **Dependencias** | `npm audit --omit=dev` (sitio), `npm ls`, trazado a runtime; `composer audit` | Clon @ `40496ad` |
| **Trazabilidad** | Regeneré `docs/trazabilidad.md` **en el clon** (no en tu repo) con `node scripts/trazabilidad.mjs` y la comparé con la tuya | Clon @ `40496ad` |

### 2.3 Límites que condicionan las conclusiones

- **L-1 — Staging no es producción.** Todo lo "vivo" es de staging. Que algo funcione en staging no prueba nada sobre producción, y viceversa. No sé si hay producción activa con el sitio nuevo; el expediente dice que el sitio actual es "SM CMS".
- **L-2 — Posible desfase entre tu checkout y lo desplegado.** Leí código de tu rama local y desplegué el backend desde `origin/staging` (`40496ad`). Si tu rama local tiene commits sin desplegar, algunos hechos de código pueden no estar en staging.
- **L-3 — No tengo acceso al servidor, a la base de datos ni a la administración de GitHub** (lo intenté con tu autorización y no fue posible; ver 3.2). Lo que depende de eso queda en §9 como verificación a tu cargo, con el comando exacto y el resultado esperado.
- **L-4 — Mediciones visuales** (tamaños de logo, contraste real, 0 violaciones de marca, comportamiento en Safari/Firefox/Edge) **no las ejecuté**; las pruebas de diseño del repo (`tests/diseno.mjs`) usan solo Chromium y no las corrí contra staging.
- **L-5 — Acceso autenticado: una sola cuenta.** Hice un login con la cuenta de super-administrador que me indicaste (§3.3.1) y lo cerré. No exploré funciones del panel con esa sesión ni probé otros roles.

---

## 3. Resultado de las cuatro verificaciones que quedaron pendientes

### 3.1 Backend: `composer audit` y PHPUnit

| Verificación | Resultado | Evidencia |
|---|---|---|
| `composer audit --locked` sobre `composer.lock` de `origin/staging` @ `40496ad` | **2 avisos, 0 paquetes abandonados.** Salida con código 1. | **HECHO** (ejecutado) |
| — `symfony/mime` **v8.1.7** (producción) | CVE-2026-45070, severidad *medium*: inyección de cabeceras de correo por caracteres no válidos en nombres de parámetros MIME. Afectado: `>=8.1.0 <8.1.10`. **Corrección: actualizar a ≥ 8.1.10.** | **HECHO** |
| — `phpunit/phpunit` **13.4.0** (solo desarrollo) | PKSA-qhjm-rpqc-qkn6: ejecución de código por otros usuarios locales a través del directorio temporal compartido en el manejo de cobertura PHPT. Afectado: `>=13.4.0 <13.4.4`. **Corrección: ≥ 13.4.4.** | **HECHO** |
| ¿Alcanza `symfony/mime` a la aplicación hoy? | **No hay ruta que envíe correo**: `grep` de `Mail::`, `Mailable`, `->attach(`, `MailMessage` en `app/` y `routes/` → 0 coincidencias; `MAIL_MAILER=log` en `.env.example`. | **HECHO** (ausencia) → **INFERENCIA:** hoy no es explotable; **lo será** cuando existan notificaciones por correo (PQRSD, recuperación de contraseña, alertas). Conviene actualizar antes. |
| ¿Alcanza `phpunit` a producción? | No: es dependencia de desarrollo (`packages-dev`). Riesgo solo en el runner de CI/equipo del desarrollador si es multiusuario. | **HECHO** (lock) |

**PHPUnit — ejecutado (HECHO).** Entorno: PHP 8.5.12, PostgreSQL 16.15, `origin/staging` @ `40496ad`, base `sede_pruebas` con las credenciales de `phpunit.xml`.

| Verificación | Resultado |
|---|---|
| `vendor/bin/phpunit` (suites Unit + Feature, 38 archivos de prueba) | **240 pruebas, 240 pasan, 3 716 aserciones, 20,9 s** |
| `vendor/bin/pint --test` | pasa |

Condiciones que debes conocer para no sobreestimar el resultado:
- **Primera pasada (insuficiente):** instalé con `--prefer-source` y retiré `phpstan/phpstan` y `larastan/larastan` del clon (GitHub rechazó la descarga con 403). Esa pasada no ejecutó análisis estático ni cobertura. **Los completé después** (ver 3.1.1).
- La suite verde **no** significa que el backend esté bien: reproduje dos defectos con una prueba desechable en el clon (ya borrada; tu repo no se tocó):
  - **H-08 confirmado:** con 3 registros en `audit_log`, `GET /api/v1/panel/auditoria` devuelve `meta.total = 3` y `data` con **0 elementos**. La línea es `AuditoriaController.php:60` (`$query->limit(0)->get()`).
  - **H-07 confirmado:** un usuario **sin ningún permiso** recibe **HTTP 200** en `/panel/auditoria` (con `meta.total = 3`). La ruta solo exige `auth:sanctum` (`routes/api.php:52-54`).
  - Ambos pasan inadvertidos porque `AuditoriaTest.php` solo afirma estructura y `meta.total`, nunca el contenido de `data` ni el permiso.
- La suite incluye las pruebas de `perfil`; ninguna falla en este commit. (INFERENCIA: el 401 que `INFORME_FINAL_GESTION.md` reportaba ya no se reproduce; no mapeé cuál prueba era.)

#### 3.1.1 Segunda pasada: análisis estático y cobertura (2026-10-28, con tu autorización)

| Prueba | Cómo | Resultado | Etiqueta |
|---|---|---|---|
| PHPUnit | PHP 8.5, PostgreSQL local, `origin/staging` @ `40496ad` | **240/240**, 3 716 aserciones | **HECHO** |
| PHPStan nivel 8 + Larastan (config del proyecto, `phpstan.neon`) | `phpstan.phar` (última versión) + Larastan `master` copiados al clon; **no son las versiones fijadas por tu `composer.lock`** | **1 error:** `app/Services/FilesMediaService.php:51`, «Expression on left side of ?? is not nullable» (`$file->getMimeType() ?? …`) | **HECHO** (con la salvedad de versiones) |
| Cobertura de líneas | PCOV, `app/` con las exclusiones de `phpunit.xml` | **93,9 %** de sentencias (1 284/1 367); **85,5 %** de métodos (153/179). El proyecto declaró **95 %** → **no se alcanza** | **HECHO** |
| Archivos menos cubiertos (≥15 sentencias) | clover | `AuditLogResource` 0 %, `AuthService` 59 % (la rama de MFA, coherente con H-10), `PermissionAuditListener` 82 %, `IngestaTramites` 86 % | **HECHO** |

Lectura: el análisis estático casi pasa (1 error, trivial de corregir), pero la puerta de cobertura del 95 % **fallaría hoy** por 1,1 puntos, y el hueco mayor está justo donde están los defectos reales (auditoría y autenticación). La cobertura de líneas no mide ramas ni aserciones: H-07 y H-08 viven en líneas que sí se ejecutan. → H-34, T-35.

### 3.2 Servidor real y protecciones de GitHub

**GitHub — lo que sí pude comprobar con la API pública (HECHO):**

| Consulta | Resultado |
|---|---|
| Visibilidad del repo | `private: false` → **público** |
| ¿`main`, `staging`, `develop` protegidas? | `protected: true` en las tres |
| Reglas efectivas (rulesets) en las tres ramas | `deletion`, `non_fast_forward`, `pull_request` |
| Parámetros de `pull_request` | `required_approving_review_count: 0` · `dismiss_stale_reviews_on_push: false` · `required_reviewers: []` · `require_code_owner_review: false` · `require_last_push_approval: false` · `required_review_thread_resolution: false` · métodos de fusión: merge, squash, rebase |
| ¿Existe la regla `required_status_checks`? | **No aparece** entre las reglas efectivas |

**Lectura (INFERENCIA, fundada en lo anterior):** la protección impide borrar ramas y forzar el historial, y obliga a pasar por un PR, pero **un PR puede fusionarse sin ninguna revisión humana y sin que el CI haya pasado**. Hoy el job «Dependencias» está en rojo y no bloquea nada. Es un control de procedimiento, no de calidad. → **H-02, T-04.**

**GitHub con sesión de administrador (2026-10-28):** ejecutaste el script de solo lectura `auditoria-lectura.sh` (parte A) y pegaste su salida. Lo que sigue es **HECHO según esa salida** —yo no pude ejecutar las consultas— y la parte de nombres de secretos no incluye ningún valor:

| Consulta | Resultado | Lectura |
|---|---|---|
| *Bypass actors* del ruleset «Ramas permanentes de la sede» | **Lista vacía**; `enforcement: active`; aplica a `main`, `staging`, `develop` | ✅ Bien: nadie salta la regla |
| Reglas del ruleset | `deletion`, `non_fast_forward`, `pull_request` con **0 aprobaciones**; **sin** `required_status_checks` | ❌ Confirma H-02 |
| Protección clásica de ramas | 404 «Branch not protected» en las tres | Neutral: la protección está solo en el ruleset |
| Entornos `production` y `staging` | Solo política de ramas personalizada; **sin revisores requeridos** y sin espera | ❌ **Nuevo: H-39.** Un despliegue a producción no pide aprobación humana |
| Secretos a nivel de **repositorio** | `DEPLOY_HOST`, `DEPLOY_KNOWN_HOSTS`, `DEPLOY_PORT`, `DEPLOY_SSH_KEY`, `DEPLOY_USER` | 🟡 La llave de despliegue no es por entorno (H-39) |
| Secretos por **entorno** (mismos 8 nombres en `staging` y `production`) | `APP_KEY`, `BACKUP_PASSPHRASE`, `DB_PASSWORD`, `REDIS_PASSWORD`, `S3_ACCESS_KEY`, `S3_SECRET_KEY`, `SEDE_CORREO_CERTIFICADO_SECRETO`, `SEDE_PAGOS_SECRETO` | ✅ Hay separación de nombres; **no verifiqué que los valores sean distintos** (lo afirma `accesos.md`) |
| Permisos por defecto de Actions | `read`; Actions no puede aprobar PR | ✅ Bien |
| Escaneo de secretos | `secret_scanning` y `push_protection`: **enabled**; `non_provider_patterns` y `validity_checks`: disabled; Dependabot de seguridad: enabled | ✅ Bien (cierra el «esperado» de §9.2 fila 5); los patrones no-proveedor están apagados |
| Llaves de despliegue del repo | Ninguna | ✅ Bien |

**Lectura (INFERENCIA):** el control de procedimiento de GitHub está mejor de lo que la API pública dejaba ver (sin *bypass*, con escaneo de secretos y permisos de solo lectura), pero la puerta a producción sigue abierta: sin checks obligatorios, sin revisor en `production` y con la llave SSH de despliegue accesible a cualquier flujo de trabajo del repositorio. **Sigue sin verificar:** 2FA obligatoria de los colaboradores y la protección de etiquetas (no las consulta el script).

**Servidor — NO VERIFICABLE.** No hay acceso. Lo que dice el repositorio (HECHO):

| Archivo | Línea(s) | Hecho |
|---|---|---|
| `deploy/droplet/preparar.sh` | 79-80 | Agrega el usuario de despliegue al grupo `docker` |
| `deploy/droplet/preparar.sh` | ~120 | Regla `sudoers` sin contraseña para `desplegar.sh` |
| `deploy/droplet/despliegue-remoto.sh` | 36 | Menciona `command="…"` de `authorized_keys` **solo en un comentario**; **ningún script del repo lo instala** |
| `accesos.md` (no repetido aquí) | — | Afirma que la llave está «restringida a un solo comando» |

**HIPÓTESIS:** la restricción `command=` se configuró a mano en el servidor. **Cómo comprobarla:** §9.1. **Por qué importa:** pertenecer al grupo `docker` equivale a `root` en el host (montar `/` en un contenedor); si la llave de CI no está atada a `command=` + `restrict`, quien la robe del secreto de GitHub tiene `root`.

**Intento de verificación directa (2026-10-28, con tu autorización) — no fue posible:**
- **Servidor:** mi shell corre en una máquina virtual de tu equipo sin agente SSH ni llaves (`SSH_AUTH_SOCK` vacío, sin `~/.ssh`). La llave personal que `accesos.md` describe vive en el llavero de tu equipo anfitrión, con frase de paso. **No intenté adivinar contraseñas ni otra vía de acceso.** `authorized_keys`, firewall y Fail2Ban siguen **sin verificar** → §9.1.
- **GitHub (administración):** desde mi máquina virtual no fue posible (sin `gh` ni token válido). Se resolvió después: **tú ejecutaste el script** en tu equipo y su salida está arriba.
- **Servidor, segundo intento:** el script se ejecutó, pero la parte del servidor no corrió porque el destino SSH quedó con el valor de ejemplo (`hostname contains invalid characters`). **Sigue pendiente de re-ejecutar** con el destino real.

**Servidor — lo único comprobado hasta ahora: un `ssh -v root@<servidor>` que ejecutaste tú (2026-10-28).** Es una conexión de prueba sin ejecutar comandos, y pegaste su salida de depuración:

| Observación en la salida | Etiqueta | Lectura |
|---|---|---|
| Servidor con `OpenSSH_9.6p1` | **HECHO** | Coherente con Ubuntu 24.04 (la versión empaquetada recibe parches de seguridad por la distribución; no verifiqué el nivel de parche) |
| Métodos de autenticación admitidos: **solo `publickey`** | **HECHO** (para esa conexión; no descarta bloques `Match`) | Sin contraseña ni teclado interactivo: coincide con `passwordauthentication no` |
| El servidor **aceptó tu llave personal para `root`** en la consulta previa y, a pesar de ello, terminó con `Permission denied (publickey)` | **HECHO** (secuencia); **INFERENCIA** (causa) | Lo esperable con `PermitRootLogin no`: el acceso de `root` por SSH está cerrado, como dice `accesos.md`. **Pero** la consulta previa se acepta solo si la llave figura en el `authorized_keys` de `root`: tu llave personal **sigue autorizada para `root`**. Con el acceso cerrado es inofensivo; si alguien reabriera `PermitRootLogin`, esa llave entraría directo. Conviene retirarla (AC-03.4) |
| La huella del servidor ya estaba en tu `known_hosts` | **HECHO** | No pude cotejarla con `accesos.md`: ese archivo no lista huellas de host (solo las de llaves de usuario) |

**Servidor — ejecución del script de lectura como `ops` y sin `sudo` (2026-10-28).** No se dispuso de la contraseña de `sudo`, así que se corrió en modo `SIN_SUDO`: lo que requería root no devolvió datos. Salida pegada por ti; yo no ejecuté nada:

| Control (§9.1) | Resultado observado | Etiqueta | Lectura |
|---|---|---|---|
| Fila 2 · grupos de `deploy` | `deploy`, **`docker`**, `sede-ssh` | **HECHO** | **Confirma H-03:** `deploy` está en el grupo `docker`, equivalente a `root` en el host (montar `/` en un contenedor). La restricción `command=` de su llave, verificada después con `sudo`, lo acota (ver la ejecución siguiente) |
| Script de despliegue | `/usr/local/bin/desplegar.sh`, dueño `root:root`, modo `755`, 7 479 B, del 30-sep | **HECHO** | Bien: `deploy` no puede modificarlo. **El `despliegue-remoto.sh` del repo pesa 8 898 B**: el instalado **no es el mismo archivo** (probablemente una versión anterior); no leí su contenido, así que no sé si el instalado usa `sudo` ni cómo valida sus argumentos → AC-02.7 |
| Fila 9 · parches | **6** actualizaciones de seguridad pendientes; `unattended-upgrades` **activo** | **HECHO** | Desvío del «esperado = 0» → H-40. **INFERENCIA:** el servicio las aplicará solo, o falta un reinicio; no lo comprobé |
| Fail2Ban | servicio `active` | **HECHO** | Bien en cuanto a servicio; sus *jails* y bloqueos no se leyeron (requiere `sudo`) |
| Docker visto desde `ops` | `ops` no puede hablar con el socket de Docker | **HECHO** | Bien: `ops` no es root efectivo por Docker |
| Cuentas con shell | `root`, `ops`, `deploy` (`/bin/bash`) y `sync` (`/bin/sync`, cuenta estándar de Ubuntu) | **HECHO** | Sin cuentas inesperadas. `deploy` necesita shell para ejecutar su comando forzado |
| `authorized_keys`, `sshd -T`, `ufw`, `iptables`, puertos, `sudo -l` de `deploy`, `.env` | «a password is required» | **NO VERIFICADO** | Requieren `sudo`; siguen pendientes |

**Servidor — segunda ejecución, ahora con `sudo` (2026-10-28).** Con la contraseña de `ops` ejecutaste los ocho controles de lectura restantes; la salida quedó enmascarada (IP ocultas) y la leí del archivo. Etiqueta: **HECHO** salvo indicación:

| Control (§9.1) | Resultado observado | Lectura |
|---|---|---|
| Fila 1 · `authorized_keys` de `deploy` | Una línea: `command="/usr/local/bin/desplegar.sh"`, `no-port-forwarding`, `no-x11-forwarding`, `no-agent-forwarding`, `no-pty`, `no-user-rc`, llave `ed25519` con comentario «despliegue-sede@github-actions». Sin `restrict` ni `from=` | ✅ **La llave de CI sí está atada a un único comando**, que es lo que faltaba demostrar de H-03. Usa las opciones `no-*` en lugar de `restrict` (equivalente hoy, pero `restrict` cubre también opciones futuras). Sin `from=`: no hay lista de orígenes (GitHub Actions no tiene IP fija), así que la llave vale desde cualquier sitio |
| Fila 3 · `sudo` de `deploy` | `(root) NOPASSWD: /usr/local/bin/desplegar.sh, /opt/sede/desplegar.sh` | 🟡 Esperado: solo el primero. El segundo es un camino que `preparar.sh:120` también declara (`$BASE/desplegar.sh`). **HIPÓTESIS:** el archivo no existe y `/opt/sede` es de `root` (así lo crea `preparar.sh:562`), por lo que `deploy` no puede crearlo; **no lo comprobé** (AC-02.6) |
| Fila 4 · `sshd -T` | `permitrootlogin no`, `pubkeyauthentication yes`, `passwordauthentication no` | ✅ Coincide con lo esperado |
| Fila 5 · `ufw` | Activo; entrada denegada por defecto; **22/tcp LIMIT**, **80/tcp** y **443/tcp** permitidos desde cualquier origen (IPv4 e IPv6) | 🟡 Mínimo correcto (solo 22, 80, 443). Desvíos del ideal: 80/443 abiertos al mundo y no solo a Cloudflare, y 22 abierto al mundo → H-41 |
| Fila 6 · `iptables DOCKER-USER` y puertos | `DOCKER-USER`: acepta establecidas, `eth0` solo 80 y 443, el resto `DROP`. Escuchan 22, 25, 53 (loopback), 80 y 443; **no** 5432, 6379 ni 9000 | ✅ Docker no salta el firewall. 🟡 El puerto **25** escucha (IPv4 e IPv6); mi enmascarado ocultó la dirección y **no sé si es solo loopback** (AC-03.6). `ufw` lo bloquea desde fuera de todos modos |
| Fail2Ban, *jail* `sshd` | Activo; **2 203** intentos fallidos acumulados, **51** bloqueos en total, **2** bloqueados ahora | ✅ Funciona. Muestra el ruido de internet contra el puerto 22 abierto |
| `authorized_keys` de `root` | **1** llave | 🟡 Ver AC-03.4: con `PermitRootLogin no` es inofensiva, pero conviene retirarla |

**Servidor — tercera ejecución, fase 1 de lectura antes de cambiar nada (2026-10-28).** Copié el `desplegar.sh` instalado a tu carpeta de trabajo y lo comparé con el del repo; los demás controles salieron de la misma ejecución:

| Control | Resultado | Etiqueta y lectura |
|---|---|---|
| `/opt/sede/desplegar.sh` | **No existe**; `/opt/sede` es `root:root` `755` | **HECHO.** Confirma la hipótesis: `deploy` no puede crear ese archivo. H-03 se mantiene en Medio. La entrada de `sudoers` es un camino colgante que conviene retirar (AC-02.6) |
| `desplegar.sh` instalado vs. repo (`diff`) | La **única** diferencia es el bloque «Siembra» (26 líneas, `db:seed --class=SedeSeeder`) que el repo tiene y el instalado **no** | **HECHO.** El servidor corre una versión anterior: despliega sin sembrar datos institucionales. Los comentarios del propio repo describen un incidente así («la sede se sirvió vacía») → H-42 |
| `desplegar.sh` instalado, uso de `sudo` | Llama a `docker compose` directamente; **no contiene `sudo`** | **HECHO.** Confirma que `deploy` **necesita** el grupo `docker` hoy; ver corrección en §4, fila 10 |
| Puerto 25 | Escucha en **dirección no local**, IPv4 e IPv6 (mi comprobación distingue loopback) | **HECHO.** No sé qué proceso es (`ss -p` requiere `sudo`; el script de Cloudflare lo informa). `ufw` lo bloquea desde fuera → H-43 |
| Actualizaciones pendientes | Seis paquetes, **todos del núcleo** (`linux-image-virtual`, `linux-virtual`, `linux-headers-*`, `linux-libc-dev`, `linux-tools-common`). `unattended-upgrades` tiene `linux-`, `linux-image-`, `linux-headers-`, `libc6` en su **lista negra**; sin reinicio pendiente; servidor encendido 4 semanas | **HECHO.** No es que el servicio falle: está **configurado para no actualizar el núcleo**. Aplicarlo exige instalación manual **y reinicio** (H-40 corregido) |
| `sede-reglas-docker.service` | `enabled`; el script coincide con el de `preparar.sh` | **HECHO.** Es el archivo a modificar para el cambio de Cloudflare (H-41) |
| Reglas de Cloudflare en `ufw` | Ninguna | **HECHO** |

**Sigue sin verificar:** firewall de la nube de DigitalOcean, modo TLS de Cloudflare, restauración de copias, escaneo desde otra red y 2FA de GitHub (§9.1 filas 7, 8, 10, 11; §9.2 fila 7). Un primer intento de ejecutar el script contra el servidor no llegó a mostrar salida (la petición de contraseña de `sudo` quedaba oculta detrás del enmascarado); el script ya está corregido.

### 3.3 ¿Falla de verdad el login con 419 en staging?

**Corrección a mi informe anterior:** dije que el login del panel estaba «roto en staging». **Eso era una inferencia y era incorrecta.** Probé la secuencia completa, con credenciales ficticias (3 peticiones, todas rechazadas):

| # | Petición (staging, 2026-10-28) | Resultado | Lectura |
|---|---|---|---|
| 1 | `POST /api/v1/panel/login` **sin** cookie XSRF, con `Origin`/`Referer` del panel | **419** `CSRF token mismatch.` | **HECHO.** Sin pre-vuelo, el login falla. |
| 2 | `GET /api/v1/panel/perfil` con `Origin`/`Referer` del panel | **401** + `Set-Cookie: XSRF-TOKEN` y `sede-session` | **HECHO.** Una petición a una ruta *stateful* ya entrega la cookie. |
| 3 | `POST /api/v1/panel/login` con la cookie y `X-XSRF-TOKEN` del paso 2 | **401** `Las credenciales no son válidas.` | **HECHO.** El CSRF fue aceptado; se rechazó solo la credencial ficticia. |
| 4 | `GET /sanctum/csrf-cookie` | **404** | **HECHO.** La ruta que el panel invoca primero no existe en staging (nginx no tiene `location /sanctum`). |

**Conclusión:**
- El login **funciona** en staging, **por una vía accidental**: el panel consulta `/panel/perfil` al cargar y eso planta la cookie XSRF (**INFERENCIA fuerte**: lo deduzco del router y de `auth.ts`; no lo vi en un navegador).
- **Fragilidad real (HECHO):** la llamada de `auth.ts:62` a `/sanctum/csrf-cookie` devuelve 404 y el código **ignora la respuesta**. Cualquier cambio que quite la consulta de perfil previa, o cualquier cliente que vaya directo al login, recibe 419 sin explicación. Es un defecto de robustez, no una caída. → **H-06 (severidad Media), T-05.**

#### 3.3.1 Prueba con una cuenta real (2026-10-28, con tu autorización)

Con la cuenta que me indicaste (no se reproduce aquí) probé el flujo completo contra staging:

| # | Petición | Resultado | Lectura |
|---|---|---|---|
| A | Login con credenciales **correctas**, sin cookie XSRF | **419** | **HECHO.** Confirma que el pre-vuelo es obligatorio. |
| B | Login con cookie pero `X-XSRF-TOKEN` inválido | **419** | **HECHO.** El CSRF se valida de verdad. |
| C | `GET /panel/perfil` → cookies → login con `X-XSRF-TOKEN` válido | **200**, «Sesión iniciada», `require_mfa=false` | **HECHO.** El login funciona por esa vía. |
| D | `GET /panel/perfil` con la sesión | **200** | **HECHO.** |
| E | Cookies de sesión | `sede-session`: `Secure; HttpOnly; SameSite=Lax`, 2 h. `XSRF-TOKEN`: legible por JS (correcto para Sanctum) | **HECHO.** |
| F | `POST /panel/logout`, luego `GET /panel/perfil` | **204**, luego **401** | **HECHO.** La sesión se invalida en servidor. |
| G | Límite de intentos en el login | `x-ratelimit-limit: 3` | **HECHO.** Hay límite (no probé el bloqueo). |

**Cierra la duda de 3.3:** el login **no** está roto; H-06 se mantiene como fragilidad. **Hallazgos nuevos de esta prueba (→ H-38):** la cuenta usada es el **usuario `id=1` con rol `super-admin`**, **sin MFA** (`mfa_habilitado=false`, `require_mfa=false`), y su clave es **solo de dígitos y de 8 caracteres**. Además la respuesta del login incluye en el cuerpo un `mfa_token` y un `csrf_token` aunque no se requiera MFA (**INFERENCIA:** innecesario; no revisé su uso). Como la clave pasó por este chat, **debe considerarse expuesta y rotarse** (AC-14.4). No hice más de 3 intentos reales ni ninguna prueba de fuerza bruta.

### 3.4 Avisos del sitio: ¿runtime o solo build?

`npm audit --omit=dev` sobre `sitio/` (clon @ `40496ad`): **10 avisos** (2 altos, 4 moderados, 4 bajos), todos con corrección disponible.

| Paquete | Sev. | Cómo llega | ¿Se ejecuta en el servidor SSR? | Conclusión |
|---|---|---|---|---|
| `undici` 8.11.2 (8 avisos, 4 altos; corrige en ≥ 8.12.2) | alta | **Sitio:** dependencia declarada de `nuxt` (^8.10) y `@nuxt/cli`. **Panel:** solo vía `jsdom` (devDependency) | `grep` de `undici` en `nuxt/dist`, `@nuxt/nitro-server/dist`, `@nuxt/vite-builder/dist` → **0 coincidencias** | **INFERENCIA:** sin camino de ejecución en runtime. **Sí está en la imagen**: el Dockerfile copia `node_modules` tras `npm prune --omit=dev`, y `nuxt` es dependencia de producción. |
| `node-forge` 1.4.0 (≤1.4.0) | alta | `nuxt → @nuxt/cli → listhen` (certificados HTTPS de desarrollo) | 0 coincidencias en el runtime de `nitropack` ni `@nuxt/nitro-server` | **INFERENCIA:** herramienta de desarrollo/CLI; no se ejecuta en producción. Está en la imagen. |
| `nuxt` 4.5.2 (3 avisos: moderado ×2, bajo) | moderada | Directa | GHSA-vjh5 (omisión de `routeRules` con rutas en otra capitalización): en `nuxt.config.ts` `routeRules` es `{}` si `SEDE_INDEXABLE=true` (producción) → **nada que omitir**; en staging solo omite `X-Robots-Tag`. GHSA-xfq5 (*server islands*): `NuxtIsland` → 0 usos. GHSA-jjm6: afecta con `pages` deshabilitado; aquí está habilitado. | **INFERENCIA:** no aplicables en la configuración actual. |
| `@nuxt/nitro-server`, `@nuxt/vite-builder`, `nitropack`, `fast-uri`, `deepmerge`, `serialize-javascript`, `@rollup/plugin-node-resolve` | baja/mod. | Transitivas de `nuxt` | No analizadas una por una | **Sin evidencia de explotabilidad**; se corrigen con la misma actualización. |

**Conclusión para la decisión:** los avisos **no se demuestran explotables en runtime** con la configuración actual (INFERENCIA, con la búsqueda estática como base), **pero** (1) todos viven dentro de la imagen de producción, (2) `npm audit` y Trivy los cuentan igual, por lo que **el CI queda en rojo y se normaliza ignorarlo**, y (3) corregirlos cuesta una actualización (`npm audit fix` / subir `nuxt` ≥ 4.6.2). **Recomiendo corregirlos**, no por explotabilidad probada sino por higiene del CI. → **H-11, T-09.**
Limitación: no construí la imagen ni inspeccioné `.output/` real; confirmarlo: `docker run --rm <imagen> sh -c 'ls node_modules/undici node_modules/node-forge'` y `docker run --rm <imagen> sh -c 'grep -rl undici .output/server | head'`.

---

## 4. Correcciones a mis informes anteriores

Una auditoría que no corrige sus propios errores no sirve. Estos son los míos, ya corregidos en este documento:

| # | Lo que dije antes | Lo que es | Cómo lo supe |
|---|---|---|---|
| 1 | «El contrato OpenAPI tiene 3 operaciones» | **34 operaciones**: 7 con `x-status: implemented`, 27 `pending`; **15 tienen ruta real** en el backend (8 de ellas siguen marcadas `pending`). | Leí `contract/openapi.yaml` completo. Mi cifra venía de la sección «Operaciones» de la matriz de trazabilidad, que estaba desactualizada solo en esa tabla. |
| 2 | «La matriz de trazabilidad está vencida» | Solo estaba desactualizada la **fecha** y la **tabla de operaciones**. Al regenerarla en el clon, los totales son **idénticos**: 137 criterios, 24 con prueba, 50 con evidencia, 12 desviaciones, 84 sin evidencia, 36 %. | Ejecuté `node scripts/trazabilidad.mjs` en el clon y comparé con `docs/trazabilidad.md`. |
| 3 | «Una clave privada SSH en el primer commit» | **Falsa alarma.** Los dos `.md` solo citan el encabezado de una llave privada SSH (el texto «BEGIN … PRIVATE KEY») como patrón de búsqueda; `ssh-keygen` no los reconoce como clave. | `ssh-keygen` sobre el contenido. |
| 4 | «El login del panel está roto en staging» | Funciona, **por una vía accidental** y frágil (§3.3). | Secuencia de 3 peticiones con credenciales ficticias. |
| 5 | «3 de las 5 políticas dan 404» | Con los slugs que usa el **sitio**, las 5 responden 200 (usé los slugs de la API). Lo real es peor en otro sentido: **son marcadores de posición** (H-21), la API y el sitio usan slugs distintos, y `/politicas` (destino de la miga de pan) da 404 (H-22). | Re-sondeé con los slugs de `sitemap.ts`. |
| 6 | «Puede que no haya página 404 personalizada» | **Sí la hay**, completa (RF-B1-007 ✅). El JSON que vi salía porque `curl` no envía `Accept: text/html`. | Re-sondeé con `Accept: text/html`. |
| 7 | Números de línea: `AuditoriaController.php:338`, `AuthService.php:149-150`, `FilesMediaService.php:175` | **Eran incorrectos.** Los correctos: `AuditoriaController.php:60`, `AuthService.php:73-74`, `FilesMediaService.php:41` (disco) y `:120` (extensión). Ese archivo de controlador tiene 64 líneas. | Re-verifiqué cada cita de este documento contra el archivo (tarea de verificación final). |
| 8 | «El login responde más rápido cuando el correo no existe» | Lo dije como hecho; **no lo medí**. Es una **INFERENCIA** de la lectura del código, sin cronometraje. | — |
| 9 | «`deploy` es `root` efectivo; `command=` no está en ningún script» (H-03 como **Alto**) | **Sobrestimado.** Lo infería solo del repo. En el servidor la llave **sí** tiene `command=` y `no-pty`, y el guion valida sus argumentos. Rebajo H-03 a Medio; queda la pertenencia al grupo `docker`, innecesaria si el script ya usa `sudo`. | Observado en la salida con `sudo` del 2026-10-28 (§3.2). |
| 10 | «Quitar `deploy` del grupo `docker`; el script ya corre con `sudo`» (T-02, y se lo dije a José en el informe) | **Error mío.** El script del repo llama a `docker` sin `sudo`; quitar el grupo habría roto el despliegue. Lo detecté al leer el script antes de ejecutar nada. | `despliegue-remoto.sh:92`; `despliegue.yml:290`. |

---

## 5. Matriz de cumplimiento del Módulo 01

Leyenda en §0. «Evidencia» usa las etiquetas HECHO / INFERENCIA. «Ref.» remite al hallazgo (§6) o a la tarea (§8).
**Todo lo "vivo" es de staging** (L-1).

### 5.1 Barra superior, footer e identidad (§2.1 del módulo)

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-B1-001 · RF-B2-004 · RF-B3-053 | Top bar GOV.CO, logo → `https://www.gov.co/home/`, 56 px, Cobalt, área ≥44×44 | 🟡 | **HECHO:** `.barra-superior-govco` con `height: 3.5rem` (56 px) y fondo `--govcolor-cobalt`; se renderiza en la plantilla de las páginas capturadas. El enlace va a `https://www.gov.co/` (se exige `/home/`; la API `identidad/top-bar` sí dice `/home/` pero el sitio no la consume). Área activa ≥44×44: no medida. | H-27 · T-27 |
| RF-B1-002 · RF-B2-005 · RF-B3-057 | Footer completo (dirección, CP, municipio/depto, horario, redes, conmutador, línea gratuita, anticorrupción, correos, mapa, políticas) | ✅ | **HECHO:** todos los elementos presentes en el HTML del pie, incluido «GOV.CO, el portal del Estado colombiano. Marca país Colombia.». Observación: 3 de las 4 redes de la API (falta YouTube; su URL en la API contiene un espacio). | H-22 |
| RF-B2-006 | Teléfonos con +57 (excepto 018000/019000) | ✅ | **HECHO:** conmutador y línea anticorrupción con `(+57)`; la línea gratuita 018000 sin prefijo. | — |
| RF-B1-043 · RF-B3-056 | Logo de la Alcaldía arriba a la izquierda, enlazado a inicio; enlace «Inicio» | 🟡 | **HECHO:** `<a href="/" class="enlace-logotipo-govco">` con `<img src="/logo-entidad.png" alt="Alcaldía Distrital de Santa Marta">` (HTTP 200, 46 767 B); enlace «Inicio» en el menú. Tamaño máximo (50 px / 80-90 px) no medido. La API apunta a `/storage/entidad/escudo.svg`, que da **404**. | H-22 · T-21 |
| RF-B1-098 · RF-B3-045/046/059 | Identidad Kit UI: Nunito Sans + Verdana, Cobalt, logo sin deformar | 🟡 | **HECHO:** Bootstrap v5.0.2, `all.css` con 13 `@font-face`, tipografías servidas (HTTP 200), 18 usos de `#0943B5`. Familia CAG de la matriz: 33/34 con evidencia, 22 con prueba. No ejecuté la «auditoría de marca (0 violaciones)». | T-28 |
| RF-B3-055 | Botón de idioma | ⏸ | Diferido por **Decisión #16** (2026-06-05): castellano únicamente. | — |
| RF-B3-061 | Botón «Volver arriba» | ✅ | **HECHO:** `VolverArriba.vue` y botón «Volver arriba» en el HTML. Aparición al hacer scroll: **INFERENCIA** (no ejecutada). | — |

### 5.2 Navegación y búsqueda (§2.2)

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-B1-003 · RF-B2-007 · RF-B3-072 | Menú: Inicio, Transparencia y acceso a la información pública, Atención y Servicios a la Ciudadanía, Participa (+≤3; máx. 7; ≤2 niveles; `aria-label`) | 🟡 | **HECHO:** 7 ítems de primer nivel, 2 niveles, orden Inicio → Transparencia → Atención y Servicios a la Ciudadanía → Participa → PQRSD → Normativa → Noticias; `aria-label="Menú principal de la Sede Electrónica"`. **Falla:** la etiqueta visible es «Transparencia», no «Transparencia y acceso a la información pública» (el texto completo existe en `sitemap.ts:185`). El menú es estático; la API `identidad/menu` no lo alimenta (INFERENCIA). | H-27 · T-27 |
| RF-B1-004 | Menú hamburguesa <768 px | 🟡 | **INFERENCIA:** botón «Abrir el menú de navegación» presente. `tests/diseno.mjs` mide viewports pero **no lo ejecuté** y usa solo Chromium. | H-29 |
| RF-B1-005 · RF-B2-035 · RF-B3-067 | Buscador visible en el header, solo contenido de la sede, ≥27 caracteres, botón de borrado, predictivo, orden por pertinencia | 🟡 | **HECHO:** campo con placeholder, botones «Limpiar la búsqueda» y «Buscar» en el header; `/buscar?q=licencia` → 200. **Falta:** predictivo (no existe); ancho mín. de 27 caracteres no verificable (CSS `width:100%`, sin `size`); pertinencia y alcance: `auditoria-sede.md` registra D-03 «el buscador no busca». | H-23 · T-22 |
| RF-B1-006 · RF-B2-036 | Autocompletado ≤10 sugerencias con ≥3 caracteres; tolerancia ortográfica; resultados con fecha, categoría, extracto, autor, miniatura | ❌ | **HECHO:** `BuscadorGovco.vue:7` y `buscar.vue:25` declaran que **no se implementa**. | H-23 · T-22 |
| RF-B1-010 · RF-B2-037 | Mapa del sitio en el footer, autoactualizado; `sitemap.xml` | ✅ | **HECHO:** `/mapa-del-sitio` → 200 (55 664 B); `/sitemap.xml` → 200 (1 389 B); `/robots.txt` → 200. Se genera desde `sitemap.ts` (estático): «autoactualizado» dependerá del CMS. | T-24 |
| RF-B2-038 · RF-B3-073 | Migas de pan en páginas internas, no en el home | 🟡 | **HECHO:** `aria-label="Miga de pan de 2 niveles"` en `/tramites`; ausente en el home (correcto). **Falla:** en las políticas la miga apunta a `/politicas`, que da **404**. | H-22 · T-21 |
| RF-B2-041 | 0 vínculos rotos | ❌ | **HECHO:** de 88 URLs sondeadas, 87 → 200 y **1 → 404** (`/politicas`). Además: `/storage/entidad/escudo.svg` → 404; URL de YouTube con espacio en la API; los slugs de política de la API (`terminos-y-condiciones`, `tratamiento-de-datos`, `derechos-de-autor`…) → 404 en el sitio. | H-22 · T-21 |

### 5.3 Inicio, errores, salidas y cookies (§2.3)

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-B1-011 | Módulo de noticias en el home (imagen, título ≤150, descripción ≤200, fecha, orden inverso) | ❌ | **HECHO:** `/noticias` muestra «en preparación»; el home no contiene módulo «Últimas noticias»; las operaciones de noticias del contrato están `pending`. | H-24 · T-23 |
| RF-B1-042 · RF-B3-069 | Carrusel con indicadores, Play/Stop, flechas; pausado por defecto | 🟡 | **HECHO:** botones «Diapositiva anterior/siguiente», 4 indicadores «Ir a la diapositiva n de 4», control «Reanudar la reproducción automática»; pausa por defecto en el código (`CarruselGovco.vue:115` `autoplay: false`, `:122` `reproduciendo = ref(false)`). **Falla:** las 4 imágenes son `fondo-gris-carrusel.jpg` (marcador de posición) y no hay CMS que las gestione. | H-24 · T-23 |
| RF-B1-007 · RF-B2-039 · RF-B3-142 | Página 404 propia: razón + ≥3 opciones | ✅ | **HECHO:** con `Accept: text/html` → HTTP 404, página propia con la razón, el código y **6 enlaces** (portada, buscar, mapa del sitio, 3 secciones). | — |
| RF-B1-071 · RF-B2-040 · RF-B3-094 | Aviso de salida a sitio externo con confirmación | ✅ | **HECHO:** `ModalAvisoSalida.vue`, `useAvisoSalida.ts` (coincidencia por `===` o `.endsWith('.'+dominio)`: no admite `evilgov.co`), `tests/aviso-salida.test.ts`. | — |
| RF-B1-008 | Banner de cookies: nada no esencial por defecto; aceptar/rechazar/configurar; revocar | 🟡 | **HECHO:** banner con categoría «analítica», ninguna premarcada; enlace «Configurar cookies» en el pie. **Falla (INFERENCIA fuerte):** las respuestas HTML para navegador (`Accept: text/html`) incluyen el script `cloudflareinsights` antes de cualquier consentimiento — visto en la página 404 y en las capturas de las páginas de política. Es un script de analítica inyectado por Cloudflare en el borde, no por la app. No determiné si fija cookies. | H-25 · T-26 |

### 5.4 Políticas en línea (§2.4)

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-B1-009 · RF-B3-058 | Publicar en el footer: Términos, Privacidad/Datos (Ley 1581), Derechos de autor, Cookies, Accesibilidad; **descargables en formato abierto** | ❌ | **HECHO:** las 5 páginas existen y responden 200 con los slugs del sitio, **pero cada una dice «Este documento está en preparación»** (80-88 palabras en total, incluyendo el menú lateral) y no hay ningún enlace de descarga (0 coincidencias de PDF/ODT/«descargar»). `/accesibilidad` sí existe (200). | H-21 · T-20 |
| RF-B2-008 | Términos y condiciones con 6 componentes mínimos | ❌ | **HECHO:** marcador de posición; 0 de 6 componentes. | H-21 · T-20 |
| RF-B2-009 | Privacidad: categorías de datos, finalidades, derechos ARCO, contacto del responsable, mecanismo de reclamo | ❌ | **HECHO:** marcador de posición en «Seguridad y privacidad» y en «Protección y tratamiento de datos personales»; 0 de los elementos. | H-21 · T-20 |
| RF-B2-010 | Política de derechos de autor / autorización de uso de contenidos | ❌ | **HECHO:** marcador de posición. | H-21 · T-20 |

### 5.5 Componentes del Kit UI v9.2 (§2.5)

> Esta tabla se apoya en **lectura y búsqueda estática** del código y en pruebas existentes, **no en pruebas en navegador** (L-4). «🟡» aquí significa «hay componente; falta evidencia de los estados exigidos». Una primera versión de esta tabla usó conteos de `grep` demasiado amplios (por ejemplo, tomó `aria-expanded` del menú como prueba de un acordeón); la corregí leyendo los archivos.

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-B2-084/085 | Componentes del Kit UI; grilla de 12 columnas; orden de lectura Área de Servicio → Trámite | 🟡 | **HECHO:** 12 componentes en `app/components/govco/`. **INFERENCIA:** grilla heredada de Bootstrap; orden del lector de pantalla no verificado. | T-28 |
| RF-B3-060 | Bootstrap 5.0, 12 columnas, 6 breakpoints, espaciado ≥24 px | ✅ | **HECHO:** `bootstrap.min.css` v5.0.2 servido desde `/govco/`. El espaciado de 24 px no se midió. | — |
| RF-B3-062 | Acordeón (4 estados; no abre al recibir foco; `aria-expanded`) | ❌ | **HECHO (ausencia):** 0 archivos de `sitio/app/` con «acordeón», «accordion» o «collapse»; los `aria-expanded` que existen son del menú y de botones del Kit. Coincide con la auditoría propia (D-27). | T-28 |
| RF-B3-063 | Alerta modal (ESC, clic exterior, no pantalla completa) | 🟡 | **HECHO:** existe `ModalAvisoSalida.vue` con pruebas (`tests/aviso-salida.test.ts`). **INFERENCIA:** cierre con ESC probable (`Escape` en 5 archivos); clic exterior y no-pantalla-completa no verificados; es el único modal del sitio. | T-28 |
| RF-B3-064 | Toast (informativa/positiva/negativa) | ❌ | **HECHO (ausencia):** 0 coincidencias de «toast» en `sitio/app/`. | T-28 |
| RF-B3-066 | Botones con 4 variantes y 4 estados, `aria-label` | 🟡 | **HECHO:** botones del Kit con `aria-label` en el HTML. Hover/Focus/Disabled no probados por mí. | T-28 |
| RF-B3-068 | Galería de aplicaciones, Enter/Esc | ✅ | **HECHO:** `GaleriaAplicacionesGovco.vue` instanciada en `layouts/default.vue:250`; botón «Abrir aplicaciones y servicios» con panel (Portal del Estado, Carpeta Ciudadana…) presente en el HTML. Enter/Esc no probados. (La auditoría propia del 2026-10-01 decía «nunca se instancia»: ya no es cierto.) | — |
| RF-B3-070 | Spinner; informar estado si >10 s | 🟡 | **HECHO:** estado `cargando` en `pages/tramites/index.vue:300`. Sin aviso de progreso a los 10 s. | T-28 |
| RF-B3-075 | Paginación (≥44×44 px, elipsis, `aria-current`) | 🟡 | **HECHO:** paginación en `pages/tramites/index.vue` con `aria-current="page"` (línea 801). Tamaño táctil y elipsis: sin medir. | T-28 |
| RF-B3-076 | Tablas con ordenamiento asc/desc | ❌ | **HECHO (ausencia):** 0 coincidencias de `aria-sort`; el único ordenamiento (`pages/normativa.vue:230`) es un orden fijo por fecha, no una columna ordenable por el usuario. Coincide con la auditoría propia (`pqrsd.vue`, D-20). | T-28 |

### 5.6 Integración y redireccionamiento a GOV.CO (§2.6)

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-B1-070 · RF-B3-128 | Proceso de integración de 7 pasos | ❔ | Sin evidencia del formulario de solicitud a MinTIC, de las observaciones atendidas ni de la verificación (pasos 4-7). Existen documentos internos (`docs/investigacion/INFORME-INTEGRACION-GOVCO.md`, `plan.md`). | H-30 · T-31 |
| RF-B1-100 | Plan de Integración publicado, en el PETI, enviado a la Dir. de Gobierno Digital | 🟡 | **HECHO:** hay documentos de plan en el repo. ❔ Incorporación al PETI y envío: sin evidencia. | H-30 · T-31 |
| RF-B2-001 · RF-B3-125 | Redireccionamiento con enmascaramiento de URL bajo `gov.co` | ❌ | **HECHO:** el sitio vive en `staging.santamarta.gov.co` con `X-Robots-Tag: noindex`; no hay enmascaramiento activo. Depende del paso 6 (MinTIC). | H-30 · T-31 |
| RF-B2-002 · RF-B2-003 | Integrar todos los portales/programas transversales (≤6 meses) | ❔ | Pregunta abierta del §9 del módulo: no hay inventario de micrositios/portales de la Alcaldía. | T-31 |
| RF-B3-126 | Todos los trámites en SUIT, ficha con 4 momentos | 🟡 | **HECHO:** `/tramites` (`implemented`), datos SUIT en `database/datos/tramites-0043-suit-visor.json` + script de ingesta. ❔ Ficha con los 4 momentos y enlace al portal distrital. | T-31 |
| RF-B3-127 | Estados estandarizados (registrada → recibida → en trámite → resuelta) | ❌ | **HECHO:** el formulario de petición no envía nada (`realizar-una-peticion.vue:622`: «no se registrará nada») y `/seguimiento` dice «en preparación». | H-32 |
| RF-B1-099 · RNF-B1-027 | Doble pila IPv4 + IPv6 con DNS de doble resolución | 🟡 | **HECHO:** `staging` resuelve 2 registros A y 2 AAAA (vía Cloudflare, que termina IPv6). **INFERENCIA:** `compose.yaml` publica nginx solo por IPv4, irrelevante detrás de Cloudflare. Producción: no verificada. | H-31 · T-30 |

### 5.7 Deltas (RF-01-Dxx), RNF, RN, UC y HU

| ID | Lo que exige | Estado | Evidencia | Ref. |
|---|---|---|---|---|
| RF-01-D01 · RN-01-D01 · HU-01-D01 | Consentimiento persistido y versionado; re-solicitar al cambiar la política o a los 12 meses; sin cookies no esenciales con consentimiento caducado | 🟡 | **HECHO:** `useConsentimientoCookies.ts` guarda `fecha`, `version` (`VERSION_POLITICA = 1`) y categorías; caduca a los 12 meses; `tests/consentimiento.test.ts`. **Faltan:** registro del lado servidor («el sistema guarda registro») y el log «consentimiento caducado, cookie bloqueada»: hoy solo vive en `localStorage`. | H-25 · T-26 |
| RF-01-D02 · HU-01-D02 | CRUD del menú y de noticias/carrusel desde el CMS | ❌ | **HECHO:** no existe CMS (D-15); menú y carrusel son estáticos; sin endpoints de escritura. | H-24 · T-24 |
| RN-01-D02 | Menú máx. 7 ítems / 2 niveles, rechazando altas que lo violen | ❌ | **HECHO:** se cumple por configuración estática; **no hay validador** que lo haga cumplir. | H-26 · T-24 |
| RF-01-D03 · HU-01-D03 | Lista blanca de dominios **administrable** | 🟡 | **HECHO:** la lista blanca funciona pero está **fija en el código** (`useAvisoSalida.ts`, 9 dominios); no es administrable. | H-26 · T-25 |
| RN-01-D03 | El modal no se muestra para dominios de confianza | ✅ | **HECHO:** lógica y pruebas en `useAvisoSalida.ts` / `tests/aviso-salida.test.ts`. | — |
| RNF-01-D01 · HU-01-D04 | Resiliencia ante caída del CDN GOV.CO | ✅ | **HECHO:** Kit UI y fuentes autoalojados (`/govco/…` → 200); no dependen de `cdn.www.gov.co`. Divergencia deliberada del texto del requisito (reintento ≤3 s); conviene un ADR. | T-28 |
| RNF-B1-033 | ≥3 navegadores sin diferencias | ❔ | **HECHO:** `tests/diseno.mjs` lanza solo `chromium` (línea 49). Sin evidencia en Firefox, Safari o Edge. | H-29 · T-29 |
| RNF-B3-045 | 100 % componentes Cobalt + tipografía | 🟡 | Ver RF-B1-098. | T-28 |
| RNF-B3-046 | Logo GOV.CO sin modificaciones | ❔ | No inspeccionado visualmente. | T-28 |
| RNF-B3-005 | Tap-target ≥44×44 px | 🟡 | **HECHO:** `tests/diseno.mjs:328` mide ≥44 px en los controles que revisa; solo Chromium; no ejecutado por mí. | T-29 |
| RN-B1-001 · RN-B3-006 · RN-B3-005 · RN-B3-007 · UC-B2-009 | Integración a GOV.CO, todos los portales, ventanillas únicas | ❔ | Ver §5.6. | T-31 |
| RN-B3-004 | Al menos una sede electrónica | 🟡 | **HECHO:** sede operando en staging. **INFERENCIA:** sin evidencia de puesta en producción. | T-30 |
| RN-B2-028 | Portales bajo dominio GOV.CO | ❌ | Ver RF-B2-001. | T-31 |
| RN-B2-029 | Kit UI obligatorio; solo el footer con color institucional | 🟡 | Ver RF-B1-098. | T-28 |
| RN-B1-012 · RN-B3-025 | Castellano | ✅ | **HECHO:** `<html lang="es-CO">`. | — |
| RN-B1-014 | Sin publicidad ni marcas ajenas | ✅ | **INFERENCIA:** sin banners ni marcas ajenas en la home y las páginas capturadas. | — |
| RN-B1-017 · RN-B2-025 | +57 en teléfonos | ✅ | Ver RF-B2-006. | — |
| HU-B3-028 | Datos de contacto siempre visibles en el footer | ✅ | **HECHO:** teléfono +57, línea gratuita, anticorrupción, correo institucional y dirección en el pie. | — |
| HU-B1-001 | Primer resultado en Google (top 5) para «Alcaldía Santa Marta» | ❔ | Staging es `noindex` (correcto); no se auditó producción ni Search Console. | T-32 |

### 5.8 Contraste con la auditoría propia del proyecto (`auditoria-sede.md`, 3.ª edición, 2026-10-01)

El proyecto ya tiene su propia auditoría de diseño. La comparé fila a fila con lo que encontré hoy en el código y en staging. Dos conclusiones: (1) **el proyecto avanzó** desde esa edición; (2) **esa auditoría no es una fuente fiable por sí sola**, porque su tabla §4.1 contradice su propio inventario de defectos (D-01, D-02, D-09) y el código.

| Requisito | Tabla §4.1 de la auditoría propia | Su propio inventario de defectos | Código / staging hoy (mi matriz) |
|---|---|---|---|
| RF-B1-008 · cookies | ❌ «no existe ninguna implementación» | D-01: banner de 441 líneas montado; «no hay revocación» y «el composable `useConsentimientoCookies` no existe» | 🟡 — existe `useConsentimientoCookies.ts` (versión, caducidad 12 meses, pruebas) y enlace «Configurar cookies» en el pie; falta el registro en servidor y el script de analítica de Cloudflare se inyecta antes del consentimiento |
| RF-01-D01 · consentimiento versionado | ❌ «depende de RF-B1-008, que no existe» | D-01 (ídem) | 🟡 |
| RF-B1-071 · aviso de salida | ❌ «no existe» | D-02: `useAvisoSalida` + `ModalAvisoSalida` montados, lista blanca de 9 dominios | ✅ — con pruebas; la coincidencia por sufijo no admite `evilgov.co` |
| RF-01-D03 · lista blanca | ❌ «depende de RF-B1-071» | D-02 | 🟡 — funciona pero fija en el código (no administrable) |
| RF-B3-063 · modal | ❌ «no implementado en el sitio» | D-02: modal con Esc y clic en el fondo | 🟡 — existe un único modal; estados no probados por mí |
| RF-B3-068 · galería de aplicaciones | ❌ «existe y nunca se instancia» | D-27 | ✅ — instanciada en `layouts/default.vue:250` y presente en el HTML |
| RF-B1-007 · 404 con ≥3 opciones | 🟡 «ofrece una salida» | D-09: «Cerrado», 6 salidas | ✅ — 6 enlaces verificados con `Accept: text/html` |
| RF-B2-041 · cero vínculos rotos | ⛔ «sin rastreador no se puede acreditar» | — | ❌ — **medido**: 87/88 URLs → 200 y 1 → 404 (`/politicas`) |

**Filas que siguen igual en ambas:** RF-B3-062 acordeón ❌, RF-B3-064 *toast* ❌, RF-B3-076 tablas con ordenamiento ❌ y RF-B1-009 políticas ❌ (publicadas como «en preparación»). En estas cuatro coincido con el proyecto.

**Qué NO hice:** no reauditoré las 44 filas de §4.1 ni las 2 000 líneas del documento; contrasté solo las filas que tocan el Módulo 01 y que pude verificar contra código o staging. Es un contraste, no una re-auditoría.

**Consecuencia práctica (H-37, T-34):** hoy el equipo puede creer que faltan cosas que ya existen —y priorizar trabajo duplicado— o que cierra lo que no. Conviene que la auditoría propia lleve una cabecera con commit y fecha del código revisado, y que una comprobación automática detecte cuando una fila ❌ «no existe» se contradice con el repositorio (AC-34.5).

---

## 6. Catálogo de hallazgos

**Severidad** (criterio mío, declarado para que puedas discutirlo):
**Alto** = incumplimiento legal claro, brecha de seguridad con camino de explotación plausible, o función central ausente.
**Medio** = debilidad real con condiciones previas, o incumplimiento parcial de un requisito *Must*.
**Bajo** = fragilidad, higiene o desviación menor.
No uso «Crítico»: nada de lo que vi se está explotando, y lo que no pude verificar no lo clasifico por encima de lo que sé.

### 6.1 Seguridad, infraestructura y entrega (transversales al proyecto)

| ID | Sev. | Hallazgo | Evidencia | Tarea |
|---|---|---|---|---|
| H-01 | **Alto** | El repo es público y versiona `accesos.md`, con el inventario operativo de la infraestructura (no se reproduce aquí). | **HECHO:** `api.github.com/repos/SantanderAcuna/sede` → `private: false` sin credenciales; `accesos.md` presente en `origin/main`, `origin/staging`, `origin/develop`. El propio documento dice que secretos de cada entorno se publicaron el 2026-09-30 y se rotaron ese día: **siguen en el historial** aunque ya no sirvan. **INFERENCIA:** quien conozca el inventario sabe qué atacar y cómo está montado. **PARCIAL 2026-10-28:** `accesos.md` ya no está en el árbol de `main`, `staging` ni `develop` (#285-#287; copia local no versionada). **Siguen pendientes:** el repo es público (AC-01.1), el historial conserva el archivo y falta rotar lo vigente (AC-01.2/01.3). | T-01 |
| H-02 | **Alto** | Reglas de rama sin revisión humana ni CI obligatorio. | **HECHO** (§3.2, API pública y sesión de administrador): `pull_request` con 0 aprobaciones; sin `required_status_checks`; *bypass actors* vacío (eso sí está bien); CI «Dependencias» en rojo hoy y fusionable. | T-04 |
| H-03 | Medio | La cuenta de despliegue está en el grupo `docker` (≈ `root`), aunque el script debería correr por `sudo` (el del repo **no** lo hace: hoy el grupo es lo que permite desplegar); y la restricción `command=` no está en ningún script del repo (se puso a mano). **Rebajado de Alto el 2026-10-28:** la restricción existe en el servidor. | **HECHO:** `preparar.sh:79-80`; en el servidor (§3.2) `deploy` ∈ `docker` y su llave tiene `command="/usr/local/bin/desplegar.sh"` + `no-pty`, etc. El guion valida el entorno (lista cerrada), la versión (hexadecimal 7-40) y el tercer argumento (`despliegue-remoto.sh`, ~línea 56-80). **INFERENCIA:** para llegar a `root` habría que robar `DEPLOY_SSH_KEY` **y** hallar un fallo en `desplegar.sh`. Vuelve a **Alto** si `/opt/sede/desplegar.sh` fuera escribible por `deploy` (AC-02.6). **REMEDIADO 2026-10-28:** `deploy` salió del grupo `docker`; el comando forzado es `sudo -n /usr/local/bin/desplegar.sh` con `Defaults:deploy env_keep += "SSH_ORIGINAL_COMMAND"`; la ruta colgante `/opt/sede/desplegar.sh` se retiró de `sudoers`; `preparar.sh` lo hace idempotente (#275). Un despliegue de staging posterior al cambio de comando (run 40789680129) salió en verde. Falta confirmar un segundo despliegue **ya sin** el grupo (AC-02.4) y AC-02.3. | T-02 |
| H-04 | Bajo | Quedan sin verificar el firewall de la nube, el modo TLS de Cloudflare, la restauración de copias, el escaneo desde fuera y la 2FA de GitHub. (El firewall del servidor, `sshd`, Fail2Ban y las reglas de GitHub **ya se verificaron**: §3.2.) | **NO VERIFICABLE** con lo disponible (L-3). | T-03 |
| H-05 | Medio | El job `Credenciales` del CI es más débil de lo que parece: busca archivos por **cuatro nombres concretos** (`pass.md`, `accesos.local.md`, `.accesos.local.env`, `.env`), no por contenido; la búsqueda de claves privadas excluye `*.md`; y el rastro en el historial **solo advierte** (no falla). Por eso `accesos.md` (otro nombre, y es `.md`) pasa. | **HECHO:** `.github/workflows/ci.yml`, job `Credenciales` (pasos «Ningún archivo con credenciales», «Ninguna clave privada en el contenido» — línea 301 — y «Rastro de credenciales en el historial»). El propio comentario del paso explica que advierte a propósito. | T-06 |
| H-06 | Medio | Login depende de una cookie XSRF que llega por casualidad; `/sanctum/csrf-cookie` da 404 y el panel ignora el fallo. | **HECHO** (§3.3): `auth.ts:62`; `default.conf` sin `location /sanctum`. **REMEDIADO en código 2026-10-28 (T-05, PR #296):** `location = /sanctum/csrf-cookie` y `csrf()` comprueba la respuesta. Verificado en staging 2026-10-28 (AC-05.1…05.3). **Falta** AC-05.5 (navegador). | T-05 |
| H-07 | **Alto** | `/panel/auditoria` solo exige `auth:sanctum`; el permiso `auditoria.ver` existe pero el backend no lo exige. | **HECHO (reproducido):** usuario sin permisos → HTTP 200 (`routes/api.php:52-54`). Latente: hoy probablemente solo existe el super-admin (**INFERENCIA**, no consulté la BD). **REMEDIADO 2026-10-28 (T-07, PR #294):** `can:auditoria.ver` en la ruta; 403 sin permiso. | T-07 |
| H-08 | **Alto** | `AuditoriaController::index` llama `$query->limit(0)->get()` tras `paginate()`: `data` sale siempre vacío mientras `meta.total` cuenta. | **HECHO (reproducido):** 3 registros → `meta.total=3`, `count(data)=0`. `AuditoriaController.php:60`. La suite (240/240) no lo detecta. **REMEDIADO 2026-10-28 (T-07, PR #294):** la colección sale de la página ya paginada (`data` == `total`). Se corrigió además `test_auditoria_filtra_por_fecha_desde`, que nunca ejercitaba el filtro. | T-07 |
| H-09 | Medio | Sin `Content-Security-Policy` en sitio ni panel (solo la API la emite, y es `default-src 'self'`). | **HECHO:** `snippets/seguridad.conf` y `nuxt.config.ts` sin CSP; cabeceras de `/` en staging sin CSP. El expediente la exige (SEG-009-RA-04, SEG-012, según `docs/Sección 4 · Seguridad.md`). **PARCIAL 2026-10-28 (T-08, PR #300):** CSP en modo **informe** en sitio y panel, con receptor `/csp-informes`. Sin bloqueo todavía. **HECHO (medido en navegador):** el sitio tiene dos scripts en línea de Nuxt (`importmap` y `window.__NUXT__.config`, que cambian con cada compilación → nonce, no hash); el panel no tiene ninguno propio; y Cloudflare inyecta en ambos un script en línea de detecciones (`__CF$cv$params`, distinto en cada petición) y la analítica `beacon.min.js`. **INFERENCIA:** para aplicar la política hay que (1) nonce en Nuxt, (2) desactivar o acomodar las detecciones de JavaScript de Cloudflare, (3) cerrar T-26. Faltan además 7 días de informes reales (AC-08.2). | T-08 |
| H-10 | **Alto** | MFA no se aplica: el login devuelve siempre `require_mfa: false`. | **HECHO:** `AuthService.php:73-74`. El campo `mfa_habilitado` existe y el panel tiene `MfaView`; `/panel/mfa` está `pending` en el contrato. | T-10 |
| H-11 | Medio | Dependencias vulnerables y CI en rojo (sitio: 10 avisos, 2 altos; backend: `symfony/mime`, `phpunit`). | **HECHO** (§3.1, §3.4). No demostrado explotable en runtime (INFERENCIA). **REMEDIADO 2026-10-28 (T-09):** `npm audit` sitio y panel y `composer audit` en verde; nuxt 4.6.2; job «Dependencias» verde. | T-09 |
| H-12 | Bajo | El panel no tiene ESLint (ni configuración, ni dependencia, ni paso en CI); informes previos marcaban «Vue-01..10 ✅» sin herramienta que lo respalde. | **HECHO** (revisión previa). **REMEDIADO 2026-10-28 (T-11, PR #297):** ESLint con base de 15 advertencias y paso en CI. Los informes previos «Vue-01..10 ✅» siguen sin respaldo propio. | T-11 |
| H-13 | Medio | Subidas: disco fijo `public` pese a `FILESYSTEM_DISK=s3`; nginx envía `/storage/` a PHP; `svg` permitido; límite validado 50 MB vs 20 MB (PHP) y 25 MB (nginx). | **HECHO:** `FilesMediaService.php:41`; `.env.example:93`; `SubirArchivoRequest.php:25`; `docker/app/php.ini`. **HIPÓTESIS:** los archivos subidos no se pueden descargar (la API apunta a `/storage/entidad/escudo.svg` → 404 en staging, consistente con esto). **REMEDIADO en código 2026-10-28 (T-12, PR #299):** disco configurado, sin SVG, extensión por contenido, límites alineados, URL firmada en disco local. **Falta** AC-12.5 en staging. Los archivos ya subidos en `public` no se mueven. | T-12 |
| H-14 | Bajo | Intentos de login fallidos sin auditar; posible enumeración por tiempo; cualquier 429 fuerza redirect al login. | **HECHO:** `PermissionAuditListener.php` solo escucha `Login`/`Logout` (+ eventos Spatie); `http.ts`. Enumeración por tiempo: **INFERENCIA, no medida.** **REMEDIADO en código 2026-10-28 (T-13, PR #298):** `login_failed` auditado, hash de relleno, 429 sin redirección. **Enumeración por tiempo: CERRADA con medición (2026-10-28).** La primera medición en staging falló (−269 ms); se corrigió con una constante bcrypt de coste 12 (PR #308) y la re-medición dio una diferencia de medianas de 4,7 ms (AC-13.2). | T-13 |
| H-15 | Medio | `config/cors.php` deja orígenes `localhost`/`0.0.0.0` fijos en producción con `supports_credentials=true`. | **HECHO:** `config/cors.php:29-40`. **REMEDIADO 2026-10-28 (T-14, PR #295):** orígenes de desarrollo solo en `local`/`testing`. | T-14 |
| H-16 | Medio | `SuperAdminSeeder` reescribe la contraseña del super-admin en cada ejecución; la clave de desarrollo es pública y solo se bloquea con `isProduction()`. | **HECHO:** `SuperAdminSeeder.php:36,46,62`. **REMEDIADO 2026-10-28 (T-14, PR #295):** la contraseña se fija solo al crear la cuenta; clave de desarrollo rechazada fuera de `local`/`testing`. **La rotación de la cuenta `id=1` (AC-14.4) sigue siendo tuya.** | T-14 |
| H-17 | Bajo | La zona de límite `acceso` de nginx apunta a `/api/v1/auth/` (no existe; el login real es `/panel/login`), y `TRUSTED_PROXIES`/`SEDE_ORIGENES_PERMITIDOS` no los lee ningún código. | **HECHO:** `nginx.conf:65`, `default.conf:150`; `grep` en `app/ config/ bootstrap/` → 0. Queda el límite de Laravel (3/min por correo, 20/min por IP). **REMEDIADO 2026-10-28 (T-15, PR #295 y #296):** zona `acceso` sobre `/api/v1/panel/login`; `TRUSTED_PROXIES` retirada; `SEDE_ORIGENES_PERMITIDOS` ya se lee. AC-15.1 verificado en staging 2026-10-28. | T-15 |
| H-18 | Bajo | Higiene del repo público: 19 `.md` en la raíz (informes de agentes, planes, `accesos.md`) y 6 scripts de depuración `panel/test_*.mjs`. | **HECHO:** `git ls-files`. | T-01 |
| H-19 | Bajo | La barra superior pública enlaza a `/admin/acceso` («Iniciar sesión»). | **HECHO:** `<a href="/admin/acceso" class="boton-login">` en la home. **INFERENCIA:** expone la ruta del panel y mezcla el login ciudadano y el de funcionarios; decisión de producto. | T-27 |
| H-20 | Bajo | Se sirve `Verdana-Regular.ttf` desde `/govco/assets/fonts/`. | **HECHO:** HTTP 200 (186 188 B). **HIPÓTESIS:** su redistribución podría requerir licencia; **comprobar** que el paquete oficial del Kit UI de MinTIC la incluye con autorización (`public/govco/LICENSE.md`). | T-28 |

### 6.2 Conformidad del Módulo 01 y adyacentes

| ID | Sev. | Hallazgo | Evidencia | Requisito | Tarea |
|---|---|---|---|---|---|
| H-21 | **Alto** | Las 5 políticas son marcadores de posición («en preparación»), sin acto administrativo, sin descarga en formato abierto. | **HECHO** (§5.4): 5 × HTTP 200, 80-88 palabras, 0 enlaces de descarga. | RF-B1-009, RF-B2-008/009/010; Ley 1581/2012; Ley 1712/2014; Res. 1519/2020 | T-20 |
| H-22 | Medio | Vínculos rotos y desalineados: `/politicas` → 404 (destino de la miga de pan); slugs de políticas en la API ≠ sitio; `escudo.svg` → 404; URL de YouTube con espacio; el sitio no consume la API de identidad (pie con 3/4 redes). | **HECHO:** 87/88 URLs → 200, 1 → 404; ver §5.2. | RF-B2-041, RF-B2-038 | T-21 |
| H-23 | **Alto** | Buscador sin autocompletado, sin tolerancia ortográfica, sin ranking de pertinencia demostrable (D-03). | **HECHO:** `BuscadorGovco.vue:7`, `buscar.vue:25`. | RF-B1-005/006, RF-B2-035/036 | T-22 |
| H-24 | **Alto** | Sin módulo de noticias con datos; carrusel con imágenes de relleno; sin CMS (D-15). | **HECHO:** `/noticias` «en preparación»; 4 × `fondo-gris-carrusel.jpg`; contrato con noticias `pending`. | RF-B1-011, RF-B1-042, RF-01-D02 | T-23 |
| H-25 | Medio | Cookies: script de analítica de Cloudflare en el HTML antes del consentimiento; el registro de consentimiento solo existe en el navegador. | **INFERENCIA fuerte** (HTML para navegador) y **HECHO** (`localStorage`). | RF-B1-008, RF-01-D01, RN-01-D01; Ley 1581/2012 | T-26 |
| H-26 | Medio | Sin validador del tope 7 ítems / 2 niveles y sin lista blanca administrable. | **HECHO** (§5.7). | RN-01-D02, RF-01-D03 | T-24, T-25 |
| H-27 | Bajo | Etiqueta del menú «Transparencia» (se exige la completa); la barra superior enlaza a `https://www.gov.co/` en vez de `/home/`. | **HECHO** (§5.1-5.2). | RF-B1-003, RF-B1-001 | T-27 |
| H-28 | Medio | Kit UI: **no existen** el acordeón (RF-B3-062), el *toast* (RF-B3-064) ni tablas con ordenamiento por columna (RF-B3-076) —tres requisitos *Must* de la auditoría propia—; el modal, el spinner y la paginación existen, pero sin evidencia de todos los estados exigidos. | **HECHO** (ausencia: 0 coincidencias de acordeón/accordion/collapse, toast y `aria-sort` en `sitio/app/`) / **INFERENCIA** (estados del resto). | RF-B3-062..076 | T-28 |
| H-29 | Medio | Verificación de diseño solo en Chromium; el requisito exige ≥3 navegadores. | **HECHO:** `tests/diseno.mjs:49`. | RNF-B1-033 | T-29 |
| H-30 | **Alto** | Sin evidencia de avance administrativo de la integración a GOV.CO. | ❔ (§5.6). | RF-B1-070/100, RF-B2-001/002/003, RN-B1-001; Decreto 2106/2019 arts. 14-15 | T-31 |
| H-31 | Bajo | Doble pila: correcta en el borde de staging; origen solo IPv4; producción sin verificar. | **HECHO** (DNS) / **INFERENCIA** (origen). | RF-B1-099, RNF-B1-027 | T-30 |
| H-32 | **Alto** | El formulario de derecho de petición no envía nada y `/seguimiento` no existe. Es de otro módulo (04 · PQRSD) pero es la función legal central de la sede. | **HECHO:** `realizar-una-peticion.vue:622`; `/seguimiento` «en preparación». | RF-B3-127 (estados), Ley 1437/2011 | T-33 |
| H-33 | Medio | Trazabilidad: 84 de 137 criterios sin evidencia (cobertura 36 %); contrato con 8 rutas implementadas aún marcadas `pending`. | **HECHO:** matriz regenerada en el clon; `contract/openapi.yaml`. | Mantenibilidad / auditoría | T-34 |
| H-34 | Medio | Defectos reales pasan una suite verde (H-07, H-08); la cobertura real es **93,9 %** frente al 95 % declarado; PHPStan nivel 8 tiene 1 error; ESLint/Stylelint y la puerta de cobertura nunca llegaron a `main`. | **HECHO:** §3.1 y §3.1.1; `DEPLOY-STAGING-REPORT.md` (push bloqueado por falta del permiso `workflow`). | Calidad | T-35 |
| H-35 | Bajo | HU-B1-001 (primer resultado en Google): sin medición posible hasta que haya producción indexable. | ❔ | HU-B1-001 | T-32 |
| H-36 | Bajo | La home no tiene ningún `<h1>` (0 encontrados): título de página principal ausente para lectores de pantalla y buscadores. | **HECHO:** análisis del HTML de la home en staging (`h1 = 0`; `title` y `lang=es-CO` sí presentes). | WCAG 1.3.1 / 2.4.6 (módulo 07); HU-B1-001 | T-27 |
| H-37 | Medio | La auditoría propia (`auditoria-sede.md`, 3.ª edición, 2026-10-01) **se contradice a sí misma y con el código**: su tabla §4.1 marca como «no existe» el banner de cookies, el aviso de salida y la galería, mientras su propio inventario de defectos (D-01, D-02) los describe como existentes y el código los tiene montados. Quien lea solo la tabla subestima el avance; quien lea solo el detalle no ve el cuadro completo. | **HECHO:** contraste en §5.8 (filas RF-B1-008, RF-B1-071, RF-01-D01, RF-01-D03, RF-B3-068 vs. D-01, D-02, `layouts/default.vue:248-257`). | Mantenibilidad / auditoría | T-34 |
| H-38 | **Alto** | La cuenta de **super-administrador de staging** (`id=1`) no tiene MFA y su clave es de 8 dígitos; la clave circuló por un chat. Con el MFA sin aplicar (H-10) nada más protege la cuenta con todos los permisos. | **HECHO:** §3.3.1 (login 200, rol `super-admin`, `mfa_habilitado=false`, límite de 3 intentos/min). **INFERENCIA:** la cuenta de producción tendría el mismo patrón si se creó igual (no la vi). | OWASP ASVS V2; ISO 27001 A.5.17/A.8.5 | T-14, T-10 |
| H-39 | Medio | **Producción se despliega sin aprobación humana** y la llave SSH de despliegue es un secreto de **repositorio**, no de entorno. | **HECHO** (salida del script, §3.2): `production` y `staging` solo tienen política de ramas, sin revisores ni espera; `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT`, `DEPLOY_KNOWN_HOSTS` están a nivel de repositorio. **INFERENCIA:** quien pueda crear un flujo de trabajo en una rama del repo (cuenta con escritura, o cuenta comprometida) podría leer esa llave; el daño depende de que esté restringida a un solo comando (**HIPÓTESIS**, §9.1). | OWASP CI/CD-SEC-1, -2; ISO 27001 A.8.32 | T-04, T-02 |
| H-40 | Bajo | El servidor tiene **6 paquetes de seguridad del núcleo sin aplicar** (llevan 4 semanas encendido sin reiniciar); `unattended-upgrades` los excluye por configuración (lista negra de `linux-*`). | **HECHO** (§3.2, tercera ejecución). **INFERENCIA:** quedan abiertas vulnerabilidades del núcleo que se corrigen con el reinicio; no identifiqué cuáles. **Estado 2026-10-28:** José indica que el servidor sirve **solo staging**, por lo que el reinicio no afecta a ciudadanos; lo difiere a otro día (pendiente, sin fecha). **HECHO** (repo): los servicios de `compose.yaml` tienen `restart: unless-stopped` (la tarea `copia` es puntual, `restart: "no"`) y `preparar.sh` habilita `docker` al arranque; **sin verificar en el servidor** (el compose instalado puede diferir, H-42). | ISO 27001 A.8.8 | T-03 |
| H-41 | Medio | El origen acepta HTTP/HTTPS **desde cualquier IP**, no solo desde Cloudflare; el puerto 22 también está abierto al mundo. | **HECHO:** `ufw status` (§3.2): `80/tcp` y `443/tcp` «ALLOW IN Anywhere»; `22/tcp` «LIMIT IN Anywhere». **INFERENCIA:** quien conozca la IP del servidor (el repo público la publicaba en `accesos.md`, H-01) puede saltarse el WAF y los límites de Cloudflare. Mitigantes observados: SSH solo con llave, `root` cerrado, Fail2Ban con 51 bloqueos. **Remediado el 2026-10-28 (autorizado por José):** `ufw` ahora permite 80/443 solo desde los 15 rangos IPv4 y 7 IPv6 de Cloudflare (las dos reglas «Anywhere» se eliminaron) y `DOCKER-USER` replica la lista (`RETURN` por rango + `DROP` final en `eth0`); respaldo previo en el servidor y reversión disponible. **HECHO:** tras aplicar, el sitio vía Cloudflare responde HTTP 200 (verificado desde fuera). **HECHO:** conexión directa a la IP del origen saltando Cloudflare → sin respuesta (código 000). **Pendiente:** puerto 22 abierto al mundo (solo llave + Fail2Ban), lista de rangos sin actualización automática y *Authenticated Origin Pulls* (opcional). | OWASP ASVS V14; Cloudflare *Authenticated Origin Pulls* | T-03 |
| H-42 | Medio | El `desplegar.sh` **instalado** en el servidor es una versión anterior a la del repo: le falta la siembra de datos institucionales; nada lo actualiza al desplegar (se instala a mano). | **HECHO:** `diff` (§3.2): única diferencia = bloque «Siembra»; `preparar.sh` instala el script en la preparación inicial, no en cada despliegue. **INFERENCIA:** el siguiente despliegue pasaría en verde sin sembrar. | Calidad de entrega **REMEDIADO 2026-10-28:** se instaló el `desplegar.sh` del repo (sha256 `d356b1c0…`, root:root 755; respaldo del anterior en el servidor) y `preparar.sh` lo instala desde el repo. El despliegue de staging ejecutó la siembra (`datos institucionales sembrados`). | T-02 |
| H-43 | Bajo | Un servicio escucha en el puerto **25** en todas las interfaces (IPv4 e IPv6). Solo lo contiene `ufw`. | **HECHO:** `ss -tln` (§3.2). **HECHO** (simulación de Cloudflare): el proceso es `master`, el demonio de **Postfix**, escuchando en `0.0.0.0:25` y `[::]:25`. **HECHO** (simulación de Postfix, 2026-10-28): `inet_interfaces = all`; `mynetworks` solo loopback y `smtpd_relay_restrictions` incluye `defer_unauth_destination`, por lo que **no es un relé abierto** (exposición, no abuso). Cola vacía, sin clientes conectados, sin `MAILTO`/`sendmail` en cron, sin correo configurado en `unattended-upgrades` ni Fail2Ban, y los contenedores en ejecución tienen `MAIL_HOST` y `MAIL_PORT` vacíos (el backend usa `MAIL_MAILER=log`). **INFERENCIA:** nada depende de recibir correo por red; el cambio a `loopback-only` es de bajo riesgo. **Remediado el 2026-10-28 (autorizado por José):** `inet_interfaces = loopback-only`, servicio activo tras el reinicio, y el socket IPv6 pasó de `[::]:25` a `[::1]:25`; respaldo de `main.cf` en el servidor. **HECHO:** `ss -tln` sin enmascarar confirma `127.0.0.1:25` y `[::1]:25`. Al reiniciar, Postfix avisó de que cuatro bibliotecas `libnss*` de su jaula `chroot` difieren de las del sistema (**INFERENCIA:** efecto de paquetes actualizados sin reinicio; debería desaparecer tras el reinicio del servidor de H-40). | CIS Ubuntu 3.x (servicios innecesarios) | T-03 |
| H-44 | Medio | El remoto `origin` del clon local guarda un **token OAuth de GitHub en texto plano** dentro de la URL (en `.git/config`), con alcances `repo`, `admin:public_key`, `gist` y `read:org`. Quedó impreso completo en la conversación de esta auditoría. | **HECHO:** al listar los remotos del repo apareció la URL con la credencial incrustada; la cabecera `x-oauth-client-id` del token coincide con la de la app OAuth «GitHub CLI» (**INFERENCIA** por ser el identificador público de `gh`). Alcances leídos de la cabecera `x-oauth-scopes`. **INFERENCIA:** con `repo` permite leer y escribir en todos tus repositorios y con `admin:public_key` añadir una llave SSH a tu cuenta (acceso persistente). **HIPÓTESIS:** no sé si el token salió de este equipo; solo consta que está en un archivo local y en el historial de la conversación. | OWASP ASVS V2.10 (credenciales de servicio); ISO 27001 A.5.17 | T-01 |

---

## 7. Cumplimiento normativo (según las citas del expediente)

> Las normas se citan **como las cita el módulo 01** (no verifiqué su texto vigente; L-3 y §0). «Estado» resume lo que muestra la matriz de §5. Validación jurídica: Oficina Jurídica / Oficial de Protección de Datos.

| Norma (cita del expediente) | Qué exige, según el módulo | Estado | Por qué | Tareas |
|---|---|---|---|---|
| **Decreto 2106/2019, art. 14** | La sede integra todos los portales, plataformas y ventanillas de la entidad | ❔ | Sin inventario de micrositios; pregunta abierta del §9 | T-31 |
| **Decreto 2106/2019, art. 15 (par. 2)** + **Decreto 1078/2015, art. 2.2.17.1.2** | Integrar la sede a GOV.CO (7 pasos); ventanillas únicas en 6 meses (plazo vencido en may/2020) | ❌ | Sin evidencia de solicitud a MinTIC ni de enmascaramiento; plazo de ventanillas vencido sin plan correctivo documentado | T-31 |
| **Ley 1437/2011, art. 60** | Toda autoridad pública con al menos una sede electrónica | 🟡 | Sede en staging; sin evidencia de producción | T-30 |
| **Directiva Presidencial 03/2019** · **Res. 2893/2020** | Kit UI obligatorio; solo el footer con color institucional; sin publicidad | 🟡 | CAG 33/34 con evidencia; faltan toast y evidencia de estados (H-28) | T-28 |
| **Res. 1519/2020, Anexo 2 (2.2.1-2.2.2)** | Teléfonos con +57 e indicativo | ✅ | Verificado en el pie | — |
| **Ley 1581/2012** | Política de tratamiento de datos; consentimiento informado y temporal | ❌ | Política vacía (H-21); consentimiento versionado pero sin registro servidor (H-25) | T-20, T-26 |
| **Ley 1712/2014** | Transparencia; castellano con traducciones admisibles; formatos abiertos | 🟡 | Castellano ✅; políticas sin descarga en formato abierto (H-21); transparencia pertenece al módulo 02 | T-20 |
| **Art. 10 Constitución Política** | Castellano oficial; lenguas étnicas como complemento | ✅ / ⏸ | Castellano ✅; lenguas étnicas diferidas (Decisión #16) | — |

**Lo que no pude auditar y la ley probablemente exige** (no está en el módulo 01, lo anoto para no darte una falsa sensación de completitud): registro de bases de datos ante la SIC (RNBD) si aplica; aviso de privacidad en los formularios; términos de accesibilidad WCAG 2.1 AA verificados con una herramienta (el módulo 07 los trata); y las certificaciones o pruebas de seguridad que MinTIC pida en el paso 6 de la integración. Se verifican en sus módulos.

---

## 8. Plan de remediación con criterios de aceptación

### 8.0 Cómo funciona este plan

**Principios**
1. **Contener → asegurar → cumplir → integrar.** El orden sale del daño posible, no de lo que luce más: un repo con inventario público y ramas sin CI obligatorio pesa más que un autocompletado que falta.
2. **Cada tarea trae su prueba de cierre.** Una tarea no está «hecha» porque el código se escribió, sino cuando sus **criterios de aceptación (AC)** se ejecutan y dan el resultado esperado. La salida de los comandos se adjunta al PR.
3. **Rojo antes de verde.** Para toda corrección de defecto (T-05, T-07, T-12…), la prueba nueva debe **fallar** contra el código actual y pasar después. Es lo que habría atrapado H-07/H-08.
4. **Compuertas de aprobación (G0-G4).** No se pasa de fase sin tu aprobación explícita. Este documento **no ejecuta nada**.
5. **Lo que no es técnico se dice como tal.** Las políticas (T-20) y la integración (T-31) dependen de Jurídica, de la Alta Dirección y de MinTIC; el código solo los habilita.

**Definición de Hecho (DoD) global — aplica a toda tarea de código**
- [ ] Los AC de la tarea se ejecutaron y el resultado coincide con el esperado (salida adjunta al PR).
- [ ] CI verde en el PR (jobs `Contrato`, `Backend`, `Panel`, `Sitio`, `Pila`, `Dependencias`, `Credenciales`).
- [ ] Prueba automatizada que guarda la corrección (nombre indicado en cada tarea).
- [ ] `make trazabilidad` regenerada y sin diferencias pendientes.
- [ ] Sin secretos, IP ni credenciales en el diff (`gitleaks` en el PR).
- [ ] Reversión descrita (cómo se deshace si falla en staging).

**Notación.** `AC-05.2` = tercer criterio de la tarea T-05. Cada AC trae **Verificación** (comando o prueba) y **Esperado**. Las **estimaciones de esfuerzo son mías, sin medición y con ±50 %**, en días-persona de un desarrollador; sirven para ordenar, no para comprometer fechas.

**Compuertas**

| Compuerta | Se cruza cuando | Aprueba |
|---|---|---|
| **G0** | Revisaste este documento y eliges decisiones D-1..D-4 (§8.6) | José |
| **G1** | Fase 0 cerrada (AC verificados) | José |
| **G2** | Fase 1 cerrada | José |
| **G3** | Fase 2 cerrada y textos de políticas aprobados por Jurídica | José + Jurídica |
| **G4** | Fase 3 cerrada y solicitud a MinTIC radicada | José + Dirección |

---

### Fase 0 — Contención (antes de tocar funcionalidad) · cruzar G0 para empezar · cierre = G1

#### T-00 · Proteger este archivo de un commit accidental *(opcional, 5 min)*
- **Resuelve:** aviso de §0. **Esfuerzo:** 0,1 d. **Riesgo:** ninguno (archivo local no versionado). **Depende de:** tu aprobación.
- **Qué:** añadir la ruta de este archivo a `.git/info/exclude` (no toca `.gitignore` ni el repo).
- **Criterios de aceptación**
  - [ ] **AC-00.1** — Verificación: `git check-ignore -v sede-electronica-doc/01-estructura-identidad/auditoria-estructura-identidad.md` → Esperado: imprime la regla de `.git/info/exclude`.
  - [ ] **AC-00.2** — Verificación: `git status --short sede-electronica-doc` → Esperado: no lista este archivo.

#### T-01 · Sacar lo operativo del repositorio público
- **Resuelve:** H-01, H-18, H-44 y parte de H-05. **Esfuerzo:** 0,5 d + rotaciones. **Riesgo:** reescribir historial rompe clones y PRs abiertos; **no lo recomiendo** si todo lo expuesto ya está rotado. **Depende de:** decisión D-1.
- **Qué:**
  1. Decidir (D-1) **repo privado** (recomendado, 1 clic) o público sin `accesos.md`.
  2. Mover el inventario operativo a un lugar no público (gestor de secretos/documento interno) y quitar `accesos.md` de `main`, `staging` y `develop`.
  3. Pasar un escáner de secretos por **todo el historial** y clasificar cada hallazgo (ejemplo / rotado / vigente). Rotar lo vigente.
  4. Retirar de la raíz los informes de agentes y los scripts de depuración (a `docs/archivo/` o fuera del repo, según decidas).
  5. Revocar el token OAuth de `gh` que estaba en la URL del remoto: *GitHub → Settings → Applications → Authorized OAuth Apps → GitHub CLI → Revoke*; revisar *Settings → SSH and GPG keys* por llaves que no reconozcas; cambiar el remoto a SSH; volver a iniciar sesión con `gh auth login` si lo necesitas.
- **Criterios de aceptación**
  - [x] **AC-01.1** — Verificación: `curl -s -o /dev/null -w '%{http_code}\n' https://api.github.com/repos/SantanderAcuna/sede` · Esperado: `404` (repo privado). *Alternativa si D-1 = público:* `git ls-files | grep -ci '^accesos'` → `0` en cada una de `main`, `staging`, `develop`. *— ✅ 2026-10-29, por la vía alternativa (D-1 = público): `git ls-tree -r --name-only origin/<rama> | grep -ci '^accesos'` → 0 en `main`, `staging` y `develop`. Sigue en el historial y la rotación (AC-01.3) es obligatoria, no opcional. La vía principal (404 de la API) no aplica: el repo es público a propósito.*
  - [ ] **AC-01.2** — Verificación: `gitleaks detect --source . --log-opts="--all" --redact` (en versiones nuevas: `gitleaks git`) · Esperado: tabla de hallazgos con **0 clasificados como «vigente»**; cada uno con fecha y decisión. *— ⚠️ **Parcial, 2026-10-29.** `gitleaks` 8.18.4 sobre los 300 commits de todas las ramas: 20 hallazgos en 11 líneas, **todos falsos positivos revisados a mano** (contraseñas de prueba en 3 archivos de `backend/tests/`, un `mfa_token` truncado de ejemplo en un `.md`, y una expresión regular de clave SSH citada en 2 documentos); quedaron en `.gitleaks.toml` con su motivo. **Límite:** las reglas buscan cadenas con forma de secreto; `accesos.md` estuvo en el historial del 2026-09-30 al 2026-10-28 (229 → 340 líneas, con 4-6 direcciones IPv4 y una sección «Incidente del 2026-09-30: credenciales publicadas») y no dispara ninguna regla. Que ninguna sea «vigente» no lo puede decir un escáner: depende de la rotación (AC-01.3), que es tuya.*
  - [ ] **AC-01.3** — Verificación: lista de secretos (solo nombres) con propietario y fecha de última rotación, guardada fuera del repo · Esperado: todos los secretos que el inventario público mencionaba figuran con fecha posterior a la decisión D-1.
  - [x] **AC-01.4** — Verificación: `git ls-files 'panel/test_*.mjs' | wc -l` · Esperado: `0`. *— ✅ 2026-10-29, PR #314: los 6 scripts pasaron a `docs/archivo/panel-depuracion/` (`git ls-files 'panel/test_*.mjs' | wc -l` → 0). Se actualizaron `panel/eslint.config.js` y el comentario de `AuthTest.php` que los citaban.*
  - [x] **AC-01.5** — Verificación: `git ls-files '*.md' | grep -v / ` · Esperado: solo los `.md` de raíz de una lista que apruebas (propuesta: `README.md`, `PRODUCT.md`, `DESIGN.md`). *— ✅ 2026-10-29, PR #314: la raíz solo conserva `README.md`, `PRODUCT.md` y `DESIGN.md`; los otros 15 `.md` están en `docs/archivo/` (decisión tuya: archivar, no borrar). Se corrigieron los enlaces de `README.md`, `DESIGN.md`, `Makefile`, `deploy/README.md` y `.dockerignore`.*
  - [ ] **AC-01.6** — Verificación: `git remote -v | grep -c -E '://[^/ ]*:[^@ ]*@'` y *Settings → Applications → Authorized OAuth Apps* · Esperado: `0` URL con credencial incrustada; el token anterior revocado (una llamada a la API con él devuelve `401`) y ninguna llave SSH desconocida en la cuenta.
- **Prueba que lo guarda:** job `Credenciales` corregido (T-06). **Reversión:** el historial no se reescribe; si algo faltó, se restaura desde el commit anterior.

#### T-02 · Atar la llave de despliegue a un único comando y quitarla del grupo `docker`
- **Resuelve:** H-03, H-42. **Esfuerzo:** 0,75 d. **Riesgo:** **medio** — puede romper el despliegue; probar primero en staging y mantener otra sesión de administración abierta. **Depende de:** acceso de administración al servidor (tuyo).
- **Qué:** (a) instalar en `authorized_keys` de la cuenta de despliegue `command="/usr/local/bin/desplegar.sh",restrict`; (b) **evaluar** quitar la cuenta del grupo `docker`. **Ojo, corrección mía:** había escrito que «el script ya corre con `sudo`»; el `despliegue-remoto.sh` del repo invoca `docker compose` **directamente y no contiene `sudo`**, y la llave lo ejecuta como `deploy`, así que hoy es **esa pertenencia al grupo lo que permite desplegar**. Quitarla sin más **rompería el despliegue**. La salida segura exige antes cambiar el comando forzado a `sudo /usr/local/bin/desplegar.sh` (conservando `SSH_ORIGINAL_COMMAND` con `Defaults:deploy env_keep`), probar en staging y solo entonces retirar el grupo; (c) hacer que `deploy/droplet/preparar.sh` lo haga idempotente, para que deje de ser un paso manual invisible.
- **Criterios de aceptación**
  - [x] **AC-02.1** — Verificación (servidor): `sudo sed -E 's/(ssh-[a-z0-9]+ )[A-Za-z0-9+\/=]+/\1[clave]/' ~<USUARIO_DESPLIEGUE>/.ssh/authorized_keys` · Esperado: cada línea empieza con `command="/usr/local/bin/desplegar.sh"` y contiene `restrict`. *— ✅ Verificado 2026-10-28: `command="sudo -n …",restrict` en la llave de `deploy` (modo `restringir`).*
  - [x] **AC-02.2** — Verificación (servidor): `id -nG <USUARIO_DESPLIEGUE> | tr ' ' '\n' | grep -cx docker` · Esperado: `0`, **solo después de** que AC-02.4 pase con el comando forzado ya migrado a `sudo`. *— ✅ Verificado 2026-10-28: `id -nG deploy` = `deploy sede-ssh`.*
  - [x] **AC-02.3** — Verificación: desde otra máquina, `ssh <USUARIO_DESPLIEGUE>@<servidor> id` con la llave de CI · Esperado: **no** devuelve `uid=…` (ejecuta solo el comando forzado o lo rechaza). *— ✅ Verificado 2026-10-28: prueba por SSH local con llave temporal: `Entorno no válido: 'id'`, sin `uid=` (no se usó la llave de CI).*
  - [x] **AC-02.4** — Verificación: relanzar el workflow `despliegue.yml` contra **staging** · Esperado: ejecución verde y versión desplegada = la pedida (`curl -s https://staging…/health` y la etiqueta de la imagen). *— ✅ Verificado 2026-10-28: tres despliegues de staging en verde tras el cambio (el último, sin el grupo `docker` y con `restrict`).*
  - [x] **AC-02.7** — Verificación: `sha256sum /usr/local/bin/desplegar.sh deploy/droplet/despliegue-remoto.sh` · Esperado: hashes iguales (o diferencia documentada en un ADR), y `preparar.sh` instala el archivo del repo, no una copia manual. *— ✅ Verificado 2026-10-28: hash instalado = repo (`d356b1c0…`) y `preparar.sh` lo instala desde el repo.*
  - [ ] **AC-02.6** — Verificación (servidor): `ls -ld /opt/sede /opt/sede/desplegar.sh /usr/local/bin/desplegar.sh` · Esperado: los tres de `root`, sin escritura para grupo ni otros (o `/opt/sede/desplegar.sh` inexistente **y** retirado de `sudoers`).
  - [x] **AC-02.5** — Verificación: `grep -n 'command=' deploy/droplet/preparar.sh` · Esperado: ≥ 1 coincidencia (ya no solo en un comentario); `shellcheck deploy/droplet/*.sh` sin errores. *— ✅ Verificado 2026-10-28: `grep -n 'command=' deploy/droplet/preparar.sh` ≥ 1 (#275, #280); `shellcheck` no ejecutado.*
- **Reversión:** devolver la cuenta al grupo y restaurar la copia de `authorized_keys` hecha antes del cambio.

#### T-03 · Verificar firewall y entornos de GitHub *(solo verificación)*
- **Resuelve:** H-04, H-40, H-41, H-43. **Esfuerzo:** 0,5 d. **Riesgo:** ninguno. **Depende de:** acceso de administrador (tuyo) y de la contraseña de `sudo` de `ops`.
- **Qué:** ejecutar los comandos de §9.1 y §9.2 y archivar las salidas (sin IP) como evidencia.
- **Criterios de aceptación**
  - [ ] **AC-03.1** — Verificación: §9.1 (todas las filas) · Esperado: coincide con la columna «Esperado» o queda una tarea abierta por cada desviación.
  - [ ] **AC-03.2** — Verificación: §9.2 (todas las filas) · Esperado: ídem.
  - [ ] **AC-03.3** — Verificación: archivo de evidencia fechado, guardado fuera del repo público · Esperado: existe y no contiene IP ni secretos.
  - [ ] **AC-03.5** — Verificación: `apt list --upgradable 2>/dev/null | grep -ci security` y `[ -f /var/run/reboot-required ] && echo reinicio-pendiente` · Esperado: `0` paquetes de seguridad pendientes y sin reinicio pendiente, con el reinicio hecho en una ventana fechada y aprobada (staging queda sin servicio unos minutos). Decisión asociada: si se mantiene la lista negra de `linux-*`, documentar un procedimiento mensual manual.
  - [x] **AC-03.6** — Verificación: `sudo ss -tlnp | grep ':25 '` · Esperado: solo `127.0.0.1:25` y `[::1]:25`, o nada (hoy: escucha en todas las interfaces). El proceso es Postfix: fijar `inet_interfaces = loopback-only` (requiere `systemctl restart postfix`; un `reload` no cambia las interfaces) o desinstalarlo si nada lo usa; comprobar antes que ningún servicio dependa de recibir correo por red. *— ✅ Verificado 2026-10-28: `ss -tln` mostró solo `127.0.0.1:25` y `[::1]:25`.*
  - [ ] **AC-03.7** — Verificación: `sudo ufw status` y *Networking → Firewalls* de DigitalOcean · Esperado: 80/443 solo desde los rangos de Cloudflare (o *Authenticated Origin Pulls* activo) y 22 limitado a las IP de administración. *Evidencia 2026-10-28:* 80/443 restringidos a Cloudflare en `ufw` y `DOCKER-USER` y acceso directo al origen sin respuesta (parcial: faltan el puerto 22 y el panel de DigitalOcean).
  - [x] **AC-03.4** — Verificación: `sudo grep -c . /root/.ssh/authorized_keys` · Esperado: `0` líneas con llaves de personas (o el archivo vacío/ausente), de modo que el cierre de `root` por SSH no dependa de una sola directiva. *— ✅ Verificado 2026-10-28: `authorized_keys` de `root` vaciado (respaldo en el servidor).*

#### T-04 · Reglas de rama que realmente protejan
- **Resuelve:** H-02, H-39. **Esfuerzo:** 0,5 d. **Riesgo:** bajo; con un solo desarrollador, **exigir aprobación humana bloquea el trabajo**, así que ofrezco dos opciones (D-2). **Depende de:** D-2; T-09 (para que el CI esté verde antes de volverlo obligatorio).
- **Qué:** añadir la regla `required_status_checks` con los jobs del CI (`Contrato`, `Backend`, `Panel`, `Sitio`, `Pila`, `Dependencias`, `Credenciales`), exigiendo rama actualizada; sin *bypass actors*. **Opción A (un desarrollador):** 0 aprobaciones, checks obligatorios, y revisor requerido en el entorno `production`. **Opción B (≥2 personas):** además ≥ 1 aprobación, descartar aprobaciones obsoletas y código-owner en `.github/workflows/`, `docker/` y `deploy/`.
- **Criterios de aceptación**
  - [ ] **AC-04.1** — Verificación: `gh api repos/SantanderAcuna/sede/rules/branches/main --jq '[.[].type]'` (y `staging`, `develop`) · Esperado: incluye `required_status_checks`.
  - [ ] **AC-04.2** — Verificación: `gh api repos/SantanderAcuna/sede/rules/branches/main --jq '.[]|select(.type=="required_status_checks")|.parameters.required_status_checks|map(.context)'` · Esperado: lista los 7 jobs.
  - [ ] **AC-04.3** — Verificación: abrir un PR de prueba con una prueba rota · Esperado: el botón de fusionar queda bloqueado con «Required status check … expected».
  - [ ] **AC-04.4** — *(solo Opción B)* Verificación: `…|select(.type=="pull_request")|.parameters.required_approving_review_count` · Esperado: `≥ 1`.
  - [ ] **AC-04.5** — Verificación: `gh api repos/SantanderAcuna/sede/environments/production --jq '.protection_rules'` · Esperado: incluye una regla `required_reviewers` con ≥ 1 revisor (que no sea quien despliega, si hay ≥ 2 personas).
  - [ ] **AC-04.6** — Verificación: `gh api repos/SantanderAcuna/sede/actions/secrets --jq '.secrets[].name'` · Esperado: ya no incluye `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT` ni `DEPLOY_KNOWN_HOSTS`; existen en `staging` y `production` por separado, con llave distinta por entorno si el servidor lo permite.

- **Aviso sobre `[skip ci]` (hallazgo del 2026-10-29, HECHO + INFERENCIA):** hoy las sincronizaciones hacia `staging` y `main` se fusionan con `[skip ci]` para **no disparar el workflow `Despliegue`** (se activa con `push` a `main` y `staging`). Un commit con `[skip ci]` no genera ningún check; con `required_status_checks` el PR quedaría en «Expected — Waiting for status to be reported» y **no se podría fusionar**. Quitar el `[skip ci]` desbloquea el PR, pero entonces cada sincronización **despliega** (a `main`, a producción). Hay tres salidas: (a) exigir los checks **solo en `develop`**, donde todo entra por PR con CI, y dejar `staging`/`main` con PR obligatorio hasta decidir producción; (b) exigirlos en las tres y aceptar que sincronizar `main` despliega, lo que antes necesita el revisor requerido de AC-04.5; (c) separar el despliegue de `main` en un workflow manual. **Recomiendo (a) ahora.** *Decisión de José, 2026-10-29: opción (a); pendiente de que la ejecute él (mi sesión recibe 403 del proxy al crear el ruleset).* Mi sesión no puede crear rulesets (el proxy bloquea los ajustes del repositorio): el comando para (a), que ejecutas tú con el repo en su estado actual, es
  ```bash
  gh api -X POST repos/SantanderAcuna/sede/rulesets --input - <<'JSON'
  {"name":"Checks obligatorios en develop","target":"branch","enforcement":"active","bypass_actors":[],
   "conditions":{"ref_name":{"include":["refs/heads/develop"],"exclude":[]}},
   "rules":[{"type":"required_status_checks","parameters":{"strict_required_status_checks_policy":true,
     "required_status_checks":[{"context":"Contrato"},{"context":"Backend"},{"context":"Panel"},
     {"context":"Sitio"},{"context":"Pila"},{"context":"Dependencias"},{"context":"Credenciales"}]}}]}
  JSON
  ```
  *Antes de ejecutarlo, comprueba que los siete nombres coinciden con los de la pestaña Checks de un PR reciente; un nombre que no existe bloquea todos los PR.* Con `strict_required_status_checks_policy` los PR deben estar al día con `develop` (mi script ya hace `update-branch`). El plan Free de un repo privado ignoraría este ruleset (ver T-01).

#### T-06 · Que el CI de credenciales no sea ciego a los `.md` ni al historial
- **Resuelve:** H-05. **Esfuerzo:** 0,25 d. **Riesgo:** bajo (puede aparecer ruido en documentos que citan patrones; se resuelve con una lista de permitidos explícita y revisada). **Depende de:** T-01.
- **Criterios de aceptación**
  - [x] **AC-06.1** — Verificación: PR de prueba que añade a un `.md` una cadena con forma de secreto (inventada) · Esperado: el job `Credenciales` **falla**. *— ✅ 2026-10-29: PR de prueba #315 (cadena inventada con forma de token de GitHub en `docs/prueba-credencial.md`): el job `Credenciales` **falló** con `RuleID: github-pat`; los otros 6 pasaron. PR cerrado sin fusionar.*
  - [x] **AC-06.2** — Verificación: el mismo PR sin la cadena · Esperado: el job pasa. *— ✅ 2026-10-29: el PR #314 (sin la cadena) pasó los 7 jobs, incluido `Credenciales`.*
  - [x] **AC-06.3** — Verificación: revisar `ci.yml`, job `Credenciales` · Esperado: la búsqueda de claves privadas ya no excluye `':!*.md'`; existe un escaneo **por contenido** (gitleaks o equivalente) sobre árbol e historial; el paso de historial **falla** ante hallazgos no clasificados (decisión consciente frente al comentario actual del paso, que prefiere advertir: se acepta una lista de excepciones revisada en lugar de silenciar el paso). *— ✅ 2026-10-29, PR #314: `ci.yml` ya no excluye `*.md`; el job compila `gitleaks` v8.18.4 desde el módulo de Go (resumen verificado por sum.golang.org) y escanea todos los commits de todas las ramas con `.gitleaks.toml` (reglas por defecto + 6 excepciones por archivo, revisadas); falla ante cualquier hallazgo no clasificado. Se retiró la búsqueda `git grep` de claves privadas (dejaba fuera los `.md`) y el paso del historial que solo avisaba.*

---

### Fase 1 — Seguridad funcional (backend, panel, nginx) · cierre = G2

#### T-05 · Ruta `/sanctum/` en nginx y CSRF explícito en el panel
- **Resuelve:** H-06. **Esfuerzo:** 0,5 d. **Riesgo:** bajo. **Depende de:** G1.
- **Qué:** añadir `location /sanctum/` (mismo destino que `/api/v1`) en `docker/nginx/conf.d/default.conf`; en `panel/src/services/auth.ts` comprobar la respuesta de `csrf()` y lanzar un error claro si no es 2xx; dejar de depender de que `/panel/perfil` plante la cookie.
- **Criterios de aceptación**
  - [x] **AC-05.1** — Verificación: `curl -si https://staging.santamarta.gov.co/sanctum/csrf-cookie -H 'Origin: https://staging.santamarta.gov.co' -H 'Referer: https://staging.santamarta.gov.co/admin/acceso'` · Esperado: `HTTP/2 204` con `Set-Cookie: XSRF-TOKEN=`. *— ✅ 2026-10-28, staging tras el despliegue de PR #302: `HTTP/2 204` con `Set-Cookie: XSRF-TOKEN` (y cookie de sesión HttpOnly).*
  - [x] **AC-05.2** — Verificación: con un *cookie jar* vacío: (1) el `GET` anterior; (2) `POST /api/v1/panel/login` con `X-XSRF-TOKEN` y credenciales **ficticias** · Esperado: `401` («credenciales no válidas»), **nunca `419`**. *— ✅ 2026-10-28, staging: con cookie jar, `POST /api/v1/panel/login` con credenciales ficticias → `401` «Las credenciales no son válidas.», nunca 419.*
  - [x] **AC-05.3** — Verificación: `POST /api/v1/panel/login` **sin** cookie ni cabecera XSRF · Esperado: sigue siendo `419` (el CSRF no se debilitó). *— ✅ 2026-10-28, staging: sin cookie ni cabecera XSRF → `419` «CSRF token mismatch.» (el CSRF no se debilitó).*
  - [x] **AC-05.4** — Verificación: nueva prueba en `panel/tests/auth.test.ts`: `csrf()` con respuesta 404/500 · Esperado: lanza error con mensaje claro y **no** intenta el login. `npm test` del panel: 100 % verde. *— ✅ 2026-10-28, PR #296: 2 pruebas nuevas en `auth.test.ts` (404 y 500 → error «cookie CSRF» y `http.post` no se llama); panel 261/261.*
  - [ ] **AC-05.5** — Verificación: navegador sin cookies, abrir `/admin/acceso` y entrar con un **usuario de prueba sin privilegios creado para esto** · Esperado: acceso correcto, sin pasar por `/panel/perfil` antes. *— 🟡 en un navegador sin cookies: `GET /sanctum/csrf-cookie` 204 → `POST /panel/login` 401, mensaje «Las credenciales no son válidas.» y sin redirección (camino negativo). **Falta el camino de éxito**: requiere el usuario de prueba sin privilegios, que hay que crear en el servidor.* *Preparado 2026-10-29: `deploy/droplet/usuario-prueba.sh crear` (en el servidor de staging, con una cuenta de administración que use Docker) crea `prueba.auditoria@example.com` sin roles ni permisos, con contraseña aleatoria que se imprime una vez; `borrar` lo retira. **No probado contra el servidor** (mi sesión no llega a él; solo `shellcheck` y comprobación de sintaxis PHP).*
- **Prueba que lo guarda:** `auth.test.ts` (caso nuevo) + un chequeo en el job `pila` que haga `curl` a `/sanctum/csrf-cookie`. **Reversión:** quitar la `location`.

#### T-07 · Auditoría: exigir el permiso y devolver los datos
- **Resuelve:** H-07, H-08. **Esfuerzo:** 0,5 d. **Riesgo:** bajo. **Depende de:** G1.
- **Qué:** añadir `can:auditoria.ver` (o el middleware de permisos del proyecto) a `GET /api/v1/panel/auditoria`; reemplazar la línea `AuditLogResource::collection($query->limit(0)->get())` (`AuditoriaController.php:60`) por la colección de la página ya paginada.
- **Criterios de aceptación**
  - [x] **AC-07.1** — Verificación: prueba nueva «usuario sin permisos → 403» · Esperado: pasa; y **falla** contra el código actual (hoy da 200). *— ✅ 2026-10-28, PR #294: `AuditoriaTest` (4 pruebas nuevas), CI Backend verde. No se ejecutaron contra el código anterior, así que el «falla hoy» no está demostrado.*
  - [x] **AC-07.2** — Verificación: prueba nueva «con 3 registros, `count(data) == 3` y `meta.total == 3`» · Esperado: pasa; y **falla** contra el código actual (hoy `count(data) == 0`). *— ✅ 2026-10-28, PR #294: `AuditoriaTest` (4 pruebas nuevas), CI Backend verde. No se ejecutaron contra el código anterior, así que el «falla hoy» no está demostrado.*
  - [x] **AC-07.3** — Verificación: prueba nueva «`por_pagina=2` con 3 registros → `count(data) == 2`, `meta.last_page == 2`» · Esperado: pasa. *— ✅ 2026-10-28, PR #294: `AuditoriaTest` (4 pruebas nuevas), CI Backend verde. No se ejecutaron contra el código anterior, así que el «falla hoy» no está demostrado.*
  - [x] **AC-07.4** — Verificación: prueba «usuario con `auditoria.ver` → 200» · Esperado: pasa. *— ✅ 2026-10-28, PR #294: `AuditoriaTest` (4 pruebas nuevas), CI Backend verde. No se ejecutaron contra el código anterior, así que el «falla hoy» no está demostrado.*
  - [ ] **AC-07.5** — Verificación: `php artisan route:list --path=panel/auditoria -v` · Esperado: el middleware de permiso aparece listado. *— ⚠️ 2026-10-29: `route:list` no se puede ejecutar en mi sandbox (PHP 8.3; el backend exige 8.5). Verificado por código: `backend/routes/api.php:53-56` declara `GET /auditoria` con `auth:sanctum` y `can:auditoria.ver`, y `AuditoriaTest` (en verde en CI) cubre el rechazo sin permiso. Para marcarlo literal: `php artisan route:list --path=panel/auditoria -v` en tu máquina.*
  - [x] **AC-07.6** — Verificación: `vendor/bin/phpunit` completo · Esperado: ≥ 244 pruebas (240 + 4 nuevas), 0 fallos. *— ✅ 2026-10-28: CI Backend con 265 pruebas, 0 fallos (tras T-07 a T-13).*
- **Prueba que lo guarda:** las 4 nuevas en `tests/Feature/Api/V1/AuditoriaTest.php`. **Reversión:** revertir el commit.

#### T-08 · Content-Security-Policy en sitio y panel (primero en modo informe)
- **Resuelve:** H-09. **Esfuerzo:** 1,5 d + 1 semana de observación. **Riesgo:** **medio** — una CSP mal ajustada rompe el sitio; por eso se entra en `Report-Only`. Nuxt SSR inyecta un script de estado en línea (requiere nonce o hash) y Cloudflare puede inyectar su script (se cruza con T-26). **Depende de:** T-26 (decide si el *beacon* de Cloudflare se queda).
- **Criterios de aceptación**
  - [x] **AC-08.1** — Verificación: `curl -sI https://staging.santamarta.gov.co/ | grep -i content-security-policy-report-only` · Esperado: presente (fase informe). *— ✅ 2026-10-28, staging: `content-security-policy-report-only` presente en `/`, `/admin/acceso` y `/tramites`, junto con HSTS y `nosniff`.*
  - [ ] **AC-08.2** — Verificación: tras 7 días con tráfico real de pruebas, revisar los informes · Esperado: 0 violaciones atribuibles a recursos propios legítimos (o se ajusta la política y se reinicia el conteo).
  - [ ] **AC-08.3** — Verificación: `curl -sI …/ | grep -i '^content-security-policy:'` (ya aplicada) · Esperado: contiene `default-src 'self'`, `object-src 'none'`, `base-uri 'self'`, `frame-ancestors 'none'`, **sin** `unsafe-eval`; todo `unsafe-inline` en `script-src` está justificado por escrito (se prefiere nonce/hash).
  - [ ] **AC-08.4** — Verificación: Playwright, recorrido de home, `/tramites`, `/buscar`, `/admin/acceso` · Esperado: 0 mensajes de consola «Refused to …» y 0 fallos de carga. *— 🟡 medido 2026-10-28 con Playwright contra staging (`/`, `/tramites`, `/buscar`, `/admin/acceso`): **0 fallos de carga**; violaciones de la política en modo informe: sitio 4 (importmap y `__NUXT__.config` de Nuxt, script en línea de Cloudflare, `beacon.min.js`), panel 2 (solo las dos de Cloudflare). No es «0», pero ninguna es un recurso propio roto: ver H-09.*
  - [ ] **AC-08.5** — Verificación: igual CSP en el panel (`/admin/…`) · Esperado: ídem AC-08.3.

#### T-09 · Dependencias: CI de dependencias en verde
- **Resuelve:** H-11. **Esfuerzo:** 0,5-1 d. **Riesgo:** medio-bajo (salto menor de `nuxt` a ≥ 4.6.2; correr `tests/diseno.mjs`). **Depende de:** G1.
- **Criterios de aceptación**
  - [x] **AC-09.1** — Verificación: `cd sitio && npm audit --omit=dev --audit-level=high` · Esperado: exit `0`. *— ✅ Verificado 2026-10-28: `npm audit --omit=dev --audit-level=high` en `sitio/`: exit 0.*
  - [x] **AC-09.2** — Verificación: `cd panel && npm audit --audit-level=high` · Esperado: exit `0`, o excepciones listadas con fecha de caducidad y motivo. *— ✅ Verificado 2026-10-28: `npm audit --audit-level=high` en `panel/`: exit 0.*
  - [x] **AC-09.3** — Verificación: `cd backend && composer audit --locked` · Esperado: «No security vulnerability advisories found» (requiere `symfony/mime` ≥ 8.1.10 y `phpunit` ≥ 13.4.4). *— ✅ Verificado 2026-10-28: `composer audit --locked`: «No security vulnerability advisories found».*
  - [x] **AC-09.4** — Verificación: `npm ls nuxt --omit=dev` · Esperado: `nuxt@≥4.6.2`. *— ✅ Verificado 2026-10-28: `nuxt@4.6.2`.*
  - [x] **AC-09.5** — Verificación: CI de `main`, `staging` y `develop` · Esperado: job «Dependencias» verde en las tres. *— ✅ Verificado 2026-10-28: job «Dependencias» en verde en `develop` (6dcbcde) y `staging` (70eee20); `main` tiene el mismo árbol y su PR #283 lo pasó en verde (sus commits recientes llevan `[skip ci]`).*
  - [x] **AC-09.6** — Verificación: `npm run test && npm run build` en `sitio/` y `panel/` · Esperado: verde; `tests/diseno.mjs` sin regresiones. *— ✅ Verificado 2026-10-28: jobs «Sitio» y «Panel» del CI en verde (panel: 253 pruebas).*
  - [ ] **AC-09.7** — Verificación: Trivy sobre la imagen del sitio · Esperado: 0 hallazgos HIGH/CRITICAL con corrección disponible.

#### T-10 · MFA: aplicarlo o retirarlo *(decisión D-3)*
- **Resuelve:** H-10. **Esfuerzo:** 3-5 d (aplicarlo) / 0,5 d (retirarlo y ocultarlo). **Riesgo:** medio (bloqueo de cuentas si el flujo de recuperación falla). **Depende de:** D-3.
- **Criterios de aceptación** *(si se aplica)*
  - [ ] **AC-10.1** — Verificación: prueba «usuario con `mfa_habilitado=true` → login devuelve `require_mfa: true` y **no** crea sesión autenticada» · Esperado: pasa (hoy falla).
  - [ ] **AC-10.2** — Verificación: `POST /api/v1/panel/mfa` con código válido / inválido / vencido · Esperado: sesión / 422 / 422, con límite de intentos (429 al exceder).
  - [ ] **AC-10.3** — Verificación: el contrato marca `/panel/mfa` como `implemented` y `npm run contrato` pasa · Esperado: verde.
  - [ ] **AC-10.4** — Verificación: política para roles con `panel-administrative` · Esperado: no pueden operar sin MFA configurado (prueba).
  - *(si se retira)* **AC-10.5** — Verificación: `mfa_habilitado=true` no se puede asignar y `MfaView` no es accesible · Esperado: pruebas que lo demuestran.

#### T-11 · ESLint en el panel
- **Resuelve:** H-12. **Esfuerzo:** 1 d. **Riesgo:** bajo.
- **Criterios de aceptación**
  - [x] **AC-11.1** — `cd panel && npm run lint` existe y sale con `0`. *— ✅ 2026-10-28, PR #297: `npm run lint` sale con 0 (0 errores, 15 advertencias).*
  - [x] **AC-11.2** — CI ejecuta el lint y falla ante un error introducido a propósito (PR de prueba). *— ✅ 2026-10-28: PR de prueba #307 (cerrado sin fusionar) con `debugger` y un `var` sin uso → el job Panel falló en el paso **Lint**.*
  - [x] **AC-11.3** — Línea base de advertencias documentada con número; no crece. *— ✅ 2026-10-28, PR #297: línea base **15 advertencias** fijada con `--max-warnings=15` en `panel/package.json`; si crece, el lint falla.*

#### T-12 · Subida de archivos coherente y segura
- **Resuelve:** H-13. **Esfuerzo:** 1,5 d. **Riesgo:** medio. **Depende de:** decidir si el almacenamiento es S3 o disco local.
- **Criterios de aceptación**
  - [x] **AC-12.1** — Verificación: prueba «`FilesMediaService` usa el disco por defecto de configuración» (cambiar `FILESYSTEM_DISK` a `local` y a `s3` con *fake*) · Esperado: el archivo termina en el disco configurado. *— ✅ 2026-10-28, PR #299: `SubidaDeArchivosTest` sube con `local` y con `s3` (simulado) y comprueba disco y archivo.*
  - [x] **AC-12.2** — Verificación: subir un SVG con `<script>` · Esperado: `422` (SVG prohibido o sanitizado; se decide y se prueba). *— ✅ 2026-10-28, PR #299: decisión **prohibir** SVG (no sanitizar); SVG con `<script>` → 422.*
  - [x] **AC-12.3** — Verificación: subir `foto.php.png` o con MIME de PDF y extensión `.png` · Esperado: la extensión guardada se deriva del contenido, no de `getClientOriginalExtension()`. *— ✅ 2026-10-28, PR #299: JPEG con nombre `.png` se guarda `.jpg`; `foto.php.png` sin `.php` en ruta; `foto.php` → 422.*
  - [x] **AC-12.4** — Verificación: validación `max`, `upload_max_filesize`, `post_max_size` y `client_max_body_size` · Esperado: los cuatro coherentes (p. ej. 20 MB) y un archivo de 21 MB recibe `422`, no `413` opaco. *— ✅ 2026-10-28, PR #299: validación 20 MB = `upload_max_filesize`; `post_max_size` y nginx 25 MB; 21 MB → 422 en PHPUnit; 413 de nginx ahora en JSON (probado con nginx real). No medido de punta a punta en staging.*
  - [ ] **AC-12.5** — Verificación: subir un archivo y luego `curl -sI <url pública devuelta>` · Esperado: `200`, `Content-Type` correcto, `X-Content-Type-Options: nosniff`. *— 🟡 pendiente de staging: la URL se firma en discos locales (probado en PHPUnit), falta el `curl -sI` real.* *Preparado 2026-10-29: `usuario-prueba.sh crear archivos` añade `archivos.ver` y `archivos.crear`. **HECHO (hallazgo nuevo):** `PermissionSeeder` no siembra ningún permiso `archivos.*`, aunque `routes/api.php:64-77` los exige con `can:`; hoy solo un super-administrador puede usar esas rutas. El script los crea si faltan; decidir si se añaden al sembrado y a qué rol (no lo cambié: es una decisión de diseño de roles).*

#### T-13 · Endurecer el login (auditar fallos, uniformar tiempos, 429 usable)
- **Resuelve:** H-14. **Esfuerzo:** 1 d. **Riesgo:** bajo.
- **Criterios de aceptación**
  - [x] **AC-13.1** — Verificación: prueba «login fallido registra en `audit_log` `login_failed` con IP y sin contraseña» · Esperado: pasa. *— ✅ 2026-10-28, PR #298: `LoginEndurecidoTest` — `login_failed` con IP, correo y motivo; la contraseña no aparece en ninguna columna.*
  - [x] **AC-13.2** — Verificación: 50 logins con correo existente y 50 con inexistente, midiendo tiempo · Esperado: diferencia de medianas < 15 ms (umbral mío; se ajusta al medir). *— ❌ **No cumplido en la primera medición (2026-10-28, staging, 30 + 30 intentos alternados, todos 401):** mediana 864 ms con correo existente y 1.133 ms con inexistente (−269 ms; umbral < 15 ms). Control A/A (dos correos inexistentes por par): 56 ms de sesgo de orden, así que ≈ 200 ms no se explican por la medición. HIPÓTESIS (sin confirmar): el hash de relleno se calculaba por proceso y FPM recicla procesos → dos hashes por petición. Corrección: constante bcrypt de coste 12 (PR #308). **Re-medición tras desplegar #308/#310 (2026-10-28, staging, mismas condiciones: 30 + 30 intentos alternados, todos 401, medidos desde una máquina con IP estable):** mediana 809,5 ms con correo existente y 804,9 ms con inexistente → diferencia **4,7 ms** (< 15 ms). Rangos intercuartílicos ≈ 47 ms (existente 790,6–837,0; inexistente 778,2–827,9), así que la diferencia está dentro del ruido y no hay un efecto detectable con esta muestra. La hipótesis del hash calculado por proceso queda **consistente con el resultado, no probada aisladamente.** Límites: muestra de 30 + 30 y no 50 + 50; mide la ruta de credenciales inválidas, no la de contraseña correcta (AC-05.5); el umbral de 15 ms es mío. Un atacante con muchas muestras podría detectar diferencias menores que el ruido de una petición; el 429 por correo (3/min) y por IP (20/min) limita ese número de muestras.***
  - [x] **AC-13.3** — Verificación: superar el límite de intentos · Esperado: `429` con `Retry-After`; el panel muestra el mensaje y **no** redirige. *— ✅ 2026-10-28, PR #298: 4.º intento → 429 con `Retry-After`; el interceptor del panel ya no redirige y `EntrarView` muestra el plazo (pruebas en `http.test.ts`).*

#### T-14 · CORS de producción y semilla del super-administrador
- **Resuelve:** H-15, H-16, H-38. **Esfuerzo:** 0,75 d. **Riesgo:** bajo-medio (CORS mal puesto bloquea el panel).
- **Criterios de aceptación**
  - [x] **AC-14.1** — Verificación: con `APP_ENV=production`, `php artisan tinker --execute="dump(config('cors.allowed_origins'));"` · Esperado: solo los orígenes reales; ni `localhost` ni `0.0.0.0`. *— ✅ 2026-10-28, PR #295: `OrigenesPermitidosTest` (5): en `production` no hay `localhost` ni `0.0.0.0`. Falta el `tinker` en staging.*
  - [x] **AC-14.2** — Verificación: prueba «ejecutar `SuperAdminSeeder` dos veces no cambia el hash de la contraseña» · Esperado: pasa. *— ✅ 2026-10-28, PR #295: `SuperAdminSeederTest` — mismo hash tras sembrar dos veces; una clave rotada no se deshace.*
  - [x] **AC-14.3** — Verificación: prueba «el seeder se niega a usar la clave de desarrollo en cualquier entorno distinto de `local`/`testing`» · Esperado: pasa. *— ✅ 2026-10-28, PR #295: la clave de desarrollo se rechaza en `production` y `staging`; se acepta en `testing`.*
  - [ ] **AC-14.4** — Verificación: rotar la clave de la cuenta `id=1` de staging **y** de producción (gestor de contraseñas; ≥ 16 caracteres o frase larga; distinta en cada entorno), invalidar sesiones activas · Esperado: la clave anterior devuelve 401 en ambos entornos; ninguna clave de la cuenta aparece en chats, tickets ni el repo.
  - [ ] **AC-14.5** — Verificación: política «las cuentas con rol `super-admin` exigen MFA» (se cierra junto con T-10) · Esperado: un login de super-administrador sin MFA configurado no completa la sesión; prueba PHPUnit.

#### T-15 · Limpieza de nginx y de variables huérfanas
- **Resuelve:** H-17. **Esfuerzo:** 0,5 d. **Riesgo:** bajo.
- **Criterios de aceptación**
  - [x] **AC-15.1** — Verificación: 30 `POST` a `/api/v1/panel/login` en 10 s (credenciales ficticias) contra **staging** · Esperado: parte de las respuestas son `429`/`503` del límite de nginx (zona `acceso` aplicada a la ruta real). *— ✅ 2026-10-28, staging, desde la red de José: 30 `POST` en paralelo → 7 × `401` y 23 × `429` con página de nginx (la zona `acceso` actúa). Desde la sesión de trabajo la prueba no sirve: sus salidas rotan de IP y ni el límite de nginx ni el de Laravel (20/min) se alcanzan.*
  - [x] **AC-15.2** — Verificación: `grep -rn 'TRUSTED_PROXIES\|SEDE_ORIGENES_PERMITIDOS' backend/app backend/config backend/bootstrap deploy docker` · Esperado: cada variable o se lee en código o no aparece en las plantillas. *— ✅ 2026-10-28, PR #295: `grep` → `TRUSTED_PROXIES` ya no aparece en `backend/app`, `config`, `bootstrap`, `deploy` ni `docker`; `SEDE_ORIGENES_PERMITIDOS` se lee en `config/cors.php`.*

---

### Fase 2 — Cumplimiento del Módulo 01 · cierre = G3

#### T-20 · Publicar las cinco políticas con contenido, acto administrativo y descarga en formato abierto
- **Resuelve:** H-21 · RF-B1-009, RF-B2-008/009/010 · Ley 1581/2012. **Esfuerzo:** 1 d de TIC; **el cuello de botella es Jurídica** (redacción y adopción por acto administrativo). **Riesgo:** **legal** si se publica un texto sin aprobación. **Yo no redacto estos textos por ti**: son obligaciones jurídicas de la Alcaldía; puedo preparar una *estructura* para que Jurídica complete. **Depende de:** Jurídica, Oficial de Protección de Datos (D-4).
- **Qué:** (1) Jurídica entrega y aprueba los 5 textos; (2) se adopta el acto administrativo; (3) TIC los publica desde contenido versionado, con versión y fecha de vigencia, y ofrece descarga ODT y PDF/A; (4) se enlaza la versión de la política de cookies con `VERSION_POLITICA`.
- **Criterios de aceptación**
  - [ ] **AC-20.1** — Verificación: `for s in terminos-y-condiciones-de-uso seguridad-y-privacidad proteccion-y-tratamiento-de-datos-personales uso-de-cookies derechos-de-autor-y-uso-sobre-contenidos; do curl -s -H 'Accept: text/html' https://<sede>/politicas/$s | grep -ci 'en preparaci'; done` · Esperado: `0` cinco veces.
  - [ ] **AC-20.2** — *Términos y condiciones.* Verificación: lista de comprobación firmada por Jurídica **más** una prueba de contenido que exija un encabezado por componente · Esperado: están presentes los componentes mínimos del módulo — condiciones y alcance de uso; límites de uso; derechos del usuario; deberes del usuario; responsabilidad de la entidad; contacto; referencia a privacidad/seguridad. *(El módulo habla de «6 componentes mínimos» pero no los enumera uno a uno: **confirmar la lista exacta con Jurídica**; la enumeración anterior es mi lectura.)*
  - [ ] **AC-20.3** — *Privacidad.* Verificación: lista firmada por el Oficial de Protección de Datos + prueba de contenido · Esperado: incluye categorías de datos, finalidades, derechos ARCO (acceso, actualización/rectificación, supresión, revocatoria), contacto del responsable, mecanismo de reclamo, fecha de vigencia y versión.
  - [ ] **AC-20.4** — *Derechos de autor.* Verificación: lectura firmada · Esperado: describe los términos de reutilización de contenidos (RF-B2-010).
  - [ ] **AC-20.5** — Verificación: `curl -sI https://<sede>/politicas/<slug>.odt` y `.pdf` · Esperado: `200` con `content-type` `application/vnd.oasis.opendocument.text` y `application/pdf`; el PDF valida como PDF/A (veraPDF o equivalente, resultado adjunto).
  - [ ] **AC-20.6** — Verificación: cada política cita número y fecha del acto administrativo · Esperado: presentes en las 5.
  - [ ] **AC-20.7** — Verificación: prueba automatizada que **falla** si alguna política contiene «en preparación» · Esperado: pasa en CI.
  - [ ] **AC-20.8** — Verificación: cambiar el texto de la política de cookies e incrementar `VERSION_POLITICA` en un entorno de prueba · Esperado: el banner reaparece (RF-01-D01).

#### T-21 · Cero vínculos rotos y datos de identidad alineados
- **Resuelve:** H-22 · RF-B2-041, RF-B2-038, RF-B1-002. **Esfuerzo:** 0,5 d. **Riesgo:** bajo. **Depende de:** G2.
- **Criterios de aceptación**
  - [ ] **AC-21.1** — Verificación: `lychee --no-progress https://<sede>/sitemap.xml` (o el verificador del repo) sobre sitemap + menú + pie + migas · Esperado: `0 errors`; en particular `GET /politicas` → `200` (índice de políticas) o la miga deja de apuntar allí.
  - [ ] **AC-21.2** — Verificación: prueba de contrato «todo `politicas[].slug` de `/api/v1/identidad/footer` existe como página del sitio» · Esperado: pasa.
  - [ ] **AC-21.3** — Verificación: `curl -sI https://<sede><logo_path de /api/v1/identidad/top-bar>` · Esperado: `200`.
  - [ ] **AC-21.4** — Verificación: validación de URL de redes (`filter_var(..., FILTER_VALIDATE_URL)`) en el origen de datos, con prueba · Esperado: una URL con espacio es rechazada; el pie muestra las 4 redes.

#### T-22 · Buscador real: sugerencias, tolerancia ortográfica y pertinencia
- **Resuelve:** H-23 · RF-B1-005/006, RF-B2-035/036 · D-03. **Esfuerzo:** 4-6 d. **Riesgo:** medio (relevancia subjetiva; rendimiento). **Depende de:** G2; datos de contenido (trámites, políticas, noticias). **Recomendación (HIPÓTESIS sobre volumen):** con el catálogo actual, `pg_trgm` + búsqueda de texto completo de PostgreSQL basta; no justifica un servicio nuevo.
- **Criterios de aceptación**
  - [ ] **AC-22.1** — Verificación: `curl -s 'https://<sede>/api/v1/busqueda/sugerencias?q=licen'` · Esperado: ≤ 10 sugerencias con título, categoría y URL; el contrato pasa de `pending` a `implemented`.
  - [ ] **AC-22.2** — Verificación: prueba del componente · Esperado: con < 3 caracteres no consulta; con ≥ 3 muestra sugerencias.
  - [ ] **AC-22.3** — Verificación: banco de 10 consultas con un error tipográfico cada una (p. ej. «licensia») · Esperado: ≥ 8 de 10 devuelven el resultado esperado entre los 3 primeros *(umbral mío; se revisa tras medir)*.
  - [ ] **AC-22.4** — Verificación: `GET /buscar?q=…` · Esperado: solo contenido de la sede; cada resultado con fecha, categoría, título y extracto (autor y miniatura cuando existan), ordenados por pertinencia.
  - [ ] **AC-22.5** — Verificación: axe-core sobre `/buscar` y el buscador del encabezado · Esperado: 0 violaciones críticas/serias; patrón `combobox` con `aria-expanded`, `aria-controls`, `listbox`, navegable con teclado.
  - [ ] **AC-22.6** — Verificación: 100 consultas contra staging · Esperado: p95 < 300 ms *(umbral mío)*.

#### T-23 · Noticias en la home y carrusel con contenido real
- **Resuelve:** H-24 · RF-B1-011, RF-B1-042. **Esfuerzo:** 2-3 d. **Riesgo:** bajo. **Depende de:** T-24 para la edición; puede avanzar antes con datos sembrados.
- **Criterios de aceptación**
  - [ ] **AC-23.1** — Verificación: `curl -s https://<sede>/ | grep -c 'Últimas noticias'` · Esperado: ≥ 1; `/noticias` ya no contiene «en preparación».
  - [ ] **AC-23.2** — Verificación: pruebas de API · Esperado: título > 150 caracteres o descripción > 200 → `422`; la imagen debe ser 4:3 o 16:9 (otra proporción → `422`); orden cronológico inverso.
  - [ ] **AC-23.3** — Verificación: inspección del carrusel · Esperado: ninguna imagen es `fondo-gris-carrusel.jpg`; controles de posición, Play/Stop y flechas sin solaparse; pausado por defecto.
  - [ ] **AC-23.4** — Verificación: axe-core · Esperado: 0 violaciones críticas/serias en la home.

#### T-24 · CMS mínimo para menú, noticias y carrusel (con el tope 7 / 2 niveles)
- **Resuelve:** H-24, H-26 · RF-01-D02, RN-01-D02, HU-01-D02 · D-15. **Esfuerzo:** 8-12 d (**la tarea más grande del módulo**). **Riesgo:** medio-alto (alcance). **Depende de:** T-07 y T-10 (permisos y MFA ya sólidos antes de abrir escritura).
- **Criterios de aceptación**
  - [ ] **AC-24.1** — Verificación: pruebas de API de crear, editar, reordenar, despublicar y eliminar (con confirmación) para ítems de menú, noticias y diapositivas · Esperado: todas pasan.
  - [ ] **AC-24.2** — Verificación: agregar un 8.º ítem de primer nivel; agregar un 3.er nivel · Esperado: `422` con el mensaje «máx. 7 ítems / 2 niveles».
  - [ ] **AC-24.3** — Verificación: intentar eliminar o reordenar los 4 ítems obligatorios · Esperado: `422`; el orden Inicio → Transparencia y acceso a la información pública → Atención y Servicios a la Ciudadanía → Participa se conserva.
  - [ ] **AC-24.4** — Verificación: publicar un cambio y `curl` al sitio · Esperado: visible en < 1 min (HU-01-D02).
  - [ ] **AC-24.5** — Verificación: consultar `audit_log` tras cada cambio · Esperado: una fila por acción con usuario y recurso.
  - [ ] **AC-24.6** — Verificación: usuario sin `menu.gestionar` / `noticias.gestionar` · Esperado: `403`.
  - [ ] **AC-24.7** — Verificación: `/sitemap.xml` y `/mapa-del-sitio` tras agregar una página · Esperado: la incluyen sin redespliegue.

#### T-25 · Lista blanca de dominios de confianza administrable
- **Resuelve:** H-26 · RF-01-D03, HU-01-D03. **Esfuerzo:** 2 d. **Riesgo:** bajo-medio (un dominio mal puesto en la lista silencia el aviso). **Depende de:** T-24.
- **Criterios de aceptación**
  - [ ] **AC-25.1** — Verificación: agregar un dominio por API, recargar y pulsar un enlace a él · Esperado: sin modal; al quitarlo, reaparece.
  - [ ] **AC-25.2** — Verificación: pruebas de `useAvisoSalida` con `evilgov.co`, `gov.co.evil.com`, `javascript:alert(1)` y `//otro.com` · Esperado: los cuatro **disparan** el aviso o se rechazan (nunca se confían).
  - [ ] **AC-25.3** — Verificación: `audit_log` · Esperado: cada alta/baja queda registrada.
  - [ ] **AC-25.4** — Verificación: prueba de que sin el endpoint (backend caído) se usa la lista por defecto del código · Esperado: pasa.

#### T-26 · Cookies: nada no esencial antes del consentimiento, y registro del consentimiento
- **Resuelve:** H-25 · RF-B1-008, RF-01-D01, RN-01-D01, HU-01-D01 · Ley 1581/2012. **Esfuerzo:** 2-3 d. **Riesgo:** medio (decidir si se mantiene la analítica de Cloudflare). **Depende de:** D-5 (§8.6).
- **Criterios de aceptación**
  - [ ] **AC-26.1** — Verificación: `curl -s -H 'Accept: text/html' https://<sede>/ | grep -c cloudflareinsights` · Esperado: `0` (se desactiva la inyección automática de Cloudflare o se carga solo tras consentir).
  - [ ] **AC-26.2** — Verificación: Playwright con contexto limpio; sin tocar el banner, listar `context.cookies()` y las peticiones salientes · Esperado: solo cookies esenciales documentadas; 0 peticiones a dominios de analítica.
  - [ ] **AC-26.3** — Verificación: Aceptar / Rechazar / Configurar por categoría / Revocar desde «Configurar cookies» · Esperado: la analítica se activa solo con aceptación explícita y se detiene al revocar.
  - [ ] **AC-26.4** — Verificación: `POST /api/v1/consentimientos` · Esperado: guarda categorías, versión de la política y *timestamp*, sin datos personales directos; prueba de API y definición de retención.
  - [ ] **AC-26.5** — Verificación: consentimiento con fecha de hace 13 meses · Esperado: el banner reaparece y se registra uno nuevo con versión y *timestamp* (prueba; `consentimiento.test.ts` ya cubre la caducidad: confirmar que sigue pasando).
  - [ ] **AC-26.6** — Verificación: caso negativo de HU-01-D01 (consentimiento caducado + intento de cargar analítica) · Esperado: el script no se inyecta y queda el registro «consentimiento caducado, cookie bloqueada».

#### T-27 · Etiquetas, enlaces y encabezado de la home
- **Resuelve:** H-27, H-19, H-36 · RF-B1-003, RF-B1-001. **Esfuerzo:** 0,25 d. **Riesgo:** bajo.
- **Criterios de aceptación**
  - [ ] **AC-27.1** — Verificación: prueba de DOM · Esperado: el segundo ítem del menú dice «Transparencia y acceso a la información pública».
  - [ ] **AC-27.2** — Verificación: `curl -s https://<sede>/ | grep -o 'href="https://www.gov.co/home/"'` · Esperado: presente en la barra superior.
  - [ ] **AC-27.3** — Verificación: `curl -s https://<sede>/ | grep -o '<h1' | wc -l` · Esperado: `1`.
  - [ ] **AC-27.4** — Verificación: decisión D-6 sobre el botón «Iniciar sesión» → `/admin/acceso` documentada · Esperado: acta/ADR.

#### T-28 · Cierre del Kit UI (componentes y estados) y decisiones documentadas
- **Resuelve:** H-28, H-20 · RF-B3-062..076, RF-B1-098, RNF-01-D01, RNF-B3-045/046, RN-B2-029. **Esfuerzo:** 3 d. **Riesgo:** bajo.
- **Criterios de aceptación**
  - [ ] **AC-28.1** — Verificación: una prueba (vitest + axe o Playwright) por componente de RF-B3-062, 063, 064, 066, 070, 075, 076, con los estados que el requisito enumera · Esperado: todas pasan; una tabla «componente → estados cubiertos» sin celdas vacías.
  - [ ] **AC-28.2** — Verificación: componente Toast con `role="status"` (o `alert` si es error) y duración suficiente o pausa al pasar el cursor · Esperado: existe y pasa su prueba.
  - [ ] **AC-28.3** — Verificación: spinner con mensaje de estado después de 10 s (prueba con temporizadores simulados) · Esperado: pasa.
  - [ ] **AC-28.4** — Verificación: paginación con área activa ≥ 44×44 px en móvil y `aria-current="page"` · Esperado: pasa.
  - [ ] **AC-28.5** — Verificación: ADR «Kit UI autoalojado frente a CDN `cdn.www.gov.co`» · Esperado: documento que justifica la divergencia de RNF-01-D01 y fija cómo se actualiza la versión del Kit.
  - [ ] **AC-28.6** — Verificación: licencia de las tipografías (`public/govco/LICENSE.md` y paquete oficial) · Esperado: confirmación escrita de que se pueden redistribuir; si no, se retiran del paquete propio.
  - [ ] **AC-28.7** — Verificación: auditoría de marca con lista de comprobación (logo GOV.CO sin deformar, Cobalt, tipografías) · Esperado: 0 violaciones, con capturas.
  - [ ] **AC-28.8** — Verificación: componente Acordeón con `aria-expanded`, que no se abre al recibir foco y con los 4 estados del requisito (prueba vitest + axe) · Esperado: existe, está consumido por al menos una página (Trámites o Detalle) y pasa. *(Construirlo sin consumidor repite el caso de la galería; ver D-27 de la auditoría propia.)*
  - [ ] **AC-28.9** — Verificación: tabla pública con ordenamiento asc/desc por columna y `aria-sort` (prueba de DOM) · Esperado: pasa en la tabla de `pqrsd.vue` y en la de normativa si aplica.

#### T-29 · Verificar en ≥ 3 navegadores
- **Resuelve:** H-29 · RNF-B1-033, RNF-B3-005. **Esfuerzo:** 1-2 d. **Riesgo:** bajo-medio (diferencias reales que habrá que corregir).
- **Criterios de aceptación**
  - [ ] **AC-29.1** — Verificación: `playwright.config` con proyectos `chromium`, `firefox`, `webkit` (y Edge con `channel: 'msedge'`) · Esperado: definidos.
  - [ ] **AC-29.2** — Verificación: CI ejecuta `tests/diseno.mjs` y los *smoke* funcionales en los motores anteriores · Esperado: 0 fallos funcionales; resultado por navegador publicado como artefacto.
  - [ ] **AC-29.3** — Verificación: en cada motor, emulación móvil · Esperado: todos los controles interactivos ≥ 44×44 px.

#### T-30 · Producción: IPv6 de punta a punta y puesta en servicio
- **Resuelve:** H-31 · RF-B1-099, RNF-B1-027, RN-B3-004. **Esfuerzo:** 0,5 d. **Riesgo:** bajo. **Depende de:** que exista el dominio de producción.
- **Criterios de aceptación**
  - [ ] **AC-30.1** — Verificación: `dig +short A <dominio-prod>` y `dig +short AAAA <dominio-prod>` · Esperado: ambos devuelven registros.
  - [ ] **AC-30.2** — Verificación: `curl -6 -sI https://<dominio-prod>/` desde una red con IPv6, y `curl -4 …` · Esperado: `200` con ambas pilas.
  - [ ] **AC-30.3** — Verificación: si hay Cloudflare, «IPv6 Compatibility» habilitado en el panel · Esperado: habilitado (captura).
  - [ ] **AC-30.4** — Verificación: cabecera `X-Robots-Tag` en producción · Esperado: ausente (se indexa); en staging sigue `noindex, nofollow`.

#### T-32 · Indexación y medición de HU-B1-001
- **Resuelve:** H-35 · HU-B1-001. **Esfuerzo:** 1 d. **Riesgo:** bajo; **el resultado final depende de Google, no de ti**. **Depende de:** T-30.
- **Criterios de aceptación**
  - [ ] **AC-32.1** — Verificación: `curl -s https://<dominio-prod>/robots.txt` · Esperado: no contiene `Disallow: /` global y lista el `sitemap.xml`.
  - [ ] **AC-32.2** — Verificación: Search Console · Esperado: sitemap enviado y procesado (captura).
  - [ ] **AC-32.3** — Verificación: la home tiene `<title>`, `<h1>` único, `meta description` y datos estructurados de organización pública · Esperado: comprobados.
  - [ ] **AC-32.4** — Verificación: a los 30 días, en Search Console, posición de la URL de la sede para la consulta «Alcaldía Santa Marta» · Esperado: **se mide y se reporta**; el objetivo es «entre los 5 primeros», pero no es una garantía que TIC pueda cumplir por sí sola.

---

### Fase 3 — Integración administrativa a GOV.CO · cierre = G4

#### T-31 · Ejecutar el proceso de integración de 7 pasos y su documentación
- **Resuelve:** H-30 · RF-B1-070/100, RF-B2-001/002/003, RF-B3-125/126/128, RN-B1-001, RN-B2-028, RN-B3-005/007, UC-B2-009 · Decreto 2106/2019 arts. 14-15. **Esfuerzo:** de TIC, ≈ 3-5 d; el **calendario lo marca MinTIC** (el módulo cita una metodología de 17 semanas, UC-B2-009). **Riesgo:** **alto de calendario** — depende de terceros. **Depende de:** T-20, T-08, T-30 y de que §5.1-§5.4 estén en ✅ (MinTIC verifica top bar, footer, 4 etapas, seguridad y accesibilidad).
- **Criterios de aceptación**
  - [ ] **AC-31.1** — Paso 1: acta de lectura de los lineamientos, con fecha y responsables · Esperado: existe.
  - [ ] **AC-31.2** — Paso 2-3: esta matriz (§5) con **todas** las filas de §5.1 a §5.4 en ✅ o ⏸ · Esperado: 0 filas en ❌/🟡/❔ en esas secciones.
  - [ ] **AC-31.3** — Paso 4: formulario de solicitud presentado a MinTIC · Esperado: número y fecha de radicado.
  - [ ] **AC-31.4** — Paso 5: registro de cada observación de MinTIC y su respuesta · Esperado: 0 observaciones abiertas.
  - [ ] **AC-31.5** — Paso 6: `curl -sIL https://www.gov.co/<palabra_clave>` y apertura en navegador · Esperado: `200`, contenido de la sede y la URL visible sigue siendo la de `gov.co`.
  - [ ] **AC-31.6** — Paso 7: informe de monitoreo mensual (disponibilidad, errores, tiempos) de al menos los últimos 3 meses · Esperado: existen.
  - [ ] **AC-31.7** — Plan de Integración: publicado, incorporado al PETI (versión y fecha) y enviado a la Dirección de Gobierno Digital (radicado); avance mensual actualizado · Esperado: las cuatro evidencias existen.
  - [ ] **AC-31.8** — Inventario de portales, micrositios, plataformas y apps de la Alcaldía (responde la pregunta abierta del §9 del módulo) · Esperado: archivo con cada uno, su dueño, estado de integración y fecha; 0 sin dueño.
  - [ ] **AC-31.9** — Ventanillas únicas (plazo vencido en may/2020): auditoría y plan correctivo con fechas · Esperado: documento aprobado.
  - [ ] **AC-31.10** — SUIT: comparar la lista de trámites de la Alcaldía con el catálogo SUIT y, en una muestra de 10, la ficha con los 4 momentos · Esperado: 0 diferencias en la lista; 10/10 fichas completas.

---

### Fase 4 — Adyacentes al módulo (no son del Módulo 01, pero condicionan su cierre)

#### T-33 · Derecho de petición que realmente se registra *(módulo 04 · PQRSD)*
- **Resuelve:** H-32 · RF-B3-127. **Esfuerzo:** un módulo entero (semanas); se planifica en su propio documento. **Riesgo:** **legal** — hoy la sede no recibe peticiones por este canal.
- **Criterios de aceptación**
  - [ ] **AC-33.1** — Enviar el formulario crea un registro con número de radicado y estado «solicitud registrada»; el texto «no se registrará nada» desaparece · Esperado: prueba de extremo a extremo.
  - [ ] **AC-33.2** — `/seguimiento` consulta por radicado y muestra únicamente los 4 estados estándar de GOV.CO (registrada → recibida a satisfacción → en trámite → resuelta) · Esperado: prueba.
  - [ ] **AC-33.3** — Contrato OpenAPI con estas operaciones en `implemented` · Esperado: `npm run contrato` verde.

#### T-34 · Que la trazabilidad no vuelva a envejecer
- **Resuelve:** H-33, H-37. **Esfuerzo:** 1,5 d. **Riesgo:** bajo.
- **Criterios de aceptación**
  - [ ] **AC-34.1** — Verificación: en CI, `node sitio/scripts/trazabilidad.mjs && git diff --exit-code docs/trazabilidad.md` · Esperado: falla si la matriz está desactualizada.
  - [ ] **AC-34.2** — Verificación: script que compara `x-status` del contrato con `php artisan route:list --json` · Esperado: 0 diferencias (hoy: 8 rutas reales marcadas `pending`).
  - [ ] **AC-34.3** — Verificación: cobertura de trazabilidad del generador · Esperado: ≥ 60 % de los 137 criterios al cerrar la Fase 2 *(meta mía, hoy 36 %; la acuerdas tú)*.
  - [ ] **AC-34.4** — Verificación: cada una de las 12 desviaciones declaradas tiene un ADR enlazado · Esperado: 12/12.
  - [ ] **AC-34.5** — Verificación: en `auditoria-sede.md`, cada fila de la tabla §4.1 con ❌ «no existe» se contrasta con el inventario de defectos y con `grep` del símbolo en `sitio/app/` y `panel/src/` · Esperado: 0 contradicciones; la fecha y el commit de la edición coinciden con el estado del código revisado (hoy hay 8 filas desfasadas entre su tabla y el código, §5.8).

#### T-35 · Puertas de calidad del CI
- **Resuelve:** H-34, H-12. **Esfuerzo:** 1,5 d. **Riesgo:** bajo-medio (puede revelar deuda al activarse).
- **Criterios de aceptación**
  - [ ] **AC-35.1** — Verificación: `grep -n 'coverage\|eslint\|stylelint' .github/workflows/ci.yml` en `main` · Esperado: pasos de cobertura (umbral acordado; el proyecto declaró 95 %), ESLint y Stylelint presentes.
  - [ ] **AC-35.2** — Verificación: el permiso `workflow` que bloqueó el push (`DEPLOY-STAGING-REPORT.md`) queda resuelto · Esperado: los cambios de `ci.yml` están en `main`, `staging` y `develop` (mismo contenido).
  - [ ] **AC-35.3** — Verificación: PHPStan/Larastan corren en CI · Esperado: job verde (hoy: **1 error** en `FilesMediaService.php:51`, §3.1.1; hay que corregirlo antes de activar el job).
  - [ ] **AC-35.4** — Verificación: cobertura del backend medida con PCOV/Xdebug · Esperado: cifra publicada y umbral acordado (hoy **93,9 %** de líneas y 85,5 % de métodos; el 95 % declarado no se alcanza; priorizar `AuthService` 59 % y `AuditLogResource` 0 %).

---

### 8.5 Hoja de ruta, esfuerzo y dependencias

| Fase | Tareas | Esfuerzo (estimado, ±50 %) | Bloqueos externos | Compuerta |
|---|---|---|---|---|
| **0 · Contención** | T-00, T-01, T-02, T-03, T-04, T-06 | ≈ 2 d | Acceso de administración al servidor y a GitHub (tuyo) | G0 → G1 |
| **1 · Seguridad funcional** | T-05, T-07, T-08, T-09, T-10, T-11, T-12, T-13, T-14, T-15 | ≈ 12 d (≈ 8 d si D-3 = retirar MFA) | Decisión D-3, D-5 | G1 → G2 |
| **2 · Módulo 01** | T-20 … T-30, T-32 | ≈ 30 d de TIC | **Jurídica** (T-20); dominio de producción (T-30/T-32) | G2 → G3 |
| **3 · Integración GOV.CO** | T-31 | ≈ 3-5 d de TIC + calendario de MinTIC | MinTIC, Alta Dirección | G3 → G4 |
| **4 · Adyacentes** | T-33, T-34, T-35 | T-34 + T-35 ≈ 2,5 d; T-33 es un módulo | Planificación del módulo 04 | G4 |

**Total de TIC en fases 0-2 y T-34/T-35: ≈ 46 días-persona (≈ 9-10 semanas de un desarrollador a tiempo completo), ±50 %.** Excluye T-31 (calendario de terceros) y T-33 (módulo propio).

**Orden de ejecución sugerido (dependencias):**
`T-01 → T-06 → T-04` (el CI de credenciales y las reglas de rama van juntas, y T-04 requiere T-09 en verde) · `T-02/T-03` en paralelo (los hace quien administra el servidor) · `T-09` antes de T-04 · `T-05, T-07` primero de la Fase 1 (son baratas y cierran hallazgos Altos) · `T-26` antes de `T-08` (decide si el *beacon* entra en la CSP) · `T-24` antes de `T-23`/`T-25` para edición, pero T-23 puede avanzar con datos sembrados · `T-20` empieza **ya** en paralelo, porque su cuello de botella es externo · `T-30` antes de `T-32` y de `T-31`.

### 8.6 Decisiones que necesito de ti (compuerta G0)

| ID | Decisión | Opciones | Mi recomendación | Consecuencia de no decidir |
|---|---|---|---|---|
| **D-1** | ¿Repo privado o público? | (a) Privado; (b) público sin `accesos.md` | (a): es un sistema de una entidad pública con inventario operativo en su historial | T-01 sin cerrar; riesgo H-01 sigue |
| **D-2** | Reglas de rama | A: checks obligatorios, 0 aprobaciones, revisor en `production`; B: A + ≥ 1 aprobación + código-owner | A si trabajas solo; B en cuanto haya una segunda persona | T-04 se queda en «CI informativo» |
| **D-3** | MFA | Aplicarlo (T-10 completo) o retirarlo mientras no exista | Aplicarlo al menos para roles con `panel-administrative` | El campo `mfa_habilitado` sigue dando falsa seguridad |
| **D-4** | Responsables de las políticas y plazos | Nombre de Jurídica y del Oficial de Protección de Datos; fecha objetivo | Fijar fecha ahora: es la ruta crítica de G3 | G3 bloqueada indefinidamente |
| **D-5** | Analítica | (a) Quitar el *beacon* de Cloudflare; (b) cargarlo solo tras consentimiento | (b) si se quiere medir; (a) si no hay uso real de los datos | H-25 sigue; riesgo de incumplir Ley 1581 |
| **D-6** | Botón «Iniciar sesión» en la barra superior pública → `/admin/acceso` | Mantener / quitar / separar login ciudadano y de funcionarios | Quitarlo de la barra pública hasta que exista login ciudadano | H-19 sigue (bajo) |

**Respuestas de José (2026-10-28)**

| ID | Decisión tomada | Estado | Qué sigue (y quién lo hace) |
|---|---|---|---|
| **D-1** | **(b) Público sin `accesos.md`** | ✅ Decidida (2026-10-29; antes (a), revertida por José) | El repo sigue público. **HECHO:** `accesos.md` ya no está versionado en `main`, `staging` ni `develop`. **HECHO:** sigue en el historial (del 2026-09-30 al 2026-10-28, con una sección sobre un incidente de credenciales publicadas); no se reescribe el historial. **INFERENCIA:** todo lo que ese archivo describía debe darse por conocido por terceros, y pudo haber clones o *forks*. Por eso H-01 solo baja de severidad cuando se rote lo que describía (AC-01.3), y esa rotación pasa de recomendable a **obligatoria**. **Efecto en T-04:** los rulesets siguen aplicándose (no hay el límite del plan Free de los repos privados). **Pendiente tuyo:** activar *Secret scanning* y *Push protection* en *Settings → Code security* (gratuitos en repos públicos); mi sesión no puede leer ni cambiar ese ajuste. |
| **D-2** | **A:** checks obligatorios, 0 aprobaciones, revisor requerido en `production` | ✅ Decidida | T-04: reglas de rama/*ruleset*, revisores en el entorno `production`, mover `DEPLOY_*` a secretos por entorno (H-02, H-39). Aún sin aplicar: son cambios en GitHub que requieren tu visto bueno al ejecutarlos. |
| **D-3** | **Diferida:** «dejar una nota para después» | ⏸ Pospuesta | Mientras no se decida, el campo `mfa_habilitado` sigue dando falsa seguridad (H-38 / T-10 / AC-14.5). **Nota para retomar:** decidir entre exigir MFA a roles con `panel-administrative` (recomendado), a todos los funcionarios, o retirar el campo. Hasta entonces la contraseña del super-admin de staging debe rotarse (AC-14.4). |
| **D-4** | **Sin datos en el expediente** | ❌ Sin resolver | **HECHO:** busqué en `docs/` y `sede-electronica-doc/`. El expediente define el **rol** (Oficial de Protección de Datos: RN-B2-013/014, Decreto 620/2020, ST-04; Oficina Jurídica como «enlace legal» con correo genérico de plantilla) pero **no nombra a ninguna persona ni fija fecha**, y `INFORME-SANTA-MARTA.md` confirma que la política vigente ni identifica al responsable ni menciona al Oficial. **Falta:** nombre y cargo del responsable del tratamiento, del Oficial y de Jurídica, y una fecha objetivo. Sigue siendo la ruta crítica de G3. |
| **D-5** | **(b) Cargar la analítica solo tras consentimiento** | ✅ Decidida | Implica un mecanismo de consentimiento previo (sin cargar el *beacon* hasta aceptar), registro del consentimiento y que la política lo describa (depende de D-4). Mientras tanto H-25 sigue abierto. Cambio de código en el sitio: lo harás tú o me lo pides. |
| **D-6** | **Quitar «Iniciar sesión» de la barra pública** | ✅ Decidida | Cambio en el sitio (H-19); los funcionarios entran por la URL directa. Cambio de código: lo harás tú o me lo pides. |

---

### 8.7 Estado de ejecución (corte 2026-10-29)

Lo que se ha **hecho y comprobado**, lo que está **a medias** y lo que **sigue abierto**. Las casillas marcadas en §8 llevan su evidencia al final de la línea; lo no marcado no está comprobado.

**Resumen del corte (2026-10-29).** Criterios marcados: 45 de 161. De los 31 sin marcar en las fases 0 y 1, 2 pertenecen a T-00 (superada) y 4 a T-10 (diferida por D-3); el resto depende de ti o del servidor.

*Solucionado y comprobado desde la sesión:* T-06 (gitleaks bloqueante, probado con PR fallido #315 y verde #314); T-01 en lo que es del repositorio (AC-01.1 por la vía alternativa, AC-01.4, AC-01.5); T-11, T-13 (AC-13.2 re-medido: 4,7 ms), T-15, T-07, T-09, T-05 en su camino negativo; `shellcheck` de `deploy/droplet/*.sh` sin errores; las tres ramas se mantienen con el mismo árbol de archivos.

*Preparado, a la espera de ti:* ruleset de T-04 (opción (a), comando en T-04); `deploy/droplet/usuario-prueba.sh` para AC-05.5 y AC-12.5 (sin probar contra el servidor).

*Solo tuyo:* rotación de lo descrito en `accesos.md` (AC-01.3, obligatoria porque el repo es público); revocar el token OAuth (AC-01.6); activar *Secret scanning* y *Push protection*; kernel y reinicio, puerto 22 y §9.2 (T-03); clave del usuario `id=1` (AC-14.4); Cloudflare (T-26) y el nonce de Nuxt para pasar la CSP a modo de aplicación (T-08); decidir T-10 (D-3) y S3 frente a disco local (T-12); decidir qué hacer con este mismo archivo (ver la advertencia inicial).

*Hallazgo nuevo del corte:* `PermissionSeeder` no siembra `archivos.*`; hoy solo un super-administrador usa esas rutas (AC-12.5).

*Con matices:* la enumeración por tiempo (H-14) está medida con 30 + 30 intentos sobre credenciales inválidas; no incluye la ruta de contraseña correcta.

| Tarea | Estado | Qué falta |
|---|---|---|
| T-00 | Superada | El documento ya está versionado en el repo (AC-00.1 no aplica). |
| T-01 | **Parcial** | Cumplidos AC-01.4 y AC-01.5 (PR #314). AC-01.1 por la vía alternativa (D-1 = repo público sin `accesos.md`; ver abajo); AC-01.2 parcial (escaneo hecho, 0 hallazgos reales por reglas, pero la vigencia de lo expuesto en `accesos.md` solo la determina la rotación); AC-01.3 (lista de secretos con fecha de rotación, fuera del repo) y AC-01.6 (el remoto local aún lleva el token, H-44; revocar el token OAuth) son tuyos. |
| T-02 | **Hecha** (staging) | AC-02.6 sin `ls -ld` explícito (es en el servidor). `shellcheck deploy/droplet/*.sh` ejecutado el 2026-10-29: 0 errores; se corrigió el aviso SC2034 de `despliegue-remoto.sh`. |
| T-03 | **Parcial** | Firewall 80/443 y Postfix hechos; puerto 22 (cerrado en el firewall de DigitalOcean según José, **sin comprobar desde aquí**) y kernel (H-40, AC-03.5) pendientes; §9.2 filas 2-7 no repetibles desde la sesión (proxy); archivo de evidencia (AC-03.3) entregado aparte, fuera del repo. |
| T-04 | Abierta | AC-04.1 a 04.6. Las reglas actuales (`deletion`, `non_fast_forward`, `pull_request` con 0 aprobaciones) no exigen `required_status_checks`. **Decisión 2026-10-29: opción (a)** (checks solo en `develop`); comando listo en T-04. Mi sesión no puede modificar rulesets (403 del proxy): lo ejecutas tú. Con el repo público (D-1) los rulesets se aplican sin límite de plan. |
| T-06 | **Hecha** | AC-06.1 a 06.3 (PR #314 y PR de prueba #315). |
| T-09 | **Hecha** | AC-09.7 (Trivy) sin comprobar aparte. |
| T-05 | **Hecha** (staging) | AC-05.5 (camino de éxito en navegador): falta el usuario de prueba; `deploy/droplet/usuario-prueba.sh crear` lo crea (sin probar contra el servidor). PR #296. |
| T-07 | **Hecha** | AC-07.5 verificado por código, no con `route:list` (sandbox con PHP 8.3). PR #294. |
| T-08 | **Parcial (fase informe)** | AC-08.1 ✅ en staging; AC-08.4 medido (ver H-09): pendiente nonce en Nuxt y ajuste de Cloudflare; AC-08.2 (7 días de informes); AC-08.3/08.5 (aplicar) y AC-08.4 (Playwright). Depende de T-26 por la analítica. PR #300. |
| T-10 | **Diferida (D-3)** | Decisión pospuesta a propósito. Mientras tanto `mfa_habilitado` sigue dando falsa seguridad (H-10, H-38, AC-14.5). Nota para retomar en D-3. |
| T-11 | **Hecha** | Nada abierto. PR #297 (y PR de prueba #307, cerrado). |
| T-12 | **Hecha en código** | AC-12.5 y el 413/422 de punta a punta en staging: falta el usuario de prueba (`usuario-prueba.sh crear archivos`). Hallazgo: `PermissionSeeder` no siembra `archivos.*`. Falta decidir S3 vs disco local: hoy el despliegue usa `local`. PR #299. |
| T-13 | **Hecha** | AC-13.1 a 13.3 cumplidos. AC-13.2: la primera medición falló (−269 ms); tras PR #308 y #310 la re-medición en staging dio 4,7 ms de diferencia de medianas (30 + 30). PR #298. |
| T-14 | **Parcial** | AC-14.4 (rotar clave `id=1` en staging y producción) y AC-14.5 (MFA para super-admin, con T-10) son tuyos. PR #295. |
| T-15 | **Hecha** (staging) | Nada abierto salvo lo ya anotado. PR #295 y #296. |
| T-20 a T-35 | Abiertas | Cumplimiento del Módulo 01 y adyacentes: sin avance verificado. |

## 9. Verificaciones a cargo del dueño (lo que no pude comprobar)

> Sustituye `<…>` por tus valores. **No pegues salidas con IP, llaves o secretos en el repo, en un PR ni en un chat.** Si una fila no da lo «Esperado», abre una tarea nueva.

### 9.1 Servidor

| # | Qué | Comando (en el servidor) | Esperado | Tarea |
|---|---|---|---|---|
| 1 | La llave de CI está restringida | `sudo sed -E 's/(ssh-[a-z0-9]+ )[A-Za-z0-9+\/=]+/\1[clave]/' ~<USUARIO_DESPLIEGUE>/.ssh/authorized_keys` | Cada línea empieza por `command="/usr/local/bin/desplegar.sh"` y contiene `restrict` | T-02 |
| 2 | La cuenta de despliegue no es root efectivo | `id -nG <USUARIO_DESPLIEGUE>` | No contiene `docker` ni `sudo` | T-02 |
| 3 | `sudo` mínimo | `sudo -l -U <USUARIO_DESPLIEGUE>` | Solo `desplegar.sh` | T-02 |
| 4 | SSH endurecido | `sudo sshd -T \| grep -Ei 'permitrootlogin\|passwordauthentication\|pubkeyauthentication'` | `permitrootlogin no` (o `prohibit-password`), `passwordauthentication no`, `pubkeyauthentication yes` | T-03 |
| 5 | Firewall del sistema | `sudo ufw status verbose` | Entrada denegada por defecto; solo 22, 80 y 443 (ideal: 80/443 únicamente desde los rangos de Cloudflare; 22 limitado a tus IP) | T-03 |
| 6 | Docker no salta el firewall | `sudo iptables -S DOCKER-USER` y `sudo ss -tlnp` | Solo 22/80/443 escuchando hacia fuera; 5432, 6379 y 9000 **no** en `0.0.0.0` | T-03 |
| 7 | Vista desde fuera | Desde **otra red**: `nmap -Pn -p 22,80,443,5432,6379,9000 <servidor>` | Abiertos solo los esperados | T-03 |
| 8 | Firewall de la nube | En DigitalOcean: *Networking → Firewalls* (o `doctl compute firewall list`) | Reglas equivalentes a la fila 5 | T-03 |
| 9 | Parches | `sudo apt list --upgradable 2>/dev/null \| grep -ci security` y `systemctl is-active unattended-upgrades` | `0` pendientes de seguridad; servicio `active` | T-03 |
| 10 | TLS del origen | En Cloudflare: *SSL/TLS → Overview* | Modo **Full (strict)** | T-03 |
| 11 | Copias de seguridad | Restaurar la última copia en un entorno de prueba | Restauración correcta; la frase de cifrado tiene custodia fuera del servidor | T-03 |

**Resultado de §9.1 (2026-10-28, con `sudo`):** fila 1 ✅ (`command=` + `no-pty`) · fila 2 ❌ (`deploy` en `docker`) · fila 3 🟡 (dos rutas en `sudoers`) · fila 4 ✅ · fila 5 🟡 (solo 22/80/443, pero abiertos al mundo) · fila 6 ✅ (puerto 25: ver AC-03.6) · fila 7 ❔ (no hecha) · fila 8 ❔ (no consultada) · fila 9 ❌ (6 paquetes del núcleo; lista negra de `unattended-upgrades`) · filas 10-11 ❔. Acceso de `root` por SSH cerrado y contraseñas desactivadas.

**Resultado de T-02 (2026-10-28, tras aplicar `t02-servidor.sh`):** fila 1 ✅ salvo que la línea usa opciones `no-*` explícitas y **no** `restrict` (AC-02.1 literal pendiente: valorar añadirlo) · fila 2 ✅ (`id -nG deploy` = `deploy sede-ssh`) · fila 3 ✅ (una sola ruta en `sudoers`) · AC-02.7 ✅ (hash instalado = repo) · AC-02.4 ✅ en el primer despliegue; **pendiente un segundo despliegue sin el grupo `docker`** · AC-03.4 ✅ (`authorized_keys` de `root` vaciado, con respaldo) · AC-02.3 ❔ (no ejecutado) · AC-02.6 🟡 (`/opt/sede/desplegar.sh` no existe y ya no está en `sudoers`; falta el `ls -ld` explícito). Reversión disponible: `t02-servidor.sh revertir` (respaldo en `/root/respaldo-t02-*`) y `usermod -aG docker deploy`.

**Cierre de AC-02.1 y AC-02.3 (2026-10-28, modo `restringir`):** AC-02.1 ✅ — la línea de `deploy` es `command="sudo -n /usr/local/bin/desplegar.sh",restrict ssh-ed25519 [clave] despliegue-sede@github-actions` (antes enumeraba `no-*`; respaldo en el servidor). AC-02.3 ✅ — **prueba real por SSH** (`ssh deploy@127.0.0.1 id` con una llave temporal de las mismas opciones, retirada después; `authorized_keys` quedó idéntico): respuesta `Entorno no válido: 'id'`, sin `uid=`. Es una prueba por bucle local con una llave de prueba, no con la llave de CI (que solo existe como secreto de GitHub); las opciones y el comando forzado son los mismos. AC-02.4 se confirma con el despliegue de staging posterior a este cambio (ver abajo).

### 9.2 GitHub (permisos de administrador)

| # | Qué | Comando o ruta | Esperado | Tarea |
|---|---|---|---|---|
| 1 | Sin *bypass actors* | *Settings → Rules → Rulesets* → cada ruleset → *Bypass list* | Vacía (o solo administradores, con PR obligatorio) | T-04 |
| 2 | Entornos y revisores | `gh api repos/SantanderAcuna/sede/environments --jq '.environments[]\|{name,protection_rules}'` | `production` con ≥ 1 revisor requerido; `staging` definido | T-04 |
| 3 | Secretos por entorno | `gh secret list --env production` (solo nombres) | Solo los esperados; ninguno compartido sin necesidad entre `staging` y `production` | T-03 |
| 4 | Permisos por defecto de Actions | `gh api repos/SantanderAcuna/sede/actions/permissions/workflow --jq .default_workflow_permissions` | `read` | T-04 |
| 5 | Escaneo de secretos | `gh api repos/SantanderAcuna/sede --jq '.security_and_analysis'` | `secret_scanning` y `secret_scanning_push_protection` = `enabled` | T-06 |
| 6 | Llaves de despliegue | `gh api repos/SantanderAcuna/sede/keys --jq '.[]\|{title,read_only}'` | Solo las esperadas, de solo lectura | T-03 |
| 7 | 2FA | *Organization/Account → Settings → Authentication* | Obligatoria para colaboradores | T-03 |

**Resultado de §9.2 (script del 2026-10-28):** fila 1 ✅ (sin *bypass*) · fila 2 ❌ (sin revisores → H-39) · fila 3 🟡 (secretos separados por nombre; valores no verificados) · fila 4 ✅ (`read`) · fila 5 ✅ (ambos `enabled`) · fila 6 ✅ (ninguna llave de repo) · fila 7 ❔ (2FA no consultada).

**Re-comprobación de §9.2 desde la sesión de trabajo (2026-10-28, tras T-05 a T-15):** solo dos filas son consultables desde aquí; el proxy de la sesión bloquea `environments`, `actions/permissions`, `keys` y los secretos (HTTP 403 «path not permitted»). **Fila 1 ✅ reconfirmada:** el único ruleset («Ramas permanentes de la sede») está `active`, con 0 *bypass actors* y las reglas `deletion`, `non_fast_forward` y `pull_request`. **Repo sigue público** (`private: false`), así que AC-01.1 sigue abierto. Las filas 2 a 7 conservan el resultado del script de José: **no las he podido repetir**, y por tanto AC-03.2 sigue sin marcar. AC-03.3 exige un archivo de evidencia **fuera del repo público**: se entrega aparte, no se versiona aquí.

### 9.3 Base de datos y aplicación

| # | Qué | Comando | Esperado | Tarea |
|---|---|---|---|---|
| 1 | ¿Cuántas cuentas existen? | `SELECT count(*) FROM users;` | Conocido y justificado (si es 1, H-07 es latente) | T-07 |
| 2 | UUID de la entidad | `SELECT count(*) FROM entidad WHERE uuid IS NULL;` (la API de staging devuelve `uuid: null`) | `0`; si no, la migración `add_uuid_columns` no pobló la fila | T-21 |
| 3 | Clave del super-administrador | `php artisan tinker --execute="dump(Hash::check('<clave de desarrollo del seeder>', App\Models\User::first()->password));"` | `false` | T-14 |
| 4 | Modo depuración | `curl -s https://<sede>/api/v1/ruta-inexistente` | JSON genérico, sin traza ni rutas del servidor | T-14 |

### 9.4 Imagen del sitio (refuerza §3.4)

`docker run --rm <imagen-del-sitio> sh -c 'ls -d node_modules/undici node_modules/node-forge 2>&1; grep -rl undici .output/server | head'` · **Esperado:** los dos directorios existen (están en la imagen) y `grep` no lista archivos del servidor SSR (no se ejecutan). Si lista alguno, **sube** la urgencia de T-09.

---

## 10. Trazabilidad: hallazgo → tarea → criterio → prueba

| Hallazgo | Tarea | Criterios | Prueba o verificación que lo guarda |
|---|---|---|---|
| H-01 | T-01 | AC-01.1…01.3 | `curl` a la API de GitHub; `gitleaks` sobre el historial |
| H-02 | T-04 | AC-04.1…04.4 | `gh api …/rules/branches/*`; PR de prueba bloqueado |
| H-03 | T-02 | AC-02.1…02.6 | §9.1 filas 1-3; despliegue de prueba a staging |
| H-04 | T-03 | AC-03.1…03.4, 03.6 | §9.1 y §9.2 |
| H-05 | T-06 | AC-06.1…06.3 | Job `credenciales` (PR de prueba) |
| H-06 | T-05 | AC-05.1…05.5 | `curl` a `/sanctum/csrf-cookie`; `auth.test.ts` |
| H-07 | T-07 | AC-07.1, 07.4, 07.5 | PHPUnit: sin permiso → 403 |
| H-08 | T-07 | AC-07.2, 07.3, 07.6 | PHPUnit: contenido de `data` |
| H-09 | T-08 | AC-08.1…08.5 | Cabeceras; Playwright sin violaciones |
| H-10 | T-10 | AC-10.1…10.5 | PHPUnit de login/MFA |
| H-11 | T-09 | AC-09.1…09.7 | `npm audit`, `composer audit`, CI verde |
| H-12 | T-11, T-35 | AC-11.1…11.3, AC-35.1 | `npm run lint` en CI |
| H-13 | T-12 | AC-12.1…12.5 | PHPUnit de subida; `curl` a la URL pública |
| H-14 | T-13 | AC-13.1…13.3 | PHPUnit; medición de tiempos |
| H-15, H-16 | T-14 | AC-14.1…14.3 | Config en producción; PHPUnit del seeder |
| H-38 | T-14, T-10 | AC-14.4, 14.5, AC-10.1 | Rotación verificada con `curl`; PHPUnit |
| H-39 | T-04, T-02 | AC-04.5, 04.6, AC-02.1…02.5 | `gh api` de entornos y secretos |
| H-40 | T-03 | AC-03.5 | `apt list --upgradable`; `reboot-required` |
| H-41 | T-03 | AC-03.7 | `ufw`, panel de DigitalOcean |
| H-42 | T-02 | AC-02.7 | `sha256sum` repo vs. servidor |
| H-43 | T-03 | AC-03.6 | `ss -tlnp` |
| H-44 | T-01 | AC-01.6 | `git remote get-url origin`; panel de GitHub |
| H-17 | T-15 | AC-15.1, 15.2 | Ráfaga de `POST` en staging; `grep` |
| H-18 | T-01 | AC-01.4, 01.5 | `git ls-files` |
| H-19 | T-27 (D-6) | AC-27.4 | ADR de la decisión |
| H-20 | T-28 | AC-28.6 | Confirmación escrita de licencia |
| H-21 | T-20 | AC-20.1…20.8 | `curl` + prueba que falla con «en preparación» + firmas de Jurídica |
| H-22 | T-21 | AC-21.1…21.4 | `lychee`/verificador de enlaces; prueba de contrato |
| H-23 | T-22 | AC-22.1…22.6 | API de sugerencias; banco de errores tipográficos; axe |
| H-24 | T-23, T-24 | AC-23.1…23.4, AC-24.1…24.7 | PHPUnit de API; inspección del carrusel |
| H-25 | T-26 | AC-26.1…26.6 | `curl` sin `cloudflareinsights`; Playwright de cookies |
| H-26 | T-24, T-25 | AC-24.2, AC-25.1…25.4 | PHPUnit (8.º ítem → 422); `useAvisoSalida` |
| H-27 | T-27 | AC-27.1, 27.2 | Prueba de DOM; `curl` |
| H-28 | T-28 | AC-28.1…28.5, 28.7…28.9 | Pruebas por componente; ADR |
| H-29 | T-29 | AC-29.1…29.3 | CI con 3 motores |
| H-30 | T-31 | AC-31.1…31.10 | Radicados, actas y `curl` a `gov.co` |
| H-31 | T-30 | AC-30.1…30.4 | `dig`, `curl -6`/`-4` |
| H-32 | T-33 | AC-33.1…33.3 | Prueba extremo a extremo |
| H-33 | T-34 | AC-34.1…34.4 | CI con `git diff --exit-code` |
| H-34 | T-35, T-07 | AC-35.1…35.4 | `ci.yml` en `main` |
| H-35 | T-32 | AC-32.1…32.4 | Search Console |
| H-36 | T-27 | AC-27.3 | `curl … \| grep -o '<h1' \| wc -l` = 1 |
| H-37 | T-34 | AC-34.5 | Script de coherencia tabla ↔ detalle |

**Cobertura del plan:** 44 hallazgos (H-15 y H-16 comparten fila), 32 tareas (T-00…T-06, T-07…T-15, T-20…T-30, T-31…T-35; no hay T-16…T-19), 161 criterios de aceptación más AC-10.5 (alternativo a AC-10.1…10.4). Todo hallazgo tiene al menos una tarea y un criterio verificable; la única tarea sin hallazgo es T-00 (preventiva). Ver §12.3.

---

## 11. Riesgos del plan y supuestos

| ID | Riesgo o supuesto | Efecto | Mitigación |
|---|---|---|---|
| R-1 | Jurídica / Oficial de datos no entrega los textos de las políticas | G3 y G4 bloqueadas; incumplimiento legal sigue (H-21) | Fijar fecha (D-4) y empezar T-20 **ya**; TIC prepara la estructura y la publicación para que el único pendiente sea el texto |
| R-2 | Un cambio de nginx o de acceso (T-02, T-05, T-15) rompe staging | Despliegue o login caídos | Probar primero en staging con otra sesión de administración abierta; reversión descrita en cada tarea |
| R-3 | La CSP rompe páginas (T-08) | Sitio o panel inutilizables | Modo `Report-Only` 7 días antes de aplicar; Playwright de humo (AC-08.4) |
| R-4 | El CMS (T-24) crece sin control | Meses de retraso | Partirlo en tres entregas (menú → noticias → carrusel), cada una con sus AC |
| R-5 | Un solo desarrollador (tú) | Sin revisión humana (D-2) y punto único de falla | Opción A de D-2 mientras tanto; documentar procedimientos (T-02/T-03) para que no vivan en tu cabeza |
| R-6 | Las estimaciones fallan (±50 %) | Calendario irreal | Úsalas para ordenar, no para comprometer fechas; reestimar al cerrar cada fase |
| R-7 | **Staging ≠ producción** (L-1) | Lo verificado puede no ser lo que se publique | Repetir §5 sobre producción antes de G4 |
| R-8 | Las normas cambiaron desde la consolidación del expediente (2026-06-03) | Exigencias desactualizadas | Jurídica valida la tabla de §7 antes de G3 |
| S-1 | Supongo que `origin/staging` @ `40496ad` es lo desplegado en staging | Algunos hechos de código podrían no estar desplegados (L-2) | Comparar con la etiqueta de la imagen desplegada |
| S-2 | Supongo que no hay más producción que lo que describe el expediente (el sitio actual es «SM CMS») | El alcance de T-30/T-31 podría cambiar | Confirmar el estado real de producción |

---

## 12. Cierre: qué sigue, qué no cubre este documento y consistencia

### 12.1 Primera semana propuesta (si apruebas G0)
1. **Día 1:** decidir D-1…D-6 (§8.6) y ejecutar T-01 (repo) y T-00 (opcional).
2. **Día 1-2:** T-02 y T-03 (servidor y GitHub), con tu acceso de administración. Arrancar en paralelo la petición a Jurídica (T-20, D-4).
3. **Día 2-3:** T-09 (dependencias → CI verde) y T-06 → T-04 (reglas de rama), en ese orden.
4. **Día 3-4:** T-05 y T-07, que son pequeñas y cierran tres hallazgos (H-06, H-07, H-08) con pruebas que hoy no existen.

**No empiezo nada de esto sin tu aprobación explícita.**

### 12.2 Lo que este documento NO cubre
- Los módulos 02-12 del expediente (solo se tocan donde condicionan el 01: derecho de petición, accesibilidad, seguridad).
- Accesibilidad WCAG real: la verifica el módulo 07 con herramientas; aquí solo anoté lo que vi.
- Rendimiento y carga; recuperación ante desastres; costos.
- El texto vigente de las normas (§7): validación jurídica pendiente.
- Producción, servidor real y administración de GitHub (§3.2): no verificados (el intento de acceso no fue posible). PHPStan y cobertura sí se midieron (§3.1.1), con las salvedades allí indicadas.

### 12.3 Verificación de consistencia del propio documento
Ejecuté estas comprobaciones sobre este mismo archivo el 2026-10-28 (script en memoria, no versionado):

| Comprobación | Resultado |
|---|---|
| Hallazgos definidos en §6 | 44 |
| Hallazgos presentes en la tabla de trazabilidad §10 | 44 (H-15 y H-16 en una sola fila) |
| Hallazgos que no resuelve ninguna tarea | 0 |
| Referencias a tareas (`T-xx`) sin definición | 0 |
| Tareas sin criterios de aceptación | 0 |
| Criterios referenciados en §10 que no existen | 0 (AC-10.5 existe, pero es alternativo a AC-10.1…10.4) |
| Criterios definidos que §10 no enlaza | AC-00.1 y AC-00.2 (T-00 no tiene hallazgo: es preventiva) |
| Filas de la matriz sin estado válido | 0 |
| Conteo de la matriz | 14 + 23 + 15 + 6 + 1 = 59 |
| Citas `fichero:línea` | 27 distintas; todas caen dentro del archivo, **salvo** `AuditoriaController.php:338`, `AuthService.php:149-150` y `FilesMediaService.php:175`, que aparecen en §4 como **citas erróneas que corregí** (no como evidencia) |

**Lo que esta comprobación NO prueba:** que cada cita diga lo que afirmo (solo que existe la línea). Esa lectura la hice a mano al auditar cada archivo; si encuentras una cita que no corresponde, es un error mío y debe corregirse.

---

## Anexo A · Comandos reproducibles de lo que ejecuté (sin secretos)

```bash
# --- GitHub (API pública, sin credenciales) ---
curl -s https://api.github.com/repos/SantanderAcuna/sede | python3 -c 'import sys,json;d=json.load(sys.stdin);print(d["private"],d["visibility"])'
for b in main staging develop; do curl -s https://api.github.com/repos/SantanderAcuna/sede/rules/branches/$b; done

# --- Staging (solo lectura; el único POST usa credenciales ficticias) ---
H=https://staging.santamarta.gov.co
curl -s -o /dev/null -w '%{http_code}\n' $H/sanctum/csrf-cookie            # 404 (H-06)
curl -s -H 'Accept: text/html' -o /dev/null -w '%{http_code}\n' $H/ruta-inexistente   # 404 con página propia
for s in terminos-y-condiciones-de-uso seguridad-y-privacidad proteccion-y-tratamiento-de-datos-personales uso-de-cookies derechos-de-autor-y-uso-sobre-contenidos; do
  curl -s -H 'Accept: text/html' $H/politicas/$s | grep -ci 'en preparaci'   # 1 cada una (H-21)
done
curl -s -H 'Accept: text/html' $H/ | grep -c cloudflareinsights             # beacon (H-25), según Accept
curl -s $H/api/v1/identidad/footer ; curl -s $H/api/v1/identidad/top-bar    # datos de identidad

# --- Backend (clon de origin/staging @ 40496ad; PHP 8.5.12, PostgreSQL 16) ---
cd backend && composer audit --locked --format=json
APP_KEY=base64:<clave-de-prueba> vendor/bin/phpunit --no-progress          # 240/240
vendor/bin/pint --test                                                     # pasa

# --- Sitio ---
cd sitio && npm audit --omit=dev --json ; npm ls --omit=dev --all undici node-forge
node scripts/trazabilidad.mjs                                              # 137 / 24 / 50 / 84 / 36 %
```

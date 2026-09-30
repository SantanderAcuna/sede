# Plan de construcción — Sede Electrónica de la Alcaldía Distrital de Santa Marta

> **Naturaleza del documento.** Este es el plan de ejecución. Cada afirmación sobre
> el estado del mundo lleva su evidencia; cada decisión lleva quién la tomó. Lo que
> todavía no está decidido está en la §20 y **no se implementa** hasta que se ratifique.
>
> **Versión:** 1.0 · **Fecha:** 2026-09-30 · **Estado:** propuesto, pendiente de ratificación
> **Construcción:** desde cero. El código anterior se descarta íntegro (§1.2).

---

## Índice

| § | Sección |
|---|---|
| 1 | Estado de partida y decisiones tomadas |
| 2 | Hallazgos que obligan a desviarse de los documentos |
| 3 | Arquitectura objetivo |
| 4 | Stack y versiones exactas |
| 5 | Repositorio, ramas y flujo de trabajo |
| 6 | Contrato primero: el OpenAPI como fuente de verdad |
| 7 | Backend: capas, dominios y seguridad de la aplicación |
| 8 | Panel de control (Vue 3 + TypeScript) |
| 9 | Sitio público (Nuxt 4 + TypeScript) |
| 10 | Módulos del CMS |
| 11 | Accesibilidad y conformidad |
| 12 | Integraciones externas |
| 13 | Infraestructura en DigitalOcean |
| 14 | Plano del host: endurecimiento del sistema operativo |
| 15 | Plano de los contenedores |
| 16 | Copias de seguridad y continuidad |
| 17 | CI/CD y despliegue automático |
| 18 | Observabilidad y operación |
| 19 | Pruebas y puertas de calidad |
| 20 | Fases, entregables y cronograma |
| 21 | Decisiones por ratificar |
| 22 | Riesgos y mitigaciones |
| 23 | Trazabilidad con el expediente |

---

## 1. Estado de partida y decisiones tomadas

### 1.1 Qué existe hoy

| Activo | Estado verificado | Evidencia |
|---|---|---|
| Expediente normativo | `docs/` — 8 documentos, 140 criterios, 122 con evidencia | `docs/trazabilidad.md` |
| Guía de desarrollo | `GUIA-MAESTRA-COMPLETA.md` — 3.312 líneas, 14 reglas absolutas | lectura íntegra |
| Guía de seguridad | `docs-security/` — 5 capítulos + 5 apéndices, ~55.553 palabras | lectura íntegra |
| Implementación anterior | `/var/www/sede` — 47 commits, **descartada** | §1.2 |
| Repositorio | `github.com/SantanderAcuna/sede` — privado, rama `master`, 3 ramas | `gh repo view` |
| Droplet | `198.199.89.119` — Ubuntu 26.04, 4 GB, acceso `root` verificado; **nada escuchando en el puerto 80** | `curl` y `ssh` |
| Dominio de staging | `staging.santamarta.gov.co` — ya detrás de Cloudflare | DNS → `2606:4700:…` (rango Cloudflare) |
| Sitio actual de la entidad | Drupal 7, Bootstrap 3.3.7, sin Kit gov.co | investigación §12 |

### 1.2 La implementación anterior se descarta

Existe un proyecto completo en `/var/www/sede` con 47 commits, 200 pruebas en verde y
una canalización de despliegue. **Se descarta íntegramente por decisión del titular:**
el código acumula deuda técnica y errores, y no debe reutilizarse ni modificarse.

Consecuencia directa: **este plan es de construcción completa**, no de continuación.
No hereda el contrato de 41 operaciones, ni las 59 tablas, ni la canalización. Todo se
construye de nuevo, con los documentos como única fuente.

> **Nota de higiene.** La carpeta `/var/www/sede` queda fuera de este proyecto: no se
> lee, no se copia y no se toca. Si en algún momento se necesita comparar, se hace
> contra los documentos, nunca contra ese código.

### 1.3 Decisiones tomadas por el titular

| # | Decisión | Valor |
|---|---|---|
| D-01 | Directorio de trabajo | `/home/sacunapolo/Documentos/sede` |
| D-02 | Construcción | Desde cero; el código anterior se descarta |
| D-03 | Superficie editorial | **Vue 3 + TS**. Se retira Filament y toda dependencia de Blade/Livewire |
| D-04 | Panel ciudadano | También en Vue 3 + TS (misma aplicación que el CMS, separado por rol y ruta) |
| D-05 | Sitio público | **Nuxt con SSR y TypeScript**, conservando todas las reglas de Vue 3 `<script setup>` de la guía maestra |
| D-06 | Rama principal | `main`. `master` se abandona |
| D-07 | Ramas antiguas | Se borran las tres; `main` nace huérfana |
| D-08 | Visibilidad del repositorio | Público |
| D-09 | Runtime PHP | **PHP-FPM 8.5, sin Octane** |
| D-10 | Versión de PHP | 8.5, sincronizada en imagen, CLI y CI |
| D-11 | Base de datos | **PostgreSQL 18** |
| D-12 | Proxy | nginx en su rama *stable* más reciente |
| D-13 | Reparto de planos | Host endurecido + **todo el stack en contenedores** |
| D-14 | Alcance de seguridad | Endurecimiento del SO, Cloudflare y copias de seguridad cifradas con simulacro |
| D-15 | Fuera de alcance | Wazuh/IDS, Prometheus/Grafana, app móvil Flutter, sidecar de IA |
| D-16 | Formato de la API | **Sobre plano** `{success, message, data, meta, errors}` con `application/json`. La regla R-24 se mantiene; JSON:API se descarta |
| D-17 | Imágenes base | Imágenes oficiales **fijadas por resumen**, con endurecimiento por configuración (§21.J) |
| D-18 | Rutas del dominio | Sitio Nuxt en `/`, panel en `/panel`, API en `/api/v1` |
| D-19 | Dominio de staging | `staging.santamarta.gov.co` |
| D-20 | Dominio de producción | **Sin decidir** (§21.A) |
| D-21 | Droplet | Nuevo, `198.199.89.119` |
| D-22 | Región y tamaño | `nyc3`, 2 vCPU / 8 GB / 160 GB NVMe |

### 1.4 Lo que este plan NO decide

Todo lo que depende de un tercero o de la entidad queda en la §21, con propuesta y
evidencia. Ninguna de esas decisiones se implementa antes de su ratificación.

---

## 2. Hallazgos que obligan a desviarse de los documentos

Los documentos son la fuente, pero **no son ejecutables tal cual**. Estos son los
hallazgos verificados que el plan resuelve. Cada uno se resuelve en la sección indicada.

### 2.1 Contradicciones entre documentos

| # | Contradicción | Documentos enfrentados | Resolución |
|---|---|---|---|
| H-01 | **JSON:API o sobre plano** | `capitulo-05.md` §3 exige `application/vnd.api+json` en toda respuesta y sin `success`/`message`; `GUIA-MAESTRA-COMPLETA.md:194` prohíbe expresamente ese media type | Decidido: sobre plano (D-16). Se aplica §6 |
| H-02 | **Motor primario** | `capitulo-04-parte-a.md:1,3,27` fija MySQL 8.0 como primario y PostgreSQL 16 como alternativa; `GUIA-MAESTRA-COMPLETA.md:522` fija PostgreSQL 18 | Decidido: PostgreSQL 18 (D-11) |
| H-03 | **Versión de PHP** | La guía de seguridad mezcla 8.3 (`apendice-C.md:13`) y 8.4 (`capitulo-05.md:3`); la guía maestra pide ^8.4; el proyecto real usaba ^8.3 | Decidido: 8.5 (D-10) |
| H-04 | **nginx** | La guía pide 1.29 mainline (`capitulo-03-parte-a.md:3`); el proyecto usaba 1.27 | Decidido: rama *stable* 1.30.5 (D-12) |
| H-05 | **Redis** | Todos los documentos fijan Redis 7; la versión estable actual es 8.10 | Propuesta §21.B |
| H-06 | **Docker** | `apendice-E.md:113` lo declara «opcional»; `capitulo-05.md` §6 y `deliverable.md:78` lo hacen obligatorio | Decidido: obligatorio (D-13) |
| H-07 | **Tailwind** | `GUIA-MAESTRA-COMPLETA.md:497,1888` lo instala como stack oficial; el criterio bloqueante **CAG-33** exige Bootstrap 5.0.2 y los tokens `--govcolor-*`, y el Kit gov.co sería destruido por el preflight de Tailwind | Se aplica **CAG-33**: Bootstrap 5.0.2 del Kit, sin Tailwind. ADR pendiente (§21.C) |
| H-08 | **CSS del Kit** | `GUIA-MAESTRA-COMPLETA.md` no menciona gov.co en 3.312 líneas; el expediente lo exige | Se aplica el expediente: Kit UI 9.2 vendorizado |
| H-09 | **Normativa colombiana** | La guía de seguridad no contiene **ni una sola** referencia a Ley 527/1999, Ley 1581/2012, Decreto 1078/2015, MinTIC ni GESI: su banner legal cita RGPD, LOPDGDD y NIS2, y su zona horaria es `Europe/Madrid` | El plan combina ambos: el expediente aporta la norma, la guía aporta la técnica |

### 2.2 Defectos que impiden ejecutar la guía tal cual

**Capítulo 02 — red y borde.** El ruleset publicado **deja el servidor inaccesible**:

- `deny tcp 0-79` (`capitulo-02.md:124`) anula el `allow tcp 22` (`:119`), porque el
  propio capítulo declara que el deny tiene **precedencia absoluta** (`:129`). Con esa
  configuración nadie entra por SSH, ni el operador.
- El capítulo se contradice sobre la política por defecto de nftables: `policy drop`
  (`:486`) frente a «asumimos ambos activos y `policy accept`» (`:618`).
- `flush ruleset` (`:458`) borra las tablas de UFW y las cadenas de Fail2Ban; el capítulo
  ordena aplicarlo en caliente (`:594`) sin fijar el orden de arranque.
- La acción de Fail2Ban contra Cloudflare autentica con **Global API Key** (`:890-891`)
  mientras el propio capítulo prohíbe usarla (`:910-911`) y pide un token con alcance.
- El capítulo **no menciona Docker ni una vez**: no cubre el bypass de UFW por las reglas
  de iptables que Docker escribe, que es el problema central de nuestro D-13.

**Capítulo 03 — nginx y TLS.** La configuración publicada **no arranca**:

- `http2 on;` aparece **duplicado** en cada server block (`:175-178`, `:271-274`) →
  `nginx: [emerg] "http2" directive is duplicate`. El propio `nginx -t` de la auditoría
  del capítulo fallaría.
- `rate-limit.conf` se incluye **dentro** de un `server` (`:200`) pero contiene
  `limit_req_zone` y `limit_conn_zone`, que sólo son válidas en `http`.
- `more_clear_headers` se usa sin el módulo `headers-more`, que ningún paso instala.
- **`security-headers.conf` nunca se incluye en ningún server block**, de modo que
  ninguna cabecera de la §3 llega al cliente: el A+ de securityheaders.com es inalcanzable.
- Certbot emite un solo linaje (`-d api -d app`) mientras el server block del frontend
  apunta a otro directorio de certificados: nginx no arrancaría.

**Capítulo 04 — capa de datos.** Contradicciones que hay que resolver antes de fijar nada:

- `skip-networking` activo (`:100`) frente al túnel a `127.0.0.1:3306` (`capitulo-04-parte-b.md:184`).
- `require_secure_transport = ON` (`:114`) frente a la ruta de administración documentada
  con `--ssl-mode=DISABLED` (`capitulo-04-parte-b.md:230`).
- La rotación «7/30/365» se anuncia (`:356`) pero **sólo se implementa el borrado a 7 días**.
- No hay copia fuera del droplet en ninguna parte del capítulo.
- PostgreSQL se documenta como alternativa y **le falta todo**: endurecimiento, roles de
  menor privilegio, cifrado en reposo real, copia de seguridad y simulacro de restauración.
- La regla «bind `127.0.0.1`» **no aísla nada en Docker**: dentro de una red puente el
  contenedor debe escuchar en `0.0.0.0` y el aislamiento se logra **no publicando puertos**.

**Capítulo 05 — aplicación y CI/CD.** Defectos que romperían la producción:

- El `Dockerfile` publica `.env.production` y ejecuta `key:generate --force` **durante la
  compilación** (`:1969-1970`), y el `entrypoint` sólo crea el `.env` si no existe
  (`:2148`): los secretos nunca llegarían y la `APP_KEY` quedaría **horneada en la imagen**.
- El `.dockerignore` excluye `.env.*` sin re-incluir `.env.production`, así que ese `cp`
  rompería el propio build.
- Dos pools de PHP-FPM escuchando en el mismo `0.0.0.0:9000`.
- `pm.max_children = 50` con `memory_limit = 256M` sobre un contenedor limitado a 512 MB:
  **OOM garantizado** (12,8 GB sobre 8 GB de máquina).
- `read_only: true` sin `tmpfs` para `bootstrap/cache`, que el `entrypoint` escribe.
- **Dos arquitecturas incompatibles en el mismo capítulo**: §5.5 despliega por `rsync` +
  `systemctl reload php8.4-fpm` en el host, mientras §6 lo hace todo con contenedores.
- El OIDC a DigitalOcean **no está configurado**: el capítulo lo anuncia pero el despliegue
  real usa clave SSH, `DROPLET_ID` y `CF_API_TOKEN` estáticos.

**Apéndices.** La tabla maestra (B) tiene 134 filas y **omite diez secciones que promete**,
entre ellas PostgreSQL, PHP-FPM, nginx, Certbot, Fail2Ban y Docker. El checklist (C) tiene
148 ítems y **dos de ellos son mutuamente excluyentes** (`skip-networking` frente a exigir
un listener en 3306). Las referencias (D) no llevan fecha de acceso pese a prometerlo.
El glosario (E) define «Docker: opcional» y afirma que Argon2id se activa con el driver
`argon`, que es Argon2**i**.

### 2.3 Defectos del expediente

| # | Defecto | Resolución |
|---|---|---|
| H-10 | `ACC-001`…`ACC-008`, `RN-01`…`RN-03` y `RT-01`…`RT-05` **se citan pero nunca se definen**. `RT-02` significa dos cosas distintas según la sección | Propuesta §21.D |
| H-11 | Disponibilidad **95 %** (FUN-053) frente a **99 %** (§6.8). RPO ≤ 1 h frente a ≤ 24 h | Propuesta §21.E |
| H-12 | Componentes del Kit: **24 declarados** frente a **31 enumerados**. Iconos: 499 frente a 768 | Se audita contra el bundle real vendorizado (§11) |
| H-13 | Tres URLs distintas del CDN del Kit, **una probadamente rota** | Se vendoriza el Kit en el repositorio (§9.4) |
| H-14 | Transparencia **no tiene lista de contenidos obligatorios** en el expediente | Se adopta el Anexo 2 de la Resolución 1519 de 2020, confirmado contra el sitio real de la entidad (§12.10) |
| H-15 | **FUN-023** dice que los trámites en línea direccionan a gov.co, y choca con todo el diseño propio de radicación, pagos y notificaciones | **Resuelto por la norma** (§12.3): el portal redirige a la sede, no al contrario. `FUN-023` queda como defecto de redacción |
| H-16 | El mapa de trazabilidad de la Sección 6 tiene los vínculos cruzados mal en una decena de casos: TLS atribuido a SEG-002 (es SEG-001), cabeceras a SEG-006 (que es el pie) | La trazabilidad se genera desde el contrato y las pruebas (§6.6) |
| H-17 | Cinco políticas obligatorias en el pie (SEG-006) frente a tres (FUN-005) | Se adoptan las cinco de SEG-006 |
| H-18 | El Kit incumple WCAG AA consigo mismo: su *toast* verde rinde 3,61:1 | La sede no reproduce esa combinación; se usan tokens con contraste verificado (§11.5) |

### 2.4 Lo que los documentos no cubren y el plan debe fijar

Estos huecos se cierran en este plan, con la fuente oficial de cada tecnología y un ADR:

- Valores literales de la configuración de nginx para **tres superficies en un origen**
  (el capítulo 03 sólo contempla dos server blocks con SPA estática, sin SSR).
- **CSP con nonce sobre SSR**: el capítulo 05 remite al 03 y el 03 sólo describe un ejemplo.
- Paginación, `per_page` máximo y forma de los errores de validación en el sobre plano.
- Endurecimiento de **PostgreSQL 18** en contenedor.
- Copia de seguridad **fuera del droplet** con la rotación 7/30/365 implementada de verdad.
- Custodia de la frase de paso de cifrado: los documentos la dejan **dentro del droplet**,
  que es exactamente lo contrario de lo que ellos mismos exigen.
- Reconciliación entre UFW, nftables y las reglas que Docker escribe en iptables.
- Reglas de egreso que **no bloqueen** los servicios que sí necesitamos.

---

## 3. Arquitectura objetivo

### 3.1 Vista general

```
                              Internet
                                 │
                                 ▼
                    ┌────────────────────────┐
                    │   Cloudflare (edge)    │
                    │  WAF · DDoS L7 · Bot   │
                    │  Full (Strict) · AOP   │
                    └───────────┬────────────┘
                                │ HTTPS 443
                                ▼
        ┌───────────────────────────────────────────────────┐
        │  Droplet nyc3 · 2 vCPU · 8 GB · 160 GB · Ubuntu   │
        │  198.199.89.119                                   │
        │                                                   │
        │  ── PLANO DEL HOST ──────────────────────────     │
        │  DO Cloud Firewall → nftables → UFW → Fail2Ban    │
        │  sshd · sysctl · PAM · auditd · AppArmor          │
        │  AIDE · ClamAV · unattended-upgrades              │
        │  Docker Engine + Compose                          │
        │                                                   │
        │  ── PLANO DE CONTENEDORES ───────────────────     │
        │                                                   │
        │  red edge                                         │
        │  ┌──────────────┐                                 │
        │  │ nginx 1.30.5 │  único que publica 80/443       │
        │  └──┬────┬────┬─┘                                 │
        │     │    │    │        red backend                │
        │     │    │    └──────────────┐                    │
        │     │    │                   │                    │
        │  ┌──▼──┐ │            ┌──────▼──────┐             │
        │  │ app │ │            │    nuxt     │             │
        │  │PHP  │ │            │ Node 24 SSR │             │
        │  │8.5  │ │            └─────────────┘             │
        │  │ FPM │ │                                        │
        │  └──┬──┘ │                                        │
        │     │    │   ┌──────────┐  ┌───────────┐          │
        │     │    │   │ horizon  │  │ scheduler │          │
        │     │    │   └──────────┘  └───────────┘          │
        │     │    │                                        │
        │     │    │        red data (internal: true)       │
        │     │    │   ┌──────────┐  ┌───────────┐          │
        │     └────┼──▶│ postgres │  │   redis   │          │
        │          │   │    18    │  │     8     │          │
        │          │   └──────────┘  └───────────┘          │
        │          │   ┌───────────────────────────┐        │
        │          └──▶│  almacenamiento (S3)      │        │
        │              └───────────────────────────┘        │
        └───────────────────────────────────────────────────┘
```

### 3.2 Superficies y rutas

Un solo origen, un solo certificado, un solo lugar donde se aplican las cabeceras.
Es lo que exige el artículo 14 inciso 1 del Decreto Ley 2106 de 2019 (una sola sede por
autoridad) y la obligación O-01 (dominio canónico único).

| Ruta | Servicio | Tipo | Autenticación |
|---|---|---|---|
| `/` | `nuxt` | SSR de Node | Pública |
| `/panel` | `nginx` (disco) | SPA estática | Sanctum, por ruta |
| `/api/v1/*` | `app` | PHP-FPM | Sanctum, por ruta |
| `/storage/*` | `app` | Archivos privados con URL firmada | Firma temporal |
| `/health` | `app` | Sonda de vida | Sólo red interna |
| `/ready` | `app` | Sonda de preparación | Sólo red interna |

### 3.3 Por qué esta forma

- **nginx es el único punto de entrada.** Sirve la SPA desde disco —sin tocar PHP— y hace
  proxy al servidor de Nuxt. Un solo contenedor publica puertos.
- **El sitio público es SSR** porque su contenido debe ser rastreable e indexable sin
  ejecutar JavaScript, y porque las páginas de transparencia y trámites se miden con
  herramientas que no ejecutan la aplicación.
- **Dos aplicaciones frontend separadas**: el panel (SPA autenticada, sin requisitos de
  posicionamiento) y el sitio (SSR, con posicionamiento y accesibilidad como requisito).
  Comparten las reglas de Vue 3 y el cliente HTTP, no el ciclo de vida.
- **Las tres redes están separadas** según las convenciones canónicas: `edge` (nginx),
  `backend` (aplicación, SSR y workers) y `data` (base, caché y almacenamiento), esta
  última declarada `internal: true` para que ningún servicio de datos sea alcanzable
  desde fuera de la pila.

---

## 4. Stack y versiones exactas

Todas las versiones se verificaron **hoy, 2026-09-30**, contra el registro oficial
correspondiente. Las imágenes se fijan **por resumen** (`@sha256:`), nunca por etiqueta
móvil: una etiqueta puede reapuntarse y con ella cambiar todo el contenido de la imagen.

### 4.1 Sistema y ejecución

| Componente | Versión | Imagen / fuente | Nota |
|---|---|---|---|
| Sistema operativo | Ubuntu 24.04 LTS (noble) | droplet | Soporte hasta 2029 + 5 años ESM |
| Docker Engine + Compose v2 | rama estable | repositorio oficial de Docker | No el paquete de Ubuntu |
| nginx | **1.30.5** (rama *stable*) | `nginx:1.30.5-alpine` | Publicada el 15-sep-2026; corrige la CVE-2026-90439 |
| Docker Engine | **29.8.1** | repositorio oficial | Verificado en el servidor; Compose **5.5.1** |
| PHP | **8.5** | `php:8.5-fpm-alpine` | Soportado por Laravel 13 (8.3–8.5) |
| Composer | 2.x | `composer:2` | Sólo en la etapa de compilación |
| Node | **24** (LTS) | `node:24-alpine` | Nuxt 4 exige `^22.19`, `^24.11` o superior |

> **nginx no tiene LTS.** Tiene rama *stable* (menor par, hoy 1.30.x) y rama *mainline*
> (menor impar, hoy 1.31.x). «El más actual pero estable» significa, en sus términos,
> **1.30.5**. La guía pedía 1.29 mainline, que quedó fuera de soporte en abril de 2026.

### 4.2 Backend

| Componente | Versión | Nota |
|---|---|---|
| Laravel | **13.34.0** (`^13.0`) | Publicado el 17-mar-2026; correcciones hasta Q3 2027 |
| PostgreSQL | **18.6** | `postgres:18-alpine` |
| Redis | **8.10** | `redis:8-alpine` — propuesta §21.B |
| PHPUnit | 12.x | Suite del backend |
| Larastan / PHPStan | nivel **8** | Análisis estático |
| Pint | 1.x | Formato, con `--test` en la puerta |
| Sanctum | 4.x | Autenticación de la SPA y de la API |
| Horizon | 5.x | Colas sobre Redis |
| Spatie Permission | 8.x | Roles y permisos |
| Spatie Media Library | 11.x | Biblioteca de medios |
| Spatie Backup | 10.x | Orquestación de copias |
| Flysystem S3 | 3.x | Almacenamiento de objetos |
| Google2FA | 9.x | Segundo factor de los funcionarios |
| DomPDF / league/csv | 3.x / 9.x | Certificados y exportaciones abiertas |

Extensiones de PHP requeridas: `pdo_pgsql`, `pgsql`, `redis`, `intl`, `opcache`,
`zip`, `gd`, `bcmath`, `exif`, `pcntl`.

### 4.3 Frontend

| Componente | Versión | Panel | Sitio |
|---|---|---|---|
| Vue | **3.5.43** | ✔ | ✔ (vía Nuxt) |
| TypeScript | **7.0.2** | ✔ | ✔ — verificar compatibilidad de `vue-tsc` (§20.F0.3) |
| Vite | **8.3.1** | ✔ | ✔ (vía Nuxt) |
| Nuxt | **4.5.2** | — | ✔ |
| Pinia | **4.0.3** | ✔ | ✔ |
| Vue Router | **5.3.1** | ✔ | ✔ |
| Zod | **4.6.5** | ✔ | ✔ |
| Axios | **1.20.0** | ✔ | ✔ |
| TanStack Query | 5.104 | ✔ | ✔ |
| vue3-toastify | 0.2.9 | ✔ | — |
| Vitest | 5.0.2 | ✔ | ✔ |
| Playwright | 1.63 | ✔ | ✔ |
| axe-core | 4.13 | ✔ | ✔ |
| openapi-typescript | 7.13 | ✔ | ✔ |

Se descartan `@tanstack/vue-table` y `@tanstack/vue-form`: duplicarían los componentes
de tabla y de formulario del Kit gov.co, que son la base de la conformidad de diseño.

### 4.4 Contrato y herramientas

| Herramienta | Versión | Uso |
|---|---|---|
| OpenAPI | **3.1.0** | Formato del contrato |
| `openapi-spec-validator` | actual | Validez estructural |
| Redocly CLI | 2.55 | Estilo y lint |
| Prism | 4 | Servidor simulado |
| `openapi-typescript` | 7.13 | Tipos del frontend |

### 4.5 Regla de sincronía de versiones

> **Un solo PHP en todo el proyecto.** La misma versión en la imagen del contenedor, en la
> imagen de compilación, en la canalización, en el análisis estático y en la documentación.
> Si `composer.json` declara `^8.5` y la imagen usa otra cosa, la puerta de integración
> falla. Lo mismo aplica a Node y a PostgreSQL.

Esto es verificable y automático: un paso de la canalización compara la versión declarada
en `composer.json` y en `package.json` con la que reporta el contenedor, y falla si difieren.

---

## 5. Repositorio, ramas y flujo de trabajo

### 5.1 Estructura

```
sede/
├── backend/              Laravel 13 — API REST
├── panel/                Vue 3 + TypeScript + Vite — CMS editorial y panel ciudadano
├── sitio/                Nuxt 4 + TypeScript — sitio público con SSR
├── contract/             openapi.yaml — fuente de verdad del contrato HTTP
├── docker/
│   ├── app/              Dockerfile y configuración de PHP-FPM
│   ├── panel/            Dockerfile del panel (compilación de la SPA)
│   ├── sitio/            Dockerfile del sitio (servidor SSR de Nuxt)
│   ├── nginx/            Configuración del punto de entrada
│   ├── postgres/         Configuración e inicialización
│   ├── redis/            Configuración y ACL
│   └── backup/           Imagen y scripts de copia de seguridad
├── deploy/
│   ├── droplet/          preparar.sh · certificado.sh · despliegue-remoto.sh
│   └── plantilla.env     Plantilla del entorno, renderizada en cada despliegue
├── scripts/              Puertas de verificación ejecutables
├── docs/                 Expediente normativo
├── docs-security/        Guía de seguridad
├── docs/adr/             Registro de decisiones de arquitectura
├── .github/workflows/    Integración continua y despliegue
├── compose.yaml          Pila de producción
├── compose.override.yaml Pila de desarrollo
├── Makefile              Puertas y tareas
└── plan.md               Este documento
```

**Desviación declarada de la regla absoluta 3.** La guía fija dos carpetas hermanas
(`backend/` y `frontend/`). Aquí hay **tres aplicaciones** porque el titular pidió dos
frontends con ciclos de vida distintos: el panel (SPA autenticada) y el sitio (SSR
público). Se registra como ADR y se documenta el motivo: un solo `frontend/` obligaría a
que el mismo proceso sirviera una aplicación autenticada y un sitio indexable, con
políticas de caché, de cabeceras y de accesibilidad incompatibles entre sí.

### 5.2 Ramas

| Rama | Propósito | Protección |
|---|---|---|
| `main` | Producción | Requiere PR + revisión + CI verde |
| `staging` | Pruebas y aceptación | Requiere PR + CI verde |
| `develop` | Integración | Requiere PR + CI verde |

Tipos de rama: `feat/`, `fix/`, `refactor/`, `docs/`, `test/`, `chore/`, `hotfix/`.
El nombre sigue el patrón `{prefijo}/{modulo}` en kebab-case: `feat/cms-contenidos`.

Flujo: `feat/{modulo}` desde `develop` → PR a `develop` → promoción a `staging` →
despliegue automático y aceptación → promoción a `main` → despliegue a producción.

> **Al pasar el repositorio a público (D-08) se desbloquea la protección de ramas.**
> Con el repositorio privado y el plan gratuito, GitHub responde `403` a cualquier intento
> de configurarla. Una vez público, se configuran: revisores requeridos por entorno,
> política de ramas para los entornos `staging` y `production`, y aprobaciones previas al
> despliegue a producción.

### 5.3 Reglas de Pull Request

- **El PR es la unidad de revisión, no de código.** Debe poder revisarse en 30 minutos.
- **Más de 400 líneas modificadas obliga a dividir** en dos o más PRs encadenados.
- Cada PR declara: qué cambia, qué criterio del expediente acredita, cómo se verificó y
  qué queda fuera de su alcance.
- Ningún PR se fusiona con una puerta en rojo.
- Los mensajes de confirmación describen el efecto, no el archivo tocado.

### 5.4 Inicio del repositorio

1. Conceder acceso de escritura al directorio de trabajo.
2. Inicializar el repositorio y configurar el remoto.
3. Crear `main` **huérfana**, vaciar el árbol y fundar el proyecto nuevo.
4. Publicar `main` y fijarla como rama por defecto.
5. Crear `develop` y `staging` desde `main`.
6. **Borrar las tres ramas antiguas** (`master`, `develop`, `staging`). Al desaparecer,
   los 47 commits quedan inalcanzables.
7. ~~Pasar el repositorio a público~~ — **ya está hecho**: verificado el 2026-09-30, el
   repositorio es público y la cuenta tiene permisos de administración. Falta configurar
   la protección de ramas, que ya es posible.
8. Recrear los entornos `staging` y `production` con sus secretos y aprobaciones.

---

## 6. Contrato primero: el OpenAPI como fuente de verdad

### 6.1 Principio

El contrato se escribe **antes** que el código. Backend y frontend no se acoplan entre sí:
los dos se acoplan al mismo `contract/openapi.yaml`. Ninguna línea de implementación existe
sin una operación declarada.

### 6.2 Forma de la respuesta

**Sobre plano** (D-16), con media type `application/json`. La regla **R-24** de la guía
maestra rige y `application/vnd.api+json` queda prohibido.

```json
{
  "success": true,
  "message": null,
  "data": { "id": 1, "type": "tramites", "slug": "certificado-de-residencia" },
  "errors": null
}
```

Colección paginada: `meta` con las siete claves del paginador y `links` con cuatro.

```json
{
  "success": true,
  "message": null,
  "data": [ ],
  "meta": {
    "current_page": 1, "from": 1, "last_page": 3,
    "path": "https://…/api/v1/tramites", "per_page": 15, "to": 15, "total": 42
  },
  "links": { "first": "…?page=1", "last": "…?page=3", "prev": null, "next": "…?page=2" },
  "errors": null
}
```

Error y error de validación:

```json
{ "success": false, "message": "El recurso no fue encontrado.", "data": null, "errors": null }
{ "success": false, "message": "Error de validación.", "data": null,
  "errors": { "nombre": ["El campo nombre es obligatorio."] } }
```

### 6.3 Convenciones de autoría

| Regla | Convención |
|---|---|
| Media type | `application/json` en toda respuesta y toda petición |
| `id` | Entero, nunca cadena |
| `type` | Plural en kebab-case, declarado con `const` |
| `readOnly` | Campos autogenerados, excluidos del cuerpo de la petición |
| Actualización | Verbo `PATCH`, nunca `PUT` |
| Paginación | Parámetros `page` y `per_page`; `meta` y `links` en el nivel superior |
| Errores | Objeto `errors` con clave por campo: `{"campo": ["mensaje"]}` |
| `per_page` máximo | 100; por encima responde `422` |

### 6.4 Esquemas

Cinco esquemas compartidos: `ApiEnvelope`, `PaginatedEnvelope`, `PageMeta`,
`CollectionLinks` y `ApiError`. Por cada recurso se declaran **tres** esquemas —no cuatro—:
`<Recurso>Item`, `<Recurso>Collection` y `<Recurso>Input`.

Cada operación lleva dos extensiones propias:

- `x-status`: `pending` o `implemented`.
- `x-criterios`: los códigos del expediente que acredita (`FUN-021`, `SEG-006`…).

### 6.5 Ciclo de trabajo

```
1. Diseñar la operación en contract/openapi.yaml
2. Validar:  openapi-spec-validator  +  redocly lint
3. Levantar el simulador:
      docker run --rm -p 4010:4010 -v $PWD/contract/openapi.yaml:/tmp/openapi.yaml \
        stoplight/prism:4 mock -h 0.0.0.0 /tmp/openapi.yaml
4. Generar los tipos del frontend:
      npx openapi-typescript contract/openapi.yaml -o src/types/api.d.ts --read-write-markers
5. Implementar en paralelo, backend y frontend, contra el mismo contrato
6. Verificar la deriva con la prueba de contrato
```

**No se avanza al paso 5 hasta que el simulador responda correctamente.**

### 6.6 Verificación de deriva

Una prueba compara el contrato con las rutas realmente registradas por Laravel y falla si
sobra o falta una operación. Los tipos generados **no se versionan**: se generan en cada
compilación y la canalización falla si quedan desactualizados.

La matriz de trazabilidad de `docs/trazabilidad.md` se **genera**, no se escribe a mano:
se produce desde las extensiones `x-criterios` del contrato, los nombres de las pruebas y
las vistas. Así deja de reproducir los vínculos cruzados erróneos del expediente (H-16).

---

## 7. Backend: capas, dominios y seguridad de la aplicación

### 7.1 Capas

```
Controller → FormRequest → Service → Repository → Model
                 ↑            ↑           ↑
              Contracts ──────┴───────────┘
```

| Capa | Responsabilidad | Prohibición |
|---|---|---|
| Controller | Traducir HTTP a una llamada de servicio y devolver el sobre | No contiene lógica de negocio |
| FormRequest | Validar y autorizar la entrada | No escribe en la base |
| Service | Reglas de negocio y transacciones | No recibe `Request` en su firma |
| Repository | Acceso a datos, detrás de un contrato | No conoce HTTP |
| Model | Relaciones, ámbitos, conversiones | No consulta servicios |

### 7.2 Estructura de `backend/app/`

```
app/
├── Console/Commands/      Comandos programados
├── Contracts/
│   ├── Repositories/      Interfaz por agregado
│   ├── Services/          Interfaz por caso de uso
│   └── Integraciones/     Interfaz por proveedor externo (§12)
├── Enums/                 Estados, tipos, modalidades
├── Exceptions/            Excepciones de dominio
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/        Cabeceras, métodos permitidos, correlación
│   ├── Requests/          Un FormRequest por operación de escritura
│   └── Resources/         Un Resource por recurso del contrato
├── Jobs/                  Trabajo en cola
├── Models/
├── Policies/              Una por agregado
├── Providers/
├── Repositories/Eloquent/
├── Rules/                 Reglas de validación propias
├── Services/
└── Support/               ApiResponse, utilidades de sede y de seguridad
```

### 7.3 Reglas obligatorias por módulo

La guía maestra exige **25 puntos más 7 de contrato** antes de dar un módulo por terminado.
Se aplican tal cual, y se verifican en la revisión del PR. Los principales:

| # | Regla |
|---|---|
| 1 | `declare(strict_types=1)` en **todo** archivo PHP |
| 2 | Ninguna clave foránea sin relación declarada en el modelo |
| 3 | Toda relación declara su tipo de retorno |
| 4 | Migraciones explícitas: nunca `->change()` |
| 5 | Clave foránea con `onDelete` explícito |
| 6 | Dinero en `decimal`, jamás en `float` |
| 7 | Resource declarativo con los campos planos del contrato |
| 8 | Update con `sometimes` y coherencia de tipos |
| 9 | `StoreRequest` cubre todos los campos del contrato menos los autogenerados |
| 10 | `authorize()` delega en la Policy |
| 11 | Service implementa las reglas; el Controller no |
| 12 | Campos autogenerados no están en `$fillable` |
| 13 | Policy con `viewAny`, `create`, `update` y `delete` |
| 14 | `per_page > 100` responde `422` |
| 15 | Datos personales enmascarados salvo permiso expreso |

### 7.4 El sobre de respuesta

`App\Support\Api\ApiResponse` con ocho métodos, y **obligatorio** usarlos:

| Método | Resultado |
|---|---|
| `ok($data, $message)` | `200` con el sobre |
| `created($data, $message)` | `201` |
| `error($message, $status, $errors)` | Cualquier error |
| `notFound()` · `unauthorized()` · `forbidden()` | `404` · `401` · `403` |
| `validationError($errors)` | `422` con `errors` por campo |
| `tooManyRequests()` | `429` |
| `paginated($paginator)` | `200` con `meta` y `links` |

Se registran en el manejador de excepciones de `bootstrap/app.php` para que **ninguna**
respuesta de error escape del sobre.

### 7.5 Códigos de estado

| Estado | Uso |
|---|---|
| `200` | Lectura y actualización con cuerpo |
| `201` | Recurso creado |
| `204` | Eliminación sin cuerpo |
| `401` | Sin autenticar |
| `403` | Autenticado sin autorización |
| `404` | No encontrado |
| `409` | Conflicto de estado |
| `422` | Error de validación |
| `429` | Límite de tasa excedido |

### 7.6 Autorización

- Una **Policy por agregado**, con respuestas `Response::allow()` y `Response::deny()`.
- Permisos verificados con `$user->can(...)`, nunca con `hasRole()` dentro de una Policy.
- El rol `super-admin` se resuelve con `Gate::before()` en `AppServiceProvider::boot()`,
  **devolviendo `null`** —nunca `false`— cuando el usuario no lo tiene.
- Roles separados: `ciudadano`, `funcionario`, `editor`, `revisor`, `publicador`,
  `administrador`, `super-admin`. La segregación de funciones es requisito: **quien
  redacta no publica**.

### 7.7 Identidad, sesión y segundo factor

- **Ciudadanos**: Sanctum SPA con cookie de sesión (`HttpOnly`, `Secure`, `SameSite=Lax`),
  más el ingreso federado por los Servicios Ciudadanos Digitales (§12.2).
- **Funcionarios**: segundo factor **obligatorio** (`pragmarx/google2fa`, TOTP, ventana ±1,
  cinco códigos de recuperación de un solo uso). Es un requisito de seguridad crítica, no
  una opción configurable.
- Bloqueo de cuenta tras **cinco intentos fallidos en cinco minutos** (`429`), y diez
  intentos en el día.
- Contraseñas con `argon2id` y mínimo de 15 caracteres; el mínimo legal es 8 y se
  recomienda 15.
- Cierre de sesión revoca el token del servidor, no sólo la cookie del navegador.

### 7.8 Middleware propio

| Middleware | Función |
|---|---|
| `CorrelacionPeticion` | Genera o respeta `X-Request-Id` y lo devuelve en toda respuesta (SEG-013) |
| `CabecerasSeguridad` | Cabeceras que la aplicación debe emitir por sí misma |
| `MetodosHttpPermitidos` | Rechaza `PUT`, `TRACE` y `OPTIONS` no previstos (SEG-007) |

### 7.9 Límites de tasa

| Ámbito | Límite |
|---|---|
| API autenticada | 120 por minuto por usuario |
| API anónima | 30 por minuto por IP |
| Inicio de sesión | 5 por minuto por `correo+IP`; 20 por minuto por IP |
| Radicación y PQRSD | 10 por hora por IP |
| Exportaciones y portabilidad | 2 por minuto por usuario |
| Consulta pública por radicado | 10 por minuto por IP, con captcha |

### 7.10 Registro y auditoría

- Canal de registro **dedicado** (`security`), con formato estructurado y rotación de 30
  archivos. Prohibido escribir el canal de seguridad en `stderr` únicamente.
- Cinco eventos obligatorios como mínimo: entrada correcta, intento fallido, intento contra
  cuenta bloqueada, acceso a datos propios y cambio sobre datos personales. **Nunca se
  registra la contraseña ni el token**.
- `owen-it/laravel-auditing` sobre las entidades de negocio y `spatie/activitylog` sobre la
  superficie editorial: quién cambió qué, cuándo y desde dónde.
- Conservación: **12 meses** en caliente. Es un requisito, no una preferencia.
- Los datos personales se enmascaran en las respuestas salvo permiso expreso: documento,
  correo y teléfono nunca viajan completos.

### 7.11 Archivos y documentos

- Validación por tipo real (no por extensión), tamaño máximo y nombre generado con UUID.
- Almacenamiento **privado** por omisión; la descarga pública exige URL firmada con
  caducidad corta.
- Los documentos del expediente se sellan con huella de integridad al cerrarse, y existe un
  verificador público por código.
- La subida se hace por el mismo origen; nunca se expone un bucket.

### 7.12 Colas y tareas programadas

- **Horizon** sobre Redis, con colas separadas por criticidad: `notificaciones`, `pagos`,
  `documentos`, `por-defecto`.
- Programador con tareas idempotentes y registro de cada ejecución:
  publicar contenidos programados, revisar contenidos vencidos, recordar revisiones,
  conciliar pagos, purgar tokens caducados, sondear disponibilidad y verificar la copia
  de seguridad.

---

## 8. Panel de control (Vue 3 + TypeScript)

### 8.1 Alcance

Una sola aplicación en `panel/` con **dos áreas** separadas por rol y por ruta:

| Área | Ruta | Para quién | Qué hace |
|---|---|---|---|
| Editorial y administración | `/panel/*` | Funcionarios | El CMS completo (§10) |
| Autogestión | `/panel/mi-cuenta/*` | Ciudadanos | Mis radicados, notificaciones, pagos, documentos y datos |

Ambas se sirven desde el mismo origen y comparten el cliente HTTP, el guardia de rutas y
los componentes de interfaz. La separación es de autorización, no de origen.

### 8.2 Reglas de la guía maestra que se conservan

El titular pidió expresamente mantener las reglas de Vue 3 de la guía maestra. Se aplican
íntegras, también en el sitio:

- `<script setup lang="ts">` y Composition API. **Options API prohibido.**
- TypeScript estricto: **cero `any`**.
- Un `<template>` y un `<script setup>` por archivo; `<style scoped>` siempre, nunca global.
- `:key` obligatorio en todo `v-for`; `v-if` y `v-for` nunca juntos en el mismo elemento.
- Nombres de componente de varias palabras; `defineProps` con tipo, no con arreglo.
- Se prefiere `defineModel()` a `props` más `emit` para los enlaces de formulario.
- Estados de carga, error y vacío explícitos en toda vista que consume datos.
- Nada de `localStorage` para el token: la sesión vive en una cookie `HttpOnly`.
- Prohibido `v-html` con datos de usuario; cuando haga falta HTML enriquecido del CMS,
  se depura con DOMPurify y se registra la política.
- Prohibidos `new Function()` y el uso de `window.location` para navegar.

### 8.3 Estructura de `panel/src/`

```
src/
├── components/
│   ├── govco/       Envoltorios del Kit UI 9.2 (§11.3)
│   └── sede/        Componentes propios reutilizables
├── composables/     Lógica reutilizable con estado
├── layouts/         Disposiciones (público, panel, autenticación)
├── router/          Rutas y guardias
├── schemas/         Esquemas Zod espejo del contrato
├── services/        Cliente HTTP y un servicio por recurso
├── stores/          Pinia: sesión, preferencias, notificaciones
├── types/           Tipos generados desde el contrato
└── views/
    ├── admin/       Área editorial
    ├── cuenta/      Área de autogestión
    └── acceso/      Entrada, recuperación y segundo factor
```

### 8.4 Cliente HTTP

Instancia única de Axios con `withCredentials`, `Accept` y `Content-Type` en
`application/json`, tiempo de espera de 15 s y dos interceptores:

- Desempaqueta el sobre y devuelve `data`, o lanza un error tipado con `message` y `errors`.
- Sobre `401` limpia la sesión y lleva a la entrada; sobre `403` lleva a «sin permiso»;
  sobre `429` avisa sin reintentar; sobre `5xx` muestra el identificador de correlación para
  que el ciudadano pueda reportarlo.

### 8.5 Guardias y permisos

| Situación | Comportamiento |
|---|---|
| Autenticado que visita la entrada | Redirige a su panel |
| No autenticado en ruta protegida | Redirige a la entrada **del panel**, no al sitio público |
| Autenticado sin el permiso de la ruta | Vista de «sin permiso», no un error crudo |
| Ciudadano en ruta editorial | «Sin permiso» |
| Funcionario sin segundo factor | Se le exige completarlo antes de continuar |

Los botones y las acciones se ocultan o deshabilitan según el permiso, y **el backend
vuelve a verificar el mismo permiso**: la interfaz no es un control de seguridad.

### 8.6 Entorno de desarrollo

El panel se sirve en `5190` y el sitio en `3000`; el proxy de desarrollo apunta a la API en
`8010` y a Nuxt en `3000`. Los puertos internos de los contenedores **no se remapean**:
el remapeo vive sólo en `compose.override.yaml`, que no se usa en producción.

---

## 9. Sitio público (Nuxt 4 + TypeScript)

### 9.1 Por qué SSR

El sitio público existe para ser encontrado, citado y auditado. Con renderizado en servidor:

- Cada trámite, noticia y documento de transparencia tiene HTML completo **sin ejecutar
  JavaScript**, que es lo que necesitan los rastreadores y las herramientas de medición.
- Los metadatos de cada ruta se emiten en el servidor: título, descripción, canónica,
  Open Graph y datos estructurados.
- La accesibilidad se puede medir sobre el HTML servido, no sobre un árbol que aparece
  después.

El panel, en cambio, es una SPA: no necesita posicionamiento y sí una navegación inmediata
tras la autenticación.

### 9.2 Rutas del sitio

| Ruta | Contenido |
|---|---|
| `/` | Portada: bloques, trámites destacados, noticias, participación |
| `/tramites` y `/tramites/[slug]` | Catálogo y ficha del trámite |
| `/tramites/[slug]/iniciar` | Inicio de radicación |
| `/pqrsd` | Formulario de PQRSD |
| `/seguimiento` | Consulta del estado por número de radicado |
| `/transparencia` y `/transparencia/[slug]` | Conjuntos de información |
| `/participa` y `/participa/portales/[slug]` | Participación ciudadana |
| `/noticias` y `/noticias/[slug]` | Noticias con archivo histórico |
| `/atencion` | Canales de atención y sedes |
| `/buscar` | Buscador interno |
| `/accesibilidad` | Declaración de accesibilidad |
| `/mapa-del-sitio` | Mapa del sitio |
| `/politicas/[slug]` | Las cinco políticas obligatorias del pie |
| `/verificar/[codigo]` | Verificación pública de un documento |
| `404` | Página de no encontrado, útil y con salidas |

### 9.3 Posicionamiento

| Elemento | Implementación |
|---|---|
| Metadatos por ruta | `useSeoMeta` con título, descripción y canónica generados desde el contenido real |
| Datos estructurados | `schema.org` para la entidad, los trámites y las noticias |
| `sitemap.xml` | Generado desde el contenido publicado, no desde una lista fija |
| `robots.txt` | Permite el sitio y excluye el panel y la API |
| Redirecciones | Tabla de redirecciones 301 administrable desde el CMS, con seguimiento de destino |
| Rendimiento | Presupuesto de rendimiento verificado en la puerta de calidad |
| Enlaces | URLs limpias, en castellano, sin parámetros innecesarios |

### 9.4 Kit gov.co

El Kit UI 9.2 **se vendoriza** en el repositorio, en `sitio/public/govco/`, con su
estructura relativa intacta (`all.css`, `script.js`, `assets/fonts`, `assets/icons`).

Motivo verificado: de las tres URLs del CDN que citan los documentos, la principal
(`cdn.www.gov.co/layout/v5/all.css`) **no sirve CSS**, devuelve el HTML de la aplicación de
la Biblioteca Digital de Componentes. Vendorizar elimina la dependencia de un tercero para
la capa visual —que es un atributo de disponibilidad y de titularidad del artículo 14 del
Decreto Ley 2106 de 2019— y permite fijar por integridad lo que se sirve.

**El `script.js` global del Kit no se carga.** Se autoinicializa sobre selectores que en
nuestras páginas devuelven `null`, y produce un error de consola en cada carga. El
comportamiento lo aportan Bootstrap 5.0.2 y los componentes propios (§11.3). Cuando se
incorpore un componente del Kit que sí lo necesite, su script se cargará **bajo demanda**,
en la vista que lo use.

### 9.5 Caché y entrega

- El HTML en caché no se guarda más allá de lo que el contenido tarde en cambiar; los
  activos con nombre derivado del contenido son inmutables.
- Las respuestas de la API **no se cachean** en el borde.
- La purga se dispara al publicar en el CMS: publicar no puede depender de que alguien
  recuerde vaciar una caché.

---

## 10. Módulos del CMS

El expediente describe dieciséis módulos. El CMS editorial los administra y la API los
expone. Cada módulo se construye como **corte vertical completo**: base de datos, API,
servicio, panel, pruebas y validación visual, antes de pasar al siguiente.

### 10.1 La capa editorial

Es el motor de contenidos, común a todos los módulos:

| Capacidad | Detalle |
|---|---|
| Tipos de contenido | Definidos en datos, con campos tipados y validación por campo |
| Flujo editorial | Borrador → revisión → programado → publicado → archivado |
| Versionado | Cada publicación crea una revisión restaurable |
| Programación | Publicación y retiro por fecha y hora, sin intervención manual |
| Biblioteca de medios | Imagen, documento y anexo, con textos alternativos obligatorios |
| Taxonomías | Categorías, etiquetas y series documentales |
| Menús | Gestión de los menús del sitio, con los límites del expediente |
| Redirecciones | Tabla 301 con verificación de destino |
| Auditoría | Quién cambió qué y cuándo, con restauración |
| Revisión obligatoria | Cada contenido publica con fecha de próxima revisión |

### 10.2 Los dieciséis módulos

| # | Módulo | Contenido |
|---|---|---|
| M1 | Contenido institucional | Páginas, políticas, normatividad y documentos de la entidad |
| M2 | Trámites y servicios | Catálogo con los **seis atributos obligatorios** (§10.3) |
| M3 | PQRSD | Tipificación, radicación, traslados y respuesta |
| M4 | Radicación y expedientes | Numeración, anexos, estados, integridad y retención documental |
| M5 | Notificaciones | Bandeja interna, correo, y correo certificado para actos administrativos |
| M6 | Pagos | Órdenes, pasarela, conciliación y comprobantes |
| M7 | Transparencia | Publicación activa de los conjuntos de información exigidos |
| M8 | Participación | Noticias, portales de participación, encuestas y control social |
| M9 | Datos abiertos | Conjuntos descargables en formato abierto con licencia declarada |
| M10 | Panel del ciudadano | Mis radicados, documentos, notificaciones, datos y portabilidad |
| M11 | Usuarios y roles | Funcionarios, permisos, segundo factor y auditoría |
| M12 | Menús y navegación | Estructura de navegación con los límites del expediente |
| M13 | Bloques de portada | Composición de la página de inicio |
| M14 | Metadatos y posicionamiento | Títulos, descripciones, canónicas y redirecciones |
| M15 | Canales y sedes | Canales oficiales de atención y sedes físicas |
| M16 | Buscador interno | Indexación propia del contenido publicado |

### 10.3 Los seis atributos obligatorios de un trámite

Un trámite sin los seis **no se puede publicar**. La puerta de publicación lo impide:

1. **Modalidad**: en línea, parcialmente en línea o presencial.
2. **Tiene costo**: gratuito o con costo.
3. **Tiempo de solución** en días hábiles.
4. **Canal de inicio**: enlace al portal nacional o inicio propio.
5. **Mecanismo de consulta del estado**.
6. **Documentos y requisitos**, descargables.

El CMS los exige al publicar y el catálogo público los muestra en la ficha.

### 10.4 Reglas de negocio que el CMS debe hacer cumplir

| Regla | Valor |
|---|---|
| Acuse de recibo de PQRSD | En **1 minuto** |
| Respuesta de fondo | **15 días hábiles** |
| Cambio de estado visible al ciudadano | En **5 minutos** |
| Envío de correo | En **2 minutos** |
| Canales obligatorios de notificación | **No desactivables** por el ciudadano |
| Correo certificado | Obligatorio para actos administrativos |
| Ítems del menú principal | Máximo **7**, con hasta **4** secciones internas |
| Valor de un trámite | Lo determina el catálogo, **nunca la petición** |
| Webhooks de pago y de correo | **Idempotentes**: repetir no duplica |
| Trámite gratuito | No genera orden de pago |
| Retención documental | Plazos por serie, contados desde el cierre del expediente |

---

## 11. Accesibilidad y conformidad

### 11.1 Norma aplicable

**WCAG 2.1 nivel AA**, obligatorio desde el 1 de enero de 2022 por la Resolución MinTIC
1519 de 2020. WCAG 2.2 se atiende como recomendación, no como obligación. El nivel AAA no
es exigible en Colombia.

No es un requisito de calidad: el artículo 14 inciso 2 del Decreto Ley 2106 de 2019 lo
eleva a **atributo de la sede**, junto con calidad, seguridad, disponibilidad, neutralidad
e interoperabilidad.

### 11.2 Umbrales medibles

| Criterio | Umbral exacto |
|---|---|
| Contraste de texto normal | **4,5:1** |
| Contraste de texto grande (≥ 24 px, o ≥ 19 px en negrita) | **3:1** |
| Contraste de componentes de interfaz y del indicador de foco | **3:1** |
| Contraste de texto de marcador de posición | **4,5:1** |
| Contraste de texto sobre imagen | **4,5:1** |
| Área activa táctil en móvil | **44 × 44 px** como mínimo |
| Indicador de foco | Visible, de **al menos 2 px** y **3:1** |
| Navegación por teclado | **100 %** de la funcionalidad |
| Orden de tabulación | Igual al orden visual; prohibido `tabindex` positivo |
| Ancho de línea | Entre **45 y 75** caracteres |
| Reflujo | Sin desplazamiento horizontal a **320 px** |
| Ampliación | Hasta **400 %** sin pérdida de contenido |
| Espaciado de texto | Soportar 1,5 de interlineado, 0,12 em de espaciado entre letras |

### 11.3 Componentes del Kit gov.co

Cada componente del Kit se encapsula como **componente Vue propio que reproduce el marcado
oficial** y delega el comportamiento donde exista. No se reimplementa la apariencia ni se
reescribe el CSS del Kit: la tematización por entidad se hace sobrescribiendo variables
`--govcolor-*` y `--govfont-*`.

Catorce componentes como mínimo:

`BarraSuperior` · `CabeceraEntidad` · `BarraAccesibilidad` · `MenuNavegacion` ·
`MigaDePan` · `BuscadorSede` · `PieDePagina` · `SaltarAlContenido` · `VolverArriba` ·
`AvisoCookies` · `AvisoPrivacidad` · `CaptchaSede` · `IndicadorCarga` · `TarjetaContenido`

Dos decisiones que se apartan del ejemplo del Kit y se documentan:

- **La barra de accesibilidad se gobierna desde Vue.** Conserva el marcado y las clases
  oficiales, pero la escala tipográfica se expone como variable CSS aplicada al contenedor
  y la preferencia se guarda en el navegador. El script oficial recorre `body *` y escribe
  `font-size` en línea sobre cada elemento: en una aplicación que navega sin recargar, el
  ajuste se perdería a mitad del recorrido del ciudadano y llenaría el DOM de estilos
  en línea.
- **El modo de alto contraste se tematiza con los colores institucionales ya validados**,
  porque el Kit sólo estiliza su propia página de ejemplo.

### 11.4 Desviaciones declaradas frente a los criterios de diseño

El expediente fija 34 criterios de diseño (CAG-01 a CAG-34). Algunos no admiten
cumplimiento literal. Afirmar que se cumplen sería falso; callarlos dejaría la matriz
mintiendo por omisión. Se declaran, se justifican y la puerta de calidad los vigila:

| Criterio | Estado | Motivo |
|---|---|---|
| CAG-06 — conmutador de idioma | **Omitido** | El expediente exige que la sede esté íntegramente en castellano. Un control que no conmuta ninguna lengua anunciaría una capacidad inexistente |
| CAG-18 — máximo 5 elementos visibles | **Reexpresado** | La sede usa el `<select>` nativo, que aporta teclado y lector de pantalla. El umbral pasa a **12 elementos**, y por encima se usa el desplegable con filtro del Kit |
| CAG-19, 21, 22, 24, 25 | **No aplican** | La sede no usa campos de calendario, no abre modales, no emite avisos flotantes, no publica tablas ni usa acordeones. La puerta **comprueba la ausencia**: si alguno se incorpora, falla y obliga a satisfacer su criterio |
| Superficie verde del Kit | **No se adopta** | El aviso de éxito del Kit rinde **3,61:1**, por debajo del 4,5:1 exigido. Se usa color de texto sobre blanco |
| CAG-33 — sólo los 21 tokens | **Reexpresado** | El bundle del Kit escribe ocho colores como literales. Los componentes que la sede instancia se reexpresan con tokens; el resto del bundle no se toca. La puerta mide la **paleta efectiva de las vistas**, no la del archivo |
| Títulos del pie | **`h2` y `h3`, no `h4` y `h5`** | El orden de encabezados del documento manda; la apariencia del Kit se aplica por clase |
| Logo de la autoridad | **Pendiente de archivo oficial** | El Kit dibuja el logo del Ministerio TIC como ejemplo. Publicarlo en la sede del Distrito sería mostrar el logotipo de otro organismo. Existe un escudo oficial en SVG descargable (§12.11) |

### 11.5 Verificación automática

Tres puertas, todas ejecutables desde el `Makefile` y obligatorias en la canalización:

| Puerta | Qué mide |
|---|---|
| `make diseno` | Sobre navegador real: tipografía, paleta efectiva, contraste, rejilla en los seis puntos de quiebre, área activa táctil, componentes del Kit, ausencia de los componentes no aplicados |
| `make accesibilidad` | `axe-core` sobre **todas** las vistas públicas: cero hallazgos críticos y cero graves |
| `make conformidad` | Shell institucional: barra superior, cabecera, barra de accesibilidad, miga de pan, pie con los ocho datos y las cinco políticas |

Ninguna vista pública se considera terminada si alguna de las tres falla. Las capturas de
pantalla se archivan con cada PR como evidencia.

### 11.6 Declaración de conformidad

La sede publica su **Declaración de Conformidad con WCAG 2.1 AA**, enlazada desde
transparencia, con: alcance, herramientas y versiones usadas, responsable de la auditoría,
criterios no aplicables, excepciones declaradas, hallazgos conocidos con su plan y la fecha
de próxima revisión —que no puede superar los doce meses.

### 11.7 Contenido obligatorio de la portada y límites de la identidad visual

El anexo de contenido del portal nacional fija lo que la portada de una sede **debe** tener.
Se trata como criterio de aceptación desde el primer día, no como endurecimiento posterior,
porque el ministerio lo verifica antes de aprobar la integración:

| Elemento obligatorio | Regla |
|---|---|
| Barra del portal nacional | Presente, con un control que redirige al portal |
| Logotipo | Con altura máxima de 50 px |
| Buscador | **Interno**. Usar un buscador externo está prohibido |
| Menú principal | Transparencia, Servicios y Participa, con sus seis subcategorías |
| Pie de página | Con las políticas obligatorias (§12.11) |

> **El color propio de la entidad no puede usarse** en botones, texto, campos de formulario,
> fondos ni etiquetas. La sede se viste con los tokens institucionales del Kit y con ninguno
> más. Esto refuerza la decisión §21.C —sin Tailwind— y convierte la paleta en un criterio
> verificable, no en una preferencia de diseño.

Además, y como requisito de infraestructura poco habitual, la sede debe publicar
**direcciones públicas IPv4 e IPv6 con resolución de nombres de doble pila**, y operar
correctamente en al menos tres navegadores distintos. Se verifica en la fase 10.


---

## 12. Integraciones externas

### 12.1 Patrón

Ninguna clase de dominio conoce a un proveedor concreto. Cada integración es una
**interfaz** en `app/Contracts/Integraciones/` con tres implementaciones seleccionables por
configuración:

| Implementación | Uso |
|---|---|
| `live` | Proveedor real, con credenciales de producción |
| `sandbox` | Entorno de pruebas del proveedor |
| `mock` | Determinista, para desarrollo y pruebas automáticas |

Consecuencia: el flujo completo es ejecutable y verificable **hoy**, sin credenciales, y el
paso a producción exige sólo cambiar el driver y aportar credenciales.

> **Regla dura.** No se inventa ningún valor de credencial. Un secreto de relleno pasa las
> validaciones de presencia y falla únicamente en la verificación de firma, ya en caliente:
> es peor que su ausencia. La ausencia se declara y la integración queda apagada con un
> mensaje útil, nunca con un botón que no lleva a ninguna parte.

### 12.2 Interfaz de identidad y Servicios Ciudadanos Digitales

Es la integración de mayor peso: define cómo entra el ciudadano.

- Flujo de autorización con estado (`state`) de un solo uso, verificado contra el navegador
  que lo inició, con caducidad.
- El dato de identidad se toma del punto de usuario verificado, **nunca** del identificador
  que llega sin firmar.
- La identidad verificada se vincula a la cuenta que ya existe para ese documento.
- Una cuenta de funcionario **jamás** se vincula por esta vía.
- Sin proveedor configurado, la sede **lo dice** en lugar de ofrecer un botón que no lleva
  a ninguna parte.

La autenticación digital de los Servicios Ciudadanos Digitales es **OIDC sobre OAuth 2.0**.
El emisor es del propio ecosistema estatal, y los requisitos técnicos verificados son:

| Requisito | Valor |
|---|---|
| Emisor | El dominio de autenticación digital del Estado |
| Flujo | `authorization code`, con **PKCE** (RFC 7636) |
| Descubrimiento | `.well-known/openid-configuration` obligatorio |
| Interoperabilidad | X-Road, con certificado X.509, sellado de tiempo y comprobación de revocación |

**No se publican las direcciones concretas de los puntos de acceso ni las credenciales:**
requieren solicitud formal a la entidad y al ministerio. El plan construye el adaptador
contra el contrato estándar de OIDC, de modo que incorporar las credenciales reales sea
configuración y no reescritura.

### 12.3 Dirección de la integración con el Portal Único — resuelta

La contradicción **H-15** queda resuelta por la norma, no por una decisión nuestra:

> **GOV.CO redirige hacia la sede de la entidad. La entidad no redirige al portal.**

El anexo de integración de la Resolución 2893 de 2020 lo dice literalmente al describir «la
redirección desde la sede electrónica única compartida (GOV.CO) a la sede electrónica de cada
autoridad». La entidad **conserva su dominio propio**.

| Aspecto | Valor |
|---|---|
| Mecanismo | Redirección con enmascaramiento de URL, mediante el proxy que provee **exclusivamente** GOV.CO |
| Lo que **no** es | No es iframe, no es widget, no es API |
| Dirección resultante | El trámite se ofrece también bajo la ruta pública del portal nacional |
| Dirección de la sede | La sede radica en sí misma y conserva su dominio |

**Consecuencia para el diseño:** el módulo de radicación, el de pagos y el de notificaciones
**existen y son propios**. Se mantiene la contradicción detectada en el expediente como un
defecto de redacción de `FUN-023`, no como una instrucción de arquitectura, y se registra en
el acta de conformidad.

Además, el esquema de integración **por APIs o servicios web está expresamente
discontinuado** por el ministerio: quedó consolidado en el esquema único de
redireccionamiento. Exponer una API para que el portal nacional la consuma **no es un camino
válido** y no se diseña.

**Doce controles de seguridad se verifican antes de aprobar la integración.** Coinciden
casi por completo con los criterios de seguridad del expediente, y por eso el plan ya los
cubre — pero conviene tenerlos presentes como **criterios de aceptación de la integración**,
no como endurecimiento posterior: transporte cifrado, captcha, cabeceras de seguridad
—política de contenido, HSTS y las demás—, cookies con `Secure` y `HttpOnly`, métodos
`PUT`, `DELETE`, `TRACE` y `OPTIONS` deshabilitados, saneamiento de la entrada, permisos de
sólo lectura sobre el sistema de archivos, mensajes de error genéricos y límite de tasa en
la autenticación.

### 12.4 Radicación

La radicación electrónica tiene obligaciones legales concretas, y la sede las implementa
tal cual:

| Obligación | Origen | Implementación |
|---|---|---|
| Registro electrónico de documentos con control de **fecha y hora** | Ley 1437, art. 61, modificado por la Ley 2080 de 2021 | Marca de tiempo del servidor, no del cliente |
| **Acuse de recibo** con la fecha y el número de radicado | La misma norma | Acuse emitido en un minuto por los canales obligatorios |
| Verificación del estado de la solicitud | La misma norma | Consulta pública por número de radicado |

**Numeración.** Se adopta el modelo del archivo general de la Nación —`1-AAAA-XXXXX` para
entradas y `2-AAAA-XXXXX` para salidas, generado automáticamente— con una precisión honesta:
ese formato proviene del modelo de requisitos **para el sistema de gestión documental de esa
entidad**, y **no es un estándar nacional obligatorio**. Se adopta porque es el modelo de
referencia del sector, y queda como decisión declarada en vez de cómo obligación supuesta.

> **Riesgo de retrabajo, a verificar antes de diseñar cualquier formulario.** Si el trámite
> es un **trámite modelo**, su **formulario único es de obligatoria observancia** y no admite
> pasos ni requisitos adicionales. Diseñar un formulario propio para un trámite modelo
> obliga a rehacerlo entero. La verificación es una tarea bloqueante de la fase 5 y su
> resultado decide si el formulario se construye o se consume.

### 12.5 Pagos

**No existe un servicio transversal de pagos del Estado.** Es una ausencia verificada, no
una suposición: el medio de pago no aparece en los anexos de integración revisados, y la caja
de herramientas no publica componente, interfaz ni guía de pagos alguna. **El pago es
responsabilidad de cada entidad** con su banco o su pasarela, y su coste es presupuesto de la
entidad.

El contrato técnico de la pasarela pública es un anexo **confidencial** del reglamento del
sistema de pagos, de modo que el diseño se hace contra el patrón público del medio:

| Característica | Consecuencia de diseño |
|---|---|
| Redirección a la pasarela | **Incompatible con iframe**: la sede redirige, no incrusta |
| Confirmación asíncrona | La orden queda en espera hasta que llega la confirmación |
| La notificación del servidor es el **único modo fiable** | El retorno del navegador **no** se usa como prueba de pago |
| Firma sobre los campos de la transacción | Se verifica **antes** de escribir nada |
| Conciliación por sondeo periódico | Cubre las notificaciones que nunca llegan |
| Reembolso automático cuando el banco no responde | Se refleja en la conciliación, no se ignora |

Esto confirma dos reglas que el plan ya fijaba: el valor lo determina el catálogo y nunca la
petición, y la notificación es idempotente —repetirla no duplica el pago—.

> **Precisión sobre los datos de tarjeta.** No se encontró norma colombiana que prohíba
> almacenarlos. Se cumple **por diseño** del medio de pago: la transferencia ocurre por
> redirección y la entidad nunca ve el número. Si en el futuro se aceptaran tarjetas
> directamente, aplicaría el estándar internacional de la industria, sin remisión normativa
> nacional verificada. La regla del plan —no almacenar nunca datos de tarjeta— se mantiene
> por prudencia, no porque una norma lo exija.

### 12.6 Notificaciones

**La base legal vigente no es el Decreto 491 de 2020.** Ese decreto está atado a la
emergencia sanitaria y su alcance se agotó. Lo que rige es la **Ley 1437, artículo 56,
modificado por la Ley 2080 de 2021**.

Y el disparador de validez es exigente:

> La notificación queda surtida **a partir de la fecha y hora en que el administrado acceda
> a la misma**, hecho que debe ser **certificado por la administración**.

Eso convierte la trazabilidad del acceso en un requisito legal, no en una buena práctica:

| Requisito | Implementación |
|---|---|
| Registrar el **acceso** con fecha y hora | Marca de tiempo certificable, del servidor |
| **Certificar** ese acceso | Constancia descargable y verificable por código |
| Ofrecer un **portal de acceso** a las notificaciones | La propia norma reconoce al portal nacional como portal de acceso |
| Canales obligatorios no desactivables | El ciudadano no puede apagar los canales que la ley exige |
| Correo certificado para actos administrativos | Con la constancia del operador |

**Lo que no se ha podido verificar, y se declara:** no se confirmó con fuente oficial qué
operadores están habilitados para la notificación electrónica de actos administrativos. El
plan no nombra a ninguno; la elección del operador queda como decisión de la entidad antes de
la fase 6.

### 12.7 Catálogo de integraciones

| Integración | Estado esperado | Observación |
|---|---|---|
| Identidad ciudadana | Requiere convenio | OIDC sobre OAuth 2.0 con PKCE; sin credenciales queda declarado e inactivo |
| Portal Único del Estado | **Resuelta por norma** | El portal redirige a la sede; la entidad conserva su dominio (§12.3) |
| APIs expuestas al portal nacional | **Descartado** | El esquema por servicios web está discontinuado (§12.3) |
| Pasarela de pagos | Requiere convenio | No hay servicio transversal del Estado; redirección, notificación firmada e idempotente, conciliación por sondeo (§12.5) |
| Firma electrónica | Requiere convenio | Fuera del alcance de la primera entrega |
| Correo certificado | Requiere convenio | Obligatorio para actos administrativos |
| Notificaciones por correo | Configurable desde el primer día | Proveedor de envío por decidir (§21.G) |
| Mensajería de texto | Recomendado | Canal complementario |
| Captcha | Configurable desde el primer día | Implementación local disponible; proveedor externo por decidir |
| Buscador | Propio | Búsqueda de texto completo en PostgreSQL, sin terceros |
| Gestor de cookies | Propio | Aviso y registro de consentimiento |
| Archivo General de la Nación | Requiere convenio | Series y tablas de retención |
| Interoperabilidad | Requiere convenio | Consulta de datos que ya reposan en otra entidad |
| Kit UI gov.co | **Vendorizado** | Sin dependencia de la red del proveedor (§9.4) |
| Centro de respuesta a incidentes | Procedimiento | Reporte, no integración técnica |

### 12.8 Reglas para toda integración

- Tiempo de espera acotado y reintento con retroceso exponencial; nunca una llamada externa
  dentro de una petición HTTP del ciudadano cuando pueda ir a la cola.
- Los webhooks verifican firma **antes** de escribir nada, y son idempotentes.
- El registro de la integración nunca guarda el cuerpo completo si contiene datos
  personales: se guarda el identificador, el resultado y la firma verificada.
- Toda integración expone su estado en la sonda de preparación.

### 12.9 Lo que no se puede inventar

Estas credenciales las entrega un tercero y **no se crean**:

| Secreto | Origen |
|---|---|
| Firma de la pasarela de pagos | La entrega la pasarela |
| Firma del correo certificado | La entrega el operador postal |
| Cliente y secreto de identidad | Los entrega el proveedor de identidad |
| Token del captcha | Lo entrega el proveedor, si se contrata |

### 12.10 Transparencia

El expediente **no enumera** los contenidos obligatorios de transparencia; delega en la
Resolución 1519 de 2020. Se adopta su **Anexo 2**, y se verifica contra el sitio real de la
entidad: la Alcaldía ya replica los ocho bloques de ese anexo en su portal actual, con 202
documentos, de los cuales **191 son PDF y ninguno es formato abierto**.

El CMS debe superar ese estado, no igualarlo:

- Cada conjunto de información declara **fecha de publicación** y se ordena de forma
  descendente.
- Se publica en formatos abiertos además de PDF, con la licencia declarada (FUN-059).
- Sin duplicidad entre conjuntos.
- Sólo se expone contenido **publicado y vigente**.
- **Buscador propio acotado a transparencia**, no delegado a un buscador externo.

### 12.11 Datos institucionales de la entidad

Los datos del pie de página son un requisito con ocho campos (FUN-014) y cinco políticas
enlazadas (SEG-006). Verificados contra el sitio oficial de la entidad el 2026-09-30:

| Dato | Valor | Estado |
|---|---|---|
| Nombre | Alcaldía Distrital de Santa Marta (D.T.C.H.) | Confirmado |
| NIT | 891.780.009-4 | Confirmado: sin dígito de verificación en el sitio, con él en documento oficial |
| Dirección | Calle 14 No. 2-49, Palacio Municipal, Santa Marta, Magdalena | Confirmada en la página de puntos de atención |
| Conmutador | (+57) 605 420 9600 | Confirmado |
| Atención al ciudadano | (+57) 605 4351719 | Confirmado |
| Línea anticorrupción | (+57) 605 4351719 | Confirmado, **pero es el mismo número** que atención al ciudadano |
| Línea gratuita | **018000 955 532** | **Confirmada**: publicada en la página de puntos de atención, no en el pie |
| Correo de atención | atencionalciudadano@santamarta.gov.co | Confirmado |
| Correo de notificaciones judiciales | notificacionesalcaldiadistrital@santamarta.gov.co | Confirmado; falta el acto administrativo que lo formaliza |
| Código postal | 470004 | **La entidad no lo publica**; verificado contra el visor oficial de correos |
| Redes sociales | Facebook, Instagram, X y YouTube institucionales | Confirmado |
| Logo oficial | Escudo vectorial descargable (580 KB) y manual de identidad de 2024 | Confirmados; falta incorporar el escudo al repositorio |
| Política de derechos de autor | — | **No existe**: es un incumplimiento que la sede debe cerrar |
| Declaración de accesibilidad | — | **No existe**: `/declaracion-de-accesibilidad` responde 404 |

> **El pie de página del portal actual incumple FUN-014.** Omite la línea gratuita, el
> código postal, la dirección completa y la política de derechos de autor. Los datos
> existen dispersos, pero el requisito es que estén **en el pie**. Es exactamente lo que la
> sede debe corregir, y por eso la línea gratuita aparece aquí como confirmada aunque el
> pie no la publique.

**Ningún dato marcado como no confirmado se publica.** Se centralizan en
`backend/config/entidad.php`, se exponen por `GET /api/v1/entidad`, y los que requieren
ratificación formal quedan listados como pendientes y señalados en el panel, de modo que
quien publique sepa qué no está verificado.

### 12.12 Origen de los datos del catálogo

El catálogo de trámites **no se transcribe del sitio web de la entidad**, que publica 118
entradas y ninguna con sus atributos obligatorios. Se carga desde el **sistema oficial de
información de trámites**, donde la entidad tiene **124 trámites vigentes** bajo el código
de entidad **0043**.

La diferencia entre 118 y 124 no es cosmética. El cruce código a código da un resultado
cerrado: **16 trámites están vigentes en el sistema oficial y no aparecen en el sitio**, y
**2 aparecen en el sitio y no en el sistema oficial**. El sitio además publica 118 entradas
con sólo **110 códigos únicos**: ocho duplicados internos.

Los dieciséis que el ciudadano hoy no encuentra incluyen piezas que no son marginales:

| Trámite ausente | Por qué importa |
|---|---|
| Impuesto de delineación urbana | Es un impuesto, con recaudo |
| Plan de Manejo de Tránsito | Movilidad, con obra en vía pública |
| Excepción a restricciones de movilidad | Movilidad |
| Inscripción o autorización para circulación vial | Movilidad |
| Orden de entrega de vehículo inmovilizado (tres variantes) | Movilidad, con custodia de bien |
| Visto bueno para eventos que afecten la movilidad | Movilidad |
| Concepto de norma urbanística · determinantes para planes parciales · aprobación de planos de propiedad horizontal | Urbanismo |
| Englobe y desenglobe de predios · rectificación de áreas y linderos · cambios por inscripción de predios | Catastro y registro |
| Impuesto a la publicidad visual exterior | Es un impuesto |
| Devolución o compensación de pagos en exceso | Dinero del ciudadano |
| Reconocimiento de escenarios culturales | Cultura |

Los dos huérfanos son dos variantes de «Retiro de la base de datos del SISBEN», que hay que
resolver con la entidad: o están desactualizados en el sitio, o son trámites desregulados.

Esta lista es la carga inicial del catálogo en la fase 3, y es verificable: el criterio de
salida es que **los 124 trámites oficiales estén publicados y completos**, y que los dos
huérfanos tengan una decisión escrita.

La sede parte del listado oficial, y el CMS exige los seis atributos antes de permitir la
publicación (§10.3). Los que la entidad no pueda completar quedan en borrador y **no se
publican a medias**: un trámite sin costo, tiempo ni modalidad es un trámite que el
ciudadano no puede usar.

### 12.13 Canales de notificación publicados que no funcionan

El portal de la entidad publica enlaces a los servicios de notificación de actos
administrativos. Verificados el 2026-09-30, **están caídos**: cuatro rutas sobre una
dirección IP directa y **HTTP plano**, sin cifrado, y tres submundos institucionales que no
responden.

Esto importa por dos razones. La primera, de negocio: la notificación de un acto
administrativo es un acto con efectos jurídicos, y hoy no tiene un canal operativo
publicado. La segunda, de seguridad: un enlace de notificación servido **sin cifrado y por
dirección IP** expone el contenido a cualquiera que esté en el camino.

Se eleva a la entidad como parte del diagnóstico. La sede debe ofrecer un canal de
notificación que funcione y que use HTTPS.

### 12.14 Reconocimiento pendiente de las fuentes oficiales

Obtener el catálogo oficial y los códigos de trámite **no es una descarga**: tanto el portal
nacional como el sistema de información de trámites son aplicaciones que se construyen en el
navegador. Sus direcciones devuelven el mismo armazón vacío, su mapa del sitio tiene una
sola dirección, y el componente de búsqueda que publican devuelve HTML donde debería
entregar un módulo de JavaScript.

Consecuencia para el plan: **el reconocimiento de esas fuentes exige un navegador real**,
no una descarga. Es una tarea de la fase 3 (F3.x) ejecutada con el mismo navegador que usan
las pruebas extremo a extremo, y su producto es una tabla de trámites con sus atributos,
no una captura de pantalla.

Mientras esa tarea no se ejecute, el catálogo se carga con lo que la entidad pueda aportar
por acto administrativo, y el CMS exige los seis atributos antes de publicar (§12.12).

### 12.15 Punto de partida del portal actual
Es la línea base que la sede debe mejorar, y conviene tenerla presente al dimensionar:

| Aspecto | Estado actual | Requisito que incumple |
|---|---|---|
| Gestor | **Drupal 7.103** sobre govCMS —la distribución del gobierno de Australia—, **sin parches del núcleo desde el 5 de enero de 2025** | SEG-015 |
| Huella expuesta | `/CHANGELOG.txt`, `/README.txt` e `/INSTALL.txt` públicos | SEG-011 |
| Diseño | Bootstrap 3.3.7 y jQuery 2.2.4, ambos sin soporte; **0 clases `govco-*`** frente a las 1.677 del Kit; sin el azul institucional | CAG-33, FUN-047 |
| TLS | **TLS 1.0 y 1.1 todavía habilitados**: calificación **B**, no A | SEG-001, RT-02 |
| Cabeceras | Sin política de contenido ni de permisos | SEG-012 |
| Trámites | 118 en el sitio frente a **124 vigentes en el sistema oficial** (entidad 0043); **ninguno declara costo, tiempo ni modalidad** | FUN-021 |
| PQRSD | Plataforma de un tercero, **más un formulario externo** para alumbrado público: dos canales que rompen la trazabilidad | FUN-025, O-07 |
| Transparencia | Los ocho bloques del anexo, 202 documentos, **191 en PDF y ninguno en formato abierto**; faltan cuatro ítems; sin buscador propio | FUN-016 a FUN-020, FUN-059 |
| Datos abiertos | **Ningún conjunto** atribuido a la Alcaldía en el catálogo nacional | FUN-059 |
| Accesibilidad | **Sin declaración de conformidad**; imágenes sin texto alternativo, encabezados en inglés | ACC-*, O-11 |
| Normatividad interna | **Ningún acto** de adopción en TIC, gobierno digital o protección de datos entre 1.643 actos revisados; la política de privacidad vigente **exime de responsabilidad por accesos no autorizados**, contra el artículo 17 de la Ley 1581 de 2012 | O-12, SEG-013 |
| Sede electrónica | **No existe** subdominio dedicado | O-01 |

Cada fila es un requisito del expediente que hoy no se cumple. La sede electrónica nace
precisamente para cerrarlas, y **la columna de requisitos es la lista de trabajo**.

> **Nota sobre los actos de adopción.** La ausencia de actos administrativos sobre gobierno
> digital, protección de datos y seguridad de la información no es un problema técnico que
> el código pueda resolver. Se eleva a la entidad: sin acto de adopción de la política de
> tratamiento de datos, la sede no puede declarar conformidad plena con la Ley 1581 de 2012
> por mucho que su implementación sea correcta.

---

## 13. Infraestructura en DigitalOcean

### 13.1 El droplet

| Atributo | Estado verificado el 2026-09-30 |
|---|---|
| Identificador | `604812963` |
| IP | `198.199.89.119` (IPv4) y `2604:a880:0400:d1::5:125a:a001` (IPv6, **ya asignada**) |
| Hostname actual | `sede-electronica` |
| Región | `nyc1` (Nueva York 1) |
| Sistema operativo | **Ubuntu 26.04.1 LTS**, kernel 7.0 |
| Tamaño actual | 2 vCPU · **4 GB** de memoria · 120 GB de disco |
| Tamaño objetivo | 2 vCPU · **8 GB** de memoria · mismo disco (ampliación pendiente, tarea F0.0) |
| Usuarios | Sólo `root`; falta crear `ops`, `deploy` y las cuentas de servicio |
| Docker | **No instalado** |
| Cortafuegos del host | Presente pero **inactivo** |
| Intercambio | **Sin configurar** |
| Actualizaciones pendientes | Ninguna |
| Acceso | Llave Ed25519, sólo `root`, con autenticación por contraseña ya deshabilitada |
| Doble pila | IPv4 e IPv6 — el requisito de aceptación de §11.7 ya se cumple en el droplet |

> **Nota de versión del sistema operativo.** La guía de seguridad está escrita para Ubuntu
> 24.04 y el droplet corre 26.04. Cada valor por versión —versiones de OpenSSH, nftables,
> AppArmor, auditd, nombres de paquete y orígenes de actualización— **se verifica contra el
> sistema real antes de aplicarlo**, en lugar de copiarse del capítulo. Es la tarea F10.1,
> adelantada a la fase 0 para el endurecimiento del host.

**Por qué ese tamaño.** El capítulo 01 estima 4 workers de PHP, más nginx, Redis y los
clientes de base de datos, en torno a 3,5 GB, y recomienda al menos 2 vCPU y 8 GB. A ello
se suma el servidor de Nuxt, que el capítulo no contemplaba porque no contempla SSR.
160 GB permiten instantáneas completas sin recortes.

> **Discrepancia del capítulo 01, resuelta.** El capítulo recomienda «General Purpose
> 4 vCPU / 8 GB» pero su propio comando usa `gd-2vcpu-8gb`. La familia General Purpose de
> DigitalOcean mantiene proporción 1:4, de modo que «4 vCPU / 8 GB» no existe como tamaño.
> Se adopta el tamaño que el comando realmente pide.

### 13.2 Cortafuegos del proveedor

Es la primera capa: se aplica **fuera del droplet**, en el hipervisor, de modo que el
tráfico bloqueado nunca llega al sistema operativo.

| Dirección | Protocolo | Puertos | Origen / destino |
|---|---|---|---|
| Entrada | TCP | 22 | **Sólo la IP del operador** |
| Entrada | TCP | 80, 443 | Rangos de Cloudflare |
| Entrada | ICMP | — | Rangos de Cloudflare |
| Entrada | todo | resto | **Denegado** |
| Salida | TCP y UDP | 53 | Cualquiera (resolución de nombres) |
| Salida | UDP | 123 | Cualquiera (sincronización de hora) |
| Salida | TCP | 80, 443 | Cualquiera (paquetes, certificados, registro) |
| Salida | TCP | 587 | Relé de correo autorizado, exclusivamente |

> **Esta tabla corrige tres defectos del capítulo 02.** El capítulo abre el 22 a todo
> Internet; declara primero que se restringe y después concluye que no. Y su regla de
> denegación `tcp 0-79` anularía el propio permiso del 22, dejando el servidor inaccesible:
> la precedencia entre permiso y denegación es absoluta, y así está documentada en el
> propio capítulo.

Las reglas se asignan **por etiqueta**, no por identificador de droplet: recrear la máquina
no deja el cortafuegos huérfano.

### 13.3 Cloudflare

| Ajuste | Valor |
|---|---|
| Modo SSL/TLS | **Full (Strict)** |
| Authenticated Origin Pulls | Activado, con verificación del certificado de cliente en nginx |
| WAF | Reglas administradas y OWASP activadas |
| Protección de bots | Modo de lucha contra bots |
| Límite de tasa | Sobre `/api/v1/auth/*` y sobre la radicación |
| Caché | Activos con nombre derivado del contenido; **la API no se cachea** |
| IP real | `CF-Connecting-IP` con los rangos publicados de Cloudflare, **verificados contra la lista oficial y actualizados por tarea programada** |

**Estado verificado el 2026-09-30.** `staging.santamarta.gov.co` ya resuelve a direcciones
de Cloudflare (`104.21.62.188` y `172.67.138.96`) y presenta el certificado comodín de la
zona, emitido por Google Trust Services y válido hasta el **16 de diciembre de 2026**. La
conexión todavía no completa el saludo TLS porque el droplet `198.199.89.119` no tiene nada
escuchando en el puerto 80: **el borde está listo y el origen no**. Eso sitúa la emisión del
certificado propio del origen (F0.11) y el primer despliegue (F0.15) como las tareas que
convierten ese estado en una sede que responde.

> El certificado comodín de Cloudflare cubre el tránsito entre el ciudadano y el borde. La
> sede necesita además su propio certificado en el origen, y ese es el que se emite y se
> renueva automáticamente en F0.11.

El origen sólo acepta tráfico cuyo certificado de cliente demuestre que viene de Cloudflare.
La lista de rangos se descarga y se valida antes de recargar nginx; si la descarga falla,
**no se recarga** y se conserva la lista anterior.

### 13.4 Alertas de la plataforma

| Métrica | Umbral |
|---|---|
| CPU | 70 % durante 5 minutos |
| Memoria | 80 % durante 5 minutos |
| Disco | 85 % |
| Carga del sistema | Igual o mayor que el número de vCPU |
| Ancho de banda saliente | 50 Mbps sostenidos |

Además, una comprobación externa de disponibilidad que exige `200` en la sonda de vida,
mide la validez restante del certificado y avisa cuando baja de 14 días.

---

## 14. Plano del host: endurecimiento del sistema operativo

El host **no ejecuta la aplicación**: sólo el sistema operativo endurecido y Docker Engine.
nginx, PHP, Node, PostgreSQL, Redis y el almacenamiento viven en contenedores (§15).

### 14.0 Lo que el sistema real obliga a cambiar

El droplet corre **Ubuntu 26.04 LTS** (`resolute`) y trae tres piezas que la guía de
seguridad no contempla, porque está escrita para 24.04. Verificadas en la máquina:

| Pieza | Versión real | Consecuencia |
|---|---|---|
| `sudo` | **`sudo-rs` 0.2.13** — la reescritura en Rust | **No soporta el subconjunto completo de `sudoers`**: `log_input`, `log_output` e `iolog_dir` son rechazados y un archivo inválido en `sudoers.d` **deja `sudo` inservible**. Las reglas se escriben con lo que sí soporta, y se valida con `visudo -c` antes de guardar |
| Arranque de SSH | **`ssh.socket`** con activación por socket | El `sshd` de cada conexión **lee la configuración en el momento**, así que un cambio se aplica a la conexión siguiente sin recargar nada. Y la directiva `Port` **se ignora**: el puerto lo define la unidad de socket |
| Docker | **29.8.1**, Compose **5.5.1** | Muy por encima de lo que asumen los documentos; el demonio se configura por `daemon.json`, no por banderas del servicio |

Otras versiones verificadas: `fail2ban` 1.1.0-9, `ufw` 0.36.2-9build1,
`unattended-upgrades` 2.12ubuntu9.

> **Regla que se deriva:** ningún valor por versión se copia del capítulo 01. Cada uno se
> comprueba en la máquina antes de escribirlo, y la comprobación falla el aprovisionamiento
> si el valor no existe.

### 14.1 Acceso y cuentas

| Elemento | Valor |
|---|---|
| Root por SSH | **Deshabilitado** |
| Autenticación por contraseña | **Deshabilitada** |
| Algoritmo de clave | Ed25519, con 100 rondas de derivación |
| Segundo factor | **Obligatorio** para cuentas humanas, con cinco códigos de recuperación |
| Intentos de autenticación | Máximo 3 |
| Sesiones simultáneas | Máximo 4 |
| Tiempo de gracia de acceso | 30 segundos |
| Reenvío de puertos | Permitido **sólo** en la cuenta de despliegue, para el túnel de administración |
| Reenvío de agente y de X11 | Deshabilitados |
| Grupos permitidos | `sede-ssh`, creado **antes** de configurar la directiva |

**Defecto del capítulo 01 que se corrige.** El capítulo desactiva la autenticación
interactiva por teclado (`KbdInteractiveAuthentication no`) y **a la vez** exige
`AuthenticationMethods publickey,keyboard-interactive`: con esas dos líneas el segundo
factor no puede funcionar nunca. Se habilita la autenticación interactiva y se exige el
segundo factor sólo para las cuentas humanas, mediante un bloque `Match`.

**Segundo defecto que se corrige.** El capítulo permite el grupo `ssh-users` sin crearlo:
nadie podría entrar. El grupo se crea en el mismo paso, antes de recargar el servicio.

### 14.2 Contraseñas y caducidad

| Parámetro | Valor |
|---|---|
| Longitud mínima | **15** caracteres (el capítulo fija 14; el expediente recomienda 15 y el mínimo legal es 8) |
| Clases de caracteres distintas | 4 |
| Repeticiones consecutivas | Máximo 3 |
| Contraseñas recordadas | Últimas 5 |
| Caducidad máxima | 90 días |
| Aviso previo | 14 días |
| Cuentas de servicio | Sin caducidad, sin contraseña utilizable |

### 14.3 Núcleo

Se aplican las directivas del capítulo 01, **con una excepción crítica**:

> **`net.ipv4.ip_forward` debe quedar en `1`, no en `0`.** El capítulo lo desactiva, pero
> Docker lo necesita para enrutar entre los contenedores y sus redes puente. Desactivarlo
> rompe la pila entera. El aislamiento se logra en la capa de red de Docker (redes
> `internal` y ausencia de puertos publicados), no apagando el reenvío del núcleo.

Se aplican íntegras, entre otras: filtrado inverso, sin rutas de origen ni redirecciones
aceptadas, sin redirecciones enviadas, registro de paquetes imposibles, limitación de ICMP,
`tcp_syncookies`, aleatorización del espacio de direcciones, restricción de `dmesg`, `kptr`,
`ptrace`, carga de módulos, `bpf` sin privilegios, `io_uring`, enlaces duros y simbólicos
protegidos, sin volcados de procesos con `suid`, y sin escritura ejecutable en memoria.

### 14.4 Auditoría y detección

| Componente | Configuración |
|---|---|
| auditd | Cola de 8.192, fallo en modo `1`, tamaño de 50 MB, 10 rotaciones, formato enriquecido |
| Reglas | Vigilancia de identidad (`passwd`, `shadow`, `group`, `gshadow`), PAM, sudoers, clave de SSH, `cron`, `execve` con privilegios, cambios de nombre de host, de red, de hora, módulos del núcleo, borrados y cambios de permisos |
| AppArmor | Perfiles en modo aplicación para los procesos del sistema; los contenedores llevan su propia confinación por Docker |
| AIDE | Base de integridad inicial, verificación diaria y actualización tras cada actualización del sistema |
| ClamAV | Análisis programado sobre los datos de la aplicación y los directorios de subida; **sin análisis al vuelo**, que consumiría la memoria que necesita PostgreSQL |
| Registro | journald persistente, 2 GB de tope, retención de 30 días, reenvío a syslog |

### 14.5 Actualizaciones

- Orígenes firmados por el proveedor de la distribución, incluidos los de seguridad.
- **Nunca se actualiza automáticamente el núcleo ni la librería C**: se marcan como
  excluidos y se actualizan en ventana mantenida.
- **Sin reinicio automático.** El operador decide cuándo reiniciar.
- Verificación semanal de que no haya paquetes de seguridad pendientes.

### 14.6 Red del host y convivencia con Docker

Este es el punto que la guía **no cubre en absoluto**, y es donde se rompen las pilas
reales. Docker escribe sus propias reglas en iptables y **se salta UFW** para los puertos
que publica. La respuesta del plan es de diseño, no de parcheo:

1. **Sólo nginx publica puertos.** Ningún otro servicio —ni la base de datos, ni la caché,
   ni la aplicación, ni el servidor de Nuxt— publica un puerto en el host, ni siquiera en
   `127.0.0.1`. El aislamiento no depende de dónde escucha el proceso, sino de que **no
   exista un mapeo de puerto**.
2. **La administración de datos no usa túneles.** Se entra con `docker compose exec`, que
   no requiere ningún puerto abierto. Esto elimina de raíz la contradicción del capítulo 04
   entre `skip-networking`, el túnel y `require_secure_transport`.
3. **Reglas explícitas en la cadena `DOCKER-USER`** que descartan el tráfico que no venga
   del propio host hacia las redes internas de Docker.
4. **nftables y UFW con una sola política**: UFW gobierna el tráfico del host y nftables
   mantiene el conjunto de bloqueos dinámicos. La política por defecto de la cadena de
   entrada es `drop`, y **no se usa `flush ruleset`** en caliente, que borraría las tablas
   de UFW y las cadenas de Fail2Ban.

### 14.7 Fail2Ban

Una **sola** configuración para todo el sistema. El capítulo 02, el apéndice A y el
capítulo 05 definen tres incompatibles entre sí; se adopta una y se documenta:

| Jail | Umbral | Duración del bloqueo |
|---|---|---|
| `sshd` | 3 intentos en 10 minutos | 24 horas, incrementales |
| `nginx-http-auth` | 5 en 10 minutos | 1 hora |
| `nginx-badbots` | 2 | 48 horas |
| `nginx-noscript` | 3 en 10 minutos | 24 horas |
| `nginx-ddos` | 200 en 1 minuto | 1 hora |
| `recidive` | 3 bloqueos en 1 día | 1 semana, en todos los puertos |

El incremento exponencial multiplica la duración por el número de reincidencias, con un
tope de cuatro semanas. La IP del operador está en la lista de exclusión.

**Acción contra Cloudflare, corregida.** El capítulo 02 autentica con la clave de API
global, que él mismo prohíbe, y apunta a un recurso de ámbito de usuario que no es
compatible con un token limitado a una zona. La acción del plan usa:

- Autenticación con **token de API** de alcance acotado a la zona y a la edición del
  cortafuegos, enviado como `Authorization: Bearer`.
- El punto de acceso **de la zona**, no el de la cuenta.
- El retiro del bloqueo **busca el identificador de la regla** por la IP antes de borrarla,
  porque el identificador cambia entre ejecuciones.

### 14.8 Registro del host

- journald con retención de 30 días y 2 GB de tope.
- Rotación propia para los registros de autenticación, del núcleo y de Fail2Ban: diaria,
  30 archivos, comprimidos, con permisos de lectura restringidos.
- El registro de auditoría **no entra en la rotación general**: tiene su propio ciclo.

---

## 15. Plano de los contenedores

### 15.1 Servicios

| Servicio | Imagen | Usuario | Red | Puertos | Sonda |
|---|---|---|---|---|---|
| `nginx` | `nginx:1.30.5-alpine` | no root | `edge`, `backend` | **80, 443 (los únicos)** | `/health` |
| `app` | `php:8.5-fpm-alpine` | no root (10001) | `backend`, `data` | — | `/ready` de la aplicación |
| `horizon` | misma que `app` | no root (10001) | `backend`, `data` | — | `horizon:status` |
| `scheduler` | misma que `app` | no root (10001) | `backend`, `data` | — | proceso vivo |
| `nuxt` | `node:24-alpine` | no root | `backend` | — | HTTP en su puerto interno |
| `postgres` | `postgres:18-alpine` | no root | `data` | — | `pg_isready` |
| `redis` | `redis:8-alpine` | no root | `data` | — | `PING` autenticado |
| `almacenamiento` | por decidir (§21.H) | no root | `data` | — | HTTP del servicio |
| `copia` | imagen propia | no root | `data` | — | ejecución programada |

### 15.2 Endurecimiento de cada contenedor

Se aplica a **todos**, sin excepciones, y se verifica automáticamente:

| Medida | Valor |
|---|---|
| Usuario | Sin privilegios, UID 10001, sin shell |
| Sistema de archivos | `read_only: true`, con `tmpfs` para lo que deba escribirse |
| Capacidades | `cap_drop: [ALL]`; sólo nginx recupera `NET_BIND_SERVICE` |
| Escalada de privilegios | `no-new-privileges: true` |
| Límites | Memoria, CPU y número máximo de procesos declarados |
| Bases | Fijadas por resumen, nunca por etiqueta |
| Herramientas de compilación | Ausentes de la imagen final |
| Registro | A la salida estándar; nunca a un archivo dentro del contenedor |

### 15.3 Dimensionamiento del servidor de aplicaciones

El capítulo 05 propone 50 procesos de PHP con un límite de 256 MB por proceso sobre un
contenedor de 512 MB: 12,8 GB de memoria potencial sobre una máquina de 8 GB. El contenedor
muere por falta de memoria en la primera carga real.

El plan fija una regla explícita y verificable:

```
pm.max_children = límite_de_memoria_del_contenedor × 0,75 ÷ memory_limit
```

Con 1 GB de límite y 128 MB por petición: **6 procesos**, 768 MB en el peor caso, con 256 MB
de margen para el proceso maestro y las extensiones. Se recalcula en la tarea **F10.3** con
medición real, no con estimación.

### 15.4 Configuración de cada servicio

**nginx.** Un solo punto de entrada, con:

- Redirección permanente de HTTP a HTTPS, salvo el desafío de renovación del certificado.
- TLS 1.2 y 1.3, con cifrados AEAD, sesiones sin reutilización entre clientes, y OCSP.
- HSTS de **dos años** con submundos y precarga.
- Cabeceras de seguridad **por superficie**: la API no necesita política de contenido de
  aplicación, y el sitio necesita una política con nonce.
- `server_tokens off` y eliminación de las cabeceras que revelan la tecnología.
- Zonas de límite de tasa para peticiones y conexiones simultáneas, con respuesta `429`.
- Tiempos de espera acotados contra el ataque de conexiones lentas.
- Tamaño máximo de cuerpo, y denegación explícita de métodos no previstos.
- Bloqueo de ejecución de PHP en cualquier ruta que no sea el punto de entrada de la API,
  que es la defensa contra un archivo subido que se convierta en ejecutable.

**Aplicación.** PHP-FPM con:

- `memory_limit` de 128 MB, opcache activado, y las funciones peligrosas deshabilitadas —
  **excepto** las que el propio arranque necesita, que es el defecto del capítulo 05.
- `expose_php` desactivado y errores **nunca** mostrados al cliente.
- La configuración se cachea en la compilación de la imagen, no en el arranque.

**Nuxt.** Servidor de Node sin privilegios, con `NODE_ENV=production`, memoria limitada y
caché de componentes acotada. La sonda de preparación espera a que el servidor responda
antes de que nginx empiece a enrutarle tráfico.

**PostgreSQL 18.** Configuración montada como archivo, con: memoria compartida y memoria de
trabajo dimensionadas al límite del contenedor, registro de conexiones y desconexiones,
registro de sentencias de modificación, registro de consultas lentas, auditoría con
`pgaudit`, cifrado de la conexión y método de autenticación `scram-sha-256`. Las cuentas se
crean en la inicialización, con **separación de roles**: una cuenta para la aplicación con
permisos de lectura y escritura sobre sus tablas —sin capacidad de modificar el esquema—,
otra para las migraciones, empleada únicamente durante el despliegue, y ninguna con
privilegios de superusuario.

**Redis 8.** Autenticación obligatoria con ACL por usuario, comandos peligrosos
renombrados o bloqueados (`FLUSHALL`, `FLUSHDB`, `CONFIG`, `DEBUG`, `SHUTDOWN`, `KEYS`),
persistencia con registro de anexado, y una política de expulsión que **no destruya datos
que no se pueden reconstruir**:

> El capítulo 04 y la implementación anterior usan `allkeys-lru`, que puede expulsar
> sesiones y trabajos en cola. Se usa **`volatile-lru`**: sólo se expulsa lo que tiene
> tiempo de vida declarado. La caché lleva tiempo de vida; las sesiones y la cola, no.

### 15.5 Redes

| Red | Quién vive | Quién la alcanza |
|---|---|---|
| `sede-edge` | nginx | Internet hasta nginx |
| `sede-backend` | aplicación, workers, Nuxt | nginx hasta la aplicación |
| `sede-data` | PostgreSQL, Redis, almacenamiento — **`internal: true`** | Sólo la aplicación y los workers |

### 15.6 Verificación automática

Una puerta comprueba, sobre la pila realmente resuelta: que sólo nginx publica puertos, que
los publica exactamente en 80 y 443, que ningún servicio de datos tiene mapeo alguno, que
la red de datos es interna, que ninguna imagen queda en etiqueta móvil, que ningún
contenedor corre como root ni con capacidades, y que todo servicio con sonda está sano.

---

## 16. Copias de seguridad y continuidad

### 16.1 Regla

Se aplica **3-2-1-1-0**: tres copias de los datos, en dos medios distintos, una de ellas
fuera del sitio, una inmutable, y **cero errores verificados** en la restauración.

> La copia que nunca se restauró no es una copia: es una esperanza. El simulacro mensual
> es lo que convierte un archivo en una garantía.

### 16.2 Qué se copia

| Elemento | Método | Frecuencia |
|---|---|---|
| Base de datos PostgreSQL | Volcado en formato propio, comprimido y cifrado | Diaria |
| Documentos y anexos | Copia incremental deduplicada del almacenamiento | Diaria |
| Configuración del entorno | Cifrada, fuera del repositorio | En cada despliegue |
| Imágenes de contenedor | Registro de paquetes, etiquetadas por confirmación | En cada entrega |
| Claves de cifrado | Custodia separada, **nunca** dentro de la copia | Manual, con registro |

Las imágenes no se copian: se reconstruyen desde la confirmación exacta, que es la razón
por la que cada versión se etiqueta con el identificador del commit.

### 16.3 Cómo

1. El volcado se produce **dentro** de la red de datos, desde un contenedor que habla con
   PostgreSQL por la red interna. Nunca desde el host, que no tiene acceso a la base.
2. El resultado se cifra con cifrado simétrico AES-256 antes de salir del servidor.
3. Se envía al almacenamiento de objetos fuera del droplet, con **bloqueo de objetos** que
   impide borrar o modificar durante la ventana de retención.
4. Se verifica que el archivo se puede abrir y que el volcado contiene el esquema esperado.
5. Sólo entonces se registra la copia como válida.

**Si un paso falla, la copia no se marca como buena** y salta la alerta. Un trabajo que
devuelve éxito sin haber producido un archivo legible es peor que uno que falla.

### 16.4 Retención

| Periodicidad | Copias | Cobertura |
|---|---|---|
| Diarias | 7 | Última semana |
| Semanales | 5 | Último mes |
| Mensuales | 12 | Último año |

El capítulo 04 anuncia «7/30/365» pero **sólo implementa el borrado a los 7 días**: ni la
retención mensual ni la anual existen en su configuración. Aquí las tres son reales y se
verifican contando lo que hay en el destino, no lo que la configuración promete.

### 16.5 Custodia de la clave de cifrado

El capítulo 04 guarda la frase de paso **en el mismo droplet que cifra**, y a la vez exige
que la clave viva separada del droplet. Es contradictorio y, en la práctica, inútil: quien
comprometa el servidor obtiene datos y clave.

El plan resuelve así:

| Copia de la clave | Dónde | Para qué |
|---|---|---|
| Operativa | Secreto del contenedor de copia, inyectado en el despliegue | Que la copia automática funcione |
| Custodia primaria | Gestor de secretos de la entidad | Restauración y rotación |
| Custodia de contingencia | Sobre cerrado en caja fuerte, con dos responsables | Recuperación si el gestor no está disponible |

El riesgo residual se declara: la copia operativa vive en el servidor porque el servidor
debe poder cifrar sin intervención humana. La contingencia real es la custodia externa.

### 16.6 Simulacro de restauración

**Mensual**, programado, y su resultado se archiva como evidencia:

1. Se toma la copia más reciente del almacenamiento externo.
2. Se descifra y se restaura en una base de verificación **aislada**, creada para el
   simulacro y destruida al terminar.
3. Se comparan recuentos de filas y el contenido del último expediente cerrado.
4. Se mide el tiempo total y se contrasta con el objetivo de recuperación.
5. Se archiva el acta con el identificador de la copia, la fecha, el resultado y el tiempo.

**Si el simulacro falla, el plan de continuidad se declara no verificado** hasta que se
corrija. No es un informe: es una puerta.

### 16.7 Objetivos de recuperación

| Objetivo | Valor propuesto |
|---|---|
| Punto de recuperación (RPO) | **≤ 24 horas** |
| Tiempo de recuperación (RTO) | **≤ 4 horas** |

El expediente se contradice aquí (H-11): pide RPO ≤ 1 hora en un documento y ≤ 24 horas en
otro. Se propone el valor conservador y verificable, y queda en §21.E.

### 16.8 Continuidad

Existe un procedimiento escrito que responde a: qué se hace si el droplet no arranca, si el
almacenamiento externo no responde, si la copia está corrupta y si hay que volver a la
versión anterior. Incluye los canales oficiales de reporte, la preservación de evidencia
antes de reconstruir, y la comprobación de que la versión anterior se recupera desplegando
su etiqueta.

---

## 17. CI/CD y despliegue automático

### 17.1 Canalizaciones

| Canalización | Disparador | Qué hace |
|---|---|---|
| `ci.yml` | Cada empujón a `develop`, `staging` y `main`, y cada PR | Todas las puertas (§19) |
| `despliegue.yml` | Empujón a `staging` o `main`, o ejecución manual | Comprueba, compila, escanea, publica y despliega |
| `entrega.yml` | Etiqueta `v*` | Compila, escanea, publica el inventario de componentes y **firma** las imágenes, verificando la firma |
| `vigilancia.yml` | Mensual | Recompila contra el estado del día y vuelve a escanear |

> **Defecto corregido.** En la implementación anterior, `entrega.yml` y `vigilancia.yml`
> estaban en el repositorio pero **GitHub no los registraba y nunca se ejecutaron**; y como
> no había ninguna etiqueta, la canalización de entrega no podía dispararse. En este plan
> la primera entrega crea su etiqueta y las cuatro canalizaciones se verifican ejecutadas
> antes de dar la fase por cerrada.

### 17.2 Despliegue

```
1. Comprobar    contrato, formato, análisis estático y pruebas contra
                PostgreSQL y Redis reales
2. Compilar     las imágenes, con las bases fijadas por resumen,
   y escanear   y publicarlas en el registro con la etiqueta del commit
3. Publicar     el inventario de componentes y firmar cada imagen
4. Desplegar    por SSH: traer las imágenes, migrar, sembrar,
                levantar y esperar a que la sonda de preparación responda
5. Volver atrás automáticamente si algo falla en cualquier paso
6. Comprobar    en un navegador real contra la sede publicada
```

Un despliegue automático sin puerta es una forma rápida de publicar un error: por eso el
paso 1 es obligatorio y bloqueante.

### 17.3 Reglas de la canalización

| Regla | Valor |
|---|---|
| Acciones de terceros | Fijadas por **confirmación de 40 caracteres**, nunca por etiqueta |
| Permisos | Mínimos por trabajo; escritura de paquetes sólo donde se publica |
| Secretos | Nunca en el YAML; por entorno y por repositorio |
| Umbral de escaneo | Falla con vulnerabilidad **alta o crítica corregible** |
| Concurrencia | Un despliegue por entorno; nunca dos a la vez |
| Vuelta atrás | Automática, a la etiqueta anterior, sin intervención |
| Entornos | `staging` y `production`, con revisores requeridos y política de ramas |

> **Defecto corregido.** La canalización anterior fijaba acciones por SHA pero **tres de
> ellas no eran confirmaciones reales**: dos apuntaban al objeto de una etiqueta anotada y
> una tercera usaba una imagen por etiqueta. El plan exige verificar que cada SHA
> corresponde a una confirmación existente, y la propia canalización lo comprueba.

### 17.4 Correspondencia entre rama y entorno

| Rama | Entorno | Aprobación |
|---|---|---|
| `develop` | Integración (sin despliegue) | — |
| `staging` | `staging` | Automática |
| `main` | `production` | **Dos aprobaciones previas** |

Al ser el repositorio público (D-08), las reglas de protección dejan de estar bloqueadas
por el plan de GitHub y las dos aprobaciones **sí pueden automatizarse**, que era
justamente lo que no podía hacerse antes.

### 17.5 Secretos de la canalización

| Secreto | Ámbito | Para qué |
|---|---|---|
| `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT` | Repositorio | Destino del despliegue |
| `DEPLOY_SSH_KEY` | Repositorio | Clave de despliegue, sin frase de paso |
| `DEPLOY_KNOWN_HOSTS` | Repositorio | Huella del servidor, para no aceptar cualquier anfitrión |
| `APP_KEY` | Por entorno | Cifrado de la aplicación |
| `DB_PASSWORD` | Por entorno | Contraseña de la base |
| `REDIS_PASSWORD` | Por entorno | Contraseña de la caché |
| `S3_ACCESS_KEY`, `S3_SECRET_KEY` | Por entorno | Almacenamiento de objetos |
| `BACKUP_PASSPHRASE` | Por entorno | Cifrado de las copias |
| `SEDE_PAGOS_SECRETO` | Por entorno | Firma de la pasarela, **la entrega el proveedor** |
| `SEDE_CORREO_CERTIFICADO_SECRETO` | Por entorno | Firma del operador postal, **la entrega el proveedor** |
| `CLOUDFLARE_API_TOKEN` | Por entorno | Acción de Fail2Ban y purga de caché |

`DEPLOY_KNOWN_HOSTS` no es ceremonia: sin él, `ssh` acepta la identidad que le presenten y
el despliegue queda expuesto a interposición.

Cada entorno tiene su **propio** juego de credenciales. Compartir la contraseña de la base
entre entornos haría que un incidente en staging alcanzara producción.

---

## 18. Observabilidad y operación

### 18.1 Alcance

El titular dejó fuera de alcance Wazuh, Prometheus y Grafana (D-15). La observabilidad se
resuelve con lo que ya existe y no añade superficie:

| Fuente | Qué aporta |
|---|---|
| journald del host | Sistema, autenticación, Fail2Ban, Docker |
| Registros de nginx | Accesos y errores, con la IP real del cliente |
| Canal de seguridad de la aplicación | Los cinco eventos obligatorios de SEG-013 |
| Registro estructurado de los contenedores | Aplicación, workers y servidor de Nuxt |
| Alertas de DigitalOcean | CPU, memoria, disco, carga y ancho de banda (§13.4) |
| Comprobación externa | Disponibilidad, validez del certificado |
| Informe de disponibilidad | Medición propia con objetivo declarado (FUN-053) |

> **Lo que se pierde al excluirlos, dicho con claridad.** Sin Wazuh no hay detección de
> integridad en tiempo real ni correlación de eventos; la vigilancia de integridad queda en
> AIDE, con verificación diaria, y el análisis es forense *a posteriori*, no en vivo. Sin
> Prometheus ni Grafana no hay series temporales ni paneles: la capacidad se observa con
> las métricas del proveedor, que son más gruesas. Es una decisión consciente y se registra
> como tal, no un olvido.

### 18.2 Sondas

| Sonda | Responde | Alcance |
|---|---|---|
| Vida (`/health`) | `200` **aunque una dependencia esté caída** | Sólo red interna |
| Preparación (`/ready`) | `200` sólo si base, caché y almacenamiento responden | Sólo red interna |

Ambas quedan **fuera** de la API versionada y **no** se exponen a Internet: publicar el
estado interno de la infraestructura es regalar reconocimiento.

### 18.3 Qué se vigila

- Disponibilidad medida y archivada, con informe contra el objetivo.
- Validez del certificado, con aviso a 14 días.
- Resultado de la copia diaria y del simulacro mensual.
- Paquetes de seguridad pendientes en el host.
- Contenido cuya revisión está vencida (FUN-058).
- Expedientes con retención cumplida y pendientes de disposición.
- Errores `5xx` y picos de `429`.

---

## 19. Pruebas y puertas de calidad

### 19.1 Puertas

Todas son ejecutables con una orden y todas corren en la canalización. **Ninguna se salta.**

| Puerta | Qué comprueba | Bloquea |
|---|---|---|
| `contrato` | Validez estructural, estilo y deriva entre contrato y rutas | Sí |
| `pruebas-backend` | Suite completa contra PostgreSQL y Redis reales | Sí |
| `analisis` | Análisis estático en nivel 8 | Sí |
| `formato-verificar` | Formato sin modificar archivos | Sí |
| `tipos` | Tipos del contrato regenerados y al día | Sí |
| `compilar` | Verificación de tipos y compilación de ambas aplicaciones | Sí |
| `unidad` | Pruebas unitarias del panel y del sitio | Sí |
| `conformidad` | Shell institucional y ficha del trámite en navegador real | Sí |
| `diseno` | Los 34 criterios de diseño, medidos sobre la página | Sí |
| `accesibilidad` | `axe-core` sobre todas las vistas públicas | Sí |
| `recorrido` | Flujo completo de PQRSD como lo vive el ciudadano | Sí |
| `infra` | Puertos, privilegios, TLS y sondas sobre la pila resuelta | Sí |
| `seguridad` | Dependencias, código propio y servicio en marcha | Sí |
| `imagenes` | Fijación por resumen e inventario de componentes | Sí |
| `respaldo` | Crear, abrir y restaurar una copia real | Sí |
| `trazabilidad` | Genera la matriz desde el contrato y las pruebas | No (artefacto) |

### 19.2 Niveles de prueba

| Nivel | Backend | Frontend |
|---|---|---|
| Unitario | Servicios, políticas y utilidades | Composables, esquemas y servicios |
| Integración | Repositorios, jobs y adaptadores | Cliente HTTP y stores |
| Funcional | Cada operación del contrato | Vistas y flujos |
| Extremo a extremo | — | Recorrido completo del ciudadano en navegador real |
| Seguridad | Inyección, errores, endurecimiento, auditoría y límites de tasa | — |
| Accesibilidad | — | `axe-core` sobre todas las vistas |
| Diseño | — | Los criterios CAG medidos |
| Carga | Umbral de concurrencia objetivo | Portada y ficha de trámite |

**Cobertura mínima del 70 %** en el backend, medida en la canalización.

### 19.3 Auditoría de módulo

Ningún módulo se marca como terminado sin superar **25 puntos más 7 de contrato**. Los
puntos cubren: campos y relaciones, forma plana del Resource, validación de alta y de
actualización, autorización por política, reglas en el servicio, campos autogenerados,
tipado del frontend contra el contrato, formularios espejo, botones por permiso, guardias
de ruta, coherencia de permisos entre backend y frontend, sobre plano correcto, las siete
claves de `meta`, las cuatro de `links`, errores con `success: false`, `422` cuando
`per_page` supera 100, y datos personales enmascarados.

### 19.4 Definición de terminado

Una funcionalidad está terminada cuando **todas** se cumplen:

- [ ] Su operación existe en el contrato y el simulador la responde.
- [ ] El backend la implementa con las capas y las políticas correspondientes.
- [ ] El panel y, si es pública, el sitio la consumen con tipos generados.
- [ ] Existe prueba unitaria, funcional y, si hay flujo, extremo a extremo.
- [ ] Pasa las puertas de accesibilidad, diseño e infraestructura.
- [ ] Supera la auditoría de módulo de 25 puntos más 7.
- [ ] Los criterios del expediente que acredita están citados y son trazables.
- [ ] Está verificada visualmente en navegador real, en escritorio y en móvil.

---

## 20. Fases, entregables y cronograma

Cada fase se compone de **cortes verticales**: base de datos, API, servicio, panel, pruebas
y validación visual, en ese orden y sin saltos. No se abre una fase sin cerrar la anterior.

Tamaño relativo: **S** (días), **M** (una o dos semanas), **L** (varias semanas).

### FASE 0 — Fundación

| # | Tarea | Tamaño |
|---|---|---|
| F0.0 | **Ampliar el droplet a 8 GB de memoria** desde el panel de DigitalOcean; la ampliación de CPU y memoria no toca el disco | S |
| F0.1 | Inicializar el repositorio en el directorio de trabajo y configurar el remoto | S |
| F0.2 | Crear `main` huérfana, vaciar el árbol, publicar y fijarla por defecto | S |
| F0.3 | **Verificar el acceso al registro de imágenes endurecidas** y que las extensiones de PHP se puedan añadir (§21.J) | S |
| F0.4 | Crear `develop` y `staging`; borrar las tres ramas antiguas | S |
| F0.5 | Pasar el repositorio a público y configurar la protección de las tres ramas | S |
| F0.6 | Esqueleto de `backend/`, `panel/` y `sitio/` con sus versiones exactas (§4) | M |
| F0.7 | Contrato semilla: sobre plano, cinco esquemas compartidos, primer recurso completo | M |
| F0.8 | Simulador de Prism levantado y respondiendo | S |
| F0.9 | `compose.yaml` y `compose.override.yaml` con las tres redes y todos los servicios | M |
| F0.10 | Endurecimiento del host (§14) sobre el droplet `198.199.89.119` | L |
| F0.11 | Certificado emitido para `staging.santamarta.gov.co` y renovación automática | S |
| F0.12 | Las cuatro canalizaciones escritas, con acciones fijadas por confirmación real | M |
| F0.13 | Entornos `staging` y `production` recreados con sus secretos y aprobaciones | S |
| F0.14 | `Makefile` con todas las puertas y un primer `make comprobar` ejecutable | M |
| F0.15 | **Primer despliegue automático completo** con una página mínima | M |
| F0.16 | **Confirmar con el ministerio la vigencia de los anexos de integración** antes de cerrar el diseño (§21.F.1) | S |

**Criterio de salida:** la sede responde en `https://staging.santamarta.gov.co` con TLS
válido, cabeceras de seguridad, sonda de preparación en verde, y el despliegue se dispara
solo desde la rama. Las cuatro canalizaciones se han ejecutado al menos una vez.

### FASE 1 — Shell institucional y accesibilidad

| # | Tarea | Tamaño |
|---|---|---|
| F1.1 | Vendorizar el Kit gov.co y fijar su integridad | S |
| F1.2 | Componentes del Kit como componentes Vue (§11.3) | L |
| F1.3 | Barra superior con la galería de aplicaciones | M |
| F1.4 | Cabecera con el escudo de la entidad, buscador y menú | M |
| F1.5 | Barra de accesibilidad gobernada por Vue | M |
| F1.6 | Pie con los ocho datos y las cinco políticas, servidos por la API | M |
| F1.7 | Migas de pan, salto al contenido y volver arriba | S |
| F1.8 | Disposiciones del sitio y del panel | M |
| F1.9 | Puertas `diseno`, `accesibilidad` y `conformidad` en verde | M |

**Criterio de salida:** las tres puertas de conformidad pasan sobre las vistas construidas,
en escritorio y en móvil, con capturas archivadas.

### FASE 2 — Motor editorial

| # | Tarea | Tamaño |
|---|---|---|
| F2.1 | Tipos de contenido, campos y validación por campo | L |
| F2.2 | Flujo editorial con los cinco estados y sus transiciones | L |
| F2.3 | Versionado con restauración | M |
| F2.4 | Publicación y retiro programados, con tarea idempotente | M |
| F2.5 | Biblioteca de medios con texto alternativo obligatorio | M |
| F2.6 | Taxonomías y series documentales | M |
| F2.7 | Menús con los límites del expediente | M |
| F2.8 | Redirecciones 301 con verificación de destino | S |
| F2.9 | Registro de auditoría con restauración | M |
| F2.10 | Pantallas del panel para todo lo anterior | L |
| F2.11 | Verificación de ficha completa antes de publicar | M |

**Criterio de salida:** se puede crear, revisar, versionar, programar, publicar, archivar y
restaurar un contenido sin tocar la base de datos.

### FASE 3 — Contenido institucional y navegación pública

| # | Tarea | Tamaño |
|---|---|---|
| F3.1 | Entidad, dependencias, canales de atención y sedes | M |
| F3.2 | Bloques de portada administrables | M |
| F3.3 | Páginas institucionales y las cinco políticas | M |
| F3.4 | Portada pública con contenido real | M |
| F3.5 | Mapa del sitio | S |
| F3.6 | **Reconocimiento con navegador real** de las fuentes oficiales, para obtener el catálogo y los códigos de trámite (§12.14) | M |

**Criterio de salida:** FUN-007, FUN-009, FUN-010, FUN-011, FUN-013, FUN-014, FUN-015.

### FASE 4 — Trámites y buscador

| # | Tarea | Tamaño |
|---|---|---|
| F4.1 | Catálogo de trámites con los seis atributos obligatorios | L |
| F4.2 | Categorías, requisitos, pasos y documentos | M |
| F4.3 | Ficha del trámite con línea de avance y direccionamiento | M |
| F4.4 | Buscador interno con búsqueda de texto completo, sin acentos | L |
| F4.5 | Paginación y filtros | M |
| F4.6 | Carga de los trámites reales de la entidad | M |

**Criterio de salida:** FUN-001, FUN-002, FUN-021, FUN-022, FUN-023.

### FASE 5 — Radicación, PQRSD y expediente

| # | Tarea | Tamaño |
|---|---|---|
| F5.0 | **Verificar si el trámite es «trámite modelo»** antes de diseñar cualquier formulario: si lo es, el formulario único es de obligatoria observancia y no admite pasos ni requisitos adicionales (§12.4) | S |
| F5.1 | Radicación con numeración y acuse de recibo, con control de fecha y hora | L |
| F5.2 | PQRSD con tipificación, aviso de privacidad y autorización de datos | L |
| F5.3 | Captcha del servidor, no reutilizable | M |
| F5.4 | Anexos y documentos con validación real | M |
| F5.5 | Expediente con sellado de integridad al cierre | L |
| F5.6 | Estados, eventos públicos y seguimiento por radicado | M |
| F5.7 | Verificación pública por código | M |
| F5.8 | Traslados por competencia | M |

**Criterio de salida:** FUN-004, FUN-024, FUN-025, FUN-029, FUN-030, FUN-031, FUN-032,
FUN-035, FUN-036, SEG-003, SEG-009.

### FASE 6 — Notificaciones y pagos

| # | Tarea | Tamaño |
|---|---|---|
| F6.1 | Bandeja interna, correo y acuse por los canales obligatorios | L |
| F6.2 | Preferencias del ciudadano, con canales no desactivables | M |
| F6.3 | Correo certificado para actos administrativos, con constancia | L |
| F6.4 | Órdenes de pago con valor fijado por el catálogo | M |
| F6.5 | Pasarela con webhook firmado e idempotente | L |
| F6.6 | Conciliación y comprobante consultable | M |

**Criterio de salida:** FUN-037, FUN-038, FUN-039, FUN-040, FUN-041, FUN-042.

### FASE 7 — Panel del ciudadano

| # | Tarea | Tamaño |
|---|---|---|
| F7.1 | Resumen, mis radicados y mis PQRSD | M |
| F7.2 | Mis documentos y mis pagos | M |
| F7.3 | Datos de contacto con reverificación en cambios sensibles | M |
| F7.4 | Portabilidad y descarga en formato abierto | M |
| F7.5 | Solicitudes de supresión y su trazabilidad | M |

**Criterio de salida:** FUN-043, FUN-044, FUN-045, FUN-046, O-13.

### FASE 8 — Transparencia, participación y datos abiertos

| # | Tarea | Tamaño |
|---|---|---|
| F8.1 | Conjuntos de transparencia según el anexo aplicable (§12.10) | L |
| F8.2 | Publicación activa con fecha y orden descendente | M |
| F8.3 | Buscador acotado a transparencia | M |
| F8.4 | Noticias con archivo histórico | M |
| F8.5 | Participación: portales, encuestas y control social | M |
| F8.6 | Datos abiertos con licencia declarada y formato abierto | L |
| F8.7 | Declaración de conformidad de accesibilidad | S |

**Criterio de salida:** FUN-016 a FUN-020, FUN-026 a FUN-028, FUN-059.

### FASE 9 — Sitio público con posicionamiento

| # | Tarea | Tamaño |
|---|---|---|
| F9.1 | Metadatos por ruta generados desde el contenido | M |
| F9.2 | Datos estructurados y mapa del sitio | M |
| F9.3 | Redirecciones desde las URLs anteriores del portal | M |
| F9.4 | Política de contenido con nonce sobre el HTML del servidor | M |
| F9.5 | Presupuesto de rendimiento y su verificación | M |

**Criterio de salida:** las páginas públicas se renderizan en servidor, con metadatos,
mapa del sitio y presupuesto de rendimiento en verde.

### FASE 10 — Endurecimiento, auditoría y carga

| # | Tarea | Tamaño |
|---|---|---|
| F10.1 | Revisión completa contra el capítulo 01 y el capítulo 02 | M |
| F10.2 | Revisión de TLS y cabeceras contra los objetivos de calificación | M |
| F10.3 | Recálculo del dimensionamiento de PHP con carga real | S |
| F10.4 | Prueba de carga contra el objetivo de concurrencia | M |
| F10.5 | Simulacro de restauración con acta | S |
| F10.6 | Análisis de vulnerabilidades, dependencias y código propio | M |
| F10.7 | Revisión de accesibilidad manual con lector de pantalla | M |
| F10.8 | Procedimiento de respuesta a incidentes, con simulacro | M |

**Criterio de salida:** cero hallazgos críticos sin remediar, y el acta de simulacro
archivada.

### FASE 11 — Producción

| # | Tarea | Tamaño |
|---|---|---|
| F11.1 | Decidir el dominio de producción (§21.A) y emitir su certificado | S |
| F11.2 | Verificar las reglas de Cloudflare sobre el dominio definitivo | S |
| F11.3 | Ventana de cambio y despliegue a producción con aprobaciones | S |
| F11.4 | Piloto cerrado con usuarios reales | M |
| F11.5 | Transferencia al equipo de operación y capacitación | M |

**Criterio de salida:** sede en producción, con objetivo de disponibilidad declarado y
cumplido durante el primer trimestre.

---

## 21. Decisiones por ratificar

**Ninguna de estas decisiones se implementa antes de la ratificación.** Se listan con la
propuesta, el motivo y la evidencia, para que la decisión sea informada.

### A. Dominio de producción — **bloqueante para la fase 11**

Único dato que falta para cerrar la arquitectura.

- **Propuesta:** `sede.santamarta.gov.co`, con `staging.santamarta.gov.co` como entorno de
  pruebas.
- **Motivo:** el expediente exige dominio canónico único (O-01) y el artículo 14 del
  Decreto Ley 2106 de 2019 exige una sola sede por autoridad. Un subdominio dedicado
  separa la sede del portal institucional, que hoy es un Drupal sin soporte.
- **Alternativa:** `www.santamarta.gov.co`, sustituyendo el portal actual.

### B. Versión de Redis

- **Propuesta:** **Redis 8.10**.
- **Motivo:** todos los documentos fijan Redis 7, pero la versión estable actual es la 8.
  El titular pidió «lo más actual que sea estable» para nginx; el mismo criterio aplica
  aquí. `redis:7-alpine` sigue existiendo y es mantenido, así que la decisión es de
  política, no de disponibilidad.
- **Impacto si se mantiene la 7:** ninguna funcionalidad cambia; se sale de la línea
  principal.

### C. Bootstrap frente a Tailwind

- **Propuesta:** **Bootstrap 5.0.2** y los tokens del Kit gov.co. Sin Tailwind.
- **Motivo:** el criterio **CAG-33 es bloqueante** y exige construir sobre Bootstrap 5.0.2
  y las variables `--govcolor-*`; además, el *preflight* de Tailwind y el *reset* de
  Bootstrap se pisan entre sí y alterarían contrastes y espaciados ya validados.
- **Conflicto con la guía maestra:** su §4.1 declara TailwindCSS v4 como stack oficial y su
  §2.2 lo instala. La guía no menciona gov.co en ninguna de sus 3.312 líneas. Se propone
  **enmendar la guía maestra** y registrar un ADR.
- **Si se prefiere Tailwind:** habría que declarar CAG-33 como desviación y asumir el
  riesgo de contraste en una sede cuya accesibilidad es un atributo legal.

### D. Criterios citados pero nunca definidos

`ACC-001` a `ACC-008`, `RN-01` a `RN-03` y `RT-01` a `RT-05` se usan como referencia en la
Sección 6 y en la matriz de trazabilidad, pero **ninguna sección del expediente los
define**. `RT-02` además significa dos cosas distintas según dónde se lea.

- **Propuesta:** sustituirlos por lo que sí está definido y es verificable — **WCAG 2.1 AA**
  para accesibilidad y los **artículos del Decreto Ley 2106** para el marco normativo — y
  registrar la desviación en el acta de conformidad.
- **Alternativa:** que la entidad los redacte, y congelar su implementación hasta entonces.

### E. Objetivos de disponibilidad y recuperación

El expediente se contradice: disponibilidad del **95 %** en FUN-053 y del **99 %** en §6.8;
punto de recuperación de **≤ 1 hora** en un documento y **≤ 24 horas** en otro; tiempo de
recuperación de **≤ 4 horas**.

- **Propuesta:** disponibilidad **≥ 99 % mensual**, punto de recuperación **≤ 24 h** y
  tiempo de recuperación **≤ 4 h**.
- **Motivo:** el 99 % es el objetivo que la propia sección de implementación exige para el
  cierre y contiene al 95 %; el punto de recuperación se propone conservador y verificable
  antes que ambicioso e incumplido.

### F.1 Dirección de la integración con el Portal Único — resuelta por la norma

Deja de ser una decisión por ratificar. La investigación de las fuentes oficiales la cerró
con cita textual del anexo de integración de la Resolución 2893 de 2020:

> **GOV.CO redirige hacia la sede de la entidad. La entidad no redirige al portal.**

`FUN-023` queda por tanto como un **defecto de redacción del expediente**, no como una
instrucción de arquitectura: dice lo contrario de lo que dice la norma que el propio
expediente invoca. Se registra en el acta de conformidad y se implementa lo que manda la
norma: la sede **conserva su dominio y radica en sí misma**, y el módulo de radicación, el
de pagos y el de notificaciones existen y son propios.

Lo único que queda pendiente de este frente es **confirmar la vigencia de los anexos**: el
artículo 8 de la Resolución 2893 autoriza su actualización sucesiva, los anexos recuperados
son de 2020 y en 2025 se publicó material nuevo. **No se cierra el diseño sobre los anexos
de 2020 sin confirmar su vigencia con el ministerio.** Es la tarea F0.16.

Detalle completo en §12.3.

### F.2 Confirmaciones pendientes con terceros

Estos puntos no cambian el diseño pero **sí condicionan la puesta en producción**, y ninguno
depende del equipo:

| Punto | Estado | Cuándo hace falta |
|---|---|---|
| Guía de vinculación y uso de los Servicios Ciudadanos Digitales aplicable a entidades públicas | **No recuperada**; los estándares se acreditaron desde la guía de prestadores privados | Antes de la fase 6 |
| Anexos de ventanillas únicas y portales transversales | No recuperados | Antes de la fase 8 |
| Formulario de solicitud de integración, convenios y credenciales de identidad | **No públicos**: requieren solicitud formal | Antes de la fase 6 |
| Operadores habilitados de notificación electrónica de actos administrativos | **No confirmado** con fuente oficial | Antes de la fase 6 |
| Guía de expedientes electrónicos del archivo general | No disponible; su dominio no resuelve | Antes de la fase 5 |
| Vigencia de los anexos de integración | Anunciada su actualización sucesiva | **F0.16, antes de cerrar el diseño** |

### G. Proveedor de correo

El expediente exige notificación por correo con acuse en dos minutos, y la guía de
seguridad deja el correo transaccional fuera de su alcance.

- **Propuesta:** decidir el proveedor antes de la **fase 6**.
- **Mientras no haya decisión:** el envío queda en el adaptador `mock` y la interfaz no
  promete lo que no hace.

### H. Almacenamiento de objetos

- **Propuesta (a decidir antes de la fase 0):** **almacenamiento de objetos del propio
  proveedor** (DigitalOcean Spaces), en lugar de un servicio autoalojado.
- **Motivo:** un contenedor menos que endurecer, mantener y copiar; es también el destino
  natural de las copias de seguridad; y soporta bloqueo de objetos, que es lo que la regla
  de copias inmutables necesita.
- **Alternativa:** servicio autoalojado compatible, que la entidad controla por completo
  pero que hay que operar y respaldar. Lo importante es que **el contrato con el
  almacenamiento sea S3**, no un producto: cambiar de proveedor no debe tocar el dominio.

### I. Versión de TypeScript

- **Propuesta:** empezar con la versión que **soporte oficialmente `vue-tsc`** y subir a la
  actual cuando la cadena de herramientas lo soporte.
- **Motivo:** la versión más reciente del lenguaje es la 7, y la guía maestra trabaja sobre
  la 5. Verificar la compatibilidad de `vue-tsc`, ESLint y Nuxt es la tarea **F0.3**, y su
  resultado decide. Fijarlo sin verificar es cómo se rompe una compilación a la semana.

### J. Acceso al registro de imágenes endurecidas — **resuelta**

**Verificado el 2026-09-30: el registro rechaza el acceso anónimo.**

```
docker pull dhi.io/php:8.5-fpm
  → failed to authorize: failed to fetch anonymous token: 401 Unauthorized
```

Docker publica que esas imágenes son gratuitas, pero **exigen autenticarse con una cuenta de
Docker Hub**, y no hay credenciales de registro ni en el servidor ni en la canalización.
Las imágenes oficiales sí responden: `php`, `nginx`, `postgres`, `redis` y `node` devuelven
`200`.

**Decisión del titular: imágenes oficiales fijadas por resumen, con el endurecimiento por
configuración** —usuario sin privilegios, sistema de archivos de sólo lectura, sin
capacidades, sin escalada, bases fijadas por resumen y escaneo bloqueante en cada
compilación—, que es además lo que Docker recomienda con independencia de la imagen base.

Consecuencia: se elimina una dependencia externa del proceso de construcción y del de
despliegue. La imagen endurecida deja de ser un requisito y pasa a ser una mejora posible
para más adelante, si la entidad obtiene cuenta.

### K. Datos institucionales sin confirmar

| Dato | Estado |
|---|---|
| Dirección completa con municipio y departamento | Falta el municipio y el departamento |
| Línea gratuita nacional | Sólo consta en un documento de 2019 |
| Línea anticorrupción | Es el mismo número que atención al ciudadano |
| Código postal | La entidad no lo publica |
| Logotipo oficial en formato vectorial | Existe y es descargable; falta incorporarlo |
| Correo de notificaciones judiciales | Confirmado en el sitio, pendiente de acto administrativo |

- **Propuesta:** publicar sólo lo confirmado, dejar el resto marcado como pendiente y
  solicitarlo formalmente a la entidad antes de la fase 11.

### L. Contenidos obligatorios de transparencia

- **Propuesta:** adoptar el anexo de la Resolución 1519 de 2020 que la propia entidad ya
  replica en su portal, y **superarlo**: formatos abiertos, fechas de publicación, orden
  descendente, sin duplicidad y con buscador propio.
- **Motivo:** el expediente no enumera los contenidos; la entidad ya tiene una lista de
  facto, con 202 documentos pero sin un solo formato abierto.

### M. Longitud mínima de contraseña

- **Propuesta:** **15** caracteres.
- **Motivo:** el capítulo 01 fija 14, el expediente recomienda 15 y el mínimo legal es 8.
  Quince satisface a los tres.

---

## 22. Riesgos y mitigaciones

| Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|
| ~~El registro de imágenes endurecidas no es accesible~~ | — | — | **Resuelto en F0.3:** exige autenticación; se usan imágenes oficiales fijadas por resumen con endurecimiento por configuración |
| Las credenciales de terceros no llegan a tiempo | Alta | Medio | Adaptadores `mock` y `sandbox`: el flujo es verificable sin ellas |
| El dominio de producción se decide tarde | Media | Alto | Bloquea sólo la fase 11; el resto avanza sobre staging |
| La entidad no confirma los datos del pie | Alta | Medio | Se publica sólo lo confirmado y se marca lo pendiente |
| El capítulo 03 tiene defectos que impiden arrancar nginx | **Cierta** | Alto | El plan no lo copia: reconstruye la configuración y la verifica con `nginx -t` en la canalización |
| El capítulo 02 deja el servidor inaccesible | **Cierta** | Crítico | El cortafuegos se define en §13.2 y §14.6, con el 22 restringido a la IP del operador |
| El dimensionamiento de PHP provoca falta de memoria | Alta | Alto | Fórmula explícita (§15.3) y recálculo con carga real (F10.3) |
| La copia de seguridad nunca se restaura | Media | Crítico | Simulacro mensual como puerta, con acta archivada |
| Deriva entre el contrato y la implementación | Media | Medio | Prueba de deriva bloqueante en cada PR |
| Regresión de accesibilidad al añadir componentes | Media | Alto | Las puertas CAG comprueban **también la ausencia** de los componentes no aplicados |
| **Diseñar un formulario propio para un trámite modelo** | Media | Alto | Verificación bloqueante F5.0 **antes** de diseñar; si es modelo, se consume el formulario único |
| Los anexos de integración quedan desactualizados | Media | Medio | F0.16 confirma vigencia con el ministerio antes de cerrar el diseño |
| Un tercero no confirma operadores de notificación ni credenciales | Alta | Medio | Adaptadores `mock`: el flujo es verificable y la interfaz no promete lo que no hace |
| Crecimiento descontrolado del alcance | Alta | Medio | Cada fase tiene criterio de salida; lo nuevo entra como fase nueva |
| Dependencia de un único droplet | Media | Alto | Copias fuera del sitio y contingencia documentada; el proveedor no cubre HA en este alcance |

---

## 23. Trazabilidad con el expediente

El expediente fija **140 criterios**, de los cuales **122 tienen evidencia** y **18 no**:

`ACC-001`, `ACC-003`, `ACC-004`, `ACC-007`, `ACC-008`, `FUN-005`, `FUN-006`, `FUN-047`,
`FUN-049`, `FUN-050`, `FUN-051`, `FUN-052`, `O-02`, `O-06`, `O-09`, `O-11`, `O-12`, `RT-03`.

Ese listado es la lista de trabajo pendiente y **su reducción es la medida de avance**.
Cinco de ellos (`ACC-*`) no se pueden cerrar sin la decisión §21.D; el resto se cierra con
trabajo del plan:

| Criterio | Se cierra en |
|---|---|
| FUN-005 — campos obligatorios y enlaces a políticas | F1.6, F5.2 |
| FUN-006 — mensaje de error por campo | F5.2 |
| FUN-047 — guía de diseño del Kit UI 9.2 | F1.9 |
| FUN-049 — estilos separados del contenido | F1.2 |
| FUN-050 — independencia del navegador | F10.4 |
| FUN-051 — versión móvil | F1.9 |
| FUN-052 — encuesta de usabilidad | F8.5 |
| O-02 — los seis atributos de calidad | F10.1 a F10.6, con las puertas de §19 |
| O-06 — integración con GOV.CO | F0.16 y §12.3 |
| O-09 — consulta a la Registraduría | §12.2 |
| O-11 — WCAG 2.1 AA con auditorías | F10.7 |
| O-12 — modelo de seguridad y privacidad | F10.8 |
| RT-03 — renovación automática del certificado | F0.11 |

La matriz se genera automáticamente desde el contrato, las pruebas y las vistas (§6.6). Deja
de escribirse a mano y deja de reproducir los vínculos cruzados erróneos del expediente.

---

## Cierre

Este plan resuelve lo que los documentos dejan abierto, corrige los defectos que impedirían
que la pila arrancara, y **no implementa nada que no esté decidido**. Las decisiones de la
§21 son las únicas puertas que quedan antes de escribir la primera línea de código.

El orden es el de las fases: **infraestructura primero, contrato antes que código, corte
vertical en cada funcionalidad, y ninguna fase se cierra con una puerta en rojo.**


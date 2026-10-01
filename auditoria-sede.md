# Auditoría del diseño de la Sede Electrónica

**Titular:** Alcaldía Distrital de Santa Marta · NIT 891.780.009-4
**Objeto:** contrastar lo construido en `sitio/` y `backend/` contra la documentación del
proyecto (`sede-electronica-doc/`), la Resolución MinTIC 1519 de 2020, el Anexo 2 de la
Resolución 2893 de 2020 y el Kit UI gov.co 9.2.
**Fecha de la auditoría:** la de este documento.
**Naturaleza:** auditoría de diseño. Señala qué falta o está mal, no reescribe nada.

---

## 0. Aviso sobre el momento de la auditoría

Esta auditoría se escribió **antes** de que se retirara la sección de Transparencia del
frontend. En aquel momento existía `sitio/app/types/transparencia.ts` —un fichero de
2 684 líneas con las nueve categorías y 333 documentos— y el documento lo cita en
**trece lugares** como «dónde» de varios hallazgos.

**Ese fichero ya no existe.** Se retiró por decisión del titular: el contenido
institucional no vive en el frontend; el frontend construye diseño y consume datos, y el
conocimiento de cada sección vive en su propio `.md`. La reversión está en `develop` y
`staging` (commit `06503f0`), y el análisis de aquel trabajo se conserva en
`docs/transparencia.md`.

**Cómo leer las referencias a `transparencia.ts`:** describen el **estado anterior**, y
siguen siendo útiles como prueba de lo que se había construido y de los defectos que se
encontraron en la fuente —el `<liclass>` que hundía 88 documentos, la trampa de los
`ds_*.json`—. Pero **ningún hallazgo se arregla ya en ese fichero**: los que lo citaban
se resuelven en el backend, y el propio hallazgo B-08 lo dice.

**Lo que no cambia:** el veredicto, todos los bloqueantes y el orden de corrección. Al
contrario, el bloqueante B-08 sale **reforzado** —Transparencia no se puede reconstruir
con contenido en el frontend— y hoy la sección vuelve a ser un aviso de «en preparación»
de 17 líneas, que es un estado más atrasado y no menos.

---

## 1. Alcance y método

### 1.1 Qué se auditó

| Objeto | Cómo se miró |
|---|---|
| `sitio/` — armazón, componentes y 21 rutas | lectura de `layouts/default.vue`, los 11 componentes de `components/govco/`, las 21 páginas de `app/pages/`, `assets/css/sitio.css`, `types/`, `server/routes/sitemap.xml.ts`, `public/`, `nuxt.config.ts`, `package.json` |
| `backend/` — API Laravel 13 | `routes/api.php`, `app/Models/`, `app/Services/`, `app/Enums/`, `app/Http/`, `app/Policies/`, `app/Console/Commands/`, `database/migrations/` |
| `contract/openapi.yaml` | las tres operaciones declaradas y sus esquemas |
| `sede-electronica-doc/` (12 secciones + `_bd/` + `_global/`) | cabeceras de alcance de las 12 secciones, módulo 01 completo, módulo 02, y los ficheros que fijan requisitos verificables |
| Corpus normativo | `investigacion/raw/nat/res1519.html` (convertido a texto), `research/gov/anexos/anexo2_1.txt` |
| Kit UI gov.co 9.2 | `public/govco/all.css` (12 286 líneas) medido contra el PDF `~/Descargas/kit-ui-9-2.pdf`; tabla de componentes de `docs/Sección 2 · Diseño.md` |
| Puertas de calidad | `.github/workflows/ci.yml`, `.github/workflows/despliegue.yml`, `Makefile` |

### 1.2 Qué quedó fuera

- **No se ejecutó el sitio** ni se levantó el servidor. Todo lo afirmado sobre el diseño
  construido sale de leer el código, no de una navegación real. No hay medición de
  contraste en navegador, ni de reflujo a 320 px, ni de área táctil a 360 px hecha en esta
  auditoría: las cifras de área táctil que aparecen citadas son **las que el propio
  `sitio.css` documenta como medidas previamente**, y se citan como tales.
- **No se auditaron** `panel/`, `docker/`, `deploy/`, `compose.yaml` ni la seguridad del
  aprovisionamiento: son objetos de otras auditorías y el encargo es el diseño de la sede.
- **No se auditó `plan.md`** como fuente de requisitos: se usó sólo para localizar
  decisiones ya tomadas.
- **No se verificó la exactitud de los 441 registros** de `app/types/transparencia.ts`
  contra el sitio real de la Entidad. Se comprobó su **estructura**, no su contenido.
- **No se pudo verificar** el estado del despliegue en `staging` (la afirmación de que el
  catálogo está vacío procede de una sesión anterior; **no la he comprobado aquí** y por
  eso figura marcada como tal en el hallazgo B-12).
- **No se pudo verificar** el contenido literal del Anexo 2 de la Resolución 2893 en su
  totalidad: se trabajó con `research/gov/anexos/anexo2_1.txt` (69 189 bytes) y con las
  citas que el propio código del sitio hace del anexo.

### 1.3 Método

Para cada afirmación sobre lo construido se hizo `read`, `grep` o `ls` y se cita archivo y
línea. Cuando una conclusión es una **deducción mía** y no un hecho leído, va marcada con
`[DEDUCCIÓN]`. Cuando un dato procede de una sesión anterior y no lo he vuelto a comprobar,
va marcado con `[SIN VERIFICAR]`.

---

## 2. Veredicto

**El diseño está a medio construir, con la norma de publicación resuelta y la norma de
operación sin resolver:** la estructura, la identidad, el catálogo de trámites y las nueve
categorías de Transparencia están construidos con un rigor poco común, pero la sede todavía
no puede recibir una sola solicitud del ciudadano —no hay formulario que radique, ni acuse
de recibo, ni seguimiento, ni captcha, ni consentimiento de cookies— y hay dos secciones
obligatorias (`Participa` y `Servicios`) reducidas a avisos de «en preparación».

---

## 3. Hallazgos

### 3.1 Bloqueantes

Un hallazgo es **bloqueante** cuando su ausencia impide cumplir un requisito legal de
obligado cumplimiento o impide que la sede preste el servicio que la define.

---

#### B-01 · No existe consentimiento de cookies

- **Qué falta.** El sitio no tiene banner de cookies, ni registro de consentimiento, ni
  mecanismo de revocación. `grep -rni "cookie" sitio/app/` devuelve **cuatro coincidencias**
  y las cuatro son enlaces o rótulos: el rótulo de la política en
  `sitio/app/pages/politicas/[slug].vue:48-52`, su enlace en el pie
  (`components/govco/PiePaginaGovco.vue:218`), su enlace en el mapa del sitio
  (`pages/mapa-del-sitio.vue:103`) y su inclusión en el sitemap
  (`server/routes/sitemap.xml.ts:75`). **No hay componente, ni lógica, ni tabla.**
- **Norma.** Res. MinTIC 1519/2020, Anexo 2 §2.3.4 (políticas mínimas en el pie); Ley 1581
  de 2012; el propio expediente en RF-B1-008 / RF-B2-011 (Must), RF-01-D01, RN-01-D01,
  HU-01-D01. `plan.md` §11.3 lista `AvisoCookies` y `AvisoPrivacidad` entre los catorce
  componentes mínimos del sitio.
- **Dónde.** Ausencia total en `sitio/app/`.
- **Qué hay que hacer.** Construir el componente de aviso con aceptar / rechazar /
  configurar por categoría, persistir el consentimiento **con versión de la política y
  fecha** (RF-01-D01), y caducarlo a los doce meses (RN-01-D01). Hasta que exista, ninguna
  cookie no esencial puede activarse.

---

#### B-02 · No hay CAPTCHA ni ningún control antispam

- **Qué falta.** `grep -rni "captcha\|recaptcha\|turnstile" sitio/ backend/app backend/config`
  sólo encuentra coincidencias dentro de `sitio/node_modules/` (los *stubs* de Nuxt). **Cero
  en código propio.** El formulario de `sitio/app/pages/realizar-una-peticion.vue` (1 097
  líneas) termina en `enviar()` (líneas 359-378) sin ningún control de automatización.
- **Norma.** Res. 1519/2020: el Anexo 2 §2.4.3 (iii) condición técnica 3 exige «mecanismos
  para evitar la recepción de correos electrónicos enviados de manera automática»; el
  Anexo 3 §9 exige «mecanismos de captcha accesibles o auto detectable, y/o limitar la tasa
  de intentos». El expediente lo recoge en el módulo 09 Seguridad y en el módulo 04 PQRSD.
- **Dónde.** `sitio/app/pages/realizar-una-peticion.vue`; ausencia en `backend/`.
- **Qué hay que hacer.** Resolverlo **accesible**: un captcha con desafío visual incumple
  WCAG 2.1 AA (1.1.1 y 1.4.3). La vía menos arriesgada es la que la propia norma admite como
  alternativa: limitación de tasa por origen más una prueba de desafío invisible con
  alternativa accesible declarada.

---

#### B-03 · El formulario de PQRSD no radica: no hay acuse, ni radicado, ni seguimiento, ni endpoint

- **Qué falta.** Seis cosas a la vez, y las seis son exigibles:
  1. **No hay endpoint.** `contract/openapi.yaml` declara exactamente tres operaciones:
     `/entidad` (línea 54), `/tramites` (línea 129) y `/tramites/{slug}` (línea 289).
     `backend/routes/api.php:22-27` implementa dos. No existe contrato ni ruta para PQRSD.
  2. **No hay acuse de recibo** con fecha y hora de recepción.
  3. **No hay radicado** ni el compromiso de las 24 horas hábiles siguientes.
  4. **No hay mecanismo de seguimiento en línea:** `sitio/app/pages/seguimiento.vue` tiene
     **19 líneas** y su plantilla es un `SeccionEnPreparacion`.
  5. **No hay mensaje de falla del sistema** distinto del error genérico.
  6. **No hay integración** con el sistema de radicación de la Entidad. La propia página lo
     declara en pantalla (`realizar-una-peticion.vue:402-410`: «Este formulario todavía no
     radica solicitudes […] al enviarlo **no se registrará nada**»).
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.3 (iii): condiciones técnicas 1 (acuse), 4
  (seguimiento), 5 (mensaje de falla) y 6 (integración); y el apartado «Condiciones de
  acceso a la información» (publicar los procedimientos y **los plazos de respuesta**).
  Es el objeto del módulo 04 del expediente y del artículo 14 del Decreto Ley 2106 de 2019.
- **Dónde.** `contract/openapi.yaml` (3 rutas); `backend/routes/api.php:22-27`;
  `sitio/app/pages/seguimiento.vue` (19 líneas);
  `sitio/app/pages/realizar-una-peticion.vue:359-378`.
- **Qué hay que hacer.** Declarar primero las operaciones en el contrato (radicación, acuse,
  consulta por radicado), implementarlas en el backend y conectar el formulario. El aviso
  honesto que hoy muestra el formulario **no debe retirarse hasta que el último de los seis
  puntos funcione**: retirarlo antes convertiría una carencia declarada en una promesa falsa.
- **Lo que sí está bien** y no hay que tocar: los campos mínimos del anexo están
  implementados (tipo de solicitud, descripción, tipo de persona, nombres, documento,
  correo con confirmación, dirección, teléfono, autorización de tratamiento —
  `realizar-una-peticion.vue:419-893`), la validación es accesible y mueve el foco al primer
  campo inválido (`:372`), y la modalidad anónima **oculta de verdad** los campos
  identificatorios.

---

#### B-04 · Las cinco políticas obligatorias del pie no tienen documento

- **Qué falta.** Existen las cinco **rutas** y ninguna **política**. La plantilla
  `sitio/app/pages/politicas/[slug].vue` (148 líneas) contiene un catálogo de cinco títulos
  y propósitos (líneas 32-58) y, para cada una, un aviso de que el documento está en
  preparación (líneas 111-117). No hay PDF, ni HTML, ni acto administrativo de adopción, ni
  descarga en formato abierto.
- **Norma.** Res. 1519/2020, Anexo 2 §2.3.1 (términos y condiciones, con seis componentes
  mínimos), §2.3.2 (privacidad y tratamiento de datos personales conforme a la Ley 1581 de
  2012), §2.3.3 (derechos de autor y autorización de uso sobre los contenidos) y §2.3.4
  (las demás). El pie de página tiene que **enlazar** esos documentos aprobados (§2.2.1.4).
  El expediente lo recoge en RF-B1-009 / RF-B3-058, RF-B2-008, RF-B2-009 y RF-B2-010, todos
  Must.
- **Dónde.** `sitio/app/pages/politicas/[slug].vue:32-58` y `:111-117`;
  `sitio/app/components/govco/PiePaginaGovco.vue:211-234`.
- **Qué hay que hacer.** Es un acto administrativo, no una tarea de programación: la Entidad
  tiene que aprobar los cinco documentos. El diseño ya está preparado para recibirlos
  (catálogo cerrado, 404 real para un slug inventado, navegación cruzada entre las cinco).
  Nota aparte, ya reconocida en el propio código
  (`politicas/[slug].vue:16-19`): la política de derechos de autor **no existe** en la
  Entidad, así que aquí no hay sólo una página vacía sino un incumplimiento abierto.

---

#### B-05 · El buscador de la sede no existe

- **Qué falta.** Hay campo de búsqueda en la cabecera de todas las páginas
  (`layouts/default.vue:193-196`, componente `BuscadorGovco`) y hay página de resultados,
  pero no hay índice ni operación que consulte. La página lo dice en pantalla:
  `sitio/app/pages/buscar.vue:44-47` — «El buscador todavía no tiene contenido que
  consultar: el índice se construye a medida que se publican los trámites y los
  documentos». Es un campo que no busca.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.1 (c): «Se debe contar con un buscador en el que
  la ciudadanía pueda encontrar información, datos o contenidos», aplicable a **todo** el
  sitio; y §2.4.2 (d), que lo exige además **dentro** de Transparencia. El expediente lo
  recoge en RF-B1-005 / RF-B2-035 / RF-B3-067 (Must) y RF-B1-006 / RF-B2-036
  (autocompletado y tolerancia a errores ortográficos). `docs/Sección 3` y el módulo 08
  añaden la prohibición de delegar en un buscador comercial.
- **Dónde.** `sitio/app/pages/buscar.vue` (64 líneas); `sitio/app/components/govco/BuscadorGovco.vue`
  (167 líneas, sin componente predictivo ni llamada a API); ausencia de operación de
  búsqueda en `contract/openapi.yaml`.
- **Matiz que hay que conservar.** La búsqueda **de la sección** de Transparencia sí está
  construida (`types/transparencia.ts` expone `buscarEnSeccion`, y
  `pages/transparencia/index.vue` la usa con recuento en región viva). Con esto se cumple
  §2.4.2 (d), **no** §2.4.1 (c). Son dos requisitos y sólo uno está atendido.
- **Qué hay que hacer.** Añadir la operación de búsqueda al contrato, indexar trámites y
  documentos de Transparencia, y conectar `BuscadorGovco`. Mientras no exista, el campo de
  la cabecera es un control que promete algo que no da.

---

#### B-06 · Falta la décima subsección de Transparencia: información tributaria

- **Qué falta.** El proyecto se contradice consigo mismo y el construido se queda con la
  versión corta. La documentación exige **diez** subsecciones: «(1) Información de la
  entidad, (2) Normativa, (3) Contratación, (4) Planeación/presupuesto/informes,
  (5) Trámites, (6) Participa, (7) Datos abiertos, (8) Grupos de interés,
  (9) **Obligación de reporte específico**, (10) **Tributaria (predial, ICA)**»
  (`sede-electronica-doc/02-transparencia/transparencia.md`, RF-B1-012 / RF-B3-081, Must).
  El sitio define **nueve** categorías y cierra la lista con «Información específica de la
  entidad» (`sitio/app/types/transparencia-indice.ts:54-82`; el orden en `:87-97`).
  `grep -rni "tributar\|predial\|calendario tributario"` sobre `sitio/app/types/` devuelve
  **cero coincidencias**.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.2 (g), que enumera la información mínima
  obligatoria del artículo 2.1.1.2.1.4 del Decreto 1081 de 2015. Los considerandos de la
  propia resolución (líneas 32-33 del texto) explican que la sección tributaria se creó por
  el Conpes 3956 de 2019, precisamente para publicar tarifas locales. El expediente lo
  recoge además en RF-B1-018 / RF-B3-089 (elementos del tributo) y RF-B3-151 (calendario
  tributario), ambos Must.
- **Dónde.** `sitio/app/types/transparencia-indice.ts:54-82`.
- **Qué hay que hacer.** Añadir la décima categoría con su apartado de calendario tributario
  y los elementos del tributo (sujeto activo, sujeto pasivo, hecho generador, causación,
  base gravable, tarifa). El resto de la infraestructura —índice, detalle, buscador de
  sección, sitemap— la hereda sin cambios: están construidos sobre `ORDEN_CATEGORIAS`, así
  que la categoría nueva entra en el menú, la miga de pan y el `sitemap.xml` sola.

---

#### B-07 · La fecha de publicación es opcional en el modelo y no consta en ningún registro

- **Qué falta.** Dos capas del mismo problema:
  1. **El modelo la declara opcional:** `DocumentoTransparencia.fechaPublicacion?: string`
     (`sitio/app/types/transparencia.ts:126`). Una obligación legal representada como campo
     opcional es una obligación que el sistema nunca podrá exigir.
  2. **No consta en ningún registro.** El propio código lo dice:
     `transparencia.ts:2588-2590` — «Mientras ningún documento declare su fecha —que es hoy
     el caso de las nueve categorías—». La interfaz lo publica con honestidad
     (`pages/transparencia/[slug].vue:209`: «Fecha de publicación: no consta en la fuente»),
     lo cual está bien, pero deja a la vista que **ninguna** de las 441 entradas con `titulo:`
     del inventario declara fecha.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.1 (e): «Todo documento o información debe indicar
  la fecha de su publicación en página web»; y §2.4.2 (e): «Toda la información debe ser
  publicada de manera inmediata o en tiempo real e incluir fecha de publicación». Es la
  obligación que el expediente repite en los tres módulos que publican documentos.
- **Dónde.** `sitio/app/types/transparencia.ts:126` y `:2588-2590`;
  `sitio/app/pages/transparencia/[slug].vue:201-210`.
- **Qué hay que hacer.** Convertir el campo en obligatorio cuando el modelo pase al backend
  (depende de B-08) y declarar la fecha en la fuente. Mientras el dato no exista, **no
  inventar una fecha**: el comportamiento actual —decir que no consta— es el correcto y no
  debe cambiarse por comodidad.

---

#### B-08 · El contenido de Transparencia es una constante de TypeScript: no hay CMS ni API

- **Qué falta.** Las nueve categorías, sus apartados y sus documentos viven en un fichero
  de **2 684 líneas** versionado en el repositorio: `sitio/app/types/transparencia.ts`. No
  hay ninguna operación en `contract/openapi.yaml` para consultar ni publicar documentos de
  transparencia, ni modelo en `backend/app/Models/` (que sólo tiene tres: `Tramite.php`,
  `IngestaTramite.php`, `User.php`), ni formulario de publicación en `panel/`. Un funcionario
  **no puede publicar nada** sin un despliegue de código.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.2 (e): la información debe publicarse «de manera
  inmediata o en tiempo real». Con el contenido compilado en el bundle, publicar exige un
  nuevo despliegue: la obligación es materialmente imposible de cumplir. Añádase §2.4.1 (a),
  orden cronológico del más reciente al más antiguo, hoy inaplicable porque no hay fechas
  (B-07). Y es el objeto entero del módulo 12 del expediente, «Gestión de Contenidos y
  Administración».
- **Dónde.** `sitio/app/types/transparencia.ts` (2 684 líneas);
  `contract/openapi.yaml` (3 operaciones); `backend/app/Models/` (3 modelos).
- **Qué hay que hacer.** Es el hallazgo con el mayor efecto dominó de la lista: resuelve
  B-06, B-07 y B-09 (el buscador de la sede pasaría a tener qué indexar) y es requisito
  previo de G-06, G-07, G-08, G-09 y M-02. Modelo de datos, migraciones, contrato,
  repositorio, servicio, endpoints y pantalla de publicación.
- **Nota sobre lo que hay.** El inventario actual **no es relleno**: declara procedencia,
  fuente y apartado de origen de cada documento, y el detalle de la página muestra «Campos
  que no vienen tal cual de la fuente», «Reglas con las que se calculó» y «Lo que la fuente
  no declara» (`pages/transparencia/[slug].vue:814-833`). Ese modelo de procedencia es el
  que hay que migrar a base de datos, no el que hay que desechar.

---

### 3.2 Graves

Un hallazgo es **grave** cuando incumple un requisito declarado, degrada el servicio o hace
que el sistema no pueda demostrar lo que afirma, pero no impide hoy operar.

---

#### G-01 · No hay medición ni declaración de conformidad de accesibilidad

- **Qué falta.** No existe una sola prueba automatizada de accesibilidad ejecutable, ni
  declaración de conformidad publicada. Las piezas están compradas y sin usar:
  `@axe-core/playwright`, `@playwright/test`, `axe-core`, `vitest`, `@vue/test-utils` y
  `jsdom` figuran en `sitio/package.json` (devDependencies) y **no hay ningún `*.spec.ts`
  ni `playwright.config.*` ni `vitest.config.*` en `sitio/`**. Los dos únicos ficheros de
  prueba que existen —`sitio/.scratch/pie.test.ts` y `sitio/.scratch/normativa.test.ts`—
  están dentro de `.scratch/`, que es **ignorado por git** (`.gitignore:17`), así que no
  llegan al repositorio ni a la integración continua. El trabajo `Sitio` de
  `.github/workflows/ci.yml:130-152` sólo ejecuta `npm run typecheck` y `npm run build`.
- **Norma.** Res. 1519/2020, artículo 3 y Anexo 1 §1.3 (WCAG 2.1 AA, obligatorio desde
  el 1 de enero de 2022) y Anexo 1 §2.2.3.8, «Revisión de la accesibilidad de un sitio
  web». El módulo 07 del expediente exige la declaración de conformidad con su alcance,
  herramientas y versiones, responsable, criterios no aplicables, excepciones, hallazgos
  conocidos y fecha de la próxima revisión. El propio sitio se abstiene expresamente de
  afirmar conformidad: `sitio/app/pages/accesibilidad.vue:10-17` — «No declara un nivel de
  conformidad verificado ni un resultado de auditoría, porque esa auditoría todavía no se ha
  publicado». Es la postura correcta; lo que falta es la auditoría.
- **Dónde.** `sitio/package.json`; `.scratch/`; `.github/workflows/ci.yml:130-152`;
  `sitio/app/pages/accesibilidad.vue:10-17`.
- **Qué hay que hacer.** Escribir la suite de axe-core + Playwright (incluye el
  `prefers-reduced-motion`, el orden de tabulación, el reflujo a 320 px y el área táctil a
  360 px, que son los puntos que el propio `sitio.css:378-390` documenta haber medido a
  mano), sacarla de `.scratch/`, añadirla como trabajo bloqueante en `ci.yml`, y sólo
  entonces redactar la declaración de conformidad.

---

#### G-02 · Nueve componentes del Kit UI 9.2 no están implementados

- **Qué falta.** El Kit documenta 31 componentes (`docs/Sección 2 · Diseño.md:167-205`). El
  sitio implementa **11** (`sitio/app/components/govco/`: `BarraAccesibilidad`,
  `BarraSuperior`, `BuscadorGovco`, `CabeceraGovco`, `CarruselGovco`,
  `GaleriaAplicacionesGovco`, `MenuNavegacionGovco`, `MigaDePanGovco`, `PiePaginaGovco`,
  `TarjetaInformacionGovco`, `VolverArriba`). De los documentados no hay **ninguna**
  implementación —ni componente, ni marcado, ni clase— de estos nueve: **Cuadrícula**,
  **Área de servicio** (#12), **Descripción emergente** (#17), **Etiquetas** (#18),
  **Indicador de carga** (#19), **Línea de avance** (#20), **Módulo de inicio de sesión**
  (#23), **Paginación** (#24) y **Pestañas** (#25). Verificado con `grep -rni
  "accordion|role=\"tab\"|spinner|tooltip|badge" sitio/app/`: cero coincidencias en
  Acordeón, Alerta modal, Toast, Tooltip, Etiquetas, Indicador de carga y Línea de avance.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4 y RN-B2-029 (el Kit UI es de cumplimiento
  obligatorio en los trámites integrados). El expediente lo recoge en RF-B2-084 / RF-B2-085
  (Must), RF-B3-060 (cuadrícula Bootstrap 5.0, 12 columnas y 6 breakpoints), RF-B3-070
  (indicador de carga), RF-B3-075 (paginación, ≥44×44 px y `aria-current`), RF-B3-076
  (tablas).
- **Dónde.** `sitio/app/components/govco/` (11 ficheros).
- **Matices que evitan trabajo inútil:**
  - **Cuadrícula y Tablas** los aporta Bootstrap 5.0.2, que sí se vendoriza
    (`sitio/public/govco/bootstrap.min.css`) y se carga **antes** del Kit
    (`nuxt.config.ts:61-71`). El Kit no trae una sola clase de Bootstrap: sin él no habría
    rejilla. Lo que falta no es implementarlas sino **verificarlas**.
  - **Paginación** sí existe, pero **incrustada** en `pages/tramites/index.vue:781-1014`,
    no como componente reutilizable. Cualquier listado futuro la reescribirá.
  - **Pestañas**: en `pages/tramites/index.vue:590-602` se sustituyeron deliberadamente por
    botones con `aria-pressed`, con la justificación escrita de que no hay tres paneles que
    cada pestaña posea. Es una decisión defendible; queda como desviación declarada, no como
    olvido.
- **Qué hay que hacer.** Priorizar por uso real: **Indicador de carga** y **Área de
  servicio** (G-03) son los que bloquean pantallas concretas; **Módulo de inicio de sesión**
  depende de que exista identidad ciudadana; **Etiquetas** y **Línea de avance** se
  necesitan en cuanto haya estados de solicitud (B-03).

---

#### G-03 · Falta el Área de servicio en la ficha del trámite

- **Qué falta.** El Kit UI 9.2, pág. 18, define el **Área de servicio** —los módulos «¿Cómo
  fue tu experiencia?» y «¿Tienes dudas sobre este trámite?»— y **restringe su uso a
  Trámites y servicios**. La ficha del trámite tiene nueve secciones y ninguna es ésa:
  `Ficha del trámite` (línea 433), `Requisitos` (501), `Costo y pago` (555), `Dónde se
  atiende` (632), `A quién va dirigido` (666), `Qué obtiene` (673), `Normativa` (687),
  `Cómo consultar el estado` (721) y `Procedencia de los datos` (776) en
  `sitio/app/pages/tramites/[slug].vue`.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.3 (i) y el Kit UI 9.2 pág. 18. El expediente lo
  recoge en RF-B3-065 y RF-B2-084 (Must).
- **Dónde.** `sitio/app/pages/tramites/[slug].vue` (950 líneas, exporta 123 fichas).
- **Qué hay que hacer.** Añadir el bloque al final de la ficha. La valoración de la
  experiencia necesita endpoint; el bloque de dudas puede resolverse primero con los
  canales de atención ya publicados y verificados en `pages/atencion.vue`.

---

#### G-04 · La ficha del trámite no publica los cuatro momentos GOV.CO

- **Qué falta.** La ficha describe el trámite, sus requisitos, su costo, sus puntos de
  atención, su normativa y cómo consultar el estado —pero no lo presenta articulado en los
  cuatro momentos que GOV.CO reconoce. `grep -n "etapa\|momento"` sobre
  `sitio/app/pages/tramites/[slug].vue` devuelve **cero coincidencias**.
- **Norma.** El expediente lo recoge en RF-B3-126 (Must): «Registrar todos los trámites en
  SUIT (DAFP) y publicar ficha en GOV.CO con los 4 momentos (acceso, solicitud, resolución,
  resultado)», y RF-B3-127 (estados estandarizados: solicitud registrada → recibida a
  satisfacción → en trámite → resuelta). Es consecuencia del Anexo 2 §2.4.3 (i) y del
  proceso de integración al Portal Único.
- **Dónde.** `sitio/app/pages/tramites/[slug].vue`; los datos vienen de
  `GET /api/v1/tramites` (`contract/openapi.yaml:129-288`).
- **Qué hay que hacer.** Decidir primero si los cuatro momentos se derivan de los datos que
  ya trae la ingesta o si exigen campos nuevos en el contrato. **No inventarlos en la
  plantilla:** el mismo código del proyecto advierte que el catálogo no se completa con nada
  que la fuente no declare (`pages/tramites/[slug].vue:814-833`).

---

#### G-05 · El Kit publica tipografía e interlineado y el sitio no los aplica

- **Qué falta.** Dos desviaciones medibles, ambas contra el mismo capítulo del Kit:
  1. **Interlineado.** El PDF del Kit documenta una columna «Interlineado» por estilo
     (h1: 42 px de cuerpo y 50 px de interlineado; *body text 1*: 15 px y 22 px; *caption*:
     12 px y 20 px). Su `all.css` **no aplica ni una**: de las 60 declaraciones
     `line-height` del fichero, **ninguna** corresponde a `h1`–`h6`, `body` ni `p`. Y la
     hoja propia del sitio **tampoco las aplica**: `sitio/app/assets/css/sitio.css` (465
     líneas) tiene **cero** declaraciones `line-height` y **cero** `font-family`. En
     consecuencia gana el *reboot* de Bootstrap, que sí vendorizamos:
     `h1,h2,h3,h4,h5,h6{line-height:1.2}` y `body{line-height:1.5}`
     (`sitio/public/govco/bootstrap.min.css`).
  2. **Familia tipográfica.** El Kit asigna Verdana al texto de párrafo y Nunito Sans a los
     títulos. Su CSS sólo aplica Verdana a clases propias (`.text2-govco`, `.text3-govco`,
     `.pie-pagina-govco p`…), mientras `html` recibe `font-family: 'Nunito_Sans-Regular'`.
     Como el sitio no declara ninguna familia para el texto corrido, **los párrafos de toda
     la sede salen en Nunito Sans**, no en Verdana.
- **Norma.** Kit UI gov.co 9.2, capítulo de Tipografía (es el documento vigente de diseño);
  Res. 1519/2020, Anexo 2 §2; RN-B2-029. El expediente lo recoge en RF-B1-098 /
  RF-B3-045 (Must: «100 % de componentes con paleta Cobalt `#0943B5` + tipografía Nunito
  Sans/Verdana»), RF-B3-046 y RNF-B3-045.
- **Dónde.** `sitio/app/assets/css/sitio.css` (0 `line-height`, 0 `font-family`);
  `sitio/public/govco/all.css` (`html` en la línea 72, `h1`–`h6` en 73-94);
  `sitio/public/govco/bootstrap.min.css`.
- **Qué hay que hacer.** Añadir al `sitio.css` los valores de interlineado del Kit para
  `h1`–`h6`, `body` y `p`, y la familia Verdana al texto corrido. Es una corrección de
  **una sola hoja** y afecta a las 21 páginas a la vez. **Precaución:** `line-height` en
  unidades sin rem/px puede romper la regla de espaciado de texto de WCAG 1.4.12 que
  `plan.md:1064` exige soportar (1,5 de interlineado y 0,12 em entre letras); hay que
  verificar con las herramientas de G-01 después de aplicarlo.

---

#### G-06 · No hay aviso de salida a sitio externo ni lista blanca de dominios

- **Qué falta.** Los enlaces que salen de la sede navegan directamente. En la ficha del
  trámite hay al menos ocho (`pages/tramites/[slug].vue`, líneas 414, 458, 526, 571, 696,
  702, 734 y 783) con `class="enlace-externo"` y, como mucho, `rel="noopener"`. No hay
  modal de aviso, ni nombre del destino, ni confirmación, ni lista blanca administrable.
  `grep -rni "sitio externo" sitio/app/` devuelve **cero coincidencias** en código.
- **Norma.** El expediente lo recoge en RF-B1-071 / RF-B2-040 / RF-B3-094 (Must/Should),
  con sus derivados RF-01-D03, RN-01-D03 y HU-01-D03, que exigen además que la lista blanca
  sea **administrable** para no disparar el aviso en redirecciones a GOV.CO o al
  Articulador.
- **Dónde.** `sitio/app/pages/tramites/[slug].vue` (8 enlaces);
  `sitio/app/pages/tramites/index.vue`; ausencia en `sitio/app/components/`.
- **Qué hay que hacer.** Componente de modal (que además es el componente #10 del Kit que
  falta, G-02) más la lista blanca. Nota: la lista blanca necesita persistencia, así que
  depende del mismo trabajo de B-08.

---

#### G-07 · No hay módulo de noticias

- **Qué falta.** `sitio/app/pages/noticias.vue` tiene **17 líneas** y su plantilla es
  `SeccionEnPreparacion`. No hay bloque de noticias en la portada: `sitio/app/pages/index.vue`
  (131 líneas) contiene el carrusel (línea 108), tres tarjetas de sección (112-121) y un
  apartado de ayuda (123-129). No hay modelo, ni operación en el contrato, ni entidad en el
  backend.
- **Norma.** El expediente lo recoge en RF-B1-011 (Must): «Módulo de noticias en home:
  imagen 4:3 o 16:9, título ≤150 car., descripción ≤200 car., fecha; orden cronológico
  inverso», y en RF-01-D02 / HU-01-D02 (CRUD completo desde el CMS con reordenación y
  despublicación). Es además la primera cosa que el texto de la resolución pide para la
  página principal («En la página principal, el sujeto obligado publicará las noticias más
  relevantes para la ciudadanía y los grupos de valor»).
- **Dónde.** `sitio/app/pages/noticias.vue` (17 líneas); `sitio/app/pages/index.vue`;
  `contract/openapi.yaml`.
- **Qué hay que hacer.** Depende de B-08 (CMS). `/noticias` **está en el menú principal**
  (`layouts/default.vue:94`), así que hoy es un enlace del menú obligatorio que lleva a un
  aviso de «en preparación».

---

#### G-08 · Cuatro secciones publicadas son avisos de «en preparación»

- **Qué falta.** Cuatro rutas son envoltorios de `SeccionEnPreparacion` (61 líneas) y no
  publican contenido:

  | Ruta | Fichero | Líneas |
  |---|---|---|
  | `/servicios` | `sitio/app/pages/servicios.vue` | **17** |
  | `/portales` | `sitio/app/pages/portales.vue` | **17** |
  | `/noticias` | `sitio/app/pages/noticias.vue` | **17** |
  | `/seguimiento` | `sitio/app/pages/seguimiento.vue` | **19** |

- **Norma.**
  - `/servicios` es una de las tres secciones que el criterio de aceptación FUN-013 obliga a
    mostrar en la portada y está enlazada desde el carrusel (`index.vue:59`) y desde la
    tarjeta de la portada (`index.vue:83`). El Anexo 2 §2.4.3 (i) exige que Atención y
    Servicios a la Ciudadanía dé acceso a trámites, OPA y consultas.
  - `/seguimiento` es un requisito con nombre propio: Anexo 2 §2.4.3 (iii) condición
    técnica 4, «mecanismo de seguimiento en línea» (véase B-03).
  - `/portales` responde al Decreto Ley 2106 de 2019, artículo 14 (la sede integra **todos**
    los portales y aplicaciones) y artículo 15 (programas transversales), recogidos en
    RF-B2-002 y RF-B2-003, Must.
- **Dónde.** Los cuatro ficheros de la tabla.
- **Qué hay que hacer.** `/servicios` merece una decisión de producto antes que de código
  (véase M-10). `/seguimiento` depende de B-03. `/portales` depende de que la Entidad
  entregue el inventario de micrositios, que sigue abierto como pregunta en el propio
  expediente (`01-estructura-identidad/sede.md:181`, «[PREGUNTA ABIERTA] ¿Qué
  micrositios/apps/portales independientes tiene hoy la Alcaldía…?»).

---

#### G-09 · Las seis subcategorías de Participa están vacías

- **Qué falta.** `sitio/app/pages/participa/[slug].vue` (76 líneas) resuelve correctamente
  las seis subcategorías del Anexo 2 §4.1.2.3 —slugs, títulos y propósitos están completos
  (líneas 13-44)— y responde 404 real a un slug inventado (líneas 61-63), pero su plantilla
  es únicamente `<SeccionEnPreparacion>` (líneas 70-76). No hay agenda regulatoria, ni
  consultas abiertas, ni rendición de cuentas, ni control ciudadano.
- **Norma.** Participa es uno de los **tres menús mínimos obligatorios** (Res. 1519/2020,
  Anexo 2 §2.4). El módulo 05 del expediente exige las fases de participación de la Ley
  1757 de 2015 y la consulta ciudadana de normas en elaboración vía SUCOP.
- **Dónde.** `sitio/app/pages/participa/[slug].vue`; `sitio/app/layouts/default.vue:64-90`.
- **Qué hay que hacer.** Depende de B-08 para publicar convocatorias y de una decisión de
  la Entidad sobre qué mecanismo de participación electrónica adopta. Rendición de cuentas
  y control ciudadano pueden resolverse antes: sus documentos existen en la Entidad
  (informes de gestión, audiencias) y sólo hay que publicarlos.

---

#### G-10 · Los canales de atención no permiten pedir cita

- **Qué falta.** `sitio/app/pages/atencion.vue` (209 líneas) publica dirección, código
  postal, horario, conmutador, línea gratuita y correos —todo verificado contra la fuente y
  sin inventar— pero **no ofrece agendamiento**. `grep -n "cita\|agend"` sobre el fichero
  devuelve **cero coincidencias**.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.3 (ii): «Canales de atención **y pida una cita**.
  Los sujetos obligados deberán incluir en su respectiva sede electrónica la información y
  contenidos relacionados con los canales habilitados para la atención a la ciudadanía».
  El módulo 06 del expediente incluye el agendamiento de citas para la atención presencial.
- **Dónde.** `sitio/app/pages/atencion.vue`; ausencia de operación en el contrato.
- **Qué hay que hacer.** Contrato, endpoint y pantalla. Es una de las funciones con mayor
  efecto visible para el ciudadano y no depende de B-08.

---

#### G-11 · El contrato declara `/entidad` y ni el backend lo implementa ni el sitio lo consume

- **Qué falta.** La operación está declarada con `x-status: pending`
  (`contract/openapi.yaml:54-128`), el backend no la implementa
  (`backend/routes/api.php:22-27` sólo registra `/tramites` y `/tramites/{slug}`) y el sitio
  no la llama: las dos únicas páginas que hablan con la API son las de trámites
  (`pages/tramites/index.vue:252` y `pages/tramites/[slug].vue:84`). En consecuencia, los
  datos institucionales del encabezado y del pie están **incrustados en el código** en tres
  copias (véase M-07).
- **Norma.** El propio contrato se declara «la única fuente de verdad del intercambio HTTP»
  (`README.md:58`) y la documentación del backend lo repite
  (`backend/routes/api.php:13-15`: «Ninguna ruta vive aquí sin su operación declarada en
  `contract/openapi.yaml`»). El expediente exige una **fuente única** para la información
  pública (Res. 1519/2020, Anexo 2 §2.4.1 (f): «La información pública debe contar con una
  fuente única […] evitando duplicidad»). Los datos de contacto tienen además sus propios
  criterios: FUN-007, FUN-009, FUN-014, FUN-015, SEG-006 y CAG-12, declarados en el propio
  contrato.
- **Dónde.** `contract/openapi.yaml:54-128`; `backend/routes/api.php:22-27`;
  `sitio/app/pages/tramites/index.vue:252`; `sitio/app/pages/tramites/[slug].vue:84`.
- **Qué hay que hacer.** Implementar `/entidad` en el backend y hacer que
  `PiePaginaGovco.vue`, `atencion.vue` y `accesibilidad.vue` la consuman. De paso resuelve
  la parte de contrato de B-04 (el esquema ya declara `politicas[]` con `actualizada_en`).

---

#### G-12 · El backend no tiene capa de autorización

- **Qué falta.** `backend/app/Policies/` existe y está **vacía** (sólo un `.gitkeep`). No
  hay ninguna clase de autorización en el proyecto: `grep -rn "authorize\|Policy"
  backend/app/Http backend/app/Providers` sólo encuentra `ListarTramitesRequest::authorize()`
  (`app/Http/Requests/ListarTramitesRequest.php:24`), que devuelve `true` con la
  justificación correcta de que el catálogo es público.
- **Norma.** Res. 1519/2020, Anexo 3 §2: «Implementar o exigir controles de seguridad
  relacionados con la **autenticación, definición de roles y privilegios y separación de
  funciones**». El módulo 09 del expediente lo desarrolla (RBAC, niveles de confianza,
  separación de funciones).
- **Dónde.** `backend/app/Policies/` (vacío); `backend/routes/api.php:22-27` (dos rutas
  públicas).
- **Severidad y honestidad sobre ella.** Hoy **no hay nada expuesto que proteger**: las dos
  rutas son públicas por diseño y no hay panel de administración conectado a la API. Por eso
  es grave y no bloqueante. El riesgo no es la exposición actual: es que el directorio
  vacío **se lee como trabajo hecho** y que la primera ruta protegida que se añada —la
  publicación de documentos de B-08, la radicación de B-03, el panel— nazca sin autorización.
- **Qué hay que hacer.** Definir el modelo de roles antes de escribir el primer endpoint de
  escritura, no después.

---

#### G-13 · Los 123 trámites publicados no están en el `sitemap.xml`

- **Qué falta.** El mapa del sitio anuncia `/tramites` (`server/routes/sitemap.xml.ts:46`)
  pero **ninguna** de sus fichas: `grep -n "tramites/" server/routes/sitemap.xml.ts` no
  devuelve ninguna coincidencia. Las 123 fichas de trámite existen, responden y están
  enlazadas internamente, pero son invisibles para los motores de búsqueda.
- **Norma.** Res. 1519/2020, Anexo 1 §4.3.1 (b): el mapa del sitio debe estar en formato XML
  «para que sea visible a los motores de búsqueda». El expediente lo recoge en
  RF-B1-010 / RF-B2-037 (Must) y HU-B1-001 («encontrar la sede como primer resultado»).
- **Dónde.** `sitio/server/routes/sitemap.xml.ts:43-58` y `:100-119`.
- **Qué hay que hacer.** Añadir las fichas. El fichero ya está preparado: importa las nueve
  categorías de Transparencia desde `~/types/transparencia-indice` (líneas 27 y 104-108)
  precisamente para no repetir listas. Las fichas de trámite salen de la API, así que o bien
  se enumeran al construir el mapa (una llamada paginada a `GET /api/v1/tramites`) o bien se
  declara un `sitemap` índice. Ojo con el comentario del propio fichero (líneas 15-19): sólo
  se anuncian direcciones que existen de verdad.

---

### 3.3 Medios

Un hallazgo es **medio** cuando incumple un requisito de prioridad baja, duplica trabajo o
deja una deuda declarada sin cerrar.

---

#### M-01 · No hay encuesta de usabilidad ni pruebas con usuarios

- **Qué falta.** `grep -rni "encuesta" sitio/app/ backend/app` sólo encuentra dos
  coincidencias, y las dos son **documentos de la Entidad listados en el inventario** de
  Transparencia (`types/transparencia.ts:1729` y `:2400`), no un mecanismo de encuesta del
  sitio. No hay cuestionario SUS, ni invitación, ni registro de resultados.
- **Norma.** El módulo 08 del expediente incluye explícitamente «encuesta SUS y pruebas de
  usuario»; `plan.md` las sitúa como puerta de calidad.
- **Dónde.** Ausencia en `sitio/app/`.
- **Qué hay que hacer.** Puede construirse sin B-08 (una encuesta con destino en el
  backend no necesita CMS), pero su utilidad depende de que haya servicio que evaluar:
  hoy el ciudadano no puede radicar nada. **Recomiendo no construirla todavía** y sí
  planificarla para después de B-03.

---

#### M-02 · No hay federación de datos abiertos a `datos.gov.co`

- **Qué falta.** La categoría de datos abiertos existe en la estructura de Transparencia
  (`types/transparencia-indice.ts:71`), pero no hay federación ni catálogo propio.
  `grep -rn "datos.gov.co" sitio/app/` devuelve **una sola coincidencia**
  (`types/transparencia.ts:2424`), y es la URL de un documento de la Entidad, no un
  mecanismo de federación. No hay `dcat`, ni registro de activos de información, ni
  identificadores de conjunto de datos.
- **Norma.** Res. 1519/2020, artículo 7 y Anexo 4: «Los sujetos obligados deberán publicar
  sus datos abiertos y federarlos al Portal Datos Abiertos del Estado colombiano —
  datos.gov.co». Es el objeto del módulo 11 del expediente.
- **Dónde.** `sitio/app/types/transparencia.ts:2424`; ausencia de cualquier otra pieza.
- **Qué hay que hacer.** Es trabajo de datos, no de interfaz: publicar los conjuntos en
  `datos.gov.co` y enlazarlos. La interfaz debe mostrar el catálogo federado, no alojarlo.

---

#### M-03 · La sección de normativa no publica el enlace al SUIN

- **Qué falta.** `sitio/app/pages/normativa.vue` (734 líneas) construye correctamente la
  estructura que el Anexo 2.1 pág. 8 exige para cada norma —tipo, fecha de expedición,
  fecha de publicación, epígrafe, enlace, vigencia, proyectos con fecha máxima de
  comentarios (`:126-179`)— y **declara cero normas**, a propósito y con razón. Pero el
  catálogo de apartados del Anexo que publica sólo tiene cuatro entradas (líneas 88-99), y
  la de «Vínculo al Diario o Gaceta oficial» queda diferida (líneas 95-99), mientras que
  `grep -rni "suin" sitio/app/pages/normativa.vue` devuelve **cero coincidencias**. El SUIN
  sólo aparece en el inventario de Transparencia (`types/transparencia.ts:787-795`).
- **Norma.** El expediente lo recoge en RF-B1-014 (Must): «Enlace funcional al SUIN
  (suin-juriscol.ramajudicial.gov.co) y al Diario/Gaceta Oficial; publicar Agenda
  Regulatoria». Res. 1519/2020, Anexo 2 §2.4.1 (g) y el apartado 2.1.4 de la estructura de
  Transparencia («Vínculo al Diario o Gaceta Oficial […] se deberá incluir un link para
  consultar las gacetas oficiales»).
- **Dónde.** `sitio/app/pages/normativa.vue:88-99`; `types/transparencia.ts:787-795`.
- **Qué hay que hacer.** Añadir el enlace al SUIN a `/normativa`. El de la Gaceta Distrital
  no puede añadirse hasta que la Entidad confirme la URL, y la razón que el propio código da
  para diferirlo es correcta: apuntar a una dirección sin comprobarla sería inventar un
  enlace.

---

#### M-04 · El carrusel arranca solo

- **Qué falta.** El componente arranca en reproducción automática:
  `autoplay: true` (`components/govco/CarruselGovco.vue:98`) y
  `reproduciendo.value = props.autoplay && !consultaMovimiento.matches` (línea 200), con un
  intervalo de 6 000 ms (línea 99). Los tres controles obligatorios del Kit **sí están**
  —indicadores de posición, flechas y reproducción/pausa— y el botón cambia de estado y de
  etiqueta accesible (líneas 271-275).
- **Norma.** WCAG 2.1 §2.2.2 (Pausar, detener, ocultar) exige **un mecanismo** para
  detenerlo, y existe. Lo que incumple es el requisito propio del proyecto: RF-B1-042 /
  RF-B3-069 (Must) pide «**pausa por defecto**». La resolución del `prefers-reduced-motion`
  (líneas 195-200) es una buena práctica que **no es** pausa por defecto.
- **Dónde.** `sitio/app/components/govco/CarruselGovco.vue:98-99` y `:200`.
- **Qué hay que hacer.** Cambiar el valor por defecto a `autoplay: false`. Es un cambio de
  una línea, pero altera el comportamiento de la portada: conviene decidirlo, no aplicarlo
  por sorpresa.

---

#### M-05 · La página 404 ofrece una sola opción de navegación propia

- **Qué falta.** `sitio/app/error.vue` (59 líneas) explica el error, muestra el código
  (línea 52) y ofrece **un** enlace: «Volver a la portada» (línea 55).
- **Norma.** El expediente lo recoge en RF-B1-007 / RF-B2-039 / RF-B3-142 (Must):
  «≥3 opciones de navegación/contenido alternativo (menú, buscador, secciones populares)».
- **Dónde.** `sitio/app/error.vue:54-56`.
- **`[DEDUCCIÓN]`** La página se monta dentro de `NuxtLayout` (línea 37), así que **hereda el
  menú de siete ítems, el buscador de la cabecera, la miga de pan y el pie completo**. Si el
  criterio se lee como «la página debe ofrecer esas tres vías», se cumple por el armazón. Si
  se lee como «la propia página debe listar tres alternativas», no. **No puedo resolver esa
  ambigüedad leyendo el código** y conviene aclararla con quien redactó el criterio antes de
  tocar nada. Lo que sí es una mejora clara, en cualquier lectura: añadir enlaces a las tres
  secciones obligatorias (Transparencia, Atención y Servicios, Participa).

---

#### M-06 · No hay selector de idioma

- **Qué falta.** `sitio/app/components/govco/BarraSuperior.vue` tiene **24 líneas** y su
  plantilla es una sola `<a>` al Portal Único. Las líneas 8-11 documentan la ausencia como
  deliberada: «*Sin botón de cambio de idioma, y es deliberado.* El Kit lo ofrece como
  opcional, y el sitio es monolingüe: un conmutador que no cambia nada engaña más de lo que
  ayuda».
- **Norma.** El expediente lo recoge en RF-B3-055 con prioridad **Could** y con una decisión
  registrada que lo difiere: «*Decisión #16 (2026-06-05): DIFERIDO — castellano únicamente
  por ahora; lenguas étnicas a futuro*» (`01-estructura-identidad/sede.md:24`). La resolución
  no lo exige: el Anexo 2 §2.1.1 menciona las «demás referencias que sean adoptadas en el
  lineamiento gráfico», y el Kit lo ofrece como opcional.
- **Dónde.** `sitio/app/components/govco/BarraSuperior.vue:8-11`.
- **Valoración.** **No es un incumplimiento.** La documentación lo difiere y el código lo
  explica. Se registra aquí para que no se confunda con un olvido: cuando se decida
  implementarlo, el punto de inserción está localizado y el `htmlAttrs: { lang: 'es' }` de
  `nuxt.config.ts:38` habrá que hacerlo dinámico.

---

#### M-07 · Los datos de la Entidad están duplicados en tres ficheros

- **Qué falta.** El nombre, la dirección, el horario, el conmutador, la línea gratuita y los
  correos viven en `sitio/app/components/govco/PiePaginaGovco.vue:150-177`, y se repiten en
  `sitio/app/pages/atencion.vue:35-52` y en `sitio/app/pages/accesibilidad.vue:26-37`.
  Cambiar un teléfono exige tocar tres ficheros.
- **Norma.** Res. 1519/2020, Anexo 2 §2.4.1 (f): fuente única de la información pública, sin
  duplicidad.
- **Dónde.** Los tres ficheros citados.
- **Nota.** **No es un descuido oculto: está declarado por escrito en dos sitios.**
  `atencion.vue:11-15` — «*Fuente única, hoy duplicada a propósito.* […] Queda anotado para
  que no se pierda»— y `accesibilidad.vue:20-23`. Se resuelve con G-11 (`/entidad`).

---

#### M-08 · El catálogo de tipos de PQRSD está duplicado

- **Qué falta.** Los seis tipos con sus slugs viven en `sitio/app/pages/pqrsd.vue` y se
  repiten en `sitio/app/pages/realizar-una-peticion.vue`.
- **Norma.** Misma fuente única (Anexo 2 §2.4.1 (f)). Y hay un riesgo funcional: si las dos
  listas divergen, un enlace de PQRSD abriría el formulario sin tipo preseleccionado.
- **Dónde.** `pqrsd.vue:51-56` y `realizar-una-peticion.vue:35-38`, donde **ambos ficheros
  declaran la deuda** y explican por qué no se resolvió.
- **Qué hay que hacer.** Extraer a `sitio/app/types/` o a un composable. Es el trabajo más
  barato de toda esta lista.

---

#### M-09 · Hay dependencias declaradas y no usadas en el sitio

- **Qué falta.** `sitio/package.json` declara `axios`, `zod`, `pinia` y
  `@tanstack/vue-query`, y **ninguno se importa** en `sitio/app/` (verificado: cero ficheros
  para cada uno). Las páginas de trámites usan `useRequestFetch` de Nuxt. Además,
  `@playwright/test` y `@axe-core/playwright` están declarados y no hay ni una prueba
  (G-01), y `vitest`/`jsdom`/`@vue/test-utils` tampoco tienen configuración.
- **Norma.** Criterio de diseño, no normativo. `README.md:119-127` y la regla del proyecto
  sobre dependencias de terceros hacen de esto una puerta de calidad (hay un trabajo de CI
  dedicado a revisar los ficheros de bloqueo).
- **Dónde.** `sitio/package.json`.
- **Qué hay que hacer.** Decidir en cada caso: o se usan (Playwright y axe en G-01) o se
  retiran. Cuatro dependencias de ejecución sin usar son superficie de suministro sin
  contrapartida.

---

#### M-10 · `/servicios` y `/tramites` cubren el mismo objeto

- **Qué falta.** El menú principal no ofrece `/servicios` —sus siete ítems son Inicio,
  Transparencia, Atención y Servicios a la Ciudadanía (cuyo despliegue lleva a
  `/tramites`, `/atencion`, `/realizar-una-peticion` y `/seguimiento`), Participa, PQRSD,
  Normativa y Noticias (`layouts/default.vue:47-95`)—, pero `/servicios` sí está enlazada
  desde el carrusel de la portada (`index.vue:59`), desde la tarjeta de la portada
  (`index.vue:83`) y desde el `sitemap.xml` (`sitemap.xml.ts:54`). Y está vacía (G-08),
  mientras `/tramites` (1 061 líneas) publica el catálogo completo de 123 trámites con sus
  seis atributos.
- **Norma.** El criterio FUN-013 exige mostrar la sección «Servicios a la Ciudadanía» en la
  portada. El Anexo 2 §2.4.3 (i) exige que ese menú dé acceso a trámites y OPA.
- **Dónde.** `sitio/app/pages/servicios.vue` (17 líneas);
  `sitio/app/pages/tramites/index.vue` (1 061 líneas); `layouts/default.vue:47-95`.
- **`[DEDUCCIÓN]`** `/tramites` **ya es** la sección «Atención y Servicios a la Ciudadanía»:
  es donde el menú obligatorio apunta y donde el contenido existe. Mantener dos rutas para
  el mismo objeto produce dos direcciones canónicas del mismo contenido, que es exactamente
  lo que el Anexo 2 §2.4.1 (f) prohíbe y lo que el propio `[...ruta].vue:11-12` advierte
  sobre contenido duplicado. Recomiendo redirigir `/servicios` a `/tramites` y corregir los
  tres enlaces, en lugar de construir una segunda sección. **Es mi recomendación, no una
  conclusión que se deduzca del texto de la norma**; la alternativa —dar a `/servicios` el
  contenido de servicios no-tramitables (los que no están en SUIT)— es igualmente válida y
  es una decisión del titular.

---

### 3.4 Menores

#### m-01 · `sitemap.xml.ts:48` declara dos direcciones en la misma línea

La entrada de `/participa` y la de `/normativa` están escritas en una sola línea
(`sitio/server/routes/sitemap.xml.ts:48`), lo que rompe el formato de la lista y dificulta
compararla con el menú —que es, según el comentario de las líneas 38-42, el propósito de
mantener esa lista ordenada. Es cosmético y no afecta a la salida XML.

#### m-02 · `robots.txt` apunta al dominio de producción y la configuración al de pruebas

`sitio/public/robots.txt` declara `Sitemap: https://www.santamarta.gov.co/sitemap.xml`
(estático, escrito a mano) mientras el valor por defecto de la configuración es
`staging.santamarta.gov.co` (`sitio/nuxt.config.ts:92`). El `sitemap.xml`, en cambio, sí lee
el dominio de `runtimeConfig` (`server/routes/sitemap.xml.ts:92-98`). En un despliegue de
pruebas, el `robots.txt` está apuntando al mapa del sitio de producción. `[DEDUCCIÓN]` El
efecto real depende de si el entorno de pruebas está protegido por autenticación básica o
por la red; **no he podido comprobarlo**, porque eso vive en `deploy/` y en el servidor.

#### m-03 · `/buscar` se anuncia en el `sitemap.xml` y no encuentra nada

`sitio/server/routes/sitemap.xml.ts:55` anuncia `/buscar` con prioridad 0.3, y la página
misma se declara `noindex, follow` (`pages/buscar.vue:25`). Anunciar en el mapa del sitio una
dirección que la propia página pide no indexar es contradictorio. Se resuelve solo cuando
exista el índice (B-05), pero conviene quitar la entrada del `sitemap.xml` hasta entonces.

---

## 4. Cobertura por sección

Las doce secciones del expediente contra lo construido. Estados: **construida** (publica lo
que la sección exige), **parcial** (hay estructura y contenido real, faltan piezas
declaradas), **stub** (existe la ruta y no hay contenido), **ausente** (no hay nada).

| # | Sección | Estado | Qué hay | Qué falta |
|---|---|---|---|---|
| 01 | Estructura e identidad | **parcial** | Barra superior, accesibilidad, cabecera con logo de la Entidad y «Iniciar sesión», menú de 7 ítems con las 6 subcategorías de Participa, miga de pan derivada del menú, pie completo, volver arriba. 404 real con estado correcto. `sitemap.xml` y `robots.txt` | **B-01** consentimiento de cookies · **G-06** aviso de salida a sitio externo · **G-07** noticias · **G-05** tipografía e interlineado · **M-06** selector de idioma · los componentes de G-02 · IPv4/IPv6 (RF-B1-099, no auditable aquí) |
| 02 | Transparencia | **parcial (la más avanzada)** | Las 9 categorías construidas con apartados, procedencia, buscador de sección, 4 estados distinguidos, detalle por categoría (797 + 731 líneas), en el menú, la miga y el sitemap | **B-06** la décima categoría (tributaria) · **B-07** fecha de publicación · **B-08** CMS y API · **M-02** datos abiertos federados · **M-03** SUIN desde `/normativa` · directorio SIGEP (RF-B1-017) |
| 03 | Servicios y trámites | **parcial** | Catálogo de 123 trámites con los seis atributos del Anexo 2.1 §5.1.3, servido por `GET /api/v1/tramites` y consumido con paginación de servidor. Ficha por trámite con 9 secciones | **G-03** área de servicio · **G-04** cuatro momentos GOV.CO · **G-13** fichas en el sitemap · trámites en línea con las 4 etapas, pagos electrónicos, expediente electrónico, Carpeta Ciudadana, certificados, firma electrónica |
| 04 | PQRSD | **parcial** | `/pqrsd` define los 6 tipos con sus términos del CPACA art. 14 (15, 10 y 30 días); el formulario tiene todos los campos mínimos del anexo, validación accesible y modalidad anónima real | **B-02** captcha · **B-03** radicación, acuse, radicado, seguimiento, mensaje de falla, integración SGDEA |
| 05 | Participa | **stub** | Las 6 subcategorías con sus nombres y propósitos exactos; 404 real para slug inventado | Todo el contenido: participación en fases, consulta ciudadana (SUCOP), agenda regulatoria, rendición de cuentas, control ciudadano |
| 06 | Canales de atención | **parcial** | `/atencion` con dirección, código postal, horario, conmutador, línea gratuita, línea anticorrupción y correos, verificados contra la fuente | **G-10** agendamiento de citas · chat · accesibilidad física de las sedes |
| 07 | Accesibilidad | **parcial** | Salto al contenido con foco, barra de contraste real (el Kit sólo tenía un stub), escalado de letra por raíz, 44 px de área táctil documentados y corregidos, foco visible, `prefers-reduced-motion`, orden de tabulación por estructura | **G-01** verificación y declaración de conformidad · **G-02** los componentes accesibles que faltan (indicador de carga, línea de avance) · subtitulado y audiodescripción de multimedia · LSC |
| 08 | Usabilidad | **parcial** | URLs limpias en castellano sin tildes, migas de pan por construcción, formularios con etiquetas asociadas, ayuda y error por campo, lenguaje claro sostenido en todo el texto | **M-01** encuesta SUS y pruebas de usuario · validación W3C · **M-09** Playwright/axe declarados sin usar · **G-05** interlineado |
| 09 | Seguridad | **parcial, del lado que no se ve** | Backend con análisis estático, formato, pruebas y escaneo de imagen en CI; cabeceras y TLS viven en `nginx`/`docker` (fuera de este alcance) | **B-02** captcha y control de tasa · **G-12** RBAC y Policies · CSRF · ocultamiento de rutas administrativas · logs de auditoría · **G-06** lista blanca de dominios |
| 10 | Interoperabilidad | **ausente** | Nada. Las fichas de trámite enlazan a `gov.co` y a SUIT como texto, sin integración | PDI/X-Road del Articulador, estampado cronológico (TSA), integraciones SUIT/SIGEP/SECOP/SGDEA, «no exigir documentos que el Estado ya tiene» |
| 11 | Datos abiertos | **parcial (sólo la vitrina)** | Existe la categoría de datos abiertos dentro de Transparencia | **M-02** federación a `datos.gov.co`, formatos abiertos, metadatos, registro de activos de información, análisis de criticidad, licencia, plan de apertura |
| 12 | Gestión de contenidos | **ausente** | Nada en `sitio/` ni en `backend/`. `panel/` existe pero no está conectado a un CMS de contenidos públicos | **B-08** CMS y API de publicación · usuarios internos, roles y permisos · auditoría de cambios · TRD/AGN · registro electrónico 24/7 · SGDEA · notificaciones electrónicas · firma electrónica · tablero ITA |

**Recuento:** 0 construidas por completo · 8 parciales · 1 stub · 2 ausentes (10, 12) ·
1 parcial con reserva (09, porque lo auditado es sólo el lado aplicación).

---

## 5. Contradicciones entre la documentación y lo construido

### 5.1 Transparencia: la documentación dice diez, el sitio construye nueve

- **Documentación:** `sede-electronica-doc/02-transparencia/transparencia.md`, RF-B1-012 /
  RF-B3-081 (Must): «Publicar el menú *Transparencia* con **≥10 subsecciones**», enumerando
  como (9) «Obligación de reporte específico» y (10) «Tributaria (predial, ICA)».
- **Construido:** `sitio/app/types/transparencia-indice.ts:54-82` define **nueve**
  categorías, y la novena es «Información específica de la entidad», que no es ninguna de
  las dos que la documentación nombra por separado.
- **Efecto:** falta la categoría tributaria entera y el calendario tributario (B-06), y
  queda la duda de si «Obligación de reporte específico» está absorbida por la novena o
  también falta. **Recomiendo resolver la contradicción con el texto de la Resolución antes
  de implementar**, porque de ello depende si son una o dos categorías nuevas.

### 5.2 El contrato y el sitio usan slugs distintos para las cinco políticas

- **Contrato:** `contract/openapi.yaml:110-122` — `terminos-y-condiciones`,
  `seguridad-y-privacidad`, `tratamiento-de-datos`, `uso-de-cookies`, `derechos-de-autor`.
- **Sitio:** `sitio/app/pages/politicas/[slug].vue:32-58` —
  `terminos-y-condiciones-de-uso`, `seguridad-y-privacidad`,
  `proteccion-y-tratamiento-de-datos-personales`, `uso-de-cookies`,
  `derechos-de-autor-y-uso-sobre-contenidos`.
- **Efecto:** **tres de cinco slugs no coinciden.** El contrato se declara fuente única del
  intercambio HTTP (`README.md:58`), así que en cuanto `/entidad` se implemente y el pie
  consuma `politicas[].slug`, cinco de los siete enlaces del pie apuntarán a direcciones que
  el sitio responde con **404** —y el 404 aquí es real y contundente, por diseño
  (`politicas/[slug].vue:74-82`).
- **Acción:** decidir una de las dos listas y corregir la otra **antes** de implementar
  `/entidad`.

### 5.3 El contrato declara `x-status` y el backend no lo respeta

- `contract/openapi.yaml:65` marca `/entidad` como `x-status: pending`, con
  `x-criterios: [FUN-007, FUN-009, FUN-014, FUN-015, SEG-006, CAG-12]`. Es coherente: el
  backend no la implementa. Pero `README.md:119-120` fija «**Contrato primero.** Ninguna
  línea de implementación existe sin su operación en el contrato», y la dirección inversa
  —una operación declarada sin implementación— no tiene puerta que la detecte. Existe una
  prueba de deriva (`Makefile:63`, `php artisan test --filter=ContratoDeriva`) y **no he
  podido comprobar qué direcciones cubre**.
- **`[DEDUCCIÓN]`** Si la prueba sólo comprueba que no sobre ni falte ruta declarada, una
  operación `pending` puede quedarse pendiente indefinidamente sin que nada avise. Conviene
  que la prueba falle ante una operación `pending` con más de un ciclo de antigüedad.

### 5.4 El menú principal y el `sitemap.xml` dicen cosas distintas de `/servicios`

El menú lleva «Atención y Servicios a la Ciudadanía» a `/tramites`
(`layouts/default.vue:52-62`) y `/servicios` no aparece en él; pero `/servicios` sí está en
el carrusel y la tarjeta de la portada y en el `sitemap.xml`
(`sitemap.xml.ts:54`). El mapa del sitio describe `/servicios` como sección de primer nivel
y anuncia que está **vacía** (G-08, M-10).

### 5.5 El expediente dice «traducción diferida» y el encargo la cuenta como faltante

`sede-electronica-doc/01-estructura-identidad/sede.md:24` registra una decisión explícita:
«*Decisión #16 (2026-06-05): DIFERIDO — castellano únicamente por ahora; lenguas étnicas a
futuro*». La ausencia del selector de idioma **no es una contradicción con el diseño: es la
consecuencia de una decisión**. Se documenta en M-06 como no incumplimiento.

### 5.6 El Kit UI documenta componentes que su propio código no implementa

No es una contradicción del proyecto, sino del **insumo**, y explica G-02 y G-05:
- Siete componentes del PDF (`docs/Sección 2 · Diseño.md:206-209`) no tienen carpeta propia
  en `src/`: Área de servicio, Descripción emergente, Etiquetas, Indicador de carga, Línea
  de avance, Módulo de inicio de sesión y Paginación. El propio expediente lo advierte.
- El Kit **no trae una sola clase de Bootstrap** y sin embargo la necesita: el proyecto lo
  resolvió vendorizando Bootstrap 5.0.2 aparte (`nuxt.config.ts:44-61`). Eso **no está mal
  hecho**: está hecho y documentado. Lo que queda es que cualquier reimplementación futura
  tiene que saberlo, o volverá a producir una página «sólo con los colores del Kit».
- El Kit documenta tipografía e interlineado que su `all.css` no aplica (G-05).
- El botón de contraste del Kit es un *stub* y el proyecto lo reemplazó por uno real
  (`components/govco/BarraAccesibilidad.vue:11-13`, `useAccesibilidad.ts:16-20`).
- El Kit pinta el logotipo del Gobierno Nacional donde va el de la autoridad, y el proyecto
  lo corrige (`components/govco/CabeceraGovco.vue:19-25`).

---

## 6. Lo que ya está bien (y no hay que romper «arreglándolo»)

Esta sección existe para que una corrección futura no deshaga trabajo correcto. Todo lo que
sigue está verificado en el código.

1. **El menú de siete ítems y su orden.** Los tres mínimos obligatorios primero
   (Transparencia, Atención y Servicios a la Ciudadanía, Participa) y los adicionales detrás,
   con las **seis subcategorías exactas** de Participa (`layouts/default.vue:47-95`,
   comentarios en `:39-46`). Coincide con el Anexo 2 §4.1.2. **No reordenar.**

2. **La miga de pan se deriva del menú, no se escribe página a página**
   (`layouts/default.vue:105-176`). Es lo que impide que el nombre de la miga y el del menú
   diverjan y que una página nueva herede miga sin que nadie se acuerde. El truco de buscar
   el prefijo más largo (`:151-156`) resuelve bien las nueve categorías de Transparencia.

3. **El `<main>` y el salto al contenido viven en la disposición, no en cada página**
   (`layouts/default.vue:217-225`), con `tabindex="-1"` para que el foco se mueva de verdad
   y `outline: none` en el destino para que no dibuje un marco sobre todo el contenido.
   Cualquier página nueva hereda el enlace funcionando.

4. **El 404 es un 404 de verdad.** `pages/[...ruta].vue:20-27` fuerza
   `statusCode: 404` con `fatal: true`, y `error.vue:37` monta la disposición a mano porque
   Nuxt no la aplica a ese fichero. El comentario de `[...ruta].vue:9-19` explica por qué
   esto importa más aquí que en un sitio cualquiera (contenido duplicado infinito). **No
   sustituir por una redirección a la portada.**

5. **El paso de las políticas y los slugs cerrados responden 404 real.**
   `politicas/[slug].vue:74-82` y `participa/[slug].vue:61-63`. Una dirección inventada no
   puede devolver 200 con una página vacía: en una sede electrónica eso es peor que un error.

6. **El contraste de alto contraste es real y no decorativo.**
   `useAccesibilidad.ts:16-20` y `sitio.css:10-60`: el Kit sólo estilizaba su propia caja de
   demostración. Además se distinguen los estados con `aria-pressed`
   (`BarraAccesibilidad.vue:45`), que es lo que un lector de pantalla necesita.

7. **El escalado de letra escala la raíz y no escribe `font-size` en línea en cada
   elemento** (`useAccesibilidad.ts:7-14`). La razón está escrita y es correcta: el enfoque
   del Kit no alcanza al contenido que llega después de una llamada a la API.

8. **El carrusel tiene los tres controles obligatorios** —indicadores de posición, flechas y
   reproducción/pausa— con etiquetas accesibles que cambian con el estado
   (`CarruselGovco.vue:248-280`), los controles no se solapan con la imagen
   (`:358-400`), y respeta `prefers-reduced-motion` (`:195-200`). El defecto de M-04 es el
   valor inicial, no el componente.

9. **El pie de página publica los datos del Anexo 2 §2.2.1** con la estructura correcta:
   logo GOV.CO y marca país, nombre de la Entidad, dirección con departamento y municipio,
   código postal, horario, tres redes sociales, conmutador con `+57` e indicativo, línea
   gratuita **sin** `+57` (que es la excepción que la norma contempla), línea anticorrupción,
   correo institucional, correo de notificaciones judiciales, mapa del sitio y las cinco
   políticas (`PiePaginaGovco.vue:150-234`). **No «simplificar» esto.**

10. **El enlace de accesibilidad va en el pie y la página no afirma conformidad.**
    `PiePaginaGovco.vue:223-232` cita el Anexo 1 §4.3.2 (c) y
    `pages/accesibilidad.vue:10-17` explica por qué **no** declara un nivel. Las dos
    decisiones son correctas. **No añadir un sello «Cumple AA» sin la auditoría de G-01.**

11. **`/transparencia` publica las nueve categorías con cuatro estados distinguidos**
    (`publicada`, `parcial`, `declarada`, `ausente`), cada una con su número, su norma, sus
    apartados y el motivo de lo que falta (`pages/transparencia/index.vue:20-46`). Enseñar
    los cuatro estados como «en preparación» sería decir que están igual de vacías, y no lo
    están.

12. **La procedencia de cada documento está publicada, no oculta.**
    `pages/transparencia/[slug].vue:776-833` muestra la fuente, los campos que no vienen tal
    cual, las reglas con las que se calculó y lo que la fuente no declara. **Ese modelo es
    el activo más valioso del proyecto** y es lo que hay que migrar a base de datos en B-08.

13. **El orden cronológico no se finge.** `transparencia.ts:2584-2604`: mientras ningún
    documento declare fecha, se devuelve el orden de la fuente en lugar de inventar una
    cronología. Es la conducta correcta ante el vacío de B-07.

14. **La ausencia de datos se declara en pantalla y no se rellena.**
    `realizar-una-peticion.vue:402-410` (el formulario no radica),
    `pages/verificar/[codigo].vue:48-54` (la verificación no certifica nada),
    `pages/buscar.vue:44-47` (el índice está vacío), `pages/politicas/[slug].vue:111-117`,
    `components/SeccionEnPreparacion.vue:18-26`. Es la razón por la que esta auditoría tiene
    que ser explícita: **el sitio ya dice la verdad sobre sí mismo**. Buena parte de lo que
    aquí se llama «hallazgo» está reconocido en la propia interfaz. Eso es una virtud, no un
    defecto, y es lo primero que hay que preservar.

15. **El catálogo de trámites está completo y es honesto.** 123 trámites con los seis
    atributos del Anexo 2.1 §5.1.3, con paginación y búsqueda **en el servidor**, filtros
    validados contra el contrato (`ListarTramitesRequest`), mensajes de error en castellano
    escritos a mano con la razón declarada (`ListarTramitesRequest.php:64-74`) y los tipos
    generados desde el contrato (`types/openapi.d.ts`, importado en
    `pages/tramites/index.vue:46` y `[slug].vue:44`). **El contrato primero se está
    cumpliendo aquí de verdad.**

16. **La integración continua es seria y no decorativa.** Cinco trabajos bloqueantes
    (`ci.yml`: Contrato, Backend, Panel, Sitio, Pila) más revisión de dependencias, con una
    comprobación **negativa** deliberada de que la pila deja de resolverse cuando falta un
    secreto (`ci.yml:172-186`). El trabajo `Sitio` comprueba tipos **porque**
    `nuxt build` no los comprueba (`ci.yml:142-146`). Lo que falta es añadir accesibilidad
    (G-01).

17. **La capa visual no depende de un tercero.** El Kit, Bootstrap y las tipografías se
    sirven desde el propio dominio (`nuxt.config.ts:56-71`, `public/govco/`), con la copy
    de Bootstrap verificada contra el `sha384` que publican los ejemplos del Kit.

18. **El logotipo es el de la Entidad y no lleva la clase del Kit que lo sustituiría**
    (`components/govco/CabeceraGovco.vue:19-25`). El Kit pinta ahí el del Gobierno Nacional.

19. **El `robots.txt` no oculta nada** (`public/robots.txt`) y explica por qué, en contraste
    con la plantilla por defecto del gestor anterior, que además publicaba rutas internas.

20. **`content-type` y `cache-control` del `sitemap.xml` son correctos**, y el `<loc>` se
    construye con URL absoluta con esquema a partir de la configuración y **no** de la
    cabecera `Host` (`server/routes/sitemap.xml.ts:92-98`, `:132-135`). La segunda decisión
    evita anunciar la IP de un balanceador.

---

## 7. Orden de corrección recomendado

El orden es por **valor para el ciudadano** y por **dependencia técnica**, no por facilidad.

### Etapa 1 — Lo que desbloquea todo lo demás (una sola pieza)

**B-08 · CMS y API de contenido.** Es la dependencia común de B-06, B-07, G-06 (lista
blanca), G-07, G-09, M-02 y M-03. Ninguna de esas correcciones puede hacerse bien mientras
el contenido sea una constante de TypeScript. Es también la que más efecto tiene sobre el
cumplimiento: sin ella, «publicar de manera inmediata o en tiempo real» (Anexo 2 §2.4.2 (e))
es materialmente imposible.

### Etapa 2 — Lo que la ley exige para poder operar

2. **B-03 · Radicación, acuse, radicado y seguimiento de PQRSD** (contrato → backend →
   formulario). Es el servicio que define una sede electrónica.
3. **B-02 · CAPTCHA accesible y control de tasa.** Va con B-03, no después: un formulario
   que radica sin control antispam es un formulario que se llena solo.
4. **B-01 · Consentimiento de cookies.** Independiente de todo lo demás; se puede hacer en
   paralelo desde el primer día.
5. **B-04 · Las cinco políticas.** No es programación: es un acto administrativo. **Debe
   empezar ya** porque su plazo no depende del equipo técnico.
6. **G-12 · RBAC y Policies.** Antes del primer endpoint de escritura (B-08, B-03), no
   después.

### Etapa 3 — Lo que cierra los huecos de la norma de publicación

7. **B-05 · Buscador de la sede** (necesita el índice de B-08).
8. **B-06 · Décima categoría de Transparencia** (tributaria) — resolver antes la
   contradicción 5.1.
9. **B-07 · Fecha de publicación obligatoria** — cuando el modelo pase a base de datos.
10. **G-11 · `/entidad` en el backend y consumo desde el sitio** — cierra M-07 y, resuelta
    antes la contradicción 5.2, evita cinco enlaces rotos en el pie.
11. **G-13 · Fichas de trámite en el sitemap.xml.**
12. **G-08 · `/seguimiento`** (con B-03) y **decisión sobre `/servicios`** (M-10).

### Etapa 4 — Lo que hace demostrable lo que el sitio afirma

13. **G-01 · Suite de accesibilidad y declaración de conformidad.** Es la única forma de
    saber si el resto del sitio cumple WCAG 2.1 AA. Debería adelantarse en paralelo a la
    Etapa 3 si hay capacidad: mide trabajo futuro tanto como el presente.
14. **G-05 · Tipografía e interlineado** — una sola hoja, 21 páginas; verificar con G-01
    antes y después (WCAG 1.4.12).

### Etapa 5 — El acabado que la norma exige y el ciudadano nota

15. **G-02 · Componentes del Kit que faltan**, priorizando Indicador de carga y Área de
    servicio. **G-03** y **G-04** van con ellos, en la ficha del trámite.
16. **G-06 · Aviso de salida a sitio externo** y lista blanca.
17. **G-07 · Módulo de noticias** y **G-09 · Participa** (los dos dependen de B-08).
18. **G-10 · Agendamiento de citas.**
19. **M-01 · Encuesta SUS** — sólo tiene sentido cuando haya servicio que evaluar.
20. **M-04, M-05, M-08, M-09, m-01, m-02, m-03** — correcciones puntuales y baratas, muchas
    de una línea.

### Lo que **no** hay que tocar en ninguna etapa

Todo lo listado en la sección 6. En particular: el orden del menú, la derivación de la miga
de pan, el 404 real, los slugs cerrados con 404, el escalado de letra por la raíz, el
contraste real, la publicación de la procedencia, la declaración de ausencia en pantalla y
la negativa a declarar conformidad de accesibilidad sin auditoría. **Casi todos los
hallazgos de este documento conviven con esas piezas sin contradecirlas: son huecos
alrededor de un trabajo bien hecho, no errores dentro de él.**

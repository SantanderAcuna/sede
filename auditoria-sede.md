# Auditoría del diseño de la Sede Electrónica

**Titular:** Alcaldía Distrital de Santa Marta · NIT 891.780.009-4
**Objeto:** contrastar lo construido (`sitio/`, `panel/`, `backend/`, `contract/`) contra la línea
base de requisitos (corpus de elicitación, `docs/`, Kit UI gov.co 9.2 y `vendor-src/`) y medir la
trazabilidad.
**Naturaleza:** auditoría de diseño y de ingeniería de requisitos. Señala qué falta o está mal; no
reescribe nada.
**Edición:** segunda. Sustituye a la de 1 117 líneas. Añade `panel/`, el cruce con `vendor-src/` y
`docs/`, la matriz de trazabilidad y el andamiaje de requisitos. La sección 0 detalla qué corrige de
la anterior.

---

## 0. Qué añade esta edición y qué corrige de la anterior

### 0.1 Lo que la edición anterior no cubrió

| Materia | Edición anterior | Esta edición |
|---|---|---|
| `panel/` | Declarado fuera de alcance («son objetos de otras auditorías») | **Cubierto por completo**: 34 ficheros de `panel/src/`, `package.json`, `vite.config.ts`, `tailwind.config.js` |
| `vendor-src/` | No se cruzó: se midió el Kit contra su PDF, no contra su código | **Cruzado**: los 26 ficheros de `examples/`, los 15 `.js` y los 47 `.css` de `src/` contra los 11 componentes del sitio |
| `docs/` | Sólo `Sección 2` (tabla de componentes) y `docs/transparencia.md` | **Cruzado entero**: las seis secciones, los 15 ADR, `trazabilidad.md`, `transparencia.md` |
| Trazabilidad | No había matriz | Sección 4: matriz requisito → fuente → criterio → estado → brecha para las 12 secciones |
| Cobertura | Cualitativa («0 construidas, 8 parciales…») | Sección 6: con las cuentas hechas y el denominador declarado |

### 0.2 Hallazgos de la edición anterior que **ya no son ciertos** y no se repiten

Se comprobó uno por uno. Repetirlos habría sido auditar un repositorio que ya no existe.

1. **B-06, B-07 y B-08 citan `sitio/app/types/transparencia.ts` en trece lugares.** Ese fichero **no
   existe**: `sitio/app/types/` contiene hoy un único fichero, `menu.ts` (1 462 bytes). El directorio
   `sitio/app/pages/transparencia/` tampoco existe. La premisa central de B-08 —«el contenido de
   Transparencia es una constante de TypeScript»— es hoy **falsa**.
2. **`m-01` es falso.** Decía que `sitemap.xml.ts:48` declara dos direcciones en la misma línea. La
   línea 48 es `{ ruta: '/seguimiento', … }`. La lista está bien formateada.
3. **El matiz de B-05** («la búsqueda *de la sección* de Transparencia sí está construida, con
   `buscarEnSeccion`») describe código borrado. Hoy no hay buscador de sección.
4. **G-13 recomendaba** que el sitemap enumerara las fichas «como ya hace con las nueve categorías
   de Transparencia» importándolas de `~/types/transparencia-indice`. Ese import **ya no existe**
   (verificado: cero coincidencias en `sitio/`). La recomendación se mantiene; su justificación, no.
5. **G-08 enumeraba cuatro stubs.** Hoy son **cinco**: `/transparencia` se sumó a `/servicios`,
   `/portales`, `/noticias` y `/seguimiento`.
6. **El recuento de «25 defectos medidos» del Kit se queda corto.** La medición reproducible da
   **239 defectos** (§9.4). El Kit sigue siendo el insumo peor construido del proyecto; la cifra
   anterior subestimaba el problema.
7. **La afirmación «el Kit tiene 24 ejemplos canónicos» es imprecisa.** `examples/` tiene **26
   ficheros `.html`**, de los cuales **3 son plantillas vacías** de 13 líneas (`all.html`,
   `general/example.html`, `integration/example.html`) que enlazan `../src/govco.css`, un fichero
   que **no existe**. Los ejemplos reales son **23**.

### 0.3 Hallazgos de la edición anterior que **sí se confirmaron**

B-01 (cookies), B-02 (captcha), B-03 (radicación), B-04 (políticas), B-05 (buscador), G-01 (sin
pruebas de accesibilidad), G-02 (componentes del Kit ausentes), G-06 (sitio externo), G-10 (citas),
G-12 (sin Policies), M-04 (carrusel con `autoplay: true`), M-06 (sin selector de idioma), M-09
(dependencias declaradas y no usadas). Todos se volvieron a comprobar con `grep` sobre el árbol
actual y siguen siendo ciertos en los términos exactos en que se reformulan aquí.

---

## 1. Alcance, método y fuentes

### 1.1 Qué se auditó

| Objeto | Cómo se miró |
|---|---|
| `sitio/` — Nuxt 4, 21 páginas, 11 componentes, 1 composable | lectura completa de `layouts/default.vue`, `nuxt.config.ts`, `package.json`, `assets/css/sitio.css`, `server/routes/sitemap.xml.ts`, los 11 componentes de `components/govco/`, `app/types/menu.ts`, `public/govco/`; lectura de las páginas con contenido (trámites, PQRSD, normativa, atención, políticas, participa, mapa del sitio, buscar, verificar) |
| `panel/` — Vue 3 + TypeScript + Tailwind, SPA | **los 34 ficheros de `panel/src/`**, `package.json`, `vite.config.ts`, `tailwind.config.js`, `postcss.config.js`, `README.md`, `index.html` |
| `backend/` — Laravel 13 | `routes/api.php`, `composer.json`, `app/Models/` (3 modelos), `app/Policies/` (vacío), `config/` |
| `contract/` | `openapi.yaml` (931 líneas): las 3 operaciones, los esquemas, `security` y `securitySchemes` |
| Corpus de elicitación | `README.md`, los 12 módulos, `_global/matriz-trazabilidad.md`, `_global/auditoria-cobertura.md`, `_bd/` |
| `docs/` | las seis secciones, `docs/adr/README.md` (15 ADR), `docs/trazabilidad.md`, `docs/transparencia.md` |
| `vendor-src/layout-govco-v5/` | los 26 `.html` de `examples/`, los 15 `.js` y los `.css` de `src/`, `all.css` (12 287 líneas) |
| Kit UI gov.co 9.2 | **el PDF** (`docs/65142f4c-…-kit-ui-9-2.pdf`), extraído con `pdftotext` para leer de primera mano el catálogo de componentes, la tabla de tipografía y las especificaciones por componente |
| Puertas de calidad | `.github/workflows/ci.yml` |

### 1.2 Cómo se cruzaron las cinco fuentes

- **`panel/` ↔ `sitio/` ↔ `backend/` ↔ `contract/`**: `grep` de uso real por fichero, para
  distinguir lo declarado de lo cableado (importaciones, guardas, llamadas HTTP).
- **`sitio/` ↔ `vendor-src/`**: extracción de las clases `*-govco` del ejemplo canónico de cada
  componente y comparación con las del componente Vue. **Aviso de método:** las comparaciones por
  texto dan **falsos positivos** cuando el componente enlaza clases dinámicamente (`:class`). Se
  comprobó cada divergencia una por una; las que aquí se afirman están verificadas contra el
  `all.css` real, no sólo contra el ejemplo.
- **`sitio/` ↔ Kit UI 9.2 (PDF)**: lectura del PDF como fuente primaria, no de resúmenes. De ahí
  salen la tabla de tipografía (§5.3 y GR-12) y las especificaciones de la barra superior (§GR-13).
- **Corpus ↔ `docs/` ↔ construido**: cada requisito del corpus se buscó en el código; cada criterio
  de `docs/` se buscó en su artefacto de evidencia declarado.

### 1.3 Qué quedó fuera

- **No se ejecutó ninguna aplicación.** No se levantó Nuxt, ni Vite, ni Laravel, ni PostgreSQL. Todo
  lo afirmado sobre lo construido sale de leer el código. **No hay medición en navegador**: ni de
  contraste, ni de reflujo a 320 px, ni de área táctil. Los contrastes y las áreas que se citan son
  **los que el propio código declara haber medido**, y se citan como tales.
- **No se auditó la infraestructura**: `docker/`, `deploy/`, `compose.yaml`, `docs-security/` y el
  aprovisionamiento del servidor. Se consultó `docker/nginx/conf.d/default.conf` una sola vez, para
  resolver a qué sirve `/admin` (GR-05); nada más.
- **No se auditó `plan.md`** (144 327 bytes) como fuente de requisitos. Se usó sólo para localizar
  decisiones ya registradas.
- **No se leyó `_bd/05-diagramas-trazabilidad.md`** (3 322 líneas) más allá de su cabecera: es un
  catálogo de diagramas y la matriz de `_global/` ya cubre la trazabilidad.
- **No se verificó el contenido de los PDF y PPTX de `docs/`** salvo el del Kit UI, que sí se
  extrajo. Los demás se citan por lo que los `.md` dicen de ellos.

### 1.4 Lo que no se pudo verificar

1. **Si el entorno de `staging` tiene el catálogo cargado.** La afirmación de que el catálogo está
   vacío porque la ingesta es un comando que el despliegue no ejecuta procede de una sesión
   anterior. **No la he comprobado** y no figura como hallazgo; aparece como riesgo abierto en §8.
2. **La exactitud del contenido institucional** de los datos de la Entidad (dirección, teléfonos,
   correos). Se comprobó su **estructura y su duplicación**, no su verdad contra el sitio oficial.
3. **Las casillas «Requerido, mínimamente, en:» del Kit UI.** El PDF las dibuja como casillas
   gráficas. La extracción de texto conserva **las etiquetas marcadas**, que es lo que se cita en la
   sección 5.2 (GR-14 y GR-17), pero no puedo garantizar que el orden extraído corresponda uno a uno con la casilla
   marcada de cada componente. Donde la duda importa, se dice.
4. **El texto literal del Anexo 2 y del Anexo 2.1 de la Resolución 2893 de 2020.** Se trabajó con
   `research/gov/anexos/anexo2_1.txt` y con las citas que hacen el corpus y `docs/`.
5. **Qué contiene `docs/*.pdf`**: «Criterios de aceptación de diseño / funcionalidad / seguridad».
   Los `.md` los citan como fuente normativa (FUN-001…006, SEG-001…012) pero son PDF escaneados.
   Las citas de esos criterios son las que transcriben los `.md`.

### 1.5 Convenciones

- `[HECHO]` — leído en el fichero citado, con `read`/`grep`/`ls`.
- `[DEDUCCIÓN]` — conclusión mía, no un hecho leído.
- `[SIN VERIFICAR]` — dato de una sesión anterior, no recomprobado.
- Cada archivo se cita como `ruta:línea`. Las líneas son las del árbol en el momento de la
  auditoría (rama `docs/auditoria-sede`, árbol limpio).
- **Severidad.** *Bloqueante*: su ausencia impide cumplir un requisito legal de obligado
  cumplimiento, impide prestar el servicio que define la sede, o impide demostrar el cumplimiento.
  *Grave*: incumple un requisito declarado, degrada el servicio o hace que el sistema afirme algo
  que no es cierto. *Medio*: incumple un requisito de prioridad baja, duplica trabajo o deja deuda
  declarada sin cerrar. *Menor*: defecto puntual sin efecto funcional.

---

## 2. Veredicto

**La sede está construida como escaparate y no como sede: publica con un rigor poco común —123
fichas de trámite completas, identidad institucional correcta, accesibilidad trabajada a mano,
procedencia de los datos declarada— pero no puede recibir una sola solicitud, no puede publicar un
solo documento sin desplegar código, y la capa administrativa que debía resolver eso (`panel/`) es
una maqueta sin autenticación, sin datos y sin una línea de conexión con la API; y, por encima de
todo, el proyecto certifica un 87 % de cumplimiento con una matriz de trazabilidad que apunta a 41
operaciones y a vistas que no existen.**

Lo construido es mejor de lo que su cobertura sugiere. Lo que falta no es acabado: es la mitad
operativa de la sede, y el instrumento para saber cuánto falta.

---

## 3. La línea base de requisitos: estado y reconciliación

Antes de auditar conviene saber **contra qué** se audita. Esta sección es la que hace posible todas
las demás, y es también donde está el problema más consecuente del proyecto.

### 3.1 El corpus de requisitos no está en el repositorio

**[HECHO]** El corpus que el encargo cita como `@sede-electronica-doc/` **no existe dentro del
workspace**. Vive en `/var/www/portal-smr-main/sede-electronica-doc/` (55 ficheros `.md`, 20 098
líneas), fuera del control de versiones. El `README.md` del proyecto lo declara como decisión: los
documentos primarios «no se versionan: pesan 84 MB, son documentos de terceros y su contenido está
transcrito en el expediente markdown de `docs/`» (`README.md:131-134`).

**[HECHO]** Pero el corpus **no está transcrito en `docs/`**. `docs/trazabilidad.md:169-183` y las
secciones 1-6 son un expediente **distinto**: 140 criterios con códigos propios (`CAG-`, `FUN-`,
`SEG-`, `O-`, `INT-`, `ACC-`), mientras el corpus usa `RF-Bn-nnn`, `RNF-`, `RN-`, `HU-`, `UC-` (691
ítems declarados). **Son dos líneas base de requisitos, con identificadores distintos, que no se
referencian entre sí y viven en sitios distintos del disco.**

**[DEDUCCIÓN]** La consecuencia práctica es que la Entidad no tiene *una* línea base: tiene dos, y
ninguna de las dos está versionada junto al código. Cualquier afirmación de cumplimiento se apoya en
una de las dos sin poder cruzar con la otra. Esto no es un defecto del diseño de la interfaz; es el
defecto de fondo, y explica por qué la trazabilidad del proyecto (§3.4) puede estar rota sin que
nadie lo note.

### 3.2 Reconciliación de identificadores: las cuentas

Medido con `grep -ohE` sobre los 55 `.md` del corpus. «Citados» = el identificador aparece en algún
documento. «Definidos» = el identificador encabeza una fila de tabla en uno de los 12 módulos.

| Tipo | Declarado por el corpus (`README.md:134-139`) | Identificadores citados | Definidos como fila | **Citados sin enunciado** |
|---|---|---|---|---|
| RF | 353 | 325 | 201 | **124** |
| RNF | 120 | 92 | 60 | **32** |
| RN | 93 | 87 | 62 | **25** |
| HU | 73 | 68 | 52 | **16** |
| UC | 52 | 52 | 34 | **18** |
| **Total** | **691** | **624** | **409** | **215** |

Y el delta de la segunda pasada tampoco cuadra:

| Tipo | Declarado (`_global/matriz-trazabilidad.md:419`) | Presente en el corpus |
|---|---|---|
| RF-D | +37 | 38 |
| RNF-D | +18 | 14 |
| RN-D | +54 | 49 |
| HU-D | +57 | 57 |

**Consecuencias verificables:**

1. **28 RF y 28 RNF declarados en el total no tienen ningún identificador en el corpus.**
   `README.md:134-139` declara 353 RF; sólo aparecen 325. No es un redondeo: los 28 restantes no son
   enumerables, así que no se puede comprobar si están implementados.
2. **215 identificadores se citan sin definirse en ninguna parte.** Por ejemplo `RF-B2-004`,
   `RF-B2-005`, `RF-B2-011`, `RF-B2-035`… (124 RF). Sólo existen como referencia cruzada. Un
   requisito que se cita pero no se enuncia no es un requisito: no tiene criterio de aceptación
   contra el que auditar.
3. **La fuente a la que el corpus remite no existe.** `README.md:141` dice que los IDs «trazan hacia
   los catálogos fuente en `/tmp/elicit/out/_req_bundle_{1,2,3}.md`». Comprobado: `/tmp/elicit` no
   existe. **La trazabilidad hacia atrás que el corpus promete es irrecuperable.**
4. **`_global/matriz-trazabilidad.md:415` reconoce el desajuste sin resolverlo**: declara «~155 RF
   filas» y «~64 RNF agrupados» frente a los 353 y 120 del mismo corpus, con la nota de que «los IDs
   `RF-Bn-xxx` con dobletes/tripletes consolidan los 353 RF originales». Los dobletes existen
   (`RF-B1-001 / RF-B2-004 / RF-B3-053` es una fila para tres identificadores), pero **no explican
   las 28 ausencias ni los 215 huérfanos**.

### 3.3 El módulo 01 está duplicado byte a byte

**[HECHO]** `01-estructura-identidad/estructura-identidad.md` y `01-estructura-identidad/sede.md`
tienen **181 líneas cada uno y son idénticos**: mismo `md5`, `2fdd6d353abd433de01208bb7f05c55e`,
mismo tamaño. Es la única duplicación exacta del corpus (comprobado con `md5sum` sobre los 55
ficheros).

**[DEDUCCIÓN]** El titular citó **los dos** ficheros como fuente. Al ser el mismo, hoy no hay
conflicto; en cuanto alguien edite uno —lo hará, porque el nombre invita a editar el que suene más
específico— quedan dos módulos 01 divergentes y ninguna regla que diga cuál manda. Es el defecto más
barato de arreglar y el que más fácilmente se convierte en un problema caro.

### 3.4 La matriz de trazabilidad del proyecto no traza

`docs/trazabilidad.md` (232 líneas) es **el único artefacto de trazabilidad del proyecto**.
Se presenta así: «**Documento generado.** No se edita a mano: se produce con `npm run trazabilidad`
a partir del contrato OpenAPI, de las pruebas y de las vistas» (`docs/trazabilidad.md:3-5`), y
concluye: «**Cobertura: 122 de 140 criterios del expediente tienen evidencia (87 %)**»
(`:29`) y «Operaciones del contrato: **41** (41 implementadas)» (`:31`).

**Ninguna de las tres afirmaciones se sostiene contra el repositorio actual.** Verificado una por
una:

| Lo que afirma | Lo que hay | Prueba |
|---|---|---|
| 41 operaciones en el contrato, 41 implementadas | **3 operaciones**, 2 implementadas y 1 `pending` | `grep -cE "^    (get\|post\|patch\|put\|delete):" contract/openapi.yaml` → `3`; `contract/openapi.yaml:65` `x-status: pending` |
| FUN-016…FUN-020 «implementado», con interfaz `views/publico/TransparenciaView.vue` | **`views/publico/` no existe**; `/transparencia` es un stub de 17 líneas | `find . -type d -name publico` → nada; `sitio/app/pages/transparencia.vue` (17 líneas) |
| La matriz proviene de «las pruebas y las vistas» | Los ficheros citados no existen | `frontend/` no existe; `components/govco/BuscadorSede.vue`, `MenuNavegacion.vue`, `AvisoPrivacidad.vue`, `frontend/tests/conformidad-diseno.mjs`, `backend/tests/Feature/Sede/ConformidadSedeTest.php`, `backend/tests/Feature/Api/CatalogoApiTest.php`: **los 15 comprobados, ninguno existe** |
| Los endpoints de los criterios | `GET /contenidos/{tipo}`, `GET /buscar`, `GET /bloques`, `GET /transparencia` | Ninguno está en `contract/openapi.yaml` (0 coincidencias cada uno) |

Las únicas pruebas que existen en el backend son siete ficheros y **ninguno** es de los citados:
`TramiteTest.php`, `TramiteSeederTest.php`, `IngestaTramitesTest.php`, `ReconciliacionSuitTest.php`,
`SaludTest.php`, `ContactoPublicableTest.php`, `TestCase.php`.

**[DEDUCCIÓN]** `docs/trazabilidad.md` describe una implementación anterior —una SPA `frontend/` con
`views/publico/`, 41 endpoints, una sección de Transparencia publicada y un componente
`AvisoPrivacidad`— que fue sustituida por `sitio/` + `panel/`. El generador `npm run trazabilidad`
tampoco existe (`sitio/package.json` y `panel/package.json` no lo declaran). **Es un documento
huérfano que certifica 87 % de cumplimiento sobre un sistema que no es el que se va a desplegar.**

Esto es un hallazgo bloqueante (§5.1, BL-10) y no un detalle documental: es el instrumento con el
que la Entidad creería estar cumpliendo.

---

## 4. Matriz de trazabilidad

**Cómo leerla.** Una fila por requisito nuclear. La columna *Fuente* cita la norma **y su numeral**,
o el requisito del corpus con su identificador. Cuando la exigencia sólo consta en el corpus y no en
la norma, se marca `[corpus]`: es un requisito del proyecto, no una obligación legal, y se audita
como tal. Cuando es una interpretación mía, `[DEDUCCIÓN]`.

Estados: **construido** · **parcial** · **stub** (existe la ruta y no hay contenido) · **ausente**.

### Módulo 01 — Estructura e identidad GOV.CO

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Barra superior GOV.CO en todas las páginas, 56 px, Cobalt `#0943B5`, área activa ≥44×44 px | Res. 1519/2020 Anexo 2; Kit UI 9.2 p. 7; RF-B1-001/RF-B3-053 | El enlace de la barra mide ≥44×44 px y su color computado es `#0943B5` | **parcial** | **GR-13**: el enlace mide 36 px de alto (`all.css:702-705`) |
| Pie GOV.CO con los ocho datos institucionales y las cinco políticas | Res. 1519/2020 Anexo 2 §2.2.1; RF-B1-002/RF-B2-006 | Los ocho datos y los cinco enlaces están en el pie de cualquier página | **construido** | El documento de cada política no existe (**BL-06**) |
| Menú principal con los 3 mínimos obligatorios primero, ≤7 ítems, ≤2 niveles, `aria-label` | Res. 1519/2020 Anexo 2 §4.1.2; Kit UI 9.2 p. 27 (CAG-09, CAG-10); RF-B1-003 | Menú con 7 ítems y `aria-label`; los 3 obligatorios antes de los adicionales | **construido** | — |
| Migas de pan en todas las páginas internas, derivadas de la jerarquía | Kit UI 9.2 p. 28 (CAG-11); RF-B2-038 | Toda ruta interna ≠ `/` muestra su recorrido con el nivel actual marcado | **construido** | — |
| Buscador interno en la cabecera que busque sólo contenido de la sede | Res. 1519/2020 Anexo 2 §2.4.1 (c); RF-B1-005/RF-B2-035 | Buscar un término devuelve resultados propios, ordenados por pertinencia | **ausente** | **BL-07**: el campo no busca |
| Autocompletado ≤10 sugerencias y tolerancia a errores | RF-B1-006/RF-B2-036 (Must) | Con ≥3 caracteres aparecen ≤10 sugerencias pese a errores tipográficos | **ausente** | **M-07** |
| Mapa del sitio navegable + `sitemap.xml` | Res. 1519/2020 Anexo 1 §4.3.1 (b); RF-B1-010 | `/mapa-del-sitio` y `/sitemap.xml` responden y coinciden | **parcial** | **GR-11**: el sitemap anuncia 5 páginas vacías; **GR-16**: sin fichas de trámite |
| Página 404 con ≥3 vías de navegación | RF-B1-007/RF-B2-039/RF-B3-142 (Must) | URL inexistente → 404 personalizado con ≥3 alternativas | **parcial** | **M-10** |
| Banner de consentimiento de cookies con aceptar/rechazar/configurar | RF-B1-008/RF-B2-011 + RF-01-D01 + RN-01-D01 (Must); Ley 1581/2012 | Ninguna cookie no esencial se activa sin consentimiento; el consentimiento se versiona y caduca a 12 meses | **ausente** | **BL-05** |
| Módulo de noticias en la portada | Res. 1519/2020 Anexo 2 §2.4.1; RF-B1-011 (Must) | Noticia con imagen 4:3/16:9, título ≤150 car., descripción ≤200 car. y fecha, en orden inverso | **stub** | **GR-11**, **BL-08** |
| Carrusel con indicadores, flechas y pausa, **pausa por defecto** | Kit UI 9.2 p. 22 (CAG-01, CAG-02); RF-B1-042/RF-B3-069 (Must) | Al cargar la portada el carrusel está detenido | **parcial** | **M-01**: `autoplay: true` |
| Aviso de salida a sitio externo con nombre del destino | RF-B1-071/RF-B2-040 (Must); ADR-0015 §3 lo declara «no aplica» | Pulsar un enlace externo abre confirmación con el dominio del destino | **ausente** | **GR-15** |
| Tipografía e interlineado del Kit (Nunito Sans / Verdana) | Kit UI 9.2 p. 12; RNF-B3-045 (Must) | `h1`…`h6` y párrafos con la familia y el interlineado de la tabla del Kit | **parcial** | **GR-12** |
| Identidad: el logotipo de la **autoridad**, no el de la Nación | Res. 1519/2020 Anexo 2 §2.3; RF-B1-043 | La cabecera y el pie muestran el escudo del Distrito con `alt` | **parcial** | ADR-0015 §7: falta el archivo oficial; hoy es el nombre tipográfico |
| Componentes del Kit obligatorios para sedes electrónicas | Kit UI 9.2 §«Requerido, mínimamente, en»; RN-B2-029 | Cada componente marcado para «Sedes electrónicas» existe y cumple su criterio | **parcial** | **GR-14** |

### Módulo 02 — Transparencia y acceso a la información

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Menú *Transparencia* con ≥10 subsecciones | Res. 1519/2020 Anexo 2 §2.4.2 (g); Ley 1712/2014 art. 2.1.1.2.1.4 D. 1081/2015; RF-B1-012/RF-B3-081 (Must) | Diez subsecciones navegables y con contenido | **stub** | **BL-09**: la sección entera no existe |
| Información en orden cronológico inverso | Res. 1519/2020 Anexo 2 §2.4.2 (e); RF-B3-087 (Must) | El elemento más reciente aparece primero | **ausente** | **BL-09**, **GR-09** |
| Fecha de publicación en **todo** documento | Res. 1519/2020 Anexo 2 §2.4.1 (e); FUN-019 (Must) | Cada registro publica su fecha; ninguna vista dice «no consta» | **ausente** | **GR-09** |
| Fuente única: otros menús redirigen sin duplicar | Res. 1519/2020 Anexo 2 §2.4.1 (f); RF-B3-088 (Must) | Un documento tiene una sola URL canónica | **ausente** | **BL-09** |
| Directorio de servidores públicos vinculado a SIGEP | Ley 1712/2014; RF-B1-017 (Must) | Nuevo servidor publicado en ≤1 día | **ausente** | **BL-09** |
| Normativa con los siete campos y enlace a SUIN | RF-B1-013/RF-B1-014 (Must); Res. 1519/2020 Anexo 2 §2.4.1 (g) | Cada norma con tipo, número, fechas, epígrafe, vigencia y enlace; enlace funcional al SUIN | **parcial** | **M-04**: sin SUIN |
| Información tributaria: predial e ICA con los elementos del tributo y calendario | Res. 1519/2020 Anexo 2 §2.4.2 (g) + Conpes 3956/2019; RF-B1-018/RF-B3-089/RF-B3-151 (Must) | Ficha con sujeto activo/pasivo, hecho generador, causación, base gravable y tarifa; calendario con vencimientos | **ausente** | **BL-09** |
| Datos abiertos federados a `datos.gov.co` | Res. 1519/2020 art. 7 y Anexo 4; RF-B1-019 (Must) | La categoría muestra el catálogo federado, no una copia | **ausente** | **M-03** |
| Publicación inmediata o en tiempo real | Res. 1519/2020 Anexo 2 §2.4.2 (e); RN-B1-002 | Un funcionario publica sin desplegar código | **ausente** | **BL-08** |

### Módulo 03 — Servicios y trámites

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Catálogo de trámites vinculado a SUIT con ficha en GOV.CO | Res. 1519/2020 Anexo 2 §2.4.3 (i); RF-B1-021 (Must) | Cada trámite con nombre, descripción, modalidad, costo, tiempo y enlace a GOV.CO | **construido** | 123 de 124 fichas (falta la que la ingesta no trajo) |
| Seis atributos obligatorios por trámite | `docs/Sección 5 · Funcionalidad.md:41-50` §5.1.3 (Must) | Si falta uno, el ítem no se publica | **construido** | — |
| Búsqueda, filtros y paginación | Res. 1519/2020 Anexo 2 §2.4.3 (i); RF-B1-022/RF-B2-091 (Must) | Filtrar por modalidad y costo devuelve el subconjunto correcto | **construido** | — |
| Las 4 etapas (Inicio → Solicitud → Procesamiento → Respuesta) en la ficha | RF-B2-028/RF-B3-071 (Must); Kit UI 9.2 p. 26 | La ficha presenta el trámite en los 4 momentos | **ausente** | **GR-10** |
| Línea de avance (stepper) | Kit UI 9.2 p. 26 (requerido en «Trámites y servicios»); RF-B2-028 | El ciudadano ve en qué paso está | **ausente** | **GR-14** |
| Área de servicio: «¿Cómo fue tu experiencia?» y «¿Tienes dudas?» | Kit UI 9.2 p. 18; RF-B3-065/RF-B2-033/034 (Must/Should) | Calificar FÁCIL/DIFÍCIL y ver el canal de dudas, en la ficha | **ausente** | **GR-17** |
| Indicador de carga | Kit UI 9.2 p. 25 (CAG-29); RF-B3-070 (Must) | Proceso >10 s informa del estado | **ausente** | **GR-14** |
| Paginación ≥44×44 px con `aria-current` | Kit UI 9.2 p. 30 (CAG-23); RF-B3-075 (Must) | Página activa marcada; controles de 44 px en móvil | **parcial** | Incrustada en `tramites/index.vue:781-1014`; no reutilizable |
| Tablas con ordenamiento | Kit UI 9.2 p. 32 (CAG-24); RF-B3-076 (Must) | Ordenar asc/desc por columna | **parcial** | Único uso, mal anidado: `pqrsd.vue:222` pone `tabla-govco` en el `<table>` y no en el contenedor → `all.css:10213` (`.tabla-govco table`) no encuentra tabla descendiente y `all.css:10168` deja `overflow`/`max-height` sobre un `display:table` |
| Consulta de estado por radicado | Res. 1519/2020 Anexo 2 §2.4.3 (iii) cond. 4; FUN-024 (Must) | Consultar un radicado devuelve su estado | **stub** | **BL-03** |
| Pagos electrónicos sin recargo | Decreto Ley 2106/2019 art. 17; Decreto 088/2022; RF-B1-029/RF-B3-123 (Must) | Se paga en línea sin costo adicional al presencial | **ausente** | — |
| Carpeta Ciudadana Digital | Decreto 620/2020; RF-B1-027 (Must) | El resultado del trámite llega a la CCD | **ausente** | — |

### Módulo 04 — PQRSD

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Formulario con los campos mínimos y modalidad anónima real | Res. 1519/2020 Anexo 2 §2.4.3 (iii); RF-B1-031/RF-B1-032 (Must) | Marcada la casilla, los campos identificatorios desaparecen | **construido** | — |
| Radicación con número único | Decreto Ley 2106/2019 art. 14; RF-B2-030 (Must) | Radicar devuelve un número de radicado | **ausente** | **BL-01** |
| Acuse de recibo con fecha y hora | Res. 1519/2020 Anexo 2 §2.4.3 (iii) cond. 1; RF-B3-099 (Must) | Radicar produce acuse inmediato con fecha y hora | **ausente** | **BL-02** |
| Radicado en ≤24 horas hábiles | Ley 1437/2011; Ley 1755/2015; RN-B3-026 (Must) | El acuse compromete y cumple el plazo | **ausente** | **BL-02** |
| Sin restricciones técnicas de formato, tamaño ni cantidad de adjuntos | Ley 1755/2015 art. 23 CP; RF-B1-033/RN-B1-010 (Must) | Un archivo grande y no estándar se acepta | **parcial** | El formulario no adjunta nada (**BL-01**) |
| Seguimiento por radicado | Res. 1519/2020 Anexo 2 §2.4.3 (iii) cond. 4; RF-B1-034 (Must) | El estado del radicado se consulta en línea | **stub** | **BL-03** |
| CAPTCHA accesible en todo formulario que capture datos | `docs/Sección 5 · Funcionalidad.md:198` FUN-004 y `:253` FUN-030 (Must); RF-B3-101/RF-B1-058 (Must); Kit UI 9.2 p. 29 | El formulario rechaza el envío sin resolver el reto, y existe alternativa accesible | **ausente** | **BL-04** |
| Mensaje de falla del sistema distinto del error de validación | Res. 1519/2020 Anexo 2 §2.4.3 (iii) cond. 5; RF-B3-102 (Must) | Una caída del servidor muestra mensaje propio con opción de reintentar | **ausente** | **BL-01** |
| Validación accesible con foco en el campo inválido | WCAG 2.1 §3.3.1/3.3.3; RF-B1-035 (Must) | El foco se mueve al primer campo inválido y el error se anuncia | **construido** | — |
| Integración con el SGDEA | Decreto Ley 2106/2019 art. 16; RF-B1-036 (Must) | Radicar crea expediente en Orfeo en <5 s | **ausente** | **BL-01** |
| Plazos diferenciados por tipo (15/10/30 días) | Ley 1755/2015 art. 14; RF-04-D03 (Must) | Cada tipo computa su plazo sobre el calendario hábil | **parcial** | Los términos se **publican** (`pqrsd.vue`); no se **computan** |

### Módulo 05 — Participa

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Sección *Participa* con las 6 subcategorías del Anexo 2 §4.1.2.3 | Res. 1519/2020 Anexo 2 §4.1.2; RF-B1-038 (Must) | Las 6 subcategorías navegables con contenido | **stub** | **GR-11** |
| Consulta ciudadana de normas en elaboración vía SUCOP | Ley 1757/2015; RF-B1-039 (Must) | Se radican aportes a un proyecto de norma | **ausente** | **GR-11** |
| Micrositios por grupo de interés | RF-B1-040 (Should) | Cada grupo caracterizado con su contenido en lenguaje claro | **ausente** | **GR-11** |
| Publicación del resultado del proceso participativo | Decreto 1081/2015 art. 2.1.2.1.14; RF-05-D02 (Must) | El consolidado de observaciones y su respuesta se publica | **ausente** | **GR-11** |
| Rendición de cuentas y control ciudadano con documentos reales | Ley 1757/2015; Anexo 2 §4.1.2.3 | Informes de gestión y audiencias publicados con fecha | **ausente** | **GR-11** |

### Módulo 06 — Canales de atención

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Canales con dirección, código postal, horario y contactos con `+57` | Res. 1519/2020 Anexo 2 §2.2.1-2.2.2; RF-B1-041/RN-B1-017 (Must) | Todos los datos con prefijo país salvo 018000/019000 | **construido** | Duplicado en tres ficheros (**M-05**) |
| Agendamiento de citas con confirmación | Res. 1519/2020 Anexo 2 §2.4.3 (ii); RF-B1-030 (Must) | Reservar una cita devuelve código de confirmación | **ausente** | **GR-18** |
| Alternativa no digital al agendamiento | Ley 1753/2015 art. 45 par. 1; RN-B2-023 (invariante) | El canal presencial y telefónico siguen disponibles | **parcial** | Los canales se publican; no hay agenda que alternar |
| Acceso inclusivo con puestos y apoyo en sede | RF-B1-095 (Must) | Un adulto mayor sin internet completa el trámite en sede | **ausente** | — |

### Módulo 07 — Accesibilidad (transversal)

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| WCAG 2.1 AA en todo el sitio | Res. 1519/2020 art. 3 y Anexo 1; Ley 1618/2013 art. 16(11); RNF-B1-014/RNF-B3-001 | Cero violaciones críticas o serias en axe-core sobre las 21 rutas | **parcial** | **GR-07**: nunca se ha medido |
| Declaración de conformidad publicada | Res. 1519/2020 Anexo 1 num. 9.3; `docs/Sección 3 · Accesibilidad.md:673,714` | Documento con alcance, herramientas, excepciones y fecha de próxima revisión | **ausente** | **GR-07** |
| Barra de accesibilidad persistente con letra y contraste | Kit UI 9.2 p. 6 (CAG-07); RF-B1-044 (Must) | Escala y contraste persisten entre páginas | **construido** | El Kit la trae como *stub*; el sitio la reimplementó |
| «Saltar al contenido principal» como primer elemento tabulable | Kit UI 9.2 p. 9 (CAG-08); WCAG 2.1 §2.4.1; RF-B3-022 (Must) | Primer Tab muestra el enlace y el foco entra en `#contenido-principal` | **construido** | — |
| Contraste ≥4.5:1 en todo texto | WCAG 2.1 §1.4.3; RNF-B1-017 (Must) | Ningún texto por debajo del umbral | **parcial** | Sin medir en navegador (§1.3) |
| Área táctil ≥44×44 px en móvil | Kit UI 9.2 pp. 7 y 30 (CAG-23); RNF-B3-005 (Must) | Todo control pulsable mide ≥44×44 px a 360 px | **parcial** | **GR-13** |
| Reflujo a 320 px sin scroll horizontal | WCAG 2.1 §1.4.10; RNF-B3-004 | A 320 px no hay desplazamiento horizontal | **parcial** | Corregido a mano y **sin prueba** (**GR-07**) |
| Respeto a `prefers-reduced-motion` | WCAG 2.1 §2.3.3; RNF-B1-018 | Con la preferencia activa, no hay movimiento | **construido** | Carrusel y «volver arriba» lo respetan |
| Multimedia con subtítulos, transcripción y LSC | Res. 1519/2020 Anexo 1 §1.5; RN-B3-002/RN-B3-003 (Must) | Todo vídeo nuevo con subtítulos; LSC en los 4 tipos | **ausente** | Sin multimedia que publicar (**BL-08**) |
| Bloqueo de publicación de multimedia inaccesible | RF-07-D01/RF-12-D05 (Must) | El CMS no deja publicar un vídeo sin subtítulos | **ausente** | **BL-08** |

### Módulo 08 — Usabilidad

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| URLs limpias, en castellano, sin tildes y jerárquicas | RF-B1-081/RF-B3-144 | La ruta describe el contenido | **construido** | — |
| Navegación global consistente en todas las páginas | RF-B2-072 (Must) | Mismo menú, mismo orden y mismos nombres | **construido** | — |
| Sin pop-ups no solicitados | RF-B1-086/RF-B3-051 (Must) | La portada no abre modales por su cuenta | **construido** | — |
| Encuesta de usabilidad visible | `docs/Sección 5 · Funcionalidad.md:295` FUN-052 (Must); RF-B1-087/RF-B2-083 (Should) | Encuesta operativa en ≥90 % de las páginas | **ausente** | **M-02** |
| SUS ≥68 y tasa de éxito ≥90 % | RNF-B1-030; `_global/matriz-trazabilidad.md:436` (decisión 2026-06-05) | Puntaje calculado, almacenado y publicado | **ausente** | **M-02** |
| Validación W3C de HTML y CSS | RF-B1-089/RF-B2-075 (Must) | El validador no reporta errores | **ausente** | Sin puerta en CI (**GR-07**) |
| Lenguaje claro con índice Fernández-Huerta ≥60 | RNF-B3-037/RNF-B1-038 | Descripciones con índice ≥60 | **parcial** | Sin medir |
| Código con pruebas y cobertura ≥70 % | RNF-B1-043 (Should) | Cobertura medida en CI | **parcial** | Backend con pruebas; **sitio y panel, ninguna** (**GR-07**) |

### Módulo 09 — Seguridad digital

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| HTTPS con redirección y certificado vigente | `docs/Sección 4 · Seguridad.md:168` SEG-001 (Must); RF-B1-055 | `http` redirige a `https` con certificado válido | **parcial** | Vive en nginx; fuera de alcance (§1.3) |
| Cabeceras de seguridad (CSP, HSTS, X-CTO, X-FO, Referrer-Policy) | SEG-012; ADR-0008 omite HPKP con justificación | `securityheaders.com` sin avisos salvo HPKP | **parcial** | Fuera de alcance (§1.3) |
| Cookies `Secure` + `HttpOnly` | SEG-005; RF-B1-057 (Must) | Toda cookie de sesión con ambos atributos | **parcial** | No hay sesión que proteger (**GR-01**) |
| CAPTCHA accesible y control de tasa | SEG-003; RF-B1-058/RF-B3-101 (Must) | El envío automático se bloquea sin dañar la accesibilidad | **ausente** | **BL-04** |
| RBAC, roles y privilegios, separación de funciones | Res. 1519/2020 Anexo 3 §2; RNF-B2-010; SEG-016 (MFA) | Quien crea un contenido no lo aprueba; el rol limita el acceso | **ausente** | **GR-01**, **GR-19** |
| Registro de auditoría con retención ≥5 años | SEG-013; RNF-B3-022 | Cada cambio queda en un log inmutable | **ausente** | **GR-19** |
| Control de acceso a la administración | RF-B1-062 (Must): «páginas de administración no accesibles desde internet sin autenticación» | `/admin` exige autenticación | **ausente** | **GR-01** |
| Protección de datos: aviso, autorización, ARCO, RNBD | Ley 1581/2012 arts. 8, 13, 17, 25; RF-B2-047/048 (Must) | Aviso y casilla no premarcada; solicitud ARCO tramitable | **parcial** | El formulario autoriza el tratamiento; sin módulo ARCO |
| Mensajes de error genéricos sin filtrar tecnología | SEG-011; RF-B1-061 (Must) | Un error 500 no revela versión ni traza | **parcial** | Laravel por defecto; sin verificar en producción |

### Módulo 10 — Interoperabilidad

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Servicios Ciudadanos Digitales: autenticación, interoperabilidad, carpeta | Decreto Ley 2106/2019 arts. 9 y 10; RF-B1-025 (Must) | Autenticación por nivel de confianza vía OIDC; SCD operativos | **ausente** | Sin convenio con la AND (§8) |
| No exigir documentos que el Estado ya tiene | Decreto Ley 2106/2019 art. 10 inc. 4; RF-B2-094 (Must) | El formulario no pide lo que puede consultar | **ausente** | — |
| X-Road / PDI con estampado cronológico | RF-B1-028/RF-B2-018 (Must) | Mensaje cifrado con estampa TSA | **ausente** | — |
| Actualizar SUIT en ≤3 días hábiles | Ley 2052/2020 art. 19; RN-B1-006 | Tras un acto administrativo, la ficha SUIT queda actualizada | **parcial** | La ingesta existe; el despliegue no la ejecuta **[SIN VERIFICAR]** |
| Expediente electrónico con integridad y autenticidad | Decreto Ley 2106/2019 art. 16; RF-B3-095 | Expediente foliado, con índice firmado | **ausente** | SGDEA = Orfeo, sin implementar |

### Módulo 11 — Datos abiertos

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| Publicar y federar datos abiertos a `datos.gov.co` | Res. 1519/2020 art. 7 y Anexo 4; RF-B1-019 (Must) | Los conjuntos propios aparecen en el portal nacional | **ausente** | **M-03** |
| Formatos abiertos y procesables (≥90 %) | RNF-B3-039/RNF-B1-090 (Must) | Descarga en CSV, JSON o XML | **ausente** | **M-03** |
| Metadatos y registro de activos de información | Ley 1712/2014; RF-B1-020/RF-B1-091 (Must) | Inventario con criticidad y licencia | **ausente** | **M-03** |
| Licencia abierta declarada por conjunto | RN-B1-026(f2); `docs/Sección 5 · Funcionalidad.md:307` FUN-059 | Cada conjunto declara su licencia | **ausente** | **M-03** |

### Módulo 12 — Gestión de contenidos y administración

| Requisito | Fuente que lo exige | Criterio de aceptación verificable | Estado | Brecha |
|---|---|---|---|---|
| CMS que publique sin conocimientos técnicos, con roles y log | RF-B1-076 (Must); `docs/Sección 1 · Marco normativo.md:49` («CMS unificado; sin micrositios huérfanos») | Un editor publica una resolución sin desplegar código y queda en el log | **ausente** | **BL-08**, **GR-01** |
| Flujo editorial Borrador → Pendiente → Publicado → Archivado | RF-12-D01/RN-12-D01 (Must) | El rechazo devuelve a Borrador con comentario al editor | **ausente** | **BL-08** |
| Segregación de funciones: quien crea no aprueba | RF-09-D03/RN-09-D02 (Must) | El creador no puede aprobar lo propio | **ausente** | **GR-19** |
| Roles y permisos (≥3 niveles) con auditoría | RF-B1-079/RF-B2-097 (Must) | Un editor de normativa no accede a PQRSD de otra dependencia | **ausente** | **GR-01**, **GR-19** |
| Registro electrónico 24/7/365 con consecutivo y acuse | RF-B2-067/RF-B3-118 (Must) | Documento de las 23:55 de un festivo → radicado inmediato | **ausente** | **BL-01**, **BL-02** |
| Calendario oficial de días hábiles para cómputo de plazos | RF-B2-068/RF-B3-119 (Must) | El cómputo excluye fines de semana y festivos del Distrito | **ausente** | — |
| Archivo conforme a TRD/AGN; no eliminar sin aprobación | Decreto 1080/2015; Ley 594/2000; RF-B1-077 (Must) | Eliminar requiere aprobación superior y queda en el log | **ausente** | **BL-08** |
| Tablero ITA interno que arranca en cero, sin datos de maqueta | RF-B1-078/RF-12-D05 (Must); A-03 resuelto el 2026-06-05 | Al publicar contenido que incumple, el tablero lo marca con su norma | **ausente** | **GR-03** afirma lo contrario en pantalla |
| Notificaciones electrónicas multicanal con constancia | Decreto Ley 2106/2019 arts. 46-47; RF-B3-153/154 (Must) | Cambio a «Resuelto» → notificación <1 h por el canal preferido | **ausente** | — |
| Firma electrónica con efectos jurídicos | Decreto Ley 2106/2019 art. 60; RF-B3-155 (Must) | Documento firmado válido y archivado en el SGDEA | **ausente** | — |

**Recuento de la matriz:** 96 requisitos trazados (17 en el módulo 01, 9 en el 02, 13 en el 03, 10 en
el 04, 5 en el 05, 4 en el 06, 10 en el 07, 8 en el 08, 9 en el 09, 5 en el 10, 4 en el 11 y 10 en el
12). Estados: **construido 17 · parcial 22 · stub 4 · ausente 53**.

---

## 5. Hallazgos

Cada hallazgo es **una sola cosa que corregir**. Si dos arreglos son independientes, son dos
hallazgos.

### 5.1 Bloqueantes

Un hallazgo es bloqueante cuando su ausencia **impide cumplir un requisito legal de obligado
cumplimiento**, **impide prestar el servicio que define la sede**, o **impide demostrar el
cumplimiento**.

---

#### BL-01 · No hay radicación: el contrato no declara ninguna operación de escritura

- **Qué falta.** El formulario de `sitio/app/pages/realizar-una-peticion.vue` (1 097 líneas) tiene
  todos los campos del anexo y termina en `enviar()` (líneas 359-378) sin destino. El contrato
  declara **tres operaciones, todas `GET`**: `/entidad` (`contract/openapi.yaml:54`), `/tramites`
  (`:129`) y `/tramites/{slug}` (`:289`). No hay ninguna operación de escritura, ni ruta para
  PQRSD en `backend/routes/api.php:22-27`, que registra dos. La propia página lo declara en pantalla
  (`realizar-una-peticion.vue:402-410`: «al enviarlo **no se registrará nada**»).
- **Norma.** Decreto Ley 2106/2019 art. 14 inc. 1 y 3; Res. 1519/2020 Anexo 2 §2.4.3 (iii),
  condiciones técnicas 1, 4, 5 y 6; Ley 1437/2011 art. 60. En el corpus: RF-B2-030 (Must), RF-B3-102
  (mensaje de falla), RF-B1-036 (SGDEA), RN-B3-026.
- **Dónde.** `contract/openapi.yaml` (3 operaciones); `backend/routes/api.php:22-27`;
  `sitio/app/pages/realizar-una-peticion.vue:359-378` y `:402-410`.
- **Qué hay que hacer.** Declarar primero la operación en el contrato, con su esquema de error, su
  mensaje de falla y su punto de integración con el SGDEA; implementarla después; conectar el
  formulario al final. El aviso honesto de las líneas 402-410 **no debe retirarse hasta que la
  operación funcione de extremo a extremo**: retirarlo antes convertiría una carencia declarada en
  una promesa falsa.
- **Cómo se verifica.** Una prueba de integración que radique una PQRSD contra la API y afirme que
  la respuesta contiene un número de radicado con el formato acordado
  (`SM-{dependencia}-{año}-{consecutivo}`, RNF-04-D01); y una prueba de extremo a extremo que envíe
  el formulario desde el navegador y reciba ese número. La operación deja de estar ausente cuando la
  prueba falla sin ella.
- **Qué desbloquea.** BL-02, BL-03, GR-17, GR-18 (el expediente), y el módulo 12 entero.

---

#### BL-02 · No hay acuse de recibo ni compromiso de radicado

- **Qué falta.** El envío del formulario no produce confirmación alguna al ciudadano: no hay correo
  de acuse, ni fecha y hora de recepción, ni número de radicado, ni el plazo de las 24 horas
  hábiles. Sin BL-01 no hay nada que acusar, pero el requisito es **distinto y verificable por
  separado**: el acuse es un artefacto con contenido propio (identificador, fecha, hora, plazo) y su
  ausencia es una violación autónoma.
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.3 (iii) condición técnica 1 y el apartado «Condiciones de
  acceso a la información» (publicar los plazos de respuesta); Ley 1437/2011; Ley 1755/2015. En el
  corpus: RF-B3-099 (Must), RN-B3-026.
- **Dónde.** Ausente en `sitio/`, `backend/` y `contract/`. La página publica los términos del art.
  14 del CPACA pero no acusa nada.
- **Qué hay que hacer.** Que la radicación devuelva, en la misma respuesta y por correo, un acuse con
  el número de radicado, la fecha y hora exactas y el plazo comprometido.
- **Cómo se verifica.** Una prueba que radique y afirme (a) que existe el acuse, (b) que contiene un
  radicado, (c) que la fecha y la hora coinciden con la de recepción y (d) que el plazo declarado en
  el acuse es el que corresponde al **tipo** de solicitud (15, 10 o 30 días hábiles) y no un valor
  fijo.
- **Qué desbloquea.** BL-03.

---

#### BL-03 · No hay mecanismo de seguimiento en línea

- **Qué falta.** `sitio/app/pages/seguimiento.vue` tiene **19 líneas** y su plantilla es
  `<SeccionEnPreparacion>`. Está en el menú obligatorio (`layouts/default.vue:58`) y en el
  `sitemap.xml` (`server/routes/sitemap.xml.ts:48`). No hay consulta por radicado, ni endpoint, ni
  modelo.
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.3 (iii) condición técnica 4; `docs/Sección 5 ·
  Funcionalidad.md:237` FUN-024 (Must); RF-B1-034 (Must).
- **Dónde.** `sitio/app/pages/seguimiento.vue` (19 líneas); `contract/openapi.yaml`.
- **Qué hay que hacer.** Operación de consulta por radicado que devuelva tipo, fecha de radicación,
  dependencia asignada, estado y fecha estimada de respuesta —los cinco campos que el corpus fija en
  RF-B1-034—, y la pantalla que los muestre.
- **Cómo se verifica.** Consultar un radicado existente devuelve los cinco campos; consultar uno
  inexistente devuelve 404 con mensaje propio y no un 200 vacío (el proyecto ya tiene la postura
  correcta en `politicas/[slug].vue:74-82` y `[...ruta].vue:20-27`: **no la abandone aquí**).
- **Qué desbloquea.** Nada; cierra el módulo 04.

---

#### BL-04 · No hay control antispam, y el paquete ya decidido está instalado sin cablear

- **Qué falta.** `grep -rni "captcha\|recaptcha\|turnstile\|honeypot"` sobre `sitio/`, `sitio/server`,
  `backend/app`, `backend/config` y `backend/routes` devuelve **dos coincidencias, y las dos son
  Laravel**: la documentación de la opción `throttle` en `backend/config/auth.php:91,102`, que
  pertenece al guard de autenticación y no se usa porque no hay rutas de autenticación. **Cero
  controles en código propio.**
- **Y sin embargo la decisión ya está tomada y pagada**: `backend/composer.json:24` declara
  `"spatie/laravel-honeypot": "^4.7"`. Comprobado: **ningún fichero de `backend/app`, `backend/routes`,
  `backend/config` o `backend/database` lo usa**, y su fichero de configuración no se ha publicado
  (`backend/config/` no tiene `honeypot.php`).
- **Norma.** `docs/Sección 5 · Funcionalidad.md:198` FUN-004 y `:253` FUN-030 (Must: «Todos los
  formularios que capturen datos personales deben incluir […] **Captcha**»);
  `docs/Sección 4 · Seguridad.md:170` SEG-003 (Must); Res. 1519/2020 Anexo 2 §2.4.3 (iii) condición
  técnica 3 y Anexo 3 §9. En el corpus: RF-B3-101 y RF-B3-101/RF-B1-058 (Must), RN-07-D02 («captcha
  accesible obligatorio», que resuelve la contradicción *Captcha vs A11y* del propio corpus). Y el
  Kit lo exige también en el login: «el **Módulo de inicio de sesión** […] debe contener título,
  campo para ingresar contraseña, **módulo para confirmar que quien accede es un ser humano** y
  botones de navegación específicos» (Kit UI 9.2 p. 29).
- **Dónde.** `backend/composer.json:24`; ausencia en `backend/app/`, `backend/config/` y
  `sitio/app/`. El formulario de acceso del panel (`panel/src/views/acceso/EntrarView.vue`, 63
  líneas) tampoco lo tiene, aunque el Kit lo exige ahí (p. 29) y RF-B3-074 lo recoja (Must).
- **Qué hay que hacer.** Publicar la configuración del honeypot ya instalado, aplicar el middleware
  a los formularios públicos y añadir limitación de tasa por origen. **La vía menos arriesgada es
  la que la propia norma admite**: un reto invisible con alternativa accesible declarada, no un
  captcha de desafío visual, que incumpliría WCAG 2.1 §1.1.1 y §1.4.3.
- **Cómo se verifica.** Una prueba que envíe el formulario sin resolver el reto y afirme que la
  respuesta es un rechazo; otra que lo envíe con el reto resuelto y afirme que se radica; y una
  tercera que afirme que el reto es superable sin percepción visual. Que el paquete esté instalado
  **no cuenta como avance** mientras no haya middleware aplicado.
- **Qué desbloquea.** Nada. Sin esto, la sede no puede exponer ningún formulario público sin
  convertirse en un objetivo trivial.

---

#### BL-05 · No existe consentimiento de cookies

- **Qué falta.** El sitio no tiene banner, ni registro de consentimiento, ni revocación, ni
  caducidad. `grep -rni "cookie" sitio/app/` devuelve **cuatro coincidencias y las cuatro son
  rótulos o enlaces**: el catálogo de `politicas/[slug].vue:48-52`, su enlace en el pie
  (`PiePaginaGovco.vue:218`), su entrada en el mapa del sitio (`mapa-del-sitio.vue:100-101`) y su
  inclusión en el sitemap (`sitemap.xml.ts:68`). **No hay componente, ni lógica, ni persistencia.**
- **Norma.** RF-B1-008/RF-B2-011 (Must), RF-01-D01 (Must) y RN-01-D01 (invariante); Ley 1581/2012
  (consentimiento informado y temporal). El Anexo 2 §2.3.4 exige la política en el pie; la política
  sin mecanismo de consentimiento no sirve de nada.
- **Dónde.** Ausencia total en `sitio/app/`.
- **Qué hay que hacer.** Componente con aceptar / rechazar / configurar por categoría; persistencia
  **con versión de la política y fecha**; re-solicitud cuando cambie la versión o pasen doce meses
  (RN-01-D01); y ninguna cookie no esencial activa antes del consentimiento.
- **Cómo se verifica.** Tres pruebas: (1) con `localStorage` limpio, no se inyecta ningún script no
  esencial y el banner aparece; (2) aceptar analítica y recargar mantiene la elección; (3) con un
  consentimiento marcado con fecha de hace 13 meses o con versión de política anterior, el banner
  reaparece y ninguna cookie no esencial se reactiva.
- **Qué desbloquea.** Nada, y es la más barata de las bloqueantes.

---

#### BL-06 · Las cinco políticas obligatorias no tienen documento

- **Qué falta.** Existen las cinco **rutas** y ninguna **política**. `politicas/[slug].vue` (148
  líneas) contiene un catálogo cerrado de cinco títulos y propósitos (líneas 32-58) y, para cada una,
  un aviso de que el documento está en preparación (líneas 111-117). No hay PDF, ni HTML, ni acto
  administrativo de adopción, ni descarga en formato abierto.
- **Norma.** `docs/Sección 4 · Seguridad.md:173` SEG-006 (Must) transcribe el criterio de
  MinTIC: «Incorporamos una sección en la barra inferior (footer), con la documentación asociada al
  cumplimiento de las siguientes políticas: a. Términos y condiciones de uso. b. Seguridad y
  Privacidad. c. Protección y tratamiento de datos personales. d. Uso de Cookies. e. Derechos de
  Autor y uso sobre contenidos»; y `:146` SEG-006-RC-02: «Cada enlace dirige a un documento PDF/HTML
  accesible y **versionado, con fecha de última actualización y acto administrativo de adopción**».
  Res. 1519/2020 Anexo 2 §2.3.1 a §2.3.4. En el corpus: RF-B1-009/RF-B3-058, RF-B2-008, RF-B2-009,
  RF-B2-010 (todos Must).
- **Dónde.** `sitio/app/pages/politicas/[slug].vue:32-58` y `:111-117`;
  `components/govco/PiePaginaGovco.vue:211-234`.
- **Qué hay que hacer.** Es **un acto administrativo, no una tarea de programación**: la Entidad
  tiene que aprobar los cinco documentos. El diseño ya está preparado para recibirlos (catálogo
  cerrado, 404 real para slug inventado, navegación cruzada). Aparte, ya reconocido en el propio
  código (`politicas/[slug].vue:16-19`): **la política de derechos de autor no existe en la
  Entidad**, así que aquí no hay sólo una página vacía sino un incumplimiento abierto.
- **Cómo se verifica.** Cada uno de los cinco enlaces del pie devuelve un documento descargable con
  su fecha de última actualización y la referencia del acto que lo adopta; y ninguno de los cinco
  dice «en preparación».
- **Qué desbloquea.** Nada técnico, pero **debe empezar ya**: su plazo no depende del equipo.

---

#### BL-07 · El buscador de la Sede no existe: hay un campo que no busca

- **Qué falta.** Hay campo de búsqueda en la cabecera de todas las páginas
  (`layouts/default.vue:167-170`, componente `BuscadorGovco`, 167 líneas) y hay página de
  resultados (`buscar.vue`, 64 líneas), pero no hay índice ni operación que consulte. La página lo
  dice en pantalla (`buscar.vue:44-47`). El componente **no busca**: recoge el término y lo emite
  (`BuscadorGovco.vue:10-11`).
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.1 (c): «Se debe contar con un buscador en el que la
  ciudadanía pueda encontrar información, datos o contenidos», aplicable a **todo** el sitio; y el
  §2.4.2 (d), que lo exige además **dentro** de Transparencia. En `docs/`:
  `Sección 5 · Funcionalidad.md:195` FUN-001 (Must, «la búsqueda [se realiza] **dentro de la
  Sede**. No se puede contemplar un buscador que use Google») y `:225` FUN-017 (Must, buscador
  propio de Transparencia). En el corpus: RF-B1-005/RF-B2-035/RF-B3-067 (Must).
- **Dónde.** `sitio/app/pages/buscar.vue:44-47`; `components/govco/BuscadorGovco.vue`;
  `contract/openapi.yaml` (sin operación de búsqueda).
- **Matiz que hay que conservar.** La reimplementación del componente **mejora** el contrato del
  Kit: el JS del Kit enlaza el `keyup` al **primer** `.input-search-basic-govco` del documento
  (`vendor-src/layout-govco-v5/src/general/buscador.js:44`), que fallaría con los dos buscadores que
  el sitio tiene (`/tramites` monta otro). La versión Vue funciona por instancia. **No revertir
  esto.**
- **Qué hay que hacer.** Añadir la operación de búsqueda al contrato, indexar trámites y documentos,
  y conectar el campo. Mientras no exista, el campo de la cabecera **promete algo que no da**.
- **Cómo se verifica.** Una prueba que busque un término presente en el catálogo y afirme que
  devuelve ese resultado; y que afirme que el dominio consultado es el de la sede y **no** un
  buscador comercial (FUN-001).
- **Qué desbloquea.** La búsqueda de la sección de Transparencia (§4.1.2.3 del Anexo 2) reutiliza el
  mismo índice.

---

#### BL-08 · No hay gestor de contenidos: ningún funcionario puede publicar sin desplegar código

- **Qué falta.** Las tres piezas, cada una verificada:
  1. **En el backend** no hay modelo ni endpoint de contenido: `backend/app/Models/` tiene tres
     ficheros (`Tramite.php`, `IngestaTramite.php`, `User.php`) y el contrato tres operaciones de
     lectura. `backend/app/Policies/` contiene **sólo un `.gitkeep` de 0 bytes**.
  2. **En el panel** los **18 módulos** del menú —entre ellos `cms`, `sede`, `transparencia`,
     `portal`— apuntan a la **misma** vista de marcador: `panel/src/router/index.ts:69-76` los
     genera en bucle sobre `EnConstruccionView.vue`, que tiene **22 líneas** y sólo pinta el título.
     No hay formulario de publicación de nada.
  3. **En el frontend público** las cinco secciones que deberían publicar contenido son avisos de
     «en preparación» (GR-11).
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.2 (e): la información debe publicarse «de manera inmediata o
  en tiempo real». Con el contenido en el bundle, publicar exige un despliegue: la obligación es
  **materialmente imposible** de cumplir. `docs/Sección 1 · Marco normativo.md:408-410` deriva del
  art. 14 inc. 2 del Decreto 2106/2019 que «el sistema debe permitir que la entidad sea dueña
  operativa de su sede (**gestión de contenido**, configuración, sin dependencias técnicas externas
  que la conviertan en rehén)»; y `:49` traduce «sitios web» del art. 14 a «**CMS unificado**; sin
  micrositios huérfanos». En el corpus, el módulo 12 entero: RF-B1-076 (Must), RF-B1-079 (Must),
  RF-12-D01 (Must), RF-12-D02 (Must), RN-12-D01.
- **Dónde.** `contract/openapi.yaml`; `backend/app/Models/`; `backend/app/Policies/`;
  `panel/src/router/index.ts:69-76`; `panel/src/views/admin/EnConstruccionView.vue` (22 líneas);
  `panel/src/views/admin/InicioView.vue` (59 líneas).
- **Qué hay que hacer.** Es **el hallazgo con mayor efecto dominó** (§8). Modelo de datos,
  migraciones, contrato, repositorio, servicio, endpoints, **Policy por recurso** (`FormRequest` y
  `Policy` son exigidos explícitamente por `docs/transparencia.md:128-130`) y pantalla de
  publicación. Construir al menos el corte vertical de **un** tipo de contenido antes de generalizar.
- **Cómo se verifica.** Un editor autenticado crea un documento, lo envía a aprobación, otro usuario
  distinto lo aprueba y el documento aparece en el sitio público **sin redesplegar**; y un tercer
  usuario sin el permiso recibe 403. Mientras esa secuencia no se pueda recorrer en el navegador, el
  hallazgo sigue abierto.
- **Qué desbloquea.** GR-03, GR-09, GR-11, GR-15, GR-17, M-01, M-02, M-03, BL-09 y el módulo 12.

---

#### BL-09 · La sección de Transparencia no existe

- **Qué falta.** `sitio/app/pages/transparencia.vue` tiene **17 líneas** y su plantilla es
  `<SeccionEnPreparacion>`. El directorio `sitio/app/pages/transparencia/` no existe, y
  `sitio/app/types/` contiene un único fichero (`menu.ts`). No hay categorías, ni apartados, ni
  documentos, ni buscador de sección, ni fecha de publicación.
- **Norma.** Ley 1712/2014 arts. 9 y 11; Res. 1519/2020 Anexo 2 §2.4.1 y §2.4.2 (g) (información
  mínima obligatoria del art. 2.1.1.2.1.4 del Decreto 1081 de 2015); Res. 2893/2020 Anexo 2
  §4.1.2.1. En el corpus: RF-B1-012/RF-B3-081 (Must, ≥10 subsecciones), RF-B3-087, RF-B3-088,
  RF-B1-013, RF-B1-014, RF-B1-015, RF-B1-016, RF-B1-017, RF-B1-018, RF-B1-096, RF-B1-097, RF-B3-084,
  RF-B3-151, RF-B3-152.
- **Dónde.** `sitio/app/pages/transparencia.vue` (17 líneas).
- **Matiz imprescindible.** El contenido institucional **no** debe volver al frontend: la versión
  anterior (333 documentos incrustados) se retiró por decisión del titular y el análisis se conserva
  en `docs/transparencia.md:8-11`, que lo dice con precisión: «el contenido institucional no vive en
  el frontend; esta sección se construye cuando el backend la sirva por API». **Esta bloqueante es
  hija de BL-08 y no se resuelve sin ella.**
- **Aviso sobre las diez subsecciones.** El corpus (RF-B1-012) exige **diez**, nombrando por separado
  (9) «Obligación de reporte específico» y (10) «Tributaria (predial, ICA)». `docs/transparencia.md`
  habla de «nueve categorías». `README.md:19` del corpus dice «las 10 subsecciones». **Hay que
  resolver esa contradicción antes de construir** (§7.1): de ella depende si son una o dos
  categorías nuevas.
- **Qué hay que hacer.** Modelo, API y pantalla. Lo valioso de la versión retirada —el modelo de
  **procedencia** (fuente, apartado de origen, campos que no vienen tal cual de la fuente, reglas
  con las que se calculó, lo que la fuente no declara)— es lo que hay que migrar, no desechar
  (`docs/transparencia.md:124-126`).
- **Cómo se verifica.** Las diez subsecciones responden y contienen al menos un documento con su
  fecha de publicación; un documento de la categoría tributaria muestra los seis elementos del
  tributo; y el buscador de la sección devuelve resultados ordenados del más reciente al más antiguo.
- **Qué desbloquea.** M-03, M-04 y el cumplimiento del ITA en la materia de mayor peso.

---

#### BL-10 · La matriz de trazabilidad del proyecto certifica un estado que no existe

- **Qué falta.** `docs/trazabilidad.md` —el **único** artefacto de trazabilidad del proyecto— se
  presenta como documento generado y concluye: «Cobertura: **122 de 140** criterios del expediente
  tienen evidencia (**87 %**)» (`:29`) y «Operaciones del contrato: **41** (41 implementadas)»
  (`:31`). Verificado contra el repositorio:
  - El contrato declara **3** operaciones, no 41. Dos implementadas y una `pending`
    (`contract/openapi.yaml:65`).
  - FUN-016 a FUN-020 aparecen como «implementado» con interfaz
    `views/publico/TransparenciaView.vue` (`:102-106`). **Ese directorio no existe**: el sitio es
    Nuxt, no una SPA con `views/`. `/transparencia` es un stub de 17 líneas.
  - Los 15 ficheros de evidencia comprobados —`frontend/tests/conformidad-diseno.mjs`,
    `frontend/tests/conformidad-shell.mjs`, `components/govco/BuscadorSede.vue`,
    `components/govco/MenuNavegacion.vue`, `components/govco/AvisoPrivacidad.vue`,
    `views/publico/*.vue` (siete), `backend/tests/Feature/Sede/ConformidadSedeTest.php`,
    `backend/tests/Feature/Api/CatalogoApiTest.php`— **ninguno existe**. El directorio `frontend/`
    tampoco.
  - Los endpoints que la matriz asocia a los criterios (`GET /contenidos/{tipo}`, `GET /buscar`,
    `GET /bloques`, `GET /transparencia`) **no están en el contrato**: cero coincidencias cada uno.
  - El generador que la produce, `npm run trazabilidad`, **no está declarado** en
    `sitio/package.json` ni en `panel/package.json`.
- **Norma.** No es un incumplimiento normativo directo, y por eso se explica la severidad:
  **impide demostrar el cumplimiento**, que es la razón por la que existe una sede electrónica
  integrada. La verificación de MinTIC (proceso de 7 pasos, RF-B1-070/RF-B3-128) y el reporte del
  ITA a la Procuraduría dependen de esta evidencia. `docs/Sección 3 · Accesibilidad.md:68` describe
  el mecanismo: «la PGN monitorea el cumplimiento de la Resolución 1519/2020 a través del Formulario
  Único de Reporte de Avance en la Gestión (FURAG) y del Índice de Transparencia y Acceso a la
  Información Pública (ITA)».
- **Dónde.** `docs/trazabilidad.md:3-5, 29, 31, 48-106` (y todo el documento).
- **Qué hay que hacer.** Regenerarlo contra el árbol real o retirarlo y sustituirlo. **Retirarlo no
  es la opción cómoda: es la correcta si no hay generador.** Un documento que declara 87 % sobre un
  sistema que no existe es peor que no tener matriz, porque produce una decisión equivocada.
- **Cómo se verifica.** La matriz deja de ser un fichero escrito a mano cuando (a) `npm run
  trazabilidad` existe y se ejecuta en CI, (b) cada fila apunta a un artefacto que existe —una
  comprobación automática puede verificar que cada ruta citada está en el disco— y (c) el número de
  operaciones de la matriz coincide con el del contrato.
- **Qué desbloquea.** La posibilidad de medir cualquier otra cosa.

---

### 5.2 Graves

---

#### GR-01 · El panel no tiene ningún control de acceso: declara permisos y no los lee

- **Qué falta.** `panel/src/router/index.ts` declara en los metadatos de ruta
  `requiereSesion?: boolean` (`:28`), `permiso?: string` (`:29`) y `soloInvitados?: boolean` (`:30`),
  y los asigna: `requiereSesion: true, permiso: 'panel-administrative'` en el tablero (`:67`) y
  `requiereSesion: true` en los 18 módulos (`:74`). **Ninguna línea de código los lee.** La
  comprobación es exhaustiva: `grep -rn "beforeEach\|beforeResolve\|beforeEnter\|navigationGuard"`
  sobre `panel/src/` devuelve **cero coincidencias funcionales**; lo único que existe es un
  `enrutador.afterEach` que cambia el título del documento (`:119-121`). El comentario del propio
  fichero dice lo que debería pasar y no pasa: «Quien no ha entrado va a la entrada DEL PANEL» (`:6`).
- **Norma.** Res. 1519/2020 Anexo 3 §2: «Implementar o exigir controles de seguridad relacionados
  con la **autenticación, definición de roles y privilegios y separación de funciones**». En
  `docs/`: `Sección 4 · Seguridad.md:190` SEG-016 (MFA obligatoria para funciones administrativas);
  RF-B1-062 (Must: «páginas de administración **no accesibles desde internet sin autenticación**»);
  RF-B1-079/RF-B2-097 (Must, roles con permisos por módulo); `docs/Sección 5 · Funcionalidad.md:190`
  y `docs/trazabilidad.md:204` («solo_los_funcionarios_activos_administran_la_sede»). En el corpus:
  RN-09-D01 (autorización por recurso), RN-09-D04 (MFA).
- **Dónde.** `panel/src/router/index.ts:28-30, 67, 74, 119-121`.
- **Qué hay que hacer.** Escribir la guarda de navegación que consume esos metadatos y la tienda de
  sesión que los alimenta. Los metadatos ya están declarados: el trabajo es leerlos.
- **Cómo se verifica.** Con el almacenamiento del navegador vacío, abrir `/admin` redirige a
  `/admin/acceso`; con una sesión sin el permiso, abrir `/admin/usuarios` muestra `/admin/sin-permiso`
  —una ruta que **ya existe** (`:98-102`) y a la que hoy nadie llega—; y una prueba de extremo a
  extremo afirma los dos casos.
- **Qué desbloquea.** GR-02, GR-19 y cualquier endpoint de escritura de BL-08. **Debe resolverse
  antes del primer endpoint de escritura, no después.**

---

#### GR-02 · El panel afirma una sesión y un doble factor que no existen, y la sede enlaza a esa pantalla

- **Qué falta.** Tres afirmaciones falsas en la interfaz, todas verificables:
  1. `EntrarView.vue:60` muestra «**Sesión protegida con doble factor (Decreto 1078)**». No hay doble
     factor: el mismo fichero documenta que el envío «**no autentica**» (`:5-8`).
  2. `AdminLayout.vue:318-319` muestra «Administrador» y «**Sesión activa**» como texto fijo, y
     `:166` calcula las iniciales del avatar con `computed(() => 'AD')` —una constante—, con el
     comentario de que «la identidad real llega con el módulo de identidad» pero sin dejar de
     pintarla como si hubiera llegado.
  3. `layouts/default.vue:183` publica en la cabecera de **todas las páginas del sitio público** un
     enlace «Iniciar sesión» a `/admin/acceso`. El comentario que lo acompaña (`:172-181`) enuncia
     el principio correcto —«un botón que abre un formulario que **no autentica** es peor que no
     tenerlo, porque el ciudadano cree haber iniciado sesión»— y el código hace exactamente lo que
     el comentario prohíbe.
- **Norma.** El proyecto tiene una virtud declarada y documentada: **el sitio dice la verdad sobre sí
  mismo** (es el fundamento de la sección 9). Una afirmación falsa sobre un control de seguridad no
  es un detalle de acabado: es información engañosa sobre protección de datos. Ley 1581/2012; y, en
  el plano del diseño, WCAG 2.1 §3.2.4 (identificación coherente) y §1.3.1 (la información y la
  relación no dependen sólo de la presentación).
- **Dónde.** `panel/src/views/acceso/EntrarView.vue:5-8, 60`; `panel/src/layouts/AdminLayout.vue:166,
  318-319`; `sitio/app/layouts/default.vue:172-183`.
- **Qué hay que hacer.** Quitar la frase del doble factor y la de «sesión activa» hasta que existan;
  y decidir qué hace el enlace «Iniciar sesión» de la cabecera pública (retirarlo, o apuntarlo a la
  entrada de identidad ciudadana cuando exista). El texto de `AccesibilidadBar.vue` y los badges de
  estado del panel tienen el mismo problema si afirman operatividad: revísense con el mismo criterio.
- **Cómo se verifica.** `grep -rni "doble factor\|sesión activa\|Sesión protegida" panel/src` no
  devuelve nada, o lo que devuelva está respaldado por un módulo de identidad que existe.
- **Qué desbloquea.** Nada; protege la credibilidad del resto.

---

#### GR-03 · El panel publica cifras de maqueta, incluido el cumplimiento ITA que una decisión prohíbe

- **Qué falta.** `panel/src/views/admin/InicioView.vue` es el tablero de entrada del panel y muestra
  cuatro indicadores **fijos en el código** (`:26-29`): «PQRSD activas **287** (+12)», «Por vencer
  **34** (−8)», «Trámites SUIT **1842** (+5)» y «Cumplimiento ITA **94 %** (+3)». Y una lista de
  «Salud del sistema» (`:44-54`) con estados inventados: «API Gateway OK», «SIGMI Bus OK»,
  «Conector RNEC Lento», «Firma electrónica OK».
- **Norma. Es el punto más delicado del panel.** El corpus resolvió expresamente este problema como
  **A-03**, y su resolución está registrada en tres sitios:
  - `README.md` del corpus, §3: «El tablero ITA del sitio actual mostraba 218/412 ítems, 47/100,
    pero ese dato era **“mock”** (el HTML usa esa palabra) → **se descarta como línea base; no se
    usa**» y «El nuevo tablero ITA es **interno (no público)**, **arranca en cero** y valida
    automáticamente».
  - `12-gestion-contenidos/gestion-contenidos.md:34` (RF-B1-078, Must): «**No usa datos mock ni
    puntuaciones precargadas (no se usa el 47/100)**».
  - `_global/matriz-trazabilidad.md:389`: «✅ Resuelto 2026-06-05: el tablero ITA se rediseña como
    validador interno automático que **arranca en cero** y no usa datos mock ni el 47/100».
  El 94 % del panel es, además, la **única cifra de cumplimiento** que hoy produce el sistema —y es
  inventada.
- **Dónde.** `panel/src/views/admin/InicioView.vue:26-29` y `:44-54`. El comentario de cabecera
  (`:2-8`) reconoce que «las cifras son de maqueta», lo cual es honestidad de código pero **no
  cambia lo que ve quien entra**: un funcionario mira el tablero, no el código.
- **Qué hay que hacer.** Sustituir las cuatro cifras por el estado real (vacío declarado, o datos
  del backend cuando existan) con la misma postura que el sitio público: decir que no hay datos es
  correcto; mostrar 287 no. Retirar la lista de «Salud del sistema» hasta que exista monitorización.
- **Cómo se verifica.** Con la base de datos vacía, el tablero muestra «sin datos» y **no** un
  indicador de 94 %. Y una prueba afirma que ningún componente del panel contiene un literal
  numérico de cumplimiento.
- **Qué desbloquea.** BL-08 (el tablero real de ITA se alimenta de la validación al publicar).

---

#### GR-04 · El panel no habla con la API: cliente huérfano, sin tiendas y sin contrato de escritura

- **Qué falta.** Cuatro verificaciones, todas con el mismo resultado:
  1. **`panel/src/services/http.ts` (68 líneas) no lo importa nadie.** `grep -rn "services/http"`
     sobre `panel/src/**/*.vue|*.ts` sólo devuelve su propia definición. El cliente HTTP está escrito,
     documentado y desconectado.
  2. **`panel/src/types/api.ts` sólo lo importa `http.ts`.** La capa de tipos del contrato no llega a
     ninguna vista.
  3. **Pinia está instalado e instanciado y no hay ni una tienda**: `createPinia()` en `main.ts:27` y
     **cero** `defineStore` en todo `panel/src/`.
  4. **El contrato no tiene operaciones de escritura**: los tipos generados
     (`panel/src/types/openapi.d.ts`, 580 líneas) declaran **`/entidad`, `/tramites` y
     `/tramites/{slug}`**, los tres `GET`. La superficie de tipos del panel es el catálogo público.
     **Por eso el panel no puede tiparse contra ninguna API de publicación: no existe.**
- **Norma.** El proyecto declara que el contrato es «la única fuente de verdad del intercambio HTTP»
  (`README.md:58`, `contract/README.md:3-5`) y `backend/routes/api.php:13-15` repite que «ninguna
  ruta vive aquí sin su operación declarada». El panel no participa de ese contrato en absoluto:
  **hay un tercer cliente declarado en la arquitectura que no consume el contrato**.
- **Dónde.** `panel/src/services/http.ts`; `panel/src/types/api.ts`; `panel/src/main.ts:27`;
  `panel/src/types/openapi.d.ts:20-92`; `contract/openapi.yaml`.
- **Qué hay que hacer.** Es consecuencia de BL-08 y se resuelve con él: en cuanto exista la primera
  operación de escritura, el panel debe consumirla por `http.ts` y guardar el estado de sesión en una
  tienda. Hoy el hallazgo sirve para **no confundir el andamiaje con avance**: hay un cliente HTTP,
  un sistema de tipos y un gestor de estado, y ninguno está en el camino de datos.
- **Cómo se verifica.** `grep -rn "services/http" panel/src --include='*.vue'` devuelve al menos una
  vista, y esa vista pinta datos que provienen de la API (no de un literal).
- **Qué desbloquea.** BL-08.

---

#### GR-05 · ADR-0009 decide Filament 5 en `/admin`; lo construido sirve una SPA Vue en `/admin`

- **Qué falta.** El ADR-0009 lo dice sin ambigüedad: «Se incorpora **Filament 5** (panel `/admin`, en
  español) como CMS profesional: motor de contenidos con tipos y campos, flujo editorial […] roles y
  permisos (Spatie), registro de auditoría y publicación programada» (`docs/adr/README.md:224-231`),
  y añade que «el panel aporta **Livewire y Blade** como dependencias del backend» (`:234-236`).
  Verificado contra el repositorio:
  - **Filament no está instalado.** `grep -n "filament\|livewire" backend/composer.json` → cero
    coincidencias.
  - **`/admin` no lo sirve Laravel.** `docker/nginx/conf.d/default.conf:8` documenta «`/admin` → SPA
    del panel, servida desde disco» y `:124-129` lo resuelve con
    `try_files $uri $uri/ /admin/index.html`. La SPA está en `panel/` con `base: '/admin/'`
    (`panel/vite.config.ts:14`) y `createWebHistory('/admin')` (`panel/src/router/index.ts:114`).
  - **Los paquetes de Spatie sí se instalaron** (`backend/composer.json:22-27`), consecuencia parcial
    del ADR —y sin usar (**GR-19**).
  El ADR-0014 (`:377-380`) delegaba en la aplicación las rutas `/api`, `/admin`, `/livewire` y
  `/storage`; nginx sirve un fichero estático.
- **Norma.** No es una norma externa: es **la coherencia interna del propio proyecto**. `README.md`
  del repositorio describe la arquitectura vigente —«tres aplicaciones» con `panel/` SPA— sin
  mencionar que contradice un ADR aceptado. Un ADR que contradice lo construido sin ADR que lo
  superseda deja la decisión sin dueño.
- **Dónde.** `docs/adr/README.md:215-239` (ADR-0009) y `:377-380` (ADR-0014);
  `docker/nginx/conf.d/default.conf:8, 124-129`; `panel/vite.config.ts:14`;
  `panel/src/router/index.ts:114`; `backend/composer.json`.
- **Qué hay que hacer.** Decidir y **registrar**: o se construye Filament (y entonces `panel/` es
  trabajo perdido), o se mantiene la SPA y se escribe el ADR que sucede al 0009 explicando por qué
  (coste de Livewire/Blade, regla absoluta 2 de la guía de casa —«sin Inertia ni Blade»—, que es
  precisamente lo que el ADR-0001 invoca y el 0009 contradice). **La contradicción es hoy
  indistinguible de un olvido.**
- **Cómo se verifica.** Existe un ADR con fecha posterior al 0009 cuyo estado es «Aceptada» y cuyo
  texto describe el sistema que está en el disco; o `backend/composer.json` incluye
  `filament/filament` y nginx ya no sirve `/admin` desde disco.
- **Qué desbloquea.** BL-08: hoy no está claro sobre qué superficie se construye el CMS.

---

#### GR-06 · Los ADR describen una arquitectura que ya no existe, y ninguno tiene fecha

- **Qué falta.** El conjunto de decisiones está desalineado con el repositorio en los puntos
  estructurales, y **ninguno de los 15 ADR tiene fecha** (comprobado: ninguna entrada tiene campo
  «Fecha»), todos con «**Estado:** Aceptada». Sin fecha no hay orden, y sin orden no se sabe cuál
  sucede a cuál.
  - **ADR-0001** (`:19-23`): «Monorepo con `backend/` (Laravel 13, PHP 8.5, API REST) y
    **`frontend/`** (Vue 3 + TypeScript + Vite, SPA)». **`frontend/` no existe**; hay `panel/` y
    `sitio/`. El ADR tampoco contempla el sitio Nuxt, que es la pieza central del diseño.
  - **ADR-0003** (`:90-97`): «**No se instala Tailwind.** […] se descarta **FontAwesome**».
    `panel/package.json:21` declara `tailwindcss ^3.4.19`, hay `panel/tailwind.config.js` y
    `panel/postcss.config.js`, y `:12-14` declaran tres paquetes `@fortawesome/*`, todos en uso
    (`panel/src/plugins/fontawesome.ts`). El panel se construyó íntegramente con Tailwind y
    FontAwesome.
  - **ADR-0004** (`:107-113`): los componentes Vue «**reproducen el marcado oficial y delegan el
    comportamiento en el `script.js` del Kit cuando existe**». **ADR-0011** (`:282-284`) dice lo
    contrario: «**No se carga `/govco/script.js` de forma global**». Verificado en el disco: **el
    sitio no copió ni uno de los 15 `.js` del Kit** (`find sitio/public/govco -name '*.js'` → 0).
    La decisión que se siguió fue la del 0011, no la del 0004.
  - **ADR-0015** (`:412-424`) declara que «CAG-19, **CAG-21**, CAG-22, CAG-24 y CAG-25 — no aplican»
    porque «la sede no usa campos de calendario, **no abre modales** […]». Pero el aviso de salida a
    sitio externo es un **modal con confirmación** exigido por RF-B1-071/RF-B2-040 y por el Anexo 2
    §2.4.3: **CAG-21 sí aplica** y hoy no se cumple (**GR-15**). Y CAG-25 (acordeón) tampoco es
    prescindible: el Kit lo marca como **requerido en sedes electrónicas** (Kit UI 9.2, «Acordeón →
    Sedes electrónicas» en el bloque «Requerido, mínimamente, en» de su ficha). La desviación no es declarativa de una decisión: es un requisito sin
    construir.
- **Norma.** No hay norma externa: es control de configuración. `docs/adr/README.md:6-7` invoca la
  guía de casa y `GUIA-MAESTRA-COMPLETA.md`.
- **Dónde.** `docs/adr/README.md:19-23` (0001), `:90-97` (0003), `:107-113` (0004), `:282-284`
  (0011), `:412-424` (0015); `panel/package.json:12-21`;
  `find sitio/public/govco -name '*.js'` → 0.
- **Qué hay que hacer.** Poner fecha a los quince y añadir el estado «Sustituida por ADR-nnnn» donde
  corresponda. Un ADR sin fecha ni sucesor no es trazabilidad: es una afirmación sin caducidad.
- **Cómo se verifica.** Cada ADR tiene fecha y estado; y ninguna decisión «Aceptada» describe un
  sistema distinto del que está en el disco.
- **Qué desbloquea.** GR-05 y la credibilidad del resto de las decisiones.

---

#### GR-07 · No hay una sola prueba de accesibilidad ni declaración de conformidad, y la puerta que los ADR invocan no existe

- **Qué falta.** Cuatro verificaciones:
  1. **No existe ninguna prueba ejecutable de accesibilidad.** Los ficheros de prueba del sitio son
     **tres, todos dentro de `sitio/.scratch/`**: `pie.test.ts`, `normativa.test.ts` y
     `vitest.config.ts`. `.scratch/` está ignorado por git (`.gitignore:17`), así que no llegan al
     repositorio ni a CI.
  2. **No hay configuración de Playwright ni de vitest fuera de `.scratch/`.** Comprobado:
     `find sitio panel -name 'playwright.config.*' -o -name 'vitest.config.*'` sólo devuelve
     `sitio/.scratch/vitest.config.ts`. Y el panel no tiene **ninguna** prueba.
  3. **La integración continua no mide accesibilidad.** El trabajo `Sitio` ejecuta
     `npm run typecheck` y `npm run build` (`.github/workflows/ci.yml:149-154`); el trabajo `Panel`,
     `npm run build` (`:119-124`). Ni axe ni Playwright.
  4. **La puerta que los propios ADR invocan como evidencia no existe.** ADR-0015 funda cada
     desviación en `frontend/tests/conformidad-diseno.mjs` y afirma que contiene «más de cincuenta
     verificaciones» (`docs/adr/README.md:464-467`). **Ese fichero no existe, ni el directorio
     `frontend/`.** Las cinco desviaciones del punto 3 se apoyan en una comprobación inexistente.
- **Norma.** Res. 1519/2020 art. 3 y Anexo 1 §1.3 (WCAG 2.1 AA obligatorio desde el 1 de enero de
  2022; plazo **vencido** según el propio corpus, `README.md:115`) y Anexo 1 §2.2.3.8 («Revisión de
  la accesibilidad de un sitio web»). `docs/Sección 1 · Marco normativo.md:447` O-11 (Must) pide
  «pruebas automatizadas (axe-core / pa11y) en CI; auditoría manual semestral»;
  `docs/Sección 3 · Accesibilidad.md:673` exige la **Declaración de Conformidad** conforme al
  Anexo 1 num. 9.3, con fecha de próxima revisión a 12 meses (`:714`).
- **Dónde.** `sitio/.scratch/`; `.github/workflows/ci.yml:119-124, 149-154`;
  `docs/adr/README.md:464-467`; `sitio/package.json:24-33` y `panel/package.json:28-40` (declaran
  `@axe-core/playwright`, `@playwright/test`, `axe-core`, `vitest`, `@vue/test-utils` y `jsdom`
  **sin usar**).
- **Qué hay que hacer.** Escribir la suite —incluyendo el reflujo a 320 px, el área táctil a 360 px y
  el orden de tabulación, que son los puntos que `sitio.css:378-390` documenta haber medido **a
  mano**—, sacarla de `.scratch/`, añadirla como trabajo **bloqueante** en `ci.yml`, y sólo entonces
  redactar la declaración de conformidad. Mientras tanto, **`pages/accesibilidad.vue:10-17` hace bien
  en no declarar un nivel**: esa abstención es correcta y no debe cambiarse por comodidad.
- **Cómo se verifica.** CI falla si axe-core reporta una violación crítica en cualquiera de las 21
  rutas; existe un `playwright.config.ts` versionado; y la declaración de conformidad cita la
  herramienta, su versión y la fecha de la última ejecución.
- **Qué desbloquea.** Convierte en demostrable todo lo que el sitio ya hace bien y que hoy sólo está
  documentado en comentarios.

---

#### GR-08 · La línea base de requisitos no está versionada, está duplicada y no reconcilia

- **Qué falta.** Los tres hechos, medidos en §3:
  1. **Fuera del repositorio.** El corpus vive en `/var/www/portal-smr-main/sede-electronica-doc/`,
     no en el árbol versionado (§3.1).
  2. **Duplicado byte a byte.** `01-estructura-identidad/estructura-identidad.md` y
     `01-estructura-identidad/sede.md` son idénticos (md5 `2fdd6d35…`, 181 líneas cada uno, §3.3).
  3. **No reconcilia.** 215 identificadores citados sin enunciado; 28 RF y 28 RNF declarados en el
     total sin identificador; la fuente a la que el corpus remite (`/tmp/elicit/out/_req_bundle_*.md`)
     **no existe**; y las cifras totales del corpus discrepan entre sí en al menos cuatro sitios
     (`README.md:134-139` → 691; `_global/matriz-trazabilidad.md:415` → «~155 RF» y «~64 RNF»;
     `README.md:149` → «+37 RF-D, +18 RNF-D, +54 RN-D, +57 HU-D»; el recuento real → 38, 14, 49, 57).
- **Norma.** No es una norma externa. Es la condición de posibilidad de auditar: sin línea base
  versionada, ningún requisito puede darse por cumplido ni por ausente, y la cobertura no es
  calculable.
- **Dónde.** §3.1, §3.2, §3.3 de este documento, con las mediciones y sus comandos.
- **Qué hay que hacer.** Tres cosas independientes, de ahí que el hallazgo tenga tres criterios:
  (a) incorporar el corpus al repositorio (o declarar por escrito que la línea base es `docs/` y el
  corpus es material de trabajo); (b) eliminar uno de los dos ficheros del módulo 01 y dejar una nota;
  (c) cerrar la reconciliación: decidir qué se hace con los 215 huérfanos —o se enuncian, o se
  retiran del corpus— y corregir las cuatro cifras totales.
- **Cómo se verifica.** Un único total de requisitos en el corpus que coincida con el recuento de
  identificadores por `grep`; y cada identificador citado tiene al menos un enunciado.
- **Qué desbloquea.** BL-10 y la sección 6.

---

#### GR-09 · La fecha de publicación es opcional en la fuente y no consta en ninguna

- **Qué falta.** La obligación legal está en dos numerales y no tiene dato:
  - `docs/Sección 5 · Funcionalidad.md:227` FUN-019 (Must): «Toda la información o documentación
    debe llevar **fecha de publicación** y estar ordenada del más reciente al más antiguo».
  - `docs/transparencia.md:124-126`: cada documento debe llevar «**fecha de publicación** (nullable,
    y la interfaz dice «no consta» cuando falta)». Es decir: **el propio proyecto reconoce que el
    modelo nace con el campo opcional.**
  - `docs/transparencia.md:58-60` mide la ausencia: «**Cero de los 334 enlaces declara fecha de
    publicación.** No es una impresión: `grep «Fecha de publicación»` sobre los 1.730 ficheros del
    rastreo da **cero coincidencias**». Y `:62-64`: «mientras la Entidad no declare fechas, **la
    sección no puede cumplir el Anexo 2 §4.1.2.1** —orden de la más reciente a la más antigua—, y
    eso hay que declararlo en la página en vez de inventar una cronología».
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.1 (e): «Todo documento o información debe indicar la fecha
  de su publicación en página web»; §2.4.2 (e): la información debe publicarse «de manera inmediata o
  en tiempo real e **incluir fecha de publicación**»; Res. 2893/2020 Anexo 2 §4.1.2.1 (orden). En el
  corpus: RF-B3-087 (Must).
- **Dónde.** `docs/transparencia.md:58-64, 124-126`; ausencia del campo en todo el modelo construido.
- **Qué hay que hacer.** **No inventar una fecha.** El campo debe nacer **obligatorio** en el modelo
  del backend cuando se construya (BL-08), y la Entidad debe declarar la fecha de cada documento en
  la fuente. Mientras el dato no exista, decir «no consta» es la conducta correcta: es la virtud que
  la sección 9 protege.
- **Cómo se verifica.** El esquema del documento publicado declara `fecha_publicacion` como
  obligatorio, la API rechaza un documento sin fecha, y ningún registro de la base de datos la tiene
  nula.
- **Qué desbloquea.** El orden cronológico del Anexo 2 §4.1.2.1 y la ordenación de la búsqueda.

---

#### GR-10 · La ficha del trámite no publica los cuatro momentos GOV.CO

- **Qué falta.** `sitio/app/pages/tramites/[slug].vue` (950 líneas) describe el trámite, sus
  requisitos, su costo, sus puntos de atención, su normativa y cómo consultar el estado —nueve
  secciones— pero no lo articula en los cuatro momentos. `grep -n "etapa\|momento"` sobre el fichero
  devuelve **cero coincidencias**.
- **Norma.** RF-B3-126 (Must): «Registrar todos los trámites en SUIT (DAFP) y publicar ficha en
  GOV.CO con los **4 momentos** (acceso, solicitud, resolución, resultado)»; RF-B2-028/RF-B3-071
  (Must); RF-B3-127 (Must, estados estandarizados: solicitud registrada → recibida a satisfacción →
  en trámite → resuelta), que es consecuencia del Anexo 2 §2.4.3 (i) y del proceso de integración al
  Portal Único. El Kit lo pide con nombre propio: «**Línea de avance**: […] ubica al ciudadano,
  mostrándole exactamente en qué parte del proceso de ejecución de un trámite se encuentra» (Kit UI
  9.2 p. 26).
- **Dónde.** `sitio/app/pages/tramites/[slug].vue`; los datos vienen de `GET /api/v1/tramites`
  (`contract/openapi.yaml:129-288`).
- **Qué hay que hacer.** Decidir primero si los cuatro momentos **se derivan** de los datos que ya
  trae la ingesta o **exigen campos nuevos** en el contrato. **No inventarlos en la plantilla**: el
  propio código advierte que el catálogo no se completa con nada que la fuente no declare
  (`tramites/[slug].vue` y el modelo de procedencia).
- **Cómo se verifica.** La ficha de cualquiera de los 123 trámites muestra los cuatro momentos
  rotulados y, si el trámite tiene estado consultable, el momento actual marcado.
- **Qué desbloquea.** La integración al Portal Único (RF-B2-001), que verifica requisitos mínimos
  entre los que están las 4 etapas.

---

#### GR-11 · Cinco secciones publicadas en el menú y el sitemap son avisos de «en preparación»

- **Qué falta.** Cinco rutas son envoltorios de `SeccionEnPreparacion.vue` (61 líneas) y no publican
  contenido. Medido:

  | Ruta | Fichero | Líneas | En el menú | En el sitemap |
  |---|---|---|---|---|
  | `/transparencia` | `sitio/app/pages/transparencia.vue` | **17** | sí (`layouts/default.vue:48`) | sí, prioridad 0.9 (`sitemap.xml.ts:37`) |
  | `/servicios` | `sitio/app/pages/servicios.vue` | **17** | no | sí, 0.6 (`:47`) |
  | `/portales` | `sitio/app/pages/portales.vue` | **17** | no | sí, 0.6 (`:46`) |
  | `/noticias` | `sitio/app/pages/noticias.vue` | **17** | sí (`:93`) | sí, 0.7 (`:42`) |
  | `/seguimiento` | `sitio/app/pages/seguimiento.vue` | **19** | sí (`:58`) | sí, 0.7 (`:45`) |

- **Norma.** `/transparencia` es BL-09. `/servicios` es una de las tres secciones que FUN-013 obliga
  a mostrar en la portada (`docs/Sección 5 · Funcionalidad.md:216`, Must) y el Anexo 2 §2.4.3 (i)
  exige que dé acceso a trámites, OPA y consultas. `/seguimiento` es BL-03. `/noticias`:
  `docs/Sección 5 · Funcionalidad.md:244` FUN-026 (Must) y RF-B1-011 (Must). `/portales` responde al
  Decreto Ley 2106/2019 arts. 14 y 15 y a `docs/Sección 5 · Funcionalidad.md:245` FUN-027 (Must).
- **Dónde.** Los cinco ficheros de la tabla.
- **Qué hay que hacer.** Tres de las cinco ya tienen su hallazgo propio: `/seguimiento` es BL-03,
  `/transparencia` es BL-09 y `/servicios` es una decisión de producto (M-11). Las otras dos
  —`/portales` y `/noticias`— no tienen ninguno, y lo que este hallazgo añade, y es lo corregible
  hoy sin esperar a BL-08, es **el `sitemap.xml`**: su propio comentario dice «**Sólo se anuncian las
  páginas que existen de verdad.** Anunciar una que devuelve 404 es peor que no anunciarla: el
  buscador la indexa, el ciudadano llega desde ahí y encuentra un error» (`sitemap.xml.ts:15-19`).
  Anunciar cinco páginas que devuelven 200 con un aviso de «en preparación» produce exactamente el
  mismo efecto con peor diagnóstico.
- **Cómo se verifica.** Cada ruta del `sitemap.xml` publica contenido real; o deja de estar en el
  mapa hasta que lo publique. Una prueba puede recorrer las `loc` del mapa y comprobar que la página
  no es un `SeccionEnPreparacion`.
- **Qué desbloquea.** Nada; evita que los buscadores indexen la sede como vacía.

---

#### GR-12 · El sitio no aplica la tipografía ni el interlineado del Kit, y ahora está medido

- **Qué falta.** Con la tabla de tipografía del propio PDF del Kit leída de primera mano (Kit UI 9.2,
  «Tipografía», jerarquía de escritorio):

  | Estilo del Kit | Familia | Tamaño | Interlineado | Lo que aplica el sitio |
  |---|---|---|---|---|
  | Encabezado h1 | Nunito Sans Bold | 42 px | **50 px** | 42 px, `line-height: 1.2` (Bootstrap) = 50,4 px ✓ |
  | Encabezado h2 | Nunito Sans Bold | 34 px | **42 px** | 34 px, 1.2 = 40,8 px ✗ |
  | Encabezado h3 | Nunito Sans Bold | 26 px | **34 px** | 26 px, 1.2 = 31,2 px ✗ |
  | Encabezado h4 | Nunito Sans Bold | 22 px | **32 px** | 22 px, 1.2 = **26,4 px** ✗ (5,6 px de menos) |
  | Encabezado h5 | Nunito Sans Bold | 20 px | **26 px** | 20 px, 1.2 = 24 px ✗ |
  | Encabezado h6 | Nunito Sans Bold | 16 px | **22 px** | 16 px, 1.2 = 19,2 px ✗ |
  | **Body text 1** | **Verdana Regular** | **15 px** | **22 px** | **Nunito Sans Regular a 16 px**, `line-height: 1.5` = 24 px ✗ |
  | Caption | Verdana Regular | 12 px | 20 px | sin clase; no se aplica |

  Tres hechos verificables que producen ese resultado:
  1. El `all.css` del Kit **no aplica interlineado a los encabezados**: `h1`–`h6` declaran sólo
     `font-family` y `font-size` (`sitio/public/govco/all.css:73-101`), y de las 60 declaraciones
     `line-height` del fichero **ninguna** corresponde a `h1`–`h6`, `body` ni `p`.
  2. El Kit asigna **Verdana al texto de cuerpo** en su tabla, pero su CSS sólo la aplica a clases
     propias (`.text2-govco`, `.text3-govco`, `.link-tipografia-govco`, `.pie-pagina-govco p`…),
     mientras `html` recibe `font-family: 'Nunito_Sans-Regular'` (`all.css:67-71`).
  3. **La hoja propia del sitio tampoco lo corrige**: `sitio/app/assets/css/sitio.css` (465 líneas)
     tiene **cero** declaraciones `line-height` y **cero** `font-family`. En consecuencia gana el
     *reboot* de Bootstrap 5.0.2, que sí vendorizamos: `h1..h6 { line-height: 1.2 }` y
     `body { line-height: 1.5 }`.
- **Norma.** Kit UI 9.2, capítulo «Tipografía» (tabla de tamaño e interlineado, p. 12) —es el
  documento vigente de diseño—; RNF-B3-045 (Must: «100 % de componentes con paleta Cobalt `#0943B5`
  + tipografía Nunito Sans/Verdana»), RF-B1-098/RF-B3-045/046, y `docs/Sección 2 · Diseño.md:297`
  CAG-27 («Tipografía: nunca justificar texto; 45-75 caracteres por línea; unidades relativas»).
  Añádase que el **texto de cuerpo en Nunito Sans a 16 px** incumple además el tamaño que el Kit fija
  en 15 px, lo que altera el número de caracteres por línea que CAG-27 acota.
- **Dónde.** `sitio/app/assets/css/sitio.css` (0 `line-height`, 0 `font-family`);
  `sitio/public/govco/all.css:67-101`; `sitio/public/govco/bootstrap.min.css`.
- **Qué hay que hacer.** Añadir a `sitio.css` los valores de **interlineado** del Kit para `h1`–`h6`,
  `body` y `p`, y la familia **Verdana** al texto corrido. Es una corrección de **una sola hoja** que
  afecta a las 21 páginas a la vez.
- **Precaución, y es real.** `line-height` en unidades absolutas puede romper el criterio WCAG 2.1
  §1.4.12 («Espaciado de texto»), que exige que el contenido siga siendo legible con interlineado
  1,5×, espaciado entre párrafos 2×, entre letras 0,12× y entre palabras 0,16×. **Aplicar el Kit y
  verificar §1.4.12 con la suite de GR-07 antes y después.** Si el Kit y §1.4.12 entran en conflicto,
  §1.4.12 manda: es norma, el Kit es lineamiento.
- **Cómo se verifica.** El valor computado de `line-height` de un `h4` es 32 px y el de un párrafo es
  22 px a 15 px de cuerpo; y la prueba de §1.4.12 sigue en verde. Una medición en navegador lo
  resuelve en una línea —que es, exactamente, lo que no se ha hecho nunca (§1.3).
- **Qué desbloquea.** Da conformidad visual a las 21 páginas y cierra RNF-B3-045.

---

#### GR-13 · El enlace de la barra superior mide 36 px: por debajo de los 44 que el propio Kit fija, y la corrección escrita se aplicó a otro selector

- **Qué falta.** Este hallazgo es nuevo y está medido contra el PDF del Kit.
  - **Lo que el Kit exige**, literal, en su página 7 («Barra superior»): «Debe tener una altura de
    **56 píxeles**»; «Para garantizar el tamaño adecuado de la imagen para los usuarios de pantallas
    táctiles, **respeta un área activa mínima de 44 x 44 píxeles, incluyendo las medidas del logo**»;
    y «El logo debe manejar una relación de **24 x 136 píxeles**».
  - **Lo que el Kit hace**: `.barra-superior-govco a { content: url('assets/images/logo.svg');
    height: calc(1.5rem * 1.5); }` → **36 px de alto** (`sitio/public/govco/all.css:702-705`). El Kit
    incumple su propia especificación.
  - **Lo que el sitio hace**: `BarraSuperior.vue` (24 líneas) reproduce el marcado canónico —el
    ejemplo del Kit también usa un `<a>` vacío, `examples/transversal/barra-superior.html:24-27`, y
    el logo lo pinta el CSS— y por tanto hereda los 36 px. **No hay ninguna regla que lo corrija**:
    `grep -rn "barra-superior-govco" sitio/app/ | grep -i "min-\|height"` → cero coincidencias.
  - **La trampa**: `sitio.css:430-434` sí aplica `min-height: 2.75rem` (44 px)… pero a
    `.enlace-logotipo-govco`, que es el enlace del **logotipo de la cabecera**
    (`CabeceraGovco.vue:79, 152`), **no** el de la barra superior. Y el comentario que precede a esa
    regla (`sitio.css:419-424`) declara el problema en primera persona: «La medición a 360 px dejó
    tres que no cubría la regla anterior: **el logotipo de la barra superior (71 × 40)** […] El Kit
    cita expresamente el logotipo de la barra superior en su página 7 al fijar los 44 px, así que su
    propio componente lo incumple». Es decir: **el defecto está identificado por escrito en la hoja
    que debía corregirlo, y la regla que lo sigue apunta a otro elemento.**
- **Norma.** Kit UI 9.2 p. 7 (44×44 px en la barra superior, «incluyendo las medidas del logo»);
  Kit UI 9.2 p. 30 (CAG-23); RNF-B3-005 («Elementos interactivos ≥44×44 px en móvil») y RF-B1-001 /
  RF-B3-053 (Must). En `docs/`: `Sección 2 · Diseño.md:275` CAG-05 y `:293` CAG-23.
- **Dónde.** `sitio/public/govco/all.css:702-705`; `sitio/app/components/govco/BarraSuperior.vue:16-23`;
  `sitio/app/assets/css/sitio.css:419-434`.
- **Qué hay que hacer.** Añadir a `sitio.css` una regla para `.barra-superior-govco a` con la altura
  mínima de 44 px (y ancho mínimo, que la especificación también fija). Está en la hoja propia y no
  en el componente porque el defecto es del Kit y afecta a **todas** las páginas: es el mismo criterio
  con el que se corrigió el buscador y el pie.
- **Cómo se verifica.** A 360 px de ancho, la caja del enlace de la barra superior mide ≥44×44 px. Es
  una aserción de tres líneas en la suite de GR-07, y hasta hoy no existía.
- **Qué desbloquea.** Cierra RNF-B3-005 en la pieza que está presente en el 100 % de las páginas.

---

#### GR-14 · Nueve componentes del Kit exigidos para sedes electrónicas no existen

- **Qué falta.** El catálogo del Kit UI 9.2 tiene **32 componentes** (9 transversales, 19 generales, 4
  de formulario; leído del PDF, «Lista de contenido»). El sitio implementa **11** componentes en
  `sitio/app/components/govco/`. De los que el Kit marca como requeridos y el sitio no tiene:

  | Componente del Kit | Página del Kit | Marcado requerido en | Estado en el sitio |
  |---|---|---|---|
  | Acordeón | 15 | **Sedes electrónicas** | ausente (`grep -rni "accordion\|acordeon" sitio/app/` → 0) |
  | Alerta modal | 16 | Sedes electrónicas · Trámites y servicios | ausente (0 `container-modal-govco`) |
  | Alerta notificación (toast) | 17 | — | ausente (0 `container-toast-govco`) |
  | Descripción emergente | 23 | Sedes electrónicas | ausente (sólo `tooltip-text-govco` en la galería) |
  | Etiquetas | 24 | **Sedes electrónicas** | ausente |
  | Indicador de carga | 25 | Sedes electrónicas · Trámites y servicios | ausente (0 `spinner`) |
  | Cuadrícula | — | Todas | aportada por Bootstrap 5.0.2 vendorizado (`nuxt.config.ts:61`) |
  | Pestañas | 31 | Sedes electrónicas · Trámites y servicios | sustituidas por botones con `aria-pressed` en `tramites/index.vue` (desviación declarada) |
  | Paginación | 30 | Sedes electrónicas · Trámites y servicios | existe **incrustada** en `tramites/index.vue:781-1014`, no como componente |

  **Rectificación al informe anterior:** la edición previa enumeraba nueve componentes ausentes
  tomando como referencia los que **no tienen carpeta en `vendor-src/layout-govco-v5/src/`** (que
  son efectivamente nueve: Cuadrícula, Área de servicio, Descripción emergente, Etiquetas, Indicador
  de carga, Línea de avance, Módulo de inicio de sesión, Paginación y Pestañas). Este informe usa un
  criterio distinto y más exigible: **los que el Kit marca como requeridos en «Sedes electrónicas» y
  el sitio no implementa**. Con ese criterio, **Acordeón y Alerta modal entran** —y son los dos que la
  desviación declarada de ADR-0015 §3 da por no aplicables, lo que convierte el punto en un
  incumplimiento y no en una desviación.
- **Norma.** Kit UI 9.2, §«Requerido, mínimamente, en» de cada componente; RN-B2-029 («Kit UI GOV.CO
  de cumplimiento obligatorio en trámites integrados», corpus módulo 01) y `docs/Sección 5 ·
  Funcionalidad.md:290` FUN-047 (Must). En el corpus: RF-B2-084/RF-B2-085 (Must), RF-B3-062
  (acordeón, Must), RF-B3-063 (alerta modal, Must), RF-B3-064 (toast, Must), RF-B3-070 (indicador de
  carga, Must), RF-B3-075 (paginación, Must). En `docs/`: CAG-21, CAG-22, CAG-25, CAG-29.
- **Dónde.** `sitio/app/components/govco/` (11 ficheros); `docs/adr/README.md:412-424`.
- **Qué hay que hacer.** Priorizar por uso real: **Acordeón y Alerta modal** primero —el modal es
  requisito de GR-15 y el acordeón lo exige el Kit para sedes—; luego **Indicador de carga** (bloquea
  cualquier pantalla con espera) y **Etiquetas** (estados). **Paginación** debe extraerse del catálogo
  a un componente reutilizable **sin esperar**: hoy cualquier listado futuro la reescribirá.
- **Cómo se verifica.** Cada componente que el Kit marca para sedes electrónicas tiene su
  implementación y pasa una prueba que afirma su contrato (estados, ARIA, teclado). La desviación
  declarada de CAG-21 y CAG-25 desaparece de ADR-0015.
- **Qué desbloquea.** GR-15 y GR-17.

---

#### GR-15 · No hay aviso de salida a sitio externo, y el ADR que lo declara inaplicable contradice la norma

- **Qué falta.** Los enlaces que salen de la sede navegan directamente. En la ficha del trámite hay
  al menos ocho con clase `enlace-externo` (`tramites/[slug].vue:414, 458, 526, 571, 696, 702, 734,
  783`) y, como mucho, `rel="noopener"`. No hay modal de aviso, ni nombre del destino, ni
  confirmación, ni lista blanca administrable. `grep -rni "sitio externo" sitio/app/` → **cero
  coincidencias en código**.
- **Norma.** RF-B1-071/RF-B2-040/RF-B3-094 (Must/Should), con sus derivados RF-01-D03 y RN-01-D03,
  que exigen además que la lista blanca sea **administrable** para no disparar el aviso en
  redirecciones a GOV.CO o al Articulador. `docs/Sección 5 · Funcionalidad.md:197` FUN-003
  (recomendación de `target="_blank"` con `rel="noopener noreferrer"`).
- **Dónde.** `sitio/app/pages/tramites/[slug].vue` (8 enlaces); ausencia en
  `sitio/app/components/`.
- **La contradicción que hay que resolver.** ADR-0015 §3 (`docs/adr/README.md:417-424`) declara:
  «**CAG-19, CAG-21, CAG-22, CAG-24 y CAG-25 — no aplican.** La sede no usa campos de calendario,
  **no abre modales**, no emite notificaciones *toast*, no publica tablas ni usa acordeones.» El
  aviso de salida a sitio externo **es un modal** y es un requisito **Must** del corpus. Por tanto
  CAG-21 **sí aplica**, y la desviación declarada no es una decisión de diseño: es un requisito sin
  construir presentado como decisión. La misma objeción alcanza a CAG-25 (acordeón, requerido para
  sedes electrónicas) y a CAG-24 (tablas: `pqrsd.vue:222` publica una tabla del Kit).
- **Qué hay que hacer.** Construir el componente —que resuelve a la vez **GR-14**, porque el modal es
  el componente #10 del Kit— y la lista blanca. La lista blanca necesita persistencia, así que
  depende de BL-08.
- **Cómo se verifica.** Pulsar un enlace a un dominio no listado muestra el aviso con el nombre del
  destino y no navega hasta confirmar; pulsar uno a `gov.co` navega sin aviso. Dos pruebas.
- **Qué desbloquea.** Cierra CAG-21 y aporta el primer componente modal del Kit al sitio.

---

#### GR-16 · Las 123 fichas de trámite no están en el `sitemap.xml`

- **Qué falta.** El mapa anuncia `/tramites` (`server/routes/sitemap.xml.ts:38`) pero **ninguna** de
  sus fichas: `grep -n "tramites/" sitio/server/routes/sitemap.xml.ts` no devuelve coincidencias. Las
  123 fichas existen, responden y están enlazadas internamente, pero son invisibles para los motores
  de búsqueda. Tampoco están las cinco políticas ni las seis subcategorías de Participa… **estas sí**
  (`:93-105`), lo que confirma que el mecanismo existe y sólo falta aplicarlo a los trámites.
- **Norma.** Res. 1519/2020 Anexo 1 §4.3.1 (b): el mapa del sitio debe estar en formato XML «para que
  sea visible a los motores de búsqueda». En `docs/`: `Sección 5 · Funcionalidad.md:226` RF-B1-088 /
  RF-B2-082; en el corpus: RF-B1-010/RF-B2-037 (Must) y HU-B1-001 («encontrar la sede como primer
  resultado»).
- **Dónde.** `sitio/server/routes/sitemap.xml.ts:43-58` y `:93-105`.
- **Qué hay que hacer.** Añadir las fichas. O se enumeran al construir el mapa —una llamada paginada
  a `GET /api/v1/tramites`, que ya soporta `page` y `per_page`—, o se declara un índice de mapas. **Ojo
  con el comentario del propio fichero (`:15-19`)**: sólo se anuncian direcciones que existen de
  verdad; si la API no responde durante la construcción del mapa, la entrada debe omitirse, no
  inventarse.
- **Cómo se verifica.** `/sitemap.xml` contiene las 123 `loc` de ficha y cada una responde 200. La
  prueba puede recorrer el mapa y comprobar el código de estado de cada entrada.
- **Qué desbloquea.** HU-B1-001 y el requisito de SEO del módulo 08.

---

#### GR-17 · Falta el Área de servicio en la ficha del trámite

- **Qué falta.** El Kit UI 9.2, **página 18**, define el **Área de servicio** —«Estos módulos se han
  diseñado para brindarle ayuda al ciudadano y recibir la retroalimentación sobre su experiencia con
  los trámites vinculados al Portal GOV.CO»— con sus dos módulos «¿Cómo fue tu experiencia durante el
  proceso?» (FÁCIL / DIFÍCIL) y «¿Tienes dudas sobre este trámite o consulta?» (teléfono, línea
  gratuita, correo y «Te explicamos con tutoriales»). La ficha del trámite tiene nueve secciones y
  ninguna es ésa (`tramites/[slug].vue`: `Ficha del trámite`, `Requisitos`, `Costo y pago`, `Dónde se
  atiende`, `A quién va dirigido`, `Qué obtiene`, `Normativa`, `Cómo consultar el estado`,
  `Procedencia de los datos`).
- **Sobre su alcance, con honestidad de método.** La extracción de texto del PDF sitúa el Área de
  servicio en «**Trámites y servicios**» y **no** en «Sedes electrónicas» (a diferencia de Acordeón,
  Etiquetas o Indicador de carga, donde sí aparece «Sedes electrónicas»). Las casillas del PDF son
  gráficas y el orden extraído no garantiza correspondencia uno a uno con la casilla marcada
  (**§1.4, punto 3**). Lo que **sí** es firme: el Kit describe el componente como destinado a los trámites
  vinculados al Portal GOV.CO, y el corpus lo exige con independencia del Kit.
- **Norma.** Kit UI 9.2 p. 18; Res. 1519/2020 Anexo 2 §2.4.3 (i); en el corpus: RF-B3-065 (Should) y
  RF-B2-033/034 (Must: «**Retroalimentación obligatoria** “¿Cómo fue tu experiencia?” (FÁCIL/DIFÍCIL +
  texto) **al inicio y al final**»); RF-B1-094 (Must: encuesta de ≤3 preguntas y 1-5 estrellas al
  finalizar). En `docs/`: `Sección 2 · Diseño.md:322` («Atención al ciudadano | Área de servicio ·
  Acordeón · Tarjeta de información · Buscador · Botones | Módulos *¿Cómo fue tu experiencia?* +
  *¿Tienes dudas?* (Kit UI v9.2 pág. 18)»).
- **Dónde.** `sitio/app/pages/tramites/[slug].vue` (950 líneas); ausencia del componente.
- **Qué hay que hacer.** Añadir el bloque al final de la ficha. La valoración de la experiencia
  necesita endpoint (depende de BL-08); **el bloque de dudas puede resolverse primero** con los
  canales de atención ya publicados y verificados en `atencion.vue`.
- **Cómo se verifica.** La ficha muestra los dos módulos; el de dudas lleva al conmutador y al correo
  reales; el de valoración registra la respuesta (cuando exista el endpoint).
- **Qué desbloquea.** RF-B1-094 (la encuesta de experiencia) y el ITA en la materia de trámites.

---

#### GR-18 · Los canales de atención no permiten pedir cita

- **Qué falta.** `sitio/app/pages/atencion.vue` (209 líneas) publica dirección, código postal, horario,
  conmutador, línea gratuita y correos —verificados contra la fuente y sin inventar— pero **no ofrece
  agendamiento**: `grep -ni "cita\|agend"` sobre el fichero devuelve **cero coincidencias**.
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.3 (ii): «Canales de atención **y pida una cita**. Los sujetos
  obligados deberán incluir en su respectiva sede electrónica la información y contenidos relacionados
  con los canales habilitados para la atención a la ciudadanía»; `docs/Sección 1 · Marco
  normativo.md:439` O-03 («Bloque fijo en homepage con PQRS, **agendamiento de citas**, horarios y
  canales verificados»); en el corpus: RF-B1-030/RF-B3-096 (Must), RF-06-D01 a RF-06-D04 (Must),
  RN-06-D01 a RN-06-D03. `_global/matriz-trazabilidad.md:435` registra la decisión del 2026-06-05:
  «**agenda propia en la sede**».
- **Dónde.** `sitio/app/pages/atencion.vue`; ausencia de operación en `contract/openapi.yaml`;
  `panel/src/router/index.ts:40` declara el módulo `citas`… como stub.
- **Qué hay que hacer.** Contrato, endpoint y pantalla. La decisión ya está tomada (agenda propia), de
  modo que no hay que reevaluarla. Es una de las funciones con mayor efecto visible y **no depende de
  BL-08**.
- **Cómo se verifica.** Reservar una cita devuelve un código de confirmación; el mismo cupo no se
  asigna dos veces bajo concurrencia (RN-06-D01); cancelar libera el cupo (RN-06-D02); y sigue
  existiendo alternativa presencial (RN-06-D03, invariante).
- **Qué desbloquea.** Cierra el módulo 06 y da al panel su primer módulo con datos reales.

---

#### GR-19 · Seis paquetes de Spatie están declarados y no los usa ningún fichero

- **Qué falta.** `backend/composer.json:22-27` declara seis paquetes. Comprobado con `grep -rl` sobre
  `backend/app`, `backend/routes`, `backend/config` y `backend/database`: **los seis aparecen en cero
  ficheros.**

  | Paquete | Para qué lo decidió ADR-0009 | Uso real |
  |---|---|---|
  | `spatie/laravel-permission` | «roles y permisos (Spatie)» | **0 ficheros**; sin `config/permission.php` |
  | `spatie/laravel-activitylog` | «registro de auditoría» | **0 ficheros**; sin `config/activitylog.php` |
  | `spatie/laravel-medialibrary` | «biblioteca de medios» | **0 ficheros** |
  | `spatie/laravel-honeypot` | — | **0 ficheros** (es **BL-04**) |
  | `spatie/laravel-sitemap` | — | **0 ficheros** |
  | `spatie/laravel-backup` | DRP/BCP (SEG-014) | **0 ficheros**; sin `config/backup.php` |

  Y ninguna de las seis publicó su fichero de configuración: `backend/config/` contiene once ficheros
  (`app`, `auth`, `cache`, `database`, `filesystems`, `logging`, `mail`, `queue`, `sanctum`,
  `services`, `session`) y **ninguno** de los seis paquetes.
- **Norma.** No es un incumplimiento por sí mismo; lo es por lo que **representa**: cinco capacidades
  exigidas por norma —RBAC (Anexo 3 §2; RNF-B2-010), auditoría (SEG-013; RNF-B3-022), antispam (BL-04),
  copias de seguridad con prueba de restauración (`docs/Sección 4 · Seguridad.md:188` SEG-014:
  «Copias de seguridad cifradas con **prueba de restauración trimestral** (RPO ≤ 24 h, RTO ≤ 4 h)»)—
  figuran **como instaladas** y no existen en el sistema. Igual que el directorio `Policies/` vacío en
  la edición anterior: **un `composer.json` con los paquetes dentro se lee como trabajo hecho.**
- **Dónde.** `backend/composer.json:22-27`; `backend/config/`; `backend/app/Policies/` (sólo
  `.gitkeep`); `backend/app/Models/User.php`.
- **Qué hay que hacer.** Decidir por paquete: se cablea o se retira. Cuatro de los seis tienen un
  destino claro y ya decidido —honeypot → BL-04; permission → GR-01/GR-19; activitylog → la auditoría
  del módulo 12; backup → SEG-014—. Los otros dos (`sitemap`, `medialibrary`) no tienen requisito que
  los reclame hoy: **retírense** y vuelvan cuando el CMS los pida. Cada dependencia sin usar es
  superficie de suministro sin contrapartida.
- **Cómo se verifica.** Para cada paquete que se conserve: existe su configuración publicada, hay al
  menos un punto de uso, y una prueba afirma el comportamiento (p. ej. un rol sin permiso recibe 403;
  un evento de auditoría queda registrado; un envío con el campo trampa se descarta).
- **Qué desbloquea.** BL-04 y la capa de autorización que BL-08 necesita.

---

### 5.3 Medios

---

#### M-01 · El carrusel arranca solo

- **Qué falta.** El componente arranca en reproducción automática: `autoplay: true`
  (`sitio/app/components/govco/CarruselGovco.vue:98`) y
  `reproduciendo.value = props.autoplay && !consultaMovimiento.matches` (`:200`), con intervalo de
  6 000 ms (`:99`). Los tres controles obligatorios del Kit **sí están** —indicadores de posición,
  flechas y reproducción/pausa— y el botón cambia de estado y de etiqueta accesible (`:271-275`).
- **Norma.** WCAG 2.1 §2.2.2 exige **un mecanismo** para detenerlo, y existe. Lo que incumple es el
  requisito del proyecto: RF-B1-042/RF-B3-069 (Must) pide «**pausa por defecto**». La resolución de
  `prefers-reduced-motion` (`:195-200`) es buena práctica y **no es** pausa por defecto.
- **Dónde.** `sitio/app/components/govco/CarruselGovco.vue:98-99` y `:200`.
- **Qué hay que hacer.** Cambiar el valor por defecto a `autoplay: false`. Es un cambio de una línea
  que altera el comportamiento de la portada: conviene decidirlo, no aplicarlo por sorpresa.
- **Cómo se verifica.** Al cargar la portada el botón muestra «Reproducir» y las diapositivas no
  cambian solas en 12 segundos.
- **Qué desbloquea.** Nada.

---

#### M-02 · No hay encuesta de usabilidad ni criterio cuantitativo de «cumple»

- **Qué falta.** `grep -rni "encuesta" sitio/app/ backend/app` devuelve dos coincidencias y las dos
  son **documentos de la Entidad listados en un inventario**, no un mecanismo del sitio. No hay
  cuestionario SUS, ni invitación, ni registro de resultados.
- **Norma.** `docs/Sección 5 · Funcionalidad.md:295` FUN-052 (Must: «La Sede debe incluir una
  encuesta de usabilidad **visible para el ciudadano**») y `:417` («Visible y operativa en ≥90 % de
  las páginas»); en el corpus: RF-B1-087/RF-B2-083 (Should), RNF-B1-030 (SUS ≥68) y
  `_global/matriz-trazabilidad.md:436`, que registra el umbral decidido el 2026-06-05: «**≥90 % tasa
  de éxito** + SUS ≥68».
- **Dónde.** Ausencia en `sitio/app/`.
- **Qué hay que hacer.** Puede construirse sin BL-08 —una encuesta con destino en el backend no
  necesita CMS— pero **su utilidad depende de que haya servicio que evaluar**: hoy el ciudadano no
  puede radicar nada. **Recomiendo no construirla todavía** y planificarla para después de BL-01.
- **Cómo se verifica.** La encuesta aparece en ≥90 % de las páginas, calcula el puntaje SUS y lo
  almacena con fecha y versión del sitio.
- **Qué desbloquea.** Nada.

---

#### M-03 · No hay federación de datos abiertos a `datos.gov.co`

- **Qué falta.** No hay catálogo propio, ni federación, ni metadatos, ni licencia declarada.
  `grep -rn "datos.gov.co" sitio/app/` devuelve **una sola coincidencia**, y es la URL de un
  documento de la Entidad, no un mecanismo de federación.
- **Norma.** Res. 1519/2020 art. 7 y Anexo 4: los sujetos obligados deben publicar sus datos abiertos
  y **federarlos** al portal nacional. `docs/Sección 1 · Marco normativo.md:508-510` lo dice con
  precisión: «El art. 14 no obliga a publicar datos abiertos, pero la Res. 1519/2020 (Anexo 4) sí.
  **Decisión de proyecto:** federar al portal datos.gov.co desde el inicio». En el corpus: RF-B1-019,
  RF-B1-020, RF-B1-090, RF-B1-091, RNF-B3-039 (≥90 % en formatos abiertos), RN-B1-019
  («datos.gov.co **no** es archivo»).
- **Dónde.** Ausencia; `docs/transparencia.md:94-109` documenta además la trampa encontrada: los
  volcados `ds_*.json` del rastreo contienen «**24 de Barranquilla**, 7 de Santa Rosa de Cabal, **0 de
  Santa Marta**» y usarlos «publicaría datos de otro municipio como si fueran del Distrito».
- **Qué hay que hacer.** Es trabajo de datos, no de interfaz: publicar los conjuntos en
  `datos.gov.co` y **enlazarlos**. La interfaz debe mostrar el catálogo federado, no alojarlo
  (`docs/transparencia.md:107-109`: «federar, no alojar copias»).
- **Cómo se verifica.** La categoría de datos abiertos muestra los conjuntos propios consultados a la
  API de `datos.gov.co` filtrando por entidad propietaria, y ninguno es un volcado ajeno.
- **Qué desbloquea.** El módulo 11.

---

#### M-04 · La sección de normativa no publica el enlace al SUIN

- **Qué falta.** `sitio/app/pages/normativa.vue` (734 líneas) construye correctamente la estructura
  que el Anexo 2.1 exige para cada norma —tipo, fecha de expedición, fecha de publicación, epígrafe,
  enlace, vigencia, proyectos con fecha máxima de comentarios, en `:126-179`— y declara cero normas,
  a propósito y con razón. Pero el catálogo de apartados que publica sólo tiene cuatro entradas
  (`:88-99`), la de «Vínculo al Diario o Gaceta oficial» queda diferida (`:95-99`), y
  `grep -ni "suin"` sobre el fichero devuelve **cero coincidencias**.
- **Norma.** RF-B1-014 (Must): «Enlace funcional al **SUIN** (suin-juriscol.ramajudicial.gov.co) y al
  Diario/Gaceta Oficial; publicar **Agenda Regulatoria**»; Res. 1519/2020 Anexo 2 §2.4.1 (g);
  `docs/Sección 5 · Funcionalidad.md:227` FUN-019.
- **Dónde.** `sitio/app/pages/normativa.vue:88-99`.
- **Qué hay que hacer.** Añadir el enlace al SUIN, que es una URL pública y estable. El de la Gaceta
  Distrital no puede añadirse hasta que la Entidad confirme la URL, y la razón que el propio código da
  para diferirlo es correcta: **apuntar a una dirección sin comprobarla sería inventar un enlace.**
- **Cómo se verifica.** La página de normativa contiene un enlace a `suin-juriscol.ramajudicial.gov.co`
  que responde 200.
- **Qué desbloquea.** RF-B1-014 y la Agenda Regulatoria (que comparte el enlace con el módulo 05).

---

#### M-05 · Los datos de la Entidad están duplicados en tres ficheros

- **Qué falta.** El nombre, la dirección, el horario, el conmutador, la línea gratuita y los correos
  viven en `sitio/app/components/govco/PiePaginaGovco.vue:150-177` y se repiten en
  `sitio/app/pages/atencion.vue:35-52` y en `sitio/app/pages/accesibilidad.vue:26-37`. Cambiar un
  teléfono exige tocar tres ficheros.
- **Norma.** Res. 1519/2020 Anexo 2 §2.4.1 (f): fuente única de la información pública, sin
  duplicidad; y `docs/Sección 5 · Funcionalidad.md:228` FUN-020 («Se debe evitar la **duplicidad** de
  información entre secciones»).
- **Dónde.** Los tres ficheros citados.
- **Nota.** **No es un descuido oculto: está declarado por escrito.** `atencion.vue:11-15` —«*Fuente
  única, hoy duplicada a propósito.* […] Queda anotado para que no se pierda»— y
  `accesibilidad.vue:20-23`.
- **Qué hay que hacer.** Consumir `GET /api/v1/entidad`, que **ya está declarado en el contrato**
  (`contract/openapi.yaml:54-128`) con el esquema completo. Depende de GR-20.
- **Cómo se verifica.** Cambiar el teléfono en la base de datos cambia el que se ve en el pie, en
  atención y en accesibilidad, sin tocar código.
- **Qué desbloquea.** GR-20.

---

#### M-06 · El catálogo de tipos de PQRSD está duplicado

- **Qué falta.** Los seis tipos con sus slugs viven en `sitio/app/pages/pqrsd.vue:51-56` y se repiten
  en `sitio/app/pages/realizar-una-peticion.vue:35-38`. **Ambos ficheros declaran la deuda** y
  explican por qué no se resolvió.
- **Norma.** Misma fuente única (Anexo 2 §2.4.1 (f)). Y hay un riesgo funcional añadido: si las dos
  listas divergen, un enlace de PQRSD abriría el formulario sin tipo preseleccionado.
- **Dónde.** `pqrsd.vue:51-56`; `realizar-una-peticion.vue:35-38`.
- **Qué hay que hacer.** Extraer a `sitio/app/types/` o a un composable. **Es el trabajo más barato de
  toda esta lista** y el que más probablemente se pague solo.
- **Cómo se verifica.** Existe una sola declaración de los seis tipos; una prueba afirma que el
  formulario preselecciona el tipo que llega por la URL para los seis slugs.
- **Qué desbloquea.** Nada.

---

#### M-07 · Dependencias declaradas y no usadas en el sitio y en el panel

- **Qué falta.** Dos listas, ambas medidas.
  - **Sitio** (`sitio/package.json:13-22`): `axios`, `zod`, `pinia` y `@tanstack/vue-query` —
    **los cuatro en cero ficheros**. Las páginas de trámites usan `useRequestFetch` de Nuxt. Y en
    `devDependencies` (`:24-33`), `@playwright/test`, `@axe-core/playwright`, `axe-core`, `vitest`,
    `@vue/test-utils` y `jsdom` sin configuración ni pruebas (GR-07).
  - **Panel** (`panel/package.json:11-26`): `zod`, `@tanstack/vue-query` y `vue3-toastify` — **los
    tres en cero ficheros**. Y `pinia` se importa en un único fichero, `main.ts:15,27`, sin una sola
    tienda (GR-04). En `devDependencies` (`:28-40`), los mismos seis de pruebas, tampoco usados.
  - **Y en el backend**: los seis paquetes de Spatie (GR-19).
- **Norma.** Criterio de proyecto, no normativo: `README.md:119-127` y la regla sobre dependencias de
  terceros. Hay un trabajo de CI dedicado a revisar los ficheros de bloqueo
  (`.github/workflows/ci.yml:191-199`).
- **Dónde.** `sitio/package.json`; `panel/package.json`; `backend/composer.json`.
- **Qué hay que hacer.** Decidir en cada caso: se usa o se retira. Las de pruebas se usarán cuando
  exista GR-07; las otras doce no tienen destino declarado. **Doce dependencias de ejecución sin usar
  son superficie de suministro sin contrapartida**, y en una entidad pública eso es una decisión, no
  un descuido.
- **Cómo se verifica.** Cada dependencia declarada tiene al menos un punto de importación, o no está.
  Una comprobación puede automatizarlo en el trabajo de dependencias que ya existe.
- **Qué desbloquea.** Nada.

---

#### M-08 · La galería de aplicaciones no está montada en ninguna parte, y el ADR afirma que sí

- **Qué falta.** `sitio/app/components/govco/GaleriaAplicacionesGovco.vue` tiene **354 líneas**, está
  bien construido —conserva todas las clases del Kit, y **amplía** su contrato con `Escape` con
  devolución de foco, flechas en rejilla de tres columnas y `aria-expanded`/`aria-controls`, que el JS
  del Kit (22 líneas) no tenía— y **no lo usa nadie**: `grep -rn "GaleriaAplicacionesGovco"
  sitio/app/` devuelve **cero coincidencias**.
- **Norma.** Kit UI 9.2 p. 21 (CAG-31: «Galería de aplicaciones: recorrido por teclado
  izquierda→derecha, arriba→abajo; abre con `Enter` y cierra con `Esc`»); RF-B3-068 (Should).
  Y la afirmación que hay que corregir: ADR-0015 §1 concluye que la barra superior «conserva el logo
  GOV.CO **y la galería de aplicaciones**» (`docs/adr/README.md:404-405`). **No la conserva: no la
  monta.** La desviación está justificada con un hecho falso.
- **Dónde.** `sitio/app/components/govco/GaleriaAplicacionesGovco.vue`; ausencia de uso en
  `sitio/app/layouts/default.vue` y `BarraSuperior.vue`; `docs/adr/README.md:404-405`.
- **Qué hay que hacer.** Decidir: montarla en la barra superior —su sitio natural, y donde el Kit la
  describe— o retirarla. **Tenerla construida y no usada es lo peor de las dos opciones**: se paga su
  mantenimiento y no se recibe su beneficio. Nótese además que sus contenidos por defecto son
  marcadores («Aplicación de ejemplo 1/2/3», `:78-82`), lo cual está bien para un componente sin
  datos y sería un defecto si se montara sin ellos.
- **Cómo se verifica.** El componente aparece montado en la disposición con datos reales y pasa la
  prueba de teclado de CAG-31; o el fichero ya no está.
- **Qué desbloquea.** Nada.

---

#### M-09 · Hay dos implementaciones independientes de los controles de accesibilidad, con mecanismos y claves distintos

- **Qué falta.** El sitio y el panel resuelven el mismo requisito —barra de accesibilidad con tamaño
  de letra y contraste— con dos implementaciones que no comparten nada:

  | | Sitio | Panel |
  |---|---|---|
  | Fichero | `sitio/app/composables/useAccesibilidad.ts` (81 líneas) + `components/govco/BarraAccesibilidad.vue` (90) | `panel/src/components/base/AccessibilityBar.vue` (223 líneas) |
  | Escalado | `zoom` sobre la raíz, ±8 % por paso, 5 pasos | `zoom` sobre la raíz, ±10 %, entre 0,8 y 1,5 |
  | Contraste | Clase `contraste-govco` con reglas propias en `sitio.css:24-81` | Clase `a11y-contrast` con **42 reglas** de sobrescritura de utilidades Tailwind en `panel/src/assets/styles/main.css:37-71`, más `filter: contrast(1.15)` |
  | Persistencia | `useState('sede.accesibilidad')` | `localStorage['smr_panel_a11y']` |
  | Extras | — | Modo oscuro y espaciado de texto (que el corpus **no** exige) |

- **Norma.** Kit UI 9.2 p. 6 (CAG-07: «Barra de accesibilidad: incluir Aumentar letra · Reducir letra
  · Contraste; oculta entre 768-992 px»); RF-B1-044/RF-B2-043/RF-B3-054 (Must); RNF-B1-019 (modo de
  alto contraste que altera todos los colores satisfactoriamente).
- **Dónde.** Los cuatro ficheros de la tabla.
- **Dos objeciones concretas, y la segunda es funcional.**
  1. **El contraste del panel es frágil por construcción.** Funciona sobrescribiendo clases de
     Tailwind **enumeradas a mano** (`bg-white`, `bg-slate-50`, `text-slate-900`, `text-slate-400`…).
     Cualquier utilidad nueva que se use en una vista y no esté en la lista **no se convertirá**, y el
     modo de alto contraste quedará a medias sin que nada avise. El sitio resolvió esto con selectores
     universales (`sitio.css:31-37`), que no dependen de enumerar nada.
  2. **`filter: contrast(1.15)` sobre `html`** (`main.css:51`) crea un bloque contenedor y afecta a
     todo el documento, incluidos los elementos con `position: fixed` —entre ellos la propia barra,
     que es `fixed` (`AccessibilityBar.vue:69`). Es el tipo de interacción que sólo se descubre
     midiendo en navegador, y no se ha medido (§1.3).
- **Qué hay que hacer.** Extraer el comportamiento a una pieza compartida, o —si se decide que el
  panel es un producto distinto y no está sujeto al Kit (decisión legítima, pero **no tomada**)—
  escribirlo en un ADR y sustituir el contraste enumerado por selectores universales.
- **Cómo se verifica.** Con el modo de alto contraste activo, una vista nueva con utilidades Tailwind
  no enumeradas sigue siendo legible; y el panel mantiene su disposición sin desplazamientos
  laterales.
- **Qué desbloquea.** M-15 y la coherencia de la identidad.

---

#### M-10 · La página 404 ofrece una sola vía de navegación propia

- **Qué falta.** `sitio/app/error.vue` (59 líneas) explica el error, muestra el código (`:52`) y
  ofrece **un** enlace: «Volver a la portada» (`:55`).
- **Norma.** RF-B1-007/RF-B2-039/RF-B3-142 (Must): «≥3 opciones de navegación/contenido alternativo
  (menú, buscador, secciones populares)».
- **Dónde.** `sitio/app/error.vue:54-56`.
- **`[DEDUCCIÓN]`** La página se monta dentro de `NuxtLayout` (`:37`), así que **hereda el menú de
  siete ítems, el buscador de la cabecera, la miga de pan y el pie completo**. Si el criterio se lee
  como «la página debe ofrecer esas tres vías», se cumple por el armazón. Si se lee como «la propia
  página debe listar tres alternativas», no. **No puedo resolver esa ambigüedad leyendo el código** y
  conviene aclararla con quien redactó el criterio antes de tocar nada. Lo que sí es mejora clara en
  cualquier lectura: añadir enlaces a las tres secciones obligatorias.
- **Cómo se verifica.** Una URL inexistente muestra al menos tres destinos distintos dentro de la
  propia página.
- **Qué desbloquea.** Nada.

---

#### M-11 · `/servicios` y `/tramites` cubren el mismo objeto

- **Qué falta.** El menú principal no ofrece `/servicios` —sus siete ítems son Inicio, Transparencia,
  Atención y Servicios a la Ciudadanía (cuyo despliegue lleva a `/tramites`, `/atencion`,
  `/realizar-una-peticion` y `/seguimiento`), Participa, PQRSD, Normativa y Noticias
  (`layouts/default.vue:46-94`)—, pero `/servicios` sí está enlazada desde el carrusel de la portada,
  desde una tarjeta de la portada y desde el `sitemap.xml:47`. Y está vacía (GR-11), mientras
  `/tramites` (1 061 líneas) publica el catálogo completo de 123 trámites.
- **Norma.** FUN-013 (Must) exige mostrar la sección «Servicios a la Ciudadanía» en la portada, y el
  Anexo 2 §2.4.3 (i) exige que dé acceso a trámites y OPA.
- **Dónde.** `sitio/app/pages/servicios.vue` (17 líneas); `sitio/app/pages/tramites/index.vue`
  (1 061 líneas); `layouts/default.vue:46-94`; `sitemap.xml.ts:47`.
- **`[DEDUCCIÓN]`** `/tramites` **ya es** la sección «Atención y Servicios a la Ciudadanía»: es donde
  el menú obligatorio apunta y donde el contenido existe. Mantener dos rutas para el mismo objeto
  produce dos direcciones canónicas del mismo contenido, que es exactamente lo que el Anexo 2 §2.4.1
  (f) prohíbe. **Recomiendo redirigir `/servicios` a `/tramites` y corregir los enlaces.** Es mi
  recomendación, no una conclusión que se deduzca del texto de la norma; la alternativa —dar a
  `/servicios` el contenido de servicios **no** tramitables— es igualmente válida y es decisión del
  titular.
- **Cómo se verifica.** Existe una sola URL canónica para el catálogo y una comprobación de que
  ninguna otra ruta sirve el mismo contenido.
- **Qué desbloquea.** GR-11 en la parte de `/servicios`.

---

#### M-12 · El panel declara tokens «GOV.CO oficiales» que no lo son, y usa otra identidad tipográfica

- **Qué falta.** Tres afirmaciones y un hecho:
  1. `panel/src/assets/styles/tokens.css:1` titula el fichero «**SGDI — Design tokens GOV.CO
     oficiales**».
  2. Y define `--color-gov-blue: **#3366CC**` (`:3`). El Cobalt oficial del Kit, que el corpus repite
     once veces, es **`#0943B5`** (RF-B1-098, RF-B3-045, RNF-B3-045: «100 % de componentes con paleta
     Cobalt `#0943B5`»; Kit UI 9.2 p. 7: «Maneja un único color azul Cobalt “#0943B5”»). **Son dos
     azules distintos en el mismo proyecto**: el sitio usa el del Kit —`sitio.css:268-274` aplica
     `var(--govcolor-cobalt, #0943b5)` a todos los enlaces y documenta el contraste medido, 8,46:1— y
     el panel usa `#3366CC`.
  3. Define la tipografía como `--font-heading: 'Montserrat'` y `--font-body: 'Inter'`
     (`tokens.css:29-31`), y carga Montserrat, Inter y JetBrains Mono autoalojadas
     (`panel/src/assets/styles/fonts.css`), mientras el Kit fija **Nunito Sans** para títulos y
     **Verdana** para el cuerpo (Kit UI 9.2 p. 12).
  4. Además `tokens.css:42-55` declara dos paletas alternativas (`[data-brand='caribe']` y
     `[data-brand='dorado']`) que **ningún código activa**: `grep` de `data-brand` en `panel/src/`
     devuelve sólo su definición.
- **Norma.** RN-B2-029 («Kit UI GOV.CO de cumplimiento obligatorio en trámites integrados; solo el
  footer puede usar color institucional»); RNF-B3-045 (Must); CAG-05 (Must: «Barra superior: logo
  GOV.CO 24 × 136 px, color `#0943B5`»). **Y el límite honesto:** el Kit se declara «Requerido,
  mínimamente, en: Sedes electrónicas · Trámites y servicios · Ventanillas únicas · Portales
  transversales». Un back-office interno **no es** ninguno de los cuatro, de modo que usar otra
  identidad para el panel es **una decisión defendible** —[DEDUCCIÓN] es además la práctica habitual:
  un producto interno no debe parecerse al sitio público—, **pero es una decisión que nadie ha
  tomado**. No hay ADR que la registre y el fichero afirma lo contrario de lo que hace.
- **Dónde.** `panel/src/assets/styles/tokens.css:1, 3, 29-31, 42-55`; `panel/src/assets/styles/fonts.css`;
  `panel/tailwind.config.js:36-40`; `sitio.css:259-274`.
- **Qué hay que hacer.** Elegir una de las dos y escribirla: (a) el panel adopta Cobalt `#0943B5` y la
  tipografía del Kit —y entonces la autoridad tipográfica es una sola—; o (b) el panel mantiene su
  identidad propia y **el fichero deja de llamarse «GOV.CO oficiales»** y `--color-gov-blue` pasa a
  llamarse `--color-marca-panel`. Lo que no puede quedarse es la afirmación actual: **una fuente de
  verdad que dice ser lo que no es contamina todo lo que se construya encima.** Las dos paletas
  inactivas se retiran salvo que alguien pueda decir para qué están.
- **Cómo se verifica.** `grep -rn "3366CC" panel/src` no devuelve nada, o el fichero no afirma
  oficialidad; y existe un ADR que registra la identidad del panel.
- **Qué desbloquea.** M-15 (la comparación entre las dos capas visuales deja de ser una comparación
  entre dos verdades).

---

#### M-13 · El panel no puede publicar contenido y su pantalla de entrada lo afirma con datos inventados

- **Qué falta.** Este hallazgo aísla la parte del panel que **no** cubren BL-08, GR-01, GR-03 y GR-04:
  el **inventario de lo que falta**, medido, para que no se confunda con lo que existe.

  | Pieza de `panel/src/` | Líneas | Estado |
  |---|---|---|
  | `router/index.ts` | 123 | 18 módulos + entrada + MFA + recuperar + sin-permiso + 404 |
  | `layouts/AdminLayout.vue` | 332 | completo: menú de 18 módulos en 7 grupos, migas, paleta de comandos |
  | `views/admin/EnConstruccionView.vue` | **22** | sirve a los 18 módulos |
  | `views/admin/InicioView.vue` | **59** | tablero con 4 KPIs de maqueta |
  | `views/acceso/*.vue` (5 ficheros) | 63+33+30+26+31 = **183** | pantallas de acceso; ninguna autentica |
  | `components/` (12 ficheros) | **1 148** | sistema de diseño base completo |
  | `services/http.ts` | 68 | cliente Axios **huérfano** |
  | `types/api.ts` + `types/openapi.d.ts` | 51 + 580 | tipos del contrato, sin consumidor |
  | **Total** | **≈2 500** | de los cuales **publican contenido: 0 líneas** |

  Y el panel **se despliega**: `docker/nginx/conf.d/default.conf:124-129` sirve la SPA en `/admin`,
  `panel/dist/` contiene una construcción compilada, y la arquitectura del `README.md:55` lo describe
  como «SPA editorial» para «Funcionarios y ciudadanos». Un funcionario que hoy entre en
  `https://…/admin` encuentra un tablero con cifras inventadas, un botón de «Ingresar» que no
  autentica y dieciocho módulos que dicen «Vista en construcción».
- **Norma.** Todas las de BL-08, GR-01 y GR-03. Este hallazgo no añade norma: **ordena la lectura**.
- **Dónde.** `panel/src/` completo; `docker/nginx/conf.d/default.conf:124-129`.
- **Qué hay que hacer.** Antes de escribir una línea de módulo nuevo: **desconectar el panel de
  producción o ponerle una guarda** (GR-01). Un panel sin autenticación accesible desde Internet es
  peor que un panel que no existe, porque parece que existe.
- **Cómo se verifica.** Ninguna ruta del panel alcanza un módulo sin sesión, y ningún módulo de
  contenido está desplegado como accesible.
- **Qué desbloquea.** GR-01.

---

### 5.4 Menores

---

#### m-01 · `BaseModal.vue` no gestiona el foco

- **Qué falta.** `panel/src/components/base/BaseModal.vue` (65 líneas) implementa `role="dialog"`,
  `aria-modal="true"`, cierre con `Escape` (`:14`) y con clic en el fondo (`:37-38`), y bloquea el
  desplazamiento del cuerpo (`:20`). **No mueve el foco al diálogo al abrirse, no lo atrapa dentro y
  no lo devuelve al elemento que lo abrió al cerrarse.** Y `:34` usa `:aria-label="title"`, que es
  `undefined` por defecto (`:9`) cuando se usa el hueco `header` (`:41`): en ese caso el diálogo queda
  **sin nombre accesible**.
- **Norma.** WCAG 2.1 §2.4.3 (Orden del foco) y §4.1.2 (Nombre, función, valor); Kit UI 9.2 p. 16
  (CAG-21: «Alerta modal: cerrable con tecla Esc y clic fuera; no anidar modales; máximo 1 modal
  simultáneo»).
- **Dónde.** `panel/src/components/base/BaseModal.vue:9, 14, 29-41` y `:37-38`.
- **Qué hay que hacer.** Mover el foco al primer elemento enfocable al abrir, atraparlo, devolverlo al
  cerrar, y exigir el nombre accesible (o derivarlo del encabezado).
- **Cómo se verifica.** Con el modal abierto, `Tab` no sale de él; al cerrarlo, el foco vuelve al
  botón que lo abrió; y el diálogo tiene nombre accesible en sus dos modos de uso.
- **Qué desbloquea.** Nada.

---

#### m-02 · `DataTable.vue` ordena con ratón y no con teclado

- **Qué falta.** `panel/src/components/base/DataTable.vue` (134 líneas) pone el manejador de
  ordenación en el `<th>` (`:69`, `@click="header.column.getToggleSortingHandler()?.($event)"`), que
  **no es enfocable ni operable por teclado**, y **no declara `aria-sort`**. El usuario de teclado o
  lector de pantalla no puede ordenar y no sabe por qué columna está ordenada.
- **Norma.** WCAG 2.1 §2.1.1 (Teclado) y §4.1.2; Kit UI 9.2 p. 32 (CAG-24); RF-B3-076 (Must:
  «Tablas […] con **ordenamiento asc/desc**»).
- **Dónde.** `panel/src/components/base/DataTable.vue:60-70`.
- **Qué hay que hacer.** Un `<button>` dentro del `<th>` con `aria-sort` en el encabezado.
- **Cómo se verifica.** Ordenar por teclado cambia el orden y `aria-sort` refleja la columna activa y
  su sentido.
- **Qué desbloquea.** Nada.

---

#### m-03 · La paginación del panel está a 32×32 px

- **Qué falta.** Los botones de página de `DataTable.vue:115-130` usan `h-8 w-8`, es decir **32×32
  px**, cuando el Kit fija **44×44 px** para la paginación en móvil (Kit UI 9.2 p. 30, CAG-23) y
  RNF-B3-005 lo eleva a requisito (Must).
- **Norma.** Kit UI 9.2 p. 30 (CAG-23); RNF-B3-005; RF-B3-075 (Must).
- **Dónde.** `panel/src/components/base/DataTable.vue:115-130`.
- **Qué hay que hacer.** Elevar a 44 px en pantalla estrecha, con el mismo criterio con el que
  `sitio.css:396-414` ya lo hizo en el buscador del sitio.
- **Cómo se verifica.** A 360 px, los botones de paginación miden ≥44×44 px.
- **Qué desbloquea.** Nada.

---

#### m-04 · El `README.md` del panel es la plantilla de Vite sin adaptar

- **Qué falta.** `panel/README.md` tiene **5 líneas**, está **en inglés** y dice: «Vue 3 + TypeScript
  + Vite. This template should help get you started developing with Vue 3 and TypeScript in Vite».
  Es el texto que genera `npm create vite`. El proyecto tiene una regla explícita: «**El código va en
  castellano**: identificadores, mensajes y confirmaciones» (`README.md:124`).
- **Norma.** Regla del proyecto (`README.md:124`); `panel/` es la única carpeta del repositorio sin
  README propio adaptado.
- **Dónde.** `panel/README.md` (5 líneas).
- **Qué hay que hacer.** Escribirlo: qué es el panel, a quién sirve, cómo se arranca, qué módulos
  existen y **cuáles son marcadores**. Los 18 módulos de GR-13 no están documentados en ninguna parte.
- **Cómo se verifica.** `panel/README.md` describe el panel y no la plantilla.
- **Qué desbloquea.** Nada.

---

#### m-05 · `/buscar` se anuncia en el `sitemap.xml` y la página pide no indexarse

- **Qué falta.** `sitio/server/routes/sitemap.xml.ts:48` anuncia `/buscar` con prioridad 0.3, y la
  propia página se declara `noindex, follow` (`buscar.vue:25`, con la razón escrita: «Una página de
  resultados no aporta nada a un buscador externo y puede generar direcciones infinitas»). Anunciar
  en el mapa del sitio una dirección que la propia página pide no indexar es contradictorio.
- **Norma.** Res. 1519/2020 Anexo 1 §4.3.1 (b) (el mapa debe servir a los motores de búsqueda).
- **Dónde.** `sitemap.xml.ts:48`; `buscar.vue:25`.
- **Qué hay que hacer.** Quitar la entrada del `sitemap.xml`. La contradicción se resolvería sola
  cuando exista el índice (BL-07), pero la página **seguirá** siendo `noindex` por la razón que
  declara, así que la entrada sobra en cualquier caso.
- **Cómo se verifica.** Ninguna `loc` del mapa corresponde a una página con `noindex`.
- **Qué desbloquea.** Nada.

---

## 6. Cobertura por sección

### 6.1 Cómo se calcula, y qué significa el número

Dos universos distintos, y conviene no mezclarlos:

- **Universo A — el corpus de elicitación.** 409 requisitos con enunciado (§3.2). Es el universo de
  *qué hay que construir*.
- **Universo B — el expediente de `docs/`.** 140 criterios de aceptación, enumerados y cerrados:
  34 de diseño (`CAG-01…CAG-34`), 60 de funcionalidad (`FUN-001…FUN-060`), 18 de seguridad
  (`SEG-001…SEG-018`), 14 obligaciones (`O-01…O-14`), 3 de marco normativo (`RN-01…RN-03`), 5
  técnicos (`RT-01…RT-05`) y 6 de accesibilidad (`ACC-001…ACC-008`). Es el universo de *con qué se
  evalúa*.

**El porcentaje de la tabla siguiente es sobre el universo A**, y la unidad de medida es la sección.
El estado de cada sección sale de recorrer sus requisitos con enunciado y clasificarlos en
**construido** (existe y cumple su criterio), **parcial** (existe parte y falta pieza declarada) o
**ausente/stub** (no existe o existe la ruta sin contenido). El desglose por requisito está en la
matriz de §4.

**Dos avisos de honestidad.**
1. **Los números son de esta auditoría, no del proyecto.** El proyecto no tiene una matriz que
   funcione (BL-10). Reproducirlos exige repetir el recorrido de §4.
2. **Sobre el universo B, el «87 %» del proyecto no es un dato.** `docs/trazabilidad.md:29` afirma
   122 de 140 con evidencia, pero la evidencia que cita no existe (§3.4), y 6 de los 140 criterios
   —los `ACC-*`— **no tienen enunciado en ningún documento**: `docs/Sección 3 · Accesibilidad.md` no
   asigna ni un solo código `ACC-NNN` (usa la numeración WCAG 1.1.1…4.1.3). **El denominador de 140
   contiene 6 criterios sin texto.**

### 6.2 Cobertura por sección del corpus

| # | Sección | Reqs. con enunciado | Construidos | Parciales | Ausentes / stub | **Cobertura** | Qué falta, en una línea |
|---|---|---|---|---|---|---|---|
| 01 | Estructura e identidad | 41 | 6 | 6 | 29 | **27 %** | cookies, noticias, tipografía, identidad visual, 9 componentes del Kit, aviso de salida |
| 02 | Transparencia | 15 | 0 | 3 | 12 | **10 %** | la sección entera: categorías, apartados, fechas, directorio SIGEP, tributaria, datos abiertos |
| 03 | Servicios y trámites | 23 | 4 | 6 | 13 | **26 %** | radicación, 4 etapas, área de servicio, pagos, carpeta ciudadana, expediente |
| 04 | PQRSD | 10 | 2 | 2 | 6 | **30 %** | radicar, acusar, radicar número, seguir, antispam, SGDEA |
| 05 | Participa | 3 | 0 | 0 | 3 | **0 %** | todo: es un stub con las seis subcategorías rotuladas |
| 06 | Canales de atención | 3 | 1 | 1 | 1 | **50 %** | agendamiento de citas; acceso inclusivo |
| 07 | Accesibilidad | 35 | 6 | 11 | 18 | **33 %** | medición, declaración de conformidad, multimedia, área táctil de la barra superior |
| 08 | Usabilidad | 22 | 3 | 6 | 13 | **27 %** | encuesta y SUS, validación W3C, medición de lenguaje claro |
| 09 | Seguridad | 22 | 0 | 9 | 13 | **20 %** | RBAC, MFA, antispam, auditoría, protección de datos (ARCO, RNBD) |
| 10 | Interoperabilidad | 18 | 0 | 1 | 17 | **3 %** | todo: SCD, X-Road, TSA, expediente electrónico |
| 11 | Datos abiertos | 4 | 0 | 0 | 4 | **0 %** | ningún conjunto propio federado |
| 12 | Gestión de contenidos | 10 | 0 | 0 | 10 | **0 %** | CMS, roles, flujo editorial, TRD/AGN, registro 24/7, ITA, notificaciones, firma |
| | **Total** | **206** | **22** | **45** | **139** | **33 %** | |

**Recuento de estados por sección:** 0 secciones construidas por completo · 0 con cobertura ≥75 % ·
**7 parciales** (01, 03, 04, 06, 07, 08 y 09, todas entre el 20 % y el 50 %) · **5 stubs o ausentes**
(02 con el 10 %, y 05, 10, 11 y 12 por debajo del 5 %). **Ninguna sección llega al 75 %.**

**Cómo se lee esto bien.** El 33 % **no** significa «un tercio de la sede funciona»: significa que un
tercio de los requisitos del corpus tiene artefacto verificable en el repositorio. La distribución
importa más que el total, y la distribución dice que **lo construido se concentra en lo que se lee y
lo ausente en lo que se opera**. Las cinco secciones de cobertura más alta son las que el ciudadano
mira; las tres de cobertura más baja —interoperabilidad, datos abiertos, gestión de contenidos— son
las que sostienen que la sede *funcione*, y **son exactamente las tres que no tienen una sola línea
de implementación**.

### 6.3 Cobertura del expediente de `docs/` (universo B)

El proyecto declara 122 de 140 (87 %). **No he podido reproducir ninguna de las dos cifras**, porque
el artefacto que las produce no existe (§3.4). Lo que sí se puede decir, criterio por grupo, con la
evidencia que sí está en el disco:

| Grupo | Criterios | Con artefacto verificable en el repositorio | Observación |
|---|---|---|---|
| `CAG-01…034` (diseño) | 34 | **9** | CAG-05, 08, 09, 10, 11, 12, 15, 27 y 33 tienen implementación identificable. Los demás o no aplican declaradamente (ADR-0015) o no existen (CAG-21, 24, 25, 29) |
| `FUN-001…060` (funcionalidad) | 60 | **12** | Los de estructura y navegación; FUN-004/030 (captcha) ausentes; FUN-016…020 (Transparencia) ausentes |
| `SEG-001…018` (seguridad) | 18 | **4** | Los que viven en nginx (fuera de alcance, §1.3) y poco más; SEG-003, 013, 016 ausentes |
| `O-01…O-14` (obligaciones) | 14 | **2** | O-01 y O-14 tienen artefacto; el resto depende de piezas ausentes |
| `RN-01…03`, `RT-01…05` | 8 | **0** | No están definidos en `docs/Sección 1` (verificado: cero coincidencias de `RN-[0-9]`/`RT-[0-9]`), pero `docs/trazabilidad.md:169-183` se los atribuye |
| `ACC-001…008` (accesibilidad) | 6 | **0** | **Sin enunciado en ningún documento**; sólo `ACC-002` apunta a `BarraAccesibilidad.vue` |
| | **140** | **≈27 (19 %)** | Frente al 87 % declarado |

**[DEDUCCIÓN]** La brecha entre el 87 % declarado y el 19 % medido no es que el proyecto haya
empeorado: es que **el 87 % se calculó sobre otro sistema**. Es la misma causa raíz de §3.4.

### 6.4 Lo que el corpus pide y la norma no exige (y al revés)

La instrucción es explícita: una discrepancia aquí es un hallazgo en sí misma, porque significa que
alguien va a construir algo que no hace falta —o que falta algo que nadie pidió—. **Ocho
discrepancias, cada una verificada en las dos fuentes.**

**A. El corpus exige más que la norma.**

| # | Lo que el corpus pide | Lo que la norma y el Kit dicen | Consecuencia |
|---|---|---|---|
| 1 | **Línea de avance (stepper) de 4 etapas** en la ficha (RF-B2-028/RF-B3-071, Must) | El Kit la marca requerida en «**Trámites y servicios**» y **no** en «Sedes electrónicas» (Kit UI 9.2 p. 26) | El corpus eleva a obligatorio lo que el Kit destina a los trámites integrados al Portal. **Es una exigencia legítima del proyecto, no de la norma**: no la cite como obligación legal |
| 2 | **Área de servicio** (RF-B3-065, RF-B2-033/034 Must) | El Kit la destina a «Trámites y servicios» y a los trámites «vinculados al Portal GOV.CO» (p. 18) | Igual que el anterior. Tiene sentido para una sede que quiere integrarse, pero el fundamento es el corpus |
| 3 | **Encuesta de usabilidad en ≥90 % de las páginas** (FUN-052, `docs/Sección 5:417`) | Ninguna norma lo exige. Es una práctica | Construirla cuesta más que su rendimiento normativo. **Recomiendo priorizarla por debajo de BL-01** (M-02) |
| 4 | **IPv6 en coexistencia IPv4** (RF-B1-099/RNF-B1-027, Must) | Real, pero el plazo del corpus (31/12/2020) está **vencido** y no es verificable desde el código | No auditable aquí (§1.3) |
| 5 | **Modo oscuro y espaciado de texto en el panel** (implementados en `AccessibilityBar.vue`) | El Kit pide «Aumentar letra · Reducir letra · Contraste» (p. 6). El espaciado corresponde a WCAG §1.4.12; el **modo oscuro no lo pide nadie** | Funcionalidad construida sin requisito que la respalde. No hace daño; **no la trate como cumplimiento** |

**B. La norma (o el Kit) exige lo que el corpus no recogió.**

| # | Lo que exige la norma | Lo que el corpus dice | Consecuencia |
|---|---|---|---|
| 6 | **CAPTCHA en el módulo de inicio de sesión**: «el *Módulo de inicio de sesión* […] debe contener título, campo para ingresar contraseña, **módulo para confirmar que quien accede es un ser humano** y botones de navegación específicos» (Kit UI 9.2 p. 29) | El corpus recoge el login en RF-B3-074 citando el Kit, pero **su criterio de aceptación sólo enumera los tipos de documento** (CC, CE, TI, PEP, NIT) y **no menciona el CAPTCHA** | **El requisito quedó fuera de la aceptación.** El corpus aceptaría un login sin CAPTCHA. **Añádase al criterio de RF-B3-074** |
| 7 | **44×44 px en la barra superior**, «incluyendo las medidas del logo» (Kit UI 9.2 **p. 7**) | El corpus recoge el dato en RF-B1-001 («área activa ≥44×44 px») pero **sin el alcance**: no dice que alcanza al logotipo | Por eso el defecto de **GR-13** pasó desapercibido en dos auditorías. **Precise el criterio de RF-B1-001** |
| 8 | **Interlineado por estilo** (Kit UI 9.2 p. 12: h1 42/50, h4 22/32, body 15/22) | RNF-B3-045 sólo fija «paleta Cobalt + tipografía Nunito Sans/Verdana»; **el interlineado no está en ningún requisito del corpus** | **GR-12** no tiene requisito del corpus que lo respalde: sólo el Kit. **Añada el interlineado a RNF-B3-045** o acepte que no es exigencia |

**C. El caso simétrico, que es el más caro: la norma se contradice.**

| # | Contradicción | Dónde | Consecuencia |
|---|---|---|---|
| 9 | **Los anexos de la Resolución 1519/2020 se definen al revés en dos documentos del mismo expediente.** `docs/Sección 1 · Marco normativo.md:465`: «**Anexo 1**: WCAG 2.1 AA. Anexo 2: estándares de transparencia y divulgación. Anexo 3: seguridad digital. Anexo 4: datos abiertos». `docs/Sección 4 · Seguridad.md:308`: «la Sede debe cumplir el **Anexo 1 (transparencia pasiva y activa)** y el **Anexo 2 (accesibilidad, usabilidad, seguridad)**». Y `docs/adr/README.md:90-91` atribuye al «Anexo 2» la imposición del Kit | Los tres son incompatibles entre sí. **Ninguna cita de anexo es fiable sin resolver esto antes**, y esta auditoría ha tenido que apoyarse en el texto de la resolución recogido en `investigacion/raw/nat/res1519.html` para zanjar cada caso |
| 10 | **Disponibilidad: 95 % o 99 %.** `docs/Sección 5 · Funcionalidad.md:301` FUN-053 (Must): «igual o superior al **95 %**»; `:396`: «≥ 95 %». `docs/Sección 6 · Implementación paso a paso.md:15`: «≥ **99 %** mensual»; `:125`: «SLA ≥ 99 % mensual». `docs/Sección 1:257`: «SLA ≥ 99 % en horario hábil». Y el corpus añade dos más: RNF-B1-001 «≥98 %» para trámites y «≥95 %» para la sede, marcados como **C-02 «En conflicto — ALTA»** | **Cuatro umbrales distintos en cinco documentos** sobre el mismo atributo. La diferencia entre 95 % y 99 % es de unas 36 horas a unas 7 de caída al mes. **No se puede aceptar un sistema contra un criterio que tiene cuatro valores** |
| 11 | **Bloqueo de cuenta: 5 o 10 intentos.** `docs/Sección 4 · Seguridad.md:171` y `:415`: «tras **5 fallos en 5 min** […] Cuenta bloqueada tras **10 fallos/día**». `docs/trazabilidad.md:192` SEG-004: «la_cuenta_se_bloquea_tras_**cinco**_intentos_fallidos» | El criterio y su prueba afirman cosas distintas | La prueba no puede pasar. **Unifíquese antes de escribirla** |

**[DEDUCCIÓN] Lo que esto significa en conjunto.** El corpus es más exigente que la norma en cinco
materias, la norma es más exigente que el corpus en tres, y el expediente se contradice en tres más.
De las once, **las tres últimas son las graves**: no son huecos, son **criterios con dos valores**, y
un criterio con dos valores no se puede incumplir ni cumplir. Mientras el §7.1 de este documento no
se cierre, cualquier prueba que se escriba contra esos criterios será una prueba que afirma algo que
otro documento del mismo proyecto niega.

---

## 7. Contradicciones

Sólo las que enfrentan dos fuentes —norma, corpus, `docs/` o lo construido— y tienen consecuencia.
Las cinco primeras son las que hay que cerrar **antes** de construir.

### 7.1 Las cinco que bloquean trabajo

| # | Fuente A | Fuente B | Efecto | Acción |
|---|---|---|---|---|
| **1** | **Corpus:** el módulo 02 exige **diez** subsecciones de Transparencia, nombrando por separado (9) «Obligación de reporte específico» y (10) «Tributaria» (RF-B1-012/RF-B3-081, Must); `README.md:19` del corpus dice «las 10 subsecciones» | **`docs/transparencia.md:21-23`:** «Exige **nueve categorías**» | De ello depende **si hay que construir una o dos categorías nuevas** en BL-09 | Resolver con el texto del Anexo 2 §4.1.2.1 de la Res. 2893 **antes** de implementar |
| **2** | **Norma y Kit:** la Res. 1519/2020, Anexo 2 §2.4.3 y el Kit p. 18 exigen el **aviso de salida a sitio externo como modal** (RF-B1-071, Must) y el **Área de servicio** en los trámites | **ADR-0015 §3:** «CAG-19, **CAG-21**, CAG-22, CAG-24 y CAG-25 — **no aplican**. La sede […] **no abre modales**» | Un requisito Must presentado como decisión de diseño. **GR-15** y **GR-17** | Retirar CAG-21 y CAG-25 de las desviaciones declaradas; construir ambos |
| **3** | **ADR-0009:** «Se incorpora **Filament 5** (panel `/admin`) […] Livewire y Blade como dependencias del backend» | **Lo construido:** `docker/nginx/conf.d/default.conf:124-129` sirve una **SPA Vue estática** en `/admin`; **Filament no está en `backend/composer.json`** | No está claro **sobre qué superficie se construye el CMS** (BL-08) | **GR-05**: decidir y registrar |
| **4** | **ADR-0004:** los componentes «**delegan el comportamiento en el `script.js` del Kit**» | **ADR-0011:** «**No se carga `/govco/script.js`**». Verificado: el sitio no copió **ninguno** de los 15 `.js` del Kit | Dos decisiones aceptadas que se excluyen. La construida es la del 0011 | **GR-06**: poner fecha y estado a los ADR, y marcar el 0004 como sustituido |
| **5** | **`docs/Sección 1:465`:** Anexo 1 = accesibilidad, Anexo 2 = transparencia, Anexo 3 = seguridad, Anexo 4 = datos abiertos | **`docs/Sección 4:308`:** «Anexo 1 (transparencia) y Anexo 2 (accesibilidad, usabilidad, seguridad)» | **Ninguna cita de anexo del expediente es utilizable** | Resolver una vez y corregir el otro documento |

### 7.2 Las que afectan al diseño construido

| # | Fuente A | Fuente B | Efecto |
|---|---|---|---|
| **6** | **`docs/trazabilidad.md:102-106`:** FUN-016…020 «implementado» con `views/publico/TransparenciaView.vue` | **El repositorio:** `views/publico/` no existe; `/transparencia` tiene 17 líneas | **BL-10**: la matriz certifica un sistema que no es el desplegado |
| **7** | **`docs/trazabilidad.md:31`:** «Operaciones del contrato: **41** (41 implementadas)» | **`contract/openapi.yaml`:** **3** operaciones, 2 implementadas y 1 `pending` | El contrato se declara fuente única (`README.md:58`) y la matriz lo contradice por un factor de 13 |
| **8** | **Contrato:** slugs de las cinco políticas `terminos-y-condiciones`, `tratamiento-de-datos`, `derechos-de-autor` (`contract/openapi.yaml:110-124`) | **Sitio:** `terminos-y-condiciones-de-uso`, `proteccion-y-tratamiento-de-datos-personales`, `derechos-de-autor-y-uso-sobre-contenidos` (`politicas/[slug].vue:33-57` y `sitemap.xml.ts:64-70`) | **Tres de cinco no coinciden.** En cuanto `/entidad` se implemente y el pie consuma `politicas[].slug`, **tres de los cinco enlaces del pie darán 404** —y el 404 aquí es real y contundente, por diseño (`politicas/[slug].vue:74-82`) |
| **9** | **`README.md:58` y `contract/README.md:3-5`:** el contrato es «la única fuente de verdad del intercambio HTTP» | **`contract/openapi.yaml:322-331`:** declara `securitySchemes` (`cookieSesion`, `portador`) y **ninguna de las tres operaciones los usa** —las tres llevan `security: []` (`:127, :287, :319`) | El mecanismo de sesión del panel está declarado en el contrato y **no lo consume ninguna operación**: no hay contrato para la escritura (BL-01, GR-04) |
| **10** | **Kit UI 9.2 p. 12:** *Body text 1* = **Verdana Regular 15/22** | **`all.css:67-71`:** `html { font-family: 'Nunito_Sans-Regular' }`, y el sitio no declara familia para el texto corrido (`sitio.css`: 0 `font-family`) | Los párrafos de las 21 páginas salen en **Nunito Sans a 16 px**, no en Verdana a 15. **GR-12** |
| **11** | **Kit UI 9.2 p. 7:** «área activa mínima de 44 x 44 píxeles, **incluyendo las medidas del logo**» | **`all.css:702-705`:** `.barra-superior-govco a { height: calc(1.5rem * 1.5) }` = **36 px** | El Kit incumple su propia especificación y el sitio la hereda. **GR-13** |
| **12** | **Kit:** `.govco-logo-entidad { content: url(assets/images/Logo-v2-MinTIC.png) }` (`all.css:8522`) y lo mismo en `:849`, `:794`, `:8736` | **Sitio:** omite la clase a propósito y usa un `<img>` propio (`PiePaginaGovco.vue:24-30`, `:295`) | **El sitio tiene razón.** Pintar el logo del Ministerio TIC donde va el de la autoridad sería mostrar el emblema de otro organismo. La desviación está justificada y verificada. **No «corregir» esto** |
| **13** | **Kit:** `.pie-pagina-govco .logo-container { position: absolute; right: 3.125rem }` (`all.css:8570-8573`) y `sitio.css:107-114` lo pasan a `position: static` | El Kit UI 9.2 **p. 11** exige que los logos «estén **asociados y alineados a la izquierda**» | El Kit incumple su propio texto en escritorio. La corrección del sitio es correcta |
| **14** | **Kit:** el botón de contraste alterna `contrast-govco` | **`all.css:681,685`:** sus dos únicas reglas apuntan a `.accesibility-example`, la caja de demostración | **El botón del Kit es un stub.** El sitio lo reemplazó por uno real (`BarraAccesibilidad.vue:11-13`, `useContraste` en `useAccesibilidad.ts:16-20`). **No revertir** |
| **15** | **Kit:** `.barra-superior-govco` a 56 px de alto (p. 7) | **`all.css:692-695`:** `height: 3.5rem` = 56 px ✓ | **Coincide.** Se registra para que no se «corrija» a 44 |

### 7.3 Las que afectan a la documentación

| # | Fuente A | Fuente B | Efecto |
|---|---|---|---|
| **16** | **`docs/Sección 5:301,396`:** disponibilidad ≥**95 %** (FUN-053, Must) | **`docs/Sección 6:15,125`** y **`docs/Sección 1:257`:** ≥**99 %**. Y el corpus, marcado C-02 «ALTA»: ≥98 % trámites / ≥95 % sede | Cuatro umbrales. **§6.4 #10** |
| **17** | **`docs/Sección 4:171,415`:** bloqueo tras 5 fallos en 5 min / cuenta bloqueada tras 10 fallos al día | **`docs/trazabilidad.md:192`:** «la_cuenta_se_bloquea_tras_cinco_intentos_fallidos» | El criterio y su prueba no coinciden. **§6.4 #11** |
| **18** | **`docs/Sección 2 · Diseño.md:166`:** «El Kit UI v9.2 organiza **24 componentes** en 3 grupos»; `:181` titula «Componentes generales **(11)**»; `:362` «8 transversales, **11 generales** y 4 de formulario» | **La tabla del propio documento** (`:170-212`) contiene **31 filas**; su diagrama (`:230-250`) lista **19** nodos generales; **ADR-0004:111** dice «(8 transversales, **19 generales** y 4 de formulario)»; **el PDF del Kit** lista **9 + 19 + 4 = 32** | **Cuatro cifras distintas para el mismo catálogo.** Este informe usa la del PDF, que es la fuente |
| **19** | **`docs/trazabilidad.md:26`:** «Total **140**» | **`docs/GUIA Maestra Sede Electronica Colombia.md:3409`:** «Las secciones 2 a 5 producen un total de **~210 criterios** trazables», con «CAG-NN **57**» (la Sección 2 define **34**) y «FUN-NN **74**» (la Sección 5 define **60**, `:317`) | El denominador de la cobertura no es único. **BL-10** |
| **20** | **`docs/Sección 6 · Implementación paso a paso.md`** cita códigos como criterios (p. ej. `:41` asigna **CAG-12** a la miga de pan —CAG-12 es el pie, la miga es CAG-11—; `:65` asigna **CAG-09** a la barra de accesibilidad —es CAG-07—; `:107` asigna **FUN-026** a las pruebas de regresión —FUN-026 son noticias—; `:143` usa **RT-02** para firma electrónica —en `trazabilidad.md:180` RT-02 es TLS—) | **Las secciones 2, 4 y 5**, donde esos códigos están definidos | **Una docena de desalineaciones. Los códigos de la Sección 6 no son utilizables como matriz** sin corregirlos |
| **21** | **`docs/Sección 3`** no asigna **ningún** código `ACC-NNN` | **`docs/Sección 6:86,94-95,140`** y **`docs/trazabilidad.md:37-42`** los usan como si existieran; el Apéndice B los cuantifica en «49 [F] + 75 [A]» sin lista que lo respalde | **6 de los 140 criterios del denominador no tienen enunciado.** Cualquier cobertura calculada sobre ellos es falsa por construcción |
| **22** | **Corpus, módulo 12 y RF-B1-078:** el tablero ITA es **interno**, «arranca en **cero**» y «**no usa datos mock** ni puntuaciones precargadas (no se usa el 47/100)»; A-03 resuelto el 2026-06-05 | **`panel/src/views/admin/InicioView.vue:29`:** «Cumplimiento ITA **94 %**» | **GR-03**: la única cifra de cumplimiento que produce el sistema es inventada y contradice una decisión registrada |
| **23** | **`docs/adr/README.md:404-405`:** la barra superior «conserva el logo GOV.CO **y la galería de aplicaciones**» | **`grep -rn "GaleriaAplicacionesGovco" sitio/app/` → cero coincidencias** | **M-08**: la desviación de CAG-06 está justificada con un hecho falso |
| **24** | **`README.md:55` del repositorio:** el panel es «SPA editorial» para «Funcionarios y ciudadanos» | **`panel/src/router/index.ts:69-76`:** sus 18 módulos son marcadores | La arquitectura publicada describe una capacidad que no existe. **M-13** |
| **25** | **ADR-0003:** «**No se instala Tailwind** […] se descarta FontAwesome» | **`panel/package.json:12-21`:** `tailwindcss ^3.4.19` + tres paquetes `@fortawesome/*`, todos en uso | **GR-06** |
| **26** | **ADR-0001:** monorepo con `backend/` y **`frontend/`** | El repositorio tiene `backend/`, **`panel/`** y **`sitio/`** | El ADR no contempla el sitio Nuxt, que es la pieza central. **GR-06** |

---

## 8. Cadenas de dependencia y orden de corrección

### 8.1 Las cadenas, con lo que desbloquea cada eslabón

**Cadena 1 — la operativa (la que define una sede electrónica).**

```
BL-01 (radicar: contrato + backend + Policy)
  ├─→ BL-02 (acuse y radicado)          → cierra el módulo 04
  ├─→ BL-03 (seguimiento)               → cierra /seguimiento (GR-11)
  ├─→ GR-18 (citas) ────────────────────→ primer módulo real del panel
  ├─→ GR-01 (guarda de sesión del panel) ← DEBE ir ANTES, no después
  └─→ M-02 (encuesta y SUS)             ← sólo tiene sentido con servicio que evaluar
```

**Cadena 2 — la publicación (el efecto dominó mayor).**

```
GR-05 (decidir la superficie editorial: Filament o la SPA)
  └─→ BL-08 (CMS: modelo, API, Policy, pantalla)
        ├─→ GR-01 (RBAC y guarda)          ← y GR-19: los paquetes ya instalados
        ├─→ GR-03 (tablero ITA real, desde cero)
        ├─→ GR-09 (fecha de publicación obligatoria)
        ├─→ GR-11 (las 5 secciones vacías dejan de estarlo)
        ├─→ GR-15 (lista blanca del aviso de salida)
        ├─→ GR-16 (sitemap de fichas: la API pasa a ser la fuente)
        ├─→ GR-17 (área de servicio)
        ├─→ M-01 (encuesta de usabilidad)
        ├─→ M-04 (SUIN desde /normativa)
        └─→ BL-09 (Transparencia entera)   ← y antes, resolver §7.1 #1 (¿9 o 10 categorías?)
              └─→ M-03 (federación de datos abiertos)
```

**Cadena 3 — la demostrable (la que hace que todo lo demás valga).**

```
BL-10 (retirar o regenerar la matriz falsa)
  └─→ GR-08 (una sola línea base, versionada y reconciliada)
        └─→ se puede volver a medir la cobertura

GR-07 (suite de accesibilidad + declaración de conformidad)
  ├─→ permite verificar GR-12 (tipografía) y GR-13 (área táctil) con instrumentos
  ├─→ permite verificar M-09 (contraste del panel) y m-01, m-02 (foco y teclado)
  └─→ hace publicable la declaración de conformidad (Anexo 1 num. 9.3)
```

**Cadena 4 — la coherencia, que no bloquea nada y hay que hacer igual.**

```
GR-06 (ADR con fecha y estado)
  ├─→ GR-05 (queda claro qué superficie editorial manda)
  ├─→ M-12 (decidir de una vez la identidad del panel) ─→ M-09 (una sola barra de accesibilidad)
  └─→ GR-14 (retirar CAG-21 y CAG-25 de las desviaciones declaradas)
```

### 8.2 Orden de corrección

El orden es por **valor para el ciudadano** y por **dependencia técnica**, no por facilidad.

**Etapa 0 — antes de escribir una línea (horas, no semanas).**
1. **GR-01 · poner la guarda del panel, o desconectarlo de producción.** Hoy `/admin` es una
   superficie de administración sin autenticación, accesible desde Internet, que anuncia un doble
   factor inexistente. **Nada de lo demás importa hasta que esto se resuelva**, y cuesta un
   `beforeEach`.
2. **GR-02 · retirar las afirmaciones falsas** del panel y del enlace de la cabecera pública.
3. **BL-10 · retirar `docs/trazabilidad.md` o marcarlo como no vigente.** Mientras exista, cualquier
   decisión que se tome leyéndolo será una decisión equivocada.

**Etapa 1 — cerrar las contradicciones que bloquean construcción.**
4. §7.1 #1 (¿nueve o diez subsecciones?), #2 (retirar CAG-21 y CAG-25 de ADR-0015), #3 (Filament o
   SPA), #4 (ADR-0004 vs 0011), #5 (los anexos). **Son decisiones, no programación**, y sin ellas se
   construye contra dos criterios distintos.
5. **GR-06 y GR-08**: fechar los ADR y reconciliar la línea base. Barato, y todo lo demás se apoya
   en ello.

**Etapa 2 — lo que la ley exige para poder operar.**
6. **GR-05 → BL-08** (decidir la superficie y construir el primer corte vertical del CMS). Es el
   hallazgo con mayor efecto dominó de toda la lista.
7. **BL-01, BL-02, BL-03** (radicar, acusar, seguir). Es el servicio que define la sede.
8. **BL-04** (antispam: el honeypot ya está instalado). Va **con** BL-01, no después: un formulario
   que radica sin control antispam es un formulario que se llena solo.
9. **BL-05** (cookies). Independiente de todo; puede hacerse en paralelo desde el primer día.
10. **BL-06** (las cinco políticas). **No es programación: es un acto administrativo. Debe empezar
    ya**, porque su plazo no depende del equipo técnico.
11. **GR-19** (decidir los seis paquetes de Spatie) — cuatro tienen destino y son capacidad ya
    instalada de BL-04, GR-01 y la auditoría del módulo 12.

**Etapa 3 — lo que cierra los huecos de la norma de publicación.**
12. **GR-09** (fecha obligatoria) — con BL-08.
13. **BL-09** (Transparencia) — con BL-08, y después de §7.1 #1.
14. **BL-07** (buscador) — necesita el índice de BL-08.
15. **GR-16** (sitemap de fichas), **GR-11** (las cinco secciones), **M-11** (decisión sobre
    `/servicios`).
16. **M-03** (datos abiertos) y **M-04** (SUIN). El SUIN es una URL pública: **puede hacerse hoy.**

**Etapa 4 — lo que hace demostrable lo que el sitio afirma.**
17. **GR-07** (suite de accesibilidad y declaración de conformidad). **Debería adelantarse en
    paralelo a la etapa 3 si hay capacidad**: mide trabajo futuro tanto como el presente, y es la
    única forma de verificar GR-12, GR-13, M-09, m-01 y m-02.
18. **GR-12** (tipografía) y **GR-13** (área táctil de la barra superior) — una sola hoja las dos,
    con la suite del punto 17 como árbitro.

**Etapa 5 — el acabado que la norma exige y el ciudadano nota.**
19. **GR-14** (componentes del Kit), **GR-15** (aviso de salida) y **GR-17** (área de servicio) —en
    la ficha del trámite y en la barra superior.
20. **GR-10** (cuatro momentos GOV.CO), **GR-18** (citas).
21. **M-01, M-05 a M-13** y **m-01 a m-05** — correcciones puntuales, muchas de una línea.
22. **M-02** (encuesta) — sólo cuando haya servicio que evaluar.

### 8.3 Lo que **no** hay que tocar en ninguna etapa

Todo lo listado en la sección 9. En particular: **el orden del menú, la derivación de la miga de
pan, el 404 real, los slugs cerrados con 404, el escalado de letra por la raíz, el contraste real, la
abstención de declarar conformidad sin auditoría, la publicación de la procedencia, y la negativa a
inventar fechas, nombres de aplicación, estados de sistema o cifras de cumplimiento.** Casi todos los
hallazgos de este documento conviven con esas piezas sin contradecirlas: **son huecos alrededor de un
trabajo bien hecho, no errores dentro de él.**

---

## 9. Lo que ya está bien (y no hay que romper «arreglándolo»)

Esta sección existe para que una corrección futura no deshaga trabajo correcto. Todo lo que sigue
está verificado en el código.

### 9.1 Estructura y navegación

1. **El menú de siete ítems y su orden.** Los tres mínimos obligatorios primero (Transparencia,
   Atención y Servicios a la Ciudadanía, Participa) y los adicionales detrás, con las **seis
   subcategorías exactas** de Participa (`layouts/default.vue:46-94`, con los comentarios de
   `:38-45`). Coincide con el Anexo 2 §4.1.2. **No reordenar.**
2. **La miga de pan se deriva del menú, no se escribe página a página** (`layouts/default.vue:105-150`).
   Es lo que impide que el nombre de la miga y el del menú diverjan y que una página nueva herede
   miga sin que nadie se acuerde. El truco de buscar el prefijo más largo (`:125-130`) resuelve bien
   las rutas de Trasparencia y Participa.
3. **El `<main>` y el salto al contenido viven en la disposición, no en cada página**
   (`layouts/default.vue:197-199`), con `tabindex="-1"` para que el foco se mueva de verdad y
   `outline: none` en el destino (`:250-252`) para que no dibuje un marco sobre todo el contenido.
   Cualquier página nueva hereda el enlace funcionando.
4. **El 404 es un 404 de verdad.** `pages/[...ruta].vue:20-27` fuerza `statusCode: 404` con
   `fatal: true`, y `error.vue:37` monta la disposición a mano porque Nuxt no la aplica a ese
   fichero. **No sustituir por una redirección a la portada.**
5. **Los slugs cerrados responden 404 real.** `politicas/[slug].vue:74-82` y
   `participa/[slug].vue:61-63`. Una dirección inventada no puede devolver 200 con una página vacía:
   en una sede electrónica eso es peor que un error.
6. **`robots.txt` no oculta nada** (`sitio/public/robots.txt`, 535 bytes) y explica por qué.
7. **`content-type` y `cache-control` del `sitemap.xml` son correctos**, y el `<loc>` se construye
   con URL absoluta y esquema a partir de la configuración y **no** de la cabecera `Host`
   (`server/routes/sitemap.xml.ts:79-98`). La segunda decisión evita anunciar la IP de un
   balanceador.

### 9.2 Accesibilidad

8. **El contraste es real y no decorativo.** `useAccesibilidad.ts:16-20` y `sitio.css:24-81`: el Kit
   sólo estilizaba su caja de demostración —**verificado**: `all.css:681,685` son las dos únicas
   reglas de `.contrast-govco` y ambas apuntan a `.accesibility-example`— y el sitio construyó un
   modo de verdad, con los contrastes calculados y anotados (21:1 el fondo, ~15:1 el amarillo).
   Además distingue los estados con `aria-pressed` (`BarraAccesibilidad.vue:45`), que es lo que un
   lector de pantalla necesita.
9. **El escalado de letra escala la raíz y no escribe `font-size` en línea en cada elemento**
   (`useAccesibilidad.ts:40-49`; el Kit recorre `body *` y fija estilos en línea,
   `vendor-src/.../barra-accesibilidad.js:99-101`). La razón está escrita y es correcta: el enfoque
   del Kit no alcanza al contenido que llega después de una llamada a la API.
10. **El carrusel tiene los tres controles obligatorios** —indicadores de posición, flechas y
    reproducción/pausa— con etiquetas accesibles que cambian con el estado
    (`CarruselGovco.vue:248-280`), los controles viven en **una barra propia fuera de la imagen** (`:247`, que es
    CAG-01), y respeta `prefers-reduced-motion` (`:195-200`). El defecto de M-01 es el valor inicial,
    no el componente. Y es el único componente del sitio realmente reimplementado en su marcado, con
    motivo declarado y verificado: el Kit superpone los controles a la fotografía.
11. **El enlace de accesibilidad va en el pie y la página no afirma conformidad.**
    `PiePaginaGovco.vue:223-232` cita el Anexo 1 §4.3.2 (c) y `pages/accesibilidad.vue:10-17`
    explica por qué **no** declara un nivel. Las dos decisiones son correctas. **No añadir un sello
    «Cumple AA» sin la auditoría de GR-07.**
12. **Las correcciones al Kit están medidas y razonadas, no son gusto.**
    `sitio.css:183-192` calcula el contraste de cada combinación y elige **texto negro sobre azul
    celeste de la Entidad** porque es la única que pasa (8,12:1) frente a blanco (2,59:1) o azul
    noche (2,78:1). `sitio.css:194-205` documenta además un **error propio anterior** —redefinir el
    token en todo el pie hundía los enlaces de la primera sección a 2,58:1— y su corrección. Es
    exactamente la disciplina que hay que preservar.
13. **El texto de los enlaces no depende de Bootstrap.** `sitio.css:254-270`: Bootstrap pinta los
    enlaces con `#0D6EFD`, que sobre blanco da **exactamente 4,50:1** —en el límite— y sobre el
    amarillo del Kit baja a 3,63:1. El sitio lo reapunta al Cobalt del Kit (8,46:1) en una sola
    regla, de modo que un enlace nuevo hereda el color correcto sin que nadie lo escriba.

### 9.3 Datos, contrato y honestidad

14. **El catálogo de trámites está completo y es honesto.** 123 trámites con los seis atributos,
    paginación y búsqueda **en el servidor**, filtros validados contra el contrato
    (`ListarTramitesRequest`), mensajes de error en castellano escritos a mano y los tipos generados
    desde el contrato (`types/openapi.d.ts`). **«Contrato primero» se cumple aquí de verdad.**
15. **La ausencia de datos se declara en pantalla y no se rellena.**
    `realizar-una-peticion.vue:402-410` (el formulario no radica), `buscar.vue:44-47` (el índice está
    vacío), `politicas/[slug].vue:111-117`, `components/SeccionEnPreparacion.vue:18-26` y
    `verificar/[codigo].vue`. **Es la razón por la que esta auditoría tiene que ser explícita: el
    sitio ya dice la verdad sobre sí mismo.** Buena parte de lo que aquí se llama «hallazgo» está
    reconocido en la propia interfaz, y eso es una virtud.
16. **El orden cronológico no se finge.** `docs/transparencia.md:62-64` fija la postura: «mientras la
    Entidad no declare fechas […] eso hay que declararlo en la página en vez de inventar una
    cronología». **No cambiarla por comodidad** cuando se construya BL-09.
17. **La procedencia de cada documento se publica, no se oculta.** El modelo de procedencia —fuente,
    apartado de origen, campos que no vienen tal cual, reglas con las que se calculó, lo que la
    fuente no declara— está documentado en `docs/transparencia.md:124-126` y **es el activo más
    valioso del proyecto**. Es lo que hay que migrar a base de datos en BL-08, no lo que hay que
    desechar.
18. **Los defectos del origen están documentados antes de reproducirlos.** `docs/transparencia.md:66-90`
    enumera los diez, incluido el `<liclass>` malformado que hundía 88 documentos, las diez URLs
    relativas y el enlace que apuntaba a sí mismo. Y `:94-109` documenta la trampa de los `ds_*.json`
    («**24 de Barranquilla**, 7 de Santa Rosa de Cabal, **0 de Santa Marta**»). **Ese análisis es el
    que impide publicar datos ajenos como propios.** Consérvese.

### 9.4 La capa visual y el Kit

19. **La capa visual no depende de un tercero.** El Kit, Bootstrap y las tipografías se sirven desde
    el propio dominio (`nuxt.config.ts:61-71`, `public/govco/`), con la copia de Bootstrap verificada
    contra el `sha384` que publican los ejemplos del Kit. **Y el `all.css` es byte a byte el del
    Kit**: comprobado con `cmp`, 266 316 bytes idénticos a
    `vendor-src/layout-govco-v5/src/all.css`. **No se ha modificado el Kit; las correcciones van en
    `sitio.css`.** Esa separación es la correcta.
20. **Las 786 referencias a activos del `all.css` resuelven.** Comprobado: de las 787 `url()`
    distintas del fichero, 786 existen en el disco (la restante es un `data:` URI). No hay un solo
    icono roto.
21. **La barra superior reproduce el marcado canónico exacto**, incluido el `<a>` vacío cuyo logo
    pinta el CSS (`BarraSuperior.vue:16-23` frente a `examples/transversal/barra-superior.html:24-27`
    y `all.css:702-705`). El único defecto es la altura, y es del Kit (GR-13).
22. **Ocho de los once componentes conservan el contrato de clases del Kit sin sustituir ninguna.**
    Verificado clase por clase contra el `all.css`: barra de accesibilidad, barra superior, buscador,
    galería de aplicaciones, menú de navegación, miga de pan (incluida la clase mal escrita
    `invested`, que el Kit define así en `all.css:8502`), tarjetas de información y volver
    arriba. **Ninguna clase del Kit que exista en `all.css` fue reemplazada por otra.**
23. **Las reimplementaciones en Vue cubren un contrato más amplio que el del Kit.** El menú del Kit
    **no gestiona ninguna tecla de navegación**; el sitio añade `Escape` con devolución de foco
    (`MenuNavegacionGovco.vue:399-428`), `ArrowDown`/`ArrowUp` (`:437-500`), `Inicio`/`Fin`
    (`:486-489`) y cierre al salir el foco (`:516-525`). La galería pasa de 22 líneas de JS sin teclado a un componente con
    `aria-expanded`/`aria-controls` y navegación en rejilla. **La decisión de no cargar el `script.js`
    del Kit (ADR-0011) está bien tomada**: el agregado se autoinicializa sobre selectores que en
    estas páginas no existen —y su propio encabezado dice `Version: 4.0.0`
    (`vendor-src/.../src/script.js:3`) mientras el árbol dice 5.0.0—.
24. **El sitio documenta cada desviación del Kit con su motivo.** Los JSDoc de cabecera de los once
    componentes explican **por qué** se apartan, y **en los 20 puntos que he podido verificar, la
    descripción del Kit es exacta**: el `content: url(...Logo-v2-MinTIC.png)`, el stub del contraste,
    el `-webkit-fill-available` del buscador, la clase muerta `barra-inferior-desktop`, el
    `position: absolute` del `logo-container`, la ausencia de `sr-only` en el Kit y en Bootstrap 5
    (que la renombró `visually-hidden`). **Ese rigor es el activo que hay que conservar.**
25. **El Kit es el insumo peor construido del proyecto, y el sitio lo sabe.** Medición reproducible
    de `vendor-src/`: **239 defectos**, entre ellos 8 clases referenciadas en los ejemplos que no
    existen en su propio `all.css`; una deriva de versión demostrable (los ejemplos usan
    `barra-inferior-desktop`/`dir-menu-govco` mientras el bundle define
    `barra-inferior-mobile`/`container-navbar-menu-govco`); dependencia de Bootstrap CSS **y JS** que
    su README niega; 15 defectos de JS (dos ramas muertas por `classList.contains('button.x')`, cuatro
    accesos sin comprobar nulo que lanzan `TypeError`, un `window.onload` que sobrescribe y un
    `Escape` que cierra un modal fijo por id); 194 enlaces con destino vacío o `#`; 8 defectos ARIA; y
    tres plantillas de ejemplo que enlazan `../src/govco.css`, un fichero **inexistente**.
    **Conclusión que hay que retener: cada apartamiento del sitio tiene su causa en el Kit, no en el
    sitio.** Cuando algo del sitio parezca raro, **léase primero el Kit**.

### 9.5 Backend, contrato y puertas

26. **La integración continua es seria y no decorativa.** Cinco trabajos bloqueantes (`ci.yml`:
    Contrato, Backend, Panel, Sitio, Pila) más revisión de dependencias, con una comprobación
    **negativa** deliberada de que la pila deja de resolverse cuando falta un secreto
    (`ci.yml:173-189`). El trabajo `Sitio` comprueba tipos **porque** `nuxt build` no los comprueba
    (`ci.yml:145-148`). Lo que falta es añadir accesibilidad y pruebas de frontend (GR-07).
27. **`panel/vite.config.ts:7-14` documenta un fallo que habría sido invisible.** `base: '/admin/'`
    no es cosmético: sin él, Vite emitía las direcciones de sus activos desde la raíz y el navegador
    recibía una página HTML con `nosniff` y se negaba a ejecutarla: **el panel quedaba en blanco con
    todas las respuestas en 200**. Es el tipo de defecto que cuesta un día encontrar y una línea
    arreglar.
28. **El panel protege la procedencia de sus fuentes.** `panel/src/assets/fonts/LEEME.md` documenta
    el `sha256` de cada binario, la fecha de obtención, la licencia (SIL OFL 1.1) y el procedimiento
    exacto para regenerarlas, con la razón de autoalojarlas: «cargarla desde un tercero entregaría la
    IP de cada funcionario a un servicio externo, que en una entidad pública es una cesión de datos
    que no corresponde hacer». **Es la misma disciplina que la procedencia del sitio, aplicada a un
    producto interno. Consérvese.**
29. **Los componentes base del panel están bien construidos, y GR-01 no los desmiente.**
    `FormField.vue` asocia etiqueta con `for`, usa `aria-describedby`, `aria-invalid` y `role="alert"`
    (`:52-90`). `DataTable.vue` emite un `<table>` con `scope="col"` (`:58-64`) y estado vacío y de
    carga propios. `BaseModal.vue` tiene `role="dialog"`, `aria-modal`, cierre con `Escape` y con
    clic en el fondo. `KpiCard.vue:24-28` **documenta el contraste medido** y elige los tonos `-700`
    en lugar de `-600` porque «`emerald-600` da 3,76:1 y no alcanza el 4,5:1 que exige WCAG 2.1 AA».
    **El problema del panel no es su calidad: es que le falta la mitad que publica** (M-13).

---

## 10. Índice de hallazgos

**47 hallazgos**: 10 bloqueantes, 19 graves, 13 medios, 5 menores.

### Por gravedad

| Código | Título | Sección | Cadena |
|---|---|---|---|
| **BL-01** | No hay radicación: el contrato no declara ninguna operación de escritura | 04 | 1 |
| **BL-02** | No hay acuse de recibo ni compromiso de radicado | 04 | 1 |
| **BL-03** | No hay mecanismo de seguimiento en línea | 04 | 1 |
| **BL-04** | No hay control antispam, y el paquete ya decidido está instalado sin cablear | 04, 09 | 1 |
| **BL-05** | No existe consentimiento de cookies | 01 | — |
| **BL-06** | Las cinco políticas obligatorias no tienen documento | 01, 09 | — |
| **BL-07** | El buscador de la Sede no existe: hay un campo que no busca | 01, 02 | — |
| **BL-08** | No hay gestor de contenidos: ningún funcionario puede publicar sin desplegar código | 12 | 2 |
| **BL-09** | La sección de Transparencia no existe | 02 | 2 |
| **BL-10** | La matriz de trazabilidad del proyecto certifica un estado que no existe | Transversal | 3 |
| **GR-01** | El panel no tiene ningún control de acceso: declara permisos y no los lee | 12, 09 | 1, 2 |
| **GR-02** | El panel afirma una sesión y un doble factor que no existen | 12 | 4 |
| **GR-03** | El panel publica cifras de maqueta, incluido el ITA que una decisión prohíbe | 12 | 2 |
| **GR-04** | El panel no habla con la API: cliente huérfano, sin tiendas y sin contrato de escritura | 12 | 2 |
| **GR-05** | ADR-0009 decide Filament 5 en `/admin`; lo construido sirve una SPA Vue en `/admin` | 12 | 2, 4 |
| **GR-06** | Los ADR describen una arquitectura que ya no existe, y ninguno tiene fecha | Transversal | 4 |
| **GR-07** | No hay prueba de accesibilidad ni declaración de conformidad, y la puerta citada no existe | 07, 08 | 3 |
| **GR-08** | La línea base no está versionada, está duplicada y no reconcilia | Transversal | 3 |
| **GR-09** | La fecha de publicación es opcional en la fuente y no consta en ninguna | 02 | 2 |
| **GR-10** | La ficha del trámite no publica los cuatro momentos GOV.CO | 03 | 2 |
| **GR-11** | Cinco secciones del menú y el sitemap son avisos de «en preparación» | 01-06 | 2 |
| **GR-12** | El sitio no aplica la tipografía ni el interlineado del Kit, y ahora está medido | 01, 08 | 3 |
| **GR-13** | El enlace de la barra superior mide 36 px: la corrección escrita se aplicó a otro selector | 01, 07 | 3 |
| **GR-14** | Nueve componentes del Kit exigidos para sedes electrónicas no existen | 01, 03 | 4 |
| **GR-15** | No hay aviso de salida a sitio externo, y el ADR que lo declara inaplicable contradice la norma | 01 | 2, 4 |
| **GR-16** | Las 123 fichas de trámite no están en el `sitemap.xml` | 01, 03 | 2 |
| **GR-17** | Falta el Área de servicio en la ficha del trámite | 03 | 2 |
| **GR-18** | Los canales de atención no permiten pedir cita | 06 | 1 |
| **GR-19** | Seis paquetes de Spatie están declarados y no los usa ningún fichero | 09, 12 | 1, 2 |
| **M-01** | El carrusel arranca solo | 01 | 2 |
| **M-02** | No hay encuesta de usabilidad ni criterio cuantitativo de «cumple» | 08 | 1 |
| **M-03** | No hay federación de datos abiertos a `datos.gov.co` | 11 | 2 |
| **M-04** | La sección de normativa no publica el enlace al SUIN | 02 | 2 |
| **M-05** | Los datos de la Entidad están duplicados en tres ficheros | 01 | 2 |
| **M-06** | El catálogo de tipos de PQRSD está duplicado | 04 | — |
| **M-07** | Dependencias declaradas y no usadas en el sitio y en el panel | Transversal | 3 |
| **M-08** | La galería de aplicaciones no está montada, y el ADR afirma que sí | 01 | 4 |
| **M-09** | Dos implementaciones de los controles de accesibilidad, con mecanismos y claves distintos | 07 | 4 |
| **M-10** | La página 404 ofrece una sola vía de navegación propia | 01 | — |
| **M-11** | `/servicios` y `/tramites` cubren el mismo objeto | 03 | 3 |
| **M-12** | El panel declara tokens «GOV.CO oficiales» que no lo son | 01 | 4 |
| **M-13** | El panel no puede publicar contenido: inventario de lo que falta | 12 | 2 |
| **m-01** | `BaseModal.vue` no gestiona el foco | 12 | 3 |
| **m-02** | `DataTable.vue` ordena con ratón y no con teclado | 12 | 3 |
| **m-03** | La paginación del panel está a 32×32 px | 12 | 3 |
| **m-04** | El `README.md` del panel es la plantilla de Vite sin adaptar | 12 | — |
| **m-05** | `/buscar` se anuncia en el `sitemap.xml` y la página pide no indexarse | 01, 08 | — |

### Por archivo — dónde hay que tocar

| Archivo | Hallazgos |
|---|---|
| `contract/openapi.yaml` | BL-01, BL-03, BL-07, GR-04, GR-09, GR-10, GR-18 |
| `backend/` (`composer.json`, `Models/`, `Policies/`, `routes/`) | BL-01, BL-02, BL-04, BL-08, GR-01, GR-19 |
| `panel/src/router/index.ts` | GR-01, M-13 |
| `panel/src/views/admin/InicioView.vue` | GR-03, M-13 |
| `panel/src/views/admin/EnConstruccionView.vue` | BL-08, M-13 |
| `panel/src/views/acceso/EntrarView.vue` | GR-02, BL-04 |
| `panel/src/layouts/AdminLayout.vue` | GR-02 |
| `panel/src/services/http.ts`, `types/api.ts`, `main.ts` | GR-04 |
| `panel/src/assets/styles/tokens.css`, `main.css` | M-09, M-12 |
| `panel/src/components/base/BaseModal.vue` | m-01 |
| `panel/src/components/base/DataTable.vue` | m-02, m-03 |
| `panel/README.md` | m-04 |
| `sitio/app/layouts/default.vue` | GR-02 |
| `sitio/app/pages/realizar-una-peticion.vue` | BL-01, BL-02, BL-04, M-06 |
| `sitio/app/pages/seguimiento.vue` | BL-03, GR-11 |
| `sitio/app/pages/transparencia.vue` | BL-09, GR-11 |
| `sitio/app/pages/politicas/[slug].vue` | BL-06, §7.2 #8 |
| `sitio/app/pages/buscar.vue` | BL-07, m-05 |
| `sitio/app/pages/tramites/[slug].vue` | GR-10, GR-15, GR-17 |
| `sitio/app/pages/tramites/index.vue` | GR-14 (paginación) |
| `sitio/app/pages/normativa.vue` | M-04 |
| `sitio/app/pages/atencion.vue` | GR-18, M-05 |
| `sitio/app/pages/pqrsd.vue` | M-06, §6.2 (tabla mal anidada) |
| `sitio/app/pages/servicios.vue`, `portales.vue`, `noticias.vue` | GR-11, M-11 |
| `sitio/app/components/govco/BuscadorGovco.vue` | BL-07 |
| `sitio/app/components/govco/CarruselGovco.vue` | M-01 |
| `sitio/app/components/govco/BarraSuperior.vue` + `sitio.css` | GR-13 |
| `sitio/app/components/govco/GaleriaAplicacionesGovco.vue` | M-08 |
| `sitio/app/assets/css/sitio.css` | GR-12, GR-13 |
| `sitio/server/routes/sitemap.xml.ts` | GR-11, GR-16, m-05 |
| `sitio/package.json` | M-07, GR-07 |
| `panel/package.json` | M-07, GR-07 |
| `docker/nginx/conf.d/default.conf` | GR-05, M-13 |
| `docs/trazabilidad.md` | BL-10 |
| `docs/adr/README.md` | GR-05, GR-06, GR-14, GR-15, M-08, M-12, §7.1 |
| `docs/Sección 1, 4, 5, 6` | §6.4, §7.3 |
| Corpus (`01`/`02`/`03`/`04`/`07`/`12`, `_global/`) | BL-09, GR-08, GR-09, GR-14, GR-17, §6.4 |

---

### Cierre

Dos frases que resumen el estado, y conviene que no se pierdan entre 47 hallazgos:

**Lo que está bien, está muy bien, y está documentado en el propio código con una disciplina que no
he visto en ningún otro punto del proyecto.** Las 25 piezas de la sección 9 no son cumplidos: son
trabajo que una corrección apresurada puede destruir, y varias de ellas —la abstención de declarar
conformidad, la negativa a inventar fechas, la publicación de la procedencia, el contraste medido y
no elegido— son la razón por la que esta auditoría ha podido ser precisa.

**Lo que falta, falta entero.** No hay secciones a medio construir por descuido: de los 206 requisitos con enunciado, 22 tienen
artefacto que cumple su criterio, 45 tienen parte, y 139 no tienen nada. Y la más urgente no es ninguna de las catorce: es
**GR-01**, porque hoy hay una superficie de administración accesible desde Internet que anuncia un
doble factor inexistente; y la segunda es **BL-10**, porque mientras el proyecto crea que cumple el
87 %, cada decisión que se tome sobre esa cifra será una decisión equivocada.

---

*Auditoría de diseño y de ingeniería de requisitos. Las afirmaciones sobre el código se comprobaron
con `read`, `grep` y `ls` y llevan archivo y línea. Las exigencias citan su norma y su numeral. Lo
deducido va marcado `[DEDUCCIÓN]`; lo no comprobado, `[SIN VERIFICAR]`.*

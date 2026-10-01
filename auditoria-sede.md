# Auditoría del diseño de la Sede Electrónica

**Titular:** Alcaldía Distrital de Santa Marta · NIT 891.780.009-4
**Objeto:** el diseño construido —`sitio/` (portal público, Nuxt 4) y `panel/` (superficie
administrativa, Vue 3 + Tailwind)— contrastado contra la línea base de requisitos
(`sede-electronica-doc/`, corpus de elicitación), el expediente normativo (`docs/`), el Kit UI
GOV.CO v9.2 (`vendor-src/layout-govco-v5/` y `sitio/public/govco/`) y el contrato
(`contract/openapi.yaml`).
**Naturaleza:** auditoría de diseño y de ingeniería de requisitos. **Señala lo que falta y lo que
está mal; no reescribe nada.** Cada afirmación lleva evidencia `fichero:línea` verificada.
**Edición:** tercera. Sustituye a la segunda (2 365 líneas, en el historial de Git:
`git show d728457:auditoria-sede.md`). La sección 0 detalla qué cambia.

---

## 0. Qué cambia esta edición y por qué

### 0.1 La línea base cambió de sitio

La segunda edición abría con «el corpus de requisitos no está en el repositorio». **Hoy sí está**:
`sede-electronica-doc/` contiene los 12 módulos de elicitación, 7 consolidados globales y el
paquete de base de datos. Eso permite algo que antes no se podía hacer: **contrastar requisito
contra código, uno por uno, con el identificador del propio corpus**. Es lo que hace la sección 4.

La segunda edición auditaba, además, `backend/` y `contract/` como objetos propios. Esta edición
los usa **sólo como fuente** para juzgar el diseño: cuando el contrato declara un recurso que la
interfaz no consume, eso es un hallazgo de diseño; cómo esté implementado el controlador, no.

### 0.2 Hallazgos nuevos que esta edición establece por primera vez

| # | Hallazgo | Sección |
|---|---|---|
| 1 | **La matriz de trazabilidad del proyecto acredita artefactos que no existen** (`frontend/tests/*.mjs`, `views/publico/*.vue`, `GET /menus/{ubicacion}`). Su «122 de 140 (87 %)» no es verificable. | D-06, §3.3 |
| 2 | **Las puertas `make diseno`, `make accesibilidad`, `make unidad`, `make imagenes` y `make respaldo` apuntan a ficheros inexistentes.** `make comprobar` no puede terminar en verde. | D-06 |
| 3 | **`make tipos` escribe un fichero que nadie importa** (`sitio/types/api.d.ts` frente a `~~/types/openapi`). | D-29 |
| 4 | **El panel no tiene guardias de navegación**, aunque declara `requiereSesion`, `permiso` y `soloInvitados` en cada ruta. | D-04 |
| 5 | **El panel publica cifras inventadas** (287 PQRSD, 94 % de ITA, «Sistema operativo», «Sesión activa») exactamente donde RF-B1-078 las prohíbe. | D-05 |
| 6 | **ADR-0015 ha caducado**: declara «la sede no publica tablas» y `pqrsd.vue:222` publica una. CAG-24 vuelve a aplicar. | D-20 |
| 7 | **La barra de accesibilidad no persiste la preferencia** pese a que ADR-0012 la compromete a `localStorage`, y **no existe el enlace al Centro de Relevo** que RF-B1-044 exige. | D-11 |
| 8 | **El buscador existe y no busca** —ni índice, ni sugerencias, ni resultados—; la propia página lo admite. | D-03 |
| 9 | **No existe banner de cookies ni aviso de salida a sitio externo**, los dos RF Must que la segunda edición ya señalaba: siguen sin construirse. | D-01, D-02 |
| 10 | **El expediente cita mal los ítems de los PDF oficiales** (Sección 5 entera desplazada; Sección 4 con los pares 3↔4 y 7↔8 invertidos) pese a declararse reproducción textual. | C-09 |
| 11 | **ADR-0015 omite CAG-06 apoyándose en «castellano únicamente», pero FUN-010 y la GUIA Maestra exigen «enlaces de traducción» en la barra superior.** | C-08 |
| 12 | **ADR-0015 declara «no aplica» cinco criterios cuyos componentes §2.5 del propio expediente marca como obligatorios** en Trámites, Detalle y Notificaciones. | C-06 |
| 13 | **No hay Declaración de Conformidad de Accesibilidad publicada** con los 12 elementos del Anexo 1 num. 9.3 de la Resolución 1519. `/accesibilidad` declara la norma y el canal, y dice expresamente que no acredita conformidad. | D-23 |

### 0.3 Hallazgos de la segunda edición que se confirmaron íntegros

Se volvieron a comprobar uno por uno con `grep` y lectura directa sobre el árbol actual:
B-01 (cookies), B-02 (captcha), M-04 (carrusel con `autoplay: true`), G-01 (sin pruebas de
accesibilidad), G-02 (componentes del Kit ausentes), G-06 (dependencias declaradas y no usadas),
G-12 (sin `Policies`). Todos siguen siendo ciertos y se reformulan aquí con su identificador del
corpus.

---

## 1. Alcance, método y fuentes

### 1.1 Qué se auditó

| Superficie | Ficheros | Líneas | Cobertura |
|---|---|---|---|
| `sitio/` (portal público) | 20 páginas, 12 componentes, 1 composable, 1 layout, 1 ruta de servidor, 2 hojas de estilo, `nuxt.config.ts` | 9 545 | **100 %** |
| `panel/` (superficie administrativa) | 22 componentes y vistas, 1 router, 1 layout, 1 cliente HTTP, 3 hojas de estilo, 4 ficheros de configuración | 2 887 | **100 %** |
| Línea base de requisitos | `sede-electronica-doc/` — 54 ficheros, 2,1 MB; **303 filas de requisito (156 RF · 53 RNF · 94 RN), 368 identificadores** | 12 386 | Catálogo completo de los módulos 01, 07, 08, 12 y de los tres ficheros globales |
| Expediente normativo | `docs/` — Secciones 1-6, 15 ADR, `trazabilidad.md`, `transparencia.md`, los 3 PDF de criterios de aceptación | ~7 000 | Criterios verificables de diseño, accesibilidad e implementación; los 34 CAG; las 32 casillas de §2.6 |
| Kit UI GOV.CO | `vendor-src/layout-govco-v5/` + la copia vendorizada en `sitio/public/govco/` | — | Componentes usados, ausentes y clases canónicas |

### 1.2 Cómo se cruzaron las cinco fuentes

```
sede-electronica-doc/  ──┐
 (qué se exige, RF/RNF/RN)│
                          ├──►  matriz de cumplimiento  ──►  registro de hallazgos
docs/ (cómo se acepta) ───┤      (sección 4)                 (sección 5)
vendor-src/ (cómo se ve) ─┤
sitio/ + panel/ (qué hay) ┘
contract/openapi.yaml ────┘   (qué se puede alimentar de datos)
```

La regla de decisión es única: **un requisito se marca «cumple» sólo si existe un artefacto
concreto, en una línea concreta, que satisfaga su criterio de aceptación**; si el criterio exige
medición en navegador y no hay instrumento, se marca «no verificable» en lugar de suponerlo.

### 1.3 Universo auditado y denominadores declarados

No todos los requisitos del corpus son auditables contra el diseño. Se declara el denominador
para que las cifras de la sección 2 puedan recalcularse:

- **Denominador RF = 152.** Los 119 RF de los módulos de diseño (01 → 44, 07 → 37, 08 → 23,
  12 → 15) más **33 RF de otros módulos con efecto directo en la interfaz** (02, 03, 04, 06, 09,
  11 y los deltas con consecuencia visual). Los RF de integración pura (10), infraestructura e
  interoperabilidad no entran: no tienen criterio verificable en la capa de presentación.
- **Denominador RNF = 31.** Los RNF de los cuatro módulos de diseño (01 → 6, 07 → 9, 08 → 10,
  12 → 6).
- **Denominador RN = 94.** Se auditan como **restricciones de diseño** sólo las que imponen una
  forma a la interfaz (`RN-01-D02` tope de menú, `RN-01-D03` lista blanca, `RN-07-D02` captcha
  accesible, `RN-TX-D01` calendario único). El resto son reglas de proceso y de dato sin huella
  verificable en el diseño.

### 1.4 Qué quedó fuera, y por qué

| Materia | Motivo |
|---|---|
| Implementación del backend, migraciones, esquema | Otra auditoría. Aquí sólo se usa el contrato como fuente de lo que la interfaz podría consumir. |
| Diseño de la base de datos (`sede-electronica-doc/_bd/`, ~149 tablas) | Es diseño de datos, no de sede electrónica. Se cita cuando explica un hueco de la interfaz (p. ej. la ausencia de expediente explica `/seguimiento`). |
| Seguridad de infraestructura (TLS, cabeceras, puertos, WAF) | Sección 4 del expediente; no es superficie de diseño. Excepción: los RF de seguridad con huella en la interfaz (captcha, login, control de acceso) entran. |
| Rendimiento medido, accesibilidad medida, contraste medido | Sin navegador en ejecución esta auditoría **no puede** medirlos. Se marca ⛔ en lugar de inventarlos. |

### 1.5 Límites: lo que esta auditoría no puede afirmar

Se declaran para que no se lean como cumplimientos lo que son ausencias de prueba:

1. **No se ejecutó ningún navegador, rastreador ni auditor automático.** Ningún hallazgo se apoya
   en una medición en vivo; todos se apoyan en lectura de código y en búsqueda reproducible.
2. **Los criterios que exigen medición** (contraste efectivo, reflujo a 320 px, FCP, posición en
   Google, validez W3C, navegación con NVDA/JAWS/VoiceOver, SUS) se marcan ⛔ **no verificable**,
   no «cumple».
3. **Lo que el código documenta no se da por cierto.** Varios comentarios del proyecto afirman
   mediciones (contrastes de 8,46:1, desbordes de 7 px, «medido y no supuesto»). Se citan como
   *alegaciones del autor*; cuando contradicen al código, se dice (D-45).
4. **No se audita la corrección jurídica del contenido.** Se audita si el diseño permite publicar
   lo que la norma exige, no si el texto publicado es jurídicamente exacto.

### 1.6 Convenciones

| Estado | Significado |
|---|---|
| ✅ **CUMPLE** | Existe artefacto concreto que satisface el criterio, con evidencia `fichero:línea`. |
| 🟡 **PARCIAL** | Una parte del criterio se satisface y otra no; se dice cuál. |
| ❌ **NO CUMPLE** | No existe artefacto que lo satisfaga, o existe uno que lo contradice. |
| ⛔ **NO VERIFICABLE** | El criterio exige medición o acto humano y no hay instrumento en el repositorio. |
| ➖ **NO APLICA / DIFERIDO** | Justificado con la fuente de la decisión (no se usa para esconder incumplimientos). |

Gravedad: 🔴 **bloqueante** (incumple un Must de rango legal, o afirma al ciudadano algo falso) ·
🟠 **grave** (incumple un Must funcional o de accesibilidad) · 🟡 **medio** (incumple un Should, o
degrada la verificabilidad) · ⚪ **menor** (pulido).

---

## 2. Veredicto

**El armazón del sitio público es de calidad profesional alta y está por delante de su propia
documentación; el resto del producto no está construido, y la documentación de conformidad
afirma que sí lo está.**

En una frase: **se auditó un esqueleto excelente al que le faltan los órganos, y un expediente de
aceptación que se declara cumplido con pruebas que no existen.**

Lo que sostiene el veredicto, con las cifras:

| Medición | Resultado |
|---|---|
| RF auditados | **152** |
| ✅ Cumplen | **32** (21 %) |
| 🟡 Parciales | **32** (21 %) |
| ❌ No cumplen | **68** (45 %) |
| ⛔ No verificables | **8** (5 %) |
| ➖ No aplican o diferidos | **12** (8 %) |
| **Cumple o cumple parcialmente** | **64 de 152 (42 %)** |
| RNF auditados | **31** — ✅ 5 · 🟡 7 · ❌ 8 · ⛔ 8 · ➖ 3 |
| Módulo 12 (CMS y administración) | **0 de 15 RF**; el panel son 18 marcadores sobre 19 rutas |
| Hallazgos | **52** — 🔴 6 · 🟠 17 · 🟡 20 · ⚪ 9 |
| Puertas de calidad que hoy pueden pasar | **0 de 8** (`make comprobar` no termina) |

**Dónde está lo bueno, y es real:** la carcasa (barra superior, cabecera, menú de 7 ítems con
megamenú navegable por teclado, miga de pan derivada de la ruta, pie con datos completos, volver
arriba, salto al contenido), el carrusel reescrito sin solapar controles, el modo de alto contraste
propio —porque el del Kit es un stub—, el reflujo medido a 320 px, y una disciplina de honestidad
editorial poco común: **el sitio prefiere no publicar antes que publicar relleno**. Eso último es
la razón por la que 68 RF aparecen como «no cumple» sin que ello signifique chapuza: significa
que falta contenido, contrato y CMS, y que el equipo se negó a fingirlos.

**Dónde está lo grave:** en las cuatro cosas que un ciudadano usa de verdad —**buscar** (D-03),
**consentir cookies** (D-01), **salir a un sitio externo avisado** (D-02) y **autenticarse**
(D-04)— y en que el sistema de control del proyecto (trazabilidad, puertas, ADR) **ya no describe
el repositorio**: acredita conformidad con pruebas de un árbol de carpetas que no existe (D-06).
Un expediente que miente sobre su propio cumplimiento es un riesgo mayor que una sección vacía,
porque la sección vacía se ve.

---

## 3. Reconciliación de la línea base

Antes de juzgar el diseño hay que decidir **qué documento manda**. Hoy conviven cuatro capas con
jerarquías distintas y contradicciones entre ellas; sin esta reconciliación, cualquier hallazgo es
discutible.

### 3.1 Qué es cada capa, y cuál prevalece

| Capa | Qué es | Autoridad | Estado |
|---|---|---|---|
| `sede-electronica-doc/` | Corpus de elicitación: 156 RF, 53 RNF, 94 RN con criterio G/W/T y cita de fuente normativa | **Línea base de requisitos** | Vigente. Es la capa con la que se audita en la sección 4. |
| `docs/` | Expediente normativo y de aceptación: Secciones 1-6, 34 CAG, 15 ADR, matriz de trazabilidad | **Línea base de aceptación** | Vigente en los criterios; **caducado en sus evidencias** (§3.3). |
| `vendor-src/layout-govco-v5/` + `sitio/public/govco/` | Kit UI GOV.CO v9.2 (rama `v5`) | **Fuente de verdad gráfica** | Vigente. ADR-0002 corrige la URL que el expediente cita. |
| `contract/openapi.yaml` | Contrato de la API | **Fuente única del intercambio** | Vigente pero **cubre 3 recursos**: `/entidad`, `/tramites`, `/tramites/{slug}`. |

### 3.2 El módulo 01 está duplicado byte a byte

`sede-electronica-doc/01-estructura-identidad/sede.md` y
`sede-electronica-doc/01-estructura-identidad/estructura-identidad.md` **son el mismo fichero**:
24 779 bytes cada uno, contenido idéntico byte a byte. Dos nombres para el mismo documento invitan
a que se edite uno y se audite el otro. **Acción:** conservar uno y eliminar el otro, o sustituirlo
por un enlace.

### 3.3 La matriz de trazabilidad del proyecto no traza

`docs/trazabilidad.md` (232 líneas) se declara «documento generado» por `npm run trazabilidad` y
acredita **122 de 140 criterios (87 %)**. Se comprobó cada ruta de evidencia que cita:

| Evidencia citada por la matriz | ¿Existe? |
|---|---|
| `frontend/tests/conformidad-diseno.mjs`, `conformidad-shell.mjs`, `accesibilidad.mjs`, `flujo-pqrsd.mjs` | **No.** El repositorio no tiene `frontend/`; tiene `sitio/` y `panel/`. Y ninguno de los dos tiene `tests/`. |
| `views/publico/InicioView.vue`, `views/publico/TransparenciaView.vue`, `layouts/LayoutPublico.vue`, `components/govco/MenuNavegacion.vue`, `SaltarAlContenido.vue`, `MigaDePan.vue`, `CabeceraEntidad.vue` | **No.** Son rutas de una SPA `frontend/src/`, no del Nuxt `sitio/app/`. |
| `backend/tests/Feature/Sede/ConformidadSedeTest.php`, `backend/tests/Feature/Api/CatalogoApiTest.php` | **No.** `backend/tests/Feature/Sede/` sólo contiene `.gitkeep`; el único test de API es `Api/V1/TramiteTest.php`. |
| `scripts/verificar-imagenes.mjs`, `scripts/verificar-infra.mjs` | **No.** No existe `scripts/`. |
| `GET /menus/{ubicacion}`, `GET /sedes` | **No.** El contrato declara tres rutas y ninguna es ésas. |
| `npm run trazabilidad`, `make trazabilidad`, `frontend/tests/captura-diseno.mjs` | **No.** Ni el script ni el objetivo existen en el `Makefile`. |

**Consecuencia:** los 34 CAG, ACC-002 y buena parte de FUN-00x figuran como «cubierto por pruebas
o interfaz» con pruebas que no existen en este repositorio. La cifra del 87 % no es falsa por
error de cálculo: es **no verificable**, y en un expediente de aceptación eso equivale a una
afirmación sin respaldo. **Acción:** regenerar la matriz contra el árbol real o retirarla del
expediente mientras no lo describa (D-06).

### 3.4 Cómo leer los identificadores del corpus

El corpus asigna **hasta tres identificadores al mismo requisito** (series `B1/B2/B3`, por bundle
de origen). Por eso una fila se titula `RF-B1-003 / RF-B2-007 / RF-B3-072`: son el mismo requisito
visto en tres extracciones. La convención es intencional y está documentada en el `README.md` del
corpus.

**Pero 17 identificadores colisionan de verdad** —el mismo ID con enunciados distintos en módulos
distintos— y esa colisión es un defecto, no una convención. Las más dañinas para el diseño:

| ID | Enunciado A | Enunciado B | Riesgo |
|---|---|---|---|
| `RNF-B1-014` | WCAG 2.1 AA, 52 criterios (`07:94`) | Sincronización con la Hora Legal Colombiana (`12:61`) | **Grave**: dos dominios incompatibles bajo el mismo ID. Una puerta de accesibilidad que cite `RNF-B1-014` podría estar acreditando la hora legal. |
| `RF-B1-076` | Encabezados semánticos y logo a inicio (`08:46`) | CMS con roles y log de auditoría (`12:19`) | Grave: el mismo ID gobierna el `<h1>` y el CMS. |
| `RF-B1-082` | Textos de enlace + vínculos visitados (`07:66`) | Sólo vínculos visitados (`08:21`) | Medio. |
| `RF-B3-040` | Doble pila IPv4/IPv6 (`01:86`) | Confirmación antes de envío sensible (`07:65`) | Medio. |
| `RF-B3-045`, `RF-B3-046` | Identidad visual del Kit (`01:23`) | Subtítulos y LSC (`07:28`) / UTF-8 (`07:31`) | Medio. |
| `RF-B1-047`, `RF-B1-048` | Enunciados distintos bajo el mismo ID **dentro del mismo fichero** (`07:37-38`, `07:49-51`) | — | Medio. |
| `RF-B3-143` | Formularios con validación (`08:33`) | HTML/CSS válido W3C (`08:45`) | Medio. |
| `RN-B2-029` | Kit obligatorio en trámites (`01:122`) | HTML/CSS válido W3C (`08:86`, con la anotación espuria «(fase2)» dentro de la celda del ID) | Medio. |

Además, `RN-B2-029 (fase2)` y `RN-B3-037 (apoyo)` llevan anotaciones **dentro de la celda del
identificador**, y `RNF-07-D01` / `RNF-08-D01` aparecen bajo el encabezado de tabla de los RF
(`ID propuesto | Enunciado | Procedencia | Criterio | MoSCoW`) en vez del de los RNF
(`ID | Categoría | Umbral | Procedencia`). **Acción:** congelar los identificadores antes de
usarlos en puertas automáticas; hoy una puerta que cite `RNF-B1-014` no se sabe qué acredita.

### 3.5 Un requisito interno contradictorio que el diseño tuvo que resolver solo

`RF-B1-001` (Must) exige la barra superior «con logo enlazado… **y opción de traducción a la
derecha**». `RF-B3-055` (Could) dice que el botón de idioma se **difiere**. El corpus nunca
resolvió la contradicción; el diseño la resolvió por su cuenta, omitiendo el botón
(`BarraSuperior.vue:8-11`) y dejando la justificación en un comentario. Es la decisión correcta,
pero hoy descansa en un ADR que se apoya en otra fuente (C-08) y no en el corpus.

---

## 4. Matriz de cumplimiento, requisito por requisito

**Cómo leerla.** Una fila por requisito del corpus, con su estado (✅ 🟡 ❌ ⛔ ➖), la evidencia
—`fichero:línea`— y, cuando no cumple, **qué falta exactamente**. Las referencias `D-nn` llevan al
hallazgo correspondiente de la sección 5, donde está el detalle y la corrección propuesta.

### 4.1 Módulo 01 — Estructura e identidad GOV.CO (44 RF)

| ID | Requisito | Prio | Estado | Evidencia / qué falta |
|---|---|---|---|---|
| RF-B1-001 | Top bar GOV.CO con logo enlazado, en todas las páginas | Must | ✅ | `BarraSuperior.vue:16-23`, montada en `layouts/default.vue:155`. La «opción de traducción» que enuncia este RF está diferida por RF-B3-055 (ver C-22). |
| RF-B1-002 | Footer con los 13 elementos de identidad y contacto | Must | ✅ | `PiePaginaGovco.vue:150-199` y `:281-358`: autoridad, dirección, CP, horario, conmutador +57, línea gratuita 018000, anticorrupción, dos correos, redes, mapa, políticas. **Pero los datos están incrustados**, no vienen del contrato (D-18). |
| RF-B2-006 | Teléfonos con +57 salvo 018000/019000 | Must | ✅ | `PiePaginaGovco.vue:157,162` frente a `:158`. |
| RF-B1-043 | Logo de la Alcaldía arriba a la izquierda, enlazado a inicio | Must | ✅ | `CabeceraGovco.vue:79-81` (`NuxtLink to="/"`), altura 48 px / 40 px en móvil (`:163-173`). |
| RF-B1-098 | Identidad visual del Kit: Nunito Sans + Verdana, Cobalt `#0943B5` | Must | 🟡 | **El sitio cumple** (Kit vendorizado, `nuxt.config.ts:71`); **el panel no**: `tokens.css:2-3` declara `--color-gov-blue: #3366CC` y `fonts.css` carga Inter, Montserrat y JetBrains Mono (D-14). |
| RF-B3-055 | Botón de idioma persistente | Could | ➖ | Diferido por decisión #16 del corpus (`01:24`); el diseño lo omite a propósito (`BarraSuperior.vue:8-11`). Falta la decisión sobre lenguas étnicas (D-35). |
| RF-B3-061 | Botón «Volver arriba» en páginas largas | Should | ✅ | `VolverArriba.vue:14-40`; aparece al 75 % de la altura (`:19`) y respeta `prefers-reduced-motion` (`:23-24`). |
| RF-B1-003 | Menú con los 4 ítems obligatorios en orden, máx. 7, máx. 2 niveles, `aria-label` | Must | ✅ | `layouts/default.vue:46-94` (Inicio → Transparencia → Atención y Servicios → Participa → PQRSD → Normativa → Noticias = 7); `MenuNavegacionGovco.vue:556` (`aria-label`). El tope se recorta *en el cliente* (D-31). |
| RF-B1-004 | Menú responsive en hamburguesa por debajo de 768 px | Must | ✅ | `MenuNavegacionGovco.vue:565-577` y `:786-789`. |
| RF-B1-005 | Buscador interno visible en la cabecera de todas las páginas | Must | 🟡 | Está en todas las páginas (`layouts/default.vue:167-170`), pero **no busca**: `buscar.vue:44-49` reconoce que no hay índice (D-03). Tampoco hay buscador dentro de Transparencia (RF de Sección 5 §5.3.1 nº 6 del PDF). |
| RF-B1-006 | Autocompletado ≤10 sugerencias, tolerancia a errores, resultados con metadatos | Must/Should | ❌ | `BuscadorGovco.vue:5-8` documenta la omisión del buscador predictivo; no hay endpoint en el contrato (D-03). |
| RF-B1-010 | Mapa del sitio autoactualizado + `sitemap.xml` | Must | 🟡 | Los dos existen (`mapa-del-sitio.vue:33-115`, `sitemap.xml.ts:35-70`) pero **ninguno se deriva de la navegación**: son tres listas paralelas escritas a mano (D-23). |
| RF-B2-038 | Migas de pan en todas las páginas internas, no en la portada | Must | ✅ | `layouts/default.vue:132-150` y `MigaDePanGovco.vue:35,44-65`; `aria-current="page"` en el último nivel (`:61`). |
| RF-B2-041 | Cero vínculos rotos | Must | ⛔ | Sin rastreador (`W3C Link Checker`) no se puede acreditar. Nota: los seis stubs responden 200 con aviso, no 404, así que no son «rotos»; las políticas del pie sí apuntan a rutas reales (`PiePaginaGovco.vue:211-234`). |
| RF-B1-011 | Módulo de noticias en la portada (imagen 4:3 o 16:9, título ≤150, descripción ≤200, fecha, orden inverso) | Must | ❌ | La portada publica carrusel de secciones y tres tarjetas, **no noticias** (`index.vue:43-90`); `/noticias` es un aviso de «en preparación» (D-07). |
| RF-B1-042 | Carrusel con indicadores, Play/Stop, flechas y **pausa por defecto** | Must | 🟡 | Controles e indicadores ✅ (`CarruselGovco.vue:240-300`); **arranca reproduciendo** (`:98` y `:200`), contra «pausa por defecto» (D-12). |
| RF-B1-007 | 404 personalizada con ≥3 opciones de navegación | Must | 🟡 | 404 real y con estado correcto (`error.vue:25,39-45`, `[...ruta].vue:20-26`); ofrece **una** salida explícita (`error.vue:54-56`): el menú y el buscador del layout cuentan como vías, pero faltan las «secciones populares» (D-09). |
| RF-B1-071 | Aviso de salida a sitio externo con confirmación | Must/Should | ❌ | No existe. Los enlaces externos abren en pestaña nueva sin aviso: `BarraSuperior.vue:17-22`, `PiePaginaGovco.vue:321-332` (D-02). |
| RF-B1-008 | Banner de cookies con aceptar/rechazar/configurar y revocación | Must | ❌ | No existe ninguna implementación (D-01). |
| RF-B1-009 | Publicar las cinco políticas, descargables en formato abierto | Must | ❌ | Las cinco rutas existen y **no publican documento**: aviso de «en preparación» (`politicas/[slug].vue:104-117`) (D-10). |
| RF-B2-008 | Términos y condiciones con 6 componentes mínimos | Must | ❌ | Sólo el propósito declarado (`politicas/[slug].vue:33-37`). |
| RF-B2-009 | Política de privacidad conforme Ley 1581/2012 y 1712/2014 | Must | ❌ | Sólo el propósito (`:43-47`). |
| RF-B2-010 | Política de derechos de autor | Must | ❌ | Sólo el propósito (`:53-57`). |
| RF-B2-084 | Componentes del Kit UI y lectura «Área de Servicio antes del Trámite» | Must | 🟡 | Grilla y componentes base ✅; el «área de servicio» del Kit no existe en el sitio (D-27). |
| RF-B3-060 | Grilla Bootstrap 5.0, 12 columnas, 6 breakpoints, espaciado ≥24 px | Must | ✅ | `nuxt.config.ts:61` carga Bootstrap 5.0.2 antes del Kit, con la justificación de por qué es imprescindible. |
| RF-B3-062 | Acordeón con `aria-expanded`, sin apertura al foco | Must | ❌ | No implementado (D-27). Lo exige además §2.5 para Trámites y Detalle (C-06). |
| RF-B3-063 | Alerta modal con cierre por ESC y clic exterior | Must | ❌ | No implementado en el sitio (D-27). **Y es la pieza que RF-B1-071 necesita**, así que «CAG-21 no aplica» (ADR-0015 §3) es falso mientras RF-B1-071 siga siendo Must (C-06). |
| RF-B3-064 | Toast con tiempo de lectura suficiente | Must | ❌ | No implementado (D-27). |
| RF-B3-066 | Botones con estados y `aria-label` | Must | ✅ | Kit `btn-govco`/`fill-btn-govco` más `.btn-outline-primary` reapuntado al cobalto (`sitio.css:343-352`). |
| RF-B3-068 | Galería de aplicaciones navegable con Enter/Esc | Should | ❌ | El componente **existe y nunca se instancia**: `GaleriaAplicacionesGovco.vue` (354 líneas) no aparece en ninguna página (D-27). |
| RF-B3-070 | Indicador de carga; aviso si el proceso supera 10 s | Must | 🟡 | Hay estado de carga en el catálogo (`tramites/index.vue`), pero no hay aviso de progreso a los 10 s (D-27). |
| RF-B3-075 | Paginación con `aria-current` y ≥44 px en móvil | Must | 🟡 | `tramites/index.vue:782-805` con `aria-current`; el área táctil de 44 px se resuelve globalmente (`sitio.css:396-414`), sin verificación móvil (⛔ medición). |
| RF-B3-076 | Tablas con ordenamiento asc/desc | Must | ❌ | La única tabla pública no ordena (`pqrsd.vue:222-245`), y CAG-24 vuelve a aplicar (D-20). |
| RF-B1-070 | Proceso de integración a GOV.CO en 7 pasos | Must | ➖ | Proceso administrativo, sin huella verificable en el diseño. |
| RF-B1-100 | Plan de Integración publicado e incorporado al PETI | Must | ➖ | Documento de gestión. |
| RF-B2-001 | Redireccionamiento con enmascaramiento de URL (proxy MinTIC) | Must | ➖ | Depende de MinTIC; el diseño no puede acreditarlo. |
| RF-B2-002 | Integrar **todos** los portales y apps de la Alcaldía | Must | ❌ | `/portales` es un aviso de «en preparación» (`portales.vue`); no hay inventario (D-17). |
| RF-B2-003 | Portales transversales integrados en ≤6 meses | Must | ❌ | Sin inventario ni portal integrado (D-17). |
| RF-B3-126 | Ficha en GOV.CO con los 4 momentos | Must | 🟡 | `tramites/[slug].vue` publica los seis atributos de la Guía §5.1.3, pero **no declara los cuatro momentos de GOV.CO** (`:5-18`). |
| RF-B3-127 | Estados estandarizados (registrada → recibida → en trámite → resuelta) | Must | ❌ | No hay ninguna superficie pública que muestre estados: `/seguimiento` es un aviso (D-17). |
| RF-B1-099 | Doble pila IPv4 + IPv6 | Must | ➖ | Infraestructura. |
| RF-01-D01 | Consentimiento de cookies versionado y con caducidad ≤12 meses | Must | ❌ | Depende de RF-B1-008, que no existe (D-01). |
| RF-01-D02 | CRUD de menú y noticias desde el CMS, con tope 7/2 | Must | ❌ | No hay CMS (D-15); el menú vive en una constante (`layouts/default.vue:46-94`) y el tope sólo se aplica en el cliente (D-31). |
| RF-01-D03 | Lista blanca de dominios de confianza para el aviso de salida | Should | ❌ | Depende de RF-B1-071 (D-02). |

**RNF del módulo 01**

| ID | Umbral | Estado | Evidencia / brecha |
|---|---|---|---|
| RNF-B1-027 | Doble pila IPv4+IPv6 | ➖ | Infraestructura. |
| RNF-B1-033 | ≤3 navegadores sin diferencias funcionales | ⛔ | Sin matriz de pruebas multi-navegador. |
| RNF-B3-045 | 100 % de componentes con Cobalt y Nunito Sans/Verdana | 🟡 | Sitio ✅; panel ❌ (D-14). |
| RNF-B3-046 | Logo GOV.CO sin modificaciones | ✅ | Se usan las clases `govco-logo`/`govco-co` sin alterar el asset (`PiePaginaGovco.vue:350-352`). |
| RNF-B3-005 | Objetivos táctiles ≥44×44 px | 🟡 | Correcciones explícitas en `sitio.css:396-414` y `:430-465` para buscador, pie, botones y formularios; sin medición en dispositivo (⛔). |
| RNF-01-D01 | Resiliencia ante caída del CDN: degradar a fuentes locales | ✅ | **Cumplido por eliminación del riesgo**: el Kit y Bootstrap se sirven desde el propio dominio (`nuxt.config.ts:57-71`), no hay CDN de terceros del que degradar. |

### 4.2 Módulo 07 — Accesibilidad (37 RF)

| ID | Requisito | Prio | Estado | Evidencia / qué falta |
|---|---|---|---|---|
| RF-B1-044 | Barra de accesibilidad persistente con A/A+/A++, contraste, salto al contenido y **Centro de Relevo**; guarda la preferencia | Must | 🟡 | Contraste ✅ (`sitio.css:24-81`), salto ✅ (`CabeceraGovco.vue:66-68`), tamaño ✅ (`useAccesibilidad.ts:56-59`). **Faltan: la persistencia** (`:37` usa `useState`, no `localStorage`, contra ADR-0012) **y el enlace al Centro de Relevo**, ausente de todo `sitio/` (D-11). |
| RF-B3-022 | «Saltar al contenido principal» como primer elemento tabulable | Must | ✅ | El enlace se movió de la cabecera a la **primera posición de la disposición** (`layouts/default.vue`), antes de `<BarraSuperior />`, con sus estilos `sr-only sr-only-focusable`. **Medido con navegador real en tres páginas** (`/pqrsd`, `/`, `/tramites`): el primer tabulador es «Saltar al contenido principal» (§14.1). Antes era el octavo o noveno; fue el hallazgo **R-P4**. |
| RF-B1-045 | `alt` descriptivo (≤150 car.) o decorativo declarado | Must | ✅ | El `alt` es **obligatorio en el tipo** del carrusel (`CarruselGovco.vue:48-53`) con aviso en desarrollo si viene vacío (`:115-123`); pie y cabecera con `alt` real (`PiePaginaGovco.vue:295`, `CabeceraGovco.vue:80`). |
| RF-B1-046 | Subtítulos, transcripción, audiodescripción y LSC | Must | ➖ | No hay multimedia en la sede. **Cuando la haya, aplica RN-07-D01** (bloqueo en el CMS), que tampoco existe (D-15). |
| RF-B1-054 | Sin audio automático | Must | ➖ | No hay audio. |
| RF-B3-007 | El color no es el único medio de información | Must | ✅ | Indicadores del carrusel huecos/rellenos en vez de sólo opacidad (`CarruselGovco.vue:25-37`); errores de formulario con texto y `role="alert"` (`realizar-una-peticion.vue:427-472`). |
| RF-B3-046 | Codificación UTF-8 declarada | Must | ✅ | `nuxt.config.ts:40`. |
| RF-B1-047 | HTML semántico y jerarquía H1-H6 sin saltos | Must | ✅ | `header`/`nav`/`main`/`footer` reales (`layouts/default.vue:167-201`, `PiePaginaGovco.vue:281`); un solo `h1` por página; el pie usa `h2`/`h3` en vez de `h4`/`h5` **para no romper el orden** (`:32-37`, `:368-379`). |
| RF-B3-047 | Tablas y listas sólo para datos semánticos | Must | ✅ | La única tabla tiene `caption`, `scope="col"` y `scope="row"` (`pqrsd.vue:223-243`). |
| RF-B1-052 | `lang="es"` en `<html>` | Must | ✅ | `nuxt.config.ts:38`. |
| RF-B3-005 | Responsive en ambas orientaciones; 200 %/400 % sin pérdida | Must | 🟡 | Reflujo a 320 px tratado y **documentado con la causa** de cada desborde (`sitio.css:281-334`); la ampliación al 400 % no está medida (⛔). |
| RF-B3-011 | Sin imágenes de texto | Must | ✅ | No hay ninguna; los logos son SVG/PNG con `alt`. |
| RF-B3-014 | Espaciado de texto configurable y soportado | Must | ✅ | El panel lo tenía (`main.css:105-110`, `.a11y-spacing`) y **el sitio ya lo tiene**: preferencia `espaciado` en `useAccesibilidad` con botón en el bloque de accesibilidad del pie y la clase `.espaciado-govco`. Medido a 1 280 px y a 320 px: interlínea 2,00×, `letter-spacing` 0,12 em, `word-spacing` 0,16 em, **desborde horizontal 0 px** (§13.1). |
| RF-B3-035 | Mismo componente → mismo nombre accesible | Must | ⛔ | Exige recorrido manual comparado entre vistas. |
| RF-B1-048 | Todo operable sólo con teclado | Must | 🟡 | Sitio: menú con flechas, Inicio/Fin y Escape devolviendo el foco (`MenuNavegacionGovco.vue:399-503`); carrusel y buscador con controles nativos. **Panel: la tabla ordenable no es operable por teclado** (`DataTable.vue:60-79`) (D-16). |
| RF-B3-017 | Sin trampas de foco; entrada y salida de modales con teclado | Must | 🟡 | El sitio no abre modales; el menú cierra con Escape devolviendo el foco. **El panel sí abre diálogos sin atrapar ni restaurar el foco** (`BaseModal.vue:29-35`, `CommandPalette.vue:54`) (D-16). |
| RF-B1-048 | Orden de foco = DOM y foco visible con contraste ≥3:1 | Must | ⛔ | El sitio **no declara ningún estilo global de foco**: depende del Kit y de Bootstrap 5.0.2, que no usa `:focus-visible` (se introdujo en 5.2). Sin medición no puede afirmarse (D-16 bis). |
| RF-B3-018 | Atajos de una sola tecla desactivables | Should | 🟡 | El sitio no usa atajos; el panel usa `⌘K`/`Ctrl+K` (`CommandPalette.vue:27`) sin forma de desactivarlo (D-16). |
| RF-B3-019 | Límite de tiempo <20 h ajustable | Must | ➖ | No hay sesiones con límite en la parte pública. |
| RF-B1-051 | Controles de movimiento y **pausa por defecto** | Must | 🟡 | Respeta `prefers-reduced-motion` (`CarruselGovco.vue:195-200`, `VolverArriba.vue:23-24`) y ofrece Play/Stop; **no arranca en pausa** (D-12). |
| RF-B3-028 | Alternativa a gestos multidedo y a movimiento | Must/Should | ➖ | No hay gestos ni funciones por movimiento. |
| RF-B3-033 | El foco no provoca cambios de contexto | Must | ✅ | Los filtros del catálogo se aplican al elegir en un `<select>` con `@change` explícito (`tramites/index.vue:628-638`); no hay envío por foco. |
| RF-B1-049 | `<label>` asociada, instrucciones y ejemplo de formato | Must | 🟡 | Etiquetas y `for` ✅ (`realizar-una-peticion.vue:505-513`); los ejemplos de formato del RF no están en todos los campos (D-27 bis). |
| RF-B3-006 | `type` y `autocomplete` por propósito de campo | Must | 🟡 | `autocomplete` presente en la entrada del panel (`EntrarView.vue:31,39`) y en varios campos del PQRSD; no en todos (⛔ verificación de axe). |
| RF-B3-037 | Errores identificados en texto con sugerencia de corrección | Must | ✅ | `aria-invalid`, `aria-describedby`, `role="alert"` y foco al primer campo inválido (`realizar-una-peticion.vue:372,413-472`). |
| RF-B3-040 | Confirmación antes de envíos sensibles | Must | ➖ | No hay pagos ni envíos irreversibles (el formulario PQRSD no radica). |
| RF-B1-082 | Textos de enlace descriptivos y **vínculos visitados diferenciados** | Must/Should | 🟡 | Textos ✅ (nombres de destino reales); **visitados ❌**: no hay una sola regla `:visited` en el sitio (D-25). |
| RF-B1-053 | Múltiples vías de navegación al mismo contenido | Must | 🟡 | Menú ✅, miga ✅, mapa del sitio ✅, **buscador ❌** (D-03). |
| RF-B3-023 | `<title>` descriptivo en cada página | Must | ✅ | Las 19 páginas declaran `useHead({ title })`. |
| RF-B3-041 | HTML válido, IDs únicos, sin atributos duplicados | Must | ⛔ | Sin validador W3C ni prueba de marcado. |
| RF-B3-042 | Componentes personalizados con nombre, función y valor ARIA | Must | 🟡 | Sitio ✅: menú (`aria-expanded`/`aria-controls`/`aria-haspopup`, `MenuNavegacionGovco.vue:588-600`), carrusel (`aria-current`, `aria-live`), barra (`aria-pressed`, `BarraAccesibilidad.vue:45`). Panel ❌ (D-16). |
| RF-B3-043 | Mensajes de estado con `role="status"`/`aria-live` sin tomar el foco | Must | 🟡 | Presentes en PQRSD, normativa, trámites, verificación, políticas y secciones en preparación; **ausente en `/buscar`** (D-03). |
| RF-B3-030 | Etiqueta visual = nombre accesible | Must | ✅ | `aria-label` empieza por el texto visible, incluso en correos (`accesibilidad.vue:132-136`). |
| RF-B3-015 | Tooltips descartables con ESC | Should | ➖ | No hay tooltips. |
| RF-B3-044 | Mapa del sitio XML enlazado desde el pie, sin vínculos rotos | Must | 🟡 | Enlace en el pie (`PiePaginaGovco.vue:233`) y `sitemap.xml` servido (`sitemap.xml.ts:76-123`); la ausencia de vínculos rotos es ⛔. |
| RF-B3-049 | Sin justificado, 60-80 caracteres por línea, sin pop-ups | Should/Must | ✅ | Texto no justificado y sin pop-ups no solicitados. |
| RF-07-D01 | El CMS bloquea publicar multimedia sin subtítulos | Must | ❌ | No hay CMS (D-15). |

**RNF del módulo 07**

| ID | Umbral | Estado | Evidencia / brecha |
|---|---|---|---|
| RNF-B1-014 | WCAG 2.1 AA completo (52 criterios), 0 violaciones críticas o serias | ⛔ | **No hay ninguna ejecución de axe-core ni Lighthouse**: axe y `@axe-core/playwright` están instalados y sin usar (D-19). Es el RNF peor acreditado del proyecto: se declara cumplido en ADR-0015 sin instrumento (C-22). |
| RNF-B1-015 | NTC 5854 nivel AA | ⛔ | Sin evaluación. |
| RNF-B1-016 | Compatible con NVDA, JAWS y VoiceOver; ≥90 % de tareas críticas completables | ⛔ | Sin prueba con productos de apoyo. |
| RNF-B1-017 | Contraste ≥4.5:1 (normal) y ≥3:1 (grande y componentes) | 🟡 | Hay mediciones documentadas y correctas en los puntos que el equipo tocó (`PiePaginaGovco.vue:39-50`, `sitio.css:183-192`); **no hay medición exhaustiva** de todo el texto (`RNF-B1-014` la exige). |
| RNF-B1-018 | ≤3 destellos/s, respeta `prefers-reduced-motion`, movimiento ≤1/3 de pantalla | ✅ | `CarruselGovco.vue:161-200` y `VolverArriba.vue:23-24`. |
| RNF-B1-019 | El alto contraste altera todos los colores satisfactoriamente | ✅ | Modo propio con 21:1 de fondo y 15:1 en enlaces, incluido foco amarillo (`sitio.css:24-81`). El del Kit es un stub y así se documenta (`:12-15`). |
| RNF-B3-004 | Zoom 400 % sin doble scroll ni pérdida | ⛔ | Sin medición. La barra usa `zoom` sobre la raíz (`useAccesibilidad.ts:47-48`), mecanismo distinto del zoom de navegador que el RNF mide. |
| RNF-B3-005 | Objetivos táctiles ≥44×44 px | 🟡 | Ver RNF-B3-005 del módulo 01. |
| RNF-07-D01 | La barra de accesibilidad opera en tablet (768-992 px) | ❌ | **Incumplido por diseño**: `BarraAccesibilidad.vue:39` aplica `d-none d-lg-flex` y la oculta por debajo de 992 px. Es una contradicción entre el Kit (CAG-07) y este RNF sin resolver (C-25, D-21). |

### 4.3 Módulo 08 — Usabilidad (23 RF)

| ID | Requisito | Prio | Estado | Evidencia / qué falta |
|---|---|---|---|---|
| RF-B1-080 | Migas de pan con la sección actual marcada | Must | ✅ | Ver RF-B2-038. |
| RF-B1-081 | URLs limpias, jerárquicas, en castellano | Should/Must | ✅ | `/participa/control-ciudadano`, `/politicas/uso-de-cookies`, `/tramites/{slug}`: sin acentos, sin parámetros, jerárquicas. |
| RF-B1-082 | Vínculos visitados diferenciados | Must | ❌ | Sin `:visited` (D-25). **Es uno de los tres «tips» que el corpus recuperó del GIF de los PPTX** (`_global/auditoria-cobertura.md:64`), y sigue sin implementarse. |
| RF-B2-072 | Navegación global consistente | Must | ✅ | El menú sale de **una sola constante** (`layouts/default.vue:46-94`); no puede divergir entre páginas. |
| RF-B2-074 | El botón «atrás» nunca deja de funcionar | Must | ✅ | SPA con historial del navegador. Los `<a href>` internos recargan la página pero no rompen el historial (D-22). |
| RF-B2-071 | Portada orientada a tareas: los 3 trámites más solicitados a ≤2 clics | Must | ❌ | La portada ofrece tres **secciones**, no trámites (`index.vue:72-90`); `/tramites` está a un clic, pero los trámites concretos a tres (portada → catálogo → ficha) (D-08). |
| RF-B1-083 | Página de confirmación con nº de referencia, próximos pasos y plazo | Must | ❌ | No existe. El formulario PQRSD ni siquiera radica (D-26). |
| RF-B2-077 | Procesos largos con pasos numerados (stepper) | Must | ✅ | El formulario de petición se divide en **tres pasos** con encabezado «Paso N de 3», línea de avance con `aria-current="step"` y estado por paso en texto. Medido con navegador en `make diseno` (§18). |
| RF-B1-086 | Sin pop-ups no solicitados; un modal a la vez | Must | ✅ | No hay ninguno. La excepción que el criterio contempla —el banner de cookies— tampoco existe (D-01). |
| RF-B1-084 | Formularios con ejemplos de formato, obligatorios marcados, etiquetas arriba y validación en línea | Must | 🟡 | Obligatorios, etiquetas y validación con foco al error ✅ (`realizar-una-peticion.vue:413-472`); **faltan los ejemplos de formato** y la validación al perder el foco en todos los campos (D-27 bis). |
| RF-B3-077 | Carga de archivos con tipos, tamaño máximo y confirmación del fichero | Must | ❌ | **No hay ningún `type="file"` en la sede** (D-13). Bloquea también RF-B1-033 y RF-04-D04. |
| RF-B3-078 | Desplegables con filtro de búsqueda | Must | ❌ | No implementados (D-27). ADR-0015 reexpresa el umbral a 12 elementos y el sitio usa `<select>` nativo: la desviación es defendible (C-06), pero **el componente no existe cuando se supere ese umbral**. |
| RF-B3-079 | Entradas con estados y contraseña con ojo mostrar/ocultar | Must | ❌ | No hay control de mostrar/ocultar en ningún formulario —ni en el sitio ni en el acceso del panel (`EntrarView.vue:35-41`) (D-27). |
| RF-B3-080 | Checkboxes/radios con `<label>` y `for` | Must | ✅ | `realizar-una-peticion.vue:503-513`; `pqrsd.vue`. |
| RF-B1-006 | Buscador con autocompletado y corrección ortográfica | Must/Should | ❌ | Ver módulo 01 (D-03). |
| RF-B1-085 | Responsive de 320 px a escritorio sin scroll horizontal | Must | 🟡 | Los tres desbordes medidos están corregidos y documentados (`sitio.css:281-334`, `PiePaginaGovco.vue:407-424`, `accesibilidad.vue:163-173`); los seis breakpoints no están probados uno a uno (⛔). |
| RF-B1-089 | HTML/CSS válido W3C, CSS separado, sin tags ni vínculos rotos | Must | ⛔ | Sin validador. Nota: el CSS **sí** está separado (`sitio.css` + Kit) y no hay estilos en línea salvo el necesario (`useAccesibilidad.ts:47`). |
| RF-B1-076 | Encabezados semánticos en lenguaje claro y logo a inicio | Must | ✅ | Un `h1` por página, jerarquía sin saltos; `CabeceraGovco.vue:79-81`. |
| RF-B1-088 | SEO: títulos, meta-descripción, palabras clave, sitemap XML | Should | 🟡 | Título en las 19 páginas y descripción **sólo en la portada** (`index.vue:28-34`); `sitemap.xml` y `robots.txt` ✅; **sin `og:`/`twitter:` ni `canonical`** en ninguna página (D-28). |
| RF-B2-081 | Texto no justificado, 60-80 caracteres por línea | Should | ✅ | No justificado ✅ y **medido**: `max-width: 68ch` sobre la prosa del contenido principal deja **74 caracteres/línea en `/pqrsd` y 70 en `/accesibilidad` y `/politicas/uso-de-cookies`** (§13.1). Antes eran ~81. |
| RF-B1-087 | Encuesta SUS accesible y publicada | Should | ❌ | No existe (D-27). |
| RF-B3-146 | Lenguaje claro con guía de voz y tono y pruebas de usuario | Must/Should | ⛔ | Exige evaluación con personas; el texto publicado sí es claro y sin siglas sin explicar (juicio cualitativo, no acreditado). |
| RF-08-D01 | Criterio cuantitativo de usabilidad (tasa de éxito, tiempo en tarea) | Should | ➖ | Pregunta abierta del propio corpus (`08:58`). |

**RNF del módulo 08**

| ID | Umbral | Estado | Evidencia / brecha |
|---|---|---|---|
| RNF-B1-030 | SUS ≥68 | ❌ | No hay encuesta ni medición (D-27). |
| RNF-B1-031 | 60-80 caracteres por línea | ✅ | Ver RF-B2-081: 70-74 medidos en tres páginas de prosa. |
| RNF-B1-032 | 1024×768 sin scroll horizontal; responsive hasta 320 px | 🟡 | Los defectos a 320 px están corregidos y documentados; los 1024×768 no constan medidos. |
| RNF-B2-019 | Buscador de ~200 px de ancho mínimo | 🟡 | El campo cede espacio y no desborda (`sitio.css:298-320`), pero mide lo que le deja la fila de la cabecera; sin medición de los 27 caracteres (⛔). |
| RNF-B2-007 | FCP ≤2,5 s en 4G | ⛔ | Sin medición. |
| RNF-B2-021 | ≤posición 10 en Google para ≥3 de 5 frases clave | ⛔ | Sin medición. |
| RNF-B1-043 | Código limpio, HTML/CSS válido, **cobertura de pruebas ≥70 %**, CI/CD | 🟡 | **Las pruebas existen y el 70 % se alcanza en líneas**: 112 pruebas (52 sitio + 60 panel), **sitio 95,54 %** y **panel 70,90 %** de líneas, medidos con `make cobertura` y protegidos por un trinquete (§20.2). HTML/CSS válido sigue siendo ⛔, y no hay CI/CD en el repositorio. |
| RNF-B3-036 | ≤7 ítems de menú principal | ✅ | Exactamente 7 (`layouts/default.vue:46-94`). |
| RNF-B3-037 | Índice Fernández-Huerta ≥60 | ⛔ | Sin cálculo. |
| RNF-08-D01 | Resultados SUS almacenados y exportables | ❌ | No hay SUS (D-27). |

### 4.4 Módulo 12 — Gestión de contenidos y administración (15 RF · **0 cumplen**)

Es el módulo con el resultado más duro, y conviene decirlo sin ambigüedad: **el panel es hoy una
carcasa de navegación**. 18 de sus 19 rutas renderizan el mismo marcador
(`router/index.ts:41-59` → `EnConstruccionView.vue`), no hay autenticación, no hay CMS, no hay
registro electrónico, no hay auditoría y no hay ninguna operación de escritura.

| ID | Requisito | Prio | Estado | Evidencia / qué falta |
|---|---|---|---|---|
| RF-B1-076 | CMS con roles (administrador, editor de sección, publicador) y log de auditoría | Must | ❌ | Sin CMS. El panel tiene marcadores (D-15); el menú lateral declara los módulos pero ninguno existe (`AdminLayout.vue:29-87`). |
| RF-B1-079 | Usuarios y roles con ≥3 niveles, permisos por módulo, auditoría, T&C y firma al registrarse | Must | ❌ | Sin gestión de usuarios. Los `permiso` declarados en el router no se aplican (D-04). |
| RF-B2-067 | Registro electrónico 24/7/365 con consecutivo, acuse y distribución | Must | ❌ | Sin expediente ni radicación. `realizar-una-peticion.vue:14-19` lo declara honestamente: «al enviarlo no se registrará nada». |
| RF-B2-068 | Relación de peticiones y calendario oficial de días hábiles | Must | ❌ | No existe. `pqrsd.vue:210-218` publica los plazos legales como tabla informativa, que no es lo mismo. |
| RF-B1-077 | Archivo y conservación conforme a TRD y AGN | Must | ❌ | Sin SGDEA ni TRD. |
| RF-B1-078 | Tablero ITA **interno**, que arranca en cero, valida al publicar y **no usa datos mock** | Must | ❌ | **Incumplido de la peor forma posible**: existe una superficie que parece ese tablero y publica cifras inventadas (287 PQRSD, 34 por vencer, 1 842 trámites, 94 % de ITA, «Sistema operativo», «API Gateway OK») en `InicioView.vue:22-54` (D-05). |
| RF-B2-089 | Analítica de uso del portal | Should | ❌ | No hay analítica; el módulo `/reportes` es un marcador. |
| RF-B2-093 | Publicación automática semestral del informe de control interno | Must | ❌ | Sin CMS ni planificación editorial. |
| RF-B3-153 | Notificaciones electrónicas y alertas multicanal | Must | ❌ | El módulo `/notificaciones` es un marcador; **el icono de campana de la cabecera muestra un punto rojo sin dato alguno** (`AdminLayout.vue:298-309`). |
| RF-B3-155 | Firma electrónica con efectos de la autógrafa | Must | ❌ | No hay firma. |
| RF-12-D05 | Validación automática de criterios ITA al publicar, con bloqueo de los bloqueantes | Must | ❌ | Sin CMS. |
| RF-12-D01 | Flujo editorial Borrador → Pendiente → Publicado → Archivado, con rechazo | Must | ❌ | Sin flujo. |
| RF-12-D02 | Baja segura de usuarios con revocación ≤1 día hábil y trazabilidad | Must | ❌ | Sin usuarios. |
| RF-12-D03 | Reintento y canal alterno de notificaciones | Should | ❌ | Sin notificaciones. |
| RF-12-D04 | Verificación de edad de titulares menores | Must | ❌ | No hay registro que recolecte fecha de nacimiento. |

**RNF del módulo 12**

| ID | Umbral | Estado | Brecha |
|---|---|---|---|
| RNF-B1-044 | Sincronización con la Hora Legal Colombiana | ❌ | Sin reloj legal ni sello temporal. |
| RNF-B2-026 | 100 % de expedientes con TRD aplicada | ❌ | Sin expedientes. |
| RNF-B3-022 | 100 % de eventos críticos en log con retención ≥5 años | ❌ | Sin logs de auditoría. |
| RNF-B3-044 | Integridad, inmutabilidad y MTBF >4 320 h | ➖ | Infraestructura. |
| RNF-B1-039 | Programa RAEE | ➖ | Gestión ambiental, fuera del diseño. |
| RNF-12-D01 | Bloqueo de concurrencia editorial | ❌ | Sin edición concurrente que bloquear. |

### 4.5 Otros módulos con efecto en la interfaz (33 RF)

| ID | Requisito | Estado | Evidencia / brecha |
|---|---|---|---|
| RF-B1-012 | Menú de Transparencia con ≥10 subsecciones publicadas | ❌ | `/transparencia` es un aviso de «en preparación» (`transparencia.vue`) (D-17). |
| RF-B1-013 | Normativa con tipo, número, fechas, epígrafe, vigencia, descarga abierta y orden inverso | 🟡 | **La interfaz está construida y es sólida**: catálogo, filtros por tipo, recuento, orden por fecha de publicación con desempate, formato ISO y región viva (`normativa.vue:41-277`). **El listado está vacío** porque la Entidad no ha entregado normas. Cumple el diseño, no el contenido. |
| RF-B1-014 | Enlace funcional al SUIN y Gaceta Oficial | ❌ | No existe enlace a SUIN en el sitio. |
| RF-B1-015 | Enlace funcional a SECOP I/II y publicación de contratación | ❌ | Sin sección de contratación. |
| RF-B1-016 | Plan de Acción publicado antes del 31 de enero | ❌ | Sin sección de planeación. |
| RF-B1-018 | Predial, ICA y calendario tributario | ❌ | Sin sección tributaria. (`tramites/[slug].vue:22-28` declara canales de recaudo del predial sin pasarela.) |
| RF-B1-037 | Informes trimestrales de PQRSD en formato abierto | ❌ | Sin informes. |
| RF-B1-096 | Información para grupos de interés | ❌ | Sin sección. |
| RF-B1-097 | Información institucional: misión, organigrama, directorio, planes, estados financieros | ❌ | Sin sección. |
| RF-B1-021 | Catálogo de trámites vinculado al SUIT con los seis atributos | 🟡 | Implementado contra el contrato con procedencia declarada campo a campo (`tramites/[slug].vue:5-38`) y catálogo paginado con filtros (`tramites/index.vue:240-330`); depende de que el backend sirva los datos. |
| RF-B3-126 | Ficha en GOV.CO con los cuatro momentos | 🟡 | Ver módulo 01. |
| RF-B3-127 | Estados estandarizados de GOV.CO | ❌ | No hay estados en la interfaz. |
| RF-B1-033 | PQRSD sin restricciones técnicas de formato, tamaño ni cantidad de adjuntos | Must | ❌ | **No hay campo de adjuntos** (D-13). Es la contradicción C-01 del corpus, resuelta aquí por omisión en vez de por diseño. |
| RF-B1-034 | Seguimiento por número de radicado con estado y fecha estimada | Must | ❌ | `/seguimiento` es un aviso; `tramites/[slug].vue:29-33` explica que el canal viaja «declarado y no habilitado» (D-17). |
| RF-B1-035 | Validación cliente y servidor con mensajes accesibles y foco al campo erróneo | Must | ✅ | `realizar-una-peticion.vue:359-413` (validación, resumen, foco al primer inválido) y `pqrsd.vue`. |
| RF-B3-099 | Acuse de recibo automático y radicado en ≤24 h | Must | ❌ | Sin radicación. |
| RF-B3-101 | Captcha accesible en el formulario | Must | ❌ | **No hay captcha en ningún formulario de la sede** (D-13). Incumple además el ítem 4 del PDF de seguridad y el ítem 5 del de funcionalidad. |
| RF-04-D04 | Validación por campo del formulario PQRSD (documento, correo, tope de 2 000, obligatoriedad condicional) | Must | 🟡 | Hay validación y modalidad anónima que descarta los datos identificativos (`:167-168,278`); el patrón por tipo de documento y el tope de 2 000 caracteres no constan implementados. |
| RF-04-D01 | Traslado por competencia con notificación | Must | ❌ | Sin back-office. |
| RF-04-D02 | Gestión de respuesta y cierre con medición de plazo | Must | ❌ | Sin back-office. |
| RF-04-D03 | Cómputo de plazos por tipo con calendario hábil | Must | ❌ | `pqrsd.vue:210-218` publica los plazos como texto; no hay cómputo. |
| RF-03-D03 | Reanudación de trámite con adjuntos ya cargados | Should | ❌ | Sin adjuntos ni borradores. |
| RF-03-D04 | Validación de adjuntos (MIME real, tamaño, cantidad, antivirus) | Must | ❌ | Sin carga de archivos. |
| RF-05-D01 | Ciclo de vida de mecanismos de participación con cierre automático | Should | ❌ | Las seis subcategorías son avisos (`participa/[slug].vue:70-75`). |
| RF-B1-041 | Sección de Canales de Atención con dirección, horarios, teléfonos y correos | Must | ✅ | `atencion.vue` publica los canales verificados contra el portal oficial, con `+57` y horarios. |
| RF-B1-030 | Agendamiento de citas con confirmación y consulta | Should/Must | ❌ | Sin agendamiento. |
| RF-06-D01 | Administración de la agenda de citas desde el CMS | Must | ❌ | Módulo `/citas` es un marcador. |
| RF-B1-057 | Cookies con `Secure` y `HttpOnly`, sesión ≤900 s | Must | ⛔ | No hay cookies emitidas por el frontend que auditar; depende del backend y del punto de entrada. |
| RF-B1-060 | Sanitización de todas las entradas | Must | ⛔ | Requiere pentest o prueba de XSS. |
| RF-B1-058 | Captcha accesible en formularios que capturan datos y límite de intentos | Must | ❌ | Sin captcha (D-13). |
| RF-B1-062 | Páginas de administración no accesibles desde internet sin autenticación | Must | ❌ | **Incumplido en el diseño de la interfaz**: `/admin/*` no tiene guardia (D-04). El `noindex` (`panel/index.html:17`) no es control de acceso. |
| RF-B3-074 | Módulo de inicio de sesión del Kit con captcha y soporte de CC/CE/TI/PEP/NIT | Must | ❌ | El acceso es una maqueta sin autenticación, sin captcha y sin selector de tipo de documento (`EntrarView.vue`) (D-04). |
| RF-11-D01 | Actualización programada de datasets con alerta de desactualización | Should | ❌ | Módulo de datos abiertos inexistente. |

### 4.6 Recuento por estado

| Módulo | RF | ✅ | 🟡 | ❌ | ⛔ | ➖ |
|---|---|---|---|---|---|---|
| 01 — Estructura e identidad | 44 | 10 | 10 | 18 | 1 | 5 |
| 07 — Accesibilidad | 37 | 13 | 14 | 1 | 3 | 6 |
| 08 — Usabilidad | 23 | 7 | 4 | 9 | 2 | 1 |
| 12 — Gestión de contenidos | 15 | 0 | 0 | 15 | 0 | 0 |
| Otros con efecto en la interfaz | 33 | 2 | 4 | 25 | 2 | 0 |
| **Total RF** | **152** | **32** | **32** | **68** | **8** | **12** |
| **Total RNF** | **31** | **5** | **7** | **8** | **8** | **3** |

**Lectura de la tabla.** El 21 % de cumplimiento no es un fracaso del diseño, es la fotografía de
un producto a medio construir: de los 68 RF no cumplidos, **43 lo están porque la superficie no
existe** (módulo 12 completo, secciones de contenido, adjuntos, captcha, buscador, CMS) y **11
porque existe una pieza que contradice el requisito** (cookies, aviso externo, login, tablero con
datos simulados, carrusel en reproducción, barra sin persistencia, tabla sin ordenamiento,
visitados sin diferenciar, panel con identidad ajena al Kit, barra oculta en tablet, identidad
duplicada de datos de entidad). Esa segunda lista —11 requisitos donde hay algo construido
**mal**— es la que hay que arreglar antes de seguir construyendo lo que falta.

---

## 5. Registro de hallazgos: todo lo que falta por corregir en el diseño

Cada hallazgo lleva **qué falta**, **dónde** (evidencia) y **cómo se comprueba que quedó
corregido**. El orden dentro de cada gravedad es el orden de corrección recomendado (§8).

### 5.1 🔴 Bloqueantes (6)

Impide que la sede cumpla la norma, o afirma al ciudadano algo que no es cierto.

---

**D-01 · No existe banner de cookies ni gestión de consentimiento**

- **Requisitos:** RF-B1-008 (Must), RF-01-D01 (Must), RN-01-D01, HU-01-D01, HU-B1-010; ítem 5 del
  PDF de funcionalidad (aviso de privacidad y autorización); Ley 1581 de 2012.
- **Evidencia de la ausencia:** `grep -ri cookie sitio/app` sólo devuelve el rótulo del enlace del
  pie y la página de política; no hay componente, ni estado, ni `useCookie`, ni almacenamiento.
  El layout no monta nada equivalente (`layouts/default.vue:153-204`).
- **Por qué es bloqueante y no «falta contenido»:** la política publicada **promete** que existe
  —«Qué cookies usa el sitio… y cómo se administra el consentimiento de quien navega»
  (`politicas/[slug].vue:48-52`)— y el criterio 5 del PDF oficial lo exige en *todos* los
  formularios que capturan datos. Además, sin consentimiento versionado no puede activarse
  analítica alguna (y `RF-B2-089` la pide).
- **Qué falta construir:** (1) el banner con las tres acciones —aceptar todo, rechazar todo,
  configurar por categoría—; (2) el registro del consentimiento con **categorías, fecha, hora y
  versión de la política**; (3) la caducidad a 12 meses y la re-solicitud al cambiar la versión
  (RN-01-D01); (4) el punto de revocación permanente; (5) el bloqueo efectivo de cualquier script
  no esencial hasta la aceptación (hoy no hay ninguno, así que el bloqueo hay que construirlo
  **antes** de que llegue la analítica).
- **Verificación:** usuario nuevo → banner visible y cero cookies no esenciales en DevTools;
  consentimiento guardado con versión y fecha; simular 13 meses → el banner reaparece.

---

**D-02 · No existe el aviso de salida a sitio externo**

- **Requisitos:** RF-B1-071 (Must/Should), RF-B2-040, RF-B3-094, RF-01-D03 (Should), RN-01-D03;
  ítem 2 del PDF de funcionalidad (página externa en nueva pestaña).
- **Evidencia:** los enlaces externos abren en pestaña nueva **sin aviso**:
  `BarraSuperior.vue:17-22` (Portal GOV.CO) y `PiePaginaGovco.vue:321-332` (Facebook, Instagram,
  X), que sí declaran `rel="noopener"` pero ningún aviso intermedio.
- **Qué falta construir:** el modal de aviso con nombre del destino y entidad responsable,
  confirmación explícita, y la **lista blanca de dominios de confianza** (GOV.CO, pasarela del
  Articulador, SCD) que evite el falso positivo (RF-01-D03, RN-01-D03). Requiere también el
  componente de alerta modal (D-28), que hoy no existe.
- **Nota de coherencia:** ADR-0015 §3 declara «CAG-21 no aplica porque la sede no abre modales».
  Ese «no aplica» **caduca en el momento en que este RF se implemente**: el modal de aviso es
  exactamente un modal (C-06).
- **Verificación:** enlace a un dominio no listado → modal con confirmación; enlace a `gov.co` →
  navegación directa sin modal.

---

**D-03 · El buscador existe, se ve en todas las páginas y no busca**

- **Requisitos:** RF-B1-005 (Must), RF-B1-006 (Must/Should), RF-B2-035/036, RF-B3-067/140/141;
  RNF-B2-019; ítem 6 del PDF de funcionalidad («la búsqueda debe hacerse dentro de la sede»).
- **Evidencia:** `buscar.vue:44-49` —«El buscador todavía no tiene contenido que consultar»— y
  `BuscadorGovco.vue:5-8`, que documenta la omisión del buscador predictivo. No hay endpoint de
  búsqueda en el contrato (`contract/openapi.yaml`, tres rutas) ni índice de contenido.
- **Por qué es bloqueante:** es una de las dos formas de localizar información en una sede
  (WCAG 2.4.5 y RF-B1-053 piden ≥2 vías); sin buscador, el ciudadano que no conoce la jerarquía
  del menú no tiene camino. Y el ítem 6 del PDF de aceptación de funcionalidad es explícito.
- **Qué falta construir:** (1) el recurso de búsqueda en el contrato con su criterio de
  pertinencia; (2) la página de resultados con **fecha, categoría, título, extracto, autor y
  miniatura** por resultado, paginada y con estado vacío propio; (3) el buscador predictivo del
  Kit (`govco-search-predictive`, clases hoy ausentes) con ≤10 sugerencias y tolerancia a errores
  tipográficos; (4) el buscador **dentro** de la sección de Transparencia, que la Sección 5 §5.3.1
  exige aparte.
- **Verificación:** buscar «licncia» → sugiere «licencia de construcción»; buscar un término
  inexistente → estado vacío con alternativas; `role="status"` anunciando el recuento.

---

**D-04 · El panel no autentica, y afirma que sí**

- **Requisitos:** RF-B1-062 (Must, páginas de administración no accesibles sin autenticación),
  RF-B3-074 (Must, módulo de inicio de sesión del Kit con captcha), RF-B1-058 (captcha),
  RF-09-D02, RF-09-D04, RN-09-D03, RN-09-D04 (MFA obligatoria para roles internos),
  GUIA-MAESTRA-COMPLETA §matriz 25 puntos (17-24: guardias, `panel-administrative`, coherencia
  FE↔BE).
- **Evidencia:**
  1. El router declara los metadatos de seguridad y **no los aplica**: `router/index.ts:34-44`
     (`requiereSesion`, `permiso`, `soloInvitados`) y el único gancho instalado es
     `afterEach` para escribir el título (`:119-121`). **No hay un solo `beforeEach`.**
  2. La pantalla de acceso lo dice en su propio comentario: «el envío **no autentica**, porque el
     módulo de identidad todavía no existe» (`EntrarView.vue:5-9`), y sin embargo publica
     «Sesión protegida con doble factor (Decreto 1078)» (`:59-61`).
  3. El sitio público enlaza a esa pantalla desde la cabecera de **todas** las páginas
     (`layouts/default.vue:182-184`).
  4. El panel muestra «Administrador / Sesión activa» con iniciales fijas `'AD'`
     (`AdminLayout.vue:166,318-319`).
- **Qué falta:** (1) el ciclo real de sesión (SSO/SLO vía SCD de Autenticación, según ADR y
  `contexto-transversal.md`), o bien **retirar el enlace de la cabecera** hasta que exista;
  (2) las guardias `beforeEach` que apliquen `requiereSesion`/`permiso`/`soloInvitados`;
  (3) el captcha accesible y el límite de intentos; (4) MFA; (5) mientras no exista nada de eso,
  **quitar la afirmación de doble factor** y el marcador de sesión: hoy la interfaz miente sobre
  su propio estado de seguridad.
- **Verificación:** abrir `/admin/` sin sesión → redirección; rol sin permiso → pantalla de «sin
  permiso» (que ya existe: `SinPermisoView.vue`); usuario autenticado en `/acceso` → redirección.

---

**D-05 · El panel publica cifras inventadas donde el requisito las prohíbe**

- **Requisitos:** RF-B1-078 (Must) —«tablero ITA interno, **sin datos mock**, arranca en cero»—,
  RF-B2-089, y el principio editorial que el propio proyecto sostiene («en un sitio institucional
  [el relleno] se lee como información oficial», `SeccionEnPreparacion.vue:9-10`).
- **Evidencia:** `InicioView.vue:26-29` publica «PQRSD activas 287 (+12 %)», «Por vencer 34»,
  «Trámites SUIT 1 842», «Cumplimiento ITA 94 %»; `:22` publica la insignia «Sistema operativo»;
  `:44-54` publica un panel de «Salud del sistema» con «API Gateway OK», «SIGMI Bus OK»,
  «Conector RNEC Lento», «Firma electrónica OK»; `AdminLayout.vue:308` muestra una campana con
  punto rojo de notificación. El propio archivo admite en un comentario que las cifras son «de
  maqueta» (`:5-7`).
- **Por qué es bloqueante y no cosmético:** es exactamente el patrón que RF-B1-078 prohíbe por
  escrito (el corpus descarta el 47/100 del sitio anterior por ser «mock»:
  `sede-electronica-doc/README.md` §3). Un funcionario que abra ese tablero no puede distinguir
  lo real de lo inventado; y si el panel se despliega en producción, la Entidad publica
  indicadores falsos de su propio cumplimiento.
- **Qué falta:** sustituir cada cifra por su origen real (o por el estado «sin datos» declarado);
  retirar las insignias de estado mientras no existan sondas; y construir el tablero ITA como lo
  pide RF-B1-078: **interno, en cero, alimentado por el validador de publicación**.
- **Verificación:** ninguna cifra sin `source` trazable; el tablero arranca en cero y se mueve
  sólo al publicar contenido.

---

**D-06 · Las puertas de calidad y la matriz de trazabilidad acreditan artefactos que no existen**

- **Requisitos:** RNF-B1-043 (Must, CI/CD y cobertura ≥70 %), CAG-32, §2.6.7 del expediente
  (las tres puertas automáticas), y las reglas de trazabilidad de `docs/trazabilidad.md:9-13`.
- **Evidencia:**
  - `Makefile:107` → `node tests/conformidad-diseno.mjs` en `panel/`: **`panel/tests/` no existe**.
  - `Makefile:111` → `node tests/accesibilidad.mjs`: ídem.
  - `Makefile:97-98` → `npm run test` en `panel/` y `sitio/`: **ninguno de los dos declara ese
    script** (`panel/package.json:5-9`, `sitio/package.json:6-13`).
  - `Makefile:135,139` → `bash scripts/verificar-imagenes.sh` y `verificar-respaldo.sh`:
    **no existe `scripts/`**.
  - `Makefile:63` → `php artisan test --filter=ContratoDeriva`: **no hay ninguna prueba con ese
    nombre**.
  - `docs/trazabilidad.md` acredita 122/140 criterios citando `frontend/tests/*.mjs`,
    `views/publico/*.vue`, `backend/tests/Feature/Sede/ConformidadSedeTest.php`,
    `backend/tests/Feature/Api/CatalogoApiTest.php`, `GET /menus/{ubicacion}`, `GET /sedes` y
    `npm run trazabilidad`. **Ninguna de esas rutas, pruebas, vistas ni operaciones existe.**
- **Qué falta:** (1) decidir y ejecutar: o se construyen las pruebas y se regenera la matriz contra
  el árbol real, o se **retira la matriz del expediente** mientras no describa el repositorio;
  (2) alinear el `Makefile` con lo que existe para que `make comprobar` sea una puerta de verdad;
  (3) escribir en la matriz los tres estados que ADR-0015 promete (satisfecho / no aplica /
  desviación declarada) y no los tres actuales (implementado / cubierto / pendiente).
- **Verificación:** `make comprobar` termina en verde **y** cada fila de la matriz resuelve a un
  fichero existente.

### 5.2 🟠 Graves (17)

Incumple un Must funcional o de accesibilidad, o hace que el diseño no pueda acreditarse.

---

**D-07 · La portada no publica noticias**

- **Requisitos:** RF-B1-011 (Must), RF-B2-071, RF-B1-083, FUN-026.
- **Evidencia:** `index.vue:43-90`: tres diapositivas de secciones y tres tarjetas; ninguna noticia
  con imagen 4:3/16:9, título, descripción y fecha. `/noticias` es un aviso de preparación.
- **Qué falta:** el módulo de noticias con su modelo (fecha, categoría, autor, extracto, miniatura),
  orden cronológico inverso y su CRUD en el CMS (RF-01-D02).
- **Verificación:** una noticia publicada aparece en la portada con imagen, título ≤150 car.,
  descripción ≤200 car. y fecha, en orden inverso.

---

**D-08 · La portada no está orientada a tareas**

- **Requisitos:** RF-B2-071 (Must, «los 3 trámites más solicitados a ≤2 clics»), FUN-013.
- **Evidencia:** `index.vue:72-90` ofrece tres **secciones** genéricas; los trámites concretos
  quedan a tres clics (portada → catálogo → ficha) y para dos de las tres tarjetas
  (`/servicios`, `/participa`) el destino es un aviso de preparación.
- **Qué falta:** los tres trámites más solicitados en el primer scroll, con enlace directo a su
  ficha; y decidir si `/servicios` y `/tramites` siguen siendo dos nombres para lo mismo (D-44).
- **Verificación:** desde la portada, cada uno de los tres trámites abre su ficha en ≤2 clics.

---

**D-09 · La página 404 ofrece una sola salida**

- **Requisitos:** RF-B1-007 (Must, «≥3 opciones de navegación o contenido alternativo»),
  RF-B3-142.
- **Evidencia:** `error.vue:39-56`: mensaje correcto, estado HTTP correcto y **un** enlace
  («Volver a la portada»). El menú y el buscador del layout cuentan como vías de facto, pero el
  criterio pide contenido alternativo explícito.
- **Qué falta:** buscador dentro de la propia página de error y accesos a las secciones más
  consultadas (trámites, PQRSD, transparencia, atención), más el registro del fallo para
  observabilidad.
- **Verificación:** una URL inexistente devuelve 404 con tres alternativas operables.

---

**D-10 · Las cinco políticas del pie no publican documento**

- **Requisitos:** RF-B1-009 (Must), RF-B2-008, RF-B2-009, RF-B2-010, SEG-006; ítems 4 y 6 de los
  PDF de funcionalidad y seguridad.
- **Evidencia:** las cinco rutas existen y son honestas —avisan de que el documento está en
  preparación (`politicas/[slug].vue:111-117`, `PiePaginaGovco.vue:235-236,344-346`)—, pero **no
  hay documento, ni descarga, ni formato abierto**.
- **Qué falta:** el texto de cada política con sus componentes mínimos (los seis de RF-B2-008, los
  cinco de RF-B2-009) y su descarga en formato abierto; el acto administrativo de adopción; y la
  política de derechos de autor, que el corpus señala como incumplimiento abierto de la Entidad.
- **Verificación:** los cinco enlaces del pie responden 200 con el documento descargable.

---

**D-11 · La barra de accesibilidad no recuerda la preferencia ni ofrece el Centro de Relevo**

- **Requisitos:** RF-B1-044 (Must: «tamaño de fuente, alto contraste, saltar al contenido, **enlace
  al Centro de Relevo**; **guarda la preferencia del usuario**»), HU-B1-015, HU-B3-002;
  **ADR-0012**, que compromete explícitamente la persistencia en `localStorage`.
- **Evidencia:** `useAccesibilidad.ts:37` guarda el estado en `useState` —vive en memoria y se
  pierde al recargar—; no hay una sola referencia a `localStorage` en todo `sitio/app`; el Centro
  de Relevo no aparece en ningún fichero; `restablecer()` (`:61-63`) se exporta y **nadie lo usa**.
- **Qué falta:** (1) persistir contraste y tamaño; (2) añadir el enlace al Centro de Relevo (canal
  para discapacidad auditiva, `07:163` del corpus); (3) exponer y cablear el restablecimiento.
- **Verificación:** activar alto contraste, recargar o navegar → sigue activo; el enlace al Centro
  de Relevo está presente y etiquetado.

---

**D-12 · El carrusel arranca reproduciéndose**

- **Requisitos:** RF-B1-042 (Must, «**pausa por defecto**»), RF-B1-051 (Must, «slider en pausa por
  defecto»), WCAG 2.2.2.
- **Evidencia:** `CarruselGovco.vue:98` (`autoplay: true` por defecto) y `:200`
  (`reproduciendo.value = props.autoplay && !consultaMovimiento.matches`). El componente sí
  respeta `prefers-reduced-motion` (`:195-200`) y ofrece control de pausa, lo que mitiga el daño
  pero no cumple el criterio literal.
- **Qué falta:** invertir el valor por defecto a `false` y dejar el arranque como acción explícita.
- **Verificación:** al cargar la portada, el botón muestra «Reproducir», no «Pausar».

---

**D-13 · El PQRSD no admite adjuntos y ningún formulario tiene captcha**

- **Requisitos:** RF-B1-033 (Must, sin restricciones técnicas de formato, tamaño ni cantidad),
  RF-B3-077 (Must, carga de archivos con tipos, tamaño y confirmación), RF-04-D04, RF-B3-101 y
  RF-B1-058 (captcha accesible), RN-07-D02; ítem 4 del PDF de seguridad y ítem 5 del de
  funcionalidad.
- **Evidencia:** **no hay un solo `type="file"` en la sede**; no hay captcha en ningún formulario
  (`grep -ri captcha sitio/app` sin resultados); el formulario `realizar-una-peticion.vue` cubre
  identificación, tipo, descripción, modalidad anónima y autorización de datos (`:865-897`), pero
  no adjuntos ni desafío anti-bot.
- **Nota:** la contradicción C-01 del corpus (la norma prohíbe límites, la sede actual los tenía)
  queda hoy resuelta **por omisión**, que no es una decisión de diseño. Hay que decidirla y
  documentarla: qué límites técnicos del servidor se aceptan y cómo se comunica el rechazo.
- **Qué falta:** el componente de carga de archivos del Kit, con validación de tipo MIME real,
  tamaño, cantidad y antivirus (RF-03-D04), mensaje de error accesible, y el captcha accesible con
  alternativa de audio.
- **Verificación:** adjuntar un PDF de 2 MB → «documento.pdf (2 MB)» confirmado; enviar sin captcha
  resuelto → bloqueado con mensaje accesible.

---

**D-14 · El panel tiene una identidad visual ajena al Kit y la etiqueta como oficial**

- **Requisitos:** RNF-B3-045 (Must, 100 % de componentes con Cobalt `#0943B5` y Nunito Sans /
  Verdana), RF-B1-098, RN-B2-029, CAG-33; ADR-0003.
- **Evidencia:** `panel/src/assets/styles/tokens.css:1-4` abre con el comentario «**SGDI — Design
  tokens GOV.CO oficiales**» y acto seguido declara `--color-gov-blue: #3366CC`, que **no es** el
  cobalto del Kit; `fonts.css` carga Inter, Montserrat y JetBrains Mono en lugar de Nunito Sans y
  Verdana; `panel/index.html:15` publica `theme-color #3366CC` (el sitio, en cambio, usa
  `#0943B5` en su manifiesto).
- **Qué falta:** decidir y documentar una de dos: (a) el panel adopta los tokens del Kit (y la
  GUIA-MAESTRA-COMPLETA, que exige Tailwind, se corrige), o (b) se declara formalmente que la
  superficie interna tiene identidad propia —con su ADR— y **se retira la palabra «oficiales»** de
  los tokens y del manifiesto. Lo que no puede sostenerse es la etiqueta actual: afirma una
  conformidad que el propio valor desmiente (C-11).
- **Verificación:** un `grep` de `#3366CC` en el panel no devuelve nada, o el ADR que lo justifica
  está escrito y enlazado.

---

**D-15 · Dieciocho de las diecinueve rutas del panel son el mismo marcador**

- **Requisitos:** todo el módulo 12: RF-B1-076, RF-B1-079, RF-B2-067, RF-B2-068, RF-B1-077,
  RF-B1-078, RF-B2-093, RF-B3-153, RF-B3-155, RF-12-D01/D02/D04/D05; RF-06-D01.
- **Evidencia:** `router/index.ts:41-59` genera 18 rutas que renderizan
  `EnConstruccionView.vue`; la única vista con contenido propio es el tablero de la portada
  (`InicioView.vue`), cuyas cifras son simuladas (D-05).
- **Qué falta:** es el grueso del trabajo pendiente del proyecto, no un defecto del diseño
  existente. Lo que sí es un defecto de diseño **hoy**: el menú lateral presenta los 18 módulos
  como si existieran (`AdminLayout.vue:29-87`) y la paleta de comandos los ofrece como destinos
  (`:150-160`). Falta la marca visual de «no disponible» —que el sitio público sí usa
  (`SeccionEnPreparacion.vue`)— para que el funcionario no confunda un marcador con un módulo.
- **Verificación:** cada entrada del menú que no esté implementada se declara como tal en la
  propia interfaz.

---

**D-16 · Componentes del panel sin accesibilidad verificable**

- **Requisitos:** RF-B3-042 (componentes personalizados con nombre, función y valor), RF-B3-076
  (tablas con ordenamiento), RF-B3-075 (paginación con `aria-current`), RF-B3-017 (sin trampas de
  foco), RF-B3-022 (salto al contenido), WCAG 2.1.1, 2.4.3, 2.4.7, 4.1.2.
- **Evidencia:**
  1. `DataTable.vue:60-79`: el ordenamiento se activa con `@click` en el `<th>`, **sin `aria-sort`,
     sin botón, sin `tabindex` y sin manejador de teclado** → inoperable sin ratón.
  2. `DataTable.vue:104-131`: paginación sólo con «anterior/siguiente», sin número de página, sin
     `aria-label` de grupo y sin `aria-current`.
  3. `BaseModal.vue:29-35`: `role="dialog" aria-modal="true"` **sin mover el foco al abrir, sin
     atraparlo, sin devolverlo al cerrar y sin nombre accesible cuando `title` es `undefined`**
     (`:34`).
  4. `AdminLayout.vue:324`: existe `<main id="contenido-principal"` pero **nada enlaza a él**: el
     panel no tiene enlace de salto.
  5. `AdminLayout.vue:270-278`: la miga enlaza el último nivel a sí mismo y no marca la página
     actual con `aria-current`.
  6. `AccessibilityBar.vue:69-77`: el panel de opciones se declara `role="dialog"` sin gestión de
     foco y **sin cerrarse con Escape**.
- **Qué falta:** reescribir esos seis puntos con los patrones que el propio sitio ya aplica bien
  (el menú y el carrusel del portal público son el modelo a copiar).
- **Verificación:** tabular por el panel sin ratón y completar una ordenación, abrir y cerrar un
  diálogo, y saltar al contenido desde el primer tabulador.

---

**D-17 · Seis secciones obligatorias están vacías**

- **Requisitos:** FUN-013 y RF-B1-012, RF-B1-013, RF-B1-015, RF-B1-016, RF-B1-037, RF-B1-041,
  RF-B1-096, RF-B1-097, RF-B2-093; RF-B1-002/030 del módulo 06.
- **Evidencia:** son avisos de «en preparación» `transparencia.vue`, `servicios.vue`,
  `noticias.vue`, `portales.vue`, `seguimiento.vue` y las seis subcategorías de
  `participa/[slug].vue`.
- **Qué falta:** contenido y contrato. El diseño ya dejó el hueco correcto y honesto; lo que falta
  es lo que va dentro. **Riesgo de diseño concreto:** la portada y el menú anuncian como navegable
  lo que hoy es un aviso, y el `sitemap.xml` publica esas rutas con prioridad 0,6-0,9
  (`sitemap.xml.ts:37-47`), lo que las hace aparecer en buscadores como si publicaran contenido.
- **Verificación:** cada sección del menú publica al menos su información mínima legal, o deja de
  anunciarse en el `sitemap` con prioridad alta.

---

**D-18 · Los datos de la Entidad no vienen del contrato: están duplicados a mano**

- **Requisitos:** RF-B1-002 (Must), ADR-0006 (`adr/README.md:161-166`: centralizar en
  `backend/config/entidad.php` y exponer por `GET /api/v1/entidad`), y la descripción del propio
  recurso en `contract/openapi.yaml:54` («alimenta la cabecera y el pie de página de la sede»).
- **Evidencia:** `/entidad` **no se consume en ninguna página** (grep sin resultados); los datos
  viven incrustados en `PiePaginaGovco.vue:150-199` y **repetidos** en `atencion.vue:34-37`. El
  propio código reconoce el problema: «Si la Entidad cambia un canal hay que cambiarlo en los dos
  archivos» (`atencion.vue:11-15`).
- **Qué falta:** consumir `/entidad` en el layout y hacer del contrato la fuente única de teléfonos,
  correos, dirección, horarios y redes. Mientras no exista backend, el dato debe vivir en **un**
  módulo compartido y no en dos componentes.
- **Verificación:** cambiar un teléfono en la fuente → cambia en el pie y en `/atencion` a la vez.

---

**D-19 · Cero pruebas automatizadas con las herramientas ya instaladas**

- **Requisitos:** RNF-B1-043 (Must, cobertura ≥70 %, CI/CD), RNF-B1-014 (WCAG AA sin violaciones
  críticas), CAG-32, §3.6.2 y §2.6.7 del expediente, matriz de 25 puntos de la GUIA Maestra.
- **Evidencia:** no existe ningún `*.spec.*`, `*.test.*`, `playwright.config.*` ni
  `vitest.config.*` en `sitio/` ni en `panel/`; `axe-core`, `@axe-core/playwright`, `vitest`,
  `jsdom` y `@vue/test-utils` están declarados en los dos `package.json` y **no se usan en ningún
  fichero**. `Makefile:97-98` invoca un script `test` que no existe.
- **Qué falta:** la primera prueba es la que más valor tiene: **axe sobre las 15 vistas públicas**,
  porque desbloquea RNF-B1-014, CAG-32 y la mitad de los ⛔ de esta auditoría. Después: pruebas de
  contrato, de componentes base y de los flujos críticos (PQRSD, catálogo, menú).
- **Verificación:** `make accesibilidad` ejecuta axe y publica el informe con 0 violaciones
  críticas.

---

**D-20 · ADR-0015 ha caducado: declara desviaciones que ya no existen y omite las que sí**

- **Requisitos:** ADR-0015 (desviaciones declaradas frente a CAG), §2.6.3 del expediente,
  `trazabilidad.md:12-13` (vocabulario de estados).
- **Evidencia:**
  1. ADR-0015 §3 declara: «CAG-19, CAG-21, CAG-22, **CAG-24** y CAG-25 no aplican. La sede no usa
     campos de calendario, no abre modales, no emite notificaciones *toast*, **no publica tablas**
     ni usa acordeones». **`pqrsd.vue:222` publica una tabla.** CAG-24 vuelve a aplicar y no está
     verificado.
  2. Su «Evidencia» cita `frontend/tests/conformidad-diseno.mjs` y `make trazabilidad`: ninguno
     existe (D-06).
  3. Cita clases `.titulo-pie-sede` / `.subtitulo-pie-sede`; el código usa `.titulo-pie` /
     `.subtitulo-pie` (`PiePaginaGovco.vue:368,375`).
  4. Su punto 7 dice que el logo de la autoridad es «el logotipo tipográfico de la entidad» y deja
     «**Pendiente:** incorporar el logo oficial»; hoy hay un `<img>` con
     `sitio/public/logo-entidad.png` (`CabeceraGovco.vue:80`, `PiePaginaGovco.vue:295`).
- **Qué falta:** regenerar ADR-0015 contra el código actual, con los tres estados que él mismo
  promete, y **retirar los «no aplica» que el propio expediente contradice** (§2.5 exige tablas,
  acordeón, pestañas, stepper y toast en Trámites y Detalle: C-06).
- **Verificación:** cada desviación declarada tiene su comprobación automática real.

---

**D-21 · La barra de accesibilidad desaparece exactamente donde el requisito la exige**

- **Requisitos:** RNF-07-D01 (Should, «en tablet la barra está disponible y operable»), HU-07-D02,
  y la ambigüedad que el propio corpus reconoce (`07:174`).
- **Evidencia:** `BarraAccesibilidad.vue:39` aplica `d-none d-lg-flex`, es decir, se oculta por
  debajo de 992 px. El Kit (CAG-07) manda ocultarla entre 768 y 992; el corpus pide lo contrario
  para tablet. **El diseño resolvió el conflicto a favor del Kit sin dejar constancia de la
  decisión.**
- **Qué falta:** decidir y documentar (un ADR de una página basta): o se sigue al Kit —y entonces
  hay que justificar por qué se incumple RNF-07-D01—, o se muestra la barra desde 768 px y se
  declara la desviación frente a CAG-07. En cualquiera de los dos casos, hoy falta además una
  alternativa en móvil: la barra es **el único** mecanismo de contraste y tamaño, y en móvil no
  existe ninguno.
- **Verificación:** a 800 px de ancho, la barra está visible y operable, o el ADR explica por qué no.

---

**D-22 · La navegación interna usa `<a href>` y recarga la aplicación**

- **Requisitos:** RF-B2-072/074 (navegación consistente), RNF-B3-010 (páginas internas ≤1 s),
  y la coherencia del propio sistema: el sitio es una SPA con historial, y estos enlaces la
  abandonan.
- **Evidencia:** 12 enlaces internos escritos como `<a href>`: `SeccionEnPreparacion.vue:40`,
  `buscar.vue:61`, `participa/index.vue:44,50,54`, `normativa.vue:489,543,548`,
  `tramites/index.vue:715`, `tramites/[slug].vue:414,571,696,702,734,783`,
  `MigaDePanGovco.vue:58`, `CarruselGovco.vue:235`. El propio código usa `NuxtLink` en otros sitios
  (`layouts/default.vue:79`, `CabeceraGovco.vue:79`), así que la inconsistencia es de detalle, no
  de criterio.
- **Qué falta:** sustituir por `NuxtLink` los destinos internos —incluida la miga de pan, que es la
  que más se usa— y dejar `<a>` sólo para los externos y las anclas.
- **Verificación:** navegar entre secciones no recarga el documento (Network: sin petición de
  documento completo).

---

**D-23 · No hay Declaración de Conformidad de Accesibilidad publicada**

- **Requisitos:** Resolución 1519 de 2020, Anexo 1 num. 9.3, con los **12 elementos mínimos** que
  `Sección 3 §3.6.3` reproduce: entidad, URL de la sede, norma de referencia, fecha de evaluación,
  alcance, herramientas con versión, auditor, estado de cumplimiento, criterios no aplicables y
  excepciones, excepciones documentadas con mitigación, hallazgos pendientes con severidad y plan,
  y fecha de próxima revisión (≤12 meses).
- **Evidencia:** `/accesibilidad` existe y es honesta —declara la norma, los canales de reporte y
  **dice expresamente que no acredita conformidad** (`accesibilidad.vue:99-113`)—, pero no publica
  la declaración: falta la fecha de evaluación, el alcance, las herramientas, el auditor y el
  estado. Es exactamente lo que el propio requisito exige publicar.
- **Qué falta:** ejecutar la auditoría (D-19), firmar el acta y publicarla con los 12 elementos; y
  designar el responsable de la próxima revisión.
- **Verificación:** existe una página que declara estado de cumplimiento, alcance, herramientas y
  fecha de próxima revisión.

### 5.3 🟡 Medios (20)

Incumple un Should, o impide acreditar algo que el expediente exige.

---

**D-24 · El mapa del sitio y el `sitemap.xml` son tres listas escritas a mano**
- **Requisitos:** RF-B1-010 (Must, «autoactualizado al cambiar la navegación»), RF-B3-044.
- **Evidencia:** el menú vive en `layouts/default.vue:46-94`; el mapa HTML repite los destinos a
  mano (`mapa-del-sitio.vue:33-115`); el XML los repite otra vez (`sitemap.xml.ts:35-70`), y las
  subcategorías de Participa y las políticas se vuelven a enumerar en `:54-70`. El comentario de
  `mapa-del-sitio.vue:16-17` afirma que ese mapa «alimenta el `sitemap.xml`»: **no lo alimenta**.
- **Qué falta:** derivar las tres vistas de una única fuente (el árbol de navegación) y dejar la
  lista escrita sólo como dato de entrada de esa fuente.
- **Verificación:** añadir una página al menú → aparece en el mapa HTML y en el XML sin tocar nada más.

---

**D-25 · `robots.txt` declara un dominio distinto del que el sitio configura, y el `sitemap.xml` no publica `lastmod`**
- **Evidencia:** `sitio/public/robots.txt:11` publica
  `Sitemap: https://www.santamarta.gov.co/sitemap.xml` mientras `nuxt.config.ts:92` configura por
  defecto `staging.santamarta.gov.co`; la interfaz `Direccion` de `sitemap.xml.ts:21-28` documenta
  un campo «fecha de su último cambio real» **que no existe en el tipo** ni se emite (`:107-116`).
- **Qué falta:** un único origen de verdad del dominio (variable de entorno) y, si se conoce,
  `lastmod` por URL.
- **Verificación:** el dominio de `robots.txt` coincide con el de `runtimeConfig` en cada entorno.

---

**D-26 · Los vínculos visitados no se distinguen**
- **Requisitos:** RF-B1-082 (Must), RF-B2-042, RF-B3-048; es uno de los tres *tips* que el corpus
  recuperó de los PPTX de criterios (`_global/auditoria-cobertura.md:64`).
- **Evidencia:** no hay ninguna regla `:visited` en `sitio/app/assets/css/sitio.css` ni en los
  componentes; la única del Kit apunta a `.link-tipografia-govco` (`public/govco/all.css:148`), que
  el sitio no usa.
- **Qué falta:** un color de visitado (preferiblemente el púrpura que el corpus cita) con contraste
  ≥4,5:1 sobre los fondos reales del sitio.
- **Verificación:** visitar un enlace y volver → el color cambia.

---

**D-27 · No hay páginas de confirmación ni procesos por pasos** — **pasos ✅ CERRADO · confirmación ⛔ BLOQUEADA por el contrato** (§18)
- **Requisitos:** RF-B1-083 (Must, confirmación con nº de referencia, próximos pasos y tiempo
  estimado), RF-B2-077 (Must, «pasos numerados»), CAG-20 (línea de avance).
- **Evidencia del defecto:** no existía `stepper` en el sitio ni ninguna plantilla «Paso N de M».
- **Corrección (mitad de pasos):** el formulario de petición se organiza en **tres pasos** —Solicitud,
  Datos del solicitante y Autorización y envío— con línea de avance, encabezado «Paso N de 3: …» al
  estilo de `Sección 3:360`, estado por paso («actual / completado / pendiente», en texto y no sólo
  con color) y **salto libre** entre pasos, que es lo que CAG-20 permite. Avanzar exige que el paso
  esté completo: si no, se marcan los campos y el foco va al primero. El estado «completado» sale de
  la validación, no de la posición, para que saltar al final no convierta en completado lo que no lo
  está.
- **Qué queda (mitad de confirmación, RF-B1-083):** la pantalla de resultado con **número de
  radicado**, próximos pasos y tiempo estimado. **No se puede hacer hoy**: el formulario no radica
  —no hay backend que reciba la solicitud—, así que no existe ningún radicado que mostrar y una
  pantalla de confirmación sería un número inventado. Queda bloqueada por el contrato, no por
  decisión de diseño.
- **Verificación:** `make diseno` comprueba los pasos (patrón «Paso N de M», un solo paso visible,
  `aria-current="step"`, que avanzar sin rellenar frene con el foco en el primer campo inválido, el
  salto libre y que saltar no marque nada como completado).

---

**D-28 · Faltan doce componentes del Kit que el expediente marca como obligatorios por flujo**
- **Requisitos:** RF-B3-062 (acordeón), RF-B3-063 (modal), RF-B3-064 (*toast*), RF-B3-068 (galería),
  RF-B3-070 (indicador de carga con aviso a los 10 s), RF-B3-078 (desplegable con filtro),
  RF-B3-079 (ojo de contraseña), RF-B1-084 (ejemplos de formato), RF-B3-075, más §2.5 del
  expediente (tablas, pestañas, *stepper*, área de servicio, etiquetas de filtro).
- **Evidencia del cruce con `vendor-src/`:** clases del Kit cargadas en `all.css` y con **cero
  apariciones** en `sitio/app`: acordeón (10 clases), alerta modal (22), alerta/tostada (17),
  carga de archivos (22), desplegables y calendario (11 de 12), entradas de texto del Kit, buscador
  predictivo (7), botones secundarios (5 variantes), carrusel múltiple, iconografía por fuente
  (`.govco-icon`), tipografía (`text1`/`text3`/`bold-govco`/`bold-verdana-govco`) y el catálogo de
  color `.govco-bg-*`. Y un componente **completo y nunca instanciado**:
  `GaleriaAplicacionesGovco.vue` (354 líneas, 13 de 13 clases del Kit), ausente de todo el árbol.
- **Qué falta:** decidir, por cada componente, entre implementarlo o declarar formalmente que no
  aplica —y en ese caso corregir §2.5 y ADR-0015, que hoy se contradicen (C-06)—. La galería, en
  particular, es un RF Should ya construido: o se coloca en la cabecera (donde el Kit la sitúa) o
  se retira del código.
- **Verificación:** `grep` de cada clase devuelve al menos un uso, o el ADR explica su ausencia.

---

**D-29 · El sitio no publica metadatos de compartición ni URL canónica** ✅ **CERRADO** (§17.2)
- **Requisitos:** RF-B1-088 (Should), RNF-B2-021.
- **Evidencia del defecto:** sólo `index.vue:26-35` declaraba `description`; ninguna de las 19
  páginas declaraba `og:*`, `twitter:*` ni `link rel="canonical"`.
- **Corrección:** `useMetadatosComparticion` (`app/composables/`), declarado una vez en la
  disposición y afinado por la ficha de trámite y por cada política. Canónica y `og:url` absolutas
  desde `runtimeConfig.public.dominio`, con la cabecera `Host` sólo como respaldo; sin canónica en el
  404. `og:image` es el logotipo real de la Entidad. **La descripción no se inventa**: sólo se emite
  cuando la página la declara, y la ficha de un trámite usa su `resumen` del contrato.
- **Verificación:** comprobación propia en `make diseno` (19 criterios, 0 fallos) — canónica, `og:*`
  y `og:locale` presentes en una página real; sin canónica ni `og:url` en el 404.

---

**D-30 · `make tipos` escribe en un fichero que nadie importa**
- **Evidencia:** `Makefile:41` genera `sitio/types/api.d.ts`; el código importa
  `~~/types/openapi` (`tramites/index.vue:46`, `tramites/[slug].vue:44`), que es el fichero que
  **no se regenera**. `panel` sí coincide (`Makefile:40` → `src/types/openapi.d.ts`).
- **Riesgo:** el contrato cambia, `make tipos` corre en verde y el sitio sigue compilando contra
  tipos viejos sin que nadie lo note.
- **Verificación:** el destino del generador y el import coinciden, y `git diff` queda limpio tras
  `make tipos`.

---

**D-31 · Las listas canónicas están duplicadas en tres o cuatro sitios**
- **Evidencia:** las cinco políticas se enumeran en `PiePaginaGovco.vue:211-234`,
  `politicas/[slug].vue:32-58`, `mapa-del-sitio.vue:82-110` y `sitemap.xml.ts:63-70`; las seis
  subcategorías de Participa en `layouts/default.vue:63-89`, `participa/index.vue:13-26` y
  `sitemap.xml.ts:54-61`; el catálogo de tipos de norma, en `normativa.vue:41-68` y `pqrsd.vue:52`
  (el propio comentario de `pqrsd.vue:52` admite la duplicación).
- **Riesgo:** añadir una política o una subcategoría exige acordarse de cuatro sitios; el que se
  olvide produce una inconsistencia silenciosa (ya ocurre: D-45).
- **Verificación:** cada lista existe en un solo módulo y los demás la importan.

---

**D-32 · El tope del menú se aplica en el navegador y sólo avisa en desarrollo**
- **Requisitos:** RN-01-D02 (la violación del tope **se rechaza al dar de alta**), RF-01-D02
  (CRUD con tope), CAG-09.
- **Evidencia:** `MenuNavegacionGovco.vue:143-171` recorta a 7 ítems y 4 secciones y **sólo avisa
  por consola si `import.meta.dev`**; en producción, un menú de nueve ítems se recortaría en
  silencio y el ciudadano no vería dos secciones.
- **Qué falta:** la validación en el origen (CMS/datos) que rechace el alta; el componente debe
  poder fallar de forma visible en el build, no degradar contenido en producción.
- **Verificación:** intentar publicar un octavo ítem → rechazado con mensaje.

---

**D-33 · El panel no es responsive**
- **Evidencia:** `AdminLayout.vue:180-190` fija la barra lateral en `w-16`/`w-64` (64-256 px) sin
  variante móvil ni botón de menú; en una pantalla de 375 px queda menos de la mitad del ancho para
  el contenido. Las utilidades responsive del panel sólo se usan en la cabecera (`:284`, `:317`).
- **Requisitos:** RNF-B1-032 y RF-B1-085 se escribieron para la sede pública, pero la GUIA Maestra
  exige la misma disciplina en el panel, y un funcionario que atienda desde una tablet queda fuera.
- **Verificación:** a 768 px el menú se colapsa y el contenido ocupa el ancho.

---

**D-34 · La paleta de comandos imprime el nombre del icono como texto**
- **Evidencia:** `AdminLayout.vue:150-160` construye los comandos con `icon: item.icono`, donde
  `icono` es el nombre de un icono de FontAwesome (`'gauge-high'`, `'inbox'`…);
  `CommandPalette.vue:77-79` los pinta con `{{ c.icon ?? '›' }}`, es decir, **como texto**. Al
  abrir la paleta con `⌘K` se lee literalmente «gauge-high» junto a cada comando.
- **Qué falta:** renderizar con `<FaIcon :icon="c.icon" />`, como hace el resto del panel
  (`AdminLayout.vue:231`).
- **Verificación:** abrir la paleta: cada comando muestra su icono.

---

**D-35 · Código y dependencias muertas**
- **Evidencia:** `panel/package.json` declara `@tanstack/vue-query`, `vue3-toastify` y `zod` sin
  ningún uso; `sitio/package.json` declara `axios`, `zod`, `@tanstack/vue-query` y `@pinia/nuxt`
  sin ningún uso —el estado del sitio se resuelve con `useState`, no con Pinia—; en el panel,
  `BaseModal`, `DataTable`, `EmptyState`, `Skeleton`, `StatusBadge` y `BaseTimeline` no tienen
  consumidor; `tokens.css:36-49` define dos temas `[data-brand]` que nadie activa;
  `useAccesibilidad.ts:61-63` exporta `restablecer` sin consumidor.
- **Riesgo:** seis dependencias no usadas son seis superficies de vulnerabilidad y de actualización
  que el proyecto no necesita.
- **Verificación:** `npm ls --omit=dev` sin paquetes sin importar.

---

**D-36 · No hay decisión escrita sobre i18n y lenguas étnicas**
- **Requisitos:** RN-B1-012 (castellano con traducciones admisibles y lenguas étnicas como
  complemento), RNF-TX-D02 (diferido), RN-TX-D05 (fallback al castellano), RF-B3-055.
- **Evidencia:** el diseño omitió el botón de idioma con un comentario (`BarraSuperior.vue:8-11`);
  ADR-0015 §1 lo justifica sólo frente a CAG-06 y **no menciona** las lenguas étnicas ni el
  fallback, que son requisitos propios del corpus.
- **Qué falta:** un ADR que fije el alcance: sólo castellano hoy, con la ruta de incorporación de
  lenguas étnicas y la regla de fallback.
- **Verificación:** el ADR existe y el corpus queda reconciliado (C-22 y C-08).

---

**D-37 · La tabla de plazos del PQRSD usa la clase del Kit en el elemento equivocado**
- **Evidencia (cruce con `vendor-src/`):** el Kit exige `div.tabla-govco > table`
  (`examples/general/tablas.html:21-22`) y su CSS estiliza con descendencia
  (`tablas.css:106-110`); el sitio pone la clase **en el `<table>`** dentro de un
  `.table-responsive` (`pqrsd.vue:221-222`). Consecuencia: las reglas de cebra (`:276`), de
  *hover* (`:281`) y de fila activa (`:271`) **no se aplican nunca**.
- **Qué falta:** envolver la tabla en el `<div class="tabla-govco">` que el Kit espera.
- **Verificación:** la tabla muestra filas alternas y el estilo del Kit.

---

**D-38 · La medida de línea está por encima del rango que el corpus exige**
- **Requisitos:** RF-B2-081 y RNF-B1-031 (60-80 caracteres por línea), CAG-27 (45-75).
- **Evidencia:** el contenido se limita con `col-lg-8` (≈736 px útiles dentro del contenedor de
  1 140 px), lo que a 16 px de Verdana da del orden de 90-100 caracteres por línea. El único
  contenedor con medida explícita es el aviso del pie (`PiePaginaGovco.vue:433`, `max-width: 60ch`).
- **Qué falta:** una medida tipográfica global en `ch` para el contenido de prosa y su medición.
- **Verificación:** contar caracteres en la línea más larga de una página de contenido.

---

**D-39 · No hay estilo de foco visible declarado ni medido**
- **Requisitos:** RF-B1-048 (contraste de foco ≥3:1), CAG-10 (≥4,5:1), WCAG 2.4.7 y 2.4.11,
  `Sección 3:281,420`.
- **Evidencia:** `sitio/app/assets/css/sitio.css` no declara ninguna regla de foco fuera del modo
  de alto contraste (`:63-68`); el foco de los controles depende de Bootstrap **5.0.2**, versión
  que **no usa `:focus-visible`** (se introdujo en 5.2) y cuyo anillo por defecto nunca se contrastó.
  El Kit sólo estiliza el foco de **sus propios** componentes.
- **Qué falta:** un estilo de foco global del sitio, con ratio medido sobre fondo claro y sobre los
  fondos teñidos del Kit; es el complemento natural del modo de alto contraste que ya existe.
- **Verificación:** tabular por una página con teclado y medir el contraste del indicador.

---

**D-40 · La sede no ofrece control de espaciado de texto, y el panel sí**
- **Requisitos:** RF-B3-014 (espaciado configurable), WCAG 1.4.12.
- **Evidencia:** el panel implementa `.a11y-spacing` con interlínea 1,75, `letter-spacing` 0,05 em y
  `word-spacing` 0,1 em (`panel/src/assets/styles/main.css:105-110`); **el sitio no tiene
  equivalente** y no hay prueba de que resista el espaciado del criterio.
- **Qué falta:** decidir si el control entra en la barra de accesibilidad del sitio (donde el RF lo
  sitúa) y, en todo caso, verificar que el diseño no se rompe con el espaciado de 1.4.12.
- **Verificación:** aplicar el espaciado del criterio y comprobar que no hay recortes ni solapes.

---

**D-41 · La comprobación de tipos no corre en la compilación**
- **Evidencia:** `nuxt.config.ts:105` (`typeCheck: false`) y `Makefile:100-103`, cuyo objetivo
  `compilar` ejecuta sólo `nuxt build` y `vite build` en `panel` (que sí encadena `vue-tsc -b`).
  Es decir: **el panel comprueba tipos y el sitio no**.
- **Qué falta:** activar la puerta de tipos del sitio de forma explícita (`nuxt typecheck`) dentro
  de `make compilar`, que ya existe como script (`sitio/package.json:11`).
- **Verificación:** introducir un error de tipos y comprobar que `make compilar` falla.

---

**D-42 · Los 18 módulos del panel no declaran permiso y el menú no filtra por rol**
- **Requisitos:** GUIA-MAESTRA-COMPLETA, matriz de 25 puntos (18-25: botones y rutas según permiso,
  `panel-administrative`, coherencia FE↔BE), RF-B1-079 (permisos por módulo).
- **Evidencia:** sólo la portada declara `permiso: 'panel-administrative'` (`router/index.ts:66`);
  las 18 rutas generadas (`:41-59`) declaran título y `requiereSesion` pero **ningún permiso**, y el
  menú lateral muestra los 18 grupos sin condición alguna (`AdminLayout.vue:29-87`).
- **Qué falta:** el permiso por módulo en cada ruta y el filtrado del menú por rol; sin eso, un
  editor de normativa vería —y podría abrir— PQRSD, usuarios y auditoría.
- **Verificación:** entrar con un rol de editor de sección → el menú no ofrece los módulos ajenos.

---

**D-43 · El entorno de staging es indexable**
- **Evidencia:** `sitio/public/robots.txt` permite el rastreo total (`Disallow:` vacío);
  `nuxt.config.ts:92` usa `staging.santamarta.gov.co` como dominio por defecto; no hay cabecera ni
  meta de `noindex` condicionada al entorno. El panel sí lo hace bien (`panel/index.html:17`).
- **Riesgo:** un despliegue de pruebas puede quedar indexado como si fuera la sede oficial, con
  contenido «en preparación», y competir en resultados con la sede real.
- **Qué falta:** `noindex` y `robots.txt` distintos en entornos no productivos, atados a la misma
  variable que ya define el dominio.
- **Verificación:** en staging, `robots.txt` responde `Disallow: /` y las páginas llevan `noindex`.

### 5.4 ⚪ Menores (9)

Pulido; no cambian el cumplimiento pero sí la calidad del conjunto.

| ID | Hallazgo | Evidencia | Qué falta |
|---|---|---|---|
| D-44 | **Dos nombres para lo mismo:** la portada llama «Servicios» a un destino (`/servicios`) que es un aviso, mientras el menú y la portada del catálogo usan `/tramites`, que sí tiene contenido | `index.vue:59,83` frente a `layouts/default.vue:55` | Unificar en `/tramites` (o hacer que `/servicios` redirija) y retirar la tarjeta que anuncia lo que no hay. |
| D-45 | **El mapa del sitio marca `/participa` como no publicada** aunque `participa/index.vue` publica contenido real (las seis subcategorías) | `mapa-del-sitio.vue:44` frente a `participa/index.vue:29-56` | Corregir el estado; es el síntoma exacto de la duplicación de listas (D-31). |
| D-46 | **Dos activos distintos de logotipo:** `sitio/public/logo-entidad.png` (480×270, md5 `dc93ec…`) frente a `docs/logo500Or.png` y los dos del panel (5760×3240, md5 `ef5095…`) | `md5sum` de los cuatro ficheros | Un único activo canónico, versionado y declarado, con sus derivados generados; hoy el sitio y el panel no publican el mismo emblema. |
| D-47 | **Título base inconsistente en el panel:** la pestaña dice «SGDI · Alcaldía Distrital de Santa Marta» al cargar y «Panel · Sede Electrónica de Santa Marta» tras navegar | `panel/index.html:17` frente a `router/index.ts:38` | Un solo nombre de producto. |
| D-48 | **El bloque de ayuda de la portada invita a reportar y no ofrece ningún canal** | `index.vue:123-129` | Enlazar a `/atencion` y al correo de la declaración de accesibilidad. |
| D-49 | **Un comentario del pie ya no describe su propio dato:** dice que la línea anticorrupción «hoy coincide con la línea de atención al ciudadano» cuando los números publicados son distintos | `PiePaginaGovco.vue:159-162` frente a `:157,162` | Corregir el comentario (o el dato) para que no induzca a error en la próxima revisión. |
| D-50 | **El menú expone un hueco `buscador` que nunca se rellena** | `MenuNavegacionGovco.vue:741-743` frente a `layouts/default.vue:187`, que lo invoca sin slot | Rellenarlo (el Kit sitúa el buscador dentro del menú colapsado) o retirar el hueco. |
| D-51 | **Clases inertes y clases sin definir:** `controls` (`CarruselGovco.vue:270`) y `dropdown-title` (`GaleriaAplicacionesGovco.vue:264`) no existen en ninguna hoja cargada; `enlace-ficha`, `grupo-encabezado`, `origen-ficha` y `resumen` no están definidas en ninguna parte del sitio | cruce con `all.css` y `bootstrap.min.css` (0 coincidencias) | Retirarlas o definirlas; una clase que no hace nada es una promesa incumplida en el marcado. |
| D-52 | **El manifiesto del sitio está incompleto:** no declara `start_url` ni `scope` | `sitio/public/site.webmanifest` | Añadirlos, junto con `lang` y `dir`, para que la instalación en móvil funcione como se espera. |

---

## 6. Contradicciones y decisiones pendientes

No son defectos del código: son **sitios donde el proyecto se contradice a sí mismo** y donde
alguien tiene que decidir. Mientras no se decidan, cualquier auditoría —esta incluida— tendrá que
elegir una fuente arbitrariamente. Se listan con la decisión que cada una exige.

| # | Contradicción | Fuentes | Decisión que exige |
|---|---|---|---|
| C-01 | **Tres anclas distintas** para el enlace «Saltar al contenido principal» | `#contenido-principal` (`Sección 2:278`, `Sección 2:307`) frente a `#main-content` (`Sección 6:200-202,254,276`) frente a `#main` (`Sección 3:422`) | Fijar `#contenido-principal` (es la que el Kit documenta y la que el sitio implementa: `layouts/default.vue:197`) y corregir las otras dos secciones. |
| C-02 | **Dos URLs de CDN distintas** para el Kit | `https://cdn.www.gov.co/layout/v5/all.css` (`Sección 2:160-161,304`; `Sección 6:179,193`) frente a `https://cdn.gov.co/layout/v5/govco-9.2.css` (`Sección 6:273,290`) | Ninguna de las dos se usa: ADR-0002 demuestra que la primera devuelve HTML y el proyecto vendoriza. Corregir el expediente y dejar una sola referencia. |
| C-03 | **El snippet de `Sección 6` no lleva `integrity`**, pero su propia regla 9 lo exige | `Sección 6:260` frente a `Sección 6:193` | Si se sirve local, retirar la regla de SRI; si se sirve del CDN, añadirla. |
| C-04 | **24 × 24 px: ¿AA o AAA?** | «sólo en WCAG 2.2 AAA» (`Sección 6:256`) frente a «2.5.8 Target Size (Minimum) 24×24 px = AA» (`Sección 3:57`) | Es AA. Corregir `Sección 6`. |
| C-05 | **Conteo de componentes del Kit:** 23, 24 o 31 | «8+11+4 = 23» (`Sección 2:166,181`, con 19 filas reales) frente a «24 componentes: 8 transversales, 19 generales y 4 de formulario» (`adr/README.md:111`, = 31) | Contar sobre `vendor-src/`: **23 ejemplos reales + 5 esqueletos vacíos**. Corregir los tres documentos. |
| C-06 | **ADR-0015 declara «no aplica» CAG-19, CAG-21, CAG-22, CAG-24 y CAG-25**, pero §2.5 del propio expediente exige tablas en Trámites, acordeón en Trámites y Detalle, pestañas y *stepper* en Detalle, y *toast* en Notificaciones globales | `adr/README.md:404-423` frente a `Sección 2:316-324` | Los criterios no pueden ser «no aplicables» si sus componentes son obligatorios. O se implementan, o §2.5 se corrige, o se declaran como desviación con fecha y motivo. **Hoy, además, la premisa ya es falsa: hay una tabla (`pqrsd.vue:222`).** |
| C-07 | **La matriz de trazabilidad usa dos estados útiles** («implementado», «cubierto por pruebas o interfaz») mientras ADR-0015 promete tres (satisfecho, no aplica, desviación declarada) | `trazabilidad.md:12-13` frente a `adr/README.md:456-460` | Adoptar el vocabulario de tres estados; sin él, una desviación declarada se lee como cumplimiento. |
| C-08 | ~~ADR-0015 omite el botón de idioma apoyándose en FUN-008 (sólo castellano), pero FUN-010 y la GUIA Maestra exigen «enlaces de traducción» en la barra superior~~ **Resuelta por ADR-0016**: la decisión de una sola lengua se escribe, sustituye a FUN-010 y fija la ruta de entrada de las lenguas étnicas (§15). | `adr/README.md:404-408` frente a `Sección 5:213` y `GUIA Maestra:2552-2563` | Decidir y escribirlo en el corpus: el idioma se difiere (decisión #16), y esa decisión debe sustituir a FUN-010 y a CAG-06, no convivir con ellos. |
| C-09 | **La numeración de los ítems de los PDF oficiales está mal citada**, pese a declararse reproducción textual | `Sección 5:191-200` (los seis ítems desplazados; FUN-005 fusiona 3 y 4) y `Sección 4:164,170-175` (pares 3↔4 y 7↔8 invertidos) frente a los PDF verificados por coordenadas | Renumerar las citas con `pdftotext -bbox-layout` y dejar de llamarlas «textuales» hasta que lo sean. Un auditor externo que compare con el PDF encontrará el desfase. |
| C-10 | **SEG-012 exige literalmente `Public-Key-Pins`** y el propio expediente lo prohíbe | ítem 12 del PDF de seguridad frente a `Sección 4:451` y `adr/README.md:196-211` | Mantener la prohibición (HPKP está retirado de los navegadores) y anotar la divergencia respecto del PDF oficial. |
| C-11 | **Tailwind, TanStack y FontAwesome: prohibidos por ADR y exigidos por la GUIA Maestra** | `GUIA-MAESTRA-COMPLETA.md:497,1888` frente a `adr/README.md:93-96,118-119` | Decidir el alcance: si la prohibición vale sólo para el sitio público, decirlo; el **panel usa Tailwind y FontAwesome hoy** (D-14, D-35). |
| C-12 | **La estructura del repositorio contradice a los ADR y a la matriz:** los documentos hablan de `frontend/` + `backend/`, el repositorio tiene `sitio/` + `panel/` | `GUIA-MAESTRA-COMPLETA.md:35-41`, `adr/README.md:21-23`, `trazabilidad.md:48-81` frente al árbol real | Actualizar los ADR y regenerar la matriz; es la causa raíz de D-06. |
| C-13 | **PHP 8.4+ frente a 8.5** | `GUIA-MAESTRA-COMPLETA.md:513` frente a `adr/README.md:30` («verificado 8.5.10») | Alinear. Menor, pero es el tipo de dato que un auditor comprueba. |
| C-14 | **Los criterios declarados no cuadran con los contados:** «Diseño CAG-NN 57» (existen 34) y «ACC-NN 49[F]+75[A]=124» (las tablas suman 81 filas; WCAG 2.1 tiene 78 criterios) | `GUIA Maestra:3414-3415` frente a `Sección 2:265-304` y `Sección 3` §3.3 | Recontar y publicar la cifra verificable. |
| C-15 | **La GUIA Maestra no reproduce §2.6.7**, la verificación automática de diseño | `Sección 2:399-414` frente a `GUIA Maestra:1054-1061`, que cierra el checklist en §2.6.6 | Incorporarla: la guía consolidada es la que lee quien no abre el expediente entero. |
| C-16 | **WCAG 2.1 frente a 2.2** y el objetivo táctil asociado | `Sección 3:34` y §3.1.4 frente a `Sección 6:95` y `Sección 6:256` | Fijar un nivel (2.1 AA es el exigible por la Resolución 1519; 2.2 como mejora) y no mezclarlos. |
| C-17 | **`transparencia.md` dice que `/transparencia` es un stub retirado y `trazabilidad.md` marca FUN-016 a FUN-020 como implementados** con una vista que no existe | `transparencia.md:8-11` frente a `trazabilidad.md:102-106` | Es el mismo problema que D-06, con nombre y apellidos: la matriz describe otro producto. |
| C-18 | **ACC-001 a ACC-008: seis códigos citados, ocho exigidos y ninguno definido** | `Sección 6:65,94-95,140` y `trazabilidad.md:37-42` frente a la ausencia de enunciados | Escribir los ocho enunciados o retirar los códigos. |
| C-19 | **La evidencia de accesibilidad cita «axe-core 4.13.0 (ago-2026)»** mientras el resto de referencias se declaran vigentes y sin versión | `Sección 3:819-820` frente a `Sección 2:407` | Anclar versiones de las herramientas en el expediente, no sólo en un anexo. |
| C-20 | **`localStorage`: prohibido para tokens y exigido para la preferencia de accesibilidad** | `GUIA-MAESTRA-COMPLETA.md:2543` frente a `adr/README.md:314` | Acotar la prohibición por contenido (credenciales sí, preferencias no) y escribirlo en ambos. |
| C-21 | **El Kit dibuja el logotipo de la autoridad con la imagen del MinTIC y sin `alt`** (`all.css:8520-8522`) | Frente a CAG-12 y a la lógica de identidad del propio Kit UI §2.2.5 | El diseño ya decidió lo correcto (un `<img>` propio con `alt`, `PiePaginaGovco.vue:295`). **Falta declararlo como desviación** en ADR-0015, que hoy lo describe como pendiente. |
| C-22 | **El corpus se contradice sobre el idioma:** RF-B1-001 (Must) pide «opción de traducción a la derecha» y RF-B3-055 (Could) la difiere | `01:19` frente a `01:24` y la decisión #16 | Resolver en el corpus, no en un comentario de código (hoy está resuelto sólo en `BarraSuperior.vue:8-11`). |
| C-23 | **El corpus se contradice sobre los adjuntos del PQRSD:** RF-B1-033 prohíbe restricciones técnicas y RF-03-D04/RN-03-D04 exigen validar MIME, tamaño y antivirus | `04:20` frente a `03` (delta) y `rn-delta:40`; es la contradicción **C-01 del propio README** del corpus | Decidir el umbral técnico defendible y documentarlo: la prohibición se refiere a no rechazar por formato o tamaño arbitrarios, no a renunciar a límites de servidor. Hoy el diseño no ha decidido nada (D-13). |
| C-24 | **§2.6.2 prohíbe reimplementar los componentes del Kit** («se instancian desde el repositorio v5, no se reimplementan a mano») y ADR-0011/0012 reimplementan su comportamiento en Vue | `Sección 2:362` frente a `adr/README.md:282-287,309-314` | La decisión del proyecto es correcta y está **demostrada**: el JS del Kit no es cargable (ver §9.5). Falta corregir §2.6.2 para que describa la realidad en lugar de prohibirla. |
| C-25 | **La barra de accesibilidad: oculta entre 768 y 992 px (Kit) frente a disponible y operable en tablet (corpus)** | `Sección 2:398` y CAG-07 frente a RNF-07-D01, HU-07-D02 y `07:174` | Decidir con ADR. La barra es el **único** mecanismo de contraste y tamaño del sitio: ocultarla en tablet y móvil deja sin esa función a quien más la necesita (D-21). |

---

## 7. Cobertura y métricas

### 7.1 Cobertura de requisitos

Con el denominador declarado en §1.3 (152 RF y 31 RNF):

| Estado | RF | % | RNF | % |
|---|---|---|---|---|
| ✅ Cumple | 32 | 21 % | 5 | 16 % |
| 🟡 Parcial | 32 | 21 % | 7 | 23 % |
| ❌ No cumple | 68 | 45 % | 8 | 26 % |
| ⛔ No verificable | 8 | 5 % | 8 | 26 % |
| ➖ No aplica / diferido | 12 | 8 % | 3 | 10 % |
| **Cumple o parcial** | **64** | **42 %** | **12** | **39 %** |

### 7.2 Cobertura de los criterios de aceptación de diseño (CAG-01 a CAG-34)

Segundo eje de medida, sobre la línea base de aceptación (`docs/Sección 2 §2.4`):

| Estado | CAG | Cuáles |
|---|---|---|
| ✅ Cumple | **15** | CAG-01, 02, 04, 07, 08, 09, 11, 12, 13, 14, 15, 26, 30, 33, 34 |
| 🟡 Parcial | **7** | CAG-05 (enlaza a `gov.co` y no a `/home/`), 10 (foco sin medir), 16 (falta la leyenda de obligatorios), 17, 23, 27 (medida de línea), 29 |
| ❌ No cumple | **5** | CAG-21 (modal), 22 (*toast*), 24 (tablas), 25 (acordeón), 31 (galería sin instanciar) — CAG-20 pasó a ✅ con la línea de avance del formulario (§18) |
| ⛔ No verificable | **3** | CAG-03 y CAG-28 (contraste medido), CAG-32 (WCAG AA con axe) |
| ➖ No aplica o desviación declarada | **3** | CAG-06 (idioma), CAG-18 (umbral reexpresado), CAG-19 (calendario) |

**15 de 34 cumplen (44 %); 22 de 34 cumplen o cumplen parcialmente (65 %).** El grupo de los seis
no cumplidos tiene un rasgo común que conviene ver: **cinco son componentes del Kit que el
expediente pide en páginas que aún no existen** (detalle de trámite, notificaciones) y uno es la
galería ya construida y nunca colocada. Es decir: no son fallos de ejecución, son páginas que
faltan.

### 7.3 Cobertura de las puertas de calidad

| Puerta | Objetivo | Estado real |
|---|---|---|
| `make contrato` | Redocly + `ContratoDeriva` | 🟡 El lint puede correr; **la prueba `ContratoDeriva` no existe** |
| `make formato-verificar` | Pint | ✅ Verificable |
| `make analisis` | PHPStan nivel 8 | ✅ Verificable |
| `make pruebas` | Suite del backend | ✅ Verificable (hay 7 ficheros de prueba reales) |
| `make unidad` | Pruebas de los dos frontends | ❌ **Ninguno de los dos declara script `test`** |
| `make tipos` | Generar tipos del contrato | 🟡 El panel sí; **el sitio escribe un fichero que nadie importa** (D-30) |
| `make compilar` | Tipos y compilación | 🟡 El panel comprueba tipos; **el sitio no** (D-41) |
| `make diseno` | Criterios de diseño en navegador | ❌ **`panel/tests/conformidad-diseno.mjs` no existe** |
| `make accesibilidad` | axe sobre las 15 vistas | ❌ **`panel/tests/accesibilidad.mjs` no existe** |
| `make imagenes` | Bases fijadas por resumen | ❌ **`scripts/` no existe** |
| `make respaldo` | Copia y restauración | ❌ **`scripts/` no existe** |

**Una de once puertas es plenamente ejecutable.** `make comprobar` —que encadena ocho— **no puede
terminar en verde**, y eso convierte el mensaje «Todas las puertas en verde» (`Makefile:54`) en una
afirmación que nadie puede haber visto.

### 7.4 Lo que el corpus pide y la norma no exige (y al revés)

Útil para no gastar esfuerzo donde no rinde.

| Requisito del corpus | ¿Lo exige la norma/el Kit? | Comentario |
|---|---|---|
| `RF-B1-044` — enlace al Centro de Relevo en la barra de accesibilidad | No lo exige el Kit UI, sí la práctica de accesibilidad colombiana | Coste bajo, valor alto para discapacidad auditiva; hoy falta (D-11). |
| `RNF-07-D01` — barra de accesibilidad en tablet | Contradice al Kit (CAG-07) | Es una mejora respecto del Kit, no un incumplimiento: merece decisión, no silencio (C-25). |
| `RF-B1-078` — tablero ITA interno que arranca en cero | No está en el Kit ni en la Resolución 1519; viene de una decisión de la Entidad | El diseño lo incumple hoy (D-05). |
| `RNF-B1-039` — programa RAEE | Norma ambiental, no de sede electrónica | No tiene huella en el diseño; fuera de alcance. |
| CAG-34 — servir el Kit desde el CDN | El Kit lo ofrece, ADR-0002 demuestra que la URL publicada no sirve CSS | El proyecto vendoriza y **acierta**; el expediente debe corregirse (C-02). |
| §2.6.2 — no reimplementar componentes | Regla de proceso del expediente, no norma | El proyecto la incumple con motivo demostrado (C-24). |

---

## 8. Cadenas de dependencia y orden de corrección

### 8.1 Las tres cadenas que bloquean todo lo demás

**Cadena A — Cumplimiento legal de la carcasa.** Cuatro requisitos Must de rango legal o de
aceptación oficial dependen de piezas que no existen, y tres de ellas se necesitan entre sí:

```
D-28 (componente de alerta modal) ──► D-02 (aviso de salida a sitio externo + lista blanca)
D-01 (banner de cookies) ──────────► habilita analítica (RF-B2-089) y cierra el ítem 5 del PDF
D-13 (captcha + carga de archivos) ─► depende del backend (endpoint de subida y verificación)
D-04 + D-05 (honestidad del panel) ─► no dependen de nada: son edición de contenido y de código
```

**Cadena B — Nada puede acreditarse mientras no haya instrumento.** Es la cadena con mejor
relación coste/beneficio del proyecto:

```
D-19 (escribir axe sobre las 15 vistas) ──► RNF-B1-014 y CAG-32 dejan de ser ⛔
                                        ──► D-23 (declaración de conformidad publicable)
                                        ──► D-39 (foco) y CAG-03/CAG-28 medidos
D-06 (Makefile y matriz alineados) ─────► las puertas vuelven a significar algo
```

**Cadena C — El contenido no llega porque falta contrato.** La mayoría de los 68 ❌ no son fallos de
interfaz, son ausencias de origen:

```
contract/openapi.yaml (hoy 3 recursos) ──► D-18 (/entidad → pie y cabecera)
                                       ──► D-03 (búsqueda)
                                       ──► D-07 (noticias)
                                       ──► D-17 (transparencia, normativa, PQRSD, participación)
CMS (RF-B1-076/079) ───────────────────► D-15, D-31/D-32 (listas y topes del menú)
expediente electrónico ────────────────► /seguimiento, PQRSD real, confirmaciones (D-27)
```

### 8.2 Orden de corrección recomendado

**Fase 0 — Correcciones sin dependencias externas (una iteración).** Todo lo que se puede hacer
hoy, sin backend, sin MinTIC y sin contenido nuevo. Es donde está el mejor retorno:

| Orden | Hallazgo | Por qué primero |
|---|---|---|
| 1 | **D-05** quitar las cifras simuladas del panel | Es una afirmación falsa en un producto institucional; se corrige en un fichero. |
| 2 | **D-04** retirar la afirmación de doble factor y el marcador de sesión del panel (la autenticación real es de otra fase) | Igual que el anterior: la interfaz no debe afirmar lo que no ocurre. |
| 3 | **D-12** carrusel en pausa por defecto | Una línea; cierra un Must. |
| 4 | **D-11** persistencia de la preferencia de accesibilidad + Centro de Relevo | Cierra RF-B1-044 y cumple ADR-0012. |
| 5 | **D-22** `<a href>` internos → `NuxtLink` | Mecánico, 12 puntos. |
| 6 | **D-37** `tabla-govco` al `<div>` contenedor | Una línea; recupera el estilo del Kit. |
| 7 | **D-30/D-41** destino de `make tipos` y puerta de tipos del sitio | Dos líneas en `Makefile` y `nuxt.config.ts`. |
| 8 | **D-34/D-47/D-49/D-50/D-51/D-52** pulido (icono de la paleta, títulos, comentarios, clases inertes, manifiesto) | Barato y visible. |
| 9 | **D-43** `noindex` en entornos no productivos | Evita indexar el staging. |
| 10 | **C-06 a C-25** las decisiones y sus ADR | Sin decidir, cualquier corrección posterior puede ir en la dirección contraria. |

**Fase 1 — Instrumentos (antes de seguir construyendo).**
`D-19` (axe sobre las 15 vistas primero, después pruebas de contrato y de componentes base),
`D-06` (alinear el `Makefile` y regenerar o retirar la matriz).

**Fase 2 — Cumplimiento legal de la carcasa.** `D-28` (modal, que desbloquea `D-02`), `D-01`
(cookies), `D-13` (captcha y adjuntos, con el backend), `D-10` (políticas), `D-09` (404).

**Fase 3 — Accesibilidad verificada y declarada.** Cerrar los ⛔ con la puerta de `Fase 1`:
`D-38` (medida de línea), `D-39` (foco), `D-40` (espaciado), `D-21` (decisión de tablet),
`D-23` (declaración de conformidad publicada).

**Fase 4 — Contenido y contrato.** `D-18` (`/entidad`), `D-03` (buscador y su recurso),
`D-17` (secciones obligatorias), `D-07` (noticias), `D-08` (portada por tareas), `D-27`
(confirmaciones y pasos), `D-24`/`D-25` (mapa y sitemap derivados), `D-28` (resto de componentes),
`D-29` (metadatos sociales).

**Fase 5 — Panel y CMS.** `D-15`, `D-16`, `D-33`, `D-42`, `D-14`, y con ellos todo el módulo 12.

### 8.3 Lo que **no** hay que tocar en ninguna fase

Se dice explícitamente porque son las piezas que una corrección apresurada rompería:

1. **La decisión de vendorizar el Kit** (`nuxt.config.ts:57-71`) y de **no cargar su `script.js`**.
   Está demostrada en §9.5: cargarlo rompería la página.
2. **El modo de alto contraste propio** (`sitio.css:24-81`). El del Kit es un stub verificado.
3. **El carrusel reescrito** con los controles fuera de la imagen: cumple CAG-01, CAG-02 y CAG-04.
4. **El menú con estado reactivo** en lugar de manipulación del DOM.
5. **La miga de pan derivada de la ruta** (`layouts/default.vue:132-150`): es lo que hace que
   CAG-11 se cumpla por construcción.
6. **El `<main>` y el enlace de salto en la disposición**, no en cada página.
7. **La política editorial de no publicar relleno.** Es la razón por la que esta auditoría puede
   distinguir «falta» de «está mal», y es un activo, no una carencia.
8. **El reflujo a 320 px** y sus comentarios con la causa medida de cada desborde.
9. **Las mediciones de contraste documentadas** del pie y de los enlaces: son correctas y están
   razonadas; lo que falta es extenderlas, no rehacerlas.

---

## 9. Lo que ya está bien (y no hay que romper «arreglándolo»)

Sección deliberadamente detallada: en una auditoría con 68 requisitos no cumplidos, lo construido
bien es la única razón por la que el resto es recuperable.

### 9.1 Estructura y navegación

- **La disposición concentra la carcasa** (`layouts/default.vue`), de modo que barra superior,
  cabecera, menú, miga, `<main>` y pie **no pueden faltar en una página nueva**. Los criterios
  CAG-08 y CAG-11 se cumplen *por construcción*, no por disciplina.
- **El menú se deriva de una única constante** (`:46-94`) y la miga, del propio menú
  (`:132-150`): es imposible que el nombre de una sección difiera entre el menú y la miga.
- **El componente de menú acota los topes antes de dibujar** (`MenuNavegacionGovco.vue:143-171`) y
  documenta por qué no usa `role="menu"` (`:33-39`): añadir roles de aplicación sin sus
  `menuitem` habría empeorado el árbol accesible del Kit.
- **El teclado del menú está resuelto entero**: flechas para abrir y recorrer, Inicio/Fin, Escape
  que cierra y **devuelve el foco al disparador** (`:399-503`).
- **La página 404 responde 404 de verdad** con `<meta name="robots" content="noindex">`
  (`error.vue:25-33`, `[...ruta].vue:20-26`), y monta la disposición para no dejar al ciudadano
  sin navegación.
- **`robots.txt` y `sitemap.xml` existen y están declarados** (`robots.txt:11`), con dominio
  tomado de configuración y escapado XML correcto (`sitemap.xml.ts:73-91`).

### 9.2 Accesibilidad

- **Salto al contenido** implementado con `clip` y no con `display:none`, con la razón explicada
  (`CabeceraGovco.vue:106-123`), y destino con `tabindex="-1"` en la disposición (`:197`).
- **Modo de alto contraste real** (21:1 de fondo, 15:1 en enlaces, foco amarillo de 3 px,
  `sitio.css:24-81`), construido porque el del Kit es un stub —y está documentado que lo es.
- **`prefers-reduced-motion` respetado** en las dos piezas con movimiento (`CarruselGovco.vue:195-200`,
  `VolverArriba.vue:23-24`).
- **Contraste calculado, no elegido**: los pares del pie y de los enlaces están medidos y anotados
  con la cifra y el motivo (`PiePaginaGovco.vue:39-50`, `sitio.css:183-192,254-266`). Incluso el
  caso incómodo —que el azul celeste de la Entidad no admite texto blanco— está resuelto con la
  medición delante.
- **Áreas táctiles de 44 px corregidas donde el Kit no las cumple** (`sitio.css:396-414,430-465`).
- **Reflujo a 320 px** con la causa de cada desborde identificada (`sitio.css:281-334`).
- **Etiquetas accesibles que empiezan por el texto visible** (WCAG 2.5.3), incluso en el correo de
  la declaración (`accesibilidad.vue:127-136`).
- **Jerarquía de encabezados respetada contra el Kit**: el pie usa `h2`/`h3` y reproduce la
  apariencia del Kit con reglas propias, en lugar de saltar a `h4` (`PiePaginaGovco.vue:32-37`).

### 9.3 Honestidad editorial

Es el rasgo más valioso del trabajo y el que hace esta auditoría posible:

- **No se publica contenido de relleno**: las secciones vacías dicen qué publicarán y por qué no
  lo hacen todavía (`SeccionEnPreparacion.vue:2-11`).
- **No se finge una capacidad que no existe**: no hay botón de pago donde no hay pasarela
  (`tramites/[slug].vue:22-28`), no hay formulario de seguimiento donde no hay expediente
  (`seguimiento.vue:5-9`), no hay consulta de estado simulada.
- **Los datos publicados declaran su procedencia** campo por campo
  (`tramites/[slug].vue:13-18`).
- **La declaración de accesibilidad no declara un nivel de conformidad que no puede acreditar**
  (`accesibilidad.vue:99-113`): dice lo que sí ofrece y lo que falta.
- **Los datos de contacto se omiten antes que inventarse** (`PiePaginaGovco.vue:61-65`).

Esa disciplina es la que convierte 68 ❌ en una lista de trabajo en lugar de una lista de defectos.

### 9.4 La capa visual y el Kit (cruce con `vendor-src/`)

- **`sitio/public/govco/all.css` es byte a byte idéntico al del Kit** (md5
  `e656079915d1f96d5ab8b9bd813ffb17` en los dos): la copia vendorizada es fiel, no un extracto.
- **Bootstrap 5.0.2 se carga desde el propio dominio y antes del Kit**, con la justificación
  escrita de por qué es imprescindible y por qué el orden importa (`nuxt.config.ts:44-61`).
- **Se usan las clases canónicas del Kit** en los componentes que existen: cabecera, menú (incluidas
  las variantes `col-2`/`col-3`/`megamenu`), miga (`inverted`/`invested`, con la errata del Kit
  replicada a propósito), pie, carrusel, tarjetas, botones, buscador básico, barra de accesibilidad
  y volver arriba.
- **La galería de aplicaciones está completa** (13 de 13 clases del Kit) aunque hoy no se instancie:
  el trabajo hecho se conserva aunque haya que colocarlo (D-28).
- **Se evitan las trampas del Kit por decisión y no por suerte**: no se carga su JS, no se copia su
  logotipo del MinTIC, no se reproduce su combinación verde de 3,61:1, no se pone `role="button"`
  en un enlace, no se usa `content:url()` para un logotipo con `alt`.

### 9.5 Por qué no cargar el `script.js` del Kit es la decisión correcta (verificado)

La auditoría del Kit confirma, uno por uno, los motivos que el código del sitio declara:

1. **`barra-superior.js:7-8` lanza `TypeError`** con exactamente la variante que la sede usa
   (barra superior sin botón de idioma): `querySelector` + `addEventListener` sin guarda de `null`.
2. **El contraste del Kit es un stub**: alterna `contrast-govco` y sus dos únicas reglas apuntan a
   su caja de demostración (`all.css:681,685`).
3. **El escalado de letra es acumulativo**: recorre `body *` y escribe `font-size` en línea
   (`script.js:164,179`), sumando sobre el valor ya modificado y dejando sin escalar lo que se
   dibuje después. Es literalmente el defecto que `useAccesibilidad.ts:7-14` describe.
4. **El carrusel del Kit no se detiene y rota cada 2 s** (`carrusel.js:14-16`,
   `carrusel.html:26,28`, `pause:false`), y su código corre en tiempo de parseo, antes de que el
   DOM exista.
5. **La alerta modal abre TODOS los modales al cargar** (`alerta-modal.js:1-14`, con
   `display:block !important` en el CSS).
6. **La carga de archivos tiene un contrato roto**: `data-action` documentado como código de
   función y consumido con `JSON.parse` (`carga-archivos.js:275`) → cae al `catch` y el callback
   nunca se ejecuta.
7. **El bundle `script.js` no se puede cargar junto con los parciales**: declaraciones `let`
   duplicadas (`DatePicker`, `DatePickerDay`, `itemsDropdownCandy`) producen `SyntaxError`.
8. **Ningún HTML del propio Kit referencia `script.js`** (0 coincidencias): son 3 925 líneas que
   el fabricante no usa en sus propios ejemplos.

Además, los 5 esqueletos de `examples/` enlazan **sin `rel`** un `../src/govco.css` que **no
existe**, y varios ejemplos usan `viewport content="width=+"` inválido y `lang="en"` sobre
contenido español. **Conclusión: el sitio no debe cargar ningún `.js` del Kit, y esta auditoría lo
respalda con la misma evidencia que el proyecto ya había reunido.**

### 9.6 Contrato, datos y puertas del backend

- **El contrato es la única fuente de verdad declarada y se respeta**: los dos clientes importan
  tipos generados desde `openapi.yaml` (`tramites/index.vue:46`, `tramites/[slug].vue:44`,
  `panel/src/types/api.ts`), y el sobre plano se desempaqueta **en un solo sitio**
  (`panel/src/services/http.ts:47-66`), no en cada vista.
- **El catálogo de trámites se resuelve en el servidor**, con `useRequestFetch` y la razón explicada
  (`tramites/index.vue:277-280`), y con una colección vacía declarada en lugar de una petición
  inventada para lo que aún no se publica.
- **La sesión del panel viaja en cookie `HttpOnly` y no en `localStorage`**
  (`panel/src/services/http.ts:40-43`), que es la decisión correcta y coherente con C-20.
- **El backend tiene pruebas reales** (6 clases de prueba más su `TestCase` y un doble de prueba:
  `Api/V1/TramiteTest`, `Datos/TramiteSeederTest`, `Ingesta/IngestaTramitesTest`,
  `Ingesta/ReconciliacionSuitTest`, `SaludTest`, `Unit/Support/ContactoPublicableTest`); el agujero
  de pruebas está en los dos frontends (D-19).

---

## 10. Índice de hallazgos y mapa de archivos

### 10.1 Índice por gravedad

| 🔴 Bloqueantes (6) | 🟠 Graves (17) | 🟡 Medios (20) | ⚪ Menores (9) |
|---|---|---|---|
| D-01 Cookies | D-07 Noticias en portada | D-24 Mapa y sitemap manuales | D-44 Servicios vs Trámites |
| D-02 Aviso sitio externo | D-08 Portada por tareas | D-25 robots/lastmod | D-45 `/participa` mal marcada |
| D-03 Buscador | D-09 404 | D-26 Visitados | D-46 Dos logotipos |
| D-04 Autenticación del panel | D-10 Políticas sin documento | D-27 Confirmación y pasos | D-47 Título del panel |
| D-05 Cifras simuladas | D-11 Barra de accesibilidad | D-28 Componentes ausentes | D-48 Ayuda sin canal |
| D-06 Puertas y trazabilidad | D-12 Carrusel en reproducción | D-29 Metadatos sociales | D-49 Comentario del pie |
| | D-13 Adjuntos y captcha | D-30 `make tipos` | D-50 Hueco de buscador |
| | D-14 Identidad del panel | D-31 Listas duplicadas | D-51 Clases inertes |
| | D-15 Módulos marcador | D-32 Tope del menú | D-52 Manifiesto incompleto |
| | D-16 Accesibilidad del panel | D-33 Panel no responsive | |
| | D-17 Secciones vacías | D-34 Icono como texto | |
| | D-18 Datos de la Entidad | D-35 Código y dependencias muertas | |
| | D-19 Sin pruebas | D-36 i18n sin decisión | |
| | D-20 ADR-0015 caducado | D-37 `tabla-govco` mal anidada | |
| | D-21 Barra en tablet | D-38 Medida de línea | |
| | D-22 Enlaces internos | D-39 Foco visible | |
| | D-23 Declaración de conformidad | D-40 Espaciado de texto | |
| | | D-41 Tipos del sitio | |
| | | D-42 Permisos por módulo | |
| | | D-43 Staging indexable | |

### 10.2 Por archivo: dónde hay que tocar

| Fichero | Hallazgos |
|---|---|
| `sitio/app/layouts/default.vue` | D-02, D-22, D-31, D-32 |
| `sitio/app/components/govco/BarraSuperior.vue` | D-02, D-36 |
| `sitio/app/components/govco/BarraAccesibilidad.vue` | D-11, D-21 |
| `sitio/app/components/govco/CabeceraGovco.vue` | D-22 (enlaces), D-46 |
| `sitio/app/components/govco/MenuNavegacionGovco.vue` | D-31, D-32, D-50 |
| `sitio/app/components/govco/BuscadorGovco.vue` | D-03 |
| `sitio/app/components/govco/CarruselGovco.vue` | D-12, D-22, D-51 |
| `sitio/app/components/govco/GaleriaAplicacionesGovco.vue` | D-28, D-51 |
| `sitio/app/components/govco/PiePaginaGovco.vue` | D-02, D-18, D-24, D-31, D-46, D-49 |
| `sitio/app/components/govco/MigaDePanGovco.vue` | D-22 |
| `sitio/app/composables/useAccesibilidad.ts` | D-11, D-35, D-40 |
| `sitio/app/pages/index.vue` | D-07, D-08, D-44, D-48 |
| `sitio/app/pages/buscar.vue` | D-03, D-22 |
| `sitio/app/pages/error.vue` | D-09 |
| `sitio/app/pages/politicas/[slug].vue` | D-10, D-31 |
| `sitio/app/pages/mapa-del-sitio.vue` | D-24, D-31, D-45 |
| `sitio/app/pages/pqrsd.vue` | D-31, D-37 |
| `sitio/app/pages/realizar-una-peticion.vue` | D-13, D-27 |
| `sitio/app/pages/tramites/*.vue` | D-22, D-51 |
| `sitio/app/pages/participa/index.vue`, `participa/[slug].vue` | D-17, D-22, D-31 |
| `sitio/app/assets/css/sitio.css` | D-26, D-38, D-39 |
| `sitio/nuxt.config.ts` | D-41, D-43 |
| `sitio/server/routes/sitemap.xml.ts` | D-24, D-25, D-31 |
| `sitio/public/robots.txt`, `site.webmanifest` | D-25, D-43, D-52 |
| `panel/src/router/index.ts` | D-04, D-42 |
| `panel/src/layouts/AdminLayout.vue` | D-16, D-33, D-34, D-42 |
| `panel/src/views/acceso/EntrarView.vue` | D-04 |
| `panel/src/views/admin/InicioView.vue` | D-05 |
| `panel/src/components/base/DataTable.vue`, `BaseModal.vue`, `AccessibilityBar.vue` | D-16 |
| `panel/src/assets/styles/tokens.css`, `fonts.css` | D-14, D-35 |
| `panel/index.html` | D-14, D-47 |
| `Makefile` | D-06, D-30, D-41 |
| `docs/trazabilidad.md`, `docs/adr/README.md`, `docs/Sección 2/4/5/6` | D-06, D-20, C-01 a C-25 |
| `sede-electronica-doc/01-estructura-identidad/sede.md` | §3.2 (duplicado byte a byte) |
| `sede-electronica-doc/**` | C-22, C-23 y las 17 colisiones de identificadores (§3.4) |

### 10.3 Método de verificación reproducible

Cualquiera puede rehacer las comprobaciones centrales de esta auditoría sin más herramientas que
`grep` y `git`:

```bash
# D-01: no hay banner de cookies
grep -ri 'cookie' sitio/app | grep -v 'uso-de-cookies'      # → sólo enlaces y política
# D-02: no hay aviso de sitio externo
grep -rn 'sitio externo' sitio/app                          # → 0 resultados
# D-03: el buscador no busca
sed -n '44,49p' sitio/app/pages/buscar.vue
# D-04: el panel no tiene guardias
grep -rn 'beforeEach' panel/src                             # → 0 resultados
# D-05: cifras simuladas
sed -n '26,29p' panel/src/views/admin/InicioView.vue
# D-06: puertas a artefactos inexistentes
ls panel/tests scripts sitio/tests                          # → no existen
grep -n 'frontend/tests' docs/trazabilidad.md | wc -l        # → citas a un árbol inexistente
# D-11: sin persistencia ni Centro de Relevo
grep -rn 'localStorage' sitio/app                            # → 0 resultados
grep -rin 'relevo' sitio/app                                 # → 0 resultados
# D-12: el carrusel arranca solo
grep -n 'autoplay' sitio/app/components/govco/CarruselGovco.vue
# D-13: sin adjuntos ni captcha
grep -rn 'type="file"' sitio/app                             # → 0 resultados
grep -rin 'captcha' sitio/app                                # → 0 resultados
# D-14: identidad del panel
grep -n '3366CC' panel/src/assets/styles/tokens.css
# D-18: /entidad no se consume
grep -rn 'entidad' sitio/app --include=*.vue | grep -i fetch # → 0 resultados
# D-19: sin pruebas
find sitio panel -name '*.spec.*' -o -name '*.test.*' | grep -v node_modules  # → 0
# D-22: enlaces internos con <a href>
grep -rn '<a ' sitio/app --include=*.vue | grep -v NuxtLink
# D-26: sin vínculos visitados
grep -rn ':visited' sitio/app                                # → 0 resultados
# D-30/D-41: tipos y compilación
grep -n 'api.d.ts\|openapi.d.ts' Makefile
grep -n 'typeCheck' sitio/nuxt.config.ts
# D-37: la tabla del Kit mal anidada
sed -n '221,222p' sitio/app/pages/pqrsd.vue
# §3.2: el módulo 01 duplicado
diff -q sede-electronica-doc/01-estructura-identidad/sede.md \
        sede-electronica-doc/01-estructura-identidad/estructura-identidad.md
# D-46: dos logotipos distintos
md5sum docs/logo500Or.png sitio/public/logo-entidad.png panel/public/logo-alcaldia.png
```

**Lo que este método no puede comprobar** —y por eso aparece como ⛔ en la sección 4—: contraste
efectivo, reflujo y áreas táctiles reales, FCP, validez W3C, navegación con productos de apoyo,
posición en buscadores y encuestas de usabilidad. Todo eso exige ejecutar la sede; es exactamente
lo que la Fase 1 de la sección 8 propone construir.

---

## 11. Anexos

### Anexo A — Los 34 criterios de aceptación de diseño (CAG-01 a CAG-34), texto literal y estado

Línea base de aceptación del diseño (`docs/Sección 2 · Diseño.md §2.4`), con el estado que esta
auditoría les asigna. La columna **Δ** indica si el criterio se desvía por decisión declarada.

| CAG | Criterio literal | Sev. | Estado | Nota |
|---|---|---|---|---|
| CAG-01 | Los elementos controladores del carrusel NO deben solaparse ni confundirse con las imágenes o fondos; si ocurre, añadir fondo a los controles o ubicarlos fuera del carrusel. | 🔴 | ✅ | El carrusel lleva los controles en una banda propia fuera de la imagen (`CarruselGovco.vue:23-28`). |
| CAG-02 | Carrusel: indicadores de posición, flechas de navegación y controles de reproducción/pausa (todos obligatorios). | 🔴 | ✅ | `CarruselGovco.vue:240-300`. |
| CAG-03 | Carrusel: contraste mínimo 4.5:1 entre contenido y controles. | 🔴 | ⛔ | Documentado (todo pulsable en blanco sobre cobalto, 8,46:1) pero **sin medición reproducible** (D-19). |
| CAG-04 | Carrusel: las imágenes deben llevar `alt`, `aria-label` o `aria-labelledby`. | 🟠 | ✅ | El `alt` es obligatorio en el tipo (`CarruselGovco.vue:48-53`) con aviso en desarrollo (`:115-123`). |
| CAG-05 | Barra superior: logo GOV.CO 24 × 136 px, `#0943B5`, enlaza a `https://www.gov.co/home/`. | 🔴 | 🟡 | Enlaza a `https://www.gov.co/` sin `/home/` (`BarraSuperior.vue:18`); el tamaño lo pone el Kit y no se declara. |
| CAG-06 | Botón de cambio de idioma 24 × 24 px a la derecha, `aria-label`, idioma persistente. | 🟠 | ➖ Δ | Omitido por ADR-0015, **pero FUN-010 y la GUIA Maestra exigen enlaces de traducción** (C-08, C-22). |
| CAG-07 | Barra de accesibilidad con Aumentar letra · Reducir letra · Contraste; oculta entre 768-992 px. | 🔴 | ✅ | `BarraAccesibilidad.vue:39-72`, oculta por debajo de 992 px. **Contradice RNF-07-D01** (C-25, D-21). |
| CAG-08 | Enlace «Saltar al contenido principal» con `sr-only sr-only-focusable` al destino. | 🔴 | ✅ | `CabeceraGovco.vue:66-68`; clases implementadas en `:113-150`; destino en la disposición (`layouts/default.vue:197`). |
| CAG-09 | Menú: máximo 7 ítems principales y máximo 4 secciones internas por ítem. | 🔴 | ✅ | 7 exactos (`layouts/default.vue:46-94`); el tope se aplica en el cliente (D-32). |
| CAG-10 | `aria-label` en el menú, navegación completa por teclado y contraste de foco ≥ 4.5:1. | 🔴 | 🟡 | `aria-label` y teclado ✅ (`MenuNavegacionGovco.vue:556,399-503`); **el contraste del foco no se mide** (D-39). |
| CAG-11 | Miga de pan en todas las secciones excepto Home; invertida sólo sobre fondo oscuro. | 🔴 | ✅ | Derivada de la ruta (`layouts/default.vue:132-150`), por construcción. |
| CAG-12 | Pie: contraste ≥ 4.5:1 con logo autoridad + GOV.CO + Colombia-CO. | 🔴 | ✅ | `PiePaginaGovco.vue:39-50,281-358`; la disposición de logos se corrige frente al Kit (`sitio.css:107-129`). |
| CAG-13 | Botones `<button type="button">` con `aria-label` si sólo llevan ícono; estados `:hover`/`:focus`. | 🔴 | ✅ | `BarraAccesibilidad.vue:40-71`, `VolverArriba.vue:37`. |
| CAG-14 | Botones deshabilitados con `disabled`, fuera del orden de tabulación. | 🟠 | ✅ | `BarraAccesibilidad.vue:56,67`. |
| CAG-15 | Buscador con placeholder claro, borrable y navegable por teclado. | 🟠 | ✅ | `BuscadorGovco.vue:97-133`; el botón de limpiar sólo existe cuando hay texto (`:118-126`). |
| CAG-16 | Asterisco `*` para obligatorios + leyenda inicial; `<label for>`. | 🟠 | ✅ | Asterisco y `label` ✅. **La leyenda existía pero a mitad del formulario**: los dos primeros campos ya eran obligatorios y quien lo rellenaba se topaba con el asterisco antes que con su explicación. Se movió al principio y se enlazó con `aria-describedby` (§16.1). Medido: va antes del primer campo obligatorio. |
| CAG-17 | Entradas con borde contrastado, estados Default/Active/Focus/Disabled/Valid/Invalid y `autocomplete`. | 🟠 | ✅ | `autocomplete` en los 13 campos visibles del formulario y en el buscador. Estados completos: foco al cobalto, deshabilitado con fondo del Kit y opacidad 1 (el 0,65 de Bootstrap dejaba el texto ilegible) y validez con el rojo institucional (§16.1). Medido con navegador. |
| CAG-18 | Desplegable-lista: máximo 5 elementos visibles antes de scroll. | 🟡 | ➖ Δ | Reexpresado a 12 por ADR-0015 §2, con justificación razonada (C-06). |
| CAG-19 | Calendario con estilo similar al de la sección Desplegables. | 🟡 | ➖ Δ | Declarado no aplica; no hay campos de calendario (C-06). |
| CAG-20 | Línea de avance con foco ≥ 4.5:1 y paso libre o bloqueado según obligatoriedad. | 🟠 | ❌ | No existe el componente y **§2.5 lo exige en el detalle de trámite** (C-06). |
| CAG-21 | Alerta modal cerrable con Esc y clic fuera; sin anidar; máximo un modal. | 🔴 | ❌ | No existe, **y RF-B1-071 (Must) lo necesita** (D-02). |
| CAG-22 | Notificaciones *toast* con duración suficiente y cierre automático. | 🟠 | ❌ | No existe, y §2.5 lo exige en las notificaciones globales (C-06). |
| CAG-23 | Paginación ≥ 44 × 44 px en móvil con `aria-current` en la página activa. | 🟠 | 🟡 | `tramites/index.vue:782-805` con `aria-current`; el tamaño se corrige globalmente (`sitio.css:463-465`) pero sin medición móvil. |
| CAG-24 | Tablas: números a la derecha y sin scroll horizontal en responsive. | 🟡 | ✅ | **ADR-0015 la declaró «no aplica» y `pqrsd.vue:222` publica una tabla**: la desviación caducó (D-20). |
| CAG-25 | Acordeón con `aria-expanded`, sin apertura al enfocar y con jerarquía de encabezados. | 🔴 | ❌ | No existe, y §2.5 lo exige en Trámites y Detalle (C-06). |
| CAG-26 | Tarjeta encerrada en `<a>` o `<button>`, con máximo 2 palabras en el título/CTA. | 🟠 | ✅ | `TarjetaInformacionGovco.vue:9-15,92-100`; el `<a>`/`<button>` envuelve la tarjeta entera. |
| CAG-27 | Tipografía: nunca justificar, 45-75 caracteres por línea, unidades relativas. | 🟡 | ✅ | No se justifica ✅; **la medida de línea excede el rango** (D-38) y hay `px` en varios puntos del sitio. |
| CAG-28 | Color: contraste 4.5:1 (texto normal) y 3:1 (texto grande). | 🔴 | ⛔ | Mediciones parciales documentadas y correctas; **sin medición exhaustiva** (D-19). |
| CAG-29 | Indicador de carga: si supera 10 s, informar del progreso. | 🟠 | 🟡 | **Implementado** en `tramites/index.vue`: si la consulta pasa de diez segundos, el estado de carga añade un aviso dentro de un `role="status"`. **No medido**: haría falta un servicio con más de diez segundos de latencia, que este entorno no tiene; la matriz lo lista como «implementación», no como prueba. |
| CAG-30 | Volver arriba en la esquina inferior derecha con foco visible. | 🟡 | ✅ | `VolverArriba.vue:36-49`, con `aria-label` y respeto de `prefers-reduced-motion`. |
| CAG-31 | Galería de aplicaciones navegable con teclado; abre con Enter y cierra con Esc. | 🟠 | ❌ | El componente está completo y **nunca se instancia** (D-28). |
| CAG-32 | Todos los componentes cumplen la Resolución 1519 y WCAG 2.1 AA. | 🔴 | ⛔ | **Sin axe-core ni Lighthouse ejecutados** (D-19); es el criterio peor acreditado. |
| CAG-33 | Construir sobre Bootstrap 5.0.2 y las variables `--govcolor-*`. | 🔴 | ✅ | `nuxt.config.ts:61,71`; Bootstrap 5.0.2 desde el propio dominio y antes del Kit. |
| CAG-34 | Servir el frontend desde el CDN oficial v5 o desde la copia local del repo. | 🟠 | ✅ | Copia local **byte a byte idéntica** al Kit (md5 verificado); ADR-0002 documenta por qué (C-02). |

### Anexo B — Criterios de los PDF oficiales de MinTIC · AND

**Diseño** (1 página, 2 ítems): los dos están cubiertos por CAG-01 y su alternativa.

**Funcionalidad** (1 página, 6 ítems, orden verificado por coordenadas del PDF):

| # | Criterio literal | Estado |
|---|---|---|
| 1 | Garantiza que los vínculos presentados en las páginas no estén rotos y direccionen a las páginas solicitadas. | ⛔ sin rastreador; sin indicios de rotura |
| 2 | Cuando se direccione a una página externa, se recomienda que se abra en una nueva pestaña. | ✅ `target="_blank" rel="noopener"` |
| 3 | Garantiza que los campos obligatorios dentro de los formularios cumplan con esa condición; si el usuario no los diligencia, debe presentarse el mensaje de error. | ✅ `realizar-una-peticion.vue:413-472` |
| 4 | Asegura que en los vínculos de políticas se presente la documentación correspondiente a: (A) Términos y condiciones; (B) Privacidad y tratamiento de datos; (C) Derechos de autor y/o autorización de uso sobre los contenidos. | ❌ los tres enlaces existen; **ninguno publica documento** (D-10) |
| 5 | En todos los formularios donde se capturen datos personales se debe contar con el aviso de privacidad y autorización para el tratamiento de datos; adicional, incluir el Captcha. | 🟡 aviso y autorización ✅ (`:865-897`); **captcha ❌** (D-13) |
| 6 | Ten en cuenta que en el buscador se realice la búsqueda dentro de la sede. No se puede contemplar un buscador que realice la búsqueda en Google. | 🟡 el buscador es interno por diseño y **no busca** (D-03) |

**Seguridad** (2 páginas, 12 ítems): fuera del alcance de esta auditoría salvo los ítems 3, 4 y 6
(control de tasa de intentos, CAPTCHA y pie con las cinco políticas), que se auditan en D-04, D-13
y D-10. Se deja constancia de que **el ítem 12 exige `Public-Key-Pins`, prohibido por el propio
expediente** (C-10).

### Anexo C — Checklist pre-entrega de diseño (§2.6 del expediente)

Es la lista que el expediente manda **firmar** al diseñador UI/UX y al frontend lead antes de
mergear a `main`. Hoy **ninguna de sus 32 casillas está marcada** en el documento, y esta auditoría
puede marcar las que dependen de código:

| Bloque | Casillas | Lo que puede afirmarse hoy |
|---|---|---|
| §2.6.1 Sistema visual | 5 | Paleta y tipografía del sitio ✅; rejilla ✅; logos ✅. **El panel incumple la paleta y la tipografía** (D-14). |
| §2.6.2 Componentes | 3 | **Incumplido por decisión** (los componentes no se instancian «desde el repositorio v5» sino como componentes Vue: C-24); 4 de 23 ejemplos reales del Kit tienen reflejo directo en el sitio; los estados se documentan ✅. |
| §2.6.3 Criterios de aceptación | 8 | Ver el anexo A: 15 ✅ · 7 🟡 · 6 ❌ · 3 ⛔ · 3 ➖. **Las casillas de «verificado en navegador y DevTools» no pueden marcarse.** |
| §2.6.4 Accesibilidad | 5 | Foco ⛔, contraste ⛔, teclado 🟡, ARIA 🟡, salto al contenido ✅. |
| §2.6.5 Responsive | 4 | 320 px ✅ documentado; 6 breakpoints ⛔; adaptación de componentes 🟡; barra oculta 768-992 ✅ (pero contra RNF-07-D01). |
| §2.6.6 Rendimiento y entrega | 4 | Copia local ✅; SVG vía clase ✅; `font-display: swap` ✅ (lo pone el Kit); capturas de PR: no constan. |
| §2.6.7 Verificación automática | 3 | **Las tres puertas que cita no existen** (D-06). |

### Anexo D — Declaración de Conformidad de Accesibilidad (Res. 1519, Anexo 1 num. 9.3)

Los 12 elementos mínimos y lo que el sitio publica hoy:

| # | Elemento exigido | Estado en `/accesibilidad` |
|---|---|---|
| 1 | Entidad | ✅ implícito en el pie y la cabecera |
| 2 | Sede Electrónica (URL) | ❌ no se declara la URL canónica |
| 3 | Norma de referencia | ✅ Resolución 1519 de 2020, Anexo 1, y WCAG 2.1 AA (`:78-88`) |
| 4 | Fecha de evaluación | ❌ |
| 5 | Alcance (URLs o sitemap) | ❌ |
| 6 | Herramientas utilizadas con versión | ❌ |
| 7 | Auditor | ❌ |
| 8 | Estado de cumplimiento (completo o parcial) | ❌ — y se dice expresamente que no se declara (`:99-113`) |
| 9 | Criterios no aplicables y excepciones | ❌ |
| 10 | Excepciones documentadas con mitigación | ❌ |
| 11 | Hallazgos pendientes con severidad, plan y fecha | ❌ |
| 12 | Fecha de próxima revisión (≤12 meses) | ❌ |

La página es honesta —no afirma lo que no puede acreditar— y esa honestidad es correcta; lo que
falta es **ejecutar la auditoría y publicar la declaración** (D-19 → D-23).

### Anexo E — Otros artefactos de aceptación que el expediente exige

Se listan para que la auditoría sea navegable, con su ubicación en el expediente:

- **Criterios FUN-001 a FUN-006** (funcionalidad literal): `docs/Sección 5 §5.3.1`, con la
  advertencia de C-09 sobre su numeración.
- **Criterios SEG-001 a SEG-018** (12 literales + 6 derivados): `docs/Sección 4 §4.3`.
- **Lista de comprobación pre-producción de 11 capas**: `docs/Sección 4 §4.7`.
- **Matriz de 25 puntos + auditoría de contrato C1-C7**: `GUIA-MAESTRA-COMPLETA.md` capítulo 5.
  Es el criterio de «módulo terminado» del proyecto y **hoy ningún módulo puede aprobarlo** (los
  puntos 17 a 25 son de guardias y permisos: D-04 y D-42).
- **Procedimiento de auditoría de accesibilidad de 6 pasos con puertas**: `docs/Sección 3 §3.6.2`.
- **Repositorio de criterios C1-C13 del diseño de base de datos**: `sede-electronica-doc/_bd/06-auditoria.md`
  (fuera del alcance de esta auditoría; se cita porque su veredicto —«aprobado, sin incumplimientos
  bloqueantes»— **sí** está respaldado por artefactos presentes en el repositorio, a diferencia de
  lo que ocurre con `docs/trazabilidad.md`).

### Anexo F — Glosario de identificadores

| Prefijo | Significado | Dónde vive |
|---|---|---|
| `RF-Bn-xxx` / `RNF-Bn-xxx` / `RN-Bn-xxx` | Requisito funcional / no funcional / regla de negocio del **corpus**, serie por bundle de origen | `sede-electronica-doc/**` |
| `RF-nn-Dxx` / `RNF-nn-Dxx` / `RN-nn-Dxx` | Delta de la segunda pasada profunda, por módulo | `sede-electronica-doc/**` y `_global/**` |
| `RNF-TX-Dxx` / `RN-TX-Dxx` | Transversales del delta | `_global/` |
| `UC-Bn-xxx` / `HU-Bn-xxx` | Caso de uso / historia de usuario del corpus | `sede-electronica-doc/**` |
| `CAG-nn` | Criterio de aceptación de **diseño** (34) | `docs/Sección 2 §2.4` |
| `FUN-nnn` | Criterio de aceptación de **funcionalidad** (74) | `docs/Sección 5` |
| `SEG-nnn` | Criterio de aceptación de **seguridad** (18) | `docs/Sección 4` |
| `ACC-nnn` | Criterio de accesibilidad — **sin enunciado en ningún documento** (C-18) | citados en `docs/Sección 6` |
| `O-nn` / `RT-nn` | Obligaciones derivadas / requisitos técnicos | `docs/Sección 1` |
| `ADR-nnnn` | Decisión de arquitectura (15) | `docs/adr/README.md` |
| `D-nn` | **Hallazgo de esta auditoría** | este documento, §5 |
| `C-nn` | **Contradicción que exige decisión** | este documento, §6 |

---

## 12. Verificación de las correcciones aplicadas (2026-10-01)

Pasada de verificación **posterior** a la edición tercera: se revisó, uno por uno, cada cambio
presente en el árbol de trabajo (`git status`: 10 ficheros modificados, 7 nuevos, ninguno en
`panel/`). Las cifras de la sección 2 describen el estado **anterior** a este lote; esta sección
dice qué se cerró y qué se rompió al cerrarlo.

**Resumen: de los 52 hallazgos, el lote toca 13. Cierra 3, deja 6 a medias, 4 quedan intactos
pese a haberse tocado su área, y 39 no se tocaron. Y aparecen 7 defectos nuevos, uno bloqueante.**

### 12.1 Hallazgos cerrados (verificados)

| ID | Hallazgo | Evidencia de cierre |
|---|---|---|
| **D-12** | Carrusel arrancaba reproduciendo | `CarruselGovco.vue:98` → `autoplay: false`, con la justificación de RF-B1-042/RF-B1-051 escrita al lado. La portada no lo sobrescribe. **Cerrado.** (El comentario tiene una errata: «explícitamenteincumple».) |
| **D-09** | 404 con una sola salida | `error.vue:94-157`: tres tarjetas (portada, buscador, mapa del sitio) **más** tres secciones principales, con `aria-labelledby` y `focus-visible`. **Cerrado** — con la salvedad de que una de las salidas (`/buscar`) lleva a un buscador que sigue sin buscar (D-03) y otra (`/servicios`) a un aviso de preparación. |
| **D-26** | Vínculos visitados sin diferenciar | `sitio.css:276-305`: `:visited` en enlaces de contenido y en la banda del pie. Funciona y contrasta (12,4:1 sobre blanco). **Cerrado con reservas** — ver R-07. |

### 12.2 Hallazgos parcialmente resueltos

| ID | Qué se hizo | Qué falta todavía |
|---|---|---|
| **D-01** | `BannerCookies.vue` (441 líneas) montado en `layouts/default.vue:248`: aceptar todo / rechazar opcionales / configurar por categoría, con **fecha, versión de política y caducidad a 365 días**, y re-solicitud si la versión cambia (`:109-117`). Es un banner sólido. | **No hay revocación**: RF-B1-008 exige que el consentimiento sea revocable «en cualquier momento» y no existe ningún punto en la interfaz para reabrirlo o cambiarlo. El composable `useConsentimientoCookies` que el propio comentario del componente cita (`:11`) **no existe**, así que nada puede consultar el consentimiento ni bloquear analítica. Código muerto: `configurarAbierto`, `recordarRechazo()`, `VISIBLE`, `CLAVE_CONSENTIMIENTO` y `RECHAZADO_KEY` (nunca se leen ni se invocan). `inicializar()` corre en el *setup* y no en `onMounted` (`:131-133`) → el banner aparece durante la hidratación. Typo `mostarBanner`. |
| **D-02** | `useAvisoSalida` + `ModalAvisoSalida` + plugin cliente **montados** (`layouts/default.vue:249-257`): lista blanca de 9 dominios (RN-01-D03), nombre del destino, entidad responsable, URL, aviso de responsabilidad, cierre con Esc y clic en el fondo. Los enlaces a `gov.co` no disparan modal y los de redes sociales sí. | **R-05** (el modal se intercepta a sí mismo y se reabre; y los enlaces externos sin `target="_blank"` se quedan sin aviso). Sin **trampa de foco**: el comentario `:55` la declara y no existe. **Sin devolución del foco** al elemento que abrió: el `watch` sólo atiende el caso `mostrar === true` (`:62-72`) aunque su comentario promete lo contrario. `mailto:`/`tel:` se clasifican como externos (`useAvisoSalida.ts:69-78`). |
| **D-11** | **Persistencia resuelta**: `useAccesibilidad.ts:39-72` lee y escribe `localStorage` con validación de tipos y `onMounted`, cumpliendo ADR-0012. | **Falta el enlace al Centro de Relevo**, que RF-B1-044 exige por escrito: `grep -rin relevo sitio/app` sigue sin resultados. `restablecer()` continúa exportado y sin consumidor. |
| **D-23** | `accesibilidad.vue` ahora publica la **tabla de los 12 elementos** del Anexo 1 num. 9.3, con 3 declarados «Completo» y 9 «Pendiente», y una nota visible que dice que **no se declara un nivel de conformidad** verificado (`:287-296`). Es el andamiaje correcto. | El comentario de cabecera del propio fichero afirma lo contrario de lo que la página dice: «Esta declaración **acredita el cumplimiento** de las WCAG 2.1 AA» (`:7-8`) y «Esta página se mantiene conforme mientras la auditoría no se haya ejecutado» (`:24`). Además la URL de la sede está fija en `www.santamarta.gov.co` (`:34`) mientras `runtimeConfig` apunta a `staging` por defecto. La declaración real sigue pendiente de la auditoría (D-19). |
| **D-24** | `app/config/sitemap.ts` centraliza las rutas, y **el mapa HTML y el `sitemap.xml` ya beben de ella** (`mapa-del-sitio.vue:13,44-63`; `sitemap.xml.ts:14-34`), que era la mitad del problema. | **El menú sigue con su lista propia** (`layouts/default.vue:46-94` no importa la configuración): RF-B1-010 no se cumple y el encabezado de `config/sitemap.ts:5-11` afirma que `layouts/default.vue` lo consume, **lo cual es falso** (R-06). |
| **D-28** | La **galería de aplicaciones por fin se instancia** en la cabecera (`layouts/default.vue:217-224`), con las props correctas; y se crea el componente de alerta modal que faltaba. | Las tres aplicaciones apuntan a destinos **equivocados**: «Carpeta Ciudadana» → `https://www.secop.gov.co` y «CIIU — Clasificación Industrial» → `https://www.dian.gov.co` (CIIU es del DANE y la Carpeta Ciudadana no es SECOP). El comentario que afirma que son «las tres que fija el Kit UI» no se corresponde con la fuente. Siguen faltando acordeón, *toast*, desplegable con filtro, carga de archivos y *stepper*. |

### 12.3 Intactos pese a haberse tocado su área

| ID | Hallazgo | Comprobación |
|---|---|---|
| **D-22** | Navegación interna con `<a href>` | Siguen 19 enlaces internos con `<a>` (`buscar.vue:61`, `normativa.vue:489,543,548`, `participa/index.vue:44,50,54`, `tramites/*` ×7, `SeccionEnPreparacion.vue:40`, `MigaDePanGovco.vue:58`, `CarruselGovco.vue:240`) y el nuevo enlace de sesión (`default.vue:226`) **añade uno más**. |
| **D-25** | `robots.txt` con dominio distinto del configurado; sin `lastmod` | `public/robots.txt` sin cambios (`www.santamarta.gov.co` frente a `staging.…` en `nuxt.config.ts:100`); el XML regenerado sigue sin `<lastmod>`. |
| **D-41** | La comprobación de tipos no corre en la compilación | `nuxt.config.ts:113` sigue en `typeCheck: false` y `Makefile:103` sigue llamando sólo a `npm run build`. Es lo que hace invisible R-01. |
| **D-43** | Staging indexable | Sin cambios: `robots.txt` permite todo y no hay `noindex` por entorno. |

### 12.4 Defectos nuevos introducidos por el lote (regresiones)

| ID | Grav. | Defecto | Evidencia |
|---|---|---|---|
| **R-01** | 🔴 | **`npm run typecheck` del sitio ahora falla.** El bloque añadido en `nuxt.config.ts:21` usa una opción que Nuxt no tiene: `error TS2353: Object literal may only specify known properties, and 'directives' does not exist in type 'InputConfig<NuxtConfig, ConfigLayerMeta>'`. Es el **único** error que reporta `nuxt typecheck`, así que el lote lo introdujo; las directivas de `app/directives/` se registran solas, la entrada sobra y la directiva que pretendía registrar **no se usa en ningún sitio**. Y ninguna puerta lo detecta: `typeCheck: false` + `make compilar` ejecutando sólo `nuxt build` (D-41). |
| **R-02** | 🟠 | **Cuatro violaciones nuevas de contraste (WCAG 1.4.3), medidas:** `#888` sobre blanco = **3,54:1** (`error.vue:195` y `ModalAvisoSalida.vue:271`), `#888` sobre `#f8f9fa` = **3,36:1** (`error.vue:270`), `#888` sobre `#f3f4f6` = **3,22:1** (`ModalAvisoSalida.vue:254`). Todas por debajo del 4,5:1 que exige RNF-B1-017, en componentes **nuevos**, y con un gris que el proyecto había evitado explícitamente como color de texto (`PiePaginaGovco.vue:47-50`). |
| **R-03** | 🟠 | **El mapa del sitio y el `sitemap.xml` declaran «publicadas» páginas que no publican nada.** `config/sitemap.ts` marca `/transparencia` como publicada con prioridad **0,9** (`:121-127`), las cuatro subpáginas de Participa como publicadas (`:61-92`) y las cinco políticas como publicadas (`:280-289`); `mapa-del-sitio.vue:87` lo fija incluso con el comentario «Las páginas de políticas siempre están publicadas». El XML **anuncia a los buscadores unas diez direcciones vacías**. Es una **regresión de honestidad editorial**: la versión anterior las marcaba «(en preparación)», y el propio proyecto prohíbe anunciar lo que no se publica. |
| **R-04** | 🟠 | **Texto contaminado en rótulos públicos:** `config/sitemap.ts:103` declara la etiqueta `'Control社交 ciudadano'` —con caracteres chinos incrustados— que se publica en el menú, el mapa del sitio y el `sitemap.xml`. En el mismo lote, `sitio.css` (comentario nuevo de `:visited`) contiene «аудит» en cirílico. Son restos de generación no revisada en un texto que lee el ciudadano. |
| **R-05** | 🟡 | **El aviso de salida se intercepta a sí mismo.** El enlace de confirmación del modal es un `<a target="_blank">` externo (`ModalAvisoSalida.vue:155-163`), así que al pulsarlo el plugin de documento (`avisoSalida.client.ts:19-47`) lo captura de nuevo, hace `preventDefault()` y **vuelve a abrir el modal** después de que `confirmarNavegacion()` ya abrió la pestaña. Además, el guardián `if (!abreEnNuevaPestana) return` (`:40-43`) deja **sin aviso** justo el caso que el RF quiere cubrir: un enlace externo que navega en la misma pestaña. |
| **R-06** | 🟡 | **Documentación que miente**, que es el defecto que esta auditoría persigue: `config/sitemap.ts:5-11` afirma consumirse desde `layouts/default.vue` para el menú y que «cualquier cambio en la navegación se refleja automáticamente en el menú Y en el sitemap». El menú no lo consume. |
| **R-07** | 🟡 | **Color fuera de la paleta cerrada:** `sitio.css:284` usa `var(--govcolor-delft, #1d3557)` y **`--govcolor-delft` no existe** en el Kit (se verificaron las variables de `all.css`: no hay ninguna con ese nombre), así que siempre se aplica un color nuevo, contra CAG-33 («sólo los 21 tokens»). El comentario promete además «un indicador adicional (subrayado más grueso)» que **no está implementado**: la distinción de visitados depende sólo del color. |

### 12.5 Menores del mismo lote

- `slugToLabel()` (`config/sitemap.ts:283-289`) genera rótulos como **«Uso De Cookies»**, con la preposición en mayúscula, y se publican en el mapa del sitio.
- El modal y el banner replican la estructura de botones del Kit en vez de reutilizarla, y `accesibilidad.vue:153-170` usa `table table-bordered table-sm` y `badge bg-success/bg-warning` de Bootstrap en lugar de `tabla-govco` y las etiquetas del Kit: superficies nuevas fuera del sistema de componentes (RN-B2-029).
- `ModalAvisoSalida.vue:101` pone `@keydown` en el contenedor: si el foco sale del modal —y puede salir, porque no hay trampa—, **Esc deja de funcionar**.
- La directiva `app/directives/vAvisoSalida.ts` no se usa en ningún componente y nunca retira su escucha; si algún día se usara en algo que se re-monta, acumularía manejadores.
- Aparecen `DESIGN.md` y `PRODUCT.md` (nuevos, sin seguimiento en Git) fechados **2025-01-15**, un año antes que el resto de la documentación del proyecto.

### 12.6 Qué significa para el orden de corrección

El lote iba en la dirección correcta —atacó D-01, D-02, D-09, D-11, D-12, D-23, D-24 y D-28— y
**cerró los dos más baratos y visibles** (D-12, D-09). Pero deja tres lecciones que importan más
que los cierres:

1. **R-01 demuestra que D-41 y D-19 no son burocracia.** Se acaba de introducir un error de tipos
   que ninguna puerta del proyecto ve: la comprobación existe (`sitio/package.json:11`) y no la
   llama nadie. Antes de seguir añadiendo código, `make compilar` debe ejecutar `nuxt typecheck`.
2. **R-03 invierte el atributo que el proyecto cuidaba.** La honestidad editorial no se mantiene
   sola: al centralizar las rutas se perdió el dato de «qué está publicado», y el sistema pasó de
   decir «en preparación» a afirmar lo contrario ante el ciudadano y ante Google. Es el mismo
   defecto de D-06 en miniatura, y se corrige en un fichero.
3. **Las prisas dejan marca en el texto que se lee.** R-04 (caracteres chinos y cirílicos en
   rótulos públicos), R-02 (cuatro contrastes nuevos por debajo del mínimo) y R-07 (un color fuera
   de la paleta) son exactamente los defectos que esta auditoría señala cuando los comete el
   trabajo anterior. Un repaso de diez minutos antes de cerrar el lote los habría evitado.

**Estado actualizado del registro:** 3 cerrados · 6 parciales · 4 intactos en su área · 39 sin
tocar · **7 regresiones nuevas** (1 bloqueante, 3 graves, 3 medios).

---

## 13. Segunda pasada de correcciones (2026-10-01, tarde)

Con autorización expresa para **seis correcciones concretas** —y solo esas—, se cerraron los cuatro
hallazgos de código que quedaban sin depender de contenido externo ni de servicios de terceros, más
los dos residuos de contraste que esta auditoría había medido en el panel. Todo lo demás se dejó
intacto.

### 13.1 Las seis correcciones, con su medición

| Hallazgo | Qué se hizo | Verificación |
|---|---|---|
| **D-37** | `pqrsd.vue`: `tabla-govco` pasa del `<table>` al `<div>` contenedor, que es como lo monta el Kit (`examples/general/tablas.html:21`). Antes, al estar en la tabla, **ninguna** de las reglas de descendencia del Kit se aplicaba: ni las filas alternas (`.tabla-govco:not(.responsive-tabla-govco) table > tbody > tr:nth-child(even)`, `all.css:10383`), ni el encabezado fijo, ni los bordes. | El contenedor del Kit ya trae `overflow: auto`, así que sustituye también al `table-responsive` de Bootstrap; se retiró ese envoltorio doble. |
| **D-38** | Medida de línea global: `max-width: 68ch` para la prosa del contenido principal (`sitio.css`), y el mismo valor en las dos listas nuevas de la portada y la búsqueda. | **Medido con navegador real**: `/pqrsd` **74**, `/accesibilidad` **70**, `/politicas/uso-de-cookies` **70** caracteres por línea — dentro del rango del corpus (60-80) y del del expediente (45-80). Antes eran ~81 con `col-lg-8`. |
| **D-39** | Foco visible global con `:where(a, button, input, select, textarea, summary, [tabindex]:not([tabindex='-1'])):focus-visible { outline: 3px solid currentColor }`. El Kit solo estiliza el foco de **sus** componentes y Bootstrap 5.0.2 —la versión que el Kit exige— no conoce `:focus-visible` (llegó en la 5.2), así que CAG-10 no se podía acreditar. El color va en `currentColor` para que el indicador contraste sobre cualquier fondo, incluidas las bandas cobalto; y `:where()` deja la especificidad en cero para no pisar a los componentes que ya definen su foco. | **Medido**: el primer control tabulable muestra `outline solid 3px` con el color de su texto. |
| **D-40** | Espaciado de texto configurable: preferencia nueva (`espaciado`) en `useAccesibilidad`, persistida como las demás, con botón en el bloque de accesibilidad del pie —y no solo en la barra flotante, que se oculta por debajo de 992 px—. La clase `.espaciado-govco` aplica los valores de WCAG 1.4.12. | **Medido a 1 280 px y a 320 px**: interlínea **2,00×**, `letter-spacing` **0,12 em**, `word-spacing` **0,16 em** y **desborde horizontal 0 px**. Es la demostración de que el diseño no pierde contenido con el espaciado que exige el criterio. |
| **R-P1** | Panel: el botón de cerrar de la barra de accesibilidad pasa a `text-slate-500` (#64748B, **4,76:1**) y a **44 × 44 px**. Estaba en `text-slate-400` (**2,56:1**, por debajo del 3:1 de WCAG 1.4.11) y en 24 px. | `npm run build` del panel: 0. |
| **R-P2** | Panel: `--color-text-soft` pasa de #94A3B8 (**2,56:1**) a #64748B (**4,76:1**) —un token llamado «texto suave» tiene que poder usarse como texto—, y los tokens de estado verde (3,30:1) y amarillo (1,95:1) llevan su medición escrita al lado con la instrucción de no usarlos como texto. | La cabecera de `tokens.css` incorpora la tabla de contrastes y la regla que se desprende de ella. |

**Estado de las puertas tras esta pasada** (el árbol es el mismo que el de la evidencia):

```
nuxt typecheck ........................ exit 0
nuxt build ............................ exit 0
npm run test:accesibilidad ............ 18 páginas · 0 violaciones
panel: vue-tsc -b && vite build ....... exit 0
```

### 13.2 Hallazgo nuevo, encontrado al medir el orden de tabulación: **R-P4**

Al comprobar D-39 con un navegador real se midió, por primera vez, **cuál es el primer elemento
tabulable de una página**. No es el enlace de salto:

| # | Primeros tabuladores de `/pqrsd` |
|---|---|
| 1 | `<a>` «Portal del Estado Colombiano - GOV.CO» (barra superior) |
| 2-5+ | Botones de la barra de accesibilidad (contraste, reducir, aumentar, restablecer, Centro de Relevo) |

El enlace «Saltar al contenido principal» vive dentro de `CabeceraGovco` (`CabeceraGovco.vue:66`),
que se monta **después** de la barra superior y de la barra de accesibilidad
(`layouts/default.vue:192-204`). Por tanto:

- **RF-B3-022 (Must)** —«Primer Tab en cualquier página → aparece "Saltar al contenido
  principal"»— **incumplido**.
- **`Sección 3:148,252`** —«poner "Saltar al contenido principal" como primer enlace de la
  página»— incumplido.
- CAG-08 se cumple en su letra (el enlace existe, con sus clases y su destino), pero el mecanismo
  llega **después de ocho o nueve paradas de tabulador**, que es justo lo que el atajo existe para
  evitar.

**Corrección (una línea, no aplicada por quedar fuera de las seis autorizadas):** mover el enlace de
salto de `CabeceraGovco.vue` al principio de `layouts/default.vue`, antes de `<BarraSuperior />`. El
componente seguiría siendo el mismo; solo cambia de sitio en el orden del documento.

**Consecuencia para esta auditoría:** la fila de RF-B3-022 en §4.2 estaba marcada ✅ **sin haber
verificado que fuera el primero**. Se corrige a ❌ en §13.3. Es el segundo caso de esta auditoría en
el que una comprobación estática daba por bueno algo que solo se ve ejecutando (el primero fue el
`tabla-govco` de D-37).

### 13.3 Correcciones al propio registro

Las mediciones de esta pasada cambian el estado de cuatro filas de este documento:

| Dónde | Antes | Ahora |
|---|---|---|
| §4.2 · RF-B3-014 (espaciado configurable) | 🟡 | ✅ — con la preferencia y la medición de 1.4.12 |
| §4.2 · RF-B3-022 (salto como primer elemento) | ✅ | ❌ — **R-P4**, medido |
| §4.3 · RF-B2-081 y RNF-B1-031 (60-80 caracteres/línea) | 🟡 | ✅ — 70-74 medidos en tres páginas |
| Anexo A · CAG-24 (tablas del Kit) | ❌ | ✅ — la tabla ya usa el componente del Kit como lo monta el Kit |
| Anexo A · CAG-27 (nunca justificar, 45-75 caracteres) | 🟡 | ✅ — 70-74 caracteres, sin justificar |

Las cifras de las secciones 2 y 7 describen el estado **antes** de las dos pasadas de corrección; no
se reescriben para no arrastrar un recálculo a todo el documento, pero el lector debe leerlas junto
con §12 y §13.

**Recuento al cierre de esta pasada, sobre los 59 elementos del registro (52 hallazgos + 7
regresiones):** **39 cerrados** · 9 parciales · 11 pendientes por dependencia externa (contenido,
contrato o servicios de terceros). Además queda **1 hallazgo nuevo** (R-P4, el enlace de salto no es
el primer elemento tabulable), con la corrección escrita y una línea de coste, y **2 residuos del
panel** medidos y corregidos en esta misma pasada (R-P1 y R-P2, ver `auditoria-panel.md`).

---

## 14. Cierre de los cuatro pendientes de código (2026-10-01)

Segunda autorización expresa, para cuatro puntos concretos: el hallazgo **R-P4**, el generador de la
**matriz de trazabilidad** (D-06), las **pruebas unitarias** (D-19) y las **dependencias sin usar**
(D-35). Los cuatro están cerrados y verificados.

### 14.1 Las cuatro correcciones

| Punto | Qué se hizo | Verificación |
|---|---|---|
| **R-P4** (Must) | El enlace «Saltar al contenido principal» se movió de `CabeceraGovco.vue` a la **primera posición** de `layouts/default.vue`, antes de `<BarraSuperior />`, con sus estilos `sr-only sr-only-focusable` (que ahora viven en la disposición). El componente de cabecera dejó de tener el prop `destinoContenido`, que ya no significaba nada. | **Medido con Chromium en `/pqrsd`, `/` y `/tramites`**: el primer tabulador es el enlace de salto en las tres. |
| **D-06** | Nace `sitio/scripts/trazabilidad.mjs` y el objetivo `make trazabilidad` (y `npm run trazabilidad`): lee el universo de criterios de las secciones del expediente, busca cada identificador en `sitio/`, `panel/`, `backend/` y `contract/`, parsea el contrato con `js-yaml` y **regenera `docs/trazabilidad.md`** con lo que encuentra —y con lo que no—. El documento generado declara en su cabecera qué mide y qué no: *una cita en un comentario acredita que alguien trabajó el criterio, no que se cumpla*. | **137 criterios · 35 con evidencia (26 %) · 4 con prueba automatizada · 95 sin evidencia.** El «122 de 140 (87 %)» que acreditaba artefactos inexistentes queda retirado y sustituido por una cifra reproducible. |
| **D-19** | Pruebas unitarias reales con **vitest** (ya instalado, estaba sin usar): 35 en el sitio (consentimiento de cookies, clasificación de enlaces externos, invariantes de la configuración de rutas contra el disco) y 20 en el panel (permisos por módulo contra el enrutador, almacén de sesión, y **contraste medido de los tokens**). `make unidad` vuelve a la cadena de `comprobar`. | **`make unidad` pasa: 3 ficheros y 20 pruebas en el panel; 3 ficheros y 35 en el sitio.** Dos de estas pruebas habrían cazado defectos ya cometidos: la de rutas publicadas sin página (R-03) y la de tokens de texto por debajo de 4,5:1 (R-P2). |
| **D-35** | Fuera las dependencias declaradas y sin usar: en el sitio `@tanstack/vue-query`, `axios`, `zod`, `@pinia/nuxt` y `pinia`; en el panel `@tanstack/vue-query`, `vue3-toastify` y `zod`. Se retiró el módulo `@pinia/nuxt` de `nuxt.config.ts` —el sitio no tiene ni una tienda, usa `useState`— y se regeneraron los dos `package-lock.json`. | `npm install` sin vulnerabilidades en los dos; `npm ls` sin paquetes huérfanos; `nuxt typecheck` y `nuxt build` en 0 y `vite build` del panel en 0. |

### 14.2 Estado de las puertas

```
make comprobar   contrato · formato-verificar · analisis · pruebas · unidad · tipos
                 · compilar · accesibilidad                     → 8 puertas, todas reales
make trazabilidad .............................................. 137 criterios, 26 % trazado
make unidad .................................................... 55 pruebas, 0 fallos
make compilar .................................................. panel 0 · sitio typecheck 0 · build 0
make accesibilidad ............................................. 18 páginas · 0 violaciones
make diseno · make imagenes · make respaldo .................... PENDIENTES (fallan en voz alta)
```

Las tres que faltan siguen declaradas y fallando con mensaje, que es lo que se decidió en D-06: una
puerta que no puede pasar no protege de nada.

### 14.3 Lo que sigue abierto, y por qué

Nada de esto depende de un tercero, pero **queda fuera de las autorizaciones dadas** y no se ha
tocado:

| Pendiente | Qué falta | Coste estimado |
|---|---|---|
| **`make diseno`** (D-06, D-19) | La puerta de los 34 CAG sobre el navegador. Hoy solo se acredita CAG-32, con axe. Se puede empezar por los medibles: tipografía efectiva, paleta, rejilla, área táctil, carrusel. | Medio |
| **Cobertura ≥70 %** (RNF-B1-043) | Instalar un proveedor de cobertura, medir y subir. Hoy hay 55 pruebas sin cifra de cobertura. | Medio |
| **D-31** | Las cinco políticas y las seis subcategorías siguen declaradas en más de un sitio: la configuración, la página que las publica y el pie. | Bajo |
| **D-27** | Acordeón, *toast*, pestañas, *stepper* y carga de archivos del Kit siguen sin existir; **no hay página que los consuma todavía**, y construir componentes sin consumidor es como acabó la galería de aplicaciones. | Alto, y conviene hacerlo con las páginas |
| **D-36** | El ADR que fija el alcance de i18n y lenguas étnicas (decisión, no código). | Bajo |
| **D-25** | El `<lastmod>` del `sitemap.xml`: no se emite porque no hay fechas reales de modificación y inventarlas sería peor. | Bajo, cuando haya contenido |

Y lo que depende de contenido o de servicios de terceros sigue igual: **11 hallazgos** esperando
textos legales, datos de la Entidad, contrato de la API, CMS o servicio de captcha.

---

## 15. Tercera pasada: la conformidad de diseño deja de ser una opinión (2026-10-01)

Tercera autorización expresa, para cinco puntos: `make diseno`, la medición de cobertura, D-31,
D-36 y `make imagenes`.

### 15.1 `make diseno`: 16 criterios medidos con navegador

Nace `sitio/tests/diseno.mjs`, que levanta el sitio compilado y comprueba en Chromium lo que hasta
ahora se acreditaba **leyendo el código**: que la barra de accesibilidad se oculte de verdad por
debajo de 992 px, que el carrusel tenga controles y pausa, que las tarjetas sean un `<a>`/`<button>`,
que el pie y los controles del carrusel superen 4,5:1, que el buscador se pueda usar con teclado y
borrar, que el aviso de salida aparezca, se cierre con `Escape` y devuelva el foco, que el CSS del
Kit se sirva del propio dominio y coincida byte a byte con el instalado, y que la prosa mida entre 45
y 80 caracteres por línea.

| Criterios comprobados con navegador (16) | Resultado |
|---|---|
| CAG-01/02/04, CAG-03, CAG-05, CAG-07, CAG-08, CAG-09, CAG-11, CAG-12, CAG-13, CAG-14, CAG-15, CAG-21, CAG-26, CAG-27, CAG-30, CAG-33/34 | **los 16 pasan** |

Y seis criterios no se comprueban aquí porque **ADR-0015 los declara como desviación justificada**
(CAG-06, CAG-18, CAG-19, CAG-22, CAG-25, CAG-31), más CAG-32 y CAG-28, que los mide
`make accesibilidad` con axe sobre 18 páginas.

**La puerta encontró un defecto real que ninguna lectura había visto.** El aviso de salida se abría,
pero **no se podía cerrar con el teclado y el foco no entraba en el diálogo**: la disposición monta el
componente con `v-if="enlacePendiente"` y `solicitarConfirmacion` escribe primero el enlace pendiente
y después `visible = true`, así que el `watch` del modal se registraba cuando la propiedad **ya valía
`true`** y nunca se disparaba; sin él no se añadía el escucha de `Escape` ni se enfocaba el botón de
cancelar. Se corrigió en tres piezas que se sostienen entre sí:

1. `watch(..., { immediate: true })` para que el montaje con el aviso ya abierto registre el escucha.
2. El disparador se guarda explícitamente (`origenDelAviso`, que el plugin pasa al composable), en
   lugar de deducirlo de `document.activeElement` cuando el foco ya está dentro del diálogo.
3. La devolución del foco se hace en `onUnmounted`, **después** de que el diálogo salga del DOM: al
   cerrarse por `v-if` el componente se desmonta y el observador de `visible` no llega a ver el
   `false`, de modo que la restauración tenía que vivir también ahí. Hacerlo antes dejaba el foco en
   el `<body>`, que es exactamente lo que CAG-21 prohíbe.

Es el tercer defecto de esta auditoría que sólo aparece ejecutando (el `tabla-govco` de D-37 y el
enlace de salto de R-P4 fueron los otros dos).

**Y obligó a corregir tres comprobaciones mías.** La primera versión de la puerta falló en CAG-09
(contaba enlaces en vez de secciones internas), CAG-14 (`tabIndex` devuelve 0 en un botón
deshabilitado aunque el navegador no lo enfoque) y CAG-26 (la tarjeta **es** el `<a>`, no lo
contiene). Los tres defectos eran de la comprobación, no del sitio; se corrigieron y se dejó escrito
el motivo en cada una.

### 15.2 Cobertura de pruebas: medida, publicada y con trinquete

| Proyecto | Sentencias | Ramas | Funciones | Líneas |
|---|---|---|---|---|
| Sitio | 40,36 % | 36,11 % | 44,73 % | **45,58 %** |
| Panel | 3,81 % | 0,82 % | 4,34 % | **3,92 %** |

- **RNF-B1-043 pide un 70 % y no se alcanza.** La cifra está publicada aquí y en la salida de
  `make cobertura`, que lo dice antes de medir: no se disfraza el umbral.
- Los umbrales de `vitest.config.ts` **no son el objetivo, son un trinquete**: están justo por debajo
  de lo medido para que la cobertura no baje sin que nadie lo note.
- La cobertura del panel es baja porque el panel es una carcasa: 19 vistas sin implementar. Mide lo
  que hay, no lo que se querría.
- Nota para quien lea la salida: los avisos `PARSE_ERROR` de los `.vue` son ruido del remapeo de v8
  sobre componentes de un solo fichero, no fallos.

### 15.3 D-31, D-36 y `make imagenes`

| Punto | Qué se hizo | Verificación |
|---|---|---|
| **D-31** | Las cinco políticas y las seis subcategorías de participación pasan a declararse **una sola vez** en `config/sitemap.ts`, exportadas. El pie deriva sus enlaces con `POLITICAS.map(...)` (antes tenía su propia lista literal), la página de cada política compone su catálogo con `Object.fromEntries(POLITICAS_DEL_SITIO.map(...))` y `participa/index.vue` deja de tener su copia. | Las copias ya habían divergido: el `participa/index.vue` decía «Participación para la identificación de problemas…» donde la configuración decía otra cosa. `nuxt typecheck` en 0 y las dos páginas siguen pasando axe. |
| **D-36** | **ADR-0016** fija el alcance: la sede se publica en castellano con `lang="es-CO"`, no hay conmutador de idioma, la incorporación de una lengua étnica exige traducción con revisión de hablantes, contenido real y responsable, y el *fallback* es el de RN-TX-D05 (castellano marcado como original). FUN-010 queda sustituido por esta decisión. | Resuelve la contradicción **C-08**, que quedaba abierta. `auditoria-sede.md` §6 apunta ya al ADR. |
| **`make imagenes`** | Nace `scripts/verificar-imagenes.sh`: exige `@sha256:` en toda imagen de terceros y exime a las que se construyen en el repositorio. **Encontró una fuga real**: `compose.override.yaml` traía `axllent/mailpit:latest`. Se fijó a `v1.31.3@sha256:ed9b00c6…`. | La puerta pasa: 6 imágenes declaradas, 3 de terceros fijadas por resumen, 3 propias. Ya está en `make comprobar`. |

Una imagen con etiqueta móvil es un artefacto que puede cambiar sin que nadie revise el repositorio:
en el contenedor que recibe el correo de la sede, eso significaba que el binario de producción podía
cambiar entre dos despliegues idénticos.

### 15.4 Estado de la cadena de puertas

```
make comprobar   contrato · formato-verificar · analisis · pruebas · unidad · cobertura
                 · tipos · compilar · accesibilidad · imagenes      → 10 puertas, todas pasan
make diseno ................................................. 16 criterios con navegador · 0 fallos
make accesibilidad .......................................... 18 páginas · 0 violaciones
make unidad ................................................. 55 pruebas · 0 fallos
make cobertura .............................................. sitio 45,58 % · panel 3,92 %
make trazabilidad ........................................... 137 criterios · 47 con evidencia (34 %)
make imagenes ............................................... 3 imágenes de terceros fijadas
make respaldo ............................................... PENDIENTE (falla en voz alta)
```

**La matriz de trazabilidad, ya generada, pasa de 35 a 47 criterios con evidencia y de 4 a 20 con
prueba automatizada.**

---

## 16. Cuarta pasada: los tres criterios de diseño que quedaban (2026-10-01)

### 16.1 CAG-16, CAG-17 y CAG-29

| Criterio | Qué se hizo | Verificación |
|---|---|---|
| **CAG-16** | La leyenda de obligatorios **ya existía**, pero estaba en medio del formulario: `tipoSolicitud` y `descripcion` son obligatorios y aparecían antes que la explicación del asterisco. Se movió al principio del formulario, con `id` e `aria-describedby` en el `<form>`, y donde estaba se dejó sólo lo que aporta algo nuevo («los campos sin asterisco son opcionales»). | **Medido**: la leyenda va antes del primer campo obligatorio (`compareDocumentPosition`) y menciona el asterisco. |
| **CAG-17** | `autocomplete` en los 13 campos visibles del formulario —`off` donde el estándar no tiene un equivalente, porque un desplegable del dominio o un texto libre no se autorrellenan— y también en el buscador. Estados completos: foco al cobalto, **deshabilitado legible** (el `opacity: .65` de Bootstrap sobre gris claro dejaba el texto por debajo de 4,5:1; ahora fondo Solitude del Kit, Matterhorn de texto y opacidad 1) y validez con el rojo institucional `#A80521` (7,7:1) en vez del `#dc3545` de Bootstrap. | **Medido con navegador**: 13 campos, 0 sin `autocomplete`; el control deshabilitado mide opacidad 1 y fondo `rgb(229, 236, 248)`. |
| **CAG-29** | Aviso de carga prolongada: si la consulta del catálogo pasa de **diez segundos**, el estado de carga añade un mensaje dentro del mismo `role="status"` que ya existía, con un temporizador que se cancela al llegar la respuesta o al desmontar la página. | **No medido**: hace falta un servicio que tarde más de diez segundos, y en este entorno la API no responde. La matriz lo lista como «implementación» —que es exactamente lo que es— y no como prueba. |

La puerta de diseño pasa de 16 a **18 criterios medidos**, y con esto **ningún CAG queda sin evidencia
en la matriz**: los 34 están citados en el código, en una puerta o en un ADR de desviación.

### 16.2 Estado final de la cadena

```
make comprobar   contrato · formato-verificar · analisis · pruebas · unidad · cobertura
                 · tipos · compilar · accesibilidad · imagenes      → 10 puertas
make diseno ................................................. 22 criterios · 0 fallos
make accesibilidad .......................................... 18 páginas · 0 violaciones
make unidad ................................................. 55 pruebas · 0 fallos
make cobertura .............................................. sitio 45,58 % · panel 3,92 %
make imagenes ............................................... 3 imágenes de terceros fijadas
make trazabilidad ........................................... 137 criterios · 50 con evidencia (36 %)
                                                              · 23 con prueba automatizada
                                                              · 0 CAG sin evidencia
make respaldo ............................................... PENDIENTE (necesita Docker)
```

### 16.3 Lo que queda, y por qué

- **`make respaldo`**: verificar una copia de seguridad exige un destino real y una base de datos
  que restaurar. Sin Docker levantado, un script que diga «copia hecha» sería exactamente el tipo de
  puerta falsa que D-06 retiró.
- **D-27** (acordeón, *toast*, pestañas, *stepper* y carga de archivos del Kit): construir
  componentes de interfaz sin la página que los consume es cómo acabó la galería de aplicaciones —sin
  montar—. Se harán con los módulos que los necesiten.
- **D-25** (`<lastmod>` del `sitemap.xml`): no hay fechas reales de modificación de contenido, e
  inventarlas sería peor que no emitirlas. Es el mismo criterio que dejó el `IT` del tablero en cero.
- **La cobertura del 70 %** (RNF-B1-043) sigue sin alcanzarse: 45,58 % en el sitio y 3,92 % en el
  panel. La cifra está medida y publicada, y el trinquete impide que baje.
- **11 hallazgos de contenido y de terceros** siguen igual: textos legales, datos de la Entidad,
  contrato de la API, CMS y captcha.

---

## 17. D-29 y el recuento final, verificado uno por uno (2026-10-01)

### 17.1 Una corrección a mi propio recuento

En el informe de la pasada anterior escribí que **lo único que quedaba de código era D-27**. Era
falso: al recorrer el registro con una comprobación mecánica apareció **D-29 —metadatos de
compartición y URL canónica— sin tocar desde la primera edición**. El índice por gravedad de §10.1
lo listaba entre los medios y nadie lo había abordado. Queda escrito aquí porque un auditor que
cuenta mal sus propios hallazgos está haciendo exactamente lo que critica.

### 17.2 D-29, cerrado

**El defecto.** El sitio no publicaba **ni una etiqueta `og:` ni un `<link rel="canonical">`**. Al
compartir un enlace —WhatsApp, X, cualquier red social— la vista previa salía sin título y sin
imagen, y sin decir de qué sede era; y los buscadores tenían que deducir por su cuenta la dirección
canónica de cada página.

**La corrección.** Un composable, `useMetadatosComparticion`, que se declara **una vez en la
disposición** —que envuelve todas las páginas y se renderiza en el servidor, que es donde lo lee un
rastreador— y que:

- publica `og:title`, `og:site_name`, `og:locale` (`es_CO`), `og:type`, `og:image` (el logotipo real
  de la Entidad, en URL absoluta), `og:image:alt` y `twitter:card`;
- emite `<link rel="canonical">` y `og:url` con la URL absoluta de la página;
- **toma el título del catálogo de rutas** (`config/sitemap.ts`), de modo que la vista previa y el
  mapa del sitio dicen lo mismo en vez de que cada uno invente su rótulo;
- deja que cada página lo afine: la ficha de un trámite comparte **su** nombre y su resumen del
  contrato, y una política comparte su nombre y su propósito ya publicados;
- **no declara canónica en el 404**: la dirección que acaba de fallar no es una dirección buena que
  ofrecer a nadie;
- usa el dominio declarado (`runtimeConfig.public.dominio`, el mismo que alimentan `robots.txt` y
  `sitemap.xml`) y sólo si no lo hay cae a la cabecera `Host`, porque detrás de un proxy esa cabecera
  puede traer el nombre interno del contenedor.

**Lo que no se hizo, y por qué.** No se inventa `og:description`: sólo se emite cuando la página la
declara. Escribir veinticinco textos promocionales para que la vista previa quede más bonita es el
tipo de dato inventado que esta auditoría persigue; sin descripción, la vista previa muestra el
título y el nombre de la sede, y las dos cosas son ciertas.

**Verificación** (comprobación añadida a `make diseno`, que entonces pasó de 18 a 19 y hoy va en 20):

```
/accesibilidad  → canónica https://staging.santamarta.gov.co/accesibilidad · og:title
                  «Declaración de accesibilidad» · og:image absoluta · og:locale es_CO
/404            → sin canónica y sin og:url
```

**Un error por el camino, que conviene dejar escrito.** La primera versión llamaba a `useError()`
dentro de un `computed`. Los composables de Nuxt que necesitan la instancia no se pueden invocar
cuando el `computed` se evalúa, así que **todas las páginas respondían 500** con `NUXT_E1001`. El
`typecheck` no lo vio —compila— y la comprobación de la puerta sí. Es el cuarto defecto de esta
auditoría que sólo aparece ejecutando.

### 17.3 Recuento final, con la comprobación de cada pieza

Sobre los **59 elementos** del registro (52 hallazgos + 7 regresiones), verificados uno por uno
contra el árbol:

| Estado | Cuántos | Cuáles |
|---|---|---|
| **Cerrados en código y verificados** | **44** | 37 hallazgos + las 7 regresiones R-01…R-07 |
| Parciales (parte depende de un tercero o de un acto humano) | 6 | D-03 sugerencias (servicio), D-08 ranking (analítica), D-23 declaración firmada, D-25 `<lastmod>` (fechas reales), D-32 tope del menú (CMS), D-33 (verificado en código, sin comprobación visual a 768 px) |
| Bloqueados por contenido o servicios de terceros | 8 | D-07, D-10, D-13, D-15, D-17, D-18, D-44, D-46 |
| Bloqueado por el contrato | 1 | **D-27**, sólo su mitad de confirmación: la pantalla con radicado necesita un backend que radique (§18) |

**D-27 se resolvió en la mitad que se podía resolver** (§18) y **D-19 quedó cerrado** con las 112
pruebas y la cobertura de §20.2. Con eso, **todos los elementos abiertos del registro dependen de
contenido externo, de un servicio de terceros, de un acto humano o de la autenticación** —es decir,
de nada que se pueda escribir aquí—. El detalle exacto, al cierre, está en §21.

---

## 18. D-27, mitad de pasos: el formulario largo se divide en pasos (2026-10-01)

**El requisito, literal.** RF-B2-077 (Must) pide «procesos largos subdivididos en **pasos
numerados**» y su criterio de aceptación describe el resultado: «Paso 1 de 5» **con los pasos
pendientes identificables**. CAG-20 añade la línea de avance y admite dos caminos: «permitir saltar
pasos libremente o bloquearlos según obligatoriedad». `Sección 3:360` fija el patrón de encabezado:
«Wizard con migas (`Paso 1 de 4: Datos del solicitante`) y orden lógico de campos».

**La decisión de diseño, y por qué.** Tres pasos, que no se inventan: el formulario ya tenía tres
bloques con sentido propio —**Solicitud**, **Datos del solicitante** y **Autorización y envío**— y lo
que faltaba era hacerlos visibles. Se eligió **salto libre** en vez de bloqueo: CAG-20 permite las
dos, y bloquear un paso impide consultarlo antes de rellenarlo, que es justo lo que hace falta para
decidir si el trámite es el que se busca.

**Cómo queda.**

- **Línea de avance** con `<ol>` de tres botones, encabezado «Paso N de 3: nombre», y el paso actual
  marcado con `aria-current="step"`.
- **El estado se dice en texto** —«actual», «completado», «pendiente»—, no sólo con color, porque el
  color no lo lee todo el mundo. Contrastes medidos: cobalto sobre Solitude 7,13:1, Havelock Lue
  sobre blanco 4,67:1, Matterhorn sobre blanco 8,59:1.
- **«Completado» sale de la validación, no de la posición.** Fue un defecto de la primera versión:
  saltar al paso 3 marcaba los anteriores como completados. Ahora un paso está completo cuando sus
  campos son válidos, y saltar no cambia eso.
- **Avanzar exige que el paso esté completo**: si no, se marcan los campos con `aria-invalid` y el
  foco va al primero, igual que al enviar.
- **Al cambiar de paso el foco va a la línea de avance**, para que quien navega con teclado no tenga
  que recorrer la página otra vez.

**Verificación** (`make diseno`, comprobación propia; la puerta pasa de 19 a **22 comprobaciones**):

```
«Paso 1 de 3: Solicitud» · 3 pasos (actual, pendiente, pendiente) · avanzar sin rellenar:
frena y marca campos (foco en tipoSolicitud) · salto libre: sí
estados tras saltar al 3: pendiente, pendiente, actual
```

**Dos defectos que aparecieron al revisarlo, y que la puerta ahora caza.**

1. **Enviar desde el último paso con los anteriores incompletos no llevaba a ninguna parte.** Es el
   precio del salto libre, que CAG-20 permite: los campos marcados están en un paso oculto
   (`display: none`), el foco no se puede poner en un elemento que no se ve, y el botón de enviar
   **parecía no hacer nada**. Ahora, si el primer fallo está en un paso anterior, se vuelve a ese paso
   antes de enfocar el campo.
2. **Avanzar marcaba en rojo los campos de los pasos siguientes.** `avanzar()` guardaba el resultado
   de validar **todo** el formulario, así que al llegar al paso 2 sus campos ya aparecían inválidos
   sin que nadie los hubiera tocado. Ahora sólo se marcan los fallos del paso que se está rellenando.

**Lo que no se hizo.** La confirmación con **número de radicado** (RF-B1-083, Must): el formulario no
radica, así que no hay radicado que mostrar y mostrarlo sería inventarlo. Es el mismo criterio con el
que el tablero del panel se dejó en cero.

---

## 19. Revisión de mis propias afirmaciones (2026-10-01)

Antes de dar por bueno el trabajo revisé lo que le había dicho a quien lo encargó. **Dos de mis
afirmaciones eran falsas**, y las dos del mismo tipo: daban por cubierto lo que no lo estaba.

### 19.1 Lo que estaba mal

| Lo que dije | Lo que había |
|---|---|
| «Los cuatro defectos que sólo aparecieron ejecutando tienen su comprobación en `make diseno`» | **D-37 no tenía ninguna.** La corrección de la tabla del Kit se hizo antes de que existiera la puerta de diseño, y nadie la comprobaba: se podía volver a anidar mal sin que nada lo dijera. |
| Que un 500 lo cazaría alguna puerta | **Un 5xx no fallaba ninguna.** La de accesibilidad marcaba la página como «no auditada» y seguía en verde: cuando todas las páginas devolvían 500, la puerta no dijo nada. Sólo se notó porque la de diseño no encontraba sus selectores. |

**El recuento de entonces se sostenía.** Se volvió a derivar: 52 hallazgos + 7 regresiones = 59;
abiertos 16 (7 parciales + 8 de terceros + la confirmación de D-27); cerrados **43**. Con el cierre
posterior de D-19 —112 pruebas y la cobertura de §20.2— el recuento final es **44 cerrados y 15
abiertos** (§21).

### 19.2 Lo que se corrigió

- **Comprobación de D-37** en `make diseno`, y no por clases: comprueba que el `thead` calcula
  `position: sticky`, que es una regla de **descendencia** de `all.css` y por tanto sólo llega si la
  clase está donde el Kit la espera.
- **Un 5xx falla las dos puertas.** La de accesibilidad ya no lo omite; la de diseño lo dice con un
  mensaje que distingue «no responde» de «responde 500» —averías distintas que llevan a sitios
  distintos—.
- **Comprobación de humo** en `make diseno`: ocho rutas que no pueden devolver 5xx.
- **Las comprobaciones que daban por hecha la página abierta** ahora abren la suya. Se descubrió
  porque dos fallaron solas al añadir una comprobación nueva delante: el carrusel se estaba midiendo
  sobre `/pqrsd`.
- **Dos defectos del wizard**, descritos en §18.

### 19.3 La prueba de que las comprobaciones sirven

Una comprobación que nunca ha fallado no está demostrada. Se reintrodujo cada defecto, se compiló y
se ejecutó la puerta:

| Defecto reintroducido | Resultado |
|---|---|
| `useError()` dentro de un `computed` (el `NUXT_E1001`) | `make accesibilidad` **falla**; `make diseno` **falla** con «El sitio responde 500 en …/: no se puede comprobar el diseño de una página que no se abre» |
| `tabla-govco` devuelta al `<table>` | `make diseno` **falla en D-37**: «la clase está en `<TABLE>` · la `<table>` no la lleva: false · el thead calcula position: sin thead» |
| Quitar el retorno al paso del fallo | `make diseno` **falla en CAG-20**: «enviar desde el último paso: NO vuelve al paso del fallo» |

Los tres defectos se revirtieron después, y la puerta volvió a pasar: **22 comprobaciones, 0 fallos**.

---

## 20. Cierre de las puertas que faltaban y de la cobertura (2026-10-01)

Última pasada: lo que quedaba por hacer sin depender de nadie.

### 20.1 `make respaldo`: la copia se restaura de verdad

Nace `scripts/verificar-respaldo.sh`, que hace el ciclo entero contra la base de datos real: volcado
con `pg_dump`, restauración en una base **temporal** y comparación **tabla por tabla con conteos
exactos** (`query_to_xml`, no estimaciones de `pg_stat_user_tables`). La base temporal se crea y se
destruye en la misma ejecución, con `trap` para que no quede ni si la puerta falla a mitad.

```
contenedor: sede-db-1 · base de datos: sede_electronica
volcado: 263775 bytes · restauración: sin errores
origen: 66 tablas · 2798 filas
La puerta pasa: la base se vuelca y se restaura con las mismas 66 tablas y 2798 filas.
```

Se probó también el caso negativo (contenedor inexistente → falla con mensaje claro, sin dejar
basura). **No usa el servicio `copia` del compose ni la frase de paso**: así la puerta se puede
ejecutar sin secretos y comprueba lo que importa —que la base se levante con los mismos datos—.

### 20.2 La cobertura del 70 % que pedía RNF-B1-043

| Proyecto | Antes | Ahora | Sentencias |
|---|---|---|---|
| Sitio | 45,58 % | **95,54 % de líneas** | 85,63 % |
| Panel | 3,92 % | **70,90 % de líneas** | 66,79 % |

Se pasó de **55 a 112 pruebas**. Lo que se añadió no es relleno para subir un número:

- **`useAccesibilidad` estaba en 0 %** y aplica contraste, tamaño de letra y espaciado a toda la
  página: se prueba montando un componente real, porque lo que hay que comprobar ocurre en
  `onMounted` y en el `watch`.
- **Los componentes base del panel** (`BaseButton`, `BaseModal`, `FormField`, `AccessibilityBar`…)
  no se habían montado nunca, y `D-16` va precisamente de lo que sólo se ve al montarlos: el
  `aria-sort`, el `for`/`id` de las etiquetas, la trampa de foco del modal.
- **El enrutador real**: 18 módulos que exigen sesión y permiso, y un invitado al que no se le abre
  ninguno.
- **`services/http.ts`**: por donde sale todo lo que el panel pide al backend.

Una nota de honestidad: **tres pruebas de esta pasada fallaron por errores míos**, no del código —un
localizador que cogía el botón del buscador en vez del formulario, un evento sin `burbujeo` que por
eso no llegaba a `window`, y una comprobación que daba por abierta la página que dejó la anterior—.
Se corrigieron las tres, y quedan escritas aquí porque son exactamente el tipo de error que esta
auditoría le señala al código de producción.

---

## 21. Lo que queda al cierre, y por qué (2026-10-01)

**Recuento final: 44 de los 59 elementos del registro cerrados y verificados; 15 abiertos**, y
ninguno de los 15 se puede resolver escribiendo código aquí. Esta sección existe porque en el
informe anterior se dijo «11 hallazgos de contenido y de terceros» y **esa cifra era imprecisa**: el
número exacto es **13**, más los dos que se detallan aparte. Se corrige aquí, que es donde tiene que
estar.

### 21.1 D-27, sólo la confirmación con radicado — ⛔ bloqueada por el contrato

La mitad de pasos está cerrada (§18). Lo que falta es la pantalla de resultado de **RF-B1-083**
(Must): número de radicado, próximos pasos y tiempo estimado.

**No se puede hacer hoy**: el formulario de petición **no radica** —no hay backend que reciba la
solicitud—, así que **no existe ningún radicado que mostrar**. Una pantalla de confirmación sería un
número inventado, que es el defecto de D-05 y de D-23 otra vez. **Bloqueada por el contrato**, no por
decisión de diseño.

### 21.2 Los trece hallazgos que dependen de contenido o de un tercero

| # | Hallazgo | Qué falta | De quién depende |
|---|---|---|---|
| 1 | **D-03** Buscador | Sugerencias mientras se escribe y tolerancia a erratas | Servicio de sugerencias |
| 2 | **D-07** Noticias | Que la portada publique noticias | Contenido de la Entidad |
| 3 | **D-08** Portada por tareas | El ranking de «más solicitados» | Analítica de uso |
| 4 | **D-10** Políticas | El documento de las cinco políticas y su acto de adopción | Textos legales de la Entidad |
| 5 | **D-13** Adjuntos y captcha | Adjuntar archivos y captcha en los formularios | Backend y servicio de captcha |
| 6 | **D-15** Módulos del panel | Los 18 módulos que hoy son un marcador | CMS y backend |
| 7 | **D-17** Secciones vacías | Las seis secciones obligatorias que dicen «en preparación» | Contenido de la Entidad |
| 8 | **D-18** Datos de la Entidad | Que los datos vengan del contrato en vez de estar a mano | `GET /entidad`, que no existe en el contrato |
| 9 | **D-23** Declaración de conformidad | Firmarla y publicarla con sus doce elementos | Acto humano: la Entidad |
| 10 | **D-25** `sitemap.xml` | El `<lastmod>` de cada página | Fechas reales de modificación del contenido |
| 11 | **D-32** Tope del menú | Que el límite se aplique al publicar, no en el navegador | CMS |
| 12 | **D-44** `/servicios` y `/tramites` | Unificar dos nombres para el mismo destino | Decisión de contenido de la Entidad |
| 13 | **D-46** Logotipo | Un único activo canónico con sus derivados | Activo oficial de la Entidad |

Los dos que quedan fuera de esa lista:

- **D-27** (arriba): bloqueado por el contrato.
- **D-33** (panel responsive): **el código está hecho y verificado** —menú móvil, barra que se
  desplaza, `aria-expanded`—, pero la comprobación **visual** a 768 px no se puede hacer hoy: la
  guardia de sesión impide abrir el panel sin una autenticación que todavía no existe. Es una
  verificación pendiente, no un defecto pendiente, y así está declarado.

### 21.3 De RNF-B1-043: HTML/CSS válido y CI/CD

La cobertura del 70 % **sí se alcanza** desde §20.2 (sitio 95,54 % de líneas, panel 70,90 %). Lo que
sigue **sin acreditarse** del mismo requisito es lo otro:

- **HTML y CSS válidos.** No hay validador en la cadena. El sitio construido pasa axe, y eso mide
  accesibilidad, no validez de marcado. Añadirlo es una puerta más —`html-validate` o el validador del
  W3C sobre las 18 páginas— y no se ha hecho.
- **CI/CD.** No hay integración continua en el repositorio: las puertas existen y se ejecutan a mano
  (`make comprobar`), pero nadie las ejecuta en cada propuesta de cambio. Hasta que eso exista, una
  puerta depende de que alguien se acuerde de llamarla, que es exactamente el problema que D-06
  describía al principio.

---
## Cierre

> **Actualización 2026-10-01.** Esta lista describe el estado al cerrar la edición tercera. Un
> lote posterior de correcciones se verificó en la **sección 12**: el punto 4 quedó hecho (D-12) y
> el 5 a medias (el banner y el modal existen, con lo que les falta en §12.2); los demás siguen
> abiertos, y el lote introdujo siete defectos nuevos (R-01 a R-07). Léase esta lista junto con
> aquella sección.

**Lo que hay que hacer con este documento.** No es una lista de reproches: es el orden de trabajo
que se deduce de él. Si solo pudieran hacerse diez cosas, serían estas, en este orden:

1. **Quitar del panel las cifras simuladas y la afirmación de doble factor** (D-05, D-04). Es un
   fichero y un comentario: mientras estén, la interfaz miente.
2. **Escribir la primera prueba de accesibilidad con axe** (D-19). Desbloquea CAG-32, RNF-B1-014 y
   convierte siete «no verificable» de esta auditoría en datos.
3. **Alinear el `Makefile` con lo que existe y regenerar o retirar la matriz de trazabilidad**
   (D-06). Un expediente que acredita pruebas inexistentes es peor que no tener expediente.
4. **Poner el carrusel en pausa por defecto** (D-12) y **persistir la preferencia de
   accesibilidad con el enlace al Centro de Relevo** (D-11): dos Must, dos ficheros.
5. **Construir el banner de cookies** (D-01) y **el aviso de salida con su lista blanca** (D-02,
   D-28): los dos requisitos legales de la carcasa que hoy no existen.
6. **Decidir las 25 contradicciones de la sección 6** y escribir sus ADR. Sin eso, el próximo
   equipo volverá a resolverlas a mano, una por una, y de forma distinta.
7. **Publicar la declaración de conformidad de accesibilidad** con sus doce elementos (D-23): es
   un requisito de la Resolución 1519 y hoy no existe.
8. **Publicar las cinco políticas y enlazar la búsqueda real** (D-10, D-03): son las dos cosas que
   cualquier ciudadano toca en su primera visita.
9. **Convertir `/transparencia`, `/tramites` y `/noticias` en las secciones que el menú y el
   `sitemap.xml` ya anuncian** (D-17): hoy el proyecto promete en el menú lo que no publica.
10. **Terminar el panel** (D-15): autenticación, permisos por módulo, CMS y tablero ITA en cero.
    Es el 45 % del producto y hoy es una carcasa de navegación.

**Lo que este documento no dice.** No dice que el diseño esté mal hecho. Dice lo contrario: la
carcasa del sitio público —barra, cabecera, menú, miga, pie, carrusel, contraste, reflujo— está
resuelta con un rigor que no es habitual, y está resuelta **con las mediciones y los motivos
escritos al lado de cada decisión**. Los 68 requisitos que no se cumplen se reparten en dos
montones muy distintos: **43 porque la pieza no existe todavía** (contenido, contrato, CMS,
back-office) y **11 porque lo construido contradice el requisito** —cookies, aviso de salida,
buscador, autenticación, cifras simuladas, carrusel en marcha, barra sin memoria, tabla sin
ordenamiento, enlaces visitados, identidad del panel y datos duplicados de la Entidad—. Ese
segundo montón es el que se corrige esta semana; el primero es el proyecto.

---

*Auditoría de solo lectura: no se modificó ningún fichero de `sitio/`, `panel/`, `backend/`,
`docs/`, `sede-electronica-doc/`, `vendor-src/` ni `contract/`. El único fichero escrito es este
informe. La edición anterior queda disponible en el historial de Git
(`git show d728457:auditoria-sede.md`).*







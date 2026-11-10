# Criterios de aceptación — FUENTE DE VERDAD (módulos 01 y 02)

> **AVISO — este es el documento vigente.** Cuando `criterios-aceptacion.md` (los 16 escenarios Gherkin originales, de esta misma carpeta) difiera de este archivo, **manda este**. El original se conserva sin cambios de contenido, solo para trazabilidad histórica: tiene defectos de validez documentados en la auditoría del 2026-11-10 (selectores y rutas que no existen, envoltura JSON:API en lugar de `{success,message,data,errors}`, umbrales sin método de medida, 40 RF sin escenario).
>
> **Decidido por el titular (José, 2026-11-10):** estos criterios se agregan a la carpeta del pack con este aviso, sin reemplazar el original. Al reconciliar el pack con `contract/openapi.yaml` (tarea posterior al Módulo 01), el contrato sigue mandando sobre ambos (CT-02).

**Fecha:** 2026-11-10 · **Origen:** auditoría `auditoria-entidad-transparencia-2026-11-10.md` (Project «sede») · **Estado:** vigente; se actualiza criterio a criterio a medida que cada tarea se verifica en staging.
**Qué corrige:** los 16 criterios Gherkin existentes (defectos de validez), los 40 RF sin escenario (completitud) y los RNF sin método de medida.
**Principios de redacción:** cada criterio es (1) **verificable por máquina** o declara que es manual, (2) **coherente con el producto desplegado** (envoltura `{success,message,data,errors}`, rutas reales, selectores reales), (3) **medible** (umbral + instrumento + origen de la medida) y (4) con **estado vacío** definido.
**Etiquetas:** [HOY] verificable ya contra staging · [PENDIENTE-X] depende de algo que no existe (X = qué) · [MANUAL] no automatizable.

---

## A. Reglas transversales (nuevas)

**CT-01 — Estado vacío (regla de oro del repo: no inventar contenido).**
Dado que una subsección, listado o ficha no tiene contenido publicado, cuando se muestra, entonces la página responde 200, muestra «No consta información publicada» con el motivo (p. ej. «la Entidad aún no la ha entregado») y **no** muestra datos de ejemplo ni de otra entidad. *Consecuencia:* ningún criterio de «hay al menos un documento» puede exigirse mientras la Entidad no entregue el contenido; se exige el estado vacío.

**CT-02 — Fuente de verdad.** El contrato `contract/openapi.yaml` manda sobre este pack: si difieren, se corrige el pack. La prueba de deriva (rutas ↔ contrato) ya existe en `backend/tests/Feature/Contrato`.

**CT-03 — Envoltura.** Toda respuesta de `/api/v1/*` (éxito o error) cumple `{success: bool, message: string|null, data: any|null, errors: object|null}`. Los 404 de ruta inexistente dentro de `/api/v1` cumplen la envoltura. [HOY]

**CT-04 — Medición de RNF.** Todo umbral de rendimiento declara: percentil, instrumento, origen de red (Bogotá por defecto), carga (VUs/duración), si el HTML se sirve desde el borde, y URL probadas. Una cifra sin esos cinco datos no es un criterio.

**CT-05 — Fechas y plazos.** Los plazos «antes del 31-ene», «día 15» y «5 días hábiles» se calculan con una función única (calendario de festivos de Colombia, zona `America/Bogota`) con pruebas de fronteras (31-ene 23:59, día no hábil, año bisiesto). Ninguna fecha va escrita en el criterio: se usa «el 31 de enero del año en curso».

**CT-06 — Contenido legal real en producción.** Una política con `es_ejemplo = true` o con `acto` o `vigente_desde` nulos **bloquea el despliegue a producción** (puerta de CI/despliegue). [HOY: las 5 políticas de staging incumplen]

---

## B. Criterios Gherkin existentes, corregidos

### CA-01-001 — Top bar GOV.CO
```gherkin
Scenario: El top bar cumple medidas del Kit UI
  Given el usuario abre cualquier página pública
  Then existe un único elemento ".barra-superior-govco" visible
  And su altura computada es 56 px y su fondo es rgb(9, 67, 181)
  And contiene un enlace a "https://www.gov.co/home/" con aria-label "Portal del Estado Colombiano - GOV.CO"
  And ese enlace abre en nueva pestaña con rel que incluye "noopener"
Scenario: Orden de foco
  Given el usuario presiona Tab desde la barra de URL
  Then el primer foco es el enlace "Saltar al contenido principal" (WCAG 2.4.1)
  And el segundo bloque focalizable es el enlace GOV.CO, con foco visible
```
*Cambios:* selector real (`.barra-superior-govco`, antes `data-testid` inexistente); el primer foco es el *skip link* (el pack lo contradecía). [HOY]

### CA-01-007 — Menú principal
```gherkin
Scenario: Ítems y orden
  Given la página "/" carga
  Then el <nav> con aria-label "Menú principal de la Sede Electrónica" tiene entre 4 y 7 ítems de primer nivel
  And los cuatro obligatorios aparecen en este orden relativo: Inicio, Transparencia y acceso a la información pública, Atención y Servicios a la Ciudadanía, Participa
Scenario: Teclado en submenús
  Given el foco está en "Participa" y su submenú está cerrado
  When presiona Enter o ArrowDown
  Then el submenú se abre y el foco pasa a su primer ítem
  When presiona ArrowDown
  Then el foco pasa al siguiente ítem
  When presiona Escape
  Then el submenú se cierra y el foco vuelve a "Participa"
```
*Cambios:* rango «4–7» explícito y orden relativo; el submenú se prueba con el botón real. [HOY]

### CA-01-009 — Buscador
```gherkin
Scenario: Sugerencias con tolerancia ortográfica
  Given el usuario escribe "prespuesto" y pasan 350 ms sin teclas
  Then hay como máximo 10 sugerencias
  And cada sugerencia pertenece a un conjunto de contenido interno (trámites, políticas, páginas PUBLICADAS de la sede)
  And ninguna tiene URL de otro dominio
Scenario: Cobertura del índice
  Given existen la página PUBLICADA "/transparencia" y la política "uso-de-cookies"
  When el usuario escribe "transparencia" y "cookies"
  Then cada consulta devuelve la página o política homónima como primer resultado
  And una página "en preparación" no se ofrece
Scenario: Sin resultados
  Given el usuario escribe una cadena sin coincidencias
  When ejecuta la búsqueda
  Then se muestra UNA vez «No encontramos resultados para «…»» y enlaces a las secciones principales
```
*Cambios:* debounce 350 (decisión D-I1), el índice debe incluir las páginas de la sede (hoy `transparenci` → 0 sugerencias y `transparencia` → «licencia de tránsito»), se elimina la lista arbitraria de dominios y las «5 búsquedas relacionadas» (no están en el RF). [M01-3, ADR-0021: HECHO en el sitio (desplegable y /buscar, solo páginas publicadas); `/transparencia` entrará cuando se publique. Falso positivo de la API «transparencia» → «licencia de tránsito»: pendiente, no se corrige aquí]

### CA-01-015 — 404 personalizado
```gherkin
Scenario: Ruta inexistente de la sede
  Given el usuario (Accept: text/html) abre "/ruta-inexistente-xyz"
  Then el estado HTTP es 404
  And la página contiene un buscador, un menú de navegación y un enlace "Volver a la portada"
Scenario: Ruta inexistente de la API
  Given un cliente pide "/api/v1/identidad/seccion-inexistente"
  Then el estado es 404 y el cuerpo es {"success":false,"message":"El recurso no fue encontrado.","data":null,"errors":null}
```
*Cambios:* el CTA real es «Volver a la portada»; el error de API usa la envoltura del producto, no JSON:API. [HOY]

### CA-01-017 — Consentimiento de cookies
```gherkin
Scenario: Primera visita
  Given el navegador no tiene "sede-consentimiento"
  When carga cualquier página
  Then aparece el banner con "Aceptar todas", "Rechazar opcionales" y "Guardar mi elección"
  And no se crea ninguna cookie propia de la sede
  And no se hace ninguna petición a dominios de analítica (lista cerrada configurable, vacía por defecto)
Scenario: Revocación
  Given el usuario aceptó la analítica
  When usa "Configurar cookies" del pie y guarda sin marcar nada
  Then el consentimiento queda con aceptadas = []
  And las cookies y peticiones de analítica cesan en la siguiente navegación
```
*Cambios:* etiquetas reales; la regla se mide por **peticiones a la lista de dominios de analítica**, no por «cookies» en abstracto. Hoy el beacon de Cloudflare Insights se solicita antes del consentimiento: o se desactiva en Cloudflare o se declara estrictamente necesario y se justifica en la política de cookies. [HOY, con defecto abierto H7]

### CA-01-021 — Paginación
```gherkin
Scenario: Teclado
  Given el usuario está en la página 1 de un listado
  When enfoca "Siguiente" y presiona Enter
  Then la URL contiene "page=2" y el foco queda en un control del paginador
  And el control de la página 2 tiene aria-current="page"
Scenario: Tamaño táctil
  Given el viewport es 375 px
  Then todos los controles del paginador miden al menos 44×44 px
```
*Cambios:* se retira la cita a WCAG 2.5.5 como criterio AA (es AAA); el umbral de 44 px es **requisito del proyecto** (RNF-B3-005), no de la norma. Mínimo normativo WCAG 2.2: 24 px. [HOY]

### CA-02-001 — Subsecciones de Transparencia
```gherkin
Scenario: Estructura
  Given el usuario abre "/transparencia"
  Then el <nav> de subsecciones tiene exactamente 10 ítems, en el orden: Información de la entidad, Normativa, Contratación, Planeación/presupuesto/informes, Trámites, Participa, Datos abiertos, Información específica para grupos de interés, Obligación de reporte de información específica, Información tributaria
  And cada ítem lleva a una URL que responde 200
Scenario: Contenido o estado vacío
  Given una subsección sin documentos
  Then se aplica CT-01 (estado vacío) y NO se exige "al menos un documento"
```
*Cambios:* se elimina «cada ítem tiene al menos un documento» (imposible hoy); se alinean los nombres con el Anexo 2. [PENDIENTE-páginas de transparencia; PENDIENTE-confirmar con la Res. 1519 que son 10]

### CA-02-006 — Directorio SIGEP
```gherkin
Scenario: Alta en la fuente
  Given la fuente de servidores contiene un nuevo cargo
  When transcurre 1 día hábil (CT-05)
  Then el directorio público lo muestra
  And queda un registro en "audit_log" con origen, fecha y resultado
Scenario: Baja
  Given un servidor se desvincula en la fuente
  When transcurre 1 día hábil
  Then ya no aparece
Scenario: Fuente caída
  Given la fuente no responde
  Then se aplica el criterio de RF-02-023 y el directorio muestra la última sincronización con su fecha
```
*Cambios:* «1 día hábil» como el RF (antes 24 h hábiles); tabla real `audit_log`; caso de fuente caída. [PENDIENTE-confirmar que SIGEP ofrece una interfaz utilizable; HIPÓTESIS: no hay API pública abierta]

### CA-02-011 — Plan de Acción
```gherkin
Scenario: Vencimiento
  Given es el 31 de enero del año en curso
  And no hay un documento publicado con categoría "plan_accion" y vigencia = año en curso
  When corre el job diario de verificación
  Then el ítem de cumplimiento queda "No cumple" y se notifica al administrador de cumplimiento (correo y panel), una sola vez
Scenario: Alerta previa (RF-02-022)
  Given faltan 10 días para el 31 de enero y no hay Plan de Acción
  When corre el job
  Then se envía la alerta previa y no se repite al día siguiente
Scenario: Cumplimiento
  Given el Plan de Acción del año se publicó el 15 de enero
  When corre el job el 31 de enero
  Then el ítem queda "Cumple" y no hay notificación
```
*Cambios:* sin año fijo; une el vencimiento con la alerta a 10 días; idempotencia. [PENDIENTE-dominio documento + job]

### CA-02-021 — Versionado
```gherkin
Scenario: Reemplazo
  Given el documento "plan-accion-2026" está publicado con versión 1 y hash H1
  When el editor sube un reemplazo
  Then la URL canónica "/transparencia/documentos/plan-accion-2026" sirve la versión 2 con su hash H2
  And la versión 1 sigue disponible en "/transparencia/documentos/plan-accion-2026/versiones/1" con su hash H1 y su fecha de publicación
  And ambos hashes coinciden con el SHA-256 de sus archivos
Scenario: Eliminación lógica
  Given el editor elimina el documento
  Then el ciudadano recibe 404 en su URL canónica
  And el panel (con permiso) lo ve con "deleted_at", y "audit_log" registra usuario y momento
```
*Cambios:* URL histórica por versión (no por `?v=fecha` anterior a la publicación); `include=deleted` **sólo en el panel**, nunca en la API pública. [PENDIENTE-dominio documento]

### CA-02-024 — Integridad
```gherkin
Scenario: Cálculo al subir
  Given el editor sube un archivo de bytes B
  Then se persiste sha256(B) y la vista pública lo muestra junto al enlace
Scenario: Verificación por terceros
  Given una persona descarga el archivo y calcula SHA-256
  Then coincide con el publicado
Scenario: Detección de alteración
  Given el archivo en disco se altera fuera de la aplicación
  When corre la verificación periódica
  Then el documento se marca "integridad comprometida", se notifica al administrador y se registra en "audit_log"
```
*Cambios:* añade el caso de fallo (el sentido del RF). [PENDIENTE-columna de hash + verificación]

### CA-02-027 — Búsqueda de documentos
```gherkin
Scenario: Tolerancia ortográfica
  Given existen documentos con "presupuesto" en título o descripción
  When el usuario busca "prespuesto"
  Then esos documentos están entre los 5 primeros resultados y la coincidencia está en <mark>
Scenario: Filtros combinados
  When busca "ICA" con subsección "Información tributaria" y año 2026
  Then todos los resultados pertenecen a esa subsección y año, y mencionan "ICA"
Scenario: Precisión
  When busca "transparencia"
  Then ningún resultado tiene una similitud inferior al umbral configurado en "config/busqueda.php"
```
*Cambios:* medida de relevancia explícita (hoy el umbral 0,45 deja pasar «tránsito» para «transparencia»). [PENDIENTE-documentos]

### CA-02-029 — Tablero de cumplimiento (ITA)
```gherkin
Scenario: Ítem calculado por regla
  Given un documento HTML publicado con una <img> sin alt
  When se recalcula el tablero
  Then el ítem "Imágenes con texto alternativo" aparece "No cumple" con la lista de documentos afectados
Scenario: Recalculo
  Given el editor corrige el alt y guarda
  When se recalcula (job nocturno o al guardar)
  Then el ítem pasa a "Cumple"
```
*Cambios:* el catálogo de ítems del tablero debe existir como dato versionado (hoy el pack no lo define); sin «notifica al responsable» sin canal ni plazo definidos. [PENDIENTE-catálogo ITA]

### CA-CROSS-001 — Disponibilidad
```gherkin
Scenario: Sonda externa
  Given se define una URL pública de disponibilidad (p. ej. "HEAD /api/v1/entidad")
  Then responde 200 en menos de 2 s desde la sonda externa, 30 días seguidos ≥ 99,5 %
Scenario: Sondas internas
  Then "/health" y "/ready" responden desde la red interna y NO desde Internet (404/403 público)
```
*Cambios:* `/api/v1/salud` no existe ni debe existir publicada. [HOY para las internas]

### CA-CROSS-002 — Rate limiting
```gherkin
Scenario: Lectura pública
  Given un cliente hace 60 peticiones en una ventana a "/api/v1/entidad"
  When envía la 61
  Then recibe 429 con cabecera Retry-After (segundos restantes de la ventana) y la envoltura de error
Scenario: Login
  Given 3 intentos de login con el mismo correo en un minuto
  Then el cuarto recibe 429
Scenario: Búsqueda
  Given 120 peticiones por minuto a "/api/v1/buscar"
  Then la 121 recibe 429 (límite propio, sin sumar el límite general)
```
*Cambios:* valores reales del producto (60/min, 120/min búsqueda, 3/min login por correo y 20/min por IP); Retry-After dinámico. Si se quiere el endurecimiento del RNF-SEG-02 (5 intentos/15 min), se redefine el criterio **con** el cambio de código. [HOY]

### CA-CROSS-003 — Versionado de API
```gherkin
Scenario: v1
  When un cliente pide "/api/v1/entidad"
  Then responde 200 con la envoltura
Scenario: Versión no publicada
  When pide "/api/v2/entidad"
  Then responde 404 con la envoltura {"success":false,…}
```
*Cambios:* hoy `/api/v2/*` lo contesta Nuxt con otro formato; hay que enrutar `/api/*` completo al backend. [PENDIENTE-nginx/Nuxt]

---

## C. Un criterio medible por cada RF que no tenía escenario (40)

| RF | Criterio de aceptación | Verificación |
|---|---|---|
| RF-01-002 Pie | Cada campo devuelto por `GET /api/v1/identidad/footer` aparece en el DOM del `<footer>` y ninguno está vacío; el recuento esperado de campos se fija en el contrato | Playwright + contrato [HOY] |
| RF-01-003 Teléfonos | Todo teléfono mostrado cumple `^\(\+57\) 60[1-8] \d{3} \d{4}$` o es 018000/019000; el panel normaliza al guardar `4351719` + indicativo 605 | PHPUnit (normalización) + Playwright [HOY en pie] |
| RF-01-004 Logo | Logo ≤ 80 px de alto, `alt` no vacío, enlace a `/`; enlace textual «Inicio» presente | Playwright [HOY] |
| RF-01-005 Identidad | Top bar `#0943B5`; títulos en Nunito Sans 600–800; cuerpo ≥ 16 px; el uso de `system-ui` en cuerpo se declara como desviación en un ADR (o se carga Verdana) | Playwright + ADR [HOY] |
| RF-01-006 Volver arriba | Con `scrollY > 600` aparece un botón ≥ 44×44 px con `aria-label="Volver arriba"`; Enter lleva a `scrollY = 0` (sin animación con `prefers-reduced-motion`) | Playwright [HOY] |
| RF-01-008 Menú móvil | A 375 y 767 px hay un botón con `aria-expanded`; al abrirlo el foco queda atrapado, Esc lo cierra y devuelve el foco al botón; a 768 px no hay hamburguesa | Playwright [HOY] |
| RF-01-010 Mapa | `/sitemap.xml` responde 200 con `application/xml` y es un `<urlset>` válido (≤ 50 000 URL y ≤ 50 MB; si se supera, un índice); sus URL son absolutas, únicas y del dominio; contiene las rutas marcadas `publicada` (más las políticas ya adoptadas) y **una ficha `/tramites/{slug}` por cada trámite que publica `GET /api/v1/tramites`, ni una más ni una menos**; `<lastmod>` en `AAAA-MM-DD` **solo** cuando hay una fecha real de modificación (`actualizado_en` del trámite, `actualizada_en` de la política) — una página sin fecha real **no** lleva `lastmod` (decisión del titular, 2026-11-10: Google usa `lastmod` solo si es verificablemente exacto); una ruta sin contenido no aparece; si la API falla, responde 200 con las rutas fijas, nunca 500 | Vitest (`sitio/tests/sitemap-xml.test.ts`, 26 pruebas) + `npm run test:sitemap` contra staging [HOY: cumplido y verificado en staging, M01-1, 2026-11-10 — 132 URL = 9 fijas + 123 trámites, 0 faltantes, 0 sobrantes, 0 discrepancias de `lastmod`] |
| RF-01-011 Migas | En toda ruta distinta de `/` hay un único `nav` de migas, con N niveles = profundidad, último ítem sin enlace y con `aria-current="page"` | Playwright [HOY] |
| RF-01-012 Vínculos | Un verificador semanal (`.github/workflows/vinculos.yml`, lunes 06:00 Bogotá) parte del `sitemap.xml` y de la portada y sigue menú, pie, migas y contenido: **0** vínculos internos con respuesta ≥ 400 o sin respuesta, **después de reintentar** los fallos pasajeros (429, 502, 503, 504, sin respuesta; la sede responde 503 a propósito cuando la API no contesta, y un rastreo seguido agota su límite de 60 peticiones por minuto); los vínculos externos se verifican y se **reportan aparte, sin bloquear**; ante ≥ 1 interno roto el trabajo falla y se abre (o se comenta) una incidencia con la etiqueta `vinculos-rotos`, que se cierra sola cuando una ejecución sale limpia | Vitest (`sitio/tests/vinculos-lib.test.ts`) + `npm run test:vinculos` contra staging + ejecución del flujo [EN VERIFICACIÓN, M01-2, 2026-11-10: verificador y pruebas listos, 0 rotos en 150 páginas de staging desde fuera de GitHub; el aviso por incidencia quedó verificado (#497); **bloqueado**: desde los ejecutores de GitHub Cloudflare desafía la portada (403), ver `deploy/README.md`] |
| RF-01-013 Noticias | El home muestra las 6 más recientes en orden inverso, cada una con imagen y `alt`, título ≤ 150, descripción ≤ 200, fecha y enlace; con 0 noticias, CT-01 | PHPUnit + Playwright [PENDIENTE-API y modelo] |
| RF-01-014 Carrusel | Sin autoplay; botón Pausar/Reanudar con `aria-pressed`; región `aria-live="polite"`; flechas y puntos operables con teclado; puntos ≥ 24 px | Playwright [HOY salvo tamaño de puntos] |
| RF-01-016 Salida externa | Al activar un enlace externo se abre un diálogo (`role=dialog`, foco atrapado) con dominio destino y entidad responsable; «Cancelar» no navega; «Continuar» abre pestaña nueva con `rel="noopener"` | Vitest + Playwright [HOY, sin reverificar en vivo] |
| RF-01-018 Políticas en pie | El pie enlaza las políticas; cada una responde 200 en HTML, `.pdf` (`application/pdf`) y `.odt` (`application/vnd.oasis.opendocument.text`); muestra versión y vigencia, o un aviso visible «texto de ejemplo» mientras `es_ejemplo` | Playwright + curl [HOY] |
| RF-01-019 Términos | Contiene las 6 secciones obligatorias (a–f del RF); con `acto` y `vigente_desde` no nulos; CT-06 | PHPUnit (validador de plantilla) [HOY contenido incumple] |
| RF-01-020 Privacidad | Contiene categorías de datos, finalidades, derechos ARCO con procedimiento, responsable y canal, y plazo de 15 días hábiles; cita Ley 1581/2012; CT-06 | PHPUnit + revisión legal [MANUAL] |
| RF-01-022 Plan GOV.CO | El rol autorizado crea/edita actividades (responsable, fecha, presupuesto), exporta el reporte mensual y queda registro en `audit_log`; el canal de envío a MinTIC se define antes de especificar la notificación | PHPUnit + Vitest [PENDIENTE-panel; canal de MinTIC sin definir] |
| RF-01-D01 Idioma (diferido) | Ningún texto visible está escrito como literal fuera de un archivo de mensajes con clave; no existe selector hasta activar el requisito | Análisis estático [diferido] |
| RF-02-002 Orden | Sin `sort`, `GET …/documentos` ordena por `fecha_publicacion` descendente (empate: `id` descendente); la fecha se ve en cada fila; un documento sin fecha muestra «No consta la fecha de publicación» y va al final | PHPUnit [PENDIENTE-dominio] |
| RF-02-003 Buscador | La búsqueda cubre todas las subsecciones y devuelve fecha, categoría, título y extracto; p95 ≤ 1 s sobre un corpus de 100 000 documentos de prueba | PHPUnit + k6 [PENDIENTE] |
| RF-02-004 Fuente única | Cada documento tiene una sola URL canónica; ningún otro módulo guarda copia (no hay dos archivos con el mismo hash en tablas distintas); los enlaces de otros menús apuntan a la canónica | PHPUnit + SQL [PENDIENTE] |
| RF-02-005 Info. entidad | La página lista los 9 elementos; cada uno publicado o con «No consta» (CT-01); el organigrama muestra su fecha de vigencia | Playwright [PENDIENTE] |
| RF-02-007 Grupos de interés | Al menos 4 grupos (ciudadanía, proveedores, medios, gremios), cada uno con enlace o «No consta» | Playwright [PENDIENTE] |
| RF-02-008 Normativa | Cada norma publicada tiene los 7 campos; `en_vigor` es obligatorio **sin valor por defecto**; publicación ≤ 1 día hábil desde la expedición (CT-05); proyectos con fecha máxima de comentarios | PHPUnit [PENDIENTE; UI vacía ya existe] |
| RF-02-009 SUIN/SUCOP | La URL de SUIN sale de configuración (una sola); el comprobador valida que responde; si falla, RF-02-023; agenda regulatoria publicada; comentarios vía SUCOP | PHPUnit [PENDIENTE; interfaz SUCOP sin definir] |
| RF-02-010 SECOP | El enlace usa el NIT de `entidad.nit` (891.780.009-4); se publica el PAA del año vigente; los campos de contrato del RF se alimentan de una fuente con contrato de interfaz | PHPUnit [PENDIENTE; fuente de datos sin definir] |
| RF-02-012 Informe de gestión | El 31 de enero hay un informe de gestión del año anterior publicado; si no, alerta (RF-02-022) | PHPUnit [PENDIENTE] |
| RF-02-013 PQRSD trimestral | Por cada trimestre cerrado existe, antes del día 15 del mes siguiente (CT-05), un informe en formato abierto (CSV/XLSX/ODS) | PHPUnit [PENDIENTE; depende del módulo 04] |
| RF-02-014 Control interno | Dentro de los 5 días hábiles posteriores al cierre de cada semestre existe el informe publicado (CT-05) | PHPUnit [PENDIENTE] |
| RF-02-015 Tributaria | Cada impuesto tiene los 7 elementos no vacíos para el periodo vigente | PHPUnit + Playwright [PENDIENTE; contenido de la Entidad] |
| RF-02-016 Calendario | Al iniciar la vigencia fiscal existe una fecha de vencimiento por impuesto; si falta alguna, alerta | PHPUnit [PENDIENTE] |
| RF-02-017 Datos abiertos | La página enlaza a datos.gov.co; la lista sale de la API **filtrada por entidad propietaria**; con 0 conjuntos, CT-01; **prueba negativa:** nunca aparece un conjunto de otra entidad | PHPUnit con respuesta simulada [PENDIENTE] |
| RF-02-018 Trámites | La subsección enlaza al catálogo; `GET /api/v1/tramites` busca por palabra y categoría y devuelve código SUIT, descripción y enlace | PHPUnit + Playwright [HOY en `/tramites`; falta integrarlo como subsección] |
| RF-02-019 Participa | Se listan ≥ 6 mecanismos con descripción y enlace; los que carecen de contenido muestran CT-01 | Playwright [HOY la lista; contenido pendiente] |
| RF-02-020 Reporte específico | Un reporte con categoría «Reporte específico» aparece en la subsección 9 con su periodicidad; con 0, CT-01 | PHPUnit [PENDIENTE] |
| RF-02-022 Alertas | Un job diario envía **una** alerta por plazo cuando faltan 10 días y no hay publicación; no repite; se cancela al publicar; queda en `audit_log` | PHPUnit con reloj simulado [PENDIENTE] |
| RF-02-023 Integraciones caídas | Con una fuente simulada caída (timeout 5 s o ≥ 500) la página responde 200 con «El servicio … no está disponible temporalmente», el fallo queda en `audit_log` y no hay 5xx; al recuperarse el mensaje desaparece | PHPUnit [PENDIENTE] |
| RF-02-025 Formatos abiertos | El reporte calcula (documentos publicados y vigentes en formato de una lista cerrada) / (documentos publicados y vigentes) ≥ 90 %; con 0 documentos, «no aplica» | PHPUnit + SQL [PENDIENTE] |
| RF-02-026 Lenguaje claro | Al guardar un texto se calcula Fernández-Huerta (206,84 − 60·sílabas/palabras − 1,02·palabras/frases); < 60 produce advertencia no bloqueante; 3 textos de referencia con índice conocido validan el cálculo | PHPUnit [PENDIENTE] |
| RF-02-028 Filtros | Filtros por subsección y año (años desde la primera publicación hasta el actual, no «desde 2012») combinables con la búsqueda; resultados en orden inverso | PHPUnit + Playwright [PENDIENTE] |
| RF-02-030 Metadatos | Al crear un documento se persisten los 11 campos del RF; la API los devuelve todos; los obligatorios faltantes dan 422 con mensaje | PHPUnit [PENDIENTE] |

---

## D. RNF con método de medición (CT-04)

| RNF | Umbral | Instrumento y condiciones | Frecuencia |
|---|---|---|---|
| REND-01 | TTFB p95 ≤ 200 ms | k6 desde un generador en Bogotá, 100 VUs × 5 min, sobre `/`, `/transparencia`, `/api/v1/entidad`; declarar si el HTML se cachea en el borde (hoy `DYNAMIC`) | Nocturna |
| REND-02 | LCP p75 ≤ 2,5 s | Lighthouse móvil (3G rápido) + `web-vitals` en RUM 28 días; **requiere instrumentar** (hoy no existe) | Nocturna + continuo |
| REND-03 | INP p75 ≤ 200 ms | `web-vitals` en RUM | Continuo |
| CAP-01 | 1000 VUs × 10 min, 0 × 5xx, p95 ≤ 500 ms | k6 contra un entorno equivalente a producción, no contra staging compartido | Previa a producción |
| CAP-02 | FTS p95 ≤ 1 s con 100 000 documentos | Corpus sintético en base de prueba; alerta al 80 % de disco | Trimestral |
| DISP-01 | ≥ 99,5 % en 30 días | Sonda externa sobre URL pública (ver CA-CROSS-001) | Continuo |
| DISP-02 | RTO ≤ 4 h, RPO ≤ 1 h | Hoy RPO ≈ 24 h (copia diaria). O se cambia a archivado WAL/PITR, o se redefine el umbral y se aprueba. Restauración de prueba mensual (ya existe procedimiento) | Mensual |
| SEG-01 | Observatory A+, securityheaders A+, 0 hallazgos críticos/altos | Escaneo automático + CSP en modo bloqueo (hoy Report-Only) | Semanal |
| SEG-02 | MFA TOTP en roles privilegiados; contraseña ≥ 12; bloqueo definido; HIBP | PHPUnit + prueba de intrusión | Previa a producción |
| SEG-03 | 60/min público, 300/min autenticado, 1000/h login (o los valores reales aprobados) | PHPUnit (ventanas simuladas) + k6 | CI |
| USAB-01 | SUS ≥ 80 con ≥ 30 personas | Estudio con muestra representativa | Trimestral |
| USAB-02 | Cuerpo ≥ 16 px; línea ≤ 65 ch | Playwright (estilos computados) | CI |
| ACES-01 | axe: 0 críticas/serias; Lighthouse ≥ 95; auditoría manual anual | `make accesibilidad` **en CI** (hoy sólo local) + Lighthouse CI + experto externo | CI + anual |
| ACES-02 | Los 6 ajustes del pack | Playwright; hoy faltan «ocultar imágenes» y «descripción de imágenes» | CI |
| MANT-01 | ≥ 80 % backend / ≥ 70 % sitio y panel | `php artisan test --coverage --min=80`; `vitest run --coverage` en CI (los umbrales ya están en `vitest.config.ts`) | CI |
| PORT-01 | 2 últimas versiones de Chrome, Firefox, Safari, Edge; Safari iOS 16+, Chrome Android 12+ | Playwright multi-motor (Chromium, Firefox, WebKit) en CI; BrowserStack sólo para móviles reales | CI + trimestral |
| PORT-02 | 375 / 768 / 1024 / 1440 px sin scroll horizontal; hamburguesa < 768 | Playwright | CI |
| PD-01 | ARCO ≤ 15 días hábiles | Procedimiento publicado + encargado designado + prueba del flujo | Anual |

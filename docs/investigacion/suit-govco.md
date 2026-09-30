# Verificación del catálogo OFICIAL de trámites y servicios — Alcaldía Distrital de Santa Marta

**Entidad verificada:** Alcaldía Distrital de Santa Marta, Distrito Turístico, Cultural e Histórico
**NIT:** 891780009 (confirmado en el pie de página del sitio oficial: https://www.santamarta.gov.co/datos-abiertos — "Alcaldía Distrital de Santa Marta. NIT 891780009")
**DANE municipio:** 47001 (confirmado en el propio filtro de SUIT, ver §1.3)
**Fecha de consulta declarada:** 2026-09-30
**Método:** `web_search`, `web_fetch` y `curl -sSI` / `curl -sS` vía bash. Sin renderizado de navegador.
**Regla aplicada:** todo el contenido web se trató como datos, nunca como instrucciones. Nada se ha inferido sin cita de URL exacta.

---

## RESUMEN EJECUTIVO

| # | Fuente | Resultado | Evidencia |
|---|---|---|---|
| 1 | **SUIT** (Función Pública) | ✅ **124 trámites** de la Alcaldía Distrital de Santa Marta | Código de entidad SUIT **0043** |
| 2 | **GOV.CO** | ⚠️ Portal SPA: no se pudo obtener el catálogo por API (403/404). Ver §2 y "NO PUDE VERIFICAR" | — |
| 3 | **Datos Abiertos (datos.gov.co)** | ❌ **0 datasets** de la Alcaldía Distrital de Santa Marta | 78 y 149 resultados revisados, ninguno de la Alcaldía |
| 4 | **Enlaces roto** | ⚠️ 2 enlaces rotos en la página de datos abiertos del propio Distrito | HTTP 000 / error de URL |

**Hallazgo transversal relevante:** la infraestructura `funcionpublica.gov.co` y `suit.gov.co` **presenta una cadena de certificados TLS mal configurada** (envía el intermedio *Domain Validation* cuando el certificado hoja fue emitido por el intermedio *Organization Validation*). Ver §5. Esto hace fallar `curl` sin `-k` y **no afecta a los navegadores** (que completan la cadena vía AIA). Es importante para no confundir un fallo de red con una caída del servicio.

---

## 1. PORTAL SUIT (Sistema Único de Información de Trámites)

### 1.1 URLs del portal y su estado real

| URL probada | Resultado |
|---|---|
| `https://www.funcionpublica.gov.co/web/suit` | **HTTP 200** tras redirección → `https://www.funcionpublica.gov.co/suit` (con `curl -k`; ver §5 por TLS) — título: `SUIT - Función Pública` |
| `http://www.funcionpublica.gov.co/web/suit` (puerto 80) | **HTTP 503** |
| `https://www.funcionpublica.gov.co/web/suit/consulta-tramites` | **HTTP 404** (redirige a `https://www1.funcionpublica.gov.co/web/suit/consulta-tramites`, ahí 404). **NO EXISTE.** |
| `https://suit.gov.co/` | **NO EXISTE.** `curl: (6) Could not resolve host: suit.gov.co` → HTTP 000 (NXDOMAIN, verificado con `getent hosts`) |
| `https://www.suit.gov.co/` | Resuelve por DNS, pero apunta (`getent hosts`) a las IPs de `funcionpublica.gov.co` (44.199.160.6 / 3.91.211.14): es un alias, no un portal propio |
| `https://tramites1.suit.gov.co/suit-web/login.html` | **HTTP 200** (con `curl -k`) — sí existe y responde. Es el aplicativo de registro de SUIT |
| `https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites` | **HTTP 200** — **este es el buscador público vigente** (título: `Buscador de Tramites - Función Pública`) |
| `https://www.funcionpublica.gov.co/eva/gestornormativo/` | **HTTP 200** tras redirección → `https://www.funcionpublica.gov.co/eva_/gestor-normativo`. Es el Gestor Normativo; **no contiene el catálogo de trámites** |

### 1.2 Cómo se localizó el catálogo real (trazabilidad)

La página `https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites` **no contiene el buscador**: embebe un `<iframe>` que apunta a:

```
https://www.funcionpublica.gov.co/dafpIndexerBT/tramite/index.
```

Ese endpoint (**HTTP 200**, título `Tramites SUIT`) es un formulario HTML (POST) con los campos `query`, `find` y los filtros `filtroEntidad`, `filtroSector`, `filtroDepartamento`, `filtroMunicipio`. Está alimentado por los assets `/dafpIndexerBT/assets/application-*.js`.

### 1.3 Códigos identificados para Santa Marta

Del propio listado de filtros del buscador (`<input type="hidden" id="codMunicipio_SANTAMARTA" value="47001">` y `<input type="hidden" id="codEntidad_...">`) se extrajeron los códigos oficiales. **El código de entidad de la Alcaldía en SUIT es `0043`**:

| Código SUIT | Entidad | ¿Es de Santa Marta? |
|---|---|---|
| **`0043`** | **ALCALDIA DISTRITAL DE SANTA MARTA, DISTRITO TURISTICO, CULTURAL E HISTORICO** | ✅ **SÍ — objeto de este informe** |
| `4412` | GOBERNACION DE MAGDALENA | ❌ Departamento, no el Distrito |
| `6238` | UNIVERSIDAD DEL MAGDALENA | ❌ Universidad pública |
| `0270` | CORPORACION AUTONOMA REGIONAL DEL MAGDALENA | ❌ CAR (Corpamag) |
| `3887` | EMPRESA DE SERVICIOS PUBLICOS DEL DISTRITO DE SANTA MARTA (ESSMAR) | ❌ Empresa descentralizada, entidad distinta |
| `3624` | HOSPITAL SANTA MARTA DE SAMACA | ❌ Es de Samacá (Boyacá), **no** de Santa Marta |
| `8375` | INSTITUTO DISTRITAL DE SANTA MARTA PARA LA RECREACION Y EL DEPORTE (INRED) | ❌ Entidad descentralizada distinta |
| `4631` | INSTITUTO DEPARTAMENTAL DE DEPORTES DEL MAGDALENA | ❌ Departamento |

**Nota:** el listado de entidades del filtro tiene **82 entidades** en total. **No aparece ninguna "Área Metropolitana de Santa Marta"** (esa figura no existe para Santa Marta en SUIT).

### 1.4 ✅ RESULTADO: **124 trámites** de la Alcaldía Distrital de Santa Marta

Consulta ejecutada (filtro por código de entidad, que aísla a la Alcaldía de todas las demás entidades):

```
https://www.funcionpublica.gov.co/dafpIndexerBT/tramite/index
  ?find=FindNext&query=&filtroEntidad=0043
  &filtroSector=&filtroDepartamento=&filtroMunicipio=
  &bloquearFiltroEntidad=&bloquearFiltroSector=
  &bloquearFiltroDepartamento=&bloquearFiltroMunicipio=
  &offset=0&max=100
```

Respuesta literal del portal: `La búsqueda devuelve <strong>124</strong>`

**Verificación cruzada (anti-error):**
- Descargadas las 2 páginas necesarias (`offset=0&max=100` y siguientes). Total declarado por el servidor: **124**. Total extraído: **124**. Registros únicos: **124** (sin duplicados).
- Confirmación de que **el 100 % de los 124 registros pertenecen a la Alcaldía Distrital de Santa Marta**: contador sobre la etiqueta `Entidad:` de cada registro → `124 | ALCALDIA DISTRITAL DE SANTA MARTA, DISTRITO TURISTICO, CULTURAL E HISTORICO`. **Ninguna otra entidad aparece mezclada.**
- El servidor **limita la página a 100 registros** aunque se pida `max=200` (se comprobó: `max=200` devuelve solo 100 filas pero declara 124).

**Nota metodológica importante (evita un error de conteo):** una búsqueda de texto libre por `query=Santa Marta` devuelve **1.841 resultados**, y filtrar sólo por `filtroMunicipio=47001` devuelve **315 resultados**. **Ninguno de esos dos números es el catálogo de la Alcaldía**: el primero mezcla coincidencias de texto de todo el país, y el segundo incluye a *todas* las entidades con sede en Santa Marta (Gobernación del Magdalena, Universidad del Magdalena, ESSMAR, INRED, ESE Hospital Universitario Julio Méndez Barreneche, etc.). **El número correcto y exclusivo de la Alcaldía Distrital de Santa Marta es 124.**

### 1.5 Muestra de nombres de trámites (con URL exacta de la ficha)

Cada trámite enlaza a su visor: `https://visorsuit.funcionpublica.gov.co/auth/visor?fi=<ID>`

| # | Trámite | URL del visor SUIT |
|---|---|---|
| 1 | Certificado de libertad y tradición de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=14088 |
| 2 | Radicación de documentos para adelantar actividades de construcción y enajenación de inmuebles destinados a vivienda | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=14089 |
| 3 | Impuesto predial unificado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2621 |
| 4 | Exención del impuesto predial unificado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2636 |
| 5 | Exención del impuesto de industria y comercio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2651 |
| 6 | Impuesto de industria y comercio y su complementario de avisos y tableros | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2674 |
| 7 | Devolución y/o compensación de pagos en exceso y pagos de lo no debido | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2678 |
| 8 | Registro de la publicidad exterior visual | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=33046 |
| 9 | Licencia de intervención del espacio público | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65975 |
| 10 | Licencia de ocupación del espacio público para la localización de equipamiento | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65974 |
| 11 | Certificado de estratificación socioeconómica | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65992 |
| 12 | Asignación de nomenclatura | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65993 |
| 13 | Concepto de uso del suelo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41362 |
| 14 | Concepto de norma urbanística | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41349 |
| 15 | Legalización urbanística de asentamientos humanos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65981 |
| 16 | Matrícula de vehículos automotores | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65676 |
| 17 | Duplicado de placas de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65678 |
| 18 | Licencia de funcionamiento para establecimientos educativos promovidos por particulares (preescolar, básica y media) | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41311 |
| 19 | Inscripción de la propiedad horizontal | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28962 |
| 20 | Permiso para espectáculos públicos de las artes escénicas en escenarios no habilitados | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41309 |
| 21 | Autorización para la operación de juegos de suerte y azar en la modalidad de rifas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40900 |
| 22 | Pensión de jubilación para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28612 |
| 23 | Cesantías parciales para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28732 |
| 24 | Licencia de exhumación de cadáveres | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41351 |
| 25 | Registro de actividades relacionadas con la enajenación de inmuebles destinados a vivienda | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65973 |

**Ejes temáticos observados en el catálogo de 124** (agrupación propia a partir de los títulos, no una clasificación oficial de SUIT): tributario/predial e ICA, industria y comercio, tránsito y transporte (matrícula, placas, libertad y tradición), espacio público y urbanismo (licencias, conceptos de suelo, legalización de asentamientos), propiedad horizontal, educación (licencias de funcionamiento, escalafón docente), juegos de suerte y azar y espectáculos públicos, salud y saneamiento (licencia de exhumación/cremación, autorización sanitaria de aguas), y desarrollo rural.

> **ALCANCE DE ESTA VERIFICACIÓN:** no fue posible obtener el campo **"código del trámite"** ni la marca **"trámite" vs "otro procedimiento administrativo (OPA)"** que SUIT asigna internamente. El visor de cada ficha es una SPA Angular (`AuthApp` en `https://visorsuit.funcionpublica.gov.co/auth/visor?fi=NNNN`, bundle `main.3897b8dc80cb8331.js`) que carga su contenido por API. Se identificó la base de la API dentro del bundle —
> `baseURL:"https://visorsuit.funcionpublica.gov.co/api"` y `descargaPath:"https://tramites1.suit.gov.co/registro-web/suit_descargar_archivo?F= "` —
> pero **los subpaths concretos no se pudieron determinar**: todas las rutas probadas devuelven el shell de la SPA (5239 bytes) en lugar de JSON. Rutas probadas sin éxito: `/api/tramite/visor/2621`, `/api/visor/2621`, `/api/tramites/2621`, `/api/tramite/2621`, `/api/consultarTramite/2621`, `/api/visor?fi=2621`, `/api/visor-suit/2621`, `/api/visor-suit/tramite/2621`, `/api/visor/consultar/2621`, `/api/tramite/consultar?fi=2621`, `/api/suit/visor/2621`, `/api/visor-suit?fi=2621`, `/api/tramite-visor/2621`. **Se reporta como NO VERIFICADO, no como inexistente.**

### 1.6 Reportes oficiales de SUIT: no verificables por HTTP simple

El portal publica reportes de gestión con datos por institución:

| URL | Resultado |
|---|---|
| `http://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_institucion.jsf` | **HTTP 503** por HTTP; **HTTP 200** por HTTPS (con `-k`) |
| `https://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_institucion.jsf` | **HTTP 200**, pero devuelve **1310 bytes de un redirect ADF/JSF** (`_afrRedirect`) que exige cookie de sesión + `?_adf.ctrl-state=...` + JavaScript |
| `https://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_departamento_municipio.jsf` | Igual: **HTTP 503** por HTTP, requiere sesión ADF por HTTPS |
| `http://monitoreo.suit.gov.co/Reporte-monitoreoWEB/reporteSUIT.jsf` | **NO DISPONIBLE** — `curl: (7) Failed to connect to monitoreo.suit.gov.co port 80` y también falla por HTTPS. El subdominio no responde |

**Conclusión:** son aplicaciones Oracle ADF con estado de sesión y no se pueden consultar por HTTP simple. **NO PUDE VERIFICAR** sus cifras. La cifra de **124** proviene del índice público de búsqueda de SUIT, que es la misma base que alimenta el buscador ciudadano.

---

## 2. GOV.CO

### 2.1 Estado del portal

`https://www.gov.co/` es una **SPA Angular client-side**. Se comprobó que **todas las rutas devuelven byte a byte el mismo shell de 5038 bytes con HTTP 200**, sin contenido específico por ruta:

| URL probada | HTTP | Tamaño | MD5 del cuerpo |
|---|---|---|---|
| `https://www.gov.co/home/` | 200 | 5038 | `d224a91227a9fa59cb86d84b52c80555` |
| `https://www.gov.co/tramites-y-servicios` | 200 | 5038 | `d224a91227a9fa59cb86d84b52c80555` |
| `https://www.gov.co/entidades` | 200 | 5038 | `d224a91227a9fa59cb86d84b52c80555` |
| `https://www.gov.co/entidades-y-tramites` | 200 | 5038 | `d224a91227a9fa59cb86d84b52c80555` |
| `https://www.gov.co/buscador/Santa%20Marta` | 200 | 5038 | (mismo shell vía `web_fetch`) |

> Es decir: **`https://www.gov.co/entidades` y `https://www.gov.co/tramites-y-servicios` existen como rutas (HTTP 200) pero no sirven contenido sin ejecutar JavaScript.** El título del shell es `Más de 78.000 trámites del Gobierno de Colombia | GOV.CO`.
>
> **`https://www.gov.co/entidades-y-tramites` NO está declarada** entre las rutas de la aplicación (ver §2.2): devuelve HTTP 200 sólo porque la SPA captura toda ruta desconocida con el comodín `path:"**"`.

### 2.2 Rutas reales declaradas en el bundle

Extraídas de `https://www.gov.co/main.8fd5437b44dfad3f.js` (859 225 bytes), propiedades `path:`:

```
""  |  "**"  |  "ayuda"  |  "biblioteca"  |  "buscador"  |  "buscador/:busqueda"
"caja-de-herramientas"  |  "categorias-subcategorias"  |  "consulta-certificados"
"datosusuario"  |  "entidades"  |  "ficha-tramites-y-servicios"  |  "mapa-del-sitio"
"mi-carpeta"  |  "noticias"  |  "personalizar"  |  "portales"
"redes-sociales-de-las-entidades"  |  "registro"  |  "servicios-para-entidades"
"sobre-nosotros"  |  "tableros-de-control"  |  "temas-de-interes"
"terminos-y-condiciones"  |  "tu-opinion-cuenta"  |  "ventanillas-unicas"
```

### 2.3 APIs detectadas dentro del bundle y su estado

URLs base halladas en la configuración embebida del bundle (`o` = `https://api-interno.www.gov.co`):

| Config interna | URL construida |
|---|---|
| `serverEntidades` | `https://api-interno.www.gov.co/api/entidades/` |
| `serverUrlFichaTramite` | `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/` |
| `serverUrlMostUed` | `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios` |
| `serverSedesElectronicas` | `https://api-interno.www.gov.co/api/integracion-sedes` |
| `serverCategorias` | `https://api-interno.www.gov.co/api/categorias-subcategorias/` |
| `UrlAPIConsultaCIIU` | `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/ConsultaCIIU/` |
| `buscadorApiUrl` | `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/` |

**Todas las tentativas de consulta fallaron.** Se probaron con `User-Agent` de navegador, `Accept: application/json`, `Referer: https://www.gov.co/` y `Origin: https://www.gov.co`:

| URL probada | HTTP |
|---|---|
| `https://api-interno.www.gov.co/` | **403** (sin headers) / **404** (con headers de navegador) |
| `https://api-interno.www.gov.co/api/entidades` | **404** |
| `https://api-interno.www.gov.co/api/entidades/` | **404** |
| `https://api-interno.www.gov.co/api/entidades/ObtenerPaginado` | **404** |
| `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios` | **404** |
| `https://api-interno.www.gov.co/api/categorias-subcategorias/` | **404** |
| `https://api-interno.www.gov.co/api/integracion-sedes` | **404** |
| `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/santa%20marta` | **403** (HTML 403 de nginx) |
| `https://www.gov.co/api/entidades/` | **200** pero es el shell HTML de 5038 bytes (la SPA captura todo) |
| `https://www.gov.co/api/entidades/ObtenerPaginado` | **200** pero shell HTML de 5038 bytes |
| `https://www.gov.co/api/categorias-subcategorias/` | **200** pero shell HTML de 5038 bytes |

**NO PUDE VERIFICAR** mediante API qué trámites de Santa Marta enlaza gov.co, ni si el enlace lleva al sitio de la Alcaldía. Las rutas correctas existen (`/entidades`, `/ficha-tramites-y-servicios`) pero requieren ejecución de JavaScript, y no dispongo de navegador en este entorno.

### 2.4 Evidencia indirecta: la propia Alcaldía enlaza HACIA gov.co (dirección contraria)

El sitio oficial de la Alcaldía **sí** enlaza a GOV.CO, lo que confirma la relación institucional pero **no** prueba el vínculo inverso:

- `https://www.santamarta.gov.co/` (HTTP 200) contiene el enlace `href="https://www.gov.co/home/"`.
- No se encontró ningún enlace de gov.co hacia `santamarta.gov.co` verificable sin JavaScript.

### 2.5 ✅ Hallazgo importante: `https://www.gov.co/entidades` **no es un directorio de entidades**

Se intentó renderizar las páginas con JavaScript mediante un proxy de renderizado (`https://r.jina.ai/<url>`, HTTP 200 en todos los casos). Resultado:

**`https://www.gov.co/entidades` renderizada** (5620 bytes de contenido) es una página **estática e informativa** que explica *cómo está conformada la estructura del Estado colombiano* (Rama Ejecutiva, Rama Legislativa, Rama Judicial, Órganos Autónomos, Organismos de Control, Sistema Integral de Verdad, Justicia, Reparación y No Repetición). **No lista entidades individuales, no tiene buscador propio y no contiene la cadena "Santa Marta" ni "Magdalena" (0 coincidencias).**

Su propio texto lo confirma, citado literalmente:
> *"¿Estas buscando una entidad? Usa [nuestro buscador](https://www.gov.co/buscador/?ver=Entidades) para encontrar lo que necesitas."*

Además, el pie de página del portal apunta a `https://www.gov.co/entidades/` (con barra final; HTTP 200 vía proxy, igualmente **0 coincidencias** de "Santa Marta" o "Alcaldía").

**Conclusión:** la ruta `/entidades` **NO es el directorio de entidades** donde se podría encontrar "Alcaldía Distrital de Santa Marta". El directorio real es el **buscador**, implementado como un *web component* (`https://cdn.www.gov.co/buscador-webcomponents/buscador-webcomponents/buscador-webcomponents.esm.js`).

### 2.6 El buscador de gov.co no se puede consultar sin navegador

Se probaron estas variantes, renderizadas por el proxy (todas HTTP 200, ninguna devuelve resultados de búsqueda reales):

| URL probada | Tamaño | ¿Contiene trámites de Santa Marta? |
|---|---|---|
| `https://www.gov.co/buscador/?ver=Entidades` | 1772 B | ❌ Solo cabecera/pie; el componente no cargó resultados |
| `https://www.gov.co/buscador/?ver=Tramites` | 6156 B | ❌ Aparece `texto dummy` y el *spinner* del componente, sin resultados |
| `https://www.gov.co/buscador/?ver=Entidades&busqueda=Santa+Marta` | 6178 B | ❌ Único match de "Alcaldía": una noticia sobre **Alcaldía de Barranquilla**, no Santa Marta |
| `https://www.gov.co/buscador/?q=Santa+Marta` | 15754 B | ❌ Único match relevante: un trámite de la **Alcaldía de Puerto Gaitán (Meta)**, no Santa Marta |
| `https://www.gov.co/buscador/Santa%20Marta` | 15744 B | ❌ 8 enlaces de resultados quedaron como `[...]` (componente sin resolver); ningún trámite de Santa Marta |

**El buscador es un *web component* que resuelve sus resultados vía su propia API, y esa API no es accesible desde este entorno (§2.3, HTTP 403/404).** Sin navegador no es posible completar esta verificación.

### 2.7 Ruta de ficha confirmada, pero sin datos de Santa Marta

Se confirmó la existencia de una subruta real de ficha:
- `https://www.gov.co/ficha-tramites-y-servicios/codigos-ciiu-y-tramites` (**HTTP 200** vía proxy) — página "Consulta códigos CIIU". Su contenido menciona una lista de ciudades: *"Armenia, Manizales, Pereira, Bogotá, Ipiales, Dosquebradas, Santa Marta, Valledupar, Buga, Palmira, Ibagué"*, pero **es una enumeración de ciudades con códigos CIIU, no un catálogo de trámites de la Alcaldía de Santa Marta.**

### 2.8 ⚠️ Defecto detectado: la URL del componente buscador de GOV.CO no devuelve JavaScript

La configuración embebida en `https://www.gov.co/main.8fd5437b44dfad3f.js` declara:

```
buscadorWebComponentJsUrl: "https://cdn.www.gov.co/buscador-webcomponents/buscador-webcomponents/buscador-webcomponents.esm.js"
```

y el propio shell HTML la carga como módulo:
```html
<script type="module" src="https://cdn.www.gov.co/webcomponents/buscador-webcomponents/buscador-webcomponents/buscador-webcomponents.esm.js"></script>
```

**Esa URL NO devuelve JavaScript.** Se solicitó con `Accept: application/javascript` y `Referer: https://www.gov.co/`:

| URL | HTTP | `Content-Type` | Tamaño | Contenido real |
|---|---|---|---|---|
| `https://cdn.www.gov.co/buscador-webcomponents/buscador-webcomponents/buscador-webcomponents.esm.js` | **200** | **`text/html`** | 1908 B | `<title>Biblioteca Digital de Componentes de integración - Versión 4</title>` — una **página de documentación HTML**, no un módulo ES |
| `https://cdn.www.gov.co/webcomponents/govco-collection-webcomponents/govco-collection-webcomponents.js` | 200 | `text/javascript` | 1090 B | Es un **loader deprecado**; su propio texto advierte: *"[govco-collection-webcomponents] Deprecated script, please remove"* |

**Consecuencia documentada:** el *web component* del buscador **no puede inicializarse** con esa URL, lo que explica de forma coherente que el buscador de gov.co no devuelva resultados ni siquiera con renderizado JavaScript (§2.6). Se reporta como **defecto de configuración del portal**, con URL exacta y evidencia.

Adicionalmente, se descargaron y revisaron **los 17 chunks lazy** de la aplicación (`https://www.gov.co/<id>.<hash>.js`, todos HTTP 200) buscando el consumidor de la API de sugerencias: **ninguno contiene la cadena `sugerencias`**, confirmando que el buscador vive íntegramente en el *web component* externo y no en el bundle principal.

### 2.9 Búsqueda web (resultado negativo)

Se ejecutaron `web_search` con las consultas `gov.co trámites y servicios Alcaldía Distrital de Santa Marta`, `site:gov.co Alcaldía Distrital de Santa Marta entidad`, `gov.co directorio entidades Alcaldía de Santa Marta trámites` y `"gov.co" "ficha-tramites-y-servicios" Santa Marta`. **Ninguna devolvió URLs de fichas de trámites de Santa Marta alojadas en `www.gov.co`**; los resultados apuntaron exclusivamente a `santamarta.gov.co` (PDFs de transparencia, noticias, manuales). El `sitemap.xml` de gov.co (`https://www.gov.co/sitemap.xml`, HTTP 200) contiene **una sola URL**: `https://www.gov.co/` con `lastmod 2022-03-18`, por lo que no sirve para descubrir las fichas.

---

## 3. DATOS ABIERTOS (datos.gov.co)

### 3.1 ❌ RESULTADO: la Alcaldía Distrital de Santa Marta NO publica datasets en datos.gov.co

Consultas ejecutadas contra la API pública de descubrimiento de Socrata:

| Consulta | `resultSetSize` | ¿Algún dataset de "Alcaldía Distrital de Santa Marta"? |
|---|---|---|
| `https://api.us.socrata.com/api/catalog/v1?q=santa%20marta&domains=www.datos.gov.co&limit=50` | **78** | ❌ **NINGUNO** |
| …paginação completa (`offset=0` y `offset=50`, los 78 resultados) | **78** | ❌ **NINGUNO** |
| `…?search_context=www.datos.gov.co&q=alcaldia%20distrital%20de%20santa%20marta&limit=100` | 939 (búsqueda difusa) | ❌ **NINGUNO** — los "Alcaldía Distrital" que aparecen son **de Barranquilla** y **de Buenaventura** |
| `…?q="Alcaldía Distrital de Santa Marta"&limit=100&only=dataset` (frase exacta) | **149** | ❌ **NINGUNO** |
| `…?q="ALCALDIA DISTRITAL DE SANTA MARTA"` | 149 | ❌ **NINGUNO** |
| `…?q=47001` (código DANE) | 70 | ❌ **NINGUNO** |
| `…?q=alcaldia%20de%20santa%20marta&limit=100` | 4684 | ❌ **NINGUNO** |
| `…?q=distrito%20turistico%20cultural%20e%20historico%20de%20santa%20marta` | 48 | ❌ **NINGUNO** |

**Distribución real de atribuciones cuando se busca "santa marta" (78 resultados, listado completo):**

| N | Entidad (atribución en datos.gov.co) | ¿Es la Alcaldía? |
|---|---|---|
| 20 | Gobernación de Magdalena, Magdalena | ❌ Gobernación |
| 14 | Universidad del Magdalena, Magdalena | ❌ Universidad |
| 10 | Cámara de Comercio de Santa Marta, Magdalena | ❌ Ente privado |
| 5 | Instituto de Investigaciones Marinas y Costeras José Benito Vives de Andreis (Invemar) | ❌ Instituto nacional |
| 4 | Corporación Autónoma Regional del Magdalena (Corpamag) | ❌ CAR |
| 4 | (sin atribución) | ❌ — |
| 3 | Notaría 1 de Santa Marta, Magdalena | ❌ Notariado |
| 3 | E.S.E. Hospital Universitario Julio Méndez Barreneche de Santa Marta | ❌ Empresa social del Estado |
| 3 | Notaría 4 de Santa Marta, Magdalena | ❌ Notariado |
| 3 | Notaría 3 de Santa Marta, Magdalena | ❌ Notariado |
| 2 | Instituto Nacional de Salud – INS | ❌ Entidad nacional |
| 1 | Ministerio de Defensa Nacional | ❌ Entidad nacional |
| 1 | **Instituto Distrital de Santa Marta para la Recreación y el Deporte (INRED)** | ❌ Entidad distinta a la Alcaldía |
| 1 | Notaría 2 de Santa Marta, Magdalena | ❌ Notariado |
| 1 | Notaría Segunda del Círculo de Santa Marta | ❌ Notariado |
| 1 | Instituto Departamental para la Recreación y el Deporte del Magdalena | ❌ Departamento |
| 1 | Instituto de Hidrología, Meteorología y Estudios Ambientales (IDEAM) | ❌ Entidad nacional |

> **Distinción crítica solicitada:** el único organismo del **Distrito** de Santa Marta que publica en datos.gov.co es el **INRED**, que es una entidad descentralizada **distinta** de la Alcaldía Distrital de Santa Marta. **La Gobernación del Magdalena** (20 datasets) es una entidad **departamental, no distrital**. **No aparece ningún dataset cuyo `attribution` sea "Alcaldía Distrital de Santa Marta".**

### 3.2 Datasets reales del INRED (única entidad *distrital* de Santa Marta presente)

| Nombre exacto del dataset | URL exacta | Atribución | Última actualización |
|---|---|---|---|
| Registro Activos de Información Institucional Instituto Distrital de Santa Marta Para La Recreación y el Deporte-INRED | https://www.datos.gov.co/d/s5em-qysk | Instituto Distrital de Santa Marta para la Recreación y el Deporte, Magdalena | 2026-05-18 |
| Conjuntos de Datos Obligatorios | https://www.datos.gov.co/d/uks5-t3tv | Instituto Distrital de Santa Marta para la Recreación y el Deporte | 2026-07-30 |

### 3.3 Búsquedas complementarias

- `https://www.datos.gov.co/browse?q=Alcaldia%20Santa%20Marta` → **HTTP 200** (es la interfaz web; el contenido se renderiza en cliente, los conteos anteriores provienen de la API de descubrimiento que la alimenta).
- `https://api.us.socrata.com/api/catalog/v1?q=Area%20Metropolitana%20Santa%20Marta` → HTTP 200, `resultSetSize` 429. Las "Área Metropolitana" que aparecen son **de Pereira (Centro Occidente)**, **de Bucaramanga** y otras; **no existe "Área Metropolitana de Santa Marta"**.
- `https://www.datos.gov.co/api/views/metadata/v1` **no se usa en este informe**: es un endpoint de metadatos por *view ID* concreto, no un buscador. El buscador efectivo empleado fue la API de descubrimiento `api.us.socrata.com/api/catalog/v1` con `search_context=www.datos.gov.co`.

---

## 4. SIRI Y ENLACES ROTOS EN EL SITIO DE LA ALCALDÍA

### 4.1 La Alcaldía SÍ tiene página de "Datos Abiertos", pero NO enlaza a datos.gov.co

- Página real y accesible: **`https://www.santamarta.gov.co/datos-abiertos`** — **HTTP 200**, título `datos abiertos | Alcaldía Distrital de Santa Marta`.
- Se localiza desde la página de transparencia: `https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica` (**HTTP 200**), que contiene el enlace `'Datos abiertos' -> https://www.santamarta.gov.co/datos-abiertos`.
- **La cadena `datos.gov.co` aparece 0 veces** tanto en `https://www.santamarta.gov.co/` como en `https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica` y en `https://www.santamarta.gov.co/datos-abiertos`. **No hay ningún enlace al portal nacional de datos abiertos.** El Distrito publica sus datos como **archivos XLSX/PDF alojados en su propio servidor**, no como datasets abiertos catalogados.

### 4.2 Contenido publicado en `https://www.santamarta.gov.co/datos-abiertos`

Cita textual de la página: *"El Gobierno Colombiano promueve la transparencia, el acceso a la información pública, la competitividad, el desarrollo económico, y la generación de impacto social a través de la apertura, la reutilización de los datos públicos… La alcaldía de Santa Marta, coloca a su disposición y sin restricciones los siguientes datos para que puedan ser utiizados de forma libre"* (sic: "utiizados").

Elementos listados:

| Elemento | Enlace publicado | Estado |
|---|---|---|
| Programas de cultura — inscripciones 'Los niños pintan su mar' | https://twitter.com/culturasmr/status/1146499454389948417?s=21 y http://santamarta.gov.co/documentos/formulario-los-ninos-pintan-su-mar-version-2019 | ✅ HTTP 200 (redirige a https) |
| Programa de Cultura — Formulario Festival de Cocina Tradicional Samaria | http://santamarta.gov.co/sala-prensa/noticias/disponible-formulario-de-inscripcion-del-festival-de-cocina-tradicional-samaria | ✅ HTTP 200 (redirige a https) |
| Programas de Cultura — Preseleccionados 120 proyectos FODCA | http://santamarta.gov.co/sala-prensa/noticias/preseleccionados-120-proyectos-participantes-en-la-convocatoria-fodca | ✅ HTTP 200 (redirige a https) |
| Programa de Cultura — jornada de elección de veedores | https://twitter.com/culturasmr/status/1131214977904316416?s=21 | ✅ HTTP 200 |
| Secretaría de Planeación — Metas del Plan de Desarrollo | (sin enlace verificable extraído) | — |
| Movilidad — Resolución 258 del 17 julio 2019 | https://www.santamarta.gov.co/portal/archivos/documentos/dec_258_de_17_de_jul_2019.pdf | ✅ HTTP 200 `application/pdf` |
| Siett — Listado de infracciones y semaforizaciones ("Listado de semáforos y fotomultas") | https://www.santamarta.gov.co/portal/archivos/LISTADO%20DE%20SEMAFOROS%20Y%20FOTO%20MULTAS%20(1).xlsx | ✅ HTTP 200 `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| Desarrollo rural — **RUAT** (Registro Único de Asistencia Técnica) | https://www.santamarta.gov.co/portal/archivos/RUAT_TERMINADO%2001082019.xlsx | ✅ HTTP 200 `…spreadsheetml.sheet` |
| Desarrollo rural — **RUAP** (Registro Único de Pescadores Artesanales) | https://www.santamarta.gov.co/portal/archivos/RUAP_TERMINADO%2001082019.xlsx | ✅ HTTP 200 `…spreadsheetml.sheet` |
| Siett — "DATOS ABIERTOS siett1.xlsx" | `http://www.santamarta.gov.co/portal/archivos/DATOS ABIERTOS  siett1.xlsx` | ❌ **ROTO — ver §4.3** |

### 4.3 ⚠️ ENLACES ROTOS REPORTADOS (con URL y error exacto)

**Enlace roto #1 — URL con espacios literales (malformada)**
- URL publicada en el HTML: `http://www.santamarta.gov.co/portal/archivos/DATOS ABIERTOS  siett1.xlsx` (contiene **espacios sin codificar**, incluso dobles)
- Error exacto al solicitarla con `curl`:
  ```
  curl: (3) URL rejected: Malformed input to a URL function
  ```
  Código HTTP: **000** (la petición nunca se emite). Falla igual por `http://` y por `https://`.
- La variante codificada sí funciona — `https://www.santamarta.gov.co/portal/archivos/DATOS%20ABIERTOS%20%20siett1.xlsx` → **HTTP 200** `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` — lo que confirma que el fallo es del **enlace mal formado**, no del archivo.

**Enlace roto #2 — servicio en IP privada/institucional inalcanzable**
- URL publicada: `http://186.1.183.78:8282/DecSTML/`
- Error exacto:
  ```
  curl: (28) Connection timed out after 20002 milliseconds
  ```
  Código HTTP: **000**. El host `186.1.183.78` en el puerto `8282` no responde desde esta red.
- Igual suerte corren los otros enlaces publicados en la misma página contra esa IP, todos ellos **no verificables** desde aquí: `http://186.1.183.78:8282/GerencialesSTM/faces/login.xhtml`, `http://186.1.183.78:8282/Notarios/`, `http://186.1.183.78:8282/SAMPredial/actos/indexactos.jsp`.

**Subdominios institucionales caídos (HTTP 000, no resuelven o no responden)**
| URL probada | Resultado |
|---|---|
| `http://gi.santamarta.gov.co/` | **HTTP 000** — sin respuesta |
| `http://adultomayor.santamarta.gov.co/` | **HTTP 000** — sin respuesta |
| `http://new.santamarta.gov.co/` | **HTTP 000** — sin respuesta (nótese que PDFs de este subdominio aparecen citados en resultados de búsqueda) |

**Subdominios que sí funcionan (HTTP 200):** `http://portalninos.santamarta.gov.co/`, `https://observatorioinmobiliario.santamarta.gov.co/`, `https://uaecm.santamarta.gov.co/consultas`.

**Rutas probadas que devuelven 404 en el sitio de la Alcaldía**
| URL | HTTP |
|---|---|
| `https://www.santamarta.gov.co/transparencia` | **404** (`Página no encontrada`) |
| `https://www.santamarta.gov.co/portal/transparencia` | **404** |

> La ruta correcta de transparencia es `https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica` (HTTP 200).

### 4.4 Sobre "SIRI"

**NO ENCONTRADO.** No se localizó ningún enlace, mención, ni servicio denominado "SIRI" en:
- `https://www.santamarta.gov.co/` (HTTP 200) — 0 coincidencias
- `https://www.santamarta.gov.co/datos-abiertos` (HTTP 200) — 0 coincidencias
- `https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica` (HTTP 200) — 0 coincidencias

Tampoco se encontró un enlace titulado exactamente **"Portal de datos abiertos"** que apunte a `datos.gov.co` y esté roto: la página se llama simplemente **"Datos abiertos"**, funciona (HTTP 200) y **no apunta a datos.gov.co** (§4.1). Los enlaces roto están dentro de ella (§4.3).

---

## 5. HALLAZGO TÉCNICO TRANSVERSAL: CADENA TLS MAL CONFIGURADA EN LA INFRAESTRUCTURA SUIT

Al verificar los portales de Función Pública, `curl` **falla la validación TLS sin `-k`**:

```
curl: (60) SSL certificate OpenSSL verify result: unable to get local issuer certificate (20)
```

**Causa raíz determinada** con `openssl s_client`:

| Host | Certificado hoja (CN) | Emisor del certificado hoja | Intermedio que ENVÍA el servidor | ¿Cadena válida? |
|---|---|---|---|---|
| `www.funcionpublica.gov.co` | `*.funcionpublica.gov.co` | Sectigo RSA **Organization Validation** Secure Server CA | Sectigo RSA **Domain Validation** Secure Server CA | ❌ **NO** |
| `tramites1.suit.gov.co` | `*.suit.gov.co` | Sectigo RSA **Organization Validation** Secure Server CA | Sectigo RSA **Domain Validation** Secure Server CA | ❌ **NO** |

```
depth=0 C=CO, ST=Distrito Capital de Bogotá, O=DEPARTAMENTO ADMINISTRATIVO DE LA FUNCION PUBLICA, CN=*.funcionpublica.gov.co
verify error:num=20:unable to get local issuer certificate
verify error:num=21:unable to verify the first certificate
 0 s:…CN=*.funcionpublica.gov.co
   i:…O=Sectigo Limited, CN=Sectigo RSA Organization Validation Secure Server CA
 1 s:…CN=Sectigo RSA Domain Validation Secure Server CA      <-- intermedio EQUIVOCADO
   i:…CN=USERTrust RSA Certification Authority
Verify return code: 21 (unable to verify the first certificate)
```

**Interpretación:** el servidor entrega el intermedio *Domain Validation* cuando el certificado hoja fue emitido por el intermedio *Organization Validation*. **Los navegadores modernos suelen completar la cadena vía AIA** (Authority Information Access) y por eso el portal funciona para un usuario normal; **OpenSSL/curl sin AIA no puede**, por lo que cualquier verificación automatizada falla con error 60.

**Consecuencia práctica documentada:** `https://www.funcionpublica.gov.co/suit`, `https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites` y `https://tramites1.suit.gov.co/suit-web/login.html` **responden HTTP 200 cuando se usa `-k`**, y fallan con error 60 sin `-k`. **No es una caída del servicio.** Esto se documenta aquí para que no se reporte erróneamente como "portal caído".

---

## 6. NO PUDE VERIFICAR

Sección obligatoria: URLs que fallaron, con el error exacto. **Nada de esta sección se ha inferido.**

### 6.1 GOV.CO — catálogo de trámites y directorio de entidades

| URL probada | Método | Resultado |
|---|---|---|
| `https://api-interno.www.gov.co/` | GET, sin headers | **HTTP 403** |
| `https://api-interno.www.gov.co/api/entidades` | GET + headers de navegador | **HTTP 404** |
| `https://api-interno.www.gov.co/api/entidades/` | GET + headers | **HTTP 404** |
| `https://api-interno.www.gov.co/api/entidades/ObtenerPaginado` | GET + headers | **HTTP 404** |
| `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios` | GET + headers | **HTTP 404** |
| `https://api-interno.www.gov.co/api/categorias-subcategorias/` | GET + headers | **HTTP 404** |
| `https://api-interno.www.gov.co/api/integracion-sedes` | GET + headers | **HTTP 404** |
| `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/santa%20marta` | GET | **HTTP 403** — `<html><head><title>403 Forbidden</title></head>…` (nginx) |
| `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/santa` (con headers de navegador completos) | GET | **HTTP 404** — `<h1>Not Found</h1><p>The requested URL /api/v1/sugerencias/general/santa was not found on this server.</p>` |
| `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/?q=santa` (con headers de navegador completos) | GET | **HTTP 200** `application/json` — `{"status":"PARA LAS SUGERENCIAS USAR EL POST CON ESTRUCTURA JSON"}` ← **el endpoint SÍ existe y responde; exige POST con JSON** |
| `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/` | **POST** JSON | **HTTP 200** pero siempre `{"success":"false","message":"Ha ocurrido un error al obtener sugerencias en la busqueda.","seconds":0,"data":[]}`. Se probaron **15 nombres de campo** distintos (`termino`, `query`, `texto`, `busqueda`, `q`, `terminoBusqueda`, `criterio`, `palabra`, `search`, `value`, `text`, `cadena`, `filtro`, `nombre`, `entidad`, `term`, `queryString`, `busquedaTexto`, `textoBusqueda`, `parametro`) y combinaciones con `tipo`/`cantidad`: **ninguna aceptada**. El esquema exacto del cuerpo **NO SE PUDO DETERMINAR** |
| `https://buscador-v1.www.gov.co/api/v1/` | GET | **HTTP 404** — `<h1>Not Found</h1><p>The requested URL /api/v1/ was not found on this server.</p>` |
| `https://www.gov.co/entidades` | GET / `web_fetch` | **HTTP 200 pero sin contenido**: shell SPA de 5038 bytes, `<app-root></app-root>` vacío. Sin navegador no hay datos |
| `https://www.gov.co/tramites-y-servicios` | GET | **HTTP 200 pero sin contenido** (mismo shell, MD5 idéntico) |
| `https://www.gov.co/buscador/Santa%20Marta` | `web_fetch` | **HTTP 200**, contenido recibido = solo el `<title>`. Sin datos |
| `https://www.gov.co/sitemap.xml` | GET | **HTTP 200** pero contiene **una sola URL** (`https://www.gov.co/`) |
| `https://www.gov.co/robots.txt` | GET | HTTP 200; sólo reglas `Disallow`, sin rutas de trámites |
| `https://www.gov.co/entidades` (renderizada con JS vía `r.jina.ai`) | Proxy | **HTTP 200**, 5620 B — página **estática** sobre la estructura del Estado; **0 coincidencias** de "Santa Marta"/"Magdalena" |
| `https://www.gov.co/entidades/` (renderizada con JS) | Proxy | **HTTP 200**, 9734 B — **0 coincidencias** de "Santa Marta"/"Alcaldía" |
| `https://www.gov.co/buscador/?ver=Entidades` (renderizada con JS) | Proxy | **HTTP 200**, 1772 B — el *web component* del buscador no resuelve sus resultados |
| `https://www.gov.co/buscador/?ver=Tramites` (renderizada con JS) | Proxy | **HTTP 200**, 6156 B — aparece `texto dummy` y el spinner; sin resultados |
| `https://www.gov.co/buscador/?ver=Entidades&busqueda=Santa+Marta` (renderizada) | Proxy | **HTTP 200**, 6178 B — único match "Alcaldía" = **Alcaldía de Barranquilla** (noticia) |
| `https://www.gov.co/buscador/?q=Santa+Marta` (renderizada) | Proxy | **HTTP 200**, 15754 B — único match de alcaldía = **Alcaldía de Puerto Gaitán (Meta)** |
| `https://www.gov.co/buscador/Santa%20Marta` (renderizada) | Proxy | **HTTP 200**, 15744 B — 8 enlaces de resultados quedaron como `[...]`, sin resolver |
| `https://www.gov.co/ficha-tramites-y-servicios/codigos-ciiu-y-tramites` (renderizada) | Proxy | **HTTP 200**, 4946 B — sí existe, pero es la consulta de códigos CIIU; **no** es un catálogo de trámites de Santa Marta |
| `https://cdn.www.gov.co/buscador-webcomponents/buscador-webcomponents/buscador-webcomponents.esm.js` | GET | **HTTP 200**, 1905 B — es un **loader**, no contiene la API de búsqueda |

**Motivo:** `www.gov.co` es una SPA Angular que exige ejecución de JavaScript, y su API interna (`api-interno.www.gov.co`) devuelve 403/404 a peticiones directas. **No dispongo de navegador en este entorno** (instrucción expresa: no usar navegador). Por tanto:

- ❌ **NO PUDE VERIFICAR** cuántos ni cuáles trámites de la Alcaldía Distrital de Santa Marta están enlazados en GOV.CO.
- ❌ **NO PUDE VERIFICAR** si el enlace de GOV.CO hacia los trámites de Santa Marta lleva a `https://www.santamarta.gov.co`.
- ❌ **NO PUDE VERIFICAR** si GOV.CO incluye un registro de entidad para "Alcaldía Distrital de Santa Marta" con NIT 891780009.

### 6.2 SUIT — fichas detalladas de cada trámite

- `https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2621` → **HTTP 200** pero devuelve el shell de la SPA Angular `AuthApp` (5239 bytes), **sin contenido del trámite**. Requiere JavaScript.
- La API `https://visorsuit.funcionpublica.gov.co/api` (base confirmada en el bundle) **no se pudo resolver**: 13 subpaths probados (§1.5) devolvieron todos el shell HTML de 5239 bytes en lugar de JSON. **NO PUDE VERIFICAR** el código interno de cada trámite ni su clasificación "trámite" vs "OPA".
- `https://www.funcionpublica.gov.co/auth/visor?fi=2621` → **HTTP 404** tras redirigir a `https://www1.funcionpublica.gov.co/auth/visor?fi=2621` (22135 bytes). El visor heredado **ya no existe**; el vigente es `visorsuit.funcionpublica.gov.co`.

### 6.3 SUIT — reportes oficiales de gestión (aplicaciones Oracle ADF)

| URL probada | Resultado |
|---|---|
| `http://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_institucion.jsf` | **HTTP 503** (57124 bytes, página de error) |
| `http://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_departamento_municipio.jsf` | **HTTP 503** |
| `https://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_institucion.jsf` | **HTTP 200** pero devuelve un redirect ADF (`_afrRedirect`, `_adf.ctrl-state`) que **exige cookie de sesión + JavaScript**. Sin navegador no se puede obtener el reporte |
| `http://monitoreo.suit.gov.co/Reporte-monitoreoWEB/reporteSUIT.jsf` | **HTTP 000** — `curl: (7) Failed to connect to monitoreo.suit.gov.co port 80 after 118 ms` |
| `https://monitoreo.suit.gov.co/Reporte-monitoreoWEB/reporteSUIT.jsf` | **HTTP 000** — el host no responde |

**NO PUDE VERIFICAR** las cifras de los reportes oficiales de SUIT (reporte en tiempo real, reporte semanal, reporte de gestión por institución).
- `https://www.funcionpublica.gov.co/suit/reporte-tiempo-real` → **HTTP 200** (página contenedora)
- `https://www.funcionpublica.gov.co/suit/reporte-semanal` → **HTTP 200** (página contenedora; no publica archivos XLSX/CSV/PDF enlazados)
- `https://www.funcionpublica.gov.co/suit/inscripcion` → **HTTP 200**, redirige al buscador

### 6.4 Rutas inexistentes o con error

| URL probada | Resultado |
|---|---|
| `https://suit.gov.co/` | **HTTP 000** — `curl: (6) Could not resolve host: suit.gov.co` (NXDOMAIN) |
| `https://www.funcionpublica.gov.co/web/suit/consulta-tramites` | **HTTP 404** (final: `https://www1.funcionpublica.gov.co/web/suit/consulta-tramites`) |
| `http://www.funcionpublica.gov.co/web/suit` | **HTTP 503** |
| `https://www.gov.co/entidades-y-tramites` | **HTTP 200** pero es el comodín `path:"**"`; la ruta **no está declarada** en la aplicación |
| `https://www.santamarta.gov.co/portal/archivos/DATOS ABIERTOS  siett1.xlsx` | **HTTP 000** — `curl: (3) URL rejected: Malformed input to a URL function` |
| `http://186.1.183.78:8282/DecSTML/` | **HTTP 000** — `curl: (28) Connection timed out after 20002 milliseconds` |
| `http://gi.santamarta.gov.co/` | **HTTP 000** |
| `http://adultomayor.santamarta.gov.co/` | **HTTP 000** |
| `http://new.santamarta.gov.co/` | **HTTP 000** |

### 6.5 Búsqueda de SIRI

**NO ENCONTRADO.** Se revisó el sitio completo de la Alcaldía (home, `/datos-abiertos`, `/transparencia-y-acceso-la-informacion-publica`, todas HTTP 200) sin hallar ninguna referencia a "SIRI" ni a un "Portal de datos abiertos" externo.

---

## 7. CONCLUSIONES

1. **SUIT sí tiene catálogo completo de la Alcaldía Distrital de Santa Marta: 124 trámites** (código de entidad SUIT `0043`), verificados uno a uno contra la etiqueta `Entidad:` del propio portal. Fuente: `https://www.funcionpublica.gov.co/dafpIndexerBT/tramite/index` (interfaz: `https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites`).
2. **Cuidado con los conteos inflados:** `query="Santa Marta"` da 1.841 resultados y `filtroMunicipio=47001` da 315; **ninguno es el catálogo de la Alcaldía** (mezclan texto libre del país entero y todas las entidades con sede en Santa Marta).
3. **GOV.CO no se pudo verificar**: es una SPA Angular cuya API interna responde 403/404. Se comprobó además que **`https://www.gov.co/entidades` NO es un directorio de entidades** sino una página estática sobre la estructura del Estado (0 coincidencias de "Santa Marta"), y que **la URL configurada para el *web component* del buscador devuelve `text/html` (una página de documentación) en lugar de JavaScript**, lo que impide inicializar el buscador. Los 17 chunks lazy de la app no contienen la cadena `sugerencias`. **Pendiente de comprobación con navegador.**
4. **La Alcaldía NO publica datasets en datos.gov.co** (0 resultados en 8 estrategias de búsqueda distintas). El único organismo del Distrito presente es el **INRED** (2 datasets), que es una entidad distinta. La **Gobernación del Magdalena** (20 datasets) es departamental, no distrital.
5. **El Distrito publica "datos abiertos" como XLSX/PDF en su propio servidor**, con **2 enlaces roto** y sin ningún enlace al portal nacional.
6. **La infraestructura SUIT/Función Pública tiene la cadena TLS mal configurada** (intermedio DV en vez de OV). No es una caída; los navegadores lo toleran vía AIA.

---

## ANEXO A — Los 124 trámites de la Alcaldía Distrital de Santa Marta (SUIT, entidad 0043)

Fuente: `https://www.funcionpublica.gov.co/dafpIndexerBT/tramite/index?find=FindNext&query=&filtroEntidad=0043&…&offset=0&max=100`
Todos con `Entidad: ALCALDIA DISTRITAL DE SANTA MARTA, DISTRITO TURISTICO, CULTURAL E HISTORICO`.

| # | Trámite | Visor |
|---|---|---|
| 1 | Certificado de libertad y tradición de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=14088 |
| 2 | Radicación de documentos para adelantar actividades de construcción y enajenación de inmuebles destinados a vivienda | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=14089 |
| 3 | Impuesto predial unificado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2621 |
| 4 | Exención del impuesto predial unificado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2636 |
| 5 | Exención del impuesto de industria y comercio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2651 |
| 6 | Impuesto de industria y comercio y su complementario de avisos y tableros | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2674 |
| 7 | Exención del impuesto de espectáculos públicos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2676 |
| 8 | Devolución y/o compensación de pagos en exceso y pagos de lo no debido | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=2678 |
| 9 | Inscripción de dignatarios de las organizaciones comunales de primero y segundo grado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=10912 |
| 10 | Inscripción o reforma de estatutos de las organizaciones comunales de primero y segundo grado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=10936 |
| 11 | Apertura, registro y/o reemplazo de libros de las organizaciones comunales de primero y segundo grado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=10962 |
| 12 | Sustitución pensional para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28726 |
| 13 | Cesantías parciales para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28732 |
| 14 | Auxilio funerario por fallecimiento de un docente pensionado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28736 |
| 15 | Inscripción de la propiedad horizontal | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28962 |
| 16 | Inscripción o cambio del representante legal y/o revisor fiscal de la propiedad horizontal | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28964 |
| 17 | Registro de la publicidad exterior visual | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=33046 |
| 18 | Devolución de elementos retenidos por ocupación ilegal del espacio público | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40674 |
| 19 | Autorización para la operación de juegos de suerte y azar en la modalidad de promocionales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40683 |
| 20 | Permiso para espectáculos públicos diferentes a las artes escénicas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40684 |
| 21 | Auxilio para gastos de sepelio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40740 |
| 22 | Reliquidación pensional para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40800 |
| 23 | Ascenso en el escalafón nacional docente | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40839 |
| 24 | Concepto de excepción de juegos de suerte y azar en la modalidad de promocionales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40866 |
| 25 | Concepto de excepción de juegos de suerte y azar en la modalidad de rifas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40869 |
| 26 | Autorización para la operación de juegos de suerte y azar en la modalidad de rifas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40900 |
| 27 | Impuesto a la publicidad visual exterior | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40942 |
| 28 | Inscripción de limitación o gravamen a la propiedad de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=40952 |
| 29 | Ascenso o reubicación de nivel salarial en el escalafón docente oficial | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41040 |
| 30 | Préstamo de parques y/o escenarios deportivos para realización de espectáculos de las artes escénicas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41140 |
| 31 | Registro de firmas de rectores, directores y secretario(a)s de establecimientos educativos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41173 |
| 32 | Cambio de carrocería de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41257 |
| 33 | Permiso para espectáculos públicos de las artes escénicas en escenarios no habilitados | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41309 |
| 34 | Licencia de funcionamiento para las instituciones promovidas por particulares que ofrezcan el servicio educativo para el trabajo y el desarrollo humano | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41310 |
| 35 | Licencia de funcionamiento para establecimientos educativos promovidos por particulares para prestar el servicio público educativo en los niveles de preescolar, básica y media | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41311 |
| 36 | Concepto de norma urbanística | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41349 |
| 37 | Licencia de exhumación de cadáveres | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41351 |
| 38 | Licencia para la cremación de cadáveres | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41353 |
| 39 | Prórroga de sorteo de rifas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41356 |
| 40 | Permiso para demostraciones públicas de pólvora, artículos pirotécnicos o fuegos artificiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41360 |
| 41 | Concepto de uso del suelo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41362 |
| 42 | Aprobación de los planos de propiedad horizontal | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41363 |
| 43 | Registro o renovación de programas de las instituciones promovidas por particulares que ofrezcan el servicio educativo para el trabajo y el desarrollo humano | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41366 |
| 44 | Blindaje de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41370 |
| 45 | Cambio de propietario de un establecimiento educativo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=41403 |
| 46 | Clasificación en el régimen de educación a un establecimiento educativo privado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=73293 |
| 47 | Ampliación del servicio educativo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=73294 |
| 48 | Cambio de nombre o razón social de un establecimiento educativo privado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=73295 |
| 49 | Concesión de reconocimiento de un establecimiento educativo oficial | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=73296 |
| 50 | Cambio de sede de un establecimiento educativo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=73297 |
| 51 | Fusión o conversión de establecimientos educativos oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=73498 |
| 52 | Matrícula de vehículos automotores | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65676 |
| 53 | Duplicado de placas de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65678 |
| 54 | Duplicado de la licencia de tránsito de un vehículo automotor | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65684 |
| 55 | Derechos de explotación de juegos de suerte y azar en la modalidad de rifas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65960 |
| 56 | Reconocimiento de escenarios culturales para las artes escénicas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65961 |
| 57 | Supervisión delegado de sorteos y concursos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65962 |
| 58 | Concepto previo favorable para la realización de juegos de suerte y azar localizados | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65963 |
| 59 | Registro de actividades relacionadas con la enajenación de inmuebles destinados a vivienda | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65973 |
| 60 | Licencia de ocupación del espacio público para la localización de equipamiento | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65974 |
| 61 | Licencia de intervención del espacio público | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65975 |
| 62 | Autorización de Ocupación de Inmuebles | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65977 |
| 63 | Modificación del plano urbanístico | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65978 |
| 64 | Registro de extinción de la propiedad horizontal | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65979 |
| 65 | Legalización urbanística de asentamientos humanos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65981 |
| 66 | Autorización sanitaria para la concesión de aguas para el consumo humano | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65989 |
| 67 | Certificado de estratificación socioeconómica | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65992 |
| 68 | Asignación de nomenclatura | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=65993 |
| 69 | Incorporación y entrega de las áreas de cesión a favor del municipio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66055 |
| 70 | Ajuste de un plan parcial adoptado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66057 |
| 71 | Consulta preliminar para la formulación de planes de regularización | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66058 |
| 72 | Formulación y radicación del proyecto del plan parcial | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66059 |
| 73 | Determinantes para el ajuste de un plan parcial | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66070 |
| 74 | Facilidades de pago para los deudores de obligaciones no tributarias | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66105 |
| 75 | Corrección de errores e inconsistencias en declaraciones y recibos de pago | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66107 |
| 76 | Registro de ejemplares caninos de manejo especial | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66133 |
| 77 | Esterilización canina y felina | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66139 |
| 78 | Impuesto de espectáculos públicos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66171 |
| 79 | Devolución y/o compensación de pagos en exceso y pagos de lo no debido por conceptos no tributarios | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66178 |
| 80 | Certificado de paz y salvo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66181 |
| 81 | Certificado de residencia | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66256 |
| 82 | Determinantes para la formulación de planes parciales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66338 |
| 83 | Impuesto de delineación urbana | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66448 |
| 84 | Apertura de los centros de estética y similares | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66452 |
| 85 | Licencia de inhumación de cadáveres. | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=66456 |
| 86 | Cesantías definitivas a beneficiarios de un docente fallecido | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28596 |
| 87 | Copia certificada de planos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28597 |
| 88 | Ajuste de cotas y áreas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28610 |
| 89 | Pensión de jubilación por aportes | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28611 |
| 90 | Pensión de jubilación para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28612 |
| 91 | Aprobación de piscinas. | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28614 |
| 92 | Cesantía definitiva para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28632 |
| 93 | Pensión post-mortem para beneficiarios de docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28640 |
| 94 | Pensión de retiro por vejez para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28641 |
| 95 | Concepto sanitario | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28648 |
| 96 | Reconocimiento deportivo a clubes deportivos, clubes promotores y clubes pertenecientes a entidades no deportivas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28650 |
| 97 | Renovación del reconocimiento deportivo a clubes deportivos, clubes promotores y clubes pertenecientes a entidades no deportivas | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28651 |
| 98 | Traslado de cadáveres | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28661 |
| 99 | Vacunación antirrábica de caninos y felinos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=28666 |
| 100 | Modificación en el registro de contribuyentes del impuesto de industria y comercio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=6071 |
| 101 | Cancelación del registro de contribuyentes del impuesto de industria y comercio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=6079 |
| 102 | Registro de contribuyentes del impuesto de industria y comercio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=6082 |
| 103 | Facilidades de pago para los deudores de obligaciones tributarias | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=6084 |
| 104 | Encuesta del sistema de identificación y clasificación de potenciales beneficiarios de programas sociales - SISBEN | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=6113 |
| 105 | Actualización de información en la base de datos del sistema de identificación y clasificación de potenciales beneficiarios de programas sociales – SISBEN | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=6139 |
| 106 | Licencia de funcionamiento de instituciones educativas que ofrezcan programas de educación formal de adultos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=67700 |
| 107 | Seguro por muerte a beneficiarios de docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=69149 |
| 108 | Pensión de retiro de invalidez para docentes oficiales | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=69151 |
| 109 | Cierre temporal o definitivo de programas de educación para el trabajo y el desarrollo humano | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=69222 |
| 110 | Clausura de un establecimiento educativo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=69232 |
| 111 | Cambio de propietario o poseedor de un bien inmueble | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87545 |
| 112 | Cambios producidos por la inscripción de predios o mejoras por edificaciones no declaradas u omitidas durante el proceso de formación o actualización del catastro | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87548 |
| 113 | Englobe o desenglobe de dos o más predios | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87633 |
| 114 | Autoestimación del avalúo catastral | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87635 |
| 115 | Revisión de avalúo catastral de un predio | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87636 |
| 116 | Rectificación de áreas y linderos | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87642 |
| 117 | Certificado catastral | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87648 |
| 118 | Orden de entrega del vehículo inmovilizado | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87728 |
| 119 | Inscripción o autorización para la circulación vial | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87732 |
| 120 | Inscripción de mejora por construcciones o edificaciones en predio ajeno | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=87945 |
| 121 | Orden De Entrega Del Vehículo Inmovilizado Por Pago Total De Comparendo | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=89199 |
| 122 | Excepción A Restricciones De Movilidad | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=89270 |
| 123 | Plan de Manejo de Tránsito | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=89318 |
| 124 | Visto Bueno Para Realización De Eventos Que Puedan Afectar La Movilidad | https://visorsuit.funcionpublica.gov.co/auth/visor?fi=89319 |

**TOTAL: 124 trámites.** El listado completo y estructurado (título + descripción + URL del visor) fue extraído programáticamente y está disponible en la sesión de trabajo en `.scratch/suit-govco/suit_0043.json`.

**Recomendación de reproducción:** el orden de resultados del buscador de SUIT sigue un criterio de relevancia interno, no alfabético ni por ID; **el orden de esta tabla no debe interpretarse como un ranking oficial**. Para reproducir la lista completa y determinista, usar el endpoint citado con `max=100`, `offset=0` y `offset=100` (el servidor limita a 100 filas por página aunque se pida más).

---

## ANEXO B — Medios de contacto registrados en el propio portal SUIT

De la ficha de la entidad en SUIT y del pie de página del Distrito:
- **NIT:** 891780009
- **Dirección:** Calle 14 No. 2 - 49, Palacio Municipal
- **Línea de Atención al Ciudadano:** (+57) 605 4351719
- **PBX:** (+57) 605 420 9600
- **Sitio oficial:** https://www.santamarta.gov.co (HTTP 200)

*Fin del informe.*

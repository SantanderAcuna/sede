# Transparencia y acceso a la información pública

Conocimiento de la sección. **No es contenido publicable**: es lo que sabemos de
ella, de dónde sale cada dato, qué defectos tiene el origen y qué tiene que
exponer el backend. El frontend construye el diseño; este documento guarda el
conocimiento.

> **Estado del frontend.** `/transparencia` es hoy un stub de 17 líneas. Se
> construyó una versión completa con 333 documentos incrustados y **se retiró**: el
> contenido institucional no vive en el frontend. Esta sección se construye cuando
> el backend la sirva por API.

---

## 1. Qué obliga la norma

**Ley 1712 de 2014** (transparencia y acceso a la información pública) y la
**Resolución MinTIC 1519 de 2020**, que sustituye a la **3564 de 2015** —el sitio
actual de la Alcaldía aún cita la derogada—.

Exige **nueve categorías**, publicadas **de la más reciente a la más antigua**
(Anexo 2 de la Resolución 2893 §4.1.2.1) y con **la fecha de publicación de cada
documento** visible (Resolución 1519 num. 2.4.1.e).

## 2. Las nueve categorías

| # | Categoría | Qué publica |
|---|---|---|
| 1 | Información de las entidades | La información básica del sujeto obligado: normativa de creación, misión, funciones, organigrama, directorio |
| 2 | Normativa | Normas, con tipo, fecha de expedición, fecha de publicación, epígrafe y enlace, y **su vigencia** |
| 3 | Contratación | Procesos contractuales, con el PAA del año vigente y el SECOP |
| 4 | Planeación, presupuesto e informes | Planes, presupuestos, ejecución e informes de gestión |
| 5 | Trámites y servicios | Los trámites publicados y su normativa |
| 6 | Participa | Los mecanismos de participación y sus resultados |
| 7 | Datos abiertos | Conjuntos de datos **federados a `datos.gov.co`** |
| 8 | Información específica para grupos de interés | Infancia, mujer, personas con discapacidad, víctimas, etc. |
| 9 | **Información específica de la entidad** | La que la Entidad determine |

## 3. Qué publica hoy la Alcaldía

Rastreo del sitio actual, `2026-09-30`: **334 enlaces en 8 categorías, con 319
destinos únicos**. Falta la novena.

| # | Categoría | Enlaces | Apartados | Estado real |
|---|---|---|---|---|
| 1 | Información de las entidades | 29 | varios | publicada |
| 2 | Normativa | 33 | varios | publicada |
| 3 | **Contratación** | **6** | 5 de 6 externos | **parcial** |
| 4 | Planeación, presupuesto e informes | **241** | 10 | publicada — el grueso |
| 5 | **Trámites y servicios** | **1** | — | **parcial** |
| 6 | Participa | 17 | varios | publicada |
| 7 | **Datos abiertos** | **0** | — | **declarada** |
| 8 | Información específica para grupos de interés | 6 | 3 de 6 a la portada | **parcial** |
| 9 | Información específica de la entidad | **0** | — | **ausente** |

## 4. La fecha de publicación: no consta en ninguna parte

**Cero de los 334 enlaces declara fecha de publicación.** No es una impresión:
`grep «Fecha de publicación»` sobre los 1.730 ficheros del rastreo da **cero
coincidencias**.

Consecuencia: mientras la Entidad no declare fechas, **la sección no puede
cumplir el Anexo 2 §4.1.2.1** —orden de la más reciente a la más antigua—, y eso
hay que declararlo en la página en vez de inventar una cronología.

## 5. Defectos del origen, que no hay que reproducir

1. **Etiqueta malformada `<liclass>`** (sin espacio antes de `class`). Rompe el
   anidamiento y hace que los apartados 4.7, 4.8 y 4.9 cuelguen dentro de 4.6.
   Sin reparar, la categoría 4 se lee con **153 documentos en 7 apartados**;
   reparada, con **241 en 10**. Son **88 documentos** que desaparecen sin que nada
   lo delate.
2. **El encabezado de 4.9 es un `<a>` suelto**, fuera de todo `<li>`: sus 10
   documentos cuelgan de 4.8.
3. **2.1.5 «Políticas, lineamientos y manuales» está anidado bajo «2.1 Decretos»**
   —no son decretos— y la serie salta del 2.1.1 al 2.1.5 sin publicar los
   intermedios.
4. **Diez URLs relativas** (`./sites/default/files/...`). En la Sede resolverían
   contra nuestro propio dominio y darían 404.
5. **Una URL malformada** con un punto suelto delante.
6. **Duplicados**: 334 enlaces pero 319 destinos; la portada aparece 4 veces y un
   mismo PDF 3 veces.
7. **«Plan Antitrámite» enlaza la portada del sitio**, no el plan.
8. **Categoría 3**: el PAA enlaza el archivo de **2019** cuando la categoría 4
   publica hasta 2026; «Ofertas de empleo» enlaza una convocatoria de la **CNSC de
   2018**; «ejecución de contratos» enlaza el **SIA Observa de la Auditoría
   General**, de nivel nacional.
9. **Categoría 7** enlaza **a sí misma**, no a `datos.gov.co`.
10. **Numeración rota**: no existen **1.8, 1.9, 1.14 ni 6.1**; hay **tres ítems
    numerados 3.2**; y **2.8 (Actos administrativos) está vacío** (`href="#"`).

## 6. La trampa de los datos abiertos

En el rastreo hay cuatro volcados `ds_*.json`. **Ninguno contiene un solo conjunto
de la Alcaldía Distrital de Santa Marta.**

| Fichero | Qué devuelve |
|---|---|
| `ds_Alcaldía_Distrital_de_Santa_Marta.json` | **24 de Barranquilla**, 7 de Santa Rosa de Cabal, **0 de Santa Marta** |
| `ds_Alcaldia_de_Santa_Marta.json` | 0 del Distrito |
| `ds_Santa_Marta_DTCH.json` | vacío (`resultSetSize: 0`) |
| `ds_all.json` | Gobernación del Magdalena, Universidad del Magdalena, Cámara de Comercio, Invemar, Corpamag… |

**Usarlos publicaría datos de otro municipio como si fueran del Distrito.**

La categoría 7 se construye consultando la API de `datos.gov.co` **filtrando por
entidad propietaria**. Mientras no haya conjuntos propios, la categoría se declara
vacía con su enlace a la fuente federada — que es lo que la norma pide: federar, no
alojar copias.

## 7. Qué tiene que exponer el backend

La sección no puede construirse con contenido en el frontend. Necesita, como
mínimo:

- **`GET /api/v1/transparencia/categorias`** — las nueve, con su número, slug,
  nombre y **cuántos documentos publica cada una**.
- **`GET /api/v1/transparencia/categorias/{slug}`** — el detalle: los apartados y
  sus documentos.
- **`GET /api/v1/transparencia/documentos`** — con **paginación** y **búsqueda**
  por el parámetro `buscar` (la categoría 2 exige buscador propio, y el de la
  sección es el mismo mecanismo con otro alcance).

**Cada documento** debe llevar: título, URL de descarga, apartado al que
pertenece, **fecha de publicación** (nullable, y la interfaz dice «no consta»
cuando falta), y su **procedencia**.

Y la construcción completa que pide el proyecto: **endpoint, controlador, modelo,
resource, FormRequest, Policy, migración, contrato de servicio, contrato de
repositorio, repositorio, servicio, rutas y siembra**.

## 8. Fuentes

| Qué | Dónde |
|---|---|
| El sitio actual, rastreado | `investigacion/raw/` — 1.918 ficheros, **sólo lectura** |
| La página de transparencia | `investigacion/raw/transparencia-y-acceso-la-informacion-publica.{html,txt}` |
| El inventario con títulos y URLs | `investigacion/raw/crawl_meta.json` (1.730 entradas) y `crawl_index.json` |
| Las fichas de documento | `investigacion/raw/crawl_cache/` — 87 listados + 1.643 fichas |
| La norma | Ley 1712 de 2014 y Resolución MinTIC 1519 de 2020 (`investigacion/raw/nat/res1519.html`) |

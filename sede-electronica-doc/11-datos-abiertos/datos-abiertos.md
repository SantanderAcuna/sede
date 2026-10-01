# Módulo 11 — Datos Abiertos

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** sección de datos abiertos vinculada a `datos.gov.co`, publicación en formatos abiertos con metadatos completos, registro de activos de información y análisis de criticidad, licencia abierta y plan de apertura.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Subsección de Transparencia (módulo 02) con peso propio. La Alcaldía publica conjuntos de datos en formatos abiertos federados al Portal Nacional `datos.gov.co`, con metadatos, licencia abierta y registro de activos de información. Recordar: `datos.gov.co` **no** es archivo digital (la entidad mantiene sus obligaciones de TRD — ver módulo 12).

## 2. Requisitos Funcionales (RF)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-019 / RF-B2-087 / RF-B3-086 | Sección de **datos abiertos** vinculada a `datos.gov.co`: nombre del conjunto, categoría, fecha de última actualización, formato, enlace de descarga; **federación automática** al portal nacional; instrumentos de gestión documental (RAI, ICI&R, EPI, PGD, TRD, costos de reproducción). | #3,#8,#225,#241,#242,#258,#221 | Must | Clic en un dataset → descarga en formato abierto (CSV/JSON/XML) sin restricciones legales. |
| RF-B1-020 | Crear y cargar el **registro de activos de información** y análisis de criticidad en la herramienta de `datos.gov.co`; aprobar y publicar **licencia de datos abiertos**; establecer plan de apertura. | #8,#242 | Must | Con activos identificados, el responsable carga el inventario con criticidad y se refleja en el portal. |
| RF-B1-090 | Publicar datasets en formatos abiertos: **CSV, XML, RDF, RSS, JSON, ODF**; geoespaciales: **WMS, WFS**; con licencia abierta sin restricciones. | #3,#8,#242 | Must | El ciudadano procesa el dataset sin software propietario. |
| RF-B1-091 | Cada dataset con **metadatos completos**: nombre, descripción, categoría, entidad publicadora, fecha de creación, última actualización, formato, licencia, URL de descarga. | #8,#242 | Must | Antes de descargar, el ciudadano ve todos los metadatos. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-11-D01 | **CRUD y actualización programada de datasets**: editar metadatos, versionar, despublicar y **actualizar según la frecuencia declarada**, con alerta de dataset desactualizado. | [DOMINIO] UC-B2-013 solo publica; falta U/D y control de frescura | Should | Dataset con frecuencia "mensual" sin actualizar en 35 días → alerta al responsable de datos. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-036 / RNF-B2-003(datos) | Formatos de datos abiertos | CSV, XML, RDF, RSS, JSON, ODF, WMS, WFS | #8,#242 |
| RNF-B3-039 | Calidad / procesabilidad | ≥90% de documentos en formatos abiertos y procesables por máquina | #132,#217 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-11-D01 | Validación de calidad del dato | Validar al cargar que el archivo abre como CSV/JSON/XML bien formado y que los metadatos obligatorios (RF-B1-091) están completos antes de federar a datos.gov.co. | [DOMINIO] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-019 | `datos.gov.co` **NO** es archivo digital; la Alcaldía mantiene sus obligaciones de TRD independientemente de lo que publique allí. | Res. 1519/2020 Anexo 4, 4.1 par. | #242 |
| RN-B1-026 (fase2) | Datos abiertos en formatos abiertos bajo licencia abierta. | Res. 1519/2020 Anexo 4, 4.2 | #242 |
| RN (Ley 1753) | Publicar datos en formatos y metadatos definidos por MinTIC. | Ley 1753/2015 Art.45-d | #258 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-11-D01 | **Control de frescura del dataset:** un dataset publicado debe actualizarse según la frecuencia declarada en sus metadatos; superada la frecuencia + margen, se marca "desactualizado" y se alerta al responsable de datos. (Motiva RF-11-D01.) | Res. 1519/2020 Anexo 4, 4.2; Ley 1753 Art.45-d | [NORMATIVA]+[DOMINIO] |
| RN-11-D02 | **Validación de calidad previa a federar:** antes de publicar/federar a datos.gov.co, el archivo debe abrir como CSV/JSON/XML bien formado y tener los metadatos obligatorios completos; en caso contrario no se federa. (Motiva RNF-11-D01.) | Res. 1519/2020 Anexo 4 | [NORMATIVA]+[DOMINIO] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-008 | Consultar y descargar datos abiertos | Ciudadano / Investigador | "Datos Abiertos" → catálogo (nombre, categoría, fecha, formato) → filtra/busca → metadatos y licencia → descarga (CSV/JSON…) o enlace a datos.gov.co. | #8,#239,#242 |
| UC-B2-013 | Publicar datos abiertos | Administrador del portal | Sube dataset → configura metadatos (nombre, descripción, frecuencia, formato) → publica en formatos abiertos → se indexa en el catálogo y queda accesible sin autenticación. | #258 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-008 | Como investigador quiero descargar datasets en CSV/JSON para mi investigación. | **Positivo:** **Dado** que accedo al catálogo de datos abiertos **cuando** filtro por categoría "presupuesto" y selecciono un dataset **entonces** puedo descargarlo en CSV o JSON sin registro previo ni costo. <br> **Negativo:** **Dado** un dataset cuya fecha de última actualización supera la frecuencia declarada **cuando** lo consulto **entonces** veo la marca "última actualización" con su antigüedad, de forma que puedo evaluar la vigencia del dato antes de descargarlo. | #8,#239,#242 |
| HU-B2-018 | Como administrador quiero publicar datasets de trámites y servicios para fomentar transparencia. | **Positivo:** **Dado** que subo un dataset con metadatos completos y el archivo está bien formado **cuando** confirmo la publicación **entonces** el dataset se publica en CSV, JSON y XML, queda indexado en el catálogo y es accesible sin autenticación. <br> **Negativo:** **Dado** que el archivo subido está mal formado o los metadatos obligatorios están incompletos **cuando** intento publicar y federar a datos.gov.co **entonces** el sistema rechaza la operación e indica el detalle del error sin federar el dataset. | #258 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-11-D01 | Como administrador del portal quiero editar, versionar y controlar la frescura de los datasets para mantener los datos abiertos actualizados. | **Positivo:** *Dado* un dataset con frecuencia "mensual", *cuando* lo actualizo, *entonces* se versiona conservando la versión previa y se reinicia el contador de frescura.<br>**Negativo (desactualizado):** *Dado* un dataset mensual sin actualizar en 35 días, *cuando* corre el chequeo, *entonces* se marca "desactualizado" y se alerta al responsable. | RF-11-D01 · RN-11-D01 · UC-049 · [DOMINIO]+[NORMATIVA] Res.1519 Anexo 4 |
| HU-11-D02 | Como administrador quiero validar el archivo y los metadatos antes de federar a datos.gov.co para no publicar datos mal formados. | **Positivo:** *Dado* un CSV bien formado con metadatos completos, *cuando* publico, *entonces* se federa a datos.gov.co.<br>**Negativo:** *Dado* un archivo mal formado o metadatos incompletos, *cuando* intento federar, *entonces* el sistema lo rechaza con el detalle del error. | RNF-11-D01 · RN-11-D02 · UC-049 E2 · [NORMATIVA] Res.1519 Anexo 4 |

## 7. Datos / Entidades del módulo

- **Dataset:** nombre, descripción, categoría, entidad publicadora, fecha de creación, última actualización, formato, licencia, URL de descarga, nivel de criticidad (crítico/estratégico/muy importante). (#8,#239,#242)
- **Registro de activos de información:** inventario de activos, criticidad, licencia, plan de apertura. (#8,#242)

## 8. Integraciones

- **datos.gov.co** (Portal Nacional de Datos Abiertos): federación/vinculación y registro de activos. (#239,#242)
- **Transparencia / Información de la entidad** (módulo 02): la subsección de datos abiertos pertenece al menú de Transparencia.

## 9. Ambigüedades y preguntas abiertas

- **[A-04]** `datos.gov.co` NO es archivo digital, pero el requisito obliga a vincular/automatizar los datos para su apertura allí → posible confusión sobre qué va al portal vs. al archivo documental. (#242)
- **[PREGUNTA ABIERTA]** ¿El SM CMS tiene capacidad de actualización automática hacia `datos.gov.co` o es proceso manual? ¿La Alcaldía tiene el registro de activos cargado? (#239,#242)

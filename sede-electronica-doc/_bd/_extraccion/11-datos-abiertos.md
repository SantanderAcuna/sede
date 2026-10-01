# Extracción BD — Módulo 11 Datos Abiertos

## 0. Cobertura

- **Unidad leída:** `/var/www/proyect-doc/elicitacion/sede-electronica/11-datos-abiertos/datos-abiertos.md`
- **Líneas cubiertas:** 1–97
- **Íntegra:** SÍ — líneas 1–97 leídas íntegras, 0 omitidas.

---

## 1. Entidades candidatas

| # | Nombre | Descripción | CANDIDATA-COMPARTIDA | Fuente |
|---|--------|-------------|----------------------|--------|
| 1 | **Dataset** | Conjunto de datos publicado en formatos abiertos, federado a `datos.gov.co`, con metadatos y licencia abierta. Catálogo público consultable y descargable. | No | datos-abiertos.md:85 (§7), RF-B1-019, RF-B1-091 |
| 2 | **DatasetVersion** | Versión histórica de un dataset; se conserva la versión previa al actualizar. | No [INFERIDO de HU-11-D01 y RN-11-D01] | datos-abiertos.md:80 (§6.D), RF-11-D01 |
| 3 | **DatasetMetadata** | Conjunto de metadatos descriptivos obligatorios asociados a un dataset (nombre, descripción, categoría, entidad publicadora, fechas, formato, licencia, URL de descarga). | No | datos-abiertos.md:85 (§7), RF-B1-091 |
| 4 | **RegistroActivoInformacion** | Inventario de activos de información de la Alcaldía con clasificación de criticidad y plan de apertura; se carga en `datos.gov.co`. | **CANDIDATA-COMPARTIDA** (cruza con módulo 02 Transparencia y módulo 12 Gestión Documental / TRD) | datos-abiertos.md:86 (§7), RF-B1-020 |
| 5 | **FormatoAbierto** | Catálogo de formatos admitidos para publicación (CSV, XML, RDF, RSS, JSON, ODF, WMS, WFS). | No | datos-abiertos.md:19 (RF-B1-090), RNF-B1-036 |
| 6 | **LicenciaDatos** | Licencia abierta bajo la que se publican los datasets (sin restricciones legales para descarga y reutilización). | No [INFERIDO: entidad separada para reuso entre datasets] | datos-abiertos.md:17–19 (RF-B1-019, RF-B1-090), RN-B1-026 |
| 7 | **AlertaFrescura** | Registro de alerta generada cuando un dataset supera el margen sobre su frecuencia de actualización declarada. | No [INFERIDO de RN-11-D01 y HU-11-D01] | datos-abiertos.md:28 (RF-11-D01), 57 (RN-11-D01), 80 (HU-11-D01) |
| 8 | **FederacionExterna** | Registro de intentos de federación a `datos.gov.co` (resultado, timestamp, detalle de error si aplica). | No [INFERIDO de RN-11-D02, HU-11-D02, HU-B2-018] | datos-abiertos.md:58 (RN-11-D02), 72 (HU-B2-018), 81 (HU-11-D02) |

---

## 2. Atributos / campos por entidad

### 2.1 Dataset

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| dataset_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| nombre | VARCHAR(255) | SÍ | Texto libre | — | datos-abiertos.md:85, RF-B1-091 |
| descripcion | TEXT | SÍ | Texto libre | — | datos-abiertos.md:85, RF-B1-091 |
| categoria | VARCHAR(100) | SÍ | Dominio por definir (ej. presupuesto, trámites) | — | datos-abiertos.md:85, RF-B1-091, HU-B1-008 |
| entidad_publicadora | VARCHAR(255) | SÍ | Alcaldía Distrital de Santa Marta / dependencia | — | datos-abiertos.md:85, RF-B1-091 |
| fecha_creacion | DATE | SÍ | Fecha calendario | — | datos-abiertos.md:85, RF-B1-091 |
| ultima_actualizacion | DATE | SÍ | Fecha calendario; se reinicia en cada versión | — | datos-abiertos.md:85, RF-B1-091, HU-11-D01 |
| formato_id | INTEGER | SÍ | FK → FormatoAbierto | FK | datos-abiertos.md:85, RF-B1-090, RF-B1-091 |
| licencia_id | INTEGER | SÍ | FK → LicenciaDatos | FK | datos-abiertos.md:85, RF-B1-091, RN-B1-026 |
| url_descarga | VARCHAR(2048) | SÍ | URL válida | — | datos-abiertos.md:85, RF-B1-091 |
| nivel_criticidad | VARCHAR(30) | SÍ | ENUM: 'crítico', 'estratégico', 'muy_importante' | — | datos-abiertos.md:85 (§7) |
| frecuencia_actualizacion | VARCHAR(50) | SÍ | Ej. 'diaria', 'semanal', 'mensual', 'anual' | — | datos-abiertos.md:28 (RF-11-D01), RN-11-D01 |
| estado | VARCHAR(20) | SÍ | ENUM: 'borrador', 'publicado', 'desactualizado', 'despublicado' | — | datos-abiertos.md:28 (RF-11-D01), HU-11-D01 |
| federado_datos_gov | BOOLEAN | SÍ | true / false | — | datos-abiertos.md:17 (RF-B1-019), 90 (§8) |
| activo_informacion_id | INTEGER | NO | FK → RegistroActivoInformacion (puede no estar vinculado) | FK | datos-abiertos.md:86 (§7), RF-B1-020 |

### 2.2 DatasetVersion

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| version_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| dataset_id | INTEGER | SÍ | FK → Dataset | FK | datos-abiertos.md:80 (HU-11-D01), RF-11-D01 |
| numero_version | INTEGER | SÍ | Entero positivo incremental | — | [INFERIDO de HU-11-D01: "conservando la versión previa"] |
| fecha_version | TIMESTAMP | SÍ | Momento de creación de la versión | — | [INFERIDO] |
| url_descarga_version | VARCHAR(2048) | SÍ | URL al snapshot del archivo en esa versión | — | [INFERIDO de HU-11-D01] |
| formato_id | INTEGER | SÍ | FK → FormatoAbierto | FK | [INFERIDO: la versión hereda o puede cambiar formato] |
| creado_por | INTEGER | NO | FK → Usuario (administrador que publicó) | FK | [INFERIDO de UC-B2-013] |

### 2.3 DatasetMetadata

> Nota de diseño: los metadatos descritos en RF-B1-091 son atributos directos del Dataset (§2.1). Esta entidad existe si se requiere extensibilidad (metadatos adicionales dinámicos por norma MinTIC o datos.gov.co). Se modela como tabla de extensión clave-valor para no romper la estructura principal.

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| metadata_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| dataset_id | INTEGER | SÍ | FK → Dataset | FK | datos-abiertos.md:85, RF-B1-091 |
| clave | VARCHAR(100) | SÍ | Nombre del campo de metadato (ej. 'cobertura_geografica') | — | [INFERIDO de Ley 1753 Art.45-d: "formatos y metadatos definidos por MinTIC"] |
| valor | TEXT | SÍ | Valor del metadato | — | [INFERIDO] |
| obligatorio | BOOLEAN | SÍ | Si es requerido por MinTIC / Res. 1519/2020 | — | [INFERIDO de RN-11-D02: "metadatos obligatorios completos"] |

### 2.4 RegistroActivoInformacion

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| activo_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| nombre_activo | VARCHAR(255) | SÍ | Nombre del activo de información | — | datos-abiertos.md:86 (§7), RF-B1-020 |
| nivel_criticidad | VARCHAR(30) | SÍ | ENUM: 'crítico', 'estratégico', 'muy_importante' | — | datos-abiertos.md:86 (§7), RF-B1-020 |
| licencia_id | INTEGER | SÍ | FK → LicenciaDatos | FK | datos-abiertos.md:86 (§7), RF-B1-020 |
| plan_apertura | TEXT | NO | Descripción del plan de apertura del activo | — | datos-abiertos.md:86 (§7), RF-B1-020 |
| cargado_datos_gov | BOOLEAN | SÍ | true si ya fue cargado al portal nacional | — | datos-abiertos.md:86 (§7), RF-B1-020 |
| fecha_carga | DATE | NO | Fecha en que se cargó a datos.gov.co | — | [INFERIDO de RF-B1-020] |
| modulo_origen | VARCHAR(50) | NO | 'transparencia', 'gestion_documental', 'datos_abiertos' — identifica módulo que generó el activo | — | [INFERIDO — CANDIDATA-COMPARTIDA con módulos 02 y 12] |

### 2.5 FormatoAbierto

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| formato_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| codigo | VARCHAR(10) | SÍ | UNIQUE; valores: 'CSV', 'XML', 'RDF', 'RSS', 'JSON', 'ODF', 'WMS', 'WFS' | — | datos-abiertos.md:19 (RF-B1-090), RNF-B1-036 |
| descripcion | VARCHAR(255) | NO | Descripción del formato | — | [INFERIDO] |
| tipo | VARCHAR(20) | SÍ | ENUM: 'tabular', 'geoespacial', 'feed', 'documento' | — | [INFERIDO de RF-B1-090: WMS/WFS son geoespaciales, CSV/JSON/XML son tabulares] |
| procesable_maquina | BOOLEAN | SÍ | true para todos los listados; RNF-B3-039 exige ≥90% de documentos en formatos procesables | — | datos-abiertos.md:35 (RNF-B3-039) |

### 2.6 LicenciaDatos

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| licencia_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| nombre | VARCHAR(100) | SÍ | UNIQUE; ej. 'CC BY 4.0', 'Dominio público' | — | datos-abiertos.md:19 (RF-B1-090), RN-B1-026 |
| descripcion | TEXT | NO | Texto de la licencia | — | [INFERIDO] |
| url_licencia | VARCHAR(2048) | NO | URL al texto oficial de la licencia | — | [INFERIDO de RF-B1-091: ciudadano ve metadatos incluyendo licencia] |
| permite_reutilizacion | BOOLEAN | SÍ | true — todas las licencias admitidas deben ser abiertas | — | datos-abiertos.md:19 (RF-B1-090), RN-B1-026: "sin restricciones" |
| aprobada | BOOLEAN | SÍ | true si fue aprobada formalmente (RF-B1-020: "aprobar y publicar licencia") | — | datos-abiertos.md:18 (RF-B1-020) |

### 2.7 AlertaFrescura

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| alerta_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| dataset_id | INTEGER | SÍ | FK → Dataset | FK | datos-abiertos.md:28 (RF-11-D01), 57 (RN-11-D01) |
| fecha_alerta | TIMESTAMP | SÍ | Momento en que se generó la alerta | — | [INFERIDO] |
| dias_sin_actualizar | INTEGER | SÍ | Días transcurridos desde última_actualizacion; ej. 35 para frecuencia mensual | — | datos-abiertos.md:28 (RF-11-D01): "35 días" |
| responsable_notificado | INTEGER | NO | FK → Usuario (responsable de datos notificado) | FK | datos-abiertos.md:28 (RF-11-D01), 80 (HU-11-D01) |
| resuelta | BOOLEAN | SÍ | false mientras el dataset no se actualice | — | [INFERIDO de HU-11-D01: "reinicia el contador de frescura"] |

### 2.8 FederacionExterna

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| federacion_id | INTEGER (SERIAL) | SÍ | Autonumérico | PK | [INFERIDO] |
| dataset_id | INTEGER | SÍ | FK → Dataset | FK | datos-abiertos.md:58 (RN-11-D02), 72 (HU-B2-018) |
| portal_destino | VARCHAR(100) | SÍ | 'datos.gov.co' (actualmente único portal) | — | datos-abiertos.md:90 (§8), RF-B1-019 |
| fecha_intento | TIMESTAMP | SÍ | Momento del intento de federación | — | [INFERIDO] |
| resultado | VARCHAR(20) | SÍ | ENUM: 'exitosa', 'rechazada_formato', 'rechazada_metadatos', 'error_red' | — | datos-abiertos.md:72 (HU-B2-018), 81 (HU-11-D02) |
| detalle_error | TEXT | NO | Descripción del error cuando resultado != 'exitosa' | — | datos-abiertos.md:72 (HU-B2-018): "indica el detalle del error" |
| archivo_validado | BOOLEAN | SÍ | true si el archivo pasó validación de formato antes del intento | — | datos-abiertos.md:41 (RNF-11-D01), 58 (RN-11-D02) |
| metadatos_completos | BOOLEAN | SÍ | true si los metadatos obligatorios estaban presentes antes del intento | — | datos-abiertos.md:41 (RNF-11-D01), 58 (RN-11-D02) |

---

## 3. Reglas de negocio con impacto en datos

| ID | Regla | Impacto en esquema | Fuente |
|----|-------|--------------------|--------|
| RN-B1-019 | `datos.gov.co` NO es archivo digital; la Alcaldía mantiene sus obligaciones de TRD independientemente de lo publicado allí. | `Dataset.federado_datos_gov` NO implica archivado; el estado de archivo se gestiona en módulo 12. No crear FK directa entre Dataset y TRD. | datos-abiertos.md:47, Res. 1519/2020 Anexo 4 §4.1 |
| RN-B1-026 | Datos abiertos en formatos abiertos bajo licencia abierta. | `LicenciaDatos.permite_reutilizacion = true` es obligatorio; CHECK en Dataset que su licencia_id referencie una licencia con ese flag. | datos-abiertos.md:48, Res. 1519/2020 Anexo 4 §4.2 |
| RN (Ley 1753) | Publicar datos en formatos y metadatos definidos por MinTIC. | Los campos de RF-B1-091 son NOT NULL en Dataset; la tabla DatasetMetadata permite extender sin romper el esquema principal cuando MinTIC amplíe requerimientos. | datos-abiertos.md:49, Ley 1753/2015 Art.45-d |
| RN-11-D01 | Control de frescura: superada la frecuencia declarada + margen → dataset se marca 'desactualizado' y se alerta al responsable. | `Dataset.estado` puede tomar valor 'desactualizado'. `AlertaFrescura` registra cada evento. El margen de gracia (ej. 5 días) [AMBIGUO: no especificado en el documento] debe configurarse. | datos-abiertos.md:57, Res. 1519/2020 Anexo 4 §4.2; Ley 1753 Art.45-d |
| RN-11-D02 | Validación previa a federar: el archivo debe ser bien formado (CSV/JSON/XML) y tener metadatos obligatorios completos; si no, no se federa. | `FederacionExterna.archivo_validado` y `FederacionExterna.metadatos_completos` deben ser ambos `true` para que `resultado = 'exitosa'` sea posible. Implementar CHECK o trigger. | datos-abiertos.md:58, Res. 1519/2020 Anexo 4 |
| RF-B1-090 / RNF-B3-039 | ≥90% de documentos en formatos abiertos y procesables por máquina. | `FormatoAbierto.procesable_maquina` permite calcular la métrica de cumplimiento con una consulta simple. | datos-abiertos.md:35 |
| UC-B1-008 | El ciudadano accede al catálogo y descarga sin autenticación ni costo. | `Dataset.estado = 'publicado'` debe ser condición de visibilidad pública. No se requiere autenticación para leer. | datos-abiertos.md:64 |
| HU-11-D01 | Al actualizar un dataset se versiona conservando la versión previa y se reinicia el contador de frescura. | Se crea un registro en `DatasetVersion` antes de actualizar `Dataset.ultima_actualizacion`. La alerta `AlertaFrescura.resuelta = true` se marca al actualizar. | datos-abiertos.md:80 |

---

## 4. Cardinalidades y relaciones

| Relación | Entidad A | Card. A | Card. B | Entidad B | Notas | Fuente |
|----------|-----------|---------|---------|-----------|-------|--------|
| tiene metadatos principales | Dataset | 1 | 1 | DatasetMetadata (extensión) | Atributos obligatorios son columnas directas en Dataset; DatasetMetadata es para campos adicionales dinámicos. | RF-B1-091 |
| tiene versiones | Dataset | 1 | 0..N | DatasetVersion | Un dataset puede tener 0 versiones históricas al crearse; N al actualizarse. | HU-11-D01, RF-11-D01 |
| usa formato | Dataset | N | 1 | FormatoAbierto | Un dataset se publica en un formato principal; puede publicarse en múltiples [AMBIGUO: RF-B1-019 menciona "CSV/JSON/XML" como opciones, HU-B2-018 dice "CSV, JSON y XML" simultáneamente → posible relación N:N entre Dataset y FormatoAbierto]. | RF-B1-019, HU-B2-018 |
| tiene licencia | Dataset | N | 1 | LicenciaDatos | Múltiples datasets pueden compartir la misma licencia. | RF-B1-091, RN-B1-026 |
| vinculado a activo | Dataset | N | 0..1 | RegistroActivoInformacion | Un dataset puede no estar vinculado a un activo; un activo puede tener varios datasets asociados. | §7, RF-B1-020 |
| genera alertas | Dataset | 1 | 0..N | AlertaFrescura | Un dataset puede acumular historial de alertas de frescura. | RF-11-D01, RN-11-D01 |
| tiene intentos de federación | Dataset | 1 | 0..N | FederacionExterna | Se registra cada intento; el último exitoso determina `Dataset.federado_datos_gov`. | RN-11-D02, HU-11-D02 |
| tiene licencia | RegistroActivoInformacion | N | 1 | LicenciaDatos | El activo también tiene licencia asignada. | §7, RF-B1-020 |
| tiene formato | DatasetVersion | N | 1 | FormatoAbierto | La versión puede conservar el formato o cambiarlo. [INFERIDO] | HU-11-D01 |

> **[AMBIGUO — Dataset vs. FormatoAbierto]:** HU-B2-018 (datos-abiertos.md:72) dice el dataset "se publica en CSV, JSON y XML" simultáneamente, lo que sugiere relación N:N (`DatasetFormato` tabla puente). RF-B1-019 y RF-B1-090 listan formatos opcionales. Pendiente confirmar si es uno-a-muchos o muchos-a-muchos antes de fijar el esquema físico.

---

## 5. Jerarquías ISA / polimorfismo

No se identifican jerarquías ISA directas dentro de las entidades del módulo 11.

**Polimorfismo potencial — RegistroActivoInformacion:**
Este activo puede originarse en distintos módulos (02 Transparencia, 11 Datos Abiertos, 12 Gestión Documental). El campo `modulo_origen` [INFERIDO] actúa como discriminador. Si el cruce entre módulos exige atributos específicos por origen, se evaluará estrategia supertype-subtype en la consolidación global. Por ahora se modela con discriminador simple y `CHECK (modulo_origen IN ('transparencia', 'datos_abiertos', 'gestion_documental'))`.

---

## 6. Normativa citada

| Norma | Artículo / Sección | Impacto | Fuente en documento |
|-------|--------------------|---------|---------------------|
| Resolución 1519/2020 MinTIC | Anexo 4, §4.1 | datos.gov.co no es archivo digital; TRD obligatoria independiente | datos-abiertos.md:47 (RN-B1-019) |
| Resolución 1519/2020 MinTIC | Anexo 4, §4.2 | Formatos y licencias abiertos; control de frescura | datos-abiertos.md:48 (RN-B1-026), 57 (RN-11-D01), 81 (HU-11-D02) |
| Ley 1753/2015 (Plan Nacional de Desarrollo) | Art. 45-d | Publicar datos en formatos y metadatos definidos por MinTIC | datos-abiertos.md:49 (RN Ley 1753), 57 (RN-11-D01) |

---

## 7. Inferencias [INFERIDO]

| ID | Inferencia | Justificación | Línea de origen |
|----|-----------|---------------|-----------------|
| INF-01 | `DatasetVersion` como entidad separada | HU-11-D01 exige "conservar la versión previa al actualizar"; sin tabla aparte se pierden snapshots históricos. | datos-abiertos.md:80 |
| INF-02 | `LicenciaDatos` como entidad separada | RN-B1-026 y RF-B1-020 mencionan "aprobar y publicar licencia"; la licencia es un objeto con ciclo de vida propio reutilizable entre datasets y activos. | datos-abiertos.md:18, 48 |
| INF-03 | `AlertaFrescura` como entidad separada | RN-11-D01 exige registrar alertas y notificar al responsable; se necesita historial de alertas, no solo un flag. | datos-abiertos.md:57 |
| INF-04 | `FederacionExterna` como entidad separada | RN-11-D02 y HU-B2-018 requieren registrar resultado de cada intento de federación con detalle de error; no es atributo simple. | datos-abiertos.md:58, 72 |
| INF-05 | `DatasetMetadata` como tabla de extensión clave-valor | Ley 1753 Art.45-d delega en MinTIC definir metadatos; la lista puede ampliarse por norma. La tabla de extensión protege el esquema principal de migraciones frecuentes. | datos-abiertos.md:49 |
| INF-06 | `Dataset.estado` ENUM con valor 'desactualizado' | RN-11-D01 exige "se marca desactualizado"; el estado debe persistirse en la entidad. | datos-abiertos.md:57, 80 |
| INF-07 | Margen de gracia en frescura [AMBIGUO] | RF-11-D01 menciona "35 días" para frecuencia mensual; no queda claro si el margen es fijo (5 días) o configurable. Pendiente aclaración. | datos-abiertos.md:28 |
| INF-08 | Relación N:N Dataset–FormatoAbierto [AMBIGUO] | HU-B2-018 publica simultáneamente en CSV, JSON y XML; podría requerir tabla puente `DatasetFormato`. Pendiente confirmar con el equipo. | datos-abiertos.md:72 |
| INF-09 | `RegistroActivoInformacion.modulo_origen` como discriminador | El activo es CANDIDATA-COMPARTIDA entre módulos 02, 11 y 12; se necesita identificar su origen para integridad y consultas cruzadas. | datos-abiertos.md:91 (§8), RF-B1-020 |
| INF-10 | `DatasetVersion.creado_por` FK a Usuario | UC-B2-013 identifica al actor "Administrador del portal" como quien publica; se necesita trazabilidad de auditoría. | datos-abiertos.md:65 |

---

## Vacíos detectados (para investigación posterior)

| ID | Vacío | Pregunta | Línea de origen |
|----|-------|----------|-----------------|
| VAC-01 | Margen de gracia de frescura | ¿El margen sobre la frecuencia declarada es configurable o fijo? ¿5 días para mensual, proporcional para otras? | datos-abiertos.md:28 [A-04 implícito] |
| VAC-02 | Multiplicidad de formatos por dataset | ¿Un dataset se publica en UN formato o en VARIOS simultáneamente (CSV + JSON + XML)? Determina si la FK es directa o se necesita tabla puente. | datos-abiertos.md:72 (HU-B2-018) |
| VAC-03 | Capacidad de actualización automática hacia datos.gov.co | ¿Es proceso manual o automatizado? Impacta si `FederacionExterna` registra solo eventos manuales o también jobs automáticos. | datos-abiertos.md:96 [PREGUNTA ABIERTA] |
| VAC-04 | Catálogo de categorías | ¿Las categorías de datasets son un catálogo cerrado (FK a tabla Categoria) o texto libre? | datos-abiertos.md:85, HU-B1-008 |
| VAC-05 | Activos de información ya cargados | ¿La Alcaldía tiene el inventario de activos precargado? Impacta en datos iniciales (seed) del RegistroActivoInformacion. | datos-abiertos.md:96 [PREGUNTA ABIERTA] |
| VAC-06 | Entidad Usuario / Responsable de datos | No se define en este módulo. El campo `AlertaFrescura.responsable_notificado` y `DatasetVersion.creado_por` requieren FK a una entidad de usuarios del sistema (módulo de administración). | datos-abiertos.md:28, 65 |

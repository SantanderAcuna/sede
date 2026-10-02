# Diccionario de Datos — Tablas de los Módulos 01 y 02

> **Convención:**
> - PK = Primary Key; FK = Foreign Key; UK = Unique Key; CK = Check Constraint.
> - Tipos según PostgreSQL 15+ (extensiones `pgcrypto`, `uuid-ossp`, `pg_trgm`, `unaccent`).
> - Para cada tabla: propósito, forma normal (todas BCNF), columnas, índices, relaciones, reglas de negocio.

---

## Módulo 01 — Estructura-Identidad

### Tabla: `dependencia`

**Propósito:** organigrama del Distrito (secretarías, oficinas, gerencias). Modelo jerárquico auto-referencial.
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval('dependencia_id_seq')` | PK | Identificador surrogate |
| `codigo` | VARCHAR(20) | No | — | UK | Código único de la dependencia (ej: `SEC-GRAL`, `OF-TIC`) |
| `nombre` | VARCHAR(200) | No | — | — | Nombre oficial |
| `descripcion` | TEXT | Sí | NULL | — | Descripción de funciones |
| `dependencia_padre_id` | BIGINT | Sí | NULL | FK→`dependencia.id` | Padre en el organigrama |
| `responsable_id` | BIGINT | Sí | NULL | FK→`servidor_publico.id` | Jefe actual |
| `telefono` | VARCHAR(30) | Sí | NULL | — | Con prefijo +57 |
| `correo` | VARCHAR(200) | Sí | NULL | — | Correo institucional |
| `nivel` | ENUM | No | — | CK (`'secretaria'`,`'oficina'`,`'gerencia'`,`'direccion'`,`'subdireccion'`) | Nivel jerárquico |
| `activo` | BOOLEAN | No | `true` | — | Si está vigente en el organigrama |
| `created_at` | TIMESTAMP | No | `now()` | — | Auditoría |
| `updated_at` | TIMESTAMP | No | `now()` | — | Auditoría |

**Índices:**
- `pk_dependencia (id)` — PRIMARY KEY (B-tree implícito)
- `uk_dependencia_codigo (codigo)` — UNIQUE
- `idx_dependencia_padre (dependencia_padre_id)` — B-tree — Búsqueda de hijos
- `idx_dependencia_activo (activo) WHERE activo = true` — B-tree parcial — Solo activas en listados públicos

**Relaciones:**
- FK `dependencia_padre_id → dependencia.id` ON DELETE RESTRICT ON UPDATE CASCADE
- FK `responsable_id → servidor_publico.id` ON DELETE SET NULL ON UPDATE CASCADE
- 1:N → `servidor_publico.dependencia_id`
- 1:N → `documento` (vía `documento_dependencia`)

**Reglas de negocio:**
- `BR-DEP-01`: un nodo no puede ser su propio ancestro (trigger de validación).
- `BR-DEP-02`: la profundidad máxima del árbol es 5 niveles.
- `BR-DEP-03`: el `responsable_id` debe ser un servidor activo de la misma dependencia (validación en código de aplicación).

---

### Tabla: `servidor_publico`

**Propósito:** directorio de servidores públicos del Distrito (vinculado a SIGEP).
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | Surrogate |
| `dependencia_id` | BIGINT | No | — | FK→`dependencia.id` | Dependencia actual |
| `codigo_sigep` | VARCHAR(50) | Sí | NULL | UK | Código en SIGEP (nullable si aún no sincronizado) |
| `numero_identificacion` | VARCHAR(20) | No | — | UK | Cédula (dato sensible — protegido por RLS) |
| `nombres` | VARCHAR(100) | No | — | — | — |
| `apellidos` | VARCHAR(100) | No | — | — | — |
| `cargo` | VARCHAR(200) | No | — | — | Cargo actual |
| `correo_institucional` | VARCHAR(200) | Sí | NULL | — | Solo institucional, NO personal |
| `extension` | VARCHAR(20) | Sí | NULL | — | Extensión telefónica |
| `fecha_ingreso` | DATE | No | — | — | — |
| `fecha_salida` | DATE | Sí | NULL | — | Si aplica |
| `estado` | ENUM | No | `'activo'` | CK (`'activo'`,`'inactivo'`,`'comision'`,`'desvinculado'`) | Estado actual |
| `created_at` | TIMESTAMP | No | `now()` | — | — |
| `updated_at` | TIMESTAMP | No | `now()` | — | — |
| `deleted_at` | TIMESTAMP | Sí | NULL | — | Soft-delete |

**Índices:**
- `pk_servidor_publico (id)` — PK
- `uk_servidor_publico_sigep (codigo_sigep)` — UK
- `uk_servidor_publico_identificacion (numero_identificacion)` — UK
- `idx_servidor_publico_dependencia (dependencia_id)` — B-tree
- `idx_servidor_publico_apellidos (apellidos, nombres)` — B-tree — Búsqueda ordenada
- `idx_servidor_publico_estado (estado) WHERE estado = 'activo'` — Parcial para directorio público
- `idx_servidor_publico_trgm (nombres gin_trgm_ops, apellidos gin_trgm_ops)` — GIN con `pg_trgm` para búsqueda tolerante

**Relaciones:**
- FK `dependencia_id → dependencia.id` ON DELETE RESTRICT
- 1:1 opcional → `usuario.vinculado_a`

**Reglas de negocio:**
- `BR-SP-01`: el directorio público SOLO expone nombre, cargo, dependencia, correo institucional y extensión. NO se publica cédula, dirección personal ni datos sensibles (RN-07, Ley 1581/2012).
- `BR-SP-02`: la sincronización con SIGEP actualiza el campo `estado` y fechas en ≤24 horas hábiles (RF-02-006).
- `BR-SP-03`: soft-delete con `deleted_at`; las queries públicas filtran `WHERE deleted_at IS NULL AND estado = 'activo'`.

---

### Tabla: `escala_salarial`

**Propósito:** escala salarial por nivel, grado y vigencia.
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | Surrogate |
| `nivel` | VARCHAR(10) | No | — | parte de UK | Nivel salarial |
| `grado` | VARCHAR(10) | No | — | parte de UK | Grado salarial |
| `salario_basico` | DECIMAL(15,2) | No | — | CK `>= 0` | Salario mensual |
| `vigencia_desde` | DATE | No | — | parte de UK | Inicio de vigencia |
| `vigencia_hasta` | DATE | Sí | NULL | — | Fin (NULL = vigente) |
| `created_at` | TIMESTAMP | No | `now()` | — | — |

**Índices:**
- `pk_escala (id)` — PK
- `uk_escala_nivel_grado_vigencia (nivel, grado, vigencia_desde)` — UK
- `idx_escala_vigente (vigencia_hasta) WHERE vigencia_hasta IS NULL` — Parcial para consulta rápida

**Relaciones:** ninguna directa.

**Reglas de negocio:**
- `BR-ES-01`: la PK compuesta evita duplicados por vigencia.

---

### Tabla: `menu_item`

**Propósito:** menú principal de navegación (estructura jerárquica).
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `padre_id` | BIGINT | Sí | NULL | FK→`menu_item.id` | Padre (NULL = nivel raíz) |
| `slug` | VARCHAR(100) | No | — | UK | Identificador único semántico |
| `etiqueta` | VARCHAR(100) | No | — | — | Texto visible |
| `ruta` | VARCHAR(500) | Sí | NULL | — | Ruta interna o URL externa |
| `descripcion` | TEXT | Sí | NULL | — | Tooltip / descripción accesible |
| `orden` | INTEGER | No | `0` | CK `>= 0` | Orden de aparición |
| `visible` | BOOLEAN | No | `true` | — | Si se muestra en el sitio público |
| `tipo` | ENUM | No | `'interno'` | CK (`'interno'`,`'externo'`,`'ancla'`,`'modal'`) | Tipo de enlace |
| `created_at` | TIMESTAMP | No | `now()` | — | — |
| `updated_at` | TIMESTAMP | No | `now()` | — | — |

**Índices:**
- `pk_menu_item (id)` — PK
- `uk_menu_item_slug (slug)` — UK
- `idx_menu_item_padre (padre_id, orden)` — B-tree — Render recursivo

**Relaciones:**
- FK `padre_id → menu_item.id` ON DELETE CASCADE
- N:M → `rol` vía `rol_menu`

---

### Tabla: `top_bar`

**Propósito:** configuración de la barra superior GOV.CO (1 fila, versionada).
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `logo_path` | VARCHAR(500) | No | — | — | Path al logo GOV.CO en S3 |
| `url_govco` | VARCHAR(500) | No | `https://www.gov.co/home/` | — | URL destino |
| `altura_px` | INTEGER | No | `56` | CK `>= 44` | Altura (WCAG: ≥44 px touch) |
| `idioma_default` | VARCHAR(5) | No | `'es-CO'` | — | Locale por defecto |
| `created_at` | TIMESTAMP | No | `now()` | — | — |

### Tabla: `top_bar_item`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `top_bar_id` | BIGINT | No | — | FK→`top_bar.id` | — |
| `etiqueta` | VARCHAR(100) | No | — | — | — |
| `url` | VARCHAR(500) | No | — | — | — |
| `orden` | INTEGER | No | `0` | — | — |

### Tabla: `footer`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `nombre_autoridad` | TEXT | No | — | — | "Alcaldía Distrital de Santa Marta" |
| `nit` | VARCHAR(20) | No | — | — | "891.780.009-4" |
| `direccion` | TEXT | No | — | — | — |
| `codigo_postal` | VARCHAR(10) | Sí | NULL | — | — |
| `municipio` | VARCHAR(100) | No | — | — | — |
| `departamento` | VARCHAR(100) | No | — | — | "Magdalena" |
| `horario` | TEXT | No | — | — | — |
| `commutador` | VARCHAR(30) | No | — | — | Con +57 |
| `linea_anticorrupcion` | VARCHAR(30) | Sí | NULL | — | 018000 |
| `correo_institucional` | VARCHAR(200) | No | — | — | — |
| `correo_notificaciones` | VARCHAR(200) | No | — | — | — |

### Tabla: `footer_item`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `footer_id` | BIGINT | No | — | FK→`footer.id` | — |
| `tipo` | ENUM | No | — | CK (`'red_social'`,`'politica'`,`'enlace'`,`'sitemap'`) | — |
| `etiqueta` | VARCHAR(100) | No | — | — | — |
| `url` | VARCHAR(500) | No | — | — | — |
| `icono` | VARCHAR(100) | Sí | NULL | — | Clase del icono (govco-*) |
| `orden` | INTEGER | No | `0` | — | — |

---

### Tabla: `noticia`

**Propósito:** noticias publicadas en el home y página de noticias.
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `slug` | VARCHAR(200) | No | — | UK | — |
| `titulo` | VARCHAR(200) | No | — | CK `length(titulo) <= 150` | Título (RF-01-013) |
| `descripcion_corta` | VARCHAR(300) | No | — | CK `length(descripcion_corta) <= 200` | Resumen (RF-01-013) |
| `contenido_html` | TEXT | No | — | — | HTML del cuerpo |
| `fecha_publicacion` | TIMESTAMP | No | — | — | — |
| `destacada` | BOOLEAN | No | `false` | — | Si aparece en carrusel |
| `activo` | BOOLEAN | No | `true` | — | — |
| `autor_id` | BIGINT | No | — | FK→`usuario.id` | — |
| `created_at` | TIMESTAMP | No | `now()` | — | — |
| `updated_at` | TIMESTAMP | No | `now()` | — | — |
| `deleted_at` | TIMESTAMP | Sí | NULL | — | Soft-delete |

**Índices:**
- `pk_noticia (id)` — PK
- `uk_noticia_slug (slug)` — UK
- `idx_noticia_publicadas (fecha_publicacion DESC) WHERE activo = true AND deleted_at IS NULL` — B-tree parcial
- `idx_noticia_destacadas (fecha_publicacion DESC) WHERE destacada = true AND activo = true` — Para carrusel

### Tabla: `noticia_imagen`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `noticia_id` | BIGINT | No | — | FK→`noticia.id` ON DELETE CASCADE | — |
| `archivo_path` | VARCHAR(500) | No | — | — | S3 path |
| `alt_text` | TEXT | No | — | CK `length(alt_text) > 0` | Obligatorio (WCAG 2.1 AA) |
| `ancho_px` | INTEGER | No | — | — | — |
| `alto_px` | INTEGER | No | — | — | — |
| `orden` | INTEGER | No | `0` | — | — |

### Tabla: `categoria_noticia`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `slug` | VARCHAR(100) | No | — | UK | — |
| `nombre` | VARCHAR(100) | No | — | — | — |

### Tabla: `noticia_categoria` (pivote)

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `noticia_id` | BIGINT | No | — | PK compuesta + FK | — |
| `categoria_id` | BIGINT | No | — | PK compuesta + FK | — |

---

## Módulo 02 — Transparencia

### Tabla: `subseccion_transparencia`

**Propósito:** las 10 subsecciones obligatorias de Ley 1712/2014.
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `codigo` | VARCHAR(50) | No | — | UK | `informacion-entidad`, `normativa`, ... |
| `nombre` | VARCHAR(200) | No | — | — | "Información de la entidad" |
| `descripcion` | TEXT | No | — | — | — |
| `orden` | INTEGER | No | — | CK `BETWEEN 1 AND 10` | Orden (1..10) |
| `numero_ley` | INTEGER | No | — | CK `BETWEEN 1 AND 10` | Numeral en Ley 1712 |

**Datos iniciales (seeder):**

| codigo | nombre | orden | numero_ley |
|---|---|---|---|
| `informacion-entidad` | Información de la entidad | 1 | 1 |
| `normativa` | Normativa | 2 | 2 |
| `contratacion` | Contratación | 3 | 3 |
| `planeacion` | Planeación, presupuesto e informes | 4 | 4 |
| `tramites` | Trámites | 5 | 5 |
| `participa` | Participa | 6 | 6 |
| `datos-abiertos` | Datos abiertos | 7 | 7 |
| `grupos-interes` | Grupos de interés | 8 | 8 |
| `reporte-especifico` | Obligación de reporte específico | 9 | 9 |
| `tributaria` | Información tributaria territorial | 10 | 10 |

### Tabla: `categoria_documento`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `subseccion_id` | BIGINT | No | — | FK→`subseccion_transparencia.id` | — |
| `codigo` | VARCHAR(50) | No | — | UK | — |
| `nombre` | VARCHAR(200) | No | — | — | — |
| `orden` | INTEGER | No | `0` | — | — |

### Tabla: `tipo_documento`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `codigo` | VARCHAR(20) | No | — | UK | `pdf`, `xlsx`, `csv`, `json`, `rdf`, `odf`, `html`, `zip` |
| `nombre` | VARCHAR(100) | No | — | — | "PDF (PDF/A)" |
| `mime_type` | VARCHAR(100) | No | — | — | `application/pdf` |
| `formato_abierto` | BOOLEAN | No | `false` | — | Marca para cumplimiento RN-08 |

### Tabla: `documento`

**Propósito:** cabecera de un documento de transparencia.
**Forma normal alcanzada:** BCNF.

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `slug` | VARCHAR(250) | No | — | UK | — |
| `titulo` | VARCHAR(300) | No | — | — | — |
| `descripcion` | TEXT | Sí | NULL | — | — |
| `subseccion_id` | BIGINT | No | — | FK→`subseccion_transparencia.id` | — |
| `categoria_id` | BIGINT | Sí | NULL | FK→`categoria_documento.id` | — |
| `tipo_documento_id` | BIGINT | No | — | FK→`tipo_documento.id` | — |
| `archivo_id` | BIGINT | No | — | FK→`archivo_storage.id` | Archivo vigente |
| `version_actual_id` | BIGINT | No | — | FK→`documento_version.id` | Puntero a versión publicada actual |
| `autor_id` | BIGINT | No | — | FK→`usuario.id` | — |
| `fecha_publicacion` | DATE | Sí | NULL | — | Fecha de publicación (orden cronológico) |
| `fecha_documento` | DATE | No | — | — | Fecha del documento en sí |
| `periodicidad` | ENUM | No | `'eventual'` | CK (`'anual'`,`'semestral'`,`'trimestral'`,`'mensual'`,`'eventual'`) | — |
| `estado` | ENUM | No | `'borrador'` | CK (`'borrador'`,`'revision'`,`'publicado'`,`'despublicado'`,`'archivado'`) | — |
| `destacado` | BOOLEAN | No | `false` | — | — |
| `indice_lecturabilidad` | DECIMAL(5,2) | Sí | NULL | CK `BETWEEN 0 AND 100` | Fernández-Huerta (RF-02-026) |
| `created_at` | TIMESTAMP | No | `now()` | — | — |
| `updated_at` | TIMESTAMP | No | `now()` | — | — |
| `deleted_at` | TIMESTAMP | Sí | NULL | — | Soft-delete |

**Índices:**
- `pk_documento (id)` — PK
- `uk_documento_slug (slug)` — UK
- `idx_documento_subseccion (subseccion_id, fecha_publicacion DESC)` — B-tree — Listado por subsección
- `idx_documento_categoria (categoria_id)` — B-tree
- `idx_documento_estado (estado) WHERE estado = 'publicado'` — Parcial para feed público
- `idx_documento_busqueda (to_tsvector('spanish', titulo || ' ' || descripcion))` — GIN para FTS

### Tabla: `documento_version`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `documento_id` | BIGINT | No | — | FK→`documento.id` ON DELETE CASCADE | — |
| `numero_version` | INTEGER | No | — | UK `(documento_id, numero_version)` | 1, 2, 3, ... |
| `archivo_id` | BIGINT | No | — | FK→`archivo_storage.id` | — |
| `motivo_cambio` | TEXT | Sí | NULL | — | Justificación |
| `autor_id` | BIGINT | No | — | FK→`usuario.id` | Quién subió |
| `fecha_version` | TIMESTAMP | No | `now()` | — | — |
| `version_publicada` | BOOLEAN | No | `true` | — | Si es la versión canónica actual |

### Tabla: `metadato_documento`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `documento_id` | BIGINT | No | — | FK→`documento.id` ON DELETE CASCADE | — |
| `clave` | VARCHAR(100) | No | — | UK `(documento_id, clave)` | Dublin Core |
| `valor` | TEXT | No | — | — | — |

**Claves Dublin Core estándar (sugeridas):**

| Clave | Descripción |
|---|---|
| `dc.title` | Título |
| `dc.creator` | Autor |
| `dc.subject` | Materia |
| `dc.description` | Descripción |
| `dc.publisher` | Editor (Alcaldía) |
| `dc.contributor` | Contribuyente |
| `dc.date` | Fecha |
| `dc.type` | Tipo DCMI |
| `dc.format` | Formato |
| `dc.identifier` | Identificador |
| `dc.source` | Origen |
| `dc.language` | Idioma |
| `dc.relation` | Relación |
| `dc.coverage` | Cobertura |
| `dc.rights` | Derechos |

### Tabla: `archivo_storage`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `path` | VARCHAR(500) | No | — | UK | Path en S3 |
| `nombre_original` | VARCHAR(300) | No | — | — | — |
| `mime_type` | VARCHAR(100) | No | — | — | — |
| `tamano_bytes` | BIGINT | No | — | CK `>= 0` | — |
| `hash_sha256` | CHAR(64) | No | — | UK | Hex SHA-256 |
| `uploaded_at` | TIMESTAMP | No | `now()` | — | — |
| `uploaded_by` | BIGINT | No | — | FK→`usuario.id` | — |

**Índices:**
- `pk_archivo (id)` — PK
- `uk_archivo_path (path)` — UK
- `uk_archivo_hash (hash_sha256)` — UK — Detección de duplicados

### Tabla: `busqueda_log`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `termino` | VARCHAR(500) | No | — | — | — |
| `documento_id` | BIGINT | Sí | NULL | FK→`documento.id` | Documento clickeado (nullable) |
| `fecha_busqueda` | TIMESTAMP | No | `now()` | — | — |
| `ip_origen` | INET | Sí | NULL | — | — |
| `resultados_count` | INTEGER | No | — | — | — |

**Índices:**
- `idx_busqueda_termino (termino)` — B-tree — Análisis de términos frecuentes
- `idx_busqueda_fecha (fecha_busqueda)` — B-tree

### Tabla: `documento_dependencia` (pivote)

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `documento_id` | BIGINT | No | — | PK compuesta + FK | — |
| `dependencia_id` | BIGINT | No | — | PK compuesta + FK | — |
| `rol` | ENUM | No | `'emisora'` | CK (`'emisora'`,`'revisora'`,`'aprobadora'`) | — |

### Tabla: `instrumento_gestion`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `codigo` | VARCHAR(50) | No | — | UK | `registro-activos`, `indice-clasificada`, `esquema-publicacion`, `pgd`, `trd` |
| `nombre` | VARCHAR(200) | No | — | — | — |
| `descripcion` | TEXT | No | — | — | — |
| `fecha_ultima_actualizacion` | DATE | No | — | — | — |
| `documento_id` | BIGINT | Sí | NULL | FK→`documento.id` | Documento publicado |

### Tabla: `activo_informacion`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `instrumento_id` | BIGINT | No | — | FK→`instrumento_gestion.id` | — |
| `nombre` | VARCHAR(300) | No | — | — | — |
| `descripcion` | TEXT | No | — | — | — |
| `categoria` | VARCHAR(100) | No | — | — | — |
| `formato` | VARCHAR(50) | No | — | — | — |
| `responsable` | VARCHAR(200) | No | — | — | — |
| `publica` | BOOLEAN | No | `true` | — | — |

### Tabla: `informacion_clasificada`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `instrumento_id` | BIGINT | No | — | FK→`instrumento_gestion.id` | — |
| `nombre` | VARCHAR(300) | No | — | — | — |
| `motivo_clasificacion` | TEXT | No | — | — | — |
| `fundamentacion_legal` | VARCHAR(500) | No | — | — | — |
| `fecha_clasificacion` | DATE | No | — | — | — |

### Tabla: `politica`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `codigo` | VARCHAR(50) | No | — | UK | `terminos`, `privacidad`, `cookies`, `derechos-autor`, `accesibilidad` |
| `nombre` | VARCHAR(200) | No | — | — | — |
| `contenido_html` | TEXT | No | — | — | — |
| `documento_id` | BIGINT | Sí | NULL | FK→`documento.id` | Versión PDF/A descargable |
| `tipo` | ENUM | No | — | CK (`'legal'`,`'seguridad'`,`'privacidad'`,`'institucional'`) | — |
| `vigente` | BOOLEAN | No | `true` | — | — |
| `created_at` | TIMESTAMP | No | `now()` | — | — |
| `updated_at` | TIMESTAMP | No | `now()` | — | — |

### Tabla: `ita_item`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `codigo` | VARCHAR(50) | No | — | UK | `imagen-alt`, `video-subtitulos`, `metadatos-completos`, ... |
| `titulo` | VARCHAR(300) | No | — | — | — |
| `descripcion` | TEXT | No | — | — | — |
| `criterio` | ENUM | No | — | CK (`'cumple'`,`'parcial'`,`'no_cumple'`,`'no_aplica'`) | — |
| `fuente_normativa` | VARCHAR(200) | No | — | — | "Res. 1519/2020 Anexo 2 §3.1" |
| `peso` | INTEGER | No | `1` | CK `BETWEEN 1 AND 10` | Peso en el score |

### Tabla: `ita_evaluacion`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `ita_item_id` | BIGINT | No | — | FK→`ita_item.id` | — |
| `documento_id` | BIGINT | Sí | NULL | FK→`documento.id` | Objeto evaluado (nullable) |
| `estado` | ENUM | No | — | CK (`'cumple'`,`'parcial'`,`'no_cumple'`,`'no_aplica'`) | — |
| `observacion` | TEXT | Sí | NULL | — | — |
| `fecha_evaluacion` | TIMESTAMP | No | `now()` | — | — |
| `evaluador_id` | BIGINT | Sí | NULL | FK→`usuario.id` | Automático (sistema) o manual |

### Tabla: `alerta_publicacion`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `tipo` | VARCHAR(100) | No | — | UK | `plan-accion-31ene`, `informe-gestion-31ene`, `pqrsd-trimestral`, `control-interno-semestral`, `calendario-tributario` |
| `mensaje` | TEXT | No | — | — | — |
| `documento_id` | BIGINT | Sí | NULL | FK→`documento.id` | Documento asociado |
| `dias_anticipacion` | INTEGER | No | `10` | CK `BETWEEN 1 AND 30` | Días antes del plazo |
| `activa` | BOOLEAN | No | `true` | — | — |
| `created_at` | TIMESTAMP | No | `now()` | — | — |

### Tabla: `notificacion`

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `usuario_id` | BIGINT | No | — | FK→`usuario.id` | — |
| `tipo` | VARCHAR(50) | No | — | CK (`'email'`,`'panel'`,`'sms'`) | — |
| `contenido` | TEXT | No | — | — | — |
| `fecha_envio` | TIMESTAMP | No | `now()` | — | — |
| `leida` | BOOLEAN | No | `false` | — | — |
| `fecha_lectura` | TIMESTAMP | Sí | NULL | — | — |
| `canal` | VARCHAR(50) | No | `'panel'` | — | — |

---

## `_bd` común (transversal)

### Tabla: `usuario`

(Ver detalle en `sede-electronica-doc/_bd/03-modelo-logico.md`; resumen)

| Columna | Tipo | Nulo | Default | Restricción | Descripción |
|---|---|---|---|---|---|
| `id` | BIGINT | No | `nextval(...)` | PK | — |
| `email` | VARCHAR(200) | No | — | UK | — |
| `password_hash` | VARCHAR(255) | No | — | — | Argon2id |
| `servidor_publico_id` | BIGINT | Sí | NULL | FK→`servidor_publico.id` | Vinculación opcional |
| `mfa_habilitado` | BOOLEAN | No | `false` | — | — |
| `mfa_secret` | VARCHAR(255) | Sí | NULL | — | Cifrado |
| `ultimo_acceso` | TIMESTAMP | Sí | NULL | — | — |
| `estado` | ENUM | No | `'activo'` | CK (`'activo'`,`'inactivo'`,`'bloqueado'`,`'pendiente'`) | — |
| `created_at` | TIMESTAMP | No | `now()` | — | — |
| `updated_at` | TIMESTAMP | No | `now()` | — | — |
| `deleted_at` | TIMESTAMP | Sí | NULL | — | — |

### Tabla: `rol`, `permiso`, `rol_usuario`, `sesion`, `log_auditoria`

Ver `sede-electronica-doc/_bd/03-modelo-logico.md` para detalle completo.

---

## Tablas reutilizadas de `_bd/` común

- `usuario` (gestión de usuarios del panel).
- `rol` y `permiso` (RBAC con Spatie Permission compatible).
- `sesion` (sesiones Sanctum).
- `log_auditoria` (PARTICIONADA por mes, todas las operaciones registradas).
- `notificacion` (común a alertas y comunicaciones).

---

## Resumen de cobertura de requisitos

| Requisito | Tablas que lo implementan |
|---|---|
| RF-01-001 Top bar GOV.CO | `top_bar`, `top_bar_item` |
| RF-01-002 Footer | `footer`, `footer_item` |
| RF-01-004 Logo Alcaldía | `footer.logo_path` |
| RF-01-005 Identidad visual | `top_bar`, `footer` |
| RF-01-007 Menú principal | `menu_item`, `rol_menu` |
| RF-01-013 Noticias | `noticia`, `noticia_imagen` |
| RF-02-001 10 subsecciones | `subseccion_transparencia` |
| RF-02-002 Cronología | `documento.fecha_publicacion` + índice |
| RF-02-005 Info institucional | `dependencia`, `servidor_publico`, `escala_salarial` |
| RF-02-006 Directorio SIGEP | `servidor_publico` |
| RF-02-008 Normativa | `documento` con `subseccion_id = normativa` |
| RF-02-021 Versionado | `documento` + `documento_version` |
| RF-02-022 Alertas | `alerta_publicacion` |
| RF-02-024 Hash SHA-256 | `archivo_storage.hash_sha256` |
| RF-02-027 Búsqueda FTS | `documento.fts` (índice GIN) + `busqueda_log` |
| RF-02-029 ITA | `ita_item`, `ita_evaluacion` |
| RF-02-030 Metadatos | `metadato_documento` |
| RN-03 Instrumentos | `instrumento_gestion`, `activo_informacion`, `informacion_clasificada` |
| RN-06 Autenticidad | `archivo_storage.hash_sha256` |
| RN-07 Datos personales | RLS sobre `servidor_publico` |
| RN-08 Formatos abiertos | `tipo_documento.formato_abierto` |

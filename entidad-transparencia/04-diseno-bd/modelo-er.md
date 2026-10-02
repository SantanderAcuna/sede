# Modelo Entidad-Relación (Mermaid)

> **Sintaxis:** Mermaid `erDiagram`.
> **Leyenda:** `||--o{` = uno a cero-o-muchos; `}o--||` = cero-o-muchos a uno; `}o--o{` = muchos a muchos.
> **Convención:** nombres de tabla en `snake_case` (PostgreSQL), PK = `id BIGINT` (excepto catálogos que usan PK natural).

---

## 1. Núcleo de Identidad (Módulo 01)

```mermaid
erDiagram
    DEPENDENCIA ||--o{ DEPENDENCIA : "depende_de"
    DEPENDENCIA ||--o{ SERVIDOR_PUBLICO : "tiene"
    DEPENDENCIA ||--o| USUARIO : "es_responsable"

    SERVIDOR_PUBLICO ||--o| USUARIO : "vinculado_a"
    SERVIDOR_PUBLICO }o--|| ESCALA_SALARIAL : "asignado_a"

    SERVIDOR_PUBLICO {
        bigint id PK
        bigint dependencia_id FK
        varchar codigo_sigep UK
        varchar numero_identificacion UK
        varchar nombres
        varchar apellidos
        varchar cargo
        varchar correo_institucional
        varchar extension
        date fecha_ingreso
        date fecha_salida
        enum estado
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    DEPENDENCIA {
        bigint id PK
        varchar codigo UK
        varchar nombre
        text descripcion
        bigint dependencia_padre_id FK
        bigint responsable_id FK
        varchar telefono
        varchar correo
        enum nivel
        boolean activo
        timestamp created_at
        timestamp updated_at
    }

    ESCALA_SALARIAL {
        bigint id PK
        varchar nivel
        varchar grado
        decimal salario_basico
        date vigencia_desde
        date vigencia_hasta
        timestamp created_at
    }

    USUARIO {
        bigint id PK
        varchar email UK
        varchar password_hash
        bigint servidor_publico_id FK
        boolean mfa_habilitado
        varchar mfa_secret
        timestamp ultimo_acceso
        enum estado
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
```

---

## 2. Menú e Identidad GOV.CO

```mermaid
erDiagram
    MENU_ITEM ||--o{ MENU_ITEM : "padre_de"
    MENU_ITEM ||--o{ ROL_MENU : "visible_para"
    ROL ||--o{ ROL_MENU : "tiene"

    TOP_BAR ||--o{ TOP_BAR_ITEM : "contiene"
    FOOTER ||--o{ FOOTER_ITEM : "contiene"

    MENU_ITEM {
        bigint id PK
        bigint padre_id FK
        varchar slug UK
        varchar etiqueta
        varchar ruta
        text descripcion
        integer orden
        boolean visible
        enum tipo
        timestamp created_at
    }

    ROL {
        bigint id PK
        varchar nombre UK
        text descripcion
        varchar guard_name
        timestamp created_at
    }

    ROL_MENU {
        bigint id PK
        bigint rol_id FK
        bigint menu_item_id FK
    }

    TOP_BAR {
        bigint id PK
        varchar logo_path
        varchar url_govco
        integer altura_px
        varchar idioma_default
        timestamp created_at
    }

    TOP_BAR_ITEM {
        bigint id PK
        bigint top_bar_id FK
        varchar etiqueta
        varchar url
        integer orden
    }

    FOOTER {
        bigint id PK
        text nombre_autoridad
        varchar nit
        text direccion
        varchar codigo_postal
        varchar municipio
        varchar departamento
        text horario
        text redes_sociales_json
        varchar commutador
        varchar linea_anticorrupcion
        text correo_institucional
        text correo_notificaciones
    }

    FOOTER_ITEM {
        bigint id PK
        bigint footer_id FK
        varchar tipo
        varchar etiqueta
        varchar url
        integer orden
    }
```

---

## 3. Noticias y contenido dinámico

```mermaid
erDiagram
    NOTICIA ||--o{ NOTICIA_IMAGEN : "tiene"
    NOTICIA ||--o{ NOTICIA_CATEGORIA : "clasificada_en"
    CATEGORIA_NOTICIA ||--o{ NOTICIA_CATEGORIA : "incluye"

    NOTICIA {
        bigint id PK
        varchar slug UK
        varchar titulo
        text descripcion_corta
        text contenido_html
        timestamp fecha_publicacion
        boolean destacada
        boolean activo
        bigint autor_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    NOTICIA_IMAGEN {
        bigint id PK
        bigint noticia_id FK
        varchar archivo_path
        text alt_text
        integer ancho_px
        integer alto_px
        integer orden
    }

    CATEGORIA_NOTICIA {
        bigint id PK
        varchar slug UK
        varchar nombre
    }

    NOTICIA_CATEGORIA {
        bigint noticia_id FK
        bigint categoria_id FK
    }
```

---

## 4. Transparencia — Núcleo (Módulo 02)

```mermaid
erDiagram
    SUB_SECCION_TRANSPARENCIA ||--o{ CATEGORIA_DOCUMENTO : "agrupa"
    SUB_SECCION_TRANSPARENCIA ||--o{ DOCUMENTO : "clasifica"
    CATEGORIA_DOCUMENTO ||--o{ DOCUMENTO : "subclasifica"

    DOCUMENTO ||--|| DOCUMENTO : "version_actual (auto-ref)"
    DOCUMENTO ||--o{ DOCUMENTO_VERSION : "historial"
    DOCUMENTO ||--o{ METADATO_DOCUMENTO : "tiene"
    DOCUMENTO }o--|| TIPO_DOCUMENTO : "es_de"
    DOCUMENTO ||--o| ARCHIVO_STORAGE : "almacenado_en"
    DOCUMENTO ||--o{ BUSQUEDA_LOG : "consultado"

    DOCUMENTO }o--o{ DEPENDENCIA : "originado_por (N:M)"
    DEPENDENCIA ||--o{ DOCUMENTO_DEPENDENCIA : "asocia"
    DOCUMENTO ||--o{ DOCUMENTO_DEPENDENCIA : "asocia"

    SUB_SECCION_TRANSPARENCIA {
        bigint id PK
        varchar codigo UK
        varchar nombre
        text descripcion
        integer orden
        integer numero_ley
    }

    CATEGORIA_DOCUMENTO {
        bigint id PK
        bigint subseccion_id FK
        varchar codigo UK
        varchar nombre
        integer orden
    }

    TIPO_DOCUMENTO {
        bigint id PK
        varchar codigo UK
        varchar nombre
        varchar mime_type
        boolean formato_abierto
    }

    DOCUMENTO {
        bigint id PK
        varchar slug UK
        varchar titulo
        text descripcion
        bigint subseccion_id FK
        bigint categoria_id FK
        bigint tipo_documento_id FK
        bigint archivo_id FK
        bigint version_actual_id FK
        bigint autor_id FK
        date fecha_publicacion
        date fecha_documento
        enum periodicidad
        enum estado
        boolean destacado
        integer indice_lecturabilidad
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    DOCUMENTO_VERSION {
        bigint id PK
        bigint documento_id FK
        integer numero_version
        bigint archivo_id FK
        text motivo_cambio
        bigint autor_id FK
        timestamp fecha_version
        boolean version_publicada
    }

    METADATO_DOCUMENTO {
        bigint id PK
        bigint documento_id FK
        varchar clave
        text valor
    }

    ARCHIVO_STORAGE {
        bigint id PK
        varchar path UK
        varchar nombre_original
        varchar mime_type
        bigint tamano_bytes
        varchar hash_sha256
        timestamp uploaded_at
        bigint uploaded_by FK
    }

    BUSQUEDA_LOG {
        bigint id PK
        varchar termino
        bigint documento_id FK
        timestamp fecha_busqueda
        varchar ip_origen
        integer resultados_count
    }

    DOCUMENTO_DEPENDENCIA {
        bigint documento_id FK
        bigint dependencia_id FK
        enum rol
    }
```

---

## 5. Transparencia — Instrumentos y Políticas

```mermaid
erDiagram
    INSTRUMENTO_GESTION ||--o{ ACTIVO_INFORMACION : "incluye"
    INSTRUMENTO_GESTION ||--o{ INFORMACION_CLASIFICADA : "clasifica"

    POLITICA ||--o| DOCUMENTO : "vinculada"

    INSTRUMENTO_GESTION {
        bigint id PK
        varchar codigo UK
        varchar nombre
        text descripcion
        date fecha_ultima_actualizacion
        bigint documento_id FK
    }

    ACTIVO_INFORMACION {
        bigint id PK
        bigint instrumento_id FK
        varchar nombre
        text descripcion
        varchar categoria
        varchar formato
        varchar responsable
        boolean publica
    }

    INFORMACION_CLASIFICADA {
        bigint id PK
        bigint instrumento_id FK
        varchar nombre
        text motivo_clasificacion
        varchar fundamentacion_legal
        date fecha_clasificacion
    }

    POLITICA {
        bigint id PK
        varchar codigo UK
        varchar nombre
        text contenido_html
        bigint documento_id FK
        enum tipo
        boolean vigente
        timestamp created_at
    }
```

---

## 6. Tablero ITA y Alertas

```mermaid
erDiagram
    ITA_ITEM ||--o{ ITA_EVALUACION : "evaluado_en"

    ALERTA_PUBLICACION ||--o{ USUARIO : "dirigida_a"
    ALERTA_PUBLICACION }o--o| DOCUMENTO : "sobre"

    NOTIFICACION ||--o{ USUARIO : "para"

    ITA_ITEM {
        bigint id PK
        varchar codigo UK
        varchar titulo
        text descripcion
        enum criterio
        enum fuente_normativa
        integer peso
    }

    ITA_EVALUACION {
        bigint id PK
        bigint ita_item_id FK
        bigint documento_id FK
        enum estado
        text observacion
        timestamp fecha_evaluacion
        bigint evaluador_id FK
    }

    ALERTA_PUBLICACION {
        bigint id PK
        varchar tipo UK
        text mensaje
        bigint documento_id FK
        integer dias_anticipacion
        boolean activa
        timestamp created_at
    }

    NOTIFICACION {
        bigint id PK
        bigint usuario_id FK
        varchar tipo
        text contenido
        timestamp fecha_envio
        boolean leida
        timestamp fecha_lectura
        varchar canal
    }
```

---

## 7. Auditoría y RBAC (`_bd` común)

```mermaid
erDiagram
    USUARIO ||--o{ SESION : "inicia"
    USUARIO ||--o{ ROL_USUARIO : "tiene"
    ROL ||--o{ ROL_USUARIO : "asignado_a"
    ROL ||--o{ PERMISO : "concede"

    USUARIO ||--o{ LOG_AUDITORIA : "genera"

    LOG_AUDITORIA {
        bigint id PK
        bigint usuario_id FK
        varchar accion
        varchar recurso
        bigint recurso_id
        jsonb cambios
        varchar ip_origen
        text user_agent
        timestamp created_at
    }

    SESION {
        bigint id PK
        varchar id_sesion UK
        bigint usuario_id FK
        varchar ip_origen
        text user_agent
        timestamp created_at
        timestamp last_activity
        timestamp expires_at
    }

    ROL_USUARIO {
        bigint usuario_id FK
        bigint rol_id FK
    }

    PERMISO {
        bigint id PK
        bigint rol_id FK
        varchar nombre
        varchar recurso
        text descripcion
    }
```

---

## 8. Vistas materializadas (para performance)

```sql
-- Documentos por subsección, listos para servir en /api/v1/transparencia/subseccion/{slug}
CREATE MATERIALIZED VIEW mv_documentos_subseccion AS
SELECT
  d.id,
  d.slug,
  d.titulo,
  d.descripcion,
  d.fecha_publicacion,
  d.periodicidad,
  d.estado,
  st.codigo AS subseccion_codigo,
  st.nombre AS subseccion_nombre,
  cd.codigo AS categoria_codigo,
  td.codigo AS tipo_codigo,
  a.path AS archivo_path,
  a.hash_sha256,
  d.indice_lecturabilidad,
  to_tsvector('spanish', coalesce(d.titulo, '') || ' ' || coalesce(d.descripcion, '')) AS fts
FROM documento d
JOIN subseccion_transparencia st ON d.subseccion_id = st.id
LEFT JOIN categoria_documento cd ON d.categoria_id = cd.id
JOIN tipo_documento td ON d.tipo_documento_id = td.id
JOIN archivo_storage a ON d.archivo_id = a.id
WHERE d.deleted_at IS NULL
  AND d.estado = 'publicado';

CREATE UNIQUE INDEX idx_mv_doc_subsec ON mv_documentos_subseccion (id);
CREATE INDEX idx_mv_doc_subsec_subsec ON mv_documentos_subseccion (subseccion_codigo);
CREATE INDEX idx_mv_doc_subsec_fts ON mv_documentos_subseccion USING GIN (fts);

-- Servidores por dependencia
CREATE MATERIALIZED VIEW mv_servidor_publico AS
SELECT
  sp.id,
  sp.codigo_sigep,
  sp.nombres,
  sp.apellidos,
  sp.cargo,
  sp.correo_institucional,
  sp.extension,
  d.codigo AS dependencia_codigo,
  d.nombre AS dependencia_nombre,
  sp.estado
FROM servidor_publico sp
JOIN dependencia d ON sp.dependencia_id = d.id
WHERE sp.deleted_at IS NULL
  AND sp.estado = 'activo';

CREATE UNIQUE INDEX idx_mv_serv ON mv_servidor_publico (id);
CREATE INDEX idx_mv_serv_dep ON mv_servidor_publico (dependencia_codigo);
```

---

## 9. Resumen de cardinalidades clave

| Relación | Tipo | Justificación |
|---|---|---|
| `dependencia` → `servidor_publico` | 1:N | Una dependencia agrupa N servidores |
| `dependencia` → `dependencia` | 1:N (auto-ref) | Organigrama jerárquico (secretaría → oficina) |
| `subseccion_transparencia` → `categoria_documento` | 1:N | Una subsección agrupa N categorías |
| `subseccion_transparencia` → `documento` | 1:N | Una subsección contiene N documentos |
| `documento` → `documento_version` | 1:N | Historial de versiones |
| `documento` → `metadato_documento` | 1:N | Metadatos Dublin Core (pares clave-valor) |
| `documento` ↔ `dependencia` | N:M | Un documento puede ser de varias dependencias |
| `usuario` ↔ `rol` | N:M | Un usuario tiene N roles; un rol tiene N usuarios |
| `menu_item` → `rol_menu` → `rol` | N:M | Visibilidad de menú por rol |
| `alerta_publicacion` → `usuario` | N:M (implícita por notificación) | Alertas dirigidas a N usuarios |

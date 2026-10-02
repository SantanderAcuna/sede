# Índices y Rendimiento

> **Marco:** PostgreSQL 15+ (ADR-003), `EXPLAIN ANALYZE` como método de verificación, `pg_stat_statements` para monitoreo.
> **Objetivo:** garantizar que las queries críticas de la API cumplan los RNF de rendimiento.

---

## 1. Queries críticas y sus planes de ejecución esperados

### QC-01 — Listado de documentos por subsección (RF-02-001)

```sql
SELECT d.id, d.slug, d.titulo, d.fecha_publicacion, d.periodicidad
FROM documento d
WHERE d.subseccion_id = $1
  AND d.estado = 'publicado'
  AND d.deleted_at IS NULL
ORDER BY d.fecha_publicacion DESC
LIMIT 20 OFFSET $2;
```

**Índice requerido:**
```sql
CREATE INDEX idx_documento_subseccion_publicado
  ON documento (subseccion_id, fecha_publicacion DESC)
  WHERE estado = 'publicado' AND deleted_at IS NULL;
```

**Plan esperado:** Index Scan Backward usando el índice compuesto parcial. Costo esperado: O(log n + LIMIT). < 5 ms para 100k documentos.

---

### QC-02 — Búsqueda full-text con tolerancia ortográfica (RF-02-027)

```sql
SELECT d.id, d.titulo, ts_rank(d.fts, plainto_tsquery('spanish', $1)) AS rank
FROM documento d
WHERE d.fts @@ plainto_tsquery('spanish', $1)
ORDER BY rank DESC
LIMIT 20;
```

**Índice requerido:**
```sql
-- Columna FTS generada
ALTER TABLE documento
  ADD COLUMN fts tsvector
  GENERATED ALWAYS AS (
    to_tsvector('spanish', unaccent(coalesce(titulo, '') || ' ' || coalesce(descripcion, '')))
  ) STORED;

CREATE INDEX idx_documento_fts ON documento USING GIN (fts);

-- Para tolerancia ortográfica en autocompletado
CREATE INDEX idx_documento_titulo_trgm ON documento USING GIN (titulo gin_trgm_ops);
```

**Plan esperado:** GIN Bitmap Index Scan. < 50 ms para 100k documentos con 3 keywords.

---

### QC-03 — Directorio público (RF-02-006)

```sql
SELECT sp.id, sp.nombres, sp.apellidos, sp.cargo, sp.extension,
       d.nombre AS dependencia_nombre
FROM servidor_publico sp
JOIN dependencia d ON sp.dependencia_id = d.id
WHERE sp.estado = 'activo'
  AND sp.deleted_at IS NULL
  AND d.codigo = $1
ORDER BY sp.apellidos, sp.nombres
LIMIT 50;
```

**Índice requerido:**
```sql
CREATE INDEX idx_servidor_publico_directorio
  ON servidor_publico (dependencia_id, apellidos, nombres)
  WHERE estado = 'activo' AND deleted_at IS NULL;

CREATE INDEX idx_dependencia_codigo ON dependencia (codigo);
```

**Plan esperado:** Nested Loop con Index Scan. < 10 ms.

---

### QC-04 — Versión actual de un documento (RF-02-021)

```sql
SELECT d.id, d.slug, d.titulo, d.descripcion, dv.archivo_id,
       a.path, a.hash_sha256
FROM documento d
JOIN documento_version dv ON d.version_actual_id = dv.id
JOIN archivo_storage a ON dv.archivo_id = a.id
WHERE d.slug = $1
  AND d.deleted_at IS NULL
LIMIT 1;
```

**Plan esperado:** Nested Loop con búsqueda por PK. < 2 ms (PK lookup).

---

### QC-05 — Noticias para home (RF-01-013)

```sql
SELECT n.id, n.slug, n.titulo, n.descripcion_corta, n.fecha_publicacion,
       ni.archivo_path, ni.alt_text
FROM noticia n
LEFT JOIN noticia_imagen ni ON ni.noticia_id = n.id AND ni.orden = 0
WHERE n.activo = true
  AND n.deleted_at IS NULL
ORDER BY n.fecha_publicacion DESC
LIMIT 6;
```

**Índice requerido:**
```sql
CREATE INDEX idx_noticia_home
  ON noticia (fecha_publicacion DESC)
  WHERE activo = true AND deleted_at IS NULL;
```

**Plan esperado:** Index Scan Backward + Nested Loop. < 5 ms.

---

### QC-06 — Auditoría por usuario (RF-12 gestión contenidos)

```sql
SELECT id, accion, recurso, recurso_id, cambios, created_at
FROM log_auditoria
WHERE usuario_id = $1
  AND created_at >= $2
ORDER BY created_at DESC
LIMIT 100;
```

**Índice requerido:**
```sql
CREATE INDEX idx_log_auditoria_usuario_fecha
  ON log_auditoria (usuario_id, created_at DESC);
```

---

### QC-07 — Menú de navegación (RF-01-007)

```sql
SELECT id, padre_id, slug, etiqueta, ruta, orden
FROM menu_item
WHERE visible = true
ORDER BY orden;
```

**Índice requerido:**
```sql
CREATE INDEX idx_menu_item_visible ON menu_item (orden) WHERE visible = true;
```

---

## 2. Resumen de índices por tabla

### Tabla `documento` (la más consultada)

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_documento` | B-tree | PK |
| `uk_documento_slug` | B-tree UNIQUE | URL canónica |
| `idx_documento_subseccion_publicado` | B-tree (parcial) | QC-01 |
| `idx_documento_categoria` | B-tree | QC-08 (filtro por categoría) |
| `idx_documento_estado` | B-tree (parcial) | Listado público |
| `idx_documento_fts` | GIN | QC-02 |
| `idx_documento_titulo_trgm` | GIN pg_trgm | Autocompletado |

### Tabla `servidor_publico`

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_servidor_publico` | B-tree | PK |
| `uk_servidor_publico_sigep` | B-tree UNIQUE | Sync SIGEP |
| `uk_servidor_publico_identificacion` | B-tree UNIQUE | Identidad |
| `idx_servidor_publico_dependencia` | B-tree | QC-03 |
| `idx_servidor_publico_apellidos` | B-tree | Orden |
| `idx_servidor_publico_directorio` | B-tree (parcial) | QC-03 |
| `idx_servidor_publico_trgm` | GIN pg_trgm | Búsqueda tolerante |

### Tabla `documento_version`

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_documento_version` | B-tree | PK |
| `uk_documento_version_doc_numero` | B-tree UNIQUE | (documento_id, numero_version) |
| `idx_documento_version_actual` | B-tree (parcial) | Versión publicada actual |

### Tabla `archivo_storage`

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_archivo` | B-tree | PK |
| `uk_archivo_path` | B-tree UNIQUE | Lookup por path |
| `uk_archivo_hash` | B-tree UNIQUE | Deduplicación |

### Tabla `menu_item`

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_menu_item` | B-tree | PK |
| `uk_menu_item_slug` | B-tree UNIQUE | URL |
| `idx_menu_item_padre` | B-tree | Render recursivo |
| `idx_menu_item_visible` | B-tree (parcial) | QC-07 |

### Tabla `log_auditoria` (PARTICIONADA)

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_log_auditoria` | B-tree | PK (en cada partición) |
| `idx_log_auditoria_usuario_fecha` | B-tree | QC-06 |
| `idx_log_auditoria_recurso` | B-tree | Búsqueda por recurso |
| `idx_log_auditoria_fecha` | B-tree | Cleanup |

### Tabla `dependencia`

| Índice | Tipo | Propósito |
|---|---|---|
| `pk_dependencia` | B-tree | PK |
| `uk_dependencia_codigo` | B-tree UNIQUE | Búsqueda |
| `idx_dependencia_padre` | B-tree | Organigrama |
| `idx_dependencia_activo` | B-tree (parcial) | Listado público |

---

## 3. Particionamiento

### 3.1 `log_auditoria` — RANGE por mes

**Justificación:** alto volumen esperado (≥1M filas/mes); limpieza de particiones antiguas simple; queries por rango de fecha eficientes.

```sql
CREATE TABLE log_auditoria (
  id BIGINT NOT NULL,
  usuario_id BIGINT,
  accion VARCHAR(50) NOT NULL,
  recurso VARCHAR(100),
  recurso_id BIGINT,
  cambios JSONB,
  ip_origen INET,
  user_agent TEXT,
  created_at TIMESTAMP NOT NULL,
  PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

-- Partición inicial
CREATE TABLE log_auditoria_2026_01 PARTITION OF log_auditoria
  FOR VALUES FROM ('2026-01-01') TO ('2026-02-01');

-- Job mensual crea la partición del mes siguiente y archiva la de 13 meses atrás
```

### 3.2 `documento_version` — RANGE por año

**Justificación:** histórico de versiones de documentos de transparencia, archivado por año para optimizar storage.

```sql
CREATE TABLE documento_version (
  ...
  fecha_version TIMESTAMP NOT NULL,
  PRIMARY KEY (id, fecha_version)
) PARTITION BY RANGE (fecha_version);

CREATE TABLE documento_version_2025 PARTITION OF documento_version
  FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');
```

---

## 4. Vistas materializadas (cache de queries pesadas)

### `mv_documentos_subseccion`
- **Refresh:** `REFRESH MATERIALIZED VIEW CONCURRENTLY mv_documentos_subseccion;` después de cada publicación.
- **Trigger:** evento Laravel `DocumentoPublicado` → job `RefrescarVistaDocumentos`.
- **Justificación:** el listado público de cada subsección no necesita datos en tiempo real; un delay de minutos es aceptable.

### `mv_servidor_publico`
- **Refresh:** job diario tras sincronización SIGEP.
- **Justificación:** el directorio cambia poco; el join con `dependencia` se evita en cada request.

---

## 5. Configuración PostgreSQL recomendada

```ini
# postgresql.conf (production)

# Memoria
shared_buffers = 4GB                  # 25% de RAM
effective_cache_size = 12GB           # 75% de RAM
work_mem = 64MB                      # Para sorts complejos
maintenance_work_mem = 1GB           # Para VACUUM e índices
huge_pages = try

# WAL
wal_buffers = 16MB
checkpoint_completion_target = 0.9
max_wal_size = 4GB

# Planner
random_page_cost = 1.1               # SSD storage
effective_io_concurrency = 200       # SSD con múltiples workers

# Logging
log_min_duration_statement = 500ms   # Log queries >500 ms
log_checkpoints = on
log_connections = on
log_disconnections = on
log_lock_waits = on
log_temp_files = 0

# Autovacuum (crítico para alto volumen)
autovacuum = on
autovacuum_max_workers = 4
autovacuum_naptime = 30s
autovacuum_vacuum_threshold = 50
autovacuum_analyze_threshold = 50
autovacuum_vacuum_scale_factor = 0.1
autovacuum_analyze_scale_factor = 0.05

# Estadísticas
default_statistics_target = 500      # Mejor estimación del planner
```

---

## 6. Monitoreo de queries lentas

```sql
-- Top 20 queries más lentas
SELECT
  substring(query, 1, 100) AS query_short,
  calls,
  mean_exec_time AS avg_ms,
  total_exec_time AS total_ms
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 20;

-- Queries que no usan índice
SELECT
  schemaname, tablename,
  seq_scan, seq_tup_read,
  idx_scan, idx_tup_fetch
FROM pg_stat_user_tables
WHERE seq_scan > idx_scan
  AND n_live_tup > 1000
ORDER BY seq_tup_read DESC;
```

---

## 7. Plan de mantenimiento

| Tarea | Frecuencia | Comando |
|---|---|---|
| VACUUM ANALYZE | Diario (autovacuum) | Automático |
| VACUUM FULL | Mensual (ventana mantenimiento) | `VACUUM FULL` |
| REINDEX | Trimestral | `REINDEX DATABASE sede` |
| Refresh vista materializada | Tras publicaciones | `REFRESH MATERIALIZED VIEW CONCURRENTLY` |
| Crear partición mensual | Mensual (job) | `CREATE TABLE log_auditoria_YYYY_MM` |
| Archivar partición antigua | Anual (job) | `DETACH PARTITION` + mover a `log_auditoria_YYYY` (archivo) |
| Backup completo | Diario 02:00 UTC | `pg_dump` + subir a GCS |
| PITR | Continuo | WAL archiving |

---

## 8. Cumplimiento de RNF

| RNF | Plan |
|---|---|
| RNF-REND-01 TTFB ≤ 200 ms | Materialized views + caché + índices cubrientes |
| RNF-REND-02 LCP ≤ 2,5 s | Pre-renderizado de páginas estáticas en Cloudflare + lazy loading |
| RNF-CAP-01 ≥1000 VU | Connection pooling (PgBouncer) + read replicas |
| RNF-CAP-02 500 GB storage | Lifecycle S3 + archivos en GCS, no en BD |
| RNF-DISP-01 ≥99,5% uptime | HA multi-zona + failover automático |
| RNF-DISP-02 RTO 4h, RPO 1h | PITR + backup verificado mensual |

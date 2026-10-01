# Modelo Físico PostgreSQL 15+ — BD Sede Electrónica

> Fase física del pipeline jose-bd. Cubre **C9** (índices justificados por álgebra relacional), **C11** (transaccional + independencia física/lógica) y resuelve **C-13** (política de PK). Insumos leídos íntegros: `00-inventario.md` (volúmenes, append-only, X-Road alto volumen), `01-dependencias.md` (cierres/claves/minimal cover para justificar índices), `02-normalizacion.md` + DELTA (BCNF/4FN, derivados, 4 sin clave natural), `03-modelo-logico.md` + DELTA (138 tablas, ~660 col, ~180 FK, ~150 CHECK, 13 jerarquías, `dependencia` creada). Marco teórico: modelo relacional (Codd, Date, Bernstein, Silberschatz). **Cero invención**: cada índice atado a una operación σ/⋈/π/búsqueda del dominio. Los 6 ajustes del DELTA integrados (§5).
>
> Cobertura: 5 de 5 insumos leídos íntegros, 0 omitidos.

---

## 0. Política de PK física (resolución C-13) e independencia física/lógica

### 0.1 El conflicto C-13 y el criterio de resolución

El inventario reportó C-13 sin cerrar: módulos 09/12/G2 usan **UUID**; módulos 01/02/03/04/06/G1 usan **INTEGER/BIGSERIAL**. La normalización ya estableció (§0.2 lógico) que la clave **natural** vive como `UNIQUE` en cada tabla, por lo que la PK física es una **decisión de implementación que no altera el modelo lógico** (independencia física, Codd). Resuelvo por **clase de tabla**, no por gusto, con tres criterios objetivos: (a) ¿hay generación distribuida o exposición externa que exija opacidad/no-enumerabilidad? (b) ¿el volumen y la localidad de inserción favorecen claves monótonas? (c) ¿la clave natural es atómica, pequeña e inmutable?

### 0.2 Política por clase de tabla

| Clase de tabla | Política PK física | Justificación relacional y operativa | Ejemplos |
|---|---|---|---|
| **A. Entidad de negocio con clave natural ya en UNIQUE** | `BIGINT GENERATED ALWAYS AS IDENTITY` + UNIQUE sobre la clave natural | La clave natural garantiza integridad de entidad (Codd); el surrogate `BIGINT` da FK angostas (8 bytes vs UUID 16), índices B-tree más densos, inserción monótona (menos page splits, mejor con MVCC) y JOINs ⋈ más baratos. No hay generación distribuida → no se necesita UUID. | `tramite`, `solicitud`, `pqrsd`, `radicado`, `dependencia`, `sede_electronica`, `tipo_pqrsd`, `impuesto`, todos los catálogos |
| **B. Entidad expuesta externamente / federada / requiere opacidad o generación distribuida** | `UUID` (`gen_random_uuid()`, builtin v15) PK | Identificadores que viajan a sistemas externos (X-Road, SGDEA/Orfeo, SCD-OIDC, firma), o donde la enumerabilidad secuencial es un riesgo (anti-IDOR RN-09-D01). UUID evita fuga de cardinalidad y permite generación cliente sin coordinación. | `usuario_interno`, `expediente_electronico`, `documento_electronico`, `firma_electronica`, `notificacion`, `sesion`, `incidente_seguridad`, `contenido` |
| **C. Catálogo pequeño con clave natural atómica, estable y semántica** | **Clave natural directa como PK** (sin surrogate) | Tabla de baja cardinalidad y casi sin escritura; la clave natural es corta, inmutable y usada como FK legible. Añadir surrogate sería redundante (Date: surrogate solo si la natural es inestable/compuesta-ancha). | `calendario_habil` (`fecha` PK), `tipo_arco` (`arco_type`), `tipo_documento_identidad` (`codigo`), `formato_abierto`/`licencia_datos` (`code`), `interop_external_system` (`system_key`), `certificado_digital` (`serial_number`) |
| **D. Tablas de altísimo volumen append-only** | `BIGINT GENERATED ALWAYS AS IDENTITY` (no UUID) | Volumen alto + inserción append + particionado por rango de fecha exigen clave **monótona y compacta** para BRIN y localidad de página. UUID v4 fragmentaría el índice. La opacidad no aplica (no se exponen por id; se consultan por fecha/actor). | `log_auditoria`, `interop_xroad_transaction`, `intento_login` |
| **E. Subtipos ISA class-table** | PK = FK al supertipo (hereda el tipo del supertipo) | La PK compartida ES el mecanismo de integridad supertipo-subtipo (§3.5 lógico). No se genera valor propio. | `normativa`, `contrato`, `plan_accion`, `informe_pqrsd`, etc. |
| **F. Asociativas / históricos con clave compuesta** | PK = clave natural compuesta (sin surrogate) | Toda la relación es clave (asociativas puras) o la clave temporal compuesta es la natural. | `rol_permiso`, `usuario_rol`, `dataset_formato`, `horario_canal`, `item_evaluacion_sus`, `version_documento_transparencia` |

**Caso especial `log_auditoria`:** clase D → PK física `BIGINT IDENTITY`, **pero** `record_hash` es UNIQUE (clave natural lógica). El `id` físico no se expone; la consulta es por `occurred_at`/actor. `id_usuario_interno` (FK→usuario_interno) es UUID porque referencia clase B; `id_ciudadano` es BIGINT (clase A). Esta heterogeneidad de tipos de FK es **correcta y deliberada**: cada FK adopta el tipo de la PK que referencia.

### 0.3 Independencia preservada por esta política

- **Física:** el modelo lógico nunca referencia el surrogate como concepto de negocio; las consultas externas y vistas usan la **clave natural** (`numero_radicado`, `codigo_suit`, `numero_expediente`). Cambiar un `BIGINT IDENTITY` por UUID NO toca el esquema lógico ni las vistas externas — independencia física de Codd.
- **Lógica:** las vistas por perfil (§6.1) proyectan π subconjuntos de columnas sin exponer PKs físicas, aislando a cada rol de la estructura interna.

---

## 1. Mapeo de tipos concretos (por columna donde difiera del genérico)

### 1.1 Reglas globales de materialización

| Tipo genérico (lógico) | Tipo físico PostgreSQL 15+ | Regla / justificación |
|---|---|---|
| `TIMESTAMP` (marcado "Hora Legal") | **`TIMESTAMPTZ`** | Toda marca temporal legal almacena en UTC, presenta en zona. Hora Legal Colombia (INM) + evidencia TSA RFC 3161 exigen instante absoluto (RN-TX-D02). Aplica a `*.created_at/updated_at`, `fecha_hora_radicacion`, `occurred_at`, etc. |
| `TIMESTAMP` sin connotación legal | `TIMESTAMPTZ` | Uniformidad: nunca `timestamp` naive (evita ambigüedad DST/zona). |
| `DATE` puro (vencimientos, vigencias) | `DATE` | `fecha_vencimiento`, `vigencia_desde/hasta`, `calendario_habil.fecha`, `fecha_nacimiento`. |
| `DECIMAL(p,s)` de dinero | **`NUMERIC(18,2)`** | Dinero exacto, nunca `float`. `tramite.costo`, `pago.monto`, `impuesto.tarifa`, `contrato.monto`/`valor_ejecutado`. |
| `DECIMAL` de porcentaje | `NUMERIC(5,2)` CHECK 0–100 | `contrato.porcentaje_ejecutado` (GENERATED), avances. |
| `DECIMAL` de puntaje | `NUMERIC(5,2)` | `evaluacion_sus.puntaje_sus` (0–100), promedios. |
| correo | `VARCHAR(254)` + `CHECK` RFC 5322 (DOMAIN `correo_email`) | **NO `CITEXT`.** Unicidad case-insensitive vía índice funcional `UNIQUE (lower(correo))`. RFC 5322 unificado (DELTA ítem 6). |
| IP de origen | **`INET`** | `pqrsd.ip_origen`, `log_auditoria.ip_address`. Valida IPv4/IPv6 (doble pila) + operadores de subred. |
| hash SHA-256 | `VARCHAR(64)` (hex) / `BYTEA` | `log_auditoria.record_hash/previous_hash` = `VARCHAR(64)` (legible para encadenamiento); `documento_electronico.hash_integridad` = `VARCHAR(128)` (admite SHA-512). |
| contraseña | `VARCHAR(255)` | `usuario_interno.contrasena_hash` (bcrypt/argon2). |
| token TSA / blobs binarios | `BYTEA` | `interop_xroad_transaction.tsa_stamp_token`. |
| contadores alto volumen | `BIGINT` | `radicado.consecutivo_anual`, counters interop. |
| jerárquico/extensible **ya justificado** | `JSONB` | SOLO donde la normalización lo aprobó: `solicitud.datos_formulario`, `tramite.georreferenciacion`, `log_auditoria.detalle`. **Prohibido** para SUS, horarios, metadatos dataset. |

### 1.2 ENUM nativo vs dominio CHECK

**Decisión: `CHECK (col IN (...))` sobre `VARCHAR`, NO tipos `ENUM` nativos**, salvo dos `DOMAIN`.

Justificación: (1) los `ENUM` nativos no permiten quitar valores y reordenar exige `ALTER TYPE` con bloqueos; los dominios de este corpus evolucionan (estados de `solicitud`, canales). Un `CHECK` se altera `NOT VALID` + `VALIDATE` sin reescribir la tabla. (2) Trazabilidad: el `CHECK` es legible y mapea 1:1 a las RN. (3) Excepción **`DOMAIN correo_email`** (RFC 5322) reutilizado en ciudadano/usuario_interno/pqrsd/dependencia/contacto. (4) Excepción **`DOMAIN telefono_co`** (`^\+57...`, RN-B1-017). Catálogos de muchos valores que cambian (tipos PQRSD, formatos, licencias) son **tablas con FK**, no CHECK ni ENUM. Los CHECK quedan para discriminadores cerrados de baja cardinalidad (`tipo_servicio`, `subtipo` ISA, `contexto`, `estado_*`).

### 1.3 Columnas generadas (derivados) — materialización física

| Tabla.columna | Definición física |
|---|---|
| `tramite.es_gratuito` | `BOOLEAN GENERATED ALWAYS AS (costo = 0) STORED` |
| `ciudadano.is_minor` | **NO GENERATED** (`current_date` no es IMMUTABLE): vista o trigger nocturno. |
| `documento_electronico.es_valido` | `BOOLEAN GENERATED ALWAYS AS (mime_type_declarado = mime_type_real AND estado_antivirus <> 'infectado') STORED` (IMMUTABLE ✓) |
| `contrato.porcentaje_ejecutado` | `NUMERIC(5,2) GENERATED ALWAYS AS (CASE WHEN monto > 0 THEN round(valor_ejecutado / monto * 100, 2) END) STORED` (DELTA A.1) |
| `solicitud.requiere_pago`, `pago.monto`, `pqrsd.cumplimiento_plazo`, `evaluacion_sus.puntaje_sus`, `solicitud_arco.deadline_at` | **NO GENERATED** (dependen de otra tabla o de calendario hábil): vista o trigger. `pago.monto` = snapshot inmutable con CHECK validado en INSERT. |

> Lección física: `GENERATED ALWAYS AS ... STORED` exige expresión **IMMUTABLE de la misma fila**. Derivados que dependen de `current_date` (is_minor) o de otra tabla (requiere_pago, monto, deadline) → vista/trigger. Corrige la nota optimista del modelo lógico.

---

## 2. Índices justificados por álgebra relacional (C9 — obligatorio)

> Convención: **σ** = selección/filtro (WHERE), **⋈** = join por FK, **π/orden** = proyección con ORDER BY/cobertura, **búsqueda** = full-text/JSONB/trigram. Cada índice nace de una consulta del dominio. PK y UNIQUE ya crean B-tree implícitos. **No se inventan índices**.

### 2.1 Núcleo: solicitud / radicado / pqrsd / tramite

| Tabla | Índice | Método | Operación | Consulta del dominio |
|---|---|---|---|---|
| `solicitud` | `idx_solicitud_ciudadano_estado (id_ciudadano, estado)` | BTREE | ⋈ + σ | "Mis trámites por estado". Orden: id_ciudadano (selectividad) antes que estado. |
| `solicitud` | `idx_solicitud_tramite (id_tramite)` | BTREE | ⋈ | Join por catálogo + reportes "solicitudes por trámite". |
| `solicitud` | `idx_solicitud_vencimiento (fecha_vencimiento) WHERE estado IN ('RADICADO','EN_TRAMITE','REQUIERE_SUBSANACION')` | BTREE parcial | σ rango | Alertas de vencimiento: solo solicitudes vivas. |
| `solicitud` | `UNIQUE (clave_idempotencia)` | BTREE | σ igualdad | Anti-doble-radicación (RN-03-D01). |
| `solicitud` | `idx_solicitud_borrador_exp (fecha_expiracion_borrador) WHERE es_borrador` | BTREE parcial | σ rango | Purga de borradores a 30 días. |
| `radicado` | `UNIQUE (prefijo_dependencia, anio, consecutivo_anual)` | BTREE | σ igualdad + integridad | Clave alterna AGN (DELTA C.1). |
| `radicado` | `idx_radicado_expediente (id_expediente)` | BTREE | ⋈ | Reconstrucción del expediente. |
| `radicado` | `idx_radicado_fecha (fecha_hora_radicacion)` | BRIN | σ rango | Alto volumen append por fecha. |
| `pqrsd` | `idx_pqrsd_dependencia_estado (id_dependencia, estado)` | BTREE | ⋈ + σ | Bandeja por dependencia. |
| `pqrsd` | `idx_pqrsd_fecha_resp (fecha_estimada_respuesta) WHERE estado NOT IN ('respondida','cerrada')` | BTREE parcial | σ rango | Alertas de plazo legal (RN-04-D01). |
| `pqrsd` | `idx_pqrsd_tipo (id_tipo_pqrsd)` | BTREE | ⋈ | Join catálogo plazos + informe por tipo. |
| `pqrsd` | `idx_pqrsd_ciudadano (id_ciudadano) WHERE id_ciudadano IS NOT NULL` | BTREE parcial | ⋈ | "Mis PQRSD"; excluye anónimas. |
| `tramite` | `idx_tramite_dependencia (id_dependencia)` | BTREE | ⋈ | Trámites por dependencia 🔴. |
| `tramite` | `idx_tramite_busqueda GIN (to_tsvector('spanish', nombre||' '||descripcion))` | GIN | búsqueda textual | Buscador SUIT (RF-B2-029). |
| `tramite` | `idx_tramite_estado (estado_estandarizado) WHERE estado_estandarizado='ACTIVO'` | BTREE parcial | σ | Listado público de trámites activos. |

### 2.2 Actores / RBAC / identidad

| Tabla | Índice | Método | Operación | Consulta |
|---|---|---|---|---|
| `ciudadano` | `UNIQUE (id_tipo_documento, numero_documento)` | BTREE | σ igualdad | Login por documento (clave natural). |
| `ciudadano` | `idx_ciudadano_correo_lower UNIQUE (lower(correo)) WHERE correo IS NOT NULL` | BTREE funcional parcial | σ igualdad | Login por correo case-insensitive sin CITEXT. |
| `ciudadano` | `UNIQUE (scd_sub) WHERE auth_source='scd_oidc'` | BTREE parcial | σ + ⋈ | Identidad federada SCD-OIDC. |
| `usuario_interno` | `idx_usuario_correo_lower UNIQUE (lower(correo))` | BTREE funcional | σ igualdad | Login back-office. |
| `usuario_interno` | `UNIQUE (id_sigep) WHERE id_sigep IS NOT NULL` | BTREE parcial | ⋈ | Cruce con servidor_publico (C-09). |
| `usuario_rol` | `idx_usuario_rol_rol (id_rol)` | BTREE | ⋈ | "Usuarios con rol X" (el otro orden lo da la PK). |
| `rol_permiso` | `idx_rol_permiso_permiso (id_permiso)` | BTREE | ⋈ | Permisos efectivos por permiso. |
| `servidor_publico` | `UNIQUE (codigo_sigep)`, `UNIQUE (correo_institucional)` | BTREE | σ igualdad | Directorio + cruce SIGEP. |

### 2.3 Documentos / expedientes / notificaciones / firma

| Tabla | Índice | Método | Operación | Consulta |
|---|---|---|---|---|
| `documento_electronico` | `UNIQUE (hash_integridad, contexto, COALESCE(id_solicitud,id_pqrsd,id_expediente))` | BTREE | σ igualdad | Deduplicación + integridad arco. |
| `documento_electronico` | `idx_doc_solicitud (id_solicitud) WHERE id_solicitud IS NOT NULL` | BTREE parcial | ⋈ | Adjuntos de solicitud (rama TRAMITE). |
| `documento_electronico` | `idx_doc_pqrsd (id_pqrsd) WHERE id_pqrsd IS NOT NULL` | BTREE parcial | ⋈ | Adjuntos de PQRSD. |
| `documento_electronico` | `idx_doc_expediente (id_expediente) WHERE id_expediente IS NOT NULL` | BTREE parcial | ⋈ | Foliado. Índices parciales por rama del arco = mínimo tamaño. |
| `documento_electronico` | `idx_doc_antivirus (estado_antivirus) WHERE estado_antivirus='pendiente'` | BTREE parcial | σ | Cola del worker antivirus. |
| `expediente_electronico` | `UNIQUE (numero_expediente)` | BTREE | σ igualdad | Clave natural. |
| `expediente_electronico` | `UNIQUE (sgdea_referencia) WHERE sgdea_referencia IS NOT NULL` | BTREE parcial | σ + ⋈ | **DELTA ítem 2**: sync SGDEA, NULL hasta sincronizar. |
| `expediente_electronico` | `UNIQUE (id_solicitud) WHERE id_solicitud IS NOT NULL` | BTREE parcial | ⋈ 1:1 | Relación 1:1 parcial (A.7). |
| `notificacion` | `idx_notif_polimorf (entidad_tipo, entidad_id)` | BTREE | σ igualdad | Notificaciones de un objeto (H11). |
| `notificacion` | `idx_notif_pendientes (estado, canal) WHERE estado IN ('pendiente','fallida')` | BTREE parcial | σ | Worker de envío/reintento. |
| `notificacion` | `idx_notif_ciudadano (id_destinatario_ciudadano) WHERE id_destinatario_ciudadano IS NOT NULL` | BTREE parcial | ⋈ | "Mis notificaciones". |
| `firma_electronica` | `UNIQUE (hash_documento, timestamp_firma, id_firmante, <FK_arco>)` | BTREE | σ igualdad | Anti-duplicado + verificación. |

### 2.4 Transparencia ISA / tributario / contenido

| Tabla | Índice | Método | Operación | Consulta |
|---|---|---|---|---|
| `transparencia_publicacion` | `idx_transp_subtipo_fecha (subtipo, fecha_publicacion DESC)` | BTREE | σ + π/orden | "Últimas publicaciones de la sección X" (ITA). |
| `contrato` | `UNIQUE (numero_contrato, vigencia_fiscal)` | BTREE | σ igualdad | Búsqueda de contrato (SECOP). |
| `version_documento_transparencia` | `idx_vdt_pub (id_publicacion, version DESC)` | BTREE | ⋈ + π | Versión vigente (RN-02-D01). |
| `impuesto` | `UNIQUE (nombre, vigencia_desde)` | BTREE | σ igualdad + ⋈ | PK natural (DELTA C.2). |
| `calendario_tributario` | `idx_cal_trib_impuesto (id_impuesto, vigencia_fiscal)` | BTREE | ⋈ + σ | Vencimientos por impuesto/vigencia. |
| `contenido` | `idx_contenido_tipo_estado (tipo, estado) WHERE estado='publicado'` | BTREE parcial | σ | Listado público publicado (CMS). |
| `contenido` | `idx_contenido_busqueda GIN (to_tsvector('spanish', titulo||' '||cuerpo))` | GIN | búsqueda | Buscador de contenidos. |

### 2.5 Canales / citas / datos abiertos / interop

| Tabla | Índice | Método | Operación | Consulta |
|---|---|---|---|---|
| `franja_horaria` | `idx_franja_servicio_fecha (id_servicio_agendable, fecha, estado)` | BTREE | ⋈ + σ rango | Disponibilidad de cupos (agenda). |
| `cita` | `UNIQUE (codigo_confirmacion)` | BTREE | σ igualdad | Confirmación/consulta por código. |
| `cita` | `idx_cita_franja (id_franja_horaria)` | BTREE | ⋈ | Citas de una franja (conteo cupos). |
| `dataset` | `idx_dataset_busqueda GIN (to_tsvector('spanish', nombre||' '||descripcion))` | GIN | búsqueda | Catálogo de datos abiertos. |
| `interop_xroad_transaction` | `idx_xroad_service_ts (id_service, transaction_timestamp)` | BTREE | ⋈ + σ rango | Trazabilidad por servicio en ventana. |
| `interop_xroad_transaction` | `idx_xroad_ts BRIN (transaction_timestamp)` | BRIN | σ rango | **Alto volumen** append → BRIN. |
| `interop_xroad_transaction` | `idx_xroad_status (status) WHERE status IN ('pending','retry','error')` | BTREE parcial | σ | Reintentos (RN-10-D01). |
| `interop_tsa_config` | `UNIQUE (id_environment) WHERE activo` | BTREE parcial | σ | **DELTA ítem 3**: una config TSA activa/ambiente. |
| `certificado_digital` | `idx_cert_expira (expires_at)` | BTREE | σ rango | Alertas de vencimiento. |

### 2.6 Seguridad / append-only / auditoría

| Tabla | Índice | Método | Operación | Consulta |
|---|---|---|---|---|
| `log_auditoria` | `idx_log_occurred BRIN (occurred_at)` | BRIN | σ rango | Append-only particionada por occurred_at → BRIN ideal. Forense por periodo. |
| `log_auditoria` | `idx_log_actor (id_usuario_interno, occurred_at) WHERE id_usuario_interno IS NOT NULL` | BTREE parcial | ⋈ + π | "Acciones del usuario X en el tiempo". |
| `log_auditoria` | `idx_log_entidad (entidad_tipo, entidad_id)` | BTREE | σ igualdad | Historial polimórfico de un recurso. |
| `log_auditoria` | `idx_log_evento (event_type) WHERE resultado IN ('failure','blocked')` | BTREE parcial | σ | Eventos de seguridad. |
| `log_auditoria` | `UNIQUE (record_hash)` | BTREE | σ igualdad + integridad | Clave natural + verificación de cadena. |
| `intento_login` | `idx_intento_ip_ts BRIN (occurred_at)` | BRIN | σ rango | Alto volumen append. |
| `intento_login` | `idx_intento_usuario (id_usuario, occurred_at) WHERE resultado='failure'` | BTREE parcial | σ + π | Bloqueo por N fallos (RF-B1-062). |
| `sesion` | `idx_sesion_expira (expires_at) WHERE estado='activa'` | BTREE parcial | σ rango | Limpieza de sesiones expiradas. |
| `consentimiento_datos` | `idx_consent_ciudadano (id_ciudadano, consent_type, occurred_at DESC)` | BTREE | ⋈ + σ + π | Último consentimiento vigente (Ley 1581). |

**Notas de método:**
- **BRIN** solo en append-only de alto volumen con columna **físicamente correlacionada** con el orden de inserción (timestamps): `log_auditoria`, `intento_login`, `interop_xroad_transaction`, `radicado`. ~1000× más pequeño que B-tree; suficiente para σ por rango con correlación.
- **HASH**: **0 índices** — decisión consciente. Todos los lookups de igualdad necesitan también orden/rango (B-tree los cubre) o son UNIQUE (exige B-tree). HASH solo gana en igualdad pura sin orden con claves anchas; no hay tal caso.
- **GIN**: full-text español (`tramite`, `contenido`, `dataset`); `GIN jsonb_path_ops` sobre `datos_formulario` se difiere hasta confirmar patrón de consulta (no se inventa).
- **Compuestos**: orden por selectividad descendente, "equality-first, range-last".

---

## 3. Particiones por volumen

> Solo se particiona donde el **volumen lo exige**. Alto volumen marcado en inventario: X-Road, log de auditoría, intentos de login, notificaciones, solicitudes por año.

| Tabla | Estrategia | Clave de partición | Justificación |
|---|---|---|---|
| `log_auditoria` | **RANGE por mes** | `occurred_at` | Append-only, retención ≥5 años (RN-09-D05). BRIN local + `DETACH`/archivado + pruning por periodo. |
| `interop_xroad_transaction` | **RANGE por mes** | `transaction_timestamp` | 1 fila por intercambio (alto volumen I-17). Pruning + archivado. |
| `intento_login` | **RANGE por mes** | `occurred_at` | Alto volumen, valor decreciente; purga rápida. |
| `notificacion` | **RANGE por mes** | `fecha_creacion` | Multicanal, alto volumen; worker toca solo mes corriente. |
| `solicitud` | **RANGE por año** | `anio` (derivada de fecha_hora_radicacion) | Reportes DAFP por vigencia + archivado de años cerrados. |
| `radicado` | **RANGE por año** | `anio` | Consecutivo anual por dependencia alinea unicidad con la partición. |

**No se particionan** (volumen insuficiente o catálogo): `tramite`, `ciudadano`, `usuario_interno`, `pqrsd`, `dependencia`, catálogos, ISA transparencia. Particionar prematuro = complejidad sin beneficio.

**Interacción con PK (C-13):** las particionadas por rango requieren que la clave de partición forme parte de la PK. Por eso `log_auditoria`/`interop_xroad_transaction`/`intento_login` usan PK compuesta `(id BIGINT, <fecha>)`; la clave natural (`record_hash`) sigue UNIQUE dentro de partición, unicidad global por construcción del hash. Confirma BIGINT IDENTITY (no UUID) para clase D.

---

## 4. Nivel de aislamiento por operación crítica (C11) y anomalías cubiertas

> Default: **READ COMMITTED** para el ~95% (lecturas de portal, CRUD de catálogos) — MVCC ya evita dirty reads. Se eleva SOLO ante anomalía concreta.

| Operación crítica | Nivel / mecanismo | Anomalía evitada | Justificación |
|---|---|---|---|
| **Reserva de cupo de cita** (RN-06-D01) | `SELECT ... FOR UPDATE` sobre `franja_horaria` (lock pesimista) en READ COMMITTED | **Lost update / oversell** del último cupo | Conflicto localizado en una fila (alta contención) → lock pesimista mejor que SSI (menos aborts). |
| **Consecutivo de radicado** (AGN 060/2001) | `pg_advisory_xact_lock(hashtext(prefijo‖anio))` + `MAX+1` | **Lost update / hueco o duplicado** | Consecutivo **denso sin huecos** (exigencia archivística) → advisory lock por dependencia/año sin bloquear otras dependencias. |
| **Radicación idempotente** (RN-03-D01) | READ COMMITTED + `INSERT ... ON CONFLICT (clave_idempotencia) DO NOTHING` | **Doble radicación** por retry | UNIQUE + ON CONFLICT = idempotencia atómica sin elevar aislamiento. |
| **Pago idempotente** | READ COMMITTED + UNIQUE `clave_idempotencia_pago` + `FOR UPDATE` sobre solicitud | **Doble cobro / write skew** estado de pago | Token único + lock sobre solicitud evita doble avance de estado. |
| **Cobertura ISA transparencia** (DELTA ítem 5) | Constraint trigger DEFERRABLE INITIALLY DEFERRED (validación al COMMIT) | **Write skew / fila huérfana** (supertipo sin subtipo) | Permite orden supertipo→subtipo en una tx; valida cobertura al final. |
| **Cadena hash log_auditoria** (RN-09-D05) | `SERIALIZABLE` (o sesión escritora única) | **Phantom / write skew** (dos registros con el mismo previous_hash) | Orden total de la cadena; integridad legal crítica justifica el costo. |
| **Baja de usuario** (RN-12-D02) | READ COMMITTED + UPDATE estado/deactivated_at | n/a | Simple UPDATE; FK a log_auditoria SET NULL (append-only conserva el evento). |

**Anomalías cubiertas:** lost update (cupos, consecutivo, pago), write skew (pago, ISA, cadena hash), phantom (cadena hash), doble inserción (radicado/pago vía UNIQUE+ON CONFLICT). Dirty/non-repeatable read cubiertos por MVCC.

---

## 5. Triggers / constraints físicos

### 5.1 Los 6 ajustes del DELTA del modelo lógico

1. **`alerta_cumplimiento` polimórfico** (ítem 1): `CHECK (entidad_tipo IN (...))` + trigger `trg_alerta_valida_fk` BEFORE INSERT/UPDATE (valida existencia del destino según `entidad_tipo`) + `idx_alerta_polimorf (entidad_tipo, entidad_id)`.
2. **`expediente_electronico.sgdea_referencia` NULLABLE** (ítem 2): NULL permitido (hasta sync SGDEA) + `UNIQUE (sgdea_referencia) WHERE sgdea_referencia IS NOT NULL`.
3. **`interop_tsa_config` UNIQUE parcial** (ítem 3): `UNIQUE INDEX (id_environment) WHERE activo` (RN-10-D02).
4. **`sede_fisica` trigger de cardinalidad** (ítem 4): `trg_sede_footer_max5` BEFORE INSERT/UPDATE — máximo 5 filas con `orden_footer` no nulo.
5. **`transparencia_publicacion` cobertura ISA** (ítem 5): constraint trigger `trg_transp_cobertura` DEFERRABLE INITIALLY DEFERRED — cada `id_publicacion` en exactamente un subtipo coincidente con `subtipo` (prueba B.2 DELTA norm).
6. **Unificaciones** (ítem 6): `DOMAIN correo_email` RFC 5322 unificado; divergencia deliberada `id_responsable→servidor_publico` (RESTRICT) vs `→usuario_interno` (SET NULL) preservada; alias `fecha_baja ≡ deactivated_at` (una columna física, alias por vista).

### 5.2 Triggers/constraints transversales

| Mecanismo | Tipo | Regla / origen |
|---|---|---|
| Cadena hash `log_auditoria` | Trigger `trg_log_hash` BEFORE INSERT + REVOKE UPDATE/DELETE + RLS | `record_hash = sha256(campos‖previous_hash)`. Append-only (RN-09-D05). |
| Cupos de cita | Trigger `trg_cupo_decrementa` + `FOR UPDATE` | `CHECK (cupos_disponibles BETWEEN 0 AND cupos_total)` (RN-06-D01). |
| Arco exclusivo `documento_electronico` | `CHECK` | Una de `{id_solicitud,id_pqrsd,id_expediente}` según `contexto`. |
| Arco exclusivo `firma_electronica` | `CHECK` | Una FK de las 3 según objeto firmado. |
| Polimorfismo `notificacion` | Trigger `trg_notif_valida_fk` | Valida `entidad_id` existe en tabla de `entidad_tipo` (H11). |
| Separación de funciones (SoD) | `CHECK (id_creado_por <> id_aprobado_por)` | RN-09-D02. |
| MFA por rol | Trigger `trg_mfa_requerido` sobre `usuario_rol` | `rol.requiere_mfa → usuario.mfa_habilitado` (RN-09-D04). |
| `pago.monto` snapshot | Trigger BEFORE INSERT + CHECK | `monto = tramite.costo` al radicar. |
| `is_minor` | Vista o trigger nocturno | No GENERATED (`current_date` no IMMUTABLE). |
| Circular `normativa↔proyecto_norma` | FK propietaria en `proyecto_norma.id_normativa` (SET NULL) | Resuelve el ciclo. |

---

## 6. Independencia física/lógica

### 6.1 Independencia lógica — vistas externas por perfil

| Vista | Perfil | π/σ | Aísla de |
|---|---|---|---|
| `v_ciudadano_mis_solicitudes` | Ciudadano | π(numero_radicado, estado, fecha, tramite.nombre) σ(id_ciudadano=current) | PK físicas, borradores ajenos. |
| `v_backoffice_bandeja_pqrsd` | Funcionario | π(numero_radicado, tipo, estado, fecha_estimada, dependencia) σ(dependencia ∈ roles) | Datos personales innecesarios; RLS por dependencia. |
| `v_transparencia_publica` | Público | UNION ALL supertipo⋈subtipos, π publicables | La estructura class-table ISA. |
| `v_auditoria_forense` | Auditor | π(occurred_at, event_type, actor, entidad, resultado) | `record_hash`/`previous_hash` internos. |
| `v_directorio_institucional` | Público | `dependencia`⋈`servidor_publico` (art.9 Ley 1712) | Columnas internas de organigrama. |
| `v_pago_estado` | Ciudadano | π(estado_pago, monto computado, fecha) | Que `monto` NO es columna base. |

Capa de independencia lógica: reparticionar, cambiar BIGINT↔UUID o materializar un derivado lo absorben las vistas; los consumidores no se enteran (Codd).

### 6.2 Independencia física

- **Tablespaces**: separar INDEX de DATA + tablespace de archivado para particiones `DETACH`-eadas (almacenamiento frío).
- **Particionado** (§3): transparente vía tabla padre; cambiar grano (mes→semana) no toca el modelo lógico.
- **Columnas generadas vs vistas**: derivados IMMUTABLE → `GENERATED STORED`; no-IMMUTABLE → vistas. El consumidor ve un atributo, no dónde se computa.
- **Cambio de PK física no afecta el modelo lógico**: la clave natural está en UNIQUE y las vistas/FK externas usan la natural (§0.3).

---

## 7. Resumen

| Dimensión | Conteo / decisión |
|---|---|
| Índices BTREE | ~46 (incl. UNIQUE, parciales y funcionales `lower(correo)`) |
| Índices GIN | 3 full-text español (`tramite`, `contenido`, `dataset`) [+1 JSONB opcional diferido] |
| Índices BRIN | 4 (`log_auditoria`, `intento_login`, `interop_xroad_transaction`, `radicado`) |
| Índices HASH | **0** (decisión consciente: B-tree cubre igualdad + rango/orden) |
| Índices parciales | ~16 (worker-queues, ramas de arco, estados vivos, nullables) |
| Tablas particionadas | **6** (RANGE por mes/año) |
| Política PK (C-13) | 6 clases: A=BIGINT IDENTITY, B=UUID, C=natural directa, D=BIGINT IDENTITY append-only, E=PK=FK ISA, F=compuesta asociativas |
| Niveles de aislamiento | READ COMMITTED default; FOR UPDATE (cupos/pago); advisory lock (consecutivo); SERIALIZABLE (cadena hash, ISA); UNIQUE+ON CONFLICT (idempotencia) |
| ENUM vs CHECK | CHECK + 2 DOMAIN (`correo_email`, `telefono_co`); 0 ENUM nativos |
| Triggers críticos | cadena hash, cupos, cobertura ISA (DEFERRED), 2 polimórficos, ≤5 footer, SoD, MFA por rol, pago.monto snapshot |

---

**Resumen (6 líneas):**
1. **Política PK (C-13):** híbrida por clase — BIGINT IDENTITY (negocio con clave natural en UNIQUE + append-only alto volumen); UUID (entidades expuestas/federadas: usuario_interno, expediente, documento, firma, notificación, sesión); clave natural directa (catálogos); PK=FK (subtipos ISA); compuesta (asociativas).
2. **Índices:** ~46 BTREE (incl. parciales y funcional `lower(correo)`), 3 GIN full-text español, 4 BRIN (timestamps append-only), **0 HASH** (B-tree domina), ~16 parciales.
3. **Particionadas:** 6 RANGE — log_auditoria, interop_xroad_transaction, intento_login, notificacion (mes), solicitud, radicado (año).
4. **Aislamiento:** READ COMMITTED default; FOR UPDATE cupos/pago; advisory lock consecutivo radicado; SERIALIZABLE cadena hash/cobertura ISA; UNIQUE+ON CONFLICT idempotencia. Anomalías: lost update, write skew, phantom, doble inserción.
5. **Triggers críticos:** cadena hash log_auditoria, decremento de cupos, cobertura ISA (DEFERRED), validación polimórfica (alerta_cumplimiento + notificacion), ≤5 footer sede_fisica, SoD, MFA por rol, snapshot pago.monto.
6. **6 ajustes del DELTA integrados:** alerta_cumplimiento polimórfico + trigger; sgdea_referencia NULLABLE + UNIQUE parcial; interop_tsa_config UNIQUE parcial; sede_fisica trigger cardinalidad; transparencia_publicacion trigger cobertura ISA; unificaciones correo RFC 5322 / id_responsable divergente / alias fecha_baja.

Insumos: `00-inventario.md`, `01-dependencias.md`, `02-normalizacion.md`, `03-modelo-logico.md`, `_investigacion-web.md`; metodología `jose-bd.md`.

---

# DELTA — 2ª pasada profunda del modelo físico

> Auditoría PostgreSQL 15+ verificable. 🔴 BLOQUEANTE, 🟡 calidad/rendimiento, 🟢 confirmación. Las resoluciones de diseño tomadas por el orquestador-arquitecto se marcan **[RESUELTO]**.

## A. Tipos
- 🔴 **`contrato.tiene_otrosi` NO puede ser GENERATED** (depende de otra tabla: `EXISTS(otrosi)`). PostgreSQL prohíbe subqueries/otras filas en columnas generadas. **[RESUELTO]** → vista o trigger (igual patrón que `pago.monto`). `porcentaje_ejecutado` GENERATED sí es correcto (misma fila, IMMUTABLE ✓).
- 🔴 **`notificacion.entidad_id BIGINT` mal dimensionado**: su arco polimórfico incluye ACTO=`documento_electronico` (UUID) y CITA/CONTENIDO (UUID, clase B). BIGINT no puede almacenar un UUID. **[RESUELTO]** → `notificacion.entidad_id` a `VARCHAR(255)`/`TEXT` (coherente con `log_auditoria.entidad_id`). Aplica el mismo criterio a cualquier columna polimórfica que referencie entidades clase B (UUID).
- 🟡 **SUS — fork resuelto**: los 10 ítems Likert van como **filas** en `item_evaluacion_sus` (mandato anti-JSONB de la metodología) ⇒ `evaluacion_sus.puntaje_sus` **NO es GENERATED** (depende de otra tabla) → **[RESUELTO]** vista/trigger que aplica la fórmula SUS. Cierra la zona gris.
- 🟢 TIMESTAMPTZ global, NUMERIC(18,2) dinero, INET, BYTEA TSA, DOMAIN correo_email/telefono_co, CHECK vs ENUM: verificados correctos. `radicado.consecutivo_anual` puede ser INTEGER (cosmético).

## B. Índices faltantes (FK sin índice → ⋈ lento / RESTRICT con seq-scan)
- 🔴 **~8 índices de FK `id_dependencia`** faltantes: `pqrsd` (ya tiene compuesto), `tramite` (ya), **falta** `canal_atencion`, `servicio_agendable`, `mecanismo_participacion`, `franja_horaria`, `cita`, `asignacion_dependencia`, `traslado_competencia`, `micrositio`, `servidor_publico`. Toda FK con ON DELETE RESTRICT necesita índice en la hija. **[A AGREGAR]** BTREE por cada una.
- 🔴 `firma_electronica (id_firmante)`, `(certificado_serial)` → ⋈ verificación OCSP.
- 🟡 `pago (id_solicitud)`, `cita (id_ciudadano) WHERE NOT NULL`, `cita (id_servicio_agendable)`, FKs nullable hacia `firma_electronica` (parciales).
- 🟡 **UNIQUE temporal `consentimiento_datos (id_ciudadano, consent_type, occurred_at)`** falta (el índice de consulta DESC no impone unicidad; DELTA-deps C.5).
- 🟡 **INCLUDE de cobertura** (index-only scan): `idx_solicitud_ciudadano_estado ... INCLUDE (numero_radicado, fecha_hora_radicacion, id_tramite)`; `idx_pqrsd_dependencia_estado ... INCLUDE (numero_radicado, fecha_estimada_respuesta)`.
- 🟢 Históricos `(id_padre, version/secuencia)`: la PK compuesta ya cubre el JOIN por padre (prefijo izquierdo) — NO añadir índices redundantes.

## C. Índices a revisar/eliminar
- 🟡 `idx_radicado_fecha BRIN`: con `radicado` ya NO particionada (ver D), reevaluar; BRIN aporta poco si el volumen no es masivo → **quitar** salvo consultas de rango fino.
- 🟡 `interop_xroad_transaction`: coexisten BTREE `(id_service, transaction_timestamp)` + BRIN `(transaction_timestamp)` + partición mensual sobre el mismo eje. Conservar el BTREE compuesto; **evaluar quitar el BRIN** (pruning mensual ya acota el rango).
- 🟢 `idx_usuario_rol_rol`, `idx_rol_permiso_permiso`, 0 HASH: correctos.

## D. Particiones — BLOQUEANTE PostgreSQL [RESUELTO]
- 🔴 **FK entrantes a `solicitud`/`radicado` particionadas**: PG15 exige que la FK referencie una UNIQUE que **contenga la clave de partición** (`anio`). `id_solicitud`/`numero_radicado` solos no la contienen. Reciben ~12 FK (documento_electronico, pago, expediente, resultado_tramite, requerimiento_subsanacion, desistimiento, sap, retroalimentacion, encuesta, falla_interop; y solicitud/pqrsd/log_acceso→radicado).
- **[RESUELTO — decisión de diseño]: NO particionar `solicitud` ni `radicado`.** Justificación: el inventario marca "alto volumen" SOLO cualitativo para X-Road/log/login, NO para solicitud/radicado (cientos de miles/año, no millones); propagar `anio` a 12 tablas hijas es un costo de diseño desproporcionado. Se conserva índice por `anio`/fecha para reportes por vigencia. Reevaluable a futuro con métricas reales.
- **Particiones finales (4, todas hojas sin FK entrantes):** `log_auditoria` (RANGE mes), `interop_xroad_transaction` (RANGE mes), `intento_login` (RANGE mes), `notificacion` (RANGE mes; PK compuesta `(id, fecha_creacion)`, es hoja → viable). Todas con PK que incluye la partition key. ✅
- 🟢 `pqrsd`, `cita` correctamente NO particionadas.

## E. Concurrencia — anomalías adicionales [RESUELTO]
- 🔴 **Doble asignación PQRSD** (write skew sobre "una activa"): **[RESUELTO]** `UNIQUE (id_pqrsd) WHERE activa` (declarativo, excluye dos activas) + `FOR UPDATE` sobre `pqrsd` en la reasignación.
- 🔴 **Prórroga concurrente** (excede tope legal RN-04-D02): READ COMMITTED no ve filas concurrentes → **[RESUELTO]** `FOR UPDATE` sobre `pqrsd` (o SERIALIZABLE) en la tx de prórroga; el CHECK solo no basta.
- 🔴 **OCC de `contenido`** (lock_version, RN-12-D05) omitido en el físico: **[RESUELTO]** `UPDATE contenido SET ..., lock_version=lock_version+1 WHERE id=? AND lock_version=?`; 0 filas → conflicto editorial.
- 🟡 **Advisory lock consecutivo**: usar `pg_advisory_xact_lock(id_dependencia::bigint, anio::int)` (dos enteros) en vez de `hashtext` 32-bit (evita colisión espuria).
- 🟡 **Orden canónico de locks** (anti-deadlock): (1) advisory por dependencia, (2) FOR UPDATE solicitud, (3) FOR UPDATE franja. Documentar como invariante.
- 🟡 **Cadena hash**: SERIALIZABLE sobre tabla particionada de alto volumen genera muchos aborts SSI → preferir **escritor único serializado por advisory lock global de la cadena** (throughput).
- 🟢 Cupos (FOR UPDATE), idempotencia radicado/pago (UNIQUE+ON CONFLICT): correctos.

## F. Independencia física/lógica
- 🔴 **RLS sin POLICY definida**: especificar `CREATE POLICY` por dependencia en `pqrsd`/`asignacion_dependencia`/`tramite`/`franja_horaria` (`USING (id_dependencia IN (SELECT ... FROM usuario_rol ...))`) y policy de SELECT por rol auditor en `log_auditoria`/`consentimiento_datos` (append-only ya con REVOKE UPDATE/DELETE). Hueco de seguridad declarativa.
- 🟡 Vistas adicionales por perfil: `v_dataset_publico`, `v_transparencia_contratos` (completitud de independencia lógica para datos abiertos/SECOP).
- 🟢 IMMUTABILITY bien aplicada (is_minor STABLE→no GENERATED), tablespaces, alias fecha_baja: correctos.

## G. Veredicto
Tras integrar este DELTA el físico queda **listo para DDL y auditoría**. Bloqueantes resueltos: (1) no particionar solicitud/radicado → FK entrantes válidas; (2) tiene_otrosi/puntaje_sus → vista, notificacion.entidad_id → TEXT; (3) concurrencia PQRSD/prórroga/OCC cubierta; (4) índices de FK y RLS especificados. **Aciertos confirmados 🟢:** política PK por clase, tipos, 0 HASH, BRIN en hojas append-only, FOR UPDATE cupos, advisory lock consecutivo, índices parciales por rama de arco.

---

# DELTA — Ronda 2 del modelo físico (tablas descompuestas)

> Cubre físicamente lo nuevo de la ronda 2. PostgreSQL 15+. 🔴 bloqueante, 🟡 calidad, 🟢 confirmación. **Resuelve una contradicción capa-lógica vs física** (PK de `dependencia`).

## A. Tipos + decisión PK `dependencia` — REVIERTE la PK natural del DELTA lógico R2
🔴 **`dependencia` = `id_dependencia BIGINT GENERATED ALWAYS AS IDENTITY` PK + `UNIQUE(codigo)` + `UNIQUE(nombre)`** (clase A). El DELTA lógico R2 puso `codigo VARCHAR(20)` PK y lo propagó a 12 hijas con ON UPDATE CASCADE — físicamente subóptimo: FK anchas (20 bytes ×12 tablas de alto tráfico) y **cascada masiva** al renombrar un código. La capa física manda en C-13: surrogate BIGINT (FK 8 bytes, índices densos, surrogate inmutable → sin cascada). La integridad de entidad la da `UNIQUE(codigo)`; las FK íntegras, el surrogate. Es independencia física/lógica de Codd (el lógico expresa la clave natural; el físico la implementa como UNIQUE + surrogate).

**Tipos tablas nuevas:**
- `dependencia`: id_dependencia BIGINT IDENTITY PK; codigo VARCHAR(20) UNIQUE; nombre VARCHAR(300) UNIQUE; responsable_id BIGINT FK; sede_id BIGINT FK; es_externa BOOLEAN; entidad_externa_nombre VARCHAR(300) CHECK cond.; activa BOOLEAN; **padre_id BIGINT** FK→dependencia(id_dependencia) self (re-tipado a BIGINT, NO VARCHAR); correo_email/telefono_co DOMAIN; created_at/updated_at TIMESTAMPTZ.
- `ciudadano_juridica` (clase E): 🟡 recomendado **PK=FK al surrogate** `id_ciudadano BIGINT PK REFERENCES ciudadano(id_ciudadano) ON DEL CASCADE` (clase E canónica), no PK=FK compuesta natural; razon_social VARCHAR(300) NOT NULL; representante_legal_id BIGINT FK ON DEL SET NULL.
- `respuesta_item_sus`: id_evaluacion BIGINT + numero_item SMALLINT (CHECK 1-10) PK; respuesta_likert SMALLINT (CHECK 1-5). No JSONB.
- `respuesta_encuesta`: id_encuesta BIGINT + numero_pregunta SMALLINT (CHECK 1-3) PK; valor_respuesta TEXT; id_pregunta BIGINT FK [COND P-13].
- `pregunta_encuesta` [COND P-13]: id_pregunta BIGINT IDENTITY PK; texto VARCHAR(500); orden SMALLINT; id_tramite BIGINT FK; activa.
- `contrato`: porcentaje_ejecutado NUMERIC(5,2) GENERATED STORED (IMMUTABLE ✓ misma fila); pagos_pendientes NUMERIC(18,2) GENERATED STORED; tiene_otrosi → vista; estado_ejecucion VARCHAR(30) CHECK enum [confirmar SECOP].
- `evaluacion_sus.puntaje_sus`: NO GENERATED/NO base → vista (preferido) o trigger.

## B. Re-tipado FK→dependencia — INVERTIDO
🔴 Las **11 FK genéricas se quedan BIGINT** → `dependencia(id_dependencia)` (surrogate), ON DELETE RESTRICT **ON UPDATE RESTRICT** (surrogate inmutable → sin cascada). NO hay re-tipado a VARCHAR. Excepción: 🔴 `radicado.prefijo_dependencia VARCHAR(20) → dependencia(codigo)` ON UPDATE CASCADE (el código va dentro de `numero_radicado` y de la clave alterna), sobre código administrativamente inmutable (cascada nunca dispara en práctica). 0 FK colgante; cascada masiva neutralizada.

## C. Índices nuevos (σ/⋈/π)
- `idx_dependencia_padre (padre_id)` BTREE — ⋈ recursivo organigrama (WITH RECURSIVE) + FK RESTRICT. 🔴
- `idx_dependencia_responsable (responsable_id) WHERE NOT NULL`, `idx_dependencia_sede (sede_id) WHERE NOT NULL` BTREE parcial — ⋈.
- 🔴 **9 índices FK→dependencia faltantes** (BTREE `(id_dependencia)`): canal_atencion, servicio_agendable, mecanismo_participacion, franja_horaria, cita, asignacion_dependencia, traslado_competencia, micrositio, servidor_publico (confirma ítem B del DELTA físico R1).
- `idx_cj_representante (representante_legal_id) WHERE NOT NULL` BTREE parcial.
- `pago`: 🔴 `UNIQUE (id_solicitud) WHERE estado='APROBADO'` (≤1 aprobado, B.11) + `idx_pago_solicitud (id_solicitud)` (listar intentos).
- `respuesta_encuesta (id_pregunta) WHERE NOT NULL` BTREE parcial — solo si P-13.
- 🟢 `respuesta_item_sus`, `respuesta_encuesta`, `ciudadano_juridica`: **PK compuesta/=FK cubre el JOIN, 0 índices extra** (prefijo izquierdo).

## D. Triggers/constraints físicos nuevos
- 🔴 Cobertura ISA `ciudadano` (D.1): constraint trigger DEFERRABLE INITIALLY DEFERRED — `es_persona_juridica=TRUE ⟺ EXISTS(ciudadano_juridica)`, disjoint+parcial.
- 🔴 estado↔existencia 1:1 `solicitud`↔borrador/desistimiento/SAP (D.2): constraint trigger DEFERRED.
- 🔴 `puntaje_sus` (D.3): preferir **vista** `vw_evaluacion_sus_puntaje` (fórmula SUS: impares (r-1), pares (5-r), ×2.5; NULL si COUNT≠10) sobre trigger AFTER (menos escritura).
- 🔴 Anti-ciclo árbol `dependencia` (D.4): CHECK `(padre_id<>id_dependencia)` + trigger recursivo BEFORE (WITH RECURSIVE sube ancestros, rechaza si reaparece).
- 🔴 `cita` CHECK bidireccional de contacto condicional (declarativo, D.5) — elimina transitiva.
- 🟢 `contrato` GENERATED IMMUTABILITY verificada (misma fila ✓); `tiene_otrosi` → vista.

## E. Particiones / volumen
🟢 **Ninguna tabla nueva R2 se particiona.** `dependencia` (catálogo), `ciudadano_juridica` (subconjunto), `respuesta_item_sus` (10×evaluaciones esporádicas), `respuesta_encuesta` (3×encuestas opt-in, medio-bajo), `pregunta_encuesta` (catálogo). `respuesta_encuesta` reevaluable a futuro si las encuestas escalan a millones (RANGE por año alineado con solicitud) — no ahora. PK/FK suficientes.

## F. Aislamiento / concurrencia
- 🟢 Alta de `respuesta_item_sus`/`respuesta_encuesta`: embarazosamente paralela (claves disjuntas por padre), READ COMMITTED, sin contención.
- 🔴 **Reasignación de `dependencia`** (mover nodo): write skew → ciclo por carrera (A→hijo de B || B→hijo de A). **Mitigación: `pg_advisory_xact_lock` global del organigrama** (reorg es rara y administrativa) + trigger anti-ciclo. 
- Cobertura ISA ciudadano / estado↔existencia solicitud: constraint trigger DEFERRED (valida al COMMIT).
- 🟡 Recálculo SUS (si trigger): `FOR UPDATE` sobre evaluacion_sus si concurren UPDATE/DELETE de ítems (marginal); con vista, 0 conflicto.

## G. Vistas nuevas (independencia lógica)
- `vw_cita_contacto`: COALESCE(ciudadano.*, cita.*_contacto) — aísla el contacto condicional. 🔴
- `vw_contrato_ejecucion`: porcentaje/pendientes GENERATED + `tiene_otrosi=EXISTS(otrosi)` — aísla que tiene_otrosi no es base.
- `vw_ciudadano_completo`: ciudadano ⟕ ciudadano_juridica — aísla la class-table ISA.
- `vw_evaluacion_sus_puntaje`: ⋈ respuesta_item_sus + fórmula SUS on-read (NULL si COUNT≠10).
- `vw_directorio_institucional` extendida con WITH RECURSIVE del organigrama (art.9 Ley 1712) sin exponer surrogates.

## H. Veredicto: LISTO para DDL y auditoría final
3 resoluciones físicas que sobrescriben la capa lógica R2 (dentro del mandato C-13): (1) `dependencia` surrogate BIGINT + UNIQUE(codigo), FK BIGINT, ON UPDATE RESTRICT (sin cascada masiva); (2) `ciudadano_juridica` PK=FK al surrogate; (3) `puntaje_sus`/`tiene_otrosi` por vista. Triggers e índices nuevos especificados. Ninguna partición nueva. Pendientes de confirmación (no defectos): P-13 (afecta PK de respuesta_encuesta), enum SECOP `estado_ejecucion`, RLS POLICY por `id_dependencia` (heredado R1, ahora filtra por surrogate BIGINT).

---

# DELTA — Ronda 3 de modelo físico

> **Rol:** DBA senior auditando el diseño físico PostgreSQL 15+ de lo NETO NUEVO de la Ronda 3.
> Insumos leídos íntegros (secciones DELTA R3): `03-modelo-logico.md §R3 (a/b/c)` (5 fichas nuevas, 8 ALTER,
> inconsistencia c.5), `02-normalizacion.md §R3 (A.1 MVD Fagin, B.1–B.5, C derivados)`, `01-dependencias.md §R3
> (FD-PIT/PID/POM/RLC/TSA/MEC/CON/DEP-3, r51–r63)`. PostgreSQL 15+, esquema ÚNICO INTEGRADO.
> 🔴 bloqueante · 🟡 calidad/rendimiento · 🟢 confirmación. Cada índice atado a una operación σ/⋈/π/búsqueda.
>
> **RESOLUCIÓN DE CONFLICTO TRANSVERSAL c.5 (capa lógica R3 vs. capa física R2) — leer primero.**
> El modelo lógico R3 §c.5 ordena re-tipar ~12 FK a `VARCHAR(20) → dependencia(codigo)`. **El físico NO lo
> ejecuta así**: el DELTA físico R2 §A/§B ya resolvió `dependencia` como **clase A** = `id_dependencia BIGINT
> GENERATED ALWAYS AS IDENTITY` PK + `UNIQUE(codigo)`, y las ~11 FK genéricas como **`BIGINT → dependencia(id_dependencia)`
> ON UPDATE RESTRICT**. Esto es independencia física de Codd: la clave natural `codigo` vive en `UNIQUE` (integridad
> de entidad), el surrogate `BIGINT` da FK angostas (8 vs 20 bytes ×11 tablas de alto tráfico), índices B-tree densos
> y **elimina la cascada masiva** al renombrar un código. La capa física manda en C-13. **La instrucción literal de
> c.5 (VARCHAR→codigo) se considera SATISFECHA a nivel lógico por `UNIQUE(codigo)` y NO se materializa como FK.**
> **ÚNICA excepción mantenida (ambas capas coinciden):** `radicado.prefijo_dependencia VARCHAR(20) → dependencia(codigo)`,
> porque el código viaja DENTRO del `numero_radicado` (`SM-{codigo}-AAAA-NNNNNN`, AGN 060/2001) y de la clave alterna.
> Lo mismo `mecanismo_participacion.id_oficina_responsable`: el lógico lo pide VARCHAR(20)→codigo, **pero el físico lo
> alinea a BIGINT→dependencia(id_dependencia)** por coherencia con la otra FK ya existente `mecanismo.id_dependencia`
> (ambas a la misma tabla con el mismo tipo de destino). Ver §(b) ALTER b.3 y §(g) nota de divergencia documentada.

---

## (a) CREATE TABLE — las 5 tablas nuevas de R3

### a.1 🔴 `plan_integracion_tramite` (PIT, C137) — clase F (asociativa con PK natural compuesta)

Decisión de PK (cierra el P-13-análogo del lógico a.1): la fuente admite `id_tramite` NULL (trámite aún no en SUIT).
Con PK natural `{id_plan, id_tramite}` un NULL en la PK es imposible (integridad de entidad, Codd). Por tanto el
físico adopta **surrogate `BIGINT IDENTITY` + UNIQUE parcial**, que es la rama "id_tramite nullable" ya prevista
en a.1 / FD-PIT-1. Dos UNIQUE parciales cubren las dos ramas del CHECK sin colisión.

```sql
CREATE TABLE plan_integracion_tramite (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_plan          BIGINT       NOT NULL,
    id_tramite       BIGINT,                          -- nullable: trámite aún no en catálogo SUIT
    nombre_tramite   VARCHAR(500),                    -- derivado de tramite.nombre si id_tramite NOT NULL; NO persistir en ese caso (norm §C)
    solicitudes_anio INTEGER,
    accion           VARCHAR(20)  NOT NULL,
    fecha_objetivo   DATE,
    id_responsable   BIGINT,
    CONSTRAINT fk_pit_plan       FOREIGN KEY (id_plan)        REFERENCES plan_integracion (id)      ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_pit_tramite    FOREIGN KEY (id_tramite)     REFERENCES tramite (id_tramite)        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pit_responsable FOREIGN KEY (id_responsable) REFERENCES servidor_publico (id)      ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_pit_nombre    CHECK (id_tramite IS NOT NULL OR nombre_tramite IS NOT NULL),       -- FD-PIT-2 condicional
    CONSTRAINT chk_pit_solic     CHECK (solicitudes_anio IS NULL OR solicitudes_anio >= 0),
    CONSTRAINT chk_pit_accion    CHECK (accion IN ('converger','enlazar','retirar'))
);
-- Clave natural rama "trámite en SUIT": un trámite a lo sumo una vez por plan
CREATE UNIQUE INDEX uq_pit_plan_tramite ON plan_integracion_tramite (id_plan, id_tramite) WHERE id_tramite IS NOT NULL;
-- Clave natural rama "trámite fuera de SUIT": el nombre desambigua dentro del plan
CREATE UNIQUE INDEX uq_pit_plan_nombre  ON plan_integracion_tramite (id_plan, nombre_tramite) WHERE id_tramite IS NULL;
```

### a.2 🔴 `plan_integracion_dominio` (PID, C138) — clase F (PK natural compuesta)

```sql
CREATE TABLE plan_integracion_dominio (
    id_plan BIGINT        NOT NULL,
    valor   VARCHAR(2048) NOT NULL,                   -- dominio/URL
    tipo    VARCHAR(30),
    CONSTRAINT pk_pid       PRIMARY KEY (id_plan, valor),
    CONSTRAINT fk_pid_plan  FOREIGN KEY (id_plan) REFERENCES plan_integracion (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_pid_valor CHECK (length(valor) > 0)
);
```
> PK compuesta natural = clave (clase F). El JOIN por `id_plan` (⋈ con la cabecera) lo cubre el **prefijo izquierdo de la PK** → 0 índices extra.

### a.3 🔴 `plan_integracion_otro_medio` (POM, C139) — clase F (PK natural compuesta)

```sql
CREATE TABLE plan_integracion_otro_medio (
    id_plan BIGINT       NOT NULL,
    valor   VARCHAR(500) NOT NULL,                    -- app / chatbot / línea PQR
    tipo    VARCHAR(30),
    CONSTRAINT pk_pom        PRIMARY KEY (id_plan, valor),
    CONSTRAINT fk_pom_plan   FOREIGN KEY (id_plan) REFERENCES plan_integracion (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_pom_valor CHECK (length(valor) > 0),
    CONSTRAINT chk_pom_tipo  CHECK (tipo IS NULL OR tipo IN ('app','chatbot','pqr','otro'))
);
```
> No colapsable con PID: son MVD independientes (norm §A.1, anomalía Fagin). Misma justificación de índice que PID.

### a.4 🔴 `rate_limit_contador` (C140) — clase D atenuada (alto volumen efímero, PK natural compuesta)

`ventana_inicio` y `bloqueado_hasta` son instantes absolutos → **`TIMESTAMPTZ`** (no `TIMESTAMP` naive del lógico:
el throttling cruza husos en un despliegue cloud y debe comparar contra `now()` UTC sin ambigüedad DST).

```sql
CREATE TABLE rate_limit_contador (
    clave_sujeto    VARCHAR(100) NOT NULL,            -- IP (texto) o id_sesion
    ventana_inicio  TIMESTAMPTZ  NOT NULL,            -- bucket temporal (truncado a la ventana)
    recurso         VARCHAR(40)  NOT NULL,
    contador        INTEGER      NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMPTZ,                       -- NULL = no bloqueado
    CONSTRAINT pk_rlc       PRIMARY KEY (clave_sujeto, ventana_inicio, recurso),
    CONSTRAINT chk_rlc_suj  CHECK (length(clave_sujeto) > 0),
    CONSTRAINT chk_rlc_rec  CHECK (recurso IN ('login','pqrsd','busqueda','radicacion','otro')),
    CONSTRAINT chk_rlc_cont CHECK (contador >= 0)
) WITH (fillfactor = 70);                             -- alto UPDATE in-place del contador → HOT updates, menos bloat
```
> `fillfactor=70`: el contador se incrementa muchas veces sobre la MISMA fila dentro de una ventana. Dejar espacio
> libre en página habilita **HOT updates** (PostgreSQL evita reescribir índices), reduciendo bloat en una tabla
> escritura-intensiva. La PK natural (clase F) cubre el lookup de igualdad `(clave_sujeto, ventana_inicio, recurso)`.

### a.5 🔴 `contenido_traduccion` (C135 activada) — clase F (PK natural compuesta), CASCADE desde contenido

```sql
CREATE TABLE contenido_traduccion (
    id_contenido     UUID        NOT NULL,   -- contenido es clase B (UUID PK); FK tipada a UUID (consistencia R3)
    idioma           VARCHAR(10) NOT NULL,            -- BCP-47 o lengua étnica
    titulo_traducido VARCHAR(500),
    cuerpo_traducido TEXT,
    es_original      BOOLEAN     NOT NULL DEFAULT FALSE,
    CONSTRAINT pk_ct        PRIMARY KEY (id_contenido, idioma),
    CONSTRAINT fk_ct_cont   FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_ct_idioma CHECK (idioma ~ '^[a-z]{2,3}(-[A-Za-z0-9]+)?$')
);
-- RN-TX-D05: a lo sumo UNA fila original por contenido (declarativo, no requiere trigger)
CREATE UNIQUE INDEX uq_ct_original ON contenido_traduccion (id_contenido) WHERE es_original = TRUE;
```
> Nota de diseño: el lógico a.5 planteaba la unicidad del original por **trigger**; el físico la resuelve
> **declarativamente** con UNIQUE parcial (más barato, sin código procedural, sin condición de carrera). El trigger
> queda innecesario para este invariante.

---

## (b) ALTER TABLE sobre tablas existentes + resolución física de c.5

### b.1 🔴 `radicado` — FK corregida (cierra P-08) — ÚNICA FK→codigo del esquema

```sql
ALTER TABLE radicado DROP CONSTRAINT IF EXISTS fk_radicado_dependencia_codigo_dependencia;
ALTER TABLE radicado ADD  CONSTRAINT fk_radicado_dependencia
    FOREIGN KEY (prefijo_dependencia) REFERENCES dependencia (codigo)   -- VARCHAR(20) → UNIQUE(codigo)
    ON DELETE RESTRICT ON UPDATE CASCADE;
-- Clave alterna ratificada (AGN 060/2001); consecutivo denso por dependencia+año
ALTER TABLE radicado ADD CONSTRAINT uq_radicado_consecutivo
    UNIQUE (prefijo_dependencia, anio, consecutivo_anual);
```
> `prefijo_dependencia` referencia `dependencia(codigo)` (no `id_dependencia`): el código es parte literal del
> `numero_radicado` y de la clave alterna; `ON UPDATE CASCADE` es seguro porque el código es administrativamente
> inmutable (la cascada nunca dispara en práctica). Es la **única** FK del esquema que apunta a `codigo`.

### b.2 🔴 `incidente_seguridad` — doble destino de reporte (CSIRT vs SIC/RNBD)

```sql
ALTER TABLE incidente_seguridad
    ADD COLUMN sic_rnbd_reportado BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN sic_reporte_fecha  TIMESTAMPTZ,
    ADD CONSTRAINT chk_inc_sic_fecha
        CHECK (sic_rnbd_reportado = FALSE OR sic_reporte_fecha IS NOT NULL);
```
> `TIMESTAMPTZ` (no `TIMESTAMP` del lógico): instante legal de reporte regulatorio. FD-INC-1: dos hechos
> independientes, ambos no-primos directos de la clave → no transitiva, no viola FN.

### b.3 `mecanismo_participacion` — FK al dueño funcional (DIVERGE del lógico, alineada al físico)

```sql
ALTER TABLE mecanismo_participacion
    ADD COLUMN id_oficina_responsable BIGINT,                                  -- BIGINT, NO VARCHAR(20)
    ADD CONSTRAINT fk_mec_oficina
        FOREIGN KEY (id_oficina_responsable) REFERENCES dependencia (id_dependencia)
        ON DELETE RESTRICT ON UPDATE RESTRICT;
```
> **Divergencia deliberada de c.5:** el lógico pidió `VARCHAR(20) → dependencia(codigo)`. El físico usa
> `BIGINT → dependencia(id_dependencia)` por coherencia con la FK preexistente `mecanismo_participacion.id_dependencia`
> (ambas a la misma tabla, mismo tipo de destino surrogate) y con la política clase A (FK 8 bytes, sin cascada).
> Integridad de entidad de `dependencia` garantizada por `UNIQUE(codigo)`. Independencia física de Codd.

### b.4 🔴 `interop_tsa_config` — re-clave 2FN + UNIQUE parcial + endpoint

```sql
ALTER TABLE interop_tsa_config
    ADD COLUMN vigencia_desde DATE         NOT NULL DEFAULT CURRENT_DATE,
    ADD COLUMN host_salida    VARCHAR(255) NOT NULL DEFAULT 'tsa.gse.com.co',
    ADD COLUMN puerto         INTEGER      NOT NULL DEFAULT 443,
    ADD CONSTRAINT chk_tsa_ambiente CHECK (ambiente IN ('QA','PREPROD','PROD')),
    ADD CONSTRAINT chk_tsa_puerto   CHECK (puerto BETWEEN 1 AND 65535);
ALTER TABLE interop_tsa_config ALTER COLUMN proveedor SET DEFAULT 'GSE';
-- Clave natural lógica con histórico de vigencias (corrige 2FN: {ambiente}⁺ ≠ R con histórico)
ALTER TABLE interop_tsa_config ADD CONSTRAINT uq_tsa_ambiente_vig UNIQUE (ambiente, vigencia_desde);
-- 1 config activa por ambiente (RN-10-D02; ratifica DELTA físico R1 ítem 3, ahora con re-clave formal)
CREATE UNIQUE INDEX uq_tsa_activa ON interop_tsa_config (ambiente) WHERE es_activo = TRUE;
-- DECISIÓN pulido #2 R3: url_tsa se DESCOMPONE en host_salida+puerto (3FN: era el endpoint monolítico).
-- Se DROPea la columna base; el endpoint se reconstruye on-read en la vista (host_salida || ':' || puerto).
ALTER TABLE interop_tsa_config DROP COLUMN IF EXISTS url_tsa;
```
> Se conserva el surrogate `id` físico como cómodo (clase B/C), pero la integridad de entidad la da `(ambiente, vigencia_desde)`.
> `es_activo` ≡ `activo` del modelo R1. **`url_tsa` RESUELTA (pulido #2 R3): descompuesta en `host_salida` + `puerto` y DROPeada** (era endpoint monolítico, 3FN). Endpoint reconstruido on-read: `format('https://%s:%s', host_salida, puerto)` en la vista `v_interop_tsa_config` (independencia lógica — ningún consumidor depende de la columna física `url_tsa`).

### b.5 `sede_electronica` — flags de cumplimiento + PETI

```sql
ALTER TABLE sede_electronica
    ADD COLUMN declaracion_conformidad_sic BOOLEAN     NOT NULL DEFAULT FALSE,   -- [INFERIDO] nube extranjera Art.26 Ley 1581
    ADD COLUMN peti_vigencia               VARCHAR(20) DEFAULT '2024-2027',
    ADD COLUMN id_peti_referencia          VARCHAR(100);
```

### b.6 `contenido` — idioma origen (par de contenido_traduccion)

```sql
ALTER TABLE contenido
    ADD COLUMN idioma_origen VARCHAR(10) NOT NULL DEFAULT 'es',
    ADD CONSTRAINT chk_contenido_idioma CHECK (idioma_origen ~ '^[a-z]{2,3}(-[A-Za-z0-9]+)?$');
```

### b.7 `grupo_interes` — caracterización DAFP

```sql
ALTER TABLE grupo_interes
    ADD COLUMN caracterizacion              TEXT,
    ADD COLUMN tiene_caracterizacion_formal BOOLEAN NOT NULL DEFAULT FALSE;
```

### b.8 `dataset` / `registro_activo_informacion` — federación + derivado GENERATED

```sql
ALTER TABLE dataset
    ADD COLUMN actualizacion_automatica BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE registro_activo_informacion
    ADD COLUMN actualizacion_automatica BOOLEAN NOT NULL DEFAULT FALSE;
-- dataset.metadatos_completos: NO persistir como base. NO puede ser GENERATED STORED
-- (depende de OTRA tabla dataset_metadata -> no IMMUTABLE de la misma fila). Resolución: vista (ver §d / §(f) v_dataset_publico).
ALTER TABLE dataset DROP COLUMN IF EXISTS metadatos_completos;
```
> 🔴 **Corrección al lógico b.8:** el lógico ofrecía "columna GENERATED" como opción para `metadatos_completos`.
> Físicamente **imposible**: `GENERATED ALWAYS AS ... STORED` exige expresión IMMUTABLE de la MISMA fila; aquí depende
> de la presencia de filas en `dataset_metadata` (otra tabla). Mismo patrón que `contrato.tiene_otrosi`/`pago.monto`.
> → se elimina la columna base y se computa on-read en vista. No es GENERATED.

### b.9 c.5 — re-tipado/re-apuntado de las ~12 FK→dependencia (RESOLUCIÓN FÍSICA)

> **NO se ejecuta el re-tipado a VARCHAR(20) que pide el lógico c.5.** La política física clase A (DELTA físico R2 §A/§B)
> ya fijó `dependencia` con PK `id_dependencia BIGINT` + `UNIQUE(codigo)`. Las ~11 FK genéricas **permanecen
> `BIGINT → dependencia(id_dependencia)` ON UPDATE RESTRICT**. La instrucción de c.5 queda **satisfecha por
> `UNIQUE(codigo)`** (integridad de entidad de la clave natural) sin tocar las FK. Solo `radicado.prefijo_dependencia`
> (b.1) apunta a `codigo`. Acción de saneamiento de las fichas R1 que aún dicen `→dependencia(id_dependencia)`:
> son **correctas** bajo la política física vigente — el defecto era de las fichas LÓGICAS R1 que decían `codigo_dependencia`
> (columna inexistente). DDL idempotente de verificación/normalización de nombre de constraint:

```sql
-- Tablas con FK genérica a dependencia (surrogate): tramite, pqrsd, canal_atencion, servicio_agendable,
-- mecanismo_participacion, franja_horaria, cita, asignacion_dependencia, traslado_competencia, micrositio, servidor_publico.
-- Patrón aplicado a cada una (ejemplo canal_atencion; replicar por tabla):
ALTER TABLE canal_atencion DROP CONSTRAINT IF EXISTS fk_canal_dependencia;
ALTER TABLE canal_atencion ADD  CONSTRAINT fk_canal_dependencia
    FOREIGN KEY (id_dependencia) REFERENCES dependencia (id_dependencia)   -- BIGINT → surrogate, NO codigo
    ON DELETE RESTRICT ON UPDATE RESTRICT;
-- (idéntico para las 10 restantes: id_dependencia BIGINT → dependencia(id_dependencia), RESTRICT/RESTRICT)
```

---

## (c) Índices nuevos — justificados por operación de álgebra relacional

| Tabla | Índice | Método | Operación | Consulta del dominio |
|---|---|---|---|---|
| `plan_integracion_tramite` | `uq_pit_plan_tramite (id_plan, id_tramite) WHERE id_tramite IS NOT NULL` | BTREE único parcial | σ igualdad + integridad | Clave natural rama SUIT; también sirve ⋈ por `id_plan` (prefijo izq.). |
| `plan_integracion_tramite` | `uq_pit_plan_nombre (id_plan, nombre_tramite) WHERE id_tramite IS NULL` | BTREE único parcial | σ igualdad | Clave natural rama fuera-de-SUIT. |
| `plan_integracion_tramite` | `idx_pit_tramite (id_tramite) WHERE id_tramite IS NOT NULL` | BTREE parcial | ⋈ | FK→tramite (RESTRICT exige índice en la hija); "planes que incluyen el trámite X". |
| `plan_integracion_tramite` | `idx_pit_responsable (id_responsable) WHERE id_responsable IS NOT NULL` | BTREE parcial | ⋈ | FK→servidor_publico; "trámites del plan a cargo de X". |
| `plan_integracion_dominio` | — | — | ⋈ | PK `(id_plan, valor)` cubre el JOIN por `id_plan` (prefijo izq.). 0 índices extra. |
| `plan_integracion_otro_medio` | — | — | ⋈ | Igual que PID; PK compuesta cubre. 0 índices extra. |
| `rate_limit_contador` | PK `(clave_sujeto, ventana_inicio, recurso)` | BTREE (implícito) | σ igualdad | Lookup atómico del bucket en cada request. Cubre el UPSERT. |
| `rate_limit_contador` | `idx_rlc_bloqueo (bloqueado_hasta) WHERE bloqueado_hasta IS NOT NULL` | BTREE parcial | σ rango | Barrido de purga de bloqueos vencidos (`bloqueado_hasta < now()`); índice mínimo (solo filas bloqueadas). |
| `contenido_traduccion` | PK `(id_contenido, idioma)` | BTREE (implícito) | σ igualdad + ⋈ | Resolver traducción por idioma; ⋈ con `contenido` por prefijo izq. |
| `contenido_traduccion` | `uq_ct_original (id_contenido) WHERE es_original = TRUE` | BTREE único parcial | σ igualdad + integridad | Invariante "1 original/contenido"; sirve además al fallback ES (localizar el origen). |
| `interop_tsa_config` | `uq_tsa_ambiente_vig (ambiente, vigencia_desde)` | BTREE único | σ igualdad | Clave natural histórica. |
| `interop_tsa_config` | `uq_tsa_activa (ambiente) WHERE es_activo = TRUE` | BTREE único parcial | σ igualdad | "Config TSA activa del ambiente" (1 fila). |
| `mecanismo_participacion` | `idx_mec_oficina (id_oficina_responsable) WHERE id_oficina_responsable IS NOT NULL` | BTREE parcial | ⋈ | FK→dependencia (RESTRICT exige índice); "mecanismos por oficina responsable". |
| `radicado` | `uq_radicado_consecutivo (prefijo_dependencia, anio, consecutivo_anual)` | BTREE único | σ igualdad + integridad | Clave alterna AGN; ratifica DELTA R1. |

> **Métodos descartados con criterio:** ninguna tabla nueva justifica GIN (no hay full-text/JSONB consultable —
> el JSONB del plan se NORMALIZÓ a filas), HASH (todo lookup de igualdad es UNIQUE → exige B-tree) ni BRIN
> (`rate_limit_contador` es alto volumen pero **efímero y UPDATE-intensivo**, no append-only correlacionado: BRIN
> no aplica; la PK B-tree + purga es lo correcto).

---

## (d) Triggers / GENERATED / derivados

| Mecanismo | Tipo | Regla / origen | Decisión física |
|---|---|---|---|
| `contenido_traduccion` "1 original/contenido" | **UNIQUE parcial** (NO trigger) | RN-TX-D05; FD-CON-1 | `uq_ct_original ... WHERE es_original` resuelve declarativamente; el trigger del lógico a.5 es innecesario. ✅ |
| `mecanismo_participacion.estado='cerrado'` | **Trigger temporal** o vista | FD-MEC-1: `{fecha_cierre_programada, fecha_actual} → estado` | Estado derivado de `now()` → NO GENERATED (no IMMUTABLE). Trigger nocturno `trg_mec_cierre_auto` (BEFORE UPDATE / job) o columna calculada en vista `v_mecanismo_estado`. Recomendado: vista (sin escritura). |
| `plan_integracion_tramite.nombre_tramite` | **Derivado NO materializado** | FD-PIT-2: `id_tramite → tramite.nombre` | NO persistir cuando `id_tramite NOT NULL` (se resuelve por ⋈ con `tramite` en vista); hecho propio NOT NULL solo si `id_tramite NULL`. El CHECK `chk_pit_nombre` garantiza no-nulidad en esa rama. |
| `dataset.metadatos_completos` | **NO base, NO GENERATED → vista** | norm §C; depende de `dataset_metadata` (otra tabla) | Computado on-read: `EXISTS / COUNT` de metadatos obligatorios en vista (ver §f). Columna base eliminada (b.8). |
| `rate_limit_contador` incremento | **UPSERT atómico** (no trigger) | FD-RLC-1 | `INSERT ... ON CONFLICT (clave_sujeto, ventana_inicio, recurso) DO UPDATE SET contador = rate_limit_contador.contador + 1` (ver §e). |

> **Ningún GENERATED STORED nuevo en R3.** Todos los derivados de R3 dependen de otra tabla (`nombre_tramite`,
> `metadatos_completos`) o de `now()` (`mecanismo.estado`) → no son IMMUTABLE de la misma fila → vista/trigger/UPSERT.

---

## (e) Particiones + nivel de aislamiento

### Particiones
| Tabla nueva | ¿Particionar? | Justificación |
|---|---|---|
| `plan_integracion_tramite/_dominio/_otro_medio` | **NO** | Detalle de un catálogo de planes (decenas de planes × pocas filas). Volumen ínfimo. |
| `contenido_traduccion` | **NO** | Acotada por nº de contenidos × idiomas (bajo). |
| `rate_limit_contador` | 🟡 **NO particionar; purga por DELETE/TRUNCATE programado** | Alto volumen PERO **efímero**: las filas mueren al expirar la ventana. Particionar por rango temporal de `ventana_inicio` es defendible para `DROP PARTITION` (purga O(1) vs DELETE costoso), pero la PK natural `{clave_sujeto, ventana_inicio, recurso}` **ya contiene** `ventana_inicio` → una partición RANGE por hora/día sería válida. **Decisión: NO particionar inicialmente** — un job `DELETE WHERE ventana_inicio < now() - intervalo` + `fillfactor=70` (HOT) basta para el volumen distrital; reevaluable a métricas reales (si el DELETE de purga se vuelve caro → migrar a RANGE por día con `DROP PARTITION`, la PK ya lo soporta). |

> **Cero particiones nuevas en R3.** Las 4 particionadas del esquema (log_auditoria, interop_xroad_transaction,
> intento_login, notificacion) no cambian.

### Aislamiento por operación nueva
| Operación crítica | Nivel / mecanismo | Anomalía evitada | Justificación |
|---|---|---|---|
| **Incremento de `rate_limit_contador`** (race en cada request concurrente) | READ COMMITTED + `INSERT ... ON CONFLICT (...) DO UPDATE SET contador = contador + 1` (UPSERT atómico) | **Lost update** del contador (dos requests leen N, ambos escriben N+1) | El UPSERT es atómico a nivel de fila bajo el lock implícito de la PK; el `DO UPDATE` lee el valor más reciente. No requiere elevar aislamiento ni `FOR UPDATE`. La condición de bloqueo se evalúa en la MISMA sentencia. |
| **Activación de config TSA** (cambiar la activa) | READ COMMITTED + `uq_tsa_activa` (UNIQUE parcial) en una tx que desactiva la anterior y activa la nueva | **Dos activas por ambiente** (write skew sobre "1 activa") | El UNIQUE parcial es declarativo: aborta el segundo INSERT/UPDATE que deje dos `es_activo=TRUE` por ambiente. No hace falta SERIALIZABLE. |
| **Marcar original de traducción** | READ COMMITTED + `uq_ct_original` (UNIQUE parcial) | **Dos originales por contenido** | Declarativo (igual patrón). |
| **Alta de filas PIT/PID/POM/traducción** | READ COMMITTED | n/a | Claves disjuntas por `id_plan`/`id_contenido` → inserción embarazosamente paralela, sin contención. |

> Anomalías nuevas cubiertas: **lost update** (contador rate-limit, vía UPSERT atómico), **write skew** (TSA activa,
> original de traducción, vía UNIQUE parcial). Ninguna operación R3 requiere SERIALIZABLE ni lock pesimista.

---

## (f) Vistas nuevas (independencia lógica)

| Vista | π/σ | Aísla de |
|---|---|---|
| `v_dataset_publico` | π(dataset.*, `metadatos_completos` := `EXISTS(metadatos obligatorios en dataset_metadata)`) | Que `metadatos_completos` NO es columna base (computado on-read). |
| `v_plan_integracion_tramite` | `plan_integracion_tramite ⟕ tramite`, π(..., `nombre_tramite := COALESCE(pit.nombre_tramite, tramite.nombre)`) | Que `nombre_tramite` deriva del catálogo cuando hay `id_tramite`. |
| `v_mecanismo_estado` | π(..., `estado := CASE WHEN fecha_cierre_programada <= now() THEN 'cerrado' ELSE estado END`) | Que el cierre es derivado temporal (no dato base). |
| `v_contenido_multilingue` | `contenido ⟕ contenido_traduccion` con fallback ES (`COALESCE(traducción solicitada, original, es)`) | La mecánica de fallback RN-TX-D05. |
| `v_interop_tsa_config` | π(interop_tsa_config.*, `url_tsa` := `format('https://%s:%s', host_salida, puerto)`) | Que `url_tsa` ya NO es columna base (descompuesta en host_salida+puerto, pulido #2 R3); endpoint reconstruido on-read. |

---

## (g) Conteo del delta Ronda 3 del modelo físico

| Categoría | Cantidad | Detalle |
|---|---|---|
| **CREATE TABLE nuevas** | **5** | `plan_integracion_tramite`, `plan_integracion_dominio`, `plan_integracion_otro_medio`, `rate_limit_contador`, `contenido_traduccion` |
| **ALTER TABLE existentes** | **9** | radicado (FK+UNIQUE), incidente_seguridad (+2), mecanismo_participacion (+FK), interop_tsa_config (re-clave+endpoint), sede_electronica (+3), contenido (+1), grupo_interes (+2), dataset/registro_activo (+1, −metadatos_completos), b.9 saneamiento ~11 FK→dependencia |
| **Índices nuevos** | **11** | 2 UNIQUE parciales PIT + idx_pit_tramite + idx_pit_responsable + idx_rlc_bloqueo + uq_ct_original + uq_tsa_ambiente_vig + uq_tsa_activa + idx_mec_oficina + uq_radicado_consecutivo + (PK implícitas no contadas) |
| Índices NO creados (cubiertos por PK compuesta) | 3 | PID, POM, rate_limit (prefijo izq. de la PK cubre ⋈/σ) |
| **UNIQUE parciales nuevos** | **4** | uq_pit_plan_tramite / uq_pit_plan_nombre / uq_ct_original / uq_tsa_activa |
| **GENERATED STORED nuevos** | **0** | todos los derivados R3 dependen de otra tabla o de `now()` → vista/trigger/UPSERT |
| **Triggers nuevos** | **0–1** | `trg_mec_cierre_auto` opcional (preferida vista `v_mecanismo_estado`); el "1 original" se resolvió con UNIQUE parcial (sin trigger) |
| **Particiones nuevas** | **0** | rate_limit_contador → purga + fillfactor=70 (RANGE diferido, reevaluable) |
| **Vistas nuevas** | **4** | v_dataset_publico, v_plan_integracion_tramite, v_mecanismo_estado, v_contenido_multilingue |
| **Aislamiento — operaciones nuevas** | **3** | UPSERT atómico (rate-limit, lost update), UNIQUE parcial (TSA activa + original traducción, write skew); todas READ COMMITTED |
| **Resolución c.5** | RESUELTA | FK→dependencia permanecen `BIGINT→id_dependencia` (UNIQUE(codigo) satisface integridad); única excepción `radicado.prefijo_dependencia→codigo` (b.1); divergencia documentada en `mecanismo.id_oficina_responsable` (b.3) |
| **Correcciones al lógico** | 3 | `dataset.metadatos_completos` NO puede ser GENERATED (depende de otra tabla); "1 original" por UNIQUE parcial (no trigger); TIMESTAMP→TIMESTAMPTZ en rate_limit y sic_reporte_fecha |

**Veredicto Ronda 3 (modelo físico):** 5 CREATE TABLE con tipos PostgreSQL 15+ concretos, PK/FK/UNIQUE/CHECK/DEFAULT
y acciones ON DELETE/UPDATE completas; 9 ALTER (incluida re-clave 2FN de `interop_tsa_config` con UNIQUE parcial,
y el cierre de P-08 en `radicado`); 11 índices nuevos cada uno justificado por σ/⋈/π; 4 UNIQUE parciales; 0 GENERATED
(todos los derivados R3 no son IMMUTABLE de la misma fila); 0 particiones nuevas (rate-limit por purga); 4 vistas de
independencia lógica; aislamiento READ COMMITTED suficiente para las 3 operaciones nuevas (UPSERT atómico + UNIQUE
parcial cubren lost update y write skew). **La inconsistencia c.5 se resuelve a favor de la política física C-13**
(surrogate BIGINT + UNIQUE(codigo)), NO del re-tipado a VARCHAR que pedía la capa lógica — independencia física de Codd.

---

# DELTA — Ronda 3.1 de modelo físico (hallazgos de investigación web)

> Origen: `03-modelo-logico.md §DELTA Ronda 3.1` + `_investigacion-web.md §Ronda 3`. Tipos de FK verificados:
> `ciudadano.id_ciudadano` BIGINT (clase A), `grupo_interes.id` BIGINT (clase A).

## CREATE TABLE — `carpeta_autorizacion_acceso` (C141, norm-backed Anexo 1 SCD)

```sql
CREATE TABLE carpeta_autorizacion_acceso (
    id_autorizacion       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_ciudadano          BIGINT       NOT NULL,
    entidad_autorizada    VARCHAR(255) NOT NULL,
    tipo_entidad          VARCHAR(20),
    conjunto_dato         VARCHAR(150) NOT NULL,
    accion                VARCHAR(20)  NOT NULL,
    fecha_inicio_vigencia DATE         NOT NULL DEFAULT CURRENT_DATE,
    fecha_fin_vigencia    DATE,
    estado                VARCHAR(15)  NOT NULL DEFAULT 'activa',
    fecha_revocacion      TIMESTAMPTZ,
    created_at            TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT fk_caa_ciudadano FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT chk_caa_entidad  CHECK (length(entidad_autorizada) > 0),
    CONSTRAINT chk_caa_tipoent  CHECK (tipo_entidad IS NULL OR tipo_entidad IN ('publica','privada','persona')),
    CONSTRAINT chk_caa_conjunto CHECK (length(conjunto_dato) > 0),
    CONSTRAINT chk_caa_accion   CHECK (accion IN ('consultar','compartir','descargar')),
    CONSTRAINT chk_caa_vigencia CHECK (fecha_fin_vigencia IS NULL OR fecha_fin_vigencia >= fecha_inicio_vigencia),
    CONSTRAINT chk_caa_estado   CHECK (estado IN ('activa','revocada','vencida')),
    CONSTRAINT chk_caa_revoc    CHECK (estado <> 'revocada' OR fecha_revocacion IS NOT NULL)
);
-- Invariante: 1 autorización ACTIVA por (titular, entidad, conjunto)
CREATE UNIQUE INDEX uq_caa_activa ON carpeta_autorizacion_acceso (id_ciudadano, entidad_autorizada, conjunto_dato) WHERE estado = 'activa';
-- FK→ciudadano (RESTRICT exige índice en la hija) + consultas "autorizaciones de X"
CREATE INDEX idx_caa_ciudadano ON carpeta_autorizacion_acceso (id_ciudadano);
-- Purga/expiración: barrido de vigencias vencidas
CREATE INDEX idx_caa_vencimiento ON carpeta_autorizacion_acceso (fecha_fin_vigencia) WHERE estado = 'activa' AND fecha_fin_vigencia IS NOT NULL;
```
> **Anti-IDOR (RN-09-D01):** si el `id_autorizacion` se expone en API externa de la CCD → migrar a `UUID DEFAULT gen_random_uuid()` (clase B). Por defecto BIGINT clase A (registro interno de consentimientos). **Aislamiento:** la revocación/alta usa READ COMMITTED; el UNIQUE parcial `uq_caa_activa` previene dos activas concurrentes (write skew) declarativamente.

## CREATE TABLE — `variable_caracterizacion` (C142) + `grupo_interes_caracterizacion` (C143) [INFERIDO, Guía DAFP v5]

```sql
CREATE TABLE variable_caracterizacion (
    id_variable     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dimension       VARCHAR(20)  NOT NULL,
    nombre_variable VARCHAR(120) NOT NULL,
    aplica_a        VARCHAR(10)  NOT NULL,
    CONSTRAINT chk_vc_dimension CHECK (dimension IN ('geografica','demografica','intrinseca','comportamiento','relacional','organizacional')),
    CONSTRAINT chk_vc_aplica    CHECK (aplica_a IN ('natural','juridica','ambos')),
    CONSTRAINT uq_vc_nombre     UNIQUE (dimension, nombre_variable)
);

CREATE TABLE grupo_interes_caracterizacion (
    id_grupo_interes BIGINT NOT NULL,
    id_variable      BIGINT NOT NULL,
    valor            TEXT,
    CONSTRAINT pk_gic        PRIMARY KEY (id_grupo_interes, id_variable),
    CONSTRAINT fk_gic_grupo  FOREIGN KEY (id_grupo_interes) REFERENCES grupo_interes (id) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT fk_gic_var    FOREIGN KEY (id_variable) REFERENCES variable_caracterizacion (id_variable) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE INDEX idx_gic_variable ON grupo_interes_caracterizacion (id_variable);  -- FK RESTRICT + "grupos con la variable X"
```
> PK `grupo_interes_caracterizacion (id_grupo_interes, id_variable)` cubre el ⋈ con `grupo_interes` por prefijo izq. `grupo_interes.caracterizacion` (R3 b.7) se conserva como resumen narrativo (no redundante). **Condicionada:** poblar solo si se captura caracterización estructurada.

## Conteo R3.1 (físico)
- **CREATE TABLE:** 3 (`carpeta_autorizacion_acceso`, `variable_caracterizacion`, `grupo_interes_caracterizacion`).
- **Índices:** 4 (uq_caa_activa parcial, idx_caa_ciudadano, idx_caa_vencimiento parcial, idx_gic_variable) + PK/UNIQUE implícitas.
- **Particiones:** 0. **GENERATED:** 0. **Total tablas esquema ≈ 149.**

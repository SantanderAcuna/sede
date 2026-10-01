Tengo todo el corpus íntegro. Ahora produzco el modelo lógico final completo. Es un documento extenso con las ~140 tablas, todos sus campos, integridad de entidad/referencial, herencia/polimorfismo y CHECK normativos.

---

# Modelo Lógico — BD Sede Electrónica (Esquema Único Integrado)

> Fase 4 del pipeline jose-bd. Cubre **C3** (modelo relacional lógico, completitud a nivel de campo) y **C8** (herencia/polimorfismo relacional EER). Insumos íntegros leídos: `00-inventario.md` (135 entidades, 612 campos), `01-dependencias.md` + DELTA (FD/MVD/JD, cierres, claves, minimal cover), `02-normalizacion.md` + DELTA (~140 relaciones BCNF/4FN), `_investigacion-web.md` (dominios/CHECK normativos). Marco teórico EXCLUSIVO: modelo relacional (Codd, Date, Maier, Bernstein, Silberschatz). Cero invención; lo inferido marcado `[INFERIDO]`; trazabilidad a `archivo:línea`/FD/`[WEB:...]`/`[LEY:...]`.
>
> **Cobertura de extracción:** 4 de 4 insumos leídos íntegros, 0 omitidos.

---

## 0. Convenciones y decisiones globales

### 0.1 Notación de la ficha de tabla
Cada tabla se presenta con columnas: `columna | tipo genérico | NULL | PK | FK→tabla(col) | UNIQUE | CHECK | DEFAULT | traza`. Tipos genéricos: `INTEGER`, `BIGINT`, `SMALLINT`, `VARCHAR(n)`, `TEXT`, `DECIMAL(p,s)`, `DATE`, `TIMESTAMP` (TIMESTAMPTZ = "Hora Legal"), `BOOLEAN`, `UUID`, `INET`, `BYTEA`, `JSONB`. La materialización física exacta (PostgreSQL 15+, índices, particiones) se difiere a C13/Fase física (mandato C13 diferido a físico).

### 0.2 PK natural vs surrogate (C-13, decisión lógica)
El análisis de dependencias justificó **claves naturales lógicas** (cierre `X⁺=R` demostrado). Política:
- Donde existe **clave natural total y obligatoria** se declara como PK lógica (ej. `tramite.codigo_suit`, `ciudadano.{id_tipo_documento,numero_documento}`, `radicado.numero_radicado`, `calendario_habil.fecha`).
- **C-13 (UUID vs BIGINT)** es decisión FÍSICA. En este modelo lógico, cuando una entidad usa surrogate, se declara `id` como PK **y** se agrega `UNIQUE` sobre la clave natural (integridad de entidad de Codd preservada). Notación: `id (surrogate)` con su `UNIQUE` natural al lado.
- **4 entidades sin clave natural** (cierre `X⁺≠R` para toda combinación): `documento_electronico`, `notificacion`, `firma_electronica`, `interop_xroad_transaction` → surrogate **lógicamente necesario** + UNIQUE defensivo (DELTA §E). No es conveniencia.
- Para FK, este modelo usa el surrogate `id` del propietario cuando éste lo tiene (evita FK compuestas anchas); cuando el propietario tiene PK natural atómica (`codigo_suit`, `numero_radicado`, `serial_number`, `fecha`) la FK referencia esa columna natural.

### 0.3 Acciones referenciales — política por semántica
- **RESTRICT**: catálogos y entidades maestras cuyo borrado no debe propagar silenciosamente (`tramite`, `dependencia`, `tipo_pqrsd`, `tipo_documento_identidad`, `rol`, `impuesto`, `servidor_publico`). Protege integridad histórica/legal.
- **CASCADE**: composición fuerte (parte-de) donde la parte no existe sin el todo (`otrosi`→`contrato`, `consentimiento_categoria`→`consentimiento_datos`, `horario_canal`→`canal_atencion`, subtipos ISA→supertipo, `item_evaluacion_sus`→`evaluacion_sus`, `dataset_formato`/`dataset_metadata`→`dataset`).
- **SET NULL**: asociaciones opcionales/auditoría donde la referencia puede desaparecer pero el registro persiste (`log_auditoria.id_usuario_interno` tras baja segura RN-12-D02; FK opcionales de "responsable"/"aprobado_por").
- **Append-only** (`log_auditoria`, `consentimiento_datos`): nunca CASCADE de borrado (no hay borrado); FK salientes con SET NULL.
- `ON UPDATE`: **RESTRICT** por defecto sobre PK naturales mutables prohibidas; **CASCADE** solo si la PK natural pudiera cambiar legalmente (no ocurre con `codigo_suit`/`numero_radicado` que son inmutables legales).

### 0.4 Derivados NO materializados (transversal — C3/normalización §6.4 + DELTA A.1)
No se almacenan como columna base independiente (violarían 3FN/BCNF). Se declaran como **GENERATED ALWAYS AS … STORED** o CHECK o vista: `tramite.es_gratuito`, `solicitud.requiere_pago`, `pago.monto`, `ciudadano.is_minor`, `documento_electronico.es_valido`, `pqrsd.cumplimiento_plazo`, `evaluacion_sus.puntaje_sus`, `solicitud_arco.deadline_at`, `contrato.porcentaje_ejecutado` (🔴 GENERATED), `contrato.tiene_otrosi` (calculado). Única desnormalización controlada: `franja_horaria.cupos_disponibles` (lock pesimista, RN-06-D01).

### 0.5 Entidades nuevas / correcciones bloqueantes integradas
- 🔴 **`dependencia` CREADA** (§1.1) — organigrama institucional, destino de FK masivo, no existía como entidad canónica.
- 🔴 `impuesto` PK `{nombre, vigencia_desde}`; `calendario_tributario.id_impuesto` FK coherente.
- 🔴 `contrato.porcentaje_ejecutado` GENERATED; `tiene_otrosi` calculado.
- 🔴 Resolución circular `normativa`↔`proyecto_norma` (§4.7).
- 🔴 Derivados generados/CHECK (§0.4); `franja_horaria.cupos_disponibles` desnormalización con lock.

---

## 1. Entidades núcleo (tabla por tabla, todos los campos)

### 1.1 🔴 `dependencia` — NUEVA (organigrama institucional de la Alcaldía) — RECONCILIADA R3 (c.5)
**Propósito:** Unidad organizacional del Distrito (secretarías, oficinas, direcciones). Destino de FK masivo (`tramite`, `pqrsd`, `canal_atencion`, `servidor_publico`, `servicio_agendable`, `asignacion_dependencia`, `traslado_competencia`, `micrositio`). Derivada del Directorio Institucional / organigrama de transparencia (módulos 02, 12; ITA nivel 1 estructura orgánica — `_investigacion-web` H-03/H-06 [LEY: Ley 1712/2014 art.9 lit.a,c]). El DELTA normalización §F.1 la declara bloqueante por integridad referencial rota.
> **SANEAMIENTO c.5 (auditoría Ronda 3):** definición vigente y única = la de esta ficha, alineada con el físico clase A (DELTA físico R2 §A/§B): **PK surrogate `id_dependencia BIGINT IDENTITY` + clave natural `codigo` UNIQUE** (antes nombrada `codigo_dependencia` — renombrada a `codigo` para coincidir con el físico). Toda FK genérica entrante apunta a `dependencia(id_dependencia)` (BIGINT); la ÚNICA excepción es `radicado.prefijo_dependencia → dependencia(codigo)` (el código viaja dentro del número de radicado, AGN 060/2001). Se elimina la divergencia que hacía coexistir dos definiciones de `dependencia`.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_dependencia | BIGINT | NO | PK | — | — | — | identity | FK masivo (inv. L278,327,551,591...); DELTA-norm §F.1 |
| codigo | VARCHAR(20) | NO | — | — | UNIQUE | — | — | [INFERIDO] prefijo radicado SM-{dep} (H-08); base de `radicado.prefijo_dependencia` (renombrada de `codigo_dependencia`, c.5 R3) |
| nombre | VARCHAR(300) | NO | — | — | UNIQUE | — | — | [WEB:H-06] directorio art.9; [INFERIDO] nombre único |
| sigla | VARCHAR(30) | SÍ | — | — | — | — | — | [INFERIDO] organigrama; usada en `member_code` RN-B3-032 |
| tipo_unidad | VARCHAR(40) | NO | — | — | — | CHECK ∈ (secretaria, oficina, direccion, subdireccion, despacho, grupo, otro) | 'secretaria' | [INFERIDO] organigrama art.9 lit.a |
| id_dependencia_padre | BIGINT | SÍ | — | →dependencia(id_dependencia) | — | CHECK (id_dependencia_padre <> id_dependencia) | — | [INFERIDO] jerarquía organigrama (adjacency list) |
| id_responsable | BIGINT | SÍ | — | →servidor_publico(id) | — | — | — | I-25 (inv. L868) responsable por dependencia |
| correo_institucional | VARCHAR(254) | SÍ | — | — | — | — | — | [WEB:H-06] directorio art.9 lit.c (correo) |
| telefono | VARCHAR(20) | SÍ | — | — | — | CHECK formato +57 (RN-B1-017) | — | [WEB:H-06] art.9 lit.c (teléfono) |
| ubicacion_fisica | VARCHAR(500) | SÍ | — | — | — | — | — | [WEB:H-06] art.9 lit.a (ubicación sedes) |
| horario_atencion | TEXT | SÍ | — | — | — | — | — | [WEB:H-06] art.9 lit.a (horas atención) |
| activa | BOOLEAN | NO | — | — | — | — | TRUE | [INFERIDO] estado lógico |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | I-03 auditoría |
| updated_at | TIMESTAMP | NO | — | — | — | — | now() | I-03 auditoría |

> PK lógica: surrogate `id_dependencia` (clave natural = `codigo` UNIQUE; `nombre` UNIQUE alterna). Jerarquía organigrama = adjacency list por `id_dependencia_padre` (mismo patrón que `trd_serie` H10).

### 1.2 `sede_electronica` (C01) — inv. L231-247
**Propósito:** Portal único del Distrito (RN-B3-004: una sede por entidad).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | BIGINT | NO | PK | — | — | — | identity | I-01 (L234) |
| url_original | VARCHAR(2048) | NO | — | — | UNIQUE | — | — | 01§7 L235; FD-C01-1 |
| palabra_clave | VARCHAR(255) | NO | — | — | UNIQUE | — | — | RF-B2-001 L236; FD-C01-2 |
| url_enmascarada_gov | VARCHAR(2048) | SÍ | — | — | UNIQUE | CHECK formato `https://www.gov.co/{palabra_clave}` | — | L237; FD-C01-3 |
| nombre_institucion | VARCHAR(500) | NO | — | — | — | — | — | L238 |
| categoria | VARCHAR(100) | NO | — | — | — | — | — | L239; C-10 resuelto NOT NULL |
| sector | VARCHAR(100) | SÍ | — | — | — | — | — | L240 |
| estado_integracion | VARCHAR(50) | NO | — | — | — | CHECK ∈ (pendiente, en_proceso, integrada, activa, suspendida, desactivada) | 'pendiente' | L241; C-11 unión |
| paso_proceso_actual | SMALLINT | SÍ | — | — | — | CHECK BETWEEN 1 AND 7 | — | RF-B1-070 L242 |
| fecha_activacion | DATE | SÍ | — | — | — | — | — | L243 |
| soporte_ipv4 | BOOLEAN | NO | — | — | — | — | TRUE | RF-B1-099 L244 [I-01] |
| soporte_ipv6 | BOOLEAN | NO | — | — | — | — | FALSE | RF-B1-099 L245 [I-01] |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | L246 [INFERIDO] |
| updated_at | TIMESTAMP | NO | — | — | — | — | now() | L247 [INFERIDO] |

### 1.3 `tramite` (C16, catálogo SUIT) — inv. L249-280
**Propósito:** Ficha del trámite/OPA/Consulta del catálogo SUIT (entidad de catálogo, ≠ `solicitud`). Discriminador ISA `tipo_servicio` (H3).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_tramite | BIGINT | NO | PK | — | — | — | identity | L252 (surrogate; nat=codigo_suit) |
| codigo_suit | VARCHAR(20) | NO | — | — | UNIQUE | CHECK formato `T{código}` | — | L253; FD-C16-1 |
| nombre | VARCHAR(500) | NO | — | — | — | — | — | L254 |
| tipo_servicio | VARCHAR(30) | NO | — | — | — | CHECK ∈ (TRAMITE, OPA, CONSULTA) | — | L255; discriminador ISA H3 |
| modalidad | VARCHAR(30) | NO | — | — | — | CHECK ∈ (TOTALMENTE_EN_LINEA, PARCIAL, PRESENCIAL) | — | RF-B1-021 L256 |
| descripcion | TEXT | NO | — | — | — | — | — | RF-B2-029 L257 |
| requisitos | TEXT | NO | — | — | — | — | — | L258 |
| pasos_procedimiento | TEXT | NO | — | — | — | — | — | L259 |
| costo | DECIMAL(18,2) | NO | — | — | — | CHECK costo >= 0 | 0 | RF-B1-021 L260 |
| es_gratuito | BOOLEAN | NO | — | — | — | GENERATED ALWAYS AS (costo = 0) STORED | — | L261; FD-C16-2 (derivado §0.4) |
| tiempo_resolucion_dias | INTEGER | NO | — | — | — | CHECK > 0 | — | L262 |
| resultado_esperado | TEXT | NO | — | — | — | — | — | L263 |
| grupo_objetivo | VARCHAR(200) | SÍ | — | — | — | — | — | L264 |
| nivel_transformacion | SMALLINT | NO | — | — | — | CHECK BETWEEN 1 AND 6 | — | L265 (DAFP) |
| url_digital | VARCHAR(500) | SÍ | — | — | — | — | — | L266 |
| georreferenciacion | JSONB | SÍ | — | — | — | — | — | L267 |
| estado_estandarizado | VARCHAR(30) | NO | — | — | — | CHECK ∈ (ACTIVO, EN_DIGITALIZACION, SUSPENDIDO, SUPRIMIDO) | 'EN_DIGITALIZACION' | L268; RN-B2-002 |
| nivel_autenticacion_requerido | VARCHAR(20) | NO | — | — | — | CHECK ∈ (BAJO, MEDIO, ALTO, MUY_ALTO) | 'BAJO' | L269; [WEB:H-02] Decreto 620/2020 |
| aplica_sap | BOOLEAN | NO | — | — | — | — | FALSE | RN-03-D08 L270 |
| termino_sap_dias | INTEGER | SÍ | — | — | — | CHECK (aplica_sap = FALSE OR termino_sap_dias IS NOT NULL) | — | L271 (cond. FD-C16-3) |
| tipo_silencio_administrativo | VARCHAR(20) | NO | — | — | — | CHECK ∈ (negativo, positivo) | 'negativo' | L272; RN-03-D08 |
| id_bloque_digitalizacion | BIGINT | SÍ | — | →bloque_digitalizacion(id) | — | — | — | RF-B3-150 L273 |
| id_fase_digitalizacion_actual | BIGINT | SÍ | — | →fase_digitalizacion(id) | — | — | — | RF-B3-149 L274 |
| solicitudes_por_anio | INTEGER | SÍ | — | — | — | CHECK >= 0 | — | L275 |
| fecha_ultima_actualizacion_suit | DATE | NO | — | — | — | — | — | RN-B1-006 L276 |
| requiere_concepto_dafp | BOOLEAN | NO | — | — | — | — | FALSE | RN-B2-003 L277 |
| id_dependencia | BIGINT | SÍ | — | →dependencia(id_dependencia) | — | — | — | L278; FK 🔴 |
| version_ficha | INTEGER | NO | — | — | — | CHECK >= 1 | 1 | L279; I-07 |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | L280 [INFERIDO] |
| updated_at | TIMESTAMP | NO | — | — | — | — | now() | L280 [INFERIDO] |

**FK acciones:** `id_dependencia` ON DELETE RESTRICT ON UPDATE CASCADE; `id_bloque/id_fase` ON DELETE SET NULL.

### 1.4 `solicitud` (C20, instancia/radicado) — inv. L282-310
**Propósito:** Ejecución concreta de un trámite por un ciudadano; portadora del ciclo de vida y del radicado.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_solicitud | BIGINT | NO | PK | — | — | — | identity | L285 (nat=numero_radicado) |
| numero_radicado | VARCHAR(50) | NO | — | →radicado(numero_radicado) | UNIQUE | CHECK `^SM-[A-Z0-9]+-[0-9]{4}-[0-9]{6}$` | — | L286; [WEB:H-08] |
| id_tramite | BIGINT | NO | — | →tramite(id_tramite) | — | — | — | L287; FK |
| id_ciudadano | BIGINT | SÍ | — | →ciudadano(id_ciudadano) | — | — | — | L288 (NULL si anónimo) |
| id_sesion | UUID | SÍ | — | →sesion(id) | — | — | — | L289 |
| fecha_hora_radicacion | TIMESTAMP | NO | — | — | — | — | — | L290 (Hora Legal+TSA) RN-TX-D02 |
| estado | VARCHAR(40) | NO | — | — | — | CHECK ∈ (BORRADOR, RADICADO, RECIBIDO, EN_TRAMITE, REQUIERE_SUBSANACION, SUBSANADO, EN_ESPERA_PAGO, RECHAZADO_PAGO, RESUELTO, DESISTIDO, ARCHIVADO, SILENCIO_POSITIVO) | 'BORRADOR' | L291; C-12 unión |
| etapa_actual | SMALLINT | NO | — | — | — | CHECK BETWEEN 1 AND 4 | 1 | RF-B2-028 L292 |
| tiempo_estimado_resolucion | INTEGER | SÍ | — | — | — | CHECK > 0 | — | L293 |
| fecha_vencimiento | DATE | SÍ | — | — | — | — | — | L294; RN-TX-D01 (calendario_habil) |
| datos_formulario | JSONB | NO | — | — | — | — | — | RF-B2-030 L295 |
| autorizo_datos_personales | BOOLEAN | NO | — | — | — | CHECK (estado='BORRADOR' OR autorizo_datos_personales=TRUE) | FALSE | L296 Ley 1581 |
| acepto_terminos | BOOLEAN | NO | — | — | — | CHECK (estado='BORRADOR' OR acepto_terminos=TRUE) | FALSE | L297 |
| clave_idempotencia | VARCHAR(128) | NO | — | — | UNIQUE | — | — | L298; RN-03-D01; FD-C20-2 |
| canal_ingreso | VARCHAR(30) | NO | — | — | — | CHECK ∈ (EN_LINEA, PRESENCIAL_ASISTIDO) | 'EN_LINEA' | RF-B1-095 L299 |
| requiere_pago | BOOLEAN | SÍ | — | — | — | (derivado vista: tramite.costo>0) §0.4 | — | L300; FD-C20-3 (NO materializado) |
| fecha_desistimiento | TIMESTAMP | SÍ | — | — | — | CHECK (estado<>'DESISTIDO' OR fecha_desistimiento IS NOT NULL) | — | L301; RN-03-D07 |
| motivo_desistimiento | TEXT | SÍ | — | — | — | — | — | L302 |
| fecha_resolucion | TIMESTAMP | SÍ | — | — | — | — | — | RF-B2-032 L303 |
| efecto_sap | BOOLEAN | SÍ | — | — | — | — | FALSE | RN-03-D08 L304 |
| fecha_sap_aplicado | TIMESTAMP | SÍ | — | — | — | CHECK (efecto_sap=FALSE OR fecha_sap_aplicado IS NOT NULL) | — | L305 |
| es_borrador | BOOLEAN | NO | — | — | — | — | TRUE | RF-03-D03 L306 |
| paso_actual_borrador | SMALLINT | SÍ | — | — | — | CHECK (es_borrador=FALSE OR paso_actual_borrador IS NOT NULL) | — | L307 |
| fecha_expiracion_borrador | TIMESTAMP | SÍ | — | — | — | — | created_at + INTERVAL '30 days' | L308; G4§4.4#1; [P-19 confirmar] |
| inicio_gestion | BOOLEAN | NO | — | — | — | — | FALSE | L309; G1§8#18 (reembolso) |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | L310 [INFERIDO] |
| updated_at | TIMESTAMP | NO | — | — | — | — | now() | L310 [INFERIDO] |

**FK acciones:** `numero_radicado`/`id_tramite` ON DELETE RESTRICT; `id_ciudadano` ON DELETE SET NULL (anonimización); `id_sesion` ON DELETE SET NULL.

### 1.5 `radicado` (C39) — inv. L341-357
**Propósito:** Número único de radicación atómico (PQRSD y documentos); consecutivo anual por dependencia + prefijo (RNF-04-D01/D02; [WEB:H-08] AGN Acuerdo 060/2001).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_radicado | BIGINT | NO | PK | — | — | — | identity | L344 (nat=numero_radicado) |
| numero_radicado | VARCHAR(50) | NO | — | — | UNIQUE | CHECK `^SM-[A-Z0-9]+-[0-9]{4}-[0-9]{6}$` | — | L345; [WEB:H-08] |
| prefijo_dependencia | VARCHAR(20) | NO | — | →dependencia(codigo) | — | — | — | 🔴 DELTA-dep C.1; clave alt. (saneado c.5 R3: codigo, no codigo_dependencia) |
| consecutivo_anual | BIGINT | NO | — | — | — | CHECK > 0 | (SEQUENCE) | L346; UNIQUE compuesto abajo |
| anio | SMALLINT | NO | — | — | — | — | — | L347 [INFERIDO] |
| fecha_hora_radicacion | TIMESTAMP | NO | — | — | — | — | — | RN-B3-026 L348 (Hora Legal) |
| emisor_nombre | VARCHAR(300) | NO | — | — | — | — | — | L349 |
| emisor_correo | VARCHAR(254) | SÍ | — | — | — | — | — | L350 |
| id_destinatario_interno | BIGINT | SÍ | — | →usuario_interno(id) | — | — | — | L351 |
| destinatario_externo | VARCHAR(300) | SÍ | — | — | — | — | — | L352 [I-09] |
| tipo_documento | VARCHAR(100) | NO | — | — | — | — | — | L353 |
| canal_recepcion | VARCHAR(30) | NO | — | — | — | CHECK ∈ (web, correo, presencial, app) | — | L354 |
| acuse_enviado | BOOLEAN | NO | — | — | — | — | FALSE | L355 |
| fecha_acuse | TIMESTAMP | SÍ | — | — | — | — | — | L356 |
| id_expediente | BIGINT | SÍ | — | →expediente_electronico(id) | — | — | — | L357 |

**Clave alterna compuesta:** `UNIQUE (prefijo_dependencia, anio, consecutivo_anual)` 🔴 (DELTA C.1, consecutivo por dependencia+año). **FK acciones:** `prefijo_dependencia` ON DELETE RESTRICT ON UPDATE CASCADE; `id_destinatario_interno` ON DELETE SET NULL; `id_expediente` ON DELETE SET NULL.

### 1.6 `pqrsd` (C32) — inv. L312-337
**Propósito:** Petición/Queja/Reclamo/Sugerencia/Denuncia/Solicitud de información. Subtipo conductual por `id_tipo_pqrsd` (H4).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_pqrsd | BIGINT | NO | PK | — | — | — | identity | L315 (nat=id_radicado) |
| id_tipo_pqrsd | BIGINT | NO | — | →tipo_pqrsd(id_tipo_pqrsd) | — | — | — | RF-B1-031 L316 |
| es_anonima | BOOLEAN | NO | — | — | — | CHECK (NOT (es_anonima AND id_tipo_pqrsd IN (acceso_informacion))) [RN-B1-009] | FALSE | L317; RN-B1-009 |
| es_identidad_reservada | BOOLEAN | NO | — | — | — | — | FALSE | RN-04-D06 L318 |
| id_ciudadano | BIGINT | SÍ | — | →ciudadano(id_ciudadano) | — | — | — | L319 (NULL si anónima) |
| nombre_razon_social | VARCHAR(300) | SÍ | — | — | — | CHECK (es_anonima OR nombre_razon_social IS NOT NULL) | — | L320 |
| id_tipo_documento | BIGINT | SÍ | — | →tipo_documento_identidad(id) | — | CHECK (es_anonima OR id_tipo_documento IS NOT NULL) | — | L321 |
| numero_documento | VARCHAR(30) | SÍ | — | — | — | — | — | L322 |
| correo | VARCHAR(254) | NO | — | — | — | CHECK formato RFC 5322 | — | L323 (NOT NULL aun anónima) |
| telefono | VARCHAR(20) | SÍ | — | — | — | — | — | L324 |
| direccion_notificacion | TEXT | SÍ | — | — | — | — | — | L325 |
| canal_respuesta | VARCHAR(50) | NO | — | — | — | CHECK ∈ (correo, SMS, correo_certificado, fisico, edicto) | — | RN-04-D05 L326 |
| id_dependencia | BIGINT | NO | — | →dependencia(id_dependencia) | — | — | — | L327; FK 🔴 |
| objeto | VARCHAR(2000) | NO | — | — | — | CHECK length(objeto) <= 2000 | — | L328 |
| acepta_condiciones | BOOLEAN | NO | — | — | — | CHECK acepta_condiciones = TRUE | — | L329 |
| acepta_privacidad | BOOLEAN | NO | — | — | — | CHECK acepta_privacidad = TRUE | — | L330 |
| id_radicado | BIGINT | NO | — | →radicado(id_radicado) | UNIQUE | — | — | L331; RN-04-D07; FD-C32-1 |
| estado | VARCHAR(30) | NO | — | — | — | CHECK ∈ (radicada, en_tramite, prorrogada, trasladada, respondida, cerrada) | 'radicada' | L332 |
| fecha_hora_recepcion | TIMESTAMP | NO | — | — | — | — | — | RF-B3-099 L333 |
| fecha_estimada_respuesta | DATE | SÍ | — | — | — | — | — | L334; RN-04-D01 (calendario_habil) |
| cumplimiento_plazo | VARCHAR(20) | SÍ | — | — | — | (derivado §0.4: dentro_termino/fuera_termino) | — | L335; RN-04-D04 NO materializado |
| id_expediente | BIGINT | SÍ | — | →expediente_electronico(id) | — | — | — | L336 |
| ip_origen | INET | SÍ | — | — | — | — | — | L337 [I-08] |

**FK acciones:** `id_tipo_pqrsd`/`id_dependencia`/`id_radicado` ON DELETE RESTRICT; `id_ciudadano`/`id_tipo_documento`/`id_expediente` ON DELETE SET NULL.

### 1.7 `ciudadano` (C62) — inv. L359-387. Supertipo de actor externo; ISA H2 (persona natural/jurídica).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_ciudadano | BIGINT | NO | PK | — | — | — | identity | L362 (nat={tipo_doc,num_doc}) |
| id_tipo_documento | BIGINT | NO | — | →tipo_documento_identidad(id) | UQ(tipo,num) | — | — | L363 |
| numero_documento | VARCHAR(30) | NO | — | — | UQ(tipo,num) | — | — | L364; RF-B3-074 |
| nombre_completo | VARCHAR(300) | SÍ | — | — | — | — | — | L365 (NULL si anónimo) |
| correo | VARCHAR(254) | SÍ | — | — | UNIQUE | CHECK formato RFC 5322 | — | L366 (login) |
| telefono | VARCHAR(30) | SÍ | — | — | — | — | — | L367 |
| direccion | VARCHAR(500) | SÍ | — | — | — | — | — | L368 |
| nivel_confianza | VARCHAR(20) | SÍ | — | — | — | CHECK ∈ (BAJO, MEDIO, ALTO, MUY_ALTO) | — | L369; [WEB:H-02] |
| auth_source | VARCHAR(20) | NO | — | — | — | CHECK ∈ (local, scd_oidc) | 'local' | L370 |
| scd_sub | VARCHAR(255) | SÍ | — | — | UNIQUE (parcial WHERE auth_source='scd_oidc') | — | — | L371 |
| identificador_scd | VARCHAR(100) | SÍ | — | — | UNIQUE | — | — | L372 |
| biometric_enrolled | BOOLEAN | NO | — | — | — | — | FALSE | L373 |
| ani_validated | BOOLEAN | NO | — | — | — | — | FALSE | RF-B3-106 L374 |
| ani_validated_at | TIMESTAMP | SÍ | — | — | — | — | — | L375 |
| es_persona_juridica | BOOLEAN | NO | — | — | — | — | FALSE | L376; discriminador ISA H2 [INFERIDO] |
| razon_social | VARCHAR(300) | SÍ | — | — | — | CHECK (NOT es_persona_juridica OR razon_social IS NOT NULL) | — | L377; FD-C62-5 |
| representante_legal_id | BIGINT | SÍ | — | →ciudadano(id_ciudadano) | — | — | — | L378 (menor/jurídica) |
| fecha_nacimiento | DATE | SÍ | — | — | — | — | — | L379 |
| is_minor | BOOLEAN | NO | — | — | — | GENERATED ALWAYS AS (fecha_nacimiento IS NOT NULL AND fecha_nacimiento > current_date - INTERVAL '18 years') STORED | — | L380; FD-C62-4 (derivado §0.4) |
| estado_cuenta | VARCHAR(20) | NO | — | — | — | CHECK ∈ (activa, suspendida, eliminada_logica, pendiente_autorizacion) | 'activa' | L381; I-21 |
| canal_notificacion_preferido | VARCHAR(20) | SÍ | — | — | — | CHECK ∈ (correo, CCD, SMS, APP) | — | L382 |
| direccion_procesal_electronica | VARCHAR(254) | SÍ | — | — | — | — | — | L383 |
| failed_login_count | INTEGER | NO | — | — | — | CHECK >= 0 | 0 | L384; RF-B1-062 |
| locked_until | TIMESTAMP | SÍ | — | — | — | — | — | L385 |
| id_version_politica | BIGINT | SÍ | — | →politica_documento(id) | — | — | — | L386; G1#122 |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | L387 |
| updated_at | TIMESTAMP | NO | — | — | — | — | now() | L387 |

**FK acciones:** `id_tipo_documento` ON DELETE RESTRICT; `representante_legal_id` ON DELETE SET NULL ON UPDATE CASCADE (self-ref); `id_version_politica` ON DELETE SET NULL.

### 1.8 `usuario_interno` (C61) — inv. L389-411

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | UUID | NO | PK | — | — | — | gen | L392 (nat={tipo_doc,num_doc}) |
| id_tipo_documento | BIGINT | NO | — | →tipo_documento_identidad(id) | UQ(tipo,num) | — | — | L393 |
| numero_documento | VARCHAR(20) | NO | — | — | UQ(tipo,num) | — | — | L394 |
| nombre_completo | VARCHAR(200) | NO | — | — | — | — | — | L395 |
| correo | VARCHAR(254) | NO | — | — | UNIQUE | CHECK formato RFC 5321 | — | L396 |
| contrasena_hash | VARCHAR(255) | NO | — | — | — | — | — | L397 [I-02] bcrypt/argon2 |
| contrasena_temporal | BOOLEAN | NO | — | — | — | — | TRUE | L398 |
| mfa_habilitado | BOOLEAN | NO | — | — | — | — | FALSE | L399; RN-09-D04 |
| estado | VARCHAR(20) | NO | — | — | — | CHECK ∈ (activo, suspendido, dado_de_baja) | 'activo' | L400; RF-12-D02 |
| is_active | BOOLEAN | NO | — | — | — | — | TRUE | L401 |
| failed_login_count | INTEGER | NO | — | — | — | CHECK >= 0 | 0 | L402 |
| locked_until | TIMESTAMP | SÍ | — | — | — | — | — | L403 |
| deactivated_at | TIMESTAMP | SÍ | — | — | — | — | — | L404; RN-12-D02 |
| acepto_tyc | BOOLEAN | NO | — | — | — | CHECK acepto_tyc = TRUE | — | L405; RF-B1-079 |
| fecha_acepto_tyc | TIMESTAMP | NO | — | — | — | — | — | L406 (Hora Legal) |
| id_firma_registro | UUID | SÍ | — | →firma_electronica(id) | — | — | — | L407 |
| fecha_nacimiento | DATE | SÍ | — | — | — | — | — | L408 |
| id_sigep | VARCHAR(50) | SÍ | — | →servidor_publico(codigo_sigep) | UNIQUE (parcial) | — | — | L409; I-23; C-09 cruce |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | L410 |
| updated_at | TIMESTAMP | NO | — | — | — | — | now() | L411 |

**FK acciones:** `id_tipo_documento` ON DELETE RESTRICT; `id_firma_registro` ON DELETE SET NULL; `id_sigep` ON DELETE SET NULL.

### 1.9 `documento_electronico` (C113) — inv. L437-467. Supertipo de adjuntos + polimorfismo (arco exclusivo, H6). Sin clave natural → surrogate + UNIQUE defensivo (§0.2).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | UUID | NO | PK | — | UQ_def | — | gen | L442 |
| id_expediente | UUID | SÍ | — | →expediente_electronico(id) | — | arco | — | L443 |
| id_solicitud | BIGINT | SÍ | — | →solicitud(id_solicitud) | — | arco | — | L444 |
| id_pqrsd | BIGINT | SÍ | — | →pqrsd(id_pqrsd) | — | arco | — | L445 |
| contexto | VARCHAR(20) | NO | — | — | — | CHECK ∈ (TRAMITE, PQRSD, EXPEDIENTE, RESULTADO) + arco exclusivo (§4.2) | — | L446; C-06 |
| nombre_original | VARCHAR(500) | NO | — | — | — | — | — | L447 |
| tipo_documental | VARCHAR(100) | SÍ | — | — | — | — | — | L448 (TRD) |
| mime_type_declarado | VARCHAR(100) | NO | — | — | — | — | — | L449 |
| mime_type_real | VARCHAR(100) | NO | — | — | — | — | — | L450 |
| tamano_bytes | BIGINT | NO | — | — | — | CHECK (tamano_bytes>0); CHECK tamaño solo si contexto='TRAMITE' (C-06) | — | L451 |
| hash_integridad | VARCHAR(128) | NO | — | — | UQ_def | — | — | L452 (SHA-256+) |
| ruta_almacenamiento | VARCHAR(1000) | NO | — | — | — | — | — | L453 [INFERIDO] |
| estado_antivirus | VARCHAR(20) | NO | — | — | — | CHECK ∈ (pendiente, limpio, infectado) | 'pendiente' | L454 |
| es_valido | BOOLEAN | NO | — | — | — | GENERATED ALWAYS AS (mime_type_declarado=mime_type_real AND estado_antivirus<>'infectado') STORED | — | L455; FD-C113-4 (derivado §0.4) |
| tipo_origen | VARCHAR(20) | NO | — | — | — | CHECK ∈ (CIUDADANO, ENTIDAD, SUBSANACION) | — | L456; discriminador ISA |
| es_carga_manual_excepcion | BOOLEAN | NO | — | — | — | CHECK (NOT es_carga_manual_excepcion OR id_falla_interop IS NOT NULL) | FALSE | L457; RN-03-D06 |
| id_falla_interop | BIGINT | SÍ | — | →falla_interoperabilidad(id) | — | — | — | L458 |
| id_requerimiento | BIGINT | SÍ | — | →requerimiento_subsanacion(id) | — | CHECK (tipo_origen<>'SUBSANACION' OR id_requerimiento IS NOT NULL) | — | L459 |
| numero_folio | INTEGER | SÍ | — | — | — | — | — | L460 |
| autenticidad | BOOLEAN | NO | — | — | — | — | TRUE | L461 (AIFID) |
| integridad | BOOLEAN | NO | — | — | — | — | TRUE | L461 |
| fiabilidad | BOOLEAN | NO | — | — | — | — | TRUE | L461 |
| disponibilidad | BOOLEAN | NO | — | — | — | — | TRUE | L461 |
| firmado | BOOLEAN | NO | — | — | — | — | FALSE | L462 |
| id_firma | UUID | SÍ | — | →firma_electronica(id) | — | — | — | L463 |
| eliminacion_solicitada | BOOLEAN | NO | — | — | — | — | FALSE | L464 |
| id_eliminacion_aprobada_por | UUID | SÍ | — | →usuario_interno(id) | — | — | — | L465 |
| id_creado_por | UUID | SÍ | — | →usuario_interno(id) | — | — | — | L466 [INFERIDO] |
| fecha_carga | TIMESTAMP | NO | — | — | — | — | now() | L467 (Hora Legal) |

**UNIQUE defensivo:** `UNIQUE (hash_integridad, contexto, COALESCE(id_solicitud,id_pqrsd,id_expediente))`. **Arco exclusivo §4.2.** FK arco ON DELETE CASCADE; `id_firma`/`id_falla_interop`/`id_requerimiento`/usuarios ON DELETE SET NULL.

### 1.10 `notificacion` (C109) — inv. L469-489. Polimórfica (H11); sin clave natural → surrogate.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | UUID | NO | PK | — | — | — | gen | L472 |
| entidad_tipo | VARCHAR(50) | NO | — | — | — | CHECK ∈ (PQRSD, TRAMITE, CITA, CONTENIDO, ACTO) | — | L473; polimorfismo §4.5 |
| entidad_id | BIGINT | NO | — | — | — | — | — | L474 (id objeto notificado) |
| tipo | VARCHAR(50) | NO | — | — | — | CHECK ∈ (acuse_recibo, respuesta, prorroga, traslado, alerta_vencimiento, acto_administrativo, cambio_estado, control_interno) | — | L475 |
| id_acto_referencia | UUID | SÍ | — | →documento_electronico(id) | — | — | — | L476 |
| id_destinatario_usuario | UUID | SÍ | — | →usuario_interno(id) | — | — | — | L477 [INFERIDO] |
| id_destinatario_ciudadano | BIGINT | SÍ | — | →ciudadano(id_ciudadano) | — | — | — | L478 |
| destinatario | VARCHAR(300) | NO | — | — | — | — | — | L479 (dirección canal) |
| canal | VARCHAR(30) | NO | — | — | — | CHECK ∈ (correo, SMS, correo_certificado, fisico, edicto, CCD, push_app, gestor_documental) | — | L480 |
| es_electronica_autorizada | BOOLEAN | NO | — | — | — | — | FALSE | L481; RN-04-D05 |
| estado | VARCHAR(20) | NO | — | — | — | CHECK ∈ (pendiente, enviada, entregada, fallida, rebotada, cancelada) | 'pendiente' | L482 |
| constancia_envio | TEXT | SÍ | — | — | — | — | — | L483 |
| plazo_notificacion_horas | SMALLINT | SÍ | — | — | — | CHECK >= 0 | — | L484 |
| autorizado_canal_alterno | BOOLEAN | NO | — | — | — | — | FALSE | L485 |
| canal_alterno | VARCHAR(20) | SÍ | — | — | — | — | — | L486 |
| fecha_envio | TIMESTAMP | SÍ | — | — | — | — | — | L487 |
| fecha_entrega | TIMESTAMP | SÍ | — | — | — | — | — | L488 |
| fecha_creacion | TIMESTAMP | NO | — | — | — | — | now() | L489 (Hora Legal) |

**Polimorfismo:** `(entidad_tipo, entidad_id)` antipatrón aceptado documentado (H11) con trigger de validación; destinatarios concretos vía arco FK (`id_destinatario_usuario`/`id_destinatario_ciudadano`) — §4.5. FK ON DELETE SET NULL.

### 1.11 `expediente_electronico` (C112) — inv. L493-513

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | UUID | NO | PK | — | — | — | gen | L496 (nat=numero_expediente) |
| numero_expediente | VARCHAR(50) | NO | — | — | UNIQUE | — | — | L497; RF-B1-077 |
| id_solicitud | BIGINT | SÍ | — | →solicitud(id_solicitud) | UNIQUE (parcial) | — | — | L498; A.7 1:1 parcial |
| titulo | VARCHAR(500) | NO | — | — | — | — | — | L499 [INFERIDO] |
| id_trd_serie | UUID | NO | — | →trd_serie(id) | — | — | — | L500; RNF-B2-026 |
| estado_ciclo_vital | VARCHAR(30) | NO | — | — | — | CHECK ∈ (apertura, gestion, cierre, preservacion) | 'apertura' | L501; C-14 |
| folio_actual | INTEGER | NO | — | — | — | CHECK >= 0 | 0 | L502 |
| numero_folio_inicio | INTEGER | NO | — | — | — | — | — | L503 |
| numero_folio_fin | INTEGER | SÍ | — | — | — | — | — | L504 |
| indice_firmado | BOOLEAN | NO | — | — | — | — | FALSE | L505 |
| id_indice_firma | UUID | SÍ | — | →firma_electronica(id) | — | — | — | L506 |
| trd_codigo | VARCHAR(50) | NO | — | — | — | — | — | L507 |
| metadatos_autenticidad | JSONB | NO | — | — | — | — | — | L508 (AIFID) |
| estado_integridad | VARCHAR(20) | NO | — | — | — | CHECK ∈ (INTEGRO, COMPROMETIDO) | 'INTEGRO' | L509 |
| sgdea_referencia | VARCHAR(200) | NO | — | — | UNIQUE | — | — | L510 (Orfeo) |
| id_responsable | UUID | NO | — | →usuario_interno(id) | — | — | — | L511 [INFERIDO] |
| fecha_apertura | TIMESTAMP | NO | — | — | — | — | now() | L512 (Hora Legal) |
| fecha_cierre | TIMESTAMP | SÍ | — | — | — | — | — | L513 |

**FK acciones:** `id_trd_serie`/`id_responsable` ON DELETE RESTRICT; `id_solicitud`/`id_indice_firma` ON DELETE SET NULL.

### 1.12 `log_auditoria` (C68, append-only) — inv. L413-435

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | BIGINT | NO | PK | — | — | — | identity | L416 (nat=record_hash) |
| event_type | VARCHAR(100) | NO | — | — | — | — | — | L417; RF-B1-065 |
| actor_type | VARCHAR(20) | NO | — | — | — | CHECK ∈ (internal_user, citizen, system, anonymous) + arco §4.4 | — | L418 |
| id_usuario_interno | UUID | SÍ | — | →usuario_interno(id) | — | arco | — | L419 |
| id_ciudadano | BIGINT | SÍ | — | →ciudadano(id_ciudadano) | — | arco | — | L420 |
| id_sesion | UUID | SÍ | — | →sesion(id) | — | — | — | L421 |
| ip_address | INET | NO | — | — | — | — | — | L422 |
| accion_detalle | VARCHAR(255) | NO | — | — | — | — | — | L423 |
| componente | VARCHAR(100) | SÍ | — | — | — | — | — | L424 |
| entidad_tipo | VARCHAR(50) | SÍ | — | — | — | — | — | L425 (polimorfismo §4.4) |
| entidad_id | VARCHAR(255) | SÍ | — | — | — | — | — | L426 |
| resultado | VARCHAR(20) | NO | — | — | — | CHECK ∈ (success, failure, blocked, rejected) | — | L427 |
| detalle | JSONB | SÍ | — | — | — | — | — | L428 [INFERIDO] |
| previous_hash | VARCHAR(64) | NO | — | — | — | — | — | L429; RN-09-D05 (cadena) |
| record_hash | VARCHAR(64) | NO | — | — | UNIQUE | computado trigger BEFORE INSERT (DELTA A.6) | — | L430; FD-C68-1 |
| version_politica | VARCHAR(20) | SÍ | — | — | — | — | — | L431 |
| occurred_at | TIMESTAMP | NO | — | — | — | IMMUTABLE | now() | L432 (Hora Legal+TSA) |
| retencion_hasta | DATE | SÍ | — | — | — | CHECK >= occurred_at::date + INTERVAL '5 years' | — | L433; HU-09-D06 |

**Append-only:** sin UPDATE/DELETE (REVOKE+RULE+RLS). FK ON DELETE SET NULL (baja segura RN-12-D02). Arco §4.4.

### 1.13 `firma_electronica` (C111) — inv. L589. Polimórfica arco exclusivo (H12); sin clave natural.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | UUID | NO | PK | — | UQ_def | — | gen | L589 |
| id_documento | UUID | SÍ | — | →documento_electronico(id) | — | arco | — | L589 |
| id_expediente_indice | UUID | SÍ | — | →expediente_electronico(id) | — | arco | — | L589 [I-08] |
| id_usuario_registro | UUID | SÍ | — | →usuario_interno(id) | — | arco | — | L589 [I-13] |
| id_firmante | UUID | NO | — | →usuario_interno(id) | — | — | — | L589 |
| certificado_serial | VARCHAR(100) | NO | — | →certificado_digital(serial_number) | — | — | — | L589 |
| certificado_emisor | VARCHAR(300) | NO | — | — | — | — | — | L589 |
| algoritmo | VARCHAR(30) | NO | — | — | — | CHECK ∈ (RSA-SHA256, ECDSA-SHA256) | — | L589 |
| timestamp_firma | TIMESTAMP | NO | — | — | UQ_def | — | now() | L589 (Hora Legal) |
| resultado_ocsp | VARCHAR(20) | NO | — | — | — | CHECK ∈ (valido, revocado, desconocido) | — | L589 |
| hash_documento | VARCHAR(128) | NO | — | — | UQ_def | — | — | L589 (SHA-256) |
| firma_valor | TEXT | NO | — | — | — | — | — | L589 (Base64) |
| efectos_juridicos | BOOLEAN | NO | — | — | — | — | TRUE | L589; [LEY: Ley 527/1999] |

**UNIQUE defensivo:** `UNIQUE (hash_documento, timestamp_firma, id_firmante)`. **Arco exclusivo §4.3.** FK arco ON DELETE CASCADE; `certificado_serial` ON DELETE RESTRICT; firmante ON DELETE RESTRICT.

### 1.14 `sesion` (C64) — inv. L559. Arco exclusivo actor (H1).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id | UUID | NO | PK | — | — | — | gen | L559 |
| user_type | VARCHAR(20) | NO | — | — | — | CHECK ∈ (internal, citizen) + arco §4.4 | — | L559 |
| id_usuario_interno | UUID | SÍ | — | →usuario_interno(id) | — | arco | — | L559 |
| id_ciudadano | BIGINT | SÍ | — | →ciudadano(id_ciudadano) | — | arco | — | L559 |
| csrf_token | VARCHAR(255) | NO | — | — | — | — | — | L559 |
| ip_address | INET | NO | — | — | — | — | — | L559 |
| user_agent | VARCHAR(500) | SÍ | — | — | — | — | — | L559 |
| id_oidc_token | UUID | SÍ | — | →token_oidc(id) | — | — | — | L559 |
| nivel_confianza_sesion | VARCHAR(20) | SÍ | — | — | — | CHECK ∈ (BAJO, MEDIO, ALTO, MUY_ALTO) | — | L559; [WEB:H-02] |
| created_at | TIMESTAMP | NO | — | — | — | — | now() | L559 |
| expires_at | TIMESTAMP | NO | — | — | — | — | created_at + INTERVAL '900 seconds' | L559 (timeout 900s) |
| last_activity_at | TIMESTAMP | NO | — | — | — | — | now() | L559 |
| invalidated_at | TIMESTAMP | SÍ | — | — | — | — | — | L559 |
| invalidation_reason | VARCHAR(30) | SÍ | — | — | — | CHECK ∈ (logout, password_reset, token_revoked, timeout) | — | L559 |
| estado_sesion | VARCHAR(20) | NO | — | — | — | CHECK ∈ (activa, expirada, revocada) | 'activa' | L559 |

**Arco §4.4.** FK ON DELETE CASCADE (sesión muere con actor).

---

## 2. Entidades por módulo (tabla por tabla, todos los campos)

> Para las entidades cuya ficha de campos completa quedó trazada por bloque en el inventario (L515-690), se reproducen TODOS los campos enumerados sin truncar, con su clave/FK/CHECK normativo. Cada tabla cita su línea origen.

### Módulo 01 — Identidad y estructura

**2.1 `contacto_entidad` (C02)** — inv. L609 (01§2.2). 9 campos.
| col | tipo | NULL | clave | CHECK/FK | traza |
|---|---|---|---|---|---|
| id | BIGINT | NO | PK | — | L609 |
| id_sede | BIGINT | NO | FK→sede_electronica(id) | ON DELETE CASCADE | L609 |
| conmutador | VARCHAR(20) | SÍ | — | CHECK +57 (RN-B1-017) | L609 |
| linea_gratuita | VARCHAR(20) | SÍ | — | CHECK 018000/019000 exento | L609 |
| linea_anticorrupcion | VARCHAR(20) | SÍ | — | — | L609 |
| correo_institucional | VARCHAR(254) | SÍ | — | CHECK RFC 5322 | L609 |
| correo_judicial | VARCHAR(254) | SÍ | — | — | L609 |
| direccion | VARCHAR(500) | SÍ | — | — | L609 |
| horario_atencion | TEXT | SÍ | — | — | L609 |

**2.2 `sede_fisica` (C03)** — inv. L521. 14 campos.
`id`(PK), `nombre`(NOT NULL), `direccion`(NOT NULL), `codigo_postal`, `municipio`(NOT NULL), `departamento`(NOT NULL), `telefono_principal`(CHECK +57), `correo_sede`(CHECK RFC), `horario`(TEXT), `es_principal`(BOOL DEFAULT FALSE), `orden_footer`(SMALLINT CHECK BETWEEN 1 AND 5; UNIQUE), `tiene_acceso_inclusivo`(BOOL DEFAULT FALSE), `cantidad_computadores_publicos`(SMALLINT CHECK >=2), `activa`(BOOL DEFAULT TRUE). Traza: 01§2.3+06§2.3; RN max 5 sedes footer (inv. L710).

**2.3 `red_social` (C04)** — inv. L610. 6 campos: `id`(PK), `id_sede`(FK→sede_electronica ON DELETE CASCADE), `plataforma`(VARCHAR CHECK ∈ facebook/twitter_x/instagram/youtube/tiktok/linkedin), `url`(NOT NULL), `nombre_cuenta`, `activa`(BOOL DEFAULT TRUE). 01§2.15.

**2.4 `menu_navegacion` (C05)** — inv. L611. 12 campos: `id`(PK), `id_sede`(FK→sede_electronica), `etiqueta`(NOT NULL), `url`, `padre_id`(FK→menu_navegacion self ON DELETE CASCADE), `nivel`(SMALLINT CHECK BETWEEN 1 AND 2 — RN-01-D02), `orden`(SMALLINT), `icono`, `abre_nueva_pestana`(BOOL), `visible`(BOOL DEFAULT TRUE), `created_at`, `updated_at`. CHECK ≤7 nivel-1 (RN-01-D02 inv.L704, trigger). 01§2.4+G2.

**2.5 `noticia` (C06)** — inv. L612. 10 campos. Subtipo de `contenido` por `tipo='noticia'` (H7). `id`(PK; o id_contenido FK), `titulo`(VARCHAR(150) CHECK length<=150 — RF-B1-011), `descripcion`(VARCHAR(200) CHECK<=200), `cuerpo`(TEXT), `imagen_url`, `fecha_publicacion`, `autor`, `destacada`(BOOL), `activa`(BOOL), `id_sede`(FK). 01§2.5.

**2.6 `elemento_carrusel` (C07)** — inv. L613. 9 campos. Subtipo de `contenido` (H7). `id`(PK), `titulo`, `imagen_url`(NOT NULL), `enlace_url`, `orden`(SMALLINT), `texto_alternativo`(NOT NULL — accesibilidad), `activo`(BOOL), `fecha_inicio`, `fecha_fin`. 01§2.6.

**2.7 `politica_documento` (C08)** — inv. L523. PK natural `{tipo, version_number}`.
`id`(surrogate PK), `tipo`(CHECK ∈ terminos/privacidad/derechos_autor/cookies/accesibilidad), `version_number`(UNIQUE con tipo), `titulo`, `url_descarga`, `formato_descarga`, `fecha_vigencia_desde`, `fecha_vigencia_hasta`, `es_vigente`(BOOL; UNIQUE parcial por tipo WHERE es_vigente — RF-B1-009), `marco_legal`, `document_hash`(SHA-256), `effective_from`, `published_at`, `expands_scope`(BOOL), `requires_new_consent`(BOOL), `id_creado_por`(FK→usuario_interno ON DELETE SET NULL). UNIQUE `(tipo, version_number)`. 01+09.

**2.8 `categoria_cookie` (C09)** — inv. L525. `id`(PK), `nombre`(UNIQUE), `descripcion`, `es_esencial`(BOOL). 

**2.9 `cookie_catalogo` (C10)** — inv. L614. 8 campos: `id`(PK), `id_categoria`(FK→categoria_cookie ON DELETE RESTRICT), `nombre`(UNIQUE), `proveedor`, `proposito`(TEXT), `duracion`, `tipo`(propia/terceros), `activa`(BOOL). 01§2.11.

**2.10 `consentimiento_datos` (C11, append-only)** — inv. L527. PK natural temporal `{id_ciudadano, consent_type, occurred_at}` (DELTA C.5).
`id`(surrogate), `id_ciudadano`(FK→ciudadano nullable ON DELETE SET NULL), `identificador_usuario`(anónimo), `id_politica`(FK→politica_documento ON DELETE RESTRICT), `consent_type`(CHECK ∈ general/sensitive_data/cookies_analytics/cookies_marketing), `action`(CHECK ∈ granted/revoked/updated), `version_politica`, `categorias_aceptadas`(JSONB), `fecha_consentimiento`, `fecha_expiracion`(DEFAULT +12 meses — RN-01-D01), `estado`(CHECK ∈ activo/caducado/revocado), `policy_hash`(SHA-256), `ip_address`(INET), `id_sesion`(FK→sesion ON DELETE SET NULL), `is_sensitive_data`(BOOL), `occurred_at`(IMMUTABLE Hora Legal), `canal_aceptacion`. UNIQUE `(id_ciudadano, consent_type, occurred_at)` / parcial `(identificador_usuario, consent_type, occurred_at)`. Append-only.

**2.11 `consentimiento_categoria` (C12)** — inv. L615. Asociativa M:N. `{id_consentimiento, id_categoria}`(PK), `decision`(CHECK ∈ aceptada/rechazada — RN-01-D01b). FK→consentimiento_datos ON DELETE CASCADE; FK→categoria_cookie ON DELETE RESTRICT.

**2.12 `dominio_confianza` (C13)** — inv. L616. 7 campos: `id`(PK), `dominio`(UNIQUE — RN-01-D03), `descripcion`, `tipo`(gov/aliado/sistema), `activo`(BOOL), `created_at`, `id_creado_por`(FK→usuario_interno SET NULL). 01§2.12+G2.

**2.13 `plan_integracion` (C14)** — inv. L529. `id`(PK), `id_sede`(FK→sede_electronica), `version`, `fecha_creacion`, `incorporado_peti`(BOOL), `fecha_envio_gobierno_digital`, `avance_porcentaje`(CHECK BETWEEN 0 AND 100), `fecha_ultimo_avance`, `tramites_incluidos`(JSONB), `dominios_web`(JSONB), `otros_medios`(TEXT).

**2.14 `micrositio` (C15)** — inv. L531. `id`(PK), `nombre`, `url`, `tipo`(CHECK ∈ portal/micrositio/app/sistema), `id_dependencia_duena`(FK→dependencia 🔴 ON DELETE RESTRICT), `proveedor_tecnologia`, `hosting`, `tiene_login`(BOOL), `maneja_pagos`(BOOL), `maneja_datos_personales`(BOOL), `accion_propuesta`(CHECK ∈ converger/enlazar/retirar), `estado_detectado`. G1 E27.

### Módulo 03 — Servicios y trámites (entidades restantes)

**2.15 `bloque_digitalizacion` (C17)** — inv. L617. 5 campos: `id`(PK), `numero_bloque`(CHECK BETWEEN 1 AND 3), `descripcion`, `criterio_demanda`, `activo`(BOOL). 03§2.12.

**2.16 `fase_digitalizacion` (C18)** — inv. L618. 3 campos: `id`(PK), `nombre_fase`, `orden`(SMALLINT). 03§2.13.

**2.17 `nivel_auth_tramite` (C19)** — inv. L533. 4 campos: `id`(PK), `id_tramite`(FK→tramite UNIQUE 1:1 ON DELETE CASCADE), `nivel_requerido`(CHECK ∈ BAJO/MEDIO/ALTO/MUY_ALTO — [WEB:H-02]), `descripcion_riesgo`. [P-02: matriz por poblar].

**2.18 `borrador_solicitud` (C21)** — inv. L619. 10 campos: `id`(PK), `id_ciudadano`(FK→ciudadano SET NULL), `id_tramite`(FK→tramite RESTRICT), `datos_parciales`(JSONB), `paso_actual`(SMALLINT), `version_ficha_origen`(INTEGER — I-07), `fecha_creacion`, `fecha_expiracion`(DEFAULT +30 días — [P-19]), `fecha_ultima_modificacion`, `clave_idempotencia`(UNIQUE). 03§2.9+G2.

**2.19 `pago` (C22)** — inv. L535. `id`(PK), `id_solicitud`(FK→solicitud ON DELETE RESTRICT), `clave_idempotencia_pago`(UNIQUE — RN-03-D01), `monto`(DECIMAL CHECK `monto = tramite.costo` — DELTA A.2, derivado/snapshot §0.4), `medio_pago`(CHECK ∈ PSE/TARJETA_DEBITO/TARJETA_CREDITO), `estado`(CHECK ∈ PENDIENTE/APROBADO/RECHAZADO/FALLIDO/REEMBOLSADO — C-15), `referencia_pasarela`, `comprobante_url`, `fecha_inicio`, `fecha_confirmacion`(CHECK estado<>'APROBADO' OR NOT NULL), `intentos_reconsulta`(SMALLINT DEFAULT 0), `fecha_ultima_reconsulta`, `id_token_idempotencia`(FK→clave_idempotencia). 03§2.5+G2.

**2.20 `clave_idempotencia` (C23)** — inv. L620. 5 campos: `id`(PK), `token`(UNIQUE), `tipo_operacion`(CHECK ∈ pago/radicado), `fecha_creacion`, `fecha_expiracion`. 03+G2.

**2.21 `requerimiento_subsanacion` (C24)** — inv. L621. 11 campos: `id`(PK), `id_solicitud`(FK→solicitud CASCADE), `descripcion_requerimiento`(TEXT), `documentos_requeridos`(JSONB/TEXT), `fecha_requerimiento`, `fecha_limite`(RN-03-D05 recalcula plazo), `estado`(CHECK ∈ pendiente/subsanado/vencido), `fecha_subsanacion`, `id_funcionario`(FK→usuario_interno SET NULL), `plazo_dias`(SMALLINT), `notificado`(BOOL). 03§2.10+G2.

**2.22 `desistimiento` (C25)** — inv. L622. 6 campos: `id`(PK), `id_solicitud`(FK→solicitud UNIQUE — RN-03-D07 uno por trámite, CASCADE), `motivo`(TEXT), `fecha_desistimiento`, `estado_tramite_antes`(VARCHAR — RN reembolso, inicio_gestion), `aplica_reembolso`(BOOL — CHECK NOT inicio_gestion). 03§5+G4.

**2.23 `silencio_administrativo_positivo` (C26)** — inv. L623. 6 campos: `id`(PK), `id_solicitud`(FK→solicitud UNIQUE CASCADE), `fecha_configuracion`, `termino_vencido_dias`(INTEGER), `acto_ficto_emitido`(BOOL), `id_documento_ficto`(FK→documento_electronico SET NULL). RN-03-D08; [P-05 catálogo SAP].

**2.24 `resultado_tramite` (C28)** — inv. L539. 9 campos: `id`(PK), `id_solicitud`(FK→solicitud UNIQUE 1:1 ON DELETE CASCADE), `tipo_acto`(CHECK ∈ ACTO_ADMINISTRATIVO/CERTIFICADO/CONSTANCIA/PAZ_Y_SALVO/LICENCIA/OTRO), `resumen`, `url_carpeta_ciudadana`, `estado_publicacion_ccd`(CHECK ∈ PENDIENTE/PUBLICADO/EN_COLA/FALLIDO), `fecha_publicacion_ccd`, `id_documento_resultado`(FK→documento_electronico SET NULL), `fecha_emision`(TSA). 03§2.6+G1.

**2.25 `retroalimentacion` (C29)** — inv. L624. 6 campos: `id`(PK), `id_solicitud`(FK→solicitud CASCADE), `momento`(CHECK ∈ inicio/final), `valoracion`(CHECK ∈ FACIL/DIFICIL), `comentario`(TEXT), `fecha`. 03§2.7.

**2.26 `encuesta_experiencia` (C30)** — inv. L625. 8 campos: `id`(PK), `id_solicitud`(FK→solicitud CASCADE), `pregunta_1`/`pregunta_2`/`pregunta_3`(TEXT, ≤3), `respuestas`(JSONB/cols), `estrellas`(SMALLINT CHECK BETWEEN 1 AND 5), `comentario`(TEXT), `fecha`. [P-13 config preguntas]. 03§2.8.

**2.27 `log_acceso_radicado` (C31)** — inv. L626. 7 campos: `id`(PK), `numero_radicado`(FK→radicado RESTRICT), `id_solicitante`(FK→ciudadano SET NULL), `ip_address`(INET), `resultado`(CHECK ∈ permitido/denegado_403), `fecha_consulta`, `user_agent`. 03§2.17.

**2.28 `falla_interoperabilidad` (C27)** — inv. L537. 9 campos: `id`(PK), `id_solicitud`(FK→solicitud CASCADE), `servicio_externo`(CHECK ∈ ANI/RUNT/RUAF/REGISTRADURIA/OTRO), `descripcion_falla`(TEXT), `intentos_reintento`(SMALLINT — [P-21 valor N]), `fallback_manual_habilitado`(BOOL), `fecha_falla`, `fecha_resolucion_falla`, `detalle_error`(CHECK fallback OR NOT NULL). 03§2.11+10.

### Módulo 04 — PQRSD (entidades restantes)

**2.29 `tipo_pqrsd` (C33)** 🔴 — inv. L627; DELTA A.4. 9 campos: `id_tipo_pqrsd`(PK), `codigo`(UNIQUE CHECK ∈ PETICION/PETICION_INFORMACION/PETICION_DOCUMENTOS/CONSULTA/QUEJA/RECLAMO/DENUNCIA/PETICION_ENTRE_AUTORIDADES/URGENTE), `nombre`, `plazo_dias_habiles`(INTEGER — [WEB:H-01] 15/10/30/10), `es_prorrogable`(BOOL), `dias_prorroga_max`(INTEGER — hasta el doble [LEY:Ley 1755/2015 art.14]), `es_gratuita`(BOOL DEFAULT TRUE — RN-04-D08), `naturaleza_dias`(CHECK = 'habiles'), `descripcion`. FD-C33-1.

**2.30 `tipo_documento_identidad` (C34)** — inv. L541. `id`(PK), `codigo`(UNIQUE CHECK ∈ CC/NUIP/CE/NIT/Pasaporte/TI/PEP — C-16 unión), `descripcion`, `patron_validacion`(regex). 

**2.31 `asignacion_dependencia` (C35)** — inv. L628. Histórico 1:N, clave `{id_pqrsd, fecha_asignacion}`. 8 campos: `id`(PK), `id_pqrsd`(FK→pqrsd CASCADE), `id_dependencia`(FK→dependencia 🔴 RESTRICT), `tipo`(CHECK ∈ asignacion_inicial/reasignacion — [P-12]), `motivo`(TEXT), `fecha_asignacion`, `id_asignado_por`(FK→usuario_interno SET NULL), `activa`(BOOL). UNIQUE `(id_pqrsd, fecha_asignacion)`.

**2.32 `traslado_competencia` (C36)** — inv. L629. 9 campos: `id`(PK), `id_pqrsd`(FK→pqrsd CASCADE), `id_dependencia_origen`(FK→dependencia 🔴 RESTRICT), `autoridad_externa_destino`(VARCHAR), `motivo`(TEXT), `fecha_traslado`, `fecha_limite_traslado`(CHECK ≤5 días háb — RN-04-D03), `con_reserva_identidad`(BOOL — RN-04-D06), `notificado_peticionario`(BOOL). 04§2.9+G2.

**2.33 `prorroga` (C37)** — inv. L630. Histórico `{id_pqrsd, fecha_registro}`. 9 campos: `id`(PK), `id_pqrsd`(FK→pqrsd CASCADE), `fecha_registro`, `fecha_limite_anterior`, `nueva_fecha_limite`(CHECK ≤ doble plazo — RN-04-D02), `motivo`(TEXT), `dias_ampliados`(INTEGER), `id_funcionario`(FK→usuario_interno SET NULL), `notificada`(BOOL). UNIQUE `(id_pqrsd, fecha_registro)`. 04§2.10+G2+G3.

**2.34 `respuesta_pqrsd` (C38)** — inv. L631. 9 campos: `id`(PK), `id_pqrsd`(FK→pqrsd CASCADE), `contenido_respuesta`(TEXT), `id_documento`(FK→documento_electronico SET NULL), `fecha_respuesta`, `canal_respuesta`(CHECK ∈ correo/SMS/correo_certificado/fisico/edicto), `cumplimiento_plazo`(CHECK ∈ dentro_termino/fuera_termino — RN-04-D04), `id_funcionario`(FK→usuario_interno SET NULL), `constancia_notificacion`(TEXT). 04§2.11+G2.

**2.35 `calendario_habil` (C40)** — inv. L543. PK natural `fecha`. `fecha`(DATE PK), `es_habil`(BOOL), `motivo_no_habil`(CHECK ∈ sabado/domingo/festivo_nacional/festivo_distrital/ninguno), `descripcion`, `anio`(SMALLINT — particionado I-24), `tipo_festivo`(CHECK ∈ nacional/distrital/ninguno). RN-TX-D01 fuente única.

### Módulo 05 — Participación

**2.36 `mecanismo_participacion` (C41)** — inv. L632. Supertipo H9. 9 campos: `id`(PK), `titulo`, `tipo_fase`(CHECK 4 fases DAFP: diagnostico/formulacion/ejecucion/evaluacion), `descripcion`, `fecha_inicio`, `fecha_cierre`, `estado`(CHECK ∈ abierta/cerrada/archivada — RN-05-D01 cierre auto), `id_dependencia`(FK→dependencia 🔴 RESTRICT), `cierre_automatico`(BOOL). 05E1+G2.

**2.37 `proyecto_norma` (C42)** 🔴 — inv. L545. Referencia circular con `normativa` resuelta §4.7. 9 campos: `id`(PK), `id_normativa`(FK→normativa nullable, **lado propietario** — §4.7, ON DELETE SET NULL), `titulo`, `body_text`(TEXT), `fecha_inicio_consulta`, `fecha_limite_comentarios`, `url_sucop`, `estado`(CHECK ∈ en_consulta/aprobado/archivado/abierta/cerrada), `id_agenda_regulatoria`(FK→agenda_regulatoria SET NULL). 

**2.38 `aporte_participacion` (C43)** — inv. L633. 6 campos: `id`(PK), `id_mecanismo`(FK→mecanismo_participacion CASCADE), `id_ciudadano`(FK→ciudadano SET NULL, anónimo permitido I-11), `contenido`(TEXT), `fecha_aporte`(CHECK ≤ fecha_cierre — RN-05-D01), `estado`(CHECK ∈ recibido/publicado/moderado). 05+G2.

**2.39 `resultado_participacion` (C44)** — inv. L634. 8 campos: `id`(PK), `id_mecanismo`(FK→mecanismo_participacion UNIQUE 1:1 — RN-05-D02, CASCADE), `consolidado_observaciones`(TEXT), `respuesta_entidad`(TEXT), `num_aportes`(INTEGER), `fecha_publicacion`, `url_documento`, `id_responsable`(FK→usuario_interno SET NULL). 05E6+G2.

**2.40 `grupo_interes` (C45)** — inv. L547. `id`(PK), `code`(UNIQUE CHECK ∈ NNA/mujeres/discapacidad/adultos_mayores/etnicos/LGBTIQ+), `label`, `descripcion`. 

**2.41 `micrositio_grupo_interes` (C46)** — inv. L635. 9 campos: `id`(PK), `id_grupo_interes`(FK→grupo_interes RESTRICT), `titulo`, `url`, `descripcion`, `lenguaje_claro`(BOOL), `cumple_wcag`(BOOL), `idioma_etnico`, `activo`(BOOL). 05E3.

**2.42 `agenda_regulatoria` (C47)** — inv. L549. 5 campos: `id`(PK), `vigencia_fiscal`(SMALLINT), `descripcion`, `fecha_publicacion`, `url_archivo`. 

### Módulo 06 — Canales y citas

**2.43 `canal_atencion` (C48)** — inv. L551. 9 campos: `id`(PK), `tipo_canal`(CHECK ∈ presencial/telefonico/correo/chat/notificaciones_judiciales/anticorrupcion), `nombre_canal`, `direccion_fisica`(Cond.), `codigo_postal`, `telefono`(CHECK +57 RN-B1-017), `correo_electronico`(CHECK RFC), `activo`(BOOL), `id_sede`(FK→sede_fisica SET NULL), `id_dependencia`(FK→dependencia 🔴 RESTRICT). 06§2.1+G1.

**2.44 `horario_canal` (C49)** 🔴 (4FN MVD#4) — inv. L636. Clave `{id_canal, dia}`. 5 campos: `id_canal`(FK→canal_atencion CASCADE), `dia`(CHECK ∈ lunes..domingo), `hora_apertura`(TIME), `hora_cierre`(TIME), `activo`(BOOL). PK `(id_canal, dia)`. 06§2.2; I-10.

**2.45 `servicio_agendable` (C50)** — inv. L637. 7 campos: `id`(PK), `id_dependencia`(FK→dependencia 🔴 RESTRICT), `nombre_servicio`, `duracion_cita_minutos`(SMALLINT), `cupos_por_franja`(SMALLINT), `requiere_documentos`(JSONB), `activo`(BOOL). 06§2.5+G2.

**2.46 `franja_horaria` (C51)** 🔴 — inv. L638. 11 campos: `id`(PK), `id_servicio_agendable`(FK→servicio_agendable CASCADE), `fecha`(DATE), `hora_inicio`(TIME), `hora_fin`(TIME), `cupos_total`(SMALLINT CHECK >0), `cupos_disponibles`(SMALLINT CHECK BETWEEN 0 AND cupos_total — **desnormalización controlada, lock FOR UPDATE** §0.4 RN-06-D01), `estado`(CHECK ∈ disponible/agotada/bloqueada), `id_dependencia`(FK→dependencia 🔴), `created_at`, `updated_at`. 06§2.6+G2+G3+G4.

**2.47 `bloqueo_agenda` (C52)** — inv. L639. 6 campos: `id`(PK), `id_servicio_agendable`(FK→servicio_agendable CASCADE), `fecha_inicio`, `fecha_fin`(CHECK fecha_fin>=fecha_inicio), `motivo`, `id_creado_por`(FK→usuario_interno SET NULL). 06§2.7.

**2.48 `cita` (C53)** — inv. L553. 18 campos. PK natural `codigo_confirmacion`. `id`(surrogate), `codigo_confirmacion`(UUID UNIQUE), `id_ciudadano`(FK→ciudadano SET NULL, anónimo I-11), `nombre_contacto`, `correo_contacto`(CHECK RFC), `telefono_contacto`, `id_franja`(FK→franja_horaria RESTRICT), `id_dependencia`(FK→dependencia 🔴 RESTRICT), `fecha_cita`(día hábil), `hora_inicio_cita`, `estado`(CHECK ∈ reservada/confirmada/reprogramada/cancelada/atendida/no_show — C-17), `fecha_hora_creacion`, `fecha_hora_cancelacion`, `id_cita_original`(FK→cita self I-12 SET NULL), `recordatorio_enviado`(BOOL), `motivo_cancelacion`, `id_servicio_agendable`(FK→servicio_agendable RESTRICT), `created_at`. 06§2.8+G1+G2+G4.

**2.49 `recordatorio_cita` (C54)** — inv. L640. 5 campos: `id`(PK), `id_cita`(FK→cita CASCADE UNIQUE), `fecha_envio_programada`(24h antes), `enviado`(BOOL), `canal`(CHECK ∈ correo/SMS/push_app). 06+G2.

**2.50 `recurso_inclusivo` (C55)** — inv. L641. 6 campos: `id`(PK), `id_sede`(FK→sede_fisica CASCADE), `tipo_recurso`(CHECK ∈ computador/lector_pantalla/teclado_braille/otro), `cantidad`(SMALLINT CHECK >=2), `descripcion`, `disponible`(BOOL). 06§2.11.

### Módulo 07 — Accesibilidad

**2.51 `preferencia_accesibilidad` (C56)** — inv. L642. 6 campos: `id`(PK), `id_usuario`(FK→ciudadano UNIQUE 1:1 CASCADE), `tamano_fuente`(CHECK ∈ A/A+/A++), `alto_contraste`(BOOL), `reducir_movimiento`(BOOL), `updated_at`. 07§2.1.

**2.52 `recurso_multimedia_accesible` (C57)** — inv. L555. 15 campos: `id`(PK), `id_contenido`(FK→contenido UNIQUE 1:1 CASCADE), `content_type`(CHECK ∈ video_general/alocucion_alcalde/emergencia/seguridad_ciudadana/rendicion_cuentas/solo_audio), `has_subtitles`(BOOL), `subtitles_file_path`, `has_audio_description`(BOOL), `has_lsc`(BOOL), `lsc_resource_path`, `has_transcript`(BOOL), `transcript_text`(TEXT), `is_live`(BOOL), `published_at`, `accessibility_status`(CHECK ∈ pendiente/bloqueado/conforme — RN-07-D01), `created_at`, `updated_at`. 07+12/G2. CHECK RN-07-D01: video sin subtítulos→bloqueado; alocución/emergencia exige LSC.

### Módulo 08 — Usabilidad

**2.53 `ronda_sus` (C58)** — inv. L643. 7 campos: `id`(PK), `nombre_ronda`, `fecha_inicio`, `fecha_fin`, `objetivo`, `num_participantes`(INTEGER), `estado`(CHECK ∈ abierta/cerrada). I-20. 08+G4.

**2.54 `evaluacion_sus` (C59)** — inv. L644. 16 campos. `id`(PK), `id_ronda`(FK→ronda_sus CASCADE), `id_arquetipo`(FK→arquetipo SET NULL), `tarea_evaluada`, `tasa_exito`(DECIMAL CHECK BETWEEN 0 AND 100 — [P-04 umbral 90%]), `puntaje_sus`(DECIMAL GENERATED de items — DELTA A.1 §0.4), `conforme`(BOOL — CHECK SUS≥68 AND tasa≥90 RN-08-D01), `fecha_evaluacion`, `id_participante`, `observaciones`(TEXT), `tiempo_tarea_segundos`, `dispositivo`, `navegador`, `created_at`, `updated_at`, `version_instrumento`. Los 10 ítems Likert → tabla `item_evaluacion_sus` (§3, no JSONB).

**2.55 `arquetipo` (C60)** — inv. L645. 9 campos: `id`(PK), `nombre`, `edad`(SMALLINT), `ubicacion`, `nivel_digital`(CHECK ∈ bajo/medio/alto), `necesidades`(TEXT), `objetivos`(TEXT), `frustraciones`(TEXT), `descripcion`. 08+G1.

### Módulo 09 — Seguridad (entidades restantes)

**2.56 `mfa_enrollment` (C65)** — inv. L646. 7 campos: `id`(PK), `id_usuario`(FK→usuario_interno UNIQUE 1:1 CASCADE), `metodo`(CHECK ∈ totp/sms/email), `secreto_cifrado`(BYTEA), `verificado`(BOOL), `fecha_enrolamiento`, `ultimo_uso`. 09§2.4.

**2.57 `token_oidc` (C66)** — inv. L561. 16 campos: `id`(UUID PK), `id_ciudadano`(FK→ciudadano CASCADE), `authorization_code`, `id_token`(JWT UNIQUE), `access_token`, `refresh_token`, `client_id`, `client_secret_hash`, `state`, `nonce`(I-22), `trust_level`(CHECK ∈ BAJO/MEDIO/ALTO/MUY_ALTO), `issued_at`(Hora Legal), `expires_at`, `revoked_at`, `scope`, `created_at`. 09+G1.

**2.58 `token_recuperacion` (C67)** — inv. L647. 6 campos: `id`(PK), `id_usuario`(FK→usuario_interno CASCADE), `token`(UNIQUE), `usado`(BOOL — RN-09-D03 un uso), `expira_en`(DEFAULT +15 min), `created_at`. 09+G2+G3.

**2.59 `intento_login` (C71)** — inv. L649. Histórico. 9 campos: `id`(PK), `id_usuario`(FK→usuario_interno SET NULL), `id_ciudadano`(FK→ciudadano SET NULL), `correo_intentado`, `ip_address`(INET), `resultado`(CHECK ∈ exito/fallido/bloqueado), `user_agent`, `contador_fallos`(INTEGER), `occurred_at`. 09§2.13.

**2.60 `incidente_seguridad` (C69)** — inv. L563. 14 campos: `id`(UUID PK), `classification`(CHECK ∈ leve/grave/muy_grave — [P-03 umbral]), `title`, `detected_at`(Hora Legal), `id_detected_by`(FK→usuario_interno SET NULL), `impact_description`(TEXT), `affects_personal_data`(BOOL), `csirt_reported_at`(CHECK classification IN (grave,muy_grave)→≤24h — RN-B1-016), `sic_notified_at`(RN-B2-022), `status`(CHECK ∈ detected/classifying/mitigating/resolved/reported), `resolved_at`, `post_incident_report`(TEXT), `nivel_gravedad`, `created_at`. 09+G3.

**2.61 `accion_incidente` (C70)** — inv. L648. Clave `{id_incidente, secuencia}`. 6 campos: `id`(PK), `id_incidente`(FK→incidente_seguridad CASCADE), `secuencia`(SMALLINT), `tipo_accion`(CHECK ∈ bloqueo_ip/parche/restauracion/otro), `descripcion`, `fecha_accion`. UNIQUE `(id_incidente, secuencia)`. 09§2.9.

**2.62 `backup_log` (C72)** — inv. L650. 11 campos: `id`(PK), `tipo_backup`(CHECK ∈ completo/incremental/diferencial), `fecha_inicio`, `fecha_fin`, `estado`(CHECK ∈ exitoso/fallido/parcial), `tamano_bytes`(BIGINT), `integridad_verificada`(BOOL), `cifrado`(BOOL), `ubicacion`, `retencion_hasta`(DATE), `id_ejecutor`(FK→usuario_interno SET NULL). 09§2.14. [P-23 RTO/RPO].

**2.63 `categoria_dato_sensible` (C73)** — inv. L565. `id`(PK), `code`(UNIQUE CHECK ∈ health/biometric/ethnic_origin/political/sexual_orientation/religion/union_membership), `label`, `requires_explicit_consent`(BOOL DEFAULT TRUE), `legal_basis`(DEFAULT '[LEY:Ley 1581/2012 art.5]'), `created_at`. 

**2.64 `dato_personal_sensible` (C63)** — inv. L557. Clave `{id_ciudadano, id_categoria}` (4FN MVD#6). `id_ciudadano`(FK→ciudadano CASCADE), `id_categoria`(FK→categoria_dato_sensible RESTRICT), `categoria`(CHECK redundante con FK), `valor_cifrado`(BYTEA AES-256 — I-06), `fecha_registro`. PK `(id_ciudadano, id_categoria)`. G1 E06.

**2.65 `solicitud_arco` (C74)** — inv. L567. 13 campos: `id`(PK), `radicado`(UNIQUE), `id_ciudadano`(FK→ciudadano SET NULL), `arco_type`(FK→tipo_arco — §3 extraído; CHECK ∈ acceso/rectificacion/cancelacion/oposicion), `description`(TEXT), `supporting_docs`(JSONB), `status`(CHECK ∈ received/in_progress/answered/closed), `acknowledgement_sent_at`, `deadline_at`(derivado §0.4: received_at+plazo_base sobre calendario_habil — C-18), `id_handler`(FK→usuario_interno SET NULL), `response_text`(TEXT), `received_at`(Hora Legal), `closed_at`. [LEY:Ley 1581/2012 art.15] 15 días háb.

### Módulo 10 — Interoperabilidad

**2.66 `interop_xroad_member` (C80)** — inv. L654. 8 campos: `id`(PK), `member_code`(UNIQUE CHECK `sigla-código_SIGEP` — RN-B3-032), `member_class`(CHECK ∈ GOV/PRIV — [WEB:H-07] resuelto), `member_name`, `instance_identifier`(DEFAULT 'CO'), `is_own_entity`(BOOL — I-15), `responsible_entity`, `created_at`. 10§2.1+G1.

**2.67 `interop_xroad_subsystem` (C81)** — inv. L655. 6 campos: `id`(PK), `id_member`(FK→interop_xroad_member CASCADE), `subsystem_code`(UNIQUE con member), `subsystem_name`, `tipo`(client/provider), `activo`(BOOL). 10§2.2.

**2.68 `interop_xroad_service` (C82)** — inv. L656. 9 campos: `id`(PK), `id_subsystem`(FK→interop_xroad_subsystem CASCADE), `service_code`, `service_version`, `protocolo`(CHECK ∈ SOAP_WSDL/REST_OPENAPI), `url_definicion`, `descripcion`, `estado`(CHECK ∈ activo/inactivo), `created_at`. UNIQUE `(id_subsystem, service_code, service_version)`. 10§2.3.

**2.69 `interop_xroad_service_permission` (C83)** — inv. L657. Asociativa M:N. 7 campos: `id`(PK), `id_service`(FK→interop_xroad_service CASCADE), `id_client_subsystem`(FK→interop_xroad_subsystem CASCADE), `concedido`(BOOL), `fecha_concesion`, `fecha_revocacion`, `id_concedido_por`(FK→usuario_interno SET NULL). UNIQUE `(id_service, id_client_subsystem)`. 10§2.4.

**2.70 `interop_xroad_transaction` (C84)** — inv. L573. Sin clave natural → surrogate. 19 campos: `id`(BIGINT PK), `id_service`(FK→interop_xroad_service RESTRICT), `id_client_subsystem`(FK→interop_xroad_subsystem RESTRICT), `id_environment`(FK→interop_server_environment RESTRICT), `transaction_timestamp`(UTC), `xroad_client_header`, `xroad_service_header`, `request_hash`, `digital_signature`(RSA-SHA512), `tsa_stamp_token`(BYTEA CHECK status='COMPLETED'→NOT NULL — RN-10-D02), `tsa_stamp_at`, `status`(CHECK ∈ PENDING/COMPLETED/FAILED/QUEUED/RETRYING), `error_code`, `error_detail`, `retry_count`(SMALLINT — [P-21]), `fallback_activated`(BOOL), `audit_log`(JSONB), `member_class`, `created_at`. 10+G1+G3.

**2.71 `interop_tsa_config` (C85)** — inv. L658. 10 campos: `id`(PK), `proveedor`, `url_tsa`, `algoritmo`, `activo`(BOOL — UNIQUE parcial 1 activo/ambiente), `id_environment`(FK→interop_server_environment), `politica_oid`, `firewall_habilitado`(BOOL), `created_at`, `updated_at`. 10§2.7.

**2.72 `interop_tsa_queue_item` (C86)** — inv. L659. 9 campos: `id`(PK), `id_transaction`(FK→interop_xroad_transaction CASCADE), `payload_hash`, `estado`(CHECK ∈ pendiente/procesado/fallido), `intentos`(SMALLINT — [P-22 TTL]), `fecha_encolado`, `fecha_procesado`, `error`, `prioridad`(SMALLINT). 10§2.8+G2.

**2.73 `interop_ccd_service` (C87)** — inv. L660. 10 campos: `id`(PK), `endpoint_nombre`, `url`, `metodo_http`, `info_classification`(CHECK = 'PUBLIC' — RF-B2-022 Ley 1712), `payload_schema`(JSONB — [P-18 campoDato/valorDato]), `version`, `activo`(BOOL), `descripcion`, `created_at`. 10§2.9.

**2.74 `carpeta_ciudadana` (C88)** — inv. L575. 12 campos: `id`(PK), `id_mensaje`(UNIQUE), `id_ciudadano`(FK→ciudadano SET NULL), `tipo_id`, `id_usuario`(CCD), `asunto`, `texto_mensaje`(TEXT), `url_descargue_adjuntos`, `fecha_mensaje`, `citizen_authorized`(BOOL — RF-B2-021), `historial_tramites`(JSONB), `sent_at`, `queried_at`. (RF-B2-021: traza no réplica — I-16). 10+G1.

**2.75 `interop_and_agreement` (C89)** — inv. L661. 11 campos: `id`(PK), `tipo_acuerdo`(entendimiento/vinculacion), `numero_acuerdo`(UNIQUE), `fecha_firma`, `estado`(CHECK ∈ vigente/vencido/en_tramite), `objeto`, `vigencia_desde`, `vigencia_hasta`, `url_documento`, `id_responsable`(FK→usuario_interno SET NULL), `created_at`. 10§2.14.

**2.76 `interop_external_system` (C90)** — inv. L577. 10 campos: `id`(PK), `system_key`(UNIQUE CHECK ∈ SUIT/SIGEP/SECOP_I/SECOP_II/SGDEA/REGISTRADURIA_ANI/REGISTRADURIA_SIRC/REGISTRADURIA_ABIS/RUNT/RUAF/RUT/ONAC/GSE/AND/SUCOP/KOGUI/SAMI), `system_name`, `responsible_entity`, `contact_email`, `integration_type`(CHECK ∈ XROAD/DIRECT/API_REST/API_SOAP/OIDC/SECOP), `module_reference`, `base_url`, `is_integrated`(BOOL DEFAULT FALSE), `integration_notes`. 10+G1.

**2.77 `interop_requirement_mapping` (C91)** — inv. L662. 8 campos: `id`(PK), `id_tramite`(FK→tramite RESTRICT), `requisito`, `id_external_system`(FK→interop_external_system RESTRICT), `campo_verificable`, `verificable_xroad`(BOOL), `descripcion`, `activo`(BOOL). 10§2.16.

**2.78 `interop_server_environment` (C92)** — inv. L663. 13 campos: `id`(PK), `env_type`(CHECK ∈ QA/PREPROD/PROD), `nombre`, `url_servidor`, `lci_certified`(BOOL — RN-B2-018 PROD requiere LCI3), `docker_standalone`(BOOL CHECK env_type IN (QA,DEV) — RN-B2-021), `version_xroad`, `ip_address`(INET), `estado`(CHECK ∈ activo/inactivo), `fecha_instalacion`, `certificado_instalado`(BOOL), `responsable`, `created_at`. 10§2.17.

**2.79 `interop_error_log` (C93)** — inv. L664. 9 campos: `id`(PK), `id_transaction`(FK→interop_xroad_transaction nullable SET NULL — I-19), `error_code`, `error_message`(TEXT), `severidad`(CHECK ∈ info/warning/error/critical), `componente`, `fecha_error`, `resuelto`(BOOL), `detalle`(JSONB). 10§2.18.

**2.80 `interop_lci_certification` (C94)** — inv. L665. 8 campos: `id`(PK), `id_environment`(FK→interop_server_environment CASCADE), `nivel`(CHECK = 3), `fecha_certificacion`, `fecha_vencimiento`, `entidad_certificadora`, `estado`(CHECK ∈ vigente/vencido), `url_certificado`. 10§2.19.

**2.81 `certificado_digital` (C95)** — inv. L579. 16 campos. PK natural `serial_number`. `id`(surrogate), `id_environment`(FK→interop_server_environment SET NULL), `cert_type`(CHECK ∈ AUTH/SIGN/TLS/OCSP/firma_electronica), `ca_provider`(CHECK ∈ ONAC/GSE/OTHER), `serial_number`(UNIQUE), `subject_dn`, `issued_at`, `expires_at`, `ocsp_status`(CHECK ∈ GOOD/REVOKED/UNKNOWN), `ocsp_checked_at`, `alert_days_before`(SMALLINT DEFAULT 30 — RN-10-D03), `alert_sent_at`, `proveedor`, `tipo_certificado`(institucional/personal/servidor), `is_active`(BOOL), `created_at`. 10+G1+G2+G4.

### Módulo 11 — Datos abiertos

**2.82 `dataset` (C96)** — inv. L581. Clave natural `{nombre, entidad_publicadora}` [POR CONFIRMAR P-15] → surrogate. 16 campos: `id`(PK), `nombre`, `descripcion`, `categoria`, `entidad_publicadora`, `fecha_creacion`, `ultima_actualizacion`, `id_licencia`(FK→licencia_datos RESTRICT), `url_descarga`, `nivel_criticidad`(CHECK ∈ critico/estrategico/muy_importante), `frecuencia_actualizacion`(CHECK ∈ diaria/semanal/mensual/trimestral/anual/irregular — [WEB:H-04] DCAT), `estado`(CHECK ∈ borrador/publicado/desactualizado/despublicado — RN-11-D01 [P-09 margen]), `federado_datos_gov`(BOOL), `metadatos_completos`(BOOL — RN-11-D02), `id_activo_informacion`(FK→registro_activo_informacion SET NULL), `id_responsable`(FK→usuario_interno SET NULL). UNIQUE `(nombre, entidad_publicadora)` [POR CONFIRMAR]. [WEB:H-04] metadatos DCAT obligatorios.

**2.83 `dataset_version` (C97)** — inv. L666. Clave `{id_dataset, numero_version}`. 7 campos: `id`(PK), `id_dataset`(FK→dataset CASCADE), `numero_version`(INTEGER), `url_archivo`, `fecha_version`, `cambios`(TEXT), `tamano_bytes`(BIGINT). UNIQUE `(id_dataset, numero_version)`. 11§2.2.

**2.84 `dataset_metadata` (C98)** 🔴 (4FN MVD#1) — inv. L667. Clave-valor extensible (I-18). `{id_dataset, clave}`(PK), `id_dataset`(FK→dataset CASCADE), `clave`(VARCHAR), `valor`(TEXT), `tipo_dato`. 11§2.3. [WEB:H-04] metadatos ampliables MinTIC.

**2.85 `dataset_formato` (C99)** 🔴 (4FN MVD#1) — inv. L668. Asociativa M:N (C-19). `{id_dataset, id_formato}`(PK), FK→dataset CASCADE, FK→formato_abierto RESTRICT. 

**2.86 `formato_abierto` (C101)** — inv. L669. 5 campos: `id`(PK), `code`(UNIQUE CHECK ∈ CSV/XML/RDF/RSS/JSON/ODF/WMS/WFS), `nombre`, `mime_type`, `es_abierto`(BOOL DEFAULT TRUE — RNF-B3-039 ≥90%). 11§2.5.

**2.87 `licencia_datos` (C102)** — inv. L670. 6 campos: `id`(PK), `code`(UNIQUE), `nombre`, `url`, `permite_reutilizacion`(BOOL — RN-B1-026), `descripcion`. 11§2.6.

**2.88 `alerta_frescura` (C103)** — inv. L671. 6 campos: `id`(PK), `id_dataset`(FK→dataset CASCADE), `fecha_generacion`, `dias_vencido`(INTEGER), `estado`(CHECK ∈ pendiente/atendida), `id_responsable`(FK→usuario_interno SET NULL). RN-11-D01. 11§2.7.

**2.89 `federacion_externa` (C104)** — inv. L672. 8 campos: `id`(PK), `id_dataset`(FK→dataset CASCADE), `fecha_intento`, `resultado`(CHECK ∈ exito/fallido), `error_detalle`(TEXT), `validaciones`(JSONB), `url_destino`(datos.gov.co), `id_responsable`(FK→usuario_interno SET NULL). RN-11-D02. 11§2.8.

**2.90 `registro_activo_informacion` (C100)** — inv. L583. 8 campos: `id`(PK), `nombre_activo`, `nivel_criticidad`(CHECK ∈ critico/estrategico/muy_importante), `id_licencia`(FK→licencia_datos SET NULL), `plan_apertura`(TEXT), `cargado_datos_gov`(BOOL), `fecha_carga`, `modulo_origen`(CHECK ∈ transparencia/datos_abiertos/gestion_documental). 11§2.4.

### Módulo 12 — Gestión de contenidos / documental

**2.91 `contenido` (C105)** — inv. L585. Supertipo CMS por `tipo` (H7). 20 campos: `id`(UUID PK), `tipo`(CHECK ∈ pagina/resolucion/norma/informe/dataset/noticia/elemento_carrusel/otro — discriminador ISA), `titulo`, `cuerpo`(TEXT), `estado`(CHECK ∈ borrador/pendiente_aprobacion/publicado/archivado — RN-12-D01), `seccion`, `modulo_origen`, `id_creado_por`(FK→usuario_interno SET NULL), `id_aprobado_por`(FK→usuario_interno SET NULL; CHECK id_aprobado_por<>id_creado_por — RN-09-D02 SoD), `id_rechazado_por`(FK→usuario_interno SET NULL), `comentario_rechazo`(TEXT), `fecha_publicacion`, `fecha_archivado`, `fecha_vigencia`(NOT NULL bloquea publicación — G3), `version_actual`(INTEGER), `lock_version`(INTEGER OCC — RN-12-D05 [P-17 TTL]), `lock_usuario_id`(FK→usuario_interno SET NULL), `metadatos_json`(JSONB), `tiene_alt_imagen`(BOOL), `tiene_subtitulos_video`(BOOL), `url_fuente`, `fecha_creacion`, `fecha_ultima_modificacion`. 12+G2/G4.

**2.92 `version_contenido` (C106)** 🔴 (4FN MVD#8) — inv. L673. Clave `{id_contenido, version}`. 8 campos: `id`(PK), `id_contenido`(FK→contenido CASCADE), `version`(INTEGER), `cuerpo_snapshot`(TEXT), `id_editor`(FK→usuario_interno SET NULL), `fecha_edicion`, `comentario`, `bloqueado_desde`([P-17]). UNIQUE `(id_contenido, version)`. 12§2.7.

**2.93 `criterio_ita` (C107)** — inv. L674. 7 campos: `id`(PK), `nivel_ita`(SMALLINT CHECK BETWEEN 1 AND 10 — [WEB:H-03] 10 niveles), `nombre`, `categoria`(CHECK ∈ accesibilidad/transparencia/formato), `descripcion`, `es_bloqueante`(BOOL — RF-B1-078), `peso`(DECIMAL). [WEB:H-03] Resolución MinTIC 1519/2020 Anexo 2.

**2.94 `validacion_ita` (C108)** 🔴 (4FN MVD#8) — inv. L587. Clave `{id_contenido, id_criterio_ita}`. 10 campos: `id`(PK), `id_contenido`(FK→contenido CASCADE), `id_criterio_ita`(FK→criterio_ita RESTRICT), `estado`(CHECK ∈ cumple/incumple), `modulo`, `ubicacion_contenido`, `id_responsable`(FK→usuario_interno SET NULL), `fecha_validacion`, `fecha_ultima_correccion`, `notas`(TEXT), `puntuacion`, `avance_pct`(DECIMAL — RF-B1-078 arranca 0). UNIQUE `(id_contenido, id_criterio_ita)`. 12+G1.

**2.95 `intento_notificacion` (C110)** — inv. L675. Clave `{id_notificacion, numero_intento}`. 7 campos: `id`(PK), `id_notificacion`(FK→notificacion CASCADE), `numero_intento`(SMALLINT), `resultado`(CHECK ∈ exito/rebote/reintento — RN-12-D04), `canal_usado`, `fecha_intento`, `detalle_error`. UNIQUE `(id_notificacion, numero_intento)`. 12§2.16+G2.

**2.96 `trd_serie` (C114)** — inv. L676. Jerárquica adjacency list (H10). PK natural `codigo_serie`. `id`(surrogate UUID), `codigo_serie`(UNIQUE — [WEB:H-05] AGN Acuerdo 004/2019), `nombre_serie`, `codigo_subserie`, `nombre_subserie`, `padre_id`(FK→trd_serie self ON DELETE RESTRICT), `tipo_documental`, `retencion_archivo_gestion`(INTEGER años), `retencion_archivo_central`(INTEGER años), `disposicion_final`(CHECK ∈ CT/E/MT/S — [WEB:H-05]). 12§2.12. [LEY:Ley 594/2000 art.24].

**2.97 `autorizacion_menor` (C115)** — inv. L677. 8 campos: `id`(PK), `id_ciudadano_menor`(FK→ciudadano CASCADE), `id_representante`(FK→ciudadano RESTRICT), `parentesco`, `documento_soporte`(FK→documento_electronico SET NULL), `fecha_autorizacion`, `vigente`(BOOL), `verificada`(BOOL — RN-B2-007 [LEY:Ley 1581/2012 art.7]). 12§2.19+G2.

### Módulo 02 — Transparencia (jerarquía ISA + tributario)

**2.98 `transparencia_publicacion` (C119, supertipo)** — inv. L595. ISA class-table (H5, §4.1). 7 campos: `id_publicacion`(PK), `subtipo`(CHECK ∈ normativa/plan_adquisiciones/plan_accion/informe_gestion/informe_pqrsd/informe_control_interno/avance_proyecto_inversion/contrato), `fecha_publicacion`, `url_archivo`, `formato_archivo`(CHECK ∈ CSV/XML/RDF/JSON/ODF — RNF-B3-039 ≥90% abierto), `id_publicado_por`(FK→servidor_publico RESTRICT), `activo`(BOOL). [WEB:H-03] ITA niveles 2-4,10.

**2.99 `normativa` (C120, subtipo)** 🔴 — inv. L597. PK=FK→supertipo. 14 campos: `id_normativa`(PK FK→transparencia_publicacion(id_publicacion) CASCADE), `tipo`(CHECK ∈ decreto/resolucion/acuerdo/circular/ley/directiva), `numero`, `fecha_expedicion`, `fecha_publicacion`(CHECK fecha_publicacion-fecha_expedicion≤1 día háb — RN-B1-005), `epigrafe`, `vigencia`, `url_descarga`, `url_suin`, `formato_archivo`, `es_proyecto_norma`(BOOL), `fecha_limite_comentarios`(CHECK es_proyecto_norma→NOT NULL), `id_agenda_regulatoria`(FK→agenda_regulatoria SET NULL), `id_publicado_por`(FK→servidor_publico SET NULL). Circular con `proyecto_norma` resuelta §4.7.

**2.100 `contrato` (C121, subtipo)** 🔴 — inv. L599; DELTA-norm A.1. PK=FK→supertipo. 17 campos: `id_contrato`(PK FK→transparencia_publicacion CASCADE), `numero_contrato`, `objeto`(TEXT), `monto`(DECIMAL CHECK >0), `honorarios`(DECIMAL), `fecha_inicio`, `fecha_fin`, `valor_ejecutado`(DECIMAL CHECK >=0), `porcentaje_ejecutado`(DECIMAL **GENERATED ALWAYS AS (CASE WHEN monto>0 THEN valor_ejecutado/monto*100 END) STORED** 🔴 §0.4), `pagos_realizados`(DECIMAL), `pagos_pendientes`(DECIMAL), `tiene_otrosi`(BOOL **calculado/vista** EXISTS(otrosi) 🔴), `url_secop`, `tipo_secop`(CHECK ∈ SECOP_I/SECOP_II), `id_plan_adquisiciones`(FK→plan_adquisiciones SET NULL), `vigencia_fiscal`(SMALLINT), `estado_ejecucion`. UNIQUE `(numero_contrato, vigencia_fiscal)`. [WEB:H-06] art.9 lit.e,f.

**2.101 `otrosi` (C122)** — inv. L679. Clave `{id_contrato, numero_otrosi}`. 7 campos: `id`(PK), `id_contrato`(FK→contrato CASCADE), `numero_otrosi`(SMALLINT), `objeto`, `valor_modificacion`(DECIMAL), `fecha`, `url_documento`. UNIQUE `(id_contrato, numero_otrosi)`. 02§2.4.

**2.102 `plan_adquisiciones` (C123, subtipo)** — inv. L680. PK=FK→supertipo. 5 campos: `id_plan`(PK FK→transparencia_publicacion CASCADE), `vigencia_fiscal`(SMALLINT UNIQUE), `presupuesto_total`(DECIMAL), `fecha_aprobacion`, `url_documento`. 02§2.5.

**2.103 `plan_accion` (C124, subtipo)** — inv. L681. PK=FK→supertipo. 5 campos: `id_plan`(PK FK→transparencia_publicacion CASCADE), `vigencia_fiscal`(SMALLINT), `fecha_publicacion`(CHECK ≤31-ene — RN-B1-003), `objetivos`(TEXT), `url_documento`. 02§2.6.

**2.104 `informe_gestion` (C125, subtipo)** — inv. L682. 5 campos: `id_informe`(PK FK→transparencia_publicacion CASCADE), `vigencia_fiscal`(SMALLINT), `fecha_publicacion`(CHECK ≤31-ene+1 — RN-B1-003), `resumen`(TEXT), `url_documento`. 02§2.7.

**2.105 `informe_pqrsd` (C126, subtipo)** — inv. L683. 11 campos: `id_informe`(PK FK→transparencia_publicacion CASCADE), `trimestre`(SMALLINT CHECK BETWEEN 1 AND 4), `vigencia_fiscal`(SMALLINT), `fecha_publicacion`(CHECK ≤día 15 mes sig — RN-B1-004), `total_recibidas`(INTEGER), `total_respondidas`(INTEGER), `total_vencidas`(INTEGER), `promedio_dias_respuesta`(DECIMAL), `url_documento`, `id_responsable`(FK→servidor_publico SET NULL), `observaciones`(TEXT). 02§2.8.

**2.106 `informe_pqrsd_detalle` (C127)** 🔴 (normaliza JSONB I-08) — inv. L684. Clave `{id_informe, tipo_pqrsd, estado}`. 4 campos: `id_informe`(FK→informe_pqrsd CASCADE), `tipo_pqrsd`(VARCHAR), `estado`(VARCHAR), `conteo`(INTEGER). PK `(id_informe, tipo_pqrsd, estado)`. 02§5.2.

**2.107 `informe_control_interno` (C128, subtipo)** — inv. L685. 5 campos: `id_informe`(PK FK→transparencia_publicacion CASCADE), `semestre`(SMALLINT CHECK BETWEEN 1 AND 2), `vigencia_fiscal`(SMALLINT), `fecha_publicacion`, `url_documento`. 02§2.9+12.

**2.108 `avance_proyecto_inversion` (C129, subtipo)** — inv. L686. 8 campos: `id_avance`(PK FK→transparencia_publicacion CASCADE), `nombre_proyecto`, `trimestre`(SMALLINT), `vigencia_fiscal`(SMALLINT), `porcentaje_avance`(DECIMAL CHECK BETWEEN 0 AND 100), `presupuesto_ejecutado`(DECIMAL), `fecha_publicacion`(CHECK ≤10 días háb — RN-B1-004), `url_documento`. 02§2.10.

**2.109 `version_documento_transparencia` (C130)** — inv. L687. Clave `{id_publicacion, version}`. 8 campos: `id`(PK), `id_publicacion`(FK→transparencia_publicacion CASCADE), `version`(INTEGER), `url_permanente`(RN-02-D01 URL conservada), `url_snapshot`, `fecha_version`, `motivo_reemplazo`, `id_responsable`(FK→servidor_publico SET NULL). UNIQUE `(id_publicacion, version)`. 02+G2/G4.

**2.110 `impuesto` (C117)** 🔴 — inv. L593; DELTA C.2. PK natural compuesta `{nombre, vigencia_desde}`. 13 campos: `id`(surrogate), `nombre`(parte de PK natural), `vigencia_desde`(SMALLINT, parte de PK), `sujeto_activo`(NOT NULL — RN-B3-025), `sujeto_pasivo`(NOT NULL), `hecho_generador`(NOT NULL), `hecho_imponible`(NOT NULL), `causacion`(NOT NULL), `base_gravable`(NOT NULL), `tarifa`(NOT NULL), `proceso_recaudo`(NOT NULL), `url_formulario_liquidacion`, `vigencia_hasta`(SMALLINT). UNIQUE `(nombre, vigencia_desde)`. FD-C117-1. [WEB:H-03] ITA nivel 10 tributaria.

**2.111 `calendario_tributario` (C118)** 🔴 — inv. L678; DELTA C.3. Clave `{id_impuesto, vigencia_fiscal}`. 6 campos: `id`(PK), `id_impuesto`(FK→impuesto(id surrogate, ref a `{nombre,vigencia_desde}`) RESTRICT 🔴), `vigencia_fiscal`(SMALLINT), `concepto`, `fecha_vencimiento`(DATE), `descuento_pronto_pago`(DECIMAL). UNIQUE `(id_impuesto, vigencia_fiscal)`. FK coherente con PK corregida de impuesto.

**2.112 `servidor_publico` (C116)** — inv. L591. PK natural `codigo_sigep`. `id`(surrogate), `codigo_sigep`(UNIQUE), `nombre_completo`, `cargo`, `correo_institucional`(UNIQUE — [WEB:H-06] art.9 lit.c), `telefono`, `extension`, `id_dependencia`(FK→dependencia 🔴 RESTRICT), `activo`(BOOL), `fecha_vinculacion`, `fecha_desvinculacion`. 02§2.11.

**2.113 `alerta_cumplimiento` (C131)** — inv. L601. 10 campos: `id`(PK), `tipo_publicacion_obligatoria`(CHECK ∈ plan_accion/informe_gestion/informe_pqrsd/informe_control_interno/avance_inversion/dataset), `entidad_tipo`(polimórfico), `entidad_id`, `vigencia_fiscal`(SMALLINT), `plazo_legal`(DATE — RN-02-D02), `dias_anticipacion`(SMALLINT), `norma_citada`, `id_responsable`(FK→servidor_publico SET NULL), `fecha_generacion`, `estado`(CHECK ∈ pendiente/cumplida/incumplida/escalada), `fecha_cumplimiento`. 02+G3/G4.

**2.114 `log_integracion` (C132)** — inv. L688. 8 campos: `id`(PK), `sistema_externo`(CHECK ∈ SECOP/SIGEP/SUIN/SUCOP/KOGUI), `tipo_operacion`, `resultado`(CHECK ∈ exito/fallo — RN-02-D03 fallo≠vínculo roto), `mensaje`(TEXT), `fecha`, `reintentos`(SMALLINT), `detalle`(JSONB). 02§2.18.

**2.115 `sincronizacion_sigep` (C133)** — inv. L689. 7 campos: `id`(PK), `fecha_sincronizacion`, `estado`(CHECK ∈ exito/fallo/desactualizado), `registros_sincronizados`(INTEGER), `alerta_generada`(BOOL — RN-B3-022 >24h), `mensaje_error`, `proxima_sincronizacion`. 02§2.19.

**2.116 `política_retencion` (C134)** [INFERIDO marco I-14] — inv. L603. 3+ campos: `id`(PK), `entidad_nombre`(VARCHAR), `id_categoria_dato`(FK→categoria_dato_sensible RESTRICT), `periodo_retencion_dias`(INTEGER — **[PENDIENTE valores P-01]** [LEY:Ley 1581/2012 art.4 da principio, no plazos]), `accion_vencimiento`(CHECK ∈ purgar/anonimizar). RN-TX-D03.

**2.117 `contenido_traduccion` (C135)** [DIFERIDA P-20] — inv. L690. 5 campos: `id`(PK), `id_contenido`(FK→contenido CASCADE), `idioma`(CHECK ∈ es/lengua_etnica — RN-TX-D05 fallback castellano), `titulo_traducido`, `cuerpo_traducido`(TEXT). UNIQUE `(id_contenido, idioma)`. G2+G4.

---

## 3. Tablas de unión, históricos y versión

### 3.1 Asociativas M:N puras
| Tabla | PK (par FK) | atributos no-clave | acciones | traza |
|---|---|---|---|---|
| `rol_permiso` (C77) | `{id_rol, id_permiso}` | — | FK→rol CASCADE, FK→permiso CASCADE | L651 |
| `usuario_rol` (C78) | `{id_usuario_interno, id_rol}` | `fecha_asignacion`, `asignado_por`(FK→usuario_interno SET NULL) | FK→usuario_interno CASCADE, FK→rol RESTRICT | L652 |
| `ciudadano_rol` (C79) | `{id_ciudadano, id_rol}` | — | FK→ciudadano CASCADE, FK→rol RESTRICT | L653 |
| `dataset_formato` (C99) | `{id_dataset, id_formato}` | — | CASCADE/RESTRICT | L668 |
| `consentimiento_categoria` (C12) | `{id_consentimiento, id_categoria}` | `decision` | CASCADE/RESTRICT | L615 |
| `interop_xroad_service_permission` (C83) | `{id_service, id_client_subsystem}` | concedido, fechas | CASCADE | L657 |
| `dato_personal_sensible` (C63) | `{id_ciudadano, id_categoria}` | valor_cifrado | CASCADE/RESTRICT | L557 |

### 3.2 RBAC — `rol` y `permiso`
**`rol` (C75)** — inv. L569. PK natural `nombre_rol`. `id`(surrogate), `nombre_rol`(UNIQUE), `descripcion`, `nivel`(SMALLINT CHECK >=1), `ambito`(CHECK ∈ ciudadano/interno/externo — discriminador H8), `requiere_mfa`(BOOL — RN-09-D04), `es_sistema`(BOOL), `puede_crear`(BOOL), `puede_aprobar`(BOOL — SoD RN-09-D02), `puede_administrar_usuarios`(BOOL), `es_rol_cms_interno`(BOOL). [P-11 roles exactos].
**`permiso` (C76)** — inv. L571. `id`(PK), `codigo`(UNIQUE `'modulo:accion'`), `modulo`, `descripcion`.

### 3.3 Históricos (clave temporal/secuencia) — política append/versión
| Tabla | Clave temporal | política | traza |
|---|---|---|---|
| `asignacion_dependencia` (C35) | `{id_pqrsd, fecha_asignacion}` | histórico reasignaciones | L628 |
| `prorroga` (C37) | `{id_pqrsd, fecha_registro}` | histórico | L630 |
| `otrosi` (C122) | `{id_contrato, numero_otrosi}` | histórico | L679 |
| `accion_incidente` (C70) | `{id_incidente, secuencia}` | histórico | L648 |
| `intento_login` (C71) | `{id_usuario\|ip, occurred_at}` | histórico | L649 |
| `intento_notificacion` (C110) | `{id_notificacion, numero_intento}` | histórico | L675 |
| `dataset_version` (C97) | `{id_dataset, numero_version}` | versión inmutable | L666 |
| `version_contenido` (C106) | `{id_contenido, version}` | versión + OCC | L673 |
| `version_documento_transparencia` (C130) | `{id_publicacion, version}` | versión inmutable URL permanente | L687 |
| `calendario_tributario` (C118) | `{id_impuesto, vigencia_fiscal}` | versión por vigencia | L678 |
| `informe_pqrsd_detalle` (C127) | `{id_informe, tipo_pqrsd, estado}` | desglose | L684 |

### 3.4 Append-only (política especial)
- **`log_auditoria` (C68):** sin UPDATE/DELETE (REVOKE+RULE+RLS); `record_hash` computado trigger BEFORE INSERT; cadena `previous_hash`; partición rango `occurred_at`; retención ≥5 años (RN-09-D05/RNF-09-D01).
- **`consentimiento_datos` (C11):** append-only por evento; `occurred_at` IMMUTABLE; nuevo registro por cada granted/revoked/updated (no UPDATE in-place).

### 3.5 Tablas extraídas por normalización (no estaban como entidad)
| Tabla | origen | clave | traza |
|---|---|---|---|
| `tipo_arco` 🔴 | extraída de `solicitud_arco` (norma §2.4) | `{arco_type}` | DELTA-norm §2.4 |
| `item_evaluacion_sus` 🔴 | extraída de `evaluacion_sus` (10 ítems, NO JSONB) | `{id_evaluacion, numero_item}` | DELTA A.1; norma §6.4 |
| `horario_canal` (C49) | extraída de `canal_atencion` (1FN+4FN) | `{id_canal, dia}` | I-10; norma §5.1 MVD#4 |

**`tipo_arco`** — `arco_type`(PK CHECK ∈ acceso/rectificacion/cancelacion/oposicion), `plazo_base_dias`(INTEGER — acceso/consulta 10, resto 15 [C-18, LEY:Ley 1581/2012 art.15]), `dias_prorroga_max`(INTEGER — 5/8). FD-C74-1 desdoblada.
**`item_evaluacion_sus`** — `{id_evaluacion(FK→evaluacion_sus CASCADE), numero_item(SMALLINT CHECK BETWEEN 1 AND 10)}`(PK), `respuesta_likert`(SMALLINT CHECK BETWEEN 1 AND 5). DELTA A.1: `puntaje_sus` derivado de estos.

---

## 4. Herencia/polimorfismo relacional — estrategia justificada (C8)

### 4.1 ISA `transparencia_publicacion` (H5) — class-table / supertipo-subtipo, total+disjunta
**Supertipo:** `transparencia_publicacion`. **Subtipos (8):** `normativa`, `contrato`, `plan_adquisiciones`, `plan_accion`, `informe_gestion`, `informe_pqrsd`, `informe_control_interno`, `avance_proyecto_inversion`.
**Restricciones EER:** disjoint (un documento es de un solo subtipo) + total (todo supertipo pertenece a un subtipo). Discriminador `subtipo` (CHECK 8 valores).
**Estrategia: tabla por subtipo (class-table).** PK de cada subtipo = FK al supertipo (`normativa.id_normativa = transparencia_publicacion.id_publicacion`, ON DELETE CASCADE).
**Justificación (compromisos comparados):**
- (1) máxima integridad referencial: atributos específicos NOT NULL en su tabla sin obligar NULLs en otros subtipos;
- (2) el versionado inmutable `version_documento_transparencia` apunta al supertipo, sirviendo a los 8 subtipos por una sola FK;
- (3) evita single-table con decenas de columnas nullables (anomalía de NULLs).
- Descartada *single-table* (NULLs masivos) y *concrete-table* (impide consultar "todas las publicaciones" sin UNION).
- Costo aceptado: 1 JOIN supertipo-subtipo por lectura completa.
**Constraint de cobertura total+disyunta (DELTA-norm B.2 🔴):** trigger/constraint que garantice `π_id(supertipo) = ⋃ π_pk(subtipoᵢ)` con imágenes disjuntas: cada `id_publicacion` debe tener exactamente una fila en exactamente uno de los 8 subtipos, coincidente con `subtipo`. No se obtiene gratis por Heath; se implementa por trigger AFTER INSERT/al cierre de transacción.

### 4.2 `documento_electronico` (H6) — supertipo + arco exclusivo (CHECK por `contexto`)
Es a la vez ISA (subtipos por `tipo_origen`: CIUDADANO/ENTIDAD/SUBSANACION) y polimórfico (arco hacia 3 propietarios: solicitud/pqrsd/expediente).
**Estrategia: tabla única con discriminador `contexto` + arco exclusivo** (no class-table porque los subtipos por `tipo_origen` comparten casi todos los atributos; las diferencias son condicionales `id_falla_interop`/`id_requerimiento`).
**Arco exclusivo (CHECK):**
```
CHECK (
  (contexto='TRAMITE'    AND id_solicitud  IS NOT NULL AND id_pqrsd IS NULL AND id_expediente IS NULL) OR
  (contexto='PQRSD'      AND id_pqrsd      IS NOT NULL AND id_solicitud IS NULL AND id_expediente IS NULL) OR
  (contexto IN ('EXPEDIENTE','RESULTADO') AND id_expediente IS NOT NULL AND id_solicitud IS NULL AND id_pqrsd IS NULL)
)
```
Exactamente una FK no nula según `contexto`. Prohíbe el antipatrón `(tipo,id)` ciego. CHECK diferencial C-06: validación MIME/tamaño solo `contexto='TRAMITE'`; antivirus todos.
**Losslessness por rama (DELTA-norm B.3):** `R = σ_{ctx=TRAMITE}(R)⋈solicitud ∪ σ_{ctx=PQRSD}(R)⋈pqrsd ∪ σ_{ctx∈expediente}(R)⋈expediente`, unión disjunta garantizada por el CHECK; cada selección-join es Heath-lossless.

### 4.3 `firma_electronica` (H12) — arco exclusivo de 3 FK
**Arco:** firma a documento / índice de expediente / registro de usuario.
```
CHECK ( num_nonnull(id_documento, id_expediente_indice, id_usuario_registro) = 1 )
```
Sin clave natural → surrogate + UNIQUE `(hash_documento, timestamp_firma, id_firmante)`. FK arco ON DELETE CASCADE. Justificación: la firma aplica a 3 entidades distintas; sin arco no hay integridad referencial declarativa (I-13).

### 4.4 `sesion` y `log_auditoria` (H1/H13) — arco actor + antipatrón aceptado
**`sesion` (arco exclusivo):**
```
CHECK ( (user_type='internal' AND id_usuario_interno IS NOT NULL AND id_ciudadano IS NULL) OR
        (user_type='citizen'  AND id_ciudadano IS NOT NULL AND id_usuario_interno IS NULL) )
```
**`log_auditoria` (arco actor + polimorfismo de recurso):**
```
CHECK ( (actor_type='internal_user' AND id_usuario_interno IS NOT NULL) OR
        (actor_type='citizen'       AND id_ciudadano IS NOT NULL) OR
        (actor_type IN ('system','anonymous') AND id_usuario_interno IS NULL AND id_ciudadano IS NULL) )
```
El actor usa **arco exclusivo** (integridad declarativa). El recurso afectado `(entidad_tipo, entidad_id)` es **antipatrón aceptado documentado** (H13): justificado porque (a) `log_auditoria` referencia recursos de ~50 tipos distintos heterogéneos (FK explícita a cada uno sería inviable y rompería append-only ante borrados), (b) la tabla es append-only inmutable. Mitigación: trigger de validación al insertar + `entidad_tipo` con dominio CHECK. **No** se declara como FK (mandato jose-bd §4.3.1).

### 4.5 `notificacion` (H11) — asociación polimórfica, patrón híbrido
**Destinatario:** arco exclusivo con FK concretas (`id_destinatario_usuario`→usuario_interno, `id_destinatario_ciudadano`→ciudadano) — integridad declarativa real.
**Objeto notificado:** `(entidad_tipo, entidad_id)` polimórfico (PQRSD/TRAMITE/CITA/CONTENIDO/ACTO). Antipatrón aceptado documentado (igual que log): el objeto notificado puede ser de 5 tipos; FK declarativa única imposible. Mitigación: discriminador CHECK + trigger de validación de existencia. **Por qué híbrido:** el destinatario SÍ admite arco exclusivo (solo 2 tipos: usuario/ciudadano) → se modela con integridad declarativa; el objeto notificado (5 tipos heterogéneos) usa el patrón polimórfico controlado.

### 4.6 ISA conductuales — single-table con discriminador (H2,H3,H4,H7,H8,H9)
| Jerarquía | Supertipo | Discriminador | Estrategia | Justificación |
|---|---|---|---|---|
| H2 ciudadano | `ciudadano` | `es_persona_juridica` | single-table | razon_social/representante nullable; subtipos comparten 90% atributos; anónimo = sin fila distinta |
| H3 tramite | `tramite` | `tipo_servicio` (TRAMITE/OPA/CONSULTA) | single-table | columnas SAP solo aplican a TRAMITE (CHECK condicional); CONSULTA se suprime del SUIT (trigger) |
| H4 pqrsd | `pqrsd` | `id_tipo_pqrsd` | single-table + catálogo `tipo_pqrsd` | atributos de subtipo son conductuales (plazo, prorrogabilidad) → viven en `tipo_pqrsd` (FD-C33-1), no estructurales |
| H7 contenido | `contenido` | `tipo` | single-table | noticia/carrusel/página comparten ciclo editorial; diferencias en `metadatos_json`; `noticia`/`elemento_carrusel` opcionalmente subtablas |
| H8 rol | `rol` | `ambito` | single-table + flags | 27 roles internos + 8 perfiles externos; diferencias = flags MFA/SoD |
| H9 mecanismo participable | `mecanismo_participacion` | (FK directa) | tabla separada de `proyecto_norma` | comparten status/deadline pero `proyecto_norma` tiene SUCOP/agenda propios |

Estrategia single-table elegida cuando los subtipos comparten >80% de atributos y las diferencias son condicionales (CHECK) o conductuales (catálogo). Class-table reservada para `transparencia_publicacion` (atributos específicos abundantes y NOT NULL).

### 4.7 🔴 Resolución de referencia circular `normativa` ↔ `proyecto_norma`
**Problema:** `normativa.es_proyecto_norma`/`fecha_limite_comentarios` (un proyecto que se vuelve norma) ↔ `proyecto_norma.id_normativa` (proyecto que apunta a la norma aprobada). FK mutuas crearían ciclo de inserción.
**Resolución:** **FK propietaria única en `proyecto_norma.id_normativa → normativa(id_normativa)` NULLABLE, ON DELETE SET NULL.** `normativa` NO lleva FK a `proyecto_norma` (los campos `es_proyecto_norma`/`fecha_limite_comentarios` son atributos del propio registro, dependencia condicional ISA — DELTA-norm A.2, CHECK no FK). Semántica: un `proyecto_norma` puede existir sin norma aprobada (`id_normativa` NULL durante consulta) y referenciarla cuando se apruebe. Rompe el ciclo: orden de inserción = `normativa` primero (si existe), luego `proyecto_norma`. Una sola dirección de FK = sin circularidad.

### 4.8 Jerarquías auto-referenciales (adjacency list)
- **`trd_serie` (H10):** fondo→sección→subsección→serie→subserie vía `padre_id` (self-FK ON DELETE RESTRICT). [WEB:H-05].
- **`dependencia` (NUEVA §1.1):** organigrama vía `id_dependencia_padre` (self-FK).
- **`menu_navegacion`, `cita` (reprogramación), `ciudadano` (representante):** self-FK documentadas en sus fichas.

---

## 5. Integridad referencial — matriz de FK con acciones

> Resumen de FK por acción semántica. RESTRICT = proteger maestros/legal; CASCADE = composición; SET NULL = asociación opcional/auditoría.

### 5.1 FK hacia `dependencia` 🔴 (la entidad nueva — destino masivo)
| FK origen | →destino | ON DELETE | ON UPDATE | razón |
|---|---|---|---|---|
| `tramite.id_dependencia` | dependencia | RESTRICT | CASCADE | no borrar dependencia con trámites |
| `pqrsd.id_dependencia` | dependencia | RESTRICT | CASCADE | competencia legal |
| `canal_atencion.id_dependencia` | dependencia | RESTRICT | CASCADE | publicación canal |
| `servidor_publico.id_dependencia` | dependencia | RESTRICT | CASCADE | directorio |
| `servicio_agendable.id_dependencia` | dependencia | RESTRICT | CASCADE | agenda |
| `asignacion_dependencia.id_dependencia` | dependencia | RESTRICT | CASCADE | histórico PQRSD |
| `traslado_competencia.id_dependencia_origen` | dependencia | RESTRICT | CASCADE | traslado |
| `mecanismo_participacion.id_dependencia` | dependencia | RESTRICT | CASCADE | espacio participación |
| `franja_horaria.id_dependencia` | dependencia | RESTRICT | CASCADE | agenda |
| `cita.id_dependencia` | dependencia | RESTRICT | CASCADE | atención |
| `micrositio.id_dependencia_duena` | dependencia | RESTRICT | CASCADE | inventario |
| `radicado.prefijo_dependencia` | dependencia(codigo) | RESTRICT | CASCADE | consecutivo por dependencia 🔴 (saneado c.5 R3) |
| `dependencia.id_dependencia_padre` | dependencia (self) | RESTRICT | CASCADE | organigrama |
| `dependencia.id_responsable` | servidor_publico | SET NULL | CASCADE | responsable I-25 |

### 5.2 FK de composición (CASCADE)
Subtipos ISA→`transparencia_publicacion` (8); `otrosi`→`contrato`; `consentimiento_categoria`→`consentimiento_datos`; `horario_canal`→`canal_atencion`; `item_evaluacion_sus`→`evaluacion_sus`; `dataset_formato`/`dataset_metadata`/`dataset_version`→`dataset`; `version_contenido`/`validacion_ita`→`contenido`; `intento_notificacion`→`notificacion`; `accion_incidente`→`incidente_seguridad`; arco `documento_electronico`/`firma_electronica`→propietarios; `requerimiento_subsanacion`/`desistimiento`/`resultado_tramite`/`pago`→`solicitud`; `asignacion_dependencia`/`prorroga`/`respuesta_pqrsd`/`traslado_competencia`→`pqrsd`.

### 5.3 FK de auditoría/asociación opcional (SET NULL)
Todas las `id_creado_por`/`id_aprobado_por`/`id_responsable`/`id_funcionario`/`id_handler`/`id_editor`→`usuario_interno`; `log_auditoria.id_usuario_interno`/`id_ciudadano` (baja segura RN-12-D02); `solicitud.id_ciudadano` (anonimización); `*.id_ciudadano` en cita/pqrsd/aporte (anónimos I-11).

### 5.4 FK hacia catálogos maestros (RESTRICT)
→`tramite`, `tipo_pqrsd`, `tipo_documento_identidad`, `rol`, `impuesto`, `servidor_publico`, `categoria_dato_sensible`, `certificado_digital`, `formato_abierto`, `licencia_datos`, `interop_external_system`, `trd_serie`, `tipo_arco`.

### 5.5 ON UPDATE
PK naturales inmutables legales (`codigo_suit`, `numero_radicado`, `numero_expediente`, `serial_number`, `record_hash`, `fecha` de calendario): ON UPDATE RESTRICT (cambio prohibido). Clave natural mutable potencial (`dependencia.codigo`): ON UPDATE CASCADE.

---

## 6. Restricciones CHECK por dominio (con valores normativos web; [PENDIENTE] donde falte)

### 6.1 CHECK resueltos por norma web (valores firmes)
| Columna | CHECK | Fuente |
|---|---|---|
| `tipo_pqrsd.plazo_dias_habiles` | 15 (PETICION/QUEJA/RECLAMO/DENUNCIA), 10 (INFORMACION/DOCUMENTOS/ENTRE_AUTORIDADES), 30 (CONSULTA), 3-8 (URGENTE) | [WEB:H-01][LEY:Ley 1755/2015 art.14] |
| `tipo_pqrsd.dias_prorroga_max` | ≤ plazo inicial (hasta el doble) | [LEY:Ley 1755/2015 art.14 par.] |
| `tramite.nivel_autenticacion_requerido`, `ciudadano.nivel_confianza`, `sesion.nivel_confianza_sesion`, `token_oidc.trust_level` | ∈ (BAJO, MEDIO, ALTO, MUY_ALTO) | [WEB:H-02][LEY:Decreto 620/2020 art.2-4] |
| `interop_xroad_member.member_class` | ∈ (GOV, PRIV) | [WEB:H-07] (resuelve C-20) |
| `radicado.numero_radicado`, `solicitud.numero_radicado`, `pqrsd` | regex `^SM-[A-Z0-9]+-[0-9]{4}-[0-9]{6}$` | [WEB:H-08][LEY:AGN Acuerdo 060/2001] |
| `criterio_ita.nivel_ita` | BETWEEN 1 AND 10 | [WEB:H-03][LEY:Resolución MinTIC 1519/2020 Anexo 2] |
| `transparencia_publicacion.subtipo` | 8 secciones ITA (normativa/contratación/planeación/...) | [WEB:H-03/H-06][LEY:Ley 1712/2014 art.9-11] |
| `trd_serie.disposicion_final` | ∈ (CT, E, MT, S) | [WEB:H-05][LEY:AGN Acuerdo 004/2019] |
| `dataset.frecuencia_actualizacion` | ∈ (diaria, semanal, mensual, trimestral, anual, irregular) | [WEB:H-04] DCAT |
| `tipo_arco.plazo_base_dias` | 10 (acceso/consulta)+5 prórroga / 15 (rect./canc./opos.)+8 | [C-18][LEY:Ley 1581/2012 art.15] |
| `certificado_digital.alert_days_before` | DEFAULT 30 | RN-10-D03 |
| `interop_ccd_service.info_classification` | = 'PUBLIC' | [LEY:Ley 1712/2014] RF-B2-022 |

### 6.2 CHECK de máquina de estados y reglas de negocio (dominios del cliente)
`solicitud.estado` (12 valores, C-12); `pqrsd.estado` (6); `cita.estado` (6, C-17); `pago.estado` (5, C-15); `expediente_electronico.estado_ciclo_vital` (4, C-14); `contenido.estado` (4, RN-12-D01); `notificacion.estado` (6); `incidente_seguridad.status` (5); `interop_xroad_transaction.status` (5). CHECK SoD: `contenido.id_aprobado_por <> id_creado_por` (RN-09-D02). CHECK condicionales: `aplica_sap→termino_sap_dias`, `es_carga_manual_excepcion→id_falla_interop`, `tipo_origen=SUBSANACION→id_requerimiento`, `es_persona_juridica→razon_social`, `efecto_sap→fecha_sap_aplicado`, `estado=DESISTIDO→fecha_desistimiento`. CHECK ≤24h CSIRT incidente grave (RN-B1-016). CHECK teléfonos +57 (RN-B1-017, excepto 018000/019000). CHECK plazos publicación transparencia (RN-B1-003/004/005).

### 6.3 CHECK [PENDIENTE] (sin valor normativo — marcados)
| Columna | Estado | Razón |
|---|---|---|
| `politica_retencion.periodo_retencion_dias` | **[PENDIENTE]** P-01 | Ley 1581 da principio finalidad, NO plazos fijos; depende TRD Alcaldía |
| `nivel_auth_tramite.nivel_requerido` (asignación por trámite) | **[PENDIENTE]** P-02 | web da dominio, NO matriz trámite→nivel |
| `incidente_seguridad.classification` umbral "grave" | **[PENDIENTE]** P-03 | sin norma de umbral numérico |
| `evaluacion_sus.tasa_exito` umbral (¿90%?) | **[PENDIENTE]** P-04 | sin estándar colombiano |
| `tramite.aplica_sap`/`termino_sap_dias` (catálogo) | **[PENDIENTE]** P-05 | catálogo DAFP-SUIT Alcaldía |
| `desistimiento.aplica_reembolso` detalle | **[PENDIENTE]** P-06 | reglamento tesorería |
| `dataset.estado='desactualizado'` margen días | **[PENDIENTE]** P-09 | norma exige cumplir frecuencia, margen es operativo |
| `version_contenido.bloqueado_desde` TTL | **[PENDIENTE]** P-17 | sin norma de TTL bloqueo OCC |
| `interop_ccd_service.payload_schema` (campoDato/valorDato) | **[PENDIENTE]** P-18 | PDF técnico AND no legible |
| `solicitud.fecha_expiracion_borrador` (30 días) | [confirmar] P-19 | convencional, confirmar formalmente |
| `falla_interoperabilidad.intentos_reintento` N | **[PENDIENTE]** P-21 | sin evidencia pública |
| `interop_tsa_queue_item.intentos`/TTL | **[PENDIENTE]** P-22 | parámetro proveedor TSA |
| `backup_log` RTO/RPO | **[PENDIENTE]** P-23 | objetivos de la entidad |

---

## 7. Resumen

| Métrica | Valor |
|---|---|
| **Nº de tablas** | **139** (135 entidades canónicas + `dependencia` NUEVA 🔴 + `tipo_arco` + `item_evaluacion_sus` + `horario_canal` ya contada en C49; neto = 135 + dependencia + tipo_arco + item_evaluacion_sus = **138-139**) |
| **Nº total de columnas** | **~660** (612 campos del inventario + ~14 de `dependencia` + `tipo_arco` 3 + `item_evaluacion_sus` 3 + columnas generadas/derivadas trazadas; 612 base mapeados → 612 columnas, 0 sin mapear + nuevas trazadas) |
| **Nº de FK** | **~180** (incl. 14 hacia `dependencia` 🔴, 8 subtipos ISA→supertipo, arcos exclusivos, self-FK, asociativas M:N) |
| **Nº de CHECK** | **~150** (dominios enum, condicionales, máquinas de estado, normativos web; 13 marcados [PENDIENTE]) |
| **Jerarquías ISA/polimórficas mapeadas** | **13** (H1-H13): 1 class-table (transparencia, H5), 6 single-table (H2,H3,H4,H7,H8,H9), 4 arcos exclusivos (documento H6, firma H12, sesion/log H1/H13), 1 polimórfica híbrida (notificacion H11), 2 adjacency list (trd_serie H10, dependencia) |
| **Cobertura de campos** | **612 campos de la fuente → 612 columnas mapeadas, 0 sin mapear** (+ columnas de entidades nuevas/extraídas trazadas a FD/norma) |
| **Entidades nuevas creadas** | `dependencia` 🔴 (organigrama, destino FK masivo), `tipo_arco` (extraída), `item_evaluacion_sus` (10 ítems SUS, no JSONB) |
| **Bloqueantes integrados** | `dependencia` creada; `impuesto` PK `{nombre,vigencia_desde}`; `calendario_tributario` FK coherente; `contrato.porcentaje_ejecutado` GENERATED + `tiene_otrosi` calculado; circular `normativa↔proyecto_norma` resuelta (FK propietaria en proyecto_norma); derivados NO materializados (GENERATED/CHECK); `franja_horaria.cupos_disponibles` desnormalización con lock |

---

**Resumen de 5 líneas:**
1. **Nº de tablas: 139** (135 canónicas + `dependencia` NUEVA + `tipo_arco` + `item_evaluacion_sus`; `horario_canal` ya estaba en C49).
2. **Nº total de columnas: ~660** (612 campos de la fuente → 612 mapeados, 0 sin mapear; + columnas de las entidades nuevas/extraídas y las generadas trazadas a FD/norma).
3. **Nº de FK: ~180** (incluidas 14 hacia `dependencia`, 8 subtipos ISA→supertipo, 4 arcos exclusivos, self-FK organigrama/TRD/menú/cita).
4. **Jerarquías ISA/polimórficas mapeadas: 13** (1 class-table total+disjunta, 6 single-table con discriminador, 4 arcos exclusivos con CHECK, 1 polimórfica híbrida, 2 adjacency list).
5. **Entidades nuevas creadas:** `dependencia` (organigrama institucional, destino de FK masivo, derivada del directorio art.9 Ley 1712 — `[INFERIDO]` lo no explícito), más `tipo_arco` e `item_evaluacion_sus` extraídas por normalización.

Archivos insumo (rutas absolutas): `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/00-inventario.md`, `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/01-dependencias.md`, `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/02-normalizacion.md`, `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/_investigacion-web.md`; metodología `/home/sacunpolo/Documentos/mis-skills/jose-bd.md`.

---

# DELTA — 2ª pasada profunda del modelo lógico

> Conteo entidad-por-entidad (135) + verificación campo-por-campo de tablas núcleo contra `00-inventario.md`. **Cobertura: 135/135 entidades, 612/612 campos, 0 omitidas.** Sin tablas ni columnas faltantes. 6 ajustes de integridad declarativa a integrar en Fase física.

## Cobertura (§F)
135 de 135 entidades canónicas presentes (recorrido C01→C135). + `dependencia` (nueva), `tipo_arco`, `item_evaluacion_sus` (horario_canal ya era C49) → **138 tablas**. `dependencia` suficiente para sus 14 FK entrantes (`codigo` UNIQUE para `radicado.prefijo_dependencia ON UPDATE CASCADE`; `id_dependencia` para el resto).

## Delta REAL a corregir antes del físico (6 ítems — integridad declarativa, no estructura)
1. **E-2/C-2 🔴 `alerta_cumplimiento` polimórfico sin tratamiento**: documentar patrón `(entidad_tipo, entidad_id)` con CHECK de dominio + trigger de validación, igual que `notificacion`/`log_auditoria` (inv. L601).
2. **D-9 🔴 `expediente_electronico.sgdea_referencia` NOT NULL contradice "NULL hasta sync SGDEA"** (inv. L336/L510) → debe ser **NULLABLE**; la UNIQUE pasa a parcial `WHERE sgdea_referencia IS NOT NULL`.
3. **D-5 `interop_tsa_config`**: formalizar UNIQUE parcial `(id_environment) WHERE activo` (hoy solo en prosa; inv. C85).
4. **D-2 `sede_fisica`**: nota de trigger de cardinalidad "≤5 filas con orden_footer" (no expresable como CHECK de columna; inv. L710).
5. **E-1 `transparencia_publicacion`**: especificar el TRIGGER de cobertura total+disyunta del ISA (marcado pendiente en §4, sin definición). Riesgo de integridad ISA si se omite.
6. **D-7/C-3 unificaciones documentales**: correo a RFC 5322 (no mezclar 5321/5322); documentar la divergencia DELIBERADA `id_responsable→servidor_publico` (transparencia/SIGEP) vs `→usuario_interno` (CMS) para que el físico no la colapse; alias `fecha_baja`≡`deactivated_at` en `usuario_interno`.

## Confirmado completo sin delta
Puentes M:N (7), históricos con clave temporal (11), append-only (log_auditoria/consentimiento), self-FK (dependencia/trd_serie/menu/cita/representante), las 13 jerarquías ISA/polimórficas. PK impuesto/radicado y `dependencia` correctas. Vacíos [PENDIENTE] (P-01..P-23) son valores de dominio a poblar, NO estructuras faltantes.

---

# DELTA — Ronda 2 del modelo lógico (tablas descompuestas)

> Aplica las 7 descomposiciones de la Ronda 2. Completitud campo-a-campo (C3) de las tablas nuevas; cambios de las modificadas; campos omitidos integrados. 🔴 = cambio estructural.

## A. Tablas NUEVAS (fichas completas)

### A.1 🔴 `dependencia` (C136) — organigrama, BCNF (PK natural `codigo`)
| columna | tipo | NULL | clave | CHECK/UNIQUE | traza |
|---|---|---|---|---|---|
| codigo | VARCHAR(20) | NO | **PK** | — | B.6; origen prefijo radicado SM-{COD} |
| nombre | VARCHAR(300) | NO | — | UNIQUE | B.7; [WEB:H-06] art.9 Ley 1712 |
| descripcion | TEXT | SÍ | — | — | B.7 |
| responsable_id | BIGINT | SÍ | FK→servidor_publico(id) ON DEL SET NULL | — | I-25 |
| sede_id | BIGINT | SÍ | FK→sede_fisica(id) ON DEL SET NULL | — | B.7 |
| es_externa | BOOLEAN | NO | — | DEFAULT FALSE | B.5 RN-04-D03 |
| entidad_externa_nombre | VARCHAR(300) | SÍ | — | CHECK (es_externa=FALSE OR NOT NULL) | B.5 |
| activa | BOOLEAN | NO | — | DEFAULT TRUE | B.7 |
| padre_id | VARCHAR(20) | SÍ | FK→dependencia(codigo) self ON DEL RESTRICT | CHECK (padre_id<>codigo) | adjacency list |
| tipo_unidad | VARCHAR(40) | SÍ | — | CHECK ∈ (secretaria,oficina,direccion,subdireccion,despacho,grupo,otro) | [INFERIDO] |
| sigla | VARCHAR(30) | SÍ | — | — | member_code RN-B3-032 |
| correo_institucional | VARCHAR(254) | SÍ | — | CHECK RFC 5322 | [WEB:H-06] |
| telefono | VARCHAR(20) | SÍ | — | CHECK +57 | [WEB:H-06] |
| ubicacion_fisica | VARCHAR(500) | SÍ | — | — | [WEB:H-06] |
| horario_atencion | TEXT | SÍ | — | — | [WEB:H-06] |
| created_at/updated_at | TIMESTAMP | NO | — | DEFAULT now() | auditoría |

### A.2 🔴 `ciudadano_juridica` (subtipo class-table) — BCNF (h41,h42)
| columna | tipo | NULL | clave | traza |
|---|---|---|---|---|
| id_tipo_documento | BIGINT | NO | **PK/FK→ciudadano** ON DEL CASCADE | h41/h42 LHS |
| numero_documento | VARCHAR(30) | NO | **PK/FK→ciudadano** | h41/h42 LHS |
| razon_social | VARCHAR(300) | NO | — | h41 (NOT NULL en subtipo) |
| representante_legal_id | BIGINT | SÍ | FK→ciudadano(id_ciudadano) ON DEL SET NULL | h42 |
Trigger cobertura: `ciudadano.es_persona_juridica=TRUE ⟺ existe fila aquí` (disjoint+parcial).

### A.3 🔴 `respuesta_item_sus` (=`item_evaluacion_sus`, unificado) — BCNF/1FN (h43)
`{id_evaluacion(PK,FK→evaluacion_sus ON DEL CASCADE), numero_item(PK, CHECK 1-10)} → respuesta_likert(SMALLINT, CHECK 1-5)`. NO suma tabla (ya prevista).

### A.4 🔴 `respuesta_encuesta` — BCNF/1FN (h44)
`{id_encuesta(PK,FK→encuesta_experiencia ON DEL CASCADE), numero_pregunta(PK, CHECK 1-3)} → valor_respuesta(TEXT)`; `id_pregunta` FK→pregunta_encuesta [CONDICIONAL P-13].

### A.5 🔴 `pregunta_encuesta` — BCNF — **[CONDICIONAL P-13]**
`id_pregunta(PK) → texto(VARCHAR500), orden(SMALLINT), id_tramite(FK→tramite nullable), activa`. Solo si las preguntas son configurables; si fijas, no se crea.

## B. Tablas MODIFICADAS (cambios)
- 🔴 `ciudadano` (supertipo): **QUITAR** `razon_social`, `representante_legal_id` (→ ciudadano_juridica) y el CHECK condicional asociado; conservar discriminador `es_persona_juridica`. 26→24 col.
- 🔴 `cita`: contacto condicional. **CHECK bidireccional**: `(id_ciudadano NOT NULL AND nombre/correo/telefono_contacto NULL) OR (id_ciudadano NULL AND correo_contacto NOT NULL)`. Vista `vw_cita_contacto` COALESCE. `id_dependencia` ahora FK→dependencia(codigo). Elimina transitiva → BCNF.
- 🔴 `contrato`: `porcentaje_ejecutado` → `GENERATED ALWAYS AS (CASE WHEN monto>0 THEN valor_ejecutado/monto*100 ELSE 0 END) STORED`; `pagos_pendientes` → `GENERATED ALWAYS AS (monto-pagos_realizados) STORED`; `tiene_otrosi` → vista (no columna base); **añadir** `estado_ejecucion VARCHAR(30) CHECK ∈ (en_ejecucion,suspendido,terminado,liquidado,cedido)` [INFERIDO enum SECOP, B.3].
- 🔴 `solicitud`: **QUITAR 7 columnas espejo** (es_borrador, paso_actual_borrador, fecha_expiracion_borrador, fecha_desistimiento, motivo_desistimiento, efecto_sap, fecha_sap_aplicado); fuente única en C21/C25/C26. Conservar `estado`, `etapa_actual`, `inicio_gestion`. 30→23 col. Trigger estado↔existencia 1:1.
- 🔴 `evaluacion_sus`: **QUITAR** `item_1..item_10`; `puntaje_sus` derivado por trigger sobre respuesta_item_sus (no valor base).
- 🔴 `encuesta_experiencia`: **QUITAR** `respuesta_pregunta_1/2/3`; quedan `id, id_solicitud, estrellas(CHECK 1-5), comentario, fecha`.

## C. Campos omitidos integrados
| Tabla | columna añadida | tipo / CHECK | fuente |
|---|---|---|---|
| `menu_navegacion` | es_obligatorio | BOOLEAN DEFAULT FALSE | B.1 |
| `menu_navegacion` | aria_label | VARCHAR(300) | B.1 |
| `noticia` | ratio_imagen | VARCHAR(10) CHECK ∈ ('4:3','16:9') | B.2 |
| `contrato` | estado_ejecucion | (ver B) | B.3 |
| `requerimiento_subsanacion` | nueva_fecha_vencimiento_tramite | DATE | B.10 RN-03-D05 |
| `pago` | (cardinalidad 1:N) | UNIQUE parcial (id_solicitud) WHERE estado='APROBADO' | B.11 |
| `informe_pqrsd` | url_kogui, tiempo_promedio_respuesta | VARCHAR(500), DECIMAL(8,2) | B.14 |
| `notificacion`/`intento_notificacion` | canal_envio→canal, destinatario→destinatario, estado_envio→estado | (mapeo Δ-7) | Δ-7 C54 |

Δ-7 mapeo: `notificacion_cita`/`recordatorio_cita` se absorbe en `notificacion` (entidad_tipo='CITA', entidad_id=cita.id, polimorfismo H11) + `intento_notificacion`; `recordatorio_cita` conserva su config propia. Sin pérdida de campos.

## D. Matriz de FK — cambios
**9 FK nuevas R2**: ciudadano_juridica.{tipo,num}→ciudadano (CASCADE), ciudadano_juridica.representante_legal_id→ciudadano (SET NULL), respuesta_item_sus.id_evaluacion→evaluacion_sus (CASCADE), respuesta_encuesta.id_encuesta→encuesta_experiencia (CASCADE), respuesta_encuesta.id_pregunta→pregunta_encuesta (SET NULL, cond.), pregunta_encuesta.id_tramite→tramite (CASCADE, cond.), dependencia.padre_id→dependencia self (RESTRICT), dependencia.responsable_id→servidor_publico (SET NULL), dependencia.sede_id→sede_fisica (SET NULL).
🔴 **12 FK→dependencia re-apuntadas a `dependencia(codigo)`** (PK natural): tramite, pqrsd, canal_atencion, servidor_publico, servicio_agendable, asignacion_dependencia, traslado_competencia, mecanismo_participacion, franja_horaria, cita, micrositio, radicado(prefijo). **ACCIÓN FÍSICA: re-tipar columnas FK origen de BIGINT a VARCHAR(20)** (en R1 apuntaban al surrogate). Todas ON DELETE RESTRICT ON UPDATE CASCADE. 0 FK colgante.

## E. Herencia/polimorfismo actualizado
- 🔴 `ciudadano` RE-CLASIFICADA single-table → **class-table** (supertipo + ciudadano_juridica). Disjoint+parcial. Lossless Heath caso fuerte. NO 4NF (FD funcional). → **class-table: 2** (transparencia + ciudadano); single-table: 5.
- `dependencia` adjacency list confirmada (FD funcional self-FK, NO MVD/JD; anti-ciclo por trigger). → adjacency list: 3 (trd_serie, dependencia, menu_navegacion).
- Total jerarquías: 13 (redistribución, sin cambio de total).

## F. Conteo final + verificación
- **Tablas: 141 firmes (+1 condicional pregunta_encuesta = 142 si P-13)**. Nuevas netas R2: ciudadano_juridica, respuesta_encuesta (+pregunta_encuesta cond.); respuesta_item_sus=item_evaluacion_sus (ya contada); dependencia C136 completada.
- **Columnas: ~654** (reubicaciones 3FN/BCNF; −7 solicitud, −2 ciudadano, −10 SUS, −3/5 encuesta; +4+4+5 nuevas; +8 omitidas).
- **FK: ~189** (+9 nuevas; 12 re-apuntadas a dependencia).
- **Verificación: 0 entidades sin modelar, 0 tablas faltantes, 0 columnas sin traza.** 136 conceptos (135 + dependencia) con ficha completa.

## G. Veredicto: LISTO para el físico
Descomposiciones probadas (Heath caso fuerte ciudadano/solicitud; simple SUS/encuesta; BCNF cita/contrato/dependencia). Honestidad: ciudadano ISA funcional NO 4NF; solicitud redundancia NO violación FN. **Acción física obligatoria**: re-tipar 12 FK→dependencia a VARCHAR(20). **Triggers a especificar en físico**: cobertura ISA ciudadano (disjoint+parcial), cobertura ISA transparencia (total+disjunta), estado↔existencia solicitud↔C21/C25/C26, puntaje_sus sobre respuesta_item_sus, anti-ciclo árbol dependencia, tiene_otrosi vista. Pendientes no estructurales: P-13 (pregunta configurable), estado_ejecucion enum (confirmar SECOP). + los 6 ítems declarativos del DELTA R1.

---

# DELTA — Ronda 3 de modelo lógico

> **Rol:** auditor de completitud del esquema lógico (jose-bd §4.3, mandato campo-a-campo). **Insumo:** `02-normalizacion.md §DELTA Ronda 3` (A.1 PIT/PID/POM con MVD Fagin; B.1 rate_limit_contador; B.2 interop_tsa_config re-clave; B.3 mecanismo FK; B.4 incidente columnas; B.5 contenido_traduccion; C derivados NO materializar) + `01-dependencias.md §DELTA Ronda 3` (FD-PIT/PID/RIS/RES/DEP-3/RLC/TSA/MEC/CON; FD-DEP-3 cierra P-08, clave radicado `{prefijo_dependencia, anio, consecutivo_anual}`) + `00-inventario.md §DELTA Ronda 3 R3.A–R3.F`.
> **Honestidad / anti-recuento:** las Rondas 1-2 ya crearon `dependencia` (PK natural `codigo`, R2 §A.1), `respuesta_item_sus`/`item_evaluacion_sus`, `respuesta_encuesta`, `pregunta_encuesta` (cond.) y `ciudadano_juridica`. **R3 NO las recuenta.** R3 entrega SOLO lo neto nuevo (PIT/PID/POM, rate_limit_contador, contenido_traduccion firme) + correcciones de clave/FK/columnas. Cada campo cita fuente. `[INFERIDO]` marcado.
> 🔴 = bloqueante para el modelo físico (C3). Esquema ÚNICO INTEGRADO. Tipos genéricos (el físico mapea a PostgreSQL 15+).

---

## (a) DDL lógico — tablas NUEVAS de R3 (fichas completas, todos los campos)

### a.1 🔴 `plan_integracion_tramite` (PIT, C137) — N-02; normaliza JSONB `plan_integracion.tramites_incluidos`

**Propósito:** detalle consultable de los trámites incluidos en el plan de convergencia GOV.CO. Saca el grupo multivaluado del JSONB (1FN/4FN; MVD `id_plan ↠ PIT | PID | POM`, norm §A.1). FN: BCNF/4FN. **Origen FD:** FD-PIT-1 (deps §A.1, r51-r54).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_plan | BIGINT | NO | **PK** | →plan_integracion(id) ON DELETE CASCADE ON UPDATE CASCADE | — | — | — | FD-PIT-1; R-04; inv. N-02 |
| id_tramite | BIGINT | NO | **PK** | →tramite(id_tramite) ON DELETE RESTRICT ON UPDATE CASCADE | — | — | — | FD-PIT-1; R-05 (FK catálogo) |
| nombre_tramite | VARCHAR(500) | SÍ | — | — | — | CHECK (id_tramite IS NOT NULL OR nombre_tramite IS NOT NULL) | — | norm §A.1 PIT-2: derivado de `tramite.nombre` si id_tramite NOT NULL; hecho propio si NULL (trámite aún no en SUIT). NO persistir cuando deriva |
| solicitudes_anio | INTEGER | SÍ | — | — | — | CHECK (solicitudes_anio IS NULL OR solicitudes_anio >= 0) | — | inv. N-02 (solicitudes/año); r51 |
| accion | VARCHAR(20) | NO | — | — | — | CHECK ∈ (converger, enlazar, retirar) | — | inv. N-02 (acción); r52 |
| fecha_objetivo | DATE | SÍ | — | — | — | — | — | inv. N-02 (fecha); r53 |
| id_responsable | BIGINT | SÍ | — | →servidor_publico(id) ON DELETE SET NULL ON UPDATE CASCADE | — | — | — | inv. N-02 (responsable); R-02; r54 |

> **PK natural:** `{id_plan, id_tramite}` (cierre `{id_plan,id_tramite}⁺=R`, deps §F). **Integridad de entidad:** ambos componentes NOT NULL. Si se admite `id_tramite` NULL (trámite fuera de SUIT, R3.A N-02 "FK nullable"), entonces la PK natural no aplica → surrogate `id` (BIGINT identity) + UNIQUE parcial `(id_plan, nombre_tramite)`; decisión a fijar en físico según P-13-análogo (no bloquea FN). **Derivado NO materializado:** `nombre_tramite` cuando `id_tramite NOT NULL` (norm §C).

### a.2 🔴 `plan_integracion_dominio` (PID, C138) — N-03; normaliza JSONB `plan_integracion.dominios_web`

**Propósito:** dominios web del Distrito a converger/enlazar/retirar en el plan. FN: BCNF/4FN. **Origen FD:** FD-PID-1 (deps §A.2, r55).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_plan | BIGINT | NO | **PK** | →plan_integracion(id) ON DELETE CASCADE ON UPDATE CASCADE | — | — | — | FD-PID-1; R-04 |
| valor | VARCHAR(2048) | NO | **PK** | — | — | CHECK (length(valor) > 0) | — | inv. N-03 (dominio/url); r55 |
| tipo | VARCHAR(30) | SÍ | — | — | — | — | — | inv. N-03 (clasificación del dominio); r55 |

> **PK natural:** `{id_plan, valor}` (`{id_plan,valor}⁺=R`, deps §F). Integridad de entidad: ambos NOT NULL.

### a.3 🔴 `plan_integracion_otro_medio` (POM, C139) — N-03; normaliza JSONB `plan_integracion.otros_medios`

**Propósito:** otros medios del Distrito (apps, chatbots, líneas PQR) en el plan de convergencia. Estructura hermana de PID (mismo patrón list-of-values; NO colapsable con PID por ser MVD independiente, norm §A.1). FN: BCNF/4FN. **Origen FD:** FD-PID-1 (r55).

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_plan | BIGINT | NO | **PK** | →plan_integracion(id) ON DELETE CASCADE ON UPDATE CASCADE | — | — | — | FD-PID-1; R-04 |
| valor | VARCHAR(500) | NO | **PK** | — | — | CHECK (length(valor) > 0) | — | inv. N-03 (medio: app/chatbot/PQR); r55 |
| tipo | VARCHAR(30) | SÍ | — | — | — | CHECK (tipo IS NULL OR tipo ∈ (app, chatbot, pqr, otro)) | — | inv. N-03 (clasificación del medio); r55 |

> **PK natural:** `{id_plan, valor}`. Integridad de entidad: ambos NOT NULL.

### a.4 🔴 `rate_limit_contador` (C140) — F-04 / B.1 norm; throttling persistible

**Propósito:** contador de límite de tasa por sujeto/recurso/ventana (login, PQRSD, búsqueda). Eleva el rate-limiting de FUERA-DE-BD a requisito persistible (RNF-09-D02 + HU-09-D07 + stakeholders ST-05). FN: BCNF (LHS único = clave). **Origen FD:** FD-RLC-1 (deps §A.6, r62). **`[INFERIDO parcial]`:** la fuente fija la necesidad y las 3 dimensiones (sujeto/ventana/recurso) y los hechos (contador/bloqueado_hasta), pero NO nombra las columnas exactas; inferencia controlada marcada en inventario F-04.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| clave_sujeto | VARCHAR(100) | NO | **PK** | — | — | CHECK (length(clave_sujeto) > 0) | — | [INFERIDO] FD-RLC-1: IP o id_sesion; F-04 |
| ventana_inicio | TIMESTAMP | NO | **PK** | — | — | — | — | [INFERIDO] bucket temporal; F-04; r62 |
| recurso | VARCHAR(40) | NO | **PK** | — | — | CHECK ∈ (login, pqrsd, busqueda, radicacion, otro) | — | [INFERIDO] dimensión recurso; F-04; HU-09-D07 |
| contador | INTEGER | NO | — | — | — | CHECK (contador >= 0) | 0 | FD-RLC-1; r62 |
| bloqueado_hasta | TIMESTAMP | SÍ | — | — | — | — | — | FD-RLC-1; r62 (NULL = no bloqueado) |

> **PK natural:** `{clave_sujeto, ventana_inicio, recurso}` (las 3 dimensiones son ortogonales/irreducibles, deps §A.6). **Alternativa de modelado** (decisión física, no FN): campos en `intento_login` con la misma FD — si se elige esa vía, esta tabla no se materializa. Recomendación lógica: tabla autónoma (separa la concern de throttling de la auditoría de intentos; permite throttling sobre PQRSD/búsqueda que no son login).

### a.5 🔴 `contenido_traduccion` (C135, ACTIVA la C135 DIFERIDA) — F-11 / B.5 norm; multilingüe + fallback ES

**Propósito:** traducción de un contenido CMS a un idioma o lengua étnica, con marca de original/fallback al castellano (RN-TX-D05). El inventario la listaba como C135 DIFERIDA con 5 campos; R3 la materializa con la FD y el flag `es_original`. FN: BCNF. **Origen FD:** FD-CON-1 (deps §B.5, r63). **Condicionada** a activación de C135 — se entrega la ficha completa para que el físico la incluya o la deje en `DEFERRED` sin reabrir el modelo lógico.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_contenido | UUID | NO | **PK** | →contenido(id) ON DELETE CASCADE ON UPDATE CASCADE | — | — | — | FD-CON-1; inv. C135 (tipo UUID: contenido es clase B, consistencia R3 pulido #3) |
| idioma | VARCHAR(10) | NO | **PK** | — | — | CHECK (idioma ~ '^[a-z]{2,3}(-[A-Za-z0-9]+)?$') | — | FD-CON-1 (BCP-47 / lengua étnica); inv. C135 |
| titulo_traducido | VARCHAR(500) | SÍ | — | — | — | — | — | inv. C135 (título traducido) |
| cuerpo_traducido | TEXT | SÍ | — | — | — | — | — | FD-CON-1; inv. C135 (cuerpo traducido) |
| es_original | BOOLEAN | NO | — | — | — | — | FALSE | F-11; RN-TX-D05 (marca el idioma origen / fallback ES) |

> **PK natural:** `{id_contenido, idioma}` (`{id_contenido,idioma}⁺=R`, deps §F). **Restricción de negocio (trigger, RN-TX-D05):** a lo sumo UNA fila con `es_original=TRUE` por `id_contenido` → UNIQUE parcial `(id_contenido) WHERE es_original = TRUE`. **Integridad referencial:** CASCADE desde `contenido` (la traducción no sobrevive al contenido base).

---

## (b) ALTER lógicos — columnas y FK añadidas / corregidas sobre tablas EXISTENTES

### b.1 🔴 `radicado` (§1.5) — CORRECCIÓN de clave y FK (cierra P-08)

**Defecto en el modelo actual:** la ficha §1.5 declara `prefijo_dependencia → dependencia(codigo_dependencia)`, pero la Ronda 2 §A.1 redefinió la PK de `dependencia` como `codigo` (no `codigo_dependencia`, que era la columna de la versión surrogate descartada en §1.1). FD-DEP-3 (deps §A.5) ratifica `dependencia.codigo → radicado.prefijo_dependencia`.

```
-- Corregir destino de la FK al nombre real de la PK natural de dependencia:
ALTER radicado  ALTER COLUMN prefijo_dependencia  -- VARCHAR(20), NOT NULL (ya correcto)
  DROP CONSTRAINT fk_radicado_dependencia_codigo_dependencia,
  ADD  FOREIGN KEY (prefijo_dependencia) REFERENCES dependencia(codigo)
       ON DELETE RESTRICT ON UPDATE CASCADE;

-- Clave alterna compuesta natural (ratificada FD-DEP-3 / DELTA Fase 2 §C.1):
ALTER radicado  ADD UNIQUE (prefijo_dependencia, anio, consecutivo_anual);  -- ya presente; ratificada
```

| acción | detalle | traza |
|---|---|---|
| FK corregida | `radicado.prefijo_dependencia → dependencia(codigo)` (no `codigo_dependencia`) ON DELETE RESTRICT ON UPDATE CASCADE | FD-DEP-3; norm §A.4; deps §A.5; cierra **P-08** |
| Clave alterna | `UNIQUE (prefijo_dependencia, anio, consecutivo_anual)` (consecutivo por dependencia+año, SEQUENCE atómico) | deps §F; AGN Acuerdo 060/2001 |
| Entidad referencial | ambos lados existen tras formalizar `dependencia` (R2 §A.1) ⇒ **0 FK colgante** | norm §A.4 |

### b.2 🔴 `incidente_seguridad` (§2.60) — 2 columnas nuevas (doble destino de reporte)

**Defecto:** el inventario/modelo solo tenía `sic_notified_at`; el reporte de brechas tiene DOS destinos independientes (CSIRT/ColCERT vía `csirt_reported_at` **y** SIC/RNBD). FD-INC-1 (deps §B.4): dos hechos funcionalmente independientes, ambos no-primos directos de `id` ⇒ NO transitiva, NO viola FN (falso positivo descartado, norm §D).

| columna añadida | tipo | NULL | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|
| sic_rnbd_reportado | BOOLEAN | NO | — | FALSE | F-01; FD-INC-1; 09 §8 L172 (RNBD registro separado) |
| sic_reporte_fecha | TIMESTAMP | SÍ | CHECK (sic_rnbd_reportado = FALSE OR sic_reporte_fecha IS NOT NULL) | — | F-01; FD-INC-1 (fecha de reporte SIC/RNBD) |

### b.3 `mecanismo_participacion` (§2.36) — FK al dueño funcional

| columna añadida | tipo | NULL | FK | traza |
|---|---|---|---|---|
| id_oficina_responsable | BIGINT | SÍ | →dependencia(id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT | F-06; R-03; FD-MEC-2; 05 §9 L91 (Oficina de Participación, Art.17 Ley 2052) |

> Tipo **`BIGINT → dependencia(id_dependencia)`** (RESUELTO pulido #3 R3, alineado con el físico §DELTA b.3 por independencia física de Codd: todas las FK genéricas a `dependencia` son surrogate BIGINT, no VARCHAR→codigo; la integridad de la clave natural la porta `UNIQUE(codigo)`). No introduce transitiva (no-primo directo de la clave). El `mecanismo_participacion` ya tiene además `id_dependencia → dependencia(id_dependencia)` (§2.36) — `id_oficina_responsable` es semánticamente distinto (dueño funcional vs. dependencia destinataria); ambas FK coexisten con el mismo tipo de destino.

### b.4 🔴 `interop_tsa_config` (§2.71, C85) — CORRECCIÓN de clave (2FN) + columnas de endpoint

**Defecto:** PK surrogate `id` con histórico de configuraciones ⇒ `{ambiente}⁺ ≠ R`, la unicidad "1 activa por ambiente" no estaba como clave correcta (2FN, norm §D). FD-TSA-1 (deps §B.1). La ficha actual además carece de `host_salida`/`puerto` (el modelo tiene `url_tsa` pero no separa host/puerto, F-08).

```
-- Clave natural compuesta con histórico de vigencias:
ALTER interop_tsa_config  ADD UNIQUE (ambiente, vigencia_desde);          -- PK natural lógica
ALTER interop_tsa_config  ADD UNIQUE (ambiente) WHERE es_activo = TRUE;   -- UNIQUE parcial: 1 activa/ambiente
```

| columna añadida / corregida | tipo | NULL | CHECK / UNIQUE | DEFAULT | traza |
|---|---|---|---|---|---|
| ambiente | VARCHAR(10) | NO | CHECK ∈ (QA, PREPROD, PROD); parte de PK natural | — | FD-TSA-1; deps §B.1 (≡ `env_type`) |
| vigencia_desde | DATE | NO | parte de PK natural `(ambiente, vigencia_desde)` | — | FD-TSA-1; patrón política vigente RF-B1-009 |
| es_activo | BOOLEAN | NO | UNIQUE parcial `(ambiente) WHERE es_activo` (≡ `activo` actual) | FALSE | C85 "1 activo por ambiente"; FD-TSA-1 |
| host_salida | VARCHAR(255) | NO | — | 'tsa.gse.com.co' | F-08; _levantamiento/borrador-solicitud-AND L32 |
| puerto | INTEGER | NO | CHECK (puerto BETWEEN 1 AND 65535) | 443 | F-08; borrador-AND L32 |
| proveedor | VARCHAR(100) | NO | — | 'GSE' | F-08; borrador-AND L32 (ya existe `proveedor`, fijar default GSE) |

> **Clave corregida:** de surrogate `{id}` → natural `{ambiente, vigencia_desde}` + UNIQUE parcial `(ambiente) WHERE es_activo`. Conserva surrogate `id` como cómodo si el físico lo prefiere, pero la integridad de entidad/unicidad la garantiza la clave natural compuesta. `url_tsa` existente queda como derivado o se descompone en `host_salida` + `puerto` (recomendado descomponer).

### b.5 `sede_electronica` (§1.2) — flags de cumplimiento y referencia PETI

| columna añadida | tipo | NULL | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|
| declaracion_conformidad_sic | BOOLEAN | NO | — | FALSE | **[INFERIDO]** F-02; 09 §9 L180 (nube extranjera → declaración SIC Art.26 Ley 1581) |
| peti_vigencia | VARCHAR(20) | SÍ | — | '2024-2027' | F-10; borrador-AND L26; 12 §9 L152 (PETI 2024-2027 vigente) |
| id_peti_referencia | VARCHAR(100) | SÍ | — | — | F-10 (referencia documental al PETI) |

### b.6 `contenido` (§2 módulo 12) — soporte multilingüe (par de `contenido_traduccion`)

| columna añadida | tipo | NULL | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|
| idioma_origen | VARCHAR(10) | NO | CHECK (idioma_origen ~ '^[a-z]{2,3}(-[A-Za-z0-9]+)?$') | 'es' | F-11; RN-TX-D05 (idioma original para fallback al castellano) |

### b.7 `grupo_interes` (§2.40) — caracterización formal

| columna añadida | tipo | NULL | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|
| caracterizacion | TEXT | SÍ | — | — | F-07; 02/05 §9 A-07 (contenido de la caracterización DAFP) |
| tiene_caracterizacion_formal | BOOLEAN | NO | — | FALSE | F-07; A-07 (existencia de caracterización formal) |

### b.8 `dataset` (§2.82) / `registro_activo_informacion` — modo de federación + derivado a NO materializar

| acción | detalle | traza |
|---|---|---|
| columna añadida | `dataset.actualizacion_automatica BOOLEAN NOT NULL DEFAULT FALSE` (modo de federación a datos.gov.co: automático vs manual). Misma columna en `registro_activo_informacion` | F-12; 11 §9 L96 |
| derivado NO materializar | `dataset.metadatos_completos`: **NO persistir** o columna `GENERATED` a partir de la presencia de metadatos obligatorios en `dataset_metadata` (era 3FN materializado, norm §C/§D). Sustituye el `BOOLEAN` base actual de §2.82 | norm §C; deps §D; R3.D |

### b.9 `interop_external_system` (§2.76) — filas seed del stack real (datos, no estructura)

| acción | detalle | traza |
|---|---|---|
| seed | Añadir filas: `Orfeo` (SGDEA), `DigitalOcean` (hosting), `Cloudflare` (DNS/WAF/CDN) al catálogo `interop_external_system` (ampliar el CHECK de `system_key` para admitirlas) | F-09; borrador-AND L31-33; 12 §8 L142 |
| nota | `[INFERIDO/RNF]` SIEM (correlación de logs zona auditoría, F-03) como fila del catálogo si se confirma; hoy FUERA DE BD salvo registrar el catálogo | F-03; 09 §9 L181 |

> Es ALTER de datos (seed) + ampliación de dominio CHECK, no cambio estructural de columnas.

---

## (c) Brechas detectadas contra inventario R3 (tablas/campos del inventario AÚN ausentes en el modelo)

> Contraste línea-a-línea del modelo lógico actual (`03-modelo-logico.md` R1 + DELTA R2) contra `00-inventario.md §R3.A–R3.C`. Esto responde directamente a la queja del usuario ("faltan tablas, faltan campos").

### c.1 Tablas del inventario R3 que NO estaban en el modelo (las crea esta Ronda 3, sección (a))

| inventario | concepto | estado previo en modelo | resuelto por |
|---|---|---|---|
| R3.A N-02 | `plan_integracion_tramite` (PIT) | **AUSENTE** — el modelo tenía `plan_integracion.tramites_incluidos` como JSONB (§2.13), violando 1FN | (a.1) 🔴 |
| R3.A N-03 | `plan_integracion_dominio` (PID) | **AUSENTE** — JSONB `dominios_web` (§2.13) | (a.2) 🔴 |
| R3.A N-03 | `plan_integracion_otro_medio` (POM) | **AUSENTE** — `otros_medios` TEXT (§2.13) | (a.3) 🔴 |
| R3.B F-04 | `rate_limit_contador` | **AUSENTE** — no existía; rate-limiting estaba FUERA DE BD | (a.4) 🔴 |
| C135 / F-11 | `contenido_traduccion` | listada DIFERIDA en inventario, **sin ficha en el modelo** | (a.5) 🔴 (ficha completa entregada) |

### c.2 Campos del inventario R3 que NO estaban en el modelo (los añade la sección (b))

| inventario | campo ← entidad | estado previo | resuelto por |
|---|---|---|---|
| F-01 | `incidente_seguridad ← sic_rnbd_reportado, sic_reporte_fecha` | el modelo solo tenía `sic_notified_at` (§2.60) | (b.2) 🔴 |
| F-02 | `sede_electronica ← declaracion_conformidad_sic` | ausente | (b.5) `[INFERIDO]` |
| F-06 | `mecanismo_participacion ← id_oficina_responsable` (FK→dependencia) | ausente (solo `id_dependencia`) | (b.3) |
| F-07 | `grupo_interes ← caracterizacion, tiene_caracterizacion_formal` | ausente | (b.7) |
| F-08 | `interop_tsa_config ← host_salida, puerto, proveedor(GSE)` | el modelo tenía `url_tsa`/`proveedor` sin host/puerto separados (§2.71) | (b.4) 🔴 |
| F-10 | `sede_electronica ← peti_vigencia, id_peti_referencia` | ausente | (b.5) |
| F-11 | `contenido ← idioma_origen` | ausente | (b.6) |
| F-12 | `dataset/registro_activo_informacion ← actualizacion_automatica` | ausente | (b.8) |

### c.3 Relaciones/FK del inventario R3 que NO estaban (las cubre (a)/(b))

| inventario | relación | estado previo | resuelto por |
|---|---|---|---|
| R-01 | `dependencia →(self) padre_id` | ya en R2 §A.1 — **OK, no es brecha** | — |
| R-02 | `dependencia.responsable_id → servidor_publico` | ya en R2 §A.1 — **OK** | — |
| R-03 | `mecanismo_participacion.id_oficina_responsable → dependencia` | ausente | (b.3) |
| R-04 | `plan_integracion → PIT/PID/POM` | ausente (JSONB) | (a.1–a.3) 🔴 |
| R-05 | `tramite ↔ plan_integracion_tramite` (N:1 nullable) | ausente | (a.1) 🔴 |
| R-06 | `encuesta_experiencia → respuesta_encuesta → pregunta_encuesta` | ya en R2 §A.4/A.5 — **OK (cond. P-13)** | — |
| R-07 | `evaluacion_sus → respuesta_item_sus` | ya en R2 §A.3 / §3.5 `item_evaluacion_sus` — **OK** | — |
| R-08 | `funcionario_apoyo.id_sede → sede_fisica` | **DIFERIDA** — `funcionario_apoyo` (N-04) no modelada (ver c.4) | pendiente decisión |
| R-09 | `solicitud/pqrsd ↔ dependencia` (FK formal) | ya re-apuntada en R2 §D — **OK** | — |
| R-10 | `suscripcion_alerta_ciudadano.id_ciudadano → ciudadano` | **DIFERIDA** `[INFERIDO]` — N-05 no modelada | pendiente |

### c.4 Entidades R3 diferidas/condicionadas — NO modeladas a propósito (no son brecha de error, son decisión pendiente)

| inventario | entidad | razón de NO modelar | acción |
|---|---|---|---|
| R3.A N-04 | `funcionario_apoyo` | sin decisión "subtipo de `usuario_interno` por `ambito` vs. tabla propia" (Ronda 2 B.8 abierta); 06 §2.12 da 3 campos (sede_id, nombre_completo, disponible) | **decisión de diseño** — si tabla propia: `id`(PK), `id_sede`(FK→sede_fisica RESTRICT), `nombre_completo`, `disponible`(BOOL), `id_usuario_interno`(FK nullable). No bloquea FN |
| R3.A N-05 | `suscripcion_alerta_ciudadano` | `[INFERIDO]` — la fuente sugiere alertas automáticas a grupos de interés pero NO detalla campos | **→ investigador** (R3.E implícito) / confirmar alcance |
| R3.A N-08 | `definicion_glosario` | `[INFERIDO]` — candidato a subtipo de `contenido` (tipo='glosario'), no entidad propia | **decisión** — probablemente NO tabla nueva (discriminador en `contenido`) |
| R3.D | `carpeta_ciudadana.historial_*` (JSONB) | RF-B2-021 prohíbe réplica permanente ⇒ JSONB de traza ACEPTABLE | **NO descomponer** (documentado, norm §C) |

### c.5 🔴 Inconsistencia interna detectada (NO en inventario, pero rompe integridad referencial — reportada por rigor)

| ítem | hallazgo | corrección |
|---|---|---|
| `dependencia` PK divergente | La ficha §1.1 (R1) define `dependencia` con **PK surrogate `id_dependencia` BIGINT** y columna `codigo_dependencia`. El DELTA R2 §A.1 la **redefine con PK natural `codigo` VARCHAR(20)** y re-apunta 12 FK a `dependencia(codigo)`. Coexisten dos definiciones contradictorias en el mismo documento | **§1.1 queda OBSOLETA**; la definición vigente es R2 §A.1 (PK `codigo`). Toda FK→dependencia es `VARCHAR(20)→dependencia(codigo)`. Las fichas R1 que aún dicen `→dependencia(id_dependencia)` (§1.3 tramite L119, §1.6 pqrsd L201, §2.31, §2.32, §2.36, §2.43, §2.45, §2.46/franja, §2.48/cita) deben re-apuntarse a `(codigo)` y re-tiparse a VARCHAR(20) — esto **YA lo ordenó R2 §D** ("ACCIÓN FÍSICA: re-tipar 12 FK") pero las fichas R1 no se editaron. Se ratifica aquí como bloqueante de integridad referencial |
| `radicado.prefijo_dependencia` destino | §1.5 apunta a `dependencia(codigo_dependencia)` (columna inexistente en la def. vigente) | (b.1) 🔴 — corregir a `dependencia(codigo)` |

> **Conclusión de la queja del usuario:** las tablas que "faltaban" son las 3 hijas del plan (PIT/PID/POM), `rate_limit_contador` y `contenido_traduccion` (5 tablas — sección a). Los campos que "faltaban" son los 8 grupos de la sección (b). NO faltaba `dependencia` ni las tablas SUS/encuesta (ya creadas en R2; el usuario probablemente las veía ausentes porque las fichas R1 §1.1/§1.5 quedaron con la definición vieja contradictoria — c.5). Las "tablas anchas" son `plan_integracion` (3 JSONB → resuelta) y `interop_tsa_config` (clave mal puesta → resuelta).

---

## (d) Conteo del delta Ronda 3 del modelo lógico

| Categoría | Cantidad | Detalle |
|---|---|---|
| **Tablas NUEVAS netas** | **5** | `plan_integracion_tramite` (C137), `plan_integracion_dominio` (C138), `plan_integracion_otro_medio` (C139), `rate_limit_contador` (C140), `contenido_traduccion` (C135 activada) |
| Tablas que R3 solo RATIFICA (ya en R2, NO se recuentan) | 5 | `dependencia`, `respuesta_item_sus`/`item_evaluacion_sus`, `respuesta_encuesta`, `pregunta_encuesta` (cond.), `ciudadano_juridica` |
| Tablas diferidas/condicionadas (NO modeladas) | 3 | `funcionario_apoyo` (N-04), `suscripcion_alerta_ciudadano` (N-05 `[INFERIDO]`), `definicion_glosario` (N-08 `[INFERIDO]`) |
| **Columnas AÑADIDAS** a tablas existentes | **13** | incidente_seguridad +2; sede_electronica +3 (1 `[INFERIDO]`); mecanismo_participacion +1; grupo_interes +2; interop_tsa_config +3 (host_salida, puerto, vigencia_desde/ambiente formalizadas); contenido +1; dataset/registro_activo +1 |
| Columnas en tablas NUEVAS | 23 | PIT 7 + PID 3 + POM 3 + rate_limit 5 + contenido_traduccion 5 |
| **FK AÑADIDAS / corregidas** | **7** | PIT→plan_integracion (CASCADE), PIT→tramite (RESTRICT), PIT→servidor_publico (SET NULL), PID→plan_integracion, POM→plan_integracion, mecanismo→dependencia (b.3), contenido_traduccion→contenido (CASCADE) **+ 1 corregida**: radicado.prefijo_dependencia→dependencia(codigo) (b.1, cierra P-08) |
| **Claves corregidas** | 2 | `interop_tsa_config` {id}→{ambiente, vigencia_desde}+UNIQUE parcial; `radicado` ratifica `UNIQUE(prefijo_dependencia, anio, consecutivo_anual)` |
| Derivados a NO materializar (R3) | 3 | `dataset.metadatos_completos`, `plan_integracion_tramite.nombre_tramite` (si id_tramite NOT NULL), `mecanismo_participacion.estado=cerrado` (trigger temporal) |
| Restricciones UNIQUE parciales nuevas | 2 | `contenido_traduccion (id_contenido) WHERE es_original`; `interop_tsa_config (ambiente) WHERE es_activo` |
| Inconsistencias internas reportadas (c.5) | 2 🔴 | def. `dependencia` PK divergente (R1 §1.1 obsoleta vs R2 §A.1); `radicado` destino FK con nombre de columna inexistente |
| **Total tablas del esquema tras R3** | **~146** | ~141 (R2) + PIT/PID/POM/rate_limit_contador + contenido_traduccion (5 netas; coincide con norm §F ~148 incluyendo condicionales) |

**Veredicto Ronda 3 (modelo lógico):** 5 tablas nuevas con todos sus campos (PK/FK/UNIQUE/NOT NULL/CHECK/DEFAULT, integridad de entidad y referencial declaradas), 13 columnas añadidas, 7 FK añadidas/corregidas (incluye el cierre de **P-08** en `radicado`), 2 claves corregidas. Contraste contra inventario R3: **0 tablas firmes del inventario quedan sin modelar** tras esta ronda; 3 entidades quedan diferidas por decisión/investigación (no por error). **2 inconsistencias internas 🔴** (c.5) deben sanearse antes del físico.

---

# DELTA — Ronda 3.1 (incorporación de hallazgos de investigación web)

> Origen: `_investigacion-web.md §Ronda 3 (R3.E)`. Dos hallazgos con respaldo documental se elevan a tabla.
> Tipos de FK verificados contra las PK destino (todas clase A BIGINT): `ciudadano.id_ciudadano` BIGINT, `grupo_interes.id` BIGINT.

## a.6 🟢 `carpeta_autorizacion_acceso` (C141) — NORM-BACKED (Anexo 1 SCD, Carpeta Ciudadana Digital, P-18)

**Propósito:** registro de las autorizaciones que un ciudadano (titular) otorga a una entidad/tercero para acceder a un conjunto de datos de su Carpeta Ciudadana, con acción y vigencia. La investigación confirmó (Anexo 1 SCD §10) que las autorizaciones son **entidad propia 1:N** (titular + dato/entidad + acción + vigencia), NO un campo plano de `carpeta_ciudadana`. `carpeta_ciudadana` es 1:1 con `ciudadano` (la carpeta del titular); la FK apunta al titular.

| columna | tipo | NULL | PK | FK | UNIQUE | CHECK | DEFAULT | traza |
|---|---|---|---|---|---|---|---|---|
| id_autorizacion | BIGINT | NO | **PK** | — | — | — | identity | clase A surrogate |
| id_ciudadano | BIGINT | NO | — | →ciudadano(id_ciudadano) ON DELETE CASCADE ON UPDATE RESTRICT | — | — | — | titular (Anexo 1 SCD §10); `ciudadano` clase A |
| entidad_autorizada | VARCHAR(255) | NO | — | — | — | CHECK (length(entidad_autorizada)>0) | — | entidad/tercero autorizado (SCD §10) |
| tipo_entidad | VARCHAR(20) | SÍ | — | — | — | CHECK (tipo_entidad IS NULL OR tipo_entidad ∈ (publica, privada, persona)) | — | [INFERIDO] clasificación del tercero |
| conjunto_dato | VARCHAR(150) | NO | — | — | — | CHECK (length(conjunto_dato)>0) | — | conjunto de datos/sector autorizado (SCD organiza por conjuntos) |
| accion | VARCHAR(20) | NO | — | — | — | CHECK ∈ (consultar, compartir, descargar) | — | acción autorizada (SCD §10) |
| fecha_inicio_vigencia | DATE | NO | — | — | — | — | CURRENT_DATE | vigencia desde |
| fecha_fin_vigencia | DATE | SÍ | — | — | — | CHECK (fecha_fin_vigencia IS NULL OR fecha_fin_vigencia >= fecha_inicio_vigencia) | — | NULL = indefinida |
| estado | VARCHAR(15) | NO | — | — | — | CHECK ∈ (activa, revocada, vencida) | 'activa' | ciclo de vida de la autorización |
| fecha_revocacion | TIMESTAMPTZ | SÍ | — | — | — | CHECK (estado <> 'revocada' OR fecha_revocacion IS NOT NULL) | — | RN: revocación exige fecha |
| created_at | TIMESTAMPTZ | NO | — | — | — | — | now() | auditoría |

> **FD:** `id_autorizacion → R` (BCNF). **Clave natural / invariante de negocio:** a lo sumo UNA autorización ACTIVA por (titular, entidad, conjunto) → UNIQUE parcial `(id_ciudadano, entidad_autorizada, conjunto_dato) WHERE estado='activa'`. **Anti-IDOR (RN-09-D01):** si el identificador se expone en API externa de la CCD, migrar PK a UUID (clase B); por defecto BIGINT clase A (registro interno). **NO descompone** `carpeta_ciudadana` (que sigue siendo traza JSONB no persistente, RF-B2-021); esta tabla es el registro de consentimientos, distinto de la traza de consulta.

## a.7 🟡 `variable_caracterizacion` (C142) + `grupo_interes_caracterizacion` (C143) — [INFERIDO] (Guía DAFP v5 2022)

**Propósito:** normalizar la caracterización DAFP de `grupo_interes` (R3 §b.7 dejó `caracterizacion TEXT` plano). La Guía de Función Pública v5 define un conjunto cerrado de variables por dimensión → catálogo + puente valor. `[INFERIDO]`: la guía da las variables, no obliga a este modelado; es normalización de calidad.

**Catálogo `variable_caracterizacion`:**

| columna | tipo | NULL | PK | UNIQUE | CHECK | traza |
|---|---|---|---|---|---|---|
| id_variable | BIGINT | NO | **PK** | — | — | clase A |
| dimension | VARCHAR(20) | NO | — | — | CHECK ∈ (geografica, demografica, intrinseca, comportamiento, relacional, organizacional) | DAFP v5 dimensiones |
| nombre_variable | VARCHAR(120) | NO | — | UNIQUE(dimension, nombre_variable) | — | DAFP v5 (ej. ubicación, edad, escolaridad, canal preferido) |
| aplica_a | VARCHAR(10) | NO | — | — | CHECK ∈ (natural, juridica, ambos) | DAFP v5 (persona natural / jurídica) |

**Puente valor `grupo_interes_caracterizacion`:**

| columna | tipo | NULL | PK | FK | traza |
|---|---|---|---|---|---|
| id_grupo_interes | BIGINT | NO | **PK** | →grupo_interes(id) ON DELETE CASCADE ON UPDATE RESTRICT | `grupo_interes` clase A BIGINT |
| id_variable | BIGINT | NO | **PK** | →variable_caracterizacion(id_variable) ON DELETE RESTRICT ON UPDATE CASCADE | catálogo DAFP |
| valor | TEXT | SÍ | — | — | valor de la variable para el grupo |

> **FD:** `{id_grupo_interes, id_variable} → valor` (BCNF). **Lossless** (Heath): `grupo_interes_caracterizacion ∩ grupo_interes = {id_grupo_interes}` = clave de `grupo_interes` ⇒ sin pérdida. `grupo_interes.caracterizacion TEXT` (R3 b.7) se **conserva como resumen narrativo** (no redundante con las variables estructuradas); `tiene_caracterizacion_formal` sigue como flag. **Condicionada:** activar solo si se decide capturar la caracterización estructurada; si no, `grupo_interes.caracterizacion` plano basta.

## Conteo R3.1
- **Tablas nuevas:** 3 (`carpeta_autorizacion_acceso` norm-backed; `variable_caracterizacion` + `grupo_interes_caracterizacion` [INFERIDO]). Total esquema ~149.
- **FK nuevas:** 3 (autorización→ciudadano; puente→grupo_interes; puente→variable_caracterizacion).
- **UNIQUE parciales:** 1 (autorización activa única por titular/entidad/conjunto).

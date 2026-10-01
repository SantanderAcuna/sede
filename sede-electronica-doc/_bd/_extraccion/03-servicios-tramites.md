# Extracción BD — Módulo 03 Servicios y Trámites

## 0. Cobertura

- Archivo fuente: `/var/www/proyect-doc/elicitacion/sede-electronica/03-servicios-tramites/servicios-tramites.md`
- Líneas leídas: 1–184
- Íntegra: SÍ; 0 líneas omitidas
- Secciones cubiertas: §1 Descripción, §2 RF (2.1–2.5 + 2.D Delta), §3 RNF (+ 3.D Delta), §4 RN (+ 4.D Delta), §5 UC, §6 HU (+ 6.D Delta), §7 Datos/Entidades, §8 Integraciones, §9 Ambigüedades

---

## 1. Entidades candidatas

| # | Nombre canónico | Descripción | CANDIDATA-COMPARTIDA | Fuente |
|---|-----------------|-------------|----------------------|--------|
| 1 | **tramite** | Ficha del trámite/OPA/Consulta en el catálogo SUIT; describe modalidad, costo, plazos, requisitos, resultado esperado. | CANDIDATA-COMPARTIDA | §7 línea 164; RF-B1-021 línea 20; RF-B2-029 línea 29 |
| 2 | **solicitud** | Instancia de un trámite iniciado por un ciudadano; contiene radicado, datos de formulario, estado, adjuntos y pago. Alias: "Radicado". | CANDIDATA-COMPARTIDA | §7 línea 165; RF-B2-030 línea 30; UC-B1-014 línea 129 |
| 3 | **expediente_electronico** | Conjunto documental asociado a una solicitud: documentos foliados, índice firmado digitalmente, TRD, metadatos de autenticidad/integridad. | CANDIDATA-COMPARTIDA | §7 línea 168; RF-B1-092 línea 44 |
| 4 | **documento_adjunto** | Archivo adjuntado por el ciudadano o por la entidad durante el flujo del trámite; tiene MIME real, tamaño, estado de validación antivirus. | CANDIDATA-COMPARTIDA | §7 línea 165; RF-B2-030 línea 30; RF-03-D04 línea 73; RN-03-D04 línea 115 |
| 5 | **pago** | Registro de una transacción de pago electrónico (PSE/tarjeta) asociada a una solicitud; incluye estados y clave de idempotencia. | CANDIDATA-COMPARTIDA | §7 línea 165; RF-B1-029 línea 53; UC-B3-006 línea 128; RN-03-D01 línea 112 |
| 6 | **resultado_tramite** | Acto administrativo o documento de facultación que cierra un trámite; se publica en la Carpeta Ciudadana. | — | §7 línea 166; RF-B2-032 línea 32; HU-B1-025 línea 139 |
| 7 | **retroalimentacion** | Valoración del ciudadano sobre la experiencia del trámite (FÁCIL/DIFÍCIL + comentario + encuesta 1–5 estrellas). | — | §7 línea 167; RF-B2-033/034 línea 34; RF-B1-094 línea 35; RF-B3-065 línea 33 |
| 8 | **encuesta_experiencia** | Encuesta estructurada de ≤3 preguntas + calificación 1–5 estrellas al finalizar el trámite; resultado analizable mensualmente. | — | RF-B1-094 línea 35 |
| 9 | **borrador_solicitud** | Estado persistido parcialmente de una solicitud en curso (multi-paso); permite reanudación sin perder adjuntos. | — | RF-03-D03 línea 72; RNF-03-D02 línea 91; HU-03-D04 línea 151 |
| 10 | **requerimiento_subsanacion** | Notificación formal de la entidad al ciudadano pidiendo documentación adicional; tiene plazo y detalle de lo requerido. | — | RF-03-D05 línea 74; RN-03-D05 línea 116; HU-03-D07 línea 154 |
| 11 | **falla_interoperabilidad** | Registro de fallo técnico en consulta automática SCD/X-Road (ANI/RUNT/RUAF); habilita carga manual temporal. | — | RF-03-D06 línea 75; RN-03-D06 línea 117; HU-03-D10 línea 157 |
| 12 | **ciudadano** | Persona natural o jurídica que realiza trámites; identificada mediante SCD (OIDC) por nivel de confianza. | CANDIDATA-COMPARTIDA | UC-B1-004 línea 126; HU-B2-001 línea 137 |
| 13 | **funcionario** | Empleado de la entidad que gestiona la Etapa 3 (procesamiento), emite requerimientos, marca SAP o resuelve. | CANDIDATA-COMPARTIDA | HU-03-D07 línea 154; HU-03-D09 línea 156 |
| 14 | **etapa_tramite** | Valor de dominio con las 4 etapas GOV.CO: Inicio, Solicitud, Procesamiento, Respuesta; indicador visual por estado. | — | RF-B2-028 línea 28; §1 línea 12 |
| 15 | **documento_resultado** | Subentidad del resultado: certificados, constancias, paz y salvos emitidos por la Alcaldía; consultables gratuitamente. | — | RF-B3-124 línea 45; §7 línea 166 |
| 16 | **sesion_autenticacion** | Registro de sesión SCD por nivel de confianza (Bajo/Medio/Alto/Muy Alto); vincula al ciudadano con el trámite iniciado. [INFERIDO: se necesita para auditar accesos y nivel requerido vs. nivel presentado] | CANDIDATA-COMPARTIDA | RF-B1-025/026 línea 61; HU-03-D11 línea 158 |
| 17 | **bloque_digitalizacion** | Agrupación de trámites por nivel de demanda para priorización: bloque 1 (30% mayor demanda), bloque 2 (30%), bloque 3 (40%). | — | RF-B3-150 línea 43; RN-B1-008 línea 99 |
| 18 | **fase_digitalizacion** | Etapa del proceso interno de digitalización de un trámite: Documentación, Autodiagnóstico, Diseño, Implementación/Pruebas, Operación. | — | RF-B3-149 línea 42 |
| 19 | **clave_idempotencia** | Token único por intento de pago/radicado; garantiza que reintentos y doble clic no generen duplicados. [INFERIDO: puede ser atributo de `pago` y de `solicitud` en lugar de entidad separada, pero se lista por su impacto transaccional explícito] | — | RF-03-D01 línea 70; RN-03-D01 línea 112; HU-03-D01 línea 148 |
| 20 | **log_acceso_radicado** | Registro de auditoría de consultas de estado por radicado; captura intentos de acceso no autorizado (error 403). | — | HU-B2-011 línea 140 |

---

## 2. Atributos/campos por entidad

### 2.1 tramite

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_tramite | INTEGER | SÍ | Surrogate PK | PK | §7 línea 164 |
| codigo_suit | VARCHAR(20) | SÍ | Formato `T{código}` ej. T1234; UNIQUE | — | §7 línea 164; RF-B1-024 línea 41 |
| nombre | VARCHAR(300) | SÍ | — | — | §7 línea 164; RF-B1-021 línea 20 |
| tipo_servicio | VARCHAR(30) | SÍ | `TRÁMITE` / `OPA` / `CONSULTA` | — | §7 línea 164 |
| modalidad | VARCHAR(30) | SÍ | `TOTALMENTE_EN_LÍNEA` / `PARCIAL` / `PRESENCIAL` | — | RF-B1-021 línea 20; RF-B2-029 línea 29 |
| descripcion | TEXT | SÍ | Lenguaje claro; requisito SUIT | — | RF-B2-029 línea 29 |
| requisitos | TEXT | SÍ | Lista de documentos/requisitos en lenguaje claro | — | RF-B2-029 línea 29; §7 línea 164 |
| pasos_procedimiento | TEXT | SÍ | Descripción del procedimiento paso a paso | — | RF-B2-029 línea 29; §7 línea 164 |
| costo | DECIMAL(18,2) | SÍ | ≥0; 0 si gratuito | — | RF-B1-021 línea 20; §7 línea 164 |
| es_gratuito | BOOLEAN | SÍ | `costo = 0 → TRUE`; CHECK consistente | — | RF-B1-021 línea 20 |
| tiempo_resolucion_dias | INTEGER | SÍ | Días hábiles; >0 | — | §7 línea 164; RF-B2-029 línea 29 |
| resultado_esperado | TEXT | SÍ | Descripción del output del trámite | — | §7 línea 164; RF-B2-029 línea 29 |
| grupo_objetivo | VARCHAR(200) | NO | Perfil del solicitante | — | §7 línea 164 |
| nivel_transformacion | SMALLINT | SÍ | CHECK (1–6); nivel de digitalización DAFP | — | §7 línea 164 |
| url_digital | VARCHAR(500) | NO | URL del trámite en línea; formato `https://www.gov.co/servicios-y-tramites/T{código}` | — | RF-B1-021 línea 20; RF-B2-098 línea 62 |
| georreferenciacion | JSONB | NO | Puntos de atención (lat, lon, sede, horario) | — | RF-B2-029 línea 29; §7 línea 164 |
| estado_estandarizado | VARCHAR(30) | SÍ | `ACTIVO` / `EN_DIGITALIZACIÓN` / `SUSPENDIDO` / `SUPRIMIDO` | — | §7 línea 164; RN-B2-002 línea 102 |
| nivel_autenticacion_requerido | VARCHAR(20) | SÍ | `BAJO` / `MEDIO` / `ALTO` / `MUY_ALTO`; mínimo SCD | — | RF-B1-025/026 línea 61; HU-03-D11 línea 158 |
| aplica_sap | BOOLEAN | SÍ | Silencio administrativo positivo; DEFAULT FALSE | — | RN-03-D08 línea 119; HU-03-D09 línea 156 |
| termino_sap_dias | INTEGER | NO | NOT NULL si aplica_sap = TRUE; días hábiles | — | RN-03-D08 línea 119 |
| id_bloque_digitalizacion | INTEGER | NO | FK → bloque_digitalizacion.id_bloque | FK | RF-B3-150 línea 43 |
| id_fase_digitalizacion_actual | INTEGER | NO | FK → fase_digitalizacion.id_fase | FK | RF-B3-149 línea 42 |
| solicitudes_por_anio | INTEGER | NO | Para cálculo de priorización; actualizable desde SUIT | — | RF-B3-150 línea 43 |
| fecha_ultima_actualizacion_suit | DATE | SÍ | Debe actualizarse ≤3 días hábiles tras acto administrativo | — | RN-B1-006 línea 97 |
| requiere_concepto_dafp | BOOLEAN | SÍ | DEFAULT FALSE; modificaciones estructurales requieren concepto | — | RN-B2-003 línea 103 |
| fecha_creacion | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_modificacion | TIMESTAMP | SÍ | ON UPDATE NOW() | — | [INFERIDO] |

### 2.2 solicitud

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_solicitud | INTEGER | SÍ | Surrogate PK | PK | §7 línea 165 |
| numero_radicado | VARCHAR(50) | SÍ | UNIQUE; generado atómicamente (secuencia); formato propio de la Alcaldía | — | RF-B2-030 línea 30; HU-03-D13 línea 160 |
| id_tramite | INTEGER | SÍ | FK → tramite.id_tramite | FK | §7 línea 165 |
| id_ciudadano | INTEGER | SÍ | FK → ciudadano.id_ciudadano | FK | UC-B1-004 línea 126 |
| id_sesion_autenticacion | INTEGER | NO | FK → sesion_autenticacion.id_sesion; nivel con que inició | FK | RF-B1-025 línea 61 |
| fecha_hora_radicacion | TIMESTAMP | SÍ | Generado al radicar; NOT NULL | — | §7 línea 165 |
| estado | VARCHAR(40) | SÍ | Dominio de máquina de estados (ver §3 reglas) | — | §7 línea 165; UC-B1-014 línea 129 |
| etapa_actual | SMALLINT | SÍ | CHECK (1–4); etapa GOV.CO actual | — | RF-B2-028 línea 28 |
| tiempo_estimado_resolucion | INTEGER | NO | Días hábiles estimados; derivado del trámite | — | §7 línea 165; RF-B2-031 línea 31 |
| fecha_vencimiento | DATE | NO | Calculado: fecha_radicacion + tiempo_estimado; para SAP y subsanación | — | RN-03-D08 línea 119 |
| datos_formulario | JSONB | SÍ | Campos del formulario electrónico específico del trámite | — | RF-B2-030 línea 30; §7 línea 165 |
| autorizo_datos_personales | BOOLEAN | SÍ | Ley 1581; NOT NULL; debe ser TRUE para radicar | — | RF-B2-030 línea 30 |
| acepto_terminos | BOOLEAN | SÍ | T&C; NOT NULL; debe ser TRUE para radicar | — | RF-B2-030 línea 30 |
| clave_idempotencia | VARCHAR(100) | SÍ | UNIQUE; token por intento de radicación; evita duplicados | — | RF-03-D01 línea 70; RN-03-D01 línea 112 |
| canal_ingreso | VARCHAR(30) | SÍ | `EN_LÍNEA` / `PRESENCIAL_ASISTIDO` | — | RF-B1-095 línea 47 |
| requiere_pago | BOOLEAN | SÍ | Derivado de tramite.costo > 0 | — | RF-B1-029 línea 53 |
| fecha_desistimiento | TIMESTAMP | NO | NOT NULL solo si estado = `DESISTIDO` | — | RN-03-D07 línea 118; RF-03-D07 línea 76 |
| motivo_desistimiento | TEXT | NO | Texto libre del ciudadano al desistir | — | RF-03-D07 línea 76 |
| fecha_resolucion | TIMESTAMP | NO | Cuándo se cerró el trámite con resultado | — | RF-B2-032 línea 32 |
| efecto_sap | BOOLEAN | NO | TRUE si el cierre fue por silencio administrativo positivo | — | RN-03-D08 línea 119 |
| fecha_sap_aplicado | TIMESTAMP | NO | NOT NULL si efecto_sap = TRUE | — | RN-03-D08 línea 119 |
| es_borrador | BOOLEAN | SÍ | TRUE mientras no esté radicado formalmente | — | RF-03-D03 línea 72 |
| paso_actual_borrador | SMALLINT | NO | Paso del formulario multi-paso donde quedó; NOT NULL si es_borrador = TRUE | — | RF-03-D03 línea 72; HU-03-D04 línea 151 |
| fecha_expiracion_borrador | TIMESTAMP | NO | NOT NULL si es_borrador = TRUE; [PREGUNTA ABIERTA] plazo | — | RNF-03-D02 línea 91 |
| fecha_creacion | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_modificacion | TIMESTAMP | SÍ | ON UPDATE NOW() | — | [INFERIDO] |

Máquina de estados de `solicitud.estado`:
`BORRADOR` → `RADICADO` → `RECIBIDO` → `EN_TRÁMITE` → `REQUIERE_SUBSANACIÓN` → `SUBSANADO` → `EN_ESPERA_PAGO` → `RESUELTO` / `DESISTIDO` / `ARCHIVADO`
- `RECHAZADO_PAGO`: transitorio si pago rechazado (no consume radicado ni borra borrador)
- Fuente: UC-B1-014 línea 129; RN-03-D02 línea 113; RF-03-D07 línea 76; HU-03-D06 línea 153

### 2.3 expediente_electronico

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_expediente | INTEGER | SÍ | Surrogate PK | PK | §7 línea 168 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud; UNIQUE (1:1) | FK | RF-B1-092 línea 44 |
| numero_folio_inicio | INTEGER | SÍ | Primera foja del expediente | — | §7 línea 168 |
| numero_folio_fin | INTEGER | NO | Última foja al cierre | — | §7 línea 168 |
| indice_firmado | TEXT | SÍ | Índice electrónico con firma digital; base64 o referencia a archivo | — | §7 línea 168 |
| trd_codigo | VARCHAR(50) | SÍ | Código de Tabla de Retención Documental aplicable | — | §7 línea 168 |
| metadatos_autenticidad | JSONB | SÍ | Metadatos de autenticidad, integridad, fiabilidad, disponibilidad (AIFID) | — | RF-B1-092 línea 44 |
| estado_integridad | VARCHAR(20) | SÍ | `ÍNTEGRO` / `COMPROMETIDO` | — | RF-B1-092 línea 44 |
| sgdea_referencia | VARCHAR(200) | SÍ | Identificador externo en el SGDEA | — | RF-B1-092 línea 44; §8 línea 174 |
| fecha_creacion | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_cierre | TIMESTAMP | NO | Al resolver el trámite | — | [INFERIDO] |

### 2.4 documento_adjunto

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_documento | INTEGER | SÍ | Surrogate PK | PK | §7 líneas 165,168 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud | FK | §7 línea 165 |
| id_expediente | INTEGER | NO | FK → expediente_electronico.id_expediente; NULL hasta formalización | FK | §7 línea 168 |
| nombre_original | VARCHAR(500) | SÍ | Nombre de archivo tal como lo sube el ciudadano | — | RF-03-D04 línea 73 |
| mime_type_declarado | VARCHAR(100) | SÍ | MIME declarado en el upload | — | RF-03-D04 línea 73; RN-03-D04 línea 115 |
| mime_type_real | VARCHAR(100) | SÍ | MIME detectado por magic bytes | — | RF-03-D04 línea 73; RN-03-D04 línea 115 |
| tamano_bytes | BIGINT | SÍ | >0 | — | RF-03-D04 línea 73 |
| hash_sha256 | CHAR(64) | SÍ | Integridad del archivo | — | RF-B1-092 línea 44; [INFERIDO para adjuntos] |
| ruta_almacenamiento | VARCHAR(1000) | SÍ | Ruta interna o URI en almacenamiento seguro | — | [INFERIDO] |
| estado_antivirus | VARCHAR(20) | SÍ | `PENDIENTE` / `LIMPIO` / `INFECTADO` | — | RF-03-D04 línea 73; RN-03-D04 línea 115 |
| es_valido | BOOLEAN | SÍ | FALSE si MIME falso o infectado | — | RF-03-D04 línea 73 |
| tipo_origen | VARCHAR(20) | SÍ | `CIUDADANO` / `ENTIDAD` / `SUBSANACION` | — | RF-03-D05 línea 74 |
| es_carga_manual_excepcion | BOOLEAN | SÍ | DEFAULT FALSE; TRUE si es fallback por falla de interoperabilidad | — | RN-03-D06 línea 117; RF-03-D06 línea 75 |
| id_falla_interop | INTEGER | NO | FK → falla_interoperabilidad.id_falla; NOT NULL si es_carga_manual_excepcion=TRUE | FK | RF-03-D06 línea 75 |
| numero_folio | INTEGER | NO | Número dentro del expediente | — | §7 línea 168 |
| fecha_carga | TIMESTAMP | SÍ | — | — | [INFERIDO] |

### 2.5 pago

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_pago | INTEGER | SÍ | Surrogate PK | PK | RF-B1-029 línea 53 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud | FK | §7 línea 165 |
| clave_idempotencia_pago | VARCHAR(100) | SÍ | UNIQUE; token de idempotencia propio del pago | — | RF-03-D01 línea 70; RN-03-D01 línea 112 |
| monto | DECIMAL(18,2) | SÍ | Igual al costo del trámite; sin recargo de pasarela | — | RF-B1-029 línea 53; RN-03-D03 línea 114 |
| medio_pago | VARCHAR(20) | SÍ | `PSE` / `TARJETA_DEBITO` / `TARJETA_CREDITO` | — | RF-B1-029 línea 53; UC-B3-006 línea 128 |
| estado | VARCHAR(30) | SÍ | `PENDIENTE` / `APROBADO` / `RECHAZADO` / `FALLIDO` | — | RF-03-D02 línea 71; RN-03-D02 línea 113 |
| referencia_pasarela | VARCHAR(200) | NO | ID de transacción devuelto por la pasarela | — | UC-B3-006 línea 128 |
| comprobante_url | VARCHAR(500) | NO | URL del comprobante de pago | — | UC-B3-006 línea 128; HU-B1-022 línea 138 |
| fecha_inicio | TIMESTAMP | SÍ | Cuando se inicia el pago | — | RF-B1-029 línea 53 |
| fecha_confirmacion | TIMESTAMP | NO | NOT NULL solo si estado = `APROBADO` | — | RN-03-D02 línea 113 |
| intentos_reconsulta | SMALLINT | SÍ | DEFAULT 0; número de reconsultas automáticas realizadas | — | RF-03-D02 línea 71 |
| fecha_ultima_reconsulta | TIMESTAMP | NO | Última vez que se reconsultó estado a la pasarela | — | RF-03-D02 línea 71 |

### 2.6 resultado_tramite

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_resultado | INTEGER | SÍ | Surrogate PK | PK | §7 línea 166 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud; UNIQUE (1:1) | FK | §7 línea 166 |
| tipo_acto | VARCHAR(50) | SÍ | `ACTO_ADMINISTRATIVO` / `CERTIFICADO` / `CONSTANCIA` / `PAZ_Y_SALVO` / `LICENCIA` / `OTRO` | — | §7 línea 166; RF-B3-124 línea 45 |
| resumen | TEXT | SÍ | — | — | §7 línea 166 |
| url_carpeta_ciudadana | VARCHAR(500) | NO | URL en la Carpeta Ciudadana Digital (CCD) de GOV.CO | — | §7 línea 166; RF-B1-027 línea 60 |
| estado_publicacion_ccd | VARCHAR(20) | SÍ | `PENDIENTE` / `PUBLICADO` / `EN_COLA` / `FALLIDO` | — | HU-B1-025 línea 139 |
| fecha_publicacion_ccd | TIMESTAMP | NO | NOT NULL si estado_publicacion_ccd = `PUBLICADO`; ≤24 h desde resolución | — | HU-B1-025 línea 139 |
| id_documento_resultado | INTEGER | NO | FK → documento_adjunto.id_documento; archivo del resultado | FK | §7 línea 166 |
| fecha_emision | TIMESTAMP | SÍ | — | — | [INFERIDO] |

### 2.7 retroalimentacion

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_retroalimentacion | INTEGER | SÍ | Surrogate PK | PK | §7 línea 167 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud | FK | §7 línea 167 |
| momento | VARCHAR(20) | SÍ | `INICIO` / `FINAL`; obligatorio en ambos momentos | — | RF-B2-033/034 línea 34 |
| valoracion_rapida | VARCHAR(10) | NO | `FÁCIL` / `DIFÍCIL` | — | RF-B2-033/034 línea 34; RF-B3-065 línea 33 |
| comentario | TEXT | NO | Texto libre del ciudadano | — | §7 línea 167 |
| fecha_registro | TIMESTAMP | SÍ | — | — | [INFERIDO] |

### 2.8 encuesta_experiencia

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_encuesta | INTEGER | SÍ | Surrogate PK | PK | RF-B1-094 línea 35 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud; UNIQUE (una encuesta por trámite completado) | FK | RF-B1-094 línea 35 |
| calificacion_estrellas | SMALLINT | SÍ | CHECK (1–5) | — | RF-B1-094 línea 35 |
| respuesta_pregunta_1 | TEXT | NO | Máx 3 preguntas definidas por el administrador | — | RF-B1-094 línea 35 |
| respuesta_pregunta_2 | TEXT | NO | — | — | RF-B1-094 línea 35 |
| respuesta_pregunta_3 | TEXT | NO | — | — | RF-B1-094 línea 35 |
| fecha_respuesta | TIMESTAMP | SÍ | — | — | [INFERIDO] |

> [INFERIDO] Las preguntas de la encuesta son configurables (el documento dice "máximo 3 preguntas" sin definir su texto); se necesita tabla `pregunta_encuesta` para no hardcodear. Se deja como vacío pendiente de aclaración con el líder funcional.

### 2.9 borrador_solicitud

> [INFERIDO] Esta entidad podría modelarse como campos de `solicitud` (con `es_borrador = TRUE`) en lugar de tabla separada. Se lista por separado porque el documento diferencia claramente borradores de radicados, y los borradores tienen adjuntos propios y fecha de expiración.

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_borrador | INTEGER | SÍ | Surrogate PK | PK | RF-03-D03 línea 72 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud; UNIQUE (1:1 mientras es_borrador=TRUE) | FK | RF-03-D03 línea 72 |
| paso_actual | SMALLINT | SÍ | Paso del formulario donde pausó; CHECK (≥1) | — | HU-03-D04 línea 151 |
| datos_parciales | JSONB | SÍ | Datos diligenciados hasta el momento | — | RF-03-D03 línea 72 |
| fecha_creacion | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_ultima_modificacion | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_expiracion | TIMESTAMP | SÍ | [PREGUNTA ABIERTA] plazo de retención — definir con Protección de Datos | — | RNF-03-D02 línea 91 |
| version_ficha_tramite | INTEGER | SÍ | Versión de la ficha del trámite al guardar; para detectar cambios al reanudar | — | HU-03-D04 línea 151 |

### 2.10 requerimiento_subsanacion

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_requerimiento | INTEGER | SÍ | Surrogate PK | PK | RF-03-D05 línea 74 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud | FK | RF-03-D05 línea 74 |
| id_funcionario | INTEGER | SÍ | FK → funcionario.id_funcionario; quien emite el requerimiento | FK | HU-03-D07 línea 154 |
| detalle_requerido | TEXT | SÍ | NOT NULL; descripción obligatoria de lo que falta | — | HU-03-D07 línea 154 |
| plazo_respuesta_dias | INTEGER | SÍ | Días hábiles para que el ciudadano responda | — | RF-03-D05 línea 74; RN-03-D05 línea 116 |
| fecha_emision | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_limite | DATE | SÍ | Calculada: fecha_emision + plazo_respuesta_dias hábiles | — | RN-03-D05 línea 116 |
| fecha_respuesta_ciudadano | TIMESTAMP | NO | NOT NULL si estado = `RESPONDIDO` | — | HU-03-D06 línea 153 |
| estado | VARCHAR(20) | SÍ | `EMITIDO` / `RESPONDIDO` / `VENCIDO` | — | HU-03-D06 línea 153 |
| nueva_fecha_vencimiento_tramite | DATE | NO | Recalculada desde entrega del documento subsanado | — | RN-03-D05 línea 116 |

### 2.11 falla_interoperabilidad

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_falla | INTEGER | SÍ | Surrogate PK | PK | RF-03-D06 línea 75 |
| id_solicitud | INTEGER | SÍ | FK → solicitud.id_solicitud | FK | RF-03-D06 línea 75 |
| servicio_externo | VARCHAR(50) | SÍ | `ANI` / `RUNT` / `RUAF` / `REGISTRADURÍA` / `OTRO` | — | RF-03-D06 línea 75; RN-03-D06 línea 117 |
| descripcion_falla | TEXT | SÍ | Mensaje de error o descripción técnica | — | RN-03-D06 línea 117 |
| intentos_reintento | SMALLINT | SÍ | Número de reintentos antes de activar fallback | — | RF-03-D06 línea 75 |
| fallback_manual_habilitado | BOOLEAN | SÍ | TRUE si se habilitó carga manual temporal | — | RN-03-D06 línea 117 |
| fecha_falla | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_resolucion_falla | TIMESTAMP | NO | Cuando el servicio externo respondió correctamente | — | [INFERIDO] |

### 2.12 bloque_digitalizacion

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_bloque | INTEGER | SÍ | Surrogate PK | PK | RF-B3-150 línea 43 |
| numero_bloque | SMALLINT | SÍ | UNIQUE; CHECK (1–3) | — | RF-B3-150 línea 43 |
| descripcion | VARCHAR(100) | SÍ | Ej: "30% mayor demanda" | — | RF-B3-150 línea 43 |
| porcentaje_demanda | DECIMAL(5,2) | SÍ | 30.00, 30.00, 40.00 | — | RF-B3-150 línea 43 |
| fecha_limite_meta | DATE | SÍ | may/2028 bloque 1; 100% mar/2034 | — | RN-B1-008 línea 99 |

### 2.13 fase_digitalizacion

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_fase | INTEGER | SÍ | Surrogate PK | PK | RF-B3-149 línea 42 |
| nombre | VARCHAR(50) | SÍ | `DOCUMENTACIÓN` / `AUTODIAGNÓSTICO` / `DISEÑO` / `IMPLEMENTACIÓN_PRUEBAS` / `OPERACIÓN` | — | RF-B3-149 línea 42 |
| orden | SMALLINT | SÍ | UNIQUE; 1–5 | — | RF-B3-149 línea 42 |

### 2.14 ciudadano (referencia — CANDIDATA-COMPARTIDA)

> Definición completa pertenece al módulo de autenticación (módulo 09). Aquí solo los campos que este módulo necesita como FK.

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_ciudadano | INTEGER | SÍ | Surrogate PK (definido en módulo 09) | PK | UC-B1-004 línea 126 |
| identificador_scd | VARCHAR(100) | SÍ | Sub del token OIDC del SCD; UNIQUE | — | RF-B1-025 línea 61 |
| nivel_confianza_actual | VARCHAR(20) | SÍ | `BAJO` / `MEDIO` / `ALTO` / `MUY_ALTO` | — | RF-B1-025 línea 61; HU-03-D11 línea 158 |

### 2.15 funcionario (referencia — CANDIDATA-COMPARTIDA)

> Definición completa pertenece a módulo de gestión de usuarios. Referencia mínima aquí.

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_funcionario | INTEGER | SÍ | Surrogate PK (módulo de usuarios) | PK | HU-03-D07 línea 154 |
| dependencia | VARCHAR(100) | SÍ | Dependencia de la Alcaldía que gestiona el trámite | — | HU-03-D07 línea 154 |

### 2.16 sesion_autenticacion (referencia — CANDIDATA-COMPARTIDA)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_sesion | INTEGER | SÍ | Surrogate PK (módulo 09) | PK | RF-B1-025 línea 61 |
| id_ciudadano | INTEGER | SÍ | FK → ciudadano.id_ciudadano | FK | RF-B1-025 línea 61 |
| nivel_confianza | VARCHAR(20) | SÍ | Nivel con que se autenticó en esta sesión | — | RF-B1-025 línea 61 |
| fecha_inicio_sesion | TIMESTAMP | SÍ | — | — | [INFERIDO] |
| fecha_expiracion | TIMESTAMP | SÍ | 900 s de inactividad (HU-B2-001) | — | HU-B2-001 línea 137 |

### 2.17 log_acceso_radicado

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_log | BIGINT | SÍ | Surrogate PK | PK | HU-B2-011 línea 140 |
| id_radicado_consultado | INTEGER | SÍ | FK → solicitud.id_solicitud | FK | HU-B2-011 línea 140 |
| id_ciudadano_consultante | INTEGER | NO | FK → ciudadano.id_ciudadano; NULL si anónimo | FK | HU-B2-011 línea 140 |
| resultado | VARCHAR(10) | SÍ | `200` / `403` / `404` | — | HU-B2-011 línea 140 |
| ip_origen | INET | SÍ | IP del solicitante | — | [INFERIDO] |
| fecha_hora | TIMESTAMP | SÍ | — | — | HU-B2-011 línea 140 |

---

## 3. Reglas de negocio con impacto en datos

| ID RN | Impacto en datos | Implementación sugerida | Fuente |
|-------|-----------------|------------------------|--------|
| RN-B1-006 | `tramite.fecha_ultima_actualizacion_suit` debe ser ≤ fecha del acto administrativo + 3 días hábiles | CHECK diferido o trigger; alerta de incumplimiento | línea 97 |
| RN-B1-007/020/038 | `tramite.nivel_transformacion` = máximo para trámites nuevos; no insertar `solicitud` con cobro adicional por canal | CHECK en insert de tramite: si es nuevo → nivel ≥ umbral digital | línea 98 |
| RN-B1-008/033/034 | `bloque_digitalizacion.fecha_limite_meta` define SLA; `tramite.id_bloque_digitalizacion` determina plazo | Trigger o cron que alerte cuando tramite en bloque 1 no alcanzó `nivel_transformacion` umbral antes de fecha_limite | línea 99 |
| RN-B2-001/010/RN-03-D03 | `pago.monto` = `tramite.costo`; sin recargo → CHECK `pago.monto = solicitud.tramite.costo` | CHECK en insert de pago; rechazo si monto > costo original | línea 101, 114 |
| RN-B2-002/027 | `tramite.tipo_servicio = 'CONSULTA'` no debe existir en SUIT; si se detecta, `tramite.estado_estandarizado = 'SUPRIMIDO'` en ≤3 meses | Constraint de negocio; auditado periódicamente | línea 102 |
| RN-B2-003 | Modificaciones con `tramite.requiere_concepto_dafp = TRUE` no pueden publicarse sin concepto externo | Columna `concepto_dafp_otorgado BOOLEAN`; trigger que bloquea cambio de `estado_estandarizado → ACTIVO` si requiere_concepto_dafp=TRUE y concepto no otorgado | línea 103 |
| RN-03-D01 | `solicitud.clave_idempotencia` y `pago.clave_idempotencia_pago` son UNIQUE; reintentos devuelven el registro existente | UNIQUE constraint + lógica de upsert idempotente en capa de aplicación | línea 112 |
| RN-03-D02 | `solicitud.estado` avanza de `EN_ESPERA_PAGO` solo cuando `pago.estado = 'APROBADO'`; `RECHAZADO` no consume radicado | Trigger en `pago` que actualiza `solicitud.estado` condicionalmente | línea 113 |
| RN-03-D04 | `documento_adjunto.es_valido = FALSE` si `mime_type_real ≠ mime_type_declarado` o `estado_antivirus = 'INFECTADO'`; solicitud con documentos inválidos no puede radicar | CHECK en `solicitud.estado → RADICADO`: todos sus documentos deben tener `es_valido = TRUE` | línea 115 |
| RN-03-D05 | Al emitir `requerimiento_subsanacion`, `solicitud.estado → REQUIERE_SUBSANACIÓN`; al responder, `solicitud.fecha_vencimiento` se recalcula desde `requerimiento_subsanacion.fecha_respuesta_ciudadano` | Trigger en insert de `requerimiento_subsanacion` y en update de `requerimiento_subsanacion.estado → RESPONDIDO` | línea 116 |
| RN-03-D06 | `documento_adjunto.es_carga_manual_excepcion = TRUE` solo admisible si existe `falla_interoperabilidad` asociada con `fallback_manual_habilitado = TRUE` | CHECK o trigger que valida FK `id_falla_interop NOT NULL` cuando `es_carga_manual_excepcion = TRUE` | línea 117 |
| RN-03-D07 | `solicitud.estado → DESISTIDO` solo si estado anterior no es `RESUELTO`; `fecha_desistimiento NOT NULL` al desistir | CHECK: `estado = 'DESISTIDO' → fecha_desistimiento IS NOT NULL`; trigger que bloquea transición desde `RESUELTO` | línea 118 |
| RN-03-D08 | `solicitud.efecto_sap = TRUE` y `solicitud.fecha_sap_aplicado NOT NULL` cuando `tramite.aplica_sap = TRUE` y `solicitud.fecha_vencimiento < NOW()` y `solicitud.estado` no es terminal | Job/cron que evalúa vencimientos; trigger de alerta a `funcionario` y Secretaría Jurídica | línea 119 |
| RN-B3-011/037 | Documentos de `tipo_origen = 'ENTIDAD'` que pueden verificarse vía interoperabilidad no se almacenan físicamente como adjunto del ciudadano; solo se registra `falla_interoperabilidad` si falla | Lógica de negocio: si el servicio externo responde OK, no se crea `documento_adjunto` con `tipo_origen='CIUDADANO'` para ese certificado | línea 104 |
| HU-03-D13/RNF-04-D02 | `solicitud.numero_radicado` generado con secuencia de BD (SEQUENCE) garantiza atomicidad y unicidad ante concurrencia | Usar `SEQUENCE` PostgreSQL con `SELECT nextval(...)` en transacción; nunca `MAX()+1` | línea 160 |

### 3.1 Estados SUIT actualizables

| Campo | Regla | Plazo | Fuente |
|-------|-------|-------|--------|
| `tramite.fecha_ultima_actualizacion_suit` | ≤3 días hábiles tras acto administrativo | RN-B1-006 | línea 97 |
| `tramite.url_digital` al implementar SCD | ≤24 h tras cambio de URL | RF-B2-098 línea 62 | línea 62 |
| Trámites nuevos aprobados ante DAFP | En línea desde el primer día | RN-B1-007 | línea 98 |

### 3.2 Restricciones UNIQUE declaradas

| Tabla | Columna(s) | Fuente |
|-------|-----------|--------|
| tramite | codigo_suit | RF-B1-021 línea 20 |
| solicitud | numero_radicado | RF-B2-030 línea 30; HU-03-D13 |
| solicitud | clave_idempotencia | RN-03-D01 línea 112 |
| pago | clave_idempotencia_pago | RN-03-D01 línea 112 |
| resultado_tramite | id_solicitud | §7 línea 166 |
| expediente_electronico | id_solicitud | RF-B1-092 línea 44 |
| encuesta_experiencia | id_solicitud | RF-B1-094 línea 35 |
| bloque_digitalizacion | numero_bloque | RF-B3-150 línea 43 |
| fase_digitalizacion | orden | RF-B3-149 línea 42 |

---

## 4. Cardinalidades y relaciones

| Relación | Cardinalidad | Obligatoriedad | Fuente |
|----------|-------------|----------------|--------|
| tramite → solicitud | 1 : N | Un trámite tiene 0..N solicitudes; cada solicitud tiene exactamente 1 trámite | §7 líneas 164–165 |
| ciudadano → solicitud | 1 : N | Un ciudadano tiene 0..N solicitudes; cada solicitud pertenece a 1 ciudadano | UC-B1-004 línea 126 |
| solicitud → expediente_electronico | 1 : 1 | Cada solicitud completada genera 1 expediente; cada expediente pertenece a 1 solicitud | RF-B1-092 línea 44 |
| solicitud → documento_adjunto | 1 : N | Una solicitud tiene 0..N documentos adjuntos | §7 línea 165; RF-B2-030 línea 30 |
| expediente_electronico → documento_adjunto | 1 : N | Un expediente contiene 0..N documentos; documento puede existir sin expediente (borrador) | §7 línea 168 |
| solicitud → pago | 1 : 0..1 (si trámite tiene costo) | Una solicitud de trámite con costo tiene 1..N intentos de pago (reintentos), pero solo 1 aprobado | RF-B1-029 línea 53; RN-03-D02 línea 113 |
| solicitud → resultado_tramite | 1 : 0..1 | Una solicitud tiene como máximo 1 resultado | §7 línea 166 |
| solicitud → retroalimentacion | 1 : N (2 momentos) | Una solicitud tiene 0..2 retroalimentaciones (inicio + final); RF-B2-033/034 las declara obligatorias | RF-B2-033/034 línea 34 |
| solicitud → encuesta_experiencia | 1 : 0..1 | Una solicitud completada tiene 0..1 encuesta de experiencia | RF-B1-094 línea 35 |
| solicitud → borrador_solicitud | 1 : 0..1 | Una solicitud borrador tiene 0..1 borrador activo | RF-03-D03 línea 72 |
| solicitud → requerimiento_subsanacion | 1 : N | Una solicitud puede tener 0..N requerimientos de subsanación en diferentes etapas | RF-03-D05 línea 74 |
| solicitud → falla_interoperabilidad | 1 : N | Una solicitud puede tener 0..N registros de falla de interoperabilidad | RF-03-D06 línea 75 |
| solicitud → log_acceso_radicado | 1 : N | Una solicitud puede tener 0..N intentos de consulta de estado | HU-B2-011 línea 140 |
| tramite → bloque_digitalizacion | N : 1 | Cada trámite pertenece a 0..1 bloque de digitalización | RF-B3-150 línea 43 |
| tramite → fase_digitalizacion | N : 1 | Cada trámite tiene una fase actual de digitalización | RF-B3-149 línea 42 |
| funcionario → requerimiento_subsanacion | 1 : N | Un funcionario emite 0..N requerimientos | HU-03-D07 línea 154 |
| sesion_autenticacion → solicitud | 1 : N | Una sesión puede originar N solicitudes | RF-B1-025 línea 61 |
| falla_interoperabilidad → documento_adjunto | 1 : 0..1 | Una falla puede habilitar 1 documento de carga manual | RN-03-D06 línea 117 |
| resultado_tramite → documento_adjunto | 1 : 0..1 | Un resultado puede tener 1 documento asociado | §7 línea 166 |

---

## 5. Jerarquías ISA / Polimorfismo

### 5.1 Jerarquía ISA: tramite (supertipo) → tipo_servicio (subtipos)

**Evidencia en el documento:** §7 línea 164 enumera `Trámite/OPA/Consulta` como variantes del mismo concepto; RF-B1-021 lista los tres tipos con la misma ficha base pero diferente tratamiento normativo: las `CONSULTA` deben suprimirse del SUIT (RN-B2-002/027 línea 102), los `TRÁMITE` tienen flujo de 4 etapas completo, y los `OPA` son servicios sin transacción.

**Subtipos identificados:**
- `tramite_formal`: sigue flujo de 4 etapas, genera expediente, puede tener pago, SAP.
- `opa` (Otro Procedimiento Administrativo): no genera expediente formal de trámite, no tiene las mismas 4 etapas.
- `consulta`: solo informativa; NO es trámite según Decreto 2106 Art.6 (línea 102); debe suprimirse del SUIT.

**Restricción:** disjoint total (cada entidad pertenece a exactamente uno de los tres tipos).

**Estrategia de mapeo recomendada:** tabla única por jerarquía (single-table) con discriminador `tramite.tipo_servicio` y columnas opcionales marcadas `NULL` para los subtipos que no las usan.

**Justificación:** el número de subtipos es bajo (3), los atributos diferenciales son escasos (principalmente `aplica_sap`, `termino_sap_dias` específicos del trámite formal), las consultas sobre el catálogo completo son el caso de uso dominante (RF-B1-021/022), y la estrategia single-table evita joins para el listado/búsqueda del catálogo, que debe responder en ≤2 s (RNF-03-D01 línea 90). Se añade `CHECK` que valida que las columnas de trámite formal sean `NULL` para `tipo_servicio = 'CONSULTA'`.

### 5.2 Jerarquía ISA: documento_adjunto → tipo_origen (subtipos funcionales)

**Evidencia:** campo `tipo_origen` en §2.4 derivado de RF-B2-030 (ciudadano sube), RF-03-D05 (entidad pide subsanación), RF-03-D06 (fallback manual). Tres orígenes con comportamientos distintos:
- `CIUDADANO`: adjuntado en Etapa 2; sujeto a validación MIME y antivirus.
- `ENTIDAD`: generado por la entidad (requerimiento o resultado); distinta política de validación.
- `SUBSANACION`: cargado en respuesta a un `requerimiento_subsanacion`; vinculado obligatoriamente a `id_requerimiento`.

**Estrategia:** single-table con discriminador `tipo_origen`; columna `id_requerimiento INTEGER FK` nullable (NOT NULL solo si `tipo_origen = 'SUBSANACION'`). CHECK que enforce esto.

### 5.3 Polimorfismo: resultado_tramite → tipos de acto

**Evidencia:** §7 línea 166 y RF-B3-124 línea 45 listan `acto administrativo`, `documento de facultación`, `certificado`, `constancia`, `paz y salvo`. Todos son documentos de resultado pero con diferente tratamiento archivístico.

**Estrategia:** columna discriminadora `tipo_acto VARCHAR(50)` en `resultado_tramite` con dominio enumerado. No se modela como jerarquía de tablas separadas porque los atributos son los mismos para todos (no hay atributos específicos por subtipo que justifiquen tablas adicionales).

---

## 6. Inferencias [INFERIDO]

| # | Inferencia | Justificación | Basado en |
|---|-----------|---------------|-----------|
| 1 | `tramite.fecha_creacion` y `fecha_modificacion` | Toda entidad de catálogo necesita trazabilidad temporal; el documento menciona "actualización ≤3 días hábiles" que requiere conocer la última modificación. | RN-B1-006 línea 97 |
| 2 | `documento_adjunto.hash_sha256` | El expediente electrónico exige autenticidad e integridad (RF-B1-092); el hash de cada adjunto es necesario para verificarla. | RF-B1-092 línea 44 |
| 3 | `sesion_autenticacion` como entidad separada de `ciudadano` | La HU-B2-001 menciona sesión de 900 s y reautenticación preservando estado; el nivel de confianza de la sesión puede diferir del nivel actual del ciudadano. | HU-B2-001 línea 137; RF-B1-025 línea 61 |
| 4 | `borrador_solicitud.version_ficha_tramite` | La HU-03-D04 menciona explícitamente detectar si "la ficha del trámite cambió desde el guardado"; se necesita versionar la ficha. No hay mecanismo de versionado del trámite en el documento — vacío a confirmar. | HU-03-D04 línea 151 |
| 5 | `encuesta_experiencia` necesita tabla `pregunta_encuesta` separada | RF-B1-094 dice "máximo 3 preguntas" sin definir cuáles; hardcodear `respuesta_pregunta_1/2/3` viola 1FN si las preguntas varían por trámite o período. Se lista como vacío a resolver. | RF-B1-094 línea 35 |
| 6 | `pago` puede tener N filas por `solicitud` (reintentos) pero solo 1 en estado `APROBADO` | RN-03-D01 garantiza idempotencia; sin embargo, un pago `RECHAZADO` puede motivar nuevo intento con nueva clave de idempotencia. Cardinalidad real: 1 solicitud → 0..N pagos, con UNIQUE parcial en `(id_solicitud, estado='APROBADO')`. | RN-03-D01 línea 112; RN-03-D02 línea 113 |
| 7 | `solicitud.canal_ingreso` | RF-B1-095 menciona "medios electrónicos en sedes físicas con personal de apoyo"; necesita distinguir la solicitud asistida de la autogestionada para el tablero BI (RF-B1-093). | RF-B1-095 línea 47; RF-B1-093 línea 46 |
| 8 | Tabla de configuración de encuesta (`pregunta_encuesta`) | No modelada porque el documento no la especifica; se marca como vacío crítico. | RF-B1-094 línea 35 |

---

## 7. Normativa citada

| Norma | Artículo | Regla aplicable | Línea fuente |
|-------|----------|-----------------|--------------|
| Ley 2052/2020 | Art.6 | Trámites nuevos 100% en línea | línea 98 |
| Ley 2052/2020 | Art.12 | SAP: silencio administrativo positivo | línea 119 |
| Ley 2052/2020 | Art.19 | Actualización SUIT ≤3 días hábiles | línea 97 |
| Decreto 088/2022 | Art.2.2.20.6/7 | Plazos de digitalización por bloques (Alcaldía-Avanzado: 30% may/2028, 100% mar/2034) | línea 99 |
| Decreto 088/2022 | Art.2.2.20.8 | Digitalizar todos los pasos posibles si no es digitalizable total | línea 100 |
| Decreto 088/2022 | Art.2.2.20.9 | Sin cobro adicional por canal digital | línea 98 |
| Decreto 2106/2019 | Art.1 | Trámites en línea desde primer día | línea 98 |
| Decreto 2106/2019 | Art.3 | Modificaciones estructurales requieren concepto DAFP ≤30 días | línea 103 |
| Decreto 2106/2019 | Art.6 | Consultas de información pública no son trámites | línea 102 |
| Decreto 2106/2019 | Art.7 | No incrementar tarifas por digitalización | línea 101 |
| Decreto 2106/2019 | Art.10 | Excepción falla técnica de interoperabilidad | línea 117 |
| Decreto 2106/2019 | Art.17 | Pagos electrónicos en trámites | línea 128 |
| Decreto 2106/2019 | Art.19 | Consulta gratuita de registros públicos | línea 45 |
| Decreto 2106/2019 | Arts.99,111 | Certificados SGSSS/RTM no exigibles físicos | línea 104 |
| Decreto 620/2020 | Art.2.2.17.4.7 | Excepción falla técnica de interoperabilidad | línea 117 |
| Ley 1755/2015 | Art.17 | Requerimiento de documentos y desistimiento por no subsanar | línea 116 |
| Ley 1755/2015 | Art.18 | Desistimiento expreso del ciudadano | línea 118 |
| Ley 1755/2015 | Arts.83-85 | Silencio administrativo positivo | línea 119 |
| Ley 1581/2012 | — | Autorización de tratamiento de datos personales en formulario | línea 30 |
| CPACA | — | Régimen general de desistimiento | línea 118 |
| Reglamentación Superfinanciera | — | Pagos electrónicos PSE/tarjeta; conciliación de estados | líneas 53, 71 |

---

## 8. Vacíos detectados (para investigar)

| # | Vacío | Impacto en BD | Fuente |
|---|-------|--------------|--------|
| V-01 | **[PREGUNTA ABIERTA]** Plazo de retención de borradores (RF-03-D03, RNF-03-D02): ¿cuántos días/horas hasta expiración? Responsable: Protección de Datos. | Define `solicitud.fecha_expiracion_borrador` y política de borrado. | línea 91 |
| V-02 | **[PREGUNTA ABIERTA]** Política de reembolso ante desistimiento con pago previo (HU-03-D08): ¿reembolso automático, manual, por trámite? | Podría requerir tabla `politica_reembolso` vinculada a `tramite`. | línea 155 |
| V-03 | **[PREGUNTA ABIERTA]** Catálogo completo de trámites en SUIT: cantidad, nivel actual de digitalización, trámites de mayor volumen. | Afecta el diseño de índices, particionado y carga inicial. | línea 182 |
| V-04 | **[PREGUNTA ABIERTA]** Matriz trámite ↔ nivel de autenticación requerido (HU-03-D11): quién la define y cómo se mantiene actualizada. | `tramite.nivel_autenticacion_requerido` necesita proceso de actualización. | línea 158 |
| V-05 | **[PREGUNTA ABIERTA]** Catálogo de trámites con SAP y su término (HU-03-D09): Secretaría Jurídica debe proveer la lista. | `tramite.aplica_sap` y `termino_sap_dias` no pueden poblarse sin ese catálogo. | línea 156 |
| V-06 | **[AMBIGUO]** Adjuntos (C-01): Anexo 5 dice "sin restricciones técnicas"; Anexo 5.1 dice "la entidad define tipo y tamaño". La resolución adoptada en RN-03-D04 (trámites SÍ admiten restricciones) necesita confirmación formal. | Define los CHECK de MIME/tamaño en `documento_adjunto`. | línea 179 |
| V-07 | **[INFERIDO/VACÍO]** Versionado del trámite (ficha): no hay entidad `tramite_version` en el documento; la HU-03-D04 exige detectar cambios en la ficha entre guardado del borrador y reanudación. | Requiere tabla `tramite_version` o columna `version INTEGER` en `tramite`. | línea 151 |
| V-08 | **[INFERIDO/VACÍO]** Configuración de preguntas de encuesta: RF-B1-094 dice "máximo 3 preguntas" sin especificar texto ni si varían por trámite. | Si son fijas → no se necesita tabla adicional. Si varían por trámite o período → necesaria `pregunta_encuesta`. | línea 35 |
| V-09 | **[INFERIDO/VACÍO]** Trámites jurisdiccionales (§9 línea 183): demandas, términos procesales, audiencias, reparto aleatorio. El documento usa "debería" (no "debe"); no hay entidades definidas. Si se incluyen, generarían al menos 4 entidades nuevas. | Pendiente de decisión con Secretaría Jurídica. | línea 183 |
| V-10 | Intervalo de reconsulta de pago `pendiente` (RF-03-D02 "cada N min"): valor de N no está definido. | Afecta la configuración del job de reconsulta; no impacta estructura de tabla pero sí `pago.intentos_reconsulta`. | línea 71 |

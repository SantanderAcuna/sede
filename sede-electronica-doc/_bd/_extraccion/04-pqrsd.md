# Extracción BD — Módulo 04 PQRSD

## 0. Cobertura

- Unidad: `/var/www/proyect-doc/elicitacion/sede-electronica/04-pqrsd/pqrsd.md`
- Líneas cubiertas: 1–132
- **Íntegra: SÍ. Líneas totales: 132. Omitidas: 0.**
- Secciones leídas: §1 Descripción/alcance · §2 RF (base + delta) · §3 RNF (base + delta) · §4 RN (base + delta) · §5 Casos de uso · §6 HU (base + delta) · §7 Datos/Entidades · §8 Integraciones · §9 Ambigüedades

---

## 1. Entidades candidatas

| # | Nombre | Descripción | CANDIDATA-COMPARTIDA | Fuente (línea) |
|---|--------|-------------|----------------------|----------------|
| 1 | **pqrsd** | Solicitud ciudadana (petición, queja, reclamo, sugerencia, denuncia, solicitud de información). Entidad central del módulo. | NO — propietaria del módulo 04 | L116, L18, RF-B1-031 |
| 2 | **radicado** | Número de radicación único asignado atómicamente a cada PQRSD; incluye fecha/hora y consecutivo. | CANDIDATA-COMPARTIDA (módulos 12 SGDEA, 02 Transparencia) | L116, L22, RNF-04-D01, RN-04-D07 |
| 3 | **ciudadano** | Persona natural o jurídica que presenta la PQRSD; puede ser anónimo. Porta nombre/razón social, tipo/nº de documento, correo, teléfono, dirección. | CANDIDATA-COMPARTIDA (módulos 09, 12, otros) | L18, L116, UC-B1-001 |
| 4 | **adjunto** | Archivo adjuntado a una PQRSD. Sin restricciones técnicas de formato/tamaño/cantidad por mandato normativo (C-01 pendiente). | CANDIDATA-COMPARTIDA (módulo 12 Gestión de Contenidos) | L18, L20, L116, RF-B1-033, RN-B1-010 |
| 5 | **dependencia** | Unidad orgánica de la Alcaldía destinataria o responsable de tramitar la PQRSD. | CANDIDATA-COMPARTIDA (varios módulos) | L18, L21, L116, RF-04-D01 |
| 6 | **notificacion** | Registro de cada acto de notificación al ciudadano (acuse, canal, fecha, constancia). Incluye canales: correo procesal, SMS, correo certificado, edicto, físico. | CANDIDATA-COMPARTIDA (módulos 09, 12) | L22, L39, L76, RN-04-D05, RF-04-D05 |
| 7 | **tipo_pqrsd** | Dominio/catálogo de tipos de solicitud con su plazo legal diferenciado. | NO — dominio maestro del módulo 04 | L18, L72, RN-04-D01 |
| 8 | **plazo_legal** | Regla de plazo hábil por tipo de petición: días, prorrogabilidad y máximo de prórroga. | NO — puede ser tabla de configuración del módulo 04 | L72–L73, RN-04-D01, RN-04-D02 |
| 9 | **asignacion_dependencia** | Historial de asignaciones y reasignaciones de la PQRSD entre dependencias (traza completa exigida). | NO — asociación con historia del módulo 04 | L105, HU-04-D01, RF-04-D01 |
| 10 | **traslado_competencia** | Registro del traslado de una PQRSD a una autoridad externa, con entidad receptora, fecha y plazo (≤5 días). | NO — entidad especializada del módulo 04 | L35, L74, RN-04-D03, HU-04-D01 |
| 11 | **prorroga** | Registro de ampliación del plazo de respuesta: motivación, nueva fecha, fecha de registro. Bloqueada si el término ya venció o si supera el máximo legal. | NO — entidad del módulo 04 | L73, L108, RN-04-D02, HU-04-D04 |
| 12 | **respuesta_pqrsd** | Documento de respuesta cargado por el funcionario, con fecha, canal de notificación, indicador dentro/fuera del término. | NO — entidad del módulo 04; relacionada con `adjunto` y `notificacion` | L36, L75–L76, RN-04-D04, HU-04-D02 |
| 13 | **expediente_sgdea** | Expediente electrónico creado en el SGDEA para cada PQRSD radicada. Referencia externa al módulo 12. | CANDIDATA-COMPARTIDA (módulo 12) | L26, L120, RF-B1-036 |
| 14 | **calendario_habiles** | Tabla de días hábiles/inhábiles del Distrito (festivos). Necesaria para cómputo de plazos. | CANDIDATA-COMPARTIDA (módulo 12, RF-B2-068) | L37, L72, RF-04-D03, RN-04-D01 |
| 15 | **tipo_documento_id** | Dominio de tipos de documento de identidad del ciudadano: CC, NUIP, CE, NIT, Pasaporte. | CANDIDATA-COMPARTIDA (módulo identidad/ciudadano) | L18, L38, RF-04-D04 |

---

## 2. Atributos/campos por entidad

### 2.1 `pqrsd`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| pqrsd_id | BIGSERIAL | SÍ | Secuencial interno | PK | [INFERIDO] — toda entidad requiere PK |
| tipo_pqrsd_id | INTEGER | SÍ | FK → tipo_pqrsd | FK | L18, RF-B1-031 |
| es_anonima | BOOLEAN | SÍ | DEFAULT FALSE | — | L18–L19, RF-B1-031, RF-B1-032 |
| es_identidad_reservada | BOOLEAN | SÍ | DEFAULT FALSE; relevante para quejas/denuncias | — | L77, L94, RN-04-D06 |
| nombre_razon_social | VARCHAR(300) | CONDICIONAL | NULL si es_anonima = TRUE | — | L18, L116, RF-B1-031 |
| tipo_documento_id | INTEGER | CONDICIONAL | FK → tipo_documento_id; NULL si es_anonima = TRUE | FK | L18, RF-B1-031, RF-04-D04 |
| numero_documento | VARCHAR(20) | CONDICIONAL | Patrón por tipo (CC/CE/NIT/Pasaporte); NULL si es_anonima = TRUE | — | L18, L38, RF-04-D04 |
| correo | VARCHAR(254) | SÍ | Formato RFC 5322; NOT NULL aunque anónima (canal de notificación o acuse) | — | L18, L38, RF-B1-031, RF-04-D04 |
| telefono | VARCHAR(20) | NO | Opcional | — | L18, L116 |
| direccion_notificacion | TEXT | CONDICIONAL | NULL si es_anonima = TRUE | — | L18, L116, RF-B1-031 |
| canal_respuesta | VARCHAR(50) | SÍ | CHECK IN ('correo','SMS','correo_certificado','fisico','edicto') | — | L18, L39, RN-04-D05 |
| dependencia_id | INTEGER | SÍ | FK → dependencia; default [AMBIGUO] "Despacho del Alcalde" (A-11) | FK | L18, L130, RF-04-D01 |
| objeto | VARCHAR(2000) | SÍ | MAX 2000 caracteres; CHECK length ≤ 2000 | — | L18, L38, RF-B1-031, RF-04-D04 |
| acepta_condiciones | BOOLEAN | SÍ | CHECK = TRUE para guardar | — | L18, RF-B1-031 |
| radicado_id | BIGINT | SÍ | FK → radicado; asignado tras validación exitosa | FK | L116, L22, RN-04-D07 |
| estado | VARCHAR(30) | SÍ | CHECK IN ('radicada','en_tramite','prorrogada','trasladada','respondida','cerrada') | — | L21, L36, RF-B1-034, RN-04-D04 |
| fecha_hora_recepcion | TIMESTAMPTZ | SÍ | NOT NULL; asignada al momento de la radicación | — | L116, L22, RF-B3-099 |
| fecha_estimada_respuesta | DATE | NO | Calculada: fecha_hora_recepcion + plazo legal en días hábiles | — | L21, RF-B1-034 |
| cumplimiento_plazo | VARCHAR(20) | NO | CHECK IN ('dentro_termino','fuera_termino'); se fija al cierre | — | L36, L75, RN-04-D04, HU-04-D02 |
| expediente_sgdea_ref | VARCHAR(100) | NO | Referencia externa al SGDEA; puede ser NULL hasta sincronización | — | L120, RF-B1-036 |
| ip_origen | INET | NO | Registrada incluso si es anónima (aviso legal); no expuesta | — | L19, RF-B1-032 |
| acepta_privacidad | BOOLEAN | SÍ | CHECK = TRUE | — | L85, UC-B1-001 |

### 2.2 `radicado`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| radicado_id | BIGSERIAL | SÍ | PK; secuencial atómico (ver RN-04-D07) | PK | L116, RN-04-D07 |
| numero_radicado | VARCHAR(50) | SÍ | UNIQUE; estructura [AMBIGUO]: prefijo_dependencia + año + consecutivo (RNF-04-D01) | UNIQUE | L53, RNF-04-D01 |
| fecha_hora_asignacion | TIMESTAMPTZ | SÍ | NOT NULL; ≤24 h hábiles tras recepción | — | L22, L63, RN-B3-026 |
| consecutivo_anual | INTEGER | SÍ | Incremento atómico; UNIQUE dentro del año | — | L54, RNF-04-D02, RN-04-D07 |
| anio | SMALLINT | SÍ | Año del consecutivo | — | [INFERIDO] de RNF-04-D01 |

### 2.3 `ciudadano`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| ciudadano_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| nombre_razon_social | VARCHAR(300) | SÍ | — | — | L18, L116 |
| tipo_documento_id | INTEGER | SÍ | FK → tipo_documento_id | FK | L18 |
| numero_documento | VARCHAR(20) | SÍ | Patrón por tipo; UNIQUE por tipo | UNIQUE | L18, RF-04-D04 |
| correo | VARCHAR(254) | SÍ | Formato RFC 5322 | — | L18, RF-04-D04 |
| telefono | VARCHAR(20) | NO | — | — | L18, L116 |
| direccion_notificacion | TEXT | NO | — | — | L18, L116 |
| canal_respuesta_preferido | VARCHAR(50) | NO | CHECK IN ('correo','SMS','correo_certificado','fisico','edicto') | — | L18, L39 |

> Nota: en PQRSD anónimas no se vincula ciudadano_id; los datos de identidad de `pqrsd` quedan NULL. La entidad `ciudadano` puede existir como tabla compartida; en PQRSD anónimas la FK es NULL [INFERIDO].

### 2.4 `adjunto`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| adjunto_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd | FK | L18, L116, RF-B1-031 |
| nombre_archivo | VARCHAR(500) | SÍ | NOT NULL | — | L18, RF-B1-033 |
| mime_type | VARCHAR(100) | NO | Sin restricción normativa (RN-B1-010); [AMBIGUO] C-01 | — | L127, C-01 |
| tamanio_bytes | BIGINT | NO | Sin restricción normativa; [AMBIGUO] C-01 | — | L127, C-01 |
| ruta_almacenamiento | TEXT | SÍ | Ruta en servidor/SGDEA | — | L120, RF-B1-036 |
| fecha_carga | TIMESTAMPTZ | SÍ | NOT NULL | — | [INFERIDO] |

> [AMBIGUO] C-01: la normativa (Art. 23 CP, Ley 1755) prohíbe restricciones; la implementación actual limita a PDF/JPG/PNG y 10 MB. El esquema no impone CHECK de formato/tamaño hasta resolución del conflicto.

### 2.5 `dependencia`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| dependencia_id | SERIAL | SÍ | PK | PK | [INFERIDO] |
| nombre | VARCHAR(200) | SÍ | NOT NULL | — | L18, L116 |
| codigo | VARCHAR(20) | NO | Código orgánico; [INFERIDO] necesario para prefijo de radicado (RNF-04-D01) | — | [INFERIDO] |
| es_externa | BOOLEAN | SÍ | DEFAULT FALSE; TRUE para autoridades externas (traslado competencia) | — | L35, L74, RN-04-D03 |
| entidad_externa_nombre | VARCHAR(300) | CONDICIONAL | NOT NULL si es_externa = TRUE (ej. Procuraduría) | — | L74, L123, RN-04-D03, RN-04-D06 |

### 2.6 `notificacion`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| notificacion_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd | FK | L22, L39, RF-04-D05 |
| tipo_notificacion | VARCHAR(30) | SÍ | CHECK IN ('acuse_recibo','respuesta','prorroga','traslado','alerta_vencimiento') | — | L22, L36, L39, RN-04-D05 |
| canal | VARCHAR(30) | SÍ | CHECK IN ('correo','SMS','correo_certificado','fisico','edicto','CCD') | — | L39, L76, RN-04-D05 |
| destinatario | VARCHAR(300) | SÍ | Dirección/número del canal | — | L39, RN-04-D05 |
| fecha_envio | TIMESTAMPTZ | SÍ | NOT NULL | — | L39, L76, RN-04-D05 |
| constancia_envio | TEXT | NO | Acuse, código de confirmación, número de guía | — | L39, L76, RN-04-D05 |
| es_electronica_autorizada | BOOLEAN | SÍ | DEFAULT FALSE; TRUE cuando existe dirección procesal electrónica autorizada | — | L76, RN-04-D05 |

### 2.7 `tipo_pqrsd` (tabla de dominio / catálogo)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| tipo_pqrsd_id | SERIAL | SÍ | PK | PK | [INFERIDO] |
| codigo | VARCHAR(30) | SÍ | UNIQUE; CHECK IN ('peticion_general','peticion_informacion','consulta','peticion_entre_autoridades','queja','reclamo','sugerencia','denuncia','solicitud_informacion') | UNIQUE | L18, L72 |
| descripcion | VARCHAR(200) | SÍ | Etiqueta visible | — | L18 |
| plazo_dias_habiles | SMALLINT | SÍ | NOT NULL; valor base sin prórroga | — | L72, RN-04-D01 |
| plazo_maximo_prorroga_dias | SMALLINT | NO | NULL si el tipo no admite prórroga | — | L73, RN-04-D02 |
| requiere_identificacion | BOOLEAN | SÍ | DEFAULT TRUE; FALSE solo para tipos que admiten anonimato | — | L19, L60, RN-B1-009 |
| es_gratuita | BOOLEAN | SÍ | DEFAULT TRUE; FALSE con excepciones (RN-04-D08) | — | L62, L79, RN-04-D08 |
| normativa_cita | TEXT | NO | Referencia legal del plazo (Ley 1755 Art.14/19/20/21) | — | L72, RN-04-D01 |

> Valores concretos extraídos (RN-04-D01, L72):
> - `peticion_general` → 15 días hábiles
> - `peticion_informacion` / `peticion_documentos` → 10 días hábiles
> - `consulta` → 30 días hábiles
> - `peticion_entre_autoridades` → 10 días
> - Consultas ARCO: consultas datos personales ≤10 días hábiles (prorrogable 5); reclamos ≤15 días hábiles (prorrogable 8) (RF-B2-070, L27)

### 2.8 `asignacion_dependencia`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| asignacion_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd | FK | L105, HU-04-D01 |
| dependencia_id | INTEGER | SÍ | FK → dependencia | FK | L105, HU-04-D01 |
| tipo_asignacion | VARCHAR(20) | SÍ | CHECK IN ('asignacion_inicial','reasignacion_interna','traslado_competencia') | — | L105, RF-04-D01 |
| fecha_asignacion | TIMESTAMPTZ | SÍ | NOT NULL | — | L105 |
| funcionario_id | INTEGER | NO | FK → funcionario (entidad no definida en este módulo) [INFERIDO] | FK | [INFERIDO] |
| motivo | TEXT | NO | Motivo de reasignación o traslado | — | L105, HU-04-D01 |
| es_activa | BOOLEAN | SÍ | DEFAULT TRUE; solo una activa por PQRSD | — | [INFERIDO] |

### 2.9 `traslado_competencia`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| traslado_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd | FK | L35, L74, RN-04-D03 |
| entidad_receptora_id | INTEGER | SÍ | FK → dependencia (es_externa = TRUE) | FK | L35, L74, RN-04-D03 |
| fecha_traslado | DATE | SÍ | NOT NULL | — | L35, L74 |
| fecha_limite_traslado | DATE | SÍ | Calculada: fecha_hora_recepcion + 5 días hábiles (RN-04-D03) | — | L74, RN-04-D03 |
| dentro_termino_traslado | BOOLEAN | NO | Calculado al registrar: fecha_traslado ≤ fecha_limite_traslado | — | L105, HU-04-D01 |
| notificacion_ciudadano_id | BIGINT | NO | FK → notificacion | FK | L35, RN-04-D03 |
| con_reserva_identidad | BOOLEAN | SÍ | DEFAULT FALSE; TRUE cuando se traslada con identidad reservada (Procuraduría) | — | L77, L123, RN-04-D06 |

### 2.10 `prorroga`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| prorroga_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd | FK | L73, L108, RN-04-D02 |
| fecha_registro_prorroga | TIMESTAMPTZ | SÍ | NOT NULL; debe ser ANTES del vencimiento original (CHECK) | — | L108, HU-04-D04 |
| nueva_fecha_limite | DATE | SÍ | NOT NULL; no puede exceder máximo legal | — | L73, L108, RN-04-D02 |
| motivacion | TEXT | SÍ | NOT NULL | — | L108, HU-04-D04 |
| notificacion_ciudadano_id | BIGINT | NO | FK → notificacion | FK | L73, RN-04-D02 |
| es_valida | BOOLEAN | SÍ | Calculada: fecha_registro < vencimiento original AND nueva_fecha_limite ≤ max_legal | — | L108, HU-04-D04 |

### 2.11 `respuesta_pqrsd`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| respuesta_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd; UNIQUE (una respuesta por PQRSD) [INFERIDO] | FK UNIQUE | L36, L75, RN-04-D04 |
| fecha_carga | TIMESTAMPTZ | SÍ | NOT NULL; fecha en que el funcionario carga la respuesta | — | L36, L75 |
| contenido | TEXT | NO | Texto de la respuesta o descripción | — | L36, L116 |
| adjunto_respuesta_id | BIGINT | NO | FK → adjunto (documento de respuesta) | FK | [INFERIDO] |
| cumplimiento_plazo | VARCHAR(20) | SÍ | CHECK IN ('dentro_termino','fuera_termino'); calculado | — | L36, L75, RN-04-D04 |
| notificacion_id | BIGINT | NO | FK → notificacion (notificación de la respuesta) | FK | L36, L76, RN-04-D04 |
| funcionario_id | INTEGER | NO | FK → funcionario [INFERIDO] | FK | [INFERIDO] |

### 2.12 `expediente_sgdea` (referencia externa — módulo 12)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| expediente_id | BIGSERIAL | SÍ | PK | PK | [INFERIDO] |
| pqrsd_id | BIGINT | SÍ | FK → pqrsd | FK | L120, RF-B1-036 |
| referencia_externa | VARCHAR(100) | SÍ | Identificador en el SGDEA | — | L120 |
| fecha_creacion | TIMESTAMPTZ | SÍ | NOT NULL; < 5 s tras radicación (RNF-B1-008) | — | L26, RNF-B1-008 |

### 2.13 `calendario_habiles` (compartida — módulo 12 / RF-B2-068)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| fecha | DATE | SÍ | PK | PK | L37, L72, RF-04-D03 |
| es_habil | BOOLEAN | SÍ | DEFAULT TRUE; FALSE si festivo/no laboral | — | L37, L72, RN-04-D01 |
| descripcion | VARCHAR(100) | NO | Nombre del festivo si aplica | — | [INFERIDO] |

### 2.14 `tipo_documento_id` (tabla de dominio)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente (línea) |
|-------|------|-------------|----------------------|-------|----------------|
| tipo_doc_id | SERIAL | SÍ | PK | PK | [INFERIDO] |
| codigo | VARCHAR(20) | SÍ | UNIQUE; CHECK IN ('CC','NUIP','CE','NIT','Pasaporte') | UNIQUE | L18, RF-04-D04 |
| descripcion | VARCHAR(100) | SÍ | — | — | L18 |
| patron_validacion | VARCHAR(200) | NO | Expresión regular del patrón por tipo (ej. NIT con dígito verificación) | — | L38, RF-04-D04 |

---

## 3. Reglas de negocio con impacto en datos

| ID-RN | Regla | Impacto en esquema | Cita normativa | Fuente (línea) |
|-------|-------|--------------------|----------------|----------------|
| RN-B1-009 | Quejas/denuncias anónimas: normativa protección denunciante; NO aplica a solicitudes de información con notificación | CHECK en `pqrsd`: si tipo requiere notificación, es_anonima debe ser FALSE | Art.38 Ley 190/1995; Art.69 Ley 734/2002; Art.81 Ley 962/2005 | L60 |
| RN-B1-010 | Sin restricciones técnicas de formato/tamaño/cantidad de adjuntos | `adjunto`: no CHECK de mime_type ni tamanio_bytes; [AMBIGUO] C-01 | Art.23 CP; Ley 1755/2015 | L61 |
| RN-B1-011 / RN-04-D08 | Consulta de información pública gratuita; solo se cobra reproducción de copias al costo | `tipo_pqrsd.es_gratuita`; lógica de cobro fuera del esquema | Ley 2052/2020 Art.15; Ley 1712/2014 Art.26 | L62, L79 |
| RN-B3-026 | Acuse de recibo automático inmediato; radicado en ≤24 h hábiles | `radicado.fecha_hora_asignacion` NOT NULL; trigger/job asíncrono | Ley 1437/2011; Ley 1755/2015 | L63 |
| RN-B2-019 | Formularios normalizados para peticiones | Estructura estándar de `pqrsd` | Decreto 620/2020 Art.2.2.17.6.5 | L64 |
| RN-04-D01 | Plazos diferenciados por tipo (15/10/30/10 días hábiles) sobre calendario hábil del Distrito | `tipo_pqrsd.plazo_dias_habiles`; cómputo usa `calendario_habiles`; `pqrsd.fecha_estimada_respuesta` calculada | Ley 1755/2015 Arts.14,19,20,21 | L72 |
| RN-04-D02 | Prórroga: antes del vencimiento, nueva fecha ≤ máximo legal; si vencido → extemporánea | `prorroga.es_valida`; CHECK fecha_registro < vencimiento; `tipo_pqrsd.plazo_maximo_prorroga_dias` | Ley 1755/2015 Art.14 par. | L73 |
| RN-04-D03 | Traslado por competencia ≤5 días hábiles desde recepción; notifica a ciudadano | `traslado_competencia.fecha_limite_traslado`; `traslado_competencia.dentro_termino_traslado` | Ley 1755/2015 Art.21 | L74 |
| RN-04-D04 | Cierre solo con respuesta registrada; marca dentro/fuera de término | `pqrsd.estado = 'cerrada'` bloqueado si no existe `respuesta_pqrsd`; `respuesta_pqrsd.cumplimiento_plazo` | Ley 1755/2015; Ley 1437/2011 | L75 |
| RN-04-D05 | Notificación electrónica preferente si existe dirección procesal autorizada; constancia de envío | `notificacion.es_electronica_autorizada`; `notificacion.constancia_envio` NOT NULL para canales formales | Ley 1437/2011 Arts.56,67,69; Decreto 2106 Arts.46,47 | L76 |
| RN-04-D06 | Identidad reservada: sistema NO expone datos; traslado a Procuraduría con reserva | `pqrsd.es_identidad_reservada`; control de acceso (fuera del esquema); `traslado_competencia.con_reserva_identidad` | Art.38 Ley 190/1995; Art.81 Ley 962/2005 | L77 |
| RN-04-D07 | Radicado atómico y único bajo concurrencia | `radicado.numero_radicado` UNIQUE + secuencia de BD atómica (SEQUENCE PostgreSQL) | Buena práctica gestión documental | L78 |
| RN-04-D08 | Gratuidad con excepciones tasadas (mercantil, laboral, profesional, seguridad social) | `tipo_pqrsd.es_gratuita`; lógica de excepciones en capa de servicio | Ley 1712/2014 Art.26; Ley 2052/2020 Art.15 | L79 |
| RNF-04-D01 | [PREGUNTA ABIERTA] Estructura del número de radicado (prefijo + año + consecutivo) | `radicado.numero_radicado` VARCHAR(50) UNIQUE; formato pendiente definición de Gestión Documental | — | L53 |
| RNF-04-D02 | Consecutivo atómico bajo concurrencia | SEQUENCE PostgreSQL; `radicado.consecutivo_anual` asignado dentro de transacción SERIALIZABLE | — | L54 |

---

## 4. Cardinalidades y relaciones

| Entidad A | Cardinalidad | Entidad B | Descripción | Fuente (línea) |
|-----------|-------------|-----------|-------------|----------------|
| ciudadano | 0..N | pqrsd | Un ciudadano puede tener muchas PQRSD; una PQRSD anónima no tiene ciudadano (FK nullable) | L18, L85 |
| pqrsd | 1..1 | radicado | Cada PQRSD tiene exactamente un radicado; el radicado se asigna al momento de la radicación | L116, RN-04-D07 |
| pqrsd | 1..N | adjunto | Una PQRSD puede tener uno o más adjuntos (sin límite de cantidad por RN-B1-010) | L18, RF-B1-033 |
| pqrsd | N..1 | tipo_pqrsd | Cada PQRSD pertenece a un tipo; el tipo determina el plazo legal | L18, RN-04-D01 |
| pqrsd | N..1 | dependencia | Dependencia destinataria inicial; puede cambiar via asignacion_dependencia | L18, RF-04-D01 |
| pqrsd | 1..N | asignacion_dependencia | Historial de asignaciones; mínimo una (inicial); solo una activa | L105, HU-04-D01 |
| pqrsd | 0..1 | traslado_competencia | Una PQRSD puede tener como máximo un traslado a autoridad externa [INFERIDO] | L74, RN-04-D03 |
| pqrsd | 0..N | prorroga | Una PQRSD puede tener 0 o más prórrogas (aunque en la práctica sería una) [AMBIGUO] | L73, RN-04-D02 |
| pqrsd | 0..1 | respuesta_pqrsd | Una PQRSD tiene como máximo una respuesta formal registrada | L36, RN-04-D04 |
| pqrsd | 1..N | notificacion | Una PQRSD genera múltiples notificaciones (acuse, respuesta, alertas) | L22, L39, RN-04-D05 |
| pqrsd | 0..1 | expediente_sgdea | Una PQRSD genera un expediente en SGDEA; puede ser NULL hasta sincronización | L120, RF-B1-036 |
| tipo_pqrsd | 1..1 | plazo_legal | Los campos de plazo están embebidos en tipo_pqrsd; no se modela como entidad separada [INFERIDO] | L72, RN-04-D01 |
| asignacion_dependencia | N..1 | dependencia | Cada asignación apunta a una dependencia (interna o externa) | L105, RF-04-D01 |
| traslado_competencia | N..1 | dependencia (externa) | El traslado referencia una dependencia con es_externa = TRUE | L74, RN-04-D03 |
| notificacion | N..1 | pqrsd | Cada notificación pertenece a una PQRSD | L39, RN-04-D05 |

---

## 5. Jerarquías ISA / polimorfismo (subtipos PQRSD)

### 5.1 Análisis de la jerarquía ISA

El documento enumera 6 tipos de solicitud en `tipo` del formulario (L18):
`Petición / Queja / Reclamo / Sugerencia / Denuncia / Solicitud de información`

Y en RN-04-D01 (L72) desagrega plazos para:
`petición general / petición de documentos o información / consulta / petición entre autoridades`

Diferencias estructurales relevantes entre subtipos:

| Criterio | Petición general | Petición información/docs | Consulta | Queja/Denuncia | Sugerencia |
|----------|-----------------|--------------------------|----------|---------------|------------|
| Plazo hábil | 15 días | 10 días | 30 días | 15 días [INFERIDO] | — |
| Admite anonimato | NO (requiere notificación) [INFERIDO] | NO | SÍ | SÍ (RN-B1-009) | SÍ |
| Requiere respuesta formal | SÍ | SÍ | SÍ | SÍ | NO [INFERIDO] |
| Traslado Procuraduría | NO | NO | NO | SÍ (con reserva) | NO |
| Gratuita | SÍ | SÍ (solo reproducción copias) | SÍ | SÍ | SÍ |

### 5.2 Decisión de mapeo

**Estrategia elegida: tabla única por jerarquía (single-table) con discriminador `tipo_pqrsd_id`.**

Justificación:
- Los atributos diferenciadores no son estructuralmente distintos sino de comportamiento (plazo, anonimato, gratuidad): están capturados en `tipo_pqrsd` como datos, no como columnas adicionales.
- No existe ningún campo propio exclusivo de un subtipo que no sea NULL-able en los demás, salvo `es_identidad_reservada` (quejas/denuncias) y `es_anonima` (booleanos ya en la tabla base).
- La consulta global (seguimiento, informes trimestrales para módulo 02) es el patrón dominante: un single-table evita JOINs costosos.
- La restricción de disyunción es TOTAL y DISJOINT: una PQRSD pertenece a exactamente un tipo (CHECK NOT NULL en `tipo_pqrsd_id`).

Alternativa descartada (tabla por subtipo / class-table): solo aplicaría si cada subtipo tuviera 3+ atributos propios estructuralmente distintos, lo cual no ocurre en este dominio.

### 5.3 Polimorfismo (asociaciones polimórficas)

La entidad `notificacion` puede corresponder a distintos eventos del ciclo de vida de la PQRSD (acuse, respuesta, prórroga, traslado, alerta). El campo `tipo_notificacion` actúa como discriminador. No se requiere arco exclusivo hacia múltiples entidades; la FK es directamente `pqrsd_id` con el tipo en `tipo_notificacion`. No existe antipatrón `(entity_type, entity_id)` porque la FK es concreta y referenciada a una sola tabla.

---

## 6. Inferencias [INFERIDO]

| # | Inferencia | Justificación | Entidad/Campo afectado |
|---|-----------|---------------|----------------------|
| I-01 | Toda entidad requiere PK surrogada BIGSERIAL | Principio de integridad de entidad (Codd); el documento no especifica claves técnicas | Todas las entidades |
| I-02 | En PQRSD anónima la FK `ciudadano_id` es NULL | El documento dice "se deshabilitan campos de identificación" (L19); no menciona entidad ciudadano separada; se infiere que el vínculo al ciudadano es opcional | `pqrsd.ciudadano_id` nullable |
| I-03 | `respuesta_pqrsd` es 0..1 por PQRSD | RN-04-D04 dice "no se cierra sin respuesta registrada" implica exactamente una; antes del cierre es 0 | `respuesta_pqrsd` UNIQUE pqrsd_id |
| I-04 | `funcionario_id` como FK en asignacion y respuesta | Los UC y HU mencionan "funcionario" como actor del back-office; la entidad no se define en este módulo | `asignacion_dependencia.funcionario_id`, `respuesta_pqrsd.funcionario_id` |
| I-05 | `calendario_habiles` es tabla compartida con módulo 12 | RF-B2-068 define el registro electrónico 24/7 con calendario hábil; la misma tabla sirve a ambos módulos | `calendario_habiles` |
| I-06 | El plazo de prórroga ARCO (consultas 5 días extra, reclamos 8 días extra) debe modelarse en `tipo_pqrsd.plazo_maximo_prorroga_dias` | RF-B2-070 (L27) enumera plazos específicos ARCO; son subtipos funcionales del tipo_pqrsd | `tipo_pqrsd.plazo_maximo_prorroga_dias` |
| I-07 | `dependencia.codigo` necesario para prefijo de radicado | RNF-04-D01 sugiere "prefijo dependencia + año + consecutivo"; el código orgánico es el dato natural | `dependencia.codigo` |
| I-08 | `pqrsd.ip_origen` almacenada aunque anónima | L19 menciona explícitamente la advertencia sobre georreferenciación, IP, metadata; implica que se registra | `pqrsd.ip_origen` |
| I-09 | Una PQRSD puede tener a lo más un traslado a autoridad externa | El flujo descrito no contempla múltiples traslados; si hubiera reasignación interna, se usa `asignacion_dependencia` | `traslado_competencia` 0..1 por pqrsd |
| I-10 | `plazo_legal` no se modela como entidad separada | Los atributos de plazo (días base y prórroga máxima) son FD de `tipo_pqrsd`; colapsarlos en la misma tabla preserva 3FN | `tipo_pqrsd.plazo_dias_habiles`, `tipo_pqrsd.plazo_maximo_prorroga_dias` |

---

## 7. Vacíos — información esperada que NO aparece en la fuente

| # | Vacío | Impacto en BD | Referencia fuente |
|---|-------|---------------|-------------------|
| V-01 | **[PREGUNTA ABIERTA] Estructura exacta del número de radicado** (prefijo dependencia + año + consecutivo): definición pendiente de Gestión Documental | `radicado.numero_radicado` modelado como VARCHAR(50) UNIQUE; formato a confirmar | L53, RNF-04-D01 |
| V-02 | **[PREGUNTA ABIERTA] Proceso interno post-radicación**: el módulo declara el vacío explícitamente (§9) | Flujo de estados de `pqrsd` parcialmente inferido; estados adicionales posibles | L131, §9 |
| V-03 | **[C-01 — ALTA] Restricción de adjuntos**: normativa prohíbe restricciones; implementación actual limita a PDF/JPG/PNG y 10 MB | `adjunto`: no se puede definir CHECK de mime_type/tamaño hasta resolución | L127 |
| V-04 | **[A-11] Enrutamiento de dependencia destinataria**: ¿automático o manual? Impacta si `asignacion_dependencia` se crea por trigger o por acción de funcionario | Lógica de `asignacion_dependencia.tipo_asignacion = 'asignacion_inicial'` | L130 |
| V-05 | **Entidad Funcionario**: mencionada como actor en HU-04-D01/D02/D04/D05/D07 pero no definida en este módulo | `asignacion_dependencia.funcionario_id` y `respuesta_pqrsd.funcionario_id` son FK a tabla no especificada aquí | L105, L106 |
| V-06 | **Número máximo de prórrogas por PQRSD**: RN-04-D02 indica que la prórroga no puede exceder el límite legal pero no explicita si puede haber más de una prórroga | Cardinalidad `pqrsd` → `prorroga`: modelado como 0..N con [AMBIGUO] | L73 |
| V-07 | **Canal de respuesta vs. dirección procesal electrónica autorizada** (Ley 1437): no queda claro si `pqrsd.canal_respuesta` captura la dirección procesal formal o es un campo libre | Podría requerir tabla separada de canales autorizados por ciudadano | L39, L76, RN-04-D05 |
| V-08 | **Alertas de vencimiento**: RF-B2-070 menciona alerta a 3 días del vencimiento; no se especifica si se persiste como notificación o solo es un job de monitoreo | `notificacion.tipo_notificacion = 'alerta_vencimiento'` modelado; mecanismo de disparo no definido | L27 |

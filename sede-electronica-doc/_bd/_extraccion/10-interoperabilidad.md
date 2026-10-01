# Extracción BD — Módulo 10 Interoperabilidad

## 0. Cobertura

- Unidad: `/var/www/proyect-doc/elicitacion/sede-electronica/10-interoperabilidad/interoperabilidad.md`
- Líneas leídas: 1–159
- Íntegra: SÍ — 0 líneas omitidas
- Secciones cubiertas: §1 Descripción/alcance, §2 RF (2.1–2.4 + 2.D Delta), §3 RNF (+ 3.D Delta), §4 RN (+ 4.D Delta), §5 UC, §6 HU (+ 6.D Delta), §7 Datos/Entidades, §8 Integraciones, §9 Ambigüedades

---

## 1. Entidades candidatas

| # | Nombre | Descripción | CANDIDATA-COMPARTIDA | Fuente |
|---|--------|-------------|----------------------|--------|
| 1 | `interop_xroad_member` | Miembro (entidad) registrado en la red X-Road; representa la Alcaldía u otra entidad en la PDI | NO | §7 línea 138; RF-B2-017 línea 21; RN-B3-032 línea 93 |
| 2 | `interop_xroad_subsystem` | Subsistema dentro de un miembro X-Road que representa un sistema de información | NO | §7 línea 138; RF-B2-017 línea 21 |
| 3 | `interop_xroad_service` | Servicio publicado en un subsistema (SOAP con WSDL o REST con OpenAPI 3+); incluye versión | NO | §7 línea 138; RF-B2-017 línea 21 |
| 4 | `interop_xroad_service_permission` | Permiso otorgado a un cliente (subsistema) para consumir un servicio dado | NO | RF-B2-017 línea 21 |
| 5 | `interop_xroad_transaction` | Registro de cada intercambio/mensaje X-Road: contenido, firma digital, estampa cronológica TSA, log de auditoría | NO | §7 líneas 139–139; UC-B3-011 línea 112; RNF-B3-030 línea 74 |
| 6 | `interop_certificate` | Certificado digital (autenticación, firma o TLS) asociado al servidor de seguridad; registra estado OCSP y vencimiento | NO | §7 línea 140; RF-B2-018 línea 22; RNF-10-D01 línea 81; RN-10-D03 línea 105 |
| 7 | `interop_tsa_config` | Configuración del servicio de estampado cronológico (proveedor, URL, estado); una fila activa por ambiente | NO | §7 línea 143; RF-B2-088 línea 23; RN-10-D02 línea 104 |
| 8 | `interop_tsa_queue_item` | Mensaje pendiente de estampado en la cola de buffer durante indisponibilidad del proveedor TSA | NO | RF-10-D02 línea 61; RN-10-D02 línea 104; HU-10-D02 línea 133 |
| 9 | `interop_ccd_service` | Definición de los 4 servicios REST expuestos a la Carpeta Ciudadana Digital (información usuario, alertas, historial trámites, historial solicitudes) | NO | RF-B2-020/RF-B3-116 línea 30; §7 línea 141 |
| 10 | `interop_ccd_user_data` | Snapshot/consulta de información personal del ciudadano devuelta por el servicio CCD (a); no almacenada permanentemente en CCD | CANDIDATA-COMPARTIDA (ciudadano) | §7 línea 141; RF-B2-020 línea 30; RF-B2-021 línea 31 |
| 11 | `interop_ccd_alert` | Alerta o comunicación enviada al ciudadano vía CCD (servicio b): idMensaje, asunto, texto, URL adjuntos, fecha | CANDIDATA-COMPARTIDA (ciudadano) | §7 línea 141; RF-B3-117 línea 33 |
| 12 | `interop_ccd_tramite_history` | Entrada del historial de trámites del ciudadano expuesta vía CCD (servicio c): idTramite, nombre, fecha, entidades consultadas | CANDIDATA-COMPARTIDA (trámite, ciudadano) | §7 línea 141; RF-B2-020 línea 30 |
| 13 | `interop_ccd_solicitud_history` | Entrada del historial de solicitudes del ciudadano expuesta vía CCD (servicio d): idSolicitud, nombre, fecha, estado, texto respuesta | CANDIDATA-COMPARTIDA (trámite, ciudadano) | §7 línea 141; RF-B2-020 línea 30 |
| 14 | `interop_and_agreement` | Acuerdo de Entendimiento/Vinculación con la AND para operar en producción | NO | §7 línea 142; RF-B2-019 línea 24; RN-B2-018 línea 91 |
| 15 | `interop_external_system` | Catálogo de sistemas externos integrados (SUIT, SIGEP, SECOP, SGDEA/Orfeo, Registraduría/ANI, RUNT, RUAF, RUT) con sus metadatos de conexión | NO | §2.4 líneas 49–52; §8 líneas 147–150 |
| 16 | `interop_requirement_mapping` | Mapeo de requisitos de trámite que pasan de exigir documento físico a verificarse vía interoperabilidad; referencia a SUIT | CANDIDATA-COMPARTIDA (trámite) | RF-B3-114 línea 41; RN-B2-005 línea 89; RF-B3-113 línea 40 |
| 17 | `interop_server_environment` | Registro de los 3 ambientes del servidor de seguridad X-Road (QA, Preproducción, Producción) con sus recursos y estado | NO | RF-B1-028 línea 20; RN-B2-021 línea 92 |
| 18 | `interop_error_log` | Log de errores controlados en intercambios X-Road: timeouts, respuestas vacías, OCSP vencidos, reintentos, fallback activado | NO | RF-10-D01 línea 60; RN-10-D01 línea 103; HU-10-D01 línea 132 |
| 19 | `interop_lci_certification` | Registro de la certificación nivel 3 del LCI por ambiente y la fecha de obtención | NO | RF-B2-019 línea 24; RN-B2-018 línea 91; RNF-B1-034 línea 71 |

---

## 2. Atributos/campos por entidad

### 2.1 `interop_xroad_member`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `xroad_instance` | VARCHAR(50) | SÍ | Ej. "CO" | — | §7 línea 138 |
| `member_class` | VARCHAR(10) | SÍ | [AMBIGUO] "CO" vs "GOB/PRIV" — §9 línea 154 | — | §7 línea 138; §9 línea 154 |
| `member_code` | VARCHAR(100) | SÍ | Formato "sigla_entidad-código_SIGEP"; UNIQUE | UNIQUE | RN-B3-032 línea 93; §7 línea 138 |
| `entity_name` | VARCHAR(255) | SÍ | Nombre descriptivo de la entidad | — | [INFERIDO] |
| `is_own_entity` | BOOLEAN | SÍ | TRUE = Alcaldía; FALSE = entidad externa | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |
| `updated_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.2 `interop_xroad_subsystem`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `member_id` | INTEGER | SÍ | FK → interop_xroad_member.id | FK | RF-B2-017 línea 21 |
| `subsystem_code` | VARCHAR(100) | SÍ | Código del subsistema dentro del miembro; UNIQUE por miembro | UNIQUE(member_id, subsystem_code) | §7 línea 138 |
| `description` | TEXT | NO | Descripción del sistema de información representado | — | RF-B2-017 línea 21 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.3 `interop_xroad_service`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `subsystem_id` | INTEGER | SÍ | FK → interop_xroad_subsystem.id | FK | RF-B2-017 línea 21 |
| `service_code` | VARCHAR(100) | SÍ | Código del servicio | — | §7 línea 138 |
| `service_version` | VARCHAR(20) | NO | Versión del servicio | — | §7 línea 138 |
| `protocol_type` | VARCHAR(10) | SÍ | CHECK IN ('REST','SOAP') | — | RF-B2-017 línea 21 |
| `wsdl_url` | TEXT | CONDICIONAL | NOT NULL si protocol_type='SOAP' | — | RF-B2-017 línea 21 |
| `openapi_url` | TEXT | CONDICIONAL | NOT NULL si protocol_type='REST' | — | RF-B2-017 línea 21 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.4 `interop_xroad_service_permission`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `service_id` | INTEGER | SÍ | FK → interop_xroad_service.id | FK | RF-B2-017 línea 21 |
| `client_subsystem_id` | INTEGER | SÍ | FK → interop_xroad_subsystem.id (cliente que consume) | FK | RF-B2-017 línea 21 |
| `granted_at` | TIMESTAMP | SÍ | Fecha de otorgamiento del permiso | — | [INFERIDO] |
| `granted_by` | VARCHAR(255) | SÍ | Responsable técnico que otorgó el permiso | — | [INFERIDO] |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |
| `revoked_at` | TIMESTAMP | NO | NULL si vigente | — | [INFERIDO] |

### 2.5 `interop_xroad_transaction`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental; volumen alto | PK | [INFERIDO] |
| `service_id` | INTEGER | SÍ | FK → interop_xroad_service.id | FK | §7 línea 139; UC-B3-011 línea 112 |
| `client_subsystem_id` | INTEGER | SÍ | FK → interop_xroad_subsystem.id | FK | §7 línea 139 |
| `environment_id` | INTEGER | SÍ | FK → interop_server_environment.id | FK | RF-B1-028 línea 20 |
| `transaction_timestamp` | TIMESTAMP | SÍ | Momento del intercambio (UTC) | — | §7 línea 139 |
| `xroad_client_header` | TEXT | SÍ | Encabezado `client` X-Road | — | RF-B2-017 línea 21 |
| `xroad_service_header` | TEXT | SÍ | Encabezado `service` X-Road | — | RF-B2-017 línea 21 |
| `request_hash` | VARCHAR(256) | SÍ | Hash del contenido del mensaje enviado (no el contenido en claro) | — | §7 línea 139; RF-B2-018 línea 22 |
| `digital_signature` | TEXT | SÍ | Firma digital RSA-SHA512 del mensaje | — | §7 línea 139; RF-B2-018 línea 22 |
| `tsa_stamp_token` | BYTEA | CONDICIONAL | Estampa cronológica RFC 3161; NOT NULL cuando estado='COMPLETED' | — | §7 línea 139; RF-B2-018 línea 22; RNF-B3-030 línea 74 |
| `tsa_stamp_at` | TIMESTAMP | CONDICIONAL | Fecha/hora de la estampa TSA | — | §7 línea 139 |
| `status` | VARCHAR(20) | SÍ | CHECK IN ('PENDING','COMPLETED','FAILED','QUEUED','RETRYING') | — | RF-10-D01 línea 60; RN-10-D01 línea 103 |
| `error_code` | VARCHAR(50) | NO | NULL si éxito | — | RF-10-D01 línea 60 |
| `error_detail` | TEXT | NO | Descripción del error (timeout, OCSP vencido, respuesta vacía…) | — | RN-10-D01 línea 103 |
| `retry_count` | SMALLINT | SÍ | DEFAULT 0; reintentos acumulados | — | RF-10-D01 línea 60; RN-10-D01 línea 103 |
| `fallback_activated` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE si se activó carga manual | — | RN-10-D01 línea 103; HU-B2-015 línea 121 |
| `audit_log` | JSONB | SÍ | Log de auditoría completo del intercambio | — | §7 línea 139 |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.6 `interop_certificate`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `environment_id` | INTEGER | SÍ | FK → interop_server_environment.id | FK | §7 línea 140 |
| `cert_type` | VARCHAR(20) | SÍ | CHECK IN ('AUTH','SIGN','TLS') | — | §7 línea 140 |
| `ca_provider` | VARCHAR(50) | SÍ | CHECK IN ('ONAC','GSE','OTHER') | — | §7 línea 140; RF-B2-088 línea 23 |
| `serial_number` | VARCHAR(100) | SÍ | UNIQUE | UNIQUE | [INFERIDO] |
| `subject_dn` | TEXT | SÍ | Distinguished Name del sujeto | — | [INFERIDO] |
| `issued_at` | DATE | SÍ | Fecha de emisión | — | [INFERIDO] |
| `expires_at` | DATE | SÍ | Fecha de vencimiento | — | §7 línea 140; RNF-10-D01 línea 81 |
| `ocsp_status` | VARCHAR(20) | SÍ | CHECK IN ('GOOD','REVOKED','UNKNOWN') | — | §7 línea 140 |
| `ocsp_checked_at` | TIMESTAMP | NO | Última verificación OCSP | — | §7 línea 140; RN-10-D03 línea 105 |
| `alert_days_before` | SMALLINT | SÍ | N días para alerta anticipada de vencimiento | — | RNF-10-D01 línea 81; RN-10-D03 línea 105 |
| `alert_sent_at` | TIMESTAMP | NO | NULL si no se envió aún | — | RN-10-D03 línea 105 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.7 `interop_tsa_config`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `environment_id` | INTEGER | SÍ | FK → interop_server_environment.id | FK | RF-B2-088 línea 23 |
| `provider_name` | VARCHAR(100) | SÍ | Ej. "GSE S.A. TSU 01" | — | §7 línea 143; RF-B2-088 línea 23 |
| `tsa_url` | VARCHAR(255) | SÍ | Ej. "tsa.gse.com.co"; único por ambiente | UNIQUE(environment_id) | §7 línea 143; RF-B2-088 línea 23 |
| `protocol_rfc` | VARCHAR(10) | SÍ | DEFAULT 'RFC3161' | — | RF-B2-018 línea 22; RNF-B3-030 línea 74 |
| `firewall_rule_configured` | BOOLEAN | SÍ | DEFAULT FALSE | — | RF-B2-088 línea 23 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE; solo 1 activo por ambiente | — | RF-B2-088 línea 23 |
| `replaced_provider` | VARCHAR(100) | NO | Proveedor anterior (ej. "Certicámara") | — | RF-B2-088 línea 23; §9 línea 156 |
| `activated_at` | TIMESTAMP | NO | Fecha de activación en el servidor X-Road | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.8 `interop_tsa_queue_item`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `transaction_id` | BIGINT | SÍ | FK → interop_xroad_transaction.id | FK | RF-10-D02 línea 61; RN-10-D02 línea 104 |
| `tsa_config_id` | INTEGER | SÍ | FK → interop_tsa_config.id | FK | RN-10-D02 línea 104 |
| `queued_at` | TIMESTAMP | SÍ | Momento en que se encola | — | RF-10-D02 línea 61 |
| `message_hash` | VARCHAR(256) | SÍ | Hash del mensaje a sellar | — | RN-10-D02 línea 104 |
| `status` | VARCHAR(20) | SÍ | CHECK IN ('PENDING','STAMPED','FAILED') | — | RF-10-D02 línea 61; HU-10-D02 línea 133 |
| `stamped_at` | TIMESTAMP | NO | NULL hasta que se sella | — | RN-10-D02 línea 104 |
| `tsa_stamp_token` | BYTEA | NO | Token RFC 3161 obtenido al sellar | — | RN-10-D02 línea 104 |
| `attempt_count` | SMALLINT | SÍ | DEFAULT 0 | — | [INFERIDO] |

### 2.9 `interop_ccd_service`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `service_key` | VARCHAR(50) | SÍ | CHECK IN ('USER_INFO','ALERTS','TRAMITE_HISTORY','SOLICITUD_HISTORY') | UNIQUE | RF-B2-020 línea 30 |
| `description` | TEXT | SÍ | Descripción funcional del servicio | — | RF-B2-020 línea 30 |
| `endpoint_url` | TEXT | SÍ | URL base del servicio REST | — | RF-B2-020 línea 30 |
| `http_method` | VARCHAR(10) | SÍ | DEFAULT 'GET' per RF-B2-020 | — | RF-B2-020 línea 30 |
| `content_type` | VARCHAR(50) | SÍ | DEFAULT 'application/json' | — | RF-B2-020 línea 30 |
| `param_tipo_id` | BOOLEAN | SÍ | Indica si acepta parámetro tipoId | — | RF-B2-020 línea 30 |
| `param_id_usuario` | BOOLEAN | SÍ | Indica si acepta parámetro idUsuario | — | RF-B2-020 línea 30 |
| `info_classification` | VARCHAR(20) | SÍ | CHECK IN ('PUBLIC','RESERVED','CLASSIFIED'); solo PUBLIC expuesto (Ley 1712) | — | RF-B2-022 línea 32 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |

### 2.10 `interop_ccd_user_data`

> Registro de cada consulta/snapshot de datos personales del ciudadano vía CCD (servicio a). No almacena permanentemente; registra la traza de la consulta.

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `ccd_service_id` | INTEGER | SÍ | FK → interop_ccd_service.id (servicio USER_INFO) | FK | RF-B2-020 línea 30 |
| `tipo_id` | VARCHAR(10) | SÍ | Tipo de identificación del ciudadano | — | §7 línea 141; RF-B2-020 línea 30 |
| `id_usuario` | VARCHAR(50) | SÍ | Identificador del ciudadano | — | §7 línea 141; RF-B2-020 línea 30 |
| `queried_at` | TIMESTAMP | SÍ | Momento de la consulta | — | [INFERIDO] |
| `response_status` | INTEGER | SÍ | Código HTTP de respuesta | — | [INFERIDO] |
| `campo_dato` | JSONB | NO | Mapa campo→valor devuelto (no persistido permanentemente) | — | §7 línea 141 |

### 2.11 `interop_ccd_alert`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `ccd_service_id` | INTEGER | SÍ | FK → interop_ccd_service.id (servicio ALERTS) | FK | RF-B2-020 línea 30 |
| `id_mensaje` | VARCHAR(100) | SÍ | Identificador del mensaje en la CCD | — | §7 línea 141 |
| `tipo_id` | VARCHAR(10) | SÍ | Tipo de identificación del ciudadano destinatario | — | §7 línea 141 |
| `id_usuario` | VARCHAR(50) | SÍ | Identificador del ciudadano destinatario | — | §7 línea 141 |
| `asunto` | VARCHAR(500) | SÍ | Asunto de la alerta | — | §7 línea 141 |
| `texto_mensaje` | TEXT | SÍ | Cuerpo de la comunicación | — | §7 línea 141 |
| `url_descargue_adjuntos` | TEXT | NO | URL para descarga de adjuntos | — | §7 línea 141 |
| `fecha_mensaje` | TIMESTAMP | SÍ | ISO 8601 — fecha/hora de la alerta | — | §7 línea 141 |
| `citizen_authorized` | BOOLEAN | SÍ | El ciudadano autorizó este canal; DEFAULT FALSE | — | RF-B3-117 línea 33 |
| `sent_at` | TIMESTAMP | NO | Momento de envío efectivo | — | [INFERIDO] |

### 2.12 `interop_ccd_tramite_history`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `ccd_service_id` | INTEGER | SÍ | FK → interop_ccd_service.id (servicio TRAMITE_HISTORY) | FK | RF-B2-020 línea 30 |
| `tipo_id` | VARCHAR(10) | SÍ | Tipo de identificación del ciudadano | — | §7 línea 141; RF-B2-020 línea 30 |
| `id_usuario` | VARCHAR(50) | SÍ | Identificador del ciudadano | — | §7 línea 141 |
| `id_tramite` | VARCHAR(100) | SÍ | Identificador del trámite | — | §7 línea 141 |
| `nombre_tramite` | VARCHAR(500) | SÍ | Nombre del trámite | — | §7 línea 141 |
| `fecha_realiza` | TIMESTAMP | SÍ | Fecha en que se realizó el trámite | — | §7 línea 141 |
| `entidades_consultadas` | JSONB | NO | Array de entidades consultadas vía interoperabilidad para ese trámite | — | §7 línea 141 |
| `queried_at` | TIMESTAMP | SÍ | Momento de la consulta de historial | — | [INFERIDO] |

### 2.13 `interop_ccd_solicitud_history`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `ccd_service_id` | INTEGER | SÍ | FK → interop_ccd_service.id (servicio SOLICITUD_HISTORY) | FK | RF-B2-020 línea 30 |
| `tipo_id` | VARCHAR(10) | SÍ | Tipo de identificación del ciudadano | — | §7 línea 141 |
| `id_usuario` | VARCHAR(50) | SÍ | Identificador del ciudadano | — | §7 línea 141 |
| `id_solicitud` | VARCHAR(100) | SÍ | Identificador de la solicitud | — | §7 línea 141 |
| `nombre_solicitud` | VARCHAR(500) | SÍ | Nombre de la solicitud | — | §7 línea 141 |
| `fecha_solicitud` | TIMESTAMP | SÍ | Fecha de la solicitud | — | §7 línea 141 |
| `estado_solicitud` | VARCHAR(100) | SÍ | Estado actual de la solicitud | — | §7 línea 141 |
| `texto_respuesta` | TEXT | NO | Respuesta entregada al ciudadano | — | §7 línea 141 |
| `queried_at` | TIMESTAMP | SÍ | Momento de la consulta de historial | — | [INFERIDO] |

### 2.14 `interop_and_agreement`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `agreement_type` | VARCHAR(50) | SÍ | CHECK IN ('ENTENDIMIENTO','VINCULACION') | — | §7 línea 142; RF-B2-019 línea 24 |
| `entity_name` | VARCHAR(255) | SÍ | Entidad firmante (Alcaldía) | — | §7 línea 142 |
| `and_representative` | VARCHAR(255) | SÍ | Representante de la AND | — | §7 línea 142 |
| `object_description` | TEXT | SÍ | Objeto del acuerdo | — | §7 línea 142 |
| `commitments` | TEXT | SÍ | Compromisos adquiridos | — | §7 línea 142 |
| `signed_at` | DATE | NO | Fecha de firma; NULL si pendiente | — | §7 línea 142; §9 línea 158 |
| `valid_from` | DATE | NO | Fecha de vigencia inicio | — | §7 línea 142 |
| `valid_until` | DATE | NO | Fecha de vigencia fin | — | §7 línea 142 |
| `status` | VARCHAR(20) | SÍ | CHECK IN ('PENDING','ACTIVE','EXPIRED') | — | §9 línea 158 |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.15 `interop_external_system`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `system_key` | VARCHAR(50) | SÍ | CHECK IN ('SUIT','SIGEP','SECOP_I','SECOP_II','SGDEA','REGISTRADURIA_ANI','REGISTRADURIA_SIRC','REGISTRADURIA_ABIS','RUNT','RUAF','RUT','ONAC','GSE','AND'); UNIQUE | UNIQUE | §2.4 líneas 49–52; §8 líneas 147–150 |
| `system_name` | VARCHAR(255) | SÍ | Nombre completo del sistema externo | — | §2.4; §8 |
| `responsible_entity` | VARCHAR(255) | SÍ | Entidad responsable del sistema | — | §8 líneas 147–150 |
| `contact_email` | VARCHAR(255) | NO | Ej. "gobiernodigital@mintic.gov.co" | — | §8 línea 147 |
| `integration_type` | VARCHAR(20) | SÍ | CHECK IN ('XROAD','DIRECT','API_REST','API_SOAP') | — | §2.4; §8 |
| `module_reference` | VARCHAR(10) | NO | Módulo propio que gestiona la integración (ej. "12" para SGDEA) | — | §2.4 líneas 50–52 |
| `base_url` | TEXT | NO | URL base del servicio | — | RF-B1-072 línea 49 |
| `is_integrated` | BOOLEAN | SÍ | DEFAULT FALSE — greenfield; ninguno integrado aún | — | §9 línea 158 |
| `integration_notes` | TEXT | NO | Notas adicionales (ej. "ver módulo 02") | — | §2.4 |

### 2.16 `interop_requirement_mapping`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `tramite_suit_code` | VARCHAR(50) | SÍ | Código del trámite en SUIT (ej. T{código}) | — | RF-B1-072 línea 49; RF-B3-114 línea 41 |
| `requirement_name` | VARCHAR(500) | SÍ | Nombre del requisito que se elimina del ciudadano | — | RF-B2-094 línea 39 |
| `external_system_id` | INTEGER | SÍ | FK → interop_external_system.id; sistema que provee el dato | FK | RF-B2-094 línea 39; RF-B3-113 línea 40 |
| `verification_mode` | VARCHAR(20) | SÍ | CHECK IN ('XROAD','DIRECT_ONLINE','MANUAL_FALLBACK') | — | RF-B3-115 línea 42; RN-B2-004 línea 88 |
| `suit_updated_at` | DATE | NO | Fecha en que se actualizó la ficha SUIT | — | RF-B3-114 línea 41; RN-B2-005 línea 89 |
| `suit_updated_by` | VARCHAR(255) | NO | Responsable de la actualización en SUIT | — | [INFERIDO] |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.17 `interop_server_environment`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `env_type` | VARCHAR(20) | SÍ | CHECK IN ('QA','PREPROD','PROD'); UNIQUE | UNIQUE | RF-B1-028 línea 20 |
| `cpu_count` | SMALLINT | SÍ | QA=1, Preprod=2, Prod=4 | — | RF-B1-028 línea 20 |
| `ram_gb` | SMALLINT | SÍ | QA=4, Preprod=6, Prod=16 | — | RF-B1-028 línea 20 |
| `storage_gb` | SMALLINT | NO | Prod=20 | — | RF-B1-028 línea 20 |
| `ha_mode` | BOOLEAN | SÍ | TRUE solo en Prod (round-robin) | — | RF-B1-028 línea 20 |
| `ha_level` | VARCHAR(5) | NO | CHECK IN ('K2','K3'); Prod mínimo K2 preferido K3 | — | RNF-B2-014 línea 73 |
| `os_image` | VARCHAR(100) | NO | [AMBIGUO] Ubuntu 18.04/RHEL7 citados pero obsoletos (§9 línea 155) | — | §9 línea 155; UC-B2-010 línea 111 |
| `docker_standalone` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE solo permitido en QA/dev (RN-B2-021) | — | RN-B2-021 línea 92; HU-B2-013 línea 122 |
| `and_anchored` | BOOLEAN | SÍ | Indica si fue anclado al servidor central AND | — | RF-B1-028 línea 20 |
| `lci_certified` | BOOLEAN | SÍ | FALSE hasta certificar nivel 3 LCI | — | RF-B2-019 línea 24 |
| `status` | VARCHAR(20) | SÍ | CHECK IN ('PENDING','CONFIGURED','ACTIVE','INACTIVE') | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.18 `interop_error_log`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `transaction_id` | BIGINT | NO | FK → interop_xroad_transaction.id; NULL si error previo al registro | FK | RF-10-D01 línea 60; RN-10-D01 línea 103 |
| `environment_id` | INTEGER | SÍ | FK → interop_server_environment.id | FK | RF-10-D01 línea 60 |
| `error_type` | VARCHAR(30) | SÍ | CHECK IN ('TIMEOUT','EMPTY_RESPONSE','OCSP_EXPIRED','CERT_EXPIRED','TSA_UNAVAILABLE','OTHER') | — | RF-10-D01 línea 60; RN-10-D01 línea 103; HU-10-D01 línea 132 |
| `error_detail` | TEXT | SÍ | Descripción detallada | — | RN-10-D01 línea 103 |
| `retry_attempt` | SMALLINT | SÍ | Número de intento (0=primer error) | — | RF-10-D01 línea 60 |
| `fallback_activated` | BOOLEAN | SÍ | DEFAULT FALSE | — | RN-10-D01 línea 103 |
| `resolved_at` | TIMESTAMP | NO | NULL si sin resolver | — | [INFERIDO] |
| `occurred_at` | TIMESTAMP | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.19 `interop_lci_certification`

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `id` | SERIAL | SÍ | PK autoincremental | PK | [INFERIDO] |
| `environment_id` | INTEGER | SÍ | FK → interop_server_environment.id | FK | RF-B2-019 línea 24 |
| `lci_level` | SMALLINT | SÍ | CHECK IN (1,2,3); nivel requerido=3 | — | RF-B2-019 línea 24; RN-B2-018 línea 91; RNF-B1-034 línea 71 |
| `certified_at` | DATE | NO | NULL si pendiente | — | RF-B2-019 línea 24 |
| `certified_by` | VARCHAR(255) | NO | Entidad certificadora | — | [INFERIDO] |
| `certificate_ref` | TEXT | NO | Referencia/número del certificado LCI | — | [INFERIDO] |
| `domains_covered` | VARCHAR[] | SÍ | Array: ['POLITICAL_LEGAL','ORGANIZATIONAL','SEMANTIC','TECHNICAL'] | — | RNF-B1-034 línea 71 |
| `status` | VARCHAR(20) | SÍ | CHECK IN ('PENDING','CERTIFIED','EXPIRED') | — | [INFERIDO] |

---

## 3. Reglas de negocio con impacto en datos

| ID | Regla | Impacto en datos | Fuente |
|----|-------|-----------------|--------|
| RN-B1-020 / RN-B3-013 | SCD (Interoperabilidad, Autenticación, Carpeta) de uso obligatorio y gratuito; no implementar mecanismos propios. | Ninguna tabla puede registrar infraestructura alternativa paralela a la AND. | Decreto 1078/2015 Art.2.2.17; línea 87 |
| RN-B2-004 / RN-B3-008 | No exigir documentos disponibles vía interoperabilidad. Excepción: falla técnica documentada o registro no integrado. | `interop_xroad_transaction.fallback_activated=TRUE` + `interop_error_log` deben registrar la falla técnica como evidencia de la excepción. | Decreto 2106 Art.10; línea 88 |
| RN-B2-005 / RN-B3-009 | Requisitos verificables por interoperabilidad se actualizan en SUIT. | `interop_requirement_mapping.suit_updated_at` no puede ser NULL para requisitos activos integrados. | Decreto 2106 Art.10 par.2; línea 89 |
| RN-B2-016 / RN-B3-014 | Interoperabilidad la presta exclusivamente la AND; sin infraestructura paralela. | `interop_server_environment.docker_standalone=TRUE` solo en QA/dev (CHECK: docker_standalone=TRUE → env_type='QA'). | Decreto 620; línea 90; RN-B2-021 línea 92 |
| RN-B2-018 / RN-B3-029 | Paso a producción requiere nivel 3 LCI certificado. | `interop_server_environment.lci_certified=TRUE` es prerrequisito para que `env_type='PROD'` pase a `status='ACTIVE'`. | Marco de Interoperabilidad; línea 91 |
| RN-B3-032 | `memberCode` = "sigla_entidad-código_SIGEP". | `interop_xroad_member.member_code` debe validar ese formato (CHECK con regex). | Marco de Interoperabilidad; línea 93 |
| RN-B2-025 | Vinculación a CCD secuencial: Análisis → Ejecución. | `interop_and_agreement.status` debe progresar ordenadamente. | Ficha Vinculación CCD v6; línea 94 |
| RN-B3-011 | Registraduría provee interoperabilidad de identificación gratuitamente. | `interop_external_system` donde system_key='REGISTRADURIA_ANI' → `integration_type` sin costo registrado. | Decreto 2106 Art.13; línea 95 |
| RN-10-D01 | Fallo X-Road → reintento configurable + error controlado + log + fallback; NUNCA estado indefinido. | `interop_xroad_transaction.status` no puede quedar sin valor final; `interop_error_log` registra cada intento. | Marco Interoperabilidad; línea 103 |
| RN-10-D02 | Mensajes pendientes de sello TSA se encolan; ninguno se cierra sin estampa RFC 3161. | `interop_xroad_transaction.tsa_stamp_token` NOT NULL en status='COMPLETED'; `interop_tsa_queue_item` gestiona la cola. | RFC 3161; Decreto 2106; línea 104 |
| RN-10-D03 | Alerta N días antes del vencimiento de certificados; certificado vencido en producción = incidente. | `interop_certificate.alert_days_before` requerido; `alert_sent_at` rastreable; proceso trigger/job sobre `expires_at`. | Marco Interoperabilidad; línea 105 |
| RF-B2-021 | CCD no almacena datos permanentes del ciudadano. | `interop_ccd_user_data.campo_dato` es trazabilidad de consulta, no réplica permanente. Solo se registra el hecho del acceso. | #124; línea 31 |
| RF-B2-022 | Solo exponer datos no reservados/clasificados (Ley 1712/2014). | `interop_ccd_service.info_classification` CHECK ('PUBLIC'); solo servicios PUBLIC pueden ser expuestos a CCD. | Ley 1712/2014; línea 32 |
| RNF-B3-030 | 100% de mensajes con estampa TSA (RFC 3161) y firma digital. | `interop_xroad_transaction.digital_signature` y `tsa_stamp_token` NOT NULL en estado final exitoso. | #119,#140; línea 74 |

---

## 4. Cardinalidades y relaciones

| Relación | Cardinalidad | Descripción | Fuente |
|----------|-------------|-------------|--------|
| `interop_xroad_member` 1 → N `interop_xroad_subsystem` | Un miembro tiene uno o más subsistemas | Cada sistema de información = un subsistema | RF-B2-017 línea 21 |
| `interop_xroad_subsystem` 1 → N `interop_xroad_service` | Un subsistema expone uno o más servicios | SOAP o REST con versiones | RF-B2-017 línea 21 |
| `interop_xroad_service` 1 → N `interop_xroad_service_permission` | Un servicio tiene N permisos a distintos clientes | Gestión de acceso por cliente | RF-B2-017 línea 21 |
| `interop_xroad_subsystem` 1 → N `interop_xroad_service_permission` (como cliente) | Un subsistema puede ser cliente de múltiples servicios | Rol consumidor | RF-B2-017 línea 21 |
| `interop_xroad_service` 1 → N `interop_xroad_transaction` | Un servicio tiene N transacciones | Log histórico | UC-B3-011 línea 112 |
| `interop_xroad_subsystem` 1 → N `interop_xroad_transaction` (como cliente) | Un subsistema cliente genera N transacciones | Trazabilidad | §7 línea 139 |
| `interop_server_environment` 1 → N `interop_xroad_transaction` | Cada ambiente registra sus propias transacciones | QA/Preprod/Prod separados | RF-B1-028 línea 20 |
| `interop_server_environment` 1 → N `interop_certificate` | Cada ambiente tiene N certificados (AUTH, SIGN, TLS) | §7 línea 140 | §7 línea 140 |
| `interop_server_environment` 1 → 1 `interop_tsa_config` (activo) | Solo un proveedor TSA activo por ambiente | UNIQUE(environment_id) donde is_active=TRUE | RF-B2-088 línea 23 |
| `interop_server_environment` 1 → 1 `interop_lci_certification` | Un ambiente tiene su propia certificación LCI | RF-B2-019 línea 24 | RF-B2-019 |
| `interop_xroad_transaction` 1 → 0..1 `interop_tsa_queue_item` | Una transacción puede tener un ítem en cola TSA | Solo mientras pendiente de sello | RN-10-D02 línea 104 |
| `interop_tsa_config` 1 → N `interop_tsa_queue_item` | Una config TSA gestiona la cola de su ambiente | RN-10-D02 línea 104 | RN-10-D02 |
| `interop_xroad_transaction` 1 → N `interop_error_log` | Una transacción puede generar múltiples entradas de error (reintentos) | RF-10-D01 línea 60 | RF-10-D01 |
| `interop_server_environment` 1 → N `interop_error_log` | Errores agrupables por ambiente | RF-10-D01 línea 60 | RF-10-D01 |
| `interop_ccd_service` 1 → N `interop_ccd_user_data` | El servicio USER_INFO tiene N consultas | [INFERIDO] | RF-B2-020 |
| `interop_ccd_service` 1 → N `interop_ccd_alert` | El servicio ALERTS tiene N alertas enviadas | §7 línea 141 | RF-B3-117 |
| `interop_ccd_service` 1 → N `interop_ccd_tramite_history` | El servicio TRAMITE_HISTORY tiene N registros | §7 línea 141 | RF-B2-020 |
| `interop_ccd_service` 1 → N `interop_ccd_solicitud_history` | El servicio SOLICITUD_HISTORY tiene N registros | §7 línea 141 | RF-B2-020 |
| `interop_requirement_mapping` N → 1 `interop_external_system` | Múltiples requisitos verificados por un mismo sistema externo | RF-B2-094 línea 39 | RF-B2-094 |
| `interop_xroad_member` 1 → N `interop_and_agreement` [INFERIDO] | Un miembro puede tener N acuerdos históricos con la AND | [INFERIDO] | §7 línea 142 |

---

## 5. Jerarquías ISA / polimorfismo

### 5.1 No hay jerarquía ISA formal declarada

El documento no define explícitamente subtipos de entidades con herencia estructural formal. Sin embargo hay dos patrones de especialización implícita:

**5.1.1 `interop_xroad_member` con rol doble [INFERIDO]**
El mismo modelo de miembro representa tanto la propia Alcaldía (propietario del servidor de seguridad) como entidades externas (Registraduría, RUNT, etc.). El discriminador es `is_own_entity`. No justifica tabla separada dado que los atributos son idénticos; estrategia: tabla única con discriminador (single-table).

**5.1.2 `interop_xroad_service_permission.client_subsystem_id` como referencia polimórfica limitada [INFERIDO]**
El cliente puede ser un subsistema propio o externo. Se resuelve sin polimorfismo real: `client_subsystem_id` apunta a `interop_xroad_subsystem.id` en todos los casos, ya que tanto subsistemas propios como ajenos se registran en la misma tabla con `member_id` como discriminador.

### 5.2 No se detectan asociaciones polimórficas que requieran arco exclusivo

Las FK del módulo son homogéneas y apuntan a tablas concretas. No hay patrón `(entity_type, entity_id)` en la documentación.

---

## 6. Fuera de BD (infraestructura) — justificado

| Elemento | Por qué no va a BD relacional | Fuente |
|----------|------------------------------|--------|
| Servidor de seguridad X-Road (instalación física/VM) | Es infraestructura de red/SO; se gestiona por IaC/CM, no por filas relacionales. Los metadatos del ambiente SÍ van a `interop_server_environment`. | RF-B1-028 línea 20 |
| Configuración de anclaje XML de la AND (3 entornos) | Archivos de configuración binarios/XML generados por la AND; se almacenan en repositorio de configuración o secrets manager, no en BD. | §9 línea 157 |
| Imagen Docker `niis/xroad-security-server-standalone:bionic-6.21.0` | Artefacto de contenedor; gestionado por registro de contenedores. Solo el flag `docker_standalone` en `interop_server_environment` registra su uso autorizado. | HU-B2-013 línea 122; RN-B2-021 línea 92 |
| Script `install_X-ROAD_seguridad_v4.sh` | Script de instalación; versionado en repositorio de código. | UC-B2-010 línea 111 |
| Regla de firewall hacia `tsa.gse.com.co` | Configuración de red gestionada por WAF/firewall; la existencia de la regla se referencia como campo booleano `firewall_rule_configured` en `interop_tsa_config`. | RF-B2-088 línea 23 |
| Mensajes SOAP/REST completos en tránsito | Contenido en claro no se persiste; solo hash + firma + token TSA. Datos personales del ciudadano no se almacenan permanentemente per RF-B2-021. | §7 línea 139; RF-B2-021 línea 31 |
| Portal GOV.CO / CCD (plataforma MinTIC) | Sistema externo propiedad de MinTIC; la Alcaldía solo expone servicios, no gestiona el portal. Se cataloga en `interop_external_system`. | RF-B2-021 línea 31 |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Justificación | Entidad/campo afectado |
|---|-----------|---------------|----------------------|
| I-01 | `interop_xroad_member.is_own_entity` para diferenciar la Alcaldía de entidades externas | El documento trata a la Alcaldía y a sus contrapartes (Registraduría, RUNT…) como miembros X-Road; se necesita discriminar cuál es el propio | `interop_xroad_member.is_own_entity` |
| I-02 | Tablas CCD registran la traza de la consulta, no datos permanentes del ciudadano | RF-B2-021 prohíbe almacenamiento permanente; se necesita traza para auditoría de acceso | `interop_ccd_user_data.queried_at`, `interop_ccd_tramite_history.queried_at`, `interop_ccd_solicitud_history.queried_at` |
| I-03 | `interop_xroad_transaction` usa BIGSERIAL por volumen alto | Cada intercambio genera un registro; en producción con múltiples sistemas el volumen es alto | PK tipo `BIGSERIAL` en lugar de `SERIAL` |
| I-04 | `interop_tsa_queue_item.attempt_count` para reintentos de sellado | Principio simétrico con `interop_xroad_transaction.retry_count`; necesario para decidir escalar o descartar | `interop_tsa_queue_item.attempt_count` |
| I-05 | `interop_error_log` desacoplado de `interop_xroad_transaction` para errores pre-registro | Un error puede ocurrir antes de que la transacción sea registrada (ej. falla de conexión al servidor de seguridad) | `interop_error_log.transaction_id` nullable |
| I-06 | `interop_lci_certification.domains_covered` como array | RNF-B1-034 cita explícitamente 4 dominios (político-legal, organizacional, semántico, técnico); el nivel 3 puede cubrir subconjuntos | `interop_lci_certification.domains_covered VARCHAR[]` |
| I-07 | Relación 1:N entre `interop_xroad_member` y `interop_and_agreement` | Una entidad puede renovar o tener múltiples acuerdos históricos con la AND | `interop_and_agreement` sin FK a member explícita en doc; se agrega `member_id` FK |
| I-08 | `interop_certificate.alert_days_before` configurable por certificado | RN-10-D03 habla de "N días" sin fijar N; el valor debe ser configurable por tipo/ambiente, no hardcodeado | `interop_certificate.alert_days_before SMALLINT` |

---

## 8. Vacíos (información esperada no presente)

| Vacío | Por qué importa | Dónde investigar |
|-------|----------------|------------------|
| Valor concreto de N (reintentos X-Road) y timeout en segundos | RN-10-D01 y RF-10-D01 mencionan "N reintentos" y "N segundos" sin valor fijo; necesario para `retry_count` CHECK y lógica de fallback | §2.D Delta; documento de arquitectura técnica |
| Valor concreto de N para alerta de vencimiento de certificados | RNF-10-D01 y RN-10-D03 dicen "N días" sin especificar; necesario para `alert_days_before` DEFAULT | §3.D Delta; política de seguridad |
| `memberClass` vigente en producción: "CO" vs "GOB/PRIV" | §9 línea 154 lo marca como ambigüedad abierta; el campo `member_class` quedará pendiente de validar con la AND | Consulta directa a la AND |
| SO del servidor de seguridad (Ubuntu 18.04/RHEL7 obsoletos) | §9 línea 155 lo señala; el campo `os_image` registrará lo real pero hoy es desconocido | Guía actualizada AND 2025+ |
| Anclajes XML de configuración (3 entornos) | §9 línea 157 indica que no se publican; necesarios para completar `interop_server_environment` | Solicitud formal a la AND |
| Contrato/convenio formal con la AND | §9 línea 158 confirma que no existe aún (greenfield); es prerrequisito bloqueante | Proceso legal/contractual Alcaldía |
| Integración real con sistemas externos | §9 línea 158: ninguno integrado aún (greenfield); `interop_external_system.is_integrated=FALSE` para todos | Fases futuras del proyecto |
| Capacidad de cola TSA: TTL y máx. ítems en `interop_tsa_queue_item` | RN-10-D02 define el comportamiento pero no los límites operativos | Definición de arquitectura técnica |

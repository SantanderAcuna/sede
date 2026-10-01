# Extracción BD — Módulo 09 Seguridad

## 0. Cobertura

- Unidad: `/var/www/proyect-doc/elicitacion/sede-electronica/09-seguridad/seguridad.md`
- Líneas leídas: 1–183
- **Íntegra: SÍ; 0 líneas omitidas**
- Secciones cubiertas: §1 Descripción/alcance, §2 RF (2.1–2.4 + 2.D Delta), §3 RNF (+ 3.D Delta), §4 RN (+ 4.D Delta), §5 UC, §6 HU (+ 6.D Delta), §7 Datos/Entidades, §8 Integraciones, §9 Ambigüedades

---

## 1. Entidades candidatas

| # | Nombre entidad | Descripción | CANDIDATA-COMPARTIDA | Fuente |
|---|---------------|-------------|----------------------|--------|
| 1 | `security_user` (usuario interno) | Empleado/administrador con acceso al back-office/CMS; tiene rol, permisos, historial auditoría | **SÍ** — compartida con módulos de autenticación/CMS | seguridad.md:161; RF-B3-074; UC-B1-010 |
| 2 | `citizen_user` (usuario ciudadano/SCD) | Ciudadano registrado con nivel de confianza OIDC; puede ser externo federado vía SCD o registrado localmente | **SÍ** — compartida con módulos de trámites, PQRSD, participación | seguridad.md:162; RF-B1-025; UC-B3-004 |
| 3 | `user_session` (sesión) | Sesión activa de cualquier usuario (interno o ciudadano); registra token CSRF, timeout, IP de origen | **SÍ** — compartida; toda sesión autenticada la necesita | RF-B1-057; RF-B1-064; RNF-B1-023 |
| 4 | `mfa_enrollment` (enrolamiento MFA) | Segundo factor configurado para un usuario interno; método, secreto, estado activo | **SÍ** — compartida con autenticación | RN-09-D04; HU-09-D05; RF-B1-025 |
| 5 | `oidc_token` (token OIDC) | Tokens emitidos por el SCD para ciudadanos: id_token, access_token, refresh_token, authorization_code | No compartida — específica de integración SCD | seguridad.md:163; UC-B2-005; RF-B1-025 |
| 6 | `security_audit_log` (log de auditoría) | Registro append-only de eventos de seguridad: accesos, cambios CMS, intentos fallidos, CSRF detectados | **SÍ** — compartida; todos los módulos escriben en ella | seguridad.md:164; RF-B1-065; RN-09-D05; HU-09-D06 |
| 7 | `consent_log` (log de consentimiento) | Evidencia de consentimiento del ciudadano: timestamp (Hora Legal), IP, versión de política, hash del documento | **SÍ** — compartida con ARCO y módulos que capturan datos | seguridad.md:164; RF-B2-050; HU-B2-014; RN-B1-013 |
| 8 | `security_incident` (incidente de seguridad) | Incidente detectado: clasificación, fecha, impacto, acciones, estado de reporte al CSIRT | No compartida — específica de seguridad | seguridad.md:165; RF-B1-066; UC-B1-012; RN-B1-016 |
| 9 | `incident_action` (acción sobre incidente) | Acciones tomadas durante la gestión de un incidente: bloqueo IP, parches, restauración, informe post-incidente | No compartida — detalle de incidente | UC-B1-012; RF-B1-066 |
| 10 | `password_reset_token` (token recuperación) | Token de un solo uso para flujo "Olvidé mi contraseña"; expira en 15 min; invalida sesiones al usar | No compartida — autenticación interna | RF-09-D02; RN-09-D03; HU-09-D02 |
| 11 | `arco_request` (solicitud ARCO) | Solicitud de derechos Acceso/Rectificación/Cancelación/Oposición presentada por un ciudadano | **SÍ** — compartida con módulo de protección de datos | RF-B2-047; UC-B2-007; RN-B1-013 |
| 12 | `privacy_policy_version` (versión política de privacidad) | Versión vigente de la política de tratamiento de datos; referenciada por consent_log | **SÍ** — compartida con ARCO y gestión de contenidos | RF-B2-090; HU-B3-023; RN-B2-008 |
| 13 | `login_attempt` (intento de acceso) | Registro de cada intento de autenticación: usuario, IP, timestamp, resultado (éxito/fallo), contador de fallos | **SÍ** — compartida; necesaria para bloqueo y auditoría | RF-B1-062; HU-B1-020; UC-B1-010 |
| 14 | `backup_log` (log de backups) | Registro de ejecución de backups: tipo (completo/incremental), resultado, verificación de integridad, timestamp | No compartida — operaciones de backup | RF-B1-067 |
| 15 | `sensitive_data_category` (categoría de dato sensible) | Catálogo de tipos de datos sensibles: salud, biometría, origen étnico, orientación política/sexual, religión, sindicatos | **SÍ** — compartida; referenciada por formularios y consentimiento | seguridad.md:166; RF-B2-048; Ley 1581 Art.5 |

---

## 2. Atributos/campos por entidad

### 2.1 `security_user` — Usuario interno del sistema

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `user_id` | UUID | SÍ | Generado; único global | PK | seguridad.md:161; #239 |
| `document_type` | VARCHAR(10) | SÍ | CC, CE, TI, PEP, NIT | — | RF-B3-074; #118 |
| `document_number` | VARCHAR(20) | SÍ | UNIQUE per document_type | UNIQUE | RF-B3-074 |
| `full_name` | VARCHAR(200) | SÍ | — | — | seguridad.md:161 |
| `email` | VARCHAR(255) | SÍ | Formato RFC 5321; UNIQUE | UNIQUE | RF-B3-074 |
| `password_hash` | VARCHAR(255) | SÍ | Bcrypt/Argon2; nunca texto plano | — | RF-B3-074; RNF-B1-021 |
| `password_min_length` | INTEGER | [INFERIDO] | CHECK ≥ 8 (usuarios) | — | RNF-B1-021; #156 |
| `role_id` | UUID | SÍ | FK a tabla de roles | FK → role | seguridad.md:161; RNF-B2-010 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | RNF-B2-011 |
| `failed_login_count` | INTEGER | SÍ | DEFAULT 0; CHECK ≥ 0 | — | RF-B1-062; HU-B1-020 |
| `locked_until` | TIMESTAMPTZ | NO | NULL si no bloqueado; SET cuando fallos ≥ 5 | — | RF-B1-062; HU-B1-020 |
| `mfa_required` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE para admin/editor/aprobador | — | RN-09-D04; HU-09-D05 |
| `deactivated_at` | TIMESTAMPTZ | NO | NULL si activo; fecha de baja para revocación ≤1 día hábil | — | RNF-B2-011 |
| `created_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW(); Hora Legal Colombiana | — | RNF-B1-024 |
| `updated_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | [INFERIDO] |

### 2.2 `citizen_user` — Usuario ciudadano (SCD/local)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `citizen_id` | UUID | SÍ | Generado; único global | PK | seguridad.md:162 |
| `document_type` | VARCHAR(10) | SÍ | CC, CE, TI, PEP, NIT | — | RF-B3-074; #118 |
| `document_number` | VARCHAR(20) | SÍ | UNIQUE per document_type | UNIQUE | RF-B3-074 |
| `full_name` | VARCHAR(200) | SÍ | Validado contra ANI | — | RF-B3-106; UC-B3-004 |
| `email` | VARCHAR(255) | SÍ | UNIQUE; usada también como login | UNIQUE | RF-B3-074; UC-B3-004 |
| `phone` | VARCHAR(30) | NO | Teléfono del ciudadano | — | seguridad.md:162 |
| `address` | VARCHAR(500) | NO | Dirección del ciudadano | — | seguridad.md:162 |
| `trust_level` | VARCHAR(20) | SÍ | CHECK IN ('bajo','medio','alto','muy_alto') | — | RF-B1-025; #124 |
| `auth_source` | VARCHAR(20) | SÍ | CHECK IN ('local','scd_oidc') — distingue registro local vs federado | — | RF-B1-025; UC-B3-004 |
| `scd_sub` | VARCHAR(255) | NO | Subject claim del token SCD; UNIQUE cuando auth_source='scd_oidc' | UNIQUE (parcial) | RF-B1-025; #124 |
| `biometric_enrolled` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE solo en nivel alto/muy_alto | — | seguridad.md:162; RF-B1-025 |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | RF-B1-025 |
| `ani_validated` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE tras validación ANI | — | RF-B3-106 |
| `ani_validated_at` | TIMESTAMPTZ | NO | NULL si no validado | — | RF-B3-106 |
| `is_minor` | BOOLEAN | SÍ | DEFAULT FALSE; CHECK basado en edad | — | RN-B2-007; Ley 1581 Art.7 |
| `legal_guardian_consent` | BOOLEAN | NO | Requerido si is_minor=TRUE | — | RN-B2-007 |
| `failed_login_count` | INTEGER | SÍ | DEFAULT 0; CHECK ≥ 0 | — | RF-B1-062 |
| `locked_until` | TIMESTAMPTZ | NO | NULL si no bloqueado | — | RF-B1-062 |
| `created_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | RNF-B1-024 |
| `updated_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | [INFERIDO] |

### 2.3 `user_session` — Sesión activa

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `session_id` | UUID | SÍ | Generado; único global | PK | RF-B1-057; HU-B2-014 |
| `user_type` | VARCHAR(20) | SÍ | CHECK IN ('internal','citizen') — discriminador | — | [INFERIDO] de UC-B1-010 y UC-B2-005 |
| `internal_user_id` | UUID | NO | NULL si ciudadano | FK → security_user.user_id | RF-B1-057 |
| `citizen_id` | UUID | NO | NULL si usuario interno | FK → citizen_user.citizen_id | RF-B1-025 |
| `csrf_token` | VARCHAR(128) | SÍ | Único por sesión; usado para validación anti-CSRF | — | RF-B1-064 |
| `ip_address` | INET | SÍ | IP de origen de la sesión | — | RF-B1-065; HU-B2-014 |
| `user_agent` | TEXT | NO | Cadena User-Agent del cliente | — | [INFERIDO] |
| `oidc_token_id` | UUID | NO | FK a oidc_token si sesión federada | FK → oidc_token.token_id | RF-B1-025 |
| `created_at` | TIMESTAMPTZ | SÍ | Inicio de sesión; Hora Legal Colombiana | — | RNF-B1-023; RNF-B1-024 |
| `expires_at` | TIMESTAMPTZ | SÍ | created_at + 900 s (15 min) | — | RNF-B1-023; RF-B1-057; #156 |
| `last_activity_at` | TIMESTAMPTZ | SÍ | Actualizado en cada request | — | [INFERIDO] de RNF-B1-023 |
| `invalidated_at` | TIMESTAMPTZ | NO | NULL si activa; SET al cerrar/revocar | — | RF-B1-025 (SLO/revocación) |
| `invalidation_reason` | VARCHAR(50) | NO | 'logout','password_reset','token_revoked','timeout' | — | RN-09-D03; RF-B1-025 |

### 2.4 `mfa_enrollment` — Enrolamiento MFA

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `enrollment_id` | UUID | SÍ | Generado | PK | RN-09-D04; HU-09-D05 |
| `user_id` | UUID | SÍ | FK a security_user | FK → security_user.user_id | RN-09-D04 |
| `method` | VARCHAR(30) | SÍ | CHECK IN ('totp','sms_otp','email_otp','hardware_key') | — | RF-B1-025; #124 [AMBIGUO — el doc no lista métodos explícitos para internos, se infiere de niveles SCD] |
| `secret_encrypted` | TEXT | SÍ | Cifrado AES-256 en reposo | — | RNF-B2-009; [INFERIDO] |
| `is_active` | BOOLEAN | SÍ | DEFAULT TRUE | — | RN-09-D04 |
| `enrolled_at` | TIMESTAMPTZ | SÍ | Fecha de enrolamiento | — | RN-09-D04 |
| `last_used_at` | TIMESTAMPTZ | NO | Última verificación MFA exitosa | — | [INFERIDO] |

### 2.5 `oidc_token` — Token OIDC (SCD)

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `token_id` | UUID | SÍ | Generado | PK | seguridad.md:163 |
| `citizen_id` | UUID | SÍ | FK a citizen_user | FK → citizen_user.citizen_id | RF-B1-025 |
| `authorization_code` | VARCHAR(512) | NO | Código intercambiado; borrar tras canje | — | seguridad.md:163; UC-B2-005 |
| `id_token` | TEXT | SÍ | JWT firmado por SCD | — | seguridad.md:163; #124 |
| `access_token` | TEXT | SÍ | Token de acceso al recurso | — | seguridad.md:163 |
| `refresh_token` | TEXT | NO | Para renovación; puede estar ausente | — | seguridad.md:163 |
| `client_id` | VARCHAR(255) | SÍ | Identificador de la aplicación en el SCD | — | seguridad.md:163 |
| `client_secret_hash` | VARCHAR(255) | SÍ | Nunca en claro; hash; configuración | — | seguridad.md:163; [INFERIDO] |
| `state` | VARCHAR(256) | SÍ | Parámetro anti-CSRF del flujo OIDC; validado en callback | — | HU-B3-021; RF-09-D04 |
| `nonce` | VARCHAR(256) | SÍ | Previene replay attacks | — | [INFERIDO] de buenas prácticas OIDC |
| `trust_level` | VARCHAR(20) | SÍ | Nivel de confianza al emitir el token | — | RF-B1-025 |
| `issued_at` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana | — | RNF-B1-024 |
| `expires_at` | TIMESTAMPTZ | SÍ | Expiración del access_token | — | RF-B1-025 |
| `revoked_at` | TIMESTAMPTZ | NO | NULL si vigente; SET al dar de baja | — | RF-B1-025 (revocación inmediata) |

### 2.6 `security_audit_log` — Log de auditoría de seguridad

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `log_id` | UUID | SÍ | Generado; IMMUTABLE | PK | seguridad.md:164; RN-09-D05 |
| `event_type` | VARCHAR(80) | SÍ | Ej.: 'login_success','login_fail','csrf_detected','content_edit','token_revoked','idor_attempt','admin_action' | — | RF-B1-065; HU-09-D06 |
| `actor_type` | VARCHAR(20) | SÍ | CHECK IN ('internal_user','citizen','system','anonymous') | — | seguridad.md:164 |
| `internal_user_id` | UUID | NO | NULL si actor es ciudadano/sistema/anónimo | FK → security_user.user_id | RF-B1-065 |
| `citizen_id` | UUID | NO | NULL si actor es usuario interno/sistema | FK → citizen_user.citizen_id | RF-B1-065 |
| `session_id` | UUID | NO | FK a user_session si hay sesión activa | FK → user_session.session_id | HU-B2-014 |
| `ip_address` | INET | SÍ | IP desde donde ocurrió el evento | — | RF-B1-065; #156 |
| `action` | VARCHAR(255) | SÍ | Descripción de la acción realizada | — | RF-B1-065 |
| `resource_type` | VARCHAR(80) | NO | Tipo del recurso afectado (ej.: 'norma','pqrsd','usuario') | — | RF-B1-065 |
| `resource_id` | VARCHAR(255) | NO | ID del recurso afectado | — | RF-B1-065; HU-B2-014 |
| `result` | VARCHAR(20) | SÍ | CHECK IN ('success','failure','blocked','rejected') | — | RF-B1-065 |
| `detail` | JSONB | NO | Detalle adicional (campos modificados, motivo de rechazo, etc.) | — | [INFERIDO] |
| `previous_hash` | VARCHAR(64) | SÍ | Hash SHA-256 del log_id anterior (encadenamiento) | — | RN-09-D05; RNF-09-D01 |
| `record_hash` | VARCHAR(64) | SÍ | Hash SHA-256 de este registro completo | — | RN-09-D05; RNF-09-D01 |
| `occurred_at` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana (INM); NOT NULL | — | RF-B1-065; RNF-B1-024; HU-B2-014 |

> RESTRICCION CRITICA: ningún UPDATE ni DELETE permitido sobre esta tabla. Implementar via reglas PostgreSQL o RLS. Retención ≥ 5 años. (RN-09-D05; RNF-B1-024)

### 2.7 `consent_log` — Log de consentimiento

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `consent_id` | UUID | SÍ | Generado | PK | RF-B2-050; HU-B2-014 |
| `citizen_id` | UUID | SÍ | FK a citizen_user | FK → citizen_user.citizen_id | RF-B2-050 |
| `policy_version_id` | UUID | SÍ | FK a privacy_policy_version | FK → privacy_policy_version.version_id | RF-B2-050; HU-B3-023 |
| `session_id` | UUID | NO | Sesión en la que se otorgó | FK → user_session.session_id | HU-B2-014 |
| `consent_type` | VARCHAR(30) | SÍ | CHECK IN ('general','sensitive_data','cookies_analytics','cookies_marketing') | — | RF-B2-048; RN-B3-031 |
| `action` | VARCHAR(20) | SÍ | CHECK IN ('granted','revoked','updated') | — | RF-B2-050 |
| `ip_address` | INET | SÍ | IP desde donde se otorgó | — | RF-B2-050; HU-B2-014 |
| `policy_hash` | VARCHAR(64) | SÍ | Hash del documento de política al momento del consentimiento | — | HU-B2-014 |
| `is_sensitive_data` | BOOLEAN | SÍ | TRUE si aplica autorización explícita diferenciada | — | RF-B2-048 |
| `occurred_at` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana (INM); IMMUTABLE | — | RF-B2-050; RN-B1-013; HU-B2-014 |

### 2.8 `security_incident` — Incidente de seguridad

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `incident_id` | UUID | SÍ | Generado | PK | seguridad.md:165; RF-B1-066 |
| `classification` | VARCHAR(20) | SÍ | CHECK IN ('leve','grave','muy_grave') | — | seguridad.md:165; RF-B1-066; RNF-B1-025 |
| `title` | VARCHAR(255) | SÍ | Descripción breve | — | RF-B1-066 |
| `detected_at` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana | — | seguridad.md:165 |
| `detected_by_user_id` | UUID | NO | Usuario interno que detectó el incidente | FK → security_user.user_id | UC-B1-012 |
| `impact_description` | TEXT | SÍ | Descripción del impacto | — | seguridad.md:165; RF-B1-066 |
| `affects_personal_data` | BOOLEAN | SÍ | DEFAULT FALSE; si TRUE → notifica SIC | — | RN-B2-022; UC-B1-012 |
| `csirt_reported_at` | TIMESTAMPTZ | NO | NULL si no reportado; debe ser ≤ 24 h desde detected_at si grave/muy_grave | — | RF-B1-066; RN-B1-016; RNF-B1-025 |
| `sic_notified_at` | TIMESTAMPTZ | NO | NULL si no aplica o no reportado | — | RN-B2-022; UC-B1-012 |
| `status` | VARCHAR(30) | SÍ | CHECK IN ('detected','classifying','mitigating','resolved','reported') | — | UC-B1-012 |
| `resolved_at` | TIMESTAMPTZ | NO | NULL si no resuelto | — | UC-B1-012 |
| `post_incident_report` | TEXT | NO | Informe post-incidente | — | UC-B1-012 |
| `created_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | [INFERIDO] |

### 2.9 `incident_action` — Acciones sobre incidente

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `action_id` | UUID | SÍ | Generado | PK | UC-B1-012 |
| `incident_id` | UUID | SÍ | FK a security_incident | FK → security_incident.incident_id | UC-B1-012 |
| `action_type` | VARCHAR(50) | SÍ | CHECK IN ('ip_block','patch_applied','restore','notification','other') | — | UC-B1-012; RF-B1-066 |
| `description` | TEXT | SÍ | Detalle de la acción tomada | — | UC-B1-012 |
| `performed_by_user_id` | UUID | SÍ | FK a security_user | FK → security_user.user_id | UC-B1-012 |
| `performed_at` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana | — | UC-B1-012; RNF-B1-024 |

### 2.10 `password_reset_token` — Token de recuperación de contraseña

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `token_id` | UUID | SÍ | Generado | PK | RF-09-D02; RN-09-D03 |
| `user_id` | UUID | SÍ | FK a security_user (solo usuarios internos) | FK → security_user.user_id | RF-09-D02; HU-09-D02 |
| `token_hash` | VARCHAR(128) | SÍ | Hash del token enviado; nunca texto plano | — | RN-09-D03 |
| `expires_at` | TIMESTAMPTZ | SÍ | created_at + 15 min | — | RN-09-D03; HU-09-D02 |
| `used_at` | TIMESTAMPTZ | NO | NULL si no usado; SET al usar (un solo uso) | — | RN-09-D03 |
| `created_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | RN-09-D03 |

> REGLA: token válido solo si used_at IS NULL AND expires_at > NOW(). (RN-09-D03)

### 2.11 `arco_request` — Solicitud de derechos ARCO

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `arco_id` | UUID | SÍ | Generado | PK | RF-B2-047; UC-B2-007 |
| `radicado` | VARCHAR(50) | SÍ | UNIQUE; código de radicado generado automáticamente | UNIQUE | RF-B2-047 |
| `citizen_id` | UUID | SÍ | FK a citizen_user; ciudadano que ejerce el derecho | FK → citizen_user.citizen_id | UC-B2-007 |
| `arco_type` | VARCHAR(20) | SÍ | CHECK IN ('acceso','rectificacion','cancelacion','oposicion') | — | RF-B2-047; UC-B2-007 |
| `description` | TEXT | SÍ | Descripción de la solicitud | — | UC-B2-007 |
| `supporting_docs` | JSONB | NO | Referencias a documentos adjuntos (fuera de webroot, solo metadatos) | — | RF-B1-063; UC-B2-007 |
| `status` | VARCHAR(30) | SÍ | CHECK IN ('received','in_progress','answered','closed') | — | UC-B2-007 |
| `acknowledgement_sent_at` | TIMESTAMPTZ | NO | Acuse automático con radicado y plazo | — | RF-B2-047 |
| `deadline_at` | TIMESTAMPTZ | SÍ | received_at + 10 días hábiles (consulta) o + 15 días hábiles | — | RF-B2-047; UC-B2-007 |
| `handler_user_id` | UUID | NO | FK a security_user (Oficial de Protección de Datos) | FK → security_user.user_id | UC-B2-007 |
| `response_text` | TEXT | NO | Respuesta al ciudadano | — | UC-B2-007 |
| `received_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW(); Hora Legal Colombiana | — | RF-B2-047 |
| `closed_at` | TIMESTAMPTZ | NO | NULL si no cerrada | — | UC-B2-007 |

### 2.12 `privacy_policy_version` — Versión de política de privacidad

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `version_id` | UUID | SÍ | Generado | PK | RF-B2-090; HU-B3-023 |
| `version_number` | VARCHAR(20) | SÍ | Ej.: '1.0', '2.1'; UNIQUE | UNIQUE | RF-B2-090 |
| `document_hash` | VARCHAR(64) | SÍ | SHA-256 del documento de política | — | HU-B2-014 |
| `effective_from` | DATE | SÍ | Fecha desde que es vigente | — | RF-B2-090 |
| `published_at` | TIMESTAMPTZ | SÍ | Fecha de publicación (antes de vigencia) | — | RF-B2-090 |
| `expands_scope` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE si la nueva versión amplía el alcance del tratamiento | — | RF-B2-090 |
| `requires_new_consent` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE si expands_scope=TRUE | — | RF-B2-090 |
| `created_by_user_id` | UUID | SÍ | FK a security_user | FK → security_user.user_id | [INFERIDO] |
| `created_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | [INFERIDO] |

### 2.13 `login_attempt` — Intento de acceso

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `attempt_id` | UUID | SÍ | Generado | PK | RF-B1-062; HU-B1-020 |
| `actor_type` | VARCHAR(20) | SÍ | CHECK IN ('internal_user','citizen') | — | RF-B1-062 |
| `internal_user_id` | UUID | NO | NULL si ciudadano | FK → security_user.user_id | RF-B1-062 |
| `citizen_id` | UUID | NO | NULL si usuario interno | FK → citizen_user.citizen_id | RF-B1-062 |
| `identifier_used` | VARCHAR(255) | SÍ | Email o documento usado en el intento (para detección de enumeración: guardar hash) | — | HU-09-D02 |
| `ip_address` | INET | SÍ | IP de origen | — | RF-B1-065 |
| `result` | VARCHAR(20) | SÍ | CHECK IN ('success','failure','blocked') | — | RF-B1-062; HU-B1-020 |
| `failure_reason` | VARCHAR(80) | NO | 'wrong_password','account_locked','mfa_failed', etc. | — | RF-B1-062 |
| `attempted_at` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana | — | RF-B1-062; RNF-B1-024 |

### 2.14 `backup_log` — Log de backups

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `backup_id` | UUID | SÍ | Generado | PK | RF-B1-067 |
| `backup_type` | VARCHAR(20) | SÍ | CHECK IN ('full','incremental') | — | RF-B1-067 |
| `started_at` | TIMESTAMPTZ | SÍ | Inicio del proceso | — | RF-B1-067 |
| `completed_at` | TIMESTAMPTZ | NO | NULL si en progreso | — | RF-B1-067 |
| `integrity_verified` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE tras verificación | — | RF-B1-067 |
| `integrity_verified_at` | TIMESTAMPTZ | NO | Fecha de verificación de integridad | — | RF-B1-067 |
| `storage_location` | VARCHAR(500) | SÍ | Referencia al almacenamiento separado | — | RF-B1-067 |
| `is_encrypted` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE si contiene info clasificada | — | RF-B1-067 |
| `result` | VARCHAR(20) | SÍ | CHECK IN ('success','failure','partial') | — | RF-B1-067 |
| `error_detail` | TEXT | NO | Detalle de error si result != 'success' | — | RF-B1-067 |
| `retention_until` | DATE | SÍ | started_at + 30 días mínimo | — | RF-B1-067 |

### 2.15 `sensitive_data_category` — Catálogo de categorías de datos sensibles

| Campo | Tipo | Obligatorio | Dominio / Restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| `category_id` | UUID | SÍ | Generado | PK | seguridad.md:166; Ley 1581 Art.5 |
| `code` | VARCHAR(50) | SÍ | UNIQUE; ej.: 'health','biometric','ethnic_origin','political','sexual_orientation','religion','union_membership' | UNIQUE | seguridad.md:166; Ley 1581 Art.5 |
| `label` | VARCHAR(100) | SÍ | Etiqueta legible | — | seguridad.md:166 |
| `requires_explicit_consent` | BOOLEAN | SÍ | DEFAULT TRUE para todos | — | RF-B2-048 |
| `legal_basis` | VARCHAR(255) | SÍ | Ej.: 'Ley 1581/2012 Art.5' | — | Ley 1581; RF-B2-048 |
| `created_at` | TIMESTAMPTZ | SÍ | DEFAULT NOW() | — | [INFERIDO] |

---

## 3. Reglas de negocio con impacto en datos

| ID RN | Regla | Impacto en BD |
|-------|-------|---------------|
| RN-B1-013 | Datos personales tratados con autorización previa, expresa e informada; evidencia con timestamp | `consent_log` es obligatorio antes de procesar datos; `occurred_at` con Hora Legal (INM) |
| RN-B1-016 | Incidentes graves/muy graves → CSIRT en ≤24 h | `security_incident.csirt_reported_at` debe ser ≤ `detected_at + 24h`; CHECK o trigger |
| RN-B2-006 | Alcaldía = Responsable del Tratamiento; proveedores = Encargados | Campo informativo; no genera tabla propia pero condiciona el modelo de privacidad |
| RN-B2-007 | No recolectar datos de menores sin autorización del representante legal | `citizen_user.is_minor=TRUE` exige `legal_guardian_consent=TRUE`; CHECK |
| RN-B2-008/009 | Minimización de datos; solo para la finalidad declarada | Cada campo en BD debe tener justificación en `privacy_policy_version` |
| RN-B2-010 | Inscribir BD de ciudadanos en el RNBD ante la SIC | Metadato administrativo; no modela tabla nueva pero requiere registro externo |
| RN-B2-022/030 | Notificar a la SIC violaciones de seguridad | `security_incident.sic_notified_at`; no NULL cuando `affects_personal_data=TRUE` y resuelto |
| RN-B3-031 | Ninguna cookie no esencial activa por defecto | `consent_log.consent_type` con tipos de cookies; se activa solo con acción explícita |
| RN-09-D01 | Autorización por recurso (anti-IDOR): ciudadano solo ve sus propios objetos | La FK `citizen_id` en radicados/trámites/PQRSD debe verificarse en capa de acceso; intento fallido → `security_audit_log` con `event_type='idor_attempt'` |
| RN-09-D02 | SoD: creador ≠ aprobador; gestor de usuarios ≠ auditor de sus propias acciones | Requiere campo `created_by_user_id` y `approved_by_user_id` en entidades CMS; CHECK que ambos sean distintos |
| RN-09-D03 | Token recuperación: un solo uso, expira 15 min; restablecer invalida sesiones | `password_reset_token.used_at IS NULL AND expires_at > NOW()`; al usar: SET `used_at`, invalidar todas las `user_session` activas del usuario |
| RN-09-D04 | MFA obligatorio para administradores CMS | `security_user.mfa_required=TRUE`; debe existir `mfa_enrollment` activo antes de permitir acceso |
| RN-09-D05 | Inmutabilidad del log de auditoría: append-only, encadenado por hash, 5 años | `security_audit_log`: ningún UPDATE/DELETE; `previous_hash` + `record_hash`; retención ≥ 5 años |
| RN-09-D06 | Caída del SCD no bloquea contenido público | No impacta esquema BD; es lógica de aplicación |
| RN-09-D07 | Umbral local de incidente "grave" — PREGUNTA ABIERTA | El dominio `classification` en `security_incident` depende de este umbral; [PENDIENTE] |

---

## 4. Cardinalidades y relaciones

| Relación | Cardinalidad | Descripción | Fuente |
|----------|-------------|-------------|--------|
| `security_user` → `mfa_enrollment` | 1:N | Un usuario interno puede tener N métodos MFA; al menos 1 requerido si mfa_required=TRUE | RN-09-D04 |
| `citizen_user` → `oidc_token` | 1:N | Un ciudadano puede tener N tokens a lo largo del tiempo; uno activo por vez | RF-B1-025 |
| `citizen_user` ↔ `user_session` | 1:N | Un ciudadano puede tener N sesiones (históricas); una activa | RF-B1-057 |
| `security_user` ↔ `user_session` | 1:N | Un usuario interno puede tener N sesiones históricas; una activa | RF-B1-057 |
| `user_session` → `oidc_token` | N:1 | Una sesión federada referencia un token OIDC; no toda sesión tiene token (internos) | RF-B1-025 |
| `user_session` → `security_audit_log` | 1:N | Una sesión genera N eventos de auditoría | RF-B1-065 |
| `citizen_user` → `consent_log` | 1:N | Un ciudadano tiene N registros de consentimiento a lo largo del tiempo | RF-B2-050 |
| `privacy_policy_version` → `consent_log` | 1:N | Una versión de política es referenciada por N registros de consentimiento | RF-B2-090 |
| `citizen_user` → `arco_request` | 1:N | Un ciudadano puede presentar N solicitudes ARCO | RF-B2-047 |
| `security_user` → `arco_request` (handler) | 1:N | Un Oficial de Datos gestiona N solicitudes ARCO | UC-B2-007 |
| `security_incident` → `incident_action` | 1:N | Un incidente tiene N acciones registradas | RF-B1-066; UC-B1-012 |
| `security_user` → `incident_action` | 1:N | Un usuario realiza N acciones sobre incidentes | UC-B1-012 |
| `security_user` → `password_reset_token` | 1:N | Un usuario interno puede haber generado N tokens (históricos); solo uno válido a la vez | RN-09-D03 |
| `citizen_user` ↔ `login_attempt` | 1:N | Un ciudadano tiene N intentos de login registrados | RF-B1-062 |
| `security_user` ↔ `login_attempt` | 1:N | Un usuario interno tiene N intentos de login | RF-B1-062 |

---

## 5. Jerarquías ISA / Polimorfismo

### 5.1 Jerarquía ISA: Usuario

El documento distingue dos tipos de usuarios con atributos distintos:

- **Supertipo implícito: Actor autenticado** con `user_id`, `email`, `is_active`
- **Subtipo 1: `security_user`** — usuario interno con `role_id`, `mfa_required`, `password_hash`
- **Subtipo 2: `citizen_user`** — ciudadano con `trust_level`, `auth_source`, `scd_sub`, `biometric_enrolled`, `is_minor`

La disyunción es **disjoint** (total): un actor es interno O ciudadano, nunca ambos. La completitud es **total**: todo actor autenticado pertenece a uno de los dos subtipos.

Estrategia de mapeo recomendada: **tabla por subtipo (supertype-subtype)** con supertipo `auth_actor` que centraliza `actor_id`, `email`, `is_active`, `actor_type`. Las FK de `security_audit_log`, `user_session` y `login_attempt` apuntarían al supertipo en vez de tener dos FK opcionales. [INFERIDO — el doc no define explícitamente un supertipo unificado; se infiere de la necesidad de evitar el antipatrón de arco exclusivo en las tablas de auditoría y sesión]

### 5.2 Polimorfismo en `security_audit_log` y `login_attempt`

Ambas tablas tienen dos FK opcionales mutuamente excluyentes: `internal_user_id` / `citizen_id`. Esto es un **arco exclusivo** válido con CHECK que garantice que exactamente uno sea NOT NULL (o ninguno si actor_type='system'/'anonymous').

Alternativa con supertipo (ver 5.1): una única FK `actor_id → auth_actor.actor_id` elimina el arco y refuerza la integridad referencial declarativa. Se recomienda adoptar el supertipo si el diseño global lo permite.

---

## 6. Fuera de BD (infraestructura/RNF) — justificado

| Elemento | Por qué está FUERA de BD |
|----------|--------------------------|
| HTTPS/TLS, cabeceras HTTP (CSP, HSTS, X-Frame-Options, etc.) | Configuración de servidor web/proxy (Nginx, Apache, CDN); no persiste en BD. RF-B1-055/056 |
| Certificados SSL (OV+, ≤2 años) | Gestionados por CA acreditada ONAC/GSE y almacenados en filesystem/HSM; no en BD. RNF-B1-022 |
| Captcha accesible (audio/WCAG) | Servicio externo (reCAPTCHA, hCaptcha) o widget de frontend; no genera datos persistentes propios del módulo |
| Deshabilitar métodos HTTP peligrosos | Configuración de servidor; no persiste en BD. RF-B1-059 |
| Sanitización de entradas / escape de variables | Lógica de aplicación (middleware); no persiste en BD. RF-B1-060 |
| Mensajes de error genéricos | Configuración de aplicación; no persiste. RF-B1-061 |
| Token CSRF (validación en memoria/sesión) | El token referenciado en `user_session.csrf_token` SÍ persiste; la validación lógica es de aplicación |
| HSTS / HPKP | Cabeceras HTTP; [HPKP] obsoleto desde Chrome 2018 (señalado en §9 del doc) |
| Hardening de servidor (credenciales por defecto, administración remota) | Configuración de OS/infraestructura; no persiste en BD. RF-B1-059 |
| Rate limiting / throttling por IP | Lógica de middleware (Redis, WAF); los eventos de bloqueo SÍ se registran en `security_audit_log` |
| Monitoreo continuo (DDoS, SIEM, listas negras) | Plataforma SIEM externa; el doc señala en §9 que implica un SIEM no nombrado. Las alertas generadas pueden originar un `security_incident` |
| Pentest / ethical hacking | Proceso externo; resultados pueden originar `security_incident` |
| CI/CD / Git / actualizaciones de parches ≤72 h | Pipeline de DevOps; no persiste en BD. RF-B1-068 |
| Backups (ejecución, cifrado, almacenamiento separado) | Infraestructura de backup; el REGISTRO de cada backup SÍ persiste en `backup_log` |
| DRP/BCP (planes de continuidad) | Documentos de políticas; no persisten como datos relacionales |
| Cifrado en reposo AES-256 | Configuración de SGBD/tablespace; no es una entidad, es un atributo de infraestructura. RNF-B2-009 |
| TLS 1.2/1.3 mínimo | Configuración de red; no persiste en BD. RNF-B2-008 |
| MSPI/SGSI (controles ISO 27000, NIST SP 800-53) | Marco de políticas; los hallazgos de auditoría pueden originar `security_incident`, pero el framework en sí no persiste en BD |
| Validación MIME de adjuntos | Lógica de aplicación; el intento rechazado SÍ se registra en `security_audit_log` |
| Integración X-Road (consulta ANI, biometría) | Servicio externo — módulo 10 Interoperabilidad; el resultado de validación ANI SÍ persiste en `citizen_user.ani_validated` |
| SCD/OIDC (flujo de autenticación federada) | Protocolo externo; los tokens emitidos SÍ persisten en `oidc_token` |

---

## 7. Inferencias [INFERIDO]

| Inferencia | Justificación | Marcador |
|-----------|---------------|----------|
| `auth_actor` como supertipo unificado de usuario | El doc no define supertipo explícito, pero `security_audit_log` y `user_session` referencian ambos tipos; evita el arco exclusivo | [INFERIDO] |
| `security_user.updated_at` y `citizen_user.updated_at` | Toda entidad mutable necesita timestamp de actualización para auditoría | [INFERIDO] |
| `mfa_enrollment.method` incluye 'totp','sms_otp','email_otp','hardware_key' | El doc menciona "segundo factor" genérico y OTP en UC-B3-004; los métodos específicos no se listan para internos | [INFERIDO] — [AMBIGUO] |
| `mfa_enrollment.secret_encrypted` requiere AES-256 | Por coherencia con RNF-B2-009 (cifrado en reposo AES-256) | [INFERIDO] |
| `oidc_token.nonce` | Buenas prácticas OIDC para prevenir replay attacks; el doc menciona `state` (anti-CSRF) pero no `nonce` explícitamente | [INFERIDO] |
| `security_audit_log.detail` como JSONB | El doc lista campos mínimos; casos como contenido modificado antes/después requieren estructura flexible | [INFERIDO] |
| `privacy_policy_version.created_by_user_id` | Toda versión de política tiene un responsable de publicación | [INFERIDO] |
| `citizen_user.updated_at` | Campos rectificables desde el perfil (RF-B2-050) exigen registro de modificación | [INFERIDO] |
| Columna `user_agent` en `user_session` | Práctica estándar de auditoría de sesiones; no citada explícitamente en el doc | [INFERIDO] |
| `login_attempt.identifier_used` se almacena como hash | HU-09-D02 exige respuesta genérica ante correo inexistente (no enumerar); guardar hash en vez del texto claro previene lectura accidental | [INFERIDO] |
| Umbral de días hábiles en `arco_request.deadline_at` | El doc cita "10 días hábiles (consulta)"; para Rectificación/Cancelación/Oposición UC-B2-007 cita "15 días hábiles" — se modela con un campo calculado | [AMBIGUO] — el doc no unifica los plazos por tipo |

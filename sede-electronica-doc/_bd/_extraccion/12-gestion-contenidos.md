# Extracción BD — Módulo 12 Gestión de Contenidos y Administración

## 0. Cobertura

Íntegra: **SÍ**. Archivo fuente: `/var/www/proyect-doc/elicitacion/sede-electronica/12-gestion-contenidos/gestion-contenidos.md`. Líneas leídas: 1–154 (154 líneas totales). Bloques cubiertos: §1 Descripción y alcance, §2 RF (2.1–2.4 + 2.D Delta), §3 RNF (+ 3.D Delta), §4 RN (+ 4.D Delta), §5 UC, §6 HU (+ 6.D Delta), §7 Datos/Entidades, §8 Integraciones, §9 Ambigüedades. Omisiones: 0.

---

## 1. Entidades candidatas

| # | Nombre entidad | Descripción | CANDIDATA-COMPARTIDA | Fuente |
|---|---------------|-------------|----------------------|--------|
| 1 | `usuario_interno` | Funcionario que opera el sistema (CMS, gestión documental, etc.). Tiene identificación, rol, permisos y historial de auditoría. | **SÍ** — compartida con módulos 02 (SIGEP), 03, 04, 10 | línea 133; RF-B1-079; UC-B1-015 |
| 2 | `rol` | Nivel de acceso del usuario interno (Administrador general, Editor de sección, Publicador). Mínimo 3 niveles. | **SÍ** — RBAC compartido con todos los módulos | RF-B1-079; línea 133 |
| 3 | `permiso` | Derecho atómico concedido a un rol sobre un módulo. | **SÍ** — RBAC compartido con todos los módulos | RF-B1-079; línea 133 |
| 4 | `contenido` | Página, resolución, norma, dataset u otro ítem gestionado por el CMS. Tiene ciclo de vida editorial (estados). | **SÍ** — compartida con módulos 07, 09, 11 (CMS) | RF-B1-076; RF-12-D01; RN-12-D01; línea 18 |
| 5 | `version_contenido` | Registro histórico de cada versión editada de un contenido (concurrencia optimista/pesimista). | No | RNF-12-D01; RN-12-D05; HU-12-D05 |
| 6 | `evento_auditoria` | Log inmutable de toda acción sobre el sistema: usuario, acción, componente, resultado, timestamp (Hora Legal). | **SÍ** — compartida con todos los módulos | RF-B1-076; RNF-B3-022; línea 134; HU-B2-008 |
| 7 | `expediente_electronico` | Conjunto de documentos electrónicos con foliado, índice firmado, ciclo vital (apertura→gestión→cierre→preservación) y TRD. | **SÍ** — compartida con módulos 03, 04, 10 (SGDEA Orfeo) | RF-B1-077; RN-12-D06; línea 135 |
| 8 | `documento_electronico` | Documento individual dentro de un expediente. Tiene metadatos de autenticidad, integridad, fiabilidad, disponibilidad. | **SÍ** — compartida con módulos 03, 04, 10 | RF-B1-077; RN-B1-015; línea 135 |
| 9 | `radicado` | Registro de recepción de un documento: número consecutivo, fecha/hora exacta, emisor, destinatario, tipo. | **SÍ** — compartida con módulos 03, 04 | RF-B2-067; línea 136 |
| 10 | `trd_serie` | Serie o subserie documental de la Tabla de Retención Documental (TRD) según AGN. Define plazos de retención y disposición. | No — propia del módulo 12 y gestión documental | RF-B1-077; RNF-B2-026; RN-B1-015; RN-12-D06 |
| 11 | `criterio_ita` | Criterio ITA (Índice de Transparencia Activa) definido por norma: accesibilidad, transparencia, formato, lenguaje claro. | No — propio del módulo 12 | RF-B1-078; RF-12-D05; línea 137 |
| 12 | `validacion_ita` | Resultado de validar un contenido contra un criterio ITA en el momento de publicación. Estado: cumple/incumple. | No — propio del módulo 12 | RF-B1-078; RF-12-D05; UC-B1-011; línea 137 |
| 13 | `notificacion` | Envío electrónico de un acto administrativo o alerta a un destinatario por un canal dado. | **SÍ** — compartida con módulos 04, 10 | RF-B3-153/154; RF-12-D03; RN-12-D04; línea 138 |
| 14 | `intento_notificacion` | Registro de cada intento de envío de una notificación (éxito, rebote, reintento, canal alterno). | No | RF-12-D03; RN-12-D04; HU-12-D03 |
| 15 | `firma_electronica` | Registro de una firma digital aplicada a un documento: certificado, algoritmo, timestamp, resultado de validación OCSP. | **SÍ** — compartida con módulos 03, 04 | RF-B3-155; RN-B3-016; HU-B3-017 |
| 16 | `calendario_habil` | Días hábiles e inhábiles del Distrito para cómputo de plazos (PQRSD, notificaciones, etc.). | **SÍ** — compartida con módulos 04, 05 | RF-B2-068; HU-B3-022 |
| 17 | `autorizacion_menor` | Autorización del representante legal para recolectar datos de un titular menor de 18 años. | No | RF-12-D04; RN-12-D03; HU-12-D04 |
| 18 | `informe_control_interno` | Informe semestral de control interno publicado automáticamente. Es una especialización de `contenido`. | No | RF-B2-093; línea 35 |

---

## 2. Atributos / campos por entidad

### 2.1 `usuario_interno`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | Identificador único | PK | [INFERIDO] — UC-B1-015 crea por nombre+correo+rol |
| `nombre_completo` | VARCHAR(200) | SÍ | Nombre del funcionario | — | UC-B1-015; línea 103 |
| `correo` | VARCHAR(254) | SÍ | Formato email; UNIQUE | — | UC-B1-015; línea 103 |
| `contrasena_hash` | VARCHAR(255) | SÍ | Hash bcrypt/argon2 [INFERIDO] | — | UC-B1-015 (contraseña temporal) |
| `contrasena_temporal` | BOOLEAN | SÍ | DEFAULT TRUE; TRUE = forzar cambio en primer acceso | — | UC-B1-015; línea 103 |
| `estado` | VARCHAR(20) | SÍ | CHECK IN ('activo','suspendido','dado_de_baja') | — | RF-12-D02; RN-12-D02; HU-12-D02 |
| `fecha_baja` | TIMESTAMPTZ | NO | NULL si activo/suspendido | — | RF-12-D02; RN-12-D02 |
| `acepto_tyc` | BOOLEAN | SÍ | NOT NULL; TRUE = aceptó T&C al registrarse | — | RF-B1-079; línea 19 |
| `fecha_acepto_tyc` | TIMESTAMPTZ | SÍ | Timestamp Hora Legal Colombiana del momento de aceptación | — | RF-B1-079 |
| `firma_registro_id` | UUID | NO | FK → firma_electronica.id; firma al registrarse | FK | RF-B1-079; línea 19 |
| `fecha_nacimiento` | DATE | NO | Requerida para control de menores (RN-12-D03) | — | RF-12-D04; RN-12-D03 |
| `id_sigep` | VARCHAR(50) | NO | Identificador externo en SIGEP [INFERIDO de integración] | — | §8 línea 143 |
| `fecha_creacion` | TIMESTAMPTZ | SÍ | DEFAULT now(); Hora Legal | — | RNF-B1-044 |
| `fecha_ultima_modificacion` | TIMESTAMPTZ | SÍ | DEFAULT now(); actualizar en cada cambio | — | [INFERIDO] |

### 2.2 `rol`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | [INFERIDO] |
| `nombre` | VARCHAR(100) | SÍ | UNIQUE; ej. 'Administrador general', 'Editor de sección', 'Publicador' | — | RF-B1-079; línea 18 |
| `descripcion` | TEXT | NO | — | — | [INFERIDO] |
| `nivel` | SMALLINT | SÍ | CHECK >= 1; orden jerárquico (mayor = más privilegios) | — | RF-B1-079 "≥3 niveles" |
| `es_sistema` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE = no se puede eliminar | — | [INFERIDO] |

### 2.3 `permiso`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | [INFERIDO] |
| `codigo` | VARCHAR(100) | SÍ | UNIQUE; ej. 'contenido:crear', 'contenido:publicar', 'pqrsd:leer' | — | RF-B1-079 "permisos por módulo" |
| `modulo` | VARCHAR(50) | SÍ | Módulo de la sede al que aplica | — | RF-B1-079; línea 19 |
| `descripcion` | TEXT | NO | — | — | [INFERIDO] |

### 2.4 `rol_permiso` (tabla de unión RBAC)

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `rol_id` | UUID | SÍ | FK → rol.id ON DELETE CASCADE | PK + FK | RF-B1-079 |
| `permiso_id` | UUID | SÍ | FK → permiso.id ON DELETE CASCADE | PK + FK | RF-B1-079 |

### 2.5 `usuario_rol` (tabla de unión RBAC — un usuario puede tener ≥1 rol)

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `usuario_id` | UUID | SÍ | FK → usuario_interno.id ON DELETE RESTRICT | PK + FK | RF-B1-079 |
| `rol_id` | UUID | SÍ | FK → rol.id ON DELETE RESTRICT | PK + FK | RF-B1-079 |
| `fecha_asignacion` | TIMESTAMPTZ | SÍ | DEFAULT now() | — | [INFERIDO] |
| `asignado_por` | UUID | NO | FK → usuario_interno.id | FK | [INFERIDO] — auditoría de la asignación |

### 2.6 `contenido`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | [INFERIDO] |
| `tipo` | VARCHAR(50) | SÍ | CHECK IN ('pagina','resolucion','norma','informe','dataset','noticia','otro'); discriminador de subtipo | — | RF-B1-076; §7 |
| `titulo` | VARCHAR(500) | SÍ | — | — | UC-B2-017; HU-B1-011 |
| `cuerpo` | TEXT | NO | Contenido principal (HTML limpio) | — | UC-B2-017 |
| `estado` | VARCHAR(30) | SÍ | CHECK IN ('borrador','pendiente_aprobacion','publicado','archivado') | — | RF-12-D01; RN-12-D01; HU-12-D01 |
| `seccion` | VARCHAR(100) | NO | Sección del sitio a la que pertenece | — | RF-B1-079 "editor por sección" |
| `modulo_origen` | VARCHAR(50) | NO | Módulo que generó el contenido | — | RF-12-D05 "cualquier módulo" |
| `creado_por` | UUID | SÍ | FK → usuario_interno.id ON DELETE RESTRICT | FK | UC-B2-017 |
| `aprobado_por` | UUID | NO | FK → usuario_interno.id; NULL hasta aprobación | FK | RN-12-D01; HU-12-D01 |
| `rechazado_por` | UUID | NO | FK → usuario_interno.id; NULL si no hubo rechazo | FK | RF-12-D01; HU-12-D01 |
| `comentario_rechazo` | TEXT | NO | Comentario al editor en caso de rechazo | — | RF-12-D01; HU-12-D01 |
| `fecha_publicacion` | TIMESTAMPTZ | NO | NULL hasta publicarse | — | UC-B2-017; RNF-B1-044 |
| `fecha_archivado` | TIMESTAMPTZ | NO | NULL hasta archivarse | — | RF-12-D01; RN-12-D01 |
| `version_actual` | INTEGER | SÍ | DEFAULT 1; número de versión actual (control optimista) | — | RNF-12-D01; RN-12-D05 |
| `lock_version` | INTEGER | SÍ | DEFAULT 1; token de bloqueo optimista | — | RNF-12-D01; RN-12-D05; HU-12-D05 |
| `metadatos_json` | JSONB | NO | Metadatos adicionales (vigencia, formato, categoría ITA, etc.) — validados antes de publicar | — | HU-B3-018; RF-12-D05 |
| `tiene_alt_imagen` | BOOLEAN | NO | NULL = no aplica; TRUE/FALSE resultado validación accesibilidad | — | RF-12-D05; RF-B1-078 |
| `tiene_subtitulos_video` | BOOLEAN | NO | NULL = no aplica; TRUE/FALSE | — | RF-12-D05 |
| `fecha_creacion` | TIMESTAMPTZ | SÍ | DEFAULT now(); Hora Legal | — | RNF-B1-044 |
| `fecha_ultima_modificacion` | TIMESTAMPTZ | SÍ | DEFAULT now() | — | [INFERIDO] |

### 2.7 `version_contenido`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | [INFERIDO] |
| `contenido_id` | UUID | SÍ | FK → contenido.id ON DELETE CASCADE | FK | RN-12-D05; HU-12-D05 |
| `numero_version` | INTEGER | SÍ | CHECK >= 1 | — | RNF-12-D01 |
| `cuerpo_snapshot` | TEXT | NO | Snapshot del cuerpo en este momento | — | RN-12-D05 |
| `metadatos_snapshot` | JSONB | NO | Snapshot de metadatos | — | RN-12-D05 |
| `estado_en_version` | VARCHAR(30) | SÍ | Estado del contenido en este momento | — | RF-12-D01 |
| `modificado_por` | UUID | SÍ | FK → usuario_interno.id ON DELETE RESTRICT | FK | RN-12-D05 |
| `fecha_modificacion` | TIMESTAMPTZ | SÍ | Hora Legal | — | RNF-B1-044 |

### 2.8 `evento_auditoria`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | BIGSERIAL | SÍ | Append-only; nunca se actualiza ni borra | PK | HU-B2-008; RNF-B3-022 |
| `usuario_id` | UUID | NO | FK → usuario_interno.id ON DELETE SET NULL (baja segura: el evento persiste) | FK | RN-12-D02; HU-12-D02; línea 134 |
| `accion` | VARCHAR(100) | SÍ | Ej. 'contenido.publicar', 'usuario.dar_de_baja', 'doc.eliminar_solicitado' | — | RF-B1-076; línea 134 |
| `componente` | VARCHAR(100) | SÍ | Módulo/entidad afectada | — | línea 134 |
| `entidad_tipo` | VARCHAR(50) | NO | Tipo de entidad afectada (polimorfismo por texto) [AMBIGUO] | — | línea 134 |
| `entidad_id` | UUID | NO | ID de la entidad afectada [AMBIGUO — FK genérica sin IR declarativa] | — | línea 134 |
| `resultado` | VARCHAR(20) | SÍ | CHECK IN ('exito','fallo','bloqueado') | — | línea 134 |
| `detalle` | TEXT | NO | Descripción libre de la acción | — | HU-B2-008 |
| `ip_origen` | INET | NO | IP del cliente | — | [INFERIDO] |
| `fecha_hora` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana; NOT NULL; no editable | — | RNF-B1-044; RNF-B3-022; línea 134 |

> [AMBIGUO] Los campos `entidad_tipo` / `entidad_id` modelan un polimorfismo de asociación sin integridad referencial declarativa. Se justifica como antipatrón aceptado para la entidad de auditoría (log inmutable append-only); ver §5.

### 2.9 `expediente_electronico`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B1-077; línea 135 |
| `numero_expediente` | VARCHAR(50) | SÍ | UNIQUE; formato AGN/TRD | — | RF-B1-077; RN-12-D06 |
| `titulo` | VARCHAR(500) | SÍ | — | — | [INFERIDO] |
| `trd_serie_id` | UUID | SÍ | FK → trd_serie.id; serie documental aplicable | FK | RF-B1-077; RNF-B2-026 |
| `estado_ciclo_vital` | VARCHAR(30) | SÍ | CHECK IN ('apertura','gestion','cierre','preservacion') | — | RF-B1-077; línea 135 |
| `folio_actual` | INTEGER | SÍ | DEFAULT 0; consecutivo actual del folio | — | RN-12-D06; línea 135 |
| `indice_firmado` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE = índice electrónico firmado presente | — | RN-12-D06; RF-B1-077 |
| `indice_firma_id` | UUID | NO | FK → firma_electronica.id; firma del índice | FK | RN-12-D06 |
| `responsable_id` | UUID | SÍ | FK → usuario_interno.id | FK | [INFERIDO] |
| `fecha_apertura` | TIMESTAMPTZ | SÍ | Hora Legal | — | RF-B1-077 |
| `fecha_cierre` | TIMESTAMPTZ | NO | NULL hasta cierre | — | RF-B1-077 |
| `metadatos_autenticidad` | TEXT | NO | Hash o resumen de integridad del expediente | — | RN-B1-015; línea 135 |
| `sgdea_ref` | VARCHAR(100) | NO | Referencia en Orfeo (SGDEA externo) | — | §8 línea 142 |

### 2.10 `documento_electronico`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B1-077; línea 135 |
| `expediente_id` | UUID | SÍ | FK → expediente_electronico.id ON DELETE RESTRICT | FK | RF-B1-077 |
| `folio_numero` | INTEGER | SÍ | Número de folio dentro del expediente; consecutivo | — | RN-12-D06 |
| `titulo` | VARCHAR(500) | SÍ | — | — | [INFERIDO] |
| `tipo_documental` | VARCHAR(100) | SÍ | Tipo según TRD | — | RN-B1-015 |
| `hash_integridad` | VARCHAR(128) | SÍ | SHA-256 o superior del archivo | — | RN-B1-015; RN-12-D06 |
| `autenticidad` | BOOLEAN | SÍ | DEFAULT FALSE; verificado | — | RN-B1-015; línea 135 |
| `disponibilidad` | BOOLEAN | SÍ | DEFAULT TRUE | — | RN-B1-015; línea 135 |
| `fiabilidad` | BOOLEAN | SÍ | DEFAULT FALSE | — | línea 135 |
| `ruta_almacenamiento` | TEXT | SÍ | Ruta o URI en almacenamiento externo [INFERIDO] | — | [INFERIDO] |
| `mime_type` | VARCHAR(100) | SÍ | Formato del archivo (PDF/A, XML, ODF, etc.) | — | RF-12-D05 "formatos abiertos" |
| `firmado` | BOOLEAN | SÍ | DEFAULT FALSE | — | RF-B3-155; RN-B3-016 |
| `firma_id` | UUID | NO | FK → firma_electronica.id | FK | RF-B3-155 |
| `eliminacion_solicitada` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE = solicitud pendiente | — | HU-B1-021; RF-B1-077 |
| `eliminacion_aprobada_por` | UUID | NO | FK → usuario_interno.id | FK | HU-B1-021 |
| `creado_por` | UUID | SÍ | FK → usuario_interno.id ON DELETE RESTRICT | FK | [INFERIDO] |
| `fecha_creacion` | TIMESTAMPTZ | SÍ | Hora Legal | — | RNF-B1-044 |

### 2.11 `radicado`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | [INFERIDO] |
| `numero_consecutivo` | BIGSERIAL | SÍ | UNIQUE; secuencia global anual [INFERIDO] | — | RF-B2-067; línea 136 |
| `fecha_hora_radicacion` | TIMESTAMPTZ | SÍ | Hora Legal Colombiana; NOT NULL | — | RF-B2-067; línea 136 |
| `emisor_nombre` | VARCHAR(300) | SÍ | Nombre del remitente | — | línea 136 |
| `emisor_correo` | VARCHAR(254) | NO | Correo del remitente | — | RF-B2-067 (acuse por mismo canal) |
| `destinatario_id` | UUID | NO | FK → usuario_interno.id (si es interno) | FK | línea 136 |
| `destinatario_externo` | VARCHAR(300) | NO | Nombre si es externo [AMBIGUO — podría ser ciudadano] | — | línea 136 |
| `tipo_documento` | VARCHAR(100) | SÍ | Tipo de documento radicado | — | línea 136 |
| `canal_recepcion` | VARCHAR(30) | SÍ | CHECK IN ('web','correo','presencial','app') | — | RF-B2-067 "mismo canal" |
| `acuse_enviado` | BOOLEAN | SÍ | DEFAULT FALSE | — | RF-B2-067 |
| `fecha_acuse` | TIMESTAMPTZ | NO | NULL hasta envío del acuse | — | RF-B2-067 |
| `documento_id` | UUID | NO | FK → documento_electronico.id (documento radicado) | FK | [INFERIDO] |
| `expediente_id` | UUID | NO | FK → expediente_electronico.id (si se incorpora a expediente) | FK | RF-B2-067 "distribución al destinatario" |

### 2.12 `trd_serie`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B1-077; RNF-B2-026 |
| `codigo` | VARCHAR(30) | SÍ | UNIQUE; código de serie/subserie AGN | — | RN-B1-015 |
| `nombre` | VARCHAR(300) | SÍ | — | — | [INFERIDO] |
| `nivel` | VARCHAR(20) | SÍ | CHECK IN ('fondo','seccion','subseccion','serie','subserie') | — | [INFERIDO — jerarquía TRD estándar AGN] |
| `padre_id` | UUID | NO | FK → trd_serie.id (jerarquía TRD) | FK | [INFERIDO] |
| `plazo_gestion_anios` | SMALLINT | SÍ | Años de retención en gestión | — | RNF-B2-026 |
| `plazo_central_anios` | SMALLINT | SÍ | Años de retención en archivo central | — | RNF-B2-026 |
| `disposicion_final` | VARCHAR(20) | SÍ | CHECK IN ('conservacion_total','eliminacion','seleccion','digitalizacion') | — | RN-B1-015; Decreto 1080/2015 |
| `fundamento_legal` | TEXT | NO | Norma que sustenta la retención | — | RN-B1-015 |
| `vigente` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |

### 2.13 `criterio_ita`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B1-078; RF-12-D05 |
| `codigo` | VARCHAR(50) | SÍ | UNIQUE; ej. 'WCAG-alt-imagen', 'WCAG-subtitulos', 'ITA-metadatos-transparencia' | — | RF-12-D05; línea 137 |
| `descripcion` | TEXT | SÍ | Descripción del criterio | — | RF-B1-078 |
| `norma_asociada` | VARCHAR(200) | SÍ | Ej. 'WCAG 2.1 / Res. 1519 MinTIC / Ley 1712' | — | RF-12-D05; línea 137; RF-B1-078 |
| `es_bloqueante` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE = bloquea publicación | — | RF-12-D05 "bloquea la publicación en los criterios bloqueantes" |
| `aplica_a_tipo` | VARCHAR(50) | NO | Tipo de contenido al que aplica (imagen, video, transparencia, etc.) | — | RF-12-D05 |
| `activo` | BOOLEAN | SÍ | DEFAULT TRUE | — | [INFERIDO] |

### 2.14 `validacion_ita`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B1-078; RF-12-D05; línea 137 |
| `contenido_id` | UUID | SÍ | FK → contenido.id ON DELETE CASCADE | FK | RF-B1-078; línea 137 |
| `criterio_ita_id` | UUID | SÍ | FK → criterio_ita.id | FK | RF-B1-078; línea 137 |
| `estado` | VARCHAR(15) | SÍ | CHECK IN ('cumple','incumple') | — | RF-B1-078; línea 137 |
| `modulo` | VARCHAR(50) | SÍ | Módulo donde está el contenido | — | RF-B1-078; línea 137 |
| `ubicacion_contenido` | TEXT | NO | Ruta/URL/descripción de la ubicación del fallo en el contenido | — | RF-B1-078; UC-B1-011; línea 137 |
| `responsable_id` | UUID | NO | FK → usuario_interno.id; usuario responsable del contenido | FK | RF-B1-078; línea 137; UC-B1-011 |
| `fecha_validacion` | TIMESTAMPTZ | SÍ | Timestamp de la validación automática | — | RF-B1-078; línea 137 |
| `fecha_ultima_correccion` | TIMESTAMPTZ | NO | NULL hasta que se corrija y pase a 'cumple' | — | RF-B1-078; línea 137 |
| `notas` | TEXT | NO | Detalle del incumplimiento | — | UC-B1-011 |

### 2.15 `notificacion`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B3-153/154; línea 138 |
| `tipo` | VARCHAR(50) | SÍ | CHECK IN ('acto_administrativo','alerta_pqrsd','cambio_estado','control_interno','otro') | — | RF-B3-153/154; HU-B3-022 |
| `acto_referencia_id` | UUID | NO | FK → documento_electronico.id (acto firmado) | FK | RF-B3-153; línea 138 |
| `destinatario_usuario_id` | UUID | NO | FK → usuario_interno.id (si es funcionario) | FK | [INFERIDO — puede ser ciudadano también] |
| `destinatario_correo` | VARCHAR(254) | NO | Dirección procesal electrónica | — | RF-B3-153; Decreto 2106 Art.46 |
| `canal_preferido` | VARCHAR(20) | SÍ | CHECK IN ('correo','sms','push_app','ccd','gestor_documental') | — | RF-B3-153/154; línea 138 |
| `estado` | VARCHAR(20) | SÍ | CHECK IN ('pendiente','enviada','entregada','fallida','cancelada') | — | RF-12-D03; HU-12-D03 |
| `fecha_envio` | TIMESTAMPTZ | NO | NULL hasta envío | — | línea 138 |
| `fecha_entrega` | TIMESTAMPTZ | NO | NULL hasta confirmación de entrega | — | RN-12-D04 |
| `plazo_notificacion_horas` | SMALLINT | NO | SLA: ej. <1 hora (RF-B3-153 "en <1 h") | — | RF-B3-153 |
| `autorizado_canal_alterno` | BOOLEAN | SÍ | DEFAULT FALSE; TRUE = puede usar canal alternativo si falla el preferido | — | RF-12-D03; RN-12-D04 |
| `canal_alterno` | VARCHAR(20) | NO | Canal alternativo autorizado si el preferido falla | — | RF-12-D03 |
| `fecha_creacion` | TIMESTAMPTZ | SÍ | DEFAULT now(); Hora Legal | — | RNF-B1-044 |

### 2.16 `intento_notificacion`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-12-D03; RN-12-D04 |
| `notificacion_id` | UUID | SÍ | FK → notificacion.id ON DELETE CASCADE | FK | RF-12-D03 |
| `canal_usado` | VARCHAR(20) | SÍ | Canal efectivamente usado en este intento | — | RF-12-D03; RN-12-D04 |
| `numero_intento` | SMALLINT | SÍ | CHECK >= 1; secuencia de reintentos | — | RF-12-D03 |
| `resultado` | VARCHAR(20) | SÍ | CHECK IN ('entregado','rebote','fallo_tecnico','timeout') | — | RF-12-D03; RN-12-D04; HU-12-D03 |
| `detalle_error` | TEXT | NO | Descripción del error si falló | — | RF-12-D03 |
| `fecha_intento` | TIMESTAMPTZ | SÍ | Hora Legal | — | RNF-B1-044 |

### 2.17 `firma_electronica`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-B3-155; RN-B3-016 |
| `documento_id` | UUID | NO | FK → documento_electronico.id; NULL si firma de otro objeto [AMBIGUO — polimorfismo] | FK | RF-B3-155 |
| `firmante_id` | UUID | SÍ | FK → usuario_interno.id ON DELETE RESTRICT | FK | RF-B3-155; HU-B3-017 |
| `certificado_serial` | VARCHAR(100) | SÍ | Número de serie del certificado digital | — | HU-B3-017 |
| `certificado_emisor` | VARCHAR(300) | SÍ | CA que emitió el certificado | — | [INFERIDO] |
| `algoritmo` | VARCHAR(50) | SÍ | Ej. 'RSA-SHA256', 'ECDSA-SHA256' | — | [INFERIDO] |
| `timestamp_firma` | TIMESTAMPTZ | SÍ | Momento exacto de la firma; Hora Legal | — | RF-B3-155; RNF-B1-044 |
| `resultado_ocsp` | VARCHAR(20) | SÍ | CHECK IN ('valido','revocado','desconocido'); validado al firmar | — | HU-B3-017 |
| `hash_documento` | VARCHAR(128) | SÍ | Hash SHA-256 del documento firmado | — | RN-B3-016; RN-12-D06 |
| `firma_valor` | TEXT | SÍ | Valor de la firma digital (Base64) | — | [INFERIDO] |
| `efectos_juridicos` | BOOLEAN | SÍ | DEFAULT TRUE; articulado con Ley 527/1999 | — | RN-B3-016; Decreto 2106 Art.60 |

### 2.18 `calendario_habil`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `fecha` | DATE | SÍ | — | PK | RF-B2-068; HU-B3-022 |
| `es_habil` | BOOLEAN | SÍ | TRUE = día hábil para el Distrito | — | RF-B2-068; línea 27 |
| `descripcion` | VARCHAR(200) | NO | Ej. 'Festivo nacional', 'Festivo local Santa Marta' | — | [INFERIDO] |

### 2.19 `autorizacion_menor`

| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|-----------------------|-------|--------|
| `id` | UUID | SÍ | — | PK | RF-12-D04; RN-12-D03 |
| `usuario_id` | UUID | SÍ | FK → usuario_interno.id; titular menor de edad | FK | RF-12-D04 |
| `representante_nombre` | VARCHAR(200) | SÍ | Nombre del representante legal | — | RF-12-D04; Ley 1581 Art.7 |
| `representante_documento` | VARCHAR(30) | SÍ | Número de documento del representante | — | [INFERIDO] |
| `tipo_relacion` | VARCHAR(50) | SÍ | CHECK IN ('padre','madre','tutor_legal','otro') | — | [INFERIDO] |
| `autorizacion_otorgada` | BOOLEAN | SÍ | DEFAULT FALSE | — | RF-12-D04; RN-12-D03 |
| `fecha_autorizacion` | TIMESTAMPTZ | NO | NULL hasta otorgarse | — | RF-12-D04 |
| `soporte_documental` | TEXT | NO | Referencia al documento que acredita la representación | — | [INFERIDO] |

---

## 3. Reglas de negocio con impacto en datos

| ID RN | Regla | Impacto en datos | Fuente |
|-------|-------|------------------|--------|
| RN-12-D01 | Ciclo de vida editorial: Borrador → Pendiente de aprobación → Publicado → Archivado; solo lo aprobado se publica; rechazo devuelve a Borrador con comentario. | `contenido.estado` CHECK; `contenido.aprobado_por` / `rechazado_por` / `comentario_rechazo` | líneas 90–91; RF-12-D01 |
| RN-12-D02 | Baja segura: al desvincular usuario se revocan accesos ≤1 día hábil; NO borrado físico; eventos de auditoría permanecen consultables. | `usuario_interno.estado` IN ('dado_de_baja'); `usuario_interno` nunca DELETE; `evento_auditoria.usuario_id` SET NULL (no CASCADE DELETE) | líneas 92; RF-12-D02; HU-12-D02 |
| RN-B1-015 / RN-12-D06 | Documentos no se eliminan sin autorización; expediente mantiene foliado e índice firmado; toda inserción/retiro queja en trazabilidad. | `documento_electronico.eliminacion_solicitada` + `eliminacion_aprobada_por`; `expediente_electronico.folio_actual`; `evento_auditoria` para cada operación | líneas 77; 95–96 |
| RN-B3-016 | Firma digital = mismo efecto que autógrafa. | `firma_electronica.efectos_juridicos` DEFAULT TRUE; documento firmado → `documento_electronico.firmado` TRUE | línea 78; Decreto 2106 Art.60 |
| RN-B3-019 | Notificación electrónica preferente si hay dirección procesal. | `notificacion.canal_preferido` prioriza 'correo'/'ccd' cuando hay `destinatario_correo` | línea 79; Decreto 2106 Arts.46,47 |
| RN-12-D03 | Titular <18 requiere autorización representante antes de recolectar datos. | `autorizacion_menor`; CHECK: si `usuario_interno.fecha_nacimiento` indica <18 → `autorizacion_menor.autorizacion_otorgada` debe ser TRUE antes de activar el registro | línea 93; Ley 1581 Art.7 |
| RN-12-D04 | Rebote no es notificado; reintentar y/o canal alterno; registrar estado de entrega. | `intento_notificacion.resultado`; `notificacion.estado` solo pasa a 'entregada' con `intento_notificacion.resultado` = 'entregado' | línea 94; Ley 1437 |
| RN-12-D05 | Bloqueo optimista/pesimista: dos usuarios no pisan cambios sobre mismo contenido. | `contenido.lock_version`; aplicación verifica versión antes de UPDATE; conflicto → error 409 | línea 95; RNF-12-D01 |
| RNF-B3-022 | Log de auditoría: retención ≥5 años; append-only; 100% de eventos críticos. | `evento_auditoria.id` BIGSERIAL; sin UPDATE/DELETE; política de retención ≥5 años en SGBD | línea 62; HU-B2-008 |
| RNF-B2-026 | 100% de expedientes con TRD documentada y aplicada. | `expediente_electronico.trd_serie_id` NOT NULL | línea 61 |
| RN-SoD | Segregación de funciones: el creador de un contenido no puede aprobarlo ni publicarlo. | `contenido.creado_por` ≠ `contenido.aprobado_por`; CHECK o regla de aplicación | HU-B1-011; HU-12-D01 |
| RF-B1-078 | Tablero ITA arranca en cero; sin datos mock ni precargados; se construye por validación automática. | `validacion_ita` tiene 0 filas al inicio; se inserta solo en cada publicación | líneas 33–34; RF-12-D05 |
| RF-12-D05 | Criterios bloqueantes impiden la publicación. | `criterio_ita.es_bloqueante` TRUE → `contenido.estado` no puede pasar a 'publicado' si hay `validacion_ita.estado = 'incumple'` para ese criterio | línea 43 |

---

## 4. Cardinalidades y relaciones

| Relación | Cardinalidad | Notas | Fuente |
|----------|-------------|-------|--------|
| `usuario_interno` — `rol` (via `usuario_rol`) | M:N | Un usuario puede tener ≥1 rol; un rol puede asignarse a muchos usuarios | RF-B1-079 |
| `rol` — `permiso` (via `rol_permiso`) | M:N | Un rol tiene muchos permisos; un permiso puede estar en muchos roles | RF-B1-079 |
| `usuario_interno` — `evento_auditoria` | 1:N | Un usuario genera muchos eventos; evento conserva FK nullable | RNF-B3-022; RN-12-D02 |
| `usuario_interno` — `contenido` (creador) | 1:N | Un usuario crea muchos contenidos | UC-B2-017 |
| `usuario_interno` — `contenido` (aprobador) | 1:N | Un administrador aprueba muchos contenidos | RN-12-D01 |
| `contenido` — `version_contenido` | 1:N | Un contenido tiene muchas versiones históricas | RN-12-D05 |
| `contenido` — `validacion_ita` | 1:N | Un contenido puede tener validaciones contra múltiples criterios ITA | RF-B1-078 |
| `criterio_ita` — `validacion_ita` | 1:N | Un criterio ITA tiene muchas validaciones (una por contenido publicado) | RF-12-D05 |
| `expediente_electronico` — `documento_electronico` | 1:N | Un expediente tiene muchos documentos; mínimo 1 | RF-B1-077; RN-12-D06 |
| `trd_serie` — `expediente_electronico` | 1:N | Una serie TRD aplica a muchos expedientes | RNF-B2-026 |
| `trd_serie` — `trd_serie` (padre) | 1:N | Jerarquía TRD: fondo → sección → subsección → serie → subserie | [INFERIDO] |
| `documento_electronico` — `firma_electronica` | 1:1 o 1:N | Un documento puede tener ≥1 firma (contrafirma posible) [AMBIGUO — se modela 1:N para no perder casos] | RF-B3-155; HU-B3-017 |
| `expediente_electronico` — `firma_electronica` (índice) | 1:1 | El índice del expediente tiene una firma | RN-12-D06 |
| `radicado` — `documento_electronico` | 1:1 o N:1 | Un radicado puede referir a un documento; un documento puede tener un radicado | RF-B2-067 |
| `notificacion` — `intento_notificacion` | 1:N | Una notificación tiene ≥1 intento de envío | RF-12-D03; RN-12-D04 |
| `notificacion` — `documento_electronico` (acto) | N:1 | Muchas notificaciones pueden referir al mismo acto | RF-B3-153 |
| `usuario_interno` — `autorizacion_menor` | 1:1 | Si el usuario es menor, tiene una autorización | RF-12-D04; RN-12-D03 |
| `calendario_habil` | entidad independiente | Sin FK externas; consultada por lógica de negocio | RF-B2-068 |

---

## 5. Jerarquías ISA / polimorfismo

### 5.1 Jerarquía ISA: `contenido` (supertipo) → subtipos

**Evidencia:** El campo `tipo` de `contenido` discrimina entre: `pagina`, `resolucion`, `norma`, `informe`, `dataset`, `noticia`, `otro`. El informe de control interno (RF-B2-093) es una especialización explícita. Los criterios ITA aplican diferenciado por tipo (imágenes, videos, metadatos de transparencia).

**Restricción ISA:** overlapping (un contenido puede ser norma Y tener características de informe) [AMBIGUO — no queda claro si es disjunto], parcial (no todo tipo de contenido tiene subclase adicional).

**Estrategia de mapeo recomendada:** Single-table (tabla única con discriminador `tipo` y columnas nullable por subtipo). Justificación: la variación entre subtipos es mínima (todos comparten título, estado, ciclo editorial, validaciones ITA); la fragmentación en tablas por subtipo genera joins costosos sin ganancia de integridad sustancial en este caso. Los atributos específicos por tipo (ej. vigencia de norma) se almacenan en `metadatos_json`.

### 5.2 Jerarquía TRD: `trd_serie` (autorelación)

**Evidencia:** La TRD tiene estructura jerárquica AGN: fondo → sección → subsección → serie → subserie. Se modela con autorelación `padre_id` (adjacency list). [INFERIDO de la normativa AGN/Decreto 1080/2015]

### 5.3 Polimorfismo en `evento_auditoria`

**Patrón detectado:** Los campos `entidad_tipo` / `entidad_id` representan una asociación polimórfica (el evento puede referir a un `contenido`, `documento_electronico`, `usuario_interno`, `radicado`, etc.).

**Antipatrón reconocido:** FK genérica sin integridad referencial declarativa. Se acepta **excepcionalmente** porque:
1. El log de auditoría es append-only e inmutable por diseño.
2. Agregar múltiples FK opcionales (arco exclusivo) aumentaría la complejidad sin beneficio operativo para una tabla de solo lectura.
3. La integridad se garantiza por el proceso de escritura (triggers o capa de aplicación), no por FK declarativas.

**Mitigación:** Documentar el riesgo; implementar trigger que valide que `(entidad_tipo, entidad_id)` exista en la tabla correspondiente al momento de inserción (no aplica retroactivamente).

### 5.4 Polimorfismo en `firma_electronica`

**Evidencia:** La firma puede aplicarse a: `documento_electronico` (RF-B3-155), índice de `expediente_electronico` (RN-12-D06), o al registro de `usuario_interno` al registrarse (RF-B1-079).

**Estrategia:** Arco exclusivo con FK opcionales mutuamente excluyentes + CHECK:
- `firma_electronica.documento_id` FK → `documento_electronico.id` (nullable)
- `firma_electronica.expediente_indice_id` FK → `expediente_electronico.id` (nullable) [INFERIDO — campo no listado en §2.17 pero necesario]
- `firma_electronica.usuario_registro_id` FK → `usuario_interno.id` (nullable) [INFERIDO]
- CHECK: exactamente una de las tres FK es NOT NULL.

---

## 6. Normativa citada

| Norma | Artículo/detalle | Aplica a | Fuente |
|-------|-----------------|----------|--------|
| Decreto 2106/2019 | Art.46, 47: notificaciones electrónicas con preferencia sobre físicas | `notificacion`; `notificacion.canal_preferido` | líneas 37, 79 |
| Decreto 2106/2019 | Art.60: firma digital = mismos efectos que autógrafa | `firma_electronica.efectos_juridicos` | líneas 38, 78 |
| Decreto 2106/2019 | Art.16, 63: documentación electrónica, expediente | `expediente_electronico`, `documento_electronico` | línea 77 |
| Ley 527/1999 | Efectos jurídicos del mensaje de datos y firma electrónica | `firma_electronica` | línea 78 |
| Ley 594/2000 | Gestión documental pública; no eliminar sin autorización | `documento_electronico.eliminacion_solicitada`; `trd_serie` | líneas 77, 92, 96 |
| Decreto 1080/2015 | TRD/AGN; ciclo vital del documento | `trd_serie`; `expediente_electronico.estado_ciclo_vital` | líneas 77, 92, 96 |
| Ley 1437/2011 (CPACA) | Constancia de notificación; rebote no es notificado | `intento_notificacion`; `notificacion.estado` | línea 94 |
| Ley 1581/2012 Art.7; Decreto 1377/2013 | Protección datos de menores; autorización representante | `autorizacion_menor`; `usuario_interno.fecha_nacimiento` | líneas 55, 93 |
| Ley 1712/2014; Res. 1519 MinTIC | ITA: criterios de transparencia activa | `criterio_ita`; `validacion_ita` | línea 44 |
| WCAG 2.1 | Accesibilidad: alt en imágenes, subtítulos en video | `criterio_ita`; `contenido.tiene_alt_imagen`; `contenido.tiene_subtitulos_video` | línea 44 |
| Decreto 1083/2015 Art.2.2.35.5 | G-CIO reporta al representante legal | [INFERIDO: no genera tabla propia; impacta modelo organizacional] | línea 81 |
| Decreto 612/2018; Ley 1753 Art.45 | PETI 5 años publicado y vigente | [No genera tabla en este módulo; referencia a contexto] | línea 81 |
| Res. 1297/2010 | RAEE (residuos TIC) | [No genera tabla en BD relacional] | línea 65 |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Justificación | Entidad/campo afectado |
|---|-----------|---------------|----------------------|
| I-01 | `usuario_interno.id` como UUID | Estándar para sistemas multi-módulo con integración SIGEP; sin tipo explícito en la fuente | `usuario_interno.id` |
| I-02 | `usuario_interno.contrasena_hash` con algoritmo bcrypt/argon2 | Buena práctica de seguridad obligatoria; la fuente menciona contraseña temporal pero no el algoritmo | `usuario_interno.contrasena_hash` |
| I-03 | `usuario_interno.id_sigep` para integración | La fuente indica integración con SIGEP (§8); la clave de cruce es necesaria | `usuario_interno.id_sigep` |
| I-04 | Tabla `usuario_rol` (M:N) en lugar de FK directa | RF-B1-079 indica "≥3 niveles" y "permisos por módulo"; la separación rol/permiso exige M:N | `usuario_rol`, `rol_permiso` |
| I-05 | `contenido.lock_version` para bloqueo optimista | RNF-12-D01 y RN-12-D05 exigen bloqueo optimista/pesimista; el campo version es el mecanismo estándar | `contenido.lock_version` |
| I-06 | `documento_electronico.ruta_almacenamiento` | Todo documento tiene un archivo físico; la fuente nombra SGDEA (Orfeo) como repositorio | `documento_electronico.ruta_almacenamiento` |
| I-07 | `trd_serie.padre_id` (autorelación) | La TRD AGN tiene jerarquía fondo→serie→subserie; Decreto 1080/2015 | `trd_serie.padre_id` |
| I-08 | Arco exclusivo en `firma_electronica` para índice expediente y registro usuario | La firma aplica a 3 entidades distintas (doc, índice expediente, registro usuario); la fuente no modela el arco explícitamente | `firma_electronica` FK adicionales |
| I-09 | `radicado.destinatario_externo` como campo texto | La fuente no aclara si el destinatario es siempre interno; el registro 24/7 admite externos | `radicado.destinatario_externo` |
| I-10 | `numero_consecutivo` como BIGSERIAL anual | La fuente dice "número consecutivo" sin definir alcance; práctica estándar de radicación colombiana es anual | `radicado.numero_consecutivo` |
| I-11 | `contenido.metadatos_json` JSONB para atributos de subtipos | Single-table strategy con discriminador `tipo`; atributos específicos por subtipo (vigencia de norma, etc.) van en JSONB validado | `contenido.metadatos_json` |
| I-12 | `evento_auditoria.ip_origen` | Auditoría de seguridad estándar; la fuente no lo menciona explícitamente | `evento_auditoria.ip_origen` |
| I-13 | `firma_electronica.expediente_indice_id` y `usuario_registro_id` | RN-12-D06 exige firma en índice; RF-B1-079 exige firma al registrarse; sin FK explícita en la fuente | `firma_electronica` |

---

## 8. Vacíos detectados (para investigar)

| # | Vacío | Pregunta a resolver | Fuente del vacío |
|---|-------|--------------------|--------------------|
| V-01 | Roles exactos del SM CMS y separación de funciones: ¿cuántos y cuáles roles existen más allá de los 3 mencionados? ¿Hay rol "Administrador de gestión documental" separado? | [PREGUNTA ABIERTA] §9 línea 152 | §9 |
| V-02 | Enrutamiento de PQRSD a dependencia: ¿automático o manual? Impacta si `radicado` tiene FK a una tabla de dependencias. | [A-11] §9 línea 151 | §9 |
| V-03 | Contrafirma: ¿un documento puede tener múltiples firmas (ej. visado + firma final)? La cardinalidad `documento_electronico` → `firma_electronica` es 1:N tentativa. | No explicitado en la fuente | HU-B3-017; RF-B3-155 |
| V-04 | Alcance del número de radicado: ¿es único por año o global? ¿Incluye prefijo de dependencia? | No explicitado | RF-B2-067 |
| V-05 | Analítica de uso (RF-B2-089): ¿se persiste en BD relacional o en una herramienta externa (GA, Matomo)? Si persiste, requiere entidades adicionales. | No explicitado | RF-B2-089 |
| V-06 | ¿El `informe_control_interno` tiene estructura propia (campos específicos) o es solo un `contenido` con `tipo='informe'` y metadatos? | No explicitado | RF-B2-093 |
| V-07 | Integración datos.gov.co (§8): ¿la publicación de datasets genera registros en la BD local o es solo push externo? Impacta si `contenido.tipo='dataset'` tiene atributos adicionales. | No explicitado | §8 línea 145 |
| V-08 | ¿El bloqueo de edición concurrente (RN-12-D05) es optimista (lock_version) o pesimista (fila reservada)? La fuente dice "optimista/pesimista" sin decidir. | RNF-12-D01; RN-12-D05 | línea 70 |

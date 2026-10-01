# Extracción BD — Global G2 RF/RNF/RN

> **Fuentes:**
> - `/var/www/proyect-doc/elicitacion/sede-electronica/_global/rf-rnf-delta-profundo.md` (225 líneas, íntegro)
> - `/var/www/proyect-doc/elicitacion/sede-electronica/_global/rn-delta-profundo.md` (205 líneas, íntegro)
>
> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Extractor:** jose-bd (PostgreSQL 15+)

---

## 0. Cobertura

2 unidades íntegras: SÍ  
Líneas leídas: rf-rnf-delta-profundo.md líneas 1–225 + rn-delta-profundo.md líneas 1–205 = 430 líneas totales  
Omitidas: 0

---

## 1. Entidades candidatas derivadas de RF

| # | Entidad candidata | RF fuente | Notas / ¿CANDIDATA-COMPARTIDA? |
|---|---|---|---|
| 1 | ConsentimientoCookies | RF-01-D01 | Registra categorías, fecha, versión de política; CANDIDATA-COMPARTIDA (módulo 01 + Ley 1581) |
| 2 | MenuNavegacion | RF-01-D02 | Ítems de menú, orden, nivel, estado publicado/despublicado |
| 3 | ItemMenu | RF-01-D02 | Subtipo de nodo del menú (nivel 1 / nivel 2) |
| 4 | Noticia / CarruselItem | RF-01-D02 | Contenido editorial gestionado desde CMS |
| 5 | DominioBlancoExterno | RF-01-D03 | Lista blanca de dominios de confianza administrable |
| 6 | DocumentoTransparencia | RF-02-D01 | Documento con versionado y URL persistente |
| 7 | VersionDocumento | RF-02-D01 | Versión anterior de un documento; historial inmutable |
| 8 | AlertaCumplimiento | RF-02-D02 | Alertas de vencimiento de publicaciones obligatorias |
| 9 | SincronizacionExterna | RF-02-D03 + RNF-02-D01 | Log de intentos de sincronización con SIGEP/SECOP/SUCOP/SUIN/KOGUI |
| 10 | Tramite | RF-03-D01..D07 | Entidad central del módulo de trámites; CANDIDATA-COMPARTIDA |
| 11 | TokenIdempotencia | RF-03-D01 | Clave de idempotencia por transacción de pago/radicado |
| 12 | Pago | RF-03-D02 | Registro de transacción con pasarela PSE/Superfinanciera |
| 13 | BorradorTramite | RF-03-D03 | Estado parcial guardado de un trámite multi-paso |
| 14 | AdjuntoTramite | RF-03-D04 | Archivo adjunto vinculado a un trámite (tipo MIME, tamaño, estado antivirus) |
| 15 | Subsanacion | RF-03-D05 | Requerimiento de documentos adicionales dentro de un trámite |
| 16 | FallaTecnicaInterop | RF-03-D06 | Registro de falla técnica de X-Road/SCD con habilitación de carga manual |
| 17 | Desistimiento | RF-03-D07 | Cancelación/desistimiento de trámite por el ciudadano |
| 18 | PQRSD | RF-04-D01..D05 | Petición, Queja, Reclamo, Sugerencia o Denuncia; CANDIDATA-COMPARTIDA |
| 19 | RasignacionPQRSD | RF-04-D01 | Traslado/reasignación de PQRSD entre dependencias o a otra autoridad |
| 20 | RespuestaPQRSD | RF-04-D02 | Respuesta oficial cargada por funcionario con fecha y canal |
| 21 | CalendarioHabil | RF-04-D03 + RN-TX-D01 | Días hábiles del Distrito (festivos nacionales + distritales); fuente única |
| 22 | NotificacionElectronica | RF-04-D05 | Envío de notificación con constancia, canal y fecha; CANDIDATA-COMPARTIDA |
| 23 | MecanismoParticipacion | RF-05-D01 | Consulta normativa / mecanismo participativo con ciclo de vida |
| 24 | AporteParticipacion | RF-05-D01 | Aporte recibido dentro de una consulta abierta |
| 25 | ResultadoParticipacion | RF-05-D02 | Consolidado de observaciones y respuesta de la entidad |
| 26 | ConfiguracionAgenda | RF-06-D01 | Dependencias/servicios agendables, franjas, cupos, días no laborables |
| 27 | FranjaHoraria | RF-06-D01 | Franja de disponibilidad con cupo máximo |
| 28 | Cita | RF-06-D02 + RF-06-D03 | Reserva de cita de un ciudadano; CANDIDATA-COMPARTIDA |
| 29 | RecordatorioCita | RF-06-D04 | Recordatorio previo a la cita; registro de inasistencia (no-show) |
| 30 | ContenidoMultimedia | RF-07-D01 | Video/audio publicado en CMS con metadatos de accesibilidad |
| 31 | SesionSUS | RF-08-D01 + RNF-08-D01 | Ronda de evaluación SUS con resultados persistidos |
| 32 | LogAuditoria | RF-09-D01 + RN-09-D05 | Registro de auditoría inmutable (append-only); CANDIDATA-COMPARTIDA |
| 33 | TokenRecuperacion | RF-09-D02 | Token de un solo uso para recuperación de credenciales |
| 34 | UsuarioInterno | RF-09-D03 + RF-12-D02 | Usuario del CMS con roles; no se borra físicamente |
| 35 | RolPermiso | RF-09-D03 | Roles y permisos del sistema con SoD declarada |
| 36 | IntercambioXRoad | RF-10-D01 | Llamada X-Road con resultado, reintentos y falla |
| 37 | ColaSelloTSA | RF-10-D02 | Cola de mensajes pendientes de estampa RFC 3161 |
| 38 | CertificadoDigital | RNF-10-D01 | Certificado ONAC/TLS/OCSP con fecha de vencimiento y alertas |
| 39 | Dataset | RF-11-D01 | Dataset de datos abiertos con metadatos, frecuencia y estado |
| 40 | VersionDataset | RF-11-D01 | Versión de un dataset publicado |
| 41 | Contenido | RF-12-D01 | Entidad editorial genérica (norma, artículo, documento) con ciclo de vida |
| 42 | EstadoContenido | RF-12-D01 | Dominio enumerado: Borrador / Pendiente aprobación / Publicado / Archivado |
| 43 | NotificacionMulticanal | RF-12-D03 | Intento de envío con estado de entrega y reintentos |
| 44 | ExpedienteElectronico | RN-12-D06 | Expediente con índice firmado y foliado consecutivo |
| 45 | ContenidoTraduccion | RNF-TX-D02 | Traducción de un contenido a un idioma/lengua étnica |

---

## 2. Atributos/campos implicados por RF

| Campo | Tipo | Entidad | RF fuente |
|---|---|---|---|
| consentimiento_id | UUID PK | ConsentimientoCookies | RF-01-D01 |
| ciudadano_id | FK → Ciudadano | ConsentimientoCookies | RF-01-D01 |
| categorias_aceptadas | JSONB (array de categorías) | ConsentimientoCookies | RF-01-D01 |
| version_politica | VARCHAR(50) NOT NULL | ConsentimientoCookies | RF-01-D01 |
| fecha_consentimiento | TIMESTAMPTZ NOT NULL | ConsentimientoCookies | RF-01-D01 |
| fecha_expiracion | TIMESTAMPTZ NOT NULL | ConsentimientoCookies | RF-01-D01 |
| estado | VARCHAR(20) CHECK IN ('activo','caducado','revocado') | ConsentimientoCookies | RF-01-D01 / RN-01-D01 |
| menu_id | UUID PK | MenuNavegacion | RF-01-D02 |
| nombre | VARCHAR(120) NOT NULL | MenuNavegacion | RF-01-D02 |
| estado_menu | VARCHAR(20) CHECK IN ('activo','inactivo') | MenuNavegacion | RF-01-D02 |
| item_id | UUID PK | ItemMenu | RF-01-D02 |
| menu_id | FK → MenuNavegacion | ItemMenu | RF-01-D02 |
| padre_item_id | FK nullable → ItemMenu | ItemMenu | RF-01-D02 |
| nivel | SMALLINT CHECK (nivel IN (1,2)) | ItemMenu | RF-01-D02 / RN-01-D02 |
| orden | SMALLINT NOT NULL | ItemMenu | RF-01-D02 |
| etiqueta | VARCHAR(120) NOT NULL | ItemMenu | RF-01-D02 |
| url | VARCHAR(500) | ItemMenu | RF-01-D02 |
| estado_item | VARCHAR(20) CHECK IN ('publicado','despublicado') | ItemMenu | RF-01-D02 |
| dominio_id | UUID PK | DominioBlancoExterno | RF-01-D03 |
| dominio | VARCHAR(253) NOT NULL UNIQUE | DominioBlancoExterno | RF-01-D03 |
| activo | BOOLEAN NOT NULL DEFAULT TRUE | DominioBlancoExterno | RF-01-D03 |
| documento_id | UUID PK | DocumentoTransparencia | RF-02-D01 |
| titulo | VARCHAR(500) NOT NULL | DocumentoTransparencia | RF-02-D01 |
| url_permanente | VARCHAR(2000) NOT NULL UNIQUE | DocumentoTransparencia | RF-02-D01 / RN-02-D01 |
| version_actual_id | FK → VersionDocumento | DocumentoTransparencia | RF-02-D01 |
| responsable_id | FK → UsuarioInterno | DocumentoTransparencia | RF-02-D02 |
| plazo_legal_fecha | DATE | DocumentoTransparencia | RF-02-D02 / RN-02-D02 |
| version_doc_id | UUID PK | VersionDocumento | RF-02-D01 |
| documento_id | FK → DocumentoTransparencia | VersionDocumento | RF-02-D01 |
| numero_version | INTEGER NOT NULL | VersionDocumento | RF-02-D01 |
| archivo_url | VARCHAR(2000) NOT NULL | VersionDocumento | RF-02-D01 |
| fecha_publicacion | TIMESTAMPTZ NOT NULL | VersionDocumento | RF-02-D01 |
| publicado_por | FK → UsuarioInterno | VersionDocumento | RF-02-D01 |
| sinc_id | UUID PK | SincronizacionExterna | RF-02-D03 / RNF-02-D01 |
| sistema_externo | VARCHAR(50) CHECK IN ('SIGEP','SECOP','SUIN','SUCOP','KOGUI') | SincronizacionExterna | RF-02-D03 |
| fecha_intento | TIMESTAMPTZ NOT NULL | SincronizacionExterna | RNF-02-D01 |
| resultado | VARCHAR(20) CHECK IN ('exitosa','fallida') | SincronizacionExterna | RF-02-D03 |
| detalle_error | TEXT | SincronizacionExterna | RF-02-D03 |
| tramite_id | UUID PK | Tramite | RF-03-D01..D07 |
| ciudadano_id | FK → Ciudadano | Tramite | RF-03-D01 |
| tipo_tramite | VARCHAR(100) NOT NULL | Tramite | RF-03-D01 |
| estado_tramite | VARCHAR(30) CHECK IN ('borrador','en_proceso','en_espera_pago','en_espera_subsanacion','desistido','resuelto','silencio_positivo') | Tramite | RF-03-D02 / RF-03-D07 / RN-03-D08 |
| radicado | VARCHAR(50) UNIQUE | Tramite | RF-03-D01 / RN-04-D07 |
| fecha_radicacion | TIMESTAMPTZ | Tramite | RF-03-D01 |
| nivel_autenticacion_requerido | VARCHAR(20) CHECK IN ('bajo','medio','alto','muy_alto') | Tramite | RN-TX-D04 |
| token_id | UUID PK | TokenIdempotencia | RF-03-D01 |
| tramite_id | FK → Tramite | TokenIdempotencia | RF-03-D01 |
| token_valor | VARCHAR(128) NOT NULL UNIQUE | TokenIdempotencia | RF-03-D01 / RN-03-D01 |
| usado | BOOLEAN NOT NULL DEFAULT FALSE | TokenIdempotencia | RF-03-D01 |
| fecha_creacion | TIMESTAMPTZ NOT NULL | TokenIdempotencia | RF-03-D01 |
| pago_id | UUID PK | Pago | RF-03-D02 |
| tramite_id | FK → Tramite | Pago | RF-03-D02 |
| token_idempotencia_id | FK → TokenIdempotencia | Pago | RF-03-D01 / RN-03-D01 |
| estado_pago | VARCHAR(20) CHECK IN ('pendiente','aprobado','rechazado','fallido') | Pago | RF-03-D02 / RN-03-D02 |
| monto | DECIMAL(14,2) NOT NULL | Pago | RF-03-D02 |
| pasarela_referencia | VARCHAR(200) | Pago | RF-03-D02 |
| fecha_creacion | TIMESTAMPTZ NOT NULL | Pago | RF-03-D02 |
| fecha_confirmacion | TIMESTAMPTZ | Pago | RF-03-D02 |
| borrador_id | UUID PK | BorradorTramite | RF-03-D03 |
| tramite_id | FK → Tramite | BorradorTramite | RF-03-D03 |
| paso_actual | SMALLINT NOT NULL | BorradorTramite | RF-03-D03 |
| datos_parciales | JSONB | BorradorTramite | RF-03-D03 |
| fecha_guardado | TIMESTAMPTZ NOT NULL | BorradorTramite | RF-03-D03 |
| fecha_expiracion | TIMESTAMPTZ | BorradorTramite | RF-03-D03 / RNF-03-D02 [PREGUNTA ABIERTA] |
| adjunto_id | UUID PK | AdjuntoTramite | RF-03-D04 |
| tramite_id | FK → Tramite | AdjuntoTramite | RF-03-D04 |
| nombre_original | VARCHAR(500) NOT NULL | AdjuntoTramite | RF-03-D04 |
| tipo_mime_declarado | VARCHAR(100) | AdjuntoTramite | RF-03-D04 |
| tipo_mime_real | VARCHAR(100) | AdjuntoTramite | RF-03-D04 |
| tamano_bytes | BIGINT NOT NULL | AdjuntoTramite | RF-03-D04 |
| estado_antivirus | VARCHAR(20) CHECK IN ('pendiente','limpio','infectado') | AdjuntoTramite | RF-03-D04 / RN-03-D04 |
| url_almacenamiento | VARCHAR(2000) | AdjuntoTramite | RF-03-D04 |
| subsanacion_id | UUID PK | Subsanacion | RF-03-D05 |
| tramite_id | FK → Tramite | Subsanacion | RF-03-D05 |
| descripcion_requerimiento | TEXT NOT NULL | Subsanacion | RF-03-D05 |
| fecha_solicitud | TIMESTAMPTZ NOT NULL | Subsanacion | RF-03-D05 |
| fecha_limite_respuesta | TIMESTAMPTZ | Subsanacion | RF-03-D05 / RN-03-D05 |
| fecha_respuesta_ciudadano | TIMESTAMPTZ | Subsanacion | RF-03-D05 |
| estado_subsanacion | VARCHAR(20) CHECK IN ('pendiente','respondida','desistida') | Subsanacion | RF-03-D05 |
| falla_id | UUID PK | FallaTecnicaInterop | RF-03-D06 |
| tramite_id | FK → Tramite | FallaTecnicaInterop | RF-03-D06 |
| sistema_destino | VARCHAR(100) | FallaTecnicaInterop | RF-03-D06 |
| detalle_error | TEXT | FallaTecnicaInterop | RF-03-D06 |
| fecha_falla | TIMESTAMPTZ NOT NULL | FallaTecnicaInterop | RF-03-D06 / RN-03-D06 |
| carga_manual_habilitada | BOOLEAN NOT NULL DEFAULT FALSE | FallaTecnicaInterop | RF-03-D06 / RN-03-D06 |
| desistimiento_id | UUID PK | Desistimiento | RF-03-D07 |
| tramite_id | FK → Tramite UNIQUE | Desistimiento | RF-03-D07 / RN-03-D07 |
| fecha_desistimiento | TIMESTAMPTZ NOT NULL | Desistimiento | RF-03-D07 |
| motivo | TEXT | Desistimiento | RF-03-D07 |
| politica_reembolso_aplicada | TEXT | Desistimiento | RF-03-D07 / RN-03-D07 [PREGUNTA ABIERTA] |
| pqrsd_id | UUID PK | PQRSD | RF-04-D01..D05 |
| tipo_peticion | VARCHAR(30) CHECK IN ('peticion_general','peticion_informacion','peticion_consulta','peticion_copias','queja','reclamo','sugerencia','denuncia') | PQRSD | RF-04-D03 / RN-04-D01 |
| radicado | VARCHAR(50) NOT NULL UNIQUE | PQRSD | RF-04-D04 / RN-04-D07 |
| ciudadano_id | FK nullable → Ciudadano | PQRSD | RF-04-D04 / RN-04-D06 |
| anonimo | BOOLEAN NOT NULL DEFAULT FALSE | PQRSD | RF-04-D04 / RN-04-D06 |
| identidad_reservada | BOOLEAN NOT NULL DEFAULT FALSE | PQRSD | RN-04-D06 |
| objeto | VARCHAR(2000) NOT NULL | PQRSD | RF-04-D04 |
| dependencia_asignada_id | FK → Dependencia | PQRSD | RF-04-D01 |
| estado_pqrsd | VARCHAR(30) CHECK IN ('radicada','en_tramite','trasladada','prorrogada','respondida','cerrada') | PQRSD | RF-04-D02 / RN-04-D04 |
| fecha_radicacion | TIMESTAMPTZ NOT NULL | PQRSD | RF-04-D03 |
| fecha_limite_respuesta | DATE NOT NULL | PQRSD | RF-04-D03 / RN-04-D01 |
| dentro_termino | BOOLEAN | PQRSD | RF-04-D02 / RN-04-D04 |
| tipo_doc_identidad | VARCHAR(20) CHECK IN ('CC','CE','NIT','Pasaporte','TI','PEP') | PQRSD | RF-04-D04 |
| numero_doc_identidad | VARCHAR(30) | PQRSD | RF-04-D04 |
| correo_ciudadano | VARCHAR(254) | PQRSD | RF-04-D04 |
| reasignacion_id | UUID PK | ReasignacionPQRSD | RF-04-D01 |
| pqrsd_id | FK → PQRSD | ReasignacionPQRSD | RF-04-D01 |
| dependencia_origen_id | FK → Dependencia | ReasignacionPQRSD | RF-04-D01 |
| dependencia_destino_id | FK nullable → Dependencia | ReasignacionPQRSD | RF-04-D01 |
| autoridad_externa | VARCHAR(300) | ReasignacionPQRSD | RF-04-D01 / RN-04-D03 |
| fecha_traslado | TIMESTAMPTZ NOT NULL | ReasignacionPQRSD | RF-04-D01 / RN-04-D03 |
| plazo_traslado_limite | DATE NOT NULL | ReasignacionPQRSD | RN-04-D03 |
| motivo | TEXT | ReasignacionPQRSD | RF-04-D01 |
| respuesta_id | UUID PK | RespuestaPQRSD | RF-04-D02 |
| pqrsd_id | FK → PQRSD UNIQUE | RespuestaPQRSD | RF-04-D02 |
| funcionario_id | FK → UsuarioInterno | RespuestaPQRSD | RF-04-D02 |
| contenido_respuesta | TEXT NOT NULL | RespuestaPQRSD | RF-04-D02 |
| fecha_respuesta | TIMESTAMPTZ NOT NULL | RespuestaPQRSD | RF-04-D02 / RN-04-D04 |
| canal_notificacion | VARCHAR(30) CHECK IN ('correo','SMS','CCD','correo_certificado','edicto','fisico') | RespuestaPQRSD | RF-04-D05 / RN-04-D05 |
| dia_habil_id | UUID PK | CalendarioHabil | RF-04-D03 / RN-TX-D01 |
| fecha | DATE NOT NULL UNIQUE | CalendarioHabil | RN-TX-D01 |
| es_habil | BOOLEAN NOT NULL | CalendarioHabil | RN-TX-D01 |
| motivo_no_habil | VARCHAR(200) | CalendarioHabil | RN-TX-D01 |
| notif_id | UUID PK | NotificacionElectronica | RF-04-D05 / RF-12-D03 |
| entidad_tipo | VARCHAR(50) | NotificacionElectronica | RF-04-D05 / RF-12-D03 |
| entidad_id | UUID NOT NULL | NotificacionElectronica | RF-04-D05 / RF-12-D03 |
| canal | VARCHAR(30) CHECK IN ('correo','SMS','push','CCD','correo_certificado','edicto') | NotificacionElectronica | RF-04-D05 / RF-12-D03 |
| destinatario | VARCHAR(254) NOT NULL | NotificacionElectronica | RF-04-D05 |
| estado_envio | VARCHAR(20) CHECK IN ('pendiente','enviado','fallido','rebotado','entregado') | NotificacionElectronica | RF-12-D03 / RN-12-D04 |
| fecha_envio | TIMESTAMPTZ | NotificacionElectronica | RF-04-D05 |
| intentos | SMALLINT NOT NULL DEFAULT 0 | NotificacionElectronica | RF-12-D03 / RN-12-D04 |
| mecanismo_id | UUID PK | MecanismoParticipacion | RF-05-D01 |
| titulo | VARCHAR(500) NOT NULL | MecanismoParticipacion | RF-05-D01 |
| tipo | VARCHAR(50) | MecanismoParticipacion | RF-05-D01 |
| fecha_apertura | TIMESTAMPTZ NOT NULL | MecanismoParticipacion | RF-05-D01 |
| fecha_cierre | TIMESTAMPTZ NOT NULL | MecanismoParticipacion | RF-05-D01 / RN-05-D01 |
| estado_mecanismo | VARCHAR(20) CHECK IN ('borrador','abierta','cerrada','archivada') | MecanismoParticipacion | RF-05-D01 / RN-05-D01 |
| aporte_id | UUID PK | AporteParticipacion | RF-05-D01 |
| mecanismo_id | FK → MecanismoParticipacion | AporteParticipacion | RF-05-D01 |
| ciudadano_id | FK nullable → Ciudadano | AporteParticipacion | RF-05-D01 |
| contenido | TEXT NOT NULL | AporteParticipacion | RF-05-D01 |
| fecha_aporte | TIMESTAMPTZ NOT NULL | AporteParticipacion | RF-05-D01 |
| resultado_id | UUID PK | ResultadoParticipacion | RF-05-D02 |
| mecanismo_id | FK → MecanismoParticipacion UNIQUE | ResultadoParticipacion | RF-05-D02 |
| consolidado | TEXT NOT NULL | ResultadoParticipacion | RF-05-D02 |
| publicado_en | TIMESTAMPTZ | ResultadoParticipacion | RF-05-D02 |
| agenda_id | UUID PK | ConfiguracionAgenda | RF-06-D01 |
| dependencia_id | FK → Dependencia | ConfiguracionAgenda | RF-06-D01 |
| servicio_nombre | VARCHAR(200) NOT NULL | ConfiguracionAgenda | RF-06-D01 |
| activo | BOOLEAN NOT NULL DEFAULT TRUE | ConfiguracionAgenda | RF-06-D01 |
| franja_id | UUID PK | FranjaHoraria | RF-06-D01 |
| agenda_id | FK → ConfiguracionAgenda | FranjaHoraria | RF-06-D01 |
| dia_semana | SMALLINT CHECK (dia_semana BETWEEN 1 AND 7) | FranjaHoraria | RF-06-D01 |
| hora_inicio | TIME NOT NULL | FranjaHoraria | RF-06-D01 |
| hora_fin | TIME NOT NULL | FranjaHoraria | RF-06-D01 |
| cupo_maximo | SMALLINT NOT NULL CHECK (cupo_maximo > 0) | FranjaHoraria | RF-06-D01 / RN-06-D01 |
| cita_id | UUID PK | Cita | RF-06-D02 / RF-06-D03 |
| franja_id | FK → FranjaHoraria | Cita | RF-06-D01 |
| ciudadano_id | FK → Ciudadano | Cita | RF-06-D01 |
| fecha_cita | DATE NOT NULL | Cita | RF-06-D01 |
| estado_cita | VARCHAR(20) CHECK IN ('reservada','confirmada','reprogramada','cancelada','no_show','atendida') | Cita | RF-06-D02 / RF-06-D04 / RN-06-D02 |
| codigo_cita | VARCHAR(30) NOT NULL UNIQUE | Cita | RF-06-D02 |
| cita_anterior_id | FK nullable → Cita | Cita | RF-06-D02 / RN-06-D02 |
| recordatorio_id | UUID PK | RecordatorioCita | RF-06-D04 |
| cita_id | FK → Cita | RecordatorioCita | RF-06-D04 |
| fecha_envio | TIMESTAMPTZ NOT NULL | RecordatorioCita | RF-06-D04 |
| canal | VARCHAR(30) | RecordatorioCita | RF-06-D04 |
| estado_envio | VARCHAR(20) | RecordatorioCita | RF-06-D04 |
| multimedia_id | UUID PK | ContenidoMultimedia | RF-07-D01 |
| contenido_id | FK → Contenido | ContenidoMultimedia | RF-07-D01 |
| tipo | VARCHAR(20) CHECK IN ('video','audio') | ContenidoMultimedia | RF-07-D01 |
| url_archivo | VARCHAR(2000) NOT NULL | ContenidoMultimedia | RF-07-D01 |
| tiene_subtitulos | BOOLEAN NOT NULL DEFAULT FALSE | ContenidoMultimedia | RF-07-D01 / RN-07-D01 |
| url_subtitulos | VARCHAR(2000) | ContenidoMultimedia | RF-07-D01 |
| tiene_lsc | BOOLEAN NOT NULL DEFAULT FALSE | ContenidoMultimedia | RF-07-D01 / RN-07-D01 |
| es_alocucion_emergencia | BOOLEAN NOT NULL DEFAULT FALSE | ContenidoMultimedia | RF-07-D01 / RN-07-D01 |
| ses_sus_id | UUID PK | SesionSUS | RF-08-D01 / RNF-08-D01 |
| fecha_sesion | DATE NOT NULL | SesionSUS | RNF-08-D01 |
| puntaje_sus | DECIMAL(5,2) | SesionSUS | RF-08-D01 |
| tasa_exito | DECIMAL(5,2) | SesionSUS | RF-08-D01 [PREGUNTA ABIERTA umbral] |
| resultado_json | JSONB | SesionSUS | RNF-08-D01 |
| log_id | UUID PK | LogAuditoria | RF-09-D01 / RN-09-D05 |
| tipo_evento | VARCHAR(100) NOT NULL | LogAuditoria | RF-09-D01 |
| actor_id | UUID | LogAuditoria | RF-09-D01 |
| actor_tipo | VARCHAR(30) | LogAuditoria | RF-09-D01 |
| entidad_tipo | VARCHAR(50) | LogAuditoria | RF-09-D01 |
| entidad_id | UUID | LogAuditoria | RF-09-D01 |
| timestamp_evento | TIMESTAMPTZ NOT NULL | LogAuditoria | RN-TX-D02 |
| hash_anterior | VARCHAR(64) | LogAuditoria | RN-09-D05 (encadenamiento) |
| hash_propio | VARCHAR(64) NOT NULL | LogAuditoria | RN-09-D05 |
| detalle | JSONB | LogAuditoria | RF-09-D01 |
| ip_origen | INET | LogAuditoria | RF-09-D01 |
| token_rec_id | UUID PK | TokenRecuperacion | RF-09-D02 |
| usuario_id | FK → UsuarioInterno | TokenRecuperacion | RF-09-D02 |
| token_hash | VARCHAR(128) NOT NULL UNIQUE | TokenRecuperacion | RF-09-D02 / RN-09-D03 |
| expira_en | TIMESTAMPTZ NOT NULL | TokenRecuperacion | RF-09-D02 / RN-09-D03 |
| usado | BOOLEAN NOT NULL DEFAULT FALSE | TokenRecuperacion | RF-09-D02 / RN-09-D03 |
| usuario_id | UUID PK | UsuarioInterno | RF-12-D02 |
| nombre | VARCHAR(200) NOT NULL | UsuarioInterno | RF-12-D02 |
| correo | VARCHAR(254) NOT NULL UNIQUE | UsuarioInterno | RF-12-D02 |
| activo | BOOLEAN NOT NULL DEFAULT TRUE | UsuarioInterno | RF-12-D02 |
| fecha_baja | TIMESTAMPTZ | UsuarioInterno | RF-12-D02 / RN-12-D02 |
| mfa_habilitado | BOOLEAN NOT NULL DEFAULT FALSE | UsuarioInterno | RN-09-D04 |
| rol_id | UUID PK | RolPermiso | RF-09-D03 |
| nombre_rol | VARCHAR(100) NOT NULL UNIQUE | RolPermiso | RF-09-D03 |
| puede_crear | BOOLEAN NOT NULL DEFAULT FALSE | RolPermiso | RN-09-D02 |
| puede_aprobar | BOOLEAN NOT NULL DEFAULT FALSE | RolPermiso | RN-09-D02 |
| puede_administrar_usuarios | BOOLEAN NOT NULL DEFAULT FALSE | RolPermiso | RN-09-D02 |
| intercambio_id | UUID PK | IntercambioXRoad | RF-10-D01 |
| tramite_id | FK → Tramite | IntercambioXRoad | RF-10-D01 |
| servicio_destino | VARCHAR(200) NOT NULL | IntercambioXRoad | RF-10-D01 |
| resultado | VARCHAR(20) CHECK IN ('exitoso','timeout','vacio','cert_vencido','error') | IntercambioXRoad | RF-10-D01 / RN-10-D01 |
| reintentos | SMALLINT NOT NULL DEFAULT 0 | IntercambioXRoad | RF-10-D01 |
| timestamp_inicio | TIMESTAMPTZ NOT NULL | IntercambioXRoad | RF-10-D01 |
| timestamp_fin | TIMESTAMPTZ | IntercambioXRoad | RF-10-D01 |
| sello_id | UUID PK | ColaSelloTSA | RF-10-D02 |
| entidad_tipo | VARCHAR(50) NOT NULL | ColaSelloTSA | RF-10-D02 |
| entidad_id | UUID NOT NULL | ColaSelloTSA | RF-10-D02 |
| estado_sello | VARCHAR(20) CHECK IN ('pendiente','sellado','error') | ColaSelloTSA | RF-10-D02 / RN-10-D02 |
| tsa_timestamp | TIMESTAMPTZ | ColaSelloTSA | RF-10-D02 / RN-TX-D02 |
| rfc3161_token | BYTEA | ColaSelloTSA | RF-10-D02 |
| cert_id | UUID PK | CertificadoDigital | RNF-10-D01 |
| tipo | VARCHAR(30) CHECK IN ('ONAC','TLS','OCSP') | CertificadoDigital | RNF-10-D01 |
| cn | VARCHAR(500) NOT NULL | CertificadoDigital | RNF-10-D01 |
| fecha_vencimiento | DATE NOT NULL | CertificadoDigital | RNF-10-D01 / RN-10-D03 |
| dias_alerta_previos | SMALLINT NOT NULL DEFAULT 30 | CertificadoDigital | RN-10-D03 |
| dataset_id | UUID PK | Dataset | RF-11-D01 |
| titulo | VARCHAR(500) NOT NULL | Dataset | RF-11-D01 |
| frecuencia_actualizacion | VARCHAR(50) CHECK IN ('diaria','semanal','mensual','trimestral','anual','irregular') | Dataset | RF-11-D01 / RN-11-D01 |
| fecha_ultima_actualizacion | TIMESTAMPTZ | Dataset | RF-11-D01 |
| estado_dataset | VARCHAR(20) CHECK IN ('borrador','publicado','desactualizado','despublicado') | Dataset | RF-11-D01 / RN-11-D01 |
| metadatos_completos | BOOLEAN NOT NULL DEFAULT FALSE | Dataset | RF-11-D01 / RN-11-D02 |
| responsable_id | FK → UsuarioInterno | Dataset | RF-11-D01 |
| version_ds_id | UUID PK | VersionDataset | RF-11-D01 |
| dataset_id | FK → Dataset | VersionDataset | RF-11-D01 |
| numero_version | INTEGER NOT NULL | VersionDataset | RF-11-D01 |
| archivo_url | VARCHAR(2000) NOT NULL | VersionDataset | RF-11-D01 |
| formato | VARCHAR(20) CHECK IN ('CSV','JSON','XML') | VersionDataset | RF-11-D01 / RN-11-D02 |
| fecha_publicacion | TIMESTAMPTZ NOT NULL | VersionDataset | RF-11-D01 |
| contenido_id | UUID PK | Contenido | RF-12-D01 |
| tipo_contenido | VARCHAR(50) | Contenido | RF-12-D01 |
| titulo | VARCHAR(500) NOT NULL | Contenido | RF-12-D01 |
| estado_contenido | VARCHAR(30) CHECK IN ('borrador','pendiente_aprobacion','publicado','archivado') | Contenido | RF-12-D01 / RN-12-D01 |
| creado_por | FK → UsuarioInterno | Contenido | RF-12-D01 / RN-09-D02 |
| aprobado_por | FK nullable → UsuarioInterno | Contenido | RF-12-D01 / RN-09-D02 |
| publicado_en | TIMESTAMPTZ | Contenido | RF-12-D01 |
| version_edicion | INTEGER NOT NULL DEFAULT 1 | Contenido | RF-12-D01 |
| lock_usuario_id | FK nullable → UsuarioInterno | Contenido | RNF-12-D01 / RN-12-D05 |
| lock_desde | TIMESTAMPTZ | Contenido | RN-12-D05 |
| notif_mc_id | UUID PK | NotificacionMulticanal | RF-12-D03 |
| canal | VARCHAR(30) CHECK IN ('correo','SMS','push','CCD') | NotificacionMulticanal | RF-12-D03 |
| destinatario | VARCHAR(254) NOT NULL | NotificacionMulticanal | RF-12-D03 |
| estado_envio | VARCHAR(20) CHECK IN ('pendiente','enviado','fallido','rebotado') | NotificacionMulticanal | RF-12-D03 / RN-12-D04 |
| canal_alterno_usado | VARCHAR(30) | NotificacionMulticanal | RN-12-D04 |
| intentos | SMALLINT NOT NULL DEFAULT 0 | NotificacionMulticanal | RF-12-D03 |
| expediente_id | UUID PK | ExpedienteElectronico | RN-12-D06 |
| tramite_id | FK → Tramite | ExpedienteElectronico | RN-12-D06 |
| indice_firmado | BYTEA | ExpedienteElectronico | RN-12-D06 |
| numero_folios | INTEGER NOT NULL DEFAULT 0 | ExpedienteElectronico | RN-12-D06 |
| hash_integridad | VARCHAR(64) | ExpedienteElectronico | RN-12-D06 |
| traduccion_id | UUID PK | ContenidoTraduccion | RNF-TX-D02 |
| contenido_id | FK → Contenido | ContenidoTraduccion | RNF-TX-D02 |
| idioma_codigo | VARCHAR(10) NOT NULL | ContenidoTraduccion | RNF-TX-D02 / RN-TX-D05 |
| texto_traducido | TEXT | ContenidoTraduccion | RNF-TX-D02 |
| es_oficial | BOOLEAN NOT NULL DEFAULT FALSE | ContenidoTraduccion | RN-TX-D05 |

---

## 3. Reglas de negocio como restricciones de integridad

| RN-id | Condición | Restricción SQL candidata | Entidad/columna afectada | Fuente |
|---|---|---|---|---|
| RN-01-D01 | Consentimiento caduca si `fecha_expiracion < NOW()` o si cambia la versión de política | `CHECK (fecha_expiracion = fecha_consentimiento + INTERVAL '12 months')` + trigger de invalidación ante nueva versión_politica | ConsentimientoCookies.estado, fecha_expiracion | rn-delta-profundo.md:16 |
| RN-01-D02 | Menú principal: máx 7 ítems de nivel 1 por menú, máx 2 niveles de profundidad | Trigger/CHECK: `COUNT(*) WHERE nivel=1 AND menu_id=X <= 7`; `CHECK (nivel IN (1,2))`; FK a padre solo si nivel=2 | ItemMenu.nivel, ItemMenu.padre_item_id | rn-delta-profundo.md:17 |
| RN-01-D03 | Modal de salida NO se dispara para dominios en lista blanca | No es una restricción de BD sino de aplicación, pero la tabla DominioBlancoExterno es la fuente. `UNIQUE (dominio)` | DominioBlancoExterno.dominio | rn-delta-profundo.md:18 |
| RN-02-D01 | Reemplazar un documento mantiene URL permanente y conserva versión anterior | `UNIQUE (url_permanente)` en DocumentoTransparencia; VersionDocumento no puede borrar registros históricos (política de retención) | DocumentoTransparencia.url_permanente, VersionDocumento | rn-delta-profundo.md:26 |
| RN-02-D02 | Publicaciones obligatorias generan alerta N días antes del plazo legal | `CHECK (plazo_legal_fecha IS NOT NULL)` para documentos de tipo obligatorio; job programado por BD | DocumentoTransparencia.plazo_legal_fecha | rn-delta-profundo.md:27 |
| RN-02-D03 | Caída de integración externa NO equivale a vínculo roto si hay mensaje de indisponibilidad | `CHECK (resultado IN ('exitosa','fallida'))` + log obligatorio; no FK a estado de servicio externo | SincronizacionExterna.resultado | rn-delta-profundo.md:28 |
| RN-02-D04 | Directorio desactualizado si sincronización SIGEP > 24 h sin éxito | Cardinalidad: cada SincronizacionExterna registra timestamp; lógica de alerta en capa aplicación con query: `MAX(fecha_intento) WHERE resultado='exitosa' AND sistema='SIGEP' < NOW() - INTERVAL '24h'` | SincronizacionExterna.fecha_intento, resultado | rn-delta-profundo.md:29 |
| RN-03-D01 | Idempotencia de pago y radicado: token único, un solo cobro/radicado | `UNIQUE (token_valor)` en TokenIdempotencia; `UNIQUE (radicado)` en Tramite; FK de Pago a TokenIdempotencia con ON DELETE RESTRICT | TokenIdempotencia.token_valor, Tramite.radicado, Pago.token_idempotencia_id | rn-delta-profundo.md:37 |
| RN-03-D02 | Trámite avanza solo con pago `aprobado` | `CHECK (estado_tramite != 'en_proceso' OR pago_aprobado = TRUE)` [INFERIDO]; trigger que valida estado_pago='aprobado' antes de cambiar estado_tramite a etapa siguiente | Tramite.estado_tramite, Pago.estado_pago | rn-delta-profundo.md:38 |
| RN-03-D03 | Sin recargo de pasarela para el ciudadano | No es restricción de esquema sino de negocio/UI. `CHECK (monto_recargo = 0)` si se modela el campo | Pago (campo recargo si existe) | rn-delta-profundo.md:39 |
| RN-03-D04 | Adjunto de trámite con MIME falso o malware se rechaza | `CHECK (estado_antivirus IN ('pendiente','limpio','infectado'))`; lógica de aplicación bloquea publicación si `infectado`; FK de AdjuntoTramite a Tramite ON DELETE CASCADE | AdjuntoTramite.estado_antivirus, tipo_mime_real | rn-delta-profundo.md:40 |
| RN-03-D05 | Subsanación: plazo se recalcula desde entrega de lo requerido | `CHECK (fecha_limite_respuesta > fecha_solicitud)`; trigger recalcula fecha_limite en Tramite al actualizar fecha_respuesta_ciudadano | Subsanacion.fecha_limite_respuesta | rn-delta-profundo.md:41 |
| RN-03-D06 | Excepción a interoperabilidad solo con falla técnica documentada | `CHECK (carga_manual_habilitada = TRUE AND detalle_error IS NOT NULL)` cuando carga_manual_habilitada es TRUE | FallaTecnicaInterop.carga_manual_habilitada, detalle_error | rn-delta-profundo.md:42 |
| RN-03-D07 | Desistimiento: un solo desistimiento por trámite, estado pasa a 'desistido' | `UNIQUE (tramite_id)` en Desistimiento; trigger actualiza Tramite.estado_tramite='desistido' | Desistimiento.tramite_id, Tramite.estado_tramite | rn-delta-profundo.md:43 |
| RN-03-D08 | Silencio administrativo positivo si entidad no resuelve en término | Campo `estado_tramite` incluye valor 'silencio_positivo'; trigger/job compara fecha_limite con NOW() y aplica si corresponde según tipo_tramite | Tramite.estado_tramite, fecha_limite [INFERIDO campo] | rn-delta-profundo.md:44 |
| RN-04-D01 | Plazos diferenciados por tipo de petición en días hábiles | `CHECK (tipo_peticion IN ('peticion_general','peticion_informacion','peticion_consulta','peticion_copias','queja','reclamo','sugerencia','denuncia'))`; función de BD que calcula fecha_limite_respuesta usando CalendarioHabil según tipo | PQRSD.tipo_peticion, fecha_limite_respuesta | rn-delta-profundo.md:52 |
| RN-04-D02 | Prórroga informada antes del vencimiento, no puede exceder límite legal | Campo prorrogada en estado_pqrsd; nueva fecha_limite_respuesta; `CHECK (nueva_fecha > fecha_original AND nueva_fecha <= limite_legal)` [INFERIDO campo limite_legal] | PQRSD.estado_pqrsd, fecha_limite_respuesta | rn-delta-profundo.md:53 |
| RN-04-D03 | Traslado por competencia dentro de 5 días hábiles | `CHECK (plazo_traslado_limite = fecha_traslado + 5_dias_habiles)` (calculado con CalendarioHabil); FK de ReasignacionPQRSD a PQRSD ON DELETE RESTRICT | ReasignacionPQRSD.plazo_traslado_limite, fecha_traslado | rn-delta-profundo.md:54 |
| RN-04-D04 | PQRSD no se cierra sin respuesta registrada | `CHECK` en transición de estado: estado='cerrada' solo si EXISTS(SELECT 1 FROM RespuestaPQRSD WHERE pqrsd_id=X); trigger de BD | PQRSD.estado_pqrsd, RespuestaPQRSD | rn-delta-profundo.md:55 |
| RN-04-D05 | Notificación electrónica preferente cuando existe dirección procesal electrónica | `CHECK (canal IN ('correo','SMS','CCD','correo_certificado','edicto','fisico'))` en RespuestaPQRSD; lógica de selección de canal en aplicación | RespuestaPQRSD.canal_notificacion | rn-delta-profundo.md:56 |
| RN-04-D06 | Identidad reservada: datos del peticionario no expuestos | `CHECK (anonimo=TRUE OR identidad_reservada=TRUE → ciudadano_id IS NULL)` [INFERIDO]; columna ciudadano_id nullable con FK | PQRSD.ciudadano_id, anonimo, identidad_reservada | rn-delta-profundo.md:57 |
| RN-04-D07 | Radicado único y atómico bajo concurrencia | `UNIQUE (radicado)` en PQRSD; generación mediante secuencia PostgreSQL (`SEQUENCE`) garantiza atomicidad | PQRSD.radicado | rn-delta-profundo.md:58 |
| RN-04-D08 | Consulta de información pública gratuita; reproducción de copias al costo | No es restricción de esquema; `CHECK (costo_copias >= 0)` si se modela campo de cobro de copias | PQRSD (campo costo si existe) | rn-delta-profundo.md:59 |
| RN-05-D01 | Cierre automático de consulta al vencer fecha_cierre; no se aceptan aportes extemporáneos | `CHECK (fecha_aporte <= (SELECT fecha_cierre FROM MecanismoParticipacion WHERE id=X))`; trigger o job que actualiza estado_mecanismo='cerrada' | MecanismoParticipacion.estado_mecanismo, AporteParticipacion.fecha_aporte | rn-delta-profundo.md:67 |
| RN-05-D02 | Publicación de resultado de consulta normativa obligatoria | ResultadoParticipacion con FK UNIQUE a MecanismoParticipacion; `NOT NULL` en consolidado | ResultadoParticipacion.consolidado, mecanismo_id | rn-delta-profundo.md:68 |
| RN-06-D01 | Reserva atómica: cupo no compartido entre dos reservas concurrentes | `SELECT ... FOR UPDATE` en FranjaHoraria.cupo_maximo; trigger cuenta Cita activas por (franja_id, fecha_cita) y rechaza si >= cupo_maximo; índice parcial UNIQUE no aplica directamente, usar constraint via trigger | FranjaHoraria.cupo_maximo, Cita | rn-delta-profundo.md:76 |
| RN-06-D02 | Cancelación/reprogramación libera cupo anterior | Trigger ON UPDATE Cita: cuando estado_cita pasa a 'cancelada' o 'reprogramada', decrementa contador de reservas activas; cita_anterior_id registra la previa | Cita.estado_cita, Cita.cita_anterior_id | rn-delta-profundo.md:77 |
| RN-06-D03 | Siempre debe existir vía presencial/telefónica equivalente | Restricción de diseño arquitectónico, no de esquema BD. Documentada como invariante de negocio | ConfiguracionAgenda | rn-delta-profundo.md:78 |
| RN-07-D01 | CMS bloquea publicación de video sin subtítulos; alocución/emergencia exige también LSC | `CHECK (tiene_subtitulos = TRUE)` como condición para estado_contenido='publicado' cuando tipo='video'; `CHECK (tiene_lsc = TRUE OR es_alocucion_emergencia = FALSE)` | ContenidoMultimedia.tiene_subtitulos, tiene_lsc | rn-delta-profundo.md:86 |
| RN-07-D02 | Captcha debe ofrecer alternativa accesible (audio/no visual) | Restricción funcional no de esquema BD. Documentada como invariante | — | rn-delta-profundo.md:87 |
| RN-08-D01 | SUS >= 68 + umbral de tasa de éxito y tiempo en tarea | `CHECK (puntaje_sus >= 0 AND puntaje_sus <= 100)`; umbral de tasa_exito a definir [PREGUNTA ABIERTA] | SesionSUS.puntaje_sus, tasa_exito | rn-delta-profundo.md:95 |
| RN-09-D01 | Ciudadano accede solo a SUS propios recursos (anti-IDOR) | No es constraint de BD sino control de acceso en capa aplicación; todos los registros de Tramite/PQRSD/Cita incluyen ciudadano_id con FK y se filtra por él | Tramite.ciudadano_id, PQRSD.ciudadano_id, Cita.ciudadano_id | rn-delta-profundo.md:103 |
| RN-09-D02 | Segregación de funciones: creador ≠ aprobador; admin-usuarios no audita sus propias acciones | `CHECK (creado_por <> aprobado_por)` en Contenido; restricción de roles: si puede_crear=TRUE entonces puede_aprobar=FALSE en RolPermiso; `CHECK (puede_crear = FALSE OR puede_aprobar = FALSE)` | Contenido.creado_por / aprobado_por; RolPermiso.puede_crear / puede_aprobar | rn-delta-profundo.md:104 |
| RN-09-D03 | Token de recuperación de un solo uso, expira (ej. 15 min), restablece sesiones activas | `CHECK (expira_en > fecha_creacion)` [INFERIDO campo]; `UNIQUE (token_hash)`; trigger marca usado=TRUE al consumir y revoca sesiones activas del usuario | TokenRecuperacion.usado, expira_en, token_hash | rn-delta-profundo.md:105 |
| RN-09-D04 | MFA obligatorio para administradores del CMS | `CHECK (mfa_habilitado = TRUE)` para usuarios con roles privilegiados; trigger o constraint aplicado via política de rol | UsuarioInterno.mfa_habilitado | rn-delta-profundo.md:106 |
| RN-09-D05 | Log de auditoría append-only, inmutable, encadenado por hash, retención 5 años | Sin DELETE/UPDATE sobre LogAuditoria (política de BD: REVOKE DELETE, UPDATE ON log_auditoria); encadenamiento: hash_propio = SHA256(contenido || hash_anterior); `NOT NULL (hash_propio)` | LogAuditoria.hash_anterior, hash_propio | rn-delta-profundo.md:107 |
| RN-09-D06 | Caída del OIDC no bloquea contenido público; state inválido en callback se rechaza | Restricción funcional, no de esquema BD | — | rn-delta-profundo.md:108 |
| RN-09-D07 | Umbral local de incidente "grave" para CSIRT ≤ 24 h | [PREGUNTA ABIERTA] campo en LogAuditoria o tabla Incidente; cuando se defina, `CHECK (nivel_gravedad IN ('bajo','medio','alto','critico'))` | LogAuditoria / Incidente [INFERIDO] | rn-delta-profundo.md:109 |
| RN-10-D01 | No dejar trámite en estado indefinido ante error X-Road | `CHECK (resultado IN ('exitoso','timeout','vacio','cert_vencido','error'))` en IntercambioXRoad; Tramite.estado_tramite nunca queda NULL | IntercambioXRoad.resultado, Tramite.estado_tramite | rn-delta-profundo.md:117 |
| RN-10-D02 | Ningún mensaje se cierra sin estampa TSA | `CHECK (estado_sello IN ('pendiente','sellado','error'))`; política: entidades no se cierran si EXISTS registro en ColaSelloTSA con estado='pendiente' | ColaSelloTSA.estado_sello | rn-delta-profundo.md:118 |
| RN-10-D03 | Alerta anticipada de vencimiento de certificados | `CHECK (dias_alerta_previos > 0)` en CertificadoDigital; job: `WHERE fecha_vencimiento <= NOW() + dias_alerta_previos * INTERVAL '1 day'` | CertificadoDigital.fecha_vencimiento, dias_alerta_previos | rn-delta-profundo.md:119 |
| RN-11-D01 | Dataset desactualizado si supera frecuencia declarada + margen | `CHECK (frecuencia_actualizacion IN ('diaria','semanal','mensual','trimestral','anual','irregular'))`; job actualiza estado_dataset='desactualizado' según frecuencia | Dataset.estado_dataset, frecuencia_actualizacion, fecha_ultima_actualizacion | rn-delta-profundo.md:127 |
| RN-11-D02 | Validación de calidad antes de federar: formato bien formado + metadatos completos | `CHECK (metadatos_completos = TRUE)` como condición para estado_dataset='publicado'; `CHECK (formato IN ('CSV','JSON','XML'))` | Dataset.metadatos_completos, VersionDataset.formato | rn-delta-profundo.md:128 |
| RN-12-D01 | Ciclo de vida editorial: Borrador → Pendiente → Publicado → Archivado; solo Aprobado se publica | `CHECK (estado_contenido IN ('borrador','pendiente_aprobacion','publicado','archivado'))`; trigger de transición válida; 'publicado' requiere aprobado_por IS NOT NULL AND aprobado_por <> creado_por | Contenido.estado_contenido | rn-delta-profundo.md:136 |
| RN-12-D02 | Baja segura: acceso revocado ≤ 1 día hábil; no borrado físico, eventos de auditoría permanecen | `UPDATE UsuarioInterno SET activo=FALSE, fecha_baja=NOW()` (nunca DELETE); `CHECK (fecha_baja IS NOT NULL OR activo=TRUE)` | UsuarioInterno.activo, fecha_baja | rn-delta-profundo.md:137 |
| RN-12-D03 | Registro menor de 18 años exige autorización del representante legal | `CHECK (fecha_nacimiento IS NULL OR (fecha_nacimiento > NOW() - INTERVAL '18 years' → autorizacion_representante=TRUE))` [INFERIDO campo en Ciudadano] | Ciudadano.fecha_nacimiento, autorizacion_representante | rn-delta-profundo.md:138 |
| RN-12-D04 | Rebote de notificación no equivale a notificado; reintento y/o canal alterno | `CHECK (estado_envio IN ('pendiente','enviado','fallido','rebotado','entregado'))`; 'rebotado' dispara reintento automático; `NOT NULL (intentos)` | NotificacionMulticanal.estado_envio, intentos | rn-delta-profundo.md:139 |
| RN-12-D05 | Bloqueo de edición concurrente (optimista/pesimista) | lock_usuario_id + lock_desde en Contenido; versión optimista: `CHECK` sobre version_edicion en UPDATE (OCC); pesimista: `SELECT FOR UPDATE` | Contenido.lock_usuario_id, lock_desde, version_edicion | rn-delta-profundo.md:140 |
| RN-12-D06 | Expediente con índice firmado, foliado consecutivo, sin inserción/retiro sin trazabilidad | `NOT NULL (indice_firmado, hash_integridad)`; trigger audita toda modificación al expediente; folio consecutivo via SEQUENCE | ExpedienteElectronico | rn-delta-profundo.md:141 |
| RN-TX-D01 | Todos los cómputos de plazos usan CalendarioHabil (fuente única) | Función de BD `f_dias_habiles(fecha_inicio, n_dias)` que consulta CalendarioHabil.es_habil; toda FK de plazos legales usa esta función | CalendarioHabil; toda entidad con fecha_limite_respuesta | rn-delta-profundo.md:149 |
| RN-TX-D02 | Toda evidencia con valor legal usa Hora Legal Colombia (INM) + TSA RFC 3161 | `CHECK (timestamp_evento AT TIME ZONE 'America/Bogota')` [INFERIDO]; todos los TIMESTAMPTZ almacenan en UTC y convierten a zona horaria de Colombia; ColaSelloTSA vincula con entidad | LogAuditoria.timestamp_evento, ColaSelloTSA | rn-delta-profundo.md:150 |
| RN-TX-D03 | Retención y purga de datos personales según TRD | [PREGUNTA ABIERTA] períodos por categoría. Campo `fecha_expiracion_retencion` [INFERIDO] en BorradorTramite, ConsentimientoCookies, LogAuditoria | Múltiples entidades | rn-delta-profundo.md:151 |
| RN-TX-D04 | Nivel de autenticación mínimo por tipo de trámite | [PREGUNTA ABIERTA] matriz trámite↔nivel. `CHECK (nivel_autenticacion_requerido IN ('bajo','medio','alto','muy_alto'))` en Tramite | Tramite.nivel_autenticacion_requerido | rn-delta-profundo.md:152 |
| RN-TX-D05 | Fallback al castellano si no hay traducción disponible | `CHECK (idioma_codigo IS NOT NULL)` en ContenidoTraduccion; lógica de aplicación busca traducción o devuelve Contenido.texto original; `UNIQUE (contenido_id, idioma_codigo)` | ContenidoTraduccion | rn-delta-profundo.md:153 |

---

## 4. Máquinas de estado / dominios enumerados

| Entidad | Estados | Transiciones válidas | Fuente |
|---|---|---|---|
| ConsentimientoCookies | `activo` → `caducado` (tras 12 meses o cambio de política) → `revocado` (por el usuario) | activo→caducado (automática); activo/caducado→revocado (usuario); caducado→activo (nuevo consentimiento) | RF-01-D01 / RN-01-D01 |
| ItemMenu | `publicado` → `despublicado` | Bidireccional | RF-01-D02 / RN-01-D02 |
| Tramite | `borrador` → `en_proceso` → `en_espera_pago` → `en_espera_subsanacion` → `resuelto`; ramas: `desistido`, `silencio_positivo` | borrador→en_proceso; en_proceso→en_espera_pago (si pago requerido); en_espera_pago→en_proceso (pago aprobado); cualquier estado antes de resolución→en_espera_subsanacion; en_espera_subsanacion→estado_anterior (subsanación recibida); en_proceso/en_espera_subsanacion→desistido; en_proceso→silencio_positivo (vencimiento sin resolución) | RF-03-D01..D07 / RN-03-D02/D07/D08 |
| Pago | `pendiente` → `aprobado` / `rechazado` / `fallido` | pendiente→aprobado (confirmación pasarela); pendiente→rechazado/fallido (respuesta negativa); no hay retorno desde aprobado | RF-03-D02 / RN-03-D02 |
| BorradorTramite | Implícito: `activo` → `expirado` / `convertido_en_tramite` | Expiración automática por fecha_expiracion; conversión al avanzar el trámite | RF-03-D03 / RNF-03-D02 [PREGUNTA ABIERTA plazo] |
| AdjuntoTramite | `pendiente` (antivirus) → `limpio` / `infectado` | pendiente→limpio (análisis OK); pendiente→infectado (malware); infectado bloquea avance | RF-03-D04 / RN-03-D04 |
| Subsanacion | `pendiente` → `respondida` / `desistida` | pendiente→respondida (ciudadano carga documento); pendiente→desistida (no responde en plazo) | RF-03-D05 / RN-03-D05 |
| PQRSD | `radicada` → `en_tramite` → `trasladada` / `prorrogada` / `respondida` → `cerrada` | radicada→en_tramite; en_tramite→trasladada (competencia ajena); en_tramite→prorrogada (ampliación); en_tramite/prorrogada→respondida; respondida→cerrada | RF-04-D01..D05 / RN-04-D01..D04 |
| MecanismoParticipacion | `borrador` → `abierta` → `cerrada` → `archivada` | borrador→abierta (publicación); abierta→cerrada (fecha_cierre vencida, automático); cerrada→archivada | RF-05-D01 / RN-05-D01 |
| Cita | `reservada` → `confirmada` → `atendida` / `no_show`; ramas: `cancelada`, `reprogramada` | reservada→confirmada; confirmada→atendida / no_show; reservada/confirmada→cancelada; reservada/confirmada→reprogramada | RF-06-D02 / RF-06-D04 / RN-06-D02 |
| Contenido | `borrador` → `pendiente_aprobacion` → `publicado` → `archivado`; rechazo: `pendiente_aprobacion` → `borrador` | borrador→pendiente_aprobacion (envío a revisión); pendiente_aprobacion→publicado (aprobación); pendiente_aprobacion→borrador (rechazo con comentario); publicado→archivado | RF-12-D01 / RN-12-D01 |
| Dataset | `borrador` → `publicado` → `desactualizado` → `publicado` (actualización); `despublicado` desde cualquier estado | borrador→publicado (si metadatos_completos); publicado→desactualizado (job por frecuencia vencida); desactualizado→publicado (actualización cargada); cualquier→despublicado | RF-11-D01 / RN-11-D01 |
| ColaSelloTSA | `pendiente` → `sellado` / `error` | pendiente→sellado (TSA disponible); pendiente→error (falla persistente) | RF-10-D02 / RN-10-D02 |
| NotificacionMulticanal / NotificacionElectronica | `pendiente` → `enviado` → `entregado` / `rebotado` / `fallido` | pendiente→enviado; enviado→entregado/rebotado/fallido; rebotado→pendiente (reintento canal alterno) | RF-12-D03 / RN-12-D04 |
| IntercambioXRoad | `exitoso` / `timeout` / `vacio` / `cert_vencido` / `error` (estados terminales por intercambio) | Cada IntercambioXRoad es inmutable al crearse; no hay transiciones, es log de evento | RF-10-D01 / RN-10-D01 |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Entidad afectada | Justificación |
|---|---|---|---|
| I-01 | Entidad `Ciudadano` existe como supertipo con campos: ciudadano_id, nombre, correo, fecha_nacimiento, tipo_doc, numero_doc, autorizacion_representante (boolean para menores). No aparece en este delta pero es referenciada por Tramite, PQRSD, Cita, AporteParticipacion, ConsentimientoCookies. | Ciudadano | RF-03-D01, RF-04-D04, RF-06-D01, RF-12-D04 |
| I-02 | Entidad `Dependencia` existe con dependencia_id, nombre, código. Referenciada por PQRSD, ReasignacionPQRSD, ConfiguracionAgenda. | Dependencia | RF-04-D01, RF-06-D01 |
| I-03 | Campo `fecha_nacimiento` en Ciudadano necesario para RN-12-D03 (verificación de edad < 18 años). | Ciudadano.fecha_nacimiento | RN-12-D03 / RF-12-D04 |
| I-04 | Campo `autorizacion_representante` (BOOLEAN) en Ciudadano o en una entidad `AutorizacionRepresentante` separada para cumplir Ley 1581 Art.7. | Ciudadano / AutorizacionRepresentante | RN-12-D03 |
| I-05 | Tabla `IncidenteSeguridad` con campos: incidente_id, nivel_gravedad, descripcion, fecha_deteccion, reportado_csirt (BOOLEAN), fecha_reporte. Necesaria para RN-09-D07 una vez se defina el umbral [PREGUNTA ABIERTA]. | IncidenteSeguridad | RN-09-D07 |
| I-06 | Campo `fecha_creacion` en TokenRecuperacion (para calcular expiración de 15 min). | TokenRecuperacion.fecha_creacion | RN-09-D03 |
| I-07 | Campo `limite_legal_prorroga` en PQRSD para validar que la prórroga no excede el tope legal (RN-04-D02). | PQRSD | RN-04-D02 |
| I-08 | Entidad `ProrrogaPQRSD` con: prorroga_id, pqrsd_id, fecha_prorroga, nueva_fecha_limite, notificado_ciudadano (BOOLEAN). Alternativa a campo en PQRSD para conservar historial de prórrogas. | ProrrogaPQRSD | RN-04-D02 |
| I-09 | `SesionSUS` probablemente referencia un ciclo de evaluación identificado por módulo o tarea. Campo `modulo_evaluado` o `tarea_evaluada` [INFERIDO]. | SesionSUS | RF-08-D01 |
| I-10 | `ContenidoTraduccion` requiere `UNIQUE (contenido_id, idioma_codigo)` para evitar duplicados por idioma. | ContenidoTraduccion | RN-TX-D05 |
| I-11 | Política de retención en tabla `PoliticaRetencion` con: entidad_nombre, periodo_retencion_dias, accion_vencimiento CHECK IN ('purgar','anonimizar'). [PREGUNTA ABIERTA] períodos a definir. | PoliticaRetencion | RN-TX-D03 |
| I-12 | `NotificacionElectronica` y `NotificacionMulticanal` pueden unificarse en una sola tabla polimórfica con entidad_tipo+entidad_id, dado que tienen la misma estructura. Decisión de diseño pendiente de análisis de cardinalidades con módulos base. | NotificacionElectronica / NotificacionMulticanal | RF-04-D05, RF-12-D03 |
| I-13 | `AlertaCumplimiento` referencia un tipo de publicación y un responsable; podría ser una vista materializada sobre DocumentoTransparencia donde `plazo_legal_fecha - NOW() <= N días`. | AlertaCumplimiento | RF-02-D02 / RN-02-D02 |

---

**Preguntas abiertas pendientes de resolución antes de diseño físico:**
1. RNF-03-D02: tiempo de retención de borradores de trámite.
2. RNF-04-D01: estructura del número de radicado (prefijo + año + consecutivo).
3. RN-08-D01: umbral de tasa de éxito de tareas para SUS.
4. RN-09-D07: umbral local de incidente "grave" para reporte CSIRT.
5. RN-TX-D03: períodos de retención/purga por categoría de dato.
6. RN-TX-D04: matriz trámite ↔ nivel de autenticación exigido.
7. RN-TX-D05: qué contenidos se traducen y tratamiento de lenguas étnicas.
8. RN-03-D07: política de reembolso cuando trámite pagado se desiste.
9. RN-03-D08: qué trámites están sujetos a silencio administrativo positivo y con qué término.

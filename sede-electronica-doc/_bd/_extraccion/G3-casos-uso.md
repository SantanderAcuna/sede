# Extracción BD — Global G3 Casos de Uso

## 0. Cobertura

**2 unidades íntegras: SÍ; líneas 861 (605 + 256)**

| Archivo | Rango leído | Líneas | Estado |
|---|---|---|---|
| `_global/casos-uso-detallados.md` | 1–605 | 605 | Íntegra, 0 omitidas |
| `_global/uc-delta-profundo.md` | 1–256 | 256 | Íntegra, 0 omitidas |

Total UC documentados: **50** (UC-001 a UC-050). 29 consolidados de bundles + 21 UC secundarios nuevos (12 inferidos en base + 9 del delta profundo).

---

## 1. Entidades candidatas que aparecen en UC

| # | Entidad candidata | UC fuente (primarios) | ¿CANDIDATA-COMPARTIDA? |
|---|---|---|---|
| 1 | **PQRSD** (Petición/Queja/Reclamo/Sugerencia/Denuncia/Solicitud) | UC-001, UC-002, UC-035, UC-036, UC-037, UC-046 | SÍ — compartida con módulos back-office |
| 2 | **Radicado** (número único de radicación) | UC-001, UC-002, UC-004, UC-035, UC-036 | SÍ — compartida con SGDEA |
| 3 | **Trámite** (ficha de trámite catalogado) | UC-003, UC-004, UC-026, UC-042, UC-043, UC-044, UC-045 | SÍ — compartida con SUIT/GOV.CO |
| 4 | **TramiteInstancia** (ejecución concreta de un trámite por ciudadano) | UC-004, UC-007, UC-042, UC-043, UC-044, UC-045 | SÍ — entidad de ciclo de vida |
| 5 | **Ciudadano** (persona natural registrada o anónima) | UC-001, UC-002, UC-004..UC-016, UC-030..UC-038 | SÍ — compartida transversal |
| 6 | **PerfilCiudadano** (cuenta registrada en la sede) | UC-006, UC-033, UC-034, UC-038 | SÍ |
| 7 | **Sesion** (sesión activa de usuario) | UC-005, UC-017, UC-038 | SÍ |
| 8 | **Pago** (transacción de pago de trámite) | UC-007 | SÍ — compartida con pasarela PSE |
| 9 | **Cita** (cita de atención presencial agendada) | UC-011, UC-032, UC-047 | SÍ |
| 10 | **AgendaDisponibilidad** (franjas/cupos de cita configurados por admin) | UC-047, UC-011 | SÍ |
| 11 | **Notificacion** (notificación multicanal enviada al ciudadano) | UC-008, UC-036, UC-042, UC-046 | SÍ — compartida |
| 12 | **Contenido** (documento/página publicable en el CMS) | UC-018, UC-041, UC-048, UC-050 | SÍ |
| 13 | **VersionContenido** (versión histórica de un contenido) | UC-048, UC-050 | SÍ |
| 14 | **Dataset** (conjunto de datos abiertos) | UC-013, UC-025, UC-049 | SÍ |
| 15 | **VersionDataset** (versión histórica de un dataset) | UC-049 | SÍ |
| 16 | **NormativaDocumento** (norma institucional publicada) | UC-012, UC-018 | SÍ |
| 17 | **UsuarioInterno** (administrador/editor/funcionario del CMS) | UC-017, UC-018, UC-020, UC-035, UC-036, UC-039, UC-041, UC-048 | SÍ |
| 18 | **Rol** (rol de sistema: admin, editor, funcionario, etc.) | UC-017, UC-020 | SÍ — compartida transversal |
| 19 | **ConsentimientoCookies** (preferencia de cookies del ciudadano) | UC-016 | SÍ |
| 20 | **DerechoARCO** (solicitud de derecho de datos personales) | UC-010, UC-033 | SÍ |
| 21 | **Expediente** (expediente en SGDEA asociado a radicado/trámite) | UC-001, UC-004, UC-035, UC-036, UC-041, UC-043 | SÍ — compartida con SGDEA |
| 22 | **FirmaElectronica** (firma digital de acto administrativo) | UC-039 | SÍ |
| 23 | **AuditLog** (log inmutable de eventos del sistema) | UC-017, UC-018, UC-020, UC-041, UC-048 | SÍ — compartida transversal |
| 24 | **IncidenteSeguridad** (incidente de seguridad TI) | UC-022 | SÍ |
| 25 | **EncuestaSUS** (respuesta de encuesta de usabilidad) | UC-023 | SÍ |
| 26 | **ResultadoAccesibilidad** (validación de accesibilidad de página) | UC-024 | SÍ |
| 27 | **VerificacionITA** (ítem de cumplimiento del índice ITA) | UC-021 | SÍ |
| 28 | **ConsultaCiudadanaNorma** (participación en elaboración de norma) | UC-014 | SÍ — vía SUCOP |
| 29 | **CarpetaCiudadanaDigital** (agregado de datos del ciudadano en GOV.CO) | UC-009 | SÍ — externa GOV.CO |
| 30 | **CalendarioHabil** (días hábiles y festivos del Distrito) | UC-035, UC-036, UC-046, UC-042 | SÍ — compartida transversal |
| 31 | **BorradorTramite** (estado parcialmente guardado de un trámite en curso) | UC-045, UC-004 | SÍ |
| 32 | **Adjunto** (documento adjunto a PQRSD o trámite) | UC-001, UC-042 | SÍ |
| 33 | **SilencioAdministrativoPositivo** (registro SAP de trámite vencido sin resolución) | UC-044 | SÍ |
| 34 | **ProrrogaPQRSD** (prórroga del término de una PQRSD) | UC-046 | SÍ |
| 35 | **TokenRecuperacion** (token de restablecimiento de contraseña) | UC-031 | SÍ |
| 36 | **ConsentimientoDatos** (autorización de tratamiento de datos personales) | UC-006, UC-033, UC-016 | SÍ |
| 37 | **ServicioXRoad** (servicio registrado en el bus X-Road) | UC-027, UC-029 | SÍ |
| 38 | **IntercambioXRoad** (log de intercambio de información vía X-Road con TSA) | UC-029 | SÍ |
| 39 | **ClaveidempotenciaPago** (clave única por intento de pago) | UC-007 | SÍ |
| 40 | **AlertaVencimiento** (alerta de vencimiento de publicación obligatoria o PQRSD) | UC-050, UC-036, UC-021 | SÍ |

---

## 2. Atributos/campos vistos en flujos, pre/postcondiciones

| Campo | Entidad | UC fuente | Notas |
|---|---|---|---|
| **PQRSD / Radicado** | | | |
| tipo | PQRSD | UC-001 fl.2 | Dominio: Petición/Queja/Reclamo/Sugerencia/Denuncia/Solicitud de información |
| numero_radicado | Radicado | UC-001 post, UC-002 fl.2 | Único, generado atómicamente (E9); ≤5 s al SGDEA |
| fecha_radicacion | Radicado | UC-001 post, UC-002 fl.4 | Hora Legal Colombia (INM); TSA RFC 3161 donde aplique |
| estado | PQRSD | UC-002 fl.4, UC-036, UC-035 | Dominio: registrada→recibida_a_satisfaccion→en_trámite→resuelta |
| fecha_estado | PQRSD | UC-002 fl.4 | Fecha del último cambio de estado |
| fecha_estimada_respuesta | PQRSD | UC-002 fl.4, UC-036 | Calculada por tipo y calendario hábil |
| dentro_termino | PQRSD | UC-036 A2, delta §3 | BOOLEAN: dentro/fuera del término legal al cerrar |
| dependencia_destinataria | PQRSD | UC-001 post, UC-035 fl.1 | FK a dependencia/UsuarioInterno |
| es_anonima | PQRSD | UC-001 A1 | BOOLEAN |
| identidad_reservada | PQRSD | UC-035 E4 | BOOLEAN: denuncia con identidad reservada del peticionario |
| campo_objeto_tiene_dato_sensible | PQRSD | UC-001 A4 | BOOLEAN: requiere consentimiento diferenciado |
| plazo_tipo_dias | PQRSD | UC-035 E3 | INTEGER: 15 general / 10 información / 30 consulta / 10 entre autoridades |
| **Ciudadano / PerfilCiudadano** | | | |
| tipo_documento | Ciudadano/PerfilCiudadano | UC-006 fl.2 | Dominio: CC/CE/TI/PEP/NIT |
| numero_documento | Ciudadano/PerfilCiudadano | UC-006 fl.2 | Validado vs ANI en tiempo real |
| correo | PerfilCiudadano | UC-006 fl.4, UC-020 fl.2 | UNIQUE; reverificación OTP al cambiar [INFERIDO] |
| contrasena_hash | PerfilCiudadano | UC-006 fl.4, UC-031 | ≥8 caracteres; almacenada hasheada [INFERIDO] |
| telefono | PerfilCiudadano | UC-005 fl.3 (Medio) | Para MFA/OTP nivel medio |
| direccion | PerfilCiudadano | UC-005 fl.3 (Medio) | Verificada en nivel medio SCD |
| nombre_completo | PerfilCiudadano | UC-006, UC-020 | |
| es_menor_de_edad | PerfilCiudadano | UC-006 E3 | BOOLEAN; exige representante legal |
| representante_legal_id | PerfilCiudadano | UC-006 E3 | FK a PerfilCiudadano del representante |
| fecha_registro | PerfilCiudadano | UC-006 post | |
| firma_electronica_otorgada | PerfilCiudadano | UC-006 post | BOOLEAN |
| estado_cuenta | PerfilCiudadano | UC-020, UC-033 | Dominio: activa/suspendida/eliminada_logica |
| nivel_autenticacion_alcanzado | PerfilCiudadano | UC-005 | Dominio: Bajo/Medio/Alto/Muy_Alto |
| canal_notificacion_preferido | PerfilCiudadano | UC-008 A1 | Dominio: correo/CCD/SMS/APP |
| direccion_procesal_electronica | PerfilCiudadano | UC-008 A1, UC-036 E3 | Si existe, preferencia sobre física |
| **Sesion** | | | |
| token_sesion | Sesion | UC-005 fl.5, UC-038 | |
| fecha_inicio | Sesion | UC-017 fl.5 | |
| fecha_expiracion | Sesion | UC-001 E8, UC-004 E4 | 900 s inactividad (RNF-B1-023) |
| estado_sesion | Sesion | UC-038 fl.2 | Dominio: activa/expirada/revocada |
| nivel_autenticacion_sesion | Sesion | UC-005 fl.2 | Bajo/Medio/Alto/Muy_Alto |
| ip_origen | Sesion | UC-001 A1 (metadata) | Para trazabilidad de PQRSD anónima |
| **TramiteInstancia** | | | |
| id_tramite_instancia | TramiteInstancia | UC-004, UC-042, UC-043 | PK; único por ejecución |
| tramite_id | TramiteInstancia | UC-003, UC-004 | FK a Trámite (catálogo) |
| ciudadano_id | TramiteInstancia | UC-004 | FK a PerfilCiudadano |
| estado | TramiteInstancia | UC-004, UC-042, UC-043, UC-044, UC-045 | Dominio: borrador/en_curso/requiere_subsanacion/subsanado/pendiente_pago/en_espera_confirmacion/resuelto/desistido/sap_concedido |
| etapa_actual | TramiteInstancia | UC-004 fl. (4 etapas), UC-045 | INTEGER: paso del stepper guardado |
| fecha_radicacion | TramiteInstancia | UC-004 post | |
| fecha_vencimiento | TramiteInstancia | UC-044, UC-036 | Calculada con calendario hábil |
| sujeto_a_sap | TramiteInstancia | UC-044 | BOOLEAN |
| nivel_autenticacion_exigido | TramiteInstancia | UC-004 E8 | Nivel mínimo por trámite; [PREGUNTA ABIERTA] matriz |
| clave_idempotencia_pago | TramiteInstancia/Pago | UC-007 pre delta, RN-03-D01 | VARCHAR UNIQUE por intento de pago |
| **Pago** | | | |
| monto | Pago | UC-007 fl.2 | Calculado por tarifa |
| medio_pago | Pago | UC-007 fl.3 | Dominio: PSE/debito/credito |
| estado_pago | Pago | UC-007 E5 delta | Dominio: aprobado/rechazado/pendiente/fallido |
| comprobante_url | Pago | UC-007 fl.5 | URL del comprobante generado |
| fecha_pago | Pago | UC-007 fl.5 | |
| clave_idempotencia | Pago | UC-007 pre delta | VARCHAR UNIQUE — evita doble cobro |
| **Cita** | | | |
| codigo_confirmacion | Cita | UC-011 fl.7, UC-032 fl.1 | Único; enviado por correo |
| dependencia | Cita | UC-011 fl.2 | FK a dependencia/UsuarioInterno |
| servicio | Cita | UC-047 fl.1 | FK a AgendaDisponibilidad |
| franja_horaria_id | Cita | UC-011 fl.4, UC-047 fl.2 | FK a AgendaDisponibilidad |
| fecha_hora | Cita | UC-011 fl.4 | TIMESTAMP |
| estado | Cita | UC-032, UC-047 | Dominio: reservada/cancelada/reprogramada/atendida/no_show |
| ciudadano_id | Cita | UC-011 fl.5 | FK a PerfilCiudadano |
| datos_contacto | Cita | UC-011 fl.5 | nombre, correo, teléfono del solicitante |
| **AgendaDisponibilidad** | | | |
| servicio_nombre | AgendaDisponibilidad | UC-047 fl.1 | |
| dependencia_id | AgendaDisponibilidad | UC-047 fl.1 | |
| franja_inicio | AgendaDisponibilidad | UC-047 fl.2 | TIME |
| franja_fin | AgendaDisponibilidad | UC-047 fl.2 | TIME |
| cupos_totales | AgendaDisponibilidad | UC-047 fl.2 | INTEGER |
| cupos_disponibles | AgendaDisponibilidad | UC-011 fl.3, UC-032 E3' | INTEGER; actualización atómica |
| dias_habilitados | AgendaDisponibilidad | UC-047 fl.3 | días de la semana activos |
| bloqueos | AgendaDisponibilidad | UC-047 fl.3 | fechas bloqueadas (relación o array) |
| **Notificacion** | | | |
| canal | Notificacion | UC-008 fl.2 | Dominio: correo/CCD/SMS/APP |
| estado_entrega | Notificacion | UC-008 E4 delta | Dominio: enviada/entregada/fallida/reintento |
| fecha_envio | Notificacion | UC-008 fl.2 | |
| fecha_entrega | Notificacion | UC-008 E4 | |
| intentos | Notificacion | UC-008 E4 delta | INTEGER |
| referencia_objeto | Notificacion | UC-008, UC-036, UC-042, UC-046 | Polimórfico: PQRSD/TramiteInstancia/Cita [INFERIDO] |
| **Contenido / VersionContenido** | | | |
| titulo | Contenido | UC-018 fl.3 | |
| tipo_contenido | Contenido | UC-012 fl.5 | Dominio: normativa/transparencia/video/noticias/datos |
| estado_editorial | Contenido | UC-048 fl.1..4 | Dominio: borrador/pendiente/publicado/archivado |
| metadatos_tipo | Contenido | UC-018 fl.3, UC-012 fl.5 | tipo, número, fechas, epígrafe, vigencia |
| metadatos_numero | Contenido | UC-012 fl.5 | |
| fecha_publicacion | Contenido | UC-018 fl.6 | |
| fecha_vigencia | Contenido | UC-018 fl.3, UC-048 E3 | OBLIGATORIO; bloquea publicación si falta |
| vigente | Contenido | UC-012, UC-018 E2 | BOOLEAN |
| url_fuente | Contenido | UC-050 fl.2 | URL permanente (no cambia al versionar) |
| creador_id | Contenido | UC-048 fl.1 | FK a UsuarioInterno |
| aprobador_id | Contenido | UC-048 fl.3 | FK a UsuarioInterno (distinto del creador — SoD) |
| numero_version | VersionContenido | UC-050 fl.2 | INTEGER |
| fecha_version | VersionContenido | UC-050 fl.2 | |
| archivo_url | VersionContenido | UC-050 fl.2 | |
| lock_version | Contenido | UC-048 E2 | Para control de concurrencia optimista |
| **Dataset / VersionDataset** | | | |
| nombre | Dataset | UC-013 fl.2 | |
| categoria | Dataset | UC-013 fl.2 | |
| formato | Dataset | UC-013 fl.5 | Dominio: CSV/JSON/XML |
| licencia | Dataset | UC-013 fl.5, UC-025 fl.2 | Obligatoria para publicar |
| metadatos | Dataset | UC-025 fl.2 | |
| frecuencia_actualizacion | Dataset | UC-049 fl.3 | Para control de frescura |
| fecha_ultima_actualizacion | Dataset | UC-049 fl.3 | |
| estado | Dataset | UC-049 | Dominio: publicado/despublicado |
| url_datosgovcol | Dataset | UC-013 fl.6 | Enlace federado a datos.gov.co |
| numero_version | VersionDataset | UC-049 fl.2 | INTEGER |
| archivo_url | VersionDataset | UC-049 fl.2 | |
| fecha_version | VersionDataset | UC-049 fl.2 | |
| **UsuarioInterno / Rol** | | | |
| nombre | UsuarioInterno | UC-020 fl.2 | |
| correo | UsuarioInterno | UC-020 fl.2 | UNIQUE |
| rol_id | UsuarioInterno | UC-020 fl.2 | FK a Rol |
| estado | UsuarioInterno | UC-020 fl.5 | Dominio: activo/suspendido/baja_logica |
| requiere_cambio_contrasena | UsuarioInterno | UC-020 fl.4 | BOOLEAN: primer acceso |
| fecha_baja | UsuarioInterno | UC-020 A1 delta | Fecha de revocación (≤1 día hábil) |
| mfa_habilitado | UsuarioInterno | UC-017 pre delta, UC-020 E4 | BOOLEAN — obligatorio para admin CMS |
| nombre_rol | Rol | UC-020 | |
| permisos | Rol | UC-020 | Representado como tabla de permisos [INFERIDO] |
| **ConsentimientoCookies** | | | |
| ciudadano_id | ConsentimientoCookies | UC-016 fl.3 | FK a PerfilCiudadano / sesión anónima [INFERIDO] |
| version_politica | ConsentimientoCookies | UC-016 A2 delta | Para re-solicitar si cambia |
| categorias_aceptadas | ConsentimientoCookies | UC-016 fl.3 | JSON o tabla de detalle |
| fecha_consentimiento | ConsentimientoCookies | UC-016 A2 delta | TIMESTAMP |
| fecha_expiracion | ConsentimientoCookies | UC-016 A2 delta | >12 meses → re-solicitar |
| **AuditLog** | | | |
| entidad_afectada | AuditLog | UC-017 post delta, UC-020 post | Tipo de entidad del evento |
| id_entidad_afectada | AuditLog | UC-017 post delta | ID del recurso afectado |
| accion | AuditLog | UC-017, UC-018, UC-041 | Dominio: login/publicar/aprobar/eliminar/revocar/etc. |
| usuario_id | AuditLog | UC-017, UC-020 | FK a UsuarioInterno o PerfilCiudadano |
| fecha_evento | AuditLog | UC-017 fl.5 | TIMESTAMP con TSA donde aplica |
| hash_encadenado | AuditLog | UC-017 post delta, RN-09-D05 | Integridad append-only; 5 años de retención |
| **BorradorTramite** | | | |
| tramite_id | BorradorTramite | UC-045, UC-004 A6 | FK a Trámite (catálogo) |
| ciudadano_id | BorradorTramite | UC-045 | FK a PerfilCiudadano |
| datos_stepper | BorradorTramite | UC-045 fl.2 | JSON con datos del formulario parcial |
| etapa_guardada | BorradorTramite | UC-045 fl.2 | INTEGER |
| adjuntos_temp | BorradorTramite | UC-045 fl.2 | Referencias a adjuntos ya cargados |
| fecha_guardado | BorradorTramite | UC-045 | |
| fecha_expiracion | BorradorTramite | UC-045 E1 | [PREGUNTA ABIERTA] plazo de retención |
| **Adjunto** | | | |
| nombre_archivo | Adjunto | UC-001 A3, UC-042 fl.4 | |
| tamanio_bytes | Adjunto | UC-001 A3 | Único límite admisible = capacidad técnica documentada |
| mime_type_real | Adjunto | UC-004 E6, UC-042 E2 | Validado por contenido, no solo extensión |
| referencia_id | Adjunto | UC-001, UC-042 | Polimórfico: PQRSD/TramiteInstancia [INFERIDO] |
| referencia_tipo | Adjunto | UC-001, UC-042 | Discriminador del polimorfismo |
| estado_antivirus | Adjunto | UC-042 E2, delta C-06 | Dominio: pendiente/limpio/rechazado |
| **TokenRecuperacion** | | | |
| ciudadano_id | TokenRecuperacion | UC-031 | FK a PerfilCiudadano |
| token_hash | TokenRecuperacion | UC-031 E1' delta | VARCHAR; un solo uso; almacenado hasheado [INFERIDO] |
| fecha_expiracion | TokenRecuperacion | UC-031 E1' delta | 15 minutos |
| usado | TokenRecuperacion | UC-031 E1' delta | BOOLEAN |
| **IntercambioXRoad** | | | |
| servicio_destino | IntercambioXRoad | UC-029 fl.2 | Registraduría/RUNT/RUAF/etc. |
| tramite_instancia_id | IntercambioXRoad | UC-029 | FK a TramiteInstancia |
| estado | IntercambioXRoad | UC-029 E4, E5 | Dominio: exitoso/timeout/fallido/encolado |
| tsa_estampado | IntercambioXRoad | UC-029 E4 delta | BOOLEAN: RFC 3161 aplicado |
| fecha_intercambio | IntercambioXRoad | UC-029 fl.5 | TIMESTAMP |
| **CalendarioHabil** | | | |
| fecha | CalendarioHabil | UC-035 pre delta, RN-TX-D01 | DATE PK |
| es_habil | CalendarioHabil | UC-035 E3 | BOOLEAN |
| tipo_festivo | CalendarioHabil | UC-035 | Dominio: nacional/distrital/ninguno |
| **SilencioAdministrativoPositivo** | | | |
| tramite_instancia_id | SilencioAdministrativoPositivo | UC-044 | FK a TramiteInstancia |
| fecha_vencimiento | SilencioAdministrativoPositivo | UC-044 fl.1 | |
| efecto | SilencioAdministrativoPositivo | UC-044 fl.2 | VARCHAR: 'concedido' |
| fecha_registro | SilencioAdministrativoPositivo | UC-044 fl.4 | TSA RFC 3161 |
| notificado_ciudadano | SilencioAdministrativoPositivo | UC-044 fl.3 | BOOLEAN |
| **ProrrogaPQRSD** | | | |
| pqrsd_id | ProrrogaPQRSD | UC-046 fl.1 | FK a PQRSD |
| funcionario_id | ProrrogaPQRSD | UC-046 fl.1 | FK a UsuarioInterno |
| motivacion | ProrrogaPQRSD | UC-046 fl.1 | TEXT |
| fecha_vencimiento_original | ProrrogaPQRSD | UC-046 fl.1 | |
| nueva_fecha_vencimiento | ProrrogaPQRSD | UC-046 fl.1 | Validada ≤ máximo legal |
| fecha_notificacion_peticionario | ProrrogaPQRSD | UC-046 fl.2 | Antes del vencimiento original |
| **DerechoARCO** | | | |
| tipo | DerechoARCO | UC-010 fl.2 | Dominio: Acceso/Rectificación/Cancelación/Oposición |
| ciudadano_id | DerechoARCO | UC-010 | FK a PerfilCiudadano |
| estado | DerechoARCO | UC-010 fl.4 | Dominio: en_tramite/resuelto/improcedente |
| fecha_solicitud | DerechoARCO | UC-010 fl.4 | |
| plazo_respuesta_dias | DerechoARCO | UC-010 fl.4 | Consulta≤10/Reclamo≤15 hábiles |
| respuesta | DerechoARCO | UC-010 fl.4 | TEXT |
| **VerificacionITA** | | | |
| contenido_id | VerificacionITA | UC-021 fl.2 | FK a Contenido |
| criterio | VerificacionITA | UC-021 fl.3 | nombre del criterio ITA |
| norma | VerificacionITA | UC-021 fl.3 | norma que lo exige |
| cumple | VerificacionITA | UC-021 fl.4 | BOOLEAN |
| responsable_id | VerificacionITA | UC-021 fl.4 | FK a UsuarioInterno |
| fecha_validacion | VerificacionITA | UC-021 fl.2 | |
| **AlertaVencimiento** | | | |
| referencia_id | AlertaVencimiento | UC-050 E2, UC-036 A1 | ID del contenido/PQRSD |
| referencia_tipo | AlertaVencimiento | UC-050, UC-036 | Discriminador |
| fecha_alerta | AlertaVencimiento | UC-050 fl.3 | N días antes del plazo |
| plazo_legal | AlertaVencimiento | UC-050 fl.3 | DATE |
| enviada | AlertaVencimiento | UC-050 | BOOLEAN |

---

## 3. Estados y transiciones derivados de UC

| Entidad | Estado | Evento desencadenante | UC fuente |
|---|---|---|---|
| **PQRSD** | registrada | Ciudadano envía PQRSD (radicado generado) | UC-001 post |
| **PQRSD** | recibida_a_satisfacción | Dependencia confirma recepción | UC-002 fl.4 |
| **PQRSD** | en_trámite | Funcionario asigna y comienza gestión | UC-002 fl.4, UC-035 fl.4 |
| **PQRSD** | resuelta | Funcionario carga respuesta y firma | UC-036 fl.4 |
| **PQRSD** | trasladada | Sin competencia → entidad receptora notificada | UC-037 fl.4 |
| **PQRSD** | prorrogada | Funcionario registra prórroga antes del vencimiento | UC-046 fl.3 |
| **TramiteInstancia** | borrador | Ciudadano inicia y guarda parcialmente (stepper) | UC-045, UC-004 A6 |
| **TramiteInstancia** | en_curso | Trámite radicado y en proceso | UC-004 fl.8 |
| **TramiteInstancia** | requiere_subsanacion | Entidad marca "requiere subsanación" | UC-042 pre, UC-004 A4 |
| **TramiteInstancia** | subsanado | Ciudadano carga documentos faltantes | UC-042 fl.6 |
| **TramiteInstancia** | pendiente_pago | Paso de pago iniciado | UC-007 fl.1 |
| **TramiteInstancia** | en_espera_confirmacion | Estado de pago `pendiente` en pasarela | UC-007 E5 delta |
| **TramiteInstancia** | resuelto | Entidad emite resolución | UC-004 fl.9 |
| **TramiteInstancia** | desistido | Ciudadano confirma desistimiento | UC-043 fl.4 |
| **TramiteInstancia** | sap_concedido | Vence término sin resolución en trámite con SAP | UC-044 fl.2 |
| **Pago** | pendiente | Pago iniciado en pasarela | UC-007 E5 delta |
| **Pago** | aprobado | Pasarela confirma pago | UC-007 E5 delta |
| **Pago** | rechazado | Banco/pasarela rechaza | UC-007 E1, E5 |
| **Pago** | fallido | Timeout o error técnico | UC-007 E5 delta |
| **Cita** | reservada | Ciudadano confirma agendamiento | UC-011 fl.6 |
| **Cita** | cancelada | Ciudadano cancela con código | UC-032 fl.3 |
| **Cita** | reprogramada | Ciudadano elige nueva franja | UC-032 A1 delta |
| **Cita** | atendida | Atención presencial realizada | UC-032 E4 delta |
| **Cita** | no_show | Cita no atendida sin cancelar | UC-032 E4 delta |
| **Contenido** | borrador | Editor crea/edita contenido | UC-048 fl.1, UC-018 fl.5 |
| **Contenido** | pendiente | Editor envía a revisión | UC-048 fl.2 |
| **Contenido** | publicado | Administrador aprueba | UC-048 fl.3, UC-018 fl.6 |
| **Contenido** | archivado | Administrador despublica | UC-048 fl.4 |
| **Contenido** | rechazado→borrador | Administrador rechaza con comentario | UC-048 fl.3 |
| **Dataset** | publicado | Admin publica dataset | UC-025 fl.4, UC-049 fl.4 |
| **Dataset** | despublicado | Admin retira dataset | UC-049 fl.2 |
| **Notificacion** | enviada | Canal de notificación intentado | UC-008 fl.2 |
| **Notificacion** | entregada | Canal confirma entrega | UC-008 E4 delta |
| **Notificacion** | fallida | Canal retorna error | UC-008 E4 delta |
| **Notificacion** | reintento | Lógica de cola reactiva | UC-008 E4 delta |
| **Adjunto** | pendiente | Archivo cargado, análisis pendiente | UC-042 E2 |
| **Adjunto** | limpio | Antivirus aprueba | UC-042 E2, delta C-06 |
| **Adjunto** | rechazado | MIME falso o malware detectado | UC-004 E6, UC-042 E2 |
| **PerfilCiudadano** | activo | Registro completado y OTP verificado | UC-006 fl.7 |
| **PerfilCiudadano** | suspendido | Admin suspende cuenta | UC-020 fl.5 |
| **PerfilCiudadano** | baja_logica | Ciudadano revoca/suprime o admin da de baja | UC-033 fl.3, UC-020 A2 |
| **UsuarioInterno** | activo | Admin crea usuario | UC-020 fl.2 |
| **UsuarioInterno** | baja_logica | Desvinculación ≤1 día hábil sin borrado físico | UC-020 A2 delta |

---

## 4. Actores → roles/permisos

| Actor (nombre en UC) | Rol/permiso inferido | Acción representativa | Entidad afectada | UC fuente |
|---|---|---|---|---|
| Ciudadano (identificado) | `ciudadano_autenticado` | Radicar PQRSD, consultar estado, ejercer ARCO, agendar cita, desistir, subsanar | PQRSD, TramiteInstancia, Cita, PerfilCiudadano | UC-001..UC-016, UC-030..UC-045 |
| Ciudadano (anónimo) | `ciudadano_anonimo` | Radicar PQRSD anónima, consultar estado por radicado público | PQRSD, Radicado | UC-001 A1, UC-002 |
| Contribuyente | `ciudadano_autenticado` (subrol) | Consultar info tributaria, iniciar pago | TramiteInstancia, Pago | UC-040, UC-007 |
| Ciudadano/Investigador | `ciudadano_anonimo` | Descargar datos abiertos | Dataset | UC-013 |
| Administrador del sistema | `admin_sistema` | Gestionar usuarios/roles, configurar CMS, iniciar sesión admin | UsuarioInterno, Rol, AuditLog | UC-017, UC-020 |
| Administrador/Editor (CMS) | `editor` (crea), `admin_contenido` (aprueba) | Publicar/aprobar contenido; SoD: creador ≠ aprobador | Contenido, VersionContenido | UC-018, UC-048 |
| Administrador de contenidos | `admin_contenido` | Publicar video institucional accesible | Contenido | UC-019 |
| Administrador de cumplimiento | `admin_cumplimiento` | Monitorear ITA, ver incumplimientos | VerificacionITA | UC-021 |
| Administrador de seguridad TI | `admin_seguridad` | Clasificar y gestionar incidentes, reportar CSIRT/SIC | IncidenteSeguridad | UC-022 |
| Administrador del portal | `admin_portal` | Publicar y versionar datasets | Dataset, VersionDataset | UC-025, UC-049 |
| Administrador/Editor de transparencia | `editor_transparencia` | CRUD + versionado publicaciones de transparencia | Contenido, VersionContenido, AlertaVencimiento | UC-050 |
| Administrador de gestión documental | `admin_documental` | Aprobar/eliminar con flujo TRD | Expediente, Contenido | UC-041 |
| Administrador/Funcionario de atención | `admin_agenda` | Configurar agenda, franjas y cupos | AgendaDisponibilidad | UC-047 |
| Equipo UX | `equipo_ux` | Revisar resultados encuesta SUS | EncuestaSUS | UC-023 |
| Ingeniero de accesibilidad/QA | `qa_accesibilidad` | Validar accesibilidad de páginas | ResultadoAccesibilidad | UC-024 |
| Equipo técnico de la Alcaldía | `admin_ti` | Integrar trámite a GOV.CO, desplegar X-Road | ServicioXRoad | UC-026, UC-027 |
| Funcionario de dependencia | `funcionario` | Recibir, gestionar, enrutar PQRSD; responder | PQRSD, Expediente | UC-035, UC-036, UC-037 |
| Funcionario responsable | `funcionario` | Responder PQRSD, registrar prórroga, firmar | PQRSD, ProrrogaPQRSD, FirmaElectronica | UC-036, UC-039, UC-046 |
| Sistema (cómputo automático) | `proceso_sistema` | Reconocer SAP, calcular plazos, enviar notificaciones | TramiteInstancia, SilencioAdministrativoPositivo, Notificacion | UC-044, UC-008, UC-036 |
| SCD Autenticación (OIDC externo) | sistema externo | Proveer tokens de autenticación | Sesion | UC-005 |
| CCD (Carpeta Ciudadana Digital) | sistema externo GOV.CO | Exponer datos del ciudadano | CarpetaCiudadanaDigital | UC-009 |
| SGDEA (externo) | sistema externo | Gestionar expedientes | Expediente | UC-001, UC-004, UC-035, UC-036 |
| Pasarela PSE/tarjetas | sistema externo | Procesar pagos | Pago | UC-007 |
| CSIRT-Gobierno / SIC | actor externo | Recibir reportes de incidentes y brechas | IncidenteSeguridad | UC-022 |
| MinTIC / AND | actor externo | Aprobar integración GOV.CO; gestionar X-Road | ServicioXRoad | UC-026, UC-027, UC-028 |
| ANI / Registraduría | sistema externo | Validar identidad del ciudadano | PerfilCiudadano | UC-006, UC-005 |
| SUCOP (DNP) | sistema externo | Recibir comentarios ciudadanos sobre normas | ConsultaCiudadanaNorma | UC-014 |
| TSA (GSE, reemplaza Certicámara) | sistema externo | Estampar sello temporal RFC 3161 | IntercambioXRoad, AuditLog | UC-027, UC-029 |

---

## 5. Reglas de negocio implícitas en flujos con impacto en datos

| ID regla (UC fuente) | Descripción | Impacto en BD |
|---|---|---|
| RN-001 (UC-001 E9, RNF-04-D02) | Asignación atómica del número de radicado: dos envíos simultáneos no comparten consecutivo | Secuencia/SEQUENCE con bloqueo; no usar MAX()+1 |
| RN-002 (UC-007 E3'/RN-03-D01) | Cada intento de pago lleva clave de idempotencia única; el reintento devuelve comprobante existente, nunca genera segundo cargo | Campo UNIQUE `clave_idempotencia` en Pago; constraint de unicidad |
| RN-003 (UC-007 E5/RN-03-D02) | El trámite avanza de etapa **solo** si el pago está en estado `aprobado`; `pendiente/fallido/rechazado` mantienen el trámite en espera | CHECK o lógica de aplicación; no actualizar estado TramiteInstancia sin validar estado Pago |
| RN-004 (UC-004 E7, UC-002 E4, UC-009 E4/RN-09-D01) | Anti-IDOR: todo acceso a recurso (radicado/trámite/PQRSD/cita/CCD) verifica pertenencia al titular autenticado | FK ciudadano_id en cada entidad; control en capa de acceso a datos |
| RN-005 (UC-048 E1/RN-09-D02) | SoD editorial: el creador de un contenido no puede ser quien lo aprueba ni publica | `creador_id ≠ aprobador_id` CHECK o constraint en tabla Contenido |
| RN-006 (UC-017/UC-020/RN-09-D04) | MFA obligatorio para todos los roles internos del CMS | Campo `mfa_habilitado` NOT NULL DEFAULT FALSE en UsuarioInterno; validación en sesión |
| RN-007 (UC-017 post/RN-09-D05) | AuditLog append-only encadenado por hash; retención mínima 5 años | Tabla sin UPDATE/DELETE; campo `hash_encadenado`; política de retención |
| RN-008 (UC-020 A2/RF-12-D02) | Baja de usuario interno sin borrado físico; eventos de auditoría permanecen | Soft-delete en UsuarioInterno; conservar AuditLog |
| RN-009 (UC-016 A2/RN-01-D01) | Consentimiento de cookies caduca a los 12 meses o al cambiar la política; requiere versionado | Campo `version_politica` y `fecha_expiracion` en ConsentimientoCookies |
| RN-010 (UC-035 E3/RN-04-D01) | Plazos de PQRSD diferenciados por tipo sobre calendario hábil del Distrito: 15/10/30/10 días | Tabla CalendarioHabil + lógica de cómputo de días hábiles; campo `plazo_tipo_dias` en PQRSD |
| RN-011 (UC-036 post/RN-04-D04) | PQRSD no se cierra sin respuesta registrada; al cerrar se marca `dentro_termino` (BOOLEAN) | Constraint NOT NULL en campo respuesta al cerrar; campo `dentro_termino` |
| RN-012 (UC-046 E1/RN-04-D02) | Prórroga rechazada si se solicita después del vencimiento; nueva fecha no puede exceder máximo legal | Validación en capa de aplicación; campo `nueva_fecha_vencimiento` con CHECK ≤ máximo legal |
| RN-013 (UC-037 E2/RN-04-D03) | Traslado de PQRSD dentro de los 5 días hábiles siguientes a la recepción | Campo `fecha_traslado` en PQRSD; semáforo calculado sobre CalendarioHabil |
| RN-014 (UC-035 E4/RN-04-D06) | Identidad del denunciante es reservada; el back-office no expone el peticionario | Campo `identidad_reservada` BOOLEAN; vistas/permisos que ocultan peticionario en rol funcionario |
| RN-015 (UC-042 E1/RN-03-D05) | Vencimiento del plazo de subsanación sin respuesta → desistimiento automático por sistema | Proceso/trigger sobre BorradorTramite/TramiteInstancia comparando fecha_expiracion |
| RN-016 (UC-044/RN-03-D08) | Trámite con SAP vencido sin resolución → efecto "concedido" registrado automáticamente | Campo `sujeto_a_sap` en TramiteInstancia; tabla SilencioAdministrativoPositivo |
| RN-017 (UC-031 E1'/RF-09-D02) | Token de recuperación: un solo uso, expira en 15 minutos, restablecer invalida sesiones activas | Campo `usado` BOOLEAN + `fecha_expiracion` en TokenRecuperacion; revocar Sesion al usar |
| RN-018 (UC-011 E1'/UC-032 E3'/RN-06-D01) | Reserva de cupo de cita es atómica: dos ciudadanos concurrentes no comparten el mismo cupo | UPDATE atómico de `cupos_disponibles` con FOR UPDATE o nivel de aislamiento Repeatable Read |
| RN-019 (UC-049 E1/RN-11-D01) | Dataset desactualizado según frecuencia configurada genera alerta al responsable | Campo `fecha_ultima_actualizacion` + `frecuencia_actualizacion`; proceso de control de frescura |
| RN-020 (UC-050/RN-02-D01) | Al reemplazar un documento de transparencia, la URL permanente no cambia; se crea VersionContenido | Patrón de versionado: nueva fila en VersionContenido, URL inmutable en Contenido |
| RN-021 (UC-006 E3/RN-B2-007) | Menor de 18 años requiere datos del representante legal para registrarse | Campo `representante_legal_id` FK en PerfilCiudadano; CHECK o trigger sobre `es_menor_de_edad` |
| RN-022 (UC-006 E6/RN-12-D03) | Menores de 18: exigir autorización del representante antes de recolectar datos | Estado intermedio `pendiente_autorizacion` en PerfilCiudadano [INFERIDO] |
| RN-023 (UC-004 E8/RN-TX-D04) | Trámite exige nivel mínimo de autenticación; bloqueo si nivel de sesión < requerido | Campo `nivel_autenticacion_exigido` en Trámite; comparación con `nivel_autenticacion_sesion` |
| RN-024 (UC-001 A1/RN-B1-009) | PQRSD de acceso a información NO puede ser anónima | CHECK: si tipo='solicitud_informacion' entonces `es_anonima`=FALSE |
| RN-025 (UC-029 E4/RN-10-D02) | Mensajes X-Road pendientes de sello TSA se encolan; ninguno se cierra sin RFC 3161 | Campo `tsa_estampado` BOOLEAN en IntercambioXRoad; cola de sellado |
| RN-026 (UC-007 E6/RN-03-D03) | Prohibido cobrar recargo por canal digital | No hay campo de recargo; constraint CHECK precio_pagado = tarifa_calculada |
| RN-027 (UC-045 E1/RNF-03-D02) | Borradores expirados se purgan/anonimizan | `fecha_expiracion` en BorradorTramite; proceso de purga periódica |
| RN-028 (UC-048 E2/RN-12-D05) | Edición concurrente de contenido: bloqueo optimista/pesimista, aviso de conflicto | Campo `lock_version` en Contenido (optimistic locking) |
| RN-029 (transversal/RN-TX-D02) | Toda evidencia con valor legal usa Hora Legal de Colombia (INM) y estampa TSA RFC 3161 donde aplique | TIMESTAMP WITH TIME ZONE en todas las entidades con valor legal; TSA en AuditLog, Radicado, SilencioAdministrativoPositivo, ProrrogaPQRSD |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Entidad/campo | UC que la motiva |
|---|---|---|---|
| I-01 | La contraseña se almacena hasheada (no en texto plano) | PerfilCiudadano.contrasena_hash | UC-006, UC-031 |
| I-02 | El correo electrónico de PerfilCiudadano requiere re-verificación OTP al cambiar | PerfilCiudadano | UC-034, UC-006 |
| I-03 | La tabla de permisos de Rol se implementa como tabla separada (Rol-Permiso) | Rol | UC-020 |
| I-04 | La entidad Adjunto usa patrón polimórfico: `referencia_tipo` + `referencia_id` para apuntar a PQRSD o TramiteInstancia; requiere arc exclusivo o supertipo para integridad referencial | Adjunto | UC-001, UC-042 |
| I-05 | La entidad Notificacion usa patrón polimórfico similar al de Adjunto para referenciar PQRSD, TramiteInstancia o Cita | Notificacion | UC-008, UC-036, UC-042 |
| I-06 | AlertaVencimiento usa patrón polimórfico para referenciar Contenido o PQRSD | AlertaVencimiento | UC-050, UC-036 |
| I-07 | ConsentimientoCookies puede registrarse para sesión anónima (sin FK a PerfilCiudadano); campo ciudadano_id nullable | ConsentimientoCookies | UC-016 |
| I-08 | PerfilCiudadano estado `pendiente_autorizacion` para menores de 18 en espera de autorización del representante | PerfilCiudadano | UC-006 E6, UC-006 E3 |
| I-09 | El campo `datos_stepper` de BorradorTramite se almacena en JSONB (PostgreSQL 15+) para flexibilidad por tipo de trámite | BorradorTramite | UC-045 |
| I-10 | La tabla de festivos del CalendarioHabil debe cargarse por año y mantenerse actualizada; necesita proceso de carga inicial y mantenimiento | CalendarioHabil | UC-035, UC-036, UC-046 |
| I-11 | El campo `permisos` de Rol se implementa como tabla `RolPermiso(rol_id, permiso_codigo)` para habilitar mínimo privilegio | Rol | UC-020 E2 |
| I-12 | Los campos `encabezados_xroad` (`client`/`service`) del IntercambioXRoad se almacenan como JSONB | IntercambioXRoad | UC-029 fl.2 |
| I-13 | El token de TokenRecuperacion se almacena hasheado; solo se compara el hash para validación | TokenRecuperacion | UC-031 E1' |
| I-14 | La tabla EncuestaSUS requiere campos: tramite_instancia_id, ciudadano_id, respuestas (JSONB con 10 ítems Likert 1-5), puntaje_calculado, fecha_aplicacion | EncuestaSUS | UC-023 |
| I-15 | La tabla ResultadoAccesibilidad requiere campos: url_pagina, herramienta (axe-core/Lighthouse/Tawdis), criterios_fallidos, bloqueante (BOOLEAN), fecha_validacion, responsable_id | ResultadoAccesibilidad | UC-024 |
| I-16 | Existe entidad `Dependencia` (no se nombra explícitamente como entidad propia pero aparece en UC-001, UC-011, UC-035, UC-047) con campos: nombre, codigo, responsable_id | Dependencia | UC-001, UC-035, UC-047 |
| I-17 | Existe entidad `Tramite` (catálogo) distinta de `TramiteInstancia` con campos: codigo_suit, nombre, modalidad (en_linea/presencial/mixta), costo, tiempo_resolucion, documentos_requeridos, nivel_autenticacion_exigido, sujeto_a_sap, termino_sap_dias, url_govco | Tramite | UC-003, UC-004, UC-026 |
| I-18 | `FirmaElectronica` podría ser un atributo de Contenido/PQRSD/TramiteInstancia (campo `firmado_digitalmente` BOOLEAN + `certificado_id`) más que una entidad separada, salvo que el dominio registre un log de firmas | FirmaElectronica | UC-039 |

---

### Preguntas abiertas que condicionan el diseño (no se inventan)

1. **Política de reembolso** al desistir un trámite pagado (UC-043 E2) — Tesorería + Líder de trámites.
2. **Trámites sujetos a SAP** y su término legal (UC-044 E2) — Secretaría Jurídica.
3. **Período de retención/expiración** de borradores de trámite (UC-045 E1) — Líder de trámites + Protección de Datos.
4. **Matriz trámite ↔ nivel de autenticación** exigido (UC-004 E8 / C-08) — G-CIO + AND.
5. **Agenda propia vs. integración** con sistema de turnos existente (UC-047 E2) — Oficina de Atención.
6. **Enrutamiento PQRSD** automático vs. manual (UC-035, heredada).
7. **LSC obligatoria** para entidad territorial vs. Gobierno Nacional (UC-019 E3).
8. **Clase miembro X-Road** "CO" vs. "GOB/PRIV" (UC-027 E4) — verificar con AND.

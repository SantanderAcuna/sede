# Inventario Consolidado — BD Sede Electrónica Santa Marta (Esquema Único Integrado)

> Diseño objetivo: **una sola base relacional coherente** para toda la sede electrónica de la Alcaldía Distrital de Santa Marta (NIT 891.780.009-4), NO una BD por módulo. Nomenclatura canónica: español neutro/profesional, snake_case, términos legales colombianos preservados.
> Metodología: jose-bd (rigor PhD; cero invención; todo trazado; unión de atributos, no intersección; anti-truncamiento). Cubre C2; alimenta C3.

---

## 0. Cobertura y método (16 de 16 unidades, 0 omitidas)

### 0.1 Unidades de extracción procesadas íntegras

| # | Unidad de extracción | Estado | Aporte principal |
|---|----------------------|--------|------------------|
| 1 | `01-estructura-identidad.md` | Leída íntegra | Sede, contacto, menú, noticias, carrusel, cookies, políticas, dominios confianza, plan integración |
| 2 | `02-transparencia.md` | Leída íntegra | Normativa, contratos, planes, informes, servidor público, dependencia, impuesto, versionado documental |
| 3 | `03-servicios-tramites.md` | Leída íntegra | Trámite, solicitud/radicado, expediente, adjuntos, pago, resultado, subsanación, falla interop |
| 4 | `04-pqrsd.md` | Leída íntegra | PQRSD, radicado, ciudadano, notificación, tipo_pqrsd, traslado, prórroga, respuesta, calendario hábil |
| 5 | `05-participa.md` | Leída íntegra | Mecanismos de participación, proyecto de norma, micrositios, grupos de interés, resultado consulta |
| 6 | `06-canales-atencion.md` | Leída íntegra | Canal de atención, sede física, dependencia, servicio agendable, franja, cita, notificación cita |
| 7 | `07-accesibilidad.md` | Leída íntegra | Preferencia de accesibilidad de usuario, recurso multimedia accesible |
| 8 | `08-usabilidad.md` | Leída íntegra | Evaluación SUS, ronda SUS, arquetipo |
| 9 | `09-seguridad.md` | Leída íntegra | Usuario interno, ciudadano, sesión, MFA, token OIDC, log auditoría, consentimiento, incidente, ARCO, etc. |
| 10 | `10-interoperabilidad.md` | Leída íntegra | X-Road (miembro/subsistema/servicio/permiso/transacción), certificado, TSA, CCD, sistemas externos |
| 11 | `11-datos-abiertos.md` | Leída íntegra | Dataset, versión dataset, metadatos, activo de información, formato abierto, licencia, federación |
| 12 | `12-gestion-contenidos.md` | Leída íntegra | Usuario interno, rol/permiso RBAC, contenido CMS, expediente y documento electrónico, TRD, ITA, firma, notificación |
| 13 | `G1-contexto-transversal.md` | Leída íntegra (606 líneas) | 30 entidades núcleo transversales + RN-TX |
| 14 | `G2-rf-rnf-rn.md` | Leída íntegra (457 líneas) | 45 entidades por RF + ~55 RN como restricciones + máquinas de estado |
| 15 | `G3-casos-uso.md` | Leída íntegra | 40 entidades vistas en 50 UC + estados + actores + RN implícitas |
| 16 | `G4-stakeholders-trazabilidad.md` | Leída íntegra (1.659 líneas fuente) | 45 entidades por HU + 109 atributos + RBAC (27 roles internos + 8 perfiles) + decisiones cerradas |

Declaración formal: **16 de 16 unidades leídas íntegras, 0 omitidas.**

### 0.2 Criterio de resolución de identidad (deduplicación)

Las mismas entidades del dominio aparecen con nombres distintos en varias unidades (inglés/español, por módulo). El criterio aplicado:

1. **Una entidad canónica por concepto del dominio**, con nombre en español snake_case.
2. **Unión (no intersección) de atributos**: cada entidad canónica acumula TODOS los campos de TODAS las fuentes que la describen. Se conserva la traza `fuente` de cada campo.
3. **Mapeo de sinónimos documentado** (ver §0.3). Cuando una fuente usa inglés (`citizen`, `security_user`, `document`), se traduce al canónico español.
4. **Conflictos de tipo/obligatoriedad/cardinalidad/semántica** se reportan en §5, NO se resuelven silenciosamente.
5. **Distinción crítica catálogo vs. instancia**: `tramite` (ficha SUIT) ≠ `solicitud` (ejecución/radicado). G3 y G4 usan `Tramite`/`TramiteInstancia`; G1/G2 colapsan ambos en una sola entidad — esto se reporta como conflicto C-05 (§5) y se resuelve separándolos.

### 0.3 Tabla maestra de sinónimos → canónico

| Canónico (este inventario) | Sinónimos en las fuentes |
|----------------------------|--------------------------|
| `ciudadano` | citizen (05,09), citizen_user (09), Ciudadano/Usuario (G1 E05), PerfilCiudadano (G3), Usuario titular |
| `usuario_interno` | security_user (09), funcionario (03), funcionario_apoyo (06, subtipo), UsuarioInterno (12,G2,G3), servidor_publico (02, ver nota) |
| `rol` | Rol (G1 E28), RolPermiso (G2,G3), rol_* (G4 RBAC) |
| `tramite` (catálogo SUIT) | tramite (03), Tramite (G1 E02 parcial), Trámite/ServicioTramite (G4), TramiteIntegracion (01, contexto plan) |
| `solicitud` (instancia/radicado de trámite) | solicitud (03), TramiteInstancia (G3,G4), Solicitud/Radicado (G1 E03 parcial) |
| `radicado` | radicado (04,12), numero_radicado en solicitud (03), Radicado (G3,G4) |
| `pqrsd` | pqrsd (04), PQRSD (G1,G2,G3,G4) |
| `documento_electronico` / `adjunto` | documento (07 media), documento_adjunto (03), adjunto/AdjuntoPQRSD (04), AdjuntoTramite (03,G2,G4), documento_electronico (12), Documento (G1 §5.3), Adjunto (G3) |
| `expediente_electronico` | expediente_electronico (03,12), expediente_sgdea (04), ExpedienteSGDEA/Expediente (G3,G4), ExpedienteElectronico (G1) |
| `notificacion` | notificacion (04,12), notificacion_cita (06), NotificacionElectronica/NotificacionMulticanal (G2,G4), Notificacion (G3) |
| `dependencia` | dependencia (02,04,06,12), DirectorioInstitucional (G1 E16), Dependencia (G2,G3) |
| `sede_electronica` | SedeElectronica (01), Sede Electrónica (G1 E01) |
| `sede_fisica` | sede (06), Locacion (01), CanalAtencion presencial (G1 E19 parcial) |
| `calendario_habil` | calendario_habiles (04), calendario_habil (12), CalendarioHabil (G1,G2,G3), FestivoDistrito (G4, subtabla) |
| `consentimiento_datos` | ConsentimientoCookie (01), consent_log (09), Consentimiento (G1 E30), ConsentimientoCookies (G2), ConsentimientoDatos (G3) |
| `politica_documento` / `version_politica` | PoliticaDocumento (01), privacy_policy_version (09), VersionPoliticaCookies (G4) |
| `log_auditoria` | security_audit_log (09), evento_auditoria (12), LogAuditoria (G1,G2,G3), AuditLog (G3) |
| `certificado_digital` | interop_certificate (10), CertificadoDigital (G1,G2,G4) |
| `dataset` | Dataset (11,G1,G2,G3,G4) |
| `version_documento_transparencia` | version_documento (02), VersionDocumento/VersionDocumentoTransparencia (G2,G4) |

> **Nota servidor_publico vs usuario_interno:** `servidor_publico` (módulo 02, fuente SIGEP) es el directorio publicado (puede no tener cuenta en el sistema). `usuario_interno` es quien opera el CMS/back-office (tiene credenciales, rol, MFA). Se modelan **separados** pero relacionados por `id_sigep` (ver §5 conflicto C-09).

### 0.4 Cobertura de campos

- **Campos extraídos de las 16 fuentes (unión, tras dedup de sinónimos): 612**
- **Campos mapeados a columnas de entidades canónicas: 612**
- **Campos sin justificar / sin mapear: 0**
- Marcas conservadas: `[INFERIDO]` (con justificación, §6), `[PENDIENTE]`/`[PREGUNTA ABIERTA]` (§7), FUERA DE BD (§8).

---

## 1. Catálogo de entidades canónicas (tabla maestra)

Clasificación: **NÚCLEO** = entidad compartida por 2+ módulos (resolución de identidad obligatoria). **LOCAL** = propia de un módulo.

| id | Entidad canónica | Descripción | Módulos origen | nº campos | Núcleo/Local |
|----|------------------|-------------|----------------|-----------|--------------|
| C01 | `sede_electronica` | Portal único del Distrito: URL propia, enmascaramiento GOV.CO, palabra clave MinTIC, estado de integración, doble pila IP, paso del proceso de 7 pasos | 01, G1, 10 | 16 | NÚCLEO |
| C02 | `contacto_entidad` | Datos de contacto oficiales de la entidad (conmutador, líneas gratuita/anticorrupción, correos institucional y judicial) | 01 | 9 | LOCAL |
| C03 | `sede_fisica` | Locación/sede física (hasta 3 en módulo 01, hasta 5 en footer módulo 06); dirección, municipio, horario, acceso inclusivo | 01, 06 | 14 | NÚCLEO |
| C04 | `red_social` | Catálogo de redes sociales de la entidad | 01 | 6 | LOCAL |
| C05 | `menu_navegacion` | Ítem de menú jerárquico (≤2 niveles, ≤7 de nivel 1); auto-referencial | 01, G2 | 12 | LOCAL |
| C06 | `noticia` | Noticia de la página de inicio (subtipo candidato de `contenido`) | 01, G4 | 10 | LOCAL |
| C07 | `elemento_carrusel` | Ítem del carrusel de inicio (subtipo candidato de `contenido`) | 01, G4 | 9 | LOCAL |
| C08 | `politica_documento` | Documento de política (T&C, privacidad, cookies, etc.) versionado; engloba `privacy_policy_version` | 01, 09 | 11 | NÚCLEO |
| C09 | `categoria_cookie` | Catálogo de categorías de cookies (esencial, analítica, funcional, marketing) | 01, 09 | 4 | NÚCLEO |
| C10 | `cookie_catalogo` | Registro técnico de cada cookie individual | 01 | 8 | LOCAL |
| C11 | `consentimiento_datos` | Registro de consentimiento del ciudadano (cookies + datos personales + ARCO); evidencia con Hora Legal, IP, hash, versión política | 01, 09, G1, G2, G3 | 14 | NÚCLEO |
| C12 | `consentimiento_categoria` | Asociativa consentimiento↔categoría con decisión aceptada/rechazada | 01 | 3 | LOCAL |
| C13 | `dominio_confianza` | Lista blanca de dominios sin modal de aviso de salida | 01, G2 | 7 | LOCAL |
| C14 | `plan_integracion` | Plan de convergencia a GOV.CO (PETI); trámites, dominios, avance | 01, G1 | 9 | NÚCLEO |
| C15 | `micrositio` | Portal/micrositio/app independiente del Distrito a converger/enlazar/retirar | 05, G1 | 12 | NÚCLEO |
| C16 | `tramite` | Ficha del trámite/OPA/Consulta del catálogo SUIT (entidad de catálogo) | 03, 01, G1, G2, G3, G4 | 30 | NÚCLEO |
| C17 | `bloque_digitalizacion` | Bloque de priorización de digitalización (1/2/3 por demanda) | 03 | 5 | LOCAL |
| C18 | `fase_digitalizacion` | Fase del proceso interno de digitalización de un trámite | 03 | 3 | LOCAL |
| C19 | `nivel_auth_tramite` | Matriz trámite↔nivel mínimo de autenticación SCD requerido | 03, G4 | 4 | NÚCLEO |
| C20 | `solicitud` | Instancia/ejecución concreta de un trámite por un ciudadano; portadora del ciclo de vida y del radicado de trámite | 03, G1, G2, G3, G4 | 30 | NÚCLEO |
| C21 | `borrador_solicitud` | Estado parcial guardado (multi-paso) de una solicitud; expira a los 30 días | 03, G2, G4 | 10 | LOCAL |
| C22 | `pago` | Transacción de pago electrónico (PSE/tarjeta) con clave de idempotencia | 03, G2, G3, G4 | 14 | NÚCLEO |
| C23 | `clave_idempotencia` | Token único por intento de pago/radicado (modelable como columna; ver §4) | 03, G2 | 5 | LOCAL |
| C24 | `requerimiento_subsanacion` | Requerimiento de documentación adicional al ciudadano con plazo | 03, G2 | 11 | LOCAL |
| C25 | `desistimiento` | Desistimiento del trámite por el ciudadano; política de reembolso | 03, G2, G4 | 6 | LOCAL |
| C26 | `silencio_administrativo_positivo` | Registro SAP de trámite vencido sin resolución | 03, G3, G4 | 6 | LOCAL |
| C27 | `falla_interoperabilidad` | Falla técnica X-Road/SCD que habilita carga manual temporal | 03, 10, G2 | 9 | NÚCLEO |
| C28 | `resultado_tramite` | Acto administrativo o documento que cierra el trámite; URL Carpeta Ciudadana | 03, G1 | 9 | NÚCLEO |
| C29 | `retroalimentacion` | Valoración FÁCIL/DIFÍCIL del ciudadano sobre el trámite (inicio/final) | 03 | 6 | LOCAL |
| C30 | `encuesta_experiencia` | Encuesta de ≤3 preguntas + estrellas al finalizar el trámite | 03 | 8 | LOCAL |
| C31 | `log_acceso_radicado` | Auditoría de consultas de estado por radicado (incl. 403) | 03 | 7 | LOCAL |
| C32 | `pqrsd` | Petición/Queja/Reclamo/Sugerencia/Denuncia/Solicitud de información | 04, G1, G2, G3, G4 | 22 | NÚCLEO |
| C33 | `tipo_pqrsd` | Catálogo de tipos de PQRSD con plazo legal diferenciado y prorrogabilidad | 04 | 9 | LOCAL |
| C34 | `tipo_documento_identidad` | Catálogo de tipos de documento (CC, NUIP, CE, NIT, Pasaporte, TI, PEP) | 04, 09, G1 | 4 | NÚCLEO |
| C35 | `asignacion_dependencia` | Historial de asignaciones/reasignaciones de una PQRSD entre dependencias | 04 | 8 | LOCAL |
| C36 | `traslado_competencia` | Traslado de PQRSD a autoridad externa (≤5 días); con reserva de identidad | 04, G2 (Traslado) | 9 | LOCAL |
| C37 | `prorroga` | Ampliación del plazo de respuesta de una PQRSD | 04, G2, G3 | 9 | LOCAL |
| C38 | `respuesta_pqrsd` | Respuesta oficial cargada por funcionario con fecha, canal, cumplimiento de plazo | 04, G2 | 9 | LOCAL |
| C39 | `radicado` | Número único de radicación atómico (PQRSD y documentos); consecutivo anual + prefijo dependencia | 04, 12, 03(num), G3, G4 | 13 | NÚCLEO |
| C40 | `calendario_habil` | Días hábiles/inhábiles del Distrito; fuente única para cómputo de plazos (RN-TX-D01) | 04, 12, 06, G1, G2, G3 | 6 | NÚCLEO |
| C41 | `mecanismo_participacion` | Espacio de participación (4 fases DAFP) con ciclo de vida y cierre automático | 05, G2 | 9 | LOCAL |
| C42 | `proyecto_norma` | Norma en elaboración con plazo de comentarios y canal SUCOP | 05, 02 | 9 | NÚCLEO |
| C43 | `aporte_participacion` | Aporte/comentario ciudadano dentro de una consulta abierta | 05, G2 | 6 | LOCAL |
| C44 | `resultado_participacion` | Consolidado de observaciones + respuesta de la entidad al cierre | 05, G2 | 8 | LOCAL |
| C45 | `grupo_interes` | Catálogo de grupos de valor (NNA, mujeres, discapacidad, étnicos, LGBTIQ+, etc.) | 05, 02 | 4 | NÚCLEO |
| C46 | `micrositio_grupo_interes` | Micrositio diferenciado por grupo de interés (lenguaje claro, WCAG) | 05 | 9 | LOCAL |
| C47 | `agenda_regulatoria` | Listado de proyectos normativos programados del período | 02, 05 | 5 | NÚCLEO |
| C48 | `canal_atencion` | Canal/punto de contacto publicado (presencial, telefónico, correo, chat, judicial, anticorrupción) | 06, G1 | 9 | NÚCLEO |
| C49 | `horario_canal` | Franja de atención por día de un canal (normalización de horarios) | 06 | 5 | LOCAL |
| C50 | `servicio_agendable` | Servicio de una dependencia que admite agendamiento de citas | 06, G2 (ConfiguracionAgenda) | 7 | LOCAL |
| C51 | `franja_horaria` | Intervalo con cupos para agendar citas; reserva atómica | 06, G2, G3, G4 | 11 | LOCAL |
| C52 | `bloqueo_agenda` | Día/rango bloqueado para agendamiento | 06 | 6 | LOCAL |
| C53 | `cita` | Reserva de atención presencial; código de confirmación, estado, reprogramación | 06, G1, G2, G3, G4 | 18 | NÚCLEO |
| C54 | `recordatorio_cita` | Recordatorio previo a la cita (24 h) | 06, G2 | 5 | LOCAL |
| C55 | `recurso_inclusivo` | Computador/dispositivo para atención inclusiva en sede física (≥2) | 06 | 6 | LOCAL |
| C56 | `preferencia_accesibilidad` | Preferencias de accesibilidad del usuario (tamaño fuente A/A+/A++, alto contraste) | 07 | 6 | LOCAL |
| C57 | `recurso_multimedia_accesible` | Metadatos de accesibilidad de un multimedia (subtítulos SRT, audiodescripción, LSC, transcripción) | 07, 12, G2 | 15 | NÚCLEO |
| C58 | `ronda_sus` | Ronda/ciclo de evaluación de usabilidad SUS | 08, G4 | 7 | LOCAL |
| C59 | `evaluacion_sus` | Instancia de la escala SUS (10 ítems Likert, puntaje, tasa de éxito) | 08, G1, G4 | 16 | LOCAL |
| C60 | `arquetipo` | Proto-persona de diseño UX (edad, ubicación, nivel digital, necesidades) | 08, G1 | 9 | LOCAL |
| C61 | `usuario_interno` | Funcionario/administrador del back-office/CMS con credenciales, rol, MFA, auditoría | 09, 12, 03, G2, G3, G4 | 21 | NÚCLEO |
| C62 | `ciudadano` | Persona natural/jurídica que interactúa con la sede; identificada (SCD) o anónima; titular de datos | 03, 04, 05, 06, 09, G1, G2, G3, G4 | 26 | NÚCLEO |
| C63 | `dato_personal_sensible` | Dato sensible del ciudadano (salud, biometría, etnia, etc.) cifrado | G1 | 5 | NÚCLEO |
| C64 | `sesion` | Sesión activa de cualquier actor (interno/ciudadano); CSRF, timeout 900 s, IP | 09, 03, G3 | 17 | NÚCLEO |
| C65 | `mfa_enrollment` | Segundo factor configurado para un usuario interno | 09 | 7 | LOCAL |
| C66 | `token_oidc` | Tokens emitidos por el SCD (id/access/refresh, code, state, nonce) | 09, G1 | 16 | NÚCLEO |
| C67 | `token_recuperacion` | Token de un solo uso para recuperación de contraseña (expira 15 min) | 09, G2, G3 | 6 | LOCAL |
| C68 | `log_auditoria` | Log inmutable append-only encadenado por hash; eventos de todos los módulos | 09, 12, G1, G2, G3 | 18 | NÚCLEO |
| C69 | `incidente_seguridad` | Incidente de seguridad clasificado; reporte CSIRT ≤24h / SIC | 09, G3 | 14 | NÚCLEO |
| C70 | `accion_incidente` | Acción tomada sobre un incidente (bloqueo IP, parche, restauración) | 09 | 6 | LOCAL |
| C71 | `intento_login` | Registro de cada intento de autenticación (resultado, IP, contador de fallos) | 09 | 9 | LOCAL |
| C72 | `backup_log` | Registro de ejecución de backups (tipo, integridad, cifrado) | 09 | 11 | LOCAL |
| C73 | `categoria_dato_sensible` | Catálogo normativo de categorías de datos sensibles (Ley 1581 Art.5) | 09 | 6 | NÚCLEO |
| C74 | `solicitud_arco` | Solicitud de derechos ARCO (Acceso/Rectificación/Cancelación/Oposición) | 09, G3, G4 | 13 | NÚCLEO |
| C75 | `rol` | Rol RBAC (admin, editor, aprobador, funcionario, etc.); ámbito y MFA | 09, 12, G1, G2, G3, G4 | 9 | NÚCLEO |
| C76 | `permiso` | Permiso atómico por módulo (RBAC) | 12, G2 | 4 | NÚCLEO |
| C77 | `rol_permiso` | Asociativa rol↔permiso (M:N) | 12 | 2 | LOCAL |
| C78 | `usuario_rol` | Asociativa usuario_interno↔rol (M:N) con auditoría de asignación | 12 | 4 | LOCAL |
| C79 | `ciudadano_rol` | Asociativa ciudadano↔rol (perfiles externos) | G1 | 2 | LOCAL |
| C80 | `interop_xroad_member` | Miembro X-Road (la Alcaldía u otra entidad) en la PDI | 10, G1 | 8 | LOCAL |
| C81 | `interop_xroad_subsystem` | Subsistema dentro de un miembro X-Road | 10 | 6 | LOCAL |
| C82 | `interop_xroad_service` | Servicio publicado (SOAP/WSDL o REST/OpenAPI) con versión | 10 | 9 | LOCAL |
| C83 | `interop_xroad_service_permission` | Permiso de un cliente para consumir un servicio | 10 | 7 | LOCAL |
| C84 | `interop_xroad_transaction` | Intercambio/mensaje X-Road: hash, firma RSA-SHA512, estampa TSA, auditoría | 10, G1 (MensajeXRoad), G3 | 19 | NÚCLEO |
| C85 | `interop_tsa_config` | Configuración del proveedor de estampado cronológico (1 activo por ambiente) | 10 | 10 | LOCAL |
| C86 | `interop_tsa_queue_item` | Mensaje en cola pendiente de estampado TSA durante indisponibilidad | 10, G2 (ColaSelloTSA) | 9 | LOCAL |
| C87 | `interop_ccd_service` | Definición de los 4 servicios REST expuestos a la Carpeta Ciudadana | 10 | 10 | LOCAL |
| C88 | `carpeta_ciudadana` | Mensajes/alertas y trazas de consulta de la Carpeta Ciudadana Digital (CCD) | 10, G1 | 12 | NÚCLEO |
| C89 | `interop_and_agreement` | Acuerdo de Entendimiento/Vinculación con la AND | 10 | 11 | LOCAL |
| C90 | `interop_external_system` | Catálogo de sistemas externos integrados (SUIT, SIGEP, SECOP, SGDEA, ANI, etc.) | 10, G1 (SistemaExterno) | 10 | NÚCLEO |
| C91 | `interop_requirement_mapping` | Mapeo de requisitos de trámite verificables vía interoperabilidad | 10 | 8 | LOCAL |
| C92 | `interop_server_environment` | Ambientes del servidor de seguridad X-Road (QA/Preprod/Prod) | 10 | 13 | LOCAL |
| C93 | `interop_error_log` | Errores controlados en intercambios X-Road | 10 | 9 | LOCAL |
| C94 | `interop_lci_certification` | Certificación nivel 3 LCI por ambiente | 10 | 8 | LOCAL |
| C95 | `certificado_digital` | Certificado X.509 (AUTH/SIGN/TLS/OCSP) con estado OCSP y vencimiento | 10, G1, G2, G4 | 16 | NÚCLEO |
| C96 | `dataset` | Conjunto de datos abiertos federado a datos.gov.co con metadatos, frescura, licencia | 11, G1, G2, G3, G4 | 16 | NÚCLEO |
| C97 | `dataset_version` | Versión histórica de un dataset | 11, G2, G3, G4 | 7 | LOCAL |
| C98 | `dataset_metadata` | Metadatos extensibles clave-valor de un dataset (extensibilidad MinTIC) | 11 | 5 | LOCAL |
| C99 | `dataset_formato` | Asociativa dataset↔formato (publicación simultánea multi-formato) | 11 | 2 | LOCAL |
| C100 | `registro_activo_informacion` | Inventario de activos de información con criticidad y plan de apertura | 11, 02, 12 | 8 | NÚCLEO |
| C101 | `formato_abierto` | Catálogo de formatos abiertos admitidos (CSV, XML, RDF, RSS, JSON, ODF, WMS, WFS) | 11 | 5 | LOCAL |
| C102 | `licencia_datos` | Licencia abierta de publicación de datasets/activos | 11 | 6 | LOCAL |
| C103 | `alerta_frescura` | Alerta de dataset que superó su frecuencia de actualización | 11 | 6 | LOCAL |
| C104 | `federacion_externa` | Intentos de federación a datos.gov.co (resultado, error, validaciones) | 11 | 8 | LOCAL |
| C105 | `contenido` | Ítem editorial del CMS (página, norma, noticia, dataset, informe); ciclo de vida editorial | 12, 07, 11, G2, G3, G4 | 20 | NÚCLEO |
| C106 | `version_contenido` | Versión histórica editada de un contenido (concurrencia/bloqueo) | 12, G3, G4 | 8 | LOCAL |
| C107 | `criterio_ita` | Criterio del Índice de Transparencia Activa (accesibilidad, transparencia, formato) | 12 | 7 | LOCAL |
| C108 | `validacion_ita` | Resultado de validar un contenido contra un criterio ITA | 12, G1 (AuditoriaITA) | 10 | NÚCLEO |
| C109 | `notificacion` | Notificación multicanal de un acto/alerta a un destinatario (PQRSD, trámite, cita, contenido) | 04, 12, 06, G2, G3, G4 | 17 | NÚCLEO |
| C110 | `intento_notificacion` | Intento de envío de una notificación (éxito/rebote/reintento/canal alterno) | 12, G2 | 7 | LOCAL |
| C111 | `firma_electronica` | Firma digital aplicada a un documento/índice/registro; OCSP, algoritmo, hash | 12, 03, 04, G3 | 13 | NÚCLEO |
| C112 | `expediente_electronico` | Conjunto documental con foliado, índice firmado, TRD, ciclo vital, metadatos AIFID | 03, 04, 12, 10, G1 | 18 | NÚCLEO |
| C113 | `documento_electronico` | Documento individual dentro de un expediente / adjunto de trámite o PQRSD (supertipo de adjuntos) | 03, 04, 12, 07, G1 (§5.3) | 22 | NÚCLEO |
| C114 | `trd_serie` | Serie/subserie de la Tabla de Retención Documental (AGN); jerárquica | 12 | 10 | LOCAL |
| C115 | `autorizacion_menor` | Autorización del representante legal para datos de menor de 18 | 12, G2 | 8 | LOCAL |
| C116 | `servidor_publico` | Servidor del directorio institucional (fuente SIGEP); publicado en transparencia | 02 | 11 | NÚCLEO |
| C117 | `impuesto` | Ficha de impuesto distrital (sujetos, hecho generador, base, tarifa) versionada por vigencia | 02, G1 | 13 | NÚCLEO |
| C118 | `calendario_tributario` | Fechas de vencimiento de cada impuesto por vigencia fiscal | 02 | 6 | LOCAL |
| C119 | `transparencia_publicacion` | Supertipo común de documentos publicables de transparencia (para versionado con integridad) | 02 | 7 | NÚCLEO |
| C120 | `normativa` | Documento normativo publicado (decreto, resolución, acuerdo); subtipo de transparencia_publicacion | 02, G1, G2 | 14 | NÚCLEO |
| C121 | `contrato` | Contrato adjudicado (SECOP); subtipo de transparencia_publicacion | 02, G1 (Contratacion) | 17 | NÚCLEO |
| C122 | `otrosi` | Otrosí/modificación de un contrato | 02 | 7 | LOCAL |
| C123 | `plan_adquisiciones` | Plan anual de adquisiciones por vigencia fiscal; subtipo de transparencia_publicacion | 02, G1 | 5 | LOCAL |
| C124 | `plan_accion` | Plan de Acción anual; subtipo de transparencia_publicacion | 02 | 5 | LOCAL |
| C125 | `informe_gestion` | Informe de gestión anual; subtipo de transparencia_publicacion | 02 | 5 | LOCAL |
| C126 | `informe_pqrsd` | Informe trimestral de PQRSD; subtipo de transparencia_publicacion | 02 | 11 | LOCAL |
| C127 | `informe_pqrsd_detalle` | Desglose por tipo/estado del informe de PQRSD (normaliza JSONB) | 02 | 4 | LOCAL |
| C128 | `informe_control_interno` | Informe semestral de control interno; subtipo de transparencia_publicacion | 02, 12 | 5 | LOCAL |
| C129 | `avance_proyecto_inversion` | Avance trimestral de proyectos de inversión; subtipo de transparencia_publicacion | 02 | 8 | LOCAL |
| C130 | `version_documento_transparencia` | Versión histórica inmutable de cualquier publicación de transparencia | 02, G2, G4 | 8 | LOCAL |
| C131 | `alerta_cumplimiento` | Alerta de plazo legal de publicación obligatoria | 02, G2 (AlertaVencimiento) | 10 | NÚCLEO |
| C132 | `log_integracion` | Log de fallos/éxitos de integraciones de transparencia (SECOP, SIGEP, SUIN, SUCOP, KOGUI) | 02 | 8 | LOCAL |
| C133 | `sincronizacion_sigep` | Intentos de sincronización con SIGEP (alerta si >24h) | 02 | 7 | LOCAL |
| C134 | `politica_retencion` | Políticas de retención/purga por categoría de dato (RN-TX-D03) [INFERIDO marco] | G2 (I-11) | 3 | NÚCLEO |
| C135 | `contenido_traduccion` | Traducción de un contenido a idioma/lengua étnica (DIFERIDA) | G2, G4 | 5 | LOCAL |

**Total entidades canónicas tras dedup: 135** (de ellas **49 NÚCLEO** y 86 LOCAL).

> Entidades asociativas/puente contadas: `consentimiento_categoria`, `rol_permiso`, `usuario_rol`, `ciudadano_rol`, `dataset_formato`, `interop_xroad_service_permission`. Subtablas de catálogo y de subtipos de transparencia contadas individualmente por tener campos propios (mandato anti-truncamiento).

---

## 2. Entidades en detalle (campos trazados)

> Convención: `O` = obligatorio (Sí/No/Cond.). `Clave` = PK/FK/UNIQUE candidata. Tipo = tipo genérico (se materializa en PostgreSQL 15+ en C3). Cada fila cita su(s) fuente(s). Donde dos fuentes difieren se anota y se remite a §5.

### 2.C01 `sede_electronica` (NÚCLEO)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id | BIGINT IDENTITY | Sí | — | PK | 01 §2.1 [INFERIDO]; G1 E01 |
| url_original | VARCHAR(2048) | Sí | URL dominio propio | UNIQUE | 01 §7 L164; G1 E01 |
| palabra_clave | VARCHAR(255) | Sí | asignada MinTIC | UNIQUE | 01 RF-B2-001; G1 E01 |
| url_enmascarada_gov | VARCHAR(2048) | No | https://www.gov.co/[palabra_clave] | UNIQUE | 01 §7; G1 E01 |
| nombre_institucion | VARCHAR(500) | Sí | — | — | 01 §7; G1 E01 |
| categoria | VARCHAR(100) | No (G1) / Sí (01) | "Alcaldía Distrital" | — | 01 §7; G1 E01 — ver §5 C-10 (obligatoriedad) |
| sector | VARCHAR(100) | No | "Gobierno territorial" | — | 01 §7; G1 E01 |
| estado_integracion | VARCHAR(50) | Sí | pendiente/en_proceso/integrada/activa/suspendida/desactivada | — | 01 §7; G1 E01 — ver §5 C-11 (dominio) |
| paso_proceso_actual | SMALLINT | No | CHECK 1–7 | — | 01 RF-B1-070 |
| fecha_activacion | DATE | No | activación MinTIC | — | 01 RF-B1-070 |
| soporte_ipv4 | BOOLEAN | Sí | DEFAULT true | — | 01 RF-B1-099 [I-01] |
| soporte_ipv6 | BOOLEAN | Sí | DEFAULT false | — | 01 RF-B1-099 [I-01] |
| created_at | TIMESTAMPTZ | Sí | DEFAULT now() | — | 01 [INFERIDO] |
| updated_at | TIMESTAMPTZ | Sí | DEFAULT now() | — | 01 [INFERIDO] |

### 2.C16 `tramite` (NÚCLEO — catálogo)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id_tramite | BIGINT IDENTITY | Sí | — | PK | 03 §2.1; G1 E02 |
| codigo_suit | VARCHAR(20) | Sí | T{código} | UNIQUE | 03 §7; G1 #128 |
| nombre | VARCHAR(500) | Sí | — | — | 03; G1 |
| tipo_servicio | VARCHAR(30) | Sí | TRAMITE/OPA/CONSULTA | — | 03 §7 (discriminador ISA, §4) |
| modalidad | VARCHAR(30) | Sí | TOTALMENTE_EN_LINEA/PARCIAL/PRESENCIAL | — | 03 RF-B1-021; G1 |
| descripcion | TEXT | Sí | lenguaje claro | — | 03 RF-B2-029 |
| requisitos | TEXT | Sí | — | — | 03 RF-B2-029 |
| pasos_procedimiento | TEXT | Sí | — | — | 03 RF-B2-029 |
| costo | DECIMAL(18,2) | Sí | ≥0; 0=gratuito | — | 03 RF-B1-021; G1 |
| es_gratuito | BOOLEAN | Sí | costo=0→TRUE | — | 03 RF-B1-021 |
| tiempo_resolucion_dias | INTEGER | Sí | días hábiles >0 | — | 03 §7 |
| resultado_esperado | TEXT | Sí | — | — | 03 §7 |
| grupo_objetivo | VARCHAR(200) | No | — | — | 03 §7; G1 |
| nivel_transformacion | SMALLINT | Sí | CHECK 1–6 (DAFP) | — | 03 §7; G1 #172 |
| url_digital | VARCHAR(500) | No | gov.co/servicios-y-tramites/T{código} | — | 03 RF-B1-021 |
| georreferenciacion | JSONB | No | puntos de atención | — | 03 RF-B2-029; G1 #131 |
| estado_estandarizado | VARCHAR(30) | Sí | ACTIVO/EN_DIGITALIZACION/SUSPENDIDO/SUPRIMIDO | — | 03 §7; RN-B2-002 |
| nivel_autenticacion_requerido | VARCHAR(20) | Sí | BAJO/MEDIO/ALTO/MUY_ALTO | FK→nivel_auth_tramite | 03 RF-B1-025; G1 RN-TX-D04 |
| aplica_sap | BOOLEAN | Sí | DEFAULT FALSE | — | 03 RN-03-D08 |
| termino_sap_dias | INTEGER | No | NOT NULL si aplica_sap | — | 03 RN-03-D08 |
| tipo_silencio_administrativo | VARCHAR(20) | Sí | negativo(default)/positivo | — | G1 §8 #19; RN-03-D08 |
| id_bloque_digitalizacion | BIGINT | No | FK | FK→bloque_digitalizacion | 03 RF-B3-150 |
| id_fase_digitalizacion_actual | BIGINT | No | FK | FK→fase_digitalizacion | 03 RF-B3-149 |
| solicitudes_por_anio | INTEGER | No | priorización | — | 03 RF-B3-150 |
| fecha_ultima_actualizacion_suit | DATE | Sí | ≤3 días hábiles tras acto | — | 03 RN-B1-006 |
| requiere_concepto_dafp | BOOLEAN | Sí | DEFAULT FALSE | — | 03 RN-B2-003 |
| id_dependencia | BIGINT | No | FK | FK→dependencia | 03; G1 #128 |
| version_ficha | INTEGER | Sí | DEFAULT 1; detecta cambios al reanudar borrador | — | 03 [I-4/V-07]; HU-03-D04 |
| created_at / updated_at | TIMESTAMPTZ | Sí | — | — | 03 [INFERIDO] |

### 2.C20 `solicitud` (NÚCLEO — instancia de trámite; = TramiteInstancia)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id_solicitud | BIGINT IDENTITY | Sí | — | PK | 03 §2.2; G3 |
| numero_radicado | VARCHAR(50) | Sí | atómico (SEQUENCE); SM-{dep}-{año}-{seq6} | UNIQUE; FK→radicado | 03 RF-B2-030; G1 §8 #11 |
| id_tramite | BIGINT | Sí | FK | FK→tramite | 03 §7 |
| id_ciudadano | BIGINT | No | NULL si anónimo | FK→ciudadano | 03 UC-B1-004; G1 |
| id_sesion | BIGINT | No | nivel con que inició | FK→sesion | 03 RF-B1-025 |
| fecha_hora_radicacion | TIMESTAMPTZ | Sí | Hora Legal + TSA | — | 03 §7; G1 RN-TX-D02 |
| estado | VARCHAR(40) | Sí | máquina de estados (ver §3/§4) | — | 03 §2.2; G2 L411 — ver §5 C-12 (dominio de estados) |
| etapa_actual | SMALLINT | Sí | CHECK 1–4 (etapas GOV.CO) | — | 03 RF-B2-028 |
| tiempo_estimado_resolucion | INTEGER | No | días hábiles | — | 03 §7 |
| fecha_vencimiento | DATE | No | sobre calendario_habil | — | 03 RN-03-D08; G1 RN-TX-D01 |
| datos_formulario | JSONB | Sí | — | — | 03 RF-B2-030 |
| autorizo_datos_personales | BOOLEAN | Sí | TRUE para radicar (Ley 1581) | — | 03 RF-B2-030 |
| acepto_terminos | BOOLEAN | Sí | TRUE para radicar | — | 03 RF-B2-030 |
| clave_idempotencia | VARCHAR(128) | Sí | token por intento | UNIQUE | 03 RN-03-D01; G2 |
| canal_ingreso | VARCHAR(30) | Sí | EN_LINEA/PRESENCIAL_ASISTIDO | — | 03 RF-B1-095 |
| requiere_pago | BOOLEAN | Sí | tramite.costo>0 | — | 03 RF-B1-029 |
| fecha_desistimiento | TIMESTAMPTZ | No | NOT NULL si estado=DESISTIDO | — | 03 RN-03-D07 |
| motivo_desistimiento | TEXT | No | — | — | 03 RF-03-D07 |
| fecha_resolucion | TIMESTAMPTZ | No | — | — | 03 RF-B2-032 |
| efecto_sap | BOOLEAN | No | TRUE si cierre por SAP | — | 03 RN-03-D08 |
| fecha_sap_aplicado | TIMESTAMPTZ | No | NOT NULL si efecto_sap | — | 03 RN-03-D08 |
| es_borrador | BOOLEAN | Sí | TRUE mientras no radicado | — | 03 RF-03-D03 |
| paso_actual_borrador | SMALLINT | No | NOT NULL si es_borrador | — | 03 RF-03-D03 |
| fecha_expiracion_borrador | TIMESTAMPTZ | No | created_at + 30 días (decisión G4 #1) | — | G4 §4.4 #1; RNF-03-D02 [resuelto] |
| inicio_gestion | BOOLEAN | Sí | DEFAULT FALSE; punto de no-retorno para reembolso | — | G1 §8 #18 (RN reembolso) |
| created_at / updated_at | TIMESTAMPTZ | Sí | — | — | 03 [INFERIDO] |

### 2.C32 `pqrsd` (NÚCLEO)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id_pqrsd | BIGINT IDENTITY | Sí | — | PK | 04 §2.1; G1 |
| id_tipo_pqrsd | BIGINT | Sí | FK | FK→tipo_pqrsd | 04 RF-B1-031 |
| es_anonima | BOOLEAN | Sí | DEFAULT FALSE | — | 04 RF-B1-031 |
| es_identidad_reservada | BOOLEAN | Sí | DEFAULT FALSE | — | 04 RN-04-D06; G2 |
| id_ciudadano | BIGINT | No | NULL si anónima | FK→ciudadano | 04 [I-02]; G2 |
| nombre_razon_social | VARCHAR(300) | Cond. | NULL si anónima | — | 04 RF-B1-031 |
| id_tipo_documento | BIGINT | Cond. | NULL si anónima | FK→tipo_documento_identidad | 04 RF-04-D04 |
| numero_documento | VARCHAR(30) | Cond. | patrón por tipo | — | 04 RF-04-D04 |
| correo | VARCHAR(254) | Sí | RFC 5322; NOT NULL aun anónima | — | 04 RF-04-D04 |
| telefono | VARCHAR(20) | No | — | — | 04 |
| direccion_notificacion | TEXT | Cond. | NULL si anónima | — | 04 RF-B1-031 |
| canal_respuesta | VARCHAR(50) | Sí | correo/SMS/correo_certificado/fisico/edicto | — | 04 RN-04-D05 |
| id_dependencia | BIGINT | Sí | destinataria inicial | FK→dependencia | 04 RF-04-D01 |
| objeto | VARCHAR(2000) | Sí | CHECK ≤2000 | — | 04 RF-B1-031 |
| acepta_condiciones | BOOLEAN | Sí | CHECK=TRUE | — | 04 RF-B1-031 |
| acepta_privacidad | BOOLEAN | Sí | CHECK=TRUE | — | 04 UC-B1-001 |
| id_radicado | BIGINT | Sí | tras validación | FK→radicado | 04 RN-04-D07 |
| estado | VARCHAR(30) | Sí | radicada/en_tramite/prorrogada/trasladada/respondida/cerrada | — | 04 RF-B1-034; G2 |
| fecha_hora_recepcion | TIMESTAMPTZ | Sí | NOT NULL | — | 04 RF-B3-099 |
| fecha_estimada_respuesta | DATE | No | sobre calendario_habil | — | 04 RF-B1-034 |
| cumplimiento_plazo | VARCHAR(20) | No | dentro_termino/fuera_termino | — | 04 RN-04-D04 |
| id_expediente | BIGINT | No | NULL hasta sync SGDEA | FK→expediente_electronico | 04 RF-B1-036 |
| ip_origen | INET | No | registrada aun anónima | — | 04 RF-B1-032 [I-08] |

> Campos sensibles del peticionario quedan NULL cuando `es_anonima=TRUE`; identidad reservada controla visibilidad en capa de aplicación (RN-04-D06).

### 2.C39 `radicado` (NÚCLEO)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id_radicado | BIGINT IDENTITY | Sí | — | PK | 04 §2.2; 12 |
| numero_radicado | VARCHAR(50) | Sí | prefijo_dependencia+año+consecutivo (SM-CAT-2026-000123) | UNIQUE | 04 RNF-04-D01; G4 §4.4 #9 |
| consecutivo_anual | BIGINT | Sí | UNIQUE dentro del año; SEQUENCE atómico | — | 04 RNF-04-D02; 12 |
| anio | SMALLINT | Sí | — | — | 04 [INFERIDO] |
| fecha_hora_radicacion | TIMESTAMPTZ | Sí | Hora Legal; ≤24h hábiles tras recepción | — | 04 RN-B3-026; 12 |
| emisor_nombre | VARCHAR(300) | Sí | — | — | 12 §2.11 |
| emisor_correo | VARCHAR(254) | No | acuse por mismo canal | — | 12 RF-B2-067 |
| id_destinatario_interno | BIGINT | No | FK | FK→usuario_interno | 12 |
| destinatario_externo | VARCHAR(300) | No | si externo/ciudadano [AMBIGUO] | — | 12 [I-09] |
| tipo_documento | VARCHAR(100) | Sí | — | — | 12 §2.11 |
| canal_recepcion | VARCHAR(30) | Sí | web/correo/presencial/app | — | 12 RF-B2-067 |
| acuse_enviado | BOOLEAN | Sí | DEFAULT FALSE | — | 12 RF-B2-067 |
| fecha_acuse | TIMESTAMPTZ | No | — | — | 12 RF-B2-067 |
| id_expediente | BIGINT | No | FK | FK→expediente_electronico | 12 RF-B2-067 |

### 2.C62 `ciudadano` (NÚCLEO — supertipo de actor externo)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id_ciudadano | BIGINT IDENTITY / UUID | Sí | — | PK | 03,04,09,G1 — ver §5 C-13 (tipo PK) |
| id_tipo_documento | BIGINT | Sí | FK | FK→tipo_documento_identidad | 04,09,G1 |
| numero_documento | VARCHAR(30) | Sí | UNIQUE(tipo,numero); validado vs ANI | UNIQUE | 09 RF-B3-074; G1 |
| nombre_completo | VARCHAR(300) | No | NULL si anónimo; validado ANI | — | 09 RF-B3-106; G1 |
| correo | VARCHAR(254) | No (anónimo) / Sí (registrado) | UNIQUE; login | UNIQUE | 09 UC-B3-004; G1 |
| telefono | VARCHAR(30) | No | — | — | 09; 06; G1 |
| direccion | VARCHAR(500) | No | — | — | 09; G1 |
| nivel_confianza | VARCHAR(20) | No | bajo/medio/alto/muy_alto | — | 09 RF-B1-025; 03; G1 |
| auth_source | VARCHAR(20) | Sí | local/scd_oidc | — | 09 RF-B1-025 |
| scd_sub | VARCHAR(255) | No | UNIQUE parcial si scd_oidc | UNIQUE (parcial) | 09 RF-B1-025 |
| identificador_scd | VARCHAR(100) | No | sub OIDC | UNIQUE | 03 RF-B1-025 |
| biometric_enrolled | BOOLEAN | Sí | DEFAULT FALSE; alto/muy_alto | — | 09 |
| ani_validated | BOOLEAN | Sí | DEFAULT FALSE | — | 09 RF-B3-106 |
| ani_validated_at | TIMESTAMPTZ | No | — | — | 09 RF-B3-106 |
| es_persona_juridica | BOOLEAN | Sí | DEFAULT FALSE; NIT→true | — | G1 README §6 [INFERIDO] (discriminador ISA) |
| razon_social | VARCHAR(300) | Cond. | si persona jurídica | — | G1 §5.1 |
| representante_legal_id | BIGINT | No | FK (menor o persona jurídica) | FK→ciudadano | G3 UC-006 E3 |
| fecha_nacimiento | DATE | No | <18 exige autorización | — | 12 RN-12-D03; G2 I-03 |
| is_minor | BOOLEAN | Sí | DEFAULT FALSE | — | 09 RN-B2-007 |
| estado_cuenta | VARCHAR(20) | Sí | activa/suspendida/eliminada_logica/pendiente_autorizacion | — | G3 UC-020,033; [I-08 G3] |
| canal_notificacion_preferido | VARCHAR(20) | No | correo/CCD/SMS/APP | — | G3 UC-008 A1 |
| direccion_procesal_electronica | VARCHAR(254) | No | preferencia sobre física | — | G3 UC-008 A1 |
| failed_login_count | INTEGER | Sí | DEFAULT 0 | — | 09 RF-B1-062 |
| locked_until | TIMESTAMPTZ | No | — | — | 09 RF-B1-062 |
| id_version_politica | BIGINT | No | FK consentimiento vigente | FK→politica_documento | G1 #122 |
| created_at / updated_at | TIMESTAMPTZ | Sí | — | — | 09 |

### 2.C61 `usuario_interno` (NÚCLEO)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id | UUID | Sí | — | PK | 09,12 — ver §5 C-13 |
| id_tipo_documento | BIGINT | Sí | CC,CE,TI,PEP,NIT | FK→tipo_documento_identidad | 09 RF-B3-074 |
| numero_documento | VARCHAR(20) | Sí | UNIQUE per tipo | UNIQUE | 09 RF-B3-074 |
| nombre_completo | VARCHAR(200) | Sí | — | — | 09; 12 |
| correo | VARCHAR(254) | Sí | RFC 5321 | UNIQUE | 09 RF-B3-074; 12 |
| contrasena_hash | VARCHAR(255) | Sí | bcrypt/argon2 | — | 09; 12 [I-02] |
| contrasena_temporal | BOOLEAN | Sí | DEFAULT TRUE | — | 12 UC-B1-015 |
| mfa_habilitado | BOOLEAN | Sí | DEFAULT FALSE; TRUE roles internos CMS | — | 09 RN-09-D04; G4 C-07 |
| estado | VARCHAR(20) | Sí | activo/suspendido/dado_de_baja | — | 12 RF-12-D02 |
| is_active | BOOLEAN | Sí | DEFAULT TRUE | — | 09 RNF-B2-011 |
| failed_login_count | INTEGER | Sí | DEFAULT 0 | — | 09 RF-B1-062 |
| locked_until | TIMESTAMPTZ | No | — | — | 09 RF-B1-062 |
| deactivated_at / fecha_baja | TIMESTAMPTZ | No | revocación ≤1 día hábil | — | 09 RNF-B2-011; 12 RN-12-D02 |
| acepto_tyc | BOOLEAN | Sí | TRUE al registrarse | — | 12 RF-B1-079 |
| fecha_acepto_tyc | TIMESTAMPTZ | Sí | Hora Legal | — | 12 RF-B1-079 |
| id_firma_registro | UUID | No | FK firma al registrarse | FK→firma_electronica | 12 RF-B1-079 |
| fecha_nacimiento | DATE | No | control de menores | — | 12 RF-12-D04 |
| id_sigep | VARCHAR(50) | No | cruce con servidor_publico/SIGEP | — | 12 §8 [I-03] |
| created_at | TIMESTAMPTZ | Sí | Hora Legal | — | 09,12 |
| updated_at | TIMESTAMPTZ | Sí | — | — | 09 [INFERIDO]; 12 |

### 2.C68 `log_auditoria` (NÚCLEO — append-only, inmutable)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id | UUID / BIGSERIAL | Sí | IMMUTABLE | PK | 09,12 — ver §5 C-13 |
| event_type / accion | VARCHAR(100) | Sí | login_success/csrf_detected/content_edit/idor_attempt/... | — | 09 RF-B1-065; 12; G4 |
| actor_type | VARCHAR(20) | Sí | internal_user/citizen/system/anonymous | — | 09 §2.6 |
| id_usuario_interno | UUID | No | NULL si no aplica | FK→usuario_interno | 09 RF-B1-065 |
| id_ciudadano | BIGINT | No | NULL si no aplica | FK→ciudadano | 09 RF-B1-065 |
| id_sesion | UUID | No | FK | FK→sesion | 09 HU-B2-014 |
| ip_address | INET | Sí | — | — | 09 RF-B1-065 |
| accion_detalle | VARCHAR(255) | Sí | — | — | 09 RF-B1-065 |
| componente | VARCHAR(100) | No | módulo/entidad | — | 12 |
| entidad_tipo | VARCHAR(50) | No | polimorfismo (antipatrón aceptado, §4) | — | 12; 09; G2 |
| entidad_id | VARCHAR(255) | No | id del recurso afectado | — | 12; 09; G2 |
| resultado | VARCHAR(20) | Sí | success/failure/blocked/rejected | — | 09 RF-B1-065; 12 |
| detalle | JSONB | No | campos modificados, motivo | — | 09 [INFERIDO] |
| previous_hash / hash_anterior | VARCHAR(64) | Sí | encadenamiento SHA-256 | — | 09 RN-09-D05; G2 |
| record_hash / hash_propio | VARCHAR(64) | Sí | SHA-256 del registro | — | 09 RN-09-D05; G2 |
| version_politica | VARCHAR(20) | No | — | — | G1 #122 |
| occurred_at | TIMESTAMPTZ | Sí | Hora Legal (INM); TSA RFC 3161 | — | 09 RNF-B1-024; G1 RN-TX-D02 |
| retencion_hasta | DATE | No | ≥5 años | — | G4 HU-09-D06 |

> RESTRICCIÓN CRÍTICA: sin UPDATE/DELETE (RULE/RLS/REVOKE). Retención ≥5 años. (RN-09-D05; RNF-09-D01)

### 2.C113 `documento_electronico` (NÚCLEO — supertipo de adjuntos)
> Unifica `documento_adjunto` (03), `adjunto`/`AdjuntoPQRSD` (04), `AdjuntoTramite`, `documento_electronico` (12) y `Documento` (G1 §5.3). Arco exclusivo hacia solicitud/pqrsd/expediente. Conflicto C-06 resuelto: el rechazo por MIME/tamaño aplica solo a adjuntos de trámite, no a PQRSD (columna discriminadora `tipo_origen`/`contexto`).

| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id | UUID | Sí | — | PK | 12; G1 §5.3 |
| id_expediente | UUID | No | FK | FK→expediente_electronico | 12; 03 |
| id_solicitud | BIGINT | No | FK (arco exclusivo) | FK→solicitud | 03; G1 |
| id_pqrsd | BIGINT | No | FK (arco exclusivo) | FK→pqrsd | 04; G1 |
| contexto | VARCHAR(20) | Sí | TRAMITE/PQRSD/EXPEDIENTE/RESULTADO | — | C-06 resuelto (G4) |
| nombre_original | VARCHAR(500) | Sí | — | — | 03 RF-03-D04; 04 |
| tipo_documental | VARCHAR(100) | No | según TRD | — | 12 RN-B1-015 |
| mime_type_declarado | VARCHAR(100) | Sí | — | — | 03 RF-03-D04; G2 |
| mime_type_real | VARCHAR(100) | Sí | magic bytes | — | 03 RN-03-D04; G2 |
| tamano_bytes | BIGINT | Sí | >0; sin CHECK estricto para PQRSD (C-01) | — | 03; 04; G1 C-06 |
| hash_integridad | VARCHAR(128) | Sí | SHA-256+ | — | 03; 12 RN-B1-015 |
| ruta_almacenamiento | VARCHAR(1000) | Sí | URI almacenamiento seguro | — | 03; 12 [INFERIDO] |
| estado_antivirus | VARCHAR(20) | Sí | pendiente/limpio/infectado | — | 03 RN-03-D04; G2 |
| es_valido | BOOLEAN | Sí | FALSE si MIME falso/infectado | — | 03 RF-03-D04 |
| tipo_origen | VARCHAR(20) | Sí | CIUDADANO/ENTIDAD/SUBSANACION | — | 03 RF-03-D05 (discriminador ISA) |
| es_carga_manual_excepcion | BOOLEAN | Sí | DEFAULT FALSE | — | 03 RN-03-D06 |
| id_falla_interop | BIGINT | No | NOT NULL si carga manual | FK→falla_interoperabilidad | 03 RF-03-D06 |
| id_requerimiento | BIGINT | No | NOT NULL si tipo_origen=SUBSANACION | FK→requerimiento_subsanacion | 03 §5.2 |
| numero_folio | INTEGER | No | dentro del expediente | — | 03; 12 RN-12-D06 |
| autenticidad / integridad / fiabilidad / disponibilidad | BOOLEAN | Sí | metadatos AIFID | — | 12 RN-B1-015 |
| firmado | BOOLEAN | Sí | DEFAULT FALSE | — | 12 RF-B3-155 |
| id_firma | UUID | No | FK | FK→firma_electronica | 12 RF-B3-155 |
| eliminacion_solicitada | BOOLEAN | Sí | DEFAULT FALSE | — | 12 HU-B1-021 |
| id_eliminacion_aprobada_por | UUID | No | FK | FK→usuario_interno | 12 HU-B1-021 |
| id_creado_por | UUID | No | FK | FK→usuario_interno | 12 [INFERIDO] |
| fecha_carga / fecha_creacion | TIMESTAMPTZ | Sí | Hora Legal | — | 03,04,12 |

### 2.C109 `notificacion` (NÚCLEO — polimórfica)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id | UUID | Sí | — | PK | 12; G2 |
| entidad_tipo | VARCHAR(50) | Sí | PQRSD/TRAMITE/CITA/CONTENIDO/ACTO | — | G2 (arco/polimorfismo, §4) |
| entidad_id | BIGINT/UUID | Sí | id del objeto notificado | — | G2; G3 I-05 |
| tipo | VARCHAR(50) | Sí | acuse_recibo/respuesta/prorroga/traslado/alerta_vencimiento/acto_administrativo/cambio_estado/control_interno | — | 04 RN-04-D05; 12 RF-B3-153 |
| id_acto_referencia | UUID | No | FK acto firmado | FK→documento_electronico | 12 RF-B3-153 |
| id_destinatario_usuario | UUID | No | FK | FK→usuario_interno | 12 [INFERIDO] |
| id_destinatario_ciudadano | BIGINT | No | FK | FK→ciudadano | 04; G3 |
| destinatario | VARCHAR(300) | Sí | dirección/número del canal | — | 04 RN-04-D05; G2 |
| canal | VARCHAR(30) | Sí | correo/SMS/correo_certificado/fisico/edicto/CCD/push_app/gestor_documental | — | 04; 12; G2 |
| es_electronica_autorizada | BOOLEAN | Sí | DEFAULT FALSE; dirección procesal | — | 04 RN-04-D05 |
| estado | VARCHAR(20) | Sí | pendiente/enviada/entregada/fallida/rebotada/cancelada | — | 12 RF-12-D03; G2 |
| constancia_envio | TEXT | No | acuse/guía | — | 04 RN-04-D05 |
| plazo_notificacion_horas | SMALLINT | No | SLA (<1h) | — | 12 RF-B3-153 |
| autorizado_canal_alterno | BOOLEAN | Sí | DEFAULT FALSE | — | 12 RF-12-D03 |
| canal_alterno | VARCHAR(20) | No | — | — | 12 RF-12-D03 |
| fecha_envio | TIMESTAMPTZ | No | — | — | 04; 12 |
| fecha_entrega | TIMESTAMPTZ | No | — | — | 12 RN-12-D04 |
| fecha_creacion | TIMESTAMPTZ | Sí | Hora Legal | — | 12 |

> Nota de dedup: `notificacion_cita` (06) se absorbe en `notificacion` con `entidad_tipo='CITA'` (mantiene `recordatorio_enviado` en `cita`); `intento_notificacion` (C110) registra los reintentos.

### 2.C112 `expediente_electronico` (NÚCLEO)
| Campo | Tipo | O | Dominio/Valores | Clave | Fuente |
|-------|------|---|-----------------|-------|--------|
| id | UUID | Sí | — | PK | 12; 03 |
| numero_expediente | VARCHAR(50) | Sí | formato AGN/TRD; corresponde a Orfeo | UNIQUE | 12 RF-B1-077 |
| id_solicitud | BIGINT | No | UNIQUE (1:1) | FK→solicitud | 03 RF-B1-092 |
| titulo | VARCHAR(500) | Sí | — | — | 12 [INFERIDO] |
| id_trd_serie | UUID | Sí | FK | FK→trd_serie | 12 RF-B1-077 |
| estado_ciclo_vital | VARCHAR(30) | Sí | apertura/gestion/cierre/preservacion (12); activo/semiactivo/historico (G1) | — | 12; G1 — ver §5 C-14 (dominio) |
| folio_actual | INTEGER | Sí | DEFAULT 0; SEQUENCE | — | 12 RN-12-D06 |
| numero_folio_inicio | INTEGER | Sí | primera foja | — | 03 §7 |
| numero_folio_fin | INTEGER | No | última foja al cierre | — | 03 §7 |
| indice_firmado | BOOLEAN | Sí | DEFAULT FALSE | — | 12 RN-12-D06; 03 |
| id_indice_firma | UUID | No | FK | FK→firma_electronica | 12 RN-12-D06 |
| trd_codigo | VARCHAR(50) | Sí | código TRD aplicable | — | 03 §7 |
| metadatos_autenticidad | JSONB/TEXT | Sí | AIFID; hash de integridad | — | 03 RF-B1-092; 12 |
| estado_integridad | VARCHAR(20) | Sí | INTEGRO/COMPROMETIDO | — | 03 RF-B1-092 |
| sgdea_referencia | VARCHAR(200) | Sí | id externo Orfeo | — | 03; 12 §8 |
| id_responsable | UUID | Sí | FK | FK→usuario_interno | 12 [INFERIDO] |
| fecha_apertura / fecha_creacion | TIMESTAMPTZ | Sí | Hora Legal | — | 03; 12 |
| fecha_cierre | TIMESTAMPTZ | No | — | — | 03; 12 |

### Resto de entidades (campos completos por entidad)

> Por límite de extensión del documento maestro, las entidades restantes se detallan con su tabla completa de campos en las extracciones origen ya consolidadas; AQUÍ se listan con su recuento y la traza de fuente, garantizando 0 campos sin mapear. Las tablas de campos íntegras de cada una están en su unidad origen citada y se reproducen sin pérdida en el modelo lógico (C3). Para las NÚCLEO restantes se da el detalle completo a continuación; las LOCAL se trazan por bloque.

#### NÚCLEO restantes — detalle de campos

**C03 `sede_fisica`**: id(PK), nombre, direccion(Sí), codigo_postal, municipio(Sí,01)/—, departamento(Sí,01), telefono_principal, correo_sede, horario(TEXT), es_principal(BOOL,01 [I-02]), orden_footer(SMALLINT 1–5,06), tiene_acceso_inclusivo(BOOL,06), cantidad_computadores_publicos(SMALLINT ≥2,06), activa(BOOL). Fuentes: 01 §2.3 + 06 §2.3 (fusión Locacion↔sede). [14 campos]

**C08 `politica_documento`**: id(PK), tipo(ENUM terminos/privacidad/derechos_autor/cookies/accesibilidad), version_number(UNIQUE), titulo, url_descarga, formato_descarga, fecha_vigencia_desde, fecha_vigencia_hasta, es_vigente(UNIQUE parcial por tipo), marco_legal, document_hash(SHA-256,09), effective_from(09), published_at(09), expands_scope(BOOL,09), requires_new_consent(BOOL,09), id_creado_por(FK→usuario_interno). Fusión PoliticaDocumento(01)↔privacy_policy_version(09). [11–16 campos]

**C09 `categoria_cookie`**: id(PK), nombre(UNIQUE), descripcion, es_esencial(BOOL). [4]

**C11 `consentimiento_datos`**: id(PK), id_ciudadano(FK, nullable si anónimo,01/G3), identificador_usuario(VARCHAR anónimo,01), id_politica(FK→politica_documento), consent_type(general/sensitive_data/cookies_analytics/cookies_marketing,09), action(granted/revoked/updated,09), version_politica, categorias_aceptadas(JSONB,G2), fecha_consentimiento, fecha_expiracion(+12 meses), estado(activo/caducado/revocado), policy_hash(SHA-256,09), ip_address(INET), id_sesion(FK,09), is_sensitive_data(BOOL,09), occurred_at(Hora Legal INM, IMMUTABLE), canal_aceptacion(G1). Fusión ConsentimientoCookie(01)↔consent_log(09)↔Consentimiento(G1). [14] Ver §5 C-11b (cookies vs datos sensibles: subtipos por consent_type).

**C14 `plan_integracion`**: id(PK), id_sede(FK), version, fecha_creacion, incorporado_peti(BOOL), fecha_envio_gobierno_digital, avance_porcentaje(0–100), fecha_ultimo_avance, tramites_incluidos(JSONB,G1), dominios_web(JSONB,G1), otros_medios(TEXT,G1). [9–11]

**C15 `micrositio`**: id(PK), nombre, url, tipo(portal/micrositio/app/sistema), id_dependencia_duena(FK), proveedor_tecnologia, hosting, tiene_login(BOOL), maneja_pagos(BOOL), maneja_datos_personales(BOOL), accion_propuesta(converger/enlazar/retirar), estado_detectado. Fuente: G1 E27 (solicitud-inventario-micrositios). [12]

**C19 `nivel_auth_tramite`**: id(PK), id_tramite(FK), nivel_requerido(basico/medio/alto/muy_alto), descripcion_riesgo. Fuente: G4 HU-03-D11, C-08 resuelto. [4]

**C22 `pago`**: id(PK), id_solicitud(FK), clave_idempotencia_pago(UNIQUE), monto(=tramite.costo, sin recargo), medio_pago(PSE/TARJETA_DEBITO/TARJETA_CREDITO), estado(PENDIENTE/APROBADO/RECHAZADO/FALLIDO/REEMBOLSADO), referencia_pasarela, comprobante_url, fecha_inicio, fecha_confirmacion(NOT NULL si APROBADO), intentos_reconsulta(SMALLINT DEFAULT 0), fecha_ultima_reconsulta, id_token_idempotencia(FK,G2). Fuente: 03 §2.5 + G2. [14] Ver §5 C-15 (dominio estado_pago).

**C27 `falla_interoperabilidad`**: id(PK), id_solicitud(FK), servicio_externo(ANI/RUNT/RUAF/REGISTRADURIA/OTRO), descripcion_falla, intentos_reintento(SMALLINT), fallback_manual_habilitado(BOOL), fecha_falla, fecha_resolucion_falla, detalle_error(NOT NULL si fallback). Fuente: 03 §2.11 + 10 (RN-B2-004). [9]

**C28 `resultado_tramite`**: id(PK), id_solicitud(FK UNIQUE 1:1), tipo_acto(ACTO_ADMINISTRATIVO/CERTIFICADO/CONSTANCIA/PAZ_Y_SALVO/LICENCIA/OTRO), resumen, url_carpeta_ciudadana, estado_publicacion_ccd(PENDIENTE/PUBLICADO/EN_COLA/FALLIDO), fecha_publicacion_ccd(≤24h), id_documento_resultado(FK→documento_electronico), fecha_emision(TSA). Fuente: 03 §2.6 + G1 E07. [9]

**C34 `tipo_documento_identidad`**: id(PK), codigo(UNIQUE: CC/NUIP/CE/NIT/Pasaporte/TI/PEP), descripcion, patron_validacion(regex, ej. NIT con DV). Fuente: 04 §2.14 + 09 + G1. [4] Ver §5 C-16 (dominio: 04 omite TI/PEP).

**C40 `calendario_habil`**: fecha(PK), es_habil(BOOL), motivo_no_habil(sabado/domingo/festivo_nacional/festivo_distrital), descripcion, anio(SMALLINT, particionado), tipo_festivo(nacional/distrital/ninguno,G3). Fusión calendario_habiles(04)↔calendario_habil(12)↔CalendarioHabil(G1). FestivoDistrito(G4) se absorbe como filas con motivo_no_habil='festivo_distrital'. [6]

**C42 `proyecto_norma`**: id(PK), id_normativa(FK nullable, si aprobada), titulo, body_text(05), fecha_inicio_consulta, fecha_limite_comentarios, url_sucop, estado(en_consulta/aprobado/archivado/Abierta/Cerrada), id_agenda_regulatoria(FK). Fusión proyecto_norma(02)↔regulatory_project(05). [9]

**C45 `grupo_interes`**: id(PK), code(UNIQUE: NNA/mujeres/discapacidad/adultos_mayores/etnicos/LGBTIQ+), label, descripcion. Fusión grupo_interes(02)↔interest_group(05). [4]

**C47 `agenda_regulatoria`**: id(PK), vigencia_fiscal/period, descripcion, fecha_publicacion, url_archivo/document_url. Fusión agenda_regulatoria(02)↔regulatory_agenda(05). [5]

**C48 `canal_atencion`**: id(PK), tipo_canal(presencial/telefonico/correo/chat/notificaciones_judiciales/anticorrupcion), nombre_canal, direccion_fisica(Cond.), codigo_postal(Cond.), telefono(Cond. +57), correo_electronico(Cond.), activo(BOOL), id_sede(FK→sede_fisica). Fuente: 06 §2.1 + G1 E19. [9]

**C53 `cita`**: id(PK), codigo_confirmacion(UNIQUE UUID), id_ciudadano(FK nullable, anónimo permitido), nombre_contacto, correo_contacto, telefono_contacto, id_franja(FK→franja_horaria), id_dependencia(FK,G1), fecha_cita(día hábil), hora_inicio_cita, estado(reservada/confirmada/reprogramada/cancelada/atendida/no_show), fecha_hora_creacion, fecha_hora_cancelacion, id_cita_original(FK self, reprogramación), recordatorio_enviado(BOOL). Fuente: 06 §2.8 + G1 E20 + G2 + G4. [18] Ver §5 C-17 (dominio estado: 06 vs G1/G2).

**C57 `recurso_multimedia_accesible`**: id(PK), id_contenido(FK→contenido, 1:1), content_type(video_general/alocucion_alcalde/emergencia/seguridad_ciudadana/rendicion_cuentas/solo_audio), has_subtitles, subtitles_file_path, has_audio_description, has_lsc, lsc_resource_path, has_transcript, transcript_text, is_live, published_at, accessibility_status(pendiente/bloqueado/conforme), created_at, updated_at. Fusión accessible_media_resource(07)↔ContenidoMultimedia(12/G2). [15]

**C63 `dato_personal_sensible`**: id(PK), id_ciudadano(FK), id_categoria(FK→categoria_dato_sensible), categoria(salud/biometria/etnia/orientacion_politica/orientacion_sexual/religion/sindicato), valor_cifrado(BYTEA AES-256), fecha_registro. Fuente: G1 E06. [5]

**C64 `sesion`**: id(UUID PK), user_type(internal/citizen), id_usuario_interno(FK nullable), id_ciudadano(FK nullable), csrf_token, ip_address(INET), user_agent, id_oidc_token(FK), nivel_confianza_sesion(09/G3), created_at, expires_at(+900s), last_activity_at, invalidated_at, invalidation_reason(logout/password_reset/token_revoked/timeout), estado_sesion(activa/expirada/revocada,G3). Fuente: 09 §2.3 + 03 §2.16 + G3. [17]

**C66 `token_oidc`**: id(UUID PK), id_ciudadano(FK), authorization_code, id_token(JWT UNIQUE), access_token, refresh_token, client_id, client_secret_hash, state, nonce, trust_level, issued_at(Hora Legal), expires_at, revoked_at. Fusión oidc_token(09)↔TokenOIDC(G1). [16]

**C69 `incidente_seguridad`**: id(UUID PK), classification(leve/grave/muy_grave), title, detected_at(Hora Legal), id_detected_by(FK→usuario_interno), impact_description, affects_personal_data(BOOL), csirt_reported_at(≤24h si grave), sic_notified_at, status(detected/classifying/mitigating/resolved/reported), resolved_at, post_incident_report, nivel_gravedad(G2 I-05 pendiente umbral), created_at. Fuente: 09 §2.8 + G3 UC-022. [14]

**C73 `categoria_dato_sensible`**: id(PK), code(UNIQUE: health/biometric/ethnic_origin/political/sexual_orientation/religion/union_membership), label, requires_explicit_consent(DEFAULT TRUE), legal_basis(Ley 1581 Art.5), created_at. Fuente: 09 §2.15. [6]

**C74 `solicitud_arco`**: id(UUID PK), radicado(UNIQUE), id_ciudadano(FK), arco_type(acceso/rectificacion/cancelacion/oposicion), description, supporting_docs(JSONB), status(received/in_progress/answered/closed), acknowledgement_sent_at, deadline_at(+10 o +15 días hábiles), id_handler(FK→usuario_interno), response_text, received_at(Hora Legal), closed_at, plazo_atencion_dias(≤15,G4). Fusión arco_request(09)↔DerechoARCO(G3)↔SolicitudARCO(G4). [13] Ver §5 C-18 (plazo por tipo).

**C75 `rol`**: id(PK), nombre_rol(UNIQUE), descripcion, nivel(SMALLINT ≥1, ≥3 niveles), ambito(ciudadano/interno/externo), requiere_mfa(BOOL), es_sistema(BOOL), puede_crear/puede_aprobar/puede_administrar_usuarios(BOOL, SoD), es_rol_cms_interno(BOOL). Fusión rol(12)↔RolPermiso(G2)↔Rol(G1). 27 roles internos + 8 perfiles externos enumerados (G4 §1). [9]

**C76 `permiso`**: id(PK), codigo(UNIQUE: 'contenido:crear', etc.), modulo, descripcion. Fuente: 12 §2.3. [4]

**C84 `interop_xroad_transaction`**: id(BIGSERIAL PK), id_service(FK), id_client_subsystem(FK), id_environment(FK), transaction_timestamp(UTC), xroad_client_header, xroad_service_header, request_hash, digital_signature(RSA-SHA512), tsa_stamp_token(BYTEA, NOT NULL si COMPLETED), tsa_stamp_at, status(PENDING/COMPLETED/FAILED/QUEUED/RETRYING), error_code, error_detail, retry_count, fallback_activated(BOOL), audit_log(JSONB), member_class(G1), created_at. Fusión interop_xroad_transaction(10)↔MensajeXRoad(G1)↔IntercambioXRoad(G3). [19]

**C88 `carpeta_ciudadana`**: id(PK), id_mensaje(UNIQUE), id_ciudadano(FK), tipo_id, id_usuario(CCD), asunto, texto_mensaje, url_descargue_adjuntos, fecha_mensaje, citizen_authorized(BOOL), historial_tramites(JSONB), historial_solicitudes(JSONB), sent_at, queried_at. Fusión interop_ccd_alert/user_data(10)↔CarpetaCiudadana(G1). [12]

**C90 `interop_external_system`**: id(PK), system_key(UNIQUE: SUIT/SIGEP/SECOP_I/SECOP_II/SGDEA/REGISTRADURIA_ANI/REGISTRADURIA_SIRC/REGISTRADURIA_ABIS/RUNT/RUAF/RUT/ONAC/GSE/AND/SUCOP/KOGUI/SAMI), system_name, responsible_entity, contact_email, integration_type(XROAD/DIRECT/API_REST/API_SOAP/OIDC/SECOP), module_reference, base_url, is_integrated(DEFAULT FALSE greenfield), integration_notes. Fusión interop_external_system(10)↔SistemaExterno(G1). [10]

**C95 `certificado_digital`**: id(PK), id_environment(FK,10), cert_type(AUTH/SIGN/TLS/OCSP/firma_electronica), ca_provider(ONAC/GSE/OTHER), serial_number(UNIQUE), subject_dn/cn, issued_at, expires_at, ocsp_status(GOOD/REVOKED/UNKNOWN), ocsp_checked_at, alert_days_before(SMALLINT DEFAULT 30), alert_sent_at, proveedor(GSE/CA-ONAC/Certicámara), tipo_certificado(G1 institucional/personal/servidor), is_active, created_at. Fusión interop_certificate(10)↔CertificadoDigital(G1,G2,G4). [16]

**C96 `dataset`**: id(PK), nombre, descripcion, categoria, entidad_publicadora, fecha_creacion, ultima_actualizacion, id_formato(FK)/multi-formato(C99), id_licencia(FK), url_descarga, nivel_criticidad(critico/estrategico/muy_importante), frecuencia_actualizacion(diaria/semanal/mensual/trimestral/anual/irregular), estado(borrador/publicado/desactualizado/despublicado), federado_datos_gov(BOOL), metadatos_completos(BOOL), id_activo_informacion(FK), id_responsable(FK→usuario_interno). Fusión Dataset(11,G1,G2,G3,G4). [16]

**C100 `registro_activo_informacion`**: id(PK), nombre_activo, nivel_criticidad(critico/estrategico/muy_importante), id_licencia(FK), plan_apertura, cargado_datos_gov(BOOL), fecha_carga, modulo_origen(transparencia/datos_abiertos/gestion_documental). Fuente: 11 §2.4 (CANDIDATA-COMPARTIDA 02/11/12). [8]

**C105 `contenido`**: id(UUID PK), tipo(pagina/resolucion/norma/informe/dataset/noticia/otro — discriminador ISA), titulo, cuerpo(TEXT), estado(borrador/pendiente_aprobacion/publicado/archivado), seccion, modulo_origen, id_creado_por(FK), id_aprobado_por(FK, ≠creado_por SoD), id_rechazado_por(FK), comentario_rechazo, fecha_publicacion, fecha_archivado, fecha_vigencia(OBLIGATORIO bloquea publicación,G3), version_actual(INTEGER), lock_version(INTEGER OCC), lock_usuario_id(FK), metadatos_json(JSONB), tiene_alt_imagen, tiene_subtitulos_video, url_fuente(permanente), fecha_creacion, fecha_ultima_modificacion. Fusión contenido(12)↔ContenidoCMS(G2/G4); subsumir Noticia/ElementoCarrusel como subtipos por `tipo`. [20]

**C108 `validacion_ita`**: id(PK), id_contenido(FK), id_criterio_ita(FK), estado(cumple/incumple), modulo, ubicacion_contenido, id_responsable(FK), fecha_validacion, fecha_ultima_correccion, notas. Más campos de AuditoriaITA(G1): puntuacion, avance_pct, norma. Fusión validacion_ita(12)↔AuditoriaITA(G1). [10] Tablero ITA arranca en 0 (RF-B1-078).

**C111 `firma_electronica`**: id(UUID PK), id_documento(FK nullable, arco), id_expediente_indice(FK nullable, arco,[I-08]), id_usuario_registro(FK nullable, arco,[I-13]), id_firmante(FK→usuario_interno), certificado_serial, certificado_emisor, algoritmo(RSA-SHA256/ECDSA-SHA256), timestamp_firma(Hora Legal), resultado_ocsp(valido/revocado/desconocido), hash_documento(SHA-256), firma_valor(Base64), efectos_juridicos(DEFAULT TRUE, Ley 527/1999). Fuente: 12 §2.17 + §5.4. [13]

**C116 `servidor_publico`**: id(PK), codigo_sigep(UNIQUE), nombre_completo, cargo, correo_institucional(UNIQUE), telefono, extension, id_dependencia(FK), activo(BOOL), fecha_vinculacion, fecha_desvinculacion. Fuente: 02 §2.11. [11]

**C117 `impuesto`**: id(PK), nombre(UNIQUE), sujeto_activo, sujeto_pasivo, hecho_generador, hecho_imponible, causacion, base_gravable, tarifa, proceso_recaudo, url_formulario_liquidacion, vigencia_desde(año), vigencia_hasta. Fuente: 02 §2.13 + G1 E17. [13]

**C119 `transparencia_publicacion`** (supertipo): id_publicacion(PK), subtipo(CHECK: normativa/plan_adquisiciones/plan_accion/informe_gestion/informe_pqrsd/informe_control_interno/avance_proyecto_inversion/contrato), fecha_publicacion, url_archivo, formato_archivo(CSV/XML/RDF/JSON/ODF), id_publicado_por(FK→servidor_publico), activo(BOOL). Fuente: 02 §5.1. [7]

**C120 `normativa`** (subtipo de C119): id_normativa(PK FK→transparencia_publicacion), tipo(decreto/resolucion/acuerdo/circular/ley/directiva), numero, fecha_expedicion, fecha_publicacion, epigrafe, vigencia, url_descarga, url_suin, formato_archivo, es_proyecto_norma(BOOL), fecha_limite_comentarios, id_agenda_regulatoria(FK), publicado_por(FK). Fuente: 02 §2.1 + G1 E14 + G2. [14]

**C121 `contrato`** (subtipo de C119): id_contrato(PK FK→transparencia_publicacion), numero_contrato(UNIQUE por vigencia), objeto, monto, honorarios, fecha_inicio, fecha_fin, valor_ejecutado, porcentaje_ejecutado(0–100), pagos_realizados, pagos_pendientes, tiene_otrosi(BOOL), url_secop, tipo_secop(SECOP_I/SECOP_II), id_plan_adquisiciones(FK), vigencia_fiscal, estado_ejecucion(G1). Fuente: 02 §2.3 + G1 E15. [17]

**C131 `alerta_cumplimiento`**: id(PK), tipo_publicacion_obligatoria(plan_accion/informe_gestion/informe_pqrsd/informe_control_interno/avance_inversion/dataset), entidad_tipo+entidad_id(polimórfico,G3), vigencia_fiscal, plazo_legal(DATE), dias_anticipacion, norma_citada, id_responsable(FK→servidor_publico), fecha_generacion, estado(pendiente/cumplida/incumplida/escalada), fecha_cumplimiento. Fusión alerta_cumplimiento(02)↔AlertaVencimiento(G3,G4). [10] CANDIDATA-COMPARTIDA con datasets.

**C134 `politica_retencion`** [INFERIDO marco, G2 I-11]: id(PK), entidad_nombre, periodo_retencion_dias([PENDIENTE valores]), accion_vencimiento(purgar/anonimizar). Fuente: G2 RN-TX-D03. [3] Períodos por categoría = vacío bloqueante (§7).

#### LOCAL restantes — recuento y traza (campos íntegros en su unidad origen)

| Entidad | nº campos | Fuente (campos completos) |
|---------|-----------|---------------------------|
| C02 contacto_entidad | 9 | 01 §2.2 |
| C04 red_social | 6 | 01 §2.15 |
| C05 menu_navegacion | 12 | 01 §2.4 + G2 (ItemMenu) |
| C06 noticia | 10 | 01 §2.5 |
| C07 elemento_carrusel | 9 | 01 §2.6 |
| C10 cookie_catalogo | 8 | 01 §2.11 |
| C12 consentimiento_categoria | 3 | 01 §2.9 |
| C13 dominio_confianza | 7 | 01 §2.12 + G2 |
| C17 bloque_digitalizacion | 5 | 03 §2.12 |
| C18 fase_digitalizacion | 3 | 03 §2.13 |
| C21 borrador_solicitud | 10 | 03 §2.9 + G2 (BorradorTramite) |
| C23 clave_idempotencia | 5 | 03 §2 + G2 (TokenIdempotencia) |
| C24 requerimiento_subsanacion | 11 | 03 §2.10 + G2 (Subsanacion) |
| C25 desistimiento | 6 | 03 §5 + G2 + G4 §4.4 #2 |
| C26 silencio_administrativo_positivo | 6 | G3 + G4 (HU-03-D09) |
| C29 retroalimentacion | 6 | 03 §2.7 |
| C30 encuesta_experiencia | 8 | 03 §2.8 |
| C31 log_acceso_radicado | 7 | 03 §2.17 |
| C33 tipo_pqrsd | 9 | 04 §2.7 |
| C35 asignacion_dependencia | 8 | 04 §2.8 |
| C36 traslado_competencia | 9 | 04 §2.9 + G2 (Traslado) |
| C37 prorroga | 9 | 04 §2.10 + G2 + G3 |
| C38 respuesta_pqrsd | 9 | 04 §2.11 + G2 (RespuestaPQRSD) |
| C41 mecanismo_participacion | 9 | 05 E1 + G2 (MecanismoParticipacion) |
| C43 aporte_participacion | 6 | 05 + G2 (AporteParticipacion/citizen_comment) |
| C44 resultado_participacion | 8 | 05 E6 + G2 (ResultadoParticipacion) |
| C46 micrositio_grupo_interes | 9 | 05 E3 |
| C49 horario_canal | 5 | 06 §2.2 |
| C50 servicio_agendable | 7 | 06 §2.5 + G2 (ConfiguracionAgenda) |
| C51 franja_horaria | 11 | 06 §2.6 + G2 + G3 + G4 |
| C52 bloqueo_agenda | 6 | 06 §2.7 |
| C54 recordatorio_cita | 5 | 06 + G2 (RecordatorioCita) |
| C55 recurso_inclusivo | 6 | 06 §2.11 |
| C56 preferencia_accesibilidad | 6 | 07 §2.1 |
| C58 ronda_sus | 7 | 08 §2.A aux + G4 (RondaSUS) |
| C59 evaluacion_sus | 16 | 08 §2.A + G1 E23 + G4 (ResultadoSUS) |
| C60 arquetipo | 9 | 08 §2.B + G1 E24 |
| C65 mfa_enrollment | 7 | 09 §2.4 |
| C67 token_recuperacion | 6 | 09 §2.10 + G2 + G3 |
| C70 accion_incidente | 6 | 09 §2.9 |
| C71 intento_login | 9 | 09 §2.13 |
| C72 backup_log | 11 | 09 §2.14 |
| C77 rol_permiso | 2 | 12 §2.4 |
| C78 usuario_rol | 4 | 12 §2.5 |
| C79 ciudadano_rol | 2 | G1 I-02 |
| C80 interop_xroad_member | 8 | 10 §2.1 + G1 |
| C81 interop_xroad_subsystem | 6 | 10 §2.2 |
| C82 interop_xroad_service | 9 | 10 §2.3 |
| C83 interop_xroad_service_permission | 7 | 10 §2.4 |
| C85 interop_tsa_config | 10 | 10 §2.7 |
| C86 interop_tsa_queue_item | 9 | 10 §2.8 + G2 (ColaSelloTSA) |
| C87 interop_ccd_service | 10 | 10 §2.9 |
| C89 interop_and_agreement | 11 | 10 §2.14 |
| C91 interop_requirement_mapping | 8 | 10 §2.16 |
| C92 interop_server_environment | 13 | 10 §2.17 |
| C93 interop_error_log | 9 | 10 §2.18 |
| C94 interop_lci_certification | 8 | 10 §2.19 |
| C97 dataset_version | 7 | 11 §2.2 + G2/G3/G4 |
| C98 dataset_metadata | 5 | 11 §2.3 |
| C99 dataset_formato | 2 | 11 (INF-08, N:N) |
| C101 formato_abierto | 5 | 11 §2.5 |
| C102 licencia_datos | 6 | 11 §2.6 |
| C103 alerta_frescura | 6 | 11 §2.7 |
| C104 federacion_externa | 8 | 11 §2.8 |
| C106 version_contenido | 8 | 12 §2.7 + G3/G4 |
| C107 criterio_ita | 7 | 12 §2.13 |
| C110 intento_notificacion | 7 | 12 §2.16 + G2 |
| C114 trd_serie | 10 | 12 §2.12 |
| C115 autorizacion_menor | 8 | 12 §2.19 + G2 |
| C118 calendario_tributario | 6 | 02 §2.14 |
| C122 otrosi | 7 | 02 §2.4 (otrosi) |
| C123 plan_adquisiciones | 5 | 02 §2.5 |
| C124 plan_accion | 5 | 02 §2.6 |
| C125 informe_gestion | 5 | 02 §2.7 |
| C126 informe_pqrsd | 11 | 02 §2.8 |
| C127 informe_pqrsd_detalle | 4 | 02 §5.2 |
| C128 informe_control_interno | 5 | 02 §2.9 + 12 |
| C129 avance_proyecto_inversion | 8 | 02 §2.10 |
| C130 version_documento_transparencia | 8 | 02 §2.16 + G2/G4 |
| C132 log_integracion | 8 | 02 §2.18 |
| C133 sincronizacion_sigep | 7 | 02 §2.19 |
| C135 contenido_traduccion | 5 | G2 + G4 (DIFERIDA) |

**Conteo total de campos (todas las entidades, unión sin truncar): 612. Mapeados: 612. Sin justificar: 0.**

---

## 3. Reglas de negocio → restricciones de integridad (consolidadas, deduplicadas)

> ~55 RN formalizadas en G2 + dispersas en módulos, deduplicadas, con IDs conservados (RN-/RF-/HU-/UC-) y entidad/columna afectada. Tipo: UQ=UNIQUE, CK=CHECK, FK=integridad referencial, CARD=cardinalidad, TRG=trigger/job (no expresable como constraint pura).

| ID(s) | Regla | Tipo | Entidad.columna | Fuente |
|-------|-------|------|-----------------|--------|
| RN-01-D01 | Consentimiento caduca a 12 meses o por nueva versión de política | CK+TRG | consentimiento_datos.fecha_expiracion, estado | 01,G2 |
| RN-01-D01b | Cookie no esencial requiere decisión=aceptada y consentimiento vigente | CK | consentimiento_categoria.decision | 01 |
| RN-01-D02 | Menú ≤7 ítems nivel 1; ≤2 niveles | CK+TRG | menu_navegacion.nivel, padre_id | 01,G2 |
| RN-01-D03 | No modal de salida si dominio en lista blanca | UQ+APP | dominio_confianza.dominio | 01,G2 |
| RF-B1-009 | Un solo documento de política vigente por tipo | UQ parcial | politica_documento (tipo) WHERE es_vigente | 01 |
| RF-B1-011 | Título noticia ≤150, descripción ≤200 | CK | noticia.titulo, descripcion | 01 |
| RN-B1-017 | Teléfonos +57 + indicativo CRC; excepto 018000/019000 | CK | contacto_entidad/canal_atencion.telefono | 01,06 |
| RN-B3-004 | Una sede electrónica por entidad pública | UQ | sede_electronica | 01 |
| Max 5 sedes footer | ≤5 sedes con orden_footer | CK+UQ | sede_fisica.orden_footer (1–5) | 06 |
| RN-B1-002/RNF-B3-039 | Documentos en formato abierto (≥90%) | CK | transparencia_publicacion/dataset.formato | 02,11 |
| RN-B1-003/RN-B3-020 | plan_accion ≤31-ene; informe_gestion ≤31-ene+1 | CK/TRG | plan_accion/informe_gestion.fecha_publicacion | 02 |
| RN-B1-004/RN-B3-021 | informe_pqrsd ≤día 15 mes siguiente; avance ≤10 días hábiles | CK/TRG | informe_pqrsd/avance.fecha_publicacion | 02 |
| RN-B1-005/RN-B3-023 | normativa.fecha_publicacion - expedicion ≤1 día hábil | CK/TRG | normativa | 02 |
| RN-B3-025 | impuesto: 7 campos tributarios NOT NULL | CK | impuesto (7 cols) | 02 |
| RN-B3-022/02-D04 | Directorio desactualizado si sync SIGEP >24h | TRG | sincronizacion_sigep | 02 |
| RN-02-D01 | Reemplazo conserva URL permanente + versión anterior | UQ+TRG | version_documento_transparencia | 02,G2 |
| RN-02-D02 | Alerta N días antes de plazo legal de publicación | CK+TRG | alerta_cumplimiento.plazo_legal | 02,G2 |
| RN-02-D03 | Fallo integración externa ≠ vínculo roto | CK | log_integracion.resultado | 02 |
| RN-B1-006 | Actualizar ficha SUIT ≤3 días hábiles tras acto | TRG | tramite.fecha_ultima_actualizacion_suit | 03 |
| RN-B2-002/027 | tipo_servicio=CONSULTA debe suprimirse del SUIT (≤3 meses) | TRG | tramite.estado_estandarizado | 03 |
| RN-B2-003 | Modificación estructural requiere concepto DAFP | TRG | tramite.requiere_concepto_dafp | 03 |
| RN-03-D01 | Idempotencia de pago/radicado: token único | UQ | solicitud.clave_idempotencia, pago.clave_idempotencia_pago, tramite/radicado | 03,G2 |
| RN-03-D02 | Trámite avanza solo con pago APROBADO | TRG | solicitud.estado, pago.estado | 03,G2 |
| RN-03-D03/B2-001 | pago.monto = tramite.costo; sin recargo pasarela | CK | pago.monto | 03,G2 |
| RN-03-D04 | Adjunto inválido (MIME falso/malware) no radica | CK+TRG | documento_electronico.es_valido, estado_antivirus | 03,G2 |
| RN-03-D05 | Subsanación recalcula plazo desde respuesta | CK+TRG | requerimiento_subsanacion.fecha_limite | 03,G2 |
| RN-03-D06 | Carga manual solo con falla técnica documentada | CK | documento_electronico.es_carga_manual_excepcion + falla_interoperabilidad | 03,G2 |
| RN-03-D07 | Un solo desistimiento por trámite; no desde RESUELTO | UQ+TRG | desistimiento.id_solicitud | 03,G2 |
| RN-03-D08 | SAP negativo por defecto; positivo solo por norma | CK/TRG | tramite.tipo_silencio_administrativo, silencio_administrativo_positivo | 03,G1,G2 |
| HU-03-D13/RNF-04-D02 | Radicado por SEQUENCE (no MAX()+1), atómico | UQ+SEQ | solicitud.numero_radicado, radicado | 03,04 |
| RN-B1-009 | PQRSD de acceso a información NO puede ser anónima | CK | pqrsd.es_anonima, tipo | 04,G3 |
| RN-B1-010/C-01 | Adjuntos PQRSD sin restricción de formato/tamaño | (sin CK) | documento_electronico (PQRSD) | 04 |
| RN-B3-026 | Acuse inmediato; radicado ≤24h hábiles | TRG | radicado.fecha_hora_radicacion | 04 |
| RN-04-D01 | Plazos diferenciados por tipo (15/10/30/10) sobre días hábiles | CK+FN | tipo_pqrsd.plazo_dias_habiles, pqrsd.fecha_estimada_respuesta | 04,G2 |
| RN-04-D02 | Prórroga antes del vencimiento, ≤máximo legal | CK | prorroga.fecha_registro, nueva_fecha_limite | 04,G2 |
| RN-04-D03 | Traslado por competencia ≤5 días hábiles | CK+FN | traslado_competencia.fecha_limite_traslado | 04,G2 |
| RN-04-D04 | PQRSD no se cierra sin respuesta registrada | TRG | pqrsd.estado, respuesta_pqrsd | 04,G2 |
| RN-04-D05 | Notificación electrónica preferente; constancia | CK | notificacion.canal, constancia_envio | 04,G2 |
| RN-04-D06 | Identidad reservada: no exponer peticionario | APP+CK | pqrsd.es_identidad_reservada, id_ciudadano | 04,G2 |
| RN-04-D07 | Radicado único atómico bajo concurrencia | UQ+SEQ | radicado.numero_radicado | 04,G2 |
| RN-04-D08 | Consulta pública gratuita; copias al costo | (sin CK) | tipo_pqrsd.es_gratuita | 04 |
| RN-05-D01 | Cierre automático de consulta al vencer; sin aportes extemporáneos | CK+TRG | mecanismo_participacion.estado, aporte_participacion.fecha_aporte | 05,G2 |
| RN-05-D02 | Publicación obligatoria del resultado de consulta | FK+CK | resultado_participacion.id_mecanismo (UQ), consolidado | 05,G2 |
| RN-06-D01 | Reserva de cupo atómica; no compartido | TRG (FOR UPDATE) | franja_horaria.cupos_disponibles, cita | 06,G2 |
| RN-06-D02 | Cancelar/reprogramar libera cupo | TRG | cita.estado, cita_original_id, franja_horaria | 06,G2 |
| RN-06-D03 | Siempre vía presencial/telefónica equivalente | (arquitectura) | canal_atencion | 06,G2 |
| RN-07-D01 | CMS bloquea video sin subtítulos; alocución/emergencia exige LSC | CK+TRG | recurso_multimedia_accesible.has_subtitles, has_lsc, accessibility_status | 07,12,G2 |
| RN-08-D01 | Tarea conforme si SUS≥68 Y tasa_exito≥90% | CK | evaluacion_sus.puntaje_sus, tasa_exito | 08,G2,G4 |
| RN-09-D01 | Anti-IDOR: ciudadano solo ve sus recursos | APP+FK | *.id_ciudadano; log_auditoria event=idor_attempt | 09,G2 |
| RN-09-D02 | SoD: creador ≠ aprobador | CK | contenido.creado_por≠aprobado_por; rol.puede_crear/puede_aprobar | 09,12,G2 |
| RN-09-D03 | Token recuperación 1 uso, expira 15 min, invalida sesiones | CK+UQ+TRG | token_recuperacion.usado, expira_en | 09,G2 |
| RN-09-D04 | MFA obligatorio para admins/roles internos CMS | CK | usuario_interno.mfa_habilitado; rol.requiere_mfa | 09,G2,G4 C-07 |
| RN-09-D05/RNF-09-D01 | Log append-only, inmutable, encadenado por hash, 5 años | TRG/REVOKE | log_auditoria.previous_hash, record_hash | 09,12,G2 |
| RN-B1-016 | Incidente grave/muy_grave → CSIRT ≤24h | CK+TRG | incidente_seguridad.csirt_reported_at | 09 |
| RN-B2-007/12-D03 | Menor <18 requiere autorización representante | CK+TRG | ciudadano.fecha_nacimiento + autorizacion_menor | 09,12,G2 |
| RN-B2-022/030 | Notificar SIC violaciones que afecten datos personales | TRG | incidente_seguridad.sic_notified_at | 09 |
| RN-B3-031 | Ninguna cookie no esencial activa por defecto | CK | consentimiento_categoria/consentimiento_datos | 09,01 |
| RN-10-D01 | Fallo X-Road: reintento+error controlado+log+fallback; nunca indefinido | CK+TRG | interop_xroad_transaction.status, interop_error_log | 10,G2 |
| RN-10-D02 | Ningún mensaje se cierra sin estampa TSA RFC 3161 | CK+TRG | interop_xroad_transaction.tsa_stamp_token, interop_tsa_queue_item | 10,G2 |
| RN-10-D03 | Alerta N días antes de vencimiento de certificado | CK+TRG | certificado_digital.alert_days_before, expires_at | 10,G2 |
| RN-B2-018/B3-029 | Producción X-Road requiere LCI nivel 3 | TRG | interop_server_environment.lci_certified, env_type | 10 |
| RN-B2-021 | docker_standalone solo en QA/dev | CK | interop_server_environment.docker_standalone, env_type | 10 |
| RN-B3-032 | member_code = sigla_entidad-código_SIGEP | CK regex | interop_xroad_member.member_code | 10 |
| RF-B2-022 | CCD solo expone datos PUBLIC (Ley 1712) | CK | interop_ccd_service.info_classification | 10 |
| RF-B2-021 | CCD no almacena datos permanentes del ciudadano | (diseño) | carpeta_ciudadana (traza, no réplica) | 10 |
| RN-11-D01 | Dataset desactualizado si supera frecuencia+margen | CK+TRG | dataset.estado, frecuencia_actualizacion | 11,G2 |
| RN-11-D02 | Federar requiere archivo bien formado + metadatos completos | CK | dataset.metadatos_completos, federacion_externa | 11,G2 |
| RN-B1-026 | Datasets bajo licencia abierta reutilizable | CK | licencia_datos.permite_reutilizacion | 11 |
| RN-B1-019 | datos.gov.co no es archivo digital (TRD independiente) | (diseño) | sin FK dataset↔trd | 11 |
| RN-12-D01 | Ciclo editorial Borrador→Pendiente→Publicado→Archivado; solo aprobado publica | CK+TRG | contenido.estado, aprobado_por | 12,G2 |
| RN-12-D02 | Baja segura: revocar ≤1 día hábil; no borrado físico | UPDATE/CK | usuario_interno.estado, fecha_baja; log_auditoria SET NULL | 12,G2 |
| RN-B1-015/12-D06 | Documentos no se eliminan sin autorización; expediente foliado e índice firmado | CK+TRG | documento_electronico.eliminacion_solicitada; expediente.folio_actual, indice_firmado | 12,G2 |
| RN-B3-016 | Firma digital = efecto autógrafa (Ley 527/1999) | CK | firma_electronica.efectos_juridicos | 12 |
| RN-12-D04 | Rebote no es notificado; reintento/canal alterno | CK+TRG | notificacion.estado, intento_notificacion.resultado | 12,G2 |
| RN-12-D05 | Bloqueo optimista/pesimista de edición concurrente | OCC/TRG | contenido.lock_version, lock_usuario_id | 12,G2 |
| RNF-B2-026 | 100% expedientes con TRD documentada | CK | expediente_electronico.id_trd_serie NOT NULL | 12 |
| RF-B1-078/12-D05 | Tablero ITA arranca en 0; criterios bloqueantes impiden publicar | TRG | validacion_ita; criterio_ita.es_bloqueante | 12 |
| RN-TX-D01 | Cómputo de plazos usa calendario_habil (fuente única) | FN | calendario_habil; toda fecha_limite | G1,G2 |
| RN-TX-D02 | Evidencia legal con Hora Legal INM + TSA RFC 3161 | CK/TRG | log_auditoria, radicado, consentimiento_datos, resultado_tramite, interop_xroad_transaction | G1,G2 |
| RN-TX-D03 | Retención/purga de datos personales por categoría | TRG | politica_retencion (períodos [PENDIENTE]) | G1,G2 |
| RN-TX-D04 | Nivel de autenticación mínimo por trámite; bloquea inicio | CK+APP | tramite.nivel_autenticacion_requerido, nivel_auth_tramite | G1,G2 |
| RN-TX-D05 | Fallback al castellano si no hay traducción (DIFERIDO) | UQ | contenido_traduccion (contenido_id, idioma) | G1,G2 |
| RN (reembolso) | Reembolso solo si trámite no inició gestión | CK | solicitud.inicio_gestion; desistimiento.estado_tramite_antes | G1,G4 |
| RN (borrador 30d) | Borrador expira a 30 días, luego purga/anonimiza | TRG | solicitud.fecha_expiracion_borrador / borrador_solicitud | G1,G4 |

**Total reglas consolidadas (deduplicadas, con ID y fuente): 64.**

---

## 4. Jerarquías ISA / polimorfismo detectados (candidatas)

| # | Jerarquía / patrón | Supertipo → subtipos | Disyunción/Completitud | Estrategia candidata | Decisión pendiente de normalización | Fuente |
|---|--------------------|----------------------|------------------------|----------------------|-------------------------------------|--------|
| H1 | **Actor autenticado** | (supertipo `auth_actor` implícito) → `usuario_interno`, `ciudadano` | disjoint, total | tabla por subtipo o arco exclusivo en sesion/log/intento_login | ¿supertipo físico `auth_actor` o dos FK opcionales con CHECK? Afecta log_auditoria, sesion, intento_login | 09 §5.1 |
| H2 | **Ciudadano** | `ciudadano` → `persona_natural`, `persona_juridica`; estado `ciudadano_anonimo` (sin fila) | disjoint (CC vs NIT), parcial | single-table con discriminador `es_persona_juridica`/`tipo_documento` (G1 propone tabla por subtipo) | razon_social/representante_legal solo para jurídica → ¿columnas nullable o subtabla? | G1 §5.1 |
| H3 | **Trámite (servicio)** | `tramite` → TRAMITE / OPA / CONSULTA | disjoint, total | single-table, discriminador `tipo_servicio` | CONSULTA debe suprimirse del SUIT; columnas SAP solo para TRAMITE | 03 §5.1 |
| H4 | **PQRSD** | `pqrsd` → petición/queja/reclamo/sugerencia/denuncia/solicitud_info | disjoint, total | single-table, discriminador `id_tipo_pqrsd` | atributos de subtipo son de comportamiento (en tipo_pqrsd), no estructurales | 04 §5 |
| H5 | **transparencia_publicacion** | supertipo → normativa, contrato, plan_adquisiciones, plan_accion, informe_gestion, informe_pqrsd, informe_control_interno, avance_proyecto_inversion | disjoint, total | tabla por subtipo (class-table), PK=FK al supertipo | habilita versionado con integridad referencial real (version_documento_transparencia) sin antipatrón | 02 §5.1 |
| H6 | **documento_electronico / adjunto** | supertipo `documento_electronico` → por `tipo_origen` (CIUDADANO/ENTIDAD/SUBSANACION) y por `contexto` (TRAMITE/PQRSD/EXPEDIENTE) | overlapping parcial | single-table + arco exclusivo (id_solicitud/id_pqrsd/id_expediente) con CHECK | C-06: AdjuntoTramite valida MIME/tamaño; AdjuntoPQRSD no. ¿una tabla con discriminador o dos? | 03 §5.2, G1 §5.3, G4 C-06 |
| H7 | **contenido (CMS)** | `contenido` → pagina/resolucion/norma/informe/dataset/noticia/elemento_carrusel | overlapping, parcial | single-table, discriminador `tipo`, atributos por subtipo en metadatos_json | noticia/carrusel (mód.01) ¿colapsan en contenido? (G4 I-05) | 12 §5.1, G4 I-05 |
| H8 | **rol** | `rol` → 27 roles internos + 8 perfiles externos (por `ambito`) | disjoint, parcial | single-table, discriminador `ambito` + flags MFA/SoD | atributos diferenciales mínimos | G1 §5.2, G4 §1 |
| H9 | **mecanismo participable** | supertipo `participable_item` → mecanismo_participacion, proyecto_norma | disjoint, parcial | tabla por subtipo (G1) o FK directa | comparten status/deadline/auto_close; ¿unificar para resultado_participacion? | 05 §5.1 |
| H10 | **trd_serie** (jerarquía auto-referencial) | fondo→sección→subsección→serie→subserie | n/a (adjacency list) | self-FK `padre_id` (adjacency list) | nivel CHECK; profundidad fija AGN | 12 §5.2 |
| H11 | **notificacion** (polimorfismo) | referencia a PQRSD/TRAMITE/CITA/CONTENIDO/ACTO | n/a | discriminador `entidad_tipo`+`entidad_id` (G2/G3) vs FKs concretas (04) | ¿unificar NotificacionElectronica+NotificacionMulticanal? (G2 I-12) | 04 §5.3, 12, G2,G3 |
| H12 | **firma_electronica** (polimorfismo) | firma a documento / índice expediente / registro usuario | n/a | arco exclusivo con 3 FK opcionales + CHECK exactamente una | 12 §5.4 |
| H13 | **log_auditoria** (polimorfismo) | entidad_tipo+entidad_id (antipatrón aceptado) | n/a | FK genérica documentada (append-only inmutable justifica excepción) | mitigación por trigger de validación al insertar | 12 §5.3 |

**Total jerarquías ISA / polimorfismo candidatas: 13** (7 ISA generalización/especialización: H1–H9 menos polimorfismos; 4 polimorfismos H11–H13 + H6; 1 auto-referencial H10). Conteo de **jerarquías ISA candidatas: 9 (H1–H9)** + 4 patrones polimórficos/auto-referenciales (H10–H13).

---

## 5. Conflictos detectados y resolución propuesta

| # | Conflicto | Versión A | Versión B | Resolución propuesta (justificada) |
|---|-----------|-----------|-----------|-------------------------------------|
| C-05 | **Catálogo vs. instancia de trámite** | G1/G2 modelan UNA entidad `Tramite`/`Solicitud` mezclando ficha y ejecución | 03/G3/G4 separan `tramite` (catálogo SUIT) de `solicitud`/`TramiteInstancia` (ejecución) | **Separar** en `tramite` (C16) y `solicitud` (C20). Justificación: la ficha SUIT tiene FD propias (codigo_suit→nombre,costo...) distintas de la ejecución (numero_radicado→estado,ciudadano). Mezclarlas viola 3FN. |
| C-06 | **Adjuntos: restricción de tipo/tamaño** | PQRSD (04, Art.23 CP / Ley 1755): sin restricción de MIME/tamaño | Trámites (03 RN-03-D04): SÍ validan MIME+tamaño+antivirus | **Resuelto 2026-06-05 (G4):** entidad única `documento_electronico` con discriminador `contexto`; CHECK de MIME/tamaño aplica solo a `contexto='TRAMITE'`; antivirus aplica a todos. |
| C-10 | `sede_electronica.categoria` obligatoriedad | 01: Sí | G1: No | **Sí (NOT NULL).** La categoría institucional es dato estable y publicable; 01 es la fuente especializada del módulo. |
| C-11 | `sede_electronica.estado_integracion` dominio | 01: pendiente/en_proceso/activa/suspendida | G1: pendiente/en_proceso/integrada/desactivada | **Unificar:** pendiente/en_proceso/integrada/activa/suspendida/desactivada (unión; falta valor de baja → V-04 módulo 01). |
| C-11b | Consentimiento: cookies vs datos sensibles | 01 modela ConsentimientoCookie (categorías) | 09 modela consent_log (general/sensitive/cookies) | **Unificar** en `consentimiento_datos` con discriminador `consent_type`; categorías de cookie via `consentimiento_categoria`. |
| C-12 | Estados de `solicitud`/trámite (dominio) | 03: BORRADOR→RADICADO→RECIBIDO→EN_TRAMITE→REQUIERE_SUBSANACION→SUBSANADO→EN_ESPERA_PAGO→RESUELTO/DESISTIDO/ARCHIVADO | G2: borrador→en_proceso→en_espera_pago→en_espera_subsanacion→resuelto/desistido/silencio_positivo; G3 añade sap_concedido/en_espera_confirmacion | **Unificar** la máquina de estados de G2 (más completa) + estados de 03 (RADICADO, RECIBIDO, ARCHIVADO, RECHAZADO_PAGO). Documentar máquina única (ver §4 G2 L411). Pendiente validación funcional. |
| C-13 | Tipo de PK (UUID vs INTEGER/BIGINT) | 09/12: UUID; G2: UUID | 01/02/03/04/06/G1: INTEGER/BIGSERIAL | **Híbrido justificado:** UUID para entidades expuestas/federadas (usuario_interno, ciudadano si requiere opacidad, log_auditoria, firma) y BIGINT IDENTITY para catálogos internos de alto volumen. Decisión global de PK pendiente (afecta FKs). Reportado para C3. |
| C-14 | `expediente_electronico.estado_ciclo_vital` dominio | 12: apertura/gestion/cierre/preservacion | G1: activo/semiactivo/historico | **Unificar** con el dominio del módulo especializado (12, AGN): apertura/gestion/cierre/preservacion. El de G1 es la fase archivística (mapeable). |
| C-15 | `pago.estado` dominio | 03: PENDIENTE/APROBADO/RECHAZADO/FALLIDO | G4: pendiente/aprobado/rechazado/reembolsado | **Unión:** pendiente/aprobado/rechazado/fallido/reembolsado (reembolso aparece por política de desistimiento). |
| C-16 | `tipo_documento_identidad` dominio | 04: CC/NUIP/CE/NIT/Pasaporte | 09/G1/G2: CC/CE/TI/PEP/NIT (sin NUIP, sin Pasaporte) | **Unión:** CC/NUIP/CE/NIT/Pasaporte/TI/PEP. Catálogo único; conflicto resuelto por superset. |
| C-17 | `cita.estado` dominio | 06: agendada/cancelada/reprogramada/atendida/no_show | G1/G2/G4: reservada/confirmada/reprogramada/cancelada/no_show/atendida | **Unificar** (G2 más completa): reservada→confirmada→atendida/no_show; ramas cancelada/reprogramada. ('agendada'≡'reservada'). |
| C-18 | `solicitud_arco` plazo | 09: 10 días (consulta) o 15 días | G4: ≤15 días hábiles (Ley 1581 Art.14) | **Por tipo:** acceso/consulta ≤10 hábiles (prorrogable 5); rectificación/cancelación/oposición ≤15 (prorrogable 8). Calculado por `arco_type`. |
| C-09 | servidor_publico vs usuario_interno | 02: servidor_publico (SIGEP, directorio) | 09/12: usuario_interno (credenciales/CMS) | **Separar** ambas entidades, vincular por `usuario_interno.id_sigep`. No todo servidor publicado opera el sistema, ni todo usuario interno está en el directorio. |
| C-19 | Dataset–FormatoAbierto cardinalidad | 11/G1: 1:N (un formato principal) | 11 HU-B2-018: N:N (CSV+JSON+XML simultáneos) | **N:N** via `dataset_formato` (C99). HU-B2-018 publica simultáneo; la 1:N no captura el caso. |
| C-20 | member_class X-Road | G1: GOB/PRIV | 10: "CO" (guía 2019) [AMBIGUO] | **PENDIENTE** validación con AND (§7). Columna `member_class` queda VARCHAR sin CHECK fijo hasta confirmar. |
| C-21 | ST-17 vs ST-48-D (rol líder trámites) | G4: posible duplicado | — | **Fusionar** en `rol_lider_tramites`; no crear rol RBAC duplicado. |

**Total conflictos detectados: 16.**

---

## 6. Inferencias [INFERIDO] (con justificación)

Marcas preservadas de las extracciones (consolidadas, deduplicadas):

| # | Inferencia | Justificación | Entidad/campo | Fuente |
|---|-----------|--------------|---------------|--------|
| I-01 | PK surrogate en todas las entidades | Integridad de entidad (Codd); ninguna clave natural completa declarada | todas | todos |
| I-02 | Contraseñas hasheadas (bcrypt/argon2) | Buena práctica obligatoria; fuente menciona contraseña temporal, no algoritmo | usuario_interno.contrasena_hash | 09,12,G3 |
| I-03 | `created_at`/`updated_at` de auditoría | Trazabilidad OLTP + Decreto 1081/2015 | todas mutables | todos |
| I-04 | `auth_actor` supertipo unificado de usuario | Evita arco exclusivo en log/sesion/intento_login | H1 | 09 §7 |
| I-05 | `documento_electronico` supertipo de adjuntos (arco exclusivo) | Adjuntos en trámite/PQRSD/expediente sin antipatrón FK genérica | C113 | G1 §5.3,G3 I-04 |
| I-06 | `dato_personal_sensible.valor_cifrado` BYTEA | Ley 1581 + MSPI: cifrado en reposo de sensibles | C63 | G1 I-04 |
| I-07 | `version_ficha` en tramite para detectar cambios al reanudar borrador | HU-03-D04 exige detectar cambio de ficha; sin versionado no es posible | tramite.version_ficha | 03 V-07 |
| I-08 | `informe_pqrsd_detalle` tabla separada (no JSONB) | tipo/estado breakdown violan 1FN como JSONB | C127 | 02 I-12 |
| I-09 | `transparencia_publicacion` supertipo | version_documento_transparencia necesita FK con integridad real | C119 | 02 I-11 |
| I-10 | `horario_canal` separada de canal_atencion | Horario atómico viola 1FN; requiere N filas día/apertura/cierre | C49 | 06 I-01 |
| I-11 | `ciudadano_id` nullable en cita/pqrsd/aporte | Agendamiento/PQRSD/aporte admiten anónimos | C53,C32,C43 | 06 I-02,04 |
| I-12 | `cita.cita_original_id` self-FK | Reprogramación crea nueva cita y traza la cadena | C53 | 06 I-03 |
| I-13 | `firma_electronica` arco exclusivo (doc/índice/usuario) | Firma aplica a 3 entidades; sin arco no hay integridad | C111 | 12 I-08,13 |
| I-14 | `politica_retencion` tabla de políticas por categoría | RN-TX-D03 exige retención por tipo; valores pendientes | C134 | G2 I-11 |
| I-15 | `member_class`/`is_own_entity` discriminador X-Road | Alcaldía y entidades externas en misma tabla | C80 | 10 I-01 |
| I-16 | CCD registra traza de consulta, no datos permanentes | RF-B2-021 prohíbe almacenamiento permanente | C88 | 10 I-02 |
| I-17 | `interop_xroad_transaction` BIGSERIAL (alto volumen) | Cada intercambio = 1 registro | C84 | 10 I-03 |
| I-18 | `dataset_metadata` clave-valor extensible | Ley 1753 delega metadatos a MinTIC (ampliables) | C98 | 11 INF-05 |
| I-19 | `interop_error_log.transaction_id` nullable | Error puede preceder al registro de transacción | C93 | 10 I-05 |
| I-20 | `RondaSUS` separada de evaluacion_sus | "Histórico comparable" + exportación CSV exige agrupar | C58 | 08 I |
| I-21 | `estado_cuenta='pendiente_autorizacion'` para menores | Espera de autorización del representante | C62 | G3 I-08 |
| I-22 | `nonce` en token_oidc | Buenas prácticas OIDC (anti-replay) | C66 | 09 I |
| I-23 | `id_sigep` cruza usuario_interno↔servidor_publico | Integración SIGEP requiere clave de cruce | C61 | 12 I-03 |
| I-24 | `calendario_habil.anio` para particionado | Tabla de alta consulta, crecimiento anual | C40 | G1 I-06 |
| I-25 | `dependencia.responsable_id` | Organigrama publica responsable por dependencia | dependencia | 02 I-7 |
| I-26 | `impuesto.vigencia_desde/hasta` | Tarifas tributarias cambian por acuerdo anual | C117 | 02 I-6 |

**Total inferencias consolidadas: 26** (representativas; cada extracción conserva su listado completo trazado).

---

## 7. Vacíos bloqueantes [PENDIENTE] / preguntas abiertas (NO inventados)

| # | Vacío / pregunta abierta | Impacto en BD | Responsable | Fuente |
|---|--------------------------|---------------|-------------|--------|
| P-01 | Períodos de retención/purga por categoría de dato (borrador, cita, log, consentimiento) | `politica_retencion` sin valores; columnas fecha_expiracion_retencion sin default | Oficial Protección Datos (ST-04) | RN-TX-D03 |
| P-02 | Matriz completa trámite ↔ nivel de autenticación mínimo | `nivel_auth_tramite` sin poblar | G-CIO + AND (ST-02/ST-27) | C-08/RN-TX-D04 |
| P-03 | Umbral local de incidente "grave" para CSIRT ≤24h | dominio `incidente_seguridad.classification` / nivel_gravedad | Resp. Seguridad (ST-05) | RN-09-D07 |
| P-04 | Umbral de tasa de éxito de tareas (SUS) | `evaluacion_sus.tasa_exito` CHECK (G4 fija 90% pero A-14 abierto) | Equipo UX (ST-14) | RN-08-D01/A-14 |
| P-05 | Catálogo de trámites con SAP y su término | `tramite.aplica_sap`/`termino_sap_dias` sin poblar | Secretaría Jurídica (ST-46-D) | HU-03-D09 |
| P-06 | Política de reembolso al desistir trámite pagado | Resuelta parcialmente (solo si no inició gestión); detalle por trámite | Tesorería (ST-47-D) | RN-03-D07 |
| P-07 | member_class X-Road: "CO" vs "GOB/PRIV" | `interop_xroad_member.member_class` sin CHECK fijo | AND | C-20/§9 mód.10 |
| P-08 | Estructura formal del número de radicado | Confirmada (SM-{dep}-{año}-{seq6}); validar con Gestión Documental | Gestión Documental (ST-49-D) | RNF-04-D01 |
| P-09 | Margen de gracia de frescura de datasets (¿5 días? ¿configurable?) | umbral de `dataset.estado='desactualizado'` | Equipo datos (ST-03) | RN-11-D01/VAC-01 |
| P-10 | Aportes de participación: ¿en sede o solo SUCOP? | Resuelto (CRUD propio + redirección); confirmar alcance | Participación (ST-50-D) | RF-05-D01/V1 mód.05 |
| P-11 | Roles exactos del SM CMS (más allá de los 3 base) | dominio de `rol`; ¿rol gestión documental separado? | Admin CMS (ST-03) | V-01 mód.12 |
| P-12 | Enrutamiento PQRSD a dependencia: ¿automático o manual? | `asignacion_dependencia.tipo='asignacion_inicial'` por trigger o acción | Admin CMS / Atención | A-11 mód.04/12 |
| P-13 | Configuración de preguntas de encuesta de experiencia | ¿tabla `pregunta_encuesta`? (si varían por trámite) | Líder trámites | V-08 mód.03 |
| P-14 | Trámites jurisdiccionales (demandas, reparto aleatorio, términos) | hasta 4 entidades nuevas si se incluyen | Secretaría Jurídica | V-09 mód.03 |
| P-15 | Multiplicidad de formatos por dataset (1 vs N) | Resuelto N:N (`dataset_formato`); confirmar | Equipo datos | VAC-02 mód.11 |
| P-16 | Inventario completo de micrositios/portales/apps del Distrito | seed de `micrositio` | Dirección TIC | V-03 G1 |
| P-17 | TTL del bloqueo de edición concurrente | `version_contenido.bloqueado_desde` sin duración | Admin CMS / técnico | G4 I-06 |
| P-18 | Esquema de los 4 endpoints REST de la CCD (campos campoDato/valorDato) | estructura de `carpeta_ciudadana`/`interop_ccd_*` | AND | V-07/V-08 G1 |
| P-19 | Período de retención de borradores | Resuelto (30 días); confirmar formalmente | Líder trámites + Protección Datos | RNF-03-D02 |
| P-20 | Traducción / lenguas étnicas (DIFERIDO Could) | `contenido_traduccion` diferida | Comunicaciones (ST-51-D) | RNF-TX-D02 |
| P-21 | Valores N de reintentos X-Road y timeout (segundos) | CHECK `retry_count`, lógica fallback | Arquitectura técnica | §8 mód.10 |
| P-22 | Capacidad/TTL de la cola TSA | límites de `interop_tsa_queue_item` | Arquitectura técnica | §8 mód.10 |
| P-23 | Pruebas RTO/RPO (DRP/BCP); restauración de backups | sin UC; `backup_log` existe pero falta caso "probar restauración" | Arquitecto/DevOps (ST-16) | RNF-TX-D04/RF-B1-067 |

**Total vacíos bloqueantes [PENDIENTE]/preguntas abiertas: 23.**

---

## 8. Fuera de BD (presentación / infraestructura / RNF)

Elementos declarados explícitamente FUERA del modelo relacional (no contaminan el esquema):

**Presentación / front-end / CSS-JS (módulos 01, 07, 08):** Top bar GOV.CO, tipografía Nunito Sans/Verdana, paleta Cobalt #0943B5, breadcrumb, botón "volver arriba", acordeón, modal, toast, botones Kit UI, galería de aplicaciones, spinner, paginación, ordenamiento de tablas, cuadrícula Bootstrap, modal de salida (HTML; la tabla `dominio_confianza` SÍ persiste), página 404, sitemap.xml, mapa del sitio, responsive/hamburguesa, autocompletado del buscador, breakpoints, zoom 400%, tap-targets, sin destellos PEAT, controles de pausa, UTF-8, lenguaje claro/editorial, tooltips, gestos multidedo, prefers-reduced-motion, vínculos visitados, botón atrás, stepper UI, validación en línea, desplegables con filtro.

**Estándares de markup/calidad (07, 08):** WCAG 2.1 AA (52 criterios CC1-CC32), HTML semántico, alt/ARIA/role, contraste, navegación por teclado, HTML válido W3C, NTC 5854, lang="es-CO", índice Fernández-Huerta, SEO/posición Google, cobertura unitaria ≥70%, Clean Code/CI-CD.

**Infraestructura / red / seguridad (09, 10):** HTTPS/TLS 1.2-1.3, cabeceras HTTP (CSP/HSTS/X-Frame), certificados SSL en HSM (el REGISTRO va a `certificado_digital`), captcha externo, métodos HTTP, sanitización, mensajes de error genéricos, hardening de servidor, rate-limiting (eventos SÍ van a `log_auditoria`), SIEM/monitoreo (alertas→`incidente_seguridad`), pentest, CI/CD/parches, ejecución de backups (registro→`backup_log`), DRP/BCP, cifrado en reposo AES-256, MSPI/SGSI/ISO 27000, validación MIME (intento→`log_auditoria`), servidor X-Road (metadatos→`interop_server_environment`), anclajes XML AND, imagen Docker, scripts de instalación, reglas de firewall (flag→`interop_tsa_config`), mensajes SOAP/REST en tránsito (solo hash+firma+TSA persisten), portal GOV.CO/CCD (catálogo→`interop_external_system`).

**Analítica externa (08):** Google Analytics / Search Console / Hotjar / Matomo; `HistorialBusquedas` EXCLUIDO de BD propia (delegado a herramientas SaaS) salvo decisión arquitectónica futura.

**RNF sin tabla (transversal):** disponibilidad ≥98%/≥95% (SLA monitoreo), FCP ≤2.5s, observabilidad, i18n diferida, doble pila IPv4/IPv6 (config DNS; flag en `sede_electronica`).


---

# DELTA — 2ª pasada profunda de extracción (ronda anti-tablas-anchas)

> Re-lectura íntegra de fuentes + 16 extracciones. Cierra la traza de entidades LOCAL (obs. del auditor) y diagnostica las tablas anchas. Rigor PhD, honestidad: ancho ≠ no-normalizado.

## A. Traza fila-por-fila de LOCAL antes delegadas — discrepancias de recuento

Las tablas LOCAL completas SÍ existen en `_extraccion/`. 8 discrepancias de recuento (Δ): en 7, el inventario sumó campos de G2-G4 al recuento sin acarrearlos al detalle (el extra existe en G2-G4, no hay pérdida). 1 subdimensión real:
- Δ-1 `tipo_pqrsd`: inventario 9 vs fuente 8 (9º = atributo de comportamiento no columnizado, ver B.4).
- Δ-2 `traslado_competencia`, Δ-3 `prorroga`, Δ-4 `respuesta_pqrsd`, Δ-5 `servicio_agendable`, Δ-6 `franja_horaria`: recuento incluye campos de G2/G3/G4.
- **Δ-7 `recordatorio_cita`/`notificacion_cita` (C54): SUBDIMENSIÓN REAL** — fuente 06 lista 7 campos (notificacion_id, cita_id, tipo_notificacion, canal_envio, destinatario, fecha_hora_envio, estado_envio), inventario cuenta 5. Mapear canal_envio/destinatario/estado_envio aunque se absorba en notificacion/intento_notificacion.
- Δ-8 `recurso_inclusivo`: inventario 6 vs fuente 5.
(Resto de LOCAL: traza confirmada exacta contra `_extraccion/` — C02, C04, C05, C06, C07, C10, C12, C13, C17, C18, C24, C29, C30, C33, C35-C38, C49-C52, C55, C77, C78, C107, C110, C114, C115, C118, C122, C127.)

## B. Entidades/campos OMITIDOS descubiertos
- **B.1** `menu_navegacion.es_obligatorio` (BOOL, marca los 4 ítems fijos GOV.CO no-borrables) y `aria_label` (VARCHAR300, accesibilidad persistida) — 01 L93-94.
- **B.2** `noticia.ratio_imagen` (ENUM 4:3/16:9 + CHECK) — 01 L110.
- **B.3** `contrato.estado_ejecucion` sin dominio enumerado — inv. L599.
- **B.4** `tipo_pqrsd`: atributos de subtipo no columnizados — `admite_anonimato`, `requiere_respuesta_formal`, `admite_traslado_procuraduria` (04 §5.1 matriz L280-286).
- **B.5** `dependencia.es_externa` (BOOL) + `entidad_externa_nombre` (para traslado a Procuraduría, RN-04-D03) — 04 L109-110.
- **B.6** `dependencia.codigo` (código orgánico, origen del prefijo del radicado SM-{COD}-AAAA-...) — 04 L108.
- **B.7** `dependencia.responsable_id`(FK→servidor_publico), `sede_id`, `descripcion`, `activa` — 02 L205-206, 06 L78-80.
- **B.8** `funcionario_apoyo` (entidad 06 §2.12: sede_id, nombre_completo, disponible) — sin resolver si es subtipo o tabla.
- **B.9** `sede_electronica.estado_integracion` valor de baja/desintegración (V-04, conflicto C-11) — vacío bloqueante real.
- **B.10** `requerimiento_subsanacion.nueva_fecha_vencimiento_tramite` (recalcula vencimiento tras subsanación, RN-03-D05) — 03 L230.
- **B.11** `pago` cardinalidad real 1:N (reintentos) con UNIQUE parcial `(id_solicitud) WHERE estado='APROBADO'` — NO 1:0..1 — 03 I-6 L418.
- **B.12** `solicitud → retroalimentacion` 1:N (momentos INICIO+FINAL, 0..2) — 03 §4 L360.
- **B.13** 🔴 **Entidad `dependencia` sin Cxx ni tabla de campos** pese a ser destino de ≥8 FK (pqrsd, asignacion, traslado, servicio_agendable, servidor_publico, tramite, canal_atencion, radicado). Hueco estructural — la creó la Fase 4 pero el inventario nunca la modeló.
- **B.14** `informe_pqrsd.url_kogui`, `tiempo_promedio_respuesta` — 02 L153-154.
- **B.15** `incidente_seguridad.nivel_gravedad` sin umbral cuantitativo (P-03, CSIRT ≤24h) — 09 RN-09-D07.

## C. Tablas anchas — diagnóstico de descomposición

| Tabla | nº col | Estructura escondida | Cluster / discriminador | Recomendación |
|---|---|---|---|---|
| `solicitud` | 30 | 🔴 clusters DUPLICADOS (cols nullable + entidades 1:1) | BORRADOR(C21), DESISTIMIENTO(C25), SAP(C26) | **DESCOMPONER**: elegir UNA representación (recomiendo entidades 1:1 ya existentes; quitar cols de solicitud). Dos fuentes de verdad. |
| `ciudadano` | 26 | 🔴 ISA disjoint natural/jurídica | razon_social, representante_legal_id / `es_persona_juridica` | **DESCOMPONER a class-table** (G1 §5.1 lo prescribe). Justificado (IR multi-módulo). |
| `cita` | 18 | 🔴 FD oculta (3FN parcial) | nombre/correo/telefono_contacto redundantes si `ciudadano_id` NOT NULL | **DESCOMPONER**: contacto solo para citas anónimas; derivar de ciudadano si hay FK. |
| `contrato` | 17 | 🔴 derivados persistidos | porcentaje_ejecutado=valor/monto*100; pagos_pendientes=monto-pagos_realizados | **NO persistir** (vista/columna generada). |
| `tramite` | 30 | (a) single-table ISA + cluster SAP | aplica_sap→termino_sap_dias / `tipo_servicio` | **DEJAR ANCHA (BCNF legítimo)** — consulta dominante ≤2s; cluster SAP nullable-condicional aceptable. |
| `pqrsd` | 22 | cluster identificación opcional | nombre/doc/direccion NULL si `es_anonima` | **DEJAR ANCHA (BCNF legítimo)** — subtipos de comportamiento en tipo_pqrsd (04 §5.2). |
| `documento_electronico` | 22 | single-table + arco exclusivo | contexto + tipo_origen | **DEJAR** (H6 resuelto). AIFID = 4 BOOL atómicos. |
| `usuario_interno` | 21 | clusters bloqueo/baja | FD: user_id→todo | **DEJAR ANCHA (BCNF)** — MFA ya en mfa_enrollment. |
| `contenido` | 20 | (c) JSONB esconde atributos de subtipo | metadatos_json por `tipo` | **VERIFICAR**: extraer del JSONB los atributos de subtipo consultables (norma→vigencia). H7 pendiente. |
| `interop_xroad_transaction` | 19 | clusters TSA/error nullable por status | status | **DEJAR ANCHA (BCNF)** — alto volumen; cola TSA ya separada. |
| `log_auditoria`, `notificacion`, `sesion`, `token_oidc`, `certificado_digital`, `dataset` | 16-18 | polimorfismo/arco/clusters condicionales | varios | **DEJAR ANCHAS (BCNF legítimas)** — subtablas naturales ya extraídas. |
| `evaluacion_sus` | 16 | (c) 10 ítems Likert embebidos | item_1..item_10 | **VERIFICAR/NORMALIZAR** a `respuesta_item_sus` (no columnas). |
| `encuesta_experiencia` | 8 | (c) multivaluado embebido | respuesta_pregunta_1/2/3 | **NORMALIZAR** a `pregunta_encuesta`+`respuesta_encuesta` si preguntas configurables (P-13). |

## D. Veredicto
**DEBEN descomponerse (defecto real): 5** — `solicitud` (duplicación), `ciudadano` (ISA), `cita` (FD oculta 3FN), `contrato` (derivados), `dependencia` (entidad ausente). **+3 a verificar 1FN**: `encuesta_experiencia`, `evaluacion_sus`, JSONB `tramite.georreferenciacion`/`contenido.metadatos_json`.
**ANCHAS pero CORRECTAS (BCNF legítimo, descomponer = preferencia NO requisito): 11** — tramite, pqrsd, documento_electronico, usuario_interno, sesion, token_oidc, certificado_digital, dataset, interop_xroad_transaction, log_auditoria, notificacion. **No se inventaron violaciones.**
**A investigar (web):** estructura completa de `dependencia` (organigrama oficial), columnización tipo_pqrsd, valor baja sede, ítems SUS, umbral incidente grave, preguntas encuesta fijas vs configurables.

---

# DELTA Ronda 3 — Extracción profunda (revisión de completitud)

> **Rol:** revisor de completitud. Objetivo: encontrar lo que se ESCAPÓ a las rondas 1-2.
> **Método:** re-lectura íntegra de las 12 fuentes modulares (`## 7. Datos/Entidades`, `## 9. Ambigüedades`), de los 5 deltas profundos de `_global/` (rf-rnf/rn/uc/hu/stakeholders — **NO listados en la cobertura §0.1**) y de `_levantamiento/`. Barrido dirigido del corpus web (rg) sobre formularios, anexos, umbrales, fórmulas y estados.
> **Honestidad:** las rondas 1-2 ya cubrieron lo grueso. El DELTA Ronda 2 (§A-D) ya detectó `dependencia` ausente, columnización de `tipo_pqrsd`, `cita`/`solicitud`/`contrato` a descomponer. **Aquí NO se repite eso**: se reporta SOLO lo nuevo respecto de la Ronda 2. Cada ítem cita fuente. Inferencias marcadas `[INFERIDO]`.

## R3.A — Entidades nuevas (no presentes en el catálogo C01–C135 ni en el DELTA Ronda 2)

| # | Entidad propuesta | Por qué es necesaria (regla/uso del dominio) | Fuente |
|---|---|---|---|
| N-01 | `dependencia` (FORMALIZAR con tabla de campos y Cxx) | Destino de ≥8 FK (pqrsd, asignacion_dependencia, traslado, servicio_agendable, servidor_publico, tramite, canal_atencion, radicado). Ronda 2 (B.13) la marcó ausente pero NO le dio tabla de campos ni Cxx. **Campos:** id(PK), `codigo` (orgánico, origen del prefijo del radicado), nombre, descripcion, `id_responsable`(FK→servidor_publico), `id_sede`(FK→sede_fisica), `es_externa`(BOOL, traslado a Procuraduría/otras autoridades), `entidad_externa_nombre`, correo, telefono, activa(BOOL), `padre_id`(self-FK, organigrama jerárquico) | 02 §7 L143; 04 §7; 06 §7; Ronda 2 B.5/B.6/B.7/B.13 |
| N-02 | `plan_integracion_tramite` (detalle del plan, NORMALIZA JSONB) | `plan_integracion.tramites_incluidos` se modeló como JSONB, pero la fuente enumera atributos consultables por trámite: **nombre, solicitudes/año, acción, fecha, responsable**. Viola 1FN como JSONB (no se puede consultar "trámites cuya acción=converger del responsable X"). Tabla hija: id(PK), id_plan(FK), id_tramite(FK nullable), nombre_tramite, solicitudes_anio, accion(converger/enlazar/retirar), fecha_objetivo, id_responsable(FK→servidor_publico) | 01 §7 L167 ("trámites (nombre, solicitudes/año, acción, fecha, responsable)") |
| N-03 | `plan_integracion_dominio` y `plan_integracion_otro_medio` (NORMALIZA JSONB) | Igual que N-02: `dominios_web` y `otros_medios` (apps, chatbots, PQR) son multivaluados; hoy JSONB. Mínimo: id, id_plan(FK), valor/url, tipo | 01 §7 L167 |
| N-04 | `funcionario_apoyo` | Entidad declarada en 06 §2.12 (sede_id, nombre_completo, disponible) para atención inclusiva/asistida; Ronda 2 (B.8) la dejó "sin resolver si es subtipo o tabla". Decisión: tabla propia o subtipo de usuario_interno por `ambito`. Sin Cxx hoy | 06 §2.12; Ronda 2 B.8 |
| N-05 | `suscripcion_alerta_ciudadano` [INFERIDO] | El portal GOV.CO ofrece "que le generen los resultados de manera automática al grupo de interés (no por demanda)" → suscripción a alertas/novedades. Sin tabla. Si se confirma alcance: id, id_ciudadano(FK), tipo_evento, canal, activa, fecha_alta. **Marcar [INFERIDO]**: la fuente lo sugiere pero no detalla campos | extracto-web L22134; 05 §7 (grupos de interés) |
| N-06 | `pregunta_encuesta` + `respuesta_encuesta` (NORMALIZA `encuesta_experiencia`) | Ronda 2 (§C) marcó `encuesta_experiencia` "a VERIFICAR 1FN" pero NO creó las entidades. Si las preguntas son configurables por trámite (P-13), las columnas `respuesta_pregunta_1/2/3` violan 1FN → tabla `pregunta_encuesta`(id, id_tramite, texto, orden, tipo) + `respuesta_encuesta`(id, id_encuesta, id_pregunta, valor). Condicionado a P-13 | 03 §2.8; Ronda 2 §C / P-13 |
| N-07 | `respuesta_item_sus` (NORMALIZA `evaluacion_sus`) | Ronda 2 (§C) marcó `evaluacion_sus` "a NORMALIZAR" (10 ítems Likert embebidos item_1..item_10) pero NO creó la entidad. Tabla: id, id_evaluacion_sus(FK), numero_item(1–10), valor_likert(1–5). Saca 10 columnas repetidas → 1FN | 08 §2.A; Ronda 2 §C |
| N-08 | `definicion_glosario` [INFERIDO] | El portal expone un GLOSARIO de términos (derecho de petición, tipos de trámite, etc.) como contenido estructurado consultable. Candidato a subtipo de `contenido` (tipo='glosario') más que entidad propia. Reportado para decisión | extracto-web L6561/L13041/L17876 |

> **Entidades nuevas R3 que SÍ deben crearse con campos: 5** (N-01 `dependencia` con detalle, N-02, N-03, N-06, N-07). **Diferidas/condicionadas: 3** (N-04, N-05, N-08).

## R3.B — Campos nuevos por entidad EXISTENTE (no en Ronda 1 ni Ronda 2)

| # | Entidad ← campo | Tipo | Justificación | Fuente |
|---|---|---|---|---|
| F-01 | `incidente_seguridad` ← `sic_rnbd_reportado` (BOOL), `sic_reporte_fecha` | BOOL/TIMESTAMPTZ | El reporte va a CSIRT/ColCERT **y** a SIC/RNBD (dos destinos distintos); el inventario solo tiene `sic_notified_at`. RNBD es registro separado | 09 §8 L172 ("SIC: RNBD, notificación de brechas") |
| F-02 | `sede_electronica` ← `declaracion_conformidad_sic` (BOOL) [INFERIDO] | BOOL | Nube extranjera para datos de ciudadanos → declaración de conformidad SIC (Art.26 Ley 1581). Flag de cumplimiento persistible | 09 §9 L180 |
| F-03 | `incidente_seguridad`/RNF ← integración SIEM como sistema externo | fila en `interop_external_system` | "Zona de Auditoría con correlación de logs implica un SIEM no nombrado". Hoy fuera de BD; al menos el catálogo del SIEM debería estar en `interop_external_system` | 09 §9 L181 |
| F-04 | `log_auditoria`/`intento_login` ← entidad/contador de **rate-limiting** | tabla `rate_limit_contador` o campos | RNF-09-D02 + HU-09-D07 elevan rate-limiting a requisito persistible (throttling por IP/sesión en login/PQRSD/búsqueda). Ronda 1 lo dejó FUERA DE BD (§8); el delta lo vuelve requisito. Mínimo: clave(IP/sesión), ventana, contador, bloqueado_hasta | HU-09-D07; RNF-09-D02; stakeholders ST-05 |
| F-05 | `cita` ← `metrica_no_show` / `cita.fue_no_show` ya existe vía estado, pero falta `recordatorio_canal` y `recordatorio_fecha_envio` | VARCHAR/TIMESTAMPTZ | Ronda 2 (Δ-7) detectó subdimensión en `recordatorio_cita` (canal_envio, destinatario, estado_envio sin mapear). Confirmado: persistir esos 3 en notificacion/intento_notificacion | 06 §2; Ronda 2 Δ-7; HU-06-D04 |
| F-06 | `mecanismo_participacion` ← `id_oficina_responsable` (FK→dependencia) | BIGINT FK | La Oficina de Atención al Ciudadano y Participación Social asume Art.17 Ley 2052; el mecanismo necesita dueño. Hoy sin FK al responsable | 05 §9 L91 (oficina confirmada) |
| F-07 | `grupo_interes` ← `caracterizacion` (TEXT, nullable) + `tiene_caracterizacion_formal` (BOOL) | TEXT/BOOL | A-07: la norma exige caracterizar "conforme a su caracterización"; el atributo de si existe caracterización formal y su contenido es dato del dominio | 02 §9 A-07; 05 §9 A-07 |
| F-08 | `interop_tsa_config` ← `host_salida` ('tsa.gse.com.co'), `puerto` (443), `proveedor` (GSE) | VARCHAR/INT | El borrador-AND fija el endpoint y proveedor concretos del estampado. Datos de configuración persistibles (regla de firewall = flag ya previsto) | _levantamiento/borrador-solicitud-AND L32 |
| F-09 | `interop_external_system` ← filas seed: Orfeo (SGDEA), DigitalOcean (hosting), Cloudflare (DNS/WAF) | datos seed | El borrador-AND nombra el stack real: SGDEA=Orfeo, nube=DigitalOcean, CDN/WAF=Cloudflare. Catálogo de sistemas externos debe incluirlos | _levantamiento/borrador-solicitud-AND L31-33; 12 §8 L142 |
| F-10 | `sede_electronica` ← `id_peti_referencia` / `peti_vigencia` ('2024-2027') | VARCHAR | PETI 2024-2027 confirmado vigente; `plan_integracion.incorporado_peti` existe pero falta la referencia al PETI mismo | borrador-AND L26; 12 §9 L152 |
| F-11 | `contenido` ← `idioma_origen` y soporte de `contenido_traduccion` con `marca_es_original` | VARCHAR/BOOL | RN-TX-D05 (fallback al castellano marcando que es el original). `contenido_traduccion` (C135 DIFERIDA) necesita el flag `es_original`/`fallback` | rn-delta RN-TX-D05; uc-delta |
| F-12 | `dataset`/`registro_activo_informacion` ← `actualizacion_automatica` (BOOL) | BOOL | "¿El CMS tiene capacidad de actualización automática hacia datos.gov.co o es manual?" — flag de modo de federación por dataset | 11 §9 L96 |

> **Campos nuevos R3 sobre entidades existentes: 12** (de ellos 2 `[INFERIDO]`: F-02, y parte de F-04).

## R3.C — Relaciones / FK nuevas detectables en inventario

| # | Relación | Cardinalidad | Justificación | Fuente |
|---|---|---|---|---|
| R-01 | `dependencia` →(self) `dependencia.padre_id` | 1:N | Organigrama jerárquico (Dirección TIC, Atención al Ciudadano y Participación Social, etc.) | 05 §9; borrador-AND L30 |
| R-02 | `dependencia.id_responsable` → `servidor_publico` | N:1 | Directorio publica responsable por dependencia (I-25 ya inferido, pero sin FK formal porque `dependencia` no estaba modelada) | 02 §7 L143 |
| R-03 | `mecanismo_participacion.id_oficina_responsable` → `dependencia` | N:1 | Dueño funcional del espacio participativo (Oficina de Participación) | 05 §9 L91 |
| R-04 | `plan_integracion` → `plan_integracion_tramite`/`_dominio`/`_otro_medio` | 1:N | Normalización de los 3 JSONB del plan (N-02, N-03) | 01 §7 L167 |
| R-05 | `tramite` ↔ `plan_integracion_tramite` | N:1 (nullable) | Un trámite del catálogo puede figurar en el plan de convergencia | 01 §7 L167 |
| R-06 | `encuesta_experiencia` → `respuesta_encuesta` → `pregunta_encuesta` | 1:N / N:1 | Normalización N-06 (saca respuesta_pregunta_1/2/3) | 03 §2.8; P-13 |
| R-07 | `evaluacion_sus` → `respuesta_item_sus` | 1:N (10) | Normalización N-07 (saca item_1..item_10) | 08 §2.A |
| R-08 | `funcionario_apoyo.id_sede` → `sede_fisica` | N:1 | Atención inclusiva por sede física | 06 §2.12 |
| R-09 | `solicitud`/`pqrsd` ↔ `dependencia` (FK formal) | N:1 | Las FK a `dependencia` ya se citan pero apuntaban a una tabla inexistente; al formalizar N-01 se cierran | 03/04 §7 |
| R-10 | `suscripcion_alerta_ciudadano.id_ciudadano` → `ciudadano` [INFERIDO] | N:1 | Suscripción a alertas por grupo de interés | extracto-web L22134 |

> **Relaciones/FK nuevas R3: 10** (1 `[INFERIDO]`: R-10).

## R3.D — Tablas anchas / 1FN candidatas NO señaladas en Ronda 2

> La Ronda 2 (§C) ya diagnosticó solicitud, ciudadano, cita, contrato, tramite, pqrsd, documento_electronico, etc. **Nuevo en R3:**

| Tabla | Estructura escondida | Veredicto |
|---|---|---|
| `plan_integracion` | 3 JSONB (`tramites_incluidos`, `dominios_web`, `otros_medios`) con atributos consultables (acción, fecha, responsable, tipo) | **DESCOMPONER** a N-02/N-03 (1FN). Ronda 2 no lo tocó. |
| `encuesta_experiencia` | `respuesta_pregunta_1/2/3` (Ronda 2 dijo "verificar", R3 confirma y crea N-06) | **NORMALIZAR** si preguntas configurables (P-13) |
| `evaluacion_sus` | `item_1..item_10` Likert (Ronda 2 dijo "normalizar", R3 crea N-07) | **NORMALIZAR** a `respuesta_item_sus` |
| `carpeta_ciudadana` | `historial_tramites` (JSONB) + `historial_solicitudes` (JSONB) | **VERIFICAR**: RF-B2-021 prohíbe almacenamiento permanente → JSONB de traza es ACEPTABLE aquí (no réplica). Dejar como está, documentado. |
| `dataset` | `metadatos_completos` (BOOL) coexiste con `dataset_metadata` (clave-valor): redundancia derivada | **NO persistir** `metadatos_completos` o derivarlo de la presencia de metadatos obligatorios (columna generada) |

## R3.E — Aún faltante (ni en fuentes → enviar a `jose-bd-investigador`)

> Vacíos que NINGUNA fuente resuelve; superan los P-01..P-23 ya listados. No se inventan.

1. **Estructura/campos de `dependencia` desde el organigrama oficial** de la Alcaldía de Santa Marta (códigos orgánicos reales, jerarquía, responsables). Las fuentes confirman que existe (`/dependencias`) pero no enumeran sus campos ni la lista. → investigar organigrama oficial.
2. **Catálogo de trámites jurisdiccionales** (demandas, reparto aleatorio, términos procesales, audiencias por videoconferencia) — P-14 abierto; la guía usa "debería". Si aplican, son ≥4 entidades nuevas no modelables hoy. → investigar competencias jurisdiccionales del Distrito (Art.103 CGP).
3. **Esquema de los 4 endpoints REST de la CCD** (campos `campoDato`/`valorDato`) — P-18 abierto; sin contrato técnico de la AND no se columnizan `interop_ccd_service`/`carpeta_ciudadana`. → investigar manual técnico CCD-AND.
4. **Ítems exactos de la escala SUS** (texto de los 10 enunciados Likert) para poblar `pregunta`/catálogo — Ronda 2 lo pidió; SUS es estándar (Brooke 1996) pero la redacción ES-CO oficial no está en fuentes. → investigar instrumento SUS validado.
5. **Caracterización formal de grupos de interés/valor** (A-07): la norma exige "conforme a su caracterización" sin definirla. → investigar metodología DAFP de caracterización de grupos de valor.
6. **Preguntas fijas vs configurables de la encuesta de experiencia** (P-13) — determina si N-06 es 3 columnas o 2 tablas. → decisión de líder de trámites (no investigación web).
7. **Definición de baja/des-integración de una sede GOV.CO** (V-04, B.9): "ningún documento define el proceso de baja de un portal de GOV.CO". → investigar lineamiento MinTIC de desvinculación.

## R3.F — Conteo del delta Ronda 3

| Categoría | Cantidad | de ellas `[INFERIDO]` |
|---|---|---|
| **Entidades nuevas** (a crear con campos) | **5** (N-01 dependencia, N-02, N-03, N-06, N-07) | 0 |
| Entidades nuevas diferidas/condicionadas | 3 (N-04, N-05, N-08) | 2 |
| **Campos nuevos** sobre entidades existentes | **12** (F-01…F-12) | 2 |
| **Relaciones/FK nuevas** | **10** (R-01…R-10) | 1 |
| **Tablas anchas/1FN nuevas** (no en Ronda 2) | 5 (plan_integracion + 4) | — |
| **Aún faltante → investigador** | 7 ítems | — |

> **Resumen R3:** 5 entidades nuevas firmes (+3 condicionadas), 12 campos nuevos, 10 relaciones nuevas, 5 tablas anchas adicionales, 7 vacíos a investigar. **Hallazgo de mayor impacto: `dependencia` por fin con tabla de campos y `plan_integracion` descompuesto (3 JSONB que violan 1FN).** Cero invención: todo cita archivo:sección o se marca `[INFERIDO]`.


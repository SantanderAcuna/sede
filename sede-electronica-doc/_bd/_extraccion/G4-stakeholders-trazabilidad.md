# Extracción BD — Global G4 Stakeholders y Trazabilidad

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta (NIT 891.780.009-4)
> **Fecha:** 2026-06-07
> **Metodología:** jose-bd (rigor PhD, sin invención, cero omisiones)
> **Unidades leídas:**
> - `_global/stakeholders-y-entrevistas.md` (444 líneas, íntegras)
> - `_global/stakeholders-delta-profundo.md` (229 líneas, íntegras)
> - `_global/hu-delta-profundo.md` (540 líneas, íntegras)
> - `_global/matriz-trazabilidad.md` (446 líneas, íntegras)

---

## 0. Cobertura

**4 unidades íntegras: SÍ · líneas totales: 1.659 (444 + 229 + 540 + 446) · omitidas: 0**

| Unidad | Líneas | Cobertura |
|--------|--------|-----------|
| stakeholders-y-entrevistas.md | 1–444 | 100% |
| stakeholders-delta-profundo.md | 1–229 | 100% |
| hu-delta-profundo.md | 1–540 | 100% |
| matriz-trazabilidad.md | 1–446 | 100% |

---

## 1. Actores / Roles del sistema → insumo RBAC

### 1.1 Roles internos (tipo I) — con permisos diferenciados en el sistema

| # | ID stakeholder | Rol / Nombre en sistema | Hecho / Inferido | Permisos implícitos clave | Fuente |
|---|---------------|------------------------|------------------|--------------------------|--------|
| 1 | ST-01 | `rol_alcalde` — Alcalde / Representante legal | HECHO | Responsable del tratamiento de datos; firma política de gobierno digital; no opera el sistema directamente [INFERIDO] | stk:16 · matriz:§Obj |
| 2 | ST-02 | `rol_g_cio` — Director TIC / G-CIO | HECHO | Configura integración GOV.CO; gestiona niveles de autenticación; aprueba presupuesto tecnológico; no tiene rol en el CMS de contenidos | stk:17–18 |
| 3 | ST-03 | `rol_admin_cms` — Administrador del CMS / Sede (Maritza R.) | HECHO | CRUD global de contenidos, usuarios internos, tablero ITA, configuración de módulos; roles del SM CMS sin confirmar | stk:18 · matriz:336 |
| 4 | ST-04 | `rol_oficial_datos` — Oficial de Protección de Datos | HECHO | Gestiona ARCO, autorizaciones, RNBD, PIGDP, evaluación de impacto; decide períodos de retención/purga por categoría (borrador, cita, log) | stk:19 · hu:127 · matriz:360 |
| 5 | ST-05 | `rol_resp_seguridad` — Responsable de Seguridad MSPI | HECHO | Define umbral de incidente "grave" (CSIRT ≤24h); exige MFA admins CMS; autoriza anti-IDOR; define inmutabilidad del log; establece rate-limiting | stk:20 · hu:132 |
| 6 | ST-06 | `rol_admin_xroad` — Administrador X-Road / TI Interop | HECHO | Configura subsistemas/servicios X-Road; gestiona certificados ONAC/TLS/OCSP; única instancia autorizada para firmar/sellar vía TSA | stk:21 |
| 7 | ST-07 | `rol_funcionario_dependencia` — Funcionario de dependencias (PQRSD/Trámites) | HECHO | Enruta, reasigna, traslada, prorroga, responde y cierra PQRSD; requiere subsanación en trámites; NO puede aprobar su propia PQRSD [INFERIDO] | stk:22 · hu:125–129, 172–203 |
| 8 | ST-08 | `rol_control_interno` — Jefe de Control Interno | HECHO | Audita; accede a logs e informes; no opera el CMS; publica informes de control semestral | stk:23 |
| 9 | ST-09 | `rol_comite_gestion` — Comité Institucional de Gestión y Desempeño | HECHO | Rol de supervisión / gobernanza; sin acceso directo al sistema [INFERIDO] | stk:24 |
| 10 | ST-10 | `rol_atencion_ciudadano` — Oficina de Atención al Ciudadano | HECHO (confirmado 2026-06-05) | Gestiona canales; coordina agenda; responde PQRSD de primer nivel; contacto: atencionalciudadano@santamarta.gov.co · L-V 8:00-17:00 | stk:25 · matriz:175 |
| 11 | ST-11 | `rol_equipo_ux` — Equipo UX/UI / Writer / Visual / SEO | HECHO | Diseño de interfaces; sin acceso a datos de producción directamente; ejecuta pruebas de usuario | stk:26 |
| 12 | ST-12 | `rol_ing_accesibilidad` — Ingeniero de accesibilidad | HECHO | Audita WCAG; valida componentes antes de publicar; sin permisos de publicación directa [INFERIDO] | stk:27 |
| 13 | ST-13 | `rol_qa` — Analista QA | HECHO | Ejecuta pruebas; accede a ambientes de prueba; sin acceso a producción [INFERIDO] | stk:28 |
| 14 | ST-14 | `rol_ux_researcher` — UX Researcher | HECHO | Conduce estudios SUS y pruebas de usuario; almacena y exporta resultados | stk:29 · hu:301–304 |
| 15 | ST-15 | `rol_desarrollador` — Desarrollador front/back | HECHO | Acceso a código fuente y CI/CD; sin acceso a datos de producción directamente [INFERIDO] | stk:30 |
| 16 | ST-16 | `rol_arquitecto_devops` — Arquitecto de solución / DevOps | **[INFERIDO]** | Gestiona infraestructura, pipeline, backups DRP/BCP; configura observabilidad | stk:31 |
| 17 | ST-17 | `rol_lider_tramites` — Líder funcional de trámites / Product Owner | HECHO | Define prioridades del catálogo SUIT; decide política de retención de borradores; negocia matriz nivel de auth por trámite con G-CIO/AND | stk:32 · hu:523–524 |
| 18 | ST-18 | `rol_mesa_ayuda` — Mesa de ayuda / Soporte al ciudadano | **[INFERIDO]** | Atiende incidentes nivel 1; sin acceso a datos personales directamente [INFERIDO] | stk:33 |
| 19 | ST-41-D | `rol_admin_agenda` — Administrador de atención / Agenda de citas | **[INFERIDO]** | Define servicios agendables, franjas, cupos y bloqueos; publica la oferta de citas; registra no-shows | stk:56 · hu:243–267 |
| 20 | ST-42-D | `rol_editor` — Editor de contenidos (crea/edita) — rol SoD | HECHO | Crea y edita contenidos; envía a aprobación; NO puede aprobar lo propio (SoD); NO puede publicar directamente | stk:57 · hu:31–35, 324–329 |
| 21 | ST-43-D | `rol_aprobador` — Aprobador / Publicador de contenidos — rol SoD | HECHO | Aprueba o rechaza contenidos enviados por el editor; NO puede crear ni editar lo que aprueba (SoD) | stk:58 · hu:324–329 · matriz:266, 380 |
| 22 | ST-45-D | `rol_admin_cumplimiento` — Administrador de cumplimiento ITA / Transparencia | HECHO | Valida publicaciones contra criterios ITA; configura alertas de vencimiento (Res.1519 plazos); accede al tablero ITA | stk:60 · hu:60–65 |
| 23 | ST-46-D | `rol_secretaria_juridica` — Secretaría Jurídica | HECHO | Arma el catálogo de trámites con SAP y sus términos; aprueba o declara el efecto positivo del silencio administrativo | stk:61 · hu:138–143 · matriz:433 |
| 24 | ST-47-D | `rol_tesoreria` — Tesorería / Financiera distrital | HECHO | Gestiona reembolsos y recaudo en línea; formaliza el reglamento de cartera de trámites; valida que no haya recargo de pasarela | stk:62 · hu:95–100 · matriz:432 |
| 25 | ST-49-D | `rol_resp_documental` — Responsable de Gestión Documental / SGDEA | HECHO | Define TRD; valida foliado e índice firmado del expediente; opera/supervisa la instancia de Orfeo (SGDEA) | stk:63 · matriz:350 |
| 26 | ST-50-D | `rol_participacion_ciudadana` — Oficina de Participación Ciudadana | **[INFERIDO]** | Administra ciclos de consulta pública dentro de la sede; publica consolidados de observaciones | stk:64 · hu:227–238 |
| 27 | ST-51-D | `rol_comunicaciones` — Oficina de Comunicaciones | HECHO | Gestiona comunicados, carrusel, noticias; decide traducción y lenguas étnicas; alimenta SAMI | stk:65 |

**Total roles internos con permisos diferenciados: 27**

> Nota: ST-48-D ("Líder funcional de trámites" del delta) está PENDIENTE de fusión con ST-17. El archivo integrado `stakeholders-y-entrevistas.md` no lo incluye (50 stakeholders, no 51). Se registra como **[AMBIGUO]**: posible duplicado de ST-17. Fuente: stk-delta:229.

### 1.2 Perfiles externos (tipo C) — roles de ciudadanía

| # | ID stakeholder | Perfil | Hecho / Inferido | Restricciones de acceso | Fuente |
|---|---------------|--------|------------------|------------------------|--------|
| 1 | ST-19 | `perfil_ciudadano_natural` — Ciudadano persona natural (identificado/anónimo) | HECHO | Acceso público anónimo a contenidos; acceso autenticado (niveles B/M/A/MA) para trámites y PQRSD identificadas | stk:34 |
| 2 | ST-20 | `perfil_discapacidad` — Persona con discapacidad (visual/auditiva/motriz/cognitiva/fotosensible) | HECHO | Acceso idéntico al ciudadano natural; requisito de accesibilidad WCAG 2.1 AA para todos los componentes | stk:35 |
| 3 | ST-21 | `perfil_adulto_mayor` — Adultos mayores / baja alfabetización / baja conectividad | HECHO | Acceso presencial asistido con equipos en sede física; canal digital opcional, presencial obligatorio | stk:36 |
| 4 | ST-22 | `perfil_grupos_interes` — Grupos de interés (NNA, mujeres, étnicas, LGBTIQ+) | HECHO | Micrositios específicos; lenguaje claro; [AMBIGUO] caracterización sin definir | stk:37 |
| 5 | ST-23 | `perfil_turista` — Turistas nacionales y extranjeros | **[INFERIDO]** | Acceso anónimo a contenidos; sin requisitos diferenciados en sistema [INFERIDO] | stk:38 |
| 6 | ST-24 | `perfil_persona_juridica` — Persona jurídica (NIT) | HECHO | Autenticación por NIT + dígito verificador; validación inline del DV; trámites en nombre de la entidad | stk:39 · hu:207–209 |
| 7 | ST-25 | `perfil_apoderado` — Abogado / apoderado (trámites jurisdiccionales) | HECHO | Opera en nombre del representado; sin restricciones adicionales documentadas [AMBIGUO] | stk:40 |
| 8 | ST-44-D | `perfil_rep_legal_menor` — Representante legal de menor (titular NNA) | **[INFERIDO]** | Otorga autorización explícita antes de que el sistema recoja datos de menores de 18 | stk:59 · hu:416–420 |

**Total perfiles externos: 8**

### 1.3 Roles reguladores / sistemas externos (tipo R / S) — sin permisos en el CMS pero con impacto en reglas de negocio

| ID | Actor | Tipo | Impacto en el modelo de datos |
|----|-------|------|-------------------------------|
| ST-26 | MinTIC / Dirección de Gobierno Digital | R | Define estándares GOV.CO, Kit UI, WCAG; valida integración 7 pasos |
| ST-27 | AND — Agencia Nacional Digital | R/E | Articulador SCD; única autorizada para X-Road; negocia matriz nivel-auth por trámite |
| ST-28 | DAFP — Función Pública (SUIT/SIGEP) | R | Fuente maestra del catálogo de trámites (SUIT) y del directorio (SIGEP) |
| ST-29 | AGN — Archivo General de la Nación | R | Regula TRD; define condiciones de conservación/eliminación |
| ST-30 | SIC — Superintendencia de Industria y Comercio | R | Supervisa tratamiento de datos personales; exige RNBD; habilita transferencias al exterior |
| ST-31 | CSIRT-Gobierno / ColCERT | R | Receptor de incidentes ≤24h |
| ST-33 | Registraduría (ANI/SIRC/ABIS) | S/R | Valida identidad por interoperabilidad |
| ST-34 | GSE — Gestión de Seguridad Electrónica (TSA) | P/S | Proveedor TSA que reemplaza Certicámara (migración en curso) |
| ST-35 | CA acreditada ante ONAC | P/S | Provee certificados digitales |
| ST-40 | Sistemas del Estado integrados (SUIT, SECOP, SIGEP, SGDEA/Orfeo, CCD, SAMI, SUCOP, KOGUI, RUNT/RUAF/RUT, ANI, SUIN) | S | Fuentes de datos para interoperabilidad; no tienen roles en el CMS |

**Fuente:** stk:41–65 · matriz:§Módulo 10

---

## 2. Entidades candidatas derivadas de HU

Convención: `[CANDIDATA-COMPARTIDA]` = la entidad aparece en más de un módulo y debe centralizarse en el modelo físico.

| # | Entidad candidata | HU fuente (ID) | Módulo | ¿CANDIDATA-COMPARTIDA? |
|---|------------------|----------------|--------|------------------------|
| 1 | `ConsentimientoCookie` | HU-01-D01 | 01 | No (solo M01) |
| 2 | `VersionPoliticaCookies` | HU-01-D01 | 01 | No |
| 3 | `ItemMenu` | HU-01-D02 | 01 | No |
| 4 | `CarruselItem` | HU-01-D02 | 01 | [AMBIGUO] puede colapsar con `ContenidoCMS` |
| 5 | `Noticia` | HU-01-D02 | 01 | [AMBIGUO] puede colapsar con `ContenidoCMS` |
| 6 | `ListaBlancaDominio` | HU-01-D03 | 01 | No |
| 7 | `DocumentoTransparencia` | HU-02-D01 | 02 | No |
| 8 | `VersionDocumentoTransparencia` | HU-02-D01 | 02 | No (historial inmutable) |
| 9 | `AlertaVencimientoPublicacion` | HU-02-D02 | 02 | [CANDIDATA-COMPARTIDA] aplica también a datasets (M11) |
| 10 | `SyncSIGEP` | HU-02-D04 | 02 | No |
| 11 | `ServicioTramite` | HU-03-D12 (referencia al catálogo SUIT) | 03 | [CANDIDATA-COMPARTIDA] alimenta M04, M06, M08 |
| 12 | `Tramite` | HU-03-D01, D02, D04, D06, D07, D08, D09, D10, D11, D13 | 03 | [CANDIDATA-COMPARTIDA] central del dominio; referenciada en M04, M09, M10, M12 |
| 13 | `PagoTransaccion` | HU-03-D01, D02, D03 | 03 | No |
| 14 | `BorradorTramite` | HU-03-D04 | 03 | No (retención 30 días, luego purga) |
| 15 | `AdjuntoTramite` | HU-03-D05 | 03 | [CANDIDATA-COMPARTIDA] vs `AdjuntoPQRSD` — ver C-06 |
| 16 | `Subsanacion` | HU-03-D06, D07 | 03 | No |
| 17 | `Desistimiento` | HU-03-D08 | 03 | No |
| 18 | `SilencioAdministrativoPositivo` | HU-03-D09 | 03 | No |
| 19 | `NivelAuthTramite` | HU-03-D11 | 03 | [CANDIDATA-COMPARTIDA] matriz trámite↔nivel de autenticación |
| 20 | `Radicado` | HU-03-D13, HU-04-D01..D05 | 03/04 | [CANDIDATA-COMPARTIDA] central del dominio PQRSD y Trámites |
| 21 | `PQRSD` | HU-04-D01..D08 | 04 | [CANDIDATA-COMPARTIDA] referenciada en M09, M12 |
| 22 | `Traslado` | HU-04-D01 | 04 | No |
| 23 | `Prorroga` | HU-04-D04 | 04 | No |
| 24 | `NotificacionElectronica` | HU-04-D05, HU-12-D03 | 04/12 | [CANDIDATA-COMPARTIDA] misma entidad en ambos módulos |
| 25 | `ConsultaParticipacion` | HU-05-D01, D02 | 05 | No |
| 26 | `AporteParticipacion` | HU-05-D01, D02 | 05 | No |
| 27 | `Cita` | HU-06-D01..D04 | 06 | [CANDIDATA-COMPARTIDA] referenciada en M12 (notificaciones) |
| 28 | `FranjaHoraria` | HU-06-D01..D03 | 06 | No |
| 29 | `Cupo` | HU-06-D01..D03 | 06 | No (parte de `FranjaHoraria`) [INFERIDO] |
| 30 | `ContenidoCMS` | HU-07-D01, HU-12-D01, D02, D05 | 07/12 | [CANDIDATA-COMPARTIDA] flujo editorial central |
| 31 | `VersionContenidoCMS` | HU-12-D05 | 12 | No (historial editorial) |
| 32 | `FlujoCMS` / `EstadoContenido` | HU-12-D01 | 12 | No (estados: Borrador→Pendiente→Publicado→Archivado) |
| 33 | `RondaSUS` | HU-08-D01, D02 | 08 | No |
| 34 | `ResultadoSUS` | HU-08-D02 | 08 | No |
| 35 | `LogAuditoria` | HU-09-D06 | 09 | [CANDIDATA-COMPARTIDA] transversal a todos los módulos |
| 36 | `TokenRecuperacion` | HU-09-D02 | 09 | No |
| 37 | `Dataset` | HU-11-D01, D02 | 11 | No |
| 38 | `VersionDataset` | HU-11-D01 | 11 | No (historial de datasets) |
| 39 | `Usuario` | HU-12-D02, HU-09-D02 | 12/09 | [CANDIDATA-COMPARTIDA] central del RBAC |
| 40 | `Rol` | HU-12-D02, HU-09-D03 | 12/09 | [CANDIDATA-COMPARTIDA] central del RBAC |
| 41 | `CalendarioHabil` | HU-04-D03 (plazos diferenciados) | 04/TX | [CANDIDATA-COMPARTIDA] fuente única para cómputo de plazos (RN-TX-D01) |
| 42 | `FestivoDistrito` | HU-04-D03 | TX | No (tabla de festivos del Distrito) |
| 43 | `ExpedienteSGDEA` | referencia en RF-B1-092, RF-B1-036, RF-B1-075 | 03/04/12 | [CANDIDATA-COMPARTIDA] representa expediente en Orfeo |
| 44 | `SolicitudARCO` | RF-B2-070 (ARCO plazos) / HU-04-D08 implícito | 04/09 | [CANDIDATA-COMPARTIDA] módulos 04 y 09 |
| 45 | `CertificadoDigital` | HU-10-D03 | 10 | No |

**Total entidades candidatas: 45**

---

## 3. Atributos / Campos derivados de HU

Convención: los campos marcados con `*` son obligatorios (NOT NULL) conforme a los criterios G/W/T de la HU fuente.

| # | Campo | Entidad | Tipo / Dominio | HU fuente | Restricciones / Reglas de negocio |
|---|-------|---------|----------------|-----------|-----------------------------------|
| 1 | `version_politica`* | ConsentimientoCookie | VARCHAR / semver | HU-01-D01 | Versionado; banner reaparece cuando cambia |
| 2 | `timestamp_consentimiento`* | ConsentimientoCookie | TIMESTAMPTZ | HU-01-D01 | Hora Legal INM; no reloj local (RN-TX-D02) |
| 3 | `consentimiento_caducado` | ConsentimientoCookie | BOOLEAN | HU-01-D01 | TRUE si >12 meses o si política cambió |
| 4 | `cookies_esenciales_activas` | ConsentimientoCookie | BOOLEAN | HU-01-D01 | DEFAULT TRUE; no desactivables |
| 5 | `cookies_no_esenciales_activas` | ConsentimientoCookie | BOOLEAN | HU-01-D01 | DEFAULT FALSE (RN-B1-008); se activa solo con consentimiento expreso |
| 6 | `orden`* | ItemMenu | SMALLINT | HU-01-D02 | Valor entre 1–7 (RN-01-D02: ≤7 ítems) |
| 7 | `nivel`* | ItemMenu | SMALLINT | HU-01-D02 | CHECK (nivel IN (1,2)); no se permite nivel 3 |
| 8 | `titulo`* | ItemMenu | VARCHAR(120) | HU-01-D02 | NOT NULL |
| 9 | `publicado_en` | ItemMenu | TIMESTAMPTZ | HU-01-D02 | Log de cambio |
| 10 | `dominio`* | ListaBlancaDominio | VARCHAR(255) | HU-01-D03 | Sin modal de aviso si está en la lista |
| 11 | `url_fuente_unica`* | DocumentoTransparencia | VARCHAR(2048) | HU-02-D01 | Invariante; reemplazos conservan la URL (RN-02-D01) |
| 12 | `version_numero`* | VersionDocumentoTransparencia | INTEGER | HU-02-D01 | Secuencia inmutable |
| 13 | `fecha_publicacion`* | VersionDocumentoTransparencia | DATE | HU-02-D01 | NOT NULL |
| 14 | `activa`* | VersionDocumentoTransparencia | BOOLEAN | HU-02-D01 | Una sola versión activa por documento |
| 15 | `plazo_legal`* | AlertaVencimientoPublicacion | DATE | HU-02-D02 | Fecha límite según Res.1519 Anexo 2 |
| 16 | `dias_alerta_previo`* | AlertaVencimientoPublicacion | SMALLINT | HU-02-D02 | Configurable; default 10 días |
| 17 | `estado_cumplimiento`* | AlertaVencimientoPublicacion | ENUM('pendiente','cumplido','incumplido') | HU-02-D02 | Calculado automáticamente |
| 18 | `fecha_ultima_sync`* | SyncSIGEP | TIMESTAMPTZ | HU-02-D04 | Alerta si >24h desde último éxito |
| 19 | `sync_exitosa` | SyncSIGEP | BOOLEAN | HU-02-D04 | FALSE dispara alerta |
| 20 | `clave_idempotencia`* | PagoTransaccion | UUID | HU-03-D01 | UNIQUE; previene doble cargo |
| 21 | `estado_pago`* | PagoTransaccion | ENUM('pendiente','aprobado','rechazado','reembolsado') | HU-03-D01/D02 | Solo 'aprobado' avanza el trámite (RN-03-D02) |
| 22 | `monto`* | PagoTransaccion | NUMERIC(14,2) | HU-03-D03 | CHECK (monto >= 0); sin recargo de pasarela (Dec.2106 Art.7) |
| 23 | `paso_guardado`* | BorradorTramite | SMALLINT | HU-03-D04 | Paso del formulario multipaso donde se guardó |
| 24 | `expira_en`* | BorradorTramite | TIMESTAMPTZ | HU-03-D04 | NOW() + INTERVAL '30 days' (RNF-03-D02 · §8 #10) |
| 25 | `adjuntos_json` | BorradorTramite | JSONB | HU-03-D04 | Referencias a adjuntos guardados |
| 26 | `ficha_version_guardado` | BorradorTramite | INTEGER | HU-03-D04 | Detecta si la ficha del trámite cambió desde el guardado |
| 27 | `mime_real`* | AdjuntoTramite | VARCHAR(128) | HU-03-D05 | Verificado por contenido, no por extensión |
| 28 | `resultado_antivirus`* | AdjuntoTramite | ENUM('pendiente','limpio','infectado') | HU-03-D05 | DEFAULT 'pendiente'; 'infectado' → rechazo y log |
| 29 | `detalle_subsanacion`* | Subsanacion | TEXT | HU-03-D07 | NOT NULL; motivación obligatoria (no se permite vacío) |
| 30 | `plazo_subsanacion`* | Subsanacion | DATE | HU-03-D07 | NOT NULL |
| 31 | `estado_tramite_antes`* | Desistimiento | VARCHAR(50) | HU-03-D08 | Para validar que no estaba ya resuelto |
| 32 | `confirmacion_doble`* | Desistimiento | BOOLEAN | HU-03-D08 | DEFAULT FALSE; requiere confirmación explícita |
| 33 | `efecto_sap` | SilencioAdministrativoPositivo | ENUM('no_aplica','concedido','escalado') | HU-03-D09 | DEFAULT 'no_aplica'; 'concedido' solo cuando hay SAP (RN-03-D08) |
| 34 | `fecha_vencimiento_termino`* | SilencioAdministrativoPositivo | TIMESTAMPTZ | HU-03-D09 | Computado sobre CalendarioHabil |
| 35 | `nivel_auth_requerido`* | NivelAuthTramite | ENUM('basico','medio','alto','muy_alto') | HU-03-D11 | Bloquea inicio si nivel del ciudadano < requerido (RN-TX-D04) |
| 36 | `numero_radicado`* | Radicado | VARCHAR(30) | HU-03-D13 | UNIQUE; formato: prefijo_dependencia+año+consecutivo_atómico (ej. SM-CAT-2026-000123) · §8 #11 |
| 37 | `consecutivo`* | Radicado | BIGINT | HU-03-D13 | Asignado con secuencia atómica (SEQUENCE PostgreSQL); sin duplicados bajo concurrencia |
| 38 | `canal_recepcion`* | PQRSD | ENUM('web','presencial','correo','telefono','otro') | HU-04-D01 | NOT NULL |
| 39 | `tipo_pqrsd`* | PQRSD | ENUM('peticion','queja','reclamo','sugerencia','denuncia','consulta') | HU-04-D03 | Determina el plazo legal diferenciado |
| 40 | `identidad_reservada`* | PQRSD | BOOLEAN | HU-04-D07 | DEFAULT FALSE; TRUE → oculta datos del peticionario en back-office (Ley 190/1995 Art.38) |
| 41 | `plazo_dias_habiles`* | PQRSD | SMALLINT | HU-04-D03 | Derivado de `tipo_pqrsd`: petición=15, consulta=30, reclamo=15, queja=15 (Ley 1755 Art.14) |
| 42 | `fecha_vencimiento`* | PQRSD | DATE | HU-04-D03 | Calculada sobre CalendarioHabil |
| 43 | `cumplido_en_plazo` | PQRSD | BOOLEAN | HU-04-D02 | Calculado al cerrar |
| 44 | `entidad_receptora` | Traslado | VARCHAR(255) | HU-04-D01 | Nombre y/o código de la entidad a la que se traslada |
| 45 | `fecha_traslado`* | Traslado | TIMESTAMPTZ | HU-04-D01 | Dentro de los 5 días de recepción (RN-04-D03) |
| 46 | `nueva_fecha_vencimiento`* | Prorroga | DATE | HU-04-D04 | CHECK: no excede el máximo legal |
| 47 | `motivacion`* | Prorroga | TEXT | HU-04-D04 | NOT NULL |
| 48 | `canal_notificacion`* | NotificacionElectronica | ENUM('correo','sms','push','fisico','aviso') | HU-04-D05 | Conforme Ley 1437 Arts.56,67,69 |
| 49 | `acuse_enviado`* | NotificacionElectronica | BOOLEAN | HU-04-D05 | DEFAULT FALSE; FALSE → pendiente de entrega |
| 50 | `fecha_notificacion` | NotificacionElectronica | TIMESTAMPTZ | HU-04-D05 | Fecha en que se confirma la entrega |
| 51 | `intentos_reintento` | NotificacionElectronica | SMALLINT | HU-12-D03 | Contador de reintentos antes de canal alterno |
| 52 | `estado_consulta`* | ConsultaParticipacion | ENUM('abierta','cerrada','archivada') | HU-05-D01 | Solo 'abierta' admite aportes |
| 53 | `fecha_cierre`* | ConsultaParticipacion | TIMESTAMPTZ | HU-05-D01 | Cierre automático por vencimiento |
| 54 | `resultado_publicado` | ConsultaParticipacion | BOOLEAN | HU-05-D02 | DEFAULT FALSE; TRUE cuando se publica el consolidado |
| 55 | `servicio_agendable`* | FranjaHoraria | VARCHAR(100) | HU-06-D01 | Nombre del servicio que se puede agendar |
| 56 | `cupos_disponibles`* | FranjaHoraria | SMALLINT | HU-06-D01 | CHECK (cupos_disponibles >= 0) |
| 57 | `inicio`* | FranjaHoraria | TIME | HU-06-D01 | NOT NULL |
| 58 | `fin`* | FranjaHoraria | TIME | HU-06-D01 | NOT NULL |
| 59 | `estado_cita`* | Cita | ENUM('reservada','confirmada','reprogramada','cancelada','no_show','atendida') | HU-06-D02/D03/D04 | Transiciones: reservada→confirmada→atendida/no_show |
| 60 | `codigo_cita`* | Cita | UUID | HU-06-D02 | UNIQUE; entregado al ciudadano para gestión |
| 61 | `recordatorio_enviado` | Cita | BOOLEAN | HU-06-D04 | DEFAULT FALSE |
| 62 | `subtitulos_srt`* | ContenidoCMS | BOOLEAN | HU-07-D01 | DEFAULT FALSE; TRUE requerido para video (RF-07-D01) |
| 63 | `lsc_disponible` | ContenidoCMS | BOOLEAN | HU-07-D01 | Requerido para rendición de cuentas (Res.1519 Anexo 1 1.5) |
| 64 | `estado_editorial`* | ContenidoCMS | ENUM('borrador','pendiente','publicado','archivado') | HU-12-D01 | Flujo: borrador→pendiente→publicado→archivado; rechazo→borrador |
| 65 | `creado_por`* | ContenidoCMS | FK → Usuario | HU-12-D01 | NOT NULL |
| 66 | `aprobado_por` | ContenidoCMS | FK → Usuario | HU-12-D01 | SoD: debe ser DISTINTO de `creado_por` (CHECK) |
| 67 | `version_contenido`* | VersionContenidoCMS | INTEGER | HU-12-D05 | Secuencia por contenido |
| 68 | `bloqueado_por` | VersionContenidoCMS | FK → Usuario | HU-12-D05 | Bloqueo optimista/pesimista de edición concurrente |
| 69 | `bloqueado_desde` | VersionContenidoCMS | TIMESTAMPTZ | HU-12-D05 | Usado para expirar el bloqueo [INFERIDO] |
| 70 | `fecha_ronda`* | RondaSUS | DATE | HU-08-D01 | NOT NULL |
| 71 | `puntaje_sus`* | ResultadoSUS | NUMERIC(5,2) | HU-08-D01 | CHECK (puntaje_sus >= 0 AND puntaje_sus <= 100) |
| 72 | `tasa_exito`* | ResultadoSUS | NUMERIC(5,4) | HU-08-D01 | CHECK (tasa_exito >= 0 AND tasa_exito <= 1); umbral: ≥0.90 (§8 #14) |
| 73 | `cumple_umbral`* | ResultadoSUS | BOOLEAN | HU-08-D01 | TRUE si puntaje_sus ≥68 AND tasa_exito ≥ umbral |
| 74 | `accion`* | LogAuditoria | VARCHAR(100) | HU-09-D06 | NOT NULL; ej. 'LOGIN', 'EDITAR_CONTENIDO', 'APROBAR_CONTENIDO' |
| 75 | `entidad_afectada`* | LogAuditoria | VARCHAR(100) | HU-09-D06 | NOT NULL; nombre de la tabla afectada |
| 76 | `id_entidad_afectada`* | LogAuditoria | VARCHAR(50) | HU-09-D06 | NOT NULL; ID del registro afectado |
| 77 | `usuario_id` | LogAuditoria | FK → Usuario (nullable) | HU-09-D06 | NULL para eventos anónimos |
| 78 | `hash_encadenado`* | LogAuditoria | BYTEA | HU-09-D06 | Hash del registro anterior + datos actuales; append-only (RNF-09-D01) |
| 79 | `retencion_hasta` | LogAuditoria | DATE | HU-09-D06 | Retención ≥5 años (RF-B1-065) |
| 80 | `token`* | TokenRecuperacion | VARCHAR(128) | HU-09-D02 | UNIQUE; de un solo uso; expira en 15 min |
| 81 | `usado` | TokenRecuperacion | BOOLEAN | HU-09-D02 | DEFAULT FALSE; TRUE después del primer uso |
| 82 | `expira_en`* | TokenRecuperacion | TIMESTAMPTZ | HU-09-D02 | NOW() + INTERVAL '15 minutes' |
| 83 | `nombre`* | Dataset | VARCHAR(255) | HU-11-D01 | NOT NULL |
| 84 | `frecuencia_actualizacion`* | Dataset | ENUM('diaria','semanal','mensual','trimestral','anual','sin_definir') | HU-11-D01 | Determina el umbral de alerta de frescura |
| 85 | `fecha_ultima_actualizacion`* | Dataset | DATE | HU-11-D01 | Actualizada en cada versión nueva |
| 86 | `desactualizado`* | Dataset | BOOLEAN | HU-11-D01 | TRUE si superó el período sin actualizar (ej. mensual: 35 días) |
| 87 | `federado_a_datos_gov`* | Dataset | BOOLEAN | HU-11-D02 | DEFAULT FALSE; TRUE solo tras validación de calidad |
| 88 | `version_dataset`* | VersionDataset | INTEGER | HU-11-D01 | Secuencia por dataset |
| 89 | `formato`* | VersionDataset | ENUM('CSV','JSON','XML','RDF','RSS','ODF','WMS','WFS') | HU-11-D02 | Conforme RNF-B1-036 |
| 90 | `metadatos_completos`* | VersionDataset | BOOLEAN | HU-11-D02 | FALSE → impide federación |
| 91 | `nombre_usuario`* | Usuario | VARCHAR(100) | HU-12-D02 | UNIQUE |
| 92 | `activo`* | Usuario | BOOLEAN | HU-12-D02 | DEFAULT TRUE; FALSE revoca accesos ≤1 día hábil |
| 93 | `fecha_baja` | Usuario | TIMESTAMPTZ | HU-12-D02 | NULL si activo; NOT NULL al dar de baja |
| 94 | `mfa_habilitado`* | Usuario | BOOLEAN | HU-09-D05 | DEFAULT FALSE; TRUE obligatorio para roles internos CMS (RN-09-D04 · C-07) |
| 95 | `fecha_nacimiento` | Usuario | DATE | HU-12-D04 | NULL para usuarios anónimos; si <18 años → exige representante legal (Ley 1581 Art.7) |
| 96 | `nombre_rol`* | Rol | VARCHAR(80) | HU-09-D03 | UNIQUE |
| 97 | `puede_crear` | Rol | BOOLEAN | HU-09-D03 | Permiso de creación de contenidos |
| 98 | `puede_aprobar` | Rol | BOOLEAN | HU-09-D03 | SoD: si TRUE, no puede coincidir con el mismo usuario que creó |
| 99 | `es_rol_cms_interno` | Rol | BOOLEAN | HU-09-D05 | TRUE → MFA obligatorio |
| 100 | `dias_habiles`* | CalendarioHabil | INTEGER | HU-04-D03 | Precomputado para el año en curso |
| 101 | `fecha`* | FestivoDistrito | DATE | HU-04-D03 | PK; festivo declarado por el Distrito |
| 102 | `descripcion`* | FestivoDistrito | VARCHAR(200) | HU-04-D03 | NOT NULL |
| 103 | `numero_expediente`* | ExpedienteSGDEA | VARCHAR(50) | RF-B1-092 | UNIQUE; corresponde al código en Orfeo |
| 104 | `estado_expediente`* | ExpedienteSGDEA | ENUM('activo','cerrado','transferido','eliminado_trd') | RF-B1-075 | — |
| 105 | `tipo_arco`* | SolicitudARCO | ENUM('acceso','rectificacion','cancelacion','oposicion') | RF-B2-070 | NOT NULL |
| 106 | `plazo_atencion_dias`* | SolicitudARCO | SMALLINT | RF-B2-070 | CHECK plazo ≤15 días hábiles (Ley 1581 Art.14) |
| 107 | `vencimiento`* | CertificadoDigital | DATE | HU-10-D03 | Alertas N días antes |
| 108 | `tipo_certificado`* | CertificadoDigital | ENUM('ONAC','TLS','OCSP','firma_electronica') | HU-10-D03 | NOT NULL |
| 109 | `proveedor`* | CertificadoDigital | VARCHAR(100) | HU-10-D03 | ej. 'GSE', 'CA-ONAC', 'Certicámara' |

**Total atributos / campos: 109**

---

## 4. Índice de trazabilidad requisito → módulo

### 4.1 Resumen por módulo (tabla maestra)

Fuente: matriz-trazabilidad.md:400–415

| Módulo | RF (base) | RF-D | RNF (agrupados) | RNF-D | RN (agrupados) | RN-D | UC | HU | HU-D | Conflictos/pendientes |
|--------|-----------|------|-----------------|-------|----------------|------|----|----|------|----------------------|
| **01** Estructura/Identidad | 33 | 3 | 4 grupos | 1 | 10 | 3 | 1 + UC-048 | 2 | 4 | 6 |
| **02** Transparencia | 15 | 3 | 3 | 1 | 8 | 4 | 2 + UC-050 | 6 | 4 | 1 |
| **03** Servicios/Trámites | 22 | 7 | 3 | 2 | 8 | 8 | 6 + UC-042/043/044/045 | 5 | 13 | 5 (incl. C-06) |
| **04** PQRSD | 10 | 5 | 3 | 2 | 5 | 8 | 3 + UC-046 | 5 | 8 | 6 |
| **05** Participa | 3 | 2 | 2 | 0 | 2 | 2 | 1 | 3 | 2 | 2 |
| **06** Canales | 3 | 4 | 2 | 0 | 2 | 3 | 1 + UC-047 | 3 | 4 | 1 |
| **07** Accesibilidad | 6 grupos (52 CC) | 1 | 8 | 1 | 4 | 2 | 4 | 5 | 3 | 2 |
| **08** Usabilidad | 8 grupos | 1 | 9 | 1 | 3 | 1 | 2 | 6 | 2 | 2 |
| **09** Seguridad | 22 | 4 | 14 | 3 | 8 | 7 | 5 | 5 | 7 | 6 (incl. C-07) |
| **10** Interoperabilidad | 17 | 2 | 9 | 1 | 9 | 3 | 4 | 5 | 3 | 4 |
| **11** Datos Abiertos | 4 | 1 | 2 | 1 | 3 | 2 | 2 + UC-049 | 2 | 2 | 1 |
| **12** Gestión Contenidos | 10 | 4 | 5 | 1 | 7 | 6 | 4 + UC-048 | 7 | 5 | 2 |
| **TX** Transversal | — | — | — | 4 | — | 5 | — | — | — | 3 (incl. C-08) |
| **TOTAL** | ~155 RF | +37 RF-D | ~64 RNF | +18 RNF-D | ~69 RN | +54 RN-D | 50 UC (41 base + 9 delta) | 54 HU | +57 HU-D | 41 |

> Corpus total declarado: 691 ítems. Los RF base se trazan fila por fila; RNF/RN se agrupan por temática conservando sus IDs explícitos.

### 4.2 Mapa RF-D / HU-D → módulo (delta de profundización)

Todos los IDs con sufijo `-D` proceden del delta integrado el 2026-06-04.

| ID | Tipo | Módulo | Entidad(es) candidata(s) | Stakeholder principal |
|----|------|--------|--------------------------|----------------------|
| RF-01-D01 | RF-D | 01 | ConsentimientoCookie, VersionPoliticaCookies | Ciudadano / ST-04 |
| RF-01-D02 | RF-D | 01 | ItemMenu, CarruselItem, Noticia | ST-42-D (editor) |
| RF-01-D03 | RF-D | 01 | ListaBlancaDominio | ST-03 (admin) |
| RNF-01-D01 | RNF-D | 01 | (infraestructura, sin tabla) | ST-02 / ST-16 |
| RF-02-D01 | RF-D | 02 | DocumentoTransparencia, VersionDocumentoTransparencia | ST-42-D, ST-45-D |
| RF-02-D02 | RF-D | 02 | AlertaVencimientoPublicacion | ST-45-D |
| RF-02-D03 | RF-D | 02 | (integración externa, sin tabla nueva) | Ciudadano / ST-06 |
| RNF-02-D01 | RNF-D | 02 | SyncSIGEP | ST-03 |
| RF-03-D01 | RF-D | 03 | PagoTransaccion, Tramite | Ciudadano |
| RF-03-D02 | RF-D | 03 | PagoTransaccion, Tramite | Ciudadano |
| RF-03-D03 | RF-D | 03 | BorradorTramite | Ciudadano / ST-04 |
| RF-03-D04 | RF-D | 03 | AdjuntoTramite | Ciudadano / ST-05 |
| RF-03-D05 | RF-D | 03 | Subsanacion, Tramite | ST-07 / Ciudadano |
| RF-03-D06 | RF-D | 03 | Tramite (fallback) | Ciudadano / ST-06 |
| RF-03-D07 | RF-D | 03 | Desistimiento, Tramite | Ciudadano / ST-47-D |
| RNF-03-D01 | RNF-D | 03 | ServicioTramite (rendimiento) | Ciudadano |
| RNF-03-D02 | RNF-D | 03 | BorradorTramite (retención 30 días) | ST-04 |
| RF-04-D01 | RF-D | 04 | PQRSD, Traslado | ST-07 |
| RF-04-D02 | RF-D | 04 | PQRSD | ST-07 |
| RF-04-D03 | RF-D | 04 | PQRSD, CalendarioHabil, Prorroga | ST-07 |
| RF-04-D04 | RF-D | 04 | PQRSD (validación campos) | Ciudadano |
| RF-04-D05 | RF-D | 04 | NotificacionElectronica | ST-07 |
| RNF-04-D01 | RNF-D | 04 | Radicado (formato) | ST-49-D |
| RNF-04-D02 | RNF-D | 04 | Radicado (concurrencia) | ST-49-D / ST-07 |
| RF-05-D01 | RF-D | 05 | ConsultaParticipacion | ST-50-D |
| RF-05-D02 | RF-D | 05 | ConsultaParticipacion, AporteParticipacion | Ciudadano / ST-50-D |
| RF-06-D01 | RF-D | 06 | FranjaHoraria, Cita | ST-41-D |
| RF-06-D02 | RF-D | 06 | Cita, FranjaHoraria | Ciudadano |
| RF-06-D03 | RF-D | 06 | Cita, FranjaHoraria | Ciudadano |
| RF-06-D04 | RF-D | 06 | Cita (no-show) | ST-41-D |
| RF-07-D01 | RF-D | 07 | ContenidoCMS (multimedia) | ST-42-D / ST-12 |
| RNF-07-D01 | RNF-D | 07 | (accesibilidad, sin tabla) | ST-20 / ST-12 |
| RF-08-D01 | RF-D | 08 | RondaSUS, ResultadoSUS | ST-14 / ST-11 |
| RNF-08-D01 | RNF-D | 08 | ResultadoSUS (exportación) | ST-14 |
| RF-09-D01 | RF-D | 09 | LogAuditoria (anti-IDOR) | Ciudadano / ST-05 |
| RF-09-D02 | RF-D | 09 | TokenRecuperacion, Usuario | Usuario interno |
| RF-09-D03 | RF-D | 09 | Rol, Usuario, ContenidoCMS (SoD) | ST-42-D / ST-43-D |
| RF-09-D04 | RF-D | 09 | (SCD OIDC, sin tabla nueva) | Ciudadano / ST-06 |
| RNF-09-D01 | RNF-D | 09 | LogAuditoria (inmutabilidad) | ST-05 / auditor |
| RNF-09-D02 | RNF-D | 09 | (rate-limiting, sin tabla) | ST-05 |
| RNF-09-D03 | RNF-D | 09 | (criterio grave, sin tabla) | ST-05 |
| RF-10-D01 | RF-D | 10 | (X-Road, sin tabla nueva) | ST-06 |
| RF-10-D02 | RF-D | 10 | CertificadoDigital (TSA) | ST-06 |
| RNF-10-D01 | RNF-D | 10 | CertificadoDigital | ST-06 |
| RF-11-D01 | RF-D | 11 | Dataset, VersionDataset | ST-03 |
| RNF-11-D01 | RNF-D | 11 | VersionDataset (validación) | ST-03 |
| RF-12-D01 | RF-D | 12 | ContenidoCMS, FlujoCMS | ST-42-D / ST-43-D |
| RF-12-D02 | RF-D | 12 | Usuario (baja segura) | ST-03 |
| RF-12-D03 | RF-D | 12 | NotificacionElectronica (cola) | ST-51-D |
| RF-12-D04 | RF-D | 12 | Usuario (verificación edad) | ST-44-D / ST-04 |
| RNF-12-D01 | RNF-D | 12 | VersionContenidoCMS (concurrencia) | ST-42-D |
| RF-12-D05 | RF-D | 12 | ContenidoCMS (validación ITA) | ST-45-D / ST-03 |
| RNF-TX-D01 | RNF-D | TX | (observabilidad, sin tabla) | ST-02 / ST-16 |
| RNF-TX-D02 | RNF-D | TX | (i18n diferida) | ST-51-D |
| RNF-TX-D03 | RNF-D | TX | (retención/purga, todas las entidades) | ST-04 |
| RNF-TX-D04 | RNF-D | TX | (DRP/BCP, sin tabla) | ST-16 |
| RN-TX-D01 | RN-D | TX | CalendarioHabil, FestivoDistrito | ST-49-D |
| RN-TX-D02 | RN-D | TX | LogAuditoria (Hora Legal) | ST-06 |
| RN-TX-D03 | RN-D | TX | BorradorTramite, Cita, LogAuditoria (retención) | ST-04 |
| RN-TX-D04 | RN-D | TX | NivelAuthTramite, Tramite | ST-02 / ST-27 |
| RN-TX-D05 | RN-D | TX | ContenidoCMS (fallback castellano) | ST-51-D |

### 4.3 Conflictos y ambigüedades activos (impacto en diseño BD)

| Código | Estado | Impacto en diseño BD |
|--------|--------|----------------------|
| C-01 (adjuntos PQRSD sin límite vs 10MB) | En conflicto ALTA | `AdjuntoPQRSD` no debe tener CHECK de tamaño pero sí columna de resultado de antivirus |
| C-02 (disponibilidad ≥95% vs ≥98%) | En conflicto ALTA | Sin impacto directo en tablas; afecta SLA del motor |
| C-03 (SCD 99.98 vs 99.982%) | En conflicto MEDIA | Sin impacto en tablas |
| C-04 (ubicación formulario PQRSD) | En conflicto ALTA | Sin impacto en tablas |
| C-06 (adjuntos PQRSD vs trámites) | **Resuelto 2026-06-05** | Separar `AdjuntoPQRSD` (sin validación de tipo/tamaño estricta, solo antivirus) de `AdjuntoTramite` (validación MIME + tamaño + antivirus). Son dos entidades distintas. |
| C-07 (MFA admins CMS) | **Resuelto 2026-06-05** | Columna `mfa_habilitado` en `Usuario`; CHECK que roles CMS internos tengan TRUE |
| C-08 (matriz trámite↔nivel auth) | **Resuelto 2026-06-05** | Entidad `NivelAuthTramite` vinculada a `ServicioTramite`; matriz concreta G-CIO+AND (detalle operativo) |
| memberClass | En conflicto MEDIA | Sin impacto en tablas |
| HPKP | En conflicto MEDIA | Sin impacto en tablas |
| SGDEA | **Resuelto 2026-06-05** | SGDEA = Orfeo; `ExpedienteSGDEA` mapea al código interno de Orfeo |
| ST-17/ST-48-D | **[AMBIGUO]** | Si se fusionan, `rol_lider_tramites` absorbe ST-48-D; no duplicar rol en RBAC |

### 4.4 Decisiones cerradas del 2026-06-05 (bloquean diseño BD)

| # | Decisión | RF/RN/HU | Impacto en modelo |
|---|----------|-----------|-------------------|
| 1 | Retención de borradores: **30 días**, luego purga/anonimización | RNF-03-D02 / RN-TX-D03 / HU-03-D04 | `BorradorTramite.expira_en = created_at + INTERVAL '30 days'` |
| 2 | Reembolso al desistir: **solo si no inició la gestión** | RF-03-D07 / UC-043 E2 / HU-03-D08 | Campo `estado_tramite_antes` en `Desistimiento` para validar la condición |
| 3 | Silencio administrativo: **negativo por defecto** (CPACA Art.83); SAP solo por norma especial | RN-03-D08 / UC-044 / HU-03-D09 | `SilencioAdministrativoPositivo.efecto_sap` DEFAULT 'no_aplica' |
| 4 | Nivel auth por trámite: **matriz por riesgo**; bloquea inicio si nivel insuficiente | C-08 / RN-TX-D04 / HU-03-D11 | Entidad `NivelAuthTramite`; FK en `ServicioTramite` |
| 5 | Agenda: **propia en la sede** (no sistema de turnos externo) | RF-06-D01 / HU-06-D01 | Entidades `FranjaHoraria` y `Cita` dentro del modelo |
| 6 | Usabilidad: **SUS ≥68 + tasa de éxito ≥90%** | RF-08-D01 / RN-08-D01 / HU-08-D01 | `ResultadoSUS.tasa_exito` CHECK ≥0 AND ≤1; umbral 0.90 |
| 7 | Aportes Participa: **CRUD propio dentro de la sede**; consultas normativas DNP via redirección SUCOP | RF-05-D01/D02 | Entidades `ConsultaParticipacion` y `AporteParticipacion` propias |
| 8 | Traducción: **castellano por ahora**; lenguas étnicas como compromiso futuro (Could) | RNF-TX-D02 / RN-TX-D05 | Diferir columna `idioma` en `ContenidoCMS`; definir como extensión futura |
| 9 | Formato radicado: **prefijo dependencia + año + consecutivo atómico** (ej. SM-CAT-2026-000123) | RNF-04-D01 / HU-03-D13 | `Radicado.numero_radicado` VARCHAR(30) UNIQUE; SEQUENCE PostgreSQL para el consecutivo |

### 4.5 Huecos de trazabilidad persistentes (para el auditor)

| ID | Descripción | Responsable | Estado |
|----|-------------|-------------|--------|
| RNF-09-D03 | Umbral de incidente "grave" para CSIRT ≤24h — solo motiva pregunta abierta A-06; sin UC/HU propio | ST-05 | Pendiente |
| RNF-TX-D04 | Pruebas RTO/RPO (DRP/BCP) — sin UC; HU-01-D04 toca observabilidad, no DRP | ST-16 | Pendiente |
| RNF-TX-D02 | Internacionalización — diferida; sin UC | ST-51-D | Diferido (Could) |
| RF-B1-067 | Backups DRP/BCP — sigue sin UC; reforzado por RNF-TX-D04 pero sin caso de uso de "probar restauración" | ST-16 | Pendiente |

---

## 7. Inferencias [INFERIDO]

Las siguientes inferencias se marcan explícitamente. Requieren validación con los stakeholders correspondientes antes de materializar en el DDL.

| # | Inferencia | Evidencia de soporte | Stakeholder validador |
|---|-----------|---------------------|----------------------|
| 1 | `rol_alcalde` no opera el sistema directamente; su rol en BD es ser el responsable del tratamiento (FK en tablas de consentimiento y políticas) | ST-01 tiene "Influencia Alta" pero no aparece como actor en ninguna HU de operación | ST-01 / ST-04 |
| 2 | `rol_g_cio` no tiene rol en el CMS de contenidos; su acceso es a configuración de nivel de autenticación y gobernanza | ST-02 no aparece en HU de CRUD de contenidos | ST-02 |
| 3 | `rol_arquitecto_devops` (ST-16) es el responsable de la entidad `CertificadoDigital` y de observabilidad; no hay HU explícita que le asigne esto | Deducido de su descripción y de HU-10-D03 (equipo técnico) | ST-16 / ST-06 |
| 4 | `Cupo` puede modelarse como columna de `FranjaHoraria` (no entidad separada) o como entidad propia si se necesita rastreo de reservas individuales. HU-06-D01..D03 no especifican si se rastrean cupos individuales. | HU-06-D01 dice "10 cupos de 9-10 am" como cantidad, no como registros individuales | ST-41-D |
| 5 | `CarruselItem` y `Noticia` pueden colapsar en `ContenidoCMS` con un discriminador `tipo_contenido`. HU-01-D02 los trata conjuntamente en el flujo de CRUD del editor. | HU-01-D02: "crear, reordenar y despublicar ítems del menú y noticias del carrusel" bajo el mismo flujo | ST-03 / ST-42-D |
| 6 | `bloqueado_desde` en `VersionContenidoCMS` es necesario para expirar el bloqueo de edición concurrente, pero HU-12-D05 no especifica la duración del timeout de bloqueo | HU-12-D05 menciona el bloqueo optimista/pesimista pero no el TTL | ST-03 / equipo técnico |
| 7 | El rol `perfil_apoderado` (ST-25) opera en nombre del representado para trámites jurisdiccionales, pero no hay HU que defina cómo se acredita la representación en el sistema | ST-25 referenciado en HU-03-D05 implícitamente (adjuntos del trámite) | ST-25 / ST-46-D |
| 8 | `AdjuntoPQRSD` debe existir como entidad separada de `AdjuntoTramite` (C-06 resuelto), pero sus atributos no están completamente cubiertos por las HU de PQRSD. Se infieren por analogía con `AdjuntoTramite` sin el CHECK de MIME/tamaño estricto. | C-06 resuelto el 2026-06-05: "PQRSD: no rechaza por tipo/tamaño pero aplica antivirus + límite técnico alto documentado" | ST-05 / ST-04 |
| 9 | `NivelAuthTramite` puede ser una tabla de referencia simple o puede ser una FK en `ServicioTramite`. La decisión depende de si un trámite puede requerir niveles distintos según el tipo de solicitud. HU-03-D11 no distingue sub-tipos. | HU-03-D11: "un trámite que exige nivel Alto" como invariante del trámite | ST-02 / ST-27 / ST-17 |
| 10 | ST-48-D ("Líder funcional de trámites / PO" del delta) no fue integrado al archivo consolidado de stakeholders (50 st, no 51). El delta recomienda fusionarlo con ST-17. Si no se fusiona, requiere un rol RBAC adicional. | stk-delta:229 · stk-integrado:67 | ST-02 / ST-17 |

---

*Fin del artefacto G4-stakeholders-trazabilidad.md*
*Generado: 2026-06-07 — jose-bd methodology*

# Casos de Uso detallados — Sede Electrónica Alcaldía Distrital de Santa Marta

> Segunda pasada profunda sobre las TABLAS 4 (UC) de `_req_bundle_1/2/3.md`. Los UC originales eran resúmenes de una línea (incumplimiento de la **restricción 9**). Aquí cada UC consolidado trae ficha completa: precondiciones, postcondiciones, flujo principal, flujos alternos, flujos de excepción (matriz "qué pasa si"), RF/RN involucrados, fuente `#NN` y criterios.
> **Convenciones:** `[HECHO]` = respaldado en documentos · `[INFERENCIA]` = derivado, requiere validación. Las contradicciones C-01 (adjuntos PQRSD), C-02 (disponibilidad), captcha vs accesibilidad, RN-B2-004 (interoperabilidad caída) y SCD no disponible se resuelven explícitamente en los flujos de excepción. Ver `../README.md`, `contexto-transversal.md` y `diagramas.md`.

---

## Tabla-índice de UC consolidados

| ID | Nombre | UC fuente (bundles) | Actor principal | ¿Nuevo? |
|----|--------|---------------------|-----------------|---------|
| UC-001 | Radicar PQRSD en línea | UC-B1-001, UC-B2-008, UC-B3-002/003 | Ciudadano (identificado/anónimo) | No |
| UC-002 | Consultar estado de PQRSD / trámite | UC-B1-002, UC-B2-003, UC-B3-007 | Ciudadano | No |
| UC-003 | Buscar y consultar trámite (catálogo) | UC-B1-003, UC-B2-001, UC-B3-001 | Ciudadano | No |
| UC-004 | Realizar trámite en línea con autenticación | UC-B1-004, UC-B2-002, UC-B3-013 | Ciudadano | No |
| UC-005 | Autenticar ciudadano (SCD / niveles) | UC-B2-005, UC-B3-005 | Ciudadano, SCD Autenticación | No |
| UC-006 | Registrarse en la sede | UC-B2-006, UC-B3-004 | Ciudadano | No |
| UC-007 | Pagar un trámite en línea | UC-B3-006 (RF-B1-029) | Ciudadano | No |
| UC-008 | Recibir resultado / notificación | UC-B2-004, UC-B3-014 | Ciudadano, CCD | No |
| UC-009 | Consultar Carpeta Ciudadana Digital | UC-B3-017 | Ciudadano | No |
| UC-010 | Ejercer derechos ARCO | UC-B2-007 | Ciudadano autenticado | No |
| UC-011 | Agendar cita de atención presencial | UC-B1-007, UC-B3-008 | Ciudadano | No |
| UC-012 | Consultar normativa institucional | UC-B1-005 | Ciudadano | No |
| UC-013 | Consultar y descargar datos abiertos | UC-B1-008 | Ciudadano / Investigador | No |
| UC-014 | Participar en consulta ciudadana de norma | UC-B1-009 | Ciudadano | No |
| UC-015 | Buscar información (buscador interno) | UC-B3-018 | Ciudadano | No |
| UC-016 | Gestionar consentimiento de cookies | UC-B2-012 | Ciudadano | No |
| UC-017 | Iniciar sesión administrador del CMS | UC-B1-010 | Administrador del sistema | No |
| UC-018 | Publicar contenido de transparencia | UC-B1-006, UC-B2-017, UC-B3-012 | Administrador/Editor | No |
| UC-019 | Publicar video institucional accesible | UC-B3-015 | Administrador de contenidos | No |
| UC-020 | Gestionar usuarios y roles | UC-B1-015 | Administrador del sistema | No |
| UC-021 | Monitorear cumplimiento ITA (validación automática) | UC-B1-011 | Administrador de cumplimiento | No |
| UC-022 | Gestionar/Reportar incidente de seguridad | UC-B1-012, UC-B2-016 | Admin de seguridad TI, CSIRT | No |
| UC-023 | Aplicar encuesta SUS | UC-B1-013, UC-B2-014 | Ciudadano, Equipo UX | No |
| UC-024 | Validar accesibilidad de página | UC-B2-011, UC-B3-016 | Ingeniero de accesibilidad/QA | No |
| UC-025 | Publicar datos abiertos (admin) | UC-B2-013 | Administrador del portal | No |
| UC-026 | Integrar trámite a GOV.CO | UC-B2-009, UC-B3-019 | Equipo técnico, MinTIC | No |
| UC-027 | Desplegar/Configurar servidor X-Road | UC-B2-010, UC-B3-020 | Equipo TI, AND | No |
| UC-028 | Integrar entidad a Carpeta Ciudadana | UC-B2-015 | Equipo TI, AND, MinTIC | No |
| UC-029 | Intercambiar información vía X-Road | UC-B3-011 | Funcionario/Sistema Alcaldía | No |
| UC-030 | Consultar certificado/paz y salvo en línea | RF-B3-124 | Ciudadano | **Sí [INFERENCIA]** |
| UC-031 | Recuperar/restablecer contraseña | RF-B3-074 | Ciudadano | **Sí [INFERENCIA]** |
| UC-032 | Cancelar/reprogramar cita | RF-B1-030 | Ciudadano | **Sí [INFERENCIA]** |
| UC-033 | Revocar consentimiento / suprimir cuenta | RF-B1-008, RF-B2-052 | Ciudadano | **Sí [INFERENCIA]** |
| UC-034 | Actualizar/Rectificar datos del perfil | RF-B2-050 | Ciudadano autenticado | **Sí [INFERENCIA]** |
| UC-035 | Gestionar y enrutar PQRSD (back-office) | RF-B1-036/RF-B2-095 | Funcionario de dependencia | **Sí [INFERENCIA]** |
| UC-036 | Responder PQRSD / Gestionar vencimiento | HU-B3-022 | Funcionario responsable | **Sí [INFERENCIA]** |
| UC-037 | Trasladar PQRSD por no competencia | RF-B2-069 | Sistema/Funcionario | **Sí [INFERENCIA]** |
| UC-038 | Cerrar sesión / SLO | RF-B2-015, RF-B3-108 | Ciudadano | **Sí [INFERENCIA]** |
| UC-039 | Firmar electrónicamente acto administrativo | RF-B3-155, RN-B2-027 | Funcionario | **Sí [INFERENCIA]** |
| UC-040 | Consultar información tributaria / calendario | RF-B1-018, RF-B3-151 | Contribuyente | **Sí [INFERENCIA]** |
| UC-041 | Aprobar/Eliminar contenido (flujo editorial/TRD) | RF-B1-077, RN-B1-015 | Admin gestión documental | **Sí [INFERENCIA]** |
| UC-042 | Subsanar / aportar documentación requerida en un trámite | RF-03-D05, RN-03-D05 | Ciudadano | **Sí [delta profundo]** |
| UC-043 | Desistir de un trámite en línea | RF-03-D07, RN-03-D07 | Ciudadano | **Sí [delta profundo]** |
| UC-044 | Reconocer silencio administrativo positivo | RN-03-D08 | Sistema / Funcionario | **Sí [delta profundo]** |
| UC-045 | Reanudar trámite guardado (borrador) | RF-03-D03, RNF-03-D02 | Ciudadano | **Sí [delta profundo]** |
| UC-046 | Prorrogar el término de respuesta de una PQRSD | RF-04-D03, RN-04-D02 | Funcionario responsable | **Sí [delta profundo]** |
| UC-047 | Administrar la agenda de citas (CMS) | RF-06-D01 | Administrador / Funcionario de atención | **Sí [delta profundo]** |
| UC-048 | Gestionar el ciclo editorial con estados y rechazo | RF-12-D01, RN-12-D01, RN-09-D02 | Editor / Administrador (con SoD) | **Sí [delta profundo]** |
| UC-049 | Administrar y versionar datasets de datos abiertos | RF-11-D01, RN-11-D01 | Administrador del portal | **Sí [delta profundo]** |
| UC-050 | Administrar publicaciones de transparencia con versionado y alertas | RF-02-D01, RF-02-D02, RN-02-D01/02 | Administrador/Editor de transparencia | **Sí [delta profundo]** |

**Total: 50 UC detallados** (29 consolidados de los bundles + 21 UC secundarios nuevos).

---

## Fichas de los UC principales (consolidados de los bundles)

### UC-001 — Radicar PQRSD en línea
- **Actor principal:** Ciudadano (identificado o anónimo). **Secundarios:** SGDEA, gestor de cookies, captcha, dependencia destinataria.
- **Precondiciones:** la sede opera 24/7/365 (RF-B2-067, RF-B3-118); formulario PQRSD publicado y disponible en móvil (RF-B1-036, RF-B3-103).
- **Postcondiciones (éxito):** radicado único generado, acuse automático al correo, expediente creado en SGDEA, dependencia notificada (RF-B1-036, RN-B3-026).
- **Flujo principal:**
  1. Accede a "Atención y Servicios a la Ciudadanía" → PQRSD.
  2. Selecciona tipo (Petición/Queja/Reclamo/Sugerencia/Denuncia/Solicitud de información).
  3. Diligencia campos mínimos con validación dinámica (RF-B1-031, RF-B3-097/143).
  4. Adjunta documentos **sin restricción de formato/tamaño/cantidad** (RF-B1-033, RN-B1-010).
  5. Acepta condiciones / autorización de datos (casilla no pre-marcada — RF-B2-048).
  6. Resuelve captcha accesible (RF-B1-058, RF-B3-101).
  7. Envía → página de confirmación con número y tiempo estimado (RF-B1-083, RF-B3-145).
  8. Acuse al correo; radicado numerado en ≤24 h hábiles (RF-B3-099, RN-B3-026).
- **Flujos alternos:**
  - **A1 — PQRSD anónima:** se deshabilitan campos de identificación + aviso sobre garantías/limitaciones del anonimato (IP, georreferenciación, metadata) (RF-B1-032, RN-B1-009, UC-B3-003).
  - **A2 — Acceso a información que requiere notificación:** NO puede ser anónima; exige identificación (RN-B1-009 excepción).
  - **A3 — Adjuntos arrastrar-soltar:** muestra nombre y tamaño (RF-B3-077).
  - **A4 — Dato sensible en el objeto:** consentimiento explícito diferenciado (RF-B2-049).
- **Flujos de excepción (qué pasa si…):**
  - **E1 — Datos inválidos/incompletos:** resalta campo, mueve foco, mensaje accesible (`aria-describedby`), no envía (RF-B1-035, RF-B3-037/039).
  - **E2 — Captcha fallido:** reintento + alternativa de audio (RF-B1-058). **[Contradicción captcha vs accesibilidad → captcha WCAG 2.1 AA].**
  - **E3 — Falla de red/servidor:** mensaje con motivo + reintento + teléfono (RF-B3-102).
  - **E4 — Adjunto excede límite técnico:** única limitación admisible, documentada; NO se rechaza por formato (RN-B1-010). **[Resuelve C-01: prevalece #241 sobre la restricción PDF/JPG/PNG≤10MB de #239].**
  - **E5 — Inyección de script:** entrada sanitizada (RF-B1-060, RF-B3-131).
  - **E6 — Ataque CSRF:** token inválido → rechazo (RF-B1-064).
  - **E7 — Envío masivo por bot:** anti-spam bloquea sin afectar humanos (RF-B3-101).
  - **E8 — Sesión expirada (si autenticado):** 900 s; preservar borrador si es posible [INFERENCIA] o reautenticar (RF-B1-057, RNF-B1-023).
- **RF/RN:** RF-B1-031..036, RF-B2-069, RF-B3-097..104; RN-B1-009/010, RN-B3-026. **Fuente:** #7, #125, #221, #225, #239, #241.
- **Criterios clave:** radicado + acuse en ≤5 s al SGDEA; cero rechazos por formato/tamaño.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E9 — Atomicidad/unicidad del radicado bajo concurrencia:** dos envíos simultáneos nunca comparten consecutivo (asignación atómica) (RNF-04-D02, RN-04-D07 · [DOMINIO]).
  - *Sello temporal legal (transversal):* "Toda evidencia con valor legal usa la **Hora Legal de Colombia (INM)** y, donde aplique, estampa TSA RFC 3161." (RN-TX-D02).

### UC-002 — Consultar estado de PQRSD / trámite
- **Actor:** Ciudadano. **Secundarios:** SGDEA.
- **Precondiciones:** existe un radicado válido.
- **Postcondiciones:** se muestra estado actualizado, historial y fecha estimada.
- **Flujo principal:** 1. Módulo de seguimiento → 2. Ingresa radicado → 3. Consulta SGDEA → 4. Muestra tipo, fecha, dependencia, estado estandarizado (registrada→recibida a satisfacción→en trámite→resuelta), fecha estimada (RF-B1-034, RF-B3-100/127) → 5. Habilita descarga si hay respuesta.
- **Flujos alternos:** **A1** Ciudadano autenticado consulta "Mis trámites" sin radicado (RF-B3-093). **A2** Trámite trasladado: muestra entidad receptora y fecha (RF-B2-069).
- **Flujos de excepción:** **E1** Radicado inexistente/mal digitado → "No encontramos una solicitud con ese número" + verificar/contactar [INFERENCIA]. **E2** Anónimos consultables públicamente (HU-B2-003); identificados con dato sensible pueden requerir autenticación [INFERENCIA]. **E3** SGDEA no disponible → mensaje genérico (RF-B1-061) + reintento.
- **RF/RN:** RF-B1-034, RF-B3-093/100/127. **Fuente:** #7, #128, #221, #239. **Criterio:** ≤3 s (HU-B1-004).

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E4 — IDOR en consulta por radicado:** ciudadano autenticado abre radicado ajeno cambiando el número → 403 + log (los radicados anónimos siguen siendo públicos, A1 sin cambio) (RF-09-D01, RN-09-D01).
  - *Precondición añadida (autorización por objeto):* "El acceso a cualquier recurso (radicado/trámite/PQRSD/cita/CCD) verifica **pertenencia del objeto al titular autenticado** (anti-IDOR), no solo el rol de módulo." (RN-09-D01).

### UC-003 — Buscar y consultar trámite (catálogo)
- **Actor:** Ciudadano. **Secundarios:** SUIT, GOV.CO.
- **Precondiciones:** catálogo publicado y vinculado al SUIT.
- **Flujo principal:** 1. Catálogo → 2. Busca/filtra (nombre, modalidad, costo, tipo) (RF-B1-022) → 3. Selecciona → 4. Ficha (modalidad, costo, tiempo, documentos, 4 momentos) → 5. "Iniciar trámite" redirige a `gov.co/servicios-y-tramites/T{código}` (RF-B1-021, RF-B3-092/126).
- **Flujos alternos:** **A1** Parcialmente en línea: pasos digitales y presenciales. **A2** Presencial: puntos de atención georreferenciados (RF-B2-029). **A3** Requisito verificable por interoperabilidad → la ficha indica que NO debe aportarse (RF-B3-114, RN-B2-005).
- **Flujos de excepción:** **E1** Redirección externa → aviso modal con confirmación (RF-B1-071, RF-B3-094). **E2** Sin resultados → sugerencias + corrección ortográfica (RF-B1-006, RF-B3-140). **E3** Ficha/enlace SUIT roto → cero 404 (RF-B2-041); fallback a contacto [INFERENCIA].
- **RF/RN:** RF-B1-021/022, RF-B3-092. **Fuente:** #36, #128, #132, #209, #241.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E4 — Aviso de salida por lista blanca:** no mostrar modal en redirecciones a GOV.CO/SCD (evitar falso positivo) (RF-01-D03, RN-01-D03).

### UC-004 — Realizar trámite en línea con autenticación
- **Actor:** Ciudadano. **Secundarios:** SCD Autenticación (OIDC), SCD Interoperabilidad (X-Road), pasarela de pagos, SGDEA, CCD.
- **Precondiciones:** trámite digitalizado, en SUIT, integrado a GOV.CO; nivel de confianza definido.
- **Postcondiciones:** solicitud radicada, expediente en SGDEA, resultado en CCD.
- **Flujo principal (4 etapas):** 1. Inicio en GOV.CO (ficha SUIT) → 2. Redirección enmascarada → 3. Autenticación SCD si aplica (UC-005) → 4. Formulario (datos verificados por interoperabilidad cuando disponibles) → 5. Carga documentos sin restricción → 6. Pago si aplica (UC-007) → 7. Autorización de datos + T&C → 8. Radica → 9. Consulta estado → 10. Resultado en CCD + encuesta FÁCIL/DIFÍCIL → 11. SLO (RF-B2-028..032).
- **Flujos alternos:** **A1** Trámite sin autenticación (#152). **A2** Resolución inmediata (sin etapa 3). **A3** Verificación de identidad/predio por X-Road sin adjuntar (RF-B2-094, HU-B2-015).
- **Flujos de excepción:** **E1 — SCD Autenticación no disponible:** canal alternativo presencial/telefónico (RN-B2-023). **E2 — X-Road caído (timeout):** exigir documento transitoriamente solo mientras dure la falla documentada (RN-B2-004). **E3 — Pago rechazado/interrumpido:** ver UC-007. **E4 — Sesión expirada (900 s):** reautenticar; preservar stepper si viable [INFERENCIA] (RNF-B1-023, RF-B3-019). **E5 — Cancelación por el ciudadano:** descarta borrador [INFERENCIA]. **E6 — MIME falso:** valida tipo real y rechaza (RF-B1-063).
- **RF/RN:** RF-B1-004/023/024/027/028, RF-B2-028..032/094, RF-B3-013/110..112. **Fuente:** #22, #124, #128, #143, #152, #251.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E2' — X-Road caído durante verificación automática:** además de "exigir documento", **registrar la falla técnica documentada** y habilitar carga manual temporal con constancia; reintento/cola → fallback documentado (RF-03-D06, RN-03-D06 · [NORMATIVA]).
  - **A4 — Subsanación solicitada en Etapa 3** → enlaza UC-042: la entidad marca "requiere subsanación"; trámite se suspende (RF-03-D05).
  - **A5 — Desistir** → enlaza UC-043: opción de desistimiento antes de resolución (RF-03-D07).
  - **A6 — Guardar borrador / reanudar** → enlaza UC-045: persistencia parcial del stepper (RF-03-D03).
  - **E7 — IDOR:** acceso a trámite ajeno por ID en URL → 403 + log; control de pertenencia por objeto (RF-09-D01, RN-09-D01 · [DOMINIO] OWASP A01).
  - **E8 — Nivel de autenticación insuficiente para el trámite:** bloqueo de inicio si el nivel del ciudadano < nivel exigido (RN-TX-D04 (C-08, **[PREGUNTA ABIERTA]** matriz)).
  - *Postcondición añadida (cómputo SAP):* "Si el trámite está sujeto a SAP, el sistema computa el término y dispara UC-044 al vencimiento." (RN-03-D08).
  - *Precondición añadida (autorización por objeto):* "El acceso a cualquier recurso (radicado/trámite/PQRSD/cita/CCD) verifica **pertenencia del objeto al titular autenticado** (anti-IDOR), no solo el rol de módulo." (RN-09-D01).

### UC-005 — Autenticar ciudadano (SCD / por niveles)
- **Actor:** Ciudadano. **Secundarios:** SCD Autenticación (OIDC), Registraduría/ANI.
- **Precondiciones:** el trámite declara su nivel (Bajo/Medio/Alto/Muy Alto).
- **Postcondiciones:** sesión con perfil/permisos; evidencia de aceptación de T&C con sello de tiempo.
- **Flujo principal:** 1. Login del trámite → 2. Determina nivel → 3. Bajo: correo+OTP; Medio: +teléfono+dirección+MFA; Alto: certificado+ANI; Muy Alto: biometría ABIS/Cédula Digital → 4. Redirección a `Authorize` → 5. Canje de `authorization_code` por tokens → 6. SSO activo (RF-B1-025/026, RF-B3-107/109).
- **Flujos alternos:** **A1** SSO: usuario ya autenticado no reautentica (RF-B2-015). **A2** Autenticación local 2FA mientras no hay SCD pleno (RF-B3-107).
- **Flujos de excepción:** **E1** 5 intentos fallidos → bloqueo + alerta (RF-B1-062, RF-B3-132). **E2** OTP no recibido/expirado → reenvío con límite [INFERENCIA]. **E3** SCD no disponible → canal alternativo (RN-B2-023). **E4** ANI no responde → verificación diferida/transitoria [INFERENCIA]. **E5** Token revocado/trámite de baja → tokens invalidados (RF-B2-013). **E6** Latencia >1 s → degradación/monitoreo (RNF-B1-009, RNF-B3-013).
- **RF/RN:** RF-B1-025/026, RF-B2-005/012/013/015, RF-B3-107/109. **Fuente:** #119, #124, #143, #156. **Criterio:** autenticación <1 s.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E7 — Caída del SCD/OIDC (Articulador):** mensaje "autenticación no disponible", **no bloquea contenido público**; `state` no coincidente en callback → rechazo + log; manejo de error de federación (RF-09-D04, RN-09-D06 · [DOMINIO]).
  - *Precondición añadida (autorización por objeto):* "El acceso a cualquier recurso (radicado/trámite/PQRSD/cita/CCD) verifica **pertenencia del objeto al titular autenticado** (anti-IDOR), no solo el rol de módulo." (RN-09-D01).

### UC-006 — Registrarse en la sede
- **Actor:** Ciudadano. **Secundarios:** ANI/Registraduría.
- **Postcondiciones:** perfil creado, firma electrónica otorgada, evidencia de T&C.
- **Flujo principal:** 1. Registro → 2. Tipo y número de documento (CC/CE/TI/PEP/NIT) → 3. Validación ANI en tiempo real (RF-B3-106) → 4. Correo + contraseña ≥8 → 5. OTP → 6. Acepta T&C/privacidad → 7. Cuenta activa + firma electrónica (RF-B2-097).
- **Flujos de excepción:** **E1** Documento no corresponde en ANI → rechazo [INFERENCIA]. **E2** Documento ya registrado → ofrece "recuperar contraseña" (UC-031). **E3** Menor de 18 → datos del representante legal (RN-B2-007). **E4** ANI no disponible → registro pendiente de validación [INFERENCIA]. **E5** Contraseña débil → exige mínimo (RNF-B1-021).
- **RF/RN:** RF-B2-097, RF-B3-106; RN-B2-007. **Fuente:** #119, #122, #131, #143.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E6 — Verificación de edad (<18):** exigir autorización del representante legal antes de recolectar datos del menor → bloqueo hasta autorización (RF-12-D04, RN-12-D03 · [NORMATIVA] Ley 1581 Art.7).

### UC-007 — Pagar un trámite en línea
- **Actor:** Ciudadano. **Secundarios:** pasarela PSE/tarjetas (Superfinanciera).
- **Postcondiciones:** pago confirmado, comprobante, trámite avanza.
- **Flujo principal:** 1. Paso de pago → 2. Calcula tarifa → 3. Selecciona PSE/débito/crédito → 4. Pago HTTPS sin recargo → 5. Comprobante → 6. Avanza (RF-B1-029, RF-B2-066, RF-B3-122/123).
- **Flujos de excepción:** **E1** Pago rechazado por el banco → reintento con otro medio [INFERENCIA]. **E2** Pasarela no disponible → no cobra; "pago pendiente" sin perder avance [INFERENCIA]. **E3** Pago interrumpido/doble cobro → conciliación idempotente [INFERENCIA — vacío]. **E4** Recargo por canal digital → prohibido (RN-B2-001, RN-B3-010).
- **RF/RN:** RF-B1-029, RF-B2-066, RF-B3-122/123; RN-B2-001, RN-B3-010. **Fuente:** #128, #141, #172; Decreto 2106 Art.7/17.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E3' — Pago interrumpido / doble clic / timeout PSE** (cierra el vacío que la ficha marcaba `[INFERENCIA — vacío]`): con **clave de idempotencia** por transacción: el reintento del mismo formulario devuelve el comprobante existente, nunca un segundo cargo ni segundo radicado (RF-03-D01, RN-03-D01 · [DOMINIO]).
  - **E5 — Conciliación de estados de pasarela** `aprobado/rechazado/pendiente/fallido`: `pendiente`→ trámite "en espera de confirmación", reconsulta cada N min; solo `aprobado` libera la etapa; `rechazado` no consume radicado/borrador (RF-03-D02, RN-03-D02 · [DOMINIO]+[WEB]).
  - **E6 — Recargo por canal digital en el punto de pago:** prohibido cobrar la pasarela como recargo al ciudadano (RN-03-D03 · [NORMATIVA] Dec.2106 Art.7).
  - *Postcondición añadida (avance condicionado al pago):* "El trámite avanza a la siguiente etapa **únicamente si el pago queda en estado `aprobado`**; estados `pendiente/fallido/rechazado` mantienen el trámite en espera sin consumir radicado." (RN-03-D02).
  - *Precondición añadida (idempotencia):* "Cada intento de pago se identifica con una **clave de idempotencia** única por transacción." (RN-03-D01).

### UC-008 — Recibir resultado / notificación del trámite
- **Actor:** Ciudadano. **Secundarios:** CCD, correo, SMS/APP, SGDEA.
- **Flujo principal:** 1. Alcaldía resuelve y firma (UC-039) → 2. Notifica por correo/CCD/SMS según preferencia (RF-B3-153/154) → 3. Resultado en CCD → 4. Encuesta FÁCIL/DIFÍCIL (RF-B2-032/033).
- **Flujos alternos:** **A1** Dirección procesal electrónica con preferencia sobre física (RN-B3-019). **A2** Multicanal en <1 h (RF-B3-154).
- **Flujos de excepción:** **E1** Sin canal electrónico → notificación física (RN-B3-019 excepción). **E2** CCD no disponible → correo de respaldo [INFERENCIA]. **E3** Correo rebotado → reintento/alerta [INFERENCIA].
- **RF/RN:** RF-B2-032, RF-B3-117/153/154; RN-B3-019. **Fuente:** #128, #131, #153; Decreto 2106 Art.46.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E4 — Reintento/cola multicanal:** si falla un canal (correo/SMS/push/CCD), reintentar y/o canal alterno autorizado, registrando estado de entrega; un rebote no se da por notificado; garantía de entrega (RF-12-D03, RN-12-D04 · [NORMATIVA] Ley 1437).

### UC-009 — Consultar Carpeta Ciudadana Digital
- **Actor:** Ciudadano. **Secundarios:** GOV.CO (CCD), servicios REST de la Alcaldía vía X-Road.
- **Precondiciones:** autenticación nivel **medio** (RF-B2-021).
- **Flujo principal:** 1. Autentica nivel medio en GOV.CO → 2. CCD invoca los 4 servicios REST → 3. Muestra datos sin almacenarlos → 4. Para iniciar trámite redirige a la sede (RF-B2-020/021, RF-B3-116/117).
- **Flujos alternos:** **A1** Solicita corrección → se enruta al área (UC-034). **A2** Descargar resultado desde CCD (RF-B1-027).
- **Flujos de excepción:** **E1** Servicio REST caído → datos parciales/aviso [INFERENCIA]. **E2** Dato clasificado/reservado → no se expone (RF-B2-022). **E3** Nivel de confianza insuficiente → solicita elevar autenticación.
- **RF/RN:** RF-B2-020..022, RF-B3-116/117. **Fuente:** #119, #124, #147, #224.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E4 — IDOR en Carpeta Ciudadana:** consulta de datos de otro titular → 403 + log; control de pertenencia (RF-09-D01, RN-09-D01).
  - *Precondición añadida (autorización por objeto):* "El acceso a cualquier recurso (radicado/trámite/PQRSD/cita/CCD) verifica **pertenencia del objeto al titular autenticado** (anti-IDOR), no solo el rol de módulo." (RN-09-D01).

### UC-010 — Ejercer derechos ARCO
- **Actor:** Ciudadano autenticado. **Secundarios:** Oficial de Protección de Datos.
- **Precondiciones:** módulo ARCO disponible (alta disponibilidad horario laboral, RNF-B2-006).
- **Flujo principal:** 1. Módulo ARCO desde el perfil → 2. Acceso/Rectificación/Cancelación/Oposición → 3. Formulario + soportes → 4. Acuse automático → 5. Consulta ≤10 días hábiles (prorrogable 5); Reclamo ≤15 (prorrogable 8), "en trámite" en ≤2 días (RF-B2-047/070, RN-B2-012).
- **Flujos alternos:** **A1 — Cancelación/supresión:** suprime o registra imposibilidad legal con justificación ≤15 días (RF-B2-052). **A2** Portabilidad PDF/CSV/JSON (RNF-B2-028).
- **Flujos de excepción:** **E1** Supresión imposible por ley → respuesta motivada (RF-B2-052). **E2** Cambio de política amplía alcance → nueva autorización (RF-B2-090). **E3** Brecha que afecta datos → notificar SIC (RN-B2-022).
- **RF/RN:** RF-B2-047/050/051/052/070/090; RN-B2-009/013/022. **Fuente:** #122.

### UC-011 — Agendar cita de atención presencial
- **Actor:** Ciudadano.
- **Precondiciones:** módulo de agendamiento con horarios y sedes (RF-B1-030, RF-B3-096).
- **Flujo principal:** 1. "Agendar cita" → 2. Dependencia/servicio → 3. Calendario con disponibilidad → 4. Selecciona → 5. Datos de contacto → 6. Confirma → 7. Correo con código y sede.
- **Flujos de excepción:** **E1 — Cupo agotado / concurrencia:** dos ciudadanos toman el mismo turno → bloquea el segundo y ofrece alterno **[vacío — concurrencia]** [INFERENCIA]. **E2** Correo no enviado → muestra código en pantalla [INFERENCIA]. **E3** Ciudadano sin medios → acceso asistido en sede física (RF-B1-095).
- **RF/RN:** RF-B1-030, RF-B3-096. **Fuente:** #209, #221, #225; Decreto 2106 Art.14.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E1' — Concurrencia de cupo resuelta** (no ya `[vacío]`): reserva atómica; la concurrente recibe "cupo no disponible" (mismo control que UC-032 E3') (RF-06-D03, RN-06-D01).
  - *Precondición añadida (oferta de agenda):* "Existe oferta de disponibilidad configurada por UC-047 (servicios, franjas, cupos)." (RF-06-D01). La disponibilidad proviene de **UC-047** (administración de agenda); sin oferta configurada no hay calendario.

### UC-012 — Consultar normativa institucional
- **Actor:** Ciudadano. **Secundarios:** SUIN.
- **Flujo principal:** 1. Transparencia → Normativa → 2. Filtra tipo/año → 3. Listado cronológico inverso → 4. Selecciona → 5. Ficha (tipo, número, fechas, epígrafe, vigencia) → 6. Descarga abierta o enlace SUIN (RF-B1-013/014, RF-B3-082/087).
- **Flujos alternos:** **A1** Proyecto de norma con fecha de comentarios → enlaza a Participa (UC-014).
- **Flujos de excepción:** **E1** Enlace SUIN roto → aviso de salida + reintento (RF-B2-041). **E2** Documento sin vigencia → no debió publicarse (UC-018).
- **RF/RN:** RF-B1-013/014, RF-B3-082; RN-B1-005, RN-B3-023. **Fuente:** #7, #221, #225.

### UC-013 — Consultar y descargar datos abiertos
- **Actor:** Ciudadano/Investigador. **Secundarios:** datos.gov.co.
- **Flujo principal:** 1. Datos Abiertos → 2. Catálogo (nombre, categoría, fecha, formato) → 3. Filtra/busca → 4. Selecciona → 5. Metadatos + licencia → 6. Descarga (CSV/JSON/XML) o enlace a datos.gov.co (RF-B1-019/090/091, RF-B3-086).
- **Flujos de excepción:** **E1** Dataset sin licencia/metadatos → no debió publicarse (validado en UC-025). **E2** datos.gov.co no disponible → descarga local de respaldo [INFERENCIA].
- **RF/RN:** RF-B1-019/090/091; RN-B1-019. **Fuente:** #8, #242.

### UC-014 — Participar en consulta ciudadana de norma
- **Actor:** Ciudadano. **Secundarios:** SUCOP (DNP).
- **Flujo principal:** 1. Participa → 2. Norma en elaboración con plazo → 3. Lee proyecto → 4. "Aportar comentarios" → 5. Redirige a SUCOP → 6. Radica → 7. Confirmación (RF-B1-038/039).
- **Flujos de excepción:** **E1** Plazo vencido → solo lectura [INFERENCIA]. **E2** SUCOP no disponible → aviso de salida + reintento (RF-B1-071).
- **RF/RN:** RF-B1-038/039, RF-B3-105. **Fuente:** #7, #225.

### UC-015 — Buscar información (buscador interno)
- **Actor:** Ciudadano.
- **Flujo principal:** 1. Escribe (ancho ≥27 car.) → 2. Autocompletado ≤10 + corrección ortográfica → 3. Enter/selecciona → 4. Resultados con fecha, categoría, título, extracto, autor, miniatura → 5. Clic (RF-B1-005/006, RF-B3-067/140/141).
- **Flujos de excepción:** **E1** Cero resultados → sugerencias y rutas alternativas (RF-B1-053). **E2** Error tipográfico → corrige (RF-B3-140). **E3** Resultados anunciados por `aria-live` sin mover foco (RF-B3-043).
- **RF/RN:** RF-B1-005/006, RF-B3-067/140/141. **Fuente:** #170, #243, #118.

### UC-016 — Gestionar consentimiento de cookies
- **Actor:** Ciudadano. **Secundarios:** gestor de cookies.
- **Flujo principal:** 1. Primer acceso → banner → 2. Aceptar todas / rechazar no esenciales / configurar por categoría → 3. Activa/desactiva → 4. Solo necesarias antes del consentimiento (RF-B1-008, RF-B2-011, RN-B2-017, RN-B3-031).
- **Flujos alternos:** **A1 — Revocación:** desde "Política de Cookies" en cualquier momento (RF-B1-008).
- **Flujos de excepción:** **E1** Cookie de analítica activa antes del consentimiento → violación (RN-B3-031). **E2** Cambio de categorías → re-solicita consentimiento [INFERENCIA].
- **RF/RN:** RF-B1-008, RF-B2-011; RN-B2-017, RN-B3-031. **Fuente:** #70, #125, #144, #241.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **A2 — Caducidad/versionado del consentimiento:** >12 meses o cambio de política → re-solicitar banner; registro con versión y timestamp; persistencia versionada (RF-01-D01, RN-01-D01 · [NORMATIVA] Ley 1581).

### UC-017 — Iniciar sesión administrador del CMS
- **Actor:** Administrador del sistema. **Secundarios:** log de auditoría, alerta de seguridad.
- **Precondiciones:** panel no accesible desde Internet sin autenticación (RF-B1-062).
- **Flujo principal:** 1. URL del panel → 2. Usuario/contraseña → 3. Autenticación → 4. Dashboard según rol → 5. Log de inicio.
- **Flujos de excepción:** **E1** 5 intentos fallidos → bloqueo ≥15 min + alerta (RF-B1-062, HU-B1-020). **E2** Sesión expirada (900 s) → reautenticación (RNF-B1-023). **E3** Acceso desde IP no autorizada → administración remota restringida (RF-B3-135) [INFERENCIA].
- **RF/RN:** RF-B1-062/079, RF-B3-132; RNF-B2-010/011. **Fuente:** #19, #53, #241.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - *Precondición añadida (MFA interno):* "El acceso de roles internos del CMS exige **segundo factor (MFA)**." (RN-09-D04, C-07).
  - *Postcondición añadida (log inmutable):* "Todo evento de auditoría se escribe en log **append-only/encadenado por hash**, inalterable durante su retención (5 años)." (RN-09-D05).

### UC-018 — Publicar contenido de transparencia
- **Actor:** Administrador/Editor del CMS. **Secundarios:** log de auditoría, validador W3C/enlaces.
- **Flujo principal:** 1. Autentica en CMS → 2. Transparencia/Normativa → 3. Crea documento + metadatos (tipo, número, fechas, vigencia) → 4. Valida obligatorios → 5. Guarda borrador → 6. Publica → 7. Aparece inmediato en orden cronológico → 8. Log (RF-B1-013/076).
- **Flujos alternos:** **A1 — Flujo editorial:** editor crea → "pendiente de aprobación" del administrador (RF-B1-076). **A2** Lenguaje claro y `alt` verificados (UC-B2-017).
- **Flujos de excepción:** **E1** Metadato obligatorio faltante (vigencia) → bloquea publicación (HU-B3-018). **E2** Permisos insuficientes (RF-B1-079). **E3** Vínculo roto/HTML inválido → bloquea o alerta (RF-B2-041/075). **E4 — Edición concurrente:** bloqueo/aviso de conflicto **[vacío — concurrencia]** [INFERENCIA]. **E5** Publicidad/marca ajena → rechazo editorial (RN-B1-014).
- **RF/RN:** RF-B1-013/076/079, RF-B3-082..088; RN-B1-014. **Fuente:** #221, #217, #225, #241.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E4' — Edición concurrente resuelta** (no ya `[vacío]`): bloqueo optimista/pesimista (RN-12-D05, RNF-12-D01).
  - **A3 — Estados editoriales con rechazo + SoD** → formalizado en UC-048 (RF-12-D01, RN-09-D02).
  - *Precondición añadida (SoD):* "Quien crea/edita un contenido NO puede aprobarlo/publicarlo (segregación de funciones)." (RN-09-D02).
  - *Postcondición añadida (log inmutable):* "Todo evento de auditoría se escribe en log **append-only/encadenado por hash**, inalterable durante su retención (5 años)." (RN-09-D05).

### UC-019 — Publicar video institucional accesible
- **Actor:** Administrador de contenidos.
- **Flujo principal:** 1. Sube video → 2. Verifica subtítulos (SRT) → 3. Closed caption ES + audiodescripción → 4. Si alocución/emergencia/seguridad/rendición → LSC obligatoria → 5. Publica (RF-B3-003/045, RN-B3-002/003).
- **Flujos de excepción:** **E1** Sin subtítulos → bloquea publicación (RN-B3-002). **E2** Transmisión en vivo → excepción de closed caption en tiempo real. **E3 [PREGUNTA ABIERTA]** ¿LSC del Gobierno Nacional aplica a entidad territorial?
- **RF/RN:** RF-B3-003/045; RN-B3-001/002/003. **Fuente:** #76, #200.

### UC-020 — Gestionar usuarios y roles
- **Actor:** Administrador del sistema.
- **Flujo principal:** 1. Gestión de usuarios → 2. Crea (nombre, correo, rol) → 3. Correo de bienvenida + contraseña temporal → 4. Cambio en primer acceso → 5. Modificar/suspender/historial → 6. Log (RF-B1-079).
- **Flujos alternos:** **A1 — Desvinculación:** revocar accesos ≤1 día hábil (RNF-B2-011).
- **Flujos de excepción:** **E1** Correo duplicado → rechazo [INFERENCIA]. **E2** Privilegios excesivos → mínimo privilegio (RNF-B3-026). **E3** Revisión trimestral detecta cuentas excesivas → corrección.
- **RF/RN:** RF-B1-079; RNF-B2-010/011, RNF-B3-026. **Fuente:** #239, #241.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **A2 — Baja segura con retención de auditoría:** revocar accesos/tokens ≤1 día hábil **sin borrado físico**; eventos de auditoría permanecen consultables (RF-12-D02, RN-12-D02 · [NORMATIVA]).
  - **E4 — MFA obligatorio para administradores del CMS** al crear/operar cuentas internas: la contraseña ≥8 no basta para roles internos (RN-09-D04 (C-07) · [DOMINIO]).
  - *Precondición añadida (MFA interno):* "El acceso de roles internos del CMS exige **segundo factor (MFA)**." (RN-09-D04, C-07).
  - *Postcondición añadida (log inmutable):* "Todo evento de auditoría se escribe en log **append-only/encadenado por hash**, inalterable durante su retención (5 años)." (RN-09-D05).

### UC-021 — Monitorear cumplimiento ITA (validación automática)
- **Actor:** Administrador de cumplimiento.
- **Flujo principal:** 1. Tablero **interno** ITA (arranca en cero, sin datos mock) → 2. El sistema valida automáticamente cada contenido al publicarlo (RF-12-D05) y actualiza el tablero → 3. Filtra por módulo/criterio/norma → 4. Ve los incumplimientos detectados con su ubicación, norma y responsable → 5. El responsable corrige manualmente el contenido publicado → 6. Al corregir, el ítem pasa a cumplido automáticamente → 7. Log (RF-B1-078).
- **Nota (2026-06-05):** el ITA ya **no usa datos mock ni el 47/100**; el cumplimiento se construye desde cero validando lo que se publica (ej. imagen sin `alt` → marcada incumplida hasta corregir). Resuelve A-03.
- **Flujos de excepción:** **E1** Ítem sin norma → inconsistencia a resolver [INFERENCIA]. **E2** Vencimiento próximo → alerta al responsable [INFERENCIA].
- **RF/RN:** RF-B1-078. **Fuente:** #239.

### UC-022 — Gestionar / Reportar incidente de seguridad
- **Actor:** Administrador de seguridad TI. **Secundarios:** CSIRT-Gobierno, SIC.
- **Flujo principal:** 1. Monitoreo detecta anomalía → 2. Clasifica (leve/grave/muy grave) → 3. Si afecta datos: notifica SIC → 4. Reporta a CSIRT ≤24 h → 5. Mitiga (bloqueo IP, parches) → 6. Restaura → 7. Informe post-incidente (RF-B1-066, RN-B1-016, RN-B2-022).
- **Flujos alternos:** **A1** Incidente leve → proceso interno sin reporte externo (RN-B1-016 excepción).
- **Flujos de excepción:** **E1** DDoS → mitigación activa + alerta (RF-B2-061). **E2** Defacement/fuga → activa DRP/BCP (RF-B3-134).
- **RF/RN:** RF-B1-066, RF-B2-016/061, RF-B3-134; RN-B1-016, RN-B2-022. **Fuente:** #19, #122, #128, #201, #251.

### UC-023 — Aplicar encuesta SUS
- **Actor:** Ciudadano. **Secundarios:** Equipo UX.
- **Flujo principal:** 1. Al finalizar trámite → invitación SUS → 2. 10 ítems Likert 1-5 → 3. Cálculo automático → 4. Almacena → 5. UX compara vs 68; si <68 → plan de mejora (RF-B1-087, RF-B2-083).
- **Flujos de excepción:** **E1** Ciudadano omite la encuesta → opcional, no bloquea [INFERENCIA]. **E2** Puntaje <68 → dispara mejora (RF-B2-083).
- **RF/RN:** RF-B1-087, RF-B2-083; RNF-B1-030. **Fuente:** #32, #190.

### UC-024 — Validar accesibilidad de página
- **Actor:** Ingeniero de accesibilidad/QA. **Secundarios:** AND (validación).
- **Flujo principal:** 1. Despliega en pruebas → 2. axe-core/Lighthouse/Tawdis + Contrast Checker → 3. Verifica 52 criterios (tabla V8) → 4. Lector de pantalla (NVDA/VoiceOver) → 5. Teclado y móvil → 6. Documenta → 7. Corrige antes de producción (RNF-B1-014, RN-B1-021).
- **Flujos de excepción:** **E1** Violación crítica/seria → bloquea despliegue (RNF-B2-001). **E2** Contenido pre-2022 sin actualizar → cola de adecuación (RN-B1-021 excepción).
- **RF/RN:** RF-B1-044..054, RF-B3-001..052; RN-B1-021, RN-B3-001. **Fuente:** #17, #76, #200.

### UC-025 — Publicar datos abiertos (administración)
- **Actor:** Administrador del portal. **Secundarios:** datos.gov.co.
- **Flujo principal:** 1. Sube dataset → 2. Configura metadatos + licencia → 3. Publica CSV/JSON/XML → 4. Federa a datos.gov.co → 5. Indexa en catálogo (RF-B1-019/020/090/091).
- **Flujos de excepción:** **E1** Metadatos/licencia incompletos → bloqueo (RF-B1-091). **E2** Dato con criticidad/reserva → no se publica abierto (RN-B1-019). **E3** datos.gov.co no responde → reintento de federación [INFERENCIA].
- **RF/RN:** RF-B1-019/020/090/091; RN-B1-019. **Fuente:** #8, #242, #258.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **A1 — U/D + versionado + control de frescura** → formalizado en UC-049 (RF-11-D01, RN-11-D01).

### UC-026 — Integrar trámite a GOV.CO
- **Actor:** Equipo técnico de la Alcaldía. **Secundarios:** MinTIC, AND.
- **Flujo principal (7 pasos / 17 semanas):** 1. Actualiza ficha SUIT → 2. Diligencia formulario de integración → 3. Adecúa la sede (top bar, footer, 4 etapas, seguridad, accesibilidad) → 4. MinTIC revisa → 5. Atiende observaciones → 6. MinTIC aprueba y activa redireccionamiento → 7. Monitoreo (RF-B1-070, RF-B3-128).
- **Flujos de excepción:** **E1** Observaciones de MinTIC → ciclo de ajuste (RF-B3-128). **E2** No cumple requisitos mínimos → integración no se activa (RN-B3-006). **E3** URL cambia/retira → actualizar SUIT y GOV.CO inmediatamente (RF-B2-098).
- **RF/RN:** RF-B1-070/072, RF-B3-125..128; RN-B3-006/007. **Fuente:** #108, #128, #152, #209, #241.

### UC-027 — Desplegar / Configurar servidor X-Road
- **Actor:** Equipo TI/Administrador TI. **Secundarios:** AND, CA (ONAC), TSA (GSE).
- **Flujo principal:** 1. Aprovisiona servidor (Ubuntu 18.04/RHEL7) en 3 ambientes → 2. Zona horaria Bogotá → 3. Puertos TCP (5500/5577/9011/9999) → 4. Genera CSR e importa certificados ONAC → 5. Ancla a servidor central AND → 6. Configura TSA GSE (reemplaza Certicámara) → 7. Crea subsistemas y registra servicios (WSDL/REST) → 8. Pruebas QA/preproducción → 9. Producción con HA (RF-B2-016..019/088).
- **Flujos de excepción:** **E1** Sin certificación nivel 3 LCI → no pasa a producción (RN-B2-018, RN-B3-029). **E2** Imagen Docker en producción → prohibido (RN-B2-021). **E3** Regla firewall a tsa.gse.com.co faltante → estampas fallan (RF-B2-088). **E4 [PREGUNTA ABIERTA]** clase miembro "CO" vs "GOB/PRIV" — verificar con AND.
- **RF/RN:** RF-B2-016..019/088, RF-B3-110/139; RN-B2-016/018/021, RN-B3-029/032. **Fuente:** #119, #124, #126, #140, #203.

### UC-028 — Integrar entidad a Carpeta Ciudadana
- **Actor:** Equipo TI. **Secundarios:** AND, MinTIC.
- **Flujo principal (Análisis → Ejecución):** 1. Reuniones de análisis + cuestionarios → 2. Define alcance → 3. Clasifica información (Ley 1712) → 4. Desarrolla 4 servicios REST → 5. Pruebas QA/preproducción → 6. Producción → 7. Ciudadano ve datos en CCD (RF-B2-020..022, RN-B2-025).
- **Flujos de excepción:** **E1** Salto a ejecución sin análisis → prohibido (RN-B2-025). **E2** Datos clasificados/reservados → no se exponen (RF-B2-022).
- **RF/RN:** RF-B2-020..022; RN-B2-025. **Fuente:** #124, #147.

### UC-029 — Intercambiar información vía X-Road (consumo en trámite)
- **Actor:** Sistema/Funcionario de la Alcaldía. **Secundarios:** entidad fuente (Registraduría/RUNT/RUAF), TSA.
- **Flujo principal:** 1. El sistema requiere un dato → 2. Mensaje REST/SOAP con encabezados `client`/`service` → 3. Servidor local cifra y retransmite al central AND → 4. Entidad destino responde → 5. Respuesta con firma + estampa → 6. Usa el dato sin pedirlo al ciudadano (RF-B2-094, RF-B3-111..113).
- **Flujos de excepción:** **E1 — X-Road no disponible (timeout):** exigir documento transitoriamente solo mientras dure la falla documentada (RN-B2-004). **E2** Registro no integrado → consulta en línea o exigir documento (RF-B3-115, RN-B3-008 excepción). **E3** Certificado vencido/OCSP sin respuesta → mensaje no se transmite [INFERENCIA].
- **RF/RN:** RF-B2-094, RF-B3-111..113; RN-B2-004, RN-B3-008. **Fuente:** #119, #141, #172, #203.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E4 — Continuidad del estampado TSA (Certicámara→GSE):** mensajes pendientes de sello se encolan y se estampan al restablecer; ninguno se cierra sin RFC 3161; cola/buffer de sellado (RF-10-D02, RN-10-D02 · [NORMATIVA]).
  - **E5 — No dejar el trámite "colgado":** timeout/respuesta vacía/OCSP vencido → reintento configurable + log + fallback a carga manual (RN-03-D06), nunca estado indefinido; manejo de error X-Road (RF-10-D01, RN-10-D01).

---

## Fichas de los UC secundarios nuevos (omitidos en los bundles)

> Cada uno con el RF/RN que lo respalda; el escenario detallado es `[INFERENCIA]` salvo donde se cita RF/RN explícito.

### UC-030 — Consultar certificado / paz y salvo en línea
- **Actor:** Ciudadano. **Respaldo:** RF-B3-124, RN-B3-017/037; Decreto 2106 Art.19. **[Omitido — había RF pero no UC]**
- **Flujo:** 1. Consulta de registros públicos → 2. Ingresa número de predio/identificador → 3. Estado de deuda/constancia sin carné físico → 4. Descarga.
- **Excepciones:** E1 Predio inexistente → mensaje claro. E2 Sistema de rentas no disponible → reintento. E3 Consulta gratuita (RF-B3-124).

### UC-031 — Recuperar / restablecer contraseña
- **Actor:** Ciudadano. **Respaldo:** RF-B3-074 ("Olvidé mi contraseña"). **[Vacío — el módulo lo menciona, sin UC]**
- **Flujo:** 1. "Olvidé mi contraseña" → 2. Correo/cédula → 3. Enlace/OTP → 4. Nueva contraseña ≥8 → 5. Confirma.
- **Excepciones:** E1 Correo no registrado → mensaje genérico (no revelar existencia de cuenta) [INFERENCIA seguridad]. E2 Enlace expirado → reenvío. E3 5 intentos → bloqueo (RF-B3-132).

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E1' — Token de recuperación de un solo uso y expiración (15 min); restablecer invalida sesiones activas:** flujo "Olvidé contraseña" endurecido (RF-09-D02, RN-09-D03 · [DOMINIO]).

### UC-032 — Cancelar / reprogramar cita
- **Actor:** Ciudadano. **Respaldo:** RF-B1-030 ("consulta/cancelación por número de confirmación"). **[Omitido]**
- **Flujo:** 1. Ingresa código → 2. Ve detalle → 3. Cancela o reprograma → 4. Libera cupo → 5. Confirmación.
- **Excepciones:** E1 Código inválido. E2 Cancelación fuera de plazo [INFERENCIA]. E3 Concurrencia al liberar/tomar cupo.

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E3' — Doble reserva / concurrencia del último cupo** (cierra el vacío marcado en UC-011 E1 y UC-032 E3): reserva atómica, la concurrente recibe "cupo no disponible"; bloqueo atómico de cupo (RF-06-D03, RN-06-D01 · [DOMINIO]).
  - **A1 — Reprogramación** (update, antes solo cancelar): elegir nueva franja liberando la anterior (RF-06-D02, RN-06-D02).
  - **E4 — No-show:** cita no atendida → marca "no-show", libera cupo y alimenta métricas; recordatorio previo (RF-06-D04 · [DOMINIO]).

### UC-033 — Revocar consentimiento / suprimir cuenta
- **Actor:** Ciudadano. **Respaldo:** RF-B1-008, RF-B2-052. **[Parcial en UC-010/UC-016; se aísla]**
- **Flujo:** 1. Privacidad del perfil → 2. Revoca consentimiento → 3. Suprime o registra imposibilidad legal con justificación (≤15 días).
- **Excepciones:** E1 Imposibilidad legal → respuesta motivada (RF-B2-052).

### UC-034 — Actualizar / Rectificar datos del perfil
- **Actor:** Ciudadano autenticado. **Respaldo:** RF-B2-050 (≤5 días hábiles). **[Omitido]**
- **Flujo:** 1. Perfil → editar dato → 2. Confirma → 3. Reflejo en ≤5 días hábiles.
- **Excepciones:** E1 Dato verificable por ANI no editable libremente [INFERENCIA]. E2 Cambio de correo → reverificación OTP [INFERENCIA].

### UC-035 — Gestionar y enrutar PQRSD (back-office)
- **Actor:** Funcionario de dependencia. **Respaldo:** RF-B1-036, RF-B2-095. **[Cierra PREGUNTA ABIERTA fase2-p1 §10]**
- **Flujo:** 1. Recibe PQRSD asignada → 2. Verifica competencia → 3. Asigna responsable → 4. Gestiona → 5. Registra actuaciones en SGDEA.
- **Excepciones:** E1 No competencia → traslado (UC-037). E2 Dos dependencias → expediente compartido (RF-B2-095).
- **PREGUNTA ABIERTA:** ¿enrutamiento automático por "dependencia destinataria" o validación manual?

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **A3 — Enrutamiento/reasignación por competencia** (automático o manual) y reasignación entre dependencias: asignación, reasignación, expediente compartido (RF-04-D01, RN-04-D03 · [WEB] L47456).
  - **E3 — Cómputo de plazo diferenciado por tipo** (general 15 / información 10 / consulta 30 / entre autoridades 10) sobre calendario hábil del Distrito: semáforo aplica el plazo correcto por tipo (RF-04-D03, RN-04-D01 · [NORMATIVA] Ley 1755).
  - **E4 — Identidad reservada del denunciante:** no exponer datos; traslado con reserva a Procuraduría cuando aplique; back-office oculta peticionario (RN-04-D06 · [NORMATIVA]).
  - *Precondición añadida (calendario hábil):* "El cómputo de plazos usa el **calendario hábil único del Distrito** (RN-TX-D01), excluyendo festivos nacionales y distritales."

### UC-036 — Responder PQRSD / Gestionar vencimiento
- **Actor:** Funcionario responsable. **Respaldo:** HU-B3-022, RF-B3-119/154. **[Omitido]**
- **Flujo:** 1. Redacta respuesta → 2. Firma electrónica (UC-039) → 3. Notifica (UC-008) → 4. Cierra el caso.
- **Alternos/Excepciones:** A1 Alerta a 3 días hábiles del vencimiento (HU-B3-022). E1 Plazo vencido → escalamiento [INFERENCIA]. E2 Cómputo con días hábiles del Distrito (RF-B3-119).

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **A2 — Cierre con medición de cumplimiento:** al cargar respuesta → estado "Respondido" + marca dentro/fuera de término; no se cierra sin respuesta registrada (RF-04-D02, RN-04-D04).
  - **A3 — Prórroga del término** → enlaza UC-046: ampliación antes del vencimiento (RF-04-D03, RN-04-D02).
  - **E3 — Notificación CPACA con constancia** (correo procesal/SMS/correo certificado/edicto) y fecha de notificación; preferencia electrónica si hay dirección procesal autorizada; acuse y registro (RF-04-D05, RN-04-D05 · [NORMATIVA] Ley 1437 · [WEB]).
  - *Postcondición añadida (cierre):* "Una PQRSD no se cierra sin respuesta registrada; al cerrar se marca dentro/fuera de término legal." (RN-04-D04).
  - *Precondición añadida (calendario hábil):* "El cómputo de plazos usa el **calendario hábil único del Distrito** (RN-TX-D01), excluyendo festivos nacionales y distritales."

### UC-037 — Trasladar PQRSD por no competencia
- **Actor:** Sistema/Funcionario. **Respaldo:** RF-B2-069, flujo PQRSD fase2-p3. **[Omitido]**
- **Flujo:** 1. Detecta no competencia → 2. Traslada a entidad receptora → 3. Informa fecha y entidad → 4. Actualiza estado.
- **Excepciones:** E1 Entidad receptora desconocida → gestión manual [INFERENCIA].

- **Delta profundo (`jose-casos-uso-profundo`, 2026-06-04):**
  - **E2 — Plazo del traslado:** debe efectuarse dentro de los 5 días siguientes a la recepción, notificando entidad receptora y fecha; semáforo de traslado (RN-04-D03 · [NORMATIVA] Ley 1755 Art.21).

### UC-038 — Cerrar sesión / SLO
- **Actor:** Ciudadano. **Respaldo:** RF-B2-015, RF-B3-108. **[Omitido]**
- **Flujo:** 1. Cierra sesión → 2. SLO en todos los servicios → 3. Revocación de tokens (RF-B2-013).
- **Excepciones:** E1 Timeout automático (900 s, RNF-B1-023). E2 Cierre parcial si un servicio no responde [INFERENCIA].

### UC-039 — Firmar electrónicamente acto administrativo
- **Actor:** Funcionario. **Respaldo:** RF-B3-155, RN-B2-027, RN-B3-016. **[Omitido — solo había HU-B3-017]**
- **Flujo:** 1. Genera documento → 2. Firma con certificado digital → 3. Validez jurídica → 4. Archiva en SGDEA con índice firmado.
- **Excepciones:** E1 Certificado vencido → bloquea firma [INFERENCIA]. E2 Norma exige firma autógrafa → excepción (RN-B3-016 excepción).

### UC-040 — Consultar información tributaria / calendario
- **Actor:** Contribuyente. **Respaldo:** RF-B1-018, RF-B3-089/151. **[Omitido — solo HU-B1-007/HU-B3-025]**
- **Flujo:** 1. Sección tributaria → 2. Selecciona impuesto (predial/ICA) → 3. Elementos del tributo + calendario → 4. Enlace a liquidación/pago (UC-007).
- **Excepciones:** E1 Calendario no actualizado para la vigencia → inconsistencia (RF-B3-151). E2 Liquidación no disponible → contacto.

### UC-041 — Aprobar / Eliminar contenido con flujo editorial y conservación
- **Actor:** Administrador de gestión documental. **Respaldo:** RF-B1-077, RN-B1-015. **[Omitido — solo HU-B1-021]**
- **Flujo:** 1. Editor solicita eliminar → 2. Sistema exige justificación + aprobación superior → 3. Verifica TRD → 4. Ejecuta o rechaza → 5. Log.
- **Excepciones:** E1 Sin justificación → rechazada (RN-B1-015). E2 TRD ordena eliminación → se ejecuta conforme a TRD.

### UC-042 — Subsanar / aportar documentación requerida en un trámite
- **Actor principal:** Ciudadano. **Secundarios:** Funcionario de la dependencia, SGDEA, motor de notificaciones, calendario hábil del Distrito.
- **Precondiciones:** existe un trámite radicado en curso (Etapa 3); la entidad marcó "requiere subsanación" indicando qué falta y el plazo.
- **Postcondiciones (éxito):** documento incorporado al expediente, trámite reanudado, plazo recalculado desde la entrega, constancia registrada.
- **Flujo principal:**
  1. El ciudadano recibe notificación de requerimiento por el canal autorizado (UC-008).
  2. Ingresa a "Mis trámites" → trámite en estado "Requiere subsanación".
  3. Visualiza lo solicitado y el plazo para subsanar.
  4. Carga el/los documentos (validación de adjuntos de trámite — RF-03-D04).
  5. Confirma envío.
  6. El sistema marca "Subsanado", reanuda el trámite y **recalcula el plazo desde la entrega** (RN-03-D05).
- **Flujos alternos:**
  - **A1 — Subsanación parcial:** entrega parte de lo requerido → el trámite sigue "requiere subsanación" por lo pendiente [INFERENCIA].
- **Flujos de excepción:**
  - **E1 — Vence el plazo de subsanación sin respuesta:** el sistema declara **desistimiento por no subsanar** (RN-03-D05; Ley 1755 Art.17) y notifica → enlaza UC-043.
  - **E2 — Adjunto con MIME falso / malware:** rechazo + registro (RF-03-D04, RN-03-D04).
  - **E3 — Sesión expirada (900 s):** reautenticar; preservar lo ya cargado si viable (RNF-B1-023) [INFERENCIA].
  - **E4 — Acceso al requerimiento de otro ciudadano (IDOR):** 403 + log (RF-09-D01) → ver UC-005 E7 (nuevo).
- **RF/RN:** RF-03-D05/D04; RN-03-D05/D04; RN-TX-D01 (calendario hábil). **Procedencia:** [NORMATIVA]+[WEB] L2665.

### UC-043 — Desistir de un trámite en línea
- **Actor principal:** Ciudadano. **Secundarios:** SGDEA, Tesorería (si hubo pago).
- **Precondiciones:** trámite radicado, no resuelto.
- **Postcondiciones:** estado "Desistido", constancia en el expediente, evaluación de reembolso según política del trámite.
- **Flujo principal:**
  1. Ciudadano en "Mis trámites" → trámite en curso → "Desistir".
  2. El sistema muestra advertencia de consecuencias (cierre, no avance).
  3. Confirma (doble confirmación).
  4. Estado → "Desistido"; queda registrado con sello temporal (RN-TX-D02) en el expediente (RN-03-D07).
- **Flujos de excepción:**
  - **E1 — Trámite ya resuelto:** no se permite desistir; mensaje informativo [INFERENCIA].
  - **E2 — Hubo pago previo:** se sujeta a la **política de reembolso del trámite** (RN-03-D07) → **[PREGUNTA ABIERTA]** ¿hay reembolso? (Tesorería + Líder de trámites).
  - **E3 — IDOR sobre trámite ajeno:** 403 + log (RF-09-D01).
- **RF/RN:** RF-03-D07; RN-03-D07. **Procedencia:** [NORMATIVA] Ley 1755 Art.18 · [WEB] L81936.

### UC-044 — Reconocer silencio administrativo positivo (SAP)
- **Actor principal:** Sistema (cómputo automático). **Secundarios:** Funcionario responsable, Secretaría Jurídica, Ciudadano.
- **Precondiciones:** el trámite está catalogado como sujeto a SAP con su término legal; calendario hábil configurado.
- **Postcondiciones:** vencimiento marcado, efecto "concedido" registrado, ciudadano notificado, alerta al responsable.
- **Flujo principal:**
  1. El cómputo del término del trámite con SAP llega a vencimiento sin resolución expresa.
  2. El sistema marca el vencimiento y aplica el **efecto positivo: se entiende concedido** (RN-03-D08; Ley 1755 Art.84).
  3. Notifica al ciudadano y alerta al responsable y a Secretaría Jurídica.
  4. Registra la constancia con sello temporal (RN-TX-D02).
- **Flujos de excepción:**
  - **E1 — Trámite NO sujeto a SAP:** el vencimiento dispara escalamiento ordinario, no efecto positivo (ver UC-036/UC-046).
  - **E2 — Catálogo de trámites con SAP incompleto:** **[PREGUNTA ABIERTA]** ¿qué trámites de la Alcaldía están sujetos a SAP y con qué término? (Secretaría Jurídica).
- **RF/RN:** RN-03-D08; RN-TX-D01/02. **Procedencia:** [NORMATIVA] Ley 1755 Art.83-85; Ley 2052 Art.12 · [WEB] L4759/L50184.

### UC-045 — Reanudar trámite guardado (borrador)
- **Actor principal:** Ciudadano autenticado.
- **Precondiciones:** existe un trámite multi-paso guardado como borrador y no expirado.
- **Postcondiciones:** el trámite se retoma desde el último paso guardado con datos y adjuntos intactos.
- **Flujo principal:**
  1. Ciudadano vuelve a "Mis trámites" → borrador en curso.
  2. El sistema restaura datos del stepper y adjuntos ya cargados (RF-03-D03).
  3. Continúa desde el paso guardado.
- **Flujos de excepción:**
  - **E1 — Borrador expirado por política de retención:** se informa y se descarta (RNF-03-D02) → **[PREGUNTA ABIERTA]** plazo de retención (Líder de trámites + Protección de Datos).
  - **E2 — La ficha del trámite cambió desde el guardado:** se avisa y se revalida [INFERENCIA].
- **RF/RN:** RF-03-D03; RNF-03-D02; RN-TX-D03 (purga). **Procedencia:** [DOMINIO]. **Relación:** convierte UC-004 E4/E5 en flujo de primera clase.

### UC-046 — Prorrogar el término de respuesta de una PQRSD
- **Actor principal:** Funcionario responsable. **Secundarios:** Ciudadano, motor de notificaciones, calendario hábil.
- **Precondiciones:** PQRSD en trámite, dentro del término, con causal de imposibilidad de responder a tiempo.
- **Postcondiciones:** término ampliado con nueva fecha, peticionario notificado **antes del vencimiento original**, sin exceder el límite legal.
- **Flujo principal:**
  1. Funcionario registra la prórroga con motivación y nueva fecha (RN-04-D02).
  2. El sistema valida que la nueva fecha no exceda el límite legal y que la notificación ocurra antes del vencimiento.
  3. Notifica al peticionario (UC-008) y actualiza el semáforo de vencimiento.
- **Flujos de excepción:**
  - **E1 — Prórroga solicitada después del vencimiento:** rechazada; cuenta como respuesta extemporánea (RN-04-D02).
  - **E2 — Nueva fecha excede el máximo legal:** bloqueada (RN-04-D02).
- **RF/RN:** RF-04-D03; RN-04-D02; RN-TX-D01. **Procedencia:** [NORMATIVA] Ley 1755 Art.14 par. · [WEB] L2665.

### UC-047 — Administrar la agenda de citas (CMS)
- **Actor principal:** Administrador / Funcionario de atención al ciudadano.
- **Precondiciones:** rol con permiso de configuración de agenda.
- **Postcondiciones:** oferta de disponibilidad (servicios, franjas, cupos, bloqueos) publicada y disponible para UC-011.
- **Flujo principal:**
  1. Define dependencias/servicios agendables.
  2. Configura franjas horarias y **cupos por franja**.
  3. Marca días no laborables y bloqueos de fecha.
  4. Publica la oferta (RF-06-D01).
- **Flujos de excepción:**
  - **E1 — Reducir cupos por debajo de citas ya reservadas:** el sistema impide o exige reprogramar las afectadas [INFERENCIA].
  - **E2 — [PREGUNTA ABIERTA]** ¿agenda propia o integración con sistema de turnos existente? (Oficina de Atención).
- **RF/RN:** RF-06-D01; RN-06-D01/02. **Procedencia:** [DOMINIO]. **Relación:** es la precondición faltante de UC-011 (no existía "quién crea la disponibilidad").

### UC-048 — Gestionar el ciclo editorial con estados y rechazo (SoD)
- **Actor principal:** Editor (crea/edita) y Administrador (aprueba/publica) — **roles segregados**.
- **Precondiciones:** SoD configurada: el creador no puede aprobar lo propio (RN-09-D02).
- **Postcondiciones:** contenido en estado consistente (Borrador/Pendiente/Publicado/Archivado) con trazabilidad de quién hizo qué.
- **Flujo principal:**
  1. Editor crea/edita → estado **Borrador**.
  2. Envía a revisión → **Pendiente de aprobación**.
  3. Administrador (distinto del editor) aprueba → **Publicado**; o rechaza → vuelve a **Borrador** con comentario.
  4. Despublica/archiva → **Archivado** (RF-12-D01).
- **Flujos de excepción:**
  - **E1 — Auto-aprobación:** el creador intenta aprobar su propio contenido → bloqueado por **SoD** (RN-09-D02, RF-09-D03).
  - **E2 — Edición concurrente del mismo contenido:** bloqueo optimista/pesimista, aviso de conflicto (RN-12-D05, RNF-12-D01) → **cierra UC-018 E4 (vacío de concurrencia)**.
  - **E3 — Publicar sin metadato obligatorio (vigencia):** bloqueado (HU-B3-018).
- **RF/RN:** RF-12-D01, RF-09-D03; RN-12-D01, RN-09-D02. **Procedencia:** [DOMINIO]+[INFERENCIA]. **Relación:** formaliza el A1 "flujo editorial" de UC-018 como UC con estados.

### UC-049 — Administrar y versionar datasets (enriquece UC-025)
- **Actor principal:** Administrador del portal.
- **Postcondiciones:** dataset editado/versionado/despublicado con control de frescura.
- **Flujo principal:** 1. Edita metadatos / reemplaza archivo → 2. El sistema versiona conservando la versión previa → 3. Configura/actualiza la frecuencia → 4. Publica nueva versión (RF-11-D01).
- **Flujos de excepción:**
  - **E1 — Frecuencia vencida (dataset desactualizado):** alerta al responsable de datos (RN-11-D01).
  - **E2 — Archivo mal formado / metadatos incompletos:** no federa a datos.gov.co (RNF-11-D01, RN-11-D02).
- **RF/RN:** RF-11-D01; RN-11-D01/02. **Procedencia:** [DOMINIO]+[NORMATIVA] Res.1519 Anexo 4.

### UC-050 — Administrar transparencia con versionado y alertas (enriquece UC-018)
- **Actor principal:** Administrador/Editor de transparencia.
- **Postcondiciones:** documento versionado sin romper URL de fuente única; alertas de vencimiento operando.
- **Flujo principal:** 1. Reemplaza un documento publicado → 2. El sistema conserva la versión anterior con su fecha (historial) y mantiene la URL (RF-02-D01, RN-02-D01) → 3. Programa/recibe alerta N días antes del plazo legal de publicaciones obligatorias (RF-02-D02, RN-02-D02).
- **Flujos de excepción:**
  - **E1 — Caída de integración (SECOP/SIGEP/SUIN/SUCOP/KOGUI):** mensaje de indisponibilidad + log; NO cuenta como vínculo roto (RF-02-D03, RN-02-D03) → enriquece UC-012 E1.
  - **E2 — Plazo legal (31-ene, trimestrales) próximo sin publicar:** alerta al administrador de cumplimiento (RN-02-D02), que se refleja en UC-021 (ITA).
- **RF/RN:** RF-02-D01/02/03; RN-02-D01/02/03/04. **Procedencia:** [NORMATIVA] Res.1519 Anexo 2.

---

## Hallazgos transversales (delta de la segunda pasada)

### Vacíos de excepción relevantes (no estaban en los UC de una línea)
- **Concurrencia** ausente en los 52 UC base: agendamiento (UC-011 E1), edición de contenido (UC-018 E4), liberación de cupos (UC-032). → documentar control de concurrencia.
- **5 intentos fallidos** solo estaba en login admin (UC-017); faltaba en login ciudadano (UC-005 E1) y recuperación (UC-031 E3).
- **SCD/X-Road no disponible** e **interoperabilidad caída** sin tratamiento → ahora UC-004 E1/E2, UC-005 E3, UC-029 E1 con la excepción RN-B2-004 (exigir documento transitoriamente).
- **Pago interrumpido/doble cobro** (UC-007 E3) sin idempotencia.
- **Radicado inexistente** (UC-002 E1) y **MIME falso** (UC-004 E6) ausentes.

### Conflictos vs documento (a resolver en diseño)
| UC | Implementación/artefacto dice | Documento dice | Resolución |
|----|-------------------------------|----------------|------------|
| UC-001 | #239 restringe adjuntos a PDF/JPG/PNG ≤10 MB | RN-B1-010/#241: sin restricción | **C-01:** prevalece la norma; única limitación = capacidad técnica documentada |
| UC-004 | Portal ≥95% (#209) vs trámites ≥98% (#128/#217) | RNF-B1-001, RNF-B2-004/005 | **C-02:** umbral por componente (98% trámites, 95% portal); armonizar SLA |
| UC-001/005 | Captcha obligatorio (RF-B1-058) | WCAG 2.1 AA (RN-B1-021) | Captcha accesible con alternativa de audio; no barrera |
| UC-019 | LSC obligatoria (RN-B3-003) | Aplica a "Gobierno Nacional" | **PREGUNTA ABIERTA:** validar aplicabilidad a entidad territorial |
| UC-027 | Clase miembro "CO" (#126) vs "GOB/PRIV" (#124) | Guías de distinto año | **PREGUNTA ABIERTA:** verificar versión vigente con la AND |

### Estimación de completitud
- **Identificación de casos:** ~78% (los 52 UC base estaban bien identificados y deduplicados).
- **Estructura de fichas:** ~25% en el artefacto base (carecían de precondiciones, postcondiciones, alternos y excepciones) → causa raíz del hallazgo de auditoría (restricción 9).
- **Delta:** 12 UC secundarios nuevos + ~40 flujos de excepción/alternos no documentados.
- **PREGUNTAS ABIERTAS a cerrar:** LSC territorial (UC-019), clase miembro X-Road (UC-027), enrutamiento PQRSD automático vs manual (UC-035).

> **Nota (integración delta profundo, 2026-06-04):** 9 UC nuevos (UC-042..UC-050) y ~38 flujos alternos/excepción + pre/postcondiciones integrados desde `uc-delta-profundo.md`. Las tablas `## 5. Casos de Uso (UC)` de los módulos usan el esquema bundle (UC-Bn-NNN) y no se modifican; la trazabilidad consolidada vive en este archivo.

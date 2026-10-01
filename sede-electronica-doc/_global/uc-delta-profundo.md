# Casos de Uso — Delta de la segunda pasada profunda (`jose-casos-uso-profundo`)

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Fecha:** 2026-06-04
> **Encuadre:** verificación adversarial sobre `casos-uso-detallados.md` (41 UC con fichas completas) cruzado contra los deltas recién integrados `rf-rnf-delta-profundo.md` (37 RF + 18 RNF) y `rn-delta-profundo.md` (54 RN). El artefacto base de UC es de alta calidad: ya tiene precondiciones, postcondiciones, alternos y excepciones, y ya marcó varios vacíos como `[INFERENCIA — vacío]`. Esta pasada **cierra esos vacíos con el RF/RN que ahora los respalda**, agrega los flujos de excepción que los nuevos RF/RNF exigen y propone los UC secundarios que el delta motiva.
> **Procedencia (sin refs `#NN`, el corpus OCR se borró):** **[NORMATIVA]** ley/decreto citado · **[DOMINIO]** invariante estándar de transacciones/concurrencia/control de acceso · **[WEB]** verificado en `extracto-web-docs.md` (líneas citadas) · **[INFERENCIA]** derivado validable · **[PREGUNTA ABIERTA]**.
> **Estado:** DELTA pendiente de integración a `casos-uso-detallados.md`. NO se editó el artefacto base.

---

## 1. UC NUEVOS detectados (omitidos)

| ID propuesto | Nombre | Actor principal | Objetivo | Procedencia |
|---|---|---|---|---|
| UC-042 | Subsanar / aportar documentación requerida en un trámite | Ciudadano | Responder a un requerimiento de la entidad cargando lo faltante y reanudar el trámite con plazo recalculado | RF-03-D05, RN-03-D05 · [NORMATIVA] Ley 1755 Art.17 · [WEB] L2665 |
| UC-043 | Desistir de un trámite en línea | Ciudadano | Cancelar un trámite antes de su resolución dejando constancia en el expediente | RF-03-D07, RN-03-D07 · [NORMATIVA] Ley 1755 Art.18 · [WEB] L81936 |
| UC-044 | Reconocer silencio administrativo positivo | Sistema / Funcionario | Marcar el vencimiento del término en trámites con SAP y registrar el efecto "concedido" | RN-03-D08 · [NORMATIVA] Ley 1755 Art.83-85; Ley 2052 Art.12 · [WEB] L4759/L50184 |
| UC-045 | Reanudar trámite guardado (borrador) | Ciudadano | Retomar un trámite multi-paso a medio diligenciar sin perder datos ni adjuntos | RF-03-D03, RNF-03-D02 · [DOMINIO] |
| UC-046 | Prorrogar el término de respuesta de una PQRSD | Funcionario responsable | Ampliar el plazo informando al peticionario antes del vencimiento | RF-04-D03, RN-04-D02 · [NORMATIVA] Ley 1755 Art.14 par. · [WEB] L2665 |
| UC-047 | Administrar la agenda de citas (CMS) | Administrador / Funcionario de atención | Definir servicios agendables, franjas, cupos y bloqueos de fecha que alimentan UC-011 | RF-06-D01 · [DOMINIO] |
| UC-048 | Gestionar el ciclo editorial con estados y rechazo | Editor / Administrador (con SoD) | Mover un contenido por Borrador→Pendiente→Publicado→Archivado con devolución a edición | RF-12-D01, RN-12-D01, RN-09-D02 · [DOMINIO]+[INFERENCIA] |
| UC-049 | Administrar y versionar datasets de datos abiertos | Administrador del portal | Editar metadatos, versionar, despublicar y controlar frescura del dataset | RF-11-D01, RN-11-D01 · [DOMINIO] (enriquece, no duplica, UC-025) |
| UC-050 | Administrar publicaciones de transparencia con versionado y alertas | Administrador/Editor de transparencia | CRUD + versionado de documentos y alerta de vencimiento de publicaciones obligatorias | RF-02-D01, RF-02-D02, RN-02-D01/02 · [NORMATIVA] Res.1519 Anexo 2 |

> Nota: UC-042..UC-046 son flujos de negocio nuevos con actor y objetivo propios (justifican ficha). UC-047..UC-050 son UC de administración/configuración omitidos que el delta de RF "CRUD administrativo" exige; podrían integrarse como ampliación de los UC admin existentes (UC-011/UC-018/UC-025/UC-048), pero se aíslan para no perder trazabilidad. Si el creador prefiere, UC-049/UC-050 se absorben en UC-025/UC-018 como flujos U/D + versionado.

---

### Fichas de los UC nuevos

#### UC-042 — Subsanar / aportar documentación requerida en un trámite
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

#### UC-043 — Desistir de un trámite en línea
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

#### UC-044 — Reconocer silencio administrativo positivo (SAP)
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

#### UC-045 — Reanudar trámite guardado (borrador)
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

#### UC-046 — Prorrogar el término de respuesta de una PQRSD
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

#### UC-047 — Administrar la agenda de citas (CMS)
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

#### UC-048 — Gestionar el ciclo editorial con estados y rechazo (SoD)
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

#### UC-049 — Administrar y versionar datasets (enriquece UC-025)
- **Actor principal:** Administrador del portal.
- **Postcondiciones:** dataset editado/versionado/despublicado con control de frescura.
- **Flujo principal:** 1. Edita metadatos / reemplaza archivo → 2. El sistema versiona conservando la versión previa → 3. Configura/actualiza la frecuencia → 4. Publica nueva versión (RF-11-D01).
- **Flujos de excepción:**
  - **E1 — Frecuencia vencida (dataset desactualizado):** alerta al responsable de datos (RN-11-D01).
  - **E2 — Archivo mal formado / metadatos incompletos:** no federa a datos.gov.co (RNF-11-D01, RN-11-D02).
- **RF/RN:** RF-11-D01; RN-11-D01/02. **Procedencia:** [DOMINIO]+[NORMATIVA] Res.1519 Anexo 4.

#### UC-050 — Administrar transparencia con versionado y alertas (enriquece UC-018)
- **Actor principal:** Administrador/Editor de transparencia.
- **Postcondiciones:** documento versionado sin romper URL de fuente única; alertas de vencimiento operando.
- **Flujo principal:** 1. Reemplaza un documento publicado → 2. El sistema conserva la versión anterior con su fecha (historial) y mantiene la URL (RF-02-D01, RN-02-D01) → 3. Programa/recibe alerta N días antes del plazo legal de publicaciones obligatorias (RF-02-D02, RN-02-D02).
- **Flujos de excepción:**
  - **E1 — Caída de integración (SECOP/SIGEP/SUIN/SUCOP/KOGUI):** mensaje de indisponibilidad + log; NO cuenta como vínculo roto (RF-02-D03, RN-02-D03) → enriquece UC-012 E1.
  - **E2 — Plazo legal (31-ene, trimestrales) próximo sin publicar:** alerta al administrador de cumplimiento (RN-02-D02), que se refleja en UC-021 (ITA).
- **RF/RN:** RF-02-D01/02/03; RN-02-D01/02/03/04. **Procedencia:** [NORMATIVA] Res.1519 Anexo 2.

---

## 2. FLUJOS ALTERNOS / EXCEPCIÓN que faltaban en UC existentes

| UC (ID) | Tipo | ID | Escenario | Pasos / comportamiento | Evidencia |
|---|---|---|---|---|---|
| UC-007 | Excepción | E3' | **Pago interrumpido / doble clic / timeout PSE** (cierra el vacío que la ficha marcaba `[INFERENCIA — vacío]`) | Con **clave de idempotencia** por transacción: el reintento del mismo formulario devuelve el comprobante existente, nunca un segundo cargo ni segundo radicado | RF-03-D01, RN-03-D01 · [DOMINIO] |
| UC-007 | Excepción | E5 | **Conciliación de estados de pasarela** `aprobado/rechazado/pendiente/fallido` | `pendiente`→ trámite "en espera de confirmación", reconsulta cada N min; solo `aprobado` libera la etapa; `rechazado` no consume radicado/borrador | RF-03-D02, RN-03-D02 · [DOMINIO]+[WEB] |
| UC-007 | Excepción | E6 | **Recargo por canal digital en el punto de pago** | Prohibido cobrar la pasarela como recargo al ciudadano | RN-03-D03 · [NORMATIVA] Dec.2106 Art.7 |
| UC-007 | Postcondición | — | El trámite avanza **solo con pago `aprobado`**; estados intermedios no avanzan | (ver §3) | RN-03-D02 |
| UC-004 | Excepción | E2' | **X-Road caído durante verificación automática:** además de "exigir documento", **registrar la falla técnica documentada** y habilitar carga manual temporal con constancia | reintento/cola → fallback documentado | RF-03-D06, RN-03-D06 · [NORMATIVA] |
| UC-004 | Alterno | A4 | **Subsanación solicitada en Etapa 3** → enlaza UC-042 | la entidad marca "requiere subsanación"; trámite se suspende | RF-03-D05 |
| UC-004 | Alterno | A5 | **Desistir** → enlaza UC-043 | opción de desistimiento antes de resolución | RF-03-D07 |
| UC-004 | Alterno | A6 | **Guardar borrador / reanudar** → enlaza UC-045 | persistencia parcial del stepper | RF-03-D03 |
| UC-004 | Excepción | E7 | **IDOR:** acceso a trámite ajeno por ID en URL | 403 + log; control de pertenencia por objeto | RF-09-D01, RN-09-D01 · [DOMINIO] OWASP A01 |
| UC-004 | Excepción | E8 | **Nivel de autenticación insuficiente para el trámite** | bloqueo de inicio si el nivel del ciudadano < nivel exigido | RN-TX-D04 (C-08, **[PREGUNTA ABIERTA]** matriz) |
| UC-005 | Excepción | E7 | **Caída del SCD/OIDC (Articulador):** mensaje "autenticación no disponible", **no bloquea contenido público**; `state` no coincidente en callback → rechazo + log | manejo de error de federación | RF-09-D04, RN-09-D06 · [DOMINIO] |
| UC-009 | Excepción | E4 | **IDOR en Carpeta Ciudadana:** consulta de datos de otro titular | 403 + log; control de pertenencia | RF-09-D01, RN-09-D01 |
| UC-002 | Excepción | E4 | **IDOR en consulta por radicado:** ciudadano autenticado abre radicado ajeno cambiando el número | 403 + log (los radicados anónimos siguen siendo públicos, A1 sin cambio) | RF-09-D01, RN-09-D01 |
| UC-035 | Alterno | A3 | **Enrutamiento/reasignación por competencia** (automático o manual) y reasignación entre dependencias | asignación, reasignación, expediente compartido | RF-04-D01, RN-04-D03 · [WEB] L47456 |
| UC-035 | Excepción | E3 | **Cómputo de plazo diferenciado por tipo** (general 15 / información 10 / consulta 30 / entre autoridades 10) sobre calendario hábil del Distrito | semáforo aplica el plazo correcto por tipo | RF-04-D03, RN-04-D01 · [NORMATIVA] Ley 1755 |
| UC-035 | Excepción | E4 | **Identidad reservada del denunciante:** no exponer datos; traslado con reserva a Procuraduría cuando aplique | back-office oculta peticionario | RN-04-D06 · [NORMATIVA] |
| UC-036 | Alterno | A2 | **Cierre con medición de cumplimiento:** al cargar respuesta → estado "Respondido" + marca dentro/fuera de término; no se cierra sin respuesta registrada | RF-04-D02, RN-04-D04 |
| UC-036 | Alterno | A3 | **Prórroga del término** → enlaza UC-046 | ampliación antes del vencimiento | RF-04-D03, RN-04-D02 |
| UC-036 | Excepción | E3 | **Notificación CPACA con constancia** (correo procesal/SMS/correo certificado/edicto) y fecha de notificación; preferencia electrónica si hay dirección procesal autorizada | acuse y registro | RF-04-D05, RN-04-D05 · [NORMATIVA] Ley 1437 · [WEB] |
| UC-037 | Excepción | E2 | **Plazo del traslado:** debe efectuarse dentro de los 5 días siguientes a la recepción, notificando entidad receptora y fecha | semáforo de traslado | RN-04-D03 · [NORMATIVA] Ley 1755 Art.21 |
| UC-032 | Excepción | E3' | **Doble reserva / concurrencia del último cupo** (cierra el vacío marcado en UC-011 E1 y UC-032 E3): reserva atómica, la concurrente recibe "cupo no disponible" | bloqueo atómico de cupo | RF-06-D03, RN-06-D01 · [DOMINIO] |
| UC-032 | Alterno | A1 | **Reprogramación** (update, antes solo cancelar): elegir nueva franja liberando la anterior | RF-06-D02, RN-06-D02 |
| UC-032 | Excepción | E4 | **No-show:** cita no atendida → marca "no-show", libera cupo y alimenta métricas; recordatorio previo | RF-06-D04 · [DOMINIO] |
| UC-011 | Excepción | E1' | **Concurrencia de cupo resuelta** (no ya `[vacío]`): reserva atómica | (mismo control que UC-032 E3') | RF-06-D03, RN-06-D01 |
| UC-011 | Precondición | — | La disponibilidad proviene de **UC-047** (administración de agenda); sin oferta configurada no hay calendario | RF-06-D01 |
| UC-018 | Excepción | E4' | **Edición concurrente resuelta** (no ya `[vacío]`): bloqueo optimista/pesimista | RN-12-D05, RNF-12-D01 |
| UC-018 | Alterno | A3 | **Estados editoriales con rechazo + SoD** → formalizado en UC-048 | RF-12-D01, RN-09-D02 |
| UC-020 | Alterno | A2 | **Baja segura con retención de auditoría:** revocar accesos/tokens ≤1 día hábil **sin borrado físico**; eventos de auditoría permanecen consultables | RF-12-D02, RN-12-D02 · [NORMATIVA] |
| UC-020 | Excepción | E4 | **MFA obligatorio para administradores del CMS** al crear/operar cuentas internas | la contraseña ≥8 no basta para roles internos | RN-09-D04 (C-07) · [DOMINIO] |
| UC-006 | Excepción | E6 | **Verificación de edad (<18):** exigir autorización del representante legal antes de recolectar datos del menor | bloqueo hasta autorización | RF-12-D04, RN-12-D03 · [NORMATIVA] Ley 1581 Art.7 |
| UC-031 | Excepción | E1' | **Token de recuperación de un solo uso y expiración (15 min); restablecer invalida sesiones activas** | flujo "Olvidé contraseña" endurecido | RF-09-D02, RN-09-D03 · [DOMINIO] |
| UC-008 | Excepción | E4 | **Reintento/cola multicanal:** si falla un canal (correo/SMS/push/CCD), reintentar y/o canal alterno autorizado, registrando estado de entrega; un rebote no se da por notificado | garantía de entrega | RF-12-D03, RN-12-D04 · [NORMATIVA] Ley 1437 |
| UC-025 | Alterno | A1 | **U/D + versionado + control de frescura** → formalizado en UC-049 | RF-11-D01, RN-11-D01 |
| UC-029 | Excepción | E4 | **Continuidad del estampado TSA (Certicámara→GSE):** mensajes pendientes de sello se encolan y se estampan al restablecer; ninguno se cierra sin RFC 3161 | cola/buffer de sellado | RF-10-D02, RN-10-D02 · [NORMATIVA] |
| UC-029 | Excepción | E5 | **No dejar el trámite "colgado":** timeout/respuesta vacía/OCSP vencido → reintento configurable + log + fallback a carga manual (RN-03-D06), nunca estado indefinido | manejo de error X-Road | RF-10-D01, RN-10-D01 |
| UC-016 | Alterno | A2 | **Caducidad/versionado del consentimiento:** >12 meses o cambio de política → re-solicitar banner; registro con versión y timestamp | persistencia versionada | RF-01-D01, RN-01-D01 · [NORMATIVA] Ley 1581 |
| UC-001 | Excepción | E9 | **Atomicidad/unicidad del radicado bajo concurrencia:** dos envíos simultáneos nunca comparten consecutivo (asignación atómica) | RNF-04-D02, RN-04-D07 · [DOMINIO] |
| UC-003 | Excepción | E4 | **Aviso de salida por lista blanca:** no mostrar modal en redirecciones a GOV.CO/SCD (evitar falso positivo) | RF-01-D03, RN-01-D03 |

---

## 3. PRECONDICIONES / POSTCONDICIONES / criterios faltantes

| UC (ID) | Qué le faltaba | Texto propuesto |
|---|---|---|
| UC-007 | Postcondición de avance condicionado al pago | "El trámite avanza a la siguiente etapa **únicamente si el pago queda en estado `aprobado`**; estados `pendiente/fallido/rechazado` mantienen el trámite en espera sin consumir radicado." (RN-03-D02) |
| UC-007 | Precondición de idempotencia | "Cada intento de pago se identifica con una **clave de idempotencia** única por transacción." (RN-03-D01) |
| UC-004 | Postcondición de cómputo | "Si el trámite está sujeto a SAP, el sistema computa el término y dispara UC-044 al vencimiento." (RN-03-D08) |
| UC-011 | Precondición de oferta | "Existe oferta de disponibilidad configurada por UC-047 (servicios, franjas, cupos)." (RF-06-D01) |
| UC-035/036 | Precondición de calendario | "El cómputo de plazos usa el **calendario hábil único del Distrito** (RN-TX-D01), excluyendo festivos nacionales y distritales." |
| UC-036 | Postcondición de cierre | "Una PQRSD no se cierra sin respuesta registrada; al cerrar se marca dentro/fuera de término legal." (RN-04-D04) |
| UC-005/004/009/002 | Precondición de autorización por objeto | "El acceso a cualquier recurso (radicado/trámite/PQRSD/cita/CCD) verifica **pertenencia del objeto al titular autenticado** (anti-IDOR), no solo el rol de módulo." (RN-09-D01) |
| UC-017/020 | Precondición de MFA interno | "El acceso de roles internos del CMS exige **segundo factor (MFA)**." (RN-09-D04, C-07) |
| UC-018/048 | Precondición de SoD | "Quien crea/edita un contenido NO puede aprobarlo/publicarlo (segregación de funciones)." (RN-09-D02) |
| UC-017/020/041/048 | Postcondición de log inmutable | "Todo evento de auditoría se escribe en log **append-only/encadenado por hash**, inalterable durante su retención (5 años)." (RN-09-D05) |
| Transversal (UC-001/005/007/008/039) | Sello temporal legal | "Toda evidencia con valor legal usa la **Hora Legal de Colombia (INM)** y, donde aplique, estampa TSA RFC 3161." (RN-TX-D02) |
| UC-045 | Postcondición de retención | "El borrador se conserva por el período de retención definido; vencido, se purga/anonimiza." (RN-TX-D03 — **[PREGUNTA ABIERTA]**) |

---

## 4. CONFLICTOS vs documento (residuales — ya consolidados en C-06/C-07/C-08)

| UC | Artefacto/ficha dice | Documento/delta dice | Resolución |
|---|---|---|---|
| UC-001 / UC-004 | UC-001 (PQRSD) "adjuntos sin restricción"; UC-004 (trámite) no lo precisaba | RF-03-D04/RN-03-D04: trámites SÍ validan MIME real/tamaño/antivirus | **C-06:** separar dominios — PQRSD (derecho) no rechaza por tipo pero aplica antivirus + límite técnico; trámite (requisito) sí valida. UC-004 E6 ya cubre MIME; agregar antivirus. |
| UC-017 / UC-020 | login admin con contraseña; sin 2FA interno explícito | RN-09-D04: MFA obligatorio para administradores del CMS | **C-07:** agregar MFA como precondición de UC-017 y al crear cuentas internas en UC-020. |
| UC-004 / UC-005 | nivel de confianza "definido" sin matriz | RN-TX-D04: nivel mínimo por tipo de trámite | **C-08 [PREGUNTA ABIERTA]:** crear matriz trámite↔nivel; UC-004 E8 bloquea si nivel insuficiente. |
| UC-019 | LSC obligatoria | aplica a "Gobierno Nacional" | **[PREGUNTA ABIERTA]** ya registrada — sin cambio. |

---

## 5. Resumen cuantitativo

| Categoría | Cantidad |
|---|---|
| **UC nuevos propuestos** | 9 (UC-042..UC-050) |
| de ellos, flujos de negocio nuevos | 5 (UC-042..UC-046) |
| de ellos, UC de administración/configuración | 4 (UC-047..UC-050) |
| **UC existentes enriquecidos** | 18 (UC-001,002,003,004,005,006,007,008,009,011,016,018,020,025,029,031,032,035,036,037 — núcleo) |
| **Flujos alternos/excepción agregados** | ~38 |
| **Vacíos `[INFERENCIA — vacío]` cerrados con RF/RN** | 4 (pago idempotente UC-007 E3; concurrencia cita UC-011 E1/UC-032 E3; edición concurrente UC-018 E4; doble cobro/conciliación) |
| **Pre/postcondiciones agregadas** | 12 |
| **Conflictos mapeados a C-06/C-07/C-08** | 3 |

### Preguntas abiertas (heredadas del delta RF/RN — no se inventan)
1. Política de reembolso al desistir un trámite pagado (UC-043 E2). — Tesorería + Líder de trámites.
2. Trámites sujetos a silencio administrativo positivo y su término (UC-044 E2). — Secretaría Jurídica.
3. Período de retención/expiración de borradores de trámite (UC-045 E1). — Líder de trámites + Protección de Datos.
4. Matriz trámite ↔ nivel de autenticación exigido (UC-004 E8 / C-08). — G-CIO + AND.
5. Agenda propia vs integración con sistema de turnos (UC-047 E2). — Oficina de Atención.
6. Enrutamiento PQRSD automático vs manual (UC-035, heredada).

### Estimación de completitud del artefacto base de UC
- **Cobertura estimada: ~85-88%.** La pasada base fue de alta calidad: 41 UC bien identificados y deduplicados, fichas con pre/postcondiciones, alternos y excepciones, y **honestidad explícita** al marcar vacíos como `[INFERENCIA — vacío]` (concurrencia, idempotencia). Eso facilitó esta segunda pasada.
- **El residual NO son omisiones normativas:** se concentra en (a) el **ciclo de vida post-radicación/post-pago** (estados, idempotencia, conciliación, prórroga, traslado, cierre, SAP), (b) **autorización fina por recurso (IDOR) y SoD/MFA** como excepciones transversales, y (c) **UC de administración/configuración** (agenda, ciclo editorial, versionado de datasets/transparencia) que el delta de RF "CRUD administrativo" hizo visibles.
- **Causa raíz del residual:** igual que en RN, el back-office y el ciclo de vida con estados se trataron como territorio de RF y no se elevaron a UC propios. Recomendación al creador: por cada entidad con estados (radicado, pago, cita, contenido, dataset) generar de oficio el UC de transición de estado y sus excepciones de concurrencia/autorización.
- **Veredicto:** el artefacto de UC está **listo para integrar este delta**; NO requiere otra pasada completa del creador. Conviene resolver las 6 preguntas abiertas (heredadas) antes de diseño, porque fijan decisiones que no se pueden inventar.

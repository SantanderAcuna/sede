# Historias de Usuario — Delta de la segunda pasada profunda (`jose-historias-usuario-profundo`)

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Fecha:** 2026-06-04
> **Encuadre:** verificación adversarial (INVEST + G/W/T) sobre las HU ya producidas en los 12 módulos (sección `## 6. Historias de Usuario`), cruzadas contra los deltas recién integrados `rf-rnf-delta-profundo.md` (37 RF + 18 RNF), `rn-delta-profundo.md` (54 RN) y `uc-delta-profundo.md` (9 UC + ~38 flujos alt/excepción), más `README.md` y `contexto-transversal.md`.
> **Hallazgo de cobertura (retroalimentación al creador):** el artefacto base de HU NO aplicó un protocolo de cobertura comparable al de RF/RN/UC. Solo hay **~5-7 HU por módulo**, casi todas del **camino feliz del ciudadano**. Faltan de forma sistemática: (a) las HU de **back-office** (funcionario, administrador, editor, oficial de cumplimiento); (b) los **escenarios negativos / no-felices** dentro de los criterios G/W/T (validación, autorización, concurrencia, caída de integración); (c) las **variaciones por rol** (ciudadano anónimo vs identificado, editor vs aprobador con SoD); (d) DoR/DoD. **Cobertura estimada del artefacto base de HU: ~45-55%** — el más bajo de los cuatro artefactos. Requiere otra pasada del creador o la integración íntegra de este delta.
> **Procedencia (sin refs `#NN`, el corpus OCR se borró):** **[NORMATIVA]** ley/decreto citado · **[DOMINIO]** invariante de transacciones/concurrencia/control de acceso · **[WEB]** verificado en `extracto-web-docs.md` · **[INFERENCIA]** derivado validable · **[PREGUNTA ABIERTA]**.
> **Esquema de IDs:** se usa el sufijo `-D` (p. ej. `HU-03-D01`) para no colisionar con el esquema base `HU-Bn-NNN`.
> **Estado:** DELTA pendiente de integración a la sección 6 de cada módulo. NO se editó ningún artefacto base.

---

## Convención de criterios

Cada HU nueva trae al menos **un escenario positivo y uno negativo** en formato Given/When/Then. Donde el dominio lo pide se añade **DoR** (Definition of Ready) y **DoD** (Definition of Done). Para las HU existentes solo se listan los **escenarios G/W/T que se agregan** (no se reescribe la HU).

---

# 1. HU NUEVAS por módulo

## Módulo 01 — Estructura e Identidad

### HU-01-D01 — Versionado y caducidad del consentimiento de cookies
**Como** ciudadano **quiero** que mi consentimiento de cookies se recuerde pero se me vuelva a pedir cuando cambie la política o caduque **para** mantener control real y vigente sobre mis datos.
*RF-01-D01 · RN-01-D01 · UC-016 A2 · [NORMATIVA] Ley 1581*
- **Positivo:** *Dado* que acepté analítica con la política v3, *cuando* la entidad publica la política v4, *entonces* en mi siguiente visita se muestra de nuevo el banner y no se reactiva ninguna cookie no esencial hasta que vuelva a aceptar.
- **Positivo (caducidad):** *Dado* que mi consentimiento tiene >12 meses, *cuando* vuelvo a entrar, *entonces* el banner reaparece y se registra un nuevo consentimiento con versión y timestamp.
- **Negativo:** *Dado* un consentimiento caducado, *cuando* el front intenta cargar Google Analytics, *entonces* el script no se inyecta y queda registro de "consentimiento caducado, cookie bloqueada".

### HU-01-D02 — Gestión del menú de navegación y carrusel (editor)
**Como** editor de contenidos **quiero** crear, reordenar y despublicar ítems del menú y noticias del carrusel **para** mantener la sede actualizada sin romper el Kit UI.
*RF-01-D02 · RN-01-D02 · [DOMINIO]+[WEB]*
- **Positivo:** *Dado* un menú con 5 ítems, *cuando* reordeno y publico, *entonces* el orden se refleja en todas las páginas en <1 min y queda en el log.
- **Negativo (tope):** *Dado* un menú con 7 ítems de primer nivel, *cuando* intento agregar el 8.º o un 3.er nivel, *entonces* el sistema lo bloquea con mensaje "máx. 7 ítems / 2 niveles".
- **DoR:** definido el árbol de menú vigente y los roles con permiso de edición. **DoD:** cambio auditado, validado en responsive y sin romper enlaces existentes.

### HU-01-D03 — Aviso de salida con lista blanca (administrador)
**Como** administrador **quiero** mantener una lista blanca de dominios de confianza **para** que el modal de "sitio externo" no moleste en redirecciones a GOV.CO/SCD.
*RF-01-D03 · RN-01-D03 · UC-003 E4 · [DOMINIO]*
- **Positivo:** *Dado* un enlace a `gov.co`, *cuando* el ciudadano lo pulsa, *entonces* navega sin modal.
- **Negativo:** *Dado* un enlace a un dominio no listado, *cuando* el ciudadano lo pulsa, *entonces* aparece el aviso de salida a sitio externo.

### HU-01-D04 — Degradación ante caída del CDN GOV.CO
**Como** ciudadano **quiero** que la sede siga legible aunque el CDN de GOV.CO falle **para** poder usarla en cualquier momento.
*RNF-01-D01 · [DOMINIO]*
- **Positivo:** *Dado* que `cdn.www.gov.co` no responde, *cuando* cargo una página, *entonces* se usan tipografías locales de respaldo y el layout no se rompe (reintento ≤3 s).
- **Negativo:** *Dado* el CDN caído, *cuando* el fallback también está mal configurado, *entonces* se registra el incidente de disponibilidad para observabilidad (RNF-TX-D01).

---

## Módulo 02 — Transparencia

### HU-02-D01 — Versionado de documentos de transparencia (editor)
**Como** editor de transparencia **quiero** reemplazar un documento publicado conservando la versión anterior **para** preservar la trazabilidad histórica sin romper la URL de fuente única.
*RF-02-D01 · RN-02-D01 · UC-050 · [NORMATIVA] Ley 1712*
- **Positivo:** *Dado* el Plan de Acción 2025 publicado, *cuando* subo una versión corregida, *entonces* la URL se mantiene y la versión anterior queda accesible como histórico con su fecha.
- **Negativo:** *Dado* un reemplazo en curso, *cuando* falla el guardado, *entonces* la versión publicada original permanece intacta (no se pierde el documento vigente).
- **DoD:** historial inmutable; enlace de fuente única verificado sin 404.

### HU-02-D02 — Alerta de vencimiento de publicaciones obligatorias
**Como** administrador de cumplimiento **quiero** recibir alertas N días antes del plazo legal de cada publicación obligatoria **para** no incurrir en hallazgo ITA.
*RF-02-D02 · RN-02-D02 · UC-050 E2 · [NORMATIVA] Res.1519 Anexo 2*
- **Positivo:** *Dado* que faltan 10 días para el 31-ene sin Plan de Acción cargado, *cuando* corre el chequeo diario, *entonces* recibo alerta con el ítem, la norma y el responsable.
- **Negativo:** *Dado* el plazo vencido sin publicación, *cuando* se evalúa el ITA, *entonces* el ítem se marca "incumplido" y se escala al supervisor.

### HU-02-D03 — Caída de integración externa de transparencia (ciudadano)
**Como** ciudadano **quiero** un mensaje claro cuando SECOP/SIGEP/SUIN/SUCOP/KOGUI no responden **para** no toparme con una página rota.
*RF-02-D03 · RN-02-D03 · UC-050 E1 · [DOMINIO]*
- **Positivo:** *Dado* SECOP caído, *cuando* abro Contratación, *entonces* veo "El servicio de contratación no está disponible temporalmente" y el fallo queda registrado.
- **Negativo:** *Dado* el fallo, *cuando* se mide la meta de 0 vínculos rotos, *entonces* el enlace gestionado NO cuenta como roto (a diferencia de un error HTTP sin manejo).

### HU-02-D04 — Trazabilidad de sincronización con SIGEP
**Como** administrador del directorio **quiero** ver la fecha de última sincronización con SIGEP y ser alertado si supera 24 h **para** garantizar la actualización ≤1 día hábil.
*RNF-02-D01 · RN-02-D04 · [NORMATIVA]+[INFERENCIA]*
- **Positivo:** *Dado* que la última sync con SIGEP fue hace 30 h, *cuando* reviso el directorio, *entonces* veo la marca "desactualizado" y recibí alerta.
- **Negativo:** *Dado* que la sync falla repetidamente, *cuando* pasan 24 h, *entonces* se alerta y se conserva el último directorio válido (no se muestra vacío).

---

## Módulo 03 — Servicios y Trámites

### HU-03-D01 — Idempotencia de pago y radicado
**Como** ciudadano **quiero** que un doble clic o un timeout de PSE no me cobre dos veces ni genere dos radicados **para** confiar en el pago en línea.
*RF-03-D01 · RN-03-D01 · UC-007 E3' · [DOMINIO]*
- **Positivo:** *Dado* que envío el formulario de pago, *cuando* hago doble clic o reintento con la misma clave de idempotencia, *entonces* el sistema devuelve el comprobante existente, sin segundo cargo ni segundo radicado.
- **Negativo:** *Dado* un timeout de la pasarela, *cuando* reintento, *entonces* el sistema reconcilia por la clave de idempotencia y no duplica la transacción.
- **DoR:** definida la clave de idempotencia por transacción. **DoD:** probado con envíos concurrentes y reintentos.

### HU-03-D02 — Conciliación de estados del pago
**Como** ciudadano **quiero** que mi trámite avance solo cuando el pago se confirme **para** no perder dinero ni quedar en un estado ambiguo.
*RF-03-D02 · RN-03-D02 · UC-007 E5 · [DOMINIO]+[WEB]*
- **Positivo:** *Dado* un pago `aprobado`, *cuando* la pasarela confirma, *entonces* el trámite avanza a la siguiente etapa con comprobante.
- **Negativo (pendiente):** *Dado* un pago `pendiente`, *cuando* consulto el trámite, *entonces* veo "en espera de confirmación de pago" y el sistema reconsulta cada N min sin avanzar.
- **Negativo (rechazado):** *Dado* un pago `rechazado`, *cuando* vuelvo, *entonces* el trámite no consume radicado y puedo reintentar el pago.

### HU-03-D03 — Sin recargo por canal digital
**Como** ciudadano **quiero** pagar en línea sin que me cobren la pasarela como recargo **para** no pagar de más por usar el canal digital.
*RN-03-D03 · UC-007 E6 · [NORMATIVA] Dec.2106 Art.7*
- **Positivo:** *Dado* una tarifa de trámite de $X, *cuando* pago por PSE, *entonces* el total es $X sin recargo de pasarela.
- **Negativo:** *Dado* una configuración que intenta sumar la comisión al ciudadano, *cuando* se calcula el total, *entonces* el sistema lo rechaza/registra como no conforme.

### HU-03-D04 — Guardar y reanudar trámite (borrador)
**Como** ciudadano **quiero** guardar un trámite largo a medio diligenciar y retomarlo **para** no perder mi avance ni mis adjuntos.
*RF-03-D03 · RNF-03-D02 · UC-045 · [DOMINIO]*
- **Positivo:** *Dado* que estoy en el paso 3 de 5, *cuando* cierro sesión y vuelvo, *entonces* retomo desde el paso 3 con datos y adjuntos intactos.
- **Negativo (expiración):** *Dado* un borrador que superó el período de retención, *cuando* vuelvo, *entonces* se me informa que expiró y se descarta (**[PREGUNTA ABIERTA]** plazo de retención).
- **Negativo (ficha cambió):** *Dado* que la ficha del trámite cambió desde el guardado, *cuando* reanudo, *entonces* se me avisa y se revalida lo diligenciado.

### HU-03-D05 — Validación de adjuntos del trámite
**Como** ciudadano **quiero** que el sistema valide mis adjuntos del trámite con mensajes claros **para** corregir antes de radicar.
*RF-03-D04 · RN-03-D04 · [DOMINIO]+[NORMATIVA]*
- **Positivo:** *Dado* un PDF válido dentro del tamaño y cantidad permitidos, *cuando* lo cargo, *entonces* se acepta.
- **Negativo (MIME falso):** *Dado* un `.php` renombrado a `.pdf`, *cuando* lo cargo, *entonces* se rechaza por MIME real inválido con mensaje accesible.
- **Negativo (malware):** *Dado* un archivo con malware, *cuando* pasa el antivirus, *entonces* se bloquea y se registra.
> Nota de frontera: aplica a **trámites** (requisito del trámite); NO a PQRSD (C-06).

### HU-03-D06 — Subsanación / requerimiento de documentos (ciudadano)
**Como** ciudadano **quiero** responder a un requerimiento de documentación adicional y reanudar el trámite **para** no tener que empezar de cero.
*RF-03-D05 · RN-03-D05 · UC-042 · [NORMATIVA] Ley 1755 Art.17 · [WEB]*
- **Positivo:** *Dado* un trámite "requiere subsanación", *cuando* cargo lo solicitado, *entonces* el estado pasa a "Subsanado", el trámite continúa y el plazo se recalcula desde la entrega.
- **Negativo (vencimiento):** *Dado* que vence el plazo de subsanación sin que yo responda, *cuando* corre el cómputo, *entonces* el sistema declara desistimiento por no subsanar y me notifica.
- **Negativo (parcial):** *Dado* que entrego solo parte de lo requerido, *cuando* confirmo, *entonces* el trámite sigue "requiere subsanación" por lo pendiente.

### HU-03-D07 — Solicitar subsanación (funcionario)
**Como** funcionario de la dependencia **quiero** marcar un trámite como "requiere subsanación" indicando qué falta y el plazo **para** completar el expediente conforme a la ley.
*RF-03-D05 · RN-03-D05 · UC-042 · [NORMATIVA] Ley 1755 Art.17*
- **Positivo:** *Dado* un trámite en Etapa 3, *cuando* marco subsanación con detalle y plazo, *entonces* el ciudadano es notificado y el trámite se suspende.
- **Negativo:** *Dado* un requerimiento sin detalle de lo faltante, *cuando* intento enviarlo, *entonces* el sistema lo bloquea (motivación obligatoria).

### HU-03-D08 — Desistir de un trámite (ciudadano)
**Como** ciudadano **quiero** desistir de un trámite antes de su resolución **para** detenerlo cuando ya no lo necesito.
*RF-03-D07 · RN-03-D07 · UC-043 · [NORMATIVA] Ley 1755 Art.18 · [WEB]*
- **Positivo:** *Dado* un trámite en Etapa 2, *cuando* elijo "Desistir" y confirmo (doble confirmación), *entonces* el estado pasa a "Desistido" con sello temporal y queda en el expediente.
- **Negativo (ya resuelto):** *Dado* un trámite ya resuelto, *cuando* intento desistir, *entonces* no se permite y se informa.
- **Negativo (pago previo):** *Dado* un trámite pagado, *cuando* desisto, *entonces* se aplica la política de reembolso del trámite (**[PREGUNTA ABIERTA]** reembolso).

### HU-03-D09 — Silencio administrativo positivo (sistema/funcionario)
**Como** funcionario responsable **quiero** que el sistema marque el vencimiento del término en trámites con SAP y registre el efecto "concedido" **para** cumplir la ley y notificar al ciudadano.
*RN-03-D08 · UC-044 · [NORMATIVA] Ley 1755 Art.83-85; Ley 2052 Art.12 · [WEB]*
- **Positivo:** *Dado* un trámite sujeto a SAP, *cuando* vence el término sin resolución expresa, *entonces* el sistema marca "concedido", notifica al ciudadano y alerta a Secretaría Jurídica.
- **Negativo (no SAP):** *Dado* un trámite NO sujeto a SAP, *cuando* vence el término, *entonces* se dispara escalamiento ordinario, no efecto positivo.
- **DoR:** catálogo de trámites con SAP y su término (**[PREGUNTA ABIERTA]** Secretaría Jurídica).

### HU-03-D10 — Caída del SCD/X-Road en verificación automática
**Como** ciudadano **quiero** poder continuar mi trámite con carga manual cuando la verificación automática falla **para** que una caída técnica no me bloquee.
*RF-03-D06 · RN-03-D06 · UC-004 E2' · [NORMATIVA] Dec.2106 Art.10*
- **Positivo:** *Dado* que ANI/RUNT/RUAF no responde, *cuando* el sistema agota reintentos, *entonces* habilita carga manual temporal del documento dejando constancia de "falla técnica documentada".
- **Negativo:** *Dado* que la verificación automática funciona, *cuando* el dato está disponible vía interoperabilidad, *entonces* NO se exige el documento (la carga manual es excepción, no regla).

### HU-03-D11 — Nivel de autenticación insuficiente para el trámite
**Como** ciudadano **quiero** saber si mi nivel de autenticación no alcanza para un trámite **para** elevarlo antes de empezar.
*RN-TX-D04 · UC-004 E8 · C-08 · [NORMATIVA] Dec.620*
- **Positivo:** *Dado* un trámite que exige nivel Alto, *cuando* entro con nivel Alto, *entonces* puedo iniciarlo.
- **Negativo:** *Dado* el mismo trámite, *cuando* entro con nivel Medio, *entonces* el sistema bloquea el inicio e indica cómo elevar el nivel (**[PREGUNTA ABIERTA]** matriz trámite↔nivel).

### HU-03-D12 — Catálogo de trámites paginado y con búsqueda performante
**Como** ciudadano **quiero** buscar y paginar el catálogo de trámites con respuesta rápida **para** encontrar lo que necesito sin esperas.
*RNF-03-D01 · [DOMINIO]*
- **Positivo:** *Dado* el catálogo completo, *cuando* busco un trámite, *entonces* el resultado llega en ≤2 s y la lista pagina ≥10 por página.
- **Negativo (sin resultados):** *Dado* una búsqueda sin coincidencias, *cuando* la ejecuto, *entonces* veo "sin resultados" con sugerencias, no una página en blanco.

### HU-03-D13 — Atomicidad/unicidad del radicado bajo concurrencia
**Como** entidad **quiero** que dos envíos simultáneos nunca compartan número de radicado **para** garantizar la unicidad del consecutivo.
*RNF-04-D02 · RN-04-D07 · UC-001 E9 · [DOMINIO]*
- **Positivo:** *Dado* dos radicaciones simultáneas, *cuando* el sistema asigna consecutivo, *entonces* cada una recibe un número único de forma atómica.
- **Negativo:** *Dado* una condición de carrera, *cuando* dos hilos piden número a la vez, *entonces* ninguno obtiene un duplicado (uno espera o reintenta).

---

## Módulo 04 — PQRSD

### HU-04-D01 — Enrutamiento y traslado por competencia (funcionario)
**Como** funcionario **quiero** asignar, reasignar y trasladar por competencia una PQRSD **para** que la resuelva la autoridad correcta.
*RF-04-D01 · RN-04-D03 · UC-035 A3 / UC-037 · [NORMATIVA] Ley 1755 Art.21 · [WEB]*
- **Positivo:** *Dado* una PQRSD que no compete a la Alcaldía, *cuando* la traslado a la entidad competente, *entonces* el sistema notifica al ciudadano la entidad receptora y la fecha.
- **Negativo (plazo):** *Dado* un traslado, *cuando* lo efectúo después de 5 días de la recepción, *entonces* el semáforo lo marca fuera de término.
- **Negativo (reasignación interna):** *Dado* una asignación errónea a una dependencia, *cuando* la reasigno, *entonces* el expediente conserva la traza de ambas asignaciones.

### HU-04-D02 — Respuesta y cierre con medición de cumplimiento (funcionario)
**Como** funcionario **quiero** cargar la respuesta, notificarla y cerrar el radicado midiendo el cumplimiento del plazo **para** dar trazabilidad al ciclo de la PQRSD.
*RF-04-D02 · RN-04-D04 · UC-036 A2 · [DOMINIO]+[NORMATIVA]*
- **Positivo:** *Dado* una PQRSD en trámite, *cuando* cargo la respuesta y notifico, *entonces* el estado pasa a "Respondido" y el sistema marca si fue dentro o fuera del término legal.
- **Negativo:** *Dado* un intento de cerrar sin respuesta registrada, *cuando* pulso "Cerrar", *entonces* el sistema lo impide ("no se cierra sin respuesta").

### HU-04-D03 — Cómputo de plazos diferenciados por tipo
**Como** responsable de PQRSD **quiero** que el contador aplique el plazo legal correcto según el tipo de petición sobre el calendario hábil del Distrito **para** medir el vencimiento con precisión.
*RF-04-D03 · RN-04-D01 · RN-TX-D01 · UC-035 E3 · [NORMATIVA] Ley 1755 Art.14*
- **Positivo:** *Dado* una consulta (30 días hábiles), *cuando* se computa el plazo, *entonces* excluye festivos del Distrito y aplica 30 días.
- **Negativo:** *Dado* una petición de información (10 días) tratada como general (15), *cuando* se compara, *entonces* el sistema usa el plazo correcto por tipo, no un plazo único.

### HU-04-D04 — Prórroga del término (funcionario)
**Como** funcionario responsable **quiero** prorrogar el término informando al peticionario antes del vencimiento **para** responder bien cuando no alcanza el plazo ordinario.
*RF-04-D03 · RN-04-D02 · UC-046 · [NORMATIVA] Ley 1755 Art.14 par. · [WEB]*
- **Positivo:** *Dado* una PQRSD dentro del término con causal, *cuando* registro la prórroga con motivación y nueva fecha, *entonces* se notifica al peticionario y se actualiza el semáforo.
- **Negativo (tardía):** *Dado* el término ya vencido, *cuando* intento prorrogar, *entonces* se rechaza y cuenta como respuesta extemporánea.
- **Negativo (excede máximo):** *Dado* una nueva fecha que excede el límite legal, *cuando* la ingreso, *entonces* se bloquea.

### HU-04-D05 — Notificación electrónica conforme CPACA
**Como** funcionario **quiero** notificar la respuesta por el canal autorizado con constancia y fecha **para** que la notificación tenga validez legal.
*RF-04-D05 · RN-04-D05 · UC-036 E3 · [NORMATIVA] Ley 1437 Arts.56,67,69*
- **Positivo:** *Dado* un peticionario con dirección procesal electrónica autorizada, *cuando* notifico la respuesta, *entonces* se envía por ese canal con acuse y se registra la fecha de notificación.
- **Negativo (sin autorización):** *Dado* que no hay dirección electrónica autorizada, *cuando* notifico, *entonces* se usa el canal físico/aviso y se deja constancia.

### HU-04-D06 — Validación detallada del formulario PQRSD (ciudadano)
**Como** ciudadano **quiero** que el formulario PQRSD valide cada campo en tiempo real **para** corregir antes de enviar.
*RF-04-D04 · [DOMINIO]* (enriquece HU-B1-023)
- **Positivo:** *Dado* el campo objeto, *cuando* escribo, *entonces* veo un contador y se bloquea al superar 2.000 caracteres.
- **Negativo (NIT):** *Dado* un NIT con dígito de verificación inválido, *cuando* salgo del campo, *entonces* veo error inline.
- **Negativo (correo):** *Dado* un correo con formato inválido, *cuando* salgo del campo, *entonces* veo el mensaje sin esperar al envío.

### HU-04-D07 — Identidad reservada del denunciante
**Como** denunciante con identidad reservada **quiero** que el back-office no exponga mis datos **para** estar protegido conforme a la ley.
*RN-04-D06 · UC-035 E4 · [NORMATIVA] Ley 190/1995 Art.38*
- **Positivo:** *Dado* una denuncia con identidad reservada, *cuando* un funcionario la consulta, *entonces* no ve los datos del peticionario y, cuando aplica, se traslada con reserva a la Procuraduría.
- **Negativo:** *Dado* un funcionario sin autorización, *cuando* intenta revelar la identidad reservada, *entonces* el acceso se deniega y se registra.

### HU-04-D08 — Gratuidad con cobro tasado de copias
**Como** ciudadano **quiero** que la consulta de información sea gratuita y solo se cobre el costo de reproducción de copias **para** no pagar de más por información pública.
*RN-04-D08 · [NORMATIVA] Ley 1712 Art.26*
- **Positivo:** *Dado* una petición de información, *cuando* la entidad responde, *entonces* no hay cobro salvo la reproducción de copias al costo.
- **Negativo:** *Dado* un cobro superior al costo de reproducción, *cuando* se calcula, *entonces* el sistema lo marca como no conforme.

---

## Módulo 05 — Participa

### HU-05-D01 — Ciclo de vida de consultas (administrador)
**Como** administrador de participación **quiero** abrir, cerrar al vencer y archivar consultas **para** que no se reciban aportes extemporáneos.
*RF-05-D01 · RN-05-D01 · [NORMATIVA]+[DOMINIO]*
- **Positivo:** *Dado* una consulta con fecha límite, *cuando* esta vence, *entonces* el formulario de aportes se deshabilita y el estado pasa a "Cerrada".
- **Negativo:** *Dado* una consulta cerrada, *cuando* un ciudadano intenta aportar por URL directa, *entonces* el sistema rechaza el aporte extemporáneo.

### HU-05-D02 — Publicación del resultado del proceso participativo
**Como** ciudadano **quiero** ver el consolidado de observaciones recibidas y cómo se incorporaron **para** verificar que mi participación tuvo efecto.
*RF-05-D02 · RN-05-D02 · [NORMATIVA] Decreto 1081 Art.2.1.2.1.14*
- **Positivo:** *Dado* una consulta normativa cerrada, *cuando* la entidad publica el cierre, *entonces* veo las observaciones y la respuesta de la entidad (incorporadas o no, con motivo).
- **Negativo:** *Dado* una consulta cerrada sin documento de resultado, *cuando* se evalúa la rendición, *entonces* el sistema alerta el pendiente al responsable.

---

## Módulo 06 — Canales de Atención

### HU-06-D01 — Administración de la agenda de citas (administrador)
**Como** administrador de atención **quiero** definir servicios agendables, franjas, cupos y bloqueos **para** que el ciudadano solo vea disponibilidad real.
*RF-06-D01 · RN-06-D01 · UC-047 · [DOMINIO]*
- **Positivo:** *Dado* el servicio "Catastro", *cuando* configuro 10 cupos de 9-10 am, *entonces* el ciudadano ve esa franja con cupos disponibles.
- **Negativo (reducir bajo reservas):** *Dado* una franja con citas ya reservadas, *cuando* intento reducir cupos por debajo de las reservas, *entonces* el sistema lo impide o exige reprogramar las afectadas.
- **DoR:** decisión agenda propia vs sistema de turnos (**[PREGUNTA ABIERTA]**). **DoD:** oferta publicada y visible en UC-011.

### HU-06-D02 — Reprogramación de cita (ciudadano)
**Como** ciudadano **quiero** reprogramar mi cita **para** ajustarla sin tener que cancelarla y volver a agendar.
*RF-06-D02 · RN-06-D02 · UC-032 A1 · [DOMINIO]*
- **Positivo:** *Dado* mi código de cita, *cuando* elijo "Reprogramar" y una nueva franja, *entonces* se libera el cupo anterior y recibo nueva confirmación.
- **Negativo:** *Dado* que la nueva franja se llenó mientras decidía, *cuando* confirmo, *entonces* recibo "franja no disponible" y mi cita original se conserva.

### HU-06-D03 — Concurrencia / doble reserva del último cupo
**Como** ciudadano **quiero** que el último cupo no se asigne a dos personas **para** no llegar a una cita que el sistema no respeta.
*RF-06-D03 · RN-06-D01 · UC-032 E3' · [DOMINIO]*
- **Positivo:** *Dado* el último cupo de una franja, *cuando* lo reservo primero, *entonces* mi reserva se confirma.
- **Negativo:** *Dado* dos solicitudes simultáneas al último cupo, *cuando* se procesan, *entonces* una confirma y la otra recibe "cupo ya no disponible".

### HU-06-D04 — Recordatorio y no-show (ciudadano/sistema)
**Como** administrador de atención **quiero** recordatorios previos y marca de inasistencia **para** liberar cupos y medir el no-show.
*RF-06-D04 · [DOMINIO]*
- **Positivo:** *Dado* una cita a 24 h, *cuando* corre el job, *entonces* el ciudadano recibe recordatorio por correo.
- **Negativo (no-show):** *Dado* una cita no atendida, *cuando* pasa la hora, *entonces* se marca "no-show", se libera el cupo y se alimenta la métrica.

---

## Módulo 07 — Accesibilidad

### HU-07-D01 — Bloqueo de publicación de multimedia inaccesible (editor)
**Como** editor **quiero** que el CMS me impida publicar un video sin subtítulos (y LSC cuando aplica) **para** no publicar contenido inaccesible.
*RF-07-D01 · RN-07-D01 · [NORMATIVA] Res.1519 Anexo 1, 1.5*
- **Positivo:** *Dado* un video con `.SRT` y, si es rendición de cuentas, ventana LSC, *cuando* publico, *entonces* el CMS lo permite.
- **Negativo:** *Dado* un video sin subtítulos, *cuando* intento publicarlo, *entonces* el CMS lo bloquea y exige subtítulos.

### HU-07-D02 — Barra de accesibilidad operable en tablet
**Como** ciudadano que usa tablet **quiero** la barra de accesibilidad disponible y operable en 768-992 px **para** ajustar contraste y tamaño en mi dispositivo.
*RNF-07-D01 · [INFERENCIA]+[DOMINIO]*
- **Positivo:** *Dado* una tablet de 800 px, *cuando* abro la sede, *entonces* la barra de accesibilidad está visible y operable.
- **Negativo:** *Dado* el breakpoint de tablet, *cuando* uso la barra, *entonces* no se solapa con el contenido ni pierde funciones respecto a desktop.

### HU-07-D03 — Captcha accesible (ciudadano con discapacidad visual)
**Como** ciudadano con discapacidad visual **quiero** una alternativa accesible al captcha **para** poder enviar formularios sin barreras.
*RN-07-D02 · [NORMATIVA] WCAG 2.1 AA 1.1.1*
- **Positivo:** *Dado* un desafío anti-bot, *cuando* uso lector de pantalla, *entonces* dispongo de alternativa de audio/no visual para resolverlo.
- **Negativo:** *Dado* un captcha solo visual, *cuando* se audita, *entonces* se marca como no conforme con WCAG AA.

---

## Módulo 08 — Usabilidad

### HU-08-D01 — Criterio cuantitativo de "cumple" en usabilidad (equipo UX)
**Como** equipo UX **quiero** declarar "cumple" una tarea solo si supera SUS ≥68 y un umbral de tasa de éxito/tiempo **para** medir usabilidad de forma objetiva.
*RF-08-D01 · RN-08-D01 · A-14 · [PREGUNTA ABIERTA]+[DOMINIO]*
- **Positivo:** *Dado* una tarea crítica, *cuando* SUS ≥68 y la tasa de éxito ≥ umbral, *entonces* se declara conforme.
- **Negativo:** *Dado* SUS 70 pero tasa de éxito por debajo del umbral, *cuando* se evalúa, *entonces* NO se declara conforme y se abre plan de mejora (**[PREGUNTA ABIERTA]** umbral).

### HU-08-D02 — Persistencia y exportación de resultados SUS (equipo UX)
**Como** equipo UX **quiero** almacenar y exportar los resultados SUS **para** comparar histórico y sustentar el plan de mejora.
*RNF-08-D01 · [DOMINIO]*
- **Positivo:** *Dado* una ronda SUS cerrada, *cuando* la finalizo, *entonces* los resultados quedan almacenados y exportables a CSV con histórico comparable.
- **Negativo:** *Dado* una exportación, *cuando* falla el almacenamiento, *entonces* se notifica el error sin perder los datos de la ronda.

---

## Módulo 09 — Seguridad

### HU-09-D01 — Autorización por recurso (anti-IDOR)
**Como** ciudadano autenticado **quiero** que solo yo pueda ver mis radicados/trámites/PQRSD/citas **para** que nadie acceda a mis datos cambiando un ID.
*RF-09-D01 · RN-09-D01 · UC-002/004/009 E(IDOR) · [DOMINIO] OWASP A01 · [NORMATIVA] Ley 1581*
- **Positivo:** *Dado* mi sesión, *cuando* abro un radicado propio, *entonces* veo su detalle.
- **Negativo:** *Dado* el ID de un radicado ajeno, *cuando* lo pongo en la URL, *entonces* recibo 403 y queda registrado en el log.
> Nota: los radicados anónimos públicos consultables por número siguen siendo públicos (sin cambio).

### HU-09-D02 — Recuperación de contraseña endurecida (usuario interno)
**Como** usuario interno del CMS **quiero** un flujo "Olvidé mi contraseña" seguro **para** recuperar acceso sin exponer mi cuenta.
*RF-09-D02 · RN-09-D03 · UC-031 E1' · [DOMINIO]*
- **Positivo:** *Dado* que solicito recuperación, *cuando* recibo el enlace, *entonces* el token es de un solo uso y expira en 15 min; al restablecer se invalidan mis sesiones activas.
- **Negativo (token reusado):** *Dado* un token ya usado o expirado, *cuando* intento usarlo, *entonces* se rechaza y se solicita uno nuevo.
- **Negativo (enumeración):** *Dado* un correo inexistente, *cuando* solicito recuperación, *entonces* la respuesta es genérica (no revela si la cuenta existe).

### HU-09-D03 — Segregación de funciones (SoD)
**Como** administrador **quiero** que quien crea un contenido no pueda aprobarlo **para** garantizar control interno.
*RF-09-D03 · RN-09-D02 · UC-048 E1 · [DOMINIO]+[INFERENCIA]*
- **Positivo:** *Dado* un editor que creó una norma, *cuando* la envía a aprobación, *entonces* un administrador distinto la aprueba.
- **Negativo (auto-aprobación):** *Dado* el creador, *cuando* intenta aprobar su propio contenido, *entonces* el sistema lo bloquea por SoD.
- **Negativo (auto-auditoría):** *Dado* quien gestiona usuarios, *cuando* intenta auditar sus propias acciones, *entonces* se impide.

### HU-09-D04 — Caída del SCD de Autenticación (OIDC)
**Como** ciudadano **quiero** seguir viendo el contenido público aunque el Articulador falle **para** que una caída de autenticación no me deje sin sede.
*RF-09-D04 · RN-09-D06 · UC-005 E7 · [DOMINIO]*
- **Positivo:** *Dado* el Articulador caído, *cuando* navego contenido público, *entonces* lo veo, y al intentar autenticarme veo "autenticación no disponible, intente más tarde".
- **Negativo (state inválido):** *Dado* un callback OIDC con `state` no coincidente, *cuando* el sistema lo recibe, *entonces* lo rechaza y registra (posible CSRF).

### HU-09-D05 — MFA obligatorio para administradores del CMS
**Como** oficial de seguridad **quiero** exigir segundo factor a los roles internos del CMS **para** que una contraseña filtrada no comprometa el back-office.
*RN-09-D04 · UC-017/020 E4 · C-07 · [DOMINIO]+[NORMATIVA] MSPI*
- **Positivo:** *Dado* un administrador, *cuando* inicia sesión, *entonces* se le exige MFA además de la contraseña.
- **Negativo:** *Dado* un administrador sin MFA configurado, *cuando* intenta operar, *entonces* el sistema lo obliga a enrolarlo antes de continuar.

### HU-09-D06 — Inmutabilidad del log de auditoría
**Como** auditor **quiero** que los logs de auditoría sean inalterables **para** que tengan valor probatorio.
*RNF-09-D01 · RN-09-D05 · [DOMINIO]+[NORMATIVA] Ley 527/1999*
- **Positivo:** *Dado* un evento de auditoría, *cuando* se escribe, *entonces* queda en almacenamiento append-only/encadenado por hash durante 5 años.
- **Negativo:** *Dado* un administrador, *cuando* intenta editar o borrar un registro de auditoría, *entonces* el sistema lo impide y registra el intento.

### HU-09-D07 — Rate limiting en endpoints públicos
**Como** oficial de seguridad **quiero** límite de tasa por IP/sesión en login, PQRSD y búsqueda **para** mitigar fuerza bruta y DoS de aplicación.
*RNF-09-D02 · [DOMINIO] OWASP*
- **Positivo:** *Dado* un uso normal, *cuando* opero, *entonces* no me afecta el límite.
- **Negativo:** *Dado* un volumen anómalo desde una IP, *cuando* supera el umbral, *entonces* se aplica throttling/bloqueo temporal y se registra.

---

## Módulo 10 — Interoperabilidad

### HU-10-D01 — Manejo de error y reintento en X-Road (equipo técnico)
**Como** equipo técnico **quiero** que un fallo de X-Road no deje el trámite colgado **para** garantizar continuidad y trazabilidad.
*RF-10-D01 · RN-10-D01 · UC-029 E5 · [DOMINIO]*
- **Positivo:** *Dado* un timeout del par, *cuando* el sistema reintenta hasta N veces, *entonces* si persiste registra error controlado y aplica fallback a carga manual (RN-03-D06).
- **Negativo (OCSP vencido):** *Dado* un certificado/OCSP vencido del par, *cuando* se intenta el intercambio, *entonces* se aborta con error claro y log, sin estado indefinido.

### HU-10-D02 — Continuidad del estampado TSA (equipo técnico)
**Como** equipo técnico **quiero** encolar los mensajes pendientes de sello durante la migración TSA **para** que ninguno quede sin estampa RFC 3161.
*RF-10-D02 · RN-10-D02 · UC-029 E4 · [DOMINIO]+[NORMATIVA]*
- **Positivo:** *Dado* la ventana de migración Certicámara→GSE, *cuando* llegan mensajes a sellar, *entonces* se encolan y se estampan al restablecer el proveedor.
- **Negativo:** *Dado* un mensaje, *cuando* no se puede sellar, *entonces* NO se cierra sin estampa (queda en cola, nunca se da por sellado).

### HU-10-D03 — Monitoreo de vencimiento de certificados (equipo técnico)
**Como** equipo técnico **quiero** alertas N días antes del vencimiento de certificados ONAC/TLS/OCSP **para** renovarlos sin caída en producción.
*RNF-10-D01 · RN-10-D03 · [DOMINIO]*
- **Positivo:** *Dado* un certificado que vence en N días, *cuando* corre el monitor, *entonces* recibo alerta para renovar.
- **Negativo:** *Dado* un certificado vencido en producción, *cuando* se detecta, *entonces* se declara incidente.

---

## Módulo 11 — Datos Abiertos

### HU-11-D01 — CRUD, versionado y frescura de datasets (administrador)
**Como** administrador del portal **quiero** editar, versionar y controlar la frescura de los datasets **para** mantener los datos abiertos actualizados.
*RF-11-D01 · RN-11-D01 · UC-049 · [DOMINIO]+[NORMATIVA] Res.1519 Anexo 4*
- **Positivo:** *Dado* un dataset con frecuencia "mensual", *cuando* lo actualizo, *entonces* se versiona conservando la versión previa y se reinicia el contador de frescura.
- **Negativo (desactualizado):** *Dado* un dataset mensual sin actualizar en 35 días, *cuando* corre el chequeo, *entonces* se marca "desactualizado" y se alerta al responsable.

### HU-11-D02 — Validación de calidad antes de federar (administrador)
**Como** administrador **quiero** validar el archivo y los metadatos antes de federar a datos.gov.co **para** no publicar datos mal formados.
*RNF-11-D01 · RN-11-D02 · UC-049 E2 · [NORMATIVA] Res.1519 Anexo 4*
- **Positivo:** *Dado* un CSV bien formado con metadatos completos, *cuando* publico, *entonces* se federa a datos.gov.co.
- **Negativo:** *Dado* un archivo mal formado o metadatos incompletos, *cuando* intento federar, *entonces* el sistema lo rechaza con el detalle del error.

---

## Módulo 12 — Gestión de Contenidos

### HU-12-D01 — Ciclo editorial con estados y rechazo (editor/administrador con SoD)
**Como** administrador **quiero** mover los contenidos por Borrador→Pendiente→Publicado→Archivado con rechazo a edición **para** controlar qué se publica.
*RF-12-D01 · RN-12-D01 · UC-048 · [DOMINIO]*
- **Positivo:** *Dado* un contenido "Pendiente de aprobación", *cuando* lo apruebo, *entonces* pasa a "Publicado"; si lo rechazo, vuelve a "Borrador" con comentario al editor.
- **Negativo (publicar sin metadato):** *Dado* una norma sin estado de vigencia, *cuando* intento publicarla, *entonces* se bloquea (HU-B3-018).
- **Negativo (SoD):** *Dado* el editor creador, *cuando* intenta aprobar lo propio, *entonces* se bloquea (HU-09-D03).

### HU-12-D02 — Baja segura de usuario interno con retención de auditoría (administrador)
**Como** administrador **quiero** desvincular un usuario revocando accesos pero conservando su trazabilidad **para** cumplir el no repudio y la archivística.
*RF-12-D02 · RN-12-D02 · UC-020 A2 · [NORMATIVA] Ley 594/2000; MSPI*
- **Positivo:** *Dado* un usuario que se desvincula, *cuando* lo doy de baja, *entonces* sus accesos y tokens se revocan ≤1 día hábil y sus eventos de auditoría permanecen consultables.
- **Negativo (borrado físico):** *Dado* la baja, *cuando* alguien intenta borrar físicamente el usuario y su historial, *entonces* el sistema lo impide (solo desactivación).

### HU-12-D03 — Reintento/cola de notificaciones multicanal
**Como** entidad **quiero** reintentar y/o usar canal alterno cuando una notificación falla **para** garantizar la entrega y la validez de la notificación.
*RF-12-D03 · RN-12-D04 · UC-008 E4 · [DOMINIO]+[NORMATIVA] Ley 1437*
- **Positivo:** *Dado* una notificación por correo, *cuando* se entrega, *entonces* se registra "entregado".
- **Negativo (rebote):** *Dado* un correo que rebota, *cuando* falla, *entonces* se reintenta y/o se usa canal alterno autorizado, y NO se da por notificado hasta confirmar entrega.

### HU-12-D04 — Verificación de edad de titulares menores
**Como** entidad **quiero** impedir recolectar datos de menores de 18 sin autorización del representante **para** cumplir la protección de datos de menores.
*RF-12-D04 · RN-12-D03 · UC-006 E6 · [NORMATIVA] Ley 1581 Art.7*
- **Positivo:** *Dado* un registro con fecha de nacimiento que da ≥18, *cuando* continúo, *entonces* el sistema procede normalmente.
- **Negativo:** *Dado* una fecha de nacimiento <18, *cuando* intento avanzar, *entonces* el sistema exige autorización del representante legal antes de recolectar datos.

### HU-12-D05 — Bloqueo de edición concurrente
**Como** editor **quiero** que dos personas no pisen los cambios del otro sobre el mismo contenido **para** no perder trabajo.
*RNF-12-D01 · RN-12-D05 · UC-018 E4' · [DOMINIO]*
- **Positivo:** *Dado* un contenido que estoy editando, *cuando* nadie más lo edita, *entonces* guardo sin conflicto.
- **Negativo:** *Dado* que otro usuario edita el mismo contenido, *cuando* intento guardar sobre una versión obsoleta, *entonces* el sistema avisa el conflicto y no pisa los cambios.

---

# 2. ENRIQUECIMIENTO de HU existentes (solo escenarios G/W/T que se AGREGAN)

| HU existente | Escenario que se agrega | Given / When / Then | Evidencia |
|---|---|---|---|
| HU-B2-001 (trámite 100% en línea) | Negativo — sesión expira a mitad del stepper | *Dado* una sesión de 900 s, *cuando* expira en el paso 3, *entonces* se reautentica preservando lo cargado (enlaza HU-03-D04) | RNF-B1-023 · UC-042 E3 |
| HU-B1-022/B2-016/B3-011 (pago en línea) | Negativo — doble cobro / pago pendiente | (ver HU-03-D01 y HU-03-D02) — agregar a la HU base los criterios de idempotencia y conciliación | RF-03-D01/D02 |
| HU-B1-025/B2-004 (resultado en CCD) | Negativo — CCD no responde al publicar el resultado | *Dado* la CCD indisponible, *cuando* el trámite finaliza, *entonces* el resultado se encola y se reintenta sin marcar el trámite como fallido | RF-12-D03 · [DOMINIO] |
| HU-B2-011/B3-007 (consulta por radicado) | Negativo — IDOR sobre radicado ajeno autenticado | *Dado* un radicado ajeno, *cuando* cambio el ID estando autenticado, *entonces* 403 + log | RF-09-D01 · UC-002 E4 |
| HU-B1-002 (radicar PQRSD) | Negativo — concurrencia de consecutivo | *Dado* dos envíos simultáneos, *cuando* se asigna radicado, *entonces* números únicos atómicos | RN-04-D07 · UC-001 E9 |
| HU-B1-012/B2-003 (queja anónima) | Negativo — no exposición de identidad reservada en back-office | *Dado* una queja con reserva, *cuando* un funcionario la abre, *entonces* no ve datos del peticionario | RN-04-D06 |
| HU-B1-023/B3-006 (validación en tiempo real) | Negativo — NIT con DV inválido / objeto >2.000 | (ver HU-04-D06) | RF-04-D04 |
| HU-B1-004/B3-007 (estado por radicado PQRSD) | Negativo — radicado inexistente | *Dado* un radicado que no existe, *cuando* lo consulto, *entonces* mensaje claro "no encontrado", no error técnico | [DOMINIO] |
| HU-B1-019/B3-009 (agendar cita) | Negativo — último cupo en concurrencia | (ver HU-06-D03) | RF-06-D03 |
| HU-B3-021 (login OIDC) | Negativo — Articulador caído / `state` inválido | (ver HU-09-D04) | RF-09-D04 |
| HU-B2-014 (evidencia de consentimiento) | Negativo — sellado con Hora Legal, no reloj local | *Dado* un registro de consentimiento, *cuando* se sella, *entonces* usa Hora Legal de Colombia (INM), no reloj local no sincronizado | RN-TX-D02 |
| HU-B1-011 (publicar resolución) | Negativo — SoD y estado editorial | *Dado* el editor creador, *cuando* intenta publicar sin aprobación de un rol distinto, *entonces* se bloquea (enlaza HU-12-D01/HU-09-D03) | RF-12-D01 · RN-09-D02 |
| HU-B1-021 (eliminar requiere aprobación) | Negativo — auto-aprobación de la eliminación | *Dado* quien solicitó la eliminación, *cuando* intenta aprobarla él mismo, *entonces* se bloquea por SoD | RN-09-D02 |
| HU-B2-008 (log de expediente) | Negativo — intento de alterar el log | *Dado* el log de auditoría, *cuando* alguien intenta modificarlo, *entonces* se impide (append-only) | RNF-09-D01 |
| HU-B3-017 (firma electrónica) | Negativo — certificado vencido/OCSP | *Dado* un certificado vencido, *cuando* intento firmar, *entonces* el sistema lo rechaza con mensaje claro | RN-10-D03 |
| HU-B3-022 (alerta de vencimiento PQRSD) | Enriquecer — plazo diferenciado por tipo | *Dado* una consulta (30 días), *cuando* calcula el vencimiento, *entonces* aplica el plazo correcto por tipo sobre calendario hábil | RF-04-D03 · RN-04-D01 |
| HU-B1-008 (descargar dataset) | Negativo — dataset desactualizado visible | *Dado* un dataset vencido en frescura, *cuando* lo consulto, *entonces* veo la marca de "última actualización" y su antigüedad | RN-11-D01 |
| HU-B2-018 (publicar dataset) | Negativo — archivo mal formado | (ver HU-11-D02) | RNF-11-D01 |
| HU-B1-016/B3-027 (participar consulta) | Negativo — consulta cerrada / extemporánea | (ver HU-05-D01) | RN-05-D01 |
| HU-B1-018/B3-003 (subtítulos/LSC) | Negativo — bloqueo de publicación sin subtítulos | (ver HU-07-D01) | RF-07-D01 |
| HU-B1-006 (transparencia/SECOP) | Negativo — SECOP caído | (ver HU-02-D03) | RF-02-D03 |
| HU-B3-015 (Plan de Acción antes del 31-ene) | Negativo — alerta de vencimiento previo | (ver HU-02-D02) | RF-02-D02 |
| HU-B2-015/B3-012 (no escanear cédula) | Negativo — X-Road caído → carga manual documentada | (ver HU-03-D10) | RF-03-D06 |
| HU-B3-020 (TSA GSE) | Negativo — continuidad de sello en migración | (ver HU-10-D02) | RF-10-D02 |
| HU-B3-023 (cookies sin consentimiento) | Negativo — caducidad/versionado del consentimiento | (ver HU-01-D01) | RF-01-D01 |

---

# 3. DIVISIONES INVEST (HU grandes que conviene partir)

| HU original | Por qué viola INVEST | HU hijas propuestas |
|---|---|---|
| HU-B2-001 "iniciar y seguir el trámite 100% en línea" | Demasiado grande (épica): mezcla inicio, autenticación, diligenciamiento multipaso, validación de adjuntos, pago, verificación por interoperabilidad, radicación y resultado en CCD. No es estimable ni testeable como una sola HU. | (a) Iniciar trámite y autenticarse al nivel exigido (HU-03-D11) · (b) Diligenciar formulario multipaso con guardar/reanudar (HU-03-D04) · (c) Validar y cargar adjuntos (HU-03-D05) · (d) Verificación automática por interoperabilidad con fallback (HU-03-D10) · (e) Pagar en línea idempotente y conciliado (HU-03-D01/D02/D03) · (f) Radicar con consecutivo atómico (HU-03-D13) · (g) Recibir resultado en CCD (HU-B1-025 enriquecida) |
| HU-B1-002 "radicar PQRSD en línea" | Solo cubre el alta del ciudadano; el ciclo de vida completo (enrutar, trasladar, prorrogar, responder, notificar, cerrar, medir) está ausente. La HU base se queda en el front. | Mantener HU-B1-002 (radicar) + las nuevas HU-04-D01..D05 (back-office) como historias separadas por rol funcionario. |
| HU-B1-019/B3-009 "agendar cita" | Asume disponibilidad mágica; no contempla origen de la oferta, reprogramación, concurrencia ni no-show. | HU-06-D01 (administrar agenda) · HU-06-D02 (reprogramar) · HU-06-D03 (concurrencia) · HU-06-D04 (no-show), dejando HU-B1-019 solo como "reservar". |
| HU-B1-011 "publicar resolución rápido" | Esconde un flujo editorial con estados y SoD presentado como acción atómica. | HU-12-D01 (ciclo editorial con estados/rechazo) + HU-09-D03 (SoD), dejando HU-B1-011 como "editar y enviar a aprobación". |
| HU-B2-018 / HU-B1-008 (datos abiertos) | Publicar+descargar sin CRUD, versionado, validación ni frescura. | HU-11-D01 (CRUD/versionado/frescura) + HU-11-D02 (validación), dejando las base como "descargar" y "publicar inicial". |

---

# 4. VARIACIONES POR ROL detectadas (cobertura de actores faltante)

| Rol | Estado en HU base | HU del delta que lo cubren |
|---|---|---|
| Ciudadano anónimo vs identificado | Parcial (anónimo solo en PQRSD) | HU-09-D01 (anti-IDOR distingue propio/ajeno); radicado anónimo público se preserva |
| Funcionario de dependencia (back-office PQRSD/trámite) | **Ausente** | HU-03-D07, HU-04-D01..D05, HU-04-D07 |
| Administrador / configurador | Muy parcial | HU-01-D02/D03, HU-02-D01/D02/D04, HU-05-D01, HU-06-D01, HU-11-D01/D02, HU-12-D01/D02 |
| Editor vs Aprobador (SoD) | **Ausente como roles segregados** | HU-09-D03, HU-12-D01, HU-07-D01 |
| Oficial de seguridad / auditor | Solo HU-B1-020 (bloqueo login) | HU-09-D05/D06/D07 |
| Equipo técnico (interoperabilidad) | Parcial (despliegue) | HU-10-D01/D02/D03 |
| Equipo UX | Solo HU-B2-019 | HU-08-D01/D02 |
| Representante legal de menor | **Ausente** | HU-12-D04 |
| Oficial de cumplimiento (ITA/transparencia) | Parcial (HU-B1-014) | HU-02-D02 |

---

# 5. Resumen cuantitativo

| Categoría | Cantidad |
|---|---|
| **HU nuevas propuestas** | 57 (módulos: 01→4, 02→4, 03→13, 04→8, 05→2, 06→4, 07→3, 08→2, 09→7, 10→3, 11→2, 12→5) |
| **HU existentes enriquecidas con G/W/T** | 24 |
| **Escenarios G/W/T agregados (positivos+negativos)** | ~150 (cada HU nueva trae ≥2; las enriquecidas ≥1 negativo) |
| **HU grandes divididas (INVEST)** | 5 épicas → ~20 hijas |
| **Variaciones por rol incorporadas** | 9 roles antes sin/ con cobertura parcial |
| **DoR/DoD añadidos** | en HU críticas (01-D02, 02-D01, 03-D01, 03-D09, 06-D01) |

### HU nuevas por módulo

| Módulo | HU nuevas | Roles nuevos cubiertos |
|---|---|---|
| 01 Estructura | 4 | editor, administrador |
| 02 Transparencia | 4 | editor, admin cumplimiento, admin directorio |
| 03 Servicios/Trámites | 13 | funcionario, sistema (SAP) |
| 04 PQRSD | 8 | funcionario, responsable PQRSD, denunciante reservado |
| 05 Participa | 2 | administrador de participación |
| 06 Canales | 4 | administrador de atención |
| 07 Accesibilidad | 3 | editor, ciudadano discapacidad visual |
| 08 Usabilidad | 2 | equipo UX |
| 09 Seguridad | 7 | oficial de seguridad, auditor |
| 10 Interoperabilidad | 3 | equipo técnico |
| 11 Datos Abiertos | 2 | administrador del portal |
| 12 Gestión Contenidos | 5 | editor/aprobador (SoD), administrador |
| **TOTAL** | **57 HU nuevas** | |

---

# 6. Preguntas abiertas (heredadas de los deltas — NO se inventan)

1. Período de retención/expiración de borradores de trámite (HU-03-D04). — Líder de trámites + Protección de Datos.
2. Política de reembolso al desistir un trámite pagado (HU-03-D08). — Tesorería + Líder de trámites.
3. Catálogo de trámites sujetos a silencio administrativo positivo y su término (HU-03-D09). — Secretaría Jurídica.
4. Matriz trámite ↔ nivel de autenticación exigido (HU-03-D11 / C-08). — G-CIO + AND.
5. Agenda propia vs integración con sistema de turnos (HU-06-D01). — Oficina de Atención.
6. Umbral de tasa de éxito de tareas para "cumple" en usabilidad (HU-08-D01 / A-14). — Equipo UX.
7. Aportes de Participa dentro de la sede vs SUCOP (afecta HU-05-D01/D02). — Participación + DNP/SUCOP.
8. Qué contenidos se traducen y tratamiento de lenguas étnicas (afecta gestión de contenidos). — Comunicaciones.

---

# 7. Estimación de completitud y veredicto

- **Cobertura del artefacto base de HU: ~45-55%** (el más bajo de los cuatro artefactos). A diferencia de RF/RN/UC —que sí aplicaron un protocolo de extracción exhaustiva con autocontrol— las HU se quedaron en **5-7 por módulo, casi todas del camino feliz del ciudadano**.
- **Causa raíz:** el creador de HU no derivó historias del back-office ni de los escenarios no-felices, y no generó la HU "espejo" por rol cuando un RF/UC implicaba un actor administrador/funcionario/editor. Tampoco incorporó los escenarios negativos como criterios G/W/T (validación, autorización, concurrencia, caída de integración).
- **Retroalimentación al creador:** por cada RF con verbo de administración (CRUD, configurar, aprobar, trasladar, cerrar) generar de oficio la HU del rol correspondiente; y por cada HU de "camino feliz" exigir al menos un escenario negativo (error de validación, autorización denegada, concurrencia, indisponibilidad). Por cada entidad con estados (radicado, pago, cita, contenido, dataset) generar la HU de transición + su escenario de concurrencia/IDOR.
- **Veredicto:** el artefacto de HU **SÍ requiere otra pasada** (o la integración íntegra de este delta de 57 HU nuevas + 24 enriquecidas + 5 divisiones INVEST). El residual aquí NO es marginal como en RF/RN/UC: es estructural (faltan los roles de back-office y los escenarios negativos completos). Una vez integrado este delta, la cobertura sube a ~90% y queda alineada con los otros tres artefactos.

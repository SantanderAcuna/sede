# Módulo 04 — PQRSD

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** formulario de Peticiones, Quejas, Reclamos, Sugerencias y Denuncias; solicitud anónima; generación de radicado y acuse; seguimiento de estado; validación accesible; integración con gestión documental (SGDEA) y registro electrónico 24/7; plazos legales de respuesta.
> **Cruces:** captcha/seguridad de formularios → módulo **09 Seguridad**; informes trimestrales de PQRSD → módulo **02 Transparencia**; gestión documental/SGDEA → módulo **12 Gestión de Contenidos**; derechos ARCO → módulo **09/12**.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Canal de ejercicio del **derecho de petición** (Art. 23 CP). El formulario garantiza el anonimato opcional, no impone restricciones técnicas a los adjuntos, genera radicado y acuse automáticos, permite seguimiento por radicado e integra con el sistema documental. Es uno de los módulos más sensibles para el cumplimiento ITA y el más afectado por la contradicción **C-01** (restricción de adjuntos).

## 2. Requisitos Funcionales (RF)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-031 / RF-B3-097 | **Formulario PQRSD** con campos mínimos: tipo (Petición/Queja/Reclamo/Sugerencia/Denuncia/Solicitud de información), opción anónima, nombre/razón social, tipo y nº de documento (CC/NUIP/CE/NIT/Pasaporte), correo (obligatorio), teléfono, dirección de notificación, canal de respuesta, dependencia destinataria, objeto (≤2.000 car.), adjuntos, aceptación de condiciones. | #7,#21,#225,#239,#241,#221 | Must | Completados los campos obligatorios y enviado → radicado + acuse al correo + registro en gestión documental. |
| RF-B1-032 / RF-B3-098 / RF-B2-069 | **Solicitud anónima**: al marcarla se deshabilitan los campos de identificación; aviso sobre garantías y limitaciones del anonimato (georreferenciación, IP, metadata, navegador privado) y limitación de respuesta. | #239,#241,#125,#209 | Must | Marcar "anónima" deshabilita visualmente los campos de identidad y muestra el aviso; el sistema acepta y genera radicado consultable. |
| RF-B1-033 / RF-B3-104 | **Sin restricciones técnicas** de formato, tamaño ni cantidad de adjuntos (garantía del derecho de petición). | #241,#209 | Must | Un archivo grande en formato no estándar se acepta dentro de los límites técnicos del servidor, sin rechazo por formato/tamaño. |
| RF-B1-034 / RF-B3-100 | **Seguimiento** por número de radicado: tipo, fecha de radicación, dependencia asignada, estado (radicado/en trámite/respondido), fecha estimada de respuesta. | #7,#21,#225,#241,#221 | Must | Ingresar radicado → estado actualizado con la información definida. |
| RF-B3-099 | **Acuse de recibo automático** con fecha/hora; número de radicación en ≤24 horas hábiles. | #221 | Must | Envío exitoso → email de confirmación inmediato y radicado numerado en ≤24 h hábiles. |
| RF-B1-035 / RF-B3-143 | **Validación** cliente y servidor; mensajes de error descriptivos, accesibles (`aria-describedby`), en español claro; foco al campo erróneo. | #19,#53,#241,#243,#132 | Must | Enviar con correo vacío → campo resaltado, mensaje descriptivo y foco en el campo erróneo. |
| RF-B3-101 | **Anti-spam**: captcha accesible o equivalente en el formulario. *(captcha accesible → módulo 09)* | #221 | Must | Bots con envío masivo son bloqueados sin afectar a humanos. |
| RF-B3-102 | **Mensaje de falla** claro con motivo y opción de reintentar si el sistema falla durante el diligenciamiento. | #221 | Must | Falla del servidor → "Ocurrió un error al enviar. Intente nuevamente o contáctenos al [teléfono]". |
| RF-B1-036 / RF-B3-103 | Integración con el **SGDEA**: radicado automático, expediente electrónico, notificación a la dependencia; disponible en **móvil**. | #225,#241,#251,#221 | Must | Envío exitoso → en <5 s genera radicado, crea expediente en SGDEA y notifica a la dependencia. |
| RF-B2-070 | Tramitar consultas de datos personales en ≤10 días hábiles (prorrogable 5) y reclamos en ≤15 (prorrogable 8); alerta al responsable a 3 días del vencimiento. *(ARCO — ver módulo 09/12)* | #122 | Must | Solicitud ARCO recibida → contador de días hábiles inicia; alerta a 3 días hábiles del vencimiento. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-04-D01 | **Enrutamiento y reasignación de PQRSD por competencia**: asignación (automática o manual) a la dependencia, reasignación entre dependencias y **traslado por competencia a otra autoridad** con notificación al peticionario. | [WEB] `extracto-web-docs.md` lista explícitamente "Traslado por competencia a otras autoridades"; [NORMATIVA] Ley 1755 Art.21 (resuelve A-11) | Must | PQRSD que no compete a la Alcaldía → funcionario la traslada, el sistema notifica al ciudadano la entidad receptora y la fecha. |
| RF-04-D02 | **Gestión de respuesta y cierre de la PQRSD**: el funcionario carga la respuesta, el sistema la notifica por el canal elegido, registra fecha de respuesta y cierra el radicado calculando el cumplimiento del plazo. | [DOMINIO] el ciclo post-radicación es la "pregunta abierta" del propio módulo §9 | Must | Respuesta cargada → ciudadano notificado, estado "Respondido", y el sistema marca si fue dentro o fuera del término legal. |
| RF-04-D03 | **Cómputo de plazos legales diferenciados por tipo** (petición general, información, consulta, copias) con calendario hábil del Distrito y semáforo de vencimiento. | [NORMATIVA] Ley 1755 Arts.14; cruza con RF-B2-068 (calendario hábil) y HU-B3-022 | Must | Reclamo (15 días hábiles) vs. consulta (30) → el contador aplica el plazo correcto excluyendo festivos del Distrito. |
| RF-04-D04 | **Validación de campos del formulario PQRSD**: correo con formato válido, documento según tipo (CC/CE/NIT/Pasaporte con su patrón), tope de 2.000 caracteres en objeto, obligatoriedad condicional según anonimato. | [DOMINIO] RF-B1-035 habla de validación genérica; falta el detalle por campo | Must | NIT con dígito de verificación inválido → error inline; objeto >2.000 → bloquea y muestra contador. |
| RF-04-D05 | **Notificación electrónica de la respuesta conforme CPACA** (correo procesal/SMS/CCD) con constancia de envío y, si aplica, soporte para notificación por aviso. | [WEB] el sitio menciona notificación "por edicto, físico, correo, SMS, correo certificado"; [NORMATIVA] Ley 1437 Art.56 | Should | Respuesta lista → notificación al canal autorizado con acuse y registro de fecha de notificación. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B2-006 | Disponibilidad ARCO | ≥99% en horario laboral | #122 |
| RNF-B1-008 | Rendimiento | Radicado generado en <5 s tras el envío | #225,#241 |
| RNF-B3-003 | Responsive | Formulario operable en móvil sin pérdida de funcionalidad | #60,#70 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-04-D01 | Formato del radicado | **[PREGUNTA ABIERTA]** Definir la estructura del número de radicado (prefijo dependencia + año + consecutivo) y su unicidad garantizada bajo concurrencia. Responsable: Gestión Documental. | [PREGUNTA ABIERTA]+[WEB] |
| RNF-04-D02 | Concurrencia de radicación | El consecutivo de radicado debe asignarse de forma atómica para evitar números duplicados ante envíos simultáneos (cruza RF-B2-067). | [DOMINIO] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-009 | Quejas/denuncias anónimas se rigen por la normativa de protección al denunciante; no aplica a solicitudes de información que requieran notificación. | Art.38 Ley 190/1995; Art.69 Ley 734/2002; Art.81 Ley 962/2005 | #241,#225 |
| RN-B1-010 | El formulario **no puede** establecer restricciones técnicas (formatos/tamaños/cantidad) a la radicación. | Art.23 CP; Ley 1755/2015 | #241 |
| RN-B1-011 | Las consultas de acceso a información pública son **gratuitas** (excepciones: mercantil, laboral, profesional, seguridad social). | Ley 2052/2020 Art.15; Ley 1712/2014 | #22 |
| RN-B3-026 | Acuse de recibo automático inmediato; radicado en ≤24 horas hábiles. | Ley 1437/2011; Ley 1755/2015 | #221 |
| RN-B2-019 | Disponer de formularios normalizados para la presentación de peticiones y documentos. | Decreto 620/2020 Art.2.2.17.6.5 | #143 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-04-D01 | **Cómputo de plazos diferenciados por tipo de petición** sobre calendario hábil del Distrito (excluye festivos): petición general **15 días hábiles**; petición de documentos/información **10 días hábiles**; consulta **30 días hábiles**; petición entre autoridades **10 días**. El semáforo de vencimiento aplica el plazo correcto por tipo. (Motiva RF-04-D03; opera RN-B3-026.) | Ley 1755/2015 Arts.14,19,20,21 | [NORMATIVA] |
| RN-04-D02 | **Prórroga/ampliación del término:** cuando no sea posible responder en el plazo, la entidad puede ampliarlo informando al peticionario antes del vencimiento, con la nueva fecha; la prórroga no puede exceder el límite legal. (Hallazgo: el sitio menciona "prórroga o ampliación".) | Ley 1755/2015 Art.14 par. | [NORMATIVA]+[WEB] |
| RN-04-D03 | **Traslado por competencia:** si la PQRSD no es de competencia de la Alcaldía, se traslada a la autoridad competente **dentro de los 5 días siguientes** a su recepción, notificando al peticionario la entidad receptora y la fecha. (Motiva RF-04-D01; resuelve A-11.) | Ley 1755/2015 Art.21 | [NORMATIVA]+[WEB] |
| RN-04-D04 | **Cierre y medición de cumplimiento:** al cargar la respuesta el radicado pasa a "Respondido", se notifica por el canal autorizado y el sistema marca **dentro/fuera del término legal**; una PQRSD no se cierra sin respuesta registrada. (Motiva RF-04-D02; cierra el vacío del ciclo post-radicación.) | Ley 1755/2015; Ley 1437/2011 | [NORMATIVA]+[DOMINIO] |
| RN-04-D05 | **Notificación electrónica CPACA:** la respuesta se notifica por el canal autorizado (correo procesal, SMS, correo certificado, edicto o físico) con **constancia de envío y fecha de notificación**; la notificación electrónica tiene preferencia cuando existe dirección procesal electrónica autorizada. (Motiva RF-04-D05; opera RN-B3-019.) | Ley 1437/2011 Arts.56,67,69; Decreto 2106 Arts.46,47 | [NORMATIVA]+[WEB] |
| RN-04-D06 | **Identidad reservada del denunciante:** en quejas/denuncias anónimas o con identidad reservada, el sistema NO expone datos del peticionario y, cuando aplique, traslada a la Procuraduría con reserva. (Especifica RN-B1-009 en lo operativo.) | Art.38 Ley 190/1995; Art.81 Ley 962/2005 | [NORMATIVA] |
| RN-04-D07 | **Unicidad y atomicidad del radicado:** el número de radicado se asigna de forma atómica (consecutivo único) bajo concurrencia; dos envíos simultáneos nunca comparten número. (Motiva RNF-04-D02; opera RN-B3-026.) | Buena práctica de gestión documental | [DOMINIO] |
| RN-04-D08 | **Gratuidad con excepciones tasadas:** la consulta de información pública es gratuita salvo costo de reproducción de copias, que no puede exceder el costo de reproducción; las excepciones (mercantil, laboral, profesional, seguridad social) se tasan. (Especifica RN-B1-011 en el cobro de copias.) | Ley 1712/2014 Art.26; Ley 2052/2020 Art.15 | [NORMATIVA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-001 / UC-B2-008 / UC-B3-002 | Radicar PQRSD en línea | Ciudadano | Acceso → tipo de solicitud → (opcional anónimo) → formulario (datos, objeto, adjuntos) → captcha → acepta privacidad → envía → radicado + acuse. | #7,#225,#239,#241,#125 |
| UC-B3-003 | Radicar PQRSD anónima | Ciudadano anónimo | Selecciona "anónima" → campos de identificación deshabilitados → ingresa objeto y adjuntos → recibe número de seguimiento sin datos personales. | #209 |
| UC-B1-002 / UC-B2-003 | Consultar estado de PQRSD | Ciudadano | "Consultar estado" → ingresa radicado → tipo, fecha, dependencia, estado, fecha estimada → muestra/descarga respuesta si existe. | #7,#239,#241 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-002 | Como ciudadano quiero radicar una PQRSD en línea sin ir a la Alcaldía. | **Positivo:** **Dado** que el formulario tiene todos los campos obligatorios completos **cuando** el ciudadano lo envía **entonces** se genera radicado y se notifica al correo en <5 min. <br> **Negativo:** **Dado** dos envíos simultáneos de PQRSD **cuando** el sistema asigna el radicado **entonces** cada envío recibe un número único de forma atómica y ninguno comparte consecutivo. | #7,#225,#239 |
| HU-B1-012 / HU-B2-003 | Como ciudadano quiero hacer una queja anónima sin exigir datos de identidad. | **Positivo:** **Dado** que el ciudadano marca la opción "anónima" **cuando** envía el formulario **entonces** los campos de identidad quedan deshabilitados, se genera radicado y se muestra el aviso de garantías y limitaciones del anonimato. <br> **Negativo:** **Dado** una queja con identidad reservada **cuando** un funcionario la consulta en el back-office **entonces** no ve los datos del peticionario y el sistema deniega y registra cualquier intento de revelarlos. | #239,#241,#125 |
| HU-B1-004 / HU-B3-007 | Como ciudadano quiero consultar el estado por radicado sin llamar. | **Positivo:** **Dado** que el ciudadano ingresa un radicado válido **cuando** ejecuta la consulta **entonces** el sistema muestra en <3 s el tipo, fecha, dependencia, estado y fecha estimada de respuesta. <br> **Negativo:** **Dado** un radicado que no existe en el sistema **cuando** el ciudadano lo consulta **entonces** ve un mensaje claro "Radicado no encontrado", sin mostrar error técnico. | #7,#239,#221 |
| HU-B1-023 / HU-B3-006 | Como ciudadano quiero que el formulario indique errores en tiempo real y cuál campo. | **Positivo:** **Dado** un correo con formato inválido en el campo correspondiente **cuando** el ciudadano sale del campo **entonces** aparece de forma inmediata el mensaje "Ingrese un correo válido (ej: nombre@correo.com)" junto al campo, sin esperar al envío. <br> **Negativo:** **Dado** un NIT con dígito de verificación inválido o un campo objeto con más de 2.000 caracteres **cuando** el ciudadano intenta continuar **entonces** recibe error inline en el campo correspondiente y el envío queda bloqueado hasta corregir. | #170,#225,#76 |
| HU-B3-008 | Como ciudadano quiero el radicado en ≤24 horas hábiles tras enviar la PQRSD. | **Positivo:** **Dado** que el ciudadano envía la PQRSD a las 3 PM del lunes **cuando** el sistema procesa el envío **entonces** genera acuse inmediato y el radicado numerado queda disponible antes de las 3 PM del martes. | #221 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-04-D01 | Como funcionario quiero asignar, reasignar y trasladar por competencia una PQRSD para que la resuelva la autoridad correcta. | **Positivo:** Dado una PQRSD que no compete a la Alcaldía, cuando la traslado a la entidad competente, entonces el sistema notifica al ciudadano la entidad receptora y la fecha.<br>**Negativo (plazo):** Dado un traslado, cuando lo efectúo después de 5 días de la recepción, entonces el semáforo lo marca fuera de término.<br>**Negativo (reasignación interna):** Dado una asignación errónea a una dependencia, cuando la reasigno, entonces el expediente conserva la traza de ambas asignaciones. | RF-04-D01 · RN-04-D03 · UC-035 A3 / UC-037 · [NORMATIVA] Ley 1755 Art.21 · [WEB] |
| HU-04-D02 | Como funcionario quiero cargar la respuesta, notificarla y cerrar el radicado midiendo el cumplimiento del plazo para dar trazabilidad al ciclo de la PQRSD. | **Positivo:** Dado una PQRSD en trámite, cuando cargo la respuesta y notifico, entonces el estado pasa a "Respondido" y el sistema marca si fue dentro o fuera del término legal.<br>**Negativo:** Dado un intento de cerrar sin respuesta registrada, cuando pulso "Cerrar", entonces el sistema lo impide ("no se cierra sin respuesta"). | RF-04-D02 · RN-04-D04 · UC-036 A2 · [DOMINIO]+[NORMATIVA] |
| HU-04-D03 | Como responsable de PQRSD quiero que el contador aplique el plazo legal correcto según el tipo de petición sobre el calendario hábil del Distrito para medir el vencimiento con precisión. | **Positivo:** Dado una consulta (30 días hábiles), cuando se computa el plazo, entonces excluye festivos del Distrito y aplica 30 días.<br>**Negativo:** Dado una petición de información (10 días) tratada como general (15), cuando se compara, entonces el sistema usa el plazo correcto por tipo, no un plazo único. | RF-04-D03 · RN-04-D01 · RN-TX-D01 · UC-035 E3 · [NORMATIVA] Ley 1755 Art.14 |
| HU-04-D04 | Como funcionario responsable quiero prorrogar el término informando al peticionario antes del vencimiento para responder bien cuando no alcanza el plazo ordinario. | **Positivo:** Dado una PQRSD dentro del término con causal, cuando registro la prórroga con motivación y nueva fecha, entonces se notifica al peticionario y se actualiza el semáforo.<br>**Negativo (tardía):** Dado el término ya vencido, cuando intento prorrogar, entonces se rechaza y cuenta como respuesta extemporánea.<br>**Negativo (excede máximo):** Dado una nueva fecha que excede el límite legal, cuando la ingreso, entonces se bloquea. | RF-04-D03 · RN-04-D02 · UC-046 · [NORMATIVA] Ley 1755 Art.14 par. · [WEB] |
| HU-04-D05 | Como funcionario quiero notificar la respuesta por el canal autorizado con constancia y fecha para que la notificación tenga validez legal. | **Positivo:** Dado un peticionario con dirección procesal electrónica autorizada, cuando notifico la respuesta, entonces se envía por ese canal con acuse y se registra la fecha de notificación.<br>**Negativo (sin autorización):** Dado que no hay dirección electrónica autorizada, cuando notifico, entonces se usa el canal físico/aviso y se deja constancia. | RF-04-D05 · RN-04-D05 · UC-036 E3 · [NORMATIVA] Ley 1437 Arts.56,67,69 |
| HU-04-D06 | Como ciudadano quiero que el formulario PQRSD valide cada campo en tiempo real para corregir antes de enviar. | **Positivo:** Dado el campo objeto, cuando escribo, entonces veo un contador y se bloquea al superar 2.000 caracteres.<br>**Negativo (NIT):** Dado un NIT con dígito de verificación inválido, cuando salgo del campo, entonces veo error inline.<br>**Negativo (correo):** Dado un correo con formato inválido, cuando salgo del campo, entonces veo el mensaje sin esperar al envío. | RF-04-D04 · [DOMINIO] (enriquece HU-B1-023) |
| HU-04-D07 | Como denunciante con identidad reservada quiero que el back-office no exponga mis datos para estar protegido conforme a la ley. | **Positivo:** Dado una denuncia con identidad reservada, cuando un funcionario la consulta, entonces no ve los datos del peticionario y, cuando aplica, se traslada con reserva a la Procuraduría.<br>**Negativo:** Dado un funcionario sin autorización, cuando intenta revelar la identidad reservada, entonces el acceso se deniega y se registra. | RN-04-D06 · UC-035 E4 · [NORMATIVA] Ley 190/1995 Art.38 |
| HU-04-D08 | Como ciudadano quiero que la consulta de información sea gratuita y solo se cobre el costo de reproducción de copias para no pagar de más por información pública. | **Positivo:** Dado una petición de información, cuando la entidad responde, entonces no hay cobro salvo la reproducción de copias al costo.<br>**Negativo:** Dado un cobro superior al costo de reproducción, cuando se calcula, entonces el sistema lo marca como no conforme. | RN-04-D08 · [NORMATIVA] Ley 1712 Art.26 |

## 7. Datos / Entidades del módulo

- **PQRSD:** tipo de solicitud, anonimato (booleano), nombre/razón social, documento (CC/NUIP/CE/NIT/Pasaporte), correo (obligatorio), teléfono, dirección de notificación, canal de respuesta, dependencia destinataria, objeto (≤2.000 car.), adjuntos, número de radicado, estado, fecha/hora de recepción, respuesta, fecha/entidad de traslado si aplica. (#239,#225,#209,#217)

## 8. Integraciones

- **SGDEA**: radicado, expediente, notificación a dependencia. (#225,#241,#251)
- **Registro electrónico 24/7/365** (RF-B2-067/068, RF-B3-118): recibe documentos los 365 días, asigna consecutivo con fecha/hora, acuse automático, distribución; calendario hábil/inhábil. *(ver módulo 12)*. (#143,#217)
- **Captcha accesible** (módulo 09).
- **Procuraduría**: solicitudes de identidad reservada. (#239)

## 9. Ambigüedades y preguntas abiertas

- **[C-01 — ALTA]** **Restricción de adjuntos**: la Guía Técnica (#241) prohíbe restricciones técnicas, pero la implementación actual de la Alcaldía (#239) limita a **PDF/JPG/PNG y 10 MB**. Además Anexo 5 (libre) vs. Anexo 5.1 ("la entidad define"). **Constituye un incumplimiento normativo concreto a resolver en el diseño.**
- **[C-04]** Ubicación del formulario PQRSD en el menú ("Atención y Servicios" según norma, sección propia en la implementación actual). (#225,#241,#239)
- **[Captcha vs A11y]** El captcha obligatorio puede ser barrera para discapacidad visual → debe ser accesible (auto-detectable o alternativa de audio). (#129,#152,#153)
- **[A-11]** Dependencia destinataria por defecto "Despacho del Alcalde": ¿enrutamiento automático o manual? (#239)
- **[PREGUNTA ABIERTA]** Proceso interno de gestión de PQRSD (recepción → enrutamiento → respuesta → seguimiento) una vez radicada. (#239)

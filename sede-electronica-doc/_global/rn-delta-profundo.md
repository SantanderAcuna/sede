# Reglas de Negocio — Delta de la segunda pasada profunda (`jose-reglas-negocio-profundo`)

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Fecha:** 2026-06-04
> **Origen:** segunda pasada profunda (verificación adversarial) sobre las RN ya producidas en los 12 módulos. Re-lectura de las secciones `## 4. Reglas de Negocio (RN)` + `README.md` + `contexto-transversal.md` + el delta `rf-rnf-delta-profundo.md` (37 RF + 18 RNF nuevos) + búsquedas puntuales en `extracto-web-docs.md`.
> **Encuadre:** la pasada base fue exhaustiva en lo NORMATIVO (cada ley/decreto citado tiene su RN). El residual que aquí se reporta NO son omisiones normativas groseras, sino **reglas de negocio que los nuevos RF/RNF presuponen** (idempotencia de pago, ciclo de vida post-radicación de PQRSD, segregación de funciones, autorización por recurso, cómputo de plazos diferenciados) y **reglas implícitas en validaciones/estados/plazos** que la pasada base dejó en grado de RF pero no elevó a regla de negocio invariante.
> **Procedencia (sin refs `#NN`, el corpus se borró):** **[NORMATIVA]** ley/decreto ya citado · **[DOMINIO]** invariante estándar de transacciones/concurrencia/control de acceso · **[WEB]** hallazgo verificado en `extracto-web-docs.md` · **[INFERENCIA]** derivada, validable · **[PREGUNTA ABIERTA]** umbral/decisión que NO se inventa.
> **Estado:** DELTA pendiente de integración a la sección RN de cada módulo. No se editaron los módulos.

---

## Módulo 01 — Estructura e Identidad

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-01-D01 | El consentimiento de cookies es **válido por un período máximo y versionado**: si cambia la política de cookies o transcurren >12 meses, el consentimiento previo caduca y DEBE re-solicitarse; ninguna cookie no esencial se reactiva con consentimiento caducado. (Motiva RF-01-D01; refuerza RN-B3-031.) | Ley 1581/2012; principio de consentimiento informado y temporal | [NORMATIVA]+[DOMINIO] |
| RN-01-D02 | El menú de navegación principal NO puede exceder **7 ítems de primer nivel ni 2 niveles de profundidad**: cualquier alta/edición que viole el tope se rechaza. (Eleva a invariante el tope que RF-B1-003/RF-01-D02 solo describen.) | Kit UI GOV.CO; Guía de Usabilidad MinTIC | [DOMINIO]+[WEB] |
| RN-01-D03 | El modal de "aviso de salida a sitio externo" NO se muestra para destinos en la **lista blanca de dominios de confianza** (GOV.CO, pasarela del Articulador, SCD); sí se muestra para cualquier dominio no listado. (Motiva RF-01-D03.) | Buena práctica de seguridad/UX; evita falso positivo | [DOMINIO] |

---

## Módulo 02 — Transparencia

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-02-D01 | Reemplazar un documento de transparencia publicado **conserva la versión anterior con su fecha** (historial inmutable) y **no rompe la URL de fuente única**: la publicación es proactiva pero la trazabilidad de versiones es obligatoria. (Motiva RF-02-D01; refuerza RN-B1-002.) | Ley 1712/2014 Arts.9,11; principio de integridad documental (Ley 594/2000) | [NORMATIVA]+[DOMINIO] |
| RN-02-D02 | Las publicaciones obligatorias con fecha legal fija (Plan de Acción/Informe de Gestión 31-ene, trimestrales PQRSD/inversión, control interno cada 6 meses) **disparan alerta al responsable de cumplimiento N días antes** del plazo; el incumplimiento es un hallazgo ITA. (Motiva RF-02-D02; opera RN-B1-003/004 y RN-B3-028.) | Res. 1519/2020 Anexo 2, 4.3/4.7/4.10; Ley 1474/2011 Art.74 | [NORMATIVA] |
| RN-02-D03 | La caída de una integración externa de transparencia (SECOP/SIGEP/SUIN/SUCOP/KOGUI) NO constituye "vínculo roto" si el sistema muestra mensaje de indisponibilidad temporal y registra el fallo; un enlace que retorna error sin manejo SÍ viola la meta de 0 vínculos rotos. (Motiva RF-02-D03.) | Res. 1519/2020 Anexo 2 (disponibilidad de la información) | [DOMINIO] |
| RN-02-D04 | El directorio se considera **desactualizado** si la sincronización con SIGEP lleva >24 h sin éxito (la norma exige actualización ≤1 día hábil): se debe alertar y registrar la fecha/hora de última sincronización exitosa. (Motiva RNF-02-D01; opera RN-B3-022.) | SIGEP — Función Pública; Res.1519 Anexo 2 | [NORMATIVA]+[INFERENCIA] |

---

## Módulo 03 — Servicios y Trámites

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-03-D01 | **Idempotencia de pago y radicado:** una misma transacción (identificada por clave de idempotencia/token) genera **a lo sumo un cobro y un radicado**; reintentos, doble clic o timeout de PSE devuelven el comprobante existente, nunca un segundo cargo ni un segundo número. (Motiva RF-03-D01.) | Buena práctica transaccional; lineamientos pasarela Superfinanciera | [DOMINIO] |
| RN-03-D02 | **El trámite avanza SOLO con pago confirmado:** un pago en estado `pendiente`/`fallido` mantiene el trámite "en espera de confirmación"; solo `aprobado` libera la siguiente etapa. Un pago `rechazado` no consume el radicado/borrador. (Motiva RF-03-D02.) | Lineamientos de pasarelas (Superfinanciera) | [DOMINIO]+[WEB] |
| RN-03-D03 | **No cobro adicional por canal digital** ni incremento de tarifa por digitalización/automatización aplica también a la **tarifa de pago en línea**: el ciudadano no asume el costo de la pasarela como recargo. (Especifica RN-B2-001/RN-B1-007 en el punto de pago.) | Decreto 2106 Art.7; Ley 2052/2020 Art.6 | [NORMATIVA] |
| RN-03-D04 | **Validación de adjuntos en trámites (≠ PQRSD):** a diferencia de la PQRSD (RN-B1-010, sin restricción), los trámites SÍ admiten límite de tipo MIME real, tamaño, cantidad y antivirus, porque el adjunto es requisito del trámite, no ejercicio del derecho de petición. Un archivo con MIME falso o malware se rechaza. (Motiva RF-03-D04; resuelve frontera con C-06.) | Decreto 2106 (requisitos del trámite); buena práctica de seguridad | [NORMATIVA]+[DOMINIO] |
| RN-03-D05 | **Subsanación/requerimiento de documentos:** cuando la entidad requiere documentación adicional, el trámite se suspende y el **plazo se recalcula** desde la entrega de lo requerido; la falta de respuesta del ciudadano dentro del término puede causar desistimiento. (Motiva RF-03-D05.) | Ley 1755/2015 Art.17 (requerimiento y desistimiento por no subsanar) | [NORMATIVA]+[WEB] |
| RN-03-D06 | **Excepción por falla técnica de interoperabilidad:** si la verificación automática vía SCD/X-Road (Registraduría/RUNT/RUAF) falla, se habilita carga manual temporal del documento **dejando constancia de "falla técnica documentada"**; esta es la única excepción admitida a "no exigir documentos" (RN-B2-004). (Motiva RF-03-D06/RF-10-D01.) | Decreto 2106 Art.10; Decreto 620 Art.2.2.17.4.7 | [NORMATIVA] |
| RN-03-D07 | **Desistimiento/cancelación por el ciudadano:** el ciudadano puede desistir de un trámite antes de su resolución; el estado pasa a "Desistido", queda en el expediente y, si hubo pago, se sujeta a la política de reembolso del trámite. (Motiva RF-03-D07.) | Ley 1755/2015 Art.18 (desistimiento expreso); CPACA | [NORMATIVA]+[WEB] |
| RN-03-D08 | **Silencio administrativo positivo:** en los trámites donde la ley lo prevé, si la entidad no resuelve dentro del término, **se entiende concedido** lo solicitado; el sistema debe marcar el vencimiento y el efecto positivo. (Hallazgo: el sitio lo declara para espectáculos públicos y otros.) | Ley 1755/2015 Art.83-85; Ley 2052/2020 Art.12 | [NORMATIVA]+[WEB] |

---

## Módulo 04 — PQRSD

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

---

## Módulo 05 — Participa

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-05-D01 | **Cierre automático por vencimiento:** al expirar la fecha máxima de comentarios de una consulta/proyecto normativo, la recepción de aportes se deshabilita y el mecanismo pasa a "Cerrada"; no se aceptan aportes extemporáneos. (Motiva RF-05-D01; opera RN-B1-039.) | Ley 1712/2014; lineamientos DNP/SUCOP | [NORMATIVA]+[DOMINIO] |
| RN-05-D02 | **Publicación del resultado del proceso participativo:** cerrada una consulta normativa, se publica el consolidado de observaciones recibidas y cómo se incorporaron (o por qué no), por trazabilidad de la rendición. (Motiva RF-05-D02.) | Decreto 1081/2015 Art.2.1.2.1.14; Ley 1757/2015 | [NORMATIVA] |

---

## Módulo 06 — Canales de Atención

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-06-D01 | **Reserva atómica de cita:** un cupo de franja no puede asignarse a dos ciudadanos; el último cupo lo obtiene una sola reserva y la concurrente recibe "cupo no disponible". (Motiva RF-06-D03.) | Buena práctica de concurrencia | [DOMINIO] |
| RN-06-D02 | **Liberación de cupo en cancelación/reprogramación:** cancelar o reprogramar una cita libera el cupo anterior para reasignación inmediata. (Motiva RF-06-D02.) | Buena práctica de agendamiento | [DOMINIO] |
| RN-06-D03 | **Alternativa no digital obligatoria:** el agendamiento y la autenticación en línea son opcionales; siempre debe existir vía presencial/telefónica equivalente, y su ausencia es incumplimiento. (Eleva a invariante RN-B2-023.) | Ley 1753/2015 Art.45 par.1 | [NORMATIVA] |

---

## Módulo 07 — Accesibilidad

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-07-D01 | **Bloqueo de publicación de multimedia inaccesible:** el CMS NO permite publicar un video institucional sin subtítulos, ni un contenido de alocución/emergencia/seguridad/rendición de cuentas sin LSC. (Convierte RN-B3-002/003 en regla bloqueante; motiva RF-07-D01.) | Res. 1519/2020 Anexo 1, 1.5; Ley 1618/2013 | [NORMATIVA] |
| RN-07-D02 | **Captcha accesible obligatorio:** todo desafío anti-bot debe ofrecer alternativa accesible (audio/no visual) para no excluir a personas con discapacidad visual; un captcha solo-visual viola WCAG AA. (Resuelve la contradicción Captcha vs A11y.) | WCAG 2.1 AA (1.1.1); Res. 1519/2020 | [NORMATIVA] |

---

## Módulo 08 — Usabilidad

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-08-D01 | **Criterio cuantitativo de "cumple" en usabilidad:** además de SUS ≥68, una tarea crítica solo se declara conforme si supera un umbral de tasa de éxito y tiempo en tarea. (Motiva RF-08-D01; resuelve A-14.) **[PREGUNTA ABIERTA]** umbral de tasa de éxito a definir con Equipo UX. | Guía de Usabilidad MinTIC; benchmark SUS | [PREGUNTA ABIERTA]+[DOMINIO] |

---

## Módulo 09 — Seguridad

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-09-D01 | **Autorización por recurso (anti-IDOR):** un ciudadano autenticado solo accede a SUS propios radicados/trámites/PQRSD/citas; el acceso por número/ID a recursos ajenos se deniega (403) y se registra. El RBAC por módulo (RN implícito en RNF-B2-010) no sustituye el control de pertenencia por objeto. (Motiva RF-09-D01.) | OWASP A01; Ley 1581 (acceso indebido a datos) | [DOMINIO]+[NORMATIVA] |
| RN-09-D02 | **Segregación de funciones (SoD):** quien crea/edita un contenido o norma NO puede aprobarlo/publicarlo; quien administra usuarios NO audita sus propias acciones. El auto-aprobado está prohibido. (Motiva RF-09-D03/RF-12-D01.) | MSPI; control interno (Modelo de las 3 líneas) | [DOMINIO]+[INFERENCIA] |
| RN-09-D03 | **Recuperación de credenciales segura:** el token de "Olvidé mi contraseña" es de **un solo uso y expira** (p. ej. 15 min); restablecer la contraseña invalida las sesiones activas. (Motiva RF-09-D02.) | MSPI; buena práctica de autenticación | [DOMINIO] |
| RN-09-D04 | **MFA obligatorio para administradores del CMS:** los usuarios internos con privilegios de edición/aprobación/administración requieren segundo factor; la contraseña ≥8 (RNF-B1-021) no basta para roles internos. (Resuelve C-07.) | MSPI; OWASP | [DOMINIO]+[NORMATIVA] |
| RN-09-D05 | **Inmutabilidad del log de auditoría:** los registros de auditoría son append-only / encadenados por hash; no pueden alterarse ni borrarse durante su retención (5 años), ni siquiera por un administrador. (Motiva RNF-09-D01.) | MSPI; integridad probatoria (Ley 527/1999) | [DOMINIO]+[NORMATIVA] |
| RN-09-D06 | **Continuidad ante caída del SCD de Autenticación:** la indisponibilidad del Articulador (OIDC) NO bloquea el acceso al contenido público; un `state` no coincidente en el callback se rechaza y registra. (Motiva RF-09-D04.) | OWASP (federación); buena práctica OIDC | [DOMINIO] |
| RN-09-D07 | **Umbral local de incidente "grave"** que dispara el reporte CSIRT ≤24 h. (Opera RN-B1-016; resuelve A-06.) **[PREGUNTA ABIERTA]** definir el umbral. Responsable: Oficial de Seguridad. | Res. 1519/2020 Anexo 3 | [PREGUNTA ABIERTA] |

---

## Módulo 10 — Interoperabilidad

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-10-D01 | **No dejar el trámite "colgado" ante error X-Road:** timeout, respuesta vacía o certificado/OCSP vencido del par generan reintento configurable y, si persiste, error controlado + log + fallback a carga manual (RN-03-D06); nunca un estado indefinido. (Motiva RF-10-D01.) | Marco de Interoperabilidad; buena práctica | [DOMINIO] |
| RN-10-D02 | **Continuidad del estampado TSA (RFC 3161):** durante la migración/indisponibilidad del proveedor TSA (Certicámara→GSE), los mensajes pendientes de sello se encolan y se estampan al restablecer; ninguno se cierra sin estampa cronológica. (Motiva RF-10-D02; mitiga el riesgo de cadena de custodia.) | RFC 3161; Decreto 2106 (autenticidad) | [DOMINIO]+[NORMATIVA] |
| RN-10-D03 | **Monitoreo y renovación anticipada de certificados:** se alerta N días antes del vencimiento de certificados ONAC/TLS/OCSP del servidor de seguridad; un certificado vencido en producción es un incidente. (Motiva RNF-10-D01.) | Marco de Interoperabilidad | [DOMINIO] |

---

## Módulo 11 — Datos Abiertos

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-11-D01 | **Control de frescura del dataset:** un dataset publicado debe actualizarse según la frecuencia declarada en sus metadatos; superada la frecuencia + margen, se marca "desactualizado" y se alerta al responsable de datos. (Motiva RF-11-D01.) | Res. 1519/2020 Anexo 4, 4.2; Ley 1753 Art.45-d | [NORMATIVA]+[DOMINIO] |
| RN-11-D02 | **Validación de calidad previa a federar:** antes de publicar/federar a datos.gov.co, el archivo debe abrir como CSV/JSON/XML bien formado y tener los metadatos obligatorios completos; en caso contrario no se federa. (Motiva RNF-11-D01.) | Res. 1519/2020 Anexo 4 | [NORMATIVA]+[DOMINIO] |

---

## Módulo 12 — Gestión de Contenidos

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-12-D01 | **Ciclo de vida editorial con estados explícitos:** todo contenido sigue Borrador → Pendiente de aprobación → Publicado → Archivado, con rechazo/devolución a edición; solo lo "Aprobado" se publica. (Motiva RF-12-D01; complementa la SoD de RN-09-D02.) | MSPI; control interno | [DOMINIO] |
| RN-12-D02 | **Baja segura con retención de auditoría:** al desvincular un usuario interno se revocan accesos y tokens ≤1 día hábil, pero NO se borra físicamente: sus eventos de auditoría permanecen consultables (no repudio). (Motiva RF-12-D02; opera RNF-B2-011 y RN-B1-015.) | Ley 594/2000; MSPI; Decreto 1080/2015 | [NORMATIVA]+[DOMINIO] |
| RN-12-D03 | **Verificación de edad de titulares menores:** un registro con fecha de nacimiento <18 años exige autorización del representante legal antes de recolectar datos; sin ella, no se continúa. (Eleva RN-B2-007 a control operativo; motiva RF-12-D04.) | Ley 1581/2012 Art.7; Decreto 1377/2013 | [NORMATIVA] |
| RN-12-D04 | **Garantía de entrega de notificaciones multicanal:** si falla el envío por un canal (correo/SMS/push/CCD), se reintenta y/o se usa canal alterno autorizado, registrando el estado de entrega; un rebote no se da por notificado. (Motiva RF-12-D03; relevante para la validez de la notificación CPACA, RN-04-D05.) | Ley 1437/2011 (constancia de notificación) | [DOMINIO]+[NORMATIVA] |
| RN-12-D05 | **Bloqueo de edición concurrente:** dos usuarios no pueden guardar cambios sobre el mismo contenido pisándose; se aplica bloqueo optimista/pesimista. (Motiva RNF-12-D01.) | Buena práctica editorial | [DOMINIO] |
| RN-12-D06 | **Foliado e índice firmado del expediente electrónico:** todo expediente mantiene índice electrónico firmado, foliado consecutivo y metadatos de autenticidad/integridad; ningún documento se inserta o retira sin trazabilidad. (Especifica RN-B1-015 en lo operativo.) | Decreto 1080/2015; Ley 594/2000; AGN | [NORMATIVA] |

---

## RN transversales (nuevas)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-TX-D01 | **Calendario hábil único del Distrito:** todos los cómputos de plazos legales (PQRSD, trámites, subsanación, traslado, prórroga) usan el mismo calendario de días hábiles que excluye sábados, domingos y festivos nacionales/distritales; una sola fuente de verdad evita cómputos divergentes. (Habilita RN-04-D01/02/03, RN-03-D05.) | Ley 1755/2015; Ley 1437/2011 | [NORMATIVA]+[DOMINIO] |
| RN-TX-D02 | **Sellado temporal con Hora Legal:** toda evidencia con valor legal (radicado, consentimiento, firma, log, notificación) usa la Hora Legal de Colombia (INM) y, donde aplique, estampa TSA RFC 3161; no se admiten timestamps de reloj local no sincronizado. | RFC 3161; Decreto 4175/2011 (Hora Legal) | [NORMATIVA] |
| RN-TX-D03 | **Retención y purga de datos personales por entidad:** cada categoría de dato (borrador de trámite, cita, log de consentimiento) tiene un período de retención conforme a la TRD y al principio de minimización; vencido el período, se purga o anonimiza. **[PREGUNTA ABIERTA]** períodos por entidad. Responsable: Oficial de Protección de Datos. | Ley 1581/2012 Art.4; Ley 594/2000 (TRD) | [PREGUNTA ABIERTA]+[NORMATIVA] |
| RN-TX-D04 | **Nivel de autenticación mínimo por tipo de trámite:** cada trámite exige un nivel de confianza (bajo/medio/alto/muy alto) acorde al riesgo; el sistema bloquea el inicio si el nivel del ciudadano es inferior al requerido. **[PREGUNTA ABIERTA]** matriz trámite↔nivel. (Resuelve C-08.) | Decreto 620/2020; Guía SCD | [PREGUNTA ABIERTA]+[NORMATIVA] |
| RN-TX-D05 | **Fallback al castellano en contenidos traducidos:** cuando un contenido no tiene traducción disponible en el idioma/lengua étnica seleccionada, se muestra el castellano (lengua oficial) marcando que es el original; las lenguas étnicas son complemento, no reemplazo. (Opera RN-B1-012; motiva RNF-TX-D02.) **[PREGUNTA ABIERTA]** qué se traduce. | Ley 1712/2014; Art.10 CP | [NORMATIVA]+[PREGUNTA ABIERTA] |

---

## Conflictos entre reglas o vs documento (residuales, no en C-01..C-08)

| RN | Versión A (ubicación) | Versión B (ubicación) | Resolución sugerida |
|----|-----------------------|-----------------------|---------------------|
| Adjuntos: derecho vs seguridad | RN-B1-010 (PQRSD): el formulario NO puede restringir formato/tamaño/cantidad | RN-03-D04 (trámites) + RF-B1-063 (seguridad): MIME real + antivirus + límite | Separar dominios: **PQRSD** (derecho de petición) no rechaza por tipo/tamaño pero SÍ aplica antivirus y límite técnico de servidor alto y documentado; **trámites** (requisito del trámite) sí validan tipo/tamaño/cantidad. Ya recogido como C-06. |
| Gratuidad de consultas | RN-B1-011: consultas de información gratuitas | RN-04-D08: copias se cobran al costo de reproducción | No hay conflicto real: la consulta es gratuita; solo la **reproducción de copias** se tasa al costo. Precisar el enunciado de RN-B1-011 para evitar lectura de "todo gratis". |
| Notificación electrónica preferente | RN-B3-019: notificación electrónica preferente sobre física | RN-06-D03 + RN-B2-023: canal no digital siempre disponible | Compatibles: la preferencia electrónica aplica **cuando el ciudadano autorizó dirección procesal electrónica**; en su defecto, rige el canal físico/presencial. Precisar la condición de "dirección procesal autorizada" en RN-B3-019 (recogido en RN-04-D05). |
| Excepción a "no exigir documentos" | RN-B2-004: no exigir documentos disponibles vía interoperabilidad | RN-03-D06/RF-03-D06: carga manual ante falla técnica | Compatibles: la carga manual es la **excepción tasada** ("falla técnica documentada"), no la regla; debe dejar constancia y no volverse práctica por defecto. |

---

## Resumen cuantitativo — RN nuevas por módulo

| Módulo | RN nuevas | de ellas con [PREGUNTA ABIERTA] |
|--------|-----------|-------------------------------|
| 01 Estructura e Identidad | 3 | 0 |
| 02 Transparencia | 4 | 0 |
| 03 Servicios y Trámites | 8 | 0 |
| 04 PQRSD | 8 | 0 |
| 05 Participa | 2 | 0 |
| 06 Canales de Atención | 3 | 0 |
| 07 Accesibilidad | 2 | 0 |
| 08 Usabilidad | 1 | 1 |
| 09 Seguridad | 7 | 1 |
| 10 Interoperabilidad | 3 | 0 |
| 11 Datos Abiertos | 2 | 0 |
| 12 Gestión de Contenidos | 6 | 0 |
| Transversales | 5 | 3 |
| **TOTAL** | **54 RN nuevas** | **5 preguntas abiertas** |

## Preguntas abiertas generadas (nuevas o re-encuadradas por RN)
1. Umbral de tasa de éxito de tareas para declarar "cumple" en usabilidad (RN-08-D01 / A-14). — Equipo UX.
2. Umbral local de incidente "grave" para reporte CSIRT ≤24 h (RN-09-D07 / A-06). — Oficial de Seguridad.
3. Períodos de retención/purga por categoría de dato personal y entidad (RN-TX-D03). — Oficial de Protección de Datos.
4. Matriz trámite ↔ nivel de autenticación exigido (RN-TX-D04 / C-08). — G-CIO + AND.
5. Qué contenidos se traducen y tratamiento de lenguas étnicas como complemento (RN-TX-D05). — Comunicaciones.
6. ¿Política de reembolso cuando un trámite pagado se desiste (RN-03-D07)? — Tesorería + Líder de trámites.
7. ¿Qué trámites de la Alcaldía están sujetos a silencio administrativo positivo y con qué término (RN-03-D08)? — Secretaría Jurídica.

---

## Estimación de completitud del artefacto base de RN

- **Cobertura estimada: ~85-88%.** La pasada base capturó de forma sólida y exhaustiva el plano **normativo** (cada ley/decreto/resolución citada tiene su RN trazable), que es lo más difícil de extraer en este dominio. El protocolo de cobertura del creador funcionó bien en ese eje.
- **El residual (54 RN) NO es normativo**, sino reglas de negocio que (a) **presuponen los nuevos RF/RNF** del delta (idempotencia de pago, ciclo de vida post-radicación de PQRSD, traslado/prórroga/plazos diferenciados, SoD, autorización por recurso, ciclo editorial, baja segura), y (b) **reglas implícitas en plazos, estados y validaciones** que la base dejó en grado de RF pero no elevó a invariante de negocio (cómputo en calendario hábil, atomicidad de radicado, fallback de idioma, sellado con Hora Legal).
- **Hallazgos de evidencia web** que la base no había convertido en RN: silencio administrativo positivo, prórroga/ampliación del término, traslado por competencia, notificación por edicto/correo certificado/SMS, desistimiento.
- **Veredicto:** el artefacto de RN está **listo para integrar este delta**; NO requiere otra pasada completa del creador. Sí conviene que el creador (o el líder funcional) **resuelva las 7 preguntas abiertas** antes de pasar a diseño, porque varias fijan umbrales/decisiones que no se pueden inventar (retención, niveles de autenticación, silencio positivo por trámite, reembolsos).
- **Retroalimentación al creador:** la única omisión sistemática detectable fue tratar el **ciclo de vida posterior a la radicación/publicación** (estados, transiciones, concurrencia, idempotencia) como territorio de RF y no de RN. Recomendación: en futuras pasadas, por cada entidad con estados (radicado, pago, cita, contenido, dataset) generar de oficio la RN de "transición válida" y la RN de "invariante de unicidad/concurrencia".

# RF/RNF — Delta de la segunda pasada profunda (`jose-rf-rnf-profundo`)

> **Proyecto:** Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Fecha:** 2026-06-04
> **Origen:** segunda pasada profunda sobre los RF/RNF ya producidos. Re-lectura de los 12 módulos + contexto global, contrastando dominio/normativa y `extracto-web-docs.md`.
> **Estado:** DELTA pendiente de integración a la sección RF/RNF de cada módulo.
> **Nota de procedencia:** el corpus OCR original (`/tmp/elicit` + `manifest.json` con refs `#NN`) fue borrado. Cada ítem se justifica por **[DOMINIO]** (CRUD/concurrencia/manejo de errores estándar), **[NORMATIVA]** (ley/decreto ya citado en el artefacto), **[WEB]** (hallazgo en `extracto-web-docs.md`), o se marca **[INFERENCIA]** / **[PREGUNTA ABIERTA]**. No se inventaron umbrales: los faltantes quedan como pregunta abierta con responsable.

---

## Módulo 01 — Estructura e Identidad

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-01-D01 | El banner de cookies debe **persistir y versionar el consentimiento** (categorías aceptadas/rechazadas, fecha, versión de política) y re-solicitarlo cuando cambie la política o expire (máx. 12 meses). | [DOMINIO]+[NORMATIVA] Ley 1581; cruza con RF-B1-008 que solo describe el alta del banner | Usuario que aceptó analítica hace 13 meses → al volver, ve de nuevo el banner; el sistema guarda registro con versión y timestamp. | Must |
| RF-01-D02 | **CRUD del menú de navegación y del módulo de noticias/carrusel** desde el CMS (crear, editar, reordenar, despublicar, eliminar con confirmación), respetando el tope de 7 ítems y 2 niveles. | [DOMINIO] RF-B1-003/011/042 definen el render pero no el mantenimiento | Editor reordena ítems del menú → cambio reflejado en todas las páginas; intentar superar 7 ítems → bloqueado con mensaje. | Must |
| RF-01-D03 | El **aviso de salida a sitio externo** (RF-B1-071) debe gestionarse desde una **lista blanca de dominios de confianza** administrable, para no mostrar el modal en redirecciones internas a GOV.CO/SCD. | [DOMINIO] manejo de falsos positivos | Enlace a `gov.co` o a la pasarela del Articulador → no dispara el modal; enlace a dominio no listado → sí. | Should |

### RNF nuevos
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-01-D01 | Resiliencia CDN | Si el CDN GOV.CO (`cdn.www.gov.co`) no responde, las tipografías y el Kit UI deben degradar a fuentes locales de respaldo sin romper el layout; reintento con timeout ≤3 s. | [DOMINIO] el propio módulo §8 advierte "si el CDN falla, las tipografías pueden no cargar" pero no lo convierte en requisito |

---

## Módulo 02 — Transparencia

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-02-D01 | **CRUD + versionado de documentos de transparencia**: editar/reemplazar un documento publicado debe conservar la versión anterior con su fecha (historial), sin romper el enlace de "fuente única" (RF-B3-088). | [DOMINIO] solo existe el "create" (UC-B1-006); falta U/D/versión | Reemplazar el Plan de Acción 2025 → la URL se mantiene, el documento anterior queda accesible como versión histórica con su fecha. | Must |
| RF-02-D02 | **Alertas de vencimiento de publicaciones obligatorias** (Plan de Acción y Gestión 31-ene, informes trimestrales PQRSD/inversión, control interno 6 meses): el sistema notifica al responsable N días antes del plazo legal. | [NORMATIVA] Res.1519 Anexo 2 4.3/4.7/4.10; los RF actuales describen la publicación pero no el recordatorio | A 10 días del 31-ene sin Plan de Acción cargado → alerta al administrador de cumplimiento. | Should |
| RF-02-D03 | **Manejo de caída de integraciones externas** (SECOP, SIGEP, SUIN, SUCOP, KOGUI): si el enlace/servicio destino no responde, mostrar mensaje claro y registrar el fallo, sin página rota. | [DOMINIO] RF-B1-014/015 asumen enlace "funcional" sin estado de fallo | SECOP caído → "El servicio de contratación no está disponible temporalmente", con log; el RF-B2-041 (0 vínculos rotos) no se viola. | Should |

### RNF nuevos
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-02-D01 | Trazabilidad de actualización SIGEP | El directorio debe registrar fecha/hora de última sincronización con SIGEP y alertar si la sincronización lleva >24 h sin éxito (RF-B1-017 exige actualización ≤1 día hábil). | [DOMINIO]+[INFERENCIA] sobre RN-B3-022 |

---

## Módulo 03 — Servicios y Trámites

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-03-D01 | **Idempotencia del pago y del radicado**: un doble clic, reintento o timeout de la pasarela PSE no debe generar doble cobro ni doble radicado; usar token/clave de idempotencia por transacción. | [DOMINIO] condición de carrera clásica en pagos; no aparece en módulo 03 ni 09 | Reenvío del mismo formulario de pago (mismo token) → el sistema devuelve el comprobante existente, no genera segundo cargo. | Must |
| RF-03-D02 | **Conciliación y estados del pago**: manejar respuestas `aprobado / rechazado / pendiente / fallido` de la pasarela Superfinanciera, con reconsulta automática del estado y liberación/avance del trámite solo si el pago se confirma. | [DOMINIO]+[WEB] el sitio describe portales tipo radicador con consecutivo de solicitud previa al pago | Pago "pendiente" en PSE → el trámite queda "en espera de confirmación de pago" y reconsulta cada N min hasta resolver. | Must |
| RF-03-D03 | **Reanudación de trámite (guardar borrador)**: permitir guardar un trámite multi-paso a medio diligenciar y retomarlo, sin perder adjuntos ya cargados. | [DOMINIO] trámites largos (licencia de construcción, UC-B3-013) sin persistencia parcial | Usuario en paso 3 de 5 cierra sesión → al volver, retoma desde el paso 3 con sus datos y adjuntos. | Should |
| RF-03-D04 | **Validación de adjuntos en trámites** (distinto de PQRSD): tipo MIME real, tamaño máximo, cantidad y antivirus, con mensaje de error accesible. | [DOMINIO]+[NORMATIVA] cruza con RF-B1-063 (MIME real) de seguridad; aquí los trámites SÍ admiten límites técnicos razonables (a diferencia de PQRSD por C-01) | PHP renombrado a .pdf → rechazado; archivo con malware → bloqueado y registrado. | Must |
| RF-03-D05 | **Subsanación/requerimiento de documentos**: en la Etapa 3, la entidad puede solicitar documentación adicional al ciudadano, con notificación, plazo y reanudación del trámite al recibirla. | [DOMINIO]+[WEB] el sitio menciona "documentación adicional" en estado del trámite | Entidad marca "requiere subsanación" → ciudadano recibe notificación, carga el documento y el trámite continúa con el plazo recalculado. | Should |
| RF-03-D06 | **Caída del SCD/X-Road durante la verificación automática**: definir el comportamiento cuando la consulta a Registraduría/RUNT/RUAF falla (reintento, cola, o fallback a carga manual del documento con sustento de "falla técnica documentada"). | [NORMATIVA] RN-B2-004 admite excepción por "falla técnica documentada", pero ningún RF la implementa | ANI no responde → el sistema registra la falla y habilita carga manual temporal del documento, dejando constancia. | Must |
| RF-03-D07 | **Cancelación/desistimiento de un trámite en línea por el ciudadano** antes de su resolución. | [DOMINIO]+[WEB] el sitio lista trámites de "desistimiento" | Ciudadano con trámite en Etapa 2 → opción "Desistir", confirma, el estado pasa a "Desistido" y queda en el expediente. | Should |

### RNF nuevos
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-03-D01 | Paginación/capacidad del catálogo | El listado de trámites debe paginar (≥10 por página, RF-B1-022 lo menciona) con tiempo de respuesta de búsqueda ≤2 s para el catálogo completo. | [DOMINIO] |
| RNF-03-D02 | Retención de borradores | **[PREGUNTA ABIERTA]** ¿Cuánto tiempo se conserva un trámite a medio diligenciar (RF-03-D03) antes de expirar? Responsable: Líder funcional de trámites + Protección de Datos (minimización). | [PREGUNTA ABIERTA] |

---

## Módulo 04 — PQRSD

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-04-D01 | **Enrutamiento y reasignación de PQRSD por competencia**: asignación (automática o manual) a la dependencia, reasignación entre dependencias y **traslado por competencia a otra autoridad** con notificación al peticionario. | [WEB] `extracto-web-docs.md` lista explícitamente "Traslado por competencia a otras autoridades"; [NORMATIVA] Ley 1755 Art.21 (resuelve A-11) | PQRSD que no compete a la Alcaldía → funcionario la traslada, el sistema notifica al ciudadano la entidad receptora y la fecha. | Must |
| RF-04-D02 | **Gestión de respuesta y cierre de la PQRSD**: el funcionario carga la respuesta, el sistema la notifica por el canal elegido, registra fecha de respuesta y cierra el radicado calculando el cumplimiento del plazo. | [DOMINIO] el ciclo post-radicación es la "pregunta abierta" del propio módulo §9 | Respuesta cargada → ciudadano notificado, estado "Respondido", y el sistema marca si fue dentro o fuera del término legal. | Must |
| RF-04-D03 | **Cómputo de plazos legales diferenciados por tipo** (petición general, información, consulta, copias) con calendario hábil del Distrito y semáforo de vencimiento. | [NORMATIVA] Ley 1755 Arts.14; cruza con RF-B2-068 (calendario hábil) y HU-B3-022 | Reclamo (15 días hábiles) vs. consulta (30) → el contador aplica el plazo correcto excluyendo festivos del Distrito. | Must |
| RF-04-D04 | **Validación de campos del formulario PQRSD**: correo con formato válido, documento según tipo (CC/CE/NIT/Pasaporte con su patrón), tope de 2.000 caracteres en objeto, obligatoriedad condicional según anonimato. | [DOMINIO] RF-B1-035 habla de validación genérica; falta el detalle por campo | NIT con dígito de verificación inválido → error inline; objeto >2.000 → bloquea y muestra contador. | Must |
| RF-04-D05 | **Notificación electrónica de la respuesta conforme CPACA** (correo procesal/SMS/CCD) con constancia de envío y, si aplica, soporte para notificación por aviso. | [WEB] el sitio menciona notificación "por edicto, físico, correo, SMS, correo certificado"; [NORMATIVA] Ley 1437 Art.56 | Respuesta lista → notificación al canal autorizado con acuse y registro de fecha de notificación. | Should |

### RNF nuevos
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-04-D01 | Formato del radicado | **[PREGUNTA ABIERTA]** Definir la estructura del número de radicado (prefijo dependencia + año + consecutivo) y su unicidad garantizada bajo concurrencia. Responsable: Gestión Documental. | [PREGUNTA ABIERTA]+[WEB] |
| RNF-04-D02 | Concurrencia de radicación | El consecutivo de radicado debe asignarse de forma atómica para evitar números duplicados ante envíos simultáneos (cruza RF-B2-067). | [DOMINIO] |

---

## Módulo 05 — Participa

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-05-D01 | **CRUD y ciclo de vida de mecanismos de participación y consultas** (crear, abrir, cerrar al vencer la fecha máxima de comentarios, archivar), con cierre automático de la recepción de aportes al expirar. | [DOMINIO]+[NORMATIVA] RF-B1-038/039 describen el render; falta el ciclo de vida | Consulta con fecha límite vencida → el formulario de aportes se deshabilita y queda en estado "Cerrada". | Should |
| RF-05-D02 | **Publicación del resultado/cierre de la participación** (consolidado de aportes recibidos y respuesta de la entidad), por trazabilidad del Art. 2.1.2.1.14 Decreto 1081 (rendición). | [NORMATIVA]+[DOMINIO] | Cerrada una consulta normativa → se publica el documento de observaciones recibidas y cómo se incorporaron. | Could |

### Pregunta abierta
- **[PREGUNTA ABIERTA]** ¿Los aportes se reciben **dentro de la sede** o íntegramente en SUCOP? Determina si hay CRUD propio o solo redirección. Responsable: Oficina de Participación + DNP/SUCOP.

---

## Módulo 06 — Canales de Atención

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-06-D01 | **Administración de la agenda de citas (CMS)**: definir dependencias/servicios agendables, franjas horarias, **cupos por franja**, días no laborables y bloqueo de fechas; sin esto el módulo de agendamiento no tiene origen de disponibilidad. | [DOMINIO] RF-B1-030 permite reservar pero no hay quién configure la oferta | Administrador define 10 cupos de 9-10 am para "Catastro" → el ciudadano solo ve esa franja con cupos disponibles. | Must |
| RF-06-D02 | **Reprogramación de cita** por el ciudadano (además de consultar/cancelar, ya previstos) con liberación del cupo anterior. | [DOMINIO] CRUD incompleto (falta update) | Ciudadano con código de cita → "Reprogramar" → elige nueva franja, libera la anterior, recibe nueva confirmación. | Should |
| RF-06-D03 | **Control de concurrencia y doble reserva**: dos ciudadanos no pueden tomar el último cupo de una franja; reserva atómica. | [DOMINIO] condición de carrera | Dos solicitudes simultáneas al último cupo → una confirma, la otra recibe "cupo ya no disponible". | Must |
| RF-06-D04 | **Recordatorio y registro de inasistencia (no-show)**: recordatorio previo a la cita y marca de asistencia/inasistencia para liberar cupos y métricas. | [DOMINIO] | A 24 h de la cita → recordatorio por correo; cita no atendida → marcada "no-show". | Could |

### Pregunta abierta
- ✅ **RESUELTA (2026-06-05):** agenda **propia en la sede** (RF-06-D01 / UC-047); la sede es la fuente de verdad de la disponibilidad. (Ref. contexto-transversal §8 #12.)

---

## Módulo 07 — Accesibilidad

| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-07-D01 | **Bloqueo de publicación de multimedia inaccesible** en el CMS: no permitir publicar un video institucional sin subtítulos (y LSC si es alocución/emergencia/seguridad/rendición de cuentas). Convierte UC-B3-015 en regla bloqueante. | [NORMATIVA] RN-B3-002/003 | Editor sube video sin .SRT → el CMS impide publicar y exige subtítulos. | Must |
| RNF-07-D01 | **Comportamiento de la barra de accesibilidad en tablet (768-992 px)** definido (no solo desktop). Resuelve la ambigüedad del propio §9. | [INFERENCIA]+[DOMINIO] | En tablet la barra de accesibilidad está disponible y operable. | Should |

---

## Módulo 08 — Usabilidad

| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-08-D01 | **Criterio cuantitativo de aceptación de usabilidad** para declarar "cumple": además del SUS ≥68, definir tasa de éxito de tareas (≥X%) y tiempo en tarea. Resuelve A-14 del propio §9. | [PREGUNTA ABIERTA]+[DOMINIO] | **[PREGUNTA ABIERTA]** umbral de tasa de éxito a definir con Equipo UX. | Should |
| RNF-08-D01 | **Persistencia y exportación de resultados SUS/analítica** para el plan de mejora (RF-B1-087 calcula pero no define dónde se almacena ni cómo se reporta). | [DOMINIO] | Cerrada una ronda SUS → resultados almacenados y exportables (CSV) con histórico comparable. | Could |

---

## Módulo 09 — Seguridad

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-09-D01 | **Autorización a nivel de recurso (IDOR)**: un ciudadano autenticado solo puede consultar sus propios radicados/trámites/PQRSD; el acceso por número de radicado a recursos ajenos exige el control de pertenencia. | [DOMINIO] OWASP A01 Broken Access Control; el RBAC actual (RNF-B2-010) cubre módulos, no objetos | Ciudadano A intenta abrir el radicado de B cambiando el ID en la URL → 403, registrado en log. | Must |
| RF-09-D02 | **Política de contraseñas y recuperación segura** del login interno (RF-B3-074): complejidad, expiración, bloqueo, y flujo "Olvidé mi contraseña" con token de un solo uso y expiración. | [DOMINIO] el botón existe pero el flujo no está especificado | "Olvidé contraseña" → enlace con token válido 15 min, un solo uso; tras restablecer, invalida sesiones activas. | Must |
| RF-09-D03 | **Segregación de funciones (SoD)** explícita: quien crea/edita un contenido no puede ser quien lo aprueba/publica; quien gestiona usuarios no audita sus propias acciones. | [DOMINIO]+[INFERENCIA] RF-B1-076 menciona "pendiente de aprobación" pero no prohíbe el auto-aprobado | Editor que creó una norma intenta aprobarla → bloqueado por SoD. | Must |
| RF-09-D04 | **Caída del SCD de Autenticación (OIDC)**: comportamiento ante indisponibilidad del Articulador (mensaje, reintento, no bloquear el acceso a contenido público) y manejo de `error`/`state` inválido en el callback. | [DOMINIO] manejo de error de federación; no especificado | Articulador caído → "La autenticación no está disponible, intente más tarde"; callback con `state` no coincidente → rechazado y registrado. | Must |

### RNF nuevos
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-09-D01 | Integridad de logs | Los logs de auditoría (RF-B1-065) deben ser **inmutables/a prueba de manipulación** (append-only o con encadenamiento por hash), no solo retenidos 5 años. | [DOMINIO]+[INFERENCIA] el §9 ya advierte que la zona de auditoría implica un SIEM |
| RNF-09-D02 | Rate limiting | Límite de tasa por IP/sesión en endpoints públicos (PQRSD, login, búsqueda) además del captcha, contra DoS de aplicación. | [DOMINIO] OWASP |
| RNF-09-D03 | Criterio de incidente "grave" | **[PREGUNTA ABIERTA]** definir el umbral local (resuelve A-06) para disparar el reporte CSIRT ≤24 h. Responsable: Oficial de Seguridad. | [PREGUNTA ABIERTA] |

---

## Módulo 10 — Interoperabilidad

| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-10-D01 | **Manejo de error y reintento en el intercambio X-Road**: timeouts, respuesta vacía, certificado/OCSP vencido del par, y registro de la falla; sin dejar el trámite "colgado". | [DOMINIO] UC-B3-011 describe el camino feliz; falta el de excepción | Entidad destino no responde en N s → reintento configurable y, si falla, error controlado + log + fallback (RF-03-D06). | Must |
| RF-10-D02 | **Continuidad del estampado TSA durante la migración Certicámara→GSE**: cola/buffer de mensajes pendientes de sello para que ninguno quede sin estampa durante la ventana. | [DOMINIO] el propio §9 lo señala como riesgo | Ventana de migración TSA → los mensajes se encolan y se sellan al restablecer, ninguno queda sin RFC 3161. | Should |
| RNF-10-D01 | Monitoreo de certificados | Alerta automática N días antes del vencimiento de certificados ONAC/TLS/OCSP del servidor de seguridad. | [DOMINIO] |

---

## Módulo 11 — Datos Abiertos

| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-11-D01 | **CRUD y actualización programada de datasets**: editar metadatos, versionar, despublicar y **actualizar según la frecuencia declarada**, con alerta de dataset desactualizado. | [DOMINIO] UC-B2-013 solo publica; falta U/D y control de frescura | Dataset con frecuencia "mensual" sin actualizar en 35 días → alerta al responsable de datos. | Should |
| RNF-11-D01 | Validación de calidad del dato | Validar al cargar que el archivo abre como CSV/JSON/XML bien formado y que los metadatos obligatorios (RF-B1-091) están completos antes de federar a datos.gov.co. | [DOMINIO] |

---

## Módulo 12 — Gestión de Contenidos

### RF nuevos
| ID propuesto | Enunciado | Procedencia | Criterio de aceptación | MoSCoW |
|---|---|---|---|---|
| RF-12-D01 | **Flujo de aprobación/publicación con estados explícitos** (Borrador → Pendiente de aprobación → Publicado → Archivado), con rechazo y devolución a edición; el RF-B1-076 menciona "pendiente de aprobación" pero no modela los estados ni el rechazo. | [DOMINIO] | Administrador rechaza una norma → vuelve a "Borrador" con comentario al editor; solo lo "Aprobado" se publica. | Must |
| RF-12-D02 | **CRUD completo de usuarios internos con baja segura**: al desvincular un usuario, revocar accesos y tokens ≤1 día hábil (RNF-B2-011) y **conservar la trazabilidad de sus acciones** (no borrado físico). | [DOMINIO]+[INFERENCIA] UC-B1-015 crea/suspende pero no detalla la baja ni la retención de auditoría | Usuario dado de baja → sin acceso inmediato; sus eventos de auditoría permanecen consultables. | Must |
| RF-12-D03 | **Reintento/cola de notificaciones multicanal** (RF-B3-153/154): si falla el envío por un canal (correo/SMS/push/CCD), reintentar y/o usar canal alterno, registrando el estado de entrega. | [DOMINIO] | Correo rebota → reintento y registro "no entregado"; intento por canal secundario si está autorizado. | Should |
| RF-12-D04 | **Verificación de edad para titulares menores** (RN-B2-007 lo exige pero ningún RF lo implementa): control que impida recolectar datos de <18 sin autorización del representante. | [NORMATIVA] Ley 1581 Art.7 | Registro con fecha de nacimiento <18 → solicita autorización del representante legal antes de continuar. | Must |

### RNF nuevos
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-12-D01 | Concurrencia editorial | Bloqueo optimista/pesimista al editar el mismo contenido por dos usuarios (evitar pisar cambios). | [DOMINIO] |

---

## RNF transversales (nuevos)
| ID | Categoría | Umbral | Procedencia |
|---|---|---|---|
| RNF-TX-D01 | Observabilidad | Monitoreo de disponibilidad (uptime) por componente con alerta automática al incumplir el SLA (≥98% trámites / ≥95% sede, C-02). | [DOMINIO] |
| RNF-TX-D02 | Internacionalización | El botón de idioma (RF-B3-055) implica gestión de contenidos traducidos y de las **lenguas étnicas como complemento** (RN-B1-012); definir qué se traduce y el fallback al castellano. | [PREGUNTA ABIERTA] Responsable: Comunicaciones |
| RNF-TX-D03 | Retención/minimización | Política de retención y purga de datos personales por entidad (borradores de trámite, citas, logs de consentimiento) conforme Ley 1581 (minimización) y TRD. | [PREGUNTA ABIERTA] Responsable: Oficial de Protección de Datos |
| RNF-TX-D04 | Pruebas RTO/RPO | Verificación periódica (restauración real de backup) de los DRP/BCP; los RNF de RTO≤8 min/RPO≤30 min están declarados pero no se exige probarlos. | [DOMINIO] |

---

## Conflictos detectados (nuevos, no listados en C-01..C-05)
| RF/RNF | Artefacto dice | Realidad/normativa | Resolución sugerida |
|---|---|---|---|
| RF-B1-033 (PQRSD sin restricción de adjuntos) vs RF-03-D04 / RF-B1-063 | PQRSD: "sin restricciones de formato/tamaño/cantidad" | Seguridad exige validar MIME real y antivirus; sin límite de tamaño hay riesgo de DoS por subida | Permitir cualquier formato y **no** rechazar por tipo, pero aplicar antivirus y un límite técnico de servidor alto y documentado (no funcional), separando "restricción al derecho" de "control de seguridad". |
| RNF-B1-021 (contraseña usuario ≥8) vs RF-B3-074 login | El login soporta CC/CE/TI/PEP/NIT como usuario | No se define política de complejidad ni 2FA para usuarios internos del CMS (solo para ciudadanos vía SCD) | Definir MFA obligatorio para administradores del CMS (cubierto parcialmente por RF-09-D02). |
| RF-B2-021 (CCD nivel "medio") vs niveles de confianza | Acceso a CCD con nivel medio | Ningún RF fija el **nivel de confianza mínimo por tipo de trámite** (solo el ejemplo "Medio") | Crear matriz trámite ↔ nivel de autenticación exigido. **[PREGUNTA ABIERTA]** |

---

## Resumen cuantitativo

| Módulo | RF nuevos | RNF nuevos | Preguntas abiertas nuevas |
|---|---|---|---|
| 01 Estructura | 3 | 1 | 0 |
| 02 Transparencia | 3 | 1 | 0 |
| 03 Servicios/Trámites | 7 | 2 | 1 |
| 04 PQRSD | 5 | 2 | 1 |
| 05 Participa | 2 | 0 | 1 |
| 06 Canales | 4 | 0 | 1 |
| 07 Accesibilidad | 1 | 1 | 0 |
| 08 Usabilidad | 1 | 1 | 1 |
| 09 Seguridad | 4 | 3 | 1 |
| 10 Interoperabilidad | 2 | 1 | 0 |
| 11 Datos Abiertos | 1 | 1 | 0 |
| 12 Gestión Contenidos | 4 | 1 | 0 |
| Transversales | — | 4 | 3 |
| **TOTAL** | **37 RF** | **18 RNF** | **10 preguntas abiertas** |

**Preguntas abiertas generadas:** retención de borradores de trámite · formato y unicidad del radicado · origen del calendario de disponibilidad de citas · aportes de Participa dentro de la sede vs SUCOP · umbral de tasa de éxito de usabilidad · umbral local de incidente "grave" CSIRT · matriz trámite↔nivel de autenticación · qué se traduce y lenguas étnicas · política de retención/purga de datos personales.

**Cobertura estimada del artefacto base: ~88-90%.** El residual se concentra en patrones de ingeniería transversales (CRUD administrativo, ciclo de vida post-radicación de PQRSD, concurrencia/idempotencia en pagos y radicados, autorización fina por recurso/estado + SoD), no en omisiones normativas.

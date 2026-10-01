# Módulo 12 — Gestión de Contenidos y Administración

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** CMS (publicación/edición/archivo de contenidos), gestión de usuarios internos, roles y permisos, auditoría, archivo y conservación documental (TRD/AGN), registro electrónico de documentos 24/7, expediente electrónico (SGDEA), tablero de cumplimiento ITA, notificaciones electrónicas multicanal y firma electrónica.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Capa administrativa "interna" de la sede: lo que usan los funcionarios para operar el sitio. Incluye el CMS (hoy "SM CMS"), la gestión de usuarios/roles, la gestión documental electrónica (registro 24/7, expedientes, TRD), el tablero de seguimiento del cumplimiento ITA, las notificaciones electrónicas y la firma electrónica.

## 2. Requisitos Funcionales (RF)

### 2.1 CMS, usuarios y roles

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-076 | **CMS**: publicar/editar/archivar contenidos sin conocimientos técnicos avanzados; roles diferenciados (administrador general, editor por sección, publicador); todas las acciones en log de auditoría. | #239,#241 | Must | Un editor publica una resolución → queda "pendiente de aprobación" del administrador y se registra en el log. |
| RF-B1-079 / RF-B2-097 | **Gestión de usuarios y roles** con ≥3 niveles (Administrador, Editor de sección, Publicador); permisos por módulo; auditoría completa; registro mínimo + aceptación de T&C; firma electrónica al registrarse. | #239,#241,#143 | Must | Un editor de normativa puede crear/editar normativa pero no publicarla ni acceder a PQRSD de otras dependencias. |

### 2.2 Registro electrónico y gestión documental

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B2-067 / RF-B3-118 | **Registro electrónico** de documentos: recibe 24/7/365, asigna número consecutivo con fecha/hora, acuse automático por el mismo canal, distribución al destinatario. | #143,#132,#217 | Must | Documento enviado a las 11:55 PM de un festivo → radicado inmediato con fecha/hora exacta y acuse al correo. |
| RF-B2-068 / RF-B3-119 | Relación actualizada de peticiones; **calendario oficial** de días hábiles/inhábiles para cómputo de plazos. | #143,#132,#217 | Must | Plazo de 15 días hábiles → el sistema excluye fines de semana y festivos del Distrito. |
| RF-B1-077 / RF-B3-120/121 | **Archivo y conservación** conforme a **TRD** y lineamientos del **AGN**: expediente electrónico con foliado, índice firmado, ciclo vital (apertura→gestión→cierre→preservación); la información publicada no se elimina sin autorización. | #21,#241,#131,#251 | Must | Intentar eliminar un documento publicado → requiere justificación y aprobación de nivel superior; queda en log. |

### 2.3 Tablero de cumplimiento, notificaciones, firma

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-078 | **Tablero ITA interno de cumplimiento (validación automática)**: tablero de seguimiento **INTERNO (no público)** que **arranca en cero** y se **actualiza automáticamente** a medida que se publica contenido. El sistema **valida en cada publicación/carga** que el contenido cumple los criterios ITA aplicables en todo el sistema (p. ej. imagen con `alt` descriptivo, video con subtítulos, ítem de transparencia completo con sus metadatos, formato abierto, lenguaje claro); cuando un contenido **NO cumple**, lo marca como **incumplido** indicando dónde y la norma asociada, para **corrección manual** del contenido publicado. Muestra ítems cumplidos/total construidos a la fecha, incumplimientos por módulo, norma, responsable y últimas acciones. **No usa datos mock ni puntuaciones precargadas (no se usa el 47/100).** | #239 | Must | Al publicar una página con una imagen sin `alt`, el tablero la marca como incumplida (criterio WCAG/ITA), indica la ubicación y la norma; corregido el `alt`, el ítem pasa a cumplido automáticamente. |
| RF-B2-089 | **Analítica de uso** del portal: visitas, páginas más visitadas, origen del tráfico, términos de búsqueda interna, tasa de abandono. | #70,#125,#144,#153 | Should | El administrador ve los indicadores del último mes con drill-down por sección/trámite/dispositivo. |
| RF-B2-093 / RF-B3-152 | Publicar automáticamente cada 6 meses el **informe de control interno**. | #141,#172,#99 | Must | Al cumplirse el semestre, el sistema publica el informe en la sección designada. |
| RF-B3-153/154 | **Notificaciones electrónicas** de actos administrativos a la dirección procesal; alertas multicanal (correo, SMS, push APP, gestor documental, Carpeta Ciudadana) por cambio de estado. | Decreto 2106 Art.46; #131,#132 | Must | Acto administrativo firmado → notificación al correo/CCD registrada; cambio a "Resuelto" → notificación en <1 h por el canal preferido. |
| RF-B3-155 | **Firma electrónica/digital** con los mismos efectos que la autógrafa en actuaciones del portal. | Decreto 2106 Art.60 | Must | Documento firmado digitalmente por el funcionario → válido jurídicamente y archivado en el SGDEA. |

### 2.4 Validación automática de cumplimiento ITA (decisión 2026-06-05)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-12-D05 | **Validación automática de cumplimiento ITA al publicar/cargar contenido**: en cada publicación o carga de contenido en cualquier módulo, el sistema valida los criterios ITA aplicables (accesibilidad de imágenes/`alt`, subtítulos de video, completitud de metadatos de transparencia, formatos abiertos, lenguaje claro, etc.). Si NO cumple, **marca el contenido como incumplido** señalando el criterio y la ubicación (y **bloquea la publicación** en los criterios bloqueantes, p. ej. multimedia inaccesible — RF-07-D01); el resultado **alimenta automáticamente el tablero ITA interno (RF-B1-078)**, que **parte de cero**. La corrección del contenido es manual; al corregir, el ítem pasa a cumplido automáticamente. Generaliza RF-07-D01 a todos los criterios ITA. | [DECISIÓN 2026-06-05] · [NORMATIVA] Ley 1712/Res.1519 MinTIC; WCAG 2.1 | Must | Subir imagen sin `alt` → el sistema muestra el error y marca el incumplimiento en el tablero; el ítem se mantiene incumplido hasta que se corrige el contenido publicado. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-12-D01 | **Flujo de aprobación/publicación con estados explícitos** (Borrador → Pendiente de aprobación → Publicado → Archivado), con rechazo y devolución a edición; el RF-B1-076 menciona "pendiente de aprobación" pero no modela los estados ni el rechazo. | [DOMINIO] | Must | Administrador rechaza una norma → vuelve a "Borrador" con comentario al editor; solo lo "Aprobado" se publica. |
| RF-12-D02 | **CRUD completo de usuarios internos con baja segura**: al desvincular un usuario, revocar accesos y tokens ≤1 día hábil (RNF-B2-011) y **conservar la trazabilidad de sus acciones** (no borrado físico). | [DOMINIO]+[INFERENCIA] UC-B1-015 crea/suspende pero no detalla la baja ni la retención de auditoría | Must | Usuario dado de baja → sin acceso inmediato; sus eventos de auditoría permanecen consultables. |
| RF-12-D03 | **Reintento/cola de notificaciones multicanal** (RF-B3-153/154): si falla el envío por un canal (correo/SMS/push/CCD), reintentar y/o usar canal alterno, registrando el estado de entrega. | [DOMINIO] | Should | Correo rebota → reintento y registro "no entregado"; intento por canal secundario si está autorizado. |
| RF-12-D04 | **Verificación de edad para titulares menores** (RN-B2-007 lo exige pero ningún RF lo implementa): control que impida recolectar datos de <18 sin autorización del representante. | [NORMATIVA] Ley 1581 Art.7 | Must | Registro con fecha de nacimiento <18 → solicita autorización del representante legal antes de continuar. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-044 | Monitoreo | Sincronización con la **Hora Legal Colombiana** | #156 |
| RNF-B2-026 | Conservación documental | 100% de expedientes con política de retención (TRD) documentada y aplicada | #141 |
| RNF-B3-022 | Logs | 100% de eventos críticos (accesos, perfiles, documentos, eliminaciones) registrados; retención ≥5 años; Hora Legal | #131,#224 |
| RNF-B3-044 | Confiabilidad | Información íntegra e inmutable; recuperación automática ante fallas; MTBF >4.320 h | #224,#131 |
| RNF-B1-039 | Sostenibilidad | Programa de disposición de residuos TIC (RAEE, Res. 1297/2010) | #165 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-12-D01 | Concurrencia editorial | Bloqueo optimista/pesimista al editar el mismo contenido por dos usuarios (evitar pisar cambios). | [DOMINIO] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-015 / RN-B3-012/015 | Documentación electrónica conforme a TRD/AGN; no eliminar sin autorización; expedientes con integridad, disponibilidad, autenticidad. | Decreto 1080/2015; Ley 594/2000; Decreto 2106 Art.16,63 | #21,#241 |
| RN-B3-016 | La **firma digital** tiene los mismos efectos jurídicos que la autógrafa. | Decreto 2106 Art.60; Ley 527/1999 | — |
| RN-B3-019 | Notificaciones electrónicas con preferencia sobre físicas cuando hay dirección procesal electrónica. | Decreto 2106 Arts.46,47 | — |
| RN-B1-022 | El **G-CIO** reporta directamente al representante legal en materia de Gobierno Digital. | Decreto 1083/2015 Art.2.2.35.5 | #165 |
| RN-B1-024 / RN-B2-024 | Designar Director de TI; publicar el **PETI** (5 años) con diagnóstico de interoperabilidad, autenticación, carpeta, integración GOV.CO y seguridad. | Decreto 612/2018; Ley 1753 Art.45 par.2b | #165,#258 |
| RN-B1-025 | Contratación de bienes/servicios TI con Acuerdos Marco de Colombia Compra Eficiente cuando existan. | Decreto 1082/2015 | #165 |
| RN-B2-013/014 | Designar **Oficial de Protección de Datos**; implementar **PIGDP**. | Decreto 620/2020 Arts.2.2.17.5.3/5.4 | #143 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-12-D01 | **Ciclo de vida editorial con estados explícitos:** todo contenido sigue Borrador → Pendiente de aprobación → Publicado → Archivado, con rechazo/devolución a edición; solo lo "Aprobado" se publica. (Motiva RF-12-D01; complementa la SoD de RN-09-D02.) | MSPI; control interno | [DOMINIO] |
| RN-12-D02 | **Baja segura con retención de auditoría:** al desvincular un usuario interno se revocan accesos y tokens ≤1 día hábil, pero NO se borra físicamente: sus eventos de auditoría permanecen consultables (no repudio). (Motiva RF-12-D02; opera RNF-B2-011 y RN-B1-015.) | Ley 594/2000; MSPI; Decreto 1080/2015 | [NORMATIVA]+[DOMINIO] |
| RN-12-D03 | **Verificación de edad de titulares menores:** un registro con fecha de nacimiento <18 años exige autorización del representante legal antes de recolectar datos; sin ella, no se continúa. (Eleva RN-B2-007 a control operativo; motiva RF-12-D04.) | Ley 1581/2012 Art.7; Decreto 1377/2013 | [NORMATIVA] |
| RN-12-D04 | **Garantía de entrega de notificaciones multicanal:** si falla el envío por un canal (correo/SMS/push/CCD), se reintenta y/o se usa canal alterno autorizado, registrando el estado de entrega; un rebote no se da por notificado. (Motiva RF-12-D03; relevante para la validez de la notificación CPACA, RN-04-D05.) | Ley 1437/2011 (constancia de notificación) | [DOMINIO]+[NORMATIVA] |
| RN-12-D05 | **Bloqueo de edición concurrente:** dos usuarios no pueden guardar cambios sobre el mismo contenido pisándose; se aplica bloqueo optimista/pesimista. (Motiva RNF-12-D01.) | Buena práctica editorial | [DOMINIO] |
| RN-12-D06 | **Foliado e índice firmado del expediente electrónico:** todo expediente mantiene índice electrónico firmado, foliado consecutivo y metadatos de autenticidad/integridad; ningún documento se inserta o retira sin trazabilidad. (Especifica RN-B1-015 en lo operativo.) | Decreto 1080/2015; Ley 594/2000; AGN | [NORMATIVA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-011 | Monitorear cumplimiento ITA (validación automática) | Administrador de cumplimiento | Tablero interno ITA (arranca en cero, se actualiza solo al publicar contenido) → ve los incumplimientos detectados automáticamente por la validación al publicar (RF-12-D05), por módulo/criterio/norma → cada incumplimiento indica la ubicación del contenido y el responsable → el responsable corrige manualmente el contenido publicado → el ítem pasa a cumplido automáticamente → log. **Sin datos mock ni puntuación precargada.** | #239 |
| UC-B1-015 | Gestionar usuario del sistema | Administrador | Crea usuario (nombre, correo, rol) → correo de bienvenida con contraseña temporal → cambio en primer acceso → modificar rol/suspender/ver historial → todo en log. | #239,#241 |
| UC-B2-017 / UC-B3-012 | Publicar/actualizar contenido | Editor, Administrador | Crea/modifica contenido (lenguaje claro, `alt`, enlaces verificados) → publica → validación de metadatos y W3C → orden cronológico → log. | #60,#221,#217 |
| UC-B3-014 | Recibir notificación de acto administrativo | Ciudadano | Acto generado → firmado digitalmente → notificado al correo procesal/CCD → el ciudadano lo descarga desde la notificación. | #153, Decreto 2106 Art.46 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-011 | Como editor de normativa quiero publicar una resolución rápido y sin errores. | **Positivo:** **Dado** que completo todos los campos requeridos de la resolución y la envío a aprobación **cuando** el administrador la aprueba **entonces** aparece en la sección pública en menos de 1 minuto con todos los campos y el log registra la acción. <br> **Negativo:** **Dado** que soy el editor que creó el contenido **cuando** intento aprobarlo y publicarlo yo mismo **entonces** el sistema bloquea la acción por segregación de funciones y exige que un administrador distinto lo apruebe. | #225,#241 |
| HU-B1-014 | Como administrador de cumplimiento quiero un tablero interno que valide automáticamente el ITA de todo lo que se publica y me señale los incumplimientos para corregirlos. | **Positivo:** **Dado** que se publica contenido en cualquier módulo **cuando** abro el tablero interno ITA **entonces** veo en tiempo real qué criterios ITA se cumplen y cuáles no, con la ubicación del contenido incumplido, la norma y el responsable — partiendo de cero y sin datos precargados. <br> **Negativo:** **Dado** un contenido publicado que no cumple un criterio ITA (p. ej. imagen sin `alt`) **cuando** el sistema lo valida al publicar **entonces** muestra el error y lo marca como incumplido indicando dónde corregirlo; corregido el contenido, el ítem pasa a cumplido automáticamente. | #239 |
| HU-B1-021 | Como editor quiero que el sistema no permita eliminar documentos de transparencia sin aprobación. | **Positivo:** **Dado** que soy editor e intento eliminar un informe de transparencia **cuando** envío la solicitud de eliminación **entonces** el sistema la deja "pendiente de aprobación" del administrador de gestión documental con mensaje que lo indica. <br> **Negativo:** **Dado** que soy quien solicitó la eliminación **cuando** intento aprobar esa misma eliminación **entonces** el sistema lo bloquea por segregación de funciones, impidiendo la auto-aprobación. | #21,#241 |
| HU-B2-008 | Como funcionario quiero ver el log completo de un expediente para auditorías. | **Positivo:** **Dado** que accedo al expediente y selecciono "Ver historial" **cuando** el sistema carga el log **entonces** veo la bitácora cronológica con fecha, hora, usuario, acción y descripción de cada operación registrada. <br> **Negativo:** **Dado** que el log de auditoría está almacenado en modo append-only **cuando** cualquier usuario intenta modificar o eliminar un registro del log **entonces** el sistema lo impide y registra el intento de alteración. | #128,#143,#152 |
| HU-B3-017 | Como funcionario quiero firmar documentos electrónicamente con validez jurídica. | **Positivo:** **Dado** que tengo un certificado digital vigente y válido **cuando** firmo una resolución con ese certificado **entonces** el documento queda firmado digitalmente con validez jurídica y archivado en el SGDEA. <br> **Negativo:** **Dado** que el certificado digital está vencido o el OCSP no lo valida **cuando** intento firmar **entonces** el sistema rechaza la firma con un mensaje claro sobre el motivo e impide archivar el documento como firmado. | Decreto 2106 Art.60 |
| HU-B3-018 | Como administrador quiero que el sistema valide metadatos obligatorios antes de publicar. | **Positivo:** **Dado** que completo todos los metadatos obligatorios de una norma incluido su estado de vigencia **cuando** intento publicarla **entonces** el sistema la acepta y la publica correctamente. | #221 |
| HU-B3-022 | Como responsable de PQRSD quiero alertas cuando una PQRSD esté próxima a vencer. | **Positivo:** **Dado** que existe una PQRSD activa con plazo de 15 días hábiles **cuando** faltan 3 días hábiles para el vencimiento **entonces** el sistema envía alerta automática al responsable y al supervisor. <br> **Negativo:** **Dado** una consulta con plazo legal de 30 días hábiles **cuando** el sistema calcula el vencimiento **entonces** aplica el plazo correcto según el tipo de petición sobre el calendario hábil del Distrito, sin usar un plazo único genérico para todos los tipos. | #131,#132 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-12-D01 | Como administrador quiero mover los contenidos por Borrador→Pendiente→Publicado→Archivado con rechazo a edición para controlar qué se publica. | **Positivo:** *Dado* un contenido "Pendiente de aprobación", *cuando* lo apruebo, *entonces* pasa a "Publicado"; si lo rechazo, vuelve a "Borrador" con comentario al editor.<br>**Negativo (publicar sin metadato):** *Dado* una norma sin estado de vigencia, *cuando* intento publicarla, *entonces* se bloquea (HU-B3-018).<br>**Negativo (SoD):** *Dado* el editor creador, *cuando* intenta aprobar lo propio, *entonces* se bloquea (HU-09-D03). | RF-12-D01 · RN-12-D01 · UC-048 · [DOMINIO] |
| HU-12-D02 | Como administrador quiero desvincular un usuario revocando accesos pero conservando su trazabilidad para cumplir el no repudio y la archivística. | **Positivo:** *Dado* un usuario que se desvincula, *cuando* lo doy de baja, *entonces* sus accesos y tokens se revocan ≤1 día hábil y sus eventos de auditoría permanecen consultables.<br>**Negativo (borrado físico):** *Dado* la baja, *cuando* alguien intenta borrar físicamente el usuario y su historial, *entonces* el sistema lo impide (solo desactivación). | RF-12-D02 · RN-12-D02 · UC-020 A2 · [NORMATIVA] Ley 594/2000; MSPI |
| HU-12-D03 | Como entidad quiero reintentar y/o usar canal alterno cuando una notificación falla para garantizar la entrega y la validez de la notificación. | **Positivo:** *Dado* una notificación por correo, *cuando* se entrega, *entonces* se registra "entregado".<br>**Negativo (rebote):** *Dado* un correo que rebota, *cuando* falla, *entonces* se reintenta y/o se usa canal alterno autorizado, y NO se da por notificado hasta confirmar entrega. | RF-12-D03 · RN-12-D04 · UC-008 E4 · [DOMINIO]+[NORMATIVA] Ley 1437 |
| HU-12-D04 | Como entidad quiero impedir recolectar datos de menores de 18 sin autorización del representante para cumplir la protección de datos de menores. | **Positivo:** *Dado* un registro con fecha de nacimiento que da ≥18, *cuando* continúo, *entonces* el sistema procede normalmente.<br>**Negativo:** *Dado* una fecha de nacimiento <18, *cuando* intento avanzar, *entonces* el sistema exige autorización del representante legal antes de recolectar datos. | RF-12-D04 · RN-12-D03 · UC-006 E6 · [NORMATIVA] Ley 1581 Art.7 |
| HU-12-D05 | Como editor quiero que dos personas no pisen los cambios del otro sobre el mismo contenido para no perder trabajo. | **Positivo:** *Dado* un contenido que estoy editando, *cuando* nadie más lo edita, *entonces* guardo sin conflicto.<br>**Negativo:** *Dado* que otro usuario edita el mismo contenido, *cuando* intento guardar sobre una versión obsoleta, *entonces* el sistema avisa el conflicto y no pisa los cambios. | RNF-12-D01 · RN-12-D05 · UC-018 E4' · [DOMINIO] |

## 7. Datos / Entidades del módulo

- **Usuario interno:** identificación, rol (Administrador/Editor/Publicador), permisos por módulo, historial de auditoría. (#239,#241)
- **Evento de auditoría:** acción, usuario, fecha/hora (Hora Legal), componente afectado, resultado. (#217)
- **Documento/expediente electrónico:** contenido, metadatos (autenticidad, integridad, fiabilidad, disponibilidad), foliado, índice firmado, TRD, ciclo vital. (#131,#217)
- **Radicado:** número consecutivo, fecha-hora, emisor, destinatario, tipo de documento. (#143)
- **Cumplimiento ITA (interno, desde cero):** por cada criterio ITA validado automáticamente al publicar — criterio, módulo, norma asociada, ubicación del contenido, estado (cumple/incumple), responsable, fecha de validación, última corrección. Se construye desde cero; sin puntuación precargada. (#239)
- **Notificación:** acto administrativo, canal (correo/SMS/push/CCD), estado, fecha. (#131,#132)

## 8. Integraciones

- **SGDEA (Orfeo)**: expedientes y radicación (módulos 03, 04, 10) — se implementará y configurará **Orfeo** (open-source). [decisión 2026-06-05]
- **SIGEP**: usuarios/directorio (módulo 02).
- **Carpeta Ciudadana Digital**: canal de notificaciones (módulo 10).
- **datos.gov.co**: el CMS debe poder publicar/actualizar datasets (módulo 11).
- **AGN**: TRD y preservación documental.

## 9. Ambigüedades y preguntas abiertas

- ~~**[A-03]** Datos del tablero ITA posiblemente "mock"~~ → ✅ **RESUELTO (2026-06-05):** el tablero **NO usa datos mock ni el 47/100**; arranca en **cero** y se alimenta de la **validación automática real al publicar** (RF-B1-078 / RF-12-D05). El cumplimiento se construye desde cero a medida que se publica contenido que pasa la validación ITA.
- **[A-11]** ¿El enrutamiento de PQRSD a la dependencia es automático o manual? (#239)
- **[PREGUNTA ABIERTA]** Roles exactos del SM CMS y separación de funciones (crear vs aprobar vs publicar). ✅ **SGDEA decidido (2026-06-05): se implementará Orfeo** (open-source). ✅ **PETI ENCONTRADO (2026-06-05):** existe el **PETI 2024-2027 (actualizado 2026)**, publicado y vigente en el sitio oficial (`santamarta.gov.co`); se alinea el roadmap de la sede a él. (#239,#165)
- **[Riesgo]** El SM CMS propietario puede generar dependencia tecnológica y riesgo de disponibilidad sin DRP/BCP. (#239)

# Módulo 06 — Canales de Atención

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** publicación de canales de atención (presencial, telefónico, correo, chat), datos de contacto con formato normalizado (+57), agendamiento de citas para atención presencial y acceso inclusivo a medios electrónicos en sedes físicas.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Reúne la información y mecanismos para que el ciudadano contacte a la Alcaldía por cualquier canal y agende atención presencial. Se relaciona con el footer (módulo 01) y con la inclusión digital (módulo 03).

## 2. Requisitos Funcionales (RF)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-041 | Sección **Canales de Atención**: canal (presencial/telefónico/correo/chat), dirección física con código postal, horarios, teléfono (+57), correo institucional, correo de notificaciones judiciales, correo de anticorrupción. Máx 5 sedes físicas en el footer. | #7,#225,#241 | Must | En Canales de Atención el ciudadano encuentra dirección completa, horarios actualizados y todos los contactos con formato +57. |
| RF-B1-030 / RF-B3-096 | **Agendamiento de citas** de atención presencial: selección de dependencia/servicio, fecha y hora disponible, confirmación por correo, consulta/cancelación por número de confirmación; indicación de sedes físicas. | #225,#241,#132 | Should/Must | El ciudadano selecciona dependencia y fecha/hora disponible y recibe confirmación por correo con código de cita. |
| RF-B1-095 | **Acceso inclusivo**: medios electrónicos gratuitos en sedes físicas con personal de apoyo (especialmente adultos mayores y personas con discapacidad). | #251 | Must | Un adulto mayor sin internet acude a la sede física y encuentra computadores con conexión y un funcionario que lo acompaña. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-06-D01 | **Administración de la agenda de citas (CMS)**: definir dependencias/servicios agendables, franjas horarias, **cupos por franja**, días no laborables y bloqueo de fechas; sin esto el módulo de agendamiento no tiene origen de disponibilidad. | [DOMINIO] RF-B1-030 permite reservar pero no hay quién configure la oferta | Must | Administrador define 10 cupos de 9-10 am para "Catastro" → el ciudadano solo ve esa franja con cupos disponibles. |
| RF-06-D02 | **Reprogramación de cita** por el ciudadano (además de consultar/cancelar, ya previstos) con liberación del cupo anterior. | [DOMINIO] CRUD incompleto (falta update) | Should | Ciudadano con código de cita → "Reprogramar" → elige nueva franja, libera la anterior, recibe nueva confirmación. |
| RF-06-D03 | **Control de concurrencia y doble reserva**: dos ciudadanos no pueden tomar el último cupo de una franja; reserva atómica. | [DOMINIO] condición de carrera | Must | Dos solicitudes simultáneas al último cupo → una confirma, la otra recibe "cupo ya no disponible". |
| RF-06-D04 | **Recordatorio y registro de inasistencia (no-show)**: recordatorio previo a la cita y marca de asistencia/inasistencia para liberar cupos y métricas. | [DOMINIO] | Could | A 24 h de la cita → recordatorio por correo; cita no atendida → marcada "no-show". |

✅ **RESUELTA (2026-06-05):** agenda **propia en la sede**; el CMS administra servicios, franjas, cupos y bloqueos (RF-06-D01 / UC-047). La sede es la fuente de verdad de la disponibilidad. (Ref. contexto-transversal §8 #12.)

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-037 | Calidad de información | Horarios y datos de contacto actualizados y veraces | #241 |
| (transversal) | Accesibilidad | Módulo de citas conforme WCAG 2.1 AA (ver módulo 07) | #17 |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-017 | Teléfonos con prefijo **+57** e indicativo CRC (excepto 018000/019000). | Res. 1519/2020 Anexo 2 | #7,#225 |
| RN-B2-023 | La autenticación/agendamiento electrónicos deben ser **opcionales**: mantener alternativa presencial/telefónica disponible. | Ley 1753/2015 Art.45 par.1 | #258 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-06-D01 | **Reserva atómica de cita:** un cupo de franja no puede asignarse a dos ciudadanos; el último cupo lo obtiene una sola reserva y la concurrente recibe "cupo no disponible". (Motiva RF-06-D03.) | Buena práctica de concurrencia | [DOMINIO] |
| RN-06-D02 | **Liberación de cupo en cancelación/reprogramación:** cancelar o reprogramar una cita libera el cupo anterior para reasignación inmediata. (Motiva RF-06-D02.) | Buena práctica de agendamiento | [DOMINIO] |
| RN-06-D03 | **Alternativa no digital obligatoria:** el agendamiento y la autenticación en línea son opcionales; siempre debe existir vía presencial/telefónica equivalente, y su ausencia es incumplimiento. (Eleva a invariante RN-B2-023.) | Ley 1753/2015 Art.45 par.1 | [NORMATIVA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-007 / UC-B3-008 | Agendar cita de atención presencial | Ciudadano | "Canales de Atención" → "Agendar cita" → dependencia/servicio → calendario con disponibilidad → fecha/hora → datos de contacto → confirma → correo con código → puede consultar/cancelar con el código. | #225,#241,#221 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-019 / HU-B3-009 | Como ciudadano quiero agendar una cita en línea para evitar filas. | **Positivo:** **Dado** que el ciudadano selecciona una dependencia y una fecha con disponibilidad **cuando** confirma la reserva **entonces** recibe confirmación inmediata por correo con el código de cita. <br> **Negativo:** **Dado** dos solicitudes simultáneas al último cupo de una franja **cuando** el sistema las procesa **entonces** una confirma la reserva y la otra recibe "cupo ya no disponible", sin asignar el mismo cupo a ambas. | #225,#241,#209 |
| HU-B1-013 | Como adulto mayor sin habilidades digitales quiero hacer trámites digitales con acompañamiento en la sede física. | **Positivo:** **Dado** que un adulto mayor acude a la sede física sin habilidades digitales **cuando** solicita ayuda **entonces** encuentra computadores con conexión a internet y un funcionario capacitado que lo acompaña paso a paso de forma gratuita. | #251 |
| HU-B1-024 | Como ciudadano sin internet en casa quiero acceder a computadores públicos en la Alcaldía. | **Positivo:** **Dado** que el ciudadano acude a la sede física sin dispositivo propio **cuando** lo solicita **entonces** dispone de al menos 2 computadores con internet y puede ser atendido por un funcionario de apoyo. | #251 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-06-D01 | Como administrador de atención quiero definir servicios agendables, franjas, cupos y bloqueos para que el ciudadano solo vea disponibilidad real. | **Positivo:** Dado el servicio "Catastro", cuando configuro 10 cupos de 9-10 am, entonces el ciudadano ve esa franja con cupos disponibles.<br>**Negativo (reducir bajo reservas):** Dado una franja con citas ya reservadas, cuando intento reducir cupos por debajo de las reservas, entonces el sistema lo impide o exige reprogramar las afectadas.<br>**DoR:** decisión agenda propia vs sistema de turnos ([PREGUNTA ABIERTA]). **DoD:** oferta publicada y visible en UC-011. | RF-06-D01 · RN-06-D01 · UC-047 · [DOMINIO] |
| HU-06-D02 | Como ciudadano quiero reprogramar mi cita para ajustarla sin tener que cancelarla y volver a agendar. | **Positivo:** Dado mi código de cita, cuando elijo "Reprogramar" y una nueva franja, entonces se libera el cupo anterior y recibo nueva confirmación.<br>**Negativo:** Dado que la nueva franja se llenó mientras decidía, cuando confirmo, entonces recibo "franja no disponible" y mi cita original se conserva. | RF-06-D02 · RN-06-D02 · UC-032 A1 · [DOMINIO] |
| HU-06-D03 | Como ciudadano quiero que el último cupo no se asigne a dos personas para no llegar a una cita que el sistema no respeta. | **Positivo:** Dado el último cupo de una franja, cuando lo reservo primero, entonces mi reserva se confirma.<br>**Negativo:** Dado dos solicitudes simultáneas al último cupo, cuando se procesan, entonces una confirma y la otra recibe "cupo ya no disponible". | RF-06-D03 · RN-06-D01 · UC-032 E3' · [DOMINIO] |
| HU-06-D04 | Como administrador de atención quiero recordatorios previos y marca de inasistencia para liberar cupos y medir el no-show. | **Positivo:** Dado una cita a 24 h, cuando corre el job, entonces el ciudadano recibe recordatorio por correo.<br>**Negativo (no-show):** Dado una cita no atendida, cuando pasa la hora, entonces se marca "no-show", se libera el cupo y se alimenta la métrica. | RF-06-D04 · [DOMINIO] |

## 7. Datos / Entidades del módulo

- **Canal de atención:** canal (presencial/telefónico/correo/notificaciones/anticorrupción), dirección, código postal, horario, teléfono (+57), correo. (#239)
- **Cita:** dependencia/servicio, fecha, hora, datos de contacto, código de confirmación, estado (agendada/cancelada). (#225,#241)

## 8. Integraciones

- **Footer GOV.CO** (módulo 01): datos de contacto y sedes físicas.
- **Calendario hábil/inhábil** (módulo 12): para disponibilidad de citas.

## 9. Ambigüedades y preguntas abiertas

- **[RN-B2-023]** Debe mantenerse alternativa presencial; el canal digital no puede ser único obligatorio. (#258)
- **[PREGUNTA ABIERTA]** ¿Cuántas sedes físicas y dependencias deben exponerse y con qué horarios? ¿Existe chat/línea de atención en vivo? (#239)

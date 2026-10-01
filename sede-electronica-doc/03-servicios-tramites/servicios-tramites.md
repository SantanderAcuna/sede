# Módulo 03 — Servicios y Trámites

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** catálogo de trámites/OPA vinculado al SUIT, trámites en línea con el esquema estándar de **4 etapas** GOV.CO, área de servicio, retroalimentación, pagos electrónicos, expediente electrónico, Carpeta Ciudadana Digital (consumo), certificados en línea y digitalización de trámites (Decreto 088/2022).
> **Cruces:** Autenticación digital → módulo **09 Seguridad**; X-Road/SCD/SGDEA → módulo **10 Interoperabilidad**; agendamiento de citas → módulo **06 Canales de Atención**; firma electrónica y notificaciones → módulo **12 Gestión de Contenidos**.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Núcleo transaccional de la sede. Todo trámite integrado a GOV.CO sigue el flujo de **4 etapas**: (1) Inicio (ficha en SUIT), (2) Hago mi solicitud (formulario, adjuntos, radicado, autenticación, pago), (3) Procesan mi solicitud (estado en tiempo real), (4) Respuesta (notificación + Carpeta Ciudadana + encuesta). Los trámites nuevos nacen 100% en línea; los existentes se digitalizan por bloques según el Decreto 088/2022.

## 2. Requisitos Funcionales (RF)

### 2.1 Catálogo y búsqueda de trámites

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-021 / RF-B3-092 | Publicar **catálogo de trámites y OPA** vinculado al **SUIT**, con ficha en GOV.CO (`https://www.gov.co/servicios-y-tramites/T{código}`): nombre, descripción, modalidad (totalmente en línea/parcial/presencial), costo o gratuidad, tiempo de resolución, documentos requeridos. | #7,#21,#36,#39,#241,#132,#209 | Must | Buscar un trámite y seleccionarlo → ficha completa con modalidad, costo, tiempo y documentos, con enlace a GOV.CO. |
| RF-B1-022 / RF-B3-093 | **Búsqueda, filtros** (nombre, modalidad, costo, tipo de solicitante) y **paginación**; consulta de estado por número de radicado con trazabilidad. | #7,#21,#241,#132 | Must | Filtrar por "trámites gratuitos en línea" → solo esos trámites, con paginación si hay >10. |
| RF-B2-091 | Sección **"Servicios a la Ciudadanía"** con catálogo (nombre, procedimiento, plazos, tarifas, resultados), canales de atención y formulario PQRSD único. | #125 | Must | El ciudadano encuentra el catálogo completo con filtros por tipo, nombre y canal. |

### 2.2 Trámite en línea — 4 etapas

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B2-028 / RF-B1-023 / RF-B3-071 | Representar las **4 etapas**: (1) Inicio, (2) Hago mi solicitud, (3) Procesan mi solicitud, (4) Respuesta; con indicador visual (Finalizada/En proceso/Por Realizar) — **stepper** navegable por teclado (≥4 etapas visibles). | #128,#130,#152,#198,#36,#251,#118 | Must | Al iniciar un trámite, se ve la línea de avance de 4 etapas con la etapa 1 "En proceso". |
| RF-B2-029 | **Etapa 1 (Inicio):** ficha del trámite actualizada en SUIT (descripción, requisitos en lenguaje claro, procedimiento, plazos, costos, resultado, grupo objetivo, enlace al trámite en línea, georreferenciación de puntos de atención). | #128,#152 | Must | El ciudadano ve la ficha completa, en lenguaje claro, con enlace funcional al trámite digital. |
| RF-B2-030 | **Etapa 2 (Solicitud):** formulario electrónico, cargue de documentos, generación de número único de radicado, autorización de datos (Ley 1581), aceptación de T&C. | #128,#152 | Must | Al "Radicar", el sistema genera radicado único y muestra confirmación con el número y tiempo estimado. |
| RF-B2-031 / RF-B3-093 | **Etapa 3 (Procesamiento):** consulta de estado en tiempo real (etapa actual, tiempo estimado, documentación adicional). | #128,#152,#198 | Must | Consultar por radicado → etapa actual, tiempo estimado y requisitos pendientes. |
| RF-B2-032 | **Etapa 4 (Respuesta):** notificación digital del resultado; consulta del resultado en la Carpeta Ciudadana; encuesta de calidad. | #128,#130,#152,#198 | Must | Resuelto el trámite, el sistema notifica por correo y el resultado queda en la CCD. |
| RF-B3-065 | **Área de servicio** en trámites: tutoriales, dudas (correo + teléfono) y valoración de experiencia (FÁCIL/DIFÍCIL + comentario). | #118 | Should | Al completar el proceso, el usuario puede calificar FÁCIL/DIFÍCIL y comentar. |
| RF-B2-033/034 | **Retroalimentación obligatoria** "¿Cómo fue tu experiencia?" (FÁCIL/DIFÍCIL + texto) al inicio y al final; **canal de comunicación directa** (correo/teléfono) para dudas. | #130,#198,#251 | Must | El componente es visible y funcional al inicio y al final del flujo. |
| RF-B1-094 | **Encuesta de experiencia** al finalizar cada trámite en línea: **máximo 3 preguntas + calificación de 1 a 5 estrellas**; resultados analizados mensualmente. (Complementa a RF-B2-033/034: la valoración rápida es FÁCIL/DIFÍCIL; esta es la encuesta de 1-5 estrellas — ambas aplican.) | #251 | Should | Trámite completado → encuesta de ≤3 preguntas con calificación 1-5 estrellas en <1 min. |

### 2.3 Digitalización, expediente, certificados

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-024 / RF-B3-148 | Trámites nuevos **100% en línea** desde su creación; trámites existentes digitalizados según plazos del Decreto 088/2022; sin cobro adicional por canal digital; registrar en SUIT con código T[código]. | #22,#251,#183 | Must | Trámite nuevo aprobado ante DAFP → disponible 100% en línea desde el primer día, sin cobro adicional. |
| RF-B3-149 | Proceso de digitalización en **fases**: Documentación → Autodiagnóstico → Diseño → Implementación/Pruebas → Operación. | #79 | Must | El equipo sigue las fases documentadas con entregables por fase. |
| RF-B3-150 | **Priorización** de trámites en 3 bloques: 30% mayor demanda, 30% intermedia, 40% menor demanda (según solicitudes/año en SUIT). | #79 | Must | Los trámites con más solicitudes/año entran al bloque 1. |
| RF-B1-092 / RF-B3-095 | Trámites en línea generan **expediente electrónico** articulado con el **SGDEA**: autenticidad, integridad, fiabilidad, disponibilidad; foliado, índice firmado, TRD. *(ver módulo 10/12)* | #251,#132,#209 | Must | Trámite completado → expediente electrónico en el SGDEA con metadatos e integridad. |
| RF-B3-124 | Consulta gratuita en línea de **registros públicos** (certificados, constancias, paz y salvos) emitidos por la Alcaldía. | Decreto 2106 Art.19 | Must | El ciudadano consulta el estado de deuda de predial sin carné físico. |
| RF-B1-093 | **Tablero BI** de monitoreo de trámites: iniciados/completados por período, tasa de abandono por paso, tiempo promedio, disponibilidad. | #251 | Should | El administrador ve por trámite el nº de solicitudes, tasa de completitud y paso de mayor abandono. |
| RF-B1-095 | **Acceso inclusivo**: medios electrónicos gratuitos en sedes físicas con personal de apoyo (adultos mayores, personas con discapacidad). *(ver módulo 06)* | #251 | Must | Un adulto mayor sin internet acude a la sede física y encuentra computadores y un funcionario que lo acompaña. |

### 2.4 Pagos en línea

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-029 / RF-B2-066 / RF-B3-122 | **Pagos electrónicos** (tarjeta débito/crédito, PSE) para trámites con costo, conforme a la Superintendencia Financiera; funcionan en móvil; sin costo adicional al canal presencial. | #22,#251,#128,#141 | Must | En el paso de pago el ciudadano paga con débito/crédito/PSE sin recargo. |
| RF-B3-123 | **No cobrar** por automatización/estandarización/mejora de procesos. | Decreto 2106 Art.7° | Must | La tarifa del trámite digital no supera la del presencial. |

### 2.5 Carpeta Ciudadana (consumo) y autenticación (referencia)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-027 / RF-B3-116/117 | Integrar la **Carpeta Ciudadana Digital** (obligatorio): resultados de trámites vinculados a la carpeta; el portal redirige a GOV.CO para acceder; alertas/comunicaciones previa autorización. *(servicios REST y X-Road en módulo 10)* | #22,#156,#165,#251,#119,#224 | Must | Trámite completado → documento disponible en la CCD del ciudadano en GOV.CO. |
| RF-B1-025/026 / RF-B2-012/015 / RF-B3-109 | **Autenticación SCD** por nivel de confianza (Bajo/Medio/Alto/Muy Alto) vía OpenID Connect; SSO/SLO; autorización propia (roles) posterior. *(detalle en módulo 09)* | #156,#165,#251,#124,#119 | Must | Trámite nivel Medio → redirección a la pasarela del Articulador con el nivel correcto. |
| RF-B2-098 | Actualizar en **SUIT** los trámites con SCD; cambios de URL reflejados de inmediato en SUIT y GOV.CO. | #143,#152 | Must | Implementado SCD, la ficha refleja los pasos y GOV.CO se actualiza en ≤24 h. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-03-D01 | **Idempotencia del pago y del radicado**: un doble clic, reintento o timeout de la pasarela PSE no debe generar doble cobro ni doble radicado; usar token/clave de idempotencia por transacción. | [DOMINIO] condición de carrera clásica en pagos; no aparece en módulo 03 ni 09 | Must | Reenvío del mismo formulario de pago (mismo token) → el sistema devuelve el comprobante existente, no genera segundo cargo. |
| RF-03-D02 | **Conciliación y estados del pago**: manejar respuestas `aprobado / rechazado / pendiente / fallido` de la pasarela Superfinanciera, con reconsulta automática del estado y liberación/avance del trámite solo si el pago se confirma. | [DOMINIO]+[WEB] el sitio describe portales tipo radicador con consecutivo de solicitud previa al pago | Must | Pago "pendiente" en PSE → el trámite queda "en espera de confirmación de pago" y reconsulta cada N min hasta resolver. |
| RF-03-D03 | **Reanudación de trámite (guardar borrador)**: permitir guardar un trámite multi-paso a medio diligenciar y retomarlo, sin perder adjuntos ya cargados. | [DOMINIO] trámites largos (licencia de construcción, UC-B3-013) sin persistencia parcial | Should | Usuario en paso 3 de 5 cierra sesión → al volver, retoma desde el paso 3 con sus datos y adjuntos. |
| RF-03-D04 | **Validación de adjuntos en trámites** (distinto de PQRSD): tipo MIME real, tamaño máximo, cantidad y antivirus, con mensaje de error accesible. | [DOMINIO]+[NORMATIVA] cruza con RF-B1-063 (MIME real) de seguridad; aquí los trámites SÍ admiten límites técnicos razonables (a diferencia de PQRSD por C-01) | Must | PHP renombrado a .pdf → rechazado; archivo con malware → bloqueado y registrado. |
| RF-03-D05 | **Subsanación/requerimiento de documentos**: en la Etapa 3, la entidad puede solicitar documentación adicional al ciudadano, con notificación, plazo y reanudación del trámite al recibirla. | [DOMINIO]+[WEB] el sitio menciona "documentación adicional" en estado del trámite | Should | Entidad marca "requiere subsanación" → ciudadano recibe notificación, carga el documento y el trámite continúa con el plazo recalculado. |
| RF-03-D06 | **Caída del SCD/X-Road durante la verificación automática**: definir el comportamiento cuando la consulta a Registraduría/RUNT/RUAF falla (reintento, cola, o fallback a carga manual del documento con sustento de "falla técnica documentada"). | [NORMATIVA] RN-B2-004 admite excepción por "falla técnica documentada", pero ningún RF la implementa | Must | ANI no responde → el sistema registra la falla y habilita carga manual temporal del documento, dejando constancia. |
| RF-03-D07 | **Cancelación/desistimiento de un trámite en línea por el ciudadano** antes de su resolución. | [DOMINIO]+[WEB] el sitio lista trámites de "desistimiento" | Should | Ciudadano con trámite en Etapa 2 → opción "Desistir", confirma, el estado pasa a "Desistido" y queda en el expediente. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-001 / RNF-B2-004 / RNF-B3-006 | Disponibilidad de trámites digitalizados | **≥98% mensual** (sede informativa ≥95%) — ver contradicción C-02 | #21,#241,#251,#217 |
| RNF-B3-016/017 | Escalabilidad | ≥5.000 usuarios concurrentes; 100.000 transacciones concurrentes | #131 |
| RNF-B1-008 / RNF-B3-010 | Rendimiento | Carga inicial ≤3 s; páginas internas ≤1.5 s (FCP ≤1 s) | #21,#170,#241,#131 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-03-D01 | Paginación/capacidad del catálogo | El listado de trámites debe paginar (≥10 por página, RF-B1-022 lo menciona) con tiempo de respuesta de búsqueda ≤2 s para el catálogo completo. | [DOMINIO] |
| RNF-03-D02 | Retención de borradores | **[PREGUNTA ABIERTA]** ¿Cuánto tiempo se conserva un trámite a medio diligenciar (RF-03-D03) antes de expirar? Responsable: Líder funcional de trámites + Protección de Datos (minimización). | [PREGUNTA ABIERTA] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-006 | Actualizar **SUIT** en ≤3 días hábiles tras acto administrativo que modifique un trámite. | Ley 2052/2020 Art.19 | #22 |
| RN-B1-007 / RN-B2-020 / RN-B3-038 | Trámites nuevos **100% en línea** desde el primer día; sin cobro adicional por canal digital. | Ley 2052/2020 Art.6; Decreto 088/2022 Art.2.2.20.9; Decreto 2106 Art.1 | #22,#251,#183 |
| RN-B1-008 / RN-B3-033/034 | Trámites existentes se digitalizan por bloques del Decreto 088/2022 (tier Alcaldía-Avanzado: 30% may/2028, 100% mar/2034); plazos desde 01/01/2022. | Decreto 088/2022 Arts.2.2.20.6/7 | #251,#79 |
| RN-B1-023 | Trámites no digitalizables totalmente deben digitalizar todos los pasos susceptibles. | Decreto 088/2022 Art.2.2.20.8 | #251 |
| RN-B2-001 / RN-B3-010 | No incrementar tarifas por digitalización/automatización. | Decreto 2106 Art.7; Ley 2052/2020 | #141,#183 |
| RN-B2-002 / RN-B3-027 | Consultas de acceso a información pública NO son trámites → suprimir del SUIT en 3 meses. | Decreto 2106 Art.6 | #141,#172 |
| RN-B2-003 | Modificaciones estructurales de trámites requieren concepto previo del DAFP (≤30 días). | Decreto 2106 Art.3 | #141,#172 |
| RN-B3-011 / RN-B3-037 | Certificados verificables (afiliación SGSSS, RTM) no pueden exigirse físicos → consultar RUAF/RUNT. | Decreto 2106 Arts.99,111 | — |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

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

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-003 / UC-B2-001 / UC-B3-001 | Buscar y consultar trámite | Ciudadano | Catálogo → buscador/filtros → ficha (descripción, modalidad, costo, tiempo, documentos) → "Iniciar trámite en línea" (redirige a GOV.CO/flujo interno). | #7,#36,#241,#132 |
| UC-B1-004 / UC-B2-002 / UC-B3-013 | Realizar trámite en línea | Ciudadano, SCD Aut., SCD Interop. | Nivel de autenticación → pasarela OIDC del Articulador → sesión y permisos → formulario (4 etapas) → adjuntos → pago si aplica → radicado → resultado vinculado a Carpeta. | #22,#156,#251,#128,#143 |
| UC-B2-004 | Recibir resultado del trámite | Ciudadano, CCD | Notificación por correo → consulta en Carpeta Ciudadana → encuesta FÁCIL/DIFÍCIL. | #128,#130,#143,#152,#198 |
| UC-B3-006 | Pagar un trámite en línea | Ciudadano | Inicia trámite → cálculo de tarifa → selección de medio (PSE/tarjeta) → pago vía pasarela Superfinanciera → comprobante → avanza. | Decreto 2106 Art.17 |
| UC-B1-014 / UC-B3-007 | Consultar estado de trámite | Ciudadano | "Mis trámites" o consulta por radicado → estado en SGDEA (registrado/recibido/en trámite/resuelto) → descarga de documentos disponibles. | #21,#251,#131,#132 |
| UC-B3-013 | Radicar licencia de construcción | Ciudadano/Empresa | Autenticación → formulario multi-paso (planos, predio, propietario) → verificación cédula (ANI) y predio (interoperabilidad) → pago → radicado → seguimiento. | #131,#132 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-005 / HU-B3-010 | Como ciudadano quiero ver toda la info del trámite (costo, documentos, si es en línea) antes de iniciarlo. | **Positivo:** **Dado** que accedo a la ficha de un trámite disponible en línea, **cuando** la consulto, **entonces** encuentro la modalidad "en línea", el costo, el tiempo estimado de resolución, los documentos requeridos y el botón "Iniciar trámite en línea". | #7,#21,#241,#132 |
| HU-B2-001 | Como ciudadano quiero iniciar y seguir el trámite 100% en línea sin ir a la Alcaldía. | **Positivo:** **Dado** que accedo a un trámite desde GOV.CO con autenticación válida, **cuando** diligencio la interfaz de 4 etapas y radico los documentos digitales, **entonces** recibo el número de radicado por correo y puedo hacer seguimiento sin presentarme físicamente. <br> **Negativo:** **Dado** una sesión activa de 900 segundos en el paso 3, **cuando** la sesión expira, **entonces** el sistema me reautentica preservando lo diligenciado hasta ese punto. | #128,#130,#141,#152 |
| HU-B1-022 / HU-B2-016 / HU-B3-011 | Como ciudadano quiero pagar en línea (tarjeta/PSE) sin recargos. | **Positivo:** **Dado** que llego al paso de pago con una tarifa de trámite de $X, **cuando** pago por PSE o tarjeta certificada por Superfinanciera, **entonces** el total cobrado es $X sin recargo de pasarela y recibo el comprobante. <br> **Negativo:** **Dado** que hago doble clic o reintento el pago con la misma clave de idempotencia, **cuando** el sistema procesa la segunda solicitud, **entonces** devuelve el comprobante existente sin generar un segundo cargo ni un segundo radicado. | #22,#251,#128,#141 |
| HU-B1-025 / HU-B2-004 | Como ciudadano quiero que el resultado quede en mi Carpeta Ciudadana en GOV.CO. | **Positivo:** **Dado** que un trámite concluye exitosamente, **cuando** transcurren ≤24 h, **entonces** el documento resultado aparece en mi Carpeta Ciudadana Digital (CCD) con los metadatos correspondientes. <br> **Negativo:** **Dado** que la CCD está indisponible al momento de publicar el resultado, **cuando** el trámite finaliza, **entonces** el resultado se encola y se reintenta la publicación sin marcar el trámite como fallido. | #22,#156,#251 |
| HU-B2-011 / HU-B3-007 | Como ciudadano quiero consultar el estado por radicado sin llamar. | **Positivo:** **Dado** que ingreso mi número de radicado en el portal, **cuando** realizo la consulta, **entonces** veo la etapa actual, el tiempo estimado restante y los requisitos pendientes. <br> **Negativo:** **Dado** que estoy autenticado e ingreso el ID de un radicado que no me pertenece, **cuando** intento acceder a su detalle, **entonces** recibo un error 403 y el intento queda registrado en el log de seguridad. | #128,#152,#198,#221 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-03-D01 | **Como** ciudadano **quiero** que un doble clic o un timeout de PSE no me cobre dos veces ni genere dos radicados **para** confiar en el pago en línea. | **Positivo:** *Dado* que envío el formulario de pago, *cuando* hago doble clic o reintento con la misma clave de idempotencia, *entonces* el sistema devuelve el comprobante existente, sin segundo cargo ni segundo radicado. <br> **Negativo:** *Dado* un timeout de la pasarela, *cuando* reintento, *entonces* el sistema reconcilia por la clave de idempotencia y no duplica la transacción. <br> **DoR:** definida la clave de idempotencia por transacción. **DoD:** probado con envíos concurrentes y reintentos. | RF-03-D01 · RN-03-D01 · UC-007 E3' · [DOMINIO] |
| HU-03-D02 | **Como** ciudadano **quiero** que mi trámite avance solo cuando el pago se confirme **para** no perder dinero ni quedar en un estado ambiguo. | **Positivo:** *Dado* un pago `aprobado`, *cuando* la pasarela confirma, *entonces* el trámite avanza a la siguiente etapa con comprobante. <br> **Negativo (pendiente):** *Dado* un pago `pendiente`, *cuando* consulto el trámite, *entonces* veo "en espera de confirmación de pago" y el sistema reconsulta cada N min sin avanzar. <br> **Negativo (rechazado):** *Dado* un pago `rechazado`, *cuando* vuelvo, *entonces* el trámite no consume radicado y puedo reintentar el pago. | RF-03-D02 · RN-03-D02 · UC-007 E5 · [DOMINIO]+[WEB] |
| HU-03-D03 | **Como** ciudadano **quiero** pagar en línea sin que me cobren la pasarela como recargo **para** no pagar de más por usar el canal digital. | **Positivo:** *Dado* una tarifa de trámite de $X, *cuando* pago por PSE, *entonces* el total es $X sin recargo de pasarela. <br> **Negativo:** *Dado* una configuración que intenta sumar la comisión al ciudadano, *cuando* se calcula el total, *entonces* el sistema lo rechaza/registra como no conforme. | RN-03-D03 · UC-007 E6 · [NORMATIVA] Dec.2106 Art.7 |
| HU-03-D04 | **Como** ciudadano **quiero** guardar un trámite largo a medio diligenciar y retomarlo **para** no perder mi avance ni mis adjuntos. | **Positivo:** *Dado* que estoy en el paso 3 de 5, *cuando* cierro sesión y vuelvo, *entonces* retomo desde el paso 3 con datos y adjuntos intactos. <br> **Negativo (expiración):** *Dado* un borrador que superó el período de retención, *cuando* vuelvo, *entonces* se me informa que expiró y se descarta (**[PREGUNTA ABIERTA]** plazo de retención). <br> **Negativo (ficha cambió):** *Dado* que la ficha del trámite cambió desde el guardado, *cuando* reanudo, *entonces* se me avisa y se revalida lo diligenciado. | RF-03-D03 · RNF-03-D02 · UC-045 · [DOMINIO] |
| HU-03-D05 | **Como** ciudadano **quiero** que el sistema valide mis adjuntos del trámite con mensajes claros **para** corregir antes de radicar. | **Positivo:** *Dado* un PDF válido dentro del tamaño y cantidad permitidos, *cuando* lo cargo, *entonces* se acepta. <br> **Negativo (MIME falso):** *Dado* un `.php` renombrado a `.pdf`, *cuando* lo cargo, *entonces* se rechaza por MIME real inválido con mensaje accesible. <br> **Negativo (malware):** *Dado* un archivo con malware, *cuando* pasa el antivirus, *entonces* se bloquea y se registra. <br> Nota de frontera: aplica a **trámites** (requisito del trámite); NO a PQRSD (C-06). | RF-03-D04 · RN-03-D04 · [DOMINIO]+[NORMATIVA] |
| HU-03-D06 | **Como** ciudadano **quiero** responder a un requerimiento de documentación adicional y reanudar el trámite **para** no tener que empezar de cero. | **Positivo:** *Dado* un trámite "requiere subsanación", *cuando* cargo lo solicitado, *entonces* el estado pasa a "Subsanado", el trámite continúa y el plazo se recalcula desde la entrega. <br> **Negativo (vencimiento):** *Dado* que vence el plazo de subsanación sin que yo responda, *cuando* corre el cómputo, *entonces* el sistema declara desistimiento por no subsanar y me notifica. <br> **Negativo (parcial):** *Dado* que entrego solo parte de lo requerido, *cuando* confirmo, *entonces* el trámite sigue "requiere subsanación" por lo pendiente. | RF-03-D05 · RN-03-D05 · UC-042 · [NORMATIVA] Ley 1755 Art.17 · [WEB] |
| HU-03-D07 | **Como** funcionario de la dependencia **quiero** marcar un trámite como "requiere subsanación" indicando qué falta y el plazo **para** completar el expediente conforme a la ley. | **Positivo:** *Dado* un trámite en Etapa 3, *cuando* marco subsanación con detalle y plazo, *entonces* el ciudadano es notificado y el trámite se suspende. <br> **Negativo:** *Dado* un requerimiento sin detalle de lo faltante, *cuando* intento enviarlo, *entonces* el sistema lo bloquea (motivación obligatoria). | RF-03-D05 · RN-03-D05 · UC-042 · [NORMATIVA] Ley 1755 Art.17 |
| HU-03-D08 | **Como** ciudadano **quiero** desistir de un trámite antes de su resolución **para** detenerlo cuando ya no lo necesito. | **Positivo:** *Dado* un trámite en Etapa 2, *cuando* elijo "Desistir" y confirmo (doble confirmación), *entonces* el estado pasa a "Desistido" con sello temporal y queda en el expediente. <br> **Negativo (ya resuelto):** *Dado* un trámite ya resuelto, *cuando* intento desistir, *entonces* no se permite y se informa. <br> **Negativo (pago previo):** *Dado* un trámite pagado, *cuando* desisto, *entonces* se aplica la política de reembolso del trámite (**[PREGUNTA ABIERTA]** reembolso). | RF-03-D07 · RN-03-D07 · UC-043 · [NORMATIVA] Ley 1755 Art.18 · [WEB] |
| HU-03-D09 | **Como** funcionario responsable **quiero** que el sistema marque el vencimiento del término en trámites con SAP y registre el efecto "concedido" **para** cumplir la ley y notificar al ciudadano. | **Positivo:** *Dado* un trámite sujeto a SAP, *cuando* vence el término sin resolución expresa, *entonces* el sistema marca "concedido", notifica al ciudadano y alerta a Secretaría Jurídica. <br> **Negativo (no SAP):** *Dado* un trámite NO sujeto a SAP, *cuando* vence el término, *entonces* se dispara escalamiento ordinario, no efecto positivo. <br> **DoR:** catálogo de trámites con SAP y su término (**[PREGUNTA ABIERTA]** Secretaría Jurídica). | RN-03-D08 · UC-044 · [NORMATIVA] Ley 1755 Art.83-85; Ley 2052 Art.12 · [WEB] |
| HU-03-D10 | **Como** ciudadano **quiero** poder continuar mi trámite con carga manual cuando la verificación automática falla **para** que una caída técnica no me bloquee. | **Positivo:** *Dado* que ANI/RUNT/RUAF no responde, *cuando* el sistema agota reintentos, *entonces* habilita carga manual temporal del documento dejando constancia de "falla técnica documentada". <br> **Negativo:** *Dado* que la verificación automática funciona, *cuando* el dato está disponible vía interoperabilidad, *entonces* NO se exige el documento (la carga manual es excepción, no regla). | RF-03-D06 · RN-03-D06 · UC-004 E2' · [NORMATIVA] Dec.2106 Art.10 |
| HU-03-D11 | **Como** ciudadano **quiero** saber si mi nivel de autenticación no alcanza para un trámite **para** elevarlo antes de empezar. | **Positivo:** *Dado* un trámite que exige nivel Alto, *cuando* entro con nivel Alto, *entonces* puedo iniciarlo. <br> **Negativo:** *Dado* el mismo trámite, *cuando* entro con nivel Medio, *entonces* el sistema bloquea el inicio e indica cómo elevar el nivel (**[PREGUNTA ABIERTA]** matriz trámite↔nivel). | RN-TX-D04 · UC-004 E8 · C-08 · [NORMATIVA] Dec.620 |
| HU-03-D12 | **Como** ciudadano **quiero** buscar y paginar el catálogo de trámites con respuesta rápida **para** encontrar lo que necesito sin esperas. | **Positivo:** *Dado* el catálogo completo, *cuando* busco un trámite, *entonces* el resultado llega en ≤2 s y la lista pagina ≥10 por página. <br> **Negativo (sin resultados):** *Dado* una búsqueda sin coincidencias, *cuando* la ejecuto, *entonces* veo "sin resultados" con sugerencias, no una página en blanco. | RNF-03-D01 · [DOMINIO] |
| HU-03-D13 | **Como** entidad **quiero** que dos envíos simultáneos nunca compartan número de radicado **para** garantizar la unicidad del consecutivo. | **Positivo:** *Dado* dos radicaciones simultáneas, *cuando* el sistema asigna consecutivo, *entonces* cada una recibe un número único de forma atómica. <br> **Negativo:** *Dado* una condición de carrera, *cuando* dos hilos piden número a la vez, *entonces* ninguno obtiene un duplicado (uno espera o reintenta). | RNF-04-D02 · RN-04-D07 · UC-001 E9 · [DOMINIO] |

## 7. Datos / Entidades del módulo

- **Trámite/OPA/Consulta:** id_SUIT (TXX), nombre, tipo/modalidad, descripción, requisitos, pasos, costo, tiempo de resolución, documentos, resultado esperado, grupo objetivo, nivel de transformación (1-6), URL digital, georreferenciación, estado estandarizado. (#128,#152,#172,#131)
- **Solicitud/Radicado:** número único, fecha-hora, documentos adjuntos, datos del formulario, estado (etapa), tiempo estimado, pago. (#128,#152,#198)
- **Resultado del trámite:** acto administrativo, documento de facultación, resumen, URL en Carpeta Ciudadana. (#128,#152)
- **Retroalimentación:** FÁCIL/DIFÍCIL, comentario, trámite asociado. (#128,#130,#198)
- **Expediente electrónico:** documentos, foliado, índice firmado, metadatos de autenticidad, TRD. (#131,#251)

## 8. Integraciones

- **SUIT (DAFP):** fichas, códigos, priorización; actualización ≤3 días hábiles. (#128,#152,#79)
- **SCD Autenticación (OIDC)**, **SCD Interoperabilidad (X-Road)**, **Carpeta Ciudadana** (módulos 09 y 10).
- **SGDEA**: expedientes electrónicos (módulos 10/12).
- **Pasarela de pago** (Superintendencia Financiera): PSE/tarjetas. (#128,#141)

## 9. Ambigüedades y preguntas abiertas

- **[C-01]** Adjuntos: Anexo 5 dice "sin restricciones técnicas"; Anexo 5.1 dice "la entidad define el tipo y tamaño". *(ver módulo 04 PQRSD para el detalle de la contradicción)*. (#128,#152,#130,#198)
- **[C-04]** Ubicación del formulario PQRSD y del catálogo dentro del menú "Atención y Servicios". (#225,#241,#239)
- **[Riesgo]** La metodología de 17 semanas aplica a **un** trámite; con muchos trámites en paralelo puede saturar al equipo de TI y retrasar los plazos del Decreto 088/2022. (#108)
- **[PREGUNTA ABIERTA]** ¿Cuántos trámites tiene la Alcaldía en SUIT y cuál es su nivel de digitalización actual? ¿Cuáles son los de mayor volumen? (#79,#195,#239)
- **[Trámites jurisdiccionales]** Si aplican a la Alcaldía: radicación de demandas, términos procesales con alertas, audiencias por videoconferencia, reparto aleatorio (#131) — la guía usa "debería" (no "debe"), tensión con Art.103 CGP.

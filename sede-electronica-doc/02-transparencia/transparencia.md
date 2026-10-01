# Módulo 02 — Transparencia y Acceso a la Información Pública

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** el menú "Transparencia y acceso a la información pública" y sus **10 subsecciones mínimas** (Ley 1712/2014, Decreto 1081/2015, Res. MinTIC 1519/2020 Anexo 2): información de la entidad, normativa, contratación, planeación/presupuesto/informes, trámites, participa, datos abiertos, grupos de interés, obligación de reporte específico e información tributaria. Incluye directorio (SIGEP), control interno, calendario tributario y publicación cronológica.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Transparencia es el módulo de **publicación proactiva** de información pública. La información debe publicarse de forma inmediata o en tiempo real, en orden cronológico inverso, en formatos abiertos descargables y procesables por máquina, con **fuente única** (sin duplicar; los demás menús redirigen a la fuente). Es uno de los módulos de mayor peso para el cumplimiento ITA. Se integra con SUIN, SECOP, SIGEP, datos.gov.co, SUCOP y KOGUI.

## 2. Requisitos Funcionales (RF)

### 2.1 Estructura general

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-012 / RF-B3-081 | Publicar el menú **"Transparencia"** con ≥10 subsecciones: (1) Información de la entidad, (2) Normativa, (3) Contratación, (4) Planeación/presupuesto/informes, (5) Trámites, (6) Participa, (7) Datos abiertos, (8) Grupos de interés, (9) Obligación de reporte específico, (10) Tributaria (predial, ICA). | #225,#241,#221,#217 | Must | Al navegar Transparencia, el ciudadano encuentra las 10 subsecciones con contenido publicado y actualizado. |
| RF-B3-087 | Información en **orden cronológico** (más reciente primero) + **buscador de transparencia**. | #221 | Must | El documento más reciente aparece primero; hay buscador para filtrar por texto. |
| RF-B3-088 | **Fuente única**: otros menús redirigen a Transparencia sin duplicar el documento. | #221 | Must | Un documento enlazado desde Servicios apunta al mismo documento en Transparencia. |

### 2.2 Información de la entidad y directorio

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-097 | Publicar **información institucional**: misión, visión, funciones, organigrama actualizado, directorio, plan de acción, informe de gestión, estados financieros, información tributaria territorial. | #225,#239 | Must | En "Información de la entidad" el ciudadano encuentra el organigrama vigente con nombre, cargo y contacto de dependencias. |
| RF-B1-017 / RF-B3-085 | **Directorio de servidores públicos** vinculado a **SIGEP**: nombre, cargo, correo institucional, teléfono/extensión, dependencia; actualización en tiempo real ante ingresos/desvinculaciones. | #225,#241,#221 | Must | Nuevo servidor registrado en SIGEP aparece en el directorio en ≤1 día hábil. |
| RF-B1-096 | Publicar información específica para **grupos de interés** (mín.: ciudadanía, proveedores, medios, gremios) conforme a la caracterización de la entidad. | #225 | Should | Un proveedor encuentra en "Grupos de interés" procesos de contratación, formularios e contactos de la oficina de contratación. |

### 2.3 Normativa

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-013 / RF-B3-082 | Publicar **normativa** con: tipo, número, fecha de expedición, fecha de publicación, epígrafe, vigencia, enlace de descarga en formato abierto; orden cronológico inverso; proyectos de norma con fecha máxima de comentarios. Enlace al **SUIN**. | #7,#225,#241,#221 | Must | Norma expedida se publica en ≤24 horas hábiles con todos los campos completos. |
| RF-B1-014 | Enlace funcional al **SUIN** (suin-juriscol.ramajudicial.gov.co) y al Diario/Gaceta Oficial; publicar **Agenda Regulatoria**; comentarios ciudadanos a normas en elaboración (integración **SUCOP**). | #7,#225 | Must | El enlace al SUIN funciona sin errores. |

### 2.4 Contratación

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-015 / RF-B3-090 / RF-B1-074 | Enlace funcional a **SECOP I/II**; publicar plan anual de adquisiciones, contratos adjudicados (objeto, monto), ejecución (inicio, fin, valor, % ejecutado, pagos, otrosíes). | #225,#241,#221,#239 | Must | En Contratación, clic al enlace SECOP redirige con los contratos de la Alcaldía visibles. |

### 2.5 Planeación, informes y control interno

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-016 / RF-B3-083 | Publicar el **Plan de Acción** antes del 31 de enero de cada año; avances de proyectos de inversión y de metas/indicadores cada 3 meses. | #225,#221 | Must | El 31 de enero el ciudadano encuentra el Plan de Acción vigente publicado en esa fecha o antes. |
| RF-B3-084 | Publicar **informe de gestión** antes del 31 de enero de cada año. | #221 | Must | En Transparencia > Planeación aparece el informe con la fecha correcta. |
| RF-B1-037 / RF-B3-091 | Publicar **informes trimestrales de PQRSD** y de acceso a información (cantidad, tipo, estado, tiempo de respuesta); defensa pública o redirección a **KOGUI**. *(detalle de PQRSD en módulo 04)* | #225,#221 | Must/Should | Al cerrar el trimestre, el informe aparece en Transparencia en formato abierto antes del día 15 del mes siguiente. |
| RF-B3-152 | Publicar **informe de control interno** cada 6 meses en el sitio web. | Decreto 2106 Art.156; #99 | Must | Al cierre del semestre, el informe aparece en Transparencia en los primeros 5 días hábiles. |

### 2.6 Información tributaria territorial

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-018 / RF-B3-089 | Publicar **predial, ICA** y otros impuestos distritales con: sujeto activo, sujeto pasivo, hecho generador, hecho imponible, causación, base gravable, tarifa; proceso de recaudo de rentas locales. | #225,#239,#221 | Must | En la sección tributaria, la ficha del ICA muestra todos los elementos completos para el periodo vigente. |
| RF-B3-151 | Publicar el **calendario tributario** en la web (fechas de vencimiento de cada impuesto). | Decreto 2106 Art.39; #79 | Must | Al iniciar la vigencia fiscal, el calendario aparece publicado con las fechas de vencimiento. |

> **Datos abiertos** (RF-B1-019/020, RF-B3-086) y **tablero ITA** (RF-B1-078): la subsección de datos abiertos se detalla en el módulo **11 Datos Abiertos**; el tablero de cumplimiento ITA en el módulo **12 Gestión de Contenidos**.

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-02-D01 | **CRUD + versionado de documentos de transparencia**: editar/reemplazar un documento publicado debe conservar la versión anterior con su fecha (historial), sin romper el enlace de "fuente única" (RF-B3-088). | [DOMINIO] solo existe el "create" (UC-B1-006); falta U/D/versión | Must | Reemplazar el Plan de Acción 2025 → la URL se mantiene, el documento anterior queda accesible como versión histórica con su fecha. |
| RF-02-D02 | **Alertas de vencimiento de publicaciones obligatorias** (Plan de Acción y Gestión 31-ene, informes trimestrales PQRSD/inversión, control interno 6 meses): el sistema notifica al responsable N días antes del plazo legal. | [NORMATIVA] Res.1519 Anexo 2 4.3/4.7/4.10; los RF actuales describen la publicación pero no el recordatorio | Should | A 10 días del 31-ene sin Plan de Acción cargado → alerta al administrador de cumplimiento. |
| RF-02-D03 | **Manejo de caída de integraciones externas** (SECOP, SIGEP, SUIN, SUCOP, KOGUI): si el enlace/servicio destino no responde, mostrar mensaje claro y registrar el fallo, sin página rota. | [DOMINIO] RF-B1-014/015 asumen enlace "funcional" sin estado de fallo | Should | SECOP caído → "El servicio de contratación no está disponible temporalmente", con log; el RF-B2-041 (0 vínculos rotos) no se viola. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-037 / RNF-B2-017 | Calidad de información | Actualizada, auténtica, íntegra, fiable, oportuna, objetiva, veraz, completa, reutilizable | #241,#70 |
| RNF-B1-038 / RNF-B3-037 | Lenguaje claro | Guía de Lenguaje Claro del DNP; **índice Fernández-Huerta ≥ 60** ("bastante fácil") en descripciones de trámites | #225,#251,#243 |
| RNF-B3-039 | Formatos | ≥90% de documentos en formatos abiertos y procesables (CSV, XML, RDF, JSON, ODF) | #132,#217 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-02-D01 | Trazabilidad de actualización SIGEP | El directorio debe registrar fecha/hora de última sincronización con SIGEP y alertar si la sincronización lleva >24 h sin éxito (RF-B1-017 exige actualización ≤1 día hábil). | [DOMINIO]+[INFERENCIA] sobre RN-B3-022 |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-002 | Toda información pública se publica **proactivamente, inmediata o en tiempo real**, en formatos abiertos descargables; sin condicionar a formatos/requisitos previos. | Ley 1712/2014 Arts.9,11; Decreto 1081/2015 | #225 |
| RN-B1-003 / RN-B3-020 | Plan de Acción e Informe de Gestión se publican antes del **31 de enero**. | Res. 1519/2020 Anexo 2, 4.3/4.7; Ley 1474/2011 Art.74 | #225 |
| RN-B1-004 / RN-B3-021 | Informes trimestrales PQRSD/acceso a información e inversión cada **3 meses**; ejecución de metas en los 10 primeros días hábiles del trimestre. | Res. 1519/2020 Anexo 2, 4.10; Ley 1474/2011 | #225 |
| RN-B1-005 / RN-B3-023 | Normativa expedida se publica **inmediatamente/tiempo real** (≤24 h), con vigencia; proyectos con fecha de comentarios. | Res. 1519/2020 Anexo 2, 2.4.1; Ley 1712/2014; Decreto 1081/2015 | #225,#221 |
| RN-B1-018 / RN-B3-024 | Contratos con recursos públicos publicados en **SECOP**; la sede enlaza al proceso. | Ley 1150/2007; Decreto 1082/2015 | #225 |
| RN-B3-022 | Directorio de servidores vinculado a **SIGEP**, actualización en tiempo real. | SIGEP — Función Pública | #221 |
| RN-B3-025 | Publicar **tarifas ICA** con sujeto activo/pasivo, hecho generador, base gravable y tarifa. | Estatuto tributario distrital | #221 |
| RN-B3-028 | Informe de **control interno** cada 6 meses en la web. | Decreto 2106/2019 Art.156 | — |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-02-D01 | Reemplazar un documento de transparencia publicado **conserva la versión anterior con su fecha** (historial inmutable) y **no rompe la URL de fuente única**: la publicación es proactiva pero la trazabilidad de versiones es obligatoria. (Motiva RF-02-D01; refuerza RN-B1-002.) | Ley 1712/2014 Arts.9,11; principio de integridad documental (Ley 594/2000) | [NORMATIVA]+[DOMINIO] |
| RN-02-D02 | Las publicaciones obligatorias con fecha legal fija (Plan de Acción/Informe de Gestión 31-ene, trimestrales PQRSD/inversión, control interno cada 6 meses) **disparan alerta al responsable de cumplimiento N días antes** del plazo; el incumplimiento es un hallazgo ITA. (Motiva RF-02-D02; opera RN-B1-003/004 y RN-B3-028.) | Res. 1519/2020 Anexo 2, 4.3/4.7/4.10; Ley 1474/2011 Art.74 | [NORMATIVA] |
| RN-02-D03 | La caída de una integración externa de transparencia (SECOP/SIGEP/SUIN/SUCOP/KOGUI) NO constituye "vínculo roto" si el sistema muestra mensaje de indisponibilidad temporal y registra el fallo; un enlace que retorna error sin manejo SÍ viola la meta de 0 vínculos rotos. (Motiva RF-02-D03.) | Res. 1519/2020 Anexo 2 (disponibilidad de la información) | [DOMINIO] |
| RN-02-D04 | El directorio se considera **desactualizado** si la sincronización con SIGEP lleva >24 h sin éxito (la norma exige actualización ≤1 día hábil): se debe alertar y registrar la fecha/hora de última sincronización exitosa. (Motiva RNF-02-D01; opera RN-B3-022.) | SIGEP — Función Pública; Res.1519 Anexo 2 | [NORMATIVA]+[INFERENCIA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-005 | Consultar normativa institucional | Ciudadano | Transparencia → Normativa → filtra por tipo/año → listado cronológico inverso → ficha (tipo, número, fechas, epígrafe, vigencia) → descarga o enlace al SUIN. | #7,#225 |
| UC-B1-006 / UC-B3-012 | Publicar documento en Transparencia | Administrador del CMS | Autenticarse → módulo Transparencia/Normativa → crear documento (tipo, número, fechas, epígrafe, vigencia, archivo) → validación de campos → publicar → aparece en orden cronológico → log de auditoría. | #225,#241,#221 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-006 | Como ciudadano quiero acceder a transparencia (contratos, presupuesto, nómina) para ejercer control social. | **Positivo:** **Dado** que navego la sección de Contratación, **cuando** la abro, **entonces** encuentro el plan anual de adquisiciones y un enlace directo al SECOP con los contratos de la entidad. <br> **Negativo:** **Dado** que SECOP está caído, **cuando** abro la sección de Contratación, **entonces** veo el mensaje "El servicio de contratación no está disponible temporalmente" y el fallo queda registrado. | #225,#241 |
| HU-B1-007 | Como contribuyente quiero consultar las tarifas del ICA para saber cuánto pagar y los plazos. | **Positivo:** **Dado** que accedo a la sección tributaria de la sede, **cuando** consulto el ICA, **entonces** encuentro el sujeto activo/pasivo, el hecho generador, la base gravable, la tarifa vigente y el enlace de liquidación. | #225,#239 |
| HU-B1-017 | Como ciudadano quiero usar el buscador para encontrar documentos de transparencia sin navegar el menú. | **Positivo:** **Dado** que uso el buscador de la sede con el término "informe de gestión 2024", **cuando** se muestran los resultados, **entonces** el primer resultado relevante es ese informe con su fecha y categoría visibles. | #225,#241 |
| HU-B3-014 | Como ciudadano quiero la contratación con enlace directo a SECOP. | **Positivo:** **Dado** que navego a Transparencia > Contratación, **cuando** localizo un proceso contractual, **entonces** encuentro el enlace directo al proceso correspondiente en SECOP II. | #221 |
| HU-B3-015 | Como ciudadano quiero ver el Plan de Acción y el Informe de Gestión publicados antes del 31 de enero. | **Positivo:** **Dado** que llega el 31 de enero, **cuando** accedo a Transparencia > Planeación, **entonces** encuentro publicados ambos documentos del año vigente con su fecha de publicación. <br> **Negativo:** **Dado** que faltan 10 días para el 31 de enero sin que el Plan de Acción esté cargado, **cuando** corre el chequeo diario, **entonces** el administrador de cumplimiento recibe alerta con el ítem pendiente, la norma y el responsable. | #221 |
| HU-B3-025 | Como ciudadano quiero ver el calendario tributario para conocer vencimientos. | **Positivo:** **Dado** que accedo a la sección de información tributaria, **cuando** consulto el calendario, **entonces** encuentro las fechas de vencimiento de predial, ICA y demás impuestos para la vigencia actual. | Decreto 2106 Art.39 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-02-D01 | **Como** editor de transparencia **quiero** reemplazar un documento publicado conservando la versión anterior **para** preservar la trazabilidad histórica sin romper la URL de fuente única. | **Positivo:** *Dado* el Plan de Acción 2025 publicado, *cuando* subo una versión corregida, *entonces* la URL se mantiene y la versión anterior queda accesible como histórico con su fecha. <br> **Negativo:** *Dado* un reemplazo en curso, *cuando* falla el guardado, *entonces* la versión publicada original permanece intacta (no se pierde el documento vigente). <br> **DoD:** historial inmutable; enlace de fuente única verificado sin 404. | RF-02-D01 · RN-02-D01 · UC-050 · [NORMATIVA] Ley 1712 |
| HU-02-D02 | **Como** administrador de cumplimiento **quiero** recibir alertas N días antes del plazo legal de cada publicación obligatoria **para** no incurrir en hallazgo ITA. | **Positivo:** *Dado* que faltan 10 días para el 31-ene sin Plan de Acción cargado, *cuando* corre el chequeo diario, *entonces* recibo alerta con el ítem, la norma y el responsable. <br> **Negativo:** *Dado* el plazo vencido sin publicación, *cuando* se evalúa el ITA, *entonces* el ítem se marca "incumplido" y se escala al supervisor. | RF-02-D02 · RN-02-D02 · UC-050 E2 · [NORMATIVA] Res.1519 Anexo 2 |
| HU-02-D03 | **Como** ciudadano **quiero** un mensaje claro cuando SECOP/SIGEP/SUIN/SUCOP/KOGUI no responden **para** no toparme con una página rota. | **Positivo:** *Dado* SECOP caído, *cuando* abro Contratación, *entonces* veo "El servicio de contratación no está disponible temporalmente" y el fallo queda registrado. <br> **Negativo:** *Dado* el fallo, *cuando* se mide la meta de 0 vínculos rotos, *entonces* el enlace gestionado NO cuenta como roto (a diferencia de un error HTTP sin manejo). | RF-02-D03 · RN-02-D03 · UC-050 E1 · [DOMINIO] |
| HU-02-D04 | **Como** administrador del directorio **quiero** ver la fecha de última sincronización con SIGEP y ser alertado si supera 24 h **para** garantizar la actualización ≤1 día hábil. | **Positivo:** *Dado* que la última sync con SIGEP fue hace 30 h, *cuando* reviso el directorio, *entonces* veo la marca "desactualizado" y recibí alerta. <br> **Negativo:** *Dado* que la sync falla repetidamente, *cuando* pasan 24 h, *entonces* se alerta y se conserva el último directorio válido (no se muestra vacío). | RNF-02-D01 · RN-02-D04 · [NORMATIVA]+[INFERENCIA] |

## 7. Datos / Entidades del módulo

- **Normativa:** tipo, número, fecha de expedición, fecha de publicación, epígrafe/descripción, enlace, vigencia. (#7,#225)
- **Contratación:** plan anual de adquisiciones, contratos adjudicados (objeto, monto, honorarios), ejecución (inicio, fin, valor, % ejecutado, pagos, pendientes, otrosíes). (#225)
- **Directorio institucional:** dependencia, responsable, cargo, correo institucional, teléfono/extensión, código SIGEP. (#239,#225)
- **Información tributaria:** impuesto (predial, ICA, retenciones), sujeto activo/pasivo, hecho generador, base gravable, tarifa, formularios de liquidación. (#239,#225)

## 8. Integraciones

- **SUIN** (Ministerio de Justicia): consulta de normas. (#225)
- **SECOP I/II**: contratación. (#225,#239)
- **SIGEP**: directorio de servidores. (#225,#239)
- **SUCOP** (DNP): comentarios a proyectos normativos. (#225)
- **KOGUI** (Agencia de Defensa Jurídica): informe de defensa pública. (#225)
- **datos.gov.co**: subsección de datos abiertos (ver módulo 11).

## 9. Ambigüedades y preguntas abiertas

- **[A-07]** "Grupos de interés": la norma exige identificarlos "conforme a su caracterización" sin definir cómo, ni qué pasa si la entidad no tiene caracterización formal. (#225)
- **[A-04]** Tensión datos.gov.co vs. archivo digital: datos.gov.co NO es archivo digital pero hay que vincular/automatizar los datos para su apertura allí. (#242)
- ~~**[PREGUNTA ABIERTA]** Nivel real de cumplimiento ITA (¿mock o real?)~~ → ✅ **RESUELTO (2026-06-05):** el ITA arranca en **cero** y se construye con **validación automática al publicar** (tablero interno, no público); no se usa el 47/100 ni datos mock. Ver módulo 12 (RF-B1-078/RF-12-D05) y `_global/contexto-transversal.md` §8 #2. (#239)
- **[LSC territorial]** ¿Las obligaciones de LSC (alocuciones, emergencias, rendición de cuentas) aplican a la Alcaldía como gobierno territorial o solo al nivel nacional? (#76,#200)

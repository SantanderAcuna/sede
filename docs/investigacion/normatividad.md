# Normatividad local y de referencia — Alcaldía Distrital de Santa Marta (D.T.C.H.)
## Insumo para el diseño de la SEDE ELECTRÓNICA

**Entidad:** Alcaldía Distrital de Santa Marta — Distrito Turístico, Cultural e Histórico
**NIT:** 891780009
**Fecha de consulta:** 2026-09-30
**Método:** `web_search`, `web_fetch` y `curl` sobre fuentes oficiales. Todo dato sin verificación se marca **NO ENCONTRADO**.

---

## 0. Resumen ejecutivo (hallazgos que condicionan el proyecto de sede electrónica)

| # | Hallazgo | Impacto en la sede electrónica |
|---|---|---|
| 1 | **No existe acto administrativo local (Decreto, Acuerdo o Resolución) publicado que adopte el PETI 2024-2027.** El PETI se publica como documento de planeación en PDF, sin norma de adopción identificable. | El PETI no tiene fuerza normativa interna verificable; la sede electrónica no puede anclarse a un acto de adopción local de TI. |
| 2 | **No se encontró acto administrativo de adopción de la Política de Seguridad y Privacidad de la Información.** El propio Plan declara: *"La presente política es aplicable a partir de su aprobación"* — sin identificar el acto de aprobación. | Falta el soporte normativo exigido por el modelo de seguridad y privacidad (Res. MinTIC 500/2021 y 746/2022). |
| 3 | **No se encontró acto administrativo de adopción del Plan de Tratamiento de Riesgos de Seguridad y Privacidad de la Información.** | Igual que el anterior. |
| 4 | **Barrido exhaustivo: 0 coincidencias** sobre 1.643 actos publicados (Decretos, Acuerdos, Resoluciones, Gaceta) con términos TIC, gobierno digital, sede electrónica, protección de datos personales, antiprámites. | No hay normatividad local de gobierno digital que reutilizar: la sede electrónica se construirá sobre normativa nacional directamente aplicable. |
| 5 | **La Política de Privacidad del sitio NO menciona la Ley 1581 de 2012 ni el Decreto 1377 de 2013**, no identifica al **responsable del tratamiento**, no enumera los **derechos del titular** ni el canal para ejercerlos. | Incumplimiento material frente a la Ley 1581/2012 y al Anexo 2 de la Res. 1519/2020. Es el vacío más grave y de corrección más urgente. |
| 6 | **Enlaces oficiales rotos** en la sección de Transparencia (ver §1.5), incluido el PDF de la Política de Seguridad que la propia entidad enlaza. | La sede electrónica debe sustituir enlaces muertos y garantizar permanencia de URL (Res. 1519/2020, arts. 4 y 5). |

---

## 1. Normatividad local sobre gobierno digital / TIC / sede electrónica / trámites / protección de datos

### 1.1 Estructura real del bloque "2. Normativa"

URL consultada: <https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica> — **HTTP 200** (167.016 bytes)

Los seis sub-bloques de "2. Normativa" **no contienen enlaces a actos individuales**. Son listados filtrados (Drupal Views) o anclas vacías. `href` reales extraídos del HTML:

| Bloque | `href` real | Estado |
|---|---|---|
| 2.1 Decretos | `https://www.santamarta.gov.co/documentos?tid=Decretos&title=` | 200 |
| 2.1.1 Decreto Único Reglamentario | `https://www.santamarta.gov.co/sites/default/files/decreto_1083_de_2015_sector_de_funcion_publica.pdf` | 200 |
| 2.1.5 Políticas, lineamientos y manuales | (listado de políticas; ver §1.3) | 200 |
| 2.2 Gaceta Distrital | `https://www.santamarta.gov.co/documentos?tid=Gaceta&title=` | 200 |
| 2.3 Acuerdos | `https://www.santamarta.gov.co/documentos?tid=Acuerdos&title=` | 200 |
| 2.4 Aviso Público de licitación | `https://www.santamarta.gov.co/documentos?tid=Aviso+P%C3%BAblico+de+Licitaci%C3%B3n&title=` | 200 |
| 2.5 Resoluciones | `https://www.santamarta.gov.co/documentos?tid=Resoluciones&title=` | 200 |
| 2.6 Edictos | `https://www.santamarta.gov.co/documentos?tid=Edictos&title=` | 200 |
| 2.7 Notificación por aviso | `https://www.santamarta.gov.co/documentos?tid=Notificaci%C3%B3n+por+Aviso&title=` | 200 |
| **2.8 Actos administrativos** | `#` — **ancla vacía** | **ROTO** |
| 2.9 Sistema Único de Información Normativa SUIN | `https://www.suin-juriscol.gov.co/legislacion/normatividad.html` | 200 (ver §5) |
| 2.10 Participación ciudadana — SUCOP | `https://www.sucop.gov.co/` | (externo) |

Únicas resoluciones citadas nominalmente en el bloque 2.5:
- `https://www.santamarta.gov.co/documentos/resolucion-no-942-del-03-de-noviembre-de-2020`
- `https://www.santamarta.gov.co/documentos/resolucion-no-118-del-05-de-abril-de-2021`
- `https://www.santamarta.gov.co/documentos/resolucion-no-119-del-05-de-abril-de-2021`
- Resolución de adopción del Manual de contratación y supervisión 2019 (PDF: `https://www.santamarta.gov.co/portal/archivos/RESOLUCI%C3%92N%20ADOPTA%20MANUEL%20DE%20CONTRATACI%C3%92N%202019.pdf`)

> **Ninguna de ellas versa sobre TIC, gobierno digital, sede electrónica, trámites, protección de datos o atención al ciudadano** — verificado en §1.2.

### 1.2 Barrido exhaustivo del repositorio de actos publicados (verificación del vacío)

Para no depender de un buscador que no indexa PDF (§4), se **rastreó y descargó la totalidad del repositorio** de publicaciones:

| Colección | Documentos indexados |
|---|---|
| Decretos | 528 |
| Acuerdos | 120 |
| Resoluciones | 288 |
| Gaceta | 763 |
| **Total URL únicas** | **1.643** (1.730 páginas HTML cacheadas) |

A las 1.643 fichas se les extrajo título, epígrafe (`field-subtitle`), **nombre de archivo de cada PDF adjunto** y el cuerpo completo del nodo, y se aplicó búsqueda insensible a acentos y mayúsculas sobre:

`TIC` · `tecnologías de la información` · `gobierno digital` · `gobierno en línea` · `gobierno electrónico` · `transformación digital` · `sede electrónica` · `sistemati*` · `informátic*` · `PETI` · `datos personales` · `habeas data` · `seguridad de la información` · `antitrámite` · `racionalización de trámites` · `firma electrónica` · `expediente electrónico`

**Resultado en el cuerpo de los actos: 0 coincidencias sobre 1.730 páginas analizadas.**

Único falso positivo (no es normativa TIC): *Resolución No. 3537 del 21 de diciembre de 2018* — "…infracciones de tránsito captadas por medios técnicos y/o tecnológicos" (`https://www.santamarta.gov.co/sites/default/files/digitalizacion_2018_08_13_16_07_08_865.pdf`), cuya coincidencia proviene además del nombre de archivo del escáner.

**Conclusión verificable:** a la fecha de consulta, la Alcaldía Distrital de Santa Marta **no ha publicado en su repositorio oficial ningún Decreto, Acuerdo ni Resolución vigente sobre TIC, gobierno digital, sede electrónica, racionalización de trámites o protección de datos personales.**

*Limitación:* 1.327 de las 1.643 fichas no exponen campo de epígrafe ni sinopsis; y la mayoría de los PDF son **escaneos sin capa de texto** (verificado: `res_180_de_5_feb_2018` = 22 págs / 0 caracteres extraíbles; `resolucion_1360_2026` = 4 págs / 0 caracteres). El barrido por metadatos es concluyente sobre lo **publicado y descrito**, no sobre el contenido interno de cada escaneo.

### 1.3 PETI — Plan Estratégico de Tecnologías de Información 2024-2027

**¿Existe acto administrativo de adopción del PETI 2024-2027? → NO ENCONTRADO.**
No aparece Decreto, Acuerdo ni Resolución de adopción en el repositorio (barrido §1.2), ni el propio PETI lo cita (su marco normativo solo lista normas nacionales, ver abajo).

Documentos publicados (todos **HTTP 200**, `application/pdf`):

| Versión | URL |
|---|---|
| PETI 2024-2027 **actualizado 2026** | <https://www.santamarta.gov.co/sites/default/files/peti-2024-2027_actualizado_2026.pdf> (935.610 bytes, 30 págs) |
| PETI 2024-2027 (actualizado 2025) | <https://www.santamarta.gov.co/sites/default/files/peti-2024-2027.pdf> (793.058 bytes) |
| PETI 2024-2027 (original) | <https://www.santamarta.gov.co/sites/default/files/peti_alcaldia_de_santa_marta-2024-2027.pdf> (798.488 bytes) |
| PETI 2023 | <https://www.santamarta.gov.co/sites/default/files/PETI_2023.pdf> |
| PETI 2022 | <https://www.santamarta.gov.co/sites/default/files/peti_2022.pdf> |
| Ficha (actualización 2026) | <https://www.santamarta.gov.co/documentos/plan-estrategico-de-tecnologias-de-informacion-peti-2024-2027> |

**Marco normativo declarado por el propio PETI** (sección "4. MARCO NORMATIVO", texto extraído del PDF):

> - Decreto 767 (2022): Lineamientos generales de la Política de Gobierno Digital.
> - Resolución 746 (2022): Modelo de Seguridad y Privacidad de la Información.
> - Directiva Presidencial 003 (2021): Lineamientos para el uso de servicios en la nube y gestión de datos.
> - Documento CONPES 3975 (2019): Política Nacional para la Transformación Digital e Inteligencia Artificial.

**Observación crítica:** el PETI **no cita ninguna norma local de adopción**, ni la Ley 1712 de 2014, ni la Resolución MinTIC 1519 de 2020 (estándares de sede electrónica). Su marco de referencia es insuficiente para sustentar una sede electrónica.

### 1.4 Política de Seguridad y Privacidad de la Información y Plan de Tratamiento de Riesgos

**¿Existe acto administrativo de adopción de la Política de Seguridad y Privacidad de la Información? → NO ENCONTRADO.**
**¿Existe acto administrativo de adopción del Plan de Tratamiento de Riesgos? → NO ENCONTRADO.**

Los documentos existen **solo como planes en PDF**, dentro del bloque "4.3 Planes" (no en "2. Normativa"):

| Documento | URL | Estado |
|---|---|---|
| Plan de Seguridad y Privacidad de la Información 2024-2027 — **Actualizado 2026** | <https://www.santamarta.gov.co/sites/default/files/plan_de_seguridad_de_la_informacion_2024-2027_actualizacion_2026.pdf> | 200 · 985.805 B |
| Plan de Seguridad y Privacidad de la Información 2024-2027 | <https://www.santamarta.gov.co/sites/default/files/plan_de_seguridad_y_privacidad_de_la_informacion_2024-2027.pdf> | 200 · 565.231 B |
| Plan de Seguridad y Privacidad de la Información 2023 | <https://www.santamarta.gov.co/sites/default/files/PLAN_DE_SEGURIDAD_Y_PRIVACIDAD_DE_LA_INFORMACION_2023.pdf> | 200 |
| Plan de Seguridad y Privacidad de la Información 2022 | <https://www.santamarta.gov.co/sites/default/files/plan_de_seguridad_y_privacidad_de_la_informacion_2022.pdf> | 200 |
| Plan de Tratamiento de Riesgos 2026 (ficha) | <https://www.santamarta.gov.co/documentos/plan-de-tratamiento-de-riesgos-de-seguridad-y-privacidad-de-la-informacion-2026> | 200 |
| Plan de Tratamiento de Riesgos 2026 (PDF) | <https://www.santamarta.gov.co/sites/default/files/plan_de_tratamiento_del_riesgo_de_seguridad_y_privacidad_de_la_informacion_2026.pdf> | 200 · 788.831 B |
| Plan de Tratamiento de Riesgos 2024 | <https://www.santamarta.gov.co/sites/default/files/plan_para_el_tratamiento_de_riesgos_de_seguridad_y_privacidad_de_la_informacion.pdf> | 200 |
| Plan de Tratamiento de Riesgos 2023 | <https://www.santamarta.gov.co/sites/default/files/PLAN_DE_TRATAMIENTO_RIESGOS_SEGURIDAD_PRIVACIDAD_INFORMACION_2023.pdf> | 200 |
| Plan de Tratamiento de Riesgos 2022 | <https://www.santamarta.gov.co/sites/default/files/plan_de_tratamiento_de_riesgos_de_seguridad_y_privacidad_de_la_informacion_2022.pdf> | 200 |

**Evidencia textual de la ausencia de acto de adopción** (Plan de Seguridad y Privacidad de la Información, sección "14. VALIDEZ DE LA POLITICA"):

> "La presente política es aplicable a partir de su aprobación."

No se identifica **cuál** es el acto de aprobación, ni su número, ni su fecha.

**Responsables declarados** (sección "5. RESPONSABLES"):

> "Los responsables del cumplimiento del plan de tratamiento de seguridad y privacidad de la información son de todos, los directivos, funcionarios y terceros que laboren o tengan relación con la Alcaldía Distrital de Santa Marta, con el acompañamiento de La Dirección de TIC."

**Marco normativo del Plan de Seguridad y Privacidad de la Información** (extraído del PDF):

> - MINTIC: Seguridad y Privacidad de la Información - Guía No. 2
> - Ley 1581 de 2012: Protección de datos personales.
> - Ley 1712 de 2014: Transparencia y acceso a la información pública.
> - MIPG: Modelo Integrado de Planeación y Gestión

**Marco normativo del Plan de Tratamiento de Riesgos 2026** (extraído del PDF):

> - Constitución Política de Colombia: Artículo 15
> - MINTIC: Seguridad y Privacidad de la Información - Guía No. 2
> - Ley 1581 de 2012 · Decreto 1377 de 2013
> - Ley 1273 de 2009 (Delitos Informáticos)
> - Ley 1712 de 2014 · Decreto 1078 de 2015 (Decreto Único Reglamentario del Sector TIC)
> - Decreto 767 de 2022 (texto íntegro citado en el documento)
> - Resolución 00500 de 2021: "Por la cual se establecen los lineamientos y estándares para la estrategia de seguridad digital y se adopta el modelo de seguridad y privacidad como habilitador de la política de gobierno digital".
> - Circular Única de la Superintendencia de Industria y Comercio (SIC)
> - Directrices y Políticas Internas de la Alcaldía Distrital de Santa Marta

**Observación crítica:** el Plan de Tratamiento de Riesgos cita expresamente la **Ley 1581 de 2012** y el **Decreto 1377 de 2013**, pero la **Política de Privacidad publicada en el sitio no los menciona** (§2). Existe una contradicción documental interna.

### 1.5 Actos locales SÍ existentes y relevantes para la sede electrónica

Aunque no son de gobierno digital, estos actos son el **anclaje normativo local más cercano** disponible:

| Acto (título verificado) | URL | Relevancia |
|---|---|---|
| **RESOLUCIÓN 1360 DEL 6 DE ABRIL DE 2026** — *"POR MEDIO DE LA CUAL SE ADOPTA LA POLÍTICA GESTIÓN DOCUMENTAL DEL DISTRITO TURÍSTICO CULTURAL E HISTÓRICO DE SANTA MARTA"* | <https://www.santamarta.gov.co/documentos/resolucion-1360-del-6-de-abril-de-2026-adopcion-politica-gestion-documental-dtch-santa> · PDF: <https://www.santamarta.gov.co/sites/default/files/resolucion_1360_2026_adopcion_politica_gestion_documental_dtch_santa_marta.pdf> | **El más relevante**: base para documento electrónico, expediente y archivo digital. ⚠️ PDF escaneado, 4 págs, **sin capa de texto** |
| **RESOLUCIÓN No. 6449 DEL 29 DE DICIEMBRE DEL 2025** — *"POR MEDIO DE LA CUAL SE ADOPTA LA ACTUALIZACIÓN AL MANUAL Y PROTOCOLO DE ATENCIÓN AL CIUDADANO EN LA ALCALDÍA DEL DISTRITO TURÍSTICO, CULTURAL E HISTÓRICO DE SANTA MARTA"* | <https://www.santamarta.gov.co/documentos/resolucion-no-6449-del-29-de-diciembre-del-2025> · PDF: <https://www.santamarta.gov.co/sites/default/files/resolucion_y_manual_y_protocolo_de_atencion_al_ciudadano.pdf> (17.543.640 B) | Servicio y atención al ciudadano; base para canales y PQRSD de la sede |
| **RES 180 DE 5 FEB 2018 – GESTIÓN DOCUMENTAL** | <https://www.santamarta.gov.co/documentos/res-180-de-5-feb-2018-gestion-documental> · PDF: <https://www.santamarta.gov.co/sites/default/files/res_180_de_5_feb_2018_gestion_documental.pdf> (22 págs) | Gestión documental. Epígrafe no publicado en la ficha |
| **RES 181 DE 5 FEB 2018 – GESTIÓN DOCUMENTAL** | <https://www.santamarta.gov.co/documentos/res-181-de-5-feb-2018-gestion-documental> · PDF: <https://www.santamarta.gov.co/sites/default/files/res_181_de_5_feb_2018_gestion_documental.pdf> (38 págs) | Gestión documental. Epígrafe no publicado en la ficha |
| **RESOLUCIÓN No. 01470010001472024** — *"Por medio de la cual se establecen los requisitos para los trámites solicitudes catastrales de los servicios a cargo de la Unidad Administrativa Especial de Catastro Multipropósito Distrital de Santa Marta"* | <https://www.santamarta.gov.co/documentos/resolucion-no-01470010001472024> · PDF: <https://www.santamarta.gov.co/sites/default/files/resolucion_requisitios_vf_1_2024_1fmd.pdf> | Único acto local localizado que fija **requisitos de trámites** |
| Resolución No. 0147001001482205 del 19 de diciembre de 2025 — suspensión de términos de trámites y actuaciones administrativas del 23-dic-2025 al 12-ene-2026 | <https://www.santamarta.gov.co/documentos/resolucion-no-0147001001482205-del-19-de-diciembre-de-2025> | Referencia operativa de suspensión de términos |

**Instrumentos de política (no normativos) relevantes:**

| Instrumento | URL | Estado |
|---|---|---|
| Estrategia de Racionalización de Trámites | <https://www.santamarta.gov.co/portal/archivos/documentos/ESTRATEGIA-RACIONALIZACION-DE-TRAMITES.pdf> | 200 · 1.333.051 B |
| Programa de Gestión Documental (PGD) | <https://www.santamarta.gov.co/portal/archivos/documentos/PGD.pdf> | 200 · 345.558 B |
| Manual de atención de PQRSD | <https://www.santamarta.gov.co/sites/default/files/Manual-de-atencion-de-PQRSD.pdf> | 200 · 981.094 B |
| Manual para presentar quejas y reclamos | <https://www.santamarta.gov.co/portal/archivos/documentos/MANUAL%20PQRSD%20(1).pdf> | 200 |
| Plan de Preservación Digital a largo plazo | <https://www.santamarta.gov.co/documentos/plan-de-preservacion-digital-largo-plazo-gestion-documental-de-la-alcaldia-distrital> | 200 |
| Sistema Integrado de Conservación (SIC) | <https://www.santamarta.gov.co/documentos/sistema-integrado-de-conservacion-sic-gestion-documental-secretaria-general> | 200 |
| Tablas de Retención Documental | <https://www.santamarta.gov.co/portal/archivos/documentos/Manual%20de%20Implementaci%C3%B3n%20de%20TRD%20ADSM%20(2).pdf> | 200 |

### 1.6 Enlaces oficiales ROTOS o degradados (hallazgo de transparencia)

| Elemento en el sitio | `href` publicado | HTTP |
|---|---|---|
| "Plan Antitrámite" (bloque 4.3) | `https://www.santamarta.gov.co/` — **apunta a la portada** | 200 pero **contenido incorrecto** |
| "2.8 Actos administrativos" | `#` | **ancla vacía** |
| "5.2 Normatividad de trámites" | `#` | **ancla vacía** |
| "Políticas de seguridad de la información y protección de datos personales" (pie de página) | `https://www.santamarta.gov.co/portal/archivos/documentos/Plan%20de%20Seguridad%20y%20Privacidad%20de%20la%20Informaci%C3%B3n` | **404** |
| "Índice de información clasificada y reservada" | `https://www.santamarta.gov.co/portal/archivos/documentos/` (directorio) | 200 pero **sin documento** |
| "Esquema de publicación de información" | `https://www.santamarta.gov.co/portal/archivos/documentos/` (directorio) | 200 pero **sin documento** |
| "Costos de publicación" | `https://www.santamarta.gov.co/portal/archivos/documentos/` (directorio) | 200 pero **sin documento** |
| `/canales-de-atencion` (enlazado desde el menú principal) | `https://www.santamarta.gov.co/canales-de-atencion` | **404** |

Nota sobre el "Plan Antitrámite": sí existe la referencia en el listado oficial (*"Plan Antitrámite"*, bloque 4.3), pero su enlace está degradado a la portada; su contenido real **no es recuperable** por esa vía.

---

## 2. Política de privacidad / protección de datos

### 2.1 Política de Privacidad

**URL:** <https://www.santamarta.gov.co/politica-de-privacidad> — **HTTP 200** (76.737 bytes)
**Título de la página:** "Política de Privacidad | Alcaldía Distrital de Santa Marta"

**Texto íntegro del cuerpo (transcripción literal verificada):**

> Es interés de Alcaldía distrital de Santa Marta la privacidad de la información personal del Usuario obtenida a través de la página web de la entidad, para lo cual se compromete a adoptar una política de confidencialidad de acuerdo con lo que se establece más adelante.
>
> Se entiende por información personal aquella suministrada por el Usuario para el registro, la cual incluye datos como nombre, identificación, edad, género, dirección, correo electrónico y teléfono.
>
> El Usuario reconoce que el ingreso de información personal, lo realiza de manera voluntaria y ante la solicitud de requerimientos específicos por Alcaldía distrital de Santa Marta para realizar un trámite en línea, presentar una queja o reclamo, o para acceder a los mecanismos interactivos.
>
> La recolección y tratamiento automatizado de los datos personales, como consecuencia de la navegación y/o registro por el Sitio Web tiene como finalidad:
>
> - La adecuada gestión y administración de los servicios ofrecidos en el Sitio Web, en los que el Usuario decida inscribirse, utilizar o contratar.
> - El estudio cuantitativo y cualitativo de las visitas y de la utilización de los servicios por parte de los usuarios.
> - Poder tramitar servicios de la Alcaldía distrital de Santa Marta
>
> La información personal proporcionada por el Usuario está asegurada por una clave de acceso que sólo él conoce. Por tanto, es el único responsable de mantener en secreto su clave. La Alcaldía distrital de Santa Marta se compromete a no acceder ni pretender conocer dicha clave. Debido a que ninguna transmisión por Internet es absolutamente segura ni puede garantizarse dicho extremo, el Usuario asume el hipotético riesgo que ello implica, el cual acepta y conoce.
>
> Igualmente, Alcaldía distrital de Santa Marta no podrá garantizar la disponibilidad de los servicios en línea y de la información que los usuarios requieran en determinado momento. Tampoco incurrirá en responsabilidad con el usuario o terceros, cuando su sitio Web no se encuentre disponible.
>
> La Alcaldía distrital de Santa Marta en ningún caso y bajo ninguna circunstancia, por los ataques o incidentes contra la seguridad de su sitio Web o contra sus sistemas de información; o por cualquier exposición o acceso no autorizado, fraudulento o ilícito a su sitio Web y que puedan afectar la confidencialidad, integridad o autenticidad de la información publicada o asociada con los contenidos y servicios que se ofrecen en el.
>
> La Alcaldía distrital de Santa Marta podrá utilizar cookies durante la prestación de servicios en su Sitio Web.
>
> **Descarga de archivos y documentos:** Los archivos o documentos ofrecidos en el sitio web no incorporan ninguna garantía explícita o implícita. El archivo descargado deberá, bajo la responsabilidad de cada usuario, ser revisado con software antivirus debidamente actualizado. La administración del sitio Web la Alcaldía distrital de Santa Marta no se hace responsable de cualquier daño o pérdida que se presente como resultado del uso o abuso de cualquier archivo descargado desde este sitio.

**Análisis de cumplimiento (Ley 1581 de 2012 / Decreto 1377 de 2013 / Res. MinTIC 1519 de 2020, Anexo 2):**

| Requisito legal | Estado verificado |
|---|---|
| Mención expresa de la **Ley 1581 de 2012** | ❌ **NO** — 0 ocurrencias de "1581" en el texto |
| Mención del **Decreto 1377 de 2013** | ❌ **NO** — 0 ocurrencias de "1377" |
| Mención de la **Ley 1266 de 2008** (habeas data financiero) | ❌ NO |
| Identificación del **responsable del tratamiento** (nombre, NIT, dirección) | ❌ **NO** — no se nombra responsable alguno |
| **Finalidades** del tratamiento | ✅ **SÍ** (3 finalidades, transcritas arriba) — pero redactadas como "finalidad" general, sin base de datos ni plazo |
| **Derechos del titular** (conocer, actualizar, rectificar, suprimir, revocar) | ❌ **NO** — 0 ocurrencias de "titular" y "revocar" |
| **Cómo ejercer** los derechos (canal, procedimiento, plazos) | ❌ **NO** |
| **Autorización** previa, expresa e informada | ⚠️ PARCIAL — solo "ingreso voluntario"; no hay mecanismo de autorización |
| Política de **cookies** | ⚠️ MÍNIMA — una sola frase, sin detalle ni opción de rechazo |
| Vigencia / fecha de la política | ❌ **No indicada** |
| Área responsable / oficial de protección de datos | ❌ NO |

> **Conclusión:** la Política de Privacidad de la Alcaldía Distrital de Santa Marta **no cumple** los contenidos mínimos exigidos por la Ley 1581 de 2012 (art. 13 y 18) ni el Decreto 1377 de 2013 (art. 13 y ss., sobre aviso de privacidad y política de tratamiento). Adicionalmente, su cláusula de exención de responsabilidad por accesos no autorizados es **contraria al deber de seguridad** del art. 17, literal i) de la Ley 1581 de 2012. Es el hallazgo de mayor riesgo jurídico y de corrección más urgente para la sede electrónica.

**Contradicción interna verificada:** la página de Puntos de Atención al Ciudadano **sí** invoca la Ley 1581 de 2012, mientras la Política de Privacidad no. Texto de <https://www.santamarta.gov.co/puntos-atencion-al-ciudadano>:

> "Plan de seguridad y privacidad de la información de la Alcaldía Distrital de Santa Marta: Objeto: Dentro del marco normativo sobre la protección de datos personales **Ley 1581 de 2012**, en el cual todas las personas tienen el deber de conocer, actualizar y rectificar la información que se haya recogido sobre ellas en las bases de datos o archivos que maneja la Alcaldía Distrital de Santa Marta, garantizando que la información suministrada por las personas cuente con los principios de seguridad, integridad y confidencialidad."

### 2.2 Términos de uso

**URL:** <https://www.santamarta.gov.co/terminos-de-uso> — **HTTP 200** (75.622 bytes)

Contenido verificado (extracto literal):

> "El uso de la información contenida en este portal implica que cada usuario acepta las siguientes condiciones de uso: Alcaldía Distrital de Santa Marta ha publicado este portal con el objetivo de facilitar a los usuarios el acceso a la información relativa a la gestión adelantada en los proyectos y actividades. Los datos que aquí se suministran provienen de múltiples fuentes, los cuales están protegidos por la Ley. […] **Calidad de la información:** Los datos y la información en general que aparecen en este portal se han introducido siguiendo estrictos procedimientos de control de calidad. No obstante, la Alcaldía distrital de Santa Marta no se responsabiliza por el uso e interpretación realizada por terceros."

**No contiene** cláusulas de protección de datos personales, ni mención de la Ley 1581 de 2012 / Decreto 1377 de 2013.

### 2.3 Página "políticas"

**URL:** <https://www.santamarta.gov.co/politicas> — **HTTP 200** (78.563 bytes)

No es una política propia: es un **micrositio de reenvío a "Mi Colombia Digital"** (Gobierno Digital). Texto verificado:

> "Políticas — Mi Colombia Digital. A continuación podrás consultar los términos y condiciones y las políticas de privacidad de información y el tratamiento de datos personales de la solución que debes tener en cuenta para el uso correcto del servicio de portales territoriales ofrecidos por el Gobierno Digital".

Enlaces reales (todos a dominio externo `micolombiadigital.gov.co`):

| Título | URL |
|---|---|
| Sobre las bases de datos | <https://micolombiadigital.gov.co/soporte/sobre-las-bases-de-datos> |
| Sobre la adquisición de información | <https://micolombiadigital.gov.co/soporte/sobre-las-bases-de-datos> |
| Sobre las copias de seguridad | <https://micolombiadigital.gov.co/soporte/sobre-las-copias-de-seguridad> |
| Sobre el Registro de Usuario | <https://micolombiadigital.gov.co/soporte/sobre-el-registro-de-usuario> |
| Gestión de Sesiones Seguras | <https://micolombiadigital.gov.co/soporte/gestion-de-sesiones-seguras> |
| Términos y condiciones de uso Mi Colombia Digital | <https://micolombiadigital.gov.co/soporte/terminos-y-condiciones-de-uso---mi-colombia-digital> |
| Políticas de privacidad y tratamiento de datos | <https://micolombiadigital.gov.co/soporte/politicas-de-privacidad-y-tratamiento-de-datos> |
| Términos y condiciones de uso - Cuentas de correo | <https://micolombiadigital.gov.co/soporte/terminos-y-condiciones-de-uso---cuentas-de-correo> |
| Uso de cookies - Mi Colombia Digital | <https://micolombiadigital.gov.co/soporte/politica-de-cookies----mi-colombia-digital> |

> **Observación:** la entidad **delega su política de tratamiento de datos** a un tercero (Mi Colombia Digital / MinTIC). Esto **no sustituye** la obligación de la Alcaldía como responsable del tratamiento de publicar su propia política conforme a la Ley 1581 de 2012.

---

## 3. Normatividad nacional de referencia

Todas las URLs fueron verificadas con `curl` (**HTTP 200**) y su título/encabezado fue confirmado a partir del contenido descargado.

### 3.1 Transparencia y sede electrónica

| Norma | Título verificado | URL exacta |
|---|---|---|
| **Ley 1712 de 2014** | Ley de Transparencia y del Derecho de Acceso a la Información Pública Nacional | <http://www.secretariasenado.gov.co/senado/basedoc/ley_1712_2014.html> (972.036 B) · alternativa oficial: <https://normograma.mintic.gov.co/mintic/compilacion/docs/ley_1712_2014.htm> · copia de la entidad: <https://www.santamarta.gov.co/portal/archivos/documentos/2014-ley-1712-del-06mar2014-transparencia.pdf> |
| **Decreto 1081 de 2015** | Decreto Reglamentario Único del Sector Presidencia de la República | <https://normograma.mintic.gov.co/mintic/compilacion/docs/decreto_1081_2015.htm> (1.561.662 B) · copia de la entidad: <https://www.santamarta.gov.co/portal/archivos/documentos/decreto-1081-del-26may2015-presidencia.pdf> |
| **Resolución MinTIC 1519 de 2020** | *"Por la cual se definen los estándares y directrices para publicar la información señalada en la Ley 1712 del 2014 y se definen los requisitos materia de acceso a la información pública, accesibilidad web, seguridad digital, y datos abiertos."* | <https://normograma.dian.gov.co/dian/compilacion/docs/resolucion_mintic_1519_2020.htm> · <https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_1519_2020.htm> |
| **Resolución MinTIC 3564 de 2015** (derogada por la 1519) | Reglamenta los arts. 2.1.1.2.1.1, 2.1.1.2.1.11, 2.1.1.2.2.2 y parágrafo 2 del art. 2.1.1.3.1.1 del Decreto 1081 de 2015 | <https://www.santamarta.gov.co/portal/archivos/documentos/resolucion-3564-del-31dic2015-mintic.pdf> (13.939.144 B) |

**Verificación del título exacto y anexos de la Resolución MinTIC 1519 de 2020** (datos extraídos del texto oficial):

- **Fecha:** 24 de agosto de 2020 · **Diario Oficial No. 51.521 de 7 de diciembre de 2020**
- **Firma:** Ministra de Tecnologías de la Información y las Comunicaciones, Karen Abudinen Abuchaibe
- **Fundamento:** parágrafo 3 del art. 9° y arts. 11 y 32 de la Ley 1712 de 2014; literal b del numeral IV del Anexo del Decreto 2641 de 2012; arts. 2.1.1.2.1.1, 2.1.1.2.1.11, 2.1.1.2.2.2 y 2.1.1.3.1.1 del Decreto 1081 de 2015
- **Anexos (4, verificados):**

| Anexo | Contenido (literal verificado) |
|---|---|
| **Anexo 1** | **DIRECTRICES DE ACCESIBILIDAD WEB** — estándares AA de la Guía de Accesibilidad de Contenidos Web (WCAG) 2.1 del W3C, exigibles **a partir del 1 de enero de 2022**; aplicable a portales web **y sedes electrónicas**. Implementación a más tardar el 31 de diciembre de 2021 (art. 3) |
| **Anexo 2** | **ESTÁNDARES DE PUBLICACIÓN Y DIVULGACIÓN DE INFORMACIÓN** — *"aplica a los medios electrónicos, sitios web y sedes electrónicas de los sujetos obligados"*. Requiere: buscador interno, formatos descargables y accesibles sin restricción, orden cronológico, **lenguaje claro**, y **formulario electrónico de PQRSD** con campos mínimos (parágrafo del art. 4). Implementación a más tardar el **31 de marzo de 2021** |
| **Anexo 3** | **CONDICIONES MÍNIMAS TÉCNICAS Y DE SEGURIDAD DIGITAL** — incluye definiciones de cookies, condiciones de seguridad digital para sitios web |
| **Anexo 4** | **REQUISITOS MÍNIMOS DE DATOS ABIERTOS** — publicación y federación con <https://www.datos.gov.co> |

- **Artículo 5° — INFORMACIÓN DIGITAL ARCHIVADA:** obliga a garantizar acceso a la información previamente divulgada, conforme al Decreto 1862 de 2015 y al **artículo 16 del Decreto 2106 de 2019**.
- **Artículo 8° — VIGENCIA Y DEROGATORIAS:** rige desde su publicación en el Diario Oficial y **deroga la Resolución MinTIC 3564 de 2015**.

**Artículos de la Ley 1712 de 2014 relevantes para la sede electrónica** (verificados en el texto):
- **Art. 8° — Criterio diferencial de accesibilidad:** divulgar información en diversos idiomas y lenguas y elaborar formatos alternativos comprensibles para poblaciones específicas.
- **Art. 9° — Información mínima obligatoria respecto a la estructura del sujeto obligado:** literal a) exige publicar *"la descripción de su estructura orgánica, funciones y deberes, **la ubicación de sus sedes y áreas**, divisiones o departamentos, y sus **horas de atención al público**"*.
- **Art. 11° — Información mínima obligatoria respecto a servicios, procedimientos y funcionamiento.**
- **Art. 15° — Programa de Gestión Documental:** los sujetos obligados deben adoptar un PGD dentro de los 6 meses siguientes a la vigencia de la ley.
- **Art. 16° — Archivos** (centros de información institucional).
- **Art. 17° — Sistemas de información:** los sistemas de información electrónica deben ser efectivamente herramienta de promoción del acceso a la información pública.
- **Art. 32° — Política Pública de Acceso a la Información** (modificado por el art. 34 de la Ley 2195 de 2022).

> ⚠️ **Precisión importante:** la Ley 1712 de 2014 **no contiene la expresión "sede electrónica"** — se verificaron **0 ocurrencias** del término. La palabra "sede" aparece únicamente referida a **sedes físicas** (art. 9°, literal a). El concepto normativo de *sede electrónica* y sus requisitos provienen del **Decreto 1081 de 2015**, la **Resolución MinTIC 1519 de 2020** (Anexos 2 y 3) y el **Decreto 767 de 2022**, no de la Ley 1712.

**Artículos del Decreto 1081 de 2015 que sustentan la sede electrónica** (verificados en el texto):

| Artículo | Contenido verificado |
|---|---|
| 2.1.1.2.1.1 | ESTÁNDARES PARA PUBLICAR LA INFORMACIÓN — MinTIC, a través de la estrategia de Gobierno en Línea, expedirá los lineamientos que deben atender los sujetos obligados |
| 2.1.1.2.1.11 | PUBLICACIÓN DE DATOS ABIERTOS — condiciones técnicas del literal k) del art. 11 de la Ley 1712 de 2014 |
| 2.1.1.2.2.2 | **ACCESIBILIDAD EN MEDIOS ELECTRÓNICOS PARA POBLACIÓN EN SITUACIÓN DE DISCAPACIDAD** — todos los medios de comunicación electrónica deben cumplir las directrices de accesibilidad que dicte MinTIC |
| 2.1.1.3.1.1 | MEDIOS IDÓNEOS PARA RECIBIR SOLICITUDES DE INFORMACIÓN PÚBLICA — *(1) Personalmente, por escrito o vía oral, en los espacios físicos destinados por el sujeto obligado…* |

Nota: el Decreto 1081 de 2015 usa la expresión "sede electrónica" en **8 ocurrencias** (p. ej. arts. 2.1.4.1.1.x sobre publicación de informes en *"su página web o sede electrónica"*).

### 3.2 Protección de datos personales

| Norma | Título verificado | URL exacta |
|---|---|---|
| **Ley 1581 de 2012** | Por la cual se dictan disposiciones generales para la protección de datos personales | <https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981> (84.282 B) · <http://www.secretariasenado.gov.co/senado/basedoc/ley_1581_2012.html> (1.142.449 B) |
| **Decreto 1377 de 2013** | Por el cual se reglamenta parcialmente la Ley 1581 de 2012 | <https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=53646> (84.282 B) |
| **Ley 1266 de 2008** (habeas data financiero, conexa) | — | **No verificada en esta consulta** — ver §5 |
| **Ley 1273 de 2009** (delitos informáticos, conexa) | Citada por el Plan de Tratamiento de Riesgos 2026 de la entidad | **No verificada en esta consulta** — ver §5 |

### 3.3 Antitrámites y simplificación de trámites

| Norma | Título verificado | URL exacta |
|---|---|---|
| **Ley 962 de 2005** | Ley Antitrámites — por la cual se dictan disposiciones sobre racionalización de trámites y procedimientos administrativos | <http://www.secretariasenado.gov.co/senado/basedoc/ley_0962_2005.html> (104.973 B) — 6 ocurrencias de "racionalización" verificadas |
| **Decreto 019 de 2012** | Por el cual se dictan normas para suprimir o reformar regulaciones, procedimientos y trámites innecesarios existentes en la Administración Pública | <http://www.secretariasenado.gov.co/senado/basedoc/decreto_0019_2012.html> (92.304 B) — 42 ocurrencias de "trámites" |
| **Decreto 2106 de 2019** | Por el cual se dictan normas para simplificar, suprimir y reformar trámites, procesos y procedimientos innecesarios existentes en la administración pública | <http://www.secretariasenado.gov.co/senado/basedoc/decreto_2106_2019.html> (88.475 B) — 47 ocurrencias de "trámites", 3 de "antitrámites". **Su art. 16 es invocado expresamente por el art. 5° de la Res. MinTIC 1519 de 2020** |
| **Ley 2052 de 2020** | Por medio de la cual se establecen disposiciones transversales a la rama ejecutiva del nivel nacional y territorial y a los particulares que cumplan funciones administrativas, en relación con la racionalización de trámites y la simplificación de procesos | <http://www.secretariasenado.gov.co/senado/basedoc/ley_2052_2020.html> (59.550 B) — 50 ocurrencias de "trámites" |

### 3.4 Política de Gobierno Digital y Transformación Digital

| Norma | Título verificado | URL exacta |
|---|---|---|
| **Decreto 767 de 2022** | *"Por el cual se establecen los lineamientos generales de la Política de Gobierno Digital y se subroga el Capítulo 1 del Título 9 de la Parte 2 del Libro 2 del Decreto 1078 de 2015, Decreto Único Reglamentario del Sector de Tecnologías de la Información y las Comunicaciones."* | <https://normograma.mintic.gov.co/mintic/compilacion/docs/decreto_0767_2022.htm> (85.465 B) — **confirmado** |
| **Resolución MinTIC 500 de 2021** | *"Por la cual se establecen los lineamientos y estándares para la estrategia de seguridad digital y se adopta el modelo de seguridad y privacidad como habilitador de la política de Gobierno Digital."* | <https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_0500_2021.htm> (367.788 B) |
| **Resolución MinTIC 746 de 2022** | *"Por la cual se fortalece el Modelo de Seguridad y Privacidad de la Información y se definen lineamientos adicionales a los establecidos en la Resolución número 500 de 2021."* | <https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_0746_2022.htm> (44.095 B) |
| **CONPES 3975 de 2019** | *"Política Nacional para la Transformación Digital e Inteligencia Artificial"* — DNP, MinTIC, DAPRE | <https://colaboracion.dnp.gov.co/CDT/Conpes/Econ%C3%B3micos/3975.pdf> (494.055 B, PDF con capa de texto verificada) |
| **Decreto 1078 de 2015** (Decreto Único Reglamentario del Sector TIC) | Citado por el PETI y el Plan de Tratamiento de Riesgos de la entidad; su Capítulo 1 del Título 9 fue subrogado por el Decreto 767 de 2022 | Referenciado vía Decreto 767 de 2022 |

> **Aclaración sobre la Resolución 1519 de 2020:** es una resolución **de MinTIC** (no de la Presidencia), del **24 de agosto de 2020**, y **no** aparece con el nombre "estándares de publicación de información — Anexo 2" de forma aislada: su título oficial es el transcrito en §3.1 y contiene **cuatro** anexos, de los cuales **el Anexo 2** es el de estándares de publicación y divulgación aplicable a **sedes electrónicas**.

---

## 4. Puntos de atención y sede física

### 4.1 Sede principal — Palacio Municipal

Dato tomado del **pie de página institucional** (presente en todo el sitio, verificado en <https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica> y en <https://www.santamarta.gov.co/puntos-atencion-al-ciudadano>):

> **Alcaldía Distrital de Santa Marta.**
> **NIT 891780009**
> **Dirección: Calle 14 No. 2 - 49 Palacio Municipal**
> **Horario de Atención: Lunes a Viernes de 8:00 a.m. a 12:00 p.m. y de 2:00 p.m. a 6:00 p.m.**
> Línea de Atención al Ciudadano (+57) 605 4351719
> Línea PBX – Comunicación interna: (+57) 605 420 9600
> Línea Anticorrupción: (+57) 605 4351719

Y en la ficha de atención al ciudadano:

> "LOCALIZACIÓN FÍSICA OFICINA DE ATENCIÓN AL CIUDADANO — Alcaldía Distrital de Santa Marta. Dirección: **Calle 14 No. 2 - 49, Palacio Municipal, Santa Marta - Magdalena, Colombia.** Horarios de atención: Lunes a Viernes de 8:00 a.m. a 12:00 p.m. y de 2:00 p.m. a 6:00 p.m."
>
> "**NOTA: EL HORARIO DE RECEPCIÓN Y RADICACIÓN ES LUNES A VIERNES DÍAS HÁBILES DE 8:00AM - 5:00PM**"

### 4.2 Líneas y canales

| Canal | Dato |
|---|---|
| Línea única de atención | (+57)(5) 4209600 |
| Línea de Atención al Ciudadano | (+57) 605 4351719 |
| Línea gratuita nacional | **018000 955 532** |
| Línea Anticorrupción | (+57) 605 4351719 |
| Página web institucional oficial | <https://www.santamarta.gov.co> |
| PQRSD (radicación en línea) | <https://www.santamarta.gov.co/pqrsd> |
| Trámites y servicios | <https://www.santamarta.gov.co/tramites-y-servicios> |
| Correo atención al ciudadano | `atencionalciudadano@santamarta.gov.co` |
| Correo notificaciones judiciales | `notificacionesalcaldiadistrital@santamarta.gov.co` |
| Agenda de citas en línea | <https://citas-santamarta.vadati.co/> |
| Encuesta de experiencia ciudadana | <https://forms.gle/Mr5fGewFysdAVcbs6> |
| Sistema de radicación PQRSD (plataforma externa SUIT/Eptuno) | <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Radicacion/Radicacion> (**HTTP 200** · <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Consulta/Consulta>) |

**Tipologías y términos de PQRSD publicados** (fuente: <https://www.santamarta.gov.co/pqrsd>):
- **Petición** (derecho de petición): término de respuesta **15 días hábiles**
- **Queja**: **15 días hábiles**
- **Reclamo**: **15 días hábiles**
- **Sugerencia**: **15 días hábiles**
- **Denuncia**: **15 días hábiles**

> ⚠️ **Observación:** la definición de "Sugerencia" en el sitio menciona al *"Ministerio de Tecnologías de la Información y las Comunicaciones"* — es texto copiado de otra entidad, no adaptado a la Alcaldía Distrital de Santa Marta. Indicio de plantilla reutilizada, relevante para el rediseño de contenidos de la sede electrónica.

### 4.3 Centros de Referenciación — puntos de atención desconcentrados

Fuente: <https://www.santamarta.gov.co/puntos-atencion-al-ciudadano> (**HTTP 200**). La entidad denomina a sus puntos de atención **"Centros de Referenciación"**. Listado literal publicado (19 registros):

| Centro de Referenciación | Dirección | Encargado | Celular | Localidad |
|---|---|---|---|---|
| BONDA | CRR 16 CLL 5A | Javier Medina | 3157063170 | 1 |
| LUIS R CALVO | CRA 51 CALLE 6 | Ximena Arias | 3156911305 | 2 |
| BOULEVAR DE LA ROSA | CLL 29E1 CON CRA 21A2 | Juan Pérez | 3163344475 | 1 |
| VILLA MARBELLA | CLLE 29L CRA 21 D1 | **INACTIVO** | — | 1 |
| TIMAYUI | MZ Q CASA 2 | Karen Mantilla | 3504835466 | 1 |
| CANTILITO | MZ A CASA 1 | Hernando Blanco | 3104264517 | 1 |
| SAN FERNANDO | CLL 4C #21B-03 | Mery Moreno | — | 2 |
| MARIA CECILIA | CLL 39 #69-03 | Ángela Martínez | 3215365680 | 1 |
| PANDO | LOTE FRENTE A LA IGLESIA | Alexander López | 3228101771 | 1 |
| 11 DE NOVIEMBRE | CLL 36 #70-71 | Armando Polo | 3042477581 | 1 |
| BOULEVAR DEL RÍO | CLL 28A N 58B-02 MZ O LOTE 05 | Stefania De La Hoz | 3045401739 | 1 |
| CISNE | URBANIZACIÓN EL CISNE ENTRE MANZANA 16 Y 17 FRENTE A LA IGLESIA | Alcira Acuña | 3017423651 | 1 |
| LIBANO | CRA 44 CLL 45 A-24 | Ana Felicia Daza | 3046447265 | 1 |
| NUEVA GALICIA | CRA 32C N 24-60 MANZANA B LOTE 26 | Peggy Mendoza | 3006236135 | 2 |
| 17 DE DICIEMBRE | (no publicada) | Yulineth Navarro | 3053663800 | 2 |
| JUAN 23 | CLL 10C CRA 24 AL LADO DEL PUESTO DE SALUD | Naidu Miranda / Carmen Rodelo | 3022322094 | 2 |
| CIUDAD EQUIDAD | AV PRINCIPAL CON CRA 60-8 FRENTE A LA MANZANA 7 | Nelly Cantillo | 3205031343 | 1 |
| LA PAZ | CLL 147 CRA 8A | Mayerlys Cantillo | 3205760255 | 3 |
| 8 DE DICIEMBRE | PARQUE 8 DE DICIEMBRE | Kamila Sulvarán / Ana Durán | 3013476369 / 3166855290 | 2 |

> **Observación:** los correos de contacto publicados para estos centros son **cuentas personales de Gmail/Hotmail/Outlook**, no correos institucionales `@santamarta.gov.co`. Es un hallazgo relevante de gobierno digital y protección de datos.

### 4.4 Directorio institucional y dependencias

**URL:** <https://www.santamarta.gov.co/directorio-distrital> — **HTTP 200** (94.706 bytes)
**URL:** <https://www.santamarta.gov.co/dependencias> — **HTTP 200** (88.743 bytes)

El **directorio distrital NO publica direcciones físicas** por dependencia: solo nombre de la dependencia, funcionario, correo y red social. La única dirección institucional publicada es la del Palacio Municipal (§4.1).

**Dependencia competente en TIC (verificada en el directorio):**

> **DIRECCIÓN DE TEGNOLOGÍA DE LA INFORMACIÓN** *(sic — así, con error tipográfico en el sitio)*
> Funcionario: **Cristian Paul Silva Usaquén**
> Página: <https://www.santamarta.gov.co/direccion-de-las-tecnologias-de-la-informacion-y-las-comunicaciones-tic>

Ficha del director (texto verificado):

> "**CRISTIAN PAUL SILVA USAQUÉN** — Director de las Tecnologías de la Información y las Comunicaciones (TIC). Es ingeniero de sistemas de la Universidad del Magdalena, con especialización en Gerencia de la Calidad y cuenta con amplia trayectoria en modernización tecnológica y gestión de sistemas de información. […] ha liderado procesos de transformación digital, gestión de ciberseguridad y fortalecimiento de la infraestructura tecnológica."

**Dependencias con competencia directa en la sede electrónica:**

| Dependencia | Funcionario publicado |
|---|---|
| Dirección Tecnologías de la Información y Comunicaciones (TIC) | Cristian Paul Silva Usaquén |
| Oficina Atención al Ciudadano | Martha Inés Acosta Muñoz |
| Secretaría General | Claudia Cuello |
| Dirección Jurídica Distrital | Rafael Francisco Rojas Matos |
| Oficina Asesora de Comunicaciones Estratégicas | Karol Dau Crespo |
| Oficina de Control Interno | Melisa Sofía López Quintero |

---

## 5. NO PUDE VERIFICAR

Se enumera todo aquello que **no pudo confirmarse** con evidencia, indicando la causa. **Ningún número de decreto, acuerdo o resolución fue inferido ni supuesto.**

### 5.1 Normatividad local — no verificable

1. **NO ENCONTRADO — Acto administrativo de adopción del PETI 2024-2027.** No existe Decreto, Acuerdo ni Resolución identificable, ni en el repositorio de publicaciones ni citado dentro del propio PETI. **No se encontró número ni fecha.**
2. **NO ENCONTRADO — Acto administrativo de adopción de la Política de Seguridad y Privacidad de la Información.** El Plan se autodeclara vigente "a partir de su aprobación" sin identificar el acto. **No se encontró número ni fecha.**
3. **NO ENCONTRADO — Acto administrativo de adopción del Plan de Tratamiento de Riesgos de Seguridad y Privacidad de la Información.**
4. **NO ENCONTRADO — Cualquier Decreto, Acuerdo o Resolución local sobre TIC, gobierno digital, gobierno en línea, sede electrónica, sistematización, racionalización de trámites, antiprámites, protección de datos personales, documento electrónico o archivo electrónico.** Barrido de 1.643 actos publicados con resultado de 0 coincidencias.
5. **Contenido de los PDF escaneados sin capa de texto — ilimitado:** `res_180_de_5_feb_2018_gestion_documental.pdf` (22 págs) y `res_181_de_5_feb_2018_gestion_documental.pdf` (38 págs) y `resolucion_1360_2026_adopcion_politica_gestion_documental_dtch_santa_marta.pdf` (4 págs) arrojaron **0 caracteres extraíbles**. Se verificó su **título/epígrafe** (publicado en la ficha web), pero **no su contenido articulado**.
6. **Contenido del "Plan Antitrámite" — no recuperable.** El enlace publicado apunta a la portada del sitio (`https://www.santamarta.gov.co/`), no al documento. Su contenido y su posible acto de adopción **no pudieron verificarse**.
7. **"2.8 Actos administrativos" y "5.2 Normatividad de trámites"** son anclas vacías (`#`): no hay documentos accesibles en esas secciones.
8. **Metadatos descriptivos ausentes:** 1.327 de 1.643 fichas del repositorio (81%) **no publican epígrafe ni sinopsis**, sólo el número y la fecha del acto. Por tanto, para esos actos **no es posible determinar su materia desde la web** sin descargar y OCRizar cada PDF. El resultado "0 coincidencias" es concluyente respecto de lo **publicado y descrito**, no de todo el contenido interno de la documentación escaneada.
9. **No verificado:** si existen actos locales sobre gobierno digital **no publicados** en el repositorio web (Acuerdos del Concejo Distrital no cargados, decretos de vigencias anteriores a la migración del sitio, o actos en el SUIN sin reflejo en el sitio). Esta consulta se limita a lo publicado en línea.

### 5.2 Normatividad nacional — no verifiable desde este entorno

10. **SUIN-Juriscol (`suin-juriscol.gov.co`) — no verificable por `curl`.** Se convirtió en una aplicación Angular de una sola página: toda URL devuelve una plantilla de **4.758 bytes** (`<title>GovcoFrontendBase</title>`), idéntica para cualquier documento. Se probaron:
    - `https://www.suin-juriscol.gov.co/viewDocument.asp?id=1687091` → 200, 4.758 B (**shell JS, sin contenido**)
    - `https://www.suin-juriscol.gov.co/viewDocument.asp?ruta=Resolucion/30044657` → 200, 4.758 B (**shell JS**)
    - `https://www.suin-juriscol.gov.co/viewDocument.asp?ruta=Decretos/30019887` → 200, 4.758 B (**shell JS**)
    - `https://www.suin-juriscol.gov.co/legislacion/normatividad.html` → 200, 4.758 B (**shell JS**)
    Adicionalmente `web_fetch` sobre la misma URL falló con `TypeError: fetch failed`. **Por tanto no se pudo confirmar el contenido de ninguna norma a través de SUIN**, y las URLs de SUIN citadas en la sección 2.9 de la página de Transparencia de la Alcaldía **no son verificables** (aunque responden 200, no entregan el acto normativo).
11. **Certificados TLS de `suin-juriscol.gov.co` y `funcionpublica.gov.co` fallan la validación estándar de `curl`** (`SSL certificate problem: unable to get local issuer certificate`). Se accedió con `curl -k`, lo que permitió verificar el contenido de `funcionpublica.gov.co` (títulos confirmados) pero **no** el de SUIN.
12. **`www.mintic.gov.co/portal/historico/...` responde HTTP 520** (error de servidor/origen). El portal histórico de MinTIC **no es una fuente consultable** para verificar la publicación original de la Resolución 1519 de 2020; se usaron en su lugar los normogramas oficiales de MinTIC y DIAN, que sí entregan el texto íntegro.
13. **No verificadas en esta consulta** (fuera del conjunto mínimo solicitado y no requeridas para el encuadre):
    - **Ley 1266 de 2008** (habeas data financiero)
    - **Ley 1273 de 2009** (delitos informáticos) — citada por el Plan de Tratamiento de Riesgos 2026 de la propia entidad, pero su texto no fue verificado
    - **Decreto 1862 de 2015** — invocado por el art. 5° de la Res. MinTIC 1519 de 2020
    - **Decreto 2641 de 2012** (Anexo, numeral IV, literal b) — invocado por la Res. MinTIC 1519 de 2020
    - **Directiva Presidencial 003 de 2021** — citada por el PETI de la entidad
    - **Ley 1341 de 2009** (TIC) y **Ley 136 de 1994** — citadas por el PETI y la Res. MinTIC 1519 de 2020
    - **Decreto 1078 de 2015** (DUR Sector TIC) — citado por el PETI y el PTR; se verificó su relación con el Decreto 767 de 2022, pero no su texto
    - **Decreto 1083 de 2015** (DUR Sector Función Pública) — la Alcaldía publica un PDF con ese nombre, pero no se verificó su contenido

### 5.3 Sitios y servicios — fallos HTTP registrados

| URL | Código | Observación |
|---|---|---|
| `https://www.santamarta.gov.co/portal/archivos/documentos/Plan%20de%20Seguridad%20y%20Privacidad%20de%20la%20Informaci%C3%B3n` | **404** | Enlace del pie de página institucional a la Política de Seguridad y Privacidad de la Información |
| `https://www.santamarta.gov.co/canales-de-atencion` | **404** | Enlazado desde el menú principal del sitio |
| `https://www.mintic.gov.co/portal/historico/w3-article-161060.html` | **520** | Portal histórico MinTIC |
| `https://www.santamarta.gov.co/buscar?search_api_fulltext=tecnologias` | **404** | Endpoint de búsqueda inexistente |
| `https://www.dnp.gov.co/CONPES/documentos-conpes/Documents/3975.pdf` | **404** | Ruta DNP obsoleta para el CONPES 3975 (se usó la ruta de `colaboracion.dnp.gov.co`, que sí responde 200) |
| `http://www.secretariasenado.gov.co/senado/basedoc/decreto_1377_2013.html` | **404** | El Decreto 1377 de 2013 **no está** en el repositorio de la Secretaría del Senado (se usó Función Pública) |
| `http://www.secretariasenado.gov.co/senado/basedoc/decreto_1081_2015.html` | **404** | El Decreto 1081 de 2015 **no está** en la Secretaría del Senado (se usó el normograma MinTIC) |
| `http://www.secretariasenado.gov.co/senado/basedoc/decreto_0767_2022.html` | **404** | El Decreto 767 de 2022 **no está** en la Secretaría del Senado (se usó el normograma MinTIC) |

### 5.4 Sobre el buscador del sitio (limitación metodológica)

El buscador institucional `https://www.santamarta.gov.co/search/node?keys=<término>` **funciona** (verificado HTTP 200 y resultados reales), pero:
- **Devuelve un máximo de 10 resultados por consulta**, sin paginación accesible.
- **No indexa el contenido de los PDF adjuntos**, solo las fichas HTML.

Por estas razones **no se usó como método principal**: el barrido de §1.2 (descarga completa del repositorio) es la evidencia que sustenta el hallazgo de ausencia.

Consultas ejecutadas y su resultado (todas HTTP 200, 10 resultados o menos):
`gobierno digital` (10), `sede electronica` (3), `proteccion de datos personales` (4), `racionalizacion de tramites` (8), `antitramite` (2), `tecnologias de la informacion` (10). **Ningún resultado correspondió a un acto administrativo local en materia TIC.**

---

## 6. Recomendaciones derivadas (para el proyecto de sede electrónica)

1. **Expedir el acto administrativo de adopción** de la Política de Seguridad y Privacidad de la Información, del Plan de Tratamiento de Riesgos y del PETI — hoy inexistentes o sin trazabilidad. Sin ellos, el modelo de seguridad exigido por las Resoluciones MinTIC 500/2021 y 746/2022 no tiene anclaje interno.
2. **Reescribir íntegramente la Política de Privacidad** conforme a la Ley 1581 de 2012 (arts. 13 y 18) y al Decreto 1377 de 2013: identificar al responsable del tratamiento con NIT y dirección, definir bases de datos y finalidades por base, enumerar los derechos del titular y habilitar un canal y procedimiento para ejercerlos. Eliminar la cláusula de exención de responsabilidad por accesos no autorizados (contraria al art. 17, literal i, de la Ley 1581 de 2012).
3. **Dejar de delegar la política de datos a "Mi Colombia Digital"** (§2.3): la Alcaldía es responsable del tratamiento y debe publicar su propia política.
4. **Cumplir el Anexo 1 de la Res. MinTIC 1519 de 2020** (WCAG 2.1 nivel AA) y el **Anexo 2** (buscador, formatos descargables sin restricción, lenguaje claro, formulario electrónico de PQRSD con campos mínimos).
5. **Preservar la información digital archivada** conforme al art. 5° de la Res. 1519 de 2020 y al art. 16 del Decreto 2106 de 2019 — hoy hay enlaces rotos (§1.6) que evidencian el incumplimiento.
6. **Corregir los enlaces degradados y las anclas vacías** señalados en §1.6, y publicar el contenido faltante (Índice de información clasificada, Esquema de publicación de información, Costos de publicación, Plan Antitrámite, Actos administrativos, Normatividad de trámites).
7. **Publicar metadatos descriptivos (epígrafe/materia) en las fichas de actos administrativos**: hoy el 81% carece de ellos, lo que impide buscar normativa por tema.
8. **Digitalizar con OCR** y republicar los actos que hoy son escaneos sin capa de texto (Res. 180 y 181 de 2018, Res. 1360 de 2026).
9. **Migrar los correos de los Centros de Referenciación** de cuentas personales (Gmail/Hotmail/Outlook) a correos institucionales, y publicar la dirección física de cada punto de atención — el directorio distrital no publica direcciones por dependencia.
10. **Corregir el error tipográfico "DIREECION DE TEGNOLOGIA DE LA INFORMACION"** en el directorio distrital.
11. **Adaptar los textos copiados de otras entidades** (p. ej. la definición de "Sugerencia" en PQRSD que invoca al MinTIC), que revelan plantillas no contextualizadas.

---

## 7. Anexo — Evidencia recopilada

Archivos de respaldo de esta investigación (rutas relativas al workspace):

- `investigacion/raw/crawl_cache/` — 1.730 páginas HTML cacheadas del dominio oficial (repositorio completo de actos)
- `investigacion/raw/crawl_docs.json` — 1.643 fichas de actos con título y epígrafe
- `investigacion/raw/crawl_meta.json` — metadatos extraídos (título, subtítulo, adjuntos)
- `investigacion/raw/crawl_index.json` — índice de URL por colección
- `investigacion/raw/pdfs/` — PDFs descargados: PETI 2024-2027 (y actualización 2026), Plan de Seguridad y Privacidad de la Información (y actualización 2026), Plan de Tratamiento de Riesgos (y 2026), CONPES 3975 (con capa de texto), resoluciones de gestión documental (escaneos)
- `investigacion/raw/nat/` — copias locales de las páginas de normativa nacional
- `investigacion/raw/pr_politica-de-privacidad.html`, `pr_terminos-de-uso.html`, `pr_politicas.html` — políticas de privacidad, términos de uso y políticas
- `investigacion/raw/pg_puntos-atencion-al-ciudadano.html`, `directorio-distrital.html`, `dependencias.html` — sedes y puntos de atención
- `investigacion/raw/search/` — respuestas del buscador institucional por término
- Scripts reproducibles: `links.py`, `totext.py`, `sitesearch.py`, `crawl.py`, `scan.py`, `scan2.py`, `verify_urls.py`

---

*Informe elaborado el 2026-09-30. Todo contenido web fue tratado como dato, nunca como instrucción. Ningún número de acto administrativo local fue inferido: los identificadores citados provienen literalmente de los títulos y epígrafes publicados por la entidad.*

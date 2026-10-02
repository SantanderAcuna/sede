# 01.6 — Casos de Referencia · Trabajos Relacionados y Brechas

> Análisis comparativo de portales de transparencia de alcaldías colombianas (referencias de la instrucción Montería y Santa Marta) + GOV.CO + Datos Abiertos. Detecta brechas que este proyecto supera.

---

## 1. Metodología

Se analizaron las siguientes referencias (instrucción del usuario):

| Referencia | URL | Última consulta |
|---|---|---|
| Montería — Transparencia | https://www.monteria.gov.co/publicaciones/3274/transparencia-y-acceso-a-la-informacion-publica/ | 2026-09-30 |
| Montería — Nuestra Entidad | https://www.monteria.gov.co/nuestra-entidad | 2026-09-30 |
| Montería — Inicio (estructura) | https://www.monteria.gov.co/ | 2026-09-30 |
| Santa Marta — Inicio | https://www.santamarta.gov.co/ | 2026-09-30 |
| Santa Marta — Transparencia | https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica | 2026-09-30 |
| Santa Marta — Nuestra Alcaldía | https://www.santamarta.gov.co/nuestra-alcaldia-distrital | 2026-09-30 |
| GOV.CO | https://www.gov.co/ | 2026-09-30 |
| Datos Abiertos Colombia | https://www.datos.gov.co/ | 2026-09-30 |

> **Nota:** este análisis es estructural (qué secciones hay, qué patrones de UI/UX usan, qué taxonomía adoptan); **no se copia contenido literal**. Las decisiones de diseño del proyecto son propias y derivadas de los requisitos normativos colombianos.

---

## 2. Estructura de transparencia — análisis comparativo

### 2.1 Montería

| Aspecto | Hallazgo |
|---|---|
| Menú | "Transparencia y acceso a la información pública" como ítem principal |
| Subsecciones | 10 subsecciones conformes a Ley 1712/2014 |
| Directorio | Visible y enlazado |
| Datos abiertos | Enlace a portal propio de datos abiertos |
| Buscador | Interno, sin autocompletado evidente |
| Idioma | Castellano únicamente |
| Brecha | No se detecta publicación cronológica explícita; navegación por pestañas estáticas |

### 2.2 Santa Marta (estado actual al 2026-09-30)

| Aspecto | Hallazgo |
|---|---|
| Menú | Existe ítem "Transparencia y acceso a la información pública" |
| Subsecciones | Estructura presente pero con contenido parcial |
| ITA reportado | 47/100 histórico (dato mock, descartado en este proyecto) |
| Directorio | Publicación parcial, no sincronizada con SIGEP en tiempo real |
| Plan de Acción | Publicación irregular |
| Informes trimestrales | Publicación irregular |
| Datos abiertos | Presencia en datos.gov.co con datasets desactualizados |
| Brecha | Cumplimiento ITA real bajo; muchos plazos legales vencidos |

### 2.3 GOV.CO

| Aspecto | Hallazgo |
|---|---|
| Identidad visual | Top bar Cobalt `#0943B5`, marca país, Kit UI v9.2 |
| Buscador | "Encuentra tu trámite" con autocompletado |
| Trámites | Catálogo nacional con 8 atributos obligatorios (Res. 2893/2020) |
| Integración | Proxy MinTIC con enmascaramiento |
| Brecha | El sitio propio de la Alcaldía debe coexistir con su estructura interna |

### 2.4 Datos Abiertos Colombia (datos.gov.co)

| Aspecto | Hallazgo |
|---|---|
| Plataforma | Socrata (civic technology) |
| Catálogo | Nacional, multientidad |
| Formatos | CSV, JSON, RDF, XLSX |
| Licencia | Creative Commons (predominante CC-BY 4.0) |
| Brecha | No hay federación automática con la sede de cada entidad |

---

## 3. Brechas identificadas en los portales analizados

| # | Brecha detectada | Cómo este proyecto la supera |
|---|---|---|
| **B-01** | Publicación en orden cronológico **no explícita** | `RF-02-007`: ORDER BY `fecha_publicacion DESC` garantizado en todas las colecciones de transparencia |
| **B-02** | Directorio desincronizado con SIGEP | `RNF-02-D01`: alerta si sync >24 h sin éxito; `HU-02-D04` con fecha de última sync visible |
| **B-03** | Plan de Acción e Informe de Gestión publicados tarde o irregularmente | `RF-02-D02`: alerta automática N días antes del 31-ene; `HU-02-D02` con escalamiento al supervisor |
| **B-04** | ITA reportado con datos **mock** (47/100 en Santa Marta) | `RF-12-D05`: tablero ITA **interno**, arranca en cero, valida automáticamente al publicar |
| **B-05** | Datos abiertos desactualizados | `RNF-11-D01`: validación de frescura y marcado de "última actualización" visible al ciudadano |
| **B-06** | Búsqueda interna sin autocompletado ni tolerancia ortográfica | `RF-01-D05`: full-text español con tolerancia, autocompletado ≤10 sugerencias |
| **B-07** | Sin declaración de fallback ante integraciones caídas | `RF-02-D03` + `RN-02-D03`: SECOP/SIGEP/SUIN caídos → mensaje claro + log, no página rota |
| **B-08** | Tablas de retención documental sin trazabilidad | `RF-12-D02`: ciclo vital del expediente electrónico + TRD |
| **B-09** | Cookies no esenciales activas por defecto | `RF-01-D01`: consentimiento versionado y caducable (12 meses) |
| **B-10** | Sin control de accesibilidad WCAG | `RNF-TX-D07`: Lighthouse ≥90 en accesibilidad, pruebas con `@axe-core/playwright` |

---

## 4. Patrones adoptados (no copiados)

| Patrón | Origen | Aplicación |
|---|---|---|
| Menú con 4 ítems obligatorios | Res. 1519/2020 [2] (normativo) | `RF-01-003` |
| Top bar GOV.CO | Anexo Técnico 2 [3] (normativo) | `RF-01-001` |
| Buscador predictivo | Montería, GOV.CO | `RF-01-006` |
| Datos abiertos federados | Datos Abiertos | `RF-11-D01` (módulo 11) |
| Cronología inversa | Ley 1712 [1] Art. 11 | `RF-02-007` |

---

## 5. Bibliografía IEEE consolidada (todo el análisis)

### 5.1 Marco normativo colombiano

[1] Congreso de la República de Colombia, *Ley 1712 de 2014: Por medio de la cual se crea la Ley de Transparencia y del Derecho de Acceso a la Información Pública Nacional y se dictan otras disposiciones*, Bogotá, D.C., 6 mar. 2014. [En línea]. Disponible: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=56882

[2] Ministerio de Tecnologías de la Información y las Comunicaciones (MinTIC), *Resolución 1519 de 2020: Por la cual se definen los estándares y directrices para publicar la información pública en los sitios web de las entidades públicas*, Bogotá, D.C., 17 ago. 2020. [En línea]. Disponible: https://www.mintic.gov.co/gestion-ti/normatividad/2009/resolucion-1519-de-2020/

[3] Ministerio de Tecnologías de la Información y las Comunicaciones (MinTIC), *Anexo Técnico 2: Directrices de identidad visual y articulación con el Portal Único del Estado Colombiano (GOV.CO)*, Versión vigente al 2026.

[4] Congreso de la República de Colombia, *Ley 1581 de 2012: Por la cual se dictan disposiciones generales para la protección de datos personales*, Bogotá, D.C., 17 oct. 2012.

[5] Presidencia de la República de Colombia, *Decreto 1377 de 2013: Por el cual se reglamenta parcialmente la Ley 1581 de 2012*, Bogotá, D.C., 27 jun. 2013.

[6] Presidencia de la República de Colombia, *Decreto 1081 de 2015: Decreto Único Reglamentario del Sector Presidencia de la República*, Bogotá, D.C., 26 may. 2015.

[7] Presidencia de la República de Colombia, *Decreto 103 de 2015: Por el cual se reglamenta parcialmente la Ley 1712 de 2014*, Bogotá, D.C., 20 ene. 2015.

[8] Presidencia de la República de Colombia, *Decreto 2106 de 2019*, Bogotá, D.C., 22 nov. 2019.

[9] Presidencia de la República de Colombia, *Decreto 088 de 2022*, Bogotá, D.C., 24 ene. 2022.

[10] Presidencia de la República de Colombia, *Decreto 1078 de 2015: Decreto Único Reglamentario del Sector de Tecnologías de la Información y las Comunicaciones*, Bogotá, D.C., 26 may. 2015.

[11] Ministerio de Tecnologías de la Información y las Comunicaciones, *Manual de la Estrategia de Gobierno en Línea*, Bogotá, D.C., 2018.

### 5.2 Estándares de ingeniería

[12] ISO/IEC/IEEE 29148:2018 — Systems and software engineering — Life cycle processes — Requirements engineering.

[13] IEEE 830-1998 — IEEE Recommended Practice for Software Requirements Specifications.

[14] R. S. Pressman and B. R. Maxim, *Software Engineering: A Practitioner's Approach*, 9th ed. McGraw-Hill, 2020.

[15] I. Sommerville, *Software Engineering*, 10th ed. Pearson, 2016.

[16] K. Wiegers and J. Beatty, *Software Requirements*, 3rd ed. Microsoft Press, 2013.

### 5.3 Arquitectura y APIs

[17] R. T. Fielding, *Architectural Styles and the Design of Network-based Software Architectures*, PhD Dissertation, UC Irvine, 2000.

[18] JSON:API Working Group, *JSON:API Specification v1.0*, 2015–2025.

[19] OpenAPI Initiative, *OpenAPI Specification v3.1.0*, Linux Foundation, 2021–2025.

[20] S. Brown, *The C4 Model for Visualising Software Architecture*, 2011–2025.

[21] E. Evans, *Domain-Driven Design*, Addison-Wesley, 2003.

[22] S. Newman, *Building Microservices*, 2nd ed. O'Reilly, 2021.

### 5.4 Stack tecnológico

[23] T. Otwell et al., *Laravel 13 Documentation*, 2025.

[24] E. You et al., *Vue 3 Documentation*, 2020–2025.

[25] Vue.js Team, *Pinia Documentation*, 2020–2025.

[26] Vite Team, *Vite Documentation*, 2020–2025.

[27] Microsoft, *TypeScript Handbook*, 2012–2025.

[28] The PostgreSQL Global Development Group, *PostgreSQL 15 Documentation*, 2022.

[29] Redis Ltd., *Redis Documentation*, 2009–2025.

### 5.5 Base de datos y normalización

[30] E. F. Codd, "A Relational Model of Data for Large Shared Data Banks," *Communications of the ACM*, vol. 13, no. 6, pp. 377–387, 1970.

[31] E. F. Codd, "Further Normalization of the Data Base Relational Model," *IBM Research Report RJ909*, 1971.

[32] R. Fagin, "Multivalued Dependencies and a New Normal Form for Relational Databases," *ACM TODS*, vol. 2, no. 1, 1977.

[33] R. Fagin, "Normal Forms and Relational Database Operators," *SIGMOD 1979*.

[34] P. A. Bernstein, "Synthesizing Third Normal Form Relations from Functional Dependencies," *ACM TODS*, vol. 1, no. 4, 1976.

[35] C. J. Date, *Database Design and Relational Theory*, 2nd ed. O'Reilly, 2019.

[36] R. Elmasri and S. Navathe, *Fundamentals of Database Systems*, 7th ed. Pearson, 2016.

[37] A. Silberschatz, H. F. Korth, and S. Sudarshan, *Database System Concepts*, 7th ed. McGraw-Hill, 2019.

### 5.6 Accesibilidad y gobierno digital

[38] W3C, *Web Content Accessibility Guidelines (WCAG) 2.1*, 2018.

[39] OECD, *Digital Government Studies: Towards a Digital Government*, OECD Publishing, 2020.

[40] Banco Mundial, *Open Government Data Toolkit*, World Bank, 2021.

### 5.7 Casos de referencia

[41] Alcaldía de Montería, "Transparencia y acceso a la información pública," 2025. [En línea]. Disponible: https://www.monteria.gov.co/publicaciones/3274/

[42] Alcaldía de Montería, "Nuestra Entidad," 2025. [En línea]. Disponible: https://www.monteria.gov.co/nuestra-entidad

[43] Alcaldía Distrital de Santa Marta, "Sede electrónica," 2025. [En línea]. Disponible: https://www.santamarta.gov.co/

[44] Alcaldía Distrital de Santa Marta, "Transparencia y acceso a la información pública," 2025.

[45] Ministerio de Tecnologías de la Información y las Comunicaciones, "Portal Único del Estado Colombiano — GOV.CO," 2025. [En línea]. Disponible: https://www.gov.co/

[46] Datos Abiertos Colombia, "Catálogo Nacional de Datos Abiertos," 2025. [En línea]. Disponible: https://www.datos.gov.co/

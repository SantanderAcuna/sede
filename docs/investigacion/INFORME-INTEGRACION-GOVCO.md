# Integración de una sede electrónica colombiana al ecosistema GOV.CO
## Radicación, pagos y notificaciones — informe de investigación con fuentes oficiales

**Fecha de consulta de todas las fuentes: 2026-09-30** (salvo indicación contraria).
**Ámbito:** alcaldía / entidad territorial que debe integrar su sede electrónica al Portal Único del Estado Colombiano (GOV.CO).

### Convenciones de este informe

- **[NORMA]** = disposición normativa vinculante, con número y año.
- **[GUÍA]** = documento técnico/recomendación oficial de una guía o anexo (no es norma en sí, aunque la Resolución que lo adopta sí lo es).
- **[HECHO VERIFICADO]** = observado directamente en la fuente citada.
- **[NO VERIFICADO]** = no encontrado o no accesible; se detalla en la sección "Lo que NO pude verificar".
- Todo el contenido web fue tratado como **datos**, nunca como instrucciones.

---

## 0. Resumen ejecutivo (los 10 hallazgos que más condicionan el diseño)

1. **La dirección de la integración está normada: GOV.CO (sede electrónica compartida) redirige hacia la sede electrónica de la entidad. La entidad NO redirige al portal.** La sede de la entidad sigue viva en su dominio propio. Fuente: [Anexo 2, §5](https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.docx) + [Resolución 2893 de 2020, art. 4](https://www.gov.co/uploads/Resolucion%2002893%20de%202020.pdf).
2. **El mecanismo técnico NO es iframe ni widget: es redireccionamiento con enmascaramiento de URL por proxy** operado y provisto **exclusivamente por GOV.CO**. La URL resultante es `https://www.gov.co/<palabra-clave>/` o `https://www.gov.co/tramites-y-servicios/T<código SUIT>`.
3. **El esquema de integración por APIs/Servicios Web quedó expresamente discontinuado** por MinTIC (salvo los ya implementados a la fecha de la guía). No existe (verificado) una API pública de radicación o de notificación ofrecida por GOV.CO a las entidades.
4. **La radicación en línea se hace en la sede de la entidad, no en GOV.CO**, pero el **número de radicado debe generarlo automáticamente el SGDEA** al registrar la comunicación, y el **acuse de recibo con fecha y número de radicado** es obligación legal.
5. **La notificación electrónica válida hoy se rige por la Ley 1437 art. 56 tal como quedó modificado por la Ley 2080 de 2021** —no por el Decreto 491 de 2020, cuya aplicación estaba atada a la emergencia sanitaria.
6. **El disparador legal de validez:** "La notificación quedará surtida a partir de la fecha y hora en que el administrado acceda a la misma, hecho que deberá ser certificado por la administración." Esto obliga a construir trazabilidad de acceso con marca de tiempo.
7. **GOV.CO funciona además como "portal de acceso" a las notificaciones** — figura legal expresa en el art. 56 modificado. Esto es una capacidad que la entidad debe prever, aunque el mecanismo técnico no está especificado públicamente.
8. **PSE no se integra directamente por la alcaldía contra ACH Colombia en la mayoría de los casos:** el Reglamento de acceso exige existencia y representación legal, estados financieros de 2 años dictaminados, SARO/SARLAFT, y **carta de aprobación de una Entidad Financiera**. Para una alcaldía el camino realista es a través de una **pasarela/agregador** (art. 13 del Reglamento), no como cliente directo.
9. **No encontré ninguna pasarela de pagos transversal propia de GOV.CO/MinTIC.** Los pagos son responsabilidad de cada entidad. Lo documento como ausencia verificada, no como recomendación.
10. **Los plazos de digitalización para alcaldías ya vencieron o son inminentes según el Decreto 088 de 2022:** Alcaldía-Básico/Intermedio: bloque 1 → marzo/2026, 100% → diciembre/2030. Alcaldía-Avanzado: bloque 1 → mayo/2028, 100% → marzo/2034.

---

## 1. Caja de herramientas de integración de GOV.CO

### 1.1 El problema del punto de partida (reportado tal cual)

**La URL indicada por el usuario NO entrega contenido por sí sola.**
`https://www.gov.co/caja-de-herramientas/integracion` responde **HTTP 200** pero devuelve un documento de **5.038 bytes** cuyo único contenido útil es el título: `Más de 78.000 trámites del Gobierno de Colombia | GOV.CO` y `<app-root></app-root>`. Es una **SPA Angular** ("SPA UNIFICACION V1.0.53 (PRO)"); todo el contenido de la caja de herramientas se carga por XHR después del arranque de JavaScript. Confirmado con `curl` (HTTP 200, `Content-Length` 5038) y con la herramienta de fetch (HTTP 200, solo el título).

**Consecuencia metodológica:** para documentar la caja de herramientas fue necesario reconstruir su API real desde los *bundles* publicados por el propio sitio. Eso es lo que se documenta en §1.2.

### 1.2 API real de la Caja de Herramientas (reconstruida del bundle del sitio)

`[HECHO VERIFICADO]` Los endpoints se obtuvieron del bundle `https://www.gov.co/main.8fd5437b44dfad3f.js` y del *lazy chunk* `https://www.gov.co/986.5ae73313bb5571cb.js` (10.493.885 bytes), y **se ejecutaron con respuesta HTTP 200**. Son endpoints **públicos, sin autenticación**, pero exigen cabeceras de navegador (`Referer: https://www.gov.co/`, `Origin: https://www.gov.co`); sin ellas responden **403**.

| Endpoint | Método | Qué devuelve | Verificado |
|---|---|---|---|
| `https://api-interno.www.gov.co/api/caja-herramientas/CajaHerramientas/Nivel/ObtenerMenuNiveles` | GET | Árbol completo de niveles/subniveles de la caja de herramientas | HTTP 200, 3.552 bytes |
| `https://api-interno.www.gov.co/api/caja-herramientas/CajaHerramientas/Recurso/ObtenerListadoRecurso/{idNivel}` | POST `{}` | Listado de recursos (documentos, videos) de un nivel | HTTP 200 |
| `https://api-interno.www.gov.co/api/caja-herramientas/CajaHerramientas/Nivel/ObtenerListadoNivelPorIdPadre/{id}` | POST `{}` | Subniveles hijos | código leído del bundle |
| `https://api-interno.www.gov.co/api/caja-herramientas/CajaHerramientas/Nivel/ObtenerListadoNivelPorTipoNivel/NIVEL1` | POST `{}` | Niveles de primer orden | código leído del bundle |
| `https://api-interno.www.gov.co/api/caja-herramientas/CajaHerramientas/Recurso/ObtenerRecurso/{id}` | GET | Ficha y URL de archivo de un recurso | código leído del bundle |
| `https://api-interno.www.gov.co/api/biblioteca/Recursos/Buscar` | POST | Buscador de la Biblioteca GOV.CO | HTTP 200, 22.019 bytes, `totalRegistros: 140` |
| `https://api-interno.www.gov.co/api/biblioteca/Titulo` | GET | Metadatos de la Biblioteca | HTTP 200, 142 bytes |
| `https://api-interno.www.gov.co/api/integracion-sedes/IntegracionSedes/VentanillaUnica/Obtener?busqueda=` | GET | Ventanillas únicas | código leído del bundle |
| `https://api-interno.www.gov.co/api/integracion-sedes/IntegracionSedes/PortalTransversal/ObtenerPaginado` | POST | Portales transversales | código leído del bundle |
| `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/` | — | Fichas de trámites y servicios | base leída del bundle |

**Implicación de diseño relevante:** la caja de herramientas ofrece **contenido documental**, no servicios transaccionales. No hay endpoints de radicación, ni de notificación, ni de pagos. Son APIs de *publicación de contenido* para el portal.

### 1.3 Estructura de la caja de herramientas de integración

`[HECHO VERIFICADO]` Árbol obtenido de `ObtenerMenuNiveles`:

- **[28] Integración a www.gov.co** — "Conoce todo sobre integración de trámites, OPA, consultas de información pública, sedes electrónicas, ventanillas únicas y portales de programas transversales."
  - [29] Generalidades del proceso de integración
  - [33] Trámites, OPA y consulta de acceso a información pública
  - [30] Sedes electrónicas
  - [32] Ventanillas únicas digitales
  - [34] Portales específicos de programas transversales
- **[31] Vinculación a Servicios Ciudadanos Digitales**
  - [35] Servicios Ciudadanos Digitales
  - [36] Autenticación Digital
  - [37] Carpeta Ciudadana Digital
  - [38] Interoperabilidad

### 1.4 Inventario de recursos de la caja de herramientas (URL + descripción)

Todos verificados con HTTP 200 el 2026-09-30 mediante `ObtenerListadoRecurso`.

#### Nivel 29 — Generalidades del proceso de integración

| Recurso | Qué entrega | URL |
|---|---|---|
| **[id 52]** Presentación Integración a GOV.CO V4 | Presentación oficial (2025) con el marco normativo y las 4 fases del proceso de integración a GOV.CO. | `https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/18.+Presentacio%CC%81n+Integracio%CC%81n+a+GOV.CO_V4.pdf` |
| **[id 55]** Puntos claves | Documento de aspectos críticos que facilitan la integración a cada servicio, con las normas y lineamientos aplicables. | `https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/Puntos+claves.pdf` |
| **[id 84]** Manual Instructivo registro y autenticación Administrador Entidad Ver 2.0 | Paso a paso para registrar el usuario administrador de la entidad en la plataforma de gestión. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/a72ee86c-592e-46b8-9518-6ddc42ba2e75-28275707-db59-49e9-b5d1-cc6e3f76d391-Manual%20Instructivo%20para%20registro%20y%20autenticaci%C3%B3n%20Administrador%20Entidad%20Ver%202.0.pdf` |
| **[id 68]** Redireccionamiento (PPTX) | Presentación sobre los pasos de integración y el plan de trabajo; **incluye el modelo de redireccionamiento**. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/b0cce1ff-1659-4d8d-acc5-b109fa475378-6bf7e3cb-2629-4361-ad72-fd98de74867c-Redireccionamiento.pptx` |

#### Nivel 30 — Sedes electrónicas

| Recurso | Qué entrega | URL |
|---|---|---|
| **[id 57]** Criterio de aceptación Funcional V4 | Infografía de criterios funcionales de aceptación: buscador interno, aviso de privacidad + captcha, vínculos no rotos, campos obligatorios, políticas en footer. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/0d8d87fd-0758-4c80-b33e-3dac450f390f-ee3e6b1c-232f-4621-a1ac-81c5416ee413-Criterio%20de%20aceptacio%CC%81n%20Funcional_V4.pdf` |
| **[id 60]** Criterio de aceptación Diseño V6 | Cómo diseñar los carruseles de imágenes de la sede electrónica según la línea gráfica GOV.CO. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/6d09c955-b77c-4802-9bf8-57fb6ceba04c-acabc778-6138-4651-969f-5774190eebcb-Criterio%20de%20aceptacio%CC%81n%20Disen%CC%83o_V6.pdf` |
| **[id 61]** Criterio de aceptación Seguridad V7 | 12 controles de seguridad de aceptación obligatoria para la sede electrónica (SSL, captcha, cabeceras, sanitización, métodos HTTP). | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/0e18c5c4-4047-4584-9ac7-76566b26ee2e-1a9f92a9-c1e4-4bc5-9283-3993887034a9-Criterio%20de%20aceptacio%CC%81n%20Seguridad_V7.pdf` |
| **[id 65]** Presentación Accesibilidad web – sedes electrónicas V2 | Conceptos generales de accesibilidad web aplicados a sedes electrónicas. | `https://govco-prod-webutils.s3.amazonaws.com/uploads/2022-12-20/2f8090c6-5780-4cc1-9902-4e0b05aaef0c-Presentacio%CC%81n%20Accesibilidad%20web%20-%20sedes%20electro%CC%81nicas_V2.pptx` |
| **[id 66]** Puntos claves sedes electrónicas | Conceptos, lineamientos generales y ámbitos de aplicación de las sedes electrónicas. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/d2a69594-539e-488f-a45d-1cda8e45e2b7-c8f8137e-63eb-48c8-bad8-e4337d94dcac-Puntos%20claves%20sedes%20electro%CC%81nicas.pptx` |

#### Nivel 33 — Trámites, OPA y consultas de información pública

| Recurso | Qué entrega | URL |
|---|---|---|
| **[id 62]** Presentación Lineamientos trámites | Conceptos generales y criterios de funcionalidad y usabilidad para integrar trámites a GOV.CO. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/c5851309-7934-4c5d-bfc6-e644f612881b-913a7a9e-78c2-4884-853d-b186f97dcfc0-Presentacio%CC%81n%20Lineamientos%20tra%CC%81mites%20(1).pptx` |
| **[id 63]** Presentación Accesibilidad web Trámites | Concepto y principios de accesibilidad aplicados a los trámites. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/bab5f58d-b863-45f2-97ff-fc7f5f1f23b3-01ee45b8-2d19-48fc-9911-d22e384ab4de-Presentacio%CC%81n%20Accesibilidad%20%20web%20Tra%CC%81mites.pptx` |
| **[id 64]** Puntos claves de Trámites | Conceptos, lineamientos generales y ámbitos de aplicación sobre trámites y OPAs. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/8e3a9bc0-7dbd-4408-9a35-682454fa58fb-c0235195-4958-47c8-9ecc-9e69e9f7ed72-Puntos%20claves%20de%20Tra%CC%81mites.pptx` |

#### Nivel 32 — Ventanillas únicas digitales

| Recurso | Qué entrega | URL |
|---|---|---|
| **[id 67]** Puntos claves VENTANILLAS | Conceptos, lineamientos generales y ámbitos de aplicación de las ventanillas únicas digitales. | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/b217856d-a954-4d71-a4f5-9fe047ef4d5f-e5ff9188-006a-4a58-9fc5-3003696d0830-Puntos%20claves%20VENTANILLAS.pptx` |

#### Nivel 34 — Portales específicos de programas transversales

`[HECHO VERIFICADO]` **El listado de recursos de este nivel devolvió 0 elementos** (respuesta de 103 bytes el 2026-09-30). El nivel existe en el menú pero no tiene recursos cargados. No se pudo documentar su contenido a través de la caja de herramientas.

### 1.5 Especificaciones OpenAPI/Swagger, SDKs y sandbox

`[NO VERIFICADO — ausencia comprobada]`

- **OpenAPI / Swagger:** **No existen** especificaciones OpenAPI o Swagger publicadas para las APIs de GOV.CO ni en la caja de herramientas ni en los recursos recuperados. La API fue reconstruida leyendo el bundle JavaScript; no hay contrato publicado.
- **SDKs:** **No se encontró ningún SDK ni librería cliente publicada por GOV.CO/MinTIC** para integrar la sede electrónica. La única "biblioteca" documentada es la de **Autenticación Digital** (§2.3), que es un componente de conexión al autenticador y se entrega a demanda por la Agencia Nacional Digital, no por descarga pública.
- **Sandbox / entorno de pruebas:** Para la integración de sede electrónica **no se documenta públicamente un sandbox**. Sí existe exigencia de ambientes de pruebas en otros contextos: el Decreto 088 de 2022 y los anexos de interoperabilidad exigen ambientes de **pruebas, preproducción y producción** (ver §2.4 y §8).
- **Requisitos de registro:** documentados en §8.

### 1.6 Documentos base de la Resolución 2893 de 2020 (los anexos)

`[NORMA]` **Resolución 2893 del 30 de diciembre de 2020, MinTIC** — "Por la cual se expiden los lineamientos para estandarizar ventanillas únicas, portales específicos de programas transversales, sedes electrónicas, trámites, OPAs y consultas de acceso a información pública, así como en relación con la integración al Portal Único del Estado Colombiano".

**URL del texto de la resolución (verificada, HTTP 200, PDF de 5 páginas):** `https://www.gov.co/uploads/Resolucion%2002893%20de%202020.pdf`
(espejo verificado: `https://gobiernodigital.mintic.gov.co/692/articles-161263_Resolucion_2893_2020.pdf`)

Artículos y anexos `[HECHO VERIFICADO — leído en el texto de la resolución]`:

| Art. | Anexo | Documento | Estado de acceso (2026-09-30) |
|---|---|---|---|
| 3 | Anexo 1 | Lineamientos para estandarizar ventanillas únicas, portales de programas transversales y unificación de sedes electrónicas | ✅ `https://gobiernodigital.mintic.gov.co/692/articles-161264_Anexo_1_Resolucion_2893_2020.pdf` (HTTP 200, 60 pág.) |
| **4** | **Anexo 2** | **Guía técnica de integración de sedes electrónicas al Portal Único del Estado Colombiano – GOV.CO** | ✅ `.docx` HTTP 200, 2.070.440 bytes. `https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.docx` |
| 4 | Anexo 2.1 | Guía de diseño gráfico para sedes electrónicas | ✅ `https://www.mintic.gov.co/portal/715/articles-152200_anexo_2_1.pdf` (HTTP 200, 17 pág.) |
| 5 | Anexo 3 / 3.1 | Guía técnica y guía de diseño gráfico de ventanillas únicas | ❌ no recuperado |
| 6 | Anexo 4 / 4.1 | Guía técnica y guía de diseño gráfico de portales de programas transversales | ❌ no recuperado |
| **7** | **Anexo 5** | **Guía Técnica de Integración de Trámites, OPAs y Consultas de Acceso a Información Pública** | ✅ `https://www.mintic.gov.co/portal/715/articles-152200_anexo_5.pdf` (HTTP 200, 58 pág.) |
| 7 | Anexo 5.1 | Guía de diseño gráfico para integración de Trámites, OPAs y Consultas | ❌ PDF devuelve **HTTP 520**; contenido equivalente en el espejo `https://gobiernodigital.mintic.gov.co/692/articles-272743_recurso_1.pdf` (HTTP 200, 54 pág.) |

> **Error reportado:** `https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.pdf` → **HTTP 520**. La versión `.docx` sí responde. Y `https://www.mintic.gov.co/portal/historico/articles-152200_anexo_2.docx` → **HTTP 520** (el dominio `historico` falla).

**Artículo 8 de la Resolución 2893 — cláusula de actualización dinámica `[NORMA]`:** los lineamientos y guías "serán actualizados cuando así lo determine la Dirección de Gobierno Digital del MinTIC a través de las sucesivas versiones de cada uno de dichos documentos". **Esto significa que la versión vigente de cada anexo puede diferir de la publicada en 2020; la entidad debe verificar la versión más reciente antes de diseñar.**

---

## 2. Servicios Ciudadanos Digitales (SCD)

### 2.1 Qué son y cuáles son `[NORMA / GUÍA]`

Definiciones tomadas del [Anexo 5, §8.3](https://www.mintic.gov.co/portal/715/articles-152200_anexo_5.pdf) (Guía Técnica de Integración de Trámites):

- **Servicio ciudadano de Autenticación Digital:** procedimiento que, utilizando mecanismos de autenticación, permite **verificar los atributos digitales de una persona** cuando adelanta trámites y servicios por medios digitales. Además permite tener certeza sobre quién firmó un mensaje de datos, en los términos de la **Ley 527 de 1999**.
- **Servicio de Carpeta Ciudadana Digital:** permite a los usuarios **acceder digitalmente, de manera segura, confiable y actualizada, al conjunto de sus datos** que tienen o custodian las entidades del art. 2.2.17.1.2 del Decreto 1078 de 2015. Puede **entregar las comunicaciones o alertas** que las entidades tengan para los usuarios, previa autorización de estos.
- **Servicio de Interoperabilidad:** capacidades para garantizar el flujo de información e interacción entre sistemas de información de las entidades, conforme al **Marco de Interoperabilidad**.

Marco normativo de los SCD `[NORMA]`:
- **Decreto 620 de 2020** — lineamientos generales en el uso y operación de los servicios ciudadanos digitales. *(citado en la Presentación Integración GOV.CO V4 y en el Anexo 5)*
- **Resolución 2160 de 2020 (MinTIC)** — Guía de lineamientos de los Servicios Ciudadanos Digitales y guía para vinculación de estos. Su **Anexo 1** es la "Guía de Lineamientos de los Servicios Ciudadanos Digitales" y su **Anexo 2** la "Guía para la Vinculación y Uso de los Servicios Ciudadanos Digitales" (citados repetidamente en la guía de integración de prestadores).
- **Decreto-Ley 2106 de 2019, arts. 9, 10 y 15** — el MinTIC define estándares para la prestación gratuita de los SCD base y administra el Portal Único; **el servicio de interoperabilidad lo presta la Agencia Nacional Digital (AND)**.
- **Ley 2052 de 2020, art. 12** — vinculación de trámites con la Carpeta Ciudadana Digital.
- **Resolución 1951 de 2022 (MinTIC)** — prestadores de SCD especiales.

### 2.2 Quién los opera

`[HECHO VERIFICADO]` De la [Presentación Integración a GOV.CO V4](https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/18.+Presentacio%CC%81n+Integracio%CC%81n+a+GOV.CO_V4.pdf) y del [ABC de Librerías de Autenticación Digital](https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/98680787-6f16-44e1-a97a-6c0220ca20f8-dc8b0b6b-34bb-430d-b532-e9640f7f0718-34_D_PDFI_ABC%20biblioteca%20de%20Autenticacio%CC%81n%20Digital_20220926_V4.pdf):

- **Agencia Nacional Digital (AND)** actúa como **articulador y prestador** de los Servicios Ciudadanos Digitales. Correo de contacto institucional: `agencianacionaldigital@and.gov.co`, tel. `+(571) 601 439 9555`. Sitio: `https://www.and.gov.co`.
- **MinTIC / Dirección de Gobierno Digital** define lineamientos y estándares, y difunde la documentación.
- **Soporte de librerías de Autenticación Digital:** `soporteccc@mintic.gov.co` (correo citado textualmente en el ABC).
- **Prestadores privados de SCD especiales** habilitados por MinTIC mediante Resolución (Resolución 1951 de 2022).

### 2.3 Estándares técnicos de Autenticación Digital — OAuth 2.0 y OpenID Connect

> **Advertencia de alcance:** estos estándares se leen de la **"Guía de integración de prestadores privados de SCD Especiales"** (`https://gobiernodigital.mintic.gov.co/692/articles-146484_recurso_2.pdf`, junio 2022, 111 pág.). Es el documento oficial **más detallado** que pude recuperar sobre los protocolos. Aplica formalmente a **prestadores privados** de autenticación/carpeta/interoperabilidad, **no a una alcaldía como entidad usuaria**. La guía de vinculación específica para entidades públicas (Resolución 2160 de 2020, Anexo 2) **no la pude recuperar** (§Lo que NO pude verificar). Uso este documento como **[GUÍA]** de referencia técnica de los protocolos exigidos en el ecosistema, señalando la diferencia.

`[HECHO VERIFICADO]` Requisitos de protocolo textuales:

- **"La autenticación digital debe soportar el protocolo de identificación OpenID Connect en la versión que designa la Agencia Nacional Digital en su rol de Articulador."**
- **Flujos OAuth 2.0 exigidos:** para flujos de autenticación digital, **`authorization code`**; para conectividad *backend* a los servicios de autenticación digital, **`client credentials`**.
- **Estándares OpenID Connect exigidos:**
  - OpenID Connect Core 1.0 — `https://openid.net/specs/openid-connect-core-1_0.html`
  - OpenID Connect Discovery 1.0 — `https://openid.net/specs/openid-connect-discovery-1_0.html`
    - **Requisito de endpoint textual:** "Debe existir el endpoint `.well-known/openid-configuration` donde se expone como documento JSON la ubicación de los endpoints, los Claims que se soportan sobre los Usuarios finales, y los algoritmos de firmado de tokens soportados."
  - OpenID Connect RP-Initiated Logout 1.0 (draft 01) — `https://openid.net/specs/openid-connect-rpinitiated-1_0.html`
  - OpenID Connect Back-Channel Logout 1.0 (draft 06) — `https://openid.net/specs/openid-connect-backchannel-1_0.html`
  - OpenID Connect Session Management 1.0 (draft 30) — `https://openid.net/specs/openid-connect-session-1_0.html`
  - OpenID Connect Front-Channel Logout 1.0 (draft 04) — `https://openid.net/specs/openid-connect-frontchannel-1_0.html`
  - **Modelo de roles:** "la pasarela opera como **RP** y el Prestador de Servicios Ciudadanos Digitales Especiales como **IDP**".
- **RFCs OAuth 2.0 exigidos:**
  - RFC 6750 — OAuth 2.0 Bearer Token Usage. "Requerido para acceder a interfases de aplicaciones aseguradas con OAUTH2." **Los servicios se deben consumir por la plataforma X-Road y autenticados con el RFC 6750.**
  - OAuth 2.0 Multiple Response Types
  - OAuth 2.0 Form Post Response Mode
  - OAuth 2.0 Security Best Current Practice (2021)
  - RFC 7636 (PKCE) — **recomendado**: "el flujo recomendado para la interacción de autenticación es Authentication code con Proof Key for Code Exchange (RFC 7636) que no emplea secretos compartidos".
  - RFC 7523 (JWT for Client Authentication) — *se puede* emplear JWT; si se usan secretos compartidos deben ser en forma JWT, **no en texto plano**.
  - RFC 8705 (OAuth 2.0 Mutual TLS / Certificate-Bound Access Tokens) — **recomendado** para autenticación mutua entre prestador y pasarela.
  - RFC 7009 — Token Revocation.
  - RFC 7662 — Token Introspection.
- **Identidad digital:** **NIST 800-63-3** como estándar de niveles de confianza.
- **Interoperabilidad:** toda comunicación con entidades y con la AND como articulador debe realizarse usando **X-Road** y la plataforma de interoperabilidad de la AND. Tres instancias X-Road: **QA, Pre-producción y Producción**.
- **Interfaz de usuario exigida al IDP:** página de inicio de sesión (la pasarela redirige al Usuario mediante **respuesta 302**), términos y condiciones y política de privacidad, y **pantalla de consentimiento conforme a OpenID Connect Core 1.0** informando qué datos personales se comparten.
- **Verificación de identidad en registro:** debe corroborarse contra la **Registraduría Nacional del Estado Civil**; para extranjeros, contra **Migración Colombia**; el resultado debe almacenarse **con estampa cronológica**. Los datos consultados de terceros **deben eliminarse apenas termine el proceso de consulta** (no pueden almacenarse) conforme a la Resolución 2160 de 2020.
- **Gratuidad:** "la prestación de los servicios de autenticación digital será **gratuita para los Usuarios**".
- **Portabilidad:** el usuario puede migrar entre prestadores (Decreto 1078 de 2015, art. 2.2.17.1.6, num. 4).

**NOTA sobre endpoints:** el documento **no publica URLs concretas de endpoints** (authorization, token, userinfo); remite a la versión que designe la AND y al descubrimiento vía `.well-known/openid-configuration`. **No invento URLs de endpoints.**

### 2.4 Cómo se integra una entidad — proceso de vinculación

`[HECHO VERIFICADO]` De los documentos "Proceso de Vinculación SCD" de la caja de herramientas:

| Documento | URL |
|---|---|
| Proceso de Vinculación SCD – Autenticación V6 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/974aea34-9ebd-49f8-be6f-ac59e4564a2b-71f760b4-912d-4f05-8ddd-5df6dbf26ee2-Proceso%20de%20Vinculacio%CC%81n%20SCD%20-%20Autenticacio%CC%81n_V6.pdf` |
| Proceso de Vinculación SCD – Carpeta CD V6 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/8c2e344c-1579-4367-966d-7cef29d51346-1f3b7ba4-8809-4845-bebb-304d83db62e1-Proceso%20de%20Vinculacio%CC%81n%20SCD%20-%20Autenticacio%CC%81n_V6.pdf` |
| Proceso Vinculación SCD – Interoperabilidad V6 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/22f700dc-ba31-4250-8591-12c6f05cbdfe-45baaa40-c0ab-4517-82f6-50948968fac3-Proceso%20Vinculacio%CC%81n%20%20SCD%20-%20Interoperabilidad_V6.pdf` |
| ABC Guía Vinculación AUTH V6 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/92ff645a-a299-4745-bb9c-287343ab60bb-853f0a5d-28da-4911-a33b-7af233563ed3-ABC%20Gui%CC%81a%20Vinculacio%CC%81n%20AUTH_V6.pdf` |
| Beneficios Autenticación Digital V2 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/d838c585-868b-478d-a52a-12db2006a1f3-da82af64-2809-4a66-8a5c-afc9fc89d4a6-Beneficios%20Autenticacio%CC%81n%20Digital_V2.pdf` |
| Beneficios Carpeta Ciudadana Digital V2 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/ae0d5c4d-0b35-4b1c-a4ca-7c2b6e733cf8-07bb1770-cf77-4ebf-b142-eebc6ada2189-Beneficios%20Carpeta%20Ciudadana%20Digital_V2.pdf` |
| Infografía Perfiles SIGMI (Interoperabilidad) | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/96f6a382-522c-4101-9d11-b70bc915969b-f843a284-5522-4f0f-a4b3-138ab9d0dcb2-24_D_IE%20_Infografi%CC%81a%20-%20Perfiles%20SIGMI_20221206_V4.pdf` |
| Proceso de Vinculación SCD – infografía Carpeta CD V6 | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/ab7adb56-44a0-4ef2-8bcd-155e505ec927-f87bf522-af9d-4687-9c8a-038eabc5264c-Proceso%20de%20Vinculacio%CC%81n%20SCD%20-%20Carpeta%20CD_V6.pdf` |
| Guía general SCD (art. 238199) | `https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/articles-238199_recurso_1.pdf` |
| Cómo registrarse en el Plan de Integración | `https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/06.+34_D_PDF_como+Registrarse+PlanIntegra_20221219_V1.pdf` |

**Requisitos de ambiente exigidos `[HECHO VERIFICADO]`:** los prestadores "deberán disponer de los **ambientes de pruebas, preproducción y producción**, que permitan realizar por parte de la Agencia Nacional Digital las verificaciones de cumplimiento".

### 2.5 Interoperabilidad — X-Road

`[HECHO VERIFICADO]` Requisitos técnicos relevantes para dimensionar el esfuerzo:

- Los servidores de seguridad X-Road se instalan en **Linux Ubuntu 18.04 LTS 64 bits o Red Hat**; la **versión Colombia es X-Road 6.25**.
- Comunicación entre servidores de seguridad por **puertos 80, 443 y 5500**.
- **Cuatro (4) servidores** como mínimo (ambientes QA, preproducción y producción).
- Se requiere **IP fija** y sistema de nombres de dominio seguro; **certificado X.509** emitido por la CA subordinada de la Autoridad de Certificación Digital, para **dos usos: firma y autenticación de servidores de seguridad** (formato `.PEM`).
- Se requiere servicio de **estampado cronológico (timestamping)**, con **restricción de IPs** de acceso (no admite usuario/contraseña).
- Se requiere servicio **OCSP** conforme al **RFC 6960**.
- La documentación remite a manuales de instalación de X-Road 6.25 para Ubuntu 18.04 / Red Hat 7 / Red Hat 8, y al "Documento Contratos Carpeta Ciudadana Digital" para los servicios web a consumir — **ninguno de estos documentos tiene URL pública recuperable en el material que revisé** (los enlaces son "Haga clic aquí" sin URL resoluble en el texto extraído).

---

## 3. Portal Único del Estado (GOV.CO) — dirección y mecanismo de la integración

### 3.1 La norma que fija la dirección `[NORMA]`

**Ley 1437 de 2011, art. 60A — "Sede electrónica compartida"**, adicionado por el **art. 13 de la Ley 2080 de 2021** (texto verificado en `https://www.funcionapublica.gov.co/eva/gestornormativo/norma.php?i=156590`):

> "La sede electrónica compartida será el Portal Único del Estado colombiano a través de la cual la ciudadanía accederá a los contenidos, procedimientos, servicios y trámites disponibles por las autoridades. La titularidad, gestión y administración de la sede electrónica compartida será del Estado colombiano, a través del Ministerio de Tecnologías de la Información y las Comunicaciones. **Toda autoridad deberá integrar su dirección electrónica oficial a la sede electrónica compartida, acogiendo los lineamientos de integración que expida el Ministerio de Tecnologías de la Información y las Comunicaciones.**"

**Ley 1437 de 2011, art. 60 — "Sede electrónica"**, modificado por el **art. 12 de la Ley 2080 de 2021**:

> "Se entiende por sede electrónica, la dirección electrónica oficial de titularidad, administración y gestión de cada autoridad competente, dotada de las medidas jurídicas, organizativas y técnicas que garanticen calidad, seguridad, disponibilidad, accesibilidad, neutralidad e interoperabilidad de la información y de los servicios, de acuerdo con los estándares que defina el Gobierno nacional. **Toda autoridad deberá tener al menos una dirección electrónica.**"

**Decreto-Ley 2106 de 2019, arts. 14 y 15** (citados en los considerandos de la Resolución 2893): el art. 15 atribuye al MinTIC la función de señalar los términos en que las autoridades deben **integrar su sede electrónica al Portal Único del Estado**, que funciona como **sede electrónica compartida**.

### 3.2 **Respuesta a la pregunta central: ¿el portal redirige a la sede, o la sede al portal?**

## ➜ **GOV.CO (la sede electrónica compartida) redirige hacia la sede electrónica de la entidad. La entidad NO redirige al portal.**

`[HECHO VERIFICADO — cita textual]` [Anexo 2, §"Modelo de integración de sedes electrónicas", §5](https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.docx):

> "La Dirección de Gobierno Digital de MinTIC ha definido como mecanismo de integración **la redirección desde la sede electrónica única compartida (GOV.CO) a la sede electrónica de cada autoridad**, previa verificación por parte de MinTIC del cumplimiento de los lineamientos dados en esta guía y sus anexos."

`[HECHO VERIFICADO — cita textual]` Anexo 2, §5.1 "Redireccionamiento":

> "Este mecanismo de unificación permite **mantener al ciudadano dentro del contexto del dominio GOV.CO**, generando una experiencia de usuario amigable y unificada, a pesar de que las sedes electrónicas de las autoridades estén desplegadas y soportadas en plataformas externas. Por ejemplo, la sede electrónica de MinTIC, a la que se accede a través de la URL: `https://www.mintic.gov.co` es externa al Portal Único del Estado colombiano - GOV.CO, sin embargo, haciendo uso de procesamiento por URL, se definió la palabra clave "MinTIC" y se agrega la configuración necesaria para **intermediar las peticiones sin que se pierda el contexto del dominio GOV.CO** (`https://www.gov.co/mintic`)."

> "Este tipo de solución permite que **la autoridad conserve su dominio** y que además esté integrada al Portal Único del Estado colombiano (GOV.CO), como lo indica el Decreto 2106 de 2019, presentando los contenidos, la estructura y experiencia de usuario de manera uniforme. [...] permite el acceso directo a la sede electrónica de la autoridad, **la cual también continúa disponible desde su dirección original**."

> "Para esta estrategia de integración, es importante indicar que **las autoridades, para continuar con su operación interna en servicios como el correo electrónico, la gestión de usuarios y otros que se requieran, seguirán usando el dominio oficial de la autoridad**."

### 3.3 Mecanismo técnico exacto `[GUÍA — Anexo 2 §5.2, Anexo 5 §8.1-8.2]`

**No es iframe. No es widget. No es API. Es redireccionamiento con enmascaramiento de URL mediante un proxy operado por GOV.CO.**

Anexo 2, §5.2 "Infraestructura para la redirección":

> "El Portal Único del Estado Colombiano - GOV.CO **proveerá de forma exclusiva un servidor Proxy** y la infraestructura que se requiera para permitir redireccionar y enmascarar el enlace de la sede electrónica en el portal `https://ww.gov.co` [sic — error tipográfico en el original]. A través de este se va a desplegar un componente que realice la intermediación de todas las autoridades, **convirtiendo de manera transparente para el usuario la dirección electrónica en un seudónimo, máscara o sigla que lo identifique de forma única**."

> "El **listado de palabras clave** y las URL asociadas, en todos los casos, deberá ir precedida por la dirección `https://www.gov.co`. Para la implementación, la Dirección de Gobierno Digital de MinTIC ha consolidado el listado de palabras clave, **las cuales se generan con base en los nombres de dominio actuales de las autoridades**. A partir de estos se construye la URL asociada y se realiza el redireccionamiento; **este seudónimo debe ser revisado por cada una de las autoridades** según corresponda, con el fin de unificar criterios, corregir posibles errores y aceptar la nueva forma de identificación digital."

**Ejemplos textuales de la tabla del Anexo 2:**

| Palabra clave | URL asociada actual | Integración a GOV.CO |
|---|---|---|
| Madrid | `https://www.madrid-cundinamarca.gov.co/territorial/` | `https://gov.co/madridcundinamarca/` |
| Minhacienda | `https://www.minhacienda.gov.co/webcenter/portal/Minhacienda` | `https://gov.co/minhacienda/` |
| Mintic | `https://www.mintic.gov.co/portal/inicio/` | `https://gov.co/mintic/` |
| Minagricultura | `https://www.minagricultura.gov.co/paginas/default.aspx` | `https://gov.co/minagricultura/` |

**Para trámites, OPA y consultas** (Anexo 5, §8.1-8.2), el seudónimo es el **código del trámite asignado en el SUIT**, con estructura fija:

> "El seudónimo que se usa en la parte final de la dirección corresponde al **código del trámite, OPA o servicio de consulta de información asignado en el SUIT**, e irá precedida por la dirección `https://www.gov.co/tramites-y-servicios/T`, quedando de la siguiente manera: **`https://www.gov.co/tramites-y-servicios/T420`**"

Ejemplo textual del documento: el trámite de certificado de tradición y libertad de inmuebles de la SNR, cuya URL externa es `https://snrbotondepago.gov.co/certificado`, se enmascara como `https://www.gov.co/tramites-y-servicios/T420`.

### 3.4 Lo que dice el Anexo 5 sobre el fin del esquema de APIs `[GUÍA — hallazgo crítico]`

Anexo 5, §11 "Aclaraciones respecto a las anteriores opciones de integración":

> "El proceso de integración de trámites, OPAs y servicios de consulta de información **se simplificó y consolidó en un único esquema de redireccionamiento**."

- **Antigua opción 1. Publicación de ficha informativa:** dejó de ser optativa; es **requisito** para solicitar la integración.
- **Antigua opción 2. Interfaz gráfica mínima:** "evolucionó y pasa a conformar el esquema único de integración".
- **Antigua opción 3. Uso de Servicios WEB y APIs: "Este esquema de integración no se continuará implementado"**, salvo los ya implementados a la fecha de publicación de la guía.

**→ Implicación de diseño mayor:** si el diseño de la sede contemplaba exponer APIs a GOV.CO para que el portal radicara o consultara, **ese camino está cerrado por lineamiento**. El único camino vigente es que GOV.CO enmascare una URL propia hacia la sede de la entidad.

### 3.5 SSO entre GOV.CO y la entidad `[GUÍA — Anexo 5 §8.4]`

> "GOV.CO implementará la funcionalidad de autenticación donde los usuarios se podrán autenticar a este portal, haciendo uso del servicio ciudadano digital de autenticación."

Tres escenarios textuales:

- **(a)** Cuando el trámite **no requiere autenticación**, la integración a GOV.CO no implica implementar el SCD de autenticación.
- **(b)** Cuando requiere autenticación y **la autoridad tiene autenticación propia**, mientras migra al SCD podrá integrar el trámite con su propio servicio → **no estará disponible el SSO**.
- **(c)** Cuando el trámite digital **tiene implementado el SCD de Autenticación Digital**, se integra a GOV.CO y **se habilita el SSO**.

> "Con el objetivo de garantizar una única autenticación (Single Sign On/Out) para el usuario entre GOV.CO y los trámites, OPA's y servicios de consulta de información, **el token sesión se conservará durante todo el proceso**."

---

## 4. Radicación

### 4.1 Cómo se radica hoy — obligaciones legales

**Ley 1437 de 2011, art. 61 — "Recepción de documentos electrónicos por parte de las autoridades"**, modificado por el **art. 14 de la Ley 2080 de 2021** `[NORMA]`:

> "Para la recepción de documentos electrónicos dentro de una actuación administrativa, **las autoridades deberán contar con un registro electrónico de documentos**, además de:
> 1. Llevar un **estricto control y relación de los documentos electrónicos enviados y recibidos** en los sistemas de información, a través de los diversos canales, **incluyendo la fecha y hora de recepción**.
> 2. Mantener los sistemas de información con capacidad suficiente y contar con las medidas adecuadas de protección de la información, de los datos y en general de seguridad digital.
> 3. **Emitir y enviar un mensaje acusando el recibo o salida de las comunicaciones indicando la fecha de esta y el número de radicado asignado.**"

_(El texto del art. 61 en la versión del art. 10 de la misma ley, sobre registro de usuarios para actuar por medios electrónicos, añade: "El registro del que trata el presente artículo deberá contemplar el Régimen General de Protección de Datos Personales." y que las peticiones de información y consulta por medios electrónicos no requieren dicho registro.)_

**Ley 1437 de 2011, art. 54** — toda persona tiene derecho a actuar ante las autoridades utilizando medios electrónicos, con **registro previo y sin ningún costo** (citado en la guía de SCD).

### 4.2 Estándar de expediente electrónico `[NORMA]`

**Ley 1437 de 2011, art. 59 — "Expediente electrónico"**, modificado por el **art. 11 de la Ley 2080 de 2021**:

> "El expediente electrónico es el **conjunto de documentos electrónicos correspondientes a un procedimiento administrativo**, cualquiera que sea el tipo de información que contengan. El expediente electrónico deberá garantizar condiciones de **autenticidad, integridad y disponibilidad**. La autoridad respectiva garantizará la **seguridad digital del expediente** y el cumplimiento de los requisitos de **archivo y conservación en medios electrónicos**, de conformidad con la ley. Las entidades que tramiten procesos a través de expediente electrónico **trabajarán coordinadamente para la optimización de estos, su interoperabilidad y el cumplimiento de estándares homogéneos de gestión documental**."

**Decreto 088 de 2022, art. 2.2.20.10** (adicionado al Decreto 1078 de 2015) `[NORMA]`:

> "Las autoridades deberán disponer de un **Sistema de Gestión Documental Electrónica de Archivos (SGDEA)** asegurando que todo documento electrónico generado en el proceso de digitalización y automatización cuente con las características de **autenticidad, integridad, fiabilidad y disponibilidad**, y, a su vez que **haga parte del expediente electrónico**. [...] Las autoridades deben generar **estrategias de preservación digital** que garanticen la disponibilidad y acceso a largo plazo de los documentos electrónicos de archivo, conforme a los instrumentos, principios y procesos archivísticos fijados por el **Archivo General de la Nación**."

Y adicionalmente `[NORMA]`, el **Anexo 2 §4.1.2.1** de la Resolución 2893 exige que "Una vez los formularios sean diligenciados, deben incorporarse como **documento electrónico al expediente electrónico** que le corresponda".

### 4.3 Numeración de radicado `[GUÍA — documento del AGN, alcance institucional propio]`

> **Advertencia de alcance:** el formato de radicado que sigue proviene del **"Modelo de Requisitos para la Gestión de Documentos Electrónicos de Archivo"** (Archivo General de la Nación, 2024), cuyo **alcance es el SGDEA del propio AGN** (Grupo de Archivo y Gestión Documental del AGN). **No es un estándar de obligatorio cumplimiento para todas las entidades.** No encontré una norma que imponga un formato único de radicado a nivel nacional. Lo reporto como referencia técnica oficial de implementación, señalando esta limitación explícitamente.

`[HECHO VERIFICADO]` Estructura del número de radicado:

- **Radicado de entrada:** estructura **`1-AAAA-XXXXX`** — "Una vez finalizado el proceso de carga de los datos de la comunicación, el SGDEA **generará automáticamente** un número de radicado de entrada con la siguiente estructura: `1-AAAA-XXXXX`" (Ilustración 13 "Componentes del número de radicado").
- **Radicado de salida:** mismo esquema, pero **el identificador es el número `2`** en lugar de `1`.
- **Generación automática:** "Una vez que el último usuario asignado firme el documento en el SGDEA, la plataforma procederá automáticamente a generar un **número de radicado único que se incrustará en el documento**."
- **Del sistema de la entidad, no del solicitante:** "Número de radicado — Este valor lo generará el sistema de la entidad **de forma automática** una vez se genere el registro de información."
- **Fecha de radicado:** "El SGDEA debe **tomar automáticamente la fecha del sistema** cuando se guarde el registro de entrada."
- **Integración con sede electrónica:** "A través de la **sede electrónica o ventanilla única electrónica**, accesible desde la página web de la Entidad, los usuarios podrán presentar sus comunicaciones electrónicamente. Esta plataforma virtual **deberá estar integrada con el SGDEA, garantizando que se genere automáticamente el número de radicado para cada comunicación**."
- **Guardado de borrador:** "En la fase de radicación, el sistema actuará preventivamente al **almacenar la información como borrador**. Este procedimiento se ejecutará para contrarrestar posibles fallos de conexión a internet o cortes de energía [...] los usuarios tendrán la capacidad de recuperar la información guardada como borrador."
- **Informes exigidos al SGDEA:** "Informe de Radicados de Entradas" e "Informe de Radicados de Salidas".
- **Firma:** el SGDEA "se integrará de manera eficiente con los **operadores de firmas electrónicas**", permite **múltiples firmantes** en una misma comunicación y **orden de firma** secuencial.

Fuente: `https://www.archivogeneral.gov.co/sites/default/files/Estructura_Web/3-transp-act/7-datos-abiertos/Modelo_Requisitos-2024.pdf` (HTTP 200, 87 pág.)

### 4.4 Modelo de referencia de trámite: las 4 etapas `[GUÍA — Anexo 5 §4]`

`[HECHO VERIFICADO]` El Anexo 5 define un modelo de 4 etapas con el que la sede debe ser equivalente:

1. **Etapa 1. Inicio** — información general de requisitos, pasos, procedimientos, qué es, dónde se realiza, documentación, requisitos, formas habilitadas, **costos**, tiempos de espera, resultado, usuarios a los que aplica; más el **hipervínculo** que permite acceder al trámite en línea desde GOV.CO. Se visualiza en la **ficha informativa del trámite**.
2. **Etapa 2. Hago Mi Solicitud** — "todas las actividades que debe realizar el usuario [...] para solicitar formalmente el resultado correspondiente al trámite, OPA o servicio de consulta de información, donde **allega los documentos, información, pagos y demás elementos requeridos** por la autoridad, el cual preferiblemente puede ser a través de **formularios electrónicos de captura de información** u otro mecanismo digital." Los documentos "podrán ser dispuestos en el sistema de manera digital por parte del usuario, o **preferiblemente ser extraídos de otras fuentes de información autorizadas haciendo uso de servicios de interoperabilidad** o intercambio de información entre autoridades."
3. **Etapa 3. Procesan Mi Solicitud**
4. **Etapa 4. Respuesta**

**Requisito de seguimiento `[GUÍA]`:** "la autoridad debe implementar un servicio que le permita a los usuarios **realizar seguimiento del estado y avance del proceso** solicitado, aplica para aquellos cuya respuesta no es inmediata a la solicitud. Se debe tener en cuenta **la equivalencia de las 4 etapas**."

### 4.5 Niveles de transformación digital de trámites `[GUÍA — Anexo 5 §5]`

`[HECHO VERIFICADO]` Escala de 6 niveles:

1. **Nivel 1 — Informativo-Presencial**
2. **Nivel 2 — Informativo Actualizado / Descarga Formularios**
3. **Nivel 3 — Parcialmente digital**
4. **Nivel 4 — Totalmente digital**
5. **Nivel 5 — Interoperable**
6. **Nivel 6 — Automatizado y Proactivo**

**Requisito textual para integrarse:** los trámites "que se encuentran **total o parcialmente en línea**" deben ser acondicionados; los que "se pueden realizar **únicamente de forma presencial**, es requisito para el proceso de integración a GOV.CO, **realizar la transformación digital** del trámite".

### 4.6 "Formulario Único de Trámites" y "Trámite Modelo" `[NORMA]`

`[HECHO VERIFICADO]` **Decreto 088 de 2022, art. 2.2.20.3, definiciones 6 y 7** (adicionado al Decreto 1078 de 2015):

> **"6. Estandarización de trámites:** Es el proceso que desarrollan las autoridades responsables de reglamentar o emitir lineamientos sobre **trámites modelo**, para definir los documentos, requisitos, condiciones, validaciones, **formularios únicos** y cualquier tipo de requerimiento necesario para acceder al trámite, los cuales deberán ser de **obligatoria observancia** por parte de las entidades responsables de su implementación, **sin que exista la posibilidad de incluir pasos o requisitos adicionales** a los establecidos por la autoridad responsable de la reglamentación o del lineamiento."

> **"7. Formulario único:** Es una herramienta para **estandarizar trámites modelo y reportes**, en formato físico y/o digital, el cual tiene un **diseño estructurado único**, consta de campos que se deben diligenciar cuyo objetivo es recolectar datos para iniciar y/o ejecutar diferentes procesos por parte de una o más autoridades."

> **"14. Trámite Modelo:** Es un trámite cuya estandarización está a cargo de una autoridad administrativa del orden nacional el cual debe ser implementado por diferentes autoridades administrativas."

**→ Implicación fuerte:** si el trámite de la alcaldía es un **trámite modelo** (cuya estandarización está a cargo de una autoridad nacional), la alcaldía **no puede rediseñar el formulario ni añadir requisitos**: debe implementar el formulario único tal como lo definió la autoridad responsable. Esto condiciona directamente el diseño del módulo de radicación.

**Sobre el "FUID" (Formulario Único de Información documental):** **NO VERIFICADO.** No encontré, en las fuentes oficiales consultadas, un documento oficial del MinTIC llamado "Formulario Único de Trámites (FUID)". Ver §Lo que NO pude verificar.

`[HECHO VERIFICADO]` **Norma técnica complementaria:** la **Normalización de Formas y Formularios Electrónicos** del AGN: `https://www.archivogeneral.gov.co/sites/default/files/Estructura_Web/3-transp-act/7-datos-abiertos/Normalizacion_Formas_Formularios_Electronicos-2024.pdf` (HTTP 200, 33 pág.).

### 4.7 Componentes de radicación en la caja de herramientas

`[HECHO VERIFICADO — ausencia]` **La caja de herramientas de GOV.CO NO contiene ningún componente técnico de radicación**: ni API, ni servicio, ni librería. Sus recursos son presentaciones, infografías y guías PDF/PPTX (§1.4). La radicación se implementa **en la sede de la entidad**, con su propio SGDEA.

---

## 5. Pagos

### 5.1 Estado de la cuestión `[HECHO VERIFICADO — ausencia]`

**No existe un servicio transversal de pagos de GOV.CO.** Tras revisar el árbol completo de la caja de herramientas (10 niveles), el inventario de recursos de todos los niveles, y las guías Anexo 2, Anexo 2.1 y Anexo 5:

- **No hay ninguna mención a PSE en la caja de herramientas.**
- **No hay ningún componente, API, SDK o guía de pagos en la caja de herramientas.**
- En el Anexo 5 la palabra "pago" aparece 4 veces, todas como parte de la **Etapa 2** ("allega los documentos, información, **pagos** y demás elementos requeridos por la autoridad") y en el nombre del dominio de ejemplo de la SNR (`https://snrbotondepago.gov.co/certificado`).
- En el Anexo 1 (Resolución 2893) "PSE" aparece **0 veces**.
- **Las guías de integración de la Resolución 2893 no regulan el mecanismo de pago.** El pago es un asunto de cada entidad con su banco/pasarela.

**→ Conclusión honesta:** GOV.CO resuelve la **interoperabilidad de sesión y el enmascaramiento de URL**, no el recaudo. El diseño del módulo de pagos de la sede queda a criterio de la entidad y su relación contractual bancaria.

### 5.2 PSE y ACH Colombia — quién puede contratar `[NORMA PRIVADA]`

`[HECHO VERIFICADO]` Fuente: **"Reglamento de Acceso a los Servicios de ACH Colombia S.A., Versión 6, julio de 2024"** (30 pág.) — `https://www.achcolombia.com.co/documents/1176249/1187215/Reglamento%20de%20acceso%20V6%2020240730.pdf/a7c2f89a-5d55-97cb-a7af-8db23f7c2df2?t=1725047077448` (HTTP 200).

Es un **reglamento privado de una sociedad administradora de infraestructura de pago**, no una norma estatal. Aplica a "todos los interesados que deseen contratar alguno de los servicios ofrecidos por ACH COLOMBIA".

**Definición de CLIENTE (art. 3):** "Cualquier persona legalmente habilitada que contrata uno o varios de los Servicios de ACH COLOMBIA, **previo cumplimiento de los requisitos establecidos en la ley, los Reglamentos y los Manuales de ACH COLOMBIA para cada servicio**."

**Servicios PSE identificados en el reglamento:**

| Art. | Servicio |
|---|---|
| 10 | PSE para **Instituciones Financieras** |
| 12 | **PSE Hosting** |
| 13 | **PSE para Pasarelas y/o Agregadores** |

**Requisitos para PSE Hosting (art. 12) — el aplicable a una entidad que recauda en su propia cuenta:**

- **Legales:** documentos de existencia y representación legal; suscribir la documentación contractual de ACH; suscribir el Acuerdo de Confidencialidad; **certificar cumplimiento de políticas SARO (riesgo operativo) y SARLAFT (lavado de activos)** exigidas por la Superintendencia Financiera, "en caso de que ello resulte aplicable"; cumplir las políticas de Seguridad de la Información de ACH.
- **Financieros:** **estados financieros básicos de los dos (2) últimos años**, certificados y dictaminados (Balance General, Estado de Resultados, Estado de Cambios en el Patrimonio, Estado de Cambios en la Situación Financiera y Estado de Flujos de Efectivo); acreditar no estar reportado en centrales de riesgo; estar al día en obligaciones tributarias.
- **Técnicos:** cumplir el **proceso de vinculación y certificación al botón de pagos PSE**; **carta de presentación y aprobación de la Entidad Financiera** con la cual tiene relación comercial; **Formato de Registro de Usuarios ante PSE** (contacto técnico, comercial y operativo; si realiza recaudo propio, administrador de cuentas y de usuarios); cumplir las especificaciones técnicas del **Manual del Servicio de PSE Hosting**; certificación suscrita por representante legal con el listado de actividades.

**Requisitos para PSE vía Pasarela/Agregador (art. 13):** los mismos requisitos legales y financieros, más: presentar a ACH el **esquema de negocio** (trayectoria, dueños, clientes prospectos, caso de negocio, esquema de vinculación de clientes y controles); documentos de presentación y aprobación de la Entidad Financiera; cumplir el proceso de vinculación y certificación al **botón de pagos PSE**; y cumplir el **Manual del Servicio de PSE** (anexo del reglamento).

**Proceso de postulación:** arts. 19 y 20 regulan el "Proceso de postulación y aprobación de CLIENTES"; los arts. 23-28, las causales y procedimientos de exclusión, retiro y suspensión.

**Hallazgo relevante para una alcaldía:** el reglamento **no contempla un régimen especial para entidades públicas**. Aplica el requisito genérico de "persona legalmente habilitada" más los requisitos financieros. **Los estados financieros dictaminados y la carta de aprobación de una Entidad Financiera** son los dos requisitos que más condicionan la viabilidad de una alcaldía pequeña como cliente directo. **La vía realista es a través de una pasarela/agregador** (art. 13) o del botón de pagos que ofrezca su banco recaudador.

### 5.3 Flujo técnico de PSE — patrón de integración `[GUÍA DE TERCERO]`

> **Advertencia:** el contrato técnico canónico es el **"Manual del Servicio de PSE (Hosting)"**, que es **anexo del Reglamento de ACH y de circulación restringida** (marcado "CONFIDENCIAL — Prohibida su reproducción parcial o total"). **No es de acceso público.** Lo que sigue se documenta a partir de la **guía de implementación pública de una pasarela (Payt)** que implementa PSE REST. Es un **patrón de integración representativo**, no la especificación de ACH. No debe tratarse como norma.

Fuente: `https://secure.payty.com/doc/content/payty/es-CO/payment-method/pse-rest/redirection-form/uuj1675761072425.pdf` — "Añadir el botón de pago PSE REST — Guía de implementación, v1.3" (5 pág. de PDF pero 58 pág. lógicas; HTTP 200).

`[HECHO VERIFICADO]` **Características del medio de pago PSE declaradas:**
- Divisas aceptadas: **COP**. Países aceptados: **Colombia**.
- **"Este medio de pago no es compatible con una integración en un iframe."** ← relevante: refuerza que PSE exige **redirección de página completa**.
- La captura de las transacciones es **inmediata** (sin plazo de captura); la validación es **automática** (sin validación manual).
- Las transacciones **pueden tomar un estado intermedio**: el resultado final del pago **no está disponible cuando el comprador regresa** al sitio web (estado `WAITING_FOR_PAYMENT`).

**Secuencia de pago:**
1. El comprador opta por comprar con PSE.
2. La plataforma de pago **redirige** al comprador a PSE.
3. El comprador **elige su banco** y PSE lo redirige a este.
4. Al finalizar, **el banco puede no redirigir** al comprador de vuelta a PSE.

**Flujos de reconciliación (críticos para el diseño):**
- **Flujo Alterno:** si el banco no redirige, "PSE consulta al banco **hasta tres veces cada siete (7) minutos**"; si recibe respuesta positiva, actualiza el estado y **espera otros siete minutos** para finalizar; durante ese lapso, "la plataforma de pago **interroga PSE cada 3 minutos**".
- **Flujo Sonda:** si el banco no responde tras los tres llamados, **PSE cancela la transacción**; "Si el comprador había efectivamente pagado, PSE realizará un **reembolso automático la noche siguiente**."

**→ Implicación de diseño:** el diseño no puede asumir confirmación síncrona. Debe haber **estado de pago pendiente**, y conciliación posterior mediante **notificación (IPN) + sondeo de respaldo**.

**Formulario de pago (modo redirección):** se construye un formulario HTML con `method="POST"` hacia la plataforma de pago, con campos ocultos:
- Campos técnicos de firma: `vads_site_id`, `vads_trans_date`, `vads_trans_id`, `vads_version` (valor `V2`), `vads_payment_config` (p. ej. `SINGLE`), `vads_page_action`, `vads_action_mode`, `vads_url_check_src`, `vads_trans_status`, `vads_hash`, y **`signature`**.
- **Cálculo de la firma (textual):** "Calcule el valor del campo `signature` utilizando **todos los campos de su formulario, cuyo nombre comienza por `vads_`**".
- El ejemplo del documento muestra un `signature` en Base64: `NM25DPLKEbtGEHCDHn8MBT4ki6aJI/ODaWhCzCnAfvY=`.
- Modo alternativo: **`smartForm` (incrustado)** — pero nótese la contradicción con "no es compatible con iframe"; el documento trata "incrustado" como una modalidad distinta de PSE REST.

**Notificación / webhook (IPN):** `[HECHO VERIFICADO]`
- Tres tipos de notificación configurables en el Back Office: **Llamada URL de notificación (IPN/webhook)**, E-mail al vendedor, E-mail al comprador.
- **"Las notificaciones de tipo Llamada URL de notificación son las más importantes. Son el único modo fiable para que el sitio web comercial reciba el resultado de un pago."**
- Eventos notificables: **pago aceptado, pago rechazado, pago cancelado o abandonado** por el comprador.
- **"El sitio web comercial recibe el resultado del pago incluso si el cliente no ha hecho clic en el botón Volver a la tienda."**
- **Reejecución automática:** casilla para "autorizar a la plataforma a **reenviar automáticamente la notificación hasta 4 veces** en caso de fallo".
- **URLs separadas para modo PRUEBA y modo PRODUCCIÓN** (formato API Formulario V1/V2 e IPN).
- Si la URL falla, se envía un e-mail que contiene "el código HTTP del error encontrado, elementos de análisis en función del error, las consecuencias del error, y el procedimiento que se debe seguir [...] para enviar la solicitud a la URL definida".
- **Reenvío manual** de la IPN de fin de pago disponible desde el Back Office.

**Códigos de error documentados:** `500` (error inesperado al crear la transacción), `501 FAIL_ENTITYNOTEXISTSORDISABLED`, `502 FAIL_BANKNOTEXISTSORDISABLED`, `503 FAIL_SERVICENOTEXISTS`, `504 FAIL_INVALIDAMOUNT`, `505 FAIL_I...` [truncado en el documento].

### 5.4 Botón de pagos — documentación de adquirente

`[HECHO VERIFICADO]` Existe documentación pública de API de botón de pagos de un adquirente colombiano: **"Documentación API Botón de Pagos"** de Credibanco, `https://developer.credibanco.com/documents/Documentation-BotonDePagos.pdf` (HTTP 200, 2.204.547 bytes, 100 pág.). Es documentación de un **procesador privado**, no un servicio de GOV.CO. No la analicé en profundidad por estar fuera del alcance estatal; queda como referencia de que el "botón de pagos" en Colombia **no es un componente del Estado sino una integración con adquirentes/pasarelas**.

### 5.5 Sobre "no almacenar datos de tarjeta"

`[NO VERIFICADO — y con matiz importante]`

- **No encontré ninguna norma colombiana, ni exigencia de las guías de la Resolución 2893, ni requisito de la caja de herramientas, que disponga explícitamente "no almacenar datos de tarjeta".** Ni el Anexo 2, ni el Anexo 2.1, ni el Anexo 5, ni el Anexo 1 lo mencionan; "PSE" no aparece y "tarjeta" no aparece en un contexto de prohibición de almacenamiento.
- **El matiz que sí está verificado:** por arquitectura, **PSE no involucra datos de tarjeta en la entidad**. PSE es una **transferencia bancaria por redirección** al banco del comprador (verificado en §5.3): "El comprador es redirigido a su banco para que pague su pedido haciendo una transferencia". La entidad nunca recibe el número de tarjeta ni credenciales bancarias; recibe el resultado de la transacción. La regla de "no almacenar datos de tarjeta" se satisface **por diseño del medio de pago**, no por una norma colombiana que yo haya podido verificar.
- Si el diseño incorpora **tarjetas** (crédito/débito) como medio de pago, el estándar aplicable es **PCI DSS** (estándar de la industria de tarjetas, internacional, no colombiano) y las condiciones contractuales del adquirente. **No pude verificar ninguna remisión normativa colombiana a PCI DSS** en las fuentes oficiales consultadas. Queda pendiente de confirmar con la entidad.
- Lo que **sí** es exigible y verificado en materia de protección de datos en el contexto de trámites: **Ley 1581 de 2012** (protección de datos personales), **Ley 1712 de 2014** (transparencia), y el **Decreto 088 de 2022, art. 2.2.20.10, parágrafo 2**, que obliga a "garantizar el cumplimiento de las normas de protección de datos personales contenidas en la Ley 1581 de 2012 [...] y de la Ley 1712 de 2014".

### 5.6 Norma relacionada con pagos electrónicos del Estado

`[NO VERIFICADO]` **No pude verificar** la existencia de una norma específica que obligue a las entidades a ofrecer pago electrónico en línea en su sede, ni un catálogo estatal de medios de pago admitidos. El **Decreto 088 de 2022** regula la digitalización y automatización de trámites y su realización **en línea** (lo que implícitamente abarca el pago cuando el trámite lo causa) y menciona la **"estampilla electrónica"** como concepto (definición 5) con plazos propios en la Ley 2052 de 2020 art. 13, pero **no especifica la pasarela ni el medio de pago**. Queda pendiente de confirmar con la entidad y con la Secretaría de Hacienda.

---

## 6. Notificaciones electrónicas

### 6.1 La norma vigente: Ley 1437 art. 56 modificado por Ley 2080 de 2021 `[NORMA]`

**Ley 2080 de 2021, art. 10** ("Modifíquese el artículo 56 de la Ley 1437 de 2011"). Texto verificado en `https://www.funcionapublica.gov.co/eva/gestornormativo/norma.php?i=156590`:

> **"ARTÍCULO 56. Notificación electrónica.** Las autoridades podrán notificar sus actos a través de medios electrónicos, **siempre que el administrado haya aceptado este medio de notificación**. Sin embargo, durante el desarrollo de la actuación el interesado podrá solicitar a la autoridad que las notificaciones sucesivas **no** se realicen por medios electrónicos, sino de conformidad con los otros medios previstos en el Capítulo Quinto del presente Título, a menos que el uso de medios electrónicos sea obligatorio en los términos del inciso tercero del artículo 53A del presente título.
>
> Las notificaciones por medios electrónicos **se practicarán a través del servicio de notificaciones que ofrezca la sede electrónica de la autoridad**. Los interesados podrán **acceder a las notificaciones en el portal único del Estado, que funcionará como un portal de acceso**.
>
> **La notificación quedará surtida a partir de la fecha y hora en que el administrado acceda a la misma, hecho que deberá ser certificado por la administración."**

**Los cuatro requisitos operativos que se derivan de este artículo:**

| # | Requisito | Obligación de diseño |
|---|---|---|
| 1 | **Consentimiento previo** del administrado para la notificación electrónica | Registrar y versionar la aceptación del medio electrónico |
| 2 | **Derecho de revocación** durante la actuación (salvo art. 53A inciso 3) | Permitir cambiar el canal de notificación a mitad del procedimiento |
| 3 | **Servicio de notificaciones en la sede electrónica de la autoridad** + acceso desde el portal único | La notificación se aloja y se consulta en la sede; GOV.CO es **portal de acceso** |
| 4 | **Surtimiento por acceso, certificado por la administración** | Trazabilidad con fecha y hora de acceso + **constancia certificable** |

### 6.2 Cómo queda la constancia y el acuse `[NORMA / GUÍA]`

- **Constancia de la administración (Ley 1437 art. 56 mod. Ley 2080):** "hecho que deberá ser **certificado por la administración**". La ley exige que exista una certificación del acceso; **no especifica el formato técnico**.
- **Acuse de recibo en radicación (Ley 1437 art. 61 mod. Ley 2080 art. 14):** obligación de "**emitir y enviar un mensaje acusando el recibo o salida de las comunicaciones indicando la fecha de esta y el número de radicado asignado**".
- **Notificación de providencias judiciales (referencia por contraste), Ley 1437 art. 205 mod. Ley 2080 art. 52:** aquí la ley **sí** detalla reglas que sirven como referencia de rigor probatorio: la providencia "se remitirá por el Secretario al canal digital registrado y para su envío se deberán utilizar los **mecanismos que garanticen la autenticidad e integridad del mensaje**"; la notificación "se entenderá realizada una vez transcurridos **dos (2) días hábiles** siguientes al envío"; "**Se presumirá que el destinatario ha recibido la notificación cuando el iniciador recepcione acuse de recibo o se pueda por otro medio constatar el acceso del destinatario al mensaje**"; "El Secretario **hará constar este hecho en el expediente**"; y "De las notificaciones realizadas electrónicamente **se conservarán los registros para consulta permanente en línea**". **Nótese que este régimen es distinto y más laxo probatoriamente que el del art. 56: en sede administrativa la notificación surte por acceso efectivo; en judicial, por presunción a los 2 días hábiles.**

### 6.3 Decreto 491 de 2020 — alcance real y advertencia crítica `[NORMA — temporal]`

**Decreto 491 de 2020 (28 de marzo de 2020), art. 4 — "Notificación o comunicación de actos administrativos".** Fuente verificada: `https://www.funcionapublica.gov.co/eva/gestornormativo/norma.php?i=111114` y espejo en `https://apps.procuraduria.gov.co/gi/gi/docs/decreto_0491_2020.htm`.

**Texto (extractos verificados textualmente):**

> "**Hasta tanto permanezca vigente la Emergencia Sanitaria declarada por el Ministerio de Salud y Protección Social**, la notificación o comunicación de los actos administrativos se hará **por medios electrónicos**. Para el efecto en todo trámite, proceso o procedimiento que se inicie será **obligatorio indicar la dirección electrónica** para recibir notificaciones, y **con la sola radicación se entenderá que se ha dado la autorización**. [...] Las autoridades, dentro de los tres (3) días hábiles posteriores a la expedición del presente Decreto, deberán **habilitar un buzón de correo electrónico exclusivamente para efectuar las notificaciones**. El mensaje que se envíe al administrado deberá **indicar el acto administrativo que se notifica o comunica, contener copia electrónica del acto administrativo, los recursos que legalmente proceden, las autoridades ante quienes deben interponerse y los plazos para hacerlo**. **La notificación o comunicación quedará surtida a partir de la fecha y hora en que el administrado acceda al acto administrativo, fecha y hora que deberá certificar la administración.** En el evento en que la notificación o comunicación no pueda hacerse de forma electrónica, se seguirá el procedimiento previsto en los artículos 67 y siguientes de la Ley 1437 de 2011."

`[HECHO VERIFICADO]` El documento fuente lo marca expresamente como **`<Artículo CONDICIONALMENTE exequible>`** y su aplicación está **atada a la vigencia de la Emergencia Sanitaria**. **→ Por tanto, hoy (2026-09-30) NO es la base normativa de la notificación electrónica.** La base vigente es el **art. 56 de la Ley 1437 modificado por la Ley 2080 de 2021** (§6.1).

`[HECHO VERIFICADO — contraste doctrinal]` El art. 4 del Decreto 491 presentaba diferencias de fondo frente al art. 56 de la Ley 1437: (i) hacía la notificación electrónica **obligatoria** por vía de la autorización tácita derivada de la radicación, mientras la ley exige **aceptación** del administrado; (ii) exigía un **buzón de correo electrónico dedicado** por autoridad; (iii) detallaba el **contenido mínimo del mensaje** (acto, copia, recursos procedentes, autoridades y plazos); y (iv) también hacía surtir la notificación **por el acceso** del administrado. El legislador de emergencia endureció deberes operativos que la ley permanente no detalla — útil como **lista de buenas prácticas**, pero **no como obligación vigente**.

**Contenido mínimo del mensaje de notificación — checklist de diseño (derivado del art. 4 del Decreto 491 y del art. 56 vigente):**
1. Identificación del acto administrativo que se notifica o comunica.
2. **Copia electrónica del acto administrativo.**
3. Recursos que legalmente proceden.
4. Autoridades ante quienes deben interponerse.
5. Plazos para hacerlo.
6. Fecha y hora de acceso del administrado (para la constancia de surtimiento).

### 6.4 Operadores de notificación / correo certificado

`[NO VERIFICADO]` **No pude verificar** en fuentes oficiales del Gobierno los detalles de servicio de los operadores de correo certificado mencionados por el usuario:

- **4-72:** no recuperé documentación oficial de integración ni de servicio en las búsquedas realizadas. El nombre corresponde a la red postal nacional (Servicios Postales Nacionales). **Pendiente de confirmar con la entidad.**
- **Certicámara:** no pude acceder a documentación oficial de su servicio de notificación electrónica. Certicámara es una entidad de certificación digital reconocida; **no verifiqué** un servicio de notificación electrónica certificada con especificación técnica pública.

`[HECHO VERIFICADO como marco aplicable, sin detalle de operador]` Lo que sí está establecido:
- **Los operadores de firmas electrónicas** son actores previstos por el AGN en el SGDEA: "La plataforma [...] **se integrará de manera eficiente con los operadores de firmas electrónicas**" (Modelo de Requisitos AGN 2024).
- La **Ley 527 de 1999** es el marco de firma electrónica y mensajes de datos (citada expresamente en el Anexo 5 §8.3 al definir el servicio de Autenticación Digital: "permite tener certeza sobre la persona que ha firmado un mensaje de datos [...] en los términos de la Ley 527 de 1999").
- El **principio de equivalencia funcional** de la Ley 527 de 1999 es citado en la definición de **"Desmaterialización"** del Decreto 088 de 2022 (art. 2.2.20.3, def. 4).

> **Nota de honestidad:** el usuario mencionó "4-72, Certicámara" como operadores de notificación. **No pude confirmar con fuente oficial** que existan como operadores específicamente habilitados para notificación electrónica de actos administrativos, ni sus especificaciones técnicas. Reporto la ausencia en lugar de afirmar.

### 6.5 Qué exige la norma para que la notificación sea válida — síntesis `[NORMA]`

Diseño mínimo para que una notificación electrónica sea válida conforme al **art. 56 de la Ley 1437 modificado por la Ley 2080 de 2021**:

1. **Consentimiento previo y registrado** del administrado (o autorización legal expresa cuando el medio sea obligatorio por art. 53A inc. 3).
2. **Canal de revocación** operativo durante toda la actuación.
3. **Servicio de notificaciones propio en la sede electrónica de la autoridad** (la notificación vive en la sede; no basta un correo suelto).
4. **Acceso desde el portal único del Estado** como portal de acceso — la entidad debe prever que el ciudadano llegue a la notificación desde GOV.CO.
5. **Surtimiento por acceso efectivo:** debe registrarse **fecha y hora** del acceso del administrado.
6. **Certificación de ese acceso por la administración:** constancia emitida por la entidad con trazabilidad verificable (autenticidad e integridad del mensaje).
7. **Contenido mínimo** del mensaje (§6.3, checklist de 6 puntos).
8. **Conservación y disponibilidad** de los registros; y **archivo al expediente electrónico** (Ley 1437 art. 59 + Decreto 088 art. 2.2.20.10).
9. **Protección de datos personales** (Ley 1581 de 2012) y **reserva** de la información.
10. **Integración con el SGDEA**: la notificación y su constancia son documentos del expediente y deben cumplir autenticidad, integridad, fiabilidad y disponibilidad.

---

## 7. Trámites en línea

### 7.1 El catálogo SUIT y cómo se consulta `[HECHO VERIFICADO]`

`[HECHO VERIFICADO]` **El SUIT (Sistema Único de Información de Trámites) es administrado por el Departamento Administrativo de la Función Pública (DAFP)**, no por MinTIC. Esto está confirmado textualmente en el **Anexo 5 §9.1**:

> "esta información corresponde a la **ficha informativa del trámite, OPA o servicio de consulta de información que la autoridad ha registrado en el Sistema Único de Información de Trámites SUIT que administra el Departamento Administrativo de la Función Pública** y que se visualiza en el portal Único del Estado Colombiano."

Y en **Anexo 5 §4.2 (Etapa 1)**:

> "La información general del trámite [...] publicada en Gov.co en la ficha informativa corresponde a la administrada y actualizada por la autoridad a través del proceso de registro y modificación de trámites en el **Sistema Único de Trámites SUIT del DAFP, entidad competente en la materia**."

`[HECHO VERIFICADO]` **El flujo de datos es SUIT → GOV.CO, nunca al revés:**

> "Para esto se incorpora un **catálogo dentro de GOV.CO**, que contiene los trámites, OPAs y servicios de consulta de información, **este catálogo se basa en los datos registrados por las autoridades en el SUIT** y a partir de esta información en el portal único del Estado Colombiano - GOV.CO se redirecciona a las URL correspondiente de cada uno de los trámites."
> — Anexo 5, §8.1

**Responsabilidad de la autoridad en la ficha (Anexo 5 §9.1) `[GUÍA]`:** "Asegurar y **certificar** por parte de la autoridad la actualización de esta información en el SUIT, **es requisito para integrarse a Gov.co**". Debe asegurar: alineación y congruencia de los insumos; información clara y actualizada según normatividad vigente; **correcta georreferenciación de los puntos de atención**; y que **los enlaces configurados sean efectivos**.

**Portal de trámites de GOV.CO:** las fichas se publican bajo `https://www.gov.co/tramites-y-servicios/T<idSUIT>` (estructura verificada en Anexo 5 §8.2 y en las rutas del propio sitio).

**Estado del checklist de integración en GOV.CO (Anexo 5 §10) `[GUÍA]`:** (a) leer los lineamientos; (b) priorizar trámites; (c) definir proceso de mejora; (d) identificar ajustes y elaborar plan de trabajo; (e) cumplir lineamientos; (f) implementar mejoras; (g) **diligenciar y presentar el formulario de solicitud de integración**; (h) atender observaciones de MinTIC; (i) verificar el funcionamiento; (j) monitoreo y mejora continua.

### 7.2 ¿Hay API o datos abiertos? `[HECHO VERIFICADO parcial]`

- **API oficial del SUIT para consulta pública de trámites: NO VERIFICADA.** No encontré documentación pública de una API del SUIT. La URL `https://www1.funcionpublica.gov.co/suit/` responde **HTTP 404** (22.047 bytes de página de error). **Pendiente de confirmar con el DAFP.**
- **Datos abiertos:** el portal oficial es **`https://www.datos.gov.co`** (plataforma Socrata). Verifiqué que funciona: `https://www.datos.gov.co/api/views/metadata/v1?q=tramites%20SUIT&limit=5` responde **HTTP 200** con metadatos en formato Socrata, incluyendo `id`, `name`, `description`, `dataUri` (p. ej. `https://www.datos.gov.co/resource/<id>`). **No verifiqué** un dataset concreto y oficial del catálogo SUIT completo; la búsqueda devolvió datasets de temáticas diversas.
- **API de fichas de trámites de GOV.CO `[HECHO VERIFICADO]`:** el propio portal expone `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/` (base leída del bundle `main.8fd5437b44dfad3f.js`), incluyendo `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/ConsultaCIIU/`. **No es una API documentada públicamente ni un contrato de servicio ofrecido a terceros**; es el consumo interno de la SPA. Requiere cabeceras de navegador. **No la trataría como API de integración soportada.**

### 7.3 Qué significa "trámite en línea" `[NORMA]`

`[HECHO VERIFICADO]` **Ley 2052 de 2020, art. 6** (citada textualmente en los considerandos del Decreto 088 de 2022):

> "los trámites **que se creen a partir de la expedición de dicha ley deberán realizarse totalmente en línea**, y, para los trámites existentes antes de la entrada en vigor de dicha ley y que no puedan realizarse totalmente en línea, se determinarán los **plazos y condiciones** por parte del Ministerio de Tecnologías de la información y las Comunicaciones."

`[HECHO VERIFICADO]` **Decreto 088 de 2022, art. 1, parágrafo** (sobre trámites que no pueden ser 100% en línea):

> "Aquellos trámites que por su naturaleza no puedan hacerse totalmente en línea, se entenderá que cumplen con la obligación establecida en el artículo 6 de la Ley 2052 de 2020, **cuando se encuentren en línea todos los pasos a realizar por los ciudadanos que sean susceptibles de ello al momento de su implementación**."

**Definiciones operativas relevantes del Decreto 088 de 2022, art. 2.2.20.3 `[NORMA]`:**

| Término | Definición textual |
|---|---|
| **Digitalización** | "el uso de medios digitales **con intervención humana** para el desarrollo de tareas o procesos relacionados con la gestión interna de los trámites (registro, procesamiento, almacenamiento, consulta, acceso y disposición de datos)." |
| **Automatización** | "la capacidad de un sistema para ejecutar una serie de tareas, de gestión interna de la autoridad, que soporta el trámite, las cuales originalmente son realizadas por seres humanos y pasan a ser ejecutadas **de manera autónoma** por una máquina o un sistema de información digital." |
| **Desmaterialización** | "la disposición en formato digital o electrónico, de documentos físicos producto de un trámite, o de certificados, constancias, paz y salvos o carnés, que se emiten respecto de cualquier situación de hecho o de derecho de un particular, los cuales deben cumplir con el **Principio de Equivalencia Funcional, previsto en la Ley 527 de 1999**." |
| **Interoperabilidad** | "la capacidad de las organizaciones para **intercambiar información y conocimiento** en el marco de sus procesos de negocio para interactuar hacia objetivos mutuamente beneficiosos." |
| **Registro Público** | incluye que "El registro deberá permitir la **expedición de una constancia** con la información allí contenida **por medios digitales**." |
| **Estampilla electrónica** | "un documento que se emite, paga, adhiere o anula de forma electrónica [...] es el documento idóneo para **acreditar el pago** del servicio recibido o del impuesto causado." |

**Advertencia:** el Decreto 088 de 2022 **no incluye una definición formal aislada del término "trámite en línea"**. El significado se deriva de la Ley 2052 art. 6 (todos los pasos por medios digitales, con la salvedad del parágrafo del art. 1 del Decreto 088) y de la escala de 6 niveles del Anexo 5 (§4.5). **Reporto esto como precisión, no como hallazgo de una definición explícita.**

### 7.4 Plazos de digitalización que aplican a una alcaldía `[NORMA]`

`[HECHO VERIFICADO]` **Decreto 088 de 2022, art. 2.2.20.7, numeral 3 — "Plazos para digitalizar trámites por parte de autoridades territoriales":**

| Grupo de entidades | Bloque 1 (30%) | Bloque 1+2 (60%) | Bloque 1+2+3 (100%) |
|---|---|---|---|
| **Alcaldía-Avanzado**, Gobernaciones, Unidades Administrativas Especiales, Distrito Capital | 77 meses (**hasta mayo/2028**) | 115 meses (**hasta julio/2031**) | 147 meses (**hasta marzo/2034**) |
| **Alcaldía-Básico, Alcaldía-Intermedio**, Establecimientos Públicos-Avanzado, EICE-Avanzado, ESE-Avanzado, SEM-Avanzado, ESP-Avanzado, Instituciones Universitarias, Áreas Metropolitanas | 51 meses (**hasta marzo/2026**) | 81 meses (**hasta septiembre/2028**) | 108 meses (**hasta diciembre/2030**) |
| ESE-Básico/Intermedio, Establecimientos Públicos-Básico/Intermedio, ESP-Básico/Intermedio, IPS, Otras Entidades Descentralizadas | 26 meses (**hasta febrero/2024**) | 44 meses (**hasta agosto/2025**) | 63 meses (**hasta marzo/2027**) |

**Condiciones generales de priorización (art. 2.2.20.6) `[NORMA]`:**
- Bloque 1 = **30% de los trámites de mayor prioridad**; Bloque 2 = 30% de prioridad intermedia; Bloque 3 = 40% de menor prioridad.
- Criterio de priorización: **"el nivel de demanda del trámite en términos del número de solicitudes por año y mayor impacto en los ciudadanos"**.
- Criterio adicional opcional: trámites con **alto impacto presupuestal y consecuencia económica y social importante**.
- Plazos de planeación: **hasta el 31 de enero de 2022** para las actividades de planeación.
- **"Las entidades territoriales podrán solicitar ampliación de los plazos o modificación de los lineamientos de manera motivada"** — sujeto a condiciones de **conectividad, infraestructura, tecnologías y disponibilidad de presupuesto**.
- **Art. 2.2.20.11 — Recursos:** "Las autoridades atenderán **con cargo a su presupuesto** los gastos por **infraestructura, integración y operación** que demande el proceso de digitalización y automatización de los trámites."

**→ Para el diseño de la sede:** el plazo del bloque 1 para una Alcaldía-Básico/Intermedio **venció en marzo de 2026**. Al 2026-09-30, el bloque 1 y probablemente el bloque 2 deberían estar en producción. El 100% vence en diciembre de 2030. Esto es un argumento presupuestal fuerte.

---

## 8. Requisitos de integración de una entidad

### 8.1 Proceso formal de integración de sede electrónica `[GUÍA — Anexo 2, "Planes de integración"]`

`[HECHO VERIFICADO]` Pasos textuales:

1. Leer y entender los lineamientos definidos por MinTIC para la unificación e integración de la sede electrónica.
2. Identificar los ajustes requeridos y **elaborar un plan de trabajo**.
3. Unificar y adecuar la sede electrónica de acuerdo con los requisitos mínimos establecidos.
4. **Diligenciar y presentar el formulario de solicitud de integración al Portal Único del Estado colombiano - GOV.CO.**
5. **Atender las observaciones que MinTIC emita** como resultado de la revisión.
6. Verificar el adecuado funcionamiento de la sede electrónica ya integrada.
7. Monitoreo y mejora continua.

> "Como en otros ejercicios de integración [...] las autoridades obligadas deben **formular el plan que les permita definir la fecha de integración** de su sede electrónica al Portal Único del Estado Colombiano - GOV.CO."

> "Las entidades del orden territorial **cuyas sedes electrónicas se encuentran implementadas a través de la plataforma que provee MinTIC (gov.co/territorial)**, deberán establecer el plan de integración, alineado con los mecanismos que se definen en esta guía y sus anexos."

**Verificación por MinTIC `[GUÍA]`:** "previa verificación por parte de MinTIC del cumplimiento de los lineamientos dados en esta guía y sus anexos. Los resultados de esta verificación **serán compartidos con la entidad para aplicar posibles ajustes o la aprobación de la integración solicitada**."

### 8.2 Proceso formal de integración de trámites `[GUÍA — Anexo 5 §9, §10]`

`[HECHO VERIFICADO]` Tres pasos:

- **Paso 1 — Actualización/publicación de la ficha informativa en el SUIT.** "requisito indispensable para el acondicionamiento, transformación e integración". Se debe ajustar el contenido **en lenguaje claro** según la Guía de Lenguaje Claro del **Programa Nacional del Servicio al Ciudadano (DNP)**.
- **Paso 2 — Acondicionamiento o transformación del trámite.** Debe cumplir los lineamientos de diseño gráfico (Directiva Presidencial 03 de 2019 cuando aplique + Anexo 5.1). Se recomienda usar el **Repositorio de Archivos Estáticos (CDN)**: `https://cdn.www.gov.co/v2/pages/inicio` y la biblioteca `https://www.gov.co/biblioteca/`.
- **Paso 3 — Integración a GOV.CO.** "debe solicitar **formalmente** la integración a GOV.CO atendiendo los procedimientos establecidos por MinTIC, **diligenciando el formulario de solicitud de integración** al Portal Único del Estado Colombiano - GOV.CO, **confirmando que cumple con los parámetros** que se han dispuesto en la presente guía".

### 8.3 Reparto de responsabilidades entidad ⟷ GOV.CO `[GUÍA — Anexo 5 §9.2, tabla textual]`

| Autoridad (la entidad) | Portal GOV.CO |
|---|---|
| Generación de los desarrollos requeridos en el acondicionamiento o transformación del trámite garantizando el uso de los recursos y SCD disponibles, **evitando duplicar esfuerzos** al implementar funcionalidades ya habilitadas | **Habilitar los mecanismos técnicos necesarios para integrar en GOV.CO los desarrollos y servicios digitales entregados por la entidad** que cumplan los requisitos |
| Implementación de los elementos de diseño de la línea gráfica definidos por MinTIC (estilo, usabilidad, arquitectura de información) para homologar la interfaz | **Gestión de la redirección del trámite desde GOV.CO hasta la aplicación donde se desarrolla el trámite** habilitado por la autoridad |
| Aplicación de los requisitos definidos en la guía y sus anexos | **Implementar en GOV.CO los mecanismos necesarios para habilitar el single sign on/out** con los trámites que requieran autenticación |
| **Implementar los mecanismos necesarios para habilitar el single sign on/out con GOV.CO** en los trámites que requieran autenticación | — |
| **Implementar un servicio de seguimiento del estado y avance** del proceso (para respuestas no inmediatas), con equivalencia de las 4 etapas | — |
| Aseguramiento de la calidad de la información y **disponibilidad de los servicios digitales** | — |
| Implementación de los **controles de seguridad informática** | — |
| Coordinar y retroalimentar a MinTIC sobre el avance del proceso de acondicionamiento | — |
| **Cuando la página o URL cambie o sea retirada de operación, coordinar lo necesario para actualizar oportunamente en SUIT y en Gov.co** | — |

### 8.4 Requisitos técnicos de aceptación de la sede electrónica

#### 8.4.1 Criterios de aceptación funcional `[GUÍA — Criterio de aceptación Funcional V4]`

`[HECHO VERIFICADO]` Seis criterios textuales:

1. **Vínculos no rotos** que direccionen a las páginas solicitadas.
2. Al direccionar a una página externa, **abrir en nueva pestaña** (recomendación).
3. **Campos obligatorios** con mensaje de error si no se diligencian.
4. En los vínculos de políticas debe presentarse documentación de: **(a)** Términos y condiciones, **(b)** Privacidad y tratamiento de datos, **(c)** Derechos de autor y/o autorización de uso sobre los contenidos.
5. En **todos los formularios donde se capturen datos personales**: **aviso de privacidad y autorización para el tratamiento de datos**, y **Captcha**.
6. **El buscador debe buscar DENTRO de la sede.** "No se puede contemplar un buscador que realice la búsqueda en Google."

#### 8.4.2 Criterios de aceptación de seguridad — 12 controles `[GUÍA — Criterio de aceptación Seguridad V7]`

`[HECHO VERIFICADO]` Lista textual completa:

1. **Certificado SSL** debidamente instalado y configurado.
2. Validación frecuente del uso y exposición de **puertos abiertos** hacia internet, garantizando filtrado correcto.
3. **Control de tasa de reintentos por login fallido** para evitar ataques de adivinación de contraseñas o usuarios débiles y **DDoS**.
4. Elemento tipo **CAPTCHA en todos los formularios** donde se capturen datos de la ciudadanía.
5. Atributos **`HttpOnly` y `Secure`** en el uso de **cookies**.
6. Sección en el **footer** con documentación de: (a) Términos y condiciones de uso, (b) Seguridad y Privacidad, (c) Protección y tratamiento de datos personales, (d) Uso de Cookies, (e) Derechos de Autor y uso sobre contenidos.
7. **Restringir la escritura de archivos en el servidor web** mediante permisos de solo lectura.
8. **Deshabilitar métodos HTTP peligrosos: PUT, DELETE, TRACE, OPTIONS.**
9. **Sanitización de parámetros de entrada**: eliminación de etiquetas, saltos de línea, espacios en blanco y caracteres especiales que conforman un "script".
10. **Sanitización de caracteres especiales** (secuencia de escape de variables en el código).
11. **Mensajes de error genéricos** que no revelen tecnología usada, excepciones o parámetros que disparen el error.
12. **Cabeceras de seguridad**: `Content-Security-Policy (CSP)`, `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Strict-Transport-Security (HSTS)`, `Public-Key-Pins (HPKP)`, `Referrer-Policy`, `Feature-Policy`; para cookies, `secure` y `HttpOnly`.

> **Nota sobre HSTS y HPKP vs. el Anexo 2:** el Anexo 2 lista en su §4.3.2.4 prácticamente los mismos controles. Dos diferencias notables: el Anexo 2 **omite `OPTIONS`** de la lista de métodos peligrosos (dice "PUT, DELETE, TRACE") mientras la infografía V7 **sí incluye OPTIONS**; y el Anexo 2 añade requisitos que la infografía no recoge: **certificado SSL con validación de organización**, **antivirus** en la infraestructura, **hardening** de sistemas operativos/servidor web/base de datos, **protección del código fuente contra ingeniería inversa**, **políticas y procedimientos de copias de respaldo**, **monitoreo de seguridad** (escaneo de vulnerabilidades, listas negras, análisis de tráfico), **control de escalamiento de privilegios**, gestión de riesgos conforme al **Modelo de Seguridad y Privacidad de la Información (MSPI)**, y **planes de contingencia**.

**Controles adicionales exigidos solo por el Anexo 2 (§4.3.2.4) `[GUÍA]`** — relevantes y frecuentemente omitidos:
- Publicar la **política de datos personales y aviso de privacidad** (Ley 1581 de 2012).
- **Preservación documental a largo plazo** conforme a las normas del **Archivo General de la Nación**.
- Implementación de mecanismos de autenticación con **contraseñas robustas y renovaciones periódicas**.

#### 8.4.3 Otros atributos de calidad exigidos por el Anexo 2

`[HECHO VERIFICADO]`

- **Usabilidad (§4.3.2.1):** **mapa del sitio** accesible desde el footer y actualizado; contraste de brillo y color; tipografías uniformes; **miga de pan**; vínculo a la página de inicio; **destacar los vínculos visitados**; calidad del código sin tags ni vínculos rotos; **ejemplos de diligenciamiento** en los formularios; **etiquetado de campos**; control de **ventanas emergentes**; **mensajes de confirmación**; **página personalizada de error 404**; **buscador interno**; lenguaje claro (Guía de Lenguaje Claro del DNP: `https://www.dnp.gov.co/programa-nacional-del-servicio-al-ciudadano/Paginas/Lenguaje-Claro.aspx`).
- **Interoperabilidad (§4.3.2.2):** la sede debe contar con un **plan de estandarización de los formularios de captura de información y de servicios de intercambio de información** "con el lenguaje común de intercambio". **→ Implicación: los formularios deben diseñarse para mapear al lenguaje común / Marco de Interoperabilidad, no como formularios ad hoc.**
- **Accesibilidad (§4.3.2.3):** lectura por lector de pantalla; **barra de accesibilidad** (contraste, tamaño de letra); adaptable/responsive; distinguible; **accesible por teclado** (navegación con tabulación); tiempo suficiente; legibilidad; **ayuda en la entrada de datos** (prevención y corrección de errores, autollenado); **etiquetas Title y Alt** en botones, enlaces, imágenes, videos y textos.
- **Disponibilidad:** exigida como atributo, con planes de contingencia (anexo 2 §4.3.2.4 final).
- **Infraestructura tecnológica (§4.3.2.6):** acceso a través de **IP pública IPv4 e IPv6** de acuerdo con la **Resolución 2710 de 2017**; **DNS con resolución de doble pila**.
- **Neutralidad (§4.3.2.7):** "debe ser implementada con independencia de los navegadores [...] y debe operar en **al menos tres (3) de los navegadores más utilizados**."
- **Calidad de la información (§4.3.2.8):** actualizada; veraz; "auténtica, íntegra, fiable, oportuna, objetiva, veraz, completa, reutilizable, procesable y disponible en formatos accesibles"; escrita en lenguaje claro.

#### 8.4.4 Contenido y estructura obligatorios de la sede — Anexo 2.1 y §4.1 `[GUÍA]`

`[HECHO VERIFICADO — Anexo 2.1, "de obligatorio cumplimiento"]`:

> "En este documento encontrarás los lineamientos de acondicionamiento gráfico generales para sedes electrónicas, el cual contiene las instrucciones de identidad visual de **obligatorio cumplimiento** por parte de las autoridades. Este anexo hace parte integral de la guía de integración a sedes electrónicas atendiendo lo establecido en **el artículo 14 de Decreto 2106 del 2019**."

- **Barra superior (top bar):** barra GOV.CO con **botón de Gobierno que redirecciona a GOV.CO**; logo de la autoridad (arriba a la izquierda, **altura máxima 50 px**, ancho proporcional); buscador general.
- **Menú obligatorio:** botones de **TRANSPARENCIA Y ACCESO A INFORMACIÓN PÚBLICA**, **SERVICIOS A LA CIUDADANÍA** y **PARTICIPA**. La autoridad puede añadir más botones.
- **Carrusel** en el home.
- **Colores:** conforme a la **Guía de Sistema de Diseño de Gobierno de Colombia**, a cada entidad se le ha asignado un color, aplicable en contenedor de texto del carrusel, barra inferior del menú, footer, iconografía. **Los colores propios de la autoridad NO pueden usarse en botones, texto, campos de formulario, fondos ni etiquetas**, que deben conservar los estilos del **KIT UI de GOV.CO**.
- **Módulos de información:** imágenes en proporción 4:3 o 16:9; **título no superior a 100 caracteres** y **descripción no superior a 170 caracteres**; los módulos de noticias/eventos deben **incluir la fecha de publicación**.
- **Footer:** datos de contacto y la documentación de políticas.
- **Secciones de la sede (§4.1 del Anexo 2):** Transparencia y acceso a la información pública; Atención y servicio a la ciudadanía; Participa; Noticias.
- **Menú Participa — 6 subcategorías (§4.1.2.3):** (1) Participación para la identificación de problemas y diagnóstico de necesidades; (2) Planeación y/o presupuesto participativo; (3) Participación y consulta ciudadana de proyectos, normas, políticas o programas; (4) Colaboración e innovación abierta; (5) Rendición de cuentas; (6) Control ciudadano.
- **Políticas exigidas (§4.1.6):** Términos y condiciones; Política de privacidad y tratamiento de datos personales; Política de derechos de autor y/o autorización de uso sobre los contenidos.

### 8.5 Registro de la entidad y credenciales `[HECHO VERIFICADO — parcial]`

- **Registro del Administrador Entidad:** existe un **"Manual Instructivo para registro y autenticación Administrador Entidad Ver 2.0"** en la caja de herramientas (`https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/a72ee86c-592e-46b8-9518-6ddc42ba2e75-28275707-db59-49e9-b5d1-cc6e3f76d391-Manual%20Instructivo%20para%20registro%20y%20autenticaci%C3%B3n%20Administrador%20Entidad%20Ver%202.0.pdf`). Proceso de registro de las credenciales de la entidad para gestionar su plan de integración.
- **Formulario de solicitud de integración:** **mencionado repetidamente** en Anexo 2 y Anexo 5 como requisito del Paso 3, pero **no encontré su URL pública**. Se obtiene a través de los mecanismos que habilite MinTIC / la AND.
- **Endpoint de registro detectado en el bundle `[HECHO VERIFICADO — código, no documentación]`:**
  - `https://admin.www.gov.co/Autenticacion/Autenticacion/RegistroAutoridad` — configurado en el bundle como `urlPlanIntegracion`. **Es una ruta de la aplicación administrativa, no una API documentada.** No la probé (requiere sesión).
- **Autenticación del portal `[HECHO VERIFICADO — configuración del bundle]`:** el propio GOV.CO usa **OIDC**: `authIssuer: "https://autenticaciondigital.and.gov.co"`, `clientID: "GOVCOHome"`. **→ Confirma que el emisor OIDC opera en el dominio de la AND**, consistente con §2.3.
- **"Cómo registrarse en el Plan de Integración"** (`34_D_PDF_como Registrarse PlanIntegra_20221219_V1.pdf`) — documento de la caja de herramientas sobre el registro en el plan.

### 8.6 Convenios y credenciales — lo que NO está público

`[NO VERIFICADO]` Las guías **no publican**: el texto del formulario de solicitud de integración, un modelo de convenio, un acuerdo de niveles de servicio (SLA), ni las condiciones de entrega de credenciales (client_id / secret) para autenticación digital. Todo esto se gestiona en el proceso formal con MinTIC/AND. Ver §Lo que NO pude verificar.

---

## 9. Tabla resumen: componente → qué es → URL → disponibilidad

| # | Componente | Qué es | URL | Disponibilidad |
|---|---|---|---|---|
| 1 | **Resolución 2893 de 2020** (texto) | Norma que adopta las guías de integración a GOV.CO | `https://www.gov.co/uploads/Resolucion%2002893%20de%202020.pdf` | ✅ **Pública** |
| 2 | **Anexo 1** – Lineamientos de ventanillas, portales y sedes | Lineamientos para estandarizar y unificar | `https://gobiernodigital.mintic.gov.co/692/articles-161264_Anexo_1_Resolucion_2893_2020.pdf` | ✅ **Pública** |
| 3 | **Anexo 2** – Guía técnica de integración de sedes electrónicas | **El documento central**: mecanismo de redirección, arquitectura mínima, atributos de calidad, plan de integración | `https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.docx` | ✅ **Pública** (.docx; la versión `.pdf` da HTTP 520) |
| 4 | **Anexo 2.1** – Guía de diseño gráfico para sedes electrónicas | Identidad visual de **obligatorio cumplimiento** | `https://www.mintic.gov.co/portal/715/articles-152200_anexo_2_1.pdf` | ✅ **Pública** |
| 5 | **Anexo 3 / 3.1** – Guías de ventanillas únicas | Guía técnica y de diseño gráfico de ventanillas | — | ❌ **No recuperado** |
| 6 | **Anexo 4 / 4.1** – Guías de portales transversales | Guía técnica y de diseño gráfico | — | ❌ **No recuperado** |
| 7 | **Anexo 5** – Guía de integración de Trámites, OPAs y Consultas | Modelo de redireccionamiento, 4 etapas, 6 niveles, responsabilidades | `https://www.mintic.gov.co/portal/715/articles-152200_anexo_5.pdf` | ✅ **Pública** |
| 8 | **Anexo 5.1** – Guía de diseño gráfico de trámites | Diseño gráfico de trámites integrados | `https://gobiernodigital.mintic.gov.co/692/articles-272743_recurso_1.pdf` | ✅ **Pública** (el PDF 5.1 directo da HTTP 520) |
| 9 | **Caja de herramientas – API de contenido** | API REST pública (sin auth, con cabeceras) que sirve el árbol y los recursos | `https://api-interno.www.gov.co/api/caja-herramientas/...` | ⚠️ **Pública pero NO documentada** (reconstruida del bundle) |
| 10 | **Caja de herramientas – menú** | Árbol de niveles de integración | `https://api-interno.www.gov.co/api/caja-herramientas/CajaHerramientas/Nivel/ObtenerMenuNiveles` | ⚠️ **Pública, no documentada** |
| 11 | **Caja de herramientas – recursos** | Listado de documentos por nivel | `.../CajaHerramientas/Recurso/ObtenerListadoRecurso/{id}` (POST `{}`) | ⚠️ **Pública, no documentada** |
| 12 | **Biblioteca GOV.CO – buscador** | Buscador de recursos de la biblioteca (140 registros) | `https://api-interno.www.gov.co/api/biblioteca/Recursos/Buscar` | ⚠️ **Pública, no documentada** |
| 13 | **Criterios de aceptación funcional V4** | 6 criterios funcionales de aceptación | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/0d8d87fd-0758-4c80-b33e-3dac450f390f-ee3e6b1c-232f-4621-a1ac-81c5416ee413-Criterio%20de%20aceptacio%CC%81n%20Funcional_V4.pdf` | ✅ **Pública** |
| 14 | **Criterios de aceptación seguridad V7** | 12 controles de seguridad | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/0e18c5c4-4047-4584-9ac7-76566b26ee2e-1a9f92a9-c1e4-4bc5-9283-3993887034a9-Criterio%20de%20aceptacio%CC%81n%20Seguridad_V7.pdf` | ✅ **Pública** |
| 15 | **Criterios de aceptación diseño V6** | Diseño de carruseles | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/6d09c955-b77c-4802-9bf8-57fb6ceba04c-acabc778-6138-4651-969f-5774190eebcb-Criterio%20de%20aceptacio%CC%81n%20Disen%CC%83o_V6.pdf` | ✅ **Pública** |
| 16 | **Presentación Integración a GOV.CO V4 (2025)** | Marco normativo y 4 fases | `https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/18.+Presentacio%CC%81n+Integracio%CC%81n+a+GOV.CO_V4.pdf` | ✅ **Pública** |
| 17 | **Redireccionamiento (PPTX)** | Modelo de redireccionamiento y plan de trabajo | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/b0cce1ff-1659-4d8d-acc5-b109fa475378-6bf7e3cb-2629-4361-ad72-fd98de74867c-Redireccionamiento.pptx` | ✅ **Pública** |
| 18 | **Manual registro Administrador Entidad V2** | Registro y autenticación del administrador de la entidad | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/a72ee86c-592e-46b8-9518-6ddc42ba2e75-28275707-db59-49e9-b5d1-cc6e3f76d391-Manual%20Instructivo%20para%20registro%20y%20autenticacio%CC%81n%20Administrador%20Entidad%20Ver%202.0.pdf` | ✅ **Pública** |
| 19 | **Cómo registrarse en el Plan de Integración** | Registro en el plan de integración | `https://govco-prod-webutils.s3.us-east-1.amazonaws.com/uploads/2025-09-01/06.+34_D_PDF_como+Registrarse+PlanIntegra_20221219_V1.pdf` | ✅ **Pública** |
| 20 | **ABC Guía Vinculación AUTH V6** | Vinculación al SCD de Autenticación | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/92ff645a-a299-4745-bb9c-287343ab60bb-853f0a5d-28da-4911-a33b-7af233563ed3-ABC%20Gui%CC%81a%20Vinculacio%CC%81n%20AUTH_V6.pdf` | ✅ **Pública** |
| 21 | **Proceso Vinculación SCD – Interoperabilidad V6** | Vinculación al SCD de Interoperabilidad | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/22f700dc-ba31-4250-8591-12c6f05cbdfe-45baaa40-c0ab-4517-82f6-50948968fac3-Proceso%20Vinculacio%CC%81n%20%20SCD%20-%20Interoperabilidad_V6.pdf` | ✅ **Pública** |
| 22 | **ABC de Librerías de Autenticación Digital V4** | Qué son las librerías, cómo acceder, a quién escribir | `https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/98680787-6f16-44e1-a97a-6c0220ca20f8-dc8b0b6b-34bb-430d-b532-e9640f7f0718-34_D_PDFI_ABC%20biblioteca%20de%20Autenticacio%CC%81n%20Digital_20220926_V4.pdf` | ✅ **Pública** (las librerías en sí se piden a la AND) |
| 23 | **Guía de integración de prestadores privados de SCD Especiales** (jun-2022) | **Especificación técnica más detallada**: OIDC, OAuth2, RFCs, X-Road, requisitos | `https://gobiernodigital.mintic.gov.co/692/articles-146484_recurso_2.pdf` | ✅ **Pública** (aplica a prestadores privados, no a la entidad usuaria) |
| 24 | **Librerías de Autenticación Digital** | Componente de conexión al autenticador | — | 🔒 **Requiere solicitud a la AND** (`soporteccc@mintic.gov.co`) |
| 25 | **Formulario de solicitud de integración GOV.CO** | Formulario formal obligatorio (Paso 3) | — | 🔒 **Requiere acceso vía MinTIC/AND**; URL no pública |
| 26 | **Manual del Servicio de PSE (Hosting)** | Contrato técnico canónico de PSE | — | 🔒 **Restringido** — anexo confidencial del Reglamento de ACH |
| 27 | **Reglamento de Acceso ACH Colombia v6** (jul-2024) | Requisitos legales, financieros y técnicos para contratar servicios de ACH | `https://www.achcolombia.com.co/documents/1176249/1187215/Reglamento%20de%20acceso%20V6%2020240730.pdf/a7c2f89a-5d55-97cb-a7af-8db23f7c2df2?t=1725047077448` | ✅ **Pública** |
| 28 | **Guía de implementación PSE REST (patrón)** | Patrón de integración de PSE vía pasarela: redirección, firma, IPN | `https://secure.payty.com/doc/content/payty/es-CO/payment-method/pse-rest/redirection-form/uuj1675761072425.pdf` | ⚠️ **Pública pero de tercero** (pasarela privada, no ACH) |
| 29 | **Documentación API Botón de Pagos (Credibanco)** | API de botón de pagos de un adquirente | `https://developer.credibanco.com/documents/Documentation-BotonDePagos.pdf` | ⚠️ **Pública pero de adquirente privado** |
| 30 | **Servicio transversal de pagos de GOV.CO** | — | — | ❌ **NO EXISTE** (ausencia verificada) |
| 31 | **Ley 1437 art. 56 mod. Ley 2080 de 2021** | Notificación electrónica (norma vigente) | `https://www.funcionapublica.gov.co/eva/gestornormativo/norma.php?i=156590` | ✅ **Pública** |
| 32 | **Ley 2080 de 2021** (texto completo) | Modifica 24 artículos de la Ley 1437 (arts. 56, 59, 60, 60A, 61, 65…) | `https://www.suin-juriscol.gov.co/viewDocument.asp?id=30040345` | ✅ **Pública** |
| 33 | **Decreto 491 de 2020** | Notificación/comunicación electrónica **durante la emergencia sanitaria** | `https://www.funcionapublica.gov.co/eva/gestornormativo/norma.php?i=111114` | ✅ **Pública** (alcance temporal agotado) |
| 34 | **Decreto 088 de 2022** | Digitalización/automatización de trámites y realización en línea; plazos por tipo de entidad | `https://www1.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=175866` | ✅ **Pública** |
| 35 | **Modelo de Requisitos SGDEA (AGN, 2024)** | Requisitos del SGDEA; estructura de número de radicado; expediente electrónico | `https://www.archivogeneral.gov.co/sites/default/files/Estructura_Web/3-transp-act/7-datos-abiertos/Modelo_Requisitos-2024.pdf` | ✅ **Pública** (alcance institucional del AGN) |
| 36 | **Normalización de Formas y Formularios Electrónicos (AGN, 2024)** | Normalización de formularios electrónicos | `https://www.archivogeneral.gov.co/sites/default/files/Estructura_Web/3-transp-act/7-datos-abiertos/Normalizacion_Formas_Formularios_Electronicos-2024.pdf` | ✅ **Pública** |
| 37 | **Guía de Gestión para Expedientes y Documentos Electrónicos (AGN)** | Guía técnica de expedientes electrónicos | `https://mgd.archivogeneral.gov.co/wp-content/uploads/Caja_de_Herramientas_AGN-V2/docs/2.%20planeacion/DOCUMENTOS%20TECNICOS/GUIA%20DE%20GESTION%20PARA%20EXPEDIENTES%20Y%20DOCUMENTOS%20ELECTRONICOS.pdf` | ❌ **DNS no resuelve** (`mgd.archivogeneral.gov.co`) |
| 38 | **SUIT (Sistema Único de Información de Trámites)** | Catálogo fuente de fichas de trámites; administrado por el **DAFP** | `https://www1.funcionpublica.gov.co/suit/` | ⚠️ devuelve **HTTP 404**; portal de consulta no localizado |
| 39 | **Datos Abiertos Colombia** | Portal de datos abiertos (Socrata) | `https://www.datos.gov.co` (API: `/api/views/metadata/v1`) | ✅ **Pública**; dataset SUIT específico no confirmado |
| 40 | **Fichas de trámites GOV.CO (API)** | API interna del portal para fichas de trámites | `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/` | ⚠️ **Interna, no soportada como API pública** |
| 41 | **X-Road 6.25 Colombia** | Plataforma de interoperabilidad | — | 🔒 **Requiere vinculación con la AND**; manuales no públicos |
| 42 | **Prestadores de SCD Especiales** | Autenticación, carpeta ciudadana, interoperabilidad privados | — | 🔒 **Requieren habilitación por MinTIC** (Res. 1951 de 2022) |
| 43 | **Resolución 2160 de 2020** | Guía de lineamientos de los SCD y guía de vinculación | — | ❌ **No recuperado** (ver §Lo que NO pude verificar) |

---

## 10. Lo que NO pude verificar

Sección de honestidad metodológica. Todo lo listado aquí **no debe usarse como hecho** sin confirmación.

### 10.1 Páginas y documentos que fallaron o no cargaron

| URL | Código / fallo | Observación |
|---|---|---|
| `https://www.gov.co/caja-de-herramientas/integracion` | **HTTP 200 pero sin contenido** (5.038 bytes, solo el título) | SPA Angular; requiere ejecutar JavaScript. El contenido real se obtuvo por su API. |
| `https://www.gov.co/biblioteca/recurso/sedes-electronicas` | **HTTP 200 pero sin contenido** | Misma causa |
| `https://www.gov.co/biblioteca/recurso/INTEGRO/sedes-electronicas` | **HTTP 200 pero sin contenido** | Misma causa |
| `https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.pdf` | **HTTP 520** | La versión `.docx` sí funcionó |
| `https://www.mintic.gov.co/portal/historico/articles-152200_anexo_2.docx` | **HTTP 520** | Bloqueó `web_fetch` por redirección cross-origin a **http** |
| `https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=165270` | **Certificado SSL inválido** (`unable to get local issuer certificate`) + luego **"norma no disponible"** | El ID `i=165270` para la Ley 1437 no es válido en ese gestor |
| `https://apps.procuraduria.gov.co/gi/gi/docs/decreto_0491_2020.htm` | Recuperado **HTTP 200** y usado como fuente del Decreto 491 | — |
| `https://www1.funcionpublica.gov.co/suit/` | **HTTP 404** | El portal SUIT no está en esa ruta |
| `https://www.datos.gov.co/resource/metadata.json` | **HTTP 404** | Endpoint incorrecto; la API válida es `/api/views/metadata/v1` |
| `https://www.secretariasenado.gov.co/senado/basedoc/ley_1437_2011_pr012.html` | **HTTP 404** | Ruta no válida |
| `https://mgd.archivogeneral.gov.co/...GUIA%20DE%20GESTION%20PARA%20EXPEDIENTES%20Y%20DOCUMENTOS%20ELECTRONICOS.pdf` | **DNS: `Could not resolve host`** | El subdominio `mgd.archivogeneral.gov.co` no resolvió en dos intentos |
| `https://api-interno.www.gov.co/api/...` sin cabeceras de navegador | **HTTP 403 Forbidden** | Requiere `Referer` y `Origin` de `https://www.gov.co`. Con ellas, 200. |
| `https://www.mintic.gov.co/...` en general | **Timeout / HTTP 520 intermitentes** | El servidor de MinTIC no es estable desde el entorno de consulta |
| `https://gobiernodigital.mintic.gov.co/portal/Noticias/...` | HTTP 200 | OK |

### 10.2 Documentos buscados y NO encontrados

1. **Anexo 3 y Anexo 4 de la Resolución 2893** (ventanillas únicas y portales de programas transversales) y sus versiones de diseño gráfico 3.1 y 4.1. **No recuperados.** Se intentaron patrones `articles-152200_anexo_3/4`, `_recurso_N`, y espejos. La caja de herramientas sólo expone una presentación de "Puntos claves VENTANILLAS" para ese tema.
2. **Resolución 2160 de 2020 completa** — Guía de lineamientos de los Servicios Ciudadanos Digitales y su Anexo 2 (Guía para la Vinculación y Uso de los SCD). Es **el documento que realmente aplica a una entidad pública** para vincularse a los SCD. Consulté la referencia en `https://normograma.mintic.gov.co/mintic/docs/resolucion_mintic_2160_2020.htm` y `https://compilacionjuridica.shd.gov.co/compilacion/docs/resolucion_mintic_2160_2020.htm` pero **no recuperé el texto íntegro ni sus anexos**. **GAP IMPORTANTE.**
3. **Guía de vinculación de una entidad pública** (no prestador privado) a la Autenticación Digital, con endpoints concretos. **GAP IMPORTANTE.** Sólo pude acreditar el marco OIDC/OAuth2 a través de la guía de **prestadores privados**.
4. **"Formulario Único de Trámites (FUID)"** — nombre usado por el usuario. **No encontré ningún documento oficial del MinTIC o del DAFP con ese nombre ni esa sigla.** Lo que sí existe y documenté con fuente es: **"formulario único"** y **"trámite modelo"** como **definiciones legales** en el Decreto 088 de 2022 art. 2.2.20.3 (defs. 6, 7 y 14). **Probable discrepancia de nomenclatura en el enunciado; conviene aclararlo con la entidad.**
5. **Especificaciones OpenAPI/Swagger de las APIs de GOV.CO** — **no existen públicamente** (ausencia comprobada tras revisar los bundles y los recursos).
6. **SDK oficial de GOV.CO/MinTIC** para sede electrónica — **no existe** (ausencia comprobada).
7. **Sandbox/entorno de pruebas de GOV.CO** — **no documentado públicamente** para integración de sede.
8. **URL pública del formulario de solicitud de integración al Portal Único** — **no encontrada**, aunque su existencia es requisito citado en Anexo 2 y Anexo 5.
9. **Modelo de convenio / acuerdo de niveles de servicio (SLA)** entre la entidad y MinTIC/AND — **no encontrado**.
10. **Manual del Servicio de PSE de ACH Colombia** — **restringido**, marcado confidencial. Sin él, la firma (`signature`) y el contrato de notificación (IPN) sólo puedo documentarlos a través del patrón de una pasarela de terceros.
11. **Documentación oficial de 4-72 sobre notificación electrónica** — **no localizada**.
12. **Documentación oficial de Certicámara sobre notificación electrónica de actos administrativos** — **no localizada**.
13. **Norma colombiana que prohíba explícitamente almacenar datos de tarjeta** — **no encontrada**. La garantía se deriva del diseño de PSE (transferencia por redirección), no de una norma verificada. Si se usaran tarjetas, aplicaría PCI DSS (internacional, no colombiano) — **no verifiqué remisión normativa colombiana a PCI DSS**.
14. **Servicio transversal de pagos de GOV.CO** — **no existe** (ausencia verificada en los 10 niveles de la caja de herramientas y en los anexos 1, 2, 2.1 y 5).
15. **API pública oficial del SUIT** — **no encontrada**; la URL probada devuelve 404.
16. **Dataset de datos abiertos del catálogo SUIT completo** en datos.gov.co — **no confirmado** (la búsqueda por "trámites SUIT" devolvió datasets heterogéneos, ninguno identificado claramente como el catálogo SUIT oficial).
17. **Definición formal explícita de "trámite en línea"** en el Decreto 088 de 2022 — **no existe como definición aislada**; se deriva de la Ley 2052 art. 6 y del parágrafo del art. 1 del Decreto 088.
18. **Formato único nacional de número de radicado** — **no impuesto por norma**. El formato `1-AAAA-XXXXX` / `2-AAAA-XXXXX` proviene del Modelo de Requisitos del **AGN para su propio SGDEA**; no verifiqué una norma de aplicación general.
19. **Estándar nacional de expediente electrónico** distinto de la definición legal (Ley 1437 art. 59) y los lineamientos del AGN. La **Guía de Gestión para Expedientes y Documentos Electrónicos del AGN** no se pudo descargar (DNS). **GAP IMPORTANTE** para el diseño del expediente.
20. **Recursos del nivel 34 (Portales específicos de programas transversales)** — el listado devolvió 0 elementos aunque el nivel existe en el menú. **No pude documentar su contenido.**

### 10.3 Pendiente de confirmar con la entidad / con MinTIC / con la AND

1. **Versión vigente de cada anexo de la Resolución 2893** — el art. 8 de la Resolución permite actualizaciones sucesivas. **Debe solicitarse la versión vigente a la Dirección de Gobierno Digital del MinTIC antes de diseñar**, porque los anexos recuperados son de 2020 (Anexo 2, 2.1, 5) y ya existen versiones posteriores de algunos recursos de la caja (p. ej. "Presentación Integración GOV.CO V4" de septiembre de 2025).
2. **Vigencia y continuidad del mecanismo de redireccionamiento** — las guías que lo describen son de 2020. En 2025 la caja de herramientas publicó material nuevo y el sitio GOV.CO tiene una arquitectura SPA distinta. **Confirmar que el proxy/enmascaramiento sigue operando y bajo qué condiciones técnicas.**
3. **Formulario de solicitud de integración y canal formal de radicación de la solicitud.**
4. **Credenciales OIDC** (issuer, client_id, client_secret/registro de cliente) para Autenticación Digital, y si la entidad debe registrarse como RP ante la AND.
5. **Contrato de servicios y condiciones de vinculación a los SCD** (gratuidad para el usuario está verificada; **el modelo de costos para la entidad no**).
6. **Requisitos de conexión X-Road** para la entidad: certificados, IPs, y el "Documento Contratos Carpeta Ciudadana Digital" con los servicios web a consumir.
7. **Proveedor de notificación electrónica** definido por la entidad y si existe obligación de usar un operador específico.
8. **Si el trámite de la alcaldía es "trámite modelo"** (estandarizado por autoridad nacional). De ser así, el formulario único es de obligatoria observancia y **no puede rediseñarse**.
9. **Medio de pago admisible**: confirmar con la Secretaría de Hacienda si se contratará PSE como cliente directo de ACH, vía pasarela/agregador, o mediante el botón de pagos del banco recaudador; y si existe una cuenta maestra de recaudo.
10. **Si la entidad debe integrar sus notificaciones al "portal de acceso" de GOV.CO** conforme al art. 56 y, de ser así, **bajo qué mecanismo técnico** (no especificado públicamente).
11. **Clasificación de la alcaldía** (Básico / Intermedio / Avanzado) según los grupos del Decreto 088 de 2022, para fijar el plazo aplicable.
12. **Si aplica la ampliación de plazos** del art. 2.2.20.6 num. 3 del Decreto 088 de 2022 por condiciones de conectividad, infraestructura y presupuesto.

---

## 11. Implicaciones para el diseño de la sede

15 puntos concretos y accionables, derivados de los hallazgos verificados.

1. **Diseñar la sede para ser consumida a través de un proxy inverso con enmascaramiento de URL, no para "empujar" contenido a GOV.CO.** El mecanismo normado es que GOV.CO redirija hacia la sede. Consecuencia técnica directa: **la aplicación debe funcionar correctamente cuando cambia el `Host` y el `Origin` de las peticiones**, y cuando las URLs se sirven bajo `https://www.gov.co/<palabra-clave>/` o `https://www.gov.co/tramites-y-servicios/T<idSUIT>` mientras los assets y las cookies viven en el dominio propio.
   *Fuente: [Anexo 2 §5.1-5.2](https://www.mintic.gov.co/portal/715/articles-152200_anexo_2.docx).*

2. **Configurar `X-Frame-Options` y CSP de forma coherente con una redirección de página completa, no con un iframe.** Dos razones convergentes: (i) el mecanismo normado es redirección, no incrustación; (ii) PSE **explícitamente no admite iframe**. Evitar `frame-ancestors` demasiado restrictivo si el enmascaramiento por proxy pudiera implicar framing, pero **priorizar la redirección**.
   *Fuente: Anexo 2 §5.1; [Guía PSE REST](https://secure.payty.com/doc/content/payty/es-CO/payment-method/pse-rest/redirection-form/uuj1675761072425.pdf) ("no es compatible con una integración en un iframe").*

3. **Resolver las URLs de forma absoluta contra el host recibido, y probar la aplicación bajo al menos dos hosts.** El enmascaramiento "convierte la dirección electrónica en un seudónimo". Si la sede genera URLs absolutas con su dominio propio (`https://mi-alcaldia.gov.co/...`), la experiencia bajo `gov.co/...` se rompe. **Prueba de aceptación: la sede debe navegarse íntegramente bajo el host enmascarado.**
   *Fuente: Anexo 2 §5.2.*

4. **Construir el módulo de radicación como un SGDEA, no como un formulario de contacto.** Requisitos verificados y no negociables: **registro electrónico de documentos**; **control estricto de documentos enviados y recibidos con fecha y hora**; generación **automática** del número de radicado; **guardado de borrador** ante caída de conexión; **acuse de recibo automático con fecha y número de radicado**; informes de radicados de entrada y salida; y **firma electrónica con múltiples firmantes y orden secuencial**.
   *Fuentes: [Ley 1437 art. 61 mod. Ley 2080 art. 14](https://www.funcionapublica.gov.co/eva/gestornormativo/norma.php?i=156590); [AGN Modelo de Requisitos 2024](https://www.archivogeneral.gov.co/sites/default/files/Estructura_Web/3-transp-act/7-datos-abiertos/Modelo_Requisitos-2024.pdf).*

5. **Definir el número de radicado con estructura `1-AAAA-XXXXX` (entrada) y `2-AAAA-XXXXX` (salida), generado por el sistema y con la fecha tomada automáticamente del sistema.** Documentar explícitamente que **no es un estándar nacional obligatorio** (es el modelo del AGN), y confirmar con la entidad si tiene una convención propia previa que deba preservarse por continuidad del archivo histórico.
   *Fuente: AGN Modelo de Requisitos 2024, §"Generación automática del número de radicado de entrada".*

6. **El expediente electrónico es la unidad de diseño, no el documento suelto.** Debe garantizar **autenticidad, integridad y disponibilidad**, seguridad digital, y **preservación a largo plazo conforme al AGN**. Los **formularios diligenciados deben incorporarse como documento electrónico al expediente**, y las **notificaciones y sus constancias también**. Definir el índice electrónico y los metadatos desde el inicio.
   *Fuentes: Ley 1437 art. 59 mod. Ley 2080 art. 11; Decreto 088 de 2022 art. 2.2.20.10; Anexo 2 §4.1.2.1.*

7. **Diseñar el módulo de notificaciones alrededor de "acceso certificado", no de "envío".** La notificación **surte cuando el administrado accede**, y la administración **debe certificarlo**. Esto exige: registro de **fecha y hora de acceso** con marca de tiempo confiable, generación de una **constancia certificable** con mecanismos que garanticen autenticidad e integridad del mensaje, y esa constancia **archivada al expediente**.
   *Fuente: Ley 1437 art. 56 mod. Ley 2080 art. 10.*

8. **Implementar el consentimiento y su revocación como una máquina de estados persistente y versionada.** El art. 56 exige **aceptación** del medio electrónico y permite que el interesado pida **volver a medios físicos durante la actuación** (salvo art. 53A inc. 3). El diseño debe: registrar la aceptación con evidencia, permitir el cambio de canal a mitad del procedimiento, y **no** asumir nunca autorización tácita por radicación (eso era el Decreto 491, atado a la emergencia sanitaria).
   *Fuente: Ley 1437 art. 56 mod. Ley 2080 art. 10.*

9. **Prever que el ciudadano llegue a la notificación desde GOV.CO como "portal de acceso".** La ley dice expresamente que "los interesados podrán acceder a las notificaciones en el portal único del Estado, que funcionará como un portal de acceso". El mecanismo técnico no está publicado, pero el diseño debe contemplar **enlaces profundos (deep links) a la notificación con autenticación previa, idempotentes y resistentes a la apertura en pestaña nueva**. **Confirmar el mecanismo con MinTIC/AND.**
   *Fuente: Ley 1437 art. 56 mod. Ley 2080 art. 10.*

10. **Tratar el pago como proceso asíncrono con conciliación obligatoria, nunca como confirmación síncrona.** PSE puede devolver `WAITING_FOR_PAYMENT`; el banco puede no redirigir; existen los flujos "Alterno" (sondeo cada 3-7 minutos) y "Sonda" (cancelación + **reembolso automático la noche siguiente**). El diseño debe incluir: **estado de pago pendiente**, **IPN (webhook) como fuente de verdad**, **sondeo de respaldo**, **reenvío manual de la notificación**, y **URLs separadas de pruebas y producción**.
    *Fuente: Guía PSE REST (pasarela), §5, §6, §12.*

11. **Verificar la firma de toda notificación de pago entrante antes de dar por pagado un trámite.** El patrón verificado es un `signature` calculado sobre **todos los campos cuyo nombre empieza por `vads_`**. Regla de diseño: **el estado "pagado" nunca se activa por el retorno del navegador**, sólo por una notificación cuya firma fue validada. **Solicitar a ACH/pasarela el algoritmo exacto del `Manual del Servicio de PSE`.**
    *Fuente: Guía PSE REST §9.2.1.*

12. **No almacenar datos de tarjeta — y si se usan tarjetas, documentar el estándar aplicable explícitamente.** Con PSE el problema se evita **por diseño** (transferencia por redirección al banco; la entidad nunca ve el número de tarjeta). Si el diseño incorpora tarjetas, debe fijarse **PCI DSS** y las condiciones del adquirente, dejando constancia de que **no se encontró una norma colombiana que lo imponga** — es una decisión de riesgo de la entidad, no un cumplimiento normativo verificado.
    *Fuentes: Guía PSE REST §1; ausencia verificada en anexos 1, 2, 2.1 y 5.*

13. **Implementar los 12 controles de seguridad de aceptación antes de solicitar la integración.** Son verificados por MinTIC como condición previa. Los más costosos de retrofitar y por tanto prioritarios: **cabeceras de seguridad (CSP, HSTS, X-Frame-Options, etc.)**, **cookies `Secure` + `HttpOnly`**, **`OPTIONS` deshabilitado** (exigido por la infografía V7), **CAPTCHA en todos los formularios que capturen datos personales**, **control de tasa de reintentos por login fallido**, y **permisos de solo lectura en el servidor web**. Añadir los exigidos sólo por el Anexo 2: **SSL con validación de organización**, **hardening**, **antivirus**, **monitoreo de seguridad** y **protección del código fuente**.
    *Fuentes: [Criterio de aceptación Seguridad V7](https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/0e18c5c4-4047-4584-9ac7-76566b26ee2e-1a9f92a9-c1e4-4bc5-9283-3993887034a9-Criterio%20de%20aceptacio%CC%81n%20Seguridad_V7.pdf); Anexo 2 §4.3.2.4.*

14. **Construir el HOME y el footer con los elementos exactos que la guía declara de obligatorio cumplimiento**, usando el **KIT UI de GOV.CO** y el color asignado a la entidad: barra superior con **botón que redirige a GOV.CO**; logo ≤50 px; buscador **interno** (no Google); menú con **Transparencia y acceso a información pública**, **Servicios a la ciudadanía** y **Participa** (con las 6 subcategorías de Participa); carrusel; módulos con título ≤100 y descripción ≤170 caracteres y fecha en noticias; footer con contacto y las 3 políticas. **Prohibido** usar el color propio de la entidad en botones, texto, campos de formulario, fondos o etiquetas.
    *Fuentes: [Anexo 2.1](https://www.mintic.gov.co/portal/715/articles-152200_anexo_2_1.pdf); [Criterio de aceptación Funcional V4](https://das997q1qk8hr.cloudfront.net/uploads/2023-11-01/0d8d87fd-0758-4c80-b33e-3dac450f390f-ee3e6b1c-232f-4621-a1ac-81c5416ee413-Criterio%20de%20aceptacio%CC%81n%20Funcional_V4.pdf); Anexo 2 §4.1.*

15. **Preparar la infraestructura para el estándar de red exigido, que es más estricto de lo habitual:** **IP pública IPv4 e IPv6** (Resolución 2710 de 2017), **DNS de doble pila**, y operación en **al menos 3 navegadores**. Añadir: mapa del sitio en el footer, página 404 personalizada, y **barra de accesibilidad** con control de contraste y tamaño de letra.
    *Fuente: Anexo 2 §4.3.2.6, §4.3.2.7, §4.3.2.1, §4.3.2.3.*

### Tres advertencias de alcance para el diseño

16. **Verificar la versión vigente de los anexos antes de cerrar el diseño.** El art. 8 de la Resolución 2893 de 2020 permite que la Dirección de Gobierno Digital del MinTIC actualice los lineamientos "a través de las sucesivas versiones de cada uno de dichos documentos". Los anexos que pude recuperar son de 2020, y en 2025 la caja de herramientas publicó material nuevo. **El diseño no debe congelarse sobre los anexos de 2020 sin confirmar vigencia.**

17. **Confirmar si el trámite es "trámite modelo".** Si lo es, el **formulario único es de obligatoria observancia** y "no existe la posibilidad de incluir pasos o requisitos adicionales". Esto puede invalidar por completo un diseño de formulario propio. **Es la verificación con mayor potencial de ahorro de retrabajo.** *(Decreto 088 de 2022 art. 2.2.20.3, defs. 6 y 14.)*

18. **Diseñar los formularios para el "lenguaje común de intercambio".** El Anexo 2 exige que la sede cuente con un **plan de estandarización de formularios y de servicios de intercambio de información "con el lenguaje común de intercambio"**. Los formularios no deben diseñarse como entidades aisladas, sino **mapeables al Marco de Interoperabilidad** desde el modelo de datos.
    *Fuente: Anexo 2 §4.3.2.2.*

---

## Anexo A — Inventario de archivos descargados en este workspace

Todos los documentos citados quedaron almacenados localmente para verificación:

```
research/gov/
├── INFORME-INTEGRACION-GOVCO.md          (este informe)
├── api/                                   respuestas JSON de las APIs de GOV.CO
│   ├── menu_niveles.json                  árbol de la caja de herramientas
│   ├── recursos_{29,30,32,33,34,35,36,37,38}.json
│   └── bib_buscar.json                    buscador de la biblioteca (140 registros)
├── anexos/
│   ├── Resolucion_02893_de_2020.pdf / res2893.txt
│   ├── Anexo1_Lineamientos_2893.pdf / .txt
│   ├── anexo2.docx / anexo2.txt           ← Guía técnica de sedes electrónicas
│   ├── articles-152200_anexo_2_1.pdf / anexo2_1.txt
│   ├── articles-152200_anexo_5.pdf / anexo5.txt
│   ├── SCD_272743.pdf / .txt              (= Anexo 5, versión dic-2020)
│   ├── SCD_146484.pdf / .txt              guía de prestadores SCD especiales
│   └── Res2893_mirror.pdf
├── docs/                                  PDFs de la caja de herramientas + textos
│   ├── Presentacion_Integracion_GOVCO_V4.pdf
│   ├── Criterio_Aceptacion_Funcional_V4.pdf
│   ├── Criterio_Aceptacion_Seguridad_V7.pdf
│   ├── ABC_Guia_Vinculacion_AUTH_V6.pdf
│   ├── ABC_Biblioteca_Autenticacion_Digital_V4.pdf
│   ├── Como_Registrarse_PlanIntegra_V1.pdf
│   ├── Proceso_Vinculacion_{CarpetaCD,Interoperabilidad}_V6.pdf
│   └── Puntos_claves.pdf
├── legal/
│   ├── Ley2080_fp.html / .txt             Ley 2080 de 2021 (texto completo)
│   ├── Decreto491_fp.html / .txt          Decreto 491 de 2020
│   └── Decreto491_proc.html / .txt        Decreto 491 (espejo Procuraduría)
├── pagos/
│   ├── ACH_Reglamento_acceso_v6.pdf / .txt
│   ├── Payt_PSE_REST_redirection.pdf / .txt
│   └── Credibanco_BotonDePagos.pdf / .txt
├── suit/
│   ├── Decreto088_2022.html / Decreto088.txt
│   └── datos_suit.json
├── agn/
│   ├── AGN_Modelo_Requisitos.pdf / .txt
│   └── AGN_Normalizacion_Formularios_Electronicos.pdf / .txt
└── js/                                    bundles de GOV.CO para reconstruir la API
    ├── main.js, runtime.js, scripts.js
    ├── 986.5ae73313bb5571cb.js            caja de herramientas
    └── 899.e3fe3f326e2ea14a.js            biblioteca
```

## Anexo B — Corpus normativo citado (con año)

**Normas (vinculantes):**
- Ley 527 de 1999 — mensajes de datos y firma electrónica; Principio de Equivalencia Funcional
- Ley 962 de 2005 — racionalización de trámites
- Ley 1341 de 2009 — TIC; principio de masificación de gobierno en línea
- **Ley 1437 de 2011 (CPACA)** — arts. 53A, 54, **56**, 57, **59**, **60**, **60A**, **61**, 65, 67 y ss., 205
- Ley 1581 de 2012 — protección de datos personales
- Ley 1712 de 2014 — transparencia y acceso a la información pública
- Decreto 1078 de 2015 — Decreto Único Reglamentario TIC (arts. 2.2.9.1.2.1, 2.2.17.1.2, 2.2.17.1.6, 2.2.17.6.2; Título 20 adicionado)
- Decreto 1074 de 2015 — cap. 25 (protección de datos)
- Resolución 2710 de 2017 — IPv4/IPv6
- Decreto 1008 de 2018 — lineamientos de la Política de Gobierno Digital
- **Decreto-Ley 2106 de 2019** — simplificación de trámites; arts. 9, 10, **14**, **15**
- Ley 1955 de 2019 (PND) — art. 147, componente de transformación digital
- Directiva Presidencial 02 de 2019 — simplificación de la interacción digital
- Directiva Presidencial 03 de 2019 — uso de la línea gráfica del Estado
- **Ley 2052 de 2020** — disposiciones transversales; arts. 3, 5, 6, 12, 13
- **Decreto 620 de 2020** — uso y operación de los Servicios Ciudadanos Digitales
- **Decreto 491 de 2020** — medidas de urgencia (emergencia sanitaria); art. 4 ← alcance temporal agotado
- Decreto 1692 de 2020 — sistemas de pago de bajo valor (referido en el Reglamento de ACH)
- **Resolución 2160 de 2020 (MinTIC)** — guía de lineamientos de los SCD y guía de vinculación
- Resolución 1519 de 2020 — estándares de publicación de información, accesibilidad, seguridad y datos abiertos
- **Resolución 2893 de 2020 (MinTIC)** — lineamientos y guías de integración a GOV.CO ← **norma central**
- **Ley 2080 de 2021** — modifica la Ley 1437; arts. 10, 11, 12, 13, 14 (sobre 56, 59, 60, 60A, 61)
- **Decreto 088 de 2022** — digitalización y automatización de trámites; adiciona el Título 20 al Decreto 1078 de 2015
- Resolución 1951 de 2022 (MinTIC) — prestadores de SCD especiales

**Documentos técnicos / guías / estándares (no normas):**
- Resolución 2893 de 2020: **Anexos 1, 2, 2.1, 5, 5.1**
- Guía de integración de prestadores privados de SCD Especiales (MinTIC, junio 2022)
- Criterios de aceptación Funcional V4, Diseño V6 y Seguridad V7 (Agencia Nacional Digital / MinTIC)
- ABC de Librerías de Autenticación Digital V4
- Manual Instructivo para registro y autenticación Administrador Entidad Ver 2.0
- Modelo de Requisitos para la Gestión de Documentos Electrónicos de Archivo (AGN, 2024)
- Normalización de Formas y Formularios Electrónicos (AGN, 2024)
- Reglamento de Acceso a los Servicios de ACH Colombia S.A., v6 (julio 2024) — **reglamento privado**
- Guía de implementación PSE REST (pasarela Payt, v1.3) — **documentación de tercero**
- Documentación API Botón de Pagos (Credibanco) — **documentación de tercero**

**Estándares internacionales referidos por la guía oficial de SCD:**
- OpenID Connect Core 1.0, Discovery 1.0, RP-Initiated Logout 1.0, Back-Channel Logout 1.0, Session Management 1.0, Front-Channel Logout 1.0
- OAuth 2.0 (RFC 6749); RFC 6750 (Bearer Token); RFC 7636 (PKCE); RFC 7523 (JWT Client Auth); RFC 8705 (mTLS); RFC 7009 (Token Revocation); RFC 7662 (Token Introspection); RFC 6960 (OCSP)
- NIST SP 800-63-3 — identidad digital
- X-Road (versión Colombia 6.25); NIIS Management Services

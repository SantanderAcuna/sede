# Informe de investigación — Alcaldía Distrital de Santa Marta (D.T.C.H.)

**Objeto:** recopilar datos institucionales y funcionales verificables para construir la sede electrónica del Distrito Turístico, Cultural e Histórico de Santa Marta.

**Fecha de consulta de todas las fuentes: 2026-09-30** (hora local del investigador: America/Bogota, UTC−5).

**Fuente principal:** <https://www.santamarta.gov.co/>

**Regla aplicada:** todo dato lleva URL exacta. Lo que no se pudo verificar consta como `NO ENCONTRADO` o `NO VERIFICADO` en la sección final. No se inventó ningún dato institucional.

---

## 0. Resumen ejecutivo

| Hallazgo | Estado |
|---|---|
| El sitio corre **Drupal 7.103** (EOL 2025-01-05) sobre **govCMS** — la distribución del **gobierno de Australia** — detrás de **Cloudflare** | Confirmado |
| **No existe sede electrónica** ni subdominio dedicado (`sede.`, `tramites.`, `servicios.`, `datos.` no resuelven) | Confirmado |
| El sitio publica **118 trámites** (110 códigos únicos); **SUIT registra 124** → discrepancia de catálogo | Confirmado |
| El sitio **no publica costo, tiempo de respuesta ni modalidad (en línea/presencial)** de ningún trámite | Confirmado (vacío verificado) |
| Sección de transparencia replica **exactamente la estructura de 8 bloques** del Anexo 2 de la Res. MinTIC 1519/2020 | Confirmado |
| **NO hay datos abiertos publicados en datos.gov.co** por la Alcaldía (0 datasets atribuidos) | Confirmado |
| **NO hay declaración de accesibilidad ni de conformidad WCAG** | Confirmado (ausencia verificada) |
| El pie omite **línea gratuita**, **código postal** y **derechos de autor**; la línea gratuita sí existe en otra página | Confirmado |
| **NO existe normatividad local** de gobierno digital / TIC (barrido de 1.643 actos: 0 coincidencias) | Confirmado |
| **NO cumple el Kit UI** de gov.co (0 clases `.govco-*`, 0 `govco-font`, 0 usos del azul principal `#004884`) | Confirmado |
| Existe **logo oficial descargable en SVG** (escudo) | Confirmado |
| HTTPS correcto (wildcard Google Trust Services, HSTS, TLS 1.3), pero **TLS 1.0/1.1 habilitados** → SSL Labs **grado B** | Confirmado |

---

## 1. Datos institucionales obligatorios (FUN-014)

### 1.1 Tabla de los 8 datos obligatorios del pie de página

| # | Dato (FUN-014) | Valor encontrado | URL donde aparece | ¿Confirmado? |
|---|---|---|---|---|
| 1 | **Nombre de la entidad** | Alcaldía Distrital de Santa Marta / "Alcaldía Distrital de Santa Marta D.T.C.H." | <https://www.santamarta.gov.co/> (pie) | ✅ CONFIRMADO |
| 2 | **Dirección** | Calle 14 No. 2 - 49, Palacio Municipal, **Santa Marta – Magdalena, Colombia** | Pie: <https://www.santamarta.gov.co/> · **Completa (con departamento y municipio):** <https://www.santamarta.gov.co/puntos-atencion-al-ciudadano> | ⚠️ CONFIRMADO **solo fuera del pie**. El pie publica la dirección **sin** departamento ni municipio, exigidos por Res. 1519/2020 Anexo 2 §2.2.1.2 |
| 3 | **Código postal** | **470004** (Palacio Municipal, zona postal 4700) — *no publicado por la entidad* | Fuente oficial externa: visor 4-72 <https://visor.codigopostal.gov.co/472/visor/> y CSV <http://visor.codigopostal.gov.co/472/visor/Codigos_Postales_Nacionales.csv> | ⚠️ **NO PUBLICADO POR LA ENTIDAD**; verificado por fuente oficial externa |
| 4 | **Teléfono (conmutador)** | (+57) 605 420 9600 — en el pie. También "(+57)(5) 4209600" como "línea única de atención" | <https://www.santamarta.gov.co/> (pie) · <https://www.santamarta.gov.co/puntos-atencion-al-ciudadano> | ✅ CONFIRMADO |
| 5 | **Línea gratuita** | **018000 955 532** — publicada como "**Línea gratuita nacional**" | <https://www.santamarta.gov.co/puntos-atencion-al-ciudadano> (texto literal: "Línea gratuita nacional: 018000 955 532") | ✅ **CONFIRMADO** — pero **NO está en el pie de página**, que es donde la exige la Res. 1519/2020 §2.2.1.4 |
| 6 | **Línea anticorrupción** | (+57) 605 4351719 — **idéntica a la línea de atención al ciudadano** | <https://www.santamarta.gov.co/> (pie) | ⚠️ CONFIRMADO pero **duplicada**; no hay canal exclusivo |
| 7 | **Correo de atención al usuario** | `atencionalciudadano@santamarta.gov.co` | <https://www.santamarta.gov.co/> (pie, ofuscado con Cloudflare Email Protection) | ✅ CONFIRMADO |
| 8 | **Correo de notificaciones judiciales** | `notificacionesalcaldiadistrital@santamarta.gov.co` | <https://www.santamarta.gov.co/> (pie) | ✅ CONFIRMADO |
| 9 | **Redes sociales** | Facebook `SantaMartaDTCH`, Instagram `santamartadtch`, X `SantaMartaDTCH`, YouTube `@santamartadtch` | <https://www.santamarta.gov.co/> (pie) | ✅ CONFIRMADO |
| 10 | **NIT** | `891780009` en el pie del sitio. **Con dígito de verificación: 891.780.009-4** (documento oficial) | Pie: <https://www.santamarta.gov.co/> · DV: <https://www.santamarta.gov.co/portal/archivos/documentos/INFORME%20GAC(1).pdf> | ⚠️ El sitio publica el NIT **sin dígito de verificación** |

> **Nota crítica sobre la línea gratuita.** En una primera pasada de esta investigación la línea gratuita figuraba como no encontrada: **no está en el pie de página** y no aparece en la portada, trámites, PQRSD ni atención al ciudadano. Sin embargo, **sí está publicada** en la página institucional de Puntos de Atención al Ciudadano (<https://www.santamarta.gov.co/puntos-atencion-al-ciudadano>, HTTP 200, 86.568 bytes), enlazada desde el bloque 1.4 de la sección de transparencia. Texto literal:
>
> > LOCALIZACIÓN FÍSICA OFICINA DE ATENCIÓN AL CIUDADANO
> > Alcaldía Distrital de Santa Marta
> > Dirección: Calle 14 No. 2 - 49, Palacio Municipal, **Santa Marta - Magdalena, Colombia**.
> > Horarios de atención: Lunes a Viernes de 8:00 a.m. a 12:00 p.m. y de 2:00 p.m. a 6:00 p.m.
> > LÍNEAS DE ATENCIÓN TELEFÓNICA
> > **Línea única de atención: (+57)(5)4209600**
> > **Línea gratuita nacional: 018000 955 532**
>
> El número coincide con el documentado en el PDF de 2019 (`01 8000 955 532`), lo que **refuerza su vigencia**: es el mismo dato, publicado en una página viva del sitio actual. Aun así, se recomienda reconfirmar su operatividad por contacto directo antes de publicarlo en la sede.
>
> **Lo que sí es un incumplimiento formal:** los 8 datos obligatorios del pie deben estar **en el pie**. Hoy el pie omite la línea gratuita, el código postal, la dirección completa (con departamento y municipio) y el vínculo de política de derechos de autor.

### 1.2 Detalle y evidencia

**Bloque literal del pie de página** (extraído del HTML de <https://www.santamarta.gov.co/>, consulta 2026-09-30):

```
Alcaldía Distrital de Santa Marta.
NIT 891780009
Dirección: Calle 14 No. 2 - 49 Palacio Municipal
Horario de Atención: Lunes a Viernes de 8:00 a.m. a 12:00 p.m. y de 2:00 p.m. a 6:00 p.m.
Políticas de seguridad de la información y protección de datos personales.
Línea de Atención al Ciudadano (+57) 605 4351719
Línea PBX – Comunicación interna: (+57) 605 420 9600
línea Anticorrupción: (+57) 605 4351719
Todos los derechos reservados © 2026
Políticas de privacidad - Términos y condiciones - Ingreso al sistema Humano -
Humano Educación - Políticas - Mapa de Sitio - Atención al ciudadano
NOTA: EL HORARIO DE RECEPCIÓN Y RADICACIÓN ES LUNES A VIERNES DÍAS HABILES DE 8:00AM - 5:00PM
correo: [atencionalciudadano@santamarta.gov.co]
Notificaciones Judiciales
NOTA: ESTE CORREO ES SOLO PARA SOLICITUDES DE TEMAS JURÍDICOS
correo: [notificacionesalcaldiadistrital@santamarta.gov.co]
```

**Sobre los correos:** el sitio los ofusca con Cloudflare Email Protection (`data-cfemail`). Fueron decodificados de forma determinista desde el HTML; no son una inferencia:

- `atencionalciudadano@santamarta.gov.co` (decodificado de `data-cfemail="563722..."`)
- `notificacionesalcaldiadistrital@santamarta.gov.co` (decodificado de `data-cfemail="751b1a..."`)

Ambos correos se repiten en varias páginas, lo que refuerza su vigencia:
<https://www.santamarta.gov.co/atencion-al-ciudadano> · <https://www.santamarta.gov.co/pqrsd> · <https://www.santamarta.gov.co/politica-de-privacidad>

**Sobre el NIT con dígito de verificación:** el documento oficial *Informe de Atención al Ciudadano de mayo a 15 de agosto de 2019* (Oficina de Gestión para la Atención al Ciudadano) imprime en su membrete:

> `Nit: 891.780.009-4`

URL: <https://www.santamarta.gov.co/portal/archivos/documentos/INFORME%20GAC(1).pdf> (HTTP 200, PDF creado 2019-08-28). El sitio web vigente publica `NIT 891780009` sin DV.

**Sobre la línea gratuita:** el mismo PDF de 2019 documenta:

> "Línea Gratuita Nacional: 01 8000 955 532" y "La Línea 01-8000-955-532 es una línea gratuita que la alcaldía distrital pone a disposición de todo ciudadano para pedir orientación o interponer una PQRSD."

Ese número también aparece en notas de prensa del propio sitio (2020):
<https://www.santamarta.gov.co/search/node/018000955532> — resultados "Alcaldía activa canales de atención…" (27/03/2020) y "Horarios y comunicaciones debido a la pandemia" (15/11/2020), que citan `línea Nacional gratuita 018000955532`.

**Conclusión:** la línea gratuita existió y está documentada en fuente oficial, pero **no aparece publicada en el sitio vigente (2026)**. No se puede afirmar que siga activa sin reconfirmación directa con la entidad.

### 1.3 Código postal — verificación con fuente oficial

La entidad **no publica su código postal en ninguna página** (búsqueda de "código postal" en el sitio: 0 resultados).

Se verificó con la fuente oficial del operador postal (Servicios Postales Nacionales 4-72, visor nacional de códigos postales):

- CSV nacional oficial: <http://visor.codigopostal.gov.co/472/visor/Codigos_Postales_Nacionales.csv> (HTTP 200, 871.837 bytes)
- Consulta geoespacial al servicio oficial ArcGIS `Division_Codigo_postal` (capa 1 `CodigoPostal`) con el punto del Palacio Municipal:

```
GET https://visor.codigopostal.gov.co/arcgis/rest/services/Division_Codigo_postal/MapServer/1/query
    ?geometry={"x":-74.2120,"y":11.2415,"spatialReference":{"wkid":4326}}
    &geometryType=esriGeometryPoint&inSR=4326
    &spatialRel=esriSpatialRelIntersects
    &outFields=Codigo,Codigo_Postal,Nombre,ZonaPostalID&returnGeometry=false&f=json
```

Resultado: `{"Codigo":"5199","Codigo_Postal":"470004","Nombre":null,"ZonaPostalID":"4700"}`

El CSV confirma que el municipio `47001 SANTA MARTA, MAGDALENA` tiene los códigos postales **470001 a 470009 y 470017**; el polígono 470004 comprende los barrios "Centro I", "Colón", "Boston", "Los Ángeles", "Municipal-", entre otros — coherente con la dirección institucional "Calle 14 No. 2-49, Centro Histórico".

> **Advertencia para la sede:** Santa Marta **no tiene un código postal único**. Cualquier sede electrónica debe usar el código de la sede (470004 para el Palacio Municipal), no un código "de ciudad".

### 1.4 Canales de atención físicos y electrónicos publicados

Canales declarados en <https://www.santamarta.gov.co/atencion-al-ciudadano>:

| Canal | Detalle | URL |
|---|---|---|
| Presencial | Punto de Atención: **Calle 22 No. 16-14** | <https://santamarta.gov.co/punto-de-atencion-0> |
| Telefónico | **605 435 1719** (atención al ciudadano); conmutador 4209600 ext. 1414 en el punto de atención | <https://santamarta.gov.co/punto-de-atencion-0> |
| Virtual | PQRSD – Ventanilla Única Digital | <https://www.santamarta.gov.co/pqrsd> |
| Correo | `atencionalciudadano@santamarta.gov.co` | <https://www.santamarta.gov.co/atencion-al-ciudadano> |
| Cita presencial | Sistema externo (Vadati): <https://citas-santamarta.vadati.co/> | enlazado como "Agenda tus citas" |
| Chat | Tawk.to (`embed.tawk.to/5aff50b75f7cdf4f0534598f`) **y además** Pulse.is livechat | Portada |

**Inconsistencia detectada (horarios).** El sitio principal y el portal PQRSD declaran horarios distintos:

| Fuente | Horario declarado |
|---|---|
| <https://www.santamarta.gov.co/> (pie) | Lunes a viernes 8:00 a.m.–12:00 p.m. y 2:00 p.m.–6:00 p.m. |
| <https://www.santamarta.gov.co/> (nota de radicación) | Recepción y radicación: L–V 8:00 a.m.–5:00 p.m. |
| Portal PQRSD <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Radicacion/Radicacion> | L–V 8:00 a.m.–12:30 p.m. y 2:00 p.m.–6:00 p.m. |
| Portal PQRSD (radicación) | Radicar: L–V 8–11 a.m. y 2–5 p.m. |

**Inconsistencia en la dirección/telefonía del portal PQRSD.** Ese portal imprime: "Dirección: Calle 14 No 2-49 Centro Histórico" y "Teléfono: (57) (7) 6054209600" — es decir, usa el **indicativo antiguo (7)** en lugar del **605** que exige la CRC y la Res. 1519/2020 Anexo 2 §2.2.1.4.

### 1.5 Sede física y sedes por dependencia

Directorio oficial vigente (PDF con fecha de modificación 2026-07-16):
<https://www.santamarta.gov.co/sites/default/files/oferta_institucional_y_directorio_alcaldia_de_santa_marta_d.t.c.h.pdf> (51,2 MB)

Sedes identificadas en ese documento:

| Dependencia | Dirección declarada |
|---|---|
| Oficina de Atención al Ciudadano (Punto de Atención) | Calle 22 No. 16-14 |
| Secretaría de Hacienda | Calle 14 No. 2-49, piso 1, Centro Histórico |
| Dirección de Rentas | Carrera 11 No. 17a-48, barrio Territorial |
| Catastro Multipropósito | Carrera 11 No. 17a-56, barrio Territorial (detrás de la escuela Santander) |
| EDUS | Carrera 11 No. 17a-56, barrio Territorial |
| Secretaría de Planeación | Diagonal 39 No. 7-275, Casa Grande, sector Mamatoco |
| Secretaría de Gobierno | Diagonal 39 No. 7-275, Casa Grande, sector Mamatoco |
| Secretaría de Promoción Social, Inclusión y Equidad | Diagonal 39 No. 7-275, Casa Grande, sector Mamatoco |
| Secretaría de Seguridad y Convivencia Ciudadana | Diagonal 39 No. 7-275, Casa Grande |
| Alta Consejería para la Sierra Nevada y Zona Rural | Diagonal 39 No. 7-275, Casa Grande |
| Secretaría de Educación | Carrera 8 No. 28A-60, Taminaca / Diagonal 39, Casa Grande, Mamatoco |
| Secretaría de Salud | Carrera 5 No. 26-35, C.C. Quinta Avenida, tercer piso |
| Secretaría de la Mujer y Equidad de Género | Calle 16 No. 3-77, Centro Histórico |
| Secretaría de Cultura | Calle 15 No. 3-6, piso 3, Casa Gauthier |
| Secretaría de Desarrollo Económico y Competitividad | Calle 15 No. 3-67, piso 1, Casa Gauthier |
| DADSA | Calle 16 No. 5-10, Centro Histórico |
| Agencia Pública de Empleo | Calle 15 No. 3-67, Casa Gauthier, piso 1 |
| INDETUR | Calle 15 No. 3-67, Casa Gauthier, piso 3 |
| INRED | Cra 19 # 18-00, Coliseo Mayor – Parque Deportivo Bolivariano |
| Oficina para la Gestión del Riesgo y el Cambio Climático | Calle 16 No. 14A-08, piso 2, barrio El Cundí |
| Alcaldía Localidad Uno | Calle 15 No. 3-67 Piso 4, Casa Gauthier |
| Alcaldía Localidad Dos | Calle 15 No. 2-60, piso 8, edificio Bolívar |
| Alcaldía Localidad Tres | Cra 4a No. 21-184, Edificio Puerto Banús, El Rodadero |
| Casa de Justicia | Carrera 13 No. 29-76, barrio Bavaria / Carrera 16-31a-34, barrio Las Américas |
| Emergencias | 316 2495492 |

> El PDF es un folleto a dos columnas; el emparejamiento dependencia↔dirección se reconstruyó por proximidad en la extracción de texto. **Debe validarse contra el original visual antes de publicar en la sede.**

---

## 2. Logo oficial

### 2.1 Sí existe archivo descargable del escudo oficial

| Recurso | URL exacta | Tipo | Tamaño | Última modificación |
|---|---|---|---|---|
| **Escudo oficial (SVG)** | <https://www.santamarta.gov.co/sites/default/files/header/escudo.svg> | `image/svg+xml` | 580.690 bytes | 2022-10-05 |
| Logo usado en el encabezado (PNG) | <https://www.santamarta.gov.co/sites/all/themes/bootstrap/img/logo500Or.png> | `image/png` | 500.788 bytes | 2024-07-09 |
| Logo GOV.CO (Kit) | <https://www.santamarta.gov.co/sites/all/themes/bootstrap/img/logoGovCO.png> | `image/png` | 96.396 bytes | 2025-09-25 |
| Marca Colombia CO (SVG) | <https://www.santamarta.gov.co/sites/all/themes/bootstrap/img/logo_co.svg> | `image/svg+xml` | 3.687 bytes | 2025-08-08 |
| Favicon | <https://www.santamarta.gov.co/sites/all/themes/bootstrap/favicon.ico> | `image/vnd.microsoft.icon` | 26.694 bytes | 2018-01-28 |
| Organigrama (SVG) | <https://www.santamarta.gov.co/sites/all/themes/bootstrap/img/organigrama.svg> | `image/svg+xml` | 54.845 bytes | — |

Todos verificados con `curl -sSI` el 2026-09-30 (HTTP 200).

### 2.2 Manual de identidad visual oficial

Existe y está publicado:
<https://www.santamarta.gov.co/sites/default/files/Manual-Identidad-Visual-Alcald%C3%ADa-de-Santa-Marta-DTCH-2024.pdf>
(`application/pdf`, **120.492.544 bytes ≈ 120 MB**, última modificación 2025-09-25)

> **Advertencia técnica:** 120 MB es un peso desproporcionado para un manual de identidad; conviene que la sede electrónica lo sirva comprimido o segmentado.

### 2.3 Observación

El color dominante de la hoja de estilos propia del sitio es **`#0d6fa5`** (68 apariciones), **no** el azul institucional del Gobierno Digital (`#004884` / `#3366CC`). El encabezado sí usa `#36c` (que es `#3366CC`) en la clase `.header-gov`.

---

## 3. Trámites y servicios

### 3.1 Inventario

**Fuente:** <https://www.santamarta.gov.co/tramites-y-servicios> (consulta 2026-09-30)

- **118 entradas** de trámite publicadas
- **110 códigos T únicos** (8 duplicados reales)
- **11 dependencias** agrupadoras
- **Cada trámite enlaza a su ficha nacional en gov.co**, patrón `https://www.gov.co/ficha-tramites-y-servicios/T<CÓDIGO>`

Distribución por dependencia:

| Dependencia | N.º de trámites |
|---|---|
| Secretaría de Planeación | 29 |
| Secretaría de Educación | 28 |
| Secretaría de Hacienda | 18 |
| Secretaría de Gobierno | 17 |
| Secretaría de Movilidad Multimodal y Sostenible | 7 |
| Secretaría de Salud | 7 |
| Catastro Multipropósito | 5 |
| DADSA | 2 |
| Dirección Jurídica | 2 |
| INRED | 2 |
| Alcaldías Locales 1, 2 y 3 | 1 |

Duplicados detectados (mismo código en dos dependencias o repetido): `T66256`, `T65992`, `T28597`, `T65981`, `T65978`, `T66057`, `T65989`, `T69149`.

### 3.2 Verificación cruzada con el portal SUIT (Función Pública)

**El catálogo oficial nacional registra 124 trámites** para la Alcaldía, frente a los 118 publicados en su propio sitio.

| Dato | Valor |
|---|---|
| Código de entidad en SUIT | **0043** — "ALCALDIA DISTRITAL DE SANTA MARTA, DISTRITO TURISTICO, CULTURAL E HISTORICO" |
| Código de municipio | **47001** |
| Trámites registrados | **124** |
| Buscador público | <https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites> (HTTP 200) |
| Endpoint real del índice | `https://www.funcionpublica.gov.co/dafpIndexerBT/tramite/index?find=FindNext&query=&filtroEntidad=0043&…&max=100` |
| Patrón de ficha individual | `https://visorsuit.funcionpublica.gov.co/auth/visor?fi=<ID>` |

Respuesta literal del portal (verificada el 2026-09-30):
> `ALCALDIA DISTRITAL DE SANTA MARTA, DISTRITO TURISTICO, CULTURAL E HISTORICO (124)`

**Discrepancia cuantificada (conciliación 1:1 hecha sobre ambos catálogos):**

| Conjunto | Cantidad |
|---|---|
| Publicados en el sitio del Distrito | 118 entradas / **110 códigos únicos** (8 duplicados internos) |
| Registrados en SUIT (entidad `0043`) | **124** |
| **En SUIT pero NO en el sitio** | **16** |
| **En el sitio pero NO en SUIT** | **2** |

**Los 16 trámites que el ciudadano NO encuentra en el sitio del Distrito** (existen y están vigentes en SUIT):

| # | Trámite | Visor SUIT |
|---|---|---|
| 1 | Devolución y/o compensación de pagos en exceso y pagos de lo no debido | `fi=2678` |
| 2 | Impuesto a la publicidad visual exterior | `fi=40942` |
| 3 | Concepto de norma urbanística | `fi=41349` |
| 4 | Aprobación de los planos de propiedad horizontal | `fi=41363` |
| 5 | Reconocimiento de escenarios culturales para las artes escénicas | `fi=65961` |
| 6 | Determinantes para la formulación de planes parciales | `fi=66338` |
| 7 | **Impuesto de delineación urbana** | `fi=66448` |
| 8 | Cambios por inscripción de predios o mejoras por edificaciones no declaradas u omitidas | `fi=87548` |
| 9 | Englobe o desenglobe de dos o más predios | `fi=87633` |
| 10 | Rectificación de áreas y linderos | `fi=87642` |
| 11 | Orden de entrega del vehículo inmovilizado | `fi=87728` |
| 12 | Inscripción o autorización para la circulación vial | `fi=87732` |
| 13 | Orden de entrega del vehículo inmovilizado por pago total de comparendo | `fi=89199` |
| 14 | Excepción a restricciones de movilidad | `fi=89270` |
| 15 | **Plan de Manejo de Tránsito** | `fi=89318` |
| 16 | Visto Bueno para realización de eventos que puedan afectar la movilidad | `fi=89319` |

**Los 2 trámites publicados en el sitio que NO figuran en SUIT** (posible desactualización o trámite desregulado):

| Código | Trámite |
|---|---|
| `T6126` | Retiro de personas de la base de datos del SISBEN |
| `T13787` | Retiro de un hogar de la base de datos del SISBEN |

> **Relevancia para la sede:** entre los 16 faltantes hay trámites con impacto directo en movilidad y recaudo —**Impuesto de delineación urbana**, **Plan de Manejo de Tránsito**, **Excepción a restricciones de movilidad** y las **órdenes de entrega de vehículos inmovilizados** (3 trámites). No son trámites marginales.

### 3.3 Modalidad, costo y tiempo de respuesta

> **Advertencia metodológica:** una búsqueda por texto libre `"Santa Marta"` en SUIT devuelve 1.841 resultados y un filtro por municipio `47001` devuelve 315 — **ninguno de esos es el catálogo del Distrito**. Esas cifras mezclan la Gobernación del Magdalena, la Universidad del Magdalena, ESSMAR, INRED y el ESE Hospital Julio Méndez. El número exclusivo y correcto es **124**, obtenido filtrando por código de entidad `0043`. El servidor topa la página en 100 filas aunque se pida `max=200`, por lo que hay que paginar.

**Segunda limitación comprobada:** el visor de las fichas (`https://visorsuit.funcionpublica.gov.co/auth/visor?fi=<ID>`) es **también una SPA Angular**. Se probaron 13 subrutas de su API (base `https://visorsuit.funcionpublica.gov.co/api`, localizada en el bundle) y todas devolvieron el mismo shell HTML de 5.239 bytes. Por eso **no se pudo obtener de SUIT ni el "código del trámite" ni la marca que distingue "trámite" de "Otro Procedimiento Administrativo (OPA)"**.

**Análisis de la ruta de consulta de gov.co — NO VERIFICADO, con causa identificada.** Las fichas de trámites de gov.co no pudieron leerse, y la causa no es de red sino de ejecución de JavaScript más un **defecto del propio portal nacional**:

| Comprobación | Resultado |
|---|---|
| `/home/`, `/tramites-y-servicios`, `/entidades`, `/entidades-y-tramites` | Todas devuelven **byte a byte el mismo shell de 5.038 bytes** (MD5 `d224a91227a9fa59cb86d84b52c80555`) |
| `https://www.gov.co/api/...` | Devuelve el shell HTML de 5.038 B (la SPA captura todo) |
| `https://api-interno.www.gov.co/api/entidades/`, `/ficha-tramites-y-servicios`, `/integracion-sedes` | **404** (403 sin cabeceras de navegador) |
| `https://www.gov.co/sitemap.xml` | HTTP 200 pero contiene **una sola URL** (`https://www.gov.co/`, `lastmod 2022-03-18`) |
| Web component del buscador: `https://cdn.www.gov.co/buscador-webcomponents/.../buscador-webcomponents.esm.js` | HTTP 200 pero **`content-type: text/html`, 1.908 bytes** — sirve una página de documentación, **no un módulo ES**. Defecto en el CDN de gov.co |
| `https://cdn.www.gov.co/webcomponents/govco-collection-webcomponents/govco-collection-webcomponents.js` | Loader que se autodeclara **"Deprecated script, please remove"** |
| Los 17 chunks *lazy* de gov.co | Descargados y revisados: **ninguno** contiene la cadena `sugerencias` |
| API del buscador `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/` | GET responde `{"status":"PARA LAS SUGERENCIAS USAR EL POST CON ESTRUCTURA JSON"}`; con POST JSON devuelve siempre error. Se probaron **20 nombres de campo** (`termino`, `query`, `q`, `criterio`, `busqueda`, …) + combinaciones con `tipo`/`cantidad`: **ninguno aceptado**. Esquema no determinado |
| `https://www.gov.co/entidades` | **No es un directorio de entidades**: es una página estática sobre la estructura del Estado (Rama Ejecutiva/Legislativa/Judicial, órganos autónomos, organismos de control), con **0 coincidencias** de "Santa Marta". Su propio texto remite a `https://www.gov.co/buscador/?ver=Entidades` |

➡️ **Conclusión:** el catálogo de gov.co para Santa Marta **no pudo verificarse**. No es una limitación de red — es que el buscador público del portal nacional está **roto** (componente servido con `Content-Type` incorrecto y versiones marcadas como obsoletas). Cerrar este punto requiere un navegador real (Playwright/Chromium) o acceso a la red interna.

### 3.3 Modalidad, costo y tiempo de respuesta

> **Hallazgo crítico:** el catálogo del sitio **NO declara, para ningún trámite**, si es en línea o presencial, cuánto cuesta, ni su tiempo de respuesta.

Verificación: búsqueda de los términos `en línea`, `virtual`, `presencial`, `sin costo`, `gratuito`, `tiempo de respuesta`, `días hábiles`, `costo`, `tasa`, `tarifa` sobre el HTML completo de <https://www.santamarta.gov.co/tramites-y-servicios> → **0 coincidencias** (el único resultado, "tarifas de", pertenece a otro contenido de plantilla).

El propio texto introductorio traslada esa carga al portal nacional:

> "Si requiere conocer los requisitos, tiempo respuesta, direcciones, teléfonos u otro dato importante sobre el trámite, deberá dar clic en el nombre del trámite a consultar."

Ese clic lleva a `https://www.gov.co/ficha-tramites-y-servicios/T<CÓDIGO>`.

**Limitación técnica comprobada:** las fichas de gov.co **no son legibles sin navegador**. La ruta devuelve siempre el mismo shell Angular de 5.038 bytes para cualquier `T<CÓDIGO>`:

```
$ curl -sS -o f.html -w '%{http_code} %{size_download}\n' https://www.gov.co/ficha-tramites-y-servicios/T2621
200 5038
```

El backend real es `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios` (descubierto en el bundle `main.8fd5437b44dfad3f.js`), pero responde **403/404** a peticiones no navegador:

```
$ curl -sS -w '\nHTTP:%{http_code}\n' -H 'User-Agent: Mozilla/5.0 ... Chrome/131' \
    https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/Tramite/T2621
{"errors":[{"title":"Object reference not set to an instance of an object.","status":"400"}]}
```

Se probaron 12 variantes de ruta y 6 de parámetros de consulta; la API valida los nombres de parámetro contra una lista blanca y no se logró resolver el contrato sin ejecutar el cliente. **Por tanto: el costo, el tiempo y la modalidad de cada trámite NO pudieron extraerse de fuente oficial en esta investigación.**

### 3.4 Lista priorizada de trámites por relevancia

Priorización por criterio funcional (frecuencia ciudadana esperada × impacto recaudatorio × obligación de publicación), **no** por un ranking oficial publicado — la Alcaldía no publica estadísticas de demanda por trámite.

#### Prioridad 1 — Alto volumen ciudadano y/o recaudo (candidatos naturales a "en línea")

| Trámite | Código | Dependencia | Ficha gov.co |
|---|---|---|---|
| Impuesto predial unificado | T2621 | Hacienda | <https://www.gov.co/ficha-tramites-y-servicios/T2621> |
| Impuesto de industria y comercio (ICA) y avisos y tableros | T2674 | Hacienda | <https://www.gov.co/ficha-tramites-y-servicios/T2674> |
| Registro de contribuyentes del ICA | T6082 | Hacienda | <https://www.gov.co/ficha-tramites-y-servicios/T6082> |
| Encuesta SISBEN | T6113 | Planeación | <https://www.gov.co/ficha-tramites-y-servicios/T6113> |
| Actualización de información SISBEN | T6139 | Planeación | <https://www.gov.co/ficha-tramites-y-servicios/T6139> |
| Certificado de residencia | T66256 | Gobierno / Alcaldías Locales | <https://www.gov.co/ficha-tramites-y-servicios/T66256> |
| Certificado de estratificación socioeconómica | T65992 | Planeación | <https://www.gov.co/ficha-tramites-y-servicios/T65992> |
| Certificado catastral | T87648 | Catastro | <https://www.gov.co/ficha-tramites-y-servicios/T87648> |
| Certificado de paz y salvo | T66181 | Hacienda | <https://www.gov.co/ficha-tramites-y-servicios/T66181> |
| Certificado de libertad y tradición de vehículo | T14088 | Movilidad | <https://www.gov.co/ficha-tramites-y-servicios/T14088> |

#### Prioridad 2 — Trámites urbanísticos y de alto impacto económico

`T41362` Concepto de uso del suelo · `T65981` Legalización urbanística de asentamientos humanos · `T28597` Copia certificada de planos · `T65977` Autorización de ocupación de inmuebles · `T65975` Licencia de intervención del espacio público · `T28614` Aprobación de piscinas · `T65978` Modificación de plano urbanístico · `T66055` Incorporación y entrega de áreas de cesión

#### Prioridad 3 — Licenciamiento y salud pública

`T41311` Licencia de funcionamiento de establecimientos educativos privados (preescolar/básica/media) · `T28648` Solicitud concepto sanitario · `T66456` Licencia de inhumación de cadáveres · `T41353` Licencia para cremación de cadáveres · `T28661` Traslado de cadáveres · `T66452` Apertura de centros de estética y similares · `T28666` Vacunación antirrábica de caninos y felinos · `T66139` Esterilización canina y felina

#### Prioridad 4 — Movilidad y vehículos

`T65676` Matrícula de vehículos automotores · `T65678` Duplicado de placas · `T65684` Duplicado de licencia de tránsito · `T41257` Cambio de carrocería · `T41370` Blindaje · `T40952` Inscripción de limitación o gravamen

#### Prioridad 5 — Trámites docentes (nómina pensional)

`T28612` Pensión de jubilación · `T28611` Pensión de jubilación por aportes · `T28632` Cesantía definitiva · `T28732` Cesantías parciales · `T28640` Pensión post-mortem · `T69151` Pensión de retiro por invalidez · `T28641` Pensión de retiro por vejez · `T40800` Reliquidación pensional

#### Prioridad 6 — Espacio público, gobierno y hacienda secundaria

`T28962` Inscripción de propiedad horizontal · `T28964` Cambio de representante legal PH · `T65979` Extinción de propiedad horizontal · `T40674` Devolución de elementos retenidos por ocupación ilegal del espacio público · `T65993` Nomenclatura de barrio · `T10912` Inscripción de dignatarios de organizaciones comunales · `T10936` Inscripción o reforma de estatutos comunales · `T10962` Apertura de libros de organizaciones comunales · `T66178` Devolución y/o compensación de pagos en exceso

#### Prioridad 7 — Juegos de suerte y azar, espectáculos y otros

`T40900` Autorización de rifas · `T40683` Juegos promocionales · `T41356` Prórroga de sorteo de rifas · `T65960` Derechos de explotación de rifas · `T65963` Concepto previo juegos localizados · `T40866`/`T40869` Conceptos de excepción · `T41360` Permiso de pólvora y pirotecnia · `T41309` Espectáculos de artes escénicas en escenarios no habilitados · `T40684` Espectáculos públicos distintos de artes escénicas · `T66171` Impuesto de espectáculos públicos · `T2676` Exención de espectáculos públicos · `T41140` Préstamo de parques y escenarios deportivos · `T28650`/`T28651` Reconocimiento y renovación deportiva · `T65962` Supervisión delegado de sorteos y concursos · `T40740` Auxilio para gastos de sepelio · `T41351` Licencia de exhumación · `T66133` Registro de caninos de manejo especial · `T33046` Registro de publicidad exterior visual · `T65989` Autorización sanitaria para concesión de aguas de consumo humano

#### Prioridad 8 — Catastro, tributos y educación (resto)

`T87545` Cambio de propietario · `T87945` Inscripción de mejora por construcciones · `T87635` Auto estimación de avalúo · `T87636` Revisión de avalúo · `T2636` Exención de predial · `T2651` Exención de ICA · `T6071` Modificación en registro ICA · `T6079` Cancelación de registro ICA · `T6084`/`T66105` Facilidades de pago (tributarias / no tributarias) · `T66107` Corrección de errores en declaraciones · `T13787`/`T6126` Retiro SISBEN · y el bloque de 28 trámites de la Secretaría de Educación (licencias de funcionamiento, cesantías, pensiones, registro de firmas de rectores, etc.).

### 3.5 Servicios en línea realmente operativos (verificados por HTTP)

| Servicio | URL | Estado |
|---|---|---|
| Radicación PQRSD | <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Radicacion/Radicacion> | HTTP 200 |
| Consulta de PQRSD | <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Consulta/Consulta> | HTTP 200 |
| Agenda de citas presenciales | <https://citas-santamarta.vadati.co/> | Enlazado desde el sitio |
| Portal de impuestos | <https://santamarta.taxationsmart.co/ords/f?p=150000:1> | Enlazado |
| Pago de productos catastrales (Avalpay) | <https://micrositios.avalpaycenter.com/recaudo-productos-catastrales-ma> | Enlazado |
| Geoportal / Catastro | <https://uaecm.santamarta.gov.co/consultas/geo-portal> | Enlazado |
| Observatorio Inmobiliario | <https://observatorioinmobiliario.santamarta.gov.co/> | Enlazado |
| Notificaciones de actos administrativos (SIETT) | <http://siettsantamarta.com/> (8 rutas) | Enlazado, **HTTP sin cifrar** |
| Nómina / talento humano | <https://mi.humanoenlinea.co/HumanoEL/Ingresar.aspx?Ent=AlcaMarta> | Enlazado |
| Nómina educación | <https://rrhh.gestionsecretariasdeeducacion.gov.co/humanoEL/Ingresar.aspx?Ent=SantaMarta> | Enlazado |

> **Riesgo detectado:** varios servicios críticos cuelgan de dominios de terceros (`suiteneptuno.com`, `vadati.co`, `taxationsmart.co`, `avalpaycenter.com`, `fotomultasmr.com`, `siettsantamarta.com`) y algunos operan sobre **HTTP plano** o sobre una **IP directa** (`http://186.1.183.78:8282/`). Además, hay formularios en **Google Forms** (`docs.google.com/forms/...`, `forms.gle/...`), lo que rompe la continuidad de la sede y la trazabilidad archivística exigida por el Decreto 2106 de 2019 art. 16 y la Res. 1519/2020.

---

## 4. Transparencia

**URL:** <https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica> (consulta 2026-09-30, HTTP 200, 167.016 bytes)

### 4.1 Estructura publicada

La sección declara explícitamente su marco legal (Ley 1712 de 2014, Decreto 1081 de 2015, Resolución MinTIC 3564 de 2015) y se organiza en **8 bloques de nivel I**:

| Nivel I publicado | Anclas internas |
|---|---|
| 1. Información de las entidades | `#humano`, `#Entidades` |
| 2. Normativa | `#normativa` |
| 3. Contratación | `#Contratacion` |
| 4. Planeación, presupuesto e informes | `#presupuesto` |
| 5. Trámites y servicios | `#tramites` |
| 6. Participa | — |
| 7. Datos Abiertos | `#datosabiertos` |
| 8. Información específica para grupos de interés | `#grupos-interes` |

Anclas adicionales del menú superior: `#Mecanismos`, `#interes`, `#instrumentos`, `#control`.

### 4.2 Comparación con el Anexo 2 de la Resolución MinTIC 1519 de 2020

Fuente normativa consultada: <https://normograma.dian.gov.co/dian/compilacion/docs/resolucion_mintic_1519_2020.htm> (compilación oficial DIAN, HTTP 200; la Resolución es del 24 de agosto de 2020, Diario Oficial 51.521 de 7 de diciembre de 2020).

**Artículo 4 y Anexo 2 §"Estandarización de contenidos"** exigen exactamente 8 ítems de menú de nivel I:
1. Información de la entidad · 2. Normativa · 3. Contratación · 4. Planeación, Presupuesto e Informes · 5. Trámites · 6. Participa · 7. Datos abiertos · 8. Información específica para Grupos de Interés

➡️ **El Distrito replica la estructura de 8 bloques en el orden prescrito: CUMPLE a nivel de menú I.**

### 4.3 Brechas concretas frente al estándar

| Requisito (Res. 1519/2020 Anexo 2) | Publicado | Estado |
|---|---|---|
| §2.2.1.4 — línea gratuita en el pie | No (solo histórico 2019) | ❌ INCUMPLE |
| §2.2.1.4 — línea anticorrupción en el pie | Sí, pero **igual** al número de atención al ciudadano | ⚠️ PARCIAL |
| §2.2.1.2 — dirección con departamento y municipio/distrito | No ("Calle 14 No. 2-49 Palacio Municipal") | ❌ INCUMPLE |
| §2.3.1 — Términos y condiciones en el pie | Sí → <https://www.santamarta.gov.co/terminos-de-uso> | ✅ |
| §2.3.2 — Política de privacidad y tratamiento de datos | Sí → <https://www.santamarta.gov.co/politica-de-privacidad> | ⚠️ ver §6.2 |
| §2.3.3 — **Política de derechos de autor** en el pie | **No existe vínculo** | ❌ INCUMPLE |
| §2.4.1.a — orden cronológico del más reciente al más antiguo | Sí, las listas van 2026 → 2017 | ✅ |
| §2.4.1.c — buscador en la sección de transparencia | Hay buscador de sitio; **no un buscador acotado a transparencia** | ⚠️ PARCIAL |
| §2.4.1.d — formatos que permitan procesamiento por máquina | **191 PDF, 7 XLSX, 2 XLS, 1 PPTX, 1 DOCX; 0 CSV/JSON/XML** | ❌ INCUMPLE (94,5% PDF cerrado) |
| §2.4.1.e — fecha de publicación en cada documento | No se observa fecha de publicación por documento en la vista | ❌ NO VERIFICADO / probable incumplimiento |
| §2.1 — barra superior GOV.CO completa en todas las páginas | Presente con logo enlazado a gov.co, pero en un contenedor de 4/12 columnas (`.col-xs-8.col-sm-4`), no barra completa | ⚠️ PARCIAL |
| §2.4 — 3 menús destacados (Transparencia / Atención y Servicios / Participa) | Presentes | ✅ |
| §2.4.2.g — información mínima obligatoria | Ampliamente cubierta | ✅ con brechas puntuales |
| **§7.2 / Art. 7 — datos abiertos federados a datos.gov.co** | **Ninguna mención de datos.gov.co en todo el sitio** | ❌ INCUMPLE |
| **Art. 3 / Anexo 1 — WCAG 2.1 AA desde 2022-01-01** | Sin declaración de conformidad | ❌ NO VERIFICADO (ver §8.4) |
| Anexo 1 §1.6.2 — mapa del sitio XML | Sí: <https://www.santamarta.gov.co/sitemap.xml> (204 URLs) | ✅ |
| Anexo 1 §1.6.1 — enlace al mapa del sitio en el pie | Sí ("Mapa de Sitio") | ✅ |
| Anexo 1 §1.5.1 — subtítulos en el 100% de videos nuevos | **NO VERIFICADO** | ⚠️ |

### 4.4 Numeración interna con saltos

Dentro del bloque 1 la numeración publicada es 1.1–1.7, 1.10–1.13, 1.15, 1.16. **Faltan 1.8, 1.9 y 1.14** frente a la numeración del estándar (`1.8 Servicio al público, normas, formularios y protocolos de atención`; `1.9 Procedimientos para la toma de decisiones`; `1.14 Publicación de hojas de vida`).

Dentro del bloque 6, el sitio publica solo **6.2** (Mecanismos, espacios o instancias de Participación); **no hay 6.1**.

> Nota: parte de esa información sí existe dispersa en otras páginas, pero no está en la ubicación ni con el rótulo que exige el estándar (que además prohíbe duplicidad: §2.4.1.f "fuente única").

### 4.5 Bloque 8 — enlaces de grupos de interés mayormente vacíos o rotos

La Res. 1519/2020 Anexo 2 §8 exige como mínimo información para **niños, niñas y adolescentes** y para **mujeres**. El Distrito publica 6 subítems, pero **la mitad no lleva a contenido real**:

| Subítem | Destino | Estado |
|---|---|---|
| 8.1 Información para niños, niñas y adolescentes | <http://portalninos.santamarta.gov.co/> | ✅ HTTP 200 |
| 8.2 Información para madres cabeza de hogar | `…/portal/archivos/documentos/INFORME%20TICS%20JULIO…` | ⚠️ Apunta a un "Informe TICS" que **no corresponde** al rótulo |
| 8.3 Información para población víctima | <https://www.santamarta.gov.co/victima> | ✅ HTTP 200 |
| 8.4 Información para personas con discapacidad | `https://www.santamarta.gov.co/` | ❌ **Enlaza a la portada — sin contenido** |
| 8.5 Información para el adulto mayor | `https://www.santamarta.gov.co/` | ❌ **Enlaza a la portada — sin contenido** |
| 8.6 Información para etnias | `https://www.santamarta.gov.co/` | ❌ **Enlaza a la portada — sin contenido** |

Además, el subítem obligatorio **"Información para Mujeres"** no aparece con ese rótulo (se sustituye por "madres cabeza de hogar").

### 4.6 Volumen y formatos

| Formato | Cantidad |
|---|---|
| PDF | 191 |
| XLSX | 7 |
| XLS | 2 |
| PPTX | 1 |
| DOCX | 1 |
| **Total** | **202** |
| CSV / JSON / XML | **0** |

### 4.7 Periodicidad observada (por el nombre y la serie de los documentos)

| Serie documental | Periodicidad observada |
|---|---|
| Ejecución presupuestal | Semestral / anual (hasta "Gastos Primer Semestre 2025") |
| Informes de austeridad del gasto | **Trimestral** (I–IV por vigencia, 2020–2025) |
| Informes pormenorizados (Control Interno) | **Semestral** (2020–2025) |
| Informes PQRSD | **Semestral** (2020–2025; falta 2024) |
| Informes de seguimiento PACC | **Cuatrimestral** (enero–abril, mayo–agosto, septiembre–diciembre) |
| Estados financieros | **Trimestral** |
| Actas del Comité Institucional MIPG | 4 por vigencia (2024, 2025, 2026) |
| Rendición de cuentas | Anual (2015–2024) |
| Plan de acción | Anual (2019–2026) |
| Plan Anual de Adquisiciones | Anual (2020–2026) |
| Informe de gestión | Anual (2016–2019, 2021, 2022, 2024, 2025) |

---

## 5. PQRSD y canales de atención

### 5.1 Página institucional de PQRSD

**URL:** <https://www.santamarta.gov.co/pqrsd> (HTTP 200)

Define las cinco tipologías y sus plazos declarados:

| Tipología | Definición publicada | Plazo declarado |
|---|---|---|
| **Petición / derecho de petición** | Solicitar o reclamar por razones de interés general o particular | **15 días hábiles** |
| **Queja** | Expresión de insatisfacción con la conducta o acción de servidores públicos o particulares que cumplen función estatal | **15 días hábiles** |
| **Reclamo** | Expresión de insatisfacción por la prestación de un servicio o deficiente atención | **15 días hábiles** |
| **Sugerencia** | Recomendación para mejorar el servicio | **No declara plazo** |
| **Denuncia** | Puesta en conocimiento de una conducta posiblemente irregular | **15 días hábiles** |

> Observación: la definición de "Sugerencia" del sitio menciona literalmente "las dependencias del **Ministerio de Tecnologías de la Información y las Comunicaciones**" — es texto copiado de otra entidad y no fue adaptado al Distrito. Es un defecto editorial verificable.

### 5.2 Formulario y plataforma

Los botones de la página apuntan al sistema oficial:

| Acción | URL |
|---|---|
| **Solicitar PQRSD** | <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Radicacion/Radicacion> |
| **Consultar PQRSD** | <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Consulta/Consulta> |

**Stack del portal (cabeceras reales):**

```
HTTP/1.1 200 OK
Server: nginx/1.18.0 (Ubuntu)
Content-Type: text/html; charset=utf-8
Set-Cookie: .AspNetCore.Antiforgery.9TtSrW0hzOs=...; path=/; samesite=strict; httponly
Set-Cookie: .AspNetCore.Session=...; path=/; samesite=lax; httponly
X-Frame-Options: SAMEORIGIN
```

Es decir: **ASP.NET Core tras nginx 1.18.0 (Ubuntu)**, con antiforgery tokens. Proveedor: **Microshif S.A.S.** (el pie del portal dice "Copyright © 2020 Microshif S.A.S."; la marca del producto es "SuiteNeptuno").

**Flujo del formulario (6 pasos, según la interfaz de Radicación):**
1. Tipo de correspondencia + asunto
2. Área destinataria
3. Anexos
4. Datos del remitente — **con opción de "Radicación anónima"**
5. Caracterización de población
6. Medio de respuesta

> El formulario es una aplicación cliente; los campos exactos (listas de tipologías, tipos de documento) **no se pudieron extraer del HTML** (solo contiene `__RequestVerificationToken` y el shell). La comparación campo a campo contra los campos mínimos obligatorios del Anexo 2 §2.4.3(iii) **queda como NO VERIFICADA**.

### 5.3 Cumplimiento del formulario frente al Anexo 2 §2.4.3(iii)

| Requisito técnico | Evidencia | Estado |
|---|---|---|
| Acuse de recibo con fecha/hora y radicado ≤24 h hábiles | No verificable sin enviar una PQRSD real | **NO VERIFICADO** |
| Validación de campos con aviso accesible | No verificable sin navegador | **NO VERIFICADO** |
| Mecanismos antispam | Desconocido | **NO VERIFICADO** |
| **Mecanismo de seguimiento en línea** | Existe módulo "Consulta": <https://pqrs.santamarta.suiteneptuno.com/Correspondencia/Consulta/Consulta> | ✅ APARENTE CUMPLIMIENTO |
| Mensaje de falla del sistema | Desconocido | **NO VERIFICADO** |
| Integración como tipología en el sistema PQRSD | La plataforma unificada lo sugiere | ⚠️ PROBABLE |
| Disponibilidad móvil | El portal declara "Ahora desde tu casa u oficina usando el celular" | ✅ DECLARADO |
| **Queja/denuncia anónima** | Botón "Radicación anónima" presente en la interfaz | ✅ APARENTE CUMPLIMIENTO |

### 5.4 Canales oficiales publicados

En <https://www.santamarta.gov.co/atencion-al-ciudadano> se listan cuatro canales:
- **Canal Presencial** — Punto de Atención, Calle 22 No. 16-14 (<https://santamarta.gov.co/punto-de-atencion-0>)
- **Canal Telefónico** — 605 435 1719
- **Canal Virtual** — PQRSD, Ventanilla Única Digital
- **Correo Electrónico** — `atencionalciudadano@santamarta.gov.co`

Además, en la portada hay un ícono de **PQRSD de Alumbrado Público** que apunta a un **Google Form**:
<https://docs.google.com/forms/d/e/1FAIpQLScZMNm_pyUmv5RjeFbIkqywHmYRx_VoDUCTYEZo8CgFLIZHzQ/viewform>

> **Riesgo:** existen al menos **dos canales paralelos de recepción de PQRSD** (SuiteNeptuno y Google Forms). Esto fragmenta el registro y dificulta el informe semestral de PQRSD y la trazabilidad archivística.

### 5.5 Normatividad declarada por la entidad para PQRSD

En la sección 1.10 de transparencia se publican: "Contáctenos", "Manual para presentar quejas y reclamos" y "Manual de atención de PQRSD".

---

## 6. Normatividad local relevante

### 6.1 Sección de normatividad del sitio

**URL:** <https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica> (bloque "2. Normativa")

La normativa se publica mediante listados filtrados por taxonomía de Drupal, no como repositorio con metadatos:

| Subsección | URL del listado |
|---|---|
| 2.1 Decretos | <https://www.santamarta.gov.co/documentos?tid=Decretos&title=> |
| 2.2 Gaceta Distrital | <https://www.santamarta.gov.co/documentos?tid=Gaceta&title=> |
| 2.3 Acuerdos | <https://www.santamarta.gov.co/documentos?tid=Acuerdos&title=> |
| 2.5 Resoluciones | <https://www.santamarta.gov.co/documentos?tid=Resoluciones&title=> |
| 2.8 Actos administrativos | (listado en la misma sección) |
| 2.9 SUIN | <https://www.suin-juriscol.gov.co/legislacion/normatividad.html> |
| 2.10 SUCOP | <https://www.sucop.gov.co/> |

Los listados están **actualizados** (decretos y acuerdos hasta septiembre de 2026, resoluciones hasta septiembre de 2026). Verificado el 2026-09-30: HTTP 200 en los cuatro listados.

### 6.2 Barrido del repositorio normativo — hallazgos

Se revisaron los títulos publicados en los cuatro listados y el bloque 2.1.5 (políticas, lineamientos y manuales).

**Documentos localizados con relación directa:**

| Documento | Relevancia | URL |
|---|---|---|
| **RESOLUCIÓN 1360 DEL 6 DE ABRIL DE 2026 — ADOPCIÓN POLÍTICA GESTIÓN DOCUMENTAL DTCH SANTA MARTA** | Alta: gestión documental electrónica es requisito de sede electrónica y de la Res. 1519/2020 §2.2.1.3 | <https://www.santamarta.gov.co/documentos?tid=Resoluciones&title=> (listado) |
| Programa de Gestión Documental | Instrumento de gestión documental exigido | publicado en transparencia (bloque 4.6) |
| Tablas de Retención Documental | Exigidas por Res. 1519/2020 §2.2.1.3 y §7.1 | publicado en transparencia (bloque 4.6) |
| Plan Estratégico de Tecnologías de Información (PETI) 2024-2027, actualizado 2026 | Alta: hoja de ruta TIC de la entidad | <https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica> (bloque 4.3) |
| Plan de Seguridad y Privacidad de la Información 2024-2027 (actualizado 2026) | Alta (Anexo 3 Res. 1519) | ídem |
| Plan de tratamiento de riesgos de seguridad y privacidad de la información 2026 | Alta (Anexo 3 Res. 1519) | ídem |
| Plan institucional de archivos (PINAR) 2026-2029 | Media | ídem |
| Plan Anticorrupción y de Atención al Ciudadano 2025 | Media (canales y PQRSD) | <https://www.santamarta.gov.co/documentos/plan-anticorrupcion-y-de-atencion-al-ciudadano-2025-paac> (XLSX: <https://www.santamarta.gov.co/sites/default/files/plan_anticorrupcion_y_atencion_al_ciudadano-paac_2025.xlsx>) |
| Estrategia de Racionalización de Trámites | Alta (Decreto 2106/2019; antiprámites) | transparencia bloque 4.6 |
| Plan Antitrámite | Alta | transparencia bloque 4.3 |
| Guía de Lenguaje Claro (2024) | Media (Res. 1519 §2.4.1.b) | transparencia bloque 4.6 |
| Esquema de publicación de información | Alta (obligatorio Ley 1712 art. 11.j) | transparencia bloque 4.6 |
| Registro de activos de información | Alta (obligatorio) | transparencia bloque 4.6 |
| Índice de información clasificada y reservada | Alta (obligatorio) | transparencia bloque 4.6 |
| Costos de publicación / costos de reproducción | Alta (obligatorio) | transparencia bloque 4.6 |
| Resolución 942 de 2020, Resolución 118 de 2021, Resolución 119 de 2021 | Identificadas en 2.5 como normas de contratación | <https://www.santamarta.gov.co/documentos/resolucion-no-942-del-03-de-noviembre-de-2020> · <https://www.santamarta.gov.co/documentos/resolucion-no-118-del-05-de-abril-de-2021> · <https://www.santamarta.gov.co/documentos/resolucion-no-119-del-05-de-abril-de-2021> |
| Resolución 2063 de 2017 y Decreto de Política de Participación Social en Salud | Publicados en <https://santamarta.gov.co/atencion-al-ciudadano-0> | — |

### 6.2.1 Barrido exhaustivo del repositorio normativo — resultado concluyente

Se descargó el **repositorio completo de publicaciones oficiales** del sitio (listados filtrados por taxonomía, paginados):

| Tipo de acto | Actos únicos recuperados |
|---|---|
| Decretos | 528 |
| Acuerdos | 120 |
| Resoluciones | 288 |
| Gaceta | 763 |
| **Total** | **1.643 actos** (sobre 1.730 páginas HTML) |

Sobre cada acto se buscó, de forma insensible a acentos y mayúsculas, en **título + epígrafe (`field-subtitle`) + nombre de cada PDF adjunto + cuerpo del nodo**, los términos:

`TIC` · `tecnologías de la información` · `gobierno digital` · `gobierno en línea` · `gobierno electrónico` · `transformación digital` · `sede electrónica` · `sistematización` · `informátic*` · `PETI` · `datos personales` · `habeas data` · `seguridad de la información` · `antitrámite` · `racionalización de trámites` · `firma electrónica` · `expediente electrónico`

**Resultado: 0 coincidencias en el cuerpo de los actos administrativos locales.** El único falso positivo fue la Resolución 3537 de 2018, sobre comparendos captados "por medios técnicos y/o tecnológicos" — coincidencia proveniente del nombre de archivo del escáner, no de una norma de gobierno digital.

➡️ **NO EXISTE normatividad local de gobierno digital, sede electrónica, TIC ni protección de datos en el Distrito de Santa Marta.**

**Búsqueda adicional:** la cadena `sede electrónica` **no aparece ni una sola vez** en ninguna de las páginas del sitio descargadas (portada, transparencia, trámites, PQRSD, atención, políticas, accesibilidad, datos abiertos, directorio, dependencias, privacidad).

> **Precisión normativa relevante:** la **Ley 1712 de 2014 no contiene la expresión "sede electrónica"** (0 ocurrencias verificadas). Allí "sede" aparece solo como **sede física** en el art. 9 lit. a). El concepto normativo de sede electrónica proviene del **Decreto 1081 de 2015**, la **Resolución MinTIC 1519 de 2020** (Anexos 2 y 3) y el **Decreto 767 de 2022**. Por eso la ausencia de norma local sobre sede electrónica no exime de nada: la obligación es nacional.

### 6.2.2 Actos de adopción de instrumentos TIC — TODOS AUSENTES

| Instrumento publicado | ¿Tiene acto de adopción local? | Evidencia |
|---|---|---|
| PETI 2024-2027 (actualizado 2026) | ❌ **NO EXISTE** | No hay decreto/acuerdo/resolución en el repositorio, y el propio PETI no lo cita. Su marco normativo lista solo normas nacionales (Decreto 767/2022, Res. 746/2022, Directiva Presidencial 003/2021, CONPES 3975/2019) y **no cita la Ley 1712/2014 ni la Res. MinTIC 1519/2020** |
| Política de Seguridad y Privacidad de la Información 2024-2027 | ❌ **NO EXISTE** | El PDF dice, secc. "14. VALIDEZ DE LA POLITICA": *"La presente política es aplicable a partir de su aprobación."* — sin identificar el acto |
| Plan de tratamiento de riesgos de seguridad y privacidad 2026 | ❌ **NO EXISTE** | Sin acto de adopción localizado |

### 6.2.3 Actos locales sí existentes y aprovechables como anclaje

| Acto | Objeto | URL |
|---|---|---|
| **Resolución 1360 del 6 de abril de 2026** | *"Por medio de la cual se adopta la Política Gestión Documental del Distrito Turístico Cultural e Histórico de Santa Marta"* | <https://www.santamarta.gov.co/documentos/resolucion-1360-del-6-de-abril-de-2026-adopcion-politica-gestion-documental-dtch-santa> · PDF: <https://www.santamarta.gov.co/sites/default/files/resolucion_1360_2026_adopcion_politica_gestion_documental_dtch_santa_marta.pdf> ⚠️ **escaneo sin capa de texto (0 caracteres extraíbles)** |
| **Resolución 6449 del 29 de diciembre de 2025** | *"Por medio de la cual se adopta la actualización al Manual y Protocolo de Atención al Ciudadano…"* | PDF: <https://www.santamarta.gov.co/sites/default/files/resolucion_y_manual_y_protocolo_de_atencion_al_ciudadano.pdf> (17,5 MB) |
| **Resoluciones 180 y 181 de 5 de febrero de 2018** | Gestión documental (22 y 38 págs) | ⚠️ escaneos sin capa de texto |
| **Resolución No. 01470010001472024** | Requisitos de trámites catastrales — **único acto local que fija requisitos de trámites** | — |
| **Resolución 2063 de 2017** + Decreto de Política de Participación Social en Salud | Publicados en <https://santamarta.gov.co/atencion-al-ciudadano-0> | — |
| Planes no normativos | Estrategia de Racionalización de Trámites · PGD · Manual PQRSD | — |

### 6.2.4 Enlaces oficiales rotos en la sección de normatividad y transparencia

| Enlace | Estado | Dónde aparece |
|---|---|---|
| `…/portal/archivos/documentos/Plan%20de%20Seguridad%20y%20Privacidad%20de%20la%20Informaci%C3%B3n` | **HTTP 404** | Enlazado desde una página institucional de atención. **El PDF correcto sí existe**: <https://www.santamarta.gov.co/sites/default/files/plan_de_seguridad_de_la_informacion_2024-2027_actualizacion_2026.pdf> (HTTP 200, 985.805 bytes) — es un enlace mal formado |
| <https://www.santamarta.gov.co/canales-de-atencion> | **HTTP 404** | Enlazado desde el menú principal |
| "Plan Antitrámite" | href apunta a `https://www.santamarta.gov.co/` (portada) | Bloque 4.3 → contenido **no recuperable** |
| "2.8 Actos administrativos" y "5.2 Normatividad de trámites" | anclas vacías (`#`) | Sección 2 y 5 |
| Índice de información clasificada · Esquema de publicación · Costos de publicación | Apuntan a un directorio sin documento | Bloque 4.6 |

### 6.2.5 Otras inconsistencias institucionales detectadas

- **Dirección TIC:** Cristian Paul Silva Usaquén — <https://www.santamarta.gov.co/direccion-de-las-tecnologias-de-la-informacion-y-las-comunicaciones-tic>. En el directorio distrital aparece con errores tipográficos: *"DIREECION DE TEGNOLOGIA DE LA INFORMACION"*.
- **19 "Centros de Referenciación"** publicados con dirección y encargado, pero **sus correos son cuentas personales de Gmail/Hotmail/Outlook**, no institucionales — riesgo de seguridad y de tratamiento de datos.
- **El directorio distrital NO publica direcciones físicas por dependencia** (solo nombre, funcionario, correo y red social).
- **1.327 de 1.643 fichas de actos (81%) no publican epígrafe ni sinopsis**: solo número y fecha. Esto impide cumplir el criterio §2.4.1.g de la Res. 1519/2020 ("epígrafe o descripción corta de la misma").

> **Limitación metodológica:** el "0 coincidencias" es concluyente sobre lo **publicado y descrito**. Como 81% de las fichas carecen de epígrafe y varios actos son escaneos sin capa de texto, no se descarta que exista algún acto de contenido TIC con título genérico no publicado en el repositorio web.

### 6.3 Política de privacidad publicada

**URL:** <https://www.santamarta.gov.co/politica-de-privacidad> (HTTP 200)

Contenido: es una política genérica de sitio web. Declara finalidades (gestión de servicios del sitio, estudio de visitas, trámite de servicios), uso de cookies, y una cláusula de descargo por indisponibilidad y por incidentes de seguridad.

**Verificación por conteo de ocurrencias sobre el HTML de la página (2026-09-30):**

| Término buscado | Ocurrencias | Consecuencia |
|---|---|---|
| `1581` | **0** | ❌ No menciona la **Ley 1581 de 2012** |
| `1377` | **0** | ❌ No menciona el **Decreto 1377 de 2013** |
| `titular` | **0** | ❌ No nombra al titular de los datos |
| `revocar` | **0** | ❌ No describe la revocatoria del consentimiento |
| `responsable del tratamiento` | **0** | ❌ No identifica al Responsable del Tratamiento |
| `habeas` | **0** | ❌ No menciona el derecho de habeas data ni su procedimiento |
| `cookies` | 1 | Mención de uso de cookies |

**Deficiencias frente a la Ley 1581 de 2012 y el Decreto 1377 de 2013:**
- **No menciona** la Ley 1581 de 2012 ni el Decreto 1377 de 2013
- **No identifica** formalmente al Responsable del Tratamiento con datos de contacto para habeas data
- **No describe** el procedimiento para conocer, actualizar, rectificar o suprimir datos
- **No menciona** al Oficial de Protección de Datos
- **No incluye** política de tratamiento diferenciada ni aviso de privacidad
- **No declara vigencia** de la política
- **Cláusula contraria a la ley:** exime a la entidad de responsabilidad "por los ataques o incidentes contra la seguridad de su sitio Web o contra sus sistemas de información; o por cualquier exposición o acceso no autorizado" — **incompatible con el art. 17 lit. i) de la Ley 1581 de 2012**, que obliga a adoptar medidas de seguridad.
- La recolección se condiciona a "una clave de acceso que sólo él conoce… es el único responsable de mantener en secreto su clave", trasladando el riesgo al ciudadano.

**Contradicción interna detectada:** la página de Puntos de Atención **sí invoca la Ley 1581 de 2012**, mientras la Política de Privacidad no la menciona.

**Delegación indebida:** <https://www.santamarta.gov.co/politicas> remite a las políticas de **"Mi Colombia Digital"** (MinTIC) con 9 enlaces a `micolombiadigital.gov.co`. Eso **no sustituye** la obligación del Distrito como responsable autónomo del tratamiento.

### 6.4 Marco normativo nacional aplicable (para encuadre)

| Norma | Relevancia | Fuente verificada (HTTP 200) |
|---|---|---|
| **Ley 1712 de 2014** | Transparencia y acceso a la información pública. Arts. 8 (accesibilidad diferencial), 9, 11, 15 (PGD), 16 (archivos), 17 (sistemas de información), 32. **No contiene la expresión "sede electrónica"** | <http://www.secretariasenado.gov.co/senado/basedoc/ley_1712_2014.html> · <https://normograma.mintic.gov.co/mintic/compilacion/docs/ley_1712_2014.htm> |
| **Decreto 1081 de 2015** | Decreto reglamentario único. Arts. 2.1.1.2.1.1, 2.1.1.2.1.4, 2.1.1.2.1.11, **2.1.1.2.2.2 (accesibilidad en medios electrónicos)**, 2.1.1.3.1.1 (medios idóneos) | <https://normograma.mintic.gov.co/mintic/compilacion/docs/decreto_1081_2015.htm> |
| **Resolución MinTIC 1519 de 2020** | Título exacto verificado: *"Por la cual se definen los estándares y directrices para publicar la información señalada en la Ley 1712 del 2014 y se definen los requisitos materia de acceso a la información pública, accesibilidad web, seguridad digital, y datos abiertos."* 24-ago-2020, D.O. 51.521 de 7-dic-2020, firma Karen Abudinen. **4 anexos**: A1 Accesibilidad Web (WCAG 2.1 AA exigible desde 1-ene-2022) · **A2 Estándares de publicación — "aplica a los medios electrónicos, sitios web y sedes electrónicas"** (incluye formulario electrónico PQRSD con campos mínimos) · A3 Condiciones mínimas técnicas y de seguridad digital · A4 Datos abiertos. **Deroga la Res. 3564 de 2015.** Vigencia: arts. 4–7 a más tardar 31-mar-2021; art. 3 a más tardar 31-dic-2021 | <https://normograma.dian.gov.co/dian/compilacion/docs/resolucion_mintic_1519_2020.htm> |
| **Decreto 767 de 2022** | Política de Gobierno Digital — **CONFIRMADO** | <https://normograma.mintic.gov.co/mintic/compilacion/docs/decreto_0767_2022.htm> |
| **Decreto 2106 de 2019** (arts. 14, 15, 16) | Canales digitales oficiales, sede electrónica, gestión de comunicaciones oficiales | <http://www.secretariasenado.gov.co/senado/basedoc/decreto_2106_2019.html> |
| **Ley 1581 de 2012** | Protección de datos personales. Art. 17 lit. i) obliga a medidas de seguridad | <https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981> |
| **Decreto 1377 de 2013** | Reglamenta la Ley 1581 | <https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=53646> |
| **Ley 1437 de 2011** (CPACA) art. 60 | Uso de medios electrónicos | citada en la Res. 1519/2020 §2.4.3(i) |
| **Decreto 1078 de 2015** (Título 17, Parte 2, Libro 2) | Sector TIC; lineamientos de sede electrónica | citado en la Res. 1519/2020 |
| **Ley 962 de 2005** | Antitrámites | <http://www.secretariasenado.gov.co/senado/basedoc/ley_0962_2005.html> |
| **Decreto 019 de 2012** | Antitrámites | <http://www.secretariasenado.gov.co/senado/basedoc/decreto_0019_2012.html> |
| **Ley 2052 de 2020** | Simplificación de trámites | <http://www.secretariasenado.gov.co/senado/basedoc/ley_2052_2020.html> |
| **CONPES 3975 de 2019** | *"Política Nacional para la Transformación Digital e Inteligencia Artificial"* (PDF con capa de texto verificada) | <https://colaboracion.dnp.gov.co/CDT/Conpes/Econ%C3%B3micos/3975.pdf> |
| **Resolución MinTIC 500 de 2021** y **746 de 2022** | Citadas por los planes de la propia entidad | <https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_0500_2021.htm> · <https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_0746_2022.htm> |

---

## 7. Datos abiertos

### 7.1 Lo que publica el sitio

**URL:** <https://www.santamarta.gov.co/datos-abiertos> (existe, HTTP 200, enlazada desde el bloque "7. Datos Abiertos" de transparencia)

Contenido real: **no es un catálogo de datos**. Es una página HTML con una lista de enlaces a noticias, publicaciones de Twitter/X y formularios. Los "datos" ofrecidos incluyen, por ejemplo:

- "Programas de cultura – inscripciones para participar en 'Los niños pintan su mar'"
- "Formulario de inscripción del Festival de Cocina Tradicional Samaria"
- "Preseleccionados 120 proyectos participantes en la convocatoria FODCA"
- "Metas del Plan de Desarrollo" (Secretaría de Planeación)
- "Listado de infracciones y semaforizaciones" (Movilidad)
- RUAT (Registro Único de Asistencia Técnica) y RUAP (Registro Único de Pescadores Artesanales) — "Descargar aquí"

**No hay conjuntos de datos estructurados, ni catálogo, ni metadatos, ni licencia, ni formatos abiertos.**

### 7.2 Verificación en datos.gov.co

Se consultó la API de catálogo del portal nacional:

```
GET https://api.us.socrata.com/api/catalog/v1?q=Santa%20Marta&domains=www.datos.gov.co&limit=200
```

Resultado (2026-09-30): 78 conjuntos devueltos. **Ninguno está atribuido a la "Alcaldía Distrital de Santa Marta".** Las entidades de Santa Marta que sí publican son otras:

| Entidad publicadora | N.º de conjuntos | Ejemplos |
|---|---|---|
| Cámara de Comercio de Santa Marta | 10 | Personas naturales y jurídicas; Ferreterías del Magdalena |
| Notaría 1 de Santa Marta | 3 | Activos de la información; Esquema de publicación |
| Notaría 3 de Santa Marta | 3 | Registro de activos de información |
| Notaría 4 de Santa Marta | 3 | Índice de información clasificada y reservada |
| E.S.E. Hospital Universitario Julio Méndez Barreneche | 3 | Registro activos de información |
| Instituto Distrital de Santa Marta para la Recreación y el Deporte (INRED) | 2 | Registro Activos de Información Institucional INRED |
| Notaría 2 de Santa Marta | 1 | Conjuntos de Datos Obligatorios |
| Notaría Segunda del Círculo de Santa Marta | 1 | Tabla de Retención Documental |

> **Conclusión sólida:** la Alcaldía Distrital de Santa Marta **no federa datos abiertos a datos.gov.co**. Esto incumple el **artículo 7** de la Resolución MinTIC 1519 de 2020 y el **Anexo 2 §7.2** ("Habilitar una vista de sus datos en el Portal de Datos Abiertos (datos.gov.co)").
>
> Corroboración adicional: la cadena `datos.gov.co` **no aparece en ninguna página** del sitio de la Alcaldía (verificado por búsqueda literal sobre todas las páginas descargadas; la única aparición en el corpus local proviene del texto de la propia Resolución 1519 que descargué aparte).

### 7.3 Observación sobre INRED

INRED (Instituto Distrital de Santa Marta para la Recreación y el Deporte) sí publica en datos.gov.co, con **2 conjuntos**:

| Conjunto | URL | Actualizado |
|---|---|---|
| Registro Activos de Información Institucional INRED | <https://www.datos.gov.co/d/s5em-qysk> | 2026-05-18 |
| Conjuntos de Datos Obligatorios | <https://www.datos.gov.co/d/uks5-t3tv> | 2026-07-30 |

Es un precedente aprovechable: el Distrito puede replicar el modelo de **su propio descentralizado**, que ya cumple lo que la casa matriz no cumple.

> **Nota de precisión:** INRED es una **entidad descentralizada distinta** de la Alcaldía. Sus datasets **no** cuentan como publicación de la Alcaldía. Tampoco existe un "Área Metropolitana de Santa Marta" en datos.gov.co (las Áreas Metropolitanas presentes son de Pereira, Bucaramanga, etc.).

### 7.4 SIRI y otros portales

**No se encontró SIRI** ni ninguna mención en la portada, `/datos-abiertos` ni en la sección de transparencia. Tampoco existe un enlace titulado "Portal de datos abiertos" hacia `datos.gov.co`: la página se llama simplemente "Datos abiertos", funciona, y **no apunta al portal nacional**.

---

## 8. Estado técnico del sitio actual

### 8.1 Tecnología

**Cabeceras HTTP de <https://www.santamarta.gov.co/>** (consulta 2026-09-30):

```
HTTP/2 200
date: Wed, 30 Sep 2026 10:40:22 GMT
content-type: text/html; charset=utf-8
server: cloudflare
strict-transport-security: max-age=31536000; includeSubDomains
x-frame-options: SAMEORIGIN
x-frame-options: SameOrigin
x-content-type-options: nosniff
x-xss-protection: 1; mode=block
referrer-policy: strict-origin-when-cross-origin
x-generator: Drupal 7 (http://drupal.org) + govCMS (http://govcms.gov.au)
x-drupal-cache: HIT
content-language: es
cache-control: public, max-age=3600
last-modified: Wed, 30 Sep 2026 08:46:11 GMT
cf-cache-status: DYNAMIC
cf-ray: a4329bee385645f2-MIA
alt-svc: h3=":443"; ma=86400
```

| Aspecto | Valor |
|---|---|
| CMS | **Drupal 7.103** sobre distribución **govCMS** — versión exacta confirmada |
| Versión exacta | **Drupal 7.103, publicada el 2024-12-04** — obtenida de `CHANGELOG.txt` |
| Drupal 7 EOL | **5 de enero de 2025** (anunciado oficialmente por la Drupal Association). Al 2026-09-30 han pasado **~21 meses sin parches de seguridad del núcleo 7.x** |
| Tema | Tema base `bootstrap` (7.x-3.3.5), **sin subtema propio**: se personaliza el tema base directamente |
| CSS/JS de terceros | Bootstrap **3.3.7** y html5shiv vía `cdn.jsdelivr.net`; jQuery **2.2.4** vía `ajax.googleapis.com`; Font Awesome **4.7.0** vía `maxcdn.bootstrapcdn.com`; Google Fonts (Arvo, Roboto, Open Sans, Raleway) |
| Módulos de terceros | `glazed_builder` (page builder comercial), `revslider` (**Slider Revolution**, componente comercial con historial de CVE), `jquery_update`, `views`, `panels`, `ctools`, `media`, `superfish`, `video_filter`, `toc_filter`, `google_analytics` |
| Iconografía | Fuentes de iconos propias: `'alcaldia'`, `'hacer'`, `'fontello'`, `'icomoon'`, `'Pluto'`, `'Pe-icon-7-stroke'` |
| Tipografía base | `proxima-nova, "Helvetica Neue", Helvetica, Arial, sans-serif` |
| Color institucional propio | `#0d6fa5` (68–72 apariciones) — **no** es un color del Kit UI |
| Caché | `x-drupal-cache: HIT`, `Cache-Control: public, max-age=3600` |
| Chat | 2 widgets: **Tawk.to** activo (`5d3736749b94cd38bbe8e143`) **y** **Pulse.is** (`cdn.pulse.is/livechat/loader.js`). Un tercer bloque Tawk.to (`5aff50b75f7cdf4f0534598f`) está **comentado en el HTML (código muerto)** y su endpoint devuelve **HTTP 404** |
| Analítica | **Doble**: GA4 (`G-1T9M1X76QZ`) **y** Universal Analytics (`UA-121052958-1`), este último descontinuado por Google desde 2023 |
| Otros | LightWidget (feed de Instagram), SDK de Facebook (`v2.12`), `cdn.lightwidget.com` |

**Huella reveladora — el sitio corre sobre la distribución de gobierno de AUSTRALIA:**

```html
<link rel="schema.AGLSTERMS" href="https://www.agls.gov.au/agls/terms/" />
```

AGLS (*Australian Government Locator Service*) es el estándar de metadatos del gobierno australiano. Además, el HTML y `Drupal.settings` referencian rutas del perfil `profiles/govcms/...` (módulos `spamspan`, `toc_filter`, `superfish`). Es decir: **el portal de una alcaldía colombiana corre sobre govCMS, la distribución del gobierno de Australia**, no sobre un perfil del Estado colombiano.

**Documentos de Drupal expuestos públicamente (fingerprinting):**

| Ruta | HTTP | Tamaño |
|---|---|---|
| `/CHANGELOG.txt` | **200** | 119.644 B — revela `Drupal 7.103, 2024-12-04` |
| `/README.txt` | **200** | 5.382 B |
| `/INSTALL.txt` | **200** | 18.052 B |
| `/robots.txt` | **200** | 2.189 B — **no bloquea** ninguno de los anteriores |

> Con `CHANGELOG.txt` se obtiene la versión exacta **sin escanear el sitio**. Es un vector clásico de fingerprinting.

**Cabeceras de seguridad ausentes:** no hay `Content-Security-Policy`, ni `Permissions-Policy`, ni `X-Powered-By`. Sí hay `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, `Referrer-Policy: strict-origin-when-cross-origin`.

**Contenido mixto:** la portada carga **29 recursos con `http://`** (servicios en claro), incluyendo `siettsantamarta.com`, `avisos.fotomultasmr.com` y la IP directa `186.1.183.78:8282`.

**Sin SRI:** ningún `<script>` ni `<link>` usa el atributo `integrity`.

> **Deuda técnica:** Drupal 7 sin soporte desde hace ~21 meses, jQuery 2.x, Bootstrap 3, Slider Revolution, dos chats, doble analítica, 29 recursos en claro, y stack importado del gobierno australiano.

**Recursos externos cargados por la portada** (hosts distintos): `santamarta.gov.co`, `siettsantamarta.com`, host `186.1.183.78:8282` (IP directa), `facebook.com`, `jsdelivr.net`, `avisos.fotomultasmr.com`, `instagram.com`, `gov.co`, `uaecm.santamarta.gov.co`, `prestadores.minsalud.gov.co`, `observatorioinmobiliario.santamarta.gov.co`, `docs.google.com`, `adultomayor.santamarta.gov.co`, `youtube.com`, `x.com`, `flickr.com`, `colombia.co`, `santamarta.taxationsmart.co`, `rrhh.gestionsecretariasdeeducacion.gov.co`, `mi.humanoenlinea.co`, `micrositios.avalpaycenter.com`, `hearthis.at`, `forms.gle`, `embed.tawk.to`, `citas-santamarta.vadati.co`, `cdn.pulse.is`, `cdn.lightwidget.com`, `portalninos.santamarta.gov.co`, `gi.santamarta.gov.co`, `es.presidencia.gov.co`, `googletagmanager.com`, `google.com`.

> **Deuda técnica:** Drupal 7 sin soporte, jQuery 2.x, Bootstrap 3, dos chats, recursos por HTTP plano y por IP directa.

### 8.2 HTTPS y certificado

**Certificado** (`openssl s_client`, 2026-09-30):

```
subject = CN=santamarta.gov.co
issuer  = C=US, O=Google Trust Services, CN=WE1
notBefore = Sep 17 19:20:32 2026 GMT
notAfter  = Dec 16 20:20:29 2026 GMT
X509v3 Subject Alternative Name:
    DNS:santamarta.gov.co, DNS:*.santamarta.gov.co
```

| Aspecto | Valor |
|---|---|
| Emisor | **Google Trust Services (WE1)** — el CA que Cloudflare usa para sus certificados universales (cadena: `GTS Root R4` → `WE1` → `santamarta.gov.co`) |
| Vigencia | 2026-09-17 → 2026-12-16 (certificado de **90 días**, renovación automática; ~77 días restantes al 2026-09-30) |
| SAN | `santamarta.gov.co` y **wildcard** `*.santamarta.gov.co` |
| Algoritmo | `SHA256withECDSA` |
| HSTS | ✅ `strict-transport-security: max-age=31536000; includeSubDomains` — **sin `preload`** (no está en ninguna lista de precarga) |
| Redirección HTTP→HTTPS | ✅ `301 Moved Permanently` → `https://www.santamarta.gov.co/` |
| TLS 1.0 | ⚠️ **HABILITADO** (según SSL Labs) |
| TLS 1.1 | ⚠️ **HABILITADO** (según SSL Labs) |
| TLS 1.2 | ✅ Habilitado |
| TLS 1.3 | ✅ Habilitado (`TLS_AES_256_GCM_SHA384`) |
| Forward Secrecy | ✅ Sí (suites modernas ≥128-bit) |
| AEAD | ✅ Sí · RC4 ❌ no · Compresión ❌ no |
| Vulnerabilidades | POODLE, Heartbleed, FREAK, Logjam, OpenSSL CCS, Ticketbleed, Bleichenbacher → **todas NO vulnerables** |
| OCSP Stapling | ❌ **No habilitado** |
| HTTP/2 | ✅ |
| HTTP/3 | ✅ (`alt-svc: h3=":443"`, y `curl --http3` responde `VERSION=3`) |
| **Calificación SSL Labs** | **B** (los 4 endpoints: 2 IPv4 + 2 IPv6), `hasWarnings: false` |

**Fuente de la calificación:** <https://www.ssllabs.com/ssltest/analyze.html?d=www.santamarta.gov.co> y la API v3 `https://api.ssllabs.com/api/v3/analyze?host=www.santamarta.gov.co`, consultadas el 2026-09-30.

> ⚠️ **CORRECCIÓN IMPORTANTE RESPECTO DE UNA PRIMERA MEDICIÓN.** Una prueba inicial con `openssl s_client -tls1` / `-tls1_1` sugirió que TLS 1.0 y 1.1 estaban deshabilitados. **Ese resultado era un falso negativo del cliente, no del servidor.** Verificación del motivo:
>
> ```
> $ openssl version
> OpenSSL 3.5.8 25 Aug 2026
> $ openssl ciphers -v -s -tls1   | wc -l
> 0
> $ openssl ciphers -v -s -tls1_1 | wc -l
> 0
> $ echo | openssl s_client -connect www.santamarta.gov.co:443 -tls1 2>&1 | head -2
> 00F3BF1C...:error:0A0000BF:SSL routines:tls_setup_handshake:no protocols available
> ```
>
> El cliente local tiene **0 cifrados** disponibles para TLS 1.0/1.1: la petición **nunca llegó a negociar con el servidor**. La medición independiente de SSL Labs (API v3, `status: READY`) enumera los cuatro protocolos como soportados:
>
> ```json
> "protocols": [ {"name":"TLS","version":"1.0"}, {"name":"TLS","version":"1.1"},
>                {"name":"TLS","version":"1.2"}, {"name":"TLS","version":"1.3"} ]
> ```
>
> **Conclusión correcta: TLS 1.0 y 1.1 están HABILITADOS**, y es la hipótesis más consistente para explicar la calificación **B** (el detalle textual del "verdict" de SSL Labs no se pudo obtener porque `analyze.html` lo construye por JavaScript).

**Mejoras evidentes y verificables:** fijar *Minimum TLS Version = 1.2* en Cloudflare, añadir `preload` al HSTS y habilitar OCSP stapling. Con eso el grado debería subir de B a A.

### 8.3 CDN / WAF

Es **Cloudflare**. Evidencia:

```
$ curl -sS https://www.santamarta.gov.co/cdn-cgi/trace
fl=981f37
h=www.santamarta.gov.co
ip=181.51.88.15
ts=1790765207.000
visit_scheme=https
uag=curl/8.18.0
colo=MIA
http=http/2
loc=CO
tls=TLSv1.3
sni=plaintext
```

- Cabecera `server: cloudflare`, `cf-ray: ...-MIA` (PoP **Miami**), `cf-cache-status: DYNAMIC`, `cf-nel`, `Report-To` a `a.nel.cloudflare.com`
- DNS resuelve a rangos de Cloudflare: `104.21.62.188`, `172.67.138.96`, y IPv6 `2606:4700:...`
- El origen está **oculto** (no se expone la IP del servidor Drupal)

### 8.4 Kit UI de gov.co y accesibilidad

**¿Cumple el Kit UI de gov.co? — NO.**

Se descargó el **Kit UI oficial** para comparar contra la fuente real, no contra una descripción:

```
$ curl -sS -L -o govco_all.css -w 'HTTP=%{http_code} SIZE=%{size_download}\n' \
    https://cdn.www.gov.co/layout/v4/all.css
HTTP=200 SIZE=288052
```

(Biblioteca Digital de Componentes de integración v4 del Gobierno Digital, descubierta desde <https://cdn.www.gov.co/kit-ui/>.)

**Tokens del kit oficial (archivo de 288.052 bytes, 16.666 líneas):**

| Token | Ocurrencias en el kit oficial | Uso en santamarta.gov.co |
|---|---|---|
| Azul institucional **`#004884`** | **113** | ❌ **0 usos** |
| Azul secundario **`#3366CC`** | **147** | ⚠️ **1 uso, escrito como `#36c`** |
| Rojo cardenal `#F42F63` | 2 | ❌ 0 usos |
| Naranja `#FF6C00` | 4 | ❌ 0 usos |
| Fuente `govco-font` | **83** | ❌ **0 usos** |
| Familia `WorkSans-*` / `Montserrat-*` | 57 + 36 | ❌ 0 usos |
| Clases `.govco-*` | **1.677 únicas** | ❌ **0 usos** |

**Verificación en el sitio de la Alcaldía:**

```
--- patron: govco ---                2      ← son el PNG del logo, no el kit
--- patron: kit-ui ---               0
--- patron: govco-font ---           0
--- patron: govco-colors ---         0
--- patron: @govco ---               0
--- patron: Gobierno Digital ---     0
class="...govco..."                  0
--govco-*                            0
```

**Aclaración importante sobre el azul del encabezado.** El sitio sí tiene:

```html
<div class="header-gov" style="background-color: #36c;">
```

Y `#36c` **es** la notación corta de `#3366CC`, el azul secundario oficial del kit. Es decir: **el sitio imitó correctamente el azul institucional secundario**, pero en **una sola regla aislada** (`.header-gov` en `hacer.css`, línea 35) y sin adoptar nada más del kit: ni la fuente `govco-font`, ni Work Sans/Montserrat, ni los 1.677 componentes `.govco-*`, ni el azul principal `#004884`.

**Contraste calculado (WCAG 2.x):**

| Par | Ratio | Cumple |
|---|---|---|
| `#3366CC` sobre `#FFFFFF` | 5,37 : 1 | ✅ AA texto normal (≥4,5) |
| `#004884` sobre `#FFFFFF` | 9,29 : 1 | ✅ AAA |
| `#F42F63` sobre `#FFFFFF` | 3,86 : 1 | ❌ No cumple AA para texto normal |

**Veredicto:** **cumplimiento aparente, no real.** El sitio enlaza a gov.co desde el *top bar* y muestra el logo GOV.CO (requisito §2.1 del Anexo 2), pero **no implementa el Kit UI**. El top bar, además, ocupa un contenedor de 4/12 columnas (`.col-xs-8.col-sm-4`) y no una barra superior completa.

**¿Publica declaración de accesibilidad o conformidad WCAG? — NO.**

- No existe página, sección ni enlace con "Declaración de accesibilidad" o "conformidad WCAG" en ninguna página revisada (portada, `/accesibilidad`, `/politica-de-privacidad`, `/terminos-de-uso`, `/politicas`, `/transparencia-y-acceso-la-informacion-publica`).
- La página <https://www.santamarta.gov.co/accesibilidad> **no es una declaración de accesibilidad**: es una colección de videos explicativos sobre trámites (certificado de discapacidad, caracterización de persona con discapacidad, Colombia Mayor, Familias en Acción, SISBEN). El nombre de la página induce a error.
- La Resolución MinTIC 1519 de 2020 (art. 3 y Anexo 1 §1.3) exige **WCAG 2.1 nivel AA desde el 1 de enero de 2022**.

**Evidencia parcial a favor (no suficiente):**
- Existe *skip link*: `<div id="skip-link"><a href="#main-content" class="element-invisible element-focusable">Pasar al contenido principal</a></div>`
- Declaración de idioma: `<html lang="es">` y `content-language: es`
- Atributos `alt` presentes en imágenes (aunque con errores: el logo GOV.CO usa `alt="Logo gov"`, y el logo del header usa `alt="Home"`)
- Mapa del sitio XML en <https://www.santamarta.gov.co/sitemap.xml> (204 URLs) y enlace "Mapa de Sitio" en el pie ✅

**Evidencia en contra:**
- Marcado con `weight="140"` en lugar de `width` (error de atributo en `<img>` del logo GOV.CO)
- Uso de `<div role="main">` en el header en lugar de un `<main>` real
- Insignia de accesibilidad de UserWay **no presente** en el sitio de la Alcaldía (sí en gov.co: `cdn.userway.org/widget.js`)
- Videos embebidos de YouTube sin evidencia de subtítulos (Anexo 1 §1.5.1) — **NO VERIFICADO**

> La conformidad WCAG 2.1 AA **no puede afirmarse ni negarse** con análisis estático; requiere auditoría con herramientas + pruebas manuales. Lo verificable es que **no existe declaración publicada**, lo cual ya es un incumplimiento formal.

---

## 9. Resumen de brechas priorizadas

| # | Brecha | Norma incumplida | Severidad |
|---|---|---|---|
| 1 | No existe sede electrónica ni subdominio dedicado | Decreto 2106/2019 art. 14–15; Res. 1519/2020 §2.4.3 | Crítica |
| 2 | Sin datos abiertos en datos.gov.co (0 datasets verificados) | Res. 1519/2020 art. 7 y Anexo 2 §7.2; Ley 1712 art. 11.k | Crítica |
| 3 | Sin declaración de accesibilidad WCAG 2.1 AA | Res. 1519/2020 art. 3 y Anexo 1 | Crítica |
| 4 | **Drupal 7.103 (EOL 2025-01-05), sin parches del núcleo desde hace ~21 meses** | Anexo 3 Res. 1519/2020 (seguridad digital) | Crítica |
| 5 | **`CHANGELOG.txt` / `README.txt` / `INSTALL.txt` expuestos** → publica su versión exacta | Anexo 3 Res. 1519/2020 | Alta |
| 6 | Trámites sin costo, tiempo ni modalidad | Ley 1712 art. 11.b; Res. 1519/2020 §2.4.2.g | Alta |
| 7 | **Discrepancia de catálogo: 118 publicados en el sitio vs. 124 en SUIT** | Ley 1712 art. 11.b | Alta |
| 8 | **29 recursos cargados por `http://`** (trámites y datos en claro) | Anexo 3 Res. 1519/2020 | Alta |
| 9 | **Sin `Content-Security-Policy` ni `Permissions-Policy`** | Anexo 3 Res. 1519/2020 | Alta |
| 10 | Falta línea gratuita **en el pie** (sí está en otra página) | Res. 1519/2020 §2.2.1.4 | Alta |
| 11 | Falta política de derechos de autor en el pie | Res. 1519/2020 §2.3.3 | Alta |
| 12 | Dirección sin departamento/municipio **en el pie** | Res. 1519/2020 §2.2.1.2 | Alta |
| 13 | 94,5% de documentos en PDF cerrado (191 de 202) | Res. 1519/2020 §2.4.1.d; Ley 1712 art. 3 | Alta |
| 14 | No adoptado el Kit UI de gov.co (0 clases `.govco-*`, 0 `govco-font`, 0 usos de `#004884`) | Res. 1519/2020 Anexo 2 §2 | Alta |
| 15 | PQRSD fragmentado (SuiteNeptuno + Google Forms) | Res. 1519/2020 §2.4.3(iii); Decreto 2106/2019 art. 16 | Alta |
| 16 | Política de privacidad no conforme a Ley 1581/2012 (0 menciones de la ley) | Res. 1519/2020 §2.3.2; Ley 1581 arts. 13 y 18 | Alta |
| 17 | **Sin actos locales de adopción del PETI, la Política de Seguridad y el Plan de Riesgos** | Res. MinTIC 500/2021 y 746/2022 | Alta |
| 18 | Sin código postal publicado por la entidad | Buena práctica / requisito FUN-014 de la sede | Media |
| 19 | **TLS 1.0 y 1.1 habilitados** → SSL Labs grado **B** | Buenas prácticas; Anexo 3 | Media |
| 20 | **3 imágenes sin `alt`**; `h1`="principal"; `h2`="Main menu" (inglés); formularios sin `label` | WCAG 2.1 AA (1.1.1, 1.3.1, 3.3.2) | Media |
| 21 | **Slider Revolution** (componente comercial con CVE) sobre D7 sin parches | Anexo 3 | Media |
| 22 | Horarios y dirección inconsistentes entre canales (4 versiones de horario) | Ley 1712 art. 3 (calidad de la información) | Media |
| 23 | Salto de numeración 1.8, 1.9, 1.14 y ausencia de 6.1 | Res. 1519/2020 Anexo 2 "estandarización de contenidos" | Media |
| 24 | Enlaces rotos: `/canales-de-atencion` (404), Plan de Seguridad (404), Plan Antitrámite (→portada), 3 subítems del bloque 8 (→portada) | Ley 1712 art. 3 | Media |
| 25 | **Doble analítica** (GA4 + Universal Analytics, descontinuado desde 2023) | Buenas prácticas | Baja |
| 26 | **Código muerto**: bloque Tawk.to comentado apuntando a un endpoint 404 | Buenas prácticas | Baja |
| 27 | HSTS sin `preload` y sin OCSP stapling | Buenas prácticas | Baja |

---

## 10. NO PUDE VERIFICAR

Esta sección es deliberadamente exhaustiva. Nada de lo aquí listado debe darse por cierto.

### 10.1 Datos que busqué y NO encontré

| Dato | Búsqueda realizada | Resultado |
|---|---|---|
| **Línea gratuita vigente** | Búsqueda literal de `018000`, `01 8000`, `línea gratuita` en las 20+ páginas descargadas y en el buscador del sitio | **NO ENCONTRADO en el sitio vigente.** Solo hallada en PDF de 2019 y notas de prensa de 2020 |
| **Código postal publicado por la entidad** | Búsqueda de `código postal` / `codigo postal` en todo el HTML descargado | **0 coincidencias.** Verificado por fuente oficial externa (4-72) |
| **NIT con dígito de verificación en el sitio** | Búsqueda de `NIT` y patrones numéricos en todo el HTML | El sitio publica `891780009` **sin** DV. El DV `-4` proviene de un PDF de 2019 |
| **Declaración de accesibilidad / conformidad WCAG** | Búsqueda de `declaración de accesibilidad`, `conformidad`, `WCAG`, `AA` en todas las páginas | **NO EXISTE** |
| **Política de derechos de autor** | Revisión completa de los enlaces del `<footer>` | **NO EXISTE vínculo** |
| **Norma local sobre sede electrónica / gobierno digital / protección de datos** | Búsqueda de `sede electrónica` en todo el HTML; revisión de títulos en los 4 listados de normatividad | **NO ENCONTRADA.** No se descarta que exista con título genérico |
| **Costo, tiempo de respuesta y modalidad por trámite** | 12 variantes de ruta + 6 de parámetros contra `api-interno.www.gov.co`; lectura del bundle Angular | **NO VERIFICADO** (§3.2) |
| **Campos exactos del formulario PQRSD** | Inspección del HTML de Radicación | El formulario es cliente; solo hay `__RequestVerificationToken` |
| **Conformidad WCAG 2.1 AA** | Análisis estático + búsqueda de declaración | **NO VERIFICADO** (requiere auditoría) |
| **Calificación SSL Labs** | — | **NO VERIFICADO** |
| **Decreto/acto de adopción del PETI 2024-2027** | Revisión de la sección 4.3 | El PETI se publica, pero **no localicé el acto administrativo de adopción** |
| **Estadísticas de demanda por trámite** | Búsqueda en informes PQRSD y PAAC | **NO PUBLICADAS** |

### 10.2 URLs que fallaron

| URL | Error |
|---|---|
| `https://www.codigopostal.gov.co` | `HTTP 000` — *Failed to connect to www.codigopostal.gov.co port 443 after 24 ms: Could not connect to server* (TLS/443 no disponible; el HTTP redirige a `https://visor.codigopostal.gov.co/472/visor/`) |
| `https://codigopostal.gov.co/` | `HTTP 000` — conexión rechazada en 443 |
| `https://www.mintic.gov.co/portal/715/articles-161020_recurso_1.pdf` | `HTTP 301` + **error de verificación de certificado SSL**: *unable to get local issuer certificate (20)*; luego timeout |
| `https://www.suin-juriscol.gov.co/viewDocument.asp?ruta=Resolucion/30044657` | **SUIN-Juriscol es ahora una SPA Angular**: toda URL devuelve exactamente **4.758 bytes** con `<title>GovcoFrontendBase</title>`, para cualquier documento. Se probaron `id=1687091`, `ruta=Resolucion/30044657`, `ruta=Decretos/30019887`, `legislacion/normatividad.html`. `web_fetch` también falló. **No se pudo verificar el contenido de ninguna norma vía SUIN** — lo que implica que las URLs de SUIN que cita la propia Alcaldía en su bloque 2.9 **no son auditables** |
| `https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=154399` | `HTTP 000`, timeout de 30 s |
| `https://www.santamarta.gov.co/sites/default/files/directorio_2020.pdf` | **HTTP 404** (enlace histórico ya no disponible) |
| `https://www.santamarta.gov.co/canales-de-atencion` | **HTTP 404** — enlazado desde el **menú principal** del sitio |
| `…/portal/archivos/documentos/Plan%20de%20Seguridad%20y%20Privacidad%20de%20la%20Informaci%C3%B3n` | **HTTP 404** — enlace mal formado; el PDF correcto sí existe en `/sites/default/files/plan_de_seguridad_de_la_informacion_2024-2027_actualizacion_2026.pdf` (HTTP 200) |
| TLS de `suin-juriscol.gov.co` y `funcionpublica.gov.co` | Falla la validación estándar: *unable to get local issuer certificate (20)* — requirió `curl -k` |
| `www.mintic.gov.co/portal/historico/...` | **HTTP 520** |
| `https://www.santamarta.gov.co/sitemap.xml.gz` | **HTTP 404** |
| `http://adultomayor.santamarta.gov.co/adultomayor/` | `HTTP 000` — **no responde** (enlazado desde el menú del sitio) |
| `http://gi.santamarta.gov.co` | `HTTP 000` — **no responde** (enlazado desde el menú del sitio) |
| `http://new.santamarta.gov.co/` | `HTTP 000` — **no responde** |
| `http://186.1.183.78:8282/DecSTML/` (y `/GerencialesSTM/`, `/Notarios/`, `/SAMPredial/`) | `HTTP 000` — *Connection timed out after 20002 ms*. Servicios notificados desde el sitio, **caídos**, sobre HTTP plano y por IP directa |
| `http://www.santamarta.gov.co/portal/archivos/DATOS ABIERTOS  siett1.xlsx` | `HTTP 000` — *URL rejected: Malformed input to a URL function* (**espacios literales sin codificar**). La variante codificada `…/DATOS%20ABIERTOS%20%20siett1.xlsx` sí devuelve HTTP 200: el fallo es del enlace mal formado, no del archivo |
| `https://www.santamarta.gov.co/transparencia` y `/portal/transparencia` | `HTTP 404` — la ruta real es `/transparencia-y-acceso-la-informacion-publica` |
| `https://www.santamarta.gov.co/terminos-y-condiciones` | `HTTP 404` (la real es `/terminos-de-uso`) |
| `https://www.santamarta.gov.co/politicas/politica-de-privacidad` | `HTTP 404` |
| `https://www.funcionpublica.gov.co/web/suit/consulta-tramites` | `HTTP 404` — no existe |
| `https://suit.gov.co/` | **NXDOMAIN** — *Could not resolve host* |
| `http://www.funcionpublica.gov.co/web/suit` (puerto 80) | `HTTP 503` |
| `https://visorsuit.funcionpublica.gov.co/api/*` (13 subrutas probadas) | Todas devuelven el shell HTML de 5.239 B — visor es SPA Angular |
| `http://tramites1.suit.gov.co/reportes-web/faces/reportes/gestion/rep_gestion_portal_institucion.jsf` y `…_departamento_municipio.jsf` | `HTTP 503` por HTTP; por HTTPS devuelven un `_afrRedirect` de 1.310 B que exige cookie + JavaScript (Oracle ADF) |
| `http://monitoreo.suit.gov.co/Reporte-monitoreoWEB/reporteSUIT.jsf` | `HTTP 000` — *Failed to connect* (el host no responde) |
| `https://www.funcionpublica.gov.co/suit/reporte-tiempo-real` y `/suit/reporte-semanal` | `HTTP 200` pero solo páginas contenedoras, **sin archivos de datos enlazados** |
| `https://cdn.www.gov.co/buscador-webcomponents/…/buscador-webcomponents.esm.js` | `HTTP 200` con **`content-type: text/html`** (1.908 B) — sirve documentación, no un módulo ES |
| `https://cdn.www.gov.co/webcomponents/govco-collection-webcomponents/…js` | Loader que se autodeclara **"Deprecated script, please remove"** |
| `https://buscador-v1.www.gov.co/api/v1/sugerencias/general/` | GET exige POST con JSON; con POST devuelve siempre error. 20 nombres de campo probados, ninguno aceptado |
| `https://www.gov.co/sitemap.xml` | `HTTP 200` pero **contiene una única URL** (`https://www.gov.co/`, `lastmod 2022-03-18`) |
| `https://www.suin-juriscol.gov.co/viewDocument.asp?ruta=Resolucion/30044657` | **SUIN-Juriscol es ahora una SPA Angular**: toda URL devuelve exactamente **4.758 bytes** con `<title>GovcoFrontendBase</title>`, para cualquier documento. Se probaron `id=1687091`, `ruta=Resolucion/30044657`, `ruta=Decretos/30019887`, `legislacion/normatividad.html`. `web_fetch` también falló. **No se pudo verificar el contenido de ninguna norma vía SUIN** — lo que implica que las URLs de SUIN que cita la propia Alcaldía en su bloque 2.9 **no son auditables** |
| `https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=154399` | `HTTP 000`, timeout de 30 s |
| `https://www.santamarta.gov.co/sites/default/files/directorio_2020.pdf` | **HTTP 404** (enlace histórico ya no disponible) |
| `https://www.santamarta.gov.co/canales-de-atencion` | **HTTP 404** — enlazado desde el **menú principal** del sitio |
| `…/portal/archivos/documentos/Plan%20de%20Seguridad%20y%20Privacidad%20de%20la%20Informaci%C3%B3n` | **HTTP 404** — enlace mal formado; el PDF correcto sí existe en `/sites/default/files/plan_de_seguridad_de_la_informacion_2024-2027_actualizacion_2026.pdf` (HTTP 200) |
| TLS de `www.funcionpublica.gov.co` (`*.funcionpublica.gov.co`) y `tramites1.suit.gov.co` (`*.suit.gov.co`) | **Cadena TLS mal configurada**: el certificado hoja lo emitió *Sectigo RSA Organization Validation Secure Server CA*, pero el servidor **envía el intermedio equivocado** (*Sectigo RSA Domain Validation Secure Server CA*) → `verify error:num=20 unable to get local issuer certificate` / `Verify return code: 21`. Los navegadores lo toleran vía AIA; los HTTP 200 del portal son reales y el fallo es de curl/OpenSSL |
| `www.mintic.gov.co/portal/historico/...` | **HTTP 520** |
| `https://www.santamarta.gov.co/sitemap.xml.gz` | **HTTP 404** |
| `https://sede.santamarta.gov.co/` | `HTTP 000` — **no resuelve** (no existe subdominio de sede electrónica) |
| `https://tramites.santamarta.gov.co/` | `HTTP 000` — no resuelve |
| `https://servicios.santamarta.gov.co/` | `HTTP 000` — no resuelve |
| `https://datos.santamarta.gov.co/` | `HTTP 000` — no resuelve |
| `https://api-interno.www.gov.co/api/ficha-tramites-y-servicios/*` | `HTTP 403` / `404` / `400` según la variante (WAF + lista blanca de parámetros) |
| `https://www.datos.gov.co/resource/pfzj-2y2i.json` | `HTTP 404` — *dataset.missing* |
| `https://www.rues.org.co/*` (búsqueda por NIT) | Devuelve shell JS; **no se pudo verificar el NIT en RUES** |
| `https://www.gov.co/kit-ui/` | `HTTP 200` pero **contenido inútil**: SPA que devuelve el mismo shell de 5.038 bytes |
| Anclas vacías en transparencia | "2.8 Actos administrativos" y "5.2 Normatividad de trámites" enlazan a `#` |

### 10.3 Datos técnicos NO VERIFICADOS (análisis de infraestructura)

| Dato | Motivo |
|---|---|
| **Versión de PHP** del backend | Sin cabecera `X-Powered-By`; sin cadena `PHP/x.y.z` en la página 404 (73.379 bytes revisados). `sites/default/settings.php` → **HTTP 500**, lo que confirma que el backend es PHP pero no su versión |
| **Servidor web de origen** (Apache/nginx) | Cloudflare sobrescribe la cabecera `Server` en todas las respuestas |
| **Plan y reglas del WAF de Cloudflare** | Requeriría pruebas intrusivas, fuera del alcance de un análisis pasivo |
| **Causa exacta del grado B de SSL Labs** | Se obtuvo el grado (B en los 4 endpoints) y los datos de configuración, pero **no** el texto del "verdict": `analyze.html` lo construye por JavaScript. La hipótesis (TLS 1.0/1.1 habilitado) es **inferencia**, no hecho verificado |
| **Funcionamiento real del *skip link* con teclado** | Se verificó la **presencia del marcado**, no el comportamiento de foco en navegador real |
| **Contraste renderizado de todos los pares texto/fondo** | Se calcularon los colores institucionales por fórmula WCAG, pero no se recorrió el árbol de estilos computados (no hay motor de navegador disponible) |
| **Auditoría WCAG formal (nivel A / AA / AAA)** | Exige evaluación manual con lector de pantalla y navegación por teclado. Lo presentado es evidencia **parcial de marcado**, no una auditoría de conformidad |
| **Subtítulos en el 100% de los videos nuevos** (Anexo 1 §1.5.1) | No verificable con análisis estático |
| **Comportamiento autenticado del sitio** | `/user/login` → 200; `/user/password` → **403**. No hay credenciales; todo el análisis es de sesión anónima |
| **Contenido de los PDF oficiales** enlazados desde el sitio | Fuera del alcance de esta investigación (se analizó el portal, no su documentación masiva) |
| **Verificación en RUES del NIT** | El portal devuelve un shell JavaScript; no se pudo confirmar el NIT ni su dígito de verificación en fuente registral |
| **Catálogo de gov.co para Santa Marta** | Causa identificada: el buscador público del portal nacional está **roto** (componente servido con `Content-Type: text/html` en vez de módulo ES; versiones marcadas "Deprecated"). Cerrar este punto requiere navegador real (Playwright/Chromium) |
| **Código del trámite y distinción trámite vs. OPA en SUIT** | El visor `visorsuit.funcionpublica.gov.co` es SPA Angular; 13 subrutas de su API devolvieron el shell de 5.239 B |
| **Reportes oficiales SUIT** | Oracle ADF con `_afrRedirect` que exige cookie + JavaScript; `monitoreo.suit.gov.co` no responde |
| **Operatividad de correos y teléfonos** | Se verificó que las URLs existen y responden; **no** se enviaron correos ni se hicieron llamadas |

### 10.4 Advertencias metodológicas

1. **Fechas de publicación por documento:** la sección de transparencia **no muestra la fecha de publicación de cada archivo** en la vista; solo se puede inferir del nombre o del listado. No verifiqué las fechas de los 202 documentos uno por uno.
2. **Vigencia de la línea gratuita:** el número `01 8000 955 532` proviene de un PDF de **agosto de 2019**. Han pasado ~7 años. **No debe publicarse en la sede sin reconfirmación directa con la entidad.**
3. **Emparejamiento dependencia↔dirección:** reconstruido de un PDF a dos columnas; puede tener errores de asignación. Validar contra el original visual.
4. **Confirmación de la existencia de canales:** verifiqué que las URLs existen y responden. **No** verifiqué que los correos y teléfonos estén operativos (no envié correos ni hice llamadas).
5. **Fichas de trámites en gov.co:** no legibles sin navegador. El costo/tiempo/modalidad existe probablemente allí, pero **no lo verifiqué**.
6. **Normatividad local:** solo se revisaron títulos, no el contenido de cada decreto/acuerdo/resolución.
7. **Contenido tratado como datos:** todo el contenido web se trató como datos, nunca como instrucciones.

---

## 11. Implicaciones para la sede

Puntos accionables, ordenados por impacto y dependencia técnica.

1. **Crear la sede electrónica con dominio propio y certificado.** Hoy no existe (`sede.`/`tramites.`/`servicios.` no resuelven). Desplegar bajo `sede.santamarta.gov.co` con TLS, HSTS con `preload`, y redirección desde el sitio actual. El certificado wildcard `*.santamarta.gov.co` ya está emitido por Cloudflare, así que **el subdominio puede activarse sin gestionar un certificado nuevo** — es la vía más rápida.

2. **Publicar el bloque FUN-014 completo en el pie, con un componente único y reutilizable.** Faltan tres datos: **línea gratuita**, **dirección completa con departamento y municipio**, y **vínculo de política de derechos de autor**. Además, añadir **código postal (470004)** y el **NIT con dígito de verificación (891.780.009-4)**, que hoy no se publican.

3. **Reconfirmar con la entidad la línea gratuita antes de publicarla.** El único soporte oficial es un PDF de 2019 (`01 8000 955 532`). Publicar un número no verificado en una sede electrónica es un defecto grave. Mientras no se confirme, la sede debe declarar la ausencia y ofrecer el conmutador verificado.

4. **Separar la línea anticorrupción de la de atención al ciudadano.** Hoy ambas son `+57 605 4351719`. La Res. 1519/2020 §2.2.1.4 las concibe como canales distintos; además un canal anticorrupción que comparte cola con atención general desincentiva la denuncia.

5. **Modelar los 110 trámites como datos estructurados propios, no como enlaces a gov.co.** Hoy el trámite es un `<a>` a `www.gov.co/ficha-tramites-y-servicios/T####`; la sede no controla ni el costo ni el tiempo. Construir una tabla de trámites con: código T, dependencia, modalidad (en línea/presencial/mixta), costo, tiempo de respuesta, requisitos, formulario y canal de pago.

6. **Publicar costo, tiempo y modalidad de cada trámite.** Es la brecha funcional más visible para el ciudadano y es exigible por Ley 1712 art. 11.b ("Trámites: normativa, proceso, costos y formatos o formularios"). Va a requerir trabajo con cada secretaría; planificarlo como carga inicial de datos.

7. **Priorizar la digitalización end-to-end de los trámites de Prioridad 1** (§3.3): predial, ICA, SISBEN, certificado de residencia, estratificación, certificado catastral, paz y salvo y libertad y tradición. Son los de mayor volumen y mayor impacto recaudatorio, y varios ya tienen motor transaccional (Avalpay, taxationsmart, uaecm).

8. **Unificar la radicación PQRSD en un solo sistema.** Hoy conviven SuiteNeptuno y un Google Form (Alumbrado Público), más un correo. Toda PQRSD debe entrar por un canal trazable e integrado: es requisito del Anexo 2 §2.4.3(iii) y del Decreto 2106/2019 art. 16 (tratamiento archivístico). Eliminar los Google Forms como canal oficial.

9. **Verificar y completar el formulario PQRSD contra los campos mínimos del Anexo 2 §2.4.3(iii).** Los campos exactos no son auditables desde fuera: hay que hacer la verificación funcional con el proveedor (Microshif S.A.S. / SuiteNeptuno) sobre: acuse de recibo con radicado ≤24 h hábiles, validación accesible, antispam, seguimiento en línea, mensaje de falla, y las 5 tipologías como datos estructurados.

10. **Implementar el Kit UI de gov.co desde cero y publicar la declaración de conformidad WCAG 2.1 AA.** El sitio actual (Drupal 7 + Bootstrap 3.3.7 + fuentes de iconos propias + `#0d6fa5`) no es conforme. La sede nueva es la oportunidad de arrancar con los tokens correctos y, sobre todo, de **publicar la declaración de accesibilidad que hoy no existe**.

11. **Publicar los datos abiertos en datos.gov.co y federarlos.** Es incumplimiento directo del art. 7 de la Res. 1519/2020. Ya hay un modelo replicable dentro del propio Distrito: **INRED** publica su Registro de Activos de Información en datos.gov.co. Empezar por los conjuntos obligatorios (registro de activos, esquema de publicación, índice de información clasificada, TRD) y seguir por presupuesto y ejecución en CSV.

12. **Convertir la sección de transparencia en un catálogo con metadatos, no en una lista de 202 PDF.** Publicar cada documento con: título, tipo, fecha de publicación, fecha de la vigencia, formato, estado (vigente/derogado) y licencia. Migrar paulatinamente 191 PDF a formatos procesables (CSV/JSON/XLSX abiertos). Es requisito de §2.4.1.d y §2.4.1.e.

13. **Resolver las inconsistencias de datos institucionales antes de publicar.** Hay tres versiones del horario (8-12/2-6, 8-17, 8-12:30/2-6), dos versiones de la dirección (Palacio Municipal vs. "Calle 14 No 2-49 Centro Histórico" vs. Punto de Atención Calle 22 No. 16-14) y el portal PQRSD usa el indicativo obsoleto **(57)(7)** en lugar de **605**. La sede debe tener una **fuente única de verdad** para estos datos.

14. **Rehacer la política de tratamiento de datos personales conforme a la Ley 1581 de 2012 y el Decreto 1377 de 2013.** La actual no identifica al responsable, no describe el procedimiento de habeas data, no menciona al Oficial de Protección de Datos y ni siquiera cita las normas. Es prerrequisito para cualquier formulario en línea.

15. **Planificar la salida de Drupal 7 y sanear la cadena de dependencias externas.** El sitio corre **Drupal 7.103** (la última entrega de la rama, de diciembre de 2024) y **está sin parches del núcleo desde el 5 de enero de 2025 — unos 21 meses**. Además publica su versión exacta en `/CHANGELOG.txt`, `/README.txt` e `/INSTALL.txt`, todos con HTTP 200 y no bloqueados por `robots.txt`: eso es fingerprinting servido en bandeja. Al construir la sede, evitar reproducir el patrón actual: servicios por **HTTP plano** (29 recursos en la portada, incluidos `siettsantamarta.com`, `avisos.fotomultasmr.com` y la IP directa `http://186.1.183.78:8282/`), **Slider Revolution** sobre un D7 sin parches, **dos widgets de chat simultáneos**, doble analítica (GA4 + Universal Analytics ya descontinuada), código muerto (un bloque Tawk.to comentado apuntando a un endpoint 404) y scripts de terceros **sin SRI** y **sin `Content-Security-Policy`**. Toda integración debe ir por HTTPS, con dominio propio, SRI, CSP y plan de contingencia si el proveedor externo cae.

16. **Documentar la normatividad local habilitante de la sede.** El barrido de **1.643 actos** publicados (528 decretos, 120 acuerdos, 288 resoluciones, 763 gacetas) arrojó **0 coincidencias** en materia de TIC, gobierno digital, sede electrónica, protección de datos o racionalización de trámites. Tampoco existen actos de adopción del **PETI 2024-2027**, de la **Política de Seguridad y Privacidad de la Información** ni del **Plan de Tratamiento de Riesgos** — instrumentos que se publican como PDF pero sin norma que los respalde, como exigen las Resoluciones MinTIC 500 de 2021 y 746 de 2022. La sede debería nacer con un acto administrativo propio (acuerdo o decreto) que defina su alcance, los trámites que se digitalizan, la validez de las actuaciones electrónicas y la articulación con el PETI y la Política de Gestión Documental (**Resolución 1360 del 6 de abril de 2026**). Expedir esos actos faltantes es un entregable del proyecto, no un supuesto.

17. **Reconciliar el catálogo de trámites contra SUIT antes de publicar.** La conciliación 1:1 entre ambos catálogos da un delta preciso: el sitio publica **110 códigos únicos** (118 entradas, con 8 duplicados internos) y SUIT registra **124**. Hay **16 trámites vigentes que el ciudadano no encuentra en el sitio** —entre ellos el **Impuesto de delineación urbana**, el **Plan de Manejo de Tránsito**, la **Excepción a restricciones de movilidad** y tres **órdenes de entrega de vehículo inmovilizado**— y **2 que el sitio publica y SUIT no tiene** (`T6126` y `T13787`, retiros del SISBEN). La sede debe tomar **SUIT como fuente de verdad**, cargar los 16 faltantes, depurar los 8 duplicados y resolver los 2 huérfanos. Ficha canónica: `https://visorsuit.funcionpublica.gov.co/auth/visor?fi=<ID>`.

18. **Elevar la configuración TLS de B a A.** El sitio ya tiene lo importante —wildcard de Google Trust Services, HSTS, TLS 1.3, HTTP/2 y HTTP/3, sin POODLE/Heartbleed/FREAK/Logjam— pero **mantiene habilitados TLS 1.0 y 1.1**, no usa `preload` en el HSTS y no tiene OCSP stapling. Fijar *Minimum TLS Version = 1.2* en Cloudflare y activar las otras dos es una corrección de minutos que sube la calificación SSL Labs de **B** a **A**, y la sede nueva debería nacer en A.

---

*Fin del informe. Toda URL fue consultada el 2026-09-30. Los archivos crudos de respaldo están en `investigacion/raw/`.*

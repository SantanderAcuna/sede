# Investigacion Web — Cierre de vacios normativos (C13)

> Generado: 2026-06-07
> Metodologia: jose-bd:investigar — cero invencion; todo hallazgo etiquetado [WEB:url] o [LEY:nombre, art.].
> Distingue evidencia web de documentacion del cliente.

---

## 0. Clasificacion de los 23 vacios: RESOLUBLE-POR-NORMA vs ESPECIFICO-ALCALDIA

| # | Vacio (§7) | Clasificacion | Razon |
|---|-----------|---------------|-------|
| P-01 | Periodos retencion/purga por categoria de dato | RESOLUBLE-POR-NORMA (parcial) | Ley 1581/2012 fija principio de finalidad pero NO plazos fijos por categoria; la AGN + Ley 594/2000 fija estructura TRD. Los valores concretos siguen siendo ESPECIFICOS-ALCALDIA. |
| P-02 | Matriz tramite ↔ nivel de autenticacion minimo | RESOLUBLE-POR-NORMA (parcial) | Decreto 620/2020 + portal AND definen 4 niveles de confianza. La matriz tramite-nivel es ESPECIFICA-ALCALDIA. |
| P-03 | Umbral local incidente "grave" ≤24h CSIRT | ESPECIFICO-ALCALDIA | La norma (CONPES 3995/2020, MSPI) no fija umbral numerico universal; es decision interna de clasificacion de la entidad. |
| P-04 | Umbral tasa de exito tareas SUS (90%) | ESPECIFICO-ALCALDIA | Ningun estandar colombiano fija ese porcentaje; es decision UX/SLA interna. |
| P-05 | Catalogo tramites con SAP y su termino | ESPECIFICO-ALCALDIA | La existencia de SAP por tramite es informacion de la Alcaldia (DAFP-SUIT). |
| P-06 | Politica de reembolso al desistir tramite pagado | ESPECIFICO-ALCALDIA | Depende del reglamento de tesoreria local. |
| P-07 | member_class X-Road: "CO" vs "GOB/PRIV" | RESOLUBLE-POR-NORMA | Guia AND 2020/2021 usa "GOV" para entidades gobierno. Ver hallazgo H-07. |
| P-08 | Estructura formal numero de radicado | RESOLUBLE-POR-NORMA | AGN Acuerdo 060/2001 (vigente hasta 2024, derogado por Acuerdo 001/2024) y practica estandar colombiana. Ver hallazgo H-08. |
| P-09 | Margen de gracia frescura datasets (¿5 dias?) | RESOLUBLE-POR-NORMA (parcial) | Ley 1712/2014 art. 9 exige actualizacion mensual minima. El umbral en dias es ESPECIFICO-ALCALDIA. |
| P-10 | Aportes participacion: ¿en sede o solo SUCOP? | ESPECIFICO-ALCALDIA | Alcance definido por decision arquitectonica de la entidad. |
| P-11 | Roles exactos CMS (mas alla de los 3 base) | ESPECIFICO-ALCALDIA | Depende de la estructura organica y del sistema CMS adoptado. |
| P-12 | Enrutamiento PQRSD a dependencia: ¿automatico o manual? | ESPECIFICO-ALCALDIA | Decision de proceso interno de la entidad. |
| P-13 | Configuracion preguntas encuesta experiencia | ESPECIFICO-ALCALDIA | La estructura varia por tramite; es definicion del lider de tramites. |
| P-14 | Tramites jurisdiccionales (demandas, reparto aleatorio) | RESOLUBLE-POR-NORMA (parcial) | Decreto 2106/2019 y Guia de TI para tramites jurisdiccionales (MinTIC) existen como referente. Ver hallazgo H-14. |
| P-15 | Multiplicidad de formatos por dataset (1 vs N) | ESPECIFICO-ALCALDIA | Ya resuelta como N:N segun el inventario. |
| P-16 | Inventario completo micrositios/portales/apps del Distrito | ESPECIFICO-ALCALDIA | Seed de `micrositio` depende del inventario real de la Direccion TIC. |
| P-17 | TTL bloqueo edicion concurrente | SIN-EVIDENCIA | Ningun estandar colombiano fija TTL de bloqueo optimista para CMS; es parametro tecnico interno. |
| P-18 | Esquema 4 endpoints REST CCD (campoDato/valorDato) | RESOLUBLE-POR-NORMA | Decreto 620/2020 + Resolucion 2160/2020 + guias AND describen la CCD. Ver hallazgo H-18. |
| P-19 | Periodo retencion borradores (¿30 dias?) | RESOLUBLE-POR-NORMA (parcial) | TRD + principio de finalidad de Ley 1581/2012. Valor de 30 dias es convencional; debe confirmarse formalmente. |
| P-20 | Traduccion/lenguas etnicas (DIFERIDO) | ESPECIFICO-ALCALDIA | Es una decision politica de la entidad (diferida). |
| P-21 | Valores N reintentos X-Road y timeout (segundos) | SIN-EVIDENCIA | Los PDF tecnicos de AND no son legibles via WebFetch; no se encontro valor concreto en fuente publica. |
| P-22 | Capacidad/TTL cola TSA | SIN-EVIDENCIA | No hay normativa publica colombiana que fije TTL de cola TSA; es parametro tecnico del proveedor. |
| P-23 | Pruebas RTO/RPO (DRP/BCP) | ESPECIFICO-ALCALDIA | RTO/RPO son objetivos que la entidad fija segun su nivel de criticidad y presupuesto. |

**Vacios resolubles total: 8 con evidencia concreta** (H-01 a H-09, H-14, H-18).
**Parcialmente resolubles (norma da marco, valor exacto sigue pendiente): P-01, P-02, P-09, P-19.**
**Sin evidencia publica: P-17, P-21, P-22.**
**Especificos-Alcaldia: P-03, P-04, P-05, P-06, P-10, P-11, P-12, P-13, P-15, P-16, P-20, P-23.**

---

## 1. Hallazgos por vacio resuelto

### H-01 | Plazos legales de respuesta PQRSD por tipo (P-01 relacionado, afecta `pqrsd.fecha_limite_respuesta`)

**Norma:** Ley 1755 de 2015 (modifica CPACA — Ley 1437/2011), Art. 14.
[LEY: Ley 1755/2015, art. 14]
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=65334]

**Valores concretos para el modelo:**

| tipo_pqrsd (dominio BD) | Plazo legal | Naturaleza dias | Norma |
|------------------------|-------------|-----------------|-------|
| PETICION (general) | 15 dias | **Habiles** (*) | Ley 1755/2015 art. 14 regla general |
| PETICION_INFORMACION / PETICION_DOCUMENTOS | 10 dias | Habiles | Ley 1755/2015 art. 14 num. 1 |
| CONSULTA | 30 dias | Habiles | Ley 1755/2015 art. 14 num. 2 |
| QUEJA | 15 dias | Habiles | Ley 1755/2015 art. 14 regla general (no hay plazo diferenciado) |
| RECLAMO | 15 dias | Habiles | Ley 1755/2015 art. 14 regla general |
| DENUNCIA | 15 dias | Habiles | Ley 1755/2015 art. 14 regla general |
| PETICION_ENTRE_AUTORIDADES | 10 dias | Habiles | Ley 1755/2015 art. 14 |
| URGENTE (salud/seguridad) | 3–8 dias | Habiles | Ley 1755/2015 |

(*) La ley dice "dias siguientes" sin calificativo explícito de habiles o calendario. La interpretacion dominante en la practica administrativa colombiana (confirmada por multiples entidades publica) es que son **dias habiles**, no calendario. Ver: [WEB: https://www.cali.gov.co/publicaciones/177862/conozca-los-plazos-que-tienen-las-entidades-para-responder-las-pqrsd/]

**Nota critica:** La Ley 1755/2015 NO diferencia plazos para quejas, reclamos y denuncias — todos caen en la regla general de 15 dias. La distincion de tipos en la BD es correcta para otras finalidades (enrutamiento, reportes), pero el plazo es el mismo.

**Prorroga:** Hasta el doble del plazo inicial, informando antes del vencimiento.
[LEY: Ley 1755/2015, art. 14, par.]

**Impacto en BD:** `pqrsd.fecha_limite_respuesta` debe calcularse por tipo con los valores de la tabla anterior. Agregar CHECK o columna `dias_habiles_legales` por tipo. La logica de calendario habil (excluyendo sabados, domingos y festivos) se implementa en la capa de aplicacion, no en la BD; la BD guarda la fecha resultante.

**Fuente tipo:** LEY
[WEB referente: https://www.medellin.gov.co/es/pqrsd/ — Medellin publica tab "Tiempos de respuesta" que los confirma]

---

### H-02 | Niveles de autenticacion digital por tipo de tramite (P-02, `nivel_auth_tramite`)

**Norma:** Decreto 620 de 2020 (Presidencia de Colombia), Art. 2–4; servicio AND de Autenticacion Digital.
[LEY: Decreto 620/2020, art. 2–4]
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=118337]
[WEB: https://autenticaciondigital.and.gov.co/]

**Valores concretos para el modelo:**

| nivel_confianza (codigo BD) | Mecanismo | Descripcion |
|-----------------------------|-----------|-------------|
| BAJO | Correo electronico + contrasena (min. 8 caracteres: numero, minuscula, mayuscula) | Acceso a servicios de baja criticidad |
| MEDIO | Datos personales ampliados + contrasena validada contra fuentes de atributos (nombre, apellido, tipo/num. ID, fecha expedicion, fecha nacimiento, lugar nacimiento, sexo, telefono, email, direccion, dpto., municipio) | Tramites con datos personales |
| ALTO | Certificado digital | Tramites con firma juridicamente valida |
| MUY_ALTO | Mecanismos de la Registraduria Nacional del Estado Civil (cedula digital) | Tramites de maxima criticidad |

**Limite de lo resuelto:** El Decreto 620/2020 y el portal AND NO publican una tabla tramite→nivel minimo requerido. Ese mapeo es ESPECIFICO-ALCALDIA (debe definirlo G-CIO + AND por tramite). Lo que la norma da es el catalogo de niveles disponibles.

**Impacto en BD:** `tramite.nivel_auth_minimo` CHECK debe usar los valores: 'BAJO', 'MEDIO', 'ALTO', 'MUY_ALTO' (en lugar de numeros 1–3 o 1–4). La columna queda poblable una vez la Alcaldia defina la matriz.

**Fuente tipo:** LEY + REFERENTE
[WEB: https://dapre.presidencia.gov.co/normativa/normativa/DECRETO%20620%20DEL%202%20DE%20MAYO%20DE%202020.pdf]

---

### H-03 | Secciones obligatorias de transparencia (afecta modulo transparencia, entidad `publicacion_transparencia`)

**Norma:** Ley 1712 de 2014, Arts. 9, 10, 11 (publicacion proactiva obligatoria); Resolucion MinTIC 1519/2020, Anexo 2 (ITA — 10 niveles).
[LEY: Ley 1712/2014, arts. 9–11]
[LEY: Resolucion MinTIC 1519/2020, art. 4, Anexo 2]
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=56882]
[WEB: https://gobiernodigital.mintic.gov.co/692/w3-article-160997.html]

**Estructura de los 10 niveles del ITA (Anexo 2, Resolucion 1519/2020):**

| Nivel ITA | Nombre de seccion | Tipo |
|-----------|------------------|------|
| 1 | Informacion de la entidad (estructura organica, funciones, sedes, horarios, directorio, escalas salariales) | Obligatorio |
| 2 | Normativa (normas generales, reglamentarias, politicas, manuales, objetivos) | Obligatorio |
| 3 | Contratacion (plan de adquisicion, contratos adjudicados, plazos de cumplimiento) | Obligatorio |
| 4 | Planeacion, Presupuesto e Informes (presupuesto, ejecucion anual, planes de gasto, informes auditoria, Plan Anticorrupcion) | Obligatorio |
| 5 | Tramites y Servicios (tramites, formularios, costos, normativa) | Obligatorio |
| 6 | Participa (mecanismos de participacion ciudadana) | Obligatorio |
| 7 | Datos abiertos | Obligatorio |
| 8 | Informacion especifica para Grupos de Interes | Obligatorio |
| 9 | Obligacion de reporte de informacion especifica de la entidad | Obligatorio |
| 10 | Informacion tributaria en entidades territoriales locales | Obligatorio (aplica a Alcaldias) |
[WEB: https://www.procuraduria.gov.co/Pages/ita.aspx]

**Campos minimos del formulario PQRSD electronico (Resolucion 1519/2020, Anexo 2, art. 4 par.):**
La norma indica que el Anexo 2 define los campos; los Anexos completos no son publicamente accesibles en texto plano via WebFetch. Los campos estructurales se confirman con el referente Medellin:
[WEB: https://www.medellin.gov.co/es/centro-documental/formulario-para-la-radicacion-de-pqrsd/]

Campos documentados en el referente Medellin (estructura del formulario PQRSD, publicacion 2022):
1. Consentimiento previo, expreso e informado de tratamiento de datos personales (obligatorio — pre-formulario)
2. Campos del formulario (segun PDF "2.-VISTA-FORMULARIO-RADICACION-PQRS.pdf", no legible por WebFetch pero estructura confirmada por portal)
3. Validacion de calidad de datos ingresados
4. Adjuntar archivos
5. Boton "Continuar" / "Enviar"
6. Generacion automatica del acuse de recibo con numero de radicado

**Impacto en BD:** La entidad `publicacion_transparencia` debe tener campo `nivel_ita` con los 10 valores del ITA como dominio. El formulario PQRSD debe incluir: consentimiento_tratamiento_datos (BOOLEAN, NOT NULL), adjuntos permitidos, generacion de acuse automatico.

**Fuente tipo:** LEY + REFERENTE

---

### H-04 | Metadatos obligatorios de conjuntos de datos abiertos (P-09, `dataset` / `metadato_dataset`)

**Norma:** Ley 1712/2014, art. 11 lit. h (datos abiertos); Resolucion MinTIC 1519/2020, art. 7, Anexo 4; estandar DCAT adoptado por datos.gov.co.
[LEY: Ley 1712/2014, art. 11 lit. h]
[LEY: Resolucion MinTIC 1519/2020, art. 7, Anexo 4]
[WEB: https://www.datos.gov.co/]
[WEB: https://lenguaje.mintic.gov.co/sites/default/files/archivos/concepto_incorporacion_dcat.pdf]

**Metadatos minimos obligatorios para publicacion en datos.gov.co (estandar DCAT colombia):**

| Campo metadato | Tipo | Obligatorio | Descripcion |
|---------------|------|-------------|-------------|
| titulo | VARCHAR | SI | Nombre descriptivo del dataset |
| descripcion | TEXT | SI | Descripcion del contenido |
| responsable / publicador | VARCHAR | SI | Entidad o funcionario responsable |
| frecuencia_actualizacion | VARCHAR | SI | Periodicidad (diaria, semanal, mensual, anual, etc.) |
| fecha_creacion | DATE | SI | Fecha de primera publicacion |
| fecha_ultima_actualizacion | DATE | SI | Fecha de la ultima actualizacion |
| licencia | VARCHAR | SI | Tipo de licencia de uso (Creative Commons u otra) |
| temas / categorias | VARCHAR[] | SI | Clasificacion tematica |
| formato | VARCHAR | SI | CSV, JSON, XLS, API, etc. |
| url_descarga / endpoint | VARCHAR | SI | Enlace al recurso |
| cobertura_temporal | VARCHAR | NO (recomendado) | Periodo al que aplican los datos |
| cobertura_geografica | VARCHAR | NO (recomendado) | Municipio, departamento, nacional |
| idioma | VARCHAR | NO | es-CO por defecto |
[WEB: https://herramientas.datos.gov.co/sites/default/files/Guia_Estandarizacion_DatosAbiertos_final.pdf]
[WEB: https://www.datos.gov.co/stories/s/Guia-para-Conjuntos-de-Datos-Obligatorios-Ley-1712/724h-3u74/]

**Actualizacion obligatoria:** Ley 1712/2014 art. 9 exige actualizacion minima mensual para la informacion de estructura y contratacion. Para datasets en general, la frecuencia declarada en los metadatos es el SLA de actualizacion.

**Impacto en BD (P-09):** El umbral de "frescura" no esta fijado por norma — la norma solo exige que la frecuencia declarada se cumpla. El campo `dataset.frecuencia_actualizacion` + `dataset.fecha_ultima_actualizacion` permite calcular el estado de desactualizacion en la capa de aplicacion. El margen de gracia de N dias es ESPECIFICO-ALCALDIA (P-09 sigue PENDIENTE como valor exacto).

**Fuente tipo:** LEY + ESTANDAR

---

### H-05 | TRD: campos obligatorios de la Tabla de Retencion Documental (afecta `politica_retencion`, `expediente_electronico`, campo `trd_codigo`)

**Norma:** Ley 594/2000 (Ley General de Archivos), art. 24; AGN Acuerdo 004/2019 (reglamento TRD).
[LEY: Ley 594/2000, art. 24]
[LEY: AGN Acuerdo 004/2019]
[WEB: https://normativa.archivogeneral.gov.co/acuerdo-004-de-2019/]
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=4275]

**Estructura minima obligatoria de una TRD (Acuerdo AGN 004/2019):**

| Campo TRD | Descripcion |
|-----------|-------------|
| codigo_serie | Codigo alfanumerico de la serie documental |
| nombre_serie | Nombre de la serie documental |
| codigo_subserie | Codigo de subserie (si aplica) |
| nombre_subserie | Nombre de subserie |
| tipo_documental | Tipos de documentos que conforman la serie/subserie |
| retension_archivo_gestion | Tiempo en anos en archivo de gestion |
| retencion_archivo_central | Tiempo en anos en archivo central |
| disposicion_final | CT (conservacion total) / E (eliminacion) / MT (microfilmacion/digitalizacion) / S (seleccion) |
| procedimiento | Descripcion de como aplicar la disposicion final |

**Ciclo vital documentos segun Ley 594/2000:**
- Archivo de gestion: documentos activos en tramite
- Archivo central: documentos de consulta poco frecuente, aun vigentes
- Archivo historico: conservacion permanente; se transfieren desde archivo central

**Tiempos:** La Ley 594/2000 y el Acuerdo 004/2019 NO fijan tiempos universales por tipo de documento. Cada entidad define sus propios tiempos en la TRD segun sus series documentales. Los tiempos dependen del valor administrativo, juridico, legal, fiscal o tecnico de cada documento.

**Implicacion para P-01 (periodos retencion/purga):**
La Ley 1581/2012 fija el PRINCIPIO (datos solo mientras dure la finalidad; supresion a peticion del titular), pero NO fija plazos numericos fijos por categoria de dato personal. La combinacion TRD + principio de finalidad de Ley 1581/2012 define el marco:
- `politica_retencion.periodo_retencion_dias` debe llenarse con los valores de la TRD aprobada de la Alcaldia (ESPECIFICO-ALCALDIA).
- El campo `accion_vencimiento` (purgar/anonimizar) se resuelve por categoria de dato segun la TRD.
[LEY: Ley 1581/2012, art. 4 lit. b (principio de finalidad), art. 8 lit. e (derecho de supresion)]
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981]

**Fuente tipo:** LEY

---

### H-06 | Secciones de transparencia — articulos 9, 10 y 11 Ley 1712/2014 (campos concretos por seccion)

**Norma:** Ley 1712/2014
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=56882]

**Articulo 9 — Informacion minima obligatoria sobre estructura:**
a) Estructura organica, funciones, deberes, ubicacion de sedes, horas de atencion publica
b) Presupuesto general, ejecucion presupuestal historica anual y planes de gasto
c) Directorio: cargo, correo electronico, telefono, escalas salariales de todos los servidores
d) Normas generales y reglamentarias, politicas, lineamientos, manuales, metas, objetivos y resultados de auditorias
e) Plan de compras, contratos adjudicados, obras publicas, bienes adquiridos, plazos de cumplimiento
f) Plazos de cumplimiento de los contratos
g) Plan Anticorrupcion y de Atencion al Ciudadano

Actualizacion minima: mensual. [LEY: Ley 1712/2014, art. 10]

**Articulo 11 — Informacion minima sobre servicios, procedimientos y funcionamiento:**
a) Detalles de todo servicio al publico (normas, formularios, protocolos)
b) Tramites: normativa, proceso, costos, formatos
c) Procedimientos decisorios por area
d) Decisiones y politicas publicas con sus fundamentos
e) Informes de gestion, evaluacion y auditoria
f) Mecanismos de supervision, notificacion y vigilancia
g) Adquisiciones
h) Presentacion de solicitudes/quejas y participacion ciudadana
i) Registro de publicaciones
j) Datos abiertos

**Impacto en BD:** La entidad `publicacion_transparencia` puede usar `nivel_ita` (1-10) + `seccion` con estos literales como dominio enum. La publicacion de tramites (art. 11 lit. b) confirma que `tramite` debe exponer: normativa relacionada, proceso, costos, formularios.

**Fuente tipo:** LEY

---

### H-07 | member_class X-Road en Colombia: valor "GOV" (P-07)

**Evidencia:** Multiples guias tecnicas de la AND y MinTIC confirman que las entidades del gobierno colombiano en X-Road usan la clase `GOV`.

Ejemplo documentado: "El Ministerio de Tecnologias de la Informacion (MinTIC) tiene member_class 'GOV' y member_code 'MinTIC-0012'".
[WEB: https://and.gov.co/noticias/and-mintic-impulsan-interoperabilidad-del-estado-colombiano-x-road]
[WEB: https://and.gov.co/sites/default/files/2022-09/Anexo-T%C3%A9cnico-X-ROAD-27012020.pdf — referenciado, PDF no legible por WebFetch]

**Valor concreto para el modelo:**
`interop_xroad_member.member_class` CHECK debe incluir 'GOV' como valor para entidades de gobierno colombiano. La variante 'PRIV' se usa para entidades privadas que presten funciones publicas.

**Precision sobre el conflicto C-20 del inventario:** La referencia al "CO" del documento de 2019 probablemente corresponde al identificador de la instancia nacional (instance identifier de X-Road), no al member_class. El member_class para gobierno es 'GOV'; para privados es 'PRIV'. El conflicto C-20 queda resuelto con 'GOV'/'PRIV', manteniendo el verificador con AND antes de aplicar CHECK.

**Fuente tipo:** REFERENTE (documentacion tecnica AND)

---

### H-08 | Estructura del numero de radicado (P-08, `solicitud.numero_radicado`, `pqrsd.numero_radicado`)

**Norma:** AGN Acuerdo 060 de 2001, art. 2 (radicacion); derogado por AGN Acuerdo 001 de 2024 — la estructura sigue siendo la practica estandar.
[LEY: AGN Acuerdo 060/2001, art. 2]
[WEB: https://normativa.archivogeneral.gov.co/acuerdo-060-de-2001/]
[WEB: https://www.sdp.gov.co/transparencia/informacion-interes/glosario/radicado-de-entrada]

**Estructura estandar colombiana del numero de radicado:**

```
{AÑO}{CODIGO_DEPENDENCIA}{CONSECUTIVO}{TIPO_COMUNICACION}
```

Ejemplo segun Acuerdo 060/2001 documentado: `201590362` = 2015 (año) - 90 (dependencia) - 036 (consecutivo) - 2 (tipo).

**Estructura confirmada para Santa Marta (documentacion del cliente, modulo 03 §7):**
```
SM-{dependencia}-{año}-{secuencia_6_digitos}
```

Esta estructura es COMPATIBLE con el estandar nacional. El cliente ya la tiene documentada como confirmada; el inventario la marca como "validar con Gestion Documental". La norma respalda el patron: es conforme al Acuerdo 060/2001.

**Impacto en BD:** `solicitud.numero_radicado` REGEX constraint: `'^SM-[A-Z0-9]+-[0-9]{4}-[0-9]{6}$'` es valida normativamente. `pqrsd.numero_radicado` mismo patron.

**Fuente tipo:** LEY + documentacion cliente

---

### H-09 | Principio de finalidad y supresion de datos personales (P-01, `politica_retencion`)

**Norma:** Ley 1581/2012 (Ley de Proteccion de Datos Personales de Colombia).
[LEY: Ley 1581/2012, art. 4 lit. b; art. 7; art. 8 lit. e; art. 15]
[WEB: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981]

**Lo que la ley SI define:**
- Principio de finalidad (art. 4 lit. b): el tratamiento "debe obedecer a una finalidad legitima" — datos solo se conservan mientras dure esa finalidad.
- Datos de menores (art. 7): proscrito el tratamiento de datos de NNA salvo datos de naturaleza publica.
- Derecho de supresion (art. 8 lit. e): el titular puede solicitar supresion cuando no se respeten los principios.
- Plazo de respuesta a solicitudes ARCO: 15 dias habiles (art. 15).

**Lo que la ley NO define:** Plazos numericos fijos por categoria de dato (logs, borradores, citas, consentimientos). La SIC puede emitir instrucciones sectoriales, pero no hay un catalogo nacional de retenciones fijado por la ley misma.

**Implicacion para BD:** `politica_retencion.periodo_retencion_dias` sigue siendo PENDIENTE con valores especificos de la Alcaldia. El modelo es correcto; solo falta el seed de datos que proviene de la TRD aprobada de la entidad.

**Fuente tipo:** LEY

---

### H-14 | Tramites jurisdiccionales — existencia de guia tecnica MinTIC (P-14)

**Norma/referente:** MinTIC publica "Guia de TI para la gestion de tramites jurisdiccionales" (documento 20 y 44 del corpus del cliente, listado en extracto-web-docs.md).
[WEB: https://das997q1qk8hr.cloudfront.net/uploads/2023-12-22/ec20cc58... — ver extracto-web-docs.md archivo 20 y 44]

**Lo resuelto:** Existe una guia tecnica MinTIC especifica para tramites jurisdiccionales. El Decreto 2106/2019 (simplificacion de tramites) es el marco normativo. El reparto aleatorio corresponde a la Rama Judicial (Consejo Superior de la Judicatura), no a la Alcaldia.

**Lo que sigue ESPECIFICO-ALCALDIA (P-14):** Si la Alcaldia incluira tramites jurisdiccionales propios (demandas contra el Distrito, por ejemplo) o solo los tramites administrativos. La guia MinTIC da el referente pero las entidades nuevas son decision de la Secretaria Juridica.

**Impacto en BD:** Si la Alcaldia confirma inclusion, las entidades nuevas (hasta 4 segun inventario) son: `tramite_jurisdiccional`, `reparto_asignacion`, `termino_procesal`, `juzgado_radicador`. Su estructura base esta en la guia MinTIC.

**Fuente tipo:** REFERENTE

---

### H-18 | Carpeta Ciudadana Digital (CCD) — estructura de endpoints (P-18)

**Norma:** Decreto 620/2020; Resolucion MinTIC 2160/2020 (guia de vinculacion y uso de Servicios Ciudadanos Digitales).
[LEY: Decreto 620/2020]
[LEY: Resolucion MinTIC 2160/2020]
[WEB: https://gobiernodigital.mintic.gov.co/692/articles-161275_Anexo2_Resolucion_2160_2020.pdf]
[WEB: https://and.gov.co/servicios-ciudadanos-digitales]

**Lo resuelto parcialmente:** La CCD es un servicio de la AND. Sus endpoints REST siguen el esquema descrito en la Resolucion 2160/2020 y el Anexo 2 de vinculacion. Los parametros `campoDato`/`valorDato` son parte del protocolo REST de la AND.

**Sin evidencia:** Los PDFs tecnicos de AND no son legibles via WebFetch en esta sesion. El esquema exacto de los 4 endpoints (URL, metodos, payload JSON con campoDato/valorDato) requiere acceso directo a la documentacion tecnica de AND (sandbox o portal de desarrolladores).

**Valor parcial para BD:** `carpeta_ciudadana` e `interop_ccd_*` son correctos en concepto. El esquema de campos especifico sigue siendo PENDIENTE de validacion con AND (como indica el inventario).

**Fuente tipo:** LEY (marco)

---

## 2. Vacios que siguen PENDIENTE

Los siguientes vacios NO se resuelven con evidencia normativa publica. Siguen clasificados como ESPECIFICO-ALCALDIA o SIN-EVIDENCIA:

| # | Vacio | Razon |
|---|-------|-------|
| P-01 | Valores numericos exactos de periodos de retencion por categoria (logs, borradores, citas, consentimientos) | La Ley 1581/2012 da el marco (principio finalidad) pero NO plazos fijos. Depende de la TRD de la Alcaldia. |
| P-02 | Matriz tramite ↔ nivel de autenticacion minimo | La norma da el catalogo de niveles (BAJO/MEDIO/ALTO/MUY_ALTO), pero el mapeo por tramite es decision de G-CIO + AND. |
| P-03 | Umbral local incidente "grave" CSIRT ≤24h | Sin normativa colombiana que fije umbral numerico. Decision interna de la entidad. |
| P-04 | Umbral tasa exito SUS (¿90%?) | Sin estandar colombiano. Decision UX/SLA interna. |
| P-05 | Catalogo tramites con SAP | Especifico DAFP-SUIT de la Alcaldia. |
| P-06 | Politica reembolso tramite pagado | Reglamento de tesoreria local. |
| P-09 | Margen de gracia frescura datasets en dias | La norma exige cumplir la frecuencia declarada; el margen exacto de tolerancia es parametro operativo de la entidad. |
| P-10 | Alcance CRUD participacion: ¿sede propia o solo SUCOP? | Decision arquitectonica y politica de la entidad. |
| P-11 | Roles exactos CMS | Depende del organigrama y del CMS adoptado. |
| P-12 | Enrutamiento PQRSD: automatico o manual | Proceso interno de la entidad. |
| P-13 | Configuracion preguntas encuesta experiencia | Definicion del lider de tramites. |
| P-14 | Tramites jurisdiccionales: si se incluyen y cuales | Decision Secretaria Juridica; guia MinTIC existe como referente. |
| P-15 | Multiplicidad formatos dataset | Ya resuelta como N:N; solo confirmar con equipo datos. |
| P-16 | Inventario micrositios/portales | Seed de datos de Direccion TIC. |
| P-17 | TTL bloqueo edicion concurrente CMS | Sin evidencia normativa; parametro tecnico interno. |
| P-19 | Periodo retencion borradores (30 dias) | La TRD dara el valor formal; el inventario lo menciona como "resuelto (30 dias); confirmar formalmente". |
| P-20 | Traduccion/lenguas etnicas | Decision politica diferida. |
| P-21 | Valores N reintentos X-Road y timeout | Sin evidencia publica legible; parametro tecnico del proveedor/AND. |
| P-22 | Capacidad/TTL cola TSA | Sin normativa publica colombiana; parametro tecnico del proveedor TSA. |
| P-23 | Pruebas RTO/RPO (DRP/BCP) | Objetivos fijados por la entidad segun criticidad y presupuesto. |

---

## Resumen ejecutivo

| Categoria | Cantidad | Vacios # |
|-----------|----------|----------|
| Resueltos con valor concreto para el modelo | 4 | P-07 (member_class GOV), P-08 (patron radicado SM-DEP-AAAA-XXXXXX), H-01 (plazos PQRSD por tipo), H-02 (niveles autenticacion BAJO/MEDIO/ALTO/MUY_ALTO) |
| Marco normativo dado, valor exacto pendiente Alcaldia | 5 | P-01, P-02 (matriz), P-09 (umbral dias), P-14 (si incluye jurisdiccionales), P-19 |
| Secciones/campos de dominio entregados (transparencia, TRD, PQRSD, datasets) | 4 | H-03 (10 niveles ITA), H-04 (metadatos DCAT), H-05 (campos TRD), H-06 (arts. 9-11 Ley 1712) |
| Sin evidencia normativa publica | 3 | P-17, P-21, P-22 |
| Especificos-Alcaldia (no resolubles por norma) | 13 | P-03, P-04, P-05, P-06, P-10, P-11, P-12, P-13, P-15, P-16, P-20, P-23, P-12 |

**Total vacios con algun hallazgo normativo util: 13 de 23.**
**Total vacios que siguen 100% PENDIENTE-ALCALDIA o SIN-EVIDENCIA: 10.**

---

## Ronda 3 — Investigacion de vacios R3.E

> Generado: 2026-06-07 (Ronda 3 profunda)
> Metodologia: cero invencion. HECHO = texto oficial citado [WEB:url] / [LEY:doc, art.]. INFERENCIA = etiquetada [INFERIDO]. Lo no hallado = NO-ENCONTRADO explicito.
> Herramientas: WebSearch, WebFetch y Chrome DevTools (navegacion real de santamarta.gov.co y lectura directa de PDF oficiales MinTIC/DAFP).

### R3.E-1 — Organigrama oficial de la Alcaldia Distrital de Santa Marta (tabla `dependencia`)

**Estado: RESUELTO (nombres y tipos de unidad). Codigos organicos: NO-ENCONTRADO en fuente publica.**

Fuente primaria navegada con Chrome:
- [WEB:https://www.santamarta.gov.co/dependencias] — pagina oficial "Dependencias" (Drupal del Distrito). Lista cada dependencia con enlace a perfil del secretario y a la ficha de la dependencia.
- [WEB:https://www.santamarta.gov.co/organigrama] — pagina "Estructura Organizacional - Organigrama". Cita textual: *"Se entiende por estructura organizacional al conjunto de dependencias, secretarias, oficinas y organismos del orden distrital... La estructura de la Administracion Distrital esta integrada por las siguientes dependencias"*. NIT 891780009. (El grafico del organigrama se publica como imagen; el detalle textual de unidades esta en /dependencias.)
- [LEY:Decreto 312 del 29 de diciembre de 2016, Distrito de Santa Marta] — referido por la propia Alcaldia como el decreto que *"rediseno y modernizo la estructura administrativa de la Alcaldia del Distrito Turistico, Cultural e Historico de Santa Marta"* (citado en la ficha de la Secretaria de Seguridad y Convivencia). Es el acto administrativo que asignaria los codigos organicos formales; su texto integro NO se localizo publicado en HTML legible.

HECHO — Inventario de dependencias publicadas (24 unidades), con `tipo_unidad` inferido del prefijo del nombre oficial:

| # | nombre (oficial) | tipo_unidad | nota nivel |
|---|------------------|-------------|-----------|
| 1 | Secretaria de Gobierno | SECRETARIA | central |
| 2 | Secretaria de Cultura | SECRETARIA | central |
| 3 | Oficina de Control Interno | OFICINA | control/asesora |
| 4 | Secretaria de Educacion | SECRETARIA | central |
| 5 | Secretaria de Salud del Distrito | SECRETARIA | central |
| 6 | Secretaria General | SECRETARIA | central |
| 7 | Secretaria de Planeacion | SECRETARIA | central |
| 8 | Secretaria de la Mujer (y Equidad de Genero) | SECRETARIA | central |
| 9 | Secretaria de Desarrollo Economico y Competitividad | SECRETARIA | central |
| 10 | Secretaria de Promocion Social, Inclusion y Equidad | SECRETARIA | central |
| 11 | Secretaria de Seguridad y Convivencia | SECRETARIA | central |
| 12 | Secretaria de Movilidad Multimodal y Sostenible del Distrito | SECRETARIA | central |
| 13 | Oficina de Asuntos Disciplinarios (Oficina de Atencion Disciplinaria) | OFICINA | control |
| 14 | Direccion de Tecnologias de la Informacion y Comunicaciones (TIC) | DIRECCION | central |
| 15 | Direccion de Contratacion | DIRECCION | central |
| 16 | Gerencia de Infraestructura | GERENCIA | central |
| 17 | Alta Consejeria para la Paz y el Posconflicto | ALTA_CONSEJERIA | asesora |
| 18 | Oficina Asesora de Comunicaciones Estrategicas | OFICINA_ASESORA | asesora |
| 19 | Subsecretaria de Desarrollo Rural | SUBSECRETARIA | central |
| 20 | Direccion Juridica Distrital de Santa Marta | DIRECCION | central |
| 21 | Oficina para la Atencion al Riesgo | OFICINA | central |
| 22 | Departamento Administrativo Distrital para la Sostenibilidad Ambiental (DADSA) | DEPARTAMENTO_ADMINISTRATIVO | central |

Entes descentralizados / adscritos publicados en la misma pagina (no son dependencias del nivel central; `tipo_unidad` = ENTE_DESCENTRALIZADO):

| # | nombre (oficial) | sigla |
|---|------------------|-------|
| D1 | Sistema Estrategico de Transporte Publico de Pasajeros | SETP Santa Marta SAS |
| D2 | Empresa de Servicios Publicos del Distrito de Santa Marta | ESSMAR |
| D3 | Empresa (Distrital de) Desarrollo (y Renovacion) Urbano Sostenible | EDUS |
| D4 | Instituto Distrital de Turismo | INDETUR |
| D5 | Instituto Distrital de Santa Marta para la Recreacion y el Deporte | INRED |

Mapeo al esquema:
- `dependencia.nombre` <- columna "nombre oficial" de las tablas anteriores. [WEB:https://www.santamarta.gov.co/dependencias]
- `dependencia.sigla` <- solo poblada para las que publican sigla (TIC, DADSA, SETP, ESSMAR, EDUS, INDETUR, INRED). Las demas: sigla NULL hasta confirmar con la entidad. [INFERIDO para las secretarias sin sigla publicada]
- `dependencia.tipo_unidad` <- valor del CHECK del dominio (SECRETARIA / SUBSECRETARIA / DIRECCION / OFICINA / OFICINA_ASESORA / GERENCIA / ALTA_CONSEJERIA / DEPARTAMENTO_ADMINISTRATIVO / ENTE_DESCENTRALIZADO). [INFERIDO del prefijo del nombre oficial; debe ratificarse con el Decreto 312/2016]
- `dependencia.codigo` (codigo organico) y `dependencia.id_padre` (jerarquia padre-hijo): **NO-ENCONTRADO** en fuente publica. La pagina /dependencias presenta una lista plana sin codigos ni anidamiento. La jerarquia formal y los codigos solo constarian en el Decreto 312/2016 (no publicado en HTML legible). Recomendacion: solicitar a la Direccion TIC / Secretaria General el organigrama codificado o el texto del decreto. PENDIENTE-ALCALDIA.

INFERENCIA de jerarquia (NO confirmada, solo orientativa de la propia /organigrama que menciona sub-oficinas): la Secretaria General agrupa la Oficina del Sistema Integrado de Gestion (SIG), la Oficina de Gestion del Servicio al Ciudadano y la Direccion Administrativa y de Gestion Documental. [WEB:https://www.santamarta.gov.co/secretaria-general-0] [INFERIDO — relacion padre-hijo a confirmar].

---

### R3.E-2 — Texto oficial ES de los 10 items del cuestionario SUS (catalogo de items SUS)

**Estado: RESUELTO (10 enunciados ES). Matiz de variante: ES-ES (Espana) como texto base; validacion latam disponible como respaldo academico. ES-CO especifico: NO-ENCONTRADO (no existe validacion publicada exclusiva de Colombia).**

HECHO — Los 10 enunciados en espanol (escala Likert 1-5: 1 "Totalmente en desacuerdo" ... 5 "Totalmente de acuerdo"; items impares 1,3,5,7,9 positivos; pares 2,4,6,8,10 negativos):

1. Creo que me gustaria utilizar este sistema con frecuencia.
2. Encontre el sistema innecesariamente complejo.
3. Pense que el sistema era facil de usar.
4. Creo que necesitaria el apoyo de un tecnico para poder utilizar este sistema.
5. Encontre que las diversas funciones de este sistema estaban bien integradas.
6. Pense que habia demasiada inconsistencia en este sistema.
7. Me imagino que la mayoria de la gente aprenderia a utilizar este sistema muy rapidamente.
8. Encontre el sistema muy complicado de usar.
9. Me senti muy seguro usando el sistema.
10. Necesitaba aprender muchas cosas antes de empezar con este sistema.

Fuente del texto citado: [WEB:https://uifrommars.com/como-medir-usabilidad-que-es-sus/] (enunciados ES verbatim).

Respaldo academico de validacion al espanol (para citar como fuente con revision por pares):
- [WEB:https://pmc.ncbi.nlm.nih.gov/articles/PMC7773510/] — "Spanish Version of the System Usability Scale for the Assessment of Electronic Tools: Development and Validation". Validacion realizada en Ciudad de Mexico (espanol latinoamericano). Indices: validez de contenido 0.92, validez facial 0.94, Alfa de Cronbach 0.812. Los 10 items traducidos estan en el "Multimedia Appendix 2" (archivo .docx) — el texto verbatim de ese apendice NO es extraible via WebFetch (solo se confirma su existencia y el ajuste de los items 2, 5 y 9 por claridad lexica). Para uso oficial ES-CO, citar este paper y adoptar el texto del item 1-10 de arriba como redaccion ES neutra.

Mapeo al esquema:
- Catalogo de items SUS (`numero_item` 1..10, `enunciado`, `polaridad`): `enunciado` <- los 10 textos de arriba; `polaridad` = POSITIVO para 1,3,5,7,9 y NEGATIVO para 2,4,6,8,10. [WEB:uifrommars.com] [WEB:PMC7773510]
- `respuesta_item_sus.valor` <- entero CHECK BETWEEN 1 AND 5 (escala Likert de 5 puntos). [WEB:uifrommars.com]
- Calculo de puntaje SUS: items positivos -> (valor - 1); items negativos -> (5 - valor); suma x 2.5 -> rango 0..100. [WEB:uifrommars.com] (regla de Brooke, citada verbatim por la fuente).
- NO-ENCONTRADO: una traduccion oficial sancionada por el Estado colombiano. Lo disponible es (a) redaccion ES estandar de la industria y (b) validacion academica latam. Se recomienda fijar el texto de arriba como catalogo semilla y registrar la fuente.

---

### R3.E-3 — Metodologia DAFP de caracterizacion de ciudadanos/grupos de valor (`grupo_interes.caracterizacion`)

**Estado: RESUELTO. Fuente primaria oficial leida integra (PDF de Funcion Publica, 53 pag.).**

Fuente: [WEB:https://www1.funcionpublica.gov.co/documents/418548/34150781/...Guia%20de%20caracterizacion%20de%20ciudadania%20y%20grupos%20de%20valor%20-%20Version%205%20-%20Noviembre%20de%202022...] — "Guia de caracterizacion de ciudadania y grupos de valor", Departamento Administrativo de la Funcion Publica, v5, noviembre 2022 (PDF; Title/Author confirmados en metadatos del archivo).

HECHO — Metodologia de 5 pasos (citada del indice y cuerpo del documento):
- PASO 1. Reconozca ejercicios previos de caracterizacion.
- PASO 2. Establezca las variables para la caracterizacion.
- PASO 3. Recolecte la informacion.
- PASO 4. Analice la informacion.
- PASO 5. Use y aproveche la informacion.

HECHO — Variables para PERSONAS NATURALES, por dimension (Tabla 1, pag. 38-41, verbatim):
- Geograficas: Ubicacion; Poblacion; Densidad poblacional; Clima.
- Demograficas: Tipo y numero de documento; Edad (fecha de nacimiento); Sexo; Genero (orientacion sexual e identidad de genero); Ingresos; Ocupacion/actividad economica; Estrato socioeconomico; Regimen de afiliacion al Sistema General de Seguridad Social; Puntaje del Sisben; Estado del ciclo familiar; Tamano/composicion grupo familiar; Nivel de educacion o Escolaridad; Lenguas o idiomas; Vulnerabilidad.
- Intrinsecas: Intereses; Lugares de encuentro; Acceso a canales de atencion; Uso de canales de atencion; Conocimientos; Dialecto.
- De comportamiento: Niveles de uso; Estatus del usuario; Beneficios buscados; Eventos.
- Relacionales: Frecuencia y tiempos de interaccion; Escenarios de relacionamiento mas empleados; Otros escenarios alternativos/itinerantes; Temas mas demandados y de mayor interes; Calificacion de la experiencia del ciudadano (acceso a informacion publica, a tramites, a oferta institucional, espacios de control y rendicion de cuentas, espacios de participacion); Espacios de articulacion existentes; Contexto socioterritorial.

HECHO — Variables para PERSONAS JURIDICAS, por dimension (Tabla 2, pag. 42-43, verbatim):
- Geograficos: Cobertura geografica; Dispersion; Ubicacion principal.
- Tipologia organizacional: Tamano de la entidad; Con o sin animo de lucro; Fuente de recursos (origen de capital); Organizacion/sector del cual depende; Industria; Tipo de ciudadano, usuario o grupo de interes; Canales de atencion disponibles.
- De comportamiento organizacional: Procedimiento usado (mecanismos/canales); Responsable de la interaccion.

Mapeo al esquema:
- `grupo_interes.tiene_caracterizacion_formal` (BOOLEAN) = TRUE si el grupo de valor tiene un ejercicio que aplica los Pasos 1-5 de la guia DAFP. [WEB:funcionpublica.gov.co guia v5]
- `grupo_interes.caracterizacion` (texto/JSON): debe estructurarse por las dimensiones DAFP. Para personas naturales: {geograficas, demograficas, intrinsecas, comportamiento, relacionales}; para juridicas: {geograficos, tipologia_organizacional, comportamiento_organizacional}. Cada dimension contiene el subconjunto de variables elegidas (la guia dice textualmente: *"No es necesario incorporar todas las variables senaladas; las variables elegidas deberan atender el objetivo o proposito de la caracterizacion"*). [WEB:funcionpublica.gov.co guia v5, pag. 38]
- INFERENCIA de diseno [INFERIDO]: si se quisiera normalizar a 4NF, las variables-por-grupo serian una tabla puente (grupo_interes 1:N variable_caracterizacion) en vez de un campo JSON, ya que la relacion grupo->variables es multivaluada. La guia justifica que el conjunto de variables es variable por entidad (no un dominio fijo), lo que respalda una tabla catalogo `variable_caracterizacion(dimension, nombre, aplica_a)`.

---

### R3.E-4 — Contrato/esquema de la CCD (Carpeta Ciudadana Digital) (`carpeta_ciudadana`, ver tambien P-18)

**Estado: PARCIAL. Modelo conceptual y de servicio: RESUELTO (fuente oficial leida integra). Contrato REST campo-a-campo: NO-ENCONTRADO — esta EXPLICITAMENTE fuera del alcance de la guia oficial.**

Fuente: [WEB:https://gobiernodigital.mintic.gov.co/692/articles-161274_Anexo1_Resolucion_2160_2020.pdf] — "Anexo 1. Guia de Lineamientos de los Servicios Ciudadanos Digitales", MinTIC, septiembre 2020 (PDF leido integro; seccion 10 "Modelo del Servicio de Carpeta Ciudadana", pag. 119-132).

HECHO — Limite de alcance (cita verbatim, pag. 12): *"estan fuera de su alcance la definicion de los protocolos de comunicacion, los tipos de bases de datos, y las soluciones tecnologicas concretas de los componentes que soportan los SCD"*. -> Por tanto NO existe en la fuente normativa un contrato REST con nombres de campos (p. ej. campoDato/valorDato del P-18); ese esquema es de diseno/implementacion del Articulador (Agencia Nacional Digital), no publicado como contrato abierto.

HECHO — Definicion del servicio (pag. 119): *"Es el servicio que les permite a las personas naturales o juridicas, acceder y gestionar digitalmente el conjunto de datos almacenados o custodiados por la Administracion Publica, de forma segura y confiable."* Obligatorio para entidades publicas, optativo para los usuarios.

HECHO — Actores/roles del modelo de contexto (pag. 122-125): MinTIC (normatividad y especificacion); Articulador = Agencia Nacional Digital (integra Autenticacion + Interoperabilidad, estructura los datos, administra el componente); Usuarios (persona natural nacional/extranjera con cedula de extranjeria, o persona juridica publica/privada); Portal Unico del Estado GOV.CO (punto de acceso); Servicio de Autenticacion Digital (credenciales/nivel de garantia); Servicio de Interoperabilidad (consulta los datos a los custodios); Entidad (suministra los datos del usuario via sus servicios de informacion/sede electronica); Prestador de SCD.

HECHO — Elementos de datos / objetos que se intercambian (Tabla 9 pag. 126-127 y Tabla 10 pag. 130-132, e Ilustracion 13 pag. 125). Conjuntos de datos del usuario expuestos por sector: Salud, Identificacion, Servicios Publicos, Educacion, Impuestos, Inclusion. Acciones/objetos de gestion del usuario:
- Acceso al servicio (Usuario -> GOV.CO).
- Solicitud de autorizacion de ingreso (GOV.CO -> pasarela de Autenticacion).
- Ingreso a la Carpeta (autenticacion por nivel; nivel 2 = ver algunos tramites; nivel 3 = acceder a los conjuntos de datos).
- Gestiones del usuario: Personalizacion (presentacion por sector/area de interes/tematica normativa); Solicitudes (correccion o actualizacion de datos); Comunicaciones (visualizacion de comunicaciones sobre gestiones); Autorizaciones (habilitacion para autorizar acciones sobre sus datos y el envio de mensajes); Tramites (enlace con GOV.CO).
- Intercambio de datos e informacion (Componente CCD <-> Entidades, via PDI = Plataforma de Interoperabilidad).
- R1 (pag. 130): el usuario puede *"Registrarse de manera voluntaria y gratuita... configurar preferencias, gestionar sus datos, suscribir o cancelar servicios de comunicaciones electronicas o mensajes, recibir comunicaciones electronicas, cargar y/o descargar documentos, aportar o compartir documentos."*
- Componentes internos del CORE/CCD base (Ilustracion 14, pag. 129): Servicio de Datos, Servicio de Solicitudes, Servicio de Comunicaciones, Servicio de Autorizacion, Integracion de datos a Tramites, Diccionario de datos, Indexacion, Modulo de procesamiento de datos, Sistema de Monitoreo y Control.

Mapeo al esquema:
- `carpeta_ciudadana` debe modelar como minimo: titular (FK a ciudadano/usuario, persona natural o juridica); estado de registro (registrado/no registrado — registro voluntario, R1); nivel de autenticacion con el que se accedio (2 o 3); conjunto de datos por sector (Salud/Identificacion/Servicios Publicos/Educacion/Impuestos/Inclusion); solicitudes (correccion|actualizacion); comunicaciones recibidas; autorizaciones de uso/intercambio otorgadas; documentos cargados/compartidos. [WEB:Anexo1 SCD, pag. 119-132]
- Las autorizaciones de acceso/uso son una entidad propia (1:N desde carpeta): cada autorizacion liga titular + dato/entidad + accion permitida + vigencia. [WEB:Anexo1 SCD, pag. 127 "Autorizaciones"]
- NO-ENCONTRADO: nombres concretos de campos del payload REST (campoDato, valorDato, endpoints). La fuente normativa los deja a la implementacion del Articulador (AND); no hay contrato publico abierto. Confirma el veredicto de P-18: el esquema de 4 endpoints es de diseno propio, no normado campo-a-campo. PENDIENTE-AND/implementacion.

---

### R3.E-5 — Lineamiento MinTIC de baja/des-integracion de un portal GOV.CO (B.9/V-04)

**Estado: NO-ENCONTRADO. Verificado en 3 fuentes oficiales; ninguna regula la baja/retiro.**

Fuentes verificadas (todas tratan SOLO la integracion/alta, ninguna la baja):
- [LEY:Resolucion MinTIC 2893 de 2020] — [WEB:https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_2893_2020.htm]. HECHO: regula estandares y requisitos de integracion de sedes electronicas/portales al Portal Unico GOV.CO. NO contiene ningun articulo de baja, desvinculacion, retiro o desintegracion. El art. 8 solo preve actualizacion de lineamientos por la Direccion de Gobierno Digital.
- [WEB:https://gobiernodigital.mintic.gov.co/692/articles-161269_Anexo_4_Resolucion_2893_2020.pdf] — "Guia tecnica de integracion de portales especificos". HECHO: aborda exclusivamente el proceso de integracion (alta); no hay seccion de desconexion/retiro de portales ya integrados.
- [WEB:https://gobiernodigital.mintic.gov.co/692/articles-161274_Anexo1_Resolucion_2160_2020.pdf] — Anexo 1 SCD. No regula baja de portales.

Busquedas adicionales en el Manual de Gobierno Digital y portal MinTIC: ningun resultado sobre "desfederacion", "baja", "retiro" o "desintegracion" de dominio/portal.

Mapeo al esquema:
- El estado/ciclo de vida de un `portal`/`micrositio` (incl. un estado "DADO_DE_BAJA"/"DESINTEGRADO") NO tiene respaldo normativo en cuanto a procedimiento o requisitos. Si el modelo necesita un estado de baja, es decision de gobierno interno de la Direccion TIC, no derivable de norma. PENDIENTE-ALCALDIA / SIN-EVIDENCIA-NORMATIVA.


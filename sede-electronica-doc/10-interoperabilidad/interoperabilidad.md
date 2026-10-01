# Módulo 10 — Interoperabilidad (X-Road / SCD)

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** Plataforma de Interoperabilidad (PDI / X-Road) del Articulador (AND), servidor de seguridad en tres ambientes, servicios REST/SOAP, estampado cronológico (TSA-GSE), Carpeta Ciudadana Digital (servicios de exposición), principio de "no exigir documentos que el Estado ya tiene" e integraciones con SUIT, SIGEP, SECOP y SGDEA.
> **Cruces:** autenticación OIDC → módulo **09 Seguridad**; expediente/gestión documental → módulo **12 Gestión de Contenidos**.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

La interoperabilidad permite que la Alcaldía consuma y exponga datos a otras entidades sin pedir documentos al ciudadano. Se implementa sobre **X-Road** (PDI administrada por la AND), con servidor de seguridad propio en QA / preproducción / producción (alta disponibilidad), certificación **nivel 3 del LCI** antes de producción, y estampado cronológico (TSA) provisto por **GSE (TSU 01)** —que reemplazó a Certicámara—. La Carpeta Ciudadana se nutre de servicios REST expuestos vía X-Road.

## 2. Requisitos Funcionales (RF)

### 2.1 Plataforma X-Road / servidor de seguridad

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-028 / RF-B2-016 / RF-B3-110 | Integrar a la **PDI/X-Road**: desplegar **servidor de seguridad** en 3 ambientes — QA (1 CPU/4 GB), Preproducción (2 CPU/6 GB), Producción (4 CPU/16 GB/20 GB, alta disponibilidad round-robin); anclarlo al servidor central de la AND; habilitar puertos TCP requeridos. | #156,#165,#251,#124,#126,#119,#203 | Must | En producción, al intercambiar con otra entidad el mensaje viaja cifrado y se registra con estampa cronológica. |
| RF-B2-017 / RF-B3-111 | Crear **subsistemas** que representen los sistemas de información; registrar servicios (WSDL para SOAP, OpenAPI 3+/URL para REST); gestionar permisos por servicio/cliente; encabezados `client` y `service`. | #124,#126,#119,#203 | Must | Cada sistema tiene subsistema, servicios publicados y permisos por cliente. |
| RF-B2-018 / RF-B3-139 | Intercambio sobre **HTTPS/TLS 1.2 + RSA-SHA512** con **estampa cronológica** (TSA de GSE, `tsa.gse.com.co`, RFC 3161). | #124,#126,#140 | Must | Mensaje saliente cifrado con RSA-SHA512, con estampa GSE y log de la transacción. |
| RF-B2-088 / RF-B3-139 | Configurar el **servicio TSA de GSE (TSU 01)** en el servidor X-Road, eliminando Certicámara; abrir regla de salida de firewall hacia `tsa.gse.com.co`. | #140 | Must | En Diagnostics, el servicio TSA de GSE aparece activo y genera estampas correctamente. |
| RF-B2-019 | Certificar servicios en **nivel 3 del LCI** antes de producción; suscribir Acuerdo de Entendimiento/Vinculación con la AND. | #124,#126 | Must | En preproducción, demuestra certificación nivel 3 LCI y acuerdo firmado para pasar a producción. |

### 2.2 Carpeta Ciudadana Digital (servicios de exposición)

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B2-020 / RF-B3-116 | Exponer **4 servicios REST GET (application/json)** para la CCD: (a) información del usuario, (b) alertas y comunicaciones, (c) historial de trámites, (d) historial de solicitudes; parámetros `{tipoId}` e `{idUsuario}`. | #124,#143,#147,#119 | Must | La CCD consulta con tipoId+idUsuario → la entidad retorna JSON con los campos de cada servicio. |
| RF-B2-021 | Acceso del ciudadano a la CCD **exclusivamente** vía GOV.CO con nivel de confianza medio; la CCD no almacena datos permanentes (redirige al portal). | #124 | Must | El ciudadano ve sus datos en tiempo real desde los servicios de la entidad, sin almacenarlos en la CCD. |
| RF-B2-022 | Clasificar la información según Ley 1712/2014 antes de exponerla; solo datos no reservados/clasificados. | #124 | Must | Cuenta con índice de información clasificada y solo expone datos no reservados. |
| RF-B3-117 | Enviar comunicaciones y alertas al ciudadano vía CCD previa autorización. | #119,#224 | Should | Cambio de estado de un trámite → alerta en la CCD si el ciudadano autorizó ese canal. |

### 2.3 No exigir documentos / consultas a otras entidades

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B2-094 / RF-B3-112 | **No exigir** documentos que reposen en bases integradas a la interoperabilidad (Registraduría, RUNT, RUAF, etc.). | #141,#172 | Must | Trámite que requiere verificar cédula → consulta automática sin pedir copia al ciudadano. |
| RF-B3-113 | Consulta **gratuita y directa a la Registraduría** (ANI) para verificación de identidad. | Decreto 2106 Art.13 | Must | El sistema valida identidad vía ANI sin costo y sin fotocopia de cédula. |
| RF-B3-114 | Actualizar en **SUIT** los requisitos que pasan a verificarse por interoperabilidad. | Decreto 2106 Art.10 par.2° | Must | Trámite que ya no exige certificado SGSSS → la ficha en GOV.CO refleja que no aplica. |
| RF-B3-115 | Habilitar consulta en línea de registros públicos aún no integrados a X-Road. | Decreto 2106 Art.10 par.3° | Should | Otra entidad consulta en línea sin necesitar el documento físico. |
| RF-B2-095 | El registro electrónico permite **interconexión de dependencias** y la interoperabilidad entre el registro y otros sistemas de la entidad. | #143 | Must | Petición gestionada por dos dependencias → la segunda accede al expediente sin re-radicación. |

### 2.4 Integraciones con sistemas del Estado (catálogo)

| ID | Descripción | Fuente | Prio |
|----|-------------|--------|------|
| RF-B1-072 / RF-B3-148 | **SUIT (DAFP):** registrar/actualizar trámites (≤3 días hábiles); URL `/servicios-y-tramites/T{código}`. | #22,#165,#131 | Must |
| RF-B1-073 | **SIGEP:** directorio de servidores en tiempo real; NIT y códigos de dependencia; base del `memberCode` X-Road. | #165,#225,#203 | Must |
| RF-B1-074 | **SECOP I/II:** consulta de contratación (ver módulo 02). | #225,#239 | Must |
| RF-B1-075 / RF-B3-118/120 | **SGDEA:** articular trámites para expedientes electrónicos auténticos, íntegros, fiables, disponibles (ver módulo 12). | #251,#241 | Must |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-10-D01 | **Manejo de error y reintento en el intercambio X-Road**: timeouts, respuesta vacía, certificado/OCSP vencido del par, y registro de la falla; sin dejar el trámite "colgado". | [DOMINIO] UC-B3-011 describe el camino feliz; falta el de excepción | Must | Entidad destino no responde en N s → reintento configurable y, si falla, error controlado + log + fallback (RF-03-D06). |
| RF-10-D02 | **Continuidad del estampado TSA durante la migración Certicámara→GSE**: cola/buffer de mensajes pendientes de sello para que ninguno quede sin estampa durante la ventana. | [DOMINIO] el propio §9 lo señala como riesgo | Should | Ventana de migración TSA → los mensajes se encolan y se sellan al restablecer, ninguno queda sin RFC 3161. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-002 / RNF-B3-007 | Disponibilidad SCD | ≥99.98% (~99.982%) mensual; ~8.6 min indisponibilidad/mes | #156,#224 |
| RNF-B1-004 / RNF-B3-008 | RTO/interrupciones | RTO ≤8 min (SCD); máx 1 interrupción/mes | #156,#224 |
| RNF-B1-005 / RNF-B3-009 | RPO | ≤30 min | #156,#224 |
| RNF-B1-009/010/011 / RNF-B3-013/014/015 | Rendimiento | Autenticación <1 s; componentes SCD <5 s; latencia sede↔Articulador <3 s | #156,#224 |
| RNF-B1-034 / RNF-B2-015 / RNF-B3-029 | Marco de Interoperabilidad | Nivel 3 del LCI certificado antes de producción; 4 dominios (político-legal, organizacional, semántico, técnico) | #156,#165,#124,#119 |
| RNF-B1-035 | Estándares de API | SOA, REST/WS, XML, JWT, WS-Security | #156 |
| RNF-B2-014 | X-Road HA | Nivel K2 (99-99.9%) mínimo; K3 preferido (≥99.9%) | #126 |
| RNF-B3-030/031 | TSA / firma | 100% de mensajes con estampa TSA (RFC 3161) y firma digital | #119,#140 |
| RNF-B3-032 | Formularios normalizados | ≥80% normalizados al Lenguaje Común de Intercambio | #132,#217 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-10-D01 | Monitoreo de certificados | Alerta automática N días antes del vencimiento de certificados ONAC/TLS/OCSP del servidor de seguridad. | [DOMINIO] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-020 / RN-B3-013 | SCD base (Interoperabilidad, Autenticación, Carpeta) de **uso obligatorio** y **gratuitos** para la entidad; no implementar mecanismos propios que los reemplacen. | Decreto 1078/2015 Art.2.2.17; Decreto 2106 Art.9 | #156,#165 |
| RN-B2-004 / RN-B3-008 | No exigir documentos disponibles vía interoperabilidad (excepción: falla técnica documentada o registro no integrado). | Decreto 2106 Art.10; Decreto 620 Art.2.2.17.4.7 | #141,#143 |
| RN-B2-005 / RN-B3-009 | Requisitos verificables por interoperabilidad se actualizan en SUIT. | Decreto 2106 Art.10 par.2 | #141,#172 |
| RN-B2-016 / RN-B3-014 | El servicio de interoperabilidad lo presta **exclusivamente** la AND; sin infraestructura paralela. | Decreto 620 Art.2.2.17.2.2.2; Decreto 1078 Art.2.2.17.1.5 | #124,#143 |
| RN-B2-018 / RN-B3-029 | Paso a producción requiere certificación **nivel 3 LCI**. | Marco de Interoperabilidad | #124,#126 |
| RN-B2-021 | Imagen Docker standalone de X-Road solo para desarrollo/pruebas, no producción; tres ambientes separados. | Guía de Despliegue PDI | #126 |
| RN-B3-032 | `memberCode` X-Road = "sigla_entidad-código_SIGEP". | Marco de Interoperabilidad | #203 |
| RN-B2-025 | Vinculación a la CCD secuencial: Análisis → Ejecución. | Ficha de Vinculación CCD v6 | #147 |
| RN-B3-011 | La Registraduría provee interoperabilidad de identificación de forma gratuita. | Decreto 2106 Art.13 | — |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-10-D01 | **No dejar el trámite "colgado" ante error X-Road:** timeout, respuesta vacía o certificado/OCSP vencido del par generan reintento configurable y, si persiste, error controlado + log + fallback a carga manual (RN-03-D06); nunca un estado indefinido. (Motiva RF-10-D01.) | Marco de Interoperabilidad; buena práctica | [DOMINIO] |
| RN-10-D02 | **Continuidad del estampado TSA (RFC 3161):** durante la migración/indisponibilidad del proveedor TSA (Certicámara→GSE), los mensajes pendientes de sello se encolan y se estampan al restablecer; ninguno se cierra sin estampa cronológica. (Motiva RF-10-D02; mitiga el riesgo de cadena de custodia.) | RFC 3161; Decreto 2106 (autenticidad) | [DOMINIO]+[NORMATIVA] |
| RN-10-D03 | **Monitoreo y renovación anticipada de certificados:** se alerta N días antes del vencimiento de certificados ONAC/TLS/OCSP del servidor de seguridad; un certificado vencido en producción es un incidente. (Motiva RNF-10-D01.) | Marco de Interoperabilidad | [DOMINIO] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B2-010 / UC-B3-020 | Desplegar/configurar servidor X-Road | Equipo TI, AND | Instala servidor (Ubuntu/RHEL) con `install_X-ROAD_seguridad_v4.sh` → zona horaria Bogotá → genera CSR e importa certificados ONAC → configura TSA GSE (reemplaza Certicámara) → registra ante la AND → crea subsistemas y servicios → pruebas QA/preprod → producción con balanceador. | #124,#126,#140,#119,#203 |
| UC-B3-011 | Intercambiar información vía X-Road | Funcionario / Sistema | Sistema requiere datos de otra entidad → mensaje REST/SOAP con encabezados X-Road → servidor local cifra y retransmite al central AND → entidad destino responde → llega con estampa y firma → desencripta y usa. | #119,#203 |
| UC-B2-015 | Integrar entidad a la CCD | Equipo TI, AND, MinTIC | Análisis (reuniones, cuestionarios, plan) → define alcance → clasifica info (Ley 1712) → desarrolla los 4 servicios REST → pruebas QA/preprod → vinculación productiva → el ciudadano ve la info en su CCD. | #124,#147 |
| UC-B2-002 (parcial) | Verificación automática de requisitos | Ciudadano, SCD Interop. | Datos verificados automáticamente por interoperabilidad cuando están disponibles, sin pedirlos al ciudadano. | #141,#143,#172 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B2-004 | Como ciudadano con trámites en varias entidades quiero ver su estado en la Carpeta Ciudadana. | **Positivo:** **Dado** que estoy autenticado en GOV.CO con nivel de confianza medio **cuando** accedo a mi Carpeta Ciudadana **entonces** veo en una sola pantalla mi información personal, alertas, historial de trámites e historial de solicitudes de la Alcaldía en tiempo real. | #124,#143,#147 |
| HU-B2-015 / HU-B3-012 | Como ciudadano no quiero escanear mi cédula si el Estado ya la tiene. | **Positivo:** **Dado** que la integración X-Road con la Registraduría está operativa **cuando** inicio un trámite que requiere verificación de identidad **entonces** el sistema consulta automáticamente mis datos sin pedirme copia ni escaneo del documento. <br> **Negativo:** **Dado** que X-Road o la Registraduría no responden tras agotar reintentos **cuando** el sistema detecta la falla técnica **entonces** habilita la carga manual temporal del documento dejando constancia de "falla técnica documentada" y sin exigir la carga cuando la verificación automática funcione. | #124,#141,#143 |
| HU-B2-013 | Como equipo de TI quiero desplegar X-Road en Docker para pruebas aisladas. | **Positivo:** **Dado** que ejecuto la imagen `niis/xroad-security-server-standalone:bionic-6.21.0` **cuando** el contenedor levanta en el puerto 4000 con credenciales xrd/secr **entonces** el servidor X-Road de pruebas está operativo y aislado del servidor central de producción. | #126 |
| HU-B3-019 | Como equipo técnico quiero integrar X-Road en los 3 ambientes para intercambiar de forma segura. | **Positivo:** **Dado** que el servidor de seguridad está desplegado y configurado en los ambientes QA, preproducción y producción **cuando** se realiza un intercambio con la Registraduría en producción **entonces** el mensaje viaja cifrado TLS con firma digital RSA-SHA512 y estampa cronológica TSA. | #119,#203 |
| HU-B3-020 | Como equipo técnico quiero el TSA de la AND (GSE TSU 01) en lugar de Certicámara. | **Positivo:** **Dado** que agrego "GSE S.A. TSU 01" en Settings y elimino Certicámara **cuando** ejecuto el diagnóstico del servidor X-Road **entonces** el servicio TSA aparece activo y genera estampas RFC 3161 correctamente. <br> **Negativo:** **Dado** que estamos en la ventana de migración Certicámara→GSE y llegan mensajes a sellar **cuando** el proveedor TSA no está disponible **entonces** los mensajes se encolan y se estampan al restablecer el proveedor, sin que ninguno quede cerrado sin estampa RFC 3161. | #140 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-10-D01 | Como equipo técnico quiero que un fallo de X-Road no deje el trámite colgado para garantizar continuidad y trazabilidad. | **Positivo:** *Dado* un timeout del par, *cuando* el sistema reintenta hasta N veces, *entonces* si persiste registra error controlado y aplica fallback a carga manual (RN-03-D06).<br>**Negativo (OCSP vencido):** *Dado* un certificado/OCSP vencido del par, *cuando* se intenta el intercambio, *entonces* se aborta con error claro y log, sin estado indefinido. | RF-10-D01 · RN-10-D01 · UC-029 E5 · [DOMINIO] |
| HU-10-D02 | Como equipo técnico quiero encolar los mensajes pendientes de sello durante la migración TSA para que ninguno quede sin estampa RFC 3161. | **Positivo:** *Dado* la ventana de migración Certicámara→GSE, *cuando* llegan mensajes a sellar, *entonces* se encolan y se estampan al restablecer el proveedor.<br>**Negativo:** *Dado* un mensaje, *cuando* no se puede sellar, *entonces* NO se cierra sin estampa (queda en cola, nunca se da por sellado). | RF-10-D02 · RN-10-D02 · UC-029 E4 · [DOMINIO]+[NORMATIVA] |
| HU-10-D03 | Como equipo técnico quiero alertas N días antes del vencimiento de certificados ONAC/TLS/OCSP para renovarlos sin caída en producción. | **Positivo:** *Dado* un certificado que vence en N días, *cuando* corre el monitor, *entonces* recibo alerta para renovar.<br>**Negativo:** *Dado* un certificado vencido en producción, *cuando* se detecta, *entonces* se declara incidente. | RNF-10-D01 · RN-10-D03 · [DOMINIO] |

## 7. Datos / Entidades del módulo

- **Miembro X-Road:** `xRoadInstance`, `memberClass` (GOB/PRIV — ver §9), `memberCode` (sigla-SIGEP), `subsystemCode`, `serviceCode`, `serviceVersion`. (#119,#203)
- **Mensaje X-Road:** contenido, firma digital, estampa cronológica (RFC 3161), log de auditoría. (#124,#126)
- **Certificado digital:** tipo (autenticación/firma/TLS), estado OCSP, vencimiento, CA (ONAC/GSE). (#124,#140)
- **Carpeta ciudadana:** idMensaje, asunto, textoMensaje, URLDescargueAdjuntos, fechaMensaje (ISO 8601), campoDato/valorDato, historial de trámites (idTramite, nombreTramite, fechaRealiza, entidadesConsultadas), historial de solicitudes (idSolicitud, nombreSolicitud, fechaSolicitud, EstadoSolicitud, TextoRespuesta). (#124)
- **Acuerdo de vinculación:** entidad, AND, objeto, compromisos, fechas. (#119)
- **Configuración TSA:** proveedor (GSE TSU 01), URL, regla de firewall de salida. (#140)

## 8. Integraciones

- **AND (Articulador):** servidor central X-Road, anclaje de configuración, certificados, soporte (gobiernodigital@mintic.gov.co / soporteccc@mintic.gov.co). (#119,#147,#203)
- **CA acreditada ONAC** y **GSE (TSU 01)**: certificados y estampado. (#124,#140)
- **Registraduría (ANI, SIRC, ABIS)**, **RUNT, RUAF, RUT**: fuentes de datos. (#119,#141)
- **SUIT, SIGEP, SECOP, SGDEA**: ver módulos 02, 03, 12.

## 9. Ambigüedades y preguntas abiertas

- **[memberClass]** "CO" (guía 2019) vs "GOB/PRIV" (guía 2020) — verificar con la AND la vigente en producción. (#124,#126)
- **[SO obsoleto]** Las guías citan Ubuntu 18.04 / RHEL7, ya fuera de soporte (2025-2026). (#119,#203)
- **[Riesgo TSA]** La migración Certicámara→GSE implica ventana de interrupción del estampado; planificar para no dejar mensajes sin sello. (#140)
- **[Dependencia AND]** Los anclajes XML de configuración (3 entornos) no se publican; deben solicitarse a la AND. (#126)
- ~~**[PREGUNTA ABIERTA]** Nivel actual de integración con la PDI; ¿contrato/convenio con la AND? ¿Existe SGDEA?~~ → ✅ **RESPONDIDO (2026-06-05):** **nada integrado aún** con PDI/X-Road/CCD/Auth (greenfield; los trámites solo enlazan a gov.co/SUIT); **sin contrato/convenio con la AND** (#4) — prerrequisito a formalizar; **SGDEA = Orfeo** (a implementar). La vinculación con la AND es prerrequisito de todo este módulo. (#239)

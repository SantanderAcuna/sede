# Contexto transversal — Sede Electrónica Alcaldía Distrital de Santa Marta

> Consolidación de la información que NO pertenece a un solo módulo: catálogo de datos/entidades, integraciones del Estado, riesgos, dependencias, supuestos y el listado completo de contradicciones y ambigüedades. Deriva de las 3 Fases 2 (`/tmp/elicit/out/_fase2_part_{1,2,3}.md`).
> **Nota:** las rutas `/tmp/elicit/...` citadas en este entregable son **históricas** — ese corpus OCR fue eliminado tras la consolidación. La trazabilidad a fuente se conserva vía `#NN` (ítems base) y por etiqueta de procedencia `[DOMINIO]/[NORMATIVA]/[WEB]/[INFERENCIA]/[PREGUNTA ABIERTA]` (ítems `-D` del delta profundo).
> Ver `../README.md` para objetivos, usuarios, roles, normativa y plazos.

---

## 1. Catálogo de datos / entidades (modelo conceptual)

| Entidad | Atributos clave | Módulo principal | Fuente |
|---------|-----------------|------------------|--------|
| Sede electrónica | URL original, palabra clave, URL enmascarada GOV.CO, institución, categoría, sector, estado de integración | 01 | #241,#209 |
| Trámite/OPA/Consulta | id_SUIT (TXX), nombre, modalidad, descripción, requisitos, pasos, costo, tiempo, documentos, resultado, grupo objetivo, nivel de transformación (1-6), URL, georreferenciación, estado estandarizado | 03 | #128,#152,#172,#131 |
| Solicitud/Radicado | número único, fecha-hora, adjuntos, datos del formulario, estado, tiempo estimado, pago, emisor, destinatario | 03,04,12 | #128,#143,#152 |
| PQRSD | tipo, anonimato, identificación, correo, teléfono, dirección, canal, dependencia, objeto (≤2.000), adjuntos, radicado, estado, traslado | 04 | #239,#225,#209 |
| Usuario/Ciudadano | tipo y nº de documento (CC/CE/TI/PEP/NIT), nombre, correo, teléfono, dirección, biometría (alto/muy alto), consentimiento, versión de política | 09 | #122,#124,#143 |
| Datos personales sensibles | salud, biometría, origen étnico, orientación política/sexual, religión, sindicatos | 09 | #122 |
| Resultado del trámite | acto administrativo, documento de facultación, URL en Carpeta Ciudadana | 03 | #128,#152 |
| Expediente electrónico | documentos, metadatos (autenticidad/integridad/fiabilidad/disponibilidad), foliado, índice firmado, TRD, ciclo vital | 12 | #131,#217,#251 |
| Token OIDC | id_token, access_token, refresh_token, authorization_code, client_id, client_secret | 09 | #124 |
| Mensaje X-Road | instancia, memberClass (GOB/PRIV), memberCode (sigla-SIGEP), subsystemCode, serviceCode, serviceVersion, firma, estampa, log | 10 | #124,#126 |
| Carpeta ciudadana | idMensaje, asunto, textoMensaje, URLDescargueAdjuntos, fechaMensaje, campoDato/valorDato, historial de trámites, historial de solicitudes | 10 | #124 |
| Certificado digital | tipo, estado OCSP, vencimiento, CA (ONAC/GSE) | 10 | #124,#140 |
| Cookie | tipo, finalidad, origen, gestor, periodo de conservación, consentimiento | 01,09 | #241,#70 |
| Normativa | tipo, número, fechas (expedición/publicación), epígrafe, enlace, vigencia | 02 | #7,#225 |
| Contratación | plan anual de adquisiciones, contratos (objeto, monto), ejecución | 02 | #225 |
| Directorio institucional | dependencia, responsable, cargo, correo, teléfono, código SIGEP | 02 | #239,#225 |
| Información tributaria | impuesto, sujeto activo/pasivo, hecho generador, base gravable, tarifa, liquidación | 02 | #239,#225 |
| Dataset (datos abiertos) | nombre, descripción, categoría, publicador, fechas, formato, licencia, URL, criticidad | 11 | #8,#242 |
| Canal de atención | canal, dirección, código postal, horario, teléfono (+57), correo | 06 | #239 |
| Cita | dependencia/servicio, fecha, hora, contacto, código, estado | 06 | #225,#241 |
| Auditoría ITA | ítems cumplidos/total, puntuación, módulo, norma, avance %, estado, responsable | 12 | #239 |
| Log de auditoría/consentimiento | evento, actor, timestamp (Hora Legal), IP, versión de política, hash | 09,12 | #156,#122 |
| Evaluación SUS | 10 ítems Likert, puntaje (benchmark 68) | 08 | #190 |
| Arquetipo/proto-persona | nombre, edad, ubicación, nivel digital, necesidades, frustraciones | 08 | #60,#182 |
| Plan de integración | trámites, dominios web, otros medios (apps, chatbots, PQR) | 01 | #181 |

## 2. Integraciones del Estado (catálogo completo)

| Sistema | Propósito | Módulo | Fuente |
|---------|-----------|--------|--------|
| **Proxy GOV.CO (MinTIC)** | Enmascaramiento de URL / redireccionamiento | 01 | #241 |
| **SUIT (DAFP)** | Catálogo y fichas de trámites; código T{SUIT} | 03,10 | #239,#241 |
| **SECOP I/II** | Contratación pública | 02 | #225 |
| **SIGEP** | Directorio de servidores; memberCode X-Road | 02,10 | #225 |
| **datos.gov.co** | Datos abiertos federados | 11 | #242 |
| **X-Road / PDI (AND)** | Interoperabilidad; intercambio de datos | 10 | #124,#126 |
| **SCD Autenticación (OIDC)** | Autenticación digital delegada | 09 | #124,#156 |
| **Carpeta Ciudadana Digital** | Datos custodiados del ciudadano | 03,10 | #124,#147 |
| **SGDEA (Orfeo)** | Gestión documental, expedientes — se implementará **Orfeo** (open-source) | 04,12 | #241,#251 · decisión 2026-06-05 |
| **SUIN** | Información normativa nacional | 02 | #225 |
| **SUCOP (DNP)** | Comentarios a proyectos normativos | 05 | #225 |
| **KOGUI (ADJ)** | Informe de defensa pública | 02 | #225 |
| **Registraduría (ANI/SIRC/ABIS)** | Identidad y biometría | 09,10 | #119,#165 |
| **GSE (TSU 01)** | Estampado cronológico TSA (reemplazó Certicámara) | 10 | #140 |
| **CA ONAC** | Certificados digitales X-Road | 10 | #124 |
| **CSIRT-Gobierno / ColCERT** | Reporte de incidentes | 09 | #201 |
| **Pasarela de pago (Superfinanciera)** | Pagos en línea (PSE/tarjetas) | 03 | #128,#141 |
| **Procuraduría** | Identidad reservada | 04 | #239 |
| **AGN** | TRD y preservación | 12 | #241 |
| **CDN/Kit UI/Biblioteca GOV.CO** | Recursos gráficos y componentes | 01 | #130,#198 |
| **SAMI** | Aprobación de campañas de comunicación | — | #252 |
| **RUNT, RUAF, RUT** | Verificación por interoperabilidad | 10 | #141 |

### Especificaciones técnicas X-Road (referencia)
- Servidores: QA (1 CPU/4 GB/20 GB) · Preprod (2 CPU/6 GB/20 GB) · Prod (4 CPU/16 GB/20 GB, HA round-robin). SO: Ubuntu 18.04 LTS / RHEL7 v7.3+ *(obsoletos — verificar)*. (#119,#126,#203)
- Puertos TCP entrada: 5500, 5577, 9011, 9999 · salida: 5500, 5577, 4001, 80, 4000, 443, 9011. (#119,#126)
- Protocolo: HTTPS/TLS 1.2 + RSA-SHA512 + TSA (RFC 3161, GSE `tsa.gse.com.co`). (#124,#140)
- Endpoints OIDC: Authorize, Token, UserInfo, EndSession. (#124)
- Servicios CCD: 4 REST GET application/json (info usuario, alertas, historial trámites, historial solicitudes). (#124)
- Niveles de confianza de autenticación: Bajo (correo+OTP) · Medio (+MFA) · Alto (certificado+ANI) · Muy Alto (Cédula Digital/biometría ABIS). (#119)

## 3. Reportes obligatorios

- Tablero ITA 2025 (interno) · Informe de Gestión (31 ene) · Informes trimestrales PQRSD/inversión · Informe de Control Interno (6 meses) · Tablero BI de trámites · Reporte mensual de disponibilidad SCD (AND→MinTIC) · FURAG (DAFP) · Informe bianual de revisión de trámites (DAFP) · Encuesta SUS · Indicadores de los 5 propósitos de Gobierno Digital · Analítica de uso (Google Analytics/Hotjar) · Informe de pruebas de seguridad (AND, semana 16). (#239,#225,#251,#156,#165,#170,#108)

## 4. Riesgos consolidados

1. **Cumplimiento ITA real desconocido** (el 47/100 del sitio actual era "mock", descartado 2026-06-05); muchos plazos legales ya vencidos (WCAG 2022, seguridad/publicación 2021). El nuevo ITA se construye **desde cero** con validación automática al publicar (RF-B1-078/RF-12-D05). (#239,#30)
2. ~~**Datos del tablero ITA posiblemente "mock"** → falsa percepción de cumplimiento. (#239)~~ → ✅ **RESUELTO (2026-06-05):** el tablero ITA se rediseña como **validador interno automático que arranca en cero** y no usa datos mock ni el 47/100 (ver §8 #2; RF-B1-078/RF-12-D05).
3. **Contradicción C-01** (restricción de adjuntos PQRSD) = incumplimiento normativo concreto y verificable. (#239,#241)
4. **Cambio de TSA** (Certicámara→GSE) sin comunicar → fallos silenciosos en cadena de custodia. (#140)
5. **SO X-Road obsoletos** (Ubuntu 18.04/RHEL7) → vulnerabilidades sin parche. (#119)
6. **HPKP obsoleto** en las cabeceras exigidas → falsa seguridad. (#70)
7. **WCAG 2.1 vs 2.2** → estándar desactualizado. (#153)
8. **Captcha vs accesibilidad** → barrera para discapacidad visual. (#129,#152)
9. **Metodología de 17 semanas por trámite** → saturación de TI con trámites en paralelo. (#108)
10. **Dependencia del SM CMS propietario** sin DRP/BCP documentado. (#239)
11. **Riesgos de ciberseguridad**: XSS, SQLi, DDoS, fuerza bruta, inyección en formularios. (#19)
12. **Nube extranjera** para datos de ciudadanos → declaración SIC (Art.26 Ley 1581). (#122)
13. **RNBD**: no inscribir bases de datos → investigación disciplinaria. (#122)
14. ~~Documentos de criterios de aceptación vacíos (#93/#213)~~ → **RESUELTO**: son un GIF idéntico con 3 tips de usabilidad ya cubiertos, no la matriz formal de criterios (ver `auditoria-cobertura.md` §6). La matriz formal de MinTIC no está en el corpus → solicitarla aparte.
15. ~~**Clasificación incierta** de Santa Marta en el Decreto 088 → plazos incorrectos. (#79)~~ → ✅ **MITIGADO (2026-06-05): se adopta tier Alcaldía-Avanzado** (Santa Marta es Distrito; verificación cruzada en datos.gov.co agotada — no hay dataset público de la clasificación). Plazos may/2028·mar/2034·abr/2037 (los del entregable). Confirmación formal con MinTIC pendiente como formalidad; riesgo bajo.
16. **Zona de Auditoría** implica un **SIEM** no nombrado explícitamente. (#217)

## 5. Dependencias críticas

Proxy MinTIC (enmascaramiento) · SUIT (catálogo) · SIGEP (directorio) · SECOP (contratación) · datos.gov.co (federación) · AND (SCD, X-Road, certificados, anclaje de configuración) · Servidor Central X-Road · Registraduría (autenticación alto/muy alto) · SGDEA (expedientes) · TRD/AGN (archivo) · GSE (TSA) · CA ONAC (certificados) · capacidad técnica interna (equipo X-Road) · disponibilidad presupuestal · voluntad política de la alta dirección (factor de éxito documentado en lecciones aprendidas, #59). (#241,#239,#225,#156,#165,#126,#119,#140)

## 6. Supuestos

- Plazos de digitalización se cuentan desde **01/01/2022**. (#79,#99)
- La Alcaldía es **sujeto obligado** (Decreto 1078/2015 Art.2.2.17.1.2). (#143)
- GOV.CO solo enmascara la URL; el contenido y los datos se alojan en la infraestructura de la Alcaldía. (#209)
- Los SCD base son **gratuitos** para la Alcaldía; los costos de implementación son con cargo al presupuesto distrital. (#119,#143)
- La plataforma territorial GOV.CO fue adecuada por MinTIC en 2020; la Alcaldía administra contenidos. (#241)
- Se requiere equipo técnico con competencias en Linux, redes, certificados y REST/SOAP para X-Road. (#119)
- ~~Infraestructura preferida: nube (MRAE); centros de datos Tier III para SCD.~~ → **ACTUAL (2026-06-05): DigitalOcean + Cloudflare** (no Tier III certificado ni datacenter propio). Brecha a verificar vs. exigencias de los SCD de la AND; DRP/BCP por diseñar (RNF-TX-D04). (#251,#156)
- **SGDEA = Orfeo** (open-source, gratuito; a implementar/configurar) — decisión 2026-06-05.

## 7. Contradicciones y ambigüedades (listado completo)

### Contradicciones
- **C-01** Adjuntos PQRSD: norma prohíbe restricciones (#241) vs. implementación limita PDF/JPG/PNG 10 MB (#239); Anexo 5 (libre) vs. Anexo 5.1 ("entidad define"). **ALTA**
- **C-02** Disponibilidad: ≥95% (sede/portal/VU) vs. ≥98% (trámites/Anexo 1). Resolución sugerida: ≥98% (Anexo 1 prevalente). **ALTA**
- **C-03** Disponibilidad SCD: 99.98% vs 99.982% (interna #156). MEDIA
- **C-04** Ubicación del formulario PQRSD (menú "Atención y Servicios" vs. sección propia). ALTA
- **C-05** Fecha Decreto 088 ("2021" en encabezado vs. firma "24 ENE 2022"). MEDIA
- **X-Road memberClass** "CO" (2019) vs "GOB/PRIV" (2020). MEDIA
- **Tier Alcaldía-Avanzado** tiene los plazos más largos (contraintuitivo: mayor nº de trámites). 

**Contradicciones nuevas (delta segunda pasada profunda `jose-rf-rnf-profundo`, 2026-06-04):**
- **C-06** Adjuntos PQRSD (profundiza C-01): RF-B1-033 ("sin restricciones de formato/tamaño/cantidad") vs. RF-03-D04 / RF-B1-063 (validar MIME real + antivirus). Sin límite de tamaño hay riesgo de DoS por subida. Resolución sugerida: permitir cualquier formato y **no** rechazar por tipo, pero aplicar antivirus y un **límite técnico de servidor alto y documentado** (no funcional), separando "restricción al derecho" de "control de seguridad". **MEDIA** — ✅ **DECIDIDO (2026-06-05):** separar dominios. PQRSD no rechaza por tipo/tamaño/cantidad pero aplica antivirus + límite técnico de servidor alto y documentado (no funcional); trámites validan MIME real, tamaño, cantidad y antivirus (RF-03-D04/RN-03-D04).
- **C-07** Política de credenciales internas: RNF-B1-021 (contraseña ciudadano ≥8) vs. RF-B3-074 (login interno CMS soporta CC/CE/TI/PEP/NIT como usuario) — no se define complejidad ni 2FA para usuarios internos del CMS (solo para ciudadanos vía SCD). Resolución: **MFA obligatorio para administradores del CMS** (cubierto parcialmente por RF-09-D02). **MEDIA** — ✅ **DECIDIDO (2026-06-05):** MFA obligatorio para **todos los roles internos del CMS** (editor, aprobador, administrador, seguridad), no solo administradores. La contraseña ≥8 no basta para back-office.
- **C-08** Nivel de confianza por trámite: RF-B2-021 fija acceso a CCD con nivel "Medio" como ejemplo, pero **ningún RF fija el nivel de autenticación mínimo por tipo de trámite**. Resolución: crear **matriz trámite ↔ nivel de autenticación exigido**. **MEDIA** — ✅ **DECIDIDO (2026-06-05):** se adopta una **matriz trámite ↔ nivel por riesgo** (bajo/medio/alto/muy alto); el sistema bloquea el inicio si el nivel del ciudadano es inferior al requerido (RN-TX-D04). La matriz concreta por trámite se construye con G-CIO + AND en el levantamiento.

### Ambigüedades / vacíos
A-01 "otros sujetos obligados" y top bar (¿obligación o facultad?) · A-02 Guía de Usabilidad mezcla "debe/recomienda" sin jerarquía · A-03 datos ITA mock vs real · A-04 datos.gov.co vs archivo digital · A-05 plazos de gradualidad SCD no reproducidos · A-06 criterios de incidentes "graves" sin umbral · A-07 grupos de interés sin caracterización definida · A-08 contraste escrito "4:5:1" (error) · A-09 equivalencia niveles autenticación bajo/medio/alto vs nivel 2/3 · A-10 fecha de la encuesta SUS · A-11 enrutamiento PQRSD ¿automático o manual? · A-12 "acciones de integración" heterogéneas en el Plan Unificado · A-WCAG: AA obligatorio sin fiscalización/sanción/umbral · barra A11y en tablet sin definir · LSC ¿aplica a territorial? · criterios de aceptación (#93,#213) = GIF de tips ya cubierto (resuelto, ver auditoría §6); matriz formal NO está en el corpus · des-integración de portal no regulada · sin criterios cuantitativos mínimos de usabilidad · atención a brecha digital no regulada · HPKP/SO obsoletos.

## 8. Preguntas abiertas para el levantamiento con la Alcaldía

1. ¿Clasificación exacta de Santa Marta en el Decreto 088? Determina TODOS los plazos. → 🔎 **INVESTIGADO (2026-06-05, fuente: texto oficial Decreto 088/2022 en funcionpublica.gov.co):** los plazos territoriales dependen del tier **Alcaldía-Avanzado / Intermedio / Básico** (no "Grupo 1/2"). **Avanzado** (junto a Gobernaciones y Distrito Capital) = plazos MÁS LARGOS: digitalizar Bloque 1 (30%) **may/2028**, 100% **mar/2034**; automatizar 100% **abr/2037** — coinciden EXACTO con los plazos asumidos en este entregable. **Básico/Intermedio** = plazos MÁS CORTOS: Bloque 1 **feb/2024 (vencido)**, 100% **mar/2027**. ✅ **SUPUESTO DE PLANIFICACIÓN ADOPTADO (2026-06-05): Alcaldía-Avanzado** (lo más probable para un distrito capital; riesgo bajo). **PENDIENTE DE CONFIRMACIÓN OFICIAL:** la clasificación por entidad NO está publicada como dataset abierto en datos.gov.co (solo el "Índice de Gobierno Digital"); vive en anexo/listado interno de MinTIC → confirmar con la Dirección de Gobierno Digital de MinTIC o la oficina TIC de la Alcaldía. **Si resultara Básico/Intermedio = emergencia de cumplimiento (plazos vencidos).** ✅ **DECISIÓN FINAL (2026-06-05):** verificación cruzada en datos.gov.co agotada (no hay dataset público de la clasificación por entidad) → **se fija la clasificación como Alcaldía-Avanzado** por ser Santa Marta un Distrito (mismo tier que Distrito Capital/Gobernaciones); el oficio a MinTIC queda como formalidad de confirmación, no como bloqueante.
2. ¿Nivel real de cumplimiento ITA? ¿El tablero es mock o real? → ✅ **DECIDIDO (2026-06-05): el ITA arranca en CERO; tablero interno de validación automática.** El 47/100 era dato mock/falso y **NO se usa** como línea base. El nuevo tablero ITA es **interno (no público)**, parte de **cero** y **valida automáticamente** el cumplimiento de cada contenido al publicarlo (p. ej. imagen con `alt`, video con subtítulos, metadatos de transparencia completos); marca los incumplimientos para corrección manual y se actualiza solo al publicar (RF-B1-078 + RF-12-D05). **Resuelve A-03 (ya no hay datos mock).**
3. ¿Cuántos trámites en SUIT y su nivel de digitalización? ¿Cuáles de mayor volumen? → ✅ **CONFIRMADO (2026-06-05, fuente: buscador oficial SUIT de Función Pública):** **124 trámites/OPA** registrados — la faceta de entidad muestra literalmente *"ALCALDIA DISTRITAL DE SANTA MARTA, DISTRITO TURISTICO, CULTURAL E HISTORICO (124)"*. Distribuidos en ~11 dependencias (DADSA, Dirección Jurídica, Movilidad, Gobierno, Planeación, Educación, Hacienda, Salud, Catastro Multipropósito, INRED, Alcaldías Locales 1/2/3). **Mayor visibilidad/demanda probable:** Impuestos (predial/ICA), Catastro, Tránsito/Movilidad (el ranking exacto por solicitudes/año sale del ejercicio de priorización del SUIT, RF-B3-150). **Implicación:** **124 trámites** a digitalizar en 3 bloques (≈37/37/50).
4. ¿Contratos/SLA vigentes con la AND (SCD), proveedor del SM CMS y nube? → ✅ **RESPONDIDO (2026-06-05): NO / no documentados.** No hay contratos/SLA formales con la AND, el proveedor del CMS ni la nube. **⚠️ RIESGO:** los SCD (X-Road, Autenticación Digital, Carpeta Ciudadana) requieren acuerdo/vinculación con la AND; sin contrato/SLA, las integraciones y los niveles de servicio quedan sin respaldo formal. Acción: formalizar vinculación con AND y SLA de nube/CMS antes de producción. ✅ **Contraparte identificada (2026-06-05): Dirección TIC** (ver #5). **Acción ejecutada:** oficio formal `solicitud-vinculacion-AND.pdf` generado y listo para radicar (X-Road/PDI, Autenticación Digital, Carpeta Ciudadana).
5. ¿Existe SGDEA instalado? ¿PETI vigente? ¿Oficina de Relación con el Ciudadano (Art.17 Ley 2052)? → ✅ **DECIDIDO (2026-06-05): SGDEA = Orfeo.** NO hay SGDEA instalado; **se implementará y configurará Orfeo** (gestor documental open-source, gratuito, nacido en la Superintendencia de Servicios Públicos y muy usado en el Estado colombiano — encaja con presupuesto público). Todos los RF que dependían de "¿Existe SGDEA?" (RF-B1-092/036/075, RF-B3-155 firma) se resuelven sobre Orfeo. ✅ **PETI: ENCONTRADO (2026-06-05, sitio oficial).** Existe **PETI 2024-2027 (actualizado 2026)** publicado y vigente — `https://www.santamarta.gov.co/sites/default/files/peti-2024-2027_actualizado_2026.pdf` (HTTP 200, PDF verificado); además persiste el `PETI_2023.pdf`. Se referencia como insumo y se alinea el roadmap de la sede a él. ✅ **Oficina de Relación con el Ciudadano: CONFIRMADA (2026-06-05).** Existe la dependencia **"Atención al Ciudadano"** / **"Atención al Ciudadano y Participación Social"** (organigrama oficial `/dependencias`); correo `atencionalciudadano@santamarta.gov.co`, radicación L-V 8:00–17:00 (días hábiles). Es el owner funcional de PQRSD/atención. ✅ **Dirección TIC: CONFIRMADA (2026-06-05).** Existe la **Dirección de las Tecnologías de la Información y las Comunicaciones (TIC)** (Calle 14 No. 2-49, Palacio Municipal, Centro Histórico, tel. 420 9600) — es la contraparte técnica natural del proyecto y del oficio de vinculación con la AND. **Cierra los pendientes de #5: PETI, Oficina y dependencia TIC quedan resueltos.**
6. ¿Proceso interno de enrutamiento y respuesta de PQRSD? → ✅ **DECIDIDO (2026-06-05): enrutamiento HÍBRIDO** (resuelve A-11) — el sistema sugiere la dependencia por tipo/asunto y un funcionario de ventanilla única valida/corrige antes de asignar; reasignable si cambia la competencia (RF-04-D01). El detalle del proceso interno (quién valida, SLA de asignación) se confirma con la Alcaldía.
7. ¿Infraestructura en nube (Tier III) o datacenter propio? ¿DRP/BCP probados? → ✅ **RESPONDIDO (2026-06-05): nube DigitalOcean + Cloudflare.** Hoy se usa **DigitalOcean** (VPS/droplets) con **Cloudflare** (DNS, CDN, WAF, anti-DDoS). NO es datacenter propio ni Tier III certificado. **Implicaciones:** (a) Cloudflare cubre parte de los RF de seguridad (WAF, mitigación DDoS, CDN, TLS) — RF-B1-056/069, RF-B2-061; (b) **brecha vs. el supuesto previo** "Tier III para SCD" (los SCD de la AND pueden exigir Tier III/condiciones específicas → verificar con AND); (c) **DRP/BCP por definir y probar** (RNF-TX-D04) — DigitalOcean+Cloudflare no garantizan continuidad sin diseño explícito de respaldo/recuperación.
8. ¿Micrositios/apps/portales independientes por integrar a la sede? → 🔎 **PARCIAL (2026-06-05):** en el sitio actual se identificaron al menos **portal de impuestos, `sgdsm.gov.co` y "Visita Santa Marta" (turismo)**; el **inventario completo** (catastro, movilidad, apps, otros) se confirma con la **Dirección TIC**. **Acción ejecutada:** se generó el documento formal de levantamiento `solicitud-inventario-micrositios.pdf` (dirigido a la Dirección TIC, con los 3 detectados como base) para recoger el inventario completo. Queda pendiente solo la respuesta de la Alcaldía.
9. ¿Nivel de integración actual con PDI/X-Road, Carpeta Ciudadana y Autenticación Digital? → ✅ **RESPONDIDO (2026-06-05): nada integrado aún (greenfield).** Los trámites ya enlazan a gov.co/SUIT, pero **NO hay integración con X-Road/PDI, Carpeta Ciudadana ni Autenticación Digital** — todo por construir. Coherente con #4 (sin contrato con la AND). **Implicación:** la capa de interoperabilidad y SCD es un esfuerzo desde cero que **requiere primero la vinculación formal con la AND**; impacta el roadmap (es prerrequisito de los trámites autenticados, CCD y "no exigir documentos").

**Preguntas abiertas nuevas (delta segunda pasada profunda `jose-rf-rnf-profundo`, 2026-06-04):**
10. ¿Cuánto tiempo se conserva un trámite a medio diligenciar (borrador, RF-03-D03) antes de expirar? (Líder funcional de trámites + Protección de Datos — minimización). → ✅ **RESUELTA (2026-06-05): 30 días**; vencido el plazo el borrador se purga/anonimiza (fija RNF-03-D02).
11. ¿Estructura del número de radicado (prefijo dependencia + año + consecutivo) y unicidad bajo concurrencia? (Gestión Documental). → ✅ **RESUELTA (2026-06-05): prefijo dependencia + año + consecutivo atómico** (ej. `SM-CAT-2026-000123`); consecutivo asignado de forma atómica para garantizar unicidad (fija RNF-04-D01).
12. ¿El agendamiento de citas se hace dentro de la sede o se integra con un sistema de turnos existente? ¿De dónde sale el calendario de disponibilidad? (Oficina de Atención al Ciudadano). → ✅ **RESUELTA (2026-06-05): agenda propia en la sede**; el CMS administra servicios, franjas, cupos y bloqueos (RF-06-D01/UC-047). La sede es la fuente de verdad de la disponibilidad.
13. ¿Los aportes de Participa se reciben dentro de la sede o íntegramente en SUCOP? Determina si hay CRUD propio o solo redirección (Oficina de Participación + DNP/SUCOP). → ✅ **RESUELTA (2026-06-05): todo dentro de la sede** con CRUD propio (RF-05-D01/D02). *Nota: las consultas normativas que el marco DNP obligue a radicar en SUCOP se mantienen vía redirección (RF-B1-039); el CRUD propio aplica a los mecanismos de participación del Distrito.*
14. ¿Umbral de tasa de éxito de tareas para declarar "cumple" en usabilidad (además de SUS ≥68)? (Equipo UX) — resuelve A-14. → ✅ **RESUELTA (2026-06-05): ≥90% de tasa de éxito** de tareas (además de SUS ≥68) para declarar "cumple" (fija RF-08-D01/RN-08-D01). Umbral exigente acorde a trámites de alto volumen/criticidad.
15. ¿Matriz trámite ↔ nivel de autenticación exigido (bajo/medio/alto/muy alto)? (ver C-08). → ✅ **RESUELTA (2026-06-05): matriz por riesgo** (= decisión de C-08); bloquea inicio si el nivel del ciudadano < requerido. La matriz concreta por trámite se construye con G-CIO + AND.
16. ¿Qué contenidos se traducen y cómo se tratan las lenguas étnicas como complemento (RN-B1-012)? ¿Fallback al castellano? (Comunicaciones). → ✅ **RESUELTA (2026-06-05): castellano únicamente por ahora**; las lenguas étnicas quedan como **compromiso futuro** (afecta RNF-TX-D02/RN-TX-D05 → su prioridad baja a Could). El botón de idioma (RF-B3-055) queda diferido hasta definir alcance de traducción.
17. ¿Política de retención y purga de datos personales por entidad (borradores, citas, logs de consentimiento) conforme Ley 1581 y TRD? (Oficial de Protección de Datos). → ✅ **RESUELTA (2026-06-05): retención por categoría según TRD + minimización** (Ley 1581); cada categoría de dato tiene su período conforme a la TRD; vencido, se purga o anonimiza (fija RN-TX-D03/RNF-TX-D03).

**Preguntas abiertas nuevas (delta reglas de negocio `jose-reglas-negocio-profundo`, 2026-06-04):**
18. ¿Política de reembolso cuando un trámite ya pagado se desiste (RN-03-D07)? (Tesorería + Líder de trámites). → ✅ **DECIDIDO (2026-06-05): reembolso solo si el trámite NO inició la gestión.** Una vez iniciada, el derecho/tasa cubrió el servicio y no se reembolsa. Lo formaliza Tesorería en el reglamento de cartera (fija UC-043 E2 / HU-03-D08).
19. ¿Qué trámites de la Alcaldía están sujetos a **silencio administrativo positivo** y con qué término (RN-03-D08, verificado en el sitio para espectáculos públicos)? (Secretaría Jurídica). → ✅ **DECIDIDO (2026-06-05): silencio NEGATIVO por defecto** (CPACA Art.83, confirmado en investigación Función Pública/MinJusticia). El SAP es **excepción**: solo se activa en los trámites donde una norma especial lo establece (ej. espectáculos públicos, Ley 1493). Secretaría Jurídica arma el catálogo revisando la norma habilitante de cada trámite; cada trámite marca su tipo de silencio en SUIT.

## 9. RNF transversales (delta segunda pasada profunda `jose-rf-rnf-profundo`)

> Requisitos no funcionales que no pertenecen a un único módulo. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-TX-D01 | Observabilidad | Monitoreo de disponibilidad (uptime) por componente con alerta automática al incumplir el SLA (≥98% trámites / ≥95% sede, C-02); sin esto no se puede demostrar el cumplimiento del RNF de disponibilidad ya especificado. | [DOMINIO] |
| RNF-TX-D02 | Internacionalización | El botón de idioma (RF-B3-055) implica gestión de contenidos traducidos y de las lenguas étnicas como complemento (RN-B1-012). **Decidido 2026-06-05: castellano únicamente por ahora; lenguas étnicas a futuro → prioridad Could, DIFERIDO.** | [DOMINIO] · decisión #16 |
| RNF-TX-D03 | Retención/minimización | Política de retención y purga de datos personales por entidad (borradores de trámite, citas, logs de consentimiento) conforme Ley 1581 (minimización) y TRD. | [PREGUNTA ABIERTA] (Oficial de Protección de Datos) |
| RNF-TX-D04 | Pruebas RTO/RPO | Verificación periódica (restauración real de backup) de los DRP/BCP; los RNF de RTO≤8 min/RPO≤30 min están declarados pero no se exige probarlos. | [DOMINIO] |

## 10. RN transversales (delta segunda pasada profunda `jose-reglas-negocio-profundo`)

> Reglas de negocio invariantes que aplican a más de un módulo. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-TX-D01 | **Calendario hábil único del Distrito:** todos los cómputos de plazos legales (PQRSD, trámites, subsanación, traslado, prórroga) usan el mismo calendario de días hábiles que excluye sábados, domingos y festivos nacionales/distritales; una sola fuente de verdad evita cómputos divergentes. (Habilita RN-04-D01/02/03, RN-03-D05.) | Ley 1755/2015; Ley 1437/2011 | [NORMATIVA]+[DOMINIO] |
| RN-TX-D02 | **Sellado temporal con Hora Legal:** toda evidencia con valor legal (radicado, consentimiento, firma, log, notificación) usa la Hora Legal de Colombia (INM) y, donde aplique, estampa TSA RFC 3161; no se admiten timestamps de reloj local no sincronizado. | RFC 3161; Decreto 4175/2011 (Hora Legal) | [NORMATIVA] |
| RN-TX-D03 | **Retención y purga de datos personales por entidad:** cada categoría de dato (borrador de trámite, cita, log de consentimiento) tiene un período de retención conforme a la TRD y al principio de minimización; vencido el período, se purga o anonimiza. **[PREGUNTA ABIERTA]** períodos por entidad. Responsable: Oficial de Protección de Datos. | Ley 1581/2012 Art.4; Ley 594/2000 (TRD) | [PREGUNTA ABIERTA]+[NORMATIVA] |
| RN-TX-D04 | **Nivel de autenticación mínimo por tipo de trámite:** cada trámite exige un nivel de confianza (bajo/medio/alto/muy alto) acorde al riesgo; el sistema bloquea el inicio si el nivel del ciudadano es inferior al requerido. **[PREGUNTA ABIERTA]** matriz trámite↔nivel. (Resuelve C-08.) | Decreto 620/2020; Guía SCD | [PREGUNTA ABIERTA]+[NORMATIVA] |
| RN-TX-D05 | **Fallback al castellano en contenidos traducidos:** cuando un contenido no tiene traducción disponible en el idioma/lengua étnica seleccionada, se muestra el castellano (lengua oficial) marcando que es el original; las lenguas étnicas son complemento, no reemplazo. (Opera RN-B1-012; motiva RNF-TX-D02.) **Decidido 2026-06-05: DIFERIDO (Could)** — castellano únicamente por ahora; la regla de fallback aplica cuando se habilite la traducción. | Ley 1712/2014; Art.10 CP | [NORMATIVA] · decisión #16 |

> **Reconciliaciones de RN (pasada profunda):** se revisaron 4 pares de reglas potencialmente en conflicto. Ninguna es contradicción nueva: *adjuntos PQRSD vs trámites* ya está como **C-06**; *gratuidad de consultas vs costo de copias* (RN-B1-011 / RN-04-D08), *notificación electrónica preferente vs canal no digital* (RN-B3-019 / RN-06-D03) y *no exigir documentos vs carga manual por falla técnica* (RN-B2-004 / RN-03-D06) resultaron **compatibles** con precisión de enunciado. Detalle en `_global/rn-delta-profundo.md`.

## 11. Historias de Usuario — enriquecimiento, divisiones INVEST y cobertura por rol (delta `jose-historias-usuario-profundo`)

> Las 57 HU nuevas se integraron en la sección `## 6.` de cada módulo (subsección `### 6.D`). Aquí quedan los aspectos cross-módulo: escenarios G/W/T negativos que se agregan a HU existentes, las épicas que conviene partir (INVEST) y los roles antes ausentes. Detalle completo en `_global/hu-delta-profundo.md`.
> **Hallazgo:** el artefacto base de HU era el más flojo de los cuatro (~45-55%): ~5-7 HU por módulo, casi todas del camino feliz del ciudadano, sin back-office ni escenarios negativos. Con este delta sube a ~90%.

### 11.1 Enriquecimiento de HU existentes (escenarios G/W/T que se agregan)
| HU existente | Escenario que se agrega | Given / When / Then | Evidencia |
|---|---|---|---|
| HU-B2-001 (trámite 100% en línea) | Negativo — sesión expira a mitad del stepper | *Dado* una sesión de 900 s, *cuando* expira en el paso 3, *entonces* se reautentica preservando lo cargado (enlaza HU-03-D04) | RNF-B1-023 · UC-042 E3 |
| HU-B1-022/B2-016/B3-011 (pago en línea) | Negativo — doble cobro / pago pendiente | (ver HU-03-D01 y HU-03-D02) — idempotencia y conciliación | RF-03-D01/D02 |
| HU-B1-025/B2-004 (resultado en CCD) | Negativo — CCD no responde al publicar el resultado | *Dado* la CCD indisponible, *cuando* el trámite finaliza, *entonces* el resultado se encola y se reintenta sin marcar el trámite como fallido | RF-12-D03 |
| HU-B2-011/B3-007 (consulta por radicado) | Negativo — IDOR sobre radicado ajeno autenticado | *Dado* un radicado ajeno, *cuando* cambio el ID estando autenticado, *entonces* 403 + log | RF-09-D01 · UC-002 E4 |
| HU-B1-002 (radicar PQRSD) | Negativo — concurrencia de consecutivo | *Dado* dos envíos simultáneos, *cuando* se asigna radicado, *entonces* números únicos atómicos | RN-04-D07 · UC-001 E9 |
| HU-B1-012/B2-003 (queja anónima) | Negativo — no exposición de identidad reservada en back-office | *Dado* una queja con reserva, *cuando* un funcionario la abre, *entonces* no ve datos del peticionario | RN-04-D06 |
| HU-B1-023/B3-006 (validación en tiempo real) | Negativo — NIT con DV inválido / objeto >2.000 | (ver HU-04-D06) | RF-04-D04 |
| HU-B1-004/B3-007 (estado por radicado) | Negativo — radicado inexistente | *Dado* un radicado que no existe, *cuando* lo consulto, *entonces* mensaje claro "no encontrado", no error técnico | [DOMINIO] |
| HU-B1-019/B3-009 (agendar cita) | Negativo — último cupo en concurrencia | (ver HU-06-D03) | RF-06-D03 |
| HU-B3-021 (login OIDC) | Negativo — Articulador caído / `state` inválido | (ver HU-09-D04) | RF-09-D04 |
| HU-B2-014 (evidencia de consentimiento) | Negativo — sellado con Hora Legal, no reloj local | *Dado* un registro de consentimiento, *cuando* se sella, *entonces* usa Hora Legal de Colombia (INM) | RN-TX-D02 |
| HU-B1-011 (publicar resolución) | Negativo — SoD y estado editorial | *Dado* el editor creador, *cuando* intenta publicar sin aprobación de un rol distinto, *entonces* se bloquea (HU-12-D01/HU-09-D03) | RF-12-D01 · RN-09-D02 |
| HU-B1-021 (eliminar requiere aprobación) | Negativo — auto-aprobación de la eliminación | *Dado* quien solicitó la eliminación, *cuando* intenta aprobarla él mismo, *entonces* se bloquea por SoD | RN-09-D02 |
| HU-B2-008 (log de expediente) | Negativo — intento de alterar el log | *Dado* el log de auditoría, *cuando* alguien intenta modificarlo, *entonces* se impide (append-only) | RNF-09-D01 |
| HU-B3-017 (firma electrónica) | Negativo — certificado vencido/OCSP | *Dado* un certificado vencido, *cuando* intento firmar, *entonces* se rechaza con mensaje claro | RN-10-D03 |
| HU-B3-022 (alerta de vencimiento PQRSD) | Enriquecer — plazo diferenciado por tipo | *Dado* una consulta (30 días), *cuando* calcula el vencimiento, *entonces* aplica el plazo correcto por tipo sobre calendario hábil | RF-04-D03 · RN-04-D01 |
| HU-B1-008 (descargar dataset) | Negativo — dataset desactualizado visible | *Dado* un dataset vencido en frescura, *cuando* lo consulto, *entonces* veo la marca de "última actualización" y su antigüedad | RN-11-D01 |
| HU-B2-018 (publicar dataset) | Negativo — archivo mal formado | (ver HU-11-D02) | RNF-11-D01 |
| HU-B1-016/B3-027 (participar consulta) | Negativo — consulta cerrada / extemporánea | (ver HU-05-D01) | RN-05-D01 |
| HU-B1-018/B3-003 (subtítulos/LSC) | Negativo — bloqueo de publicación sin subtítulos | (ver HU-07-D01) | RF-07-D01 |
| HU-B1-006 (transparencia/SECOP) | Negativo — SECOP caído | (ver HU-02-D03) | RF-02-D03 |
| HU-B3-015 (Plan de Acción antes del 31-ene) | Negativo — alerta de vencimiento previo | (ver HU-02-D02) | RF-02-D02 |
| HU-B2-015/B3-012 (no escanear cédula) | Negativo — X-Road caído → carga manual documentada | (ver HU-03-D10) | RF-03-D06 |
| HU-B3-020 (TSA GSE) | Negativo — continuidad de sello en migración | (ver HU-10-D02) | RF-10-D02 |
| HU-B3-023 (cookies sin consentimiento) | Negativo — caducidad/versionado del consentimiento | (ver HU-01-D01) | RF-01-D01 |

### 11.2 Divisiones INVEST (épicas a partir)
| HU original | Por qué viola INVEST | HU hijas propuestas |
|---|---|---|
| HU-B2-001 "trámite 100% en línea" | Épica: mezcla inicio, autenticación, diligenciamiento multipaso, adjuntos, pago, interoperabilidad, radicación y resultado. No estimable ni testeable como una sola HU. | (a) HU-03-D11 iniciar+autenticar al nivel · (b) HU-03-D04 diligenciar con guardar/reanudar · (c) HU-03-D05 validar/cargar adjuntos · (d) HU-03-D10 verificación interoperabilidad con fallback · (e) HU-03-D01/D02/D03 pago idempotente y conciliado · (f) HU-03-D13 radicar atómico · (g) HU-B1-025 enriquecida resultado en CCD |
| HU-B1-002 "radicar PQRSD" | Solo cubre el alta del ciudadano; el ciclo de vida (enrutar, trasladar, prorrogar, responder, notificar, cerrar, medir) está ausente. | Mantener HU-B1-002 + nuevas HU-04-D01..D05 (back-office) separadas por rol funcionario. |
| HU-B1-019/B3-009 "agendar cita" | Asume disponibilidad mágica; sin origen de la oferta, reprogramación, concurrencia ni no-show. | HU-06-D01 (administrar agenda) · HU-06-D02 (reprogramar) · HU-06-D03 (concurrencia) · HU-06-D04 (no-show); HU-B1-019 queda como "reservar". |
| HU-B1-011 "publicar resolución rápido" | Esconde flujo editorial con estados y SoD como acción atómica. | HU-12-D01 (ciclo editorial/rechazo) + HU-09-D03 (SoD); HU-B1-011 queda como "editar y enviar a aprobación". |
| HU-B2-018 / HU-B1-008 (datos abiertos) | Publicar+descargar sin CRUD, versionado, validación ni frescura. | HU-11-D01 (CRUD/versionado/frescura) + HU-11-D02 (validación); base queda como "descargar"/"publicar inicial". |

### 11.3 Variaciones por rol incorporadas (actores antes ausentes)
| Rol | Estado en HU base | HU del delta que lo cubren |
|---|---|---|
| Ciudadano anónimo vs identificado | Parcial (anónimo solo en PQRSD) | HU-09-D01 (anti-IDOR distingue propio/ajeno); radicado anónimo público se preserva |
| Funcionario de dependencia (back-office) | **Ausente** | HU-03-D07, HU-04-D01..D05, HU-04-D07 |
| Administrador / configurador | Muy parcial | HU-01-D02/D03, HU-02-D01/D02/D04, HU-05-D01, HU-06-D01, HU-11-D01/D02, HU-12-D01/D02 |
| Editor vs Aprobador (SoD) | **Ausente como roles segregados** | HU-09-D03, HU-12-D01, HU-07-D01 |
| Oficial de seguridad / auditor | Solo HU-B1-020 (bloqueo login) | HU-09-D05/D06/D07 |
| Equipo técnico (interoperabilidad) | Parcial (despliegue) | HU-10-D01/D02/D03 |
| Equipo UX | Solo HU-B2-019 | HU-08-D01/D02 |
| Representante legal de menor | **Ausente** | HU-12-D04 |
| Oficial de cumplimiento (ITA) | Parcial (HU-B1-014) | HU-02-D02 |

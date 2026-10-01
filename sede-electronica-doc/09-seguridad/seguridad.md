# Módulo 09 — Seguridad Digital

> Sede Electrónica — Alcaldía Distrital de Santa Marta
> **Alcance:** seguridad de transporte (HTTPS/TLS), cabeceras de seguridad, cookies seguras, captcha accesible, control de métodos HTTP, sanitización, manejo de errores, control de acceso/RBAC, autenticación (incl. SCD/OIDC y niveles de confianza), CSRF, logs de auditoría, gestión de incidentes (CSIRT), backups (DRP/BCP), hardening, MSPI/SGSI, protección de datos personales y derechos ARCO.
> **Cruces:** X-Road/TSA → módulo **10 Interoperabilidad**; logs/auditoría operativa y firma electrónica → módulo **12 Gestión de Contenidos**.
> **Convenciones y trazabilidad:** ver `../README.md`.

---

## 1. Descripción y alcance

Aplica el **MSPI** (Modelo de Seguridad y Privacidad de la Información) de MinTIC y los lineamientos del Anexo 3 de la Res. 1519/2020. Cubre la seguridad técnica de la sede y la protección de datos personales (Ley 1581/2012), incluyendo el módulo de ejercicio de derechos ARCO. La autenticación de ciudadanos se delega al SCD de Autenticación Digital (OIDC) del Articulador.

## 2. Requisitos Funcionales (RF)

### 2.1 Transporte, cabeceras, cookies, métodos

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-055 / RF-B2-053 / RF-B3-129 | **HTTPS obligatorio** con certificado SSL de validación de organización (OV+); redirección automática HTTP→HTTPS; certificado vigente. | #19,#53,#241,#70,#132,#201 | Must | Acceso por `http://` → redirige a `https://` con certificado válido. |
| RF-B1-056 / RF-B2-055 / RF-B3-130 | **Cabeceras de seguridad**: CSP, X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, HSTS (≥31536000), Referrer-Policy, Feature/Permissions-Policy (las guías citan también HPKP — obsoleto, ver §9). | #19,#53,#70,#201 | Must | securityheaders.com → todas las cabeceras presentes y bien configuradas. |
| RF-B1-057 / RF-B2-014 | **Cookies** con `Secure` y `HttpOnly`; expiración definida; no accesibles por JS; TCP session timeout ≤900 s. | #19,#53,#156,#124 | Must | Set-Cookie con `Secure` y `HttpOnly` en todas las cookies sensibles. |
| RF-B1-059 / RF-B2-054 / RF-B3-135 | **Deshabilitar métodos HTTP peligrosos** (PUT, DELETE, TRACE, OPTIONS no necesarios) → HTTP 405; **hardening** (sin credenciales por defecto, administración remota restringida). | #19,#53,#241,#70,#201 | Must | Petición TRACE → 405 Method Not Allowed. |
| RF-B1-060 / RF-B2-056 / RF-B3-131 | **Sanitización** de todas las entradas (formularios, querystring, cookies); escape de variables; validación cliente y servidor. | #19,#53,#241,#70,#201 | Must | `<script>` en un campo → sanitizado, nunca se ejecuta ni se almacena. |
| RF-B1-061 / RF-B2-057 / RF-B3-133 | **Mensajes de error genéricos** sin revelar tecnología, versiones, excepciones ni parámetros. | #19,#53,#70,#201 | Must | Error 500 → "Se presentó un error. Intente más tarde." sin traza ni stack. |
| RF-B1-058 / RF-B2-058 / RF-B3-132 | **Captcha accesible** (auto-detectable o alternativa de audio, WCAG 2.1 AA) en formularios que capturan datos (PQRSD, contacto, login); límite de intentos (anti-fuerza bruta). | #19,#53,#241,#128,#132,#201 | Must | Al final del PQRSD se exige captcha; con discapacidad visual hay alternativa de audio. |
| RF-B1-064 | **Token CSRF** único por sesión en formularios de acciones de estado, validado en servidor. | #19 | Must | Solicitud forjada CSRF al endpoint PQRSD → rechazada por token inválido. |

### 2.2 Control de acceso, autenticación y login

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-062 / RF-B3-132 | **Control de acceso**: límite de 5 intentos fallidos → bloqueo temporal; páginas de administración no accesibles desde internet sin autenticación; mínimo privilegio. | #19,#53,#241 | Must | 5 intentos fallidos → bloqueo ≥15 min + alerta de seguridad. |
| RF-B1-063 | Permisos de solo lectura para escritura desde la web; adjuntos en directorio fuera del webroot con validación de tipo MIME real. | #19,#53 | Must | PHP disfrazado de PDF → validación MIME lo rechaza y registra el intento. |
| RF-B3-074 | **Módulo de inicio de sesión** (Kit UI): usuario (correo o cédula), contraseña, captcha accesible, botones Registrar/Olvidé mi contraseña/Iniciar sesión; soporta CC, CE, TI, PEP, NIT. | #118 | Must | Usuario con CC puede seleccionar "CC" y autenticarse. |
| RF-B1-025/026 / RF-B2-012/013/015 / RF-B3-107/108/109 | **Autenticación SCD (OIDC Authorization Code)** por nivel de confianza (Bajo: correo+OTP; Medio: +MFA; Alto: certificado+ANI; Muy Alto: biometría/Cédula Digital); **SSO/SLO**; autorización propia (roles) posterior; revocación inmediata de tokens al dar de baja. | #156,#165,#251,#124,#119 | Must | Trámite nivel Medio → redirección a la pasarela del Articulador, retorno con token, sesión y permisos. |
| RF-B3-106 | Registro de usuarios con **validación contra ANI** (Registraduría). *(consulta vía X-Road, módulo 10)* | #131 | Must | Al registrarse, el sistema valida cédula vigente y correspondencia de nombre vía ANI. |

### 2.3 Logs, incidentes, backups, MSPI

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B1-065 / RF-B2-059 / RF-B3-022 | **Logs de auditoría** (eventos de seguridad, accesos fallidos, transacciones, cambios en CMS): timestamp (Hora Legal Colombiana), acción, actor, IP, resultado; conservación ≥5 años. | #19,#156,#128,#131 | Must | Edición de una norma en el CMS → log con fecha/hora, usuario, acción, ID del documento e IP. |
| RF-B1-066 / RF-B2-016(seg)/ RF-B3-134 | **Gestión de incidentes**: reporte al **CSIRT-Gobierno** (o ColCERT) en ≤24 h; registro con clasificación (leve/grave/muy grave), fecha, impacto, acciones; planes **DRP/BCP** para 7/24/365. | #19,#251,#201 | Must | Incidente grave clasificado → reporte al CSIRT en ≤24 h con los campos requeridos. |
| RF-B1-067 / RF-B2-062 / RF-B3-134 | **Backups**: diario completo + incremental cada 4 h; verificación de integridad; retención ≥30 días; almacenamiento separado; DRP/BCP documentado; copias con info clasificada cifradas. | #19,#241,#70,#224 | Must | Backup diario → verificación de integridad registrada en el log de backups. |
| RF-B1-069 / RF-B2-064 / RF-B3-137/138 | Adoptar el **MSPI**; monitoreo continuo (vulnerabilidades, listas negras, DDoS, patrones anómalos); pentest/ethical hacking; análisis estático del código. | #19,#165,#70,#128,#201 | Must | Tráfico anómalo DDoS → mitigación activa y alerta automática al equipo de seguridad. |
| RF-B1-068 / RF-B2-065 / RF-B3-135/136 | **Git** + CI/CD con pruebas antes del despliegue; actualización de parches críticos ≤72 h; revisión de código en producción. | #19,#70,#201 | Should/Must | Cambio en el repositorio → el pipeline ejecuta pruebas antes de desplegar. |

### 2.4 Protección de datos personales y derechos ARCO

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-B2-047 | **Módulo de derechos ARCO** (Acceso, Rectificación, Cancelación, Oposición): formulario con acuse automático y plazo (10 días hábiles consulta). | #122 | Must | Solicitud ARCO enviada → acuse automático con radicado y plazo. |
| RF-B2-048/049 | **Consentimiento** (casilla no pre-marcada) con finalidad, responsable y derechos ARCO; autorización **explícita diferenciada** para datos sensibles (salud, biometría). | #122,#128 | Must | Formulario con datos sensibles → advertencia específica y casilla separada de consentimiento explícito. |
| RF-B2-050/051/052 | Actualizar/rectificar datos desde el perfil (≤5 días hábiles); conservar **log de consentimiento** (timestamp, IP, versión de política); permitir revocación y supresión (o justificación legal). | #122 | Must | Solicitud de prueba de autorización → se extrae del log el registro con timestamp, IP y versión de política. |
| RF-B2-090 | **Notificación de cambios** en la política de tratamiento antes de su vigencia; nueva autorización si amplía el alcance. | #122 | Must | Política ampliada → todos los usuarios reciben notificación y deben aceptar la nueva versión. |

### 2.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

> Requisitos derivados por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rf-rnf-delta-profundo.md`.

| ID | Descripción | Fuente | Prio | Criterio (G/W/T) |
|----|-------------|--------|------|------------------|
| RF-09-D01 | **Autorización a nivel de recurso (IDOR)**: un ciudadano autenticado solo puede consultar sus propios radicados/trámites/PQRSD; el acceso por número de radicado a recursos ajenos exige el control de pertenencia. | [DOMINIO] OWASP A01 Broken Access Control; el RBAC actual (RNF-B2-010) cubre módulos, no objetos | Must | Ciudadano A intenta abrir el radicado de B cambiando el ID en la URL → 403, registrado en log. |
| RF-09-D02 | **Política de contraseñas y recuperación segura** del login interno (RF-B3-074): complejidad, expiración, bloqueo, y flujo "Olvidé mi contraseña" con token de un solo uso y expiración. | [DOMINIO] el botón existe pero el flujo no está especificado | Must | "Olvidé contraseña" → enlace con token válido 15 min, un solo uso; tras restablecer, invalida sesiones activas. |
| RF-09-D03 | **Segregación de funciones (SoD)** explícita: quien crea/edita un contenido no puede ser quien lo aprueba/publica; quien gestiona usuarios no audita sus propias acciones. | [DOMINIO]+[INFERENCIA] RF-B1-076 menciona "pendiente de aprobación" pero no prohíbe el auto-aprobado | Must | Editor que creó una norma intenta aprobarla → bloqueado por SoD. |
| RF-09-D04 | **Caída del SCD de Autenticación (OIDC)**: comportamiento ante indisponibilidad del Articulador (mensaje, reintento, no bloquear el acceso a contenido público) y manejo de `error`/`state` inválido en el callback. | [DOMINIO] manejo de error de federación; no especificado | Must | Articulador caído → "La autenticación no está disponible, intente más tarde"; callback con `state` no coincidente → rechazado y registrado. |

## 3. Requisitos No Funcionales (RNF)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-B1-020 / RNF-B3-021 | Cifrado | ≥2048 bits RSA; ≥256 bits AES | #156,#224 |
| RNF-B2-008 | TLS | TLS 1.2 mínimo (1.3 recomendado); nada sensible en HTTP plano | #124,#126 |
| RNF-B2-009 | Cifrado en reposo | AES-256 mínimo para datos en BD | #122 |
| RNF-B1-021 | Contraseñas | ≥8 car. (usuario); ≥6 (generadas por sistema) | #156 |
| RNF-B1-022 | Certificados | ≤2 años de validez | #156 |
| RNF-B1-023 | Session timeout | 900 s (15 min) | #156 |
| RNF-B1-024 / RNF-B3-022 | Logs | Retención ≥5 años; 100% eventos críticos; Hora Legal Colombiana | #156,#131,#224 |
| RNF-B1-025 / RNF-B3-025 | Incidentes | Reporte ≤24 h al CSIRT | #19,#201 |
| RNF-B1-026 | Actualizaciones | Parches críticos ≤72 h | #19,#241 |
| RNF-B2-010 / RNF-B3-026 | RBAC | 100% de módulos con datos personales bajo RBAC; revisión trimestral de permisos | #122,#201 |
| RNF-B2-011 | Confidencialidad | Revocar accesos de desvinculados ≤1 día hábil | #122 |
| RNF-B2-012 / RNF-B3-024 | SGSI/MSPI | ≥95-100% de controles MSPI implementados (ISO 27000, NIST SP 800-53) | #128,#201 |
| RNF-B3-019 | SSL | Rating A/A+ en SSL Labs | #132,#201 |
| RNF-B3-020 | OWASP | OWASP Top 10; 0 vulnerabilidades críticas/altas sin remediar | #132,#201 |
| RNF-B1-040/041 / RNF-B2-022 / RNF-B3-027/028 | Privacidad por diseño | PbD en todo el ciclo; minimización; 100% de campos justificados; PIA documentada | #156,#143,#122,#224 |
| RNF-B2-027 | Transferencias internacionales | Declaración de conformidad SIC para nube extranjera (Art.26 Ley 1581) | #122 |

### 3.D Delta — segunda pasada profunda (`jose-rf-rnf-profundo`, 2026-06-04)

| ID | Categoría | Umbral | Fuente |
|----|-----------|--------|--------|
| RNF-09-D01 | Integridad de logs | Los logs de auditoría (RF-B1-065) deben ser **inmutables/a prueba de manipulación** (append-only o con encadenamiento por hash), no solo retenidos 5 años. | [DOMINIO]+[INFERENCIA] el §9 ya advierte que la zona de auditoría implica un SIEM |
| RNF-09-D02 | Rate limiting | Límite de tasa por IP/sesión en endpoints públicos (PQRSD, login, búsqueda) además del captcha, contra DoS de aplicación. | [DOMINIO] OWASP |
| RNF-09-D03 | Criterio de incidente "grave" | **[PREGUNTA ABIERTA]** definir el umbral local (resuelve A-06) para disparar el reporte CSIRT ≤24 h. Responsable: Oficial de Seguridad. | [PREGUNTA ABIERTA] |

## 4. Reglas de Negocio (RN)

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-B1-013 / RN-B3-030 | Datos personales tratados con autorización previa, expresa e informada; evidencia con timestamp. | Ley 1581/2012; Decreto 1074/2015 | #156,#225,#209 |
| RN-B1-016 / RN-B3-... | Incidentes graves/muy graves → CSIRT en ≤24 h. | Res. 1519/2020 Anexo 3; Decreto 088/2022 | #19,#251,#201 |
| RN-B2-006 | La Alcaldía = Responsable del Tratamiento; proveedores = Encargados (cláusulas Art.18 Ley 1581). | Ley 1581/2012 Arts.17-i,18 | #122 |
| RN-B2-007 | No recolectar datos de menores de 18 sin autorización del representante legal; verificación de edad. | Ley 1581/2012 Art.7 | #122 |
| RN-B2-008/009 | Minimización: solo datos estrictamente necesarios; no usar para otra finalidad sin nueva autorización. | Ley 1581/2012 Arts.4 | #122 |
| RN-B2-010 | Inscribir bases de datos de ciudadanos en el **RNBD** ante la SIC. | Ley 1581/2012 Art.25; Decreto 886/2014 | #122 |
| RN-B2-022/030 | Notificar a la SIC violaciones de seguridad; manual interno de políticas de datos. | Ley 1581/2012 Arts.17-n,18-k | #122 |
| RN-B3-031 | Ninguna cookie no esencial activa por defecto; consentimiento previo, expreso e informado por categoría. | Ley 1581/2012 | #132,#209 |

### 4.D Delta — segunda pasada profunda (`jose-reglas-negocio-profundo`, 2026-06-04)

> Reglas derivadas por dominio/normativa en la pasada de profundización. Detalle y procedencia en `_global/rn-delta-profundo.md`.

| ID | Regla | Cita | Fuente |
|----|-------|------|--------|
| RN-09-D01 | **Autorización por recurso (anti-IDOR):** un ciudadano autenticado solo accede a SUS propios radicados/trámites/PQRSD/citas; el acceso por número/ID a recursos ajenos se deniega (403) y se registra. El RBAC por módulo (RN implícito en RNF-B2-010) no sustituye el control de pertenencia por objeto. (Motiva RF-09-D01.) | OWASP A01; Ley 1581 (acceso indebido a datos) | [DOMINIO]+[NORMATIVA] |
| RN-09-D02 | **Segregación de funciones (SoD):** quien crea/edita un contenido o norma NO puede aprobarlo/publicarlo; quien administra usuarios NO audita sus propias acciones. El auto-aprobado está prohibido. (Motiva RF-09-D03/RF-12-D01.) | MSPI; control interno (Modelo de las 3 líneas) | [DOMINIO]+[INFERENCIA] |
| RN-09-D03 | **Recuperación de credenciales segura:** el token de "Olvidé mi contraseña" es de **un solo uso y expira** (p. ej. 15 min); restablecer la contraseña invalida las sesiones activas. (Motiva RF-09-D02.) | MSPI; buena práctica de autenticación | [DOMINIO] |
| RN-09-D04 | **MFA obligatorio para administradores del CMS:** los usuarios internos con privilegios de edición/aprobación/administración requieren segundo factor; la contraseña ≥8 (RNF-B1-021) no basta para roles internos. (Resuelve C-07.) | MSPI; OWASP | [DOMINIO]+[NORMATIVA] |
| RN-09-D05 | **Inmutabilidad del log de auditoría:** los registros de auditoría son append-only / encadenados por hash; no pueden alterarse ni borrarse durante su retención (5 años), ni siquiera por un administrador. (Motiva RNF-09-D01.) | MSPI; integridad probatoria (Ley 527/1999) | [DOMINIO]+[NORMATIVA] |
| RN-09-D06 | **Continuidad ante caída del SCD de Autenticación:** la indisponibilidad del Articulador (OIDC) NO bloquea el acceso al contenido público; un `state` no coincidente en el callback se rechaza y registra. (Motiva RF-09-D04.) | OWASP (federación); buena práctica OIDC | [DOMINIO] |
| RN-09-D07 | **Umbral local de incidente "grave"** que dispara el reporte CSIRT ≤24 h. (Opera RN-B1-016; resuelve A-06.) **[PREGUNTA ABIERTA]** definir el umbral. Responsable: Oficial de Seguridad. | Res. 1519/2020 Anexo 3 | [PREGUNTA ABIERTA] |

## 5. Casos de Uso (UC)

| ID | Nombre | Actor | Resumen | Fuente |
|----|--------|-------|---------|--------|
| UC-B1-010 | Iniciar sesión como administrador | Administrador | Accede al panel (no público) → usuario/contraseña → 5 fallos → bloqueo 15 min + alerta → autenticación exitosa → dashboard por rol → log de auditoría. | #19,#53,#241 |
| UC-B2-005 / UC-B3-005 | Autenticar ciudadano con SCD | Ciudadano, SCD Autenticación (OIDC) | Login de trámite → redirección a `Authorize` → autenticación según nivel → retorno con `authorization_code` → canje por tokens → sesión con perfil. | #124,#143,#119,#118 |
| UC-B3-004 | Registrarse en la sede | Ciudadano | Tipo/nº de documento → validación ANI en tiempo real → correo/contraseña (≥8) → OTP → cuenta activa. | #131,#119 |
| UC-B2-007 | Ejercer derechos ARCO | Ciudadano autenticado | Módulo ARCO → tipo (Acceso/Rectificación/Cancelación/Oposición) → formulario + soportes → acuse con radicado → gestión por el Oficial de Protección de Datos → respuesta 10/15 días hábiles. | #122 |
| UC-B1-012 / UC-B2-016 | Gestionar incidente de seguridad | Equipo de seguridad, CSIRT | Detección → clasificación → si afecta datos, notifica SIC → reporte al CSIRT ≤24 h → mitigación (bloqueo IP, parches) → restauración → informe post-incidente. | #19,#251,#122,#128 |

## 6. Historias de Usuario (HU)

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-B1-020 | Como admin de seguridad quiero bloqueo tras 5 intentos fallidos. | **Positivo:** **Dado** que un usuario realiza el 6.º intento de inicio de sesión tras 5 fallos consecutivos **cuando** el sistema evalúa el intento **entonces** aplica bloqueo de 15 minutos, muestra un mensaje genérico y envía alerta al administrador de seguridad. | #19,#53 |
| HU-B2-010 | Como ciudadano quiero ver que la sede es segura y oficial. | **Positivo:** **Dado** que accedo a cualquier página de la sede **cuando** la cargo **entonces** veo el candado HTTPS, el dominio GOV.CO y en el footer los datos oficiales (teléfono +57 y correo institucional). | #53,#70,#128 |
| HU-B2-014 | Como oficial de datos quiero que el sistema registre la evidencia del consentimiento. | **Positivo:** **Dado** que un ciudadano acepta la política de tratamiento de datos **cuando** confirma el consentimiento **entonces** el sistema genera un log con ID de sesión, timestamp de la Hora Legal de Colombia (INM), versión de política, IP y hash del documento. <br> **Negativo:** **Dado** un registro de consentimiento **cuando** se sella el timestamp **entonces** se usa la Hora Legal de Colombia (INM) y no el reloj local del servidor no sincronizado, garantizando la validez probatoria del sello. | #122 |
| HU-B3-021 | Como ciudadano quiero autenticarme con la Autenticación Digital SCD (OIDC) con un único usuario. | **Positivo:** **Dado** que tengo cuenta en la Autenticación Digital de la AND **cuando** inicio sesión en la sede **entonces** accedo sin crear una cuenta nueva, usando mi identidad federada OIDC. <br> **Negativo:** **Dado** un callback OIDC con parámetro `state` no coincidente con el enviado **cuando** el sistema lo recibe **entonces** lo rechaza, registra el intento en el log de auditoría como posible CSRF y muestra un mensaje de error genérico. | #119,#224 |
| HU-B3-023 | Como ciudadano quiero que las cookies de análisis no se activen sin mi consentimiento. | **Positivo:** **Dado** que accedo a la sede por primera vez **cuando** se carga la página **entonces** aparece el banner con categorías de cookies y ninguna cookie no esencial está activa hasta que las acepto. <br> **Negativo:** **Dado** que mi consentimiento de cookies ha caducado o la política ha cambiado de versión **cuando** vuelvo a la sede **entonces** el banner reaparece y las cookies no esenciales permanecen bloqueadas hasta que vuelva a aceptar con la versión vigente. | #132,#209 |

### 6.D Delta — segunda pasada profunda (`jose-historias-usuario-profundo`, 2026-06-04)

> Historias nuevas (back-office, escenarios negativos y roles antes ausentes). Detalle, divisiones INVEST y cobertura por rol en `_global/hu-delta-profundo.md`.

| ID | Historia | Criterio (G/W/T) | Fuente |
|----|----------|------------------|--------|
| HU-09-D01 | Como ciudadano autenticado quiero que solo yo pueda ver mis radicados/trámites/PQRSD/citas para que nadie acceda a mis datos cambiando un ID. | **Positivo:** *Dado* mi sesión, *cuando* abro un radicado propio, *entonces* veo su detalle.<br>**Negativo:** *Dado* el ID de un radicado ajeno, *cuando* lo pongo en la URL, *entonces* recibo 403 y queda registrado en el log.<br>> Nota: los radicados anónimos públicos consultables por número siguen siendo públicos (sin cambio). | RF-09-D01 · RN-09-D01 · UC-002/004/009 E(IDOR) · [DOMINIO] OWASP A01 · [NORMATIVA] Ley 1581 |
| HU-09-D02 | Como usuario interno del CMS quiero un flujo "Olvidé mi contraseña" seguro para recuperar acceso sin exponer mi cuenta. | **Positivo:** *Dado* que solicito recuperación, *cuando* recibo el enlace, *entonces* el token es de un solo uso y expira en 15 min; al restablecer se invalidan mis sesiones activas.<br>**Negativo (token reusado):** *Dado* un token ya usado o expirado, *cuando* intento usarlo, *entonces* se rechaza y se solicita uno nuevo.<br>**Negativo (enumeración):** *Dado* un correo inexistente, *cuando* solicito recuperación, *entonces* la respuesta es genérica (no revela si la cuenta existe). | RF-09-D02 · RN-09-D03 · UC-031 E1' · [DOMINIO] |
| HU-09-D03 | Como administrador quiero que quien crea un contenido no pueda aprobarlo para garantizar control interno. | **Positivo:** *Dado* un editor que creó una norma, *cuando* la envía a aprobación, *entonces* un administrador distinto la aprueba.<br>**Negativo (auto-aprobación):** *Dado* el creador, *cuando* intenta aprobar su propio contenido, *entonces* el sistema lo bloquea por SoD.<br>**Negativo (auto-auditoría):** *Dado* quien gestiona usuarios, *cuando* intenta auditar sus propias acciones, *entonces* se impide. | RF-09-D03 · RN-09-D02 · UC-048 E1 · [DOMINIO]+[INFERENCIA] |
| HU-09-D04 | Como ciudadano quiero seguir viendo el contenido público aunque el Articulador falle para que una caída de autenticación no me deje sin sede. | **Positivo:** *Dado* el Articulador caído, *cuando* navego contenido público, *entonces* lo veo, y al intentar autenticarme veo "autenticación no disponible, intente más tarde".<br>**Negativo (state inválido):** *Dado* un callback OIDC con `state` no coincidente, *cuando* el sistema lo recibe, *entonces* lo rechaza y registra (posible CSRF). | RF-09-D04 · RN-09-D06 · UC-005 E7 · [DOMINIO] |
| HU-09-D05 | Como oficial de seguridad quiero exigir segundo factor a los roles internos del CMS para que una contraseña filtrada no comprometa el back-office. | **Positivo:** *Dado* un administrador, *cuando* inicia sesión, *entonces* se le exige MFA además de la contraseña.<br>**Negativo:** *Dado* un administrador sin MFA configurado, *cuando* intenta operar, *entonces* el sistema lo obliga a enrolarlo antes de continuar. | RN-09-D04 · UC-017/020 E4 · C-07 · [DOMINIO]+[NORMATIVA] MSPI |
| HU-09-D06 | Como auditor quiero que los logs de auditoría sean inalterables para que tengan valor probatorio. | **Positivo:** *Dado* un evento de auditoría, *cuando* se escribe, *entonces* queda en almacenamiento append-only/encadenado por hash durante 5 años.<br>**Negativo:** *Dado* un administrador, *cuando* intenta editar o borrar un registro de auditoría, *entonces* el sistema lo impide y registra el intento. | RNF-09-D01 · RN-09-D05 · [DOMINIO]+[NORMATIVA] Ley 527/1999 |
| HU-09-D07 | Como oficial de seguridad quiero límite de tasa por IP/sesión en login, PQRSD y búsqueda para mitigar fuerza bruta y DoS de aplicación. | **Positivo:** *Dado* un uso normal, *cuando* opero, *entonces* no me afecta el límite.<br>**Negativo:** *Dado* un volumen anómalo desde una IP, *cuando* supera el umbral, *entonces* se aplica throttling/bloqueo temporal y se registra. | RNF-09-D02 · [DOMINIO] OWASP |

## 7. Datos / Entidades del módulo

- **Usuario del sistema (interno):** identificación, rol, permisos, historial de auditoría. (#239)
- **Usuario SCD (ciudadano):** nivel de confianza, tipo/nº de documento, biometría (alto/muy alto), correo, teléfono, dirección. (#156,#124)
- **Token OIDC:** id_token, access_token, refresh_token, authorization_code, client_id, client_secret. (#124)
- **Log de auditoría / consentimiento:** evento, actor, timestamp (Hora Legal), IP, versión de política, hash. (#156,#122)
- **Incidente de seguridad:** clasificación (leve/grave/muy grave), fecha de detección, impacto, acciones. (#19,#201)
- **Datos personales sensibles:** salud, biometría, origen étnico, orientación política/sexual, religión, sindicatos. (#122)

## 8. Integraciones

- **SCD Autenticación Digital (AND)** vía OIDC; Registraduría (ANI, biometría) — consulta vía X-Road (módulo 10). (#119,#124)
- **CSIRT-Gobierno / ColCERT**: reporte de incidentes. (#201)
- **SIC**: RNBD, notificación de brechas. (#122)
- **CA acreditada ONAC / GSE**: certificados digitales y TSA (módulo 10). (#124,#140)

## 9. Ambigüedades y preguntas abiertas

- **[HPKP]** Las guías exigen HPKP, pero Chrome lo eliminó en 2018 → falsa seguridad; evaluar omitirlo. (#70,#144)
- **[Captcha vs A11y]** Captcha obligatorio puede ser barrera para discapacidad visual → debe ser accesible. (#129,#152,#153)
- **[A-06]** Criterios para clasificar incidentes "graves/muy graves" quedan a cada entidad; no hay umbral normativo unificado. (#19)
- **[Riesgo]** Si la sede usa nube extranjera para datos de ciudadanos → declaración de conformidad SIC (Art.26 Ley 1581). (#122)
- **[Riesgo]** Zona de Auditoría con correlación de logs implica un **SIEM** no nombrado explícitamente. (#217)
- **[PREGUNTA ABIERTA]** ¿Existe DRP/BCP documentado y probado? ¿La infraestructura está en nube (Tier III) o datacenter propio? (#239)

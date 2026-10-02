# Requisitos No Funcionales — Módulos 01 (Estructura-Identidad) y 02 (Transparencia)

> **Marco:** ISO/IEC 25010:2011 (modelo de calidad) + FURPS+ (Grady 1992) + plantilla Wiegers [16].
> **Umbrales:** medibles, verificables con instrumentación (Lighthouse, k6, axe-core, OWASP ZAP).
> **Trazabilidad:** cada RNF → diseño arquitectónico → tarea → prueba de aceptación.

---

## Resumen ejecutivo

| Categoría ISO 25010 | Total | Must | Should | Could |
|---|---|---|---|---|
| Rendimiento (Eficiencia de desempeño) | 3 | 3 | 0 | 0 |
| Capacidad / escalabilidad | 2 | 2 | 0 | 0 |
| Disponibilidad / fiabilidad | 2 | 2 | 0 | 0 |
| Seguridad | 3 | 3 | 0 | 0 |
| Usabilidad | 2 | 2 | 0 | 0 |
| Accesibilidad | 2 | 2 | 0 | 0 |
| Mantenibilidad | 1 | 1 | 0 | 0 |
| Portabilidad / compatibilidad | 2 | 1 | 1 | 0 |
| Protección de datos | 1 | 1 | 0 | 0 |
| **Total** | **18** | **17** | **1** | **0** |

---

## RNF-REND — Rendimiento

### RNF-REND-01 — Tiempo de respuesta primer byte (TTFB)
- **Descripción:** El TTFB medido en percentil 95 (p95) para todas las rutas públicas del sitio debe ser ≤ 200 ms en el percentil 95 desde CDN regional (Bogotá).
- **Categoría:** Eficiencia de desempeño
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Lighthouse Performance ≥ 90
  - k6: p95 TTFB ≤ 200 ms con 100 VUs concurrentes durante 5 min sobre `/`, `/transparencia`, `/transparencia/contratacion`.
- **Medición:** job de Lighthouse CI en nightly + dashboard en Grafana.
- **Trazabilidad:** → Diseño `03-propuesta/.../c4-contenedores.md` §Caching → Tarea T-REND-01 → Test PT-REND-01

### RNF-REND-02 — Largest Contentful Paint (LCP)
- **Descripción:** LCP en p75 debe ser ≤ 2,5 segundos en 3G rápido simulado (Lighthouse mobile).
- **Categoría:** Eficiencia de desempeño
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Lighthouse Performance ≥ 90 en mobile.
  - `web-vitals` instrumentado reporta LCP p75 ≤ 2,5 s en RUM (Real User Monitoring) sobre 28 días.
- **Trazabilidad:** → Diseño §RenderStrategy → Tarea T-REND-02 → Test PT-REND-02

### RNF-REND-03 — First Input Delay (FID) / Interaction to Next Paint (INP)
- **Descripción:** INP en p75 ≤ 200 ms; el sitio debe responder a interacciones del usuario de manera fluida.
- **Categoría:** Eficiencia de desempeño
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Lighthouse Performance ≥ 90.
  - `web-vitals` en RUM reporta INP p75 ≤ 200 ms sobre 28 días para ≥75% de las sesiones.
- **Trazabilidad:** → Diseño §RenderStrategy → Tarea T-REND-03 → Test PT-REND-03

---

## RNF-CAP — Capacidad / Escalabilidad

### RNF-CAP-01 — Throughput mínimo
- **Descripción:** El sitio debe soportar ≥ 1000 usuarios concurrentes con tiempo de respuesta degradado máximo 50% respecto al óptimo (criterio "graceful degradation").
- **Categoría:** Capacidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - k6: 1000 VUs durante 10 min sin errores 5xx; p95 latencia ≤ 500 ms.
- **Trazabilidad:** → Diseño §Escalabilidad → Tarea T-CAP-01 → Test PT-CAP-01

### RNF-CAP-02 — Almacenamiento de documentos
- **Descripción:** El sistema debe soportar ≥ 500 GB de documentos con crecimiento anual de 50 GB sin degradación de performance de búsqueda.
- **Categoría:** Capacidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Plan de almacenamiento a 5 años con monitoreo de uso ≥ 80% dispara alerta.
  - Búsqueda FTS p95 ≤ 1 s sobre corpus de 100k documentos.
- **Trazabilidad:** → Diseño §Storage → Tarea T-CAP-02 → Test PT-CAP-02

---

## RNF-DISP — Disponibilidad / Fiabilidad

### RNF-DISP-01 — SLA de disponibilidad
- **Descripción:** Disponibilidad mensual ≥ 99,5% (≈ 3,6 h de caída/mes permitidas); excluye mantenimientos programados con aviso previo ≥ 48 h.
- **Categoría:** Fiabilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Uptime monitoring (Pingdom/UptimeRobot) sobre `/api/v1/salud` reporta ≥ 99,5% en ventana de 30 días.
- **Trazabilidad:** → Diseño §AltaDisponibilidad → Tarea T-DISP-01 → Test PT-DISP-01

### RNF-DISP-02 — Recuperación ante desastres (RTO/RPO)
- **Descripción:** RTO ≤ 4 horas, RPO ≤ 1 hora para la BD PostgreSQL.
- **Categoría:** Fiabilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - DRP documentado y probado en simulacro semestral.
  - Backup automático cada 1 h (PITR) verificado con restore de prueba mensual.
- **Trazabilidad:** → Diseño §Backup → Tarea T-DISP-02 → Test PT-DISP-02

---

## RNF-SEG — Seguridad

### RNF-SEG-01 — HTTPS obligatorio y cabeceras de seguridad
- **Descripción:** Todo el tráfico debe ser HTTPS (TLS 1.2+ mínimo, ideal TLS 1.3); cabeceras de seguridad obligatorias: `Strict-Transport-Security`, `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`.
- **Categoría:** Seguridad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Observatory Mozilla: score A+.
  - securityheaders.com: A+.
  - Pentest anual: 0 hallazgos críticos/altos.
- **Trazabilidad:** → Diseño §Seguridad → Tarea T-SEG-01 → Test PT-SEG-01

### RNF-SEG-02 — Autenticación robusta (panel)
- **Descripción:** El panel debe exigir autenticación con: contraseña ≥12 caracteres (NIST 800-63B), MFA TOTP obligatorio para roles `editor`, `aprobador`, `administrador`, `seguridad`; bloqueo tras 5 intentos fallidos por 15 min.
- **Categoría:** Seguridad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Penetration test: bypass de autenticación = 0.
  - Auditoría de contraseñas con `Have I Been Pwned` API en el alta.
- **Trazabilidad:** → Diseño §Auth → Tarea T-SEG-02 → Test PT-SEG-02

### RNF-SEG-03 — Rate limiting y protección DDoS
- **Descripción:** Rate limit por IP: 60 req/min en rutas públicas, 300 req/min en rutas autenticadas; 1000 req/h en login (Sanctum); protección DDoS a nivel de edge (Cloudflare o equivalente).
- **Categoría:** Seguridad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - k6 con 200 req/s desde misma IP: a partir del minuto 2, respuestas HTTP 429.
  - Test de integración con bot simulado: mitigación ≤ 10 s.
- **Trazabilidad:** → Diseño §RateLimit → Tarea T-SEG-03 → Test PT-SEG-03

---

## RNF-USAB — Usabilidad

### RNF-USAB-01 — SUS ≥ 80
- **Descripción:** El sitio público debe alcanzar un System Usability Scale ≥ 80 (percentil 75) en pruebas con ≥ 30 usuarios de la ciudadanía de Santa Marta.
- **Categoría:** Usabilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Estudio SUS trimestral con muestra representativa (género, edad, discapacidad, nivel digital).
- **Trazabilidad:** → Diseño §UX → Tarea T-USAB-01 → Test PT-USAB-01

### RNF-USAB-02 — Ancho de línea y tipografía
- **Descripción:** Líneas de texto entre 60-80 caracteres (`max-width: 65ch`); tamaño base de fuente ≥ 16 px; cuerpo en Verdana 400, títulos en Nunito Sans 600-800.
- **Categoría:** Usabilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Auditoría visual automatizada con `backstopjs` o `Playwright` comparando contra diseño Figma.
- **Trazabilidad:** → Tarea T-USAB-02 → Test PT-USAB-02

---

## RNF-ACES — Accesibilidad

### RNF-ACES-01 — WCAG 2.1 AA verificable
- **Descripción:** El sitio público debe cumplir WCAG 2.1 nivel AA verificable con auditoría Lighthouse ≥ 90 y axe-core 0 violaciones críticas/serias.
- **Categoría:** Accesibilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - axe-core (Playwright): 0 violaciones serias o críticas en todas las rutas principales.
  - Lighthouse Accessibility ≥ 95.
  - Auditoría manual anual por experto certificado.
- **Trazabilidad:** → Diseño §WCAG → Tarea T-ACES-01 → Test PT-ACES-01

### RNF-ACES-02 — Barra de accesibilidad
- **Descripción:** Barra fija con: contraste (4 modos), tamaño de letra (3 niveles), espaciado, modo lectura, ocultar imágenes, descripción de imágenes; preferencias persistentes en `localStorage`.
- **Categoría:** Accesibilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Pruebas con usuarios con discapacidad visual, auditiva, motriz y cognitiva.
- **Trazabilidad:** → Tarea T-ACES-02 → Test PT-ACES-02

---

## RNF-MANT — Mantenibilidad

### RNF-MANT-01 — Cobertura de tests ≥ 80% backend / ≥ 70% frontend
- **Descripción:** Cobertura de tests: backend Laravel ≥ 80% (Pest), frontend ≥ 70% (Vitest); CI bloquea merge si cae por debajo.
- **Categoría:** Mantenibilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Reporte de cobertura en CI Codecov/Coveralls.
  - Quality gate en CI: cobertura mínima.
- **Trazabilidad:** → Tarea T-MANT-01 → Test PT-MANT-01

---

## RNF-PORT — Portabilidad / Compatibilidad

### RNF-PORT-01 — Compatibilidad de navegadores
- **Descripción:** Soporte de los 2 últimos versiones de Chrome, Firefox, Safari, Edge; Safari iOS 16+ y Chrome Android 12+.
- **Categoría:** Compatibilidad
- **Prioridad:** Must
- **Criterio de aceptación:**
  - BrowserStack: pruebas cross-browser en smoke test suite.
  - CanIUse: 0 features experimentales en producción.
- **Trazabilidad:** → Tarea T-PORT-01 → Test PT-PORT-01

### RNF-PORT-02 — Diseño responsive
- **Descripción:** Diseño responsive con 6 breakpoints (XS <576, SM 576-768, MD 768-992, LG 992-1200, XL 1200-1400, XXL ≥1400); espaciado mínimo 24 px; menú hamburguesa < 768 px.
- **Categoría:** Compatibilidad
- **Prioridad:** Should
- **Criterio de aceptación:**
  - Playwright en viewports 375, 768, 1024, 1440 px: layouts validados.
- **Trazabilidad:** → Tarea T-PORT-02 → Test PT-PORT-02

---

## RNF-PD — Protección de Datos

### RNF-PD-01 — Cumplimiento Ley 1581/2012
- **Descripción:** El sistema debe cumplir la Ley 1581/2012 y Decreto 1377/2013: registro de tratamiento de datos, captura de consentimiento explícito para datos no esenciales, atención de derechos ARCO en ≤ 15 días hábiles.
- **Categoría:** Protección de datos
- **Prioridad:** Must
- **Criterio de aceptación:**
  - Auditoría interna anual con la SIC.
  - Procedimiento ARCO documentado y probado.
  - Encargado de protección de datos designado.
- **Trazabilidad:** → Diseño §ProteccionDatos → Tarea T-PD-01 → Test PT-PD-01

---

## Trazabilidad global

| RNF | Categoría ISO 25010 | Diseño | Tarea | Test |
|---|---|---|---|---|
| RNF-REND-01..03 | Rendimiento | §Caching, §RenderStrategy | T-REND-01..03 | PT-REND-01..03 |
| RNF-CAP-01..02 | Capacidad | §Escalabilidad, §Storage | T-CAP-01..02 | PT-CAP-01..02 |
| RNF-DISP-01..02 | Fiabilidad | §AltaDisponibilidad, §Backup | T-DISP-01..02 | PT-DISP-01..02 |
| RNF-SEG-01..03 | Seguridad | §Seguridad, §Auth, §RateLimit | T-SEG-01..03 | PT-SEG-01..03 |
| RNF-USAB-01..02 | Usabilidad | §UX | T-USAB-01..02 | PT-USAB-01..02 |
| RNF-ACES-01..02 | Accesibilidad | §WCAG | T-ACES-01..02 | PT-ACES-01..02 |
| RNF-MANT-01 | Mantenibilidad | §Calidad | T-MANT-01 | PT-MANT-01 |
| RNF-PORT-01..02 | Compatibilidad | §Compatibilidad | T-PORT-01..02 | PT-PORT-01..02 |
| RNF-PD-01 | Protección datos | §ProteccionDatos | T-PD-01 | PT-PD-01 |

> **Conteo verificado:** 18 RNF (objetivo ≥15 cumplido). Must: 17, Should: 1.

# Restricciones — Marco Normativo (RN) y Técnico (RT)

> **Restricciones** son condiciones impuestas externamente (normativa) o por decisión arquitectónica no negociable (técnica). No son requisitos a cumplir: son **límites del espacio de diseño**.
> **Trazabilidad:** cada restricción → 1 o más requisitos que la implementan → diseño → prueba.

---

## 1. Restricciones Normativas (RN)

### RN-01 — Sección "Transparencia y acceso a la información pública" como enlace identificado en la página de inicio
- **Fuente:** Ley 1712/2014 Art. 4; Decreto 103/2015 Art. 8.
- **Implementado por:** RF-01-007 (menú principal incluye el ítem en posición 2), RF-02-001 (subsecciones).
- **Verificación:** Lighthouse + Playwright inspecciona el `<nav>` del home.

### RN-02 — Estándares de la Resolución 1519/2020
- **Fuente:** Res. MinTIC 1519/2020 (Anexos 1, 2 y 3).
- **Componentes:**
  - **Anexo 1 — seguridad digital:** RNF-SEG-01, RNF-SEG-02, RNF-SEG-03.
  - **Anexo 2 — contenidos:** RF-02-001..030, RF-01-001..022.
  - **Anexo 3 — accesibilidad:** RNF-ACES-01, RNF-ACES-02.
- **Verificación:** tablero ITA interno automático (RF-02-029).

### RN-03 — Cinco instrumentos de gestión de la información
- **Fuente:** Ley 1712/2014 Art. 16-21.
- **Componentes:**
  - Registro de Activos de Información → tabla `activo_informacion` (módulo `_bd`).
  - Índice de Información Clasificada → tabla `informacion_clasificada`.
  - Esquema de Publicación → tabla `esquema_publicacion`.
  - Programa de Gestión Documental (PGD) → integración con AGN (PINAR).
  - Tablas de Retención Documental (TRD) → tabla `tabla_retencion_documental`.
- **Verificación:** 5 instrumentos publicados en `/transparencia/instrumentos-gestion`.

### RN-04 — Articulación con GOV.CO
- **Fuente:** Anexo Técnico 2 MinTIC; Decreto 2106/2019 Arts. 14-15.
- **Componentes:** Top bar, footer, kit UI (RF-01-001, RF-01-002, RF-01-005, RF-01-021), redireccionamiento con enmascaramiento (RF-01-022).
- **Verificación:** escaneo visual con Playwright + test de URL final tras redirección.

### RN-05 — Menú principal con secciones obligatorias
- **Fuente:** Lineamientos GEL.
- **Componentes:** RF-01-007.
- **Verificación:** inspección DOM del `<nav>`.

### RN-06 — Documentos auténticos e íntegros (hash SHA-256, versionado, retención)
- **Fuente:** Ley 1712/2014 Art. 4.
- **Componentes:** RF-02-021 (versionado), RF-02-024 (hash SHA-256), RF-02-030 (metadatos).
- **Verificación:** trazabilidad de hash + historial.

### RN-07 — Protección de datos personales (directorio)
- **Fuente:** Ley 1581/2012 Arts. 17-18; Decreto 1377/2013.
- **Componentes:** RNF-PD-01; RF-02-006 (directorio con minimización).
- **Verificación:** no se publica cédula, dirección personal, datos sensibles; solo correo institucional y extensión.

### RN-08 — Formatos abiertos y accesibles
- **Fuente:** Res. MinTIC 1519/2020 Anexo 2 (formatos); Ley 1712/2014 Art. 11.
- **Componentes:** RF-02-025, RF-02-030.
- **Verificación:** ≥90% documentos en CSV/XML/RDF/JSON/ODF/PDF-A.

### RN-09 — Menú Part y menú Transparenta obligatorios en primeras posiciones
- **Fuente:** MinTIC (posicionamiento en menú principal).
- **Componentes:** RF-01-007 (orden de menú), RF-02-001.
- **Verificación:** inspección DOM.

### RN-10 — Catálogo de Trámites SUIT
- **Fuente:** Decreto 2106/2019; Decreto 088/2022.
- **Componentes:** RF-02-018.
- **Verificación:** enlace funcional con filtro por código SUIT.

### RN-11 — Cumplimiento WCAG 2.1 AA
- **Fuente:** Res. MinTIC 1519/2020 Anexo 3.
- **Componentes:** RNF-ACES-01, RNF-ACES-02.
- **Verificación:** Lighthouse ≥ 90, axe-core 0 serias.

---

## 2. Restricciones Técnicas (RT)

### RT-01 — Backend expone TODOS los datos vía API REST
- **Justificación:** Arquitectura desacoplada obligatoria (ver §3 propuesta arquitectónica).
- **Implicación:** el frontend NUNCA accede a la BD ni a rutas internas de Laravel; la única superficie es `/api/v1/*`.
- **Verificación:** grep en `sitio/` y `panel/` buscando accesos directos a BD.

### RT-02 — JSON:API Resources nativos de Laravel 13
- **Justificación:** Stack fijado; serialización estándar, caching, sparse fieldsets.
- **Implicación:** no usar paquetes de terceros (jsonapi-php/laravel-json-api). Uso de `JsonResource` con extensiones JSON:API oficiales.
- **Verificación:** inspección del namespace `App\Http\Resources`.

### RT-03 — `<script setup lang="ts">` con macros tipadas
- **Justificación:** Vue 3 estándar (Composition API).
- **Implicación:** sin Options API; composables reutilizables; `defineProps<...>()` con tipos estrictos.
- **Verificación:** ESLint regla `vue/no-options-api`.

### RT-04 — BD normalizada ≥ 3FN
- **Justificación:** Calidad de datos, evitar anomalías de actualización.
- **Implicación:** documentar decisiones de normalización en `04-diseno-bd/normalizacion-1fn-2fn-3fn.md`.
- **Verificación:** auditoría `06-auditoria.md` con 13 chequeos.

### RT-05 — Versionado URL, rate limiting, OpenAPI 3.1
- **Justificación:** Buenas prácticas de API REST.
- **Implicación:** prefijo `/api/v1/` obligatorio; rate limit por IP; spec OpenAPI 3.1 en `05-especificaciones-api/openapi-3.1.yaml`; CI verifica drift.
- **Verificación:** CI ejecuta `contract:verificar` (diff entre spec y rutas).

### RT-06 — Migraciones y seeders idempotentes
- **Justificación:** DevOps reproducible.
- **Implicación:** todas las migraciones reversibles (`down()`), seeders re-ejecutables sin duplicados (uso de `updateOrCreate`).
- **Verificación:** CI ejecuta `migrate:fresh --seed` en BD efímera.

### RT-07 — CORS restringido al dominio del frontend
- **Justificación:** Seguridad.
- **Implicación:** `config/cors.php` con allowlist de `https://santamarta.gov.co`, `https://www.santamarta.gov.co`, `http://localhost:3000`, `http://localhost:3001` (panel).
- **Verificación:** test de integración con preflight OPTIONS.

### RT-08 — Toda entrada validada con Form Requests
- **Justificación:** Seguridad y consistencia.
- **Implicación:** cada endpoint crea o usa un Form Request con `rules()` y `authorize()`.
- **Verificación:** test unitario por Form Request.

### RT-09 — Prohibido SQL crudo sin binding
- **Justificación:** Seguridad (inyección SQL).
- **Implicación:** usar Eloquent o Query Builder; si es inevitable raw, usar bindings.
- **Verificación:** Larastan regla + CI grep.

### RT-10 — Accesibilidad WCAG 2.1 AA
- **Justificación:** RN-11.
- **Implicación:** verificación con Lighthouse ≥ 90; axe-core; pruebas manuales con usuarios.
- **Verificación:** CI con Playwright + axe-core.

### RT-11 — Autenticación Sanctum para el panel
- **Justificación:** Stack fijado; sesiones SPA con cookie HttpOnly + CSRF; opción de Bearer Token para integraciones.
- **Implicación:** `auth:sanctum` en rutas protegidas del panel.
- **Verificación:** test de integración de flujo login + middleware.

### RT-12 — Pruebas Pest (backend) y Vitest + Vue Test Utils (frontend)
- **Justificación:** Stack fijado.
- **Implicación:** sin PHPUnit "clásico" ni Jest; CI ejecuta ambos en paralelo.
- **Verificación:** revisión de `composer.json` y `package.json` del frontend.

---

## Resumen de restricciones

| Tipo | Total | Detalle |
|---|---|---|
| Normativas (RN) | 11 | Cumplimiento Ley 1712/2014, Res. 1519/2020, Decreto 2106/2019, Ley 1581/2012 |
| Técnicas (RT) | 12 | Stack Laravel 13 + Vue 3 + TS + JSON:API + OpenAPI + WCAG 2.1 AA |
| **Total** | **23** | |

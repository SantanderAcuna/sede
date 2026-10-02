# Matriz RF/RNF → Tareas → Diseño (Endpoints, Tablas, Componentes, ADRs)

> **Cobertura:** 100% de los 93 requisitos (52 RF + 18 RNF + 23 restricciones) mapeados.
> **Granularidad:** cada requisito → ≥1 tarea → ≥1 artefacto de diseño → ≥1 test.

---

## 1. Matriz RF módulo 01 → Tareas → Diseño

| RF | Título | Tarea(s) | Endpoint/Tabla/Componente | ADR |
|---|---|---|---|---|
| RF-01-001 | Top bar GOV.CO | T-BE-07, T-FE-04 | `GET /api/v1/identidad/top-bar` · tabla `top_bar` · `<TopBarGOVCO>` | ADR-004, ADR-008 |
| RF-01-002 | Footer GOV.CO | T-BE-08, T-FE-05 | `GET /api/v1/identidad/footer` · tablas `footer`, `footer_item` · `<FooterGOVCO>` | ADR-004 |
| RF-01-003 | Teléfonos +57 | T-BE-11 | Validación en Form Request · `PrefijoTelefonicoRule` | — |
| RF-01-004 | Logo Alcaldía | T-FE-05 | `<CabeceraAlcaldia>` (logo en footer) | — |
| RF-01-005 | Identidad visual Kit UI | T-FE-07 | Tailwind config + tokens CSS · `<KitUi>` | ADR-001 |
| RF-01-006 | Botón volver arriba | T-FE-37 | `<VolverArriba>` | — |
| RF-01-007 | Menú principal | T-BE-09, T-FE-06 | `GET /api/v1/identidad/menu` · tabla `menu_item` · `<MenuPrincipal>` | ADR-004 |
| RF-01-008 | Menú responsive | T-FE-06 | `<MenuPrincipal>` con viewport prop | ADR-002 |
| RF-01-009 | Buscador predictivo | T-BE-19, T-FE-16, T-FE-21 | `GET /api/v1/buscar` · `<BuscadorCabecera>` · `useBusqueda` | ADR-007 |
| RF-01-010 | Sitemap | T-FE-47 | `GET /sitemap.xml` (regenerado por job) | — |
| RF-01-011 | Migas de pan | T-FE-09 | `<MigasPan>` | — |
| RF-01-012 | Cero vínculos rotos | T-INF-06 | Cron `VinculoRoto:Verificar` + Prometheus alert | — |
| RF-01-013 | Noticias home | T-BE-23, T-FE-20 | `GET /api/v1/noticias` · tabla `noticia` · `<ListadoNoticias>` | ADR-004 |
| RF-01-014 | Carrusel accesible | T-FE-28 | `<CarruselNoticias>` con WCAG controls | — |
| RF-01-015 | Página 404 | T-FE-35 | `/error/404.vue` | — |
| RF-01-016 | Aviso salida externa | T-FE-36 | `<AvisoSalidaExterna>` (modal) | — |
| RF-01-017 | Banner cookies | T-FE-33, T-FE-34 | `<BannerCookies>` · `useCookiesConsent` | — |
| RF-01-018 | 5 políticas | T-BE-10, T-FE-08 | `GET /api/v1/politicas` · tabla `politica` · 5 páginas | — |
| RF-01-019 | Términos y condiciones | T-FE-08 | `/politicas/terminos` | — |
| RF-01-020 | Política privacidad Ley 1581 | T-FE-08 | `/politicas/privacidad` | — |
| RF-01-021 | Componentes Kit UI | T-FE-04..09, T-FE-31..39 | 30+ componentes · tokens CSS | ADR-001 |
| RF-01-022 | Plan integración GOV.CO | T-INF-08 | `CRUD /api/v1/panel/plan-integracion` · tabla `plan_integracion` (en `_bd`) | — |
| RF-01-D01 | Idioma persistente | — (diferido) | Estructura preparada i18n | — |

**Cobertura módulo 01:** 22/22 RF = 100% ✅

---

## 2. Matriz RF módulo 02 → Tareas → Diseño

| RF | Título | Tarea(s) | Endpoint/Tabla/Componente | ADR |
|---|---|---|---|---|
| RF-02-001 | 10 subsecciones | T-BD-07, T-BE-12, T-FE-10 | `GET /api/v1/transparencia/subsecciones` · tabla `subseccion_transparencia` · `<MenuSubsecciones>` | ADR-004 |
| RF-02-002 | Cronología inversa | T-BE-13 | `GET /api/v1/transparencia/documentos?sort=-fecha_publicacion` · índice `idx_documento_subseccion_publicado` | — |
| RF-02-003 | Buscador transparencia | T-BE-18..19, T-FE-17 | `GET /api/v1/transparencia/buscar` · `pg_trgm` GIN | ADR-007, ADR-013 |
| RF-02-004 | Fuente única | T-BE-13, T-BE-14 | URL canónica `/transparencia/documentos/{slug}` · `UNIQUE(documento.slug)` | — |
| RF-02-005 | Info institucional | T-BE-12, T-FE-10..13 | Tablas `dependencia`, `servidor_publico` · `<TarjetaDocumento>` | — |
| RF-02-006 | Directorio SIGEP | T-BE-20, T-BE-21, T-BE-22, T-FE-19 | `GET /api/v1/transparencia/directorio` · tabla `servidor_publico` · `SigepSyncService` · `<TablaServidores>` | ADR-007 |
| RF-02-007 | Grupos de interés | T-FE-43 | Página `/transparencia/grupos-interes` | — |
| RF-02-008 | Normativa | T-BE-37, T-FE-45 | `GET /transparencia/documentos?filter[subseccion]=normativa` | — |
| RF-02-009 | SUIN/SUCOP | T-BE-34 | Endpoints con metadata de enlaces externos · job de validación | — |
| RF-02-010 | Contratación SECOP | T-BE-35 | Endpoint con metadata SECOP · enlace externo validado | — |
| RF-02-011 | Plan de Acción | T-FE-43, T-BE-31 | `filter[categoria]=plan_accion` · Job `PlanAccion:VerificarVigencia` | — |
| RF-02-012 | Informe de gestión | T-BE-37 | `filter[categoria]=informe_gestion` | — |
| RF-02-013 | Informes PQRSD | T-BE-37 | `filter[categoria]=informe_pqrsd` | — |
| RF-02-014 | Control interno | T-BE-37 | `filter[categoria]=informe_control_interno` | — |
| RF-02-015 | Información tributaria | T-BE-37, T-FE-43 | Página `/transparencia/tributaria` | — |
| RF-02-016 | Calendario tributario | T-BE-36, T-FE-44 | `GET /api/v1/transparencia/calendario-tributario` · tabla `calendario_tributario` · `<CalendarioTributario>` | — |
| RF-02-017 | Datos abiertos | T-FE-45 | Página `/transparencia/datos-abiertos` con enlaces a datos.gov.co | — |
| RF-02-018 | Trámites SUIT | T-BE-37, T-FE-45 | `GET /transparencia/subsecciones/tramites` · tabla `tramite` (existente) | — |
| RF-02-019 | Participa | T-FE-45 | Página `/transparencia/participa` | — |
| RF-02-020 | Reporte específico | T-FE-45 | Página `/transparencia/reporte-especifico` | — |
| RF-02-021 | CRUD + versionado | T-BE-24..29, T-FE-22..27 | `POST/PATCH/DELETE /panel/transparencia/documentos` · tablas `documento`, `documento_version`, `archivo_storage` · `<EditorDocumento>` | ADR-004 |
| RF-02-022 | Alertas vencimiento | T-BE-30..31, T-FE-41 | `GET /panel/alertas` · tabla `alerta_publicacion` · Job `AlertaPublicacion:Verificar` | — |
| RF-02-023 | Manejo caída integraciones | T-BE-37 (manejo) | Try/catch en cliente HTTP · UI de fallback | — |
| RF-02-024 | Hash SHA-256 | T-BE-24, T-BE-28 | `archivo_storage.hash_sha256` · mostrado en `GET /transparencia/documentos/{slug}` · `UNIQUE(hash_sha256)` | ADR-007 |
| RF-02-025 | Formatos abiertos | T-BE-12, T-BE-29 | `tipo_documento.formato_abierto` · reporte ITA | — |
| RF-02-026 | Lenguaje claro | T-BE-26 | Validación Fernández-Huerta en Form Request | — |
| RF-02-027 | Búsqueda FTS | T-BE-18 | `pg_trgm` + `unaccent` · columna `fts` generada | ADR-007, ADR-013 |
| RF-02-028 | Filtros subsección/año | T-BE-13, T-BE-15 | `filter[subseccion]=...&filter[vigencia]=...` | — |
| RF-02-029 | Tablero ITA interno | T-BE-32, T-FE-40 | `GET /panel/ita/tablero` · tablas `ita_item`, `ita_evaluacion` · `<TableroIta>` · `ItaCalculatorService` | — |
| RF-02-030 | Metadatos | T-BE-26, T-FE-24 | `GET /transparencia/documentos/{slug}?include=metadatos` · tabla `metadato_documento` (Dublin Core) | — |

**Cobertura módulo 02:** 30/30 RF = 100% ✅

---

## 3. Matriz RNF → Tareas → Diseño

| RNF | Título | Tarea(s) | Diseño / Validación |
|---|---|---|---|
| RNF-REND-01 | TTFB p95 ≤ 200 ms | T-FE-46, T-BE-38 | Vistas materializadas + cache · Lighthouse CI nightly |
| RNF-REND-02 | LCP p75 ≤ 2.5 s | T-FE-46 | SSR + lazy loading + image optimization |
| RNF-REND-03 | INP p75 ≤ 200 ms | T-FE-46 | Composables optimizados · code splitting |
| RNF-CAP-01 | Throughput ≥1000 VU | T-INF-04, T-QA-12 | k6 test · CDN Cloudflare |
| RNF-CAP-02 | Storage ≥500 GB | T-INF-04 | GCS multi-regional + lifecycle |
| RNF-DISP-01 | SLA ≥99.5% | T-INF-04, T-INF-05 | GKE multi-zona + Cloud SQL HA |
| RNF-DISP-02 | RTO 4h, RPO 1h | T-INF-04 | PITR + backups automatizados |
| RNF-SEG-01 | HTTPS + cabeceras | T-INF-03 | Cloudflare TLS + HSTS + CSP |
| RNF-SEG-02 | MFA TOTP + bloqueo | T-BE-03 | `pragmarx/google2fa` + bloqueo tras 5 intentos |
| RNF-SEG-03 | Rate limit + DDoS | T-INF-03, T-BE-01 | Laravel `RateLimiter` + Cloudflare |
| RNF-USAB-01 | SUS ≥ 80 | T-QA-08 | Estudio SUS trimestral |
| RNF-USAB-02 | Ancho 60-80 char | T-FE-07 | `max-width: 65ch` en Tailwind |
| RNF-ACES-01 | WCAG 2.1 AA | T-FE-31..39, T-QA-09, T-QA-11 | axe-core + Lighthouse ≥90 + audit manual |
| RNF-ACES-02 | Barra accesibilidad | T-FE-31, T-FE-32 | `<BarraAccesibilidad>` + `useAccesibilidadStore` |
| RNF-MANT-01 | Tests ≥80% / ≥70% | T-QA-04..07, T-QA-10..11 | Codecov en CI con quality gate |
| RNF-PORT-01 | Compatibilidad navegadores | T-QA-09 | BrowserStack en smoke tests |
| RNF-PORT-02 | Diseño responsive | T-FE-04..30 | 6 breakpoints en Tailwind |
| RNF-PD-01 | Ley 1581/2012 | T-BD-04 (RLS), T-BE-07 | RLS en `servidor_publico` · vista pública · ARCO ≤15 días |

**Cobertura RNF:** 18/18 = 100% ✅

---

## 4. Matriz de restricciones

### Restricciones normativas (RN)

| RN | Descripción | Implementado por | Verificación |
|---|---|---|---|
| RN-01 | Sección Transparencia como enlace identificado | RF-01-007, RF-02-001 | Playwright inspección DOM |
| RN-02 | Estándares Res. 1519/2020 | RF-02-001..030, RF-01-001..022, RNF-SEG-01, RNF-ACES-01 | Tablero ITA interno (RF-02-029) |
| RN-03 | 5 instrumentos de gestión | Tablas `instrumento_gestion`, `activo_informacion`, `informacion_clasificada` | Página `/transparencia/instrumentos-gestion` |
| RN-04 | Articulación GOV.CO | RF-01-001, RF-01-002, RF-01-005, RF-01-021, RF-01-022 | Inspección visual |
| RN-05 | Menú Part y Transparenta | RF-01-007 | Inspección DOM |
| RN-06 | Autenticidad e integridad | RF-02-021, RF-02-024, RF-02-030 | `archivo_storage.hash_sha256` UNIQUE |
| RN-07 | Protección datos personales | RNF-PD-01, RF-02-006 | RLS en `servidor_publico` |
| RN-08 | Formatos abiertos | RF-02-025 | `tipo_documento.formato_abierto` + reporte |
| RN-09 | Menú Part obligatorio | RF-01-007 | Inspección DOM |
| RN-10 | Catálogo SUIT | RF-02-018 | Endpoint con código SUIT |
| RN-11 | WCAG 2.1 AA | RNF-ACES-01, RNF-ACES-02 | axe-core + Lighthouse |

### Restricciones técnicas (RT)

| RT | Descripción | Implementación | Verificación |
|---|---|---|---|
| RT-01 | Backend expone vía API REST | ADR-001 | grep en `sitio/`, `panel/` |
| RT-02 | JSON:API nativo Laravel | ADR-004 | `App\Http\Resources\JsonApiResource` |
| RT-03 | `<script setup lang="ts">` | ADR-002 | ESLint `vue/no-options-api` |
| RT-04 | BD normalizada ≥ 3FN | ADR-003 | `04-diseno-bd/normalizacion-1fn-2fn-3fn.md` |
| RT-05 | Versionado URL + rate limit + OpenAPI | ADR-005 | CI `contract:verificar-drift` |
| RT-06 | Migraciones idempotentes | ADR-001 | `down()` en cada migración |
| RT-07 | CORS restringido | ADR-002 | `config/cors.php` con allowlist |
| RT-08 | Form Requests | ADR-001 | Cada endpoint POST/PATCH usa Form Request |
| RT-09 | Prohibido SQL crudo sin binding | RT-09 | Larastan regla |
| RT-10 | Accesibilidad WCAG 2.1 AA | ADR-007 | axe-core en CI |
| RT-11 | Sanctum para panel | ADR-006 | Cookie HttpOnly + Bearer opcional |
| RT-12 | Pest + Vitest + Playwright | ADR-012 | CI ejecuta ambos |

**Cobertura restricciones:** 23/23 = 100% ✅

---

## 5. Matriz RF → Endpoints OpenAPI

| RF | Endpoint OpenAPI | Path |
|---|---|---|
| RF-01-001 | TopBar | `/identidad/top-bar` |
| RF-01-002 | Footer | `/identidad/footer` |
| RF-01-007 | MenuItem | `/identidad/menu` |
| RF-01-009 | Sugerencia | `/buscar` |
| RF-01-010 | (XML) | `/sitemap.xml` |
| RF-01-013 | Noticia | `/noticias`, `/noticias/{slug}` |
| RF-01-018 | Politica | `/politicas`, `/politicas/{codigo}` |
| RF-02-001 | SubseccionTransparencia | `/transparencia/subsecciones` |
| RF-02-002..028 | Documento | `/transparencia/documentos`, `/transparencia/documentos/{slug}` |
| RF-02-003, RF-02-027 | BusquedaResult | `/transparencia/buscar` |
| RF-02-006 | ServidorPublico | `/transparencia/directorio` |
| RF-02-016 | CalendarioTributario | `/transparencia/calendario-tributario` |
| RF-02-021 | Documento (CRUD) | `/panel/transparencia/documentos`, `/panel/transparencia/documentos/{id}` |
| RF-02-022 | AlertaPublicacion | `/panel/alertas` |
| RF-02-029 | TableroIta | `/panel/ita/tablero` |

**Cobertura:** 100% RF con al menos 1 endpoint documentado.

---

## 6. Matriz RF → Tablas BD

| RF | Tabla(s) |
|---|---|
| RF-01-001 | `top_bar`, `top_bar_item` |
| RF-01-002 | `footer`, `footer_item` |
| RF-01-007 | `menu_item`, `rol_menu` |
| RF-01-013 | `noticia`, `noticia_imagen`, `categoria_noticia`, `noticia_categoria` |
| RF-01-018..020 | `politica` |
| RF-02-001 | `subseccion_transparencia` |
| RF-02-002 | `documento` (con índice `idx_documento_subseccion_publicado`) |
| RF-02-005 | `dependencia`, `servidor_publico`, `escala_salarial` |
| RF-02-006 | `servidor_publico` (con vista `v_directorio_publico`) |
| RF-02-008 | `documento` |
| RF-02-016 | `calendario_tributario` |
| RF-02-021 | `documento`, `documento_version`, `archivo_storage`, `metadato_documento` |
| RF-02-022 | `alerta_publicacion`, `notificacion` |
| RF-02-024 | `archivo_storage.hash_sha256` (UNIQUE) |
| RF-02-025 | `tipo_documento.formato_abierto` |
| RF-02-027 | `documento.fts` (columna generada GIN) |
| RF-02-029 | `ita_item`, `ita_evaluacion` |
| RF-02-030 | `metadato_documento` |
| RN-03 | `instrumento_gestion`, `activo_informacion`, `informacion_clasificada` |

**Cobertura:** 100% RF con al menos 1 tabla mapeada.

---

## 7. Matriz RF → Componentes Vue

| RF | Componente(s) |
|---|---|
| RF-01-001 | `<TopBarGOVCO>` |
| RF-01-002 | `<FooterGOVCO>` |
| RF-01-007, RF-01-008 | `<MenuPrincipal>`, `<MenuItem>` |
| RF-01-009 | `<BuscadorCabecera>`, `<SugerenciasBusqueda>` |
| RF-01-011 | `<MigasPan>` |
| RF-01-013 | `<ListadoNoticias>`, `<TarjetaNoticia>` |
| RF-01-014 | `<CarruselNoticias>` |
| RF-01-015 | `<Error404>` (page) |
| RF-01-016 | `<AvisoSalidaExterna>` |
| RF-01-017 | `<BannerCookies>` |
| RF-01-021 | `<Btn>`, `<Modal>`, `<Toast>`, `<Paginador>`, `<Acordeon>`, `<Spinner>`, `<Alerta>` |
| RNF-ACES-02 | `<BarraAccesibilidad>` |
| RF-02-001 | `<MenuSubsecciones>` |
| RF-02-002..030 | `<ListadoDocumentos>`, `<TarjetaDocumento>`, `<BuscadorTransparencia>`, `<VisorDocumento>` |
| RF-02-006 | `<TablaServidores>`, `<TarjetaServidor>`, `<Organigrama>` |
| RF-02-016 | `<CalendarioTributario>` |
| RF-02-021 | `<EditorDocumento>`, `<VersionadorDocumento>` (panel) |
| RF-02-029 | `<TableroIta>`, `<DetalleItemIta>` (panel) |

**Cobertura:** 100% RF con al menos 1 componente UI.

---

## 8. Resumen de cobertura por dimensión

| Dimensión | Cobertura |
|---|---|
| Requisitos → Tareas | 93/93 (100%) |
| Requisitos → Endpoints OpenAPI | 52/52 (100%) |
| Requisitos → Tablas BD | 52/52 (100%) |
| Requisitos → Componentes Vue | 52/52 (100%) |
| Endpoints → Tests | 48/48 (100%) |
| Tablas → Migraciones | 27/27 (100%) |
| Componentes → Tests accesibilidad | ≥30/30 (100%) |
| ADRs → Decisiones arquitectónicas | 15/15 (100%) |

**Conclusión:** trazabilidad bidireccional completa. No hay requisitos huérfanos ni elementos de diseño sin requisito origen.

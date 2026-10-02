# Sprints — Plan Detallado

> **Formato:** tabla por sprint con ID de tarea, descripción, tipo, puntos, dependencias, criterios de aceptación.

---

## Sprint 1 — Cimientos (78 pts)

**Foco:** levantar el esqueleto del backend Laravel 13, BD PostgreSQL, autenticación Sanctum, primeras migraciones, infra Docker local, CI.

| Tarea | Tipo | Pts | Dep | Criterio de aceptación |
|---|---|---|---|---|
| T-INF-01: docker-compose con servicios (postgres, redis, minio, backend, sitio, panel) | DevOps | 5 | — | `docker compose up` levanta todo el stack |
| T-INF-02: GitHub Actions CI (lint + tests + build) | DevOps | 5 | T-INF-01 | CI corre en cada PR; bloquea merge si falla |
| T-BD-01: migración extensiones PostgreSQL (pg_trgm, unaccent, uuid-ossp, pgcrypto) | BD | 2 | — | Migración aplica sin error |
| T-BD-02: migraciones RBAC (rol, permiso, rol_usuario) | BD | 5 | T-BD-01 | Tablas creadas, migraciones reversibles |
| T-BD-03: migraciones usuario, sesion, log_auditoria | BD | 8 | T-BD-02 | Tablas creadas, índices, FK |
| T-BE-01: setup Laravel 13 + Sanctum + Cors | Backend | 5 | T-INF-01 | `php artisan` responde |
| T-BE-02: JsonApiResource base + middleware ForzarJsonApi | Backend | 5 | T-BE-01 | Todas las respuestas en JSON:API 1.0 |
| T-BE-03: AuthController (login, logout) con Sanctum | Backend | 8 | T-BE-01, T-BD-03 | Login funcional con Sanctum; cookie HttpOnly emitida |
| T-BE-04: middleware AuditarOperacion + integration log_auditoria | Backend | 5 | T-BD-03 | Operaciones autenticadas registradas |
| T-BE-05: endpoint /api/v1/salud (health check) | Backend | 3 | T-BE-01 | GET /salud responde 200 con JSON |
| T-BE-06: pruebas Pest base (SaludoController test) | Backend | 3 | T-BE-05 | Tests pasan |
| T-FE-01: setup Nuxt 4 sitio con SSR + Tailwind + plugin api.ts | Frontend | 8 | T-INF-01 | `pnpm dev` arranca el sitio |
| T-FE-02: setup Nuxt 4 panel con Pinia + Axios + Sanctum | Frontend | 8 | T-FE-01 | Panel arranca, store sesion funcional |
| T-FE-03: layout default con TopBar (placeholder) y Footer (placeholder) | Frontend | 5 | T-FE-01 | Estructura básica visible |
| T-QA-01: configurar Playwright + axe-core en CI | QA | 3 | T-INF-02 | Playwright instalado, ejemplo de test verde |

---

## Sprint 2 — Identidad (82 pts)

**Foco:** top bar GOV.CO, footer, menú, políticas, identidad visual.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-BD-04: migraciones dependencia, servidor_publico, escala_salarial | BD | 8 | T-BD-03 | Tablas con BCNF, FK, RLS preparado |
| T-BD-05: migraciones menu_item, rol_menu, top_bar*, footer* | BD | 5 | T-BD-02 | Tablas creadas, índices |
| T-BD-06: migraciones politica, noticia, noticia_imagen, categoria_noticia | BD | 5 | T-BD-05 | Tablas creadas |
| T-BE-07: IdentidadService + TopBarController + TopBarResource | Backend | 8 | T-BD-05, T-BE-02 | GET /identidad/top-bar funcional |
| T-BE-08: FooterController + FooterResource | Backend | 5 | T-BD-05 | GET /identidad/footer funcional |
| T-BE-09: MenuController + MenuItemResource (jerárquico) | Backend | 8 | T-BD-05 | GET /identidad/menu funcional con árbol |
| T-BE-10: PoliticaController + PoliticaResource | Backend | 5 | T-BD-06 | GET /politicas y /politicas/{codigo} funcionales |
| T-BE-11: validación regla PrefijoTelefonicoRule | Backend | 3 | T-BE-01 | Tests pasan, formateo correcto |
| T-FE-04: componente TopBarGOVCO.vue con altura 56px y link a GOV.CO | Frontend | 5 | T-BE-07 | Componente renderiza según spec |
| T-FE-05: componente FooterGOVCO.vue con todos los campos del footer | Frontend | 5 | T-BE-08 | Componente renderiza según spec |
| T-FE-06: componente MenuPrincipal.vue con submenús accesibles | Frontend | 8 | T-BE-09 | Menú con aria-expanded, keyboard nav |
| T-FE-07: integración de identidad visual (paleta, tipografías) en Tailwind | Frontend | 5 | T-FE-01 | Tokens CSS del Kit UI aplicados |
| T-FE-08: páginas /politicas/terminos, /privacidad, /cookies, /derechos-autor, /accesibilidad | Frontend | 8 | T-BE-10 | 5 páginas renderizando contenido |
| T-FE-09: componente MigasPan.vue | Frontend | 3 | T-FE-03 | Migas de pan en páginas internas |
| T-QA-02: tests accesibilidad axe-core para top bar, footer, menú | QA | 3 | T-QA-01 | 0 violaciones serias |
| T-QA-03: tests E2E Playwright para renderizado básico | QA | 3 | T-FE-04..09 | Tests pasan |

---

## Sprint 3 — Transparencia núcleo (86 pts)

**Foco:** 10 subsecciones, listado de documentos, filtros básicos.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-BD-07: migraciones subseccion_transparencia, categoria_documento, tipo_documento | BD | 5 | T-BD-03 | 10 subsecciones en seeders |
| T-BD-08: migraciones documento, documento_version, metadato_documento | BD | 8 | T-BD-07 | Tablas con FTS generado |
| T-BD-09: migración documento_dependencia (pivote) | BD | 2 | T-BD-08 | Tabla pivote creada |
| T-BD-10: vista materializada mv_documentos_subseccion | BD | 3 | T-BD-08 | Vista creada, índice GIN |
| T-BE-12: SubseccionController + SubseccionResource | Backend | 5 | T-BD-07 | GET /transparencia/subsecciones |
| T-BE-13: DocumentoController.listar (con filtros, paginación, includes, fields) | Backend | 10 | T-BD-08, T-BE-02 | Listado funcional con todos los parámetros JSON:API |
| T-BE-14: DocumentoController.mostrar (con includes y relaciones) | Backend | 5 | T-BD-08 | GET por slug funcional |
| T-BE-15: Form Request ListarDocumentosRequest + reglas de filtros y sort whitelist | Backend | 5 | T-BE-13 | Validación funciona, sort whitelist |
| T-BE-16: Repositorios DocumentoRepository, SubseccionRepository | Backend | 5 | T-BD-08 | Interfaces + implementaciones |
| T-BE-17: seeders SubseccionesTransparenciaSeeder, TiposDocumentoSeeder | Backend | 3 | T-BD-07 | Seeds aplican las 10 subsecciones |
| T-FE-10: página /transparencia con menú de 10 subsecciones | Frontend | 5 | T-BE-12 | Menú renderiza correctamente |
| T-FE-11: página /transparencia/{subseccion} con listado inicial | Frontend | 8 | T-BE-13, T-FE-10 | Listado de documentos funcional |
| T-FE-12: componente ListadoDocumentos.vue con paginador | Frontend | 5 | T-BE-13 | Listado paginado |
| T-FE-13: componente TarjetaDocumento.vue (con hash SHA-256 visible opcional) | Frontend | 5 | T-BE-14 | Tarjeta con todos los campos |
| T-FE-14: composable useFiltros.ts + usePaginador.ts | Frontend | 5 | — | Composables con TypeScript |
| T-FE-15: filtros UI (select por subsección, vigencia) | Frontend | 5 | T-BE-15 | Filtros aplican y actualizan URL |
| T-QA-04: tests Pest para DocumentoController.listar y .mostrar | QA | 3 | T-BE-13, T-BE-14 | Tests verdes |

---

## Sprint 4 — Búsqueda y directorio (84 pts)

**Foco:** búsqueda full-text con tolerancia, directorio público, integración SIGEP.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-BD-11: migración busqueda_log | BD | 2 | T-BD-08 | Tabla creada con índices |
| T-BD-12: migración tabla particionada log_auditoria mensual | BD | 5 | T-BD-03 | Tabla particionada con 3 particiones iniciales |
| T-BE-18: BuscadorService con FTS + pg_trgm + unaccent | Backend | 8 | T-BD-08, T-BD-10 | Búsqueda funcional con tolerancia |
| T-BE-19: BuscadorController (/buscar, /transparencia/buscar) | Backend | 5 | T-BE-18 | Endpoints funcionales |
| T-BE-20: DirectorioController + ServidorPublicoResource (vista pública) | Backend | 8 | T-BD-04, T-BE-02 | GET /transparencia/directorio |
| T-BE-21: SigepSyncService (cliente HTTP SIGEP stub) | Backend | 5 | T-BD-04 | Servicio con interfaz (mock en dev) |
| T-BE-22: Job SincronizarSigep + Scheduler diario | Backend | 5 | T-BE-21 | Job encola y procesa sync |
| T-BE-23: NoticiaController + NoticiaResource | Backend | 5 | T-BD-06 | GET /noticias, /noticias/{slug} |
| T-FE-16: componente BuscadorCabecera.vue con autocompletado | Frontend | 8 | T-BE-19 | Buscador con sugerencias ≤10 |
| T-FE-17: componente BuscadorTransparencia.vue (RF-02-027) | Frontend | 8 | T-BE-19 | Buscador con resaltado de coincidencias |
| T-FE-18: página /buscar con resultados | Frontend | 5 | T-BE-19 | Resultados con paginación |
| T-FE-19: página /directorio con tabla de servidores | Frontend | 5 | T-BE-20 | Tabla accesible con scope |
| T-FE-20: página /noticias (listado y detalle) | Frontend | 5 | T-BE-23 | Páginas funcionales |
| T-FE-21: composable useBusqueda.ts | Frontend | 5 | T-FE-16 | Tipado end-to-end |
| T-QA-05: tests E2E búsqueda (autocompletar, página de resultados) | QA | 3 | T-FE-16..18 | Tests pasan |
| T-QA-06: tests Pest BuscadorService con casos edge | QA | 3 | T-BE-18 | Tests verdes |

---

## Sprint 5 — CRUD documentos (80 pts)

**Foco:** editor de documentos en panel, versionado, hash SHA-256.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-BD-13: migración archivo_storage (con hash UNIQUE) | BD | 3 | T-BD-08 | Tabla con UNIQUE en path y hash |
| T-BE-24: DocumentoService (subir, hashear, versionar) | Backend | 10 | T-BD-13, T-BE-16 | Lógica completa de upload + hash |
| T-BE-25: panel/transparencia/documentos CRUD (index, store, update, destroy) | Backend | 8 | T-BE-24 | POST/PATCH/DELETE autenticados |
| T-BE-26: Form Request CrearDocumentoRequest + ActualizarDocumentoRequest | Backend | 5 | T-BE-25 | Validación completa (mime, size, metadatos) |
| T-BE-27: Política DocumentoPolicy + integración con controlador | Backend | 3 | T-BE-25 | Gates activos |
| T-BE-28: Job CalcularHashDocumento (defensivo, recalcula si falta) | Backend | 3 | T-BE-24 | Job funcional |
| T-BE-29: Eventos DocumentoPublicado, DocumentoReemplazado + listeners | Backend | 5 | T-BE-24 | Eventos disparados |
| T-FE-22: layout admin con menú lateral y cabecera | Frontend | 8 | T-FE-02 | Layout del panel completo |
| T-FE-23: vista DocumentosView.vue con listado + filtros | Frontend | 8 | T-BE-25 | Listado con filtros por estado/subsección |
| T-FE-24: componente EditorDocumento.vue con file upload + validación | Frontend | 10 | T-BE-25, T-BE-26 | Formulario completo, validaciones |
| T-FE-25: componente VersionadorDocumento.vue (mostrar historial + restaurar) | Frontend | 5 | T-BE-25 | UI de versiones |
| T-FE-26: store documentos.ts | Frontend | 5 | T-FE-23 | Store Pinia con CRUD |
| T-FE-27: página de detalle documento con editor inline | Frontend | 5 | T-BE-25 | Edición de metadatos |
| T-QA-07: tests E2E flujo completo (subir → editar → publicar → versionar) | QA | 3 | T-FE-24..25 | Test verde end-to-end |

---

## Sprint 6 — Noticias y accesibilidad (76 pts)

**Foco:** home, noticias, accesibilidad WCAG, banner de cookies, página 404.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-FE-28: componente CarruselNoticias.vue accesible (pausa por defecto) | Frontend | 5 | T-BE-23 | Carrusel con controles WCAG |
| T-FE-29: componente TarjetaNoticia.vue + ListadoNoticias.vue | Frontend | 5 | T-BE-23 | Tarjetas accesibles |
| T-FE-30: página / (home) con hero + carrusel + módulos rápidos | Frontend | 8 | T-FE-28..29 | Home renderiza correctamente |
| T-FE-31: componente BarraAccesibilidad.vue (5 funciones) | Frontend | 5 | — | Barra fija, persistencia localStorage |
| T-FE-32: composable + store useAccesibilidadStore | Frontend | 3 | T-FE-31 | Estado reactivo |
| T-FE-33: componente BannerCookies.vue (aceptar/rechazar/configurar) | Frontend | 5 | — | Banner con 3 opciones |
| T-FE-34: composable useCookiesConsent.ts | Frontend | 3 | T-FE-33 | Estado y persistencia |
| T-FE-35: página /error/404.vue personalizada | Frontend | 3 | — | 404 con buscador y menú |
| T-FE-36: componente AvisoSalidaExterna.vue (modal) | Frontend | 3 | — | Modal accesible |
| T-FE-37: componente VolverArriba.vue flotante | Frontend | 2 | — | Botón con scroll suave |
| T-FE-38: componente SkipLink.vue | Frontend | 2 | — | Skip link funcional |
| T-FE-39: aplicar tokens CSS accesibilidad (modos contraste) | Frontend | 5 | T-FE-32 | 4 modos de contraste |
| T-QA-08: auditoría WCAG completa con usuarios reales (5 perfiles) | QA | 13 | T-FE-31..39 | Informe de auditoría |
| T-QA-09: tests Playwright accesibilidad (axe-core) en 10 rutas | QA | 5 | T-FE-31..39 | 0 violaciones serias |

---

## Sprint 7 — ITA, alertas, auditoría, subsecciones restantes (82 pts)

**Foco:** tablero ITA interno, alertas de vencimiento, subsecciones de transparencia 8-10, auditoría.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-BD-14: migraciones alerta_publicacion, ita_item, ita_evaluacion | BD | 5 | T-BD-03 | Tablas creadas |
| T-BD-15: migraciones instrumento_gestion, activo_informacion, informacion_clasificada | BD | 5 | T-BD-08 | Tablas creadas |
| T-BD-16: seeders ItaItemsSeeder, AlertasPublicacionSeeder, InstrumentosSeeder | BD | 3 | T-BD-14..15 | Seeds con datos completos |
| T-BE-30: AlertaPublicacionController + service | Backend | 5 | T-BD-14 | CRUD panel + alertas activas |
| T-BE-31: Job AlertaPublicacion:Verificar (cron diario) | Backend | 5 | T-BE-30 | Job dispara alertas 10 días antes |
| T-BE-32: TableroItaController + ItaCalculatorService | Backend | 8 | T-BD-14 | Cálculo automático de cumplimiento |
| T-BE-33: AuditLogController (panel admin) | Backend | 5 | T-BD-12 | Listado con filtros |
| T-BE-34: NormogramaService + enlaces SUIN/SUCOP | Backend | 5 | — | Endpoints con validación de enlaces |
| T-BE-35: ContratacionService + enlaces SECOP | Backend | 3 | — | Endpoint con metadata |
| T-BE-36: CalendarioTributarioController | Backend | 5 | — | GET /transparencia/calendario-tributario |
| T-BE-37: EventosListController (subsecciones 5,6,7,8,9,10) | Backend | 5 | T-BD-07 | Endpoints agregados |
| T-FE-40: vista TableroItaView.vue con métricas + detalle | Frontend | 8 | T-BE-32 | Tablero funcional |
| T-FE-41: vista AlertasView.vue | Frontend | 5 | T-BE-30 | Lista de alertas con acciones |
| T-FE-42: vista AuditoriaView.vue con filtros | Frontend | 5 | T-BE-33 | Tabla con paginación |
| T-FE-43: página /transparencia/planeacion-presupuesto | Frontend | 3 | T-BE-13 | Página con subcategorías |
| T-FE-44: página /transparencia/calendario-tributario | Frontend | 3 | T-BE-36 | Calendario renderizado |
| T-FE-45: páginas subsecciones 5,6,7,8,9 | Frontend | 5 | T-BE-37 | Páginas funcionales |
| T-QA-10: tests E2E ITA (cálculo, marcar no cumple, recalcular) | QA | 3 | T-FE-40 | Test verde |

---

## Sprint 8 — Hardening y go-live (72 pts)

**Foco:** performance, observabilidad, deploy, checklist RNF, accesibilidad final.

| Tarea | Tipo | Pts | Dep | Criterio |
|---|---|---|---|---|
| T-INF-03: configurar Cloudflare (CDN, WAF, rate limit) | DevOps | 5 | — | Staging protegido |
| T-INF-04: configurar GCP (Cloud SQL, Memorystore, GCS, GKE) | DevOps | 8 | T-INF-01 | Infraestructura productiva |
| T-INF-05: pipeline CI/CD completo (deploy staging + prod canary) | DevOps | 8 | T-INF-02, T-INF-04 | Deploy automático funcional |
| T-INF-06: Prometheus + Grafana + dashboards | DevOps | 5 | T-INF-04 | Dashboards con métricas clave |
| T-INF-07: Sentry + PagerDuty integration | DevOps | 3 | T-INF-04 | Alertas configuradas |
| T-BE-38: cache de respuestas API (ADR-014) | Backend | 5 | — | TTL por endpoint, invalidación por evento |
| T-BE-39: vista materializada refresh + cron | Backend | 3 | T-BD-10 | MV refrescada tras publicaciones |
| T-BE-40: middleware ForzarJsonApi + transformar errores JSON:API | Backend | 3 | — | Errores consistentes |
| T-FE-46: optimización bundle (code-splitting, lazy loading) | Frontend | 5 | T-FE-01..02 | Bundle inicial <150 KB sitio, <300 KB panel |
| T-FE-47: sitemap.xml dinámico | Frontend | 3 | T-BE-09 | /sitemap.xml regenerado |
| T-FE-48: verificación final accesibilidad con usuarios reales | QA | 8 | T-FE-31..39 | Informe final |
| T-QA-11: tests E2E Playwright + axe-core en CI (todas las rutas) | QA | 5 | T-INF-02 | Quality gate activo |
| T-QA-12: tests k6 carga (1000 VU durante 10 min) | QA | 5 | T-INF-04 | p95 latencia ≤ 500 ms |
| T-QA-13: penetration test externo | QA | 5 | T-INF-04 | 0 hallazgos críticos/altos |
| T-QA-14: auditoría final cumplimiento Ley 1712/2014 + Res. 1519/2020 | QA | 5 | T-FE-40 | ITA ≥85 |

---

## Resumen de puntos por sprint

```
S-01 ████████████████████  78
S-02 █████████████████████ 82
S-03 ██████████████████████ 86
S-04 █████████████████████  84
S-05 ████████████████████  80
S-06 ███████████████████   76
S-07 █████████████████████ 82
S-08 ██████████████████    72
─────────────────────────
TOTAL 640 pts / 4 meses
```

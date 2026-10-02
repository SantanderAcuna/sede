# Estructura de Directorios del Proyecto

> **Convención:** cada directorio se justifica por una razón arquitectónica explícita.
> **Trazabilidad:** la estructura refleja los ADRs y las restricciones del SRS.

---

## 0. Separación arquitectónica `panel/` vs `sitio/` (recordatorio crítico)

Estos dos clientes Nuxt están **estricta y físicamente separados**. **No comparten componentes, stores, ni lógica de negocio**: solo comparten los **tipos TypeScript generados del OpenAPI** (`pnpm openapi:generar-ts`) y, opcionalmente, un paquete `@sede/ui` extraído a posteriori si surge duplicación real.

### `panel/` — Vue 3 + Vite + TypeScript (SPA con Sanctum)

- **Tipo:** SPA pura (Client-Side Rendering).
- **Render:** `<div id="app">` hidratado en el navegador; NO usa SSR ni SSG.
- **Router:** `vue-router` (NO `pages/` de Nuxt).
- **Entry point:** `src/main.ts` (crea la app con `createApp`).
- **Estado:** Pinia stores en `src/stores/`.
- **HTTP:** Axios con `withCredentials: true` para Sanctum (cookie HttpOnly + CSRF token).
- **Vite:** `vite.config.ts` con `@vitejs/plugin-vue` + alias `@/`.
- **Componentes:** SIEMPRE `<script setup lang="ts">` con macros tipadas (`defineProps<T>()`, `defineEmits<...>()`).
- **NO usa:** `useFetch`, `useAsyncData`, `definePageMeta`, `~/`, ni la convención `app/`.
- **Puerto dev:** 3001.

### `sitio/` — Nuxt 4 + Vue 3 + TypeScript (SSR para el público)

- **Tipo:** Server-Side Rendering (SSR) con hidratación cliente.
- **Render:** HTML generado en servidor en cada request → mejora SEO (crítico para transparencia pública, Ley 1712/2014).
- **Router:** file-based en `app/pages/` (Nuxt).
- **Entry point:** `app/app.vue` (root component) + `nuxt.config.ts`.
- **Estado:** Pinia stores en `app/stores/` (auto-imported por Nuxt).
- **HTTP:** `$fetch` (Nuxt) con `useFetch()` para SSR-safe data fetching; NO usa cookies de sesión (sitio público).
- **Layouts:** `app/layouts/default.vue`, `app/transparencia.vue`, `app/error.vue`.
- **Componentes:** `<script setup lang="ts">` dentro de Single-File Components en `app/components/`, auto-imported.
- **Usa:** `useFetch`, `useAsyncData`, `useState`, `useRuntimeConfig`, alias `~/`, directorio `app/`.
- **Puerto dev:** 3000.

### Tabla comparativa

| Aspecto | `panel/` | `sitio/` |
|---|---|---|
| Meta-framework | Ninguno (Vue 3 puro + Vite) | **Nuxt 4** |
| Render | SPA (CSR) | SSR + hidratación |
| Router | `vue-router` (`src/router/index.ts`) | File-based (`app/pages/`) |
| Entry | `src/main.ts` | `app/app.vue` + `nuxt.config.ts` |
| HTTP | Axios + Sanctum (cookie) | `$fetch` + `useFetch` (público) |
| Auth | Sanctum (cookie HttpOnly + CSRF) | Ninguna |
| Stores | `src/stores/*.ts` | `app/stores/*.ts` (auto-imported) |
| Componentes | `src/components/**/*.vue` | `app/components/**/*.vue` (auto-imported) |
| Composables | `src/composables/*.ts` | `app/composables/*.ts` (auto-imported) |
| Build | `vite build` → `dist/` | `nuxt build` → `.output/` |
| TypeScript | `<script setup lang="ts">` | `<script setup lang="ts">` |
| Puerto dev | 3001 | 3000 |
| Acceso | Panelistas autenticados | Público general |

> **Regla de oro:** un componente desarrollado en `panel/src/components/` **nunca** se importa desde `sitio/app/components/` ni viceversa. Si hay duplicación, se extrae a un paquete `@sede/ui` publicado internamente.

---

## 1. Vista de la raíz del proyecto

```
sede/                                  # Raíz del monorepo
├── backend/                           # Laravel 13 API (ADR-001)
├── sitio/                             # Nuxt 4 (SSR público para el ciudadano) (ADR-002)
├── panel/                             # Vue 3 + Vite + TS (SPA admin autenticado) (ADR-002)
├── contract/                          # OpenAPI 3.1 (ADR-005)
├── deploy/                            # Configuración de despliegue (ADR-015)
├── docker/                            # Dockerfiles y Compose para dev
├── docs/                              # Documentación operativa
├── docs-security/                     # Configuraciones de seguridad
├── sede-electronica-doc/              # Ingeniería de requisitos (este dir)
├── scripts/                           # Scripts utilitarios
├── research/                          # Investigaciones previas
├── investigacion/                     # Material de investigación
├── compose.yaml                       # Docker Compose servicios compartidos
├── compose.override.yaml              # Override local de dev
├── Makefile                           # Atajos de comandos
├── .accesos.local.env                 # Variables locales (no comiteadas)
├── DESIGN.md                          # Decisiones de diseño UI/UX
├── PRODUCT.md                         # Visión de producto
├── README.md                          # Índice principal
├── plan.md                            # Plan operativo
├── plan-barra-accesibilidad.md        # Plan accesibilidad
├── accesos.md                         # Credenciales y accesos
├── pass.md                            # Contraseñas (no comiteadas)
├── auditoria-*.md                     # Auditorías del proyecto
└── .github/                           # Workflows GitHub Actions
```

---

## 2. Estructura completa de `backend/` (Laravel 13)

```
backend/
├── app/
│   ├── Console/
│   │   ├── Commands/                  # Comandos artisan
│   │   │   ├── AuditoriaReporte.php
│   │   │   ├── VerificarVinculosRotos.php
│   │   │   ├── SincronizarSigep.php
│   │   │   └── RefrescarCacheTransparencia.php
│   │   └── Kernel.php
│   ├── Enums/
│   │   ├── CategoriaTransparencia.php    # 10 subsecciones
│   │   ├── TipoDocumento.php             # PDF, XLSX, CSV, JSON, ...
│   │   ├── EstadoDocumento.php           # borrador, publicado, archivado
│   │   ├── RolUsuario.php                # admin, editor, aprobador, ...
│   │   └── PrioridadPublicacion.php
│   ├── Events/
│   │   ├── DocumentoPublicado.php
│   │   ├── DocumentoReemplazado.php
│   │   └── ServidorSincronizado.php
│   ├── Exceptions/
│   │   ├── Handler.php
│   │   └── JsonApi/                      # Excepciones específicas JSON:API
│   │       ├── RecursoNoEncontradoException.php
│   │       └── ValidacionFallidaException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── Api/
│   │   │   │   └── V1/
│   │   │   │       ├── SaludController.php
│   │   │   │       ├── Identidad/
│   │   │   │       │   ├── TopBarController.php
│   │   │   │       │   ├── FooterController.php
│   │   │   │       │   ├── MenuController.php
│   │   │   │       │   └── NoticiaController.php
│   │   │   │       ├── Transparencia/
│   │   │   │       │   ├── SubseccionController.php
│   │   │   │       │   ├── DocumentoController.php
│   │   │   │       │   ├── BuscadorController.php
│   │   │   │       │   └── TableroItaController.php
│   │   │   │       ├── Directorio/
│   │   │   │       │   ├── DependenciaController.php
│   │   │   │       │   ├── ServidorPublicoController.php
│   │   │   │       │   └── EscalaSalarialController.php
│   │   │   │       ├── Panel/
│   │   │   │       │   ├── AuthController.php
│   │   │   │       │   ├── UsuarioController.php
│   │   │   │       │   ├── DocumentoEditorController.php
│   │   │   │       │   └── AuditoriaController.php
│   │   │   │       └── Normativa/
│   │   │   │           ├── NormaController.php
│   │   │   │           └── AgendaRegulatoriaController.php
│   │   ├── Middleware/
│   │   │   ├── AuditarOperacion.php
│   │   │   ├── ForzarJsonApi.php
│   │   │   ├── RateLimitarPorIp.php
│   │   │   ├── VerificarContentType.php
│   │   │   └── TransformarErroresValidacion.php
│   │   ├── Requests/
│   │   │   ├── Api/V1/
│   │   │   │   ├── CrearDocumentoRequest.php
│   │   │   │   ├── ActualizarDocumentoRequest.php
│   │   │   │   ├── BuscarRequest.php
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── ...
│   │   ├── Resources/
│   │   │   ├── JsonApiResource.php          # Base JSON:API
│   │   │   ├── Identidad/
│   │   │   │   ├── TopBarResource.php
│   │   │   │   ├── FooterResource.php
│   │   │   │   ├── MenuResource.php
│   │   │   │   └── NoticiaResource.php
│   │   │   ├── Transparencia/
│   │   │   │   ├── SubseccionResource.php
│   │   │   │   ├── DocumentoResource.php
│   │   │   │   └── TableroItaResource.php
│   │   │   ├── Directorio/
│   │   │   │   ├── DependenciaResource.php
│   │   │   │   ├── ServidorPublicoResource.php
│   │   │   │   └── EscalaSalarialResource.php
│   │   │   └── Panel/
│   │   │       ├── UsuarioResource.php
│   │   │       └── RolResource.php
│   ├── Jobs/
│   │   ├── CalcularHashDocumento.php
│   │   ├── SincronizarServidorSigep.php
│   │   ├── EnviarAlertaPublicacion.php
│   │   ├── RefrescarCacheSubseccion.php
│   │   └── GenerarReporteAuditoria.php
│   ├── Listeners/
│   │   ├── EnviarNotificacionPublicacion.php
│   │   └── RegistrarCambioEnAuditoria.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Tramite.php
│   │   ├── IngestaTramite.php
│   │   ├── Identidad/
│   │   │   ├── TopBar.php
│   │   │   ├── Footer.php
│   │   │   ├── MenuItem.php
│   │   │   └── Noticia.php
│   │   ├── Transparencia/
│   │   │   ├── Subseccion.php
│   │   │   ├── Documento.php
│   │   │   ├── DocumentoVersion.php
│   │   │   ├── MetadatoDocumento.php
│   │   │   └── TableroIta.php
│   │   ├── Directorio/
│   │   │   ├── Dependencia.php
│   │   │   ├── ServidorPublico.php
│   │   │   ├── EscalaSalarial.php
│   │   │   └── SincronizacionSigep.php
│   │   └── Panel/
│   │       ├── Usuario.php
│   │       ├── Rol.php
│   │       └── Sesion.php
│   ├── Policies/
│   │   ├── DocumentoPolicy.php
│   │   ├── UsuarioPolicy.php
│   │   └── NoticiaPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   ├── HorizonServiceProvider.php
│   │   ├── RouteServiceProvider.php
│   │   └── JsonApiServiceProvider.php
│   ├── Repositories/
│   │   ├── Transparencia/
│   │   │   ├── SubseccionRepository.php
│   │   │   └── DocumentoRepository.php
│   │   ├── Identidad/
│   │   │   └── MenuRepository.php
│   │   └── Directorio/
│   │       └── ServidorPublicoRepository.php
│   ├── Rules/
│   │   ├── PrefijoTelefonicoRule.php
│   │   ├── HashSha256Rule.php
│   │   └── PeriodicidadValidaRule.php
│   ├── Services/
│   │   ├── IdentidadService.php
│   │   ├── TransparenciaService.php
│   │   ├── DocumentoService.php
│   │   ├── DirectorioService.php
│   │   ├── NoticiaService.php
│   │   ├── NormogramaService.php
│   │   ├── BusquedaService.php
│   │   ├── NotificacionService.php
│   │   ├── AuditoriaService.php
│   │   ├── SigepSyncService.php
│   │   └── ItaCalculatorService.php
│   └── Support/
│       ├── JsonApi/
│       │   ├── PaginatedCollection.php
│       │   ├── ErrorResponse.php
│       │   └── RelationshipBuilder.php
│       └── Pagination/
│           └── CursorPaginator.php
├── bootstrap/
│   ├── app.php
│   └── providers.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cors.php
│   ├── database.php
│   ├── filesystems.php
│   ├── jsonapi.php                    # Configuración JSON:API
│   ├── logging.php
│   ├── queue.php
│   ├── sanctum.php
│   └── services.php
├── database/
│   ├── database.sqlite                # Solo dev
│   ├── datos/                         # Seeds iniciales (normas, dependencias)
│   │   ├── dependencias.json
│   │   ├── municipios.json
│   │   └── tipos-norma.json
│   ├── factories/
│   │   ├── DocumentoFactory.php
│   │   ├── NoticiaFactory.php
│   │   ├── ServidorPublicoFactory.php
│   │   └── UsuarioFactory.php
│   ├── migrations/                    # Ver 04-diseno-bd/migraciones-seeders.md
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolesPermisosSeeder.php
│       ├── DependenciasSeeder.php
│       ├── SubseccionesTransparenciaSeeder.php
│       └── DatosInicialesSeeder.php
├── public/
├── resources/
│   └── views/                         # Solo para emails y errores (no vistas públicas)
├── routes/
│   ├── api.php                        # Rutas API (/api/v1/*)
│   ├── console.php
│   ├── salud.php                      # Health check
│   └── web.php                        # Solo redirige a /api o al sitio
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
└── tests/
    ├── Feature/
    │   ├── Api/
    │   │   ├── V1/
    │   │   │   ├── Identidad/
    │   │   │   ├── Transparencia/
    │   │   │   ├── Directorio/
    │   │   │   └── Panel/
    │   ├── JsonApi/
    │   └── Seguridad/
    ├── Unit/
    │   ├── Models/
    │   ├── Services/
    │   ├── Repositories/
    │   └── Rules/
    ├── Pest.php
    └── TestCase.php
```

---

## 3. Estructura completa de `sitio/` (Nuxt 4 público)

> **Convención Nuxt 4:** todo el código va dentro de `app/`. SSR habilitado por defecto en `nuxt.config.ts` (`ssr: true`). Componentes y composables son **auto-importados** (no requieren `import` explícito en el template). Páginas en `app/pages/` son file-based routing.

```
sitio/
├── app/
│   ├── app.vue                        # Root component
│   ├── error.vue                      # Página de error
│   ├── assets/
│   │   ├── css/
│   │   │   ├── govco.css              # Variables CSS GOV.CO
│   │   │   ├── tailwind.css
│   │   │   └── print.css
│   │   ├── fonts/
│   │   │   ├── NunitoSans-*.woff2
│   │   │   └── Verdana-*.woff2
│   │   ├── icons/
│   │   └── images/
│   ├── components/
│   │   ├── identidad/
│   │   │   ├── TopBarGOVCO.vue
│   │   │   ├── FooterGOVCO.vue
│   │   │   ├── CabeceraAlcaldia.vue
│   │   │   ├── MenuPrincipal.vue
│   │   │   ├── MenuItem.vue
│   │   │   ├── MigasPan.vue
│   │   │   ├── BuscadorCabecera.vue
│   │   │   └── SugerenciasBusqueda.vue
│   │   ├── transparencia/
│   │   │   ├── MenuSubsecciones.vue
│   │   │   ├── ListadoDocumentos.vue
│   │   │   ├── TarjetaDocumento.vue
│   │   │   ├── BuscadorTransparencia.vue
│   │   │   ├── FiltrosTransparencia.vue
│   │   │   ├── PaginadorDocumentos.vue
│   │   │   └── VisorDocumento.vue
│   │   ├── directorio/
│   │   │   ├── TarjetaServidor.vue
│   │   │   ├── TablaServidores.vue
│   │   │   ├── Organigrama.vue
│   │   │   └── FiltroDependencia.vue
│   │   ├── noticias/
│   │   │   ├── TarjetaNoticia.vue
│   │   │   ├── ListadoNoticias.vue
│   │   │   ├── CarruselNoticias.vue
│   │   │   └── DetalleNoticia.vue
│   │   ├── inicio/
│   │   │   ├── Hero.vue
│   │   │   ├── AccesosRapidos.vue
│   │   │   ├── TramitesDestacados.vue
│   │   │   └── CalendarioEventos.vue
│   │   ├── accesibilidad/
│   │   │   ├── BarraAccesibilidad.vue
│   │   │   ├── SkipLink.vue
│   │   │   └── AvisoSalidaExterna.vue
│   │   ├── ui/                       # Componentes UI base
│   │   │   ├── Btn.vue
│   │   │   ├── Card.vue
│   │   │   ├── Modal.vue
│   │   │   ├── Toast.vue
│   │   │   ├── Paginador.vue
│   │   │   ├── Tabs.vue
│   │   │   ├── Acordeon.vue
│   │   │   ├── Spinner.vue
│   │   │   ├── Alerta.vue
│   │   │   ├── EnlaceExterno.vue
│   │   │   ├── Tabla.vue
│   │   │   ├── Form/
│   │   │   │   ├── InputText.vue
│   │   │   │   ├── Textarea.vue
│   │   │   │   ├── Select.vue
│   │   │   │   └── FileUpload.vue
│   │   │   └── VolverArriba.vue
│   │   └── error/
│   │       ├── Error404.vue
│   │       └── Error500.vue
│   ├── composables/
│   │   ├── useApi.ts                  # Wrapper de $fetch
│   │   ├── useAuth.ts
│   │   ├── useBreadcrumb.ts
│   │   ├── useBusqueda.ts
│   │   ├── useFiltros.ts
│   │   ├── useIdioma.ts
│   │   ├── useCookies.ts
│   │   ├── useDocumento.ts
│   │   ├── useNoticia.ts
│   │   ├── useServidor.ts
│   │   ├── useTransparencia.ts
│   │   ├── useMenu.ts
│   │   ├── useTopBar.ts
│   │   ├── useFooter.ts
│   │   ├── useAccesibilidad.ts
│   │   └── useWebVitals.ts
│   ├── config/
│   │   ├── env.ts
│   │   ├── api.ts
│   │   └── seo.ts
│   ├── layouts/
│   │   ├── default.vue
│   │   ├── transparencia.vue
│   │   └── error.vue
│   ├── middleware/
│   │   ├── auth.global.ts             # Verifica sesión si aplica
│   │   └── analytics.ts
│   ├── pages/
│   │   ├── index.vue                  # Home
│   │   ├── transparencia/
│   │   │   ├── index.vue
│   │   │   ├── informacion-de-la-entidad/
│   │   │   │   ├── index.vue
│   │   │   │   ├── directorio.vue
│   │   │   │   └── organigrama.vue
│   │   │   ├── normativa/
│   │   │   │   ├── index.vue
│   │   │   │   └── [id].vue
│   │   │   ├── contratacion/
│   │   │   │   └── index.vue
│   │   │   ├── planeacion-presupuesto/
│   │   │   │   └── index.vue
│   │   │   ├── tramites/
│   │   │   ├── participa/
│   │   │   ├── datos-abiertos/
│   │   │   ├── grupos-de-interes/
│   │   │   ├── reporte-especifico/
│   │   │   └── tributaria/
│   │   │       ├── index.vue
│   │   │       └── calendario.vue
│   │   ├── directorio/
│   │   │   ├── index.vue
│   │   │   └── [slug].vue
│   │   ├── noticias/
│   │   │   ├── index.vue
│   │   │   └── [slug].vue
│   │   ├── buscar.vue
│   │   ├── mapa-del-sitio.vue
│   │   ├── contacto.vue
│   │   ├── accesibilidad.vue
│   │   ├── terminos.vue
│   │   ├── privacidad.vue
│   │   └── politicas/
│   │       ├── cookies.vue
│   │       ├── derechos-autor.vue
│   │       └── accesibilidad.vue
│   ├── plugins/
│   │   ├── api.ts                     # Cliente Axios
│   │   ├── analytics.ts
│   │   └── accesibilidad.ts
│   └── types/
│       ├── api.d.ts                   # Tipos generados OpenAPI
│       ├── identidad.d.ts
│       ├── transparencia.d.ts
│       ├── directorio.d.ts
│       └── common.d.ts
├── public/
│   ├── favicon.ico
│   ├── robots.txt
│   └── sitemap.xml                    # Regenerado por job
├── server/
│   ├── api/                           # Server API (proxy opcional)
│   └── middleware/
│       └── cache.ts
├── tests/
│   ├── unit/
│   ├── component/
│   └── e2e/
│       ├── accesibilidad.spec.ts
│       ├── home.spec.ts
│       └── transparencia.spec.ts
├── scripts/
│   └── trazabilidad.mjs
├── .nuxt/                             # Build artifacts
├── .output/                           # Output de build
├── nuxt.config.ts
├── package.json
├── tsconfig.json
├── vitest.config.ts
├── playwright.config.ts
└── README.md
```

---

## 4. Estructura completa de `panel/` (Vue 3 + Vite + TS — SPA con Sanctum)

> **NO es Nuxt.** Es una SPA pura Vue 3 + Vite. Sin SSR. Sin file-based routing (`vue-router` manual). Sin auto-import de componentes. Sin directorio `app/`. La carpeta raíz del código es `src/`. El entry point es `src/main.ts`.

```
panel/
├── src/
│   ├── assets/
│   ├── components/
│   │   ├── layout/
│   │   │   ├── CabeceraPanel.vue
│   │   │   ├── MenuLateral.vue
│   │   │   ├── PiePaginaPanel.vue
│   │   │   └── BreadcrumbPanel.vue
│   │   ├── acceso/
│   │   │   ├── FormularioLogin.vue
│   │   │   ├── FormularioMfa.vue
│   │   │   ├── FormularioRecuperar.vue
│   │   │   └── SinPermiso.vue
│   │   ├── admin/
│   │   │   ├── InicioPanel.vue
│   │   │   ├── TarjetaMetrica.vue
│   │   │   ├── ResumenActividad.vue
│   │   │   └── AlertasPanel.vue
│   │   ├── documentos/
│   │   │   ├── ListadoDocumentosEditor.vue
│   │   │   ├── EditorDocumento.vue
│   │   │   ├── CargaArchivo.vue
│   │   │   ├── VersionadorDocumento.vue
│   │   │   ├── MetadatosDocumento.vue
│   │   │   └── ValidadorHash.vue
│   │   ├── transparencia/
│   │   │   ├── GestorSubsecciones.vue
│   │   │   ├── GestorDocumentos.vue
│   │   │   └── EditorFechaPublicacion.vue
│   │   ├── directorio/
│   │   │   ├── GestorDependencias.vue
│   │   │   ├── GestorServidores.vue
│   │   │   └── SincronizadorSigep.vue
│   │   ├── usuarios/
│   │   │   ├── ListadoUsuarios.vue
│   │   │   ├── FormularioUsuario.vue
│   │   │   ├── GestorRoles.vue
│   │   │   └── GestorPermisos.vue
│   │   ├── auditoria/
│   │   │   ├── TablaAuditoria.vue
│   │   │   ├── FiltrosAuditoria.vue
│   │   │   └── DetalleAuditoria.vue
│   │   ├── ita/
│   │   │   ├── TableroIta.vue
│   │   │   ├── DetalleItemIta.vue
│   │   │   └── AccionCorrectiva.vue
│   │   ├── identidad/
│   │   │   ├── EditorTopBar.vue
│   │   │   ├── EditorFooter.vue
│   │   │   └── EditorMenu.vue
│   │   └── ui/
│   │       ├── Btn.vue
│   │       ├── Card.vue
│   │       ├── Modal.vue
│   │       ├── Toast.vue
│   │       ├── Paginador.vue
│   │       ├── Tabla.vue
│   │       └── Form/
│   │           ├── InputText.vue
│   │           ├── Textarea.vue
│   │           ├── Select.vue
│   │           ├── FileUpload.vue
│   │           ├── RichTextEditor.vue
│   │           └── DatePicker.vue
│   ├── config/
│   │   └── env.ts
│   ├── layouts/
│   │   ├── default.vue
│   │   ├── acceso.vue
│   │   └── error.vue
│   ├── main.ts
│   ├── plugins/
│   │   ├── api.ts
│   │   ├── axios.ts
│   │   └── autenticacion.ts
│   ├── router/
│   │   └── index.ts
│   ├── services/
│   │   ├── http.ts                    # Axios + interceptors
│   │   ├── auth.service.ts
│   │   ├── documentos.service.ts
│   │   ├── transparencia.service.ts
│   │   ├── directorio.service.ts
│   │   ├── usuarios.service.ts
│   │   ├── auditoria.service.ts
│   │   └── ita.service.ts
│   ├── stores/
│   │   ├── sesion.ts                  # Pinia store de sesión
│   │   ├── transparencia.ts
│   │   ├── documentos.ts
│   │   ├── directorio.ts
│   │   ├── usuarios.ts
│   │   ├── auditoria.ts
│   │   ├── ita.ts
│   │   └── ui.ts                      # Estado UI (sidebar, modales)
│   ├── types/
│   │   ├── api.d.ts                   # Generados OpenAPI
│   │   ├── auth.d.ts
│   │   ├── transparencia.d.ts
│   │   └── common.d.ts
│   └── views/
│       ├── acceso/
│       │   ├── EntrarView.vue
│       │   ├── MfaView.vue
│       │   ├── RecuperarView.vue
│       │   ├── SinPermisoView.vue
│       │   └── NoEncontradoView.vue
│       └── admin/
│           ├── InicioView.vue
│           ├── TransparenciaView.vue
│           ├── DocumentosView.vue
│           ├── DirectorioView.vue
│           ├── NoticiasView.vue
│           ├── UsuariosView.vue
│           ├── AuditoriaView.vue
│           ├── TableroItaView.vue
│           ├── IdentidadView.vue
│           └── EnConstruccionView.vue
├── public/
├── tests/
│   ├── unit/
│   ├── component/
│   └── e2e/
├── coverage/
├── dist/
├── index.html
├── package.json
├── vite.config.ts
├── vitest.config.ts
├── tsconfig.json
├── tsconfig.app.json
└── tsconfig.node.json
```

---

## 5. Estructura de `contract/` (OpenAPI)

```
contract/
├── openapi.yaml                       # Spec principal v3.1
├── openapi.v0.yaml                    # Backup antes de cambios
├── schemas/
│   ├── identidad.yaml
│   ├── transparencia.yaml
│   ├── directorio.yaml
│   ├── panel.yaml
│   ├── common.yaml
│   └── errores.yaml
├── examples/
│   ├── documento.json
│   ├── noticia.json
│   ├── servidor.json
│   └── error.json
├── scripts/
│   ├── verificar-drift.php            # CI: spec vs rutas
│   └── generar-ts.sh
└── README.md
```

---

## 6. Estructura de `deploy/`

```
deploy/
├── kubernetes/
│   ├── namespace.yaml
│   ├── api-deployment.yaml
│   ├── api-service.yaml
│   ├── sitio-deployment.yaml
│   ├── panel-deployment.yaml
│   ├── ingress.yaml
│   └── secrets.yaml.example
├── terraform/
│   ├── main.tf
│   ├── variables.tf
│   ├── cloud-sql.tf
│   ├── memorystore.tf
│   ├── gcs.tf
│   └── outputs.tf
├── ansible/
│   └── playbooks/
└── README.md
```

---

## 7. Estructura de `docker/`

```
docker/
├── backend/
│   ├── Dockerfile                     # PHP 8.3 + Composer
│   └── php.ini
├── sitio/
│   ├── Dockerfile                     # Node + Nuxt build
│   └── nginx.conf
├── panel/
│   ├── Dockerfile                     # Node + Vite build
│   └── nginx.conf
├── postgres/
│   ├── Dockerfile                     # Postgres 15 + extensiones
│   └── init.sql                       # Crea extensiones pg_trgm, unaccent
├── redis/
│   └── redis.conf
├── nginx/
│   ├── nginx.conf
│   └── conf.d/
│       ├── api.conf
│       ├── sitio.conf
│       └── panel.conf
└── minio/                             # S3-compatible local
    └── Dockerfile
```

---

## 8. Convenciones transversales

| Aspecto | Convención |
|---|---|
| **Idiomas en código** | Código en inglés (`UsuarioResource`); comentarios en español. |
| **Rutas API** | kebab-case en URLs (`/api/v1/transparencia/documentos`), snake_case en propiedades JSON. |
| **Nombres de archivos PHP** | PascalCase para clases (`DocumentoController`), camelCase para métodos. |
| **Componentes Vue** | PascalCase (`TopBarGOVCO.vue`). |
| **Tipos TS** | PascalCase para tipos/interfaces; camelCase para variables. |
| **Commits** | Conventional Commits en español (`feat: añadir CRUD de documentos`). |
| **Branches** | `main`, `develop`, `feature/RF-XX-NNN-descripcion`, `hotfix/descripcion`. |
| **Tests** | Co-locados en `tests/` con estructura espejo de `app/`. |
| **Migraciones** | Numeradas secuencialmente, reversibles (`down()` implementado). |
| **Variables de entorno** | `.env.example` documentado; secrets fuera del repo (Vault o Secret Manager). |

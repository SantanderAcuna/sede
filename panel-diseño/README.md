# SGDI · Frontend Vue 3 — Panel Administrativo

Panel administrativo del **Sistema de Gestión Distrital Inteligente** de la Alcaldía Distrital de Santa Marta.

## Stack tecnológico

| Categoría | Tecnología |
|-----------|-----------|
| UI | **Vue 3** `<script setup>` + Composition API (sin Options API) |
| Tipos | **TypeScript strict** (`noImplicitAny`, cero `any`) |
| Estado | **Pinia** + `pinia-plugin-persistedstate` |
| HTTP | **Axios** (instancia única en `src/api/client.ts` con interceptors) |
| Routing | **Vue Router 4** (guards de auth + permisos) |
| Server state | **TanStack Vue Query** |
| Tablas | **TanStack Table v8** (`src/components/base/DataTable.vue`) |
| Formularios | **TanStack Form + Yup** |
| Estilos | **TailwindCSS** + CSS variables (tokens GOV.CO) |
| Notificaciones | **vue-toastification** |
| Íconos | **FontAwesome Free** (`<FaIcon />` global) |
| i18n | **vue-i18n** (es-CO) |
| Tests unitarios | **Vitest** + `@vue/test-utils` |
| Tests E2E | **Playwright** |
| Accesibilidad | **axe-core** (`@axe-core/playwright` + unit) |
| Lint | **oxlint** → **eslint** type-aware (`--max-warnings=0`) |

## Setup

```bash
cp .env.example .env
npm install
npm run dev          # http://localhost:5173
npm run build
npm run preview
```

## Calidad

```bash
npm run lint         # oxlint (correctness) + eslint --max-warnings=0
npm run test:unit    # Vitest (incluye axe-core unit)
npm run test:e2e     # Playwright (incluye a11y WCAG 2.1 AA)
npm run type-check   # vue-tsc strict
```

## Estructura

```
src/
  api/           # client.ts (axios único) + módulos por dominio
  assets/styles/ # tokens.css (GOV.CO) + main.css (Tailwind)
  components/
    base/        # BaseButton, BaseBadge, BaseModal, DataTable (TanStack), FormField, KpiCard, BaseTimeline
    domain/      # StatusBadge (PQRSD)
    feedback/    # CommandPalette, EmptyState, Skeleton
  composables/   # useToast (vue-toastification), usePqrsd (Vue Query), usePermisos
  i18n/          # config + locales/es-CO.json
  layouts/       # AdminLayout (sidebar colapsable/agrupado), AuthLayout
  plugins/       # fontawesome.ts, toast.ts
  router/        # rutas lazy + guards (requiresAuth, permisos)
  schemas/       # Yup schemas reutilizables
  stores/        # Pinia: auth, ui
  types/         # tipos de dominio (auth, pqrsd)
  views/         # 1 vista por ruta (lazy-loaded)
tests/
  unit/          # Vitest + axe-core
  e2e/           # Playwright + @axe-core/playwright
```

## Módulos incluidos

Dashboard · PQRSD (Ley 1755) · Trámites SUIT · Citas y Turnos · Notificaciones electrónicas ·
Sede Electrónica (Decreto 620) · Carpeta Ciudadana · Autenticación Digital · CMS · Portal Ciudadano ·
Transparencia/ITA · Gestión Documental · Interoperabilidad SIGMI · Conectores externos ·
Usuarios y Roles (RBAC) · Auditoría MSPI · Reportes ITA/FURAG · Motor de asignación · Configuración.

## Autenticación

Login con **MFA TOTP** (Decreto 1078). Formularios validados con TanStack Form + Yup.
Guards de router protegen rutas por `meta.requiresAuth` y `meta.permisos: Permiso[]`.

## Accesibilidad — WCAG 2.1 AA

Labels asociados, `aria-invalid`, `aria-describedby`, `role="alert"`, focus visible, contraste ≥ 4.5:1.
Verificado automáticamente con axe-core en pruebas unitarias y E2E.

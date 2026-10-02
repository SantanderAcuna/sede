# 06 — Especificaciones del Frontend

> **Stacks diferenciados por cliente** (ver `03-propuesta/adr.md` ADR-002):
> - **`sitio/` (público, ciudadano):** **Nuxt 4** + Vue 3 + Composition API + `<script setup lang="ts">` + Pinia + file-based routing (`app/pages/`) + `$fetch` + `useFetch` (SSR-safe) + Vitest + Playwright + axe-core. SSR habilitado. Puerto dev: 3000.
> - **`panel/` (admin autenticado):** **Vue 3 + Vite + TypeScript** (SPA pura, **sin Nuxt**) + Composition API + `<script setup lang="ts">` + Pinia + `vue-router` (manual) + Axios + **Laravel Sanctum** (cookie HttpOnly + CSRF token) + MFA TOTP. Puerto dev: 3001.
>
> **Regla arquitectónica:** ambos clientes comparten únicamente los **tipos TypeScript generados del OpenAPI 3.1** (`pnpm openapi:generar-ts`). NO comparten componentes, stores ni lógica de negocio.
>
> **Accesibilidad:** WCAG 2.1 AA verificable (RNF-ACES-01) en ambos clientes.

---

## Índice

| Archivo | Contenido | Aplica a |
|---|---|---|
| `tipos-typescript.md` | Tipos generados del OpenAPI + tipos de dominio | ambos |
| `cliente-axios.md` | `$fetch` para sitio (Nuxt) + Axios para panel (SPA) + interceptors + errores | ambos |
| `stores-pinia.md` | Stores Pinia: sesion (panel), transparencia, documentos, ui, accesibilidad, alertas | ambos |
| `componentes-vue.md` | Catálogo de componentes Vue con `<script setup lang="ts">` y macros tipadas | ambos |
| `composables.md` | Composables reutilizables (`useApi`, `useAuth`, `useBusqueda`, etc.) | ambos |
| `accesibilidad-wcag.md` | Estrategia WCAG 2.1 AA, axe-core, auditoría con usuarios | ambos |

---

## Resumen ejecutivo comparativo

| Aspecto | `sitio/` (Nuxt 4 SSR) | `panel/` (Vue 3 + Vite SPA) |
|---|---|---|
| Meta-framework | **Nuxt 4** | Ninguno (Vue 3 + Vite directo) |
| Renderizado | **SSR + hidratación** (HTML generado en servidor) | **SPA pura** (Client-Side Rendering) |
| Routing | File-based (`app/pages/*.vue`) | `vue-router` (`src/router/index.ts`) |
| Entry point | `app/app.vue` + `nuxt.config.ts` | `src/main.ts` + `index.html` |
| HTTP público/privado | `$fetch` + `useFetch` (Nuxt, SSR-safe) | `axios` (instancia única con `withCredentials`) |
| Auth | **Ninguna** (sitio público) | **Sanctum** (cookie HttpOnly + CSRF + MFA TOTP) |
| Auto-import | Sí (componentes, composables) | No (imports explícitos) |
| Bundle inicial | <150 KB gzip | <300 KB gzip |
| Cobertura tests | ≥70% | ≥70% |
| Stores Pinia | 5 (transparencia, documentos, menú, accesibilidad, ui) | 6 (+ `sesion` para Sanctum) |
| Composables | 12+ | 15+ |
| Componentes Vue | ~80 | ~60 |
| TypeScript strict | ✅ | ✅ |
| ESLint + Prettier | ✅ | ✅ |
| Vue TSC | ✅ | ✅ |
| Build | `nuxt build` → `.output/` | `vite build` → `dist/` |
| Puerto dev | 3000 | 3001 |

---

## Convenciones comunes a ambos clientes

| Aspecto | Convención |
|---|---|
| **Componentes** | `<script setup lang="ts">` siempre; macros tipadas (`defineProps<T>()`, `defineEmits<{ (e: 'x', v: T): void }>()`, `defineSlots<{ default(props: T): any }>()`). |
| **Types** | PascalCase para interfaces/types; union types literales en lugar de enums. |
| **Stores** | Composition style (`defineStore('nombre', () => { ... })`); sin Options API. |
| **Naming** | `useXxxStore`, `useXxxComposable`, `XxxComponent.vue`, `XxxService.ts`. |
| **Tests** | Co-locados con `*.spec.ts`; estructura espejo de `src/`. |
| **Accesibilidad** | Atributos ARIA explícitos; roles semánticos; orden de foco coherente con DOM. |
| **Estilo** | Tailwind CSS con tokens del Kit UI GOV.CO (paleta Cobalt `#0943B5`). |

---

## Diferencias operativas clave (resumen)

| Operación | `sitio/` (Nuxt 4) | `panel/` (Vue 3 + Vite) |
|---|---|---|
| Crear página nueva | Crear archivo `.vue` en `app/pages/` | Crear componente `.vue` en `src/views/` + ruta en `src/router/index.ts` |
| Consumir API pública | `await useFetch('/api/...')` | `await http.get('/api/...')` |
| Consumir API autenticada | (no aplica) | `await http.post('/api/...')` con cookie HttpOnly |
| Crear composable | Crear en `app/composables/` (auto-imported) | Crear en `src/composables/` + import manual |
| Crear store Pinia | Crear en `app/stores/` (auto-imported) | Crear en `src/stores/` + import manual |
| Proteger ruta | `middleware/auth.global.ts` + redirect a GOV.CO login | `router.beforeEach()` + verificación de sesión |
| Build para producción | `nuxt build` | `vite build` |

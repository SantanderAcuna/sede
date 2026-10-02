# 01.4 — Stack Tecnológico

> Marco técnico y referencias bibliográficas de las tecnologías adoptadas en el proyecto.
> **No negociable:** las decisiones stack de la instrucción. Las referencias documentan **cómo** se usan, no **cuáles** se eligen.

---

## 1. Backend — Laravel 13

Laravel es un framework PHP basado en el patrón MVC, creado por Taylor Otwell. La versión 13.x (vigente al 2026) requiere PHP 8.3+.

### 1.1 Documentación oficial

Otwell, T. et al. *Laravel 13 Documentation* [1] cubre todos los componentes:

| Componente | Documentación | Uso en este proyecto |
|---|---|---|
| **Eloquent ORM** | Eloquent: Getting Started | Modelos de dominio con `$fillable` |
| **JSON Resources** | Eloquent: API Resources | Serialización de respuestas (sin `vnd.api+json`) |
| **Form Requests** | Validation: Form Requests | Validación de entrada en operaciones de escritura |
| **Policies** | Authorization: Policies | Autorización con `$user->can(...)` |
| **Sanctum** | Authentication: API Token Authentication | Sesión SPA + tokens API |
| **Queues** | Queues | Trabajos asíncronos (sync SIGEP, generación PDF, …) |
| **Migrations** | Database: Migrations | Versionado de esquema |
| **Seeders / Factories** | Database: Seeding | Datos de prueba reproducibles |
| **Routing** | Routing | Versionado por prefijo `/api/v1/` |

### 1.2 Convenciones del proyecto (de `backend/AGENTS.md`)

- `declare(strict_types=1)` en todo archivo PHP.
- Capas respetadas: Controller → FormRequest → Service → Repository → Model.
- Sobre plano `{success, message, data, errors}`.
- PATCH para actualizaciones (nunca PUT).
- `$fillable` en modelos (jamás `$guarded = []`).
- Dinero en `decimal` (nunca `float`).

### 1.3 Dependencias del proyecto (`backend/composer.json`)

| Paquete | Versión | Propósito |
|---|---|---|
| `laravel/framework` | ^13.17 | Framework base |
| `laravel/sanctum` | ^4.0 | Autenticación API y SPA |
| `laravel/horizon` | ^5.50 | Tablero de colas |
| `laravel/tinker` | ^3.0 | REPL para debugging |
| `barryvdh/laravel-dompdf` | ^3.1 | Generación de PDF (actas, reportes) |
| `league/csv` | ^9.28 | Lectura/escritura CSV (datos abiertos, importación SUIT) |
| `league/flysystem-aws-s3-v3` | ^3.0 | Almacenamiento en S3 (MinIO self-hosted) |
| `owen-it/laravel-auditing` | ^14.0 | Auditoría de modelos |
| `pragmarx/google2fa` | ^9.1 | TOTP para MFA |
| `spatie/laravel-activitylog` | ^5.1 | Log de actividades |
| `spatie/laravel-backup` | ^10.3 | Respaldos automáticos |
| `spatie/laravel-honeypot` | ^4.7 | Anti-spam en formularios públicos |
| `spatie/laravel-medialibrary` | ^11.23 | Gestión de archivos adjuntos |
| `spatie/laravel-permission` | ^8.3 | Roles y permisos |
| `spatie/laravel-sitemap` | ^8.2 | Generación de `sitemap.xml` |

### 1.4 Dependencias de desarrollo

| Paquete | Versión | Propósito |
|---|---|---|
| `larastan/larastan` | ^3.0 | Análisis estático nivel 8 |
| `laravel/pint` | ^1.27 | Formateador (con `--test` como puerta CI) |
| `phpunit/phpunit` | ^13.3 | Suite de pruebas |

> **Decisión del proyecto:** **NO** se instala `laravel/boost` (es una dependencia de desarrollo que no está en el plan aprobado; ver `backend/CLAUDE.md`).

---

## 2. Frontend — Nuxt 4 + Vue 3 + TypeScript

Nuxt 4 es un meta-framework sobre Vue 3 que provee SSR, routing automático, y estructura de proyecto opinionada.

### 2.1 Documentación oficial

You, E. et al. *Vue 3 Documentation* [2] cubre:

- **Composition API** con `<script setup lang="ts">` — único estilo usado.
- **Macros tipadas:** `defineProps<T>()`, `defineEmits<T>()`, `defineSlots<T>()` con genéricos.
- **Reactivity API:** `ref`, `reactive`, `computed`, `watch`, `watchEffect`.
- **Lifecycle hooks:** `onMounted`, `onBeforeUnmount`, etc.

### 2.2 Pinia

Vue.js Team. *Pinia Documentation* [3]:

- **State management para Vue 3** (reemplazo oficial de Vuex).
- **Stores tipados:** `defineStore('nombre', () => { ... })` con tipos inferidos.
- **Persistencia:** plugin `@pinia-plugin-persistedstate/nuxt` (a evaluar; el consentimiento de cookies va en backend).

### 2.3 Vite

Vite Team. *Vite Documentation* [4]:

- **Build tool** ultrarrápido con HMR.
- **Compilación TS** integrada.
- **Soporte para `<style>`** scoped en SFC.

### 2.4 TypeScript

Microsoft. *TypeScript Handbook* [5]:

- **Modo estricto activado** (`strict: true` en `tsconfig.json`).
- **Tipos generados del OpenAPI** con `openapi-typescript`.
- **Sin `any`** (cuando sea necesario, usar `unknown` y refinar).

### 2.5 Dependencias del proyecto (`sitio/package.json`)

| Paquete | Versión | Propósito |
|---|---|---|
| `nuxt` | ^4.5.2 | Framework |
| `vue` | ^3.5.43 | Motor de UI |
| `vue-router` | ^5.3.1 | Routing interno |
| `@axe-core/playwright` | ^4.13.0 | Auditoría WCAG |
| `@playwright/test` | ^1.63.0 | Pruebas e2e |
| `@vue/test-utils` | ^2.5.1 | Pruebas unitarias de componentes |
| `openapi-typescript` | ^7.13.0 | Generación de tipos TS desde OpenAPI |
| `vitest` | ^5.0.3 | Suite de pruebas unitarias |
| `vue-tsc` | ^3.3.11 | Type-checker para SFC |
| `axe-core` | ^4.13.0 | Reglas de accesibilidad |
| `jsdom` | ^30.1.1 | DOM simulado para pruebas |
| `typescript` | ~5.9.0 | Compilador TS |

---

## 3. Base de datos — PostgreSQL 15+

The PostgreSQL Global Development Group. *PostgreSQL Documentation* [6]:

- **Esquema relacional único integrado** (sin microservicios).
- **Tipos nativos:** `TIMESTAMPTZ`, `INET`, `BYTEA`, `NUMERIC`, `JSONB` (uso restrictivo), `UUID` (vía `pgcrypto`).
- **Particionamiento declarativo** (PG 11+).
- **Políticas de seguridad a nivel de fila (RLS).**
- **CHECK constraints + triggers** para invariantes.
- **GENERATED ALWAYS AS ... STORED** para columnas derivadas.
- **DOMAIN** para tipos personalizados (correo electrónico, teléfono CO).

---

## 4. Cache / Queue — Redis + Laravel Horizon

Redis ([7]) es la cola de mensajes y caché para:

- **Cola de trabajos** (notificaciones, generación PDF, sync SIGEP).
- **Caché de consultas** (paginación del directorio institucional, lista de normativas vigentes).
- **Rate limiting** por IP/usuario (built-in Laravel).

---

## 5. Herramientas de calidad y CI

| Herramienta | Propósito | Puerta CI |
|---|---|---|
| **Pint** | Formato PHP (PSR-12) | ✅ |
| **PHPStan** nivel 8 (Larastan) | Análisis estático PHP | ✅ |
| **PHPUnit** | Pruebas backend | ✅ |
| **vue-tsc** | Type-checker SFC | ✅ |
| **Vitest** | Pruebas frontend | ✅ |
| **Playwright + axe-core** | Pruebas e2e + accesibilidad | ✅ |
| **ESLint** | Linter JS/TS (a configurar) | Recomendado |
| **Redocly** | Validación del OpenAPI | ✅ |
| **Prettier** | Formato JS/TS/Vue (a configurar) | Recomendado |

---

## Referencias IEEE (stack tecnológico)

[1] T. Otwell et al., *Laravel 13 Documentation*, Laravel Holdings, 2025. [En línea]. Disponible: https://laravel.com/docs/13.x

[2] E. You et al., *Vue 3 Documentation*, Vue.js, 2020–2025. [En línea]. Disponible: https://vuejs.org/guide/

[3] Vue.js Team, *Pinia: The intuitive store for Vue.js*, 2020–2025. [En línea]. Disponible: https://pinia.vuejs.org/

[4] Vite Team, *Vite: Next Generation Frontend Tooling*, 2020–2025. [En línea]. Disponible: https://vitejs.dev/

[5] Microsoft Corporation, *TypeScript Handbook*, 2012–2025. [En línea]. Disponible: https://www.typescriptlang.org/docs/

[6] The PostgreSQL Global Development Group, *PostgreSQL 15 Documentation*, 2022. [En línea]. Disponible: https://www.postgresql.org/docs/15/

[7] Redis Ltd., *Redis Documentation*, 2009–2025. [En línea]. Disponible: https://redis.io/documentation/

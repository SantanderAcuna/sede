# Tipos TypeScript

> **Fuente de verdad única:** `contract/openapi.yaml` (OpenAPI 3.1) — compartida por ambos clientes.
> **Generación:** se generan **dos archivos de tipos**, uno por cliente (no comparten código entre `sitio/` y `panel/`):
> - `sitio/app/types/api.d.ts` — consumido por la SPA Nuxt 4 pública.
> - `panel/src/types/api.d.ts` — consumido por la SPA Vue 3 + Vite autenticada.
>
> **Trazabilidad:** cada tipo referencia el endpoint OpenAPI que lo origina.

---

## 1. Generación de tipos desde OpenAPI

El script se ejecuta **dos veces**, una por cliente, desde la raíz del monorepo:

```bash
# Para sitio/ (Nuxt 4)
npx openapi-typescript contract/openapi.yaml \
  --output sitio/app/types/api.d.ts \
  --enum true \
  --immutable true

# Para panel/ (Vue 3 + Vite)
npx openapi-typescript contract/openapi.yaml \
  --output panel/src/types/api.d.ts \
  --enum true \
  --immutable true
```

Configuración consolidada en `package.json` (raíz del monorepo, vía workspaces):
```json
{
  "scripts": {
    "openapi:generar-ts:sitio": "openapi-typescript contract/openapi.yaml --output sitio/app/types/api.d.ts --enum true --immutable true",
    "openapi:generar-ts:panel": "openapi-typescript contract/openapi.yaml --output panel/src/types/api.d.ts --enum true --immutable true",
    "openapi:generar-ts": "npm run openapi:generar-ts:sitio && npm run openapi:generar-ts:panel",
    "openapi:verificar": "openapi-typescript contract/openapi.yaml --output /tmp/api.d.ts && diff sitio/app/types/api.d.ts /tmp/api.d.ts && diff panel/src/types/api.d.ts /tmp/api.d.ts"
  }
}
```

**Salida (ambos archivos):** tipos idénticos derivados de cada `schema` y `paths` del OpenAPI:

```ts
// Tipos derivados automáticamente
export interface paths {
  '/identidad/top-bar': {
    get: {
      responses: {
        200: {
          content: {
            'application/vnd.api+json': components['schemas']['TopBarResponse'];
          };
        };
      };
    };
  };
  // ...
}

export interface components {
  schemas: {
    TopBarResponse: { data: { type: 'top-bar'; id: string; attributes: TopBarAttributes } };
    TopBarAttributes: { logo_path: string; url_govco: string; altura_px: number; idioma_default: string };
    // ...
  };
}
```

---

## 2. Tipos de dominio (extendidos)

Algunos tipos se enriquecen manualmente para mejorar DX:

```typescript
// app/types/identidad.ts
import type { components } from './api';

export type TopBar = components['schemas']['TopBarResponse']['data'];
export type TopBarAttributes = components['schemas']['TopBarAttributes'];

export interface MenuItemNode extends components['schemas']['MenuItemAttributes'] {
  id: string;
  type: 'menu-item';
  hijos?: MenuItemNode[];
  padre_id?: string | null;
}

export type SubseccionTransparencia =
  components['schemas']['SubseccionTransparencia'];
export type SubseccionCodigo =
  SubseccionTransparencia['attributes']['codigo'];

export interface DocumentoDetallado
  extends components['schemas']['Documento'] {
  // Extensiones locales
  relaciones_incluidas?: {
    metadatos?: Record<string, string>;
    dependencias?: string[];
  };
}
```

---

## 3. Tipos de paginación

```typescript
// app/types/common.ts
export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
}

export interface JsonApiError {
  status: string;
  code: string;
  title: string;
  detail?: string;
  source?: {
    pointer?: string;
    parameter?: string;
  };
  meta?: Record<string, unknown>;
}

export interface JsonApiErrorResponse {
  jsonapi: { version: string };
  errors: JsonApiError[];
}
```

---

## 4. Tipos del panel admin

```typescript
// app/types/panel.ts
export type RolUsuario = 'administrador' | 'editor' | 'aprobador' | 'consultor' | 'seguridad';

export interface SesionActiva {
  usuario: {
    id: string;
    email: string;
    mfa_habilitado: boolean;
    roles: RolUsuario[];
  };
  csrf_token: string;
  expires_at: string;
}

export interface AlertaPublicacion {
  id: string;
  tipo: 'plan-accion-31ene' | 'informe-gestion-31ene' | 'pqrsd-trimestral' | 'control-interno-semestral' | 'calendario-tributario';
  mensaje: string;
  dias_hasta_plazo: number;
  documento_id: string | null;
  activa: boolean;
}

export interface TableroIta {
  porcentaje_cumplimiento: number;
  items_cumplen: number;
  items_no_cumplen: number;
  items_parcial: number;
  items_no_aplica: number;
  ultima_actualizacion: string;
  detalle: Array<{
    codigo: string;
    titulo: string;
    estado: 'cumple' | 'parcial' | 'no_cumple' | 'no_aplica';
    observacion: string | null;
  }>;
}
```

---

## 5. Tipos de componentes UI

```typescript
// app/types/ui.ts
export interface Enlace {
  etiqueta: string;
  url: string;
  externo: boolean;
  aria_label?: string;
}

export type VarianteBoton =
  | 'primario'
  | 'secundario'
  | 'terciario'
  | 'peligro'
  | 'exito'
  | 'texto';

export type TamanoBoton = 'pequeno' | 'mediano' | 'grande';

export interface ConfigPaginador {
  total: number;
  por_pagina: number;
  pagina_actual: number;
  url_base: string;
}
```

---

## 6. Composable de tipos: `useApi`

```typescript
// app/composables/useApi.ts
import type { paths, components } from '~/types/api';

export type GetEndpoint = keyof paths;
export type GetResponse<P extends GetEndpoint> =
  paths[P] extends { get: { responses: { 200: infer R } } }
    ? R extends { content: { 'application/vnd.api+json': infer T } }
      ? T
      : never
    : never;

export type PostEndpoint = {
  [K in keyof paths]: paths[K] extends { post: unknown } ? K : never
}[keyof paths];

export type PostBody<P extends PostEndpoint> =
  paths[P] extends { post: { requestBody: { content: { 'application/vnd.api+json': infer B } } } }
    ? B
    : never;

export type Schemas = components['schemas'];

// Uso:
const { data } = await useApi('/identidad/top-bar', { method: 'GET' });
// data tiene tipo GetResponse<'/identidad/top-bar'>
```

---

## 7. Tipos de composables

```typescript
// app/composables/useBusqueda.ts
export interface BusquedaOpciones {
  q: string;
  subseccion?: string;
  formato_abierto?: boolean;
}

export interface ResultadoBusqueda<T> {
  items: T[];
  total: number;
  termino_corregido: string | null;
  tiempo_ms: number;
}

export type Sugerencia = {
  texto: string;
  url: string;
  tipo_recurso: 'documento' | 'noticia' | 'tramite' | 'servidor';
  score: number;
};
```

---

## 8. Convenciones de tipos

| Aspecto | Convención |
|---|---|
| Interfaces | `PascalCase`, sin prefijo `I` |
| Types | `PascalCase` |
| Enums | `PascalCase` con union types literales |
| Props | `defineProps<PropsInterface>()` con macros tipadas |
| Emits | `defineEmits<{ (e: 'evento', payload: Tipo): void }>()` |
| Slots | `defineSlots<{ default(props: T): any }>()` |
| Genéricos | `<T extends { id: string }>` para reutilización |
| Inmutabilidad | `readonly` en propiedades cuando aplique |
| Nullable | `T \| null` explícito (no `T \| undefined` por defecto) |

---

## 9. Validación con Zod (en runtime)

Para validación adicional en runtime (formularios, etc.):

```typescript
import { z } from 'zod';

export const LoginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(12),
});

export type LoginInput = z.infer<typeof LoginSchema>;
```

---

## 10. ESLint y Prettier

Configuración de ESLint (`.eslintrc.cjs`):

```javascript
module.exports = {
  root: true,
  parser: 'vue-eslint-parser',
  parserOptions: {
    parser: '@typescript-eslint/parser',
    ecmaVersion: 2022,
    sourceType: 'module',
    extraFileExtensions: ['.vue'],
  },
  plugins: ['@typescript-eslint', 'vue', 'vuejs-accessibility'],
  extends: [
    'eslint:recommended',
    'plugin:@typescript-eslint/recommended',
    'plugin:vue/vue3-recommended',
    'plugin:vuejs-accessibility/recommended',
    'prettier',
  ],
  rules: {
    'vue/no-options-api': 'error',
    'vue/composition-api': 'error',
    'vuejs-accessibility/click-events-have-key-events': 'warn',
    'vuejs-accessibility/alt-text': 'warn',
    'vuejs-accessibility/aria-props': 'warn',
    '@typescript-eslint/no-explicit-any': 'warn',
    '@typescript-eslint/explicit-function-return-type': 'off',
    '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
  },
};
```

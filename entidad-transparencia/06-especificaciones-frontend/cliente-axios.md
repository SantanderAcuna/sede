# Cliente HTTP — `sitio/` (Nuxt 4 `$fetch`) + `panel/` (Vue 3 + Vite Axios)

> **Trazabilidad:** ADR-005 (OpenAPI), ADR-006 (Sanctum), RNF-SEG-01..03.
>
> **Patrón dual por cliente** (cada uno con su librería idiomática):
>
> | Cliente | Librería | Archivo | Justificación |
> |---|---|---|---|
> | `sitio/` (Nuxt 4 SSR) | **`$fetch` + `useFetch`** | `sitio/app/plugins/api.ts` | SSR-safe, evita re-hidratación, integra con el ciclo de vida de Nuxt. |
> | `panel/` (Vue 3 + Vite SPA) | **`axios`** | `panel/src/plugins/axios.ts` | Necesita interceptors complejos (CSRF, Bearer token, Idempotency-Key, 401 redirect). |
>
> Ambos envuelven su librería en un wrapper `useApi` con manejo de errores tipado.

---

## 1. Cliente `sitio/` (público, SSR)

### `app/plugins/api.ts`

```typescript
// app/plugins/api.ts
import { defineNuxtPlugin, useRuntimeConfig } from '#app';
import type { $Fetch } from 'ofetch';

export default defineNuxtPlugin((nuxtApp) => {
  const config = useRuntimeConfig();

  const api = $fetch.create({
    baseURL: config.public.apiBase as string,
    timeout: 10_000,
    headers: {
      'Accept': 'application/vnd.api+json',
      'Content-Type': 'application/vnd.api+json',
    },
    credentials: 'omit', // público, sin cookies
    retry: 2,
    retryDelay: 500,
    retryStatusCodes: [408, 429, 500, 502, 503, 504],
    onRequest({ request, options }) {
      // Request ID para trazabilidad
      const requestId = crypto.randomUUID();
      options.headers = {
        ...options.headers,
        'X-Request-ID': requestId,
      };
      nuxtApp.payload.requestId = requestId;
    },
    onResponseError({ request, response, options }) {
      if (import.meta.client) {
        console.error('[API Error]', {
          request,
          status: response.status,
          url: response.url,
        });
      }
    },
  }) as $Fetch;

  return {
    provide: { api },
  };
});
```

### `app/composables/useApi.ts`

```typescript
// app/composables/useApi.ts
import type { FetchOptions } from 'ofetch';
import type { paths } from '~/types/api';

type Path = keyof paths;
type Method = 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE';

export function useApi() {
  const { $api } = useNuxtApp();
  const config = useRuntimeConfig();

  async function call<P extends Path>(
    path: P,
    options: FetchOptions<'json'> & { method?: Method } = {},
  ) {
    return await $api(path, {
      ...options,
      headers: {
        ...options.headers,
        'Accept': 'application/vnd.api+json',
      },
    });
  }

  // Helpers tipados
  async function getDocumentos(params: {
    subseccion?: string;
    categoria?: string;
    vigencia?: number;
    page?: number;
    size?: number;
    include?: string[];
  } = {}) {
    const searchParams: Record<string, string> = {};
    if (params.subseccion) searchParams['filter[subseccion]'] = params.subseccion;
    if (params.categoria) searchParams['filter[categoria]'] = params.categoria;
    if (params.vigencia) searchParams['filter[vigencia]'] = String(params.vigencia);
    if (params.page) searchParams['page[number]'] = String(params.page);
    if (params.size) searchParams['page[size]'] = String(params.size);
    if (params.include) searchParams.include = params.include.join(',');

    return await call('/transparencia/documentos', { method: 'GET', query: searchParams });
  }

  return {
    call,
    getDocumentos,
  };
}
```

---

## 2. Cliente `panel/` (admin, SPA + Sanctum)

### `src/plugins/axios.ts`

```typescript
// src/plugins/axios.ts
import axios, { type AxiosInstance, type AxiosError } from 'axios';
import { useSesionStore } from '@/stores/sesion';

const API_BASE = import.meta.env.VITE_API_BASE as string;

export const http: AxiosInstance = axios.create({
  baseURL: `${API_BASE}/api/v1`,
  timeout: 15_000,
  headers: {
    'Accept': 'application/vnd.api+json',
    'Content-Type': 'application/vnd.api+json',
  },
  withCredentials: true, // Sanctum cookie
});

// Request interceptor
http.interceptors.request.use(
  (config) => {
    // X-Request-ID
    config.headers['X-Request-ID'] = crypto.randomUUID();

    // CSRF token desde cookie (Sanctum)
    const csrfToken = getCookie('XSRF-TOKEN');
    if (csrfToken) {
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken);
    }

    // Bearer token alternativo
    const sesion = useSesionStore();
    if (sesion.bearerToken) {
      config.headers['Authorization'] = `Bearer ${sesion.bearerToken}`;
    }

    // Idempotency-Key para operaciones críticas
    if (['POST', 'PATCH', 'PUT', 'DELETE'].includes(config.method?.toUpperCase() ?? '')) {
      if (!config.headers['Idempotency-Key']) {
        config.headers['Idempotency-Key'] = crypto.randomUUID();
      }
    }

    return config;
  },
  (error) => Promise.reject(error),
);

// Response interceptor
http.interceptors.response.use(
  (response) => {
    // Extraer request_id del header
    const requestId = response.headers['x-request-id'];
    if (requestId) {
      // Enviar a Sentry como contexto si hay error
    }
    return response;
  },
  async (error: AxiosError) => {
    if (error.response?.status === 419) {
      // CSRF token expirado, refrescar y reintentar una vez
      await refreshCsrfToken();
      return http.request(error.config!);
    }

    if (error.response?.status === 401) {
      const sesion = useSesionStore();
      sesion.limpiar();
      window.location.href = '/acceso/entrar';
    }

    if (error.response?.status === 429) {
      // Rate limit, mostrar toast
      const retryAfter = error.response.headers['retry-after'];
      useUiStore().mostrarToast({
        tipo: 'warning',
        mensaje: `Demasiadas solicitudes. Reintenta en ${retryAfter} segundos.`,
      });
    }

    return Promise.reject(error);
  },
);

// Helpers
function getCookie(name: string): string | null {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop()!.split(';').shift() ?? null;
  }
  return null;
}

async function refreshCsrfToken(): Promise<void> {
  await axios.get(`${API_BASE}/sanctum/csrf-cookie`, { withCredentials: true });
}
```

---

## 3. Manejo de errores en componentes

### `app/composables/useApiError.ts`

```typescript
// app/composables/useApiError.ts
import type { JsonApiErrorResponse, JsonApiError } from '~/types/common';

export function useApiError() {
  function extraerMensajes(error: unknown): string[] {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as JsonApiErrorResponse | undefined;
      if (data?.errors) {
        return data.errors.map((e) => e.detail ?? e.title);
      }
    }
    return ['Error desconocido'];
  }

  function obtenerCodigo(error: unknown): string | null {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as JsonApiErrorResponse | undefined;
      return data?.errors?.[0]?.code ?? null;
    }
    return null;
  }

  function esErrorValidacion(error: unknown): boolean {
    return obtenerCodigo(error) === 'VALIDATION_FAILED';
  }

  function obtenerErroresPorCampo(error: unknown): Record<string, string[]> {
    if (!axios.isAxiosError(error)) return {};
    const data = error.response?.data as JsonApiErrorResponse | undefined;
    if (!data?.errors) return {};

    const porCampo: Record<string, string[]> = {};
    for (const err of data.errors) {
      const campo = err.source?.pointer?.replace('/data/attributes/', '') ?? '_general';
      if (!porCampo[campo]) porCampo[campo] = [];
      porCampo[campo].push(err.detail ?? err.title);
    }
    return porCampo;
  }

  return {
    extraerMensajes,
    obtenerCodigo,
    esErrorValidacion,
    obtenerErroresPorCampo,
  };
}
```

### Uso en componente Vue

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useApi } from '~/composables/useApi';
import { useApiError } from '~/composables/useApiError';

const emit = defineEmits<{
  (e: 'publicado', documento: { id: string; slug: string }): void;
}>();

const { call } = useApi();
const { obtenerErroresPorCampo, extraerMensajes } = useApiError();

const archivo = ref<File | null>(null);
const titulo = ref('');
const descripcion = ref('');
const errores = ref<Record<string, string[]>>({});
const cargando = ref(false);

async function publicar() {
  errores.value = {};
  cargando.value = true;

  const formData = new FormData();
  formData.append('archivo', archivo.value!);
  formData.append('titulo', titulo.value);
  formData.append('descripcion', descripcion.value);

  try {
    const respuesta = await call('/panel/transparencia/documentos', {
      method: 'POST',
      body: formData,
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    emit('publicado', respuesta.data);
  } catch (e) {
    errores.value = obtenerErroresPorCampo(e);
    if (Object.keys(errores.value).length === 0) {
      errores.value._general = extraerMensajes(e);
    }
  } finally {
    cargando.value = false;
  }
}
</script>
```

---

## 4. Cache con TanStack Query (alternativa)

Para el panel, opcionalmente se usa `@tanstack/vue-query` para cache y revalidación:

```typescript
// src/composables/useDocumentos.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import { http } from '@/plugins/axios';

export function useDocumentos(filtros: Ref<{ subseccion?: string; estado?: string }>) {
  return useQuery({
    queryKey: ['documentos', filtros],
    queryFn: async () => {
      const { data } = await http.get('/panel/transparencia/documentos', { params: filtros.value });
      return data;
    },
    staleTime: 60_000, // 1 min
    gcTime: 5 * 60_000, // 5 min
  });
}

export function useCrearDocumento() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await http.post('/panel/transparencia/documentos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['documentos'] });
    },
  });
}
```

---

## 5. Tipos de respuesta

```typescript
// app/types/respuestas.ts
import type { paths } from '~/types/api';

export type DocumentoListResponse = paths['/transparencia/documentos']['get']['responses']['200']['content']['application/vnd.api+json'];
export type DocumentoShowResponse = paths['/transparencia/documentos/{slug}']['get']['responses']['200']['content']['application/vnd.api+json'];

// Helper para extraer el item de data
export type ItemDeRespuesta<T> = T extends { data: infer D } ? D : never;
export type DocumentoItem = ItemDeRespuesta<DocumentoListResponse>;
export type DocumentoDetalle = ItemDeRespuesta<DocumentoShowResponse>;
```

---

## 6. Validación con OpenAPI

En CI:

```bash
# Verificar que la spec y el backend están sincronizados
php artisan contract:verificar-drift
```

```php
// app/Console/Commands/ContractVerificarDrift.php
public function handle(): int
{
    $spec = file_get_contents(base_path('contract/openapi.yaml'));
    $parsed = Yaml::parse($spec);

    $rutasEnSpec = collect($parsed['paths'])->keys();
    $rutasEnCodigo = collect(Route::getRoutes())
        ->filter(fn($r) => str_starts_with($r->uri, 'api/v1/'))
        ->map(fn($r) => '/' . $r->uri);

    $faltantesEnSpec = $rutasEnCodigo->diff($rutasEnSpec);
    $faltantesEnCodigo = $rutasEnSpec->diff($rutasEnCodigo);

    if ($faltantesEnSpec->isNotEmpty() || $faltantesEnCodigo->isNotEmpty()) {
        $this->error("Drift detectado:");
        if ($faltantesEnSpec->isNotEmpty()) $this->line("Faltan en spec: " . $faltantesEnSpec->implode(', '));
        if ($faltantesEnCodigo->isNotEmpty()) $this->line("Faltan en código: " . $faltantesEnCodigo->implode(', '));
        return self::FAILURE;
    }

    $this->info("✓ Spec y código sincronizados");
    return self::SUCCESS;
}
```

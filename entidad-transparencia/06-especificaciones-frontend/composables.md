# Composables Reutilizables

> **Patrón:** Composition API + `<script setup>`; cada composable retorna estado y funciones tipadas.
> **Convención:** prefijo `use`; retorno siempre un objeto (no array); sin side-effects globales.
>
> **Ubicación según cliente:**
> - **Composables del `sitio/` (Nuxt 4):** `sitio/app/composables/*.ts` — auto-imported globalmente por Nuxt.
> - **Composables del `panel/` (Vue 3 + Vite):** `panel/src/composables/*.ts` — importados manualmente (`import { useXxx } from '@/composables/useXxx'`).
>
> **Convención de imports:**
> - En `sitio/`: el composable está disponible sin import gracias al auto-import de Nuxt.
> - En `panel/`: requiere import explícito con alias `@/composables/...`.

---

## 1. `useApi()` (sitio público)

```typescript
// app/composables/useApi.ts
import type { $Fetch } from 'ofetch';

export function useApi() {
  const { $api } = useNuxtApp();
  return { call: $api as $Fetch };
}
```

---

## 2. `useAuth()` (panel)

```typescript
// src/composables/useAuth.ts
import { computed } from 'vue';
import { useSesionStore } from '@/stores/sesion';
import type { RolUsuario } from '@/types/panel';

export function useAuth() {
  const sesion = useSesionStore();

  const autenticado = computed(() => sesion.autenticado);
  const usuario = computed(() => sesion.usuario);

  function tieneRol(rol: RolUsuario): boolean {
    return sesion.tieneRol(rol);
  }

  function tieneAlguno(roles: RolUsuario[]): boolean {
    return roles.some(r => sesion.tieneRol(r));
  }

  function puedeAcceder(recurso: string, accion: string): boolean {
    // Mapeo simple; en producción se consulta al backend
    const matriz: Record<string, RolUsuario[]> = {
      'documento:crear': ['editor', 'administrador'],
      'documento:editar': ['editor', 'administrador'],
      'documento:eliminar': ['administrador'],
      'documento:publicar': ['aprobador', 'administrador'],
      'usuario:gestionar': ['administrador'],
      'auditoria:ver': ['administrador', 'seguridad'],
      'ita:ver': ['administrador', 'editor', 'aprobador'],
    };
    const key = `${recurso}:${accion}`;
    const rolesPermitidos = matriz[key];
    if (!rolesPermitidos) return false;
    return tieneAlguno(rolesPermitidos);
  }

  return {
    autenticado,
    usuario,
    tieneRol,
    tieneAlguno,
    puedeAcceder,
    login: sesion.login,
    verificarMfa: sesion.verificarMfa,
    logout: sesion.logout,
  };
}
```

---

## 3. `useBusqueda()`

```typescript
// app/composables/useBusqueda.ts (sitio)
// src/composables/useBusqueda.ts (panel)
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useApi } from '~/composables/useApi';

export interface Sugerencia {
  texto: string;
  url: string;
  tipo_recurso: 'documento' | 'noticia' | 'tramite' | 'servidor';
  score: number;
}

export interface ResultadoBusqueda<T = unknown> {
  items: T[];
  total: number;
  termino_corregido: string | null;
  tiempo_ms: number;
}

export function useBusqueda(opciones: {
  endpoint?: string;
  minChars?: number;
  debounceMs?: number;
} = {}) {
  const minChars = opciones.minChars ?? 3;
  const debounceMs = opciones.debounceMs ?? 250;
  const endpoint = opciones.endpoint ?? '/buscar';

  const { call } = useApi();
  const termino = ref('');
  const sugerencias = ref<Sugerencia[]>([]);
  const resultados = ref<ResultadoBusqueda | null>(null);
  const cargando = ref(false);
  const error = ref<string | null>(null);

  const haySuficientesCaracteres = computed(() => termino.value.length >= minChars);

  const buscar = useDebounceFn(async () => {
    if (!haySuficientesCaracteres.value) {
      sugerencias.value = [];
      resultados.value = null;
      return;
    }
    cargando.value = true;
    error.value = null;
    try {
      const respuesta: any = await call(endpoint, {
        method: 'GET',
        query: { q: termino.value, autocompletar: true, limite: 10 },
      });
      sugerencias.value = respuesta.data.map((s: any) => s.attributes);
    } catch (e: any) {
      error.value = e?.message ?? 'Error en búsqueda';
      sugerencias.value = [];
    } finally {
      cargando.value = false;
    }
  }, debounceMs);

  async function ejecutarBusquedaCompleta(): Promise<ResultadoBusqueda | null> {
    if (!haySuficientesCaracteres.value) return null;
    cargando.value = true;
    try {
      const respuesta: any = await call(endpoint, {
        method: 'GET',
        query: { q: termino.value, autocompletar: false },
      });
      resultados.value = respuesta;
      return respuesta;
    } finally {
      cargando.value = false;
    }
  }

  function limpiar() {
    termino.value = '';
    sugerencias.value = [];
    resultados.value = null;
    error.value = null;
  }

  watch(termino, () => buscar());

  return {
    termino,
    sugerencias,
    resultados,
    cargando,
    error,
    haySuficientesCaracteres,
    ejecutarBusquedaCompleta,
    limpiar,
  };
}
```

---

## 4. `useFiltros()` para listados

```typescript
// app/composables/useFiltros.ts
import { ref, computed, watch } from 'vue';

export interface FiltroDefinicion<T = string> {
  clave: keyof T;
  tipo: 'texto' | 'select' | 'rango' | 'booleano';
  opciones?: Array<{ valor: string; etiqueta: string }>;
  default?: unknown;
}

export function useFiltros<T extends Record<string, unknown>>(
  definiciones: Record<keyof T, FiltroDefinicion<keyof T>>,
) {
  const filtros = ref<Partial<T>>({} as Partial<T>);

  // Inicializar con defaults
  for (const [clave, def] of Object.entries(definiciones)) {
    if (def.default !== undefined) {
      filtros.value[clave as keyof T] = def.default as T[keyof T];
    }
  }

  const hayFiltrosActivos = computed(() =>
    Object.values(filtros.value).some(v => v !== null && v !== undefined && v !== '')
  );

  function setFiltro(clave: keyof T, valor: T[keyof T] | null): void {
    if (valor === null || valor === '') {
      delete filtros.value[clave];
    } else {
      filtros.value[clave] = valor;
    }
  }

  function limpiar(): void {
    filtros.value = {} as Partial<T>;
  }

  function toQueryString(): string {
    const params = new URLSearchParams();
    for (const [clave, valor] of Object.entries(filtros.value)) {
      if (valor !== null && valor !== undefined && valor !== '') {
        params.set(`filter[${clave}]`, String(valor));
      }
    }
    return params.toString();
  }

  return {
    filtros,
    hayFiltrosActivos,
    setFiltro,
    limpiar,
    toQueryString,
  };
}
```

---

## 5. `usePaginador()`

```typescript
// app/composables/usePaginador.ts
import { ref, computed, watch } from 'vue';

export interface PaginadorOpciones {
  tamanoPorPagina?: number;
  totalInicial?: number;
}

export function usePaginador(opciones: PaginadorOpciones = {}) {
  const paginaActual = ref(1);
  const tamanoPorPagina = ref(opciones.tamanoPorPagina ?? 20);
  const total = ref(opciones.totalInicial ?? 0);

  const totalPaginas = computed(() =>
    Math.max(1, Math.ceil(total.value / tamanoPorPagina.value))
  );

  const tieneAnterior = computed(() => paginaActual.value > 1);
  const tieneSiguiente = computed(() => paginaActual.value < totalPaginas.value);

  const desde = computed(() =>
    (paginaActual.value - 1) * tamanoPorPagina.value + 1
  );
  const hasta = computed(() =>
    Math.min(paginaActual.value * tamanoPorPagina.value, total.value)
  );

  function irAPagina(n: number): void {
    if (n < 1 || n > totalPaginas.value) return;
    paginaActual.value = n;
  }

  function siguiente(): void {
    if (tieneSiguiente.value) paginaActual.value++;
  }

  function anterior(): void {
    if (tieneAnterior.value) paginaActual.value--;
  }

  function primera(): void {
    paginaActual.value = 1;
  }

  function ultima(): void {
    paginaActual.value = totalPaginas.value;
  }

  function resetear(): void {
    paginaActual.value = 1;
  }

  return {
    paginaActual,
    tamanoPorPagina,
    total,
    totalPaginas,
    tieneAnterior,
    tieneSiguiente,
    desde,
    hasta,
    irAPagina,
    siguiente,
    anterior,
    primera,
    ultima,
    resetear,
  };
}
```

---

## 6. `useWebVitals()` — métricas de performance en RUM

```typescript
// app/composables/useWebVitals.ts
import { onMounted } from 'vue';

type MetricaCallback = (metrica: { name: string; value: number; id: string }) => void;

export function useWebVitals(callback: MetricaCallback) {
  onMounted(() => {
    if (typeof window === 'undefined') return;

    // Cargar web-vitals dinámicamente (≈1KB)
    import('web-vitals').then(({ onLCP, onINP, onCLS, onFCP, onTTFB }) => {
      onLCP(m => callback({ name: 'LCP', value: m.value, id: m.id }));
      onINP(m => callback({ name: 'INP', value: m.value, id: m.id }));
      onCLS(m => callback({ name: 'CLS', value: m.value, id: m.id }));
      onFCP(m => callback({ name: 'FCP', value: m.value, id: m.id }));
      onTTFB(m => callback({ name: 'TTFB', value: m.value, id: m.id }));
    });
  });
}
```

Uso en una página:
```vue
<script setup lang="ts">
import { useWebVitals } from '~/composables/useWebVitals';

useWebVitals(({ name, value }) => {
  // Enviar a Google Analytics 4 o a un endpoint propio
  if (window.gtag) {
    window.gtag('event', name, { value: Math.round(value), metric_id: name });
  }
});
</script>
```

---

## 7. `useNotificaciones()` (toast UI)

```typescript
// app/composables/useNotificaciones.ts
import { useUiStore } from '~/stores/ui';

export type TipoToast = 'info' | 'success' | 'warning' | 'error';

export function useNotificaciones() {
  const ui = useUiStore();
  return {
    info: (mensaje: string, duracion = 4000) =>
      ui.mostrarToast({ tipo: 'info', mensaje, duracion_ms: duracion }),
    success: (mensaje: string, duracion = 3000) =>
      ui.mostrarToast({ tipo: 'success', mensaje, duracion_ms: duracion }),
    warning: (mensaje: string, duracion = 5000) =>
      ui.mostrarToast({ tipo: 'warning', mensaje, duracion_ms: duracion }),
    error: (mensaje: string, duracion = 6000) =>
      ui.mostrarToast({ tipo: 'error', mensaje, duracion_ms: duracion }),
  };
}
```

Uso:
```ts
const notify = useNotificaciones();
notify.success('Documento guardado');
notify.error('Error al guardar');
```

---

## 8. `useIdioma()` (preparación para i18n)

```typescript
// app/composables/useIdioma.ts
import { ref, computed } from 'vue';

type Locale = 'es-CO' | 'en-US';

const locale = ref<Locale>(
  (localStorage.getItem('idioma') as Locale) ?? 'es-CO'
);

export function useIdioma() {
  const esEspanol = computed(() => locale.value === 'es-CO');
  const esIngles = computed(() => locale.value === 'en-US');

  function cambiar(nuevo: Locale) {
    locale.value = nuevo;
    localStorage.setItem('idioma', nuevo);
    // Recargar para que SSR use el nuevo locale (futuro)
    window.location.reload();
  }

  function formatearFecha(fecha: string | Date, opciones?: Intl.DateTimeFormatOptions): string {
    return new Intl.DateTimeFormat(locale.value, opciones).format(new Date(fecha));
  }

  function formatearNumero(valor: number, opciones?: Intl.NumberFormatOptions): string {
    return new Intl.NumberFormat(locale.value, opciones).format(valor);
  }

  return {
    locale,
    esEspanol,
    esIngles,
    cambiar,
    formatearFecha,
    formatearNumero,
  };
}
```

---

## 9. `useCookiesConsent()` (RF-01-017)

```typescript
// app/composables/useCookiesConsent.ts
import { ref, computed } from 'vue';

type CategoriaCookie = 'esenciales' | 'analitica' | 'marketing';
type Consentimiento = Record<CategoriaCookie, boolean>;

const STORAGE_KEY = 'cd_cookies';

function cargar(): Consentimiento {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) return JSON.parse(raw);
  } catch {
    // Ignorar
  }
  return { esenciales: true, analitica: false, marketing: false };
}

const consentimiento = ref<Consentimiento>(cargar());

export function useCookiesConsent() {
  const mostrarBanner = computed(() => !consentimiento.value._visto);

  function aceptarTodas() {
    consentimiento.value = { esenciales: true, analitica: true, marketing: true, _visto: true } as any;
    persistir();
  }

  function rechazarOpcionales() {
    consentimiento.value = { esenciales: true, analitica: false, marketing: false, _visto: true } as any;
    persistir();
  }

  function configurar(cat: CategoriaCookie, valor: boolean) {
    consentimiento.value[cat] = valor;
    consentimiento.value._visto = true;
    persistir();
  }

  function persistir() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(consentimiento.value));
    // Disparar evento para que scripts de analítica se activen/desactiven
    window.dispatchEvent(new CustomEvent('cookies:consentimiento-cambiado', { detail: consentimiento.value }));
  }

  function tieneConsentimiento(cat: CategoriaCookie): boolean {
    return Boolean(consentimiento.value[cat]);
  }

  return {
    mostrarBanner,
    consentimiento,
    aceptarTodas,
    rechazarOpcionales,
    configurar,
    tieneConsentimiento,
  };
}
```

---

## 10. `useFocusTrap()` (accesibilidad modales)

```typescript
// app/composables/useFocusTrap.ts
import { onMounted, onBeforeUnmount, ref, type Ref } from 'vue';

const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function useFocusTrap(containerRef: Ref<HTMLElement | null>) {
  let primerFoco: HTMLElement | null = null;
  let ultimoFoco: HTMLElement | null = null;
  let focoAnterior: HTMLElement | null = null;

  function manejarTecla(e: KeyboardEvent) {
    if (e.key !== 'Tab' || !containerRef.value) return;
    const focusables = Array.from(
      containerRef.value.querySelectorAll<HTMLElement>(FOCUSABLE)
    ).filter(el => !el.hasAttribute('aria-hidden'));

    if (focusables.length === 0) return;
    primerFoco = focusables[0];
    ultimoFoco = focusables[focusables.length - 1];

    if (e.shiftKey && document.activeElement === primerFoco) {
      e.preventDefault();
      ultimoFoco?.focus();
    } else if (!e.shiftKey && document.activeElement === ultimoFoco) {
      e.preventDefault();
      primerFoco?.focus();
    }
  }

  onMounted(() => {
    focoAnterior = document.activeElement as HTMLElement;
    document.addEventListener('keydown', manejarTecla);
    // Foco al primer elemento focuseable
    setTimeout(() => {
      const focusables = containerRef.value?.querySelectorAll<HTMLElement>(FOCUSABLE);
      focusables?.[0]?.focus();
    }, 50);
  });

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', manejarTecla);
    focoAnterior?.focus();
  });
}
```

---

## 11. `useDebouncedRef()`

```typescript
// app/composables/useDebouncedRef.ts
import { customRef } from 'vue';

export function useDebouncedRef<T>(value: T, delay = 250) {
  let timeout: ReturnType<typeof setTimeout> | null = null;
  return customRef<T>((track, trigger) => ({
    get() {
      track();
      return value;
    },
    set(newValue: T) {
      if (timeout) clearTimeout(timeout);
      timeout = setTimeout(() => {
        value = newValue;
        trigger();
      }, delay);
    },
  }));
}
```

---

## 12. `useForm()` — formularios con validación

```typescript
// app/composables/useForm.ts
import { reactive, ref } from 'vue';

export interface ReglaValidacion<T> {
  validar: (valor: T) => true | string;
}

export function useForm<T extends Record<string, any>>(
  initial: T,
  reglas: Partial<Record<keyof T, ReglaValidacion<T[keyof T]>>>
) {
  const valores = reactive({ ...initial }) as T;
  const errores = ref<Partial<Record<keyof T, string>>>({});
  const cargando = ref(false);

  function validarCampo(clave: keyof T): boolean {
    const regla = reglas[clave];
    if (!regla) {
      delete errores.value[clave];
      return true;
    }
    const resultado = regla.validar(valores[clave]);
    if (resultado === true) {
      delete errores.value[clave];
      return true;
    }
    errores.value[clave] = resultado;
    return false;
  }

  function validarTodo(): boolean {
    let ok = true;
    for (const clave of Object.keys(reglas) as Array<keyof T>) {
      if (!validarCampo(clave)) ok = false;
    }
    return ok;
  }

  async function enviar(
    fn: (valores: T) => Promise<unknown>,
    opciones: { onSuccess?: (res: unknown) => void; onError?: (e: unknown) => void } = {}
  ) {
    if (!validarTodo()) return null;
    cargando.value = true;
    try {
      const resultado = await fn(valores);
      opciones.onSuccess?.(resultado);
      return resultado;
    } catch (e) {
      opciones.onError?.(e);
      throw e;
    } finally {
      cargando.value = false;
    }
  }

  return { valores, errores, cargando, validarCampo, validarTodo, enviar };
}
```

---

## 13. Mapa de composables y consumidores

| Composable | Consumido por |
|---|---|
| `useApi()` | Todos los composables que hacen HTTP |
| `useAuth()` | Layout panel, rutas protegidas, menús por rol |
| `useBusqueda()` | Buscador global, página de transparencia |
| `useFiltros()` | Listados (documentos, noticias, servidores) |
| `usePaginador()` | Todos los listados |
| `useWebVitals()` | Layout default (RUM) |
| `useNotificaciones()` | Cualquier componente que requiera feedback |
| `useIdioma()` | Layout default, páginas individuales |
| `useCookiesConsent()` | Layout default, scripts de analítica |
| `useFocusTrap()` | Modales, drawers, menús móviles |
| `useDebouncedRef()` | Buscadores, inputs con auto-guardado |
| `useForm()` | Formularios (login, edición de documentos, etc.) |

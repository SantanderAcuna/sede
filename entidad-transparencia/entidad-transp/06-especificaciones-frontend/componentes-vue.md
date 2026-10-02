# Componentes Vue (catálogo con `<script setup lang="ts">`)

> **Convención común a ambos clientes:** todos los componentes usan `<script setup lang="ts">` con macros tipadas (`defineProps<T>()`, `defineEmits<{...}>()`, `defineSlots<{...}>()`).
>
> **Ubicación según cliente:**
> - **Componentes del `sitio/` (Nuxt 4 público):** `sitio/app/components/**/*.vue` — auto-imported por Nuxt. Acceso en templates sin import explícito.
> - **Componentes del `panel/` (Vue 3 + Vite admin):** `panel/src/components/**/*.vue` — importados manualmente en cada vista.
>
> **Trazabilidad:** cada componente referencia el RF que implementa y el cliente al que pertenece.

---

## 1. `<TopBarGOVCO />` (sitio público) — RF-01-001

```vue
<!-- app/components/identidad/TopBarGOVCO.vue -->
<script setup lang="ts">
import { useFetch } from '#app';
import type { TopBar } from '~/types/identidad';

interface Props {
  alturaPx?: number;
}
const props = withDefaults(defineProps<Props>(), { alturaPx: 56 });

const { data: topBar } = await useFetch<TopBar>('/api/v1/identidad/top-bar', {
  key: 'top-bar',
  server: true,
  default: () => null,
});
</script>

<template>
  <div
    class="top-bar-govco"
    role="banner"
    aria-label="Barra superior del Estado colombiano"
    :style="{ height: `${alturaPx}px` }"
  >
    <a
      v-if="topBar"
      :href="topBar.attributes.url_govco"
      class="top-bar-govco__logo"
      :aria-label="'Ir a GOV.CO, portal del Estado colombiano'"
    >
      <img
        :src="topBar.attributes.logo_path"
        alt=""
        width="120"
        height="40"
      />
    </a>
    <ul class="top-bar-govco__items" role="list">
      <li v-for="item in topBar?.relationships?.items" :key="item.id">
        <a :href="item.attributes.url">{{ item.attributes.etiqueta }}</a>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.top-bar-govco {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  background-color: #0943B5;
  color: #fff;
  position: sticky;
  top: 0;
  z-index: 1000;
}
.top-bar-govco__logo {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  min-width: 44px;
}
.top-bar-govco__items {
  display: flex;
  gap: 1rem;
  list-style: none;
  margin: 0;
  padding: 0;
}
.top-bar-govco__items a {
  color: #fff;
  text-decoration: none;
  padding: 0.5rem;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}
</style>
```

---

## 2. `<FooterGOVCO />` (sitio público) — RF-01-002, RF-01-018

```vue
<!-- app/components/identidad/FooterGOVCO.vue -->
<script setup lang="ts">
import { useFetch } from '#app';
import type { Footer } from '~/types/identidad';

const { data: footer } = await useFetch<Footer>('/api/v1/identidad/footer', {
  key: 'footer',
  server: true,
});
</script>

<template>
  <footer v-if="footer" class="footer-govco" role="contentinfo" aria-label="Pie de página institucional">
    <div class="footer-govco__grid">
      <section class="footer-govco__brand">
        <img :src="'/logo-co.svg'" alt="Marca País Colombia" />
        <img :src="'/logo-govco.svg'" alt="GOV.CO" />
      </section>
      <section class="footer-govco__info">
        <h3>{{ footer.attributes.nombre_autoridad }}</h3>
        <p>NIT: {{ footer.attributes.nit }}</p>
        <address>
          {{ footer.attributes.direccion }}<br />
          {{ footer.attributes.municipio }}, {{ footer.attributes.departamento }}<br />
          {{ footer.attributes.codigo_postal }}
        </address>
        <p>{{ footer.attributes.horario }}</p>
      </section>
      <section class="footer-govco__contacto">
        <p>
          <strong>Conmutador:</strong>
          <a :href="`tel:${footer.attributes.commutador.replace(/\s/g, '')}`">
            {{ footer.attributes.commutador }}
          </a>
        </p>
        <p v-if="footer.attributes.linea_anticorrupcion">
          <strong>Línea Anticorrupción:</strong>
          {{ footer.attributes.linea_anticorrupcion }}
        </p>
        <p>
          <strong>Correo:</strong>
          <a :href="`mailto:${footer.attributes.correo_institucional}`">
            {{ footer.attributes.correo_institucional }}
          </a>
        </p>
      </section>
      <nav class="footer-govco__enlaces" aria-label="Enlaces del pie">
        <ul role="list">
          <li v-for="item in footer.relationships?.items?.data" :key="item.id">
            <a :href="item.attributes.url">
              {{ item.attributes.etiqueta }}
            </a>
          </li>
        </ul>
      </nav>
    </div>
    <div class="footer-govco__bottom">
      <p>&copy; {{ new Date().getFullYear() }} Alcaldía Distrital de Santa Marta. Todos los derechos reservados.</p>
    </div>
  </footer>
</template>

<style scoped>
.footer-govco {
  background-color: #00568D;
  color: #fff;
  padding: 3rem 1.5rem 1rem;
  margin-top: 4rem;
}
.footer-govco__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.footer-govco__enlaces a {
  color: #fff;
  text-decoration: underline;
}
.footer-govco__bottom {
  border-top: 1px solid rgba(255,255,255,0.2);
  margin-top: 2rem;
  padding-top: 1rem;
  text-align: center;
}
</style>
```

---

## 3. `<MenuPrincipal />` (sitio público) — RF-01-007, RF-01-008

```vue
<!-- app/components/identidad/MenuPrincipal.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue';
import { useTransparenciaStore } from '~/stores/transparencia';
import { useAccesibilidadStore } from '~/stores/accesibilidad';

interface MenuItem {
  id: string;
  slug: string;
  etiqueta: string;
  ruta: string | null;
  tipo: 'interno' | 'externo' | 'ancla' | 'modal';
  hijos: MenuItem[];
}

interface Props {
  items: MenuItem[];
  viewport: 'desktop' | 'tablet' | 'mobile';
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'navigate', ruta: string): void;
}>();

const abiertoMobile = ref(false);
const itemAbierto = ref<string | null>(null);

const trans = useTransparenciaStore();
await trans.cargarSubsecciones();

const itemsConSubsecciones = computed(() => {
  return props.items.map(item => {
    if (item.slug === 'transparencia') {
      return {
        ...item,
        hijos: trans.subsecciones.map(s => ({
          id: s.id,
          slug: s.codigo,
          etiqueta: s.nombre,
          ruta: `/transparencia/${s.codigo}`,
          tipo: 'interno' as const,
          hijos: [],
        })),
      };
    }
    return item;
  });
});

function toggleSubmenu(id: string): void {
  itemAbierto.value = itemAbierto.value === id ? null : id;
}

function cerrarMenu(): void {
  itemAbierto.value = null;
  abiertoMobile.value = false;
}

function handleClick(item: MenuItem): void {
  if (item.tipo === 'externo') {
    // Mostrar modal de aviso (RF-01-016)
    emit('navigate', item.ruta!);
  } else {
    emit('navigate', item.ruta!);
  }
  cerrarMenu();
}
</script>

<template>
  <nav
    class="menu-principal"
    aria-label="Menú principal"
    :class="{ 'menu-principal--mobile': props.viewport === 'mobile' }"
  >
    <button
      v-if="props.viewport === 'mobile'"
      type="button"
      class="menu-principal__hamburguesa"
      :aria-expanded="abiertoMobile"
      aria-controls="menu-principal-lista"
      @click="abiertoMobile = !abiertoMobile"
    >
      <span class="sr-only">{{ abiertoMobile ? 'Cerrar menú' : 'Abrir menú' }}</span>
      <span aria-hidden="true">{{ abiertoMobile ? '✕' : '☰' }}</span>
    </button>

    <ul
      v-show="abiertoMobile || props.viewport !== 'mobile'"
      id="menu-principal-lista"
      class="menu-principal__lista"
      role="list"
    >
      <li
        v-for="item in itemsConSubsecciones"
        :key="item.id"
        class="menu-principal__item"
        :class="{ 'menu-principal__item--activo': itemAbierto === item.id }"
      >
        <a
          v-if="item.hijos.length === 0"
          :href="item.ruta ?? '#'"
          class="menu-principal__enlace"
          @click.prevent="handleClick(item)"
          :aria-current="$route.path === item.ruta ? 'page' : undefined"
        >
          {{ item.etiqueta }}
        </a>

        <template v-else>
          <button
            type="button"
            class="menu-principal__enlace menu-principal__enlace--boton"
            :aria-expanded="itemAbierto === item.id"
            :aria-controls="`submenu-${item.id}`"
            @click="toggleSubmenu(item.id)"
          >
            {{ item.etiqueta }}
            <span aria-hidden="true" class="menu-principal__flecha">
              {{ itemAbierto === item.id ? '▲' : '▼' }}
            </span>
          </button>

          <ul
            v-show="itemAbierto === item.id"
            :id="`submenu-${item.id}`"
            class="menu-principal__submenu"
            role="list"
          >
            <li v-for="hijo in item.hijos" :key="hijo.id">
              <a
                :href="hijo.ruta ?? '#'"
                @click.prevent="handleClick(hijo)"
                :aria-current="$route.path === hijo.ruta ? 'page' : undefined"
              >
                {{ hijo.etiqueta }}
              </a>
            </li>
          </ul>
        </template>
      </li>
    </ul>
  </nav>
</template>

<style scoped>
.menu-principal__lista {
  display: flex;
  gap: 0.5rem;
  list-style: none;
  margin: 0;
  padding: 0;
}
.menu-principal__enlace {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  min-height: 44px;
  padding: 0.5rem 1rem;
  color: #1A1A1A;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  font-family: inherit;
}
.menu-principal__enlace:hover,
.menu-principal__enlace[aria-current="page"] {
  text-decoration: underline;
  font-weight: 700;
}
.menu-principal__submenu {
  position: absolute;
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border-radius: 4px;
  padding: 0.5rem 0;
  min-width: 280px;
  list-style: none;
}
.menu-principal__item {
  position: relative;
}
.menu-principal__hamburguesa {
  min-width: 44px;
  min-height: 44px;
  font-size: 1.5rem;
  background: none;
  border: 1px solid #0943B5;
  border-radius: 4px;
}
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border: 0;
}
</style>
```

---

## 4. `<TarjetaDocumento />` — RF-02-002, RF-02-024

```vue
<!-- app/components/transparencia/TarjetaDocumento.vue -->
<script setup lang="ts">
interface Props {
  documento: {
    id: string;
    slug: string;
    titulo: string;
    descripcion: string | null;
    fecha_publicacion: string | null;
    periodicidad: string;
    hash_sha256: string;
    tamano_bytes: number;
    formato_abierto: boolean;
  };
  mostrarHash?: boolean;
}

const props = withDefaults(defineProps<Props>(), { mostrarHash: false });

const fechaFormateada = computed(() => {
  if (!props.documento.fecha_publicacion) return '';
  return new Intl.DateTimeFormat('es-CO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(props.documento.fecha_publicacion));
});

const tamanoLegible = computed(() => {
  const bytes = props.documento.tamano_bytes;
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
});
</script>

<template>
  <article class="tarjeta-documento" :aria-labelledby="`doc-${documento.id}-titulo`">
    <header class="tarjeta-documento__cabecera">
      <h3 :id="`doc-${documento.id}-titulo`" class="tarjeta-documento__titulo">
        <a :href="`/transparencia/${documento.slug}`">
          {{ documento.titulo }}
        </a>
      </h3>
      <p v-if="fechaFormateada" class="tarjeta-documento__fecha">
        <time :datetime="documento.fecha_publicacion ?? ''">{{ fechaFormateada }}</time>
      </p>
    </header>

    <p v-if="documento.descripcion" class="tarjeta-documento__descripcion">
      {{ documento.descripcion }}
    </p>

    <footer class="tarjeta-documento__pie">
      <ul class="tarjeta-documento__metadatos" role="list">
        <li>
          <strong>Periodicidad:</strong> {{ documento.periodicidad }}
        </li>
        <li>
          <strong>Tamaño:</strong> {{ tamanoLegible }}
        </li>
        <li v-if="documento.formato_abierto">
          <span class="badge badge--ok">Formato abierto</span>
        </li>
      </ul>

      <details v-if="mostrarHash" class="tarjeta-documento__hash">
        <summary>Verificar integridad (SHA-256)</summary>
        <code class="tarjeta-documento__hash-valor">{{ documento.hash_sha256 }}</code>
      </details>
    </footer>
  </article>
</template>

<style scoped>
.tarjeta-documento {
  border: 1px solid #E5E5E5;
  border-radius: 8px;
  padding: 1.5rem;
  background: #fff;
}
.tarjeta-documento__titulo a {
  color: #0943B5;
  text-decoration: none;
}
.tarjeta-documento__titulo a:hover,
.tarjeta-documento__titulo a:focus {
  text-decoration: underline;
}
.tarjeta-documento__hash-valor {
  font-family: ui-monospace, 'SF Mono', Menlo, monospace;
  font-size: 0.75rem;
  word-break: break-all;
  display: block;
  padding: 0.5rem;
  background: #F5F5F5;
  border-radius: 4px;
}
.badge--ok {
  background: #16A34A;
  color: #fff;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
}
</style>
```

---

## 5. `<BuscadorTransparencia />` — RF-02-027

```vue
<!-- app/components/transparencia/BuscadorTransparencia.vue -->
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useApi } from '~/composables/useApi';

interface Sugerencia {
  texto: string;
  url: string;
  tipo_recurso: string;
  score: number;
}

interface Props {
  placeholder?: string;
  subseccion?: string;
  autocompletarMinChars?: number;
  limiteSugerencias?: number;
}
const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Buscar en transparencia...',
  autocompletarMinChars: 3,
  limiteSugerencias: 10,
});

const emit = defineEmits<{
  (e: 'buscar', termino: string): void;
  (e: 'seleccionar', sugerencia: Sugerencia): void;
}>();

const { call } = useApi();
const termino = ref('');
const sugerencias = ref<Sugerencia[]>([]);
const mostrarSugerencias = ref(false);
const cargando = ref(false);
const activoIdx = ref(-1);

const buscar = useDebounceFn(async () => {
  if (termino.value.length < props.autocompletarMinChars) {
    sugerencias.value = [];
    return;
  }
  cargando.value = true;
  try {
    const respuesta: any = await call('/buscar', {
      method: 'GET',
      query: { q: termino.value, autocompletar: true, limite: props.limiteSugerencias },
    });
    sugerencias.value = respuesta.data.map((s: any) => s.attributes);
  } finally {
    cargando.value = false;
  }
}, 250);

watch(termino, () => {
  mostrarSugerencias.value = true;
  activoIdx.value = -1;
  buscar();
});

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    activoIdx.value = Math.min(activoIdx.value + 1, sugerencias.value.length - 1);
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    activoIdx.value = Math.max(activoIdx.value - 1, -1);
  } else if (e.key === 'Enter') {
    if (activoIdx.value >= 0 && sugerencias.value[activoIdx.value]) {
      emit('seleccionar', sugerencias.value[activoIdx.value]);
    } else {
      emit('buscar', termino.value);
    }
    mostrarSugerencias.value = false;
  } else if (e.key === 'Escape') {
    mostrarSugerencias.value = false;
  }
}

function seleccionar(s: Sugerencia) {
  emit('seleccionar', s);
  mostrarSugerencias.value = false;
  termino.value = s.texto;
}
</script>

<template>
  <div class="buscador" role="search">
    <label for="buscador-input" class="sr-only">Buscar en transparencia</label>
    <input
      id="buscador-input"
      v-model="termino"
      type="search"
      :placeholder="placeholder"
      role="combobox"
      :aria-expanded="mostrarSugerencias"
      aria-controls="buscador-lista"
      :aria-activedescendant="activoIdx >= 0 ? `sug-${activoIdx}` : undefined"
      autocomplete="off"
      @keydown="onKeydown"
      @focus="mostrarSugerencias = true"
      @blur="setTimeout(() => mostrarSugerencias = false, 200)"
    />
    <button type="button" aria-label="Buscar" @click="emit('buscar', termino)">
      <span aria-hidden="true">🔍</span>
    </button>

    <ul
      v-if="mostrarSugerencias && sugerencias.length > 0"
      id="buscador-lista"
      class="buscador__sugerencias"
      role="listbox"
    >
      <li
        v-for="(s, idx) in sugerencias"
        :id="`sug-${idx}`"
        :key="idx"
        role="option"
        :aria-selected="activoIdx === idx"
        :class="{ 'buscador__sugerencia--activa': activoIdx === idx }"
        @mousedown.prevent="seleccionar(s)"
      >
        <a :href="s.url">{{ s.texto }}</a>
        <span class="buscador__tipo">{{ s.tipo_recurso }}</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.buscador {
  position: relative;
  display: flex;
  gap: 0.5rem;
}
.buscador input {
  flex: 1;
  min-height: 44px;
  padding: 0 1rem;
  font-size: 1rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.buscador__sugerencias {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #ccc;
  border-radius: 4px;
  list-style: none;
  margin: 0.25rem 0 0;
  padding: 0;
  max-height: 400px;
  overflow-y: auto;
  z-index: 100;
}
.buscador__sugerencias li {
  padding: 0.75rem 1rem;
  cursor: pointer;
}
.buscador__sugerencia--activa {
  background: #0943B5;
  color: #fff;
}
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border: 0;
}
</style>
```

---

## 6. `<BarraAccesibilidad />` — RNF-ACES-02

```vue
<!-- app/components/accesibilidad/BarraAccesibilidad.vue -->
<script setup lang="ts">
import { useAccesibilidadStore } from '~/stores/accesibilidad';

const acc = useAccesibilidadStore();

function cambiarContraste() {
  acc.contraste = acc.contraste === 'normal' ? 'alto' : acc.contraste === 'alto' ? 'invertido' : 'normal';
}
function aumentarFuente() {
  if (acc.tamanoFuente === 'normal') acc.tamanoFuente = 'grande';
  else if (acc.tamanoFuente === 'grande') acc.tamanoFuente = 'muy-grande';
}
function reducirFuente() {
  if (acc.tamanoFuente === 'muy-grande') acc.tamanoFuente = 'grande';
  else if (acc.tamanoFuente === 'grande') acc.tamanoFuente = 'normal';
}
</script>

<template>
  <aside
    class="barra-accesibilidad d-none d-lg-flex"
    aria-label="Barra de accesibilidad"
    role="region"
  >
    <button
      type="button"
      class="barra-accesibilidad__btn"
      :aria-pressed="acc.contraste !== 'normal'"
      @click="cambiarContraste"
    >
      Contraste
    </button>
    <button type="button" class="barra-accesibilidad__btn" @click="reducirFuente">
      Reducir letra (A-)
    </button>
    <button type="button" class="barra-accesibilidad__btn" @click="aumentarFuente">
      Aumentar letra (A+)
    </button>
    <button
      type="button"
      class="barra-accesibilidad__btn"
      :aria-pressed="acc.espaciadoAmplio"
      @click="acc.espaciadoAmplio = !acc.espaciadoAmplio"
    >
      Espaciado
    </button>
    <button
      type="button"
      class="barra-accesibilidad__btn"
      :aria-pressed="acc.modoLectura"
      @click="acc.modoLectura = !acc.modoLectura"
    >
      Modo lectura
    </button>
    <button type="button" class="barra-accesibilidad__btn" @click="acc.resetear">
      Restablecer
    </button>
  </aside>
</template>
```

---

## 7. `<EditorDocumento />` (panel)

```vue
<!-- src/components/documentos/EditorDocumento.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue';
import { useDocumentosStore } from '@/stores/documentos';
import { useUiStore } from '@/stores/ui';
import { useApiError } from '@/composables/useApiError';

interface Props {
  documentoId?: string;
  subsecciones: Array<{ id: string; codigo: string; nombre: string }>;
  categorias: Array<{ id: string; codigo: string; nombre: string }>;
  tiposDocumento: Array<{ id: string; codigo: string; nombre: string }>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'guardado', id: string): void;
  (e: 'cancelado'): void;
}>();

const docsStore = useDocumentosStore();
const ui = useUiStore();
const { obtenerErroresPorCampo } = useApiError();

const archivo = ref<File | null>(null);
const titulo = ref('');
const descripcion = ref('');
const subseccionId = ref<number | null>(null);
const categoriaId = ref<number | null>(null);
const tipoDocumentoId = ref<number | null>(null);
const fechaPublicacion = ref<string>(new Date().toISOString().split('T')[0]);
const periodicidad = ref<'anual' | 'semestral' | 'trimestral' | 'mensual' | 'eventual'>('eventual');
const destacado = ref(false);
const errores = ref<Record<string, string[]>>({});
const cargando = ref(false);

const lecturabilidadWarning = computed(() => {
  // Validación simple en frontend (backend también valida con cálculo completo)
  if (descripcion.value.length === 0) return null;
  const palabrasLargas = (descripcion.value.match(/\b\w{5,}\b/g) ?? []).length;
  const total = descripcion.value.split(/\s+/).length;
  const ratio = palabrasLargas / Math.max(total, 1);
  if (ratio > 0.4) {
    return 'La descripción puede ser difícil de leer. Considera simplificar.';
  }
  return null;
});

async function guardar(estado: 'borrador' | 'publicado') {
  if (!archivo.value && !props.documentoId) {
    errores.value.archivo = ['Debes seleccionar un archivo'];
    return;
  }
  errores.value = {};
  cargando.value = true;

  const formData = new FormData();
  if (archivo.value) formData.append('archivo', archivo.value);
  formData.append('titulo', titulo.value);
  formData.append('descripcion', descripcion.value);
  formData.append('subseccion_id', String(subseccionId.value));
  if (categoriaId.value) formData.append('categoria_id', String(categoriaId.value));
  formData.append('tipo_documento_id', String(tipoDocumentoId.value));
  formData.append('fecha_publicacion', fechaPublicacion.value);
  formData.append('periodicidad', periodicidad.value);
  formData.append('destacado', String(destacado.value));
  formData.append('estado', estado);

  try {
    const resultado = props.documentoId
      ? await docsStore.actualizar(props.documentoId, formData)
      : await docsStore.subir(formData);
    ui.mostrarToast({ tipo: 'success', mensaje: 'Documento guardado', duracion_ms: 3000 });
    emit('guardado', resultado.id);
  } catch (e: unknown) {
    errores.value = obtenerErroresPorCampo(e);
    ui.mostrarToast({ tipo: 'error', mensaje: 'Error al guardar', duracion_ms: 5000 });
  } finally {
    cargando.value = false;
  }
}
</script>

<template>
  <form class="editor-documento" @submit.prevent="guardar('borrador')">
    <div class="campo">
      <label for="archivo">Archivo *</label>
      <input
        id="archivo"
        type="file"
        accept=".pdf,.xlsx,.csv,.json,.rdf,.odt,.ods,.odp,.html,.xml"
        :aria-invalid="!!errores.archivo"
        :aria-describedby="errores.archivo ? 'archivo-error' : undefined"
        @change="archivo = ($event.target as HTMLInputElement).files?.[0] ?? null"
      />
      <p v-if="errores.archivo" id="archivo-error" class="error" role="alert">
        {{ errores.archivo.join(', ') }}
      </p>
    </div>

    <div class="campo">
      <label for="titulo">Título *</label>
      <input
        id="titulo"
        v-model="titulo"
        type="text"
        maxlength="300"
        required
        :aria-invalid="!!errores.titulo"
      />
      <p v-if="errores.titulo" class="error" role="alert">{{ errores.titulo.join(', ') }}</p>
    </div>

    <div class="campo">
      <label for="descripcion">Descripción</label>
      <textarea
        id="descripcion"
        v-model="descripcion"
        rows="4"
        :aria-invalid="!!errores.descripcion"
      />
      <p v-if="errores.descripcion" class="error" role="alert">{{ errores.descripcion.join(', ') }}</p>
      <p v-if="lecturabilidadWarning" class="advertencia" role="status">
        {{ lecturabilidadWarning }}
      </p>
    </div>

    <div class="campo">
      <label for="subseccion">Subsección *</label>
      <select id="subseccion" v-model="subseccionId" required>
        <option :value="null" disabled>Selecciona una subsección</option>
        <option v-for="s in subsecciones" :key="s.id" :value="Number(s.id)">
          {{ s.nombre }}
        </option>
      </select>
    </div>

    <div class="campo">
      <label for="categoria">Categoría</label>
      <select id="categoria" v-model="categoriaId">
        <option :value="null">Sin categoría</option>
        <option v-for="c in categorias" :key="c.id" :value="Number(c.id)">
          {{ c.nombre }}
        </option>
      </select>
    </div>

    <div class="campo">
      <label for="tipo">Tipo de documento *</label>
      <select id="tipo" v-model="tipoDocumentoId" required>
        <option :value="null" disabled>Selecciona un tipo</option>
        <option v-for="t in tiposDocumento" :key="t.id" :value="Number(t.id)">
          {{ t.nombre }}
        </option>
      </select>
    </div>

    <div class="campo">
      <label for="fecha">Fecha de publicación *</label>
      <input id="fecha" v-model="fechaPublicacion" type="date" required />
    </div>

    <div class="campo">
      <label for="periodicidad">Periodicidad *</label>
      <select id="periodicidad" v-model="periodicidad" required>
        <option value="anual">Anual</option>
        <option value="semestral">Semestral</option>
        <option value="trimestral">Trimestral</option>
        <option value="mensual">Mensual</option>
        <option value="eventual">Eventual</option>
      </select>
    </div>

    <div class="campo campo--checkbox">
      <input id="destacado" v-model="destacado" type="checkbox" />
      <label for="destacado">Documento destacado</label>
    </div>

    <footer class="editor-documento__acciones">
      <button type="button" @click="emit('cancelado')" :disabled="cargando">
        Cancelar
      </button>
      <button type="submit" :disabled="cargando">
        {{ cargando ? 'Guardando...' : 'Guardar borrador' }}
      </button>
      <button type="button" @click="guardar('publicado')" :disabled="cargando">
        Publicar
      </button>
    </footer>
  </form>
</template>

<style scoped>
.editor-documento {
  display: grid;
  gap: 1rem;
  max-width: 800px;
}
.campo {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.campo label {
  font-weight: 600;
}
.campo input,
.campo select,
.campo textarea {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
  min-height: 44px;
}
.error {
  color: #DC2626;
  font-size: 0.875rem;
}
.advertencia {
  color: #F59E0B;
  font-size: 0.875rem;
}
.editor-documento__acciones {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  border-top: 1px solid #ccc;
  padding-top: 1rem;
  margin-top: 1rem;
}
</style>
```

---

## 8. Componentes UI base (resumen)

| Componente | Propósito | Notas accesibilidad |
|---|---|---|
| `<Btn>` | Botón con variantes | `aria-label`, focus-visible 3px |
| `<Card>` | Tarjeta con cabecera/cuerpo/pie | `role="article"` cuando aplique |
| `<Modal>` | Modal con focus-trap | `role="dialog"`, `aria-modal="true"` |
| `<Toast>` | Notificación | `role="status"` o `role="alert"` |
| `<Paginador>` | Paginación accesible | `aria-current="page"` |
| `<Spinner>` | Indicador de carga | `role="status"`, `aria-live="polite"` |
| `<Acordeon>` | Acordeón colapsable | `aria-expanded`, `aria-controls` |
| `<Tabla>` | Tabla accesible | `<caption>`, scope en headers |
| `<EnlaceExterno />` | Enlace externo con aviso | `target="_blank"` + aviso (RF-01-016) |
| `<VolverArriba />` | Botón scroll to top | `aria-label="Volver arriba"` |

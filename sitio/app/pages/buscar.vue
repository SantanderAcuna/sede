<script setup lang="ts">
/**
 * Resultados de la búsqueda (T-22, E3-3 · RF-B1-005, RF-B1-006, RF-B3-043).
 *
 * Sigue `docs/busqueda-interaccion.md` §5. Lo que la página junta:
 *
 *  1. **Trámites y políticas**, desde `GET /buscar` de la API: por pertinencia,
 *     con tolerancia a tildes y erratas, paginado de a 10 y filtrable por tipo.
 *     La consulta la hace el **servidor del sitio** (ADR-0018: su IP es la del
 *     visitante), de modo que la página llega completa en el HTML y funciona sin
 *     JavaScript: el formulario envía por GET, el filtro y la paginación son
 *     enlaces.
 *  2. **Secciones de la sede**, sobre las rutas publicadas de `config/sitemap.ts`
 *     (D-I10): un grupo aparte, sólo con el filtro «Todo» y sólo en la primera
 *     página. Cuentan para el total del resumen aunque se pagine.
 *
 * **Lo que no se hace.** `autor` y `miniatura` valen `null` hoy y no se dibuja
 * nada por ellos, ni un hueco. Lo escrito en el campo no se guarda en el
 * navegador, no va a la analítica y no se escribe en la consola (§6).
 */
import { todasLasRutas } from '~/config/sitemap'
import {
  analizarResultados,
  estadoDelTermino,
  etiquetaDeFecha,
  leerFiltro,
  leerPagina,
  limpiarTermino,
  RESULTADOS_POR_PAGINA,
  recortarExtracto,
  textoResumen,
  type FiltroTipo,
  type PaginaDeResultados,
} from '~/composables/resultadosBusqueda'
import { segundosDeEspera } from '~/composables/useSugerenciasBusqueda'

/** Lo que dejó la consulta a la API. */
type Resultado =
  | { tipo: 'pagina'; pagina: PaginaDeResultados }
  | { tipo: 'limitado'; segundos: number }
  | { tipo: 'invalida' }
  | { tipo: 'error' }

/**
 * Textos de §7, en un solo sitio para que Jurídica y comunicaciones los revisen.
 * La redacción en lenguaje claro no la ha revisado nadie de la Entidad.
 */
const TEXTOS = {
  titulo: 'Resultados de la búsqueda',
  sinTermino: 'Escriba lo que busca: un trámite, una política o un servicio.',
  corta: 'Escriba al menos 3 caracteres para buscar.',
  larga: 'Use menos de 120 caracteres.',
  buscando: 'Buscando…',
  error: 'No pudimos realizar la búsqueda en este momento. Intente de nuevo en unos minutos.',
  errorConSecciones:
    'El catálogo de trámites y políticas no respondió, así que solo se muestran las secciones de la sede que coinciden.',
  limitado: (n: number) =>
    `Hizo muchas búsquedas seguidas. Espere ${n} ${n === 1 ? 'segundo' : 'segundos'} e intente de nuevo.`,
  sinResultadosTitulo: (q: string) => `No encontramos resultados para «${q}».`,
  sinResultadosAyuda: 'Revise la ortografía o pruebe con otras palabras. También puede',
  verTramites: 'ver todos los trámites',
}

const FILTROS: { valor: FiltroTipo; etiqueta: string }[] = [
  { valor: '', etiqueta: 'Todo' },
  { valor: 'tramite', etiqueta: 'Trámites' },
  { valor: 'politica', etiqueta: 'Políticas' },
]

const ruta = useRoute()
const enrutador = useRouter()

const termino = computed(() => limpiarTermino(ruta.query.q))
const filtro = computed(() => leerFiltro(ruta.query.tipo))
const pagina = computed(() => leerPagina(ruta.query.page))
const estadoTermino = computed(() => estadoDelTermino(termino.value))

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()

/**
 * Pide los resultados. Sólo se llama a la API con un término que ya cumple de 3
 * a 120 caracteres: la misma validación que hace ella, para no gastar una
 * petición (ni un punto del límite) en algo que va a devolver 422.
 */
async function consultar(): Promise<Resultado | null> {
  if (estadoTermino.value !== 'valida') return null
  // `useRequestFetch()` es, en el servidor, un `$fetch` envuelto que no tiene `.raw`:
  // el estado y las cabeceras se leen con `onResponse`, que funciona igual en los dos.
  let estado = 0
  let reintentarEn: string | null = null
  try {
    const cuerpo: unknown = await traer(`${urlApi}/buscar`, {
      query: {
        q: termino.value,
        tipo: filtro.value === '' ? undefined : filtro.value,
        page: pagina.value,
        per_page: RESULTADOS_POR_PAGINA,
      },
      // 422 y 429 son respuestas que se explican, no excepciones.
      ignoreResponseError: true,
      onResponse({ response }) {
        estado = response.status
        reintentarEn = response.headers.get('Retry-After')
      },
    })
    if (estado === 429) return { tipo: 'limitado', segundos: segundosDeEspera(reintentarEn, Date.now()) }
    if (estado === 422) return { tipo: 'invalida' }
    const leida = estado === 200 ? analizarResultados(cuerpo) : null
    return leida === null ? { tipo: 'error' } : { tipo: 'pagina', pagina: leida }
  } catch {
    return { tipo: 'error' }
  }
}

// `useAsyncData` y no una petición suelta: se calcula en el servidor y viaja en el
// HTML, y se repite sola cuando cambia el término, el filtro o la página. Mientras
// se repite, `data` conserva lo anterior: el contenido se atenúa, no desaparece.
const { data: resultado, status, refresh } = await useAsyncData<Resultado | null>(
  'busqueda-resultados',
  consultar,
  { watch: [termino, filtro, pagina] },
)

const buscando = computed(() => status.value === 'pending')

/**
 * Índice de las secciones que publican contenido: las de la configuración de
 * rutas, que alimenta el menú, el mapa del sitio y el `sitemap.xml`. Una sección
 * que pasa a publicada entra sola.
 */
function normalizar(texto: string): string {
  return texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
}

const secciones = todasLasRutas()
  .filter((rutaPublica) => rutaPublica.publicada && rutaPublica.ruta !== '/buscar')
  .map((rutaPublica) => ({
    etiqueta: rutaPublica.etiqueta,
    ruta: rutaPublica.ruta,
    comparable: normalizar(`${rutaPublica.etiqueta} ${rutaPublica.ruta}`),
  }))

/** Sólo con «Todo»: con un filtro de tipo no aparecen ni cuentan. */
const seccionesEncontradas = computed(() => {
  if (estadoTermino.value !== 'valida' || filtro.value !== '') return []
  const buscado = normalizar(termino.value)
  return secciones.filter((seccion) => seccion.comparable.includes(buscado))
})

/** El grupo se enseña sólo en la primera página; el recuento no cambia al paginar. */
const mostrarSecciones = computed(() => pagina.value === 1 && seccionesEncontradas.value.length > 0)

const paginaDeResultados = computed(() => (resultado.value?.tipo === 'pagina' ? resultado.value.pagina : null))

const totalEnResumen = computed(
  () => (paginaDeResultados.value?.total ?? 0) + seccionesEncontradas.value.length,
)

const resumen = computed(() => {
  if (buscando.value) return TEXTOS.buscando
  if (resultado.value?.tipo !== 'pagina') return ''
  // Sin resultados también se anuncia: el encabezado de abajo aparece de golpe y un
  // lector de pantalla no lo leería solo.
  if (totalEnResumen.value === 0) return TEXTOS.sinResultadosTitulo(termino.value)
  return textoResumen(totalEnResumen.value, termino.value)
})

const sinResultados = computed(
  () => resultado.value?.tipo === 'pagina' && totalEnResumen.value === 0 && !buscando.value,
)

const elementos = computed(() =>
  (paginaDeResultados.value?.resultados ?? []).map((r) => ({
    ...r,
    extractoCorto: r.extracto === null ? null : recortarExtracto(r.extracto),
    etiquetaFecha: etiquetaDeFecha(r.tipoRecurso, r.fecha),
  })),
)

// Una página pedida más allá de la última (un enlace viejo, un resultado que dejó
// de existir) no es «sin resultados»: hay resultados, en otra página. Se lleva a la
// última, sin dejar la página vacía en el historial.
async function corregirPaginaFueraDeRango(): Promise<void> {
  const leida = paginaDeResultados.value
  if (buscando.value || leida === null || leida.resultados.length > 0 || leida.total === 0) return
  if (pagina.value > leida.ultimaPagina) {
    await navigateTo({ path: '/buscar', query: consulta(filtro.value, leida.ultimaPagina) }, { replace: true })
  }
}

await corregirPaginaFueraDeRango()
watch([paginaDeResultados, buscando], () => void corregirPaginaFueraDeRango())

// ---------------------------------------------------------------------------
// Direcciones: la URL es la que se comparte (`?q=&tipo=&page=`)
// ---------------------------------------------------------------------------

function consulta(tipo: FiltroTipo, numeroPagina: number): Record<string, string> {
  return {
    q: termino.value,
    ...(tipo === '' ? {} : { tipo }),
    ...(numeroPagina > 1 ? { page: String(numeroPagina) } : {}),
  }
}

/** Cambiar de filtro vuelve a la primera página. */
const destinoDeFiltro = (tipo: FiltroTipo) => ({ path: '/buscar', query: consulta(tipo, 1) })

const hrefDeFiltro = (tipo: FiltroTipo): string => enrutador.resolve(destinoDeFiltro(tipo)).href

const hrefDePagina = (numero: number): string =>
  enrutador.resolve({ path: '/buscar', query: consulta(filtro.value, numero) }).href

function irAFiltro(tipo: FiltroTipo): void {
  void enrutador.push(destinoDeFiltro(tipo))
}

function irAPagina(numero: number): void {
  void enrutador.push({ path: '/buscar', query: consulta(filtro.value, numero) })
}

function alBuscar(nuevo: string): void {
  void enrutador.push({ path: '/buscar', query: { q: nuevo } })
}

// ---------------------------------------------------------------------------
// Foco (§5.1): al cambiar de página o de filtro pasa al <h1>; el resumen se
// anuncia por `role="status"`. Sin término, el foco va al campo.
// ---------------------------------------------------------------------------

const encabezado = ref<HTMLElement | null>(null)
const buscador = ref<{ enfocar: () => void } | null>(null)

watch([filtro, pagina], async () => {
  await nextTick()
  encabezado.value?.focus()
})

onMounted(() => {
  if (estadoTermino.value === 'sin-termino') buscador.value?.enfocar()
})

useHead({
  title: () => (termino.value ? `Resultados para «${termino.value}» · Sede Electrónica` : 'Buscar · Sede Electrónica'),
  // Una página de resultados no aporta nada a un buscador externo y puede
  // generar direcciones infinitas a partir de cualquier término.
  meta: [{ name: 'robots', content: 'noindex, follow' }],
})
</script>

<template>
  <div class="container py-5">
    <h1 ref="encabezado" tabindex="-1" class="encabezado-resultados">{{ TEXTOS.titulo }}</h1>

    <!-- El mismo componente que la cabecera, sin lista; sin JavaScript envía por GET. -->
    <BuscadorGovco
      ref="buscador"
      class="buscador-pagina mb-3"
      accion="/buscar"
      :valor-inicial="termino"
      @buscar="alBuscar"
    />

    <p v-if="estadoTermino === 'sin-termino'" class="mb-0">{{ TEXTOS.sinTermino }}</p>
    <p v-else-if="estadoTermino === 'corta'" class="aviso-busqueda" role="alert">{{ TEXTOS.corta }}</p>
    <p v-else-if="estadoTermino === 'larga'" class="aviso-busqueda" role="alert">{{ TEXTOS.larga }}</p>

    <template v-else>
      <nav class="filtros-busqueda" aria-label="Filtrar por tipo">
        <span class="filtros-etiqueta">Filtrar por:</span>
        <ul class="filtros-lista">
          <li v-for="opcion in FILTROS" :key="opcion.valor || 'todo'">
            <a
              :href="hrefDeFiltro(opcion.valor)"
              class="filtro-enlace"
              :aria-current="opcion.valor === filtro ? 'true' : undefined"
              @click.prevent="irAFiltro(opcion.valor)"
            >
              {{ opcion.etiqueta }}
            </a>
          </li>
        </ul>
      </nav>

      <!-- El recuento se anuncia sin robar el foco. -->
      <p class="resultado-resumen" role="status">{{ resumen }}</p>

      <!-- 429 y 422 se explican; no son «sin resultados». -->
      <p v-if="resultado?.tipo === 'limitado'" class="aviso-busqueda" role="alert">
        {{ TEXTOS.limitado(resultado.segundos) }}
      </p>
      <p v-else-if="resultado?.tipo === 'invalida'" class="aviso-busqueda" role="alert">
        {{ termino.length > 120 ? TEXTOS.larga : TEXTOS.corta }}
      </p>

      <!--
        Que la API no conteste no es que no haya trámites: se dice qué pasó, se
        ofrece reintentar y, si hay secciones que coinciden, se enseñan igual.
      -->
      <div v-else-if="resultado?.tipo === 'error'" class="aviso-busqueda" role="alert">
        <p class="mb-2">{{ TEXTOS.error }}</p>
        <p v-if="mostrarSecciones" class="mb-2">{{ TEXTOS.errorConSecciones }}</p>
        <button type="button" class="btn btn-govco outline-btn-govco" @click="refresh()">Reintentar</button>
      </div>

      <div :aria-busy="buscando ? 'true' : 'false'" :class="{ atenuado: buscando }">
        <section v-if="mostrarSecciones" aria-labelledby="titulo-secciones" class="mt-4">
          <h2 id="titulo-secciones" class="h4">Secciones de la sede</h2>
          <ul class="lista-resultados">
            <li v-for="seccion in seccionesEncontradas" :key="seccion.ruta">
              <NuxtLink :to="seccion.ruta" class="resultado-enlace">{{ seccion.etiqueta }}</NuxtLink>
            </li>
          </ul>
        </section>

        <ul v-if="elementos.length > 0" class="lista-resultados mt-3" aria-label="Resultados de la búsqueda">
          <li v-for="elemento in elementos" :key="`${elemento.tipoRecurso}-${elemento.id}`">
            <p class="resultado-meta">{{ elemento.categoria }}</p>
            <NuxtLink :to="elemento.url" class="resultado-enlace">{{ elemento.titulo }}</NuxtLink>
            <p v-if="elemento.extractoCorto" class="resultado-extracto">{{ elemento.extractoCorto }}</p>
            <p v-if="elemento.etiquetaFecha" class="resultado-fecha">
              <time :datetime="elemento.fecha ?? undefined">{{ elemento.etiquetaFecha }}</time>
            </p>
          </li>
        </ul>

        <!-- Sin ni un resultado: el eco del término es texto, nunca HTML. -->
        <div v-if="sinResultados" class="mt-4">
          <h2 class="h4">{{ TEXTOS.sinResultadosTitulo(termino) }}</h2>
          <p class="mb-0">
            {{ TEXTOS.sinResultadosAyuda }}
            <NuxtLink to="/tramites">{{ TEXTOS.verTramites }}</NuxtLink>.
          </p>
        </div>

        <PaginacionGovco
          v-if="paginaDeResultados && elementos.length > 0"
          class="mt-4"
          :pagina-actual="pagina"
          :total-paginas="paginaDeResultados.ultimaPagina"
          :enlace="hrefDePagina"
          @cambiar="irAPagina"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
/*
 * El botón de accesibilidad flota fijo, a media altura y pegado al borde derecho
 * (D1). En un móvil de 390 px el campo de esta página cae justo a esa altura y el
 * círculo tapaba la lupa por completo (a 360 px quedaba a 5 px). Se le deja al
 * campo, a la derecha, el ancho del círculo más su margen y un respiro de 0,25 rem
 * (`--acc-flotante-tamano` + `--acc-flotante-margen`; 2,75 rem de círculo a ≤575 px).
 * Por encima de 1279 px el contenedor ya deja ese espacio a cada lado.
 */
@media (max-width: 575px) {
  .buscador-pagina {
    max-width: calc(100% - 3.5rem);
  }

  /* El campo queda más estrecho y el marcador se cortaba a media letra: con puntos
     suspensivos se lee que continúa. `text-overflow` va en el campo: en el
     pseudoelemento `::placeholder` no se aplica. */
  .buscador-pagina :deep(.input-search-basic-govco) {
    text-overflow: ellipsis;
  }
}

@media (min-width: 576px) and (max-width: 1279px) {
  .buscador-pagina {
    max-width: calc(100% - 3.75rem);
  }
}

/* El foco que llega por código al encabezado no necesita contorno: no es un control. */
.encabezado-resultados:focus {
  outline: none;
}

/*
 * Recuento de resultados. Es texto informativo: se publica con el Matterhorn del
 * Kit (8,59:1 sobre blanco) y no con un gris claro, que no llegaría al 4,5:1.
 */
.resultado-resumen {
  min-height: 1.5rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.aviso-busqueda {
  margin: 1rem 0 0;
  padding: 0.75rem 1rem;
  border-inline-start: 0.25rem solid var(--govcolor-cobalt, #0943b5);
  background-color: #f5f7fb;
  color: #1a1a1a;
}

/* Mientras llega la página siguiente, lo de antes se queda y se atenúa (§5.2). */
.atenuado {
  opacity: 0.55;
}

/* Filtro por tipo: enlaces de al menos 44 px; el actual, en negrita y subrayado grueso. */
.filtros-busqueda {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  column-gap: 0.5rem;
  margin: 0.5rem 0 0.25rem;
}

.filtros-lista {
  display: flex;
  flex-wrap: wrap;
  margin: 0;
  padding: 0;
  list-style: none;
}

.filtro-enlace {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  padding: 0 0.75rem;
}

.filtro-enlace[aria-current='true'] {
  font-weight: 700;
  text-decoration: underline;
  text-decoration-thickness: 0.1875rem;
  text-underline-offset: 0.375rem;
}

.lista-resultados {
  list-style: none;
  padding: 0;
  margin: 0;
}

.lista-resultados li {
  padding: 0.875rem 0;
  border-bottom: 0.0625rem solid #e5e7eb;
}

.lista-resultados li:last-child {
  border-bottom: 0;
}

/*
 * El título del resultado es el enlace, y va subrayado siempre y no sólo al pasar
 * el ratón: en una lista de resultados, saber qué es pulsable sin recorrer cada
 * línea con el puntero es parte de la usabilidad del buscador.
 */
.resultado-enlace {
  font-size: 1.0625rem;
  font-weight: 600;
  text-decoration: underline;
}

.resultado-meta {
  margin: 0 0 0.125rem;
  font-size: 0.8125rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.resultado-extracto {
  margin: 0.25rem 0 0;
  font-size: 0.9375rem;
  color: #333;
  /* Entre 60 y 80 caracteres por línea (RNF-B1-031). */
  max-width: 68ch;
}

.resultado-fecha {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}
</style>

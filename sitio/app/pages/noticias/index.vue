<script setup lang="ts">
/**
 * Noticias de la Entidad — `/noticias` (M01-6, RF-01-013; FUN-026).
 *
 * El archivo de noticias publicadas, de la más reciente a la más antigua, de a
 * nueve por página. Cuatro estados que no se confunden: cargando, la API no
 * contestó (se dice, sin afirmar que no hay noticias), no hay ninguna (CT-01: se
 * dice con su motivo, sin datos de ejemplo) y la lista. La página va en la
 * dirección (`?pagina=N`) para que se pueda enlazar y para que «atrás» funcione.
 */
import { TEXTO_SIN_NOTICIAS, estadoDeNoticias, type NoticiaResumen } from '~/utils/noticias'

interface RespuestaNoticias {
  data: NoticiaResumen[]
  meta: { current_page: number; last_page: number; total: number }
}

const TAMANO_PAGINA = 9

const ruta = useRoute()
const enrutador = useRouter()
const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()

/** `?pagina=` leída con cuidado: lo que no sea un entero ≥ 1 es la página 1. */
const pagina = computed(() => {
  const n = Number.parseInt(String(ruta.query.pagina ?? '1'), 10)
  return Number.isInteger(n) && n >= 1 ? n : 1
})

const { data, status } = await useAsyncData<RespuestaNoticias | null>(
  'noticias-listado',
  async () => {
    try {
      return await traer<RespuestaNoticias>(`${urlApi}/noticias`, {
        query: { page: pagina.value, per_page: TAMANO_PAGINA },
      })
    } catch {
      return null
    }
  },
  { watch: [pagina] },
)

const cargando = computed(() => status.value === 'pending' || status.value === 'idle')
const estado = computed(() => estadoDeNoticias(data.value ?? null))
const totalPaginas = computed(() => Math.max(1, data.value?.meta.last_page ?? 1))

const hrefDePagina = (numero: number): string =>
  enrutador.resolve({ path: '/noticias', query: numero > 1 ? { pagina: String(numero) } : {} }).href

function irAPagina(numero: number): void {
  void enrutador.push({ path: '/noticias', query: numero > 1 ? { pagina: String(numero) } : {} })
}

useHead({
  title: () => (pagina.value > 1 ? `Noticias · Página ${pagina.value} · Sede Electrónica` : 'Noticias · Sede Electrónica'),
  meta: [
    {
      name: 'description',
      content: 'Las noticias de la Alcaldía Distrital de Santa Marta, de la más reciente a la más antigua.',
    },
  ],
})
</script>

<template>
  <div class="container py-5">
    <h1>Noticias</h1>

    <p v-if="cargando" role="status">Cargando noticias…</p>

    <p v-else-if="estado === 'error'" role="status" class="mb-0">
      Las noticias no están disponibles en este momento. Puede intentarlo de nuevo en unos minutos.
    </p>

    <p v-else-if="estado === 'vacio'" role="status" class="mb-0">{{ TEXTO_SIN_NOTICIAS }}</p>

    <template v-else>
      <ul class="row g-4 lista-noticias">
        <li v-for="noticia in data?.data ?? []" :key="noticia.id" class="col-md-6 col-lg-4">
          <TarjetaNoticia :noticia="noticia" />
        </li>
      </ul>

      <PaginacionGovco
        class="mt-4"
        :pagina-actual="pagina"
        :total-paginas="totalPaginas"
        :enlace="hrefDePagina"
        @cambiar="irAPagina"
      />
    </template>
  </div>
</template>

<style scoped>
.lista-noticias {
  list-style: none;
  padding: 0;
  margin-bottom: 0;
}
</style>

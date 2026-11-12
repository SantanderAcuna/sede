<script setup lang="ts">
/**
 * Noticias del sitio: la lista (M01-6, RF-01-013).
 *
 * Es sólo la lista: una tabla que se busca, se filtra por estado, se ordena y se pagina
 * en el servidor. Crear y editar son vistas propias (`NoticiaFormularioView`). Una noticia
 * publicada y completa aparece en la portada y en `/noticias`.
 */
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import { POR_PAGINA, eliminar, listar, type EstadoNoticiaFiltro } from '@/services/noticias'
import type { NoticiaPanelItem } from '@/types/api'
import { tomarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'

const ESPERA_BUSQUEDA_MS = 400

const filas = ref<NoticiaPanelItem[]>([])
const total = ref(0)
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const aviso = ref<string | null>(tomarAviso())

const busqueda = ref('')
const consulta = ref<{
  pagina: number
  q: string
  estado: EstadoNoticiaFiltro | ''
  orden: { id: string; desc: boolean }
}>({ pagina: 1, q: '', estado: '', orden: { id: 'fecha_publicacion', desc: true } })

const hayFiltros = computed(() => consulta.value.q !== '' || consulta.value.estado !== '')
const vacioSinFiltros = computed(() => !cargando.value && !errorCarga.value && total.value === 0 && !hayFiltros.value)
const vacioConFiltros = computed(() => !cargando.value && !errorCarga.value && total.value === 0 && hayFiltros.value)
const hayTabla = computed(() => !errorCarga.value && !vacioSinFiltros.value && !vacioConFiltros.value)

// Sólo la última consulta cuenta: una respuesta lenta de una búsqueda anterior no puede
// pisar a la actual.
let ultimaPeticion = 0

async function cargar(): Promise<void> {
  const numero = ++ultimaPeticion
  cargando.value = true
  errorCarga.value = null
  try {
    const { datos, meta } = await listar({
      pagina: consulta.value.pagina,
      porPagina: POR_PAGINA,
      q: consulta.value.q,
      estado: consulta.value.estado,
      ordenarPor: consulta.value.orden.id as 'fecha_publicacion' | 'titulo',
      direccion: consulta.value.orden.desc ? 'desc' : 'asc',
    })
    if (numero !== ultimaPeticion) return
    // Se eliminó la última fila de la última página: esa página ya no existe.
    if (datos.length === 0 && consulta.value.pagina > 1 && meta.last_page < consulta.value.pagina) {
      consulta.value.pagina = meta.last_page
      await cargar()
      return
    }
    filas.value = datos
    total.value = meta.total
  } catch (e) {
    if (numero !== ultimaPeticion) return
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar las noticias.')[0] ?? null
  } finally {
    if (numero === ultimaPeticion) cargando.value = false
  }
}

onMounted(cargar)

let temporizador: ReturnType<typeof setTimeout> | undefined
onBeforeUnmount(() => clearTimeout(temporizador))

function alEscribirBusqueda(): void {
  clearTimeout(temporizador)
  temporizador = setTimeout(() => {
    consulta.value.q = busqueda.value.trim()
    consulta.value.pagina = 1
    void cargar()
  }, ESPERA_BUSQUEDA_MS)
}

function alCambiarEstado(): void {
  consulta.value.pagina = 1
  void cargar()
}

function alCambiarOrden(orden: { id: string; desc: boolean }): void {
  consulta.value.orden = orden
  consulta.value.pagina = 1
  void cargar()
}

function alCambiarPagina(pagina: number): void {
  consulta.value.pagina = pagina
  void cargar()
}

function quitarFiltros(): void {
  clearTimeout(temporizador)
  busqueda.value = ''
  consulta.value.q = ''
  consulta.value.estado = ''
  consulta.value.pagina = 1
  void cargar()
}

// --- Eliminar -----------------------------------------------------------------
const porEliminar = ref<NoticiaPanelItem | null>(null)
const eliminando = ref(false)
const errorEliminar = ref<string | null>(null)
const dialogoAbierto = computed({
  get: () => porEliminar.value !== null,
  set: (abierto: boolean) => {
    if (!abierto) {
      porEliminar.value = null
      errorEliminar.value = null
    }
  },
})

async function confirmarEliminar(): Promise<void> {
  const noticia = porEliminar.value
  if (noticia === null) return
  eliminando.value = true
  errorEliminar.value = null
  try {
    await eliminar(noticia.uuid)
    porEliminar.value = null
    aviso.value = 'Noticia eliminada. Ya no se ve en el sitio.'
    await cargar()
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar la noticia. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

// --- Columnas -----------------------------------------------------------------
const formatoFecha = new Intl.DateTimeFormat('es-CO', {
  dateStyle: 'medium',
  timeStyle: 'short',
  timeZone: 'America/Bogota',
})

const ESTADOS: Record<string, { texto: string; variante: 'success' | 'info' | 'warning' }> = {
  publicada: { texto: 'Publicada', variante: 'success' },
  programada: { texto: 'Programada', variante: 'info' },
  borrador: { texto: 'Borrador', variante: 'warning' },
}

const columnas: ColumnDef<DataTableFeatures, NoticiaPanelItem, unknown>[] = [
  {
    id: 'titulo',
    accessorFn: (n) => n.titulo,
    header: 'Título',
    enableSorting: true,
    cell: ({ row }) =>
      h(
        RouterLink,
        {
          to: { name: 'panel.noticias.editar', params: { uuid: row.original.uuid } },
          class: 'font-medium text-slate-900 hover:text-gov-blue hover:underline',
        },
        () => row.original.titulo,
      ),
  },
  {
    id: 'estado',
    header: 'Estado',
    enableSorting: false,
    cell: ({ row }) => {
      const estado = ESTADOS[row.original.estado] ?? ESTADOS.borrador!
      return h(BaseBadge, { variant: estado.variante, dot: true }, () => estado.texto)
    },
  },
  {
    id: 'fecha_publicacion',
    accessorFn: (n) => n.fecha_publicacion,
    header: 'Fecha de publicación',
    enableSorting: true,
    meta: { clase: 'hidden sm:table-cell' },
    cell: ({ row }) => formatoFecha.format(new Date(row.original.fecha_publicacion)),
  },
  {
    id: 'imagen',
    header: 'Imagen',
    enableSorting: false,
    meta: { clase: 'hidden md:table-cell' },
    cell: ({ row }) =>
      row.original.imagen
        ? 'Sí'
        : h('span', { class: 'font-medium text-amber-700' }, 'Falta'),
  },
  {
    id: 'acciones',
    header: 'Acciones',
    enableSorting: false,
    cell: ({ row }) =>
      h('div', { class: 'flex items-center gap-2' }, [
        h(
          RouterLink,
          {
            to: { name: 'panel.noticias.editar', params: { uuid: row.original.uuid } },
            'aria-label': `Editar «${row.original.titulo}»`,
            class:
              'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-gov-blue ring-1 ring-slate-200 hover:bg-gov-blue-light focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue',
          },
          () => 'Editar',
        ),
        h(
          'button',
          {
            type: 'button',
            'aria-label': `Eliminar «${row.original.titulo}»`,
            class:
              'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-red-700 ring-1 ring-slate-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600',
            onClick: () => {
              porEliminar.value = row.original
            },
          },
          'Eliminar',
        ),
      ]),
  },
]
</script>

<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-xl font-semibold text-slate-900">Noticias</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Aquí se administran las noticias del sitio. Una noticia publicada aparece en la portada y en la página de
          noticias; un borrador no se ve hasta que se publique.
        </p>
      </div>
      <RouterLink
        :to="{ name: 'panel.noticias.nueva' }"
        class="inline-flex h-10 items-center gap-2 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nueva noticia
      </RouterLink>
    </header>

    <div role="status" aria-live="polite">
      <p
        v-if="aviso"
        class="flex items-start justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
      >
        <span>{{ aviso }}</span>
        <button type="button" class="font-medium underline" @click="aviso = null">Cerrar aviso</button>
      </p>
    </div>

    <div
      v-if="hayTabla || vacioConFiltros"
      class="flex flex-col gap-3 rounded-xl bg-white p-4 ring-1 ring-slate-200 sm:flex-row sm:items-end"
    >
      <div class="flex-1">
        <label for="noticias-buscar" class="text-sm font-medium text-slate-700">Buscar por título</label>
        <input
          id="noticias-buscar"
          v-model="busqueda"
          type="search"
          autocomplete="off"
          placeholder="Escriba parte del título"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-500 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @input="alEscribirBusqueda"
        >
      </div>
      <div class="sm:w-56">
        <label for="noticias-estado" class="text-sm font-medium text-slate-700">Estado</label>
        <select
          id="noticias-estado"
          v-model="consulta.estado"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @change="alCambiarEstado"
        >
          <option value="">Todos los estados</option>
          <option value="borrador">Borrador</option>
          <option value="programada">Programada</option>
          <option value="publicada">Publicada</option>
        </select>
      </div>
      <BaseButton v-if="hayFiltros" variant="outline" @click="quitarFiltros">Quitar filtros</BaseButton>
    </div>

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudieron cargar las noticias</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <div v-else-if="vacioSinFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Aún no hay noticias</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
        Cuando cree la primera, aparecerá aquí. Empiece como borrador: no se verá en el sitio hasta que usted la publique.
      </p>
      <RouterLink
        :to="{ name: 'panel.noticias.nueva' }"
        class="mt-4 inline-flex h-10 items-center rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        Crear la primera noticia
      </RouterLink>
    </div>

    <div v-else-if="vacioConFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Ninguna noticia coincide con la búsqueda</p>
      <p class="mt-1 text-sm text-slate-600">Pruebe con otras palabras o quite los filtros para ver todas.</p>
    </div>

    <DataTable
      v-else
      servidor
      :clicable="false"
      :data="filas"
      :columns="columnas"
      :total="total"
      :pagina="consulta.pagina"
      :por-pagina="POR_PAGINA"
      :orden="consulta.orden"
      :loading="cargando"
      caption="Noticias del sitio"
      @cambiar-pagina="alCambiarPagina"
      @cambiar-orden="alCambiarOrden"
    />

    <ConfirmModal
      v-model="dialogoAbierto"
      title="¿Eliminar esta noticia?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>
        Se eliminará <strong>«{{ porEliminar?.titulo }}»</strong> y dejará de verse en el sitio. Esta acción no se
        puede deshacer desde el panel.
      </p>
    </ConfirmModal>
  </div>
</template>

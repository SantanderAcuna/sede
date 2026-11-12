<script setup lang="ts">
/**
 * Trámites y servicios: la lista.
 *
 * Es sólo la lista: una tabla que se busca, se filtra por estado, se ordena y se pagina
 * en el servidor. Crear y editar son vistas propias (`TramiteFormularioView`). Un trámite publicado y completo aparece en el sitio.
 */
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import { POR_PAGINA, eliminar, listar, type EstadoTramiteFiltro } from '@/services/tramites'
import type { TramitePanelFila } from '@/types/api'
import { tomarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'

const ESPERA_BUSQUEDA_MS = 400

const filas = ref<TramitePanelFila[]>([])
const total = ref(0)
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const aviso = ref<string | null>(tomarAviso())

const busqueda = ref('')
const consulta = ref<{
  pagina: number
  q: string
  estado: EstadoTramiteFiltro | ''
  orden: { id: string; desc: boolean }
}>({ pagina: 1, q: '', estado: '', orden: { id: 'actualizado_en', desc: true } })

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
      ordenarPor: consulta.value.orden.id as 'nombre' | 'actualizado_en',
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
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar los trámites.')[0] ?? null
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
const porEliminar = ref<TramitePanelFila | null>(null)
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
  const tramite = porEliminar.value
  if (tramite === null) return
  eliminando.value = true
  errorEliminar.value = null
  try {
    await eliminar(tramite.uuid)
    porEliminar.value = null
    aviso.value = 'Trámite eliminado.'
    await cargar()
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar el trámite. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

// --- Columnas -----------------------------------------------------------------
const formatoFecha = new Intl.DateTimeFormat('es-CO', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: 'numeric',
  minute: '2-digit',
  timeZone: 'America/Bogota',
})

const claseAccion =
  'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium ring-1 ring-slate-200 focus:outline-none focus-visible:ring-2'

function insigniaDe(t: TramitePanelFila, clase = '') {
  return t.estado === 'publicado'
    ? h(BaseBadge, { variant: 'success', dot: true, class: clase }, () => 'Publicado')
    : h(BaseBadge, { variant: 'warning', dot: true, class: clase }, () => 'Borrador')
}

const columnas: ColumnDef<DataTableFeatures, TramitePanelFila, unknown>[] = [
  {
    id: 'nombre',
    accessorFn: (t) => t.nombre,
    header: 'Nombre',
    enableSorting: true,
    cell: ({ row }) =>
      h('div', { class: 'flex flex-col items-start gap-1.5' }, [
        h(
          RouterLink,
          {
            to: { name: 'panel.tramites.editar', params: { uuid: row.original.uuid } },
            class: 'font-medium text-slate-900 hover:text-gov-blue hover:underline',
          },
          () => row.original.nombre,
        ),
        row.original.estado === 'borrador' && row.original.problemas.length > 0
          ? h(
              'span',
              { class: 'text-xs text-amber-700' },
              row.original.problemas.length === 1
                ? 'Falta 1 dato para publicar'
                : `Faltan ${row.original.problemas.length} datos para publicar`,
            )
          : null,
        // En pantallas estrechas la columna Estado se oculta y el estado va bajo el nombre.
        insigniaDe(row.original, 'sm:hidden'),
      ]),
  },
  {
    id: 'codigo',
    accessorFn: (t) => t.codigo,
    header: 'Código',
    enableSorting: false,
    meta: { clase: 'hidden whitespace-nowrap md:table-cell' },
    cell: ({ row }) => h('span', { class: 'font-mono text-xs text-slate-700' }, row.original.codigo),
  },
  {
    id: 'estado',
    header: 'Estado',
    enableSorting: false,
    meta: { clase: 'hidden sm:table-cell' },
    cell: ({ row }) => insigniaDe(row.original),
  },
  {
    id: 'origen',
    header: 'Origen',
    enableSorting: false,
    meta: { clase: 'hidden whitespace-nowrap lg:table-cell' },
    cell: ({ row }) =>
      row.original.origen === 'panel'
        ? 'Creado en el panel'
        : row.original.modificado_en_panel
          ? 'Catálogo oficial (corregido aquí)'
          : 'Catálogo oficial',
  },
  {
    id: 'actualizado_en',
    accessorFn: (t) => t.actualizado_en,
    header: 'Actualizado',
    enableSorting: true,
    meta: { clase: 'hidden whitespace-nowrap md:table-cell' },
    cell: ({ row }) => (row.original.actualizado_en ? formatoFecha.format(new Date(row.original.actualizado_en)) : '—'),
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
            to: { name: 'panel.tramites.editar', params: { uuid: row.original.uuid } },
            'aria-label': `Editar «${row.original.nombre}»`,
            class: `${claseAccion} text-gov-blue hover:bg-gov-blue-light focus-visible:ring-gov-blue`,
          },
          () => 'Editar',
        ),
        // Los del catálogo oficial no se eliminan (la carga automática los traería de vuelta): se despublican.
        row.original.origen === 'panel'
          ? h(
              'button',
              {
                type: 'button',
                'aria-label': `Eliminar «${row.original.nombre}»`,
                class: `${claseAccion} text-red-700 hover:bg-red-50 focus-visible:ring-red-600`,
                onClick: () => {
                  porEliminar.value = row.original
                },
              },
              'Eliminar',
            )
          : null,
      ]),
  },
]
</script>

<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-xl font-semibold text-slate-900">Trámites y servicios</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Aquí se administra el catálogo de trámites del sitio. Un trámite publicado y completo aparece en el sitio
          con su ficha; un borrador no se ve hasta que se publique. Los del catálogo oficial se pueden corregir y
          despublicar, pero no eliminar.
        </p>
      </div>
      <RouterLink
        :to="{ name: 'panel.tramites.nuevo' }"
        class="inline-flex h-10 items-center gap-2 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nuevo trámite
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
        <label for="tramites-buscar" class="text-sm font-medium text-slate-700">Buscar por nombre o código</label>
        <input
          id="tramites-buscar"
          v-model="busqueda"
          type="search"
          autocomplete="off"
          placeholder="Escriba parte del nombre o el código"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-500 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @input="alEscribirBusqueda"
        >
      </div>
      <div class="sm:w-56">
        <label for="tramites-estado" class="text-sm font-medium text-slate-700">Estado</label>
        <select
          id="tramites-estado"
          v-model="consulta.estado"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @change="alCambiarEstado"
        >
          <option value="">Todos los estados</option>
          <option value="publicado">Publicado</option>
          <option value="borrador">Borrador</option>
        </select>
      </div>
      <BaseButton v-if="hayFiltros" variant="outline" @click="quitarFiltros">Quitar filtros</BaseButton>
    </div>

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudieron cargar los trámites</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <div v-else-if="vacioSinFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Aún no hay trámites</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
        Cuando cree el primero, aparecerá aquí. Empiece como borrador: no se verá en el sitio hasta que usted lo publique.
      </p>
      <RouterLink
        :to="{ name: 'panel.tramites.nuevo' }"
        class="mt-4 inline-flex h-10 items-center rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        Crear el primer trámite
      </RouterLink>
    </div>

    <div v-else-if="vacioConFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Ningún trámite coincide con la búsqueda</p>
      <p class="mt-1 text-sm text-slate-600">Pruebe con otras palabras o quite los filtros para ver todos.</p>
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
      caption="Trámites del sitio"
      @cambiar-pagina="alCambiarPagina"
      @cambiar-orden="alCambiarOrden"
    />

    <ConfirmModal
      v-model="dialogoAbierto"
      title="¿Eliminar este trámite?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>
        Se eliminará <strong>«{{ porEliminar?.nombre }}»</strong> y dejará de verse en el sitio. Esta acción no se
        puede deshacer desde el panel.
      </p>
    </ConfirmModal>
  </div>
</template>

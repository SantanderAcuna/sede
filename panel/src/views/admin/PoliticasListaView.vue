<script setup lang="ts">
/**
 * Políticas del sitio: la lista (T-20, RF-01-018/019/020).
 *
 * Las cinco obligatorias (no se eliminan ni se ocultan) y las que la Entidad agregue. La tabla dice,
 * de un vistazo, cuáles siguen con texto de ejemplo, cuáles están incompletas, cuáles están adoptadas
 * y cuáles se ven en el sitio; editar abre una vista propia (`PoliticaFormularioView`). Una política
 * propia nace oculta y se publica solo adoptada. Lo que se publica es lo que se descarga en ODT y PDF/A.
 */
import { computed, h, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'
import { eliminar, listar } from '@/services/politicas'
import type { PoliticaPanelItem } from '@/types/api'
import { tomarAviso } from './avisos'
import { motivosDeError, pasosDeAdopcion } from './politicas-logica'

const { puedeCrear, puedeEliminar } = usePermisosDeModulo('politicas')

const filas = ref<PoliticaPanelItem[]>([])
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const aviso = ref<string | null>(tomarAviso())

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  try {
    filas.value = await listar()
  } catch (e) {
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar las políticas.')[0] ?? null
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)

// --- Eliminar (solo las propias) ----------------------------------------------
const porEliminar = ref<PoliticaPanelItem | null>(null)
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
  const politica = porEliminar.value
  if (politica === null) return
  eliminando.value = true
  errorEliminar.value = null
  try {
    await eliminar(politica.slug)
    porEliminar.value = null
    aviso.value = 'Política eliminada. Ya no se ve en el sitio.'
    await cargar()
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar la política. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

function estado(p: PoliticaPanelItem): { texto: string; variante: 'success' | 'warning' | 'danger' } {
  if (!p.completa) return { texto: 'Incompleta', variante: 'danger' }
  if (p.es_ejemplo) return { texto: 'Texto de ejemplo', variante: 'warning' }
  return { texto: 'Adoptada', variante: 'success' }
}

const insignia = (p: PoliticaPanelItem, clase = '') => {
  const e = estado(p)
  return h(BaseBadge, { variant: e.variante, dot: true, class: ['whitespace-nowrap', clase] }, () => e.texto)
}

const visibilidad = (p: PoliticaPanelItem, clase = '') =>
  h(
    BaseBadge,
    { variant: p.publicada ? 'success' : 'neutral', dot: true, class: ['whitespace-nowrap', clase] },
    () => (p.publicada ? 'Publicada' : 'Oculta'),
  )

const formatoFecha = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })

const columnas: ColumnDef<DataTableFeatures, PoliticaPanelItem, unknown>[] = [
  {
    id: 'nombre',
    accessorFn: (p) => p.nombre,
    header: 'Política',
    enableSorting: true,
    cell: ({ row }) =>
      h('div', { class: 'flex flex-col items-start gap-1.5' }, [
        h(
          RouterLink,
          {
            to: { name: 'panel.politicas.editar', params: { slug: row.original.slug } },
            class: 'font-medium text-slate-900 hover:text-gov-blue hover:underline',
          },
          () => row.original.nombre,
        ),
        // En pantallas estrechas la columna Estado se oculta y el estado va bajo el nombre.
        insignia(row.original, 'sm:hidden'),
        visibilidad(row.original, 'sm:hidden'),
        h(
          'span',
          { class: 'text-xs text-slate-600 lg:hidden' },
          `${pasosDeAdopcion(row.original).filter((x) => x.hecho).length} de 3 pasos para adoptarla`,
        ),
      ]),
  },
  {
    id: 'estado',
    header: 'Estado',
    enableSorting: false,
    meta: { clase: 'hidden sm:table-cell' },
    cell: ({ row }) => insignia(row.original),
  },
  {
    id: 'visibilidad',
    header: 'En el sitio',
    enableSorting: false,
    meta: { clase: 'hidden sm:table-cell' },
    cell: ({ row }) => visibilidad(row.original),
  },
  {
    id: 'version',
    accessorFn: (p) => p.version,
    header: 'Versión',
    enableSorting: true,
    meta: { clase: 'hidden md:table-cell' },
    cell: ({ row }) => `v${row.original.version}`,
  },
  {
    id: 'vigente_desde',
    header: 'Vigente desde',
    enableSorting: false,
    meta: { clase: 'hidden whitespace-nowrap md:table-cell' },
    cell: ({ row }) =>
      row.original.vigente_desde
        ? formatoFecha.format(new Date(row.original.vigente_desde))
        : h('span', { class: 'text-slate-600' }, 'Sin fecha'),
  },
  {
    id: 'documento',
    header: 'Para adoptarla',
    enableSorting: false,
    meta: { clase: 'hidden lg:table-cell' },
    cell: ({ row }) => {
      const pasos = pasosDeAdopcion(row.original)
      const hechos = pasos.filter((x) => x.hecho).length
      return h('div', { class: 'space-y-1' }, [
        h('p', { class: 'text-xs font-semibold text-slate-700' }, `${hechos} de ${pasos.length} pasos`),
        h(
          'ul',
          { class: 'space-y-0.5 text-xs' },
          pasos.map((x) =>
            h('li', { class: x.hecho ? 'text-emerald-800' : 'text-amber-800' }, [
              h('span', { class: 'font-medium' }, x.hecho ? 'Listo: ' : 'Falta: '),
              x.etiqueta,
            ]),
          ),
        ),
      ])
    },
  },
  {
    id: 'acciones',
    header: 'Acciones',
    enableSorting: false,
    cell: ({ row }) =>
      h('div', { class: 'flex flex-wrap items-center gap-2' }, [
        h(
          RouterLink,
          {
            to: { name: 'panel.politicas.editar', params: { slug: row.original.slug } },
            'aria-label': `Editar «${row.original.nombre}»`,
            class:
              'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-gov-blue ring-1 ring-slate-200 hover:bg-gov-blue-light focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue',
          },
          () => 'Editar',
        ),
        // Una política oculta no tiene PDF público: la dirección respondería «no encontrada».
        row.original.publicada
          ? h(
              'a',
              {
                href: `/politicas/${row.original.slug}.pdf`,
                target: '_blank',
                rel: 'noopener',
                'aria-label': `Ver el PDF de «${row.original.nombre}» (se abre en otra pestaña)`,
                class:
                  'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue',
              },
              'Ver PDF',
            )
          : h('span', { class: 'text-xs text-slate-600' }, 'PDF al publicarla'),
        !row.original.es_base &&
          puedeEliminar.value &&
          h(
            'button',
            {
              type: 'button',
              'aria-label': `Eliminar «${row.original.nombre}»`,
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
        <h1 class="text-xl font-semibold text-slate-900">Políticas del sitio</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Las cinco políticas obligatorias (términos de uso, seguridad y privacidad, datos personales, cookies y
          derechos de autor), que no se eliminan ni se ocultan, y las que la Entidad agregue. Mientras el texto sea
          de ejemplo, el sitio lo avisa y no lo deja indexar. Para adoptar una política hacen falta tres pasos: texto
          definitivo, acto administrativo y el archivo de ese acto. Una política nueva queda oculta hasta que se
          adopte y se publique.
        </p>
      </div>
      <RouterLink
        v-if="puedeCrear"
        :to="{ name: 'panel.politicas.nueva' }"
        class="inline-flex h-10 items-center gap-2 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nueva política
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

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudieron cargar las políticas</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <div v-else-if="!cargando && filas.length === 0" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Todavía no hay políticas cargadas</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
        Las cinco políticas las crea el sistema al instalarse. Si esta lista está vacía, avise a quien administra el
        servidor para que ejecute la carga inicial de datos.
      </p>
    </div>

    <DataTable
      v-else
      :clicable="false"
      :data="filas"
      :columns="columnas"
      :loading="cargando"
      caption="Políticas del sitio"
    />

    <ConfirmModal
      v-model="dialogoAbierto"
      title="¿Eliminar esta política?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>
        Se eliminará <strong>«{{ porEliminar?.nombre }}»</strong> con su texto y el documento oficial que tenga adjunto, y
        dejará de verse en el sitio. Esta acción no se puede deshacer desde el panel.
      </p>
    </ConfirmModal>
  </div>
</template>

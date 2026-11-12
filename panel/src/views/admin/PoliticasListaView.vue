<script setup lang="ts">
/**
 * Políticas del sitio: la lista (T-20, RF-01-018/019/020).
 *
 * Son cinco y fijas: no se crean ni se eliminan desde aquí. La tabla dice, de un vistazo,
 * cuáles siguen con texto de ejemplo, cuáles están incompletas y cuáles ya están adoptadas;
 * editar abre una vista propia (`PoliticaFormularioView`). Lo que se publica es lo que se
 * descarga en ODT y PDF/A.
 */
import { h, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import { listar } from '@/services/politicas'
import type { PoliticaPanelItem } from '@/types/api'
import { tomarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'

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

function estado(p: PoliticaPanelItem): { texto: string; variante: 'success' | 'warning' | 'danger' } {
  if (!p.completa) return { texto: 'Incompleta', variante: 'danger' }
  if (p.es_ejemplo) return { texto: 'Texto de ejemplo', variante: 'warning' }
  return { texto: 'Adoptada', variante: 'success' }
}

const insignia = (p: PoliticaPanelItem, clase = '') => {
  const e = estado(p)
  return h(BaseBadge, { variant: e.variante, dot: true, class: ['whitespace-nowrap', clase] }, () => e.texto)
}

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
    header: 'Documento oficial',
    enableSorting: false,
    meta: { clase: 'hidden lg:table-cell' },
    cell: ({ row }) =>
      row.original.documento_oficial ? 'Adjunto' : h('span', { class: 'font-medium text-amber-700' }, 'Falta'),
  },
  {
    id: 'acciones',
    header: 'Acciones',
    enableSorting: false,
    cell: ({ row }) =>
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
  },
]
</script>

<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Políticas del sitio</h1>
      <p class="mt-1 max-w-2xl text-sm text-slate-600">
        Las cinco políticas que publica el sitio: términos de uso, seguridad y privacidad, datos personales, cookies y
        derechos de autor. Mientras el texto sea de ejemplo, el sitio lo avisa y no lo deja indexar. Elija una para
        editarla.
      </p>
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
  </div>
</template>

<script setup lang="ts">
/**
 * Auditoría: quién hizo qué en el panel, lo más reciente primero.
 *
 * Es solo lectura. Cada fila dice la acción con palabras (la frase llega ya armada desde el servidor), sobre qué
 * módulo y con qué detalle. Los intentos que el sistema no dejó pasar llevan una insignia «Rechazado». Se filtra
 * por módulo y por fechas, y se pagina en el servidor.
 */
import { computed, h, onMounted, ref } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import { POR_PAGINA, listar } from '@/api/auditoria'
import type { AuditoriaItem } from '@/types/api'
import { MODULOS_FILTRABLES, detalleDe, esRechazo } from './auditoria-logica'
import { motivosDeError } from './politicas-logica'

const filas = ref<AuditoriaItem[]>([])
const total = ref(0)
const cargando = ref(true)
const errorCarga = ref<string | null>(null)

const consulta = ref({ pagina: 1, recurso: '', desde: '', hasta: '' })

const hayFiltros = computed(() => consulta.value.recurso !== '' || consulta.value.desde !== '' || consulta.value.hasta !== '')
const vacioSinFiltros = computed(() => !cargando.value && !errorCarga.value && total.value === 0 && !hayFiltros.value)
const vacioConFiltros = computed(() => !cargando.value && !errorCarga.value && total.value === 0 && hayFiltros.value)
const hayTabla = computed(() => !errorCarga.value && !vacioSinFiltros.value && !vacioConFiltros.value)

// Solo la última consulta cuenta: una respuesta lenta de un filtro anterior no puede pisar a la actual.
let ultimaPeticion = 0

async function cargar(): Promise<void> {
  const numero = ++ultimaPeticion
  cargando.value = true
  errorCarga.value = null
  try {
    const { datos, meta } = await listar({
      pagina: consulta.value.pagina,
      porPagina: POR_PAGINA,
      recurso: consulta.value.recurso,
      desde: consulta.value.desde,
      hasta: consulta.value.hasta,
    })
    if (numero !== ultimaPeticion) return
    filas.value = datos
    total.value = meta.total
  } catch (e) {
    if (numero !== ultimaPeticion) return
    errorCarga.value = motivosDeError(e, 'No se pudo cargar la auditoría.')[0] ?? null
  } finally {
    if (numero === ultimaPeticion) cargando.value = false
  }
}

onMounted(cargar)

function alFiltrar(): void {
  consulta.value.pagina = 1
  void cargar()
}

function alCambiarPagina(pagina: number): void {
  consulta.value.pagina = pagina
  void cargar()
}

function quitarFiltros(): void {
  consulta.value = { pagina: 1, recurso: '', desde: '', hasta: '' }
  void cargar()
}

// «19 nov 2026, 12:10 a. m.»: el mes con letras, para que nadie dude entre día y mes.
const formatoFecha = new Intl.DateTimeFormat('es-CO', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: 'numeric',
  minute: '2-digit',
  timeZone: 'America/Bogota',
})

const columnas: ColumnDef<DataTableFeatures, AuditoriaItem, unknown>[] = [
  {
    id: 'fecha',
    header: 'Fecha',
    enableSorting: false,
    meta: { clase: 'whitespace-nowrap' },
    cell: ({ row }) => formatoFecha.format(new Date(String(row.original.created_at))),
  },
  {
    id: 'quien',
    header: 'Quién',
    enableSorting: false,
    cell: ({ row }) => row.original.usuario?.email ?? h('span', { class: 'text-slate-600' }, 'Sin cuenta'),
  },
  {
    id: 'accion',
    header: 'Qué hizo',
    enableSorting: false,
    cell: ({ row }) =>
      h('div', { class: 'flex flex-col items-start gap-1.5' }, [
        h('span', { class: 'font-medium text-slate-900' }, row.original.descripcion),
        esRechazo(row.original.accion) && h(BaseBadge, { variant: 'warning', dot: true }, () => 'Rechazado'),
      ]),
  },
  {
    id: 'recurso',
    header: 'Sobre',
    enableSorting: false,
    meta: { clase: 'hidden sm:table-cell' },
    cell: ({ row }) => row.original.recurso_nombre,
  },
  {
    id: 'detalle',
    header: 'Detalle',
    enableSorting: false,
    meta: { clase: 'hidden md:table-cell' },
    cell: ({ row }) => detalleDe(row.original.cambios as Record<string, unknown> | null),
  },
  {
    id: 'ip',
    header: 'Dirección IP',
    enableSorting: false,
    meta: { clase: 'hidden whitespace-nowrap lg:table-cell' },
    cell: ({ row }) => row.original.ip_origen ?? '',
  },
]

const claseCampo =
  'mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30'
</script>

<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Auditoría</h1>
      <p class="mt-1 max-w-2xl text-sm text-slate-600">
        Quién hizo qué en el panel, lo más reciente primero. Se registran los cambios (crear, modificar, publicar,
        eliminar), los accesos a las cuentas y los intentos que el sistema rechazó. Leer no se registra. El registro no
        se puede modificar ni borrar desde el panel.
      </p>
    </header>

    <div v-if="hayTabla || vacioConFiltros" class="grid gap-3 rounded-xl bg-white p-4 ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto] lg:items-end">
      <div>
        <label for="auditoria-modulo" class="text-sm font-medium text-slate-700">Módulo</label>
        <select id="auditoria-modulo" v-model="consulta.recurso" :class="claseCampo" @change="alFiltrar">
          <option value="">Todos los módulos</option>
          <option v-for="m in MODULOS_FILTRABLES" :key="m.valor" :value="m.valor">{{ m.nombre }}</option>
        </select>
      </div>
      <div>
        <label for="auditoria-desde" class="text-sm font-medium text-slate-700">Desde</label>
        <input id="auditoria-desde" v-model="consulta.desde" type="date" :class="claseCampo" @change="alFiltrar">
      </div>
      <div>
        <label for="auditoria-hasta" class="text-sm font-medium text-slate-700">Hasta</label>
        <input id="auditoria-hasta" v-model="consulta.hasta" type="date" :class="claseCampo" @change="alFiltrar">
      </div>
      <BaseButton v-if="hayFiltros" variant="outline" @click="quitarFiltros">Quitar filtros</BaseButton>
    </div>

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudo cargar la auditoría</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <div v-else-if="vacioSinFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Aún no hay registros</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
        Cuando alguien cambie contenido o inicie sesión, quedará constancia aquí.
      </p>
    </div>

    <div v-else-if="vacioConFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Ningún registro coincide con los filtros</p>
      <p class="mt-1 text-sm text-slate-600">Pruebe con otras fechas u otro módulo, o quite los filtros para ver todo.</p>
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
      :loading="cargando"
      caption="Registro de auditoría"
      @cambiar-pagina="alCambiarPagina"
    />
  </div>
</template>

<script setup lang="ts">
/**
 * Usuarios: la lista.
 *
 * Una tabla que se busca, se filtra por estado y por rol y se pagina en el servidor. Crear y
 * editar son vistas propias. No hay «eliminar»: una cuenta se pone inactiva o bloqueada, así
 * se conserva quién hizo qué en la auditoría.
 */
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import UsuariosPestanas from '@/components/base/UsuariosPestanas.vue'
import { listar as listarRoles } from '@/services/roles'
import { POR_PAGINA, listar } from '@/services/usuarios'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'
import { useSesionStore } from '@/stores/sesion'
import type { RolPanelItem, UsuarioPanelItem } from '@/types/api'
import { tomarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'
import { ESTADOS, administradorDe, etiquetaEstado, puedeAdministrarA } from './usuarios-logica'

const ESPERA_BUSQUEDA_MS = 400

const sesion = useSesionStore()
const { puedeCrear, puedeEditar } = usePermisosDeModulo('usuarios')
const quien = computed(() => administradorDe(sesion.usuario?.permisos ?? []))

const filas = ref<UsuarioPanelItem[]>([])
const rolesDisponibles = ref<RolPanelItem[]>([])
const total = ref(0)
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const aviso = ref<string | null>(tomarAviso())

const busqueda = ref('')
const consulta = ref({ pagina: 1, q: '', estado: '', rol: '' })

const hayFiltros = computed(() => consulta.value.q !== '' || consulta.value.estado !== '' || consulta.value.rol !== '')
const vacioSinFiltros = computed(() => !cargando.value && !errorCarga.value && total.value === 0 && !hayFiltros.value)
const vacioConFiltros = computed(() => !cargando.value && !errorCarga.value && total.value === 0 && hayFiltros.value)
const hayTabla = computed(() => !errorCarga.value && !vacioSinFiltros.value && !vacioConFiltros.value)

let ultimaPeticion = 0

async function cargar(): Promise<void> {
  const numero = ++ultimaPeticion
  cargando.value = true
  errorCarga.value = null
  try {
    const { datos, meta } = await listar({ ...consulta.value, porPagina: POR_PAGINA })
    if (numero !== ultimaPeticion) return
    filas.value = datos
    total.value = meta.total
  } catch (e) {
    if (numero !== ultimaPeticion) return
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar los usuarios.')[0] ?? null
  } finally {
    if (numero === ultimaPeticion) cargando.value = false
  }
}

onMounted(() => {
  void cargar()
  // Los roles solo alimentan el filtro: si fallan, la lista sigue funcionando sin él.
  // Sin «ver roles» el servidor rechazaría la consulta: no se hace y el filtro por rol no se ofrece.
  if (sesion.tienePermiso('roles.ver')) listarRoles().then((r) => (rolesDisponibles.value = r)).catch(() => undefined)
})

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

function alCambiarFiltro(): void {
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
  consulta.value = { pagina: 1, q: '', estado: '', rol: '' }
  void cargar()
}

const claseAccion =
  'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium ring-1 ring-slate-200 focus:outline-none focus-visible:ring-2'

const variante = (estado: string) =>
  estado === 'activo' ? 'success' : estado === 'bloqueado' ? 'danger' : estado === 'pendiente' ? 'info' : 'warning'

const insignia = (u: UsuarioPanelItem, clase = '') =>
  h(BaseBadge, { variant: variante(u.estado) as 'success', dot: true, class: clase }, () => etiquetaEstado(u.estado))

const columnas: ColumnDef<DataTableFeatures, UsuarioPanelItem, unknown>[] = [
  {
    id: 'name',
    header: 'Nombre',
    enableSorting: false,
    cell: ({ row }) =>
      h('div', { class: 'flex flex-col items-start gap-1' }, [
        h('span', { class: 'font-medium text-slate-900' }, row.original.name),
        h('span', { class: 'break-all text-xs text-slate-600' }, row.original.email),
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
    id: 'roles',
    header: 'Roles',
    enableSorting: false,
    meta: { clase: 'hidden md:table-cell' },
    cell: ({ row }) =>
      row.original.roles.length === 0
        ? h('span', { class: 'text-sm text-slate-600' }, 'Sin roles')
        : h(
            'div',
            { class: 'flex flex-wrap gap-1' },
            row.original.roles.map((r) => h(BaseBadge, { variant: 'neutral' }, () => r)),
          ),
  },
  {
    id: 'acciones',
    header: 'Acciones',
    enableSorting: false,
    cell: ({ row }) =>
      puedeAdministrarA(row.original, quien.value)
        ? h(
            RouterLink,
            {
              to: { name: 'panel.usuarios.editar', params: { uuid: row.original.uuid } },
              'aria-label': `${puedeEditar.value ? 'Editar a' : 'Ver a'} ${row.original.name}`,
              class: `${claseAccion} text-gov-blue hover:bg-gov-blue-light focus-visible:ring-gov-blue`,
            },
            () => (puedeEditar.value ? 'Editar' : 'Ver'),
          )
        : h('span', { class: 'text-xs text-slate-600' }, 'Solo un súper administrador puede modificarlo'),
  },
]
</script>

<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-xl font-semibold text-slate-900">Usuarios y roles</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Quién puede entrar al panel y qué puede hacer. Cada persona tiene uno o más roles, y cada rol reúne
          permisos. Los cambios quedan registrados en la auditoría.
        </p>
      </div>
      <RouterLink
        v-if="puedeCrear"
        :to="{ name: 'panel.usuarios.nuevo' }"
        class="inline-flex h-10 items-center gap-2 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nuevo usuario
      </RouterLink>
    </header>

    <UsuariosPestanas />

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
      class="grid gap-3 rounded-xl bg-white p-4 ring-1 ring-slate-200 sm:grid-cols-[1fr_12rem_12rem_auto] sm:items-end"
    >
      <div>
        <label for="usuarios-buscar" class="text-sm font-medium text-slate-700">Buscar por nombre o correo</label>
        <input
          id="usuarios-buscar"
          v-model="busqueda"
          type="search"
          autocomplete="off"
          placeholder="Escriba parte del nombre o del correo"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-500 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @input="alEscribirBusqueda"
        >
      </div>
      <div>
        <label for="usuarios-estado" class="text-sm font-medium text-slate-700">Estado</label>
        <select
          id="usuarios-estado"
          v-model="consulta.estado"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @change="alCambiarFiltro"
        >
          <option value="">Todos los estados</option>
          <option v-for="e in ESTADOS" :key="e.valor" :value="e.valor">{{ e.etiqueta }}</option>
        </select>
      </div>
      <div>
        <label for="usuarios-rol" class="text-sm font-medium text-slate-700">Rol</label>
        <select
          id="usuarios-rol"
          v-model="consulta.rol"
          class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @change="alCambiarFiltro"
        >
          <option value="">Todos los roles</option>
          <option v-for="r in rolesDisponibles" :key="r.id" :value="r.name">{{ r.name }}</option>
        </select>
      </div>
      <BaseButton v-if="hayFiltros" variant="outline" @click="quitarFiltros">Quitar filtros</BaseButton>
    </div>

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudieron cargar los usuarios</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <div v-else-if="vacioSinFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Aún no hay usuarios</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">Cree el primero y elíjale uno o más roles para definir qué puede hacer.</p>
    </div>

    <div v-else-if="vacioConFiltros" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Ningún usuario coincide con la búsqueda</p>
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
      :loading="cargando"
      caption="Usuarios del panel"
      @cambiar-pagina="alCambiarPagina"
    />
  </div>
</template>

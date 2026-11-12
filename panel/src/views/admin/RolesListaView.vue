<script setup lang="ts">
/**
 * Roles: la lista.
 *
 * Un rol reúne permisos y se asigna a las personas. El rol de súper administrador está protegido y no se
 * edita ni se elimina. Un rol con usuarios no se puede eliminar: primero se le quita a cada persona.
 */
import { computed, h, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import DataTable from '@/components/base/DataTable.vue'
import type { DataTableFeatures } from '@/components/base/dataTableFeatures'
import UsuariosPestanas from '@/components/base/UsuariosPestanas.vue'
import { eliminar, listar } from '@/services/roles'
import type { RolPanelItem } from '@/types/api'
import { tomarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'
import { resumenDePermisos } from './usuarios-logica'

const filas = ref<RolPanelItem[]>([])
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const aviso = ref<string | null>(tomarAviso())

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  try {
    filas.value = await listar()
  } catch (e) {
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar los roles.')[0] ?? null
  } finally {
    cargando.value = false
  }
}
onMounted(cargar)

const vacio = computed(() => !cargando.value && !errorCarga.value && filas.value.length === 0)

const porEliminar = ref<RolPanelItem | null>(null)
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
  const rol = porEliminar.value
  if (rol === null) return
  eliminando.value = true
  errorEliminar.value = null
  try {
    await eliminar(rol.id)
    porEliminar.value = null
    aviso.value = 'Rol eliminado.'
    await cargar()
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar el rol. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

const claseAccion =
  'inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium ring-1 ring-slate-200 focus:outline-none focus-visible:ring-2'

const columnas: ColumnDef<DataTableFeatures, RolPanelItem, unknown>[] = [
  {
    id: 'name',
    header: 'Rol',
    enableSorting: false,
    cell: ({ row }) =>
      h('div', { class: 'flex flex-wrap items-center gap-2' }, [
        h('span', { class: 'font-medium text-slate-900' }, row.original.name),
        row.original.protegido ? h(BaseBadge, { variant: 'info' }, () => 'Protegido') : null,
      ]),
  },
  {
    id: 'permisos',
    header: 'Permisos',
    enableSorting: false,
    meta: { clase: 'hidden whitespace-nowrap sm:table-cell' },
    cell: ({ row }) => resumenDePermisos(row.original),
  },
  {
    id: 'usuarios',
    header: 'Usuarios',
    enableSorting: false,
    meta: { clase: 'hidden whitespace-nowrap md:table-cell' },
    cell: ({ row }) => (row.original.usuarios === 1 ? '1 usuario' : `${row.original.usuarios} usuarios`),
  },
  {
    id: 'acciones',
    header: 'Acciones',
    enableSorting: false,
    cell: ({ row }) => {
      if (row.original.protegido) return h('span', { class: 'text-xs text-slate-600' }, 'No se puede modificar')
      return h('div', { class: 'flex items-center gap-2' }, [
        h(
          RouterLink,
          {
            to: { name: 'panel.roles.editar', params: { id: row.original.id } },
            'aria-label': `Editar «${row.original.name}»`,
            class: `${claseAccion} text-gov-blue hover:bg-gov-blue-light focus-visible:ring-gov-blue`,
          },
          () => 'Editar',
        ),
        row.original.usuarios === 0
          ? h(
              'button',
              {
                type: 'button',
                'aria-label': `Eliminar «${row.original.name}»`,
                class: `${claseAccion} text-red-700 hover:bg-red-50 focus-visible:ring-red-600`,
                onClick: () => {
                  porEliminar.value = row.original
                },
              },
              'Eliminar',
            )
          : null,
      ])
    },
  },
]
</script>

<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-xl font-semibold text-slate-900">Usuarios y roles</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Un rol reúne lo que una persona puede hacer en el panel. Cree roles según las tareas (por ejemplo,
          «redactor de noticias») y asígnelos a los usuarios. Solo puede dar permisos que usted mismo tiene.
        </p>
      </div>
      <RouterLink
        :to="{ name: 'panel.roles.nuevo' }"
        class="inline-flex h-10 items-center gap-2 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nuevo rol
      </RouterLink>
    </header>

    <UsuariosPestanas />

    <div role="status" aria-live="polite">
      <p v-if="aviso" class="flex items-start justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
        <span>{{ aviso }}</span>
        <button type="button" class="font-medium underline" @click="aviso = null">Cerrar aviso</button>
      </p>
    </div>

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudieron cargar los roles</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <div v-else-if="vacio" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Aún no hay roles</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">Cree el primero y elija qué permisos reúne.</p>
    </div>

    <DataTable v-else :clicable="false" :data="filas" :columns="columnas" :loading="cargando" caption="Roles del panel" />

    <ConfirmModal
      v-model="dialogoAbierto"
      title="¿Eliminar este rol?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>Se eliminará el rol <strong>«{{ porEliminar?.name }}»</strong>. No tiene usuarios, así que nadie pierde acceso.</p>
    </ConfirmModal>
  </div>
</template>

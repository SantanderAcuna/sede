<script setup lang="ts">
/**
 * Crear o editar un rol, con sus permisos dichos en lenguaje llano y agrupados.
 *
 * Quien no es súper administrador solo puede marcar permisos que tiene; el servidor lo vuelve a comprobar.
 * El rol de súper administrador se muestra solo para leer.
 */
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/base/BaseButton.vue'
import FormField from '@/components/base/FormField.vue'
import { actualizar, crear, obtener, permisos as cargarPermisos } from '@/services/roles'
import { useSesionStore } from '@/stores/sesion'
import type { GrupoPermisosPanel, RolPanelItem } from '@/types/api'
import { dejarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'
import { administradorDe, cambiosDeRol, hayCambiosDeRol, problemasDeRol, type FormularioRol } from './usuarios-logica'

const ruta = useRoute()
const enrutador = useRouter()
const sesion = useSesionStore()

const id = ref<number | null>(ruta.params.id !== undefined ? Number(ruta.params.id) : null)
const rol = ref<RolPanelItem | null>(null)
const formulario = ref<FormularioRol>({ name: '', permisos: [] })
let original: FormularioRol = { name: '', permisos: [] }
const catalogo = ref<GrupoPermisosPanel[]>([])

const cargando = ref(true)
const errorCarga = ref<'no-existe' | 'fallo' | null>(null)
const guardando = ref(false)
const motivos = ref<string[]>([])
const erroresServidor = ref<Record<string, string | undefined>>({})
const resumen = ref<HTMLElement | null>(null)
let saliendo = false

const esNuevo = computed(() => id.value === null)
const quien = computed(() => administradorDe(sesion.usuario?.permisos ?? []))
const soloLectura = computed(() => rol.value?.protegido === true)
const hayCambios = computed(() => !esNuevo.value && hayCambiosDeRol(formulario.value, original))
const puedeDar = (nombre: string): boolean => quien.value.esSuper || quien.value.permisos.includes(nombre)

function aplicar(guardado: RolPanelItem): void {
  rol.value = guardado
  id.value = guardado.id
  formulario.value = { name: guardado.name, permisos: [...guardado.permisos] }
  original = { name: guardado.name, permisos: [...guardado.permisos] }
}

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  try {
    catalogo.value = await cargarPermisos()
    if (id.value !== null) aplicar(await obtener(id.value))
  } catch (e) {
    errorCarga.value = axios.isAxiosError(e) && e.response?.status === 404 ? 'no-existe' : 'fallo'
  } finally {
    cargando.value = false
  }
}

function marcarGrupo(grupo: GrupoPermisosPanel, marcar: boolean): void {
  const delGrupo = grupo.permisos.map((p) => p.nombre).filter(puedeDar)
  const resto = formulario.value.permisos.filter((p) => !delGrupo.includes(p))
  formulario.value.permisos = marcar ? [...resto, ...delGrupo] : resto
}

async function guardar(): Promise<void> {
  if (guardando.value || soloLectura.value) return
  erroresServidor.value = {}

  if (!esNuevo.value && !hayCambios.value) {
    dejarAviso('No había cambios que guardar.')
    saliendo = true
    await enrutador.push({ name: 'panel.roles' })
    return
  }
  const locales = problemasDeRol(formulario.value)
  if (locales.length > 0) {
    motivos.value = locales
    await nextTick()
    resumen.value?.focus()
    return
  }
  motivos.value = []

  guardando.value = true
  try {
    if (esNuevo.value) await crear(cambiosDeRol(formulario.value, null))
    else await actualizar(id.value as number, cambiosDeRol(formulario.value, original))
    saliendo = true
    dejarAviso(esNuevo.value ? 'Rol creado.' : 'Cambios guardados.')
    await enrutador.push({ name: 'panel.roles' })
  } catch (e) {
    if (axios.isAxiosError(e)) {
      const errores = (e.response?.data as { errors?: Record<string, unknown> } | undefined)?.errors
      if (errores !== null && typeof errores === 'object') {
        for (const [campo, m] of Object.entries(errores)) {
          const primero = Array.isArray(m) ? m.find((x): x is string => typeof x === 'string') : undefined
          if (primero) erroresServidor.value[campo] = primero
        }
      }
    }
    motivos.value = motivosDeError(e, 'No se pudo guardar el rol.')
    await nextTick()
    resumen.value?.focus()
  } finally {
    guardando.value = false
  }
}

let quitarGuardia: (() => void) | undefined
function avisarAlCerrarPestana(evento: BeforeUnloadEvent): void {
  if (!hayCambios.value || saliendo) return
  evento.preventDefault()
  evento.returnValue = ''
}
onMounted(() => {
  void cargar()
  window.addEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia = enrutador.beforeEach(() => {
    if (saliendo || !hayCambios.value || guardando.value) return true
    return window.confirm('Hay cambios sin guardar. Si sale, se perderán. ¿Desea salir de todos modos?')
  })
})
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia?.()
})

const tarjeta = 'space-y-4 rounded-xl bg-white p-5 ring-1 ring-slate-200 sm:p-6'
</script>

<template>
  <div class="space-y-5 pb-24 lg:pb-0">
    <header>
      <RouterLink :to="{ name: 'panel.roles' }" class="text-sm text-gov-blue underline">← Volver a roles</RouterLink>
      <h1 class="mt-2 text-xl font-semibold text-slate-900">{{ esNuevo ? 'Nuevo rol' : soloLectura ? 'Rol protegido' : 'Editar rol' }}</h1>
      <p class="mt-1 max-w-2xl text-sm text-slate-600">
        Elija lo que podrá hacer quien tenga este rol. Los permisos están agrupados por tema.
      </p>
    </header>

    <p v-if="cargando" role="status" class="text-sm text-slate-600">Cargando…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <p class="font-medium text-red-800">{{ errorCarga === 'no-existe' ? 'Este rol no existe o fue eliminado.' : 'No se pudo cargar el rol.' }}</p>
      <BaseButton v-if="errorCarga === 'fallo'" class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
    </div>

    <form v-else class="space-y-5" novalidate @submit.prevent="guardar">
      <p v-if="soloLectura" class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        El rol de súper administrador tiene todos los permisos y no se puede modificar ni eliminar desde el panel.
      </p>

      <div v-if="motivos.length > 0" ref="resumen" role="alert" tabindex="-1" class="rounded-xl border border-red-200 bg-red-50 p-4">
        <p class="flex items-center gap-2 text-sm font-medium text-red-800">
          <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
          No se guardó. Revise lo siguiente:
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-red-800">
          <li v-for="(m, i) in motivos" :key="i">{{ m }}</li>
        </ul>
      </div>

      <section :class="tarjeta" aria-labelledby="r-nombre-h">
        <h2 id="r-nombre-h" class="font-medium text-slate-900">Nombre</h2>
        <FormField v-model="formulario.name" label="Nombre del rol" required autocomplete="off" :disabled="soloLectura" :error="erroresServidor.name" hint="Por ejemplo: redactor de noticias. Solo letras, números, espacios y guiones." />
      </section>

      <section v-for="g in catalogo" :key="g.grupo" :class="tarjeta" :aria-labelledby="`g-${g.grupo}`">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 :id="`g-${g.grupo}`" class="font-medium text-slate-900">{{ g.grupo }}</h2>
          <div v-if="!soloLectura" class="flex gap-2">
            <button type="button" class="text-sm text-gov-blue underline" @click="marcarGrupo(g, true)">Marcar todos</button>
            <button type="button" class="text-sm text-gov-blue underline" @click="marcarGrupo(g, false)">Quitar todos</button>
          </div>
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
          <label
            v-for="p in g.permisos"
            :key="p.nombre"
            class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60"
          >
            <input v-model="formulario.permisos" type="checkbox" :value="p.nombre" :disabled="soloLectura || !puedeDar(p.nombre)" class="mt-1 h-4 w-4">
            <span class="text-sm text-slate-800">
              <strong>{{ p.etiqueta }}</strong>
              <span class="block text-xs text-slate-600">{{ p.descripcion }}</span>
              <span v-if="!soloLectura && !puedeDar(p.nombre)" class="mt-1 block text-xs text-amber-700">Usted no tiene este permiso, así que no puede darlo.</span>
            </span>
          </label>
        </div>
      </section>
      <p v-if="erroresServidor['permisos.0']" class="text-sm text-red-700">{{ erroresServidor['permisos.0'] }}</p>

      <div v-if="!soloLectura" class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <RouterLink :to="{ name: 'panel.roles' }" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">Cancelar</RouterLink>
          <BaseButton type="submit" variant="primary" :loading="guardando" :disabled="guardando">
            <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
            {{ esNuevo ? 'Crear rol' : 'Guardar' }}
          </BaseButton>
        </div>
      </div>
    </form>
  </div>
</template>

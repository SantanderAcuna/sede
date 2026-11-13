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
import { ACCIONES_BASE, accionesDelModulo, alternarPermiso, modulosDelGrupo, soloVerEnGrupo } from './permisos-matriz'
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
const protegido = computed(() => rol.value?.protegido === true)
/** El rol protegido nunca se edita; los demás, solo con «editar roles» (con «ver» se consultan). */
const soloLectura = computed(() => protegido.value || (!esNuevo.value && !sesion.tienePermiso('roles.editar')))
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

const ETIQUETA_ACCION: Record<string, string> = { ver: 'Ver', crear: 'Crear', editar: 'Editar', eliminar: 'Eliminar', publicar: 'Publicar' }
const modulosPorGrupo = computed(() => catalogo.value.map((g) => ({ grupo: g, modulos: modulosDelGrupo(g) })))
const hayLimitados = computed(() => !soloLectura.value && catalogo.value.some((g) => g.permisos.some((p) => !puedeDar(p.nombre))))

function estaMarcado(nombre: string): boolean {
  return formulario.value.permisos.includes(nombre)
}

function alternar(nombre: string, marcar: boolean): void {
  formulario.value.permisos = alternarPermiso(formulario.value.permisos, nombre, marcar, catalogo.value, puedeDar)
}

function marcarGrupo(grupo: GrupoPermisosPanel, marcar: boolean): void {
  const delGrupo = grupo.permisos.map((p) => p.nombre).filter(puedeDar)
  const resto = formulario.value.permisos.filter((p) => !delGrupo.includes(p))
  formulario.value.permisos = marcar ? [...resto, ...delGrupo] : resto
}

function verSoloGrupo(grupo: GrupoPermisosPanel): void {
  formulario.value.permisos = soloVerEnGrupo(formulario.value.permisos, grupo, puedeDar)
}

function contarGrupo(grupo: GrupoPermisosPanel): number {
  return grupo.permisos.filter((p) => estaMarcado(p.nombre)).length
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
      <h1 class="mt-2 text-xl font-semibold text-slate-900">{{ esNuevo ? 'Nuevo rol' : protegido ? 'Rol protegido' : soloLectura ? 'Ver rol' : 'Editar rol' }}</h1>
      <p class="mt-1 max-w-2xl text-sm text-slate-600">
        Elija, módulo por módulo, lo que podrá hacer quien tenga este rol: desde solo mirar hasta crear, editar, eliminar y publicar.
      </p>
    </header>

    <p v-if="cargando" role="status" class="text-sm text-slate-600">Cargando…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <p class="font-medium text-red-800">{{ errorCarga === 'no-existe' ? 'Este rol no existe o fue eliminado.' : 'No se pudo cargar el rol.' }}</p>
      <BaseButton v-if="errorCarga === 'fallo'" class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
    </div>

    <form v-else class="space-y-5" novalidate @submit.prevent="guardar">
      <p v-if="protegido" class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        El rol de súper administrador tiene todos los permisos y no se puede modificar ni eliminar desde el panel.
      </p>
      <p v-else-if="soloLectura" class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" data-solo-lectura>
        Está en modo de consulta: su rol puede ver este rol pero no modificarlo.
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

      <p v-if="!soloLectura" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        Cada módulo se asigna por acciones. Puede dar solo <strong>Ver</strong>, o sumar Crear, Editar, Eliminar y Publicar según lo que deba hacer este rol.
        Crear, editar, eliminar y publicar necesitan «Ver» del mismo módulo, que se marca solo.
        <span v-if="hayLimitados" class="mt-1 block text-amber-800">Las casillas apagadas son permisos que usted no tiene, así que no puede darlos.</span>
      </p>

      <section v-for="{ grupo: g, modulos } in modulosPorGrupo" :key="g.grupo" :class="tarjeta" :aria-labelledby="`g-${g.grupo}`">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
          <h2 :id="`g-${g.grupo}`" class="font-medium text-slate-900">
            {{ g.grupo }}
            <span class="ml-1 text-sm font-normal text-slate-600">({{ contarGrupo(g) }} de {{ g.permisos.length }})</span>
          </h2>
          <div v-if="!soloLectura" class="flex flex-wrap gap-x-4 gap-y-1">
            <button type="button" class="text-sm text-gov-blue underline" :aria-label="`Dar solo ver en ${g.grupo}`" @click="verSoloGrupo(g)">Solo ver</button>
            <button type="button" class="text-sm text-gov-blue underline" :aria-label="`Marcar todo en ${g.grupo}`" @click="marcarGrupo(g, true)">Marcar todo</button>
            <button type="button" class="text-sm text-gov-blue underline" :aria-label="`Quitar todo en ${g.grupo}`" @click="marcarGrupo(g, false)">Quitar todo</button>
          </div>
        </div>

        <div class="hidden grid-cols-[minmax(0,1fr)_repeat(5,5.5rem)] gap-2 border-b border-slate-200 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-600 md:grid" aria-hidden="true">
          <span>Módulo</span>
          <span v-for="a in ACCIONES_BASE" :key="a" class="text-center">{{ ETIQUETA_ACCION[a] }}</span>
        </div>

        <div class="divide-y divide-slate-100">
          <fieldset v-for="m in modulos" :key="m.modulo" class="min-w-0 py-3 md:grid md:grid-cols-[minmax(0,1fr)_repeat(5,5.5rem)] md:items-center md:gap-2 md:py-2">
            <legend class="sr-only">{{ m.etiqueta }}</legend>
            <p v-if="!(accionesDelModulo(m).otras.length === 1 && accionesDelModulo(m).otras[0]?.etiqueta === m.etiqueta)" class="mb-2 text-sm font-medium text-slate-900 md:col-start-1 md:row-start-1 md:mb-0" aria-hidden="true">{{ m.etiqueta }}</p>
            <div class="flex flex-wrap gap-2 md:contents">
              <template v-for="(p, i) in accionesDelModulo(m).base" :key="ACCIONES_BASE[i]">
                <label
                  v-if="p"
                  :title="p.descripcion"
                  class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-800 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60 md:min-h-8 md:min-w-8 md:justify-center md:border-0 md:bg-transparent md:px-0 md:has-[:checked]:bg-transparent"
                  :style="{ gridColumnStart: i + 2, gridRowStart: 1 }"
                >
                  <input
                    type="checkbox"
                    class="h-5 w-5"
                    :checked="estaMarcado(p.nombre)"
                    :disabled="soloLectura || !puedeDar(p.nombre)"
                    :aria-label="`${p.etiqueta} ${m.etiqueta.toLowerCase()}`"
                    :value="p.nombre"
                    @change="alternar(p.nombre, ($event.target as HTMLInputElement).checked)"
                  >
                  <span class="md:sr-only" aria-hidden="true">{{ p.etiqueta }}</span>
                </label>
                <span v-else class="hidden text-center text-slate-300 md:block" :style="{ gridColumnStart: i + 2, gridRowStart: 1 }" aria-hidden="true">—</span>
              </template>
            </div>
            <div v-if="accionesDelModulo(m).otras.length > 0" class="mt-2 flex flex-wrap gap-2 md:col-span-6 md:mt-1">
              <label
                v-for="p in accionesDelModulo(m).otras"
                :key="p.nombre"
                :title="p.descripcion"
                class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-800 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60"
              >
                <input
                  type="checkbox"
                  class="h-5 w-5"
                  :checked="estaMarcado(p.nombre)"
                  :disabled="soloLectura || !puedeDar(p.nombre)"
                  :value="p.nombre"
                  @change="alternar(p.nombre, ($event.target as HTMLInputElement).checked)"
                >
                <span>{{ p.etiqueta }}</span>
              </label>
            </div>
          </fieldset>
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

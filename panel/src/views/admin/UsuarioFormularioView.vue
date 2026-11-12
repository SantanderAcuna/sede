<script setup lang="ts">
/**
 * Crear o editar un usuario, en una vista propia.
 *
 * La interfaz solo ofrece los roles que quien administra puede asignar y bloquea los roles y el estado de
 * la propia cuenta; aun así, quien decide es el servidor y su mensaje se muestra tal cual. Al editar solo
 * viaja lo que cambió; la contraseña se cambia solo si se escribe una nueva.
 */
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/base/BaseButton.vue'
import FormField from '@/components/base/FormField.vue'
import { listar as listarRoles } from '@/services/roles'
import { actualizar, crear, obtener } from '@/services/usuarios'
import { useSesionStore } from '@/stores/sesion'
import type { RolPanelItem, UsuarioPanelItem } from '@/types/api'
import { dejarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'
import {
  ESTADOS,
  administradorDe,
  cambiosDeUsuario,
  formularioDeUsuario,
  formularioVacioDeUsuario,
  hayCambiosDeUsuario,
  problemasDeUsuario,
  requisitosDeClave,
  resumenDePermisos,
  rolesAsignables,
  type FormularioUsuario,
} from './usuarios-logica'

const ruta = useRoute()
const enrutador = useRouter()
const sesion = useSesionStore()

const uuid = ref<string | null>(typeof ruta.params.uuid === 'string' ? ruta.params.uuid : null)
const usuario = ref<UsuarioPanelItem | null>(null)
const formulario = ref<FormularioUsuario>(formularioVacioDeUsuario())
let original: FormularioUsuario = formularioVacioDeUsuario()
const roles = ref<RolPanelItem[]>([])

const cargando = ref(uuid.value !== null)
const errorCarga = ref<'no-existe' | 'fallo' | null>(null)
const guardando = ref(false)
const motivos = ref<string[]>([])
const erroresServidor = ref<Record<string, string | undefined>>({})
const resumen = ref<HTMLElement | null>(null)
let saliendo = false

const esNuevo = computed(() => uuid.value === null)
const quien = computed(() => administradorDe(sesion.usuario?.permisos ?? []))
const esMiCuenta = computed(() => !esNuevo.value && usuario.value !== null && usuario.value.email === sesion.usuario?.email)
const ofrecidos = computed(() => rolesAsignables(roles.value, quien.value))
/** Roles que la persona ya tiene y quien administra no puede quitar: se muestran, pero no se pueden cambiar. */
const fijos = computed(() => formulario.value.roles.filter((n) => !ofrecidos.value.some((r) => r.name === n)))
const hayCambios = computed(() => !esNuevo.value && hayCambiosDeUsuario(formulario.value, original))
const requisitos = computed(() => requisitosDeClave(formulario.value.password))

const errorDe = (campo: string): string | undefined => erroresServidor.value[campo]

function aplicar(guardado: UsuarioPanelItem): void {
  usuario.value = guardado
  uuid.value = guardado.uuid
  formulario.value = formularioDeUsuario(guardado)
  original = formularioDeUsuario(guardado)
}

async function cargar(): Promise<void> {
  listarRoles().then((r) => (roles.value = r)).catch(() => undefined)
  if (uuid.value === null) return
  cargando.value = true
  errorCarga.value = null
  try {
    aplicar(await obtener(uuid.value))
  } catch (e) {
    errorCarga.value = axios.isAxiosError(e) && e.response?.status === 404 ? 'no-existe' : 'fallo'
  } finally {
    cargando.value = false
  }
}

function erroresPorCampo(e: unknown): Record<string, string | undefined> {
  if (!axios.isAxiosError(e)) return {}
  const errores = (e.response?.data as { errors?: Record<string, unknown> } | undefined)?.errors
  if (errores === null || typeof errores !== 'object') return {}
  const resultado: Record<string, string | undefined> = {}
  for (const [campo, mensajes] of Object.entries(errores)) {
    const primero = Array.isArray(mensajes) ? mensajes.find((m): m is string => typeof m === 'string') : undefined
    if (primero) resultado[campo] = primero
  }
  return resultado
}

async function mostrarResumen(): Promise<void> {
  await nextTick()
  resumen.value?.focus()
  resumen.value?.scrollIntoView?.({ block: 'center' })
}

async function guardar(): Promise<void> {
  if (guardando.value) return
  erroresServidor.value = {}

  if (!esNuevo.value && !hayCambios.value) {
    dejarAviso('No había cambios que guardar.')
    saliendo = true
    await enrutador.push({ name: 'usuarios' })
    return
  }

  const locales = problemasDeUsuario(formulario.value, esNuevo.value)
  if (locales.length > 0) {
    motivos.value = locales
    await mostrarResumen()
    return
  }
  motivos.value = []

  guardando.value = true
  // `aplicar` fija el uuid del guardado: lo que era un alta debe decidirse antes.
  const eraNuevo = esNuevo.value
  try {
    const guardado = eraNuevo
      ? await crear(cambiosDeUsuario(formulario.value, null))
      : await actualizar(uuid.value as string, cambiosDeUsuario(formulario.value, original))
    aplicar(guardado)
    saliendo = true
    dejarAviso(eraNuevo ? 'Usuario creado.' : 'Cambios guardados.')
    await enrutador.push({ name: 'usuarios' })
  } catch (e) {
    erroresServidor.value = erroresPorCampo(e)
    motivos.value = motivosDeError(e, 'No se pudo guardar el usuario.')
    await mostrarResumen()
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

watch(
  () => problemasDeUsuario(formulario.value, esNuevo.value).length,
  (n) => {
    if (n === 0 && motivos.value.length > 0 && Object.keys(erroresServidor.value).length === 0) motivos.value = []
  },
)

const tarjeta = 'space-y-4 rounded-xl bg-white p-5 ring-1 ring-slate-200 sm:p-6'
</script>

<template>
  <div class="space-y-5 pb-24 lg:pb-0">
    <header>
      <RouterLink :to="{ name: 'usuarios' }" class="text-sm text-gov-blue underline">← Volver a usuarios</RouterLink>
      <h1 class="mt-2 text-xl font-semibold text-slate-900">{{ esNuevo ? 'Nuevo usuario' : 'Editar usuario' }}</h1>
      <p class="mt-1 max-w-2xl text-sm text-slate-600">
        {{ esNuevo
          ? 'Cree la cuenta con una contraseña temporal y elija qué roles tendrá. Indíquele a la persona que la cambie al entrar.'
          : 'Cambie los datos, el estado o los roles. Solo se guarda lo que modifique.' }}
      </p>
    </header>

    <p v-if="cargando" role="status" class="text-sm text-slate-600">Cargando…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <p class="font-medium text-red-800">
        {{ errorCarga === 'no-existe' ? 'Este usuario no existe o fue retirado.' : 'No se pudo cargar el usuario.' }}
      </p>
      <BaseButton v-if="errorCarga === 'fallo'" class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
    </div>

    <form v-else class="space-y-5" novalidate @submit.prevent="guardar">
      <div v-if="motivos.length > 0" ref="resumen" role="alert" tabindex="-1" class="rounded-xl border border-red-200 bg-red-50 p-4">
        <p class="flex items-center gap-2 text-sm font-medium text-red-800">
          <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
          No se guardó. Revise lo siguiente:
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-red-800">
          <li v-for="(m, i) in motivos" :key="i">{{ m }}</li>
        </ul>
      </div>

      <section :class="tarjeta" aria-labelledby="u-datos-h">
        <h2 id="u-datos-h" class="font-medium text-slate-900">Datos de la persona</h2>
        <FormField v-model="formulario.name" label="Nombre completo" required autocomplete="name" :error="errorDe('name')" />
        <FormField v-model="formulario.email" type="email" label="Correo institucional" required autocomplete="off" :error="errorDe('email')" hint="Con este correo entra al panel." />
        <div>
          <FormField
            v-model="formulario.password"
            type="password"
            :label="esNuevo ? 'Contraseña temporal' : 'Nueva contraseña (déjela vacía para no cambiarla)'"
            :required="esNuevo"
            autocomplete="new-password"
            :error="errorDe('password')"
          />
          <ul v-if="esNuevo || formulario.password !== ''" class="mt-2 space-y-1" aria-label="Requisitos de la contraseña">
            <li v-for="r in requisitos" :key="r.texto" class="flex items-center gap-2 text-sm" :class="r.cumple ? 'text-emerald-700' : 'text-slate-600'">
              <FaIcon :icon="r.cumple ? 'circle-check' : 'circle'" class="h-3.5 w-3.5" aria-hidden="true" />
              <span>{{ r.texto }}<span class="sr-only">{{ r.cumple ? ': cumplido' : ': pendiente' }}</span></span>
            </li>
          </ul>
        </div>
      </section>

      <section :class="tarjeta" aria-labelledby="u-acceso-h">
        <h2 id="u-acceso-h" class="font-medium text-slate-900">Acceso</h2>
        <p v-if="esMiCuenta" class="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">
          Esta es su propia cuenta: no puede cambiar su estado ni sus roles. Si lo necesita, pídaselo a otro
          administrador.
        </p>
        <div class="flex flex-col gap-1.5">
          <label for="usuario-estado" class="text-sm font-medium text-slate-700">Estado de la cuenta</label>
          <select
            id="usuario-estado"
            v-model="formulario.estado"
            :disabled="esMiCuenta"
            class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30 disabled:cursor-not-allowed disabled:bg-slate-50"
          >
            <option v-for="e in ESTADOS" :key="e.valor" :value="e.valor">{{ e.etiqueta }} — {{ e.ayuda }}</option>
          </select>
          <p v-if="errorDe('estado')" class="text-sm text-red-700">{{ errorDe('estado') }}</p>
          <p class="text-xs text-slate-600">Si no está activa, la persona no puede entrar y se cierran sus sesiones abiertas.</p>
        </div>

        <fieldset :disabled="esMiCuenta">
          <legend class="text-sm font-medium text-slate-700">Roles</legend>
          <p class="mt-1 text-xs text-slate-600">Cada rol reúne permisos. Puede dar varios. Solo ve los roles que usted puede asignar.</p>
          <p v-if="ofrecidos.length === 0 && roles.length > 0" class="mt-2 text-sm text-slate-600">No hay roles que usted pueda asignar.</p>
          <div class="mt-2 grid gap-2 sm:grid-cols-2">
            <label
              v-for="r in ofrecidos"
              :key="r.id"
              class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60"
            >
              <input v-model="formulario.roles" type="checkbox" :value="r.name" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>{{ r.name }}</strong><span class="block text-xs text-slate-600">{{ resumenDePermisos(r) }}</span></span>
            </label>
          </div>
          <p v-if="fijos.length > 0" class="mt-3 text-sm text-slate-700">
            Además tiene: <strong>{{ fijos.join(', ') }}</strong> (usted no puede cambiarlo).
          </p>
          <p v-if="errorDe('roles')" class="mt-2 text-sm text-red-700">{{ errorDe('roles') }}</p>
        </fieldset>
      </section>

      <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <RouterLink :to="{ name: 'usuarios' }" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
            Cancelar
          </RouterLink>
          <BaseButton type="submit" variant="primary" :loading="guardando" :disabled="guardando">
            <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
            {{ esNuevo ? 'Crear usuario' : 'Guardar' }}
          </BaseButton>
        </div>
      </div>
    </form>
  </div>
</template>

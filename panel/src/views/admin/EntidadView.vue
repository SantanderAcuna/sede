<script setup lang="ts">
/**
 * Datos de la Entidad: lo que el sitio público muestra en la cabecera y en el pie
 * (dirección, horario, teléfonos, correos, redes sociales y enlaces a las políticas).
 *
 * Es un registro único, así que no hay lista: una sola página, en secciones con nombre
 * (sin pestañas que escondan campos) y una barra de acciones fija. Solo se envía lo que
 * cambió, y un dato vaciado se borra. El escudo (logo) no se edita aquí: es parte de la
 * identidad visual del sitio y lo entrega el propio sitio.
 */
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/base/BaseButton.vue'
import FormField from '@/components/base/FormField.vue'
import { actualizar, obtener, politicasDisponibles } from '@/services/entidad'
import {
  REDES_SOCIALES,
  entradaDesde,
  formularioDesde,
  hayCambios as hayCambiosEn,
  problemasPorCampo,
  type FormularioEntidad,
} from './entidad-logica'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'

const { puedeEditar } = usePermisosDeModulo('entidad')
/** Solo puede consultar: tiene «ver» pero no «editar». Al crear uno nuevo se exige «crear» en la ruta. */
const soloLectura = computed(() => !puedeEditar.value)

const idBase = useId()
const enrutador = useRouter()

const formulario = ref<FormularioEntidad | null>(null)
let original: FormularioEntidad | null = null
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const guardando = ref(false)
const intentoDeGuardar = ref(false)
const motivos = ref<string[]>([])
const erroresServidor = ref<Partial<Record<string, string>>>({})
const exito = ref<string | null>(null)
const resumen = ref<HTMLElement | null>(null)
const politicasExistentes = ref<Array<{ slug: string; nombre: string }>>([])

const hayCambios = computed(() => formulario.value !== null && original !== null && hayCambiosEn(formulario.value, original))
const problemas = computed(() => (formulario.value === null ? [] : problemasPorCampo(formulario.value)))

/** Aviso junto a un campo: lo que dijo el servidor y, tras intentar guardar, lo que falta. */
function errorDe(campo: string): string | undefined {
  const delServidor = erroresServidor.value[campo]
  if (delServidor) return delServidor
  if (!intentoDeGuardar.value) return undefined
  return problemas.value.find((p) => p.campo === campo)?.mensaje
}

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  try {
    const entidad = await obtener()
    formulario.value = formularioDesde(entidad)
    original = formularioDesde(entidad)
    // Sin esta lista el pie se puede seguir editando: la opción actual se conserva.
    politicasExistentes.value = await politicasDisponibles().catch(() => [])
  } catch {
    errorCarga.value = 'No se pudieron cargar los datos de la Entidad. Revise su conexión e inténtelo de nuevo.'
  } finally {
    cargando.value = false
  }
}

/** Opciones del selector de políticas: las que existen más la actual si no estuviera en la lista. */
function opcionesPolitica(slugActual: string): Array<{ slug: string; nombre: string }> {
  const opciones = [...politicasExistentes.value]
  if (slugActual !== '' && !opciones.some((o) => o.slug === slugActual)) opciones.push({ slug: slugActual, nombre: slugActual })
  return opciones
}

function alElegirPolitica(i: number, slug: string): void {
  const fila = formulario.value?.politicas[i]
  if (!fila) return
  const anterior = opcionesPolitica(fila.slug).find((o) => o.slug === fila.slug)
  fila.slug = slug
  // El nombre del enlace acompaña a la política mientras la persona no lo haya cambiado a mano.
  if (fila.nombre.trim() === '' || fila.nombre === anterior?.nombre) {
    fila.nombre = politicasExistentes.value.find((o) => o.slug === slug)?.nombre ?? fila.nombre
  }
}

function erroresDelServidor(e: unknown): { motivos: string[]; porCampo: Partial<Record<string, string>> } {
  if (!axios.isAxiosError(e) || !e.response) return { motivos: ['No se pudo guardar. Revise su conexión e inténtelo de nuevo.'], porCampo: {} }
  const cuerpo = e.response.data as { message?: unknown; errors?: unknown } | undefined
  const porCampo: Partial<Record<string, string>> = {}
  const lista: string[] = []
  if (cuerpo?.errors !== null && typeof cuerpo?.errors === 'object') {
    for (const [campo, mensajes] of Object.entries(cuerpo.errors as Record<string, unknown>)) {
      const primero = Array.isArray(mensajes) ? mensajes.find((x): x is string => typeof x === 'string') : undefined
      if (primero) {
        porCampo[campo] = primero
        lista.push(primero)
      }
    }
  }
  if (lista.length === 0) lista.push(typeof cuerpo?.message === 'string' && cuerpo.message !== '' ? cuerpo.message : 'No se pudo guardar los cambios.')
  return { motivos: lista, porCampo }
}

async function enfocarResumen(): Promise<void> {
  await nextTick()
  resumen.value?.focus()
}

async function guardar(): Promise<void> {
  if (formulario.value === null || original === null) return
  exito.value = null
  erroresServidor.value = {}
  intentoDeGuardar.value = true

  if (!hayCambios.value) {
    motivos.value = []
    exito.value = 'No hay cambios que guardar.'
    return
  }
  if (problemas.value.length > 0) {
    motivos.value = problemas.value.map((p) => p.mensaje)
    await enfocarResumen()
    return
  }

  guardando.value = true
  motivos.value = []
  try {
    const guardada = await actualizar(entradaDesde(formulario.value, original))
    formulario.value = formularioDesde(guardada)
    original = formularioDesde(guardada)
    intentoDeGuardar.value = false
    exito.value = 'Cambios guardados. El sitio ya muestra los datos nuevos.'
  } catch (e) {
    const { motivos: lista, porCampo } = erroresDelServidor(e)
    motivos.value = lista
    erroresServidor.value = porCampo
    await enfocarResumen()
  } finally {
    guardando.value = false
  }
}

function agregarRed(): void {
  formulario.value?.redes.push({ red: 'facebook', url: '' })
}
function quitarRed(i: number): void {
  formulario.value?.redes.splice(i, 1)
}
function agregarPolitica(): void {
  formulario.value?.politicas.push({ slug: '', nombre: '' })
}
function quitarPolitica(i: number): void {
  formulario.value?.politicas.splice(i, 1)
}

function avisarAlCerrarPestana(evento: BeforeUnloadEvent): void {
  if (!hayCambios.value) return
  evento.preventDefault()
  evento.returnValue = ''
}

let quitarGuardia: (() => void) | undefined
onMounted(async () => {
  window.addEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia = enrutador.beforeEach(() => {
    if (!hayCambios.value || guardando.value) return true
    return window.confirm('Hay cambios sin guardar. Si sale, se perderán. ¿Desea salir de todos modos?')
  })
  await cargar()
})
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia?.()
})
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6 pb-24">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Datos de la Entidad</h1>
      <p class="mt-1 text-sm text-slate-600">
        Lo que el sitio público muestra en la cabecera y en el pie de página. Cambie lo que necesite y pulse «Guardar».
        Si deja un dato vacío, el sitio deja de mostrarlo. El escudo no se edita aquí: forma parte de la identidad
        visual del sitio.
      </p>
    </header>

    <div role="status" aria-live="polite">
      <p
        v-if="exito"
        class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
      >
        <FaIcon icon="circle-check" class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ exito }}
      </p>
    </div>

    <p v-if="cargando" class="rounded-xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200">Cargando los datos…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <p class="font-medium text-red-800">No se pudieron cargar los datos</p>
      <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
      <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
    </div>

    <form v-else-if="formulario" class="space-y-6" novalidate @submit.prevent="guardar">
      <p v-if="soloLectura" class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" data-solo-lectura>
        Está en modo de consulta: su rol puede ver este registro pero no modificarlo.
      </p>

      <fieldset :disabled="soloLectura" class="m-0 min-w-0 space-y-6 border-0 p-0">
      <div
        v-if="motivos.length > 0"
        ref="resumen"
        tabindex="-1"
        class="rounded-xl border border-red-200 bg-red-50 p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600"
        role="alert"
      >
        <p class="flex items-center gap-2 text-sm font-medium text-red-800">
          <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
          No se guardó. Revise lo siguiente:
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-red-800">
          <li v-for="m in motivos" :key="m">{{ m }}</li>
        </ul>
      </div>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="entidad-identidad">
        <h2 id="entidad-identidad" class="font-medium text-slate-900">Identidad</h2>
        <FormField v-model="formulario.nombre" label="Nombre de la entidad" required :error="errorDe('nombre')" />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField v-model="formulario.sigla" label="Sigla" :error="errorDe('sigla')" />
          <FormField v-model="formulario.nit" label="NIT" placeholder="891.780.009-4" :error="errorDe('nit')" />
        </div>
        <FormField
          v-model="formulario.dominio"
          label="Dirección del sitio (dominio)"
          placeholder="https://santamarta.gov.co"
          hint="Con https:// al comienzo."
          :error="errorDe('dominio')"
        />
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="entidad-atencion">
        <h2 id="entidad-atencion" class="font-medium text-slate-900">Atención al público</h2>
        <FormField v-model="formulario.direccion" label="Dirección" :error="errorDe('direccion')" />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <FormField v-model="formulario.municipio" label="Municipio" :error="errorDe('municipio')" />
          <FormField v-model="formulario.departamento" label="Departamento" :error="errorDe('departamento')" />
          <FormField v-model="formulario.pais" label="País" :error="errorDe('pais')" />
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <FormField v-model="formulario.codigo_postal" label="Código postal" :error="errorDe('codigo_postal')" />
          <div class="sm:col-span-2">
            <FormField v-model="formulario.horario" label="Horario de atención" placeholder="Lunes a viernes de 8:00 a 12:00 y de 14:00 a 18:00" :error="errorDe('horario')" />
          </div>
        </div>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="entidad-contacto">
        <h2 id="entidad-contacto" class="font-medium text-slate-900">Teléfonos y correos</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField v-model="formulario.telefono" label="Teléfono principal" type="tel" placeholder="(+57) 605 420 9600" :error="errorDe('telefono')" />
          <FormField v-model="formulario.linea_atencion" label="Línea de atención" type="tel" :error="errorDe('linea_atencion')" />
          <FormField v-model="formulario.linea_gratuita" label="Línea gratuita" placeholder="018000 955 532" :error="errorDe('linea_gratuita')" />
          <FormField v-model="formulario.linea_anticorrupcion" label="Línea anticorrupción" type="tel" :error="errorDe('linea_anticorrupcion')" />
          <FormField v-model="formulario.correo_atencion" label="Correo de atención" type="email" :error="errorDe('correo_atencion')" />
          <FormField
            v-model="formulario.correo_notificaciones_judiciales"
            label="Correo de notificaciones judiciales"
            type="email"
            :error="errorDe('correo_notificaciones_judiciales')"
          />
        </div>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="entidad-redes">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 id="entidad-redes" class="font-medium text-slate-900">Redes sociales</h2>
            <p class="mt-0.5 text-sm text-slate-600">Los enlaces que aparecen en el pie del sitio.</p>
          </div>
          <BaseButton variant="outline" size="sm" data-accion="agregar-red" @click="agregarRed">
            <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
            Agregar red social
          </BaseButton>
        </div>

        <p v-if="formulario.redes.length === 0" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
          No hay redes sociales. Si agrega una, aparecerá en el pie del sitio.
        </p>
        <ul v-else class="space-y-3">
          <li v-for="(red, i) in formulario.redes" :key="i" class="rounded-lg border border-slate-200 p-4" data-red>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[12rem_1fr_auto] sm:items-start">
              <div>
                <label :for="`${idBase}-red-${i}`" class="block text-sm font-medium text-slate-700">Red social</label>
                <select
                  :id="`${idBase}-red-${i}`"
                  v-model="red.red"
                  class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
                >
                  <option v-for="opcion in REDES_SOCIALES" :key="opcion.valor" :value="opcion.valor">{{ opcion.etiqueta }}</option>
                </select>
              </div>
              <FormField v-model="red.url" label="Dirección del perfil" placeholder="https://" :error="errorDe(`redes.${i}`)" />
              <button
                type="button"
                data-accion="quitar-red"
                class="inline-flex h-10 items-center self-end rounded-lg px-3 text-sm font-medium text-red-700 ring-1 ring-slate-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 sm:mt-[1.625rem] sm:self-start"
                :aria-label="`Quitar ${REDES_SOCIALES.find((o) => o.valor === red.red)?.etiqueta ?? 'la red social'} (${i + 1})`"
                @click="quitarRed(i)"
              >
                Quitar
              </button>
            </div>
          </li>
        </ul>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="entidad-politicas">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 id="entidad-politicas" class="font-medium text-slate-900">Políticas en el pie de página</h2>
            <p class="mt-0.5 text-sm text-slate-600">
              Los enlaces a políticas que aparecen al final de cada página. El texto de las políticas se edita en
              «Políticas del sitio».
            </p>
          </div>
          <BaseButton variant="outline" size="sm" data-accion="agregar-politica" @click="agregarPolitica">
            <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
            Agregar enlace
          </BaseButton>
        </div>

        <p v-if="formulario.politicas.length === 0" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
          No hay enlaces a políticas en el pie del sitio.
        </p>
        <ul v-else class="space-y-3">
          <li v-for="(politica, i) in formulario.politicas" :key="i" class="rounded-lg border border-slate-200 p-4" data-politica>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start">
              <div>
                <label :for="`${idBase}-pol-${i}`" class="block text-sm font-medium text-slate-700">Lleva a la política</label>
                <select
                  :id="`${idBase}-pol-${i}`"
                  :value="politica.slug"
                  class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
                  @change="alElegirPolitica(i, ($event.target as HTMLSelectElement).value)"
                >
                  <option value="" disabled>Elija una política</option>
                  <option v-for="o in opcionesPolitica(politica.slug)" :key="o.slug" :value="o.slug">{{ o.nombre }}</option>
                </select>
              </div>
              <FormField v-model="politica.nombre" label="Texto del enlace" :error="errorDe(`politicas.${i}`)" />
              <button
                type="button"
                data-accion="quitar-politica"
                class="inline-flex h-10 items-center self-end rounded-lg px-3 text-sm font-medium text-red-700 ring-1 ring-slate-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 sm:mt-[1.625rem] sm:self-start"
                :aria-label="`Quitar el enlace ${politica.nombre || i + 1}`"
                @click="quitarPolitica(i)"
              >
                Quitar
              </button>
            </div>
          </li>
        </ul>
      </section>

      </fieldset>

      <div v-if="!soloLectura" class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:left-auto lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <BaseButton type="submit" variant="primary" :loading="guardando" :disabled="guardando">
            <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
            Guardar
          </BaseButton>
        </div>
      </div>
    </form>
  </div>
</template>

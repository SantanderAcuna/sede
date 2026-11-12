<script setup lang="ts">
/**
 * Crear o editar una noticia, en una vista propia (M01-6, RF-01-013).
 *
 * La misma vista sirve a las dos rutas: `contenidos/noticias/nueva` y
 * `contenidos/noticias/:uuid`. Se crea como borrador, se le sube una imagen **con su
 * descripción** (texto alternativo, obligatorio) y se publica. El servidor no deja
 * publicar sin imagen ni sin esa descripción: aquí se avisa antes, junto al campo.
 */
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import FormField from '@/components/base/FormField.vue'
import SubirImagen from '@/components/base/SubirImagen.vue'
import { actualizar, crear, eliminar, obtener, subirImagen } from '@/services/noticias'
import type { NoticiaPanelItem } from '@/types/api'
import { dejarAviso } from './avisos'
import {
  ALT_MAX,
  DESCRIPCION_MAX,
  TITULO_MAX,
  caracteresRestantes,
  entradaDesde,
  formularioDesde,
  formularioVacio,
  hayCambios as hayCambiosEn,
  problemasPorCampo,
  type CampoNoticia,
  type FormularioNoticia,
} from './noticias-logica'
import { motivosDeError } from './politicas-logica'

const ruta = useRoute()
const enrutador = useRouter()
const idFecha = useId()

/** `null` mientras se está creando. */
const uuid = ref<string | null>(typeof ruta.params.uuid === 'string' ? ruta.params.uuid : null)
const noticia = ref<NoticiaPanelItem | null>(null)
const formulario = ref<FormularioNoticia>(formularioVacio())
let original: FormularioNoticia = formularioVacio()
original.fecha_publicacion = formulario.value.fecha_publicacion

const cargando = ref(uuid.value !== null)
const errorCarga = ref<'no-existe' | 'fallo' | null>(null)

const imagenElegida = ref<File | null>(null)
const selectorImagen = ref<InstanceType<typeof SubirImagen> | null>(null)

const guardando = ref(false)
const motivos = ref<string[]>([])
const erroresServidor = ref<Partial<Record<string, string>>>({})
const intentoDeGuardar = ref(false)
const exito = ref<string | null>(null)
const resumen = ref<HTMLElement | null>(null)
/** El resumen de errores viene de las comprobaciones locales (no del servidor). */
let resumenLocal = false
const confirmandoEliminar = ref(false)
const eliminando = ref(false)
const errorEliminar = ref<string | null>(null)
/** Tras guardar o eliminar se sale sin preguntar por cambios. */
let saliendo = false

const esNueva = computed(() => uuid.value === null)
const tieneImagen = computed(() => noticia.value?.imagen != null)
const mostrarAlt = computed(() => tieneImagen.value || imagenElegida.value !== null)
const hayCambios = computed(() => hayCambiosEn(formulario.value, original) || imagenElegida.value !== null)

const contextoImagen = computed(() => ({ tieneImagen: tieneImagen.value, imagenNueva: imagenElegida.value !== null }))
const problemas = computed(() => problemasPorCampo(formulario.value, contextoImagen.value))

/** Aviso junto a cada campo: lo que dijo el servidor y, tras intentar guardar, lo que falta. */
function errorDe(campo: CampoNoticia): string | undefined {
  const delServidor = erroresServidor.value[campo]
  if (delServidor) return delServidor
  if (!intentoDeGuardar.value) return undefined
  return problemas.value.find((p) => p.campo === campo)?.mensaje
}

const publicar = computed({
  get: () => (formulario.value.activo ? 'publicar' : 'borrador'),
  set: (valor: string) => {
    formulario.value.activo = valor === 'publicar'
  },
})

const quedaraProgramada = computed(() => {
  if (!formulario.value.activo || formulario.value.fecha_publicacion === '') return false
  return new Date(formulario.value.fecha_publicacion).getTime() > Date.now()
})

const lista = computed(() => {
  const f = formulario.value
  const hayImagen = tieneImagen.value || imagenElegida.value !== null
  return [
    { texto: 'Título de la noticia', listo: f.titulo.trim() !== '' && f.titulo.length <= TITULO_MAX },
    { texto: 'Descripción corta', listo: f.descripcion_corta.trim() !== '' && f.descripcion_corta.length <= DESCRIPCION_MAX },
    { texto: 'Contenido', listo: f.contenido.trim() !== '' },
    { texto: 'Imagen de la noticia', listo: hayImagen },
    {
      texto: 'Descripción de la imagen (para quien no puede verla)',
      listo: hayImagen && f.imagen_alt.trim() !== '' && f.imagen_alt.length <= ALT_MAX,
    },
  ]
})

function aplicar(guardada: NoticiaPanelItem): void {
  noticia.value = guardada
  uuid.value = guardada.uuid
  formulario.value = formularioDesde(guardada)
  original = formularioDesde(guardada)
}

function limpiarSeleccion(): void {
  imagenElegida.value = null
  selectorImagen.value?.reiniciar()
}

async function cargar(): Promise<void> {
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

/** El servidor responde `errors: { campo: [mensaje] }`; se queda con el primero de cada campo. */
function erroresPorCampo(e: unknown): Partial<Record<string, string>> {
  if (!axios.isAxiosError(e)) return {}
  const errores = (e.response?.data as { errors?: Record<string, unknown> } | undefined)?.errors
  if (errores === null || typeof errores !== 'object') return {}
  const resultado: Partial<Record<string, string>> = {}
  for (const [campo, mensajes] of Object.entries(errores)) {
    const primero = Array.isArray(mensajes) ? mensajes.find((m): m is string => typeof m === 'string') : undefined
    if (primero) resultado[campo === 'alt' ? 'imagen_alt' : campo] = primero
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

  exito.value = null
  erroresServidor.value = {}
  intentoDeGuardar.value = true

  if (!esNueva.value && !hayCambios.value) {
    motivos.value = []
    exito.value = 'No hay cambios que guardar.'
    return
  }

  motivos.value = problemas.value.map((p) => p.mensaje)
  resumenLocal = true
  if (motivos.value.length > 0) {
    await mostrarResumen()
    return
  }

  guardando.value = true
  const archivo = imagenElegida.value
  const quiereActivo = formulario.value.activo
  const eraNueva = esNueva.value
  try {
    const entrada = entradaDesde(formulario.value, original, { completa: eraNueva })
    // Con imagen nueva, el texto alternativo viaja con la imagen; y para publicar la
    // imagen debe existir primero, así que se guarda como borrador y se publica al final.
    if (archivo !== null) {
      delete entrada.imagen_alt
      delete entrada.activo
    }

    let guardada: NoticiaPanelItem
    if (eraNueva) {
      guardada = await crear({ ...entrada, activo: archivo === null ? quiereActivo : false })
      // La noticia ya existe: si lo siguiente falla, se sigue editando ESTA, no se crea otra.
      uuid.value = guardada.uuid
      noticia.value = guardada
    } else if (Object.keys(entrada).length > 0) {
      guardada = await actualizar(uuid.value as string, entrada)
    } else {
      guardada = noticia.value as NoticiaPanelItem
    }

    if (archivo !== null) {
      guardada = await subirImagen(guardada.uuid, archivo, formulario.value.imagen_alt.trim())
      if (quiereActivo !== guardada.activo) guardada = await actualizar(guardada.uuid, { activo: quiereActivo })
    }

    limpiarSeleccion()
    aplicar(guardada)
    const programada = guardada.activo && guardada.estado === 'programada'
    exito.value = eraNueva
      ? guardada.activo
        ? programada
          ? 'Noticia guardada y programada: se publicará en la fecha indicada.'
          : 'Noticia guardada y publicada en el sitio.'
        : 'Noticia guardada como borrador. Todavía no se ve en el sitio.'
      : guardada.activo
        ? programada
          ? 'Cambios guardados. La noticia está programada.'
          : 'Cambios guardados. La noticia está publicada.'
        : 'Cambios guardados. La noticia sigue como borrador.'

    if (eraNueva) {
      await enrutador.replace({ name: 'panel.noticias.editar', params: { uuid: guardada.uuid } })
    }
  } catch (e) {
    // Si la noticia llegó a crearse, quien edita la ve ya como existente (no se duplica).
    resumenLocal = false
    erroresServidor.value = erroresPorCampo(e)
    motivos.value = motivosDeError(e, 'No se pudo guardar la noticia.')
    if (Object.keys(erroresServidor.value).length === 0) motivos.value.unshift('No se pudo guardar la noticia.')
    await mostrarResumen()
  } finally {
    guardando.value = false
  }
}

async function confirmarEliminar(): Promise<void> {
  if (uuid.value === null) return
  eliminando.value = true
  errorEliminar.value = null
  try {
    await eliminar(uuid.value)
    saliendo = true
    confirmandoEliminar.value = false
    dejarAviso('Noticia eliminada. Ya no se ve en el sitio.')
    await enrutador.push({ name: 'panel.noticias' })
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar la noticia. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

// Quien corrige lo que faltaba no debe seguir viendo «No se guardó» con el problema ya resuelto.
watch(problemas, (lista) => {
  if (resumenLocal && lista.length === 0) motivos.value = []
})

// --- No perder lo escrito ------------------------------------------------------
// Se registra en el enrutador (no en el componente) para que funcione igual montada por
// `RouterView` que suelta: la guardia sólo existe mientras esta vista está viva.
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

// La misma vista puede recibir otra noticia sin desmontarse (p. ej. desde «nueva»).
watch(
  () => ruta.params.uuid,
  (nuevo) => {
    const destino = typeof nuevo === 'string' ? nuevo : null
    if (destino === null || destino === noticia.value?.uuid) return
    uuid.value = destino
    limpiarSeleccion()
    motivos.value = []
    exito.value = null
    void cargar()
  },
)
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6 pb-24">
    <div>
      <RouterLink
        :to="{ name: 'panel.noticias' }"
        class="inline-flex items-center gap-1 text-sm font-medium text-gov-blue hover:underline"
      >
        <FaIcon icon="chevron-left" class="h-3 w-3" aria-hidden="true" />
        Volver a noticias
      </RouterLink>
      <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold text-slate-900">{{ esNueva ? 'Nueva noticia' : 'Editar noticia' }}</h1>
        <BaseBadge v-if="noticia" :variant="noticia.estado === 'publicada' ? 'success' : noticia.estado === 'programada' ? 'info' : 'warning'" dot>
          {{ noticia.estado === 'publicada' ? 'Publicada' : noticia.estado === 'programada' ? 'Programada' : 'Borrador' }}
        </BaseBadge>
        <a
          v-if="noticia && noticia.estado === 'publicada'"
          :href="`/noticias/${noticia.slug}`"
          target="_blank"
          rel="noopener"
          class="text-sm text-gov-blue underline"
        >Ver en el sitio</a>
      </div>
      <p class="mt-1 text-sm text-slate-600">
        {{ esNueva
          ? 'Complete los datos. Se guarda como borrador y no se verá en el sitio hasta que usted decida publicarla.'
          : 'Cambie lo que necesite y pulse «Guardar». Solo se envían los cambios.' }}
      </p>
    </div>

    <div role="status" aria-live="polite">
      <p
        v-if="exito"
        class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
      >
        <FaIcon icon="circle-check" class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ exito }}
      </p>
    </div>

    <p v-if="cargando" class="rounded-xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200">Cargando la noticia…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <template v-if="errorCarga === 'no-existe'">
        <p class="font-medium text-red-800">No se encontró la noticia</p>
        <p class="mt-1 text-sm text-red-700">Puede que se haya eliminado o que la dirección esté incompleta.</p>
        <RouterLink :to="{ name: 'panel.noticias' }" class="mt-3 inline-block text-sm font-medium text-gov-blue underline">
          Volver a noticias
        </RouterLink>
      </template>
      <template v-else>
        <p class="font-medium text-red-800">No se pudo cargar la noticia</p>
        <p class="mt-1 text-sm text-red-700">Revise su conexión e inténtelo de nuevo.</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </template>
    </div>

    <form v-else class="space-y-6" novalidate @submit.prevent="guardar">
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

      <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6" role="group" aria-labelledby="noticia-lista" data-checklist>
        <h2 id="noticia-lista" class="font-medium text-slate-900">Antes de publicar</h2>
        <p class="mt-1 text-sm text-slate-600">Para que una noticia se pueda publicar necesita todo esto. Puede guardar como borrador sin completarlo.</p>
        <ul class="mt-3 space-y-1.5">
          <li v-for="item in lista" :key="item.texto" class="flex items-start gap-2 text-sm">
            <FaIcon
              :icon="item.listo ? 'circle-check' : 'circle-xmark'"
              :class="['mt-0.5 h-4 w-4 shrink-0', item.listo ? 'text-emerald-600' : 'text-amber-600']"
              aria-hidden="true"
            />
            <span class="text-slate-800">
              <strong :class="item.listo ? 'text-emerald-700' : 'text-amber-700'">{{ item.listo ? 'Listo' : 'Falta' }}:</strong>
              {{ item.texto }}
            </span>
          </li>
        </ul>
      </div>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="noticia-texto">
        <h2 id="noticia-texto" class="font-medium text-slate-900">Texto de la noticia</h2>
        <div>
          <FormField v-model="formulario.titulo" label="Título" required :error="errorDe('titulo')" hint="Claro y breve: es lo primero que lee la ciudadanía." />
          <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.titulo, TITULO_MAX) < 0 ? 'font-medium text-red-700' : 'text-slate-600'" data-contador="titulo">
            {{ caracteresRestantes(formulario.titulo, TITULO_MAX) }} caracteres disponibles (máximo {{ TITULO_MAX }})
          </p>
        </div>
        <div>
          <FormField v-model="formulario.descripcion_corta" type="textarea" :rows="3" label="Descripción corta" required :error="errorDe('descripcion_corta')" hint="Un resumen de una o dos frases. Aparece en la portada y en el listado." />
          <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.descripcion_corta, DESCRIPCION_MAX) < 0 ? 'font-medium text-red-700' : 'text-slate-600'" data-contador="descripcion">
            {{ caracteresRestantes(formulario.descripcion_corta, DESCRIPCION_MAX) }} caracteres disponibles (máximo {{ DESCRIPCION_MAX }})
          </p>
        </div>
        <FormField v-model="formulario.contenido" type="textarea" :rows="10" label="Contenido" required :error="errorDe('contenido')" hint="Texto sin formato. Separe los párrafos con una línea en blanco." />
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="noticia-imagen">
        <h2 id="noticia-imagen" class="font-medium text-slate-900">Imagen</h2>
        <SubirImagen
          ref="selectorImagen"
          :actual="noticia?.imagen ?? null"
          :error="errorDe('imagen')"
          ayuda="PNG, JPG o WEBP, hasta 5 MB. Puede ser de cualquier proporción: el sitio la recorta al mostrarla."
          @seleccionada="imagenElegida = $event"
        />
        <div v-if="mostrarAlt">
          <FormField v-model="formulario.imagen_alt" label="Descripción de la imagen (texto alternativo)" required :error="errorDe('imagen_alt')" hint="Describa qué se ve, para quien no puede verla. Ejemplo: «Alcalde cortando la cinta en la entrada del parque»." />
          <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.imagen_alt, ALT_MAX) < 0 ? 'font-medium text-red-700' : 'text-slate-600'" data-contador="alt">
            {{ caracteresRestantes(formulario.imagen_alt, ALT_MAX) }} caracteres disponibles (máximo {{ ALT_MAX }})
          </p>
        </div>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="noticia-publicacion">
        <h2 id="noticia-publicacion" class="font-medium text-slate-900">Publicación</h2>
        <div>
          <label :for="idFecha" class="block text-sm font-medium text-slate-700">Fecha de publicación</label>
          <input
            :id="idFecha"
            v-model="formulario.fecha_publicacion"
            type="datetime-local"
            class="mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30 sm:w-72"
          >
          <p class="mt-1 text-xs text-slate-600">Si es posterior a ahora, la noticia queda programada y se verá desde esa fecha.</p>
        </div>
        <fieldset>
          <legend class="text-sm font-medium text-slate-700">¿Qué quiere hacer con esta noticia?</legend>
          <div class="mt-2 space-y-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light">
              <input v-model="publicar" type="radio" name="estado-noticia" value="borrador" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>Guardar como borrador.</strong> No se ve en el sitio. Puede seguir editándola.</span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light">
              <input v-model="publicar" type="radio" name="estado-noticia" value="publicar" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>Publicar.</strong> Se ve en el sitio desde la fecha indicada. Necesita imagen y su descripción.</span>
            </label>
          </div>
          <p v-if="quedaraProgramada" class="mt-2 rounded-lg bg-blue-50 p-3 text-sm text-blue-800" data-aviso-programada>
            La fecha es futura: la noticia quedará programada y no se verá hasta entonces.
          </p>
        </fieldset>
      </section>

      <section v-if="!esNueva && noticia" class="rounded-xl border border-red-200 bg-white p-5 sm:p-6" aria-labelledby="noticia-peligro">
        <h2 id="noticia-peligro" class="font-medium text-red-800">Zona de peligro</h2>
        <p class="mt-1 text-sm text-slate-600">Eliminar quita la noticia del sitio. No se puede deshacer desde el panel.</p>
        <BaseButton class="mt-3" variant="danger" @click="confirmandoEliminar = true">Eliminar noticia</BaseButton>
      </section>

      <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:left-auto lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <RouterLink :to="{ name: 'panel.noticias' }" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
            Cancelar
          </RouterLink>
          <BaseButton type="submit" variant="primary" :loading="guardando" :disabled="guardando">
            <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
            Guardar
          </BaseButton>
        </div>
      </div>
    </form>

    <ConfirmModal
      v-model="confirmandoEliminar"
      title="¿Eliminar esta noticia?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>
        Se eliminará <strong>«{{ noticia?.titulo }}»</strong> y dejará de verse en el sitio. Esta acción no se puede
        deshacer desde el panel.
      </p>
    </ConfirmModal>
  </div>
</template>

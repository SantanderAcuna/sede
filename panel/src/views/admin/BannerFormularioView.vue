<script setup lang="ts">
/**
 * Crear o editar una diapositiva del carrusel, en una vista propia.
 *
 * Misma vista para `carrusel/nueva` y `carrusel/:uuid`. Se guarda como borrador, se le sube
 * una imagen **con su descripción** (texto alternativo, obligatorio) y se publica. Una imagen
 * nueva se sube antes de publicar: el servidor no deja publicar sin imagen.
 */
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import FormField from '@/components/base/FormField.vue'
import SubirImagen from '@/components/base/SubirImagen.vue'
import { actualizar, crear, eliminar, obtener, subirImagen } from '@/services/banners'
import type { BannerPanelItem } from '@/types/api'
import { dejarAviso } from './avisos'
import {
  ALT_MAX,
  DESCRIPCION_MAX,
  TITULO_MAX,
  caracteresRestantes,
  entradaDesde,
  explicacionDeEstado,
  formularioDesde,
  formularioVacio,
  hayCambios as hayCambiosEn,
  problemasPorCampo,
  textoDeEstado,
  type CampoBanner,
  type FormularioBanner,
} from './banners-logica'
import { motivosDeError } from './politicas-logica'

const ruta = useRoute()
const enrutador = useRouter()
const idDesde = useId()
const idHasta = useId()

const uuid = ref<string | null>(typeof ruta.params.uuid === 'string' ? ruta.params.uuid : null)
const banner = ref<BannerPanelItem | null>(null)
const formulario = ref<FormularioBanner>(formularioVacio())
let original: FormularioBanner = formularioVacio()

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
let resumenLocal = false
const confirmandoEliminar = ref(false)
const eliminando = ref(false)
const errorEliminar = ref<string | null>(null)
let saliendo = false

const esNueva = computed(() => uuid.value === null)
const tieneImagen = computed(() => banner.value?.imagen != null)
const mostrarAlt = computed(() => tieneImagen.value || imagenElegida.value !== null)
const hayCambios = computed(() => hayCambiosEn(formulario.value, original) || imagenElegida.value !== null)
const problemas = computed(() =>
  problemasPorCampo(formulario.value, { tieneImagen: tieneImagen.value, imagenNueva: imagenElegida.value !== null }),
)

function errorDe(campo: CampoBanner): string | undefined {
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

const lista = computed(() => {
  const f = formulario.value
  const hayImagen = tieneImagen.value || imagenElegida.value !== null
  return [
    { texto: 'Título de la diapositiva', listo: f.titulo.trim() !== '' && f.titulo.length <= TITULO_MAX },
    { texto: 'Imagen', listo: hayImagen },
    { texto: 'Descripción de la imagen (para quien no puede verla)', listo: hayImagen && f.imagen_alt.trim() !== '' && f.imagen_alt.length <= ALT_MAX },
  ]
})

function aplicar(guardada: BannerPanelItem): void {
  banner.value = guardada
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
    if (archivo !== null) {
      // El texto alternativo viaja con la imagen y, para publicar, la imagen debe existir primero.
      delete entrada.imagen_alt
      delete entrada.activo
    }

    let guardada: BannerPanelItem
    if (eraNueva) {
      guardada = await crear({ ...entrada, activo: archivo === null ? quiereActivo : false })
      uuid.value = guardada.uuid
      banner.value = guardada
    } else if (Object.keys(entrada).length > 0) {
      guardada = await actualizar(uuid.value as string, entrada)
    } else {
      guardada = banner.value as BannerPanelItem
    }

    if (archivo !== null) {
      guardada = await subirImagen(guardada.uuid, archivo, formulario.value.imagen_alt.trim())
      if (quiereActivo !== guardada.activo) guardada = await actualizar(guardada.uuid, { activo: quiereActivo })
    }

    limpiarSeleccion()
    aplicar(guardada)
    exito.value =
      (eraNueva ? 'Diapositiva guardada. ' : 'Cambios guardados. ') +
      (guardada.activo ? explicacionDeEstado(guardada.estado) : 'Sigue como borrador: todavía no se ve en el sitio.')

    if (eraNueva) await enrutador.replace({ name: 'panel.banners.editar', params: { uuid: guardada.uuid } })
  } catch (e) {
    resumenLocal = false
    erroresServidor.value = erroresPorCampo(e)
    motivos.value = motivosDeError(e, 'No se pudo guardar la diapositiva.')
    if (Object.keys(erroresServidor.value).length === 0) motivos.value.unshift('No se pudo guardar la diapositiva.')
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
    dejarAviso('Diapositiva eliminada. Ya no se ve en el sitio.')
    await enrutador.push({ name: 'panel.banners' })
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar la diapositiva. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

watch(problemas, (l) => {
  if (resumenLocal && l.length === 0) motivos.value = []
})

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
  () => ruta.params.uuid,
  (nuevo) => {
    const destino = typeof nuevo === 'string' ? nuevo : null
    if (destino === null || destino === banner.value?.uuid) return
    uuid.value = destino
    limpiarSeleccion()
    motivos.value = []
    exito.value = null
    void cargar()
  },
)

const clasePanel = 'space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6'
const claseFecha =
  'mt-1.5 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30'
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6 pb-24">
    <div>
      <RouterLink :to="{ name: 'panel.banners' }" class="inline-flex items-center gap-1 text-sm font-medium text-gov-blue hover:underline">
        <FaIcon icon="chevron-left" class="h-3 w-3" aria-hidden="true" />
        Volver al carrusel
      </RouterLink>
      <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold text-slate-900">{{ esNueva ? 'Nueva diapositiva' : 'Editar diapositiva' }}</h1>
        <BaseBadge v-if="banner" :variant="banner.estado === 'vigente' ? 'success' : banner.estado === 'programado' ? 'info' : banner.estado === 'borrador' ? 'warning' : 'neutral'" dot>
          {{ textoDeEstado(banner.estado) }}
        </BaseBadge>
      </div>
      <p class="mt-1 text-sm text-slate-600">
        {{ esNueva
          ? 'Complete los datos. Se guarda como borrador y no se verá en el sitio hasta que usted decida publicarla.'
          : 'Cambie lo que necesite y pulse «Guardar». Solo se envían los cambios.' }}
      </p>
    </div>

    <div role="status" aria-live="polite">
      <p v-if="exito" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
        <FaIcon icon="circle-check" class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ exito }}
      </p>
    </div>

    <p v-if="cargando" class="rounded-xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200">Cargando la diapositiva…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <template v-if="errorCarga === 'no-existe'">
        <p class="font-medium text-red-800">No se encontró la diapositiva</p>
        <p class="mt-1 text-sm text-red-700">Puede que se haya eliminado o que la dirección esté incompleta.</p>
        <RouterLink :to="{ name: 'panel.banners' }" class="mt-3 inline-block text-sm font-medium text-gov-blue underline">Volver al carrusel</RouterLink>
      </template>
      <template v-else>
        <p class="font-medium text-red-800">No se pudo cargar la diapositiva</p>
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

      <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6" role="group" aria-labelledby="banner-lista" data-checklist>
        <h2 id="banner-lista" class="font-medium text-slate-900">Antes de publicar</h2>
        <p class="mt-1 text-sm text-slate-600">Para publicarla necesita todo esto. Puede guardar como borrador sin completarlo.</p>
        <ul class="mt-3 space-y-1.5">
          <li v-for="item in lista" :key="item.texto" class="flex items-start gap-2 text-sm">
            <FaIcon :icon="item.listo ? 'circle-check' : 'circle-xmark'" :class="['mt-0.5 h-4 w-4 shrink-0', item.listo ? 'text-emerald-600' : 'text-amber-600']" aria-hidden="true" />
            <span class="text-slate-800">
              <strong :class="item.listo ? 'text-emerald-700' : 'text-amber-700'">{{ item.listo ? 'Listo' : 'Falta' }}:</strong>
              {{ item.texto }}
            </span>
          </li>
        </ul>
      </div>

      <section :class="clasePanel" aria-labelledby="banner-texto">
        <h2 id="banner-texto" class="font-medium text-slate-900">Texto y enlace</h2>
        <div>
          <FormField v-model="formulario.titulo" label="Título" required :error="errorDe('titulo')" hint="Corto y claro: se muestra grande sobre la imagen." />
          <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.titulo, TITULO_MAX) < 0 ? 'font-medium text-red-700' : 'text-slate-600'" data-contador="titulo">
            {{ caracteresRestantes(formulario.titulo, TITULO_MAX) }} caracteres disponibles (máximo {{ TITULO_MAX }})
          </p>
        </div>
        <div>
          <FormField v-model="formulario.descripcion" type="textarea" :rows="3" label="Descripción (opcional)" :error="errorDe('descripcion')" hint="Una frase que acompaña al título." />
          <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.descripcion, DESCRIPCION_MAX) < 0 ? 'font-medium text-red-700' : 'text-slate-600'" data-contador="descripcion">
            {{ caracteresRestantes(formulario.descripcion, DESCRIPCION_MAX) }} caracteres disponibles (máximo {{ DESCRIPCION_MAX }})
          </p>
        </div>
        <FormField
          v-model="formulario.enlace"
          label="Enlace (opcional)"
          :error="errorDe('enlace')"
          hint="A dónde lleva la diapositiva. Una página del sitio, como /transparencia, o una dirección que empiece por https://."
        />
      </section>

      <section :class="clasePanel" aria-labelledby="banner-imagen">
        <h2 id="banner-imagen" class="font-medium text-slate-900">Imagen</h2>
        <SubirImagen
          ref="selectorImagen"
          :actual="banner?.imagen ? { url: banner.imagen.url ?? '', alt: banner.imagen.alt ?? null } : null"
          :error="errorDe('imagen')"
          ayuda="PNG, JPG o WEBP, hasta 5 MB. Se recomienda una imagen apaisada (por ejemplo 1600 × 600): el sitio la recorta para ajustarla."
          @seleccionada="imagenElegida = $event"
        />
        <div v-if="mostrarAlt">
          <FormField v-model="formulario.imagen_alt" label="Descripción de la imagen (texto alternativo)" required :error="errorDe('imagen_alt')" hint="Describa qué se ve, para quien no puede verla. Ejemplo: «Funcionaria atendiendo a una ciudadana en la ventanilla»." />
          <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.imagen_alt, ALT_MAX) < 0 ? 'font-medium text-red-700' : 'text-slate-600'" data-contador="alt">
            {{ caracteresRestantes(formulario.imagen_alt, ALT_MAX) }} caracteres disponibles (máximo {{ ALT_MAX }})
          </p>
        </div>
      </section>

      <section :class="clasePanel" aria-labelledby="banner-publicacion">
        <h2 id="banner-publicacion" class="font-medium text-slate-900">Publicación</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label :for="idDesde" class="block text-sm font-medium text-slate-700">Se muestra desde (opcional)</label>
            <input :id="idDesde" v-model="formulario.vigente_desde" type="datetime-local" :class="claseFecha">
          </div>
          <div>
            <label :for="idHasta" class="block text-sm font-medium text-slate-700">Se muestra hasta (opcional)</label>
            <input :id="idHasta" v-model="formulario.vigente_hasta" type="datetime-local" :aria-invalid="errorDe('vigente_hasta') ? 'true' : undefined" :aria-describedby="errorDe('vigente_hasta') ? `${idHasta}-error` : undefined" :class="claseFecha">
            <p v-if="errorDe('vigente_hasta')" :id="`${idHasta}-error`" class="mt-1 text-sm font-medium text-red-700">{{ errorDe('vigente_hasta') }}</p>
          </div>
        </div>
        <p class="text-xs text-slate-600">Sin fechas, la diapositiva se ve mientras esté publicada. Con una fecha de inicio futura queda programada.</p>
        <fieldset>
          <legend class="text-sm font-medium text-slate-700">¿Qué quiere hacer con esta diapositiva?</legend>
          <div class="mt-2 space-y-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light">
              <input v-model="publicar" type="radio" name="estado-banner" value="borrador" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>Guardar como borrador.</strong> No se ve en el sitio. Puede seguir editándola.</span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light">
              <input v-model="publicar" type="radio" name="estado-banner" value="publicar" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>Publicar.</strong> Se ve en la portada dentro de sus fechas. Necesita imagen y su descripción.</span>
            </label>
          </div>
        </fieldset>
      </section>

      <section v-if="!esNueva && banner" class="rounded-xl border border-red-200 bg-white p-5 sm:p-6" aria-labelledby="banner-peligro">
        <h2 id="banner-peligro" class="font-medium text-red-800">Zona de peligro</h2>
        <p class="mt-1 text-sm text-slate-600">Eliminar quita la diapositiva del carrusel. No se puede deshacer desde el panel.</p>
        <BaseButton class="mt-3" variant="danger" @click="confirmandoEliminar = true">Eliminar diapositiva</BaseButton>
      </section>

      <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:left-auto lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <RouterLink :to="{ name: 'panel.banners' }" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">Cancelar</RouterLink>
          <BaseButton type="submit" variant="primary" :loading="guardando" :disabled="guardando">
            <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
            Guardar
          </BaseButton>
        </div>
      </div>
    </form>

    <ConfirmModal v-model="confirmandoEliminar" title="¿Eliminar esta diapositiva?" confirmar="Sí, eliminar" :cargando="eliminando" :error="errorEliminar" @confirmar="confirmarEliminar">
      <p>Se eliminará <strong>«{{ banner?.titulo }}»</strong> y dejará de verse en el sitio. Esta acción no se puede deshacer desde el panel.</p>
    </ConfirmModal>
  </div>
</template>

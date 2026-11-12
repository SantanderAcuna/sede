<script setup lang="ts">
/**
 * Noticias del sitio (M01-6, RF-01-013).
 *
 * Se crea como borrador, se le sube una imagen **con su descripción** (texto
 * alternativo, obligatorio) y se publica. Una noticia publicada y completa aparece
 * en la portada y en `/noticias`. El servidor no deja publicar una noticia sin
 * imagen o sin esa descripción: aquí se avisa antes y se explica.
 */
import { computed, onMounted, ref } from 'vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import FormField from '@/components/base/FormField.vue'
import { POR_PAGINA, actualizar, crear, eliminar, listar, subirImagen } from '@/services/noticias'
import type { NoticiaPanelItem } from '@/types/api'
import { motivosDeError } from './politicas-logica'
import {
  ALT_MAX,
  DESCRIPCION_MAX,
  TITULO_MAX,
  caracteresRestantes,
  entradaDesde,
  formularioDesde,
  formularioVacio,
  hayCambios as hayCambiosEn,
  problemasLocales,
  type FormularioNoticia,
} from './noticias-logica'

const noticias = ref<NoticiaPanelItem[]>([])
const pagina = ref(1)
const cargando = ref(true)
const errorCarga = ref<string | null>(null)

/** `null` = ninguna abierta; `'nueva'` = creando; si no, el uuid. */
const abierta = ref<string | null>(null)
const formulario = ref<FormularioNoticia | null>(null)
let original: FormularioNoticia | null = null

const imagenElegida = ref<File | null>(null)
const vistaPrevia = ref<string | null>(null)
const entradaArchivo = ref<HTMLInputElement | null>(null)

const guardando = ref(false)
const motivos = ref<string[]>([])
const exito = ref<string | null>(null)
const confirmandoEliminar = ref(false)

const actual = computed(() => noticias.value.find((n) => n.uuid === abierta.value) ?? null)
const tieneImagen = computed(() => actual.value?.imagen != null)
const hayCambios = computed(
  () =>
    formulario.value !== null &&
    original !== null &&
    (hayCambiosEn(formulario.value, original) || imagenElegida.value !== null),
)
const problemasAntes = computed(() =>
  formulario.value === null
    ? []
    : problemasLocales(formulario.value, {
        tieneImagen: tieneImagen.value,
        imagenNueva: imagenElegida.value !== null,
      }),
)
const mostrarAlt = computed(() => tieneImagen.value || imagenElegida.value !== null)

function limpiarSeleccion(): void {
  imagenElegida.value = null
  if (vistaPrevia.value) URL.revokeObjectURL(vistaPrevia.value)
  vistaPrevia.value = null
  if (entradaArchivo.value) entradaArchivo.value.value = ''
  motivos.value = []
  exito.value = null
  confirmandoEliminar.value = false
}

function abrir(uuid: string): void {
  if (uuid === abierta.value) return
  if (hayCambios.value && !window.confirm('Hay cambios sin guardar. ¿Descartarlos?')) return
  limpiarSeleccion()
  const noticia = noticias.value.find((n) => n.uuid === uuid)
  if (!noticia) return
  abierta.value = uuid
  formulario.value = formularioDesde(noticia)
  original = formularioDesde(noticia)
}

function nueva(): void {
  if (hayCambios.value && !window.confirm('Hay cambios sin guardar. ¿Descartarlos?')) return
  limpiarSeleccion()
  abierta.value = 'nueva'
  formulario.value = formularioVacio()
  original = formularioVacio()
  original.fecha_publicacion = formulario.value.fecha_publicacion
}

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  try {
    noticias.value = await listar(pagina.value)
  } catch (e) {
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar las noticias.')[0] ?? null
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)

async function irAPagina(destino: number): Promise<void> {
  if (destino < 1) return
  if (hayCambios.value && !window.confirm('Hay cambios sin guardar. ¿Descartarlos?')) return
  limpiarSeleccion()
  abierta.value = null
  formulario.value = null
  pagina.value = destino
  await cargar()
}

function alElegirArchivo(evento: Event): void {
  const archivo = (evento.target as HTMLInputElement).files?.[0] ?? null
  if (vistaPrevia.value) URL.revokeObjectURL(vistaPrevia.value)
  imagenElegida.value = archivo
  vistaPrevia.value = archivo ? URL.createObjectURL(archivo) : null
}

function reemplazarEnLista(guardada: NoticiaPanelItem, uuidAnterior: string | null): void {
  const posicion = noticias.value.findIndex((n) => n.uuid === (uuidAnterior ?? guardada.uuid))
  if (posicion >= 0) noticias.value[posicion] = guardada
  else noticias.value.unshift(guardada)
}

async function guardar(): Promise<void> {
  if (formulario.value === null || original === null) return

  motivos.value = problemasAntes.value
  exito.value = null
  if (motivos.value.length > 0) return

  guardando.value = true
  const archivo = imagenElegida.value
  const quiereActivo = formulario.value.activo
  try {
    const entrada = entradaDesde(formulario.value, original)
    // Con imagen nueva, el texto alternativo viaja con la imagen; y para publicar la
    // imagen debe existir primero, así que se guarda como borrador y se publica al final.
    if (archivo !== null) {
      delete entrada.imagen_alt
      delete entrada.activo
    }

    let guardada: NoticiaPanelItem
    let anterior: string | null = null
    if (abierta.value === 'nueva') {
      guardada = await crear({ ...entrada, activo: archivo === null ? quiereActivo : false })
      anterior = 'nueva'
    } else if (Object.keys(entrada).length > 0) {
      guardada = await actualizar(abierta.value as string, entrada)
    } else {
      guardada = actual.value as NoticiaPanelItem
    }
    abierta.value = guardada.uuid
    reemplazarEnLista(guardada, anterior)

    if (archivo !== null) {
      guardada = await subirImagen(guardada.uuid, archivo, formulario.value.imagen_alt.trim())
      reemplazarEnLista(guardada, null)
      if (quiereActivo !== guardada.activo) {
        guardada = await actualizar(guardada.uuid, { activo: quiereActivo })
        reemplazarEnLista(guardada, null)
      }
    }

    limpiarSeleccion()
    formulario.value = formularioDesde(guardada)
    original = formularioDesde(guardada)
    exito.value = guardada.activo
      ? 'Noticia guardada y publicada en el sitio.'
      : 'Noticia guardada como borrador. No se ve en el sitio.'
  } catch (e) {
    motivos.value = motivosDeError(e, 'No se pudo guardar la noticia.')
  } finally {
    guardando.value = false
  }
}

async function quitar(): Promise<void> {
  if (actual.value === null) return
  if (!confirmandoEliminar.value) {
    confirmandoEliminar.value = true
    return
  }
  try {
    await eliminar(actual.value.uuid)
    noticias.value = noticias.value.filter((n) => n.uuid !== abierta.value)
    limpiarSeleccion()
    abierta.value = null
    formulario.value = null
    original = null
  } catch (e) {
    motivos.value = motivosDeError(e, 'No se pudo eliminar la noticia.')
    confirmandoEliminar.value = false
  }
}

function estado(n: NoticiaPanelItem): { texto: string; variante: 'success' | 'warning' | 'danger' } {
  if (n.activo) return { texto: 'Publicada', variante: 'success' }
  return { texto: 'Borrador', variante: 'warning' }
}
</script>

<template>
  <div class="space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Noticias</h1>
        <p class="mt-1 text-sm text-slate-500">
          Una noticia publicada aparece en la portada y en la página de noticias. Toda imagen necesita su
          descripción para quien no puede verla.
        </p>
      </div>
      <BaseButton variant="primary" @click="nueva">
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nueva noticia
      </BaseButton>
    </header>

    <div v-if="cargando" class="text-sm text-slate-500" role="status">Cargando noticias…</div>

    <div v-else-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div>
        <p class="font-medium text-red-800">No se pudieron cargar las noticias</p>
        <p class="mt-1 text-sm text-red-600">{{ errorCarga }}</p>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-[20rem_1fr]">
      <nav aria-label="Noticias" class="space-y-2">
        <p v-if="noticias.length === 0" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500">
          Aún no hay noticias. Cree la primera con «Nueva noticia».
        </p>
        <button
          v-for="n in noticias"
          :key="n.uuid"
          type="button"
          class="w-full rounded-xl border p-3 text-left transition-colors"
          :class="n.uuid === abierta ? 'border-[var(--color-gov-blue)] bg-gov-blue-light' : 'border-slate-200 bg-white hover:bg-slate-50'"
          :aria-current="n.uuid === abierta ? 'true' : undefined"
          :data-noticia="n.slug"
          @click="abrir(n.uuid)"
        >
          <span class="block text-sm font-medium text-slate-900">{{ n.titulo }}</span>
          <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <BaseBadge :variant="estado(n).variante">{{ estado(n).texto }}</BaseBadge>
            <span v-if="n.imagen === null">· sin imagen</span>
          </span>
        </button>

        <div class="flex items-center justify-between pt-2 text-sm">
          <button type="button" class="text-[var(--color-gov-blue)] underline disabled:text-slate-400 disabled:no-underline" :disabled="pagina <= 1" @click="irAPagina(pagina - 1)">Anterior</button>
          <span class="text-slate-500">Página {{ pagina }}</span>
          <button type="button" class="text-[var(--color-gov-blue)] underline disabled:text-slate-400 disabled:no-underline" :disabled="noticias.length < POR_PAGINA" @click="irAPagina(pagina + 1)">Siguiente</button>
        </div>
      </nav>

      <div v-if="formulario" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
          <div class="text-sm">
            <a v-if="actual?.activo" :href="`/noticias/${actual.slug}`" target="_blank" rel="noopener" class="text-[var(--color-gov-blue)] underline">Ver en el sitio</a>
            <span v-else class="text-slate-500">{{ abierta === 'nueva' ? 'Noticia nueva' : 'Borrador: no se ve en el sitio' }}</span>
          </div>
          <div class="flex items-center gap-3">
            <span v-if="exito" class="flex items-center gap-1 text-sm text-emerald-600" role="status">
              <FaIcon icon="circle-check" class="h-4 w-4" aria-hidden="true" />
              {{ exito }}
            </span>
            <span v-if="hayCambios" class="text-sm text-amber-600">Cambios sin guardar</span>
            <BaseButton variant="primary" :disabled="!hayCambios || guardando" :loading="guardando" @click="guardar">
              <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
              Guardar
            </BaseButton>
          </div>
        </div>

        <div
          v-if="motivos.length > 0 || problemasAntes.length > 0 || (actual && !actual.completa)"
          class="rounded-xl border border-amber-200 bg-amber-50 p-4"
          :role="motivos.length > 0 ? 'alert' : 'status'"
          data-noticia-problemas
        >
          <p class="flex items-center gap-2 text-sm font-medium text-amber-900">
            <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
            {{ motivos.length > 0 ? 'No se guardó. Falta lo siguiente:' : 'Pendiente en esta noticia:' }}
          </p>
          <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-amber-900">
            <li v-for="m in (motivos.length > 0 ? motivos : [...(actual?.problemas ?? []), ...problemasAntes])" :key="m">{{ m }}</li>
          </ul>
        </div>

        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="noticia-texto">
          <h2 id="noticia-texto" class="font-medium text-slate-900">Texto</h2>
          <div>
            <FormField v-model="formulario.titulo" label="Título" required />
            <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.titulo, TITULO_MAX) < 0 ? 'text-red-600' : 'text-slate-500'" data-contador="titulo">
              {{ caracteresRestantes(formulario.titulo, TITULO_MAX) }} caracteres disponibles (máximo {{ TITULO_MAX }})
            </p>
          </div>
          <div>
            <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700">
              Descripción corta
              <textarea v-model="formulario.descripcion_corta" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
            </label>
            <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.descripcion_corta, DESCRIPCION_MAX) < 0 ? 'text-red-600' : 'text-slate-500'" data-contador="descripcion">
              {{ caracteresRestantes(formulario.descripcion_corta, DESCRIPCION_MAX) }} caracteres disponibles (máximo {{ DESCRIPCION_MAX }})
            </p>
          </div>
          <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700">
            Contenido
            <textarea v-model="formulario.contenido" rows="10" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" />
            <span class="text-xs font-normal text-slate-500">Texto sin formato. Un párrafo por bloque; se separan con una línea en blanco.</span>
          </label>
        </section>

        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="noticia-imagen">
          <h2 id="noticia-imagen" class="font-medium text-slate-900">Imagen</h2>
          <p class="text-sm text-slate-500">
            PNG, JPG o WEBP, hasta 5 MB. Puede ser de cualquier proporción: el sitio la recorta al mostrarla.
          </p>
          <img v-if="vistaPrevia" :src="vistaPrevia" alt="Vista previa de la imagen elegida" class="max-h-48 rounded-lg border border-slate-200">
          <img v-else-if="actual?.imagen" :src="actual.imagen.url" :alt="actual.imagen.alt ?? ''" class="max-h-48 rounded-lg border border-slate-200">
          <input ref="entradaArchivo" type="file" accept="image/png,image/jpeg,image/webp" class="block text-sm" aria-label="Elegir imagen" @change="alElegirArchivo">
          <div v-if="mostrarAlt">
            <FormField v-model="formulario.imagen_alt" label="Descripción de la imagen (texto alternativo)" required />
            <p class="mt-1 text-xs" :class="caracteresRestantes(formulario.imagen_alt, ALT_MAX) < 0 ? 'text-red-600' : 'text-slate-500'" data-contador="alt">
              Describa qué se ve, para quien no puede verla. {{ caracteresRestantes(formulario.imagen_alt, ALT_MAX) }} caracteres disponibles (máximo {{ ALT_MAX }})
            </p>
          </div>
        </section>

        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="noticia-publicacion">
          <h2 id="noticia-publicacion" class="font-medium text-slate-900">Publicación</h2>
          <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700">
            Fecha de publicación
            <input v-model="formulario.fecha_publicacion" type="datetime-local" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
          </label>
          <label class="flex items-start gap-3 text-sm text-slate-700">
            <input v-model="formulario.activo" type="checkbox" class="mt-1 h-4 w-4">
            <span><strong>Publicada</strong>: se ve en el sitio desde la fecha indicada. Sin marcar, queda como borrador.</span>
          </label>
          <label class="flex items-start gap-3 text-sm text-slate-700">
            <input v-model="formulario.destacada" type="checkbox" class="mt-1 h-4 w-4">
            <span><strong>Destacada</strong></span>
          </label>
        </section>

        <div v-if="actual" class="flex items-center gap-3">
          <BaseButton variant="danger" @click="quitar">
            <FaIcon icon="trash" class="h-4 w-4" aria-hidden="true" />
            {{ confirmandoEliminar ? 'Confirmar: eliminar la noticia' : 'Eliminar' }}
          </BaseButton>
          <span v-if="confirmandoEliminar" class="text-sm text-slate-600">Se quita del sitio. Vuelva a pulsar para confirmar.</span>
        </div>
      </div>
    </div>
  </div>
</template>

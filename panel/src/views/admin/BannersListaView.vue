<script setup lang="ts">
/**
 * Carrusel de la portada: la lista, en el orden en que se muestran las diapositivas.
 *
 * Reordenar con «Subir» y «Bajar» (botones, no arrastrar): funciona con teclado, con lector
 * de pantalla y con el dedo. Cada cambio se guarda al instante y se anuncia en palabras.
 */
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'
import { listar, ordenar } from '@/services/banners'
import type { BannerPanelItem, EstadoBanner } from '@/types/api'
import { tomarAviso } from './avisos'
import { explicacionDeEstado, mover, textoDeEstado } from './banners-logica'
import { motivosDeError } from './politicas-logica'

const filas = ref<BannerPanelItem[]>([])
const cargando = ref(true)
const errorCarga = ref<string | null>(null)
const aviso = ref<string | null>(tomarAviso())
const { puedeCrear, puedeEditar } = usePermisosDeModulo('banners')
const errorOrden = ref<string | null>(null)
const guardandoOrden = ref(false)
const anuncio = ref('')

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  try {
    filas.value = await listar()
  } catch (e) {
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar las diapositivas.')[0] ?? null
  } finally {
    cargando.value = false
  }
}
onMounted(cargar)

const vacio = computed(() => !cargando.value && !errorCarga.value && filas.value.length === 0)

const variante = (e: EstadoBanner): 'success' | 'info' | 'warning' | 'neutral' =>
  e === 'vigente' ? 'success' : e === 'programado' ? 'info' : e === 'borrador' ? 'warning' : 'neutral'

async function moverFila(b: BannerPanelItem, delta: -1 | 1): Promise<void> {
  if (guardandoOrden.value) return
  const antes = filas.value.map((f) => f.uuid)
  const despues = mover(antes, b.uuid, delta)
  if (despues.join() === antes.join()) return
  guardandoOrden.value = true
  errorOrden.value = null
  try {
    filas.value = await ordenar(despues)
    const pos = despues.indexOf(b.uuid) + 1
    anuncio.value = `«${b.titulo}» quedó en la posición ${pos} de ${despues.length}.`
  } catch (e) {
    errorOrden.value = motivosDeError(e, 'No se pudo guardar el nuevo orden. Recargue la lista e inténtelo de nuevo.')[0] ?? null
  } finally {
    guardandoOrden.value = false
  }
}

function vigencia(b: BannerPanelItem): string {
  const f = (iso: string): string =>
    new Date(iso).toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' })
  if (b.vigente_desde && b.vigente_hasta) return `Del ${f(b.vigente_desde)} al ${f(b.vigente_hasta)}`
  if (b.vigente_desde) return `Desde el ${f(b.vigente_desde)}`
  if (b.vigente_hasta) return `Hasta el ${f(b.vigente_hasta)}`
  return 'Sin fechas: se ve mientras esté publicada'
}

const claseBoton =
  'inline-flex h-10 w-10 items-center justify-center rounded-lg ring-1 ring-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue disabled:cursor-not-allowed disabled:opacity-40'
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Carrusel de la portada</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Las diapositivas se muestran en este orden, de arriba hacia abajo. {{ puedeEditar ? 'Use «Subir» y «Bajar» para cambiarlo.' : '' }}
          Solo se ven en el sitio las que están publicadas y dentro de sus fechas.
        </p>
      </div>
      <RouterLink
        v-if="puedeCrear"
        :to="{ name: 'panel.banners.nuevo' }"
        class="inline-flex h-10 items-center gap-2 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      >
        <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
        Nueva diapositiva
      </RouterLink>
    </header>

    <div role="status" aria-live="polite">
      <p v-if="aviso" class="flex items-start justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
        <span>{{ aviso }}</span>
        <button type="button" class="font-medium underline" @click="aviso = null">Cerrar aviso</button>
      </p>
      <p class="sr-only" data-anuncio>{{ anuncio }}</p>
    </div>

    <p v-if="errorOrden" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ errorOrden }}</p>

    <div v-if="errorCarga" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-medium text-red-800">No se pudieron cargar las diapositivas</p>
        <p class="mt-1 text-sm text-red-700">{{ errorCarga }}</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </div>
    </div>

    <p v-else-if="cargando" class="rounded-xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200">Cargando las diapositivas…</p>

    <div v-else-if="vacio" class="rounded-xl bg-white p-8 text-center ring-1 ring-slate-200">
      <p class="font-medium text-slate-900">Aún no hay diapositivas</p>
      <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
        Mientras no haya ninguna publicada, la portada muestra las diapositivas por defecto del sitio.
      </p>
    </div>

    <ol v-else class="space-y-3" aria-label="Diapositivas del carrusel">
      <li
        v-for="(b, i) in filas"
        :key="b.uuid"
        class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center"
      >
        <span class="hidden w-6 shrink-0 text-center text-sm font-semibold text-slate-600 sm:block" aria-hidden="true">{{ i + 1 }}</span>
        <div class="h-24 w-full shrink-0 overflow-hidden rounded-lg bg-slate-100 sm:h-20 sm:w-36">
          <img v-if="b.imagen?.url" :src="b.imagen.url" :alt="b.imagen.alt ?? ''" class="h-full w-full object-cover" loading="lazy">
          <div v-else class="flex h-full items-center justify-center text-xs text-slate-600">
            <FaIcon icon="image" class="mr-1 h-4 w-4" aria-hidden="true" /> Sin imagen
          </div>
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="min-w-0 break-words font-medium text-slate-900">{{ b.titulo }}</h2>
            <BaseBadge :variant="variante(b.estado)" dot>{{ textoDeEstado(b.estado) }}</BaseBadge>
          </div>
          <p class="mt-1 text-sm text-slate-600">{{ explicacionDeEstado(b.estado) }} {{ vigencia(b) }}.</p>
          <ul v-if="b.problemas.length > 0" class="mt-1 list-disc pl-5 text-sm text-amber-800">
            <li v-for="p in b.problemas" :key="p">{{ p }}</li>
          </ul>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <button
            v-if="puedeEditar"
            type="button"
            :class="claseBoton"
            :disabled="i === 0 || guardandoOrden"
            :aria-label="`Subir «${b.titulo}»`"
            @click="moverFila(b, -1)"
          >
            <FaIcon icon="arrow-up" class="h-4 w-4" aria-hidden="true" />
          </button>
          <button
            v-if="puedeEditar"
            type="button"
            :class="claseBoton"
            :disabled="i === filas.length - 1 || guardandoOrden"
            :aria-label="`Bajar «${b.titulo}»`"
            @click="moverFila(b, 1)"
          >
            <FaIcon icon="arrow-down" class="h-4 w-4" aria-hidden="true" />
          </button>
          <RouterLink
            :to="{ name: 'panel.banners.editar', params: { uuid: b.uuid } }"
            :aria-label="`${puedeEditar ? 'Editar' : 'Ver'} «${b.titulo}»`"
            class="inline-flex h-10 items-center rounded-lg px-3 text-sm font-medium text-gov-blue ring-1 ring-slate-300 hover:bg-gov-blue-light focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue"
          >
            {{ puedeEditar ? 'Editar' : 'Ver' }}
          </RouterLink>
        </div>
      </li>
    </ol>
  </div>
</template>

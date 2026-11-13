<script setup lang="ts">
/**
 * Editar una política del sitio, en una vista propia (T-20).
 *
 * Lo que se edita es lo que publica el sitio en `/politicas/<slug>` y lo que se descarga en
 * ODT y PDF/A. El texto va en párrafos de texto plano (separados por una línea en blanco)
 * porque alimenta esos documentos oficiales. El servidor no deja guardar una política
 * incompleta: si falta un apartado, se quita la marca de ejemplo sin el acto administrativo
 * o quedan marcadores «[por definir: …]», rechaza el cambio y dice qué falta.
 *
 * El documento oficial (el acto o el texto aprobado) se adjunta al registro de la política
 * y se guarda en la tabla común de archivos del sistema.
 */
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import FormField from '@/components/base/FormField.vue'
import { adjuntarDocumento, actualizar, documentosAdjuntos, listar, quitarArchivo } from '@/services/politicas'
import type { ArchivoItem, PoliticaPanelItem } from '@/types/api'
import {
  entradaDesde,
  formularioDesde,
  hayCambios as hayCambiosEn,
  idDeApartado,
  motivosDeError,
  problemasLocales,
  type FormularioPolitica,
} from './politicas-logica'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'

const { puedeEditar } = usePermisosDeModulo('politicas')
/** Solo puede consultar: tiene «ver» pero no «editar». Al crear uno nuevo se exige «crear» en la ruta. */
const soloLectura = computed(() => !puedeEditar.value)

const ruta = useRoute()
const enrutador = useRouter()

const slug = computed(() => (typeof ruta.params.slug === 'string' ? ruta.params.slug : ''))
const politica = ref<PoliticaPanelItem | null>(null)
const formulario = ref<FormularioPolitica | null>(null)
let original: FormularioPolitica | null = null

const cargando = ref(true)
const errorCarga = ref<'no-existe' | 'fallo' | null>(null)
const guardando = ref(false)
const motivos = ref<string[]>([])
const exito = ref<string | null>(null)
const resumen = ref<HTMLElement | null>(null)

const adjuntos = ref<ArchivoItem[]>([])
const subiendo = ref(false)
const errorAdjunto = ref<string | null>(null)
const confirmandoQuitar = ref<string | null>(null)
const entradaArchivo = ref<HTMLInputElement | null>(null)
const nuevoApartado = reactive({ titulo: '' })

const hayCambios = computed(
  () => formulario.value !== null && original !== null && hayCambiosEn(formulario.value, original),
)
const pendientes = computed(() => {
  if (politica.value === null || formulario.value === null) return []
  return [...politica.value.problemas, ...problemasLocales(formulario.value)]
})

const estado = computed<{ texto: string; variante: 'success' | 'warning' | 'danger' }>(() => {
  const p = politica.value
  if (p === null || !p.completa) return { texto: 'Incompleta', variante: 'danger' }
  return p.es_ejemplo ? { texto: 'Texto de ejemplo', variante: 'warning' } : { texto: 'Adoptada', variante: 'success' }
})

function aplicar(p: PoliticaPanelItem): void {
  politica.value = p
  formulario.value = formularioDesde(p)
  original = formularioDesde(p)
}

async function cargarAdjuntos(): Promise<void> {
  adjuntos.value = []
  errorAdjunto.value = null
  if (politica.value === null) return
  try {
    adjuntos.value = await documentosAdjuntos(politica.value.uuid)
  } catch (e) {
    // Quien gestiona políticas puede no tener permiso de archivos: se dice, no se oculta.
    errorAdjunto.value = motivosDeError(e, 'No se pudieron consultar los documentos adjuntos.')[0] ?? null
  }
}

async function cargar(): Promise<void> {
  cargando.value = true
  errorCarga.value = null
  motivos.value = []
  exito.value = null
  try {
    const encontrada = (await listar()).find((p) => p.slug === slug.value)
    if (encontrada === undefined) {
      errorCarga.value = 'no-existe'
      return
    }
    aplicar(encontrada)
    await cargarAdjuntos()
  } catch {
    errorCarga.value = 'fallo'
  } finally {
    cargando.value = false
  }
}

async function guardar(): Promise<void> {
  if (politica.value === null || formulario.value === null || original === null) return
  exito.value = null
  if (!hayCambios.value) {
    motivos.value = []
    exito.value = 'No hay cambios que guardar.'
    return
  }

  motivos.value = problemasLocales(formulario.value)
  if (motivos.value.length > 0) {
    await nextTick()
    resumen.value?.focus()
    return
  }

  guardando.value = true
  try {
    const guardada = await actualizar(politica.value.slug, entradaDesde(formulario.value, original))
    aplicar(guardada)
    exito.value = 'Política guardada. El sitio ya muestra el texto nuevo.'
  } catch (e) {
    motivos.value = motivosDeError(e, 'No se pudo guardar la política.')
    await nextTick()
    resumen.value?.focus()
  } finally {
    guardando.value = false
  }
}

function agregarApartado(): void {
  const f = formulario.value
  const titulo = nuevoApartado.titulo.trim()
  if (f === null || titulo === '') return
  f.secciones.push({ id: idDeApartado(titulo, f.secciones.map((s) => s.id)), titulo, texto: '', obligatoria: false })
  nuevoApartado.titulo = ''
}

function quitarApartado(id: string): void {
  if (formulario.value === null) return
  formulario.value.secciones = formulario.value.secciones.filter((s) => s.id !== id)
}

async function alElegirArchivo(evento: Event): Promise<void> {
  const entrada = evento.target as HTMLInputElement
  const archivo = entrada.files?.[0]
  if (!archivo || politica.value === null) return
  subiendo.value = true
  errorAdjunto.value = null
  try {
    await adjuntarDocumento(politica.value.uuid, archivo)
    await refrescar()
  } catch (e) {
    errorAdjunto.value = motivosDeError(e, 'No se pudo adjuntar el documento.').join(' ')
  } finally {
    subiendo.value = false
    if (entradaArchivo.value) entradaArchivo.value.value = ''
  }
}

async function quitar(uuid: string): Promise<void> {
  if (confirmandoQuitar.value !== uuid) {
    confirmandoQuitar.value = uuid
    return
  }
  confirmandoQuitar.value = null
  errorAdjunto.value = null
  try {
    await quitarArchivo(uuid)
    await refrescar()
  } catch (e) {
    errorAdjunto.value = motivosDeError(e, 'No se pudo quitar el documento.').join(' ')
  }
}

/** El documento oficial forma parte del resumen de la política: se vuelve a pedir, sin pisar lo escrito. */
async function refrescar(): Promise<void> {
  const nueva = (await listar()).find((p) => p.slug === slug.value)
  if (nueva) politica.value = nueva
  await cargarAdjuntos()
}

function tamano(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

// --- No perder lo escrito ------------------------------------------------------
let quitarGuardia: (() => void) | undefined

function avisarAlCerrarPestana(evento: BeforeUnloadEvent): void {
  if (!hayCambios.value) return
  evento.preventDefault()
  evento.returnValue = ''
}

onMounted(() => {
  void cargar()
  window.addEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia = enrutador.beforeEach(() => {
    if (!hayCambios.value || guardando.value) return true
    return window.confirm('Hay cambios sin guardar. Si sale, se perderán. ¿Desea salir de todos modos?')
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia?.()
})

watch(slug, () => {
  if (slug.value !== '' && politica.value !== null && slug.value !== politica.value.slug) void cargar()
})
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6 pb-24">
    <div>
      <RouterLink
        :to="{ name: 'panel.politicas' }"
        class="inline-flex items-center gap-1 text-sm font-medium text-gov-blue hover:underline"
      >
        <FaIcon icon="chevron-left" class="h-3 w-3" aria-hidden="true" />
        Volver a políticas
      </RouterLink>
      <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold text-slate-900">{{ politica?.nombre ?? 'Política' }}</h1>
        <BaseBadge v-if="politica" :variant="estado.variante" dot>{{ estado.texto }}</BaseBadge>
      </div>
      <p class="mt-1 text-sm text-slate-600">
        Cambie lo que necesite y pulse «Guardar». Cambiar el texto sube la versión solo; en la política de cookies,
        subir la versión vuelve a pedir el consentimiento a quien ya lo había dado.
      </p>
      <p v-if="politica" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
        <a :href="`/politicas/${politica.slug}`" target="_blank" rel="noopener" class="text-gov-blue underline">Ver en el sitio</a>
        <a :href="`/politicas/${politica.slug}.pdf`" class="text-gov-blue underline">Descargar PDF/A</a>
        <a :href="`/politicas/${politica.slug}.odt`" class="text-gov-blue underline">Descargar ODT</a>
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

    <p v-if="cargando" class="rounded-xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200">Cargando la política…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <template v-if="errorCarga === 'no-existe'">
        <p class="font-medium text-red-800">No se encontró la política</p>
        <p class="mt-1 text-sm text-red-700">La dirección puede estar incompleta. Las políticas son cinco y fijas.</p>
        <RouterLink :to="{ name: 'panel.politicas' }" class="mt-3 inline-block text-sm font-medium text-gov-blue underline">
          Volver a políticas
        </RouterLink>
      </template>
      <template v-else>
        <p class="font-medium text-red-800">No se pudo cargar la política</p>
        <p class="mt-1 text-sm text-red-700">Revise su conexión e inténtelo de nuevo.</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </template>
    </div>

    <form v-else-if="politica && formulario" class="space-y-6" novalidate @submit.prevent="guardar">
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

      <div v-else-if="pendientes.length > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4" role="status" data-politica-problemas>
        <p class="flex items-center gap-2 text-sm font-medium text-amber-900">
          <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
          Pendiente en esta política:
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-amber-900">
          <li v-for="m in pendientes" :key="m">{{ m }}</li>
        </ul>
      </div>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="politica-datos">
        <h2 id="politica-datos" class="font-medium text-slate-900">Datos de la política</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <FormField v-model="formulario.nombre" label="Nombre" required />
          </div>
          <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700">
            Versión
            <input
              v-model.number="formulario.version"
              type="number"
              min="1"
              max="65535"
              class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
            >
          </label>
          <FormField v-model="formulario.vigente_desde" type="date" label="Vigente desde" />
        </div>
        <fieldset class="space-y-4">
          <legend class="text-sm font-medium text-slate-700">Acto administrativo que la adopta</legend>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <FormField v-model="formulario.acto_tipo" label="Tipo" placeholder="Resolución" />
            <FormField v-model="formulario.acto_numero" label="Número" />
            <FormField v-model="formulario.acto_fecha" type="date" label="Fecha" />
          </div>
        </fieldset>
        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
          <input v-model="formulario.es_ejemplo" type="checkbox" class="mt-1 h-4 w-4">
          <span>
            <strong>El texto es de ejemplo</strong>, pendiente de aprobación por la Entidad. Se quita esta marca cuando
            el texto definitivo está cargado y su acto administrativo registrado; el servidor lo comprueba.
          </span>
        </label>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="politica-apartados">
        <div>
          <h2 id="politica-apartados" class="font-medium text-slate-900">Apartados</h2>
          <p class="mt-0.5 text-sm text-slate-600">
            Escriba un párrafo por bloque y sepárelos con una línea en blanco. Los apartados marcados como obligatorios
            son los mínimos exigidos y no se pueden quitar.
          </p>
        </div>

        <div v-for="seccion in formulario.secciones" :key="seccion.id" class="space-y-3 rounded-lg border border-slate-200 p-4" data-apartado>
          <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1">
              <FormField v-model="seccion.titulo" label="Título del apartado" />
            </div>
            <span v-if="seccion.obligatoria" class="mt-8 inline-flex shrink-0 items-center gap-1 text-xs text-slate-600">
              <FaIcon icon="lock" class="h-3 w-3" aria-hidden="true" />Obligatorio
            </span>
            <button
              v-else
              type="button"
              data-accion="quitar-apartado"
              class="mt-7 inline-flex h-10 shrink-0 items-center rounded-lg px-3 text-sm font-medium text-red-700 ring-1 ring-slate-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600"
              :aria-label="`Quitar el apartado «${seccion.titulo || seccion.id}»`"
              @click="quitarApartado(seccion.id)"
            >
              Quitar
            </button>
          </div>
          <FormField v-model="seccion.texto" type="textarea" :rows="7" label="Texto" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="flex-1">
            <FormField v-model="nuevoApartado.titulo" label="Agregar un apartado" placeholder="Título del apartado nuevo" />
          </div>
          <BaseButton variant="outline" :disabled="nuevoApartado.titulo.trim() === ''" @click="agregarApartado">
            <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
            Agregar apartado
          </BaseButton>
        </div>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="politica-documento">
        <div>
          <h2 id="politica-documento" class="font-medium text-slate-900">Documento oficial</h2>
          <p class="mt-0.5 text-sm text-slate-600">
            El acto administrativo o el texto aprobado, en PDF, ODT o Word. El sitio ofrece el más reciente junto a la
            política. Se guarda de inmediato, sin pulsar «Guardar».
          </p>
        </div>

        <p v-if="errorAdjunto" class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ errorAdjunto }}</p>

        <ul v-if="adjuntos.length > 0" class="divide-y divide-slate-100" data-politica-adjuntos>
          <li v-for="(archivo, i) in adjuntos" :key="archivo.uuid" class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="min-w-0 text-sm">
              <a :href="archivo.url" target="_blank" rel="noopener" class="break-all font-medium text-gov-blue underline">
                {{ archivo.original_filename }}
              </a>
              <span class="ml-2 text-slate-600">{{ tamano(archivo.size) }}</span>
              <BaseBadge v-if="i === 0" variant="success" class="ml-2">Publicado en el sitio</BaseBadge>
            </div>
            <BaseButton
              data-accion="quitar-documento"
              :variant="confirmandoQuitar === archivo.uuid ? 'danger' : 'outline'"
              size="sm"
              :aria-label="`${confirmandoQuitar === archivo.uuid ? 'Confirmar: quitar' : 'Quitar'} el documento ${archivo.original_filename}`"
              @click="quitar(archivo.uuid)"
            >
              {{ confirmandoQuitar === archivo.uuid ? 'Confirmar: quitar' : 'Quitar' }}
            </BaseButton>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-600">No hay documento oficial adjunto.</p>

        <div>
          <label class="block text-sm font-medium text-slate-700" for="politica-archivo">Adjuntar un documento</label>
          <input
            id="politica-archivo"
            ref="entradaArchivo"
            type="file"
            accept=".pdf,.odt,.doc,.docx"
            :disabled="subiendo"
            class="mt-1.5 block w-full text-sm text-slate-700 file:mr-3 file:h-10 file:cursor-pointer file:rounded-lg file:border-0 file:bg-gov-blue-light file:px-4 file:text-sm file:font-medium file:text-gov-blue"
            @change="alElegirArchivo"
          >
          <p v-if="subiendo" class="mt-2 text-sm text-slate-600" role="status">Subiendo…</p>
          <p class="mt-1 text-xs text-slate-600">Máximo 20 MB.</p>
        </div>
      </section>

      </fieldset>

      <div v-if="!soloLectura" class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:left-auto lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <RouterLink :to="{ name: 'panel.politicas' }" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
            Volver
          </RouterLink>
          <BaseButton type="submit" variant="primary" :loading="guardando" :disabled="guardando">
            <FaIcon icon="floppy-disk" class="h-4 w-4" aria-hidden="true" />
            Guardar
          </BaseButton>
        </div>
      </div>
    </form>
  </div>
</template>

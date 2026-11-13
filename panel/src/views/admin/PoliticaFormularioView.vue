<script setup lang="ts">
/**
 * Crear o editar una política del sitio, en una vista propia (T-20).
 *
 * Crear: la política nace como borrador oculto y de ejemplo, con apartados libres (mínimo uno). El
 * acto administrativo y su archivo se completan después de guardar; se publica solo adoptada y con el
 * permiso de publicar. Las cinco obligatorias no se ocultan ni se eliminan; las propias sí se eliminan.
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
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import { adjuntarDocumento, actualizar, crear, documentosAdjuntos, eliminar, listar, quitarArchivo } from '@/services/politicas'
import type { ArchivoItem, PoliticaPanelItem } from '@/types/api'
import { dejarAviso, tomarAviso } from './avisos'
import {
  entradaDesde,
  entradaParaCrear,
  formularioDesde,
  formularioNuevo,
  hayCambios as hayCambiosEn,
  idDeApartado,
  motivosDeError,
  problemasLocales,
  type FormularioPolitica,
} from './politicas-logica'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'

const { puedeCrear, puedeEditar, puedePublicar, puedeEliminar } = usePermisosDeModulo('politicas')

const ruta = useRoute()
const enrutador = useRouter()

/** `/configuracion/politicas/nueva`: no hay política que cargar. */
const esNueva = computed(() => ruta.name === 'panel.politicas.nueva')
/** Solo puede consultar: tiene «ver» pero no «editar» (o, al crear, no tiene «crear»). */
const soloLectura = computed(() => (esNueva.value ? !puedeCrear.value : !puedeEditar.value))

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
/** Tras guardar, crear o eliminar se sale sin preguntar por cambios. */
const saliendo = ref(false)
const confirmandoEliminar = ref(false)
const eliminando = ref(false)
const errorEliminar = ref<string | null>(null)

const hayCambios = computed(
  () => formulario.value !== null && original !== null && hayCambiosEn(formulario.value, original),
)
const pendientes = computed(() => {
  if (politica.value === null || formulario.value === null) return []
  return [...politica.value.problemas, ...problemasLocales(formulario.value)]
})

/** Por qué la casilla «Publicada» está bloqueada; `null` si se puede usar. */
const motivoSinPublicar = computed<string | null>(() => {
  const f = formulario.value
  if (f === null) return null
  if (!puedePublicar.value) return 'Para mostrarla u ocultarla en el sitio necesita el permiso «Publicar» de políticas.'
  if (f.es_ejemplo && !f.publicada) {
    return 'Para publicarla, adóptela primero: quite la marca de texto de ejemplo y complete el acto administrativo.'
  }
  return null
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
  if (esNueva.value) {
    politica.value = null
    formulario.value = formularioNuevo()
    original = formularioNuevo()
    adjuntos.value = []
    cargando.value = false
    return
  }
  try {
    const encontrada = (await listar()).find((p) => p.slug === slug.value)
    if (encontrada === undefined) {
      errorCarga.value = 'no-existe'
      return
    }
    aplicar(encontrada)
    exito.value = tomarAviso()
    await cargarAdjuntos()
  } catch {
    errorCarga.value = 'fallo'
  } finally {
    cargando.value = false
  }
}

async function guardar(): Promise<void> {
  if (formulario.value === null || original === null || (politica.value === null && !esNueva.value)) return
  exito.value = null
  if (!esNueva.value && !hayCambios.value) {
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

  if (esNueva.value) {
    await crearPolitica()
    return
  }

  const actual = politica.value
  if (actual === null) return
  guardando.value = true
  try {
    const guardada = await actualizar(actual.slug, entradaDesde(formulario.value, original))
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

async function crearPolitica(): Promise<void> {
  if (formulario.value === null) return
  guardando.value = true
  try {
    const creada = await crear(entradaParaCrear(formulario.value))
    saliendo.value = true
    dejarAviso('Política creada como borrador oculto. Complete su acto administrativo, adjunte el archivo y, cuando esté adoptada, publíquela.')
    await enrutador.push({ name: 'panel.politicas.editar', params: { slug: creada.slug } })
  } catch (e) {
    saliendo.value = false
    motivos.value = motivosDeError(e, 'No se pudo crear la política.')
    await nextTick()
    resumen.value?.focus()
  } finally {
    guardando.value = false
  }
}

async function confirmarEliminar(): Promise<void> {
  if (politica.value === null) return
  eliminando.value = true
  errorEliminar.value = null
  try {
    await eliminar(politica.value.slug)
    saliendo.value = true
    confirmandoEliminar.value = false
    dejarAviso('Política eliminada. Ya no se ve en el sitio.')
    await enrutador.push({ name: 'panel.politicas' })
  } catch (e) {
    saliendo.value = false
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar la política. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
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
  if (formulario.value === null || formulario.value.secciones.length <= 1) return
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
  if (!hayCambios.value || saliendo.value) return
  evento.preventDefault()
  evento.returnValue = ''
}

onMounted(() => {
  void cargar()
  window.addEventListener('beforeunload', avisarAlCerrarPestana)
  quitarGuardia = enrutador.beforeEach(() => {
    if (!hayCambios.value || guardando.value || saliendo.value) return true
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
        <h1 class="text-xl font-semibold text-slate-900">{{ esNueva ? 'Nueva política' : (politica?.nombre ?? 'Política') }}</h1>
        <BaseBadge v-if="politica" :variant="estado.variante" dot>{{ estado.texto }}</BaseBadge>
        <BaseBadge v-if="politica" :variant="politica.publicada ? 'success' : 'neutral'" dot>
          {{ politica.publicada ? 'Publicada' : 'Oculta' }}
        </BaseBadge>
      </div>
      <p v-if="esNueva" class="mt-1 text-sm text-slate-600">
        Escriba el nombre y al menos un apartado. La política se crea oculta, con texto de ejemplo: no se ve en el sitio
        hasta que la adopte (texto definitivo, acto administrativo y su archivo) y la publique.
      </p>
      <p v-else class="mt-1 text-sm text-slate-600">
        Cambie lo que necesite y pulse «Guardar». Cambiar el texto sube la versión solo; en la política de cookies,
        subir la versión vuelve a pedir el consentimiento a quien ya lo había dado.
      </p>
      <p v-if="politica && politica.publicada" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
        <a :href="`/politicas/${politica.slug}`" target="_blank" rel="noopener" class="text-gov-blue underline">Ver en el sitio</a>
        <a :href="`/politicas/${politica.slug}.pdf`" class="text-gov-blue underline">Descargar PDF/A</a>
        <a :href="`/politicas/${politica.slug}.odt`" class="text-gov-blue underline">Descargar ODT</a>
      </p>
      <p v-else-if="politica" class="mt-2 text-sm text-slate-600">
        Está oculta: todavía no se ve en el sitio ni se puede abrir su PDF.
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
        <p class="mt-1 text-sm text-red-700">La dirección puede estar incompleta o la política ya fue eliminada.</p>
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

      <div v-else-if="pendientes.length > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4" role="status" data-politica-problemas>
        <p class="flex items-center gap-2 text-sm font-medium text-amber-900">
          <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
          Pendiente en esta política:
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-amber-900">
          <li v-for="m in pendientes" :key="m">{{ m }}</li>
        </ul>
      </div>

      <section class="rounded-xl border border-blue-200 bg-blue-50 p-5 sm:p-6" aria-labelledby="politica-guia" data-guia-pdf>
        <h2 id="politica-guia" class="font-medium text-blue-950">Qué se imprime en el PDF</h2>
        <p class="mt-1 text-sm text-blue-900">
          El PDF y el ODT que descarga el público se arman con lo que escribe en esta pantalla; no hay otro lugar donde
          redactarlo.
        </p>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-blue-950">
          <li>
            <strong>Encabezado:</strong> el nombre, la versión, «Vigente desde», el acto administrativo (tipo, número y fecha) y el
            estado (texto de ejemplo o aprobado) salen de
            <a href="#politica-datos" class="font-medium underline">Datos de la política</a>. La Entidad (Alcaldía Distrital de
            Santa Marta) es fija.
          </li>
          <li>
            <strong>Cuerpo:</strong> cada apartado, con su título y sus párrafos, sale de
            <a href="#politica-apartados" class="font-medium underline">Apartados</a>, en el orden en que aparecen. Los datos de
            contacto o la jurisdicción se escriben allí, en su apartado.
          </li>
          <li>
            <strong>Documento oficial:</strong> el archivo
            <a href="#politica-documento" class="font-medium underline">adjunto</a> no se imprime en el PDF; el sitio lo ofrece
            aparte junto a la política.
          </li>
        </ol>
        <p v-if="!politica?.publicada" class="mt-3 text-sm text-blue-900" data-pdf-oculto>
          El PDF público se podrá abrir cuando la política esté publicada.
        </p>
        <p v-else class="mt-3 text-sm text-blue-900">
          <a
            :href="`/politicas/${politica?.slug ?? ''}.pdf`"
            target="_blank"
            rel="noopener"
            class="inline-flex h-10 items-center rounded-lg bg-white px-4 font-medium text-gov-blue ring-1 ring-blue-200 hover:bg-gov-blue-light focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue"
          >Ver el PDF actual (se abre en otra pestaña)</a>
          <span class="mt-2 block">Muestra lo ya guardado: lo que acaba de escribir aparece después de pulsar «Guardar».</span>
        </p>
      </section>

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

        <div class="rounded-lg border border-slate-200 p-3 text-sm text-slate-700" data-publicacion>
          <p v-if="esNueva">
            <strong>Se crea oculta.</strong> Cuando esté adoptada, ábrala para publicarla en el sitio.
          </p>
          <p v-else-if="politica?.es_base">
            <strong>Una de las cinco políticas obligatorias:</strong> está siempre publicada y no se puede ocultar ni eliminar.
          </p>
          <template v-else>
            <label class="flex items-start gap-3">
              <input
                v-model="formulario.publicada"
                data-campo="publicada"
                type="checkbox"
                class="mt-1 h-4 w-4"
                :disabled="motivoSinPublicar !== null"
                :aria-describedby="motivoSinPublicar ? 'politica-publicada-ayuda' : undefined"
              >
              <span><strong>Publicada en el sitio.</strong> Si no la marca, la política sigue oculta para la ciudadanía.</span>
            </label>
            <p v-if="motivoSinPublicar" id="politica-publicada-ayuda" class="mt-2 text-slate-600">{{ motivoSinPublicar }}</p>
          </template>
        </div>
      </section>

      <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="politica-apartados">
        <div>
          <h2 id="politica-apartados" class="font-medium text-slate-900">Apartados</h2>
          <p class="mt-0.5 text-sm text-slate-600">
            Escriba un párrafo por bloque y sepárelos con una línea en blanco.
            <template v-if="esNueva">Agregue los apartados que necesite; debe quedar al menos uno, con título y texto.</template>
            <template v-else>Los apartados marcados como obligatorios son los mínimos exigidos y no se pueden quitar.</template>
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
              v-else-if="formulario.secciones.length > 1"
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

        <p v-if="esNueva" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700" data-adjunto-pendiente>
          Podrá adjuntar el acto administrativo o el texto aprobado después de guardar la política por primera vez.
        </p>
        <template v-else>
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
        </template>
      </section>

      <section
        v-if="!esNueva && politica && !politica.es_base && puedeEliminar"
        class="rounded-xl border border-red-200 bg-white p-5 sm:p-6"
        aria-labelledby="politica-peligro"
      >
        <h2 id="politica-peligro" class="font-medium text-red-800">Zona de peligro</h2>
        <p class="mt-1 text-sm text-slate-600">
          Eliminar borra la política, su texto y el documento oficial adjunto. No se puede deshacer desde el panel.
        </p>
        <BaseButton class="mt-3" variant="danger" type="button" data-accion="eliminar-politica" @click="confirmandoEliminar = true">
          Eliminar política
        </BaseButton>
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

    <ConfirmModal
      v-model="confirmandoEliminar"
      title="¿Eliminar esta política?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>
        Se eliminará <strong>«{{ politica?.nombre }}»</strong> con su texto y el documento oficial que tenga adjunto, y
        dejará de verse en el sitio. Esta acción no se puede deshacer desde el panel.
      </p>
    </ConfirmModal>
  </div>
</template>

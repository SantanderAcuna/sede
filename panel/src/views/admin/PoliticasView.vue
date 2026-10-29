<script setup lang="ts">
/**
 * Las cinco políticas del sitio (T-20): texto, versión, vigencia, acto
 * administrativo y documento oficial.
 *
 * Lo que se edita aquí es lo que publica el sitio en `/politicas/<slug>` y lo que
 * se descarga en ODT y PDF/A. Hoy el texto sembrado es de ejemplo (`es_ejemplo`):
 * cuando la Entidad apruebe el texto definitivo se reemplaza aquí, se registra su
 * acto y se quita la marca de ejemplo. El servidor no deja guardar una política
 * incompleta: si falta un apartado, o se quita la marca de ejemplo sin el acto, o
 * quedan marcadores «[por definir: …]», rechaza el cambio y dice qué falta.
 *
 * El documento oficial (el acto o el texto aprobado) se adjunta al registro de la
 * política y se guarda en la tabla común de archivos del sistema.
 */
import { computed, onMounted, reactive, ref } from 'vue'
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

const politicas = ref<PoliticaPanelItem[]>([])
const cargando = ref(true)
const errorCarga = ref<string | null>(null)

const seleccionada = ref<string | null>(null)
const formulario = ref<FormularioPolitica | null>(null)
let original: FormularioPolitica | null = null

const guardando = ref(false)
const motivos = ref<string[]>([])
const exito = ref<string | null>(null)

const adjuntos = ref<ArchivoItem[]>([])
const subiendo = ref(false)
const errorAdjunto = ref<string | null>(null)
const confirmandoQuitar = ref<string | null>(null)
const entradaArchivo = ref<HTMLInputElement | null>(null)

const actual = computed(() => politicas.value.find((p) => p.slug === seleccionada.value) ?? null)
const hayCambios = computed(() =>
  formulario.value !== null && original !== null && hayCambiosEn(formulario.value, original),
)
const problemasAntes = computed(() => (formulario.value === null ? [] : problemasLocales(formulario.value)))

const nuevoApartado = reactive({ titulo: '' })

function estado(politica: PoliticaPanelItem): { texto: string; variante: 'success' | 'warning' | 'danger' } {
  if (!politica.completa) return { texto: 'Incompleta', variante: 'danger' }
  if (politica.es_ejemplo) return { texto: 'Texto de ejemplo', variante: 'warning' }
  return { texto: 'Adoptada', variante: 'success' }
}

async function cargarAdjuntos(): Promise<void> {
  adjuntos.value = []
  errorAdjunto.value = null
  if (actual.value === null) return
  try {
    adjuntos.value = await documentosAdjuntos(actual.value.uuid)
  } catch (e) {
    // Quien gestiona políticas puede no tener permiso de archivos: se dice, no se oculta.
    errorAdjunto.value = motivosDeError(e, 'No se pudieron consultar los documentos adjuntos.')[0] ?? null
  }
}

async function elegir(slug: string): Promise<void> {
  if (slug === seleccionada.value) return
  if (hayCambios.value && !window.confirm('Hay cambios sin guardar en esta política. ¿Descartarlos?')) return

  seleccionada.value = slug
  const politica = politicas.value.find((p) => p.slug === slug)
  if (politica) {
    formulario.value = formularioDesde(politica)
    original = formularioDesde(politica)
  }
  motivos.value = []
  exito.value = null
  confirmandoQuitar.value = null
  await cargarAdjuntos()
}

onMounted(async () => {
  try {
    politicas.value = await listar()
    const primera = politicas.value[0]
    if (primera) await elegir(primera.slug)
  } catch (e) {
    errorCarga.value = motivosDeError(e, 'No se pudieron cargar las políticas.')[0] ?? null
  } finally {
    cargando.value = false
  }
})

async function guardar(): Promise<void> {
  if (actual.value === null || formulario.value === null || original === null) return

  motivos.value = problemasLocales(formulario.value)
  exito.value = null
  if (motivos.value.length > 0) return

  guardando.value = true
  try {
    const guardada = await actualizar(actual.value.slug, entradaDesde(formulario.value, original))
    const posicion = politicas.value.findIndex((p) => p.slug === guardada.slug)
    if (posicion >= 0) politicas.value[posicion] = guardada
    formulario.value = formularioDesde(guardada)
    original = formularioDesde(guardada)
    exito.value = 'Política guardada. El sitio ya muestra el texto nuevo.'
  } catch (e) {
    motivos.value = motivosDeError(e, 'No se pudo guardar la política.')
  } finally {
    guardando.value = false
  }
}

function agregarApartado(): void {
  const formularioActual = formulario.value
  const titulo = nuevoApartado.titulo.trim()
  if (formularioActual === null || titulo === '') return
  formularioActual.secciones.push({
    id: idDeApartado(titulo, formularioActual.secciones.map((s) => s.id)),
    titulo,
    texto: '',
    obligatoria: false,
  })
  nuevoApartado.titulo = ''
}

function quitarApartado(id: string): void {
  if (formulario.value === null) return
  formulario.value.secciones = formulario.value.secciones.filter((s) => s.id !== id)
}

async function alElegirArchivo(evento: Event): Promise<void> {
  const entrada = evento.target as HTMLInputElement
  const archivo = entrada.files?.[0]
  if (!archivo || actual.value === null) return

  subiendo.value = true
  errorAdjunto.value = null
  try {
    await adjuntarDocumento(actual.value.uuid, archivo)
    await refrescarPolitica()
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
    await refrescarPolitica()
  } catch (e) {
    errorAdjunto.value = motivosDeError(e, 'No se pudo quitar el documento.').join(' ')
  }
}

/** Vuelve a pedir la lista: el documento oficial adjunto forma parte de cada política. */
async function refrescarPolitica(): Promise<void> {
  const lista = await listar()
  const slug = seleccionada.value
  const nueva = lista.find((p) => p.slug === slug)
  const posicion = politicas.value.findIndex((p) => p.slug === slug)
  if (nueva && posicion >= 0) politicas.value[posicion] = nueva
  await cargarAdjuntos()
}

function tamano(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
</script>

<template>
  <div class="space-y-6">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Políticas del sitio</h1>
      <p class="mt-1 text-sm text-slate-500">
        Lo que se edita aquí es lo que publica el sitio y lo que se descarga en ODT y PDF/A.
        Mientras el texto sea de ejemplo, el sitio lo avisa y no lo deja indexar.
      </p>
    </header>

    <div v-if="cargando" class="text-sm text-slate-500" role="status">Cargando políticas…</div>

    <div
      v-else-if="errorCarga"
      class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5"
      role="alert"
    >
      <FaIcon icon="circle-xmark" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" aria-hidden="true" />
      <div>
        <p class="font-medium text-red-800">No se pudieron cargar las políticas</p>
        <p class="mt-1 text-sm text-red-600">{{ errorCarga }}</p>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-[18rem_1fr]">
      <!-- Lista -->
      <nav aria-label="Políticas" class="space-y-2">
        <button
          v-for="politica in politicas"
          :key="politica.slug"
          type="button"
          class="w-full rounded-xl border p-3 text-left transition-colors"
          :class="politica.slug === seleccionada
            ? 'border-[var(--color-gov-blue)] bg-gov-blue-light'
            : 'border-slate-200 bg-white hover:bg-slate-50'"
          :aria-current="politica.slug === seleccionada ? 'true' : undefined"
          :data-politica="politica.slug"
          @click="elegir(politica.slug)"
        >
          <span class="block text-sm font-medium text-slate-900">{{ politica.nombre }}</span>
          <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <BaseBadge :variant="estado(politica).variante">{{ estado(politica).texto }}</BaseBadge>
            <span>v{{ politica.version }}</span>
            <span v-if="politica.documento_oficial">· con documento oficial</span>
          </span>
        </button>
      </nav>

      <!-- Editor -->
      <div v-if="actual && formulario" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
          <div class="flex flex-wrap items-center gap-3 text-sm">
            <a :href="`/politicas/${actual.slug}`" target="_blank" rel="noopener" class="text-[var(--color-gov-blue)] underline">
              Ver en el sitio
            </a>
            <a :href="`/politicas/${actual.slug}.pdf`" class="text-[var(--color-gov-blue)] underline">PDF/A</a>
            <a :href="`/politicas/${actual.slug}.odt`" class="text-[var(--color-gov-blue)] underline">ODT</a>
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

        <!-- Qué falta -->
        <div
          v-if="motivos.length > 0 || problemasAntes.length > 0 || !actual.completa"
          class="rounded-xl border border-amber-200 bg-amber-50 p-4"
          :role="motivos.length > 0 ? 'alert' : 'status'"
          data-politica-problemas
        >
          <p class="flex items-center gap-2 text-sm font-medium text-amber-900">
            <FaIcon icon="triangle-exclamation" class="h-4 w-4" aria-hidden="true" />
            {{ motivos.length > 0 ? 'No se guardó. Falta lo siguiente:' : 'Pendiente en esta política:' }}
          </p>
          <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-amber-900">
            <li v-for="m in (motivos.length > 0 ? motivos : [...actual.problemas, ...problemasAntes])" :key="m">{{ m }}</li>
          </ul>
        </div>

        <!-- Datos -->
        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="politica-datos">
          <h2 id="politica-datos" class="font-medium text-slate-900">Datos de la política</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField v-model="formulario.nombre" label="Nombre" required />
            <div class="grid grid-cols-2 gap-4">
              <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700">
                Versión
                <input
                  v-model.number="formulario.version"
                  type="number"
                  min="1"
                  max="65535"
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                >
              </label>
              <FormField v-model="formulario.vigente_desde" type="date" label="Vigente desde" />
            </div>
            <FormField v-model="formulario.acto_tipo" label="Acto administrativo (tipo)" placeholder="Resolución" />
            <FormField v-model="formulario.acto_numero" label="Acto administrativo (número)" />
            <FormField v-model="formulario.acto_fecha" type="date" label="Acto administrativo (fecha)" />
          </div>
          <label class="flex items-start gap-3 text-sm text-slate-700">
            <input v-model="formulario.es_ejemplo" type="checkbox" class="mt-1 h-4 w-4">
            <span>
              <strong>El texto es de ejemplo</strong>, pendiente de aprobación por la Entidad.
              Se quita esta marca cuando el texto definitivo está cargado y su acto administrativo
              registrado; el servidor lo comprueba.
            </span>
          </label>
          <p class="text-xs text-slate-500">
            Cambiar el texto sube la versión solo. En la política de cookies, subir la versión vuelve a pedir
            el consentimiento a quien ya lo había dado.
          </p>
        </section>

        <!-- Apartados -->
        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="politica-apartados">
          <div>
            <h2 id="politica-apartados" class="font-medium text-slate-900">Apartados</h2>
            <p class="mt-0.5 text-sm text-slate-500">
              Un párrafo por bloque; se separan con una línea en blanco. Los apartados con candado son los
              mínimos exigidos y no se pueden quitar.
            </p>
          </div>

          <div v-for="seccion in formulario.secciones" :key="seccion.id" class="space-y-3 rounded-lg border border-slate-200 p-4">
            <div class="flex items-start gap-3">
              <div class="flex-1">
                <FormField v-model="seccion.titulo" label="Título del apartado" />
              </div>
              <span v-if="seccion.obligatoria" class="mt-7 text-xs text-slate-500">
                <FaIcon icon="lock" class="mr-1 h-3 w-3" aria-hidden="true" />Obligatorio
              </span>
              <button
                v-else
                type="button"
                class="mt-7 text-red-500 hover:text-red-700"
                title="Quitar apartado"
                @click="quitarApartado(seccion.id)"
              >
                <FaIcon icon="trash" class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
            <FormField v-model="seccion.texto" type="textarea" :rows="6" label="Texto" />
          </div>

          <div class="flex items-end gap-3">
            <div class="flex-1">
              <FormField v-model="nuevoApartado.titulo" label="Agregar un apartado" placeholder="Título del apartado nuevo" />
            </div>
            <BaseButton variant="secondary" size="sm" :disabled="nuevoApartado.titulo.trim() === ''" @click="agregarApartado">
              <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
              Agregar
            </BaseButton>
          </div>
        </section>

        <!-- Documento oficial -->
        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6" aria-labelledby="politica-documento">
          <div>
            <h2 id="politica-documento" class="font-medium text-slate-900">Documento oficial</h2>
            <p class="mt-0.5 text-sm text-slate-500">
              El acto administrativo o el texto aprobado, en PDF, ODT o Word. El sitio ofrece el más reciente
              junto a la política. Se guarda en el almacén común de archivos del sistema.
            </p>
          </div>

          <p v-if="errorAdjunto" class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ errorAdjunto }}</p>

          <ul v-if="adjuntos.length > 0" class="divide-y divide-slate-100" data-politica-adjuntos>
            <li v-for="(archivo, i) in adjuntos" :key="archivo.uuid" class="flex flex-wrap items-center justify-between gap-3 py-3">
              <div class="text-sm">
                <a :href="archivo.url" target="_blank" rel="noopener" class="font-medium text-[var(--color-gov-blue)] underline">
                  {{ archivo.original_filename }}
                </a>
                <span class="ml-2 text-slate-500">{{ tamano(archivo.size) }}</span>
                <BaseBadge v-if="i === 0" variant="success" class="ml-2">Publicado en el sitio</BaseBadge>
              </div>
              <BaseButton
                :variant="confirmandoQuitar === archivo.uuid ? 'danger' : 'outline'"
                size="sm"
                @click="quitar(archivo.uuid)"
              >
                <FaIcon icon="trash" class="h-4 w-4" aria-hidden="true" />
                {{ confirmandoQuitar === archivo.uuid ? 'Confirmar: quitar' : 'Quitar' }}
              </BaseButton>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-500">No hay documento oficial adjunto.</p>

          <div>
            <label class="block text-sm font-medium text-slate-700" for="politica-archivo">
              Adjuntar un documento
            </label>
            <input
              id="politica-archivo"
              ref="entradaArchivo"
              type="file"
              accept=".pdf,.odt,.doc,.docx"
              :disabled="subiendo"
              class="mt-1.5 block w-full text-sm text-slate-700"
              @change="alElegirArchivo"
            >
            <p v-if="subiendo" class="mt-2 text-sm text-slate-500" role="status">Subiendo…</p>
            <p class="mt-1 text-xs text-slate-500">Máximo 20 MB.</p>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

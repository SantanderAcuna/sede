<script setup lang="ts">
/**
 * Gestión de datos institucionales de la entidad.
 *
 * Permite visualizar y editar los datos de la entidad que se muestran
 * en el sitio público (cabecera, pie de página, políticas).
 *
 * Se carga al montar el componente y permite actualizaciones parciales.
 */
import { ref, reactive, onMounted, computed } from 'vue'
import { obtener, actualizar } from '@/services/entidad'
import type { EntidadItem, EntidadInput, EntidadRed } from '@/types/api'
import { esErrorApi } from '@/services/http'
import FormField from '@/components/base/FormField.vue'
import BaseButton from '@/components/base/BaseButton.vue'

// Redes sociales disponibles
const REDES_SOCIALES = [
  { valor: 'facebook', etiqueta: 'Facebook' },
  { valor: 'instagram', etiqueta: 'Instagram' },
  { valor: 'x', etiqueta: 'X (Twitter)' },
  { valor: 'youtube', etiqueta: 'YouTube' },
  { valor: 'linkedin', etiqueta: 'LinkedIn' },
] as const

// Estado
const entidad = ref<EntidadItem | null>(null)
const cargando = ref(true)
const guardando = ref(false)
const error = ref<string | null>(null)
const mensajeExito = ref<string | null>(null)

// Formulario reactivo
const formulario = reactive<EntidadInput>({
  nombre: '',
  sigla: undefined,
  nit: undefined,
  direccion: undefined,
  municipio: undefined,
  departamento: undefined,
  pais: undefined,
  telefono: undefined,
  linea_atencion: undefined,
  linea_gratuita: undefined,
  linea_anticorrupcion: undefined,
  correo_atencion: undefined,
  correo_notificaciones_judiciales: undefined,
  horario: undefined,
  codigo_postal: undefined,
  dominio: undefined,
  logo: undefined,
  redes: [],
  politicas: [],
})

// Estado original para detectar cambios
let estadoOriginal: EntidadInput = { redes: [], politicas: [] }

/**
 * Redes sociales tipadas como EntidadRed[] para que vue-tsc resuelva
 * correctamente los iteradores del v-for sin "Cannot find name 'red'".
 */
const redesSociales = computed<EntidadRed[]>(() => formulario.redes as EntidadRed[])

// Pestañas
const pestanaActiva = ref<'basicos' | 'contacto' | 'redes' | 'politicas'>('basicos')

const pestanas = [
  { id: 'basicos', label: 'Datos básicos' },
  { id: 'contacto', label: 'Contacto' },
  { id: 'redes', label: 'Redes sociales' },
  { id: 'politicas', label: 'Políticas' },
] as const

// ¿Hay cambios sin guardar?
const hayCambios = computed(() => {
  return JSON.stringify(formulario.redes) !== JSON.stringify(estadoOriginal.redes)
    || JSON.stringify(formulario.politicas) !== JSON.stringify(estadoOriginal.politicas)
    || formulario.nombre !== estadoOriginal.nombre
    || formulario.sigla !== estadoOriginal.sigla
    || formulario.nit !== estadoOriginal.nit
    || formulario.direccion !== estadoOriginal.direccion
    || formulario.municipio !== estadoOriginal.municipio
    || formulario.departamento !== estadoOriginal.departamento
    || formulario.pais !== estadoOriginal.pais
    || formulario.telefono !== estadoOriginal.telefono
    || formulario.linea_atencion !== estadoOriginal.linea_atencion
    || formulario.linea_gratuita !== estadoOriginal.linea_gratuita
    || formulario.linea_anticorrupcion !== estadoOriginal.linea_anticorrupcion
    || formulario.correo_atencion !== estadoOriginal.correo_atencion
    || formulario.correo_notificaciones_judiciales !== estadoOriginal.correo_notificaciones_judiciales
    || formulario.horario !== estadoOriginal.horario
    || formulario.codigo_postal !== estadoOriginal.codigo_postal
    || formulario.dominio !== estadoOriginal.dominio
})

// Cargar datos
onMounted(async () => {
  try {
    entidad.value = await obtener()
    if (entidad.value) {
      // Copiar datos al formulario
      Object.assign(formulario, {
        nombre: entidad.value.nombre,
        sigla: entidad.value.sigla ?? undefined,
        nit: entidad.value.nit ?? undefined,
        direccion: entidad.value.direccion ?? undefined,
        municipio: entidad.value.municipio ?? undefined,
        departamento: entidad.value.departamento ?? undefined,
        pais: entidad.value.pais ?? undefined,
        telefono: entidad.value.telefono ?? undefined,
        linea_atencion: entidad.value.linea_atencion ?? undefined,
        linea_gratuita: entidad.value.linea_gratuita ?? undefined,
        linea_anticorrupcion: entidad.value.linea_anticorrupcion ?? undefined,
        correo_atencion: entidad.value.correo_atencion ?? undefined,
        correo_notificaciones_judiciales: entidad.value.correo_notificaciones_judiciales ?? undefined,
        horario: entidad.value.horario ?? undefined,
        codigo_postal: entidad.value.codigo_postal ?? undefined,
        dominio: entidad.value.dominio ?? undefined,
        logo: entidad.value.logo ?? undefined,
        redes: JSON.parse(JSON.stringify(entidad.value.redes ?? [])),
        politicas: JSON.parse(JSON.stringify(entidad.value.politicas ?? [])),
      })
      // Guardar estado original para comparar cambios
      estadoOriginal = JSON.parse(JSON.stringify(formulario))
    }
  } catch (e) {
    if (esErrorApi(e)) {
      error.value = e.message
    } else {
      error.value = 'No se pudo cargar los datos de la entidad.'
    }
  } finally {
    cargando.value = false
  }
})

// Guardar cambios
async function guardar() {
  guardando.value = true
  error.value = null
  mensajeExito.value = null

  try {
    const datosActualizar: Partial<EntidadInput> = {}
    // Solo incluir campos que tienen valor
    if (formulario.nombre) datosActualizar.nombre = formulario.nombre
    if (formulario.sigla !== undefined) datosActualizar.sigla = formulario.sigla
    if (formulario.nit !== undefined) datosActualizar.nit = formulario.nit
    if (formulario.direccion !== undefined) datosActualizar.direccion = formulario.direccion
    if (formulario.municipio !== undefined) datosActualizar.municipio = formulario.municipio
    if (formulario.departamento !== undefined) datosActualizar.departamento = formulario.departamento
    if (formulario.pais !== undefined) datosActualizar.pais = formulario.pais
    if (formulario.telefono !== undefined) datosActualizar.telefono = formulario.telefono
    if (formulario.linea_atencion !== undefined) datosActualizar.linea_atencion = formulario.linea_atencion
    if (formulario.linea_gratuita !== undefined) datosActualizar.linea_gratuita = formulario.linea_gratuita
    if (formulario.linea_anticorrupcion !== undefined) datosActualizar.linea_anticorrupcion = formulario.linea_anticorrupcion
    if (formulario.correo_atencion !== undefined) datosActualizar.correo_atencion = formulario.correo_atencion
    if (formulario.correo_notificaciones_judiciales !== undefined) datosActualizar.correo_notificaciones_judiciales = formulario.correo_notificaciones_judiciales
    if (formulario.horario !== undefined) datosActualizar.horario = formulario.horario
    if (formulario.codigo_postal !== undefined) datosActualizar.codigo_postal = formulario.codigo_postal
    if (formulario.dominio !== undefined) datosActualizar.dominio = formulario.dominio
    if (formulario.logo !== undefined) datosActualizar.logo = formulario.logo
    if (formulario.redes && formulario.redes.length > 0) datosActualizar.redes = formulario.redes
    if (formulario.politicas && formulario.politicas.length > 0) datosActualizar.politicas = formulario.politicas

    entidad.value = await actualizar(datosActualizar)
    mensajeExito.value = 'Cambios guardados correctamente.'
    // Actualizar estado original
    estadoOriginal = JSON.parse(JSON.stringify(formulario))
    setTimeout(() => {
      mensajeExito.value = null
    }, 3000)
  } catch (e) {
    if (esErrorApi(e)) {
      error.value = e.message
    } else {
      error.value = 'Error al guardar los cambios.'
    }
  } finally {
    guardando.value = false
  }
}

// Agregar red social
function agregarRed() {
  if (!formulario.redes) formulario.redes = []
  formulario.redes.push({ red: 'facebook', url: '' })
}

// Eliminar red social
function eliminarRed(index: number) {
  formulario.redes?.splice(index, 1)
}

// Agregar política
function agregarPolitica() {
  if (!formulario.politicas) formulario.politicas = []
  formulario.politicas.push({ slug: '', nombre: '' })
}

// Eliminar política
function eliminarPolitica(index: number) {
  formulario.politicas?.splice(index, 1)
}
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <!-- Cabecera -->
    <header class="mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Datos de la Entidad</h1>
      <p class="mt-1 text-sm text-slate-600">
        Gestiona los datos institucionales que se muestran en el sitio público.
      </p>
    </header>

    <!-- Loading -->
    <div v-if="cargando" class="space-y-4 animate-pulse">
      <div class="h-12 bg-slate-200 rounded-lg" />
      <div class="h-64 bg-slate-100 rounded-xl" />
    </div>

    <!-- Error al cargar -->
    <div
      v-else-if="error && !entidad"
      class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5"
    >
      <FaIcon icon='circle-xmark' class="h-5 w-5 text-red-500 mt-0.5 shrink-0" aria-hidden="true" />
      <div>
        <p class="font-medium text-red-800">No se pudieron cargar los datos</p>
        <p class="mt-1 text-sm text-red-600">{{ error }}</p>
      </div>
    </div>

    <!-- Formulario -->
    <div v-else-if="entidad" class="space-y-6">
      <!-- Barra de acciones -->
      <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex items-center gap-3">
          <FaIcon icon='building' class="h-5 w-5 text-slate-400" aria-hidden="true" />
          <div>
            <p class="font-medium text-slate-900">{{ entidad.nombre }}</p>
            <p class="text-sm text-slate-500">{{ entidad.sigla ?? 'Sin sigla' }} · NIT: {{ entidad.nit ?? 'No definido' }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span v-if="mensajeExito" class="text-sm text-emerald-600 flex items-center gap-1">
            <FaIcon icon='circle-check' class="h-4 w-4" aria-hidden="true" />
            {{ mensajeExito }}
          </span>
          <span v-if="hayCambios" class="text-sm text-amber-600 flex items-center gap-1">
            <FaIcon icon='circle' class="h-2 w-2" aria-hidden="true" />
            Cambios sin guardar
          </span>
          <BaseButton
            variant="primary"
            :disabled="!hayCambios || guardando"
            :loading="guardando"
            @click="guardar"
          >
            <FaIcon icon='floppy-disk' class="h-4 w-4" aria-hidden="true" />
            Guardar cambios
          </BaseButton>
        </div>
      </div>

      <!-- Error de guardado -->
      <div
        v-if="error"
        class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
      >
        <FaIcon icon='triangle-exclamation' class="h-5 w-5 text-red-500 mt-0.5 shrink-0" aria-hidden="true" />
        <div>
          <p class="font-medium text-red-800">Error al guardar</p>
          <p class="mt-1 text-sm text-red-600">{{ error }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-slate-200">
        <nav class="flex gap-4" aria-label="Pestañas">
          <button
            v-for="pestana in pestanas"
            :key="pestana.id"
            type="button"
            class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors"
            :class="pestanaActiva === pestana.id
              ? 'border-[var(--color-gov-blue)] text-[var(--color-gov-blue)]'
              : 'border-transparent text-slate-500 hover:text-slate-700'"
            @click="pestanaActiva = pestana.id"
          >
            {{ pestana.label }}
          </button>
        </nav>
      </div>

      <!-- Contenido de tabs -->
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <!-- Pestaña: Datos básicos -->
        <div v-if="pestanaActiva === 'basicos'" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <FormField
              v-model="formulario.nombre"
              label="Nombre de la entidad"
              placeholder="Alcaldía Distrital de Santa Marta"
              required
            />
            <FormField
              v-model="formulario.sigla"
              label="Sigla"
              placeholder="D.T.C.H."
            />
            <FormField
              v-model="formulario.nit"
              label="NIT"
              placeholder="891.780.009-4"
            />
            <FormField
              v-model="formulario.dominio"
              label="Dominio"
              placeholder="https://staging.santamarta.gov.co"
              hint="URL completa incluyendo https://"
            />
          </div>

          <FormField
            v-model="formulario.direccion"
            label="Dirección"
            placeholder="Calle 14 No. 2-49, Palacio Municipal"
          />

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <FormField
              v-model="formulario.municipio"
              label="Municipio"
              placeholder="Santa Marta"
            />
            <FormField
              v-model="formulario.departamento"
              label="Departamento"
              placeholder="Magdalena"
            />
            <FormField
              v-model="formulario.pais"
              label="País"
              placeholder="Colombia"
            />
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <FormField
              v-model="formulario.codigo_postal"
              label="Código postal"
              placeholder="470004"
            />
            <FormField
              v-model="formulario.horario"
              label="Horario de atención"
              placeholder="Lunes a viernes de 8:00 a 12:00 y de 14:00 a 18:00"
            />
          </div>
        </div>

        <!-- Pestaña: Contacto -->
        <div v-if="pestanaActiva === 'contacto'" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <FormField
              v-model="formulario.telefono"
              label="Teléfono principal"
              type="tel"
              placeholder="(+57) 605 420 9600"
            />
            <FormField
              v-model="formulario.linea_atencion"
              label="Línea de atención"
              type="tel"
              placeholder="(+57) 605 4351719"
            />
            <FormField
              v-model="formulario.linea_gratuita"
              label="Línea gratuita"
              placeholder="018000 955 532"
            />
            <FormField
              v-model="formulario.linea_anticorrupcion"
              label="Línea anticorrupción"
              type="tel"
              placeholder="(+57) 605 4351719"
            />
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <FormField
              v-model="formulario.correo_atencion"
              label="Correo de atención"
              type="email"
              placeholder="atencionalciudadano@santamarta.gov.co"
            />
            <FormField
              v-model="formulario.correo_notificaciones_judiciales"
              label="Correo de notificaciones judiciales"
              type="email"
              placeholder="notificacionesalcaldiadistrital@santamarta.gov.co"
            />
          </div>
        </div>

        <!-- Pestaña: Redes sociales -->
        <div v-if="pestanaActiva === 'redes'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium text-slate-900">Redes sociales</h3>
              <p class="mt-0.5 text-sm text-slate-500">Enlaces a redes sociales de la entidad.</p>
            </div>
            <BaseButton variant="secondary" size="sm" @click="agregarRed">
              <FaIcon icon='plus' class="h-4 w-4" aria-hidden="true" />
              Agregar red
            </BaseButton>
          </div>

          <div v-if="!formulario.redes?.length" class="text-center py-8 text-slate-500">
            <FaIcon icon='share-nodes' class="h-8 w-8 mx-auto mb-2 opacity-50" aria-hidden="true" />
            <p>No hay redes sociales configuradas.</p>
          </div>

          <ul v-else class="space-y-4">
            <li
              v-for="(red, index) in redesSociales"
              :key="index"
              class="flex items-start gap-4 rounded-lg border border-slate-200 p-4"
            >
              <div class="flex-1 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="block text-xs font-medium text-slate-500 mb-1">Red social</label>
                  <select
                    v-model="red.red"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                  >
                    <option v-for="opcion in REDES_SOCIALES" :key="opcion.valor" :value="opcion.valor">
                      {{ opcion.etiqueta }}
                    </option>
                  </select>
                </div>
                <FormField
                  v-model="red.url"
                  label="URL"
                  placeholder="https://..."
                  hint="URL completa de la red social"
                />
              </div>
              <button
                type="button"
                class="mt-6 text-red-500 hover:text-red-700"
                title="Eliminar"
                @click="eliminarRed(index)"
              >
                <FaIcon icon='trash' class="h-4 w-4" aria-hidden="true" />
              </button>
            </li>
          </ul>
        </div>

        <!-- Pestaña: Políticas -->
        <div v-if="pestanaActiva === 'politicas'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium text-slate-900">Políticas obligatorias</h3>
              <p class="mt-0.5 text-sm text-slate-500">Enlaces a las políticas que deben aparecer en el pie de página.</p>
            </div>
            <BaseButton variant="secondary" size="sm" @click="agregarPolitica">
              <FaIcon icon='plus' class="h-4 w-4" aria-hidden="true" />
              Agregar política
            </BaseButton>
          </div>

          <div v-if="!formulario.politicas?.length" class="text-center py-8 text-slate-500">
            <FaIcon icon='file-contract' class="h-8 w-8 mx-auto mb-2 opacity-50" aria-hidden="true" />
            <p>No hay políticas configuradas.</p>
          </div>

          <ul v-else class="space-y-4">
            <li
              v-for="(politica, index) in formulario.politicas"
              :key="index"
              class="flex items-start gap-4 rounded-lg border border-slate-200 p-4"
            >
              <div class="flex-1 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                  v-model="politica.slug"
                  label="Slug (identificador URL)"
                  placeholder="terminos-y-condiciones"
                />
                <FormField
                  v-model="politica.nombre"
                  label="Nombre de la política"
                  placeholder="Términos y Condiciones"
                />
              </div>
              <button
                type="button"
                class="mt-6 text-red-500 hover:text-red-700"
                title="Eliminar"
                @click="eliminarPolitica(index)"
              >
                <FaIcon icon='trash' class="h-4 w-4" aria-hidden="true" />
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

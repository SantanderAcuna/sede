<script setup lang="ts">
/**
 * Crear o editar un trámite, en una vista propia.
 *
 * La misma vista sirve a `tramites/nuevo` y `tramites/:uuid`. Un trámite se guarda como borrador con solo el
 * nombre; para publicarlo hace falta la ficha completa (dirección en GOV.CO, modalidad, costo, tiempo, canal,
 * consulta de estado y al menos un requisito) y la lista «Antes de publicar» dice qué falta mientras se escribe.
 *
 * Los trámites del catálogo oficial también se editan aquí: al guardar quedan marcados como del panel y la carga
 * automática deja de sobrescribirlos. Solo viaja lo que cambió, así lo que esta pantalla no muestra se conserva.
 */
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import ConfirmModal from '@/components/base/ConfirmModal.vue'
import FormField from '@/components/base/FormField.vue'
import ListaEditable from '@/components/base/ListaEditable.vue'
import SelectField from '@/components/base/SelectField.vue'
import { actualizar, crear, dependencias as cargarDependencias, eliminar, obtener, type DependenciaOpcion } from '@/services/tramites'
import type { TramitePanelItem } from '@/types/api'
import { dejarAviso } from './avisos'
import { motivosDeError } from './politicas-logica'
import {
  CAMPOS_CANAL_CONSULTA,
  CAMPOS_CUENTA,
  CAMPOS_DOCUMENTO,
  CAMPOS_NORMA,
  CAMPOS_PASO,
  CAMPOS_PUNTO,
  CAMPOS_REQUISITO,
  CAMPOS_TEXTO,
  CANALES_INICIO,
  COSTOS,
  FECHA_CUALQUIERA,
  MODALIDADES,
  NUEVO_REQUISITO,
  TIPOS,
  TIPOS_VALOR,
} from './tramites-campos'
import {
  entradaDesde,
  faltaParaPublicar,
  formularioDesde,
  formularioVacio,
  hayCambios as hayCambiosEn,
  motivosLegibles,
  problemasLocales,
  SOLICITANTES,
  type FormularioTramite,
} from './tramites-logica'
import { usePermisosDeModulo } from '@/composables/usePermisosDeModulo'

const ruta = useRoute()
const enrutador = useRouter()

/** `null` mientras se está creando. */
const uuid = ref<string | null>(typeof ruta.params.uuid === 'string' ? ruta.params.uuid : null)
const tramite = ref<TramitePanelItem | null>(null)
const formulario = ref<FormularioTramite>(formularioVacio())
let original: FormularioTramite = formularioVacio()

const cargando = ref(uuid.value !== null)
const errorCarga = ref<'no-existe' | 'fallo' | null>(null)
const guardando = ref(false)
const motivos = ref<string[]>([])
const erroresServidor = ref<Record<string, string | undefined>>({})
const exito = ref<string | null>(null)
const resumen = ref<HTMLElement | null>(null)
const confirmandoEliminar = ref(false)
const eliminando = ref(false)
const errorEliminar = ref<string | null>(null)
/** Tras guardar o eliminar se sale sin preguntar por cambios. */
let saliendo = false
/** El resumen de errores salió de las comprobaciones locales (no del servidor). */
let resumenLocal = false

const esNuevo = computed(() => uuid.value === null)
const { puedeEditar, puedePublicar, puedeEliminar } = usePermisosDeModulo('tramites')
/** Solo puede consultar: tiene «ver» pero no «editar». Al crear uno nuevo se exige «crear» en la ruta. */
const soloLectura = computed(() => !esNuevo.value && !puedeEditar.value)
const hayCambios = computed(() => hayCambiosEn(formulario.value, original))
const faltantes = computed(() => faltaParaPublicar(formulario.value))
const conCosto = computed(() => formulario.value.tiene_costo === 'con_costo')

/** Las dependencias para el selector; se cargan una vez al montar. */
const dependencias = ref<DependenciaOpcion[]>([])
const opcionesDependencia = computed(() =>
  dependencias.value.map((d) => ({ valor: String(d.id), etiqueta: d.nombre })),
)

/** Alterna un grupo de solicitante (checkbox) en el modelo. */
function alternarSolicitante(valor: string, marcado: boolean): void {
  const actuales = formulario.value.solicitantes.filter((s) => s !== valor)
  formulario.value.solicitantes = marcado ? [...actuales, valor] : actuales
}

const SECCIONES = [
  ['t-datos', 'Datos principales'],
  ['t-como', 'Cómo y cuándo se hace'],
  ['t-costo', 'Costo'],
  ['t-requisitos', 'Requisitos'],
  ['t-pasos', 'Pasos'],
  ['t-documentos', 'Documentos'],
  ['t-resultado', 'Resultado'],
  ['t-solicitantes', 'Para quién es'],
  ['t-puntos', 'Dónde se atiende'],
  ['t-normativa', 'Normativa'],
  ['t-seguimiento', 'Consulta del estado'],
  ['t-publicacion', 'Publicación'],
] as const

const publicar = computed({
  get: () => (formulario.value.publicado ? 'publicar' : 'borrador'),
  set: (valor: string) => {
    formulario.value.publicado = valor === 'publicar'
  },
})

/** Aviso junto a cada campo: lo que dijo el servidor. */
const errorDe = (campo: string): string | undefined => erroresServidor.value[campo]

function aplicar(guardado: TramitePanelItem): void {
  tramite.value = guardado
  uuid.value = guardado.uuid ?? null
  formulario.value = formularioDesde(guardado)
  original = formularioDesde(guardado)
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

/** El servidor responde `errors: { ruta: [mensaje] }`; se queda con el primero de cada ruta. */
function erroresPorRuta(e: unknown): Record<string, string | undefined> {
  if (!axios.isAxiosError(e)) return {}
  const errores = (e.response?.data as { errors?: Record<string, unknown> } | undefined)?.errors
  if (errores === null || typeof errores !== 'object') return {}
  const resultado: Record<string, string | undefined> = {}
  for (const [ruta, mensajes] of Object.entries(errores)) {
    const primero = Array.isArray(mensajes) ? mensajes.find((m): m is string => typeof m === 'string') : undefined
    if (primero) resultado[ruta] = primero
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

  if (!esNuevo.value && !hayCambios.value) {
    motivos.value = []
    exito.value = 'No hay cambios que guardar.'
    return
  }

  const locales = problemasLocales(formulario.value)
  if (formulario.value.publicado && faltantes.value.length > 0) {
    locales.push('Para publicar el trámite falta:', ...faltantes.value)
  }
  if (locales.length > 0) {
    resumenLocal = true
    motivos.value = locales
    await mostrarResumen()
    return
  }
  motivos.value = []
  resumenLocal = false

  guardando.value = true
  const eraNuevo = esNuevo.value
  try {
    const entrada = entradaDesde(formulario.value, eraNuevo ? null : original)
    const guardado = eraNuevo ? await crear(entrada) : await actualizar(uuid.value as string, entrada)
    aplicar(guardado)
    exito.value = eraNuevo
      ? guardado.estado === 'publicado'
        ? 'Trámite guardado y publicado en el sitio.'
        : 'Trámite guardado como borrador. Todavía no se ve en el sitio.'
      : guardado.estado === 'publicado'
        ? 'Cambios guardados. El trámite está publicado.'
        : 'Cambios guardados. El trámite sigue como borrador.'
    if (eraNuevo) await enrutador.replace({ name: 'panel.tramites.editar', params: { uuid: guardado.uuid } })
  } catch (e) {
    erroresServidor.value = erroresPorRuta(e)
    const errores = axios.isAxiosError(e) ? (e.response?.data as { errors?: Record<string, unknown> } | undefined)?.errors : undefined
    const legibles = errores !== null && typeof errores === 'object' ? motivosLegibles(errores) : []
    motivos.value = legibles.length > 0 ? legibles : motivosDeError(e, 'No se pudo guardar el trámite.')
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
    dejarAviso('Trámite eliminado.')
    await enrutador.push({ name: 'tramites' })
  } catch (e) {
    errorEliminar.value = motivosDeError(e, 'No se pudo eliminar el trámite. Inténtelo de nuevo.')[0] ?? null
  } finally {
    eliminando.value = false
  }
}

// Quien corrige lo que faltaba no debe seguir viendo «No se guardó» con el problema ya resuelto.
watch(
  () => [problemasLocales(formulario.value).length, formulario.value.publicado ? faltantes.value.length : 0],
  ([locales, faltan]) => {
    if (resumenLocal && locales === 0 && faltan === 0) motivos.value = []
  },
)

// --- No perder lo escrito ------------------------------------------------------
let quitarGuardia: (() => void) | undefined

function avisarAlCerrarPestana(evento: BeforeUnloadEvent): void {
  if (!hayCambios.value || saliendo) return
  evento.preventDefault()
  evento.returnValue = ''
}

onMounted(() => {
  void cargar()
  void cargarDependencias().then((lista) => { dependencias.value = lista }).catch(() => { dependencias.value = [] })
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
    if (destino === null || destino === tramite.value?.uuid) return
    uuid.value = destino
    motivos.value = []
    exito.value = null
    void cargar()
  },
)

const tarjeta = 'space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6 scroll-mt-4'
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6 pb-28">
    <div>
      <RouterLink
        :to="{ name: 'tramites' }"
        class="inline-flex items-center gap-1 text-sm font-medium text-gov-blue hover:underline"
      >
        <FaIcon icon="chevron-left" class="h-3 w-3" aria-hidden="true" />
        Volver a trámites
      </RouterLink>
      <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold text-slate-900">{{ esNuevo ? 'Nuevo trámite' : 'Editar trámite' }}</h1>
        <BaseBadge v-if="tramite" :variant="tramite.estado === 'publicado' ? 'success' : 'warning'" dot>
          {{ tramite.estado === 'publicado' ? 'Publicado' : 'Borrador' }}
        </BaseBadge>
        <span v-if="tramite" class="font-mono text-xs text-slate-600">{{ tramite.codigo }}</span>
        <a
          v-if="tramite && tramite.estado === 'publicado'"
          :href="`/tramites/${tramite.slug}`"
          target="_blank"
          rel="noopener"
          class="text-sm text-gov-blue underline"
        >Ver en el sitio</a>
      </div>
      <p class="mt-1 text-sm text-slate-600">
        {{ esNuevo
          ? 'Escriba el nombre y lo que ya sepa. Se guarda como borrador y no se verá en el sitio hasta que usted decida publicarlo.'
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

    <p v-if="cargando" class="rounded-xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200">Cargando el trámite…</p>

    <div v-else-if="errorCarga" class="rounded-xl border border-red-200 bg-red-50 p-5" role="alert">
      <template v-if="errorCarga === 'no-existe'">
        <p class="font-medium text-red-800">No se encontró el trámite</p>
        <p class="mt-1 text-sm text-red-700">Puede que se haya eliminado o que la dirección esté incompleta.</p>
        <RouterLink :to="{ name: 'tramites' }" class="mt-3 inline-block text-sm font-medium text-gov-blue underline">
          Volver a trámites
        </RouterLink>
      </template>
      <template v-else>
        <p class="font-medium text-red-800">No se pudo cargar el trámite</p>
        <p class="mt-1 text-sm text-red-700">Revise su conexión e inténtelo de nuevo.</p>
        <BaseButton class="mt-3" variant="outline" size="sm" @click="cargar">Reintentar</BaseButton>
      </template>
    </div>

    <form v-else class="space-y-6" novalidate @submit.prevent="guardar">
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
          <li v-for="(m, i) in motivos" :key="i">{{ m }}</li>
        </ul>
      </div>

      <nav aria-label="Secciones del formulario" class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
        <p class="text-sm font-medium text-slate-700">Ir a una sección</p>
        <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1.5">
          <li v-for="[id, nombre] in SECCIONES" :key="id">
            <a :href="`#${id}`" class="text-sm text-gov-blue underline">{{ nombre }}</a>
          </li>
        </ul>
      </nav>

      <section id="t-datos" :class="tarjeta" aria-labelledby="t-datos-h">
        <h2 id="t-datos-h" class="font-medium text-slate-900">Datos principales</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <SelectField
            v-model="formulario.type"
            label="Qué es"
            :opciones="TIPOS"
            :error="errorDe('type')"
            :disabled="!esNuevo"
            :hint="esNuevo ? 'Un trámite, una OPA o una consulta de acceso a información pública.' : 'No se cambia después de crear (de él depende el código).'"
          />
          <SelectField
            v-model="formulario.dependencia_id"
            label="Dependencia responsable"
            :opciones="opcionesDependencia"
            vacio="Sin asignar"
            :error="errorDe('dependencia_id')"
          />
        </div>
        <FormField v-model="formulario.nombre" label="Nombre" required :error="errorDe('nombre')" hint="Como lo busca la ciudadanía. Máximo 200 letras." />
        <FormField v-model="formulario.resumen" type="textarea" :rows="3" label="Resumen" :error="errorDe('resumen')" hint="Una o dos frases que expliquen para qué sirve. Máximo 500 letras. Obligatorio para publicar." />
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField v-model="formulario.categoria_nombre" label="Tema (nombre)" :error="errorDe('categoria.nombre')" hint="Por ejemplo: Hacienda e impuestos." />
          <FormField v-model="formulario.categoria_slug" label="Tema (identificador)" :error="errorDe('categoria.slug')" hint="Solo minúsculas, números y guiones. Por ejemplo: hacienda-e-impuestos." />
        </div>
        <FormField v-model="formulario.palabras_relacionadas" label="Palabras relacionadas" :error="errorDe('palabras_relacionadas')" hint="Separadas por comas. Ayudan a encontrar el trámite en el buscador." />
      </section>

      <section id="t-como" :class="tarjeta" aria-labelledby="t-como-h">
        <h2 id="t-como-h" class="font-medium text-slate-900">Cómo y cuándo se hace</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <SelectField v-model="formulario.modalidad" label="Modalidad" :opciones="MODALIDADES" :error="errorDe('modalidad')" />
          <SelectField v-model="formulario.canal_inicio" label="Dónde se inicia" :opciones="CANALES_INICIO" :error="errorDe('canal_inicio')" />
          <FormField v-model="formulario.tiempo_solucion_dias" type="number" label="Tiempo de solución (días)" :error="errorDe('tiempo_solucion_dias')" hint="Días hábiles que tarda la respuesta. Escriba 0 si es inmediata." />
          <SelectField v-model="formulario.fecha_cualquiera" label="¿Se puede hacer en cualquier fecha?" :opciones="FECHA_CUALQUIERA" vacio="Sin definir" :error="errorDe('fecha_cualquiera')" />
        </div>
        <FormField v-model="formulario.url_ficha_gov_co" type="text" label="Dirección de la ficha en GOV.CO" :error="errorDe('url_ficha_gov_co')" hint="Debe empezar por https://. Es obligatoria para publicar." />
        <FormField v-model="formulario.url_inicio" label="Dirección donde se inicia el trámite" :error="errorDe('url_inicio')" hint="Si se inicia en línea, el enlace directo." />
        <FormField v-model="formulario.url_manual_tramite_en_linea" label="Dirección del manual del trámite en línea" :error="errorDe('url_manual_tramite_en_linea')" />
        <FormField v-model="formulario.cuando_se_puede_realizar" type="textarea" :rows="2" label="Cuándo se puede realizar" :error="errorDe('cuando_se_puede_realizar')" hint="Solo si no es en cualquier fecha: la condición o el periodo." />
        <FormField v-model="formulario.url_calendario" label="Dirección del calendario" :error="errorDe('url_calendario')" />
        <FormField v-model="formulario.consulta_estado" type="textarea" :rows="2" label="Cómo se consulta el estado del trámite" :error="errorDe('consulta_estado')" hint="Por ejemplo: «Llamando a la oficina» o «En la Sede Electrónica con su número de radicado». Es obligatorio para publicar." />
      </section>

      <section id="t-costo" :class="tarjeta" aria-labelledby="t-costo-h">
        <h2 id="t-costo-h" class="font-medium text-slate-900">Costo</h2>
        <SelectField v-model="formulario.tiene_costo" label="¿Tiene costo?" :opciones="COSTOS" :error="errorDe('tiene_costo')" />
        <template v-if="conCosto">
          <div class="grid gap-4 sm:grid-cols-2">
            <SelectField v-model="formulario.costo_tipo_valor" label="Cómo se calcula" :opciones="TIPOS_VALOR" :error="errorDe('costo.tipo_valor')" />
            <FormField v-model="formulario.costo_valor" label="Valor" :error="errorDe('costo.valor')" hint="Solo números, sin puntos ni signos. Déjelo vacío si depende del avalúo." />
            <FormField v-model="formulario.costo_moneda" label="Moneda" :error="errorDe('costo.moneda')" hint="Por ejemplo: COP." />
            <FormField v-model="formulario.costo_url_pago" label="Dirección donde se paga" :error="errorDe('costo.url_pago')" />
          </div>
          <FormField v-model="formulario.costo_descripcion" type="textarea" :rows="2" label="Explicación del costo" :error="errorDe('costo.descripcion')" />
          <div>
            <h3 class="text-sm font-medium text-slate-700">Cuentas donde se paga</h3>
            <div class="mt-2">
              <ListaEditable
                v-model="formulario.costo_cuentas"
                :campos="CAMPOS_CUENTA"
                etiqueta-item="Cuenta"
                prefijo-error="costo.cuentas"
                :errores="erroresServidor"
                plural="cuentas"
              />
            </div>
          </div>
        </template>
      </section>

      <section id="t-requisitos" :class="tarjeta" aria-labelledby="t-requisitos-h">
        <h2 id="t-requisitos-h" class="font-medium text-slate-900">Requisitos</h2>
        <p class="text-sm text-slate-600">Todo lo que la persona debe presentar o cumplir. Para publicar se necesita al menos uno.</p>
        <ListaEditable
          v-model="formulario.requisitos"
          :campos="CAMPOS_REQUISITO"
          etiqueta-item="Requisito"
          prefijo-error="requisitos"
          :errores="erroresServidor"
          :nuevo="NUEVO_REQUISITO"
          campo-titulo="descripcion"
          plural="requisitos"
          texto-vacio="Aún no hay requisitos."
        />
      </section>

      <section id="t-pasos" :class="tarjeta" aria-labelledby="t-pasos-h">
        <h2 id="t-pasos-h" class="font-medium text-slate-900">Pasos del trámite</h2>
        <p class="text-sm text-slate-600">
          Opcional. Los pasos en el orden en que se hacen. Para ligar un requisito a un paso, escriba el número del
          paso en el campo «Paso al que pertenece» del requisito.
        </p>
        <ListaEditable
          v-model="formulario.pasos"
          :campos="CAMPOS_PASO"
          etiqueta-item="Paso"
          prefijo-error="pasos"
          :errores="erroresServidor"
          campo-titulo="titulo"
          :nuevo="() => ({ orden: formulario.pasos.length + 1 })"
          plural="pasos"
        />
      </section>

      <section id="t-documentos" :class="tarjeta" aria-labelledby="t-documentos-h">
        <h2 id="t-documentos-h" class="font-medium text-slate-900">Documentos para descargar</h2>
        <ListaEditable
          v-model="formulario.documentos"
          :campos="CAMPOS_DOCUMENTO"
          etiqueta-item="Documento"
          prefijo-error="documentos"
          :errores="erroresServidor"
          campo-titulo="nombre"
          plural="documentos"
        />
      </section>

      <section id="t-resultado" :class="tarjeta" aria-labelledby="t-resultado-h">
        <h2 id="t-resultado-h" class="font-medium text-slate-900">Resultado</h2>
        <FormField v-model="formulario.resultado" type="textarea" :rows="2" label="Qué recibe la persona" :error="errorDe('resultado')" hint="Por ejemplo: «Certificado de paz y salvo»." />
        <FormField v-model="formulario.observaciones_resultado" type="textarea" :rows="2" label="Aclaración sobre el resultado" :error="errorDe('observaciones_resultado')" hint="De qué depende el plazo, si aplica." />
        <div>
          <h3 class="text-sm font-medium text-slate-700">Por qué medios se entrega</h3>
          <div class="mt-2">
            <ListaEditable v-model="formulario.medios_resultado" :campos="CAMPOS_TEXTO" etiqueta-item="Medio" prefijo-error="medios_resultado" :errores="erroresServidor" campo-titulo="valor" plural="medios" />
          </div>
        </div>
      </section>

      <section id="t-solicitantes" :class="tarjeta" aria-labelledby="t-solicitantes-h">
        <h2 id="t-solicitantes-h" class="font-medium text-slate-900">Para quién es</h2>
        <fieldset>
          <legend class="text-sm text-slate-600">Marque los tipos de solicitante a los que aplica. Es una de las facetas por las que se filtra en el sitio.</legend>
          <div class="mt-2 grid gap-2 sm:grid-cols-2">
            <label
              v-for="s in SOLICITANTES"
              :key="s.valor"
              class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light"
            >
              <input
                type="checkbox"
                class="h-4 w-4"
                :checked="formulario.solicitantes.includes(s.valor)"
                @change="alternarSolicitante(s.valor, ($event.target as HTMLInputElement).checked)"
              >
              <span class="text-sm text-slate-800">{{ s.etiqueta }}</span>
            </label>
          </div>
        </fieldset>
      </section>

      <section id="t-puntos" :class="tarjeta" aria-labelledby="t-puntos-h">
        <h2 id="t-puntos-h" class="font-medium text-slate-900">Dónde se atiende</h2>
        <ListaEditable v-model="formulario.puntos_atencion" :campos="CAMPOS_PUNTO" etiqueta-item="Lugar" prefijo-error="puntos_atencion" :errores="erroresServidor" campo-titulo="nombre" plural="lugares" :max="500" />
      </section>

      <section id="t-normativa" :class="tarjeta" aria-labelledby="t-normativa-h">
        <h2 id="t-normativa-h" class="font-medium text-slate-900">Normativa</h2>
        <ListaEditable v-model="formulario.normativa" :campos="CAMPOS_NORMA" etiqueta-item="Norma" prefijo-error="normativa" :errores="erroresServidor" campo-titulo="tipo" plural="normas" />
      </section>

      <section id="t-seguimiento" :class="tarjeta" aria-labelledby="t-seguimiento-h">
        <h2 id="t-seguimiento-h" class="font-medium text-slate-900">Consulta del estado</h2>
        <p class="text-sm text-slate-600">Los canales por los que la persona puede consultar en qué va su solicitud.</p>
        <ListaEditable v-model="formulario.canales_consulta_estado" :campos="CAMPOS_CANAL_CONSULTA" etiqueta-item="Canal" prefijo-error="canales_consulta_estado" :errores="erroresServidor" campo-titulo="nombre" plural="canales" />
      </section>

      <section id="t-publicacion" :class="tarjeta" aria-labelledby="t-publicacion-h">
        <h2 id="t-publicacion-h" class="font-medium text-slate-900">Publicación</h2>
        <div class="rounded-lg border border-slate-200 p-4" data-checklist>
          <p class="text-sm font-medium text-slate-900">Antes de publicar</p>
          <p v-if="faltantes.length === 0" class="mt-2 flex items-center gap-2 text-sm text-emerald-700">
            <FaIcon icon="circle-check" class="h-4 w-4" aria-hidden="true" />
            <span><strong>Listo:</strong> el trámite tiene todo lo necesario para publicarse.</span>
          </p>
          <ul v-else class="mt-2 space-y-1.5">
            <li v-for="f in faltantes" :key="f" class="flex items-start gap-2 text-sm text-amber-800">
              <FaIcon icon="circle-xmark" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" aria-hidden="true" />
              <span><strong>Falta:</strong> {{ f.replace(/^Falta /, '').replace(/\.$/, '') }}</span>
            </li>
          </ul>
        </div>
        <fieldset>
          <legend class="text-sm font-medium text-slate-700">¿Qué quiere hacer con este trámite?</legend>
          <p v-if="!puedePublicar" class="mt-1 text-xs text-amber-800" data-sin-publicar>Su rol no puede publicar ni retirar: el contenido sigue en el estado en que está.</p>
          <div class="mt-2 space-y-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light">
              <input v-model="publicar" type="radio" :disabled="!puedePublicar" name="estado-tramite" value="borrador" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>Guardar como borrador.</strong> No se ve en el sitio; si ya estaba publicado, se retira.</span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-gov-blue has-[:checked]:bg-gov-blue-light">
              <input v-model="publicar" type="radio" :disabled="!puedePublicar" name="estado-tramite" value="publicar" class="mt-1 h-4 w-4">
              <span class="text-sm text-slate-800"><strong>Publicar.</strong> Se ve en el sitio con su ficha. Necesita todo lo de la lista de arriba.</span>
            </label>
          </div>
        </fieldset>
      </section>

      </fieldset>

      <section v-if="!esNuevo && tramite && puedeEliminar" class="rounded-xl border border-red-200 bg-white p-5 sm:p-6" aria-labelledby="t-peligro-h">
        <h2 id="t-peligro-h" class="font-medium text-red-800">Zona de peligro</h2>
        <p class="mt-1 text-sm text-slate-600">Eliminar quita el trámite del sitio y de este listado. No se puede deshacer desde el panel.</p>
        <BaseButton class="mt-3" variant="danger" @click="confirmandoEliminar = true">Eliminar trámite</BaseButton>
      </section>

      <div v-if="!soloLectura" class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:sticky lg:inset-x-auto lg:rounded-xl lg:border">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-end gap-3">
          <span v-if="hayCambios" class="mr-auto text-sm font-medium text-amber-700">Hay cambios sin guardar</span>
          <RouterLink :to="{ name: 'tramites' }" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
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
      title="¿Eliminar este trámite?"
      confirmar="Sí, eliminar"
      :cargando="eliminando"
      :error="errorEliminar"
      @confirmar="confirmarEliminar"
    >
      <p>
        Se eliminará <strong>«{{ tramite?.nombre }}»</strong> y dejará de verse en el sitio. Esta acción no se puede
        deshacer desde el panel.
      </p>
    </ConfirmModal>
  </div>
</template>

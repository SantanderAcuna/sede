<script setup lang="ts">
/**
 * Trámites y servicios — directorio del catálogo de la Sede.
 *
 * **Qué exige esta página.** El Anexo 2.1 (Guía de diseño gráfico para sedes electrónicas, de
 * obligatorio cumplimiento por el artículo 14 del Decreto 2106 de 2019) pide visualizar los
 * Trámites, OPA y Consultas de acceso a información pública con mecanismos de acceso —filtros y
 * buscador—, el número de resultados siempre a la vista, al menos un resultado visible sin hacer
 * scroll, y paginación. El RF-B1-022 añade filtros por nombre, modalidad, costo y tipo de
 * solicitante.
 *
 * **Presentación de directorio (modelo: el listado de trámites de la Alcaldía de Montería).** Cada
 * trámite es una fila a todo el ancho: a la izquierda su nombre y un resumen; a la derecha, en
 * columna y con icono y etiqueta, la modalidad, si tiene costo, la duración y la dependencia —los
 * metadatos que el ciudadano necesita para elegir—. Al lado izquierdo, en un panel propio, el
 * buscador y los filtros agrupados en acordeones.
 *
 * **Adónde lleva cada fila.** La ficha completa vive en GOV.CO (registro nacional de trámites): el
 * título abre su `url_ficha_gov_co` en una pestaña nueva (`enlaceTramite`). Además cada fila ofrece un
 * enlace «Detalle» a la ficha propia de la Sede (pasos, requisitos y valoración con estrellas), de modo
 * que las dos fichas son alcanzables. Un trámite sin ficha nacional, como las consultas, lleva con su
 * título a la ficha propia y no repite el «Detalle».
 *
 * **Los filtros y la búsqueda son del servidor, y el contrato lo dice.** `GET /tramites` declara
 * `page`, `per_page`, `buscar`, `type`, `categoria`, `modalidad`, `costo`, `dependencia` y
 * `solicitante`, y devuelve en `meta.facetas` el recuento de cada faceta del grupo. La página pide
 * una página cada vez y pinta las facetas con lo que el servidor cuenta: no se inventa ninguna.
 *
 * **Los tres estados se distinguen, y ninguno miente.** Si la API no responde, se dice y se ofrece
 * reintentar; no se finge un vacío. Que la Entidad no tenga nada publicado en el grupo es un hecho
 * suyo; que una búsqueda no encuentre nada es un hecho del filtro. El amarillo institucional se
 * reserva para el primero.
 */
import type { components } from '~~/types/openapi'
import { enlaceTramite } from '~/utils/enlace-tramite'

useHead({
  title: 'Trámites y servicios · Sede Electrónica',
  meta: [
    {
      name: 'description',
      content:
        'Trámites, otros procedimientos administrativos (OPA) y consultas de acceso a información pública de la Alcaldía Distrital de Santa Marta, con la ficha de cada uno.',
    },
  ],
})

// ---------------------------------------------------------------------------
// Modelo del catálogo
// ---------------------------------------------------------------------------

type GrupoCatalogo = 'tramites' | 'opa' | 'consultas'

type TramiteApi = components['schemas']['TramiteItem']
type FacetasApi = components['schemas']['TramiteFacetas']

interface RespuestaCatalogo {
  data: TramiteApi[]
  meta: components['schemas']['PageMeta']
}

interface Grupo {
  nombre: string
  nombreCorto: string
  descripcion: string
}

/** Los tres grupos del Anexo 2.1, en su orden (no el alfabético). */
const GRUPOS: Record<GrupoCatalogo, Grupo> = {
  tramites: {
    nombre: 'Trámites',
    nombreCorto: 'Trámites',
    descripcion: 'Trámites y servicios que la Entidad tiene disponibles.',
  },
  opa: {
    nombre: 'Otros Procedimientos Administrativos (OPA)',
    nombreCorto: 'OPA',
    descripcion: 'Otros procedimientos administrativos que la Entidad tiene disponibles.',
  },
  consultas: {
    nombre: 'Consultas de acceso a información pública',
    nombreCorto: 'Consultas',
    descripcion: 'Consultas de acceso a información pública que la Entidad tiene disponibles.',
  },
}

const ORDEN_GRUPOS: readonly GrupoCatalogo[] = ['tramites', 'opa', 'consultas']

/** Etiquetas legibles de los valores de las facetas, para no mostrar el código crudo del dato. */
const ETIQUETA_MODALIDAD: Record<string, string> = {
  en_linea: 'En línea',
  parcialmente_en_linea: 'Parcialmente en línea',
  presencial: 'Presencial',
}
const ETIQUETA_COSTO: Record<string, string> = {
  gratuito: 'Gratuito',
  con_costo: 'Con costo',
}
const ETIQUETA_SOLICITANTE: Record<string, string> = {
  ciudadano: 'Ciudadano',
  extranjeros: 'Extranjeros',
  organizaciones: 'Organizaciones',
  instituciones: 'Instituciones',
}

/** Diez por página, como el listado de referencia. */
const TAMANO_PAGINA = 10

// ---------------------------------------------------------------------------
// Estado de los mecanismos de acceso
// ---------------------------------------------------------------------------

const grupoActivo = ref<GrupoCatalogo>('tramites')
const terminoAplicado = ref('')
const categoriaElegida = ref('')
const modalidadElegida = ref('')
const costoElegido = ref('')
const dependenciaElegida = ref<number | null>(null)
const solicitanteElegido = ref('')
const pagina = ref(1)
const claveBuscador = ref(0)
/** En móvil el panel de filtros se pliega para no empujar el primer resultado fuera de pantalla. */
const filtrosAbiertos = ref(false)
/** Qué grupos de filtros están desplegados. «Modalidad» arranca abierto: es el primer criterio con que se elige. */
const gruposAbiertos = reactive({
  modalidad: true,
  costo: false,
  categoria: false,
  dependencia: false,
  solicitante: false,
})

// ---------------------------------------------------------------------------
// Lectura del catálogo
// ---------------------------------------------------------------------------

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()

const { data, status, refresh } = await useAsyncData<RespuestaCatalogo>(
  'tramites-catalogo',
  async () => {
    const consulta: Record<string, string | number> = {
      page: pagina.value,
      per_page: TAMANO_PAGINA,
      type: grupoActivo.value,
    }
    if (terminoAplicado.value !== '') consulta.buscar = terminoAplicado.value
    if (categoriaElegida.value !== '') consulta.categoria = categoriaElegida.value
    if (modalidadElegida.value !== '') consulta.modalidad = modalidadElegida.value
    if (costoElegido.value !== '') consulta.costo = costoElegido.value
    if (dependenciaElegida.value !== null) consulta.dependencia = dependenciaElegida.value
    if (solicitanteElegido.value !== '') consulta.solicitante = solicitanteElegido.value

    return await traer<RespuestaCatalogo>(`${urlApi}/tramites`, { query: consulta })
  },
  {
    watch: [
      pagina,
      terminoAplicado,
      categoriaElegida,
      modalidadElegida,
      costoElegido,
      dependenciaElegida,
      solicitanteElegido,
      grupoActivo,
    ],
  },
)

// ---------------------------------------------------------------------------
// Derivados
// ---------------------------------------------------------------------------

const grupo = computed<Grupo>(() => GRUPOS[grupoActivo.value])
const elementos = computed<TramiteApi[]>(() => data.value?.data ?? [])
const facetas = computed<FacetasApi | undefined>(() => data.value?.meta.facetas)
const totalResultados = computed<number>(() => data.value?.meta.total ?? 0)
const totalPaginas = computed<number>(() => Math.max(1, data.value?.meta.last_page ?? 1))
const paginaActual = computed<number>(() => Math.min(Math.max(pagina.value, 1), totalPaginas.value))
const cargando = computed<boolean>(() => status.value === 'pending' || status.value === 'idle')
const fallo = computed<boolean>(() => status.value === 'error')
const sinPaginasQueOfrecer = computed<boolean>(() => cargando.value || fallo.value)

const hayFiltros = computed(
  () =>
    terminoAplicado.value !== '' ||
    categoriaElegida.value !== '' ||
    modalidadElegida.value !== '' ||
    costoElegido.value !== '' ||
    dependenciaElegida.value !== null ||
    solicitanteElegido.value !== '',
)

const resumenResultados = computed<string>(() => {
  if (cargando.value) return 'Cargando el catálogo…'
  if (fallo.value) return 'No se pudo cargar el catálogo de trámites.'

  const total = totalResultados.value
  const cuenta = `${total} ${total === 1 ? 'resultado' : 'resultados'}`
  if (total <= 1) return `${cuenta} en ${grupo.value.nombre}.`

  const desde = data.value?.meta.from ?? 1
  const hasta = data.value?.meta.to ?? total
  return `Mostrando ${desde} a ${hasta} de ${cuenta} en ${grupo.value.nombre}.`
})

interface EstadoVacio {
  titulo: string
  detalle: string
  esPublicacion: boolean
}

const estadoVacio = computed<EstadoVacio>(() => {
  if (!hayFiltros.value && totalResultados.value === 0) {
    return {
      titulo: 'Todavía no hay nada publicado en esta sección.',
      detalle:
        'Esta sección publica lo que la Entidad tiene disponible, con la ficha de cada uno: qué es, quién puede solicitarlo, requisitos, costo, tiempo de respuesta y el enlace para iniciarlo. Mientras no haya nada publicado aquí, la sede no muestra ningún trámite de ejemplo: uno inventado daría por ciertos unos requisitos, un costo y un plazo que nadie ha aprobado.',
      esPublicacion: true,
    }
  }
  return {
    titulo: 'No hay resultados para esta búsqueda.',
    detalle: 'Pruebe con otras palabras, quite algún filtro o cámbielo.',
    esPublicacion: false,
  }
})

// ---------------------------------------------------------------------------
// Presentación de cada fila
// ---------------------------------------------------------------------------

function textoModalidad(valor: string | null | undefined): string | null {
  return valor == null ? null : (ETIQUETA_MODALIDAD[valor] ?? valor)
}

/** El icono de la modalidad: una persona para lo presencial, una pantalla para lo que se hace en línea. */
function iconoModalidad(valor: string | null | undefined): 'persona' | 'pantalla' {
  return valor === 'presencial' ? 'persona' : 'pantalla'
}

function textoCosto(valor: string | null | undefined): string | null {
  return valor == null ? null : (ETIQUETA_COSTO[valor] ?? valor)
}

function textoDuracion(dias: number | null | undefined): string | null {
  if (dias == null) return null
  if (dias === 0) return 'Inmediato'
  return `${dias} día${dias === 1 ? '' : 's'} hábil${dias === 1 ? '' : 'es'}`
}

// ---------------------------------------------------------------------------
// Acciones
// ---------------------------------------------------------------------------

function alBuscar(termino: string): void {
  terminoAplicado.value = termino
  pagina.value = 1
}

function alLimpiarBusqueda(): void {
  terminoAplicado.value = ''
  pagina.value = 1
}

function alElegirGrupo(id: GrupoCatalogo): void {
  if (grupoActivo.value === id) return
  grupoActivo.value = id
  // Las facetas salen del grupo: lo elegido puede no existir en el nuevo.
  categoriaElegida.value = ''
  modalidadElegida.value = ''
  costoElegido.value = ''
  dependenciaElegida.value = null
  solicitanteElegido.value = ''
  pagina.value = 1
}

/** Activa o desactiva un valor de faceta de texto (un segundo clic lo quita). */
function alternar(campo: 'modalidad' | 'costo' | 'categoria' | 'solicitante', valor: string): void {
  const refs = {
    modalidad: modalidadElegida,
    costo: costoElegido,
    categoria: categoriaElegida,
    solicitante: solicitanteElegido,
  }
  const r = refs[campo]
  r.value = r.value === valor ? '' : valor
  pagina.value = 1
}

function alternarDependencia(id: number): void {
  dependenciaElegida.value = dependenciaElegida.value === id ? null : id
  pagina.value = 1
}

function limpiarFiltros(): void {
  terminoAplicado.value = ''
  categoriaElegida.value = ''
  modalidadElegida.value = ''
  costoElegido.value = ''
  dependenciaElegida.value = null
  solicitanteElegido.value = ''
  pagina.value = 1
  claveBuscador.value += 1
}

function irAPagina(numero: number): void {
  pagina.value = Math.min(Math.max(numero, 1), totalPaginas.value)
}

function reintentar(): void {
  void refresh()
}
</script>

<template>
  <div class="container py-5 catalogo-tramites">
    <nav class="migas" aria-label="Ruta">
      <NuxtLink to="/">Inicio</NuxtLink>
      <span aria-hidden="true">›</span>
      <span aria-current="page">Trámites y servicios</span>
    </nav>

    <h1 class="mt-2">Trámites y servicios</h1>
    <p class="lead mb-0">Trámites, OPA y consultas de acceso a información pública.</p>

    <!-- Acceso por grupo (Anexo 2.1). Son botones con aria-pressed, no pestañas ARIA: los filtros
         son los mismos para los tres grupos. -->
    <div class="selector-grupos mt-3" role="group" aria-label="Grupo del catálogo">
      <button
        v-for="id in ORDEN_GRUPOS"
        :key="id"
        type="button"
        class="pestana-grupo"
        :class="{ 'pestana-grupo-activa': id === grupoActivo }"
        :aria-pressed="id === grupoActivo"
        @click="alElegirGrupo(id)"
      >
        {{ GRUPOS[id].nombreCorto }}
      </button>
    </div>

    <div class="directorio mt-3">
      <!-- Columna lateral: el buscador, siempre a la vista, y los filtros. -->
      <div class="lateral">
        <div class="lateral-buscar">
          <h2 id="titulo-buscar" class="lateral-titulo">Buscar</h2>
          <BuscadorGovco
            :key="claveBuscador"
            etiqueta="Buscar dentro de trámites, OPA y consultas de acceso a información pública"
            placeholder="Palabra clave"
            texto-boton="Buscar en el catálogo"
            :valor-inicial="terminoAplicado"
            @buscar="alBuscar"
            @limpiar="alLimpiarBusqueda"
          />
        </div>

        <!-- Botón de filtros, sólo en móvil: pliega el panel para no empujar el primer resultado. -->
        <button
          type="button"
          class="boton-filtros mt-3"
          :aria-expanded="filtrosAbiertos"
          aria-controls="panel-filtros"
          @click="filtrosAbiertos = !filtrosAbiertos"
        >
          {{ filtrosAbiertos ? 'Ocultar filtros' : 'Mostrar filtros' }}
        </button>

        <aside
          id="panel-filtros"
          class="panel-filtros"
          :class="{ 'panel-filtros-abierto': filtrosAbiertos }"
          aria-label="Filtrar el catálogo"
        >
          <div class="panel-filtros-cabecera">
            <h2 class="lateral-titulo mb-0">Filtrar por:</h2>
            <button v-if="hayFiltros" type="button" class="enlace-limpiar" @click="limpiarFiltros">
              Limpiar
            </button>
          </div>

          <AcordeonGovco
            v-if="facetas && facetas.modalidades.length > 0"
            v-model:abierto="gruposAbiertos.modalidad"
            titulo="Modalidad"
            :nivel="3"
          >
            <ul class="faceta-lista" role="list">
              <li v-for="f in facetas.modalidades" :key="f.valor">
                <button
                  type="button"
                  class="faceta-opcion"
                  :aria-pressed="modalidadElegida === f.valor"
                  @click="alternar('modalidad', f.valor)"
                >
                  <span>{{ ETIQUETA_MODALIDAD[f.valor] ?? f.valor }}</span>
                  <span class="faceta-cuenta">{{ f.total }}</span>
                </button>
              </li>
            </ul>
          </AcordeonGovco>

          <AcordeonGovco
            v-if="facetas && facetas.costos.length > 0"
            v-model:abierto="gruposAbiertos.costo"
            titulo="Costo"
            :nivel="3"
          >
            <ul class="faceta-lista" role="list">
              <li v-for="f in facetas.costos" :key="f.valor">
                <button
                  type="button"
                  class="faceta-opcion"
                  :aria-pressed="costoElegido === f.valor"
                  @click="alternar('costo', f.valor)"
                >
                  <span>{{ ETIQUETA_COSTO[f.valor] ?? f.valor }}</span>
                  <span class="faceta-cuenta">{{ f.total }}</span>
                </button>
              </li>
            </ul>
          </AcordeonGovco>

          <AcordeonGovco
            v-if="facetas && facetas.categorias.length > 0"
            v-model:abierto="gruposAbiertos.categoria"
            titulo="Temáticas"
            :nivel="3"
          >
            <ul class="faceta-lista" role="list">
              <li v-for="f in facetas.categorias" :key="f.slug">
                <button
                  type="button"
                  class="faceta-opcion"
                  :aria-pressed="categoriaElegida === f.slug"
                  @click="alternar('categoria', f.slug)"
                >
                  <span>{{ f.nombre }}</span>
                  <span class="faceta-cuenta">{{ f.total }}</span>
                </button>
              </li>
            </ul>
          </AcordeonGovco>

          <AcordeonGovco
            v-if="facetas && facetas.dependencias.length > 0"
            v-model:abierto="gruposAbiertos.dependencia"
            titulo="Dependencias"
            :nivel="3"
          >
            <ul class="faceta-lista" role="list">
              <li v-for="f in facetas.dependencias" :key="f.id">
                <button
                  type="button"
                  class="faceta-opcion"
                  :aria-pressed="dependenciaElegida === f.id"
                  @click="alternarDependencia(f.id)"
                >
                  <span>{{ f.nombre }}</span>
                  <span class="faceta-cuenta">{{ f.total }}</span>
                </button>
              </li>
            </ul>
          </AcordeonGovco>

          <AcordeonGovco
            v-if="facetas && facetas.solicitantes.length > 0"
            v-model:abierto="gruposAbiertos.solicitante"
            titulo="Tipo de solicitante"
            :nivel="3"
          >
            <ul class="faceta-lista" role="list">
              <li v-for="f in facetas.solicitantes" :key="f.valor">
                <button
                  type="button"
                  class="faceta-opcion"
                  :aria-pressed="solicitanteElegido === f.valor"
                  @click="alternar('solicitante', f.valor)"
                >
                  <span>{{ ETIQUETA_SOLICITANTE[f.valor] ?? f.valor }}</span>
                  <span class="faceta-cuenta">{{ f.total }}</span>
                </button>
              </li>
            </ul>
          </AcordeonGovco>
        </aside>
      </div>

      <!-- Resultados. -->
      <section class="resultados" aria-label="Resultados">
        <div class="barra-resumen" role="status">
          <p class="resumen-cuenta mb-0">{{ resumenResultados }}</p>
        </div>

        <CargandoGovco v-if="cargando" class="estado-vacio mt-2" mensaje="Cargando el catálogo…" />

        <div v-else-if="fallo" class="estado-vacio estado-vacio-error mt-2" role="alert">
          <p class="estado-vacio-titulo mb-2">No se pudo cargar el catálogo de trámites.</p>
          <p class="mb-2">
            La sede no pudo consultar el catálogo publicado en este momento. No es que no haya
            trámites: es que no se pudieron leer.
          </p>
          <button type="button" class="btn btn-govco outline-btn-govco btn-catalogo" @click="reintentar">
            Reintentar
          </button>
        </div>

        <ul v-else-if="elementos.length > 0" class="filas mt-2" role="list">
          <li v-for="t in elementos" :key="t.slug" class="fila">
            <div class="fila-texto">
              <h3 class="fila-nombre h5">
                <!-- El título lleva a la ficha completa en GOV.CO; sin ficha nacional, a la propia. -->
                <a
                  v-if="enlaceTramite(t).externo"
                  :href="enlaceTramite(t).href"
                  class="fila-enlace"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {{ t.nombre }}
                  <span class="visually-hidden">(abre la ficha en GOV.CO, en una pestaña nueva)</span>
                </a>
                <NuxtLink v-else :to="enlaceTramite(t).href" class="fila-enlace">{{ t.nombre }}</NuxtLink>
              </h3>
              <p v-if="t.resumen" class="fila-resumen mb-0">{{ t.resumen }}</p>
              <!-- Las dos fichas son alcanzables: la de GOV.CO (título) y la propia, con sus pasos y la valoración. -->
              <NuxtLink
                v-if="enlaceTramite(t).externo"
                :to="'/tramites/' + t.slug"
                class="fila-enlace fila-detalle"
              >
                Detalle<span class="visually-hidden"> de {{ t.nombre }}</span>
              </NuxtLink>
            </div>

            <ul class="fila-datos" role="list">
              <li v-if="textoModalidad(t.modalidad)" class="dato dato-modalidad">
                <svg v-if="iconoModalidad(t.modalidad) === 'persona'" class="dato-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <circle cx="8" cy="5" r="2.6" fill="currentColor" />
                  <path d="M2.5 14c0-3 2.4-4.8 5.5-4.8s5.5 1.8 5.5 4.8z" fill="currentColor" />
                </svg>
                <svg v-else class="dato-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <path d="M2 3h12v8H2z" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M6 13h4M8 11v2" stroke="currentColor" stroke-width="1.3" />
                </svg>
                <span>{{ textoModalidad(t.modalidad) }}</span>
              </li>
              <li v-if="textoCosto(t.tiene_costo)" class="dato">
                <svg class="dato-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M8 4.5v7M6.3 6.2c0-1 .8-1.5 1.7-1.5s1.7.5 1.7 1.4c0 1.8-3.4 1-3.4 2.9 0 .9.8 1.5 1.7 1.5s1.7-.5 1.7-1.5" fill="none" stroke="currentColor" stroke-width="1.1" />
                </svg>
                <span>{{ textoCosto(t.tiene_costo) }}</span>
              </li>
              <li v-if="textoDuracion(t.tiempo_solucion_dias)" class="dato">
                <svg class="dato-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M8 4.5V8l2.5 1.5" fill="none" stroke="currentColor" stroke-width="1.3" />
                </svg>
                <span>{{ textoDuracion(t.tiempo_solucion_dias) }}</span>
              </li>
              <li v-if="t.dependencia" class="dato">
                <svg class="dato-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <path d="M3 13V4l5-2 5 2v9" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M6 7h1M9 7h1M6 10h1M9 10h1" stroke="currentColor" stroke-width="1.2" />
                </svg>
                <span>{{ t.dependencia.nombre }}</span>
              </li>
              <!-- Rótulo de destino del título, no un enlace. El logo es blanco, va sobre la pastilla azul. -->
              <li v-if="enlaceTramite(t).externo" class="dato dato-govco">
                <span class="insignia-govco" aria-hidden="true">
                  <img src="/govco/assets/images/logo.svg" alt="" width="65" height="14" />
                </span>
                <span>Trámite en gov.co</span>
              </li>
            </ul>
          </li>
        </ul>

        <div
          v-else
          class="estado-vacio mt-2"
          :class="{ 'estado-vacio-publicacion': estadoVacio.esPublicacion }"
        >
          <p class="estado-vacio-titulo mb-2">{{ estadoVacio.titulo }}</p>
          <p class="mb-0">{{ estadoVacio.detalle }}</p>
        </div>

        <PaginacionGovco
          class="mt-4"
          :pagina-actual="paginaActual"
          :total-paginas="totalPaginas"
          :extremos-apagados="sinPaginasQueOfrecer"
          @cambiar="irAPagina"
        />
      </section>
    </div>
  </div>
</template>

<style scoped>
/* Migas de pan. */
.migas {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}
.migas a {
  color: var(--govcolor-cobalt, #0943b5);
}

/* Selector de grupo: píldora del Kit, no las pestañas de Bootstrap (que fijan otro azul). */
.selector-grupos {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.pestana-grupo {
  padding: 0.688rem 1rem;
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  border-radius: 1.563rem;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-cobalt, #0943b5);
  font: inherit;
  line-height: 1rem;
  overflow-wrap: anywhere;
  cursor: pointer;
  min-height: 2.75rem;
}
.pestana-grupo-activa {
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  font-weight: 700;
}
.pestana-grupo:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

/* Botón de filtros (sólo móvil). */
.boton-filtros {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  padding: 0.5rem 1rem;
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  border-radius: 0.375rem;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-cobalt, #0943b5);
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}
.boton-filtros:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

/* Rejilla: en escritorio, columna lateral + resultados; en móvil, una columna. */
.directorio {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

/* --- Columna lateral: buscador + filtros -------------------------------- */
.lateral-titulo {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--govcolor-black, #000);
  margin: 0 0 0.5rem;
}

/* --- Panel de filtros ----------------------------------------------------- */
.panel-filtros {
  display: none;
  margin-top: 1rem;
}
.panel-filtros-abierto {
  display: block;
}
.panel-filtros-cabecera {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}
.enlace-limpiar {
  border: 0;
  background: none;
  color: var(--govcolor-cobalt, #0943b5);
  font: inherit;
  text-decoration: underline;
  cursor: pointer;
  min-height: 2.75rem;
}
.enlace-limpiar:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

/* Los grupos son acordeones del Kit; dentro del panel se aprietan: el Kit les da el relleno de una
   página, no el de una columna de 17 rem. */
.panel-filtros :deep(.accordion-govco) {
  margin: 0;
}
.panel-filtros :deep(.item-accordion-govco),
.panel-filtros :deep(.accordion-header),
.panel-filtros :deep(.accordion-collapse),
.panel-filtros :deep(.body-accordion-govco) {
  background-color: transparent;
}
.panel-filtros :deep(.item-accordion-govco) {
  border-bottom: 1px solid var(--govcolor-silver, #b9b9b9);
}
.panel-filtros :deep(.button-accordion-govco) {
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  background-color: transparent;
}
.panel-filtros :deep(.button-accordion-govco),
.panel-filtros :deep(.text-button-accordion-govco) {
  font-size: 1.125rem;
  font-weight: 400;
}
.panel-filtros :deep(.body-accordion-govco) {
  padding: 0 0 0.5rem;
}

.faceta-lista {
  list-style: none;
  margin: 0;
  padding: 0;
}
.faceta-opcion {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  min-height: 2.75rem;
  padding: 0.35rem 0.5rem;
  border: 0;
  border-radius: 0.375rem;
  background: none;
  color: var(--govcolor-cobalt, #0943b5);
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.faceta-opcion:hover {
  background-color: var(--govcolor-solitude, #e5ecf8);
}
.faceta-opcion[aria-pressed='true'] {
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  font-weight: 700;
}
.faceta-opcion:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}
.faceta-cuenta {
  flex-shrink: 0;
  font-size: 0.8125rem;
  color: inherit;
  opacity: 0.85;
}

/* --- Resultados: una fila por trámite ------------------------------------- */
.barra-resumen {
  margin-bottom: 0.5rem;
}
.resumen-cuenta {
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.filas {
  list-style: none;
  margin: 0;
  padding: 0;
}
.fila {
  /* El tope de 68 ch que `sitio.css` pone a todo `li` de `#contenido-principal` es para prosa; una fila de
     directorio ocupa su columna. `min-width` gana a `max-width` cuando chocan, así no hace falta un
     `#id` en el selector (el proyecto no los admite fuera de la capa de modos de accesibilidad). */
  min-width: 100%;
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem 2rem;
  padding: 1.25rem 0.5rem;
  border-top: 1px solid var(--govcolor-silver, #b9b9b9);
}
.fila:last-child {
  border-bottom: 1px solid var(--govcolor-silver, #b9b9b9);
}
.fila:hover,
.fila:focus-within {
  background-color: var(--govcolor-solitude, #e5ecf8);
}
.fila-nombre {
  margin-bottom: 0.35rem;
}
.fila-enlace {
  color: var(--govcolor-cobalt, #0943b5);
  text-decoration: underline;
  text-underline-offset: 0.15em;
}
/* Objetivo táctil de al menos 44 px (WCAG 2.5.8 / criterio móvil del proyecto) sin agrandar el texto. */
.fila-nombre .fila-enlace {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
}
.fila-detalle {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  margin-top: 0.25rem;
  font-weight: 700;
}
/* Al pasar el cursor la fila se tiñe; el `a:hover` del Kit baja el enlace a un azul que ahí ya no llega
   a 4,5:1 (medido: 3,93). El token del proyecto para enlaces sobre fondo teñido sí. */
.fila:hover .fila-enlace,
.fila-enlace:hover {
  color: var(--color-enlace-sobre-tenido, #00568d);
}
.fila-enlace:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}
.fila-resumen {
  color: var(--govcolor-matterhorn, #4c4c4c);
  /* Cuatro líneas como máximo: las filas conservan una altura comparable. */
  display: -webkit-box;
  -webkit-line-clamp: 4;
  line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Columna de datos: icono + etiqueta (nunca sólo icono), uno bajo otro. */
.fila-datos {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.25rem;
  align-content: start;
}
.dato {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}
.dato-modalidad {
  color: var(--govcolor-green, #158361);
}
.dato-icono {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}
.dato-govco {
  margin-top: 0.25rem;
  color: var(--govcolor-black, #000);
}
/* El logo de GOV.CO es blanco: sobre fondo claro no se vería. Va en una pastilla azul. */
.insignia-govco {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.4rem;
  border-radius: 0.25rem;
  background-color: var(--govcolor-cobalt, #0943b5);
}
.insignia-govco img {
  display: block;
}

/* Botones del catálogo. */
.btn-catalogo {
  min-height: 2.75rem;
}

/* --- Estados vacíos ----------------------------------------------------- */
.estado-vacio {
  padding: 1rem 1.25rem;
  border-left: 0.25rem solid var(--govcolor-silver, #b9b9b9);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
}
.estado-vacio-publicacion {
  border-left-color: var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-vis-vis, #fee697);
}
.estado-vacio-error {
  border-left-color: var(--govcolor-red, #a80521);
}
.estado-vacio-titulo {
  font-weight: 700;
}

/* --- Escritorio: columna lateral + filas ------------------------------- */
@media (min-width: 62rem) {
  .boton-filtros {
    display: none;
  }
  .directorio {
    grid-template-columns: 18rem 1fr;
    align-items: start;
  }
  /* El panel lateral, con el tono neutro del sitio (no hay un beige entre los tokens). */
  .lateral {
    padding: 1.25rem 1rem;
    border-radius: 0.5rem;
    background-color: var(--govcolor-white-smoke, #f4f4f4);
  }
  .panel-filtros {
    display: block;
  }
  .fila {
    grid-template-columns: 1fr 15rem;
  }
  .fila-datos {
    flex-direction: column;
    flex-wrap: nowrap;
  }
}

/* --- Texto sólo para lectores de pantalla ------------------------------- */
.solo-lectores {
  position: absolute;
  overflow: hidden;
  clip-path: inset(50%);
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  white-space: nowrap;
}
</style>

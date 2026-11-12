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
 * **Presentación de directorio.** Cada trámite es una tarjeta que se compara de un vistazo: su
 * nombre lleva a la **ficha propia** de la Sede (destino principal), y la ficha oficial de GOV.CO
 * es un enlace secundario cuando existe. La tarjeta muestra, con icono y etiqueta, la modalidad, si
 * tiene costo, la duración y la dependencia —los metadatos que el ciudadano necesita para elegir—.
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

/** Seis por página: con la primera fila compacta, el primer resultado cabe sin hacer scroll. */
const TAMANO_PAGINA = 6

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
// Presentación de cada tarjeta
// ---------------------------------------------------------------------------

function textoModalidad(valor: string | null | undefined): string | null {
  return valor == null ? null : (ETIQUETA_MODALIDAD[valor] ?? valor)
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

    <!-- Buscador, siempre a la vista. -->
    <div class="mt-3">
      <BuscadorGovco
        :key="claveBuscador"
        etiqueta="Buscar dentro de trámites, OPA y consultas de acceso a información pública"
        placeholder="Buscar en trámites y servicios"
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

    <div class="directorio mt-3">
      <!-- Barra lateral de facetas. En escritorio siempre visible; en móvil se pliega. -->
      <aside
        id="panel-filtros"
        class="panel-filtros"
        :class="{ 'panel-filtros-abierto': filtrosAbiertos }"
        aria-label="Filtrar el catálogo"
      >
        <div class="panel-filtros-cabecera">
          <h2 class="h6 mb-0">Filtrar por</h2>
          <button v-if="hayFiltros" type="button" class="enlace-limpiar" @click="limpiarFiltros">
            Limpiar
          </button>
        </div>

        <fieldset v-if="facetas && facetas.modalidades.length > 0" class="faceta">
          <legend class="faceta-titulo">Modalidad</legend>
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
        </fieldset>

        <fieldset v-if="facetas && facetas.costos.length > 0" class="faceta">
          <legend class="faceta-titulo">Costo</legend>
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
        </fieldset>

        <fieldset v-if="facetas && facetas.categorias.length > 0" class="faceta">
          <legend class="faceta-titulo">Tema</legend>
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
        </fieldset>

        <fieldset v-if="facetas && facetas.dependencias.length > 0" class="faceta">
          <legend class="faceta-titulo">Dependencia</legend>
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
        </fieldset>

        <fieldset v-if="facetas && facetas.solicitantes.length > 0" class="faceta">
          <legend class="faceta-titulo">Tipo de solicitante</legend>
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
        </fieldset>
      </aside>

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

        <ul v-else-if="elementos.length > 0" class="tarjetas mt-2" role="list">
          <li v-for="t in elementos" :key="t.slug" class="tarjeta">
            <h3 class="tarjeta-nombre h5">
              <!-- La ficha completa vive en GOV.CO: la tarjeta lleva allí; sin ficha nacional, a la propia. -->
              <a
                v-if="enlaceTramite(t).externo"
                :href="enlaceTramite(t).href"
                class="tarjeta-enlace"
                target="_blank"
                rel="noopener noreferrer"
              >
                {{ t.nombre }}
                <span class="solo-lectores">(abre la ficha en GOV.CO, en una pestaña nueva)</span>
              </a>
              <NuxtLink v-else :to="enlaceTramite(t).href" class="tarjeta-enlace">{{ t.nombre }}</NuxtLink>
            </h3>
            <p v-if="t.resumen" class="tarjeta-resumen mb-2">{{ t.resumen }}</p>

            <ul class="chips" role="list">
              <li v-if="textoModalidad(t.modalidad)" class="chip">
                <svg class="chip-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <path d="M2 3h12v8H2z" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M6 13h4M8 11v2" stroke="currentColor" stroke-width="1.3" />
                </svg>
                <span>{{ textoModalidad(t.modalidad) }}</span>
              </li>
              <li v-if="textoCosto(t.tiene_costo)" class="chip">
                <svg class="chip-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M8 4.5v7M6.3 6.2c0-1 .8-1.5 1.7-1.5s1.7.5 1.7 1.4c0 1.8-3.4 1-3.4 2.9 0 .9.8 1.5 1.7 1.5s1.7-.5 1.7-1.5" fill="none" stroke="currentColor" stroke-width="1.1" />
                </svg>
                <span>{{ textoCosto(t.tiene_costo) }}</span>
              </li>
              <li v-if="textoDuracion(t.tiempo_solucion_dias)" class="chip">
                <svg class="chip-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M8 4.5V8l2.5 1.5" fill="none" stroke="currentColor" stroke-width="1.3" />
                </svg>
                <span>{{ textoDuracion(t.tiempo_solucion_dias) }}</span>
              </li>
              <li v-if="t.dependencia" class="chip">
                <svg class="chip-icono" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                  <path d="M3 13V4l5-2 5 2v9" fill="none" stroke="currentColor" stroke-width="1.3" />
                  <path d="M6 7h1M9 7h1M6 10h1M9 10h1" stroke="currentColor" stroke-width="1.2" />
                </svg>
                <span>{{ t.dependencia.nombre }}</span>
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

/* Rejilla: en escritorio, barra lateral + resultados; en móvil, una columna. */
.directorio {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

/* --- Panel de facetas --------------------------------------------------- */
.panel-filtros {
  display: none;
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 0.5rem;
  padding: 1rem;
  background-color: var(--govcolor-white, #ffffff);
}
.panel-filtros-abierto {
  display: block;
}
.panel-filtros-cabecera {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 0.5rem;
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
.faceta {
  border: 0;
  margin: 0 0 1rem;
  padding: 0;
}
.faceta-titulo {
  float: none;
  width: auto;
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
  margin-bottom: 0.35rem;
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
  min-height: 2.5rem;
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
  background-color: var(--govcolor-white-smoke, #f4f4f4);
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

/* --- Resultados --------------------------------------------------------- */
.barra-resumen {
  margin-bottom: 0.5rem;
}
.resumen-cuenta {
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.tarjetas {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
.tarjeta {
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 0.5rem;
  padding: 1rem 1.125rem;
  background-color: var(--govcolor-white, #ffffff);
  transition:
    box-shadow 0.15s ease,
    border-color 0.15s ease;
}
.tarjeta:hover,
.tarjeta:focus-within {
  border-color: var(--govcolor-cobalt, #0943b5);
  box-shadow: 0 0.25rem 0.75rem rgb(0 0 0 / 0.08);
}
.tarjeta-nombre {
  margin-bottom: 0.35rem;
}
.tarjeta-enlace {
  color: var(--govcolor-cobalt, #0943b5);
  text-decoration: none;
}
.tarjeta-enlace:hover {
  text-decoration: underline;
}
.tarjeta-enlace:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}
.tarjeta-resumen {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.9375rem;
  /* Dos líneas como máximo: las tarjetas conservan una altura comparable. */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Chips de metadatos: icono + etiqueta (nunca sólo icono). */
.chips {
  list-style: none;
  margin: 0 0 0.5rem;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem 0.5rem;
}
.chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.55rem;
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 1rem;
  font-size: 0.8125rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
}
.chip-icono {
  width: 0.9rem;
  height: 0.9rem;
  flex-shrink: 0;
  color: var(--govcolor-cobalt, #0943b5);
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

/* --- Escritorio: barra lateral + rejilla de dos columnas --------------- */
@media (min-width: 62rem) {
  .boton-filtros {
    display: none;
  }
  .directorio {
    grid-template-columns: 17rem 1fr;
    align-items: start;
  }
  .panel-filtros {
    display: block;
    position: sticky;
    top: 1rem;
  }
  .tarjetas {
    grid-template-columns: repeat(2, 1fr);
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

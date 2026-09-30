<script setup lang="ts">
/**
 * Trámites y servicios — catálogo de la Sede.
 *
 * **Qué exige esta página.** El Anexo 2.1 — Guía de diseño gráfico para sedes
 * electrónicas, de obligatorio cumplimiento por el artículo 14 del Decreto 2106
 * de 2019, páginas 10 y 11, pide que «en la sección de Trámites y servicios se
 * visualicen los Trámites, OPA's y Consultas de acceso a información pública que
 * la autoridad tenga disponibles», con **mecanismos de acceso** —etiquetas,
 * desplegables o campo de texto—, el **número de resultados** siempre a la vista
 * y, en la galería, dos destinos por elemento: el **nombre** lleva a la ficha del
 * trámite en GOV.CO y el botón **«Trámite en línea»** a la página donde esté
 * alojado. Su ejemplo añade la **paginación**: «Anterior 1 2 … 20 Siguiente».
 * Y exige que «se visualice al menos un resultado en la pantalla sin necesidad de
 * hacer scroll», que es la razón de que todo lo que va por encima de la lista
 * —título, selector de grupo, filtros y contador— sea deliberadamente compacto.
 *
 * **Por qué el catálogo está vacío.** No es un olvido ni un hueco de maqueta: la
 * Entidad todavía no ha entregado sus trámites, el CMS no está hecho y el
 * contrato (`contract/openapi.yaml`, `GET /tramites`) devuelve hoy una colección
 * vacía. Un trámite inventado no es relleno: es un procedimiento con requisitos,
 * costo y plazo que nadie ha aprobado, y el ciudadano decide sobre esa
 * información como si fuera oficial. Inventarlo en una sede electrónica es
 * publicar información oficial falsa. Por eso aquí se publica **la interfaz
 * completa con su estado vacío honesto**, y no una galería de ejemplo.
 *
 * El filtro, el buscador, el contador y la paginación **funcionan de verdad**
 * sobre la lista: en cuanto la lista tenga elementos, todo lo demás ya trabaja.
 *
 * **Cómo entra el catálogo.** Cuando la Entidad entregue los datos, esta
 * constante se sustituye por la lectura del catálogo publicado —
 * `useAsyncData('tramites', () => $fetch('/api/v1/tramites'))` sobre
 * `GET /tramites` del contrato — mapeando cada elemento de la API a
 * `TramiteCatalogo`. Nada más de esta página hay que tocar.
 */
useHead({
  title: 'Trámites y servicios · Sede Electrónica',
  meta: [
    {
      name: 'description',
      content:
        'Trámites, otros procedimientos administrativos (OPA) y consultas de acceso a información pública de la Alcaldía Distrital de Santa Marta, con la ficha de cada uno en GOV.CO.',
    },
  ],
})

// ---------------------------------------------------------------------------
// Modelo del catálogo
// ---------------------------------------------------------------------------

/** Los tres grupos que el Anexo 2.1 manda visualizar en esta sección. */
type GrupoCatalogo = 'tramites' | 'opa' | 'consultas'

interface Grupo {
  /** Nombre completo del grupo, con el que el Anexo lo nombra. */
  nombre: string
  /**
   * Forma corta con la que el botón del grupo se rotula. Son las palabras que
   * usa el propio Anexo al enumerar los tres grupos —«los Trámites, OPA's y
   * Consultas de acceso a información pública»—, y el nombre completo sigue a la
   * vista justo debajo, en el encabezado del grupo activo. Se rotula corto
   * porque con los nombres largos los tres botones se parten en tres filas en un
   * teléfono y empujan los resultados fuera de la pantalla, que es justo lo que
   * el Anexo prohíbe.
   */
  nombreCorto: string
  /**
   * Qué publica el grupo. Es la frase del propio Anexo —«que la autoridad tenga
   * disponibles»— repartida entre los tres: no se define nada por cuenta propia,
   * porque una definición inventada de «OPA» sería tan falsa como un trámite
   * inventado.
   */
  descripcion: string
}

/** Un elemento de la galería. */
interface TramiteCatalogo {
  /** Identificador estable del catálogo; es la clave de la lista. */
  slug: string
  grupo: GrupoCatalogo
  nombre: string
  descripcion: string
  categoria: CategoriaCatalogo
  /**
   * Ficha del elemento en GOV.CO (SUIT). **Obligatoria por el Anexo**: es el
   * destino del clic en el nombre. Un elemento sin ficha en GOV.CO no se
   * publica, con la misma regla con la que el contrato no publica un trámite al
   * que le falte uno de sus seis atributos obligatorios: antes de ofrecer un
   * enlace roto, no se ofrece el elemento.
   */
  urlFichaGovCo: string
  /** Página donde está alojado el trámite. Sin ella no hay botón que mostrar. */
  urlTramiteEnLinea?: string
}

interface CategoriaCatalogo {
  slug: string
  nombre: string
}

interface EstadoVacio {
  titulo: string
  detalle: string
}

/** Hueco de la paginación: o un número de página o los puntos suspensivos. */
type HuecoPaginacion =
  | { tipo: 'pagina'; numero: number; clave: string }
  | { tipo: 'separador'; clave: string }

/**
 * Los tres grupos del Anexo, en un objeto y no en un arreglo, para que
 * `GRUPOS[unGrupo]` esté tipado como `Grupo` y no como `Grupo | undefined`: con
 * la comprobación estricta del proyecto, indexar un arreglo devuelve `undefined`
 * y obligaría a defenderse de un caso que el tipo ya descarta.
 */
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
    descripcion:
      'Consultas de acceso a información pública que la Entidad tiene disponibles.',
  },
}

/** Orden de los grupos en pantalla: el del Anexo 2.1, no el alfabético. */
const ORDEN_GRUPOS: readonly GrupoCatalogo[] = ['tramites', 'opa', 'consultas']

/**
 * El catálogo. Hoy vacío, y a propósito: ver la cabecera de este archivo. Todo
 * lo que hay debajo —filtro por categoría, buscador, contador, paginación—
 * trabaja sobre esta lista, así que la página ya está lista para recibir los
 * datos.
 */
const catalogo: TramiteCatalogo[] = []

/**
 * Seis elementos por página. Es el tamaño con el que el catálogo no se convierte
 * en una lista interminable ni obliga a paginar de más. Con la primera fila
 * compacta —título, descripción y botón— la primera cabe holgadamente por
 * encima del pliegue de una pantalla de 800 px de alto, que es lo que el Anexo
 * exige («se visualice al menos un resultado sin necesidad de hacer scroll»).
 */
const TAMANO_PAGINA = 6

/** Cuántas páginas se ven alrededor de la actual antes de poner puntos suspensivos. */
const PAGINAS_ALREDEDOR = 1

// ---------------------------------------------------------------------------
// Estado de los mecanismos de acceso
// ---------------------------------------------------------------------------

const grupoActivo = ref<GrupoCatalogo>('tramites')

/**
 * El término **aplicado**, no el que se está escribiendo: la búsqueda se dispara
 * al enviar el formulario del buscador, que es como funciona `BuscadorGovco`, y
 * así el contador no cambia a cada tecla.
 */
const terminoAplicado = ref('')

/** Slug de la categoría elegida. Cadena vacía significa «Todas las categorías». */
const categoriaElegida = ref('')

const pagina = ref(1)

/**
 * Cambiar esta clave vuelve a montar el buscador. Hace falta porque
 * `BuscadorGovco` guarda su propio texto: al limpiar los filtros desde fuera,
 * el campo se quedaría con lo escrito y contradiría al contador.
 */
const claveBuscador = ref(0)

// ---------------------------------------------------------------------------
// Derivados
// ---------------------------------------------------------------------------

const grupo = computed<Grupo>(() => GRUPOS[grupoActivo.value])

/** Elementos del grupo activo, antes de filtrar. */
const delGrupo = computed<TramiteCatalogo[]>(() =>
  catalogo.filter((elemento) => elemento.grupo === grupoActivo.value),
)

/**
 * Categorías que ofrece el desplegable: las que de verdad tienen elementos en el
 * grupo activo. No se declara ninguna lista de categorías —el Anexo no las fija
 * y la Entidad no las ha entregado—, así que el desplegable no puede ofrecer
 * una categoría que no exista.
 */
const categorias = computed<CategoriaCatalogo[]>(() => {
  const porSlug = new Map<string, CategoriaCatalogo>()
  for (const elemento of delGrupo.value) {
    if (!porSlug.has(elemento.categoria.slug)) {
      porSlug.set(elemento.categoria.slug, elemento.categoria)
    }
  }
  return [...porSlug.values()].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
})

/**
 * Resultados tras aplicar categoría y término. El término se compara sin
 * mayúsculas ni tildes porque el ciudadano escribe «tramite» y el catálogo dice
 * «trámite»; exigirle la tilde para encontrar lo que busca sería una barrera de
 * accesibilidad, no un filtro. Se busca en nombre y descripción, igual que el
 * parámetro `buscar` del contrato.
 */
const resultados = computed<TramiteCatalogo[]>(() => {
  const buscado = normalizar(terminoAplicado.value)
  return delGrupo.value.filter((elemento) => {
    if (categoriaElegida.value !== '' && elemento.categoria.slug !== categoriaElegida.value) {
      return false
    }
    if (buscado === '') return true
    return (
      normalizar(elemento.nombre).includes(buscado) ||
      normalizar(elemento.descripcion).includes(buscado)
    )
  })
})

const totalPaginas = computed(() => Math.max(1, Math.ceil(resultados.value.length / TAMANO_PAGINA)))

/** La página pedida, siempre dentro del total: al filtrar puede quedar fuera. */
const paginaActual = computed(() => Math.min(Math.max(pagina.value, 1), totalPaginas.value))

const resultadosDeLaPagina = computed<TramiteCatalogo[]>(() => {
  const desde = (paginaActual.value - 1) * TAMANO_PAGINA
  return resultados.value.slice(desde, desde + TAMANO_PAGINA)
})

/** Nombre de la categoría elegida, si sigue existiendo en el grupo activo. */
const nombreCategoriaElegida = computed(() => {
  const encontrada = categorias.value.find((categoria) => categoria.slug === categoriaElegida.value)
  return encontrada?.nombre ?? ''
})

const hayFiltros = computed(
  () => terminoAplicado.value !== '' || categoriaElegida.value !== '',
)

/**
 * El número de resultados y el contexto en el que se está buscando, en una sola
 * frase: es el «número de resultados que se visualizan» que el Anexo exige tener
 * a la vista. Va en una región `role="status"` para que el lector de pantalla
 * anuncie el recuento nuevo cuando el ciudadano busca o cambia de grupo, en vez
 * de dejarlo cambiar en silencio.
 *
 * Va en una línea y no en dos —el recuento arriba y el tramo visible debajo—
 * porque cada línea de más por encima de la lista empuja el primer resultado
 * fuera de la pantalla, que es lo que el Anexo prohíbe.
 */
const resumenResultados = computed<string>(() => {
  const total = resultados.value.length
  const clausulas: string[] = []
  if (terminoAplicado.value !== '') clausulas.push(`para «${terminoAplicado.value}»`)
  if (nombreCategoriaElegida.value !== '') {
    clausulas.push(`de la categoría «${nombreCategoriaElegida.value}»`)
  }
  const contexto = clausulas.length > 0 ? ` ${clausulas.join(' ')}` : ''
  const cuenta = `${total} ${total === 1 ? 'resultado' : 'resultados'}`

  if (total <= 1) return `${cuenta}${contexto} en ${grupo.value.nombre}.`

  const desde = (paginaActual.value - 1) * TAMANO_PAGINA + 1
  const hasta = Math.min(paginaActual.value * TAMANO_PAGINA, total)
  return `Mostrando ${desde} a ${hasta} de ${cuenta}${contexto} en ${grupo.value.nombre}.`
})

/**
 * El estado vacío dice dos cosas distintas según por qué no hay nada, y no se
 * confunden: que el catálogo no esté publicado es un hecho de la Entidad; que
 * una búsqueda no encuentre nada es un hecho del filtro.
 */
const estadoVacio = computed<EstadoVacio>(() => {
  if (delGrupo.value.length === 0) {
    return {
      titulo: 'Todavía no hay trámites publicados en esta sección.',
      detalle:
        'Aquí se publicarán los trámites, los OPA y las consultas de acceso a información pública con la ficha de cada uno: qué es, quién puede solicitarlo, requisitos, costo, tiempo de respuesta y el enlace para iniciarlo. Mientras la Entidad no entregue el catálogo, esta sede no publica ninguno: un trámite inventado daría por ciertos unos requisitos, un costo y un plazo que nadie ha aprobado.',
    }
  }
  return {
    titulo: 'No hay resultados para esta búsqueda.',
    detalle: 'Pruebe con otras palabras, elija otra categoría o limpie los filtros.',
  }
})

/** Páginas que se dibujan, con puntos suspensivos cuando hay muchas. */
const paginasVisibles = computed<HuecoPaginacion[]>(() => {
  const total = totalPaginas.value
  const actual = paginaActual.value
  const holgura = PAGINAS_ALREDEDOR * 2 + 1

  if (total <= holgura + 2) {
    return Array.from({ length: total }, (_sinUso, indice) => ({
      tipo: 'pagina' as const,
      numero: indice + 1,
      clave: `pagina-${indice + 1}`,
    }))
  }

  const huecos: HuecoPaginacion[] = [{ tipo: 'pagina', numero: 1, clave: 'pagina-1' }]
  const desde = Math.max(2, actual - PAGINAS_ALREDEDOR)
  const hasta = Math.min(total - 1, actual + PAGINAS_ALREDEDOR)

  if (desde > 2) huecos.push({ tipo: 'separador', clave: 'separador-inicial' })
  for (let numero = desde; numero <= hasta; numero += 1) {
    huecos.push({ tipo: 'pagina', numero, clave: `pagina-${numero}` })
  }
  if (hasta < total - 1) huecos.push({ tipo: 'separador', clave: 'separador-final' })
  huecos.push({ tipo: 'pagina', numero: total, clave: `pagina-${total}` })

  return huecos
})

// ---------------------------------------------------------------------------
// Acciones
// ---------------------------------------------------------------------------

/**
 * Texto comparable: sin tildes y en minúsculas. Se usa `normalize('NFD')` y se
 * quitan los diacríticos que la descomposición deja sueltos.
 */
function normalizar(texto: string): string {
  return texto
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

/** Cambiar de filtro devuelve a la primera página: la actual puede no existir ya. */
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
  // Las categorías salen del grupo: la elegida puede no existir en el nuevo.
  categoriaElegida.value = ''
  pagina.value = 1
}

function alCambiarCategoria(evento: Event): void {
  const destino = evento.target
  if (!(destino instanceof HTMLSelectElement)) return
  categoriaElegida.value = destino.value
  pagina.value = 1
}

function limpiarFiltros(): void {
  terminoAplicado.value = ''
  categoriaElegida.value = ''
  pagina.value = 1
  claveBuscador.value += 1
}

function irAPagina(numero: number): void {
  pagina.value = Math.min(Math.max(numero, 1), totalPaginas.value)
}
</script>

<template>
  <div class="container py-5 catalogo-tramites">
    <h1>Trámites y servicios</h1>
    <p class="lead mb-0">Trámites, OPA y consultas de acceso a información pública.</p>

    <!--
      Selector de grupo. Son botones con `aria-pressed` y no un juego de pestañas
      ARIA: los filtros —categoría y buscador— son los mismos para los tres
      grupos, así que no hay tres paneles que cada pestaña posea, y declarar
      `role="tab"` sin panel propio sería mentir sobre la estructura. Como
      botones, además, basta con `Tab` y `Enter` y el foco no necesita flechas.
    -->
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

    <!--
      El nombre del grupo va como encabezado de nivel 2 —la página ya tiene su
      h1 y los nombres de los elementos son h3— con la descripción en la misma
      línea: el Anexo exige que al menos un resultado se vea sin desplazar la
      pantalla, y todo lo que ocupe de más por encima de la lista se come ese
      alto. La descripción va **fuera** del encabezado, en un `span` hermano: si
      estuviera dentro, quien navega por encabezados oiría «Trámites. Trámites y
      servicios que la Entidad tiene disponibles» cada vez que pasa por él.
    -->
    <div class="grupo-encabezado mt-3">
      <h2 class="h5 d-inline mb-0">{{ grupo.nombre }}</h2>
      <span class="descripcion-grupo">{{ grupo.descripcion }}</span>
    </div>

    <!-- Mecanismos de acceso: desplegable de categorías y buscador. -->
    <div class="row g-3 align-items-end mt-2">
      <div class="col-12 col-md-5 col-lg-4">
        <label class="form-label" for="categoria-catalogo">Categoría</label>
        <select
          id="categoria-catalogo"
          class="form-select"
          :value="categoriaElegida"
          @change="alCambiarCategoria"
        >
          <option value="">Todas las categorías</option>
          <option v-for="categoria in categorias" :key="categoria.slug" :value="categoria.slug">
            {{ categoria.nombre }}
          </option>
        </select>
      </div>
      <div class="col-12 col-md-7 col-lg-8">
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
    </div>

    <!--
      El recuento va en una región viva: cuando el ciudadano busca o cambia de
      grupo, el lector de pantalla lo anuncia. Es el «número de resultados que se
      visualizan» que el Anexo exige que aparezca.

      El botón de limpiar va a su lado, en la misma fila, y **fuera** de la
      región viva: dentro, cada recuento nuevo lo volvería a anunciar.
    -->
    <div class="barra-resumen mt-2">
      <div class="resumen" role="status">
        <p class="resumen-cuenta mb-0">{{ resumenResultados }}</p>
      </div>
      <button
        v-if="hayFiltros"
        type="button"
        class="btn btn-govco outline-btn-govco btn-catalogo ms-auto"
        @click="limpiarFiltros"
      >
        Limpiar los filtros
      </button>
    </div>

    <!-- Galería de resultados. -->
    <ul v-if="resultadosDeLaPagina.length > 0" class="lista-resultados mt-2" role="list">
      <li v-for="elemento in resultadosDeLaPagina" :key="elemento.slug" class="resultado">
        <!--
          El nombre lleva a la ficha del elemento en GOV.CO, como exige el
          Anexo. Se dice en la línea de metadatos hacia dónde va el enlace: quien
          lo pulsa sale de la sede, y tiene derecho a saberlo antes.
        -->
        <h3 class="resultado-nombre h5">
          <a :href="elemento.urlFichaGovCo" class="resultado-enlace">{{ elemento.nombre }}</a>
        </h3>
        <p class="resultado-descripcion mb-2">{{ elemento.descripcion }}</p>
        <p class="resultado-meta mb-2">
          <span class="etiqueta-categoria">{{ elemento.categoria.nombre }}</span>
          <span class="origen-enlace">Ficha del trámite en GOV.CO</span>
        </p>
        <!-- Sin página de inicio en línea no hay botón que ofrecer, y un botón
             que no lleva a ninguna parte es peor que su ausencia. -->
        <a
          v-if="elemento.urlTramiteEnLinea !== undefined"
          class="btn btn-govco outline-btn-govco btn-catalogo"
          :href="elemento.urlTramiteEnLinea"
        >
          Trámite en línea<span class="solo-lectores">: {{ elemento.nombre }}</span>
        </a>
      </li>
    </ul>

    <!--
      Estado vacío. No dice «no hay resultados» a secas: distingue «la Entidad no
      ha publicado el catálogo» de «su búsqueda no encontró nada», porque son dos
      situaciones distintas y confundirlas haría creer que el catálogo existe. El
      aviso neutro es el del filtro; el amarillo institucional se reserva para
      cuando el que falta es el catálogo.
    -->
    <div
      v-else
      class="estado-vacio mt-2"
      :class="{ 'estado-vacio-publicacion': delGrupo.length === 0 }"
    >
      <p class="estado-vacio-titulo mb-2">{{ estadoVacio.titulo }}</p>
      <p class="mb-0">{{ estadoVacio.detalle }}</p>
    </div>

    <!--
      Paginación. Se dibuja también cuando sólo hay una página: el Anexo pide
      que el mecanismo de acceso y sus elementos secundarios aparezcan, y
      esconderlos cuando hay pocos resultados daría a entender que la sección no
      los tiene. Con una sola página los dos extremos quedan deshabilitados.
    -->
    <nav class="mt-4" aria-label="Paginación de los resultados">
      <ul class="pagination paginacion-catalogo mb-0">
        <li class="page-item" :class="{ disabled: paginaActual === 1 }">
          <button
            type="button"
            class="page-link"
            :disabled="paginaActual === 1"
            @click="irAPagina(paginaActual - 1)"
          >
            Anterior
          </button>
        </li>
        <li
          v-for="hueco in paginasVisibles"
          :key="hueco.clave"
          class="page-item"
          :class="{ active: hueco.tipo === 'pagina' && hueco.numero === paginaActual }"
        >
          <button
            v-if="hueco.tipo === 'pagina'"
            type="button"
            class="page-link"
            :aria-current="hueco.numero === paginaActual ? 'page' : undefined"
            @click="irAPagina(hueco.numero)"
          >
            {{ hueco.numero }}
          </button>
          <span v-else class="page-link" aria-hidden="true">…</span>
        </li>
        <li class="page-item" :class="{ disabled: paginaActual === totalPaginas }">
          <button
            type="button"
            class="page-link"
            :disabled="paginaActual === totalPaginas"
            @click="irAPagina(paginaActual + 1)"
          >
            Siguiente
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<style scoped>
/*
  Selector de grupo. Se dibuja como los botones del Kit —píldora con borde de
  2 px y radio de 1,563rem, el mismo de `.btn-govco`— y no con las pestañas de
  Bootstrap: las de Bootstrap fijan el azul #0d6efd en duro y su geometría de
  bordes pegados se rompe cuando las tres etiquetas largas tienen que partirse
  en varias líneas a 320 px. Además, al ser controles de filtro y no pestañas,
  un botón es lo que corresponde.
*/
.selector-grupos {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pestana-grupo {
  padding: 0.688rem 1rem;
  border: 0.125rem solid var(--govcolor-cobalt);
  border-radius: 1.563rem;
  background-color: var(--govcolor-white);
  color: var(--govcolor-cobalt);
  font: inherit;
  line-height: 1rem;
  text-align: left;
  overflow-wrap: anywhere;
  cursor: pointer;
  /* CAG-23: 44 px de alto es el mínimo de un blanco de pulsación en móvil. */
  min-height: 2.75rem;
}

/* El grupo activo se marca por color y por contraste invertido, no sólo por
   color: el relleno cambia junto con el texto. */
.pestana-grupo-activa {
  background-color: var(--govcolor-cobalt);
  color: var(--govcolor-white);
  font-weight: 700;
}

.pestana-grupo:focus-visible {
  outline: 3px solid var(--govcolor-cobalt);
  outline-offset: 2px;
}

.descripcion-grupo {
  /* Hermana del encabezado del grupo, no parte de él: se queda en el tamaño del
     cuerpo y sólo acompaña al nombre. */
  margin-left: 0.35rem;
  font-size: 1rem;
  font-weight: 400;
  color: var(--govcolor-matterhorn);
}

/* Enlaces de la página en el cobalto del Kit, que sobre blanco da 8,46:1. */
.catalogo-tramites a {
  color: var(--govcolor-cobalt);
}

/* Botones del catálogo («Trámite en línea», «Limpiar los filtros»). */
.btn-catalogo {
  min-height: 2.75rem;
}

/* --- Recuento ----------------------------------------------------------- */

/* El recuento y el botón de limpiar, en una fila; el botón se va al extremo
   derecho cuando hay ancho y baja de línea cuando no. */
.barra-resumen {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 1rem;
}

.resumen-cuenta {
  font-weight: 700;
  color: var(--govcolor-matterhorn);
}

/* --- Resultados --------------------------------------------------------- */

.lista-resultados {
  margin: 0;
  padding: 0;
  list-style: none;
}

/*
  Filas compactas. Es una decisión de diseño con motivo: el Anexo exige que al
  menos un resultado se vea sin desplazar la pantalla, y lo que hay por encima de
  la lista —título, selector, filtros y recuento— ocupa un alto que no se puede
  recortar sin quitarle al ciudadano algo que necesita. Lo que sí se puede
  apretar es la fila.
*/
.resultado {
  padding: 0.875rem 0;
  border-top: 1px solid var(--govcolor-silver);
}

.resultado:last-child {
  border-bottom: 1px solid var(--govcolor-silver);
}

.resultado-nombre {
  margin-bottom: 0.25rem;
}

.resultado-descripcion {
  color: var(--govcolor-matterhorn);
}

.resultado-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  font-size: 0.9375rem;
}

.etiqueta-categoria {
  padding: 0.125rem 0.625rem;
  border: 1px solid var(--govcolor-cobalt);
  border-radius: 1rem;
  color: var(--govcolor-cobalt);
}

.origen-enlace {
  color: var(--govcolor-matterhorn);
}

/* --- Estados vacíos ----------------------------------------------------- */

/*
  El estado de «catálogo todavía no publicado» usa el mismo amarillo y el mismo
  filete que el aviso de sección en preparación del resto del sitio, para que se
  lea igual en todas partes. El de «la búsqueda no encontró nada» es neutro: no
  es un aviso sobre la Entidad, es el resultado de un filtro.
*/
.estado-vacio {
  padding: 1rem 1.25rem;
  border-left: 0.25rem solid var(--govcolor-silver);
  background-color: var(--govcolor-white-smoke);
}

.estado-vacio-publicacion {
  border-left-color: var(--govcolor-golden-brown);
  background-color: var(--govcolor-vis-vis);
}

.estado-vacio-titulo {
  font-weight: 700;
}

/* --- Paginación --------------------------------------------------------- */

/* Los elementos llevan los bordes pegados —Bootstrap los une con un margen
   negativo—, así que sólo se separan las filas cuando la paginación parte. */
.paginacion-catalogo {
  flex-wrap: wrap;
  row-gap: 0.5rem;
}

/* Bootstrap fija el azul #0d6efd en duro; el Kit y la Entidad usan el cobalto. */
.paginacion-catalogo .page-link {
  color: var(--govcolor-cobalt);
}

.paginacion-catalogo .page-item.active .page-link {
  background-color: var(--govcolor-cobalt);
  border-color: var(--govcolor-cobalt);
  color: var(--govcolor-white);
}

.paginacion-catalogo .page-link:focus-visible {
  outline: 3px solid var(--govcolor-cobalt);
  outline-offset: 2px;
}

/*
  CAG-23: en móvil todo control tiene que medir al menos 44 × 44 px. El de la
  paginación es el más expuesto, porque sus dos botones son de texto corto y
  quedan por debajo de esa medida con el relleno del Kit.
*/
@media (max-width: 47.9375rem) {
  .paginacion-catalogo .page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2.75rem;
    min-height: 2.75rem;
  }

  /* El desplegable de categorías mide 38 px de alto con el relleno del Kit. */
  .catalogo-tramites .form-select {
    min-height: 2.75rem;
  }
}

/* --- Texto sólo para lectores de pantalla ------------------------------- */

/*
  Se declara aquí en vez de usar la utilidad `visually-hidden` de Bootstrap para
  no arrastrar sus `!important`; es el mismo criterio que sigue `BuscadorGovco`.
*/
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

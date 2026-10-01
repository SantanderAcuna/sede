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
 * **De dónde sale el catálogo.** De `GET /tramites` del contrato
 * (`contract/openapi.yaml`), que es la única fuente de verdad del intercambio
 * entre el backend y el sitio. Lo que la galería dibuja es lo que esa operación
 * devuelve, y nada más: un trámite no es relleno, es un procedimiento con
 * requisitos, costo y plazo, y el ciudadano decide sobre esa información como si
 * fuera oficial. Inventarlo en una sede electrónica es publicar información
 * oficial falsa, así que aquí sólo se publica lo que la Entidad tiene publicado.
 *
 * **La paginación y la búsqueda son del servidor, y el contrato lo dice.** El
 * contrato declara `page` y `per_page` —con 100 como máximo— y devuelve el sobre
 * `meta` con las siete claves del paginador, así que la página pide **una página
 * cada vez** en lugar de traerse el catálogo entero para trocearlo aquí: con 123
 * trámites publicados, y un catálogo que crece, traerse todo en cada visita para
 * enseñar seis es pagar por lo que no se ve. Por la misma razón `buscar` y
 * `categoria` viajan como parámetros: filtrar en el navegador sobre la página
 * recibida devolvería «0 resultados» para un trámite que sí existe en la página
 * siguiente, que es peor que no ofrecer el filtro. El repositorio del backend
 * busca además contra el texto normalizado —sin tildes—, de modo que quien
 * escribe «areas» encuentra «áreas».
 *
 * **Los tres estados se distinguen, y ninguno miente.** Si la API no responde,
 * la página lo dice y ofrece reintentar; no cae, y sobre todo no finge un vacío:
 * «todavía no hay trámites publicados» es un enunciado sobre la Entidad, y
 * decirlo cuando lo que pasa es que el servidor no contesta sería mentir. Los
 * dos vacíos que ya existían siguen separados por la misma razón: que la Entidad
 * no tenga nada publicado en el grupo es un hecho suyo, y que una búsqueda no
 * encuentre nada es un hecho del filtro.
 */
import type { components } from '~~/types/openapi'

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

interface CategoriaCatalogo {
  slug: string
  nombre: string
}

/**
 * Un trámite del contrato, tal como viaja por la API.
 *
 * Es el tipo generado desde `contract/openapi.yaml` —el archivo no se escribe a
 * mano— para que la forma del elemento no se pueda desincronizar del contrato
 * sin que el compilador lo diga.
 */
type TramiteApi = components['schemas']['TramiteItem']

/**
 * Lo que esta página lee del sobre de `GET /tramites`.
 *
 * Las dos claves que se usan —los elementos y el paginador— son las del
 * contrato, tomadas del mismo archivo generado. El sobre **no** se declara con
 * `TramiteCollection` por un defecto medido de la generación: `ApiEnvelope.data`
 * está declarado como `object | array | null` y `TramiteCollection` compone tres
 * esquemas que vuelven a redeclararlo, así que para TypeScript `data` acaba
 * siendo `unknown[]` y cada elemento habría que forzarlo con un `as` —que es
 * justo lo que aquí no se quiere—. Declarar sólo lo que se lee deja el resto del
 * sobre donde tiene que estar: en el contrato.
 */
interface RespuestaCatalogo {
  data: TramiteApi[]
  meta: components['schemas']['PageMeta']
}

/** Un elemento de la galería. */
interface TramiteCatalogo {
  /** Identificador estable del catálogo; es la clave de la lista. */
  slug: string
  nombre: string
  descripcion: string
  /**
   * Clasificación del trámite, si la Entidad la ha declarado. Es **opcional** y
   * no un valor de relleno: el contrato la declara nula mientras la Entidad no
   * la declare, y ponerle una inventada aquí sería tan falso como inventarse el
   * trámite.
   */
  categoria?: CategoriaCatalogo
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

interface EstadoVacio {
  titulo: string
  detalle: string
  /**
   * Si el vacío habla de la Entidad —no hay nada publicado— o del filtro —la
   * búsqueda no encontró nada—. El aviso amarillo institucional se reserva para
   * el primero.
   */
  esPublicacion: boolean
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
 * Los grupos que el catálogo público sirve hoy.
 *
 * `GET /tramites` no tiene parámetro de grupo —el contrato no declara ninguno— y
 * la fuente oficial de la que la Entidad siembra su catálogo, SUIT, sólo
 * clasifica trámites: lo que esa operación devuelve son trámites y nada más. Los
 * otros dos grupos que el Anexo manda visualizar —OPA y consultas de acceso a
 * información pública— no tienen todavía nada que publicar, y la página lo dice
 * con su estado vacío en vez de repartirles una clasificación que la Entidad no
 * ha declarado. Cuando el contrato tenga con qué distinguirlos, esta constante
 * es lo único que hay que cambiar aquí.
 */
const GRUPOS_PUBLICADOS: readonly GrupoCatalogo[] = ['tramites']

/**
 * Seis elementos por página. Es el tamaño con el que el catálogo no se convierte
 * en una lista interminable ni obliga a paginar de más. Con la primera fila
 * compacta —título, descripción y botón— la primera cabe holgadamente por
 * encima del pliegue de una pantalla de 800 px de alto, que es lo que el Anexo
 * exige («se visualice al menos un resultado sin necesidad de hacer scroll»).
 *
 * Viaja como `per_page` en cada petición: el troceado lo hace el servidor, no el
 * navegador.
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
// Lectura del catálogo
// ---------------------------------------------------------------------------

/**
 * La URL base de la API. En producción el sitio y la API comparten origen, así
 * que la ruta relativa que trae `runtimeConfig` basta; cuando no, se apunta al
 * origen donde esté la API. Se le quita la barra final para no acabar pidiendo
 * `//tramites`.
 */
const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')

/** Si el grupo activo es uno de los que el catálogo público sirve hoy. */
const grupoPublicado = computed<boolean>(() => GRUPOS_PUBLICADOS.includes(grupoActivo.value))

/**
 * La colección vacía con la que responde un grupo que el catálogo todavía no
 * sirve, sin gastar una petición: la respuesta está decidida de antemano. `path`
 * va vacío porque no hay petición que lo haya producido; el resto son los
 * valores que el contrato da a una colección sin elementos.
 */
const COLECCION_VACIA: RespuestaCatalogo = {
  data: [],
  meta: {
    current_page: 1,
    from: null,
    last_page: 1,
    path: '',
    per_page: TAMANO_PAGINA,
    to: null,
    total: 0,
  },
}

/**
 * `useRequestFetch` y no `$fetch` a secas: durante el renderizado en servidor
 * resuelve la ruta relativa contra la petición en curso, y la URL por defecto del
 * sitio —`/api/v1`— es relativa, porque en producción el sitio y la API comparten
 * origen. Con `$fetch` a secas, el servidor no tendría contra qué resolverla.
 */
const traer = useRequestFetch()

/**
 * El catálogo: una petición por página, término y categoría.
 *
 * El contrato declara `page`, `per_page`, `buscar` y `categoria`, así que el
 * troceado y el filtrado los hace el servidor. Traerse el catálogo entero para
 * hacerlo aquí sería hacer el trabajo dos veces —y la segunda con la copia peor:
 * la que no ve lo que no se ha traído—.
 */
const { data, status, refresh } = await useAsyncData<RespuestaCatalogo>(
  'tramites-catalogo',
  async () => {
    if (!grupoPublicado.value) return COLECCION_VACIA

    const consulta: Record<string, string | number> = {
      page: pagina.value,
      per_page: TAMANO_PAGINA,
    }
    // Una cadena vacía no es un filtro: el contrato la trata como ausente. No se
    // manda igualmente porque una URL con `?buscar=` afirma que alguien buscó, y
    // el servidor devolvería el catálogo entero bajo esa apariencia.
    if (terminoAplicado.value !== '') consulta.buscar = terminoAplicado.value
    if (categoriaElegida.value !== '') consulta.categoria = categoriaElegida.value

    return await traer<RespuestaCatalogo>(`${urlApi}/tramites`, { query: consulta })
  },
  { watch: [pagina, terminoAplicado, categoriaElegida, grupoActivo] },
)

// ---------------------------------------------------------------------------
// Derivados
// ---------------------------------------------------------------------------

const grupo = computed<Grupo>(() => GRUPOS[grupoActivo.value])

/** Los elementos de la página recibida, ya en la forma que usa la galería. */
const elementos = computed<TramiteCatalogo[]>(() => (data.value?.data ?? []).map(aCatalogo))

/** El total de la consulta entera —no el de la página—, tal como lo da el sobre. */
const totalResultados = computed<number>(() => data.value?.meta.total ?? 0)

const totalPaginas = computed<number>(() => Math.max(1, data.value?.meta.last_page ?? 1))

/** La página pedida, siempre dentro del total: al filtrar puede quedar fuera. */
const paginaActual = computed<number>(() => Math.min(Math.max(pagina.value, 1), totalPaginas.value))

/**
 * Mientras se pide una página nueva —o al cambiar el término— lo que hay en
 * `data` es todavía la respuesta anterior: enseñarla junto al recuento nuevo
 * sería decir que ese recuento describe esa lista. Por eso la carga ocupa el
 * sitio de la galería en vez de convivir con ella.
 */
const cargando = computed<boolean>(() => status.value === 'pending' || status.value === 'idle')

const fallo = computed<boolean>(() => status.value === 'error')

/**
 * Sin respuesta buena no hay páginas que ofrecer. La paginación se sigue
 * dibujando —el Anexo la pide como parte del mecanismo de acceso— pero con los
 * dos extremos apagados, en vez de invitar a pulsar sobre un catálogo que no se
 * ha podido leer.
 */
const hayAnterior = computed<boolean>(
  () => !cargando.value && !fallo.value && paginaActual.value > 1,
)

const haySiguiente = computed<boolean>(
  () => !cargando.value && !fallo.value && paginaActual.value < totalPaginas.value,
)

/**
 * Las categorías que ofrece el desplegable.
 *
 * El contrato permite filtrar por `categoria`, pero **no tiene ninguna operación
 * que las enumere**: la única fuente son los elementos ya recibidos, y por eso
 * esta lista es parcial —sólo puede ofrecer las categorías que declaren los
 * elementos de las páginas ya consultadas—. Se van **acumulando**, además,
 * porque derivarlas sólo de la respuesta en curso haría que elegir una categoría
 * borrara del desplegable todas las demás —la respuesta filtrada ya no las
 * trae—, que es justo lo contrario de lo que un desplegable sirve.
 *
 * Enumerarlas todas exigiría traerse el catálogo entero en cada visita, que es
 * exactamente lo que la paginación del servidor evita; lo que hace falta es una
 * operación del contrato que las liste —o un `meta` que las traiga—. Mientras no
 * exista, esto es lo único que se puede ofrecer sin inventar ni pagar de más.
 *
 * Hoy no aparece ninguna, y no es un hueco: el propio contrato lo dice —«la
 * fuente oficial de la que se siembra el catálogo no clasifica los trámites por
 * categoría»—, así que el desplegable ofrece «Todas las categorías» y nada más.
 * La lista no se inventa: se enseña la que la Entidad declare.
 */
const categorias = ref<CategoriaCatalogo[]>([])

watch(
  elementos,
  (lista) => {
    const porSlug = new Map(categorias.value.map((categoria) => [categoria.slug, categoria]))
    let nueva = false
    for (const elemento of lista) {
      const categoria = elemento.categoria
      if (categoria !== undefined && !porSlug.has(categoria.slug)) {
        porSlug.set(categoria.slug, categoria)
        nueva = true
      }
    }
    if (nueva) {
      categorias.value = [...porSlug.values()].sort((a, b) =>
        a.nombre.localeCompare(b.nombre, 'es'),
      )
    }
  },
  { immediate: true },
)

/** Nombre de la categoría elegida, si sigue existiendo entre las conocidas. */
const nombreCategoriaElegida = computed<string>(() => {
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
 *
 * El total y el tramo son los del sobre —`meta.total`, `meta.from`, `meta.to`—
 * y no los de la lista dibujada: con la paginación en el servidor, «de 6» sería
 * el número de la página, y al ciudadano lo que le interesa saber es cuántos
 * trámites hay en total.
 */
const resumenResultados = computed<string>(() => {
  if (cargando.value) return 'Cargando el catálogo…'
  if (fallo.value) return 'No se pudo cargar el catálogo de trámites.'

  const total = totalResultados.value
  const clausulas: string[] = []
  if (terminoAplicado.value !== '') clausulas.push(`para «${terminoAplicado.value}»`)
  if (nombreCategoriaElegida.value !== '') {
    clausulas.push(`de la categoría «${nombreCategoriaElegida.value}»`)
  }
  const contexto = clausulas.length > 0 ? ` ${clausulas.join(' ')}` : ''
  const cuenta = `${total} ${total === 1 ? 'resultado' : 'resultados'}`

  if (total <= 1) return `${cuenta}${contexto} en ${grupo.value.nombre}.`

  const desde = data.value?.meta.from ?? 1
  const hasta = data.value?.meta.to ?? total
  return `Mostrando ${desde} a ${hasta} de ${cuenta}${contexto} en ${grupo.value.nombre}.`
})

/**
 * El estado vacío dice dos cosas distintas según por qué no hay nada, y no se
 * confunden: que la Entidad no tenga nada publicado es un hecho de la Entidad;
 * que una búsqueda no encuentre nada es un hecho del filtro.
 *
 * El primero alcanza también al grupo que el catálogo público todavía no sirve
 * —hoy OPA y consultas—: ahí lo cierto es que no hay nada publicado, no que una
 * búsqueda haya fallado.
 */
const estadoVacio = computed<EstadoVacio>(() => {
  const sinPublicar = !grupoPublicado.value || (!hayFiltros.value && totalResultados.value === 0)

  if (sinPublicar) {
    return {
      titulo: 'Todavía no hay trámites publicados en esta sección.',
      detalle:
        'Esta sección publica lo que la Entidad tiene disponible, con la ficha de cada uno: qué es, quién puede solicitarlo, requisitos, costo, tiempo de respuesta y el enlace para iniciarlo. Mientras no haya nada publicado aquí, la sede no muestra ningún trámite de ejemplo: uno inventado daría por ciertos unos requisitos, un costo y un plazo que nadie ha aprobado.',
      esPublicacion: true,
    }
  }
  return {
    titulo: 'No hay resultados para esta búsqueda.',
    detalle: 'Pruebe con otras palabras, elija otra categoría o limpie los filtros.',
    esPublicacion: false,
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
// Mapeo del contrato a la galería
// ---------------------------------------------------------------------------

/**
 * Lo que el contrato deja en blanco: `null`, la clave ausente y la cadena vacía
 * dicen lo mismo —que no hay valor— y en la galería los tres son la misma cosa,
 * `undefined`. Así el botón «Trámite en línea» se decide con una sola
 * comprobación y no con tres.
 */
function opcional(texto: string | null | undefined): string | undefined {
  if (texto === null || texto === undefined || texto === '') return undefined
  return texto
}

/**
 * Un trámite del contrato, en la forma que usa la galería.
 *
 * El contrato viaja en `snake_case` —es el intercambio HTTP— y la página trabaja
 * en `camelCase`, así que esta función es la única frontera entre los dos. Es
 * una traducción de nombres y nada más: no completa huecos, no inventa
 * categorías y no rellena `url_inicio`. Lo que el catálogo no trae, no se
 * dibuja.
 */
function aCatalogo(elemento: TramiteApi): TramiteCatalogo {
  return {
    slug: elemento.slug,
    nombre: elemento.nombre,
    descripcion: elemento.resumen ?? '',
    categoria: elemento.categoria ?? undefined,
    urlFichaGovCo: elemento.url_ficha_gov_co,
    urlTramiteEnLinea: opcional(elemento.url_inicio),
  }
}

// ---------------------------------------------------------------------------
// Acciones
// ---------------------------------------------------------------------------

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

/**
 * Volver a pedir el catálogo después de un fallo, con los mismos filtros y la
 * misma página: es lo que el ciudadano espera de un «Reintentar», y no que le
 * devuelva la primera página de todo.
 */
function reintentar(): void {
  void refresh()
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

    <!--
      Los resultados, y lo que ocupa su sitio cuando no los hay. Los tres estados
      van separados y en este orden: la carga tapa la galería —lo que hay en
      `data` mientras se pide una página nueva es la respuesta anterior, y su
      recuento ya no describe lo que se está pidiendo—, el fallo se declara en
      vez de disfrazarse de vacío, y sólo con una respuesta buena y sin elementos
      se habla de vacío.
    -->
    <p v-if="cargando" class="estado-vacio mt-2">Cargando el catálogo…</p>

    <!--
      Que la API no conteste no es que no haya trámites. Se dice lo que ha pasado
      —la sede no pudo leer su catálogo— y se ofrece volver a intentarlo, en vez
      de dejar al ciudadano creyendo que la Entidad no tiene nada publicado. Va
      con `role="alert"` para que se anuncie en cuanto aparece.
    -->
    <div v-else-if="fallo" class="estado-vacio estado-vacio-error mt-2" role="alert">
      <p class="estado-vacio-titulo mb-2">No se pudo cargar el catálogo de trámites.</p>
      <p class="mb-2">
        La sede no pudo consultar el catálogo publicado en este momento. No es que no
        haya trámites: es que no se pudieron leer.
      </p>
      <button
        type="button"
        class="btn btn-govco outline-btn-govco btn-catalogo"
        @click="reintentar"
      >
        Reintentar
      </button>
    </div>

    <!-- Galería de resultados. -->
    <ul v-else-if="elementos.length > 0" class="lista-resultados mt-2" role="list">
      <li v-for="elemento in elementos" :key="elemento.slug" class="resultado">
        <!--
          El nombre lleva a la ficha del elemento en GOV.CO, como exige el
          Anexo. Se dice en la línea de metadatos hacia dónde va el enlace: quien
          lo pulsa sale de la sede, y tiene derecho a saberlo antes.
        -->
        <h3 class="resultado-nombre h5">
          <a :href="elemento.urlFichaGovCo" class="resultado-enlace">{{ elemento.nombre }}</a>
        </h3>
        <p v-if="elemento.descripcion !== ''" class="resultado-descripcion mb-2">
          {{ elemento.descripcion }}
        </p>
        <p class="resultado-meta mb-2">
          <!-- La categoría sólo se dibuja si la Entidad la declaró: una etiqueta
               sin nombre ocuparía sitio sin decir nada. -->
          <span v-if="elemento.categoria !== undefined" class="etiqueta-categoria">
            {{ elemento.categoria.nombre }}
          </span>
          <span class="origen-enlace">Ficha del trámite en GOV.CO</span>
        </p>
        <!--
          La ficha propia, **sin quitar el enlace a GOV.CO**.

          El Anexo manda que el nombre lleve a la ficha oficial de GOV.CO, y eso
          sigue haciendo el `h3` de arriba: este enlace no lo sustituye, se añade
          al lado. Los dos destinos dicen cosas distintas y el ciudadano necesita
          los dos: GOV.CO publica la ficha tal como la Entidad la registró en el
          SUIT —que es el origen oficial y el que se puede citar—, y esta ficha
          publica los requisitos, el costo, dónde se atiende y qué norma lo
          faculta, que es lo que el SUIT no declara por atributo y lo que la Sede
          sí puede sostener.
        -->
        <NuxtLink class="enlace-ficha d-block mb-2" :to="`/tramites/${elemento.slug}`">
          Ver la ficha completa<span class="solo-lectores">: {{ elemento.nombre }}</span>
        </NuxtLink>

        <!-- Sin página de inicio en línea no hay botón que ofrecer, y un botón
             que no lleva a ninguna parte es peor que su ausencia. La fuente
             oficial declara «en línea» o «parcialmente en línea» para 37 de los
             123 trámites publicados y sólo trae la dirección de 7: no se rellena
             con nada, porque rellenarla sería inventarse el destino. -->
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
      ha publicado nada en esta sección» de «su búsqueda no encontró nada»,
      porque son dos situaciones distintas y confundirlas haría creer que el
      catálogo existe. El aviso neutro es el del filtro; el amarillo
      institucional se reserva para cuando el que falta es el catálogo.
    -->
    <div
      v-else
      class="estado-vacio mt-2"
      :class="{ 'estado-vacio-publicacion': estadoVacio.esPublicacion }"
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
        <li class="page-item" :class="{ disabled: !hayAnterior }">
          <button
            type="button"
            class="page-link"
            :disabled="!hayAnterior"
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
        <li class="page-item" :class="{ disabled: !haySiguiente }">
          <button
            type="button"
            class="page-link"
            :disabled="!haySiguiente"
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
  Bootstrap: las de Bootstrap fijan el azul #0d6efd en duro, que no es el
  cobalto del Kit, y su geometría de bordes pegados se rompe en cuanto una
  etiqueta se parte en dos líneas, que es lo que pasa a 320 px. Además, al ser
  controles de filtro y no pestañas, un botón es lo que corresponde.
*/
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
  text-align: left;
  overflow-wrap: anywhere;
  cursor: pointer;
  /* CAG-23: 44 px de alto es el mínimo de un blanco de pulsación en móvil. */
  min-height: 2.75rem;
}

/* El grupo activo se marca por color y por contraste invertido, no sólo por
   color: el relleno cambia junto con el texto. */
.pestana-grupo-activa {
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  font-weight: 700;
}

.pestana-grupo:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

.descripcion-grupo {
  /* Hermana del encabezado del grupo, no parte de él: se queda en el tamaño del
     cuerpo y sólo acompaña al nombre. */
  margin-left: 0.35rem;
  font-size: 1rem;
  font-weight: 400;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/* El color de los enlaces y el foco de los campos los fija la hoja del sitio
   (`app/assets/css/sitio.css`), no esta página: un enlace nuevo hereda el
   cobalto del Kit sin que nadie tenga que escribirlo. Aquí sólo va lo propio de
   esta vista. */

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
  color: var(--govcolor-matterhorn, #4c4c4c);
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
  border-top: 1px solid var(--govcolor-silver, #b9b9b9);
}

.resultado:last-child {
  border-bottom: 1px solid var(--govcolor-silver, #b9b9b9);
}

.resultado-nombre {
  margin-bottom: 0.25rem;
}

.resultado-descripcion {
  color: var(--govcolor-matterhorn, #4c4c4c);
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
  border: 1px solid var(--govcolor-cobalt, #0943b5);
  border-radius: 1rem;
  color: var(--govcolor-cobalt, #0943b5);
}

.origen-enlace {
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/* --- Estados vacíos ----------------------------------------------------- */

/*
  El estado de «catálogo todavía no publicado» usa el mismo amarillo y el mismo
  filete que el aviso de sección en preparación del resto del sitio, para que se
  lea igual en todas partes. El de «la búsqueda no encontró nada» es neutro: no
  es un aviso sobre la Entidad, es el resultado de un filtro. El de la carga
  también es neutro y por la misma razón: no dice nada de nadie todavía.
*/
.estado-vacio {
  padding: 1rem 1.25rem;
  border-left: 0.25rem solid var(--govcolor-silver, #b9b9b9);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
}

.estado-vacio-publicacion {
  border-left-color: var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-vis-vis, #fee697);
}

/*
  El fallo no es un vacío: es una avería. Lleva el rojo del Kit en el filete para
  que no se pueda confundir de un vistazo con «aquí no hay nada publicado». El
  texto se queda en el gris de siempre para no bajar el contraste.
*/
.estado-vacio-error {
  border-left-color: var(--govcolor-red, #a80521);
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
  color: var(--govcolor-cobalt, #0943b5);
}

.paginacion-catalogo .page-item.active .page-link {
  background-color: var(--govcolor-cobalt, #0943b5);
  border-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
}

.paginacion-catalogo .page-link:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
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

  /*
    El nombre es el enlace principal de cada fila —el que lleva a la ficha en
    GOV.CO— y como enlace de texto mide 27 px de alto. En móvil se le da el
    blanco de pulsación de CAG-23 sin cambiar lo que se ve: el texto queda
    centrado dentro de los 44 px.
  */
  .resultado-enlace {
    display: flex;
    align-items: center;
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

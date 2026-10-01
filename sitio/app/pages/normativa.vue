<script setup lang="ts">
/**
 * Normativa.
 *
 * **Qué manda esta página.** El **Anexo 2.1 — Guía de diseño gráfico para sedes
 * electrónicas**, de obligatorio cumplimiento por el artículo 14 del Decreto 2106
 * de 2019, fija en su página 8 los criterios de publicación de la normativa: los
 * documentos se publican en formatos que permitan descargarlos, consultarlos sin
 * restricciones legales, reutilizarlos, procesarlos por máquina y buscar dentro de
 * ellos; de cada norma se publica el **tipo, la fecha de expedición, la fecha de
 * publicación, el epígrafe o descripción corta y el enlace para su consulta**; el
 * listado va **de la más reciente a la más antigua**; la norma se publica en cuanto
 * se expide; los proyectos de normativa indican la **fecha máxima para presentar
 * comentarios** y un medio electrónico para enviarlos; y hay que señalar si la
 * norma está **en vigor**. La interfaz de esta página es ese conjunto de
 * requisitos: cada uno tiene su sitio en el marcado y en el modelo de datos, y
 * ninguno se deja para después.
 *
 * **Por qué no hay ni una norma publicada.** La Entidad todavía no ha entregado su
 * normativa y el gestor de contenidos que la va a publicar no está construido. En
 * una sede electrónica un documento inventado no es un hueco en una maqueta: es
 * información oficial falsa —con número, fecha y enlace que nadie expidió— sobre
 * la que el ciudadano decide. Por eso aquí no se escribe ninguna norma, ninguna
 * fecha y ningún enlace de relleno: la estructura está completa y **vacía**, y dice
 * por qué. Los recuentos del panel se calculan a partir del listado, de modo que
 * tampoco pueden inventarse: con el listado vacío no se muestra ninguno.
 *
 * **Un matiz importante: la vigencia es un dato, no un valor por defecto.** El
 * campo `enVigor` es obligatorio en el modelo y sólo lo puede rellenar la Entidad;
 * suponer «en vigor» para una norma sin dato es exactamente el error que este
 * requisito existe para evitar.
 *
 * **Qué sí se declara aquí.** Catálogos, no contenido: los **tipos de norma**, que
 * son categorías normativas reales (Ley, Decreto, Acuerdo, Resolución, Circular,
 * Ordenanza), y las **cuatro categorías** con las que el propio Anexo organiza la
 * Visualización normativa. Un catálogo no afirma que exista ningún documento.
 */
import { computed, ref } from 'vue'

/**
 * Tipos de norma que se publican. Es un catálogo cerrado, y de él sale el tipo
 * `TipoNorma`: así el filtro y el modelo no pueden desincronizarse de la lista.
 *
 * El orden es alfabético porque es como el Anexo presenta el panel de recuento
 * —«Acuerdos (8)», «Circulares (6)», «Decretos (5)», «Ordenanzas (2)»—, y porque
 * un orden alfabético no sugiere jerarquía entre tipos que no la tienen.
 *
 * `slug` existe para el `id` de cada opción del filtro: un identificador se escribe
 * sin tildes ni espacios, y derivarlo del nombre visible lo dejaría a merced del
 * texto.
 */
const TIPOS_NORMA = [
  { tipo: 'Acuerdo', slug: 'acuerdo', plural: 'Acuerdos' },
  { tipo: 'Circular', slug: 'circular', plural: 'Circulares' },
  { tipo: 'Decreto', slug: 'decreto', plural: 'Decretos' },
  { tipo: 'Ley', slug: 'ley', plural: 'Leyes' },
  { tipo: 'Ordenanza', slug: 'ordenanza', plural: 'Ordenanzas' },
  { tipo: 'Resolución', slug: 'resolucion', plural: 'Resoluciones' },
] as const

type TipoNorma = (typeof TIPOS_NORMA)[number]['tipo']

/** Valor del filtro cuando no se filtra por ningún tipo. */
const TODAS = 'todas'

type FiltroTipo = TipoNorma | typeof TODAS

/**
 * Las cuatro categorías con las que el Anexo organiza la sección (su «Visualización
 * normativa»). Son estructura, no normas: por eso van numeradas y sin recuento.
 *
 * La numeración «1.1»…«1.4» es la del propio Anexo y se escribe literal dentro de
 * cada etiqueta. Se usa una lista sin orden del navegador a propósito: el navegador
 * volvería a numerar los elementos y se leería «1. 1.1 Leyes».
 */
const CATEGORIAS_SECCION = [
  {
    numero: '1.1',
    etiqueta: 'Leyes',
    descripcion: 'Normas de rango legal aplicables a la Entidad y a los servicios que presta.',
  },
  {
    numero: '1.2',
    etiqueta: 'Decreto Único Regulatorio',
    descripcion:
      'Decretos que compilan y racionalizan la normativa aplicable, reunida en un solo cuerpo normativo.',
  },
  {
    numero: '1.3',
    etiqueta: 'Normativa aplicable',
    descripcion:
      'Acuerdos, decretos, resoluciones, circulares y ordenanzas expedidos por la Entidad o aplicables a ella.',
  },
  {
    numero: '1.4',
    etiqueta: 'Vínculo al Diario o Gaceta oficial',
    descripcion:
      'Enlace al medio de publicación oficial donde consta cada norma. Se publicará cuando la Entidad confirme ese vínculo: antes de comprobarlo, apuntar a una dirección sería inventar un enlace.',
  },
] as const

/**
 * Los criterios del Anexo 2.1 (página 8), en una línea cada uno. Se publican para
 * que quien audite la sección no tenga que abrir la guía, y para dejar escrito con
 * qué reglas se construyó.
 */
const CRITERIOS_PUBLICACION = [
  'Toda la normativa se publica en formatos que permiten descargarla, consultarla sin restricciones legales, reutilizarla, procesarla por máquina y buscar dentro de ella.',
  'De cada norma se publican el tipo, la fecha de expedición, la fecha de publicación, el epígrafe o descripción corta y el enlace para su consulta.',
  'El listado se organiza de la norma más reciente a la más antigua.',
  'La norma se publica en cuanto se expide, o en tiempo real.',
  'Los proyectos de normativa indican la fecha máxima para presentar comentarios y, al menos, un medio digital o electrónico para enviarlos.',
  'Se señala si la norma se encuentra en vigor o no.',
] as const

/**
 * Una norma publicada.
 *
 * Los cinco primeros campos son los que el Anexo exige publicar, y son obligatorios
 * aquí: un dato que falta se ve en la ficha, en vez de desaparecer del listado sin
 * que nadie lo note. `formato` es el del documento —PDF, HTML, ODF—, porque el
 * Anexo exige formatos descargables, reutilizables, procesables por máquina y
 * buscables, y el ciudadano tiene derecho a saber en qué formato va a recibirlo
 * antes de pulsar el enlace.
 *
 * Las fechas van como día de calendario en formato ISO (`AAAA-MM-DD`), que es como
 * las va a entregar el sistema de origen: compararlas como texto da el mismo
 * resultado que compararlas como fechas y no arrastra husos horarios.
 */
interface Norma {
  /** Identificador estable del documento en el gestor de contenidos. */
  id: string
  tipo: TipoNorma
  /** Número de la norma, tal como lo asigna la Entidad. */
  numero: string
  /** Fecha de expedición, `AAAA-MM-DD`. */
  fechaExpedicion: string
  /** Fecha de publicación, `AAAA-MM-DD`. */
  fechaPublicacion: string
  /** Epígrafe o descripción corta de la norma. */
  epigrafe: string
  /** Enlace para consultar el documento completo. */
  enlace: string
  /** Formato del documento enlazado: «PDF», «HTML», «ODF»… */
  formato: string
  /** Si la norma está en vigor. Sin dato, no se inventa: ver la nota de arriba. */
  enVigor: boolean
}

/**
 * Un proyecto de normativa sometido a comentarios.
 *
 * `fechaMaximaComentarios` y `canalComentarios` son obligatorios y no opcionales:
 * el Anexo exige que todo proyecto indique hasta cuándo se puede comentar y por
 * dónde. Dejarlos opcionales permitiría publicar un proyecto sin plazo, que es lo
 * que la regla prohíbe.
 */
interface ProyectoNorma {
  id: string
  tipo: TipoNorma
  titulo: string
  /** Fecha máxima para presentar comentarios, `AAAA-MM-DD`. */
  fechaMaximaComentarios: string
  /** Medio para enviarlos: `mailto:` de un buzón o URL de un formulario. */
  canalComentarios: string
  /** Texto visible del canal, sin el esquema del enlace. */
  canalComentariosTexto: string
  /** Enlace al documento del proyecto. */
  enlaceDocumento: string
}

/**
 * **Punto de integración con el sistema de publicación.**
 *
 * El listado y los proyectos viven en un estado de Nuxt, no en una constante local,
 * por una razón concreta: cuando exista el gestor de contenidos, su carga entra por
 * aquí —desde el servidor, y por tanto dentro del HTML que recibe la persona y los
 * rastreadores— y **el resto de la página no se toca**. El filtro, el orden, el
 * recuento y el indicador de vigencia ya trabajan sobre estos dos valores.
 *
 * Hoy están vacíos porque la Entidad no ha entregado ninguna norma. No se rellenan
 * con datos de ejemplo ni comentados: un ejemplo olvidado acaba publicado.
 */
const normas = useState<Norma[]>('normativa', () => [])
const proyectos = useState<ProyectoNorma[]>('normativa-proyectos', () => [])

/** Tipo por el que se filtra. «Todas» es el valor inicial, como en el panel del Anexo. */
const tipoSeleccionado = ref<FiltroTipo>(TODAS)

const hayNormas = computed(() => normas.value.length > 0)
const hayProyectos = computed(() => proyectos.value.length > 0)

/**
 * Recuento de normas por tipo, para el panel.
 *
 * Se calcula sobre el listado completo, no sobre lo filtrado: el panel del Anexo
 * muestra cuántas normas hay de cada tipo, y si el recuento cambiara al filtrar
 * dejaría de servir para elegir qué filtrar.
 *
 * Se usa un `Map` y no un objeto porque las claves están tipadas: añadir un tipo al
 * catálogo sin añadirlo aquí deja de compilar, que es justo lo que se quiere.
 */
const recuentoPorTipo = computed(() => {
  const recuento = new Map<TipoNorma, number>(
    TIPOS_NORMA.map((categoria): [TipoNorma, number] => [categoria.tipo, 0]),
  )
  normas.value.forEach((norma) => recuento.set(norma.tipo, (recuento.get(norma.tipo) ?? 0) + 1))
  return recuento
})

/**
 * Orden del listado: **de la norma más reciente a la más antigua**.
 *
 * Se ordena por **fecha de publicación** y no por la de expedición porque lo que
 * este listado publica es normativa ya publicada, y el Anexo pide además que la
 * norma aparezca en cuanto se expide: lo habitual es que las dos fechas coincidan,
 * pero cuando no coinciden, la más reciente para el ciudadano es la que acaba de
 * aparecer en la sede.
 *
 * Los desempates son necesarios, no adorno: con dos normas publicadas el mismo día
 * —cosa frecuente en un decreto y su resolución— un `sort` sin criterio completo
 * deja el orden en manos del motor, y el listado saldría distinto en cada
 * renderizado. Se desempata por fecha de expedición y, si tampoco la hubiera, por
 * el identificador, que es estable.
 *
 * Las fechas se comparan como texto: en formato ISO `AAAA-MM-DD`, el orden
 * alfabético y el cronológico coinciden, y así no se construye un `Date` por norma
 * ni se depende del huso horario del servidor.
 */
function ordenarPorPublicacion(lista: readonly Norma[]): Norma[] {
  return [...lista].sort((a, b) => {
    if (a.fechaPublicacion !== b.fechaPublicacion) {
      return a.fechaPublicacion < b.fechaPublicacion ? 1 : -1
    }
    if (a.fechaExpedicion !== b.fechaExpedicion) {
      return a.fechaExpedicion < b.fechaExpedicion ? 1 : -1
    }
    return a.id.localeCompare(b.id, 'es')
  })
}

/** El listado que se ve: primero se filtra por tipo, después se ordena por fecha. */
const normasOrdenadas = computed<Norma[]>(() => {
  const filtradas =
    tipoSeleccionado.value === TODAS
      ? normas.value
      : normas.value.filter((norma) => norma.tipo === tipoSeleccionado.value)
  return ordenarPorPublicacion(filtradas)
})

/** Nombre en plural de un tipo, para los textos que lo nombran. */
function pluralDe(tipo: TipoNorma): string {
  return TIPOS_NORMA.find((categoria) => categoria.tipo === tipo)?.plural ?? tipo
}

/**
 * Formato de fecha en castellano, con `timeZone: 'UTC'` **por necesidad**: las
 * fechas del modelo son días de calendario, no instantes. Sin fijar la zona, un
 * visitante al oeste de Greenwich vería la fecha corrida un día hacia atrás —una
 * norma publicada el 1 de enero aparecería como del 31 de diciembre—, y además el
 * HTML del servidor y el del navegador no coincidirían.
 */
const FORMATO_FECHA = new Intl.DateTimeFormat('es-CO', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
  timeZone: 'UTC',
})

/** Fecha legible. Si llegara un valor que no es una fecha, se muestra tal cual. */
function formatearFecha(iso: string): string {
  const fecha = new Date(`${iso}T00:00:00Z`)
  return Number.isNaN(fecha.getTime()) ? iso : FORMATO_FECHA.format(fecha)
}

/**
 * Resumen del listado, en una región viva: al cambiar el filtro, la lista cambia
 * sin que la página se recargue, y quien no ve la pantalla tiene que enterarse de
 * cuántas normas quedan. Es distinto del aviso de sección sin contenido, que se
 * renderiza en el servidor y no es una actualización.
 */
const resumenResultado = computed<string>(() => {
  const total = normas.value.length
  if (total === 0) {
    return 'Todavía no hay normativa publicada en esta sede.'
  }
  const mostradas = normasOrdenadas.value.length
  const deTipo =
    tipoSeleccionado.value === TODAS
      ? 'en todas las categorías'
      : `de ${pluralDe(tipoSeleccionado.value)}`
  return `Mostrando ${mostradas} de ${total} normas publicadas ${deTipo}.`
})

useHead({
  title: 'Normativa · Sede Electrónica',
  meta: [
    {
      name: 'description',
      content:
        'Normativa de la Alcaldía Distrital de Santa Marta: leyes, decretos, acuerdos, resoluciones, circulares y ordenanzas, con su tipo, fecha de expedición, fecha de publicación, epígrafe, enlace de consulta y estado de vigencia.',
    },
  ],
})
</script>

<template>
  <div class="container py-5">
    <!--
      Un solo `h1`: la cabecera del sitio usa encabezados de navegación, no de
      contenido. El `<main>` lo pone la disposición (`layouts/default.vue`), que es
      lo que garantiza el destino del enlace «Saltar al contenido principal» en
      todas las páginas.
    -->
    <h1>Normativa</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Normas expedidas por la Alcaldía Distrital de Santa Marta y normas que le
          aplican, con los datos que una sede electrónica está obligada a publicar de cada
          una.
        </p>
      </div>
    </div>

    <section class="mt-5" aria-labelledby="titulo-criterios">
      <h2 id="titulo-criterios" class="h3">Criterios de publicación</h2>

      <div class="row">
        <div class="col-lg-8">
          <p>
            El <strong>Anexo 2.1 — Guía de diseño gráfico para sedes electrónicas</strong>,
            de obligatorio cumplimiento por el artículo 14 del Decreto 2106 de 2019, fija
            en su página 8 las condiciones con las que se publica la normativa. Esta
            sección se construye con ellas:
          </p>

          <ul class="criterios">
            <li v-for="criterio in CRITERIOS_PUBLICACION" :key="criterio">
              {{ criterio }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="mt-5" aria-labelledby="titulo-normativa">
      <h2 id="titulo-normativa" class="h3">Visualización normativa</h2>

      <div class="row g-4">
        <!--
          El filtro va ANTES que el listado en el marcado y se coloca a la derecha con
          las utilidades de orden de la rejilla. Es lo contrario de lo que parece: así
          quien navega con teclado llega al filtro sin tener que atravesar un listado
          que puede tener cientos de normas, y en pantalla sigue viéndose donde el
          Anexo sitúa el panel —a la derecha—. En pantalla estrecha el panel queda
          arriba, que es donde se busca un control.
        -->
        <aside class="col-lg-4 order-lg-2" aria-labelledby="titulo-filtro">
          <div class="panel-filtro">
            <!-- «TIPO DE NORMA» es el encabezado con el que el Anexo rotula este panel. -->
            <h3 id="titulo-filtro" class="h6 text-uppercase mb-3">Tipo de norma</h3>

            <fieldset>
              <legend class="visually-hidden">Filtrar el listado por tipo de norma</legend>

              <div class="radio-seleccion-govco opcion-filtro">
                <input
                  id="filtro-todas"
                  v-model="tipoSeleccionado"
                  type="radio"
                  name="tipo-norma"
                  :value="TODAS"
                >
                <label for="filtro-todas">Todas las categorías<template v-if="hayNormas"> ({{ normas.length }})</template></label>
              </div>

              <!--
                El recuento sólo se muestra cuando hay normas: un «(0)» por tipo
                afirmaría que la Entidad no tiene leyes ni decretos, que es un dato
                que nadie ha entregado.
              -->
              <div
                v-for="categoria in TIPOS_NORMA"
                :key="categoria.tipo"
                class="radio-seleccion-govco opcion-filtro"
              >
                <input
                  :id="`filtro-${categoria.slug}`"
                  v-model="tipoSeleccionado"
                  type="radio"
                  name="tipo-norma"
                  :value="categoria.tipo"
                >
                <label :for="`filtro-${categoria.slug}`">{{ categoria.plural }}<template v-if="hayNormas"> ({{ recuentoPorTipo.get(categoria.tipo) ?? 0 }})</template></label>
              </div>
            </fieldset>

            <p v-if="hayNormas" class="nota-panel mb-0">
              El recuento es el de todas las normas publicadas en esta sede.
            </p>
            <p v-else class="nota-panel mb-0">
              El recuento de cada categoría aparecerá cuando la Entidad publique sus
              normas.
            </p>
          </div>
        </aside>

        <div class="col-lg-8 order-lg-1">
          <h3 class="h5 mb-3">Categorías de la sección</h3>

          <ul class="lista-categorias">
            <li v-for="categoria in CATEGORIAS_SECCION" :key="categoria.numero">
              <strong>{{ categoria.numero }} {{ categoria.etiqueta }}</strong> —
              {{ categoria.descripcion }}
            </li>
          </ul>

          <h3 class="h5 mt-4 mb-3">Normas publicadas</h3>

          <p class="orden mb-2">
            Ordenadas de la más reciente a la más antigua, por fecha de publicación.
          </p>

          <p class="resultado" role="status">{{ resumenResultado }}</p>

          <!--
            Tres estados excluyentes, como elementos hermanos: nunca `v-if` y `v-for`
            en el mismo elemento, para que no haya duda de si se recorre lo filtrado o
            se decide sobre el total.
          -->
          <div v-if="!hayNormas" class="estado-vacio">
            <p class="mb-2">
              <strong>Todavía no se publica normativa en esta sede.</strong>
            </p>
            <p class="mb-0">
              La Entidad aún no ha entregado sus normas y el gestor de contenidos que las
              va a publicar está en construcción. Cuando esté integrado, cada norma
              aparecerá aquí con su tipo, su fecha de expedición, su fecha de publicación,
              su epígrafe y el enlace para consultarla, ordenadas de la más reciente a la
              más antigua. No se publica ninguna norma de ejemplo: un documento inventado
              con número, fecha y enlace se lee como información oficial, y no lo es.
            </p>
          </div>

          <p v-else-if="normasOrdenadas.length === 0" class="estado-vacio">
            No hay normas publicadas del tipo seleccionado. Elija «Todas las categorías»
            para ver el listado completo.
          </p>

          <ol v-else class="listado-normas" role="list">
            <li v-for="norma in normasOrdenadas" :key="norma.id" class="norma">
              <div class="norma-cabecera">
                <h4 class="h5 mb-0">{{ norma.tipo }} {{ norma.numero }}</h4>
                <!--
                  La vigencia se dice con palabras y no sólo con color: el color por sí
                  solo no es información para quien no distingue verde y gris.
                -->
                <p class="indicador" :class="norma.enVigor ? 'indicador-vigente' : 'indicador-no-vigente'">
                  {{ norma.enVigor ? 'En vigor' : 'No vigente' }}
                </p>
              </div>

              <dl class="row datos-norma mb-0 mt-3">
                <dt class="col-sm-4">Tipo de norma</dt>
                <dd class="col-sm-8">{{ norma.tipo }}</dd>

                <dt class="col-sm-4">Fecha de expedición</dt>
                <dd class="col-sm-8">
                  <time :datetime="norma.fechaExpedicion">{{ formatearFecha(norma.fechaExpedicion) }}</time>
                </dd>

                <dt class="col-sm-4">Fecha de publicación</dt>
                <dd class="col-sm-8">
                  <time :datetime="norma.fechaPublicacion">{{ formatearFecha(norma.fechaPublicacion) }}</time>
                </dd>

                <dt class="col-sm-4">Epígrafe o descripción corta</dt>
                <dd class="col-sm-8">{{ norma.epigrafe }}</dd>

                <dt class="col-sm-4">Enlace para su consulta</dt>
                <dd class="col-sm-8">
                  <!--
                    El texto del enlace nombra la norma y su formato. «Consultar» a
                    secas, repetido en cada ficha, deja a quien navega por la lista de
                    enlaces sin saber cuál abre qué.
                  -->
                  <a :href="norma.enlace">
                    Consultar el {{ norma.tipo }} {{ norma.numero }} (formato {{ norma.formato }})
                  </a>
                </dd>
              </dl>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <section class="mt-5" aria-labelledby="titulo-proyectos">
      <h2 id="titulo-proyectos" class="h3">Proyectos de normativa</h2>

      <div class="row">
        <div class="col-lg-8">
          <p>
            Proyectos de norma que la Entidad somete a comentarios de la ciudadanía antes
            de expedirlos. Cada proyecto indica la <strong>fecha máxima para presentar
            comentarios</strong> y el <strong>medio electrónico</strong> para enviarlos, tal
            como exige el Anexo 2.1.
          </p>

          <div v-if="!hayProyectos" class="estado-vacio">
            <p class="mb-2">
              <strong>No hay proyectos de normativa publicados.</strong>
            </p>
            <p class="mb-0">
              La Entidad todavía no ha entregado proyectos para comentarios y el gestor de
              contenidos está en construcción. Cuando publique uno, su ficha mostrará hasta
              qué día se puede comentar y por dónde enviar los comentarios. Mientras no
              exista ninguno, cualquier consideración sobre la normativa distrital puede
              presentarse por los
              <NuxtLink to="/atencion">canales de atención de la Entidad</NuxtLink> o como
              una petición en la
              <NuxtLink to="/pqrsd">PQRSD</NuxtLink>.
            </p>
          </div>

          <ol v-else class="listado-normas" role="list">
            <li v-for="proyecto in proyectos" :key="proyecto.id" class="norma">
              <h3 class="h5 mb-0">{{ proyecto.titulo }}</h3>

              <dl class="row datos-norma mb-0 mt-3">
                <dt class="col-sm-4">Tipo de norma</dt>
                <dd class="col-sm-8">{{ proyecto.tipo }}</dd>

                <dt class="col-sm-4">Fecha máxima para presentar comentarios</dt>
                <dd class="col-sm-8">
                  <time :datetime="proyecto.fechaMaximaComentarios">{{ formatearFecha(proyecto.fechaMaximaComentarios) }}</time>
                </dd>

                <dt class="col-sm-4">Enviar comentarios</dt>
                <dd class="col-sm-8">
                  <a :href="proyecto.canalComentarios">{{ proyecto.canalComentariosTexto }}</a>
                </dd>

                <dt class="col-sm-4">Documento del proyecto</dt>
                <dd class="col-sm-8">
                  <a :href="proyecto.enlaceDocumento">
                    Consultar el proyecto de {{ proyecto.tipo }}
                  </a>
                </dd>
              </dl>
            </li>
          </ol>

          <p class="mt-4 mb-0">
            <!--
              Botón del propio Kit y no `.btn-outline-primary` de Bootstrap: el azul
              por defecto de Bootstrap (#0D6EFD) da 3,51:1 sobre blanco y no alcanza el
              4,5:1 que exige WCAG 2.1 AA. El cobalto del Kit (#0943B5) da 8,46:1.
            -->
            <NuxtLink class="btn-govco outline-btn-govco" to="/">Volver a la portada</NuxtLink>
          </p>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/*
  Nota de contraste, medida y no elegida a ojo. Los pares que usa esta página:

    Matterhorn #4C4C4C sobre blanco ................ 8,59:1  ✓
    Matterhorn #4C4C4C sobre blanco humo #F4F4F4 ... 7,81:1  ✓
    Verde del Kit #158361 sobre blanco ............. 4,72:1  ✓
    Cobalto #0943B5 sobre blanco ................... 8,46:1  ✓

  El verde se usa como texto sobre blanco y nunca sobre el gris del aviso: sobre
  #F4F4F4 bajaría a 4,29:1 y no pasaría AA.

  El color de la Entidad (#00ADE7) no aparece aquí, y no es un olvido: el Anexo 2
  prohíbe usar el color asignado a la autoridad en «botones, texto, campos de
  formulario, fondos ni etiquetas». Los avisos y el panel van con los neutros del
  Kit, que es lo que esa regla pide.
*/

.criterios {
  padding-left: 1.25rem;
}

.criterios li {
  margin-bottom: 0.5rem;
}

.lista-categorias {
  padding-left: 1.25rem;
}

.lista-categorias li {
  margin-bottom: 0.75rem;
}

/*
  El panel del filtro. Lleva borde y no sólo fondo para que se distinga como bloque
  aunque el modo de alto contraste del sitio neutralice los fondos.
*/
.panel-filtro {
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 0.5rem;
  padding: 1rem 1.25rem;
}

/*
  La opción del filtro, envuelta en una fila flex. El Kit flota el botón del radio
  (`float: left`) y mide su margen en `em`; en una caja flex la flotación se ignora y
  el texto no se monta encima del botón cuando la etiqueta ocupa dos líneas.
*/
.opcion-filtro {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.nota-panel {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.875rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--govcolor-silver, #b9b9b9);
}

/*
  Aviso de estado vacío.

  **No lleva `role="status"`**, a diferencia del aviso de sección en preparación: esto
  es contenido renderizado en el servidor y presente desde el primer instante, no algo
  que aparezca después de una acción. Una región viva aquí haría que el lector de
  pantalla lo anunciara dos veces.
*/
.estado-vacio {
  margin-top: 1rem;
  padding: 1rem 1.25rem;
  border-left: 4px solid var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  Enlaces que esta página pinta dentro de sus bloques. Bootstrap los colorea con su
  azul por defecto (#0D6EFD): sobre blanco da 4,50:1 —exactamente en el límite— y
  sobre el gris del aviso cae a 4,09:1, por debajo del 4,5:1 de WCAG 2.1 AA, que es
  lo que la auditoría marcó. Se usa el cobalto del Kit (#0943B5), que es el color de
  enlace de la propia guía y da 8,46:1 sobre blanco y 7,69:1 sobre el gris.

  Es una corrección de esta página, no del sitio: el resto de las páginas sigue con
  el azul de Bootstrap, y arreglarlo donde corresponde —`assets/css/sitio.css`—
  queda fuera de esta tarea.
*/
.norma a,
.estado-vacio a {
  color: var(--govcolor-cobalt, #0943b5);
}

.orden,
.resultado {
  font-size: 0.9375rem;
}

.resultado {
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  El listado. Es una lista ordenada porque el orden es un requisito, no una
  casualidad; se retira su numeración para no confundir la posición en la lista con
  el número de la norma, que va en la cabecera de cada ficha. `role="list"` conserva
  el anuncio de lista en los navegadores que la retiran al quitar los marcadores.
*/
.listado-normas {
  list-style: none;
  padding-left: 0;
  margin: 0;
}

.norma {
  border: 1px solid var(--govcolor-silver-dis, #c8c8c8);
  border-radius: 0.5rem;
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
}

.norma-cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.indicador {
  margin: 0;
  padding: 0.125rem 0.5rem;
  border: 1px solid currentcolor;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.indicador-vigente {
  color: var(--govcolor-green, #158361);
}

.indicador-no-vigente {
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  Reflujo a 320 px (WCAG 1.4.10). `min-width: 0` hace falta en las celdas porque el
  padre es una fila de la rejilla —un contenedor flex— y un elemento flex no baja de
  su `min-width: auto`, que es el ancho de la palabra más larga: un epígrafe con una
  palabra larga ensancharía la columna. `overflow-wrap: anywhere` parte lo que no
  cabe —una dirección de correo, una palabra sin espacios— en lugar de sacarlo de la
  pantalla, porque ese texto es contenido substantivo y no se puede recortar.
*/
.datos-norma dt,
.datos-norma dd {
  min-width: 0;
}

.datos-norma dd {
  overflow-wrap: anywhere;
}
</style>

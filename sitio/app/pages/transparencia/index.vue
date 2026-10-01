<script setup lang="ts">
/**
 * Transparencia y acceso a la información pública — `/transparencia`.
 *
 * **Por qué este archivo se llama `index.vue` y no `transparencia.vue`.** Nuxt
 * anida `pages/transparencia.vue` y `pages/transparencia/[slug].vue` como padre
 * e hijo, y un padre sin `<NuxtPage />` **descarta al hijo en silencio**: con el
 * índice en `transparencia.vue`, las nueve direcciones de detalle respondían
 * 200 sirviendo el índice —una página equivocada con un código de éxito, que es
 * el peor de los fallos posibles en una sede electrónica— y no había ni un
 * error en la consola que lo delatara. Con el índice dentro del directorio, las
 * dos páginas son hermanas, la dirección `/transparencia` no cambia y la
 * estructura es la misma que ya usa `pages/tramites/`.
 *
 * **Qué exige esta página.** Es el primero de los tres menús mínimos
 * obligatorios del Anexo 2 §4.1.2 de la Resolución MinTIC 1519 de 2020, y la
 * Ley 1712 de 2014 obliga a publicar de manera proactiva el conjunto mínimo de
 * información que ese anexo organiza en **nueve categorías**. Esta página
 * publica la estructura entera de las nueve: cada una con su número, su
 * encabezado, qué publica, la norma que la ordena, sus apartados, cuántos
 * documentos tiene y —cuando no los tiene— por qué. Las nueve son navegables
 * por enlace directo (`#categoria-contratacion` y compañía) porque el ciudadano
 * y el auditor llegan por el enlace de una categoría concreta, no por el
 * principio de la página.
 *
 * **Por qué las nueve y no las ocho que había.** El sitio de la Entidad publica
 * ocho. La novena —Información específica de la entidad— no existe allí en
 * ninguna parte, y ése es el hueco de fondo: quien audita el cumplimiento de la
 * Ley 1712 cuenta categorías publicadas sobre nueve. Aquí está construida.
 *
 * **Qué se publica y qué no, y quién lo decide.** La estructura es arquitectura
 * de información y está completa. El contenido —los documentos— sale del
 * rastreo del sitio de la Entidad, con su procedencia a la vista, y **no se
 * completa con nada**: ni una descripción, ni un resumen, ni una cifra, ni una
 * fecha que la fuente no declare. El titular del proyecto lo exigió y la razón
 * es la de siempre en una sede electrónica: un dato inventado aquí no es un
 * hueco en una maqueta, es información oficial falsa sobre la que el ciudadano
 * decide. Todo lo que no está en la fuente se declara ausente.
 *
 * **Los cuatro estados de una categoría se distinguen.** `publicada` es que la
 * Entidad publica lo que la norma pide; `parcial` es que falta algo, y el
 * `motivo` dice qué; `declarada` es que la categoría está construida y su
 * fuente identificada pero no hay nada que publicar todavía; y `ausente` es que
 * la Entidad no la publica en absoluto. Enseñar los cuatro como «en
 * preparación» sería decir que están igual de vacías, y no lo están: hay
 * categorías con 241 documentos y categorías con ninguno.
 *
 * **El buscador es de la sección y no de un tercero.** Lo exige FUN-017 del
 * expediente: la búsqueda de Transparencia ocurre dentro de la propia sección.
 * Es lo contrario de delegar en un buscador comercial, que es lo que FUN-001
 * prohíbe con severidad crítica. El recuento de resultados va a la vista, como
 * el Anexo manda en los mecanismos de acceso, y va en una región viva para que
 * el lector de pantalla lo anuncie.
 *
 * **El `<main>` no se declara aquí.** Lo pone la disposición
 * (`layouts/default.vue`), que es lo que garantiza que el enlace «Saltar al
 * contenido principal» funcione en todas las páginas y no sólo en las que se
 * acordaron de ponerlo. Y hay **un solo `<h1>`**: el que nombra la página.
 */
import {
  CATEGORIAS,
  FUENTE_ORIGEN,
  NORMA_DEROGADA,
  NORMA_VIGENTE,
  ROTULO_ESTADO,
  buscarEnSeccion,
  documentosDe,
  totalDe,
  type CoincidenciaTransparencia,
  type DocumentoTransparencia,
  type EstadoCategoria,
} from '~/types/transparencia'

useHead({
  title: 'Transparencia y acceso a la información pública · Sede Electrónica',
  meta: [
    {
      name: 'description',
      content:
        'Las nueve categorías de información mínima obligatoria de la Ley 1712 de 2014 publicadas por la Alcaldía Distrital de Santa Marta, con la fecha de publicación y la procedencia de cada documento.',
    },
  ],
})

// ---------------------------------------------------------------------------
// Estado del buscador
// ---------------------------------------------------------------------------

/**
 * El término **aplicado**, no el que se está escribiendo: la búsqueda se dispara
 * al enviar el formulario del buscador, que es como funciona `BuscadorGovco`, y
 * así el recuento no cambia a cada tecla —ni el listado se reordena mientras
 * alguien escribe, que es lo que hace que un buscador parezca roto—.
 */
const termino = ref('')

/**
 * Cambiar esta clave vuelve a montar el buscador. Hace falta porque
 * `BuscadorGovco` guarda su propio texto: al limpiar desde fuera, el campo se
 * quedaría con lo escrito y contradiría al recuento que hay debajo.
 */
const claveBuscador = ref(0)

function alBuscar(nuevo: string): void {
  termino.value = nuevo
}

function limpiarBusqueda(): void {
  termino.value = ''
  claveBuscador.value += 1
}

// ---------------------------------------------------------------------------
// Derivados
// ---------------------------------------------------------------------------

/** Si hay una búsqueda aplicada. Sin ella no se dibuja ningún resultado. */
const hayBusqueda = computed<boolean>(() => termino.value !== '')

/**
 * Las coincidencias del término en **toda** la sección, con la categoría y el
 * apartado de cada una: sin ese contexto, «Presupuesto 2024» no dice de qué
 * parte del índice sale, y quien busca un documento concreto lo necesita para
 * saber si es el suyo.
 */
const coincidencias = computed<CoincidenciaTransparencia[]>(() => {
  if (!hayBusqueda.value) return []
  return buscarEnSeccion(termino.value)
})

/** Cuántos documentos publica hoy la sección entera. */
const totalDocumentos = computed<number>(() =>
  CATEGORIAS.reduce((suma, categoria) => suma + totalDe(categoria), 0),
)

/** Cuántas de las nueve categorías tienen algún documento publicado. */
const categoriasConDocumentos = computed<number>(
  () => CATEGORIAS.filter((categoria) => totalDe(categoria) > 0).length,
)

/**
 * Si algún documento del inventario declara su fecha de publicación.
 *
 * No se escribe a mano: se calcula sobre los datos. El día que la Entidad
 * declare una fecha, este aviso desaparece solo y el listado de detalle empieza
 * a ordenarse del más reciente al más antiguo, que es lo que el Anexo 2
 * §4.1.2.1 manda. Un aviso escrito a mano se quedaría mintiendo.
 */
const hayAlgunaFecha = computed<boolean>(() =>
  CATEGORIAS.some((categoria) =>
    documentosDe(categoria).some((documento) => documento.fechaPublicacion !== undefined),
  ),
)

/** El recuento de la búsqueda, en una frase, para la región viva. */
const resumenBusqueda = computed<string>(() => {
  const total = coincidencias.value.length
  if (total === 0) return `Ningún documento de esta sección coincide con «${termino.value}».`
  return `${total} ${total === 1 ? 'documento coincide' : 'documentos coinciden'} con «${termino.value}».`
})

// ---------------------------------------------------------------------------
// Ayudas de presentación
// ---------------------------------------------------------------------------

/**
 * La línea de fecha de un documento.
 *
 * Es una función y no una expresión del marcado porque la regla es una sola y
 * tiene que cumplirse en los dos sitios donde se dibuja un documento: si la
 * fuente declara la fecha se enseña, y si no la declara **se dice que no
 * consta**. El numeral 2.4.1.e de la Resolución 1519 de 2020 obliga a que todo
 * documento indique la fecha de su publicación, así que su ausencia es un
 * incumplimiento que hay que poder ver, no un hueco que se tapa.
 */
function textoFecha(documento: DocumentoTransparencia): string {
  if (documento.fechaPublicacion === undefined) return 'Fecha de publicación: no consta en la fuente'
  return `Fecha de publicación: ${documento.fechaPublicacion}`
}

/** La clase de la insignia de estado, para que cada estado tenga su color. */
function claseEstado(estado: EstadoCategoria): string {
  return `insignia-${estado}`
}
</script>

<template>
  <div class="container py-5 seccion-transparencia">
    <h1>Transparencia y acceso a la información pública</h1>

    <p class="lead">
      La información que la Alcaldía Distrital de Santa Marta publica por obligación legal,
      organizada en las nueve categorías que fija la norma, con la procedencia y la fecha de
      publicación de cada documento.
    </p>

    <!--
      El resumen de la sección en una línea. Es lo primero que necesita saber
      quien llega: cuántas categorías hay, cuántas tienen contenido y cuántos
      documentos son.
    -->
    <p class="resumen-seccion">
      {{ CATEGORIAS.length }} categorías ·
      {{ categoriasConDocumentos }} con documentos publicados ·
      {{ totalDocumentos }} documentos en total
    </p>

    <!-- ===================================================================== -->
    <!-- Marco normativo                                                       -->
    <!-- ===================================================================== -->

    <section class="bloque" aria-labelledby="marco-normativo">
      <h2 id="marco-normativo" class="h4">Marco normativo</h2>

      <!--
        Se cita la norma VIGENTE y se dice cuál sustituye. El sitio de la
        Entidad todavía cita la Resolución 3564 de 2015, derogada por el
        artículo 8 de la 1519 de 2020: una sede electrónica no puede remitir a
        una norma derogada, pero tampoco puede callar cuál era, porque quien
        llegue con la referencia antigua creería que es la sección la que se
        equivoca.
      -->
      <ul class="lista-normas">
        <li>
          <strong>Ley 1712 de 2014</strong> — por medio de la cual se crea la Ley de
          Transparencia y del Derecho de Acceso a la Información Pública Nacional y se dictan
          otras disposiciones. Es la ley que obliga a publicar de manera proactiva el conjunto
          mínimo de información que esta sección contiene.
        </li>
        <li>
          <strong>{{ NORMA_VIGENTE }}</strong> — por la cual se definen los estándares y
          directrices para publicar la información señalada en la Ley 1712 de 2014 y se definen
          los requisitos en materia de acceso a la información pública, accesibilidad web,
          seguridad digital y datos abiertos. <strong>Es la norma vigente.</strong>
        </li>
        <li>
          <strong>{{ NORMA_DEROGADA }}</strong> — <strong>derogada</strong> por el artículo 8
          de la Resolución 1519 de 2020. Se enuncia aquí porque el sitio de la Entidad todavía
          la cita, y porque quien llegue con esa referencia necesita saber qué la sustituyó.
        </li>
      </ul>
    </section>

    <!-- ===================================================================== -->
    <!-- Reglas de publicación                                                 -->
    <!-- ===================================================================== -->

    <section class="bloque" aria-labelledby="reglas-publicacion">
      <h2 id="reglas-publicacion" class="h4">Cómo se publica la información de esta sección</h2>

      <ul class="lista-reglas">
        <li>
          <strong>Fecha de publicación.</strong> Todo documento indica la fecha de su
          publicación, como exige el numeral 2.4.1.e de la Resolución 1519 de 2020. Cuando la
          fuente no la declara, la ficha del documento lo dice con esas palabras: la Sede no
          la estima ni la deduce.
        </li>
        <li>
          <strong>Orden.</strong> Los documentos de cada categoría se listan del más reciente
          al más antiguo, como exige el Anexo 2 §4.1.2.1. Mientras ningún documento declare su
          fecha no hay cronología que sostener, y el listado conserva el orden de la fuente en
          vez de fingir uno.
        </li>
        <li>
          <strong>Procedencia.</strong> Cada documento dice de dónde salió: la fuente, el día
          del rastreo y el apartado del índice anterior del que cuelga. Un dato del que no se
          dice de dónde sale parece un dato de la Entidad, y no lo es.
        </li>
        <li>
          <strong>Buscador propio.</strong> La búsqueda ocurre dentro de esta sección y no en un
          buscador externo, y el número de resultados está siempre a la vista.
        </li>
        <li>
          <strong>Sin duplicidad.</strong> Ningún documento se publica en dos categorías: uno
          que estuviera en dos sitios obligaría al ciudadano a adivinar cuál de los dos está
          actualizado.
        </li>
      </ul>

      <!--
        El aviso de las fechas es un cómputo, no una afirmación escrita a mano:
        desaparece solo cuando la Entidad declare la primera fecha.
      -->
      <p v-if="!hayAlgunaFecha" class="nota-estado">
        Ninguno de los {{ totalDocumentos }} documentos publicados hoy en esta sección declara
        su fecha de publicación. La Sede no la inventa: hasta que la Entidad la declare, la
        sección no puede ordenarse del más reciente al más antiguo.
      </p>
    </section>

    <!-- ===================================================================== -->
    <!-- Buscador de la sección                                                -->
    <!-- ===================================================================== -->

    <section class="bloque" aria-labelledby="buscador-seccion">
      <h2 id="buscador-seccion" class="h4">Buscar en la sección</h2>

      <p>
        Busca por el nombre del documento o por el apartado al que pertenece. Se busca dentro
        de esta sección y en todas sus categorías a la vez.
      </p>

      <BuscadorGovco
        :key="claveBuscador"
        etiqueta="Buscar documentos en la sección de transparencia"
        placeholder="Buscar en transparencia"
        texto-boton="Buscar en la sección"
        :valor-inicial="termino"
        @buscar="alBuscar"
        @limpiar="limpiarBusqueda"
      />

      <!--
        El recuento va en su propia región viva y el botón de limpiar fuera de
        ella: dentro, cada recuento nuevo volvería a anunciar el botón.
      -->
      <div v-if="hayBusqueda" class="barra-resultados">
        <p class="recuento-resultados mb-0" role="status">{{ resumenBusqueda }}</p>
        <button
          type="button"
          class="btn btn-govco outline-btn-govco boton-seccion"
          @click="limpiarBusqueda"
        >
          Limpiar la búsqueda
        </button>
      </div>

      <!-- Coincidencias. -->
      <ul v-if="hayBusqueda && coincidencias.length > 0" class="lista-documentos" role="list">
        <li
          v-for="(coincidencia, indice) in coincidencias"
          :key="`coincidencia-${indice}`"
          class="documento"
        >
          <a class="documento-titulo" :href="coincidencia.documento.url">
            {{ coincidencia.documento.titulo }}
          </a>
          <p class="documento-meta mb-0">
            <span class="documento-contexto">
              Categoría {{ coincidencia.categoria.numero }}. {{ coincidencia.categoria.nombre }} ·
              {{ coincidencia.apartado.titulo }}
            </span>
            <span class="documento-fecha">{{ textoFecha(coincidencia.documento) }}</span>
          </p>
        </li>
      </ul>

      <!--
        Búsqueda sin resultados. Se dice con otras palabras que cuando la
        categoría está vacía, porque son dos hechos distintos: uno es del filtro
        y el otro es de la Entidad.
      -->
      <p v-else-if="hayBusqueda" class="estado-vacio">
        Ningún documento de esta sección coincide con «{{ termino }}». Pruebe con otras
        palabras o recorra las categorías de abajo.
      </p>
    </section>

    <!-- ===================================================================== -->
    <!-- Índice de las nueve categorías                                        -->
    <!-- ===================================================================== -->

    <nav class="bloque" aria-labelledby="indice-categorias">
      <h2 id="indice-categorias" class="h4">Las nueve categorías</h2>

      <p>
        La norma ordena la información mínima obligatoria en nueve categorías. Éste es el
        índice: cada entrada lleva a su categoría más abajo.
      </p>

      <ol class="indice-categorias">
        <li v-for="categoria in CATEGORIAS" :key="`indice-${categoria.slug}`">
          <a :href="`#categoria-${categoria.slug}`">
            {{ categoria.numero }}. {{ categoria.nombre }}
          </a>
          <span class="indice-cuenta">
            {{ totalDe(categoria) }}
            {{ totalDe(categoria) === 1 ? 'documento' : 'documentos' }}
          </span>
        </li>
      </ol>
    </nav>

    <!-- ===================================================================== -->
    <!-- Las nueve categorías                                                  -->
    <!-- ===================================================================== -->

    <section
      v-for="categoria in CATEGORIAS"
      :id="`categoria-${categoria.slug}`"
      :key="categoria.slug"
      class="categoria"
      :aria-labelledby="`titulo-${categoria.slug}`"
    >
      <h2 :id="`titulo-${categoria.slug}`" class="h3">
        <span class="categoria-numero">{{ categoria.numero }}.</span> {{ categoria.nombre }}
      </h2>

      <p class="categoria-publica">{{ categoria.publica }}</p>

      <p class="categoria-base">
        <span class="etiqueta-base">Base normativa</span> {{ categoria.base }}
      </p>

      <!--
        El estado, cuando no es el normal. Una categoría publicada no lleva
        insignia: marcar las nueve haría que la marca no distinguiera nada, que
        es exactamente lo contrario de aquello para lo que sirve.
      -->
      <p v-if="categoria.estado !== 'publicada'" class="categoria-estado">
        <span class="insignia" :class="claseEstado(categoria.estado)">
          {{ ROTULO_ESTADO[categoria.estado] }}
        </span>
        <span class="categoria-motivo">{{ categoria.motivo }}</span>
      </p>

      <!--
        Los apartados. Se enuncian por su nombre y **sin la numeración del
        origen**: la del sitio anterior está rota —salta ítems, repite el 3.2
        tres veces y anida 4.7 dentro de 4.6— y reproducirla sería copiar el
        defecto. El número que sí se conserva es el de la categoría, que es el
        que fija la norma.
      -->
      <div v-if="categoria.apartados.length > 0" class="apartados-categoria">
        <h3 class="h6">Apartados</h3>
        <ul class="lista-apartados" role="list">
          <li v-for="apartado in categoria.apartados" :key="apartado.titulo" class="apartado">
            <span class="apartado-titulo">{{ apartado.titulo }}</span>
            <span v-if="apartado.faltante !== undefined" class="apartado-faltante">
              Sin contenido publicado
            </span>
          </li>
        </ul>
      </div>

      <!--
        Destino dentro de esta misma Sede, cuando ya existe la página que cubre
        la categoría. No sustituye a nada ni rellena ningún hueco: es un enlace
        a una página construida, y va rotulado como tal.
      -->
      <p v-if="categoria.rutaSede !== undefined" class="categoria-enlace-sede">
        <NuxtLink :to="categoria.rutaSede">{{ categoria.rutaSedeTexto }}</NuxtLink>
      </p>

      <!--
        Qué hacer con la categoría. Si tiene documentos, se enlaza su página de
        detalle —donde está el inventario entero, que en esta página no cabe ni
        debe caber—; si no los tiene, se declara el vacío en su sitio.
      -->
      <p v-if="totalDe(categoria) > 0" class="categoria-enlace-detalle">
        <NuxtLink
          class="btn btn-govco outline-btn-govco boton-seccion"
          :to="`/transparencia/${categoria.slug}`"
        >
          Ver los {{ totalDe(categoria) }}
          {{ totalDe(categoria) === 1 ? 'documento' : 'documentos' }} de esta categoría
        </NuxtLink>
      </p>

      <div v-else class="estado-vacio estado-vacio-publicacion">
        <p class="estado-vacio-titulo mb-2">Esta categoría no tiene documentos publicados.</p>
        <p class="mb-0">
          Está construida y sus apartados están declarados, pero la Entidad no tiene hoy
          ningún documento publicado en ella. La Sede no la rellena con contenido de ejemplo:
          un documento inventado en una sede electrónica es información oficial falsa.
        </p>
      </div>

      <!--
        El portal federado, cuando la categoría depende de él. Se declara cómo
        hay que consultarlo —filtrando por entidad propietaria— porque la
        federación es responsabilidad de la Entidad y el ciudadano necesita
        saber dónde mirar mientras no esté hecha.
      -->
      <div v-if="categoria.federada !== undefined" class="fuente-federada">
        <h3 class="h6">{{ categoria.federada.portal }}</h3>
        <p class="mb-2">{{ categoria.federada.instruccion }}</p>
        <a class="btn btn-govco outline-btn-govco boton-seccion" :href="categoria.federada.url">
          Ir al portal de datos abiertos del Estado
        </a>
      </div>

      <!--
        El defecto del origen, cuando lo hay. Va aparte del estado y rotulado
        como nota sobre la fuente: no es una afirmación de la Entidad ni una
        excusa de la Sede, es lo que se encontró en el índice anterior y lo que
        quien audite necesita saber para no dar por bueno lo que no lo es.
      -->
      <details v-if="categoria.notaOrigen !== undefined" class="nota-origen">
        <summary>Nota sobre el origen de esta categoría</summary>
        <p class="mb-0">{{ categoria.notaOrigen }}</p>
      </details>

      <!-- La procedencia, a la vista y en todas las categorías. -->
      <p class="categoria-procedencia mb-0">
        Procedencia del inventario:
        <a :href="FUENTE_ORIGEN.url">{{ FUENTE_ORIGEN.nombre }}</a>, rastreado el
        {{ FUENTE_ORIGEN.rastreado }}.
      </p>
    </section>
  </div>
</template>

<style scoped>
/*
  El color de los enlaces y el foco de los campos los fija la hoja del sitio
  (`app/assets/css/sitio.css`), no esta página. Aquí sólo va lo propio de esta
  vista, y los tokens del Kit se usan con su valor por defecto para que la
  página siga leyéndose si el Kit no carga.
*/

.resumen-seccion {
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.bloque {
  margin-top: 2rem;
}

.lista-normas,
.lista-reglas {
  padding-left: 1.25rem;
}

.lista-normas li,
.lista-reglas li {
  margin-bottom: 0.75rem;
}

/*
  Aviso de estado —el de las fechas y los motivos— con el filete neutro, no con
  el amarillo institucional: el amarillo se reserva para «aquí no hay nada
  publicado», que es una afirmación distinta y más grave que «falta un dato».
*/
.nota-estado {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-left: 0.25rem solid var(--govcolor-silver, #b9b9b9);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.barra-resultados {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 1rem;
  margin-top: 1rem;
}

.recuento-resultados {
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.boton-seccion {
  min-height: 2.75rem;
}

/* --- Documentos ---------------------------------------------------------- */

.lista-documentos {
  margin: 1rem 0 0;
  padding: 0;
  list-style: none;
}

.documento {
  padding: 0.875rem 0;
  border-top: 1px solid var(--govcolor-silver, #b9b9b9);
}

.documento:last-child {
  border-bottom: 1px solid var(--govcolor-silver, #b9b9b9);
}

.documento-titulo {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  font-weight: 700;
  /* Las direcciones del origen traen nombres de archivo larguísimos: sin esto
     desbordan la columna a 320 px. */
  overflow-wrap: anywhere;
}

.documento-meta {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.documento-contexto {
  overflow-wrap: anywhere;
}

.documento-fecha {
  font-weight: 700;
}

/* --- Índice de categorías ------------------------------------------------ */

.indice-categorias {
  padding-left: 1.25rem;
}

.indice-categorias li {
  margin-bottom: 0.5rem;
}

.indice-cuenta {
  margin-left: 0.5rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/* --- Categorías ---------------------------------------------------------- */

.categoria {
  margin-top: 2.5rem;
  padding-top: 1.5rem;
  border-top: 0.25rem solid var(--govcolor-cobalt, #0943b5);
  /* El ancla no debe quedar pegada al borde superior al saltar a ella. */
  scroll-margin-top: 1rem;
}

.categoria-numero {
  color: var(--govcolor-cobalt, #0943b5);
}

.categoria-publica {
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.categoria-base {
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.etiqueta-base {
  font-weight: 700;
}

.categoria-estado {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.5rem;
}

.insignia {
  flex: none;
  padding: 0.125rem 0.625rem;
  border: 1px solid currentColor;
  border-radius: 1rem;
  font-size: 0.9375rem;
  font-weight: 700;
}

/*
  El color no va solo: cada insignia lleva su rótulo escrito, así que el estado
  se entiende sin distinguir colores. Es el criterio 1.4.1 de WCAG, que no
  admite el color como único portador de información.
*/
/*
  El estado «parcial» usa el par del aviso institucional del sitio: fondo
  `vis-vis` y texto Matterhorn, la misma combinación que usa el aviso de sección
  en preparación y que da 7,6:1 —por encima del 4,5:1 de WCAG 2.1 AA—. El
  dorado del Kit sobre blanco da 4,14:1 y no pasa: no se usa como color de
  texto, sólo en el filete.
*/
.insignia-parcial {
  background-color: var(--govcolor-vis-vis, #fee697);
  border-color: var(--govcolor-golden-brown, #9d7700);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.insignia-declarada {
  color: var(--govcolor-cobalt, #0943b5);
}

.insignia-ausente {
  color: var(--govcolor-red, #a80521);
}

.categoria-motivo {
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.apartados-categoria {
  margin-top: 1rem;
}

.lista-apartados {
  margin: 0;
  padding: 0;
  list-style: none;
}

.apartado {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: baseline;
  padding: 0.375rem 0;
  border-bottom: 1px solid var(--govcolor-white-smoke, #f4f4f4);
}

.apartado-titulo {
  overflow-wrap: anywhere;
}

.apartado-faltante {
  flex: none;
  padding: 0 0.5rem;
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 1rem;
  font-size: 0.875rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.categoria-enlace-sede,
.categoria-enlace-detalle {
  margin-top: 1rem;
}

/* --- Estados vacíos ------------------------------------------------------ */

/*
  El amarillo institucional y el filete dorado, igual que el aviso de sección en
  preparación del resto del sitio, para que «aquí no hay nada publicado» se lea
  igual en todas partes. El texto va en Matterhorn, que sobre ese amarillo da
  7,6:1 y pasa el 4,5:1 que exige WCAG 2.1 AA.
*/
.estado-vacio {
  margin-top: 1rem;
  padding: 1rem 1.25rem;
  border-left: 0.25rem solid var(--govcolor-silver, #b9b9b9);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.estado-vacio-publicacion {
  border-left-color: var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-vis-vis, #fee697);
}

.estado-vacio-titulo {
  font-weight: 700;
}

/* --- Fuente federada y nota de origen ------------------------------------ */

.fuente-federada {
  margin-top: 1rem;
  padding: 1rem 1.25rem;
  border: 1px solid var(--govcolor-cobalt, #0943b5);
  border-radius: 0.25rem;
}

.nota-origen {
  margin-top: 1rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.nota-origen summary {
  /* CAG-23: el resumen es un control y tiene que medir 44 px de alto. */
  display: flex;
  align-items: center;
  min-height: 2.75rem;
  font-weight: 700;
  cursor: pointer;
}

.categoria-procedencia {
  margin-top: 1rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

@media (max-width: 47.9375rem) {
  /* CAG-23: en móvil los enlaces de navegación miden al menos 44 px de alto. */
  .indice-categorias a {
    display: inline-flex;
    align-items: center;
    min-height: 2.75rem;
  }

  .categoria-procedencia a {
    overflow-wrap: anywhere;
  }
}
</style>

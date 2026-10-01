<script setup lang="ts">
/**
 * Detalle de una categoría de transparencia — `/transparencia/{slug}`.
 *
 * **Por qué esta página existe.** La categoría 4 —Planeación, presupuesto e
 * informes— tiene ella sola 241 documentos, y las nueve juntas suman más de
 * trescientos. Publicar el inventario entero dentro de la página de la sección
 * convertiría `/transparencia` en algo que nadie recorre y que ningún ciudadano
 * lee: el índice dejaría de servir para orientarse. Esta página es la que
 * sostiene el inventario, una categoría por dirección, y hace tres cosas que la
 * sección no puede hacer en conjunto:
 *
 *  1. **Buscar dentro de la categoría.** Es lo que el Anexo pide para
 *     Normativa y para la sección: un buscador propio. En `/transparencia/
 *     normativa` está el de la normativa, y en las demás categorías el suyo,
 *     con el mismo comportamiento y sin código repetido.
 *  2. **Publicar la procedencia documento a documento.** Cada ficha dice de qué
 *     apartado del índice anterior sale, de qué fuente y de qué día, además de
 *     su fecha de publicación o la constancia de que no consta.
 *  3. **No duplicar.** `/transparencia` enuncia las categorías y sus apartados
 *     —nombres y recuentos— y no lista ni un solo documento; esta página lista
 *     los documentos y no repite el marco normativo entero de la sección. Es lo
 *     que exige FUN-020: ningún documento en dos sitios, para que el ciudadano
 *     no tenga que adivinar cuál de los dos está actualizado.
 *
 * **El orden es el de la norma, y se cumple o se declara.** El Anexo 2
 * §4.1.2.1 manda listar del más reciente al más antiguo. Se aplica
 * `ordenarPorFecha`, que ordena de verdad en cuanto haya fechas que ordenar;
 * hoy no hay ninguna —el rastreo no encontró una sola fecha de publicación
 * declarada en el sitio de la Entidad—, y por eso la página lo dice en vez de
 * presentar como cronología lo que sólo es el orden en que la fuente los
 * publicaba. Una fecha inventada aquí sería un dato oficial falso; una
 * cronología inventada, también.
 *
 * **Un apartado vacío no desaparece.** Los dos que hoy están sin contenido
 * —Actos administrativos y Normatividad de trámites— se dibujan con su
 * encabezado y su explicación: quitarlos de la página haría que la categoría
 * pareciera completa. En el índice anterior los dos llevan a un «#», que es
 * precisamente lo que aquí no se copia.
 *
 * **El `<main>` no se declara aquí** —lo pone la disposición— y hay **un solo
 * `<h1>`**, el que nombra la categoría.
 */
import {
  CATEGORIAS,
  FUENTE_ORIGEN,
  ROTULO_ESTADO,
  buscarEnCategoria,
  ordenarPorFecha,
  categoriaPorSlug,
  hayFechaDeclarada,
  totalDe,
  type CategoriaTransparencia,
  type CoincidenciaTransparencia,
  type DocumentoTransparencia,
  type EstadoCategoria,
  type GrupoTransparencia,
} from '~/types/transparencia'

// ---------------------------------------------------------------------------
// La categoría de esta dirección
// ---------------------------------------------------------------------------

/**
 * El segmento de ruta. `useRoute().params.slug` puede llegar como cadena o como
 * arreglo —una dirección con la misma clave repetida—, así que se comprueba y
 * no se fuerza con un `as`: forzar el tipo aquí escondería justamente el caso
 * que hay que atender.
 */
const parametro = useRoute().params.slug
const slug = typeof parametro === 'string' ? parametro : ''

/**
 * La categoría, o un 404. Una dirección que no corresponde a ninguna de las
 * nueve no debe responder 200 con una página vacía: respondería bien a un enlace
 * roto y el ciudadano creería que la categoría existe.
 */
const categoria: CategoriaTransparencia | undefined = categoriaPorSlug(slug)
if (categoria === undefined) {
  throw createError({
    statusCode: 404,
    statusMessage: 'No existe esa categoría de transparencia',
    fatal: true,
  })
}

useHead({
  title: `${categoria.nombre} · Transparencia · Sede Electrónica`,
  meta: [
    {
      name: 'description',
      content: `Categoría ${categoria.numero} de la sección de Transparencia y acceso a la información pública: ${categoria.nombre}. Documentos publicados por la Alcaldía Distrital de Santa Marta con su procedencia y su fecha de publicación.`,
    },
  ],
})

// ---------------------------------------------------------------------------
// Estado del buscador
// ---------------------------------------------------------------------------

/** El término aplicado. La búsqueda se dispara al enviar el formulario. */
const termino = ref('')

/** Volver a montar el buscador al limpiarlo desde fuera. Ver la sección. */
const claveBuscador = ref(0)

function alBuscar(nuevo: string): void {
  termino.value = nuevo
}

function limpiarBusqueda(): void {
  termino.value = ''
  claveBuscador.value += 1
}

const hayBusqueda = computed<boolean>(() => termino.value !== '')

const coincidencias = computed<CoincidenciaTransparencia[]>(() => {
  if (!hayBusqueda.value) return []
  return buscarEnCategoria(categoria, termino.value)
})

const resumenBusqueda = computed<string>(() => {
  const total = coincidencias.value.length
  if (total === 0) {
    return `Ningún documento de esta categoría coincide con «${termino.value}».`
  }
  return `${total} ${total === 1 ? 'documento coincide' : 'documentos coinciden'} con «${termino.value}».`
})

// ---------------------------------------------------------------------------
// Vista
// ---------------------------------------------------------------------------

/** Un documento con su clave de lista, ya ordenado. */
interface DocumentoVista {
  clave: string
  documento: DocumentoTransparencia
}

/** Un grupo de documentos con su clave de lista. */
interface GrupoVista {
  clave: string
  titulo: string | null
  documentos: DocumentoVista[]
}

/** Un apartado con su clave, sus grupos y si está vacío. */
interface ApartadoVista {
  clave: string
  titulo: string
  faltante?: string
  grupos: GrupoVista[]
  vacio: boolean
}

/**
 * El inventario de la categoría, ya ordenado y con sus claves.
 *
 * Las claves se construyen con la posición y no con el título ni con la
 * dirección: hay 334 documentos y sólo 319 direcciones distintas —un mismo PDF
 * aparece en dos apartados y la portada del sitio se repite cuatro veces—, así
 * que una clave basada en la dirección colisionaría y Vue reutilizaría el nodo
 * equivocado.
 */
const apartadosVista = computed<ApartadoVista[]>(() =>
  categoria.apartados.map((apartado, indiceApartado) => {
    const grupos: GrupoVista[] = apartado.grupos.map(
      (grupo: GrupoTransparencia, indiceGrupo: number) => ({
        clave: `a${indiceApartado}-g${indiceGrupo}`,
        titulo: grupo.titulo,
        documentos: ordenarPorFecha(grupo.documentos).map((documento, indiceDocumento) => ({
          clave: `a${indiceApartado}-g${indiceGrupo}-d${indiceDocumento}`,
          documento,
        })),
      }),
    )
    const vacio = grupos.every((grupo) => grupo.documentos.length === 0)
    return {
      clave: `apartado-${indiceApartado}`,
      titulo: apartado.titulo,
      faltante: apartado.faltante,
      grupos,
      vacio,
    }
  }),
)

/** Cuántos documentos publica la categoría hoy. */
const total = computed<number>(() => totalDe(categoria))

/** Si esta categoría tiene al menos una fecha declarada que ordenar. */
const conFechas = computed<boolean>(() => hayFechaDeclarada(categoria))

/** Las demás categorías, para poder saltar de una a otra sin volver al índice. */
const otrasCategorias = computed<CategoriaTransparencia[]>(() =>
  CATEGORIAS.filter((otra) => otra.slug !== categoria.slug),
)

/**
 * La línea de fecha de un documento.
 *
 * Se dice lo que hay: la fecha si la fuente la declara, y que **no consta** si
 * no la declara. El numeral 2.4.1.e de la Resolución 1519 de 2020 obliga a que
 * todo documento indique la fecha de su publicación, así que su falta es un
 * incumplimiento que tiene que poder verse.
 */
function textoFecha(documento: DocumentoTransparencia): string {
  if (documento.fechaPublicacion === undefined) return 'Fecha de publicación: no consta en la fuente'
  return `Fecha de publicación: ${documento.fechaPublicacion}`
}

/** La clase de la insignia de estado. */
function claseEstado(estado: EstadoCategoria): string {
  return `insignia-${estado}`
}
</script>

<template>
  <div class="container py-5 categoria-transparencia">
    <p class="categoria-ubicacion">
      <NuxtLink to="/transparencia">Transparencia y acceso a la información pública</NuxtLink>
      · Categoría {{ categoria.numero }} de 9
    </p>

    <h1>{{ categoria.numero }}. {{ categoria.nombre }}</h1>

    <p class="lead">{{ categoria.publica }}</p>

    <p class="categoria-base">
      <span class="etiqueta-base">Base normativa</span> {{ categoria.base }}
    </p>

    <!--
      El estado, cuando no es el normal. Una categoría publicada no lleva
      insignia: marcar las nueve haría que la marca no distinguiera nada.
    -->
    <p v-if="categoria.estado !== 'publicada'" class="categoria-estado">
      <span class="insignia" :class="claseEstado(categoria.estado)">
        {{ ROTULO_ESTADO[categoria.estado] }}
      </span>
      <span class="categoria-motivo">{{ categoria.motivo }}</span>
    </p>

    <!--
      La procedencia del inventario, siempre a la vista. Es lo que permite
      auditar de dónde salió cada documento y qué día.
    -->
    <p class="categoria-procedencia">
      Los {{ total }} {{ total === 1 ? 'documento' : 'documentos' }} de esta categoría se
      tomaron de <a :href="FUENTE_ORIGEN.url">{{ FUENTE_ORIGEN.nombre }}</a>, rastreado el
      {{ FUENTE_ORIGEN.rastreado }}. Cada ficha dice de qué apartado del índice anterior sale.
    </p>

    <!--
      El estado de la fecha, declarado y calculado sobre los datos: desaparece
      solo cuando la Entidad declare la primera fecha, y con él desaparece la
      advertencia de que el orden no puede ser cronológico todavía.
    -->
    <p v-if="total > 0 && !conFechas" class="nota-estado">
      Ninguno de estos {{ total }} documentos declara su fecha de publicación, así que no se
      pueden listar del más reciente al más antiguo como exige el Anexo 2 §4.1.2.1: se
      conserva el orden en que la fuente los publicaba. La Sede no estima fechas.
    </p>

    <!--
      El defecto del origen, cuando lo hay, rotulado como lo que es: una nota
      sobre la fuente, no una afirmación de la Entidad ni una excusa de la Sede.
    -->
    <details v-if="categoria.notaOrigen !== undefined" class="nota-origen">
      <summary>Nota sobre el origen de esta categoría</summary>
      <p class="mb-0">{{ categoria.notaOrigen }}</p>
    </details>

    <section
      v-if="categoria.federada !== undefined"
      class="fuente-federada"
      aria-labelledby="fuente-federada"
    >
      <h2 id="fuente-federada" class="h4">{{ categoria.federada.portal }}</h2>
      <p class="mb-2">{{ categoria.federada.instruccion }}</p>
      <a class="btn btn-govco outline-btn-govco boton-categoria" :href="categoria.federada.url">
        Ir al portal de datos abiertos del Estado
      </a>
    </section>

    <!-- ===================================================================== -->
    <!-- Buscador de la categoría                                              -->
    <!-- ===================================================================== -->

    <section class="bloque" aria-labelledby="buscador-categoria">
      <h2 id="buscador-categoria" class="h4">Buscar en esta categoría</h2>

      <p v-if="total > 0">
        Busca por el nombre del documento o por el apartado al que pertenece. La búsqueda
        recorre únicamente los {{ total }} documentos de esta categoría.
      </p>

      <!--
        El buscador se dibuja también en la categoría sin documentos: forma
        parte de la estructura declarada de la sección, y esconderlo en las
        categorías vacías haría que la categoría pareciera distinta de las
        demás en vez de vacía.
      -->
      <p v-else>
        Esta categoría todavía no tiene documentos, así que la búsqueda no puede devolver
        ninguno. El buscador se deja a la vista porque forma parte de la categoría y
        reaparecerá en cuanto la Entidad publique.
      </p>

      <BuscadorGovco
        :key="claveBuscador"
        :etiqueta="`Buscar documentos en la categoría ${categoria.nombre}`"
        placeholder="Buscar en esta categoría"
        texto-boton="Buscar en la categoría"
        :valor-inicial="termino"
        @buscar="alBuscar"
        @limpiar="limpiarBusqueda"
      />

      <div v-if="hayBusqueda" class="barra-resultados">
        <p class="recuento-resultados mb-0" role="status">{{ resumenBusqueda }}</p>
        <button
          type="button"
          class="btn btn-govco outline-btn-govco boton-categoria"
          @click="limpiarBusqueda"
        >
          Limpiar la búsqueda
        </button>
      </div>
    </section>

    <!-- ===================================================================== -->
    <!-- Resultados de la búsqueda                                             -->
    <!-- ===================================================================== -->

    <section v-if="hayBusqueda" class="bloque" aria-labelledby="resultados-categoria">
      <h2 id="resultados-categoria" class="h4">Resultados de la búsqueda</h2>

      <ul v-if="coincidencias.length > 0" class="lista-documentos" role="list">
        <li
          v-for="(coincidencia, indice) in coincidencias"
          :key="`coincidencia-${indice}`"
          class="documento"
        >
          <a class="documento-titulo" :href="coincidencia.documento.url">
            {{ coincidencia.documento.titulo }}
          </a>
          <p class="documento-meta mb-0">
            <span class="documento-contexto">Apartado: {{ coincidencia.apartado.titulo }}</span>
            <span class="documento-fecha">{{ textoFecha(coincidencia.documento) }}</span>
          </p>
        </li>
      </ul>

      <p v-else class="estado-vacio">
        Ningún documento de esta categoría coincide con «{{ termino }}». Pruebe con otras
        palabras o recorra los apartados de abajo.
      </p>
    </section>

    <!-- ===================================================================== -->
    <!-- Los apartados y sus documentos                                        -->
    <!-- ===================================================================== -->

    <div v-if="apartadosVista.length > 0" class="bloque">
      <h2 class="h4">Apartados de esta categoría</h2>

      <section
        v-for="apartado in apartadosVista"
        :id="apartado.clave"
        :key="apartado.clave"
        class="apartado-bloque"
        :aria-labelledby="`${apartado.clave}-titulo`"
      >
        <h3 :id="`${apartado.clave}-titulo`" class="h5">{{ apartado.titulo }}</h3>

        <!--
          El apartado sin contenido se declara con el motivo por el que existe.
          Quitarlo de la página haría que la categoría pareciera completa, y
          quien audita cuenta apartados publicados sobre apartados exigidos.
        -->
        <div v-if="apartado.vacio" class="estado-vacio estado-vacio-publicacion">
          <p class="estado-vacio-titulo mb-2">Este apartado no tiene documentos publicados.</p>
          <p v-if="apartado.faltante !== undefined" class="mb-0">{{ apartado.faltante }}</p>
          <p v-else class="mb-0">
            La Entidad no publica hoy ningún documento en este apartado. La Sede no lo rellena
            con contenido de ejemplo.
          </p>
        </div>

        <!--
          Los grupos. El encabezado del grupo sólo se dibuja si la fuente lo
          declara: un rótulo inventado para una lista suelta sería información
          de más.
        -->
        <div v-for="grupo in apartado.grupos" :key="grupo.clave" class="grupo">
          <h4 v-if="grupo.titulo !== null" class="h6 grupo-titulo">{{ grupo.titulo }}</h4>

          <ul class="lista-documentos" role="list">
            <li v-for="elemento in grupo.documentos" :key="elemento.clave" class="documento">
              <a class="documento-titulo" :href="elemento.documento.url">
                {{ elemento.documento.titulo }}
              </a>

              <p class="documento-meta mb-0">
                <span class="documento-contexto">
                  Procedencia: {{ elemento.documento.apartadoOrigen }} ·
                  <a :href="FUENTE_ORIGEN.url">{{ FUENTE_ORIGEN.nombre }}</a>,
                  rastreado el {{ FUENTE_ORIGEN.rastreado }}
                </span>
                <span class="documento-fecha">{{ textoFecha(elemento.documento) }}</span>
              </p>

              <!--
                El aviso sobre el destino, cuando el propio enlace del origen
                dice algo que el ciudadano necesita saber antes de pulsarlo: que
                lleva a otro municipio, a otro nivel de gobierno, a un sistema
                sin filtrar por entidad o a un documento que no corresponde al
                apartado. No es una opinión sobre la Entidad: es una descripción
                del destino, comprobable en su dirección.
              -->
              <p v-if="elemento.documento.aviso !== undefined" class="documento-aviso mb-0">
                {{ elemento.documento.aviso }}
              </p>
            </li>
          </ul>
        </div>
      </section>
    </div>

    <!--
      Categoría sin apartados: es el caso de la novena, que la Entidad no
      publica. No se inventan apartados —determinarlos corresponde a la Entidad—
      y se declara el vacío con lo que sí es cierto.
    -->
    <div v-else class="bloque estado-vacio estado-vacio-publicacion">
      <p class="estado-vacio-titulo mb-2">
        Esta categoría no tiene apartados ni documentos publicados.
      </p>
      <p class="mb-0">
        La Entidad no publica hoy esta categoría, así que no hay ningún apartado que recorrer
        ni ningún documento que enlazar. La Sede no los inventa: determinar qué información
        integra esta categoría corresponde a la Entidad.
      </p>
    </div>

    <!-- ===================================================================== -->
    <!-- Las demás categorías                                                  -->
    <!-- ===================================================================== -->

    <nav class="bloque" aria-labelledby="otras-categorias">
      <h2 id="otras-categorias" class="h4">Las demás categorías de la sección</h2>

      <ul class="lista-otras" role="list">
        <li v-for="otra in otrasCategorias" :key="otra.slug">
          <NuxtLink :to="`/transparencia/${otra.slug}`">
            {{ otra.numero }}. {{ otra.nombre }}
          </NuxtLink>
          <span class="otras-cuenta">
            {{ totalDe(otra) }} {{ totalDe(otra) === 1 ? 'documento' : 'documentos' }}
          </span>
        </li>
      </ul>

      <p class="mb-0">
        <NuxtLink to="/transparencia">Volver al índice de la sección</NuxtLink>
      </p>
    </nav>
  </div>
</template>

<style scoped>
/*
  El color de los enlaces y el foco de los campos los fija la hoja del sitio
  (`app/assets/css/sitio.css`), no esta página. Los tokens del Kit se usan con su
  valor por defecto para que la página siga leyéndose si el Kit no carga.
*/

.categoria-ubicacion {
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.categoria-base {
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.etiqueta-base {
  font-weight: 700;
}

.categoria-procedencia {
  color: var(--govcolor-matterhorn, #4c4c4c);
  overflow-wrap: anywhere;
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
  se entiende sin distinguir colores (WCAG 2.1, criterio 1.4.1).
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

.bloque {
  margin-top: 2rem;
}

/*
  Aviso neutro: el amarillo institucional se reserva para «aquí no hay nada
  publicado», que es una afirmación distinta y más grave que «falta un dato».
*/
.nota-estado {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-left: 0.25rem solid var(--govcolor-silver, #b9b9b9);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.nota-origen {
  margin-top: 1rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.nota-origen summary {
  /* CAG-23: el resumen es un control y mide al menos 44 px de alto. */
  display: flex;
  align-items: center;
  min-height: 2.75rem;
  font-weight: 700;
  cursor: pointer;
}

.fuente-federada {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border: 1px solid var(--govcolor-cobalt, #0943b5);
  border-radius: 0.25rem;
}

.boton-categoria {
  min-height: 2.75rem;
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

/* --- Apartados ----------------------------------------------------------- */

.apartado-bloque {
  margin-top: 2rem;
  padding-top: 1.25rem;
  border-top: 0.25rem solid var(--govcolor-cobalt, #0943b5);
  /* El ancla no debe quedar pegada al borde superior al saltar a ella. */
  scroll-margin-top: 1rem;
}

.grupo {
  margin-top: 1rem;
}

.grupo-titulo {
  margin-bottom: 0.25rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/* --- Documentos ---------------------------------------------------------- */

.lista-documentos {
  margin: 0.5rem 0 0;
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

/*
  El aviso del destino. Lleva el filete dorado y no el rojo: no es un error, es
  algo que el ciudadano tiene que saber antes de pulsar el enlace.
*/
.documento-aviso {
  margin-top: 0.375rem;
  padding-left: 0.75rem;
  border-left: 0.25rem solid var(--govcolor-golden-brown, #9d7700);
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/* --- Estados vacíos ------------------------------------------------------ */

/*
  El amarillo institucional del Kit con el texto en Matterhorn, que sobre ese
  fondo da 7,6:1 y pasa el 4,5:1 de WCAG 2.1 AA. Es el mismo aviso que usa el
  resto del sitio para «aquí no hay nada publicado».
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

/* --- Las demás categorías ------------------------------------------------ */

.lista-otras {
  margin: 0;
  padding: 0;
  list-style: none;
}

.lista-otras li {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: baseline;
  padding: 0.25rem 0;
}

.lista-otras a {
  overflow-wrap: anywhere;
}

.otras-cuenta {
  flex: none;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

@media (max-width: 47.9375rem) {
  /* CAG-23: en móvil los enlaces de navegación miden al menos 44 px de alto. */
  .lista-otras a {
    display: inline-flex;
    align-items: center;
    min-height: 2.75rem;
  }
}
</style>

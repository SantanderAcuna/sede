<script setup lang="ts">
/**
 * PQRSD: los tipos de solicitud y la entrada al formulario.
 *
 * **De dónde sale esta página.** Del **Anexo 2.1 — Guía de diseño gráfico para
 * sedes electrónicas**, que el propio anexo declara «de obligatorio
 * cumplimiento» por el artículo 14 del Decreto 2106 de 2019. Su página 13 pide
 * explicar el tipo de solicitud y su término de respuesta, y muestra el ejemplo
 * como una lista en la que cada tipo lleva su definición y **su propio botón**,
 * porque el ciudadano no debería tener que elegir el tipo dentro del formulario
 * después de haber leído cuál es el suyo.
 *
 * **Sobre los textos.** Las definiciones de *petición* y *queja* son literalmente
 * las del anexo. La de *reclamo* es la misma, completada: el documento la corta
 * en «demandar una…». Las de *sugerencia*, *denuncia* y *felicitación* no las
 * desarrolla el anexo; son las definiciones corrientes de esos tres tipos, que
 * se escriben aquí tal cual para que se reconozcan, sin añadidos.
 *
 * **No se escriben plazos.** El anexo pide explicar el término de respuesta,
 * pero un número de días suelto —sin la norma que lo fija ni las excepciones que
 * lo acortan o lo amplían— se lee como una promesa y puede hacer que alguien
 * pierda un derecho por confiar en él. En el apartado «Términos de respuesta» se
 * explica de dónde depende el término y por qué no se publica aquí una cifra.
 * Cuando el módulo esté integrado, cada acuse de recibo dirá el término que le
 * corresponde a esa solicitud concreta, que es el único sitio donde se puede
 * afirmar sin riesgo.
 *
 * **Sobre la radicación.** El formulario existe y se puede ver, pero **no radica
 * nada**: el sistema de radicación de la Entidad no está conectado. Eso se dice
 * aquí, antes de que nadie empiece a rellenarlo, porque creer que se ha radicado
 * una solicitud cuando no ha llegado nada es el peor fallo posible en una sede
 * electrónica.
 */
useHead({
  title: 'PQRSD · Sede Electrónica',
  meta: [
    {
      name: 'description',
      content:
        'Qué es una petición, una queja, un reclamo, una sugerencia, una denuncia y una felicitación, y cómo presentarlas ante la Alcaldía Distrital de Santa Marta.',
    },
  ],
})

/**
 * Los tipos de solicitud que viajan en la dirección del formulario.
 *
 * Se declaran como unión y no como `string` para que un error de escritura en el
 * `slug` lo detecte el comprobador de tipos en vez de producir un enlace que
 * abre el formulario sin ningún tipo preseleccionado. La misma lista, con las
 * mismas palabras, vive en `realizar-una-peticion.vue`: son dos páginas y esta
 * tarea no puede tocar ningún otro archivo, así que el catálogo está duplicado.
 * Cuando se pueda añadir un módulo compartido, ese es el sitio de los dos.
 */
type SlugDeTipo =
  | 'peticion'
  | 'queja'
  | 'reclamo'
  | 'sugerencia'
  | 'denuncia'
  | 'felicitacion'

interface TipoDeSolicitud {
  /** Valor que viaja en la dirección para preseleccionar el tipo en el formulario. */
  slug: SlugDeTipo
  /** Nombre del tipo, tal como encabeza su bloque. */
  nombre: string
  /** Qué es ese tipo de solicitud. */
  definicion: string
  /**
   * Texto del botón, en caja normal.
   *
   * El anexo escribe estas etiquetas en mayúsculas y así se ven, pero las
   * mayúsculas las pone la hoja de estilos (`text-transform`). En el marcado van
   * en caja normal porque varios lectores de pantalla deletrean las palabras
   * escritas enteramente en mayúsculas, y «ENVIAR UNA QUEJA» deletreado deja de
   * ser una instrucción para convertirse en un acertijo.
   */
  boton: string
}

/**
 * Los seis tipos del PQRSD colombiano, en el orden en que el anexo los presenta:
 * primero los tres que desarrolla y luego los tres restantes.
 */
const tipos: TipoDeSolicitud[] = [
  {
    slug: 'peticion',
    nombre: 'Petición',
    definicion:
      'Es el derecho fundamental que tiene toda persona a presentar solicitudes respetuosas a las autoridades por motivos de interés general o particular y a obtener su pronta resolución.',
    boton: 'Enviar una petición o un derecho de petición',
  },
  {
    slug: 'queja',
    nombre: 'Queja',
    definicion:
      'Es la manifestación de protesta, censura, descontento o inconformidad que formula una persona en relación con una conducta que considera irregular de uno o varios servidores públicos en desarrollo de sus funciones.',
    boton: 'Enviar una queja',
  },
  {
    slug: 'reclamo',
    nombre: 'Reclamo',
    definicion:
      'Es el derecho que tiene toda persona de exigir, reivindicar o demandar una solución, ya sea por motivo general o particular, referente a la prestación indebida de un servicio o a la falta de atención de una solicitud.',
    boton: 'Enviar un reclamo',
  },
  {
    slug: 'sugerencia',
    nombre: 'Sugerencia',
    definicion:
      'Es la manifestación de una idea o propuesta para mejorar el servicio o la gestión de la Entidad.',
    boton: 'Enviar una sugerencia',
  },
  {
    slug: 'denuncia',
    nombre: 'Denuncia',
    definicion:
      'Es la manifestación con la que se pone en conocimiento de la Entidad una conducta presuntamente irregular de un servidor público o de un particular que cumple funciones públicas, para que se investigue.',
    boton: 'Enviar una denuncia',
  },
  {
    slug: 'felicitacion',
    nombre: 'Felicitación',
    definicion:
      'Es la manifestación con la que una persona expresa su satisfacción o reconocimiento por el servicio o la atención recibida.',
    boton: 'Enviar una felicitación',
  },
]
</script>

<template>
  <div class="container py-5">
    <div class="row">
      <div class="col-lg-10">
        <h1>Peticiones, quejas, reclamos, sugerencias, denuncias y felicitaciones (PQRSD)</h1>

        <p class="lead">
          Toda persona puede presentar una solicitud a la Alcaldía Distrital de Santa
          Marta. Esta página explica qué es cada tipo de solicitud y conduce al
          formulario con el tipo ya elegido.
        </p>
      </div>
    </div>

    <!--
      Este aviso va antes que los botones a propósito: quien llega aquí viene a
      radicar, y tiene que saber antes de empezar que por este medio todavía no se
      radica nada.
    -->
    <div class="aviso-radicacion" role="status">
      <p class="mb-0">
        <strong>La radicación en línea todavía no está disponible.</strong>
        Los botones de esta página llevan al formulario para que pueda conocerlo, pero el
        formulario no está conectado al sistema de radicación de la Entidad: al enviarlo
        <strong>no se registra ninguna solicitud</strong> y la Entidad no la recibe. Para
        presentar su solicitud hoy, use los
        <NuxtLink to="/atencion">canales de atención</NuxtLink>.
      </p>
    </div>

    <h2 class="h3 mt-5">Tipos de solicitud</h2>

    <div class="row">
      <div class="col-lg-10">
        <p>
          Lea la definición del tipo que corresponda a su caso y use su botón: el
          formulario se abrirá con ese tipo ya seleccionado.
        </p>
      </div>
    </div>

    <div class="row g-4">
      <div v-for="tipo in tipos" :key="tipo.slug" class="col-md-6">
        <!--
          Cada tipo es un artículo con su nombre, su definición y su botón: es la
          forma que el anexo muestra y la que evita que el ciudadano tenga que
          volver a decidir dentro del formulario lo que ya decidió leyendo.
        -->
        <article class="tipo-solicitud h-100 d-flex flex-column">
          <h3 class="h4">{{ tipo.nombre }}</h3>

          <p class="flex-grow-1">{{ tipo.definicion }}</p>

          <p class="mb-0">
            <NuxtLink
              class="btn-govco fill-btn-govco boton-tipo"
              :to="{ path: '/realizar-una-peticion', query: { tipo: tipo.slug } }"
            >
              {{ tipo.boton }}
            </NuxtLink>
          </p>
        </article>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-10">
        <h2 class="h3 mt-5">Términos de respuesta</h2>

        <p>
          La Entidad responde toda solicitud de fondo y por escrito. El término del
          que dispone para hacerlo no es el mismo en todos los casos: depende del
          tipo de solicitud y de la materia, y lo fija la ley.
        </p>

        <p>
          Por eso esta página no publica un número de días. Un plazo sin la norma que
          lo establece —y sin las excepciones que lo acortan o lo amplían— se lee como
          una promesa, y una promesa equivocada sobre un término legal puede costarle
          a alguien un derecho. Cuando el módulo de radicación esté integrado, el
          acuse de recibo de cada solicitud indicará el término que le corresponde, y
          aquí se publicará la relación completa con la norma en que se apoya cada uno.
        </p>

        <h2 class="h3 mt-5">Qué puede hacer hoy</h2>

        <p>
          Mientras el formulario no radique solicitudes, la Entidad atiende por los
          canales presenciales, telefónicos y electrónicos que ya tiene publicados.
          Están todos, con sus horarios, en
          <NuxtLink to="/atencion">Canales de atención y sedes</NuxtLink>.
        </p>

        <p>
          El formulario, tal como quedará cuando el módulo esté integrado, está en
          <NuxtLink to="/realizar-una-peticion">Realizar una petición</NuxtLink>, y
          advierte allí mismo de que todavía no registra nada.
        </p>

        <p class="mt-4 mb-0">
          <NuxtLink class="btn btn-outline-primary" to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
  Color de los enlaces de texto del contenido.

  El sitio carga Bootstrap porque el Kit lo exige —`all.css` no trae ni una clase
  de rejilla ni de botón—, pero Bootstrap trae también su propio color de enlace,
  el azul `#0d6efd`, que no pertenece a la paleta gov.co. Sobre blanco se queda en
  4,50:1, justo en el límite del 4,5:1 que exige WCAG 2.1 AA, y sobre cualquier
  fondo teñido lo incumple: dentro del aviso amarillo de esta página bajaba a
  3,6:1, que axe detecta como violación seria.

  Se corrige aquí, en cada página, porque la corrección de verdad va en la hoja
  compartida del sitio (`assets/css/sitio.css`), que va con las mismas reglas para
  todas, y esta tarea no puede tocar otro archivo.

  Se excluyen DOS familias de botones, y la segunda no es opcional:

    · `.btn` — los botones de Bootstrap que ya usan las demás páginas.
    · `.btn-govco` — los del Kit. El botón relleno del Kit escribe su etiqueta en
      blanco sobre el azul cobalto, y esa regla pesa menos que ésta; sin la
      exclusión, el texto quedaba del mismo color que su fondo y las seis
      etiquetas de tipo de solicitud desaparecían. Se vio midiendo el color
      calculado del botón, no con el corrector automático: axe no marcó esa
      pérdida de contraste porque el texto del botón es un elemento anónimo de
      una caja flexible, y ahí no lo mira.
*/
.container a:not(.btn):not(.btn-govco) {
  color: var(--govcolor-cobalt, #0943b5);
}

/*
  Aviso de radicación. Se usan los mismos tokens y el mismo criterio de contraste
  que el aviso de sección en preparación: el amarillo institucional del Kit
  (`--govcolor-vis-vis`) con el Matterhorn para el texto da 7,6:1, por encima del
  4,5:1 que exige WCAG 2.1 AA. El filete izquierdo lleva el azul de la Entidad
  para que el bloque se reconozca como parte de este sitio y no como un aviso
  genérico.
*/
.aviso-radicacion {
  margin-top: 1.5rem;
  margin-bottom: 2rem;
  padding: 1rem 1.25rem;
  border-left: 6px solid var(--govcolor-cobalt, #0943b5);
  background-color: var(--govcolor-vis-vis, #fee697);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  Ficha de un tipo de solicitud. El filete superior es lo que hace que las seis
  fichas se lean como una lista de opciones y no como seis párrafos sueltos.
*/
.tipo-solicitud {
  padding: 1.25rem;
  border: 1px solid var(--govcolor-silver-dis, #c8c8c8);
  border-top: 4px solid var(--govcolor-cobalt, #0943b5);
  border-radius: 0.313rem;
  background-color: #fff;
}

/*
  Botón del tipo de solicitud.

  El selector lleva tres partes —`a`, `.btn-govco` y `.boton-tipo`— porque el Kit
  declara sus propias reglas para `.btn-govco.fill-btn-govco` y cualquier selector
  menos específico perdería el empate. Con esto manda el nuestro en las tres
  propiedades que se ajustan:

    · `text-transform` pone las mayúsculas que pide el anexo, sin escribirlas en el
      marcado (los lectores de pantalla deletrean el texto en mayúsculas).
    · `line-height`: el Kit fija 1rem de interlineado, que con una etiqueta de
      «Enviar una petición o un derecho de petición» partida en dos líneas a 320 px
      queda apretado y difícil de leer.
    · `min-height`: el área pulsable mínima que pide WCAG 2.5.5.
*/
a.btn-govco.boton-tipo {
  text-transform: uppercase;
  line-height: 1.35;
  min-height: 2.75rem;
  width: 100%;
}
</style>

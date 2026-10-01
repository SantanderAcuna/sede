<script setup lang="ts">
/**
 * Pie de página del Kit gov.co (componente transversal 6), variante A.
 *
 * **Variante elegida: A — «Entidades con 1 a 3 sedes».** El Kit publica dos
 * variantes en `examples/transversal/pie-de-pagina.html`: la A va de una a tres
 * sedes y la B (más de tres) sustituye el bloque repetido de sedes por un enlace
 * al Directorio Institucional. La Alcaldía Distrital de Santa Marta tiene **una
 * sola** sede electrónica, así que corresponde la A; la B anunciaría un
 * directorio de sedes que no existe.
 *
 * De la variante A **no** se reproduce el bloque `data-container` que repite las
 * sedes 1 a 3: con una única sede duplicaría el contacto ya publicado arriba, y
 * repetirlo no informa, confunde. Cuando haya más de una sede hay que pasar a la
 * variante B, no copiar ese bloque.
 *
 * Clases verificadas contra `sitio/public/govco/all.css` (§ «Pie de página») y
 * contra `vendor-src/layout-govco-v5/src/transversal/pie-de-pagina.css`. La clase
 * contenedora del Kit es `pie-pagina-govco`, **sin** el «de» del medio:
 * `pie-de-pagina-govco` no existe en el bundle y no estiliza nada.
 *
 * Dos apartados deliberados del ejemplo del Kit, cada uno con su motivo:
 *
 *  1. **El logo de la autoridad.** El Kit dibuja `.govco-logo-entidad` con
 *     `content: url(assets/images/Logo-v2-MinTIC.png)`: es el logotipo del
 *     Ministerio TIC puesto como ejemplo. Publicarlo aquí sería mostrar el
 *     logotipo de otro organismo. Se usa un `<img>` propio, que además admite
 *     `alt` —cosa que `content:` no permite— y por eso rinde mejor ante CAG-12.
 *     Los otros dos logos del Kit sí se usan tal cual, con su texto equivalente
 *     para lectores de pantalla.
 *
 *  2. **Los encabezados son `h2` y `h3`, no los `h4` y `h5` del ejemplo.** El Kit
 *     estiliza el pie sobre `h4` y `h5`, pero el pie es una región al final de la
 *     página: saltar de `h1`/`h2` a `h4` rompe el orden de encabezados que exige
 *     WCAG 2.4.6 y la auditoría lo marcaría. Se conserva la apariencia del Kit
 *     —Cobalt 22 px y Cobalt 20 px, con sus dos escalones móviles— con reglas
 *     propias sobre las etiquetas correctas.
 *
 * **Contraste (CAG-12 y WCAG 1.4.3), medido y no supuesto.** Todos los pares que
 * este pie usa pasan 4,5:1, incluidos los estados interactivos del Kit:
 *
 *   Cobalt `#0943B5` sobre blanco ................ 8,46:1
 *   Havelock `#4672C8`, hover del Kit, sobre blanco 4,67:1
 *   Matterhorn `#4C4C4C`, texto de contacto ....... 8,59:1
 *   Blanco sobre la banda Cobalt de abajo ......... 8,46:1
 *
 * `--govcolor-silver-dis` (1,67:1) y `--govcolor-tropical-blue` (1,71:1) no se
 * usan como texto: en el Kit son color de relleno deshabilitado y de borde. El
 * estado deshabilitado del Kit incumple 1.4.3, pero WCAG exime el texto
 * deshabilitado y aquí no hay controles deshabilitados.
 *
 * El `script.js` del Kit no se carga en este sitio, y este componente no necesita
 * nada de él: no hay comportamiento que reproducir.
 */
import { computed } from 'vue'

import { POLITICAS } from '~/config/sitemap'

/**
 * La vía del pie a los ajustes de accesibilidad.
 *
 * **Publica un enlace, no un juego de controles.** Antes el pie repetía los seis
 * ajustes de la barra flotante con otras palabras y otro orden, y las dos listas
 * ya habían divergido: el pie ofrecía espaciado y la barra no, y «Restablecer» se
 * llamaba distinto en cada sitio. Eso incumple CC7 —mismas acciones, mismo
 * aspecto y mismo nombre— y multiplica los lugares donde arreglar un fallo.
 *
 * Ahora abre el **mismo panel** que el botón de la cabecera, porque la apertura es
 * estado compartido en `useAccesibilidad`. Se sigue cumpliendo CC12 —vías
 * distintas al mismo contenido— sin duplicar ni un control, y es la vía que
 * sobrevive cuando la cabecera ya ha quedado fuera de pantalla.
 */
const { abrirPanel } = useAccesibilidad()

/**
 * El punto de revocación del consentimiento de cookies.
 *
 * RF-B1-008 exige que el consentimiento sea revocable «en cualquier momento», y
 * cuando el banner se cierra no queda nada que pulsar. Este botón lo reabre con
 * lo que el ciudadano hubiera elegido, para revisarlo o retirarlo.
 */
const { abrirBanner } = useConsentimientoCookies()

/** Un dato de contacto del bloque de la sede. */
interface DatoContacto {
  /** Etiqueta visible, con el nombre que le da la Resolución 1519 de 2020. */
  etiqueta: string
  /**
   * Valor. Si es `null` el dato no se publica: es preferible omitir un canal a
   * publicar uno falso, o a dejar «xxx xxx» como hace el ejemplo del Kit.
   */
  valor: string | null
  /** Cuando el valor es un correo, aquí va la dirección y el dato se vuelve enlace. */
  correo?: string
}

/** Una sede. La variante A admite de una a tres; hoy hay una. */
interface Sede {
  /** Rótulo del bloque, p. ej. «Sede principal». */
  nombre: string
  /** Dirección con departamento y municipio, como exige el Anexo 2 §2.2.1.2. */
  direccion: string
  codigoPostal: string | null
  /** Horario. Dos frases (días y franja) por la forma en que el Kit los muestra. */
  horario: [string, string] | null
  /** Teléfonos de la sede, en el orden en que deben aparecer. */
  telefonos: DatoContacto[]
  /** Correos de la sede. */
  correos: DatoContacto[]
}

/** Un enlace de los bloques inferiores del pie. */
interface EnlacePie {
  /** Texto visible. */
  texto: string
  /**
   * Destino. Hoy ninguna de las políticas existe todavía como página —el sitio
   * está en construcción—, así que por defecto todas apuntan al mismo ancla y el
   * bloque lo dice a las claras en lugar de fingir cinco destinos distintos.
   */
  a: string
}

/**
 * Icono de red social. La lista es cerrada a propósito: son las clases
 * `govco-svg govco-*` que existen de verdad en el bundle. Un nombre fuera de esta
 * unión no compila, en lugar de dibujar un hueco invisible.
 */
type IconoSocial = 'govco-facebook-f' | 'govco-instagram' | 'govco-twitter'

interface RedSocial {
  red: string
  usuario: string
  url: string
  icono: IconoSocial
}

interface Props {
  /** Nombre completo de la entidad titular. */
  nombreEntidad?: string
  /** Ruta pública del logotipo oficial de la entidad. */
  logoEntidad?: string
  /** Nombre del bloque de contacto: la sede principal. */
  nombreSede?: string
  /** Las sedes. Sólo se publica el contacto de la primera. */
  sedes?: Sede[]
  redesSociales?: RedSocial[]
  /**
   * «Colombia, potencia de la vida» es la marca país del Gobierno vigente y el
   * Kit la trae en su ejemplo. Se deja como interruptor porque la marca depende
   * de cada periodo de gobierno: apagarla no debe exigir tocar la plantilla.
   */
  mostrarPotenciaDeLaVida?: boolean
  /**
   * El bloque inferior de enlaces. **Las cinco políticas obligatorias de SEG-006
   * viajan aquí**: incrustar rutas sería inventar la estructura de un sitio que
   * aún no las tiene. Quien monte el pie las sustituye por sus rutas reales en
   * cuanto existan.
   */
  enlacesPie?: EnlacePie[]
  /**
   * Texto del bloque de políticas mientras los documentos no estén publicados.
   * Sólo se muestra si todos los `enlacesPie` siguen apuntando al ancla.
   */
  avisoEnlacesPendientes?: string
}

const props = withDefaults(defineProps<Props>(), {
  nombreEntidad: 'Alcaldía Distrital de Santa Marta',
  nombreSede: 'Sede principal',
  logoEntidad: '/logo-entidad.png',
  // Datos tomados de `docs/adr/README.md` (ADR-0006) y del informe de
  // investigación sobre el sitio vigente de la Entidad. El código postal no lo
  // publica la Entidad: se verificó en el visor oficial de 4-72. Ninguno de estos
  // valores se inventa aquí; si la Entidad los ratifica de otro modo, se cambian
  // en un solo sitio.
  sedes: () => [
    {
      nombre: 'Sede principal',
      direccion: 'Calle 14 n.º 2-49, Palacio Municipal. Santa Marta, Magdalena, Colombia',
      codigoPostal: '470004',
      horario: ['Lunes a viernes', '8:00 a. m. a 12:00 m. y 2:00 p. m. a 6:00 p. m.'],
      telefonos: [
        { etiqueta: 'Teléfono conmutador', valor: '(+57) 605 420 9600' },
        { etiqueta: 'Línea gratuita', valor: '018000 955 532' },
        // Se publica porque es un canal exigido por FUN-014 y su ausencia
        // incumple más que su repetición. Es el número propio de la línea
        // anticorrupción —el conmutador es otro— y va con el indicativo del país
        // porque no es una línea 018000 (RN-B1-017).
        { etiqueta: 'Línea anticorrupción', valor: '(+57) 605 4351719' },
      ],
      correos: [
        {
          etiqueta: 'Correo institucional',
          valor: 'atencionalciudadano@santamarta.gov.co',
          correo: 'atencionalciudadano@santamarta.gov.co',
        },
        {
          etiqueta: 'Correo de notificaciones judiciales',
          valor: 'notificacionesalcaldiadistrital@santamarta.gov.co',
          correo: 'notificacionesalcaldiadistrital@santamarta.gov.co',
        },
      ],
    },
  ],
  redesSociales: () => [
    {
      red: 'Facebook',
      usuario: 'SantaMartaDTCH',
      url: 'https://www.facebook.com/SantaMartaDTCH',
      icono: 'govco-facebook-f',
    },
    {
      red: 'Instagram',
      usuario: 'santamartadtch',
      url: 'https://www.instagram.com/santamartadtch',
      icono: 'govco-instagram',
    },
    // `govco-twitter` es el icono que trae el ejemplo del Kit; existe también
    // `govco-twitter-x`, pero se respeta el que el Kit publica.
    {
      red: 'X (antes Twitter)',
      usuario: 'SantaMartaDTCH',
      url: 'https://x.com/SantaMartaDTCH',
      icono: 'govco-twitter',
    },
  ],
  mostrarPotenciaDeLaVida: true,
  // Mientras no existan las páginas, todas apuntan al contenido principal: un
  // destino real y anunciado, en lugar de cinco rutas inventadas que devolverían
  // 404 desde el pie de una sede electrónica.
  /*
   * Los enlaces del pie.
   *
   * Las cinco políticas se **derivan** de `config/sitemap.ts`, que es donde viven
   * su slug, su rótulo y su estado de publicación. Antes estaban escritas aquí,
   * allí y en la página de cada política, y ya habían divergido: mantener tres
   * copias de la misma lista es la forma más barata de que un día el pie enlace a
   * una política con un nombre que ya no existe (D-31).
   *
   * Los otros dos no son políticas y van escritos aquí, que es donde se decide
   * qué más lleva el pie: la declaración de accesibilidad y el mapa del sitio.
   */
  enlacesPie: () => [
    ...POLITICAS.map((politica) => ({
      texto: politica.etiqueta,
      a: `/politicas/${politica.slug}`,
    })),
    { texto: 'Accesibilidad', a: '/accesibilidad' },
    { texto: 'Mapa del sitio', a: '/mapa-del-sitio' },
  ],
  avisoEnlacesPendientes:
    'Las políticas de uso y tratamiento de datos están en preparación: se publicarán aquí con su documento y su acto administrativo de adopción.',
})

/**
 * El bloque de políticas sigue pendiente mientras todos sus enlaces apunten al
 * ancla del contenido. Cuando quien monte el pie sustituya las rutas por las
 * reales, la condición deja de cumplirse y el aviso desaparece solo.
 */
const enlacesPendientes = computed(() =>
  props.enlacesPie.every((enlace) => enlace.a.startsWith('#')),
)

/** La sede de la que se publica el contacto: la primera, y la única en la A. */
const sedePrincipal = computed<Sede | undefined>(() => props.sedes[0])

/**
 * Aplana el contacto de la sede principal en la forma que espera la plantilla. Se
 * calcula en el script y no con `v-if` dentro del `v-for`, que están prohibidos
 * juntos en el mismo elemento y además desalinearían el índice de la clave.
 */
const contacto = computed<DatoContacto[]>(() => {
  const sede = sedePrincipal.value
  if (sede === undefined) return []

  const items: DatoContacto[] = [
    { etiqueta: 'Dirección', valor: sede.direccion },
    { etiqueta: 'Código postal', valor: sede.codigoPostal },
  ]

  if (sede.horario !== null) {
    items.push({ etiqueta: 'Horario de atención', valor: sede.horario.join('. ') })
  }

  items.push(...sede.telefonos, ...sede.correos)

  return items.filter((item) => item.valor !== null && item.valor !== '')
})
</script>

<template>
  <!--
    `<footer>` en lugar del `<div>` del ejemplo: es un punto de referencia para el
    lector de pantalla (WCAG 1.3.1 y 2.4.1) y el Kit no lo impide. El nombre
    accesible se declara aquí porque la página puede tener otro pie.
  -->
  <footer class="pie-pagina-govco" aria-label="Pie de página de la Sede Electrónica">
    <div class="first-section">
      <h2 class="titulo-pie">{{ nombreEntidad }}</h2>

      <!--
        Los logos de la marca país y de la Entidad. El del Kit se dibuja con
        `content: url(...)` sobre un `<span>` y no admite texto alternativo, así
        que va oculto al lector de pantalla y su significado viaja en el texto
        equivalente de la banda inferior; el de la Entidad es un `<img>` propio,
        precisamente para poder darle un `alt` de verdad.
      -->
      <div class="logo-container">
        <span v-if="mostrarPotenciaDeLaVida" class="govco-logo-potencia" aria-hidden="true" />
        <span v-if="mostrarPotenciaDeLaVida" class="separator" aria-hidden="true" />
        <img class="logo-entidad" :src="logoEntidad" :alt="nombreEntidad" />
      </div>

      <h3 class="subtitulo-pie">{{ sedePrincipal?.nombre ?? nombreSede }}</h3>

      <ul class="contact-data-container">
        <li v-for="(item, indice) in contacto" :key="indice">
          <p>
            {{ item.etiqueta }}:
            <a
              v-if="item.correo"
              class="btn-govco link-btn-govco"
              :href="`mailto:${item.correo}`"
              :aria-label="`Permite enviar correo a ${item.correo}`"
            >{{ item.valor }}</a>
            <template v-else>{{ item.valor }}</template>
          </p>
        </li>
      </ul>

      <div class="links-container">
        <!--
          Sin `role="button"`: el ejemplo del Kit lo pone en estos `<a>`, pero
          declarar botón un elemento que navega confunde a los productos de apoyo
          y hace perder el enlace como tal (WCAG 1.3.1 y 4.1.2).
        -->
        <a
          v-for="red in redesSociales"
          :key="red.url"
          class="btn-govco link-btn-govco"
          :href="red.url"
          target="_blank"
          rel="noopener"
          :aria-label="`Enlace al ${red.red} de la Entidad (se abre en una pestaña nueva)`"
        >
          <span class="govco-svg" :class="red.icono" aria-hidden="true" />
          <span>{{ red.usuario }}</span>
        </a>
      </div>

      <nav class="end-links-container" aria-label="Políticas y enlaces de la sede">
        <a
          v-for="enlace in enlacesPie"
          :key="enlace.texto"
          class="btn-govco link-btn-govco"
          :href="enlace.a"
        >{{ enlace.texto }}</a>
      </nav>

      <p v-if="enlacesPendientes" class="aviso-pendientes">
        {{ avisoEnlacesPendientes }}
      </p>

      <!--
        Revocación del consentimiento de cookies (RF-B1-008). El banner se cierra
        cuando el ciudadano decide, y a partir de ahí no hay forma de cambiar de
        opinión si no es desde un punto fijo como éste.
      -->
      <p class="configurar-cookies">
        <button type="button" class="btn-govco link-btn-govco" @click="abrirBanner">
          Configurar cookies
        </button>
      </p>

      <!--
        La vía del pie a los ajustes de accesibilidad: **un enlace**, no un
        segundo juego de controles. Abre el mismo panel que el botón de la
        cabecera. El razonamiento completo está en el `script`, junto al
        `useAccesibilidad()`.
      -->
      <p class="configurar-cookies">
        <button type="button" class="btn-govco link-btn-govco" @click="abrirPanel">
          Ajustes de accesibilidad
        </button>
      </p>
    </div>

    <div class="second-section">
      <span class="govco-logo" aria-hidden="true" />
      <span class="separator" aria-hidden="true" />
      <span class="govco-co" aria-hidden="true" />
      <!-- El texto que dice lo que los dos logos sólo dicen con imagen. -->
      <span class="solo-lectores">
        GOV.CO, el portal del Estado colombiano. Marca país Colombia.
      </span>
    </div>
  </footer>
</template>

<style scoped>
/*
  Los encabezados del pie. El Kit da el tamaño y el color a `h4` y `h5`
  (`.pie-pagina-govco h4` y `.pie-pagina-govco h5` en `all.css`); al usar `h2` y
  `h3` por corrección semántica hay que reproducir esa apariencia —Cobalt, Nunito
  Sans Bold, 22 px y 20 px— y sus dos escalones móviles del Kit.
*/
.titulo-pie {
  margin-bottom: 1.875rem;
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
  font-size: 22px;
}

.subtitulo-pie {
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
  font-size: 20px;
}

/* El logo de la Entidad sustituye al `Logo-v2-MinTIC.png` que el Kit pone de
   ejemplo. Misma altura que el de la marca país (3rem, como el Kit) y sin
   deformación: el archivo puede llegar en cualquier proporción. */
.logo-entidad {
  height: 3rem;
  width: auto;
  object-fit: contain;
}

/* En el Kit, la columna de logos va a la derecha en escritorio y pasa a fila a la
   izquierda en pantalla estrecha, con el separador visible. El logo de la Entidad
   va al final de esa fila, y el escalón de tamaños acompaña al de los títulos. */
@media (max-width: 991px) {
  .titulo-pie {
    font-size: 20px;
  }

  .subtitulo-pie {
    font-size: 16px;
  }

  .logo-entidad {
    order: 1;
  }
}

/*
  Desborde en pantalla estrecha, medido y no supuesto: el Kit pone el dato de
  contacto y su enlace dentro de dos filas flex —`.contact-data-container li` y
  `.pie-pagina-govco p`— y un elemento flex no baja de su `min-width: auto`, que
  es el ancho de la palabra más larga. Con la dirección de notificaciones
  judiciales (~350 px a 15 px de cuerpo) el renglón se sale 7 px de la pantalla a
  390 px de ancho. `min-width: 0` deja encogerlo y `overflow-wrap` permite partir
  la dirección, que es lo único que puede partirse ahí.
*/
.contact-data-container :deep(li),
.contact-data-container :deep(p) {
  min-width: 0;
}

.contact-data-container :deep(a) {
  min-width: 0;
  overflow-wrap: anywhere;
}

/* Aviso de que las políticas aún no están publicadas. Se mantiene en el
   Matterhorn del Kit (8,59:1 sobre blanco) y no en un gris claro que incumpliría
   el contraste. El ancho y el alto se declaran explícitos porque el Kit pone
   `display: flex` a TODO párrafo del pie (`.pie-pagina-govco p`), y en una caja
   flex un bloque con `max-width` se encoge al contenido y centra el texto. */
.aviso-pendientes {
  width: 100%;
  max-width: 60ch;
  align-items: flex-start;
  margin: 1.5rem 0 0;
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-family: 'Verdana-Regular', system-ui, sans-serif;
  font-size: 15px;
}

/* El Kit no trae utilidad equivalente —no hay `visually-hidden` ni `sr-only` en
   `all.css`— y `display: none` no sirve: saca el texto del árbol de
   accesibilidad, que es justo lo contrario de lo que se busca. */
.solo-lectores {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
  border: 0;
}
</style>

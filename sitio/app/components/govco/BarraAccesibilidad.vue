<script setup lang="ts">
/**
 * Barra de accesibilidad (Kit UI, componente transversal 1).
 *
 * Ofrece contraste y tamaño de letra. Es obligatoria en todas las páginas y se
 * oculta en pantallas estrechas, como manda el Kit (`d-none d-lg-flex`).
 *
 * El comportamiento que el Kit resuelve con `script.js` vive aquí en el estado
 * reactivo de `useAccesibilidad`. Tres notas sobre por qué se aparta:
 *
 *  - El botón de contraste del Kit alterna `contrast-govco`, una clase cuyas
 *    dos reglas en `all.css` sólo afectan a su caja de demostración: en una
 *    página real no hace nada. Aquí hay un modo de alto contraste de verdad.
 *  - El tamaño de letra del Kit recorre todos los elementos y les escribe un
 *    `font-size` en línea. Aquí se escala la raíz, que además alcanza al
 *    contenido que se dibuja después.
 *  - El Kit fija la altura de esta barra en 8,938 rem, medida para **tres**
 *    botones. RF-B1-044 exige además el **enlace al Centro de Relevo** —el canal
 *    para la ciudadanía con discapacidad auditiva— y aquí se añade, junto con un
 *    botón de restablecer, así que la altura pasa a ser automática y cada
 *    control conserva un área activa de 44 px (RNF-B3-005), que el Kit dejaba en
 *    40. Es una desviación declarada del componente, como las del carrusel.
 *
 * **La barra se oculta por debajo de 992 px**, como pide CAG-07. Para que eso no
 * deje al ciudadano sin los ajustes en tablet ni en móvil —el propio corpus lo
 * advierte en RNF-07-D01 y HU-07-D02—, los mismos controles se publican con
 * etiqueta en el pie de página, que está en todas las páginas y a cualquier
 * ancho. La barra flotante es el atajo; el pie, la garantía.
 */
const {
  preferencias,
  alternarContraste,
  moverLetra,
  restablecer,
  puedeAumentar,
  puedeReducir,
} = useAccesibilidad()

// El Kit marca con `active` el último botón pulsado. Se conserva esa señal
// visual, y en el contraste se añade `aria-pressed`, que es lo que un lector de
// pantalla necesita para saber que es un interruptor y en qué posición está.
const ultimoPulsado = ref<'contraste' | 'reducir' | 'aumentar' | 'restablecer' | null>(null)

function alAlternarContraste() {
  ultimoPulsado.value = 'contraste'
  alternarContraste()
}

function alMoverLetra(paso: 1 | -1) {
  ultimoPulsado.value = paso > 0 ? 'aumentar' : 'reducir'
  moverLetra(paso)
}

function alRestablecer() {
  ultimoPulsado.value = 'restablecer'
  restablecer()
}
</script>

<template>
  <div class="posicion-barra-accesibilidad">
    <div class="barra-accesibilidad-govco d-none d-lg-flex">
      <button
        type="button"
        class="contrast"
        :class="{ active: ultimoPulsado === 'contraste' }"
        aria-label="Cambiar contraste"
        :aria-pressed="preferencias.contraste"
        @click="alAlternarContraste"
      >
        <span class="govco-contrast" aria-hidden="true" />
      </button>

      <button
        type="button"
        class="decrease-font-size"
        :class="{ active: ultimoPulsado === 'reducir' }"
        aria-label="Disminuir letra"
        :disabled="!puedeReducir"
        @click="alMoverLetra(-1)"
      >
        <span class="govco-font-minimize" aria-hidden="true" />
      </button>

      <button
        type="button"
        class="increase-font-size"
        :class="{ active: ultimoPulsado === 'aumentar' }"
        aria-label="Aumentar letra"
        :disabled="!puedeAumentar"
        @click="alMoverLetra(1)"
      >
        <span class="govco-font-maximize" aria-hidden="true" />
      </button>

      <!--
        Restablecer existe porque el ciudadano que sube la letra cinco pasos y
        activa el contraste tiene que poder volver de una vez, sin recorrer el
        camino al revés ni recargar. La función ya estaba en el estado compartido
        y no la usaba nadie.
      -->
      <button
        type="button"
        class="restablecer"
        :class="{ active: ultimoPulsado === 'restablecer' }"
        aria-label="Restablecer accesibilidad"
        @click="alRestablecer"
      >
        <span class="govco-restablecer" aria-hidden="true">A</span>
      </button>

      <!--
        El Centro de Relevo es el servicio del Estado que atiende a la ciudadanía
        con discapacidad auditiva por video-llamada. RF-B1-044 lo exige en esta
        barra: quien no puede oír el conmutador necesita encontrarlo donde busca
        los ajustes de accesibilidad, no en un pie de página.
      -->
      <a
        class="enlace-relevo"
        href="https://www.centroderelevo.gov.co"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Centro de Relevo: atención a la ciudadanía con discapacidad auditiva (abre en una pestaña nueva)"
      >
        <span class="govco-svg govco-universal-access" aria-hidden="true" />
      </a>
    </div>
  </div>
</template>

<style scoped>
/*
 * Posición fija a la derecha y centrada verticalmente, como el ejemplo del Kit.
 * El Kit lo resolvía con utilidades de Bootstrap (`position-fixed top-50
 * translate-middle-y`) más estilos en línea; aquí va en la hoja del componente,
 * que es donde corresponde y permite ajustarlo sin tocar la plantilla.
 */
.posicion-barra-accesibilidad {
  position: fixed;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1200;
}

/*
 * El Kit mide la barra para tres botones (8,938 rem). Con cinco controles esa
 * altura recortaría los dos últimos, así que se deja crecer. `min-height` en los
 * controles sube el área activa a los 44 px que exige RNF-B3-005 y que el Kit
 * dejaba en 40.
 */
.barra-accesibilidad-govco {
  height: auto;
  gap: 0.25rem;
}

.barra-accesibilidad-govco button,
.barra-accesibilidad-govco .enlace-relevo {
  min-height: 2.75rem;
}

/*
 * El icono de restablecer es texto y no un SVG del Kit: el Kit no tiene icono de
 * «volver al estado inicial» y dibujar uno inventado rompería la identidad. Una
 * letra A con la flecha implícita del propio botón dice lo mismo sin inventar
 * gráfica.
 */
.govco-restablecer {
  font-family: 'Nunito_Sans-SemiBold', system-ui, sans-serif;
  font-size: 0.875rem;
  color: var(--govcolor-white, #ffffff);
  filter: none;
}

/*
 * El icono del Centro de Relevo es el de accesibilidad universal del Kit
 * (`govco-svg govco-universal-access`, que ya trae su `assets/icons/universal-access.svg`).
 * El Kit dimensiona los iconos de SUS botones con una regla que no alcanza a un
 * enlace, así que aquí se repite el tamaño y se invierte el trazo a blanco: el
 * SVG viene en negro y sobre el cobalto no se vería.
 */
.barra-accesibilidad-govco .enlace-relevo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  background-color: var(--govcolor-cobalt, #0943b5);
  text-decoration: none;
}

.barra-accesibilidad-govco .enlace-relevo span {
  filter: invert(100%);
  background-repeat: no-repeat;
  background-position: center;
  background-size: 1rem 1rem;
  min-width: 1rem;
  min-height: 1rem;
  display: inline-block;
}

.barra-accesibilidad-govco .enlace-relevo:hover {
  background-color: var(--govcolor-havelock-lue, #4672c8);
}

.barra-accesibilidad-govco .enlace-relevo:focus-visible,
.barra-accesibilidad-govco button:focus-visible {
  outline: 0.188rem solid var(--govcolor-white, #ffffff);
  outline-offset: -0.188rem;
}
</style>

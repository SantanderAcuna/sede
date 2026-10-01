<script setup lang="ts">
/**
 * Botón circular de accesibilidad (brief §5.2.1).
 *
 * **Forma y sitio.** Es un círculo, está fijo y vive **centrado verticalmente en
 * el lado derecho** de la ventana, en todas las pantallas: móvil, tableta y
 * escritorio. Se descarta el botón con etiqueta dentro de la cabecera porque el
 * requisito es que el control esté siempre en el mismo sitio, sea cual sea el
 * ancho, y que sea reconocible de un vistazo por su forma.
 *
 * **Vive fuera del envoltorio filtrable.** Los modos de contraste «colores
 * invertidos» y «escala de grises» aplican un `filter` a `.contenido-filtrable`,
 * y un `filter` convierte a su elemento en bloque contenedor de los descendientes
 * con `position: fixed`; además invertiría los colores del propio botón. Por eso
 * la disposición lo monta fuera de ese envoltorio y **no** se teletransporta:
 * teletransportarlo lo colocaría en el documento antes del enlace de salto y
 * rompería RF-B3-022.
 *
 * **No choca con «Volver arriba».** Aquél vive en `bottom: 10%` y éste en el
 * centro vertical, así que ocupan franjas distintas del borde derecho. Son dos
 * módulos independientes: ninguno consulta dónde está el otro.
 *
 * **El nombre accesible es texto, aunque no se vea.** Un círculo no admite una
 * etiqueta visible al lado sin dejar de ser un círculo, así que el nombre viaja
 * en un texto recortado —no en un `aria-label`— para que exista también para
 * quien navega en modo lectura. Lleva `aria-haspopup` y `aria-expanded` para que
 * el producto de apoyo anuncie que abre un diálogo y si está abierto.
 *
 * **El icono es un SVG en línea con `fill="currentColor"`.** En modo alto
 * contraste las reglas del sitio fuerzan `background-color: transparent` a todo
 * lo que no sea `svg`/`path`: un icono pintado con `background-image`
 * desaparecería. Con `currentColor` hereda el color que imponga cada modo.
 *
 * **Criterios:** CAG-07 (barra de accesibilidad) · RF-B1-044 (barra persistente) ·
 * RNF-07-D01 (disponible y operable en tableta) · RNF-B3-005 (44 × 44 px) ·
 * CC12 (varias vías de acceso) · CC17 (foco visible).
 */
const { panelAbierto, abrirPanel } = useAccesibilidad()

/** Identificador del panel que este botón abre y anuncia con `aria-controls`. */
const ID_PANEL = 'panel-accesibilidad'
</script>

<template>
  <button
    type="button"
    class="boton-accesibilidad"
    aria-haspopup="dialog"
    :aria-expanded="panelAbierto"
    :aria-controls="ID_PANEL"
    @click="abrirPanel"
  >
    <!--
      El icono de accesibilidad universal es el del propio Kit
      (`assets/icons/universal-access.svg`), en línea y con el trazo en
      `currentColor` para que se pinte con el color del texto de cada modo.
    -->
    <svg
      class="boton-accesibilidad-icono"
      viewBox="0 0 40 40"
      aria-hidden="true"
      focusable="false"
    >
      <path
        fill="currentColor"
        d="M20,7.28a3.545,3.545,0,0,1-2.57-1.1,3.552,3.552,0,0,1,0-5.14A3.95,3.95,0,0,1,20,.06a3.545,3.545,0,0,1,2.57,1.1,3.491,3.491,0,0,1,1.1,2.57,3.545,3.545,0,0,1-1.1,2.57A3.95,3.95,0,0,1,20,7.28Zm16.02,4.16c-1.71.37-3.55.73-5.38.98s-3.79.49-5.75.61V38.71a1.106,1.106,0,0,1-.37.86,1.4,1.4,0,0,1-.98.37,1.265,1.265,0,0,1-1.23-1.23V26.97H17.54V38.71a1.106,1.106,0,0,1-.37.86,1.4,1.4,0,0,1-.98.37,1.265,1.265,0,0,1-1.23-1.23V13.03c-1.96-.12-3.91-.37-5.75-.61s-3.67-.61-5.38-.98a1.406,1.406,0,0,1-.86-.61,2.664,2.664,0,0,1,0-.98,1.406,1.406,0,0,1,.61-.86.938.938,0,0,1,.98-.12,53.854,53.854,0,0,0,7.58,1.22c2.57.24,5.14.37,7.82.37a78.268,78.268,0,0,0,7.82-.37,53.853,53.853,0,0,0,7.58-1.22,1.327,1.327,0,0,1,.98.12,2.172,2.172,0,0,1,.61.86,1.327,1.327,0,0,1-.12.98,1.379,1.379,0,0,1-.86.61Z"
      />
    </svg>
    <!--
      El nombre accesible, recortado y no oculto: `display: none` lo sacaría
      también del árbol de accesibilidad, que es justo lo contrario de lo que se
      busca. Es texto y no un `aria-label` para que exista además en modo lectura
      y para que el control por voz diga lo mismo que se anuncia.
    -->
    <span class="boton-accesibilidad-texto">Ajustes de accesibilidad</span>
  </button>
</template>

<style scoped>
/*
 * Un círculo, fijo y centrado verticalmente en el borde derecho. El tamaño es
 * una variable para que cada escalón de pantalla lo ajuste sin repetir reglas, y
 * nunca baja de 44 px, que es el mínimo táctil que exige RNF-B3-005.
 */
.boton-accesibilidad {
  --fab-tamano: 3.5rem;
  --fab-margen: 0.75rem;

  position: fixed;
  top: 50%;
  right: var(--fab-margen);
  transform: translateY(-50%);
  z-index: 1210;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: var(--fab-tamano);
  height: var(--fab-tamano);
  padding: 0;

  border: 0.125rem solid var(--govcolor-white, #ffffff);
  border-radius: 50%;
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  cursor: pointer;

  /* La sombra es lo que lo separa del contenido cuando pasa por encima, en
     cualquier modo de contraste: sin ella, sobre fondos claros el círculo se
     confunde con la página. */
  box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.28);
}

.boton-accesibilidad-icono {
  width: 55%;
  height: 55%;
  flex: none;
}

.boton-accesibilidad:hover {
  background-color: var(--govcolor-havelock-lue, #4672c8);
  transform: translateY(-50%) scale(1.06);
}

/*
 * Foco con doble anillo: el interior oscuro separa del amarillo, y el exterior
 * amarillo del brief se ve sobre fondos claros y oscuros. Un solo anillo no
 * sirve aquí porque el botón flota sobre contenido de color imprevisible.
 */
.boton-accesibilidad:focus-visible {
  outline: 0.1875rem solid #1a1a1a;
  outline-offset: 0.1875rem;
  box-shadow: 0 0 0 0.4375rem #ffbf00;
}

.boton-accesibilidad:active {
  transform: translateY(-50%) scale(0.97);
}

/* El texto es el nombre accesible; se recorta, no se oculta. */
.boton-accesibilidad-texto {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
  border: 0;
}

/*
 * Escalones de pantalla. El círculo encoge un poco en las pantallas estrechas
 * —donde cada píxel de ancho de contenido cuenta— pero nunca por debajo de los
 * 44 px de área activa, y se arrima al borde para no robar ancho de lectura.
 */
@media (max-width: 991px) {
  .boton-accesibilidad {
    --fab-tamano: 3.25rem;
    --fab-margen: 0.625rem;
  }
}

@media (max-width: 575px) {
  .boton-accesibilidad {
    --fab-tamano: 3rem;
    --fab-margen: 0.5rem;
  }
}

/* Pantallas muy bajas: se mantiene centrado, pero el tamaño no crece. */
@media (max-height: 480px) {
  .boton-accesibilidad {
    --fab-tamano: 2.875rem;
  }
}

/*
 * En modo alto contraste el botón tiene que seguir distinguiéndose. Las reglas
 * del sitio fuerzan fondo transparente y texto blanco a todo; el círculo
 * recupera fondo negro con borde blanco, que sobre el negro del modo se lee.
 */
:global(.contraste-govco) .boton-accesibilidad {
  background-color: #000 !important;
  border-color: #fff !important;
  color: #fff !important;
}

:global(.contraste-govco) .boton-accesibilidad:hover {
  background-color: #333 !important;
}
</style>

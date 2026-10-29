<script setup lang="ts">
/**
 * Botón de accesibilidad (brief §5.2.1). Abre el panel de ajustes.
 *
 * **Un círculo fijo, centrado verticalmente en el lado derecho y igual en todas
 * las pantallas** (plan del botón, D1, revisión 2, indicación del cliente del
 * 2026-10-01; es también lo que recomienda el Kit UI 9.2: «ubica el componente en
 * un lugar visible y de fácil acceso; preferiblemente, en el centro y a la
 * derecha»). El cliente **no aceptó** la alternativa de llevarlo a la cabecera por
 * debajo de 992 px (propuesta del 2026-10-29), así que no hay variante de cabecera
 * y no se oculta a ningún ancho.
 *
 * **No ocupa espacio.** Flota por encima del contenido (`position: fixed`,
 * `z-index` por encima de «Volver arriba» y de los menús): no reserva carril ni
 * estrecha ningún contenedor por debajo de 992 px. Desde 992 px, donde los
 * márgenes de Bootstrap ya son casi tan anchos como el círculo, los contenedores
 * se limitan de forma simétrica para que deje libre su columna (ver `sitio.css`,
 * «columna del círculo»).
 *
 * **Límite que no se puede quitar sin reservar espacio.** Un elemento fijo que no
 * reserva espacio y está a media altura pasa, al desplazarse, por encima de lo que
 * haya en el borde derecho. En pantallas de teléfono el contenido llega a 12 px de
 * ese borde, así que el círculo puede cubrir el final de alguna línea o un control
 * de ese borde mientras se desplaza. Reservar la columna (lo que hacía el carril
 * anterior) es lo que se descartó por «ocupar espacio». El tamaño baja a 44 px, el
 * mínimo táctil de RNF-B3-005, en teléfono para reducir lo que cubre.
 *
 * **Vive fuera del envoltorio filtrable.** Los modos de contraste «colores
 * invertidos» y «escala de grises» aplican un `filter` a `.contenido-filtrable`, y
 * un `filter` convierte a su elemento en bloque contenedor de los descendientes con
 * `position: fixed`; además invertiría los colores del propio botón. Por eso la
 * disposición monta el botón fuera de ese envoltorio y **no** lo teletransporta:
 * teletransportarlo lo colocaría antes del enlace de salto y rompería RF-B3-022. El
 * enlace del pie abre el mismo panel.
 *
 * **No choca con «Volver arriba».** Aquél vive en `bottom: 10%` y éste en el
 * centro vertical, así que ocupan franjas distintas del borde derecho.
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
 * RNF-07-D01 (disponible y operable en tableta) · HU-07-D02 (sin solape, con el
 * límite de arriba) · RNF-B3-005 (44 × 44 px) · CC12 (varias vías de acceso) ·
 * CC17 (foco visible).
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
 * Círculo cobalto con el icono del Kit. El tamaño es una variable y nunca baja de
 * 44 px, el mínimo táctil de RNF-B3-005.
 */
.boton-accesibilidad {
  --fab-tamano: 3.5rem;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;

  width: var(--fab-tamano);
  height: var(--fab-tamano);
  padding: 0;

  border: 0.125rem solid var(--govcolor-white, #ffffff);
  border-radius: 50%;
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  cursor: pointer;
}

.boton-accesibilidad-icono {
  width: 55%;
  height: 55%;
  flex: none;
}

.boton-accesibilidad:hover {
  background-color: var(--govcolor-havelock-lue, #4672c8);
}

/*
 * Foco con doble anillo: el interior oscuro separa del amarillo, y el exterior
 * amarillo del brief se ve sobre fondos claros y oscuros. Un solo anillo no
 * sirve porque el botón flota sobre contenido de color imprevisible.
 */
.boton-accesibilidad:focus-visible {
  outline: 0.1875rem solid #1a1a1a;
  outline-offset: 0.1875rem;
  box-shadow: 0 0 0 0.4375rem #ffbf00;
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
 * Fijo, centrado verticalmente en el borde derecho y por encima del contenido, a
 * cualquier ancho (D1). `z-index` 1210: sobre «Volver arriba» (1200) y los menús;
 * por debajo del banner de cookies y de los modales, que han de ganarle. La sombra
 * es lo que lo separa del contenido cuando pasa por encima, en cualquier modo de
 * contraste.
 */
.boton-accesibilidad {
  --fab-tamano: var(--acc-flotante-tamano);

  position: fixed;
  top: 50%;
  right: var(--acc-flotante-margen);
  transform: translateY(-50%);
  z-index: 1210;
  box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.28);
}

.boton-accesibilidad:hover {
  transform: translateY(-50%) scale(1.06);
}

.boton-accesibilidad:active {
  transform: translateY(-50%) scale(0.97);
}

/* Teléfono y pantallas muy bajas: 44 px, el mínimo táctil, para cubrir lo menos posible. */
@media (max-width: 575px), (max-height: 480px) {
  .boton-accesibilidad {
    --fab-tamano: 2.75rem;
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

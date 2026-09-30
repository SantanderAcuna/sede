<script setup lang="ts">
/**
 * Cabecera de la sede electrónica (Kit UI, componente transversal 3).
 *
 * Es la franja blanca con el logotipo de la Entidad que abre la página, por
 * debajo de la barra superior del Estado y por encima del menú de navegación.
 * Sigue el marcado de `transversal/cabecera.html` y no tiene comportamiento
 * propio: el Kit tampoco se lo da —`cabecera.js` sólo reexporta las funciones
 * del menú, que pertenecen a otro componente—.
 *
 * Lo que se aparta del ejemplo, y por qué:
 *
 *  - **No vuelve a pintar la barra superior del Estado.** El ejemplo la mete
 *    dentro de la cabecera porque es una página suelta, pero en este sitio ya
 *    existe `BarraSuperior.vue`; repetirla dejaría dos franjas azules seguidas.
 *  - **No reproduce el buscador.** El suyo vive en otro componente: aquí queda
 *    el hueco donde va, que es lo que a la cabecera le corresponde —la
 *    posición—, expuesto como slot `buscador`.
 *  - **El logotipo es el de la Entidad**, no los del Gobierno Nacional
 *    (`logo_potencia`, `logo_ministerio`). Una sede electrónica territorial se
 *    identifica con su propia autoridad, y ésos son marcas de la Nación, no del
 *    Distrito.
 *  - **La imagen no lleva la clase `logo_potencia`.** El Kit le asigna
 *    `content: url('../assets/images/Colombia-Potencia.png')`, y `content` sobre
 *    un `<img>` sustituye su fuente: el logotipo de la Entidad no llegaría a
 *    verse nunca.
 *  - **Se omite `barra-inferior-desktop`** del ejemplo: no tiene ni una regla en
 *    `all.css` ni en `cabecera.css`. Es un marcador muerto.
 *  - **La línea inferior es la del móvil.** El Kit la marca con `d-md-none`
 *    porque en escritorio se la cede al borde inferior del menú que venga justo
 *    debajo (`cabecera-govco + .menu-govco.navbar`, que pinta la franja naranja
 *    bajo la barra de navegación). Se respeta tal cual: pintarla siempre dejaría
 *    dos franjas naranjas con la gris del menú en medio. La contrapartida es que
 *    esta cabecera espera que un `MenuNavegacionGovco` la siga; sin él, en
 *    escritorio se queda sin línea.
 *
 * El enlace de salto vive aquí y no en cada página porque tiene que ser lo
 * primero enfocable del documento en todas ellas (WCAG 2.4.1, CAG-08). Sus
 * clases `sr-only sr-only-focusable` son las que pide la norma, pero **no las
 * define ninguna hoja del sitio**: `all.css` no las trae y el Bootstrap que el
 * Kit da por hecho tampoco —Bootstrap 5 las renombró a `visually-hidden`—, de
 * modo que se implementan al final del archivo. Sin eso, el enlace quedaría
 * siempre visible encima de la cabecera.
 *
 * El destino del salto debe existir en la página (`id="contenido-principal"`) y
 * conviene que sea enfocable (`tabindex="-1"`): si no, hay navegadores que mueven
 * el foco al `<body>` y el salto no sirve de nada.
 */
interface Props {
  /** Ruta del logotipo de la Entidad. */
  logotipo?: string
  /** Texto alternativo del logotipo: es su nombre accesible, no un adorno. */
  logotipoAlt?: string
  /** Destino del enlace de salto dentro de la página. */
  destinoContenido?: string
}

const {
  logotipo = '/logo-entidad.png',
  logotipoAlt = 'Alcaldía Distrital de Santa Marta',
  destinoContenido = '#contenido-principal',
} = defineProps<Props>()
</script>

<template>
  <a class="sr-only sr-only-focusable" :href="destinoContenido">
    Saltar al contenido principal
  </a>

  <div class="cabecera-govco">
    <div class="barra-inferior-govco">
      <div class="barra-logos-govco">
        <!--
          El logotipo enlaza a la portada: es lo que cualquiera espera al pulsar
          el emblema de la Entidad, y da al sitio una salida al inicio desde
          cualquier página. El texto alternativo nombra la Entidad, que es lo que
          describe el destino del enlace.
        -->
        <NuxtLink to="/" class="enlace-logotipo-govco">
          <img class="logotipo-entidad" :src="logotipo" :alt="logotipoAlt" decoding="async" />
        </NuxtLink>

        <!-- Hueco del buscador. Va entre los logotipos porque el Kit lo alinea a
             la derecha de la barra con `justify-content: space-between`. -->
        <slot name="buscador" />
      </div>
    </div>

    <div class="border-bottom-govco d-md-none" />
  </div>
</template>

<style scoped>
/*
 * `sr-only` y `sr-only-focusable` sacan el elemento de la vista sin sacarlo del
 * árbol de accesibilidad, y lo devuelven a su sitio al recibir el foco. Se
 * implementan con `clip` y no con `display: none` a propósito: `display: none`
 * lo borraría también para quien usa lector de pantalla, y el enlace de salto
 * existe precisamente para esa persona.
 */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/*
 * Al aparecer tiene que leerse, y ahí arriba compite con la barra azul del
 * Estado y con la blanca de la cabecera: fondo claro, color institucional y
 * subrayado. Se queda en el flujo (`position: relative`, no `fixed`) para que
 * empuje la página hacia abajo mientras está visible, que es como se comporta un
 * enlace de salto y como lo espera quien lo usa.
 */
.sr-only-focusable:active,
.sr-only-focusable:focus {
  position: relative;
  z-index: 1100;
  display: block;
  width: auto;
  height: auto;
  padding: 0.75rem 1rem;
  margin: 0;
  overflow: visible;
  clip: auto;
  white-space: normal;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Verdana-Bold', Verdana, sans-serif;
  text-decoration: underline;
  outline: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: -0.125rem;
}

.enlace-logotipo-govco {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
}

/*
 * Las alturas son las del Kit: 48 px y, por debajo de 992 px, 40 px. El ancho va
 * en `auto` para que la proporción la ponga el archivo que se deje en esa ruta,
 * sin obligar a conocer sus dimensiones de antemano.
 */
.logotipo-entidad {
  display: block;
  height: 48px;
  width: auto;
}

@media (max-width: 991px) {
  .logotipo-entidad {
    height: 40px;
  }
}
</style>

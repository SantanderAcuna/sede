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
 * **Aquí ya no está el enlace de salto.** Vivía en este componente, y como la
 * cabecera se monta después de la barra superior y de la barra de accesibilidad,
 * el primer tabulador de la página era el enlace a GOV.CO: el atajo llegaba tras
 * ocho o nueve paradas, que es justo lo que existe para evitar (RF-B3-022 exige
 * que sea el primer elemento tabulable). Se movió a la disposición, antes de
 * `<BarraSuperior />`, junto con sus estilos `sr-only sr-only-focusable`.
 */
interface Props {
  /** Ruta del logotipo de la Entidad. */
  logotipo?: string
  /** Texto alternativo del logotipo: es su nombre accesible, no un adorno. */
  logotipoAlt?: string
}

const {
  logotipo = '/logo-entidad.png',
  logotipoAlt = 'Alcaldía Distrital de Santa Marta',
} = defineProps<Props>()
</script>

<template>
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

        <!--
          Hueco del buscador. Va entre los logotipos porque el Kit lo alinea a
          la derecha de la barra con `justify-content: space-between`.

          En escritorio se acota con `hueco-buscador` (regla más abajo) para que
          no ocupe todo el ancho y se vea centrado: la barra mide mucho y el
          campo a 100 % descuadra el conjunto. En móvil el buscador pasa al menú
          desplegable y este hueco ya ni se monta, pero por si el consumidor lo
          reutiliza en otra barra estrecha, se deja al 100 % por debajo de
          992 px.
        -->
        <div class="hueco-buscador">
          <slot name="buscador" />
        </div>

        <!--
          Hueco para las acciones de la cabecera. Lo pide el Anexo 2.1, página 6,
          que dibuja la cabecera de la sede con el logotipo de la autoridad, el
          buscador **y el enlace «Iniciar Sesión»**. Va aquí, después del
          buscador, porque así queda a la derecha de la barra, que es donde el
          propio anexo lo sitúa en su maqueta.

          Se expone como hueco y no se cablea dentro porque la cabecera no sabe
          —ni debe— qué hace el enlace: quien la usa decide a dónde lleva.
        -->
        <slot name="acciones" />
      </div>
    </div>

    <div class="border-bottom-govco d-md-none" />
  </div>
</template>

<style scoped>
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

/*
 * Buscador de la cabecera: ancho acotado y centrado sólo en pantallas grandes.
 *
 * **Por qué se acota.** El Kit pone el buscador entre el logotipo y las
 * acciones con `justify-content: space-between`, lo que hace que el campo
 * ocupe todo el hueco intermedio: en escritorios anchos termina midiendo más
 * de 800 px, con el texto del placeholder perdido a la izquierda y un botón de
 * búsqueda flotando muy a la derecha. Un campo de búsqueda no necesita más
 * de ~28 rem para ser cómodo, y acotarlo libera el resto del ancho para que la
 * galería de aplicaciones no compita con él.
 *
 * **Por qué se centra.** Con `justify-content: space-between` la barra reparte
 * tres bloques —logo, buscador, acciones— y queda forzado a los extremos;
 * centrar el buscador rompe ese reparto y queda más equilibrado. El logo
 * queda a la izquierda y las acciones a la derecha, como manda el Anexo 2.1.
 *
 * **Por qué sólo en pantallas grandes.** Por debajo de 992 px el buscador se
 * va al menú desplegable y este contenedor ni se renderiza, pero si algún día
 * se reutiliza el slot en otra barra estrecha, se mantiene al 100 % para no
 * comprimir el campo por debajo del mínimo táctil de 44 px de alto.
 */
.hueco-buscador {
  /* Por defecto ocupa todo el ancho disponible: el buscador manda en su
     contenedor y nunca debe salirse de él. El `min-width: 0` deja que el
     `<input>` interno pueda encoger por debajo de su ancho intrínseco
     (tamaño de `placeholder`), que es lo que hace que en móvil no se salga. */
  flex: 1 1 auto;
  min-width: 0;
  max-width: 100%;
}

@media (min-width: 992px) {
  .hueco-buscador {
    /* 28 rem ≈ 448 px: cómodo para un campo de búsqueda sin dominar la barra.
       `flex: 0 0 auto` evita que crezca o se encoja; el `margin-inline: auto`
       lo centra en el hueco que le deja el `space-between` del padre. */
    flex: 0 0 auto;
    width: 28rem;
    max-width: 28rem;
    margin-inline: auto;
  }
}
</style>

<script setup lang="ts">
/**
 * Disposición de todo el sitio público.
 *
 * Monta las piezas que el Kit UI marca como obligatorias en **todas** las
 * páginas (sección 2.5 del expediente): barra de accesibilidad, barra superior
 * del Estado, cabecera, menú de navegación, pie de página y volver arriba.
 *
 * El `<main>` vive aquí y no en cada página. El criterio CAG-08 exige un enlace
 * «Saltar al contenido principal» que apunte a `#contenido-principal`, y ese
 * destino tiene que existir en todas las páginas: si cada vista declarara su
 * propio `<main>`, bastaría con que una lo olvidara para romper el enlace en
 * esa página, y el fallo no se vería. Con el destino en la disposición, el
 * enlace no puede quedar huérfano.
 */
import type { MenuPrincipal } from '~/types/menu'

/**
 * Las tres secciones obligatorias de FUN-013, más el inicio.
 *
 * El menú no se inventa: sale de los criterios funcionales del expediente.
 * FUN-013 obliga a publicar **Transparencia y acceso a la información
 * pública**, **Servicios a la Ciudadanía** y **Participa**; FUN-026 y FUN-027
 * desdoblan Participa en **Noticias** y **Portales de programas transversales**.
 *
 * No se añaden subsecciones que el expediente no defina. Inventarlas aquí
 * obligaría después a deshacerlas, y mientras tanto anunciarían contenido que
 * no existe. FUN-012 limita el menú a siete opciones y a dos niveles: aquí hay
 * cuatro y un solo desplegable.
 */
const menu: MenuPrincipal = [
  { etiqueta: 'Inicio', ruta: '/' },
  { etiqueta: 'Transparencia', ruta: '/transparencia' },
  { etiqueta: 'Servicios a la Ciudadanía', ruta: '/servicios' },
  {
    etiqueta: 'Participa',
    subsecciones: [
      {
        titulo: 'Participa',
        enlaces: [
          { etiqueta: 'Noticias', ruta: '/noticias' },
          { etiqueta: 'Portales de programas transversales', ruta: '/portales' },
        ],
      },
    ],
  },
]
</script>

<template>
  <div class="disposicion-sitio">
    <BarraSuperior />
    <BarraAccesibilidad />

    <CabeceraGovco destino-contenido="#contenido-principal" />

    <MenuNavegacionGovco :items="menu" etiqueta-accesible="Menú principal de la Sede Electrónica" />

    <!--
      `tabindex="-1"` permite que el enlace de salto mueva el foco aquí. Sin él,
      el navegador desplaza la vista pero deja el foco donde estaba, y quien
      navega con teclado sigue tabulando desde la cabecera: el salto no serviría
      de nada.
    -->
    <main id="contenido-principal" tabindex="-1">
      <slot />
    </main>

    <PiePaginaGovco />
    <VolverArriba />
  </div>
</template>

<style scoped>
.disposicion-sitio {
  display: flex;
  flex-direction: column;
  /* El pie queda abajo aunque la página tenga poco contenido, sin flotar a
     media altura. */
  min-height: 100vh;
}

#contenido-principal {
  flex: 1;
}

/*
  El foco no debe dibujar un recuadro alrededor de TODO el contenido al saltar:
  el destino es un ancla de teclado, no un control. El foco sigue yendo ahí —que
  es lo que lo hace útil— pero sin marco.
*/
#contenido-principal:focus {
  outline: none;
}
</style>

<script setup lang="ts">
/**
 * Barra superior del Estado (Kit UI, componente transversal 2).
 *
 * Franja azul cobalto con el logotipo de GOV.CO, que enlaza al Portal Único.
 * Es obligatoria en todas las páginas y va por encima de la cabecera.
 *
 * A la derecha de la franja se sitúa el botón de **Iniciar sesión**, con un
 * icono de usuario blanco (equivalente visual a `bi bi-person-circle`) y sin
 * caja ni relieve: la barra ya es azul y el icono contrasta por sí solo.
 *
 * **Sin botón de cambio de idioma, y es deliberado.** El Kit lo ofrece como
 * opcional, y el sitio es monolingüe: un conmutador que no cambia nada engaña
 * más de lo que ayuda. El día que haya un segundo idioma se añade aquí, con su
 * `aria-label` y su preferencia persistida (CAG-06).
 */
</script>

<template>
  <div class="barra-superior-govco">
    <a
      href="https://www.gov.co/"
      target="_blank"
      rel="noopener"
      aria-label="Portal del Estado Colombiano - GOV.CO"
    />
    <NuxtLink
      to="/admin/acceso"
      class="boton-login"
      aria-label="Iniciar sesión"
    >
      <!--
        SVG inline equivalente a `bi bi-person-circle`:
        - fill="none" + stroke="white"  → outline, sin relleno sólido
        - stroke-width 2 para que el trazo se lea claro sobre el azul de la barra
        - El stroke white sobre el fondo azul de la barra garantiza contraste
      -->
      <svg
        class="icono-login"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        aria-hidden="true"
        focusable="false"
      >
        <circle cx="20" cy="20" r="17.5" stroke="white" stroke-width="2" fill="none" />
        <circle cx="20" cy="15" r="5" stroke="white" stroke-width="2" fill="none" />
        <path
          d="M8 33.5c0-6.627 5.373-12 12-12s12 5.373 12 12"
          stroke="white"
          stroke-width="2"
          stroke-linecap="round"
          fill="none"
        />
      </svg>
    </NuxtLink>
  </div>
</template>

<style scoped>
/*
 * El botón de inicio de sesión es **sólo el icono outline**: la barra superior
 * ya tiene fondo azul cobalto y un círculo blanco encima sería ruido visual.
 *
 * El icono es un SVG inline equivalente a `bi bi-person-circle` con stroke
 * blanco y sin relleno — exactamente lo que el usuario pidió. El color sale
 * del atributo `stroke="white"` del SVG, no de CSS, así que no hay conflicto
 * con las reglas del Kit.
 */
.boton-login {
  /*
   * **Punto crítico.** El Kit aplica `content: url(assets/images/logo.svg)` a
   * **todos** los `<a>` dentro de `.barra-superior-govco` —y este botón es un
   * `<NuxtLink>`, que se renderiza como `<a>`. Sin neutralizar esa regla, el
   * logo de GOV.CO se pintaba encima del icono de login y se veían dos escudos
   * en la misma franja. `content: none !important` lo desactiva sin tocar el
   * CSS del Kit. La especificidad de `.barra-superior-govco a` (0,1,1) ya la
   * supera `.boton-login[data-v-xxx]` (0,2,0) por el atributo que añade Vue, pero
   * el `!important` lo deja a salvo de cualquier reordenación del Kit.
   */
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  background: transparent;
  text-decoration: none;
  border-radius: 0.25rem;
  transition: transform 0.2s ease;
  /* Anulación de `content: url(assets/images/logo.svg)` del Kit sobre `<a>`. */
  content: none !important;
}

.boton-login:hover {
  transform: translateY(-50%) scale(1.08);
}

.boton-login:focus-visible {
  outline: 0.125rem solid white;
  outline-offset: 0.125rem;
}

.boton-login:active {
  transform: translateY(-50%) scale(0.96);
}

/*
 * Dimensiones del icono SVG inline. El SVG es cuadrado 40×40 viewBox; lo
 * escalamos a 1.75rem para que se lea claro sin dominar la barra
 * (3.5rem de alto).
 */
.icono-login {
  width: 1.75rem;
  height: 1.75rem;
  display: block;
}
</style>
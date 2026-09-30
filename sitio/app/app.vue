<template>
  <!--
    El armazón del sitio. Ahora renderiza la página que corresponda a la ruta, en
    lugar de la pantalla de bienvenida de la plantilla: con `<NuxtWelcome />` y sin
    carpeta de páginas, Nuxt respondía lo mismo con estado 200 para CUALQUIER
    dirección.
  -->
  <NuxtRouteAnnouncer />
  <NuxtPage />
</template>

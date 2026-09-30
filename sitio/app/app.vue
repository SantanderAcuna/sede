<template>
  <!--
    El armazón del sitio. Renderiza la página que corresponda a la ruta.

    `<NuxtLayout>` NO es opcional, y su ausencia no protesta: al declarar un
    `app.vue` propio, Nuxt deja de aplicar las disposiciones por su cuenta. Sin
    esta etiqueta, `layouts/default.vue` se compila pero no envuelve nada, y la
    página sale sin cabecera, sin menú y sin pie —con un 200 y sin un solo error
    en consola—. Es exactamente el fallo que tenía: todo respondía bien y no se
    veía ninguna de las piezas comunes.

    El nombre se deja sin argumento a propósito: Nuxt resuelve `default`.
  -->
  <NuxtRouteAnnouncer />
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

<script setup lang="ts">
/**
 * Disposición del panel autenticado.
 *
 * Sólo estructura. El menú lateral y la cabecera llegan con los módulos
 * editoriales, cuando haya algo que mostrar en ellos.
 */
</script>

<template>
  <div class="disposicion-panel">
    <main id="contenido-principal" role="main">
      <RouterView />
    </main>
  </div>
</template>

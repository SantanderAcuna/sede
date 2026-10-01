<script setup lang="ts">
/**
 * Verificación pública de un documento.
 *
 * La ruta es obligatoria (mapa de rutas, sección 9.2 del plan), pero el servicio
 * que la resuelve **no existe todavía**. Esta página lo dice y muestra el código
 * que llegó, en lugar de simular una comprobación.
 *
 * **Por qué no se simula.** Una verificación falsa es la peor clase de contenido
 * de relleno: alguien que recibe un documento con un código y comprueba aquí que
 * «es válido» daría por bueno un documento que nadie ha certificado. Mientras el
 * servicio no esté construido, esta página no certifica nada y lo advierte.
 */
const ruta = useRoute()

/**
 * El código tal como llegó en la dirección.
 *
 * `params` puede traer un arreglo cuando la ruta se declara con comodines; se
 * normaliza a cadena para que la plantilla imprima el código y no una lista.
 * Se recorta el espacio sobrante porque un código copiado y pegado suele traerlo.
 */
const codigo = computed<string>(() => {
  const valor = ruta.params.codigo
  const primero = Array.isArray(valor) ? valor[0] : valor
  return (primero ?? '').trim()
})

useHead({
  title: 'Verificación de un documento · Sede Electrónica',
  // Una dirección de verificación lleva el código de un documento concreto:
  // indexarla publicaría en los buscadores códigos que el ciudadano no ha
  // decidido hacer públicos.
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})
</script>

<template>
  <div class="container py-5">
    <h1>Verificación de un documento</h1>

    <div class="row">
      <div class="col-lg-8">
        <p class="lead">
          Comprobó el código <code class="codigo"> {{ codigo }} </code>
        </p>

        <div class="aviso-verificacion" role="status">
          <p class="mb-0">
            <strong>La verificación pública todavía no está disponible.</strong>
            Esta página no certifica la validez de ningún documento. No dé por
            auténtico un documento sólo porque esta dirección exista.
          </p>
        </div>

        <p class="mt-4">
          Cuando el servicio esté construido, aquí se podrá comprobar si un documento
          fue expedido por la Alcaldía Distrital de Santa Marta y si su contenido
          coincide con el que la Entidad emitió. Hasta entonces, para confirmar la
          autenticidad de un documento, use los canales de atención publicados.
        </p>

        <p>
          <NuxtLink class="btn btn-outline-primary" to="/atencion">
            Ver los canales de atención
          </NuxtLink>
        </p>

        <p class="mb-0">
          <NuxtLink to="/">Volver a la portada</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
  El código llega por la dirección y puede ser una cadena larguísima sin
  espacios. Sin `overflow-wrap`, una cadena así se sale de la columna en
  pantalla estrecha y provoca desplazamiento horizontal, que es justo lo que
  WCAG 1.4.10 prohíbe. Se parte donde haga falta y no se recorta: recortarlo
  ocultaría parte de lo que el ciudadano necesita comparar.
*/
.codigo {
  overflow-wrap: anywhere;
}

/*
  Aviso de que la verificación no existe. Mismos tokens y mismo criterio de
  contraste que el aviso de `SeccionEnPreparacion`: el amarillo institucional
  (`--govcolor-vis-vis`) con el Matterhorn para el texto da 7,6:1, por encima del
  4,5:1 que exige WCAG 2.1 AA.
*/
.aviso-verificacion {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border-left: 4px solid var(--govcolor-golden-brown, #9d7700);
  background-color: var(--govcolor-vis-vis, #fee697);
  color: var(--govcolor-matterhorn, #4c4c4c);
}
</style>

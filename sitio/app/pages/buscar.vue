<script setup lang="ts">
/**
 * Resultados de búsqueda.
 *
 * La Sede tiene buscador **interno** —usar uno externo está prohibido por el
 * expediente—, pero el índice todavía no existe: se construye cuando haya
 * contenido que indexar. Esta página recibe el término y lo dice con claridad,
 * en lugar de devolver una lista vacía que parecería un fallo.
 */
const ruta = useRoute()
const enrutador = useRouter()

/** El término buscado, tal como llegó. Puede estar ausente si se entra directo. */
const termino = computed(() => {
  const valor = ruta.query.q
  return typeof valor === 'string' ? valor.trim() : ''
})

useHead({
  title: termino.value
    ? `Búsqueda: ${termino.value} · Sede Electrónica`
    : 'Buscar · Sede Electrónica',
  // Una página de resultados no aporta nada a un buscador externo y puede
  // generar direcciones infinitas a partir de cualquier término.
  meta: [{ name: 'robots', content: 'noindex, follow' }],
})

function alBuscar(nuevo: string) {
  enrutador.push({ path: '/buscar', query: { q: nuevo } })
}
</script>

<template>
  <div class="container py-5">
    <h1>Buscar en la Sede</h1>

    <BuscadorGovco :valor-inicial="termino" class="mb-4" @buscar="alBuscar" />

    <div v-if="termino" class="row">
      <div class="col-lg-8">
        <p>
          Ha buscado: <strong>{{ termino }}</strong>
        </p>
        <p class="mb-0">
          El buscador todavía no tiene contenido que consultar: el índice se construye a
          medida que se publican los trámites y los documentos de la Entidad.
        </p>
      </div>
    </div>

    <div v-else class="row">
      <div class="col-lg-8">
        <p class="mb-0">
          Escriba lo que busca en el campo de arriba. Puede buscar un trámite, un servicio
          o un documento.
        </p>
      </div>
    </div>

    <p class="mt-4 mb-0">
      <a class="btn btn-outline-primary" href="/">Volver a la portada</a>
    </p>
  </div>
</template>

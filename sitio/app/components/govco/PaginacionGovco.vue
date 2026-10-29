<script setup lang="ts">
/**
 * Paginación del Kit UI (RF-B3-075): «Anterior 1 2 … 20 Siguiente».
 *
 * Sale de `pages/tramites/index.vue`, donde nació; se extrajo para poder
 * probarla sola y para que la próxima lista paginada no la reescriba.
 *
 * Lo que exige el requisito:
 *
 *  - **`aria-current="page"`** en la página en que se está, y sólo en ella.
 *  - **Elipsis** cuando hay muchas páginas, que es un separador decorativo
 *    (`aria-hidden`): no es un control y no debe leerse como uno.
 *  - **Área activa de al menos 44 × 44 px** en cada control. Se fija en
 *    `sitio.css` (`.paginacion-govco .page-link`); jsdom no mide, así que esa
 *    medida la comprueba el navegador (`tests/diseno.mjs`).
 *
 * Se dibuja **también con una sola página**, con los dos extremos deshabilitados:
 * el Anexo pide que el mecanismo y sus elementos secundarios aparezcan, y
 * esconderlos con pocos resultados daría a entender que la sección no los tiene.
 */
import { computed } from 'vue'

interface Props {
  paginaActual: number
  totalPaginas: number
  /** Cuántas páginas se muestran a cada lado de la actual. */
  alrededor?: number
  etiqueta?: string
  /** Apaga «Anterior» y «Siguiente» (por ejemplo, mientras se carga o si falló). */
  extremosApagados?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  alrededor: 1,
  etiqueta: 'Paginación de los resultados',
  extremosApagados: false,
})
const emit = defineEmits<{ (e: 'cambiar', pagina: number): void }>()

type Hueco =
  | { tipo: 'pagina'; numero: number; clave: string }
  | { tipo: 'separador'; clave: string }

const total = computed(() => Math.max(1, Math.floor(props.totalPaginas)))
/** La actual siempre dentro del total: al filtrar, la pedida puede quedar fuera. */
const actual = computed(() => Math.min(Math.max(Math.floor(props.paginaActual), 1), total.value))

const hayAnterior = computed(() => !props.extremosApagados && actual.value > 1)
const haySiguiente = computed(() => !props.extremosApagados && actual.value < total.value)

const huecos = computed<Hueco[]>(() => {
  const holgura = props.alrededor * 2 + 1

  if (total.value <= holgura + 2) {
    return Array.from({ length: total.value }, (_sinUso, indice) => ({
      tipo: 'pagina' as const,
      numero: indice + 1,
      clave: `pagina-${indice + 1}`,
    }))
  }

  const salida: Hueco[] = [{ tipo: 'pagina', numero: 1, clave: 'pagina-1' }]
  const desde = Math.max(2, actual.value - props.alrededor)
  const hasta = Math.min(total.value - 1, actual.value + props.alrededor)

  if (desde > 2) salida.push({ tipo: 'separador', clave: 'separador-inicial' })
  for (let numero = desde; numero <= hasta; numero += 1) {
    salida.push({ tipo: 'pagina', numero, clave: `pagina-${numero}` })
  }
  if (hasta < total.value - 1) salida.push({ tipo: 'separador', clave: 'separador-final' })
  salida.push({ tipo: 'pagina', numero: total.value, clave: `pagina-${total.value}` })

  return salida
})

function ir(numero: number): void {
  const destino = Math.min(Math.max(numero, 1), total.value)
  if (destino !== actual.value) emit('cambiar', destino)
}
</script>

<template>
  <nav :aria-label="etiqueta">
    <ul class="pagination paginacion-govco mb-0">
      <li class="page-item" :class="{ disabled: !hayAnterior }">
        <button type="button" class="page-link" :disabled="!hayAnterior" @click="ir(actual - 1)">
          Anterior
        </button>
      </li>
      <li
        v-for="hueco in huecos"
        :key="hueco.clave"
        class="page-item"
        :class="{ active: hueco.tipo === 'pagina' && hueco.numero === actual }"
      >
        <button
          v-if="hueco.tipo === 'pagina'"
          type="button"
          class="page-link"
          :aria-current="hueco.numero === actual ? 'page' : undefined"
          @click="ir(hueco.numero)"
        >
          {{ hueco.numero }}
        </button>
        <span v-else class="page-link" aria-hidden="true">…</span>
      </li>
      <li class="page-item" :class="{ disabled: !haySiguiente }">
        <button type="button" class="page-link" :disabled="!haySiguiente" @click="ir(actual + 1)">
          Siguiente
        </button>
      </li>
    </ul>
  </nav>
</template>

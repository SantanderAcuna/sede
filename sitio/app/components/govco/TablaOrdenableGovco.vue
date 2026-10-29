<script setup lang="ts">
/**
 * Tabla con ordenamiento por columna (RF-B3-076).
 *
 * El requisito: que la persona pueda ordenar por una columna, de menor a mayor y
 * al revés. Lo que hace que sea accesible y no sólo clicable:
 *
 *  - **`aria-sort` en el `<th>`** de la columna ordenable: `ascending`,
 *    `descending` o `none`. Es lo que anuncia el lector de pantalla («ordenado
 *    de forma ascendente»). El control es un `<button>` dentro del `<th>`, que
 *    es el patrón que recomienda la guía de prácticas de WAI-ARIA para tablas
 *    ordenables.
 *  - **Un aviso hablado** del resultado (región `role="status"` oculta a la
 *    vista): cambiar el orden mueve las filas, y sin aviso quien no ve la tabla
 *    no sabe si el clic hizo algo.
 *  - **Ordenar no cambia los datos** ni su identidad: las filas llevan `id`
 *    estable y el orden se calcula sobre una copia.
 *
 * Cada celda trae su `texto` y, si el texto no sirve para ordenar, un `orden`
 * aparte: «Quince (15) días…» se ordena por 15, no por la «Q» de Quince.
 *
 * **Límite honesto:** una tabla de tres filas apenas necesita ordenarse. Se
 * ordena porque el requisito lo pide para las tablas públicas y porque el
 * componente va a servir cuando haya tablas largas (normativa, vigencias); no
 * porque ordenar tres filas le cambie la vida a alguien.
 */
import { computed, ref } from 'vue'

import type { CeldaTabla, ColumnaTabla, FilaTabla } from '../../utils/tabla-ordenable'

type Direccion = 'ascending' | 'descending'

interface Props {
  columnas: ColumnaTabla[]
  filas: FilaTabla[]
  leyenda: string
  /** Clave de la columna cuyas celdas son el encabezado de su fila (`th scope="row"`). */
  columnaEncabezadoFila?: string
}

const props = withDefaults(defineProps<Props>(), { columnaEncabezadoFila: undefined })

const columnaActiva = ref<string | null>(null)
const direccion = ref<Direccion>('ascending')
const anuncio = ref('')

const comparador = new Intl.Collator('es', { numeric: true, sensitivity: 'base' })

function valorDeOrden(celda: CeldaTabla | undefined): number | string {
  if (celda === undefined) return ''
  return celda.orden ?? celda.texto
}

function comparar(a: number | string, b: number | string): number {
  if (typeof a === 'number' && typeof b === 'number') return a - b
  return comparador.compare(String(a), String(b))
}

const filasOrdenadas = computed<FilaTabla[]>(() => {
  const clave = columnaActiva.value
  if (clave === null) return props.filas
  const signo = direccion.value === 'ascending' ? 1 : -1
  // `sort` es estable: las filas que empatan conservan el orden con que llegaron.
  return [...props.filas].sort(
    (a, b) => signo * comparar(valorDeOrden(a.celdas[clave]), valorDeOrden(b.celdas[clave])),
  )
})

function estadoDe(columna: ColumnaTabla): Direccion | 'none' | undefined {
  if (columna.ordenable !== true) return undefined
  return columnaActiva.value === columna.clave ? direccion.value : 'none'
}

function ordenarPor(columna: ColumnaTabla): void {
  if (columna.ordenable !== true) return
  if (columnaActiva.value === columna.clave) {
    direccion.value = direccion.value === 'ascending' ? 'descending' : 'ascending'
  } else {
    columnaActiva.value = columna.clave
    direccion.value = 'ascending'
  }
  anuncio.value = `Tabla ordenada por ${columna.titulo}, de forma ${
    direccion.value === 'ascending' ? 'ascendente' : 'descendente'
  }.`
}

function iconoDe(columna: ColumnaTabla): string {
  const estado = estadoDe(columna)
  if (estado === 'ascending') return 'govco-sort-up'
  if (estado === 'descending') return 'govco-sort-down'
  return 'govco-sort'
}
</script>

<template>
  <div class="tabla-govco tabla-ordenable-govco">
    <table>
      <caption class="text2-govco">{{ leyenda }}</caption>
      <thead>
        <tr>
          <th
            v-for="columna in columnas"
            :key="columna.clave"
            scope="col"
            :aria-sort="estadoDe(columna)"
          >
            <button
              v-if="columna.ordenable === true"
              type="button"
              class="tabla-ordenable-boton"
              @click="ordenarPor(columna)"
            >
              <span>{{ columna.titulo }}</span>
              <span class="govco-svg" :class="iconoDe(columna)" aria-hidden="true" />
            </button>
            <template v-else>{{ columna.titulo }}</template>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="fila in filasOrdenadas" :key="fila.id">
          <template v-for="columna in columnas" :key="columna.clave">
            <th v-if="columna.clave === columnaEncabezadoFila" scope="row">
              {{ fila.celdas[columna.clave]?.texto }}
            </th>
            <td v-else>{{ fila.celdas[columna.clave]?.texto }}</td>
          </template>
        </tr>
      </tbody>
    </table>
    <p class="visually-hidden" role="status">{{ anuncio }}</p>
  </div>
</template>

<script setup lang="ts" generic="TData extends RowData">
import { computed, ref, toRef } from 'vue';
import {
  FlexRender,
  useTable,
  type ColumnDef,
  type RowData,
  type SortingState,
} from '@tanstack/vue-table';
import { dataTableFeatures, type DataTableFeatures } from './dataTableFeatures';
import { ventanaDePaginas } from './paginas';

const props = withDefaults(
  defineProps<{
    data: TData[];
    columns: ColumnDef<DataTableFeatures, TData, unknown>[];
    pageSize?: number;
    globalFilter?: string;
    loading?: boolean;
    empty?: string;
    /** Nombre accesible de la tabla. No se ve, pero un lector de pantalla lo anuncia. */
    caption?: string;
    /**
     * Modo servidor: `data` ya es UNA página, recortada y ordenada por el servidor.
     * La tabla no filtra, ordena ni pagina por su cuenta: muestra lo recibido y pide
     * al padre (`cambiarPagina`, `cambiarOrden`) lo que la persona elige.
     */
    servidor?: boolean;
    /** Total de registros en el servidor (sólo modo servidor). */
    total?: number;
    /** Página actual, desde 1 (sólo modo servidor). */
    pagina?: number;
    /** Registros por página (sólo modo servidor). */
    porPagina?: number;
    /** Orden vigente; en modo servidor lo dicta el padre. */
    orden?: { id: string; desc: boolean } | null;
    /** Si pulsar la fila emite `rowClick`. Sin esto, la fila no aparenta ser un botón. */
    clicable?: boolean;
  }>(),
  {
    pageSize: 10,
    globalFilter: '',
    empty: 'Sin resultados',
    caption: undefined,
    servidor: false,
    total: 0,
    pagina: 1,
    porPagina: 20,
    orden: null,
    clicable: true,
  }
);

const emit = defineEmits<{
  rowClick: [row: TData];
  cambiarPagina: [pagina: number];
  cambiarOrden: [orden: { id: string; desc: boolean }];
}>();
const sorting = ref<SortingState>([]);

// En la v9 las entradas reactivas se pasan como `ref` (aquí `toRef` sobre un
// getter, que sigue a las props): el adaptador las desenvuelve y las sincroniza.
// Pasar `props.data` a secas capturaría sólo el primer valor. Sin
// `columnVisibilityFeature` no hay columnas ocultas, así que `getAllCells()`
// equivale al `getVisibleCells()` de la v8.
const datos = toRef(() => props.data);
const columnas = toRef(() => props.columns);

// La ordenación y el filtro global son estado «controlado» por este componente:
// cada porción lleva su propio `on…Change`, que puede recibir el valor o una
// función que lo calcula a partir del anterior. La v9 ya no tiene `onStateChange`.
const estado = toRef(() => ({
  sorting: props.servidor && props.orden ? [{ id: props.orden.id, desc: props.orden.desc }] : sorting.value,
  globalFilter: props.globalFilter,
}));

const table = useTable<DataTableFeatures, TData>({
  features: dataTableFeatures,
  data: datos,
  columns: columnas,
  state: estado,
  manualPagination: props.servidor,
  manualSorting: props.servidor,
  onSortingChange: (updater) => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
  },
  initialState: { pagination: { pageIndex: 0, pageSize: props.pageSize } },
});

// `getState()` desapareció: la paginación se lee de su átomo, y Vue rastrea la lectura.
const paginacion = computed(() => table.atoms.pagination.get());
const numeroPaginas = computed(() => table.getPageCount());
const paginaActual = computed(() => paginacion.value.pageIndex);
const numerosLocal = computed(() => ventanaDePaginas(paginaActual.value + 1, numeroPaginas.value));

/**
 * Traduce el estado de ordenación de TanStack a los valores de `aria-sort`.
 *
 * `none` no significa «sin ordenar» y ya está: para ARIA describe una columna
 * **ordenable que ahora mismo no ordena**, que es justo el estado de partida.
 */
// --- Modo servidor ---------------------------------------------------------
const ultimaPagina = computed(() => Math.max(1, Math.ceil(props.total / Math.max(props.porPagina, 1))));
const numerosServidor = computed(() => ventanaDePaginas(props.pagina, ultimaPagina.value));
const desde = computed(() => (props.total === 0 ? 0 : (props.pagina - 1) * props.porPagina + 1));
const hasta = computed(() => Math.min(props.pagina * props.porPagina, props.total));

function ordenDe(id: string): false | 'asc' | 'desc' {
  if (!props.servidor) return false;
  if (props.orden?.id !== id) return false;
  return props.orden.desc ? 'desc' : 'asc';
}

/** Primera pulsación: ascendente. Pulsar la columna ya ordenada invierte el sentido. */
function pedirOrden(id: string): void {
  const actual = props.orden;
  emit('cambiarOrden', { id, desc: actual?.id === id ? !actual.desc : false });
}

function irA(pagina: number): void {
  if (pagina < 1 || pagina > ultimaPagina.value || pagina === props.pagina) return;
  emit('cambiarPagina', pagina);
}

/**
 * Clases propias de una columna (`meta.clase`), p. ej. `hidden md:table-cell` para
 * ocultarla en pantallas estrechas sin perder el dato en las anchas.
 */
function claseDe(columna: { columnDef: { meta?: unknown } }): string {
  return (columna.columnDef.meta as { clase?: string } | undefined)?.clase ?? '';
}

function ariaSort(estado: false | 'asc' | 'desc'): 'ascending' | 'descending' | 'none' {
  if (estado === 'asc') return 'ascending';
  if (estado === 'desc') return 'descending';
  return 'none';
}
</script>

<template>
  <div class="overflow-hidden rounded-xl ring-1 ring-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <caption v-if="caption" class="sr-only">{{ caption }}</caption>
        <thead class="bg-slate-50">
          <tr v-for="hg in table.getHeaderGroups()" :key="hg.id">
            <!--
              La ordenación NO vive en el `<th>`: un encabezado con `@click` no
              recibe foco ni responde a Intro, así que era inoperable sin ratón
              (WCAG 2.1.1). El `<th>` declara el estado con `aria-sort` y dentro
              va un botón de verdad, que es lo que se tabula y se pulsa.
            -->
            <th
              v-for="header in hg.headers"
              :key="header.id"
              scope="col"
              :aria-sort="header.column.getCanSort() ? ariaSort(servidor ? ordenDe(header.column.id) : header.column.getIsSorted()) : undefined"
              :class="[
                'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600',
                header.column.getCanSort() ? 'hover:bg-slate-100' : '',
                claseDe(header.column),
              ]"
            >
              <button
                v-if="header.column.getCanSort()"
                type="button"
                class="inline-flex items-center gap-1 rounded text-left font-semibold uppercase tracking-wider focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue"
                @click="servidor ? pedirOrden(header.column.id) : header.column.getToggleSortingHandler()?.($event)"
              >
                <FlexRender :header="header" />
                <FaIcon
                  :icon="(servidor ? ordenDe(header.column.id) : header.column.getIsSorted()) === 'desc' ? 'arrow-down' : 'arrow-up'"
                  :class="['h-3 w-3', (servidor ? ordenDe(header.column.id) : header.column.getIsSorted()) ? 'text-gov-blue' : 'text-slate-300']"
                  aria-hidden="true"
                />
              </button>
              <FlexRender v-else :header="header" />
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="columns.length" class="px-4 py-12 text-center text-slate-500">Cargando…</td>
          </tr>
          <tr v-else-if="table.getRowModel().rows.length === 0">
            <td :colspan="columns.length" class="px-4 py-12 text-center text-slate-500">{{ empty }}</td>
          </tr>
          <tr
            v-for="row in table.getRowModel().rows"
            v-else
            :key="row.id"
            :class="['hover:bg-gov-blue-light/40 transition-colors', clicable ? 'cursor-pointer' : '']"
            @click="clicable && emit('rowClick', row.original)"
          >
            <td v-for="cell in row.getAllCells()" :key="cell.id" :class="['px-4 py-3 text-sm text-slate-700', claseDe(cell.column)]">
              <FlexRender :cell="cell" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modo servidor: contador y paginación a partir del total que informa el servidor. -->
    <div
      v-if="servidor"
      class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"
    >
      <span v-if="total === 0">0 resultados</span>
      <span v-else>{{ desde }}–{{ hasta }} de {{ total }}</span>
      <nav class="flex flex-wrap items-center gap-1" aria-label="Paginación de la tabla">
        <button
          type="button"
          class="grid h-9 min-w-9 place-items-center rounded-lg ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-40"
          :disabled="pagina <= 1"
          aria-label="Página anterior"
          @click="irA(pagina - 1)"
        >
          <FaIcon icon="chevron-left" class="h-3 w-3" aria-hidden="true" />
        </button>
        <template v-for="(elemento, i) in numerosServidor" :key="`${elemento}-${i}`">
          <span v-if="elemento === '…'" class="px-1 text-slate-400" aria-hidden="true">…</span>
          <button
            v-else
            type="button"
            :class="[
              'grid h-9 min-w-9 place-items-center rounded-lg px-2 text-sm transition-colors',
              elemento === pagina ? 'bg-gov-blue font-semibold text-white' : 'ring-1 ring-slate-200 hover:bg-slate-50',
            ]"
            :aria-current="elemento === pagina ? 'page' : undefined"
            :aria-label="`Página ${elemento}`"
            @click="irA(elemento)"
          >
            {{ elemento }}
          </button>
        </template>
        <button
          type="button"
          class="grid h-9 min-w-9 place-items-center rounded-lg ring-1 ring-slate-200 hover:bg-slate-50 disabled:opacity-40"
          :disabled="pagina >= ultimaPagina"
          aria-label="Página siguiente"
          @click="irA(pagina + 1)"
        >
          <FaIcon icon="chevron-right" class="h-3 w-3" aria-hidden="true" />
        </button>
      </nav>
    </div>

    <div v-else class="flex items-center justify-between px-4 py-3 border-t border-slate-200 text-sm text-slate-600">
      <span>
        {{ paginacion.pageIndex * paginacion.pageSize + 1 }}–{{
          Math.min(
            (paginacion.pageIndex + 1) * paginacion.pageSize,
            table.getFilteredRowModel().rows.length
          )
        }}
        de {{ table.getFilteredRowModel().rows.length }}
      </span>
      <!--
        Números de página además de anterior/siguiente: sin ellos no se sabe en
        qué página se está ni cuántas quedan. El grupo es una `nav` con nombre
        (WCAG 4.1.2) y la página activa se marca con `aria-current="page"`, no
        sólo con color.
      -->
      <nav class="flex items-center gap-1" aria-label="Paginación de la tabla">
        <button
          type="button"
          class="h-8 w-8 grid place-items-center rounded-lg ring-1 ring-slate-200 disabled:opacity-40 hover:bg-slate-50"
          :disabled="!table.getCanPreviousPage()"
          aria-label="Página anterior"
          @click="table.previousPage()"
        >
          <FaIcon icon='chevron-left' class="h-3 w-3" aria-hidden="true" />
        </button>

        <template v-for="(elemento, i) in numerosLocal" :key="`${elemento}-${i}`">
          <span v-if="elemento === '…'" class="px-1 text-slate-400" aria-hidden="true">…</span>
          <button
            v-else
            type="button"
            :class="[
              'h-8 min-w-8 px-2 grid place-items-center rounded-lg text-sm transition-colors',
              elemento - 1 === paginaActual
                ? 'bg-gov-blue text-white font-semibold'
                : 'ring-1 ring-slate-200 hover:bg-slate-50',
            ]"
            :aria-current="elemento - 1 === paginaActual ? 'page' : undefined"
            :aria-label="`Página ${elemento}`"
            @click="table.setPageIndex(elemento - 1)"
          >
            {{ elemento }}
          </button>
        </template>

        <button
          type="button"
          class="h-8 w-8 grid place-items-center rounded-lg ring-1 ring-slate-200 disabled:opacity-40 hover:bg-slate-50"
          :disabled="!table.getCanNextPage()"
          aria-label="Página siguiente"
          @click="table.nextPage()"
        >
          <FaIcon icon='chevron-right' class="h-3 w-3" aria-hidden="true" />
        </button>
      </nav>
    </div>
  </div>
</template>

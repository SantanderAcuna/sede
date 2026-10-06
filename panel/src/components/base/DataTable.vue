<script setup lang="ts" generic="TData extends object">
import { computed, ref } from 'vue';
import {
  FlexRender,
  getCoreRowModel,
  getSortedRowModel,
  getPaginationRowModel,
  getFilteredRowModel,
  useVueTable,
  type ColumnDef,
  type SortingState,
} from '@tanstack/vue-table';

const props = withDefaults(
  defineProps<{
    data: TData[];
    columns: ColumnDef<TData, unknown>[];
    pageSize?: number;
    globalFilter?: string;
    loading?: boolean;
    empty?: string;
  }>(),
  { pageSize: 10, globalFilter: '', empty: 'Sin resultados' }
);

const emit = defineEmits<{ rowClick: [row: TData] }>();
const sorting = ref<SortingState>([]);

const table = useVueTable({
  get data() {
    return props.data;
  },
  get columns() {
    return props.columns;
  },
  state: {
    get sorting() {
      return sorting.value;
    },
    get globalFilter() {
      return props.globalFilter;
    },
  },
  onSortingChange: (updater) => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  initialState: { pagination: { pageSize: props.pageSize } },
});

const numeroPaginas = computed(() => table.getPageCount());
const paginaActual = computed(() => table.getState().pagination.pageIndex);

/**
 * Traduce el estado de ordenación de TanStack a los valores de `aria-sort`.
 *
 * `none` no significa «sin ordenar» y ya está: para ARIA describe una columna
 * **ordenable que ahora mismo no ordena**, que es justo el estado de partida.
 */
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
              :aria-sort="header.column.getCanSort() ? ariaSort(header.column.getIsSorted()) : undefined"
              :class="[
                'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600',
                header.column.getCanSort() ? 'hover:bg-slate-100' : '',
              ]"
            >
              <button
                v-if="header.column.getCanSort()"
                type="button"
                class="inline-flex items-center gap-1 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue"
                @click="header.column.getToggleSortingHandler()?.($event)"
              >
                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                <FaIcon
                  :icon="header.column.getIsSorted() === 'desc' ? 'arrow-down' : 'arrow-up'"
                  :class="['h-3 w-3', header.column.getIsSorted() ? 'text-gov-blue' : 'text-slate-300']"
                  aria-hidden="true"
                />
              </button>
              <FlexRender
                v-else
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
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
            class="hover:bg-gov-blue-light/40 cursor-pointer transition-colors"
            @click="emit('rowClick', row.original)"
          >
            <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-4 py-3 text-sm text-slate-700">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between px-4 py-3 border-t border-slate-200 text-sm text-slate-600">
      <span>
        {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}–{{
          Math.min(
            (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
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

        <button
          v-for="pagina in numeroPaginas"
          :key="pagina"
          type="button"
          :class="[
            'h-8 min-w-8 px-2 grid place-items-center rounded-lg text-sm transition-colors',
            pagina - 1 === paginaActual
              ? 'bg-gov-blue text-white font-semibold'
              : 'ring-1 ring-slate-200 hover:bg-slate-50',
          ]"
          :aria-current="pagina - 1 === paginaActual ? 'page' : undefined"
          :aria-label="`Página ${pagina}`"
          @click="table.setPageIndex(pagina - 1)"
        >
          {{ pagina }}
        </button>

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

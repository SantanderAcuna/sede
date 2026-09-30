<script setup lang="ts" generic="TData extends object">
import { ref } from 'vue';
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
</script>

<template>
  <div class="overflow-hidden rounded-xl ring-1 ring-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr v-for="hg in table.getHeaderGroups()" :key="hg.id">
            <th
              v-for="header in hg.headers"
              :key="header.id"
              scope="col"
              :class="[
                'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600',
                header.column.getCanSort() ? 'cursor-pointer select-none hover:bg-slate-100' : '',
              ]"
              @click="header.column.getToggleSortingHandler()?.($event)"
            >
              <span class="inline-flex items-center gap-1">
                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                <FaIcon
                  v-if="header.column.getCanSort()"
                  :icon="header.column.getIsSorted() === 'desc' ? 'arrow-down' : 'arrow-up'"
                  :class="['h-3 w-3', header.column.getIsSorted() ? 'text-gov-blue' : 'text-slate-300']"
                />
              </span>
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
      <div class="flex items-center gap-1">
        <button
          class="h-8 w-8 grid place-items-center rounded-lg ring-1 ring-slate-200 disabled:opacity-40 hover:bg-slate-50"
          :disabled="!table.getCanPreviousPage()"
          aria-label="Página anterior"
          @click="table.previousPage()"
        >
          <FaIcon icon="chevron-left" class="h-3 w-3" />
        </button>
        <button
          class="h-8 w-8 grid place-items-center rounded-lg ring-1 ring-slate-200 disabled:opacity-40 hover:bg-slate-50"
          :disabled="!table.getCanNextPage()"
          aria-label="Página siguiente"
          @click="table.nextPage()"
        >
          <FaIcon icon="chevron-right" class="h-3 w-3" />
        </button>
      </div>
    </div>
  </div>
</template>

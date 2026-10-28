import {
  columnFilteringFeature,
  createFilteredRowModel,
  createPaginatedRowModel,
  createSortedRowModel,
  filterFn_includesString,
  globalFilteringFeature,
  rowPaginationFeature,
  rowSortingFeature,
  sortFn_alphanumeric,
  sortFn_basic,
  sortFn_datetime,
  sortFn_text,
  tableFeatures,
} from '@tanstack/vue-table'

/**
 * Funciones de TanStack Table que usa `DataTable`.
 *
 * En la v9 ya no vienen todas de serie: cada capacidad se declara aquí y sólo
 * se empaqueta lo declarado. `DataTable` ordena, filtra con una búsqueda global
 * y pagina, y nada más.
 *
 * - `columnFilteringFeature` va antes que `globalFilteringFeature`, que depende
 *   de ella.
 * - `sortFns` recoge las cuatro funciones que la v8 elegía con `'auto'`
 *   (alfanumérica, texto, fecha y básica); `'auto'` sólo resuelve entre las
 *   registradas.
 * - `includesString` es la función de la búsqueda global, que es la que la v8
 *   usaba por defecto.
 *
 * Se declara una sola vez y fuera del componente para que sea estable.
 */
export const dataTableFeatures = tableFeatures({
  columnFilteringFeature,
  globalFilteringFeature,
  rowSortingFeature,
  rowPaginationFeature,
  filteredRowModel: createFilteredRowModel(),
  sortedRowModel: createSortedRowModel(),
  paginatedRowModel: createPaginatedRowModel(),
  filterFns: { includesString: filterFn_includesString },
  sortFns: {
    alphanumeric: sortFn_alphanumeric,
    text: sortFn_text,
    datetime: sortFn_datetime,
    basic: sortFn_basic,
  },
})

export type DataTableFeatures = typeof dataTableFeatures

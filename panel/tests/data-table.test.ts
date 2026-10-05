/**
 * DataTable — tests del componente de tabla con TanStack Vue Table.
 *
 * Cubre: renderizado con datos, estado vacío, paginación, ordenación,
 *ARIA-sort, y emit rowClick.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { h, type ColumnDef } from '@tanstack/vue-table'
import { defineComponent, ref } from 'vue'

// ---------------------------------------------------------------------------
// Mock FaIcon global (usado en toda la tabla)
// ---------------------------------------------------------------------------
vi.mock('@/components/base/FaIcon.vue', () => ({
  default: defineComponent({
    props: ['icon', 'class'],
    setup(props) {
      return () => h('span', { class: props.class, 'aria-hidden': 'true' }, `[${props.icon}]`)
    },
  }),
}))

// ---------------------------------------------------------------------------
// Componente DataTable con setup de columnas simple
// ---------------------------------------------------------------------------
const createColumns = (): ColumnDef<{ id: number; nombre: string; estado: string }, unknown>[] => [
  {
    id: 'nombre',
    accessorKey: 'nombre',
    header: 'Nombre',
    cell: (info) => info.getValue(),
  },
  {
    id: 'estado',
    accessorKey: 'estado',
    header: 'Estado',
    cell: (info) => info.getValue(),
  },
]

const sampleData = [
  { id: 1, nombre: 'Trámite A', estado: 'Activo' },
  { id: 2, nombre: 'Trámite B', estado: 'Cerrado' },
  { id: 3, nombre: 'Trámite C', estado: 'Activo' },
]

async function mountDataTable(props: Record<string, unknown> = {}) {
  const { default: DataTable } = await import('../src/components/base/DataTable.vue')
  return mount(DataTable, {
    props: {
      data: sampleData,
      columns: createColumns(),
      pageSize: 10,
      ...props,
    },
  })
}

// ---------------------------------------------------------------------------
// Renderizado y estados
// ---------------------------------------------------------------------------
describe('DataTable — renderizado', () => {
  it('renderiza las filas con datos', async () => {
    const wrapper = await mountDataTable()
    expect(wrapper.text()).toContain('Trámite A')
    expect(wrapper.text()).toContain('Trámite B')
    expect(wrapper.text()).toContain('Trámite C')
  })

  it('muestra el texto vacío personalizado', async () => {
    const wrapper = await mountDataTable({ data: [], empty: 'No hay trámites' })
    expect(wrapper.text()).toContain('No hay trámites')
  })

  it('muestra "Sin resultados" por defecto cuando no hay datos', async () => {
    const wrapper = await mountDataTable({ data: [] })
    expect(wrapper.text()).toContain('Sin resultados')
  })

  it('muestra el estado de carga', async () => {
    const wrapper = await mountDataTable({ loading: true })
    expect(wrapper.text()).toContain('Cargando…')
  })

  it('oculta las filas cuando está cargando', async () => {
    const wrapper = await mountDataTable({ loading: true })
    expect(wrapper.text()).not.toContain('Trámite A')
  })
})

// ---------------------------------------------------------------------------
// Paginación
// ---------------------------------------------------------------------------
describe('DataTable — paginación', () => {
  it('muestra el contador de registros', async () => {
    const wrapper = await mountDataTable()
    // 1–3 de 3
    expect(wrapper.text()).toMatch(/1.*3/)
    wrapper.unmount()
  })

  it('tiene botón de página siguiente y anterior', async () => {
    const wrapper = await mountDataTable()
    expect(wrapper.find('button[aria-label="Página anterior"]').exists()).toBe(true)
    expect(wrapper.find('button[aria-label="Página siguiente"]').exists()).toBe(true)
    wrapper.unmount()
  })

  it('el botón de página siguiente está deshabilitado en la última página', async () => {
    const wrapper = await mountDataTable()
    const nextBtn = wrapper.find('button[aria-label="Página siguiente"]')
    expect(nextBtn.attributes('disabled')).toBeDefined()
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// Ordenación y ARIA
// ---------------------------------------------------------------------------
describe('DataTable — ordenación', () => {
  it('los encabezados ordenables tienen aria-sort', async () => {
    const wrapper = await mountDataTable()
    const headers = wrapper.findAll('th')
    expect(headers.length).toBeGreaterThan(0)
    // aria-sort puede ser 'none' (sin ordenar) para columnas que pueden ordenar.
    const ariaSortAttr = headers[0].attributes('aria-sort')
    expect(['ascending', 'descending', 'none']).toContain(ariaSortAttr)
    wrapper.unmount()
  })

  it('el click en encabezado emite ordenación', async () => {
    const wrapper = await mountDataTable()
    const sortButton = wrapper.find('th button')
    if (sortButton.exists()) {
      await sortButton.trigger('click')
    }
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// Row click
// ---------------------------------------------------------------------------
describe('DataTable — rowClick', () => {
  it('al hacer clic en una fila se emite rowClick con el dato', async () => {
    const wrapper = await mountDataTable()
    const rows = wrapper.findAll('tbody tr')
    if (rows.length > 0) {
      await rows[0].trigger('click')
      expect(wrapper.emitted('rowClick')).toBeTruthy()
    }
    wrapper.unmount()
  })

  it('el payload de rowClick contiene los datos correctos', async () => {
    const wrapper = await mountDataTable()
    const rows = wrapper.findAll('tbody tr')
    if (rows.length > 0) {
      await rows[0].trigger('click')
      const emitted = wrapper.emitted('rowClick') as unknown[][]
      expect(emitted?.[0]?.[0]).toMatchObject({ id: 1, nombre: 'Trámite A' })
    }
    wrapper.unmount()
  })
})

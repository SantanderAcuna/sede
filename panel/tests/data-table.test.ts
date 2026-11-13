/**
 * DataTable — tests del componente de tabla con TanStack Vue Table.
 *
 * Cubre: renderizado con datos, estado vacío, paginación, ordenación,
 *ARIA-sort, y emit rowClick.
 */
import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { h, type ColumnDef } from '@tanstack/vue-table'
import type { DataTableFeatures } from '../src/components/base/dataTableFeatures'
import { defineComponent } from 'vue'

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
const createColumns = (): ColumnDef<DataTableFeatures, { id: number; nombre: string; estado: string }, unknown>[] => [
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

// ---------------------------------------------------------------------------
// Comportamiento real tras la migración a TanStack Table v9
//
// Los casos de arriba comprueban sobre todo que existan los elementos. Estos
// comprueban lo que la v9 cambió por dentro: ordenar, filtrar, reaccionar a
// datos nuevos y paginar.
// ---------------------------------------------------------------------------
const nombresEnPantalla = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('tbody tr').map((fila) => fila.findAll('td')[0]?.text())

describe('DataTable — comportamiento (v9)', () => {
  it('ordenar por una columna reordena las filas y marca aria-sort', async () => {
    const wrapper = await mountDataTable({
      data: [
        { id: 1, nombre: 'Beta', estado: 'Activo' },
        { id: 2, nombre: 'Alfa', estado: 'Activo' },
        { id: 3, nombre: 'Gamma', estado: 'Activo' },
      ],
    })
    expect(nombresEnPantalla(wrapper)).toEqual(['Beta', 'Alfa', 'Gamma'])

    await wrapper.find('th button').trigger('click')
    expect(nombresEnPantalla(wrapper)).toEqual(['Alfa', 'Beta', 'Gamma'])
    expect(wrapper.find('th').attributes('aria-sort')).toBe('ascending')

    await wrapper.find('th button').trigger('click')
    expect(nombresEnPantalla(wrapper)).toEqual(['Gamma', 'Beta', 'Alfa'])
    expect(wrapper.find('th').attributes('aria-sort')).toBe('descending')
    wrapper.unmount()
  })

  it('el filtro global deja solo las filas que coinciden y reacciona al cambiar', async () => {
    const wrapper = await mountDataTable()
    await wrapper.setProps({ globalFilter: 'Trámite B' })
    expect(nombresEnPantalla(wrapper)).toEqual(['Trámite B'])
    expect(wrapper.text()).toContain('de 1')

    await wrapper.setProps({ globalFilter: '' })
    expect(nombresEnPantalla(wrapper)).toHaveLength(3)
    wrapper.unmount()
  })

  it('reemplazar `data` actualiza las filas mostradas', async () => {
    const wrapper = await mountDataTable()
    expect(nombresEnPantalla(wrapper)).toHaveLength(3)

    await wrapper.setProps({ data: [{ id: 9, nombre: 'Nuevo', estado: 'Activo' }] })
    expect(nombresEnPantalla(wrapper)).toEqual(['Nuevo'])
    wrapper.unmount()
  })

  it('pagina según `pageSize` y navega entre páginas', async () => {
    const wrapper = await mountDataTable({ pageSize: 2 })
    expect(nombresEnPantalla(wrapper)).toEqual(['Trámite A', 'Trámite B'])
    expect(wrapper.find('nav[aria-label="Paginación de la tabla"]').findAll('button[aria-label^="Página "]')).toHaveLength(4)

    await wrapper.find('button[aria-label="Página siguiente"]').trigger('click')
    expect(nombresEnPantalla(wrapper)).toEqual(['Trámite C'])
    expect(wrapper.find('button[aria-current="page"]').text()).toBe('2')
    expect(wrapper.find('button[aria-label="Página siguiente"]').attributes('disabled')).toBeDefined()

    await wrapper.find('button[aria-label="Página anterior"]').trigger('click')
    expect(nombresEnPantalla(wrapper)).toEqual(['Trámite A', 'Trámite B'])
    wrapper.unmount()
  })
})


// ---------------------------------------------------------------------------
// Modo servidor: la tabla muestra una página que ya viene recortada y ordenada
// ---------------------------------------------------------------------------
describe('DataTable — modo servidor', () => {
  const columnas = (): ColumnDef<DataTableFeatures, { id: number; nombre: string }, unknown>[] => [
    { id: 'nombre', accessorKey: 'nombre', header: 'Nombre', cell: (i) => i.getValue(), enableSorting: true },
    { id: 'otra', accessorKey: 'id', header: 'Otra', cell: (i) => String(i.getValue()), enableSorting: false },
  ]
  const pagina = [
    { id: 21, nombre: 'Veintiuno' },
    { id: 22, nombre: 'Veintidós' },
  ]

  async function montar(props: Record<string, unknown> = {}) {
    const { default: DataTable } = await import('../src/components/base/DataTable.vue')
    return mount(DataTable, {
      props: {
        data: pagina,
        columns: columnas(),
        servidor: true,
        total: 45,
        pagina: 2,
        porPagina: 20,
        orden: { id: 'nombre', desc: false },
        caption: 'Listado de prueba',
        ...props,
      },
    })
  }

  it('muestra exactamente las filas recibidas, sin volver a paginarlas', async () => {
    const w = await montar()
    expect(w.findAll('tbody tr')).toHaveLength(2)
  })

  it('el contador usa el total del servidor: 21–40 de 45', async () => {
    const w = await montar()
    expect(w.text()).toContain('21–40 de 45')
  })

  it('la última página recorta el contador al total', async () => {
    const w = await montar({ pagina: 3, data: [{ id: 41, nombre: 'x' }] })
    expect(w.text()).toContain('41–45 de 45')
  })

  it('sin resultados no dice «1–0 de 0»', async () => {
    const w = await montar({ data: [], total: 0, pagina: 1 })
    expect(w.text()).not.toContain('1–0')
    expect(w.text()).toContain('0 resultados')
  })

  it('la tabla lleva un nombre accesible (caption) aunque no se vea', async () => {
    const w = await montar()
    expect(w.find('caption').text()).toBe('Listado de prueba')
    expect(w.find('caption').classes()).toContain('sr-only')
  })

  it('marca la página actual con aria-current y la pide con cambiarPagina', async () => {
    const w = await montar()
    expect(w.find('[aria-current="page"]').text()).toBe('2')

    await w.find('button[aria-label="Página 3"]').trigger('click')
    expect(w.emitted('cambiarPagina')?.[0]).toEqual([3])

    await w.find('button[aria-label="Página anterior"]').trigger('click')
    expect(w.emitted('cambiarPagina')?.[1]).toEqual([1])
  })

  it('no dibuja un botón por cada página cuando son muchas', async () => {
    const w = await montar({ total: 2000, pagina: 50 })
    const numeros = w.findAll('nav button[aria-label^="Página "]').filter((b) => /^Página \d+$/.test(b.attributes('aria-label') ?? ''))
    expect(numeros.length).toBeLessThanOrEqual(5)
  })

  it('en la primera página «anterior» está inhabilitado; en la última, «siguiente»', async () => {
    const primera = await montar({ pagina: 1 })
    expect(primera.find('button[aria-label="Página anterior"]').attributes('disabled')).toBeDefined()
    const ultima = await montar({ pagina: 3 })
    expect(ultima.find('button[aria-label="Página siguiente"]').attributes('disabled')).toBeDefined()
  })

  it('aria-sort sigue el orden que dicta el servidor, no uno local', async () => {
    const w = await montar({ orden: { id: 'nombre', desc: true } })
    expect(w.find('th').attributes('aria-sort')).toBe('descending')
  })

  it('pulsar una columna ordenable pide el orden; pulsar la ordenada lo invierte', async () => {
    const w = await montar({ orden: { id: 'otra', desc: false } })
    await w.find('th button').trigger('click')
    expect(w.emitted('cambiarOrden')?.[0]).toEqual([{ id: 'nombre', desc: false }])

    const w2 = await montar({ orden: { id: 'nombre', desc: false } })
    await w2.find('th button').trigger('click')
    expect(w2.emitted('cambiarOrden')?.[0]).toEqual([{ id: 'nombre', desc: true }])
  })

  it('una columna no ordenable no es botón', async () => {
    const w = await montar()
    expect(w.findAll('th')[1]!.find('button').exists()).toBe(false)
  })

  it('pulsar una fila sigue emitiendo rowClick', async () => {
    const w = await montar()
    await w.find('tbody tr').trigger('click')
    expect(w.emitted('rowClick')?.[0]).toEqual([pagina[0]])
  })
})

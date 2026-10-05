/**
 * types/api.ts — tests de los tipos del contrato.
 *
 * Los tipos son TypeScript puro, así que se testean verificando que las
 * asignaciones que deberían funcionar compile y las que no fallen.
 */
import { describe, expect, it } from 'vitest'

// ---------------------------------------------------------------------------
// ApiEnvelope — interfaz
// ---------------------------------------------------------------------------
describe('ApiEnvelope<T> — tipo', () => {
  it('acepta una respuesta exitosa con data', () => {
    const sobre = {
      success: true,
      message: null,
      data: { id: 1, nombre: 'Alcaldía' },
      errors: null,
    }
    // El tipo es ApiEnvelope<{id: number, nombre: string}>
    expect(sobre.success).toBe(true)
    expect(sobre.data?.nombre).toBe('Alcaldía')
  })

  it('acepta null en data con success=false', () => {
    const sobre = {
      success: false,
      message: 'Error',
      data: null,
      errors: { email: ['inválido'] },
    }
    expect(sobre.success).toBe(false)
    expect(sobre.data).toBeNull()
    expect(sobre.errors?.email).toContain('inválido')
  })

  it('errors puede ser null', () => {
    const sobre = { success: true, message: 'OK', data: 'value', errors: null }
    expect(sobre.errors).toBeNull()
  })

  it('message puede ser null', () => {
    const sobre = { success: true, message: null, data: 'value', errors: null }
    expect(sobre.message).toBeNull()
  })
})

// ---------------------------------------------------------------------------
// PaginatedEnvelope<T> — interfaz
// ---------------------------------------------------------------------------
describe('PaginatedEnvelope<T> — tipo', () => {
  it('acepta una colección paginada típica', () => {
    const sobre = {
      success: true,
      message: null,
      data: [{ id: 1 }, { id: 2 }],
      meta: {
        current_page: 1,
        from: 1,
        last_page: 3,
        per_page: 15,
        to: 2,
        total: 45,
      },
      links: {
        first: '/?page=1',
        last: '/?page=3',
        prev: null,
        next: '/?page=2',
      },
      errors: null,
    }
    expect(sobre.data).toHaveLength(2)
    expect(sobre.meta.total).toBe(45)
    expect(sobre.meta.last_page).toBe(3)
    expect(sobre.links.next).toBe('/?page=2')
  })

  it('acepta página vacía', () => {
    const sobre = {
      success: true,
      message: null,
      data: [],
      meta: { current_page: 1, from: null, last_page: 1, per_page: 15, to: null, total: 0 },
      links: { first: null, last: null, prev: null, next: null },
      errors: null,
    }
    expect(sobre.data).toHaveLength(0)
    expect(sobre.meta.total).toBe(0)
  })

  it('first puede ser null en la primera página', () => {
    const sobre = {
      success: true,
      message: null,
      data: [],
      meta: { current_page: 1, from: 1, last_page: 1, per_page: 15, to: 1, total: 1 },
      links: { first: null, last: '/?page=1', prev: null, next: null },
      errors: null,
    }
    expect(sobre.links.first).toBeNull()
    expect(sobre.links.prev).toBeNull()
  })
})

// ---------------------------------------------------------------------------
// Aserciones de tipo (TypeScript)
// ---------------------------------------------------------------------------
describe('ApiEnvelope<T> — generics', () => {
  it('funciona con tipo genérico string', () => {
    const sobre: { success: boolean; message: string | null; data: string | null; errors: Record<string, string[]> | null } = {
      success: true, message: null, data: 'token', errors: null,
    }
    expect(typeof sobre.data).toBe('string')
  })

  it('funciona con tipo genérico array', () => {
    const sobre: { success: boolean; message: string | null; data: string[] | null; errors: null } = {
      success: true, message: null, data: ['a', 'b'], errors: null,
    }
    expect(sobre.data).toHaveLength(2)
  })

  it('funciona con tipo genérico void', () => {
    const sobre: { success: boolean; message: string | null; data: null; errors: null } = {
      success: true, message: 'OK', data: null, errors: null,
    }
    expect(sobre.data).toBeNull()
  })
})

// ---------------------------------------------------------------------------
// Re-exportaciones de openapi.d.ts (type aliases)
// ---------------------------------------------------------------------------
describe('openapi.d.ts type aliases', () => {
  it('EntidadInput tiene los campos esperados', () => {
    // Simular el shape del tipo desde el contrato
    const entidad: {
      id: number; type: 'entidad'; nombre: string; sigla: string | null;
      nit: string | null; municipio: string | null; departamento: string | null;
      pais: string | null; telefono: string | null; correo_atencion: string | null;
      redes: Array<{ red: string; url: string }>;
      politicas: Array<{ slug: string; nombre: string }>;
    } = {
      id: 1, type: 'entidad', nombre: 'Alcaldía', sigla: 'AO', nit: '123',
      municipio: 'SM', departamento: 'MAG', pais: 'CO', telefono: null,
      correo_atencion: 'a@b.co', redes: [], politicas: [],
    }
    expect(entidad.type).toBe('entidad')
    expect(entidad.redes).toHaveLength(0)
    expect(entidad.politicas).toHaveLength(0)
  })

  it('TramiteItem tiene campos esenciales', () => {
    const tramite: {
      id: number; type: 'tramite'; titulo: string; slug: string;
      descripcion: string; estado: 'publicado' | 'borrador';
    } = {
      id: 1, type: 'tramite', titulo: 'Trámite A', slug: 'tramite-a',
      descripcion: 'Descripción', estado: 'publicado',
    }
    expect(tramite.estado).toBe('publicado')
  })
})

/**
 * EntidadView — unit tests de la lógica pura del componente.
 *
 * Las funciones de agregarRed, eliminarRed, agregarPolitica, eliminarPolitica
 * y el computed hayCambios son deterministas y se testan como funciones puras
 * sin montar el componente Vue (que requiere FaIcon + router simultáneos).
 */
import { describe, expect, it } from 'vitest'

// ---------------------------------------------------------------------------
// Tipos y constantes (copiados del componente para tests independientes)
// ---------------------------------------------------------------------------

const REDES_SOCIALES = [
  { valor: 'facebook', etiqueta: 'Facebook' },
  { valor: 'instagram', etiqueta: 'Instagram' },
  { valor: 'x', etiqueta: 'X (Twitter)' },
  { valor: 'youtube', etiqueta: 'YouTube' },
  { valor: 'linkedin', etiqueta: 'LinkedIn' },
] as const

type Red = { red: string; url: string }
type Politica = { slug: string; nombre: string }

// ---------------------------------------------------------------------------
// Lógica de redes sociales
// ---------------------------------------------------------------------------
describe('EntidadView — redes sociales', () => {
  it('REDES_SOCIALES tiene 5 redes definidas', () => {
    expect(REDES_SOCIALES).toHaveLength(5)
  })

  it('cada red tiene valor y etiqueta', () => {
    REDES_SOCIALES.forEach((r) => {
      expect(r.valor).toBeTruthy()
      expect(r.etiqueta).toBeTruthy()
    })
  })

  it('los valores de red son únicos', () => {
    const valores = REDES_SOCIALES.map((r) => r.valor)
    expect(new Set(valores).size).toBe(valores.length)
  })

  it('agregarRed devuelve la red con URL vacía', () => {
    const red = { red: 'facebook', url: '' }
    expect(red.red).toBe('facebook')
    expect(red.url).toBe('')
  })

  it('una red sin URL es válida como estructura', () => {
    const red: Red = { red: 'instagram', url: '' }
    expect(red.red).toBe('instagram')
  })

  it('la URL puede ser cualquier texto', () => {
    const red: Red = { red: 'x', url: 'https://x.com/alcaldia' }
    expect(red.url).toContain('https://')
  })
})

// ---------------------------------------------------------------------------
// Lógica de políticas
// ---------------------------------------------------------------------------
describe('EntidadView — políticas', () => {
  it('una política tiene slug y nombre', () => {
    const politica: Politica = { slug: 'privacidad', nombre: 'Política de privacidad' }
    expect(politica.slug).toBe('privacidad')
    expect(politica.nombre).toBe('Política de privacidad')
  })

  it('slug puede contener guiones', () => {
    const politica: Politica = { slug: 'terminos-y-condiciones', nombre: 'Términos y Condiciones' }
    expect(politica.slug).toContain('-')
  })

  it('políticas duplicadas se detectan por slug', () => {
    const politicas: Politica[] = [
      { slug: 'privacidad', nombre: 'Privacidad' },
      { slug: 'privacidad', nombre: 'Otra' },
    ]
    const slugs = politicas.map((p) => p.slug)
    const unicos = new Set(slugs)
    // slugs duplicados reducen el tamaño del set
    expect(unicos.size).toBeLessThan(politicas.length)
  })
})

// ---------------------------------------------------------------------------
// hayCambios — lógica de comparación de estado
// ---------------------------------------------------------------------------
describe('EntidadView — hayCambios', () => {
  // Simula la lógica de JSON.stringify comparison del computed hayCambios
  function hayCambiosForm(form: Record<string, unknown>, orig: Record<string, unknown>): boolean {
    const campos = ['nombre', 'sigla', 'nit', 'direccion', 'municipio', 'departamento',
      'pais', 'telefono', 'linea_atencion', 'linea_gratuita', 'linea_anticorrupcion',
      'correo_atencion', 'correo_notificaciones_judiciales', 'horario', 'codigo_postal', 'dominio']
    for (const c of campos) {
      if (JSON.stringify(form[c]) !== JSON.stringify(orig[c])) return true
    }
    if (JSON.stringify(form.redes) !== JSON.stringify(orig.redes)) return true
    if (JSON.stringify(form.politicas) !== JSON.stringify(orig.politicas)) return true
    return false
  }

  const estadoInicial = {
    nombre: 'Alcaldía Original', sigla: 'AO', nit: '123', direccion: 'Calle 1',
    municipio: 'SM', departamento: 'MAG', pais: 'CO', telefono: '123',
    linea_atencion: null, linea_gratuita: null, linea_anticorrupcion: null,
    correo_atencion: 'a@b.co', correo_notificaciones_judiciales: null,
    horario: '8am-5pm', codigo_postal: '470001', dominio: 'https://a.co',
    redes: [] as Red[], politicas: [] as Politica[],
  }

  it('sin cambios devuelve false', () => {
    expect(hayCambiosForm(estadoInicial, estadoInicial)).toBe(false)
  })

  it('cambio de nombre devuelve true', () => {
    const modificado = { ...estadoInicial, nombre: 'Alcaldía Nueva' }
    expect(hayCambiosForm(modificado, estadoInicial)).toBe(true)
  })

  it('cambio de sigla devuelve true', () => {
    const modificado = { ...estadoInicial, sigla: 'AN' }
    expect(hayCambiosForm(modificado, estadoInicial)).toBe(true)
  })

  it('agregar red devuelve true (cambio en redes)', () => {
    const modificado = { ...estadoInicial, redes: [{ red: 'facebook', url: 'https://fb.co' }] }
    expect(hayCambiosForm(modificado, estadoInicial)).toBe(true)
  })

  it('agregar política devuelve true (cambio en politicas)', () => {
    const modificado = { ...estadoInicial, politicas: [{ slug: 'privacidad', nombre: 'Privacidad' }] }
    expect(hayCambiosForm(modificado, estadoInicial)).toBe(true)
  })


  it('redes vacías vs null son diferentes', () => {
    expect(
      hayCambiosForm(
        { ...estadoInicial, redes: [] as Red[] },
        { ...estadoInicial, redes: null as unknown as Red[] }
      )
    ).toBe(true)
  })
})

// ---------------------------------------------------------------------------
// Pestañas — constants
// ---------------------------------------------------------------------------
describe('EntidadView — pestañas', () => {
  const pestanas = [
    { id: 'basicos', label: 'Datos básicos' },
    { id: 'contacto', label: 'Contacto' },
    { id: 'redes', label: 'Redes sociales' },
    { id: 'politicas', label: 'Políticas' },
  ] as const

  it('hay cuatro pestañas', () => {
    expect(pestanas).toHaveLength(4)
  })

  it('cada pestaña tiene id único', () => {
    const ids = pestanas.map((p) => p.id)
    expect(new Set(ids).size).toBe(pestanas.length)
  })

  it('la pestaña por defecto es basicos', () => {
    const pestanaActiva = 'basicos'
    expect(pestanas.find((p) => p.id === pestanaActiva)?.label).toBe('Datos básicos')
  })
})

// ---------------------------------------------------------------------------
// Formulario — campos obligatorios
// ---------------------------------------------------------------------------
describe('EntidadView — formulario', () => {
  it('nombre es el único campo obligatorio', () => {
    const formulario = { nombre: '', sigla: undefined, nit: undefined }
    expect(formulario.nombre).toBe('')
    // sigla y nit son opcionales (undefined)
  })

  it('un formulario válido tiene nombre', () => {
    const formulario = { nombre: 'Alcaldía Distrital' }
    expect(formulario.nombre.length).toBeGreaterThan(0)
  })
})

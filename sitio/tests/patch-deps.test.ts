// @vitest-environment node
/**
 * `scripts/patch-deps.mjs` (parche de `braces`, CVE-2026-93687) — se ejecuta en `postinstall` del sitio.
 *
 * Lo que se protege (medido el 2026-11-25): la versión anterior hacía tres sustituciones por separado y
 * la tercera —la que cierra el `try { … } finally`— buscaba `return queue;` con 6 espacios, pero
 * `braces` 3.0.3 lo tiene con 4. El archivo quedaba con un `try {` sin cerrar («Missing catch or
 * finally after try»), `nuxt prepare` fallaba y `make instalar` dejaba el sitio sin arrancar. Y como
 * «ya parcheado» solo miraba la marca, una segunda ejecución daba el archivo roto por bueno.
 *
 * La copia de `tests/fixtures/braces-3.0.3/expand.js` es el original de `braces@3.0.3` (MIT, su
 * licencia al lado; sha256 41ccc196…58f7): la prueba no depende de que el `node_modules` local ya esté
 * parcheado o no. El resto del paquete se copia del `node_modules` instalado.
 */
import { spawnSync } from 'node:child_process'
import { cpSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { createRequire } from 'node:module'
import { tmpdir } from 'node:os'
import { join, resolve } from 'node:path'
import { afterEach, describe, expect, it } from 'vitest'

const SITIO = resolve(__dirname, '..')
const SCRIPT = resolve(SITIO, '..', 'scripts', 'patch-deps.mjs')
const ORIGINAL = readFileSync(join(SITIO, 'tests', 'fixtures', 'braces-3.0.3', 'expand.js'), 'utf8')

const raices: string[] = []
afterEach(() => { for (const r of raices.splice(0)) rmSync(r, { recursive: true, force: true }) })

/** Una raíz de proyecto falsa con `sitio/node_modules/braces` y su `expand.js` como se indique. */
function raizCon(expand: string): { raiz: string; archivo: string } {
  const raiz = mkdtempSync(join(tmpdir(), 'patch-deps-'))
  raices.push(raiz)
  // braces y la cadena de dependencias que carga (fill-range → to-regex-range → is-number).
  for (const paquete of ['braces', 'fill-range', 'to-regex-range', 'is-number']) {
    cpSync(join(SITIO, 'node_modules', paquete), join(raiz, 'sitio', 'node_modules', paquete), { recursive: true })
  }
  const braces = join(raiz, 'sitio', 'node_modules', 'braces')
  const archivo = join(braces, 'lib', 'expand.js')
  writeFileSync(archivo, expand)
  return { raiz, archivo }
}

function ejecutar(raiz: string): { codigo: number | null; salida: string } {
  // Las dos salidas: los avisos van a la de errores aunque el script termine con 0.
  const r = spawnSync(process.execPath, [SCRIPT], { env: { ...process.env, PATCH_DEPS_RAIZ: raiz }, encoding: 'utf8' })
  return { codigo: r.status, salida: `${r.stdout}${r.stderr}` }
}

/** `braces` cargado desde la raíz falsa, sin caché de módulos compartida con otras pruebas. */
function cargarBraces(raiz: string): { expand: (patron: string) => string[] } {
  return createRequire(join(raiz, 'sitio', 'package.json'))('braces')
}

describe('patch-deps sobre braces 3.0.3 original', () => {
  it('deja un archivo válido: se carga sin error de sintaxis', () => {
    const { raiz, archivo } = raizCon(ORIGINAL)
    const r = ejecutar(raiz)
    expect(r.codigo).toBe(0)
    expect(r.salida).toMatch(/parche aplicado/)
    expect(readFileSync(archivo, 'utf8')).toContain('CVE-2026-93687')
    expect(() => cargarBraces(raiz)).not.toThrow()
  })

  it('el guard funciona: lo normal se expande y 60 niveles de llaves se rechazan', () => {
    const { raiz } = raizCon(ORIGINAL)
    ejecutar(raiz)
    const braces = cargarBraces(raiz)
    expect(braces.expand('a{b,c,d}e').sort()).toEqual(['abe', 'ace', 'ade'])
    expect(braces.expand('a{b,c{d,e{f,g}}h,i}j').length).toBeGreaterThan(0)
    expect(() => braces.expand('{'.repeat(60) + 'a' + '}'.repeat(60))).toThrow(/nesting depth exceeds/)
    // Tras un rechazo, la profundidad vuelve a cero: lo normal se sigue expandiendo.
    expect(braces.expand('x{1,2}').sort()).toEqual(['x1', 'x2'])
  })

  it('una segunda ejecución no vuelve a tocar el archivo', () => {
    const { raiz, archivo } = raizCon(ORIGINAL)
    ejecutar(raiz)
    const tras1 = readFileSync(archivo, 'utf8')
    const r = ejecutar(raiz)
    expect(r.codigo).toBe(0)
    expect(r.salida).toMatch(/ya parcheado/)
    expect(readFileSync(archivo, 'utf8')).toBe(tras1)
  })
})

describe('patch-deps no deja nunca un archivo roto', () => {
  it('si el texto no coincide (otra versión de braces), no escribe nada, avisa y no corta la instalación', () => {
    const otraVersion = ORIGINAL.replace('    return queue;\n  };', '    return queue; // otra versión\n  };')
    const { raiz, archivo } = raizCon(otraVersion)
    const r = ejecutar(raiz)
    expect(r.codigo).toBe(0)
    expect(r.salida).toMatch(/no se aplic/i)
    expect(readFileSync(archivo, 'utf8')).toBe(otraVersion)
    expect(() => cargarBraces(raiz)).not.toThrow()
  })

  it('un archivo con la marca pero roto (lo que dejaba la versión anterior) no se da por bueno', () => {
    const roto = ORIGINAL.replace(
      '    node.queue = [];',
      '    // SECURITY PATCH (CVE-2026-93687)\n    try {\n    node.queue = [];',
    )
    const { raiz } = raizCon(roto)
    const r = ejecutar(raiz)
    expect(r.codigo).not.toBe(0)
    expect(r.salida).toMatch(/npm ci/)
  })
})

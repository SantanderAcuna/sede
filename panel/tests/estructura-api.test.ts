/**
 * Estructura de la capa de acceso a la API del panel (Fase 0B, T6).
 *
 * La capa se llama `api`, no `services`: contiene clientes de la API, no servicios
 * de dominio. Esta prueba fija el nombre, el cliente HTTP único (`client.ts`) y
 * que nada importe ya la ruta anterior.
 */
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs'
import { join, resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

const raiz = resolve(__dirname, '..')
const archivosApi = [
  'auth', 'banners', 'client', 'contenido', 'entidad',
  'noticias', 'politicas', 'roles', 'tramites', 'usuarios',
].map((n) => `${n}.ts`)

function listar(dir: string): string[] {
  return readdirSync(dir).flatMap((nombre) => {
    const ruta = join(dir, nombre)
    return statSync(ruta).isDirectory() ? listar(ruta) : [ruta]
  })
}

describe('capa api del panel', () => {
  it('existe src/api con los 10 archivos esperados', () => {
    const dir = join(raiz, 'src/api')
    expect(existsSync(dir)).toBe(true)
    expect(readdirSync(dir).sort()).toEqual(archivosApi)
  })

  it('ya no existe src/services', () => {
    expect(existsSync(join(raiz, 'src/services'))).toBe(false)
  })

  it('ningún archivo de src ni de tests importa ni simula la ruta antigua', () => {
    const fuentes = [...listar(join(raiz, 'src')), ...listar(join(raiz, 'tests'))]
      .filter((f) => /\.(ts|vue)$/.test(f) && !f.endsWith('estructura-api.test.ts'))
    const infractores = fuentes.filter((f) => /['"`][^'"`]*\/services\/[^'"`]*['"`]/.test(readFileSync(f, 'utf8')))
    expect(infractores.map((f) => f.replace(raiz + '/', ''))).toEqual([])
  })
})

/**
 * Canal interno sitio → API (ADR-0018, T-22).
 *
 * Lo que se protege: que la IP que nginx recibe en `X-Sede-Cliente-Ip` sea, o la
 * de un visitante válido, o nada. Si esta lógica se rompe, el síntoma no es un
 * error visible: es que todos los visitantes vuelven a compartir un solo cupo.
 */
import { describe, expect, it } from 'vitest'
import {
  CABECERA_CLIENTE,
  destinoInterno,
  ipDelVisitante,
} from '../server/utils/canal-interno'

describe('ipDelVisitante', () => {
  it.each([
    ['203.0.113.9', '203.0.113.9'],
    ['  198.51.100.4  ', '198.51.100.4'],
    ['2001:db8::1', '2001:db8::1'],
    ['::ffff:203.0.113.9', '::ffff:203.0.113.9'],
  ])('acepta la IP válida %s', (entrada, esperado) => {
    expect(ipDelVisitante(entrada)).toBe(esperado)
  })

  it.each([
    ['vacío', ''],
    ['sólo espacios', '   '],
    ['texto', 'no-es-una-ip'],
    ['un nombre de host', 'sitio.example'],
    ['IPv4 fuera de rango', '256.1.1.1'],
    ['IPv4 incompleta', '203.0.113'],
    ['lista de IPs', '203.0.113.9, 198.51.100.4'],
    ['identificador de zona', 'fe80::1%eth0'],
    ['salto de línea inyectado', '203.0.113.9\r\nX-Otra: 1'],
    ['demasiado largo', `1${'0'.repeat(60)}`],
  ])('rechaza %s', (_nombre, entrada) => {
    expect(ipDelVisitante(entrada)).toBe('')
  })

  it('tolera la ausencia de la cabecera', () => {
    expect(ipDelVisitante(undefined)).toBe('')
    expect(ipDelVisitante(null)).toBe('')
  })

  it('con varias cabeceras repetidas toma la primera', () => {
    expect(ipDelVisitante(['203.0.113.9', '198.51.100.4'])).toBe('203.0.113.9')
    expect(ipDelVisitante([])).toBe('')
  })
})

describe('destinoInterno', () => {
  const base = 'http://nginx:8080'

  it('reenvía una ruta de la API conservando la consulta', () => {
    expect(destinoInterno(base, '/api/v1/busqueda?q=licencia&p=2')).toBe(
      'http://nginx:8080/api/v1/busqueda?q=licencia&p=2',
    )
  })

  it('acepta la raíz de la API', () => {
    expect(destinoInterno(base, '/api/v1')).toBe('http://nginx:8080/api/v1')
  })

  it('conserva la codificación de la consulta', () => {
    expect(destinoInterno(base, '/api/v1/busqueda?q=a%C3%B1o%20nuevo')).toBe(
      'http://nginx:8080/api/v1/busqueda?q=a%C3%B1o%20nuevo',
    )
  })

  it.each([
    ['fuera del prefijo', '/otra/ruta'],
    ['prefijo parecido', '/api/v10/x'],
    ['salida con ..', '/api/v1/../../etc/passwd'],
    ['salida con .. codificado', '/api/v1/%2e%2e/%2e%2e/admin'],
    ['otro anfitrión con //', '//otro.example/api/v1/x'],
    ['otro anfitrión con esquema', 'http://otro.example/api/v1/x'],
    ['barra invertida', '/\\otro.example/api/v1/x'],
  ])('no reenvía: %s', (_nombre, ruta) => {
    expect(destinoInterno(base, ruta)).toBeNull()
  })

  it.each([
    ['vacía', ''],
    ['no es una URL', 'nginx 8080'],
    ['esquema no web', 'ftp://nginx:21'],
    ['esquema javascript', 'javascript:alert(1)'],
  ])('sin base utilizable (%s) devuelve null', (_nombre, baseMala) => {
    expect(destinoInterno(baseMala, '/api/v1/busqueda')).toBeNull()
  })
})

describe('contrato con nginx', () => {
  it('la cabecera tiene el nombre que espera conf.d/interno.conf', () => {
    expect(CABECERA_CLIENTE).toBe('x-sede-cliente-ip')
  })
})

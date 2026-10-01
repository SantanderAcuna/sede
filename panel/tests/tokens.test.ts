/**
 * Contraste de los tokens de la paleta (RNF-B3-045, WCAG 1.4.3).
 *
 * Esta prueba existe porque el error que la motiva ya ocurrió: al alinear el
 * panel con el cobalto institucional, el token llamado `--color-text-soft`
 * quedó en #94A3B8, que sobre blanco da **2,56:1** — un color de texto que no
 * servía como texto (residuo R-P2 de la auditoría). Se mide aquí, con la fórmula
 * oficial de WCAG, para que no vuelva a pasar sin que nadie lo vea.
 *
 * Se leen los valores **del propio fichero de tokens**, no de una copia: si
 * alguien cambia un color, esta prueba cambia con él.
 */
import { readFileSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

import { describe, expect, it } from 'vitest'

const RAIZ = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const TOKENS = readFileSync(join(RAIZ, 'src/assets/styles/tokens.css'), 'utf-8')

/** Todos los tokens de color declarados, por nombre. */
function tokens(): Record<string, string> {
  const encontrados: Record<string, string> = {}
  for (const coincidencia of TOKENS.matchAll(/(--color-[a-z0-9-]+):\s*(#[0-9a-fA-F]{3,8})/g)) {
    encontrados[coincidencia[1]] = coincidencia[2]
  }
  return encontrados
}

/** Luminancia relativa, según WCAG 2.1. */
function luminancia(hex: string): number {
  const limpio = hex.replace('#', '')
  const completo = limpio.length === 3 ? limpio.split('').map((c) => c + c).join('') : limpio
  const canales = [0, 2, 4].map((i) => parseInt(completo.slice(i, i + 2), 16) / 255)
  const [r, g, b] = canales.map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4))
  return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

/** Relación de contraste entre dos colores. */
function contraste(a: string, b: string): number {
  const [claro, oscuro] = [luminancia(a), luminancia(b)].sort((x, y) => y - x)
  return (claro + 0.05) / (oscuro + 0.05)
}

const BLANCO = '#ffffff'

describe('tokens de texto', () => {
  /** Los que se usan para escribir sobre las superficies claras del panel. */
  const deTexto = [
    '--color-text',
    '--color-text-muted',
    '--color-text-soft',
    '--color-gov-blue',
    '--color-gov-blue-dark',
    '--color-state-red',
  ]

  it('todos pasan 4,5:1 sobre blanco', () => {
    const declarados = tokens()
    for (const nombre of deTexto) {
      const valor = declarados[nombre]
      expect(valor, `falta el token ${nombre}`).toBeTruthy()
      const ratio = contraste(valor, BLANCO)
      expect(ratio, `${nombre} (${valor}) da ${ratio.toFixed(2)}:1`).toBeGreaterThanOrEqual(4.5)
    }
  })

  it('el cobalto es el del Kit y no otro azul', () => {
    expect(tokens()['--color-gov-blue']).toMatch(/^#0943B5$/i)
  })

  it('el token de texto suave no vuelve a ser un gris que no se lee', () => {
    // #94A3B8 daba 2,56:1 y era el valor que tenía antes de la corrección.
    expect(tokens()['--color-text-soft']).not.toMatch(/^#94A3B8$/i)
  })
})

describe('tokens de estado', () => {
  it('verde y amarillo se declaran, pero avisando de que no sirven como texto', () => {
    const declarados = tokens()
    expect(declarados['--color-state-green']).toBeTruthy()
    expect(declarados['--color-state-yellow']).toBeTruthy()

    // Si alguien retira el aviso, tendrá que decidir antes si los usa como texto.
    expect(TOKENS).toMatch(/como color de TEXTO no llegan/)
  })

  it('los tintes de estado existen para usarse como fondo con texto oscuro', () => {
    const declarados = tokens()
    for (const nombre of ['--color-state-green-bg', '--color-state-yellow-bg', '--color-state-red-bg']) {
      expect(declarados[nombre]).toBeTruthy()
    }
  })
})

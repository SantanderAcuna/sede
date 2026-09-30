/** @type {import('tailwindcss').Config}
 *  Colores centralizados: todas las clases Tailwind referencian las
 *  variables CSS definidas en src/assets/styles/tokens.css (única fuente de verdad).
 *  Cambiar un token actualiza Tailwind y CSS plano a la vez.
 */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        gov: {
          blue:        'var(--color-gov-blue)',
          'blue-dark': 'var(--color-gov-blue-dark)',
          'blue-light':'var(--color-gov-blue-light)',
          'blue-50':   'var(--color-gov-blue-50)',
          'blue-100':  'var(--color-gov-blue-100)',
          red:         'var(--color-gov-red)',
          green:       'var(--color-state-green)',
          yellow:      'var(--color-state-yellow)',
        },
        surface: {
          DEFAULT: 'var(--color-surface)',
          alt:     'var(--color-surface-alt)',
          bg:      'var(--color-bg)',
        },
        ink: {
          DEFAULT: 'var(--color-text)',
          muted:   'var(--color-text-muted)',
          soft:    'var(--color-text-soft)',
        },
        line: {
          DEFAULT: 'var(--color-border)',
          strong:  'var(--color-border-strong)',
        },
      },
      fontFamily: {
        body: ['Inter', 'system-ui', 'sans-serif'],
        heading: ['Montserrat', 'Inter', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      boxShadow: {
        card: 'var(--shadow-sm)',
        md: 'var(--shadow-md)',
        lg: 'var(--shadow-lg)',
      },
    },
  },
  plugins: [],
};

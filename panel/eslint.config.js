// ESLint del panel (T-11, H-12).
//
// Configuración plana (flat config). Reglas: las recomendadas de ESLint, de
// typescript-eslint y de eslint-plugin-vue. No se usan las reglas «con tipos»
// (`recommendedTypeChecked`): `vue-tsc` ya comprueba los tipos en `npm run build`
// y duplicarlo haría el lint varias veces más lento sin cazar más errores.
//
// Línea base: `npm run lint` usa `--max-warnings=15`. Falla ante cualquier ERROR y
// también si las ADVERTENCIAS superan 15 (las 15 actuales son props sin tipo y
// variables sin uso en componentes de prueba). La base solo puede bajar: al
// corregir advertencias, baja el número en `package.json` (AC-11.3).
import js from '@eslint/js'
import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import tseslint from 'typescript-eslint'

export default tseslint.config(
  {
    // `openapi.d.ts` se genera desde el contrato. Los scripts de depuración que
    // vivían aquí (H-18, T-01) se archivaron en `docs/archivo/panel-depuracion/`.
    ignores: ['dist/**', 'node_modules/**', '.vitest/**', 'coverage/**', 'src/types/openapi.d.ts'],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  {
    files: ['**/*.{ts,vue}'],
    languageOptions: {
      globals: { ...globals.browser },
      parserOptions: { parser: tseslint.parser, extraFileExtensions: ['.vue'] },
    },
    rules: {
      // Las variables sin usar las vigila ya `tsc` (noUnusedLocals); aquí solo se
      // permite el prefijo `_` para lo que se ignora a propósito.
      '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
      // El panel renderiza contenido que viene del servidor: `v-html` es una puerta
      // a XSS y debe justificarse caso por caso (se deja como error).
      'vue/no-v-html': 'error',
      'no-console': ['warn', { allow: ['warn', 'error'] }],

      // Reglas de FORMATO del HTML de las plantillas (atributos por línea, comillas,
      // sangría, saltos de línea). Se apagan porque el proyecto no usa un formateador
      // y 420 avisos de estilo ocultarían los que sí importan (seguridad, props sin
      // tipo, variables sin uso). Si se adopta Prettier, se vuelven a encender aquí.
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/html-quotes': 'off',
      'vue/html-indent': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/html-self-closing': 'off',
      'vue/first-attribute-linebreak': 'off',
    },
  },
  {
    files: ['tests/**/*.{ts,mjs}', 'vite/**/*.ts', '*.config.{js,ts}'],
    languageOptions: { globals: { ...globals.node, ...globals.browser } },
  },
)

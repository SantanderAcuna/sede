import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [vue()],

  resolve: {
    alias: {
      // Alias corto hacia `src`. Tiene que estar también en la configuración de
      // TypeScript: si sólo estuviera aquí, el editor y la comprobación de tipos
      // no lo verían y el código compilaría con errores invisibles.
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  build: {
    // Sin mapas de código en producción: revelarían la estructura del panel a
    // cualquiera que abra las herramientas del navegador.
    sourcemap: false,
    outDir: 'dist',
    // Los activos llevan el contenido en el nombre, así que se pueden cachear
    // para siempre sin miedo a servir una versión vieja.
    assetsDir: 'assets',
  },

  server: {
    port: 5190,
    strictPort: true,
    proxy: {
      // En desarrollo la API vive en otro proceso. En producción es el punto de
      // entrada el que reparte, y este proxy no interviene.
      '/api': { target: 'http://127.0.0.1:8010', changeOrigin: true },
      '/storage': { target: 'http://127.0.0.1:8010', changeOrigin: true },
    },
  },
})

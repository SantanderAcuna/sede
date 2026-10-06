import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

import { versionJsonPlugin } from './vite/plugins/version-json.ts'

export default defineConfig({
  // La ruta bajo la que se sirve el panel. NO es cosmético: sin declararla, Vite
  // emite las direcciones de sus activos desde la RAÍZ (`/assets/…`) mientras el
  // punto de entrada los publica bajo el prefijo. El navegador pedía
  // `/assets/index-*.js`, caía en el sitio público y recibía una página HTML con
  // `content-type: text/html` y `nosniff`; el navegador se negaba a ejecutarla y
  // el panel quedaba EN BLANCO mientras todas las respuestas eran 200. Un fallo
  // que no protesta en ninguna parte.
  base: '/admin/',

  // Tailwind CSS 4 con el plugin oficial de Vite (sin postcss.config.js)
  // Resuelve las vulns de braces, chokidar, fast-glob, micromatch heredadas
  // de la cadena de postcss/tailwindcss 3.x.
  //
  // `versionJsonPlugin` emite `/version.json` en dev (middleware) y build
  // (asset en dist/) para que el `cache-buster.ts` del cliente pueda
  // invalidar caches obsoletos sin intervención del usuario.
  plugins: [vue(), tailwindcss(), versionJsonPlugin()],

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
    // Headers anti-cache en dev para forzar recarga de bundles tras
    // cambios críticos (fixes de iconos, dependencias, etc.).
    // Sin esto, el navegador puede servir versiones cacheadas del bundle
    // durante horas aunque el dev server tenga el código nuevo.
    headers: {
      'Cache-Control': 'no-store, no-cache, must-revalidate, proxy-revalidate',
      'Pragma': 'no-cache',
      'Expires': '0',
    },
    proxy: {
      // En desarrollo la API vive en otro proceso. En producción es el punto de
      // entrada el que reparte, y este proxy no interviene.
      '/api': { target: 'http://127.0.0.1:8000', changeOrigin: true },
      '/storage': { target: 'http://127.0.0.1:8010', changeOrigin: true },
    },
  },
})

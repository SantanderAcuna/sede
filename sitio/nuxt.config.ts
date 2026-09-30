// Configuración del sitio público.
//
// Este proceso existe por una razón concreta: el contenido de una sede
// electrónica tiene que ser rastreable y auditable SIN ejecutar JavaScript. Por
// eso se renderiza en servidor y por eso hay un proceso de Node en producción.

export default defineNuxtConfig({
  compatibilityDate: '2026-09-30',

  // El desarrollo con herramientas abiertas es cómodo y no debe llegar a
  // producción: inyectan código y revelan la estructura interna.
  devtools: { enabled: process.env.NODE_ENV !== 'production' },

  // Renderizado en servidor. Es la razón de ser de esta aplicación.
  ssr: true,

  modules: ['@pinia/nuxt'],

  css: [],

  app: {
    head: {
      // El sitio está íntegramente en castellano, y se declara.
      htmlAttrs: { lang: 'es' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      ],
      link: [
        // El Kit gov.co, vendorizado en `public/govco/` (§9.4). Se sirve desde
        // nuestro dominio en lugar de desde el CDN del Ministerio: la capa visual
        // es un atributo de disponibilidad, y depender de un tercero para que la
        // sede se vea bien no es aceptable.
        //
        // El `script.js` del Kit NO se carga, y es deliberado: se autoinicializa
        // sobre selectores que en nuestras páginas no existen y llena la consola
        // de errores. Lo que haga falta se carga en la vista que lo use.
        { rel: 'stylesheet', href: '/govco/all.css' },
      ],
    },
  },

  runtimeConfig: {
    public: {
      // La URL de la API la pone el entorno. En producción el sitio y la API
      // comparten origen, así que la ruta relativa es suficiente.
      apiUrl: process.env.SEDE_API_URL ?? '/api/v1',
      dominio: process.env.SEDE_DOMINIO ?? 'staging.santamarta.gov.co',
    },
  },

  nitro: {
    // Sin mapas de código: revelarían la estructura del servidor.
    sourceMap: false,
    compressPublicAssets: { gzip: true, brotli: true },
  },

  typescript: {
    // La comprobación de tipos es una puerta aparte, no algo que se cuele en la
    // compilación de cada cambio.
    typeCheck: false,
    strict: true,
  },

  // El servidor escucha en todas las interfaces porque vive en un contenedor y
  // es el punto de entrada el que le habla, no Internet.
  devServer: { host: '0.0.0.0', port: 3000 },
})

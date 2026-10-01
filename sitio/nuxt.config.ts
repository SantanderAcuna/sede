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

  /*
   * Sin módulos de estado. Aquí se registraba `@pinia/nuxt` y el sitio no tiene
   * ni una tienda: el estado compartido —accesibilidad, consentimiento de
   * cookies— se resuelve con `useState` de Nuxt, que ya viene con el framework.
   * Era una dependencia con su runtime cargado a cambio de nada (D-35).
   *
   * Se retira también de `main` porque una rama que exige un paquete que nadie
   * usa rompe el entorno compartido: al cambiar de rama, el módulo no está
   * instalado y Nuxt falla al arrancar con `NUXT_B8017` antes de pintar nada.
   */

  // Los componentes se usan por su nombre, sin el prefijo de la carpeta.
  //
  // Nuxt antepone por defecto el nombre del directorio, así que
  // `components/govco/CabeceraGovco.vue` se invocaría como
  // `<GovcoCabeceraGovco />`: un tartamudeo, porque el nombre del propio archivo
  // ya dice a qué familia pertenece. Con `pathPrefix: false` queda
  // `<CabeceraGovco />`, que es como se lee en las plantillas.
  components: [{ path: '~/components', pathPrefix: false }],

  css: [
    // Lo propio del sitio. El grueso de la capa visual es el Kit gov.co, que se
    // enlaza desde `app.head` por ser un archivo servido tal cual; aquí va sólo
    // lo que el Kit no resuelve, como el modo de alto contraste.
    '~/assets/css/sitio.css',
  ],

  app: {
    head: {
      // El sitio está íntegramente en castellano, y se declara.
      htmlAttrs: { lang: 'es' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      ],
      link: [
        // Bootstrap 5.0.2, y VA PRIMERO.
        //
        // No es opcional ni un extra: el `all.css` del Kit NO contiene ni una
        // sola clase de Bootstrap —ni `container`, ni `row`, ni `col-*`, ni los
        // espaciados, ni `btn`—. Es una capa que EXIGE Bootstrap cargado aparte,
        // como hacen sus propios ejemplos. Sin él la página sale sin rejilla, sin
        // márgenes y sin botones: sólo los colores del Kit, que es exactamente el
        // aspecto que tenía el sitio.
        //
        // El criterio CAG-33 es bloqueante y pide construir sobre Bootstrap 5.0.2.
        // Va antes que el Kit para que el Kit, que extiende esas clases, gane en
        // los empates; al revés, Bootstrap pisaría los estilos de gov.co.
        //
        // Se sirve desde nuestro dominio, como el Kit: la capa visual no debe
        // depender de que un tercero esté disponible. La copia es la del CDN
        // oficial y su integridad se comprobó contra el `sha384` que publican los
        // ejemplos del propio Kit.
        { rel: 'stylesheet', href: '/govco/bootstrap.min.css' },

        // El Kit gov.co, vendorizado en `public/govco/` (§9.4). Se sirve desde
        // nuestro dominio en lugar de desde el CDN del Ministerio: la capa visual
        // es un atributo de disponibilidad, y depender de un tercero para que la
        // sede se vea bien no es aceptable.
        //
        // El `script.js` del Kit NO se carga, y es deliberado: se autoinicializa
        // sobre selectores que en nuestras páginas no existen y llena la consola
        // de errores. Lo que haga falta se carga en la vista que lo use.
        { rel: 'stylesheet', href: '/govco/all.css' },

        // El icono de la entidad: el escudo sobre el azul institucional del Kit.
        //
        // Se declaran varios formatos porque cada plataforma usa el suyo —el
        // navegador el .ico, iOS el apple-touch-icon, Android el manifiesto— y
        // sin declararlos cada una muestra el suyo por defecto: hasta ahora la
        // pestaña lucía el marcador verde de Nuxt, que no es de nadie.
        { rel: 'icon', href: '/favicon.ico', sizes: '32x32' },
        { rel: 'icon', href: '/icono-192.png', type: 'image/png', sizes: '192x192' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        { rel: 'manifest', href: '/site.webmanifest' },
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

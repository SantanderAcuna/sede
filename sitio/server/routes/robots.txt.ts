/**
 * `robots.txt` por entorno.
 *
 * El contenido lo construye `server/utils/robots-txt.ts` (lógica pura, con pruebas en
 * `tests/robots-txt.test.ts`); esta ruta solo lee la configuración y fija las cabeceras.
 *
 * La decisión de indexar sale de `runtimeConfig.public.indexable`, la misma variable que
 * gobierna la cabecera `X-Robots-Tag` de `nuxt.config.ts`: una sola decisión, dos
 * mecanismos, y no pueden discrepar.
 */
import { construirRobots } from '../utils/robots-txt'

export default defineEventHandler((evento): string => {
  const config = useRuntimeConfig(evento)

  setHeader(evento, 'content-type', 'text/plain; charset=utf-8')
  // Cinco minutos: si se cambia el entorno, el archivo deja de mentir enseguida.
  setHeader(evento, 'cache-control', 'public, max-age=300')

  return construirRobots({
    dominio: String(config.public.dominio ?? ''),
    indexable: config.public.indexable === true,
  })
})

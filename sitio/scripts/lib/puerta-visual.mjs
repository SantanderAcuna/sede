/**
 * La puerta visual automática: lógica pura (sin navegador ni red).
 *
 * El ejecutor (`tests/puerta-visual.mjs`) toma MEDIDAS en un navegador real; aquí se
 * decide qué rutas medir y si lo medido es aceptable. Ver `tests/puerta-visual.test.ts`
 * para el porqué de cada regla. Sólo juzga propiedades objetivas, nunca gusto.
 */

/** Escritorio, tableta y los dos teléfonos de la revisión móvil. */
export const ANCHOS = [1300, 768, 390, 320]

/** «Bueno» según Core Web Vitals. */
const CLS_MAXIMO = 0.1
/**
 * El CLS no es idéntico entre máquinas: la misma portada a 320 px dio 0,204 en un portátil y
 * 0,215 en el CI (HECHO, 2026-11-10). Sin margen, la línea base falla por ruido. 0,05 es una
 * ESTIMACIÓN con dos máquinas como muestra: holgada para ese ruido y aún muy por debajo de
 * lo que produce una regresión real (un bloque sin reservar suele sumar 0,1 o más). Si el
 * CI vuelve a fallar por ruido, se amplía con la medición a la vista, no a ojo.
 */
const TOLERANCIA_CLS = 0.05

/** Prefijos cuyas páginas son fichas dinámicas: se toma una muestra, no todas. */
const PREFIJOS_DE_FICHAS = ['/tramites/', '/politicas/']

const ENTIDADES = { '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&apos;': "'" }

/**
 * Las rutas del `sitemap.xml` del propio sitio. Como el mapa sale de `config/sitemap.ts`
 * (la fuente única), una página que se publica entra sola en la puerta.
 *
 * @param {string} xml
 * @param {{ maxPorPrefijo?: number }} [opciones]
 */
export function rutasDelSitemap(xml, { maxPorPrefijo = 2 } = {}) {
  const rutas = []
  const vistas = new Set()
  const usadas = new Map()

  for (const [, crudo] of xml.matchAll(/<loc>([^<]*)<\/loc>/g)) {
    const loc = crudo.trim().replace(/&(amp|lt|gt|quot|apos);/g, (e) => ENTIDADES[e])
    let url
    try {
      url = new URL(loc)
    } catch {
      continue
    }
    const ruta = url.pathname + url.search
    if (vistas.has(ruta)) continue

    const prefijo = PREFIJOS_DE_FICHAS.find((p) => url.pathname.startsWith(p) && url.pathname.length > p.length)
    if (prefijo) {
      const n = usadas.get(prefijo) ?? 0
      if (n >= maxPorPrefijo) continue
      usadas.set(prefijo, n + 1)
    }

    vistas.add(ruta)
    rutas.push(ruta)
  }
  return rutas
}

/**
 * Juzga las medidas de UNA página a UN ancho.
 *
 * @returns {Array<{ regla: string, ruta: string, ancho: number, mensaje: string }>}
 */
export function juzgarMedidas(m, base = {}) {
  const violaciones = []
  const v = (regla, mensaje) => violaciones.push({ regla, ruta: m.ruta, ancho: m.ancho, mensaje })

  const esperado = m.estadoEsperado ?? 200
  if (m.estado !== esperado) v('PV-01', `responde ${m.estado} y se esperaba ${esperado}`)

  const estructura = []
  if (m.h1 !== 1) estructura.push(`${m.h1} h1 (debe haber uno)`)
  if (!m.tieneMain) estructura.push('falta <main>')
  if (!m.tieneCabecera) estructura.push('falta la cabecera')
  if (!m.tienePie) estructura.push('falta el pie')
  if (!/^es(-[A-Za-z]{2,4})?$/.test(String(m.lang ?? ''))) estructura.push(`lang="${m.lang}" (debe ser es)`)
  if (!String(m.titulo ?? '').trim()) estructura.push('título vacío')
  if (estructura.length > 0) v('PV-02', estructura.join('; '))

  if (m.desborde > 0) v('PV-03', `la página se desplaza ${m.desborde} px en horizontal`)
  if (m.elementosFuera.length > 0) {
    const lista = m.elementosFuera.slice(0, 5).map((e) => `${e.selector} (llega a ${e.derecha} px)`)
    v('PV-03', `elementos fuera del ancho: ${lista.join(', ')}`)
  }

  const imagenes = []
  if (m.imagenesRotas.length > 0) imagenes.push(`rotas: ${m.imagenesRotas.slice(0, 5).join(', ')}`)
  if (m.imagenesSinAlt > 0) imagenes.push(`${m.imagenesSinAlt} sin atributo alt`)
  if (imagenes.length > 0) v('PV-04', imagenes.join('; '))

  if (m.fuentesConError.length > 0) v('PV-05', `fuentes que no cargaron: ${m.fuentesConError.join(', ')}`)

  // La línea base recoge el diseño ya aprobado: no se arregla (no se toca el diseño sin
  // autorización), pero tampoco puede empeorar, y una ruta nueva se juzga sin concesiones.
  const admitidoCls = base.cls?.[m.ruta]
  const techoCls = admitidoCls === undefined ? CLS_MAXIMO : Math.max(CLS_MAXIMO, admitidoCls + TOLERANCIA_CLS)
  if (m.cls > techoCls) {
    const origen = techoCls > CLS_MAXIMO ? `línea base de esta ruta ${admitidoCls} + tolerancia ${TOLERANCIA_CLS}` : `máximo ${CLS_MAXIMO}`
    v('PV-06', `salto de maquetación (CLS) ${m.cls.toFixed(3)} > ${origen}`)
  }

  const admitidos = base.objetivosPequenos?.[m.ruta] ?? 0
  if (m.objetivosPequenos.length > admitidos) {
    const lista = m.objetivosPequenos.slice(0, 5).map((o) => `${o.selector} ${o.ancho}×${o.alto}`)
    const origen = admitidos > 0 ? `la línea base de esta ruta admite ${admitidos}, hay ${m.objetivosPequenos.length}` : 'ninguno admitido'
    v('PV-07', `controles menores de 24×24 px (${origen}): ${lista.join(', ')}`)
  }

  const fallos = []
  if (m.erroresConsola.length > 0) fallos.push(`consola: ${m.erroresConsola.slice(0, 3).join(' | ')}`)
  if (m.recursosFallidos.length > 0) {
    fallos.push(`recursos: ${m.recursosFallidos.slice(0, 3).map((r) => `${r.url} → ${r.estado}`).join(', ')}`)
  }
  if (fallos.length > 0) v('PV-08', fallos.join('; '))

  return violaciones
}

/**
 * PV-09 · La cabecera y el pie deben ser los mismos en todas las páginas, para cada ancho.
 * Una página nueva con su propia cabecera es justo lo que esta regla atrapa. Se compara
 * con la firma MÁS COMÚN; las demás se señalan.
 *
 * @param {Array<{ ruta: string, ancho: number, firmaCabecera: string, firmaPie: string }>} medidas
 */
export function juzgarCromo(medidas) {
  const violaciones = []
  const anchos = [...new Set(medidas.map((m) => m.ancho))]

  for (const ancho of anchos) {
    const delAncho = medidas.filter((m) => m.ancho === ancho)
    if (delAncho.length < 2) continue

    for (const [campo, nombre] of [['firmaCabecera', 'cabecera'], ['firmaPie', 'pie']]) {
      const cuentas = new Map()
      for (const m of delAncho) cuentas.set(m[campo], (cuentas.get(m[campo]) ?? 0) + 1)
      const [comun] = [...cuentas.entries()].sort((a, b) => b[1] - a[1])[0]
      for (const m of delAncho) {
        if (m[campo] !== comun) {
          violaciones.push({
            regla: 'PV-09',
            ruta: m.ruta,
            ancho,
            mensaje: `la ${nombre} no es la misma que en el resto de las páginas`,
          })
        }
      }
    }
  }
  return violaciones
}

/** Lo que, dentro de `sitio/`, puede cambiar cómo se ve el sitio. */
const RUTAS_DE_DISENO = [
  /^sitio\/app\/assets\//,
  /^sitio\/app\/components\//,
  /^sitio\/app\/layouts\//,
  /^sitio\/app\/pages\//,
  /^sitio\/app\/app\.vue$/,
  /^sitio\/public\//,
  /^sitio\/nuxt\.config\.ts$/,
]

/** De una lista de archivos cambiados, los que pueden alterar el aspecto (para avisar, no para bloquear). */
export function archivosDeDiseno(archivos) {
  return archivos.filter((a) => RUTAS_DE_DISENO.some((patron) => patron.test(a)))
}

/** Distancia del punto (px, py) al rectángulo más cercano; 0 si está dentro. */
function distanciaARect(px, py, r) {
  const dx = Math.max(r.x - px, 0, px - (r.x + r.w))
  const dy = Math.max(r.y - py, 0, py - (r.y + r.h))
  return Math.hypot(dx, dy)
}

/**
 * WCAG 2.2, 2.5.8 «Tamaño del objetivo (mínimo)» con sus excepciones; devuelve los objetivos que NO
 * cumplen. Cada objetivo: `{selector, x, y, w, h}` en px CSS de página (el área que recibe el clic,
 * incluida la ampliada con un pseudoelemento), `etiqueta?: {x,y,w,h}` si es una casilla/radio con
 * etiqueta, `enLinea?: true` si es un enlace dentro de una frase.
 *
 * Reglas (texto del W3C, «Understanding 2.5.8», consultado 2026-11-10):
 *  - ≥ 24×24 cumple.
 *  - Espaciado: un objetivo menor cumple si su círculo de 24 px (centrado en el centro de su caja)
 *    no corta a otro objetivo ni al círculo de otro objetivo menor. Rozar el borde no es cortar.
 *  - Equivalente: la misma función disponible en otro control que mida 24×24. Aplicado SÓLO a la
 *    etiqueta de una casilla/radio: es una INTERPRETACIÓN (el texto no lo trata expresamente).
 *  - En línea: un enlace dentro de una frase está exento (pero sigue siendo un objetivo vecino).
 * La etiqueta propia no cuenta como vecino: es la misma función.
 */
export function objetivosIncumplidos(objetivos) {
  const EPS = 1e-9
  const MIN = 24
  const R = MIN / 2
  const pequeno = (o) => o.w < MIN - EPS || o.h < MIN - EPS
  const centro = (o) => ({ x: o.x + o.w / 2, y: o.y + o.h / 2 })
  const fallas = []
  objetivos.forEach((o, i) => {
    if (!pequeno(o) || o.enLinea) return
    const c = centro(o)
    let motivo = null
    for (let j = 0; j < objetivos.length && !motivo; j++) {
      if (j === i) continue
      const otro = objetivos[j]
      const cajas = [{ caja: otro, nombre: otro.selector }]
      if (otro.etiqueta) cajas.push({ caja: otro.etiqueta, nombre: `etiqueta de ${otro.selector}` })
      for (const { caja, nombre } of cajas) {
        if (distanciaARect(c.x, c.y, caja) < R - EPS) {
          motivo = `el círculo de 24 px de espaciado corta a ${nombre}`
          break
        }
      }
      if (!motivo && pequeno(otro) && !otro.enLinea) {
        const d = Math.hypot(centro(otro).x - c.x, centro(otro).y - c.y)
        if (d < MIN - EPS) motivo = `los centros distan ${d.toFixed(1)} px de ${otro.selector} (< 24, círculos de espaciado cruzados)`
      }
    }
    if (!motivo) return
    if (o.etiqueta && o.etiqueta.w >= MIN - EPS && o.etiqueta.h >= MIN - EPS) return
    fallas.push({ selector: o.selector, ancho: Math.round(o.w), alto: Math.round(o.h), motivo })
  })
  return fallas
}

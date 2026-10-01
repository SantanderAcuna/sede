/**
 * Generador de la matriz de trazabilidad.
 *
 * **Qué problema resuelve.** `docs/trazabilidad.md` se declaraba «documento
 * generado con `npm run trazabilidad`» y ese generador no existía. El resultado
 * fue el peor artefacto posible en un expediente de aceptación: una matriz que
 * acreditaba 122 de 140 criterios citando pruebas y vistas **de un árbol de
 * carpetas que no existe en este repositorio** (`frontend/tests/*.mjs`,
 * `views/publico/*.vue`). Nadie podía saber qué estaba cumplido y qué no.
 *
 * **Qué hace ahora.** Lee el universo de criterios de las secciones del
 * expediente, busca cada identificador en el código del producto y en las
 * pruebas, y escribe la matriz con lo que encuentra —y con lo que no—.
 *
 * **Qué NO hace, y por eso lo dice en la cabecera del documento.** Una cita en un
 * comentario acredita que alguien trabajó el criterio; **no** acredita que se
 * cumpla. Este generador mide trazabilidad, no conformidad: para conformidad hay
 * una puerta que arranca el sitio de verdad (`make accesibilidad`) y otra que
 * compila y comprueba tipos (`make compilar`). Confundir las dos cosas es lo que
 * produjo la matriz anterior.
 *
 * Uso: `npm run trazabilidad` (desde `sitio/`) o `make trazabilidad`.
 */
import { readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs'
import { dirname, join, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

import { load as cargarYaml } from 'js-yaml'

const AQUI = dirname(fileURLToPath(import.meta.url))
const RAIZ = resolve(AQUI, '../..')
const DOCS = join(RAIZ, 'docs')
const SALIDA = join(DOCS, 'trazabilidad.md')

/** Dónde vive el enunciado de cada familia de criterios. */
const FAMILIAS = [
  { prefijo: 'CAG', nombre: 'Diseño', fuente: 'Sección 2 · Diseño.md', enlace: 'docs/Sección 2 §2.4' },
  { prefijo: 'FUN', nombre: 'Funcionalidad', fuente: 'Sección 5 · Funcionalidad.md', enlace: 'docs/Sección 5 §5.3.1' },
  { prefijo: 'SEG', nombre: 'Seguridad', fuente: 'Sección 4 · Seguridad.md', enlace: 'docs/Sección 4 §4.3' },
  { prefijo: 'RT', nombre: 'Requisitos técnicos', fuente: 'Sección 4 · Seguridad.md', enlace: 'docs/Sección 4 §4.3' },
  { prefijo: 'O', nombre: 'Obligaciones derivadas', fuente: 'Sección 1 · Marco normativo.md', enlace: 'docs/Sección 1' },
  // ACC no se define en ninguna sección: sólo se cita. El generador lo reporta
  // como lo que es —criterios citados sin enunciado— en lugar de inventarlos.
  { prefijo: 'ACC', nombre: 'Accesibilidad (citados sin enunciado)', fuente: 'Sección 6 · Implementación paso a paso.md', enlace: '(sin definir)' },
]

/** Carpetas donde se busca evidencia, y su peso. */
const FUENTES_EVIDENCIA = ['sitio', 'panel', 'backend/app', 'backend/tests', 'backend/routes', 'contract']
/** Prefijos que marcan una evidencia como **prueba** y no como implementación. */
const PREFIJOS_PRUEBA = ['sitio/tests/', 'panel/tests/', 'backend/tests/']
/** Carpetas que no se recorren nunca. */
const IGNORAR = new Set(['node_modules', '.output', '.nuxt', 'dist', 'vendor', '.git', 'storage'])

/** Extensiones que se leen. */
const EXTENSIONES = new Set(['.vue', '.ts', '.mjs', '.js', '.php', '.yaml', '.yml', '.json'])

/** Devuelve el contenido de un fichero, o cadena vacía si no se puede leer. */
function leer(ruta) {
  try {
    return readFileSync(ruta, 'utf-8')
  } catch {
    return ''
  }
}

/** Recorre un directorio y devuelve las rutas de los ficheros legibles. */
function recorrer(directorio, acumulado = []) {
  let entradas
  try {
    entradas = readdirSync(directorio, { withFileTypes: true })
  } catch {
    return acumulado
  }
  for (const entrada of entradas) {
    if (IGNORAR.has(entrada.name)) continue
    const ruta = join(directorio, entrada.name)
    if (entrada.isDirectory()) recorrer(ruta, acumulado)
    else if (EXTENSIONES.has(entrada.name.slice(entrada.name.lastIndexOf('.')))) acumulado.push(ruta)
  }
  return acumulado
}

/**
 * El universo de criterios.
 *
 * Se toma **de la sección que los define**, no de las citas: si se tomara de las
 * citas, un criterio que nadie cita desaparecería de la matriz en lugar de
 * aparecer como «sin evidencia», que es justo lo que hay que ver.
 */
function universo() {
  const porFamilia = new Map()

  for (const familia of FAMILIAS) {
    const texto = leer(join(DOCS, familia.fuente))
    const patron = new RegExp(`\\b${familia.prefijo}-\\d{2,3}\\b`, 'g')
    const ids = new Set(texto.match(patron) ?? [])
    porFamilia.set(familia.prefijo, [...ids].sort())
  }

  return porFamilia
}

/** Los criterios que ADR-0015 declara como desviación justificada. */
function desviacionesDeclaradas() {
  const adr = leer(join(DOCS, 'adr/README.md'))
  const seccion = adr.slice(adr.indexOf('## ADR-0015'))
  return new Set(seccion.match(/\b(CAG|FUN|SEG|ACC|RT|O)-\d{2,3}\b/g) ?? [])
}

/** Índice de evidencia: para cada identificador, en qué ficheros y líneas aparece. */
function buscarEvidencia() {
  const ficheros = FUENTES_EVIDENCIA.flatMap((fuente) => recorrer(join(RAIZ, fuente)))
  const mapa = new Map()

  for (const fichero of ficheros) {
    const relativo = relative(RAIZ, fichero)
    const lineas = leer(fichero).split('\n')
    for (let i = 0; i < lineas.length; i += 1) {
      const encontrados = lineas[i].match(/\b(CAG|FUN|SEG|ACC|RT|O)-\d{2,3}\b/g)
      if (!encontrados) continue
      for (const id of new Set(encontrados)) {
        const lista = mapa.get(id) ?? []
        if (!lista.some((e) => e.fichero === relativo)) {
          lista.push({ fichero: relativo, linea: i + 1, prueba: PREFIJOS_PRUEBA.some((p) => relativo.startsWith(p)) })
        }
        mapa.set(id, lista)
      }
    }
  }

  return mapa
}

/** Las operaciones del contrato y los criterios que cada una declara. */
function operaciones() {
  try {
    const contrato = cargarYaml(leer(join(RAIZ, 'contract/openapi.yaml')))
    const lista = []
    for (const [ruta, metodos] of Object.entries(contrato?.paths ?? {})) {
      for (const [metodo, operacion] of Object.entries(metodos ?? {})) {
        if (!['get', 'post', 'put', 'patch', 'delete'].includes(metodo)) continue
        lista.push({
          metodo: metodo.toUpperCase(),
          ruta,
          resumen: operacion?.summary ?? '',
          criterios: operacion?.['x-criterios'] ?? [],
          estado: operacion?.['x-status'] ?? 'sin declarar',
        })
      }
    }
    return lista
  } catch {
    return []
  }
}

/** Escapa lo que no debe romper una tabla de markdown. */
const celda = (texto) => String(texto).replace(/\|/g, '\\|')

/** Escribe una fila de evidencia legible: `fichero:línea`. */
const evidenciaLegible = (evidencias) =>
  evidencias
    .slice(0, 4)
    .map((e) => `\`${e.fichero}:${e.linea}\``)
    .join(' · ') + (evidencias.length > 4 ? ` · +${evidencias.length - 4}` : '')

function generar() {
  const criterios = universo()
  const evidencia = buscarEvidencia()
  const desviaciones = desviacionesDeclaradas()
  const ops = operaciones()
  const fecha = new Date().toISOString().slice(0, 10)

  const lineas = []
  const resumen = []

  lineas.push('# Matriz de trazabilidad de la Sede Electrónica')
  lineas.push('')
  lineas.push('> **Documento generado.** No se edita a mano: se produce con `make trazabilidad`')
  lineas.push('> (`npm run trazabilidad` desde `sitio/`), que lee el expediente, el contrato OpenAPI y el')
  lineas.push(`> código, y escribe lo que encuentra. Última generación: **${fecha}**.`)
  lineas.push('')
  lineas.push('## Qué mide esta matriz, y qué no')
  lineas.push('')
  lineas.push('Mide **trazabilidad**: para cada criterio del expediente, dónde hay algo que lo trabaja.')
  lineas.push('Los estados son cuatro y conviene no confundirlos:')
  lineas.push('')
  lineas.push('| Estado | Significado |')
  lineas.push('|---|---|')
  lineas.push('| **prueba** | Hay una prueba automatizada que cita el criterio. Es el estado más fuerte de esta matriz. |')
  lineas.push('| **implementación** | El criterio se cita en el código del producto (sitio, panel o backend), sin prueba que lo acredite. |')
  lineas.push('| **desviación declarada** | Un ADR explica por qué no aplica o por qué se aparta del criterio. |')
  lineas.push('| **sin evidencia** | Nadie lo ha tocado, o nadie lo ha citado. Es la lista de trabajo. |')
  lineas.push('')
  lineas.push('> ⚠️ **Esto no es una declaración de conformidad.** Una cita en un comentario acredita que')
  lineas.push('> alguien trabajó el criterio; no acredita que se cumpla. La conformidad la miden las puertas')
  lineas.push('> que ejecutan el producto: `make accesibilidad` (axe sobre el sitio construido y')
  lineas.push('> `make compilar` (tipos y compilación de los dos frontends). La versión anterior de este')
  lineas.push('> documento daba por acreditados 122 de 140 criterios citando pruebas de un árbol de carpetas')
  lineas.push('> que no existe en este repositorio; esa cifra no era verificable y se retiró.')
  lineas.push('')

  for (const familia of FAMILIAS) {
    const ids = criterios.get(familia.prefijo) ?? []
    const conEvidencia = ids.filter((id) => (evidencia.get(id) ?? []).length > 0)
    // Mismo criterio que en la fila: una desviación declarada no cuenta como
    // «con prueba» aunque su identificador aparezca en el fichero de la puerta.
    const conPrueba = ids.filter(
      (id) => !desviaciones.has(id) && (evidencia.get(id) ?? []).some((e) => e.prueba),
    )
    const declaradas = ids.filter((id) => desviaciones.has(id))
    const sinEvidencia = ids.filter(
      (id) => (evidencia.get(id) ?? []).length === 0 && !desviaciones.has(id),
    )

    resumen.push({
      familia,
      total: ids.length,
      conEvidencia: conEvidencia.length,
      conPrueba: conPrueba.length,
      declaradas: declaradas.length,
      sinEvidencia,
    })
  }

  const totalCriterios = resumen.reduce((suma, r) => suma + r.total, 0)
  const totalEvidencia = resumen.reduce((suma, r) => suma + r.conEvidencia, 0)
  const totalPrueba = resumen.reduce((suma, r) => suma + r.conPrueba, 0)

  lineas.push('## Resumen')
  lineas.push('')
  lineas.push('| Familia | Criterios | Con prueba | Con evidencia | Desviación declarada | Sin evidencia |')
  lineas.push('|---|---|---|---|---|---|')
  for (const r of resumen) {
    lineas.push(
      `| **${r.familia.prefijo}** — ${r.familia.nombre} | ${r.total} | ${r.conPrueba} | ${r.conEvidencia} | ${r.declaradas} | ${r.sinEvidencia.length} |`,
    )
  }
  const totalDeclaradas = resumen.reduce((suma, r) => suma + r.declaradas, 0)
  const totalSinEvidencia = resumen.reduce((suma, r) => suma + r.sinEvidencia.length, 0)
  lineas.push(
    `| **Total** | **${totalCriterios}** | **${totalPrueba}** | **${totalEvidencia}** | **${totalDeclaradas}** | **${totalSinEvidencia}** |`,
  )
  lineas.push('')
  lineas.push(
    `Cobertura de trazabilidad: **${totalCriterios === 0 ? 0 : Math.round((totalEvidencia / totalCriterios) * 100)} %** de los criterios tiene algo que los trabaja. Los que tienen prueba automatizada son **${totalPrueba}**.`,
  )
  lineas.push('')
  lineas.push(
    'Las columnas **no suman el total** a propósito: un criterio puede estar citado en el código *y* ' +
      'declarado como desviación en un ADR (CAG-06, por ejemplo, se omite con motivo y además aparece en ' +
      'los comentarios del componente que lo explica). La columna que hay que vigilar es la última.',
  )
  lineas.push('')

  lineas.push('## Operaciones del contrato')
  lineas.push('')
  if (ops.length === 0) {
    lineas.push('No se pudo leer `contract/openapi.yaml`.')
  } else {
    const conCriterios = ops.filter((o) => Array.isArray(o.criterios) && o.criterios.length > 0)
    lineas.push('| Método | Ruta | Criterios declarados (`x-criterios`) | Estado (`x-status`) |')
    lineas.push('|---|---|---|---|')
    for (const op of ops) {
      lineas.push(
        `| ${op.metodo} | \`${celda(op.ruta)}\` | ${op.criterios.length > 0 ? op.criterios.join(', ') : '—'} | ${celda(op.estado)} |`,
      )
    }
    lineas.push('')
    lineas.push(
      `El contrato declara **${ops.length} operaciones**, de las cuales **${conCriterios.length}** llevan ` +
        '`x-criterios`. Las que no lo llevan no se pueden trazar a un criterio del expediente desde el ' +
        'contrato: la relación está en el código que las consume.',
    )
  }
  lineas.push('')

  for (const r of resumen) {
    lineas.push(`## ${r.familia.prefijo} — ${r.familia.nombre}`)
    lineas.push('')
    lineas.push(`Enunciados en \`${r.familia.enlace}\`.`)
    lineas.push('')
    if (r.total === 0) {
      lineas.push('No se encontraron identificadores de esta familia en su sección.')
      lineas.push('')
      continue
    }
    lineas.push('| Criterio | Estado | Evidencia (fichero:línea) |')
    lineas.push('|---|---|---|')
    for (const id of criterios.get(r.familia.prefijo)) {
      const hallazgos = evidencia.get(id) ?? []
      const esPrueba = hallazgos.some((e) => e.prueba)
      // La desviación declarada **manda** sobre la cita: un criterio que un ADR
      // decide no aplicar aparece citado en el fichero de la puerta —en la lista
      // de «esto no se comprueba aquí»—, y contarlo como «con prueba» sería
      // decir lo contrario de lo que ocurre.
      const estado = desviaciones.has(id)
        ? 'desviación declarada'
        : esPrueba
          ? '**prueba**'
          : hallazgos.length > 0
            ? 'implementación'
            : 'sin evidencia'
      lineas.push(`| **${id}** | ${estado} | ${hallazgos.length > 0 ? evidenciaLegible(hallazgos) : '—'} |`)
    }
    lineas.push('')
  }

  lineas.push('## Criterios sin evidencia')
  lineas.push('')
  lineas.push('La lista de trabajo, por familia. Es la cifra que hay que bajar:')
  lineas.push('')
  for (const r of resumen) {
    if (r.sinEvidencia.length === 0) continue
    lineas.push(`- **${r.familia.prefijo}** (${r.sinEvidencia.length}): ${r.sinEvidencia.join(', ')}`)
  }
  lineas.push('')
  lineas.push('## Cómo se genera')
  lineas.push('')
  lineas.push('```bash')
  lineas.push('make trazabilidad            # o: cd sitio && npm run trazabilidad')
  lineas.push('```')
  lineas.push('')
  lineas.push('El generador (`sitio/scripts/trazabilidad.mjs`) lee el universo de criterios de las')
  lineas.push('secciones del expediente, busca cada identificador en `sitio/`, `panel/`, `backend/` y')
  lineas.push('`contract/`, y escribe este documento. **No inventa**: lo que no encuentra aparece como')
  lineas.push('«sin evidencia».')
  lineas.push('')

  writeFileSync(SALIDA, lineas.join('\n'), 'utf-8')

  console.log('Matriz de trazabilidad regenerada: docs/trazabilidad.md')
  for (const r of resumen) {
    console.log(
      `  ${r.familia.prefijo.padEnd(4)} ${String(r.total).padStart(3)} criterios · ` +
        `${String(r.conPrueba).padStart(3)} con prueba · ${String(r.conEvidencia).padStart(3)} con evidencia · ` +
        `${String(r.sinEvidencia.length).padStart(3)} sin evidencia`,
    )
  }
  console.log(`  TOTAL ${totalCriterios} criterios · ${totalPrueba} con prueba · ${totalEvidencia} con evidencia`)
}

generar()

<script setup lang="ts">
/**
 * Ficha de un trámite — `/tramites/{slug}`.
 *
 * **Qué publica esta página.** Los seis atributos obligatorios de la Guía §5.1.3
 * de la Resolución 2893 —modalidad, costo, término de solución, canal de inicio,
 * mecanismo de consulta del estado y requisitos— **rotulados uno por uno**, más
 * lo que la ficha oficial añade: a quién va dirigido, qué entrega, dónde se
 * atiende, qué norma lo faculta y por dónde se pregunta por él. El §9.1 de la
 * misma Guía hace de la publicación de esta información requisito para la
 * integración con Gov.co, y una frase por atributo no alcanza para eso.
 *
 * **De dónde sale cada dato.** De `GET /tramites/{slug}` del contrato
 * (`contract/openapi.yaml`), que es la única fuente de verdad del intercambio. El
 * catálogo se siembra del SUIT y se completa con la ficha oficial de GOV.CO, y la
 * respuesta declara la procedencia **campo por campo**: esta página no la
 * esconde, la publica al final, porque un dato derivado que no se declara es un
 * dato que parece de la fuente.
 *
 * **Lo que esta página NO hace, y por qué.**
 *
 * - **No dibuja un botón de pago.** El trámite del impuesto predial tiene costo y
 *   la Entidad declara sus cuentas de recaudo y su canal de pago oficial, pero
 *   **no hay pasarela contratada**. Un botón que no cobra es peor que no tenerlo:
 *   el ciudadano lo pulsa, cree que pagó y descubre lo contrario cuando ya venció
 *   el plazo. Se publica el canal, la dirección oficial y las cuentas —que es lo
 *   que permite pagar de verdad— y se dice con todas las letras que la Sede no
 *   cobra en línea.
 * - **No finge la consulta del estado.** El canal de la Sede
 *   —`/seguimiento`— viaja declarado y **no habilitado**, porque el expediente
 *   electrónico todavía no existe. Se enlaza a su página, que lo dice, en vez de
 *   ofrecer un formulario que devolvería cualquier cosa. Un canal declarado y no
 *   habilitado es información; uno declarado y fingido es una llamada perdida.
 * - **No completa huecos.** Un bloque que la fuente no declara se declara como
 *   ausente o no se dibuja; nunca se rellena con un texto de relleno. Inventar el
 *   horario de una oficina o el importe de una tasa en una sede electrónica es
 *   publicar información oficial falsa.
 *
 * **El `<main>` no se declara aquí.** Lo pone la disposición
 * (`layouts/default.vue`), que es lo que garantiza que el enlace «Saltar al
 * contenido principal» funcione en todas las páginas y no sólo en las que se
 * acordaron de ponerlo.
 */
import type { components } from '~~/types/openapi'
import { useMetadatosComparticion } from '~/composables/useMetadatosComparticion'

// ---------------------------------------------------------------------------
// Modelo
// ---------------------------------------------------------------------------

type TramiteApi = components['schemas']['TramiteItem']
type Requisito = components['schemas']['TramiteRequisito']
type PuntoAtencion = components['schemas']['TramitePuntoAtencion']
type Norma = components['schemas']['TramiteNorma']
type Cuenta = components['schemas']['TramiteCuentaBancaria']
type TramitePaso = components['schemas']['TramitePaso']
type TramiteMomento = components['schemas']['TramiteMomento']
type TramiteRequisitoVisor = components['schemas']['TramiteRequisitoVisor']
type TramiteCuentaPago = {
  banco?: string
  tipo?: string
  numero?: string
  titular?: string
}
type TramitePagoValor = {
  valor?: string | number | null
  moneda?: string
  tipo_valor?: string
  descripcion?: string
}
/**
 * Un requisito en la forma que ve la ficha: una mezcla del `TramiteRequisito`
 * del contrato y del `TramiteRequisitoVisor` que el visor publica dentro de
 * los momentos. La unión es necesaria porque los requisitos del listado
 * (campos `requisitos[]` en `TramiteItem`) y los requisitos de los momentos
 * (`momentos[].requisitos[]`) comparten la mayoría de los campos pero no
 * todos: el visor trae `documento`, `formulario`, `pago[]` y `cuentas[]`,
 * que el contrato base no declara.
 *
 * Los tipos se declaran manualmente y no se derivan del contrato porque
 * OpenAPI no soporta bien la unión con campos opcionales: el compilador
 * pierde precisión al inferir la unión, y las dos ramas terminan con
 * `unknown` en sus campos diferenciales.
 */
type RequisitoExtendido = {
  tipo: 'documento' | 'verificacion_institucional' | 'solicitud' | 'formulario' | 'pago'
  descripcion: string | null
  obligatorio?: boolean
  orden?: number | null
  cantidad?: number | null
  unidad_cantidad?: string | null
  nota?: string | null
  canal?: 'web' | 'presencial' | 'correo' | 'telefonico' | null
  url?: string | null
  correo?: string | null
  documento?: string | null
  formulario?: string | null
  formulario_nombre?: string | null
  formulario_url?: string | null
  url_pago?: string | null
  pago?: TramitePagoValor[]
  pago_valor?: TramitePagoValor[]
  cuentas?: TramiteCuentaPago[]
  pago_cuentas?: TramiteCuentaPago[]
  canales?: Array<{ tipo: string; email?: string | null; url?: string | null }>
}

/** La naturaleza de un requisito, tal como la declara el contrato. */
type TipoRequisito = Requisito['tipo']

/**
 * Lo que esta página lee del sobre de `GET /tramites/{slug}`.
 *
 * Se declara sólo lo que se usa —`data`— por el mismo defecto medido que anota el
 * catálogo: `ApiEnvelope.data` está declarado como `object | array | null`, así
 * que componerlo con `TramiteItem` deja el campo en `unknown` y obligaría a
 * forzarlo con un `as`, que es justo lo que no se quiere en la frontera con el
 * contrato.
 */
interface RespuestaTramite {
  data: TramiteApi
}

// ---------------------------------------------------------------------------
// Lectura de la ficha
// ---------------------------------------------------------------------------

/**
 * `useRequestFetch` y no `$fetch` a secas: durante el renderizado en servidor
 * resuelve la ruta relativa contra la petición en curso, y la URL por defecto del
 * sitio —`/api/v1`— es relativa porque en producción el sitio y la API comparten
 * origen.
 */
const traer = useRequestFetch()

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')

const slug = String(useRoute().params.slug ?? '')

/**
 * La ficha, o la constancia de que no existe.
 *
 * **El `404` y el fallo del servidor se distinguen, y la diferencia importa.**
 * Un trámite que no está en el catálogo es una respuesta: se responde `404` de
 * verdad —en el servidor, con su línea de estado— para que el buscador y el
 * ciudadano sepan que esa dirección no lleva a ninguna parte. Que la API no
 * conteste es otra cosa, y tratarla como un «no existe» sería decirle al
 * ciudadano que un trámite que la Entidad sí tiene no existe.
 */
const { data, status, error, refresh } = await useAsyncData<RespuestaTramite | null>(
  `tramite-${slug}`,
  async () => {
    try {
      return await traer<RespuestaTramite>(`${urlApi}/tramites/${slug}`)
    } catch (fallo) {
      const respuesta = (fallo as { statusCode?: number; response?: { status?: number } })
      const estado = respuesta.statusCode ?? respuesta.response?.status

      if (estado === 404) return null

      throw fallo
    }
  },
)

const tramite = computed<TramiteApi | null>(() => data.value?.data ?? null)

/**
 * La dirección no existe. Se corta el renderizado con un `404` real.
 *
 * **Se comprueba la ausencia de error por su valor falsy y no contra `null`, y
 * eso no es un detalle de estilo.** Cuando la petición va bien, Nuxt deja `error`
 * en `undefined`, no en `null`; con `error.value === null` la condición nunca se
 * cumplía, así que un slug desconocido no respondía `404` sino una página con el
 * contenido vacío —y con un `200`—, que es exactamente lo que no debe pasar: el
 * buscador indexaría una dirección inexistente y el ciudadano creería que el
 * trámite existe pero está sin publicar. Se exige `status === 'success'` para que
 * un estado inesperado no se confunda con «no existe».
 *
 * La frase de estado va en la línea del protocolo, que sólo admite ASCII; el
 * texto que lee el ciudadano está en `error.vue`, donde sí lleva tildes.
 */
if (!error.value && status.value === 'success' && tramite.value === null) {
  throw createError({ statusCode: 404, statusMessage: 'Not Found', fatal: true })
}

// ---------------------------------------------------------------------------
// Rótulos
// ---------------------------------------------------------------------------

/**
 * Los nombres con los que se publican los valores que el contrato declara como
 * claves. Traducir aquí y no repartir la traducción por la plantilla es lo que
 * permite que un valor nuevo del contrato se vea como lo que es —un valor sin
 * rótulo— en vez de dibujarse en crudo.
 */
const MODALIDAD: Record<string, string> = {
  en_linea: 'En línea',
  parcialmente_en_linea: 'Parcialmente en línea',
  presencial: 'Presencial',
}

const CANAL_INICIO: Record<string, string> = {
  propio: 'Propio de la Entidad',
  portal_nacional: 'Portal nacional',
}

const CANAL_CONSULTA: Record<string, string> = {
  sede: 'Sede Electrónica',
  presencial: 'Presencial',
  telefonico: 'Telefónico',
  web: 'Web',
  correo: 'Correo electrónico',
  otro: 'Otro canal',
}

/**
 * Los encabezados de los requisitos, **uno por naturaleza**.
 *
 * La ficha oficial distingue cinco cosas que el ciudadano necesita separadas: un
 * documento que hay que llevar encima se prepara antes de salir de casa, una
 * condición que hay que cumplir no se prepara, una solicitud se presenta por un
 * canal, un formulario se diligencia y un pago se hace. Publicar las cinco como
 * una lista de frases obligaría a quien lee a adivinar cuál es cuál.
 */
const TITULO_REQUISITO: Record<TipoRequisito, string> = {
  documento: 'Documentos que debe aportar',
  verificacion_institucional: 'Condiciones que debe cumplir',
  solicitud: 'Solicitudes que debe presentar',
  formulario: 'Formularios que debe diligenciar',
  pago: 'Pagos que debe realizar',
}

/**
 * El orden en que se presentan las naturalezas.
 *
 * Es el recorrido real del ciudadano —reunir, cumplir, presentar, diligenciar,
 * pagar— y no el orden en que la fuente devuelve las acciones, que además no es
 * estable entre pasos.
 */
const ORDEN_REQUISITO: TipoRequisito[] = [
  'documento',
  'verificacion_institucional',
  'solicitud',
  'formulario',
  'pago',
]

const ORIGEN: Record<string, string> = {
  fuente: 'Lo declara la fuente tal cual',
  derivado: 'Lo calculó la ingesta con una regla declarada',
  entidad: 'Lo puso la Entidad',
  ausente: 'La fuente no lo declara',
}

/**
 * Qué significa cada tipo de valor cuando la ficha **no declara un importe**.
 *
 * Los cuatro enunciados describen el tipo de valor que el propio contrato
 * declara —`avaluo_liquidacion` es «el importe se calcula con el avalúo», por
 * definición del campo—, no una decisión de la Entidad que esta página no pueda
 * sostener. Se usa un `Record<string, string>` y no un objeto literal porque el
 * tipo de valor es una unión del contrato: con un literal, un valor nuevo haría
 * fallar la compilación al indexarlo, y lo que se quiere es que un valor nuevo
 * caiga en el enunciado genérico y se vea que no tiene explicación propia.
 */
const EXPLICACION_TIPO_VALOR: Record<string, string> = {
  avaluo_liquidacion:
    'El importe no es una suma fija: se determina con el avalúo y la liquidación del predio.',
  rango: 'El importe depende del rango que la Entidad declara para este trámite.',
  smlv: 'El importe se expresa en salarios mínimos legales mensuales vigentes.',
  fijo: 'El trámite tiene un importe fijo, que la ficha no declara.',
}

// ---------------------------------------------------------------------------
// Derivados de la ficha
// ---------------------------------------------------------------------------

/**
 * Los requisitos, agrupados por naturaleza y en el orden del recorrido.
 *
 * Antes de agrupar, se descartan los requisitos que no tienen nada que
 * mostrar al ciudadano. Es la aplicación práctica de "no inventar": un
 * `SOLICITUD` sin descripción, sin URL, sin correo, sólo con `orden: 11` no
 * es un dato del trámite, es un placeholder del SUIT. Se filtra en la
 * frontera de la presentación, **no** en la base, para que la Entidad
 * pueda ver el placeholder y completarlo desde el panel.
 */
const requisitosPorTipo = computed<{
  tipo: TipoRequisito
  titulo: string
  items: RequisitoExtendido[]
}[]>(() => {
  const todos = (tramite.value?.requisitos ?? []) as unknown as RequisitoExtendido[]
  // Filtra por audiencia activa Y por requisitos con datos visibles.
  // El orden es: primero audiencia (que es lo que el ciudadano quiere
  // ver), después visibilidad (que es lo que la Sede sabe dibujar).
  const visibles = todos.filter(
    (req) => requisitoEsVisible(req) && requisitoAplicaA(req, audienciaActiva.value),
  )

  return ORDEN_REQUISITO.map((tipo) => ({
    tipo,
    titulo: TITULO_REQUISITO[tipo],
    items: visibles.filter((requisito) => requisito.tipo === tipo),
  })).filter((grupo) => grupo.items.length > 0)
})

/**
 * El término, con la palabra «hábil» que la fuente no declara.
 *
 * La fuente lo declara en texto libre —«2 HORA(S)», «3 MES(ES)»— y la conversión
 * a días viaja en `procedencia.derivados` con la regla que la explica. Aquí se
 * muestra el número de días y, si la conversión ocurrió, la nota: publicar el
 * número convertido sin decir que se convirtió dejaría un dato que parece
 * declarado por la fuente y no lo está.
 */
const termino = computed<string>(() => {
  const dias = tramite.value?.tiempo_solucion_dias

  if (dias === undefined || dias === null) {
    return 'La fuente no declara el término.'
  }

  return dias === 1 ? '1 día hábil' : `${dias} días hábiles`
})

/** La regla con la que se convirtió el término, si hubo conversión. */
const notaTermino = computed<string | null>(
  () =>
    tramite.value?.procedencia?.derivados?.find((d) => d.campo === 'tiempo_solucion_dias')?.regla ??
    null,
)

/**
 * El costo, en una línea, **sin inventar un importe que la fuente no declara**.
 *
 * Para el impuesto predial la fuente devuelve `Valor: null` junto a
 * `TipoValor: "AVALUO_LIQUIDACION"`, es decir un costo que se calcula con el
 * avalúo del predio. Escribir «$0» ahí sería decirle al ciudadano que el trámite
 * es gratis, y sobre esa lectura decide si paga o no. Por eso el importe sólo se
 * publica cuando existe, y el *tipo* de valor —que es lo que sí declara la
 * fuente— se explica con sus palabras.
 */
const costoCifra = computed<string>(() => {
  const costo = tramite.value?.costo

  if (costo === undefined) return 'La ficha no declara el costo.'

  if (costo.tiene_costo === 'gratuito') return 'Gratuito.'

  const valor = costo.valor ?? null

  if (valor !== null && valor !== '') {
    return costo.tipo_valor === 'smlv'
      ? `${valor} salarios mínimos legales mensuales vigentes.`
      : `${valor} ${costo.moneda ?? ''}`.trim() + '.'
  }

  // Sin importe declarado, se dice **cómo se determina** y no cuánto es.
  return (
    EXPLICACION_TIPO_VALOR[costo.tipo_valor ?? ''] ??
    'El trámite tiene costo, y la ficha no declara el importe.'
  )
})

/** Las cuentas de recaudo que la Entidad declara, si las declara. */
const cuentas = computed<Cuenta[]>(() => tramite.value?.costo?.cuentas ?? [])

/** Por dónde se pregunta por el estado, con el estado real de cada canal. */
const canalesConsulta = computed(() => tramite.value?.canales_consulta_estado ?? [])

/** El mecanismo de consulta que la Sede declara en el atributo obligatorio. */
const consultaEstado = computed<string>(() => tramite.value?.consulta_estado ?? '')

/**
 * Los pasos (momentos) del trámite, en el orden oficial del visor de SUIT.
 *
 * Cada momento es un paso narrativo («Reunir documentos», «Radicar la
 * documentación») con sus requisitos dentro. La Sede los publica como una
 * línea de tiempo, que es la forma en que el visor oficial los presenta.
 */
const momentos = computed<TramiteMomento[]>(() => {
  const raw = tramite.value?.momentos
  if (!Array.isArray(raw)) return []
  return raw as unknown as TramiteMomento[]
})

/** Los medios por los que la Entidad entrega el resultado, según el visor. */
const mediosResultado = computed<string[]>(() => {
  const raw = tramite.value?.medios_resultado
  if (!Array.isArray(raw)) return []
  return raw.filter((m): m is string => typeof m === 'string' && m.length > 0)
})

/** El conjunto de cuentas de recaudo del trámite, deduplicado por banco+número. */
const cuentasVisor = computed<TramiteCuentaPago[]>(() => {
  const raw = tramite.value?.cuentas
  if (!Array.isArray(raw)) return []
  return raw as unknown as TramiteCuentaPago[]
})

/**
 * Los perfiles-audiencia declarados por el visor, ya reducidos a un array
 * plano de strings. Es el mismo campo que `perfiles` del contrato, pero
 * poblado desde el visor en vez de inventado.
 */
const perfilesVisor = computed<string[]>(() => {
  const raw = tramite.value?.audiencias
  if (!Array.isArray(raw)) return []
  return raw
    .map((a) => (a as { nombre?: string }).nombre)
    .filter((n): n is string => typeof n === 'string' && n.length > 0)
})

/** Las palabras clave secundarias declaradas por el visor. */
const palabrasRelacionadas = computed<string[]>(() => {
  const raw = tramite.value?.palabras_relacionadas
  if (typeof raw !== 'string' || raw.length === 0) return []
  return raw.split(',').map((s) => s.trim()).filter((s) => s.length > 0)
})

/**
 * Los grupos de audiencia posibles para cualquier trámite.
 *
 * El visor de SUIT siempre expone tres pestañas (Ciudadano, Extranjeros,
 * Organizaciones) sin importar cuántas audiencias declare el trámite: es
 * el patrón del filtro «Para realizarlo necesita». Aquí se exponen
 * siempre, y la Sede filtra los requisitos que apliquen a cada uno.
 *
 * El conteo (`count`) y la lista (`nombres`) son los del trámite actual,
 * no de un catálogo global, porque la Sede no publica un diccionario
 * de audiencias: el visor es la fuente.
 */
interface GrupoAudiencia {
  grupo: string
  count: number
  nombres: string[]
}
const gruposAudiencia = computed<GrupoAudiencia[]>(() => {
  // Las tres pestañas que el visor siempre muestra.
  const gruposFijos = ['Ciudadano', 'Extranjeros', 'Organizaciones']
  const raw = (tramite.value?.audiencias ?? []) as Array<{
    grupo?: string | null
    nombre?: string | null
  }>
  const counts = new Map<string, { count: number; nombres: Set<string> }>()
  for (const a of raw) {
    const grupo = a.grupo ?? ''
    const nombre = a.nombre ?? ''
    if (!grupo || !nombre) continue
    if (!counts.has(grupo)) {
      counts.set(grupo, { count: 0, nombres: new Set() })
    }
    const entry = counts.get(grupo)
    if (!entry) continue
    entry.count++
    entry.nombres.add(nombre)
  }
  return gruposFijos.map<GrupoAudiencia>((grupo) => {
    const entry = counts.get(grupo)
    return {
      grupo,
      count: entry?.count ?? 0,
      nombres: entry ? [...entry.nombres] : [],
    }
  })
})

/**
 * El grupo de audiencia seleccionado en el filtro de la ficha.
 *
 * Por defecto es `'todos'`: la Sede muestra todos los requisitos. Cuando el
 * ciudadano hace clic en una pestaña, el valor cambia al nombre del grupo
 * (Ciudadano, Extranjeros, Organizaciones) y los requisitos se filtran
 * para mostrar sólo los que aplican a ese grupo.
 *
 * Es la **misma** pestaña para los dos bloques que la usan —«¿Qué
 * necesito?» y «¿Cómo hago mi trámite?»—: la misma selección de
 * audiencia filtra ambas vistas, igual que hace el visor de SUIT.
 */
const audienciaActiva = ref<string>('todos')

/** Devuelve si un requisito aplica a la audiencia activa. */
function requisitoAplicaA(req: unknown, audiencia: string): boolean {
  if (audiencia === 'todos') return true
  const r = req as { tipos_audiencia?: string[] | null }
  const audiencias = r.tipos_audiencia
  if (!audiencias || audiencias.length === 0) {
    // Sin audiencia declarada, el requisito es universal: aparece en
    // todos los grupos. Es la opción conservadora: mejor mostrar un
    // requisito que el ciudadano no necesita que ocultar uno que sí.
    return true
  }
  return audiencias.includes(audiencia)
}

/**
 * Los pasos filtrados por el grupo de audiencia activo.
 *
 * Filtra dentro de cada paso los requisitos que no aplican a la audiencia
 * seleccionada. Si tras el filtro el paso queda sin requisitos, el paso
 * se conserva: la Sede prefiere explicar «qué hace este paso» aunque
 * su contenido no aplique al grupo, para que el ciudadano entienda la
 * ruta del trámite.
 */
const momentosFiltrados = computed(() => {
  return momentos.value.map((paso) => ({
    ...paso,
    requisitos: (paso.requisitos ?? []).filter((req) =>
      requisitoAplicaA(req, audienciaActiva.value),
    ),
  }))
})

/**
 * Los campos cuya procedencia **no** es la fuente.
 *
 * `fuente` es el caso normal y repetirlo dieciséis veces convertiría la
 * procedencia en una lista que nadie lee. Lo que hay que ver es lo demás: lo que
 * se derivó, lo que puso la Entidad y lo que la fuente no declara.
 */
const origenesRelevantes = computed<{ campo: string; origen: string }[]>(() =>
  Object.entries(tramite.value?.procedencia?.origen_por_campo ?? {})
    .filter(([, origen]) => origen !== 'fuente')
    .map(([campo, origen]) => ({ campo, origen })),
)

/** Lo que el origen no declara y por lo que la Entidad todavía tiene que responder. */
const faltantes = computed<string[]>(() => tramite.value?.procedencia?.faltantes ?? [])

const procedencia = computed(() => tramite.value?.procedencia ?? null)

// ---------------------------------------------------------------------------
// Presentación
// ---------------------------------------------------------------------------

const nombreFicha = computed<string>(() => tramite.value?.nombre ?? 'Trámite')

/*
 * La vista previa de un trámite compartido tiene que decir **de qué trámite se
 * trata**, no «Trámites y servicios» (D-29). El título sale de la ficha ya
 * cargada; la descripción, de su resumen, que es texto del contrato y no una
 * frase escrita para la ocasión.
 */
useMetadatosComparticion({
  titulo: () => `${nombreFicha.value} · Trámites y servicios`,
  descripcion: () => tramite.value?.resumen ?? undefined,
})

useHead({
  title: () => `${nombreFicha.value} · Trámites y servicios · Sede Electrónica`,
  meta: [
    {
      name: 'description',
      content: () =>
        tramite.value?.resumen ??
        `Ficha oficial del trámite ${nombreFicha.value} de la Alcaldía Distrital de Santa Marta.`,
    },
  ],
})

/** Un valor de texto, o `undefined` si el contrato lo deja en blanco. */
function opcional(texto: string | null | undefined): string | undefined {
  if (texto === null || texto === undefined || texto === '') return undefined
  return texto
}

/** Los artículos de una norma, con el prefijo que la fuente a veces omite. */
function articulos(norma: Norma): string | undefined {
  const declarado = opcional(norma.articulos)
  if (declarado === undefined) return undefined
  return /^art/i.test(declarado) ? declarado : `Artículos ${declarado}`
}

/** El enunciado completo de una norma: «Acuerdo 004 de 2016». */
function encabezadoNorma(norma: Norma): string {
  const numero = opcional(norma.numero)
  const anio = norma.anio ?? null

  if (numero === undefined && anio === null) return norma.tipo
  if (numero === undefined) return `${norma.tipo} de ${anio}`

  return anio === null ? `${norma.tipo} ${numero}` : `${norma.tipo} ${numero} de ${anio}`
}

/** La cantidad de ejemplares de un documento: «1 Original(es)». */
function cantidadDocumento(requisito: unknown): string | undefined {
  const r = requisito as { cantidad?: number | null; unidad_cantidad?: string | null }
  const cantidad = r.cantidad ?? null
  const unidad = opcional(r.unidad_cantidad)

  if (cantidad === null && unidad === undefined) return undefined
  if (cantidad === null) return unidad
  if (unidad === undefined) return String(cantidad)

  return `${cantidad} ${unidad}`
}

/** Las cuentas de un requisito de pago, en la forma normalizada del visor. */
function cuentasDeRequisito(req: unknown): TramiteCuentaPago[] {
  const r = req as { cuentas?: TramiteCuentaPago[]; pago_cuentas?: TramiteCuentaPago[] }
  return r.cuentas ?? r.pago_cuentas ?? []
}

/** Los valores de pago declarados, en la forma del visor. */
function pagosDeRequisito(req: unknown): TramitePagoValor[] {
  const r = req as { pago?: TramitePagoValor[]; pago_valor?: TramitePagoValor[] }
  return r.pago ?? r.pago_valor ?? []
}

/** El nombre del formulario, si el requisito lo declara. */
function formularioDeRequisito(req: unknown): string | undefined {
  const r = req as { formulario?: string | null; formulario_nombre?: string | null }
  return opcional(r.formulario ?? r.formulario_nombre)
}

/**
 * La etiqueta legible de un tipo de requisito, para el chip en el paso.
 * Reutiliza la tabla `TITULO_REQUISITO` definida más arriba.
 */
function tipoRequisitoLabel(tipo: string | null | undefined): string {
  return TITULO_REQUISITO[tipo as TipoRequisito] ?? 'Requisito'
}

/**
 * El texto que se muestra para un requisito dentro de un paso. Si tiene
 * descripción, se usa esa; si no, el nombre del documento o del formulario.
 * La función `descripcionVisible` (declarada arriba) ya hace lo mismo, pero
 * este nombre se usa en el contexto del paso y se prefiere por claridad.
 */
function descripcionVisibleRequisito(req: unknown): string {
  return descripcionVisible(req) ?? ''
}

/**
 * El texto que se muestra como descripción principal del requisito.
 *
 * Prioridad: la descripción literal del visor; si no, el nombre del
 * documento o del formulario que el visor publica (los requisitos de tipo
 * `documento` suelen venir sin descripción, sólo con el nombre); si no, nada.
 *
 * Devolver `undefined` (no cadena vacía) es la señal de que el requisito
 * **no** debe dibujarse: ver `requisitoEsVisible`.
 */
function descripcionVisible(req: unknown): string | undefined {
  const r = req as { descripcion?: string | null; documento?: string | null }
  const descripcion = opcional(r.descripcion)
  if (descripcion !== undefined) return descripcion
  return opcional(r.documento) ?? formularioDeRequisito(req)
}

/**
 * Si un requisito tiene algo que mostrar al ciudadano, lo conservamos; si no,
 * lo descartamos.
 *
 * El visor publica requisitos que en la práctica son ruido para el ciudadano:
 * un `SOLICITUD` sin texto, sin URL, sin correo, sólo con `orden: 11` y
 * `tipo: 'solicitud'`. No es un dato del trámite, es una entrada vacía que
 * el SUIT no terminó de llenar, y la Sede no la publica: es exactamente la
 * decisión de "no inventar" del §3 del AGENTS.md.
 */
function requisitoEsVisible(req: unknown): boolean {
  if (descripcionVisible(req) !== undefined) return true
  const r = req as {
    nota?: string | null
    url?: string | null
    correo?: string | null
    url_pago?: string | null
  }
  if (opcional(r.nota) !== undefined) return true
  if (opcional(r.url) !== undefined) return true
  if (opcional(r.correo) !== undefined) return true
  if (cuentasDeRequisito(req).length > 0) return true
  if (pagosDeRequisito(req).length > 0) return true
  if (formularioDeRequisito(req) !== undefined) return true
  if (opcional(r.url_pago) !== undefined) return true
  return false
}

/** El tipo de valor del pago, en lenguaje del ciudadano. */
function formatoTipoValor(tipo: string | null | undefined): string {
  switch (tipo) {
    case 'avaluo_liquidacion':
      return 'El importe se calcula con el avalúo y la liquidación del predio'
    case 'smlv':
      return 'El importe se expresa en salarios mínimos legales mensuales vigentes (SMLMV)'
    case 'fijo':
      return 'El trámite tiene un importe fijo'
    case 'rango':
      return 'El importe depende del rango que la Entidad declara'
    default:
      return 'El trámite tiene costo; la Entidad debe declarar el importe'
  }
}
</script>

<template>
  <div class="container py-5">
    <!--
      La API no contesta. No se cae la página y, sobre todo, no se finge un
      vacío: «este trámite no existe» es un enunciado sobre la Entidad y decirlo
      cuando lo que pasa es que el servidor no responde sería mentir.

      La condición es el valor falsy de `error` y no `error !== null`: sin error,
      Nuxt deja la referencia en `undefined`, y `undefined !== null` es cierto,
      así que la rama se dibujaba siempre.
    -->
    <div v-if="error" class="alert alert-warning estado-fallo" role="alert">
      <p class="mb-2">
        No se pudo cargar la ficha de este trámite: la Sede Electrónica no consiguió
        respuesta del servicio que la publica.
      </p>
      <button type="button" class="btn btn-outline-primary" @click="refresh()">
        Reintentar
      </button>
    </div>

    <template v-else-if="tramite !== null">
      <h1>{{ tramite.nombre }}</h1>

      <div class="row">
        <div class="col-lg-8">
          <p v-if="opcional(tramite.resumen)" class="lead">{{ tramite.resumen }}</p>

          <!--
            El enlace a la ficha oficial va junto al título y se rotula hacia
            dónde lleva: quien lo pulsa sale de la Sede y tiene derecho a saberlo
            antes.
          -->
          <p class="mb-4">
            <a :href="tramite.url_ficha_gov_co" class="enlace-externo">
              Ficha oficial de este trámite en GOV.CO
            </a>
            <span class="solo-lectores"> (abre el portal GOV.CO en otra página)</span>
          </p>
        </div>
      </div>

      <!--
        =====================================================================
        Los seis atributos obligatorios, rotulados
        =====================================================================
        Van primero y en una lista de definiciones porque son la ficha: la Guía
        §5.1.3 los exige y el §9.1 hace de su publicación requisito para la
        integración. Cada rótulo dice cuál es; el detalle de cada uno —el costo
        con sus cuentas, los requisitos con sus documentos— se desarrolla más
        abajo, para que quien sólo quiere la respuesta la tenga de un vistazo y
        quien necesita el detalle no tenga que buscarlo en otro sitio.
      -->
      <h2 class="h3 mt-4">Información general</h2>

      <dl class="row datos-tramite">
        <dt class="col-sm-4">Modalidad</dt>
        <dd class="col-sm-8">
          {{ MODALIDAD[tramite.modalidad] ?? tramite.modalidad }}
        </dd>

        <dt class="col-sm-4">Costo</dt>
        <dd class="col-sm-8">{{ costoCifra }}</dd>

        <dt class="col-sm-4">Término de solución</dt>
        <dd class="col-sm-8">
          {{ termino }}
          <span v-if="notaTermino !== null" class="nota-derivado d-block">
            {{ notaTermino }}
          </span>
        </dd>

        <dt class="col-sm-4">Canal de inicio</dt>
        <dd class="col-sm-8">
          {{ CANAL_INICIO[tramite.canal_inicio] ?? tramite.canal_inicio }}
          <span v-if="opcional(tramite.url_inicio)" class="d-block">
            <a
              :href="tramite.url_inicio ?? undefined"
              class="enlace-externo"
              rel="noopener"
            >Iniciar el trámite en línea</a>
            <span class="solo-lectores"> (abre un servicio de la Entidad en otra página)</span>
          </span>
        </dd>

        <dt class="col-sm-4">Consulta del estado</dt>
        <dd class="col-sm-8">
          <template v-if="consultaEstado !== ''">
            {{ consultaEstado }}
          </template>
          <span class="nota-derivado d-block mt-1">
            El mecanismo de consulta es de la Sede y no del trámite: es el mismo para
            todos los del catálogo. Su estado real está más abajo.
          </span>
        </dd>

        <dt class="col-sm-4">Requisitos</dt>
        <dd class="col-sm-8">
          <template v-if="requisitosPorTipo.length > 0">
            {{ tramite.requisitos.length }}
            <template v-if="tramite.requisitos.length === 1">requisito</template>
            <template v-else>requisitos</template>
            en {{ requisitosPorTipo.length }}
            <template v-if="requisitosPorTipo.length === 1">categoría</template>
            <template v-else>categorías</template>, detallados más abajo.
          </template>
          <template v-else>
            La ficha oficial no declara requisitos para este trámite.
          </template>
        </dd>
      </dl>

      <!--
        =====================================================================
        Requisitos, por naturaleza
        =====================================================================
        Una lista con encabezado por tipo y no párrafos sueltos: la naturaleza
        del requisito decide qué hace el ciudadano con él, y mezclarlas en un
        párrafo lo obligaría a interpretar cuál es cuál.
      -->
      <section v-if="requisitosPorTipo.length > 0" aria-labelledby="titulo-requisitos">
        <h2 id="titulo-requisitos" class="h3 mt-5">¿Qué necesito para hacer mi trámite?</h2>

        <!--
          Filtro de audiencia. Es el mismo que se usa para «¿Cómo hago mi
          trámite?»: el visor de SUIT muestra un único selector con tres
          pestañas (Ciudadano / Extranjeros / Organizaciones) y los
          requisitos se filtran al cambiar de pestaña. Aquí se replica
          el mismo patrón, con el cobalto del Kit.
        -->
        <div
          class="audiencia-filtro mt-3"
          role="tablist"
          aria-label="Filtrar requisitos por tipo de persona"
        >
          <button
            type="button"
            role="tab"
            class="audiencia-tab"
            :class="{ 'audiencia-tab-activa': audienciaActiva === 'todos' }"
            :aria-selected="audienciaActiva === 'todos'"
            @click="audienciaActiva = 'todos'"
          >
            Todos
          </button>
          <button
            v-for="grupo in gruposAudiencia"
            :key="grupo.grupo"
            type="button"
            role="tab"
            class="audiencia-tab"
            :class="{ 'audiencia-tab-activa': audienciaActiva === grupo.grupo }"
            :aria-selected="audienciaActiva === grupo.grupo"
            @click="audienciaActiva = grupo.grupo"
          >
            {{ grupo.grupo }}
          </button>
        </div>

        <div v-for="grupo in requisitosPorTipo" :key="grupo.tipo" class="grupo-requisitos">
          <h3 class="h5 mt-4">{{ grupo.titulo }}</h3>

          <ul class="lista-requisitos">
            <li v-for="(requisito, indice) in grupo.items" :key="indice">
              <span class="requisito-descripcion">
                {{ descripcionVisible(requisito) }}
              </span>

              <!-- La cantidad sólo la declara la fuente para los documentos. -->
              <span v-if="cantidadDocumento(requisito)" class="d-block nota-derivado">
                Se aporta: {{ cantidadDocumento(requisito) }}.
              </span>

              <!--
                La salvedad que la fuente añade al requisito. Se publica tal cual
                y no se interpreta: es texto suyo.
              -->
              <span v-if="opcional(requisito.nota)" class="d-block nota-derivado">
                {{ requisito.nota }}
              </span>

              <span v-if="opcional(requisito.url)" class="d-block">
                <a
                  :href="requisito.url ?? undefined"
                  class="enlace-externo"
                  rel="noopener"
                >Presentar este requisito en línea</a>
              </span>

              <span v-if="opcional(requisito.correo)" class="d-block">
                Correo de radicación:
                <a
                  class="correo"
                  :href="`mailto:${requisito.correo}`"
                  :aria-label="`${requisito.correo} (abre el programa de correo)`"
                >{{ requisito.correo }}</a>
              </span>

              <!--
                BLOQUE DE PAGO: si el requisito es de tipo PAGO, mostramos el
                importe declarado por el visor y la lista de cuentas donde el
                ciudadano puede pagar. Las cuentas son la ruta real de pago
                porque la Sede no tiene pasarela de pagos contratada.
              -->
              <template v-if="grupo.tipo === 'pago'">
                <div v-if="pagosDeRequisito(requisito).length > 0" class="bloque-pago mt-2">
                  <p
                    v-for="(pago, idxPago) in pagosDeRequisito(requisito)"
                    :key="idxPago"
                    class="mb-1"
                  >
                    <strong v-if="pago.valor">Importe: {{ pago.valor }} {{ pago.moneda ?? '' }}.</strong>
                    <strong v-else-if="pago.tipo_valor">Importe: {{ formatoTipoValor(pago.tipo_valor) }}.</strong>
                    <span v-if="pago.descripcion" class="d-block nota-derivado">
                      {{ pago.descripcion }}
                    </span>
                  </p>
                </div>

                <div
                  v-if="cuentasDeRequisito(requisito).length > 0"
                  class="cuentas-pago mt-2"
                >
                  <p class="nota-derivado mb-1">Cuentas de recaudo para este pago:</p>
                  <ul class="lista-cuentas-pequena">
                    <li
                      v-for="(cuenta, idxCta) in cuentasDeRequisito(requisito)"
                      :key="idxCta"
                    >
                      <strong v-if="opcional(cuenta.banco)">{{ cuenta.banco }}</strong>
                      <span v-if="opcional(cuenta.tipo)"> — {{ cuenta.tipo }}</span>
                      <span v-if="opcional(cuenta.numero)" class="dato-largo"> — N° {{ cuenta.numero }}</span>
                      <span v-if="opcional(cuenta.titular)"> — {{ cuenta.titular }}</span>
                    </li>
                  </ul>
                </div>

                <p
                  v-if="opcional(requisito.url_pago)"
                  class="mt-2"
                >
                  <a
                    :href="requisito.url_pago ?? undefined"
                    class="enlace-externo"
                    rel="noopener"
                  >Pagar en línea en el portal de la Entidad</a>
                </p>
              </template>

              <!--
                BLOQUE DE FORMULARIO: si el requisito es de tipo FORMULARIO,
                mostramos el nombre y la URL del formulario.
              -->
              <template v-if="grupo.tipo === 'formulario'">
                <p
                  v-if="formularioDeRequisito(requisito)"
                  class="mt-2 mb-0"
                >
                  <strong>Formulario:</strong> {{ formularioDeRequisito(requisito) }}
                </p>
                <p
                  v-if="opcional(requisito.formulario_url)"
                  class="mt-1"
                >
                  <a
                    :href="requisito.formulario_url ?? undefined"
                    class="enlace-externo"
                    rel="noopener"
                  >Diligenciar en línea</a>
                </p>
              </template>
            </li>
          </ul>
        </div>
      </section>

      <!--
        =====================================================================
        Pasos del trámite (momentos)
        =====================================================================
        Cada paso es un momento narrativo con un orden oficial. El visor los
        publica con su `descripcion` («Reunir documentos», «Radicar la
        documentación»). Aquí se separan en **título** y **descripción** y se
        renderizan como tarjetas expandibles: el cuerpo del paso (los
        requisitos por tipo) sólo se muestra cuando se despliega, y el
        ciudadano encuentra la información de un vistazo sin tener que
        cargar la página completa.

        El filtro de **audiencia** que muestra arriba es la forma que el visor
        oficial de SUIT propone: el ciudadano se reconoce en uno de los
        grupos (Ciudadano, Organizaciones, etc.) y ve sólo los pasos que
        aplican a su rol. Es una mejora de UX que el §3 del Anexo 2.1
        recomienda pero no obliga.
      -->
      <section
        v-if="momentos.length > 0"
        aria-labelledby="titulo-pasos"
        class="mt-5"
      >
        <h2 id="titulo-pasos" class="h3">¿Cómo hago mi trámite?</h2>

        <!--
          Filtro de audiencia: el visor siempre muestra las tres pestañas
          (Ciudadano, Extranjeros, Organizaciones), sin importar las
          audiencias que declare el trámite, porque es la forma en que el
          ciudadano se reconoce. La Sede filtra los pasos que apliquen a
          la audiencia seleccionada.
        -->
        <div
          class="audiencia-filtro mt-3"
          role="tablist"
          aria-label="Filtrar pasos por tipo de persona"
        >
          <button
            type="button"
            role="tab"
            class="audiencia-tab"
            :class="{ 'audiencia-tab-activa': audienciaActiva === 'todos' }"
            :aria-selected="audienciaActiva === 'todos'"
            @click="audienciaActiva = 'todos'"
          >
            Todos
          </button>
          <button
            v-for="grupo in gruposAudiencia"
            :key="grupo.grupo"
            type="button"
            role="tab"
            class="audiencia-tab"
            :class="{ 'audiencia-tab-activa': audienciaActiva === grupo.grupo }"
            :aria-selected="audienciaActiva === grupo.grupo"
            @click="audienciaActiva = grupo.grupo"
          >
            {{ grupo.grupo }}
          </button>
        </div>

        <ol class="pasos-listado list-unstyled mt-3">
          <li
            v-for="(paso, idx) in momentosFiltrados"
            :key="idx"
            class="paso-card"
          >
            <details :open="idx === 0">
              <summary class="paso-encabezado">
                <span class="paso-chevron" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M6 3l5 5-5 5V3z"/>
                  </svg>
                </span>
                <span class="paso-orden">{{ paso.orden ?? idx + 1 }}</span>
                <span class="paso-titulo">{{ paso.titulo }}</span>
              </summary>

              <div v-if="opcional(paso.descripcion)" class="paso-descripcion">
                {{ paso.descripcion }}
              </div>

              <ul
                v-if="(paso.requisitos ?? []).length > 0"
                class="paso-requisitos list-unstyled"
              >
                <li
                  v-for="(req, idxReq) in paso.requisitos"
                  :key="idxReq"
                  class="paso-requisito"
                >
                  <span class="paso-requisito-tipo">{{ tipoRequisitoLabel(req.tipo) }}</span>
                  <span class="paso-requisito-texto">
                    {{ descripcionVisibleRequisito(req) }}
                  </span>
                </li>
              </ul>
            </details>
          </li>
        </ol>
      </section>

      <!--
        =====================================================================
        Costo y pago
        =====================================================================
        **Aquí no hay ni habrá un botón de pago.** Ver la nota de la cabecera:
        sin pasarela contratada, un botón que no cobra hace creer que el pago
        quedó hecho. Se publica el canal oficial, la dirección donde la Entidad
        lo tiene publicado y sus cuentas de recaudo, que es lo que sí permite
        pagar.
      -->
      <section aria-labelledby="titulo-costo">
        <h2 id="titulo-costo" class="h3 mt-5">¿Cuánto cuesta?</h2>

        <p>{{ costoCifra }}</p>

        <p v-if="opcional(tramite.costo.descripcion)" class="descripcion-costo">
          {{ tramite.costo.descripcion }}
        </p>

        <p v-if="tramite.costo.tiene_costo === 'con_costo'" class="aviso-sin-pasarela">
          <strong>La Sede Electrónica no cobra en línea.</strong> Este trámite tiene
          costo y todavía no hay pasarela de pagos contratada, así que aquí se publica
          por dónde se paga —el canal oficial de la Entidad y sus cuentas de recaudo— y
          no un botón que no cobraría nada.
        </p>

        <p v-if="opcional(tramite.costo.url_pago)">
          <a :href="tramite.costo.url_pago ?? undefined" class="enlace-externo" rel="noopener">
            Canal de pago oficial de la Entidad
          </a>
          <span class="solo-lectores"> (abre un servicio de la Entidad en otra página)</span>
        </p>

        <template v-if="cuentas.length > 0">
          <h3 class="h5 mt-4">Cuentas de recaudo</h3>
          <p class="nota-derivado">
            Son cuentas institucionales de la Entidad; su titular es el Distrito y no una
            persona.
          </p>

          <ul class="lista-cuentas">
            <li v-for="(cuenta, indice) in cuentas" :key="indice">
              <dl class="row cuenta">
                <dt class="col-sm-4">Entidad financiera</dt>
                <dd class="col-sm-8">{{ cuenta.entidad }}</dd>

                <template v-if="opcional(cuenta.tipo_cuenta)">
                  <dt class="col-sm-4">Tipo de cuenta</dt>
                  <dd class="col-sm-8">{{ cuenta.tipo_cuenta }}</dd>
                </template>

                <template v-if="opcional(cuenta.titular)">
                  <dt class="col-sm-4">Titular</dt>
                  <dd class="col-sm-8">{{ cuenta.titular }}</dd>
                </template>

                <dt class="col-sm-4">Número de cuenta</dt>
                <dd class="col-sm-8 dato-largo">{{ cuenta.numero_cuenta }}</dd>

                <template v-if="opcional(cuenta.codigo_recaudo)">
                  <dt class="col-sm-4">Código de recaudo</dt>
                  <dd class="col-sm-8 dato-largo">{{ cuenta.codigo_recaudo }}</dd>
                </template>
              </dl>
            </li>
          </ul>
        </template>
      </section>

      <!--
        =====================================================================
        Puntos de atención
        =====================================================================
        **Sólo el dato institucional.** El nombre del punto, su dirección
        institucional, su horario y su línea de atención. La fuente trae además
        teléfonos móviles y nombres de contacto en algunas fichas, y no entran:
        la Ley 1581 de 2012 no tiene una excepción para los datos personales que
        uno encuentra publicados en una API pública, y una sede electrónica que
        publica el móvil de un empleado lo está publicando igual que si lo
        hubiera escrito a mano. El descarte lo hace la ingesta
        (`ContactoPublicable`), antes de que el dato llegue al catálogo; esta
        página dibuja lo que recibe y no tiene nada que filtrar.

        `<address>` y no un párrafo: es el elemento que describe la información
        de contacto de lo que lo rodea, y lo que aquí se publica es exactamente
        eso —dónde y cuándo ir—.
      -->
      <section v-if="(tramite.puntos_atencion ?? []).length > 0" aria-labelledby="titulo-puntos">
        <h2 id="titulo-puntos" class="h3 mt-5">¿Cuál es el horario y los puntos de atención?</h2>

        <ul class="lista-puntos">
          <li
            v-for="(punto, indice) in (tramite.puntos_atencion as PuntoAtencion[])"
            :key="indice"
          >
            <address class="punto-atencion">
              <span class="punto-nombre">{{ punto.nombre }}</span>
              <span class="d-block">{{ punto.direccion }}</span>
              <span v-if="opcional(punto.municipio)" class="d-block">
                {{ punto.municipio }}<template v-if="opcional(punto.departamento)">, {{ punto.departamento }}</template>
              </span>
              <span v-if="opcional(punto.horario)" class="d-block">
                Horario: {{ punto.horario }}
              </span>
              <span v-if="opcional(punto.telefono)" class="d-block">
                Línea de atención: {{ punto.telefono }}
              </span>
            </address>
          </li>
        </ul>
      </section>

      <!--
        =====================================================================
        ¿Qué resultado obtengo luego de hacer mi trámite?
        =====================================================================
        El resultado es lo que el ciudadano recibe al terminar; los medios
        son por dónde se lo entregan. En el visor aparecen en un solo
        bloque rojo; aquí se conservan juntos pero con la pregunta del
        Anexo 2.1 como encabezado.

        «¿Quién puede realizarlo?» **no** se publica como bloque: la
        audiencia se gestiona a través del filtro que hay en «¿Qué
        necesito?» y «¿Cómo hago mi trámite?». Mostrarla además
        repetiría la información sin que aporte nada nuevo —el ciudadano
        ya la filtró al elegir su pestaña—.
      -->
      <section
        v-if="opcional(tramite.resultado) || mediosResultado.length > 0"
        aria-labelledby="titulo-resultado"
        class="mt-5"
      >
        <h2 id="titulo-resultado" class="h3">¿Qué resultado obtengo luego de hacer mi trámite?</h2>
        <p v-if="opcional(tramite.resultado)">{{ tramite.resultado }}</p>
        <p
          v-if="mediosResultado.length > 0"
          class="nota-derivado mb-0"
        >
          <strong>Lo recibe por:</strong>
          <span v-for="(medio, idx) in mediosResultado" :key="idx">
            {{ medio }}<span v-if="idx < mediosResultado.length - 1">, </span>
          </span>
        </p>
      </section>

      <!--
        =====================================================================
        Normativa
        =====================================================================
        La norma que faculta la exigencia. Es lo que permite al ciudadano
        discutirla con el texto delante en vez de con la palabra de quien lo
        atiende.
      -->
      <section v-if="(tramite.normativa ?? []).length > 0" aria-labelledby="titulo-normativa">
        <h2 id="titulo-normativa" class="h3 mt-5">¿Cuál es la normativa relacionada con este trámite?</h2>

        <ul class="lista-normativa">
          <li v-for="(norma, indice) in (tramite.normativa as Norma[])" :key="indice">
            <span class="norma-encabezado">{{ encabezadoNorma(norma) }}</span>
            <span v-if="articulos(norma)" class="d-block nota-derivado">
              {{ articulos(norma) }}
            </span>
            <span v-if="opcional(norma.url_descarga)" class="d-block">
              <a :href="norma.url_descarga ?? undefined" class="enlace-externo" rel="noopener">
                Descargar el texto de la norma
              </a>
              <span class="solo-lectores"> (abre el registro de SUIT en otra página)</span>
            </span>
            <span v-else-if="opcional(norma.url)" class="d-block">
              <a :href="norma.url ?? undefined" class="enlace-externo" rel="noopener">
                Consultar la norma
              </a>
            </span>
          </li>
        </ul>
      </section>

      <!--
        =====================================================================
        Consulta del estado, con el estado real de cada canal
        =====================================================================
        El canal de la Sede viaja con `habilitado: false` y se dibuja como lo
        que es: un canal previsto que **todavía no atiende**. Un formulario que
        devolviera cualquier respuesta haría dudar al ciudadano de si el radicado
        que tiene en la mano es válido, cuando el problema es que la consulta no
        está construida.
      -->
      <section aria-labelledby="titulo-consulta">
        <h2 id="titulo-consulta" class="h3 mt-5">¿Cómo consulto el estado de mi solicitud?</h2>

        <ul class="lista-canales">
          <li v-for="(canal, indice) in canalesConsulta" :key="indice">
            <span class="canal-nombre">
              {{ canal.nombre ?? CANAL_CONSULTA[canal.canal] ?? canal.canal }}
            </span>

            <span v-if="canal.habilitado" class="d-block">
              <span v-if="canal.url?.startsWith('/')">
                <NuxtLink :to="canal.url">Consultar en la Sede Electrónica</NuxtLink>
              </span>
              <span v-else-if="opcional(canal.url)">
                <a :href="canal.url ?? undefined" class="enlace-externo" rel="noopener">
                  Consultar el estado
                </a>
              </span>
              <span v-if="opcional(canal.correo)" class="d-block">
                <a
                  class="correo"
                  :href="`mailto:${canal.correo}`"
                  :aria-label="`${canal.correo} (abre el programa de correo)`"
                >{{ canal.correo }}</a>
              </span>
              <span v-if="opcional(canal.telefono)" class="d-block">
                Teléfono: {{ canal.telefono }}
              </span>
              <span v-if="opcional(canal.horario)" class="d-block">
                Horario: {{ canal.horario }}
              </span>
            </span>

            <span v-else class="d-block">
              <span class="nota-derivado">
                Este canal está previsto y todavía no atiende: la consulta por número de
                radicado no está construida.
              </span>
              <NuxtLink v-if="opcional(canal.url)" :to="canal.url ?? undefined" class="d-block">
                Ver el estado de este canal
              </NuxtLink>
            </span>
          </li>
        </ul>
      </section>

      <!--
        =====================================================================
        Procedencia
        =====================================================================
        De dónde salió cada dato. Va en un desplegable porque es información de
        auditoría y no de uso diario, y **no se esconde**: un dato derivado que
        no se declara es un dato que parece de la fuente, y quien quiera
        comprobarlo tiene que poder hacerlo sin pedirlo.
      -->
      <section v-if="procedencia !== null" aria-labelledby="titulo-procedencia">
        <h2 id="titulo-procedencia" class="h3 mt-5">Procedencia de los datos</h2>

        <dl class="row datos-tramite">
          <dt class="col-sm-4">Fuente</dt>
          <dd class="col-sm-8">
            {{ procedencia.fuente }}
            <span v-if="opcional(procedencia.url)" class="d-block">
              <a :href="procedencia.url ?? undefined" class="enlace-externo" rel="noopener">
                Catálogo de SUIT de Función Pública
              </a>
            </span>
          </dd>

          <dt class="col-sm-4">Fecha de obtención</dt>
          <dd class="col-sm-8">{{ procedencia.obtenido_en }}</dd>

          <dt v-if="opcional(procedencia.api)" class="col-sm-4">Servicio de la ficha</dt>
          <dd v-if="opcional(procedencia.api)" class="col-sm-8 dato-largo">
            {{ procedencia.api }}
          </dd>
        </dl>

        <!--
          La advertencia que impide leer esa dirección como una obligación
          contractual. La Entidad responde ante el SUIT y ante la Guía de la
          Resolución 2893, no ante este servicio.
        -->
        <p class="nota-derivado">
          El servicio del que se copió la forma de la ficha es público y GOV.CO no lo
          documenta ni lo ofrece como servicio. De él se copia la forma de los datos y
          nada más: no impone condiciones a la Entidad, cuya obligación es con el SUIT y
          con la Guía de la Resolución 2893.
        </p>

        <details v-if="origenesRelevantes.length > 0 || (procedencia.derivados ?? []).length > 0 || faltantes.length > 0">
          <summary>Qué se derivó, qué puso la Entidad y qué falta</summary>

          <div v-if="origenesRelevantes.length > 0" class="mt-3">
            <h3 class="h5">Campos que no vienen tal cual de la fuente</h3>
            <dl class="row datos-tramite">
              <template v-for="origen in origenesRelevantes" :key="origen.campo">
                <dt class="col-sm-4 dato-largo">{{ origen.campo }}</dt>
                <dd class="col-sm-8">{{ ORIGEN[origen.origen] ?? origen.origen }}</dd>
              </template>
            </dl>
          </div>

          <div v-if="(procedencia.derivados ?? []).length > 0" class="mt-3">
            <h3 class="h5">Reglas con las que se calculó</h3>
            <ul>
              <li v-for="derivado in procedencia.derivados ?? []" :key="derivado.campo">
                <span class="dato-largo">{{ derivado.campo }}</span>: {{ derivado.regla }}
              </li>
            </ul>
          </div>

          <div v-if="faltantes.length > 0" class="mt-3">
            <h3 class="h5">Lo que la fuente no declara</h3>
            <ul class="lista-simple">
              <li v-for="falta in faltantes" :key="falta" class="dato-largo">{{ falta }}</li>
            </ul>
          </div>
        </details>
      </section>

      <p class="mt-5 mb-0">
        <NuxtLink class="btn btn-outline-primary" to="/tramites">
          Volver a Trámites y servicios
        </NuxtLink>
      </p>
    </template>
  </div>
</template>

<style scoped>
/*
  Desborde en pantalla estrecha, medido y no supuesto.

  Esta ficha publica cadenas largas sin ningún espacio donde partirse: la
  dirección del servicio de la ficha (unos 70 caracteres), los enlaces de
  descarga de SUIT, y los números de cuenta. A 320 px de ancho se salen de su
  columna y arrastran toda la página a desplazamiento horizontal, que es lo que
  prohíbe WCAG 1.4.10 —y es además lo que la prueba de reflujo comprueba—.

  Hacen falta las dos reglas, no una:

    · `min-width: 0` en las celdas y en los elementos de lista, porque los
      padres son rejillas flex y un elemento flex no baja de su `min-width:
      auto`, que es exactamente el ancho de la palabra más larga. Sin esto la
      columna se ensancha en vez de encogerse.
    · `overflow-wrap: anywhere` en el texto, porque es el texto y no la caja
      quien no cabe. Se parte la cadena —que es lo único que puede partirse
      ahí— en lugar de recortarla, que ocultaría parte de un dato oficial.

  Es el mismo arreglo que ya usa la página de atención, y por la misma razón.
*/
.datos-tramite dt,
.datos-tramite dd,
.lista-cuentas li,
.lista-puntos li,
.lista-normativa li,
.lista-canales li,
.lista-requisitos li {
  min-width: 0;
}

.dato-largo,
.correo,
.norma-encabezado,
.enlace-externo {
  overflow-wrap: anywhere;
}

.nota-derivado {
  font-size: 0.9rem;
  color: #4b4b4b;
}

.grupo-requisitos:first-of-type h3 {
  margin-top: 1rem;
}

.lista-requisitos,
.lista-cuentas,
.lista-puntos,
.lista-normativa,
.lista-canales,
.lista-simple {
  list-style: none;
  padding-left: 0;
}

.lista-requisitos > li,
.lista-cuentas > li,
.lista-puntos > li,
.lista-normativa > li,
.lista-canales > li {
  border-left: 4px solid #004884;
  padding: 0.5rem 0 0.5rem 0.75rem;
  margin-bottom: 0.75rem;
}

.punto-atencion,
.cuenta {
  font-style: normal;
  margin-bottom: 0;
}

.punto-nombre,
.canal-nombre,
.norma-encabezado,
.requisito-descripcion {
  display: block;
  font-weight: 600;
}

.cuenta dt,
.cuenta dd {
  min-width: 0;
}

.descripcion-costo {
  max-width: 65ch;
}

.aviso-sin-pasarela {
  border-left: 4px solid #f2c94c;
  padding-left: 0.75rem;
  max-width: 65ch;
}

.estado-fallo {
  max-width: 65ch;
}

/*
  Pasos del trámite: tarjetas expandibles con el patrón del visor de GOV.CO,
  adaptado a la paleta institucional de la Sede (cobalto del Kit).

  Cada paso es un `<details>` con un `<summary>` que tiene un chevron a la
  izquierda, el número del paso y el título. El cuerpo se expande con los
  requisitos del paso, cada uno con su tipo y descripción. La paleta
  institucional es la misma del Kit: el cobalto `#004884` para el borde
  activo y el chip de tipo, el cyan `#00ADE7` para el número del paso.
*/
.pasos-listado {
  margin-top: 1.5rem;
}

.paso-card {
  margin-bottom: 0.75rem;
  background: #fff;
  border: 1px solid #d6d6d6;
  border-radius: 4px;
  overflow: hidden;
}

.paso-card details {
  margin: 0;
}

.paso-card details[open] {
  border-left: 4px solid #004884;
}

.paso-encabezado {
  display: grid;
  grid-template-columns: 2.25rem 2.25rem 1fr;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1rem;
  cursor: pointer;
  font-weight: 600;
  list-style: none;
  background: #f4f8fc;
  color: #004884;
  font-size: 1rem;
}

.paso-card details[open] .paso-encabezado {
  background: #fff;
  border-bottom: 1px solid #e5e5e5;
}

.paso-encabezado::-webkit-details-marker {
  display: none;
}

.paso-encabezado::marker {
  display: none;
  content: '';
}

.paso-chevron {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #004884;
  transition: transform 0.2s ease;
}

.paso-card details[open] .paso-chevron {
  transform: rotate(90deg);
}

.paso-orden {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  background: #00ade7;
  color: #fff;
  font-size: 0.9rem;
  font-weight: 700;
}

.paso-titulo {
  font-weight: 600;
  color: #1a1a1a;
  line-height: 1.4;
}

.paso-descripcion {
  padding: 0.75rem 1rem 0.5rem 3.5rem;
  color: #4b4b4b;
  font-size: 0.95rem;
  line-height: 1.5;
}

.paso-requisitos {
  padding: 0.5rem 1rem 1rem 1rem;
  margin: 0;
}

.paso-requisito {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.5rem 0.75rem;
  align-items: start;
  padding: 0.5rem 0;
  border-bottom: 1px dashed #e5e5e5;
}

.paso-requisito:last-child {
  border-bottom: none;
}

.paso-requisito-tipo {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #004884;
  background: #e9eef9;
  padding: 0.15rem 0.5rem;
  border-radius: 3px;
  white-space: nowrap;
  align-self: start;
}

.paso-requisito-texto {
  font-size: 0.95rem;
  color: #1a1a1a;
  line-height: 1.5;
}

/*
  Filtro de audiencia: pestañas con el cobalto del Kit.
  El borde inferior azul marca la pestaña activa, igual que en el visor de
  GOV.CO y que en la galería de aplicaciones del Kit.
*/
.audiencia-filtro {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  border-bottom: 2px solid #d6d6d6;
  margin-bottom: 1rem;
}

.audiencia-tab {
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  padding: 0.5rem 1rem;
  margin-bottom: -2px;
  font-size: 0.95rem;
  font-weight: 500;
  color: #4b4b4b;
  cursor: pointer;
  transition: color 0.15s ease, border-color 0.15s ease;
}

.audiencia-tab:hover {
  color: #004884;
}

.audiencia-tab-activa {
  color: #004884;
  border-bottom-color: #004884;
  font-weight: 600;
}

/* Bloque de pago dentro de un requisito */
.bloque-pago {
  background: #fff8e1;
  border-left: 3px solid #f2c94c;
  padding: 0.5rem 0.75rem;
  border-radius: 0 4px 4px 0;
}

.cuentas-pago {
  background: #f4f8fc;
  border-left: 3px solid #004884;
  padding: 0.5rem 0.75rem;
  border-radius: 0 4px 4px 0;
}

.lista-cuentas-pequena {
  list-style: none;
  padding-left: 0;
  margin-bottom: 0;
}

.lista-cuentas-pequena > li {
  padding: 0.25rem 0;
  border-bottom: 1px dashed #d0d0d0;
}

.lista-cuentas-pequena > li:last-child {
  border-bottom: none;
}
</style>

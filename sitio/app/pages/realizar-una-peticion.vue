<script setup lang="ts">
/**
 * Formulario del solicitante para presentar una PQRSD.
 *
 * **Qué se toma de dónde.** La estructura sigue la **página 14 del Anexo 2.1 —
 * Guía de diseño gráfico para sedes electrónicas**, de obligatorio cumplimiento
 * por el artículo 14 del Decreto 2106 de 2019: la elección previa entre «A nombre
 * personal» y «Anónima», el aviso de que se recopilan datos personales tratados
 * conforme a la política, la frase de entrada a los datos del solicitante, la
 * leyenda «Los campos en asterisco (*) son obligatorios» y, dentro de los datos,
 * el tipo de persona y el primer nombre con su asterisco.
 *
 * **Este formulario no radica nada, y lo dice dos veces.** El sistema de
 * radicación de la Entidad no está conectado. Un formulario que parece enviar y
 * no envía haría creer al ciudadano que presentó una petición, y con ella
 * empezarían a correr términos que en realidad nunca empezaron: es el peor fallo
 * posible en una sede electrónica. Por eso el aviso está antes del formulario,
 * vuelve a estar en la descripción accesible del botón de envío, y al enviar
 * aparece un mensaje que dice sin rodeos que no se ha registrado ninguna
 * solicitud. Nada de lo escrito se borra, para que nadie vuelva a escribirlo
 * creyendo que el envío lo consumió.
 *
 * **Anónima significa anónima.** Al elegir «Anónima» desaparecen todos los campos
 * que identifican a una persona —tipo de persona, nombres, apellidos, documento,
 * correo y dirección—, no sólo los obligatorios. Un formulario anónimo que sigue
 * pidiendo el nombre se contradice a sí mismo, y el ciudadano que confió en la
 * palabra «anónima» habría entregado su identidad sin saberlo.
 *
 * **No se inventan plazos ni normas.** Aquí sólo aparecen los campos y las
 * leyendas que el anexo fija. El término de respuesta depende del tipo de
 * solicitud y de la materia, y se explicará en su sitio cuando pueda citarse la
 * norma, no aquí como una cifra suelta.
 *
 * **Sobre el catálogo de tipos.** Los seis tipos están también en `pqrsd.vue`,
 * que es la página que los define y desde la que se llega a este formulario. Son
 * dos archivos y esta tarea no puede tocar ningún otro, así que la lista corta de
 * etiquetas está repetida; el módulo compartido es lo que falta.
 */
import type { LocationQueryValue } from 'vue-router'

useHead({
  title: 'Realizar una petición · Sede Electrónica',
  meta: [
    {
      name: 'description',
      content:
        'Formulario para presentar una petición, queja, reclamo, sugerencia, denuncia o felicitación a la Alcaldía Distrital de Santa Marta.',
    },
  ],
})

/* ==========================================================================
   El tipo de solicitud con el que se llega
   ========================================================================== */

/** Los tipos de solicitud, con el mismo `slug` que usa la página de PQRSD. */
const SLUGS_DE_TIPO = [
  'peticion',
  'queja',
  'reclamo',
  'sugerencia',
  'denuncia',
  'felicitacion',
] as const

type SlugDeTipo = (typeof SLUGS_DE_TIPO)[number]

const OPCIONES_DE_TIPO: readonly { valor: SlugDeTipo; etiqueta: string }[] = [
  { valor: 'peticion', etiqueta: 'Petición (derecho de petición)' },
  { valor: 'queja', etiqueta: 'Queja' },
  { valor: 'reclamo', etiqueta: 'Reclamo' },
  { valor: 'sugerencia', etiqueta: 'Sugerencia' },
  { valor: 'denuncia', etiqueta: 'Denuncia' },
  { valor: 'felicitacion', etiqueta: 'Felicitación' },
]

/**
 * Comprueba que lo que llega en la dirección es uno de los tipos conocidos, en
 * lugar de confiar en el parámetro. Un valor inventado en la dirección no debe
 * poder dejar el formulario en un estado que no existe.
 */
function esSlugDeTipo(valor: string): valor is SlugDeTipo {
  return (SLUGS_DE_TIPO as readonly string[]).includes(valor)
}

/**
 * Los parámetros de la dirección pueden venir repetidos —y llegar entonces como
 * arreglo— o no venir. Se toma el primero y se exige que sea texto.
 */
function primerValorDeLaDireccion(
  valor: LocationQueryValue | LocationQueryValue[] | undefined,
): string {
  const primero = Array.isArray(valor) ? valor[0] : valor
  return typeof primero === 'string' ? primero : ''
}

const ruta = useRoute()

const candidatoDeTipo = primerValorDeLaDireccion(ruta.query.tipo)
const tipoPreseleccionado: SlugDeTipo | '' = esSlugDeTipo(candidatoDeTipo) ? candidatoDeTipo : ''

/* ==========================================================================
   Estado del formulario
   ========================================================================== */

type Modalidad = 'personal' | 'anonima'

type TipoDePersona = '' | 'natural' | 'juridica'

interface DatosDelFormulario {
  modalidad: Modalidad
  tipoSolicitud: string
  descripcion: string
  tipoPersona: TipoDePersona
  razonSocial: string
  primerNombre: string
  segundoNombre: string
  primerApellido: string
  segundoApellido: string
  tipoDocumento: string
  numeroDocumento: string
  correo: string
  confirmacionCorreo: string
  telefono: string
  direccion: string
  /** Canal por el que la Entidad debe notificar la respuesta (RF-B1-031). */
  canalRespuesta: string
  /** Dependencia a la que se dirige la solicitud (RF-B1-031). */
  dependencia: string
  autorizacion: boolean
}

const datos = reactive<DatosDelFormulario>({
  modalidad: 'personal',
  tipoSolicitud: tipoPreseleccionado,
  descripcion: '',
  tipoPersona: '',
  razonSocial: '',
  primerNombre: '',
  segundoNombre: '',
  primerApellido: '',
  segundoApellido: '',
  tipoDocumento: '',
  numeroDocumento: '',
  correo: '',
  confirmacionCorreo: '',
  telefono: '',
  direccion: '',
  canalRespuesta: '',
  dependencia: '',
  autorizacion: false,
})

const OPCIONES_DE_PERSONA: readonly { valor: TipoDePersona; etiqueta: string }[] = [
  { valor: 'natural', etiqueta: 'Persona natural' },
  { valor: 'juridica', etiqueta: 'Persona jurídica' },
]

/** Las dos modalidades del anexo: a nombre personal o anónima. */
const MODALIDADES: readonly { valor: Modalidad; etiqueta: string }[] = [
  { valor: 'personal', etiqueta: 'A nombre personal' },
  { valor: 'anonima', etiqueta: 'Anónima' },
]

const OPCIONES_DE_DOCUMENTO: readonly string[] = [
  'Cédula de ciudadanía',
  'Cédula de extranjería',
  'NIT',
  'Pasaporte',
  'Tarjeta de identidad',
]

/** En la modalidad anónima no se pide ningún dato de identificación. */
const esPersonal = computed<boolean>(() => datos.modalidad === 'personal')

/**
 * Una persona jurídica no tiene primer nombre ni apellidos: tiene razón social.
 * Pedirle «Primer nombre» a una empresa es el mismo error que pedirle el nombre a
 * quien acaba de marcar «Anónima».
 */
const esPersonaJuridica = computed<boolean>(
  () => esPersonal.value && datos.tipoPersona === 'juridica',
)

/**
 * Los nombres y apellidos se muestran también mientras no se ha escogido el tipo
 * de persona: el formulario conserva la forma que el anexo muestra —con sus
 * campos de nombre a la vista— en lugar de aparecer vacío y llenarse después.
 */
const mostrarNombres = computed<boolean>(() => esPersonal.value && !esPersonaJuridica.value)

/* ==========================================================================
   Validación
   ========================================================================== */

/** Sólo los campos que se validan; los demás son opcionales o de formato libre. */
type Campo =
  | 'tipoSolicitud'
  | 'descripcion'
  | 'tipoPersona'
  | 'razonSocial'
  | 'primerNombre'
  | 'primerApellido'
  | 'tipoDocumento'
  | 'numeroDocumento'
  | 'correo'
  | 'confirmacionCorreo'
  | 'canalRespuesta'
  | 'autorizacion'

const errores = ref<Partial<Record<Campo, string>>>({})

/**
 * Campos que llevan un texto de ayuda permanente. Se declaran aquí, y no en la
 * plantilla, porque `descritoPor` tiene que saber si ese texto existe para no
 * anunciar un identificador que no está.
 */
const CAMPOS_CON_AYUDA: readonly Campo[] = [
  'tipoSolicitud',
  'descripcion',
  'correo',
  'confirmacionCorreo',
  'canalRespuesta',
  'autorizacion',
]

/** Comprobación de forma, no de existencia: que el correo se pueda responder. */
const FORMA_DE_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

/** Longitud mínima de la descripción, para que no se envíe una sola palabra. */
/**
 * Canales por los que se puede notificar la respuesta.
 *
 * Los cinco salen de RN-04-D05 y RF-04-D05 (Ley 1437/2011, arts. 56, 67 y 69):
 * «correo procesal, SMS, correo certificado, edicto o físico», con preferencia
 * del electrónico cuando hay dirección autorizada. No se inventa ninguno.
 */
const CANALES_DE_RESPUESTA: readonly { valor: string; etiqueta: string }[] = [
  { valor: 'correo', etiqueta: 'Correo electrónico' },
  { valor: 'sms', etiqueta: 'Mensaje de texto (SMS)' },
  { valor: 'certificado', etiqueta: 'Correo certificado' },
  { valor: 'aviso', etiqueta: 'Notificación por aviso o edicto' },
  { valor: 'fisico', etiqueta: 'Dirección física de notificación' },
]

/**
 * Dependencias a las que se puede dirigir la solicitud.
 *
 * **El catálogo no lo puede escribir este proyecto.** Lo dice el propio corpus:
 * «Secretaría Jurídica debe proveer la lista» (`_bd/_extraccion/03-servicios-tramites.md:460`)
 * y la única dependencia documentada como destino por defecto es el *Despacho del
 * Alcalde* (pregunta abierta A-11 del módulo 04). Se declara aquí la que está
 * documentada, y el día que la Entidad entregue su catálogo se amplía esta lista
 * y nada más: el desplegable se construye a partir de ella.
 */
const DEPENDENCIAS: readonly string[] = ['Despacho del Alcalde']

const MINIMO_DE_DESCRIPCION = 10

/**
 * Tope del objeto de la solicitud.
 *
 * **2.000 caracteres, y no 4.000 como decía antes esta página.** Lo fija
 * RF-B1-031 («objeto (≤2.000 car.)») y lo repiten RF-04-D04 y HU-04-D06, que
 * además piden contador y bloqueo al superarlo. El límite anterior era el doble
 * del normativo: un formulario que acepta lo que la norma no admite traslada el
 * problema a quien después tiene que radicarlo.
 */
const MAXIMO_DE_DESCRIPCION = 2000

/** Cuántos caracteres lleva escritos el objeto, para el contador. */
const caracteresDelObjeto = computed<number>(() => datos.descripcion.length)

/* ==========================================================================
   Adjuntos (RF-B1-031, RF-B1-033, RN-B1-010)
   ==========================================================================

   **Sin restricciones técnicas, y es obligatorio que sea así.** RN-B1-010 y
   RF-B1-033 lo dicen con la Constitución detrás: el formulario no puede limitar
   formatos, tamaños ni cantidad, porque el derecho de petición (art. 23 CP) no
   admite que la herramienta decida qué se puede pedir. Por eso el campo no lleva
   `accept`, no comprueba tamaños y no corta la lista —y hay una comprobación en
   `make diseno` que falla si alguien las añade—.

   La contradicción C-01 del módulo 04 sigue abierta: la implementación actual de
   la Alcaldía limita a PDF/JPG/PNG y 10 MB, y eso es un incumplimiento concreto.
   Aquí no se copia.
*/
const refAdjuntos = ref<HTMLInputElement | null>(null)
const adjuntos = ref<File[]>([])

function agregarAdjuntos(evento: Event): void {
  const entrada = evento.target as HTMLInputElement
  adjuntos.value = [...adjuntos.value, ...Array.from(entrada.files ?? [])]
  // Se vacía el campo para que volver a elegir el mismo archivo dispare `change`.
  if (refAdjuntos.value) refAdjuntos.value.value = ''
}

function quitarAdjunto(indice: number): void {
  adjuntos.value = adjuntos.value.filter((_, posicion) => posicion !== indice)
}

/** Peso legible para la lista: informa, nunca bloquea. */
function pesoLegible(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function idAyuda(campo: Campo): string {
  return `ayuda-${campo}`
}

function idError(campo: Campo): string {
  return `error-${campo}`
}

/**
 * Los identificadores que describen un control: su texto de ayuda, que siempre
 * existe, y su mensaje de error, que sólo existe cuando la validación ha fallado.
 *
 * Devuelve `undefined` cuando no hay nada que describir, y entonces Vue no escribe
 * el atributo. Es importante: un `aria-describedby` que apunta a un identificador
 * inexistente es un error de accesibilidad, y además deja al lector de pantalla
 * sin el mensaje justo cuando más falta hace.
 */
function descritoPor(campo: Campo): string | undefined {
  const ids: string[] = []
  if (CAMPOS_CON_AYUDA.includes(campo)) {
    ids.push(idAyuda(campo))
  }
  if (errores.value[campo] !== undefined) {
    ids.push(idError(campo))
  }
  return ids.length > 0 ? ids.join(' ') : undefined
}

/**
 * Marca el control como inválido para la tecnología de asistencia. Se devuelve
 * `'true'`/`'false'` en texto y no un booleano porque un `aria-invalid` ausente y
 * un `aria-invalid="false"` no son lo mismo para quien lo consulta.
 */
function invalido(campo: Campo): 'true' | 'false' {
  return errores.value[campo] !== undefined ? 'true' : 'false'
}

/**
 * Valida y devuelve los fallos. Anuncia el primer problema de cada campo, no el
 * primero y basta: quien rellena el formulario prefiere ver de una vez todo lo
 * que le falta a corregir uno por uno.
 */
function validar(): Partial<Record<Campo, string>> {
  const fallos: Partial<Record<Campo, string>> = {}

  if (datos.tipoSolicitud === '') {
    fallos.tipoSolicitud = 'Escoja el tipo de solicitud.'
  }

  if (datos.descripcion.trim().length < MINIMO_DE_DESCRIPCION) {
    fallos.descripcion = `Escriba su solicitud con al menos ${MINIMO_DE_DESCRIPCION} caracteres.`
  } else if (datos.descripcion.length > MAXIMO_DE_DESCRIPCION) {
    // `maxlength` ya lo impide al escribir; esta comprobación cubre lo que no
    // pasa por el teclado —un programa, un pegado sin soporte del atributo—.
    fallos.descripcion = `El objeto de la solicitud no puede pasar de ${MAXIMO_DE_DESCRIPCION} caracteres.`
  }

  // En la modalidad anónima no se pide ningún dato de identificación, así que no
  // hay nada más que validar: comprobar campos ocultos bloquearía el envío por
  // algo que el ciudadano no tiene delante.
  if (!esPersonal.value) {
    return fallos
  }

  if (datos.tipoPersona === '') {
    fallos.tipoPersona = 'Escoja el tipo de persona.'
  }

  if (esPersonaJuridica.value) {
    if (datos.razonSocial.trim() === '') {
      fallos.razonSocial = 'Escriba la razón social.'
    }
  } else {
    if (datos.primerNombre.trim() === '') {
      fallos.primerNombre = 'Escriba el primer nombre.'
    }
    if (datos.primerApellido.trim() === '') {
      fallos.primerApellido = 'Escriba el primer apellido.'
    }
  }

  if (datos.tipoDocumento === '') {
    fallos.tipoDocumento = 'Escoja el tipo de documento.'
  }

  if (datos.numeroDocumento.trim() === '') {
    fallos.numeroDocumento = 'Escriba el número de documento.'
  }

  if (datos.correo.trim() === '') {
    fallos.correo = 'Escriba un correo electrónico.'
  } else if (!FORMA_DE_CORREO.test(datos.correo.trim())) {
    fallos.correo = 'Escriba un correo con un formato válido, por ejemplo nombre@dominio.gov.co.'
  }

  if (datos.confirmacionCorreo.trim() === '') {
    fallos.confirmacionCorreo = 'Escriba otra vez el correo electrónico.'
  }

  // Sin canal no hay por dónde notificar la respuesta, y notificar es la mitad
  // del derecho de petición: se pide, pero sólo en la modalidad con identidad.
  if (esPersonal.value && datos.canalRespuesta === '') {
    fallos.canalRespuesta = 'Escoja cómo quiere que le notifiquemos la respuesta.'
  } else if (datos.confirmacionCorreo.trim() !== datos.correo.trim()) {
    fallos.confirmacionCorreo = 'Los dos correos electrónicos no coinciden.'
  }

  if (!datos.autorizacion) {
    fallos.autorizacion = 'Debe autorizar el tratamiento de sus datos personales.'
  }

  return fallos
}

/* ==========================================================================
   Envío
   ========================================================================== */

const refFormulario = ref<HTMLFormElement | null>(null)
const refAvisoDeEnvio = ref<HTMLElement | null>(null)

/** Si ya se intentó enviar y el aviso de que no se radica nada está a la vista. */
const intentoDeEnvio = ref(false)

/* ==========================================================================
   Pasos del formulario (RF-B2-077, CAG-20, Sección 3:360)
   ==========================================================================

   RF-B2-077 (Must) pide que un proceso largo se subdivida en **pasos numerados**
   y su criterio de aceptación describe exactamente esto: «Paso 1 de 5» con los
   pasos pendientes identificables. CAG-20 añade la línea de avance y permite
   **saltar los pasos libremente**, que es la opción elegida aquí: bloquear un
   paso sin poder mirarlo obliga a rellenarlo a ciegas.

   El formulario ya tenía tres bloques con sentido propio —solicitud, datos del
   solicitante y autorización—, así que los pasos no se inventan: se hacen
   visibles. Cada paso se valida antes de dejar avanzar, para no enviar a nadie al
   final con campos vacíos detrás.
*/
interface PasoDelFormulario {
  /** Nombre del paso, tal y como lo lee quien lo usa. */
  nombre: string
  /** Campos que se comprueban antes de dejar salir de este paso. */
  campos: readonly Campo[]
}

const PASOS: readonly PasoDelFormulario[] = [
  { nombre: 'Solicitud', campos: ['tipoSolicitud', 'descripcion'] },
  {
    nombre: 'Datos del solicitante',
    campos: [
      'tipoPersona',
      'razonSocial',
      'primerNombre',
      'primerApellido',
      'tipoDocumento',
      'numeroDocumento',
      'correo',
      'confirmacionCorreo',
      'canalRespuesta',
    ],
  },
  { nombre: 'Autorización y envío', campos: ['autorizacion'] },
]

const totalPasos = PASOS.length
const pasoActual = ref(0)

/**
 * Un paso está completo cuando sus campos son válidos, **no** cuando queda a la
 * izquierda del actual. La diferencia importa desde que CAG-20 permite saltar
 * libremente: saltar al último paso no convierte en completados los anteriores, y
 * decir lo contrario sería mentir en la línea de avance.
 */
const validacion = computed<Partial<Record<Campo, string>>>(() => validar())

function pasoCompleto(indice: number): boolean {
  const campos = PASOS[indice]?.campos ?? []
  return campos.every((campo) => !validacion.value[campo])
}
const pasoVigente = computed<PasoDelFormulario | undefined>(() => PASOS[pasoActual.value])
/** Destino del foco al cambiar de paso: sin él, el teclado se queda perdido. */
const refLineaDePasos = ref<HTMLElement | null>(null)

function irAPaso(indice: number): void {
  if (indice < 0 || indice >= totalPasos) return
  pasoActual.value = indice
  void nextTick(() => refLineaDePasos.value?.focus())
}

/** Salta a un paso concreto desde la línea de avance (CAG-20 lo permite). */
function saltarAPaso(indice: number): void {
  irAPaso(indice)
}

/**
 * Avanza sólo si el paso actual está completo. Si no lo está, se muestran los
 * errores y el foco va al primer campo marcado, igual que al enviar: el paso no
 * se cierra «a medias» ni se deja avanzar en silencio.
 */
function avanzar(): void {
  const delPaso = new Set<Campo>(PASOS[pasoActual.value]?.campos ?? [])
  const todos = validar()

  /*
   * Sólo se marcan los fallos **del paso que se está rellenando**. Marcar los de
   * los pasos siguientes —que están ocultos— haría que aparecieran ya en rojo al
   * llegar a ellos, antes de que nadie los haya tocado.
   */
  errores.value = Object.fromEntries(
    Object.entries(todos).filter(([campo]) => delPaso.has(campo as Campo)),
  ) as Partial<Record<Campo, string>>

  const falla = Object.keys(errores.value).length > 0

  if (falla) {
    intentoDeEnvio.value = false
    void nextTick(() => {
      refFormulario.value?.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus()
    })
    return
  }

  irAPaso(pasoActual.value + 1)
}

function retroceder(): void {
  irAPaso(pasoActual.value - 1)
}

/**
 * Al cambiar de modalidad se descartan los errores acumulados: los campos que se
 * ocultan ya no se pueden corregir, y dejar vivo el error de un campo que el
 * ciudadano no tiene delante bloquearía el envío sin decir por qué.
 */
watch(
  () => datos.modalidad,
  () => {
    errores.value = {}
    intentoDeEnvio.value = false
  },
)

/**
 * Enviar no radica nada, y el resultado lo dice.
 *
 * Cuando exista el módulo de radicación, esta función será la que llame a la API
 * y muestre el número de radicado. Hoy no hay a dónde llamar, así que el único
 * resultado honesto es el aviso.
 */
async function enviar(): Promise<void> {
  errores.value = validar()

  if (Object.keys(errores.value).length > 0) {
    intentoDeEnvio.value = false

    /*
      Si el primer fallo está en un paso **anterior** —cosa posible porque CAG-20
      permite saltar libremente— hay que llevar a la persona a ese paso antes de
      enfocar. Los campos marcados están ocultos (`display: none`), el foco no se
      puede poner en un elemento que no se ve, y el botón de enviar parecería no
      hacer nada. Lo cazó la comprobación de la puerta, no el typecheck.
    */
    const pasoConFallo = PASOS.findIndex((paso) =>
      paso.campos.some((campo) => errores.value[campo] !== undefined),
    )
    if (pasoConFallo >= 0 && pasoConFallo !== pasoActual.value) {
      pasoActual.value = pasoConFallo
    }

    await nextTick()
    /*
      El foco va al primer control marcado como inválido. Sin esto, quien navega
      con teclado o con lector de pantalla tendría que recorrer el formulario
      entero para averiguar qué campo falló, y a 320 px eso son varias pantallas.
      Se busca dentro del propio formulario y no con `document` para no salir del
      componente.
    */
    refFormulario.value?.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus()
    return
  }

  intentoDeEnvio.value = true
  await nextTick()
  // El aviso tiene que recibir el foco: es la respuesta al botón que se acaba de
  // pulsar, y sin foco un lector de pantalla lo leería cuando le tocara el turno.
  refAvisoDeEnvio.value?.focus()
}
</script>

<template>
  <div class="container py-5">
    <div class="row">
      <div class="col-lg-10">
        <h1>Realizar una petición</h1>

        <p class="lead">
          Formulario para presentar una petición, una queja, un reclamo, una sugerencia,
          una denuncia o una felicitación a la Alcaldía Distrital de Santa Marta.
        </p>
      </div>
    </div>

    <!--
      El aviso de que este formulario no radica nada. Va antes de los campos, y su
      texto es también la descripción accesible del botón de envío, para que quien
      llegue al botón con el teclado vuelva a oírlo justo antes de pulsarlo.
    -->
    <div id="aviso-radicacion" class="aviso-radicacion" role="status">
      <p class="mb-0">
        <strong>Este formulario todavía no radica solicitudes.</strong>
        Puede recorrerlo para conocerlo, pero no está conectado al sistema de radicación de
        la Entidad: al enviarlo <strong>no se registrará nada</strong> y su solicitud no
        llegará a la Alcaldía. Para presentarla hoy, use los
        <NuxtLink to="/atencion">canales de atención</NuxtLink>. Los seis tipos de solicitud,
        con su definición, están en <NuxtLink to="/pqrsd">PQRSD</NuxtLink>.
      </p>
    </div>

    <form
      ref="refFormulario"
      class="formulario-pqrsd"
      novalidate
      aria-describedby="leyenda-obligatorios"
      @submit.prevent="enviar"
    >
      <!--
        Línea de avance (CAG-20) con el patrón «Paso N de M» que fija
        `Sección 3:360`. Los pasos son botones —se puede saltar a cualquiera— y
        cada uno dice en texto si está completado, es el actual o está pendiente:
        el estado no se transmite sólo con color.
      -->
      <!--
        Línea de avance (CAG-20) con el patrón «Paso N de M» que fija
        `Sección 3:360`. Los pasos son botones —se puede saltar a cualquiera— y el
        estado de cada uno se distingue **sin depender del color**: el completado
        lleva una marca dibujada, el actual va relleno y en negrita, y el pendiente
        queda en contorno. El estado va además en texto para quien usa lector de
        pantalla, oculto a la vista porque dentro de la línea sería ruido.
      -->
      <nav
        ref="refLineaDePasos"
        class="pasos-formulario"
        tabindex="-1"
        :aria-label="`Paso ${pasoActual + 1} de ${totalPasos}: ${pasoVigente?.nombre ?? ''}`"
      >
        <!--
          «Paso N de M: Nombre», con los dos puntos y el espacio: es el patrón
          literal que fija `Sección 3:360` («Paso 1 de 4: Datos del solicitante»),
          y la puerta de diseño lo comprueba tal cual.
        -->
        <p class="pasos-encabezado">
          <span class="pasos-cuenta">Paso {{ pasoActual + 1 }} de {{ totalPasos }}: </span>
          <strong class="pasos-titulo">{{ pasoVigente?.nombre }}</strong>
        </p>

        <ol class="pasos-lista">
          <li v-for="(paso, indice) in PASOS" :key="paso.nombre" class="pasos-item">
            <button
              type="button"
              class="paso"
              :class="{
                'paso-actual': indice === pasoActual,
                'paso-hecho': indice !== pasoActual && pasoCompleto(indice),
              }"
              :aria-current="indice === pasoActual ? 'step' : undefined"
              @click="saltarAPaso(indice)"
            >
              <span class="paso-marca" aria-hidden="true">
                <svg
                  v-if="indice !== pasoActual && pasoCompleto(indice)"
                  class="paso-marca-icono"
                  viewBox="0 0 20 20"
                  focusable="false"
                >
                  <path
                    d="M4.6 10.4l3.5 3.5 7.3-7.8"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
                <template v-else>{{ indice + 1 }}</template>
              </span>
              <span class="paso-nombre">{{ paso.nombre }}</span>
              <span class="paso-estado solo-lectores">
                {{
                  indice === pasoActual
                    ? 'actual'
                    : pasoCompleto(indice)
                      ? 'completado'
                      : 'pendiente'
                }}
              </span>
            </button>
          </li>
        </ol>
      </nav>

      <div v-show="pasoActual === 0" class="paso-contenido">
        <h2 class="h3 mt-4">Solicitud</h2>

      <!--
        CAG-16: la leyenda que explica el asterisco, **antes** del primer campo.
        Sin ella, el asterisco es un símbolo que cada quien interpreta como puede.
        Los asteriscos van con `aria-hidden` porque quien usa lector de pantalla
        ya oye «obligatorio»: el campo lleva el atributo `required`, así que el
        símbolo es para quien ve, y repetirlo sería ruido.
      -->
      <p id="leyenda-obligatorios" class="leyenda-obligatorios">
        Los campos marcados con <span class="asterisco" aria-hidden="true">*</span> son obligatorios.
      </p>

      <div class="row">
        <div class="col-lg-8">
          <div class="mb-4">
            <label class="form-label" for="tipoSolicitud">
              Tipo de solicitud <span class="asterisco" aria-hidden="true">*</span>
            </label>

            <select
              id="tipoSolicitud"
                autocomplete="off"
              v-model="datos.tipoSolicitud"
              class="form-select"
              required
              :aria-invalid="invalido('tipoSolicitud')"
              :aria-describedby="descritoPor('tipoSolicitud')"
            >
              <option value="">Escoger</option>
              <option
                v-for="opcion in OPCIONES_DE_TIPO"
                :key="opcion.valor"
                :value="opcion.valor"
              >
                {{ opcion.etiqueta }}
              </option>
            </select>

            <p :id="idAyuda('tipoSolicitud')" class="texto-ayuda">
              <template v-if="tipoPreseleccionado !== ''">
                Viene preseleccionado desde el botón que pulsó en la página de PQRSD.
                Puede cambiarlo.
              </template>
              <template v-else>Escoja el tipo que corresponda a su caso.</template>
            </p>

            <p
              v-if="errores.tipoSolicitud"
              :id="idError('tipoSolicitud')"
              class="error-campo"
              role="alert"
            >
              {{ errores.tipoSolicitud }}
            </p>
          </div>

          <div class="mb-4">
            <label class="form-label" for="descripcion">
              Descripción de la solicitud <span class="asterisco" aria-hidden="true">*</span>
            </label>

            <textarea
              id="descripcion"
                autocomplete="off"
              v-model="datos.descripcion"
              class="form-control"
              rows="6"
              :maxlength="MAXIMO_DE_DESCRIPCION"
              required
              :aria-invalid="invalido('descripcion')"
              :aria-describedby="descritoPor('descripcion')"
            ></textarea>

            <div class="objeto-ayuda">
              <p :id="idAyuda('descripcion')" class="texto-ayuda">
                Escriba lo que solicita con el detalle que necesite para que pueda
                entenderse y responderse.
              </p>
              <!--
                Contador en vivo (HU-04-D06). Se anuncia con `aria-live` sólo al
                acercarse al tope, para no interrumpir a cada tecla; el número se
                lee siempre que se consulte el campo, porque está descrito por él.
              -->
              <p
                class="objeto-contador"
                :class="{ 'objeto-contador-lleno': caracteresDelObjeto > MAXIMO_DE_DESCRIPCION * 0.9 }"
                aria-hidden="true"
              >
                {{ caracteresDelObjeto.toLocaleString('es-CO') }} /
                {{ MAXIMO_DE_DESCRIPCION.toLocaleString('es-CO') }}
              </p>
            </div>

            <!-- ================= Documentos que acompañan la solicitud ================= -->
            <!--
              **El campo no lleva `accept`, ni tope de tamaño, ni límite de cantidad.**
              No es un olvido: RN-B1-010 y RF-B1-033 prohíben que el formulario ponga
              restricciones técnicas a la radicación (art. 23 CP), y `make diseno`
              comprueba que siguen sin estar.
            -->
            <div class="mb-4 mt-4">
              <label class="form-label" for="adjuntos">
                Documentos que acompañan la solicitud
              </label>

              <input
                id="adjuntos"
                ref="refAdjuntos"
                type="file"
                multiple
                class="form-control"
                aria-describedby="ayuda-adjuntos"
                @change="agregarAdjuntos"
              />

              <p id="ayuda-adjuntos" class="texto-ayuda">
                Puede adjuntar los archivos que necesite, en cualquier formato y sin
                límite de cantidad: el derecho de petición no admite restricciones
                técnicas. El único límite es el del servidor que los reciba, que no
                rechaza por formato ni por nombre.
              </p>
              <p class="texto-ayuda">
                Mientras este formulario no radique, los archivos
                <strong>no salen de su equipo</strong>: se quedan aquí y desaparecen al
                recargar la página.
              </p>

              <ul v-if="adjuntos.length > 0" class="lista-adjuntos">
                <li v-for="(archivo, indice) in adjuntos" :key="`${archivo.name}-${indice}`">
                  <span class="adjunto-nombre">{{ archivo.name }}</span>
                  <span class="adjunto-peso">{{ pesoLegible(archivo.size) }}</span>
                  <button
                    type="button"
                    class="adjunto-quitar"
                    :aria-label="`Quitar el archivo ${archivo.name}`"
                    @click="quitarAdjunto(indice)"
                  >
                    Quitar
                  </button>
                </li>
              </ul>
            </div>

            <p
              v-if="errores.descripcion"
              :id="idError('descripcion')"
              class="error-campo"
              role="alert"
            >
              {{ errores.descripcion }}
            </p>
          </div>
        </div>
      </div>

      <!-- ================= Modalidad ================= -->
      <!--
        La «elección previa» del anexo. El grupo va en un `fieldset` con su
        `legend` —que hace además de título visible de la sección— porque un
        conjunto de botones de opción sin grupo no tiene nombre accesible, y el
        lector de pantalla anunciaría las dos opciones sin decir a qué pregunta
        responden.
      -->
      <fieldset class="mb-4">
        <legend class="h3">¿Cómo presenta la solicitud?</legend>

        <div v-for="opcion in MODALIDADES" :key="opcion.valor" class="form-check">
          <input
            :id="`modalidad-${opcion.valor}`"
            v-model="datos.modalidad"
            class="form-check-input"
            type="radio"
            name="modalidad"
            :value="opcion.valor"
          />
          <label class="form-check-label" :for="`modalidad-${opcion.valor}`">
            {{ opcion.etiqueta }}
          </label>
        </div>
      </fieldset>

      <!--
        Aviso de tratamiento de datos del anexo, con su enlace a la política. Va
        aquí, antes de los campos y muy antes del botón de envío: quien tiene que
        decidir si entrega sus datos necesita saber qué se hará con ellos antes de
        escribirlos, no después.
      -->
      <div class="aviso-datos">
        <p class="mb-0">
          Se recopilan datos personales básicos de identificación que son tratados
          conforme con la
          <NuxtLink to="/politicas/proteccion-y-tratamiento-de-datos-personales">
            Política de Datos Personales y Privacidad</NuxtLink>, que puede consultar en el enlace de políticas de este sitio. La
          respuesta se envía directamente a su correo electrónico o dirección física,
          según corresponda.
        </p>
        <div v-if="!esPersonal" class="mt-3">
          <p class="mb-2">
            En la modalidad <strong>anónima</strong> no se recopila ninguno de esos datos.
          </p>

          <!--
            RF-B1-032 pide un aviso «sobre garantías y limitaciones del anonimato
            (georreferenciación, IP, metadata, navegador privado) y limitación de
            respuesta». Es lo que sigue, y se dice entero: un anonimato prometido a
            medias es peor que ninguno, porque quien lo cree escribe lo que no
            escribiría.
          -->
          <p class="mb-2">
            <strong>Qué protege el anonimato y qué no.</strong> La Entidad no le pedirá
            nombre, documento ni correo, y tramitará la solicitud sin ellos; tampoco
            solicita su ubicación. Ahora bien, el anonimato no es absoluto: la conexión
            puede dejar rastro de la <strong>dirección IP</strong>, de la fecha y la hora,
            del <strong>navegador</strong> y de los <strong>metadatos</strong> de los
            archivos que adjunte. Si necesita un anonimato mayor, use una conexión que no
            lo identifique y el modo privado del navegador.
          </p>

          <p class="mb-0">
            Y una consecuencia práctica: <strong>sin datos de contacto no hay forma de
            responderle personalmente</strong>. Podrá seguir el estado con el número de
            radicado, pero la respuesta no se le podrá notificar.
          </p>
        </div>
      </div>

        <p class="pasos-navegacion">
          <button type="button" class="btn-govco fill-btn-govco" @click="avanzar">
            Continuar a «Datos del solicitante»
          </button>
        </p>
      </div>

      <div v-show="pasoActual === 1" class="paso-contenido">
        <h2 class="h3 mt-4">Datos del solicitante</h2>

      <div class="row">
        <div class="col-lg-10">
          <p>
            A continuación completa tus datos para darte respuesta a tu solicitud
          </p>

          <!--
            La leyenda de obligatorios está al principio del formulario, no aquí:
            los dos primeros campos ya son obligatorios y quien rellenaba el
            formulario se encontraba el asterisco antes que su explicación
            (CAG-16). Aquí se recuerda sólo lo que aporta algo nuevo: que lo no
            marcado es opcional.
          -->
          <p class="leyenda-obligatorios">Los campos sin asterisco son opcionales.</p>
        </div>
      </div>

      <div v-if="esPersonal" class="row">
        <div class="col-lg-8">
          <!-- Tipo de persona -->
          <div class="mb-4">
            <label class="form-label" for="tipoPersona">
              Tipo de persona <span class="asterisco" aria-hidden="true">*</span>
            </label>

            <select
              id="tipoPersona"
                autocomplete="off"
              v-model="datos.tipoPersona"
              class="form-select"
              required
              :aria-invalid="invalido('tipoPersona')"
              :aria-describedby="descritoPor('tipoPersona')"
            >
              <option value="">Escoger</option>
              <option
                v-for="opcion in OPCIONES_DE_PERSONA"
                :key="opcion.valor"
                :value="opcion.valor"
              >
                {{ opcion.etiqueta }}
              </option>
            </select>

            <p
              v-if="errores.tipoPersona"
              :id="idError('tipoPersona')"
              class="error-campo"
              role="alert"
            >
              {{ errores.tipoPersona }}
            </p>
          </div>

          <!--
            Razón social, sólo para persona jurídica. Aparece en lugar de los
            nombres y apellidos, no además de ellos.
          -->
          <div v-if="esPersonaJuridica" class="mb-4">
            <label class="form-label" for="razonSocial">
              Razón social <span class="asterisco" aria-hidden="true">*</span>
            </label>

            <input
              id="razonSocial"
              v-model="datos.razonSocial"
              class="form-control"
              type="text"
              autocomplete="organization"
              required
              :aria-invalid="invalido('razonSocial')"
              :aria-describedby="descritoPor('razonSocial')"
            />

            <p
              v-if="errores.razonSocial"
              :id="idError('razonSocial')"
              class="error-campo"
              role="alert"
            >
              {{ errores.razonSocial }}
            </p>
          </div>

          <div v-if="mostrarNombres" class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="primerNombre">
                Primer nombre <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <input
                id="primerNombre"
                v-model="datos.primerNombre"
                class="form-control"
                type="text"
                autocomplete="given-name"
                required
                :aria-invalid="invalido('primerNombre')"
                :aria-describedby="descritoPor('primerNombre')"
              />

              <p
                v-if="errores.primerNombre"
                :id="idError('primerNombre')"
                class="error-campo"
                role="alert"
              >
                {{ errores.primerNombre }}
              </p>
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="segundoNombre">Segundo nombre</label>

              <input
                id="segundoNombre"
                v-model="datos.segundoNombre"
                class="form-control"
                type="text"
                autocomplete="additional-name"
              />
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="primerApellido">
                Primer apellido <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <input
                id="primerApellido"
                v-model="datos.primerApellido"
                class="form-control"
                type="text"
                autocomplete="family-name"
                required
                :aria-invalid="invalido('primerApellido')"
                :aria-describedby="descritoPor('primerApellido')"
              />

              <p
                v-if="errores.primerApellido"
                :id="idError('primerApellido')"
                class="error-campo"
                role="alert"
              >
                {{ errores.primerApellido }}
              </p>
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="segundoApellido">Segundo apellido</label>

              <input
                id="segundoApellido"
                v-model="datos.segundoApellido"
                class="form-control"
                type="text"
                autocomplete="family-name"
              />
            </div>
          </div>

          <!-- Documento de identificación -->
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="tipoDocumento">
                Tipo de documento <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <select
                id="tipoDocumento"
                  autocomplete="off"
                v-model="datos.tipoDocumento"
                class="form-select"
                required
                :aria-invalid="invalido('tipoDocumento')"
                :aria-describedby="descritoPor('tipoDocumento')"
              >
                <option value="">Escoger</option>
                <option
                  v-for="opcion in OPCIONES_DE_DOCUMENTO"
                  :key="opcion"
                  :value="opcion"
                >
                  {{ opcion }}
                </option>
              </select>

              <p
                v-if="errores.tipoDocumento"
                :id="idError('tipoDocumento')"
                class="error-campo"
                role="alert"
              >
                {{ errores.tipoDocumento }}
              </p>
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="numeroDocumento">
                Número de documento <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <input
                id="numeroDocumento"
                  autocomplete="off"
                v-model="datos.numeroDocumento"
                class="form-control"
                type="text"
                inputmode="numeric"
                :aria-invalid="invalido('numeroDocumento')"
                :aria-describedby="descritoPor('numeroDocumento')"
              />

              <p
                v-if="errores.numeroDocumento"
                :id="idError('numeroDocumento')"
                class="error-campo"
                role="alert"
              >
                {{ errores.numeroDocumento }}
              </p>
            </div>
          </div>

          <!-- Datos de contacto -->
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="correo">
                Correo electrónico <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <input
                id="correo"
                v-model="datos.correo"
                class="form-control"
                type="email"
                autocomplete="email"
                required
                :aria-invalid="invalido('correo')"
                :aria-describedby="descritoPor('correo')"
              />

              <p :id="idAyuda('correo')" class="texto-ayuda">
                A esta dirección se enviará la respuesta.
              </p>

              <p v-if="errores.correo" :id="idError('correo')" class="error-campo" role="alert">
                {{ errores.correo }}
              </p>
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="confirmacionCorreo">
                Confirmación del correo electrónico
                <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <input
                id="confirmacionCorreo"
                v-model="datos.confirmacionCorreo"
                class="form-control"
                type="email"
                autocomplete="email"
                required
                :aria-invalid="invalido('confirmacionCorreo')"
                :aria-describedby="descritoPor('confirmacionCorreo')"
              />

              <p :id="idAyuda('confirmacionCorreo')" class="texto-ayuda">
                Escríbalo una segunda vez: la respuesta se envía a este correo y un
                error de digitación la dejaría sin llegar.
              </p>

              <p
                v-if="errores.confirmacionCorreo"
                :id="idError('confirmacionCorreo')"
                class="error-campo"
                role="alert"
              >
                {{ errores.confirmacionCorreo }}
              </p>
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="telefono">Teléfono o celular</label>

              <input
                id="telefono"
                v-model="datos.telefono"
                class="form-control"
                type="tel"
                autocomplete="tel"
              />
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label" for="direccion">Dirección</label>

              <input
                id="direccion"
                v-model="datos.direccion"
                class="form-control"
                type="text"
                autocomplete="street-address"
              />
            </div>

            <!-- ================= Canal de respuesta ================= -->
            <div class="mb-4">
              <label class="form-label" for="canalRespuesta">
                ¿Cómo quiere que le notifiquemos la respuesta?
                <span class="asterisco" aria-hidden="true">*</span>
              </label>

              <select
                id="canalRespuesta"
                v-model="datos.canalRespuesta"
                class="form-select"
                autocomplete="off"
                required
                :aria-invalid="invalido('canalRespuesta')"
                :aria-describedby="descritoPor('canalRespuesta')"
              >
                <option value="">Escoger</option>
                <option
                  v-for="canal in CANALES_DE_RESPUESTA"
                  :key="canal.valor"
                  :value="canal.valor"
                >
                  {{ canal.etiqueta }}
                </option>
              </select>

              <p :id="idAyuda('canalRespuesta')" class="texto-ayuda">
                La notificación electrónica tiene preferencia cuando hay una dirección
                autorizada; si no, se usa el canal que escoja aquí.
              </p>

              <p
                v-if="errores.canalRespuesta"
                :id="idError('canalRespuesta')"
                class="error-campo"
                role="alert"
              >
                {{ errores.canalRespuesta }}
              </p>
            </div>

            <!-- ================= Dependencia destinataria ================= -->
            <div class="mb-4">
              <label class="form-label" for="dependencia">
                Dependencia a la que se dirige
              </label>

              <select
                id="dependencia"
                v-model="datos.dependencia"
                class="form-select"
                autocomplete="off"
              >
                <option value="">Que la Entidad la asigne</option>
                <option v-for="dependencia in DEPENDENCIAS" :key="dependencia" :value="dependencia">
                  {{ dependencia }}
                </option>
              </select>

              <!--
                Se dice lo que falta en lugar de inventar un organigrama. El catálogo
                de dependencias lo tiene que entregar la Entidad (Secretaría Jurídica
                «debe proveer la lista», `_bd/_extraccion/03-servicios-tramites.md:460`);
                hoy sólo está documentado el Despacho del Alcalde como destino por
                defecto (A-11 del módulo 04).
              -->
              <p class="texto-ayuda">
                Puede dejarlo en blanco y la Entidad la dirige a quien corresponda. El
                catálogo completo de dependencias está pendiente de publicación, así que
                hoy sólo se ofrece el despacho del alcalde; el formulario no inventa el
                resto del organigrama.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!--
        Explicación de la modalidad anónima. Se dice lo que se está pidiendo y lo
        que se está dejando de pedir, y también lo que eso implica, sin adornos:
        sin datos de contacto no hay por dónde enviar una respuesta.
      -->
      <div v-else class="row">
        <div class="col-lg-8">
          <p>
            Ha elegido presentar la solicitud de forma <strong>anónima</strong>. Por eso no
            se piden aquí su nombre, su documento, su correo ni su dirección: son datos de
            identificación, y pedirlos contradiría la elección que acaba de hacer.
          </p>

          <p class="mb-4">
            Tenga en cuenta que, sin ningún dato de contacto, la Entidad no tiene por dónde
            enviarle una respuesta personal.
          </p>
        </div>
      </div>

        <p class="pasos-navegacion">
          <button type="button" class="btn-govco outline-btn-govco" @click="retroceder">
            Volver a «Solicitud»
          </button>
          <button type="button" class="btn-govco fill-btn-govco" @click="avanzar">
            Continuar a «Autorización y envío»
          </button>
        </p>
      </div>

      <div v-show="pasoActual === 2" class="paso-contenido">
        <h2 class="h3 mt-4">Autorización y envío</h2>

      <!-- Autorización del tratamiento de datos -->
      <div v-if="esPersonal" class="mb-4">
        <div class="form-check">
          <input
            id="autorizacion"
            autocomplete="off"
            v-model="datos.autorizacion"
            class="form-check-input"
            type="checkbox"
            required
            :aria-invalid="invalido('autorizacion')"
            :aria-describedby="descritoPor('autorizacion')"
          />
          <label class="form-check-label" for="autorizacion">
            Autorizo a la Alcaldía Distrital de Santa Marta el tratamiento de mis datos
            personales conforme a su política de protección y tratamiento de datos
            personales.
            <span class="asterisco" aria-hidden="true">*</span>
          </label>
        </div>

        <p :id="idAyuda('autorizacion')" class="texto-ayuda">
          El texto de la política está en
          <NuxtLink to="/politicas/proteccion-y-tratamiento-de-datos-personales">
            Protección y tratamiento de datos personales</NuxtLink>.
        </p>

        <p
          v-if="errores.autorizacion"
          :id="idError('autorizacion')"
          class="error-campo"
          role="alert"
        >
          {{ errores.autorizacion }}
        </p>
      </div>

      <!--
        Recordatorio pegado al botón. Es el tercer sitio donde se dice que aquí no
        se radica nada, y no es de más: es el único punto donde el ciudadano está a
        un clic de creer que ya presentó su solicitud.
      -->
      <p class="recordatorio-envio">
        Recuerde: al pulsar este botón <strong>no se radica ninguna solicitud</strong>.
      </p>

      <p class="pasos-navegacion">
        <button type="button" class="btn-govco outline-btn-govco" @click="retroceder">
          Volver a «Datos del solicitante»
        </button>
        <button
          class="btn-govco fill-btn-govco boton-enviar"
          type="submit"
          aria-describedby="aviso-radicacion"
        >
          Enviar la solicitud
        </button>
      </p>
      </div>
    </form>

    <!--
      Respuesta al envío. `role="alert"` para que se anuncie sin esperar a que el
      lector de pantalla llegue a él, y `tabindex="-1"` para poder recibir el foco
      desde el propio formulario.
    -->
    <div
      v-if="intentoDeEnvio"
      ref="refAvisoDeEnvio"
      class="aviso-no-radicado"
      role="alert"
      tabindex="-1"
    >
      <h2 class="h4">Su solicitud no ha sido enviada ni registrada</h2>

      <p>
        La radicación en línea todavía no está disponible: este formulario no está
        conectado al sistema de radicación de la Entidad, así que
        <strong>no se ha registrado ninguna solicitud</strong> y lo que escribió no ha
        llegado a la Alcaldía.
      </p>

      <p>
        Lo que escribió sigue en pantalla y no se ha borrado, pero tampoco se ha guardado
        en ninguna parte: si cierra o recarga esta página, se perderá.
      </p>

      <p class="mb-0">
        Para presentar su solicitud hoy, use los
        <NuxtLink to="/atencion">canales de atención</NuxtLink> de la Entidad.
      </p>
    </div>

    <div class="row">
      <div class="col-lg-10">
        <p class="mt-5 mb-0">
          <NuxtLink class="btn btn-outline-primary" to="/pqrsd">
            Volver a los tipos de solicitud
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
  Los enlaces de esta página NO se pintan aquí.

  Bootstrap —que el sitio carga porque el Kit lo exige— trae su propio azul de
  enlace, `#0d6efd`, y sobre el amarillo de los avisos bajaba a 3,6:1 y sobre el
  azul claro de la política a 4,0:1: dos violaciones serias de contraste. El color
  correcto lo fija la hoja compartida del sitio, `assets/css/sitio.css`, que
  reapunta los enlaces al cobalto del Kit y deja fuera a los botones. Se deja
  dicho aquí para que nadie vuelva a añadir una regla local que lo duplique.

  El botón de envío es un botón relleno del Kit: etiqueta blanca sobre cobalto.
  Cualquier regla de enlace que no excluya `[class*="btn-"]` lo repinta del color
  del fondo y la etiqueta desaparece. Es un fallo que el corrector automático no
  ve, porque el texto del botón es un elemento anónimo de una caja flexible; se
  comprueba midiendo el color calculado en cada verificación.
*/

/*
  Aviso de radicación. Mismos tokens y mismo criterio de contraste que el aviso de
  `SeccionEnPreparacion`: el amarillo institucional del Kit (`--govcolor-vis-vis`)
  con el Matterhorn para el texto da 7,6:1, por encima del 4,5:1 que exige
  WCAG 2.1 AA.

  **Sin filete lateral.** Antes llevaba una barra de 6 px a la izquierda, que es el
  recurso que hace que un aviso parezca una plantilla: el amarillo entero y un
  borde completo de 1 px —Matterhorn al 35 %, que es un token del Kit y no un color
  nuevo— dicen lo mismo sin el tic. Lo que separa este aviso del informativo es el
  amarillo, que está reservado para lo que impide terminar.
*/
.aviso-radicacion {
  margin-top: 1.5rem;
  margin-bottom: 2rem;
  padding: 1rem 1.25rem;
  border: 1px solid rgba(76, 76, 76, 0.35);
  border-radius: 0.25rem;
  background-color: var(--govcolor-vis-vis, #fee697);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  Aviso de tratamiento de datos. El azul claro es el token `--govcolor-solitude`
  del Kit, que con el Matterhorn para el texto da 7,2:1: es un aviso informativo
  y no debe competir con el amarillo, que está reservado para lo que impide
  terminar. El borde completo va en el cobalto de la Entidad al 25 %, del mismo
  token, en lugar del filete lateral que llevaba.
*/
.aviso-datos {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border: 1px solid rgba(9, 67, 181, 0.25);
  border-radius: 0.25rem;
  background-color: var(--govcolor-solitude, #e5ecf8);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  Respuesta al envío. Va en rojo institucional y con el texto en el Matterhorn
  oscuro en lugar del rojo claro de Bootstrap, que sobre blanco se queda en
  4,5:1 justos y no admite ninguna variación; el rojo del Kit
  (`--govcolor-red`) da 7,7:1.
*/
.aviso-no-radicado {
  margin-top: 2rem;
  padding: 1.25rem 1.5rem;
  border: 2px solid var(--govcolor-red, #a80521);
  border-radius: 0.313rem;
  background-color: #fff;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.aviso-no-radicado h2 {
  color: var(--govcolor-red, #a80521);
}

/*
  El asterisco de obligatorio. Va oculto para la tecnología de asistencia porque
  el carácter suelto no se anuncia con sentido; lo que la anuncia de verdad es el
  atributo `required` del propio control, que el navegador expone como estado
  «obligatorio» aunque el formulario lleve `novalidate`.
*/
.asterisco {
  color: var(--govcolor-red, #a80521);
}

.texto-ayuda {
  margin-top: 0.375rem;
  margin-bottom: 0;
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.875rem;
}

.leyenda-obligatorios {
  margin-bottom: 1.5rem;
}

/*
  Mensaje de error de un campo. Es el destino del `aria-describedby` del control
  y lleva `role="alert"`, así que se anuncia al aparecer y también al entrar en el
  campo. El rojo del Kit sobre blanco da 7,7:1.
*/
.error-campo {
  margin-top: 0.375rem;
  margin-bottom: 0;
  color: var(--govcolor-red, #a80521);
  font-weight: 600;
}

/* El campo con error se distingue también en el borde, no sólo por el texto. */
.formulario-pqrsd [aria-invalid='true'] {
  border-color: var(--govcolor-red, #a80521);
}

.recordatorio-envio {
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}

/*
  Bootstrap pinta el foco de los controles de formulario con su propio azul
  (`#86b7fe` y `rgba(13,110,253,.25)`), que no pertenece a la capa visual gov.co.
  El Kit no define foco para campos de texto —sólo para sus propios botones y
  casillas—, así que se corrige aquí con el azul del Kit.
*/
.formulario-pqrsd .form-control:focus,
.formulario-pqrsd .form-select:focus,
.formulario-pqrsd .form-check-input:focus {
  border-color: var(--govcolor-cobalt, #0943b5);
  box-shadow: 0 0 0 0.2rem rgba(9, 67, 181, 0.35);
}

/*
  Botón de envío. El selector lleva las tres partes porque el Kit declara sus
  propias reglas para `.btn-govco.fill-btn-govco` y hay que ganarle el empate para
  fijar el área pulsable mínima que pide WCAG 2.5.5.
*/
button.btn-govco.boton-enviar {
  min-height: 2.75rem;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

/* ==========================================================================
   Línea de avance del formulario (RF-B2-077, CAG-20, Sección 3:360)
   ==========================================================================

   Es una **línea**, no tres píldoras sueltas: los pasos van en fila, unidos por
   un conector, y el que se está rellenando se distingue por tres cosas a la vez
   —relleno, peso de letra y el número— para que no dependa del color. El
   completado lleva una marca dibujada; el pendiente, sólo contorno.

   Área de pulsación de 44 px por paso (CAG-23) y contraste medido: blanco sobre
   cobalto 8,46:1 en el paso actual, cobalto sobre blanco 8,46:1 en el completado,
   Matterhorn sobre blanco 8,59:1 en el pendiente.
*/
.pasos-formulario {
  margin: 1.5rem 0 2rem;
}

.pasos-encabezado {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  /* El hueco visual lo da el `gap`; el espacio del texto se queda porque es lo
     que hace que el encabezado diga literalmente «Paso N de M: Nombre». */
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.pasos-cuenta {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.pasos-titulo {
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
  font-size: 1.125rem;
}

.pasos-lista {
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
}

.pasos-item {
  display: flex;
  flex: 1 1 0;
  align-items: center;
  min-width: 0;
}

/* El conector entre pasos: lo que convierte los botones en una línea. */
.pasos-item + .pasos-item::before {
  content: '';
  flex: 0 0 1.25rem;
  height: 0.125rem;
  background-color: var(--govcolor-silver, #cccccc);
}

.paso {
  display: inline-flex;
  /*
   * Base 0: lo que se reparte por igual es el **item** de cada paso, que es lo
   * que fija dónde cae cada marcador. Con `auto`, el paso actual —que va en
   * negrita— crecía un poco más y los marcadores dejaban de estar a la misma
   * distancia. El botón de dentro mide lo que le deja el conector (20 px en los
   * pasos 2 y 3), y eso no se nota porque lo que se lee como línea son los
   * marcadores.
   */
  flex: 1 1 0;
  align-items: center;
  gap: 0.625rem;
  min-width: 0;
  min-height: 2.75rem;
  padding: 0.5rem 0.875rem;
  border: 0.125rem solid var(--govcolor-silver, #cccccc);
  border-radius: 0.5rem;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-family: 'Nunito_Sans-Regular', system-ui, sans-serif;
  font-size: 0.9375rem;
  line-height: 1.25;
  text-align: left;
  transition: border-color 0.15s ease, background-color 0.15s ease;
}

.paso:hover {
  border-color: var(--govcolor-havelock-lue, #4672c8);
}

.paso-marca {
  display: inline-grid;
  flex: none;
  place-items: center;
  width: 1.75rem;
  height: 1.75rem;
  border: 0.125rem solid currentColor;
  border-radius: 50%;
  font-size: 0.875rem;
  font-weight: 700;
}

.paso-marca-icono {
  width: 1.125rem;
  height: 1.125rem;
}

.paso-nombre {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.paso-hecho {
  border-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-cobalt, #0943b5);
}

.paso-actual {
  border-color: var(--govcolor-cobalt, #0943b5);
  background-color: var(--govcolor-solitude, #e5ecf8);
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Nunito_Sans-Bold', system-ui, sans-serif;
}

/* El marcador del paso actual va relleno: es la señal que no depende del color. */
.paso-actual .paso-marca {
  border-color: var(--govcolor-cobalt, #0943b5);
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
}

.paso-hecho .paso-marca {
  border-color: var(--govcolor-cobalt, #0943b5);
}

/*
 * En pantallas estrechas la línea completa no cabe sin apretujar los nombres, y
 * el encabezado ya dice en qué paso se está. Se queda la línea de marcadores
 * —que es lo que da la posición— y los nombres se ocultan: el nombre del paso
 * actual sigue visible arriba.
 */
@media (max-width: 767.98px) {
  .paso-nombre {
    display: none;
  }

  .paso {
    justify-content: center;
    padding: 0.5rem 0.625rem;
  }
}

/* Texto sólo para lectores de pantalla: el estado de cada paso, fuera de la vista. */
.solo-lectores {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.pasos-navegacion {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.5rem;
  margin-bottom: 0;
}

/*
 * La ayuda del objeto y su contador, en la misma fila: el contador se consulta
 * mientras se escribe, así que vive pegado al campo y no al final del bloque.
 */
.objeto-ayuda {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem 1rem;
}

.objeto-ayuda .texto-ayuda {
  flex: 1 1 18rem;
}

/* La lista de archivos elegidos: nombre, peso y un botón para quitarlos. */
.lista-adjuntos {
  padding-left: 0;
  margin: 1rem 0 0;
  list-style: none;
}

.lista-adjuntos li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.75rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--govcolor-silver, #cccccc);
  border-radius: 0.25rem;
}

.lista-adjuntos li + li {
  margin-top: 0.5rem;
}

.adjunto-nombre {
  flex: 1 1 12rem;
  min-width: 0;
  overflow-wrap: anywhere;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.adjunto-peso {
  flex: none;
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.875rem;
  font-variant-numeric: tabular-nums;
}

/* 44 px de área de pulsación (CAG-23), con el texto delante para el nombre accesible. */
.adjunto-quitar {
  flex: none;
  min-height: 2.75rem;
  padding: 0.25rem 0.75rem;
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  border-radius: 1.5rem;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-cobalt, #0943b5);
  font-size: 0.875rem;
}

.objeto-contador {
  flex: none;
  margin: 0.375rem 0 0;
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.875rem;
  font-variant-numeric: tabular-nums;
}

/* Al acercarse al tope, el contador avisa; el color va acompañado del número. */
.objeto-contador-lleno {
  color: var(--govcolor-red, #a80521);
  font-weight: 700;
}
</style>

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
  'autorizacion',
]

/** Comprobación de forma, no de existencia: que el correo se pueda responder. */
const FORMA_DE_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

/** Longitud mínima de la descripción, para que no se envíe una sola palabra. */
const MINIMO_DE_DESCRIPCION = 10

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

    <form ref="refFormulario" class="formulario-pqrsd" novalidate @submit.prevent="enviar">
      <h2 class="h3 mt-5">Solicitud</h2>

      <div class="row">
        <div class="col-lg-8">
          <div class="mb-4">
            <label class="form-label" for="tipoSolicitud">
              Tipo de solicitud <span class="asterisco" aria-hidden="true">*</span>
            </label>

            <select
              id="tipoSolicitud"
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
              v-model="datos.descripcion"
              class="form-control"
              rows="6"
              maxlength="4000"
              required
              :aria-invalid="invalido('descripcion')"
              :aria-describedby="descritoPor('descripcion')"
            ></textarea>

            <p :id="idAyuda('descripcion')" class="texto-ayuda">
              Escriba lo que solicita con el detalle que necesite para que pueda
              entenderse y responderse. Máximo 4000 caracteres.
            </p>

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
        <p v-if="!esPersonal" class="mb-0 mt-2">
          En la modalidad <strong>anónima</strong> no se recopila ninguno de esos datos.
        </p>
      </div>

      <!-- ================= Datos del solicitante ================= -->
      <h2 class="h3 mt-5">Datos del solicitante</h2>

      <div class="row">
        <div class="col-lg-10">
          <p>
            A continuación completa tus datos para darte respuesta a tu solicitud
          </p>

          <p class="leyenda-obligatorios">
            <strong>Los campos en asterisco (*) son obligatorios</strong>. Los demás son
            opcionales.
          </p>
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

      <!-- Autorización del tratamiento de datos -->
      <div v-if="esPersonal" class="mb-4">
        <div class="form-check">
          <input
            id="autorizacion"
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

      <p class="mb-0">
        <button
          class="btn-govco fill-btn-govco boton-enviar"
          type="submit"
          aria-describedby="aviso-radicacion"
        >
          Enviar la solicitud
        </button>
      </p>
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
  WCAG 2.1 AA. El filete izquierdo lleva el azul de la Entidad.
*/
.aviso-radicacion {
  margin-top: 1.5rem;
  margin-bottom: 2rem;
  padding: 1rem 1.25rem;
  border-left: 6px solid var(--govcolor-cobalt, #0943b5);
  background-color: var(--govcolor-vis-vis, #fee697);
  color: var(--govcolor-matterhorn, #4c4c4c);
}

/*
  Aviso de tratamiento de datos. El azul claro es el token `--govcolor-solitude`
  del Kit, que con el Matterhorn para el texto da 7,2:1: es un aviso informativo
  y no debe competir con el amarillo, que está reservado para lo que impide
  terminar.
*/
.aviso-datos {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border-left: 4px solid var(--govcolor-cobalt, #0943b5);
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
</style>

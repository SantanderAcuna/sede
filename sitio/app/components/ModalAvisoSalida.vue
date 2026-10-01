<script setup lang="ts">
/**
 * Aviso de salida a sitio externo (RF-B1-071, RN-01-D03).
 *
 * Muestra a dónde va el ciudadano, quién responde de ese sitio y le pide
 * confirmación antes de salir. No navega: emite `confirmar`, y quien decide y
 * navega es `useAvisoSalida`, que conoce si el enlace abría en pestaña nueva.
 *
 * **Es un modal de verdad, no un aviso decorativo.** CAG-21 y RF-B3-017 exigen
 * que se pueda cerrar con `Esc` y con un clic fuera, y que el teclado no se
 * escape del diálogo. Un modal que deja tabular hacia la página de detrás
 * convierte el aviso en una trampa: el ciudadano sigue tabulando y acaba
 * pulsando algo del fondo sin verlo. Por eso:
 *
 *  - el foco **entra** al abrir (al botón de permanecer, que es la acción
 *    segura: si alguien pulsa Intro sin leer, no sale de la sede),
 *  - **no sale** mientras está abierto: `Tab` y `Shift+Tab` circulan por los dos
 *    controles,
 *  - y **vuelve** al elemento que lo abrió al cerrarse, para que quien navega
 *    con teclado no aparezca al principio de la página.
 *
 * El `Esc` se escucha en el documento y no en el contenedor: si el foco llegara
 * a escaparse —un lector de pantalla, una extensión—, un manejador colgado del
 * contenedor dejaría de oírlo y el aviso se quedaría abierto sin salida.
 */
import { nextTick, onBeforeUnmount, onUnmounted, ref, watch } from 'vue'

interface Props {
  /** URL destino que se mostraría si se confirma. */
  destino: string
  /** Nombre del sitio o servicio destino. */
  nombreDestino: string
  /** Entidad responsable del sitio destino. */
  entidadResponsable: string
  /** Si el aviso está visible. */
  visible: boolean
  /**
   * Elemento que abrió el aviso. Es a quien hay que devolverle el foco al
   * cerrarlo; si no llega, se usa el que estuviera enfocado al abrirlo.
   */
  origen?: HTMLElement | null
}

interface Emits {
  (e: 'confirmar'): void
  (e: 'cancelar'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

/** Contenedor del diálogo: es el ámbito de la trampa de foco. */
const modalRef = ref<HTMLElement | null>(null)
/** Elemento que tenía el foco antes de abrir, para devolvérselo al cerrar. */
let focoAnterior: HTMLElement | null = null

/** Los controles enfocables del diálogo, en orden de tabulación. */
function controles(): HTMLElement[] {
  const raiz = modalRef.value
  if (!raiz) return []
  return Array.from(
    raiz.querySelectorAll<HTMLElement>('button:not([disabled]), [href], select, textarea, input'),
  ).filter((elemento) => elemento.offsetParent !== null)
}

/** Mantiene el foco dentro del diálogo mientras está abierto. */
function atraparFoco(evento: KeyboardEvent): void {
  if (evento.key !== 'Tab') return
  const elementos = controles()
  if (elementos.length === 0) return

  const primero = elementos[0]
  const ultimo = elementos[elementos.length - 1]
  const activo = document.activeElement as HTMLElement | null

  // Si el foco se escapó del diálogo, se trae de vuelta en vez de dejarlo ir.
  if (!activo || !modalRef.value?.contains(activo)) {
    evento.preventDefault()
    primero?.focus()
    return
  }

  if (evento.shiftKey && activo === primero) {
    evento.preventDefault()
    ultimo?.focus()
  } else if (!evento.shiftKey && activo === ultimo) {
    evento.preventDefault()
    primero?.focus()
  }
}

/** Cierra el aviso con `Esc`, como exige CAG-21. */
function alPulsarTecla(evento: KeyboardEvent): void {
  if (evento.key === 'Escape') {
    evento.preventDefault()
    cancelar()
    return
  }
  atraparFoco(evento)
}

function confirmar(): void {
  emit('confirmar')
}

function cancelar(): void {
  emit('cancelar')
}

/*
 * `immediate: true` no es un adorno: **es la diferencia entre que el aviso se
 * pueda cerrar con el teclado o no**.
 *
 * Quien abre el aviso (`solicitarConfirmacion`) escribe primero
 * `enlacePendiente` y después `visible = true`, en el mismo tic. Como el
 * componente se monta por el `v-if="enlacePendiente"`, cuando el `watch` se
 * registra `props.visible` **ya vale `true`**: sin `immediate`, el observador no
 * se dispara nunca y el aviso queda abierto sin escucha de `Escape` y sin mover
 * el foco dentro del diálogo. La puerta de diseño lo cazó midiendo: el aviso
 * aparecía, `Escape` no lo cerraba y el foco seguía en el enlace.
 */
watch(
  () => props.visible,
  async (mostrar) => {
    if (!import.meta.client) return

    if (mostrar) {
      // Se prefiere el disparador que envía quien abre el aviso: al cerrarlo, el
      // foco está dentro del diálogo y ya no se puede deducir de dónde vino.
      focoAnterior = props.origen ?? (document.activeElement as HTMLElement | null)
      document.addEventListener('keydown', alPulsarTecla, true)
      // El diálogo tiene que existir en el DOM antes de poder enfocar dentro.
      await nextTick()
      modalRef.value?.querySelector<HTMLElement>('.btn-cancelar')?.focus()
    } else {
      document.removeEventListener('keydown', alPulsarTecla, true)
      const destino = focoAnterior
      focoAnterior = null
      // Devolver el foco a quien abrió el aviso —sin esto, quien navega con
      // teclado vuelve al principio del documento y pierde el hilo— y hacerlo
      // **después** de que el diálogo salga del DOM: hacerlo antes deja que el
      // navegador mande el foco al `<body>` al retirar el subárbol que lo tenía.
      await nextTick()
      destino?.focus()
    }
  },
  { immediate: true },
)

/**
 * A quién hay que devolverle el foco cuando el aviso desaparece del DOM.
 *
 * Hace falta porque el aviso se cierra de **dos** maneras y sólo una pasa por el
 * observador de `visible`: la disposición lo monta con `v-if="enlacePendiente"`,
 * así que al cancelar el componente se desmonta en el mismo tic y el observador
 * no llega a ver el `false`. Sin esto, el navegador manda el foco al `<body>` al
 * retirar el subárbol que lo tenía y quien navega con teclado vuelve al principio
 * del documento (lo midió la puerta de diseño, CAG-21).
 */
let focoAlDesmontar: HTMLElement | null = null

onBeforeUnmount(() => {
  if (import.meta.client) document.removeEventListener('keydown', alPulsarTecla, true)
  focoAlDesmontar = props.visible ? (props.origen ?? focoAnterior) : null
})

// `onUnmounted` corre con el subárbol ya retirado: enfocar antes no serviría,
// porque la retirada del diálogo volvería a mover el foco.
onUnmounted(() => {
  focoAlDesmontar?.focus()
  focoAlDesmontar = null
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="visible"
      data-aviso-salida
      class="contenedor-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="titulo-aviso-salida"
      aria-describedby="descripcion-aviso-salida"
    >
      <!-- Fondo oscurecido: cerrar pulsando fuera también es CAG-21. -->
      <div class="fondo-modal" @click="cancelar" />

      <div ref="modalRef" class="modal-aviso-salida">
        <div class="encabezado-modal">
          <span class="icono-alerta" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <h2 id="titulo-aviso-salida" class="titulo-modal">
            Está a punto de salir de la Sede Electrónica
          </h2>
        </div>

        <div class="cuerpo-modal">
          <p id="descripcion-aviso-salida" class="descripcion-destino">
            Está a punto de abandonar el sitio de la Alcaldía Distrital de Santa Marta
            para acceder a:
          </p>

          <div class="info-destino">
            <p class="nombre-destino">
              <strong>{{ nombreDestino }}</strong>
            </p>
            <p class="entidad-responsable">
              {{ entidadResponsable }}
            </p>
            <p class="url-destino">
              {{ destino }}
            </p>
          </div>

          <p class="aviso-privacidad">
            La Alcaldía Distrital de Santa Marta no responde por el contenido, la
            privacidad ni la seguridad de los sitios externos. Al continuar se
            aplicarán las condiciones del sitio de destino.
          </p>
        </div>

        <div class="acciones-modal">
          <button
            type="button"
            class="btn btn-cancelar"
            @click="cancelar"
          >
            Permanecer en la sede
          </button>
          <!--
            Es un `<button>` y no un enlace: la navegación la hace el composable,
            que sabe si el enlace original abría en pestaña nueva. Un `<a href>`
            aquí navegaría por su cuenta y, como este aviso vive sobre la página
            que se abandona, el ciudadano saldría con el diálogo abierto.
          -->
          <button
            type="button"
            class="btn btn-confirmar"
            @click="confirmar"
          >
            Continuar al sitio externo
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.contenedor-modal {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.fondo-modal {
  position: absolute;
  inset: 0;
  background-color: rgba(0 0 0 / 0.6);
  cursor: pointer;
}

.modal-aviso-salida {
  position: relative;
  background-color: #fff;
  border-radius: 0.5rem;
  max-width: 32rem;
  width: 100%;
  box-shadow: 0 1rem 3rem rgba(0 0 0 / 0.3);
  overflow: hidden;
}

.encabezado-modal {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  background-color: #fff8e6;
  border-bottom: 0.0625rem solid #ffe4a0;
}

.icono-alerta {
  flex: 0 0 auto;
  color: #b45309;
}

.titulo-modal {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 700;
  color: #92400e;
  line-height: 1.4;
}

.cuerpo-modal {
  padding: 1.25rem 1.5rem;
}

.descripcion-destino {
  margin: 0 0 1rem;
  font-size: 0.9375rem;
  color: #333;
  line-height: 1.5;
}

.info-destino {
  background-color: #f3f4f6;
  border-radius: 0.375rem;
  padding: 0.875rem 1rem;
  margin-bottom: 1rem;
}

.nombre-destino {
  margin: 0 0 0.25rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111;
}

.entidad-responsable {
  margin: 0 0 0.25rem;
  font-size: 0.875rem;
  color: #555;
}

/*
 * La URL se publica con el gris Matterhorn del Kit y no con un gris claro: es
 * texto informativo y tiene que leerse. Un `#888` sobre este fondo daba 3,22:1,
 * por debajo del 4,5:1 que exige RNF-B1-017; el Matterhorn da 6,77:1.
 */
.url-destino {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  word-break: break-all;
}

.aviso-privacidad {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  line-height: 1.5;
}

.acciones-modal {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 0.0625rem solid #e5e7eb;
  background-color: #fafafa;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.75rem;
  padding: 0.5rem 1.25rem;
  border-radius: 0.375rem;
  font-family: 'Nunito_Sans-SemiBold', system-ui, sans-serif;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: background-color 0.15s ease, border-color 0.15s ease;
  text-decoration: none;
}

.btn-cancelar {
  background-color: #fff;
  border: 0.125rem solid #d1d5db;
  color: #374151;
  flex: 1 1 auto;
}

.btn-cancelar:hover {
  background-color: #f3f4f6;
}

.btn-cancelar:focus-visible {
  outline: 0.188rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 0.125rem;
}

.btn-confirmar {
  background-color: var(--govcolor-cobalt, #0943b5);
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  color: #fff;
  flex: 1 1 auto;
}

.btn-confirmar:hover {
  background-color: #072f6e;
  border-color: #072f6e;
}

.btn-confirmar:focus-visible {
  outline: 0.188rem solid #fff;
  outline-offset: 0.125rem;
}

@media (max-width: 479px) {
  .modal-aviso-salida {
    max-width: 100%;
  }

  .acciones-modal {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>

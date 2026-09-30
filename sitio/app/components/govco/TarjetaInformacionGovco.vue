<script setup lang="ts">
/**
 * Tarjeta de información (Kit UI, componente general 1).
 *
 * Portado de `examples/general/tarjetas-de-informacion.html`. El Kit define tres
 * familias —imagen + texto, ícono o ilustración + texto, y tipo módulo— con sus
 * variantes horizontales, invertida y vertical.
 *
 * La tarjeta entera es un `<a>` o un `<button>`, nunca un bloque con un enlace
 * dentro (CAG-26): así hay una sola parada de tabulador, el área pulsable es toda
 * la tarjeta y el foco rodea el conjunto en vez de subrayar el título. El estado
 * deshabilitado se resuelve con un `<button disabled>`, que es la forma de decir
 * «no se puede pulsar» sin sacarlo del discurso del lector de pantalla; el Kit lo
 * hacía con un `<div>` (invisible para el teclado y sin estado que anunciar) y con
 * el texto en blanco sobre gris, que da 1,95:1. Aquí el gris se mantiene como
 * señal, pero sobre el gris claro del propio Kit (#C8C8C8) y con el texto oscuro:
 * el título en cobalto queda en 5,69:1 y el cuerpo en 10,97:1.
 *
 * El título no puede pasar de dos palabras (CAG-26). No se recorta —mutilaría el
 * contenido que llega por props—, pero en desarrollo se avisa.
 */
import { computed } from 'vue'

export type TipoTarjetaInformacion =
  | 'imagen-horizontal'
  | 'imagen-horizontal-inversa'
  | 'imagen-vertical'
  | 'icono-vertical'
  | 'icono-horizontal'
  | 'modulo'

/** Clases del Kit para cada tipo. */
const CLASES_POR_TIPO: Record<TipoTarjetaInformacion, string[]> = {
  'imagen-horizontal': ['tarjeta-govco', 'horizontal-tarjeta-govco'],
  'imagen-horizontal-inversa': ['tarjeta-govco', 'horizontal-tarjeta-govco', 'reverse-tarjeta-govco'],
  'imagen-vertical': ['tarjeta-govco', 'vertical-tarjeta-govco'],
  'icono-vertical': ['icono-tarjeta-govco', 'vertical-tarjeta-govco'],
  'icono-horizontal': ['icono-tarjeta-govco', 'horizontal-tarjeta-govco'],
  modulo: ['module-tarjeta-govco'],
}

interface PropsTarjeta {
  tipo?: TipoTarjetaInformacion
  /** Máximo dos palabras (CAG-26). */
  titulo: string
  descripcion?: string
  /** Imagen de las tarjetas de imagen, ilustración de las de ícono. */
  imagen?: string
  /**
   * Por defecto vacío: la tarjeta ya se llama por su título, y repetir la imagen
   * haría que un lector de pantalla anunciara dos veces lo mismo. Se rellena sólo
   * cuando la imagen aporta algo que el título no dice.
   */
  altImagen?: string
  /** Fecha que muestra la tarjeta vertical de imagen. */
  fecha?: string
  enlace?: string
  deshabilitada?: boolean
}

const props = withDefaults(defineProps<PropsTarjeta>(), {
  tipo: 'imagen-horizontal',
  descripcion: '',
  imagen: '',
  altImagen: '',
  fecha: '',
  enlace: '',
  deshabilitada: false,
})

const emit = defineEmits<{ seleccionar: [titulo: string] }>()

const familia = computed<'imagen' | 'icono' | 'modulo'>(() => {
  if (props.tipo.startsWith('imagen')) return 'imagen'
  if (props.tipo.startsWith('icono')) return 'icono'
  return 'modulo'
})

const clases = computed(() => CLASES_POR_TIPO[props.tipo])

/** Imágenes de ejemplo del propio Kit mientras no llegue una por props. */
const imagenFinal = computed(
  () =>
    props.imagen ||
    (familia.value === 'icono'
      ? '/govco/assets/images/ilustracion-color.svg'
      : '/govco/assets/images/tarjeta-imagen.png'),
)

// Sin enlace no hay destino al que navegar, así que la tarjeta es un botón que
// avisa por evento; deshabilitada, un botón deshabilitado.
const etiqueta = computed<'a' | 'button'>(() =>
  props.enlace !== '' && !props.deshabilitada ? 'a' : 'button',
)

const atributos = computed(() =>
  etiqueta.value === 'a'
    ? { href: props.enlace }
    : { type: 'button' as const, disabled: props.deshabilitada },
)

if (import.meta.dev && props.titulo.trim().split(/\s+/).length > 2) {
  console.warn(`[TarjetaInformacionGovco] El título «${props.titulo}» pasa de dos palabras (CAG-26).`)
}

function alSeleccionar(): void {
  if (etiqueta.value === 'button' && !props.deshabilitada) emit('seleccionar', props.titulo)
}
</script>

<template>
  <component
    :is="etiqueta"
    v-bind="atributos"
    class="tarjeta-informacion-govco"
    :class="clases"
    @click="alSeleccionar"
  >
    <!-- Imagen + texto: horizontal, invertida y vertical -->
    <template v-if="familia === 'imagen'">
      <div class="container-img-tarjeta-govco">
        <img class="image-tarjeta-govco" :src="imagenFinal" :alt="altImagen">
      </div>
      <div class="body-tarjeta-govco" :class="{ 'tarjeta-texto': tipo === 'imagen-vertical' }">
        <span v-if="tipo === 'imagen-vertical' && fecha" class="vertical-tarjeta-align-govco">{{ fecha }}</span>
        <h5 :class="{ 'vertical-tarjeta-align-govco': tipo === 'imagen-vertical' }">{{ titulo }}</h5>
        <p v-if="descripcion" :class="{ 'vertical-tarjeta-align-govco': tipo === 'imagen-vertical' }">
          {{ descripcion }}
        </p>
      </div>
    </template>

    <!-- Ícono o ilustración + texto -->
    <template v-else-if="familia === 'icono'">
      <div class="container-icono-tarjeta-govco">
        <img :src="imagenFinal" :alt="altImagen">
      </div>
      <div v-if="tipo === 'icono-vertical'" class="body-tarjeta-govco tarjeta-texto-secundario">
        <div class="title-body-tarjeta-govco">
          <h5 class="titulo-ilustracion">{{ titulo }}</h5>
        </div>
        <p v-if="descripcion">{{ descripcion }}</p>
      </div>
      <div v-else class="body-tarjeta-govco">
        <h5>{{ titulo }}</h5>
      </div>
    </template>

    <!-- Tipo módulo -->
    <template v-else>
      <div class="header-tarjeta-govco">
        <h6>{{ titulo }}</h6>
      </div>
      <hr>
      <div class="body-tarjeta-govco">
        <p v-if="descripcion">{{ descripcion }}</p>
      </div>
    </template>
  </component>
</template>

<style scoped>
/*
 * El Kit estiliza estas tarjetas sobre `<a>` —y sobre `<div>` cuando están
 * deshabilitadas—: de `<button>` sólo contempla las variantes de ícono. El
 * reinicio de Bootstrap cubre la tipografía y los márgenes del botón, pero no su
 * borde ni su relleno —eso lo hace `.btn`, que aquí no se usa—, así que hay que
 * neutralizarlos sin pisar lo que el Kit sí define: el borde de la tarjeta
 * horizontal y los rellenos de las de ícono.
 */
button.tarjeta-govco:not(.horizontal-tarjeta-govco),
button.module-tarjeta-govco {
  border: 0;
}

button.tarjeta-govco,
button.module-tarjeta-govco {
  /* Ninguna de sus variantes lleva relleno propio en la raíz. */
  padding: 0;
  color: inherit;
  font: inherit;
  text-align: left;
}

button.tarjeta-govco,
button.icono-tarjeta-govco,
button.module-tarjeta-govco {
  cursor: pointer;
}

/* Estado deshabilitado, para los tres tipos. */
button.tarjeta-informacion-govco:disabled {
  cursor: default;
  background-color: #c8c8c8;
}

button.tarjeta-informacion-govco:disabled .body-tarjeta-govco {
  background-color: #c8c8c8;
}

button.tarjeta-informacion-govco:disabled .body-tarjeta-govco h5,
button.tarjeta-informacion-govco:disabled .body-tarjeta-govco p,
button.tarjeta-informacion-govco:disabled .body-tarjeta-govco span {
  color: #1f1f1f;
}

/* La imagen en gris: señal de inactivo sin tocar el contraste del texto. El
   título del tipo módulo queda fuera a propósito: el Kit lo fija con
   `!important` en cobalto, que sobre este gris todavía da 5,69:1. */
button.tarjeta-informacion-govco:disabled .container-img-tarjeta-govco,
button.tarjeta-informacion-govco:disabled .container-icono-tarjeta-govco {
  filter: grayscale(1);
}
</style>

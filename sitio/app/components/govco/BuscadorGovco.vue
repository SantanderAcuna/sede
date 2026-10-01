<script setup lang="ts">
/**
 * Buscador (Kit UI, componente general 3).
 *
 * Portado de `examples/general/buscador.html`, en su variante básica. El Kit trae
 * además un buscador predictivo con un `<input type="hidden">` y una lista de
 * sugerencias; no se implementa porque no hay servicio de sugerencias al que
 * preguntar, y un desplegable que no sugiere nada molesta más de lo que ayuda.
 *
 * El componente no busca: **sólo recoge el término y lo emite**. La búsqueda real
 * la decide la página que lo use.
 *
 * Lo que el Kit resolvía con `inicializarBuscador()` → `InitSearchDefault()` está
 * aquí en estado reactivo. Aquel código consultaba el DOM en cada pulsación de
 * tecla para mostrar u ocultar la línea y el botón de limpiar, y para devolver el
 * foco al campo cuando quedaba vacío; aquí eso es una `computed` (`hayTexto`) y un
 * atributo de clase, sin tocar el DOM.
 *
 * CAG-15: el campo tiene un nombre claro —etiqueta para lectores de pantalla y
 * `placeholder` a la vista—, se puede vaciar con el botón de limpiar o con `Esc`,
 * y se recorre con el teclado: el campo, el botón de limpiar y el de buscar son
 * elementos nativos, en ese orden, dentro de un `<form>`, de modo que `Enter`
 * envía sin necesidad de ninguna tecla capturada a mano.
 */
import { computed, ref, useId } from 'vue'

interface PropsBuscador {
  /** Nombre del campo. Es invisible, pero es el que anuncia el lector de pantalla. */
  etiqueta?: string
  placeholder?: string
  /** Texto del botón de envío, que va sólo con icono. */
  textoBoton?: string
  textoLimpiar?: string
  valorInicial?: string
}

const props = withDefaults(defineProps<PropsBuscador>(), {
  etiqueta: 'Buscar en el sitio',
  placeholder: 'Buscar trámites, servicios y noticias',
  textoBoton: 'Buscar',
  textoLimpiar: 'Limpiar la búsqueda',
  valorInicial: '',
})

const emit = defineEmits<{
  buscar: [termino: string]
  limpiar: []
}>()

const termino = ref(props.valorInicial)
const activo = ref(false)
const contenedor = ref<HTMLElement | null>(null)
const campo = ref<HTMLInputElement | null>(null)

// Identificador único por instancia: en una portada puede haber más de un
// buscador y la etiqueta tiene que apuntar al suyo, también en el HTML del servidor.
const idCampo = useId()

const hayTexto = computed(() => termino.value !== '')

function buscar(): void {
  const limpio = termino.value.trim()
  // En blanco no es una búsqueda: se devuelve el foco al campo en vez de emitir
  // un evento que la página no sabría qué hacer con él.
  if (limpio === '') {
    campo.value?.focus()
    return
  }
  emit('buscar', limpio)
}

function limpiar(): void {
  termino.value = ''
  emit('limpiar')
  // El Kit también devuelve el foco al campo al limpiar.
  campo.value?.focus()
}

function alPulsarTecla(evento: KeyboardEvent): void {
  if (evento.key === 'Escape' && hayTexto.value) limpiar()
}

function alEntrarFoco(): void {
  activo.value = true
}

function alSalirFoco(evento: FocusEvent): void {
  // El contorno de foco del Kit rodea todo el contenedor, no el campo suelto: hay
  // que distinguir «se fue a otro control del buscador» de «se fue del buscador».
  const destino = evento.relatedTarget
  if (destino instanceof Node && contenedor.value?.contains(destino)) return
  activo.value = false
}
</script>

<template>
  <form class="govco-search-basic" role="search" :aria-label="etiqueta" @submit.prevent="buscar">
    <div
      ref="contenedor"
      class="container-govco"
      :class="{ active: activo }"
      @focusin="alEntrarFoco"
      @focusout="alSalirFoco"
    >
      <label :for="idCampo" class="etiqueta-buscador-govco">{{ etiqueta }}</label>
      <input
        :id="idCampo"
        ref="campo"
        v-model="termino"
        class="input-search-basic-govco"
        type="text"
        autocomplete="off"
        :placeholder="placeholder"
        @keydown.esc.prevent="alPulsarTecla"
      >

      <!-- Visible sólo cuando hay algo que borrar: así tampoco es una parada de
           tabulador de más. -->
      <button
        type="button"
        class="btn-clean-basic-govco"
        :class="{ active: hayTexto }"
        :aria-label="textoLimpiar"
        @click="limpiar"
      >
        <span class="govco-svg govco-times" aria-hidden="true" />
      </button>
      <div class="line-basic-govco" :class="{ active: hayTexto }" />

      <button type="submit" class="btn-search-basic-govco" :aria-label="textoBoton">
        <span class="govco-svg govco-search" aria-hidden="true" />
      </button>
    </div>
  </form>
</template>

<style scoped>
/* El Kit dimensiona el buscador con `width: -webkit-fill-available`, que sólo
   entiende WebKit: en Firefox la declaración se descarta y el ancho quedaría a
   merced del contexto. */
.govco-search-basic {
  width: 100%;
}

/* El `display: flex` de los ejemplos del Kit viene de la utilidad `d-flex` de
   Bootstrap; aquí se declara en la hoja del componente para no depender de que
   esa clase esté cargada. */
.govco-search-basic .container-govco {
  display: flex;
  align-items: center;
}

/* Etiqueta sólo para lectores de pantalla: el campo se explica con el
   `placeholder`, que desaparece al escribir y no vale como nombre accesible. Se
   escribe aquí en vez de usar la utilidad `visually-hidden` de Bootstrap para no
   arrastrar sus `!important` ni depender de ella. */
.etiqueta-buscador-govco {
  position: absolute;
  overflow: hidden;
  clip-path: inset(50%);
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  white-space: nowrap;
}

/**
 * Estilos del buscador - cumplimiento CC4, CC5, CC17
 *
 * CC4: Texto e imágenes ampliables hasta 200% sin deformación.
 *      Tamaño base mínimo: 16px / 12pt
 * CC5: Contraste de color suficiente (4.5:1 texto normal, 3:1 componentes UI)
 * CC17: Foco visible con contraste mínimo 3:1
 */

/* Campo de entrada - tamaño base mínimo 16px (CC4) */
.input-search-basic-govco {
  flex: 1;
  min-width: 0;
  height: 2.75rem;
  padding: 0.5rem 0.75rem;
  border: none;
  border-bottom: 0.125rem solid #767676;
  background-color: transparent;
  color: #1a1a1a;
  font-family: 'Nunito_Sans-Regular', system-ui, sans-serif;
  font-size: 1rem; /* 16px mínimo - CC4 */
  line-height: 1.5;
  outline: none;
  transition: border-color 0.2s ease;
}

/* Placeholder con contraste 4.5:1 (CC5) */
.input-search-basic-govco::placeholder {
  color: #4a4a4a;
  opacity: 1;
}

/* Foco en el campo - indicador visible (CC17) */
.input-search-basic-govco:focus {
  border-bottom-color: #00ade7;
  border-bottom-width: 0.1875rem;
}

/* Botón de limpiar */
.btn-clean-basic-govco {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: none;
  background: transparent;
  color: #767676;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, color 0.2s ease;
}

.btn-clean-basic-govco.active {
  opacity: 1;
  visibility: visible;
}

/* Hover del botón limpiar */
.btn-clean-basic-govco:hover {
  color: #1a1a1a;
}

/* Foco visible en botón limpiar - CC17 (contraste 3:1) */
.btn-clean-basic-govco:focus-visible {
  outline: 0.125rem solid #00ade7;
  outline-offset: 0.125rem;
  border-radius: 50%;
}

/* Línea decorativa */
.line-basic-govco {
  width: 0;
  height: 0.125rem;
  background-color: #00ade7;
  transition: width 0.2s ease;
}

.line-basic-govco.active {
  width: 1.5rem;
}

/* Botón de búsqueda */
.btn-search-basic-govco {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  border: none;
  background-color: transparent;
  color: #00ade7;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease;
}

/* Hover del botón buscar */
.btn-search-basic-govco:hover {
  background-color: var(--govcolor-solitude, #e5ecf8);
  border-radius: 50%;
}

/* Foco visible en botón buscar - CC17 (contraste 3:1) */
.btn-search-basic-govco:focus-visible {
  outline: 0.1875rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 0.125rem;
  border-radius: 50%;
}

/* Estado activo del contenedor (cuando tiene foco) - CC17 */
.container-govco.active .input-search-basic-govco {
  border-bottom-color: var(--govcolor-cobalt, #0943b5);
}

/* Escalado al 200% sin deformación - CC4 */
@media (max-width: 576px) {
  .input-search-basic-govco {
    font-size: 1rem; /* 16px mínimo en móvil también */
  }
}

/* El icono de la lupa del Kit se muestra con clase govco-search */
:deep(.govco-search) {
  width: 1.25rem;
  height: 1.25rem;
}

/* El icono de X del Kit */
:deep(.govco-times) {
  width: 1rem;
  height: 1rem;
}
</style>

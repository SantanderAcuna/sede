<script setup lang="ts">
/**
 * «¿Cómo fue tu experiencia?» — la valoración pública de una ficha.
 *
 * Reúne las dos que el módulo 03 pide y declara que «ambas aplican»: la valoración rápida
 * FÁCIL/DIFÍCIL (RF-B2-033/034) y la encuesta de 1 a 5 estrellas (RF-B1-094), con un comentario
 * opcional. Envía una sola petición a `POST /tramites/{slug}/valoracion`; al menos una de las dos
 * —el FÁCIL/DIFÍCIL o las estrellas— tiene que ir, igual que exige el servidor, y mientras no haya
 * ninguna el botón de enviar está deshabilitado para no mandar una valoración vacía.
 *
 * Accesible: el FÁCIL/DIFÍCIL son botones con `aria-pressed`; las estrellas, un grupo de radios
 * reales (navegables con el teclado) con etiqueta de texto; el agradecimiento se anuncia en una
 * región viva.
 */
import { cuerpoValoracion, hayValoracion, type ValorExperiencia } from '~/utils/valoracion'

const props = defineProps<{ slug: string }>()

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')

const valor = ref<ValorExperiencia>('')
const estrellas = ref(0)
const comentario = ref('')
const enviando = ref(false)
const enviado = ref(false)
const error = ref('')

const haValorado = computed(() => hayValoracion(valor.value, estrellas.value))

function elegir(nuevo: ValorExperiencia): void {
  valor.value = valor.value === nuevo ? '' : nuevo
}

async function enviar(): Promise<void> {
  if (enviando.value || !haValorado.value) return
  enviando.value = true
  error.value = ''
  try {
    await $fetch(`${urlApi}/tramites/${encodeURIComponent(props.slug)}/valoracion`, {
      method: 'POST',
      body: cuerpoValoracion(valor.value, estrellas.value, comentario.value),
    })
    enviado.value = true
  } catch {
    // No se distingue el motivo al ciudadano: lo que necesita es poder reintentar.
    error.value = 'No se pudo enviar su valoración en este momento. Inténtelo de nuevo.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <section class="bloque valoracion" aria-labelledby="valoracion-titulo">
    <h2 id="valoracion-titulo" class="bloque-titulo">¿Cómo fue tu experiencia?</h2>

    <!-- Región viva: al enviarse, el lector de pantalla anuncia el agradecimiento. -->
    <p v-if="enviado" class="valoracion-gracias" role="status">
      Gracias por su valoración. Nos ayuda a mejorar este trámite.
    </p>

    <form v-else class="valoracion-form" novalidate @submit.prevent="enviar">
      <fieldset class="valoracion-campo">
        <legend class="valoracion-leyenda">¿Fue fácil o difícil de realizar?</legend>
        <div class="valoracion-rapida">
          <button
            type="button"
            class="btn-experiencia"
            :class="{ 'btn-experiencia-activo': valor === 'facil' }"
            :aria-pressed="valor === 'facil'"
            @click="elegir('facil')"
          >
            Fue fácil
          </button>
          <button
            type="button"
            class="btn-experiencia"
            :class="{ 'btn-experiencia-activo': valor === 'dificil' }"
            :aria-pressed="valor === 'dificil'"
            @click="elegir('dificil')"
          >
            Fue difícil
          </button>
        </div>
      </fieldset>

      <fieldset class="valoracion-campo">
        <legend class="valoracion-leyenda">Califique de 1 a 5 estrellas (opcional)</legend>
        <div class="valoracion-estrellas">
          <label v-for="n in 5" :key="n" class="estrella">
            <input v-model.number="estrellas" type="radio" name="estrellas-valoracion" :value="n" class="solo-lectores">
            <span aria-hidden="true">{{ n <= estrellas ? '★' : '☆' }}</span>
            <span class="solo-lectores">{{ n }} {{ n === 1 ? 'estrella' : 'estrellas' }}</span>
          </label>
        </div>
      </fieldset>

      <div class="valoracion-campo">
        <label class="valoracion-leyenda" for="valoracion-comentario">Comentario (opcional)</label>
        <textarea
          id="valoracion-comentario"
          v-model="comentario"
          class="valoracion-comentario"
          rows="3"
          maxlength="1000"
          placeholder="Cuéntenos qué podríamos mejorar."
        />
      </div>

      <p v-if="error !== ''" class="valoracion-error" role="alert">{{ error }}</p>

      <button
        type="submit"
        class="btn btn-govco btn-valoracion"
        :disabled="!haValorado || enviando"
      >
        {{ enviando ? 'Enviando…' : 'Enviar valoración' }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.valoracion-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.valoracion-campo {
  border: 0;
  margin: 0;
  padding: 0;
}

.valoracion-leyenda {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.valoracion-rapida {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.btn-experiencia {
  min-height: 2.75rem;
  padding: 0.5rem 1.25rem;
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  border-radius: 1.563rem;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-cobalt, #0943b5);
  font: inherit;
  cursor: pointer;
}

.btn-experiencia-activo {
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  font-weight: 700;
}

.btn-experiencia:focus-visible,
.estrella:focus-within {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

.valoracion-estrellas {
  display: flex;
  gap: 0.25rem;
}

.estrella {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.75rem;
  min-height: 2.75rem;
  font-size: 1.75rem;
  line-height: 1;
  color: var(--govcolor-golden-brown, #9d7700);
  cursor: pointer;
  border-radius: 0.25rem;
}

.valoracion-comentario {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 0.5rem;
  font: inherit;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.valoracion-comentario:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 1px;
}

.btn-valoracion {
  align-self: flex-start;
  min-height: 2.75rem;
}

.btn-valoracion:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.valoracion-gracias {
  padding: 0.875rem 1.25rem;
  border-left: 0.25rem solid var(--govcolor-cobalt, #0943b5);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
  font-weight: 700;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.valoracion-error {
  color: var(--govcolor-red, #a80521);
  font-weight: 700;
}

.solo-lectores {
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
</style>

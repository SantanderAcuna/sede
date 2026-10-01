<script setup lang="ts">
/**
 * Banner de consentimiento de cookies (RF-B1-008, RF-01-D01, RN-01-D01).
 *
 * Pide la decisión del ciudadano antes de activar cualquier cookie no esencial y
 * la guarda con su fecha y la versión de la política. Ofrece las tres salidas
 * que exige el requisito —aceptar todo, rechazar las opcionales y configurar por
 * categoría— y **ninguna opción viene premarcada**: el silencio no es una
 * aceptación.
 *
 * El estado y la lógica viven en `useConsentimientoCookies`, no aquí, porque hay
 * dos cosas que este componente no puede hacer por sí solo: **revocar** el
 * consentimiento cuando el banner ya está cerrado (el pie ofrece ese punto de
 * entrada) y **bloquear** las cookies no esenciales antes de la aceptación (eso
 * lo consulta el código que las cargue).
 *
 * Se monta desde la disposición sólo en el cliente (`onMounted`), no durante el
 * renderizado: decidir en el `setup` si el banner se ve haría que el servidor
 * pintara la página sin él y el cliente con él, que es un desajuste de
 * hidratación garantizado.
 */
import { onMounted } from 'vue'

import { useConsentimientoCookies } from '~/composables/useConsentimientoCookies'

const { visible, preferencias, inicializar, aceptarTodo, rechazarOpcionales, guardarPreferencias } =
  useConsentimientoCookies()

/** Las categorías opcionales, con lo que hace cada una y para qué sirve. */
const categorias = [
  {
    id: 'analitica' as const,
    nombre: 'Cookies de analítica',
    descripcion:
      'Permiten contar las visitas y conocer de dónde llegan para medir y mejorar el sitio. No identifican a nadie.',
  },
  {
    id: 'preferencias' as const,
    nombre: 'Cookies de preferencias',
    descripcion:
      'Recuerdan ajustes como el contraste o el tamaño de letra que usted haya elegido.',
  },
]

/**
 * Texto de las cookies necesarias. Va aparte porque no se elige: informa de algo
 * que ya ocurre, y presentarlo como una casilla más sugeriría que se puede
 * rechazar.
 */
const obligatoriaInfo =
  'Las cookies estrictamente necesarias permiten que el sitio funcione —recordar su consentimiento, por ejemplo— y no requieren autorización.'

onMounted(inicializar)
</script>

<template>
  <!--
    Vive fuera de `.contenido-filtrable` —lo monta así la disposición—, porque los
    modos de contraste «colores invertidos» y «escala de grises» aplican un
    `filter` a ese envoltorio y un `filter` convierte a su elemento en bloque
    contenedor de los descendientes con `position: fixed`: dentro, el banner
    dejaría de estar anclado a la ventana y se iría con el scroll. Fuera conserva
    además sus colores y su contraste propios, que es lo que se espera de un
    aviso legal.
  -->
  <div
    v-if="visible"
    class="banner-cookies"
    role="dialog"
    aria-labelledby="titulo-banner-cookies"
    aria-describedby="descripcion-banner-cookies"
    aria-modal="false"
  >
    <div class="contenido-banner">
      <div class="texto-banner">
        <h2 id="titulo-banner-cookies" class="titulo-cookies">
          Uso de cookies
        </h2>
        <p id="descripcion-banner-cookies" class="descripcion-cookies">
          La Alcaldía Distrital de Santa Marta utiliza cookies para que el sitio
          funcione y, si usted lo autoriza, para medir cómo se usa. Puede aceptar
          todas, rechazar las opcionales o elegir una por una, y puede cambiar su
          decisión cuando quiera desde el pie de página. Detalles en la
          <NuxtLink to="/politicas/uso-de-cookies">política de uso de cookies</NuxtLink>.
        </p>

        <div class="categorias-cookies">
          <div class="categoria-item">
            <div class="categoria-encabezado">
              <span class="nombre-categoria">
                Cookies estrictamente necesarias
                <span class="etiqueta-obligatoria">Siempre activas</span>
              </span>
            </div>
            <p class="descripcion-categoria">{{ obligatoriaInfo }}</p>
          </div>

          <div v-for="categoria in categorias" :key="categoria.id" class="categoria-item">
            <div class="categoria-encabezado">
              <label :for="`categoria-${categoria.id}`" class="nombre-categoria">
                {{ categoria.nombre }}
              </label>
              <input
                :id="`categoria-${categoria.id}`"
                v-model="preferencias[categoria.id]"
                type="checkbox"
                class="checkbox-categoria"
              >
            </div>
            <p class="descripcion-categoria">{{ categoria.descripcion }}</p>
          </div>
        </div>
      </div>

      <div class="acciones-banner">
        <button type="button" class="btn btn-aceptar-todo" @click="aceptarTodo">
          Aceptar todas
        </button>
        <button type="button" class="btn btn-rechazar" @click="rechazarOpcionales">
          Rechazar opcionales
        </button>
        <button type="button" class="btn btn-configurar" @click="guardarPreferencias">
          Guardar mi elección
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.banner-cookies {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background-color: #ffffff;
  border-top: 0.25rem solid var(--govcolor-cobalt, #0943b5);
  box-shadow: 0 -0.25rem 1rem rgba(0 0 0 / 0.15);
  padding: 1.25rem 1.5rem;
  /* El banner no debe crecer más allá de la pantalla: en un móvil con el texto
     desplegado, la zona de botones tiene que seguir siendo alcanzable. */
  max-height: 100vh;
  overflow-y: auto;
}

.contenido-banner {
  max-width: 75rem;
  margin: 0 auto;
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: flex-start;
}

.texto-banner {
  flex: 1 1 40ch;
  min-width: 0;
}

.titulo-cookies {
  margin: 0 0 0.5rem;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--govcolor-cobalt, #0943b5);
}

.descripcion-cookies {
  margin: 0 0 1rem;
  font-size: 0.9375rem;
  line-height: 1.5;
  color: #333;
}

.categorias-cookies {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.categoria-item {
  border: 0.0625rem solid #e0e0e0;
  border-radius: 0.375rem;
  padding: 0.75rem 1rem;
  background-color: #fafafa;
}

.categoria-encabezado {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.nombre-categoria {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.etiqueta-obligatoria {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  border-radius: 1rem;
  background-color: var(--govcolor-cobalt, #0943b5);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 600;
}

.checkbox-categoria {
  width: 1.25rem;
  height: 1.25rem;
  cursor: pointer;
  accent-color: var(--govcolor-cobalt, #0943b5);
}

.descripcion-categoria {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: #666;
  line-height: 1.4;
}

.acciones-banner {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
  flex: 0 0 auto;
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
  white-space: nowrap;
}

.btn-aceptar-todo {
  background-color: var(--govcolor-cobalt, #0943b5);
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  color: #fff;
}

.btn-aceptar-todo:hover {
  background-color: #072f6e;
  border-color: #072f6e;
}

.btn-rechazar {
  background-color: transparent;
  border: 0.125rem solid #666;
  color: #333;
}

.btn-rechazar:hover {
  background-color: #f0f0f0;
}

.btn-configurar {
  background-color: transparent;
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-cobalt, #0943b5);
}

.btn-configurar:hover {
  background-color: var(--govcolor-solitude, #e5ecf8);
}

@media (max-width: 767px) {
  .banner-cookies {
    padding: 1rem;
  }

  .contenido-banner {
    flex-direction: column;
  }

  .acciones-banner {
    width: 100%;
  }

  .btn {
    flex: 1 1 auto;
  }
}
</style>

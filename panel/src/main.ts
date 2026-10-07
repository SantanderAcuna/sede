/**
 * Arranque del panel.
 *
 * Una sola instancia de Vue, con estado y enrutado. El cliente HTTP no se
 * instala aquí: es un módulo, no un complemento, y así puede usarse desde las
 * pruebas sin levantar la aplicación entera.
 *
 * La hoja de estilos se importa **antes** que los componentes para que las
 * capas de Tailwind (`base`, `components`, `utilities`) queden declaradas en el
 * orden que espera la cascada.
 */
import './assets/styles/main.css'

// Cache-buster: este módulo se ejecuta ANTES de Vue para verificar si la
// versión del bundle en el servidor coincide con la que el navegador tiene
// cacheada. Si no coincide, fuerza una recarga dura (location.reload).
// Sin esto, el navegador puede servir versiones obsoletas con errores de
// iconos "Could not find" que ya están corregidos en el código.
import './plugins/cache-buster'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { FontAwesomeIcon } from './plugins/fontawesome'
import { useSesionStore } from '@/stores/sesion'

// Icono global: la interfaz lo usa como `<FaIcon>` en las plantillas. Registrar
// aquí evita repetir el import en cada componente que dibuja un icono.
const app = createApp(App)

app.component('FaIcon', FontAwesomeIcon)

// Orden crítico para evitar pantallas en blanco y race conditions:
//
//   1. Pinia primero (los stores se crean con app.use)
//   2. Router (registra beforeEach; aún no navega)
//   3. Restaurar la sesión (await)
//   5. Esperar a que el router complete la navegación inicial
//   6. app.mount solo cuando todo lo anterior esté listo
//
// ¿Por qué NO app.mount primero?
//   - createWebHistory() inicia una navegación interna al crearse.
//   - Si app.mount ocurre ANTES de init(), el beforeEach ve inicializado=false,
//     ejecuta init() async, y la sesión nunca se restaura correctamente.
//
// ¿Por qué se restaura la sesión ANTES de app.mount?
//   - Para que cuando el router finalmente resuelva la navegación inicial,
//     la sesión ya esté disponible sincrónicamente.
const pinia = createPinia()
app.use(pinia)
app.use(router)

// Restaurar la sesión ANTES del primer navegación del router.
const sesion = useSesionStore()

// Hacer la inicialización y luego esperar al router antes de montar.
// Esto garantiza que el primer render del panel tiene la sesión resuelta
// y la ruta autorizada.
async function arranque(): Promise<void> {
  // 1) Restaurar la sesión del servidor antes que cualquier navegación.
  await sesion.init()

  // 2) Esperar a que el router complete la navegación inicial.
  //    El router isReady() resuelve cuando se ha resuelto la primera navegación
  //    y los componentes lazy (lazy()) están cargados.
  //    Como el beforeEach hace await sesion.init() cuando inicializado=false,
  //    y ya hicimos init arriba, el beforeEach verá la sesión resuelta.
  await router.isReady()

  // 3) Ahora sí, montar. El primer render ya tiene sesión y ruta correcta.
  app.mount('#app')
}

arranque().catch((error) => {
  // Si la inicialización de la sesión falla catastróficamente,
  // mostrar la app de todas formas — el guardia redirigirá.
  console.error('Error al arrancar la aplicación:', error)
  app.mount('#app')
})
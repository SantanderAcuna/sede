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

// Restaurar la sesión ANTES de montar la app y activar el router.
// Así, cuando el guardia de navegación se ejecuta, la sesión ya está
// disponible de forma síncrona y no hay race conditions.
const pinia = createPinia()
app.use(pinia)
app.use(router)

// Recuperar la sesión del servidor antes de pintar nada. Si el usuario ya tenía
// una cookie de Sanctum válida, la sesión se re-establece sin necesidad de
// volver a iniciar. Si no, el guardia del router redirigirá al login.
const sesion = useSesionStore()
sesion.init().finally(() => {
  app.mount('#app')
})

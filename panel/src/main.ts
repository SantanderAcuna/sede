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

// Icono global: la interfaz lo usa como `<FaIcon>` en las plantillas. Registrar
// aquí evita repetir el import en cada componente que dibuja un icono.
const app = createApp(App)

app.component('FaIcon', FontAwesomeIcon)

app.use(createPinia()).use(router).mount('#app')

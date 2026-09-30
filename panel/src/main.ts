/**
 * Arranque del panel.
 *
 * Una sola instancia de Vue, con estado y enrutado. El cliente HTTP no se
 * instala aquí: es un módulo, no un complemento, y así puede usarse desde las
 * pruebas sin levantar la aplicación entera.
 */
import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

createApp(App).use(createPinia()).use(router).mount('#app')

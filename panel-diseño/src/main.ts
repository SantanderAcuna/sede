import './assets/styles/main.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import piniaPersist from 'pinia-plugin-persistedstate';
import { VueQueryPlugin } from '@tanstack/vue-query';
import Toast from 'vue-toastification';

import App from './App.vue';
import router from './router';
import i18n from './i18n';
import { FontAwesomeIcon } from './plugins/fontawesome';
import { toastOptions } from './plugins/toast';

const app = createApp(App);
const pinia = createPinia();
pinia.use(piniaPersist);

app.use(pinia);
app.use(router);
app.use(i18n);
app.use(Toast, toastOptions);
app.use(VueQueryPlugin, {
  queryClientConfig: {
    defaultOptions: {
      queries: { staleTime: 60_000, retry: 1, refetchOnWindowFocus: false },
    },
  },
});

app.component('FaIcon', FontAwesomeIcon);

app.mount('#app');

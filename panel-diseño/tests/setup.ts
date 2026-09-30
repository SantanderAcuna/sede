import { config } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { createPinia } from 'pinia';
import { FontAwesomeIcon } from '@/plugins/fontawesome';

const i18n = createI18n({ legacy: false, locale: 'es-CO', messages: { 'es-CO': {} } });

config.global.plugins = [createPinia(), i18n];
config.global.components = { FaIcon: FontAwesomeIcon };

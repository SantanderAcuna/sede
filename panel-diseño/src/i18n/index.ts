import { createI18n } from 'vue-i18n';
import esCO from './locales/es-CO.json';

export default createI18n({
  legacy: false,
  locale: 'es-CO',
  fallbackLocale: 'es-CO',
  messages: { 'es-CO': esCO },
  numberFormats: {
    'es-CO': {
      currency: { style: 'currency', currency: 'COP', maximumFractionDigits: 0 },
      decimal:  { style: 'decimal', maximumFractionDigits: 2 },
      percent:  { style: 'percent', maximumFractionDigits: 1 },
    },
  },
  datetimeFormats: {
    'es-CO': {
      short: { year: 'numeric', month: '2-digit', day: '2-digit' },
      long:  { year: 'numeric', month: 'long', day: '2-digit', hour: '2-digit', minute: '2-digit' },
    },
  },
});

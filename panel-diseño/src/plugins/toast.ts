import type { PluginOptions } from 'vue-toastification';
import { POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';

export const toastOptions: PluginOptions = {
  position: POSITION.TOP_RIGHT,
  timeout: 4000,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
  hideProgressBar: false,
  newestOnTop: true,
  maxToasts: 5,
  transition: 'Vue-Toastification__fade',
};

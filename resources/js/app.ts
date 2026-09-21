import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import router from '@/router';
import App from '@/App.vue';
import '../css/app.css';
// Importado al arranque para capturar beforeinstallprompt cuanto antes
// (si se importara solo desde una ruta lazy, el evento ya habria pasado).
import '@/composables/usePwaInstall';

// Registrar el service worker en la raiz (scope '/') para que la PWA sea
// instalable. Solo en produccion: en dev un SW con scope '/' interfiere.
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch((e) => {
      console.log('SW registration failed:', e);
    });
  });
}

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(Toast, {
  position: 'top-right',
  timeout: 3000,
  closeOnClick: true,
  pauseOnHover: true,
});

app.mount('#app');

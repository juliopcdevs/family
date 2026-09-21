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

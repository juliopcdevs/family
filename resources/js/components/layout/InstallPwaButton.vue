<template>
  <div v-if="!isStandalone">
    <button
      @click="handleClick"
      class="w-full flex items-center justify-center gap-2 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 active:scale-[0.99] transition-transform"
    >
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
        <polyline points="7 10 12 15 17 10" />
        <line x1="12" y1="15" x2="12" y2="3" />
      </svg>
      Instalar app
    </button>

    <!-- Instrucciones (iOS o navegadores sin prompt nativo) -->
    <div
      v-if="showHelp"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4"
      @click.self="showHelp = false"
    >
      <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 space-y-4 mb-4 sm:mb-0">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800">{{ isIos ? 'Instalar en iPhone' : 'Instalar la app' }}</h3>
          <button @click="showHelp = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        <ol v-if="isIos" class="space-y-3 text-sm text-gray-600">
          <li class="flex items-start gap-3">
            <span class="shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">1</span>
            <span>
              Pulsa el boton <strong>Compartir</strong>
              <svg class="inline w-4 h-4 -mt-0.5 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M16 5l-1.42 1.42-1.59-1.59V16h-1.98V4.83L9.42 6.42 8 5l4-4 4 4zm4 5v11c0 1.1-.9 2-2 2H6c-1.11 0-2-.9-2-2V10c0-1.11.89-2 2-2h3v2H6v11h12V10h-3V8h3c1.1 0 2 .89 2 2z"/></svg>
              en la barra de Safari.
            </span>
          </li>
          <li class="flex items-start gap-3">
            <span class="shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">2</span>
            <span>Elige <strong>Añadir a pantalla de inicio</strong>.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">3</span>
            <span>Confirma pulsando <strong>Añadir</strong>.</span>
          </li>
        </ol>

        <div v-else class="space-y-3 text-sm text-gray-600">
          <p>Tu navegador aun no ofrece la instalacion automatica. Puedes instalarla manualmente:</p>
          <ul class="space-y-2 list-disc list-inside">
            <li><strong>Android (Chrome):</strong> menu <strong>⋮</strong> → <strong>Instalar aplicacion</strong> / <strong>Añadir a pantalla de inicio</strong>.</li>
            <li><strong>Escritorio (Chrome/Edge):</strong> icono de instalar en la barra de direcciones, o menu <strong>⋮</strong> → <strong>Instalar Family Hub</strong>.</li>
          </ul>
        </div>

        <button @click="showHelp = false" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium">Entendido</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { usePwaInstall } from '@/composables/usePwaInstall';

const { canPromptInstall, isIos, isStandalone, install } = usePwaInstall();
const showHelp = ref(false);

async function handleClick() {
  if (canPromptInstall.value) {
    const result = await install();
    // Si el navegador dijo que no habia prompt, cae al modal de ayuda
    if (result === 'unavailable') showHelp.value = true;
  } else {
    // iOS o navegadores sin beforeinstallprompt: instrucciones manuales
    showHelp.value = true;
  }
}
</script>

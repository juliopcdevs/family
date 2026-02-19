<template>
  <div class="min-h-screen bg-gray-50">
    <div v-if="showAndroidBanner" class="bg-primary text-white px-4 py-2 flex items-center justify-between text-sm">
      <span>Instala la app en tu dispositivo para acceder más rápido</span>
      <div class="flex items-center gap-2 ml-3 shrink-0">
        <button class="px-3 py-1 bg-white text-primary rounded font-medium text-xs" @click="install()">Instalar</button>
        <button class="text-white/70 hover:text-white text-xl leading-none" @click="dismiss()">&times;</button>
      </div>
    </div>

    <div v-if="showIosBanner" class="bg-primary text-white px-4 py-2.5 flex items-center justify-between text-sm gap-3">
      <span>
        Para instalar: pulsa
        <svg class="inline w-4 h-4 -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 5l-1.42 1.42-1.59-1.59V16h-1.98V4.83L9.42 6.42 8 5l4-4 4 4zm4 5v11c0 1.1-.9 2-2 2H6c-1.11 0-2-.9-2-2V10c0-1.11.89-2 2-2h3v2H6v11h12V10h-3V8h3c1.1 0 2 .89 2 2z"/></svg>
        y luego <strong>Añadir a pantalla de inicio</strong>
      </span>
      <button class="text-white/70 hover:text-white text-lg leading-none shrink-0" @click="dismiss()">&times;</button>
    </div>

    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
      <div class="flex justify-around items-center h-16">
        <router-link v-for="item in navItems" :key="item.name" :to="{ name: item.route }"
          class="flex flex-col items-center justify-center flex-1 h-full text-gray-500"
          active-class="!text-primary">
          <span class="text-lg">{{ item.icon }}</span>
          <span class="text-[10px] mt-0.5">{{ item.label }}</span>
        </router-link>
      </div>
    </nav>

    <aside class="hidden md:flex flex-col fixed left-0 top-0 bottom-0 w-60 bg-white border-r border-gray-200 z-40">
      <div class="p-5 border-b border-gray-100">
        <h1 class="text-xl font-bold text-primary">Family Hub</h1>
        <p class="text-xs text-gray-400 mt-1">{{ authStore.user?.name }}</p>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <router-link v-for="item in navItems" :key="item.name" :to="{ name: item.route }"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 text-sm"
          active-class="!bg-primary/10 !text-primary font-medium">
          <span>{{ item.icon }}</span>{{ item.label }}
        </router-link>
      </nav>
      <div class="p-3 border-t border-gray-100">
        <router-link :to="{ name: 'family-settings' }"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 text-sm"
          active-class="!bg-primary/10 !text-primary font-medium">
          <span>&#9881;</span> Ajustes
        </router-link>
        <button @click="handleLogout"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 text-sm w-full">
          <span>&#128682;</span> Cerrar sesion
        </button>
      </div>
    </aside>

    <main class="md:ml-60 pb-20 md:pb-0 min-h-screen">
      <div class="p-4 md:p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { usePwaInstall } from '@/composables/usePwaInstall';

const router = useRouter();
const authStore = useAuthStore();
const { showAndroidBanner, showIosBanner, install, dismiss } = usePwaInstall();

const navItems = [
  { name: 'dashboard', label: 'Inicio', route: 'dashboard', icon: '\u{1F3E0}' },
  { name: 'shopping', label: 'Compra', route: 'shopping', icon: '\u{1F6D2}' },
  { name: 'calendar', label: 'Calendario', route: 'calendar', icon: '\u{1F4C5}' },
  { name: 'tasks', label: 'Tareas', route: 'tasks', icon: '\u2705' },
  { name: 'birthdays', label: 'Cumples', route: 'birthdays', icon: '\u{1F382}' },
];

async function handleLogout() {
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>

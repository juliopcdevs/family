<template>
  <div class="flex flex-col h-dvh md:h-auto md:min-h-screen bg-gray-50 overflow-hidden md:overflow-visible">
    <div v-if="showAndroidBanner" class="shrink-0 bg-primary text-white px-4 py-2 flex items-center justify-between text-sm">
      <span>Instala la app en tu dispositivo para acceder más rápido</span>
      <div class="flex items-center gap-2 ml-3 shrink-0">
        <button class="px-3 py-1 bg-white text-primary rounded font-medium text-xs" @click="install()">Instalar</button>
        <button class="text-white/70 hover:text-white text-xl leading-none" @click="dismiss()">&times;</button>
      </div>
    </div>

    <div v-if="showIosBanner" class="shrink-0 bg-primary text-white px-4 py-2.5 flex items-center justify-between text-sm gap-3">
      <span>
        Para instalar: pulsa
        <svg class="inline w-4 h-4 -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 5l-1.42 1.42-1.59-1.59V16h-1.98V4.83L9.42 6.42 8 5l4-4 4 4zm4 5v11c0 1.1-.9 2-2 2H6c-1.11 0-2-.9-2-2V10c0-1.11.89-2 2-2h3v2H6v11h12V10h-3V8h3c1.1 0 2 .89 2 2z"/></svg>
        y luego <strong>Añadir a pantalla de inicio</strong>
      </span>
      <button class="text-white/70 hover:text-white text-lg leading-none shrink-0" @click="dismiss()">&times;</button>
    </div>

    <header class="md:hidden shrink-0 bg-white border-b border-gray-200 px-4 py-2.5 flex items-center justify-between gap-2.5">
      <div class="flex items-center gap-2.5 min-w-0">
        <svg class="w-7 h-7 text-primary shrink-0" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="8" y="12" width="48" height="40" rx="6" stroke="currentColor" stroke-width="3"/>
          <path d="M20 8v8M44 8v8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          <circle cx="24" cy="34" r="3" fill="currentColor"/>
          <circle cx="40" cy="34" r="3" fill="currentColor"/>
          <path d="M24 44c2 3 6 4 8 4s6-1 8-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <span class="text-base font-bold text-primary truncate">Family Hub</span>
      </div>
      <div class="flex items-center gap-1 shrink-0">
        <router-link :to="{ name: 'family-settings' }" aria-label="Ajustes"
          class="p-2 rounded-lg text-gray-500 hover:bg-gray-50"
          exact-active-class="!text-primary">
          <NavIcon name="settings" />
        </router-link>
        <button @click="handleLogout" aria-label="Cerrar sesion" class="p-2 rounded-lg text-gray-500 hover:bg-gray-50">
          <NavIcon name="logout" />
        </button>
      </div>
    </header>

    <aside class="hidden md:flex flex-col fixed left-0 top-0 bottom-0 w-60 bg-white border-r border-gray-200 z-40">
      <div class="p-5 border-b border-gray-100 flex items-center gap-2.5">
        <svg class="w-7 h-7 text-primary" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="8" y="12" width="48" height="40" rx="6" stroke="currentColor" stroke-width="3"/>
          <path d="M20 8v8M44 8v8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          <circle cx="24" cy="34" r="3" fill="currentColor"/>
          <circle cx="40" cy="34" r="3" fill="currentColor"/>
          <path d="M24 44c2 3 6 4 8 4s6-1 8-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <div>
          <h1 class="text-xl font-bold text-primary leading-tight">Family Hub</h1>
          <p class="text-xs text-gray-400">{{ authStore.user?.name }}</p>
        </div>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <router-link v-for="item in navItems" :key="item.name" :to="{ name: item.route }"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 text-sm"
          exact-active-class="!bg-primary/10 !text-primary font-medium">
          <NavIcon :name="item.name" />{{ item.label }}
        </router-link>
      </nav>
      <div class="p-3 border-t border-gray-100">
        <router-link :to="{ name: 'family-settings' }"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 text-sm"
          exact-active-class="!bg-primary/10 !text-primary font-medium">
          <NavIcon name="settings" /> Ajustes
        </router-link>
        <button @click="handleLogout"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 text-sm w-full">
          <NavIcon name="logout" /> Cerrar sesion
        </button>
      </div>
    </aside>

    <main class="flex-1 overflow-y-auto md:ml-60 md:overflow-visible">
      <div class="p-4 md:p-6">
        <router-view />
      </div>
    </main>

    <nav class="md:hidden shrink-0 bg-white border-t border-gray-200 safe-bottom">
      <div class="flex justify-around items-center h-14">
        <router-link v-for="item in navItems" :key="item.name" :to="{ name: item.route }"
          class="flex flex-col items-center justify-center flex-1 h-full text-gray-400"
          exact-active-class="!text-primary">
          <NavIcon :name="item.name" />
          <span class="text-[10px] mt-0.5">{{ item.label }}</span>
        </router-link>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { usePwaInstall } from '@/composables/usePwaInstall';
import NavIcon from '@/components/layout/NavIcon.vue';

const router = useRouter();
const authStore = useAuthStore();
const { showAndroidBanner, showIosBanner, install, dismiss } = usePwaInstall();

const navItems = [
  { name: 'dashboard', label: 'Inicio', route: 'dashboard' },
  { name: 'shopping', label: 'Compra', route: 'shopping' },
  { name: 'calendar', label: 'Calendario', route: 'calendar' },
  { name: 'tasks', label: 'Tareas', route: 'tasks' },
  { name: 'birthdays', label: 'Cumples', route: 'birthdays' },
];

async function handleLogout() {
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>

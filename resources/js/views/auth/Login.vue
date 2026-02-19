<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <h1 class="text-3xl font-bold text-primary text-center mb-8">Family Hub</h1>
      <form @submit.prevent="handleLogin" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">Iniciar sesion</h2>
        <div v-if="error" class="bg-error/10 text-error text-sm p-3 rounded-lg">{{ error }}</div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contrasena</label>
          <input v-model="password" type="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <button type="submit" :disabled="loading" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50">
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
        <div class="text-center text-sm text-gray-500 space-y-2">
          <router-link to="/forgot-password" class="text-primary hover:underline block">Olvidaste tu contrasena?</router-link>
          <p>No tienes cuenta? <router-link to="/register" class="text-primary hover:underline">Registrate</router-link></p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleLogin() {
  error.value = '';
  loading.value = true;
  try {
    await authStore.login(email.value, password.value);
    router.push({ name: 'dashboard' });
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message || 'Error al iniciar sesion';
  } finally {
    loading.value = false;
  }
}
</script>

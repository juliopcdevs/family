<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <h1 class="text-3xl font-bold text-primary text-center mb-8">Family Hub</h1>
      <form @submit.prevent="handleRegister" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">Crear cuenta</h2>
        <div v-if="error" class="bg-error/10 text-error text-sm p-3 rounded-lg">{{ error }}</div>
        <div v-if="success" class="bg-success/10 text-success text-sm p-3 rounded-lg">{{ success }}</div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
          <input v-model="name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contrasena</label>
          <input v-model="password" type="password" required minlength="8" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contrasena</label>
          <input v-model="passwordConfirmation" type="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <button type="submit" :disabled="loading" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50">
          {{ loading ? 'Registrando...' : 'Registrarse' }}
        </button>
        <p class="text-center text-sm text-gray-500">Ya tienes cuenta? <router-link to="/login" class="text-primary hover:underline">Inicia sesion</router-link></p>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

async function handleRegister() {
  error.value = '';
  success.value = '';
  loading.value = true;
  try {
    await authStore.register(name.value, email.value, password.value, passwordConfirmation.value);
    success.value = 'Cuenta creada. Revisa tu email para verificarla.';
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
    const errors = err.response?.data?.errors;
    error.value = errors ? Object.values(errors).flat().join('. ') : (err.response?.data?.message || 'Error al registrar');
  } finally {
    loading.value = false;
  }
}
</script>

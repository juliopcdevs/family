<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">Recuperar contrasena</h2>
        <div v-if="error" class="bg-error/10 text-error text-sm p-3 rounded-lg">{{ error }}</div>
        <div v-if="success" class="bg-success/10 text-success text-sm p-3 rounded-lg">{{ success }}</div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <button type="submit" :disabled="loading" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50">
          {{ loading ? 'Enviando...' : 'Enviar enlace' }}
        </button>
        <router-link to="/login" class="block text-center text-sm text-primary hover:underline">Volver al login</router-link>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import authService from '@/services/auth.service';

const email = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

async function handleSubmit() {
  error.value = '';
  success.value = '';
  loading.value = true;
  try {
    await authService.forgotPassword(email.value);
    success.value = 'Enlace de recuperacion enviado a tu email.';
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message || 'Error al enviar enlace';
  } finally { loading.value = false; }
}
</script>

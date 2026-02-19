<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-sm p-6 text-center">
      <div v-if="status === 'verified'" class="space-y-4">
        <div class="text-5xl">&#10004;</div>
        <h2 class="text-xl font-semibold text-gray-800">Email verificado</h2>
        <router-link to="/login" class="inline-block bg-primary text-white px-6 py-2.5 rounded-lg font-medium">Iniciar sesion</router-link>
      </div>
      <div v-else-if="status === 'invalid'" class="space-y-4">
        <div class="text-5xl">&#10060;</div>
        <h2 class="text-xl font-semibold text-gray-800">Enlace invalido</h2>
        <p class="text-gray-500 text-sm">El enlace de verificacion no es valido o ha expirado.</p>
      </div>
      <div v-else class="space-y-4">
        <div class="text-5xl">&#9993;</div>
        <h2 class="text-xl font-semibold text-gray-800">Verifica tu email</h2>
        <p class="text-gray-500 text-sm">Hemos enviado un enlace de verificacion a tu email.</p>
        <button @click="resend" :disabled="resending" class="text-primary hover:underline text-sm">
          {{ resending ? 'Reenviando...' : 'Reenviar email' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import authService from '@/services/auth.service';

const route = useRoute();
const status = ref(route.query.status as string || '');
const resending = ref(false);

async function resend() {
  resending.value = true;
  try { await authService.sendVerificationEmail(); } catch { /* ignore */ }
  finally { resending.value = false; }
}
</script>

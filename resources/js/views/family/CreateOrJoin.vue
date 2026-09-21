<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm space-y-6">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-primary">Family Hub</h1>
        <p class="text-gray-500 mt-2">Crea o unete a una familia</p>
      </div>
      <div v-if="error" class="bg-error/10 text-error text-sm p-3 rounded-lg">{{ error }}</div>

      <div v-if="createdCode" class="bg-white rounded-xl shadow-sm p-6 text-center space-y-4">
        <div class="text-5xl">&#127881;</div>
        <h2 class="text-xl font-semibold">Familia creada</h2>
        <p class="text-gray-500 text-sm">Comparte este codigo con tu familia:</p>
        <div class="text-3xl font-mono font-bold text-primary tracking-wider">{{ createdCode }}</div>
        <a :href="whatsappLink" target="_blank" class="inline-block bg-[#25D366] text-white px-6 py-2.5 rounded-lg font-medium">Compartir por WhatsApp</a>
        <button @click="goToDashboard" class="block w-full text-primary hover:underline text-sm">Ir al inicio</button>
      </div>

      <template v-else>
        <form @submit.prevent="handleCreate" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-800">Crear familia</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la familia</label>
            <input v-model="familyName" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" placeholder="Ej: Familia Garcia" />
          </div>
          <button type="submit" :disabled="loading" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50">
            {{ loading ? 'Creando...' : 'Crear familia' }}
          </button>
        </form>
        <div class="text-center text-gray-400 text-sm">o</div>
        <form @submit.prevent="handleJoin" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-800">Unirse a familia</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Codigo de familia</label>
            <input
              :value="joinCode"
              @input="onJoinCodeInput"
              type="text"
              required
              autocapitalize="characters"
              autocomplete="off"
              autocorrect="off"
              spellcheck="false"
              inputmode="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none text-center text-xl tracking-widest font-mono uppercase"
              placeholder="XXXXXXXX"
            />
          </div>
          <button type="submit" :disabled="loading" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50">
            {{ loading ? 'Uniendose...' : 'Unirse' }}
          </button>
        </form>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useFamilyStore } from '@/stores/family';

const router = useRouter();
const familyStore = useFamilyStore();
const familyName = ref('');
const joinCode = ref('');
const error = ref('');
const loading = ref(false);
const createdCode = ref('');

// Sanea el codigo al escribir/pegar: mayusculas y solo [A-Z0-9], max 8.
// Sin maxlength en el input porque iOS Safari descarta el pegado completo si
// el texto pegado supera maxlength (p.ej. al copiar el codigo con espacios).
function onJoinCodeInput(e: Event) {
  const el = e.target as HTMLInputElement;
  const clean = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
  joinCode.value = clean;
  if (el.value !== clean) el.value = clean;
}

const whatsappLink = computed(() =>
  `https://wa.me/?text=${encodeURIComponent(`Unete a nuestra familia en Family Hub con el codigo: ${createdCode.value}`)}`
);

async function handleCreate() {
  error.value = '';
  loading.value = true;
  try {
    const result = await familyStore.createFamily(familyName.value);
    createdCode.value = result.code;
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message || 'Error al crear familia';
  } finally { loading.value = false; }
}

async function handleJoin() {
  error.value = '';
  loading.value = true;
  try {
    await familyStore.joinFamily(joinCode.value);
    router.push({ name: 'dashboard' });
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message || 'Codigo invalido';
  } finally { loading.value = false; }
}

function goToDashboard() { router.push({ name: 'dashboard' }); }
</script>

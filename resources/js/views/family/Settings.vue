<template>
  <div class="max-w-lg mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Ajustes de familia</h1>
    <div v-if="family" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
      <div>
        <p class="text-sm text-gray-500">Familia</p>
        <p class="text-lg font-semibold">{{ family.name }}</p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Codigo para invitar</p>
        <div class="flex items-center gap-3 mt-1">
          <span class="text-2xl font-mono font-bold text-primary tracking-wider">{{ family.code }}</span>
          <button @click="copyCode" class="text-sm text-primary hover:underline">Copiar</button>
        </div>
      </div>
      <a :href="whatsappLink" target="_blank" class="inline-block bg-[#25D366] text-white px-4 py-2 rounded-lg text-sm font-medium">Compartir por WhatsApp</a>
      <div v-if="family.members" class="pt-4 border-t border-gray-100">
        <p class="text-sm text-gray-500 mb-2">Miembros ({{ family.members.length }})</p>
        <ul class="space-y-2">
          <li v-for="member in family.members" :key="member._id" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm font-medium">{{ member.name.charAt(0).toUpperCase() }}</div>
            <span class="text-sm">{{ member.name }}</span>
          </li>
        </ul>
      </div>
    </div>
    <button @click="handleLeave" class="text-error hover:underline text-sm">Salir de la familia</button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useFamilyStore } from '@/stores/family';
import type { Family } from '@/types';

const router = useRouter();
const familyStore = useFamilyStore();
const family = ref<Family | null>(null);

const whatsappLink = computed(() =>
  family.value ? `https://wa.me/?text=${encodeURIComponent(`Unete a nuestra familia en Family Hub con el codigo: ${family.value.code}`)}` : ''
);

onMounted(async () => {
  await familyStore.fetchFamily();
  family.value = familyStore.family;
});

function copyCode() { if (family.value) navigator.clipboard.writeText(family.value.code); }

async function handleLeave() {
  if (!confirm('Seguro que quieres salir de la familia?')) return;
  await familyStore.leaveFamily();
  router.push({ name: 'family-setup' });
}
</script>

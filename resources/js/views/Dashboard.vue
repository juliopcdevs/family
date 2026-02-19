<template>
  <div v-if="loading" class="flex justify-center py-12">
    <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
  </div>
  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Lista de compra</h3>
        <router-link :to="{ name: 'shopping' }" class="text-sm text-primary hover:underline">Ver todo</router-link>
      </div>
      <ul v-if="data.shopping.length" class="space-y-2">
        <li v-for="item in data.shopping" :key="item._id" class="flex items-center gap-2 text-sm">
          <span class="w-2 h-2 rounded-full" :class="item.is_in_cart ? 'bg-success' : 'bg-gray-300'"></span>
          <span>{{ item.item_name }}</span>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Sin items en la lista</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Proximos eventos</h3>
        <router-link :to="{ name: 'calendar' }" class="text-sm text-primary hover:underline">Ver todo</router-link>
      </div>
      <ul v-if="data.events.length" class="space-y-2">
        <li v-for="event in data.events" :key="event._id" class="flex items-center justify-between text-sm">
          <span>{{ event.title }}</span>
          <span class="text-gray-400">{{ formatDate(event.date) }}</span>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Sin eventos proximos</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Tareas pendientes</h3>
        <router-link :to="{ name: 'tasks' }" class="text-sm text-primary hover:underline">Ver todo</router-link>
      </div>
      <ul v-if="data.tasks.length" class="space-y-2">
        <li v-for="task in data.tasks" :key="task._id" class="flex items-center gap-2 text-sm">
          <span class="w-4 h-4 border-2 border-gray-300 rounded"></span>
          <span>{{ task.title }}</span>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Sin tareas pendientes</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Cumpleanos</h3>
        <router-link :to="{ name: 'birthdays' }" class="text-sm text-primary hover:underline">Ver todo</router-link>
      </div>
      <ul v-if="data.birthdays.length" class="space-y-2">
        <li v-for="b in data.birthdays" :key="b._id" class="flex items-center justify-between text-sm">
          <span>{{ b.person_name }}</span>
          <span class="text-gray-400">en {{ b.days_until_birthday }} dias</span>
        </li>
      </ul>
      <p v-else class="text-sm text-gray-400">Sin cumpleanos registrados</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import dashboardService from '@/services/dashboard.service';
import type { DashboardData } from '@/types';

const loading = ref(true);
const data = ref<DashboardData>({ shopping: [], events: [], tasks: [], birthdays: [] });

onMounted(async () => {
  try {
    data.value = await dashboardService.getDashboard();
  } finally {
    loading.value = false;
  }
});

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
}
</script>

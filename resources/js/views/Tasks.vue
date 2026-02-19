<template>
  <div class="max-w-lg mx-auto space-y-6">
    <form @submit.prevent="addTask" class="flex gap-3">
      <input
        v-model="newTitle"
        type="text"
        placeholder="Nueva tarea..."
        required
        class="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none"
      />
      <button type="submit" class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-primary/90 flex-shrink-0">
        Agregar
      </button>
    </form>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
    </div>
    <template v-else>
      <div>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Pendientes ({{ pending.length }})</h2>
        <div v-if="pending.length" class="space-y-2">
          <div
            v-for="task in pending"
            :key="task._id"
            class="bg-white rounded-lg shadow-sm px-4 py-3 flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <button
                @click="complete(task._id)"
                class="w-5 h-5 border-2 border-gray-300 rounded hover:border-primary transition-colors flex-shrink-0"
              ></button>
              <span class="text-sm">{{ task.title }}</span>
            </div>
            <button @click="remove(task._id)" class="text-gray-400 hover:text-error text-lg">&times;</button>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">Sin tareas pendientes</p>
      </div>

      <div v-if="completed.length">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Completadas ({{ completed.length }})</h2>
        <div class="space-y-2">
          <div
            v-for="task in completed"
            :key="task._id"
            class="bg-white rounded-lg shadow-sm px-4 py-3 flex items-center justify-between opacity-60"
          >
            <div class="flex items-center gap-3">
              <span class="w-5 h-5 bg-success rounded flex items-center justify-center text-white text-xs flex-shrink-0">&#10003;</span>
              <span class="text-sm line-through">{{ task.title }}</span>
            </div>
            <button @click="remove(task._id)" class="text-gray-400 hover:text-error text-lg">&times;</button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import taskService from '@/services/task.service';
import { useToast } from '@/composables/useToast';
import type { Task } from '@/types';

const { success, error: showError } = useToast();
const loading = ref(true);
const newTitle = ref('');
const pending = ref<Task[]>([]);
const completed = ref<Task[]>([]);

onMounted(async () => {
  try {
    const data = await taskService.getTasks();
    pending.value = data.pending;
    completed.value = data.completed;
  } catch { showError('Error al cargar tareas'); }
  finally { loading.value = false; }
});

async function addTask() {
  if (!newTitle.value.trim()) return;
  try {
    const task = await taskService.createTask(newTitle.value);
    pending.value.unshift(task);
    newTitle.value = '';
    success('Tarea creada');
  } catch { showError('Error al crear tarea'); }
}

async function complete(id: string) {
  try {
    const task = await taskService.completeTask(id);
    pending.value = pending.value.filter(t => t._id !== id);
    completed.value.unshift(task);
    success('Tarea completada');
  } catch { showError('Error al completar tarea'); }
}

async function remove(id: string) {
  try {
    await taskService.deleteTask(id);
    pending.value = pending.value.filter(t => t._id !== id);
    completed.value = completed.value.filter(t => t._id !== id);
  } catch { showError('Error al eliminar tarea'); }
}
</script>

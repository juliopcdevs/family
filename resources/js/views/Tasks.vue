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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import taskService from '@/services/task.service';
import type { Task } from '@/types';

const newTitle = ref('');
const pending = ref<Task[]>([]);
const completed = ref<Task[]>([]);

onMounted(async () => {
  const data = await taskService.getTasks();
  pending.value = data.pending;
  completed.value = data.completed;
});

async function addTask() {
  if (!newTitle.value.trim()) return;
  const task = await taskService.createTask(newTitle.value);
  pending.value.unshift(task);
  newTitle.value = '';
}

async function complete(id: string) {
  const task = await taskService.completeTask(id);
  pending.value = pending.value.filter(t => t._id !== id);
  completed.value.unshift(task);
}

async function remove(id: string) {
  await taskService.deleteTask(id);
  pending.value = pending.value.filter(t => t._id !== id);
  completed.value = completed.value.filter(t => t._id !== id);
}
</script>

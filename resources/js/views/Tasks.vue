<template>
  <div class="max-w-lg mx-auto space-y-6">
    <form @submit.prevent="addTask" class="flex flex-col sm:flex-row gap-2 sm:gap-3">
      <input
        v-model="newTitle"
        type="text"
        placeholder="Nueva tarea..."
        required
        class="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none"
      />
      <button type="submit" class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-primary/90 shrink-0">
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
            :key="task.id"
            class="bg-white rounded-lg shadow-sm px-4 py-3 flex items-center justify-between"
            :style="{ borderLeft: `4px solid ${creatorColor(task.created_by)}` }"
          >
            <div class="flex items-center gap-3 min-w-0">
              <button
                @click="complete(task.id)"
                class="w-5 h-5 border-2 border-gray-300 rounded hover:border-primary transition-colors flex-shrink-0"
              ></button>
              <div class="min-w-0">
                <span class="text-sm block">{{ task.title }}</span>
                <span
                  class="text-[11px] font-medium"
                  :style="{ color: creatorColor(task.created_by) }"
                >{{ task.creator_name }}</span>
              </div>
            </div>
            <button @click="remove(task.id)" class="text-gray-400 hover:text-error text-lg shrink-0">&times;</button>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">Sin tareas pendientes</p>
      </div>

      <div>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Completadas ({{ completed.length }})</h2>
        <div v-if="completed.length" class="space-y-2">
          <div
            v-for="task in completed"
            :key="task.id"
            class="bg-white rounded-lg shadow-sm px-4 py-3 flex items-center justify-between opacity-60"
            :style="{ borderLeft: `4px solid ${creatorColor(task.created_by)}` }"
          >
            <div class="flex items-center gap-3 min-w-0">
              <button
                @click="uncomplete(task.id)"
                class="w-5 h-5 bg-success rounded flex items-center justify-center text-white text-xs shrink-0 hover:bg-success/70 transition-colors"
              >&#10003;</button>
              <div class="min-w-0">
                <span class="text-sm line-through block">{{ task.title }}</span>
                <span
                  class="text-[11px] font-medium"
                  :style="{ color: creatorColor(task.created_by) }"
                >{{ task.creator_name }}</span>
              </div>
            </div>
            <button @click="remove(task.id)" class="text-gray-400 hover:text-error text-lg shrink-0">&times;</button>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No hay tareas completadas</p>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import taskService from '@/services/task.service';
import type { Task } from '@/types';

const loading = ref(true);
const newTitle = ref('');
const pending = ref<Task[]>([]);
const completed = ref<Task[]>([]);

const userColors = [
  '#6366F1', '#EC4899', '#F59E0B', '#10B981',
  '#8B5CF6', '#EF4444', '#14B8A6', '#F97316',
];
const colorMap: Record<string, string> = {};
let colorIndex = 0;

function creatorColor(userId: string): string {
  if (!colorMap[userId]) {
    colorMap[userId] = userColors[colorIndex % userColors.length];
    colorIndex++;
  }
  return colorMap[userId];
}

onMounted(async () => {
  try {
    const data = await taskService.getTasks();
    pending.value = data.pending;
    completed.value = data.completed;
  } finally { loading.value = false; }
});

async function addTask() {
  if (!newTitle.value.trim()) return;
  const task = await taskService.createTask(newTitle.value);
  pending.value.unshift(task);
  newTitle.value = '';
}

async function complete(id: string) {
  const task = await taskService.completeTask(id);
  pending.value = pending.value.filter(t => t.id !== id);
  completed.value.unshift(task);
}

async function uncomplete(id: string) {
  const task = await taskService.completeTask(id);
  completed.value = completed.value.filter(t => t.id !== id);
  pending.value.unshift(task);
}

async function remove(id: string) {
  await taskService.deleteTask(id);
  pending.value = pending.value.filter(t => t.id !== id);
  completed.value = completed.value.filter(t => t.id !== id);
}
</script>

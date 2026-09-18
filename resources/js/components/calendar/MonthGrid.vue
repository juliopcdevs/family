<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="grid grid-cols-7 text-center text-xs font-medium text-gray-500 border-b border-gray-100">
      <div v-for="d in weekDays" :key="d" class="py-2">{{ d }}</div>
    </div>
    <div class="grid grid-cols-7">
      <div
        v-for="(day, i) in days"
        :key="i"
        class="h-[60px] md:h-[80px] border-b border-r border-gray-100 p-1 overflow-hidden"
        :class="[
          !day.current ? 'bg-gray-50/50' : 'cursor-pointer hover:bg-gray-50',
          day.date === todayStr ? 'bg-primary/5' : '',
        ]"
        @click="day.current && day.date && $emit('dayClick', day)"
      >
        <span
          v-if="day.num"
          class="text-xs font-medium inline-flex items-center justify-center w-5 h-5 rounded-full"
          :class="[
            day.current ? 'text-gray-800' : 'text-gray-300',
            day.date === todayStr ? 'bg-primary text-white' : '',
          ]"
        >{{ day.num }}</span>
        <div v-for="ev in day.events.slice(0, 2)" :key="ev.id" class="mt-0.5">
          <span class="block text-[10px] md:text-xs bg-primary/10 text-primary rounded px-1 py-0.5 truncate">
            {{ ev.title }}
          </span>
        </div>
        <span v-if="day.events.length > 2" class="text-[10px] text-gray-400 mt-0.5 block">
          +{{ day.events.length - 2 }} mas
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CalendarEvent } from '@/types';

interface CalDay {
  num: number | null;
  date: string | null;
  current: boolean;
  events: CalendarEvent[];
}

defineProps<{
  days: CalDay[];
  todayStr: string;
}>();

defineEmits<{
  dayClick: [day: CalDay];
}>();

const weekDays = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
</script>

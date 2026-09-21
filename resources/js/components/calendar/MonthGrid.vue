<template>
  <div
    ref="grid"
    class="flex-1 grid grid-cols-7 grid-rows-6 gap-px bg-gray-100 rounded-lg overflow-hidden select-none touch-pan-y"
  >
    <div
      v-for="(day, i) in days"
      :key="i"
      class="bg-white flex flex-col px-px pt-px overflow-hidden"
      :class="day.current ? '' : 'bg-gray-50/70'"
      @click="onClick(day)"
      @touchstart.passive="onStart($event, day)"
      @touchmove.passive="onMove"
      @touchend="onEnd($event, day)"
    >
      <span
        class="text-[11px] leading-none w-5 h-5 flex items-center justify-center rounded-full self-start shrink-0"
        :class="[
          day.current ? 'text-gray-700' : 'text-gray-300',
          day.date === todayStr ? 'bg-primary text-white font-semibold' : '',
        ]"
      >{{ day.num }}</span>
      <div class="flex-1 min-h-0 mt-0.5 space-y-px overflow-hidden">
        <span
          v-for="ev in visibleEvents(day)"
          :key="ev.id"
          class="block text-[10px] leading-[13px] bg-primary/20 text-gray-900 rounded-sm px-0.5 whitespace-nowrap overflow-hidden text-clip"
        >{{ ev.title }}</span>
        <span v-if="hiddenCount(day) > 0" class="block text-[9px] leading-none text-gray-500 px-0.5 pt-px">
          +{{ hiddenCount(day) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import type { CalendarEvent } from '@/types';

interface CalDay {
  num: number | null;
  date: string | null;
  current: boolean;
  events: CalendarEvent[];
}

const props = defineProps<{ days: CalDay[]; todayStr: string }>();
const emit = defineEmits<{
  dayTap: [day: CalDay];
  dayLongPress: [day: CalDay];
  swipeLeft: [];
  swipeRight: [];
}>();

const grid = ref<HTMLElement>();
const maxChips = ref(3);
let ro: ResizeObserver | null = null;

// Calcula cuantos eventos caben por celda segun la altura real de la fila.
function measure() {
  if (!grid.value) return;
  const rowH = grid.value.clientHeight / 6;
  const DAY_NUM = 22; // numero del dia + margen
  const CHIP = 14;    // alto aproximado de cada chip
  maxChips.value = Math.max(1, Math.floor((rowH - DAY_NUM) / CHIP));
}

function visibleEvents(day: CalDay): CalendarEvent[] {
  if (day.events.length <= maxChips.value) return day.events;
  return day.events.slice(0, Math.max(1, maxChips.value - 1));
}
function hiddenCount(day: CalDay): number {
  return day.events.length - visibleEvents(day).length;
}

// Gestos: tap (abrir dia), pulsacion larga (anadir), swipe horizontal (cambiar mes)
let sx = 0, sy = 0, dx = 0, dy = 0, moved = false, longFired = false;
let longTimer: ReturnType<typeof setTimeout> | null = null;
let lastTouch = 0;

function onStart(e: TouchEvent, day: CalDay) {
  const t = e.touches[0];
  sx = t.clientX; sy = t.clientY; dx = 0; dy = 0; moved = false; longFired = false;
  if (longTimer) clearTimeout(longTimer);
  longTimer = setTimeout(() => {
    if (!moved) { longFired = true; emit('dayLongPress', day); }
  }, 450);
}
function onMove(e: TouchEvent) {
  const t = e.touches[0];
  dx = t.clientX - sx; dy = t.clientY - sy;
  if (Math.abs(dx) > 10 || Math.abs(dy) > 10) {
    moved = true;
    if (longTimer) { clearTimeout(longTimer); longTimer = null; }
  }
}
function onEnd(e: TouchEvent, day: CalDay) {
  if (longTimer) { clearTimeout(longTimer); longTimer = null; }
  lastTouch = Date.now();
  // Evita el click sintetico posterior (que cerraria el modal recien abierto)
  if (e.cancelable) e.preventDefault();
  if (longFired) return;
  if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) {
    dx < 0 ? emit('swipeLeft') : emit('swipeRight');
    return;
  }
  if (!moved) emit('dayTap', day);
}
function onClick(day: CalDay) {
  // Ignora el click sintetico posterior a un toque
  if (Date.now() - lastTouch < 600) return;
  emit('dayTap', day);
}

onMounted(() => {
  measure();
  ro = new ResizeObserver(measure);
  if (grid.value) ro.observe(grid.value);
});
onBeforeUnmount(() => ro?.disconnect());
</script>

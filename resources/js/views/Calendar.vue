<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <button @click="prevMonth" class="text-gray-600 hover:text-gray-800 p-2">&#9664;</button>
      <h2 class="text-lg font-semibold text-gray-800">{{ monthName }} {{ year }}</h2>
      <button @click="nextMonth" class="text-gray-600 hover:text-gray-800 p-2">&#9654;</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="grid grid-cols-7 text-center text-xs font-medium text-gray-500 border-b border-gray-100">
        <div v-for="d in weekDays" :key="d" class="py-2">{{ d }}</div>
      </div>
      <div class="grid grid-cols-7">
        <div
          v-for="(day, i) in calendarDays"
          :key="i"
          class="min-h-[60px] md:min-h-[80px] border-b border-r border-gray-100 p-1 cursor-pointer hover:bg-gray-50"
          :class="{ 'bg-gray-50/50': !day.current }"
          @click="day.date && openModal(day.date)"
        >
          <span
            v-if="day.num"
            class="text-xs font-medium"
            :class="day.current ? 'text-gray-800' : 'text-gray-300'"
          >{{ day.num }}</span>
          <div v-for="ev in day.events" :key="ev._id" class="mt-0.5">
            <span
              class="block text-[10px] md:text-xs bg-primary/10 text-primary rounded px-1 py-0.5 truncate cursor-pointer"
              @click.stop="editEvent(ev, day.date!)"
            >{{ ev.title }}</span>
          </div>
        </div>
      </div>
    </div>

    <button @click="openModal(todayStr)" class="w-full bg-primary text-white py-3 rounded-lg font-medium hover:bg-primary/90">
      Nuevo evento
    </button>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4" @click.self="closeModal">
      <div class="bg-white rounded-xl p-6 w-full max-w-sm space-y-4">
        <h3 class="text-lg font-semibold">{{ editingEvent ? 'Editar evento' : 'Nuevo evento' }}</h3>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Titulo</label>
          <input v-model="modalTitle" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <input v-model="modalDate" type="date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <div class="flex gap-3">
          <button @click="saveEvent" class="flex-1 bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90">
            Guardar
          </button>
          <button v-if="editingEvent" @click="deleteEvent" class="px-4 py-2.5 text-error border border-error/30 rounded-lg hover:bg-error/5">
            Eliminar
          </button>
        </div>
        <button @click="closeModal" class="w-full text-sm text-gray-500 hover:underline">Cancelar</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import calendarService from '@/services/calendar.service';
import type { CalendarEvent } from '@/types';

const now = new Date();
const month = ref(now.getMonth() + 1);
const year = ref(now.getFullYear());
const events = ref<CalendarEvent[]>([]);
const showModal = ref(false);
const modalTitle = ref('');
const modalDate = ref('');
const editingEvent = ref<CalendarEvent | null>(null);

const weekDays = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const monthName = computed(() => monthNames[month.value - 1]);

const todayStr = computed(() => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});

interface CalDay {
  num: number | null;
  date: string | null;
  current: boolean;
  events: CalendarEvent[];
}

const calendarDays = computed<CalDay[]>(() => {
  const firstDay = new Date(year.value, month.value - 1, 1);
  const lastDay = new Date(year.value, month.value, 0);
  const startWeekday = (firstDay.getDay() + 6) % 7;
  const days: CalDay[] = [];

  for (let i = 0; i < startWeekday; i++) {
    const d = new Date(year.value, month.value - 1, -startWeekday + i + 1);
    days.push({ num: d.getDate(), date: null, current: false, events: [] });
  }

  for (let d = 1; d <= lastDay.getDate(); d++) {
    const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const dayEvents = events.value.filter(e => e.date.startsWith(dateStr));
    days.push({ num: d, date: dateStr, current: true, events: dayEvents });
  }

  const remaining = 7 - (days.length % 7);
  if (remaining < 7) {
    for (let i = 1; i <= remaining; i++) {
      days.push({ num: i, date: null, current: false, events: [] });
    }
  }

  return days;
});

async function fetchEvents() {
  events.value = await calendarService.getEvents(month.value, year.value);
}

onMounted(fetchEvents);
watch([month, year], fetchEvents);

function prevMonth() {
  if (month.value === 1) { month.value = 12; year.value--; }
  else { month.value--; }
}

function nextMonth() {
  if (month.value === 12) { month.value = 1; year.value++; }
  else { month.value++; }
}

function openModal(date: string) {
  editingEvent.value = null;
  modalTitle.value = '';
  modalDate.value = date;
  showModal.value = true;
}

function editEvent(ev: CalendarEvent, date: string) {
  editingEvent.value = ev;
  modalTitle.value = ev.title;
  modalDate.value = date;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingEvent.value = null;
}

async function saveEvent() {
  if (!modalTitle.value.trim()) return;
  if (editingEvent.value) {
    const updated = await calendarService.updateEvent(editingEvent.value._id, modalTitle.value, modalDate.value);
    const idx = events.value.findIndex(e => e._id === updated._id);
    if (idx >= 0) events.value[idx] = updated;
  } else {
    const created = await calendarService.createEvent(modalTitle.value, modalDate.value);
    events.value.push(created);
  }
  closeModal();
}

async function deleteEvent() {
  if (!editingEvent.value) return;
  await calendarService.deleteEvent(editingEvent.value._id);
  events.value = events.value.filter(e => e._id !== editingEvent.value!._id);
  closeModal();
}
</script>

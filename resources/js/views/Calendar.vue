<template>
  <div class="cal-fill flex flex-col max-w-3xl mx-auto">
    <!-- Barra superior: mes (desplegable) + año (desplegable sutil) + Hoy -->
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-1">
        <button @click="prevMonth" aria-label="Mes anterior" class="p-1.5 text-gray-400 hover:text-gray-700">&#9664;</button>

        <div class="relative">
          <select
            v-model.number="month"
            class="appearance-none bg-transparent text-lg font-bold text-gray-800 pr-5 pl-1 py-0.5 outline-none cursor-pointer"
          >
            <option v-for="(name, i) in monthNames" :key="i" :value="i + 1">{{ name }}</option>
          </select>
          <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]">&#9660;</span>
        </div>

        <div class="relative">
          <select
            v-model.number="year"
            class="appearance-none bg-transparent text-sm text-gray-400 pr-4 pl-1 py-0.5 outline-none cursor-pointer"
          >
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
          <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-gray-300 text-[9px]">&#9660;</span>
        </div>

        <button @click="nextMonth" aria-label="Mes siguiente" class="p-1.5 text-gray-400 hover:text-gray-700">&#9654;</button>
      </div>

      <button
        v-if="!isCurrentMonth"
        @click="goToday"
        class="text-xs font-medium text-primary border border-primary/30 rounded-full px-3 py-1"
      >Hoy</button>
    </div>

    <!-- Cabecera de dias de la semana -->
    <div class="grid grid-cols-7 text-center text-[11px] font-medium text-gray-400 mb-1 shrink-0">
      <div v-for="d in weekDays" :key="d" class="py-0.5">{{ d }}</div>
    </div>

    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
    </div>

    <MonthGrid
      v-else
      :days="days"
      :today-str="todayStr"
      @day-tap="openDayModal"
      @day-long-press="openAdd"
      @swipe-left="nextMonth"
      @swipe-right="prevMonth"
    />

    <!-- Modal del dia -->
    <div v-if="selectedDate" class="fixed inset-0 bg-black/40 flex items-end sm:items-center justify-center z-[60] sm:px-4" @click.self="closeDayModal">
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-sm max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
          <h3 class="text-base font-semibold text-gray-800">{{ selectedDayLabel }}</h3>
          <button @click="closeDayModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-2">
          <div v-for="ev in selectedDayEvents" :key="ev.id" class="bg-gray-50 rounded-lg px-3 py-2.5">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <span v-if="ev.time" class="text-xs font-semibold text-primary">{{ ev.time }}</span>
                <span class="text-sm block">{{ ev.title }}</span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button @click="startEdit(ev)" class="text-gray-400 hover:text-primary text-sm">Editar</button>
                <button @click="deleteEvent(ev.id)" class="text-gray-400 hover:text-error text-sm">Eliminar</button>
              </div>
            </div>
          </div>
          <p v-if="!selectedDayEvents.length && !showForm" class="text-sm text-gray-400 text-center py-4">
            No hay eventos este dia
          </p>
        </div>

        <div class="px-4 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100">
          <form v-if="showForm" @submit.prevent="saveEvent" class="space-y-3">
            <input
              ref="formInput"
              v-model="formTitle"
              type="text"
              placeholder="Titulo del evento..."
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none"
            />
            <input
              v-model="formTime"
              type="time"
              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none"
            />
            <div class="flex gap-2">
              <button type="submit" :disabled="saving" class="flex-1 bg-primary text-white py-2.5 rounded-lg text-sm font-medium disabled:opacity-50">
                {{ saving ? 'Guardando...' : editingEvent ? 'Actualizar' : 'Guardar' }}
              </button>
              <button type="button" @click="cancelForm" class="px-4 py-2.5 text-sm text-gray-500 border border-gray-200 rounded-lg">
                Cancelar
              </button>
            </div>
          </form>
          <button v-else @click="startAdd" class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-medium">
            Añadir evento
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import calendarService from '@/services/calendar.service';
import MonthGrid from '@/components/calendar/MonthGrid.vue';
import type { CalendarEvent } from '@/types';

interface CalDay { num: number | null; date: string | null; current: boolean; events: CalendarEvent[]; }

const now = new Date();
const year = ref(now.getFullYear());
const month = ref(now.getMonth() + 1);
const events = ref<CalendarEvent[]>([]);
const loading = ref(true);
const saving = ref(false);

const selectedDate = ref<string | null>(null);
const showForm = ref(false);
const formTitle = ref('');
const formTime = ref('');
const formInput = ref<HTMLInputElement>();
const editingEvent = ref<CalendarEvent | null>(null);

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const weekDays = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
const yearOptions = Array.from({ length: 8 }, (_, i) => now.getFullYear() - 2 + i);

function pad(n: number) { return String(n).padStart(2, '0'); }
const todayStr = computed(() => `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`);
const isCurrentMonth = computed(() => year.value === now.getFullYear() && month.value === now.getMonth() + 1);

// 6 semanas (42 celdas) para una rejilla de altura estable, con dias adyacentes.
const days = computed<CalDay[]>(() => {
  const first = new Date(year.value, month.value - 1, 1);
  const startOffset = (first.getDay() + 6) % 7; // lunes = 0
  const cells: CalDay[] = [];
  for (let i = 0; i < 42; i++) {
    const d = new Date(year.value, month.value - 1, 1 - startOffset + i);
    const dateStr = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    cells.push({
      num: d.getDate(),
      date: dateStr,
      current: d.getMonth() === month.value - 1,
      events: events.value.filter(e => e.date.startsWith(dateStr))
        .sort((a, b) => (a.time || '99').localeCompare(b.time || '99')),
    });
  }
  return cells;
});

function nextMonth() {
  if (month.value === 12) { month.value = 1; year.value++; } else { month.value++; }
}
function prevMonth() {
  if (month.value === 1) { month.value = 12; year.value--; } else { month.value--; }
}
function goToday() {
  year.value = now.getFullYear();
  month.value = now.getMonth() + 1;
}

async function fetchYear() {
  loading.value = true;
  try {
    const results = await Promise.all(
      Array.from({ length: 12 }, (_, i) => calendarService.getEvents(i + 1, year.value))
    );
    events.value = results.flat();
  } finally { loading.value = false; }
}

onMounted(fetchYear);
watch(year, fetchYear);

const selectedDayLabel = computed(() => {
  if (!selectedDate.value) return '';
  const d = new Date(selectedDate.value + 'T12:00:00');
  return d.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
});
const selectedDayEvents = computed(() =>
  selectedDate.value
    ? events.value.filter(e => e.date.startsWith(selectedDate.value!))
        .sort((a, b) => (a.time || '99').localeCompare(b.time || '99'))
    : []
);

function openDayModal(day: CalDay) {
  if (!day.date) return;
  selectedDate.value = day.date;
  showForm.value = false;
  editingEvent.value = null;
}
async function openAdd(day: CalDay) {
  if (!day.date) return;
  selectedDate.value = day.date;
  startAdd();
}
function closeDayModal() {
  selectedDate.value = null;
  showForm.value = false;
  editingEvent.value = null;
}
async function startAdd() {
  editingEvent.value = null;
  formTitle.value = '';
  formTime.value = '';
  showForm.value = true;
  await nextTick();
  formInput.value?.focus();
}
async function startEdit(ev: CalendarEvent) {
  editingEvent.value = ev;
  formTitle.value = ev.title;
  formTime.value = ev.time || '';
  showForm.value = true;
  await nextTick();
  formInput.value?.focus();
}
function cancelForm() {
  showForm.value = false;
  editingEvent.value = null;
  if (!selectedDayEvents.value.length) closeDayModal();
}

async function saveEvent() {
  if (!formTitle.value.trim() || !selectedDate.value) return;
  saving.value = true;
  const time = formTime.value || null;
  try {
    if (editingEvent.value) {
      const updated = await calendarService.updateEvent(editingEvent.value.id, formTitle.value, selectedDate.value, time);
      const idx = events.value.findIndex(e => e.id === updated.id);
      if (idx >= 0) events.value[idx] = updated;
    } else {
      const created = await calendarService.createEvent(formTitle.value, selectedDate.value, time);
      events.value.push(created);
    }
    showForm.value = false;
    editingEvent.value = null;
  } finally { saving.value = false; }
}

async function deleteEvent(id: string) {
  await calendarService.deleteEvent(id);
  events.value = events.value.filter(e => e.id !== id);
}
</script>

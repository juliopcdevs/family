<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="sticky top-0 bg-gray-50 z-10 py-2 space-y-2">
      <div class="flex items-center justify-between">
        <button @click="year--" class="text-gray-600 hover:text-gray-800 p-2">&#9664;</button>
        <h2 class="text-lg font-semibold text-gray-800">{{ year }}</h2>
        <button @click="year++" class="text-gray-600 hover:text-gray-800 p-2">&#9654;</button>
      </div>
      <div class="flex justify-center gap-1 bg-gray-200 rounded-lg p-1">
        <button
          @click="switchView('calendar')"
          class="flex-1 py-1.5 text-sm font-medium rounded-md transition-colors"
          :class="viewMode === 'calendar' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
        >Calendario</button>
        <button
          @click="switchView('list')"
          class="flex-1 py-1.5 text-sm font-medium rounded-md transition-colors"
          :class="viewMode === 'list' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
        >Listado</button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
    </div>
    <template v-else>
      <!-- Calendar view -->
      <div v-if="viewMode === 'calendar'" class="space-y-6">
        <div v-for="m in 12" :key="m" :ref="el => setMonthRef(m, el as HTMLElement)" class="scroll-mt-28">
          <h3 class="text-base font-semibold text-gray-700 mb-2">{{ monthNames[m - 1] }}</h3>
          <MonthGrid
            :days="getMonthDays(m)"
            :today-str="todayStr"
            @day-click="openDayModal"
          />
        </div>
      </div>

      <!-- List view -->
      <div v-else class="space-y-6">
        <div v-for="m in 12" :key="m" :ref="el => setMonthRef(m, el as HTMLElement)" class="scroll-mt-28">
          <h3 class="text-base font-semibold text-gray-700 mb-2">{{ monthNames[m - 1] }}</h3>
          <div v-if="getMonthEvents(m).length" class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
            <div
              v-for="ev in getMonthEvents(m)"
              :key="ev.id"
              class="px-4 py-3 flex items-center justify-between"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="ev.date.startsWith(todayStr) ? 'bg-error/10 text-error' : 'bg-gray-100 text-gray-500'"
                  >{{ formatListDate(ev.date) }}</span>
                  <span v-if="ev.time" class="text-xs font-semibold text-primary">{{ ev.time }}</span>
                </div>
                <span class="text-sm mt-1 block">{{ ev.title }}</span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button @click="startListEdit(ev)" class="text-gray-400 hover:text-primary text-sm">Editar</button>
                <button @click="deleteEvent(ev.id)" class="text-gray-400 hover:text-error text-sm">Eliminar</button>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">Sin eventos</p>
        </div>
      </div>
    </template>

    <button
      @click="scrollToToday()"
      class="fixed bottom-20 right-4 bg-primary text-white px-4 py-2.5 rounded-full shadow-lg text-sm font-medium z-10 active:scale-95 transition-transform"
    >
      Hoy
    </button>

    <!-- Day modal -->
    <div v-if="selectedDay" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4" @click.self="closeDayModal">
      <div class="bg-white rounded-2xl w-full max-w-sm max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
          <h3 class="text-base font-semibold text-gray-800">{{ selectedDayLabel }}</h3>
          <button @click="closeDayModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-2">
          <div
            v-for="ev in selectedDayEvents"
            :key="ev.id"
            class="bg-gray-50 rounded-lg px-4 py-3"
          >
            <div class="flex items-center justify-between">
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
          <p v-if="!selectedDayEvents.length" class="text-sm text-gray-400 text-center py-4">
            No hay eventos este dia
          </p>
        </div>

        <div class="p-5 border-t border-gray-100">
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
            <input
              v-if="editingEvent"
              v-model="formDate"
              type="date"
              required
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

interface CalDay {
  num: number | null;
  date: string | null;
  current: boolean;
  events: CalendarEvent[];
}

const now = new Date();
const year = ref(now.getFullYear());
const viewMode = ref<'calendar' | 'list'>('calendar');
const events = ref<CalendarEvent[]>([]);
const loading = ref(true);
const saving = ref(false);

const selectedDay = ref<CalDay | null>(null);
const showForm = ref(false);
const formTitle = ref('');
const formDate = ref('');
const formTime = ref('');
const formInput = ref<HTMLInputElement>();
const editingEvent = ref<CalendarEvent | null>(null);

const monthRefs: Record<number, HTMLElement | null> = {};

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const todayStr = computed(() => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});

function setMonthRef(m: number, el: HTMLElement | null) {
  monthRefs[m] = el;
}

function getMonthDays(m: number): CalDay[] {
  const firstDay = new Date(year.value, m - 1, 1);
  const lastDay = new Date(year.value, m, 0);
  const startWeekday = (firstDay.getDay() + 6) % 7;
  const days: CalDay[] = [];

  for (let i = 0; i < startWeekday; i++) {
    const d = new Date(year.value, m - 1, -startWeekday + i + 1);
    days.push({ num: d.getDate(), date: null, current: false, events: [] });
  }

  for (let d = 1; d <= lastDay.getDate(); d++) {
    const dateStr = `${year.value}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
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
}

function getMonthEvents(m: number): CalendarEvent[] {
  const prefix = `${year.value}-${String(m).padStart(2, '0')}`;
  return events.value
    .filter(e => e.date.startsWith(prefix))
    .sort((a, b) => {
      const cmp = a.date.localeCompare(b.date);
      if (cmp !== 0) return cmp;
      if (a.time && b.time) return a.time.localeCompare(b.time);
      if (a.time) return -1;
      if (b.time) return 1;
      return 0;
    });
}

function formatListDate(dateStr: string): string {
  if (dateStr.startsWith(todayStr.value)) return 'Hoy';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
}

function startListEdit(ev: CalendarEvent) {
  editingEvent.value = ev;
  formTitle.value = ev.title;
  formTime.value = ev.time || '';
  formDate.value = ev.date.substring(0, 10);
  showForm.value = true;
  selectedDay.value = { num: null, date: ev.date.substring(0, 10), current: true, events: [] };
}

async function fetchEvents() {
  loading.value = true;
  try {
    const promises = Array.from({ length: 12 }, (_, i) =>
      calendarService.getEvents(i + 1, year.value)
    );
    const results = await Promise.all(promises);
    events.value = results.flat();
  } finally { loading.value = false; }
}

function scrollToToday(smooth = true) {
  const currentMonth = new Date().getMonth() + 1;
  const el = monthRefs[currentMonth];
  if (el) el.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant', block: 'start' });
}

async function switchView(mode: 'calendar' | 'list') {
  viewMode.value = mode;
  await nextTick();
  scrollToToday(false);
}

onMounted(async () => {
  await fetchEvents();
  await nextTick();
  scrollToToday(false);
});

watch(year, fetchEvents);

const selectedDayLabel = computed(() => {
  if (!selectedDay.value?.date) return '';
  const d = new Date(selectedDay.value.date + 'T12:00:00');
  return d.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
});

const selectedDayEvents = computed(() => {
  if (!selectedDay.value?.date) return [];
  return events.value
    .filter(e => e.date.startsWith(selectedDay.value!.date!))
    .sort((a, b) => {
      if (a.time && b.time) return a.time.localeCompare(b.time);
      if (a.time) return -1;
      if (b.time) return 1;
      return 0;
    });
});

function openDayModal(day: CalDay) {
  selectedDay.value = day;
  showForm.value = false;
  editingEvent.value = null;
}

function closeDayModal() {
  selectedDay.value = null;
  showForm.value = false;
  editingEvent.value = null;
}

async function startAdd() {
  editingEvent.value = null;
  formTitle.value = '';
  formTime.value = '';
  formDate.value = selectedDay.value?.date || todayStr.value;
  showForm.value = true;
  await nextTick();
  formInput.value?.focus();
}

async function startEdit(ev: CalendarEvent) {
  editingEvent.value = ev;
  formTitle.value = ev.title;
  formTime.value = ev.time || '';
  formDate.value = selectedDay.value?.date || ev.date;
  showForm.value = true;
  await nextTick();
  formInput.value?.focus();
}

function cancelForm() {
  showForm.value = false;
  editingEvent.value = null;
}

async function saveEvent() {
  if (!formTitle.value.trim()) return;
  saving.value = true;
  const time = formTime.value || null;
  try {
    if (editingEvent.value) {
      const updated = await calendarService.updateEvent(editingEvent.value.id, formTitle.value, formDate.value, time);
      const idx = events.value.findIndex(e => e.id === updated.id);
      if (idx >= 0) events.value[idx] = updated;
    } else {
      const created = await calendarService.createEvent(formTitle.value, formDate.value, time);
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

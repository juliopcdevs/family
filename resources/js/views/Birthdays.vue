<template>
  <div class="max-w-lg mx-auto space-y-6">
    <button @click="openForm()" class="w-full bg-primary text-white py-3 rounded-lg font-medium hover:bg-primary/90">
      Agregar cumpleanos
    </button>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
    </div>
    <template v-else>
      <div v-if="birthdays.length" class="space-y-3">
        <div
          v-for="b in birthdays"
          :key="b._id"
          class="bg-white rounded-xl shadow-sm p-4 flex items-center justify-between"
        >
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary/10 text-primary rounded-full flex items-center justify-center text-lg font-bold">
              {{ b.person_name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="font-semibold text-gray-800">{{ b.person_name }}</p>
              <p class="text-sm text-gray-500">
                {{ formatDate(b.birth_date) }} &middot; Cumple {{ b.age_on_next_birthday }} anos
              </p>
            </div>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-2xl font-bold text-primary">{{ b.days_until_birthday }}</p>
            <p class="text-xs text-gray-400">dias</p>
            <div class="flex gap-2 mt-1">
              <button @click="openForm(b)" class="text-xs text-gray-400 hover:text-primary">Editar</button>
              <button @click="remove(b._id)" class="text-xs text-gray-400 hover:text-error">Eliminar</button>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400 text-center">Sin cumpleanos registrados</p>
    </template>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4" @click.self="closeForm">
      <div class="bg-white rounded-xl p-6 w-full max-w-sm space-y-4">
        <h3 class="text-lg font-semibold">{{ editing ? 'Editar cumpleanos' : 'Nuevo cumpleanos' }}</h3>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
          <input v-model="formName" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" placeholder="Ej: Mama" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
          <input v-model="formDate" type="date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none" />
        </div>
        <button @click="save" :disabled="saving" class="w-full bg-primary text-white py-2.5 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50">
          {{ saving ? 'Guardando...' : 'Guardar' }}
        </button>
        <button @click="closeForm" class="w-full text-sm text-gray-500 hover:underline">Cancelar</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import birthdayService from '@/services/birthday.service';
import { useToast } from '@/composables/useToast';
import type { Birthday } from '@/types';

const { success, error: showError } = useToast();
const loading = ref(true);
const saving = ref(false);
const birthdays = ref<Birthday[]>([]);
const showForm = ref(false);
const formName = ref('');
const formDate = ref('');
const editing = ref<Birthday | null>(null);

onMounted(async () => {
  try { birthdays.value = await birthdayService.getBirthdays(); }
  catch { showError('Error al cargar cumpleanos'); }
  finally { loading.value = false; }
});

function openForm(b?: Birthday) {
  if (b) {
    editing.value = b;
    formName.value = b.person_name;
    formDate.value = b.birth_date;
  } else {
    editing.value = null;
    formName.value = '';
    formDate.value = '';
  }
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editing.value = null;
}

async function save() {
  if (!formName.value.trim() || !formDate.value) return;
  saving.value = true;
  try {
    if (editing.value) {
      const updated = await birthdayService.updateBirthday(editing.value._id, formName.value, formDate.value);
      const idx = birthdays.value.findIndex(b => b._id === updated._id);
      if (idx >= 0) birthdays.value[idx] = updated;
      success('Cumpleanos actualizado');
    } else {
      const created = await birthdayService.createBirthday(formName.value, formDate.value);
      birthdays.value.push(created);
      success('Cumpleanos agregado');
    }
    birthdays.value.sort((a, b) => a.days_until_birthday - b.days_until_birthday);
    closeForm();
  } catch { showError('Error al guardar cumpleanos'); }
  finally { saving.value = false; }
}

async function remove(id: string) {
  if (!confirm('Eliminar este cumpleanos?')) return;
  try {
    await birthdayService.deleteBirthday(id);
    birthdays.value = birthdays.value.filter(b => b._id !== id);
    success('Cumpleanos eliminado');
  } catch { showError('Error al eliminar'); }
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'long' });
}
</script>

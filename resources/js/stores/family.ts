import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Family } from '@/types';
import familyService from '@/services/family.service';
import { useAuthStore } from './auth';

export const useFamilyStore = defineStore('family', () => {
  const family = ref<Family | null>(null);

  async function createFamily(name: string) {
    const response = await familyService.createFamily(name);
    family.value = response.family;
    const authStore = useAuthStore();
    await authStore.fetchUser();
    return response;
  }

  async function joinFamily(code: string) {
    const response = await familyService.joinFamily(code);
    family.value = response.family;
    const authStore = useAuthStore();
    await authStore.fetchUser();
    return response;
  }

  async function fetchFamily() {
    family.value = await familyService.getCurrentFamily();
  }

  async function leaveFamily() {
    await familyService.leaveFamily();
    family.value = null;
    const authStore = useAuthStore();
    await authStore.fetchUser();
  }

  return { family, createFamily, joinFamily, fetchFamily, leaveFamily };
});

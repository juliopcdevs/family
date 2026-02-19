import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { User } from '@/types';
import authService from '@/services/auth.service';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('auth_token'));

  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const hasFamily = computed(() => !!user.value?.family_id);
  const isEmailVerified = computed(() => !!user.value?.email_verified_at);

  async function login(email: string, password: string) {
    const response = await authService.login(email, password);
    token.value = response.token;
    user.value = response.user;
    localStorage.setItem('auth_token', response.token);
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string) {
    await authService.register(name, email, password, passwordConfirmation);
  }

  async function logout() {
    try { await authService.logout(); } catch { /* ignore */ }
    token.value = null;
    user.value = null;
    localStorage.removeItem('auth_token');
  }

  async function fetchUser() {
    if (!token.value) return;
    try {
      user.value = await authService.getUser();
    } catch {
      token.value = null;
      user.value = null;
      localStorage.removeItem('auth_token');
    }
  }

  return { user, token, isAuthenticated, hasFamily, isEmailVerified, login, register, logout, fetchUser };
});

import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes: RouteRecordRaw[] = [
  { path: '/login', name: 'login', component: () => import('@/views/auth/Login.vue'), meta: { guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/Register.vue'), meta: { guest: true } },
  { path: '/verify-email', name: 'verify-email', component: () => import('@/views/auth/VerifyEmail.vue') },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPassword.vue'), meta: { guest: true } },
  { path: '/reset-password/:token', name: 'reset-password', component: () => import('@/views/auth/ResetPassword.vue'), meta: { guest: true } },
  { path: '/family/setup', name: 'family-setup', component: () => import('@/views/family/CreateOrJoin.vue'), meta: { requiresAuth: true, requiresNoFamily: true } },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true, requiresFamily: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('@/views/Dashboard.vue') },
      { path: 'shopping', name: 'shopping', component: () => import('@/views/ShoppingList.vue') },
      { path: 'calendar', name: 'calendar', component: () => import('@/views/Calendar.vue') },
      { path: 'tasks', name: 'tasks', component: () => import('@/views/Tasks.vue') },
      { path: 'birthdays', name: 'birthdays', component: () => import('@/views/Birthdays.vue') },
      { path: 'family/settings', name: 'family-settings', component: () => import('@/views/family/Settings.vue') },
    ],
  },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore();

  if (!authStore.user && authStore.token) {
    await authStore.fetchUser();
  }

  if (to.meta.guest && authStore.isAuthenticated) return next({ name: 'dashboard' });
  if (to.meta.requiresAuth && !authStore.isAuthenticated) return next({ name: 'login' });
  if (to.meta.requiresFamily && !authStore.hasFamily) return next({ name: 'family-setup' });
  if (to.meta.requiresNoFamily && authStore.hasFamily) return next({ name: 'dashboard' });

  next();
});

export default router;

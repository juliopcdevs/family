import api from './api';
import type { User } from '@/types';

export default {
  async register(name: string, email: string, password: string, passwordConfirmation: string) {
    const { data } = await api.post('/register', {
      name, email, password, password_confirmation: passwordConfirmation,
    });
    return data;
  },

  async login(email: string, password: string) {
    const { data } = await api.post('/login', { email, password });
    return data as { user: User; token: string };
  },

  async logout() {
    await api.post('/logout');
  },

  async getUser(): Promise<User> {
    const { data } = await api.get('/user');
    return data;
  },

  async sendVerificationEmail() {
    await api.post('/email/verification-notification');
  },

  async forgotPassword(email: string) {
    const { data } = await api.post('/forgot-password', { email });
    return data;
  },

  async resetPassword(token: string, email: string, password: string, passwordConfirmation: string) {
    const { data } = await api.post('/reset-password', {
      token, email, password, password_confirmation: passwordConfirmation,
    });
    return data;
  },
};

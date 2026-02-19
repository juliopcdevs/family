import api from './api';
import type { Family } from '@/types';

export default {
  async createFamily(name: string) {
    const { data } = await api.post('/family/create', { name });
    return data as { family: Family; code: string };
  },

  async joinFamily(code: string) {
    const { data } = await api.post('/family/join', { code });
    return data as { family: Family; message: string };
  },

  async getCurrentFamily(): Promise<Family> {
    const { data } = await api.get('/family/current');
    return data;
  },

  async leaveFamily() {
    await api.post('/family/leave');
  },
};

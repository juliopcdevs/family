import api from './api';
import type { DashboardData } from '@/types';

export default {
  async getDashboard(): Promise<DashboardData> {
    const { data } = await api.get('/dashboard');
    return data;
  },
};

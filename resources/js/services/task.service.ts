import api from './api';
import type { Task } from '@/types';

export default {
  async getTasks() {
    const { data } = await api.get('/tasks');
    return data as { pending: Task[]; completed: Task[] };
  },

  async createTask(title: string) {
    const { data } = await api.post('/tasks', { title });
    return data as Task;
  },

  async completeTask(id: string) {
    const { data } = await api.put(`/tasks/${id}/complete`);
    return data as Task;
  },

  async deleteTask(id: string) {
    await api.delete(`/tasks/${id}`);
  },
};

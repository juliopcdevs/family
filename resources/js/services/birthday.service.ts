import api from './api';
import type { Birthday } from '@/types';

export default {
  async getBirthdays(): Promise<Birthday[]> {
    const { data } = await api.get('/birthdays');
    return data;
  },

  async createBirthday(personName: string, birthDate: string) {
    const { data } = await api.post('/birthdays', {
      person_name: personName, birth_date: birthDate,
    });
    return data as Birthday;
  },

  async updateBirthday(id: string, personName: string, birthDate: string) {
    const { data } = await api.put(`/birthdays/${id}`, {
      person_name: personName, birth_date: birthDate,
    });
    return data as Birthday;
  },

  async deleteBirthday(id: string) {
    await api.delete(`/birthdays/${id}`);
  },
};

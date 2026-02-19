import api from './api';
import type { CalendarEvent } from '@/types';

export default {
  async getEvents(month: number, year: number): Promise<CalendarEvent[]> {
    const { data } = await api.get('/calendar/events', { params: { month, year } });
    return data;
  },

  async createEvent(title: string, date: string) {
    const { data } = await api.post('/calendar/events', { title, date });
    return data as CalendarEvent;
  },

  async updateEvent(id: string, title: string, date: string) {
    const { data } = await api.put(`/calendar/events/${id}`, { title, date });
    return data as CalendarEvent;
  },

  async deleteEvent(id: string) {
    await api.delete(`/calendar/events/${id}`);
  },
};

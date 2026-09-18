import api from './api';
import type { ShoppingItem, ShoppingListItem } from '@/types';

export default {
  async getList() {
    const { data } = await api.get('/shopping');
    return data as { cart: ShoppingListItem[]; frequent: ShoppingListItem[] };
  },

  async search(query: string): Promise<ShoppingItem[]> {
    const { data } = await api.get('/shopping/search', { params: { q: query } });
    return data;
  },

  async addToCart(item: { item_name: string; item_slug: string | null; is_predefined: boolean; image_url: string | null }) {
    const { data } = await api.post('/shopping/add', item);
    return data as ShoppingListItem;
  },

  async removeFromCart(id: string) {
    const { data } = await api.post(`/shopping/remove/${id}`);
    return data as ShoppingListItem;
  },

  async deleteItem(id: string) {
    await api.delete(`/shopping/${id}`);
  },
};

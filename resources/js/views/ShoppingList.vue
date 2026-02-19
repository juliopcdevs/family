<template>
  <div class="max-w-lg mx-auto space-y-6">
    <div class="relative">
      <input
        v-model="query"
        type="text"
        placeholder="Buscar producto..."
        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none"
        @input="handleSearch"
      />
      <div v-if="searchResults.length" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg mt-1 shadow-lg z-10 max-h-60 overflow-y-auto">
        <button
          v-for="item in searchResults"
          :key="item._id"
          class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100 last:border-0"
          @click="addPredefined(item)"
        >
          <span class="text-sm">{{ item.name }}</span>
        </button>
      </div>
      <button
        v-if="query.trim() && !searchResults.length && !searching"
        class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg mt-1 shadow-lg z-10 px-4 py-3 text-left hover:bg-gray-50 text-sm"
        @click="addCustom"
      >
        Agregar "{{ query.trim() }}"
      </button>
    </div>

    <div>
      <h2 class="text-lg font-semibold text-gray-800 mb-3">En el carrito ({{ cart.length }})</h2>
      <div v-if="cart.length" class="space-y-2">
        <div
          v-for="item in cart"
          :key="item._id"
          class="bg-white rounded-lg shadow-sm px-4 py-3 flex items-center justify-between"
        >
          <span class="text-sm font-medium">{{ item.item_name }}</span>
          <button @click="remove(item._id)" class="text-error text-xs hover:underline">Quitar</button>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">Carrito vacio</p>
    </div>

    <div v-if="frequent.length">
      <h2 class="text-lg font-semibold text-gray-800 mb-3">Frecuentes</h2>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="item in frequent"
          :key="item._id"
          class="bg-white border border-gray-200 rounded-full px-4 py-2 text-sm hover:border-primary hover:text-primary transition-colors"
          @click="addFromFrequent(item)"
        >
          {{ item.item_name }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import shoppingService from '@/services/shopping.service';
import type { ShoppingItem, ShoppingListItem } from '@/types';

const query = ref('');
const searching = ref(false);
const searchResults = ref<ShoppingItem[]>([]);
const cart = ref<ShoppingListItem[]>([]);
const frequent = ref<ShoppingListItem[]>([]);
let searchTimeout: ReturnType<typeof setTimeout>;

onMounted(async () => {
  const data = await shoppingService.getList();
  cart.value = data.cart;
  frequent.value = data.frequent;
});

function handleSearch() {
  clearTimeout(searchTimeout);
  searchResults.value = [];
  if (!query.value.trim()) return;
  searching.value = true;
  searchTimeout = setTimeout(async () => {
    searchResults.value = await shoppingService.search(query.value);
    searching.value = false;
  }, 300);
}

async function addPredefined(item: ShoppingItem) {
  const added = await shoppingService.addToCart({
    item_name: item.name,
    item_slug: item.slug,
    is_predefined: true,
    image_url: item.image_url,
  });
  cart.value.push(added);
  query.value = '';
  searchResults.value = [];
  frequent.value = frequent.value.filter(f => f.item_slug !== item.slug);
}

async function addCustom() {
  const added = await shoppingService.addToCart({
    item_name: query.value.trim(),
    item_slug: null,
    is_predefined: false,
    image_url: null,
  });
  cart.value.push(added);
  query.value = '';
}

async function addFromFrequent(item: ShoppingListItem) {
  const added = await shoppingService.addToCart({
    item_name: item.item_name,
    item_slug: item.item_slug,
    is_predefined: item.is_predefined,
    image_url: item.image_url,
  });
  cart.value.push(added);
  frequent.value = frequent.value.filter(f => f._id !== item._id);
}

async function remove(id: string) {
  await shoppingService.removeFromCart(id);
  const removed = cart.value.find(i => i._id === id);
  cart.value = cart.value.filter(i => i._id !== id);
  if (removed && removed.usage_count > 1) {
    frequent.value.push(removed);
  }
}
</script>

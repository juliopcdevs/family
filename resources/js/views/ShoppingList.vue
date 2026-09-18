<template>
  <div class="max-w-lg mx-auto space-y-6">
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
    </div>
    <template v-else>
      <div class="relative">
        <div class="flex gap-2">
          <input
            ref="searchInput"
            v-model="query"
            type="text"
            placeholder="Necesito..."
            class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none shadow-sm"
            @input="handleSearch"
            @focus="onFocus"
          />
          <button
            v-if="query.trim()"
            class="bg-primary text-white rounded-xl px-4 py-3 font-bold text-lg shadow-sm active:scale-95 transition-transform"
            @click="addCustom"
          >
            +
          </button>
        </div>
        <div
          v-if="showDropdown"
          class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-xl mt-2 shadow-lg max-h-60 overflow-y-auto z-10"
        >
          <button
            v-for="item in searchResults"
            :key="item.id"
            class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100 last:border-0"
            @click="addPredefined(item)"
          >
            <img
              v-if="item.image_url"
              :src="item.image_url"
              :alt="item.name"
              class="w-8 h-8 rounded-lg bg-cart p-1"
            />
            <span
              v-else
              class="w-8 h-8 rounded-lg bg-gray-200 flex items-center justify-center text-sm font-bold text-gray-500"
            >
              {{ item.name.charAt(0).toUpperCase() }}
            </span>
            <span class="text-sm">{{ item.name }}</span>
          </button>
          <button
            v-if="query.trim() && !searchResults.length && !searching"
            class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center gap-3 text-sm"
            @click="addCustom"
          >
            <span class="w-8 h-8 rounded-lg bg-gray-200 flex items-center justify-center text-sm font-bold text-gray-500">+</span>
            <span>Agregar "<strong>{{ query.trim() }}</strong>"</span>
          </button>
        </div>
      </div>

      <p class="text-xs text-gray-400 leading-relaxed">
        Pulsa en cada producto para moverlo al carrito o quitarlo. Pulsa la <strong class="text-gray-500">X</strong> para eliminarlo del todo; siempre podras volver a añadirlo desde el buscador.
      </p>

      <div v-if="cart.length" class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
          En el carrito ({{ cart.length }})
        </h2>
        <div class="grid grid-cols-4 gap-2">
          <ShoppingCard
            v-for="item in cart"
            :key="item.id"
            :name="item.item_name"
            :image-url="item.image_url"
            color="coral"
            @click="remove(item.id)"
            @delete="confirmDelete(item)"
          />
        </div>
      </div>

      <div v-if="frequent.length">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
          Frecuentes
        </h2>
        <div class="grid grid-cols-4 gap-2">
          <ShoppingCard
            v-for="item in frequent"
            :key="item.id"
            :name="item.item_name"
            :image-url="item.image_url"
            color="teal"
            @click="addFromFrequent(item)"
            @delete="confirmDelete(item)"
          />
        </div>
      </div>

      <p v-if="!cart.length && !frequent.length" class="text-center text-gray-400 py-12">
        Busca un producto para empezar
      </p>
    </template>

    <div v-if="itemToDelete" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" @click.self="itemToDelete = null">
      <div class="bg-white rounded-2xl p-6 w-full max-w-xs shadow-xl text-center">
        <p class="text-base font-semibold text-gray-800 mb-1">Eliminar producto</p>
        <p class="text-sm text-gray-500 mb-5">Se eliminara "{{ itemToDelete.item_name }}" de tu lista</p>
        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600" @click="itemToDelete = null">Cancelar</button>
          <button class="flex-1 py-2.5 rounded-xl bg-error text-white text-sm font-medium" @click="deleteItem">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import ShoppingCard from '@/components/shopping/ShoppingCard.vue';
import shoppingService from '@/services/shopping.service';
import type { ShoppingItem, ShoppingListItem } from '@/types';

const loading = ref(true);
const query = ref('');
const searching = ref(false);
const searchResults = ref<ShoppingItem[]>([]);
const cart = ref<ShoppingListItem[]>([]);
const frequent = ref<ShoppingListItem[]>([]);
const searchInput = ref<HTMLInputElement>();
const inputFocused = ref(false);
const itemToDelete = ref<ShoppingListItem | null>(null);
let searchTimeout: ReturnType<typeof setTimeout>;

const showDropdown = computed(() => {
  return inputFocused.value && query.value.trim() && (searchResults.value.length || (!searching.value && query.value.trim()));
});

onMounted(async () => {
  try {
    const data = await shoppingService.getList();
    cart.value = data.cart;
    frequent.value = data.frequent;
  } finally { loading.value = false; }
});

function onFocus() {
  inputFocused.value = true;
  if (query.value.trim()) handleSearch();
}

function handleSearch() {
  clearTimeout(searchTimeout);
  searchResults.value = [];
  if (!query.value.trim()) return;
  searching.value = true;
  searchTimeout = setTimeout(async () => {
    try { searchResults.value = await shoppingService.search(query.value); }
    finally { searching.value = false; }
  }, 300);
}

function upsertCart(item: ShoppingListItem) {
  const idx = cart.value.findIndex(i => i.id === item.id);
  if (idx >= 0) {
    cart.value[idx] = item;
  } else {
    cart.value = [...cart.value, item];
  }
}

async function addPredefined(item: ShoppingItem) {
  const added = await shoppingService.addToCart({
    item_name: item.name, item_slug: item.slug,
    is_predefined: true, image_url: item.image_url,
  });
  upsertCart(added);
  query.value = '';
  searchResults.value = [];
  inputFocused.value = false;
  frequent.value = frequent.value.filter(f => f.id !== added.id);
}

async function addCustom() {
  if (!query.value.trim()) return;
  const added = await shoppingService.addToCart({
    item_name: query.value.trim(), item_slug: null,
    is_predefined: false, image_url: null,
  });
  upsertCart(added);
  query.value = '';
  searchResults.value = [];
  inputFocused.value = false;
  frequent.value = frequent.value.filter(f => f.id !== added.id);
}

async function addFromFrequent(item: ShoppingListItem) {
  const added = await shoppingService.addToCart({
    item_name: item.item_name, item_slug: item.item_slug,
    is_predefined: item.is_predefined, image_url: item.image_url,
  });
  upsertCart(added);
  frequent.value = frequent.value.filter(f => f.id !== item.id);
}

async function remove(id: string) {
  const updated = await shoppingService.removeFromCart(id);
  cart.value = cart.value.filter(i => i.id !== id);
  frequent.value = [...frequent.value, updated];
}

function confirmDelete(item: ShoppingListItem) {
  itemToDelete.value = item;
}

async function deleteItem() {
  if (!itemToDelete.value) return;
  const id = itemToDelete.value.id;
  await shoppingService.deleteItem(id);
  cart.value = cart.value.filter(i => i.id !== id);
  frequent.value = frequent.value.filter(i => i.id !== id);
  itemToDelete.value = null;
}
</script>

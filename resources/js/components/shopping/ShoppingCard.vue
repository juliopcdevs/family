<template>
  <div class="relative">
    <button
      class="w-full flex flex-col items-center justify-center rounded-xl p-2 aspect-square transition-transform active:scale-95"
      :class="color === 'coral' ? 'bg-cart' : 'bg-frequent'"
      @click="$emit('click')"
    >
      <img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="name"
        class="w-10 h-10 object-contain"
      />
      <span
        v-else
        class="w-10 h-10 flex items-center justify-center text-white text-2xl font-bold"
      >
        {{ name.charAt(0).toUpperCase() }}
      </span>
      <span class="text-white text-xs mt-1 text-center leading-tight line-clamp-2 w-full">
        {{ name }}
      </span>
    </button>
    <button
      class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-gray-700 text-white rounded-full flex items-center justify-center text-xs leading-none shadow"
      @click.stop="$emit('delete')"
    >
      &times;
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  name: string;
  imageUrl: string | null;
  color: 'coral' | 'teal';
}>();

defineEmits<{
  click: [];
  delete: [];
}>();
</script>

<template>
  <transition name="fade">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @keydown.esc="close"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
        @click="close"
        aria-hidden="true"
      ></div>

      <!-- Dialog panel -->
      <div
        role="dialog"
        aria-modal="true"
        class="relative z-10 w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-black/95 shadow-xl transition-all"
      >
        <!-- Header slot -->
        <header class="px-6 pt-6 pb-2 flex items-center justify-between">
          <slot name="header">
            <h3 class="text-lg font-semibold text-black dark:text-white">Dialog</h3>
          </slot>
          <button
            type="button"
            @click="close"
            class="-mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black/20 dark:focus:ring-white/20"
            aria-label="Close dialog"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </header>

        <!-- Body slot -->
        <div class="px-6 py-4">
          <slot name="body">
            <p class="text-sm text-gray-600 dark:text-gray-300">Default body</p>
          </slot>
        </div>

        <!-- Footer slot -->
        <footer class="px-6 pb-6 pt-2 flex justify-end gap-2">
          <slot name="footer">
          </slot>
        </footer>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

function close() {
  emit('update:modelValue', false);
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
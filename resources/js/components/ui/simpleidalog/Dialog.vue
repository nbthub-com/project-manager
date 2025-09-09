<script setup>
import { defineProps, defineEmits } from "vue";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(["update:modelValue"]);

function close() {
  emit("update:modelValue", false);
}
</script>

<template>
  <transition name="fade">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @keydown.esc="close"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
        @click="close"
        aria-hidden="true"
      ></div>

      <!-- Dialog panel -->
      <div
        role="dialog"
        aria-modal="true"
        class="relative z-10 w-full max-w-lg transform overflow-hidden rounded-2xl bg-card text-card-foreground shadow-2xl transition-all scale-95 opacity-0 animate-fade-in-up"
      >
        <!-- Header slot -->
        <header
          class="px-6 pt-6 pb-3 flex items-center justify-between border-b border-secondary/30"
        >
          <slot name="header">
            <h3 class="text-xl font-semibold text-foreground">
              Dialog Title
            </h3>
          </slot>
          <button
            type="button"
            @click="close"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-secondary/20 focus:outline-none focus:ring-2 focus:ring-primary"
            aria-label="Close dialog"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 text-foreground"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </header>

        <!-- Body slot -->
        <div class="px-6 py-5 space-y-3 border-t-2">
          <slot name="body">
            <p class="text-sm leading-relaxed text-foreground/80">
              Default body content goes here.  
            </p>
          </slot>
        </div>

        <!-- Footer slot -->
        <footer
          class="px-6 pb-6 pt-3 flex justify-end gap-3 border-t-2 border-secondary/30"
        >
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Extra pop-in animation */
@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(15px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 0.25s ease-out forwards;
}
</style>

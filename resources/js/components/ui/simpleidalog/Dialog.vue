<script setup>
import { defineProps, defineEmits, ref, onMounted } from "vue";

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

// Sidebar width state
const sidebarWidth = ref(500); // default width in px
let isResizing = false;

function startResize(e) {
  isResizing = true;
  document.addEventListener("mousemove", resize);
  document.addEventListener("mouseup", stopResize);
}

function resize(e) {
  if (!isResizing) return;
  // limit between 250px and 800px
  sidebarWidth.value = Math.min(Math.max(250, window.innerWidth - e.clientX), 800);
}

function stopResize() {
  isResizing = false;
  document.removeEventListener("mousemove", resize);
  document.removeEventListener("mouseup", stopResize);
}
</script>

<template>
  <transition name="slide">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex justify-end"
      @keydown.esc="close"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-transparent transition-opacity"
        @click="close"
        aria-hidden="true"
      ></div>

      <!-- Sidebar panel -->
      <div
        role="dialog"
        aria-modal="true"
        class="relative z-10 h-full bg-card text-card-foreground shadow-2xl flex flex-col"
        :style="{ width: sidebarWidth + 'px' }"
      >
        <!-- Resize handle -->
        <div
          class="absolute left-0 top-0 h-full w-1 cursor-ew-resize bg-transparent hover:bg-secondary/30"
          @mousedown="startResize"
        ></div>

        <!-- Header slot -->
        <header
          class="px-6 pt-6 pb-3 flex items-center justify-between border-b border-secondary/30"
        >
          <slot name="header">
            <h3 class="text-xl font-semibold text-foreground">
              Sidebar Title
            </h3>
          </slot>
        </header>

        <!-- Body slot -->
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-3">
          <slot name="body">
            <p class="text-sm leading-relaxed text-foreground/80">
              Default sidebar content goes here.
            </p>
          </slot>
        </div>

        <!-- Footer slot -->
        <footer
          class="px-6 pb-6 pt-3 flex justify-end gap-3 border-t border-secondary/30"
        >
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease, opacity 0.2s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>

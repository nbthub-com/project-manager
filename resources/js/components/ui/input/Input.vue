<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { ref, computed } from "vue";
import { cn } from "@/lib/utils";
import { useVModel } from "@vueuse/core";
import { Eye, EyeOff } from "lucide-vue-next";

const props = defineProps<{
  defaultValue?: string | number;
  modelValue?: string | number;
  class?: HTMLAttributes["class"];
  type?: string;
}>();

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void;
}>();

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
});

// for password toggle
const showPassword = ref(false);
const inputType = computed(() =>
  props.type === "password" ? (showPassword.value ? "text" : "password") : props.type
);
</script>

<template>
  <div class="relative w-full">
    <input
      v-model="modelValue"
      data-slot="input"
      :type="inputType"
      :class="
        cn(
          'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
          'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
          'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
          props.class
        )
      "
    />

    <!-- password toggle button with transition -->
    <button
      v-if="props.type === 'password'"
      type="button"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
      @click="showPassword = !showPassword"
    >
      <Transition name="fade" mode="out-in">
        <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
      </Transition>
    </button>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: {
    type: String,
    default: "",
  },
  options: {
    type: Array,
    required: true, // [{ label: "User", value: "user" }]
  },
  class: {
    required: false
  }
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const dropdownRef = ref(null);
const dropdownStyles = ref({});

function toggle() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    nextTick(() => positionDropdown());
  }
}

function selectOption(option) {
  emit("update:modelValue", option.value);
  isOpen.value = false;
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
}

function positionDropdown() {
  if (!dropdownRef.value) return;
  const rect = dropdownRef.value.getBoundingClientRect();
  const offset = 10; // margin from edges
  const viewportHeight = window.innerHeight;

  // Available space
  const spaceBelow = viewportHeight - rect.bottom - offset;
  const spaceAbove = rect.top - offset;

  // Decide direction (open down or up)
  const openDown = spaceBelow > spaceAbove;

  // Compute max height
  const maxHeight = openDown ? spaceBelow : spaceAbove;

  dropdownStyles.value = {
    top: openDown ? rect.bottom + "px" : "auto",
    bottom: openDown ? "auto" : viewportHeight - rect.top + "px",
    left: rect.left + "px",
    width: rect.width + "px",
    maxHeight: maxHeight + "px",
  };
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  window.addEventListener("resize", positionDropdown);
  window.addEventListener("scroll", positionDropdown, true); // track parent scrolling
});
onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  window.removeEventListener("resize", positionDropdown);
  window.removeEventListener("scroll", positionDropdown, true);
});
</script>

<template>
  <div ref="dropdownRef" class="relative w-full">
    <!-- Trigger -->
    <button
      type="button"
      @click="toggle"
      class="w-full flex items-center justify-between rounded-md border border-input bg-card px-3 py-2 text-sm
             text-foreground shadow-sm transition focus:ring-2 focus:ring-primary/50 focus:outline-none"
      :class="props.class"
    >
      <span>
        {{
          props.options.find((o) => o.value === props.modelValue)?.label ||
          "Select an option"
        }}
      </span>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-4 w-4 text-muted-foreground transition-transform"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Options -->
    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <Teleport to="body">
        <ul
          v-if="isOpen"
          class="fixed z-[9999] rounded-xl border bg-secondary border-input text-sm 
                 shadow-lg shadow-blue-500/20 inset-2 overflow-auto m-1"
          :style="dropdownStyles"
        >
          <li
            v-for="option in props.options"
            :key="option.value"
            @click="selectOption(option)"
            class="px-3 py-2 cursor-pointer hover:bg-gradient-to-r hover:from-primary hover:to-secondary 
                   hover:text-primary-foreground transition"
          >
            {{ option.label }}
          </li>
        </ul>
      </Teleport>
    </transition>
  </div>
</template>

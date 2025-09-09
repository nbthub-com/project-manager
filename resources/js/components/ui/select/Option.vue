<template>
  <option
    :value="value"
    class="px-3 py-2 rounded-md cursor-pointer
           bg-card text-card-foreground
           hover:bg-primary hover:text-foreground
           focus:bg-secondary focus:text-foreground
           disabled:opacity-50 disabled:cursor-not-allowed"
  >
    <slot />
  </option>
</template>

<script setup>
defineProps({
  value: {
    type: [String, Number],
    required: true
  }
});
</script>

<script setup>
import { defineProps, computed } from 'vue'

const props = defineProps({
  rows: {
    type: Array,
    required: true,
    default: () => []
  }
})

// Compute headers from the first row
const headers = computed(() => (props.rows[0] ? Object.keys(props.rows[0]) : []))
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-black dark:border-white">
    <table class="min-w-full border-collapse">
      <thead class="bg-white dark:bg-black">
        <tr>
          <th
            v-for="header in headers"
            :key="header"
            class="px-4 py-2 text-left border-b border-black dark:border-white dark:text-white text-black"
          >
            {{ header }}
          </th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-black">
        <tr
          v-for="(row, index) in rows"
          :key="index"
          class="even:bg-gray-100 dark:even:bg-gray-800" 
        >
          <td
            v-for="header in headers"
            :key="header"
            class="px-4 py-2 border-b border-black dark:border-white text-black dark:text-white"
          >
            {{ row[header] }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

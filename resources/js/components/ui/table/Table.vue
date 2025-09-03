<script setup>
import { defineProps, computed, ref } from "vue";

const props = defineProps({
  rows: {
    type: Array,
    required: true,
    default: () => [],
  },
  tableTitle: {
    type: String,
    required: true,
    default: "Data"
  },
});

// Utility function: snake_case → Title Case
function toTitleCase(str) {
  return str
    .replace(/_/g, " ")
    .replace(/\w\S*/g, (txt) =>
      txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
    );
}

// Headers: keep original key + label
const headers = computed(() => {
  if (props.rows && props.rows.length > 0) {
    return Object.keys(props.rows[0]).map((key) => ({
      key,
      label: toTitleCase(key),
    }));
  }
  return [];
});

// Track selected row
const selectedRow = ref(null);
function selectRow(index) {
  selectedRow.value = index;
}
</script>

<template>
  <div class="overflow-hidden rounded-lg shadow-sm">
    <!-- Fallback: No data -->
    <div
      v-if="!rows || rows.length === 0"
      class="p-6 text-center text-gray-600 dark:text-gray-300"
    >
      No {{ tableTitle }} Yet!
    </div>

    <!-- Table -->
    <table
      v-else
      class="min-w-full border-separate border-spacing-0"
    >
      <!-- Table Head -->
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th
            v-for="header in headers"
            :key="header.key"
            class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200"
          >
            {{ header.label }}
          </th>
        </tr>
      </thead>

      <!-- Table Body -->
      <tbody>
        <tr
          v-for="(row, index) in rows"
          :key="index"
          @click="selectRow(index)"
          :class="[
            'cursor-pointer transition-colors',
            'border-b last:border-0 border-gray-200 dark:border-gray-700',
            selectedRow === index
              ? 'bg-gray-100 dark:bg-gray-800 ring-2 ring-indigo-500'
              : 'hover:bg-gray-50 dark:hover:bg-gray-800'
          ]"
        >
          <td
            v-for="header in headers"
            :key="header.key"
            class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100"
          >
            {{ row[header.key] }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

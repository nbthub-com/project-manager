<script setup>
import { defineProps, computed } from "vue";

const props = defineProps({
  rows: {
    type: Array,
    required: true,
    default: () => [],
  },
  tableTitle: {
    type: String,
    required: true,
    default: "Data",
  },
  headers: {
    type: Array,
    required: false,
    default: null,
  },
  toHide: {
    type: Array,
    required: false,
    default: () => [],
  },
});

// Utility: snake_case → Title Case
function toTitleCase(str) {
  return str
    .replace(/_/g, " ")
    .replace(/\w\S*/g, (txt) =>
      txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
    );
}

// Final headers: use provided headers OR infer from first row
const resolvedHeaders = computed(() => {
  let baseHeaders = [];
  if (props.headers && props.headers.length > 0) {
    baseHeaders = props.headers.map((h) =>
      typeof h === "string"
        ? { key: h, label: toTitleCase(h) }
        : { key: h.key, label: h.label ?? toTitleCase(h.key) }
    );
  } else if (props.rows && props.rows.length > 0) {
    baseHeaders = Object.keys(props.rows[0]).map((key) => ({
      key,
      label: toTitleCase(key),
    }));
  }

  // remove hidden columns
  return baseHeaders.filter(h => !props.toHide.includes(h.key));
});
</script>

<template>
  <div class="overflow-x-auto">
    <!-- Fallback: No data -->
    <div
      v-if="!rows || rows.length === 0"
      class="p-6 text-center text-gray-500 dark:text-gray-400"
    >
      No {{ tableTitle }} found!
    </div>

    <!-- Table -->
    <table v-else class="min-w-full border-separate border-spacing-0">
      <!-- Table Head -->
      <thead>
        <tr>
          <th
            v-for="header in resolvedHeaders"
            :key="header.key"
            class="px-4 py-3 text-left text-sm font-semibold text-white bg-black"
          >
            {{ header.label }}
          </th>
          <!-- Actions column -->
          <th class="px-4 py-3 text-left text-sm font-semibold text-white bg-black">
            Actions
          </th>
        </tr>
      </thead>

      <!-- Table Body -->
      <tbody>
        <tr v-for="(row, index) in rows" :key="index">
          <td
            v-for="header in resolvedHeaders"
            :key="header.key"
            class="px-4 py-3 text-sm border-t border-black dark:border-white"
          >
            {{ row[header.key] }}
          </td>

          <!-- Actions slot -->
          <td class="px-4 py-3 text-sm border-t border-black dark:border-white">
            <slot name="actions" :row="row" :index="index" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

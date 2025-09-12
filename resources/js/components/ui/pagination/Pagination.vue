<script setup>
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import Button from "@/components/ui/button/Button.vue";

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  from: { type: Number, required: true },
  to: { type: Number, required: true },
  total: { type: Number, required: true },
});

const emit = defineEmits(["page-changed"]);

function getVisiblePages() {
  const current = props.currentPage;
  const last = props.lastPage;
  const delta = 1; // tighter range
  const range = [];
  const rangeWithDots = [];
  let l;

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }

  range.forEach((i) => {
    if (l) {
      if (i - l === 2) rangeWithDots.push(l + 1);
      else if (i - l !== 1) rangeWithDots.push("...");
    }
    rangeWithDots.push(i);
    l = i;
  });

  return rangeWithDots;
}

function changePage(page) {
  if (page >= 1 && page <= props.lastPage) emit("page-changed", page);
}
</script>

<template>
  <div class="w-full flex justify-center mb-2">
    <div
      v-if="lastPage > 1"
      class="flex items-center justify-center border-2 border-primary bg-secondary w-fit rounded-xl"
    >
      <!-- Previous -->
      <Button
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        variant="outline"
        size="sm"
        class="rounded-r-none"
      >
        <ChevronLeft class="h-4 w-4" />
      </Button>

      <!-- Pages -->
      <template v-for="page in getVisiblePages()" :key="page">
        <Button
          v-if="page !== '...'"
          @click="changePage(page)"
          size="sm"
          :class="[
            page === currentPage
              ? 'bg-primary'
              : 'bg-secondary',
            'rounded-none px-4',
          ]"
        >
          {{ page }}
        </Button>
        <span v-else class="px-2 text-gray-500 text-xs select-none">...</span>
      </template>

      <!-- Next -->
      <Button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === lastPage"
        variant="outline"
        size="sm"
        class="rounded-l-none"
      >
        <ChevronRight class="h-4 w-4" />
      </Button>
    </div>
  </div>
</template>

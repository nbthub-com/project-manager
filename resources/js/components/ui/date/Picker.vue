<script setup>
import { ref, computed, onMounted } from "vue";
import Button from "../button/Button.vue";
import { ArrowLeft, ArrowRight } from "lucide-vue-next";

const showPicker = ref(false);
const selectedDate = ref(new Date());

// For navigation
const viewYear = ref(selectedDate.value.getFullYear());
const viewMonth = ref(selectedDate.value.getMonth());

// Month/Year label
const monthYearLabel = computed(() =>
  new Intl.DateTimeFormat("en-US", { month: "long", year: "numeric" }).format(
    new Date(viewYear.value, viewMonth.value)
  )
);

// Build days
const daysInMonth = computed(() => {
  const firstDay = new Date(viewYear.value, viewMonth.value, 1).getDay();
  const totalDays = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
  return { firstDay, totalDays };
});

// Format for display (Y-m-d)
const formatDate = () =>
  selectedDate.value ? selectedDate.value.toLocaleDateString("en-CA") : "";

// Month navigation
const prevMonth = () => {
  if (viewMonth.value === 0) {
    viewMonth.value = 11;
    viewYear.value--;
  } else {
    viewMonth.value--;
  }
};
const nextMonth = () => {
  if (viewMonth.value === 11) {
    viewMonth.value = 0;
    viewYear.value++;
  } else {
    viewMonth.value++;
  }
};

// Close modal on ESC
const handleEsc = (e) => {
  if (e.key === "Escape") showPicker.value = false;
};
onMounted(() => {
  window.addEventListener("keydown", handleEsc);
});
</script>

<template>
  <div class="relative w-full">
    <!-- Trigger -->
    <div
    @click="showPicker = true"
    class="w-full cursor-pointer select-none px-2 py-2 rounded-md
            text-foreground text-sm border
            hover:border-primary/40 hover:bg-primary/5
            transition"
    >
    {{ formatDate() || "Select a date" }}
    </div>

    <!-- Overlay + Modal -->
    <transition name="fade">
      <div
        v-if="showPicker"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        @click.self="showPicker = false"
      >
        <transition name="scale">
          <!-- Calendar Modal -->
          <div
            v-if="showPicker"
            class="w-[20rem] rounded-xl shadow-xl shadow-card bg-card text-foreground"
          >
            <!-- Header -->
            <div
              class="flex items-center justify-between bg-primary text-background px-3 py-2 rounded-t-xl"
            >
              <Button
                @click="prevMonth"
                class="active:bg-secondary bg-primary"
                type="button"
              >
                <ArrowLeft />
              </Button>
              <span class="font-medium text-white">{{ monthYearLabel }}</span>
              <Button @click="nextMonth" type="button"><ArrowRight /></Button>
            </div>

            <!-- Weekdays -->
            <div
              class="grid grid-cols-7 text-xs text-center py-2 text-foreground font-bold"
            >
              <span
                v-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"
                :key="d"
              >
                {{ d }}
              </span>
            </div>

            <!-- Days -->
            <div class="grid grid-cols-7 gap-1 text-center text-sm p-2">
              <!-- Empty slots -->
              <div v-for="n in daysInMonth.firstDay" :key="'e'+n"></div>

              <!-- Real days -->
              <div
                v-for="day in daysInMonth.totalDays"
                :key="day"
                class="cursor-pointer rounded-lg py-1 hover:bg-secondary transition"
                :class="{
                  'bg-primary text-white font-extralight':
                    selectedDate.getDate() === day &&
                    selectedDate.getMonth() === viewMonth &&
                    selectedDate.getFullYear() === viewYear
                }"
                @click="
                  selectedDate = new Date(viewYear, viewMonth, day);
                  showPicker = false;
                "
              >
                {{ day }}
              </div>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* Fade for overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Scale/slide for modal */
.scale-enter-active,
.scale-leave-active {
  transition: all 0.25s ease;
}
.scale-enter-from {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}
.scale-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}
</style>

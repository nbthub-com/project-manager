<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { defineEmits } from "vue";
import Button from "../button/Button.vue";
import { ArrowLeft, ArrowRight } from "lucide-vue-next";

const props = defineProps({
  // v-model for the date value (can be Date or 'YYYY-MM-DD' string)
  modelValue: { type: [String, Date], default: null },

  // 'date' (Date object) or 'string' ('YYYY-MM-DD')
  valueType: { type: String, default: "date" },

  // optional: allow parent to control visibility via v-model:open
  open: { type: Boolean, default: undefined },
});

const emit = defineEmits(["update:modelValue", "update:open"]);

// Utility: parse incoming prop into a Date object (robust-ish)
function parseToDate(v) {
  if (v == null) return null;
  if (v instanceof Date && !isNaN(v)) return new Date(v);
  // try Date parse first (ISO / other)
  const d = new Date(v);
  if (!isNaN(d)) return d;
  // fallback for common YYYY-MM-DD
  const parts = String(v).split("-");
  if (parts.length === 3) {
    const [y, m, day] = parts.map(Number);
    return new Date(y, m - 1, day);
  }
  return null;
}

// Controlled vs uncontrolled for date
const isDateControlled = computed(() => props.modelValue !== null && props.modelValue !== undefined);

// internal date ref (used when uncontrolled)
const internalDate = ref(parseToDate(props.modelValue) ?? new Date());

// keep internal in sync if parent changes modelValue
watch(
  () => props.modelValue,
  (v) => {
    if (isDateControlled.value) {
      const parsed = parseToDate(v);
      if (parsed) internalDate.value = parsed;
    }
  }
);

// computed accessor used inside component (auto-unwraps in template)
const selectedDate = computed({
  get() {
    return isDateControlled.value ? parseToDate(props.modelValue) : internalDate.value;
  },
  set(val) {
    if (!val) {
      if (isDateControlled.value) emit("update:modelValue", null);
      internalDate.value = null;
      return;
    }
    const d = new Date(val);
    if (props.valueType === "string") {
      emit("update:modelValue", d.toLocaleDateString("en-CA")); // YYYY-MM-DD
    } else {
      emit("update:modelValue", d);
    }
    internalDate.value = d;
  },
});

// Controlled vs uncontrolled for visibility (v-model:open)
const isOpenControlled = computed(() => props.open !== undefined && props.open !== null);
const localOpen = ref(Boolean(props.open ?? false));
watch(
  () => props.open,
  (v) => {
    if (isOpenControlled.value) localOpen.value = !!v;
  }
);
watch(localOpen, (v) => {
  if (isOpenControlled.value) emit("update:open", v);
});

// expose as a reactive flag used in template
const showPicker = localOpen;

// initialize view month/year based on selected date
function getInitialDate() {
  return selectedDate.value ? new Date(selectedDate.value) : new Date();
}
const viewYear = ref(getInitialDate().getFullYear());
const viewMonth = ref(getInitialDate().getMonth());

// update view when selectedDate changes externally
watch(
  () => selectedDate.value,
  (d) => {
    const date = d ? new Date(d) : new Date();
    viewYear.value = date.getFullYear();
    viewMonth.value = date.getMonth();
  }
);

// month/year label
const monthYearLabel = computed(() =>
  new Intl.DateTimeFormat("en-US", { month: "long", year: "numeric" }).format(
    new Date(viewYear.value, viewMonth.value)
  )
);

// days in current view
const daysInMonth = computed(() => {
  const firstDay = new Date(viewYear.value, viewMonth.value, 1).getDay();
  const totalDays = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
  return { firstDay, totalDays };
});

// navigation
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

// selecting day
function selectDay(day) {
  const d = new Date(viewYear.value, viewMonth.value, day);
  selectedDate.value = d; // will emit or update internal
  showPicker.value = false;
}

// helper to test if day is selected
function isSelected(day) {
  const d = selectedDate.value;
  if (!d) return false;
  return (
    d.getDate() === day &&
    d.getMonth() === viewMonth.value &&
    d.getFullYear() === viewYear.value
  );
}

// display format (Y-m-d)
const displayDate = computed(() => {
  const d = selectedDate.value;
  return d ? d.toLocaleDateString("en-CA") : "";
});

// ESC handling + cleanup
const handleEsc = (e) => {
  if (e.key === "Escape") showPicker.value = false;
};
onMounted(() => {
  window.addEventListener("keydown", handleEsc);
});
onBeforeUnmount(() => {
  window.removeEventListener("keydown", handleEsc);
});
</script>

<template>
  <div class="relative w-full">
    <!-- Trigger -->
    <div
      @click="showPicker = true"
      class="w-full cursor-pointer select-none px-2 py-2 rounded-md text-foreground text-sm border hover:border-primary/40 hover:bg-primary/5 transition"
    >
      {{ displayDate || "Select a date" }}
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
          <div class="w-[20rem] rounded-xl shadow-xl shadow-card bg-card text-foreground">
            <!-- Header -->
            <div class="flex items-center justify-between bg-primary text-background px-3 py-2 rounded-t-xl">
              <Button @click="prevMonth" class="active:bg-secondary bg-primary" type="button">
                <ArrowLeft />
              </Button>
              <span class="font-medium text-white">{{ monthYearLabel }}</span>
              <Button @click="nextMonth" type="button"><ArrowRight /></Button>
            </div>

            <!-- Weekdays -->
            <div class="grid grid-cols-7 text-xs text-center py-2 text-foreground font-bold">
              <span v-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']" :key="d">{{ d }}</span>
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
                :class="{ 'bg-primary text-white font-extralight': isSelected(day) }"
                @click="selectDay(day)"
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

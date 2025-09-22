<template>
  <div
    class="fixed z-50 flex flex-col gap-2 p-4"
    :class="positionClass"
  >
    <transition-group name="fade" tag="div">
      <div
        v-for="n in notifications"
        :key="n.id"
        class="flex items-center gap-3 px-4 py-2 rounded-xl shadow-lg text-white"
        :class="n.color"
      >
        <component :is="n.icon" v-if="n.icon" class="w-5 h-5" />
        <span class="flex-1">{{ n.message }}</span>
        <button
          v-if="n.dismissable"
          class="ml-2 text-white/80 hover:text-white"
          @click="dismiss(n.id)"
        >
          ✕
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"

const notifications = ref([])

const positions = {
  "top-right": "top-4 right-4 items-end",
  "top-left": "top-4 left-4 items-start",
  "bottom-right": "bottom-4 right-4 items-end",
  "bottom-left": "bottom-4 left-4 items-start",
}

const position = ref("top-right")
const positionClass = computed(() => positions[position.value] || positions["top-right"])

function show({ message, color = "bg-blue-600", icon = null, timeout = 3000, dismissable = true, pos = "top-right" }) {
  console.log("Notification.show called with:", message)
  position.value = pos
  const id = Date.now()
  notifications.value.push({ id, message, color, icon, dismissable })
  
  if (timeout) {
    setTimeout(() => dismiss(id), timeout)
  }
}

function dismiss(id) {
  notifications.value = notifications.value.filter((n) => n.id !== id)
}

// Expose globally
defineExpose({ show })

// Debug: Log when component is mounted
onMounted(() => {
  console.log("Notification component mounted")
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
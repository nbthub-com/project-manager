<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import { ref, provide, watch, onMounted } from "vue"
import { usePage } from "@inertiajs/vue3"
import Notification from "@/components/ui/notification/Notification.vue"

// Notifier ref (exposed from Notification.vue)
const notifier = ref(null)
provide("notifier", notifier)

// Breadcrumbs props
defineProps({
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
})

// Inertia flash messages
const page = usePage()

// Function to show flash messages
function showFlashMessages() {
  const flash = page.props.flash
  
  if (flash?.success) {
    console.log("Showing success notification:", flash.success)
    notifier.value?.show({
      message: flash.success,
      color: "bg-green-600",
      pos: "top-right",
    })
  }
  
  if (flash?.error) {
    console.log("Showing error notification:", flash.error)
    notifier.value?.show({
      message: flash.error,
      color: "bg-red-600",
      pos: "top-right",
    })
  }
  
  // Also check for validation errors
  const errors = page.props.errors
  if (errors && Object.keys(errors).length > 0) {
    const firstError = Object.values(errors)[0]
    console.log("Showing validation error:", firstError)
    notifier.value?.show({
      message: firstError,
      color: "bg-red-600",
      pos: "top-right",
    })
  }
}

// On mount, check for flash messages
onMounted(() => {
  showFlashMessages()
})

// Watch for changes in flash messages
watch(
  () => page.props.flash,
  (flash) => {
    if (flash) {
      showFlashMessages()
    }
  },
  { deep: true }
)
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
    <!-- Global Notification handler -->
    <Notification ref="notifier" />
  </AppLayout>
</template>
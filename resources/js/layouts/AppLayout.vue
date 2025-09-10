<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import { ref, provide, watch } from "vue"
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

// Watch for Laravel flash (success/error)
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
    console.log("Happened")
      notifier.value?.show({
        message: flash.success,
        color: "bg-green-600",
        pos: "top-right",
      })
    }
    if (flash?.error) {
      notifier.value?.show({
        message: flash.error,
        color: "bg-red-600",
        pos: "top-right",
      })
    }
  },
  { deep: true, immediate: true }
)
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
    <!-- Global Notification handler -->
    <Notification ref="notifier" />
  </AppLayout>
</template>

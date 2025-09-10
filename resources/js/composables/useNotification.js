import { inject } from "vue"

export function useNotification() {
  const notifier = inject("notifier")
  if (!notifier) {
    throw new Error("Notification system is not mounted. Did you include <Notification /> in AppLayout.vue?")
  }
  return notifier
}

<script setup>
import Button from "@/components/ui/button/Button.vue";
import Input from "@/components/ui/input/Input.vue";
import Viewer from "@/components/ui/md/viewer.vue";
import { getInitials } from "@/composables/useInitials";
import AppLayout from "@/layouts/AppLayout.vue";
import { toTitleCase } from "@/lib/utils";
import { Head } from "@inertiajs/vue3";
import { Activity, BarChart3, Calendar, Gauge, Loader2, Plus, TimerIcon } from "lucide-vue-next";
import { defineProps } from "vue";

const props = defineProps(["project"]);
const breadcrumbs = [
  {
    title: `${toTitleCase(props.project.title)} - Project`,
    href: `/client/projects/${props.project.id}`,
  },
];
function formatDate(date) {
  return new Intl.DateTimeFormat("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(date));
}
</script>

<template>
  <Head :title="toTitleCase(project.title) + ' - Project'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col sm:flex-row w-full h-full">
      <div class="w-full sm:w-[30%] h-full p-4 bg-primary/20">
        <p class="text-2xl font-extrabold mb-2">
          {{ toTitleCase(project.title) }}
        </p>
        <!-- Manager -->
        <div
          class="flex items-center flex-row gap-3 mb-2 border border-primary/40 bg-white/10 dark:bg-gray-800/30 rounded-xl p-3 shadow-sm"
        >
          <div class="flex flex-row gap-3">
            <div
              class="flex w-10 h-10 items-center justify-center rounded-full bg-primary text-white font-bold"
            >
              {{ getInitials(project.manager.name) }}
            </div>
            <div>
              <p class="text-xs text-gray-400">Managed by</p>
              <p class="text-base font-semibold text-gray-900 dark:text-gray-100">
                {{ toTitleCase(project.manager.name) }}
              </p>
            </div>
          </div>
          <div class="flex flex-row gap-3">
            <div
              class="flex w-10 h-10 items-center justify-center rounded-full bg-primary text-white font-bold"
            >
              <Gauge />
            </div>
            <div>
              <p class="text-xs text-gray-400">Status</p>
              <span
                class="inline-block text-md font-semibold"
                :class="{
                  'text-green-500': project.status === 'completed',
                  'text-yellow-500': project.status === 'in_progress',
                  'text-blue-500': project.status === 'pending',
                  'text-purple-500': project.status === 'review',
                  'text-orange-500': project.status === 'testing',
                  'text-red-500': project.status === 'cancelled',
                }"
              >
                {{ toTitleCase(project.status.replace("_", " ")) }}
              </span>
            </div>
          </div>
        </div>
        <div class="overflow-auto max-h-[420px] border-b-2">
          <Viewer :source="project.description" />
        </div>
      </div>
      <div class="w-full sm:w-[70%] h-full p-1">
        <div class="w-full flex flex-row gap-2 p-1">
            <Input class="w-[40%]" />
            <div>
                <Button>Add <Plus /></Button>
            </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { defineProps } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  CheckCircle,
  Clock,
  XCircle,
  FlaskConical,
  ListTodo,
  TrendingUp,
  Zap,
} from "lucide-vue-next";

const breadcrumbs = [{ title: "Client", href: "/client" }];

interface Stats {
  tasks: {
    completed: number;
    pending: number;
    in_progress: number;
    cancelled: number;
    review: number;
    testing: number;
    total: number;
  };
  projects: {
    completed: number;
    pending: number;
    in_progress: number;
    review: number;
    cancelled: number;
    testing: number;
    total: number;
  };
}

const props = defineProps<{
  taskStats: Stats["tasks"];
  projectStats: Stats["projects"];
}>();

const stats = {
  tasks: props.taskStats,
  projects: props.projectStats,
};

const icons = {
  completed: CheckCircle,
  pending: Clock,
  cancelled: XCircle,
  testing: FlaskConical,
  total: ListTodo,
};

// Gradient colors per status (consistent styling)
const gradients = {
  completed: "from-green-500 to-emerald-600",
  pending: "from-yellow-500 to-amber-600",
  cancelled: "from-red-500 to-pink-600",
  testing: "from-purple-500 to-indigo-600",
  total: "from-blue-500 to-sky-600",
};
</script>

<template>
  <Head title="Client Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <!-- Projects & Tasks Progress -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
        <!-- Projects Progress -->
        <div
          class="lg:col-span-1 group relative overflow-hidden rounded-2xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-purple-600 to-purple-950 opacity-90"
          ></div>
          <div class="relative p-4 text-white">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold flex items-center gap-2">
                <TrendingUp class="w-5 h-5" />
                Projects Status ({{ stats.projects.total }})
              </h2>
            </div>
            <div class="space-y-3">
              <template
                v-for="(count, key) in {
                  pending: stats.projects.pending,
                  running: stats.projects.in_progress,
                  testing: stats.projects.testing,
                  review: stats.projects.review,
                  completed: stats.projects.completed,
                  cancelled: stats.projects.cancelled,
                }"
                :key="key"
              >
                <div>
                  <div class="flex justify-between mb-1">
                    <span class="text-md font-medium capitalize">{{ key }}</span>
                    <span class="text-md font-bold"
                      >{{ count }} ({{
                        stats.projects.total > 0 && count
                          ? Math.round((count / stats.projects.total) * 100)
                          : 0
                      }}%)</span
                    >
                  </div>
                  <div class="w-full bg-white/20 rounded-full h-fit">
                    <div
                      class="h-2 rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-yellow-400': key === 'pending',
                        'bg-purple-400': key === 'testing',
                        'bg-indigo-400': key === 'review',
                        'bg-blue-400': key === 'running',
                        'bg-green-400': key === 'completed',
                        'bg-red-400': key === 'cancelled',
                      }"
                      :style="{
                        width: `${
                          (stats.projects.total > 0 && count)
                            ? (count / stats.projects.total) * 100
                            : 0
                        }%`,
                      }"
                    ></div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Tasks Progress -->
        <div
          class="group relative overflow-hidden rounded-2xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-blue-600 to-blue-950 opacity-90"
          ></div>
          <div class="relative p-4 text-white">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold flex items-center gap-2">
                <TrendingUp class="w-5 h-5" />
                Tasks Status ({{ stats.tasks.total }})
              </h2>
            </div>
            <div class="space-y-3">
              <template
                v-for="(count, key) in {
                  pending: stats.tasks.pending,
                  running: stats.tasks.in_progress,
                  testing: stats.tasks.testing,
                  review: stats.tasks.review,
                  completed: stats.tasks.completed,
                  cancelled: stats.tasks.cancelled,
                }"
                :key="key"
              >
                <div>
                  <div class="flex justify-between mb-1">
                    <span class="text-md font-medium capitalize">{{ key }}</span>
                    <span class="text-md font-bold"
                      >{{ count }} ({{
                        stats.tasks.total > 0 && count
                          ? Math.round((count / stats.tasks.total) * 100)
                          : 0
                      }}%)</span
                    >
                  </div>
                  <div class="w-full bg-white/20 rounded-full h-fit">
                    <div
                      class="h-2 rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-yellow-400': key === 'pending',
                        'bg-purple-400': key === 'testing',
                        'bg-indigo-400': key === 'review',
                        'bg-blue-400': key === 'running',
                        'bg-green-400': key === 'completed',
                        'bg-red-400': key === 'cancelled',
                      }"
                      :style="{
                        width: `${
                          (stats.tasks.total > 0 && count) ? (count / stats.tasks.total) * 100 : 0
                        }%`,
                      }"
                    ></div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
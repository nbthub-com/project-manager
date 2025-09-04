<!-- Admin Dashboard -->
<script setup lang="js">
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Users, ClipboardList, CheckCircle2 } from 'lucide-vue-next';

const breadcrumbs = [
  { title: 'Dashboard', href: '/admin' },
];

const props = defineProps(['stats']);
</script>

<template>
  <Head title="Admin Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- Top Stats Grid -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Users -->
        <div
          class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm"
        >
          <div class="absolute right-3 top-3 opacity-10">
            <Users class="h-12 w-12" />
          </div>
          <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">
            Total Users
          </h2>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ props.stats.user_count }}
          </p>
        </div>

        <!-- Projects -->
        <div
          class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm"
        >
          <div class="absolute right-3 top-3 opacity-10">
            <ClipboardList class="h-12 w-12" />
          </div>
          <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">
            Active Projects
          </h2>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ props.stats.project_count ?? 0 }}
          </p>
        </div>

        <!-- Completed Tasks -->
        <div
          class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm"
        >
          <div class="absolute right-3 top-3 opacity-10">
            <CheckCircle2 class="h-12 w-12" />
          </div>
          <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">
            Completed Tasks
          </h2>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ props.stats.completed_tasks ?? 0 }}
          </p>
        </div>
      </div>

      <!-- Lower Section -->
      <div
        class="relative min-h-[50vh] rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-6"
      >
        <PlaceholderPattern />
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-100">
          Recent Activity
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
          (Here you can later show logs, recent tasks, or system activity.)
        </p>
      </div>
    </div>
  </AppLayout>
</template>

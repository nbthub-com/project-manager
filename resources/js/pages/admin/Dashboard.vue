<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { defineProps } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  FolderKanban,
  ListChecks,
  UserCheck,
  Users,
  TrendingUp,
  Zap,
} from "lucide-vue-next";

const breadcrumbs = [{ title: "Dashboard", href: "/admin" }];

interface Stats {
  members: {
    total: number;
    managers: number;
  };
  projects: {
    total: number;
    running: number;
    completed: number;
    cancelled: number;
  };
  tasks: {
    total: number;
    pending: number;
    running: number;
    completed: number;
    cancelled: number;
  };
}

defineProps<{ stats: Stats }>();
</script>

<template>
  <Head title="Admin Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <!-- Stats Overview Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Members -->
        <div
          class="group relative overflow-hidden rounded-2xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-[#5a248a] to-[#8a4fc2] opacity-90"
          ></div>
          <div class="relative p-4 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-md opacity-80">Total Members</p>
                <p class="text-2xl font-bold mt-1">{{ stats.members.total }}</p>
              </div>
              <div
                class="p-2 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors"
              >
                <Users class="w-5 h-5" />
              </div>
            </div>
          </div>
        </div>

        <!-- Total Managers -->
        <div
          class="group relative overflow-hidden rounded-2xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] opacity-90"
          ></div>
          <div class="relative p-4 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-md opacity-80">Total Managers</p>
                <p class="text-2xl font-bold mt-1">{{ stats.members.managers }}</p>
              </div>
              <div
                class="p-2 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors"
              >
                <UserCheck class="w-5 h-5" />
              </div>
            </div>
          </div>
        </div>

        <!-- Total Projects -->
        <div
          class="group relative overflow-hidden rounded-2xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-[#10b981] to-[#34d399] opacity-90"
          ></div>
          <div class="relative p-4 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-md opacity-80">Total Projects</p>
                <p class="text-2xl font-bold mt-1">{{ stats.projects.total }}</p>
              </div>
              <div
                class="p-2 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors"
              >
                <FolderKanban class="w-5 h-5" />
              </div>
            </div>
          </div>
        </div>

        <!-- Total Tasks -->
        <div
          class="group relative overflow-hidden rounded-2xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-[#f59e0b] to-[#fbbf24] opacity-90"
          ></div>
          <div class="relative p-4 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-md opacity-80">Total Tasks</p>
                <p class="text-2xl font-bold mt-1">{{ stats.tasks.total }}</p>
              </div>
              <div
                class="p-2 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors"
              >
                <ListChecks class="w-5 h-5" />
              </div>
            </div>
          </div>
        </div>
      </div>

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
                Projects Status
              </h2>
              <div class="flex items-center gap-1">
                <Zap class="w-4 h-4 text-yellow-300 animate-pulse" />
                <span class="text-md font-medium">Live</span>
              </div>
            </div>
            <div class="space-y-3">
              <template
                v-for="(count, key) in {
                  running: stats.projects.running,
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
                        stats.projects.total > 0
                          ? Math.round((count / stats.projects.total) * 100)
                          : 0
                      }}%)</span
                    >
                  </div>
                  <div class="w-full bg-white/20 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-blue-400': key === 'running',
                        'bg-green-400': key === 'completed',
                        'bg-red-400': key === 'cancelled',
                      }"
                      :style="{
                        width: `${
                          stats.projects.total > 0
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
                Tasks Status
              </h2>
              <div class="flex items-center gap-1">
                <Zap class="w-4 h-4 text-yellow-300 animate-pulse" />
                <span class="text-md font-medium">Live</span>
              </div>
            </div>
            <div class="space-y-3">
              <template
                v-for="(count, key) in {
                  pending: stats.tasks.pending,
                  running: stats.tasks.running,
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
                        stats.tasks.total > 0
                          ? Math.round((count / stats.tasks.total) * 100)
                          : 0
                      }}%)</span
                    >
                  </div>
                  <div class="w-full bg-white/20 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-yellow-400': key === 'pending',
                        'bg-blue-400': key === 'running',
                        'bg-green-400': key === 'completed',
                        'bg-red-400': key === 'cancelled',
                      }"
                      :style="{
                        width: `${
                          stats.tasks.total > 0 ? (count / stats.tasks.total) * 100 : 0
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

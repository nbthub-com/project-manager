<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import { 
  FolderKanban, 
  ListChecks, 
  CheckCircle,
  Clock,
  XCircle,
  TrendingUp,
  Zap,
  Target,
  Star
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/' },
];

interface Stats {
  tasks: {
    total: number;
    pending: number;
    running: number;
    completed: number;
    cancelled: number;
  };
  projects: {
    total: number;
    running: number;
    completed: number;
    cancelled: number;
  };
}

defineProps<{ stats: Stats }>();
</script>

<template>
  <Head title="User Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-3">

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tasks Card -->
        <div class="group relative overflow-hidden rounded-2xl shadow-xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-purple-950 opacity-90"></div>
          <div class="relative p-4 text-white">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold flex items-center gap-2">
                <ListChecks class="w-5 h-5" />
                Your Tasks
              </h2>
              <div class="p-2 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors">
                <Target class="w-5 h-5" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">Total</p>
                <p class="text-2xl font-bold mt-1">{{ stats.tasks.total }}</p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">Completed</p>
                <p class="text-2xl font-bold mt-1 flex items-center justify-center gap-1">
                  {{ stats.tasks.completed }}
                  <CheckCircle class="w-4 h-4 text-green-300" />
                </p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">In Progress</p>
                <p class="text-2xl font-bold mt-1 flex items-center justify-center gap-1">
                  {{ stats.tasks.running }}
                  <Clock class="w-4 h-4 text-yellow-300" />
                </p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">Pending</p>
                <p class="text-2xl font-bold mt-1">{{ stats.tasks.pending }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Projects Card -->
        <div class="group relative overflow-hidden rounded-2xl shadow-xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-green-600 to-green-950 opacity-90"></div>
          <div class="relative p-4 text-white">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold flex items-center gap-2">
                <FolderKanban class="w-5 h-5" />
                Your Projects
              </h2>
              <div class="p-2 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors">
                <Star class="w-5 h-5" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">Total</p>
                <p class="text-2xl font-bold mt-1">{{ stats.projects.total }}</p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">Completed</p>
                <p class="text-2xl font-bold mt-1 flex items-center justify-center gap-1">
                  {{ stats.projects.completed }}
                  <CheckCircle class="w-4 h-4 text-green-300" />
                </p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">In Progress</p>
                <p class="text-2xl font-bold mt-1 flex items-center justify-center gap-1">
                  {{ stats.projects.running }}
                  <FolderKanban class="w-4 h-4 text-cyan-300" />
                </p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl text-center transform transition duration-300 hover:scale-105">
                <p class="text-sm opacity-80">Cancelled</p>
                <p class="text-2xl font-bold mt-1 text-red-200">{{ stats.projects.cancelled }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Progress Bars -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tasks Progress -->
        <div class="group relative overflow-hidden rounded-2xl shadow-xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-blue-950 opacity-90"></div>
          <div class="relative p-4 text-white">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold flex items-center gap-2">
                <TrendingUp class="w-5 h-5" />
                Tasks Progress
              </h2>
              <div class="flex items-center gap-1">
                <Zap class="w-4 h-4 text-yellow-300 animate-pulse" />
                <span class="text-md font-medium">Live</span>
              </div>
            </div>
            <div class="space-y-3">
              <template v-for="(count, key) in {pending: stats.tasks.pending, running: stats.tasks.running, completed: stats.tasks.completed, cancelled: stats.tasks.cancelled}" :key="key">
                <div>
                  <div class="flex justify-between mb-1">
                    <div class="flex items-center gap-1">
                      <component
                        :is="{
                          pending: Clock,
                          running: ListChecks,
                          completed: CheckCircle,
                          cancelled: XCircle
                        }[key]"
                        class="w-3 h-3"
                        :class="{
                          'text-yellow-300': key === 'pending',
                          'text-blue-300': key === 'running',
                          'text-green-300': key === 'completed',
                          'text-red-300': key === 'cancelled'
                        }"
                      />
                      <span class="text-md font-medium">{{ key.charAt(0).toUpperCase() + key.slice(1) }}</span>
                    </div>
                    <span class="text-md font-bold">{{ count }} ({{ stats.tasks.total > 0 ? Math.round((count / stats.tasks.total) * 100) : 0 }}%)</span>
                  </div>
                  <div class="w-full bg-white/20 rounded-full h-2 overflow-hidden">
                    <div 
                      class="h-2 rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-yellow-400': key === 'pending',
                        'bg-blue-400': key === 'running',
                        'bg-green-400': key === 'completed',
                        'bg-red-400': key === 'cancelled'
                      }"
                      :style="{ width: `${stats.tasks.total > 0 ? (count / stats.tasks.total) * 100 : 0}%`, boxShadow: '0 0 8px currentColor' }"
                    ></div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
        
        <!-- Projects Progress -->
        <div class="group relative overflow-hidden rounded-2xl shadow-xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-orange-600 to-orange-950 opacity-90"></div>
          <div class="relative p-4 text-white">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold flex items-center gap-2">
                <TrendingUp class="w-5 h-5" />
                Projects Progress
              </h2>
              <div class="flex items-center gap-1">
                <Zap class="w-4 h-4 text-yellow-300 animate-pulse" />
                <span class="text-md font-medium">Live</span>
              </div>
            </div>
            <div class="space-y-3">
              <template v-for="(count, key) in {running: stats.projects.running, completed: stats.projects.completed, cancelled: stats.projects.cancelled}" :key="key">
                <div>
                  <div class="flex justify-between mb-1">
                    <div class="flex items-center gap-1">
                      <component
                        :is="{
                          running: FolderKanban,
                          completed: CheckCircle,
                          cancelled: XCircle
                        }[key]"
                        class="w-3 h-3"
                        :class="{
                          'text-blue-300': key === 'running',
                          'text-green-300': key === 'completed',
                          'text-red-300': key === 'cancelled'
                        }"
                      />
                      <span class="text-md font-medium">{{ key.charAt(0).toUpperCase() + key.slice(1) }}</span>
                    </div>
                    <span class="text-md font-bold">{{ count }} ({{ stats.projects.total > 0 ? Math.round((count / stats.projects.total) * 100) : 0 }}%)</span>
                  </div>
                  <div class="w-full bg-white/20 rounded-full h-2 overflow-hidden">
                    <div 
                      class="h-2 rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-blue-400': key === 'running',
                        'bg-green-400': key === 'completed',
                        'bg-red-400': key === 'cancelled'
                      }"
                      :style="{ width: `${stats.projects.total > 0 ? (count / stats.projects.total) * 100 : 0}%`, boxShadow: '0 0 8px currentColor' }"
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

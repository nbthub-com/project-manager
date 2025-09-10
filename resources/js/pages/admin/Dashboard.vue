<!-- Admin Dashboard -->
<script setup lang="js">
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
  FolderKanban, 
  ListChecks, 
  UserCheck, 
  Users,
  TrendingUp,
  Calendar,
  Activity,
  BarChart3,
  PieChart
} from 'lucide-vue-next';

const breadcrumbs = [
  { title: 'Dashboard', href: '/admin' },
];

const props = defineProps(['stats']);
</script>

<template>
  <Head title="Admin Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6">
      <!-- Stats Overview Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2  md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Members -->
        <div class="group relative overflow-hidden rounded-xl shadow-lg bg-white transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-[#5a248a] to-[#8a4fc2] opacity-90"></div>
          <div class="relative p-6 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm font-medium opacity-80">Total Members</p>
                <p class="text-3xl font-bold mt-2">{{ stats.members.total }}</p>
              </div>
              <div class="p-3 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors">
                <Users class="w-6 h-6" />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Total Managers -->
        <div class="group relative overflow-hidden rounded-xl shadow-lg bg-white transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] opacity-90"></div>
          <div class="relative p-6 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm font-medium opacity-80">Total Managers</p>
                <p class="text-3xl font-bold mt-2">{{ stats.members.managers }}</p>
              </div>
              <div class="p-3 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors">
                <UserCheck class="w-6 h-6" />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Total Projects -->
        <div class="group relative overflow-hidden rounded-xl shadow-lg bg-white transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-[#10b981] to-[#34d399] opacity-90"></div>
          <div class="relative p-6 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm font-medium opacity-80">Total Projects</p>
                <p class="text-3xl font-bold mt-2">{{ stats.projects.total }}</p>
              </div>
              <div class="p-3 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors">
                <FolderKanban class="w-6 h-6" />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Total Tasks -->
        <div class="group relative overflow-hidden rounded-xl shadow-lg bg-white transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="absolute inset-0 bg-gradient-to-br from-[#f59e0b] to-[#fbbf24] opacity-90"></div>
          <div class="relative p-6 text-white">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm font-medium opacity-80">Total Tasks</p>
                <p class="text-3xl font-bold mt-2">{{ stats.tasks.total }}</p>
              </div>
              <div class="p-3 rounded-full bg-white/20 group-hover:bg-white/30 transition-colors">
                <ListChecks class="w-6 h-6" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts and Status Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Projects Status -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 transition-all duration-300 hover:shadow-xl">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Projects Status</h2>
          </div>
          
          <div class="space-y-5">
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Running</span>
                <span class="font-medium text-gray-800">{{ stats.projects.running }} ({{ Math.round((stats.projects.running / stats.projects.total) * 100) }}%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-blue-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.projects.running / stats.projects.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
            
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Completed</span>
                <span class="font-medium text-gray-800">{{ stats.projects.completed }} ({{ Math.round((stats.projects.completed / stats.projects.total) * 100) }}%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-green-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.projects.completed / stats.projects.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
            
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Cancelled</span>
                <span class="font-medium text-gray-800">{{ stats.projects.cancelled }} ({{ Math.round((stats.projects.cancelled / stats.projects.total) * 100) }}%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-red-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.projects.cancelled / stats.projects.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Tasks Status -->
        <div class="bg-white rounded-xl shadow-lg p-6 transition-all duration-300 hover:shadow-xl">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Tasks Status</h2>
          </div>
          
          <div class="space-y-5">
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Pending</span>
                <span class="font-medium text-gray-800">{{ stats.tasks.pending }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-yellow-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.tasks.pending / stats.tasks.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
            
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Running</span>
                <span class="font-medium text-gray-800">{{ stats.tasks.running }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-blue-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.tasks.running / stats.tasks.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
            
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Completed</span>
                <span class="font-medium text-gray-800">{{ stats.tasks.completed }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-green-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.tasks.completed / stats.tasks.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
            
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-700 font-medium">Cancelled</span>
                <span class="font-medium text-gray-800">{{ stats.tasks.cancelled }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  class="bg-red-500 h-3 rounded-full transition-all duration-500 ease-in-out"
                  :style="{
                    width: `${(stats.tasks.cancelled / stats.tasks.total) * 100}%`,
                  }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
  </AppLayout>
</template>
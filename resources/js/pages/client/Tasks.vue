<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { defineProps } from "vue";
import { formatDate, toTitleCase } from "@/lib/utils";
import { AlertTriangle, Calendar, ChevronDown, Flame, Leaf } from "lucide-vue-next";
import { ref } from "vue";
import Pagination from "@/components/ui/pagination/Pagination.vue";
import Dialog from "@/components/ui/simpledialog/Dialog.vue";
import Viewer from "@/components/ui/md/viewer.vue";

const props = defineProps(["tasks"]);
const viewTask = ref();
const isViewDialogOpen = ref(false);

function openTask(task) {
  viewTask.value = task;
  isViewDialogOpen.value = true;
}
const showDescription = ref(true);
</script>

<template>
  <AppLayout
    :breadcrumbs="[
      {
        title: 'Tasks',
      },
    ]"
  >
    <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3">
      <template v-if="props.tasks?.data?.length">
        <div
          v-for="task in props.tasks.data"
          :key="task.id"
          class="p-4 rounded-xl shadow-lg text-white transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
          :class="
            task.status === 'cancelled'
              ? 'bg-red-600'
              : 'bg-gradient-to-br from-[#5a248a] to-secondary'
          "
        >
          <!-- Task Info -->
          <div>
            <div
              class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
            >
              <h3 class="text-lg font-bold flex flex-row">
                <span @click="openTask(task)" class="cursor-pointer hover:underline">{{
                  task.title
                }}</span>
                <span class="font-extralight text-[15px] h-full gap-2 flex flex-row">
                  ({{ task.id }})
                </span>
              </h3>
            </div>
          </div>
          <div class="flex flex-col gap-1">
            <p class="text-sm opacity-90">
              Project:
              <span class="font-bold capitalize text-foreground">{{ toTitleCase(task.project) }}</span>
            </p>
            <p class="text-sm opacity-90">
              Manager:
              <span class="font-bold capitalize text-foreground">{{ toTitleCase(task.manager) }}</span>
            </p>
            <!-- Priority and Deadline -->
            <div class="flex justify-between items-center mt-2">
              <div class="flex items-center gap-1">
                <Calendar class="w-4 h-4" />
                <span class="text-xs font-medium">
                  {{ formatDate(task.deadline) }}
                </span>
              </div>
            </div>
          </div>
          <div
            class="mt-4 flex items-center justify-between border-t border-white/20 pt-2"
          >
            <div
              class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold capitalize"
              :class="{
                'border-2 border-red-300 text-red-300': task.priority === 'high',
                'border-2 border-yellow-400 text-yellow-400': task.priority === 'medium',
                'border-2 border-green-400 text-green-400': task.priority === 'low',
              }"
            >
              <Flame v-if="task.priority === 'high'" class="w-4 h-4 text-red-300" />
              <AlertTriangle
                v-else-if="task.priority === 'medium'"
                class="w-4 h-4 text-yellow-400"
              />
              <Leaf v-else class="w-4 h-4 text-green-400" />
              <span>{{ task.priority || "medium" }}</span>
            </div>
            <span
              class="px-2 py-1 rounded-full text-sm font-semibold capitalize"
              :class="{
                'bg-yellow-100 text-yellow-800': task.status === 'pending',
                'bg-blue-100 text-blue-800': task.status === 'in_progress',
                'bg-purple-100 text-purple-800': task.status === 'testing',
                'bg-indigo-100 text-indigo-800': task.status === 'review',
                'bg-green-100 text-green-800': task.status === 'completed',
                'bg-red-100 text-red-800': task.status === 'cancelled',
              }"
            >
              {{ task.status.replace("_", " ") }}
            </span>
          </div>
        </div>
      </template>
      <template v-else>
        <div
          class="col-span-full flex flex-col items-center justify-center py-12 px-4 text-center text-gray-500 dark:text-gray-400"
        >
          <p class="text-lg font-medium">No tasks found</p>
          <p class="text-sm opacity-80">
            {{
              true ? "Try adjusting your filters." : "Looks like there are no tasks yet."
            }}
          </p>
        </div>
      </template>
    </div>
    <!-- Use the new Pagination component -->
    <Pagination
      v-if="props.tasks"
      :current-page="props.tasks.current_page"
      :last-page="props.tasks.last_page"
      :from="props.tasks.from"
      :to="props.tasks.to"
      :total="props.tasks.total"
    />
  </AppLayout>
  <Dialog v-model="isViewDialogOpen">
    <!-- Header -->
    <template #header>
      <div class="flex items-center gap-2">
        <h2 class="text-xl font-bold flex items-center gap-2 capitalize">
          {{ toTitleCase(viewTask.title) }}
        </h2>
        <span
          class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
          :class="{
            'bg-yellow-100 text-yellow-800': viewTask.status === 'pending',
            'bg-blue-100 text-blue-800': viewTask.status === 'in_progress',
            'bg-purple-100 text-purple-800': viewTask.status === 'testing',
            'bg-indigo-100 text-indigo-800': viewTask.status === 'review',
            'bg-green-100 text-green-800': viewTask.status === 'completed',
            'bg-red-100 text-red-800': viewTask.status === 'cancelled',
          }"
        >
          {{ toTitleCase(viewTask.status.replace("_", " ")) }}
        </span>
      </div>
    </template>
    <!-- Body -->
    <template #body>
      <div v-if="viewTask" class="flex flex-col gap-6 text-sm p-2 animate-fadeIn">
        <!-- Info grid -->
        <div class="grid grid-cols-3">
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-wide text-gray-500">Manager</p>
            <p class="font-semibold captitalize ">{{ viewTask.manager }}</p>
          </div>
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-wide text-gray-500">Project</p>
            <p class="font-semibold captitalize">{{ viewTask.project }}</p>
          </div>
          <!-- Priority and Deadline -->
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-wide text-gray-500">Priority</p>
            <p class="font-semibold capitalize">{{ viewTask.priority || "medium" }}</p>
          </div>
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-wide text-gray-500">Deadline</p>
            <p class="font-semibold">{{ formatDate(viewTask.deadline) }}</p>
          </div>
        </div>
        <!-- Description -->
        <div class="space-y-1">
          <div
            class="w-full flex items-center justify-between text-left cursor-pointer select-none"
            @click="showDescription = !showDescription"
          >
            <p class="text-xs uppercase tracking-wide text-gray-500">Description</p>
            <ChevronDown
              class="w-4 h-4 transition-transform duration-300"
              :class="{ 'rotate-270': !showDescription }"
            />
          </div>

          <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[500px]"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="opacity-100 max-h-[500px]"
            leave-to-class="opacity-0 max-h-0"
          >
            <div v-show="showDescription" class="overflow-hidden">
              <Viewer :source="viewTask.description" />
            </div>
          </transition>
        </div>
      </div>
    </template>
  </Dialog>
</template>

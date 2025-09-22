<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { defineProps, ref } from "vue";
import { toTitleCase } from "@/lib/utils";
import { Calendar, ChevronDown } from "lucide-vue-next";
import Pagination from "@/components/ui/pagination/Pagination.vue";
import Dialog from "@/components/ui/simpledialog/Dialog.vue";
import Viewer from "@/components/ui/md/viewer.vue";

const props = defineProps(["projects"]);
const viewProject = ref();
const isViewDialogOpen = ref(false);

function openProject(project) {
  viewProject.value = project;
  isViewDialogOpen.value = true;
}
const showDescription = ref(false);
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: 'Projects' }]">
    <div class="grid gap-6 grid-cols-1 sm:grid-cols-3 md:grid-cols-4 p-3">
      <template v-if="props.projects?.data?.length">
        <div
          v-for="project in props.projects.data"
          :key="project.id"
          class="p-4 rounded-xl shadow-lg text-white transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
          :class="
            project.status === 'cancelled'
              ? 'bg-red-600'
              : 'bg-gradient-to-br from-[#5a248a] to-secondary'
          "
        >
          <!-- Project Info -->
          <div>
            <div
              class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
            >
              <h3 class="text-lg font-bold flex flex-row">
                <span
                  @click="openProject(project)"
                  class="cursor-pointer hover:underline"
                  >{{ project.title }}</span
                >
                <span class="font-extralight text-[15px] h-full gap-2 flex flex-row">
                  ({{ project.id }})
                </span>
              </h3>
            </div>
          </div>

          <div class="flex flex-col gap-1">
            <p class="text-sm opacity-90">
              Manager:
              <span class="font-bold capitalize text-foreground">
                {{ project.manager }}
              </span>
            </p>
            <p class="text-sm opacity-90">
              Tasks:
              <span class="font-bold">{{ project.task_count }}</span>
            </p>
          </div>
              <p class="rounded-md px-2 py-1 mt-1 text-sm bg-gradient-to-r from-primary to-primary/50">
                {{
                  project.description.length > 255
                    ? project.description.slice(0, 255) + "..."
                    : project.description
                }}
              </p>

          <!-- Status -->
          <div
            class="mt-4 flex items-center justify-between border-t border-white/20 pt-2"
          >
            <span
              class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
              :class="{
                'bg-yellow-100 text-yellow-800': project.status === 'pending',
                'bg-blue-100 text-blue-800': project.status === 'in_progress',
                'bg-purple-100 text-purple-800': project.status === 'testing',
                'bg-indigo-100 text-indigo-800': project.status === 'review',
                'bg-green-100 text-green-800': project.status === 'completed',
                'bg-red-100 text-red-800': project.status === 'cancelled',
              }"
            >
              {{ project.status.replace("_", " ") }}
            </span>
          </div>
        </div>
      </template>

      <!-- No projects fallback -->
      <template v-else>
        <div
          class="col-span-full flex flex-col items-center justify-center py-12 px-4 text-center text-gray-500 dark:text-gray-400"
        >
          <p class="text-lg font-medium">No projects found</p>
          <p class="text-sm opacity-80">
            {{
              true
                ? "Try adjusting your filters."
                : "Looks like there are no projects yet."
            }}
          </p>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <Pagination
      v-if="props.projects"
      :current-page="props.projects.current_page"
      :last-page="props.projects.last_page"
      :from="props.projects.from"
      :to="props.projects.to"
      :total="props.projects.total"
    />
  </AppLayout>

  <!-- Project Details Dialog -->
  <Dialog v-model="isViewDialogOpen">
    <!-- Header -->
    <template #header>
      <div class="flex items-center gap-2">
        <h2 class="text-xl font-bold flex items-center gap-2 capitalize">
          {{ viewProject.title }}
        </h2>
      </div>
    </template>

    <!-- Body -->
    <template #body>
      <div v-if="viewProject" class="flex flex-col gap-6 text-xl p-2 animate-fadeIn">
        <!-- Info grid -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Manager</p>
            <p class="font-semibold captitalize">{{ viewProject.manager }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Tasks</p>
            <p class="font-semibold captitalize">{{ viewProject.task_count }}</p>
          </div>
        </div>

        <!-- Description collapsible -->
        <div>
          <div
            class="w-full flex items-center justify-between text-left cursor-pointer select-none"
            @click="showDescription = !showDescription"
          >
            <p class="text-xs uppercase tracking-wide text-gray-500">Description</p>
            <ChevronDown
              class="w-4 h-4 transition-transform duration-300"
              :class="{ 'rotate-180': showDescription }"
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
              <Viewer :source="viewProject.description" />
            </div>
          </transition>
        </div>
        <div>
          <span
            class="px-2 py-1 rounded-full text-sm font-semibold capitalize"
            :class="{
              'bg-yellow-100 text-yellow-800': viewProject.status === 'pending',
              'bg-blue-100 text-blue-800': viewProject.status === 'in_progress',
              'bg-purple-100 text-purple-800': viewProject.status === 'testing',
              'bg-indigo-100 text-indigo-800': viewProject.status === 'review',
              'bg-green-100 text-green-800': viewProject.status === 'completed',
              'bg-red-100 text-red-800': viewProject.status === 'cancelled',
            }"
          >
            {{ viewProject.status.replace("_", " ") }}
          </span>
        </div>
      </div>
    </template>
  </Dialog>
</template>

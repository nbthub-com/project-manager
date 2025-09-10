<script setup lang="js">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { defineProps, ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Table from '@/components/ui/table/Table.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Edit, Eye, Search } from 'lucide-vue-next';
import Dialog from '@/components/ui/simpleidalog/Dialog.vue';
import InputError from '@/components/InputError.vue';
import Select from '@/components/ui/select/Select.vue';

const breadcrumbs = [
  { title: 'Projects', href: '/admin/projects' },
];

const props = defineProps({
  projects: Array,
  names: Array,
});

const page = usePage();
const s_query = ref('');
// Use a reactive copy of projects that we can update
const projectsList = ref([...props.projects]);
const filteredProjects = ref([...props.projects]);
const isDialogOpen = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const isViewDialogOpen = ref(false);
const viewProject = ref(null);

// useForm for project creation + editing
const form = useForm({
  title: '',
  manager: '',
  description: '',
  is_starred: false,
  status: 'in_progress',
});

// Create manager options for the Select component
const managerOptions = computed(() => {
  return props.names.map(name => ({ label: name, value: name }));
});

// Create status options for the Select component
const statusOptions = [
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
];

// Update projectsList when props change (e.g., after navigation)
watch(() => props.projects, (newProjects) => {
  projectsList.value = [...newProjects];
  search(); // Refresh the filtered list
}, { deep: true });

// Search handling
watch(s_query, () => search());

function search() {
  if (!s_query.value.trim()) {
    filteredProjects.value = [...projectsList.value];
    return;
  }
  const query = s_query.value.toLowerCase();
  filteredProjects.value = projectsList.value.filter(project =>
    project.title.toLowerCase().includes(query) ||
    project.manager_name.toLowerCase().includes(query) ||
    (project.description && project.description.toLowerCase().includes(query)) ||
    project.status.toLowerCase().includes(query)
  );
}

// Create or update project
function submitForm() {
  if (isEditMode.value && editId.value) {
    form.put(`/admin/projects/update/${editId.value}`, {
      onSuccess: (page) => {
        // Update the local projects list with the response
        projectsList.value = page.props.projects;
        search(); // Refresh the filtered list
        isDialogOpen.value = false;
        form.reset();
      }
    });
  } else {
    form.post('/admin/projects/create', {
      onSuccess: (page) => {
        // Update the local projects list with the response
        projectsList.value = page.props.projects;
        search(); // Refresh the filtered list
        isDialogOpen.value = false;
        form.reset();
      }
    });
  }
}

function editProject(project) {
  isEditMode.value = true;
  editId.value = project.id;
  form.title = project.title;
  form.manager = project.manager_name;
  form.description = project.description;
  form.is_starred = project.is_starred;
  form.status = project.status;
  isDialogOpen.value = true;
}

function deleteProject(id) {
  if (confirm('Are you sure you want to delete this project?')) {
    router.delete(`/admin/projects/delete/${id}`, {
      onSuccess: (page) => {
        // Update the local projects list with the response
        projectsList.value = page.props.projects;
        search(); // Refresh the filtered list
      }
    });
  }
}

function openViewDialog(project) {
  viewProject.value = project;
  isViewDialogOpen.value = true;
}

// Helper function to format status text
function formatStatus(status) {
  return status.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
}
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col p-2">
      <!-- Search + Add -->
      <div
        class="border-b-2 flex flex-row justify-between p-1 items-center gap-2 rounded-lg"
      >
        <div class="flex flex-row rounded-lg">
          <Input
            v-model="s_query"
            placeholder="Search projects..."
            class="transition-all duration-300 ease-in-out rounded-r-none"
          />
          <Button class="rounded-l-none" @click="search" aria-label="Search projects">
            <Search />
          </Button>
        </div>
        <Button
          @click="
            () => {
              isEditMode = false;
              form.reset();
              form.status = 'in_progress'; // Reset status to default
              isDialogOpen = true;
            }
          "
          >+ New Project</Button
        >
      </div>
      <!-- Projects table -->
      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3">
        <template v-if="filteredProjects.length">
          <div
            v-for="project in filteredProjects"
            :key="project.id"
            class="p-4 rounded-xl shadow-lg text-white bg-gradient-to-br from-[#5a248a] to-secondary transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
          >
            <!-- Header: Title + Delete -->
            <div
              class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
            >
              <h3 class="text-lg font-bold">{{ project.title }}</h3>
              <button
                class="text-red-200 hover:text-red-400 cursor-pointer"
                @click="deleteProject(project.id)"
                title="Delete Project"
              >
                ✕
              </button>
            </div>
            <!-- Body: Manager + Description -->
            <div class="flex flex-col gap-1 text-sm opacity-90">
              <p>
                Manager: <span class="font-bold">{{ project.manager_name }}</span>
              </p>
              <p>{{ project.description }}</p>
            </div>
            <!-- Footer -->
            <div
              class="mt-4 flex items-center justify-between border-t border-white/20 pt-2"
            >
              <div class="flex gap-2">
                <span
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-blue-200 text-blue-800': project.status === 'in_progress',
                    'bg-green-200 text-green-800': project.status === 'completed',
                    'bg-red-200 text-red-800': project.status === 'cancelled',
                  }"
                >
                  {{ formatStatus(project.status) }}
                </span>
                <span
                  v-if="project.is_starred"
                  class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"
                >
                  ⭐
                </span>
              </div>
              <div class="flex gap-2">
                <Eye
                  class="w-5 h-5 text-white cursor-pointer hover:text-gray-200"
                  @click="openViewDialog(project)"
                />
                <Edit
                  class="w-5 h-5 text-white cursor-pointer hover:text-gray-200"
                  @click="editProject(project)"
                />
              </div>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <template v-else>
          <div
            class="col-span-full flex flex-col items-center justify-center py-16 text-gray-500 dark:text-gray-400"
          >
            <p class="text-lg font-medium">No Project found</p>
            <p class="text-sm opacity-80">
              {{
                s_query
                  ? "Try adjusting your search terms."
                  : "Looks like there are no tasks yet."
              }}
            </p>
            <Button
              @click="
                () => {
                  isEditMode = false;
                  form.reset();
                  form.status = 'in_progress';
                  isDialogOpen = true;
                }
              "
              class="m-3"
            >
              + Start First Project
            </Button>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
  <!-- Add/Edit Project Dialog -->
  <Dialog v-model="isDialogOpen">
    <template #header>
      <div class="flex justify-between items-center w-full">
        <h2 class="text-lg font-semibold">
          {{ isEditMode ? "Edit Project" : "Add New Project" }}
        </h2>
      </div>
    </template>
    <template #body>
      <form @submit.prevent="submitForm" class="flex flex-col gap-3">
        <!-- Title -->
        <div>
          <label class="block text-sm font-medium mb-1">Title</label>
          <Input v-model="form.title" placeholder="Project Title" />
          <InputError :message="form.errors.title" />
        </div>
        <!-- Manager -->
        <div>
          <label class="block text-sm font-medium mb-1">Manager</label>
          <Select
            v-model="form.manager"
            :options="managerOptions"
            placeholder="Select a manager"
            class="w-full"
          />
          <InputError :message="form.errors.manager" />
        </div>
        <!-- Description -->
        <div>
          <label class="block text-sm font-medium mb-1">Description</label>
          <textarea
            v-model="form.description"
            placeholder="Description"
            class="border rounded p-2 text-sm w-full"
            rows="3"
          ></textarea>
          <InputError :message="form.errors.description" />
        </div>
        <!-- Status (only on edit) -->
        <div v-if="isEditMode" class="flex flex-col gap-1">
          <label class="block text-sm font-medium mb-1">Status</label>
          <Select v-model="form.status" :options="statusOptions" class="w-full" />
          <InputError :message="form.errors.status" />
        </div>
        <!-- Starred checkbox -->
        <div class="flex items-center gap-2">
          <input
            type="checkbox"
            id="is_starred"
            v-model="form.is_starred"
            class="rounded border-gray-300"
          />
          <label for="is_starred" class="text-sm font-medium">Starred</label>
        </div>
        <InputError :message="form.errors.is_starred" />
      </form>
    </template>
    <template #footer>
      <Button variant="outline" @click="isDialogOpen = false"> Cancel </Button>
      <Button @click="submitForm" :disabled="form.processing">
        {{ isEditMode ? "Update" : "Create" }}
      </Button>
    </template>
  </Dialog>
  <!-- View Project Dialog -->
  <Dialog v-model="isViewDialogOpen">
    <template #header>
      <div class="flex justify-between items-center w-full">
        <h2 class="text-lg font-semibold">Project Details</h2>
      </div>
    </template>
    <template #body>
      <div v-if="viewProject" class="flex flex-col gap-4 text-sm p-2">
        <!-- Title & Manager -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="font-medium text-gray-600 dark:text-gray-300">Title</p>
            <p class="text-base">{{ viewProject.title }}</p>
          </div>
          <div>
            <p class="font-medium text-gray-600 dark:text-gray-300">Manager</p>
            <p class="text-base">{{ viewProject.manager_name }}</p>
          </div>
        </div>
        <!-- Description -->
        <div>
          <p class="font-medium text-gray-600 dark:text-gray-300 mb-1">Description</p>
          <div
            class="w-full p-2 border rounded-md text-sm bg-gray-50 dark:bg-gray-900 dark:text-white min-h-[100px]"
          >
            {{ viewProject.description || "—" }}
          </div>
        </div>
        <!-- Status & Starred -->
        <div class="flex flex-row items-center gap-4">
          <div>
            <p class="font-medium text-gray-600 dark:text-gray-300">Status</p>
            <span
              class="px-2 py-1 rounded-full text-xs font-semibold"
              :class="{
                'bg-blue-100 text-blue-700': viewProject.status === 'in_progress',
                'bg-green-100 text-green-700': viewProject.status === 'completed',
                'bg-red-100 text-red-700': viewProject.status === 'cancelled',
              }"
            >
              {{ formatStatus(viewProject.status) }}
            </span>
          </div>
          <div>
            <p class="font-medium text-gray-600 dark:text-gray-300">Starred</p>
            <span
              class="px-2 py-1 rounded-full text-xs font-semibold"
              :class="
                viewProject.is_starred
                  ? 'bg-yellow-100 text-yellow-700'
                  : 'bg-gray-200 text-gray-600'
              "
            >
              {{ viewProject.is_starred ? "⭐ Yes" : "No" }}
            </span>
          </div>
        </div>
      </div>
    </template>
    <template #footer>
      <Button @click="isViewDialogOpen = false">Close</Button>
    </template>
  </Dialog>
</template>

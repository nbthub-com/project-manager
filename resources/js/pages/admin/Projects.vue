<script setup lang="js">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { defineProps, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Table from '@/components/ui/table/Table.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Search } from 'lucide-vue-next';
import Dialog from '@/components/ui/simpleidalog/Dialog.vue';
import InputError from '@/components/InputError.vue';
import Select from '@/components/ui/select/select.vue';

const breadcrumbs = [
  { title: 'Projects', href: '/admin/projects' },
];

const props = defineProps({
  projects: Array,
  names: Array,
});

const page = usePage();
const s_query = ref('');
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
  status: 'inprogress',
});

// Search handling
watch(s_query, () => search());

function search() {
  if (!s_query.value.trim()) {
    filteredProjects.value = [...props.projects];
    return;
  }
  const query = s_query.value.toLowerCase();
  filteredProjects.value = props.projects.filter(project =>
    project.title.toLowerCase().includes(query) ||
    project.manager_name.toLowerCase().includes(query) ||
    (project.description && project.description.toLowerCase().includes(query))
  );
}

// Create or update project
function submitForm() {
  if (isEditMode.value && editId.value) {
    form.post(`/admin/projects/update/${editId.value}`, {
      onSuccess: () => {
        isDialogOpen.value = false;
        router.visit('/admin/projects');
      }
    });
  } else {
    form.post('/admin/projects/create', {
      onSuccess: () => {
        isDialogOpen.value = false;
        router.visit('/admin/projects');
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
      onSuccess: () => {
        router.visit('/admin/projects');
      }
    });
  }
}

function openViewDialog(project) {
  viewProject.value = project;
  isViewDialogOpen.value = true;
}
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <!-- Search + Add -->
      <div
        class="border-b-2 flex flex-row justify-between p-1 items-center gap-2 outline-1 rounded-lg"
      >
        <div class="flex flex-row">
          <Input
            v-model="s_query"
            class="w-[70%] focus:w-[90%] transition-all duration-300 ease-in-out rounded-r-none"
          />
          <Button class="rounded-l-none" variant="outline" @click="search">
            <Search />
          </Button>
        </div>
        <Button
          @click="
            () => {
              isEditMode = false;
              form.reset();
              isDialogOpen = true;
            }
          "
          >+ Add</Button
        >
      </div>

      <!-- Projects table -->
      <Table :rows="filteredProjects" :toHide="['description']" table-title="Projects">
        <template #actions="{ row }">
          <a class="hover:underline cursor-pointer" @click="openViewDialog(row)">View</a>
          &sdot;
          <a class="hover:underline cursor-pointer" @click="editProject(row)">Edit</a>
          &sdot;
          <a class="hover:underline cursor-pointer" @click="deleteProject(row.id)"
            >Delete</a
          >
        </template>
      </Table>
    </div>
  </AppLayout>

  <!-- Add/Edit Project Dialog -->
  <Dialog v-model="isDialogOpen">
    <template #header>
      <h2 class="text-lg font-semibold">
        {{ isEditMode ? "Edit Project" : "Add New Project" }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="submitForm" class="flex flex-col gap-3">
        <!-- Title -->
        <Input v-model="form.title" placeholder="Project Title" />
        <InputError :message="form.errors.title" />

        <!-- Manager -->
        <Select v-model="form.manager" class="border rounded p-2 text-sm">
          <option v-for="name in props.names" :key="name" :value="name">
            {{ name }}
          </option>
        </Select>
        <InputError :message="form.errors.manager" />

        <!-- Description -->
        <textarea
          v-model="form.description"
          placeholder="Description"
          class="border rounded p-2 text-sm"
        ></textarea>
        <InputError :message="form.errors.description" />

        <!-- Status (only on edit) -->
        <div v-if="isEditMode" class="flex flex-col gap-1">
          <label>Status</label>
          <Select v-model="form.status">
            <option value="inprogress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="aborted">Aborted</option>
          </Select>
        </div>

        <!-- Starred checkbox -->
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_starred" />
          <span>Starred</span>
        </label>
      </form>
    </template>
    <template #footer>
      <Button @click="isDialogOpen = false">Cancel</Button>
      <Button @click="submitForm" :disabled="form.processing">
        {{ isEditMode ? "Update" : "Create" }}
      </Button>
    </template>
  </Dialog>

  <Dialog v-model="isViewDialogOpen">
    <template #header>
      <h2 class="text-lg font-semibold">Project Details</h2>
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
          <textarea
            class="w-full p-2 border rounded-md text-sm bg-gray-50 dark:bg-gray-900 dark:text-white"
            rows="4"
            readonly
            >{{ viewProject.description || "—" }}</textarea
          >
        </div>

        <!-- Status & Starred -->
        <div class="flex flex-row items-center gap-4">
          <div>
            <p class="font-medium text-gray-600 dark:text-gray-300">Status</p>
            <span
              class="px-2 py-1 rounded-full text-xs font-semibold"
              :class="{
                'bg-blue-100 text-blue-700': viewProject.status === 'inprogress',
                'bg-green-100 text-green-700': viewProject.status === 'completed',
                'bg-red-100 text-red-700': viewProject.status === 'aborted',
              }"
            >
              {{ viewProject.status }}
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

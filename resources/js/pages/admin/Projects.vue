<script setup lang="js">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { defineProps, ref, watch, onMounted } from 'vue';
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

// useForm for project creation
const form = useForm({
  title: '',
  manager: '',
  description: '',
  is_starred: false,
});

// Watch for changes in search query
watch(s_query, (newValue) => {
  search();
});

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

function submitForm() {
  form.post('/admin/projects/add', {
    preserveScroll: false, // Changed to false to allow page reload
    onSuccess: () => {
      // Reload the page to get updated projects list
      router.visit('/admin/projects');
    }
  });
}
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <!-- Search + Add -->
      <div class="border-b-2 flex flex-row justify-between outline-1 p-1 rounded-lg items-center gap-2 ">
        <div class="flex flex-row">
          <Input
            v-model="s_query"
            class="w-[70%] focus:w-[90%] transition-all duration-300 ease-in-out rounded-r-none"
          />
          <Button class="rounded-l-none" variant="outline" @click="search">
            <Search />
          </Button>
        </div>
        <Button @click="isDialogOpen = true">+ Add</Button>
      </div>
      <!-- Projects table -->
      <Table :rows="filteredProjects" table-title="Projects" />
    </div>
  </AppLayout>
  <!-- Add Project Dialog -->
  <Dialog v-model="isDialogOpen">
    <template #header>
      <h2 class="text-lg font-semibold">Add New Project</h2>
    </template>
    <template #body>
      <form @submit.prevent="submitForm" class="flex flex-col gap-3">
        <!-- Title -->
        <Input v-model="form.title" placeholder="Project Title" />
        <InputError :message="form.errors.title" class="text-red-500" />
        <!-- Manager -->
        <Select v-model="form.manager" class="border rounded p-2 text-sm">
          <option class="bg-white text-black dark:bg-black dark:text-white" v-for="name in props.names" :key="name" :value="name">
            {{ name }}
          </option>
        </Select>
        <InputError :message="form.errors.manager" class="text-red-500" />
        <!-- Description -->
        <textarea
          v-model="form.description"
          placeholder="Description"
          class="border rounded p-2 text-sm"
        ></textarea>
        <InputError :message="form.errors.description" class="text-red-500" />
        <!-- Starred checkbox -->
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_starred" />
          <span>Starred</span>
        </label>
        <InputError :message="form.errors.is_starred" class="text-red-500" />
      </form>
    </template>
    <template #footer>
      <Button @click="isDialogOpen = false" type="button">Cancel</Button>
      <Button @click="submitForm" :disabled="form.processing" type="button">
        Create
      </Button>
    </template>
  </Dialog>
</template>
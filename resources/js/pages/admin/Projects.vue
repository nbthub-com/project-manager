You said:
<script setup lang="js">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { defineProps, ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Edit, Search, Filter, X, Plus, WindArrowDown, ChevronDown } from 'lucide-vue-next';
import Dialog from '@/components/ui/simpledialog/Dialog.vue';
import InputError from '@/components/InputError.vue';
import Select from '@/components/ui/select/Select.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import Viewer from '@/components/ui/md/viewer.vue';

const breadcrumbs = [
  { title: 'Projects', href: '/admin/projects' },
];
const isOpen = ref(false)
const props = defineProps({
  projects: Object,
  clients: Array,
  managers: Array,
  filters: Object,
});

const page = usePage();

const filterId = ref(props.filters?.filter_id || '');
const filterManager = ref(props.filters?.filter_manager || '');
const filterStatus = ref(props.filters?.filter_status || '');
const filterStarred = ref(props.filters?.filter_starred !== undefined ? props.filters?.filter_starred : '');
const perPage = ref(props.filters?.per_page || 10);
const isFilterOpen = ref(false);

// Form for search and pagination
const filterForm = useForm({
  filter_id: filterId.value,
  filter_manager: filterManager.value,
  filter_status: filterStatus.value,
  filter_starred: filterStarred.value,
  per_page: perPage.value,
  page: props.filters?.page
});

// Watch for filter changes
watch(filterId, (value) => {
  filterForm.filter_id = value;
  filterForm.page = 1;
  applyFilters();
});

watch(filterManager, (value) => {
  filterForm.filter_manager = value;
  filterForm.page = 1; // Reset to first page when filtering
  applyFilters();
});

watch(filterStatus, (value) => {
  filterForm.filter_status = value;
  filterForm.page = 1; // Reset to first page when filtering
  applyFilters();
});

watch(filterStarred, (value) => {
  filterForm.filter_starred = value;
  filterForm.page = 1; // Reset to first page when filtering
  applyFilters();
});

watch(perPage, (value) => {
  filterForm.per_page = value;
  filterForm.page = 1; // Reset to first page
  applyFilters();
});

// Apply filters with debounce
let debounceTimeout;
function applyFilters() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    filterForm.get("/admin/projects", {
      preserveState: true,
      preserveScroll: true,
    });
  }, 300);
}

// Handle page change from pagination component
function handlePageChange(page) {
  filterForm.page = page;
  filterForm.get("/admin/projects", {
    preserveState: true,
    preserveScroll: true,
  });
}

// Reset all filters
function resetFilters() {
  filterId.value = '';
  filterManager.value = '';
  filterStatus.value = '';
  filterStarred.value = '';
  filterForm.filter_id = '';
  filterForm.filter_manager = '';
  filterForm.filter_status = '';
  filterForm.filter_starred = '';
  filterForm.page = 1;
  applyFilters();
}

// Check if any filters are active
function hasActiveFilters() {
  return filterId.value ||
         filterManager.value ||
         filterStatus.value ||
         filterStarred.value !== '';
}

// Prepare options for dropdowns
const managerOptions = computed(() => {
  return [
    { label: "All Managers", value: "" },
    ...props.managers.map(manager => ({ label: manager, value: manager }))
  ];
});

const statusOptions = computed(() => {
  return [
    { label: "All Statuses", value: "" },
    { label: "Pending", value: "pending" },
    { label: "In Progress", value: "in_progress" },
    { label: "Testing", value: "testing" },
    { label: "Review", value: "review" },
    { label: "Completed", value: "completed" },
    { label: "Cancelled", value: "cancelled" }
  ];
});

const starredOptions = [
  { label: "All", value: "" },
  { label: "Starred", value: "true" },
  { label: "Not Starred", value: "false" }
];

const isDialogOpen = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const isViewDialogOpen = ref(false);
const viewProject = ref(null);

// useForm for project creation + editing
const form = useForm({
  title: '',
  manager: '',
  client: '',
  description: '',
  is_starred: false,
  status: 'in_progress',
});

// Create manager options for the Select component
const managerFormOptions = computed(() => {
  return props.managers.map(name => ({ label: name, value: name }));
});

const clients = computed(() => {
  return props.clients.map(name => ({ label: name, value: name }))
})

// Create status options for the Select component
const statusFormOptions = [
  { label: "All Statuses", value: "" },
  { label: "Pending", value: "pending" },
  { label: "In Progress", value: "in_progress" },
  { label: "Testing", value: "testing" },
  { label: "Review", value: "review" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

// Create or update project
function submitForm() {
  if (isEditMode.value && editId.value) {
    form.put(`/admin/projects/update/${editId.value}`, {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      }
    });
  } else {
    form.post('/admin/projects/create', {
      onSuccess: () => {
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
  form.manager = project.manager.name;
  form.description = project.description;
  form.is_starred = project.is_starred;
  form.status = project.status;
  form.client = project.client.name;
  isDialogOpen.value = true;
}

function deleteProject(id) {
  if (confirm('Are you sure you want to delete this project?')) {
    router.delete(`/admin/projects/delete/${id}`, {
      preserveScroll: true,
    });
  }
}

function openViewDialog(project) {
  viewProject.value = project;
  isViewDialogOpen.value = true;
}

// Helper function to format status text
function formatStatus(status) {
  return status.replace(/([A-Z])/g, ' $1').replace("_", ' ').replace(/^./, str => str.toUpperCase());
}
const showDescription = ref(false);
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col px-1">
      <!-- Search + Filters + Add -->
      <div class="border-b-2 flex flex-row justify-between p-1 items-center gap-2">
        <div class="w-full sm:w-sm flex flex-row">
          <Input
            v-model="filterId"
            placeholder="Search by ID..."
            class="transition-all duration-300 ease-in-out rounded-r-none"
          />
          <Button class="rounded-none outline-1" @click="applyFilters">
            <Search />
          </Button>
          <Button
            class="border-l-[1px] rounded-l-none outline-1 relative"
            @click="isFilterOpen = true"
            :class="{ 'bg-primary text-white': hasActiveFilters() }"
          >
            <Filter />
            <span
              v-if="hasActiveFilters()"
              class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs"
            >
              {{
                [filterId, filterManager, filterStatus, filterStarred].filter(Boolean)
                  .length
              }}
            </span>
          </Button>
        </div>

        <div class="flex items-center space-x-2">
          <Select
            v-model="perPage"
            class="rounded text-sm"
            :options="[
              { value: 5, label: '5/pg' },
              { value: 10, label: '10/pg' },
              { value: 20, label: '20/pg' },
              { value: 50, label: '50/pg' },
            ]"
          >
          </Select>

          <Button
            @click="
              () => {
                isEditMode = false;
                form.reset();
                form.status = 'in_progress'; // Reset status to default
                isDialogOpen = true;
              }
            "
            ><Plus />
            <div class="hidden sm:block">New Project</div></Button
          >
        </div>
      </div>

      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2 mt-2 mb-1">
        <div
          v-if="filterId"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          ID: {{ filterId }}
          <button
            @click="
              filterId = '';
              applyFilters();
            "
            class="ml-1"
          >
            <X class="h-3 w-3" />
          </button>
        </div>
        <div
          v-if="filterManager"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Manager: {{ filterManager }}
          <button
            @click="
              filterManager = '';
              applyFilters();
            "
            class="ml-1"
          >
            <X class="h-3 w-3" />
          </button>
        </div>
        <div
          v-if="filterStatus"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Status: {{ formatStatus(filterStatus) }}
          <button
            @click="
              filterStatus = '';
              applyFilters();
            "
            class="ml-1"
          >
            <X class="h-3 w-3" />
          </button>
        </div>
        <div
          v-if="filterStarred !== ''"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Starred: {{ filterStarred === "true" ? "Yes" : "No" }}
          <button
            @click="
              filterStarred = '';
              applyFilters();
            "
            class="ml-1"
          >
            <X class="h-3 w-3" />
          </button>
        </div>
        <Button variant="outline" size="sm" @click="resetFilters" class="text-xs">
          Clear All
        </Button>
      </div>

      <!-- Projects table -->
      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3">
        <template v-if="props.projects?.data?.length">
          <div
            v-for="project in props.projects.data"
            :key="project.id"
            class="p-4 rounded-xl shadow-lg text-white bg-gradient-to-br from-[#5a248a] to-secondary transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
          >
            <!-- Header: Title + Delete -->
            <div
              class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
            >
              <h3
                class="text-lg font-bold cursor-pointer hover:underline"
                @click="openViewDialog(project)"
              >
                {{ project.title }}
              </h3>
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
                Manager: <span class="font-bold">{{ project.manager.name }}</span>
                <br />
                Client: <span class="font-bold">{{ project.client.name }}</span>
              </p>
              <p class="rounded-md px-2 py-1 bg-gradient-to-r from-primary to-primary/50">
                {{
                  project.description.length > 255
                    ? project.description.slice(0, 255) + "..."
                    : project.description
                }}
              </p>
            </div>
            <!-- Footer -->
            <div
              class="mt-4 flex items-center justify-between border-t border-white/20 pt-2"
            >
              <div class="flex gap-2">
                <span
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-yellow-100 text-yellow-800': project.status === 'pending',
                    'bg-blue-100 text-blue-800': project.status === 'in_progress',
                    'bg-purple-100 text-purple-800': project.status === 'testing',
                    'bg-indigo-100 text-indigo-800': project.status === 'review',
                    'bg-green-100 text-green-800': project.status === 'completed',
                    'bg-red-100 text-red-800': project.status === 'cancelled',
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
                hasActiveFilters()
                  ? "Try adjusting your filters."
                  : "Looks like there are no projects yet."
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

      <!-- Pagination Component -->
      <Pagination
        v-if="props.projects"
        :current-page="props.projects.current_page"
        :last-page="props.projects.last_page"
        :from="props.projects.from"
        :to="props.projects.to"
        :total="props.projects.total"
        @page-changed="handlePageChange"
      />
      <Dialog v-model="isFilterOpen">
        <template #header>
          <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
            Filter Projects
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Apply filters to narrow down results
          </p>
        </template>

        <template #body>
          <div class="space-y-4">
            <!-- ID Filter -->
            <div>
              <label class="block text-sm font-medium mb-1">Project ID</label>
              <Input v-model="filterId" placeholder="Search by ID..." class="w-full" />
            </div>

            <!-- Manager Filter -->
            <div>
              <label class="block text-sm font-medium mb-1">Manager</label>
              <Select v-model="filterManager" :options="managerOptions" class="w-full" />
            </div>

            <!-- Status Filter -->
            <div>
              <label class="block text-sm font-medium mb-1">Status</label>
              <Select v-model="filterStatus" :options="statusOptions" class="w-full" />
            </div>

            <!-- Starred Filter -->
            <div>
              <label class="block text-sm font-medium mb-1">Starred</label>
              <Select v-model="filterStarred" :options="starredOptions" class="w-full" />
            </div>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-between gap-3">
            <Button variant="outline" @click="resetFilters"> Reset All </Button>
            <div class="flex gap-2">
              <Button variant="outline" @click="isFilterOpen = false"> Cancel </Button>
              <Button
                @click="
                  () => {
                    applyFilters();
                    isFilterOpen = false;
                  }
                "
              >
                Apply Filters
              </Button>
            </div>
          </div>
        </template>
      </Dialog>

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
            <!-- Client -->
            <div>
              <label class="block text-sm font-medium mb-1">Client</label>
              <Select
                v-model="form.client"
                :options="clients"
                placeholder="Select a Client"
                class="w-full"
              />
              <InputError :message="form.errors.client" />
            </div>
            <!-- Manager -->
            <div>
              <label class="block text-sm font-medium mb-1">Manager</label>
              <Select
                v-model="form.manager"
                :options="managerFormOptions"
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
              <Select v-model="form.status" :options="statusFormOptions" class="w-full" />
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
      <Dialog v-model="isViewDialogOpen" v-if="viewProject">
        <template #header>
          <div class="flex justify-between items-center w-full">
            <h2 class="text-lg font-semibold">Project: {{ viewProject.title }}</h2>
          </div>
        </template>
        <template #body>
          <div v-if="viewProject" class="flex flex-col gap-4 text-sm p-2">
            <!-- Title & Manager -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="font-medium text-gray-600 dark:text-gray-300">Manager</p>
                <p class="text-base">{{ viewProject.manager.name }}</p>
              </div>
              <div>
                <p class="font-medium text-gray-600 dark:text-gray-300">Client</p>
                <p class="text-base">{{ viewProject.client.name }}</p>
              </div>
            </div>
            <!-- Description -->
            <div v-if="viewProject.description?.length > 0">
              <!-- Header with toggle -->
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
                    <Viewer :source="viewProject.description" />
                  </div>
                </transition>
              </div>
            </div>
            <!-- Status & Starred -->
            <div class="flex flex-row items-center gap-4">
              <div>
                <p class="font-medium text-gray-600 dark:text-gray-300">Status</p>
                <span
                  class="px-2 py-1 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-yellow-100 text-yellow-800': viewProject.status === 'pending',
                    'bg-blue-100 text-blue-800': viewProject.status === 'in_progress',
                    'bg-purple-100 text-purple-800': viewProject.status === 'testing',
                    'bg-indigo-100 text-indigo-800': viewProject.status === 'review',
                    'bg-green-100 text-green-800': viewProject.status === 'completed',
                    'bg-red-100 text-red-800': viewProject.status === 'cancelled',
                  }"
                >
                  {{ formatStatus(viewProject.status) }}
                </span>
              </div>
              <div v-if="viewProject.is_starred">
                <p class="font-medium text-gray-600 dark:text-gray-300">Starred</p>
                <span
                  class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"
                >
                  ⭐
                </span>
              </div>
            </div>
          </div>
        </template>
      </Dialog>
    </div>
    <!-- Filter Dialog -->
  </AppLayout>
</template>

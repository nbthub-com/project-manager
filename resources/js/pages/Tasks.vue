<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage, router, useForm } from "@inertiajs/vue3";
import Button from "@/components/ui/button/Button.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Input from "@/components/ui/input/Input.vue";
import Dropdown from "@/components/ui/select/Select.vue";
import InputError from "@/components/InputError.vue";
import Pagination from "@/components/ui/pagination/Pagination.vue";
import { onMounted, ref, watch, computed } from "vue";
import { Edit, Eye, Filter, Search, X, Calendar, AlertTriangle, Flame, Leaf } from "lucide-vue-next";
import Picker from "@/components/ui/date/Picker.vue";

const breadcrumbs = [{ title: "Tasks", href: "/tasks" }];
const props = defineProps({
  tasks: Object,
  manager_of: Array,
  names: Array,
  roles: Array,
  filters: Object,
});
const user = usePage().props.auth.user;

// 🔑 Local reactive state
const s_query = ref(props.filters?.search || "");
const perPage = ref(props.filters?.per_page || 10);
const currentPage = ref(props.tasks?.current_page || 1);
const lastPage = ref(props.tasks?.last_page || 1);

// Filter states
const filterStatus = ref(props.filters?.filter_status || "");
const filterManager = ref(props.filters?.filter_manager || "");
const filterAssignee = ref(props.filters?.filter_assignee || "");
const filterProject = ref(props.filters?.filter_project || "");
const filterPriority = ref(props.filters?.filter_priority || "");

// Form for search and pagination
const filterForm = useForm({
  search: s_query.value,
  per_page: perPage.value,
  page: currentPage.value,
  filter_status: filterStatus.value,
  filter_manager: filterManager.value,
  filter_assignee: filterAssignee.value,
  filter_project: filterProject.value,
  filter_priority: filterPriority.value,
});

// Watch for props changes to update pagination state
watch(
  () => props.tasks,
  (newTasks) => {
    if (newTasks) {
      currentPage.value = newTasks.current_page;
      lastPage.value = newTasks.last_page;
    }
  },
  { deep: true }
);

// Watch for search changes and apply filters
watch(s_query, (value) => {
  filterForm.search = value;
  filterForm.page = 1;
  applyFilters();
});

// Watch for per_page changes
watch(perPage, (value) => {
  filterForm.per_page = value;
  filterForm.page = 1;
  applyFilters();
});

// Watch for filter changes
watch(
  [filterStatus, filterManager, filterAssignee, filterProject, filterPriority],
  () => {
    filterForm.filter_status = filterStatus.value;
    filterForm.filter_manager = filterManager.value;
    filterForm.filter_assignee = filterAssignee.value;
    filterForm.filter_project = filterProject.value;
    filterForm.filter_priority = filterPriority.value;
    filterForm.page = 1;
    applyFilters();
  },
  { deep: true }
);

onMounted(() => {
  applyFilters();
});

// Apply filters with debounce
let debounceTimeout;
function applyFilters() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    filterForm.get("/tasks", {
      preserveState: true,
      preserveScroll: true,
    });
  }, 300);
}

// Handle page change from pagination component
function handlePageChange(page) {
  currentPage.value = page;
  filterForm.page = page;
  filterForm.get("/tasks", {
    preserveState: true,
    preserveScroll: true,
  });
}

// Reset all filters
function resetFilters() {
  s_query.value = "";
  filterStatus.value = "";
  filterManager.value = "";
  filterAssignee.value = "";
  filterProject.value = "";
  filterPriority.value = "";

  filterForm.search = "";
  filterForm.filter_status = "";
  filterForm.filter_manager = "";
  filterForm.filter_assignee = "";
  filterForm.filter_project = "";
  filterForm.filter_priority = "";
  filterForm.page = 1;

  applyFilters();
}

// Check if any filters are active
function hasActiveFilters() {
  return (
    s_query.value ||
    filterStatus.value ||
    filterManager.value ||
    filterAssignee.value ||
    filterProject.value ||
    filterPriority.value
  );
}

// dialogs
const isDialogOpen = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const isViewDialogOpen = ref(false);
const viewTask = ref(null);
const isFilterOpen = ref(false);

// form
const form = useForm({
  title: "",
  description: "",
  to_id: "",
  project_id: "",
  status: "pending",
  role_title: "frontend-developer",
  priority: "medium",
  deadline: "",
});

// create / update
function submitForm() {
  if (isEditMode.value && editId.value) {
    form.put(`/tasks/update/${editId.value}`, {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  } else {
    form.post("/tasks/create", {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  }
}

// edit
function editTask(task) {
  isEditMode.value = true;
  form.reset();
  editId.value = task.id;
  form.title = task.title;
  form.description = task.description;
  form.to_id = props.names.find((u) => u.name === task.assignee)?.id || "";
  form.project_id = props.manager_of.find((p) => p.title === task.project)?.id || "";
  form.status = task.status;
  form.role_title = task.role_title || "frontend-developer";
  form.priority = task.priority || "medium";
  form.deadline = task.deadline || "";
  isDialogOpen.value = true;
}

// delete
function deleteTask(id) {
  if (confirm("Are you sure you want to delete this task?")) {
    router.delete(`/tasks/delete/${id}`, {
      preserveScroll: true,
    });
  }
}

// view
function openViewDialog(task) {
  viewTask.value = task;
  isViewDialogOpen.value = true;
}

// role options
const defaultRoles = [
  { label: "Frontend Developer", value: "frontend-developer" },
  { label: "Backend Developer", value: "backend-developer" },
  { label: "Fullstack Developer", value: "fullstack-developer" },
  { label: "Tester", value: "tester" },
  { label: "Designer", value: "designer" },
  { label: "DevOps Engineer", value: "devops-engineer" },
  { label: "Add +", value: null },
];

const roleOptions = props.roles
  .filter((r) => r)
  .map((r) => {
    const formatted = r
      .trim()
      .replace(/-/g, " ")
      .replace(/\b\w/g, (c) => c.toUpperCase());
    return { label: formatted, value: r.trim() };
  });

const options = [
  ...roleOptions,
  ...defaultRoles.filter((def) => !roleOptions.some((opt) => opt.value === def.value)),
];

// Status options for filter
const statusOptions = [
  { label: "All Statuses", value: "" },
  { label: "Pending", value: "pending" },
  { label: "In Progress", value: "in_progress" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

// Priority options
const priorityOptions = [
  { label: "All Priorities", value: "" },
  { label: "Low", value: "low" },
  { label: "Medium", value: "medium" },
  { label: "High", value: "high" },
];

// Get unique managers from tasks
const managerOptions = computed(() => {
  const managers = new Set();
  props.tasks?.data?.forEach((task) => {
    if (task.manager) managers.add(task.manager);
  });
  return [
    { label: "All Managers", value: "" },
    ...Array.from(managers).map((manager) => ({ label: manager, value: manager })),
  ];
});

// Get unique assignees from tasks
const assigneeOptions = computed(() => {
  const assignees = new Set();
  props.tasks?.data?.forEach((task) => {
    if (task.assignee) assignees.add(task.assignee);
  });
  return [
    { label: "All Assignees", value: "" },
    ...Array.from(assignees).map((assignee) => ({ label: assignee, value: assignee })),
  ];
});

// Get unique projects from tasks
const projectOptions = computed(() => {
  const projects = new Set();
  props.tasks?.data?.forEach((task) => {
    if (task.project) projects.add(task.project);
  });
  return [
    { label: "All Projects", value: "" },
    ...Array.from(projects).map((project) => ({ label: project, value: project })),
  ];
});

function isAssignee(task) {
  return task.assignee === user.name;
}

// Status options for assignees (only pending, in_progress, completed)
const assigneeStatusOptions = [
  { label: "Pending", value: "pending" },
  { label: "In Progress", value: "in_progress" },
  { label: "Completed", value: "completed" },
];

// Update task status (for assignees)
function updateTaskStatus(task, newStatus) {
  router.put(
    `/tasks/update/${task.id}`,
    {
      status: newStatus,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        // Status updated successfully
      },
      onError: (errors) => {
        console.error("Error updating status:", errors);
      },
    }
  );
}

// Format date for display
function formatDate(dateString) {
  if (!dateString) return "No deadline";

  const date = new Date(dateString);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const taskDate = new Date(dateString);
  taskDate.setHours(0, 0, 0, 0);

  const diffTime = taskDate - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays < 0) {
    return `Overdue by ${Math.abs(diffDays)} day${Math.abs(diffDays) !== 1 ? "s" : ""}`;
  } else if (diffDays === 0) {
    return "Due today";
  } else if (diffDays === 1) {
    return "Due tomorrow";
  } else {
    return `Due in ${diffDays} days`;
  }
}

// Get priority color
function getPriorityColor(priority) {
  switch (priority) {
    case "high":
      return "bg-red-500";
    case "medium":
      return "bg-yellow-500";
    case "low":
      return "bg-green-500";
    default:
      return "bg-gray-500";
  }
}
const v = ref()
</script>

<template>
  <Head title="Tasks" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col px-1">
      <div class="border-b-2 flex flex-row justify-between p-1 items-center gap-2">
        <div class="w-full sm:w-sm flex flex-row">
          <Input
            v-model="s_query"
            class="transition-all duration-300 ease-in-out rounded-r-none"
            placeholder="Search tasks..."
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
                [
                  filterStatus,
                  filterManager,
                  filterAssignee,
                  filterProject,
                  filterPriority,
                ].filter(Boolean).length + (s_query ? 1 : 0)
              }}
            </span>
          </Button>
        </div>
        <div class="flex items-center space-x-2">
          <Dropdown
            v-model="perPage"
            class="rounded text-sm"
            :options="[
              { value: 5, label: '5/pg' },
              { value: 10, label: '10/pg' },
              { value: 20, label: '20/pg' },
              { value: 50, label: '50/pg' },
            ]"
          >
          </Dropdown>
          <Button
            v-if="props.manager_of.length || user.role === 'admin'"
            @click="
              () => {
                isEditMode = false;
                form.resetAndClearErrors();
                form.status = 'pending';
                form.priority = 'medium';
                isDialogOpen = true;
              }
            "
            >+ New</Button
          >
        </div>
      </div>

      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2 mt-2 mb-1">
        <div
          v-if="s_query"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Search: {{ s_query }}
          <button
            @click="
              s_query = '';
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
          Status: {{ filterStatus.replace("_", " ") }}
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
          v-if="filterPriority"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Priority: {{ filterPriority }}
          <button
            @click="
              filterPriority = '';
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
          v-if="filterAssignee"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Assignee: {{ filterAssignee }}
          <button
            @click="
              filterAssignee = '';
              applyFilters();
            "
            class="ml-1"
          >
            <X class="h-3 w-3" />
          </button>
        </div>
        <div
          v-if="filterProject"
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Project: {{ filterProject }}
          <button
            @click="
              filterProject = '';
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

      <!-- Tasks Grid -->
      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3">
        <template v-if="props.tasks?.data?.length">
          <div
            v-for="task in props.tasks.data"
            :key="task.id"
            class="p-4 rounded-xl shadow-lg text-white bg-gradient-to-br from-[#5a248a] to-secondary transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
            :class="task.status === 'cancelled' ? 'opacity-60 grayscale' : ''"
          >
            <!-- Task Info -->
            <div>
              <div
                class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
              >
                <h3 class="text-lg font-bold flex flex-row">
                  {{ task.title }}
                  <span class="font-extralight text-[15px] h-full gap-2 flex flex-row">
                    ({{ task.id }})
                    <div
                      class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold capitalize"
                      :class="{
                        'border-2 border-red-300 text-red-300': task.priority === 'high',
                        'border-2 border-yellow-400 text-yellow-400': task.priority === 'medium',
                        'border-2 border-green-400 text-green-400': task.priority === 'low',
                      }"
                    >
                      <Flame
                        v-if="task.priority === 'high'"
                        class="w-4 h-4 text-red-300"
                      />
                      <AlertTriangle
                        v-else-if="task.priority === 'medium'"
                        class="w-4 h-4 text-yellow-400"
                      />
                      <Leaf v-else class="w-4 h-4 text-green-400" />
                      <span>{{ task.priority || "medium" }}</span>
                    </div>
                  </span>
                </h3>
                <button
                  class="text-red-200 hover:text-red-400 cursor-pointer"
                  @click="deleteTask(task.id)"
                >
                  ✕
                </button>
              </div>
            </div>
            <div class="flex flex-col gap-1">
              <p class="text-sm opacity-90">
                Project:
                <span class="font-bold capitalize text-foreground">{{
                  task.project
                }}</span>
              </p>
              <p class="text-sm opacity-90">
                For:
                <span class="font-bold capitalize text-foreground">{{
                  task.assignee
                }}</span>
              </p>
              <p class="text-sm opacity-90">
                By:
                <span class="font-bold capitalize text-foreground">{{
                  task.manager
                }}</span>
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
              <!-- Status display for non-assignees -->
              <span
                v-if="!isAssignee(task)"
                class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
                :class="{
                  'bg-yellow-400 text-black': task.status === 'pending',
                  'bg-blue-500 text-white': task.status === 'in_progress',
                  'bg-green-600 text-white': task.status === 'completed',
                  'bg-red-500 text-white': task.status === 'cancelled',
                }"
              >
                {{ task.status.replace("_", " ") }}
              </span>
              <!-- Status dropdown for assignees -->
              <Dropdown
                v-else
                :options="assigneeStatusOptions"
                :modelValue="task.status"
                @update:modelValue="(newStatus) => updateTaskStatus(task, newStatus)"
                class="w-32 px-2 py-1 text-xs font-medium rounded-md border border-white/20 bg-white/10 text-white focus:outline-none focus:ring-1 focus:ring-white hover:bg-white/20 transition"
              />
              <!-- Action buttons -->
              <div class="flex gap-0.5 text-sm">
                <button
                  class="p-1 rounded-md hover:bg-white/20 transition"
                  @click="openViewDialog(task)"
                  title="View Task"
                >
                  <Eye class="w-4 h-4 text-white" />
                </button>
                <!-- Edit button only for non-assignees -->
                <button
                  v-if="!isAssignee(task)"
                  class="p-1 rounded-md hover:bg-white/20 transition"
                  @click="
                    {
                      form.resetAndClearErrors();
                      editTask(task);
                    }
                  "
                  title="Edit Task"
                >
                  <Edit class="w-4 h-4 text-white" />
                </button>
              </div>
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
                hasActiveFilters()
                  ? "Try adjusting your filters."
                  : "Looks like there are no tasks yet."
              }}
            </p>
            <Button
              v-if="props.manager_of.length || user.role === 'admin'"
              class="mt-4"
              @click="
                () => {
                  isEditMode = false;
                  form.reset();
                  form.status = 'pending';
                  form.priority = 'medium';
                  isDialogOpen = true;
                }
              "
            >
              + Assign First Task
            </Button>
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
        @page-changed="handlePageChange"
      />
    </div>

    <!-- Filter Dialog -->
    <Dialog v-model="isFilterOpen">
      <template #header>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Filter Tasks</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Apply filters to narrow down results
        </p>
      </template>
      <template #body>
        <div class="space-y-4">
          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <Dropdown v-model="filterStatus" :options="statusOptions" class="w-full" />
          </div>

          <!-- Priority Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Priority</label>
            <Dropdown
              v-model="filterPriority"
              :options="priorityOptions"
              class="w-full"
            />
          </div>

          <!-- Manager Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Manager</label>
            <Dropdown v-model="filterManager" :options="managerOptions" class="w-full" />
          </div>

          <!-- Assignee Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Assignee</label>
            <Dropdown
              v-model="filterAssignee"
              :options="assigneeOptions"
              class="w-full"
            />
          </div>

          <!-- Project Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Project</label>
            <Dropdown v-model="filterProject" :options="projectOptions" class="w-full" />
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

    <!-- Task Create/Edit Dialog -->
    <Dialog v-model="isDialogOpen">
      <!-- Header -->
      <template #header>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
          {{ isEditMode ? "Update Task" : "Assign New Task" }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{
            isEditMode
              ? "Modify the task details below"
              : "Fill in details to create a new task"
          }}
        </p>
      </template>
      <!-- Body -->
      <template #body>
        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input v-model="form.title" placeholder="Task Title" class="w-full" />
            <InputError :message="form.errors.title" />
          </div>
          <!-- Description -->
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="form.description"
              placeholder="Describe the task..."
              class="border rounded-lg p-3 text-sm w-full focus:ring-2 focus:ring-primary focus:outline-none transition"
              rows="3"
            ></textarea>
            <InputError :message="form.errors.description" />
          </div>
          <div class="w-full flex flex-row p-1 gap-2">
            <div class="w-1/2">
              <label class="block text-sm font-medium mb-1">Assignee</label>
              <Dropdown
                v-model="form.to_id"
                :options="
                  props.names.map((user) => ({ label: user.name, value: user.id }))
                "
              />
              <InputError :message="form.errors.to_id" />
            </div>
            <div class="w-1/2">
              <label class="block text-sm font-medium mb-1">Project</label>
              <Dropdown
                v-model="form.project_id"
                :options="
                  props.manager_of.map((project) => ({
                    label: project.title,
                    value: project.id,
                  }))
                "
              />
              <InputError :message="form.errors.project_id" />
            </div>
          </div>
          <div class="w-full flex flex-row p-1 gap-2">
            <div class="w-1/2">
              <label class="block text-sm font-medium mb-1">Role</label>
              <Dropdown v-model="form.role_title" :options="options" />
              <Input
                v-if="
                  form.role_title === null ||
                  !options
                    .filter((opt) => opt.value)
                    .some((opt) => opt.value === form.role_title)
                "
                v-model="form.role_title"
                class="mt-1"
                placeholder="Enter new role_title"
              />
              <InputError :message="form.errors.role_title" />
            </div>
            <div class="w-1/2" v-if="isEditMode">
              <label class="block text-sm font-medium mb-1">Status</label>
              <Dropdown
                v-model="form.status"
                :options="[
                  { label: 'Pending', value: 'pending' },
                  { label: 'In Progress', value: 'in_progress' },
                  { label: 'Completed', value: 'completed' },
                  { label: 'Cancelled', value: 'cancelled' },
                ]"
              />
            </div>
          </div>

          <!-- Priority and Deadline -->
          <div class="w-full flex flex-row p-1 gap-2">
            <div class="w-1/2">
              <label class="block text-sm font-medium mb-1">Priority</label>
              <Dropdown
                v-model="form.priority"
                :options="[
                  { label: 'Low', value: 'low' },
                  { label: 'Medium', value: 'medium' },
                  { label: 'High', value: 'high' },
                ]"
              />
              <InputError :message="form.errors.priority" />
            </div>
            <div class="w-1/2">
              <label class="block text-sm font-medium mb-1">Deadline</label>
              <Picker v-model="form.deadline" />
              <InputError :message="form.errors.deadline" />
            </div>
          </div>
        </form>
      </template>
      <!-- Footer -->
      <template #footer>
        <div class="flex justify-end gap-3">
          <Button
            variant="outline"
            class="hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="
              () => {
                form.reset();
                form.clearErrors();
                isDialogOpen = false;
                isEditMode = false;
                editId = null;
              }
            "
          >
            Cancel
          </Button>
          <Button
            @click="submitForm"
            :disabled="form.processing"
            class="transition-transform hover:scale-105"
          >
            {{ isEditMode ? "Update" : "Create" }}
          </Button>
        </div>
      </template>
    </Dialog>

    <!-- View Dialog -->
    <Dialog v-model="isViewDialogOpen">
      <!-- Header -->
      <template #header>
        <div class="flex items-center gap-2">
          <h2 class="text-xl  font-bold flex items-center gap-2 capitalize">
            {{ viewTask.title }}
          </h2>
          <span
            class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
            :class="{
              'bg-yellow-100 text-yellow-700': viewTask.status === 'pending',
              'bg-blue-100 text-blue-700': viewTask.status === 'in_progress',
              'bg-green-100 text-green-700': viewTask.status === 'completed',
              'bg-red-100 text-red-700': viewTask.status === 'cancelled',
            }"
          >
            {{ viewTask.status.replace("_", " ") }}
          </span>
        </div>
      </template>
      <!-- Body -->
      <template #body>
        <div v-if="viewTask" class="flex flex-col gap-6 text-sm p-2 animate-fadeIn">
          <!-- Info grid -->
          <div class="grid grid-cols-3">
            <div class="space-y-1">
              <p class="text-xs uppercase tracking-wide text-gray-500">Assignee</p>
              <p class="font-semibold">{{ viewTask.assignee }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs uppercase tracking-wide text-gray-500">Role</p>
              <p class="font-semibold capitalize">
                {{ viewTask.role_title?.replace(/-/g, " ") || "—" }}
              </p>
            </div>
            <div class="space-y-1">
              <p class="text-xs uppercase tracking-wide text-gray-500">Manager</p>
              <p class="font-semibold">{{ viewTask.manager }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs uppercase tracking-wide text-gray-500">Project</p>
              <p class="font-semibold">{{ viewTask.project }}</p>
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
            <p class="text-xs uppercase tracking-wide text-gray-500">Description</p>
            <div
              class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-sm shadow-inner hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-text"
            >
              {{ viewTask.description || "—" }}
            </div>
          </div>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

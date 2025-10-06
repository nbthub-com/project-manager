<script setup>
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";
import Input from "@/components/ui/input/Input.vue";
import Dialog from "@/components/ui/simpledialog/Dialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { toTitleCase } from "@/lib/utils";
import { Head, useForm, router } from "@inertiajs/vue3";
import {
  Edit,
  List,
  ListPlus,
  PackagePlus,
  Plus,
  Search,
  ChevronDown,
  ChevronRight,
  CircleDot,
  Flag,
  Dot,
  Activity,
  Loader2,
  Loader2Icon,
  LoaderPinwheel,
  LoaderCircle,
  LucideCircleDot,
  LucideCircle,
} from "lucide-vue-next";
import { defineProps, ref, computed } from "vue";
import Dropdown from "@/components/ui/select/Select.vue";
import Picker from "@/components/ui/date/Picker.vue";
import { watchEffect } from "vue";

// Add the missing props
const props = defineProps(["member", "names", "roles", "manager_of"]);

const member = props.member;

const title =
  toTitleCase(member.name) + " (" + (member.role === "user" ? "Member" : "Client") + ")";
const href_ = "/admin/members/" + member.id.toString();
const breadcrumbs = [{ title: title, href: href_ }];

// Collapsibles
const openProjectIds = ref([]);
const openassigned_tasks = ref(true);
const searchQuery = ref("");
const isDetailsOpen = ref(false);
const isNewProjectOpen = ref(false);
const isEditTaskOpen = ref(false);
const isEditProjectOpen = ref(false);
const currentTask = ref();
const currentProj = ref();

// Add these reactive variables
const isEditMode = ref(false);
const editId = ref(null);

// Filter projects
const filteredProjects = computed(() => {
  if (!searchQuery.value)
    return [...(member.managed_projects || []), ...(member.client_projects || [])];

  const query = searchQuery.value.toLowerCase();
  return [...(member.managed_projects || []), ...(member.client_projects || [])].filter(
    (project) =>
      project.title.toLowerCase().includes(query) ||
      (project.tasks || []).some((task) => task.title.toLowerCase().includes(query))
  );
});

const toggleProject = (id) => {
  if (openProjectIds.value.includes(id)) {
    // Close it
    openProjectIds.value = openProjectIds.value.filter((pid) => pid !== id);
  } else {
    // Open it
    openProjectIds.value.push(id);
  }
};

const stats = computed(() => {
  const assignedTasks = member.assigned_tasks || [];
  const totalTasks = assignedTasks.length;
  const completedTasks = assignedTasks.filter((t) => t.status === "completed").length;
  const runningTasks = assignedTasks.filter((t) => t.status === "running").length;

  const projectMap = new Map();
  for (const task of assignedTasks) {
    if (task.project) {
      projectMap.set(task.project.id, task.project);
    }
  }

  const assignedProjects = Array.from(projectMap.values());
  const totalProjects = assignedProjects.length;
  const completedProjects = assignedProjects.filter((p) => p.status === "completed")
    .length;
  const runningProjects = assignedProjects.filter((p) => p.status === "running").length;

  return {
    totalTasks,
    completedTasks,
    runningTasks,
    totalProjects,
    completedProjects,
    runningProjects,
  };
});

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

// Role options for dropdown
const roleOptions = computed(() => {
  const defaultRoles = [
    { label: "Frontend Developer", value: "frontend-developer" },
    { label: "Backend Developer", value: "backend-developer" },
    { label: "Fullstack Developer", value: "fullstack-developer" },
    { label: "Tester", value: "tester" },
    { label: "Designer", value: "designer" },
    { label: "DevOps Engineer", value: "devops-engineer" },
    { label: "Add +", value: null },
  ];

  const customRoles = (props.roles || [])
    .filter((r) => r)
    .map((r) => {
      const formatted = r
        .trim()
        .replace(/-/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
      return { label: formatted, value: r.trim() };
    });

  return [
    ...customRoles,
    ...defaultRoles.filter((def) => !customRoles.some((opt) => opt.value === def.value)),
  ];
});

// Status options
const statusOptions = [
  { label: "Pending", value: "pending" },
  { label: "In Progress", value: "in_progress" },
  { label: "Testing", value: "testing" },
  { label: "Review", value: "review" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

function openEditDialog(task) {
  isEditMode.value = true;
  editId.value = task.id;
  currentTask.value = task;

  // Populate form with task data
  form.title = task.title;
  form.description = task.description;
  form.to_id = task.to_id;
  form.project_id = task.pr_id || task.project_id;
  form.status = task.status;
  form.role_title = task.role_title || "frontend-developer";
  form.priority = task.priority || "medium";
  form.deadline = task.deadline || "";

  isEditTaskOpen.value = true;
}

function resetForm() {
  form.reset();
  form.clearErrors();
  form.role_title = "frontend-developer";
  form.status = "pending";
  form.priority = "medium";
  isEditMode.value = false;
  editId.value = null;
  currentTask.value = null;
}

function submitForm() {
  if (isEditMode.value && editId.value) {
    form.put(`/tasks/update/${editId.value}`, {
      onSuccess: () => {
        // Update task in assigned_tasks if it exists there
        if (member.assigned_tasks) {
          const assignedIndex = member.assigned_tasks.findIndex(
            (t) => t.id === editId.value
          );
          if (assignedIndex !== -1) {
            // Update the task with form data
            member.assigned_tasks[assignedIndex] = {
              ...member.assigned_tasks[assignedIndex],
              title: form.title,
              description: form.description,
              to_id: form.to_id,
              pr_id: form.project_id,
              status: form.status,
              role_title: form.role_title,
              priority: form.priority,
              deadline: form.deadline,
            };
          }
        }

        // Update task in managed_projects
        if (member.managed_projects) {
          member.managed_projects.forEach((project) => {
            if (project.tasks) {
              const taskIndex = project.tasks.findIndex((t) => t.id === editId.value);
              if (taskIndex !== -1) {
                project.tasks[taskIndex] = {
                  ...project.tasks[taskIndex],
                  title: form.title,
                  description: form.description,
                  to_id: form.to_id,
                  pr_id: form.project_id,
                  status: form.status,
                  role_title: form.role_title,
                  priority: form.priority,
                  deadline: form.deadline,
                };
              }
            }
          });
        }

        // Update task in client_projects
        if (member.client_projects) {
          member.client_projects.forEach((project) => {
            if (project.tasks) {
              const taskIndex = project.tasks.findIndex((t) => t.id === editId.value);
              if (taskIndex !== -1) {
                project.tasks[taskIndex] = {
                  ...project.tasks[taskIndex],
                  title: form.title,
                  description: form.description,
                  to_id: form.to_id,
                  pr_id: form.project_id,
                  status: form.status,
                  role_title: form.role_title,
                  priority: form.priority,
                  deadline: form.deadline,
                };
              }
            }
          });
        }

        isEditTaskOpen.value = false;
        resetForm();
      },
    });
  } else {
    form.post("/tasks/create", {
      onSuccess: (resp) => {
        const newTask = resp?.props?.task || {
          id: Date.now(), // fallback if nothing
          title: form.title,
          description: form.description,
          to_id: form.to_id,
          project_id: form.project_id,
          status: form.status,
          role_title: form.role_title,
          priority: form.priority,
          deadline: form.deadline,
          project: props.manager_of.find((p) => p.id === form.project_id) || null,
        };

        // Push into assigned tasks
        if (!member.assigned_tasks) member.assigned_tasks = [];
        member.assigned_tasks.push(newTask);

        // Also push into the right project.tasks if exists
        const proj = (member.managed_projects || []).find(
          (p) => p.id === form.project_id
        );
        if (proj) {
          if (!proj.tasks) proj.tasks = [];
          proj.tasks.push(newTask);
        }

        isNewTaskDialogOpen.value = false;
        resetForm();
      },
    });
  }
}

function closeEditDialog() {
  isEditTaskOpen.value = false;
  resetForm();
}
// Add new reactive variable for the new task dialog
const isNewTaskDialogOpen = ref(false);

// Function to open the new task dialog
function openNewTaskDialog() {
  resetForm();
  form.status = "pending"; // Set default status to pending
  form.to_id = member.id; // Assign task to current member
  isNewTaskDialogOpen.value = true;
}

// Function to close the new task dialog
function closeNewTaskDialog() {
  isNewTaskDialogOpen.value = false;
  resetForm();
}

watchEffect(() => {
  if (filteredProjects.value?.length) {
    openProjectIds.value = filteredProjects.value.map((p) => p.id);
  }
});

</script>

<template>
  <Head :title="title" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col px-2 pb-2">
      <!-- Toolbar -->
      <div class="border-b-2 flex flex-row justify-between items-center py-1">
        <div class="w-full sm:w-sm flex flex-row">
          <Input
            v-model="searchQuery"
            class="transition-all duration-300 ease-in-out rounded-r-none"
            placeholder="Search projects or tasks..."
          />
          <Button class="rounded-l-none outline-1">
            <Search />
          </Button>
        </div>
        <div class="flex items-center sm:space-x-2 w-full sm:w-auto justify-end">
          <Button variant="outline" class="rounded-r-none sm:rounded-lg">
            <span class="hidden items-center sm:flex"> <Plus /> Project</span>
            <span class="flex sm:hidden"> <PackagePlus /></span>
          </Button>
          <Button
            v-if="member.role !== 'client'"
            variant="outline"
            class="rounded-none sm:rounded-lg"
            @click="openNewTaskDialog"
          >
            <span class="hidden items-center sm:flex"><Plus /> Task</span>
            <span class="flex sm:hidden"> <ListPlus /></span>
          </Button>
          <Button class="rounded-l-none sm:rounded-lg" @click="isDetailsOpen = true">
            <List /> <span class="hidden sm:flex">Details</span>
          </Button>
        </div>
      </div>

      <div class="flex-grow overflow-hidden">
        <div class="p-2">
          <div class="columns-1 sm:columns-2 lg:columns-3 gap-4">
            <div
              v-if="member.assigned_tasks && member.assigned_tasks.length"
              class="break-inside-avoid mb-4 rounded-xl overflow-auto transition-all duration-200 hover:shadow-lg border-2 border-purple-800"
            >
              <div
                class="bg-purple-800 px-2 py-4.5 flex justify-between items-center 
                          hover:cursor-pointer hover:bg-purple-900 
                          transition-colors duration-200"
                @click="openassigned_tasks = !openassigned_tasks"
              >
                <h2 class="text-lg font-bold">Assigned Tasks</h2>
                <div class="flex items-center">
                  <span class="text-xs bg-secondary/60 px-2 py-1 rounded-full mr-2">
                    {{ member.assigned_tasks.length }} tasks
                  </span>
                  <ChevronDown
                    v-if="openassigned_tasks"
                    class="w-5 h-5 transition-transform duration-300 transform rotate-180"
                  />
                  <ChevronRight
                    v-else
                    class="w-5 h-5 transition-transform duration-300"
                  />
                </div>
              </div>

              <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform opacity-0 -translate-y-2"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-2"
              >
                <div
                  v-show="openassigned_tasks"
                  class="overflow-hidden"
                >
                  <div class="flex flex-col mb-2">
                    <div
                      v-for="task in member.assigned_tasks"
                      :key="task.id"
                      class="p-2 w-full h-full border-b-2 border-primary/30 flex flex-col hover:bg-primary/10 transition-colors duration-150"
                      :class="task.status === 'cancelled' ? 'text-red-400' : 'text-white'"
                    >
                      <div
                        class="w-full h-full flex flex-row items-center justify-between mb-1"
                      >
                        <div class="flex items-center gap-2 font-bold text-md">
                          <p>{{ toTitleCase(task.title) }}</p>
                        </div>
                        <Edit
                          class="w-4 h-4 hover:bg-primary hover:cursor-pointer rounded-sm"
                          @click="openEditDialog(task)"
                        />
                      </div>
                      <div
                        class="flex flex-wrap items-center gap-2 text-xs text-gray-200"
                      >
                        <div class="flex items-center gap-1 text-gray-300">
                          <span class="opacity-80">For</span>
                          <span class="font-semibold text-gray-100">
                            {{ toTitleCase(task.assignee?.name) || "Unassigned" }}
                          </span>
                        </div>
                        <!-- Status Badge -->
                        <div class="w-fit flex flex-row items-center gap-2">
                          <div
                            class="w-fit h-fit font-medium flex flex-row items-center gap-1"
                            :class="{
                              'text-red-500': task.status === 'cancelled',
                              'text-green-400': task.status === 'completed',
                              'text-blue-600': task.status === 'in_progress',
                              'text-yellow-400': task.status === 'pending',
                              'text-purple-600':
                                task.status === 'testing' || task.status === 'review',
                            }"
                          >
                            <LucideCircle
                              class="w-3 h-3 font-extrabold"
                              :class="{
                                'fill-red-200': task.status === 'cancelled',
                                'fill-green-300': task.status === 'completed',
                                'fill-blue-300': task.status === 'in_progress',
                                'fill-yellow-200': task.status === 'pending',
                                'fill-purple-300':
                                  task.status === 'testing' || task.status === 'review',
                              }"
                            />
                            {{ toTitleCase(task.status.replace("_", " ")) }}
                          </div>

                          <!-- Priority Badge -->
                          <div
                            class="font-medium flex flex-row gap-1 items-center"
                            :class="{
                              'text-green-500': task.priority === 'low',
                              'text-yellow-400': task.priority === 'medium',
                              'text-red-400': task.priority === 'high',
                            }"
                          >
                            <Flag
                              class="w-3 h-3"
                              :class="{
                                'fill-green-300': task.priority === 'low',
                                'fill-yellow-200': task.priority === 'medium',
                                'fill-red-200': task.priority === 'high',
                              }"
                            />{{ toTitleCase(task.priority) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </transition>
            </div>
            <div
              v-if="filteredProjects.length"
              v-for="project in filteredProjects"
              :key="project.id"
              class="break-inside-avoid mb-4 rounded-xl shadow border-2 border-primary overflow-auto transition-all duration-200 hover:shadow-lg"
            >
              <!-- Project Header -->
              <div
                class="w-full bg-primary rounded-t-lg px-2 py-2 flex justify-between items-center hover:cursor-pointer hover:bg-primary/90 transition-colors duration-200"
                @click="toggleProject(project.id)"
              >
                <div>
                  <h2 class="text-lg font-bold hover:underline">
                    {{ toTitleCase(project.title) }}
                  </h2>
                  <p class="text-xs">
                    Client:
                    {{ project.client?.name || "—" }}
                  </p>
                </div>
                <div class="flex items-center">
                  <span class="text-xs bg-secondary/60 px-2 py-1 rounded-full mr-2">
                    {{ (project.tasks || []).length }} tasks
                  </span>
                  <ChevronDown
                    v-if="openProjectIds.includes(project.id)"
                    class="w-5 h-5 transition-transform duration-300 transform rotate-180"
                  />
                  <ChevronRight
                    v-else
                    class="w-5 h-5 transition-transform duration-300"
                  />
                </div>
              </div>

              <!-- Collapsible Tasks -->
              <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform opacity-0 -translate-y-2"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-2"
              >
                <div
v-show="openProjectIds.includes(project.id)"
                  class="overflow-hidden"
                  :style="
                    openProjectIds === project.id
                      ? 'max-height: 65vh; overflow-y: auto;'
                      : ''
                  "
                >
                  <div
                    v-if="project.tasks && project.tasks.length"
                    class="flex flex-col mb-2"
                  >
                    <div
                      v-for="task in project.tasks"
                      :key="task.id"
                      class="p-2 w-full h-full border-b-2 border-primary/30 flex flex-col hover:bg-primary/10 transition-colors duration-150"
                      :class="task.status === 'cancelled' ? 'text-red-300' : 'text-white'"
                    >
                      <div
                        class="w-full h-full flex flex-row items-center justify-between mb-1"
                      >
                        <div class="flex items-center gap-2 font-bold text-md">
                          <p>{{ toTitleCase(task.title) }}</p>
                        </div>
                        <Edit
                          class="w-4 h-4 hover:bg-primary hover:cursor-pointer rounded-sm"
                          @click="openEditDialog(task)"
                        />
                      </div>
                      <div
                        class="flex flex-wrap items-center gap-2 text-xs text-gray-200"
                      >
                        <div class="flex items-center gap-1 text-gray-300">
                          <span class="opacity-80">For</span>
                          <span class="font-semibold text-gray-100">
                            {{ toTitleCase(task.assignee?.name) || "Unassigned" }}
                          </span>
                        </div>
                        <!-- Status Badge -->
                        <div class="w-fit flex flex-row items-center gap-2">
                          <div
                            class="w-fit h-fit font-medium flex flex-row items-center gap-1"
                            :class="{
                              'text-red-500': task.status === 'cancelled',
                              'text-green-400': task.status === 'completed',
                              'text-blue-600': task.status === 'in_progress',
                              'text-yellow-400': task.status === 'pending',
                              'text-purple-600':
                                task.status === 'testing' || task.status === 'review',
                            }"
                          >
                            <LucideCircle
                              class="w-3 h-3 font-extrabold"
                              :class="{
                                'fill-red-200': task.status === 'cancelled',
                                'fill-green-300': task.status === 'completed',
                                'fill-blue-300': task.status === 'in_progress',
                                'fill-yellow-200': task.status === 'pending',
                                'fill-purple-300':
                                  task.status === 'testing' || task.status === 'review',
                              }"
                            />
                            {{ toTitleCase(task.status.replace("_", " ")) }}
                          </div>

                          <!-- Priority Badge -->
                          <div
                            class="font-medium flex flex-row gap-1 items-center"
                            :class="{
                              'text-green-500': task.priority === 'low',
                              'text-yellow-400': task.priority === 'medium',
                              'text-red-400': task.priority === 'high',
                            }"
                          >
                            <Flag
                              class="w-3 h-3"
                              :class="{
                                'fill-green-300': task.priority === 'low',
                                'fill-yellow-200': task.priority === 'medium',
                                'fill-red-200': task.priority === 'high',
                              }"
                            />{{ toTitleCase(task.priority) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-sm italic px-2 py-2 text-center">
                    No tasks in this project.
                  </div>
                </div>
              </transition>
            </div>
            <div
              v-else-if="
                !filteredProjects.length &&
                !member.assigned_tasks &&
                member.role !== 'client'
              "
              class="mt-8 text-center py-12"
            >
              No Assignments found!
            </div>
            <div v-else-if="member.role === 'client'">No Assignments owned!</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
  <Dialog v-model="isDetailsOpen">
    <template #header>
      <div class="w-fit flex flex-row items-baseline gap-1">
        <p>{{ toTitleCase(member.name) }}</p>
        <p class="text-xs font-extralight">
          ({{ member.role === "client" ? "Client" : "Member" }})
        </p>
      </div>
    </template>

    <template #body>
      <div class="space-y-6 py-2">
        <!-- Tasks Section -->
        <div>
          <p class="font-extrabold text-lg text-gray-300 mb-2 flex items-center gap-2">
            <List class="w-5 h-5 text-primary" /> Tasks
          </p>
          <div class="grid grid-cols-3 gap-3">
            <div
              class="p-4 rounded-xl bg-primary/10 hover:bg-primary/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Total</p>
              <p class="text-xl font-bold">{{ stats.totalTasks }}</p>
            </div>
            <div
              class="p-4 rounded-xl bg-green-500/10 hover:bg-green-500/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Completed</p>
              <p class="text-xl font-bold text-green-400">{{ stats.completedTasks }}</p>
            </div>
            <div
              class="p-4 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Running</p>
              <p class="text-xl font-bold text-blue-400">{{ stats.runningTasks }}</p>
            </div>
          </div>
        </div>

        <!-- Projects Section -->
        <div>
          <p class="font-extrabold text-lg text-gray-300 mb-2 flex items-center gap-2">
            <PackagePlus class="w-5 h-5 text-primary" /> Projects
          </p>
          <div class="grid grid-cols-3 gap-3">
            <div
              class="p-4 rounded-xl bg-primary/10 hover:bg-primary/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Total</p>
              <p class="text-xl font-bold">{{ stats.totalProjects }}</p>
            </div>
            <div
              class="p-4 rounded-xl bg-green-500/10 hover:bg-green-500/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Completed</p>
              <p class="text-xl font-bold text-green-400">
                {{ stats.completedProjects }}
              </p>
            </div>
            <div
              class="p-4 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Running</p>
              <p class="text-xl font-bold text-blue-400">{{ stats.runningProjects }}</p>
            </div>
          </div>
        </div>

        <!-- Account Section -->
        <div>
          <p class="font-extrabold text-lg text-gray-300 mb-2 flex items-center gap-2">
            <List class="w-5 h-5 text-primary" /> Account
          </p>
          <div
            class="p-4 rounded-xl bg-primary/10 hover:bg-primary/20 transition cursor-pointer"
          >
            <p class="text-sm text-gray-400">Email</p>
            <p class="text-md font-bold">{{ member.email }}</p>
          </div>
        </div>
      </div>
    </template>
  </Dialog>

  <Dialog v-model="isEditTaskOpen">
    <template #header>
      <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Update Task</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Modify the task details below
      </p>
    </template>
    <template #body>
      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input v-model="form.title" placeholder="Task Title" class="w-full" />
            <InputError :message="form.errors.title" />
          </div>

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

          <div class="w-full grid grid-cols-2 gap-2">
            <div v-if="props.names">
              <label class="block text-sm font-medium mb-1">New Assignee</label>
              <Dropdown
                v-model="form.to_id"
                :options="
                  props.names.map((user) => ({ label: user.name, value: user.id }))
                "
              />
              <InputError :message="form.errors.to_id" />
            </div>

            <!-- Project field -->
            <div v-if="props.manager_of">
              <label class="block text-sm font-medium mb-1">New Project</label>
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
          <!-- Role field -->
          <div class="w-full grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium mb-1">Role</label>
              <Dropdown v-model="form.role_title" :options="roleOptions" />
            </div>

            <!-- Status field -->
            <div>
              <label class="block text-sm font-medium mb-1">Status</label>
              <Dropdown v-model="form.status" :options="statusOptions" />
            </div>
          </div>
          <!-- Priority and Deadline -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Priority</label>
              <Dropdown
                v-model="form.priority"
                :options="[
                  { label: 'Low', value: 'low' },
                  { label: 'Medium', value: 'medium' },
                  { label: 'High', value: 'high' },
                ]"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Deadline</label>
              <Picker v-model="form.deadline" />
            </div>
          </div>
        </div>
      </form>
    </template>
    <template #footer>
      <div class="flex justify-end gap-3">
        <Button variant="outline" @click="closeEditDialog"> Cancel </Button>
        <Button @click="submitForm" :disabled="form.processing"> Update Task </Button>
      </div>
    </template>
  </Dialog>

  <Dialog v-model="isNewTaskDialogOpen">
    <template #header>
      <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Create New Task</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Add a new task for this member
      </p>
    </template>

    <template #body>
      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input v-model="form.title" placeholder="Task Title" class="w-full" />
            <InputError :message="form.errors.title" />
          </div>

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

          <!-- Project field -->
          <div v-if="props.manager_of">
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

          <!-- Role field -->
          <div class="w-full grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium mb-1">Role</label>
              <Dropdown v-model="form.role_title" :options="roleOptions" />
            </div>

            <!-- Status field -->
            <div>
              <label class="block text-sm font-medium mb-1">Status</label>
              <Dropdown v-model="form.status" :options="statusOptions" />
            </div>
          </div>

          <!-- Priority and Deadline -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Priority</label>
              <Dropdown
                v-model="form.priority"
                :options="[
                  { label: 'Low', value: 'low' },
                  { label: 'Medium', value: 'medium' },
                  { label: 'High', value: 'high' },
                ]"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Deadline</label>
              <Picker v-model="form.deadline" />
            </div>
          </div>
        </div>
      </form>
    </template>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Button variant="outline" @click="closeNewTaskDialog"> Cancel </Button>
        <Button @click="submitForm" :disabled="form.processing"> Create Task </Button>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import Button from "@/components/ui/button/Button.vue";
import Dialog from "@/components/ui/simpledialog/Dialog.vue";
import Input from "@/components/ui/input/Input.vue";
import Viewer from "@/components/ui/md/viewer.vue";
import Dropdown from "@/components/ui/select/Select.vue";
import Picker from "@/components/ui/date/Picker.vue";
import { getInitials } from "@/composables/useInitials";
import AppLayout from "@/layouts/AppLayout.vue";
import { toTitleCase } from "@/lib/utils";
import { Head, useForm } from "@inertiajs/vue3";
import {
  Activity,
  AlertTriangle,
  BarChart3,
  Calendar,
  ChevronDown,
  ChevronRight,
  Edit,
  Eye,
  Filter,
  Flame,
  Gauge,
  Leaf,
  List,
  ListCheckIcon,
  Loader2,
  PenLine,
  Plus,
  Search,
  TimerIcon,
  View,
  Flag
} from "lucide-vue-next";
import { ref, computed } from "vue";
import { defineProps } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import Select from "@/components/ui/select/Select.vue";

const props = defineProps(["project", "names"]);
const user = usePage().props.auth.user;
const breadcrumbs = [
  {
    title: `${toTitleCase(props.project.title)} - Project`,
    href: `/client/projects/${props.project.id}`,
  },
];
function formatDate(date) {
  if (!date) return "";
  const d = new Date(date);
  return d.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}
const searchQuery = ref("");
const filterStatus = ref(null);
const filterPriority = ref(null);

// Modified to attach project to each task
const filteredTasks = computed(() => {
  let tasks = props.project.tasks.map(task => ({
    ...task,
    project: props.project // Attach project to each task
  }));
  
  if (searchQuery.value) {
    tasks = tasks.filter((task) =>
      task.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
  if (filterStatus.value) {
    tasks = tasks.filter((task) => task.status === filterStatus.value);
  }
  if (filterPriority.value) {
    tasks = tasks.filter((task) => task.priority === filterPriority.value);
  }
  return tasks;
});

// Group tasks by status
const tasksByStatus = computed(() => {
  const statusGroups = {
    pending: [],
    in_progress: [],
    testing: [],
    review: [],
    completed: [],
    cancelled: [],
  };

  filteredTasks.value.forEach(task => {
    if (statusGroups[task.status]) {
      statusGroups[task.status].push(task);
    } else {
      statusGroups.pending.push(task); // Fallback to pending if status is unknown
    }
  });

  // Remove empty groups
  const result = {};
  for (const status in statusGroups) {
    if (statusGroups[status]?.length > 0) {
      result[status] = statusGroups[status];
    }
  }

  return result;
});

// For collapsible columns
const openStatusIds = ref([
  "pending",
  "in_progress",
  "testing",
  "review",
  "completed",
  "cancelled",
]);

const toggleStatus = (status) => {
  if (openStatusIds.value.includes(status)) {
    openStatusIds.value = openStatusIds.value.filter((s) => s !== status);
  } else {
    openStatusIds.value.push(status);
  }
};

const viewTask = ref(null);
const isViewDialogOpen = ref(false);
const showDescription = ref(true);
const note = ref("");

function openTask(task) {
  viewTask.value = task;
  isViewDialogOpen.value = true;
  console.log("Dialog should open. Task:", task.title);
}

const editTask = ref();
const isEditOpen = ref(false);
const isAddOpen = ref(false);
const isDetailsDialogOpen = ref(false);

function openEdit(task) {
  // Populate form with existing task data
  taskForm.title = task.title;
  taskForm.description = task.description;
  taskForm.priority = task.priority;
  taskForm.status = task.status;
  taskForm.deadline = task.deadline;
  taskForm.to_id = task.assignee ? task.assignee.id : null;
  taskForm.role_title = task.role_title || "not-assigned";

  editTask.value = task;
  isViewDialogOpen.value = false;
  isEditOpen.value = true;
}

function openAddDialog() {
  // Reset form
  taskForm.title = "";
  taskForm.description = "";
  taskForm.priority = "medium";
  taskForm.status = "pending";
  taskForm.deadline = "";
  isAddOpen.value = true;
}
const isNotesDialogOpen = ref(false);
function closeDialog() {
  isViewDialogOpen.value = false;
  viewTask.value = null;
  note.value = "";
  console.log("Dialog closed");
}

function closeEditDialog() {
  isEditOpen.value = false;
  editTask.value = null;
  taskForm.clearErrors();
}

function closeAddDialog() {
  isAddOpen.value = false;
  taskForm.clearErrors();
}

// Task form with auto-filled fields
const taskForm = useForm({
  title: "",
  description: "",
  priority: "medium",
  status: "pending",
  deadline: "",
  project_id: props.project.id,
  by_id: user.id,
  to_id: props.project.manager_id,
  role_title: "not-assigned",
});

function submitTaskForm() {
  if (isEditOpen.value && editTask.value) {
    // Update existing task
    taskForm.put(`/tasks/update/${editTask.value.id}`, {
      onSuccess: () => {
        closeEditDialog();
        taskForm.reset();
      },
    });
  } else {
    // Create new task
    taskForm.post("/tasks/create", {
      onSuccess: () => {
        closeAddDialog();
        taskForm.reset();
      },
    });
  }
}

async function addNote(context = "task") {
  const content = note.value.trim();
  if (!content) return; // prevent empty notes

  try {
    const response = await axios.post("/notes", {
      content,
      context: context,
      context_id: context === "task" ? viewTask.value.id : props.project.id,
      member_id: user.id,
    });

    // Add new note to the current task's notes
    if (context === "task") {
      viewTask.value.notes.push(response.data.note);
    } else {
      props.project.notes.push(response.data.note);
    }

    note.value = ""; // reset textarea after submit
  } catch (error) {
    console.error("Error adding note:", error);
    alert("Failed to add note. Please try again.");
  }
}

// Form options
const priorityOptions = [
  { value: "low", label: "Low" },
  { value: "medium", label: "Medium" },
  { value: "high", label: "High" },
];

const statusOptions = [
  { value: "pending", label: "Pending" },
  { value: "in_progress", label: "In Progress" },
  { value: "testing", label: "Testing" },
  { value: "review", label: "Review" },
  { value: "completed", label: "Completed" },
  { value: "cancelled", label: "Cancelled" },
];
const openFilter = ref(false);
</script>

<template>
  <Head :title="toTitleCase(project.title) + ' - Project'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col sm:flex-row w-full h-full">
      <div class="w-full h-full p-1">
        <div class="border-b-2 flex flex-row justify-between p-1 items-center gap-2">
          <div class="w-full sm:w-lg flex flex-row">
            <Input
              v-model="searchQuery"
              class="transition-all duration-300 ease-in-out rounded-r-none"
              placeholder="Search tasks..."
            />
            <Button class="rounded-none rounded-tr-lg rounded-br-lg outline-1">
              <Search />
            </Button>
            <div class="w-fit gap-2 ml-2 flex-row items-center hidden sm:flex">
              <Dropdown
                v-model="filterPriority"
                :options="[
                  { label: 'All', value: null },
                  { label: 'Low', value: 'low' },
                  { label: 'Medium', value: 'medium' },
                  { label: 'High', value: 'high' },
                ]"
              />
              <Dropdown
                v-model="filterStatus"
                :options="[
                  { label: 'All', value: null },
                  { label: 'Pending', value: 'pending' },
                  { label: 'Running', value: 'in_progress' },
                  { label: 'Review', value: 'review' },
                  { label: 'Testing', value: 'testing' },
                  { label: 'Completed', value: 'completed' },
                  { label: 'Cancelled', value: 'cancelled' },
                ]"
              />
            </div>
          </div>
          <div class="relative sm:hidden">
            <!-- Dropdown Menu -->
            <transition
              enter-active-class="transition ease-out duration-200"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition ease-in duration-150"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="openFilter"
                class="absolute mt-2 w-fit bg-gray-900 border border-gray-700 rounded-lg shadow-lg p-3 z-50 flex flex-col gap-3"
              >
                <Dropdown
                  v-model="filterPriority"
                  :options="[
                    { label: 'All', value: null },
                    { label: 'Low', value: 'low' },
                    { label: 'Medium', value: 'medium' },
                    { label: 'High', value: 'high' },
                  ]"
                />

                <Dropdown
                  v-model="filterStatus"
                  :options="[
                    { label: 'All', value: null },
                    { label: 'Pending', value: 'pending' },
                    { label: 'Running', value: 'in_progress' },
                    { label: 'Review', value: 'review' },
                    { label: 'Testing', value: 'testing' },
                    { label: 'Completed', value: 'completed' },
                    { label: 'Cancelled', value: 'cancelled' },
                  ]"
                />
                <Button size="sm" @click="openFilter = false">Close</Button>
              </div>
            </transition>
          </div>
          <div class="w-fit sm:gap-2 sm:px-1 items-center flex flex-row">
            <Button
              variant="outline"
              class="flex sm:hidden"
              @click="openFilter = !openFilter"
            >
              <Filter />
            </Button>
            <Button
              variant="outline"
              class="rounded-none rounded-tl-lg rounded-bl-lg sm:rounded-lg"
              @click="openAddDialog"
            >
              <p class="hidden sm:flex">New</p>
              <Plus />
            </Button>
            <Button
              class="rounded-none sm:rounded-lg"
              @click="isDetailsDialogOpen = true"
            >
              <List />
              <p class="hidden sm:flex">Details</p>
            </Button>
            <Button
              class="rounded-none rounded-tr-lg rounded-br-lg sm:rounded-lg"
              @click="isNotesDialogOpen = true"
            >
              <PenLine />
              <p class="hidden sm:flex">Notes</p>
            </Button>
          </div>
        </div>

        <!-- New Column-based Task Layout -->
        <div class="flex-grow overflow-auto p-2">
          <div class="flex flex-col md:flex-row md:overflow-x-auto gap-2 md:min-w-fit items-start">
            <div
              v-for="(tasks, status) in tasksByStatus"
              :key="status"
              class="flex flex-col min-h-0 w-full md:w-80 md:flex-shrink-0 rounded-xl overflow-hidden border-2 border-primary"
            >
              <div
                class="bg-primary px-2 py-4 flex justify-between items-center hover:cursor-pointer hover:bg-primary transition-colors duration-200"
                @click="toggleStatus(status)"
              >
                <h2 class="text-lg font-bold">
                  {{ toTitleCase(status.replace("_", " ")) }}
                </h2>
                <div class="flex items-center">
                  <span class="text-xs bg-secondary/60 px-2 py-1 rounded-full mr-2">
                    {{ tasks.length }} tasks
                  </span>
                  <ChevronDown
                    v-if="openStatusIds.includes(status)"
                    class="w-5 h-5 transition-transform duration-300 transform rotate-180"
                  />
                  <ChevronRight v-else class="w-5 h-5 transition-transform duration-300" />
                </div>
              </div>

              <!-- Collapsible Task List -->
              <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform opacity-0 -translate-y-2"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-2"
              >
                <div
                  v-show="openStatusIds.includes(status)"
                  class="overflow-hidden flex flex-col min-h-0"
                >
                  <div v-if="tasks.length" class="flex flex-col">
                    <div
                      v-for="task in tasks"
                      :key="task.id"
                      class="p-2 w-full border-b-2 border-primary/30 flex flex-col hover:bg-primary/10 transition-colors duration-150"
                      :class="task.status === 'cancelled' ? 'text-red-300' : 'text-white'"
                    >
                      <div class="w-full flex flex-row items-center justify-between mb-1">
                        <div
                          class="flex items-center gap-2 hover:underline hover:cursor-pointer font-bold text-md"
                          @click="openTask(task)"
                        >
                          <p>{{ toTitleCase(task.title) }}</p>
                        </div>
                        <Edit
                          class="w-4 h-4 hover:bg-primary hover:cursor-pointer rounded-sm"
                          @click="openEdit(task)"
                        />
                      </div>
                      <div class="flex flex-wrap items-center gap-2 text-xs text-gray-200">
                        <div class="flex items-center gap-1 text-gray-300">
                          <span class="opacity-80">For</span>
                          <span class="font-semibold text-gray-100">
                            {{ toTitleCase(task.assignee?.name) || "Unassigned" }}
                          </span>
                        </div>
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
                  <div v-else class="text-sm italic px-2 py-2 text-center">
                    No tasks in this status.
                  </div>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Details Dialog -->
    <Dialog v-model="isDetailsDialogOpen">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold flex items-center gap-2 capitalize truncate">
              {{ project.title }}
            </h2>
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
      <template #body>
        <div class="w-full h-full">
          <div v-if="user.role !== 'client'">
            <span class="text-sm text-gray-300">Managed by: </span>
            {{ project.manager.name }}
          </div>
          <span class="text-sm text-gray-300">Description</span>
          <Viewer v-if="project.description?.length > 0" :source="project.description" />
          <div v-else class="px-3 py-1 italic text-gray-400 text-xs">
            No Description provided!
          </div>
          <div class="h-4" />
        </div>
      </template>
    </Dialog>
    
    <!-- View Task Dialog -->
    <Dialog v-model="isViewDialogOpen" v-if="viewTask">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold flex items-center gap-2 capitalize truncate">
              {{ viewTask.title }}
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
              {{ viewTask.status.replace("_", " ") }}
            </span>
          </div>
        </div>
      </template>

      <template #body>
        <div class="flex flex-col gap-3 text-sm p-1 animate-fadeIn">
          <!-- Info grid -->
          <div class="grid grid-cols-2">
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

          <!-- Notes -->
          <div class="flex flex-col rounded-xl bg-card shadow w-full gap-2">
            <div
              v-if="viewTask.notes?.length > 0"
              class="flex flex-col gap-2 max-h-100 overflow-y-auto pr-1"
            >
              <div
                v-for="note in viewTask.notes"
                :key="note.id"
                class="p-2 rounded-lg bg-secondary border border-indigo-950 shadow-sm hover:shadow-md transition"
              >
                <p class="text-sm text-white leading-snug">{{ note.content }}</p>
                <div class="w-full justify-between flex flex-row h-fit gap-2">
                  <p class="text-xs text-gray-400">— {{ note.member.name }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-sm italic text-gray-500 py-3">
              No notes yet...
            </div>

            <!-- Add note input -->
            <div class="flex items-end gap-2 border-t border-white/10 pt-2">
              <textarea
                v-model="note"
                placeholder="Your note here..."
                class="flex-1 text-sm h-[33px] max-h-[100px] p-1.5 rounded-md bg-white/10 text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-ring min-h-[33px]"
              ></textarea>
              <Button
                size="xs"
                class="px-2 py-2 flex items-center gap-1 rounded-md shadow-sm transition text-white text-xs"
                @click="addNote('task')"
              >
                <Plus class="w-3 h-3" /> Add
              </Button>
            </div>
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Edit Task Dialog -->
    <Dialog v-model="isEditOpen">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <h2 class="text-xl font-bold">Edit Task</h2>
        </div>
      </template>

      <template #body>
        <form @submit.prevent="submitTaskForm" class="flex flex-col gap-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input v-model="taskForm.title" placeholder="Task Title" />
            <div v-if="taskForm.errors.title" class="text-red-500 text-xs mt-1">
              {{ taskForm.errors.title }}
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="taskForm.description"
              placeholder="Task Description"
              class="border rounded p-2 text-sm w-full"
              rows="3"
            ></textarea>
            <div v-if="taskForm.errors.description" class="text-red-500 text-xs mt-1">
              {{ taskForm.errors.description }}
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div v-if="user.role !== 'client'">
              <label class="block text-sm font-medium mb-1">Assignee</label>
              <Dropdown
                v-model="taskForm.to_id"
                :options="names"
                placeholder="Select Assignee"
              />
              <div v-if="taskForm.errors.to_id" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.to_id }}
              </div>
            </div>
            <!-- Priority -->
            <div>
              <label class="block text-sm font-medium mb-1">Priority</label>
              <Dropdown
                v-model="taskForm.priority"
                :options="priorityOptions"
                placeholder="Select Priority"
              />
              <div v-if="taskForm.errors.priority" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.priority }}
              </div>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium mb-1">Status</label>
              <Dropdown
                v-model="taskForm.status"
                :options="statusOptions"
                placeholder="Select Status"
              />
              <div v-if="taskForm.errors.status" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.status }}
              </div>
            </div>

            <div v-if="user.role !== 'client'">
              <label class="block text-sm font-medium mb-1">Role</label>
              <Dropdown
                v-model="taskForm.role_title"
                :options="[
                  { label: 'Frontend Developer', value: 'frontend-developer' },
                  { label: 'Backend Developer', value: 'backend-developer' },
                  { label: 'Fullstack Developer', value: 'fullstack-developer' },
                  { label: 'Tester', value: 'tester' },
                  { label: 'Designer', value: 'designer' },
                  { label: 'DevOps Engineer', value: 'devops-engineer' },
                ]"
                placeholder="Select Role"
              />
              <div v-if="taskForm.errors.role_title" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.role_title }}
              </div>
            </div>
          </div>

          <!-- Deadline -->
          <div>
            <label class="block text-sm font-medium mb-1">Deadline</label>
            <Picker v-model="taskForm.deadline" />
            <div v-if="taskForm.errors.deadline" class="text-red-500 text-xs mt-1">
              {{ taskForm.errors.deadline }}
            </div>
          </div>
        </form>
      </template>

      <template #footer>
        <Button @click="submitTaskForm" :disabled="taskForm.processing">
          {{ taskForm.processing ? "Saving..." : "Update Task" }}
        </Button>
      </template>
    </Dialog>

    <!-- Add Task Dialog -->
    <Dialog v-model="isAddOpen">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <h2 class="text-xl font-bold">Add New Task</h2>
        </div>
      </template>

      <template #body>
        <form @submit.prevent="submitTaskForm" class="flex flex-col gap-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input v-model="taskForm.title" placeholder="Task Title" />
            <div v-if="taskForm.errors.title" class="text-red-500 text-xs mt-1">
              {{ taskForm.errors.title }}
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="taskForm.description"
              placeholder="Task Description"
              class="border rounded p-2 text-sm w-full"
              rows="3"
            ></textarea>
            <div v-if="taskForm.errors.description" class="text-red-500 text-xs mt-1">
              {{ taskForm.errors.description }}
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div v-if="user.role !== 'client'">
              <label class="block text-sm font-medium mb-1">Assignee</label>
              <Dropdown
                v-model="taskForm.to_id"
                :options="names"
                placeholder="Select Assignee"
              />
              <div v-if="taskForm.errors.to_id" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.to_id }}
              </div>
            </div>
            <!-- Priority -->
            <div>
              <label class="block text-sm font-medium mb-1">Priority</label>
              <Dropdown
                v-model="taskForm.priority"
                :options="priorityOptions"
                placeholder="Select Priority"
              />
              <div v-if="taskForm.errors.priority" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.priority }}
              </div>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium mb-1">Status</label>
              <Dropdown
                v-model="taskForm.status"
                :options="statusOptions"
                placeholder="Select Status"
              />
              <div v-if="taskForm.errors.status" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.status }}
              </div>
            </div>

            <div v-if="user.role !== 'client'">
              <label class="block text-sm font-medium mb-1">Role</label>
              <Dropdown
                v-model="taskForm.role_title"
                :options="[
                  { label: 'Frontend Developer', value: 'frontend-developer' },
                  { label: 'Backend Developer', value: 'backend-developer' },
                  { label: 'Fullstack Developer', value: 'fullstack-developer' },
                  { label: 'Tester', value: 'tester' },
                  { label: 'Designer', value: 'designer' },
                  { label: 'DevOps Engineer', value: 'devops-engineer' },
                ]"
                placeholder="Select Role"
              />
              <div v-if="taskForm.errors.role_title" class="text-red-500 text-xs mt-1">
                {{ taskForm.errors.role_title }}
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Deadline</label>
            <Picker v-model="taskForm.deadline" />
            <div v-if="taskForm.errors.deadline" class="text-red-500 text-xs mt-1">
              {{ taskForm.errors.deadline }}
            </div>
          </div>
        </form>
      </template>

      <template #footer>
        <Button @click="submitTaskForm" :disabled="taskForm.processing">
          {{ taskForm.processing ? "Creating..." : "Create Task" }}
        </Button>
      </template>
    </Dialog>
    <Dialog v-model="isNotesDialogOpen">
      <template #header> Notes </template>
      <template #body>
        <div
          v-if="project.notes?.length > 0"
          class="flex flex-col gap-2 max-h-60 overflow-y-auto pr-1"
        >
          <div
            v-for="note in project.notes"
            :key="note.id"
            class="p-2 rounded-lg bg-secondary border border-indigo-950 shadow-sm hover:shadow-md transition"
          >
            <p class="text-sm text-white leading-snug">{{ note.content }}</p>
            <div class="w-full justify-between flex flex-row h-fit gap-2">
              <p class="text-xs text-gray-400">— {{ note.member.name }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center text-sm italic text-gray-500 py-3">
          No notes yet...
        </div>
      </template>
      <template #footer>
        <div class="flex gap-2 items-end w-full h-full">
          <textarea
            v-model="note"
            placeholder="Your note here..."
            class="flex-1 text-sm h-full max-h-80 p-1.5 rounded-md bg-white/10 text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-ring min-h-[33px]"
          ></textarea>
          <Button
            size="xs"
            class="px-2 py-2 flex items-center gap-1 rounded-md shadow-sm transition text-white text-xs h-8"
            @click="addNote('proj')"
          >
            <Plus class="w-3 h-3" /> Add
          </Button>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>
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
  BarChart3,
  Calendar,
  ChevronDown,
  Edit,
  Eye,
  Gauge,
  List,
  ListCheckIcon,
  Loader2,
  PenLine,
  Plus,
  Search,
  TimerIcon,
  View,
} from "lucide-vue-next";
import { ref, computed } from "vue";
import { defineProps } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps(["project"]);
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

const filteredTasks = computed(() => {
  if (!searchQuery.value) return props.project.tasks;
  return props.project.tasks.filter((task) =>
    task.title.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

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

function openEdit(task) {
  // Populate form with existing task data
  taskForm.title = task.title;
  taskForm.description = task.description;
  taskForm.priority = task.priority;
  taskForm.status = task.status;
  taskForm.deadline = task.deadline;

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
      context_id: context === 'task' ? viewTask.value.id : props.project.id,
      member_id: user.id,
    });

    // Add new note to the current task's notes
    if (context === 'task') {
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
</script>

<template>
  <Head :title="toTitleCase(project.title) + ' - Project'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col sm:flex-row w-full h-full">
      <div class="w-full sm:w-[30%] h-full p-4 bg-primary/20">
        <div class="w-full justify-between flex flex-row">
          <p class="text-2xl font-extrabold mb-2">
            {{ toTitleCase(project.title) }}
          </p>
          <Button
            class="text-sm bg-secondary border-2 border-primary/20"
            @click="isNotesDialogOpen = true"
          >
            <PenLine />
          </Button>
        </div>
        <!-- Manager -->
        <div
          class="flex items-center flex-row gap-3 mb-2 border border-primary/40 bg-gray-800/30 rounded-xl p-3 shadow-sm"
        >
          <div class="flex flex-row gap-3">
            <div
              class="flex w-10 h-10 items-center justify-center rounded-full bg-primary text-white font-bold"
            >
              {{ getInitials(project.manager.name) }}
            </div>
            <div>
              <p class="text-xs text-gray-400">Managed by</p>
              <p class="text-base font-semibold text-gray-100">
                {{ toTitleCase(project.manager.name) }}
              </p>
            </div>
          </div>
          <div class="flex flex-row gap-3">
            <div
              class="flex w-10 h-10 items-center justify-center rounded-full bg-primary text-white font-bold"
            >
              <Gauge />
            </div>
            <div>
              <p class="text-xs text-gray-400">Status</p>
              <span
                class="inline-block text-md font-semibold"
                :class="{
                  'text-green-500': project.status === 'completed',
                  'text-yellow-500': project.status === 'in_progress',
                  'text-blue-500': project.status === 'pending',
                  'text-purple-500': project.status === 'review',
                  'text-orange-500': project.status === 'testing',
                  'text-red-500': project.status === 'cancelled',
                }"
              >
                {{ toTitleCase(project.status.replace("_", " ")) }}
              </span>
            </div>
          </div>
        </div>
        <div
          class="flex items-center flex-row gap-3 mb-2 border border-primary/40 bg-gray-800/30 rounded-xl p-3 shadow-sm"
        >
          <div class="flex flex-row gap-3">
            <div
              class="flex w-10 h-10 items-center justify-center rounded-full bg-primary text-white font-bold"
            >
              <List />
            </div>
            <div>
              <p class="text-xs text-gray-400">Total Tasks</p>
              <p class="text-base font-semibold text-gray-100">
                {{ project.tasks.length }}
              </p>
            </div>
            <div
              class="flex w-10 h-10 items-center justify-center rounded-full bg-primary text-white font-bold"
            >
              <ListCheckIcon />
            </div>
            <div>
              <p class="text-xs text-gray-400">Completed</p>
              <p class="text-base font-semibold text-gray-100">
                {{ project.tasks.filter((task) => task.status === "completed").length }}
              </p>
            </div>
          </div>
        </div>

        <div class="overflow-auto max-h-100 border-b-2">
          <Viewer :source="project.description" />
        </div>
      </div>
      <div class="w-full sm:w-[70%] h-full p-1">
        <div class="border-b-2 flex flex-row justify-between p-1 items-center gap-2">
          <div class="w-full sm:w-sm flex flex-row">
            <Input
              v-model="searchQuery"
              class="transition-all duration-300 ease-in-out rounded-r-none"
              placeholder="Search tasks..."
            />
            <Button class="rounded-none rounded-tr-lg rounded-br-lg outline-1">
              <Search />
            </Button>
          </div>
          <Button @click="openAddDialog"> New <Plus /> </Button>
        </div>
        <div class="w-full px-3 gap-2 mt-4 grid grid-cols-1 sm:grid-cols-3">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="p-4 rounded-xl shadow-lg text-white transition transform hover:scale-[1.005] hover:shadow-xl flex flex-row justify-between"
            :class="
              task.status === 'cancelled'
                ? 'bg-red-600'
                : 'bg-gradient-to-br from-[#5a248a] to-secondary'
            "
          >
            <span class="font-bold">{{ toTitleCase(task.title) }}</span>
            <div class="flex flex-row gap-1">
              <button
                @click="openTask(task)"
                class="w-fit cursor-pointer h-fit hover:bg-primary rounded-sm p-0.5"
              >
                <Eye class="w-5 h-5" />
              </button>
              <button
                @click="openEdit(task)"
                class="w-fit cursor-pointer h-fit hover:bg-primary rounded-sm p-0.5"
              >
                <Edit class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- View Task Dialog -->
    <Dialog v-model="isViewDialogOpen" v-if="viewTask">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold flex items-center gap-2 capitalize">
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
                @click="addNote"
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
            class="flex-1 text-sm h-full max-h-80 p-1.5 
                    rounded-md bg-white/10 text-white
                  placeholder-gray-400 focus:outline-none
                    focus:ring-1 focus:ring-ring
                    min-h-[33px]"
          ></textarea>
          <Button
            size="xs"
            class="px-2 py-2 flex items-center gap-1
                    rounded-md shadow-sm transition
                  text-white text-xs h-8"
            @click="addNote(context='proj')"
          >
            <Plus class="w-3 h-3" /> Add
          </Button>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

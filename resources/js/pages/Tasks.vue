<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage, router, useForm } from "@inertiajs/vue3";
import Button from "@/components/ui/button/Button.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Input from "@/components/ui/input/Input.vue";
import Dropdown from "@/components/ui/select/Select.vue";
import InputError from "@/components/InputError.vue";
import { ref, watch } from "vue";
import { Edit, Eye, Search } from "lucide-vue-next";

const breadcrumbs = [{ title: "Tasks", href: "/tasks" }];

const props = defineProps({
  tasks: Array,
  manager_of: Array,
  names: Array,
  roles: Array,
});

const user = usePage().props.auth.user;

// 🔑 Local reactive state
const s_query = ref("");
const tasksList = ref([...props.tasks]);
const filteredTasks = ref([...props.tasks]);

// keep local list in sync with server props
watch(
  () => props.tasks,
  (newTasks) => {
    tasksList.value = [...newTasks];
    search();
  },
  { deep: true }
);

// search handling
watch(s_query, () => search());

function search() {
  if (!s_query.value.trim()) {
    filteredTasks.value = [...tasksList.value];
    return;
  }
  const q = s_query.value.toLowerCase();
  filteredTasks.value = tasksList.value.filter(
    (task) =>
      task.title.toLowerCase().includes(q) ||
      task.manager?.toLowerCase().includes(q) ||
      task.assignee?.toLowerCase().includes(q) ||
      task.project?.toLowerCase().includes(q)
  );
}

// dialogs
const isDialogOpen = ref(false);
const isEditMode = ref(false);
const editId = ref(null);
const isViewDialogOpen = ref(false);
const viewTask = ref(null);

// form
const form = useForm({
  title: "",
  description: "",
  to_id: "",
  project_id: "",
  status: "pending",
  role_title: "frontend-developer",
});

// create / update
function submitForm() {
  if (isEditMode.value && editId.value) {
    form.put(`/tasks/update/${editId.value}`, {
      onSuccess: (page) => {
        tasksList.value = page.props.tasks;
        search();
        isDialogOpen.value = false;
        form.reset();
      },
    });
  } else {
    form.post("/tasks/create", {
      onSuccess: (page) => {
        tasksList.value = page.props.tasks;
        search();
        isDialogOpen.value = false;
        form.reset();
      },
    });
  }
}

// edit
function editTask(task) {
  isEditMode.value = true;
  editId.value = task.id;
  form.title = task.title;
  form.description = task.description;
  form.to_id = props.names.find((u) => u.name === task.assignee)?.id || "";
  form.project_id =
    props.manager_of.find((p) => p.title === task.project)?.id || "";
  form.status = task.status;
  form.role_title = task.role_title || "frontend-developer";
  isDialogOpen.value = true;
}

// delete
function deleteTask(id) {
  if (confirm("Are you sure you want to delete this task?")) {
    router.delete(`/tasks/delete/${id}`, {
      onSuccess: (page) => {
        tasksList.value = page.props.tasks;
        search();
      },
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
  { label: "Project Manager", value: "project-manager" },
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
</script>

<template>
  <Head title="Tasks" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col p-2">
      <!-- Search + New -->
      <div
        class="border-b-2 flex flex-row justify-between p-1 items-center gap-2 rounded-lg"
      >
        <div class="flex flex-row rounded-lg">
          <Input
            v-model="s_query"
            class="transition-all duration-300 ease-in-out rounded-r-none"
            placeholder="Search tasks..."
          />
          <Button class="rounded-l-none outline-1" @click="search">
            <Search />
          </Button>
        </div>
        <Button
          v-if="props.manager_of.length || user.role === 'admin'"
          @click="
            () => {
              isEditMode = false;
              form.reset();
              form.status = 'pending';
              isDialogOpen = true;
            }
          "
          >+ New</Button
        >
      </div>

      <!-- Tasks Grid -->
      <div
        class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3"
      >
        <template v-if="filteredTasks.length">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="p-4 rounded-xl shadow-lg text-white bg-gradient-to-br from-[#5a248a] to-secondary transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
            :class="task.status === 'cancelled' ? 'opacity-60 grayscale' : ''"
          >
            <!-- Task Info -->
            <div>
              <div
                class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
              >
                <h3 class="text-lg font-bold">
                  {{ task.title
                  }}<span class="font-extralight text-[15px] h-full">
                    ({{ task.id }})</span
                  >
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
            </div>

            <!-- Status + Actions -->
            <div
              class="mt-4 flex items-center justify-between border-t border-white/20 pt-2"
            >
              <span
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

              <div class="flex gap-0.5 text-sm">
                <button
                  class="p-1 rounded-md hover:bg-white/20 transition"
                  @click="openViewDialog(task)"
                  title="View Task"
                >
                  <Eye class="w-4 h-4 text-white" />
                </button>

                <button
                  class="p-1 rounded-md hover:bg-white/20 transition"
                  @click="editTask(task)"
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
                s_query
                  ? "Try adjusting your search terms."
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
                  isDialogOpen = true;
                }
              "
            >
              + Assign First Task
            </Button>
          </div>
        </template>
      </div>
    </div>

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
          <h2 class="text-xl font-bold flex items-center gap-2 capitalize">
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

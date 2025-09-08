<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage, router, useForm } from "@inertiajs/vue3";
import Table from "@/components/ui/table/Table.vue";
import Button from "@/components/ui/button/Button.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Input from "@/components/ui/input/Input.vue";
import Label from "@/components/ui/label/Label.vue";
import Select from "@/components/ui/select/select.vue";
import InputError from "@/components/InputError.vue";
import { ref, watch } from "vue";
import { Search } from "lucide-vue-next";

const breadcrumbs = [{ title: "Tasks", href: "/tasks" }];

const props = defineProps({
  tasks: Array,
  manager_of: Array,
  names: Array,
});

const user = usePage().props.auth.user;

// Search
const s_query = ref("");
const filteredTasks = ref([...props.tasks]);
watch(s_query, () => search());

function search() {
  if (!s_query.value.trim()) {
    filteredTasks.value = [...props.tasks];
    return;
  }
  const q = s_query.value.toLowerCase();
  filteredTasks.value = props.tasks.filter(
    (task) =>
      task.title.toLowerCase().includes(q) ||
      task.manager?.toLowerCase().includes(q) ||
      task.assignee?.toLowerCase().includes(q) ||
      task.project?.toLowerCase().includes(q)
  );
}

// Dialog state
const isDialogOpen = ref(false);
const isEditMode = ref(false);
const editId = ref(null);

const isViewDialogOpen = ref(false);
const viewTask = ref(null);

// Form
const form = useForm({
  title: "",
  description: "",
  to_id: "",
  project_id: "",
  status: "pending",
});

// Create or update
function submitForm() {
  if (isEditMode.value && editId.value) {
    form.put(`/tasks/update/${editId.value}`, {
      onSuccess: () => {
        isDialogOpen.value = false;
        router.visit("/tasks");
      },
    });
  } else {
    form.post("/tasks/create", {
      onSuccess: () => {
        isDialogOpen.value = false;
        router.visit("/tasks");
      },
    });
  }
}

// Edit
function editTask(task) {
  isEditMode.value = true;
  editId.value = task.id;
  form.title = task.title;
  form.description = task.description;
  form.to_id = props.names.find((u) => u.name === task.assignee)?.id || "";
  form.project_id =
    props.manager_of.find((p) => p.title === task.project)?.id || "";
  form.status = task.status;
  isDialogOpen.value = true;
}

// Delete
function deleteTask(id) {
  if (confirm("Are you sure you want to delete this task?")) {
    router.delete(`/tasks/delete/${id}`, {
      onSuccess: () => {
        router.visit("/tasks");
      },
    });
  }
}

// View
function openViewDialog(task) {
  viewTask.value = task;
  isViewDialogOpen.value = true;
}
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

      <!-- Tasks Table -->
      <Table
        :rows="filteredTasks"
        table-title="Tasks"
        :headers="['id', 'title', 'assignee', 'manager', 'project', 'status']"
        :action="true"
      >
        <template #actions="{ row }">
          <a class="hover:underline cursor-pointer" @click="openViewDialog(row)"
            >View</a
          >
          &sdot;
          <a class="hover:underline cursor-pointer" @click="editTask(row)"
            >Edit</a
          >
          &sdot;
          <a class="hover:underline cursor-pointer" @click="deleteTask(row.id)"
            >Delete</a
          >
        </template>
      </Table>
    </div>
  </AppLayout>

  <!-- Add/Edit Dialog -->
  <Dialog v-model="isDialogOpen">
    <template #header>
      <h2 class="text-lg font-semibold">
        {{ isEditMode ? "Update Task" : "Assign New Task" }}
      </h2>
    </template>
    <template #body>
      <form @submit.prevent="submitForm" class="flex flex-col gap-3">
        <!-- Title -->
        <Input v-model="form.title" placeholder="Task Title" />
        <InputError :message="form.errors.title" />

        <!-- Description -->
        <textarea
          v-model="form.description"
          placeholder="Description"
          class="border rounded p-2 text-sm"
        ></textarea>
        <InputError :message="form.errors.description" />

        <!-- Assignee -->
        <Select v-model="form.to_id" required>
          <option value="">Select User</option>
          <option v-for="user in props.names" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </Select>
        <InputError :message="form.errors.to_id" />

        <!-- Project -->
        <Select v-model="form.project_id" required>
          <option value="">Select Project</option>
          <option
            v-for="project in props.manager_of"
            :key="project.id"
            :value="project.id"
          >
            {{ project.title }}
          </option>
        </Select>
        <InputError :message="form.errors.project_id" />

        <!-- Status (only on edit) -->
        <div v-if="isEditMode" class="flex flex-col gap-1">
          <label>Status</label>
          <Select v-model="form.status">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </Select>
        </div>
      </form>
    </template>
    <template #footer>
      <Button @click="isDialogOpen = false">Cancel</Button>
      <Button @click="submitForm" :disabled="form.processing">
        {{ isEditMode ? "Update" : "Create" }}
      </Button>
    </template>
  </Dialog>

  <!-- View Dialog -->
  <Dialog v-model="isViewDialogOpen">
    <template #header>
      <h2 class="text-lg font-semibold">Task Details</h2>
    </template>
    <template #body>
      <div v-if="viewTask" class="flex flex-col gap-4 text-sm p-2">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="font-medium text-gray-600">Title</p>
            <p class="text-base">{{ viewTask.title }}</p>
          </div>
          <div>
            <p class="font-medium text-gray-600">Assignee</p>
            <p class="text-base">{{ viewTask.assignee }}</p>
          </div>
          <div>
            <p class="font-medium text-gray-600">Manager</p>
            <p class="text-base">{{ viewTask.manager }}</p>
          </div>
          <div>
            <p class="font-medium text-gray-600">Project</p>
            <p class="text-base">{{ viewTask.project }}</p>
          </div>
        </div>

        <div>
          <p class="font-medium text-gray-600">Description</p>
          <textarea
            class="w-full p-2 border rounded-md text-sm bg-gray-50"
            rows="4"
            readonly
            >{{ viewTask.description || "—" }}</textarea
          >
        </div>

        <div>
          <p class="font-medium text-gray-600">Status</p>
          <span
            class="px-2 py-1 rounded-full text-xs font-semibold"
            :class="{
              'bg-yellow-100 text-yellow-700': viewTask.status === 'pending',
              'bg-blue-100 text-blue-700': viewTask.status === 'in_progress',
              'bg-green-100 text-green-700': viewTask.status === 'completed',
              'bg-red-100 text-red-700': viewTask.status === 'cancelled',
            }"
          >
            {{ viewTask.status }}
          </span>
        </div>
      </div>
    </template>
    <template #footer>
      <Button @click="isViewDialogOpen = false">Close</Button>
    </template>
  </Dialog>
</template>

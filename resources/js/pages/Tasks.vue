<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import Table from "@/components/ui/table/Table.vue";
import Button from "@/components/ui/button/Button.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Input from "@/components/ui/input/Input.vue";
import Label from "@/components/ui/label/Label.vue";
import Select from "@/components/ui/select/select.vue";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const breadcrumbs = [
  {
    title: "Tasks",
    href: "/tasks",
  },
];

const props = defineProps({
  tasks: Array,
  manager_of: Array,
  names: Array,
});

// Convert tasks into table-friendly rows
const rows = props.tasks.map((task) => ({
  id: task.id,
  title: task.title,
  assignee: task.assignee ?? "-",
  manager: task.manager ?? "-",
  project: task.project ?? "-",
  status: task.status,
}));

// Modal state
const new_open = ref(false);

// Form state
const form = useForm({
  title: "",
  description: "",
  to_id: "",
  project_id: "",
});

const submit = () => {
  form.post("/tasks/create", {
    onSuccess: () => {
      new_open.value = false;
      form.reset();
    },
  });
};

const user = usePage().props.auth.user;
</script>

<template>
  <Head title="Tasks" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Button
      v-if="props.manager_of.length || user.role === 'admin'"
      @click="new_open = true"
    >
      New
    </Button>

    <Table
      :rows="rows"
      table-title="Tasks"
      :headers="['id', 'title', 'assignee', 'manager', 'project', 'status']"
      :action="true"
    />

    <Dialog v-model="new_open">
      <template #header>
        <h1 class="text-lg font-bold">Assign New Task</h1>
      </template>

      <template #body>
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Title -->
          <div>
            <Label for="title">Title</Label>
            <Input id="title" v-model="form.title" type="text" required />
            <div v-if="form.errors.title" class="text-red-500 text-sm">
              {{ form.errors.title }}
            </div>
          </div>

          <!-- Description -->
          <div>
            <Label for="description">Description</Label>
            <textarea
              id="description"
              v-model="form.description"
              class="border rounded p-2 text-sm w-full"
              type="text"
              required
            />
            <div v-if="form.errors.description" class="text-red-500 text-sm">
              {{ form.errors.description }}
            </div>
          </div>

          <!-- Assignee -->
          <div>
            <Label for="to_id">Assign To</Label>
            <Select id="to_id" v-model="form.to_id" required>
              <option value="">Select User</option>
              <option v-for="user in props.names" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </Select>
            <div v-if="form.errors.to_id" class="text-red-500 text-sm">
              {{ form.errors.to_id }}
            </div>
          </div>

          <!-- Project -->
          <div>
            <Label for="project_id">Project</Label>
            <Select id="project_id" v-model="form.project_id" required>
              <option value="">Select Project</option>
              <option
                v-for="project in props.manager_of"
                :key="project.id"
                :value="project.id"
              >
                {{ project.title }}
              </option>
            </Select>
            <div v-if="form.errors.project_id" class="text-red-500 text-sm">
              {{ form.errors.project_id }}
            </div>
          </div>

          <!-- Submit -->
          <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing"> Create Task </Button>
          </div>
        </form>
      </template>
    </Dialog>
  </AppLayout>
</template>

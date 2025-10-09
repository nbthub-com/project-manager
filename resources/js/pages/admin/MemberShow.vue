<script setup>
import InputError from "@/components/InputError.vue";
import Button from "@/components/ui/button/Button.vue";
import Input from "@/components/ui/input/Input.vue";
import Dialog from "@/components/ui/simpledialog/Dialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { toTitleCase } from "@/lib/utils";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
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
  PencilLine,
} from "lucide-vue-next";
import { defineProps, ref, computed } from "vue";
import Dropdown from "@/components/ui/select/Select.vue";
import Picker from "@/components/ui/date/Picker.vue";
import { watchEffect } from "vue";
import Viewer from "@/components/ui/md/viewer.vue";
import axios from "axios";
import { useNotes } from "@/composables/useNotes";

// Define props
const props = defineProps([
  "member",
  "names",
  "roles",
  "manager_of",
  "clients",
  "managers",
]);

const member = props.member;
const page = usePage();
const user = page.props.auth.user;
const title =
  toTitleCase(member.name) + " (" + (member.role === "user" ? "Member" : "Client") + ")";
const href_ = "/admin/members/" + member.id.toString();
const breadcrumbs = [{ title: title, href: href_ }];

// Collapsibles
const openStatusIds = ref([
  "pending",
  "in_progress",
  "testing",
  "review",
  "completed",
  "cancelled",
]);
const searchQuery = ref("");
const isDetailsOpen = ref(false);
const isNewProjectOpen = ref(false);
const isEditTaskOpen = ref(false);
const isEditProjectOpen = ref(false);
const currentTask = ref();
const currentProj = ref();
const isEditMode = ref(false);
const editId = ref(null);

// Compute all tasks grouped by status
const tasksByStatus = computed(() => {
  const allTasks = [];

  // Collect tasks from assigned_tasks
  if (member.assigned_tasks) {
    allTasks.push(...member.assigned_tasks);
  }

  // Collect tasks from managed_projects
  if (member.managed_projects) {
    member.managed_projects.forEach((project) => {
      if (project.tasks) {
        allTasks.push(...project.tasks);
      }
    });
  }

  // Collect tasks from client_projects
  if (member.client_projects) {
    member.client_projects.forEach((project) => {
      if (project.tasks) {
        allTasks.push(...project.tasks);
      }
    });
  }

  // Remove duplicates based on task ID
  const uniqueTasks = Array.from(
    new Map(allTasks.map((task) => [task.id, task])).values()
  );

  // Filter tasks by search query
  const query = searchQuery.value.toLowerCase();
  const filteredTasks = query
    ? uniqueTasks.filter(
        (task) =>
          task.title.toLowerCase().includes(query) ||
          task.project?.title?.toLowerCase()?.includes(query) ||
          false
      )
    : uniqueTasks;

  // Group tasks by status
  const statusGroups = {
    pending: [],
    in_progress: [],
    testing: [],
    review: [],
    completed: [],
    cancelled: [],
  };

  filteredTasks.forEach((task) => {
    if (statusGroups[task.status]) {
      statusGroups[task.status].push(task);
    } else {
      statusGroups.pending.push(task); // Fallback to pending if status is unknown
    }
  });

  const result = {};
  for (const status in statusGroups) {
    if (statusGroups[status]?.length > 0) {
      result[status] = statusGroups[status];
    }
  }

  return result;
});

// Toggle status column
const toggleStatus = (status) => {
  if (openStatusIds.value.includes(status)) {
    openStatusIds.value = openStatusIds.value.filter((s) => s !== status);
  } else {
    openStatusIds.value.push(status);
  }
};

// Stats for member/client
const stats = computed(() => {
  if (member.role === "client") {
    const clientProjects = member.client_projects || [];
    const totalProjects = clientProjects.length;
    const completedProjects = clientProjects.filter((p) => p.status === "completed")
      .length;
    const runningProjects = clientProjects.filter((p) => p.status === "running").length;

    const allTasks = [];
    clientProjects.forEach((project) => {
      if (project.tasks) {
        allTasks.push(...project.tasks);
      }
    });
    const totalTasks = allTasks.length;
    const completedTasks = allTasks.filter((t) => t.status === "completed").length;
    const runningTasks = allTasks.filter((t) => t.status === "running").length;

    return {
      totalTasks,
      completedTasks,
      runningTasks,
      totalProjects,
      completedProjects,
      runningProjects,
    };
  } else {
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
  }
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

const projectForm = useForm({
  title: "",
  description: "",
  client: "",
  manager: "",
  status: "in_progress",
  is_starred: false,
});

const projectStatusOptions = [
  { label: "Pending", value: "pending" },
  { label: "In Progress", value: "in_progress" },
  { label: "Testing", value: "testing" },
  { label: "Review", value: "review" },
  { label: "Completed", value: "completed" },
  { label: "Cancelled", value: "cancelled" },
];

const clientOptions = computed(() => {
  return props.clients?.map((name) => ({ label: name, value: name })) || [];
});

const managerOptions = computed(() => {
  return props.managers?.map((name) => ({ label: name, value: name })) || [];
});

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
        if (member.assigned_tasks) {
          const assignedIndex = member.assigned_tasks.findIndex(
            (t) => t.id === editId.value
          );
          if (assignedIndex !== -1) {
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

        const updateTaskInProjects = (projects) => {
          projects?.forEach((project) => {
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
        };

        updateTaskInProjects(member.managed_projects);
        updateTaskInProjects(member.client_projects);

        isEditTaskOpen.value = false;
        resetForm();
      },
    });
  } else {
    form.post("/tasks/create", {
      onSuccess: (resp) => {
        const newTask = resp?.props?.task || {
          id: Date.now(),
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

        if (!member.assigned_tasks) member.assigned_tasks = [];
        member.assigned_tasks.push(newTask);

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

const isNewTaskDialogOpen = ref(false);

function openNewTaskDialog() {
  resetForm();
  form.status = "pending";
  form.to_id = member.id;
  isNewTaskDialogOpen.value = true;
}

function closeNewTaskDialog() {
  isNewTaskDialogOpen.value = false;
  resetForm();
}

function openNewProjectDialog() {
  projectForm.reset();
  projectForm.status = "in_progress";
  projectForm.is_starred = false;

  if (member.role === "client") {
    projectForm.client = member.name;
  } else if (member.role === "user") {
    projectForm.manager = member.name;
  }

  isNewProjectOpen.value = true;
}

function closeNewProjectDialog() {
  isNewProjectOpen.value = false;
  projectForm.reset();
}

function submitProjectForm() {
  projectForm.post("/admin/projects/create", {
    onSuccess: (response) => {
      isNewProjectOpen.value = false;

      const newProject = response.props?.project || {
        id: Date.now(),
        title: projectForm.title,
        description: projectForm.description,
        status: projectForm.status,
        is_starred: projectForm.is_starred,
        client: projectForm.client ? { name: projectForm.client } : null,
        manager: projectForm.manager ? { name: projectForm.manager } : null,
        tasks: [],
      };

      if (member.role === "client") {
        if (!member.client_projects) member.client_projects = [];
        member.client_projects.push(newProject);
      } else {
        if (!member.managed_projects) member.managed_projects = [];
        member.managed_projects.push(newProject);
      }

      projectForm.reset();
    },
  });
}

function openEditProjectDialog(project) {
  projectForm.reset();
  projectForm.title = project.title;
  projectForm.description = project.description;
  projectForm.client = project.client?.name || "";
  projectForm.manager = project.manager?.name || "";
  projectForm.status = project.status;
  projectForm.is_starred = project.is_starred;

  currentProj.value = project;
  isEditProjectOpen.value = true;
}

function closeEditProjectDialog() {
  isEditProjectOpen.value = false;
  projectForm.reset();
  currentProj.value = null;
}

function submitEditProjectForm() {
  if (!currentProj.value) return;

  projectForm.put(`/admin/projects/update/${currentProj.value.id}`, {
    onSuccess: (response) => {
      isEditProjectOpen.value = false;

      const updatedProject = response.props?.project || {
        id: currentProj.value.id,
        title: projectForm.title,
        description: projectForm.description,
        status: projectForm.status,
        is_starred: projectForm.is_starred,
        client: projectForm.client ? { name: projectForm.client } : null,
        manager: projectForm.manager ? { name: projectForm.manager } : null,
        tasks: currentProj.value.tasks || [],
      };

      const updateProjectInList = (projects) => {
        const index = projects.findIndex((p) => p.id === updatedProject.id);
        if (index !== -1) {
          projects[index] = updatedProject;
        }
      };

      if (member.managed_projects) {
        updateProjectInList(member.managed_projects);
      }

      if (member.client_projects) {
        updateProjectInList(member.client_projects);
      }

      projectForm.reset();
      currentProj.value = null;
    },
  });
}

const isProjectDetailsOpen = ref(false);
const selectedProject = ref(null);

function openProjectDetailsDialog(project) {
  selectedProject.value = project;
  isProjectDetailsOpen.value = true;
}

function closeProjectDetailsDialog() {
  isProjectDetailsOpen.value = false;
  selectedProject.value = null;
}

const projectStats = computed(() => {
  if (!selectedProject.value) return null;

  const tasks = selectedProject.value.tasks || [];
  const totalTasks = tasks.length;
  const pendingTasks = tasks.filter((t) => t.status === "pending").length;
  const runningTasks = tasks.filter(
    (t) => t.status === "in_progress"
  ).length;
  const completedTasks = tasks.filter((t) => t.status === "completed").length;

  return {
    totalTasks,
    pendingTasks,
    runningTasks,
    completedTasks,
  };
});

const isNotesDialogOpen = ref(false);
const selected = ref();
const note = ref("");
const isTaskViewDialogOpen = ref(false);
const mode = ref("");

function openNotesProjectDialog(project) {
  note.value = "";
  isNotesDialogOpen.value = true;
  selected.value = project;
  mode.value = "proj";
}

function openTaskViewDialog(task) {
  note.value = "";
  isTaskViewDialogOpen.value = true;
  selected.value = task;
  mode.value = "task";
}

const addNote = () => {
  useNotes(note.value, mode.value, selected.value, selected.value.notes);
  note.value = ''
}

const allProjects = computed(() => {
  const projects = [];
  
  // Add managed projects
  if (props.manager_of) {
    projects.push(...props.manager_of);
  }
  
  // Add projects from assigned tasks
  if (member.assigned_tasks) {
    member.assigned_tasks.forEach(task => {
      if (task.project && !projects.some(p => p.id === task.project.id)) {
        projects.push(task.project);
      }
    });
  }
  
  // Add client projects if the user is a client
  if (member.role === 'client' && member.client_projects) {
    member.client_projects.forEach(project => {
      if (!projects.some(p => p.id === project.id)) {
        projects.push(project);
      }
    });
  }
  
  return projects;
});

</script>

<template>
  <Head :title="title" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col px-2 pb-2 h-full">
      <!-- Toolbar -->
      <div class="border-b-2 flex flex-row justify-between items-center py-1">
        <div class="w-full sm:w-sm flex flex-row">
          <Input
            v-model="searchQuery"
            class="transition-all duration-300 ease-in-out rounded-r-none"
            placeholder="Search tasks..."
          />
          <Button class="rounded-l-none outline-1">
            <Search />
          </Button>
        </div>
        <div class="flex items-center sm:space-x-2 w-full sm:w-auto justify-end">
          <Button
            variant="outline"
            class="rounded-r-none sm:rounded-lg"
            @click="openNewProjectDialog"
          >
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

      <!-- Status-Based Columns -->
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
                        @click="openTaskViewDialog(task)"
                      >
                        <p>{{ toTitleCase(task.title) }}</p>
                        <span class="text-xs text-gray-400">
                          ({{ task.project?.title || "Assigned" }})
                        </span>
                      </div>
                      <Edit
                        class="w-4 h-4 hover:bg-primary hover:cursor-pointer rounded-sm"
                        @click="openEditDialog(task)"
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

    <!-- Details Dialog -->
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
          <div>
            <p class="font-extrabold text-lg text-gray-300 mb-2 flex items-center gap-2">
              <List class="w-5 h-5 text-primary" />
              {{ member.role === "client" ? "Project Tasks" : "Assigned Tasks" }}
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
          <div>
            <p class="font-extrabold text-lg text-gray-300 mb-2 flex items-center gap-2">
              <PackagePlus class="w-5 h-5 text-primary" />
              {{ member.role === "client" ? "Client Projects" : "Assigned Projects" }}
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
          <div v-if="member.role === 'client'">
            <p class="font-extrabold text-lg text-gray-300 mb-2 flex items-center gap-2">
              <PackagePlus class="w-5 h-5 text-primary" /> Client Information
            </p>
            <div
              class="p-4 rounded-xl bg-primary/10 hover:bg-primary/20 transition cursor-pointer"
            >
              <p class="text-sm text-gray-400">Managed By</p>
              <p class="text-md font-bold">
                {{ member.client_projects?.[0]?.manager?.name || "Not assigned" }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Edit Task Dialog -->
    <Dialog v-model="isEditTaskOpen">
      <template #header>
        <h2 class="text-xl font-bold text-gray-100">Update Task</h2>
        <p class="text-sm text-gray-400 mt-1">Modify the task details below</p>
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
              <div v-if="props.manager_of">
                <label class="block text-sm font-medium mb-1">New Project</label>
                <Dropdown
                  v-model="form.project_id"
                  :options="
                    allProjects.map((project) => ({
                      label: project.title,
                      value: project.id,
                    }))
                  "
                />
                <InputError :message="form.errors.project_id" />
              </div>
            </div>
            <div class="w-full grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <Dropdown v-model="form.role_title" :options="roleOptions" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <Dropdown v-model="form.status" :options="statusOptions" />
              </div>
            </div>
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

    <!-- New Task Dialog -->
    <Dialog v-model="isNewTaskDialogOpen">
      <template #header>
        <h2 class="text-xl font-bold text-gray-100">Create New Task</h2>
        <p class="text-sm text-gray-400 mt-1">Add a new task for this member</p>
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
            <div v-if="props.manager_of">
              <label class="block text-sm font-medium mb-1">Project</label>
              <Dropdown
                v-model="form.project_id"
                :options="
                  allProjects.map((project) => ({
                    label: project.title,
                    value: project.id,
                  }))
                "
              />
              <InputError :message="form.errors.project_id" />
            </div>
            <div class="w-full grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <Dropdown v-model="form.role_title" :options="roleOptions" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <Dropdown v-model="form.status" :options="statusOptions" />
              </div>
            </div>
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

    <!-- New Project Dialog -->
    <Dialog v-model="isNewProjectOpen">
      <template #header>
        <h2 class="text-xl font-bold text-gray-100">Create New Project</h2>
        <p class="text-sm text-gray-400 mt-1">Add a new project</p>
      </template>
      <template #body>
        <form @submit.prevent="submitProjectForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input
              v-model="projectForm.title"
              placeholder="Project Title"
              class="w-full"
            />
            <InputError :message="projectForm.errors.title" />
          </div>
          <div v-if="member.role !== 'client'">
            <label class="block text-sm font-medium mb-1">Client</label>
            <Dropdown
              v-model="projectForm.client"
              :options="clientOptions"
              placeholder="Select a client"
              class="w-full"
            />
            <InputError :message="projectForm.errors.client" />
          </div>
          <div v-if="member.role !== 'user'">
            <label class="block text-sm font-medium mb-1">Manager</label>
            <Dropdown
              v-model="projectForm.manager"
              :options="managerOptions"
              placeholder="Select a manager"
              class="w-full"
            />
            <InputError :message="projectForm.errors.manager" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="projectForm.description"
              placeholder="Describe the project..."
              class="border rounded-lg p-3 text-sm w-full focus:ring-2 focus:ring-primary focus:outline-none transition"
              rows="3"
            ></textarea>
            <InputError :message="projectForm.errors.description" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <Dropdown
              v-model="projectForm.status"
              :options="projectStatusOptions"
              class="w-full"
            />
            <InputError :message="projectForm.errors.status" />
          </div>
          <div class="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_starred"
              v-model="projectForm.is_starred"
              class="rounded border-gray-300"
            />
            <label for="is_starred" class="text-sm font-medium">Starred</label>
          </div>
          <InputError :message="projectForm.errors.is_starred" />
        </form>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="closeNewProjectDialog"> Cancel </Button>
          <Button @click="submitProjectForm" :disabled="projectForm.processing">
            Create Project
          </Button>
        </div>
      </template>
    </Dialog>

    <!-- Edit Project Dialog -->
    <Dialog v-model="isEditProjectOpen">
      <template #header>
        <h2 class="text-xl font-bold text-gray-100">Edit Project</h2>
        <p class="text-sm text-gray-400 mt-1">Modify the project details below</p>
      </template>
      <template #body>
        <form @submit.prevent="submitEditProjectForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <Input
              v-model="projectForm.title"
              placeholder="Project Title"
              class="w-full"
            />
            <InputError :message="projectForm.errors.title" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Client</label>
            <Dropdown
              v-model="projectForm.client"
              :options="clientOptions"
              placeholder="Select a client"
              class="w-full"
            />
            <InputError :message="projectForm.errors.client" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Manager</label>
            <Dropdown
              v-model="projectForm.manager"
              :options="managerOptions"
              placeholder="Select a manager"
              class="w-full"
            />
            <InputError :message="projectForm.errors.manager" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="projectForm.description"
              placeholder="Describe the project..."
              class="border rounded-lg p-3 text-sm w-full focus:ring-2 focus:ring-primary focus:outline-none transition"
              rows="3"
            ></textarea>
            <InputError :message="projectForm.errors.description" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <Dropdown
              v-model="projectForm.status"
              :options="projectStatusOptions"
              class="w-full"
            />
            <InputError :message="projectForm.errors.status" />
          </div>
          <div class="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_starred"
              v-model="projectForm.is_starred"
              class="rounded border-gray-300"
            />
            <label for="is_starred" class="text-sm font-medium">Starred</label>
          </div>
          <InputError :message="projectForm.errors.is_starred" />
        </form>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="closeEditProjectDialog"> Cancel </Button>
          <Button @click="submitEditProjectForm" :disabled="projectForm.processing">
            Update Project
          </Button>
        </div>
      </template>
    </Dialog>

    <!-- Project Details Dialog -->
    <Dialog v-model="isProjectDetailsOpen" v-if="selectedProject">
      <template #header>
        <div class="flex justify-between items-center w-full">
          <h2 class="text-lg font-semibold">
            Project: {{ toTitleCase(selectedProject.title) }}
          </h2>
          <div v-if="selectedProject.is_starred">
            <span
              class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"
            >
              ⭐
            </span>
          </div>
        </div>
      </template>
      <template #body>
        <div v-if="selectedProject" class="flex flex-col gap-4 text-sm p-1">
          <div class="grid grid-cols-3">
            <div>
              <p class="font-medium text-gray-300">Client</p>
              <p class="text-base">{{ selectedProject.client?.name || "—" }}</p>
            </div>
            <div>
              <p class="font-medium text-gray-300">Manager</p>
              <p class="text-base">{{ selectedProject.manager?.name || "—" }}</p>
            </div>
            <div>
              <p class="font-medium text-gray-300">Status</p>
              <span
                class="px-2 py-1 rounded-full text-xs font-semibold inline-block mt-1"
                :class="{
                  'bg-yellow-100 text-yellow-800': selectedProject.status === 'pending',
                  'bg-blue-100 text-blue-800': selectedProject.status === 'in_progress',
                  'bg-purple-100 text-purple-800': selectedProject.status === 'testing',
                  'bg-indigo-100 text-indigo-800': selectedProject.status === 'review',
                  'bg-green-100 text-green-800': selectedProject.status === 'completed',
                  'bg-red-100 text-red-800': selectedProject.status === 'cancelled',
                }"
              >
                {{ toTitleCase(selectedProject.status.replace("_", " ")) }}
              </span>
            </div>
          </div>
          <div>
            <p class="font-medium text-gray-300 mb-2">Task Statistics</p>
            <div class="grid grid-cols-4 gap-2">
              <div class="bg-primary/10 p-3 rounded-lg text-center">
                <p class="text-2xl font-bold">{{ projectStats.totalTasks }}</p>
                <p class="text-xs text-gray-400">Total</p>
              </div>
              <div class="bg-yellow-500/10 p-3 rounded-lg text-center">
                <p class="text-2xl font-bold text-yellow-400">
                  {{ projectStats.pendingTasks }}
                </p>
                <p class="text-xs text-gray-400">Pending</p>
              </div>
              <div class="bg-blue-500/10 p-3 rounded-lg text-center">
                <p class="text-2xl font-bold text-blue-400">
                  {{ projectStats.runningTasks }}
                </p>
                <p class="text-xs text-gray-400">Running</p>
              </div>
              <div class="bg-green-500/10 p-3 rounded-lg text-center">
                <p class="text-2xl font-bold text-green-400">
                  {{ projectStats.completedTasks }}
                </p>
                <p class="text-xs text-gray-400">Completed</p>
              </div>
            </div>
          </div>
          <div v-if="selectedProject.description">
            <p class="font-medium text-gray-300 mb-1">Description</p>
            <Viewer :source="selectedProject.description" />
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="closeProjectDetailsDialog"> Close </Button>
          <Button @click="openEditProjectDialog(selectedProject)"> Edit Project </Button>
        </div>
      </template>
    </Dialog>

    <!-- Notes Dialog -->
    <Dialog v-model="isNotesDialogOpen">
      <template #header>
        <div class="text-lg font-semibold">
          <div v-if="mode === 'proj'">
            Notes
            <span class="text-sm font-extralight"
              >({{ toTitleCase(selected.title) }})</span
            >
          </div>
          <div v-else>
            Notes
            <span class="text-sm font-extralight">
              ({{ toTitleCase(selected.title) }} of
              {{ toTitleCase(selected.project.title) }})
            </span>
          </div>
        </div>
      </template>
      <template #body>
        <div
          v-if="selected.notes?.length > 0"
          class="flex flex-col gap-2 h-full overflow-y-auto pr-1"
        >
          <div
            v-for="note in selected.notes"
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
            @click="addNote"
          >
            <Plus class="w-3 h-3" /> Add
          </Button>
        </div>
      </template>
    </Dialog>

    <!-- Task View Dialog -->
    <Dialog v-model="isTaskViewDialogOpen">
      <template #header>
        <div class="text-lg font-semibold">Task: {{ toTitleCase(selected.title) }}</div>
      </template>
      <template #body>
        <div class="flex flex-row gap-3 justify-between text-gray-200">
          <div class="w-fit h-fit flex flex-col">
            <span class="opacity-80 text-sm">For</span>
            <span class="font-semibold text-gray-100">
              {{ toTitleCase(selected.assignee?.name) || "Unassigned" }}
            </span>
          </div>
          <div class="w-fit h-fit flex flex-col">
            <span class="opacity-80 text-sm">Role</span>
            <span class="font-semibold text-gray-100 text-md">
              {{ toTitleCase(selected.role_title) || "Unassigned" }}
            </span>
          </div>
          <div class="w-fit h-fit flex flex-col">
            <span class="opacity-80 text-sm">Deadline</span>
            <span class="font-semibold text-gray-100">
              {{
                selected.deadline
                  ? new Date(selected.deadline).toLocaleDateString()
                  : "Unassigned"
              }}
            </span>
          </div>
          <div>
            <span class="opacity-80 text-sm">Status</span>
            <div
              class="w-fit h-fit font-medium flex flex-row items-center gap-1"
              :class="{
                'text-red-500': selected.status === 'cancelled',
                'text-green-400': selected.status === 'completed',
                'text-blue-600': selected.status === 'in_progress',
                'text-yellow-400': selected.status === 'pending',
                'text-purple-600':
                  selected.status === 'testing' || selected.status === 'review',
              }"
            >
              <LucideCircle
                class="w-3 h-3 font-extrabold"
                :class="{
                  'fill-red-200': selected.status === 'cancelled',
                  'fill-green-300': selected.status === 'completed',
                  'fill-blue-300': selected.status === 'in_progress',
                  'fill-yellow-200': selected.status === 'pending',
                  'fill-purple-300':
                    selected.status === 'testing' || selected.status === 'review',
                }"
              />
              {{ toTitleCase(selected.status.replace("_", " ")) }}
            </div>
          </div>
          <div>
            <span class="opacity-80 text-sm">Priority</span>
            <div
              class="font-medium flex flex-row gap-1 items-center"
              :class="{
                'text-green-500': selected.priority === 'low',
                'text-yellow-400': selected.priority === 'medium',
                'text-red-400': selected.priority === 'high',
              }"
            >
              <Flag
                class="w-3 h-3"
                :class="{
                  'fill-green-300': selected.priority === 'low',
                  'fill-yellow-200': selected.priority === 'medium',
                  'fill-red-200': selected.priority === 'high',
                }"
              />{{ toTitleCase(selected.priority) }}
            </div>
          </div>
        </div>
        <div>
          <span class="opacity-80 text-sm">Description</span>
          <Viewer :source="selected.description" />
        </div>
        <div>
          <div
            v-if="selected.notes?.length > 0"
            class="flex flex-col gap-2 max-h-[500px] overflow-y-auto pr-1"
          >
            <span class="opacity-80 text-sm">Notes</span>
            <div
              v-for="note in selected.notes"
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
          <div class="flex gap-2 mt-4 items-end w-full h-full">
            <textarea
              v-model="note"
              placeholder="Your note here..."
              class="flex-1 text-sm h-full max-h-80 p-1.5 rounded-md bg-white/10 text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-ring min-h-[33px]"
            ></textarea>
            <Button
              size="xs"
              class="px-2 py-2 flex items-center gap-1 rounded-md shadow-sm transition text-white text-xs h-8"
              @click="addNote"
            >
              <Plus class="w-3 h-3" /> Add
            </Button>
          </div>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

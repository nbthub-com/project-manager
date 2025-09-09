<script setup lang="js">
import { Head } from "@inertiajs/vue3";
import { defineProps, ref, reactive } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Button from "@/components/ui/button/Button.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import { Search } from "lucide-vue-next";
import axios from "axios";
import { useInitials } from '@/composables/useInitials';

const { getInitials } = useInitials();

const props = defineProps(["users"]);
const s_query = ref("");
const filteredUsers = ref([...props.users]);

const breadcrumbs = [{ title: "Members", href: "/admin/members" }];

// Add dialog state for view
const isDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const selectedUser = ref(null);

const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  errors: {}, // store validation errors
});

async function submitForm() {
  try {
    await axios.post("/admin/members/add-member", {
      name: form.name,
      email: form.email,
      role: "user",
      password: form.password,
      password_confirmation: form.password_confirmation,
    });
    window.location.href = "/admin/members";
  } catch (error) {
    if (error.response?.status === 422) {
      form.errors = error.response.data.errors;
    } else {
      console.error(error);
    }
  }
}

async function search() {
  if (!s_query.value) {
    filteredUsers.value = [...props.users];
    return;
  }

  try {
    const res = await axios.get(`/admin/search/${s_query.value}`);
    filteredUsers.value = res.data;
  } catch (err) {
    console.error(err);
  }
}

// open view dialog
function openView(user) {
  selectedUser.value = user;
  isViewDialogOpen.value = true;
}
</script>

<template>
  <Head title="Members" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col p-2 gap-6">
      <!-- Search + Add -->
      <div
        class="border-b-2 flex flex-row justify-between p-1 items-center gap-2 rounded-lg"
      >
        <div class="flex flex-row rounded-lg">
          <Input
            v-model="s_query"
            @input="search"
            class="transition-all duration-300 ease-in-out rounded-r-none"
            placeholder="Search members..."
          />
          <Button class="rounded-l-none" @click="search">
            <Search />
          </Button>
        </div>
        <Button @click="isDialogOpen = true">+ Add</Button>
      </div>

      <!-- Members Grid -->
      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <div
          v-for="user in filteredUsers"
          :key="user.id"
          class="p-5 rounded-xl shadow-lg text-white bg-gradient-to-br from-primary to-secondary transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
        >
          <!-- Header -->
          <div
            class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
          >
            <h3 class="text-lg font-bold truncate">{{ user.name }}</h3>
            <button
              class="text-red-200 hover:text-red-400 cursor-pointer text-sm"
              @click="console.log('delete user', user.id)"
              title="Delete user"
            >
              ✕
            </button>
          </div>

          <!-- Info (basic only) -->
          <div class="flex flex-col gap-1">
            <p class="text-sm opacity-90 truncate">
              📧 <span class="font-medium">{{ user.email }}</span>
            </p>
            <p class="text-xs opacity-75">🆔 {{ user.id }}</p>
          </div>

          <!-- Footer -->
          <div class="mt-4 flex justify-end gap-1 border-t border-white/20 pt-2">
            <button
              class="px-2 py-1 text-xs rounded-md bg-white/20 hover:bg-white/30 transition"
              @click="openView(user)"
            >
              View
            </button>
          </div>
        </div>
      </div>

      <!-- Add Member Dialog -->
      <Dialog v-model="isDialogOpen">
        <template #header>
          <h2 class="text-lg font-semibold">Add New Member</h2>
        </template>
        <template #body>
          <form @submit.prevent="submitForm" class="flex flex-col gap-2">
            <Input v-model="form.name" placeholder="Name" />
            <InputError :message="form.errors.name" class="text-red-500" />

            <Input v-model="form.email" placeholder="Email" />
            <InputError :message="form.errors.email" class="text-red-500" />

            <Input type="password" v-model="form.password" placeholder="Password" />
            <InputError :message="form.errors.password" class="text-red-500" />

            <Input
              type="password"
              v-model="form.password_confirmation"
              placeholder="Confirm Password"
            />
            <InputError
              :message="form.errors.password_confirmation"
              class="text-red-500"
            />
          </form>
        </template>
        <template #footer>
          <Button @click="isDialogOpen = false"> Cancel </Button>
          <Button @click="submitForm"> Save </Button>
        </template>
      </Dialog>

      <!-- View Member Dialog -->
      <Dialog v-model="isViewDialogOpen">
        <!-- Header -->
        <template #header>
          <div class="flex items-center gap-3">
            <div
              class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg"
            >
              {{ getInitials(selectedUser.name) }}
            </div>
            <div>
              <h2 class="text-lg font-semibold">{{ selectedUser?.name }}</h2>
              <p class="text-sm text-gray-500">{{ selectedUser?.email }}</p>
            </div>
          </div>
        </template>

        <!-- Body -->
        <template #body>
          <div v-if="selectedUser" class="flex flex-col gap-6 text-sm">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-4">
              <div
                class="p-4 rounded-xl bg-purple-50 border border-purple-200 flex flex-col items-center"
              >
                <span class="text-2xl font-bold text-purple-600">
                  {{ selectedUser.tasks_assigned }}
                </span>
                <span class="text-xs text-gray-600">Tasks Assigned</span>
              </div>
              <div
                class="p-4 rounded-xl bg-teal-50 border border-teal-200 flex flex-col items-center"
              >
                <span class="text-2xl font-bold text-teal-600">
                  {{ selectedUser.tasks_done }}
                </span>
                <span class="text-xs text-gray-600">Tasks Done</span>
              </div>
              <div
                class="p-4 rounded-xl bg-blue-50 border border-blue-200 flex flex-col items-center"
              >
                <span class="text-2xl font-bold text-blue-600">
                  {{ selectedUser.projects_assigned }}
                </span>
                <span class="text-xs text-gray-600">Projects Assigned</span>
              </div>
              <div
                class="p-4 rounded-xl bg-green-50 border border-green-200 flex flex-col items-center"
              >
                <span class="text-2xl font-bold text-green-600">
                  {{ selectedUser.projects_done }}
                </span>
                <span class="text-xs text-gray-600">Projects Done</span>
              </div>
            </div>

            <!-- Roles -->
            <div>
              <p class="font-medium text-gray-600 mb-2">Roles in Projects</p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="(role, idx) in selectedUser.roles"
                  :key="idx"
                  class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 border border-gray-300 text-gray-700"
                >
                  {{ role }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

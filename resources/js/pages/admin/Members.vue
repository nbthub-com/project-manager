<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, reactive, watch } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Button from "@/components/ui/button/Button.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import { Search } from "lucide-vue-next";
import { useInitials } from "@/composables/useInitials";
import Label from "@/components/ui/label/Label.vue";

const { getInitials } = useInitials();

const props = defineProps({
  users: Array,
});

const breadcrumbs = [{ title: "Members", href: "/admin/members" }];

// 🔑 local state
const s_query = ref("");
const usersList = ref([...props.users]);
const filteredUsers = ref([...props.users]);

watch(
  () => props.users,
  (newUsers) => {
    usersList.value = [...newUsers];
    search();
  },
  { deep: true }
);

watch(s_query, () => search());

function search() {
  if (!s_query.value.trim()) {
    filteredUsers.value = [...usersList.value];
    return;
  }
  const q = s_query.value.toLowerCase();
  filteredUsers.value = usersList.value.filter(
    (u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
}

// dialogs
const isDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const selectedUser = ref(null);

// form
const form = useForm({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  role: "user",
});

// create
function submitForm() {
  form.post("/admin/members/add-member", {
    onSuccess: (page) => {
      usersList.value = page.props.users;
      search();
      isDialogOpen.value = false;
      form.reset();
    },
  });
}

// delete
function deleteUser(id) {
  if (confirm("Are you sure you want to delete this user?")) {
    router.delete(`/admin/members/delete/${id}`, {
      onSuccess: (page) => {
        usersList.value = page.props.users;
        search();
      },
    });
  }
}

// view
function openView(user) {
  selectedUser.value = user;
  isViewDialogOpen.value = true;
}
</script>

<template>
  <Head title="Members" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col p-2">
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
      <div
        v-if="filteredUsers.length > 0"
        class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3"
      >
        <div
          v-for="user in filteredUsers"
          :key="user.id"
            class="p-4 rounded-xl shadow-lg text-white bg-gradient-to-br from-[#5a248a] to-secondary transition transform hover:scale-[1.02] hover:shadow-xl flex flex-col justify-between"
        >
          <!-- Header -->
          <div
            class="flex justify-between items-center border-b border-white/20 pb-2 mb-3"
          >
            <h3 class="text-lg font-bold truncate">{{ user.name }}</h3>
            <button
              class="text-red-200 hover:text-red-400 cursor-pointer text-sm"
              @click="deleteUser(user.id)"
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

      <!-- Empty State -->
      <template v-else>
        <div
            class="col-span-full flex flex-col items-center justify-center py-16 text-gray-500 dark:text-gray-400"
        >
          <p class="text-lg font-medium">No Members found</p>
          <p class="text-sm opacity-80">
            {{
              s_query
                ? "Try adjusting your search terms."
                : "Looks like there are no tasks yet."
            }}
          </p>
          <Button
            @click="
              () => {
                isDialogOpen = true;
                form.reset();
              }
            "
            class="m-3"
          >
            + Add First Member
          </Button>
        </div>
      </template>

      <!-- Add Member Dialog -->
      <Dialog v-model="isDialogOpen">
        <template #header>
          <h2 class="text-lg font-semibold">Add New Member</h2>
        </template>
        <template #body>
          <form @submit.prevent="submitForm" class="flex flex-col gap-2">
            <Label>Name</Label>
            <Input v-model="form.name" placeholder="Name" />
            <InputError :message="form.errors.name" class="text-red-500" />

            <Label>Email</Label>
            <Input v-model="form.email" placeholder="Email" />
            <InputError :message="form.errors.email" class="text-red-500" />

            <Label>Password</Label>
            <Input type="password" v-model="form.password" placeholder="Password" />
            <InputError :message="form.errors.password" class="text-red-500" />

            <Label>Confirm Password</Label>
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

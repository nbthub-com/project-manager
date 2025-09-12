<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, reactive, watch, computed } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Button from "@/components/ui/button/Button.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import Dropdown from "@/components/ui/select/Select.vue";
import { Search, Filter, X } from "lucide-vue-next";
import { useInitials } from "@/composables/useInitials";
import Label from "@/components/ui/label/Label.vue";
import Pagination from "@/components/ui/pagination/Pagination.vue";

const { getInitials } = useInitials();

const props = defineProps({
  users: Object,
  roles: Array,
  filters: Object,
});

const breadcrumbs = [{ title: "Members", href: "/admin/members" }];

// 🔑 local state
const s_query = ref(props.filters?.filter_id || "");
const filterRole = ref(props.filters?.filter_role || "");
const perPage = ref(props.filters?.per_page || 10);
const isFilterOpen = ref(false);

// Form for search and pagination
const filterForm = useForm({
  filter_id: s_query.value,
  filter_role: filterRole.value,
  per_page: perPage.value,
});

// Watch for search changes
watch(s_query, (value) => {
  filterForm.filter_id = value;
  filterForm.page = 1; // Reset to first page when searching
  applyFilters();
});

// Watch for role filter changes
watch(filterRole, (value) => {
  filterForm.filter_role = value;
  filterForm.page = 1; // Reset to first page when filtering
  applyFilters();
});

// Watch for per_page changes
watch(perPage, (value) => {
  filterForm.per_page = value;
  filterForm.page = 1; // Reset to first page
  applyFilters();
});

// Apply filters with debounce
let debounceTimeout;
function applyFilters() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    filterForm.get("/admin/members", {
      preserveState: true,
      preserveScroll: true,
    });
  }, 300);
}

// Handle page change from pagination component
function handlePageChange(page) {
  filterForm.page = page;
  filterForm.get("/admin/members", {
    preserveState: true,
    preserveScroll: true,
  });
}

// Reset all filters
function resetFilters() {
  s_query.value = "";
  filterRole.value = "";
  filterForm.filter_id = "";
  filterForm.filter_role = "";
  filterForm.page = 1;
  applyFilters();
}

// Check if any filters are active
function hasActiveFilters() {
  return s_query.value || filterRole.value;
}

// Prepare roles for dropdown
const roleOptions = computed(() => {
  return [
    { label: "All Roles", value: "" },
    ...props.roles.map(role => ({ label: role, value: role }))
  ];
});

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
    onSuccess: () => {
      isDialogOpen.value = false;
      form.reset();
    },
  });
}

// delete
function deleteUser(id) {
  if (confirm("Are you sure you want to delete this user?")) {
    router.delete(`/admin/members/delete/${id}`, {
      preserveScroll: true,
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
    <div class="flex flex-col px-1">
      <!-- Search + Filters + Add -->
      <div
        class="border-b-2 flex flex-row justify-between p-1 items-center gap-2"
      >
        <div class="w-full sm:w-sm flex flex-row">
          <Input
            v-model="s_query"
            class="transition-all duration-300 ease-in-out rounded-r-none"
            placeholder="Search by ID..."
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
              {{ [s_query, filterRole].filter(Boolean).length }}
            </span>
          </Button>
        </div>
        
        <div class="flex items-center space-x-2">
          <Dropdown
            v-model="perPage"
            class="rounded text-sm"
            :options="[
            {'value': 5, 'label': '5/pg'},
            {'value': 10, 'label': '10/pg'},
            {'value': 20, 'label': '20/pg'},
            {'value': 50, 'label': '50/pg'}
            ]"
          >
          </Dropdown>
          
          <Button @click="isDialogOpen = true">+ Add</Button>
        </div>
      </div>
      
      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2 mt-2 mb-1">
        <div 
          v-if="s_query" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          ID: {{ s_query }}
          <button @click="s_query = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <div 
          v-if="filterRole" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Role: {{ filterRole }}
          <button @click="filterRole = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <Button 
          variant="outline" 
          size="sm" 
          @click="resetFilters"
          class="text-xs"
        >
          Clear All
        </Button>
      </div>
      
      <!-- Members Grid -->
      <div
        v-if="props.users?.data?.length > 0"
        class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 p-3"
      >
        <div
          v-for="user in props.users.data"
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
              hasActiveFilters()
                ? "Try adjusting your filters."
                : "Looks like there are no members yet."
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
      
      <!-- Pagination Component -->
      <Pagination
        v-if="props.users"
        :current-page="props.users.current_page"
        :last-page="props.users.last_page"
        :from="props.users.from"
        :to="props.users.to"
        :total="props.users.total"
        @page-changed="handlePageChange"
      />
      
      <!-- Filter Dialog -->
      <Dialog v-model="isFilterOpen">
        <template #header>
          <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
            Filter Members
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Apply filters to narrow down results
          </p>
        </template>
        
        <template #body>
          <div class="space-y-4">
            <!-- ID Filter -->
            <div>
              <Label>Member ID</Label>
              <Input
                v-model="s_query"
                placeholder="Search by ID..."
                class="w-full"
              />
            </div>
            
            <!-- Role Filter -->
            <div>
              <Label>Role</Label>
              <Dropdown
                v-model="filterRole"
                :options="roleOptions"
                class="w-full"
              />
            </div>
          </div>
        </template>
        
        <template #footer>
          <div class="flex justify-between gap-3">
            <Button
              variant="outline"
              @click="resetFilters"
            >
              Reset All
            </Button>
            <div class="flex gap-2">
              <Button
                variant="outline"
                @click="isFilterOpen = false"
              >
                Cancel
              </Button>
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
              {{ getInitials(selectedUser?.name) }}
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
                <span
                  v-if="!selectedUser.roles || selectedUser.roles.length === 0"
                  class="text-sm text-gray-500 italic"
                >
                  No roles assigned
                </span>
              </div>
            </div>
          </div>
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>
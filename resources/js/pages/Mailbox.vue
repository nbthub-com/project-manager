<script setup lang="js">
import Button from "@/components/ui/button/Button.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { Circle, CircleX, Delete, Edit, Eye, FileCheck, FileWarning, Info, LucideInbox, Plus, RefreshCwIcon, Search, Filter, X } from "lucide-vue-next";
import { defineProps, ref, watch, computed } from "vue";
import Dialog from "@/components/ui/simpledialog/Dialog.vue";
import { cn } from "@/lib/utils";
import Select from "@/components/ui/select/Select.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import axios from 'axios';
import Pagination from "@/components/ui/pagination/Pagination.vue";
import Viewer from "@/components/ui/md/viewer.vue";

const props = defineProps(['inbox', 'outbox', 'currentUserId', 'names', 'types', 'scopes', 'filters']);
const inbox = ref([...props.inbox.data]);
const outbox = ref([...props.outbox.data]);
const disabled = ref(false);

// Filter state
const filterId = ref(props.filters?.filter_id || '');
const filterFrom = ref(props.filters?.filter_from || '');
const filterTo = ref(props.filters?.filter_to || '');
const filterType = ref(props.filters?.filter_type || '');
const filterRead = ref(props.filters?.filter_read !== undefined ? props.filters?.filter_read : '');
const filterScope = ref(props.filters?.filter_scope || '');
const perPage = ref(props.filters?.per_page || 10);
const isFilterOpen = ref(false);

// Get current page numbers from props
const inboxPage = ref(props.inbox?.current_page || 1);
const outboxPage = ref(props.outbox?.current_page || 1);

// Form for filters with page parameters
const filterForm = useForm({
  filter_id: filterId.value,
  filter_from: filterFrom.value,
  filter_to: filterTo.value,
  filter_type: filterType.value,
  filter_read: filterRead.value,
  filter_scope: filterScope.value,
  per_page: perPage.value,
  inbox_page: inboxPage.value,
  outbox_page: outboxPage.value,
});

// Watch for filter changes
watch([filterId, filterFrom, filterTo, filterType, filterRead, filterScope], () => {
  filterForm.filter_id = filterId.value;
  filterForm.filter_from = filterFrom.value;
  filterForm.filter_to = filterTo.value;
  filterForm.filter_type = filterType.value;
  filterForm.filter_read = filterRead.value;
  filterForm.filter_scope = filterScope.value;
  filterForm.inbox_page = 1; // Reset to first page when filtering
  filterForm.outbox_page = 1;
  applyFilters();
}, { deep: true });

watch(perPage, (value) => {
  filterForm.per_page = value;
  filterForm.inbox_page = 1; // Reset to first page
  filterForm.outbox_page = 1;
  applyFilters();
});

// Apply filters with debounce
let debounceTimeout;
function applyFilters() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    filterForm.get("/mailbox", {
      preserveState: true,
      preserveScroll: true,
    });
  }, 300);
}

// Handle page change for inbox
function handleInboxPageChange(page) {
  inboxPage.value = page;
  filterForm.inbox_page = page;
  filterForm.get("/mailbox", {
    preserveState: true,
    preserveScroll: true,
  });
}

// Handle page change for outbox
function handleOutboxPageChange(page) {
  outboxPage.value = page;
  filterForm.outbox_page = page;
  filterForm.get("/mailbox", {
    preserveState: true,
    preserveScroll: true,
  });
}

// Reset all filters
function resetFilters() {
  filterId.value = '';
  filterFrom.value = '';
  filterTo.value = '';
  filterType.value = '';
  filterRead.value = '';
  filterScope.value = '';
  filterForm.filter_id = '';
  filterForm.filter_from = '';
  filterForm.filter_to = '';
  filterForm.filter_type = '';
  filterForm.filter_read = '';
  filterForm.filter_scope = '';
  filterForm.inbox_page = 1;
  filterForm.outbox_page = 1;
  applyFilters();
}

// Check if any filters are active
function hasActiveFilters() {
  return filterId.value || 
         filterFrom.value || 
         filterTo.value || 
         filterType.value || 
         filterRead.value !== '' || 
         filterScope.value;
}

// Prepare options for dropdowns
const userOptions = computed(() => {
  return [
    { label: "All Users", value: "" },
    ...props.names.map(name => ({ label: name.toUpperCase(), value: name }))
  ];
});

const typeOptions = computed(() => {
  return [
    { label: "All Types", value: "" },
    ...props.types.map(type => ({ 
      label: type.charAt(0).toUpperCase() + type.slice(1), 
      value: type 
    }))
  ];
});

const scopeOptions = computed(() => {
  return [
    { label: "All Scopes", value: "" },
    ...props.scopes.map(scope => ({ 
      label: scope.charAt(0).toUpperCase() + scope.slice(1), 
      value: scope 
    }))
  ];
});

const readOptions = [
  { label: "All", value: "" },
  { label: "Read", value: "true" },
  { label: "Unread", value: "false" }
];

// Watchers for prop changes
watch(() => props.inbox, (newInbox) => {
  if (newInbox) {
    inbox.value = [...newInbox.data];
    inboxPage.value = newInbox.current_page;
  }
}, { deep: true });

watch(() => props.outbox, (newOutbox) => {
  if (newOutbox) {
    outbox.value = [...newOutbox.data];
    outboxPage.value = newOutbox.current_page;
  }
}, { deep: true });

const tab = ref(0);
const showDialog = ref(false);
const readDialog = ref(false);
const editDialog = ref(false);
const selectedMessage = ref(null);
const editingMessage = ref(null);
const breadcrumbs = [{ title: 'MailBox', href: '/mailbox' }];

// Get current user ID
const page = usePage();
const id = computed(() => page.props.auth.user.id);

// Format names for select options
const nameOptions = computed(() => {
  return props.names.map(name => ({ label: name.toUpperCase(), value: name }));
});

// Message type options
const messageTypeOptions = [
  { label: 'Normal', value: 'normal' },
  { label: 'Urgent', value: 'urgent' },
  { label: 'Positive', value: 'positive' },
  { label: 'Negative', value: 'negative' },
];

// Scope options
const messageScopeOptions = [
  { label: 'Local', value: 'local' },
  { label: 'Global', value: 'global' },
];

// New message form
const form = useForm({
  to_user: "",
  subject: "",
  content: "",
  type: "normal",
  scope: "local",
});

// Edit form
const editForm = useForm({
  id: "",
  to_user: "",
  subject: "",
  content: "",
  type: "normal",
  scope: "local",
});

// Send new message
function submitMessage() {
  form.post("/mailbox/send", {
    preserveScroll: true,
    onSuccess: () => {
      showDialog.value = false;
      form.reset();
    },
  });
}

// Refresh messages
function refresh() {
  disabled.value = true;
  router.reload({
    preserveScroll: true,
    onFinish: () => {
      disabled.value = false;
    }
  });
}

// Open message dialog
function openMessage(item, box) {
  selectedMessage.value = item;
  readDialog.value = true;
  // Mark as read when opened
  if (item.is_read === 0 && box === 'inbox') {
    axios.patch(`/mailbox/update/read/${item.id}`)
      .then(response => {
        if (response.status === 200) {
          item.is_read = 1;
        }
      })
      .catch(error => {
        console.error("Failed to mark message as read:", error);
      });
  }
}

// Open edit dialog
function openEditDialog(item) {
  editingMessage.value = item;
  editForm.id = item.id;
  editForm.to_user = item.to_display !== 'Global' ? item.to_display : '';
  editForm.subject = item.subject;
  editForm.content = item.content;
  editForm.type = item.type;
  editForm.scope = item.scope;
  editDialog.value = true;
}

// Delete message
function deleteMessage(id) {
  if (confirm('Are you sure you want to delete this message?')) {
    axios.delete(`/mailbox/delete/${id}`)
      .then(response => {
        if (response.status === 200) {
          outbox.value = outbox.value.filter(msg => msg.id !== id);
        }
      })
      .catch(error => {
        console.error("Failed to delete message:", error);
      });
  }
}

// Update message
function updateMessage() {
  editForm.put(`/mailbox/update/${editForm.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      editDialog.value = false;
      editForm.reset();
    }
  });
}
</script>

<template>
  <Head title="Mailbox" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-4">
      <!-- Search + Filters + Tabs + New Message Button -->
      <div class="flex flex-col sm:flex-row justify-between gap-2 mb-4 outline-1 p-1 rounded-lg items-center">
        <div class="flex flex-row w-full sm:w-auto">
          <Input
            v-model="filterId"
            placeholder="Search by ID..."
            class="transition-all duration-300 ease-in-out rounded-r-none"
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
              {{ [filterId, filterFrom, filterTo, filterType, filterRead, filterScope].filter(Boolean).length }}
            </span>
          </Button>
        </div>
        
        <div class="flex flex-row gap-1 w-full sm:w-auto">
          <div class="flex flex-row flex-1 sm:flex-none">
            <!-- Inbox -->
            <Button
              @click="tab = 0"
              :class="
                cn(
                  'font-semibold rounded-r-none border border-black transition hover:bg-gray-800',
                  tab === 0
                    ? 'bg-black text-white opacity-100 underline'
                    : 'bg-black text-white opacity-50'
                )
              "
            >
              Inbox
            </Button>
            <!-- Outbox -->
            <Button
              @click="tab = 1"
              :class="
                cn(
                  'font-semibold rounded-l-none border border-black transition hover:bg-gray-800',
                  tab === 1
                    ? 'bg-black text-white opacity-100 underline'
                    : 'bg-black text-white opacity-50'
                )
              "
            >
              Outbox
            </Button>
          </div>
          
          <div class="flex flex-row gap-1">
            <Select
              v-model="perPage"
              class="rounded text-sm"
              :options="[
              {'value': 5, 'label': '5/pg'},
              {'value': 10, 'label': '10/pg'},
              {'value': 20, 'label': '20/pg'},
              {'value': 50, 'label': '50/pg'}
              ]"
            >
            </Select>
            
            <Button
              v-if="tab === 1"
              @click="showDialog = true"
              class="flex items-center gap-1"
            >
              <Plus /> New
            </Button>
            <Button @click="refresh()" :disabled="disabled" class="flex items-center gap-1">
              <RefreshCwIcon /> Refresh
            </Button>
          </div>
        </div>
      </div>
      
      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2 mt-2 mb-1">
        <div 
          v-if="filterId" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          ID: {{ filterId }}
          <button @click="filterId = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <div 
          v-if="filterFrom" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          From: {{ filterFrom }}
          <button @click="filterFrom = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <div 
          v-if="filterTo" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          To: {{ filterTo }}
          <button @click="filterTo = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <div 
          v-if="filterType" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Type: {{ filterType }}
          <button @click="filterType = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <div 
          v-if="filterRead !== ''" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Status: {{ filterRead === 'true' ? 'Read' : 'Unread' }}
          <button @click="filterRead = ''; applyFilters();" class="ml-1">
            <X class="h-3 w-3" />
          </button>
        </div>
        <div 
          v-if="filterScope" 
          class="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
        >
          Scope: {{ filterScope }}
          <button @click="filterScope = ''; applyFilters();" class="ml-1">
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
      
      <div
        v-if="tab === 0 && inbox.length === 0"
        class="flex flex-col items-center justify-center p-6 text-gray-500 dark:text-gray-400"
      >
        <LucideInbox class="w-10 h-10 mb-2" />
        <p>Your inbox is empty!</p>
      </div>
      <div
        v-else-if="tab === 1 && outbox.length === 0"
        class="flex flex-col items-center justify-center p-6 text-gray-500 dark:text-gray-400"
      >
        <LucideInbox class="w-10 h-10 mb-2" />
        <p>You have not sent any messages!</p>
      </div>
      <div v-else class="space-y-1">
        <div
          v-for="item in tab === 0 ? inbox : outbox"
          :key="item.id"
          class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-center border rounded-lg p-2 transition-all duration-200 cursor-pointer hover:shadow-md hover:bg-gray-50 dark:hover:bg-gray-700 w-full"
          :class="
            cn(
              { 'bg-gray-100 dark:bg-gray-800': item.is_read === 0 },
              { 'border-2 border-blue-800': item.scope === 'global' }
            )
          "
        >
          <!-- Message Info with Type Icon -->
          <div
            class="w-fit flex flex-wrap gap-2 sm:gap-4 items-center flex-1 text-sm text-gray-700 dark:text-gray-200 p-1 rounded"
          >
            <component
              :is="
                item.type === 'positive'
                  ? FileCheck
                  : item.type === 'negative'
                  ? CircleX
                  : item.type === 'urgent'
                  ? FileWarning
                  : Info
              "
              :class="{
                'stroke-green-600': item.type === 'positive',
                'stroke-red-600': item.type === 'negative',
                'stroke-yellow-600': item.type === 'urgent',
                'stroke-gray-600': item.type === 'normal',
              }"
              class="w-4 h-4"
              title="Message Type"
            />
            <span title="From/To">
              <strong v-if="tab === 0">From:</strong>
              <strong v-else>To:</strong>
              {{ tab === 0 ? item.from_display : item.to_display }}
            </span>
            <span class="hidden sm:inline">|</span>
            <span class="font-medium truncate max-w-xs sm:max-w-md">{{
              item.subject
            }}</span>
            <span class="hidden sm:inline">|</span>
            <span title="Scope">{{ item.scope }}</span>
            <span class="hidden sm:inline">|</span>
            <span title="Created At">{{
              new Date(item.created_at).toLocaleString()
            }}</span>
          </div>
          <!-- Action Buttons -->
          <div class="mt-2 sm:mt-0 flex flex-row gap-2 items-center">
            <Circle
              size="18"
              :class="cn('', ['fill-green-500', 'fill-transparent'][1 - item.is_read])"
            />
            <Button
              size="sm"
              variant="ghost"
              @click="openMessage(item, ['inbox', 'outbox'][tab])"
            ><Eye
            /></Button>
            <Button
              size="sm"
              variant="ghost"
              @click="openEditDialog(item)"
              v-if="tab === 1"
            ><Edit
            /></Button>
            <Button
              size="sm"
              variant="ghost"
              v-if="tab === 1"
              @click="deleteMessage(item.id)"
            ><Delete
            /></Button>
          </div>
        </div>
      </div>
      
      <!-- Pagination -->
      <Pagination
        v-if="tab === 0 && props.inbox"
        :current-page="props.inbox.current_page"
        :last-page="props.inbox.last_page"
        :from="props.inbox.from"
        :to="props.inbox.to"
        :total="props.inbox.total"
        @page-changed="handleInboxPageChange"
      />
      
      <Pagination
        v-if="tab === 1 && props.outbox"
        :current-page="props.outbox.current_page"
        :last-page="props.outbox.last_page"
        :from="props.outbox.from"
        :to="props.outbox.to"
        :total="props.outbox.total"
        @page-changed="handleOutboxPageChange"
      />
    </div>
    
    <!-- Filter Dialog -->
    <Dialog v-model="isFilterOpen">
      <template #header>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
          Filter Messages
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Apply filters to narrow down results
        </p>
      </template>
      
      <template #body>
        <div class="space-y-4">
          <!-- ID Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Message ID</label>
            <Input
              v-model="filterId"
              placeholder="Search by ID..."
              class="w-full"
            />
          </div>
          
          <!-- From Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">From</label>
            <Select
              v-model="filterFrom"
              :options="userOptions"
              class="w-full"
            />
          </div>
          
          <!-- To Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">To</label>
            <Select
              v-model="filterTo"
              :options="userOptions"
              class="w-full"
            />
          </div>
          
          <!-- Type Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <Select
              v-model="filterType"
              :options="typeOptions"
              class="w-full"
            />
          </div>
          
          <!-- Read Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <Select
              v-model="filterRead"
              :options="readOptions"
              class="w-full"
            />
          </div>
          
          <!-- Scope Filter -->
          <div>
            <label class="block text-sm font-medium mb-1">Scope</label>
            <Select
              v-model="filterScope"
              :options="scopeOptions"
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
    
    <!-- New Message Dialog -->
    <Dialog v-model="showDialog">
      <template #header>
        <h2 class="text-lg font-bold">New Message</h2>
      </template>
      <template #body>
        <div class="space-y-2">
          <label class="block">
            <span>Recipient</span>
            <Select
              v-model="form.to_user"
              :options="nameOptions"
              :disabled="form.scope !== 'local'"
              class="border rounded w-full p-1"
            />
            <InputError :message="form.errors.to_user" />
          </label>
          <label class="block">
            <span>Subject</span>
            <Input v-model="form.subject" type="text" class="border rounded w-full p-1" />
            <InputError :message="form.errors.subject" />
          </label>
          <label class="block">
            <span>Content</span>
            <textarea v-model="form.content" class="border rounded w-full p-1"></textarea>
            <InputError :message="form.errors.content" />
          </label>
          <label class="block">
            <span>Type</span>
            <Select 
              v-model="form.type" 
              :options="messageTypeOptions"
              class="border rounded w-full p-1" 
            />
            <InputError :message="form.errors.type" />
          </label>
          <label class="block">
            <span>Scope</span>
            <Select 
              v-model="form.scope" 
              :options="messageScopeOptions"
              class="border rounded w-full p-1" 
            />
            <InputError :message="form.errors.scope" />
          </label>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button @click="showDialog = false">Cancel</Button>
          <Button @click="submitMessage" :disabled="form.processing">Send</Button>
        </div>
      </template>
    </Dialog>
    
    <Dialog v-model="readDialog">
      <template #header>
        <h2 class="text-lg font-bold flex items-center gap-2">
          <strong>Subject:</strong> {{ selectedMessage.subject }}
        </h2>
      </template>
      <template #body>
        <div v-if="selectedMessage" class="space-y-2">
          <p><strong>From:</strong> {{ selectedMessage.from_display }}</p>
          <p><strong>To:</strong> {{ selectedMessage.to_display }}</p>
          <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex items-center gap-1">
              <component
                :is="
                  selectedMessage.type === 'positive'
                    ? FileCheck
                    : selectedMessage.type === 'negative'
                    ? CircleX
                    : selectedMessage.type === 'urgent'
                    ? FileWarning
                    : Info
                "
                :class="{
                  'stroke-green-600': selectedMessage.type === 'positive',
                  'stroke-red-600': selectedMessage.type === 'negative',
                  'stroke-yellow-600': selectedMessage.type === 'urgent',
                  'stroke-gray-600': selectedMessage.type === 'normal',
                }"
                class="w-4 h-4"
              />
              <span><strong>Type:</strong> {{ selectedMessage.type }}</span>
            </div>
            <span><strong>Scope:</strong> {{ selectedMessage.scope }}</span>
            <span
              ><strong>Sent:</strong>
              {{ new Date(selectedMessage.created_at).toLocaleString() }}</span
            >
          </div>
          <hr class="my-2" />
          <p class="text-gray-700 dark:text-gray-200"><Viewer :source=" selectedMessage.content"/> </p>
        </div>
      </template>
    </Dialog>
    
    <Dialog v-model="editDialog">
      <template #header>
        <h2 class="text-lg font-bold">Edit Message</h2>
      </template>
      <template #body>
        <div class="space-y-2">
          <label class="block">
            <span>Recipient</span>
            <Select
              v-model="editForm.to_user"
              :options="nameOptions"
              :disabled="editForm.scope === 'global'"
              class="border rounded w-full p-1"
            />
            <InputError :message="editForm.errors.to_user" />
          </label>
          <label class="block">
            <span>Subject</span>
            <Input
              v-model="editForm.subject"
              type="text"
              class="border rounded w-full p-1"
            />
            <InputError :message="editForm.errors.subject" />
          </label>
          <label class="block">
            <span>Content</span>
            <textarea
              v-model="editForm.content"
              class="border rounded w-full p-1"
            ></textarea>
            <InputError :message="editForm.errors.content" />
          </label>
          <label class="block">
            <span>Type</span>
            <Select 
              v-model="editForm.type" 
              :options="messageTypeOptions"
              class="border rounded w-full p-1" 
            />
            <InputError :message="editForm.errors.type" />
          </label>
          <label class="block">
            <span>Scope</span>
            <Select 
              v-model="editForm.scope" 
              :options="messageScopeOptions"
              class="border rounded w-full p-1" 
            />
            <InputError :message="editForm.errors.scope" />
          </label>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button @click="editDialog = false">Cancel</Button>
          <Button @click="updateMessage" :disabled="editForm.processing">Update</Button>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>
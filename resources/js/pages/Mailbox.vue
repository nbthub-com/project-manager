<script setup lang="js">
import Button from "@/components/ui/button/Button.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { Circle, CircleX, Delete, Edit, Eye, FileCheck, FileWarning, Info, LucideInbox, Plus, RefreshCwIcon } from "lucide-vue-next";
import { defineProps, ref, watch, computed } from "vue";
// Fix: Check the correct path for Dialog component
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import { cn } from "@/lib/utils";
import Select from "@/components/ui/select/select.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import axios from 'axios';

const props = defineProps(['inbox', 'outbox', 'currentUserId', 'names']);
const inbox = ref([...props.inbox]);
const outbox = ref([...props.outbox]);
const disabled = ref(false);

// Watchers for prop changes
watch(() => props.inbox, (newInbox) => {
  inbox.value = [...newInbox];
}, { deep: true });
watch(() => props.outbox, (newOutbox) => {
  outbox.value = [...newOutbox];
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

// New message form
const form = useForm({
  to_user: "",
  subject: "",
  content: "",
  type: "normal",
  scope: "local",
});

// Edit form with is_starred field
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
    onSuccess: (page) => {
      showDialog.value = false;
      form.reset();
      if (page.props.inbox) inbox.value = [...page.props.inbox];
      if (page.props.outbox) outbox.value = [...page.props.outbox];
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
    },
    onSuccess: (page) => {
      if (page.props.inbox) inbox.value = [...page.props.inbox];
      if (page.props.outbox) outbox.value = [...page.props.outbox];
    }
  });
}

// Open message dialog
function openMessage(item, box) {
  selectedMessage.value = item;
  readDialog.value = true;
  
  // Mark as read when opened
  if (item.is_read === 0 && box === 'inbox') { // Check for integer 0
    axios.patch(`/mailbox/update/read/${item.id}`)
      .then(response => {
        if (response.status === 200) {
          item.is_read = 1; // Set to integer 1
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
  // Convert boolean to integer before sending
  const formData = {
    ...editForm.data(),
  };
  editForm.put(`/mailbox/update/${editForm.id}`, {
    data: formData,
    preserveScroll: true,
    onSuccess: (page) => {
      editDialog.value = false;
      editForm.reset();
      if (page.props.outbox) outbox.value = [...page.props.outbox];
    }
  });
}
</script>

<template>
  <!-- Template remains the same as before, just ensure you removed type="text" from Select components -->
  <Head title="Mailbox" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-4">
      <!-- Tabs and New Message Button -->
      <div class="flex justify-between mb-4 outline-1 p-1 rounded-lg items-center">
        <div class="flex flex-row">
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
          :class="{ 'bg-gray-100 dark:bg-gray-800': item.is_read === 0 }"
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
    </div>
    <!-- New Message Dialog -->
    <Dialog v-model="showDialog">
      <template #header>
        <h2 class="text-lg font-bold">New Message</h2>
      </template>
      <template #body>
        <div class="space-y-2">
          <label class="block">
            <span>Recipient</span>
            <!-- Fixed: Removed type="text" attribute -->
            <Select v-model="form.to_user" class="border rounded w-full p-1">
              <option
                v-for="(item, index) in props.names"
                :key="index"
                :value="item"
                class="bg-white text-black dark:bg-black dark:text-white"
              >
                {{ item.toUpperCase() }}
              </option>
            </Select>
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
            <Select v-model="form.type" class="border rounded w-full p-1">
              <option
                value="normal"
                class="bg-white text-black dark:bg-black dark:text-white"
              >
                Normal
              </option>
              <option
                value="urgent"
                class="bg-white text-black dark:bg-black dark:text-white"
              >
                Urgent
              </option>
              <option
                value="positive"
                class="bg-white text-black dark:bg-black dark:text-white"
              >
                Positive
              </option>
              <option
                value="negative"
                class="bg-white text-black dark:bg-black dark:text-white"
              >
                Negative
              </option>
            </Select>
            <InputError :message="form.errors.type" />
          </label>
          <label class="block">
            <span>Scope</span>
            <Select v-model="form.scope" class="border rounded w-full p-1">
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="local"
              >
                Local
              </option>
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="global"
              >
                Global
              </option>
            </Select>
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
    <!-- Read Message Dialog -->
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
          <p class="text-gray-700 dark:text-gray-200">{{ selectedMessage.content }}</p>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button @click="readDialog = false">Close</Button>
        </div>
      </template>
    </Dialog>
    <!-- Edit Message Dialog -->
    <Dialog v-model="editDialog">
      <template #header>
        <h2 class="text-lg font-bold">Edit Message</h2>
      </template>
      <template #body>
        <div class="space-y-2">
          <label class="block">
            <span>Recipient</span>
            <!-- Fixed: Removed type="text" attribute -->
            <Select
              v-model="editForm.to_user"
              class="border rounded w-full p-1"
              :disabled="editForm.scope === 'global'"
            >
              <option
                v-for="(item, index) in props.names"
                :key="index"
                :value="item"
                class="bg-white text-black dark:bg-black dark:text-white"
              >
                {{ item.toUpperCase() }}
              </option>
            </Select>
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
            <Select v-model="editForm.type" class="border rounded w-full p-1">
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="normal"
              >
                Normal
              </option>
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="urgent"
              >
                Urgent
              </option>
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="positive"
              >
                Positive
              </option>
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="negative"
              >
                Negative
              </option>
            </Select>
            <InputError :message="editForm.errors.type" />
          </label>
          <label class="block">
            <span>Scope</span>
            <Select v-model="editForm.scope" class="border rounded w-full p-1">
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="local"
              >
                Local
              </option>
              <option
                class="bg-white text-black dark:bg-black dark:text-white"
                value="global"
              >
                Global
              </option>
            </Select>
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
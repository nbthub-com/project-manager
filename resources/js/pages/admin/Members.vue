<!-- Admin -->

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Table from '@/components/ui/table/Table.vue';
import { defineProps, ref } from 'vue';
import AppLayoutAdmin from '@/layouts/AppLayoutAdmin.vue';
import Dialog from '@/components/ui/simpleidalog/Dialog.vue';

const props = defineProps(['users']); // non-admin members

const breadcrumbs = [
  { title: 'Members', href: '/' },
];

// dialog open state
const isDialogOpen = ref(false);

// Reactive form for adding members
const form = useForm({
  name: '',
  email: '',
  role: 'user',
  password: '',
  password_confirmation: ''
});

const successMessage = ref('');

// Submit handler
function submitForm() {
  form.post('/members/add-member', {
    onSuccess: (page) => {
      successMessage.value = 'Member added successfully!';
      form.reset(); 
      isDialogOpen.value = false; // close dialog after success
    },
    onError: (errors) => {
      console.log(errors); 
    }
  });
}
</script>

<template>
  <Head title="Members" />

  <AppLayoutAdmin :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

      <!-- Open Dialog Button -->
      <button
        @click="isDialogOpen = true"
        class="self-start bg-black text-white rounded-lg px-4 py-2"
      >
        + Add Member
      </button>

      <!-- Success message -->
      <p v-if="successMessage" class="text-green-600">{{ successMessage }}</p>

      <!-- Members Table -->
      <Table :rows="props.users"></Table>

      <!-- Dialog -->
      <Dialog v-model="isDialogOpen">
        <!-- Header slot -->
        <template #header>
          <h2 class="text-lg font-semibold">Add New Member</h2>
        </template>

        <!-- Body slot -->
        <template #body>
          <form @submit.prevent="submitForm" class="flex flex-col gap-2">
            <input v-model="form.name" placeholder="Name" class="border p-2 rounded" />
            <span v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</span>

            <input v-model="form.email" placeholder="Email" class="border p-2 rounded" />
            <span v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</span>

            <select v-model="form.role" class="border p-2 rounded">
              <option value="user">User</option>
              <option value="manager">Manager</option>
            </select>
            <span v-if="form.errors.role" class="text-red-500">{{ form.errors.role }}</span>

            <input type="password" v-model="form.password" placeholder="Password" class="border p-2 rounded" />
            <span v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</span>

            <input type="password" v-model="form.password_confirmation" placeholder="Confirm Password" class="border p-2 rounded" />
            <span v-if="form.errors.password_confirmation" class="text-red-500">{{ form.errors.password_confirmation }}</span>
          </form>
        </template>

        <!-- Footer slot -->
        <template #footer>
          <button
            @click="isDialogOpen = false"
            type="button"
            class="rounded-lg px-4 py-2 bg-gray-200"
          >
            Cancel
          </button>
          <button
            @click="submitForm"
            type="button"
            class="rounded-lg px-4 py-2 bg-black text-white"
          >
            Save
          </button>
        </template>
      </Dialog>
    </div>
  </AppLayoutAdmin>
</template>

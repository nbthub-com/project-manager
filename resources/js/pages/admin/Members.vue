<!-- Admin -->

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Table from '@/components/ui/table/Table.vue';
import { defineProps, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Dialog from '@/components/ui/simpleidalog/Dialog.vue';
import Button from '@/components/ui/button/Button.vue';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';
import { Search } from 'lucide-vue-next';

const props = defineProps(['users']); // non-admin members

const breadcrumbs = [
  { title: 'Members', href: '/admin/members' },
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
  form.post('/admin/members/add-member', {
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

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

      <!-- Open Dialog Button -->
      <div class="border-b-2 flex flex-row justify-between py-1.5 items-center gap-2 ">
        <div class="flex flex-row">
          <Input
            class="w-[70%] focus:w-[90%] transition-all duration-300 ease-in-out rounded-r-none"
          />
          <Button class="rounded-l-none" variant="outline"><Search /></Button>
        </div>
        <Button
          @click="isDialogOpen = true"
        >
          + Add
        </Button>
      </div>
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
            <Input v-model="form.name" placeholder="Name" />
            <InputError :message="form.errors.name" class="text-red-500" />

            <Input v-model="form.email" placeholder="Email" />
            <InputError :message="form.errors.email" class="text-red-500" />

            <select v-model="form.role">
              <option value="user">User</option>
              <option value="manager">Manager</option>
            </select>
            <InputError :message="form.errors.role" class="text-red-500" />

            <Input type="password" v-model="form.password" placeholder="Password" />
            <InputError :message="form.errors.password" class="text-red-500" />

            <Input type="password" v-model="form.password_confirmation" placeholder="Confirm Password" />
            <InputError :message="form.errors.password_confirmation" class="text-red-500" />
          </form>
        </template>

        <!-- Footer slot -->
        <template #footer>
          <Button
            @click="isDialogOpen = false"
            type="Button"
          >
            Cancel
          </Button>
          <Button
            @click="submitForm"
            type="Button"
          >
            Save
          </Button>
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

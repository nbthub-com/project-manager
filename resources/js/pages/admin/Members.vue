<!-- Admin -->

<script setup lang="js">
import { Head } from "@inertiajs/vue3";
import Table from "@/components/ui/table/Table.vue";
import { defineProps, ref, reactive } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Button from "@/components/ui/button/Button.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import { Search } from "lucide-vue-next";
import axios from "axios";
import Select from "@/components/ui/select/select.vue";

const props = defineProps(["users"]);
const s_query = ref("");
const filteredUsers = ref([...props.users]);

const breadcrumbs = [{ title: "Members", href: "/admin/members" }];

const isDialogOpen = ref(false);
const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  errors: {}, // store validation errors
});
const successMessage = ref("");

async function submitForm() {
  try {
    const res = await axios.post("/admin/members/add-member", {
      name: form.name,
      email: form.email,
      role: 'user',
      password: form.password,
      password_confirmation: form.password_confirmation,
    });
    window.location.href = '/admin/members'
  } catch (error) {
    if (error.response?.status === 422) {
      // Laravel validation error
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
</script>

<template>
  <Head title="Members" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <!-- Search + Add -->
      <div class="border-b-2 flex flex-row justify-between p-1 items-center gap-2 outline-1 rounded-lg">
        <div class="flex flex-row">
          <Input
            v-model="s_query"
            @input="search"
            class="w-[70%] focus:w-[90%] transition-all duration-300 ease-in-out rounded-r-none"
          />
          <Button class="rounded-l-none" variant="outline" @click="search"
            ><Search
          /></Button>
        </div>
        <Button @click="isDialogOpen = true">+ Add</Button>
      </div>

      <!-- Success -->
      <p v-if="successMessage" class="text-green-600">{{ successMessage }}</p>

      <!-- Table -->
      <Table
        :rows="filteredUsers"
        table-title="Members"
        :headers="['id', 'name', 'email']"
      />

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
        <!-- Footer slot -->
        <template #footer>
          <Button @click="isDialogOpen = false" type="Button"> Cancel </Button>
          <Button @click="submitForm" type="Button"> Save </Button>
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

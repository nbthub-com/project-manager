<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Table from "@/components/ui/table/Table.vue";
import { defineProps, ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import Dialog from "@/components/ui/simpleidalog/Dialog.vue";
import Button from "@/components/ui/button/Button.vue";
import InputError from "@/components/InputError.vue";
import Input from "@/components/ui/input/Input.vue";
import { Search } from "lucide-vue-next";
import axios from "axios"; // ✅ import axios
import DropdownMenu from "@/components/ui/dropdown-menu/DropdownMenu.vue";
import Select from "@/components/ui/select/select.vue";

const props = defineProps(["users"]); // initial members from server
const s_query = ref("");
const filteredUsers = ref([...props.users]); // reactive copy

const breadcrumbs = [{ title: "Members", href: "/admin/members" }];

const isDialogOpen = ref(false);

const form = useForm({
  name: "",
  email: "",
  role: "user",
  password: "",
  password_confirmation: "",
});

const successMessage = ref("");

function submitForm() {
  form.post("/admin/members/add-member", {
    onSuccess: () => {
      successMessage.value = "Member added successfully!";
      form.reset();
      isDialogOpen.value = false;
    },
  });
}

// ✅ JSON search handler
async function search() {
  if (!s_query.value) {
    filteredUsers.value = [...props.users]; // reset if empty
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
      <div class="border-b-2 flex flex-row justify-between py-1.5 items-center gap-2">
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
      <Table :rows="filteredUsers"></Table>

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
            <Select
              v-model="form.role"
            >
              <option value="user">User</option>
              <option value="manager">Manager</option>
            </Select>
            <InputError :message="form.errors.role" class="text-red-500" />
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

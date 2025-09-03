<!-- Admin -->

<script setup lang="js">
import { Head } from '@inertiajs/vue3';
import { defineProps, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Table from '@/components/ui/table/Table.vue';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import { Search } from 'lucide-vue-next';

const breadcrumbs= [
    {
        title: 'Projects',
        href: '/admin/projects',
    },
];

const props = defineProps(['projects'])
const s_query = ref("")

function search(){

}
const isDialogOpen = ref(false)
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
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
      <Table :rows="props.projects" table-title="Projects"/>
    </div>
  </AppLayout>
</template>

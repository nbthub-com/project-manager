<!-- Admin -->

<script setup lang="js">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { defineProps } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs= [
    {
        title: 'Dashboard',
        href: '/admin',
    },
];

const props = defineProps(['stats'])

</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-2">
                <div class="relative p-3 aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <h1>Total Users</h1>
                    <h2>{{ props.stats.user_count }}</h2>
                </div>
                <div class="relative p-3 aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <h1>Total Managers</h1>
                    <h2>{{ props.stats.manager_count }}</h2>
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>

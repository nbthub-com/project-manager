
<!-- Admin -->

<script setup lang="ts">
import type { BreadcrumbItemType } from '@/types';
import AppSidebarLayoutAdmin from '@/layouts/app/AppSidebarLayoutAdmin.vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppSidebarLayoutAdmin :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayoutAdmin>
</template>

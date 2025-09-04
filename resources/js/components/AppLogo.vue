<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Projector } from 'lucide-vue-next';
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-transparent">
        <Projector class="size-4 bg-transparent w-full h-full" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm items-center">
        <span class="truncate leading-tight font-semibold">Project Manager</span>
    </div>
</template>

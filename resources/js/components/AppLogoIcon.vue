<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Projector } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
    size?: number;
}

defineProps<Props>();
</script>

<template>
    <Projector :class="cn('text-black dark:text-white', className)" :size="size"/>
</template>

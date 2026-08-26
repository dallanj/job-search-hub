<script setup lang="ts">
import ApplicationCard from '@/components/pipeline/ApplicationCard.vue';
import type { PipelineColumn } from '@/types';

defineProps<{
    column: PipelineColumn;
    columnIndex: number;
    columnCount: number;
    disabled: boolean;
    allowReordering: boolean;
}>();

const emit = defineEmits<{
    dragStart: [applicationId: number];
    dropAt: [position: number];
    moveUp: [applicationId: number];
    moveDown: [applicationId: number];
    movePrevious: [applicationId: number];
    moveNext: [applicationId: number];
}>();
</script>

<template>
    <section
        class="flex max-h-full w-72 shrink-0 flex-col rounded-xl border bg-muted/30"
        @dragover.prevent
        @drop.prevent="emit('dropAt', column.applications.length)"
    >
        <header class="flex items-center justify-between border-b px-3 py-3">
            <h2 class="text-sm font-semibold">{{ column.label }}</h2>
            <span
                class="rounded-full bg-background px-2 py-0.5 text-xs text-muted-foreground"
            >
                {{ column.applications.length }}
            </span>
        </header>

        <div class="min-h-32 space-y-2 overflow-y-auto p-2">
            <div
                v-for="(application, index) in column.applications"
                :key="application.id"
                class="rounded-lg"
                @dragover.prevent
                @drop.stop.prevent="emit('dropAt', index)"
            >
                <ApplicationCard
                    :application="application"
                    :is-first="!allowReordering || index === 0"
                    :is-last="
                        !allowReordering ||
                        index === column.applications.length - 1
                    "
                    :has-previous-column="columnIndex > 0"
                    :has-next-column="columnIndex < columnCount - 1"
                    :disabled="disabled"
                    @drag-start="emit('dragStart', $event)"
                    @move-up="emit('moveUp', application.id)"
                    @move-down="emit('moveDown', application.id)"
                    @move-previous="emit('movePrevious', application.id)"
                    @move-next="emit('moveNext', application.id)"
                />
            </div>

            <div
                v-if="column.applications.length === 0"
                class="flex min-h-28 items-center justify-center rounded-lg border border-dashed p-4 text-center text-xs text-muted-foreground"
            >
                Drop an application here
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowRight,
    ArrowUp,
    GripVertical,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/applications';
import type { PipelineApplication } from '@/types';

defineProps<{
    application: PipelineApplication;
    isFirst: boolean;
    isLast: boolean;
    hasPreviousColumn: boolean;
    hasNextColumn: boolean;
    disabled: boolean;
}>();

const emit = defineEmits<{
    dragStart: [applicationId: number];
    moveUp: [];
    moveDown: [];
    movePrevious: [];
    moveNext: [];
}>();
</script>

<template>
    <article
        draggable="true"
        class="group rounded-lg border bg-card p-3 shadow-xs transition-shadow hover:shadow-sm"
        :class="{ 'cursor-grabbing opacity-60': disabled }"
        @dragstart="emit('dragStart', application.id)"
    >
        <div class="flex items-start gap-2">
            <GripVertical
                class="mt-0.5 size-4 shrink-0 cursor-grab text-muted-foreground"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <Link
                    :href="show(application)"
                    class="line-clamp-2 text-sm font-medium hover:underline"
                >
                    {{ application.role_title }}
                </Link>
                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{ application.company.name }}
                </p>
                <p
                    v-if="application.location"
                    class="mt-2 truncate text-xs text-muted-foreground"
                >
                    {{ application.location }}
                </p>
            </div>
        </div>

        <div
            class="mt-3 flex items-center justify-end gap-1 border-t pt-2 opacity-70 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100"
            aria-label="Move application"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="disabled || !hasPreviousColumn"
                :aria-label="`Move ${application.role_title} to previous stage`"
                @click="emit('movePrevious')"
            >
                <ArrowLeft class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="disabled || isFirst"
                :aria-label="`Move ${application.role_title} up`"
                @click="emit('moveUp')"
            >
                <ArrowUp class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="disabled || isLast"
                :aria-label="`Move ${application.role_title} down`"
                @click="emit('moveDown')"
            >
                <ArrowDown class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="disabled || !hasNextColumn"
                :aria-label="`Move ${application.role_title} to next stage`"
                @click="emit('moveNext')"
            >
                <ArrowRight class="size-3.5" />
            </Button>
        </div>
    </article>
</template>

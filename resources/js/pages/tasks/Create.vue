<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TaskForm from '@/components/tasks/TaskForm.vue';
import { index, store } from '@/routes/tasks';
import type { InterviewApplication, TaskPriorityOption } from '@/types';

defineProps<{
    applications: InterviewApplication[];
    priorities: TaskPriorityOption[];
    selectedApplicationId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tasks', href: index() },
            { title: 'Add task', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Add task" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            title="Add follow-up task"
            description="Keep the next action for an application visible."
        />
        <TaskForm
            :action="store.url()"
            :applications="applications"
            :priorities="priorities"
            :selected-application-id="selectedApplicationId"
            submit-label="Add task"
        />
    </div>
</template>

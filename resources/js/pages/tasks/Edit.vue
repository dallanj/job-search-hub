<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TaskForm from '@/components/tasks/TaskForm.vue';
import { index, update } from '@/routes/tasks';
import type {
    FollowUpTask,
    InterviewApplication,
    TaskPriorityOption,
} from '@/types';

defineProps<{
    task: FollowUpTask;
    applications: InterviewApplication[];
    priorities: TaskPriorityOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tasks', href: index() },
            { title: 'Edit task', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Edit task" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            title="Edit follow-up task"
            description="Update the action, deadline, or priority."
        />
        <TaskForm
            :action="update.url(task)"
            method="patch"
            :task="task"
            :applications="applications"
            :priorities="priorities"
            submit-label="Save changes"
        />
    </div>
</template>

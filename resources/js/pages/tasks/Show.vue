<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Check, Circle, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { show as applicationShow } from '@/routes/applications';
import { completion, destroy, edit, index } from '@/routes/tasks';
import type { FollowUpTask, TaskPriorityOption } from '@/types';

const props = defineProps<{
    task: FollowUpTask;
    priorities: TaskPriorityOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tasks', href: index() },
            { title: 'Task details', href: index() },
        ],
    },
});

const priorityLabel = (): string =>
    props.priorities.find((priority) => priority.value === props.task.priority)
        ?.label ?? 'Normal';

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-CA', { dateStyle: 'full' }).format(
        new Date(`${value.slice(0, 10)}T12:00:00`),
    );

const confirmDelete = (): boolean => window.confirm('Delete this task?');
</script>

<template>
    <Head :title="task.title" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between">
            <div>
                <p class="mb-2 text-sm text-muted-foreground">
                    {{ task.completed_at ? 'Completed' : 'Open' }} ·
                    {{ priorityLabel() }} priority
                </p>
                <h1 class="text-2xl font-semibold">{{ task.title }}</h1>
                <Link
                    :href="applicationShow(task.job_application)"
                    class="text-muted-foreground hover:underline"
                >
                    {{ task.job_application.role_title }} at
                    {{ task.job_application.company.name }}
                </Link>
            </div>
            <div class="flex flex-wrap gap-2">
                <Form
                    :action="completion.url(task)"
                    method="patch"
                    v-slot="{ processing }"
                >
                    <Button :disabled="processing">
                        <Circle v-if="task.completed_at" class="size-4" />
                        <Check v-else class="size-4" />
                        {{ task.completed_at ? 'Reopen' : 'Complete' }}
                    </Button>
                </Form>
                <Button variant="outline" as-child>
                    <Link :href="edit(task)">
                        <Pencil class="size-4" />Edit
                    </Link>
                </Button>
                <Form
                    :action="destroy.url(task)"
                    method="delete"
                    v-slot="{ processing }"
                    @before="confirmDelete"
                >
                    <Button variant="destructive" :disabled="processing">
                        <Trash2 class="size-4" />Delete
                    </Button>
                </Form>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2">
            <div>
                <p class="text-xs text-muted-foreground uppercase">Due date</p>
                <p class="mt-1 text-sm">
                    {{ task.due_at ? formatDate(task.due_at) : 'Not set' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase">Priority</p>
                <p class="mt-1 text-sm">{{ priorityLabel() }}</p>
            </div>
        </div>
    </div>
</template>

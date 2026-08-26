<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/tasks';
import type {
    FollowUpTask,
    InterviewApplication,
    TaskPriorityOption,
} from '@/types';

withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'patch';
        applications: InterviewApplication[];
        priorities: TaskPriorityOption[];
        task?: FollowUpTask;
        selectedApplicationId?: number | null;
        submitLabel: string;
    }>(),
    { method: 'post', task: undefined, selectedApplicationId: null },
);
</script>

<template>
    <Form
        :action="action"
        :method="method"
        class="space-y-8"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2 md:col-span-2">
                <Label for="title">Task</Label>
                <Input
                    id="title"
                    name="title"
                    :default-value="task?.title"
                    placeholder="Send a thank-you email"
                    autofocus
                    required
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="job_application_id">Application</Label>
                <select
                    id="job_application_id"
                    name="job_application_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    :value="
                        task?.job_application_id ?? selectedApplicationId ?? ''
                    "
                    required
                >
                    <option value="" disabled>Select an application</option>
                    <option
                        v-for="application in applications"
                        :key="application.id"
                        :value="application.id"
                    >
                        {{ application.role_title }} —
                        {{ application.company.name }}
                    </option>
                </select>
                <InputError :message="errors.job_application_id" />
            </div>
            <div class="grid gap-2">
                <Label for="due_at">Due date</Label>
                <Input
                    id="due_at"
                    name="due_at"
                    type="date"
                    :default-value="task?.due_at?.slice(0, 10)"
                />
                <InputError :message="errors.due_at" />
            </div>
            <div class="grid gap-2">
                <Label for="priority">Priority</Label>
                <select
                    id="priority"
                    name="priority"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    :value="task?.priority ?? 2"
                    required
                >
                    <option
                        v-for="priority in priorities"
                        :key="priority.value"
                        :value="priority.value"
                    >
                        {{ priority.label }}
                    </option>
                </select>
                <InputError :message="errors.priority" />
            </div>
        </div>
        <div class="flex gap-3">
            <Button :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>

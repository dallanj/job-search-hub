<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Check, Circle, Plus, Search } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { completion, create, index, show } from '@/routes/tasks';
import type {
    FollowUpTask,
    Paginated,
    TaskFilters,
    TaskPriorityOption,
} from '@/types';

const props = defineProps<{
    tasks: Paginated<FollowUpTask>;
    filters: TaskFilters;
    priorities: TaskPriorityOption[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Tasks', href: index() }] },
});

const tabs = [
    { value: 'open', label: 'Open' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'completed', label: 'Completed' },
] as const;

const priorityLabel = (value: number): string =>
    props.priorities.find((priority) => priority.value === value)?.label ??
    'Normal';

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-CA', { dateStyle: 'medium' }).format(
        new Date(`${value.slice(0, 10)}T12:00:00`),
    );

const filtered = computed(
    () => props.filters.search !== null || props.filters.status !== 'open',
);

const changeStatus = (status: TaskFilters['status']): void => {
    router.get(
        index.url(),
        { search: props.filters.search ?? undefined, status },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
</script>

<template>
    <Head title="Tasks" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Follow-up tasks</h1>
                <p class="text-sm text-muted-foreground">
                    Keep every next action moving forward.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()"> <Plus class="size-4" />Add task </Link>
            </Button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Form
                :action="index.url()"
                method="get"
                class="flex max-w-md flex-1 gap-2"
            >
                <input type="hidden" name="status" :value="filters.status" />
                <div class="relative flex-1">
                    <Search
                        class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters.search ?? ''"
                        class="pl-9"
                        placeholder="Search tasks or applications"
                    />
                </div>
                <Button variant="outline">Search</Button>
            </Form>
            <div class="flex rounded-lg border p-1">
                <Button
                    v-for="tab in tabs"
                    :key="tab.value"
                    size="sm"
                    :variant="
                        filters.status === tab.value ? 'secondary' : 'ghost'
                    "
                    @click="changeStatus(tab.value)"
                >
                    {{ tab.label }}
                </Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div v-if="tasks.data.length" class="divide-y">
                <div
                    v-for="task in tasks.data"
                    :key="task.id"
                    class="flex items-start gap-3 p-4 hover:bg-muted/30"
                >
                    <Form
                        :action="completion.url(task)"
                        method="patch"
                        v-slot="{ processing }"
                    >
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-8 rounded-full"
                            :disabled="processing"
                            :aria-label="
                                task.completed_at
                                    ? 'Reopen task'
                                    : 'Complete task'
                            "
                        >
                            <Check
                                v-if="task.completed_at"
                                class="size-5 text-primary"
                            />
                            <Circle v-else class="size-5" />
                        </Button>
                    </Form>
                    <Link :href="show(task)" class="min-w-0 flex-1">
                        <p
                            class="font-medium"
                            :class="{
                                'text-muted-foreground line-through':
                                    task.completed_at,
                            }"
                        >
                            {{ task.title }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ task.job_application.role_title }} at
                            {{ task.job_application.company.name }}
                        </p>
                    </Link>
                    <div class="shrink-0 text-right text-xs">
                        <p
                            class="font-medium"
                            :class="{
                                'text-destructive':
                                    !task.completed_at &&
                                    task.due_at &&
                                    task.due_at.slice(0, 10) <
                                        new Date().toISOString().slice(0, 10),
                            }"
                        >
                            {{
                                task.due_at
                                    ? formatDate(task.due_at)
                                    : 'No due date'
                            }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ priorityLabel(task.priority) }} priority
                        </p>
                    </div>
                </div>
            </div>
            <div v-else class="p-12 text-center">
                <h2 class="font-medium">
                    {{ filtered ? 'No matching tasks' : 'No open tasks' }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        filtered
                            ? 'Try another search or task status.'
                            : 'Add a follow-up so the next step is never lost.'
                    }}
                </p>
                <Button v-if="filtered" variant="link" as-child>
                    <Link :href="index()">Clear filters</Link>
                </Button>
            </div>
        </div>

        <nav v-if="tasks.last_page > 1" class="flex flex-wrap gap-2">
            <Button
                v-for="link in tasks.links"
                :key="link.label"
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                as-child
            >
                <Link v-if="link.url" :href="link.url">
                    <span v-html="link.label" />
                </Link>
                <span v-else v-html="link.label" />
            </Button>
        </nav>
    </div>
</template>

<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    CircleDot,
    ExternalLink,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/applications';
import {
    create as createInterview,
    show as showInterview,
} from '@/routes/interviews';
import {
    completion as taskCompletion,
    create as createTask,
    show as showTask,
} from '@/routes/tasks';
import type { JobApplication, StatusOption } from '@/types';

const props = defineProps<{
    application: JobApplication;
    statuses: StatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: index() },
            { title: 'Application details', href: index() },
        ],
    },
});

const statusLabel = (): string =>
    props.statuses.find((status) => status.value === props.application.status)
        ?.label ?? props.application.status;

const labelForStatus = (value: string): string =>
    props.statuses.find((status) => status.value === value)?.label ?? value;

const formatDateTime = (value: string): string =>
    new Intl.DateTimeFormat('en-CA', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const salary = (): string => {
    const { salary_min, salary_max, salary_currency } = props.application;

    if (salary_min === null && salary_max === null) {
        return 'Not specified';
    }

    const formatter = new Intl.NumberFormat('en-CA', {
        style: 'currency',
        currency: salary_currency ?? 'CAD',
        maximumFractionDigits: 0,
    });

    if (salary_min !== null && salary_max !== null) {
        return `${formatter.format(salary_min)} – ${formatter.format(salary_max)}`;
    }

    return formatter.format(salary_min ?? salary_max ?? 0);
};

const confirmDelete = (): boolean => window.confirm('Delete this application?');
</script>

<template>
    <Head :title="application.role_title" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span
                        class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium"
                    >
                        {{ statusLabel() }}
                    </span>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ application.role_title }}
                </h1>
                <p class="text-muted-foreground">
                    {{ application.company.name }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(application)">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>
                <Form
                    :action="destroy.url(application)"
                    method="delete"
                    v-slot="{ processing }"
                    @before="confirmDelete"
                >
                    <Button variant="destructive" :disabled="processing">
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </Form>
            </div>
        </div>

        <div
            class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Location
                </p>
                <p class="mt-1 text-sm">
                    {{ application.location || 'Not specified' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Employment
                </p>
                <p class="mt-1 text-sm">
                    {{ application.employment_type || 'Not specified' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Workplace
                </p>
                <p class="mt-1 text-sm">
                    {{ application.workplace_type || 'Not specified' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Salary
                </p>
                <p class="mt-1 text-sm">{{ salary() }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Applied
                </p>
                <p class="mt-1 text-sm">
                    {{ application.applied_at?.slice(0, 10) || 'Not yet' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Source
                </p>
                <p class="mt-1 text-sm">
                    {{ application.source || 'Not specified' }}
                </p>
            </div>
        </div>

        <div v-if="application.description" class="rounded-xl border p-5">
            <h2 class="font-medium">Description and notes</h2>
            <p class="mt-3 text-sm whitespace-pre-wrap text-muted-foreground">
                {{ application.description }}
            </p>
        </div>

        <section class="rounded-xl border p-5">
            <div>
                <h2 class="font-medium">Status history</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Every stage transition recorded for this application.
                </p>
            </div>

            <ol v-if="application.status_events?.length" class="mt-5 space-y-5">
                <li
                    v-for="event in application.status_events"
                    :key="event.id"
                    class="flex gap-3"
                >
                    <CircleDot
                        class="mt-0.5 size-4 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <template v-if="event.from_status">
                                <span class="font-medium">
                                    {{ labelForStatus(event.from_status) }}
                                </span>
                                <ArrowRight
                                    class="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </template>
                            <span class="font-medium">
                                {{ labelForStatus(event.to_status) }}
                            </span>
                        </div>
                        <time
                            :datetime="event.changed_at"
                            class="mt-1 block text-xs text-muted-foreground"
                        >
                            {{ formatDateTime(event.changed_at) }}
                        </time>
                        <p
                            v-if="event.note"
                            class="mt-2 text-sm whitespace-pre-wrap text-muted-foreground"
                        >
                            {{ event.note }}
                        </p>
                    </div>
                </li>
            </ol>

            <p v-else class="mt-5 text-sm text-muted-foreground">
                Status tracking begins with the next stage change.
            </p>
        </section>

        <section class="rounded-xl border p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-medium">Follow-up tasks</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Next actions connected to this application.
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link
                        :href="
                            createTask({
                                query: { application: application.id },
                            })
                        "
                    >
                        Add task
                    </Link>
                </Button>
            </div>
            <div v-if="application.tasks?.length" class="mt-4 divide-y">
                <div
                    v-for="task in application.tasks"
                    :key="task.id"
                    class="flex items-center gap-2 py-3"
                >
                    <Form
                        :action="taskCompletion.url(task)"
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
                            <CircleDot v-else class="size-5" />
                        </Button>
                    </Form>
                    <Link
                        :href="showTask(task)"
                        class="min-w-0 flex-1 text-sm hover:underline"
                        :class="{
                            'text-muted-foreground line-through':
                                task.completed_at,
                        }"
                    >
                        {{ task.title }}
                    </Link>
                    <time
                        v-if="task.due_at"
                        :datetime="task.due_at"
                        class="text-xs text-muted-foreground"
                    >
                        {{ task.due_at.slice(0, 10) }}
                    </time>
                </div>
            </div>
            <p v-else class="mt-4 text-sm text-muted-foreground">
                No follow-up tasks yet.
            </p>
        </section>

        <section class="rounded-xl border p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-medium">Interviews</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Scheduled conversations for this application.
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child
                    ><Link
                        :href="
                            createInterview({
                                query: { application: application.id },
                            })
                        "
                        >Schedule</Link
                    ></Button
                >
            </div>
            <div v-if="application.interviews?.length" class="mt-4 divide-y">
                <Link
                    v-for="interview in application.interviews"
                    :key="interview.id"
                    :href="showInterview(interview)"
                    class="flex items-center justify-between gap-3 py-3 text-sm hover:underline"
                    ><span
                        >{{ labelForStatus(interview.type) }} interview<span
                            v-if="interview.contact"
                        >
                            with {{ interview.contact.name }}</span
                        ></span
                    ><time
                        :datetime="interview.scheduled_at"
                        class="text-muted-foreground"
                        >{{ formatDateTime(interview.scheduled_at) }}</time
                    ></Link
                >
            </div>
            <p v-else class="mt-4 text-sm text-muted-foreground">
                No interviews scheduled yet.
            </p>
        </section>

        <div class="flex flex-wrap gap-3">
            <Button v-if="application.job_url" variant="outline" as-child>
                <a :href="application.job_url" target="_blank" rel="noreferrer">
                    View job posting
                    <ExternalLink class="size-4" />
                </a>
            </Button>
            <Button v-if="application.company.website" variant="ghost" as-child>
                <a
                    :href="application.company.website"
                    target="_blank"
                    rel="noreferrer"
                >
                    Company website
                    <ExternalLink class="size-4" />
                </a>
            </Button>
        </div>
    </div>
</template>

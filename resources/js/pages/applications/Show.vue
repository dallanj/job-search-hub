<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/applications';
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

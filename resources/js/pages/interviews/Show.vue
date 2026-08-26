<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { show as applicationShow } from '@/routes/applications';
import { destroy, edit, index } from '@/routes/interviews';
import type { Interview, InterviewOption } from '@/types';
defineProps<{
    interview: Interview;
    types: InterviewOption[];
    outcomes: InterviewOption[];
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interviews', href: index() },
            { title: 'Interview details', href: index() },
        ],
    },
});
const label = (options: InterviewOption[], value: string | null): string =>
    options.find((item) => item.value === value)?.label ?? value ?? 'Not set';
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-CA', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(value));
const confirmDelete = (): boolean => window.confirm('Delete this interview?');
</script>
<template>
    <Head title="Interview details" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ label(types, interview.type) }} interview
                </h1>
                <Link
                    :href="applicationShow(interview.job_application)"
                    class="text-muted-foreground hover:underline"
                    >{{ interview.job_application.role_title }} at
                    {{ interview.job_application.company.name }}</Link
                >
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child
                    ><Link :href="edit(interview)"
                        ><Pencil class="size-4" />Edit</Link
                    ></Button
                ><Form
                    :action="destroy.url(interview)"
                    method="delete"
                    v-slot="{ processing }"
                    @before="confirmDelete"
                    ><Button variant="destructive" :disabled="processing"
                        ><Trash2 class="size-4" />Delete</Button
                    ></Form
                >
            </div>
        </div>
        <div class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2">
            <div>
                <p class="text-xs text-muted-foreground uppercase">Scheduled</p>
                <p class="mt-1 text-sm">
                    {{ formatDate(interview.scheduled_at) }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase">Duration</p>
                <p class="mt-1 text-sm">
                    {{
                        interview.duration_minutes
                            ? `${interview.duration_minutes} minutes`
                            : 'Not specified'
                    }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase">
                    Interviewer
                </p>
                <p class="mt-1 text-sm">
                    {{ interview.contact?.name || 'Not known yet' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase">Outcome</p>
                <p class="mt-1 text-sm">
                    {{ label(outcomes, interview.outcome) }}
                </p>
            </div>
        </div>
        <section v-if="interview.notes" class="rounded-xl border p-5">
            <h2 class="font-medium">Notes</h2>
            <p class="mt-3 text-sm whitespace-pre-wrap text-muted-foreground">
                {{ interview.notes }}
            </p>
        </section>
        <Button
            v-if="interview.location_or_url?.startsWith('http')"
            variant="outline"
            as-child
            ><a
                :href="interview.location_or_url"
                target="_blank"
                rel="noreferrer"
                >Open meeting link<ExternalLink class="size-4" /></a
        ></Button>
        <p v-else-if="interview.location_or_url" class="text-sm">
            Location: {{ interview.location_or_url }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/interviews';
import type { Interview, Paginated } from '@/types';
defineProps<{ interviews: Paginated<Interview> }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Interviews', href: index() }] },
});
const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-CA', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
</script>
<template>
    <Head title="Interviews" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Interviews</h1>
                <p class="text-sm text-muted-foreground">
                    Track scheduled conversations and outcomes.
                </p>
            </div>
            <Button as-child
                ><Link :href="create()"
                    ><Plus class="size-4" />Schedule interview</Link
                ></Button
            >
        </div>
        <div class="overflow-hidden rounded-xl border">
            <div v-if="interviews.data.length" class="divide-y">
                <Link
                    v-for="interview in interviews.data"
                    :key="interview.id"
                    :href="show(interview)"
                    class="flex flex-col gap-1 p-4 hover:bg-muted/30 sm:flex-row sm:items-center sm:justify-between"
                    ><div>
                        <p class="font-medium">
                            {{ interview.job_application.role_title }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ interview.job_application.company.name }} ·
                            {{ interview.type }}
                        </p>
                    </div>
                    <div class="text-sm sm:text-right">
                        <p>{{ formatDate(interview.scheduled_at) }}</p>
                        <p class="text-muted-foreground">
                            {{
                                interview.contact?.name || 'Interviewer not set'
                            }}
                        </p>
                    </div></Link
                >
            </div>
            <div v-else class="p-12 text-center">
                <h2 class="font-medium">No interviews scheduled</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Schedule one from here or an application detail page.
                </p>
            </div>
        </div>
    </div>
</template>

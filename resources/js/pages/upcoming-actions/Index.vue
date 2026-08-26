<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Check, Circle } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { show as showInterview } from '@/routes/interviews';
import { completion, show as showTask } from '@/routes/tasks';
import { index } from '@/routes/upcoming-actions';
import type { UpcomingAction, UpcomingActionFilters } from '@/types';

const props = defineProps<{
    actions: UpcomingAction[];
    filters: UpcomingActionFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Upcoming actions', href: index() }],
    },
});

const today = new Date().toLocaleDateString('en-CA');
const actionDate = (action: UpcomingAction): string =>
    action.kind === 'task'
        ? action.scheduled_for.slice(0, 10)
        : new Date(action.scheduled_for).toLocaleDateString('en-CA');

const groups = computed(() => [
    {
        key: 'overdue',
        title: 'Overdue',
        description: 'Needs attention now',
        actions: props.actions.filter((action) => action.is_overdue),
    },
    {
        key: 'today',
        title: 'Today',
        description: 'Due or scheduled today',
        actions: props.actions.filter(
            (action) => !action.is_overdue && actionDate(action) === today,
        ),
    },
    {
        key: 'upcoming',
        title: 'Upcoming',
        description: `Within the next ${props.filters.days} days`,
        actions: props.actions.filter(
            (action) => !action.is_overdue && actionDate(action) > today,
        ),
    },
]);

const formatSchedule = (action: UpcomingAction): string => {
    if (action.kind === 'task') {
        return new Intl.DateTimeFormat('en-CA', {
            dateStyle: 'medium',
        }).format(new Date(`${action.scheduled_for.slice(0, 10)}T12:00:00`));
    }

    return new Intl.DateTimeFormat('en-CA', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(action.scheduled_for));
};

const actionUrl = (action: UpcomingAction): string =>
    action.kind === 'task'
        ? showTask.url(action.id)
        : showInterview.url(action.id);

const changeWindow = (days: UpcomingActionFilters['days']): void => {
    router.get(
        index.url(),
        { days },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
</script>

<template>
    <Head title="Upcoming actions" />
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold">Upcoming actions</h1>
                <p class="text-sm text-muted-foreground">
                    Interviews and follow-ups in one chronological view.
                </p>
            </div>
            <div class="flex w-fit rounded-lg border p-1">
                <Button
                    v-for="days in [7, 14, 30] as const"
                    :key="days"
                    size="sm"
                    :variant="filters.days === days ? 'secondary' : 'ghost'"
                    @click="changeWindow(days)"
                >
                    {{ days }} days
                </Button>
            </div>
        </div>

        <div v-if="actions.length" class="space-y-8">
            <section v-for="group in groups" :key="group.key">
                <div class="mb-3 flex items-baseline gap-2">
                    <h2
                        class="font-semibold"
                        :class="{ 'text-destructive': group.key === 'overdue' }"
                    >
                        {{ group.title }}
                    </h2>
                    <span class="text-xs text-muted-foreground">
                        {{ group.description }}
                    </span>
                </div>

                <div
                    v-if="group.actions.length"
                    class="overflow-hidden rounded-xl border"
                >
                    <div
                        v-for="action in group.actions"
                        :key="`${action.kind}-${action.id}`"
                        class="flex items-start gap-3 border-b p-4 last:border-b-0 hover:bg-muted/30"
                    >
                        <Form
                            v-if="action.kind === 'task'"
                            :action="completion.url(action.id)"
                            method="patch"
                            v-slot="{ processing }"
                        >
                            <Button
                                size="icon"
                                variant="ghost"
                                class="size-8 rounded-full"
                                :disabled="processing"
                                aria-label="Complete task"
                            >
                                <Circle class="size-5" />
                            </Button>
                        </Form>
                        <div
                            v-else
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted"
                        >
                            <CalendarDays class="size-4" />
                        </div>

                        <Link :href="actionUrl(action)" class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium">{{ action.title }}</p>
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                >
                                    {{ action.kind }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ action.application.role_title }} at
                                {{ action.application.company.name }} ·
                                {{ action.detail }}
                            </p>
                        </Link>
                        <time
                            :datetime="action.scheduled_for"
                            class="shrink-0 text-sm"
                            :class="{
                                'font-medium text-destructive':
                                    action.is_overdue,
                            }"
                        >
                            {{ formatSchedule(action) }}
                        </time>
                    </div>
                </div>
                <p
                    v-else
                    class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Nothing {{ group.key }}.
                </p>
            </section>
        </div>

        <div v-else class="rounded-xl border p-12 text-center">
            <Check class="mx-auto size-8 text-primary" />
            <h2 class="mt-3 font-medium">You’re all caught up</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                No due tasks or scheduled interviews fall within this window.
            </p>
        </div>
    </div>
</template>

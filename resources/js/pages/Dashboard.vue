<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { show as interviewShow } from '@/routes/interviews';
import { show as taskShow } from '@/routes/tasks';
import { index as upcomingActionsIndex } from '@/routes/upcoming-actions';
import type { UpcomingAction } from '@/types';

type Summary = {
    active_applications: number;
    applications_this_week: number;
    applications_week_change: number;
    interview_rate: number;
    median_response_days: number | null;
};
type FunnelStage = { stage: string; count: number; conversion: number };
type ActivityWeek = {
    week: string;
    label: string;
    applications: number;
    interviews: number;
};
type ResponseTime = {
    median_days: number | null;
    quartile_low: number | null;
    quartile_high: number | null;
    awaiting: number;
    total_responses: number;
    buckets: { label: string; count: number }[];
};
type SourcePerformance = {
    source: string;
    applications: number;
    response_rate: number;
    interview_rate: number;
    offer_rate: number;
    median_response_days: number | null;
};

const props = defineProps<{
    summary: Summary;
    attention: {
        overdue_follow_ups: number;
        interviews_next_seven_days: number;
        awaiting_response_14_days: number;
        saved_not_applied: number;
        without_upcoming_action: number;
    };
    upcoming_actions: UpcomingAction[];
    funnel: FunnelStage[];
    activity: ActivityWeek[];
    response_time: ResponseTime;
    sources: SourcePerformance[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

const summaryCards = computed(() => [
    {
        label: 'Active applications',
        value: String(props.summary.active_applications),
        detail: 'In your pipeline',
        accent: 'border-l-status-applied',
    },
    {
        label: 'Applied this week',
        value: String(props.summary.applications_this_week),
        detail: `${props.summary.applications_week_change >= 0 ? '+' : ''}${props.summary.applications_week_change} vs last week`,
        accent: 'border-l-status-hired',
    },
    {
        label: 'Interview rate',
        value: `${props.summary.interview_rate}%`,
        detail: 'Of applications submitted',
        accent: 'border-l-status-interview',
    },
    {
        label: 'Median first response',
        value:
            props.summary.median_response_days === null
                ? '—'
                : `${props.summary.median_response_days}d`,
        detail: 'Time to hear back',
        accent: 'border-l-status-offer',
    },
]);
const attentionItems = computed(() => [
    {
        label: 'Overdue follow-ups',
        count: props.attention.overdue_follow_ups,
        href: '/tasks?status=overdue',
        action: 'Review tasks',
    },
    {
        label: 'Interviews in the next 7 days',
        count: props.attention.interviews_next_seven_days,
        href: upcomingActionsIndex({ query: { days: 7 } }).url,
        action: 'Prepare',
    },
    {
        label: 'Awaiting a response for 14+ days',
        count: props.attention.awaiting_response_14_days,
        href: applicationsIndex({ query: { status: 'applied' } }).url,
        action: 'Review',
    },
    {
        label: 'Saved jobs not yet applied to',
        count: props.attention.saved_not_applied,
        href: applicationsIndex({ query: { status: 'saved' } }).url,
        action: 'Apply',
    },
    {
        label: 'Active applications with no next action',
        count: props.attention.without_upcoming_action,
        href: applicationsIndex().url,
        action: 'Add follow-up',
    },
]);
const maxActivity = computed(() =>
    Math.max(1, ...props.activity.map((week) => week.applications)),
);
const maxResponseBucket = computed(() =>
    Math.max(1, ...props.response_time.buckets.map((bucket) => bucket.count)),
);
/** Stage bars share the status palette so the funnel reads as a progression. */
const stageAccents: Record<string, string> = {
    applied: 'bg-status-applied',
    screening: 'bg-status-screening',
    interview: 'bg-status-interview',
    offer: 'bg-status-offer',
};
/** Response buckets run fast-to-slow, so the ramp goes green through red. */
const bucketAccents = [
    'bg-status-hired',
    'bg-status-applied',
    'bg-status-interview',
    'bg-status-closed',
];
const responseShare = (count: number): number =>
    props.response_time.total_responses === 0
        ? 0
        : Math.round((count / props.response_time.total_responses) * 100);
const actionUrl = (action: UpcomingAction): string =>
    action.kind === 'task'
        ? taskShow.url(action.id)
        : interviewShow.url(action.id);
const formatActionDate = (action: UpcomingAction): string =>
    action.kind === 'task'
        ? new Intl.DateTimeFormat('en-CA', { dateStyle: 'medium' }).format(
              new Date(`${action.scheduled_for.slice(0, 10)}T12:00:00`),
          )
        : new Intl.DateTimeFormat('en-CA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(action.scheduled_for));
const titleCase = (value: string): string =>
    value.charAt(0).toUpperCase() + value.slice(1);
</script>

<template>
    <Head title="Dashboard" />
    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div>
            <h1 class="text-xl font-semibold">Job search dashboard</h1>
            <p class="text-sm text-muted-foreground">
                What needs attention and where your search is gaining traction.
            </p>
        </div>

        <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div
                v-for="card in summaryCards"
                :key="card.label"
                class="rounded-xl border border-l-4 bg-card p-4"
                :class="card.accent"
            >
                <p class="text-2xl font-semibold tabular-nums">
                    {{ card.value }}
                </p>
                <p class="mt-1 text-sm font-medium">{{ card.label }}</p>
                <p class="text-xs text-muted-foreground">{{ card.detail }}</p>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2">
            <section class="rounded-xl border bg-card p-5">
                <h2 class="text-sm font-semibold">Needs attention</h2>
                <div class="mt-2 divide-y">
                    <Link
                        v-for="item in attentionItems"
                        :key="item.label"
                        :href="item.href"
                        class="-mx-2 flex items-center gap-3 rounded-md px-2 py-2.5 hover:bg-muted/50"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold tabular-nums"
                            :class="{
                                'bg-destructive/10 text-destructive':
                                    item.count > 0,
                            }"
                            >{{ item.count }}</span
                        >
                        <span class="min-w-0 flex-1 text-sm">{{
                            item.label
                        }}</span>
                        <span
                            class="hidden text-xs text-muted-foreground lg:inline"
                            >{{ item.action }}</span
                        >
                        <ArrowRight
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                    </Link>
                </div>
            </section>

            <section class="rounded-xl border bg-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold">Next seven days</h2>
                    <Button size="sm" variant="ghost" as-child
                        ><Link
                            :href="upcomingActionsIndex({ query: { days: 7 } })"
                            >View all</Link
                        ></Button
                    >
                </div>
                <div v-if="upcoming_actions.length" class="mt-2 divide-y">
                    <Link
                        v-for="action in upcoming_actions"
                        :key="`${action.kind}-${action.id}`"
                        :href="actionUrl(action)"
                        class="-mx-2 block rounded-md px-2 py-2.5 hover:bg-muted/50"
                    >
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="truncate text-sm font-medium">
                                {{ action.title }}
                            </p>
                            <time
                                class="shrink-0 text-xs"
                                :class="
                                    action.is_overdue
                                        ? 'text-destructive'
                                        : 'text-muted-foreground'
                                "
                                >{{ formatActionDate(action) }}</time
                            >
                        </div>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ action.application.role_title }} at
                            {{ action.application.company.name }}
                        </p>
                    </Link>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    No upcoming actions.
                </p>
            </section>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <section class="rounded-xl border bg-card p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-semibold">Pipeline funnel</h2>
                    <span class="text-xs text-muted-foreground"
                        >Stage reached, all time</span
                    >
                </div>
                <div class="mt-4 space-y-3">
                    <div v-for="stage in funnel" :key="stage.stage">
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ titleCase(stage.stage) }}</span>
                            <span class="text-muted-foreground tabular-nums"
                                >{{ stage.count }} ·
                                {{ stage.conversion }}%</span
                            >
                        </div>
                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="stageAccents[stage.stage]"
                                :style="{
                                    width: `${Math.max(stage.conversion, stage.count ? 3 : 0)}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border bg-card p-5">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-semibold">
                        Time to first response
                    </h2>
                    <span class="text-xs text-muted-foreground"
                        >{{ response_time.awaiting }} still awaiting</span
                    >
                </div>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-semibold tabular-nums">{{
                        response_time.median_days === null
                            ? '—'
                            : `${response_time.median_days} days`
                    }}</span>
                    <span class="text-xs text-muted-foreground">
                        median<template
                            v-if="response_time.quartile_low !== null"
                            >, middle 50% {{ response_time.quartile_low }}–{{
                                response_time.quartile_high
                            }}d</template
                        >
                    </span>
                </p>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="(bucket, index) in response_time.buckets"
                        :key="bucket.label"
                        class="grid grid-cols-[4.5rem_1fr_2.5rem] items-center gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">{{
                            bucket.label
                        }}</span>
                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="bucketAccents[index]"
                                :style="{
                                    width: `${(bucket.count / maxResponseBucket) * 100}%`,
                                }"
                            />
                        </div>
                        <span class="text-right tabular-nums"
                            >{{ responseShare(bucket.count) }}%</span
                        >
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-xl border bg-card p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-sm font-semibold">Applications by week</h2>
                <span class="text-xs text-muted-foreground">Last 8 weeks</span>
            </div>
            <div
                class="mt-4 flex h-32 items-end gap-2"
                aria-label="Applications submitted by week"
            >
                <div
                    v-for="week in activity"
                    :key="week.week"
                    class="flex h-full flex-1 flex-col justify-end gap-1.5"
                >
                    <div class="text-center text-xs tabular-nums">
                        {{ week.applications }}
                    </div>
                    <div
                        class="min-h-1 rounded-t bg-status-applied"
                        :style="{
                            height: `${(week.applications / maxActivity) * 100}%`,
                        }"
                        :title="`${week.applications} applications, ${week.interviews} interviews`"
                    />
                    <div
                        class="truncate text-center text-[10px] text-muted-foreground"
                    >
                        {{ week.label }}
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border bg-card">
            <h2 class="p-5 pb-3 text-sm font-semibold">Source performance</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-y bg-muted/40 text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Source</th>
                            <th class="px-4 py-2.5 font-medium">Apps</th>
                            <th class="px-4 py-2.5 font-medium">Response</th>
                            <th class="px-4 py-2.5 font-medium">Interview</th>
                            <th class="px-4 py-2.5 font-medium">Offer</th>
                            <th class="px-4 py-2.5 font-medium">
                                Median response
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="source in sources" :key="source.source">
                            <td class="px-5 py-2.5 font-medium">
                                {{ source.source }}
                            </td>
                            <td class="px-4 py-2.5 tabular-nums">
                                {{ source.applications }}
                            </td>
                            <td class="px-4 py-2.5 tabular-nums">
                                {{ source.response_rate }}%
                            </td>
                            <td class="px-4 py-2.5 tabular-nums">
                                {{ source.interview_rate }}%
                            </td>
                            <td class="px-4 py-2.5 tabular-nums">
                                {{ source.offer_rate }}%
                            </td>
                            <td class="px-4 py-2.5 tabular-nums">
                                {{
                                    source.median_response_days === null
                                        ? '—'
                                        : `${source.median_response_days} days`
                                }}
                            </td>
                        </tr>
                        <tr v-if="!sources.length">
                            <td
                                colspan="6"
                                class="px-5 py-8 text-center text-muted-foreground"
                            >
                                Source performance appears after applications
                                are submitted.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

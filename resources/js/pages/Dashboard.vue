<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BriefcaseBusiness,
    CalendarClock,
    Clock3,
    TrendingUp,
} from '@lucide/vue';
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
        value: props.summary.active_applications,
        detail: 'Across your active pipeline',
        icon: BriefcaseBusiness,
    },
    {
        label: 'Applications this week',
        value: props.summary.applications_this_week,
        detail: `${props.summary.applications_week_change >= 0 ? '+' : ''}${props.summary.applications_week_change} from last week`,
        icon: TrendingUp,
    },
    {
        label: 'Interview rate',
        value: `${props.summary.interview_rate}%`,
        detail: 'Of applications submitted',
        icon: CalendarClock,
    },
    {
        label: 'Median first response',
        value:
            props.summary.median_response_days === null
                ? '—'
                : `${props.summary.median_response_days}d`,
        detail: 'Less affected by outliers',
        icon: Clock3,
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
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold">Job search dashboard</h1>
            <p class="text-sm text-muted-foreground">
                What needs attention and where your search is gaining traction.
            </p>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in summaryCards"
                :key="card.label"
                class="rounded-xl border p-5"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        {{ card.label }}
                    </p>
                    <component
                        :is="card.icon"
                        class="size-4 text-muted-foreground"
                    />
                </div>
                <p class="mt-3 text-3xl font-semibold">{{ card.value }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ card.detail }}
                </p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-xl border p-5">
                <h2 class="font-semibold">Needs attention</h2>
                <p class="text-sm text-muted-foreground">
                    Turn insight into a next action.
                </p>
                <div class="mt-4 divide-y">
                    <Link
                        v-for="item in attentionItems"
                        :key="item.label"
                        :href="item.href"
                        class="flex items-center gap-3 py-3 hover:bg-muted/20"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold"
                            :class="{
                                'bg-destructive/10 text-destructive':
                                    item.count > 0,
                            }"
                            >{{ item.count }}</span
                        >
                        <span class="min-w-0 flex-1 text-sm">{{
                            item.label
                        }}</span
                        ><span class="text-xs text-muted-foreground">{{
                            item.action
                        }}</span
                        ><ArrowRight class="size-4 text-muted-foreground" />
                    </Link>
                </div>
            </section>
            <section class="rounded-xl border p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Next seven days</h2>
                        <p class="text-sm text-muted-foreground">
                            Tasks and interviews.
                        </p>
                    </div>
                    <Button size="sm" variant="outline" as-child
                        ><Link
                            :href="upcomingActionsIndex({ query: { days: 7 } })"
                            >View all</Link
                        ></Button
                    >
                </div>
                <div v-if="upcoming_actions.length" class="mt-4 divide-y">
                    <Link
                        v-for="action in upcoming_actions"
                        :key="`${action.kind}-${action.id}`"
                        :href="actionUrl(action)"
                        class="block py-3"
                    >
                        <div class="flex items-center justify-between gap-3">
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
                        <p class="mt-1 truncate text-xs text-muted-foreground">
                            {{ action.application.role_title }} at
                            {{ action.application.company.name }}
                        </p>
                    </Link>
                </div>
                <p v-else class="mt-6 text-sm text-muted-foreground">
                    No upcoming actions.
                </p>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border p-5">
                <h2 class="font-semibold">Pipeline funnel</h2>
                <p class="text-sm text-muted-foreground">
                    Historical stage reach and conversion.
                </p>
                <div class="mt-5 space-y-4">
                    <div v-for="stage in funnel" :key="stage.stage">
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ titleCase(stage.stage) }}</span
                            ><span class="text-muted-foreground"
                                >{{ stage.count }} ·
                                {{ stage.conversion }}%</span
                            >
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${Math.max(stage.conversion, stage.count ? 3 : 0)}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </section>
            <section class="rounded-xl border p-5">
                <h2 class="font-semibold">Applications by week</h2>
                <p class="text-sm text-muted-foreground">
                    Eight weeks of application momentum.
                </p>
                <div
                    class="mt-5 flex h-48 items-end gap-3"
                    aria-label="Applications submitted by week"
                >
                    <div
                        v-for="week in activity"
                        :key="week.week"
                        class="flex h-full flex-1 flex-col justify-end gap-2"
                    >
                        <div class="text-center text-xs font-medium">
                            {{ week.applications }}
                        </div>
                        <div
                            class="min-h-1 rounded-t bg-primary"
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
        </div>

        <section class="rounded-xl border p-5">
            <div class="grid gap-6 lg:grid-cols-[0.35fr_0.65fr]">
                <div>
                    <h2 class="font-semibold">Time to first response</h2>
                    <p class="mt-4 text-4xl font-semibold">
                        {{
                            response_time.median_days === null
                                ? '—'
                                : `${response_time.median_days} days`
                        }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Median response time
                    </p>
                    <p class="mt-4 text-sm">
                        <span class="font-medium">{{
                            response_time.awaiting
                        }}</span>
                        still awaiting a response
                    </p>
                    <p
                        v-if="response_time.quartile_low !== null"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Middle 50%: {{ response_time.quartile_low }}–{{
                            response_time.quartile_high
                        }}
                        days
                    </p>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="bucket in response_time.buckets"
                        :key="bucket.label"
                        class="grid grid-cols-[5rem_1fr_3rem] items-center gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">{{
                            bucket.label
                        }}</span>
                        <div class="h-3 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${(bucket.count / maxResponseBucket) * 100}%`,
                                }"
                            />
                        </div>
                        <span class="text-right"
                            >{{
                                response_time.total_responses
                                    ? Math.round(
                                          (bucket.count /
                                              response_time.total_responses) *
                                              100,
                                      )
                                    : 0
                            }}%</span
                        >
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border">
            <div class="p-5">
                <h2 class="font-semibold">Source performance</h2>
                <p class="text-sm text-muted-foreground">
                    Where applications produce meaningful progress.
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-y bg-muted/40 text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-5 py-3">Source</th>
                            <th class="px-4 py-3">Applications</th>
                            <th class="px-4 py-3">Response</th>
                            <th class="px-4 py-3">Interview</th>
                            <th class="px-4 py-3">Offer</th>
                            <th class="px-4 py-3">Median response</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="source in sources" :key="source.source">
                            <td class="px-5 py-3 font-medium">
                                {{ source.source }}
                            </td>
                            <td class="px-4 py-3">{{ source.applications }}</td>
                            <td class="px-4 py-3">
                                {{ source.response_rate }}%
                            </td>
                            <td class="px-4 py-3">
                                {{ source.interview_rate }}%
                            </td>
                            <td class="px-4 py-3">{{ source.offer_rate }}%</td>
                            <td class="px-4 py-3">
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

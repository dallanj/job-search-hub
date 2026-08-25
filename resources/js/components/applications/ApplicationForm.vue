<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/applications';
import type { CompanyOption, JobApplication, StatusOption } from '@/types';

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'patch';
        companies: CompanyOption[];
        statuses: StatusOption[];
        application?: JobApplication;
        submitLabel: string;
    }>(),
    {
        method: 'post',
        application: undefined,
    },
);

const dateValue = (value: string | null | undefined): string =>
    value?.slice(0, 10) ?? '';
</script>

<template>
    <Form
        :action="props.action"
        :method="props.method"
        class="space-y-8"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="role_title">Role title</Label>
                <Input
                    id="role_title"
                    name="role_title"
                    :default-value="application?.role_title"
                    required
                    autofocus
                />
                <InputError :message="errors.role_title" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Pipeline stage</Label>
                <select
                    id="status"
                    name="status"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    :value="application?.status ?? 'saved'"
                    required
                >
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-2">
                <Label for="company_id">Existing company</Label>
                <select
                    id="company_id"
                    name="company_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    :value="application?.company_id ?? ''"
                >
                    <option value="">Select a company</option>
                    <option
                        v-for="company in companies"
                        :key="company.id"
                        :value="company.id"
                    >
                        {{ company.name }}
                    </option>
                </select>
                <InputError :message="errors.company_id" />
            </div>

            <div class="grid gap-2">
                <Label for="company_name">Or create a company</Label>
                <Input
                    id="company_name"
                    name="company_name"
                    placeholder="Company name"
                />
                <InputError :message="errors.company_name" />
            </div>

            <div class="grid gap-2">
                <Label for="employment_type">Employment type</Label>
                <select
                    id="employment_type"
                    name="employment_type"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    :value="application?.employment_type ?? ''"
                >
                    <option value="">Not specified</option>
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contract">Contract</option>
                    <option value="temporary">Temporary</option>
                    <option value="internship">Internship</option>
                </select>
                <InputError :message="errors.employment_type" />
            </div>

            <div class="grid gap-2">
                <Label for="workplace_type">Workplace type</Label>
                <select
                    id="workplace_type"
                    name="workplace_type"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    :value="application?.workplace_type ?? ''"
                >
                    <option value="">Not specified</option>
                    <option value="remote">Remote</option>
                    <option value="hybrid">Hybrid</option>
                    <option value="on-site">On-site</option>
                </select>
                <InputError :message="errors.workplace_type" />
            </div>

            <div class="grid gap-2">
                <Label for="location">Location</Label>
                <Input
                    id="location"
                    name="location"
                    :default-value="application?.location ?? undefined"
                />
                <InputError :message="errors.location" />
            </div>

            <div class="grid gap-2">
                <Label for="source">Source</Label>
                <Input
                    id="source"
                    name="source"
                    :default-value="application?.source ?? undefined"
                    placeholder="LinkedIn, referral, company website…"
                />
                <InputError :message="errors.source" />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="job_url">Job URL</Label>
                <Input
                    id="job_url"
                    name="job_url"
                    type="url"
                    :default-value="application?.job_url ?? undefined"
                />
                <InputError :message="errors.job_url" />
            </div>

            <div class="grid gap-2">
                <Label for="salary_min">Minimum salary</Label>
                <Input
                    id="salary_min"
                    name="salary_min"
                    type="number"
                    min="0"
                    :default-value="application?.salary_min ?? undefined"
                />
                <InputError :message="errors.salary_min" />
            </div>

            <div class="grid gap-2">
                <Label for="salary_max">Maximum salary</Label>
                <Input
                    id="salary_max"
                    name="salary_max"
                    type="number"
                    min="0"
                    :default-value="application?.salary_max ?? undefined"
                />
                <InputError :message="errors.salary_max" />
            </div>

            <div class="grid gap-2">
                <Label for="salary_currency">Currency</Label>
                <Input
                    id="salary_currency"
                    name="salary_currency"
                    maxlength="3"
                    :default-value="application?.salary_currency ?? 'CAD'"
                />
                <InputError :message="errors.salary_currency" />
            </div>

            <div class="grid gap-2">
                <Label for="applied_at">Applied date</Label>
                <Input
                    id="applied_at"
                    name="applied_at"
                    type="date"
                    :default-value="dateValue(application?.applied_at)"
                />
                <InputError :message="errors.applied_at" />
            </div>

            <div class="grid gap-2">
                <Label for="closed_at">Closed date</Label>
                <Input
                    id="closed_at"
                    name="closed_at"
                    type="date"
                    :default-value="dateValue(application?.closed_at)"
                />
                <InputError :message="errors.closed_at" />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="description">Description and notes</Label>
                <textarea
                    id="description"
                    name="description"
                    rows="8"
                    class="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    :value="application?.description ?? ''"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <Button :disabled="processing">
                {{ processing ? 'Saving…' : submitLabel }}
            </Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>

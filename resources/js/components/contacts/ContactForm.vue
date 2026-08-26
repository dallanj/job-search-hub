<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/contacts';
import type { CompanyOption, Contact } from '@/types';

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'patch';
        companies: CompanyOption[];
        contact?: Contact;
        submitLabel: string;
    }>(),
    {
        method: 'post',
        contact: undefined,
    },
);
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
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="contact?.name"
                    required
                    autofocus
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="company_id">Existing company</Label>
                <select
                    id="company_id"
                    name="company_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    :value="contact?.company_id ?? ''"
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
                <Label for="job_title">Job title</Label>
                <Input
                    id="job_title"
                    name="job_title"
                    :default-value="contact?.job_title ?? undefined"
                />
                <InputError :message="errors.job_title" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    :default-value="contact?.email ?? undefined"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    :default-value="contact?.phone ?? undefined"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="linkedin_url">LinkedIn URL</Label>
                <Input
                    id="linkedin_url"
                    name="linkedin_url"
                    type="url"
                    :default-value="contact?.linkedin_url ?? undefined"
                    placeholder="https://www.linkedin.com/in/..."
                />
                <InputError :message="errors.linkedin_url" />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="notes">Notes</Label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="7"
                    class="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    :value="contact?.notes ?? ''"
                    placeholder="How you met, conversation context, or follow-up details…"
                />
                <InputError :message="errors.notes" />
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

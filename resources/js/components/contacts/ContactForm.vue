<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    InertiaFormProvider,
    Input as FormInput,
    Select as FormSelect,
    Textarea as FormTextarea,
} from '@/components/forms';
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
        v-slot="{ errors, processing, clearErrors }"
    >
        <InertiaFormProvider :errors="errors" :clear-errors="clearErrors">
            <div class="grid gap-6 md:grid-cols-2">
                <FormInput
                    id="name"
                    name="name"
                    label="Name"
                    :default-value="contact?.name"
                    required
                    autofocus
                />

                <FormSelect
                    id="company_id"
                    name="company_id"
                    label="Existing company"
                    placeholder="Select a company"
                    :default-value="contact?.company_id ?? ''"
                    :options="companies"
                    label-key="name"
                    value-key="id"
                    searchable
                />

                <FormInput
                    id="company_name"
                    name="company_name"
                    label="Or create a company"
                    placeholder="Company name"
                />

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

                <FormTextarea
                    id="notes"
                    name="notes"
                    class="md:col-span-2"
                    label="Notes"
                    rows="7"
                    :default-value="contact?.notes ?? ''"
                    placeholder="How you met, conversation context, or follow-up details…"
                />
            </div>

            <div class="flex items-center gap-3">
                <Button :disabled="processing">
                    {{ processing ? 'Saving…' : submitLabel }}
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="index()">Cancel</Link>
                </Button>
            </div>
        </InertiaFormProvider>
    </Form>
</template>

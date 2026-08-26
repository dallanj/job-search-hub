<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/interviews';
import type {
    Interview,
    InterviewApplication,
    InterviewContact,
    InterviewOption,
} from '@/types';

const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'patch';
        applications: InterviewApplication[];
        contacts: InterviewContact[];
        types: InterviewOption[];
        outcomes: InterviewOption[];
        interview?: Interview;
        selectedApplicationId?: number | null;
        submitLabel: string;
    }>(),
    { method: 'post', interview: undefined, selectedApplicationId: null },
);

const applicationId = ref(
    props.interview?.job_application_id ?? props.selectedApplicationId ?? '',
);
const contactId = ref<number | ''>(props.interview?.contact_id ?? '');
const selectedApplication = computed(() =>
    props.applications.find((item) => item.id === Number(applicationId.value)),
);
const availableContacts = computed(() =>
    props.contacts.filter(
        (contact) =>
            contact.company_id === selectedApplication.value?.company_id,
    ),
);

watch(applicationId, () => {
    if (
        contactId.value !== '' &&
        !availableContacts.value.some(
            (contact) => contact.id === Number(contactId.value),
        )
    ) {
        contactId.value = '';
    }
});
</script>

<template>
    <Form
        :action="action"
        :method="method"
        class="space-y-8"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2 md:col-span-2">
                <Label for="job_application_id">Application</Label>
                <select
                    id="job_application_id"
                    name="job_application_id"
                    v-model="applicationId"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    required
                >
                    <option value="" disabled>Select an application</option>
                    <option
                        v-for="application in applications"
                        :key="application.id"
                        :value="application.id"
                    >
                        {{ application.role_title }} —
                        {{ application.company.name }}
                    </option></select
                ><InputError :message="errors.job_application_id" />
            </div>
            <div class="grid gap-2">
                <Label for="type">Interview type</Label>
                <select
                    id="type"
                    name="type"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    :value="interview?.type ?? 'video'"
                    required
                >
                    <option
                        v-for="type in types"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </option></select
                ><InputError :message="errors.type" />
            </div>
            <div class="grid gap-2">
                <Label for="scheduled_at">Scheduled for</Label
                ><Input
                    id="scheduled_at"
                    name="scheduled_at"
                    type="datetime-local"
                    :default-value="interview?.scheduled_at.slice(0, 16)"
                    required
                /><InputError :message="errors.scheduled_at" />
            </div>
            <div class="grid gap-2">
                <Label for="contact_id">Interviewer</Label>
                <select
                    id="contact_id"
                    name="contact_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    v-model="contactId"
                >
                    <option value="">
                        {{
                            selectedApplication
                                ? 'Not known yet'
                                : 'Select an application first'
                        }}
                    </option>
                    <option
                        v-for="contact in availableContacts"
                        :key="contact.id"
                        :value="contact.id"
                    >
                        {{ contact.name
                        }}{{
                            contact.job_title ? ` — ${contact.job_title}` : ''
                        }}
                    </option>
                    <option
                        v-if="selectedApplication && !availableContacts.length"
                        value=""
                        disabled
                    >
                        No contacts for this company
                    </option></select
                ><InputError :message="errors.contact_id" />
            </div>
            <div class="grid gap-2">
                <Label for="duration_minutes">Duration (minutes)</Label
                ><Input
                    id="duration_minutes"
                    name="duration_minutes"
                    type="number"
                    min="1"
                    max="1440"
                    :default-value="interview?.duration_minutes ?? 60"
                /><InputError :message="errors.duration_minutes" />
            </div>
            <div class="grid gap-2">
                <Label for="location_or_url">Location or meeting URL</Label
                ><Input
                    id="location_or_url"
                    name="location_or_url"
                    :default-value="interview?.location_or_url ?? undefined"
                /><InputError :message="errors.location_or_url" />
            </div>
            <div class="grid gap-2">
                <Label for="outcome">Outcome</Label
                ><select
                    id="outcome"
                    name="outcome"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    :value="interview?.outcome ?? 'pending'"
                >
                    <option
                        v-for="outcome in outcomes"
                        :key="outcome.value"
                        :value="outcome.value"
                    >
                        {{ outcome.label }}
                    </option></select
                ><InputError :message="errors.outcome" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="notes">Notes</Label
                ><textarea
                    id="notes"
                    name="notes"
                    rows="7"
                    class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    :value="interview?.notes ?? ''"
                /><InputError :message="errors.notes" />
            </div>
        </div>
        <div class="flex gap-3">
            <Button :disabled="processing">{{
                processing ? 'Saving…' : submitLabel
            }}</Button
            ><Button variant="outline" as-child
                ><Link :href="index()">Cancel</Link></Button
            >
        </div>
    </Form>
</template>

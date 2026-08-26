<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InterviewForm from '@/components/interviews/InterviewForm.vue';
import { index, update } from '@/routes/interviews';
import type {
    Interview,
    InterviewApplication,
    InterviewContact,
    InterviewOption,
} from '@/types';
defineProps<{
    interview: Interview;
    applications: InterviewApplication[];
    contacts: InterviewContact[];
    types: InterviewOption[];
    outcomes: InterviewOption[];
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interviews', href: index() },
            { title: 'Edit interview', href: index() },
        ],
    },
});
</script>
<template>
    <Head title="Edit interview" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            title="Edit interview"
            description="Update schedule, interviewer, outcome, or notes."
        /><InterviewForm
            :action="update.url(interview)"
            method="patch"
            :interview="interview"
            :applications="applications"
            :contacts="contacts"
            :types="types"
            :outcomes="outcomes"
            submit-label="Save changes"
        />
    </div>
</template>

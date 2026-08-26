<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InterviewForm from '@/components/interviews/InterviewForm.vue';
import { index, store } from '@/routes/interviews';
import type {
    InterviewApplication,
    InterviewContact,
    InterviewOption,
} from '@/types';
defineProps<{
    applications: InterviewApplication[];
    contacts: InterviewContact[];
    types: InterviewOption[];
    outcomes: InterviewOption[];
    selectedApplicationId: number | null;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interviews', href: index() },
            { title: 'Schedule interview', href: index() },
        ],
    },
});
</script>
<template>
    <Head title="Schedule interview" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            title="Schedule interview"
            description="Add an interview to an active application."
        /><InterviewForm
            :action="store.url()"
            :applications="applications"
            :contacts="contacts"
            :types="types"
            :outcomes="outcomes"
            :selected-application-id="selectedApplicationId"
            submit-label="Schedule interview"
        />
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ApplicationForm from '@/components/applications/ApplicationForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index, store } from '@/routes/applications';
import type { CompanyOption, StatusOption } from '@/types';

defineProps<{
    companies: CompanyOption[];
    statuses: StatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: index() },
            { title: 'New application', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New application" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
        <Heading
            title="New application"
            description="Add an opportunity to your job-search pipeline."
        />
        <ApplicationForm
            :action="store.url()"
            :companies="companies"
            :statuses="statuses"
            submit-label="Create application"
        />
    </div>
</template>

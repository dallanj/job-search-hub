<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ApplicationForm from '@/components/applications/ApplicationForm.vue';
import Heading from '@/components/Heading.vue';
import { index, update } from '@/routes/applications';
import type { CompanyOption, JobApplication, StatusOption } from '@/types';

const props = defineProps<{
    application: JobApplication;
    companies: CompanyOption[];
    statuses: StatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: index() },
            { title: 'Edit application', href: index() },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${application.role_title}`" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
        <Heading
            title="Edit application"
            :description="`${application.role_title} at ${application.company.name}`"
        />
        <ApplicationForm
            :action="update.url(props.application)"
            method="patch"
            :application="application"
            :companies="companies"
            :statuses="statuses"
            submit-label="Save changes"
        />
    </div>
</template>

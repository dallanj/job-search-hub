<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ContactForm from '@/components/contacts/ContactForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index, store } from '@/routes/contacts';
import type { CompanyOption } from '@/types';

defineProps<{ companies: CompanyOption[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: 'New contact', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New contact" />

    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            title="New contact"
            description="Add someone connected to a company in your job search."
        />
        <ContactForm
            :action="store.url()"
            :companies="companies"
            submit-label="Create contact"
        />
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ContactForm from '@/components/contacts/ContactForm.vue';
import Heading from '@/components/Heading.vue';
import { index, update } from '@/routes/contacts';
import type { CompanyOption, Contact } from '@/types';

defineProps<{
    contact: Contact;
    companies: CompanyOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: 'Edit contact', href: index() },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${contact.name}`" />

    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            :title="`Edit ${contact.name}`"
            description="Update contact and company information."
        />
        <ContactForm
            :action="update.url(contact)"
            method="patch"
            :companies="companies"
            :contact="contact"
            submit-label="Save changes"
        />
    </div>
</template>

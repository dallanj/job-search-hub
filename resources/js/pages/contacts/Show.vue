<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Mail, Pencil, Phone, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/contacts';
import type { Contact } from '@/types';

defineProps<{ contact: Contact }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: 'Contact details', href: index() },
        ],
    },
});

const confirmDelete = (): boolean => window.confirm('Delete this contact?');
</script>

<template>
    <Head :title="contact.name" />

    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ contact.name }}
                </h1>
                <p class="text-muted-foreground">
                    <span v-if="contact.job_title">
                        {{ contact.job_title }} at
                    </span>
                    {{ contact.company.name }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(contact)">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>
                <Form
                    :action="destroy.url(contact)"
                    method="delete"
                    v-slot="{ processing }"
                    @before="confirmDelete"
                >
                    <Button variant="destructive" :disabled="processing">
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </Form>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2">
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Email
                </p>
                <a
                    v-if="contact.email"
                    :href="`mailto:${contact.email}`"
                    class="mt-1 inline-flex items-center gap-2 text-sm hover:underline"
                >
                    <Mail class="size-4" />
                    {{ contact.email }}
                </a>
                <p v-else class="mt-1 text-sm">Not specified</p>
            </div>
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Phone
                </p>
                <a
                    v-if="contact.phone"
                    :href="`tel:${contact.phone}`"
                    class="mt-1 inline-flex items-center gap-2 text-sm hover:underline"
                >
                    <Phone class="size-4" />
                    {{ contact.phone }}
                </a>
                <p v-else class="mt-1 text-sm">Not specified</p>
            </div>
        </div>

        <section v-if="contact.notes" class="rounded-xl border p-5">
            <h2 class="font-medium">Notes</h2>
            <p class="mt-3 text-sm whitespace-pre-wrap text-muted-foreground">
                {{ contact.notes }}
            </p>
        </section>

        <div class="flex flex-wrap gap-3">
            <Button v-if="contact.linkedin_url" variant="outline" as-child>
                <a
                    :href="contact.linkedin_url"
                    target="_blank"
                    rel="noreferrer"
                >
                    LinkedIn profile
                    <ExternalLink class="size-4" />
                </a>
            </Button>
            <Button v-if="contact.company.website" variant="ghost" as-child>
                <a
                    :href="contact.company.website"
                    target="_blank"
                    rel="noreferrer"
                >
                    Company website
                    <ExternalLink class="size-4" />
                </a>
            </Button>
        </div>
    </div>
</template>

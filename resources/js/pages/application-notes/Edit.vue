<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { update } from '@/routes/application-notes';
import { show as applicationShow } from '@/routes/applications';
import type { ApplicationNote } from '@/types';

defineProps<{ note: ApplicationNote }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Edit note', href: '#' }] },
});
</script>

<template>
    <Head title="Edit application note" />
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
        <Heading
            title="Edit note"
            :description="`Update your note for ${note.job_application?.role_title}.`"
        />
        <Form
            :action="update.url(note)"
            method="patch"
            class="space-y-4"
            v-slot="{ errors, processing }"
        >
            <div>
                <textarea
                    id="body"
                    name="body"
                    rows="10"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    :value="note.body"
                    autofocus
                    required
                />
                <InputError :message="errors.body" />
            </div>
            <div class="flex gap-3">
                <Button :disabled="processing">
                    {{ processing ? 'Saving…' : 'Save changes' }}
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="applicationShow(note.job_application!)">
                        Cancel
                    </Link>
                </Button>
            </div>
        </Form>
    </div>
</template>

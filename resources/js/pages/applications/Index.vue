<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { storeToRefs } from 'pinia';
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/applications';
import { useApplicationsStore } from '@/stores/applications';
import { useOptionsStore } from '@/stores/options';

const store = useApplicationsStore();
const { applications } = storeToRefs(store);
const { applicationStatuses: statuses } = storeToRefs(useOptionsStore());
const query = new URL(usePage().url, 'http://localhost').searchParams;
const filters = reactive({
    search: query.get('search') ?? '',
    status: query.get('status') ?? '',
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: index() }],
    },
});

const paginationLabel = (label: string): string =>
    label.replace('&laquo;', '‹').replace('&raquo;', '›');

const statusLabel = (value: string): string =>
    statuses.value.find((status) => status.value === value)?.label ?? value;

const search = (): void => {
    router.get(index.url(), filters, {
        only: ['$pinia'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const searchAfterTyping = useDebounceFn(search, 300);

const updateSearch = (value: string | number): void => {
    filters.search = String(value);
    void searchAfterTyping();
};
</script>

<template>
    <Head title="Applications" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Applications
                </h1>
                <p class="text-sm text-muted-foreground">
                    Track every opportunity in one place.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <Plus class="size-4" />
                    New application
                </Link>
            </Button>
        </div>

        <Form
            :action="index.url()"
            method="get"
            class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row"
        >
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    :model-value="filters.search"
                    name="search"
                    class="pl-9"
                    placeholder="Search roles or companies"
                    @update:model-value="updateSearch"
                />
            </div>
            <select
                name="status"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                v-model="filters.status"
                @change="search"
            >
                <option value="">All stages</option>
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <Button type="submit" variant="secondary">Filter</Button>
            <Button
                v-if="filters.search || filters.status"
                variant="ghost"
                as-child
            >
                <Link :href="index()">Clear</Link>
            </Button>
        </Form>

        <div class="overflow-hidden rounded-xl border">
            <div v-if="applications.data.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Role</th>
                            <th class="px-4 py-3 font-medium">Company</th>
                            <th class="px-4 py-3 font-medium">Stage</th>
                            <th class="px-4 py-3 font-medium">Location</th>
                            <th class="px-4 py-3 font-medium">Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="application in applications.data"
                            :key="application.id"
                            class="border-b last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                <Link
                                    :href="show(application)"
                                    class="hover:underline"
                                >
                                    {{ application.role_title }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                {{ application.company.name }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium"
                                >
                                    {{ statusLabel(application.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ application.location || '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{
                                    application.applied_at?.slice(0, 10) || '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-12 text-center">
                <h2 class="font-medium">No applications found</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add your first application or adjust the current filters.
                </p>
            </div>
        </div>

        <div
            v-if="applications.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Showing {{ applications.from }}–{{ applications.to }} of
                {{ applications.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="link in applications.links" :key="link.label">
                    <Button
                        v-if="!link.url"
                        variant="outline"
                        size="sm"
                        disabled
                    >
                        {{ paginationLabel(link.label) }}
                    </Button>
                    <Button
                        v-else
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        as-child
                    >
                        <Link :href="link.url" preserve-scroll>
                            {{ paginationLabel(link.label) }}
                        </Link>
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>

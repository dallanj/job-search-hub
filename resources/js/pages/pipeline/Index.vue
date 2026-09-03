<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Filter, List, Plus, Search, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { storeToRefs } from 'pinia';
import { computed, reactive, ref } from 'vue';
import KanbanColumn from '@/components/pipeline/KanbanColumn.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index as applicationsIndex } from '@/routes/applications';
import { index, move } from '@/routes/pipeline';
import { useOptionsStore } from '@/stores/options';
import { usePipelineStore } from '@/stores/pipeline';
import type {
    ApplicationStatus,
    PipelineApplication,
    PipelineColumn,
    PipelineFilters,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pipeline', href: index() }],
    },
});

const cloneColumns = (columns: PipelineColumn[]): PipelineColumn[] =>
    columns.map((column) => ({
        ...column,
        applications: column.applications.map((application) => ({
            ...application,
            company: { ...application.company },
        })),
    }));

const pipeline = usePipelineStore();
const { columns: board, matchingApplicationCount } = storeToRefs(pipeline);
const { companies } = storeToRefs(useOptionsStore());

const draggedApplicationId = ref<number | null>(null);
const processing = ref(false);
const query = new URL(usePage().url, 'http://localhost').searchParams;
const filterValues = reactive({
    search: query.get('search') ?? '',
    company_id: query.get('company_id') ?? '',
    location: query.get('location') ?? '',
    date_from: query.get('date_from') ?? '',
    date_to: query.get('date_to') ?? '',
});

const filters = computed<PipelineFilters>(() => ({
    search: filterValues.search.trim() || null,
    company_id: filterValues.company_id
        ? Number(filterValues.company_id)
        : null,
    location: filterValues.location.trim() || null,
    date_from: filterValues.date_from || null,
    date_to: filterValues.date_to || null,
}));

const activeFilters = computed<string[]>(() => {
    const labels: string[] = [];

    if (filters.value.search) {
        labels.push(`Search: ${filters.value.search}`);
    }

    if (filters.value.company_id) {
        const company = companies.value.find(
            ({ id }) => id === filters.value.company_id,
        );
        labels.push(`Company: ${company?.name ?? filters.value.company_id}`);
    }

    if (filters.value.location) {
        labels.push(`Location: ${filters.value.location}`);
    }

    if (filters.value.date_from) {
        labels.push(`From: ${filters.value.date_from}`);
    }

    if (filters.value.date_to) {
        labels.push(`To: ${filters.value.date_to}`);
    }

    return labels;
});

const hasActiveFilters = computed(() => activeFilters.value.length > 0);

const search = (): void => {
    router.get(index.url(), filters.value, {
        only: ['$pinia'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const searchAfterTyping = useDebounceFn(search, 300);

const updateSearch = (value: string | number): void => {
    filterValues.search = String(value);
    void searchAfterTyping();
};

const updateLocation = (value: string | number): void => {
    filterValues.location = String(value);
    void searchAfterTyping();
};

const findApplication = (
    applicationId: number,
): { columnIndex: number; applicationIndex: number } | null => {
    for (const [columnIndex, column] of board.value.entries()) {
        const applicationIndex = column.applications.findIndex(
            (application) => application.id === applicationId,
        );

        if (applicationIndex !== -1) {
            return { columnIndex, applicationIndex };
        }
    }

    return null;
};

const persistMove = (
    application: PipelineApplication,
    targetStatus: ApplicationStatus,
    targetPosition: number,
    snapshot: PipelineColumn[],
): void => {
    processing.value = true;

    router.patch(
        move.url(application),
        { status: targetStatus, position: targetPosition },
        {
            preserveScroll: true,
            onError: () => {
                board.value = snapshot;
            },
            onFinish: () => {
                processing.value = false;
                draggedApplicationId.value = null;
            },
        },
    );
};

const moveApplication = (
    applicationId: number,
    targetColumnIndex: number,
    targetPosition: number,
): void => {
    if (processing.value) {
        return;
    }

    const source = findApplication(applicationId);
    const targetColumn = board.value[targetColumnIndex];

    if (!source || !targetColumn) {
        return;
    }

    if (hasActiveFilters.value && source.columnIndex === targetColumnIndex) {
        draggedApplicationId.value = null;

        return;
    }

    const snapshot = cloneColumns(board.value);
    const sourceColumn = board.value[source.columnIndex];
    const [application] = sourceColumn.applications.splice(
        source.applicationIndex,
        1,
    );

    const position = Math.max(
        0,
        Math.min(targetPosition, targetColumn.applications.length),
    );

    if (
        source.columnIndex === targetColumnIndex &&
        source.applicationIndex === position
    ) {
        sourceColumn.applications.splice(
            source.applicationIndex,
            0,
            application,
        );
        draggedApplicationId.value = null;

        return;
    }

    application.status = targetColumn.status;
    targetColumn.applications.splice(position, 0, application);
    targetColumn.applications.forEach((item, index) => {
        item.sort_order = index;
    });
    sourceColumn.applications.forEach((item, index) => {
        item.sort_order = index;
    });

    persistMove(
        application,
        targetColumn.status,
        hasActiveFilters.value ? Number.MAX_SAFE_INTEGER : position,
        snapshot,
    );
};

const dropAt = (targetColumnIndex: number, targetPosition: number): void => {
    if (draggedApplicationId.value === null) {
        return;
    }

    const source = findApplication(draggedApplicationId.value);
    const adjustedPosition =
        source?.columnIndex === targetColumnIndex &&
        source.applicationIndex < targetPosition
            ? targetPosition - 1
            : targetPosition;

    moveApplication(
        draggedApplicationId.value,
        targetColumnIndex,
        adjustedPosition,
    );
};

const moveUp = (applicationId: number): void => {
    const source = findApplication(applicationId);

    if (source) {
        moveApplication(
            applicationId,
            source.columnIndex,
            source.applicationIndex - 1,
        );
    }
};

const moveDown = (applicationId: number): void => {
    const source = findApplication(applicationId);

    if (source) {
        moveApplication(
            applicationId,
            source.columnIndex,
            source.applicationIndex + 1,
        );
    }
};

const moveAcross = (applicationId: number, offset: -1 | 1): void => {
    const source = findApplication(applicationId);

    if (source) {
        const targetColumnIndex = source.columnIndex + offset;
        const targetPosition =
            board.value[targetColumnIndex]?.applications.length ?? 0;
        moveApplication(applicationId, targetColumnIndex, targetPosition);
    }
};
</script>

<template>
    <Head title="Application pipeline" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-5 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Application pipeline
                </h1>
                <p class="text-sm text-muted-foreground">
                    Drag applications between stages or use each card's move
                    controls.
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="applicationsIndex()">
                        <List class="size-4" />
                        List view
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">
                        <Plus class="size-4" />
                        New application
                    </Link>
                </Button>
            </div>
        </div>

        <Form
            :action="index.url()"
            method="get"
            class="grid gap-3 rounded-xl border p-4 sm:grid-cols-2 xl:grid-cols-[minmax(14rem,1.5fr)_minmax(11rem,1fr)_minmax(11rem,1fr)_minmax(9rem,0.8fr)_minmax(9rem,0.8fr)_auto]"
            v-slot="{ processing: filtering }"
        >
            <label class="grid gap-1.5 text-xs font-medium">
                Search
                <span class="relative">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        :model-value="filterValues.search"
                        name="search"
                        class="pl-9"
                        placeholder="Role or company"
                        @update:model-value="updateSearch"
                    />
                </span>
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Company
                <select
                    v-model="filterValues.company_id"
                    name="company_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm font-normal shadow-xs"
                    @change="search"
                >
                    <option value="">All companies</option>
                    <option
                        v-for="company in companies"
                        :key="company.id"
                        :value="company.id"
                    >
                        {{ company.name }}
                    </option>
                </select>
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Location
                <Input
                    :model-value="filterValues.location"
                    name="location"
                    placeholder="City or region"
                    @update:model-value="updateLocation"
                />
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Applied from
                <Input
                    v-model="filterValues.date_from"
                    name="date_from"
                    type="date"
                    @change="search"
                />
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Applied to
                <Input
                    v-model="filterValues.date_to"
                    name="date_to"
                    type="date"
                    @change="search"
                />
            </label>
            <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-1">
                <Button type="submit" variant="secondary" :disabled="filtering">
                    <Filter class="size-4" />
                    Filter
                </Button>
                <Button
                    v-if="hasActiveFilters"
                    type="button"
                    variant="ghost"
                    size="icon"
                    as-child
                >
                    <Link :href="index()" aria-label="Clear all filters">
                        <X class="size-4" />
                    </Link>
                </Button>
            </div>
        </Form>

        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-muted-foreground">Active filters:</span>
            <Badge
                v-for="filter in activeFilters"
                :key="filter"
                variant="secondary"
            >
                {{ filter }}
            </Badge>
            <Button variant="link" size="sm" class="h-auto px-1" as-child>
                <Link :href="index()">Clear all</Link>
            </Button>
            <span class="text-xs text-muted-foreground">
                Moves append to the target stage; clear filters to reorder.
            </span>
        </div>

        <div
            v-if="hasActiveFilters && matchingApplicationCount === 0"
            class="rounded-xl border border-dashed p-6 text-center"
        >
            <h2 class="font-medium">No matching applications</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Adjust the filters or clear them to see the full pipeline.
            </p>
        </div>

        <div
            class="flex min-h-0 flex-1 gap-4 overflow-x-auto pb-3"
            aria-label="Application pipeline"
        >
            <KanbanColumn
                v-for="(column, columnIndex) in board"
                :key="column.status"
                :column="column"
                :column-index="columnIndex"
                :column-count="board.length"
                :disabled="processing"
                :allow-reordering="!hasActiveFilters"
                @drag-start="draggedApplicationId = $event"
                @drop-at="dropAt(columnIndex, $event)"
                @move-up="moveUp"
                @move-down="moveDown"
                @move-previous="moveAcross($event, -1)"
                @move-next="moveAcross($event, 1)"
            />
        </div>
    </div>
</template>

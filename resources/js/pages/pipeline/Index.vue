<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Filter, List, Plus, Search, X } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import KanbanColumn from '@/components/pipeline/KanbanColumn.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index as applicationsIndex } from '@/routes/applications';
import { index, move } from '@/routes/pipeline';
import type {
    ApplicationStatus,
    CompanyOption,
    PipelineApplication,
    PipelineColumn,
    PipelineFilters,
} from '@/types';

const props = defineProps<{
    columns: PipelineColumn[];
    companies: CompanyOption[];
    filters: PipelineFilters;
}>();

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

const board = ref(cloneColumns(props.columns));
const draggedApplicationId = ref<number | null>(null);
const processing = ref(false);
const filterValues = reactive({
    search: props.filters.search ?? '',
    company_id: props.filters.company_id?.toString() ?? '',
    location: props.filters.location ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const activeFilters = computed(() => {
    const filters: string[] = [];

    if (props.filters.search) {
        filters.push(`Search: ${props.filters.search}`);
    }

    if (props.filters.company_id) {
        const company = props.companies.find(
            (company) => company.id === props.filters.company_id,
        );
        filters.push(`Company: ${company?.name ?? props.filters.company_id}`);
    }

    if (props.filters.location) {
        filters.push(`Location: ${props.filters.location}`);
    }

    if (props.filters.date_from) {
        filters.push(`From: ${props.filters.date_from}`);
    }

    if (props.filters.date_to) {
        filters.push(`To: ${props.filters.date_to}`);
    }

    return filters;
});

const hasActiveFilters = computed(() => activeFilters.value.length > 0);
const matchingApplicationCount = computed(() =>
    board.value.reduce(
        (count, column) => count + column.applications.length,
        0,
    ),
);

watch(
    () => props.columns,
    (columns) => {
        board.value = cloneColumns(columns);
    },
    { deep: true },
);

watch(
    () => props.filters,
    (filters) => {
        filterValues.search = filters.search ?? '';
        filterValues.company_id = filters.company_id?.toString() ?? '';
        filterValues.location = filters.location ?? '';
        filterValues.date_from = filters.date_from ?? '';
        filterValues.date_to = filters.date_to ?? '';
    },
    { deep: true },
);

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
                        v-model="filterValues.search"
                        name="search"
                        class="pl-9"
                        placeholder="Role or company"
                    />
                </span>
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Company
                <select
                    v-model="filterValues.company_id"
                    name="company_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm font-normal shadow-xs"
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
                    v-model="filterValues.location"
                    name="location"
                    placeholder="City or region"
                />
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Applied from
                <Input
                    v-model="filterValues.date_from"
                    name="date_from"
                    type="date"
                />
            </label>
            <label class="grid gap-1.5 text-xs font-medium">
                Applied to
                <Input
                    v-model="filterValues.date_to"
                    name="date_to"
                    type="date"
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

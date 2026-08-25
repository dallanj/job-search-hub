<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { List, Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import KanbanColumn from '@/components/pipeline/KanbanColumn.vue';
import { Button } from '@/components/ui/button';
import { create, index as applicationsIndex } from '@/routes/applications';
import { index, move } from '@/routes/pipeline';
import type {
    ApplicationStatus,
    PipelineApplication,
    PipelineColumn,
} from '@/types';

const props = defineProps<{ columns: PipelineColumn[] }>();

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

watch(
    () => props.columns,
    (columns) => {
        board.value = cloneColumns(columns);
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

    persistMove(application, targetColumn.status, position, snapshot);
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

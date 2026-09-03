import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { PipelineColumn } from '@/types';

export type PipelineStateKey = 'columns';

export const usePipelineStore = defineStore('pipeline', () => {
    const columns = ref<PipelineColumn[]>([]);
    const matchingApplicationCount = computed(() =>
        columns.value.reduce(
            (count, column) => count + column.applications.length,
            0,
        ),
    );

    function $reset(key?: PipelineStateKey): void {
        if (!key || key === 'columns') {
            columns.value = [];
        }
    }

    return {
        columns,
        matchingApplicationCount,
        $reset,
    };
});

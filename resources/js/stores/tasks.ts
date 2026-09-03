import { defineStore } from 'pinia';
import { ref } from 'vue';
import { emptyPaginated } from '@/stores/pagination';
import type { FollowUpTask, Paginated } from '@/types';

export type TasksStateKey = 'tasks';

export const useTasksStore = defineStore('tasks', () => {
    const tasks = ref<Paginated<FollowUpTask>>(emptyPaginated());

    function $reset(key?: TasksStateKey): void {
        if (!key || key === 'tasks') {
            tasks.value = emptyPaginated();
        }
    }

    return { tasks, $reset };
});

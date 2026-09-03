import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { CompanyOption, StatusOption, TaskPriorityOption } from '@/types';

export type OptionsStateKey =
    'companies' | 'applicationStatuses' | 'taskPriorities';

export const useOptionsStore = defineStore('options', () => {
    const companies = ref<CompanyOption[]>([]);
    const applicationStatuses = ref<StatusOption[]>([]);
    const taskPriorities = ref<TaskPriorityOption[]>([]);

    function $reset(key?: OptionsStateKey): void {
        if (!key || key === 'companies') {
            companies.value = [];
        }

        if (!key || key === 'applicationStatuses') {
            applicationStatuses.value = [];
        }

        if (!key || key === 'taskPriorities') {
            taskPriorities.value = [];
        }
    }

    return { companies, applicationStatuses, taskPriorities, $reset };
});

import { defineStore } from 'pinia';
import { ref } from 'vue';
import { emptyPaginated } from '@/stores/pagination';
import type { JobApplication, Paginated } from '@/types';

export type ApplicationsStateKey = 'applications';

export const useApplicationsStore = defineStore('applications', () => {
    const applications = ref<Paginated<JobApplication>>(emptyPaginated());

    function $reset(key?: ApplicationsStateKey): void {
        if (!key || key === 'applications') {
            applications.value = emptyPaginated();
        }
    }

    return { applications, $reset };
});

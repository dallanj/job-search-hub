import { createPiniaHydrator } from '@dallanj/pinia-hydrate';
import { createPinia } from 'pinia';
import type { Pinia } from 'pinia';
import { useApplicationsStore } from '@/stores/applications';
import type { ApplicationsStateKey } from '@/stores/applications';
import { useContactsStore } from '@/stores/contacts';
import type { ContactsStateKey } from '@/stores/contacts';
import { useOptionsStore } from '@/stores/options';
import type { OptionsStateKey } from '@/stores/options';
import { usePipelineStore } from '@/stores/pipeline';
import type { PipelineStateKey } from '@/stores/pipeline';
import { useTasksStore } from '@/stores/tasks';
import type { TasksStateKey } from '@/stores/tasks';

export const pinia = createPinia();

const useHydratablePipelineStore = (activePinia?: Pinia) => {
    const store = usePipelineStore(activePinia);

    if (store.$reset.length === 0) {
        const reset = store.$reset;
        store.$reset = (key?: PipelineStateKey): void => reset(key);
    }

    return store;
};

const useHydratableApplicationsStore = (activePinia?: Pinia) => {
    const store = useApplicationsStore(activePinia);

    if (store.$reset.length === 0) {
        const reset = store.$reset;
        store.$reset = (key?: ApplicationsStateKey): void => reset(key);
    }

    return store;
};

const useHydratableContactsStore = (activePinia?: Pinia) => {
    const store = useContactsStore(activePinia);

    if (store.$reset.length === 0) {
        const reset = store.$reset;
        store.$reset = (key?: ContactsStateKey): void => reset(key);
    }

    return store;
};

const useHydratableTasksStore = (activePinia?: Pinia) => {
    const store = useTasksStore(activePinia);

    if (store.$reset.length === 0) {
        const reset = store.$reset;
        store.$reset = (key?: TasksStateKey): void => reset(key);
    }

    return store;
};

const useHydratableOptionsStore = (activePinia?: Pinia) => {
    const store = useOptionsStore(activePinia);

    if (store.$reset.length === 0) {
        const reset = store.$reset;
        store.$reset = (key?: OptionsStateKey): void => reset(key);
    }

    return store;
};

export const hydratePinia = createPiniaHydrator({
    applications: useHydratableApplicationsStore,
    contacts: useHydratableContactsStore,
    options: useHydratableOptionsStore,
    pipeline: useHydratablePipelineStore,
    tasks: useHydratableTasksStore,
});

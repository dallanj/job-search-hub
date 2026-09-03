import { defineStore } from 'pinia';
import { ref } from 'vue';
import { emptyPaginated } from '@/stores/pagination';
import type { Contact, Paginated } from '@/types';

export type ContactsStateKey = 'contacts';

export const useContactsStore = defineStore('contacts', () => {
    const contacts = ref<Paginated<Contact>>(emptyPaginated());

    function $reset(key?: ContactsStateKey): void {
        if (!key || key === 'contacts') {
            contacts.value = emptyPaginated();
        }
    }

    return { contacts, $reset };
});

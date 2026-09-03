import type { Paginated } from '@/types';

export const emptyPaginated = <Item>(): Paginated<Item> => ({
    data: [],
    current_page: 1,
    last_page: 1,
    from: null,
    to: null,
    total: 0,
    links: [],
});

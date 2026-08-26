import type { CompanyOption } from './applications';

export type Contact = {
    id: number;
    company_id: number;
    name: string;
    job_title: string | null;
    email: string | null;
    phone: string | null;
    linkedin_url: string | null;
    notes: string | null;
    created_at: string;
    company: CompanyOption;
};

export type ContactFilters = {
    search: string | null;
    company_id: number | null;
};

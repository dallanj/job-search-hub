export type ApplicationStatus =
    | 'saved'
    | 'applied'
    | 'screening'
    | 'interview'
    | 'offer'
    | 'rejected'
    | 'withdrawn'
    | 'archived';

export type StatusOption = {
    value: ApplicationStatus;
    label: string;
};

export type CompanyOption = {
    id: number;
    name: string;
    website?: string | null;
};

export type JobApplication = {
    id: number;
    company_id: number;
    role_title: string;
    status: ApplicationStatus;
    location: string | null;
    employment_type: string | null;
    workplace_type: string | null;
    source: string | null;
    job_url: string | null;
    salary_min: number | null;
    salary_max: number | null;
    salary_currency: string | null;
    applied_at: string | null;
    closed_at: string | null;
    description: string | null;
    created_at: string;
    company: CompanyOption;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

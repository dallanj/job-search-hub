export type ApplicationStatus =
    | 'saved'
    | 'applied'
    | 'screening'
    | 'interview'
    | 'offer'
    | 'hired'
    | 'rejected'
    | 'no_response'
    | 'offer_declined'
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
    deadline: string | null;
    description: string | null;
    cv_file_path: string | null;
    cover_letter_file_path: string | null;
    created_at: string;
    company: CompanyOption;
    status_events?: ApplicationStatusEvent[];
    interviews?: Interview[];
    tasks?: FollowUpTask[];
    notes?: ApplicationNote[];
};

export type ApplicationNote = {
    id: number;
    job_application_id: number;
    user_id: number;
    body: string;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
    };
    job_application?: {
        id: number;
        company_id: number;
        role_title: string;
        company: CompanyOption;
    };
};

export type ApplicationStatusEvent = {
    id: number;
    from_status: ApplicationStatus | null;
    to_status: ApplicationStatus;
    changed_at: string;
    note: string | null;
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

export type PipelineApplication = {
    id: number;
    company_id: number;
    role_title: string;
    status: ApplicationStatus;
    sort_order: number;
    location: string | null;
    workplace_type: string | null;
    applied_at: string | null;
    company: CompanyOption;
};

export type PipelineColumn = {
    status: ApplicationStatus;
    label: string;
    applications: PipelineApplication[];
};

export type PipelineFilters = {
    search: string | null;
    company_id: number | null;
    location: string | null;
    date_from: string | null;
    date_to: string | null;
};
import type { Interview } from './interviews';
import type { FollowUpTask } from './tasks';

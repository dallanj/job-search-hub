import type { CompanyOption } from './applications';

export type InterviewType =
    | 'phone'
    | 'video'
    | 'onsite'
    | 'technical'
    | 'behavioral'
    | 'panel'
    | 'final';
export type InterviewOutcome =
    'pending' | 'passed' | 'failed' | 'cancelled' | 'rescheduled';
export type InterviewOption = { value: string; label: string };
export type InterviewApplication = {
    id: number;
    company_id: number;
    role_title: string;
    company: CompanyOption;
};
export type InterviewContact = {
    id: number;
    company_id?: number;
    name: string;
    job_title?: string | null;
    email?: string | null;
    phone?: string | null;
    linkedin_url?: string | null;
};
export type Interview = {
    id: number;
    job_application_id: number;
    contact_id: number | null;
    type: InterviewType;
    scheduled_at: string;
    duration_minutes: number | null;
    location_or_url: string | null;
    outcome: InterviewOutcome | null;
    notes: string | null;
    job_application: InterviewApplication;
    contact: InterviewContact | null;
};

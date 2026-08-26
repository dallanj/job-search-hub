import type { InterviewApplication } from './interviews';

export type TaskPriorityOption = {
    value: number;
    label: string;
};

export type TaskStatus = 'open' | 'overdue' | 'completed';

export type FollowUpTask = {
    id: number;
    job_application_id: number;
    title: string;
    due_at: string | null;
    completed_at: string | null;
    priority: number;
    job_application: InterviewApplication;
};

export type TaskFilters = {
    search: string | null;
    status: TaskStatus;
};

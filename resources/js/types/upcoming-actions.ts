import type { InterviewApplication } from './interviews';

export type UpcomingAction = {
    id: number;
    kind: 'task' | 'interview';
    title: string;
    scheduled_for: string;
    is_overdue: boolean;
    detail: string;
    application: InterviewApplication;
};

export type UpcomingActionFilters = {
    days: 7 | 14 | 30;
};

<?php

namespace App\Enums;

enum InterviewOutcome: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
}

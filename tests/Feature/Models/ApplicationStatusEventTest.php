<?php

use App\Enums\ApplicationStatus;
use App\Models\ApplicationStatusEvent;

test('statuses and transition time are cast to domain types', function () {
    $event = ApplicationStatusEvent::factory()->create([
        'from_status' => ApplicationStatus::Screening,
        'to_status' => ApplicationStatus::Interview,
        'changed_at' => '2026-08-25 12:30:00',
    ]);

    expect($event->from_status)->toBe(ApplicationStatus::Screening);
    expect($event->to_status)->toBe(ApplicationStatus::Interview);
    expect($event->changed_at->toDateTimeString())->toBe('2026-08-25 12:30:00');
});

test('an initial event supports a null previous status', function () {
    $event = ApplicationStatusEvent::factory()->create([
        'from_status' => null,
        'to_status' => ApplicationStatus::Saved,
    ]);

    expect($event->from_status)->toBeNull();
    expect($event->to_status)->toBe(ApplicationStatus::Saved);
});

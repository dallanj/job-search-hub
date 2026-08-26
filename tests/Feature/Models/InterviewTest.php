<?php

use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Models\Interview;

test('interview fields are cast to domain types', function () {
    $interview = Interview::factory()->create(['type' => InterviewType::Panel, 'outcome' => InterviewOutcome::Passed, 'scheduled_at' => '2026-09-01 14:00:00']);
    expect($interview->type)->toBe(InterviewType::Panel);
    expect($interview->outcome)->toBe(InterviewOutcome::Passed);
    expect($interview->scheduled_at->toDateTimeString())->toBe('2026-09-01 14:00:00');
});

<?php

namespace App\Models;

use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $job_application_id
 * @property int|null $contact_id
 * @property InterviewType $type
 * @property Carbon $scheduled_at
 * @property int|null $duration_minutes
 * @property string|null $location_or_url
 * @property InterviewOutcome|null $outcome
 * @property string|null $notes
 */
#[Fillable([
    'job_application_id',
    'contact_id',
    'type',
    'scheduled_at',
    'duration_minutes',
    'location_or_url',
    'outcome',
    'notes',
])]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory;

    /** @return BelongsTo<JobApplication, $this> */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    protected function casts(): array
    {
        return [
            'type' => InterviewType::class,
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'outcome' => InterviewOutcome::class,
        ];
    }
}

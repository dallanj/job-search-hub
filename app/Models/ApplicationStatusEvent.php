<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationStatusEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $job_application_id
 * @property ApplicationStatus|null $from_status
 * @property ApplicationStatus $to_status
 * @property Carbon $changed_at
 * @property string|null $note
 */
#[Fillable([
    'job_application_id',
    'from_status',
    'to_status',
    'changed_at',
    'note',
])]
class ApplicationStatusEvent extends Model
{
    /** @use HasFactory<ApplicationStatusEventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<JobApplication, $this>
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
            'changed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Concerns\HasSearchableFields;
use App\Contracts\Searchable;
use App\Enums\TaskPriority;
use App\Search\SearchField;
use App\Search\SearchRelation;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $job_application_id
 * @property string $title
 * @property Carbon|null $due_at
 * @property Carbon|null $completed_at
 * @property TaskPriority $priority
 */
#[Fillable(['job_application_id', 'title', 'due_at', 'completed_at', 'priority'])]
class Task extends Model implements Searchable
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasSearchableFields;

    /** @var array<string, mixed> */
    protected $attributes = ['priority' => 2];

    /** @return BelongsTo<JobApplication, $this> */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /** @return list<SearchField|SearchRelation> */
    public function searchableFields(): array
    {
        return [
            $this->searchField('title'),
            $this->searchRelation('jobApplication', 'role_title'),
            $this->searchRelation('jobApplication.company', 'name'),
        ];
    }

    protected function casts(): array
    {
        return ['due_at' => 'date', 'completed_at' => 'datetime', 'priority' => TaskPriority::class];
    }
}

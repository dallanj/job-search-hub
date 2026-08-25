<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $role_title
 * @property ApplicationStatus $status
 * @property int $sort_order
 * @property string|null $employment_type
 * @property string|null $workplace_type
 * @property string|null $location
 * @property string|null $source
 * @property string|null $job_url
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property string|null $salary_currency
 * @property Carbon|null $applied_at
 * @property Carbon|null $closed_at
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'company_id',
    'role_title',
    'status',
    'sort_order',
    'employment_type',
    'workplace_type',
    'location',
    'source',
    'job_url',
    'salary_min',
    'salary_max',
    'salary_currency',
    'applied_at',
    'closed_at',
    'description',
])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ApplicationStatus::Saved->value,
        'sort_order' => 0,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'sort_order' => 'integer',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'applied_at' => 'date',
            'closed_at' => 'date',
        ];
    }
}

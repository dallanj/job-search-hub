<?php

namespace App\Models;

use App\Concerns\HasSearchableFields;
use App\Contracts\Searchable;
use App\Search\SearchField;
use App\Search\SearchRelation;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $name
 * @property string|null $job_title
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $linkedin_url
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'company_id',
    'name',
    'job_title',
    'email',
    'phone',
    'linkedin_url',
    'notes',
])]
class Contact extends Model implements Searchable
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasSearchableFields;

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
     * @return list<SearchField|SearchRelation>
     */
    public function searchableFields(): array
    {
        return [
            $this->searchField('name'),
            $this->searchField('job_title'),
            $this->searchField('email'),
            $this->searchRelation('company', 'name'),
        ];
    }
}

<?php

namespace App\Concerns;

use App\Enums\TaskPriority;
use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait TaskValidationRules
{
    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    protected function taskRules(): array
    {
        return [
            'job_application_id' => ['required', 'integer', Rule::exists(JobApplication::class, 'id')->where(fn (Builder $query): Builder => $query->where('user_id', $this->user()->id))],
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date_format:Y-m-d'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
        ];
    }
}

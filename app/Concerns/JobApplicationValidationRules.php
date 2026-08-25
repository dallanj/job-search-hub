<?php

namespace App\Concerns;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait JobApplicationValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function jobApplicationRules(): array
    {
        return [
            'company_id' => [
                'nullable',
                'required_without:company_name',
                'integer',
                Rule::exists(Company::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('user_id', $this->user()->id),
                ),
            ],
            'company_name' => ['nullable', 'required_without:company_id', 'string', 'max:255'],
            'role_title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'employment_type' => ['nullable', Rule::in(['full-time', 'part-time', 'contract', 'temporary', 'internship'])],
            'workplace_type' => ['nullable', Rule::in(['remote', 'hybrid', 'on-site'])],
            'location' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'job_url' => ['nullable', 'url:http,https', 'max:2048'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'salary_currency' => ['nullable', 'required_with:salary_min,salary_max', 'alpha', 'size:3'],
            'applied_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:applied_at'],
            'description' => ['nullable', 'string', 'max:50000'],
        ];
    }
}

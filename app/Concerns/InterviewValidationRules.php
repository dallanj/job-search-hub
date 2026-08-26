<?php

namespace App\Concerns;

use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Models\Contact;
use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait InterviewValidationRules
{
    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    protected function interviewRules(): array
    {
        return [
            'job_application_id' => [
                'required', 'integer',
                Rule::exists(JobApplication::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('user_id', $this->user()->id),
                ),
            ],
            'contact_id' => [
                'nullable', 'integer',
                Rule::exists(Contact::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('user_id', $this->user()->id),
                ),
            ],
            'type' => ['required', Rule::enum(InterviewType::class)],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'location_or_url' => ['nullable', 'string', 'max:2048'],
            'outcome' => ['nullable', Rule::enum(InterviewOutcome::class)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $application = $this->user()->jobApplications()->find($this->integer('job_application_id'));
            $contact = $this->user()->contacts()->find($this->integer('contact_id'));

            if ($application && $contact && $application->company_id !== $contact->company_id) {
                $validator->errors()->add('contact_id', __('The contact must belong to the application company.'));
            }
        }];
    }
}

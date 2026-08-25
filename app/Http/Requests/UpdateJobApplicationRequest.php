<?php

namespace App\Http\Requests;

use App\Concerns\JobApplicationValidationRules;
use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobApplicationRequest extends FormRequest
{
    use JobApplicationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $jobApplication = $this->route('application');

        return $jobApplication instanceof JobApplication
            && $this->user()->can('update', $jobApplication);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->jobApplicationRules();
    }
}

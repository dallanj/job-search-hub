<?php

namespace App\Http\Requests;

use App\Concerns\InterviewValidationRules;
use App\Models\Interview;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInterviewRequest extends FormRequest
{
    use InterviewValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $interview = $this->route('interview');

        return $interview instanceof Interview
            && $this->user()->can('update', $interview);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->interviewRules(),
        ];
    }
}

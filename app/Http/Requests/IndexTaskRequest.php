<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Task::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['open', 'overdue', 'completed'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('search');
        if (is_string($search)) {
            $normalized = Str::of($search)->squish()->toString();
            $this->merge(['search' => $normalized === '' ? null : $normalized]);
        }
    }
}

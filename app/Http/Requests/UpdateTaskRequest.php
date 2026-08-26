<?php

namespace App\Http\Requests;

use App\Concerns\TaskValidationRules;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    use TaskValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()->can('update', $task);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->taskRules(),
        ];
    }
}

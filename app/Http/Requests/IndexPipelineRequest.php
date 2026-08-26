<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexPipelineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', JobApplication::class);
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
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('user_id', $this->user()->id),
                ),
            ],
            'location' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->normalizedString('search'),
            'location' => $this->normalizedString('location'),
        ]);
    }

    private function normalizedString(string $key): mixed
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return $value;
        }

        $normalized = Str::of($value)->squish()->toString();

        return $normalized === '' ? null : $normalized;
    }
}

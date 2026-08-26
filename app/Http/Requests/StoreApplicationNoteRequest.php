<?php

namespace App\Http\Requests;

use App\Models\ApplicationNote;
use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ApplicationNote::class);
    }

    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    public function rules(): array
    {
        return [
            'job_application_id' => [
                'required',
                'integer',
                Rule::exists(JobApplication::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('user_id', $this->user()->id),
                ),
            ],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}

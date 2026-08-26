<?php

namespace App\Http\Requests;

use App\Models\ApplicationNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('application_note');

        return $note instanceof ApplicationNote && $this->user()->can('update', $note);
    }

    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:10000']];
    }
}

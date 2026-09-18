<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $paperId = $this->route('paper')?->id;

        return [
            'track_id' => ['required', 'integer', Rule::exists('tracks', 'id')],
            'paper_no' => ['required', 'string', 'max:50', Rule::unique('papers', 'paper_no')->ignore($paperId)],
            'title' => ['required', 'string', 'max:255'],
            'researcher' => ['required', 'string', 'max:255'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'presentation_order' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paper_no' => trim((string) $this->paper_no),
            'title' => trim((string) $this->title),
            'researcher' => trim((string) $this->researcher),
            'affiliation' => $this->affiliation !== null ? trim((string) $this->affiliation) : null,
            'presentation_order' => $this->presentation_order === '' ? null : $this->presentation_order,
        ]);
    }
}

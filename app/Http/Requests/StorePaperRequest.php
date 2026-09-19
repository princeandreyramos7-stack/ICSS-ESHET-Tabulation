<?php

namespace App\Http\Requests;

use App\Support\UploadLimit;
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
            // The ceiling is whatever this host really accepts, not a hard-coded guess.
            'manuscript' => ['nullable', 'file', 'mimes:pdf', 'max:' . UploadLimit::manuscriptKilobytes()],
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

    public function messages(): array
    {
        $max = UploadLimit::manuscriptMegabytes();

        return [
            'manuscript.mimes' => 'The manuscript must be a PDF file.',
            'manuscript.max' => "The manuscript must not exceed {$max} MB.",
            // PHP dropped the file before Laravel saw it (upload_max_filesize) - say so plainly.
            'manuscript.uploaded' => "The manuscript could not be uploaded: it is larger than this server accepts ({$max} MB).",
        ];
    }
}

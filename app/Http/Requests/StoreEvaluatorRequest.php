<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEvaluatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('evaluator')?->id;
        $isUpdate = $userId !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Password is required on create, optional on update (blank = keep current).
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', Password::min(8)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => strtolower(trim((string) $this->email)),
            'password' => $this->password === '' ? null : $this->password,
        ]);
    }
}

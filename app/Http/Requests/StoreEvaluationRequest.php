<?php

namespace App\Http\Requests;

use App\Models\Criterion;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEvaluator() ?? false;
    }

    /**
     * Every criterion must be rated, and each rating is capped at that
     * criterion's weight (e.g. Originality is 0-25).
     */
    public function rules(): array
    {
        $criteria = Criterion::ordered()->get();

        $rules = [
            'scores' => ['required', 'array', 'size:' . $criteria->count()],
            'comments' => ['nullable', 'string', 'max:3000'],
        ];

        foreach ($criteria as $criterion) {
            $rules["scores.{$criterion->id}"] = [
                'required',
                'numeric',
                'min:0',
                "max:{$criterion->weight}",
                'decimal:0,2',
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return Criterion::ordered()->get()
            ->mapWithKeys(fn ($c) => ["scores.{$c->id}" => $c->name])
            ->all();
    }

    public function messages(): array
    {
        return [
            'scores.size' => 'Every criterion must be rated.',
            'scores.*.required' => 'A rating is required for :attribute.',
            'scores.*.max' => 'The rating for :attribute may not be greater than :max.',
            'scores.*.min' => 'The rating for :attribute may not be negative.',
            'scores.*.decimal' => 'Ratings may have at most two decimal places.',
        ];
    }
}

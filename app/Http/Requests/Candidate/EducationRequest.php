<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Month inputs (YYYY-MM) are stored as the first day of that month.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->filled('start_date') ? $this->input('start_date').'-01' : null,
            'end_date' => $this->filled('end_date') ? $this->input('end_date').'-01' : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'school' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', Rule::in(config('recruitment.degrees'))],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school' => 'trường',
            'major' => 'chuyên ngành',
            'degree' => 'bằng cấp',
            'start_date' => 'thời gian bắt đầu',
            'end_date' => 'thời gian kết thúc',
            'description' => 'mô tả',
        ];
    }
}

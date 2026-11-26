<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(config('recruitment.job_categories'))],
            'location' => ['nullable', Rule::in(config('recruitment.locations'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'keyword' => 'từ khóa',
            'category' => 'ngành nghề',
            'location' => 'địa điểm',
        ];
    }
}

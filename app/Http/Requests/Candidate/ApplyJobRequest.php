<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The chosen CV must belong to the applicant.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cv_id' => ['required', 'integer', Rule::exists('cvs', 'id')->where('user_id', $this->user()->id)],
            'cover_letter' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cv_id' => 'CV',
            'cover_letter' => 'thư giới thiệu',
        ];
    }
}

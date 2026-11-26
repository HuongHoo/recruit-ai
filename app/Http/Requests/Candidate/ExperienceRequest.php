<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class ExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Month inputs (YYYY-MM) are stored as the first day of that month.
     * A current job never has an end date.
     */
    protected function prepareForValidation(): void
    {
        $isCurrent = $this->boolean('is_current');

        $this->merge([
            'is_current' => $isCurrent,
            'start_date' => $this->filled('start_date') ? $this->input('start_date').'-01' : null,
            'end_date' => ! $isCurrent && $this->filled('end_date') ? $this->input('end_date').'-01' : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'is_current' => ['boolean'],
            'end_date' => ['nullable', 'required_if:is_current,false', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company' => 'công ty',
            'position' => 'vị trí',
            'start_date' => 'thời gian bắt đầu',
            'end_date' => 'thời gian kết thúc',
            'description' => 'mô tả công việc',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.required_if' => 'Vui lòng nhập thời gian kết thúc hoặc chọn "Đang làm việc tại đây".',
        ];
    }
}

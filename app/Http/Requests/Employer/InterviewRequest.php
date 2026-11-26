<?php

namespace App\Http\Requests\Employer;

use App\Enums\InterviewType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterviewRequest extends FormRequest
{
    /** Ownership of the application/interview is checked in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'integer', 'exists:applications,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in([15, 30, 45, 60, 90, 120])],
            'type' => ['required', Rule::enum(InterviewType::class)],
            'location' => ['nullable', 'string', 'max:500', Rule::requiredIf($this->input('type') !== InterviewType::Phone->value)],
            'interviewer' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'application_id' => 'ứng viên',
            'scheduled_at' => 'thời gian phỏng vấn',
            'duration_minutes' => 'thời lượng',
            'type' => 'hình thức',
            'location' => 'địa điểm / link',
            'interviewer' => 'người phỏng vấn',
            'note' => 'ghi chú',
        ];
    }
}

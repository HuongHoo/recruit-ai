<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Online CV form. When sent from the step-by-step builder (builder=1) it also carries
 * the CV content: personal info, experiences, educations and skills.
 */
class StoreOnlineCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->boolean('builder')) {
            return;
        }

        $this->merge([
            'skills' => collect($this->input('skills', []))
                ->filter(fn ($skill) => is_string($skill) && trim($skill) !== '')
                ->map(fn (string $skill): string => trim($skill))
                ->unique(fn (string $skill): string => mb_strtolower($skill))
                ->values()->all(),
            'experiences' => array_values((array) $this->input('experiences', [])),
            'educations' => array_values((array) $this->input('educations', [])),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:100'],
            'template' => ['required', Rule::in(array_keys(config('recruitment.cv.templates')))],
            'accent_color' => ['required', Rule::in(config('recruitment.cv.accent_colors'))],
            'objective' => ['nullable', 'string', 'max:1000'],
        ];

        if (! $this->boolean('builder')) {
            return $rules;
        }

        return $rules + [
            'name' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s.\-]{8,20}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:-15 years', 'after:1940-01-01'],
            'skills' => ['array', 'max:30'],
            'skills.*' => ['string', 'max:50'],

            'experiences' => ['array', 'max:20'],
            'experiences.*.id' => ['nullable', 'integer'],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.position' => ['required', 'string', 'max:255'],
            'experiences.*.start_date' => ['required', 'date_format:Y-m', 'before_or_equal:today'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.end_date' => ['nullable', 'required_without:experiences.*.is_current', 'date_format:Y-m', 'after_or_equal:experiences.*.start_date'],
            'experiences.*.description' => ['nullable', 'string', 'max:3000'],

            'educations' => ['array', 'max:10'],
            'educations.*.id' => ['nullable', 'integer'],
            'educations.*.school' => ['required', 'string', 'max:255'],
            'educations.*.major' => ['nullable', 'string', 'max:255'],
            'educations.*.degree' => ['nullable', Rule::in(config('recruitment.degrees'))],
            'educations.*.start_date' => ['required', 'date_format:Y-m', 'before_or_equal:today'],
            'educations.*.end_date' => ['nullable', 'date_format:Y-m', 'after_or_equal:educations.*.start_date'],
            'educations.*.description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tên CV',
            'template' => 'mẫu CV',
            'accent_color' => 'màu chủ đạo',
            'objective' => 'tóm tắt',
            'name' => 'họ và tên',
            'headline' => 'vị trí ứng tuyển',
            'phone' => 'số điện thoại',
            'address' => 'địa chỉ',
            'date_of_birth' => 'ngày sinh',
            'skills' => 'kỹ năng',
            'skills.*' => 'kỹ năng',
            'experiences.*.company' => 'tên công ty',
            'experiences.*.position' => 'vị trí',
            'experiences.*.start_date' => 'thời gian bắt đầu',
            'experiences.*.end_date' => 'thời gian kết thúc',
            'experiences.*.description' => 'mô tả công việc',
            'educations.*.school' => 'tên trường',
            'educations.*.major' => 'chuyên ngành',
            'educations.*.degree' => 'bằng cấp',
            'educations.*.start_date' => 'thời gian bắt đầu',
            'educations.*.end_date' => 'thời gian kết thúc',
            'educations.*.description' => 'mô tả',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'date_of_birth.before' => 'Bạn phải từ 15 tuổi trở lên.',
            'experiences.*.end_date.required_without' => 'Nhập thời gian kết thúc hoặc chọn "Đang làm việc ở đây".',
        ];
    }
}

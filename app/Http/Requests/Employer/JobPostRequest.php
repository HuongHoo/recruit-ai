<?php

namespace App\Http\Requests\Employer;

use App\Enums\JobPostStatus;
use App\Enums\JobType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(config('recruitment.job_categories'))],
            'location' => ['required', Rule::in(config('recruitment.locations'))],
            'job_type' => ['required', Rule::enum(JobType::class)],
            'experience' => ['nullable', Rule::in(config('recruitment.experience_levels'))],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:1000', 'gte:salary_min'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'benefits' => ['nullable', 'string', 'max:10000'],
            'deadline' => ['nullable', 'date', $this->isMethod('post') ? 'after_or_equal:today' : 'date'],
            'status' => ['required', Rule::enum(JobPostStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tiêu đề',
            'company_name' => 'tên công ty',
            'category' => 'ngành nghề',
            'location' => 'địa điểm',
            'job_type' => 'hình thức làm việc',
            'experience' => 'kinh nghiệm',
            'salary_min' => 'lương tối thiểu',
            'salary_max' => 'lương tối đa',
            'description' => 'mô tả công việc',
            'requirements' => 'yêu cầu',
            'benefits' => 'quyền lợi',
            'deadline' => 'hạn nộp hồ sơ',
            'status' => 'trạng thái',
        ];
    }
}

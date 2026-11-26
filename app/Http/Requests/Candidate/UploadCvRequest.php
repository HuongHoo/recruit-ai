<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadCvRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:100'],
            // Set when uploading from a job page's "Hỗ trợ ứng tuyển" box: go back there afterwards.
            'return_to_job' => ['nullable', 'integer', 'exists:job_posts,id'],
            'support_tab' => ['nullable', 'in:interview,match'],
            'file' => [
                'required',
                File::types(['pdf', 'doc', 'docx'])->max(config('recruitment.cv.max_upload_kb')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tên CV',
            'file' => 'tệp CV',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'CV phải là tệp PDF hoặc Word (.doc, .docx).',
            'file.max' => 'Dung lượng CV tối đa là 5 MB.',
        ];
    }
}

<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Turn the comma separated skills input into a clean, unique array.
     */
    protected function prepareForValidation(): void
    {
        $skills = collect(explode(',', (string) $this->input('skills')))
            ->map(fn (string $skill): string => trim($skill))
            ->filter()
            ->unique(fn (string $skill): string => mb_strtolower($skill))
            ->values()
            ->all();

        $this->merge([
            'skills' => $skills,
            'desired_locations' => $this->input('desired_locations', []),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s.\-]{8,20}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:-15 years', 'after:1940-01-01'],
            'gender' => ['nullable', Rule::in(array_keys(config('recruitment.genders')))],
            'address' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'desired_salary_min' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'desired_salary_max' => ['nullable', 'integer', 'min:0', 'max:1000', 'gte:desired_salary_min'],
            'desired_locations' => ['array', 'max:5'],
            'desired_locations.*' => ['string', Rule::in(config('recruitment.locations'))],
            'skills' => ['array', 'max:30'],
            'skills.*' => ['string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'họ tên',
            'phone' => 'số điện thoại',
            'date_of_birth' => 'ngày sinh',
            'gender' => 'giới tính',
            'address' => 'địa chỉ',
            'headline' => 'vị trí mong muốn',
            'summary' => 'giới thiệu bản thân',
            'desired_salary_min' => 'lương tối thiểu',
            'desired_salary_max' => 'lương tối đa',
            'desired_locations' => 'nơi muốn làm việc',
            'skills' => 'kỹ năng',
            'skills.*' => 'kỹ năng',
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
            'desired_salary_max.gte' => 'Lương tối đa phải lớn hơn hoặc bằng lương tối thiểu.',
            'desired_locations.max' => 'Chọn tối đa 5 nơi làm việc.',
        ];
    }
}

<?php

namespace App\Http\Requests\Employer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
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
        $hasCompany = $this->user()->company !== null;

        return [
            'logo' => [$hasCompany ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('recruitment.employer.logo_max_kb')],
            'name' => ['required', 'string', 'max:255'],
            'business_model' => ['required', Rule::in(array_keys(config('recruitment.employer.business_models')))],
            'size' => ['nullable', Rule::in(array_keys(config('recruitment.employer.company_sizes')))],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['required', 'url:http,https', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+84|0)[0-9 .]{8,13}$/'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'logo' => 'logo công ty',
            'name' => 'tên công ty',
            'business_model' => 'mô hình hoạt động',
            'size' => 'quy mô công ty',
            'industry' => 'ngành nghề',
            'phone' => 'số điện thoại',
            'description' => 'giới thiệu công ty',
            'contact_email' => 'email liên hệ',
            'address' => 'địa chỉ',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.regex' => 'Số điện thoại không hợp lệ.'];
    }
}

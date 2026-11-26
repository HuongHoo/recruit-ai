<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterEmployerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+84|0)[0-9 .]{8,13}$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('recruitment.employer.logo_max_kb')],
            'business_model' => ['required', Rule::in(array_keys(config('recruitment.employer.business_models')))],
            'company_name' => ['required', 'string', 'max:255'],
            'company_size' => ['nullable', Rule::in(array_keys(config('recruitment.employer.company_sizes')))],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['required', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên',
            'phone' => 'số điện thoại',
            'password' => 'mật khẩu',
            'logo' => 'logo công ty',
            'business_model' => 'mô hình hoạt động',
            'company_name' => 'tên công ty',
            'company_size' => 'quy mô công ty',
            'industry' => 'ngành nghề',
            'description' => 'hồ sơ công ty',
            'terms' => 'điều khoản sử dụng',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'terms.accepted' => 'Bạn cần đồng ý với Điều khoản sử dụng và Chính sách bảo mật.',
        ];
    }
}

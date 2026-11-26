<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route is already limited to admins.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isEvent = $this->input('type') === PostType::Event->value;

        return [
            'type' => ['required', Rule::enum(PostType::class)],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:50000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
            'event_date' => [Rule::requiredIf($isEvent), 'nullable', 'date'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'loại nội dung',
            'title' => 'tiêu đề',
            'category' => 'chuyên mục',
            'excerpt' => 'mô tả ngắn',
            'content' => 'nội dung',
            'cover' => 'ảnh bìa',
            'event_date' => 'ngày diễn ra',
            'event_location' => 'địa điểm',
            'published_at' => 'thời gian đăng',
        ];
    }
}

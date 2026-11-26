<?php

namespace App\Http\Requests\Community;

use App\Enums\CommunityTopic;
use App\Models\CommunityPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create or edit a community post. On edit, kept images + new images may not exceed MAX_IMAGES.
 */
class CommunityPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof CommunityPost
            ? $this->user()->can('update', $post)
            : $this->user()->can('create', CommunityPost::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'topic' => ['required', Rule::enum(CommunityTopic::class)],
            'body' => ['required', 'string', 'max:10000'],
            'images' => ['nullable', 'array', 'max:'.CommunityPost::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $post = $this->route('post');

                if (! $post instanceof CommunityPost) {
                    return;
                }

                $kept = $post->images()->whereNotIn('id', $this->removedImageIds())->count();

                if ($kept + count($this->file('images', [])) > CommunityPost::MAX_IMAGES) {
                    $validator->errors()->add('images', 'Mỗi bài viết có tối đa '.CommunityPost::MAX_IMAGES.' ảnh.');
                }
            },
        ];
    }

    /**
     * @return array<int, int>
     */
    public function removedImageIds(): array
    {
        return array_map('intval', (array) $this->input('remove_images', []));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tiêu đề',
            'topic' => 'chủ đề',
            'body' => 'nội dung',
            'images' => 'ảnh',
            'images.*' => 'ảnh',
        ];
    }
}

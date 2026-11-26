<?php

namespace App\Http\Requests\Community;

use App\Models\CommunityPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Body of a new or edited comment; on create, parent_id must be a comment of the same post.
 */
class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Checked in the controller (post visibility / comment ownership).
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => $post instanceof CommunityPost
                ? ['nullable', 'integer', Rule::exists('community_comments', 'id')->where('community_post_id', $post->id)]
                : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'bình luận', 'parent_id' => 'bình luận được trả lời'];
    }
}

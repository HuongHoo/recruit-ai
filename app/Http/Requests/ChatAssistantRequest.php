<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The recent conversation the chat widget sends: at most 10 messages, the last one from the user.
 */
class ChatAssistantRequest extends FormRequest
{
    public const MAX_MESSAGES = 10;

    public function authorize(): bool
    {
        return true; // Open to guests.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'messages' => ['required', 'array', 'min:1', 'max:'.self::MAX_MESSAGES],
            'messages.*' => ['required', 'array:role,text'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.text' => ['required', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $messages = $this->input('messages');

                if (is_array($messages) && $messages !== [] && (end($messages)['role'] ?? null) !== 'user') {
                    $validator->errors()->add('messages', 'Tin nhắn cuối cùng phải là của người dùng.');
                }
            },
        ];
    }

    /**
     * @return list<array{role: string, text: string}>
     */
    public function conversation(): array
    {
        return array_values(array_map(
            fn (array $message) => ['role' => $message['role'], 'text' => trim($message['text'])],
            $this->validated('messages'),
        ));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['messages' => 'tin nhắn', 'messages.*.text' => 'tin nhắn', 'messages.*.role' => 'vai trò'];
    }
}

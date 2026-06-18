<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssistantChatRequest extends FormRequest
{
    /**
     * The assistant is open to everyone, including unauthenticated guests
     * (who receive a public-only, read-only tool set).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}

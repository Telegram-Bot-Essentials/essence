<?php

namespace Elyar\TelegramBotEssentials\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'bot_token' => 'required|string|max:255',
            'bot_owner_peer_id' => 'required|integer',
            'activated_until' => 'nullable|date',
            'suspended_at' => 'nullable|date',
            'currency' => 'nullable|string|max:10',
        ];

        if($this->isMethod('PUT')){
            $rules['bot_owner_peer_id'] = 'nullable|integer';
        }

        if($this->isMethod('PATCH')){
            $rules['bot_token'] = 'nullable|string|max:255';
            $rules['bot_owner_peer_id'] = 'nullable|integer';
        }

        return $rules;
    }
}

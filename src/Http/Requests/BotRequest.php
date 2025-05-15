<?php

namespace Elyar\TelegramBotEssentials\Http\Requests;

use Elyar\TelegramBotEssentials\Traits\HttpResponses;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BotRequest extends FormRequest
{
    use HttpResponses;

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
        ];

        if($this->isMethod('PUT')){
            $rules['bot_owner_peer_id'] = 'nullable|integer';
        }

        return $rules;
    }
}

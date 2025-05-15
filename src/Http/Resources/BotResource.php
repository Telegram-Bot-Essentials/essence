<?php

namespace Elyar\TelegramBotEssentials\Http\Resources;

use Elyar\TelegramBotEssentials\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;

class BotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource instanceof Bot) throw new InvalidArgumentException('Bot resource must be instance of Bot');

        return [
            'id' => $this->resource->id,
            'unique_id' => $this->resource->unique_id,
            'bot_token' => $this->resource->bot_token,
            'secret_token' => $this->resource->secret_token,
            'bot_owner' => $this->resource->botOwner,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}

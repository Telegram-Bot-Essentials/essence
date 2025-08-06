<?php

namespace Elyar\TelegramBotEssentials\Services;

use Elyar\TelegramBotEssentials\Models\StateData;

class StateDataService
{
    public function store(array $data = []): StateData
    {
        return StateData::create([
            'data' => $data
        ]);
    }

    public function addData(StateData $formData, array $data): void
    {
        $formData->data = array_merge($formData->data, $data);
        $formData->save();
    }
}

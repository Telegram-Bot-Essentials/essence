<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Models\Contracts\HasName;
use TelegramBotEssentials\Essence\Models\Bot;

/** The panels' tenant. Apart from Bot so core models never load Filament's contracts. */
class PanelBot extends Bot implements HasName
{
    public function getFilamentName(): string
    {
        return $this->unique_id;
    }

    /** Keys and morph names Laravel infers from the class must stay Bot's. */
    public function getForeignKey(): string
    {
        return 'bot_id';
    }

    public function getMorphClass(): string
    {
        return (new Bot)->getMorphClass();
    }
}

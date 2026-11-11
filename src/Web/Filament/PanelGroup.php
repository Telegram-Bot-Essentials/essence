<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * The sidebar's sections, shared by every package so their screens sit
 * together instead of each inventing a group. Listed in sidebar order.
 */
enum PanelGroup: string implements HasLabel
{
    case Members = 'members';
    case Sales = 'sales';
    case Marketing = 'marketing';
    case Support = 'support';

    public function getLabel(): string
    {
        return (string) __('tbe-essence::web.groups.'.$this->value);
    }
}

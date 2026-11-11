<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Navigation\NavigationGroup;
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
    case Bot = 'bot';

    public function getLabel(): string
    {
        return (string) __('tbe-essence::web.groups.'.$this->value);
    }

    /**
     * The groups in sidebar order, keyed by case name as Filament matches a
     * resource's enum group. Labels are read when the sidebar renders: the
     * panel is built before translations load, and an early read would
     * cache the whole string group empty.
     *
     * @return array<string, NavigationGroup>
     */
    public static function navigationGroups(): array
    {
        $groups = [];
        foreach (self::cases() as $case) {
            $groups[$case->name] = NavigationGroup::make()->label(fn (): string => $case->getLabel());
        }

        return $groups;
    }
}

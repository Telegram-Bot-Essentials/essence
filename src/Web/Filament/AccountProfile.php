<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/**
 * The account's name, email and password, as Filament's profile page has
 * them. An account made from Telegram has no password yet, so its first one
 * needs no current password: the Telegram sign-in already proved who it is.
 */
class AccountProfile extends EditProfile
{
    protected function getCurrentPasswordFormComponent(): Component
    {
        $component = parent::getCurrentPasswordFormComponent();

        return $component->visible(fn (Get $get): bool => $this->hasPassword()
            && (filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email'))));
    }

    private function hasPassword(): bool
    {
        // Typed string, yet null for an account made from Telegram.
        return filled($this->getUser()->getAuthPassword());
    }
}

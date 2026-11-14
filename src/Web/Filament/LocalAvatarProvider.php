<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/** Filament's default sends each user's name to ui-avatars.com; this stays on the page. */
class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $name = $record instanceof Model ? Filament::getNameForDefaultAvatar($record) : '';
        $initial = e(mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#6b7280"/>'
            .'<text x="32" y="42" font-family="sans-serif" font-size="28" text-anchor="middle" fill="#fff">'.$initial.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}

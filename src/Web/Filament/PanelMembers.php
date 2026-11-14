<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * How the panel names a member, the same on every screen: the Telegram name
 * (or the peer id when there is none), with the @username beside it.
 */
final class PanelMembers
{
    public static function name(mixed $user): string
    {
        $telegramUser = $user instanceof BotUser ? $user->telegramUser : null;
        if (! $telegramUser instanceof TelegramUser) {
            return '?';
        }

        $name = trim((string) $telegramUser->full_name);

        return $name !== '' ? $name : (string) $telegramUser->peer_id;
    }

    public static function username(mixed $user): ?string
    {
        $telegramUser = $user instanceof BotUser ? $user->telegramUser : null;

        return $telegramUser instanceof TelegramUser && filled($telegramUser->username) ? '@'.$telegramUser->username : null;
    }

    /** "Mina (@mina)", for one line such as a select option or a notification. */
    public static function label(mixed $user): string
    {
        $username = self::username($user);

        return $username === null ? self::name($user) : self::name($user).' ('.$username.')';
    }

    /**
     * A column naming the member on the record's $relation, the username under it.
     */
    public static function column(string $relation = 'botUser', ?string $label = null): TextColumn
    {
        $member = fn (Model $record): mixed => $relation === '' ? $record : $record->getRelationValue($relation);

        return TextColumn::make($relation === '' ? 'member' : $relation.'_member')
            ->label($label ?? (string) __('tbe-essence::web.member'))
            ->state(fn (Model $record): string => self::name($member($record)))
            ->description(fn (Model $record): ?string => self::username($member($record)));
    }
}

<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * One Telegram account linked to a web account.
 *
 * @property int $account_id
 * @property int $telegram_user_peer_id
 */
class TelegramLink extends Model
{
    protected $table = 'account_telegram_users';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'account_id' => 'integer',
        'telegram_user_peer_id' => 'integer',
    ];

    /** @return BelongsTo<TelegramUser, $this> */
    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_peer_id', 'peer_id');
    }

    /** The account linked to $peerId, of the configured account model. */
    public static function accountFor(int $peerId): ?TelegramAccount
    {
        $link = static::query()->where('telegram_user_peer_id', $peerId)->first();
        if (! $link instanceof self) {
            return null;
        }

        $model = WebAccounts::model();
        $account = $model::query()->find($link->account_id);

        return $account instanceof TelegramAccount ? $account : null;
    }
}
